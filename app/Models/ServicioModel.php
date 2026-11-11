<?php

namespace App\Models;

use CodeIgniter\Model;

class ServicioModel extends Model
{
    protected $table            = 'servicios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'nombre_item',
        'descripcion',
        'detalle_largo',
        'galeria_json',
        'etiqueta_destacada',
        'precio_interno',
    ];

    protected $useTimestamps = false;
}