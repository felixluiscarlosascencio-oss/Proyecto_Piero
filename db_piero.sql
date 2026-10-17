DROP DATABASE IF EXISTS PIERO_SYSTEM;
CREATE DATABASE PIERO_SYSTEM
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE PIERO_SYSTEM;

-- 1. TABLAS PRINCIPALES Y CATALOGOS
-- Instituciones Educativas
CREATE TABLE instituciones (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nombre_institucion VARCHAR(150) NOT NULL,
    direccion         TEXT NULL,
    telefono          VARCHAR(20) NULL,
    correo            VARCHAR(100) NULL,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Clientes (Delegados, Tesoreras, Apoderados)
CREATE TABLE clientes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    documento      VARCHAR(20) NULL,
    celular        VARCHAR(20) NULL,
    correo         VARCHAR(100) NULL,
    observaciones  TEXT NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Personal interno de la empresa
CREATE TABLE personal (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    celular        VARCHAR(20) NULL,
    rol            ENUM('Encargado','Fotografo','Produccion') NOT NULL,
    estado         BOOLEAN NOT NULL DEFAULT TRUE,
    observaciones  TEXT NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuarios del sistema (PK: usuario_id)
CREATE TABLE usuarios (
    usuario_id     INT AUTO_INCREMENT PRIMARY KEY,
    personal_id    INT NULL,
    usuario        VARCHAR(50)  NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    rol            ENUM('Admin','Encargado','Fotografo','Produccion') NOT NULL,
    estado         BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_acceso  DATETIME NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_id) REFERENCES personal(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Catálogo de productos y servicios
CREATE TABLE productos_servicios (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    nombre_item       VARCHAR(100) NOT NULL,
    tipo              ENUM('Paquete Base','Servicio Adicional') NOT NULL DEFAULT 'Paquete Base',
    descripcion       TEXT NULL,
    precio_unitario   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado            BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. COTIZACIONES Y CONTRATOS
CREATE TABLE cotizaciones (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion   VARCHAR(50) NULL,
    cliente_id          INT NOT NULL,
    institucion_id      INT NOT NULL,
    nivel               VARCHAR(50) NULL,
    aula                VARCHAR(50) NULL,
    cantidad_alumnos    INT NOT NULL DEFAULT 1,
    subtotal            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado              ENUM('Borrador','Enviada','Aceptada','Rechazada') NOT NULL DEFAULT 'Borrador',
    fecha_cotizacion    DATE NOT NULL,
    fecha_vencimiento   DATE NULL,
    observaciones       TEXT NULL,
    usuario_id          INT NULL,
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id)       ON DELETE RESTRICT,
    FOREIGN KEY (institucion_id) REFERENCES instituciones(id)  ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)     REFERENCES usuarios(usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE cotizacion_detalles (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id         INT NOT NULL,
    producto_servicio_id  INT NOT NULL,
    descripcion           VARCHAR(255) NULL,
    cantidad              INT NOT NULL DEFAULT 1,
    precio_unitario       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (cotizacion_id)        REFERENCES cotizaciones(id)        ON DELETE CASCADE,
    FOREIGN KEY (producto_servicio_id) REFERENCES productos_servicios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE contratos (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    numero_contrato       VARCHAR(50) NULL,
    cotizacion_id         INT NULL UNIQUE,
    institucion_id        INT NOT NULL,
    cliente_id            INT NOT NULL,
    encargado_id          INT NULL,
    nivel                 VARCHAR(50) NULL,
    aula                  VARCHAR(50) NULL,
    cant_alumnos_inicial  INT NOT NULL DEFAULT 1,
    monto_total           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_pagado          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    monto_saldo           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_contrato        DATE NOT NULL,
    estado_contrato       VARCHAR(50) DEFAULT 'Activo',
    observaciones         TEXT NULL,
    creado_en             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id)  REFERENCES cotizaciones(id)  ON DELETE SET NULL,
    FOREIGN KEY (institucion_id) REFERENCES instituciones(id) ON DELETE RESTRICT,
    FOREIGN KEY (cliente_id)     REFERENCES clientes(id)      ON DELETE RESTRICT,
    FOREIGN KEY (encargado_id)   REFERENCES personal(id)      ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. ALUMNOS, RETIROS Y PEDIDOS
CREATE TABLE alumnos (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id               INT NOT NULL,
    codigo_alumno             VARCHAR(50) NULL,
    nombre_completo           VARCHAR(150) NOT NULL,
    genero                    ENUM('Masculino','Femenino') NULL,
    numero_lista              INT NULL,
    tipo_registro             ENUM('Regular','Tardio') NOT NULL DEFAULT 'Regular',
    fecha_incorporacion       DATE NULL,
    sesion_incorporacion_id   INT NULL,
    precio_aplicado           DECIMAL(10,2) NULL,
    incluir_en_cuadro         BOOLEAN NOT NULL DEFAULT TRUE,
    estado                    ENUM('Activo','Retirado') NOT NULL DEFAULT 'Activo',
    observaciones             VARCHAR(255) NULL,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Registro de retiros PK: alumno_id - Relación 1 a 1
CREATE TABLE retiros_alumno (
    alumno_id     INT NOT NULL PRIMARY KEY,
    fecha_retiro  DATE NOT NULL,
    motivo        VARCHAR(255) NOT NULL,
    etapa_retiro  VARCHAR(100) NOT NULL,
    observaciones VARCHAR(255) NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id    INT NULL,
    FOREIGN KEY (alumno_id)  REFERENCES alumnos(id)             ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE pedido_detalles (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id           INT NOT NULL,
    alumno_id             INT NULL,
    producto_servicio_id  INT NOT NULL,
    cantidad              INT NOT NULL DEFAULT 1,
    precio_aplicado       DECIMAL(10,2) NOT NULL,
    es_adicional          BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_solicitud       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones         TEXT NULL,
    FOREIGN KEY (contrato_id)          REFERENCES contratos(id)           ON DELETE RESTRICT,
    FOREIGN KEY (alumno_id)            REFERENCES alumnos(id)             ON DELETE RESTRICT,
    FOREIGN KEY (producto_servicio_id) REFERENCES productos_servicios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. SESIONES Y PRODUCCION
CREATE TABLE sesiones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id     INT NOT NULL,
    fotografo_id    INT NULL,
    fecha_sesion    DATE NULL,
    hora_inicio     TIME NULL,
    hora_fin        TIME NULL,
    lugar           VARCHAR(100) NULL,
    tipo_sesion     VARCHAR(50) NULL,
    estado_sesion   VARCHAR(50) NULL,
    observaciones   TEXT NULL,
    FOREIGN KEY (contrato_id)  REFERENCES contratos(id) ON DELETE RESTRICT,
    FOREIGN KEY (fotografo_id) REFERENCES personal(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- Asignar la FK de sesion_incorporacion en alumnos después de crear la tabla sesiones
ALTER TABLE alumnos 
ADD CONSTRAINT fk_alumno_sesion_inc 
FOREIGN KEY (sesion_incorporacion_id) REFERENCES sesiones(id) ON DELETE SET NULL;

CREATE TABLE sesion_alumnos (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id         INT NOT NULL,
    alumno_id         INT NOT NULL,
    estado            VARCHAR(50) NULL,
    hora_fotografia   TIME NULL,
    observaciones     VARCHAR(255) NULL,
    FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE RESTRICT,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)  ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE archivos_produccion (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id         INT NOT NULL UNIQUE,
    tipo_archivo        VARCHAR(50) NULL,
    direccion_archivos  TEXT NULL,
    estado_datos        VARCHAR(50) NULL,
    observaciones       TEXT NULL,
    fecha_registro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE seguimiento_produccion (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id     INT NOT NULL,
    etapa           VARCHAR(50) NOT NULL,
    fecha_inicio    DATETIME NULL,
    fecha_fin       DATETIME NULL,
    estado          VARCHAR(50) NULL,
    observaciones   VARCHAR(255) NULL,
    usuario_id      INT NULL,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id)           ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(usuario_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. PAGOS
CREATE TABLE pagos (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    contrato_id        INT NOT NULL,
    alumno_id          INT NULL,
    monto              DECIMAL(10,2) NOT NULL,
    fecha_pago         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_pago          VARCHAR(50) NULL,
    concepto           VARCHAR(150) NULL,
    numero_operacion   VARCHAR(100) NULL,
    comprobante        VARCHAR(100) NULL,
    observaciones      TEXT NULL,
    usuario_id         INT NULL,
    FOREIGN KEY (contrato_id) REFERENCES contratos(id)              ON DELETE RESTRICT,
    FOREIGN KEY (alumno_id)   REFERENCES retiros_alumno(alumno_id)  ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(usuario_id)       ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. USUARIO INICIAL (ADMIN)
-- ---------------------------------------------------------------------
//*INSERT INTO usuarios (usuario, password, rol, estado, creado_en)*/
//*VALUES ('admin', 'admin1234', 'Admin', 1, NOW());*/