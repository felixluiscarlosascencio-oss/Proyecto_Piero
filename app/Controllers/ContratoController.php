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

        if ($cantidad === false || $cantidad < 1 || $cantidad > 10000 || mb_strlen($nivel) > 50 || mb_strlen($aula) > 50 || mb_strlen($observaciones) > 10000 || mb_strlen($nuevoAlumno) > 150) {
            return redirect()->back()->withInput()->with('error', 'Revisa la cantidad, nivel, aula y observaciones.');
        }

        if ($nuevoAlumno !== '' && ! AdminAccess::can('contratos.editar')) {
            return redirect()->back()->with('error', 'No tienes permiso para dar de alta alumnos.');
        }

        $db->transBegin();
        $db->table('contratos')->where('id', $id)->update([
            'nivel'                => $nivel,
            'aula'                 => $aula,
            'cant_alumnos_inicial' => $cantidad,
            'observaciones'        => $observaciones,
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

        if ($db->transStatus() === false) {
            $db->transRollback();

            return redirect()->back()->withInput()->with('error', 'No se pudieron guardar los cambios del contrato.');
        }
        $db->transCommit();

        return redirect()->to(base_url('admin/contratos/' . $id))->with('mensaje', 'Contrato actualizado.');
    }

    public function eliminar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'Solo el administrador puede eliminar contratos.');
        }

        $db = Database::connect();
        $contrato = $db->table('contratos')->where('id', $id)->get()->getRowArray();
        if ($contrato === null) {
            return redirect()->to(base_url('admin/contratos'))->with('error', 'No se encontró el contrato.');
        }

        if ($this->esCerrado((string) ($contrato['estado_contrato'] ?? ''))) {
            return redirect()->to(base_url('admin/contratos/' . $id))->with('error', 'Los contratos cerrados no se pueden eliminar.');
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

    private function esCerrado(string $estado): bool
    {
        return in_array(mb_strtolower(trim($estado)), ['cerrado', 'cerrada', 'finalizado', 'finalizada'], true);
    }
}
