<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use Config\Database;

class UsuarioAdminController extends BaseController
{
    private const PERMISSIONS = [
        'cotizaciones.ver'       => 'Ver cotizaciones',
        'cotizaciones.editar'    => 'Solicitar edición de cotizaciones',
        'cotizaciones.eliminar'  => 'Solicitar eliminación de cotizaciones',
        'contratos.ver'          => 'Ver contratos',
        'contratos.editar'       => 'Editar contratos',
    ];

    private const ROLES = ['Secretaria', 'Encargado', 'Fotografo', 'Produccion'];

    public function index()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $usuarios = Database::connect()->table('usuarios')
            ->select('id, nombre, usuario, cargo, rol, activo')
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/usuarios/index', [
            'usuarios'    => $usuarios,
            'permissions' => self::PERMISSIONS,
            'roles'       => self::ROLES,
        ]);
    }

    public function crear()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $rules = [
            'nombre'    => 'required|string|min_length[2]|max_length[100]',
            'usuario'   => 'required|alpha_numeric_punct|min_length[3]|max_length[50]',
            'password'  => 'required|min_length[10]|max_length[255]',
            'cargo'     => 'required|string|min_length[2]|max_length[80]',
            'rol'       => 'required|in_list[' . implode(',', self::ROLES) . ']',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $permissions = $this->request->getPost('permisos');
        if (! is_array($permissions)) {
            $permissions = [];
        }
        $permissions = array_filter($permissions, 'is_string');
        $permissions = array_values(array_intersect(array_unique($permissions), array_keys(self::PERMISSIONS)));

        $db = Database::connect();
        $exists = $db->table('usuarios')->where('usuario', trim($this->request->getPost('usuario')))->countAllResults();
        if ($exists > 0) {
            return redirect()->back()->withInput()->with('error', 'Ese nombre de usuario ya está registrado.');
        }

        $db->table('usuarios')->insert([
            'nombre'        => trim($this->request->getPost('nombre')),
            'usuario'       => trim($this->request->getPost('usuario')),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'cargo'         => trim($this->request->getPost('cargo')),
            'rol'           => $this->request->getPost('rol'),
            'permisos_json' => json_encode($permissions, JSON_THROW_ON_ERROR),
            'activo'        => 1,
        ]);

        return redirect()->to(base_url('admin/usuarios'))->with('mensaje', 'Cuenta del equipo creada.');
    }
}
