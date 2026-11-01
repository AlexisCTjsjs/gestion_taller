<?php
$pageTitle = 'Citas de Mantenimiento';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroEstado = trim($_GET['estado'] ?? '');

$stmtClientes = $pdo->query("SELECT id, nombres, apellidos, numero_documento FROM clientes ORDER BY nombres ASC");
$listaClientes = $stmtClientes->fetchAll();

$stmtVehiculos = $pdo->query("SELECT id, cliente_id, placa, marca, modelo FROM vehiculos ORDER BY placa ASC");
$listaVehiculos = $stmtVehiculos->fetchAll();

$stmtMecanicos = $pdo->query("SELECT id, nombres, apellidos, especialidad FROM mecanicos WHERE estado != 'Inactivo' ORDER BY nombres ASC");
$listaMecanicos = $stmtMecanicos->fetchAll();

$stmtServicios = $pdo->query("SELECT nombre, precio FROM servicios WHERE estado = 'Activo' ORDER BY nombre ASC");
$listaServicios = $stmtServicios->fetchAll();

$sql = "
    SELECT c.*, 
    CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
    cl.telefono as cliente_telefono,
    v.placa, v.marca, v.modelo,
    CONCAT(m.nombres, ' ', m.apellidos) as mecanico_nombre
    FROM citas c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN vehiculos v ON c.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON c.mecanico_id = m.id
";

$where = [];
$params = [];

