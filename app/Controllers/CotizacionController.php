<?php

namespace App\Controllers;

class CotizacionController extends BaseController
{
    /**
     * Muestra el listado de todas las cotizaciones guardadas en sesión.
     */
    public function index()
    {
        $session = session();
        $data['cotizaciones'] = $session->get('cotizaciones_temp') ?? [];
        return view('cotizaciones/index', $data);
    }

    /**
     * Carga la vista del formulario interactivo para armar una nueva cotización.
     */
    public function crear()
    {
        return view('cotizaciones/crear');
    }

    /**
     * Recibe los datos por POST, calcula totales y los AGREGA a la lista en sesión.
     */
    public function guardar()
    {
        $serviciosSeleccionados = $this->request->getPost('servicios') ?? [];
        $cantidadAlumnos = (int) $this->request->getPost('cantidad_alumnos');

        $precioUnitario = 0;
        foreach ($serviciosSeleccionados as $precio) {
            $precioUnitario += (float) $precio;
        }

        $totalSeccion = $precioUnitario * $cantidadAlumnos;

        // Generamos un ID único temporal basado en el tiempo
        $idTemp = time();

        $dataCotizacion = [
            'id'               => $idTemp,
            'nombre_colegio'   => $this->request->getPost('nombre_colegio'),
            'grado'            => $this->request->getPost('grado'),
            'seccion'          => $this->request->getPost('seccion'),
            'cantidad_alumnos' => $cantidadAlumnos,
            'sesiones'         => $this->request->getPost('sesiones'),
            'modelo_cuadro'    => $this->request->getPost('modelo_cuadro'),
            'servicios'        => $serviciosSeleccionados,
            'precio_unitario'  => $precioUnitario,
            'total_seccion'    => $totalSeccion,
            'fecha_creacion'   => date('Y-m-d H:i:s')
        ];

        $session = session();
        $cotizaciones = $session->get('cotizaciones_temp') ?? [];
        
        // Agregamos la nueva cotización al arreglo
        invocation: 
        $cotizaciones[$idTemp] = $dataCotizacion;
        $session->set('cotizaciones_temp', $cotizaciones);

        return redirect()->to(base_url('cotizaciones'))->with('mensaje', '¡Cotización guardada en la lista de prueba!');
    }

    /**
     * Carga una cotización específica para editarla.
     */
    public function editar($id = null)
    {
        $session = session();
        $cotizaciones = $session->get('cotizaciones_temp') ?? [];

        if (!isset($cotizaciones[$id])) {
            return redirect()->to(base_url('cotizaciones'))->with('error', 'Cotización no encontrada.');
        }

        $data['cotizacion'] = $cotizaciones[$id];
        return view('cotizaciones/editar', $data);
    }

    /**
     * Actualiza los datos de una cotización existente en la sesión.
     */
    public function actualizar($id = null)
    {
        $session = session();
        $cotizaciones = $session->get('cotizaciones_temp') ?? [];

        if (!isset($cotizaciones[$id])) {
            return redirect()->to(base_url('cotizaciones'))->with('error', 'Cotización no encontrada.');
        }

        $serviciosSeleccionados = $this->request->getPost('servicios') ?? [];
        $cantidadAlumnos = (int) $this->request->getPost('cantidad_alumnos');

        $precioUnitario = 0;
        foreach ($serviciosSeleccionados as $precio) {
            $precioUnitario += (float) $precio;
        }

        $totalSeccion = $precioUnitario * $cantidadAlumnos;

        // Actualizamos los datos manteniendo el mismo ID
        $cotizaciones[$id] = [
            'id'               => $id,
            'nombre_colegio'   => $this->request->getPost('nombre_colegio'),
            'grado'            => $this->request->getPost('grado'),
            'seccion'          => $this->request->getPost('seccion'),
            'cantidad_alumnos' => $cantidadAlumnos,
            'sesiones'         => $this->request->getPost('sesiones'),
            'modelo_cuadro'    => $this->request->getPost('modelo_cuadro'),
            'servicios'        => $serviciosSeleccionados,
            'precio_unitario'  => $precioUnitario,
            'total_seccion'    => $totalSeccion,
            'fecha_creacion'   => $cotizaciones[$id]['fecha_creacion']
        ];

        $session->set('cotizaciones_temp', $cotizaciones);

        return redirect()->to(base_url('cotizaciones'))->with('mensaje', 'Cotización actualizada correctamente.');
    }

    /**
     * Elimina una cotización específica de la lista en sesión.
     */
    public function eliminar($id = null)
    {
        $session = session();
        $cotizaciones = $session->get('cotizaciones_temp') ?? [];

        if (isset($cotizaciones[$id])) {
            unset($cotizaciones[$id]);
            $session->set('cotizaciones_temp', $cotizaciones);
        }

        return redirect()->to(base_url('cotizaciones'))->with('mensaje', 'Cotización eliminada de la lista.');
    }
}