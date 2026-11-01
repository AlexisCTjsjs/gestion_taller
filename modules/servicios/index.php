<?php
$pageTitle = 'Catálogo de Servicios';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroCat = trim($_GET['categoria'] ?? '');

$sql = "SELECT * FROM servicios";
$where = [];
$params = [];

if (!empty($buscar)) {
    $where[] = "(nombre LIKE :b OR codigo LIKE :b OR descripcion LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if (!empty($filtroCat)) {
    $where[] = "categoria = :cat";
    $params[':cat'] = $filtroCat;
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY categoria ASC, nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$servicios = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Catálogo de Servicios de Reparación
        </h2>
        <p>Mano de obra técnica, mantenimientos preventivos y correctivos estandarizados en Motor Express.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoServicio')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Nuevo Servicio
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Tarifario de Mano de Obra (<?= count($servicios) ?>)</h3>
            <p>Servicios disponibles para agregar a las órdenes de trabajo de clientes</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?categoria=' + encodeURIComponent(this.value)">
                <option value="">-- Todas las Categorías --</option>
                <option value="Mantenimiento Preventivo" <?= $filtroCat == 'Mantenimiento Preventivo' ? 'selected' : '' ?>>Mantenimiento Preventivo</option>
                <option value="Diagnóstico Computarizado" <?= $filtroCat == 'Diagnóstico Computarizado' ? 'selected' : '' ?>>Diagnóstico Computarizado</option>
                <option value="Frenos" <?= $filtroCat == 'Frenos' ? 'selected' : '' ?>>Frenos</option>
                <option value="Motor" <?= $filtroCat == 'Motor' ? 'selected' : '' ?>>Motor</option>
                <option value="Suspensión" <?= $filtroCat == 'Suspensión' ? 'selected' : '' ?>>Suspensión</option>
                <option value="Sistema Eléctrico" <?= $filtroCat == 'Sistema Eléctrico' ? 'selected' : '' ?>>Sistema Eléctrico</option>
                <option value="Alineación y Balanceo" <?= $filtroCat == 'Alineación y Balanceo' ? 'selected' : '' ?>>Alineación y Balanceo</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar por servicio..." data-target-table="serviciosTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="serviciosTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre del Servicio</th>
                    <th>Categoría</th>
                    <th>Descripción / Alcance</th>
                    <th>Tiempo Estimado</th>
                    <th>Precio Estándar</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($servicios) > 0): ?>
                    <?php foreach ($servicios as $s): ?>
                        <tr>
                            <td><strong><?= sanitize($s['codigo']) ?></strong></td>
                            <td><strong><?= sanitize($s['nombre']) ?></strong></td>
                            <td><span class="status-badge badge-primary"><?= sanitize($s['categoria']) ?></span></td>
                            <td><span style="font-size:0.8rem; color: var(--text-muted);"><?= sanitize($s['descripcion'] ?: '-') ?></span></td>
                            <td>⏱️ <?= (int)$s['tiempo_estimado_min'] ?> minutos</td>
                            <td><strong style="color: var(--secondary); font-size: 0.95rem;"><?= formatMoney($s['precio']) ?></strong></td>
                            <td><?= getStatusBadge($s['estado']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn-icon-action edit" title="Editar Servicio" onclick='editarServicio(<?= json_encode($s) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="Eliminar Servicio" onclick="confirmDelete('servicio_actions.php?action=eliminar&id=<?= $s['id'] ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron servicios registrados en esta categoría.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoServicio">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Registrar Servicio en Catálogo</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoServicio')">&times;</button>
        </div>
        <form method="POST" action="servicio_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Código del Servicio: *</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: SRV-009" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría: *</label>
                        <select name="categoria" class="form-control" required>
                            <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                            <option value="Diagnóstico Computarizado">Diagnóstico Computarizado</option>
                            <option value="Frenos">Frenos</option>
                            <option value="Motor">Motor</option>
                            <option value="Suspensión">Suspensión</option>
                            <option value="Sistema Eléctrico">Sistema Eléctrico</option>
                            <option value="Alineación y Balanceo">Alineación y Balanceo</option>
                            <option value="Otros">Otros Servicios</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre del Servicio: *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Limpieza de Inyectores por Ultrasonido" required>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Precio Estándar (S/): *</label>
                        <input type="number" step="0.01" min="0" name="precio" class="form-control" placeholder="Ej: 150.00" required>
                    </div>
                    <div class="form-group">
                        <label>Tiempo Estimado (Minutos):</label>
                        <input type="number" min="5" step="5" name="tiempo_estimado_min" class="form-control" value="60">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Descripción / Procedimiento Técnico:</label>
                    <textarea name="descripcion" class="form-control" placeholder="Detalle de pasos y puntos incluidos"></textarea>
                </div>

                <div class="form-group">
                    <label>Estado: *</label>
                    <select name="estado" class="form-control" required>
                        <option value="Activo">Activo (Disponible en OTs)</option>
                        <option value="Inactivo">Inactivo (Ocultar)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoServicio')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Servicio</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarServicio">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Editar Servicio de Catálogo</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarServicio')">&times;</button>
        </div>
        <form method="POST" action="servicio_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_srv_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Código: *</label>
                        <input type="text" name="codigo" id="edit_srv_codigo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría: *</label>
                        <select name="categoria" id="edit_srv_categoria" class="form-control" required>
                            <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                            <option value="Diagnóstico Computarizado">Diagnóstico Computarizado</option>
                            <option value="Frenos">Frenos</option>
                            <option value="Motor">Motor</option>
                            <option value="Suspensión">Suspensión</option>
                            <option value="Sistema Eléctrico">Sistema Eléctrico</option>
                            <option value="Alineación y Balanceo">Alineación y Balanceo</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre: *</label>
                    <input type="text" name="nombre" id="edit_srv_nombre" class="form-control" required>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Precio (S/): *</label>
                        <input type="number" step="0.01" min="0" name="precio" id="edit_srv_precio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tiempo Estimado (Minutos):</label>
                        <input type="number" name="tiempo_estimado_min" id="edit_srv_tiempo" class="form-control">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Descripción:</label>
                    <textarea name="descripcion" id="edit_srv_desc" class="form-control"></textarea>
                </div>

                <div class="form-group">
                    <label>Estado: *</label>
                    <select name="estado" id="edit_srv_estado" class="form-control" required>
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarServicio')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarServicio(s) {
    document.getElementById('edit_srv_id').value = s.id;
    document.getElementById('edit_srv_codigo').value = s.codigo;
    document.getElementById('edit_srv_categoria').value = s.categoria;
    document.getElementById('edit_srv_nombre').value = s.nombre;
    document.getElementById('edit_srv_precio').value = s.precio;
    document.getElementById('edit_srv_tiempo').value = s.tiempo_estimado_min;
    document.getElementById('edit_srv_desc').value = s.descripcion || '';
    document.getElementById('edit_srv_estado').value = s.estado;
    openModal('modalEditarServicio');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
