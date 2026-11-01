<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

requireAuth();
$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*,
           ot.codigo_orden, ot.costo_total, ot.monto_pagado as total_pagado_ot, ot.saldo_pendiente,
           cl.nombres, cl.apellidos, cl.numero_documento, cl.tipo_documento, cl.direccion,
           v.placa, v.marca, v.modelo
    FROM pagos p
    JOIN ordenes_trabajo ot ON p.orden_id = ot.id
    JOIN clientes cl ON ot.cliente_id = cl.id
    JOIN vehiculos v ON ot.vehiculo_id = v.id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$pago = $stmt->fetch();

if (!$pago) {
    die("Comprobante de pago no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante <?= sanitize($pago['comprobante_numero']) ?> - Motor Express</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/print.css">
    <style>
        body { background: #f1f5f9; padding: 25px; }
        .receipt-ticket {
            max-width: 520px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            font-size: 0.875rem;
            color: #0f172a;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .ticket-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }
        .ticket-total {
            border-top: 2px dashed #cbd5e1;
            border-bottom: 2px dashed #cbd5e1;
            margin: 15px 0;
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.25rem;
            font-weight: 800;
        }
    </style>
</head>
<body>

<div style="text-align: center; margin-bottom: 15px;" class="no-print">
    <button onclick="window.print()" class="btn btn-primary">
        🖨️ Imprimir Comprobante de Caja
    </button>
    <a href="index.php" class="btn btn-secondary" style="margin-left: 10px;">
        &larr; Volver a Pagos
    </a>
</div>

<div class="receipt-ticket">
    <div class="ticket-header">
        <h2 style="font-size: 1.5rem; margin: 0; color: #0f172a;">MOTOR <span style="color: #2563eb;">EXPRESS</span></h2>
        <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #64748b;">Taller y Mecánica Automotriz</div>
        <div style="font-size: 0.78rem; color: #475569; margin-top: 6px; line-height: 1.4;">
            RUC: 20608974512<br>
            Av. Automotriz 450, Lima - Perú<br>
            Teléfono: +51 987 654 321
        </div>
        <div style="margin-top: 10px; font-weight: 700; font-size: 1rem; color: #0f172a; border: 1px solid #0f172a; padding: 4px; border-radius: 4px; display: inline-block;">
            <?= strtoupper(sanitize($pago['comprobante_tipo'])) ?>: <?= sanitize($pago['comprobante_numero']) ?>
        </div>
    </div>

    <div>
        <div class="ticket-row">
            <span>Fecha y Hora de Emisión:</span>
            <strong><?= formatDateTime($pago['fecha_pago']) ?></strong>
        </div>
        <div class="ticket-row">
            <span>N° Operación de Caja:</span>
            <strong><?= sanitize($pago['codigo_pago']) ?></strong>
        </div>
        <div class="ticket-row">
            <span>Orden de Trabajo Relacionada:</span>
            <strong><?= sanitize($pago['codigo_orden']) ?></strong>
        </div>
    </div>

    <div style="border-top: 1px solid #e2e8f0; margin: 10px 0; padding-top: 10px;">
        <div class="ticket-row">
            <span>Cliente:</span>
            <strong><?= sanitize($pago['nombres'] . ' ' . $pago['apellidos']) ?></strong>
        </div>
        <div class="ticket-row">
            <span>Documento:</span>
            <span><?= sanitize($pago['tipo_documento'] . ': ' . $pago['numero_documento']) ?></span>
        </div>
        <div class="ticket-row">
            <span>Vehículo / Placa:</span>
            <strong style="font-family: monospace; font-size: 0.95rem;"><?= sanitize($pago['placa']) ?> (<?= sanitize($pago['marca'] . ' ' . $pago['modelo']) ?>)</strong>
        </div>
    </div>

    <div style="border-top: 1px solid #e2e8f0; margin: 10px 0; padding-top: 10px;">
        <div class="ticket-row">
            <span>Método de Pago:</span>
            <strong>
                <?php if ($pago['metodo_pago'] === 'Yape'): ?>
                    📱 Yape
                <?php else: ?>
                    <?= sanitize($pago['metodo_pago']) ?>
                <?php endif; ?>
            </strong>
        </div>
        <?php if ($pago['nro_operacion']): ?>
            <div class="ticket-row">
                <span>Ref. / N° Operación:</span>
                <strong><?= sanitize($pago['nro_operacion']) ?></strong>
            </div>
        <?php endif; ?>
        <?php if ($pago['observaciones']): ?>
            <div class="ticket-row">
                <span>Concepto:</span>
                <span><?= sanitize($pago['observaciones']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="ticket-total">
        <span>TOTAL COBRADO:</span>
        <span style="color: #047857;"><?= formatMoney($pago['monto']) ?></span>
    </div>

    <div style="font-size: 0.8rem; line-height: 1.5; color: #475569;">
        <div class="ticket-row">
            <span>Costo Total de la Reparación:</span>
            <span><?= formatMoney($pago['costo_total']) ?></span>
        </div>
        <div class="ticket-row">
            <span>Total Acumulado Pagado:</span>
            <span><?= formatMoney($pago['total_pagado_ot']) ?></span>
        </div>
        <div class="ticket-row" style="font-weight: 700; color: #b91c1c;">
            <span>Saldo Restante por Liquidar:</span>
            <span><?= formatMoney($pago['saldo_pendiente']) ?></span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 25px; border-top: 1px dashed #cbd5e1; padding-top: 15px; font-size: 0.75rem; color: #64748b;">
        ¡Gracias por confiar en Motor Express!<br>
        Conserve este comprobante para hacer efectiva su garantía técnica.<br>
        <em>www.motorexpress.pe</em>
    </div>
</div>

</body>
</html>
