<?php
$pageTitle = 'Gestión de Vehículos';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');

$stmtClientes = $pdo->query("SELECT id, nombres, apellidos, numero_documento FROM clientes ORDER BY nombres ASC");
$listaClientes = $stmtClientes->fetchAll();

$sql = "
    SELECT v.*, 
    CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
    cl.telefono as cliente_telefono,
    cl.numero_documento as cliente_doc,
    COUNT(ot.id) as total_ordenes
    FROM vehiculos v
    JOIN clientes cl ON v.cliente_id = cl.id
    LEFT JOIN ordenes_trabajo ot ON v.id = ot.vehiculo_id
";

$params = [];
if (!empty($buscar)) {
    $sql .= " WHERE v.placa LIKE :b OR v.marca LIKE :b OR v.modelo LIKE :b OR cl.nombres LIKE :b OR cl.apellidos LIKE :b";
    $params[':b'] = "%$buscar%";
}

$sql .= " GROUP BY v.id ORDER BY v.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehiculos = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="22" height="13" rx="2" ry="2"></rect><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"></path><circle cx="6" cy="12" r="2"></circle><circle cx="18" cy="12" r="2"></circle></svg>
            Registro de Vehículos
        </h2>
        <p>Administración del parque automotor atendido: placas, marcas, modelos, año y propietarios.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoVehiculo')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Registrar Vehículo
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Vehículos en Taller (<?= count($vehiculos) ?>)</h3>
            <p>Control de unidades registradas y acceso a su historial técnico</p>
        </div>
        <div class="table-filters">
            <input type="text" class="table-search-input" placeholder="Buscar placa, modelo, dueño..." data-target-table="vehiculosTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="vehiculosTable">
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Marca y Modelo</th>
                    <th>Año</th>
                    <th>Propietario (Cliente)</th>
                    <th>Combustible</th>
                    <th>Kilometraje</th>
                    <th>Historial OTs</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($vehiculos) > 0): ?>
                    <?php foreach ($vehiculos as $v): ?>
                        <tr>
                            <td>
                                <span style="background: #1e293b; color: #f8fafc; padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 700; letter-spacing: 1px; font-family: monospace;">
                                    <?= sanitize($v['placa']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= sanitize($v['marca'] . ' ' . $v['modelo']) ?></strong>
                                <?php if ($v['color']): ?>
                                    <div style="font-size:0.75rem; color: var(--text-muted);">Color: <?= sanitize($v['color']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= (int)$v['anio_fabricacion'] ?></strong></td>
                            <td>
                                <div><strong><?= sanitize($v['cliente_nombre']) ?></strong></div>
                                <div style="font-size:0.75rem; color: var(--text-muted);">Doc: <?= sanitize($v['cliente_doc']) ?> | Tel: <?= sanitize($v['cliente_telefono']) ?></div>
                            </td>
                            <td><span class="status-badge badge-secondary"><?= sanitize($v['tipo_combustible']) ?></span></td>
                            <td><strong><?= number_format((int)$v['kilometraje']) ?> Km</strong></td>
                            <td>
                                <a href="../ordenes/index.php?buscar=<?= urlencode($v['placa']) ?>" class="status-badge badge-info" title="Ver órdenes de trabajo">
                                    <?= $v['total_ordenes'] ?> orden(es)
                                </a>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn-icon-action edit" title="Editar Vehículo" onclick='editarVehiculo(<?= json_encode($v) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="Eliminar Vehículo" onclick="confirmDelete('vehiculo_actions.php?action=eliminar&id=<?= $v['id'] ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron vehículos registrados en la base de datos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoVehiculo">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Registrar Vehículo en Motor Express</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoVehiculo')">&times;</button>
        </div>
        <form method="POST" action="vehiculo_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Propietario / Cliente: *</label>
                        <select name="cliente_id" class="form-control" required>
                            <option value="">-- Seleccione Cliente --</option>
                            <?php foreach ($listaClientes as $lc): ?>
                                <option value="<?= $lc['id'] ?>"><?= sanitize($lc['nombres'] . ' ' . $lc['apellidos']) ?> (Doc: <?= sanitize($lc['numero_documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Placa del Vehículo: *</label>
                        <input type="text" name="placa" class="form-control" placeholder="Ej: ABC-123" style="text-transform: uppercase;" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Marca: *</label>
                        <input type="text" name="marca" class="form-control" placeholder="Ej: Toyota" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo: *</label>
                        <input type="text" name="modelo" class="form-control" placeholder="Ej: Corolla Cross" required>
                    </div>
                    <div class="form-group">
                        <label>Año de Fabricación: *</label>
                        <input type="number" name="anio_fabricacion" class="form-control" placeholder="Ej: 2022" min="1970" max="<?= date('Y') + 1 ?>" value="<?= date('Y') ?>" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Color:</label>
                        <input type="text" name="color" class="form-control" placeholder="Ej: Blanco Perlado">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Combustible: *</label>
                        <select name="tipo_combustible" class="form-control" required>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diésel">Diésel</option>
                            <option value="GLP">GLP (Gas Licuado)</option>
                            <option value="GNV">GNV (Gas Natural)</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Eléctrico">100% Eléctrico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kilometraje Actual (Km):</label>
                        <input type="number" name="kilometraje" class="form-control" placeholder="Ej: 45000" min="0" value="0">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>N° VIN / Chasis (Opcional):</label>
                    <input type="text" name="vin_chasis" class="form-control" placeholder="Número de serie o chasis">
                </div>

                <div class="form-group">
                    <label>Notas Técnicas / Observaciones Iniciales:</label>
                    <textarea name="notas" class="form-control" placeholder="Historial previo, estado de carrocería, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoVehiculo')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Vehículo</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarVehiculo">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Editar Datos del Vehículo</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarVehiculo')">&times;</button>
        </div>
        <form method="POST" action="vehiculo_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_vehiculo_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Propietario / Cliente: *</label>
                        <select name="cliente_id" id="edit_cliente_id" class="form-control" required>
                            <?php foreach ($listaClientes as $lc): ?>
                                <option value="<?= $lc['id'] ?>"><?= sanitize($lc['nombres'] . ' ' . $lc['apellidos']) ?> (Doc: <?= sanitize($lc['numero_documento']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Placa del Vehículo: *</label>
                        <input type="text" name="placa" id="edit_placa" class="form-control" style="text-transform: uppercase;" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Marca: *</label>
                        <input type="text" name="marca" id="edit_marca" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Modelo: *</label>
                        <input type="text" name="modelo" id="edit_modelo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Año de Fabricación: *</label>
                        <input type="number" name="anio_fabricacion" id="edit_anio_fabricacion" class="form-control" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Color:</label>
                        <input type="text" name="color" id="edit_color" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Combustible: *</label>
                        <select name="tipo_combustible" id="edit_tipo_combustible" class="form-control" required>
                            <option value="Gasolina">Gasolina</option>
                            <option value="Diésel">Diésel</option>
                            <option value="GLP">GLP</option>
                            <option value="GNV">GNV</option>
                            <option value="Híbrido">Híbrido</option>
                            <option value="Eléctrico">Eléctrico</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kilometraje Actual (Km):</label>
                        <input type="number" name="kilometraje" id="edit_kilometraje" class="form-control">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>N° VIN / Chasis:</label>
                    <input type="text" name="vin_chasis" id="edit_vin_chasis" class="form-control">
                </div>

                <div class="form-group">
                    <label>Notas Técnicas:</label>
                    <textarea name="notas" id="edit_notas" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarVehiculo')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Vehículo</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarVehiculo(v) {
    document.getElementById('edit_vehiculo_id').value = v.id;
    document.getElementById('edit_cliente_id').value = v.cliente_id;
    document.getElementById('edit_placa').value = v.placa;
    document.getElementById('edit_marca').value = v.marca;
    document.getElementById('edit_modelo').value = v.modelo;
    document.getElementById('edit_anio_fabricacion').value = v.anio_fabricacion;
    document.getElementById('edit_color').value = v.color || '';
    document.getElementById('edit_tipo_combustible').value = v.tipo_combustible;
    document.getElementById('edit_kilometraje').value = v.kilometraje || 0;
    document.getElementById('edit_vin_chasis').value = v.vin_chasis || '';
    document.getElementById('edit_notas').value = v.notas || '';
    openModal('modalEditarVehiculo');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
