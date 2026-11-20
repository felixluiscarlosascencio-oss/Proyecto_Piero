<?php

namespace App\Controllers;

use Config\Database;

class AuthController extends BaseController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 300;

    public function login()
    {
        if (session()->get('logged_in') === true) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return view('admin/login');
    }

    public function processLogin()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->to('/login')->with('error', 'Usuario o contraseña incorrectos.');
        }

        $throttler = service('throttler');
        $throttleKey = 'login-' . md5(strtolower($username) . '|' . $this->request->getIPAddress());

        if ($throttler->check($throttleKey, self::MAX_LOGIN_ATTEMPTS, self::LOGIN_WINDOW_SECONDS) === false) {
            return redirect()->to('/login')
                ->with('error', 'Demasiados intentos. Espera unos minutos antes de volver a intentarlo.');
        }

        $db = Database::connect();
        $user = $db->table('usuarios')->where('usuario', $username)->get()->getRowArray();

        if ($user === null || ! password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return redirect()->to('/login')->with('error', 'Usuario o contraseña incorrectos.');
        }

        if ((int) ($user['activo'] ?? 0) !== 1) {
            return redirect()->to('/login')->with('error', 'El usuario se encuentra inactivo.');
        }

        $throttler->remove($throttleKey);
        session()->regenerate(true);

        $userId = (int) ($user['id'] ?? 0);
        if ($userId > 0) {
            $db->table('usuarios')->where('id', $userId)->update([
                'ultimo_acceso' => date('Y-m-d H:i:s'),
            ]);
        }

        $permisos = json_decode((string) ($user['permisos_json'] ?? 'null'), true);

        session()->set([
            'id_usuario' => $userId,
            'usuario'    => $user['usuario'],
            'username'   => $user['usuario'],
            'nombre'     => ($user['nombre'] ?? '') !== '' ? $user['nombre'] : $user['usuario'],
            'cargo'      => $user['cargo'] ?? '',
            'rol'        => $user['rol'],
            'permisos'   => is_array($permisos) ? $permisos : null,
            'isLoggedIn' => true,
            'logged_in'  => true,
        ]);

        return redirect()->to(base_url('admin/dashboard'));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
