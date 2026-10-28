<?php

namespace App\Libraries;

class AdminAccess
{
    private const DEFAULT_PERMISSIONS = [
        'Secretaria' => ['cotizaciones.ver', 'cotizaciones.editar', 'cotizaciones.eliminar', 'contratos.ver', 'contratos.editar'],
        'Encargado'  => ['cotizaciones.ver', 'cotizaciones.editar', 'cotizaciones.eliminar', 'contratos.ver', 'contratos.editar'],
        'Fotografo'  => ['cotizaciones.ver', 'cotizaciones.editar', 'contratos.ver'],
        'Produccion' => ['cotizaciones.ver', 'cotizaciones.editar', 'contratos.ver', 'contratos.editar'],
    ];

    public static function isAdmin(): bool
    {
        return in_array(session()->get('rol'), ['Dueno', 'Admin'], true);
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

        if (is_array($permissions)) {
            return in_array($permission, $permissions, true);
        }

        $role = (string) session()->get('rol');

        return in_array($permission, self::DEFAULT_PERMISSIONS[$role] ?? [], true);
    }
}
