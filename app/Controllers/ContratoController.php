<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\ServicioModel;
use Config\Database;

class ContratoController extends BaseController
{
    public function index()
    {
        if (! AdminAccess::can('contratos.ver')) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permiso para ver contratos.');
        }

        $contratos = Database::connect()->table('contratos')->orderBy('id', 'DESC')->get()->getResultArray();

        return view('admin/contratos/index', ['contratos' => $contratos]);
    }

    public function editar(int $id)
    {
        if (! AdminAccess::can('contratos.ver')) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No tienes permiso para ver contratos.');
        }

        $db = Database::connect();
        $contrato = $db->table('contratos')->where('id', $id)->get()->getRowArray();
        if ($contrato === null) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No se encontró el contrato.');
        }

        $alumnos = $db->table('alumnos')->where('contrato_id', $id)->orderBy('numero_lista', 'ASC')->get()->getResultArray();
        $ajustes = $db->table('contrato_ajustes')
            ->where('contrato_id', $id)
            ->orderBy('creado_en', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
        foreach (['servicios', 'agenda', 'cuadros'] as $detalle) {
            $datos = json_decode($contrato[$detalle . '_json'] ?? '[]', true);
            $contrato[$detalle] = is_array($datos) ? $datos : [];
        }

        $costoInternoEstimado = null;
        if (AdminAccess::isAdmin()) {
            $catalogo = (new ServicioModel())->select('id, nombre_item, precio_interno')->findAll();
            $porId = [];
            $porNombre = [];
            foreach ($catalogo as $servicioCatalogo) {
                $porId[(int) $servicioCatalogo['id']] = (float) $servicioCatalogo['precio_interno'];
                $porNombre[(string) $servicioCatalogo['nombre_item']] = (float) $servicioCatalogo['precio_interno'];
            }

            $costoUnitario = 0.0;
            $serviciosVinculados = 0;
            foreach ($contrato['servicios'] as $servicio) {
                if (! is_array($servicio)) {
                    continue;
                }
                $servicioId = filter_var($servicio['servicio_id'] ?? null, FILTER_VALIDATE_INT);
                $costo = $servicioId !== false && isset($porId[$servicioId])
                    ? $porId[$servicioId]
                    : ($porNombre[(string) ($servicio['nombre'] ?? '')] ?? null);
                if ($costo !== null) {
                    $costoUnitario += $costo;
                    $serviciosVinculados++;
                }
            }
            if ($serviciosVinculados > 0) {
                $costoInternoEstimado = round($costoUnitario * (int) $contrato['cant_alumnos_inicial'], 2);
            }
        }

        return view('admin/contratos/editar', [
            'contrato' => $contrato,
            'alumnos' => $alumnos,
            'ajustes' => $ajustes,
            'costoInternoEstimado' => $costoInternoEstimado,
        ]);
    }

    public function crearAjuste(int $id)
    {
        if (! AdminAccess::can('contratos.editar')) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No tienes permiso para registrar adendas.');
        }

        $db = Database::connect();
        if ($db->table('contratos')->where('id', $id)->countAllResults() === 0) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No se encontró el contrato.');
        }

        $tipo = $this->postString('tipo');
        $motivo = $this->postString('motivo');
        $impacto = $this->request->getPost('impacto_financiero');
        if (! in_array($tipo, ['alumnos', 'servicio', 'recargo'], true)
            || mb_strlen($motivo) < 5
            || mb_strlen($motivo) > 500
            || ! is_numeric($impacto)
            || abs((float) $impacto) > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'Revisa el tipo, motivo e impacto financiero de la adenda.');
        }

        $detalle = [];
        $impactoFinanciero = round((float) $impacto, 2);
        if ($tipo === 'alumnos') {
            $cantidadDelta = filter_var($this->request->getPost('cantidad_delta'), FILTER_VALIDATE_INT);
            if ($cantidadDelta === false || $cantidadDelta === 0 || abs($cantidadDelta) > 10000) {
                return redirect()->back()->withInput()->with('error', 'La variación de alumnos debe ser un entero entre -10000 y 10000, distinto de cero.');
            }
            $detalle = ['cantidad_alumnos_delta' => $cantidadDelta];
        } elseif ($tipo === 'servicio') {
            $accion = $this->postString('accion_servicio');
            $nombre = $this->postString('servicio_nombre');
            $cantidad = filter_var($this->request->getPost('servicio_cantidad'), FILTER_VALIDATE_INT);
            $precio = $this->request->getPost('servicio_precio');
            if (! in_array($accion, ['agregar', 'retirar'], true)
                || $nombre === ''
                || mb_strlen($nombre) > 120
                || $cantidad === false
                || $cantidad < 1
                || $cantidad > 10000
                || ! is_numeric($precio)
                || (float) $precio < 0
                || (float) $precio > 99999999.99) {
                return redirect()->back()->withInput()->with('error', 'Revisa la operación, servicio, cantidad y precio de la adenda.');
            }
            $impactoFinanciero = abs($impactoFinanciero) * ($accion === 'retirar' ? -1 : 1);
            $detalle = [
                'accion' => $accion,
                'servicio' => $nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => round((float) $precio, 2),
            ];
        } else {
            $concepto = $this->postString('concepto_recargo');
            if ($concepto === '' || mb_strlen($concepto) > 150 || $impactoFinanciero <= 0) {
                return redirect()->back()->withInput()->with('error', 'El recargo necesita un concepto y un impacto positivo.');
            }
            $detalle = ['concepto' => $concepto];
        }

        if (! $db->table('contrato_ajustes')->insert([
            'contrato_id' => $id,
            'tipo' => $tipo,
            'motivo' => $motivo,
            'detalle_json' => json_encode($detalle, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'impacto_financiero' => $impactoFinanciero,
            'creado_por' => (int) session()->get('id_usuario'),
            'creado_en' => date('Y-m-d H:i:s'),
        ])) {
            log_message('error', 'Failed to create a contract addendum for contract #' . $id . '.');

            return redirect()->back()->withInput()->with('error', 'No se pudo guardar la adenda.');
        }

        return redirect()->to(base_url('admin/contratos/' . $id))->with('mensaje', 'Adenda registrada. El contrato original permanece sin cambios.');
    }

    public function actualizar(int $id)
    {
        return redirect()->to(base_url('admin/contratos/' . $id))
            ->with('error', 'La edición directa de contratos está bloqueada. Registra los cambios mediante una adenda.');
    }

    public function eliminar(int $id)
    {
        return redirect()->to(base_url('admin/contratos/' . $id))
            ->with('error', 'Los contratos emitidos son inmutables y no se pueden eliminar.');
    }

    private function postString(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_string($value) ? trim($value) : '';
    }
}
