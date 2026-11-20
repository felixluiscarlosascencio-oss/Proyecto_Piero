<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Elimina registros de migraciones cuyos archivos ya no existen.
 *
 * El proyecto tuvo migraciones vacías y archivos eliminados que dejaron
 * filas huérfanas en la tabla `migrations`. Esto impide que `migrate:status`
 * refleje el estado real del esquema.
 */
class ReconcileLegacyMigrations extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('migrations')) {
            return;
        }

        $directory = APPPATH . 'Database/Migrations/';
        $records = $this->db->table('migrations')->get()->getResultArray();

        foreach ($records as $record) {
            $class = (string) ($record['class'] ?? '');
            $shortName = substr($class, strrpos($class, '\\') + 1);

            if ($shortName === '' || preg_match('/^[A-Za-z0-9_]+$/', $shortName) !== 1) {
                continue;
            }

            $matches = glob($directory . '*_' . $shortName . '.php') ?: [];

            if ($matches === []) {
                $this->db->table('migrations')->where('id', $record['id'])->delete();
            }
        }
    }

    public function down()
    {
        // Reconciliar el historial no es reversible.
    }
}
