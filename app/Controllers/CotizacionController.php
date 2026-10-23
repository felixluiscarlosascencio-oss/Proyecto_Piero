<?php

namespace App\Controllers;

class CotizacionController extends BaseController
{
    public function index()
    {
        return view('cotizaciones/index');
    }

    public function crear()
    {
        return view('cotizaciones/crear');
    }

    public function guardar()
    {
        // Aquí procesaremos los datos más adelante
    }
}