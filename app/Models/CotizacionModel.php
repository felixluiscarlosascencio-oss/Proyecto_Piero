<?php

namespace App\Models;

use CodeIgniter\Model;

class CotizacionModel extends Model
{
    protected $table            = 'cotizaciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'nombre_colegio',
        'grado',
        'seccion',
        'cantidad_alumnos',
        'sesiones',
        'modelo_cuadro',
        'servicios_json',
        'precio_unitario',
        'total_seccion',
        'fecha_creacion'
    ];

    // Dates
    protected $useTimestamps = false;
}