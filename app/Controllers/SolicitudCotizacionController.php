<?php

namespace App\Controllers;

use App\Models\CotizacionModel;
use App\Models\ProductoServicioModel;
use Throwable;

class SolicitudCotizacionController extends BaseController
{
    private const PALABRAS_PROHIBIDAS = [
        'puta', 'puto', 'mierda', 'verga', 'pendejo', 'pendeja',
        'idiota', 'imbecil', 'estupido', 'estupida', 'cabron',
        'cabrona', 'coño', 'concha', 'maricon', 'joder',
    ];

    public function crear(): \CodeIgniter\HTTP\ResponseInterface
    {
        $honeypot = $this->request->getPost('website');
        if ($honeypot !== null && (! is_string($honeypot) || trim($honeypot) !== '')) {
            return $this->jsonError(422, 'No se pudo validar la solicitud.');
        }

        $datos = [
            'nombre_colegio'       => $this->postString('nombre_colegio'),
            'nombre_seccion'       => $this->postString('nombre_seccion'),
            'cantidad_estudiantes' => $this->postString('cantidad_estudiantes'),
            'persona_encargada'    => $this->postString('persona_encargada'),
            'telefono_contacto'    => $this->postString('telefono_contacto'),
        ];
        foreach ($datos as $value) {
            if (! mb_check_encoding($value, 'UTF-8')) {
                return $this->jsonError(422, 'Revisa los caracteres de los datos ingresados.');
            }
        }

        $validation = service('validation');
        $validation->setRules([
            'nombre_colegio'       => 'required|string|max_length[150]',
            'nombre_seccion'       => 'permit_empty|string|max_length[120]',
            'cantidad_estudiantes' => 'required|integer|greater_than[0]|less_than_equal_to[10000]',
            'persona_encargada'    => 'required|string|max_length[150]',
            'telefono_contacto'    => 'required|string|max_length[30]',
        ]);

        if (! $validation->run($datos)) {
            return $this->jsonError(422, 'Revisa los datos ingresados.', $validation->getErrors());
        }

        if (! preg_match('/^[0-9+().\s-]{7,30}$/', $datos['telefono_contacto'])) {
            return $this->jsonError(422, 'Ingresa un teléfono o WhatsApp válido.');
        }

        foreach (['nombre_colegio', 'nombre_seccion', 'persona_encargada'] as $campo) {
            if ($this->contienePalabraProhibida($datos[$campo])) {
                return $this->jsonError(422, 'La solicitud contiene lenguaje no permitido.');
            }
        }

        $servicios = $this->serviciosSeleccionados($this->request->getPost('servicios'));
        if ($servicios === []) {
            return $this->jsonError(422, 'Selecciona al menos un servicio disponible.');
        }

        $recaptcha = $this->verificarRecaptcha();
        if ($recaptcha !== null) {
            return $recaptcha;
        }

        $cantidad = (int) $datos['cantidad_estudiantes'];
        $precioUnitario = array_sum(array_column($servicios, 'precio_unitario'));
        $total = round($precioUnitario * $cantidad, 2);
        if ($precioUnitario > 9999999999.99 || $total > 99999999.99) {
            return $this->jsonError(422, 'El total de la solicitud supera el máximo permitido.');
        }

        $model = new CotizacionModel();
        try {
            $id = $model->insert([
                'nombre_colegio'       => $datos['nombre_colegio'],
                'nombre_seccion'       => $datos['nombre_seccion'] !== '' ? $datos['nombre_seccion'] : null,
                'persona_encargada'    => $datos['persona_encargada'],
                'telefono_contacto'    => $datos['telefono_contacto'],
                'cantidad_estudiantes' => $cantidad,
                'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'precio_unitario'      => $precioUnitario,
                'total_seccion'        => $total,
                'estado'               => CotizacionModel::ESTADO_SOLICITUD,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Failed to persist a public quote request: ' . $exception->getMessage());

            return $this->jsonError(500, 'No se pudo guardar la solicitud. Inténtalo nuevamente.');
        }

        if (! $id) {
            log_message('error', 'Failed to save a public quote request: ' . json_encode($model->errors()));

            return $this->jsonError(500, 'No se pudo guardar la solicitud. Inténtalo nuevamente.');
        }

        $mensaje = implode("\n", [
            'Hola, deseo solicitar una cotización de Piero Producciones.',
            'Solicitud #' . $id,
            'Institución: ' . $datos['nombre_colegio'],
            'Promoción/sección: ' . ($datos['nombre_seccion'] ?: 'No especificada'),
            'Alumnos: ' . $cantidad,
            'Encargado/a: ' . $datos['persona_encargada'],
            'Teléfono: ' . $datos['telefono_contacto'],
            'Servicios: ' . implode(', ', array_column($servicios, 'nombre')),
        ]);

        helper('contacto');

        return $this->response->setJSON([
            'whatsapp_url' => enlace_wsp($mensaje),
            'csrf_hash'    => csrf_hash(),
        ]);
    }

    /**
     * @param array<string, string> $errors
     */
    private function jsonError(int $status, string $message, array $errors = []): \CodeIgniter\HTTP\ResponseInterface
    {
        $body = ['message' => $message, 'csrf_hash' => csrf_hash()];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    private function postString(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<array{nombre: string, precio_unitario: float}>
     */
    private function serviciosSeleccionados(mixed $input): array
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

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        $catalogo = (new ProductoServicioModel())
            ->where('estado', 1)
            ->whereIn('id', $ids)
            ->findAll();

        if (count($catalogo) !== count($ids)) {
            return [];
        }

        $servicios = [];
        foreach ($catalogo as $servicio) {
            $precio = (float) $servicio['precio_unitario'];
            if ($precio < 0 || $precio > 9999999999.99) {
                return [];
            }
            $servicios[] = [
                'nombre'          => (string) $servicio['nombre_item'],
                'precio_unitario' => round($precio, 2),
            ];
        }

        return $servicios;
    }

    private function contienePalabraProhibida(string $texto): bool
    {
        $normalizado = mb_strtolower($texto, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizado);
        if ($ascii !== false) {
            $normalizado = $ascii;
        }

        $palabras = implode('|', array_map(
            static fn (string $palabra): string => preg_quote($palabra, '/'),
            self::PALABRAS_PROHIBIDAS
        ));

        return preg_match('/(?<![a-z])(?:' . $palabras . ')(?![a-z])/i', $normalizado) === 1;
    }

    private function verificarRecaptcha(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $siteKey = trim((string) env('RECAPTCHA_SITE_KEY'));
        $secret = trim((string) env('RECAPTCHA_SECRET_KEY'));

        if ($siteKey === '' && $secret === '') {
            return null;
        }

        if ($siteKey === '' || $secret === '') {
            log_message('error', 'Google reCAPTCHA is not configured: RECAPTCHA_SITE_KEY and RECAPTCHA_SECRET_KEY are required.');

            return $this->jsonError(503, 'El formulario no está disponible temporalmente. Inténtalo más tarde.');
        }

        $token = $this->postString('g-recaptcha-response');
        if ($token === '') {
            return $this->jsonError(422, 'Confirma que no eres un robot.');
        }

        try {
            $response = service('curlrequest')->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $this->request->getIPAddress(),
                ],
                'http_errors' => false,
                'timeout'     => 5,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Google reCAPTCHA verification failed: ' . $exception->getMessage());

            return $this->jsonError(503, 'No se pudo validar el formulario. Inténtalo más tarde.');
        }

        $result = json_decode($response->getBody(), true);
        $requestHost = parse_url('http://' . (string) $this->request->getServer('HTTP_HOST'), PHP_URL_HOST);
        $captchaHost = is_array($result) ? ($result['hostname'] ?? null) : null;
        if ($response->getStatusCode() !== 200 || ! is_array($result) || ($result['success'] ?? false) !== true
            || ! is_string($requestHost) || ! is_string($captchaHost)
            || strcasecmp($requestHost, $captchaHost) !== 0) {
            return $this->jsonError(422, 'No se pudo validar el reCAPTCHA. Inténtalo nuevamente.');
        }

        return null;
    }
}
