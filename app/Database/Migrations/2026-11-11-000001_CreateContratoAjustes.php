<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContratoAjustes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('contrato_ajustes')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'contrato_id' => ['type' => 'INT', 'unsigned' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 30],
            'motivo' => ['type' => 'VARCHAR', 'constraint' => 500],
            'detalle_json' => ['type' => 'TEXT'],
            'impacto_financiero' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'creado_por' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('contrato_id');
        $this->forge->createTable('contrato_ajustes');
    }

    public function down()
    {
        // Contract addenda are an audit trail and are deliberately preserved.
    }
}
