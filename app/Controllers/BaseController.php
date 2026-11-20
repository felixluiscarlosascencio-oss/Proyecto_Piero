<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Helpers disponibles en todos los controladores y sus vistas.
     *
     * @var list<string>
     */
    protected $helpers = ['servicio'];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @param CLIRequest|IncomingRequest $request
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    protected function postString(string $field): string
    {
        $value = $this->request->getPost($field);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Devuelve una página 403 sin provocar redirecciones circulares.
     */
    protected function sinPermiso(string $mensaje = 'No tienes permisos para acceder a esta sección.'): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('admin/sin-permiso', ['mensaje' => $mensaje]));
    }
}
