# 🚗 Motor Express - Sistema de Gestión de Taller Mecánico

Sistema web integral desarrollado en **PHP** y **MySQL** para la administración completa de un taller mecánico automotriz: control de clientes, vehículos, mecánicos, inventario de repuestos, servicios, citas, órdenes de trabajo, facturación y reportes estadísticos.

---

## 🛠️ Tecnologías Utilizadas

- **Backend:** PHP 7.4+ / PHP 8.x (Vanilla con arquitectura modular y PDO)
- **Base de Datos:** MySQL / MariaDB (`motor_express_db`)
- **Frontend:** HTML5, CSS3, JavaScript Vanilla, Bootstrap Icons / FontAwesome
- **Gráficos y Estadísticas:** Chart.js
- **Modelado:** MySQL Workbench (`.mwb`)

---

## 🚀 Módulos y Funcionalidades

1. **Autenticación y Seguridad:**
   - Inicio de sesión y registro de usuarios con encriptación (`password_hash`).
   - Control de sesiones y roles (`Administrador`, `Recepcionista`, `Mecánico`).

2. **Gestión de Clientes y Vehículos:**
   - Directorio de clientes con validación de documentos (DNI, RUC, etc.).
   - Registro de vehículos vinculados con placa, marca, modelo, año, kilometraje y combustible.

3. **Catálogo de Servicios y Mecánicos:**
   - Especialidades de mecánicos, asignación y estado.
   - Tarifario y tiempos estimados de mano de obra y servicios mecánicos.

4. **Inventario y Repuestos:**
   - Control de stock, precios de compra/venta y alertas de bajo inventario.

5. **Citas y Órdenes de Trabajo:**
   - Agendamiento de citas y cambio de estados (Pendiente, En Proceso, Completado).
   - Generación de órdenes de trabajo con servicios y repuestos asociados.
   - Vista detallada e impresión en formato prolijo para el cliente.

6. **Facturación, Pagos y Comprobantes:**
   - Registro de pagos con cálculo de totales, subtotal e impuestos (IGV/IVA).
   - Impresión de comprobantes de pago.

7. **Dashboard y Reportes:**
   - Panel interactivo con métricas clave en tiempo real.
   - Gráficos interactivos de ingresos mensuales, estado de órdenes y servicios más solicitados.

---

## 📋 Requisitos e Instalación

### Requisitos:
- Servidor local como **XAMPP**, WampServer o Laragon.
- **Apache** y **MySQL** activos.
- Navegador web moderno.

### Pasos de Instalación:
1. Clonar el repositorio dentro del directorio raíz de su servidor web (por ejemplo `C:\xampp\htdocs\`):
   ```bash
   git clone https://github.com/AlexisCTjsjs/gestion_taller.git
   ```
2. Asegurar que MySQL esté iniciado en XAMPP.
3. Importar la base de datos automáticamente accediendo desde el navegador a:
   ```
   http://localhost/gestion_taller/database/setup_db.php
   ```
   *(O alternativamente importar manualmente `database/schema.sql` en phpMyAdmin).*
4. Iniciar la aplicación navegando a:
   ```
   http://localhost/gestion_taller/
   ```
5. Registrar un nuevo usuario administrador desde `register.php` o iniciar sesión.

---

## 📂 Estructura del Proyecto

```
gestion_taller/
├── assets/
│   ├── css/ (style.css, print.css)
│   ├── js/ (main.js, charts.js)
│   └── images/
├── config/
│   ├── auth.php
│   ├── database.php
│   └── helpers.php
├── database/
│   ├── schema.sql
│   ├── setup_db.php
│   └── taller_database.mwb
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── right_sidebar.php
│   └── footer.php
├── modules/
│   ├── citas/
│   ├── clientes/
│   ├── mecanicos/
│   ├── ordenes/
│   ├── pagos/
│   ├── reportes/
│   ├── repuestos/
│   ├── servicios/
│   └── vehiculos/
├── index.php
├── login.php
├── logout.php
├── register.php
└── README.md
```

---

## 📄 Licencia

Este proyecto es de código abierto bajo la licencia MIT.
