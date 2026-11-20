<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Libraries\CatalogoServicios;
use App\Libraries\DocumentoCodigo;
use App\Models\CotizacionModel;
use App\Models\ServicioModel;
use App\Models\SolicitudAdminModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class CotizacionController extends BaseController
{
    public function index()
    {
        return $this->listar(false);
    }

    public function vigentes()
    {
        return $this->listar(true);
    }

    private function listar(bool $soloVigentes)
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return $this->sinPermiso('No tienes permisos para ver cotizaciones.');
        }

        $db = db_connect();
        $query = $db->table('solicitudes_cotizacion s')
            ->select('s.*, c.id AS contrato_id, c.codigo AS contrato_codigo, c.estado_contrato')
            ->join('contratos c', 'c.cotizacion_id = s.id', 'left')
            ->where('s.estado !=', CotizacionModel::ESTADO_REEMPLAZADA)
            ->groupStart()
                ->where('s.codigo_cotizacion', null)
                ->orWhere('s.version = (SELECT MAX(v.version) FROM solicitudes_cotizacion v WHERE v.codigo_cotizacion = s.codigo_cotizacion)', null, false)
            ->groupEnd();

        if ($soloVigentes) {
            $query->groupStart()
                ->where('s.estado', CotizacionModel::ESTADO_ACEPTADA)
                ->orGroupStart()
                    ->where('s.estado', CotizacionModel::ESTADO_ENVIADA)
                    ->groupStart()
                        ->where('s.vence_en', null)
                        ->orWhere('s.vence_en >=', date('Y-m-d H:i:s'))
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();
        }

        $texto = trim((string) ($this->request->getGet('q') ?? ''));
        if ($texto !== '') {
            $query->groupStart()
                ->like('s.nombre_colegio', $texto)
                ->orLike('s.persona_encargada', $texto)
                ->orLike('s.telefono_contacto', $texto)
                ->orLike('s.codigo_solicitud', $texto)
                ->orLike('s.codigo_cotizacion', $texto)
            ->groupEnd();
        }

        $filtroEstado = (string) ($this->request->getGet('estado') ?? '');
        $this->aplicarFiltroEstado($query, $filtroEstado, 's');

        $total = (int) $query->countAllResults(false);
        $porPagina = 20;
        $pagina = max(1, (int) ($this->request->getGet('pagina') ?? 1));
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);

        $query->orderBy('s.created_at', 'DESC')->orderBy('s.id', 'DESC')
            ->limit($porPagina, ($pagina - 1) * $porPagina);
        $cotizaciones = $query->get()->getResultArray();
        foreach ($cotizaciones as &$cotizacion) {
            $cotizacion['servicios'] = json_decode((string) $cotizacion['servicios_json'], true) ?: [];
            $cotizacion['estado_efectivo'] = CotizacionModel::estadoEfectivo($cotizacion);
            $cotizacion['codigo_visible'] = CotizacionModel::codigoVisible($cotizacion);
            $cotizacion['es_formal'] = CotizacionModel::esCotizacionFormal($cotizacion);
        }
        unset($cotizacion);

        return view('admin/cotizaciones/index', [
            'cotizaciones' => $cotizaciones,
            'vigentes'     => $soloVigentes,
            'puedeCrear'   => AdminAccess::isAdmin(),
            'texto'        => $texto,
            'filtroEstado' => $filtroEstado,
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
        ]);
    }

    public function nueva()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear cotizaciones directamente.');
        }

        return view('admin/cotizaciones/nueva', [
            'serviciosCatalogo' => $this->catalogoServicios(),
            'duplicada' => null,
            'siguienteVersion' => 1,
        ]);
    }

    public function duplicar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede duplicar cotizaciones.');
        }

        $cotizacion = (new CotizacionModel())->find($id);
        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la cotización que deseas duplicar.');
        }
        if (! CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Primero genera la cotización formal y define sus precios.');
        }
        if ($cotizacion['estado'] === CotizacionModel::ESTADO_REEMPLAZADA
            || $cotizacion['estado'] === CotizacionModel::ESTADO_CONVERTIDA) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo puedes crear una versión nueva a partir de la cotización vigente.');
        }
        if ($this->contratoDeCodigo((string) $cotizacion['codigo_cotizacion']) !== null) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'La cotización ya generó un contrato; no se pueden crear nuevas versiones.');
        }

        $servicios = json_decode((string) $cotizacion['servicios_json'], true);
        if (! is_array($servicios)) {
            log_message('error', 'Invalid service data in quote #' . $id . ' while opening clone form.');

            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pudieron leer los servicios de la cotización original.');
        }

        $cotizacion['servicios'] = $servicios;

        return view('admin/cotizaciones/nueva', [
            'serviciosCatalogo' => $this->catalogoServicios(),
            'duplicada' => $cotizacion,
            'siguienteVersion' => $this->siguienteVersion((string) $cotizacion['codigo_cotizacion']),
        ]);
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
            'cantidad_estudiantes' => 'required|integer|greater_than[0]|less_than_equal_to[999]',
            'persona_encargada'    => 'required|string|max_length[150]',
            'telefono_contacto'    => 'required|string|exact_length[9]',
        ]);
        if (! $validation->run($datos) || ! preg_match('/^[0-9]{9}$/', $datos['telefono_contacto'])) {
            return redirect()->back()->withInput()->with('error', 'Revisa la institución, promoción, cantidad, persona encargada y teléfono.');
        }

        $postDuplicarDe = $this->request->getPost('duplicar_de');
        $duplicarDe = filter_var($postDuplicarDe, FILTER_VALIDATE_INT);
        if ($postDuplicarDe !== null && $postDuplicarDe !== ''
            && ($duplicarDe === false || $duplicarDe < 1)) {
            return redirect()->back()->withInput()->with('error', 'La cotización de origen no es válida.');
        }

        $origen = null;
        $codigoCotizacion = null;
        $rootId = null;
        if ($duplicarDe !== false && $duplicarDe > 0) {
            $origen = (new CotizacionModel())->find($duplicarDe);
            if ($origen === null || $origen['estado'] === CotizacionModel::ESTADO_REEMPLAZADA) {
                return redirect()->back()->withInput()->with('error', 'Solo puedes crear una versión nueva a partir de la cotización vigente.');
            }
            if (! CotizacionModel::esCotizacionFormal($origen)) {
                return redirect()->back()->withInput()->with('error', 'Primero genera la cotización formal desde la solicitud.');
            }
            if ($this->contratoDeCodigo((string) $origen['codigo_cotizacion']) !== null) {
                return redirect()->back()->withInput()->with('error', 'La cotización ya tiene un contrato y no admite nuevas versiones.');
            }

            $serviciosOrigen = json_decode((string) $origen['servicios_json'], true);
            $servicios = (new CatalogoServicios())->clonados(
                $serviciosOrigen,
                $this->request->getPost('servicios'),
                $this->request->getPost('precios')
            );
            $codigoCotizacion = (string) $origen['codigo_cotizacion'];
            $rootId = (int) ($origen['cotizacion_origen_id'] ?? 0);
            $rootId = $rootId > 0 ? $rootId : (int) $origen['id'];
        } else {
            $servicios = (new CatalogoServicios())->paraCotizacion(
                $this->request->getPost('servicios'),
                $this->request->getPost('precios')
            );
        }
        if ($servicios === []) {
            return redirect()->back()->withInput()->with('error', 'Selecciona al menos un servicio con precio interno configurado; los precios de cotización no pueden superarlo.');
        }

        $cantidad = (int) $datos['cantidad_estudiantes'];
        $precioUnitario = array_sum(array_column($servicios, 'precio_unitario'));
        $total = round($precioUnitario * $cantidad, 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total de la cotización supera el máximo permitido.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            if ($codigoCotizacion === null) {
                $codigoCotizacion = DocumentoCodigo::siguiente(DocumentoCodigo::COT);
                $version = 1;
            } else {
                $version = $this->siguienteVersion($codigoCotizacion);
            }

            $registro = [
                'nombre_colegio'       => $datos['nombre_colegio'],
                'nombre_seccion'       => $datos['nombre_seccion'] !== '' ? $datos['nombre_seccion'] : null,
                'persona_encargada'    => $datos['persona_encargada'],
                'telefono_contacto'    => $datos['telefono_contacto'],
                'cantidad_estudiantes' => $cantidad,
                'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'precio_unitario'      => $precioUnitario,
                'total_seccion'        => $total,
                'estado'               => CotizacionModel::ESTADO_BORRADOR,
                'estado_revision'      => CotizacionModel::REVISION_REVISADA,
                'codigo_cotizacion'    => $codigoCotizacion,
                'version'              => $version,
                'aprobado_por'         => (int) session()->get('id_usuario'),
            ];
            if ($rootId !== null) {
                $registro['cotizacion_origen_id'] = $rootId;
            }

            $id = (int) (new CotizacionModel())->insert($registro);
            if ($id < 1) {
                throw new \RuntimeException('The new quote could not be saved.');
            }

            $this->registrarEvento($db, 'cotizacion_creada', $id, null, [
                'codigo'  => $codigoCotizacion,
                'version' => $version,
                'origen'  => $origen['id'] ?? null,
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('The quote transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to save quote version' . ($duplicarDe ? ' from #' . $duplicarDe : '') . ': ' . $exception->getMessage());

            return redirect()->back()->withInput()->with('error', 'No se pudo guardar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
            ->with('mensaje', $origen !== null
                ? 'Versión ' . $version . ' creada. Las versiones anteriores se conservan en el historial.'
                : 'Cotización ' . $codigoCotizacion . ' creada. Revisa sus datos y envíala al cliente.');
    }

    public function ver(int $id)
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return $this->sinPermiso('No tienes permisos para ver cotizaciones.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la solicitud.');
        }

        $cotizacion['servicios'] = json_decode((string) $cotizacion['servicios_json'], true);
        if (! is_array($cotizacion['servicios'])) {
            log_message('error', 'Invalid service data in quote #' . $id);

            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se pudieron leer los servicios de la cotización.');
        }

        $db = db_connect();
        $formal = CotizacionModel::esCotizacionFormal($cotizacion);
        $codigo = (string) ($cotizacion['codigo_cotizacion'] ?? '');
        $contrato = $formal ? $this->contratoDeCodigo($codigo, true) : null;

        $versiones = [];
        if ($formal) {
            $versiones = $db->table('solicitudes_cotizacion')
                ->where('codigo_cotizacion', $codigo)
                ->orderBy('version', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($versiones as &$version) {
                $version['servicios'] = json_decode((string) $version['servicios_json'], true) ?: [];
                $version['estado_efectivo'] = CotizacionModel::estadoEfectivo($version);
            }
            unset($version);
        }

        $usuarios = [];
        foreach ($db->table('usuarios u')
            ->select('u.id, u.usuario, p.nombre')
            ->join('personal p', 'p.id = u.personal_id', 'left')
            ->get()
            ->getResultArray() as $usuario) {
            $usuarios[(int) $usuario['id']] = trim((string) ($usuario['nombre'] ?: $usuario['usuario']));
        }
        $responsable = static function (array $fila) use ($usuarios): string {
            $id = (int) ($fila['aprobado_por'] ?? 0);
            if (isset($usuarios[$id])) {
                return $usuarios[$id];
            }
            $id = (int) ($fila['respondido_por'] ?? 0);

            return $usuarios[$id] ?? 'Sin responsable';
        };
        $cotizacion['responsable'] = $responsable($cotizacion);
        foreach ($versiones as &$version) {
            $version['responsable'] = $responsable($version);
        }
        unset($version);

        $ultimaVersionId = $versiones === [] ? (int) $cotizacion['id'] : (int) $versiones[array_key_last($versiones)]['id'];
        $esUltimaVersion = (int) $cotizacion['id'] === $ultimaVersionId;
        $estadoEfectivo = CotizacionModel::estadoEfectivo($cotizacion);
        $administrador = AdminAccess::isAdmin();

        return view('admin/cotizaciones/detalle', [
            'cotizacion'         => $cotizacion,
            'contrato'           => $contrato,
            'versiones'          => $versiones,
            'ultimaVersionId'    => $ultimaVersionId,
            'esUltimaVersion'    => $esUltimaVersion,
            'esSolicitud'        => ! $formal,
            'estadoEfectivo'     => $estadoEfectivo,
            'administrador'      => $administrador,
            'puedeEditarPrecios' => $administrador && ! $formal
                && (float) $cotizacion['precio_unitario'] <= 0
                && $cotizacion['servicios'] !== [],
            'puedeMarcarRevision' => $administrador && ! $formal
                && $cotizacion['estado_revision'] !== CotizacionModel::REVISION_REVISADA,
            'puedeCrearVersion'  => $administrador && $formal && $esUltimaVersion
                && $contrato === null
                && ! in_array($cotizacion['estado'], [CotizacionModel::ESTADO_REEMPLAZADA, CotizacionModel::ESTADO_CONVERTIDA], true),
            'puedeActivar'       => $administrador && $formal
                && $cotizacion['estado'] === CotizacionModel::ESTADO_BORRADOR
                && (float) $cotizacion['total_seccion'] > 0,
            'puedeResponder'     => $administrador && $formal && $esUltimaVersion
                && in_array($estadoEfectivo, [CotizacionModel::ESTADO_ENVIADA, CotizacionModel::ESTADO_VENCIDA], true),
            'puedePasarContrato' => $administrador && $formal
                && $cotizacion['estado'] === CotizacionModel::ESTADO_ACEPTADA
                && $contrato === null,
            'puedeDescargarPdf'  => $formal && (float) $cotizacion['total_seccion'] > 0,
            'puedeEliminar'      => $this->puedeEliminar($cotizacion, $formal, $codigo),
            'eventos'            => $this->eventosDe($db, $id, $contrato['id'] ?? null),
        ]);
    }

    public function actualizarPrecios(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede definir los precios de cotización.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null
            || CotizacionModel::esCotizacionFormal($cotizacion)
            || $cotizacion['estado'] !== CotizacionModel::ESTADO_BORRADOR
            || (float) $cotizacion['precio_unitario'] > 0) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Esta cotización ya fue generada. Usa una nueva versión para cambiar sus precios.');
        }

        $servicios = json_decode((string) $cotizacion['servicios_json'], true);
        $precios = $this->request->getPost('precios');
        if (! is_array($servicios) || $servicios === [] || ! is_array($precios)) {
            return redirect()->back()->withInput()->with('error', 'No se pudieron leer los servicios y precios ingresados.');
        }

        $precioUnitario = 0.0;
        foreach ($servicios as $indice => &$servicio) {
            $precio = $precios[$indice] ?? null;
            $precioInterno = is_array($servicio) && is_numeric($servicio['precio_interno'] ?? null)
                ? (float) $servicio['precio_interno']
                : null;
            if (! is_array($servicio)
                || ! is_numeric($precio)
                || (float) $precio <= 0
                || (float) $precio > 9999999999.99
                || ($precioInterno !== null && ((float) $precioInterno <= 0 || (float) $precio > $precioInterno))) {
                unset($servicio);

                return redirect()->back()->withInput()->with('error', 'Ingresa un precio válido que no supere el precio interno guardado en la cotización.');
            }
            $servicio['precio_unitario'] = round((float) $precio, 2);
            $precioUnitario += $servicio['precio_unitario'];
        }
        unset($servicio);

        $total = round($precioUnitario * (int) $cotizacion['cantidad_estudiantes'], 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El total de la cotización supera el máximo permitido.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $codigo = DocumentoCodigo::siguiente(DocumentoCodigo::COT);
            $actualizado = $db->table('solicitudes_cotizacion')
                ->where('id', $id)
                ->where('codigo_cotizacion', null)
                ->where('estado', CotizacionModel::ESTADO_BORRADOR)
                ->where('precio_unitario <=', 0)
                ->update([
                    'servicios_json'    => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'precio_unitario'   => $precioUnitario,
                    'total_seccion'     => $total,
                    'codigo_cotizacion' => $codigo,
                    'version'           => 1,
                    'estado'            => CotizacionModel::ESTADO_BORRADOR,
                    'estado_revision'   => CotizacionModel::REVISION_REVISADA,
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            if (! $actualizado || $db->affectedRows() !== 1) {
                throw new \RuntimeException('The quote was already priced by another request.');
            }
            $this->registrarEvento($db, 'cotizacion_generada', $id, null, ['codigo' => $codigo]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while pricing the quote.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to lock initial prices for quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'La cotización ya se guardó o no se pudo actualizar; no se modificaron sus precios.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
            ->with('mensaje', 'Cotización ' . $codigo . ' generada. Sus precios quedaron bloqueados; usa una nueva versión para cambios.');
    }

    public function marcarEnRevision(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para cambiar la revisión.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'La solicitud ya no está pendiente de revisión.');
        }
        if ($cotizacion['estado_revision'] === CotizacionModel::REVISION_REVISADA) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('mensaje', 'La solicitud ya fue revisada.');
        }

        $model->update($id, ['estado_revision' => CotizacionModel::REVISION_EN_REVISION]);
        $this->registrarEvento(db_connect(), 'revision_iniciada', $id, null, []);

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('mensaje', 'Solicitud marcada en revisión.');
    }

    public function eliminar(int $id)
    {
        if (! AdminAccess::isAdmin() && ! AdminAccess::can('cotizaciones.eliminar')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para solicitar la eliminación.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la cotización.');
        }
        if (! $this->puedeEliminar($cotizacion, CotizacionModel::esCotizacionFormal($cotizacion), (string) ($cotizacion['codigo_cotizacion'] ?? ''))) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Esta cotización tiene versiones o un contrato vinculado y debe conservarse para auditoría.');
        }

        if (! AdminAccess::isAdmin()) {
            $guardada = (new SolicitudAdminModel())->insert([
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
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede enviar la cotización.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Primero genera la cotización formal.');
        }
        if ($cotizacion['estado'] !== CotizacionModel::ESTADO_BORRADOR) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo se puede enviar una cotización en borrador.');
        }
        if ((float) $cotizacion['total_seccion'] <= 0 || (float) $cotizacion['total_seccion'] > 99999999.99) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Configura los precios de cotización antes de enviarla.');
        }

        $diasVigencia = filter_var($this->request->getPost('dias_vigencia'), FILTER_VALIDATE_INT);
        if ($diasVigencia === false || $diasVigencia < 1 || $diasVigencia > 365) {
            return redirect()->back()->withInput()->with('error', 'La vigencia debe ser de 1 a 365 días.');
        }

        $codigo = (string) $cotizacion['codigo_cotizacion'];
        if ($this->contratoDeCodigo($codigo) !== null) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'La cotización ya tiene un contrato asociado.');
        }

        $venceEn = (new \DateTimeImmutable('today'))->modify('+' . $diasVigencia . ' days')->format('Y-m-d 23:59:59');
        $db = db_connect();
        $db->transBegin();
        try {
            $db->table('solicitudes_cotizacion')
                ->where('id', $id)
                ->where('codigo_cotizacion', $codigo)
                ->where('estado', CotizacionModel::ESTADO_BORRADOR)
                ->update([
                    'estado'          => CotizacionModel::ESTADO_ENVIADA,
                    'dias_vigencia'   => $diasVigencia,
                    'vence_en'        => $venceEn,
                    'aprobado_por'    => (int) session()->get('id_usuario'),
                    'estado_revision' => CotizacionModel::REVISION_REVISADA,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('The quote changed while it was being sent.');
            }

            $db->table('solicitudes_cotizacion')
                ->where('codigo_cotizacion', $codigo)
                ->where('id !=', $id)
                ->where('estado !=', CotizacionModel::ESTADO_REEMPLAZADA)
                ->update([
                    'estado'     => CotizacionModel::ESTADO_REEMPLAZADA,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->registrarEvento($db, 'cotizacion_enviada', $id, null, [
                'codigo' => $codigo,
                'version' => (int) $cotizacion['version'],
                'vence_en' => $venceEn,
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while sending the quote.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to send quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->back()->with('error', 'No se pudo enviar la cotización.');
        }

        return redirect()->to(base_url('admin/cotizaciones/vigentes'))
            ->with('mensaje', 'Cotización ' . $codigo . ' enviada y vigente hasta ' . date('d/m/Y', strtotime($venceEn)) . '.');
    }

    public function aceptar(int $id)
    {
        return $this->responder($id, true);
    }

    public function rechazar(int $id)
    {
        return $this->responder($id, false);
    }

    private function responder(int $id, bool $aceptar)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede registrar la respuesta del cliente.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la cotización formal.');
        }
        $estadoEfectivo = CotizacionModel::estadoEfectivo($cotizacion);
        if (! in_array($estadoEfectivo, [CotizacionModel::ESTADO_ENVIADA, CotizacionModel::ESTADO_VENCIDA], true)) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Solo una cotización enviada puede registrarse como aceptada o rechazada.');
        }
        if ($cotizacion['cotizacion_origen_id'] !== null
            && $this->esVersionReemplazadaSegura((int) $cotizacion['id'])) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'No se puede responder una versión histórica.');
        }

        $motivo = $this->postString('motivo_decision');
        if (! $aceptar && (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 1000)) {
            return redirect()->back()->withInput()->with('error', 'Indica el motivo del rechazo (entre 5 y 1000 caracteres).');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $db->table('solicitudes_cotizacion')
                ->where('id', $id)
                ->where('codigo_cotizacion', (string) $cotizacion['codigo_cotizacion'])
                ->where('estado', CotizacionModel::ESTADO_ENVIADA)
                ->update([
                    'estado'          => $aceptar ? CotizacionModel::ESTADO_ACEPTADA : CotizacionModel::ESTADO_RECHAZADA,
                    'respondido_en'   => date('Y-m-d H:i:s'),
                    'respondido_por'  => (int) session()->get('id_usuario'),
                    'motivo_decision' => $aceptar ? null : $motivo,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('The quote was already answered by another request.');
            }

            $this->registrarEvento($db, $aceptar ? 'cotizacion_aceptada' : 'cotizacion_rechazada', $id, null, [
                'codigo' => $cotizacion['codigo_cotizacion'],
                'motivo' => $aceptar ? null : $motivo,
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while answering the quote.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to answer quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'La cotización ya fue respondida o no se pudo registrar la respuesta.');
        }

        return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
            ->with('mensaje', $aceptar ? 'Cotización aceptada. Ya puedes generar el contrato.' : 'Cotización rechazada.');
    }

    public function pasarAContrato(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'Solo el administrador puede crear el contrato.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No se encontró la cotización.');
        }
        if ($cotizacion['estado'] === CotizacionModel::ESTADO_REEMPLAZADA) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'No se puede crear un contrato desde una versión histórica.');
        }
        if ($cotizacion['estado'] !== CotizacionModel::ESTADO_ACEPTADA) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))
                ->with('error', 'Solo una cotización aceptada por el cliente puede convertirse en contrato.');
        }

        $existente = $this->contratoDeCodigo((string) $cotizacion['codigo_cotizacion']);
        if ($existente !== null) {
            return redirect()->to(base_url('admin/contratos/' . $existente['id']))
                ->with('mensaje', 'Esta cotización ya tiene un contrato asociado.');
        }

        $servicios = json_decode((string) $cotizacion['servicios_json'], true);
        if (! is_array($servicios) || $servicios === []) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'La cotización no tiene servicios válidos para crear el contrato.');
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $db->table('solicitudes_cotizacion')
                ->where('id', $id)
                ->where('codigo_cotizacion', (string) $cotizacion['codigo_cotizacion'])
                ->where('estado', CotizacionModel::ESTADO_ACEPTADA)
                ->update([
                    'estado'     => CotizacionModel::ESTADO_CONVERTIDA,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            if ($db->affectedRows() !== 1) {
                throw new \RuntimeException('The quote was converted by another request.');
            }

            $institucionId = $this->obtenerInstitucionId($db, $cotizacion);
            $clienteId = $this->obtenerClienteId($db, $cotizacion);
            $codigoContrato = DocumentoCodigo::siguiente(DocumentoCodigo::CON);
            $contratoInsertado = $db->table('contratos')->insert([
                'codigo'               => $codigoContrato,
                'numero_contrato'      => $codigoContrato,
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
            if ($contratoId < 1) {
                throw new \RuntimeException('The contract could not be saved.');
            }
            $this->registrarEvento($db, 'contrato_creado', $id, $contratoId, [
                'codigo_contrato' => $codigoContrato,
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('The database transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to create contract from quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'No se pudo crear el contrato. Verifica que no exista ya uno para esta cotización.');
        }

        return redirect()->to(base_url('admin/contratos/' . $contratoId))
            ->with('mensaje', 'Contrato ' . $codigoContrato . ' creado a partir de la cotización aceptada.');
    }

    public function descargarPdf(int $id)
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return $this->sinPermiso('No tienes permiso para descargar cotizaciones.');
        }

        $model = new CotizacionModel();
        $cotizacion = $model->find($id);
        if ($cotizacion === null || ! CotizacionModel::esCotizacionFormal($cotizacion)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'La solicitud todavía no tiene una cotización formal en PDF.');
        }
        if ((float) $cotizacion['total_seccion'] <= 0) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'La cotización no tiene precios definidos.');
        }

        try {
            $cotizacion['servicios'] = json_decode((string) $cotizacion['servicios_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            log_message('error', 'Invalid services in quote #' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'No se pudieron leer los servicios de la cotización.');
        }
        if (! is_array($cotizacion['servicios'])) {
            return redirect()->to(base_url('admin/cotizaciones/' . $id . '/ver'))->with('error', 'No se pudieron leer los servicios de la cotización.');
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('admin/cotizaciones/pdf', ['cotizacion' => $cotizacion]), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $contenidoPdf = $pdf->output();

        $model->update($id, ['pdf_generado_en' => date('Y-m-d H:i:s')]);

        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $cotizacion['codigo_cotizacion']);
        $version = (int) ($cotizacion['version'] ?? 1);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nombre . '-v' . $version . '.pdf"')
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

    private function siguienteVersion(string $codigo): int
    {
        $fila = db_connect()->table('solicitudes_cotizacion')
            ->selectMax('version', 'maxima')
            ->where('codigo_cotizacion', $codigo)
            ->get()
            ->getRowArray();

        return ((int) ($fila['maxima'] ?? 0)) + 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function contratoDeCodigo(string $codigo, bool $incluirEstado = false): ?array
    {
        if ($codigo === '') {
            return null;
        }

        $select = 'c.id, c.codigo, c.numero_contrato' . ($incluirEstado ? ', c.estado_contrato, c.monto_total, c.creado_en' : '');
        $fila = db_connect()->table('contratos c')
            ->select($select)
            ->join('solicitudes_cotizacion s', 's.id = c.cotizacion_id')
            ->where('s.codigo_cotizacion', $codigo)
            ->orderBy('c.id', 'ASC')
            ->get()
            ->getRowArray();

        return $fila ?: null;
    }

    /**
     * @param array<string, mixed> $cotizacion
     */
    private function puedeEliminar(array $cotizacion, bool $formal, string $codigo): bool
    {
        if (! AdminAccess::isAdmin() && ! AdminAccess::can('cotizaciones.eliminar')) {
            return false;
        }
        if ($cotizacion['estado'] !== CotizacionModel::ESTADO_BORRADOR) {
            return false;
        }
        if ($formal && $codigo !== '') {
            if ($this->contratoDeCodigo($codigo) !== null) {
                return false;
            }
            $versiones = db_connect()->table('solicitudes_cotizacion')
                ->where('codigo_cotizacion', $codigo)
                ->countAllResults();
            if ($versiones > 1) {
                return false;
            }
        } elseif ($cotizacion['cotizacion_origen_id'] !== null) {
            return false;
        }

        return true;
    }

    private function esVersionReemplazadaSegura(int $id): bool
    {
        $fila = db_connect()->table('solicitudes_cotizacion')->select('estado, version, codigo_cotizacion')
            ->where('id', $id)->get()->getRowArray();
        if ($fila === null) {
            return true;
        }
        $maxima = db_connect()->table('solicitudes_cotizacion')->selectMax('version', 'maxima')
            ->where('codigo_cotizacion', $fila['codigo_cotizacion'])->get()->getRowArray();

        return $fila['estado'] === CotizacionModel::ESTADO_REEMPLAZADA
            || (int) $fila['version'] < (int) ($maxima['maxima'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventosDe(\CodeIgniter\Database\BaseConnection $db, int $cotizacionId, ?int $contratoId): array
    {
        $query = $db->table('eventos_comerciales')
            ->groupStart()
                ->where('cotizacion_id', $cotizacionId);
        if ($contratoId !== null) {
            $query->orWhere('contrato_id', $contratoId);
        }
        $query->groupEnd();

        return $query->orderBy('creado_en', 'DESC')->orderBy('id', 'DESC')->limit(25)->get()->getResultArray();
    }

    /**
     * @param array<string, mixed> $detalle
     */
    private function registrarEvento(\CodeIgniter\Database\BaseConnection $db, string $tipo, ?int $cotizacionId, ?int $contratoId, array $detalle): void
    {
        $db->table('eventos_comerciales')->insert([
            'cotizacion_id' => $cotizacionId,
            'contrato_id'   => $contratoId,
            'tipo'          => $tipo,
            'detalle'       => $detalle === [] ? null : json_encode($detalle, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'usuario_id'    => (int) session()->get('id_usuario'),
            'creado_en'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function aplicarFiltroEstado($query, string $estado, string $alias): void
    {
        switch ($estado) {
            case 'sin_revisar':
                $query->where($alias . '.estado_revision', CotizacionModel::REVISION_SIN_REVISAR);
                break;
            case 'en_revision':
                $query->where($alias . '.estado_revision', CotizacionModel::REVISION_EN_REVISION);
                break;
            case 'revisadas':
                $query->where($alias . '.estado_revision', CotizacionModel::REVISION_REVISADA);
                break;
            case 'aceptadas':
                $query->whereIn($alias . '.estado', [CotizacionModel::ESTADO_ACEPTADA, CotizacionModel::ESTADO_CONVERTIDA]);
                break;
            case 'rechazadas':
                $query->where($alias . '.estado', CotizacionModel::ESTADO_RECHAZADA);
                break;
            case 'borradores':
                $query->where($alias . '.estado', CotizacionModel::ESTADO_BORRADOR);
                break;
            case 'convertidas':
                $query->where($alias . '.estado', CotizacionModel::ESTADO_CONVERTIDA);
                break;
            case 'vencidas':
                $query->where($alias . '.estado', CotizacionModel::ESTADO_ENVIADA)
                    ->where($alias . '.vence_en IS NOT NULL', null, false)
                    ->where($alias . '.vence_en <', date('Y-m-d H:i:s'));
                break;
            case 'vigentes':
                $query->where($alias . '.estado', CotizacionModel::ESTADO_ENVIADA)
                    ->groupStart()
                        ->where($alias . '.vence_en', null)
                        ->orWhere($alias . '.vence_en >=', date('Y-m-d H:i:s'))
                    ->groupEnd();
                break;
        }
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
