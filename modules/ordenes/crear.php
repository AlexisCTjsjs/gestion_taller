<?php
$pageTitle = 'Generar Nueva Orden de Trabajo';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$citaId = (int)($_GET['cita_id'] ?? 0);
$citaData = null;

if ($citaId > 0) {
    $stmtCita = $pdo->prepare("SELECT * FROM citas WHERE id = :id");
    $stmtCita->execute([':id' => $citaId]);
    $citaData = $stmtCita->fetch();
}

$clientes = $pdo->query("SELECT id, nombres, apellidos, numero_documento FROM clientes ORDER BY nombres ASC")->fetchAll();
$vehiculos = $pdo->query("SELECT id, cliente_id, placa, marca, modelo, kilometraje FROM vehiculos ORDER BY placa ASC")->fetchAll();
$mecanicos = $pdo->query("SELECT id, nombres, apellidos, especialidad, estado FROM mecanicos WHERE estado != 'Inactivo' ORDER BY nombres ASC")->fetchAll();
$servicios = $pdo->query("SELECT id, codigo, nombre, categoria, precio FROM servicios WHERE estado = 'Activo' ORDER BY categoria ASC, nombre ASC")->fetchAll();
$repuestos = $pdo->query("SELECT id, codigo, nombre, categoria, stock, precio_venta FROM repuestos WHERE estado = 'Activo' AND stock > 0 ORDER BY categoria ASC, nombre ASC")->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
            Generar Orden de Trabajo (OT)
        </h2>
        <p>Apertura formal de reparación: recepción del vehículo, diagnóstico de fallas, mano de obra y repuestos.</p>
    </div>
    <div class="page-actions">
        <a href="index.php" class="btn btn-secondary">
            &larr; Volver al Listado
        </a>
    </div>
</div>

