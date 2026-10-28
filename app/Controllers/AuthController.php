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
            // Verifica si está activo (1 o true)
            if (isset($user['activo']) && (int)$user['activo'] === 0) {
                return redirect()->to('/login')->with('error', 'El usuario se encuentra inactivo.');
            }

            // Compara la contraseña ingresada con el hash encriptado
            if (password_verify($password, $user['password_hash'])) {
                $session->regenerate(true);
                
                // Actualizar ultimo_acceso
                $db->table('usuarios')->where('id', $user['id'])->update([
                    'ultimo_acceso' => date('Y-m-d H:i:s')
                ]);

                // Guardar datos clave en la sesión (compatibles con main.php)
                $sessionData = [
                    'id_usuario' => $user['id'],
                    'usuario'    => $user['usuario'],
                    'username'   => $user['usuario'],  // Se agrega 'username' para la vista main.php
                    'rol'        => $user['rol'],
                    'isLoggedIn' => true,
                    'logged_in'  => true               // Se agrega 'logged_in' para la vista main.php
                ];
                $session->set($sessionData);

                // Redirige a la página principal
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