<?php

namespace App\Libraries;

class AdminAccess
{
    private const DEFAULT_PERMISSIONS = [
        'Secretaria' => ['cotizaciones.ver', 'cotizaciones.eliminar', 'contratos.ver', 'contratos.editar'],
        'Encargado'  => ['cotizaciones.ver', 'cotizaciones.eliminar', 'contratos.ver', 'contratos.editar'],
        'Fotografo'  => ['cotizaciones.ver', 'contratos.ver'],
        'Produccion' => ['cotizaciones.ver', 'contratos.ver', 'contratos.editar'],
    ];

    public static function isAdmin(): bool
    {
        return session()->get('rol') === 'Dueno';
    }

    public static function can(string $permission): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        $permissions = session()->get('permisos');
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        // Un array vacío significa "sin permisos específicos": se usan los
        // permisos predeterminados del rol para no dejar cuentas bloqueadas.
        if (is_array($permissions) && $permissions !== []) {
            return in_array($permission, $permissions, true);
        }

        $role = (string) session()->get('rol');

        return in_array($permission, self::DEFAULT_PERMISSIONS[$role] ?? [], true);
    }
}
