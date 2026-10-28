<?php

namespace App\Models;

use CodeIgniter\Model;

class CotizacionModel extends Model
{
    public const ESTADO_SOLICITUD = 'solicitud';
    public const ESTADO_APROBADA_ADMIN = 'aprobada_admin';
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO = 'aprobado';

    protected $table            = 'solicitudes_cotizacion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'nombre_colegio',
        'cantidad_estudiantes',
        'servicios_json',
        'precio_unitario',
        'total_seccion',
        'estado',
        'dias_vigencia',
        'vence_en',
        'aprobado_por',
        'pdf_generado_en',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}