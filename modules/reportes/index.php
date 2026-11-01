<?php
$pageTitle = 'Reportes Gerenciales';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();

$tab = $_GET['tab'] ?? 'servicios';
$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

$stmtServicios = $pdo->prepare("
    SELECT s.codigo, s.nombre, s.categoria, 
           COUNT(os.id) as total_veces,
           COALESCE(SUM(os.precio), 0) as total_recaudado
    FROM servicios s
    LEFT JOIN orden_servicios os ON s.id = os.servicio_id
    LEFT JOIN ordenes_trabajo ot ON os.orden_id = ot.id AND DATE(ot.fecha_ingreso) BETWEEN :f1 AND :f2
    GROUP BY s.id
    ORDER BY total_recaudado DESC
");
$stmtServicios->execute([':f1' => $fechaInicio, ':f2' => $fechaFin]);
$reporteServicios = $stmtServicios->fetchAll();

$stmtVehiculos = $pdo->prepare("
    SELECT v.placa, v.marca, v.modelo, v.anio_fabricacion, v.kilometraje,
           CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
           cl.telefono as cliente_telefono,
           COUNT(ot.id) as total_atenciones,
           MAX(ot.fecha_ingreso) as ultima_atencion,
           COALESCE(SUM(ot.costo_total), 0) as facturacion_total
    FROM vehiculos v
    JOIN clientes cl ON v.cliente_id = cl.id
    LEFT JOIN ordenes_trabajo ot ON v.id = ot.vehiculo_id AND DATE(ot.fecha_ingreso) BETWEEN :f1 AND :f2
    GROUP BY v.id
    HAVING total_atenciones > 0
    ORDER BY facturacion_total DESC
");
$stmtVehiculos->execute([':f1' => $fechaInicio, ':f2' => $fechaFin]);
$reporteVehiculos = $stmtVehiculos->fetchAll();

$stmtPendientes = $pdo->query("
    SELECT ot.*, 
           CONCAT(cl.nombres, ' ', cl.apellidos) as cliente_nombre,
           cl.telefono as cliente_telefono,
           v.placa, v.marca, v.modelo,
           CONCAT(m.nombres, ' ', m.apellidos) as mecanico_nombre,
           DATEDIFF(NOW(), ot.fecha_ingreso) as dias_en_taller,
           d.fallas_detectadas, d.gravedad
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON ot.mecanico_id = m.id
    LEFT JOIN diagnosticos d ON ot.id = d.orden_id
    WHERE ot.estado NOT IN ('Entregada', 'Cancelada')
    ORDER BY dias_en_taller DESC
");
$reportePendientes = $stmtPendientes->fetchAll();

$stmtIngresos = $pdo->prepare("
    SELECT metodo_pago,
           COUNT(id) as total_transacciones,
           COALESCE(SUM(monto), 0) as total_ingreso
    FROM pagos
    WHERE DATE(fecha_pago) BETWEEN :f1 AND :f2
    GROUP BY metodo_pago
    ORDER BY total_ingreso DESC
");
$stmtIngresos->execute([':f1' => $fechaInicio, ':f2' => $fechaFin]);
$reporteIngresos = $stmtIngresos->fetchAll();

$totalIngresoPeriodo = array_sum(array_column($reporteIngresos, 'total_ingreso'));
?>

<div class="page-header">
    <div class="page-title-group">
        <h2>
            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            Reportes Gerenciales y Estadísticas
        </h2>
        <p>Análisis de servicios realizados, vehículos atendidos, reparaciones en proceso e ingresos por método de pago.</p>
    </div>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-secondary">
            🖨️ Imprimir Reporte
        </button>
    </div>
</div>

<div class="table-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
    <form method="GET" action="index.php" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <input type="hidden" name="tab" value="<?= sanitize($tab) ?>">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <strong>Periodo de Análisis:</strong>
            <label style="font-size: 0.825rem; color: var(--text-muted);">Desde:</label>
            <input type="date" name="fecha_inicio" class="form-control" style="width: auto;" value="<?= $fechaInicio ?>">
            <label style="font-size: 0.825rem; color: var(--text-muted);">Hasta:</label>
            <input type="date" name="fecha_fin" class="form-control" style="width: auto;" value="<?= $fechaFin ?>">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        </div>
        <div style="font-size: 0.85rem; color: var(--text-muted);">
            Rango activo: <strong><?= formatDate($fechaInicio) ?></strong> al <strong><?= formatDate($fechaFin) ?></strong>
        </div>
    </form>
</div>

<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;">
    <a href="index.php?tab=servicios&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn <?= $tab === 'servicios' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: var(--radius-full);">
        🛠️ 1. Servicios Realizados
    </a>
    <a href="index.php?tab=vehiculos&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn <?= $tab === 'vehiculos' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: var(--radius-full);">
        🚗 2. Vehículos Atendidos
    </a>
    <a href="index.php?tab=pendientes&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn <?= $tab === 'pendientes' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: var(--radius-full);">
        ⏳ 3. Reparaciones Pendientes (<?= count($reportePendientes) ?>)
    </a>
    <a href="index.php?tab=ingresos&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" class="btn <?= $tab === 'ingresos' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: var(--radius-full);">
        💰 4. Ingresos Obtenidos
    </a>
</div>

<?php if ($tab === 'servicios'): ?>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Reporte de Servicios de Mano de Obra Realizados</h3>
                <p>Volumen de aplicaciones e ingresos totales generados por tipo de trabajo</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre del Servicio</th>
                        <th>Categoría</th>
                        <th style="text-align: center;">Veces Realizado</th>
                        <th style="text-align: right;">Ingreso Generado (S/)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reporteServicios) > 0): ?>
                        <?php foreach ($reporteServicios as $rs): ?>
                            <tr>
                                <td><strong><?= sanitize($rs['codigo']) ?></strong></td>
                                <td><strong><?= sanitize($rs['nombre']) ?></strong></td>
                                <td><span class="status-badge badge-primary"><?= sanitize($rs['categoria']) ?></span></td>
                                <td style="text-align: center;">
                                    <span class="status-badge badge-secondary"><?= $rs['total_veces'] ?> orden(es)</span>
                                </td>
                                <td style="text-align: right;"><strong><?= formatMoney($rs['total_recaudado']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 2rem;">No hay registros en el rango.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'vehiculos'): ?>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Reporte de Vehículos Atendidos en Taller</h3>
                <p>Historial de unidades atendidas, frecuencia de ingreso y facturación acumulada</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Marca y Modelo</th>
                        <th>Año</th>
                        <th>Propietario</th>
                        <th style="text-align: center;">Órdenes Atendidas</th>
                        <th>Último Ingreso</th>
                        <th style="text-align: right;">Facturación Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reporteVehiculos) > 0): ?>
                        <?php foreach ($reporteVehiculos as $rv): ?>
                            <tr>
                                <td><strong style="font-family: monospace; font-size: 0.95rem; color: var(--primary);"><?= sanitize($rv['placa']) ?></strong></td>
                                <td><?= sanitize($rv['marca'] . ' ' . $rv['modelo']) ?></td>
                                <td><?= (int)$rv['anio_fabricacion'] ?></td>
                                <td>
                                    <strong><?= sanitize($rv['cliente_nombre']) ?></strong>
                                    <div style="font-size:0.75rem; color: var(--text-muted);">Tel: <?= sanitize($rv['cliente_telefono']) ?></div>
                                </td>
                                <td style="text-align: center;"><span class="status-badge badge-info"><?= $rv['total_atenciones'] ?> atencion(es)</span></td>
                                <td><?= formatDate($rv['ultima_atencion']) ?></td>
                                <td style="text-align: right;"><strong><?= formatMoney($rv['facturacion_total']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 2rem;">No hay vehículos con órdenes en el periodo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'pendientes'): ?>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Reporte de Reparaciones Pendientes en Taller</h3>
                <p>Vehículos actualmente en bahía con días transcurridos y trabajo pendiente</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>N° Orden</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Mecánico</th>
                        <th>Fallas Detectadas</th>
                        <th>Días en Taller</th>
                        <th>Estado Actual</th>
                        <th>Saldo Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reportePendientes) > 0): ?>
                        <?php foreach ($reportePendientes as $rp): ?>
                            <tr>
                                <td>
                                    <a href="../ordenes/detalle.php?id=<?= $rp['id'] ?>" style="font-weight: 700;">
                                        <?= sanitize($rp['codigo_orden']) ?>
                                    </a>
                                </td>
                                <td>
                                    <strong style="font-family: monospace;"><?= sanitize($rp['placa']) ?></strong>
                                    <div style="font-size:0.75rem; color: var(--text-muted);"><?= sanitize($rp['marca'] . ' ' . $rp['modelo']) ?></div>
                                </td>
                                <td><?= sanitize($rp['cliente_nombre']) ?></td>
                                <td><?= sanitize($rp['mecanico_nombre'] ?? 'Sin asignar') ?></td>
                                <td>
                                    <span style="font-size:0.8rem;"><?= sanitize(substr($rp['fallas_detectadas'] ?? 'En revisión inicial', 0, 70)) ?>...</span>
                                </td>
                                <td>
                                    <?php if ($rp['dias_en_taller'] >= 3): ?>
                                        <span class="status-badge badge-danger">⚠️ <?= $rp['dias_en_taller'] ?> días</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-warning"><?= $rp['dias_en_taller'] ?> día(s)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($rp['estado']) ?></td>
                                <td><strong style="color: var(--danger);"><?= formatMoney($rp['saldo_pendiente']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding: 2rem;">No hay reparaciones pendientes en este momento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tab === 'ingresos'): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <div class="stat-card stat-purple">
            <div class="stat-info">
                <span class="stat-label">Ingresos Totales en el Periodo</span>
                <h3 class="stat-value"><?= formatMoney($totalIngresoPeriodo) ?></h3>
                <p class="stat-sub">Del <?= formatDate($fechaInicio) ?> al <?= formatDate($fechaFin) ?></p>
            </div>
            <div class="stat-icon">
                <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                <h3>Desglose de Ingresos por Medio de Pago</h3>
                <p>Efectivo, Tarjetas de Crédito/Débito y Billetera Digital (Yape)</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th style="text-align: center;">N° de Operaciones</th>
                        <th style="text-align: right;">Total Recaudado (S/)</th>
                        <th style="text-align: right;">% de Participación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reporteIngresos) > 0): ?>
                        <?php foreach ($reporteIngresos as $ri): 
                            $porcentaje = ($totalIngresoPeriodo > 0) ? ($ri['total_ingreso'] / $totalIngresoPeriodo) * 100 : 0;
                        ?>
                            <tr>
                                <td>
                                    <?php if ($ri['metodo_pago'] === 'Yape'): ?>
                                        <span style="background: #732181; color: white; padding: 3px 10px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.8rem;">📱 Yape</span>
                                    <?php elseif (strpos($ri['metodo_pago'], 'Tarjeta') !== false): ?>
                                        <span class="status-badge badge-primary">💳 <?= sanitize($ri['metodo_pago']) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge badge-success">💵 <?= sanitize($ri['metodo_pago']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?= $ri['total_transacciones'] ?> pago(s)</td>
                                <td style="text-align: right;"><strong style="font-size: 1rem; color: var(--secondary);"><?= formatMoney($ri['total_ingreso']) ?></strong></td>
                                <td style="text-align: right;"><strong><?= number_format($porcentaje, 1) ?>%</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 2rem;">No hay pagos registrados en este periodo.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; font-size: 1rem;">
                        <td>TOTAL GENERAL:</td>
                        <td style="text-align: center;"><?= array_sum(array_column($reporteIngresos, 'total_transacciones')) ?></td>
                        <td style="text-align: right; color: var(--success);"><?= formatMoney($totalIngresoPeriodo) ?></td>
                        <td style="text-align: right;">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
