<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureContractManagementTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('contratos')) {
            $this->forge->addField([
                'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'numero_contrato'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'cotizacion_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'institucion_id'       => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'cliente_id'           => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'encargado_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'nivel'                => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'aula'                 => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'cant_alumnos_inicial' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'monto_total'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'monto_pagado'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'monto_saldo'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'fecha_contrato'       => ['type' => 'DATE'],
                'estado_contrato'      => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Activo'],
                'observaciones'        => ['type' => 'TEXT', 'null' => true],
                'creado_en'            => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('contratos');
        }

        if (! $this->db->tableExists('alumnos')) {
            $this->forge->addField([
                'id'                      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'contrato_id'             => ['type' => 'INT', 'unsigned' => true],
                'codigo_alumno'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'nombre_completo'         => ['type' => 'VARCHAR', 'constraint' => 150],
                'genero'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'numero_lista'            => ['type' => 'INT', 'null' => true],
                'tipo_registro'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Regular'],
                'fecha_incorporacion'     => ['type' => 'DATE', 'null' => true],
                'sesion_incorporacion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'precio_aplicado'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
                'incluir_en_cuadro'       => ['type' => 'BOOLEAN', 'default' => true],
                'estado'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Activo'],
                'observaciones'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('contrato_id');
            $this->forge->createTable('alumnos');
        }
    }

    public function down()
    {
        // Contract records may predate this migration; preserve them on rollback.
    }
}
