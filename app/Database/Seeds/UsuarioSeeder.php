<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        if (strlen($password) < 10) {
            throw new RuntimeException(
                'Define SEED_ADMIN_PASSWORD (mínimo 10 caracteres) antes de ejecutar este seeder.'
            );
        }

        $existe = $this->db->table('usuarios')->where('usuario', 'admin')->countAllResults() > 0;
        if ($existe) {
            throw new RuntimeException('La cuenta "admin" ya existe; no se creó un duplicado.');
        }

        $this->db->table('usuarios')->insert([
            'usuario'       => 'admin',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'nombre'        => 'Administrador',
            'cargo'         => 'Dueño',
            'rol'           => 'Dueno',
            'activo'        => 1,
        ]);
    }
}
