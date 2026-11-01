<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/helpers.php';

requireAuth();
$pdo = getDBConnection();
$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        setFlash('error', "Error de seguridad (CSRF).");
        header("Location: index.php");
        exit;
    }

    if ($action === 'crear') {
        $ordenId     = (int)($_POST['orden_id'] ?? 0);
        $metodoPago  = trim($_POST['metodo_pago'] ?? 'Efectivo');
        $monto       = (float)($_POST['monto'] ?? 0);
        $nroOper     = trim($_POST['nro_operacion'] ?? '');
        $tipoComp    = trim($_POST['comprobante_tipo'] ?? 'Boleta');
        $observacion = trim($_POST['observaciones'] ?? '');

        if ($ordenId <= 0 || $monto <= 0) {
            setFlash('error', "Debe seleccionar una orden de trabajo e indicar un monto mayor a 0.");
            header("Location: index.php");
            exit;
        }

        $stmtOT = $pdo->prepare("SELECT codigo_orden, costo_total, monto_pagado, saldo_pendiente FROM ordenes_trabajo WHERE id = :id");
        $stmtOT->execute([':id' => $ordenId]);
        $ot = $stmtOT->fetch();

        if (!$ot) {
            setFlash('error', "La orden de trabajo no existe.");
            header("Location: index.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmtCount = $pdo->query("SELECT MAX(id) as max_id FROM pagos");
            $nextId = ($stmtCount->fetch()['max_id'] ?? 0) + 1;
            $codigoPago = 'PAG-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            
            $serie = ($tipoComp === 'Factura') ? 'F001' : (($tipoComp === 'Boleta') ? 'B001' : 'RC01');
            $numeroComprobante = $serie . '-' . str_pad($nextId + 450, 6, '0', STR_PAD_LEFT);

            $stmtInsPago = $pdo->prepare("
                INSERT INTO pagos (
                    codigo_pago, orden_id, metodo_pago, monto, nro_operacion,
                    comprobante_tipo, comprobante_numero, fecha_pago, observaciones
                ) VALUES (
                    :cod, :oid, :metodo, :monto, :oper,
                    :tipo, :num, NOW(), :obs
                )
            ");
            $stmtInsPago->execute([
                ':cod'    => $codigoPago,
                ':oid'    => $ordenId,
                ':metodo' => $metodoPago,
                ':monto'  => $monto,
                ':oper'   => $nroOper,
                ':tipo'   => $tipoComp,
                ':num'    => $numeroComprobante,
                ':obs'    => $observacion
            ]);

            $pagoId = (int)$pdo->lastInsertId();

            $nuevoPagado = (float)$ot['monto_pagado'] + $monto;
            $nuevoSaldo  = max(0, (float)$ot['costo_total'] - $nuevoPagado);

            $stmtUpdOT = $pdo->prepare("
                UPDATE ordenes_trabajo 
                SET monto_pagado = :mp, saldo_pendiente = :sp 
                WHERE id = :id
            ");
            $stmtUpdOT->execute([
                ':mp' => $nuevoPagado,
                ':sp' => $nuevoSaldo,
                ':id' => $ordenId
            ]);

            $pdo->commit();
            setFlash('success', "Pago de " . formatMoney($monto) . " registrado mediante $metodoPago con comprobante $numeroComprobante.");
            header("Location: comprobante.php?id=" . $pagoId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', "Error al registrar el pago: " . $e->getMessage());
            header("Location: index.php");
            exit;
        }
    }
}

header("Location: index.php");
exit;
