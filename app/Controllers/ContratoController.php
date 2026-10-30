<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use Config\Database;
use Throwable;

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
        foreach (['servicios', 'agenda', 'cuadros'] as $detalle) {
            $datos = json_decode($contrato[$detalle . '_json'] ?? '[]', true);
            $contrato[$detalle] = is_array($datos) ? $datos : [];
        }

        return view('admin/contratos/editar', ['contrato' => $contrato, 'alumnos' => $alumnos]);
    }

    public function actualizar(int $id)
    {
        if (! AdminAccess::can('contratos.editar')) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No tienes permiso para editar contratos.');
        }

        $db = Database::connect();
        $contrato = $db->table('contratos')->where('id', $id)->get()->getRowArray();
        if ($contrato === null) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No se encontró el contrato.');
        }

        $cantidad = filter_var($this->request->getPost('cant_alumnos_inicial'), FILTER_VALIDATE_INT);
        $nivel = trim((string) $this->request->getPost('nivel'));
        $aula = trim((string) $this->request->getPost('aula'));
        $observaciones = trim((string) $this->request->getPost('observaciones'));
        $retirarAlumnoId = filter_var($this->request->getPost('retirar_alumno_id'), FILTER_VALIDATE_INT);
        $nuevoAlumno = $retirarAlumnoId === false
            ? trim((string) $this->request->getPost('nuevo_alumno'))
            : '';

        $serviciosInput = $this->request->getPost('servicios');
        $servicios = [];
        $agendaInput = $this->request->getPost('agenda');
        $agenda = [];
        $cuadrosInput = $this->request->getPost('cuadros');
        $cuadros = [];

        if ($cantidad === false || $cantidad < 1 || $cantidad > 10000 || mb_strlen($nivel) > 50 || mb_strlen($aula) > 50 || mb_strlen($observaciones) > 10000 || mb_strlen($nuevoAlumno) > 150) {
            return redirect()->back()->withInput()->with('error', 'Revisa la cantidad, nivel, aula y observaciones.');
        }

        if (! is_array($serviciosInput) || ! is_array($agendaInput) || ! is_array($cuadrosInput)) {
            return redirect()->back()->withInput()->with('error', 'Revisa los servicios, fechas de sesiones y detalles de cuadros.');
        }

        foreach ($serviciosInput as $servicio) {
            if (! is_array($servicio)) {
                continue;
            }
            $nombre = trim((string) ($servicio['nombre'] ?? ''));
            $precio = $servicio['precio_unitario'] ?? null;
            if ($nombre === '' && ($precio === null || $precio === '')) {
                continue;
            }
            if ($nombre === '' || mb_strlen($nombre) > 120 || ! is_numeric($precio) || (float) $precio < 0 || (float) $precio > 9999999999.99) {
                return redirect()->back()->withInput()->with('error', 'Revisa el nombre y precio de los servicios.');
            }
            $servicios[] = ['nombre' => $nombre, 'precio_unitario' => round((float) $precio, 2)];
        }
        if ($servicios === []) {
            return redirect()->back()->withInput()->with('error', 'El contrato debe incluir al menos un servicio.');
        }
        $montoTotal = round(array_sum(array_column($servicios, 'precio_unitario')) * $cantidad, 2);
        if ($montoTotal > 99999999.99) {
            return redirect()->back()->withInput()->with('error', 'El monto supera el máximo permitido por el contrato.');
        }

        foreach ($agendaInput as $sesion) {
            if (! is_array($sesion)) {
                continue;
            }
            $fecha = trim((string) ($sesion['fecha'] ?? ''));
            $detalle = trim((string) ($sesion['detalle'] ?? ''));
            if ($fecha === '' && $detalle === '') {
                continue;
            }
            $fechaValida = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
            $erroresFecha = \DateTimeImmutable::getLastErrors();
            if ($fechaValida === false || $fechaValida->format('Y-m-d') !== $fecha || ($erroresFecha !== false && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0)) || $detalle === '' || mb_strlen($detalle) > 500) {
                return redirect()->back()->withInput()->with('error', 'Cada sesión requiere una fecha válida y un detalle de hasta 500 caracteres.');
            }
            $agenda[] = ['fecha' => $fecha, 'detalle' => $detalle];
        }

        foreach ($cuadrosInput as $cuadro) {
            if (! is_array($cuadro)) {
                continue;
            }
            $detalle = trim((string) ($cuadro['detalle'] ?? ''));
            $cantidadCuadros = $cuadro['cantidad'] ?? null;
            if ($detalle === '' && ($cantidadCuadros === null || $cantidadCuadros === '')) {
                continue;
            }
            $cantidadValida = filter_var($cantidadCuadros, FILTER_VALIDATE_INT);
            if ($detalle === '' || mb_strlen($detalle) > 300 || $cantidadValida === false || $cantidadValida < 1 || $cantidadValida > 10000) {
                return redirect()->back()->withInput()->with('error', 'Cada cuadro requiere una descripción y una cantidad válida.');
            }
            $cuadros[] = ['detalle' => $detalle, 'cantidad' => $cantidadValida];
        }

        if ($nuevoAlumno !== '' && ! AdminAccess::can('contratos.editar')) {
            return redirect()->back()->with('error', 'No tienes permiso para dar de alta alumnos.');
        }

        $db->transBegin();
        $montoPagado = (float) ($contrato['monto_pagado'] ?? 0);
        $actualizado = $db->table('contratos')->where('id', $id)->update([
            'nivel'                => $nivel,
            'aula'                 => $aula,
            'cant_alumnos_inicial' => $cantidad,
            'observaciones'        => $observaciones,
            'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'agenda_json'          => json_encode($agenda, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'cuadros_json'         => json_encode($cuadros, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'monto_total'          => $montoTotal,
            'monto_saldo'          => max(0, $montoTotal - $montoPagado),
        ]);

        if ($nuevoAlumno !== '') {
            $db->table('alumnos')->insert([
                'contrato_id'         => $id,
                'nombre_completo'     => $nuevoAlumno,
                'tipo_registro'       => 'Tardio',
                'fecha_incorporacion' => date('Y-m-d'),
                'estado'              => 'Activo',
            ]);
        }

        if ($retirarAlumnoId !== false && $retirarAlumnoId > 0) {
            $db->table('alumnos')
                ->where('id', $retirarAlumnoId)
                ->where('contrato_id', $id)
                ->where('estado', 'Activo')
                ->update(['estado' => 'Retirado']);
        }

        if (! $actualizado || $db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'No se pudieron guardar los cambios del contrato.');
        }
        $db->transCommit();

        return redirect()->to(base_url('admin/contratos/' . $id))->with('mensaje', 'Contrato actualizado.');
    }

    public function eliminar(int $id)
    {
        if (! AdminAccess::isOwner()) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'Solo el rol Dueño puede eliminar contratos.');
        }

        $db = Database::connect();
        $contrato = $db->table('contratos')->where('id', $id)->get()->getRowArray();
        if ($contrato === null) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No se encontró el contrato.');
        }

        try {
            if (! $db->table('contratos')->where('id', $id)->delete()) {
                return redirect()->to(base_url('admin/contratos'))->with('error', 'No se pudo eliminar el contrato.');
            }
        } catch (Throwable $exception) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'El contrato tiene registros vinculados y no se puede eliminar.');
        }

        return redirect()->to(base_url('admin/contratos'))->with('mensaje', 'Contrato eliminado.');
    }
}
