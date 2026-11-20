<?php

namespace App\Libraries;

use InvalidArgumentException;
use Throwable;

/**
 * Genera códigos documentales consecutivos y persistentes.
 *
 * El consecutivo se guarda por tipo y año en `documentos_secuencia`, de modo
 * que los códigos no dependen del orden visual ni del id de la fila.
 */
class DocumentoCodigo
{
    public const SOL = 'SOL';
    public const COT = 'COT';
    public const CON = 'CON';
    public const ADE = 'ADE';

    private const TIPOS = [self::SOL, self::COT, self::CON, self::ADE];

    public static function siguiente(string $tipo, ?int $anio = null): string
    {
        $tipo = strtoupper(trim($tipo));
        if (! in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('Tipo de documento no válido: ' . $tipo);
        }

        $anio ??= (int) date('Y');
        $db = db_connect();
        $propiaTransaccion = $db->transDepth === 0;
        if ($propiaTransaccion) {
            $db->transBegin();
        }

        try {
            $bloqueo = $db->getPlatform() === 'MySQLi' ? ' FOR UPDATE' : '';
            $fila = $db->query(
                'SELECT id, ultimo FROM documentos_secuencia WHERE tipo = ? AND anio = ?' . $bloqueo,
                [$tipo, $anio]
            )->getRowArray();

            if ($fila === null) {
                try {
                    $db->table('documentos_secuencia')->insert([
                        'tipo'   => $tipo,
                        'anio'   => $anio,
                        'ultimo' => 0,
                    ]);
                } catch (Throwable $exception) {
                    // Otra petición pudo crear la fila al mismo tiempo.
                    log_message('debug', 'Sequence row already exists for ' . $tipo . '/' . $anio . ': ' . $exception->getMessage());
                }
                $fila = $db->query(
                    'SELECT id, ultimo FROM documentos_secuencia WHERE tipo = ? AND anio = ?' . $bloqueo,
                    [$tipo, $anio]
                )->getRowArray();
            }

            $db->query(
                'UPDATE documentos_secuencia SET ultimo = ultimo + 1 WHERE tipo = ? AND anio = ?',
                [$tipo, $anio]
            );

            $fila = $db->table('documentos_secuencia')
                ->where('tipo', $tipo)
                ->where('anio', $anio)
                ->get()
                ->getRowArray();

            if ($fila === null) {
                throw new \RuntimeException('No se pudo obtener el consecutivo de ' . $tipo . '.');
            }

            $codigo = sprintf('%s-%d-%06d', $tipo, $anio, (int) $fila['ultimo']);

            if ($propiaTransaccion) {
                $db->transCommit();
            }

            return $codigo;
        } catch (Throwable $exception) {
            if ($propiaTransaccion) {
                $db->transRollback();
            }
            throw $exception;
        }
    }
}
