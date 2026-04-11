<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BoardSectionModel;
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
        $row = $this->finalizeTodoRow($payload, $row, null, false);

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
        $row = $this->finalizeTodoRow($payload, $row, $existing, true);
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

        $sectionId = $row['section_id'] ?? null;

        return [
            'id'          => (string) $row['id'],
            'title'       => $row['title'],
            'description' => ($description !== null && $description !== '') ? (string) $description : null,
            'completed'   => $this->toBool($row['completed'] ?? false),
            'priority'    => $row['priority'],
            'category'    => $row['category'],
            'sectionId'   => $sectionId !== null && $sectionId !== '' ? (string) $sectionId : (string) $this->getSectionIdBySlug('todo'),
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

        if (! $partial || array_key_exists('sectionId', $payload)) {
            if (array_key_exists('sectionId', $payload) && $payload['sectionId'] !== null && $payload['sectionId'] !== '') {
                $row['section_id'] = (int) $payload['sectionId'];
            } elseif (! $partial) {
                $row['section_id'] = null;
            }
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
            'section_id' => 'permit_empty|is_natural_no_zero',
        ];

        if (! $this->validateData($data, $rules)) {
            return false;
        }

        if (isset($data['section_id']) && $data['section_id'] !== null && $data['section_id'] !== '') {
            $sid = (int) $data['section_id'];

            if (model(BoardSectionModel::class)->find($sid) === null) {
                $this->validator->setError('section_id', 'Section invalide.');

                return false;
            }
        }

        return true;
    }

    protected function getSectionIdBySlug(string $slug): int
    {
        $row = model(BoardSectionModel::class)->select('id')->where('slug', $slug)->first();

        return $row !== null ? (int) $row['id'] : 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function syncCompletedWithSection(array &$row): void
    {
        $id = $row['section_id'] ?? null;

        if ($id === null || $id === '') {
            return;
        }

        $section = model(BoardSectionModel::class)->select('slug')->find((int) $id);
        $slug = $section['slug'] ?? null;
        $row['completed'] = ($slug === 'done');
    }

    /**
     * @param array<string, mixed>      $payload
     * @param array<string, mixed>      $row
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, mixed>
     */
    protected function finalizeTodoRow(array $payload, array $row, ?array $existing, bool $partial): array
    {
        if (! $partial) {
            if (! isset($row['section_id']) || $row['section_id'] === null) {
                if ($this->toBool($payload['completed'] ?? false)) {
                    $row['section_id'] = $this->getSectionIdBySlug('done');
                } else {
                    $row['section_id'] = $this->getSectionIdBySlug('todo');
                }
            }

            $this->syncCompletedWithSection($row);

            return $row;
        }

        $merged = $existing !== null ? array_merge($existing, $row) : $row;

        if (array_key_exists('section_id', $row)) {
            $this->syncCompletedWithSection($merged);
        } elseif (array_key_exists('completed', $row)) {
            if ($this->toBool($merged['completed'] ?? false)) {
                $merged['section_id'] = $this->getSectionIdBySlug('done');
            } else {
                $merged['section_id'] = $this->getSectionIdBySlug('todo');
            }

            $merged['completed'] = $this->toBool($merged['completed'] ?? false);
        }

        $out = $row;

        if (array_key_exists('section_id', $row) || array_key_exists('completed', $row)) {
            $out['section_id'] = $merged['section_id'];
            $out['completed'] = $this->toBool($merged['completed'] ?? false);
        }

        return $out;
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
