<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoServicioModel extends Model
{
    protected $table            = 'productos_servicios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'nombre_item',
        'tipo',
        'descripcion',
        'detalle_largo',
        'precio_unitario',
        'estado',
        'galeria_json',
        'creado_en',
    ];

    protected $useTimestamps = false;
}