if (!empty($buscar)) {
    $where[] = "(c.codigo_cita LIKE :b OR cl.nombres LIKE :b OR cl.apellidos LIKE :b OR v.placa LIKE :b OR c.motivo LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if (!empty($filtroEstado)) {
    $where[] = "c.estado = :est";
    $params[':est'] = $filtroEstado;
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY c.fecha_cita DESC, c.hora_cita DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Programación de Citas para Mantenimiento
        </h2>
        <p>Agenda técnica de revisiones preventivas, diagnósticos computarizados y mantenimientos programados.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevaCita')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Programar Nueva Cita
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Citas Registradas (<?= count($citas) ?>)</h3>
            <p>Control de fechas, turnos y conversión directa a Órdenes de Trabajo</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?estado=' + encodeURIComponent(this.value)">
                <option value="">-- Todos los Estados --</option>
                <option value="Pendiente" <?= $filtroEstado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="Confirmada" <?= $filtroEstado == 'Confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="En Proceso" <?= $filtroEstado == 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                <option value="Completada" <?= $filtroEstado == 'Completada' ? 'selected' : '' ?>>Completada</option>
                <option value="Cancelada" <?= $filtroEstado == 'Cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar código, placa, cliente..." data-target-table="citasTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="citasTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha & Hora</th>
                    <th>Cliente</th>
                    <th>Vehículo</th>
                    <th>Motivo / Solicitud</th>
                    <th>Mecánico Asignado</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($citas) > 0): ?>
                    <?php foreach ($citas as $c): ?>
                        <tr>
                            <td><strong><?= sanitize($c['codigo_cita']) ?></strong></td>
                            <td>
                                <div><strong><?= formatDate($c['fecha_cita']) ?></strong></div>
                                <span style="font-size:0.8rem; color: var(--primary); font-weight: 600;">🕒 <?= date('h:i A', strtotime($c['hora_cita'])) ?></span>
                            </td>
                            <td>
                                <strong><?= sanitize($c['cliente_nombre']) ?></strong>
                                <div style="font-size:0.75rem; color: var(--text-muted);">Tel: <?= sanitize($c['cliente_telefono']) ?></div>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: #1e293b; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-family: monospace;">
                                    <?= sanitize($c['placa']) ?>
                                </span>
                                <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($c['marca'] . ' ' . $c['modelo']) ?></div>
                            </td>
                            <td>
                                <div><?= sanitize($c['motivo']) ?></div>
                                <?php if ($c['servicio_sugerido']): ?>
                                    <div style="font-size:0.75rem; color: var(--text-muted);">Sugerido: <em><?= sanitize($c['servicio_sugerido']) ?></em></div>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($c['mecanico_nombre'] ?? 'Sin asignar') ?></td>
                            <td><?= getStatusBadge($c['estado']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($c['estado'] !== 'Completada' && $c['estado'] !== 'Cancelada'): ?>
                                        <a href="../ordenes/crear.php?cita_id=<?= $c['id'] ?>" class="btn-icon-action view" title="Generar Orden de Trabajo desde esta Cita">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                                        </a>
                                    <?php endif; ?>

                                    <button type="button" class="btn-icon-action edit" title="Editar Cita" onclick='editarCita(<?= json_encode($c) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>

                                    <?php if ($c['estado'] !== 'Cancelada'): ?>
                                        <button type="button" class="btn-icon-action delete" title="Cancelar Cita" onclick="confirmDelete('cita_actions.php?action=cancelar&id=<?= $c['id'] ?>', '¿Deseas cancelar esta cita de mantenimiento?')">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No hay citas de mantenimiento registradas con los filtros actuales.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevaCita">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Programar Cita para Mantenimiento</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevaCita')">&times;</button>
        </div>
        <form method="POST" action="cita_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Cliente Propietario: *</label>
                        <select name="cliente_id" id="cita_cliente_select" class="form-control" required onchange="filtrarVehiculosPorCliente(this.value, 'cita_vehiculo_select')">
                            <option value="">-- Seleccionar Cliente --</option>
                            <?php foreach ($listaClientes as $lc): ?>
                                <option value="<?= $lc['id'] ?>"><?= sanitize($lc['nombres'] . ' ' . $lc['apellidos']) ?> (Doc: <?= sanitize($lc['numero_documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vehículo a Atender: *</label>
                        <select name="vehiculo_id" id="cita_vehiculo_select" class="form-control" required>
                            <option value="">-- Primero seleccione cliente --</option>
                            <?php foreach ($listaVehiculos as $lv): ?>
                                <option value="<?= $lv['id'] ?>" data-cliente="<?= $lv['cliente_id'] ?>">
                                    <?= sanitize($lv['placa'] . ' - ' . $lv['marca'] . ' ' . $lv['modelo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Fecha de la Cita: *</label>
                        <input type="date" name="fecha_cita" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Hora Programada: *</label>
                        <input type="time" name="hora_cita" class="form-control" value="09:00" required>
                    </div>
                    <div class="form-group">
                        <label>Mecánico Sugerido:</label>
                        <select name="mecanico_id" class="form-control">
                            <option value="">-- Asignación Automática / Por definir --</option>
                            <?php foreach ($listaMecanicos as $lm): ?>
                                <option value="<?= $lm['id'] ?>"><?= sanitize($lm['nombres'] . ' ' . $lm['apellidos']) ?> (<?= sanitize($lm['especialidad']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Motivo de la Cita / Falla reportada: *</label>
                        <input type="text" name="motivo" class="form-control" placeholder="Ej: Mantenimiento preventivo por 50,000 Km" required>
                    </div>
                    <div class="form-group">
                        <label>Servicio Sugerido del Catálogo:</label>
                        <select name="servicio_sugerido" class="form-control">
                            <option value="">-- Seleccionar Servicio (Opcional) --</option>
                            <?php foreach ($listaServicios as $ls): ?>
                                <option value="<?= sanitize($ls['nombre']) ?>"><?= sanitize($ls['nombre']) ?> (<?= formatMoney($ls['precio']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Estado Inicial: *</label>
                        <select name="estado" class="form-control" required>
                            <option value="Pendiente">Pendiente de Confirmación</option>
                            <option value="Confirmada" selected>Confirmada con Cliente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observaciones Adicionales:</label>
                        <input type="text" name="observaciones" class="form-control" placeholder="Cliente solicita traer su propio lubricante, etc.">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevaCita')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agendar Cita</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarCita">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Editar Cita de Mantenimiento</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarCita')">&times;</button>
        </div>
        <form method="POST" action="cita_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_cita_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Cliente Propietario: *</label>
                        <select name="cliente_id" id="edit_cita_cliente_id" class="form-control" required onchange="filtrarVehiculosPorCliente(this.value, 'edit_cita_vehiculo_id')">
                            <?php foreach ($listaClientes as $lc): ?>
                                <option value="<?= $lc['id'] ?>"><?= sanitize($lc['nombres'] . ' ' . $lc['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vehículo: *</label>
                        <select name="vehiculo_id" id="edit_cita_vehiculo_id" class="form-control" required>
                            <?php foreach ($listaVehiculos as $lv): ?>
                                <option value="<?= $lv['id'] ?>" data-cliente="<?= $lv['cliente_id'] ?>">
                                    <?= sanitize($lv['placa'] . ' - ' . $lv['marca'] . ' ' . $lv['modelo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Fecha: *</label>
                        <input type="date" name="fecha_cita" id="edit_cita_fecha" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Hora: *</label>
                        <input type="time" name="hora_cita" id="edit_cita_hora" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Mecánico:</label>
                        <select name="mecanico_id" id="edit_cita_mecanico_id" class="form-control">
                            <option value="">-- Sin asignar --</option>
                            <?php foreach ($listaMecanicos as $lm): ?>
                                <option value="<?= $lm['id'] ?>"><?= sanitize($lm['nombres'] . ' ' . $lm['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Motivo: *</label>
                        <input type="text" name="motivo" id="edit_cita_motivo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Servicio Sugerido:</label>
                        <input type="text" name="servicio_sugerido" id="edit_cita_servicio" class="form-control">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Estado: *</label>
                        <select name="estado" id="edit_cita_estado" class="form-control" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Confirmada">Confirmada</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Observaciones:</label>
                        <input type="text" name="observaciones" id="edit_cita_obs" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarCita')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Cita</button>
            </div>
        </form>
    </div>
</div>

<script>
const vehiculosGlobal = <?= json_encode($listaVehiculos) ?>;

function filtrarVehiculosPorCliente(clienteId, selectElementId) {
    const select = document.getElementById(selectElementId);
    select.innerHTML = '<option value="">-- Seleccionar Vehículo --</option>';
    if (!clienteId) return;

    const filtrados = vehiculosGlobal.filter(v => v.cliente_id == clienteId);
    filtrados.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id;
        opt.textContent = `${v.placa} - ${v.marca} ${v.modelo}`;
        select.appendChild(opt);
    });
}

function editarCita(c) {
    document.getElementById('edit_cita_id').value = c.id;
    document.getElementById('edit_cita_cliente_id').value = c.cliente_id;
    filtrarVehiculosPorCliente(c.cliente_id, 'edit_cita_vehiculo_id');
    setTimeout(() => {
        document.getElementById('edit_cita_vehiculo_id').value = c.vehiculo_id;
    }, 50);
    document.getElementById('edit_cita_fecha').value = c.fecha_cita;
    document.getElementById('edit_cita_hora').value = c.hora_cita;
    document.getElementById('edit_cita_mecanico_id').value = c.mecanico_id || '';
    document.getElementById('edit_cita_motivo').value = c.motivo;
    document.getElementById('edit_cita_servicio').value = c.servicio_sugerido || '';
    document.getElementById('edit_cita_estado').value = c.estado;
    document.getElementById('edit_cita_obs').value = c.observaciones || '';
    openModal('modalEditarCita');
}

if (new URLSearchParams(window.location.search).get('action') === 'nueva') {
    openModal('modalNuevaCita');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
