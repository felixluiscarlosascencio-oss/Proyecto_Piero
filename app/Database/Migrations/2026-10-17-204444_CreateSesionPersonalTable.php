<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSesionPersonalTable extends Migration
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
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'celular' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'rol' => [
                'type'       => 'ENUM',
                'constraint' => ['Dueno', 'Secretaria', 'Encargado', 'Fotografo', 'Camarografo', 'Produccion'],
            ],
            'activo' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
            ],
            'observaciones' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'creado_en DATETIME DEFAULT CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('personal');
    }

    public function down()
    {
        $this->forge->dropTable('personal');
    }
}