<?php
?>
</main> 

<footer class="main-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <h3>MOTOR <span>EXPRESS</span></h3>
            <p>Sistema Integral de Gestión de Taller Automotriz, Órdenes de Reparación, Control de Repuestos, Diagnóstico Computarizado y Caja.</p>
            <div style="margin-top: 1rem; font-size: 0.8rem; color: #cbd5e1;">
                <strong>Sede Principal:</strong> Av. Automotriz 450 - Lima, Perú
            </div>
        </div>

        <div class="footer-col">
            <h4>Módulos Operativos</h4>
            <ul class="footer-links">
                <li><a href="<?= BASE_URL ?>/modules/ordenes/index.php">Órdenes de Trabajo</a></li>
                <li><a href="<?= BASE_URL ?>/modules/citas/index.php">Citas de Mantenimiento</a></li>
                <li><a href="<?= BASE_URL ?>/modules/clientes/index.php">Gestión de Clientes</a></li>
                <li><a href="<?= BASE_URL ?>/modules/vehiculos/index.php">Registro de Vehículos</a></li>
                <li><a href="<?= BASE_URL ?>/modules/mecanicos/index.php">Especialistas Técnicos</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Almacén y Finanzas</h4>
            <ul class="footer-links">
                <li><a href="<?= BASE_URL ?>/modules/repuestos/index.php">Control de Repuestos</a></li>
                <li><a href="<?= BASE_URL ?>/modules/servicios/index.php">Catálogo de Servicios</a></li>
                <li><a href="<?= BASE_URL ?>/modules/pagos/index.php">Caja y Pagos (Yape/Tarjetas)</a></li>
                <li><a href="<?= BASE_URL ?>/modules/reportes/index.php">Reportes Gerenciales</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Soporte y Seguridad</h4>
            <div style="font-size: 0.825rem; line-height: 1.6;">
                <p>Consultas SQL Preparadas con <strong>PDO MySQL</strong>.</p>
                <p style="margin-top: 6px;">Autenticación con <code>password_hash()</code> y protección CSRF.</p>
                <div style="margin-top: 12px;">
                    <span class="footer-badge">
                        <span class="pulse-dot" style="background:#10b981;"></span>
                        MySQL: motor_express_db OK
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div>
            &copy; <?= date('Y') ?> <strong>Motor Express S.A.C.</strong> | Todos los derechos reservados.
        </div>
        <div>
            Desarrollado para <strong>SENATI - Carrera de Ingeniería de Software con Inteligencia Artificial</strong>
        </div>
    </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
</body>
</html>
