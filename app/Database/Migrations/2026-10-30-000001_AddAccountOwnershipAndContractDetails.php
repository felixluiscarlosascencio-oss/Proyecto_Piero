<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccountOwnershipAndContractDetails extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('usuarios') && ! $this->db->fieldExists('creado_por', 'usuarios')) {
            $this->forge->addColumn('usuarios', [
                'creado_por' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
        }

        if ($this->db->tableExists('contratos')) {
            foreach ([
                'servicios_json' => ['type' => 'TEXT', 'null' => true],
                'agenda_json'    => ['type' => 'TEXT', 'null' => true],
                'cuadros_json'   => ['type' => 'TEXT', 'null' => true],
            ] as $field => $definition) {
                if (! $this->db->fieldExists($field, 'contratos')) {
                    $this->forge->addColumn('contratos', [$field => $definition]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('contratos')) {
            foreach (['servicios_json', 'agenda_json', 'cuadros_json'] as $field) {
                if ($this->db->fieldExists($field, 'contratos')) {
                    $this->forge->dropColumn('contratos', $field);
                }
            }
        }

        if ($this->db->tableExists('usuarios') && $this->db->fieldExists('creado_por', 'usuarios')) {
            $this->forge->dropColumn('usuarios', 'creado_por');
        }
    }
}