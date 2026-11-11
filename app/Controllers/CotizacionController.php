<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\CotizacionModel;
use App\Models\ServicioModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class CotizacionController extends BaseController
{
    public function index()
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para ver cotizaciones.');
        }

        $cotizaciones = (new CotizacionModel())
            ->select('solicitudes_cotizacion.*, contratos.id AS contrato_id')
            ->join('contratos', 'contratos.cotizacion_id = solicitudes_cotizacion.id', 'left')
            ->orderBy('solicitudes_cotizacion.created_at', 'DESC')
            ->orderBy('solicitudes_cotizacion.id', 'DESC')
            ->findAll();

        return view('admin/cotizaciones/index', [
            'cotizaciones' => $cotizaciones,
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
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('admin/cotizaciones/index', [
            'cotizaciones' => $cotizaciones,
            'vigentes'     => true,
            'puedeCrear'   => AdminAccess::isAdmin(),
        ]);
    }

    public function nueva()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear cotizaciones directamente.');
        }

        return view('admin/cotizaciones/nueva', ['serviciosCatalogo' => $this->catalogoServicios()]);
    }

    public function crear()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear cotizaciones directamente.');
        }

        $datos = [
            'nombre_colegio'       => $this->postString('nombre_colegio'),
            'nombre_seccion'       => $this->postString('nombre_seccion'),
            'cantidad_estudiantes' => $this->postString('cantidad_estudiantes'),
            'persona_encargada'    => $this->postString('persona_encargada'),
            'telefono_contacto'    => $this->postString('telefono_contacto'),
        ];
        $validation = service('validation');
        $validation->setRules([
            'nombre_colegio'       => 'required|string|max_length[150]',
            'nombre_seccion'       => 'permit_empty|string|max_length[120]',
            'cantidad_estudiantes' => 'required|integer|greater_than[0]|less_than_equal_to[10000]',
            'persona_encargada'    => 'required|string|max_length[150]',
            'telefono_contacto'    => 'required|string|max_length[30]',
        ]);
        if (! $validation->run($datos) || ! preg_match('/^[0-9+().\s-]{7,30}$/', $datos['telefono_contacto'])) {
            return redirect()->back()->withInput()->with('error', 'Revisa la institución, promoción, cantidad, persona encargada y teléfono.');
        }

        $servicios = $this->serviciosSeleccionados(
            $this->request->getPost('servicios'),
            $this->request->getPost('precios')
        );
        if ($servicios === []) {
            return redirect()->back()->withInput()->with('error', 'Selecciona al menos un servicio disponible.');
        }

        $cantidad = (int) $datos['cantidad_estudiantes'];
        $precioUnitario = array_sum(array_column($servicios, 'precio_unitario'));
        $total = round($precioUnitario * $cantidad, 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total de la cotización supera el máximo permitido.');
        }

        $model = new CotizacionModel();
        $id = $model->insert([
            'nombre_colegio'       => $datos['nombre_colegio'],
            'nombre_seccion'       => $datos['nombre_seccion'] !== '' ? $datos['nombre_seccion'] : null,
            'persona_encargada'    => $datos['persona_encargada'],
            'telefono_contacto'    => $datos['telefono_contacto'],
            'cantidad_estudiantes' => $cantidad,
            'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario'      => $precioUnitario,
            'total_seccion'        => $total,
            'estado'               => CotizacionModel::ESTADO_APROBADA_ADMIN,
            'aprobado_por'         => (int) session()->get('id_usuario'),
        ]);

        if (! $id) {
            log_message('error', 'Failed to create a manual quote: ' . json_encode($model->errors()));

            return redirect()->back()->withInput()->with('error', 'No se pudo crear la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
            ->with('mensaje', 'Cotización creada. Ya puedes pasarla a vigente o a contrato.');
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

        $cotizacion['servicios'] = json_decode($cotizacion['servicios_json'], true);
        if (! is_array($cotizacion['servicios'])) {
            log_message('error', 'Invalid service data in quote #' . $id);

            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pudieron leer los servicios de la cotización.');
        }
        $contrato = db_connect()->table('contratos')
            ->select('id')
            ->where('cotizacion_id', $id)
            ->get()
            ->getRowArray();

        return view('admin/cotizaciones/detalle', [
            'cotizacion' => $cotizacion,
            'contratoId' => $contrato['id'] ?? null,
        ]);
    }

    public function actualizarPrecios(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede definir los precios de cotización.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! in_array($cotizacion['estado'], [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN], true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pueden actualizar los precios de esta cotización.');
        }

        $servicios = json_decode($cotizacion['servicios_json'], true);
        $precios = $this->request->getPost('precios');
        if (! is_array($servicios) || $servicios === [] || ! is_array($precios)) {
            return redirect()->back()->withInput()->with('error', 'No se pudieron leer los servicios y precios ingresados.');
        }

        $precioUnitario = 0.0;
        foreach ($servicios as $indice => &$servicio) {
            $precio = $precios[$indice] ?? null;
            if (! is_array($servicio) || ! is_numeric($precio) || (float) $precio < 0 || (float) $precio > 9999999999.99) {
                unset($servicio);

                return redirect()->back()->withInput()->with('error', 'Ingresa un precio válido para cada servicio.');
            }
            $servicio['precio_unitario'] = round((float) $precio, 2);
            $precioUnitario += $servicio['precio_unitario'];
        }
        unset($servicio);

        $total = round($precioUnitario * (int) $cotizacion['cantidad_estudiantes'], 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total de la cotización supera el máximo permitido.');
        }

        if (! $model->update($id, [
            'servicios_json'  => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario' => $precioUnitario,
            'total_seccion'   => $total,
        ])) {
            log_message('error', 'Failed to update prices for quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->back()->with('error', 'No se pudieron guardar los precios de la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('mensaje', 'Precios de cotización actualizados.');
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
            $guardada = (new \App\Models\SolicitudAdminModel())->insert([
                'entidad'        => 'cotizacion',
                'entidad_id'     => $id,
                'accion'         => 'eliminar',
                'estado'         => 'pendiente',
                'solicitado_por' => session()->get('id_usuario'),
            ]);
            if (! $guardada) {
                return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pudo solicitar la eliminación.');
            }

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
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se puede activar una cotización pendiente de vigencia.');
        }
        if ((float) $cotizacion['total_seccion'] <= 0 || (float) $cotizacion['total_seccion'] > 99999999.99) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Configura los precios de cotización antes de activar esta cotización.');
        }

        $diasVigencia = filter_var($this->request->getPost('dias_vigencia'), FILTER_VALIDATE_INT);
        if ($diasVigencia === false || $diasVigencia < 1 || $diasVigencia > 365) {
            return redirect()->back()->withInput()->with('error', 'La vigencia debe ser de 1 a 365 días.');
        }

        $venceEn = (new \DateTimeImmutable('today'))->modify('+' . $diasVigencia . ' days')->format('Y-m-d 23:59:59');
        if (! $model->update($id, [
            'estado'        => CotizacionModel::ESTADO_PENDIENTE,
            'dias_vigencia' => $diasVigencia,
            'vence_en'      => $venceEn,
            'aprobado_por'  => (int) session()->get('id_usuario'),
        ])) {
            log_message('error', 'Failed to activate quote #' . $id . ': ' . json_encode($model->errors()));

            return redirect()->back()->with('error', 'No se pudo activar la vigencia de la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/vigentes'))
            ->with('mensaje', 'Cotización vigente hasta ' . date('d/m/Y', strtotime($venceEn)) . '.');
    }

    public function pasarAContrato(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede pasar una cotización a contrato.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la solicitud.');
        }

        $db = db_connect();
        $contratoExistente = $db->table('contratos')->select('id')->where('cotizacion_id', $id)->get()->getRowArray();
        if ($contratoExistente !== null) {
            return redirect()->to(base_url('admin/contratos/' . $contratoExistente['id']))
                ->with('mensaje', 'Esta cotización ya tiene un contrato asociado.');
        }

        $servicios = json_decode($cotizacion['servicios_json'], true);
        if (! is_array($servicios) || $servicios === []) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'La cotización no tiene servicios válidos para crear el contrato.');
        }

        $db->transBegin();
        try {
            $institucionId = $this->obtenerInstitucionId($db, $cotizacion);
            $clienteId = $this->obtenerClienteId($db, $cotizacion);
            $contratoInsertado = $db->table('contratos')->insert([
                'numero_contrato'      => 'PP-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                'cotizacion_id'        => $id,
                'institucion_id'       => $institucionId,
                'cliente_id'           => $clienteId,
                'nombre_colegio'       => $cotizacion['nombre_colegio'],
                'nombre_seccion'       => $cotizacion['nombre_seccion'],
                'persona_encargada'    => $cotizacion['persona_encargada'],
                'telefono_contacto'    => $cotizacion['telefono_contacto'],
                'aula'                 => $cotizacion['nombre_seccion'],
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
            if (! $contratoInsertado) {
                throw new \RuntimeException('The contract record could not be saved.');
            }
            $contratoId = (int) $db->insertID();
            if ($contratoId < 1 || ! $model->update($id, ['estado' => CotizacionModel::ESTADO_APROBADO])) {
                throw new \RuntimeException('The contract or source quote could not be saved.');
            }
            if ($db->transStatus() === false) {
                throw new \RuntimeException('The database transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to create contract from quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'No se pudo crear el contrato.');
        }

        return redirect()->to(base_url('admin/contratos/' . $contratoId))
            ->with('mensaje', 'Contrato creado con los datos de la solicitud. Completa sus datos formales y programación.');
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

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'No se pudo guardar el estado del PDF.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="cotizacion-' . $id . '.pdf"')
            ->setBody($contenidoPdf);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogoServicios(): array
    {
        return (new ServicioModel())
            ->select('id, nombre_item, descripcion, precio_interno')
            ->orderBy('nombre_item', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array{servicio_id: int, nombre: string, descripcion: string, precio_unitario: float}>
     */
    private function serviciosSeleccionados($input, mixed $preciosInput): array
    {
        if (! is_array($input)) {
            return [];
        }

        $ids = [];
        foreach ($input as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                return [];
            }
            $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        if ($ids === [] || ! is_array($preciosInput)) {
            return [];
        }

        $serviciosCatalogo = (new ServicioModel())
            ->select('id, nombre_item, descripcion')
            ->whereIn('id', $ids)
            ->findAll();
        if (count($serviciosCatalogo) !== count($ids)) {
            return [];
        }

        $servicios = [];
        foreach ($serviciosCatalogo as $servicio) {
            $precio = $preciosInput[$servicio['id']] ?? null;
            if (! is_numeric($precio) || (float) $precio < 0 || (float) $precio > 9999999999.99) {
                return [];
            }
            $servicios[] = [
                'servicio_id'     => (int) $servicio['id'],
                'nombre'          => (string) $servicio['nombre_item'],
                'descripcion'     => (string) ($servicio['descripcion'] ?? ''),
                'precio_unitario' => round((float) $precio, 2),
            ];
        }

        return $servicios;
    }

    private function postString(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array<string, mixed> $cotizacion
     */
    private function obtenerInstitucionId(\CodeIgniter\Database\BaseConnection $db, array $cotizacion): int
    {
        if (! $db->tableExists('instituciones')) {
            return 1;
        }

        $nombreField = $db->fieldExists('nombre_institucion', 'instituciones')
            ? 'nombre_institucion'
            : ($db->fieldExists('nombre', 'instituciones') ? 'nombre' : null);
        if ($nombreField === null) {
            throw new \RuntimeException('The institutions table has no supported name field.');
        }

        $existing = $db->table('instituciones')
            ->select('id')
            ->where($nombreField, $cotizacion['nombre_colegio'])
            ->get()
            ->getRowArray();
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $record = [$nombreField => $cotizacion['nombre_colegio']];
        if ($db->fieldExists('telefono', 'instituciones')) {
            $record['telefono'] = $cotizacion['telefono_contacto'];
        }
        if ($db->fieldExists('creado_en', 'instituciones')) {
            $record['creado_en'] = date('Y-m-d H:i:s');
        }
        if (! $db->table('instituciones')->insert($record)) {
            throw new \RuntimeException('The institution record could not be saved.');
        }

        return (int) $db->insertID();
    }

    /**
     * @param array<string, mixed> $cotizacion
     */
    private function obtenerClienteId(\CodeIgniter\Database\BaseConnection $db, array $cotizacion): int
    {
        if (! $db->tableExists('clientes')) {
            return 1;
        }

        $nombreField = $db->fieldExists('nombre', 'clientes') ? 'nombre' : null;
        if ($nombreField === null) {
            throw new \RuntimeException('The clients table has no supported name field.');
        }
        $telefonoField = $db->fieldExists('celular', 'clientes')
            ? 'celular'
            : ($db->fieldExists('telefono', 'clientes') ? 'telefono' : null);
        $nombre = mb_substr((string) $cotizacion['persona_encargada'], 0, 100);
        $telefono = mb_substr((string) $cotizacion['telefono_contacto'], 0, 20);

        $query = $db->table('clientes')->select('id')->where($nombreField, $nombre);
        if ($telefonoField !== null) {
            $query->where($telefonoField, $telefono);
        }
        $existing = $query->get()->getRowArray();
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $record = [$nombreField => $nombre];
        if ($telefonoField !== null) {
            $record[$telefonoField] = $telefono;
        }
        if ($db->fieldExists('creado_en', 'clientes')) {
            $record['creado_en'] = date('Y-m-d H:i:s');
        }
        if (! $db->table('clientes')->insert($record)) {
            throw new \RuntimeException('The client record could not be saved.');
        }

        return (int) $db->insertID();
    }
}
