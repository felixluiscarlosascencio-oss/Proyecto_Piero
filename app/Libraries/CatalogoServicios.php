<?php

namespace App\Libraries;

use App\Models\ServicioModel;

/**
 * Centraliza la lectura y validación del catálogo de servicios.
 *
 * Se usa tanto en la solicitud pública (sin precios) como en la creación de
 * cotizaciones y versiones desde el panel administrativo (con precios).
 */
class CatalogoServicios
{
    private const PRECIO_MAXIMO = 9999999999.99;

    /**
     * Servicios seleccionados desde el formulario público. El precio queda en
     * cero hasta que un administrador lo defina.
     *
     * @return list<array{servicio_id: int, nombre: string, descripcion: string, precio_interno: float, precio_unitario: int}>
     */
    public function paraSolicitud(mixed $input): array
    {
        $ids = $this->idsValidos($input);
        if ($ids === []) {
            return [];
        }

        $catalogo = $this->buscar($ids);
        if (count($catalogo) !== count($ids)) {
            return [];
        }

        $servicios = [];
        foreach ($catalogo as $servicio) {
            $servicios[] = [
                'servicio_id'     => (int) $servicio['id'],
                'nombre'          => (string) $servicio['nombre_item'],
                'descripcion'     => (string) ($servicio['descripcion'] ?? ''),
                'precio_interno'  => (float) $servicio['precio_interno'],
                'precio_unitario' => 0,
            ];
        }

        return $servicios;
    }

    /**
     * Servicios seleccionados con un precio de cotización validado contra el
     * precio interno del catálogo.
     *
     * @return list<array{servicio_id: int, nombre: string, descripcion: string, precio_interno: float, precio_unitario: float}>
     */
    public function paraCotizacion(mixed $input, mixed $preciosInput): array
    {
        $ids = $this->idsValidos($input);
        if ($ids === [] || ! is_array($preciosInput)) {
            return [];
        }

        $servicios = $this->buscar($ids);
        if (count($servicios) !== count($ids)) {
            return [];
        }

        $resultado = [];
        foreach ($servicios as $servicio) {
            $precio = $preciosInput[$servicio['id']] ?? $servicio['precio_interno'];
            if ($precio === '') {
                $precio = $servicio['precio_interno'];
            }
            $precioInterno = (float) $servicio['precio_interno'];
            if (! is_numeric($precio)
                || (float) $precio <= 0
                || $precioInterno <= 0
                || (float) $precio > self::PRECIO_MAXIMO
                || (float) $precio > $precioInterno) {
                return [];
            }
            $resultado[] = [
                'servicio_id'     => (int) $servicio['id'],
                'nombre'          => (string) $servicio['nombre_item'],
                'descripcion'     => (string) ($servicio['descripcion'] ?? ''),
                'precio_interno'  => $precioInterno,
                'precio_unitario' => round((float) $precio, 2),
            ];
        }

        return $resultado;
    }

    /**
     * Reconstruye la selección de una nueva versión, conservando servicios de
     * la cotización original y agregando los nuevos con su precio.
     *
     * @return list<array<string, mixed>>
     */
    public function clonados(mixed $serviciosOrigen, mixed $seleccion, mixed $precios): array
    {
        if (! is_array($serviciosOrigen) || ! is_array($seleccion)) {
            return [];
        }

        $seleccionados = [];
        $idsNuevos = [];
        $idsOrigen = [];
        foreach ($serviciosOrigen as $servicioOrigen) {
            if (is_array($servicioOrigen) && isset($servicioOrigen['servicio_id'])) {
                $idsOrigen[(string) $servicioOrigen['servicio_id']] = true;
            }
        }

        foreach ($seleccion as $valor) {
            if (! is_string($valor) && ! is_int($valor)) {
                return [];
            }
            $valor = (string) $valor;

            if (preg_match('/^origen-(\d+)$/', $valor, $coincidencia) === 1) {
                $indice = (int) $coincidencia[1];
                $servicio = $serviciosOrigen[$indice] ?? null;
                if (! is_array($servicio)
                    || ! is_string($servicio['nombre'] ?? null)
                    || trim($servicio['nombre']) === ''
                    || ! is_numeric($servicio['precio_unitario'] ?? null)
                    || (float) $servicio['precio_unitario'] < 0
                    || (float) $servicio['precio_unitario'] > self::PRECIO_MAXIMO) {
                    return [];
                }
                $seleccionados[] = $servicio;
                continue;
            }

            $id = filter_var($valor, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1 || isset($idsOrigen[(string) $id])) {
                return [];
            }
            $idsNuevos[] = $id;
        }

        $idsNuevos = array_values(array_unique($idsNuevos));
        if ($idsNuevos !== []) {
            if (! is_array($precios)) {
                return [];
            }
            foreach ($idsNuevos as $id) {
                if (! array_key_exists($id, $precios) || ! is_numeric($precios[$id])) {
                    return [];
                }
            }
            $nuevos = $this->paraCotizacion($idsNuevos, $precios);
            if (count($nuevos) !== count($idsNuevos)) {
                return [];
            }
            $seleccionados = array_merge($seleccionados, $nuevos);
        }

        return $seleccionados;
    }

    /**
     * @return list<int>
     */
    private function idsValidos(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $ids = [];
        foreach ($input as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                return [];
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<int> $ids
     * @return list<array<string, mixed>>
     */
    private function buscar(array $ids): array
    {
        return (new ServicioModel())
            ->select('id, nombre_item, descripcion, precio_interno')
            ->whereIn('id', $ids)
            ->findAll();
    }
}
