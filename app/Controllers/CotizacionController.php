<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\CotizacionModel;
use App\Models\SolicitudAdminModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class CotizacionController extends BaseController
{
    public function index()
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para ver cotizaciones.');
        }

        $model = new CotizacionModel();

        return view('admin/cotizaciones/index', [
            'cotizaciones' => $model
                ->select('solicitudes_cotizacion.*, contratos.id AS contrato_id')
                ->join('contratos', 'contratos.cotizacion_id = solicitudes_cotizacion.id', 'left')
                ->orderBy('created_at', 'DESC')
                ->findAll(),
            'vigentes'     => false,
            'puedeCrear'   => AdminAccess::isAdmin(),
        ]);
    }

    public function vigentes()
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para ver cotizaciones.');
        }

        $cotizaciones = (new CotizacionModel())
            ->where('estado', CotizacionModel::ESTADO_PENDIENTE)
            ->groupStart()
                ->where('vence_en', null)
                ->orWhere('vence_en >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('vence_en', 'ASC')
            ->findAll();

        return view('admin/cotizaciones/index', ['cotizaciones' => $cotizaciones, 'vigentes' => true, 'puedeCrear' => AdminAccess::isAdmin()]);
    }

    public function nueva()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear cotizaciones directamente.');
        }

        return view('admin/cotizaciones/nueva');
    }

    public function crear()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear cotizaciones directamente.');
        }

        $nombreColegio = trim((string) $this->request->getPost('nombre_colegio'));
        $cantidad = filter_var($this->request->getPost('cantidad_estudiantes'), FILTER_VALIDATE_INT);
        $servicios = $this->normalizarServicios($this->request->getPost('servicios'));

        if ($nombreColegio === '' || mb_strlen($nombreColegio) > 150 || $cantidad === false || $cantidad < 1 || $cantidad > 10000 || $servicios === []) {
            return redirect()->back()->withInput()->with('error', 'Revisa la institución, la cantidad de estudiantes y los servicios con sus precios.');
        }

        $precioUnitario = array_sum(array_column($servicios, 'precio_unitario'));
        $total = round($precioUnitario * $cantidad, 2);
        if ($precioUnitario <= 0 || $precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total de la cotización supera el máximo permitido.');
        }

        $model = new CotizacionModel();
        $id = $model->insert([
            'nombre_colegio'       => $nombreColegio,
            'cantidad_estudiantes' => $cantidad,
            'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario'      => $precioUnitario,
            'total_seccion'        => $total,
            'estado'               => CotizacionModel::ESTADO_APROBADA_ADMIN,
            'aprobado_por'         => (int) session()->get('id_usuario'),
        ]);

        if (! $id) {
            return redirect()->back()->withInput()->with('error', 'No se pudo crear la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/editar'))->with('mensaje', 'Cotización creada. Define su vigencia para enviarla al cliente.');
    }

    public function ver(int $id)
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para ver cotizaciones.');
        }

        $cotizacion = (new CotizacionModel())->find($id);
        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la solicitud.');
        }

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);
        $contratoId = db_connect()->table('contratos')->select('id')->where('cotizacion_id', $id)->get()->getRowArray()['id'] ?? null;

        return view('admin/cotizaciones/editar', ['cotizacion' => $cotizacion, 'soloLectura' => true, 'contratoId' => $contratoId]);
    }

    public function editar(int $id)
    {
        if (! AdminAccess::can('cotizaciones.editar')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para solicitar cambios en cotizaciones.');
        }

        $cotizacion = (new CotizacionModel())->find($id);

        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la solicitud.');
        }

        $estadosEditables = AdminAccess::isAdmin()
            ? [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN, CotizacionModel::ESTADO_PENDIENTE]
            : [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN];
        if (! in_array($cotizacion['estado'], $estadosEditables, true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Esta cotización se administra desde su contrato.');
        }

        if (! in_array($cotizacion['estado'], [
            CotizacionModel::ESTADO_SOLICITUD,
            CotizacionModel::ESTADO_APROBADA_ADMIN,
        ], true)) {
            if (! AdminAccess::isAdmin() || $cotizacion['estado'] !== CotizacionModel::ESTADO_PENDIENTE) {
                return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Esta cotización ya no se puede modificar.');
            }
        }

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);

        return view('admin/cotizaciones/editar', ['cotizacion' => $cotizacion, 'soloLectura' => false]);
    }

    public function actualizar(int $id)
    {
        if (! AdminAccess::isAdmin() && ! AdminAccess::can('cotizaciones.editar')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para solicitar cambios en cotizaciones.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        $estadosEditables = AdminAccess::isAdmin()
            ? [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN, CotizacionModel::ESTADO_PENDIENTE]
            : [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN];
        if ($cotizacion === null || ! in_array($cotizacion['estado'], $estadosEditables, true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se puede actualizar esta cotización.');
        }

        $cantidad = filter_var($this->request->getPost('cantidad_estudiantes'), FILTER_VALIDATE_INT);
        $servicios = $this->request->getPost('servicios');

        if ($cantidad === false || $cantidad < 1 || $cantidad > 10000 || ! is_array($servicios)) {
            return redirect()->back()->withInput()->with('error', 'Ingresa una cantidad válida y al menos un servicio.');
        }

        $serviciosNormalizados = [];
        foreach ($servicios as $servicio) {
            if (! is_array($servicio)) {
                continue;
            }

            $nombre = $servicio['nombre'] ?? null;
            $precio = $servicio['precio_unitario'] ?? null;

            if ($nombre === null && ($precio === null || $precio === '')) {
                continue;
            }

            if (
                ! is_string($nombre)
                || trim($nombre) === ''
                || mb_strlen(trim($nombre)) > 120
                || ! is_numeric($precio)
                || (float) $precio < 0
                || (float) $precio > 9999999999.99
            ) {
                return redirect()->back()->withInput()->with('error', 'Revisa el nombre y el precio de cada servicio.');
            }

            $serviciosNormalizados[] = [
                'nombre'          => trim($nombre),
                'precio_unitario' => round((float) $precio, 2),
            ];
        }

        if ($serviciosNormalizados === []) {
            return redirect()->back()->withInput()->with('error', 'La cotización debe incluir al menos un servicio con precio.');
        }

        $precioUnitario = array_sum(array_column($serviciosNormalizados, 'precio_unitario'));
        $total = round($precioUnitario * $cantidad, 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total por estudiante supera el máximo permitido.');
        }

        $cambios = [
            'cantidad_estudiantes' => $cantidad,
            'servicios_json'       => json_encode($serviciosNormalizados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario'      => $precioUnitario,
            'total_seccion'        => $total,
        ];
        if ($cotizacion['estado'] === CotizacionModel::ESTADO_PENDIENTE) {
            $cambios['pdf_generado_en'] = null;
        }

        if (! AdminAccess::isAdmin()) {
            (new SolicitudAdminModel())->insert([
                'entidad'       => 'cotizacion',
                'entidad_id'    => $id,
                'accion'        => 'editar',
                'payload_json'  => json_encode($cambios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'estado'        => 'pendiente',
                'solicitado_por' => session()->get('id_usuario'),
            ]);

            return redirect()->to(base_url('admin/solicitudes'))
                ->with('mensaje', 'La edición quedó pendiente de aprobación del administrador.');
        }

        if (! $model->update($id, $cambios)) {
            log_message('error', 'Failed to update quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('mensaje', 'Cotización actualizada.');
    }

    public function eliminar(int $id)
    {
        if (! AdminAccess::isAdmin() && ! AdminAccess::can('cotizaciones.eliminar')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para solicitar la eliminación.');
        }

        $model = new CotizacionModel();
        if ($model->find($id) === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la cotización.');
        }

        if (db_connect()->table('contratos')->where('cotizacion_id', $id)->countAllResults() > 0) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'Una cotización convertida a contrato no se puede eliminar.');
        }

        if (! AdminAccess::isAdmin()) {
            (new SolicitudAdminModel())->insert([
                'entidad'        => 'cotizacion',
                'entidad_id'     => $id,
                'accion'         => 'eliminar',
                'estado'         => 'pendiente',
                'solicitado_por' => session()->get('id_usuario'),
            ]);

            return redirect()->to(base_url('admin/solicitudes'))
                ->with('mensaje', 'La eliminación quedó pendiente de aprobación del administrador.');
        }

        if (! $model->delete($id)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pudo eliminar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones'))->with('mensaje', 'Cotización eliminada.');
    }

    public function activar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede activar la vigencia.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! in_array($cotizacion['estado'], [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN], true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se puede activar una cotización revisada.');
        }
        if ((float) $cotizacion['total_seccion'] <= 0 || (float) $cotizacion['total_seccion'] > 99999999.99) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/editar'))->with('error', 'Define precios válidos dentro del máximo permitido antes de activar la cotización.');
        }

        $diasVigencia = filter_var($this->request->getPost('dias_vigencia'), FILTER_VALIDATE_INT);
        if ($diasVigencia === false || $diasVigencia < 1 || $diasVigencia > 365) {
            return redirect()->back()->withInput()->with('error', 'La vigencia debe ser de 1 a 365 días.');
        }

        $venceEn = (new \DateTimeImmutable('today'))->modify('+' . $diasVigencia . ' days')->format('Y-m-d 23:59:59');
        if (! $model->update($id, [
            'estado'          => CotizacionModel::ESTADO_PENDIENTE,
            'dias_vigencia'   => $diasVigencia,
            'vence_en'        => $venceEn,
            'aprobado_por'    => (int) session()->get('id_usuario'),
        ])) {
            return redirect()->back()->with('error', 'No se pudo activar la vigencia de la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/vigentes'))->with('mensaje', 'Cotización vigente hasta ' . date('d/m/Y', strtotime($venceEn)) . '.');
    }

    public function descargarPdf(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede generar la cotización.');
        }
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if ($cotizacion === null || $cotizacion['estado'] !== CotizacionModel::ESTADO_PENDIENTE) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo las cotizaciones vigentes pueden generar un PDF.');
        }

        $diasVigencia = (int) $cotizacion['dias_vigencia'];
        if ($diasVigencia < 1 || $diasVigencia > 365 || empty($cotizacion['vence_en'])) {
            return redirect()->back()->with('error', 'La cotización no tiene una vigencia válida.');
        }

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);
        $cotizacion['dias_vigencia'] = $diasVigencia;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin/cotizaciones/pdf', ['cotizacion' => $cotizacion]), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $contenidoPdf = $pdf->output();

        if (! $model->update($id, ['pdf_generado_en' => date('Y-m-d H:i:s')])) {
            log_message('error', 'Failed to mark generated PDF for quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'No se pudo guardar la vigencia y el estado del PDF.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="cotizacion-' . $id . '.pdf"')
            ->setBody($contenidoPdf);
    }

    public function confirmar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede confirmar cotizaciones.');
        }
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if ($cotizacion === null || ! in_array($cotizacion['estado'], [CotizacionModel::ESTADO_PENDIENTE, CotizacionModel::ESTADO_APROBADO], true)
            || ($cotizacion['estado'] === CotizacionModel::ESTADO_PENDIENTE && $cotizacion['vence_en'] !== null && strtotime($cotizacion['vence_en']) < time())) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se puede confirmar una cotización pendiente.');
        }
        if ((float) $cotizacion['total_seccion'] <= 0 || (float) $cotizacion['total_seccion'] > 99999999.99) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'El total no es válido para crear un contrato.');
        }

        $db = db_connect();
        $contrato = $db->table('contratos')->where('cotizacion_id', $id)->get()->getRowArray();
        if ($contrato !== null) {
            return redirect()->to(base_url('admin/contratos/' . $contrato['id']))->with('mensaje', 'Esta cotización ya tiene un contrato asociado.');
        }

        $db->transBegin();
        $db->table('contratos')->insert([
            'numero_contrato'      => 'PP-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
            'cotizacion_id'        => $id,
            'institucion_id'       => 1,
            'cliente_id'           => 1,
            'cant_alumnos_inicial' => (int) $cotizacion['cantidad_estudiantes'],
            'monto_total'          => (float) $cotizacion['total_seccion'],
            'monto_pagado'         => 0,
            'monto_saldo'          => (float) $cotizacion['total_seccion'],
            'fecha_contrato'       => date('Y-m-d'),
            'estado_contrato'      => 'Activo',
            'servicios_json'       => $cotizacion['servicios_json'],
            'agenda_json'          => json_encode([], JSON_THROW_ON_ERROR),
            'cuadros_json'         => json_encode([], JSON_THROW_ON_ERROR),
            'creado_en'            => date('Y-m-d H:i:s'),
        ]);
        $contratoId = (int) $db->insertID();
        if ($contratoId > 0) {
            $db->table('solicitudes_cotizacion')->where('id', $id)->update([
                'estado'     => CotizacionModel::ESTADO_APROBADO,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($contratoId === 0 || $db->transStatus() === false) {
            $db->transRollback();

            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'No se pudo confirmar la cotización.');
        }
        $db->transCommit();

        return redirect()->to(base_url('admin/contratos/' . $contratoId))->with('mensaje', 'Contrato creado desde la cotización confirmada.');
    }

    private function normalizarServicios($servicios): array
    {
        if (! is_array($servicios)) {
            return [];
        }

        $normalizados = [];
        foreach ($servicios as $servicio) {
            if (! is_array($servicio)) {
                return [];
            }
            $nombre = $servicio['nombre'] ?? '';
            $precio = $servicio['precio_unitario'] ?? null;
            if (! is_string($nombre)) {
                return [];
            }
            $nombre = trim($nombre);
            if ($nombre === '' && ($precio === null || $precio === '')) {
                continue;
            }
            if ($nombre === '' || mb_strlen($nombre) > 120 || ! is_numeric($precio) || (float) $precio < 0 || (float) $precio > 9999999999.99) {
                return [];
            }
            $normalizados[] = ['nombre' => $nombre, 'precio_unitario' => round((float) $precio, 2)];
        }

        return $normalizados;
    }
}
