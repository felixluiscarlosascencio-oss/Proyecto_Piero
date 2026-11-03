<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommerceRequestFields extends Migration
{
    public function up()
    {
        $this->addColumns('solicitudes_cotizacion', [
            'nombre_seccion'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'persona_encargada'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono_contacto'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        ]);

        $this->addColumns('contratos', [
            'nombre_colegio'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'nombre_seccion'       => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'persona_encargada'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono_contacto'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
        ]);
        $this->ensureUniqueQuoteContract();

        if (! $this->db->tableExists('productos_servicios')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'nombre_item'      => ['type' => 'VARCHAR', 'constraint' => 100],
                'tipo'             => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'Servicio Adicional'],
                'descripcion'      => ['type' => 'TEXT', 'null' => true],
                'detalle_largo'    => ['type' => 'TEXT', 'null' => true],
                'precio_unitario'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
                'estado'           => ['type' => 'BOOLEAN', 'default' => true],
                'galeria_json'     => ['type' => 'TEXT', 'null' => true],
                'creado_en'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('productos_servicios');
        } else {
            $this->addColumns('productos_servicios', [
                'detalle_largo' => ['type' => 'TEXT', 'null' => true],
                'galeria_json'  => ['type' => 'TEXT', 'null' => true],
            ]);
        }

        if ($this->db->tableExists('productos_servicios')
            && $this->db->table('productos_servicios')->countAllResults() === 0) {
            $now = date('Y-m-d H:i:s');
            $this->db->table('productos_servicios')->insertBatch([
                [
                    'nombre_item' => 'Paquete Base Anuario',
                    'tipo' => 'Paquete Base',
                    'descripcion' => 'Servicio base para preparar recuerdos de la promoción.',
                    'detalle_largo' => 'La cobertura, el contenido y los acabados del anuario se coordinan con la institución al preparar la cotización.',
                    'precio_unitario' => 0,
                    'estado' => 1,
                    'galeria_json' => '[]',
                    'creado_en' => $now,
                ],
                [
                    'nombre_item' => 'Sesión de Exteriores y Drone',
                    'tipo' => 'Servicio Adicional',
                    'descripcion' => 'Sesión fotográfica de la promoción en exteriores.',
                    'detalle_largo' => 'La locación, disponibilidad de tomas aéreas y condiciones de la sesión se coordinan con la institución.',
                    'precio_unitario' => 0,
                    'estado' => 1,
                    'galeria_json' => '[]',
                    'creado_en' => $now,
                ],
                [
                    'nombre_item' => 'Cuadro Retrato de Graduación',
                    'tipo' => 'Servicio Adicional',
                    'descripcion' => 'Retrato de graduación con acabado por definir.',
                    'detalle_largo' => 'La presentación, las medidas y el acabado se confirman con la institución al preparar la cotización.',
                    'precio_unitario' => 0,
                    'estado' => 1,
                    'galeria_json' => '[]',
                    'creado_en' => $now,
                ],
                [
                    'nombre_item' => 'Cuadro Giratorio de Graduación',
                    'tipo' => 'Servicio Adicional',
                    'descripcion' => 'Cuadro decorativo giratorio para recordar la graduación.',
                    'detalle_largo' => 'Producto decorativo de graduación con diseño giratorio. Los acabados y medidas se confirman al preparar la cotización.',
                    'precio_unitario' => 0,
                    'estado' => 1,
                    'galeria_json' => '[]',
                    'creado_en' => $now,
                ],
                [
                    'nombre_item' => 'Cuadro de Graduación',
                    'tipo' => 'Servicio Adicional',
                    'descripcion' => 'Cuadro conmemorativo de la promoción.',
                    'detalle_largo' => 'Cuadro conmemorativo personalizado para la promoción. Las medidas y acabados se confirman al preparar la cotización.',
                    'precio_unitario' => 0,
                    'estado' => 1,
                    'galeria_json' => '[]',
                    'creado_en' => $now,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->dropColumns('solicitudes_cotizacion', [
            'nombre_seccion',
            'persona_encargada',
            'telefono_contacto',
        ]);
        $this->dropColumns('contratos', [
            'nombre_colegio',
            'nombre_seccion',
            'persona_encargada',
            'telefono_contacto',
        ]);
        $this->dropUniqueQuoteContract();
        $this->dropColumns('productos_servicios', ['detalle_largo', 'galeria_json']);
    }

    /**
     * @param array<string, array<string, mixed>> $columns
     */
    private function addColumns(string $table, array $columns): void
    {
        if (! $this->db->tableExists($table)) {
            return;
        }

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
        if (! $this->db->tableExists($table)) {
            return;
        }

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }

    private function ensureUniqueQuoteContract(): void
    {
        if (! $this->db->tableExists('contratos') || ! $this->db->fieldExists('cotizacion_id', 'contratos')) {
            return;
        }

        foreach ($this->db->getIndexData('contratos') as $index) {
            if ($index->type === 'UNIQUE' && in_array('cotizacion_id', $index->fields, true)) {
                return;
            }
        }

        $table = $this->db->protectIdentifiers($this->db->prefixTable('contratos'), false);
        $this->db->query('CREATE UNIQUE INDEX contratos_cotizacion_id_unique ON ' . $table . ' (cotizacion_id)');
    }

    private function dropUniqueQuoteContract(): void
    {
        if (! $this->db->tableExists('contratos')) {
            return;
        }

        foreach ($this->db->getIndexData('contratos') as $index) {
            if ($index->name !== 'contratos_cotizacion_id_unique') {
                continue;
            }

            if ($this->db->getPlatform() === 'MySQLi') {
                $table = $this->db->protectIdentifiers($this->db->prefixTable('contratos'), false);
                $this->db->query('DROP INDEX contratos_cotizacion_id_unique ON ' . $table);
            } else {
                $indexName = $this->db->protectIdentifiers('contratos_cotizacion_id_unique', false);
                $this->db->query('DROP INDEX ' . $indexName);
            }
            break;
        }
    }
}
