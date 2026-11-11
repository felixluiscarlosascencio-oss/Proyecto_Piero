<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class RefactorServiciosCatalog extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('productos_servicios') && ! $this->db->tableExists('servicios')) {
            $this->forge->renameTable('productos_servicios', 'servicios');
        } elseif ($this->db->tableExists('productos_servicios') && $this->db->tableExists('servicios')) {
            throw new RuntimeException('Both productos_servicios and servicios exist; refusing to merge service catalogs implicitly.');
        }

        if (! $this->db->tableExists('servicios')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'nombre_item'         => ['type' => 'VARCHAR', 'constraint' => 100],
                'descripcion'         => ['type' => 'TEXT', 'null' => true],
                'detalle_largo'       => ['type' => 'TEXT', 'null' => true],
                'galeria_json'        => ['type' => 'TEXT', 'null' => true],
                'etiqueta_destacada'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'precio_interno'      => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('servicios');

            return;
        }

        $this->addColumns('servicios', [
            'detalle_largo'      => ['type' => 'TEXT', 'null' => true],
            'galeria_json'       => ['type' => 'TEXT', 'null' => true],
            'etiqueta_destacada' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'precio_interno'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
        ]);

        if ($this->db->fieldExists('precio_unitario', 'servicios')) {
            $this->db->query('UPDATE ' . $this->db->protectIdentifiers('servicios')
                . ' SET ' . $this->db->protectIdentifiers('precio_interno')
                . ' = ' . $this->db->protectIdentifiers('precio_unitario'));
        }

        $this->dropColumns('servicios', ['tipo', 'precio_unitario', 'estado', 'creado_en']);
    }

    public function down()
    {
        if (! $this->db->tableExists('servicios')) {
            return;
        }

        $this->addColumns('servicios', [
            'tipo'            => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Servicio Adicional'],
            'precio_unitario' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'estado'          => ['type' => 'BOOLEAN', 'default' => true],
            'creado_en'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->db->query('UPDATE ' . $this->db->protectIdentifiers('servicios')
            . ' SET ' . $this->db->protectIdentifiers('precio_unitario')
            . ' = ' . $this->db->protectIdentifiers('precio_interno'));
        $this->dropColumns('servicios', ['etiqueta_destacada', 'precio_interno']);
        $this->forge->renameTable('servicios', 'productos_servicios');
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     */
    private function addColumns(string $table, array $columns): void
    {
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, $table)) {
                $this->forge->addColumn($table, [$name => $definition]);
            }
        }
    }

    /**
     * @param list<string> $columns
     */
    private function dropColumns(string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}
