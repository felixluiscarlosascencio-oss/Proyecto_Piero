<?php

/**
 * Devuelve la galería de un servicio como lista de rutas válidas.
 */
function galeria_servicio(mixed $json): array
{
    $imagenes = $json;

    if (is_string($json) && $json !== '') {
        $imagenes = json_decode($json, true);
    }

    if (! is_array($imagenes)) {
        return [];
    }

    return array_values(array_filter(
        $imagenes,
        static fn ($imagen): bool => is_string($imagen) && $imagen !== ''
    ));
}
