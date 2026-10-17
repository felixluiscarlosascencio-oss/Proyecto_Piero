<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuariosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'personal_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'usuario' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'rol' => [
                'type'       => 'ENUM',
                'constraint' => ['Dueno', 'Secretaria', 'Encargado', 'Fotografo', 'Produccion'],
            ],
            'activo' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
            ],
            'ultimo_acceso' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'creado_en DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('personal_id', 'personal', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('usuarios');
    }
}