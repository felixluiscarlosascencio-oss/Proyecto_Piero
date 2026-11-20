<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Profesionaliza el módulo comercial:
 * - códigos documentales persistentes (SOL/COT/CON/ADE)
 * - numeración de versiones persistente
 * - separación entre revisión administrativa y estado comercial
 * - fecha de primera sesión, respuesta del cliente y auditoría
 *
 * La migración es idempotente y conserva todos los registros existentes.
 */
class ProfessionalizeCommercialModule extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('solicitudes_cotizacion')) {
            $columnas = [
                'codigo_solicitud'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'codigo_cotizacion' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'version'           => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'estado_revision'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'SIN_REVISAR'],
                'primera_sesion'    => ['type' => 'DATETIME', 'null' => true],
                'respondido_en'     => ['type' => 'DATETIME', 'null' => true],
                'respondido_por'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'motivo_decision'   => ['type' => 'TEXT', 'null' => true],
            ];
            foreach ($columnas as $nombre => $definicion) {
                if (! $this->db->fieldExists($nombre, 'solicitudes_cotizacion')) {
                    $this->forge->addColumn('solicitudes_cotizacion', [$nombre => $definicion]);
                }
            }
        }

        if ($this->db->tableExists('contratos') && ! $this->db->fieldExists('codigo', 'contratos')) {
            $this->forge->addColumn('contratos', [
                'codigo' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            ]);
        }

        if ($this->db->tableExists('contrato_ajustes') && ! $this->db->fieldExists('codigo', 'contrato_ajustes')) {
            $this->forge->addColumn('contrato_ajustes', [
                'codigo' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            ]);
        }

        if (! $this->db->tableExists('documentos_secuencia')) {
            $this->forge->addField([
                'id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tipo'   => ['type' => 'VARCHAR', 'constraint' => 10],
                'anio'   => ['type' => 'INT', 'unsigned' => true],
                'ultimo' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tipo', 'anio'], false, true);
            $this->forge->createTable('documentos_secuencia');
        }

        if (! $this->db->tableExists('eventos_comerciales')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'cotizacion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'contrato_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'tipo'          => ['type' => 'VARCHAR', 'constraint' => 50],
                'detalle'       => ['type' => 'TEXT', 'null' => true],
                'usuario_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'creado_en'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('cotizacion_id');
            $this->forge->addKey('contrato_id');
            $this->forge->createTable('eventos_comerciales');
        }

        $this->backfillQuotes();
        $this->backfillContracts();
        $this->backfillAddenda();
        $this->seedSequences();

        $this->ensureIndex('solicitudes_cotizacion', 'solicitudes_codigo_solicitud_unique', ['codigo_solicitud'], true);
        $this->ensureIndex('solicitudes_cotizacion', 'solicitudes_codigo_cotizacion_version_unique', ['codigo_cotizacion', 'version'], true);
        $this->ensureIndex('solicitudes_cotizacion', 'solicitudes_created_at_index', ['created_at']);
        $this->ensureIndex('solicitudes_cotizacion', 'solicitudes_estado_revision_index', ['estado_revision']);
        $this->ensureIndex('contratos', 'contratos_codigo_unique', ['codigo'], true);
        $this->ensureIndex('contrato_ajustes', 'contrato_ajustes_codigo_unique', ['codigo'], true);
    }

    public function down()
    {
        // Se conservan los datos; solo se retiran los índices nuevos.
        foreach ([
            ['solicitudes_cotizacion', 'solicitudes_codigo_solicitud_unique'],
            ['solicitudes_cotizacion', 'solicitudes_codigo_cotizacion_version_unique'],
            ['solicitudes_cotizacion', 'solicitudes_created_at_index'],
            ['solicitudes_cotizacion', 'solicitudes_estado_revision_index'],
            ['contratos', 'contratos_codigo_unique'],
            ['contrato_ajustes', 'contrato_ajustes_codigo_unique'],
        ] as [$tabla, $indice]) {
            $this->dropIndex($tabla, $indice);
        }
    }

    private function backfillQuotes(): void
    {
        if (! $this->db->tableExists('solicitudes_cotizacion')) {
            return;
        }

        $filas = $this->db->table('solicitudes_cotizacion')
            ->select('id, cotizacion_origen_id, estado, created_at, nombre_seccion, distrito, provincia, nivel, tipo_servicio')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $grupos = [];
        foreach ($filas as $fila) {
            $raiz = (int) ($fila['cotizacion_origen_id'] ?? 0);
            $raiz = $raiz > 0 ? $raiz : (int) $fila['id'];
            $grupos[$raiz][] = $fila;
        }

        $mapaEstados = [
            'solicitud'      => 'borrador',
            'aprobada_admin' => 'borrador',
            'pendiente'      => 'enviada',
            'aprobado'       => 'convertida',
            'reemplazada'    => 'reemplazada',
            'borrador'       => 'borrador',
            'enviada'        => 'enviada',
            'aceptada'       => 'aceptada',
            'rechazada'      => 'rechazada',
            'vencida'        => 'vencida',
            'convertida'     => 'convertida',
        ];

        foreach ($grupos as $raiz => $versiones) {
            usort($versiones, static fn (array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);
            $primera = $versiones[0];
            $anio = $this->anioDe($primera['created_at'] ?? null);
            $esFormal = count($versiones) > 1;
            foreach ($versiones as $version) {
                if (! in_array($version['estado'], ['solicitud', 'reemplazada'], true)) {
                    $esFormal = true;
                }
            }

            $codigoCotizacion = $esFormal
                ? 'COT-' . $anio . '-' . str_pad((string) $raiz, 6, '0', STR_PAD_LEFT)
                : null;

            foreach ($versiones as $indice => $version) {
                $numeroVersion = $indice + 1;
                $estadoOriginal = (string) $version['estado'];
                $estado = $mapaEstados[$estadoOriginal] ?? 'borrador';
                $esSolicitudPublica = $estadoOriginal === 'solicitud'
                    || ($indice === 0 && $this->pareceSolicitudPublica($version));
                $codigoSolicitud = $esSolicitudPublica
                    ? 'SOL-' . $anio . '-' . str_pad((string) $version['id'], 6, '0', STR_PAD_LEFT)
                    : null;

                $datos = [
                    'codigo_solicitud'  => $codigoSolicitud,
                    'codigo_cotizacion' => $codigoCotizacion,
                    'version'           => $numeroVersion,
                    'estado'            => $estado,
                    'estado_revision'   => $estadoOriginal === 'solicitud' ? 'SIN_REVISAR' : 'REVISADA',
                ];
                $this->db->table('solicitudes_cotizacion')->where('id', $version['id'])->update($datos);
            }
        }
    }

    private function backfillContracts(): void
    {
        if (! $this->db->tableExists('contratos')) {
            return;
        }

        $filas = $this->db->table('contratos')->select('id, numero_contrato, creado_en')->get()->getResultArray();
        foreach ($filas as $fila) {
            if (! $this->db->fieldExists('codigo', 'contratos')) {
                break;
            }
            $anio = $this->anioDe($fila['creado_en'] ?? null);
            $sufijo = $fila['id'];
            if (preg_match('/-(\d+)$/', (string) ($fila['numero_contrato'] ?? ''), $coincidencia) === 1) {
                $sufijo = (int) $coincidencia[1];
            }
            $codigo = 'CON-' . $anio . '-' . str_pad((string) $sufijo, 6, '0', STR_PAD_LEFT);
            $this->db->table('contratos')->where('id', $fila['id'])->update(['codigo' => $codigo]);
        }
    }

    private function backfillAddenda(): void
    {
        if (! $this->db->tableExists('contrato_ajustes') || ! $this->db->fieldExists('codigo', 'contrato_ajustes')) {
            return;
        }

        $filas = $this->db->table('contrato_ajustes')->select('id, creado_en')->get()->getResultArray();
        foreach ($filas as $fila) {
            $anio = $this->anioDe($fila['creado_en'] ?? null);
            $codigo = 'ADE-' . $anio . '-' . str_pad((string) $fila['id'], 6, '0', STR_PAD_LEFT);
            $this->db->table('contrato_ajustes')->where('id', $fila['id'])->update(['codigo' => $codigo]);
        }
    }

    private function seedSequences(): void
    {
        $anioActual = (int) date('Y');
        $definiciones = [
            'SOL' => $this->maxSuffix('solicitudes_cotizacion', 'codigo_solicitud'),
            'COT' => $this->maxSuffix('solicitudes_cotizacion', 'codigo_cotizacion'),
            'CON' => $this->db->tableExists('contratos') ? $this->maxSuffix('contratos', 'codigo') : 0,
            'ADE' => $this->db->tableExists('contrato_ajustes') ? $this->maxSuffix('contrato_ajustes', 'codigo') : 0,
        ];

        foreach ($definiciones as $tipo => $ultimo) {
            $existente = $this->db->table('documentos_secuencia')
                ->where('tipo', $tipo)
                ->where('anio', $anioActual)
                ->get()
                ->getRowArray();
            if ($existente === null) {
                $this->db->table('documentos_secuencia')->insert([
                    'tipo'   => $tipo,
                    'anio'   => $anioActual,
                    'ultimo' => (int) $ultimo,
                ]);
            } elseif ((int) $existente['ultimo'] < (int) $ultimo) {
                $this->db->table('documentos_secuencia')
                    ->where('id', $existente['id'])
                    ->update(['ultimo' => (int) $ultimo]);
            }
        }
    }

    private function maxSuffix(string $tabla, string $columna): int
    {
        if (! $this->db->tableExists($tabla) || ! $this->db->fieldExists($columna, $tabla)) {
            return 0;
        }

        $maximo = 0;
        foreach ($this->db->table($tabla)->select($columna)->where($columna . ' !=', null)->get()->getResultArray() as $fila) {
            $codigo = (string) ($fila[$columna] ?? '');
            if (preg_match('/-(\d+)$/', $codigo, $coincidencia) === 1) {
                $maximo = max($maximo, (int) $coincidencia[1]);
            }
        }

        return $maximo;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function pareceSolicitudPublica(array $fila): bool
    {
        $senales = array_filter([
            $fila['distrito'] ?? null,
            $fila['provincia'] ?? null,
            $fila['nivel'] ?? null,
            $fila['tipo_servicio'] ?? null,
        ], static fn ($valor): bool => is_string($valor) && trim($valor) !== '');

        return $senales !== [] && empty($fila['nombre_seccion']);
    }

    private function anioDe(?string $fecha): int
    {
        if ($fecha === null || $fecha === '') {
            return (int) date('Y');
        }
        $timestamp = strtotime($fecha);

        return $timestamp === false ? (int) date('Y') : (int) date('Y', $timestamp);
    }

    /**
     * @param list<string> $columnas
     */
    private function ensureIndex(string $tabla, string $nombre, array $columnas, bool $unico = false): void
    {
        if (! $this->db->tableExists($tabla)) {
            return;
        }

        foreach ($this->db->getIndexData($tabla) as $indice) {
            if (($indice->name ?? '') === $nombre) {
                return;
            }
        }

        $tablaSegura = $this->db->protectIdentifiers($this->db->prefixTable($tabla), false);
        $columnasSeguras = implode(', ', array_map(
            fn (string $columna): string => $this->db->protectIdentifiers($columna, true, false),
            $columnas
        ));
        $tipo = $unico ? 'UNIQUE INDEX' : 'INDEX';
        $this->db->query('CREATE ' . $tipo . ' ' . $nombre . ' ON ' . $tablaSegura . ' (' . $columnasSeguras . ')');
    }

    private function dropIndex(string $tabla, string $nombre): void
    {
        if (! $this->db->tableExists($tabla)) {
            return;
        }

        foreach ($this->db->getIndexData($tabla) as $indice) {
            if (($indice->name ?? '') !== $nombre) {
                continue;
            }

            if ($this->db->getPlatform() === 'MySQLi') {
                $tablaSegura = $this->db->protectIdentifiers($this->db->prefixTable($tabla), false);
                $this->db->query('DROP INDEX ' . $nombre . ' ON ' . $tablaSegura);
            } else {
                $this->db->query('DROP INDEX ' . $this->db->protectIdentifiers($nombre, false));
            }
            return;
        }
    }
}
