

-- 1. TABLA DE USUARIOS
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('administrador', 'vendedor', 'bodeguero') NOT NULL DEFAULT 'vendedor',
  `telefono` VARCHAR(25) DEFAULT NULL,
  `estado` ENUM('activo', 'inactivo') DEFAULT 'activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA DE CLIENTES
CREATE TABLE `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rut_identificacion` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `correo` VARCHAR(120) DEFAULT NULL,
  `telefono` VARCHAR(25) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA DE PROVEEDORES
CREATE TABLE `proveedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rut` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(120) NOT NULL,
  `contacto` VARCHAR(100) DEFAULT NULL,
  `telefono` VARCHAR(25) DEFAULT NULL,
  `correo` VARCHAR(120) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DE PRODUCTOS (STOCK)
CREATE TABLE `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `categoria` VARCHAR(80) NOT NULL DEFAULT 'Ferretería General',
  `descripcion` TEXT DEFAULT NULL,
  `precio_compra` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `precio_venta` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `unidad` VARCHAR(20) NOT NULL DEFAULT 'unidad',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA DE MOVIMIENTOS DE KARDEX (ENTRADAS / SALIDAS / AJUSTES)
CREATE TABLE `movimientos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `producto_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `tipo` ENUM('entrada', 'salida', 'ajuste') NOT NULL,
  `cantidad` INT NOT NULL,
  `stock_anterior` INT NOT NULL,
  `stock_nuevo` INT NOT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mov_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA DE COMPRAS
CREATE TABLE `compras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(50) NOT NULL UNIQUE,
  `proveedor_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('recibida', 'pendiente', 'cancelada') DEFAULT 'recibida',
  `observaciones` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_compra_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_compra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_compras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `compra_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_detcompra_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detcompra_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA DE VENTAS
CREATE TABLE `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `folio` VARCHAR(50) NOT NULL UNIQUE,
  `cliente_id` INT DEFAULT NULL,
  `usuario_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` ENUM('Efectivo', 'Tarjeta de Débito', 'Tarjeta de Crédito', 'Transferencia') NOT NULL DEFAULT 'Efectivo',
  `estado` ENUM('completada', 'anulada') DEFAULT 'completada',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `venta_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_detventa_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detventa_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DATOS INICIALES (DEMO / SEMILLA)
-- Contraseña genérica 'admin123'
INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password`, `rol`, `telefono`) VALUES
(1, 'Administrador Tuerca', 'admin@latuerca.cl', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'administrador', '+56 9 8765 4321'),
(2, 'Vendedor Mostrador', 'vendedor@latuerca.cl', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'vendedor', '+56 9 7654 3210'),
(3, 'Encargado Bodega', 'bodega@latuerca.cl', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'bodeguero', '+56 9 6543 2109');

INSERT INTO `clientes` (`id`, `rut_identificacion`, `nombre`, `correo`, `telefono`, `direccion`) VALUES
(1, '11.111.111-1', 'Cliente Mostrador General', 'mostrador@latuerca.cl', '+56 9 0000 0000', 'Local Central'),
(2, '17.892.431-K', 'Constructora Los Robles SpA', 'compras@losrobles.cl', '+56 2 2456 7890', 'Av. Industrial 430'),
(3, '14.567.890-2', 'Carlos Miranda Tapia', 'carlos.m@gmail.com', '+56 9 9876 5432', 'Pasaje Las Heras 120');

INSERT INTO `proveedores` (`id`, `rut`, `nombre`, `contacto`, `telefono`, `correo`, `direccion`) VALUES
(1, '76.456.789-1', 'Distribuidora Central Ferretera', 'Ignacio Soto', '+56 2 2789 0000', 'contacto@centralferretera.cl', 'Av. Maipú 2100'),
(2, '77.123.456-8', 'Importadora Metales y Pernos del Sur', 'Camila Ortiz', '+56 2 2890 1111', 'ventas@pernosdelsur.cl', 'Camino a Melipilla 8840');

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `categoria`, `descripcion`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `unidad`) VALUES
(1, 'FER-001', 'Tuerca Hexagonal Acero Inox 3/8"', 'Fijaciones y Pernos', 'Tuerca hexagonal de alta resistencia paso estándar', 150.00, 350.00, 480, 50, 'unidad'),
(2, 'FER-002', 'Perno Coche Zincado 3/8 x 2"', 'Fijaciones y Pernos', 'Perno con cuello cuadrado con tuerca incluida', 280.00, 600.00, 220, 40, 'unidad'),
(3, 'HER-101', 'Martillo Carpintero Mango Fibra 16oz', 'Herramientas Manuales', 'Martillo con uña forjada templada y mango antideslizante', 4200.00, 8990.00, 18, 5, 'unidad'),
(4, 'HER-102', 'Taladro Percutor 750W Profesional', 'Herramientas Eléctricas', 'Taladro eléctrico con velocidad variable y reversa', 29000.00, 49990.00, 12, 3, 'unidad'),
(5, 'CON-201', 'Disco de Corte Metal 4 1/2" Extra Fino', 'Abrasivos y Corte', 'Disco de corte para esmeril angular 115mm', 650.00, 1490.00, 95, 20, 'unidad'),
(6, 'CON-202', 'Cinta Métrica 5 Metros de Impacto', 'Medición', 'Huincha de medir con freno magnético y caja engomada', 1800.00, 3990.00, 4, 10, 'unidad');

INSERT INTO `movimientos` (`producto_id`, `usuario_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `motivo`) VALUES
(1, 1, 'entrada', 500, 0, 500, 'Inventario inicial apertura'),
(2, 1, 'entrada', 250, 0, 250, 'Inventario inicial apertura'),
(3, 1, 'entrada', 20, 0, 20, 'Inventario inicial apertura'),
(4, 1, 'entrada', 15, 0, 15, 'Inventario inicial apertura'),
(5, 1, 'entrada', 100, 0, 100, 'Inventario inicial apertura'),
(6, 1, 'entrada', 10, 0, 10, 'Inventario inicial apertura');

INSERT INTO `ventas` (`id`, `folio`, `cliente_id`, `usuario_id`, `fecha`, `total`, `metodo_pago`, `estado`) VALUES
(1, 'BOL-2026-001', 1, 2, '2026-10-10', 7000.00, 'Efectivo', 'completada'),
(2, 'FAC-2026-001', 2, 2, '2026-10-11', 18000.00, 'Transferencia', 'completada');

INSERT INTO `detalle_ventas` (`venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 20, 350.00, 7000.00),
(2, 2, 30, 600.00, 18000.00);

-- Actualizar stock tras venta demo
UPDATE `productos` SET `stock` = 480 WHERE `id` = 1;
UPDATE `productos` SET `stock` = 220 WHERE `id` = 2;
INSERT INTO `movimientos` (`producto_id`, `usuario_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `motivo`) VALUES
(1, 2, 'salida', 20, 500, 480, 'Venta en mostrador BOL-2026-001'),
(2, 2, 'salida', 30, 250, 220, 'Venta con factura FAC-2026-001');

INSERT INTO `compras` (`id`, `folio`, `proveedor_id`, `usuario_id`, `fecha`, `total`, `estado`, `observaciones`) VALUES
(1, 'COM-2026-001', 1, 1, '2026-10-08', 58000.00, 'recibida', 'Reposición de taladros y herramientas de taller');

INSERT INTO `detalle_compras` (`compra_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 4, 2, 29000.00, 58000.00);
