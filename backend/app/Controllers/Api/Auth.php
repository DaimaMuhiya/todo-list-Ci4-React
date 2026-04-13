<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthToken;
use App\Libraries\CurrentUser;
use App\Models\AuthMagicLinkModel;
use App\Models\BoardSectionModel;
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

        $this->sendWelcomeEmail($email, $firstName, $rawToken);

        $user = $userModel->find($userId);

        return $this->response->setStatusCode(201)->setJSON([
            'user'    => $userModel->serializePublic($user),
            'message' => 'Compte cree. Verifiez votre boite e-mail pour le lien de connexion.',
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

        $response = $this->response->setJSON([
            'user' => $userModel->serializePublic($user),
        ]);

        return $this->attachAuthCookie($response, (int) $user['id'], (string) $user['role']);
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

        $okUrl = $frontend . '/?connexion=1';
        $response = $this->response->redirect($okUrl);

        return $this->attachAuthCookie($response, $userId, (string) $user['role']);
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

    protected function attachAuthCookie(ResponseInterface $response, int $userId, string $role): ResponseInterface
    {
        $cfg = config('Auth');
        $jwt = AuthToken::mint($userId, $role, $cfg->jwtSecret, $cfg->jwtTtlSeconds);

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

    protected function sendWelcomeEmail(string $toEmail, string $firstName, string $rawMagicToken): void
    {
        $email = Services::email();
        $from  = config('Email')->fromEmail;
        $name  = config('Email')->fromName;

        if ($from === '') {
            log_message('notice', 'E-mail non envoye : fromEmail vide (config Email).');

            return;
        }

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
            log_message('error', 'E-mail inscription : ' . $email->printDebugger(['headers']));
        }
    }
}
