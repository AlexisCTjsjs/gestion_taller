<?php
$pageTitle = 'Detalle de Orden de Trabajo';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', "Orden de trabajo no especificada.");
    header("Location: index.php");
    exit;
}

$stmtOT = $pdo->prepare("
    SELECT ot.*, 
           cl.id as cliente_id, cl.nombres, cl.apellidos, cl.numero_documento, cl.tipo_documento, cl.telefono, cl.email, cl.direccion,
           v.id as vehiculo_id, v.placa, v.marca, v.modelo, v.anio_fabricacion, v.color, v.tipo_combustible, v.kilometraje as vehiculo_km_actual, v.vin_chasis,
           m.nombres as mecanico_nombre, m.apellidos as mecanico_apellido, m.especialidad as mecanico_esp, m.telefono as mecanico_tel
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON ot.mecanico_id = m.id
    WHERE ot.id = :id
");
$stmtOT->execute([':id' => $id]);
$ot = $stmtOT->fetch();

if (!$ot) {
    setFlash('error', "La orden de trabajo no existe.");
    header("Location: index.php");
    exit;
}

$stmtDiag = $pdo->prepare("SELECT * FROM diagnosticos WHERE orden_id = :id");
$stmtDiag->execute([':id' => $id]);
$diagnostico = $stmtDiag->fetch();

$stmtServ = $pdo->prepare("
    SELECT os.*, s.codigo, s.nombre, s.categoria 
    FROM orden_servicios os
    JOIN servicios s ON os.servicio_id = s.id
    WHERE os.orden_id = :id
");
$stmtServ->execute([':id' => $id]);
$servicios = $stmtServ->fetchAll();

$stmtRep = $pdo->prepare("
    SELECT orp.*, r.codigo, r.nombre, r.categoria, r.ubicacion_almacen
    FROM orden_repuestos orp
    JOIN repuestos r ON orp.repuesto_id = r.id
    WHERE orp.orden_id = :id
");
$stmtRep->execute([':id' => $id]);
$repuestos = $stmtRep->fetchAll();

$stmtPagos = $pdo->prepare("SELECT * FROM pagos WHERE orden_id = :id ORDER BY id DESC");
$stmtPagos->execute([':id' => $id]);
$pagos = $stmtPagos->fetchAll();

$todosServicios = $pdo->query("SELECT id, nombre, precio FROM servicios WHERE estado = 'Activo' ORDER BY nombre ASC")->fetchAll();
$todosRepuestos = $pdo->query("SELECT id, nombre, stock, precio_venta FROM repuestos WHERE estado = 'Activo' AND stock > 0 ORDER BY nombre ASC")->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <span style="color: var(--primary);"><?= sanitize($ot['codigo_orden']) ?></span> - Detalle Técnico de Reparación
        </h2>
        <p>Estado actual: <?= getStatusBadge($ot['estado']) ?> | Ingresó el <?= formatDateTime($ot['fecha_ingreso']) ?></p>
    </div>
    <div class="page-actions">
        <a href="imprimir.php?id=<?= $ot['id'] ?>" target="_blank" class="btn btn-secondary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Imprimir Hoja de OT / Proforma
        </a>
        <a href="../pagos/index.php?orden_id=<?= $ot['id'] ?>" class="btn btn-success">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            Registrar Pago (Efectivo/Yape/Tarjetas)
        </a>
    </div>
</div>

<div class="table-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; background: #ffffff;">
    <form method="POST" action="orden_actions.php?action=cambiar_estado" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        <input type="hidden" name="orden_id" value="<?= $ot['id'] ?>">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <label style="font-weight: 700; font-size: 0.9rem; color: var(--secondary);">Transición de Estado en Taller:</label>
            <select name="nuevo_estado" class="form-control" style="width: auto; font-weight: 600;">
                <option value="En espera" <?= $ot['estado'] == 'En espera' ? 'selected' : '' ?>>En espera de turno</option>
                <option value="En diagnóstico" <?= $ot['estado'] == 'En diagnóstico' ? 'selected' : '' ?>>En diagnóstico</option>
                <option value="En reparación" <?= $ot['estado'] == 'En reparación' ? 'selected' : '' ?>>En reparación</option>
                <option value="Esperando repuestos" <?= $ot['estado'] == 'Esperando repuestos' ? 'selected' : '' ?>>Esperando repuestos</option>
                <option value="Finalizada" <?= $ot['estado'] == 'Finalizada' ? 'selected' : '' ?>>Finalizada (Listo para entrega)</option>
                <option value="Entregada" <?= $ot['estado'] == 'Entregada' ? 'selected' : '' ?>>Entregada al Cliente</option>
                <option value="Cancelada" <?= $ot['estado'] == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Actualizar Estado</button>
    </form>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Datos del Cliente</h3>
            </div>
        </div>
        <div style="padding: 1.25rem; font-size: 0.875rem; line-height: 1.6;">
            <div><strong>Nombre:</strong> <?= sanitize($ot['nombres'] . ' ' . $ot['apellidos']) ?></div>
            <div><strong>Documento:</strong> <?= sanitize($ot['tipo_documento'] . ' ' . $ot['numero_documento']) ?></div>
            <div><strong>Teléfono:</strong> <a href="tel:<?= sanitize($ot['telefono']) ?>">📞 <?= sanitize($ot['telefono']) ?></a></div>
            <div><strong>Correo:</strong> <?= sanitize($ot['email'] ?: 'No registrado') ?></div>
            <div><strong>Dirección:</strong> <?= sanitize($ot['direccion'] ?: 'No registrada') ?></div>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Vehículo en Bahía</h3>
            </div>
        </div>
        <div style="padding: 1.25rem; font-size: 0.875rem; line-height: 1.6;">
            <div><strong>Placa:</strong> <span style="background: #1e293b; color: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-family: monospace;"><?= sanitize($ot['placa']) ?></span></div>
            <div><strong>Marca / Modelo:</strong> <?= sanitize($ot['marca'] . ' ' . $ot['modelo']) ?> (Año <?= (int)$ot['anio_fabricacion'] ?>)</div>
            <div><strong>Combustible / Nivel:</strong> <?= sanitize($ot['tipo_combustible']) ?> | <strong>Nivel: <?= sanitize($ot['nivel_combustible']) ?></strong></div>
            <div><strong>Km Ingreso:</strong> <?= number_format((int)$ot['kilometraje_ingreso']) ?> Km</div>
            <div><strong>VIN / Chasis:</strong> <?= sanitize($ot['vin_chasis'] ?: 'No especificado') ?></div>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Especialista Asignado</h3>
            </div>
        </div>
        <div style="padding: 1.25rem; font-size: 0.875rem; line-height: 1.6;">
            <?php if ($ot['mecanico_nombre']): ?>
                <div><strong>Mecánico:</strong> <?= sanitize($ot['mecanico_nombre'] . ' ' . $ot['mecanico_apellido']) ?></div>
                <div><strong>Especialidad:</strong> <span class="status-badge badge-primary"><?= sanitize($ot['mecanico_esp']) ?></span></div>
                <div><strong>Contacto:</strong> <?= sanitize($ot['mecanico_tel']) ?></div>
                <div style="margin-top: 8px;"><strong>Fecha Entrega Est.:</strong> <?= formatDateTime($ot['fecha_estimada_entrega']) ?></div>
            <?php else: ?>
                <p style="color: var(--text-muted); font-style: italic;">Sin mecánico asignado actualmente.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="table-card" style="margin-bottom: 1.5rem;">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Diagnóstico de Fallas Mecánicas y Evaluación</h3>
            <p>Informe técnico generado para el vehículo <?= sanitize($ot['placa']) ?></p>
        </div>
        <?php if ($diagnostico): ?>
            <div><?= getStatusBadge($diagnostico['gravedad']) ?></div>
        <?php endif; ?>
    </div>
    <div style="padding: 1.5rem; font-size: 0.9rem;">
        <?php if ($diagnostico): ?>
            <div class="form-grid" style="margin-bottom: 1rem;">
                <div>
                    <strong>Sistema Automotriz Afectado:</strong>
                    <p style="color: var(--primary); font-weight: 600;"><?= sanitize($diagnostico['sistema_afectado']) ?></p>
                </div>
                <div>
                    <strong>Códigos de Falla OBD2 / DTC:</strong>
                    <p style="font-family: monospace; font-weight: 700; color: #dc2626;"><?= sanitize($diagnostico['scanner_codigos_dtc'] ?: 'Ningún código activo') ?></p>
                </div>
                <div>
                    <strong>Fecha de Diagnóstico:</strong>
                    <p><?= formatDateTime($diagnostico['fecha_diagnostico']) ?></p>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <strong>Fallas Mecánicas Detectadas:</strong>
                <p style="background: #f8fafc; padding: 0.75rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary); margin-top: 4px;">
                    <?= nl2br(sanitize($diagnostico['fallas_detectadas'])) ?>
                </p>
            </div>
            <?php if ($diagnostico['recomendaciones']): ?>
                <div>
                    <strong>Recomendaciones Técnicas del Mecánico:</strong>
                    <p style="background: #fffbeb; padding: 0.75rem; border-radius: var(--radius-md); border-left: 4px solid var(--accent); margin-top: 4px; color: #92400e;">
                        <?= nl2br(sanitize($diagnostico['recomendaciones'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: var(--text-muted); font-style: italic;">No se ha registrado diagnóstico formal para esta orden todavía.</p>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Servicios de Mano de Obra</h3>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="openModal('modalAgregarServicio')">+ Agregar</button>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($servicios) > 0): ?>
                        <?php foreach ($servicios as $s): ?>
                            <tr>
                                <td><strong><?= sanitize($s['nombre']) ?></strong></td>
                                <td><span class="status-badge badge-secondary"><?= sanitize($s['categoria']) ?></span></td>
                                <td><strong><?= formatMoney($s['precio']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; padding: 1rem; color: var(--text-muted);">Sin servicios cargados</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 700;">
                        <td colspan="2">Total Servicios:</td>
                        <td><?= formatMoney($ot['total_servicios']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 0;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Repuestos y Piezas Utilizadas</h3>
            </div>
            <button type="button" class="btn btn-sm btn-primary" onclick="openModal('modalAgregarRepuesto')">+ Agregar</button>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Repuesto</th>
                        <th>Cant.</th>
                        <th>P. Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($repuestos) > 0): ?>
                        <?php foreach ($repuestos as $r): ?>
                            <tr>
                                <td><strong><?= sanitize($r['nombre']) ?></strong></td>
                                <td><?= (int)$r['cantidad'] ?> unid.</td>
                                <td><?= formatMoney($r['precio_unitario']) ?></td>
                                <td><strong><?= formatMoney($r['subtotal']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 1rem; color: var(--text-muted);">Sin repuestos utilizados</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 700;">
                        <td colspan="3">Total Repuestos:</td>
                        <td><?= formatMoney($ot['total_repuestos']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Historial de Pagos y Liquidación (Efectivo / Tarjetas / Yape)</h3>
            <p>Comprobantes emitidos y saldo pendiente de cobro</p>
        </div>
        <div style="font-size: 1.1rem; font-weight: 800;">
            Total: <?= formatMoney($ot['costo_total']) ?> | 
            Pagado: <span style="color: var(--success);"><?= formatMoney($ot['monto_pagado']) ?></span> | 
            Saldo: <span style="color: var(--danger);"><?= formatMoney($ot['saldo_pendiente']) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>N° Recibo / Comprobante</th>
                    <th>Método de Pago</th>
                    <th>Monto Pagado</th>
                    <th>N° Operación</th>
                    <th>Fecha de Pago</th>
                    <th>Acción</th>
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
                                <?php if ($p['metodo_pago'] === 'Yape'): ?>
                                    <span style="background: #732181; color: white; padding: 2px 8px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.75rem;">Yape</span>
                                <?php elseif (strpos($p['metodo_pago'], 'Tarjeta') !== false): ?>
                                    <span class="status-badge badge-primary"><?= sanitize($p['metodo_pago']) ?></span>
                                <?php else: ?>
                                    <span class="status-badge badge-success"><?= sanitize($p['metodo_pago']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= formatMoney($p['monto']) ?></strong></td>
                            <td><?= sanitize($p['nro_operacion'] ?: '-') ?></td>
                            <td><?= formatDateTime($p['fecha_pago']) ?></td>
                            <td>
                                <a href="../pagos/comprobante.php?id=<?= $p['id'] ?>" target="_blank" class="btn-icon-action view" title="Imprimir Comprobante">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 1.5rem; color: var(--text-muted);">
                            No se han registrado pagos para esta orden de trabajo todavía.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalAgregarServicio">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Agregar Servicio a la Orden</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalAgregarServicio')">&times;</button>
        </div>
        <form method="POST" action="orden_actions.php?action=agregar_servicio">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="orden_id" value="<?= $ot['id'] ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Seleccionar Servicio: *</label>
                    <select name="servicio_id" id="modal_select_srv" class="form-control" required onchange="actualizarPrecioSrv(this)">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($todosServicios as $ts): ?>
                            <option value="<?= $ts['id'] ?>" data-precio="<?= $ts['precio'] ?>"><?= sanitize($ts['nombre']) ?> (<?= formatMoney($ts['precio']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Precio a Cobrar (S/): *</label>
                    <input type="number" step="0.01" min="0" name="precio" id="modal_input_srv_precio" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAgregarServicio')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agregar Servicio</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalAgregarRepuesto">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Cargar Repuesto del Almacén</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalAgregarRepuesto')">&times;</button>
        </div>
        <form method="POST" action="orden_actions.php?action=agregar_repuesto">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="orden_id" value="<?= $ot['id'] ?>">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Seleccionar Repuesto: *</label>
                    <select name="repuesto_id" id="modal_select_rep" class="form-control" required onchange="actualizarRepuestoMax(this)">
                        <option value="">-- Seleccionar Repuesto en Stock --</option>
                        <?php foreach ($todosRepuestos as $tr): ?>
                            <option value="<?= $tr['id'] ?>" data-stock="<?= $tr['stock'] ?>"><?= sanitize($tr['nombre']) ?> (Stock: <?= $tr['stock'] ?> | PU: <?= formatMoney($tr['precio_venta']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cantidad de Unidades: *</label>
                    <input type="number" min="1" max="100" name="cantidad" id="modal_input_rep_cant" class="form-control" value="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAgregarRepuesto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cargar Repuesto</button>
            </div>
        </form>
    </div>
</div>

<script>
function actualizarPrecioSrv(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.precio) {
        document.getElementById('modal_input_srv_precio').value = opt.dataset.precio;
    }
}

function actualizarRepuestoMax(select) {
    const opt = select.options[select.selectedIndex];
    if (opt && opt.dataset.stock) {
        const inp = document.getElementById('modal_input_rep_cant');
        inp.max = opt.dataset.stock;
        inp.value = 1;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
