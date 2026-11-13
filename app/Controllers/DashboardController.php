<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\CotizacionModel;
use CodeIgniter\Database\BaseBuilder;

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
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para ver el dashboard.');
        }

        $db = db_connect();
        $inicioMes = date('Y-m-01 00:00:00');
        $inicioMesAnterior = date('Y-m-01 00:00:00', strtotime('first day of previous month'));
        $finMesAnterior = date('Y-m-t 23:59:59', strtotime('last month'));
        $cotizacionesMes = $this->contarCotizaciones($db->table('solicitudes_cotizacion')->where('created_at >=', $inicioMes));
        $cotizacionesMesAnterior = $this->contarCotizaciones(
            $db->table('solicitudes_cotizacion')
                ->where('created_at >=', $inicioMesAnterior)
                ->where('created_at <=', $finMesAnterior)
        );
        $variacion = $cotizacionesMesAnterior === 0
            ? ($cotizacionesMes > 0 ? 100.0 : 0.0)
            : round((($cotizacionesMes - $cotizacionesMesAnterior) / $cotizacionesMesAnterior) * 100, 1);

        $estadoCounts = [
            'pendientes' => $this->contarCotizaciones(
                $db->table('solicitudes_cotizacion')
                    ->whereIn('estado', [CotizacionModel::ESTADO_SOLICITUD, CotizacionModel::ESTADO_APROBADA_ADMIN, CotizacionModel::ESTADO_PENDIENTE])
            ),
            'aprobadas' => $this->contarCotizaciones(
                $db->table('solicitudes_cotizacion')->whereIn('estado', [CotizacionModel::ESTADO_APROBADO, 'aprobada'])
            ),
            'rechazadas' => $this->contarCotizaciones(
                $db->table('solicitudes_cotizacion')->whereIn('estado', ['rechazada', 'denegada'])
            ),
        ];
        $ingresosEstimados = (float) ($db->table('solicitudes_cotizacion')
            ->selectSum('total_seccion', 'total')
            ->where('estado', CotizacionModel::ESTADO_PENDIENTE)
            ->get()
            ->getRowArray()['total'] ?? 0);

        $meses = [];
        $mesActual = new \DateTimeImmutable('first day of this month');
        for ($offset = 5; $offset >= 0; $offset--) {
            $inicio = $mesActual->modify('-' . $offset . ' months');
            $fin = $inicio->modify('last day of this month')->setTime(23, 59, 59);
            $meses[] = [
                'label' => $inicio->format('m/Y'),
                'total' => $this->contarCotizaciones(
                    $db->table('solicitudes_cotizacion')
                        ->where('created_at >=', $inicio->format('Y-m-d 00:00:00'))
                        ->where('created_at <=', $fin->format('Y-m-d H:i:s'))
                ),
            ];
        }

        $serviciosSolicitados = [];
        foreach ($db->table('solicitudes_cotizacion')->select('servicios_json')->get()->getResultArray() as $cotizacion) {
            $servicios = json_decode((string) ($cotizacion['servicios_json'] ?? ''), true);
            if (! is_array($servicios)) {
                continue;
            }
            foreach ($servicios as $servicio) {
                if (! is_array($servicio) || ! is_string($servicio['nombre'] ?? null)) {
                    continue;
                }
                $nombre = trim($servicio['nombre']);
                if ($nombre !== '') {
                    $serviciosSolicitados[$nombre] = ($serviciosSolicitados[$nombre] ?? 0) + 1;
                }
            }
        }
        arsort($serviciosSolicitados);

        return view('admin/dashboard/index', [
            'esReporte' => $esReporte,
            'cotizacionesMes' => $cotizacionesMes,
            'variacion' => $variacion,
            'pendientes' => $estadoCounts['pendientes'],
            'aprobadas' => $estadoCounts['aprobadas'],
            'ingresosEstimados' => $ingresosEstimados,
            'graficoMeses' => $meses,
            'graficoEstados' => $estadoCounts,
            'serviciosSolicitados' => array_slice($serviciosSolicitados, 0, 5, true),
        ]);
    }

    public function buscar()
    {
        if (! AdminAccess::can('cotizaciones.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para buscar registros.');
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
                ->groupEnd()
                ->orderBy('created_at', 'ASC')
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
                        ->like('numero_contrato', $termino)
                        ->orLike('nombre_colegio', $termino)
                        ->orLike('persona_encargada', $termino)
                    ->groupEnd()
                    ->orderBy('creado_en', 'ASC')
                    ->limit(20)
                    ->get()
                    ->getResultArray();
            }
            if (AdminAccess::isAdmin()) {
                $usuarios = db_connect()->table('usuarios')
                    ->groupStart()
                        ->like('nombre', $termino)
                        ->orLike('usuario', $termino)
                        ->orLike('cargo', $termino)
                    ->groupEnd()
                    ->orderBy('nombre', 'ASC')
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

    private function contarCotizaciones(BaseBuilder $query): int
    {
        return $query->countAllResults();
    }
}
