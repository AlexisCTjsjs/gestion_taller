<?php
$pageTitle = 'Gestión de Mecánicos';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroEspecialidad = trim($_GET['especialidad'] ?? '');

$sql = "
    SELECT m.*, 
    COUNT(ot.id) as total_ordenes_activas
    FROM mecanicos m
    LEFT JOIN ordenes_trabajo ot ON m.id = ot.mecanico_id AND ot.estado NOT IN ('Entregada', 'Cancelada')
";

$whereClauses = [];
$params = [];

if (!empty($buscar)) {
    $whereClauses[] = "(m.nombres LIKE :b OR m.apellidos LIKE :b OR m.dni LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if (!empty($filtroEspecialidad)) {
    $whereClauses[] = "m.especialidad = :esp";
    $params[':esp'] = $filtroEspecialidad;
}

if (count($whereClauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " GROUP BY m.id ORDER BY m.nombres ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mecanicos = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            Equipo de Mecánicos por Especialidad
        </h2>
        <p>Control del personal técnico, disponibilidad en bahías y áreas de competencia en Motor Express.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoMecanico')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Registrar Mecánico
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Técnicos Registrados (<?= count($mecanicos) ?>)</h3>
            <p>Especialistas calificados y órdenes de trabajo asignadas en proceso</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?especialidad=' + encodeURIComponent(this.value)">
                <option value="">-- Todas las Especialidades --</option>
                <option value="Motor y Transmisión" <?= $filtroEspecialidad == 'Motor y Transmisión' ? 'selected' : '' ?>>Motor y Transmisión</option>
                <option value="Frenos y Suspensión" <?= $filtroEspecialidad == 'Frenos y Suspensión' ? 'selected' : '' ?>>Frenos y Suspensión</option>
                <option value="Electricidad y Electrónica" <?= $filtroEspecialidad == 'Electricidad y Electrónica' ? 'selected' : '' ?>>Electricidad y Electrónica</option>
                <option value="Diagnóstico Computarizado" <?= $filtroEspecialidad == 'Diagnóstico Computarizado' ? 'selected' : '' ?>>Diagnóstico Computarizado</option>
                <option value="Mantenimiento General" <?= $filtroEspecialidad == 'Mantenimiento General' ? 'selected' : '' ?>>Mantenimiento General</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar mecánico o DNI..." data-target-table="mecanicosTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="mecanicosTable">
            <thead>
                <tr>
                    <th>Especialista</th>
                    <th>DNI</th>
                    <th>Teléfono</th>
                    <th>Especialidad Técnica</th>
                    <th>Estado de Disponibilidad</th>
                    <th>OTs en Proceso</th>
                    <th>Fecha Ingreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($mecanicos) > 0): ?>
                    <?php foreach ($mecanicos as $m): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                        <?= strtoupper(substr($m['nombres'], 0, 1) . substr($m['apellidos'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= sanitize($m['nombres'] . ' ' . $m['apellidos']) ?></strong>
                                        <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($m['email'] ?: 'Sin email') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= sanitize($m['dni']) ?></td>
                            <td>
                                <a href="tel:<?= sanitize($m['telefono']) ?>" style="color: inherit; text-decoration: none;">
                                    📞 <?= sanitize($m['telefono']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="status-badge badge-primary">
                                    🔧 <?= sanitize($m['especialidad']) ?>
                                </span>
                            </td>
                            <td><?= getStatusBadge($m['estado']) ?></td>
                            <td>
                                <?php if ($m['total_ordenes_activas'] > 0): ?>
                                    <span class="status-badge badge-warning"><?= $m['total_ordenes_activas'] ?> activa(s)</span>
                                <?php else: ?>
                                    <span class="status-badge badge-secondary">Sin carga actual</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatDate($m['fecha_ingreso']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn-icon-action edit" title="Editar Mecánico" onclick='editarMecanico(<?= json_encode($m) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="Eliminar Mecánico" onclick="confirmDelete('mecanico_actions.php?action=eliminar&id=<?= $m['id'] ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron mecánicos registrados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoMecanico">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Registrar Nuevo Mecánico Especialista</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoMecanico')">&times;</button>
        </div>
        <form method="POST" action="mecanico_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Nombres: *</label>
                        <input type="text" name="nombres" class="form-control" placeholder="Ej: David" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos: *</label>
                        <input type="text" name="apellidos" class="form-control" placeholder="Ej: Torres Benítez" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>DNI: *</label>
                        <input type="text" name="dni" class="form-control" placeholder="Ej: 46781290" required maxlength="12">
                    </div>
                    <div class="form-group">
                        <label>Teléfono: *</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="Ej: 993412587" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="email" class="form-control" placeholder="david.torres@motorexpress.pe">
                    </div>
                    <div class="form-group">
                        <label>Especialidad Principal: *</label>
                        <select name="especialidad" class="form-control" required>
                            <option value="Diagnóstico Computarizado">Diagnóstico Computarizado & Escaneo OBD2</option>
                            <option value="Frenos y Suspensión">Frenos y Suspensión</option>
                            <option value="Motor y Transmisión">Motor y Transmisión</option>
                            <option value="Electricidad y Electrónica">Electricidad y Electrónica</option>
                            <option value="Alineación y Afinamiento">Alineación y Afinamiento</option>
                            <option value="Chapa y Pintura">Chapa y Pintura</option>
                            <option value="Mantenimiento General" selected>Mantenimiento General</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Estado de Disponibilidad: *</label>
                        <select name="estado" class="form-control" required>
                            <option value="Disponible">Disponible (En Bahía)</option>
                            <option value="En Servicio">En Servicio (Ocupado con OT)</option>
                            <option value="Vacaciones">Vacaciones / Permiso</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Ingreso:</label>
                        <input type="date" name="fecha_ingreso" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoMecanico')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Especialista</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarMecanico">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Editar Datos del Mecánico</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarMecanico')">&times;</button>
        </div>
        <form method="POST" action="mecanico_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_mecanico_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Nombres: *</label>
                        <input type="text" name="nombres" id="edit_nombres" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos: *</label>
                        <input type="text" name="apellidos" id="edit_apellidos" class="form-control" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>DNI: *</label>
                        <input type="text" name="dni" id="edit_dni" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Teléfono: *</label>
                        <input type="tel" name="telefono" id="edit_telefono" class="form-control" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Especialidad: *</label>
                        <select name="especialidad" id="edit_especialidad" class="form-control" required>
                            <option value="Diagnóstico Computarizado">Diagnóstico Computarizado</option>
                            <option value="Frenos y Suspensión">Frenos y Suspensión</option>
                            <option value="Motor y Transmisión">Motor y Transmisión</option>
                            <option value="Electricidad y Electrónica">Electricidad y Electrónica</option>
                            <option value="Alineación y Afinamiento">Alineación y Afinamiento</option>
                            <option value="Chapa y Pintura">Chapa y Pintura</option>
                            <option value="Mantenimiento General">Mantenimiento General</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Estado: *</label>
                        <select name="estado" id="edit_estado" class="form-control" required>
                            <option value="Disponible">Disponible</option>
                            <option value="En Servicio">En Servicio</option>
                            <option value="Vacaciones">Vacaciones</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Ingreso:</label>
                        <input type="date" name="fecha_ingreso" id="edit_fecha_ingreso" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarMecanico')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Mecánico</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarMecanico(m) {
    document.getElementById('edit_mecanico_id').value = m.id;
    document.getElementById('edit_nombres').value = m.nombres;
    document.getElementById('edit_apellidos').value = m.apellidos;
    document.getElementById('edit_dni').value = m.dni;
    document.getElementById('edit_telefono').value = m.telefono;
    document.getElementById('edit_email').value = m.email || '';
    document.getElementById('edit_especialidad').value = m.especialidad;
    document.getElementById('edit_estado').value = m.estado;
    document.getElementById('edit_fecha_ingreso').value = m.fecha_ingreso || '';
    openModal('modalEditarMecanico');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
