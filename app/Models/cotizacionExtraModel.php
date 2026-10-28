<?php

namespace App\Models;

use CodeIgniter\Model;

class CotizacionExtraModel extends Model
{
    protected $table            = 'cotizacion_extras';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields    = [
        'cotizacion_id',
        'descripcion',
        'monto',
    ];

    protected $useTimestamps = false;
}
