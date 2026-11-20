<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Corrige el backfill documental de la migración anterior y refuerza la
 * integridad del módulo comercial:
 *
 * - rellena codigos CON/ADE que quedaron nulos por la cache de campos;
 * - recalcula los consecutivos a partir de los códigos ya emitidos;
 * - impide que una cotización genere más de un contrato;
 * - agrega índices de apoyo para las consultas del panel.
 */
class FixCommercialDocumentCodes extends Migration
{
    public function up()
    {
        $this->backfillContratos();
        $this->backfillAjustes();
        $this->recalcularSecuencias();

        $this->ensureIndex('contratos', 'contratos_cotizacion_id_unique', ['cotizacion_id'], true);
        $this->ensureIndex('eventos_comerciales', 'eventos_cotizacion_fecha_index', ['cotizacion_id', 'creado_en']);
        $this->ensureIndex('contrato_ajustes', 'contrato_ajustes_contrato_fecha_index', ['contrato_id', 'creado_en']);
    }

    public function down()
    {
        foreach ([
            ['contratos', 'contratos_cotizacion_id_unique'],
            ['eventos_comerciales', 'eventos_cotizacion_fecha_index'],
            ['contrato_ajustes', 'contrato_ajustes_contrato_fecha_index'],
        ] as [$tabla, $indice]) {
            $this->dropIndex($tabla, $indice);
        }
    }

    private function backfillContratos(): void
    {
        if (! $this->db->tableExists('contratos') || ! $this->db->fieldExists('codigo', 'contratos')) {
            return;
        }

        $filas = $this->db->table('contratos')
            ->select('id, numero_contrato, creado_en')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($filas as $fila) {
            $anio = $this->anioDe($fila['creado_en'] ?? null);
            $sufijo = (int) $fila['id'];
            if (preg_match('/-(\d+)$/', (string) ($fila['numero_contrato'] ?? ''), $coincidencia) === 1) {
                $sufijo = (int) $coincidencia[1];
            }

            $this->db->table('contratos')
                ->where('id', $fila['id'])
                ->update([
                    'codigo' => 'CON-' . $anio . '-' . str_pad((string) $sufijo, 6, '0', STR_PAD_LEFT),
                ]);
        }
    }

    private function backfillAjustes(): void
    {
        if (! $this->db->tableExists('contrato_ajustes')
            || ! $this->db->fieldExists('codigo', 'contrato_ajustes')) {
            return;
        }

        $filas = $this->db->table('contrato_ajustes')
            ->select('id, creado_en')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($filas as $fila) {
            $anio = $this->anioDe($fila['creado_en'] ?? null);
            $this->db->table('contrato_ajustes')
                ->where('id', $fila['id'])
                ->update([
                    'codigo' => 'ADE-' . $anio . '-' . str_pad((string) $fila['id'], 6, '0', STR_PAD_LEFT),
                ]);
        }
    }

    private function recalcularSecuencias(): void
    {
        if (! $this->db->tableExists('documentos_secuencia')) {
            return;
        }

        $anio = (int) date('Y');
        $definiciones = [
            'SOL' => $this->db->tableExists('solicitudes_cotizacion')
                ? $this->maxSuffix('solicitudes_cotizacion', 'codigo_solicitud', $anio)
                : 0,
            'COT' => $this->db->tableExists('solicitudes_cotizacion')
                ? $this->maxSuffix('solicitudes_cotizacion', 'codigo_cotizacion', $anio)
                : 0,
            'CON' => $this->db->tableExists('contratos')
                ? $this->maxSuffix('contratos', 'codigo', $anio)
                : 0,
            'ADE' => $this->db->tableExists('contrato_ajustes')
                ? $this->maxSuffix('contrato_ajustes', 'codigo', $anio)
                : 0,
        ];

        foreach ($definiciones as $tipo => $ultimo) {
            $existente = $this->db->table('documentos_secuencia')
                ->where('tipo', $tipo)
                ->where('anio', $anio)
                ->get()
                ->getRowArray();

            if ($existente === null) {
                $this->db->table('documentos_secuencia')->insert([
                    'tipo'   => $tipo,
                    'anio'   => $anio,
                    'ultimo' => $ultimo,
                ]);
            } elseif ((int) $existente['ultimo'] < $ultimo) {
                $this->db->table('documentos_secuencia')
                    ->where('id', $existente['id'])
                    ->update(['ultimo' => $ultimo]);
            }
        }
    }

    private function maxSuffix(string $tabla, string $columna, int $anio): int
    {
        if (! $this->db->tableExists($tabla) || ! $this->db->fieldExists($columna, $tabla)) {
            return 0;
        }

        $prefijo = $anio . '-';
        $maximo = 0;
        $filas = $this->db->table($tabla)
            ->select($columna)
            ->where($columna . ' IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($filas as $fila) {
            $codigo = (string) ($fila[$columna] ?? '');
            if (preg_match('/-(\d{4})-(\d+)$/', $codigo, $coincidencia) === 1
                && $coincidencia[1] . '-' === $prefijo) {
                $maximo = max($maximo, (int) $coincidencia[2]);
            }
        }

        return $maximo;
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

        if ($unico && $this->tieneDuplicados($tabla, $columnas)) {
            log_message('warning', 'No se creó el índice único ' . $nombre . ': existen datos duplicados en ' . $tabla . '.');

            return;
        }

        $tablaSegura = $this->db->protectIdentifiers($this->db->prefixTable($tabla), false);
        $columnasSeguras = implode(', ', array_map(
            fn (string $columna): string => $this->db->protectIdentifiers($columna, true, false),
            $columnas
        ));
        $tipo = $unico ? 'UNIQUE INDEX' : 'INDEX';
        $this->db->query('CREATE ' . $tipo . ' ' . $nombre . ' ON ' . $tablaSegura . ' (' . $columnasSeguras . ')');
    }

    /**
     * @param list<string> $columnas
     */
    private function tieneDuplicados(string $tabla, array $columnas): bool
    {
        $grupos = implode(', ', array_map(
            fn (string $columna): string => $this->db->protectIdentifiers($columna, true, false),
            $columnas
        ));
        $filtro = count($columnas) === 1
            ? ' WHERE ' . $this->db->protectIdentifiers($columnas[0], true, false) . ' IS NOT NULL'
            : '';
        $sql = 'SELECT COUNT(*) AS total FROM (SELECT ' . $grupos
            . ' FROM ' . $this->db->protectIdentifiers($this->db->prefixTable($tabla), false)
            . $filtro
            . ' GROUP BY ' . $grupos . ' HAVING COUNT(*) > 1) duplicados';
        $fila = $this->db->query($sql)->getRowArray();

        return (int) ($fila['total'] ?? 0) > 0;
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
