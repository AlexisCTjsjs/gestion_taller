<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

requireAuth();
$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmtOT = $pdo->prepare("
    SELECT ot.*, 
           cl.nombres, cl.apellidos, cl.numero_documento, cl.tipo_documento, cl.telefono, cl.email, cl.direccion,
           v.placa, v.marca, v.modelo, v.anio_fabricacion, v.color, v.tipo_combustible, v.kilometraje, v.vin_chasis,
           m.nombres as mecanico_nombre, m.apellidos as mecanico_apellido, m.especialidad as mecanico_esp
    FROM ordenes_trabajo ot
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    LEFT JOIN mecanicos m ON ot.mecanico_id = m.id
    WHERE ot.id = :id
");
$stmtOT->execute([':id' => $id]);
$ot = $stmtOT->fetch();

if (!$ot) {
    die("Orden no encontrada.");
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
    SELECT orp.*, r.codigo, r.nombre, r.categoria 
    FROM orden_repuestos orp
    JOIN repuestos r ON orp.repuesto_id = r.id
    WHERE orp.orden_id = :id
");
$stmtRep->execute([':id' => $id]);
$repuestos = $stmtRep->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Trabajo <?= sanitize($ot['codigo_orden']) ?> - Motor Express</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/print.css">
    <style>
        body { background: #f1f5f9; padding: 20px; }
        .print-sheet {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            padding: 35px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            color: #1e293b;
        }
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        .meta-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
        }
        .meta-label {
            background: #f8fafc;
            font-weight: 700;
            width: 20%;
        }
        .section-heading {
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin: 15px 0 10px 0;
            color: #0f172a;
        }
        .signature-box {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
        }
        .signature-line {
            width: 42%;
            border-top: 1px solid #334155;
            text-align: center;
            font-size: 0.85rem;
            padding-top: 6px;
        }
    </style>
</head>
<body>

<div style="text-align: center; margin-bottom: 15px;" class="no-print">
    <button onclick="window.print()" class="btn btn-primary" style="padding: 10px 25px; font-size: 1rem;">
        🖨️ Imprimir Hoja de Trabajo / Guardar como PDF
    </button>
    <a href="detalle.php?id=<?= $ot['id'] ?>" class="btn btn-secondary" style="margin-left: 10px;">
        &larr; Volver
    </a>
</div>

<div class="print-sheet">
    <div class="header-box">
        <div>
            <h1 style="font-size: 1.8rem; color: #0f172a; margin: 0; line-height: 1;">MOTOR <span style="color: #2563eb;">EXPRESS</span></h1>
            <p style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; margin: 4px 0 6px 0; color: #64748b;">Centro de Diagnóstico y Reparación Automotriz</p>
            <div style="font-size: 0.8rem; color: #475569; line-height: 1.4;">
                RUC: 20608974512<br>
                Av. Automotriz 450, Lima - Perú<br>
                Central: (01) 458-9920 | WhatsApp: +51 987 654 321<br>
                contacto@motorexpress.pe
            </div>
        </div>
        <div style="text-align: right; border: 2px solid #0f172a; padding: 12px 20px; border-radius: 6px; background: #f8fafc;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">ORDEN DE TRABAJO</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0f172a;"><?= sanitize($ot['codigo_orden']) ?></div>
            <div style="font-size: 0.8rem; color: #475569; margin-top: 4px;">Fecha: <?= formatDateTime($ot['fecha_ingreso']) ?></div>
            <div style="margin-top: 4px;"><?= getStatusBadge($ot['estado']) ?></div>
        </div>
    </div>

    <div class="section-heading">1. Información del Propietario y Unidad</div>
    <table class="meta-table">
        <tr>
            <td class="meta-label">Cliente:</td>
            <td><strong><?= sanitize($ot['nombres'] . ' ' . $ot['apellidos']) ?></strong></td>
            <td class="meta-label">Placa:</td>
            <td><strong style="font-size: 1.05rem; font-family: monospace;"><?= sanitize($ot['placa']) ?></strong></td>
        </tr>
        <tr>
            <td class="meta-label">Documento:</td>
            <td><?= sanitize($ot['tipo_documento'] . ': ' . $ot['numero_documento']) ?></td>
            <td class="meta-label">Marca / Modelo:</td>
            <td><?= sanitize($ot['marca'] . ' ' . $ot['modelo']) ?> (Año <?= $ot['anio_fabricacion'] ?>)</td>
        </tr>
        <tr>
            <td class="meta-label">Teléfono:</td>
            <td><?= sanitize($ot['telefono']) ?></td>
            <td class="meta-label">Combustible / Km:</td>
            <td><?= sanitize($ot['tipo_combustible']) ?> | <?= number_format($ot['kilometraje_ingreso']) ?> Km (Tanque: <?= sanitize($ot['nivel_combustible']) ?>)</td>
        </tr>
        <tr>
            <td class="meta-label">Dirección:</td>
            <td><?= sanitize($ot['direccion'] ?: '-') ?></td>
            <td class="meta-label">Mecánico Asignado:</td>
            <td><strong><?= sanitize($ot['mecanico_nombre'] . ' ' . $ot['mecanico_apellido']) ?></strong> (<?= sanitize($ot['mecanico_esp']) ?>)</td>
        </tr>
    </table>

    <?php if ($diagnostico): ?>
        <div class="section-heading">2. Diagnóstico Técnico de Fallas Mecánicas</div>
        <table class="meta-table">
            <tr>
                <td class="meta-label">Sistema Afectado:</td>
                <td><?= sanitize($diagnostico['sistema_afectado']) ?></td>
                <td class="meta-label">Gravedad / DTC:</td>
                <td><?= sanitize($diagnostico['gravedad']) ?> | Códigos: <code><?= sanitize($diagnostico['scanner_codigos_dtc'] ?: 'Ninguno') ?></code></td>
            </tr>
            <tr>
                <td class="meta-label">Fallas Detectadas:</td>
                <td colspan="3"><?= nl2br(sanitize($diagnostico['fallas_detectadas'])) ?></td>
            </tr>
            <?php if ($diagnostico['recomendaciones']): ?>
                <tr>
                    <td class="meta-label">Recomendaciones:</td>
                    <td colspan="3"><?= nl2br(sanitize($diagnostico['recomendaciones'])) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php endif; ?>

    <div class="section-heading">3. Servicios de Mano de Obra Realizados</div>
    <table class="data-table" style="margin-bottom: 15px; font-size: 0.85rem;">
        <thead>
            <tr style="background: #f1f5f9;">
                <th>Código</th>
                <th>Descripción del Servicio</th>
                <th>Categoría</th>
                <th style="text-align: right;">Importe (S/)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($servicios) > 0): ?>
                <?php foreach ($servicios as $s): ?>
                    <tr>
                        <td><?= sanitize($s['codigo']) ?></td>
                        <td><strong><?= sanitize($s['nombre']) ?></strong></td>
                        <td><?= sanitize($s['categoria']) ?></td>
                        <td style="text-align: right;"><?= formatMoney($s['precio']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">Sin servicios</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-heading">4. Repuestos y Autopartes Instaladas</div>
    <table class="data-table" style="margin-bottom: 20px; font-size: 0.85rem;">
        <thead>
            <tr style="background: #f1f5f9;">
                <th>Código</th>
                <th>Repuesto / Pieza</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Precio Unitario</th>
                <th style="text-align: right;">Subtotal (S/)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($repuestos) > 0): ?>
                <?php foreach ($repuestos as $r): ?>
                    <tr>
                        <td><?= sanitize($r['codigo']) ?></td>
                        <td><strong><?= sanitize($r['nombre']) ?></strong></td>
                        <td style="text-align: center;"><?= (int)$r['cantidad'] ?></td>
                        <td style="text-align: right;"><?= formatMoney($r['precio_unitario']) ?></td>
                        <td style="text-align: right;"><?= formatMoney($r['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No se requirieron repuestos nuevos</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
        <div style="width: 320px; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 0.9rem;">
            <div style="display: flex; justify-content: space-between; padding: 6px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <span>Subtotal Mano de Obra:</span>
                <strong><?= formatMoney($ot['total_servicios']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <span>Subtotal Repuestos:</span>
                <strong><?= formatMoney($ot['total_repuestos']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px 12px; background: #0f172a; color: white; font-size: 1.05rem;">
                <span>COSTO TOTAL:</span>
                <strong><?= formatMoney($ot['costo_total']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 12px; background: #ecfdf5; color: #047857;">
                <span>Monto Cancelado:</span>
                <strong><?= formatMoney($ot['monto_pagado']) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 12px; background: #fef2f2; color: #b91c1c; font-weight: 700;">
                <span>Saldo Pendiente:</span>
                <span><?= formatMoney($ot['saldo_pendiente']) ?></span>
            </div>
        </div>
    </div>

    <div style="font-size: 0.725rem; color: #64748b; line-height: 1.4; border: 1px dashed #cbd5e1; padding: 8px 12px; border-radius: 4px;">
        <strong>Términos de Garantía:</strong> Motor Express ofrece 90 días o 3,000 kilómetros de garantía en servicios de mano de obra y repuestos genuinos instalados. No nos hacemos responsables por objetos de valor no inventariados al ingreso del vehículo. Todo vehículo retirado del taller debe ser cancelado al 100%.
    </div>

    <div class="signature-box">
        <div class="signature-line">
            <strong><?= sanitize($ot['nombres'] . ' ' . $ot['apellidos']) ?></strong><br>
            Firma del Propietario / Cliente<br>
            Doc: <?= sanitize($ot['numero_documento']) ?>
        </div>
        <div class="signature-line">
            <strong><?= sanitize($ot['mecanico_nombre'] . ' ' . $ot['mecanico_apellido']) ?></strong><br>
            Firma del Mecánico Responsable<br>
            Taller Automotriz Motor Express
        </div>
    </div>
</div>

</body>
</html>
