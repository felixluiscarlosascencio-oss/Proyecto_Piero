<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCotizacionWorkflowTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre_colegio' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'cantidad_estudiantes' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'servicios_json' => [
                'type' => 'TEXT',
            ],
            'precio_unitario' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'total_seccion' => [
                'type'       => 'DECIMAL',
                'constraint' => '16,2',
                'default'    => 0,
            ],
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'solicitud',
            ],
            'dias_vigencia' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'vence_en' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'aprobado_por' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'pdf_generado_en' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('solicitudes_cotizacion');
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes_cotizacion');
    }
}
