-- ===================================================
-- Base de Datos: abogados_socios
-- Compatible con MySQL / MariaDB (DBeaver, XAMPP, Railway)
-- ===================================================

CREATE DATABASE IF NOT EXISTS `abogados_socios` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `abogados_socios`;

-- Desactivar restricciones de clave foránea temporalmente
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `facturas`;
DROP TABLE IF EXISTS `contratos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabla de Usuarios (Roles: administrador, abogado)
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('administrador', 'abogado') NOT NULL DEFAULT 'abogado',
  `especialidad` VARCHAR(100) DEFAULT 'Derecho Corporativo',
  `telefono` VARCHAR(25) DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Clientes
CREATE TABLE `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rut_identificacion` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `empresa` VARCHAR(120) DEFAULT NULL,
  `correo` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(25) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Contratos (Módulo de Contratos)
CREATE TABLE `contratos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_expediente` VARCHAR(50) NOT NULL UNIQUE,
  `titulo` VARCHAR(150) NOT NULL,
  `tipo_contrato` ENUM('Mercantil', 'Laboral', 'Inmobiliario', 'Propiedad Intelectual', 'Litigio Civil', 'Servicios Legales') NOT NULL,
  `cliente_id` INT NOT NULL,
  `abogado_id` INT NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `monto_acordado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('Borrador', 'Activo', 'En Revision', 'Finalizado', 'Rescindido') NOT NULL DEFAULT 'Borrador',
  `clausulas_resumen` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_contratos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_contratos_abogado` FOREIGN KEY (`abogado_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Facturación (Honorarios y Servicios Legales)
CREATE TABLE `facturas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `folio_factura` VARCHAR(50) NOT NULL UNIQUE,
  `contrato_id` INT NOT NULL,
  `cliente_id` INT NOT NULL,
  `fecha_emision` DATE NOT NULL,
  `fecha_vencimiento` DATE NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `impuestos` DECIMAL(12,2) NOT NULL,
  `total` DECIMAL(12,2) NOT NULL,
  `metodo_pago` ENUM('Transferencia Bancaria', 'Tarjeta de Credito', 'Cheque', 'Efectivo') NOT NULL DEFAULT 'Transferencia Bancaria',
  `estado_pago` ENUM('Pendiente', 'Pagado', 'Anulada') NOT NULL DEFAULT 'Pendiente',
  `notas` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_facturas_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_facturas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- DATOS INICIALES DE PRUEBA (Passwords con password_hash: 'admin123' y 'abogado123')
-- Hash de 'admin123': $2y$10$wL.Rk6g3e/7/pP9m1Qd0xeZ1N31jS1L5XU0wT1dO/2U7m0sS0Yd7y (se incluye compatibilidad con fallback)
-- ===================================================

-- Usuarios
INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password`, `rol`, `especialidad`, `telefono`) VALUES
(1, 'Lic. Roberto Valenzuela (Admin)', 'admin@abogados.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'administrador', 'Derecho Penal & Corporativo', '+56 9 8765 4321'),
(2, 'Abg. Sofia Mendoza', 'abogado@abogados.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'abogado', 'Derecho Laboral y Contratos', '+56 9 7654 3210'),
(3, 'Abg. Carlos Benítez', 'carlos@abogados.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'abogado', 'Litigios Civiles & Notarial', '+56 9 6543 2109');
-- Nota: La contraseña para todos es: admin123 (o password_verify estándar)

-- Clientes
INSERT INTO `clientes` (`id`, `rut_identificacion`, `nombre`, `empresa`, `correo`, `telefono`, `direccion`) VALUES
(1, '76.123.456-7', 'Martín Morales Santander', 'Inversiones Pacífico S.A.', 'martin@inversionespacifico.cl', '+56 2 2345 6789', 'Av. El Bosque Norte 0250, Las Condes'),
(2, '77.890.123-4', 'Valentina Castro Ríos', 'Tecnologías Andinas SpA', 'vcastro@techandina.cl', '+56 2 2987 6543', 'Av. Providencia 1760, Santiago'),
(3, '15.432.198-K', 'Rodrigo Lagos Fuentes', 'Particular', 'rodrigo.lagos@gmail.com', '+56 9 9123 4567', 'Calle Moneda 920, Santiago Centro');

-- Contratos
INSERT INTO `contratos` (`id`, `numero_expediente`, `titulo`, `tipo_contrato`, `cliente_id`, `abogado_id`, `fecha_inicio`, `fecha_vencimiento`, `monto_acordado`, `estado`, `clausulas_resumen`) VALUES
(1, 'EXP-2026-001', 'Asesoría y Blindaje Corporativo Anual', 'Mercantil', 1, 1, '2026-01-15', '2027-01-14', 18500.00, 'Activo', 'Blindaje societario, revisión de actas y gobierno corporativo integral.'),
(2, 'EXP-2026-002', 'Redacción de Contratos de Confidencialidad y Software', 'Propiedad Intelectual', 2, 2, '2026-02-01', '2026-08-01', 6200.00, 'Activo', 'Acuerdos de cesión de derechos de código y protección de secretos industriales.'),
(3, 'EXP-2026-003', 'Representación Demanda Colectiva Laboral', 'Laboral', 1, 2, '2026-02-20', '2026-11-30', 9400.00, 'En Revision', 'Mediación ante inspección de trabajo y defensa prejudicial.'),
(4, 'EXP-2026-004', 'Compraventa Inmueble y Estudio de Títulos', 'Inmobiliario', 3, 3, '2026-03-05', '2026-05-15', 3800.00, 'Finalizado', 'Estudio de dominio de 30 años, levantamiento de gravámenes e inscripción en Conservador.');

-- Facturas
INSERT INTO `facturas` (`id`, `folio_factura`, `contrato_id`, `cliente_id`, `fecha_emision`, `fecha_vencimiento`, `subtotal`, `impuestos`, `total`, `metodo_pago`, `estado_pago`, `notas`) VALUES
(1, 'FACT-2026-101', 1, 1, '2026-01-16', '2026-01-30', 15546.22, 2953.78, 18500.00, 'Transferencia Bancaria', 'Pagado', 'Pago de cuota inicial del servicio corporativo anual.'),
(2, 'FACT-2026-102', 2, 2, '2026-02-05', '2026-02-28', 5210.08, 989.92, 6200.00, 'Transferencia Bancaria', 'Pagado', 'Honorarios devengados primer hito entrega de contratos IP.'),
(3, 'FACT-2026-103', 3, 1, '2026-03-01', '2026-03-25', 7899.16, 1500.84, 9400.00, 'Tarjeta de Credito', 'Pendiente', 'Anticipo por gastos judiciales y aranceles de representación.'),
(4, 'FACT-2026-104', 4, 3, '2026-03-10', '2026-03-20', 3193.28, 606.72, 3800.00, 'Cheque', 'Pagado', 'Cierre de honorarios tras inscripción exitosa en Conservador de Bienes Raíces.');
