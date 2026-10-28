<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class AuthController extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(base_url('admin/cotizaciones'));
        }

        return view('admin/login');
    }

    public function processLogin()
    {
        $session = session();
        $db = Database::connect();

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Busca en la tabla 'usuarios' por el campo 'usuario'
        $user = $db->table('usuarios')->where('usuario', $username)->get()->getRowArray();

        if ($user) {
            $passwordHash = $user['password_hash'] ?? $user['password'] ?? '';
            $activo = $user['activo'] ?? $user['estado'] ?? 1;

            if ((int) $activo === 0) {
                return redirect()->to('/login')->with('error', 'El usuario se encuentra inactivo.');
            }

            if (is_string($passwordHash) && password_verify((string) $password, $passwordHash)) {
                $session->regenerate(true);

                $userId = (int) ($user['id'] ?? $user['usuario_id'] ?? 0);
                if ($userId > 0) {
                    $db->table('usuarios')->where($db->fieldExists('id', 'usuarios') ? 'id' : 'usuario_id', $userId)->update([
                        'ultimo_acceso' => date('Y-m-d H:i:s'),
                    ]);
                }

                $nombre = $user['nombre'] ?? $user['usuario'];
                if (empty($user['nombre']) && ! empty($user['personal_id']) && $db->tableExists('personal')) {
                    $personal = $db->table('personal')->where('id', $user['personal_id'])->get()->getRowArray();
                    $nombre = $personal['nombre'] ?? $nombre;
                }

                $permisos = json_decode($user['permisos_json'] ?? 'null', true);
                $sessionData = [
                    'id_usuario' => $userId,
                    'usuario'    => $user['usuario'],
                    'username'   => $user['usuario'],
                    'nombre'     => $nombre,
                    'cargo'      => $user['cargo'] ?? '',
                    'rol'        => $user['rol'],
                    'permisos'   => is_array($permisos) ? $permisos : null,
                    'isLoggedIn' => true,
                    'logged_in'  => true,
                ];
                $session->set($sessionData);

                return redirect()->to(base_url('admin/cotizaciones'));
            }
        }

        // Credenciales incorrectas
        return redirect()->to('/login')->with('error', 'Usuario o contraseña incorrectos.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}