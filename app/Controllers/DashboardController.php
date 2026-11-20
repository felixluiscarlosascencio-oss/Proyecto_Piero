<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\CotizacionModel;
use DateTimeImmutable;

class DashboardController extends BaseController
{
    public function index()
    {
        return $this->mostrar(false);
    }

    public function reportes()
    {
        return $this->mostrar(true);
    }

    private function mostrar(bool $esReporte)
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return $this->sinPermiso('No tienes permiso para ver el dashboard.');
        }

        $db = db_connect();
        [$inicio, $fin, $periodo, $etiquetaPeriodo] = $this->resolverPeriodo();
        $diasPeriodo = (int) $inicio->diff($fin)->days + 1;
        $finAnterior = $inicio->modify('-1 day')->setTime(23, 59, 59);
        $inicioAnterior = $finAnterior->modify('-' . ($diasPeriodo - 1) . ' days')->setTime(0, 0, 0);

        $documentosPeriodo = (int) $db->table('solicitudes_cotizacion')
            ->where('created_at >=', $inicio->format('Y-m-d H:i:s'))
            ->where('created_at <=', $fin->format('Y-m-d H:i:s'))
            ->countAllResults();
        $documentosAnterior = (int) $db->table('solicitudes_cotizacion')
            ->where('created_at >=', $inicioAnterior->format('Y-m-d H:i:s'))
            ->where('created_at <=', $finAnterior->format('Y-m-d H:i:s'))
            ->countAllResults();
        $variacion = $documentosAnterior === 0
            ? ($documentosPeriodo > 0 ? 100.0 : 0.0)
            : round((($documentosPeriodo - $documentosAnterior) / $documentosAnterior) * 100, 1);

        $solicitudesPendientes = (int) $db->table('solicitudes_cotizacion')
            ->where('codigo_cotizacion', null)
            ->whereIn('estado_revision', [
                CotizacionModel::REVISION_SIN_REVISAR,
                CotizacionModel::REVISION_EN_REVISION,
            ])
            ->countAllResults();

        $cotizacionesVigentes = (int) $db->table('solicitudes_cotizacion')
            ->where('estado', CotizacionModel::ESTADO_ENVIADA)
            ->groupStart()
                ->where('vence_en', null)
                ->orWhere('vence_en >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->countAllResults();

        $contratos = $db->table('contratos')->select('id, monto_total, estado_contrato')->get()->getResultArray();
        $ajustesPorContrato = [];
        if ($db->tableExists('contrato_ajustes')) {
            foreach ($db->table('contrato_ajustes')
                ->select('contrato_id, SUM(impacto_financiero) AS impacto')
                ->groupBy('contrato_id')
                ->get()
                ->getResultArray() as $ajuste) {
                $ajustesPorContrato[(int) $ajuste['contrato_id']] = (float) $ajuste['impacto'];
            }
        }
        $contratosActivos = 0;
        $montoContratado = 0.0;
        foreach ($contratos as $contrato) {
            $estadoContrato = mb_strtolower(trim((string) ($contrato['estado_contrato'] ?? 'activo')));
            if (in_array($estadoContrato, ['cerrado', 'cerrada', 'finalizado', 'finalizada', 'anulado', 'anulada'], true)) {
                continue;
            }
            $contratosActivos++;
            $montoContratado += (float) $contrato['monto_total'] + ($ajustesPorContrato[(int) $contrato['id']] ?? 0.0);
        }

        $estados = [
            'solicitudes' => (int) $db->table('solicitudes_cotizacion')
                ->where('codigo_cotizacion', null)->countAllResults(),
            'borradores' => (int) $db->table('solicitudes_cotizacion')
                ->where('estado', CotizacionModel::ESTADO_BORRADOR)->countAllResults(),
            'enviadas' => $cotizacionesVigentes,
            'aceptadas' => (int) $db->table('solicitudes_cotizacion')
                ->whereIn('estado', [CotizacionModel::ESTADO_ACEPTADA, CotizacionModel::ESTADO_CONVERTIDA])
                ->countAllResults(),
            'rechazadas' => (int) $db->table('solicitudes_cotizacion')
                ->where('estado', CotizacionModel::ESTADO_RECHAZADA)->countAllResults(),
        ];

        $grafico = $this->serieDiaria($inicio, $fin);
        $serviciosSolicitados = $this->serviciosDelPeriodo($inicio, $fin);

        [$tabla, $filtrosTabla, $totalTabla, $paginaTabla, $totalPaginasTabla] = $this->tablaComercial($db);

        return view('admin/dashboard/index', [
            'esReporte' => $esReporte,
            'periodo' => $periodo,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'desde' => $inicio->format('Y-m-d'),
            'hasta' => $fin->format('Y-m-d'),
            'documentosPeriodo' => $documentosPeriodo,
            'variacion' => $variacion,
            'solicitudesPendientes' => $solicitudesPendientes,
            'cotizacionesVigentes' => $cotizacionesVigentes,
            'contratosActivos' => $contratosActivos,
            'montoContratado' => $montoContratado,
            'grafico' => $grafico,
            'graficoEstados' => $estados,
            'serviciosSolicitados' => $serviciosSolicitados,
            'tabla' => $tabla,
            'filtrosTabla' => $filtrosTabla,
            'totalTabla' => $totalTabla,
            'paginaTabla' => $paginaTabla,
            'totalPaginasTabla' => $totalPaginasTabla,
            'puedeVerContratos' => AdminAccess::can('contratos.ver'),
        ]);
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable, 2: string, 3: string}
     */
    private function resolverPeriodo(): array
    {
        $hoy = new DateTimeImmutable('today');
        $periodo = (string) ($this->request->getGet('periodo') ?? '30d');
        $desde = (string) ($this->request->getGet('desde') ?? '');
        $hasta = (string) ($this->request->getGet('hasta') ?? '');

        $valida = static function (string $fecha): ?DateTimeImmutable {
            $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

            return $objeto instanceof DateTimeImmutable && $objeto->format('Y-m-d') === $fecha ? $objeto : null;
        };

        switch ($periodo) {
            case '7d':
                return [$hoy->modify('-6 days'), $hoy->setTime(23, 59, 59), '7d', 'Últimos 7 días'];
            case 'mes':
                return [$hoy->modify('first day of this month'), $hoy->setTime(23, 59, 59), 'mes', 'Mes actual'];
            case 'mes_anterior':
                $inicio = $hoy->modify('first day of last month');
                $fin = $hoy->modify('last day of last month')->setTime(23, 59, 59);

                return [$inicio, $fin, 'mes_anterior', 'Mes anterior'];
            case 'custom':
                $inicio = $valida($desde);
                $fin = $valida($hasta);
                if ($inicio !== null && $fin !== null && $inicio <= $fin) {
                    if ($inicio->diff($fin)->days > 366) {
                        $inicio = $fin->modify('-366 days');
                    }

                    return [$inicio->setTime(0, 0, 0), $fin->setTime(23, 59, 59), 'custom', 'Del ' . $inicio->format('d/m/Y') . ' al ' . $fin->format('d/m/Y')];
                }

                return [$hoy->modify('-29 days'), $hoy->setTime(23, 59, 59), '30d', 'Últimos 30 días'];
            case '30d':
            default:
                return [$hoy->modify('-29 days'), $hoy->setTime(23, 59, 59), '30d', 'Últimos 30 días'];
        }
    }

    /**
     * @return list<array{label: string, solicitudes: int, cotizaciones: int, total: int}>
     */
    private function serieDiaria(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        $conteo = [];
        foreach (db_connect()->table('solicitudes_cotizacion')
            ->select('created_at, codigo_cotizacion')
            ->where('created_at >=', $inicio->format('Y-m-d H:i:s'))
            ->where('created_at <=', $fin->format('Y-m-d H:i:s'))
            ->get()
            ->getResultArray() as $fila) {
            $fecha = substr((string) $fila['created_at'], 0, 10);
            if ($fecha === '') {
                continue;
            }
            if (! isset($conteo[$fecha])) {
                $conteo[$fecha] = ['solicitudes' => 0, 'cotizaciones' => 0];
            }
            if (empty($fila['codigo_cotizacion'])) {
                $conteo[$fecha]['solicitudes']++;
            } else {
                $conteo[$fecha]['cotizaciones']++;
            }
        }

        $serie = [];
        $cursor = $inicio->setTime(0, 0, 0);
        $ultimo = $fin->setTime(0, 0, 0);
        while ($cursor <= $ultimo) {
            $clave = $cursor->format('Y-m-d');
            $solicitudes = $conteo[$clave]['solicitudes'] ?? 0;
            $cotizaciones = $conteo[$clave]['cotizaciones'] ?? 0;
            $serie[] = [
                'label' => $cursor->format('d/m'),
                'solicitudes' => $solicitudes,
                'cotizaciones' => $cotizaciones,
                'total' => $solicitudes + $cotizaciones,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $serie;
    }

    /**
     * @return list<array{nombre: string, total: int}>
     */
    private function serviciosDelPeriodo(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        $servicios = [];
        foreach (db_connect()->table('solicitudes_cotizacion')
            ->select('servicios_json')
            ->where('created_at >=', $inicio->format('Y-m-d H:i:s'))
            ->where('created_at <=', $fin->format('Y-m-d H:i:s'))
            ->get()
            ->getResultArray() as $fila) {
            $items = json_decode((string) ($fila['servicios_json'] ?? ''), true);
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                $nombre = is_array($item) && is_string($item['nombre'] ?? null) ? trim($item['nombre']) : '';
                if ($nombre !== '') {
                    $servicios[$nombre] = ($servicios[$nombre] ?? 0) + 1;
                }
            }
        }
        arsort($servicios);

        $resultado = [];
        foreach (array_slice($servicios, 0, 6, true) as $nombre => $total) {
            $resultado[] = ['nombre' => $nombre, 'total' => $total];
        }

        return $resultado;
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: array<string, string>, 2: int, 3: int, 4: int}
     */
    private function tablaComercial(\CodeIgniter\Database\BaseConnection $db): array
    {
        $filtros = [
            'q' => trim((string) ($this->request->getGet('tq') ?? '')),
            'estado' => (string) ($this->request->getGet('testado') ?? ''),
            'revision' => (string) ($this->request->getGet('trevision') ?? ''),
            'tipo' => (string) ($this->request->getGet('ttipo') ?? ''),
            'desde' => (string) ($this->request->getGet('tdesde') ?? ''),
            'hasta' => (string) ($this->request->getGet('thasta') ?? ''),
            'orden' => (string) ($this->request->getGet('torden') ?? 'fecha'),
            'dir' => strtolower((string) ($this->request->getGet('tdir') ?? 'desc')) === 'asc' ? 'asc' : 'desc',
        ];
        foreach (['desde', 'hasta'] as $campo) {
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $filtros[$campo]);
            if (! $fecha instanceof DateTimeImmutable || $fecha->format('Y-m-d') !== $filtros[$campo]) {
                $filtros[$campo] = '';
            }
        }

        $constructor = function () use ($db, $filtros) {
            $query = $db->table('solicitudes_cotizacion s')
                ->select('s.*, c.id AS contrato_id, c.codigo AS contrato_codigo')
                ->join('contratos c', 'c.cotizacion_id = s.id', 'left')
                ->where('s.estado !=', CotizacionModel::ESTADO_REEMPLAZADA)
                ->groupStart()
                    ->where('s.codigo_cotizacion', null)
                    ->orWhere('s.version = (SELECT MAX(v.version) FROM solicitudes_cotizacion v WHERE v.codigo_cotizacion = s.codigo_cotizacion)', null, false)
                ->groupEnd();

            if ($filtros['q'] !== '') {
                $texto = $filtros['q'];
                $query->groupStart()
                    ->like('s.nombre_colegio', $texto)
                    ->orLike('s.persona_encargada', $texto)
                    ->orLike('s.telefono_contacto', $texto)
                    ->orLike('s.codigo_solicitud', $texto)
                    ->orLike('s.codigo_cotizacion', $texto)
                ->groupEnd();
            }
            if ($filtros['revision'] !== '') {
                $query->where('s.estado_revision', strtoupper($filtros['revision']));
            }
            if ($filtros['tipo'] === 'solicitud') {
                $query->where('s.codigo_cotizacion', null);
            } elseif ($filtros['tipo'] === 'cotizacion') {
                $query->where('s.codigo_cotizacion IS NOT NULL', null, false)
                    ->where('c.id', null);
            } elseif ($filtros['tipo'] === 'contrato') {
                $query->where('c.id IS NOT NULL', null, false);
            }
            if ($filtros['desde'] !== '') {
                $query->where('s.created_at >=', $filtros['desde'] . ' 00:00:00');
            }
            if ($filtros['hasta'] !== '') {
                $query->where('s.created_at <=', $filtros['hasta'] . ' 23:59:59');
            }

            switch ($filtros['estado']) {
                case 'borrador':
                    $query->where('s.estado', CotizacionModel::ESTADO_BORRADOR);
                    break;
                case 'enviada':
                    $query->where('s.estado', CotizacionModel::ESTADO_ENVIADA)
                        ->groupStart()
                            ->where('s.vence_en', null)
                            ->orWhere('s.vence_en >=', date('Y-m-d H:i:s'))
                        ->groupEnd();
                    break;
                case 'vencida':
                    $query->where('s.estado', CotizacionModel::ESTADO_ENVIADA)
                        ->where('s.vence_en IS NOT NULL', null, false)
                        ->where('s.vence_en <', date('Y-m-d H:i:s'));
                    break;
                case 'aceptada':
                    $query->whereIn('s.estado', [CotizacionModel::ESTADO_ACEPTADA, CotizacionModel::ESTADO_CONVERTIDA]);
                    break;
                case 'rechazada':
                    $query->where('s.estado', CotizacionModel::ESTADO_RECHAZADA);
                    break;
            }

            $columnas = [
                'fecha' => 's.created_at',
                'institucion' => 's.nombre_colegio',
                'total' => 's.total_seccion',
                'codigo' => 's.codigo_cotizacion',
            ];
            $columna = $columnas[$filtros['orden']] ?? $columnas['fecha'];
            $query->orderBy($columna, $filtros['dir'])->orderBy('s.id', $filtros['dir']);

            return $query;
        };

        $total = (int) $constructor()->countAllResults();
        $porPagina = 8;
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = min(max(1, (int) ($this->request->getGet('tpagina') ?? 1)), $totalPaginas);
        $filas = $constructor()->limit($porPagina, ($pagina - 1) * $porPagina)->get()->getResultArray();
        foreach ($filas as &$fila) {
            $fila['estado_efectivo'] = CotizacionModel::estadoEfectivo($fila);
            $fila['es_formal'] = CotizacionModel::esCotizacionFormal($fila);
            $fila['codigo_visible'] = CotizacionModel::codigoVisible($fila);
        }
        unset($fila);

        return [$filas, $filtros, $total, $pagina, $totalPaginas];
    }

    public function buscar()
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return $this->sinPermiso('No tienes permiso para buscar registros.');
        }

        $consulta = $this->request->getGet('q');
        $termino = is_string($consulta) ? trim($consulta) : '';
        $cotizaciones = [];
        $servicios = [];
        $contratos = [];
        $usuarios = [];
        if ($termino !== '') {
            $cotizaciones = db_connect()->table('solicitudes_cotizacion')
                ->groupStart()
                    ->like('nombre_colegio', $termino)
                    ->orLike('persona_encargada', $termino)
                    ->orLike('telefono_contacto', $termino)
                    ->orLike('codigo_solicitud', $termino)
                    ->orLike('codigo_cotizacion', $termino)
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->limit(20)
                ->get()
                ->getResultArray();
            $servicios = db_connect()->table('servicios')
                ->like('nombre_item', $termino)
                ->orderBy('nombre_item', 'ASC')
                ->limit(10)
                ->get()
                ->getResultArray();
            if (AdminAccess::can('contratos.ver')) {
                $contratos = db_connect()->table('contratos')
                    ->groupStart()
                        ->like('codigo', $termino)
                        ->orLike('numero_contrato', $termino)
                        ->orLike('nombre_colegio', $termino)
                        ->orLike('persona_encargada', $termino)
                    ->groupEnd()
                    ->orderBy('creado_en', 'DESC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            }
            if (AdminAccess::isAdmin()) {
                $usuarios = db_connect()->table('usuarios')
                    ->groupStart()
                        ->like('usuario', $termino)
                    ->groupEnd()
                    ->orderBy('usuario', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            }
        }

        return view('admin/busqueda', [
            'termino' => $termino,
            'cotizaciones' => $cotizaciones,
            'servicios' => $servicios,
            'contratos' => $contratos,
            'usuarios' => $usuarios,
        ]);
    }
}
