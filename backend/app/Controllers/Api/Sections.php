<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BoardSectionModel;
use CodeIgniter\HTTP\ResponseInterface;

class Sections extends BaseController
{
    /**
     * GET /api/sections
     */
    public function index(): ResponseInterface
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = model(BoardSectionModel::class)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON(array_map([$this, 'serialize'], $rows));
    }

    /**
     * POST /api/sections
     */
    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';

        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['name' => 'Le nom de la section est requis.']]);
        }

        if (mb_strlen($name) > 191) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => ['name' => 'Le nom est trop long.']]);
        }

        $db = $this->db;
        $maxRow = $db->query('SELECT MAX(sort_order) AS m FROM board_sections')->getRowArray();
        $sortOrder = isset($maxRow['m']) && $maxRow['m'] !== null ? (int) $maxRow['m'] + 1 : 0;

        $model = model(BoardSectionModel::class);
        $insert = [
            'name' => $name,
            'slug'        => null,
            'sort_order'  => $sortOrder,
        ];

        if ($model->insert($insert) === false) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $model->errors()]);
        }

        $id = (int) $model->getInsertID();
        $created = $model->find($id);

        return $this->response->setStatusCode(201)->setJSON($this->serialize($created));
    }

    /**
     * DELETE /api/sections/{id}
     */
    public function delete(string $id): ResponseInterface
    {
        $model = model(BoardSectionModel::class);
        $row = $model->find((int) $id);

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Section introuvable.']);
        }

        if (($row['slug'] ?? null) !== null && $row['slug'] !== '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Les sections par défaut ne peuvent pas être supprimées.']);
        }

        $todoSection = $model->where('slug', 'todo')->first();

        if ($todoSection === null) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Configuration des sections invalide.']);
        }

        $todoSectionId = (int) $todoSection['id'];
        $sectionId = (int) $id;

        $this->db->table('todos')->where('section_id', $sectionId)->update([
            'section_id' => $todoSectionId,
            'completed'  => false,
        ]);
        $model->delete($sectionId);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected function serialize(array $row): array
    {
        $slug = $row['slug'] ?? null;

        return [
            'id'         => (string) $row['id'],
            'name'       => $row['name'],
            'slug'       => ($slug !== null && $slug !== '') ? (string) $slug : null,
            'sortOrder'  => (int) ($row['sort_order'] ?? 0),
        ];
    }
}
