<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuoteVersioning extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('solicitudes_cotizacion')
            && ! $this->db->fieldExists('cotizacion_origen_id', 'solicitudes_cotizacion')) {
            $this->forge->addColumn('solicitudes_cotizacion', [
                'cotizacion_origen_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('solicitudes_cotizacion')
            && $this->db->fieldExists('cotizacion_origen_id', 'solicitudes_cotizacion')) {
            $this->forge->dropColumn('solicitudes_cotizacion', 'cotizacion_origen_id');
        }
    }
}
