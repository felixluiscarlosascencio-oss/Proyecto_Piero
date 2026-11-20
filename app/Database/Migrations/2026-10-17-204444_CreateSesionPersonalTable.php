<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migración heredada: la tabla `personal` se crea en CreatePersonalTable.
 * Se conserva como no-op para no romper el historial de migraciones.
 */
class CreateSesionPersonalTable extends Migration
{
    public function up()
    {
        // La tabla ya no se crea aquí.
    }

    public function down()
    {
        // La tabla pertenece a CreatePersonalTable.
    }
}
