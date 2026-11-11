<?php

namespace App\Controllers;

use App\Libraries\AdminAccess;
use App\Models\ServicioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use Throwable;

class ServicioAdminController extends BaseController
{
    private const MAX_IMAGE_SIZE = 5242880;
    private const MAX_IMAGES = 10;

    public function index()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        return view('admin/servicios/index', [
            'servicios' => (new ServicioModel())->orderBy('nombre_item', 'ASC')->findAll(),
        ]);
    }

    public function nuevo()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        return view('admin/servicios/form', ['servicio' => null]);
    }

    public function crear()
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        if (! $this->validarFormulario()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $imagenes = $this->guardarImagenes();
        if ($imagenes === null) {
            return redirect()->back()->withInput()->with('error', 'No se pudieron cargar las imágenes. Verifica que sean JPG, PNG o WebP de hasta 5 MB.');
        }

        $model = new ServicioModel();
        try {
            $id = $model->insert($this->datosServicio($imagenes));
        } catch (Throwable $exception) {
            $this->eliminarArchivos($imagenes);
            log_message('error', 'Failed to create a service: ' . $exception->getMessage());

            return redirect()->back()->withInput()->with('error', 'No se pudo guardar el servicio.');
        }
        if ($id === false) {
            $this->eliminarArchivos($imagenes);

            return redirect()->back()->withInput()->with('error', 'No se pudo guardar el servicio: ' . implode(' ', $model->errors()));
        }

        return redirect()->to(base_url('admin/servicios'))->with('mensaje', 'Servicio creado correctamente.');
    }

    public function editar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $servicio = (new ServicioModel())->find($id);
        if ($servicio === null) {
            return redirect()->to(base_url('admin/servicios'))->with('error', 'No se encontró el servicio.');
        }

        return view('admin/servicios/form', ['servicio' => $servicio]);
    }

    public function actualizar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $model = new ServicioModel();
        $servicio = $model->find($id);
        if ($servicio === null) {
            return redirect()->to(base_url('admin/servicios'))->with('error', 'No se encontró el servicio.');
        }
        if (! $this->validarFormulario()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $galeriaActual = $this->galeria($servicio['galeria_json'] ?? null);
        $eliminar = $this->request->getPost('eliminar_imagenes');
        $eliminar = is_array($eliminar) ? array_unique(array_map('intval', $eliminar)) : [];
        $galeria = [];
        $imagenesEliminadas = [];
        foreach ($galeriaActual as $indice => $imagen) {
            if (in_array($indice, $eliminar, true)) {
                $imagenesEliminadas[] = $imagen;
            } else {
                $galeria[] = $imagen;
            }
        }

        $imagenesNuevas = $this->guardarImagenes();
        if ($imagenesNuevas === null) {
            return redirect()->back()->withInput()->with('error', 'No se pudieron cargar las imágenes. Verifica que sean JPG, PNG o WebP de hasta 5 MB.');
        }
        $galeria = array_values(array_merge($galeria, $imagenesNuevas));
        if (count($galeria) > self::MAX_IMAGES) {
            $this->eliminarArchivos($imagenesNuevas);

            return redirect()->back()->withInput()->with('error', 'La galería admite un máximo de 10 imágenes.');
        }

        try {
            $actualizado = $model->update($id, $this->datosServicio($galeria));
        } catch (Throwable $exception) {
            $this->eliminarArchivos($imagenesNuevas);
            log_message('error', 'Failed to update service ' . $id . ': ' . $exception->getMessage());

            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar el servicio.');
        }
        if (! $actualizado) {
            $this->eliminarArchivos($imagenesNuevas);

            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar el servicio: ' . implode(' ', $model->errors()));
        }

        $this->eliminarArchivos($imagenesEliminadas);

        return redirect()->to(base_url('admin/servicios'))->with('mensaje', 'Servicio actualizado correctamente.');
    }

    public function eliminar(int $id)
    {
        if (! AdminAccess::isAdmin()) {
            return redirect()->to(base_url('admin/cotizaciones'))->with('error', 'No tienes permisos para realizar esta acción.');
        }

        $model = new ServicioModel();
        $servicio = $model->find($id);
        if ($servicio === null) {
            return redirect()->to(base_url('admin/servicios'))->with('error', 'No se encontró el servicio.');
        }

        try {
            if (! $model->delete($id)) {
                return redirect()->to(base_url('admin/servicios'))->with('error', 'No se pudo eliminar el servicio.');
            }
        } catch (Throwable $exception) {
            log_message('error', 'Failed to delete service ' . $id . ': ' . $exception->getMessage());

            return redirect()->to(base_url('admin/servicios'))->with('error', 'No se pudo eliminar el servicio porque está asociado a otros registros.');
        }

        $this->eliminarArchivos($this->galeria($servicio['galeria_json'] ?? null));

        return redirect()->to(base_url('admin/servicios'))->with('mensaje', 'Servicio eliminado correctamente.');
    }

    private function validarFormulario(): bool
    {
        return $this->validate([
            'nombre_item'        => 'required|string|min_length[2]|max_length[100]',
            'descripcion'        => 'permit_empty|string|max_length[16000]',
            'detalle_largo'      => 'permit_empty|string|max_length[16000]',
            'etiqueta_destacada' => 'permit_empty|string|max_length[100]',
            'precio_interno'     => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[99999999.99]',
        ]);
    }

    /**
     * @param list<string> $galeria
     * @return array<string, mixed>
     */
    private function datosServicio(array $galeria): array
    {
        $texto = function (string $campo): ?string {
            $valor = $this->request->getPost($campo);
            if (! is_string($valor)) {
                return null;
            }
            $valor = trim($valor);

            return $valor === '' ? null : $valor;
        };

        return [
            'nombre_item'        => trim((string) $this->request->getPost('nombre_item')),
            'descripcion'        => $texto('descripcion'),
            'detalle_largo'      => $texto('detalle_largo'),
            'galeria_json'       => json_encode($galeria, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'etiqueta_destacada' => $texto('etiqueta_destacada'),
            'precio_interno'     => number_format((float) $this->request->getPost('precio_interno'), 2, '.', ''),
        ];
    }

    /**
     * @return list<string>|null
     */
    private function guardarImagenes(): ?array
    {
        $archivos = $this->request->getFileMultiple('galeria') ?? [];
        $archivos = array_values(array_filter($archivos, static fn ($archivo): bool => $archivo instanceof UploadedFile && $archivo->getError() !== UPLOAD_ERR_NO_FILE));
        if (count($archivos) > self::MAX_IMAGES) {
            return null;
        }

        $destino = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'servicios';
        if (! is_dir($destino) && ! mkdir($destino, 0755, true) && ! is_dir($destino)) {
            log_message('error', 'Could not create the public service gallery directory.');

            return null;
        }

        $guardadas = [];
        foreach ($archivos as $archivo) {
            if (! $archivo->isValid()
                || $archivo->getSize() > self::MAX_IMAGE_SIZE
                || ! in_array($archivo->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                $this->eliminarArchivos($guardadas);

                return null;
            }

            $nombre = $archivo->getRandomName();
            try {
                $archivo->move($destino, $nombre);
            } catch (Throwable $exception) {
                log_message('error', 'Failed to move a service gallery image: ' . $exception->getMessage());
                $this->eliminarArchivos($guardadas);

                return null;
            }
            $guardadas[] = 'uploads/servicios/' . $nombre;
        }

        return $guardadas;
    }

    /**
     * @return list<string>
     */
    private function galeria(mixed $json): array
    {
        if (! is_string($json) || $json === '') {
            return [];
        }
        $imagenes = json_decode($json, true);

        return is_array($imagenes)
            ? array_values(array_filter($imagenes, static fn ($imagen): bool => is_string($imagen) && $imagen !== ''))
            : [];
    }

    /**
     * @param list<string> $imagenes
     */
    private function eliminarArchivos(array $imagenes): void
    {
        foreach ($imagenes as $imagen) {
            $prefijo = 'uploads/servicios/';
            if (! str_starts_with($imagen, $prefijo)) {
                continue;
            }
            $nombre = substr($imagen, strlen($prefijo));
            if ($nombre === '' || basename($nombre) !== $nombre) {
                continue;
            }
            $ruta = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'servicios' . DIRECTORY_SEPARATOR . $nombre;
            if (is_file($ruta) && ! unlink($ruta)) {
                log_message('error', 'Could not remove service gallery image: ' . $ruta);
            }
        }
    }
}
