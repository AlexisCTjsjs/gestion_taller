<?php
$pageTitle = 'Órdenes de Trabajo';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$sql = "
    SELECT ot.*, 
    CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
    cl.telefono as cliente_telefono,
    v.placa, v.marca, v.modelo,
    CONCAT(m.nombres, ' ', m.apellidos) as mecanico_nombre,
    m.especialidad as mecanico_esp
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON ot.mecanico_id = m.id
";

$where = [];
$params = [];

if (!empty($buscar)) {
    $where[] = "(ot.codigo_orden LIKE :b OR cl.nombres LIKE :b OR cl.apellidos LIKE :b OR v.placa LIKE :b OR m.nombres LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if ($filtroEstado === 'pendientes') {
    $where[] = "ot.estado NOT IN ('Entregada', 'Cancelada')";
} elseif (!empty($filtroEstado)) {
    $where[] = "ot.estado = :est";
    $params[':est'] = $filtroEstado;
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY ot.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordenes = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Órdenes de Trabajo & Reparaciones
        </h2>
        <p>Seguimiento integral del ciclo de reparación vehicular, diagnósticos, control de costos y repuestos.</p>
    </div>
    <div class="page-actions">
        <a href="crear.php" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Nueva Orden de Trabajo
        </a>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Registro de Órdenes (<?= count($ordenes) ?>)</h3>
            <p>Historial técnico y financiero con estado de avance en bahía</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?estado=' + encodeURIComponent(this.value)">
                <option value="">-- Todos los Estados --</option>
                <option value="pendientes" <?= $filtroEstado == 'pendientes' ? 'selected' : '' ?>>⚠️ Reparaciones Pendientes</option>
                <option value="En espera" <?= $filtroEstado == 'En espera' ? 'selected' : '' ?>>En espera</option>
                <option value="En diagnóstico" <?= $filtroEstado == 'En diagnóstico' ? 'selected' : '' ?>>En diagnóstico</option>
                <option value="En reparación" <?= $filtroEstado == 'En reparación' ? 'selected' : '' ?>>En reparación</option>
                <option value="Esperando repuestos" <?= $filtroEstado == 'Esperando repuestos' ? 'selected' : '' ?>>Esperando repuestos</option>
                <option value="Finalizada" <?= $filtroEstado == 'Finalizada' ? 'selected' : '' ?>>Finalizada</option>
                <option value="Entregada" <?= $filtroEstado == 'Entregada' ? 'selected' : '' ?>>Entregada al Cliente</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar OT, placa, cliente..." data-target-table="ordenesTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="ordenesTable">
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Ingreso</th>
                    <th>Vehículo</th>
                    <th>Cliente</th>
                    <th>Mecánico Asignado</th>
                    <th>Estado</th>
                    <th>Servicios</th>
                    <th>Repuestos</th>
                    <th>Total</th>
                    <th>Saldo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ordenes) > 0): ?>
                    <?php foreach ($ordenes as $o): ?>
                        <tr>
                            <td>
                                <a href="detalle.php?id=<?= $o['id'] ?>" style="font-weight: 700; color: var(--primary);">
                                    <?= sanitize($o['codigo_orden']) ?>
                                </a>
                            </td>
                            <td><?= formatDate($o['fecha_ingreso']) ?></td>
                            <td>
                                <span style="background: #1e293b; color: #fff; padding: 2px 7px; border-radius: 4px; font-weight: 700; font-family: monospace; font-size: 0.8rem;">
                                    <?= sanitize($o['placa']) ?>
                                </span>
                                <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($o['marca'] . ' ' . $o['modelo']) ?></div>
                            </td>
                            <td>
                                <div><strong><?= sanitize($o['cliente_nombre']) ?></strong></div>
                                <div style="font-size:0.75rem; color: var(--text-muted);">Tel: <?= sanitize($o['cliente_telefono']) ?></div>
                            </td>
                            <td>
                                <?php if ($o['mecanico_nombre']): ?>
                                    <div><strong><?= sanitize($o['mecanico_nombre']) ?></strong></div>
                                    <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($o['mecanico_esp']) ?></div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td><?= getStatusBadge($o['estado']) ?></td>
                            <td><?= formatMoney($o['total_servicios']) ?></td>
                            <td><?= formatMoney($o['total_repuestos']) ?></td>
                            <td><strong style="color: var(--secondary);"><?= formatMoney($o['costo_total']) ?></strong></td>
                            <td>
                                <?php if ($o['saldo_pendiente'] > 0): ?>
                                    <span style="color: var(--danger); font-weight: 700;"><?= formatMoney($o['saldo_pendiente']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--success); font-weight: 600;">S/ 0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="detalle.php?id=<?= $o['id'] ?>" class="btn-icon-action view" title="Ver Detalle y Diagnóstico">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                    </a>
                                    <a href="imprimir.php?id=<?= $o['id'] ?>" target="_blank" class="btn-icon-action" title="Imprimir Orden / Proforma">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                    </a>
                                    <?php if ($o['saldo_pendiente'] > 0): ?>
                                        <a href="../pagos/index.php?orden_id=<?= $o['id'] ?>" class="btn-icon-action edit" title="Cobrar Saldo">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron órdenes de trabajo registradas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
