<?php

namespace App\Controllers;

use App\Libraries\CatalogoServicios;
use App\Libraries\DocumentoCodigo;
use App\Models\CotizacionModel;
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
            'cantidad_estudiantes' => $this->postString('cantidad_estudiantes'),
            'persona_encargada'    => $this->postString('persona_encargada'),
            'telefono_contacto'    => $this->postString('telefono_contacto'),
            'distrito'             => $this->postString('distrito'),
            'provincia'            => $this->postString('provincia'),
            'nivel'                => $this->postString('nivel'),
            'tipo_servicio'        => $this->postString('tipo_servicio'),
            'observaciones'        => $this->postString('observaciones'),
        ];
        foreach ($datos as $value) {
            if (! mb_check_encoding($value, 'UTF-8')) {
                return $this->jsonError(422, 'Revisa los caracteres de los datos ingresados.');
            }
        }

        $validation = service('validation');
        $validation->setRules([
            'nombre_colegio'       => 'required|string|max_length[150]',
            'cantidad_estudiantes' => 'required|integer|greater_than[0]|less_than_equal_to[999]',
            'persona_encargada'    => 'required|string|max_length[150]',
            'telefono_contacto'    => 'required|string|exact_length[9]',
            'distrito'             => 'required|string|max_length[100]',
            'provincia'            => 'required|string|max_length[100]',
            'nivel'                => 'required|in_list[Inicial,Primaria,Secundaria]',
            'tipo_servicio'        => 'required|in_list[Anuario,Cuadros]',
            'observaciones'        => 'permit_empty|string|max_length[1000]',
        ]);

        if (! $validation->run($datos)) {
            return $this->jsonError(422, 'Revisa los datos ingresados.', $validation->getErrors());
        }

        if (! preg_match('/^[0-9]{9}$/', $datos['telefono_contacto'])) {
            return $this->jsonError(422, 'Ingresa un teléfono o WhatsApp válido.');
        }

        foreach (['nombre_colegio', 'persona_encargada', 'distrito', 'provincia', 'observaciones'] as $campo) {
            if ($this->contienePalabraProhibida($datos[$campo])) {
                return $this->jsonError(422, 'La solicitud contiene lenguaje no permitido.');
            }
        }

        $servicios = (new CatalogoServicios())->paraSolicitud($this->request->getPost('servicios'));
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
        $db = db_connect();
        $db->transBegin();
        try {
            $codigoSolicitud = DocumentoCodigo::siguiente(DocumentoCodigo::SOL);
            $id = $model->insert([
                'nombre_colegio'       => $datos['nombre_colegio'],
                'nombre_seccion'       => null,
                'persona_encargada'    => $datos['persona_encargada'],
                'telefono_contacto'    => $datos['telefono_contacto'],
                'distrito'             => $datos['distrito'],
                'provincia'            => $datos['provincia'],
                'nivel'                => $datos['nivel'],
                'tipo_servicio'        => $datos['tipo_servicio'],
                'observaciones'        => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
                'cantidad_estudiantes' => $cantidad,
                'servicios_json'       => json_encode($servicios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'precio_unitario'      => 0,
                'total_seccion'        => 0,
                'estado'               => CotizacionModel::ESTADO_BORRADOR,
                'estado_revision'      => CotizacionModel::REVISION_SIN_REVISAR,
                'codigo_solicitud'     => $codigoSolicitud,
                'version'              => 1,
            ]);
            if (! $id) {
                throw new \RuntimeException('The public request did not return an id.');
            }
            $db->table('eventos_comerciales')->insert([
                'cotizacion_id' => (int) $id,
                'contrato_id'   => null,
                'tipo'          => 'solicitud_recibida',
                'detalle'       => json_encode(['codigo' => $codigoSolicitud], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'usuario_id'    => null,
                'creado_en'     => date('Y-m-d H:i:s'),
            ]);
            if ($db->transStatus() === false) {
                throw new \RuntimeException('The public request transaction failed.');
            }
            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();
            log_message('error', 'Failed to persist a public quote request: ' . $exception->getMessage());

            return $this->jsonError(500, 'No se pudo guardar la solicitud. Inténtalo nuevamente.');
        }

        $mensaje = implode("\n", [
            'Hola, deseo solicitar una cotización de Piero Producciones.',
            'Solicitud ' . $codigoSolicitud,
            'Institución: ' . $datos['nombre_colegio'],
            'Distrito: ' . $datos['distrito'],
            'Provincia: ' . $datos['provincia'],
            'Nivel: ' . $datos['nivel'],
            'Servicio solicitado: ' . $datos['tipo_servicio'],
            'Alumnos: ' . $cantidad,
            'Encargado/a: ' . $datos['persona_encargada'],
            'Teléfono: ' . $datos['telefono_contacto'],
            'Servicios: ' . implode(', ', array_column($servicios, 'nombre')),
        ]);
        if ($datos['observaciones'] !== '') {
            $mensaje .= "\nObservaciones: " . $datos['observaciones'];
        }

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

        if ($response->getStatusCode() !== 200) {
            log_message('error', 'Google reCAPTCHA verification returned HTTP status ' . $response->getStatusCode() . '.');

            return $this->jsonError(503, 'No se pudo validar el formulario. Inténtalo más tarde.');
        }

        try {
            $result = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            log_message('error', 'Google reCAPTCHA verification returned invalid JSON: ' . $exception->getMessage());

            return $this->jsonError(503, 'No se pudo validar el formulario. Inténtalo más tarde.');
        }

        if (! is_array($result)) {
            log_message('error', 'Google reCAPTCHA returned an unexpected response.');

            return $this->jsonError(503, 'No se pudo interpretar la respuesta del reCAPTCHA. Inténtalo más tarde.');
        }

        $errorCodes = is_array($result['error-codes'] ?? null)
                ? array_filter($result['error-codes'], 'is_string')
                : [];

        if (array_intersect(['missing-input-secret', 'invalid-input-secret'], $errorCodes) !== []) {
            log_message('error', 'Google reCAPTCHA secret key was rejected. Error codes: ' . implode(', ', $errorCodes));

            return $this->jsonError(503, 'La clave secreta de reCAPTCHA no fue aceptada. Revisa la configuración del servidor.');
        }

        if (($result['success'] ?? false) !== true) {
            log_message('error', 'Google reCAPTCHA rejected the token. Error codes: ' . implode(', ', $errorCodes));

            return $this->jsonError(422, 'El reCAPTCHA venció o no se pudo validar. Marca la casilla nuevamente e inténtalo otra vez.');
        }

        return null;
    }
}
