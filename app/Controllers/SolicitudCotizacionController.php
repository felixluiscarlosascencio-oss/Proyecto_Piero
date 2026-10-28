<?php

namespace App\Controllers;

use App\Models\CotizacionModel;

class SolicitudCotizacionController extends BaseController
{
    private const SERVICIOS_DISPONIBLES = [
        'Paquete Base Anuario',
        'Sesión de Exteriores y Drone',
        'Cuadro Retrato de Graduación',
    ];

    public function crear()
    {
        $nombreColegio = $this->request->getPost('nombre_colegio');
        $datos = [
            'nombre_colegio'       => is_string($nombreColegio) ? trim($nombreColegio) : '',
            'cantidad_estudiantes' => $this->request->getPost('cantidad_estudiantes'),
        ];
        $servicios = $this->request->getPost('servicios');

        $validation = service('validation');
        $validation->setRules([
            'nombre_colegio'       => 'required|string|max_length[150]',
            'cantidad_estudiantes' => 'required|integer|greater_than[0]|less_than_equal_to[10000]',
        ]);

        if (! $validation->run($datos)) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Revisa los datos ingresados.',
                'errors'  => $validation->getErrors(),
            ]);
        }

        if (! is_array($servicios)) {
            $servicios = [];
        }

        $servicios = array_values(array_unique(array_filter(
            array_map(static fn ($servicio): string => is_string($servicio) ? trim($servicio) : '', $servicios),
            static fn (string $servicio): bool => in_array($servicio, self::SERVICIOS_DISPONIBLES, true)
        )));

        if ($servicios === []) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Selecciona al menos un servicio.',
            ]);
        }

        $model = new CotizacionModel();
        if (! $model->insert([
            'nombre_colegio'       => $datos['nombre_colegio'],
            'cantidad_estudiantes' => (int) $datos['cantidad_estudiantes'],
            'servicios_json'       => json_encode(array_map(
                static fn (string $nombre): array => ['nombre' => $nombre, 'precio_unitario' => 0],
                $servicios
            ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'precio_unitario'      => 0,
            'total_seccion'        => 0,
            'estado'               => CotizacionModel::ESTADO_SOLICITUD,
        ])) {
            log_message('error', 'Failed to save a public quote request: ' . json_encode($model->errors()));

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'No se pudo guardar la solicitud. Inténtalo nuevamente.',
            ]);
        }

        $id = $model->getInsertID();
        $mensaje = implode("\n", [
            'Hola, deseo solicitar una cotización de Piero Producciones.',
            'Solicitud #' . $id,
            'Institución: ' . $datos['nombre_colegio'],
            'Cantidad de estudiantes: ' . $datos['cantidad_estudiantes'],
            'Servicios: ' . implode(', ', $servicios),
        ]);

        helper('contacto');

        return $this->response->setJSON([
            'whatsapp_url' => enlace_wsp($mensaje),
        ]);
    }
}
