<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthMagicLinkModel extends Model
{
    protected $table            = 'auth_magic_links';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected bool $allowEmptyInserts = false;
}
