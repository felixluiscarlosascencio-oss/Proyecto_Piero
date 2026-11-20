<?php

namespace App\Models;

use CodeIgniter\Model;

class CotizacionModel extends Model
{
    // Estado comercial de la cotización formal.
    public const ESTADO_BORRADOR    = 'borrador';
    public const ESTADO_ENVIADA     = 'enviada';
    public const ESTADO_ACEPTADA    = 'aceptada';
    public const ESTADO_RECHAZADA   = 'rechazada';
    public const ESTADO_VENCIDA     = 'vencida';
    public const ESTADO_CONVERTIDA  = 'convertida';
    public const ESTADO_REEMPLAZADA = 'reemplazada';

    // Revisión administrativa de la solicitud.
    public const REVISION_SIN_REVISAR = 'SIN_REVISAR';
    public const REVISION_EN_REVISION = 'EN_REVISION';
    public const REVISION_REVISADA    = 'REVISADA';

    // Alias heredados: se mantienen para no romper integraciones antiguas.
    public const ESTADO_SOLICITUD      = self::ESTADO_BORRADOR;
    public const ESTADO_APROBADA_ADMIN = self::ESTADO_BORRADOR;
    public const ESTADO_PENDIENTE      = self::ESTADO_ENVIADA;
    public const ESTADO_APROBADO       = self::ESTADO_CONVERTIDA;

    protected $table            = 'solicitudes_cotizacion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'nombre_colegio',
        'nombre_seccion',
        'persona_encargada',
        'telefono_contacto',
        'distrito',
        'provincia',
        'nivel',
        'tipo_servicio',
        'observaciones',
        'cantidad_estudiantes',
        'servicios_json',
        'precio_unitario',
        'total_seccion',
        'estado',
        'estado_revision',
        'codigo_solicitud',
        'codigo_cotizacion',
        'version',
        'primera_sesion',
        'dias_vigencia',
        'vence_en',
        'aprobado_por',
        'respondido_en',
        'respondido_por',
        'motivo_decision',
        'pdf_generado_en',
        'cotizacion_origen_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Estado comercial efectivo (una cotización enviada y vencida se muestra
     * como vencida sin alterar el histórico almacenado).
     *
     * @param array<string, mixed> $cotizacion
     */
    public static function estadoEfectivo(array $cotizacion): string
    {
        $estado = (string) ($cotizacion['estado'] ?? self::ESTADO_BORRADOR);
        $venceEn = $cotizacion['vence_en'] ?? null;

        if ($estado === self::ESTADO_ENVIADA && is_string($venceEn) && $venceEn !== ''
            && strtotime($venceEn) !== false && strtotime($venceEn) < time()) {
            return self::ESTADO_VENCIDA;
        }

        return $estado;
    }

    public static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            self::ESTADO_BORRADOR    => 'Borrador',
            self::ESTADO_ENVIADA     => 'Enviada',
            self::ESTADO_ACEPTADA    => 'Aceptada',
            self::ESTADO_RECHAZADA   => 'Rechazada',
            self::ESTADO_VENCIDA     => 'Vencida',
            self::ESTADO_CONVERTIDA  => 'Convertida a contrato',
            self::ESTADO_REEMPLAZADA => 'Versión histórica',
            default                  => ucfirst($estado),
        };
    }

    public static function etiquetaRevision(string $revision): string
    {
        return match ($revision) {
            self::REVISION_SIN_REVISAR => 'Sin revisar',
            self::REVISION_EN_REVISION => 'En revisión',
            self::REVISION_REVISADA    => 'Revisada',
            default                    => ucfirst(strtolower($revision)),
        };
    }

    /**
     * @param array<string, mixed> $cotizacion
     */
    public static function esCotizacionFormal(array $cotizacion): bool
    {
        return ! empty($cotizacion['codigo_cotizacion']);
    }

    /**
     * @param array<string, mixed> $cotizacion
     */
    public static function codigoVisible(array $cotizacion): string
    {
        return (string) ($cotizacion['codigo_cotizacion']
            ?: ($cotizacion['codigo_solicitud'] ?: '#' . ($cotizacion['id'] ?? '')));
    }
}
