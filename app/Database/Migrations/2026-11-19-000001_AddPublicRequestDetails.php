<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublicRequestDetails extends Migration
{
    private const COLUMNS = [
        'distrito'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        'provincia'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        'nivel'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        'tipo_servicio'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
        'observaciones'   => ['type' => 'TEXT', 'null' => true],
    ];

    public function up()
    {
        if (! $this->db->tableExists('solicitudes_cotizacion')) {
            return;
        }

        foreach (self::COLUMNS as $name => $definition) {
            if (! $this->db->fieldExists($name, 'solicitudes_cotizacion')) {
                $this->forge->addColumn('solicitudes_cotizacion', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('solicitudes_cotizacion')) {
            return;
        }

        foreach (array_keys(self::COLUMNS) as $name) {
            if ($this->db->fieldExists($name, 'solicitudes_cotizacion')) {
                $this->forge->dropColumn('solicitudes_cotizacion', $name);
            }
        }
    }
}
