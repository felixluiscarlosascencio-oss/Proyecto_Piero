<?php

namespace App\Models;

use CodeIgniter\Model;

class SolicitudAdminModel extends Model
{
    protected $table            = 'solicitudes_admin';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'entidad',
        'entidad_id',
        'accion',
        'payload_json',
        'estado',
        'solicitado_por',
        'resuelto_por',
        'resuelto_en',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
