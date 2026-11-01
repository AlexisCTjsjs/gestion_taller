<?php
$pageTitle = 'Caja y Pagos de Clientes';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroMetodo = trim($_GET['metodo'] ?? '');
$preOrdenId = (int)($_GET['orden_id'] ?? 0);

$stmtOrdenesPendientes = $pdo->query("
    SELECT ot.id, ot.codigo_orden, ot.saldo_pendiente, ot.costo_total,
           cl.nombres, cl.apellidos, v.placa
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    WHERE ot.saldo_pendiente > 0
    ORDER BY ot.id DESC
");
$ordenesConSaldo = $stmtOrdenesPendientes->fetchAll();

$sql = "
    SELECT p.*, 
           ot.codigo_orden, ot.costo_total,
           CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
           cl.numero_documento as cliente_doc,
           v.placa, v.marca, v.modelo
    FROM pagos p
    JOIN ordenes_trabajo ot ON p.orden_id = ot.id
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
";

$where = [];
$params = [];

if (!empty($buscar)) {
    $where[] = "(p.codigo_pago LIKE :b OR p.comprobante_numero LIKE :b OR ot.codigo_orden LIKE :b OR v.placa LIKE :b OR cl.nombres LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if (!empty($filtroMetodo)) {
    $where[] = "p.metodo_pago = :m";
    $params[':m'] = $filtroMetodo;
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pagos = $stmt->fetchAll();

$totalCobrado = array_sum(array_column($pagos, 'monto'));
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
            Caja & Cobranzas de Clientes
        </h2>
        <p>Recepción de pagos en Efectivo, Tarjeta de Crédito, Tarjeta de Débito o Yape con emisión de comprobante.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-success" onclick="openModal('modalNuevoPago')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Registrar Nuevo Pago
        </button>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card stat-purple">
        <div class="stat-info">
            <span class="stat-label">Total Recaudado en Listado</span>
            <h3 class="stat-value"><?= formatMoney($totalCobrado) ?></h3>
            <p class="stat-sub">Ingresos registrados</p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-info">
            <span class="stat-label">Órdenes con Saldo Pendiente</span>
            <h3 class="stat-value"><?= count($ordenesConSaldo) ?></h3>
            <p class="stat-sub">Pendientes de cobro total</p>
        </div>
        <div class="stat-icon">
            <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Transacciones y Comprobantes (<?= count($pagos) ?>)</h3>
            <p>Registro de pagos por métodos autorizados: Efectivo, Tarjetas o Yape</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?metodo=' + encodeURIComponent(this.value)">
                <option value="">-- Todos los Métodos --</option>
                <option value="Yape" <?= $filtroMetodo == 'Yape' ? 'selected' : '' ?>>Yape</option>
                <option value="Tarjeta de Crédito" <?= $filtroMetodo == 'Tarjeta de Crédito' ? 'selected' : '' ?>>Tarjeta de Crédito</option>
                <option value="Tarjeta de Débito" <?= $filtroMetodo == 'Tarjeta de Débito' ? 'selected' : '' ?>>Tarjeta de Débito</option>
                <option value="Efectivo" <?= $filtroMetodo == 'Efectivo' ? 'selected' : '' ?>>Efectivo</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar comprobante, cliente..." data-target-table="pagosTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="pagosTable">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>N° Orden</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Método de Pago</th>
                    <th>N° Operación</th>
                    <th>Monto Pagado</th>
                    <th>Fecha & Hora</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pagos) > 0): ?>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($p['comprobante_numero']) ?></strong>
                                <span class="status-badge badge-secondary" style="margin-left: 4px;"><?= sanitize($p['comprobante_tipo']) ?></span>
                            </td>
                            <td>
                                <a href="../ordenes/detalle.php?id=<?= $p['orden_id'] ?>" style="font-weight: 700;">
                                    <?= sanitize($p['codigo_orden']) ?>
                                </a>
                            </td>
                            <td>
                                <div><strong><?= sanitize($p['cliente_nombre']) ?></strong></div>
                                <div style="font-size:0.75rem; color: var(--text-muted);">Doc: <?= sanitize($p['cliente_doc']) ?></div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; color: var(--primary);"><?= sanitize($p['placa']) ?></span>
                                <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($p['marca'] . ' ' . $p['modelo']) ?></div>
                            </td>
                            <td>
                                <?php if ($p['metodo_pago'] === 'Yape'): ?>
                                    <span style="background: #732181; color: white; padding: 3px 10px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.75rem;">
                                        📱 Yape
                                    </span>
                                <?php elseif (strpos($p['metodo_pago'], 'Tarjeta') !== false): ?>
                                    <span class="status-badge badge-primary">
                                        💳 <?= sanitize($p['metodo_pago']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-success">
                                        💵 <?= sanitize($p['metodo_pago']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($p['nro_operacion'] ?: '-') ?></td>
                            <td><strong style="color: var(--secondary); font-size: 1rem;"><?= formatMoney($p['monto']) ?></strong></td>
                            <td><?= formatDateTime($p['fecha_pago']) ?></td>
                            <td>
                                <a href="comprobante.php?id=<?= $p['id'] ?>" target="_blank" class="btn-icon-action view" title="Ver e Imprimir Comprobante Oficial">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron registros de pagos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoPago">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Registrar Pago de Reparación</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoPago')">&times;</button>
        </div>
        <form method="POST" action="pago_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Seleccionar Orden de Trabajo con Saldo: *</label>
                    <select name="orden_id" id="pago_orden_select" class="form-control" required onchange="cargarSaldoSugerido(this)">
                        <option value="">-- Seleccionar Orden --</option>
                        <?php foreach ($ordenesConSaldo as $ocs): ?>
                            <option value="<?= $ocs['id'] ?>" data-saldo="<?= $ocs['saldo_pendiente'] ?>" <?= ($preOrdenId == $ocs['id']) ? 'selected' : '' ?>>
                                <?= sanitize($ocs['codigo_orden']) ?> - <?= sanitize($ocs['nombres'] . ' ' . $ocs['apellidos']) ?> (Placa: <?= sanitize($ocs['placa']) ?>) - Saldo: <?= formatMoney($ocs['saldo_pendiente']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Método de Pago: *</label>
                        <select name="metodo_pago" id="pago_metodo" class="form-control" required onchange="toggleOperacionInfo(this.value)">
                            <option value="Efectivo">💵 Efectivo en Caja</option>
                            <option value="Yape" selected>📱 Yape (Código QR / BCP)</option>
                            <option value="Tarjeta de Débito">💳 Tarjeta de Débito (POS)</option>
                            <option value="Tarjeta de Crédito">💳 Tarjeta de Crédito (POS)</option>
                            <option value="Plin">📱 Plin (Interbank/BBVA/Scotiabank)</option>
                            <option value="Transferencia">🏦 Transferencia Bancaria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monto a Pagar (S/): *</label>
                        <input type="number" step="0.01" min="0.50" name="monto" id="pago_monto" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Tipo de Comprobante: *</label>
                        <select name="comprobante_tipo" class="form-control" required>
                            <option value="Boleta">Boleta Electrónica</option>
                            <option value="Factura">Factura Electrónica</option>
                            <option value="Recibo de Caja">Recibo de Caja</option>
                            <option value="Nota de Venta">Nota de Venta</option>
                        </select>
                    </div>
                    <div class="form-group" id="group_nro_operacion">
                        <label id="lbl_nro_operacion">N° de Operación / Ref. Yape: *</label>
                        <input type="text" name="nro_operacion" id="pago_nro_operacion" class="form-control" placeholder="Ej: YP-892134">
                    </div>
                </div>

                <div class="form-group">
                    <label>Observaciones del Pago:</label>
                    <input type="text" name="observaciones" class="form-control" placeholder="Cancelación de mano de obra o anticipo">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoPago')">Cancelar</button>
                <button type="submit" class="btn btn-success">Confirmar y Emitir Comprobante</button>
            </div>
        </form>
    </div>
</div>

<script>
function cargarSaldoSugerido(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.saldo) {
        document.getElementById('pago_monto').value = parseFloat(opt.dataset.saldo).toFixed(2);
    }
}

function toggleOperacionInfo(metodo) {
    const lbl = document.getElementById('lbl_nro_operacion');
    const inp = document.getElementById('pago_nro_operacion');
    if (metodo === 'Yape' || metodo === 'Plin') {
        lbl.textContent = 'Código / Ref. de Transferencia ' + metodo + ': *';
        inp.placeholder = 'Ej: YP-102948 o Ref. App';
        inp.required = true;
    } else if (metodo.includes('Tarjeta')) {
        lbl.textContent = 'N° Referencia Voucher POS: *';
        inp.placeholder = 'Ej: POS-VISA-48201';
        inp.required = true;
    } else {
        lbl.textContent = 'N° Operación (Opcional):';
        inp.placeholder = 'Recibo o ref.';
        inp.required = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('pago_orden_select');
    if (select.value) {
        cargarSaldoSugerido(select);
        openModal('modalNuevoPago');
    }
    toggleOperacionInfo(document.getElementById('pago_metodo').value);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
