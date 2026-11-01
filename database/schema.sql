CREATE DATABASE IF NOT EXISTS `motor_express_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `motor_express_db`;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('Administrador', 'Recepcionista', 'Mecánico') DEFAULT 'Recepcionista',
  `telefono` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
  `ultimo_acceso` DATETIME DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_documento` ENUM('DNI', 'RUC', 'Carnet Extranjería', 'Pasaporte') DEFAULT 'DNI',
  `numero_documento` VARCHAR(20) NOT NULL UNIQUE,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `placa` VARCHAR(15) NOT NULL UNIQUE,
  `marca` VARCHAR(50) NOT NULL,
  `modelo` VARCHAR(50) NOT NULL,
  `anio_fabricacion` INT NOT NULL,
  `color` VARCHAR(30) DEFAULT NULL,
  `tipo_combustible` ENUM('Gasolina', 'Diésel', 'GLP', 'GNV', 'Híbrido', 'Eléctrico') DEFAULT 'Gasolina',
  `kilometraje` INT DEFAULT 0,
  `vin_chasis` VARCHAR(50) DEFAULT NULL,
  `notas` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mecanicos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `dni` VARCHAR(15) NOT NULL UNIQUE,
  `telefono` VARCHAR(20) NOT NULL,
  `email` VARCHAR(120) DEFAULT NULL,
  `especialidad` ENUM('Motor y Transmisión', 'Frenos y Suspensión', 'Electricidad y Electrónica', 'Diagnóstico Computarizado', 'Alineación y Afinamiento', 'Chapa y Pintura', 'Mantenimiento General') DEFAULT 'Mantenimiento General',
  `estado` ENUM('Disponible', 'En Servicio', 'Vacaciones', 'Inactivo') DEFAULT 'Disponible',
  `fecha_ingreso` DATE DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `citas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_cita` VARCHAR(20) NOT NULL UNIQUE,
  `cliente_id` INT NOT NULL,
  `vehiculo_id` INT NOT NULL,
  `mecanico_id` INT DEFAULT NULL,
  `fecha_cita` DATE NOT NULL,
  `hora_cita` TIME NOT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `servicio_sugerido` VARCHAR(150) DEFAULT NULL,
  `estado` ENUM('Pendiente', 'Confirmada', 'En Proceso', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
  `observaciones` TEXT DEFAULT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mecanico_id`) REFERENCES `mecanicos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `categoria` ENUM('Mantenimiento Preventivo', 'Motor', 'Frenos', 'Suspensión', 'Sistema Eléctrico', 'Alineación y Balanceo', 'Diagnóstico Computarizado', 'Otros') DEFAULT 'Mantenimiento Preventivo',
  `descripcion` TEXT DEFAULT NULL,
  `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tiempo_estimado_min` INT DEFAULT 60,
  `estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `repuestos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `categoria` ENUM('Filtros', 'Fluidos y Aceites', 'Frenos', 'Suspensión', 'Motor', 'Sistema Eléctrico', 'Neumáticos', 'Accesorios') DEFAULT 'Filtros',
  `descripcion` TEXT DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `stock_minimo` INT NOT NULL DEFAULT 5,
  `precio_compra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `precio_venta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ubicacion_almacen` VARCHAR(50) DEFAULT 'Estante A1',
  `estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ordenes_trabajo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_orden` VARCHAR(25) NOT NULL UNIQUE,
  `cliente_id` INT NOT NULL,
  `vehiculo_id` INT NOT NULL,
  `mecanico_id` INT DEFAULT NULL,
  `cita_id` INT DEFAULT NULL,
  `fecha_ingreso` DATETIME NOT NULL,
  `fecha_estimada_entrega` DATETIME DEFAULT NULL,
  `fecha_cierre` DATETIME DEFAULT NULL,
  `kilometraje_ingreso` INT DEFAULT 0,
  `nivel_combustible` ENUM('Reserva', '1/4', '1/2', '3/4', 'Lleno') DEFAULT '1/2',
  `estado` ENUM('En espera', 'En diagnóstico', 'En reparación', 'Esperando repuestos', 'Finalizada', 'Entregada', 'Cancelada') DEFAULT 'En espera',
  `observaciones_cliente` TEXT DEFAULT NULL,
  `total_servicios` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_repuestos` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `costo_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_pagado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `saldo_pendiente` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mecanico_id`) REFERENCES `mecanicos`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`cita_id`) REFERENCES `citas`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orden_servicios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `servicio_id` INT NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `observaciones` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`servicio_id`) REFERENCES `servicios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orden_repuestos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `repuesto_id` INT NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`repuesto_id`) REFERENCES `repuestos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `diagnosticos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `vehiculo_id` INT NOT NULL,
  `mecanico_id` INT DEFAULT NULL,
  `fallas_detectadas` TEXT NOT NULL,
  `sistema_afectado` ENUM('Motor', 'Frenos', 'Transmisión', 'Suspensión', 'Eléctrico', 'Dirección', 'Escape', 'Refrigeración', 'General') DEFAULT 'General',
  `gravedad` ENUM('Leve', 'Moderada', 'Crítica') DEFAULT 'Moderada',
  `scanner_codigos_dtc` VARCHAR(255) DEFAULT NULL,
  `recomendaciones` TEXT DEFAULT NULL,
  `fecha_diagnostico` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mecanico_id`) REFERENCES `mecanicos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_pago` VARCHAR(25) NOT NULL UNIQUE,
  `orden_id` INT NOT NULL,
  `metodo_pago` ENUM('Efectivo', 'Tarjeta de Crédito', 'Tarjeta de Débito', 'Yape', 'Plin', 'Transferencia') NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `nro_operacion` VARCHAR(50) DEFAULT NULL,
  `comprobante_tipo` ENUM('Boleta', 'Factura', 'Nota de Venta', 'Recibo de Caja') DEFAULT 'Boleta',
  `comprobante_numero` VARCHAR(30) NOT NULL,
  `fecha_pago` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `observaciones` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes_trabajo`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;