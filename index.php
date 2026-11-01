<?php
$pageTitle = 'Dashboard General';
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

$stmtClientes = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE estado = 'Activo'");
$totalClientes = $stmtClientes->fetch()['total'] ?? 0;

$stmtVehiculos = $pdo->query("SELECT COUNT(*) as total FROM vehiculos");
$totalVehiculos = $stmtVehiculos->fetch()['total'] ?? 0;

$stmtPendientes = $pdo->query("SELECT COUNT(*) as total FROM ordenes_trabajo WHERE estado NOT IN ('Entregada', 'Cancelada')");
$totalPendientes = $stmtPendientes->fetch()['total'] ?? 0;

$stmtIngresos = $pdo->query("SELECT COALESCE(SUM(monto), 0) as total FROM pagos");
$totalIngresos = $stmtIngresos->fetch()['total'] ?? 0;

$stmtMetodos = $pdo->query("
    SELECT metodo_pago, COALESCE(SUM(monto), 0) as total 
    FROM pagos 
    GROUP BY metodo_pago
");
$metodosPagos = $stmtMetodos->fetchAll();

$stmtEstados = $pdo->query("
    SELECT estado, COUNT(*) as cantidad 
    FROM ordenes_trabajo 
    GROUP BY estado
");
$estadosOrdenes = $stmtEstados->fetchAll();

$stmtRecientes = $pdo->query("
    SELECT ot.*, 
    CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
    v.placa, v.marca, v.modelo,
    CONCAT(m.nombres, ' ', m.apellidos) as mecanico_nombre
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON ot.mecanico_id = m.id
    ORDER BY ot.id DESC LIMIT 5
");
$ordenesRecientes = $stmtRecientes->fetchAll();

$stmtCitasHoy = $pdo->query("
    SELECT c.*, 
    CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
    v.placa, v.marca, v.modelo
    FROM citas c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN vehiculos v ON c.vehiculo_id = v.id
    WHERE c.fecha_cita = CURDATE()
    ORDER BY c.hora_cita ASC LIMIT 5
");
$citasHoy = $stmtCitasHoy->fetchAll();
?>

<section class="dashboard-hero">
    <div class="hero-content">
        <span class="hero-badge">
            <span class="pulse-dot"></span> Gestión Automotriz de Alto Rendimiento
        </span>
        <h2 class="hero-title">¡Bienvenido al Taller, <?= sanitize($currentUser['nombre']) ?>!</h2>
        <p class="hero-desc">
            Panel de control centralizado de <strong>Motor Express</strong>. Supervise en tiempo real las órdenes de trabajo activas,
            el diagnóstico computarizado de fallas, inventario de repuestos y flujo de ingresos en caja.
        </p>
        <div class="hero-buttons">
            <a href="<?= BASE_URL ?>/modules/ordenes/crear.php" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Generar Orden de Trabajo
            </a>
            <a href="<?= BASE_URL ?>/modules/citas/index.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color:white; border-color: rgba(255,255,255,0.3);">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Ver Citas Programadas
            </a>
            <a href="<?= BASE_URL ?>/modules/pagos/index.php" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color:white; border-color: rgba(255,255,255,0.3);">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Cobrar (Yape / Tarjetas)
            </a>
        </div>
    </div>
</section>

<section class="stats-grid">
    <div class="stat-card stat-blue">
        <div class="stat-info">
            <span class="stat-label">Clientes Registrados</span>
            <h3 class="stat-value"><?= number_format($totalClientes) ?></h3>
            <p class="stat-sub">
                <a href="<?= BASE_URL ?>/modules/clientes/index.php">Ver cartera activa &rarr;</a>
            </p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
    </div>

    <div class="stat-card stat-green">
        <div class="stat-info">
            <span class="stat-label">Vehículos Atendidos</span>
            <h3 class="stat-value"><?= number_format($totalVehiculos) ?></h3>
            <p class="stat-sub">
                <a href="<?= BASE_URL ?>/modules/vehiculos/index.php">Historial de placas &rarr;</a>
            </p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="22" height="13" rx="2" ry="2"></rect><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"></path><circle cx="6" cy="12" r="2"></circle><circle cx="18" cy="12" r="2"></circle></svg>
        </div>
    </div>

    <div class="stat-card stat-amber">
        <div class="stat-info">
            <span class="stat-label">Reparaciones Pendientes</span>
            <h3 class="stat-value"><?= number_format($totalPendientes) ?></h3>
            <p class="stat-sub">
                <a href="<?= BASE_URL ?>/modules/ordenes/index.php?estado=pendientes">Órdenes en taller &rarr;</a>
            </p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
    </div>

    <div class="stat-card stat-purple">
        <div class="stat-info">
            <span class="stat-label">Ingresos Totales en Caja</span>
            <h3 class="stat-value"><?= formatMoney($totalIngresos) ?></h3>
            <p class="stat-sub">
                <a href="<?= BASE_URL ?>/modules/pagos/index.php">Ver transacciones &rarr;</a>
            </p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>
</section>

<div style="margin-bottom: 1rem;">
    <h3 style="font-size: 1.3rem;">Módulos Estratégicos del Taller Automotriz</h3>
    <p style="color: var(--text-muted); font-size: 0.875rem;">Operaciones clave para optimizar la productividad y control de costos en Motor Express.</p>
</div>

<section class="image-cards-grid">
    <div class="image-card">
        <div class="image-card-img-wrap">
            <img src="<?= BASE_URL ?>/assets/images/car_diagnostic.jpg" alt="Diagnóstico Computarizado" loading="lazy">
            <span class="image-card-tag">Diagnóstico OBD2</span>
        </div>
        <div class="image-card-body">
            <div>
                <h4>Diagnósticos y Fallas Mecánicas</h4>
                <p>Escaneo de computadoras ECU, lectura de códigos de falla DTC, verificación de inyección y recomendaciones técnicas para cada orden.</p>
            </div>
            <div class="image-card-footer">
                <span class="status-badge badge-info">Bahía 1 & 2 Activas</span>
                <a href="<?= BASE_URL ?>/modules/ordenes/index.php" class="btn btn-sm btn-secondary">Ver Diagnósticos &rarr;</a>
            </div>
        </div>
    </div>

    <div class="image-card">
        <div class="image-card-img-wrap">
            <img src="<?= BASE_URL ?>/assets/images/auto_spare_parts.jpg" alt="Almacén de Repuestos" loading="lazy">
            <span class="image-card-tag">Inventario & Stock</span>
        </div>
        <div class="image-card-body">
            <div>
                <h4>Control de Repuestos Utilizados</h4>
                <p>Gestión de pastillas cerámicas, discos de freno, aceites sintéticos Mobil 1, filtros y control riguroso de costos de piezas instaladas.</p>
            </div>
            <div class="image-card-footer">
                <span class="status-badge badge-warning"><?= $alerts['repuestos_bajo_stock'] ?> Bajo Stock</span>
                <a href="<?= BASE_URL ?>/modules/repuestos/index.php" class="btn btn-sm btn-secondary">Gestionar Stock &rarr;</a>
            </div>
        </div>
    </div>

    <div class="image-card">
        <div class="image-card-img-wrap">
            <img src="<?= BASE_URL ?>/assets/images/mechanic_work.jpg" alt="Mecánico Especialista" loading="lazy">
            <span class="image-card-tag">Mecánica de Precisión</span>
        </div>
        <div class="image-card-body">
            <div>
                <h4>Mecánicos por Especialidad</h4>
                <p>Distribución de trabajo según especialidad: Frenos y Suspensión, Motor y Transmisión, Sistema Eléctrico y Afinamiento Electrónico.</p>
            </div>
            <div class="image-card-footer">
                <span class="status-badge badge-success">Personal Disponible</span>
                <a href="<?= BASE_URL ?>/modules/mecanicos/index.php" class="btn btn-sm btn-secondary">Ver Equipo &rarr;</a>
            </div>
        </div>
    </div>
</section>

<section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Ingresos por Método de Pago</h3>
                <p>Efectivo, Tarjeta de Crédito/Débito, Yape</p>
            </div>
        </div>
        <div style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <canvas id="paymentMethodsChart" width="300" height="220" style="max-width: 100%; height: auto;"></canvas>
            <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center; font-size: 0.8rem;">
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #732181; border-radius: 2px;"></span> Yape</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #2563eb; border-radius: 2px;"></span> Tarjetas</span>
                <span style="display: flex; align-items: center; gap: 4px;"><span style="width: 10px; height: 10px; background: #10b981; border-radius: 2px;"></span> Efectivo</span>
            </div>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Estado Actual de las Órdenes</h3>
                <p>Distribución de vehículos en el taller</p>
            </div>
        </div>
        <div style="padding: 1.5rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <canvas id="ordersStatusBarChart" width="340" height="220" style="max-width: 100%; height: auto;"></canvas>
        </div>
    </div>
</section>

<section class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Órdenes de Trabajo Recientes</h3>
            <p>Monitoreo del flujo de reparaciones y costos acumulados</p>
        </div>
        <div class="table-filters">
            <input type="text" class="table-search-input" placeholder="Filtrar orden o placa..." data-target-table="recentOrdersTable">
            <a href="<?= BASE_URL ?>/modules/ordenes/index.php" class="btn btn-sm btn-secondary">Ver Todas las Órdenes &rarr;</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table" id="recentOrdersTable">
            <thead>
                <tr>
                    <th>Código OT</th>
                    <th>Cliente</th>
                    <th>Vehículo / Placa</th>
                    <th>Mecánico Asignado</th>
                    <th>Fecha Ingreso</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Saldo Pendiente</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ordenesRecientes) > 0): ?>
                    <?php foreach ($ordenesRecientes as $ot): ?>
                        <tr>
                            <td><strong><?= sanitize($ot['codigo_orden']) ?></strong></td>
                            <td><?= sanitize($ot['cliente_nombre']) ?></td>
                            <td>
                                <span style="font-weight: 600; color: var(--primary);"><?= sanitize($ot['placa']) ?></span> 
                                <span style="font-size:0.75rem; color: var(--text-muted);">(<?= sanitize($ot['marca'] . ' ' . $ot['modelo']) ?>)</span>
                            </td>
                            <td><?= sanitize($ot['mecanico_nombre'] ?? 'Sin asignar') ?></td>
                            <td><?= formatDate($ot['fecha_ingreso']) ?></td>
                            <td><?= getStatusBadge($ot['estado']) ?></td>
                            <td><strong><?= formatMoney($ot['costo_total']) ?></strong></td>
                            <td>
                                <?php if ($ot['saldo_pendiente'] > 0): ?>
                                    <span style="color: var(--danger); font-weight: 700;"><?= formatMoney($ot['saldo_pendiente']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--success); font-weight: 600;">S/ 0.00 (Cancelado)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= BASE_URL ?>/modules/ordenes/detalle.php?id=<?= $ot['id'] ?>" class="btn-icon-action view" title="Ver Detalle Técnico">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/ordenes/imprimir.php?id=<?= $ot['id'] ?>" target="_blank" class="btn-icon-action" title="Imprimir Orden">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 2rem; color: var(--text-muted);">No hay órdenes de trabajo registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const paymentData = [
        <?php foreach ($metodosPagos as $m): ?>
            {
                label: '<?= addslashes($m['metodo_pago']) ?>',
                value: <?= (float)$m['total'] ?>,
                color: '<?= ($m['metodo_pago'] == 'Yape') ? '#732181' : (($m['metodo_pago'] == 'Efectivo') ? '#10b981' : '#2563eb') ?>'
            },
        <?php endforeach; ?>
    ];

    if (paymentData.length > 0) {
        drawDonutChart('paymentMethodsChart', paymentData, {
            centerText: '<?= formatMoney($totalIngresos) ?>',
            centerSub: 'Total Cobrado'
        });
    }

    const statusData = [
        <?php foreach ($estadosOrdenes as $est): ?>
            {
                label: '<?= substr(addslashes($est['estado']), 0, 8) ?>',
                value: <?= (int)$est['cantidad'] ?>,
                color: '<?= ($est['estado'] == 'Entregada') ? '#10b981' : (($est['estado'] == 'En reparación') ? '#f59e0b' : '#3b82f6') ?>'
            },
        <?php endforeach; ?>
    ];

    if (statusData.length > 0) {
        drawBarChart('ordersStatusBarChart', statusData);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
