<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'usuario'       => 'admin',
            'password_hash' => password_hash('Piero#2026', PASSWORD_BCRYPT),
            'rol'           => 'Dueno',
            'activo'        => 1,
        ];

        $this->db->table('usuarios')->insert($data);
    }
}