<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('error', 'Inicia sesión para acceder al panel.');
        }

        if (in_array('admin', $arguments ?? [], true) && ! in_array(session()->get('rol'), ['Dueno', 'Admin'], true)) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
