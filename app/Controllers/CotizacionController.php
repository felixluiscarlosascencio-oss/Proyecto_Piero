<?php

namespace App\Controllers;

use App\Models\CotizacionModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class CotizacionController extends BaseController
{
    public function index(): string
    {
        $model = new CotizacionModel();

        return view('admin/cotizaciones/index', [
            'cotizaciones' => $model->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function editar(int $id)
    {
        $cotizacion = (new CotizacionModel())->find($id);

        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la solicitud.');
        }

        if (! in_array($cotizacion['estado'], [
            CotizacionModel::ESTADO_SOLICITUD,
            CotizacionModel::ESTADO_APROBADA_ADMIN,
        ], true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Esta cotización ya no se puede modificar.');
        }

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);

        return view('admin/cotizaciones/editar', ['cotizacion' => $cotizacion]);
    }

    public function actualizar(int $id)
    {
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if ($cotizacion === null || ! in_array($cotizacion['estado'], [
            CotizacionModel::ESTADO_SOLICITUD,
            CotizacionModel::ESTADO_APROBADA_ADMIN,
        ], true)) {
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
        if ($precioUnitario > 9999999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total por estudiante supera el máximo permitido.');
        }

        if (! $model->update($id, [
            'cantidad_estudiantes' => $cantidad,
            'servicios_json'       => json_encode($serviciosNormalizados, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario'      => $precioUnitario,
            'total_seccion'        => round($precioUnitario * $cantidad, 2),
        ])) {
            log_message('error', 'Failed to update quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('mensaje', 'Cotización actualizada.');
    }

    public function aprobar(int $id)
    {
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if ($cotizacion === null || $cotizacion['estado'] !== CotizacionModel::ESTADO_SOLICITUD) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se pueden aprobar solicitudes pendientes de revisión.');
        }

        if ((float) $cotizacion['total_seccion'] <= 0) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'Define los precios antes de aprobar la cotización.');
        }

        if (! $model->update($id, [
            'estado'       => CotizacionModel::ESTADO_APROBADA_ADMIN,
            'aprobado_por' => session()->get('id_usuario'),
        ])) {
            log_message('error', 'Failed to approve quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'No se pudo aprobar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('mensaje', 'Cotización aprobada. Ya se puede generar el PDF.');
    }

    public function descargarPdf(int $id)
    {
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if ($cotizacion === null || $cotizacion['estado'] !== CotizacionModel::ESTADO_APROBADA_ADMIN) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La cotización debe estar aprobada por el administrador antes de generar el PDF.');
        }

        $diasVigencia = filter_var($this->request->getPost('dias_vigencia'), FILTER_VALIDATE_INT);
        if ($diasVigencia === false || $diasVigencia < 1 || $diasVigencia > 365) {
            return redirect()->back()->withInput()->with('error', 'La vigencia debe ser de 1 a 365 días.');
        }

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);
        $cotizacion['dias_vigencia'] = $diasVigencia;
        $cotizacion['vence_en'] = date('Y-m-d', strtotime('+' . $diasVigencia . ' days'));

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin/cotizaciones/pdf', ['cotizacion' => $cotizacion]), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $contenidoPdf = $pdf->output();

        if (! $model->update($id, [
            'estado'         => CotizacionModel::ESTADO_PENDIENTE,
            'dias_vigencia'  => $diasVigencia,
            'vence_en'       => $cotizacion['vence_en'] . ' 23:59:59',
            'pdf_generado_en' => date('Y-m-d H:i:s'),
        ])) {
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
        $model = new CotizacionModel();
        $cotizacion = $model->find($id);

        if (
            $cotizacion === null
            || $cotizacion['estado'] !== CotizacionModel::ESTADO_PENDIENTE
            || ($cotizacion['vence_en'] !== null && strtotime($cotizacion['vence_en']) < time())
        ) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se puede confirmar una cotización pendiente.');
        }

        if (! $model->update($id, ['estado' => CotizacionModel::ESTADO_APROBADO])) {
            log_message('error', 'Failed to confirm quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('error', 'No se pudo confirmar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id))->with('mensaje', 'Cotización marcada como aprobada por el cliente. Ya puede coordinarse la producción.');
    }
}
