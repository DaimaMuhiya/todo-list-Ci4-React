<?php

namespace App\Models;

use CodeIgniter\Model;

class TodoModel extends Model
{
    protected $table            = 'todos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'description',
        'completed',
        'priority',
        'category',
        'user_id',
        'section_id',
        'due_date',
        'created_at',
    ];

    protected bool $allowEmptyInserts = false;

    /** @var array<string, string> Seuls les types reconnus par DataCaster (ex. boolean) */
    protected array $casts = [
        'completed' => 'boolean',
    ];

    // Dates
    protected $useTimestamps      = false;
    protected $dateFormat          = 'datetime';
    protected $createdField        = 'created_at';
    protected $updatedField        = '';

    protected $beforeInsert = ['setCreatedAt'];

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function setCreatedAt(array $data): array
    {
        $data['data']['created_at'] = date('Y-m-d H:i:s');

        return $data;
    }
}
