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

    // Ajusta o confirma los campos exactos según tu migración de productos/servicios
    protected $allowedFields    = [
        'nombre', 
        'descripcion', 
        'precio_base', 
        'tipo',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}