<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsersAuthAndUserScope extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'user',
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'TIMESTAMP',
            ],
            'used_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('user_id');
        $this->forge->createTable('auth_magic_links', true);

        $this->forge->addColumn('board_sections', [
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        $this->forge->addColumn('todos', [
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        $adminEmail = env('auth.adminEmail', 'admin@localhost');
        $adminPass  = env('auth.adminPassword', 'AdminDev2026!');

        $this->db->table('users')->insert([
            'last_name'  => 'Administrateur',
            'first_name' => 'Super',
            'email'      => $adminEmail,
            'password'   => password_hash((string) $adminPass, PASSWORD_DEFAULT),
            'role'       => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $adminRow = $this->db->table('users')->select('id')->where('email', $adminEmail)->get()->getRowArray();
        $adminId  = $adminRow !== null ? (int) $adminRow['id'] : null;

        if ($adminId !== null) {
            $this->db->table('board_sections')->update(['user_id' => $adminId]);
            $this->db->table('todos')->update(['user_id' => $adminId]);
        }

        $this->db->query('ALTER TABLE board_sections ALTER COLUMN user_id SET NOT NULL');
        $this->db->query('ALTER TABLE todos ALTER COLUMN user_id SET NOT NULL');

        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'board_sections');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'todos');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'auth_magic_links');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('auth_magic_links', 'auth_magic_links_user_id_foreign');
        $this->forge->dropTable('auth_magic_links', true);

        $this->forge->dropForeignKey('todos', 'todos_user_id_foreign');
        $this->forge->dropForeignKey('board_sections', 'board_sections_user_id_foreign');

        $this->forge->dropColumn('todos', 'user_id');
        $this->forge->dropColumn('board_sections', 'user_id');

        $this->forge->dropTable('users', true);
    }
}
