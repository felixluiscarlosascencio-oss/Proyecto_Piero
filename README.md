# Piero Producciones

Aplicación de gestión para cotizaciones escolares, construida con PHP 8.4 y
CodeIgniter 4.7.5.

## Requisitos

- PHP 8.4 con las extensiones requeridas por CodeIgniter, Dompdf y la base de
  datos configurada.
- Composer.
- Base de datos configurada en `.env`.

## Instalación

```sh
composer install
php spark migrate
```

Configura `app.baseURL` y las credenciales de base de datos en `.env`. El
servidor web debe apuntar a `public/`.

## Cotizaciones

El cotizador público persiste cada solicitud en `solicitudes_cotizacion` y
prepara un mensaje estructurado de WhatsApp para que el cliente lo envíe al
número comercial configurado en `app/Helpers/contacto_helper.php`.
El formulario solicita institución, promoción/sección, alumnos, persona
encargada, teléfono y servicios. La validación del servidor incluye Google
reCAPTCHA v2, un campo trampa y una lista básica de lenguaje no permitido.
Configura `RECAPTCHA_SITE_KEY` y `RECAPTCHA_SECRET_KEY` en el entorno de PHP
antes de habilitar solicitudes públicas; sin ambas claves el formulario queda
deshabilitado y el servidor rechaza los envíos.

El catálogo público y las selecciones del panel usan `productos_servicios`.
Configura `precio_unitario` para cada servicio: los registros iniciales quedan
en cero porque no se deben asignar precios comerciales no confirmados. La
descripción ampliada se almacena en `detalle_largo` y la galería en
`galeria_json` como una lista de rutas de imágenes locales bajo `public/`.

El panel requiere autenticación y está disponible en `/admin/cotizaciones`.
El administrador también puede crear cotizaciones desde el panel usando los
mismos datos y catálogo que el formulario público. Las cotizaciones no se
editan: solo se pueden ver y eliminar, además de iniciar una transición
comercial. Los estados de cada cotización son:

1. `solicitud`: recibida y pendiente de revisión.
2. `aprobada_admin`: creada por el administrador; permite configurar la vigencia.
3. `pendiente`: cotización vigente hasta `vence_en`; el administrador puede
  descargar el PDF o confirmar al cliente.
4. `aprobado`: confirmada y vinculada a un contrato.

La vigencia se activa con un plazo de 1 a 365 días desde el detalle de la
cotización. La acción "Pasar a Contrato" crea un registro en `contratos`,
vinculado mediante `cotizacion_id` y copia los datos de institución, sección,
encargado, teléfono, alumnos y servicios. El administrador completa los datos
formales y la agenda desde el contrato. Solo el rol `Dueno` puede eliminar
contratos.
Los servicios, las fechas de sesiones y los detalles/cantidades de cuadros se
guardan como JSON editable en el contrato; la lista de estudiantes permanece en
`alumnos`.

Las cuentas nuevas registran su creador en `usuarios.creado_por`. Solo este
administrador puede eliminarlas desde el panel. Las cuentas antiguas, cuyo
creador queda desconocido, no son eliminables bajo esa regla. La eliminación de
contratos se reserva al rol `Dueno`.

Los cambios estructurales están en las migraciones recientes; aplica las
migraciones con `php spark migrate` antes de usar las nuevas funciones.

ARCHIVO .env
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = database.db
database.default.username = 
database.default.password = 
database.default.DBDriver = SQLite3
database.default.DBPrefix = 
database.default.pConnect = false
database.default.DBDebug = true
database.default.charset = utf8
database.default.DBCollat = utf8_general_ci
database.default.port = 3306