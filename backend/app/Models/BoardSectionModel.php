<?php

namespace App\Models;

use CodeIgniter\Model;

class BoardSectionModel extends Model
{
    protected $table            = 'board_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'sort_order',
    ];

    protected bool $allowEmptyInserts = false;
}
