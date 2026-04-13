<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\CurrentUser;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AdminUsers extends BaseController
{
    /**
     * GET /api/admin/users
     */
    public function index(): ResponseInterface
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = model(UserModel::class)->orderBy('id', 'ASC')->findAll();
        $out  = [];

        foreach ($rows as $row) {
            $out[] = model(UserModel::class)->serializePublic($row);
        }

        return $this->response->setJSON($out);
    }

    /**
     * PATCH /api/admin/users/{id}
     */
    public function update(string $id): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        if (! isset($payload['role']) || ! in_array($payload['role'], ['user', 'admin'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['role' => 'Role invalide (user ou admin).']]);
        }

        $model = model(UserModel::class);
        $row   = $model->find((int) $id);

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Utilisateur introuvable.']);
        }

        if ((int) $id === CurrentUser::id() && $payload['role'] !== 'admin') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Vous ne pouvez pas retirer votre propre role administrateur.']);
        }

        $model->update((int) $id, ['role' => $payload['role']]);

        if ($model->errors() !== []) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $model->errors()]);
        }

        $updated = $model->find((int) $id);

        return $this->response->setJSON($model->serializePublic($updated));
    }

    /**
     * DELETE /api/admin/users/{id}
     */
    public function delete(string $id): ResponseInterface
    {
        if ((int) $id === CurrentUser::id()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $model = model(UserModel::class);

        if ($model->find((int) $id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Utilisateur introuvable.']);
        }

        $this->db->table('todos')->where('user_id', (int) $id)->delete();
        $this->db->table('board_sections')->where('user_id', (int) $id)->delete();
        $model->delete((int) $id);

        return $this->response->setJSON(['ok' => true]);
    }
}
