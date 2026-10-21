<?php

namespace App\Models;

use CodeIgniter\Model;

class CotizacionDetalleModel extends Model
{
    protected $table            = 'cotizacion_detalles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'cotizacion_id', 
        'producto_servicio_id', 
        'cantidad', 
        'precio_unitario', 
        'subtotal'
    ];

    protected $useTimestamps = false;
}