<form method="POST" action="orden_actions.php?action=crear" id="formNuevaOT">
    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
    <?php if ($citaId): ?>
        <input type="hidden" name="cita_id" value="<?= $citaId ?>">
    <?php endif; ?>

    <div class="table-card" style="margin-bottom: 1.5rem;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>1. Recepción del Vehículo y Cliente</h3>
                <p>Identificación de la unidad, kilometraje y asignación técnica inicial</p>
            </div>
        </div>
        <div style="padding: 1.5rem;">
            <div class="form-grid" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label>Propietario / Cliente: *</label>
                    <select name="cliente_id" id="ot_cliente_select" class="form-control" required onchange="filtrarVehiculosOT(this.value)">
                        <option value="">-- Seleccionar Cliente --</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($citaData && $citaData['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= sanitize($c['nombres'] . ' ' . $c['apellidos']) ?> (Doc: <?= sanitize($c['numero_documento']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vehículo / Placa: *</label>
                    <select name="vehiculo_id" id="ot_vehiculo_select" class="form-control" required onchange="actualizarKmSugerido(this)">
                        <option value="">-- Primero seleccione cliente --</option>
                        <?php foreach ($vehiculos as $v): ?>
                            <option value="<?= $v['id'] ?>" data-cliente="<?= $v['cliente_id'] ?>" data-km="<?= $v['kilometraje'] ?>" <?= ($citaData && $citaData['vehiculo_id'] == $v['id']) ? 'selected' : '' ?>>
                                <?= sanitize($v['placa'] . ' - ' . $v['marca'] . ' ' . $v['modelo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mecánico Responsable: *</label>
                    <select name="mecanico_id" class="form-control" required>
                        <option value="">-- Seleccionar Mecánico Especialista --</option>
                        <?php foreach ($mecanicos as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($citaData && $citaData['mecanico_id'] == $m['id']) ? 'selected' : '' ?>>
                                <?= sanitize($m['nombres'] . ' ' . $m['apellidos']) ?> (<?= sanitize($m['especialidad']) ?>) - [<?= $m['estado'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label>Kilometraje de Ingreso (Km): *</label>
                    <input type="number" name="kilometraje_ingreso" id="km_ingreso" class="form-control" value="0" min="0" required>
                </div>
                <div class="form-group">
                    <label>Nivel de Combustible: *</label>
                    <select name="nivel_combustible" class="form-control" required>
                        <option value="Reserva">Reserva (Luz encendida)</option>
                        <option value="1/4">1/4 de Tanque</option>
                        <option value="1/2" selected>1/2 de Tanque</option>
                        <option value="3/4">3/4 de Tanque</option>
                        <option value="Lleno">Tanque Lleno</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha Estimada de Entrega:</label>
                    <input type="datetime-local" name="fecha_estimada_entrega" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+1 day')) ?>">
                </div>
                <div class="form-group">
                    <label>Estado Inicial de la OT:</label>
                    <select name="estado" class="form-control">
                        <option value="En espera">En espera de turno</option>
                        <option value="En diagnóstico" selected>En diagnóstico técnico</option>
                        <option value="En reparación">En reparación directa</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Motivo de Ingreso / Declaración del Cliente: *</label>
                <textarea name="observaciones_cliente" class="form-control" placeholder="Describa el síntoma o motivo manifestado por el conductor..." required><?= $citaData ? sanitize($citaData['motivo']) : '' ?></textarea>
            </div>
        </div>
    </div>

    <div class="table-card" style="margin-bottom: 1.5rem;">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>2. Diagnóstico Técnico de Fallas Mecánicas</h3>
                <p>Registro de inspección computarizada, códigos DTC y recomendaciones mecánicas</p>
            </div>
        </div>
        <div style="padding: 1.5rem;">
            <div class="form-grid" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label>Sistema Automotriz Afectado: *</label>
                    <select name="sistema_afectado" class="form-control" required>
                        <option value="General">Revisión General / Preventiva</option>
                        <option value="Motor">Motor / Combustión</option>
                        <option value="Frenos">Sistema de Frenos (Discos/Pastillas/ABS)</option>
                        <option value="Transmisión">Caja y Embrague / Transmisión</option>
                        <option value="Suspensión">Suspensión y Amortiguación</option>
                        <option value="Eléctrico">Sistema Eléctrico y Batería</option>
                        <option value="Dirección">Dirección Hidráulica/Electroasistida</option>
                        <option value="Refrigeración">Sistema de Refrigeración y Radiador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nivel de Gravedad / Riesgo: *</label>
                    <select name="gravedad" class="form-control" required>
                        <option value="Leve">Leve (Puede circular temporalmente)</option>
                        <option value="Moderada" selected>Moderada (Atención requerida)</option>
                        <option value="Crítica">Crítica (Riesgo inminente de avería severa)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Scanner Códigos DTC (OBD2):</label>
                    <input type="text" name="scanner_codigos_dtc" class="form-control" placeholder="Ej: P0300, P0101, C1201">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Fallas Detectadas en Diagnóstico: *</label>
                    <textarea name="fallas_detectadas" class="form-control" placeholder="Detalle de fallas mecánicas encontradas durante la inspección técnica..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Recomendaciones Técnicas del Especialista:</label>
                    <textarea name="recomendaciones" class="form-control" placeholder="Recomendaciones para el cliente y piezas a cambiar"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="table-card" style="margin-bottom: 0;">
            <div class="table-card-header">
                <div class="table-card-title">
                    <h3>3. Servicios de Mano de Obra</h3>
                    <p>Seleccione los servicios a realizar</p>
                </div>
            </div>
            <div style="padding: 1rem; max-height: 380px; overflow-y: auto;">
                <?php foreach ($servicios as $s): ?>
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--border-color); cursor: pointer;">
                        <input type="checkbox" name="servicios[]" value="<?= $s['id'] ?>" data-precio="<?= $s['precio'] ?>" class="srv-checkbox" onchange="calcularTotalesOT()" style="margin-top: 4px;">
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between;">
                                <strong><?= sanitize($s['nombre']) ?></strong>
                                <span style="font-weight: 700; color: var(--primary);"><?= formatMoney($s['precio']) ?></span>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= sanitize($s['categoria']) ?> | <?= $s['codigo'] ?></span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-card" style="margin-bottom: 0;">
            <div class="table-card-header">
                <div class="table-card-title">
                    <h3>4. Repuestos del Almacén</h3>
                    <p>Seleccione repuestos y cantidad requerida</p>
                </div>
            </div>
            <div style="padding: 1rem; max-height: 380px; overflow-y: auto;">
                <?php foreach ($repuestos as $r): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--border-color); gap: 0.5rem;">
                        <div style="flex: 1;">
                            <strong><?= sanitize($r['nombre']) ?></strong>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?= sanitize($r['categoria']) ?> | Stock: <strong><?= $r['stock'] ?></strong> | PU: <?= formatMoney($r['precio_venta']) ?>
                            </div>
                        </div>
                        <div style="width: 80px;">
                            <input type="number" name="repuestos[<?= $r['id'] ?>]" min="0" max="<?= $r['stock'] ?>" value="0" data-pu="<?= $r['precio_venta'] ?>" class="form-control rep-input" onchange="calcularTotalesOT()" style="padding: 0.35rem 0.5rem; text-align: center;">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="table-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
        <div style="padding: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <h3 style="color: white; font-size: 1.4rem;">Cálculo de Proforma y Presupuesto</h3>
                <p style="color: #94a3b8; font-size: 0.85rem;">Monto inicial sujeto a mano de obra y repuestos cargados a la orden</p>
                <div style="display: flex; gap: 2rem; margin-top: 1rem; font-size: 0.95rem;">
                    <div>Mano de Obra: <strong id="lblTotalServicios" style="color: #60a5fa;">S/ 0.00</strong></div>
                    <div>Repuestos: <strong id="lblTotalRepuestos" style="color: #f59e0b;">S/ 0.00</strong></div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.85rem; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.5px;">Costo Total Estimado:</div>
                <div id="lblCostoTotal" style="font-size: 2.2rem; font-weight: 800; color: #34d399; margin: 4px 0 12px 0;">S/ 0.00</div>
                <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1rem;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Aprobar y Emitir Orden de Trabajo
                </button>
            </div>
        </div>
    </div>
</form>

<script>
const vehiculosGlobalOT = <?= json_encode($vehiculos) ?>;

function filtrarVehiculosOT(clienteId) {
    const select = document.getElementById('ot_vehiculo_select');
    select.innerHTML = '<option value="">-- Seleccionar Vehículo --</option>';
    if (!clienteId) return;

    const filtrados = vehiculosGlobalOT.filter(v => v.cliente_id == clienteId);
    filtrados.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id;
        opt.textContent = `${v.placa} - ${v.marca} ${v.modelo}`;
        opt.dataset.km = v.kilometraje;
        select.appendChild(opt);
    });

    if (filtrados.length === 1) {
        select.selectedIndex = 1;
        actualizarKmSugerido(select);
    }
}

function actualizarKmSugerido(select) {
    const selectedOpt = select.options[select.selectedIndex];
    if (selectedOpt && selectedOpt.dataset.km) {
        document.getElementById('km_ingreso').value = selectedOpt.dataset.km;
    }
}

function calcularTotalesOT() {
    let totalServicios = 0.0;
    document.querySelectorAll('.srv-checkbox:checked').forEach(cb => {
        totalServicios += parseFloat(cb.dataset.precio || 0);
    });

    let totalRepuestos = 0.0;
    document.querySelectorAll('.rep-input').forEach(inp => {
        const cant = parseInt(inp.value || 0);
        if (cant > 0) {
            totalRepuestos += cant * parseFloat(inp.dataset.pu || 0);
        }
    });

    const costoTotal = totalServicios + totalRepuestos;

    document.getElementById('lblTotalServicios').textContent = 'S/ ' + totalServicios.toFixed(2);
    document.getElementById('lblTotalRepuestos').textContent = 'S/ ' + totalRepuestos.toFixed(2);
    document.getElementById('lblCostoTotal').textContent = 'S/ ' + costoTotal.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    const clienteSelect = document.getElementById('ot_cliente_select');
    if (clienteSelect.value && !document.getElementById('ot_vehiculo_select').value) {
        filtrarVehiculosOT(clienteSelect.value);
    }
    calcularTotalesOT();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
