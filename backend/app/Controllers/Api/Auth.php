<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthToken;
use App\Libraries\CurrentUser;
use App\Models\AuthMagicLinkModel;
use App\Models\BoardSectionModel;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Auth extends BaseController
{
    /** @var list<string> */
    protected $helpers = ['url'];

    /**
     * POST /api/auth/register
     */
    public function register(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $lastName  = isset($payload['lastName']) ? trim((string) $payload['lastName']) : '';
        $firstName = isset($payload['firstName']) ? trim((string) $payload['firstName']) : '';
        $email     = isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : '';
        $password  = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($lastName === '' || $firstName === '' || $email === '' || $password === '') {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'Tous les champs sont requis.']]);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['email' => 'Adresse e-mail invalide.']]);
        }

        if (strlen($password) < 8) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['password' => 'Le mot de passe doit contenir au moins 8 caracteres.']]);
        }

        $userModel = model(UserModel::class);

        if ($userModel->where('email', $email)->first() !== null) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['email' => 'Cette adresse e-mail est deja utilisee.']]);
        }

        $db = $this->db;
        $db->transStart();

        if ($userModel->insert([
            'last_name'  => $lastName,
            'first_name' => $firstName,
            'email'      => $email,
            'password'   => $password,
            'role'       => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ]) === false) {
            $db->transRollback();

            return $this->response->setStatusCode(422)->setJSON(['errors' => $userModel->errors()]);
        }

        $userId = (int) $userModel->getInsertID();

        $sectionRows = [
            ['user_id' => $userId, 'name' => 'À faire', 'slug' => 'todo', 'sort_order' => 0],
            ['user_id' => $userId, 'name' => 'En cours', 'slug' => 'in_progress', 'sort_order' => 1],
            ['user_id' => $userId, 'name' => 'Terminé', 'slug' => 'done', 'sort_order' => 2],
        ];

        $db->table('board_sections')->insertBatch($sectionRows);

        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expires   = date('Y-m-d H:i:s', time() + 86400 * 2);

        model(AuthMagicLinkModel::class)->insert([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expires,
            'used_at'    => null,
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Inscription impossible pour le moment.']);
        }

        log_message('info', 'Inscription: compte cree user_id=' . $userId . ' email=' . $email);

        $mailStatus = $this->sendWelcomeEmail($email, $firstName, $rawToken);

        $user = $userModel->find($userId);

        $mailSent = $mailStatus === 'sent';
        $message  = $mailSent
            ? 'Compte cree. Verifiez votre boite e-mail pour le lien de connexion.'
            : 'Compte cree. Connexion possible avec votre e-mail et mot de passe. L e-mail de lien n a pas pu etre envoye — verifiez la configuration SMTP (logs serveur).';

        log_message(
            $mailSent ? 'info' : 'warning',
            'Inscription: envoi e-mail bienvenue mailStatus=' . $mailStatus . ' user_id=' . $userId . ' email=' . $email,
        );

        return $this->response->setStatusCode(201)->setJSON([
            'user'       => $userModel->serializePublic($user),
            'message'    => $message,
            'mailSent'   => $mailSent,
            'mailStatus' => $mailStatus,
        ]);
    }

    /**
     * POST /api/auth/login
     */
    public function login(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $email    = isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';

        if ($email === '' || $password === '') {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['form' => 'E-mail et mot de passe requis.']]);
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->where('email', $email)->first();

        if ($user === null || ! password_verify($password, (string) $user['password'])) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Identifiants incorrects.']);
        }

        $jwt = $this->mintJwt((int) $user['id'], (string) $user['role']);

        $response = $this->response->setJSON([
            'user'         => $userModel->serializePublic($user),
            'accessToken'  => $jwt,
        ]);

        return $this->applyAuthCookie($response, $jwt);
    }

    /**
     * GET /api/auth/magic?token=...
     */
    public function magic(): ResponseInterface
    {
        $token = $this->request->getGet('token');
        $token = is_string($token) ? trim($token) : '';

        $frontend = config('Auth')->frontendBaseUrl;
        $failUrl  = $frontend . '/login?erreur=lien';

        if ($token === '') {
            return $this->response->redirect($failUrl);
        }

        $hash  = hash('sha256', $token);
        $model = model(AuthMagicLinkModel::class);
        $row   = $model->where('token_hash', $hash)->first();

        if ($row === null) {
            return $this->response->redirect($failUrl);
        }

        $expires = strtotime((string) $row['expires_at']);

        if ($expires === false || $expires < time()) {
            return $this->response->redirect($failUrl);
        }

        if ($row['used_at'] !== null && $row['used_at'] !== '') {
            return $this->response->redirect($failUrl);
        }

        $userId = (int) $row['user_id'];
        $user   = model(UserModel::class)->find($userId);

        if ($user === null) {
            return $this->response->redirect($failUrl);
        }

        $model->update((int) $row['id'], ['used_at' => date('Y-m-d H:i:s')]);

        $jwt = $this->mintJwt($userId, (string) $user['role']);
        $base = rtrim($frontend, '/');
        $okUrl = $base . '/?connexion=1&accessToken=' . rawurlencode($jwt);
        $response = $this->response->redirect($okUrl);

        return $this->applyAuthCookie($response, $jwt);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): ResponseInterface
    {
        $cfg      = config('Auth');
        $response = $this->response->setJSON(['ok' => true]);

        return $response->deleteCookie($cfg->cookieName);
    }

    /**
     * DELETE /api/auth/account — supprime l’utilisateur connecté et toutes ses données.
     */
    public function deleteAccount(): ResponseInterface
    {
        $uid = CurrentUser::id();

        if ($uid === null) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Non authentifié.']);
        }

        $db = $this->db;
        $db->transStart();

        $db->table('auth_magic_links')->where('user_id', $uid)->delete();
        $db->table('todos')->where('user_id', $uid)->delete();
        $db->table('board_sections')->where('user_id', $uid)->delete();

        if (model(UserModel::class)->delete($uid) === false) {
            $db->transRollback();

            return $this->response->setStatusCode(500)->setJSON(['error' => 'Suppression du compte impossible.']);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Suppression du compte impossible.']);
        }

        $cfg      = config('Auth');
        $response = $this->response->setJSON(['ok' => true]);

        return $response->deleteCookie($cfg->cookieName);
    }

    /**
     * GET /api/auth/me
     */
    public function me(): ResponseInterface
    {
        $uid = CurrentUser::id();

        if ($uid === null) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Non authentifié.']);
        }

        $user = model(UserModel::class)->find($uid);

        if ($user === null) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Utilisateur introuvable.']);
        }

        return $this->response->setJSON(model(UserModel::class)->serializePublic($user));
    }

    /**
     * POST /api/auth/password-reset/request
     * Corps : lastName, firstName, email — si correspondance, envoi d’un e-mail avec lien.
     */
    public function passwordResetRequest(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $lastName  = isset($payload['lastName']) ? trim((string) $payload['lastName']) : '';
        $firstName = isset($payload['firstName']) ? trim((string) $payload['firstName']) : '';
        $email     = isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : '';

        $generic = [
            'message' => 'Si les informations correspondent a un compte, un e-mail vous a ete envoye.',
        ];

        if ($lastName === '' || $firstName === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON($generic);
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->where('email', $email)->first();

        if ($user === null) {
            return $this->response->setJSON($generic);
        }

        if (
            $this->normalizePersonName((string) $user['last_name']) !== $this->normalizePersonName($lastName)
            || $this->normalizePersonName((string) $user['first_name']) !== $this->normalizePersonName($firstName)
        ) {
            return $this->response->setJSON($generic);
        }

        $userId = (int) $user['id'];

        $this->db->table('password_resets')->where('user_id', $userId)->delete();

        $rawRequest  = bin2hex(random_bytes(32));
        $requestHash = hash('sha256', $rawRequest);
        $expiresReq  = date('Y-m-d H:i:s', time() + 3600);

        $prModel = model(PasswordResetModel::class);

        if (
            $prModel->insert([
                'user_id'               => $userId,
                'request_token_hash'    => $requestHash,
                'completion_token_hash' => null,
                'request_expires_at'    => $expiresReq,
                'completion_expires_at' => null,
                'created_at'            => date('Y-m-d H:i:s'),
            ]) === false
        ) {
            log_message('error', 'password_resets insert: ' . json_encode($prModel->errors()));

            return $this->response->setJSON($generic);
        }

        $resetMailStatus = $this->sendPasswordResetRequestEmail($email, (string) $user['first_name'], $rawRequest);

        log_message(
            $resetMailStatus === 'sent' ? 'info' : 'warning',
            'Reinit. mot de passe: envoi e-mail mailStatus=' . $resetMailStatus . ' user_id=' . $userId . ' email=' . $email,
        );

        return $this->response->setJSON($generic);
    }

    /**
     * POST /api/auth/password-reset/confirm
     * Corps : token (jeton de la demande, depuis l’e-mail / l’URL).
     */
    public function passwordResetConfirm(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $token = isset($payload['token']) ? trim((string) $payload['token']) : '';

        if ($token === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Jeton manquant.']);
        }

        $hash = hash('sha256', $token);
        $model = model(PasswordResetModel::class);
        $row   = $model->where('request_token_hash', $hash)->first();

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Lien invalide ou expire.']);
        }

        $reqExp = strtotime((string) $row['request_expires_at']);

        if ($reqExp === false || $reqExp < time()) {
            $model->delete((int) $row['id']);

            return $this->response->setStatusCode(410)->setJSON(['error' => 'Lien expire. Demandez une nouvelle reinitialisation.']);
        }

        $existingCompletion = $row['completion_token_hash'] ?? null;
        $compExpTs          = isset($row['completion_expires_at']) ? strtotime((string) $row['completion_expires_at']) : false;

        if (
            $existingCompletion !== null && $existingCompletion !== ''
            && $compExpTs !== false && $compExpTs >= time()
        ) {
            return $this->response->setStatusCode(409)->setJSON([
                'error' => 'Cette etape a deja ete validee. Utilisez le formulaire de nouveau mot de passe sur la meme page, ou recommencez depuis la page « Mot de passe oublie ».',
            ]);
        }

        $rawCompletion  = bin2hex(random_bytes(32));
        $completionHash = hash('sha256', $rawCompletion);
        $compExpires    = date('Y-m-d H:i:s', time() + 1800);

        $model->update((int) $row['id'], [
            'completion_token_hash' => $completionHash,
            'completion_expires_at' => $compExpires,
        ]);

        if ($model->errors() !== []) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Impossible de poursuivre. Reessayez.']);
        }

        return $this->response->setJSON([
            'completionToken' => $rawCompletion,
        ]);
    }

    /**
     * POST /api/auth/password-reset/complete
     * Corps : completionToken, password, passwordConfirm
     */
    public function passwordResetComplete(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $completion = isset($payload['completionToken']) ? trim((string) $payload['completionToken']) : '';
        $password   = isset($payload['password']) ? (string) $payload['password'] : '';
        $confirm    = isset($payload['passwordConfirm']) ? (string) $payload['passwordConfirm'] : '';

        if ($completion === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Jeton de confirmation manquant.']);
        }

        if (strlen($password) < 8) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['password' => 'Le mot de passe doit contenir au moins 8 caracteres.']]);
        }

        if ($password !== $confirm) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['passwordConfirm' => 'Les mots de passe ne correspondent pas.']]);
        }

        $hash  = hash('sha256', $completion);
        $model = model(PasswordResetModel::class);
        $row   = $model->where('completion_token_hash', $hash)->first();

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session de reinitialisation invalide ou expiree.']);
        }

        $compExp = strtotime((string) $row['completion_expires_at']);

        if ($compExp === false || $compExp < time()) {
            $model->delete((int) $row['id']);

            return $this->response->setStatusCode(410)->setJSON(['error' => 'Delai depasse. Recommencez la reinitialisation.']);
        }

        $userId = (int) $row['user_id'];
        $userModel = model(UserModel::class);

        if (! $userModel->update($userId, ['password' => $password])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Mise a jour impossible.']);
        }

        $model->delete((int) $row['id']);
        $this->db->table('password_resets')->where('user_id', $userId)->delete();

        return $this->response->setJSON(['ok' => true, 'message' => 'Mot de passe mis a jour. Vous pouvez vous connecter.']);
    }

    protected function normalizePersonName(string $name): string
    {
        $n = mb_strtolower(trim($name), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $n) ?? $n;
    }

    protected function mintJwt(int $userId, string $role): string
    {
        $cfg = config('Auth');

        return AuthToken::mint($userId, $role, $cfg->jwtSecret, $cfg->jwtTtlSeconds);
    }

    /**
     * Cookie HttpOnly (même origine / navigateurs permissifs) + Bearer pour SPA cross-origin.
     */
    protected function applyAuthCookie(ResponseInterface $response, string $jwt): ResponseInterface
    {
        $cfg    = config('Auth');
        $cookie = config('Cookie');

        return $response->setCookie(
            $cfg->cookieName,
            $jwt,
            time() + $cfg->jwtTtlSeconds,
            $cookie->domain,
            $cookie->path,
            $cookie->prefix,
            $cookie->secure,
            $cookie->httponly,
            $cookie->samesite === '' ? 'Lax' : $cookie->samesite,
        );
    }

    /**
     * @return 'sent'|'not_configured'|'send_failed'
     */
    protected function sendWelcomeEmail(string $toEmail, string $firstName, string $rawMagicToken): string
    {
        $cfg = config('Email');
        $from = $cfg->fromEmail;
        $name = $cfg->fromName;

        log_message(
            'info',
            'E-mail bienvenue: tentative envoi vers=' . $toEmail
            . ' protocol=' . $cfg->protocol
            . ' smtpHost=' . ($cfg->SMTPHost !== '' ? '(defini)' : '(vide)')
            . ' fromEmail=' . ($from !== '' ? '(defini)' : '(vide)'),
        );

        if ($from === '') {
            log_message('warning', 'E-mail bienvenue: abandon — fromEmail vide (config Email).');

            return 'not_configured';
        }

        $email = Services::email(null, false);

        $loginUrl = rtrim(config('Auth')->frontendBaseUrl, '/') . '/login';
        // Lien public = page /login du frontend uniquement (le token est consommé via redirection vers l’API).
        $quickLoginUrl = $loginUrl . '?token=' . rawurlencode($rawMagicToken);

        $body = <<<TXT
Bonjour {$firstName},

Votre compte Taskflow a bien ete cree.

Connexion rapide sans mot de passe (lien valable 48 h, usage unique) :
{$quickLoginUrl}

Sur cette meme page, vous pouvez aussi vous connecter avec votre e-mail et le mot de passe choisi.

Cordialement,
L equipe Taskflow
TXT;

        $email->setFrom($from, $name !== '' ? $name : 'Taskflow');
        $email->setTo($toEmail);
        $email->setSubject('Votre compte Taskflow — lien de connexion');
        $email->setMessage($body);

        if (! $email->send()) {
            log_message('error', 'E-mail bienvenue: echec SMTP / envoi — ' . $email->printDebugger(['headers']));

            return 'send_failed';
        }

        log_message('info', 'E-mail bienvenue: envoye OK vers=' . $toEmail);

        return 'sent';
    }

    /**
     * @return 'sent'|'not_configured'|'send_failed'
     */
    protected function sendPasswordResetRequestEmail(string $toEmail, string $firstName, string $rawRequestToken): string
    {
        $cfg  = config('Email');
        $from = $cfg->fromEmail;
        $name = $cfg->fromName;

        log_message(
            'info',
            'E-mail reinit. MDP: tentative envoi vers=' . $toEmail
            . ' protocol=' . $cfg->protocol
            . ' smtpHost=' . ($cfg->SMTPHost !== '' ? '(defini)' : '(vide)'),
        );

        if ($from === '') {
            log_message('warning', 'E-mail reinit. MDP: abandon — fromEmail vide.');

            return 'not_configured';
        }

        $emailService = Services::email(null, false);

        $base     = rtrim(config('Auth')->frontendBaseUrl, '/');
        $resetUrl = $base . '/reset-password?token=' . rawurlencode($rawRequestToken);

        $plain = <<<TXT
Bonjour {$firstName},

Une demande de reinitialisation de mot de passe a ete faite pour votre compte Taskflow.

Si vous etes bien a l'origine de cette demande, ouvrez le lien ci-dessous dans votre navigateur, puis cliquez sur « Oui, je confirme » pour choisir un nouveau mot de passe :

{$resetUrl}

Si vous n'etes pas a l'origine de cette demande, ignorez cet e-mail. Votre mot de passe actuel reste inchangé.

Lien valable 1 heure.

Cordialement,
L equipe Taskflow
TXT;

        $safeName = htmlspecialchars($firstName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeUrl  = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $html = <<<HTML
<p>Bonjour {$safeName},</p>
<p>Une demande de <strong>reinitialisation de mot de passe</strong> a ete faite pour votre compte Taskflow.</p>
<p>Si vous etes bien a l'origine de cette demande :</p>
<ol>
  <li>Ouvrez la page securisee via le bouton ci-dessous (ou le lien en texte).</li>
  <li>Cliquez sur <strong>Oui, je confirme</strong> pour acceder au formulaire de nouveau mot de passe.</li>
</ol>
<p style="margin:24px 0;">
  <a href="{$safeUrl}" style="display:inline-block;padding:12px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Oui, je confirme</a>
</p>
<p style="font-size:13px;color:#555;">Ou copiez ce lien :<br><a href="{$safeUrl}">{$safeUrl}</a></p>
<p>Si vous n'etes pas a l'origine de cette demande, ignorez cet e-mail. Votre mot de passe actuel reste inchangé.</p>
<p style="font-size:13px;color:#555;">Lien valable 1 heure.</p>
<p>Cordialement,<br>L equipe Taskflow</p>
HTML;

        $emailService->setMailType('html');
        $emailService->setFrom($from, $name !== '' ? $name : 'Taskflow');
        $emailService->setTo($toEmail);
        $emailService->setSubject('Taskflow — confirmation de reinitialisation du mot de passe');
        $emailService->setMessage($html);
        $emailService->setAltMessage($plain);

        if (! $emailService->send()) {
            log_message('error', 'E-mail reinit. MDP: echec — ' . $emailService->printDebugger(['headers']));

            return 'send_failed';
        }

        log_message('info', 'E-mail reinit. MDP: envoye OK vers=' . $toEmail);

        return 'sent';
    }
}
