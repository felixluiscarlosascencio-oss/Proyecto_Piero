<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    public function login()
    {
        return view('login');
    }

    public function processLogin()
    {
        $session = session();
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $username = $request->getPost('username');
        $password = $request->getPost('password');

        // Busca en la tabla 'usuarios' por el campo 'usuario'
        $user = $db->table('usuarios')->where('usuario', $username)->get()->getRowArray();

        if ($user) {
            // Verifica si está activo (1 o true)
            if (isset($user['activo']) && (int)$user['activo'] === 0) {
                return redirect()->to('/login')->with('error', 'El usuario se encuentra inactivo.');
            }

            // Compara la contraseña ingresada con el hash encriptado
            if (password_verify($password, $user['password_hash'])) {
                
                // Actualizar ultimo_acceso
                $db->table('usuarios')->where('id', $user['id'])->update([
                    'ultimo_acceso' => date('Y-m-d H:i:s')
                ]);

                // Guardar datos clave en la sesión
                $sessionData = [
                    'id_usuario' => $user['id'],
                    'usuario'    => $user['usuario'],
                    'rol'        => $user['rol'],
                    'isLoggedIn' => true,
                ];
                $session->set($sessionData);

                // Redirige a la página principal
                return redirect()->to('/');
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