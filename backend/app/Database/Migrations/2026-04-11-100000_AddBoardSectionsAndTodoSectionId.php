<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBoardSectionsAndTodoSectionId extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'sort_order' => [
                'type'    => 'INTEGER',
                'default' => 0,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('board_sections', true);

        $this->db->table('board_sections')->insertBatch([
            ['name' => 'À faire', 'slug' => 'todo', 'sort_order' => 0],
            ['name' => 'En cours', 'slug' => 'in_progress', 'sort_order' => 1],
            ['name' => 'Terminé', 'slug' => 'done', 'sort_order' => 2],
        ]);

        $this->forge->addColumn('todos', [
            'section_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);

        $todoRow = $this->db->table('board_sections')->select('id')->where('slug', 'todo')->get()->getRowArray();
        $doneRow = $this->db->table('board_sections')->select('id')->where('slug', 'done')->get()->getRowArray();

        $todoId = $todoRow !== null ? (int) $todoRow['id'] : null;
        $doneId = $doneRow !== null ? (int) $doneRow['id'] : null;

        if ($doneId !== null) {
            $this->db->query('UPDATE todos SET section_id = ? WHERE completed = true', [$doneId]);
        }

        if ($todoId !== null) {
            $this->db->query('UPDATE todos SET section_id = ? WHERE section_id IS NULL', [$todoId]);
        }

        $this->forge->addForeignKey('section_id', 'board_sections', 'id', 'SET NULL', 'CASCADE');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('todos', 'todos_section_id_foreign');
        $this->forge->dropColumn('todos', 'section_id');
        $this->forge->dropTable('board_sections', true);
    }
}
