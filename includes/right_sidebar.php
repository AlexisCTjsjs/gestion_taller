<?php
$pdoSidebar = getDBConnection();

$stmtLow = $pdoSidebar->query("SELECT codigo, nombre, stock, stock_minimo FROM repuestos WHERE stock <= stock_minimo AND estado = 'Activo' LIMIT 4");
$lowStockItems = $stmtLow->fetchAll();

$stmtToday = $pdoSidebar->query("
    SELECT c.codigo_cita, c.hora_cita, c.motivo, cl.nombres, cl.apellidos, v.placa 
    FROM citas c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN vehiculos v ON c.vehiculo_id = v.id
    WHERE c.fecha_cita = CURDATE() AND c.estado != 'Cancelada'
    ORDER BY c.hora_cita ASC LIMIT 4
");
$todayAppointments = $stmtToday->fetchAll();
?>

<div class="drawer-overlay" id="drawerOverlay"></div>

<aside class="right-drawer" id="rightDrawer" aria-label="Menú Lateral Derecho">
    <div class="drawer-header">
        <h3>
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            Panel de Acciones
        </h3>
        <button class="drawer-close-btn" id="drawerCloseBtn" aria-label="Cerrar panel">&times;</button>
    </div>

    <div class="drawer-body">
        <div class="user-profile-card">
            <div class="user-avatar-circle">
                <?= strtoupper(substr($currentUser['nombre'], 0, 1) . substr($currentUser['apellido'], 0, 1)) ?>
            </div>
            <div class="user-info-text">
                <h4><?= sanitize($currentUser['nombre'] . ' ' . $currentUser['apellido']) ?></h4>
                <p><?= sanitize($currentUser['email']) ?></p>
                <span class="user-role-badge">Rol: <?= sanitize($currentUser['rol']) ?></span>
            </div>
        </div>

        <div>
            <div class="drawer-section-title">
                <span>Atajos Frecuentes</span>
            </div>
            <div class="drawer-shortcuts">
                <a href="<?= BASE_URL ?>/modules/ordenes/crear.php" class="shortcut-item">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                    <span>Nueva Orden</span>
                </a>
                <a href="<?= BASE_URL ?>/modules/citas/index.php?action=nueva" class="shortcut-item">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="12" y1="11" x2="12" y2="17"></line><line x1="9" y1="14" x2="15" y2="14"></line></svg>
                    <span>Agendar Cita</span>
                </a>
                <a href="<?= BASE_URL ?>/modules/pagos/index.php" class="shortcut-item">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                    <span>Registrar Pago</span>
                </a>
                <a href="<?= BASE_URL ?>/modules/clientes/index.php?action=nuevo" class="shortcut-item">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <span>Nuevo Cliente</span>
                </a>
            </div>
        </div>

        <div>
            <div class="drawer-section-title">
                <span>Alertas de Stock Crítico</span>
                <span class="status-badge badge-danger"><?= count($lowStockItems) ?></span>
            </div>
            <?php if (count($lowStockItems) > 0): ?>
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="drawer-alert-item drawer-alert-danger">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div>
                            <strong><?= sanitize($item['nombre']) ?></strong>
                            <div style="font-size:0.75rem;">Quedan solo <strong><?= $item['stock'] ?></strong> unid. (Mín: <?= $item['stock_minimo'] ?>)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div style="text-align:right;">
                    <a href="<?= BASE_URL ?>/modules/repuestos/index.php" style="font-size:0.785rem; font-weight:600;">Ver Inventario Completo &rarr;</a>
                </div>
            <?php else: ?>
                <div class="drawer-alert-item drawer-alert-info">
                    <span>Todos los repuestos tienen stock óptimo.</span>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <div class="drawer-section-title">
                <span>Citas de Mantenimiento Hoy</span>
                <span class="status-badge badge-warning"><?= count($todayAppointments) ?></span>
            </div>
            <?php if (count($todayAppointments) > 0): ?>
                <?php foreach ($todayAppointments as $app): ?>
                    <div class="drawer-alert-item drawer-alert-warning">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <div>
                            <strong><?= date('h:i A', strtotime($app['hora_cita'])) ?> - Placa: <?= sanitize($app['placa']) ?></strong>
                            <div style="font-size:0.75rem;"><?= sanitize($app['nombres'] . ' ' . $app['apellidos']) ?> (<?= sanitize($app['motivo']) ?>)</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="drawer-alert-item drawer-alert-info">
                    <span>No hay más citas programadas para hoy.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="drawer-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" onclick="return confirm('¿Deseas cerrar tu sesión en Motor Express?');">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Cerrar Sesión Segura
        </a>
    </div>
</aside>
