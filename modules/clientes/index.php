<?php
$pageTitle = 'Gestión de Clientes';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');

$sql = "
    SELECT cl.*, 
    COUNT(v.id) as total_vehiculos
    FROM clientes cl
    LEFT JOIN vehiculos v ON cl.id = v.cliente_id
";

$params = [];
if (!empty($buscar)) {
    $sql .= " WHERE cl.nombres LIKE :b OR cl.apellidos LIKE :b OR cl.numero_documento LIKE :b OR cl.telefono LIKE :b";
    $params[':b'] = "%$buscar%";
}

$sql .= " GROUP BY cl.id ORDER BY cl.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Administración de Clientes
        </h2>
        <p>Registro, actualización y consulta de propietarios de vehículos atendidos en Motor Express.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoCliente')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Registrar Nuevo Cliente
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Cartera de Clientes (<?= count($clientes) ?>)</h3>
            <p>Lista general con vehículos registrados y datos de contacto directo</p>
        </div>
        <div class="table-filters">
            <input type="text" class="table-search-input" placeholder="Buscar por DNI, nombre..." data-target-table="clientesTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="clientesTable">
            <thead>
                <tr>
                    <th>Doc. Identidad</th>
                    <th>Nombres y Apellidos</th>
                    <th>Teléfono</th>
                    <th>Correo Electrónico</th>
                    <th>Dirección</th>
                    <th>Vehículos</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($clientes) > 0): ?>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <span class="status-badge badge-secondary"><?= sanitize($c['tipo_documento']) ?></span>
                                <strong style="margin-left: 4px;"><?= sanitize($c['numero_documento']) ?></strong>
                            </td>
                            <td><strong><?= sanitize($c['nombres'] . ' ' . $c['apellidos']) ?></strong></td>
                            <td>
                                <a href="tel:<?= sanitize($c['telefono']) ?>" style="color: inherit; text-decoration: none;">
                                    📞 <?= sanitize($c['telefono']) ?>
                                </a>
                            </td>
                            <td><?= sanitize($c['email'] ?: '-') ?></td>
                            <td><span style="font-size:0.8rem; color: var(--text-muted);"><?= sanitize($c['direccion'] ?: '-') ?></span></td>
                            <td>
                                <span class="status-badge badge-primary"><?= $c['total_vehiculos'] ?> vehículo(s)</span>
                            </td>
                            <td><?= formatDate($c['creado_en']) ?></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn-icon-action edit" title="Editar Cliente" onclick='editarCliente(<?= json_encode($c) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="Eliminar Cliente" onclick="confirmDelete('cliente_actions.php?action=eliminar&id=<?= $c['id'] ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No se encontraron clientes registrados con los criterios indicados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoCliente">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Registrar Nuevo Cliente</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoCliente')">&times;</button>
        </div>
        <form method="POST" action="cliente_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Tipo Documento: *</label>
                        <select name="tipo_documento" class="form-control" required>
                            <option value="DNI">DNI (Persona Natural)</option>
                            <option value="RUC">RUC (Empresa / Persona Jurídica)</option>
                            <option value="Carnet Extranjería">Carnet de Extranjería</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>N° Documento: *</label>
                        <input type="text" name="numero_documento" class="form-control" placeholder="Ej: 45892134" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Nombres / Razón Social: *</label>
                        <input type="text" name="nombres" class="form-control" placeholder="Ej: Juan Carlos" required>
                    </div>
                    <div class="form-group">
                        <label>Apellidos / Siglas: *</label>
                        <input type="text" name="apellidos" class="form-control" placeholder="Ej: Pérez Morales" required>
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Teléfono / Celular: *</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="Ej: 987112233" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="email" class="form-control" placeholder="juan.perez@gmail.com">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dirección Domiciliaria o Fiscal:</label>
                    <input type="text" name="direccion" class="form-control" placeholder="Ej: Av. Javier Prado Este 2450, San Borja">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarCliente">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Editar Datos del Cliente</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarCliente')">&times;</button>
        </div>
        <form method="POST" action="cliente_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_cliente_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Tipo Documento: *</label>
                        <select name="tipo_documento" id="edit_tipo_documento" class="form-control" required>
                            <option value="DNI">DNI</option>
                            <option value="RUC">RUC</option>
                            <option value="Carnet Extranjería">Carnet de Extranjería</option>
                            <option value="Pasaporte">Pasaporte</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>N° Documento: *</label>
                        <input type="text" name="numero_documento" id="edit_numero_documento" class="form-control" required>
                    </div>
                </div>

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
                        <label>Teléfono: *</label>
                        <input type="tel" name="telefono" id="edit_telefono" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" id="edit_direccion" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Cliente</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarCliente(c) {
    document.getElementById('edit_cliente_id').value = c.id;
    document.getElementById('edit_tipo_documento').value = c.tipo_documento;
    document.getElementById('edit_numero_documento').value = c.numero_documento;
    document.getElementById('edit_nombres').value = c.nombres;
    document.getElementById('edit_apellidos').value = c.apellidos;
    document.getElementById('edit_telefono').value = c.telefono;
    document.getElementById('edit_email').value = c.email || '';
    document.getElementById('edit_direccion').value = c.direccion || '';
    openModal('modalEditarCliente');
}

if (new URLSearchParams(window.location.search).get('action') === 'nuevo') {
    openModal('modalNuevoCliente');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
