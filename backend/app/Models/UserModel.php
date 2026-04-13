<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'last_name',
        'first_name',
        'email',
        'password',
        'role',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function hashPassword(array $data): array
    {
        if (! isset($data['data']['password'])) {
            return $data;
        }

        $plain = $data['data']['password'];

        if ($plain === '' || (is_string($plain) && str_starts_with($plain, '$2y$'))) {
            return $data;
        }

        $data['data']['password'] = password_hash((string) $plain, PASSWORD_DEFAULT);

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public function serializePublic(array $row): array
    {
        return [
            'id'         => (string) $row['id'],
            'lastName'   => $row['last_name'],
            'firstName'  => $row['first_name'],
            'email'      => $row['email'],
            'role'       => $row['role'],
            'createdAt'  => $this->formatCreatedAt($row['created_at'] ?? null),
        ];
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
