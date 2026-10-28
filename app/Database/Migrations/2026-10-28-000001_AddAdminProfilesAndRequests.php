<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdminProfilesAndRequests extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('usuarios')) {
            if (! $this->db->fieldExists('nombre', 'usuarios')) {
                $this->forge->addColumn('usuarios', [
                    'nombre' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                ]);
            }

            if (! $this->db->fieldExists('cargo', 'usuarios')) {
                $this->forge->addColumn('usuarios', [
                    'cargo' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
                ]);
            }

            if (! $this->db->fieldExists('permisos_json', 'usuarios')) {
                $this->forge->addColumn('usuarios', [
                    'permisos_json' => ['type' => 'TEXT', 'null' => true],
                ]);
            }
        }

        if (! $this->db->tableExists('solicitudes_admin')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'entidad' => ['type' => 'VARCHAR', 'constraint' => 40],
                'entidad_id' => ['type' => 'INT', 'unsigned' => true],
                'accion' => ['type' => 'VARCHAR', 'constraint' => 30],
                'payload_json' => ['type' => 'TEXT', 'null' => true],
                'estado' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pendiente'],
                'solicitado_por' => ['type' => 'INT', 'unsigned' => true],
                'resuelto_por' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'resuelto_en' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['estado', 'entidad']);
            $this->forge->createTable('solicitudes_admin');
        }
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes_admin', true);

        if ($this->db->tableExists('usuarios')) {
            foreach (['permisos_json', 'cargo', 'nombre'] as $field) {
                if ($this->db->fieldExists($field, 'usuarios')) {
                    $this->forge->dropColumn('usuarios', $field);
                }
            }
        }
    }
}
