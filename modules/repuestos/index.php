<?php
$pageTitle = 'Inventario de Repuestos';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$buscar = trim($_GET['buscar'] ?? '');
$filtroCat = trim($_GET['categoria'] ?? '');
$filtroBajoStock = isset($_GET['bajo_stock']);

$sql = "SELECT * FROM repuestos";
$where = [];
$params = [];

if (!empty($buscar)) {
    $where[] = "(nombre LIKE :b OR codigo LIKE :b OR descripcion LIKE :b OR ubicacion_almacen LIKE :b)";
    $params[':b'] = "%$buscar%";
}

if (!empty($filtroCat)) {
    $where[] = "categoria = :cat";
    $params[':cat'] = $filtroCat;
}

if ($filtroBajoStock) {
    $where[] = "stock <= stock_minimo";
}

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY (stock <= stock_minimo) DESC, nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$repuestos = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Almacén y Control de Repuestos
        </h2>
        <p>Inventario de autopartes, niveles de stock de seguridad, precios de compra y valorización en órdenes de trabajo.</p>
    </div>
    <div class="page-actions">
        <a href="index.php?bajo_stock=1" class="btn btn-secondary <?= $filtroBajoStock ? 'btn-danger' : '' ?>">
            ⚠️ Ver Stock Crítico
        </a>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoRepuesto')">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Ingresar Repuesto
        </button>
    </div>
</div>

