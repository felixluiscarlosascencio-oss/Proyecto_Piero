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

El panel requiere autenticación y está disponible en `/admin/cotizaciones`.
El administrador también puede crear cotizaciones desde el panel. Los estados de
cada cotización son:

1. `solicitud`: recibida y pendiente de revisión.
2. `aprobada_admin`: precios revisados; permite configurar la vigencia.
3. `pendiente`: cotización vigente hasta `vence_en`; el administrador puede
  descargar el PDF o confirmar al cliente.
4. `aprobado`: confirmada y vinculada a un contrato.

La vigencia se activa con un plazo de 1 a 365 días. Al confirmar una cotización
vigente se crea un registro en `contratos`, vinculado mediante `cotizacion_id`.
Los servicios, las fechas de sesiones y los detalles/cantidades de cuadros se
guardan como JSON editable en el contrato; la lista de estudiantes permanece en
`alumnos`.

Las cuentas nuevas registran su creador en `usuarios.creado_por`. Solo este
administrador puede eliminarlas desde el panel. Las cuentas antiguas, cuyo
creador queda desconocido, no son eliminables bajo esa regla. La eliminación de
contratos se reserva al rol `Dueno`.

Los cambios estructurales están en la migración
`2026-10-30-000001_AddAccountOwnershipAndContractDetails`; aplica las migraciones
con `php spark migrate` antes de usar las nuevas funciones.

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