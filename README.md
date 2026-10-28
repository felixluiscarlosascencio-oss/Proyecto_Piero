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
Los estados de cada cotización son:

1. `solicitud`: recibida y pendiente de revisión.
2. `aprobada_admin`: precios revisados y aprobados; habilita el PDF.
3. `pendiente`: PDF generado y cotización vigente para el cliente.
4. `aprobado`: confirmada por el cliente; permite coordinar producción.

La vigencia se indica al generar el PDF. La estructura de la tabla de este flujo
se gestiona exclusivamente mediante la migración de CodeIgniter.

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