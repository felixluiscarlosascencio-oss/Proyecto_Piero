<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\CotizacionModel;
use App\Models\SolicitudAdminModel;

class SolicitudAdminController extends BaseController
{
    public function index()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $solicitudes = (new SolicitudAdminModel())
            ->select('solicitudes_admin.*, usuarios.nombre AS solicitante_nombre, usuarios.usuario AS solicitante_usuario')
            ->join('usuarios', 'usuarios.id = solicitudes_admin.solicitado_por', 'left')
            ->orderBy('solicitudes_admin.created_at', 'DESC')
            ->findAll();

        return view('admin/solicitudes/index', ['solicitudes' => $solicitudes]);
    }

    public function resolver(int $id, string $decision)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        if (! in_array($decision, ['aprobar', 'denegar'], true)) {
            return redirect()->to(base_url('admin/solicitudes'))->with('error', 'Decisión no válida.');
        }

        $solicitudModel = new SolicitudAdminModel();
        $solicitud = $solicitudModel->find($id);
        if ($solicitud === null || $solicitud['estado'] !== 'pendiente') {
            return redirect()->to(base_url('admin/solicitudes'))->with('error', 'La solicitud ya fue atendida o no existe.');
        }

        $db = db_connect();
        $db->transBegin();

        if ($decision === 'aprobar') {
            $cotizaciones = new CotizacionModel();
            $cotizacion = $cotizaciones->find((int) $solicitud['entidad_id']);
            if ($cotizacion === null || $solicitud['entidad'] !== 'cotizacion') {
                $db->transRollback();

                return redirect()->to(base_url('admin/solicitudes'))->with('error', 'El registro solicitado ya no está disponible.');
            }

            if ($solicitud['accion'] === 'eliminar') {
                if ($db->table('contratos')->where('cotizacion_id', $cotizacion['id'])->countAllResults() > 0) {
                    $db->transRollback();

                    return redirect()->to(base_url('admin/solicitudes'))->with('error', 'No se puede eliminar una cotización convertida a contrato.');
                }
                if (! $cotizaciones->delete((int) $cotizacion['id'])) {
                    $db->transRollback();

                    return redirect()->to(base_url('admin/solicitudes'))->with('error', 'No se pudo eliminar la cotización.');
                }
            } else {
                $db->transRollback();

                return redirect()->to(base_url('admin/solicitudes'))->with('error', 'Las ediciones de cotización ya no están permitidas; puedes denegar esta solicitud antigua.');
            }
        }

        if (! $solicitudModel->update($id, [
            'estado'      => $decision === 'aprobar' ? 'aprobada' : 'denegada',
            'resuelto_por' => session()->get('id_usuario'),
            'resuelto_en' => date('Y-m-d H:i:s'),
        ])) {
            $db->transRollback();

            return redirect()->to(base_url('admin/solicitudes'))->with('error', 'No se pudo guardar la decisión.');
        }

        if ($db->transStatus() === false) {
            $db->transRollback();

            return redirect()->to(base_url('admin/solicitudes'))->with('error', 'No se pudo guardar la decisión.');
        }

        $db->transCommit();

        return redirect()->to(base_url('admin/solicitudes'))->with('mensaje', 'Solicitud ' . ($decision === 'aprobar' ? 'confirmada' : 'denegada') . '.');
    }
}