<div class="table-card">
    <div class="table-card-header">
        <div class="table-card-title">
            <h3>Catálogo de Autopartes (<?= count($repuestos) ?>)</h3>
            <p>Lista de ítems con control de existencias en almacén y precio de reposición</p>
        </div>
        <div class="table-filters">
            <select class="table-search-input" style="width: auto;" onchange="window.location.href='index.php?categoria=' + encodeURIComponent(this.value)">
                <option value="">-- Todas las Categorías --</option>
                <option value="Frenos" <?= $filtroCat == 'Frenos' ? 'selected' : '' ?>>Frenos</option>
                <option value="Filtros" <?= $filtroCat == 'Filtros' ? 'selected' : '' ?>>Filtros</option>
                <option value="Fluidos y Aceites" <?= $filtroCat == 'Fluidos y Aceites' ? 'selected' : '' ?>>Fluidos y Aceites</option>
                <option value="Suspensión" <?= $filtroCat == 'Suspensión' ? 'selected' : '' ?>>Suspensión</option>
                <option value="Motor" <?= $filtroCat == 'Motor' ? 'selected' : '' ?>>Motor</option>
                <option value="Sistema Eléctrico" <?= $filtroCat == 'Sistema Eléctrico' ? 'selected' : '' ?>>Sistema Eléctrico</option>
            </select>
            <input type="text" class="table-search-input" placeholder="Buscar por código, nombre..." data-target-table="repuestosTable" value="<?= sanitize($buscar) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table" id="repuestosTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Repuesto / Pieza</th>
                    <th>Categoría</th>
                    <th>Stock Actual</th>
                    <th>Stock Mín.</th>
                    <th>Precio Compra</th>
                    <th>Precio Venta</th>
                    <th>Ubicación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($repuestos) > 0): ?>
                    <?php foreach ($repuestos as $r): 
                        $esBajoStock = $r['stock'] <= $r['stock_minimo'];
                    ?>
                        <tr style="<?= $esBajoStock ? 'background: #fff8f8;' : '' ?>">
                            <td><strong><?= sanitize($r['codigo']) ?></strong></td>
                            <td>
                                <strong><?= sanitize($r['nombre']) ?></strong>
                                <?php if ($r['descripcion']): ?>
                                    <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($r['descripcion']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge badge-secondary"><?= sanitize($r['categoria']) ?></span></td>
                            <td>
                                <?php if ($esBajoStock): ?>
                                    <span class="status-badge badge-danger">
                                        ⚠️ <?= $r['stock'] ?> unid. (Crítico)
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-success">
                                        <?= $r['stock'] ?> unid.
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['stock_minimo'] ?> unid.</td>
                            <td><?= formatMoney($r['precio_compra']) ?></td>
                            <td><strong style="color: var(--secondary); font-size: 0.95rem;"><?= formatMoney($r['precio_venta']) ?></strong></td>
                            <td><span style="font-size:0.8rem; color: var(--text-muted);">📍 <?= sanitize($r['ubicacion_almacen'] ?: 'Almacén') ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn-icon-action view" title="Ajustar Stock Rápido" onclick='ajustarStockModal(<?= json_encode($r) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action edit" title="Editar Repuesto" onclick='editarRepuesto(<?= json_encode($r) ?>)'>
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button type="button" class="btn-icon-action delete" title="Eliminar Repuesto" onclick="confirmDelete('repuesto_actions.php?action=eliminar&id=<?= $r['id'] ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">
                            No hay repuestos registrados que coincidan con la búsqueda.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoRepuesto">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Ingresar Nuevo Repuesto a Inventario</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoRepuesto')">&times;</button>
        </div>
        <form method="POST" action="repuesto_actions.php?action=crear">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Código de Repuesto / OEM: *</label>
                        <input type="text" name="codigo" class="form-control" placeholder="Ej: REP-FRN-10" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría: *</label>
                        <select name="categoria" class="form-control" required>
                            <option value="Frenos">Frenos</option>
                            <option value="Filtros">Filtros</option>
                            <option value="Fluidos y Aceites">Fluidos y Aceites</option>
                            <option value="Suspensión">Suspensión</option>
                            <option value="Motor">Motor</option>
                            <option value="Sistema Eléctrico">Sistema Eléctrico</option>
                            <option value="Neumáticos">Neumáticos</option>
                            <option value="Accesorios">Accesorios</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre Comercial del Repuesto: *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Discos de Freno Brembo Ventilados" required>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Stock Inicial (Unidades): *</label>
                        <input type="number" min="0" name="stock" class="form-control" value="10" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Mínimo de Alerta: *</label>
                        <input type="number" min="1" name="stock_minimo" class="form-control" value="4" required>
                    </div>
                    <div class="form-group">
                        <label>Ubicación en Almacén:</label>
                        <input type="text" name="ubicacion_almacen" class="form-control" placeholder="Ej: Estante C-2">
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Costo de Compra (S/):</label>
                        <input type="number" step="0.01" min="0" name="precio_compra" class="form-control" placeholder="Ej: 80.00" value="0.00">
                    </div>
                    <div class="form-group">
                        <label>Precio de Venta al Cliente (S/): *</label>
                        <input type="number" step="0.01" min="0" name="precio_venta" class="form-control" placeholder="Ej: 140.00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción / Especificaciones:</label>
                    <textarea name="descripcion" class="form-control" placeholder="Compatibilidad de marcas, origen, etc."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoRepuesto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar en Almacén</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarRepuesto">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3>Editar Datos del Repuesto</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarRepuesto')">&times;</button>
        </div>
        <form method="POST" action="repuesto_actions.php?action=editar">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="edit_rep_id">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Código: *</label>
                        <input type="text" name="codigo" id="edit_rep_codigo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Categoría: *</label>
                        <select name="categoria" id="edit_rep_categoria" class="form-control" required>
                            <option value="Frenos">Frenos</option>
                            <option value="Filtros">Filtros</option>
                            <option value="Fluidos y Aceites">Fluidos y Aceites</option>
                            <option value="Suspensión">Suspensión</option>
                            <option value="Motor">Motor</option>
                            <option value="Sistema Eléctrico">Sistema Eléctrico</option>
                            <option value="Neumáticos">Neumáticos</option>
                            <option value="Accesorios">Accesorios</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre: *</label>
                    <input type="text" name="nombre" id="edit_rep_nombre" class="form-control" required>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Stock Actual: *</label>
                        <input type="number" min="0" name="stock" id="edit_rep_stock" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Mínimo: *</label>
                        <input type="number" min="1" name="stock_minimo" id="edit_rep_stock_minimo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Ubicación:</label>
                        <input type="text" name="ubicacion_almacen" id="edit_rep_ubicacion" class="form-control">
                    </div>
                </div>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Costo de Compra (S/):</label>
                        <input type="number" step="0.01" min="0" name="precio_compra" id="edit_rep_precio_compra" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Precio Venta (S/): *</label>
                        <input type="number" step="0.01" min="0" name="precio_venta" id="edit_rep_precio_venta" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descripción:</label>
                    <textarea name="descripcion" id="edit_rep_descripcion" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarRepuesto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Repuesto</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalAjustarStock">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Ajustar Stock de Repuesto</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalAjustarStock')">&times;</button>
        </div>
        <form method="POST" action="repuesto_actions.php?action=ajustar_stock">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="id" id="stock_ajuste_id">
            <div class="modal-body">
                <p style="margin-bottom: 1rem; font-size: 0.95rem;">
                    Repuesto: <strong id="stock_ajuste_nombre" style="color: var(--primary);"></strong><br>
                    Stock actual en almacén: <strong id="stock_ajuste_actual"></strong> unidades.
                </p>

                <div class="form-grid" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Tipo de Movimiento: *</label>
                        <select name="tipo" class="form-control" required>
                            <option value="entrada">➕ Entrada / Compra (+ Sumar al stock)</option>
                            <option value="salida">➖ Salida / Merma (- Restar del stock)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad de Unidades: *</label>
                        <input type="number" min="1" name="cantidad" class="form-control" value="1" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalAjustarStock')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Aplicar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarRepuesto(r) {
    document.getElementById('edit_rep_id').value = r.id;
    document.getElementById('edit_rep_codigo').value = r.codigo;
    document.getElementById('edit_rep_categoria').value = r.categoria;
    document.getElementById('edit_rep_nombre').value = r.nombre;
    document.getElementById('edit_rep_stock').value = r.stock;
    document.getElementById('edit_rep_stock_minimo').value = r.stock_minimo;
    document.getElementById('edit_rep_precio_compra').value = r.precio_compra;
    document.getElementById('edit_rep_precio_venta').value = r.precio_venta;
    document.getElementById('edit_rep_ubicacion').value = r.ubicacion_almacen || '';
    document.getElementById('edit_rep_descripcion').value = r.descripcion || '';
    openModal('modalEditarRepuesto');
}

function ajustarStockModal(r) {
    document.getElementById('stock_ajuste_id').value = r.id;
    document.getElementById('stock_ajuste_nombre').textContent = r.nombre + ' (' + r.codigo + ')';
    document.getElementById('stock_ajuste_actual').textContent = r.stock;
    openModal('modalAjustarStock');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
