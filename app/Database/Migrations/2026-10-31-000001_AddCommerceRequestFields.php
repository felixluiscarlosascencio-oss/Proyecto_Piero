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

        if ($this->db->tableExists('productos_servicios') && ! $this->db->tableExists('servicios')) {
            $this->forge->renameTable('productos_servicios', 'servicios');
        } elseif ($this->db->tableExists('productos_servicios') && $this->db->tableExists('servicios')) {
            throw new \RuntimeException('Both service catalog tables exist before AddCommerceRequestFields.');
        }

        if (! $this->db->tableExists('servicios')) {
            $this->forge->addField([
                'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'nombre_item'        => ['type' => 'VARCHAR', 'constraint' => 100],
                'descripcion'        => ['type' => 'TEXT', 'null' => true],
                'detalle_largo'      => ['type' => 'TEXT', 'null' => true],
                'galeria_json'       => ['type' => 'TEXT', 'null' => true],
                'etiqueta_destacada' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'precio_interno'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('servicios');
        } else {
            $this->addColumns('servicios', [
                'detalle_largo'      => ['type' => 'TEXT', 'null' => true],
                'galeria_json'       => ['type' => 'TEXT', 'null' => true],
                'etiqueta_destacada' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'precio_interno'     => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            ]);
        }

        if ($this->db->table('servicios')->countAllResults() === 0) {
            $this->db->table('servicios')->insertBatch([
                [
                    'nombre_item' => 'Paquete Base Anuario',
                    'descripcion' => 'Servicio base para preparar recuerdos de la promoción.',
                    'detalle_largo' => 'La cobertura, el contenido y los acabados del anuario se coordinan con la institución al preparar la cotización.',
                    'galeria_json' => '[]',
                    'etiqueta_destacada' => null,
                    'precio_interno' => 0,
                ],
                [
                    'nombre_item' => 'Sesión de Exteriores y Drone',
                    'descripcion' => 'Sesión fotográfica de la promoción en exteriores.',
                    'detalle_largo' => 'La locación, disponibilidad de tomas aéreas y condiciones de la sesión se coordinan con la institución.',
                    'galeria_json' => '[]',
                    'etiqueta_destacada' => null,
                    'precio_interno' => 0,
                ],
                [
                    'nombre_item' => 'Cuadro Retrato de Graduación',
                    'descripcion' => 'Retrato de graduación con acabado por definir.',
                    'detalle_largo' => 'La presentación, las medidas y el acabado se confirman con la institución al preparar la cotización.',
                    'galeria_json' => '[]',
                    'etiqueta_destacada' => null,
                    'precio_interno' => 0,
                ],
                [
                    'nombre_item' => 'Cuadro Giratorio de Graduación',
                    'descripcion' => 'Cuadro decorativo giratorio para recordar la graduación.',
                    'detalle_largo' => 'Producto decorativo de graduación con diseño giratorio. Los acabados y medidas se confirman al preparar la cotización.',
                    'galeria_json' => '[]',
                    'etiqueta_destacada' => null,
                    'precio_interno' => 0,
                ],
                [
                    'nombre_item' => 'Cuadro de Graduación',
                    'descripcion' => 'Cuadro conmemorativo de la promoción.',
                    'detalle_largo' => 'Cuadro conmemorativo personalizado para la promoción. Las medidas y acabados se confirman al preparar la cotización.',
                    'galeria_json' => '[]',
                    'etiqueta_destacada' => null,
                    'precio_interno' => 0,
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
        $catalogo = $this->db->tableExists('servicios') ? 'servicios' : 'productos_servicios';
        $this->dropColumns($catalogo, ['detalle_largo', 'galeria_json']);
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
