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

Configura las credenciales de base de datos en `.env`. La URL base se detecta
automáticamente del dominio, proxy o túnel que recibe la petición, por lo que no
debes fijar `localhost`. Solo para forzar una URL fija (por ejemplo en un dominio
de producción) usa `app.autoDetectBaseURL = false` y `app.baseURL`. El servidor
web debe apuntar a `public/`.

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

## Configuración del entorno

Copia `.env.example` a `.env` y completa los valores. **Nunca subas `.env` al
repositorio.**

Variables importantes:

```dotenv
CI_ENVIRONMENT = production

# Dominio fijo en producción (recomendado). Si usas túneles/proxy, deja
# autoDetectBaseURL activo y configura app.allowedHostnames si es posible.
app.baseURL = 'https://tudominio.com/'
app.autoDetectBaseURL = false

RECAPTCHA_SITE_KEY = tu_site_key
RECAPTCHA_SECRET_KEY = tu_secret_key

database.default.database = database.db
database.default.DBDriver = SQLite3
database.default.DBDebug = false
```

Reglas de seguridad:

- La `RECAPTCHA_SECRET_KEY` es privada: no la compartas ni la escribas en el
  README, issues o capturas.
- En producción usa `CI_ENVIRONMENT = production` y
  `database.default.DBDebug = false`.
- La base SQLite (`writable/database.db`) contiene datos reales y está excluida
  de Git. Crea el esquema con `php spark migrate`.
- Para el primer administrador ejecuta el seeder con una contraseña propia:

```sh
SEED_ADMIN_PASSWORD="una-contraseña-larga-y-unica" php spark db:seed UsuarioSeeder
```

## Despliegue

1. `composer install --no-dev --optimize-autoloader`
2. Copia `.env.example` a `.env` y configura producción.
3. `php spark migrate`
4. Apunta el servidor web a `public/`.
5. Permisos de escritura solo para `writable/`.