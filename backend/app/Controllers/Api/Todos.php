<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TodoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Todos extends BaseController
{
    /**
     * GET /api/todos
     */
    public function index(): ResponseInterface
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = model(TodoModel::class)->orderBy('created_at', 'DESC')->findAll();

        return $this->response->setJSON(array_map([$this, 'serialize'], $rows));
    }

    /**
     * GET /api/todos/{id}
     */
    public function show(string $id): ResponseInterface
    {
        $row = model(TodoModel::class)->find((int) $id);

        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tache introuvable.']);
        }

        return $this->response->setJSON($this->serialize($row));
    }

    /**
     * POST /api/todos
     */
    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $row = $this->payloadToRow($payload, false);

        if (! $this->validatePayload($row, true)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        $model = model(TodoModel::class);

        if ($model->insert($row) === false) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $model->errors()]);
        }

        $id = (int) $model->getInsertID();
        $created = $model->find($id);

        return $this->response->setStatusCode(201)->setJSON($this->serialize($created));
    }

    /**
     * PUT /api/todos/{id}
     */
    public function update(string $id): ResponseInterface
    {
        $model = model(TodoModel::class);
        $existing = $model->find((int) $id);

        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tache introuvable.']);
        }

        $payload = $this->request->getJSON(true);

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Corps JSON invalide.']);
        }

        $row = $this->payloadToRow($payload, true);
        $merged = array_merge($existing, $row);

        if (! $this->validatePayload($merged, true)) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $this->validator->getErrors()]);
        }

        if ($model->update((int) $id, $row) === false) {
            return $this->response->setStatusCode(422)->setJSON(['errors' => $model->errors()]);
        }

        $updated = $model->find((int) $id);

        return $this->response->setJSON($this->serialize($updated));
    }

    /**
     * DELETE /api/todos/{id}
     */
    public function delete(string $id): ResponseInterface
    {
        $model = model(TodoModel::class);

        if ($model->find((int) $id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tache introuvable.']);
        }

        $model->delete((int) $id);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    protected function serialize(array $row): array
    {
        $description = $row['description'] ?? null;

        return [
            'id'          => (string) $row['id'],
            'title'       => $row['title'],
            'description' => ($description !== null && $description !== '') ? (string) $description : null,
            'completed'   => $this->toBool($row['completed'] ?? false),
            'priority'    => $row['priority'],
            'category'    => $row['category'],
            'dueDate'     => isset($row['due_date']) && $row['due_date'] !== null && $row['due_date'] !== ''
                ? $this->formatDate($row['due_date'])
                : null,
            'createdAt'   => $this->formatCreatedAt($row['created_at'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    protected function payloadToRow(array $payload, bool $partial): array
    {
        $row = [];

        if (! $partial || array_key_exists('title', $payload)) {
            $row['title'] = isset($payload['title']) ? (string) $payload['title'] : '';
        }

        if (! $partial || array_key_exists('description', $payload)) {
            $desc = $payload['description'] ?? null;
            $row['description'] = ($desc === null || $desc === '') ? null : (string) $desc;
        }

        if (! $partial || array_key_exists('completed', $payload)) {
            $row['completed'] = $this->toBool($payload['completed'] ?? false);
        }

        if (! $partial || array_key_exists('priority', $payload)) {
            $row['priority'] = $payload['priority'] ?? 'medium';
        }

        if (! $partial || array_key_exists('category', $payload)) {
            $row['category'] = $payload['category'] ?? 'work';
        }

        if (! $partial || array_key_exists('dueDate', $payload)) {
            $due = $payload['dueDate'] ?? null;
            $row['due_date'] = ($due === null || $due === '') ? null : (string) $due;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function validatePayload(array $data, bool $titleRequired): bool
    {
        $rules = [
            'title' => $titleRequired ? 'required|max_length[255]' : 'permit_empty|max_length[255]',
            'priority' => 'if_exist|in_list[low,medium,high]',
            'category' => 'if_exist|in_list[work,personal,urgent,other]',
            'due_date' => 'permit_empty|valid_date',
        ];

        return $this->validateData($data, $rules);
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 't' || $value === 'f') {
            return $value === 't';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function formatDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $s = (string) $value;

        return substr($s, 0, 10);
    }

    protected function formatCreatedAt(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        if ($value === null || $value === '') {
            return (new \DateTimeImmutable('now'))->format('c');
        }

        $ts = strtotime((string) $value);

        return $ts ? date('c', $ts) : (string) $value;
    }
}
