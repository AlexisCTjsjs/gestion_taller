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
        $clienteId     = (int)($_POST['cliente_id'] ?? 0);
        $vehiculoId    = (int)($_POST['vehiculo_id'] ?? 0);
        $mecanicoId    = !empty($_POST['mecanico_id']) ? (int)$_POST['mecanico_id'] : null;
        $citaId        = !empty($_POST['cita_id']) ? (int)$_POST['cita_id'] : null;
        $kmIngreso     = (int)($_POST['kilometraje_ingreso'] ?? 0);
        $combustible   = trim($_POST['nivel_combustible'] ?? '1/2');
        $fechaEntrega  = !empty($_POST['fecha_estimada_entrega']) ? $_POST['fecha_estimada_entrega'] : null;
        $obsCliente    = trim($_POST['observaciones_cliente'] ?? '');
        $estadoInicial = trim($_POST['estado'] ?? 'En espera');

        $fallas        = trim($_POST['fallas_detectadas'] ?? '');
        $sistema       = trim($_POST['sistema_afectado'] ?? 'General');
        $gravedad      = trim($_POST['gravedad'] ?? 'Moderada');
        $dtc           = trim($_POST['scanner_codigos_dtc'] ?? '');
        $recom         = trim($_POST['recomendaciones'] ?? '');

        $serviciosSeleccionados = $_POST['servicios'] ?? [];
        $repuestosSeleccionados = $_POST['repuestos'] ?? [];

        if ($clienteId <= 0 || $vehiculoId <= 0) {
            setFlash('error', "Debe seleccionar un cliente y un vehículo válidos.");
            header("Location: crear.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmtCount = $pdo->query("SELECT MAX(id) as max_id FROM ordenes_trabajo");
            $nextId = ($stmtCount->fetch()['max_id'] ?? 0) + 1;
            $codigoOrden = 'OT-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $stmtOT = $pdo->prepare("
                INSERT INTO ordenes_trabajo (
                    codigo_orden, cliente_id, vehiculo_id, mecanico_id, cita_id,
                    fecha_ingreso, fecha_estimada_entrega, kilometraje_ingreso,
                    nivel_combustible, estado, observaciones_cliente,
                    total_servicios, total_repuestos, costo_total, monto_pagado, saldo_pendiente
                ) VALUES (
                    :cod, :cliente_id, :vehiculo_id, :mecanico_id, :cita_id,
                    NOW(), :fecha_entrega, :km,
                    :comb, :estado, :obs,
                    0, 0, 0, 0, 0
                )
            ");
            $stmtOT->execute([
                ':cod'           => $codigoOrden,
                ':cliente_id'    => $clienteId,
                ':vehiculo_id'   => $vehiculoId,
                ':mecanico_id'   => $mecanicoId,
                ':cita_id'       => $citaId,
                ':fecha_entrega' => $fechaEntrega,
                ':km'            => $kmIngreso,
                ':comb'          => $combustible,
                ':estado'        => $estadoInicial,
                ':obs'           => $obsCliente
            ]);

            $ordenId = (int)$pdo->lastInsertId();

            if (!empty($fallas)) {
                $stmtDiag = $pdo->prepare("
                    INSERT INTO diagnosticos (
                        orden_id, vehiculo_id, mecanico_id, fallas_detectadas,
                        sistema_afectado, gravedad, scanner_codigos_dtc, recomendaciones
                    ) VALUES (
                        :orden_id, :vehiculo_id, :mecanico_id, :fallas,
                        :sistema, :gravedad, :dtc, :recom
                    )
                ");
                $stmtDiag->execute([
                    ':orden_id'    => $ordenId,
                    ':vehiculo_id' => $vehiculoId,
                    ':mecanico_id' => $mecanicoId,
                    ':fallas'      => $fallas,
                    ':sistema'     => $sistema,
                    ':gravedad'    => $gravedad,
                    ':dtc'         => $dtc,
                    ':recom'       => $recom
                ]);
            }

            $totalServicios = 0.0;
            if (is_array($serviciosSeleccionados) && count($serviciosSeleccionados) > 0) {
                $stmtGetSrv = $pdo->prepare("SELECT precio FROM servicios WHERE id = :id");
                $stmtInsSrv = $pdo->prepare("INSERT INTO orden_servicios (orden_id, servicio_id, precio) VALUES (:oid, :sid, :precio)");

                foreach ($serviciosSeleccionados as $srvId) {
                    $srvId = (int)$srvId;
                    if ($srvId > 0) {
                        $stmtGetSrv->execute([':id' => $srvId]);
                        $srvData = $stmtGetSrv->fetch();
                        if ($srvData) {
                            $precio = (float)$srvData['precio'];
                            $stmtInsSrv->execute([
                                ':oid'    => $ordenId,
                                ':sid'    => $srvId,
                                ':precio' => $precio
                            ]);
                            $totalServicios += $precio;
                        }
                    }
                }
            }

            $totalRepuestos = 0.0;
            if (is_array($repuestosSeleccionados)) {
                $stmtGetRep = $pdo->prepare("SELECT precio_venta, stock FROM repuestos WHERE id = :id");
                $stmtInsRep = $pdo->prepare("INSERT INTO orden_repuestos (orden_id, repuesto_id, cantidad, precio_unitario, subtotal) VALUES (:oid, :rid, :cant, :pu, :sub)");
                $stmtDescStock = $pdo->prepare("UPDATE repuestos SET stock = GREATEST(0, stock - :cant) WHERE id = :id");

                foreach ($repuestosSeleccionados as $repId => $cant) {
                    $repId = (int)$repId;
                    $cant = (int)$cant;
                    if ($repId > 0 && $cant > 0) {
                        $stmtGetRep->execute([':id' => $repId]);
                        $repData = $stmtGetRep->fetch();
                        if ($repData) {
                            $pu = (float)$repData['precio_venta'];
                            $subtotal = $pu * $cant;
                            $stmtInsRep->execute([
                                ':oid'  => $ordenId,
                                ':rid'  => $repId,
                                ':cant' => $cant,
                                ':pu'   => $pu,
                                ':sub'  => $subtotal
                            ]);
                            $stmtDescStock->execute([':cant' => $cant, ':id' => $repId]);
                            $totalRepuestos += $subtotal;
                        }
                    }
                }
            }

            $costoTotal = $totalServicios + $totalRepuestos;
            $stmtUpdateTotales = $pdo->prepare("
                UPDATE ordenes_trabajo 
                SET total_servicios = :ts, total_repuestos = :tr, costo_total = :ct, saldo_pendiente = :ct
                WHERE id = :id
            ");
            $stmtUpdateTotales->execute([
                ':ts' => $totalServicios,
                ':tr' => $totalRepuestos,
                ':ct' => $costoTotal,
                ':id' => $ordenId
            ]);

            $stmtUpdateKm = $pdo->prepare("UPDATE vehiculos SET kilometraje = GREATEST(kilometraje, :km) WHERE id = :vid");
            $stmtUpdateKm->execute([':km' => $kmIngreso, ':vid' => $vehiculoId]);

            if ($citaId) {
                $stmtCita = $pdo->prepare("UPDATE citas SET estado = 'En Proceso' WHERE id = :cid");
                $stmtCita->execute([':cid' => $citaId]);
            }

            if ($mecanicoId) {
                $stmtMec = $pdo->prepare("UPDATE mecanicos SET estado = 'En Servicio' WHERE id = :mid");
                $stmtMec->execute([':mid' => $mecanicoId]);
            }

            $pdo->commit();
            setFlash('success', "¡Orden de Trabajo $codigoOrden generada exitosamente!");
            header("Location: detalle.php?id=" . $ordenId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', "Error al generar la orden: " . $e->getMessage());
            header("Location: crear.php");
            exit;
        }
    }

    if ($action === 'cambiar_estado') {
        $ordenId     = (int)($_POST['orden_id'] ?? 0);
        $nuevoEstado = trim($_POST['nuevo_estado'] ?? '');

        if ($ordenId > 0 && !empty($nuevoEstado)) {
            $stmt = $pdo->prepare("
                UPDATE ordenes_trabajo 
                SET estado = :est,
                    fecha_cierre = CASE WHEN :est IN ('Finalizada', 'Entregada') THEN NOW() ELSE fecha_cierre END
                WHERE id = :id
            ");
            $stmt->execute([':est' => $nuevoEstado, ':id' => $ordenId]);

            if (in_array($nuevoEstado, ['Finalizada', 'Entregada'])) {
                $stmtMecId = $pdo->prepare("SELECT mecanico_id FROM ordenes_trabajo WHERE id = :id");
                $stmtMecId->execute([':id' => $ordenId]);
                $mecId = $stmtMecId->fetch()['mecanico_id'] ?? null;
                if ($mecId) {
                    $stmtCheckOther = $pdo->prepare("SELECT COUNT(*) as c FROM ordenes_trabajo WHERE mecanico_id = :mid AND estado NOT IN ('Finalizada', 'Entregada', 'Cancelada')");
                    $stmtCheckOther->execute([':mid' => $mecId]);
                    if ($stmtCheckOther->fetch()['c'] == 0) {
                        $pdo->prepare("UPDATE mecanicos SET estado = 'Disponible' WHERE id = :mid")->execute([':mid' => $mecId]);
                    }
                }
            }

            setFlash('success', "Estado de la orden actualizado a: $nuevoEstado.");
            header("Location: detalle.php?id=" . $ordenId);
            exit;
        }
    }

    if ($action === 'agregar_servicio') {
        $ordenId    = (int)($_POST['orden_id'] ?? 0);
        $servicioId = (int)($_POST['servicio_id'] ?? 0);
        $precio     = (float)($_POST['precio'] ?? 0);

        if ($ordenId > 0 && $servicioId > 0 && $precio > 0) {
            $stmt = $pdo->prepare("INSERT INTO orden_servicios (orden_id, servicio_id, precio) VALUES (:oid, :sid, :p)");
            $stmt->execute([':oid' => $ordenId, ':sid' => $servicioId, ':p' => $precio]);

            recalcularTotalesOrden($pdo, $ordenId);
            setFlash('success', "Servicio agregado a la orden.");
        }
        header("Location: detalle.php?id=" . $ordenId);
        exit;
    }

    if ($action === 'agregar_repuesto') {
        $ordenId    = (int)($_POST['orden_id'] ?? 0);
        $repuestoId = (int)($_POST['repuesto_id'] ?? 0);
        $cantidad   = (int)($_POST['cantidad'] ?? 1);

        if ($ordenId > 0 && $repuestoId > 0 && $cantidad > 0) {
            $stmtRep = $pdo->prepare("SELECT precio_venta, stock FROM repuestos WHERE id = :id");
            $stmtRep->execute([':id' => $repuestoId]);
            $rData = $stmtRep->fetch();

            if ($rData) {
                $pu = (float)$rData['precio_venta'];
                $subtotal = $pu * $cantidad;

                $stmtIns = $pdo->prepare("INSERT INTO orden_repuestos (orden_id, repuesto_id, cantidad, precio_unitario, subtotal) VALUES (:oid, :rid, :cant, :pu, :sub)");
                $stmtIns->execute([':oid' => $ordenId, ':rid' => $repuestoId, ':cant' => $cantidad, ':pu' => $pu, ':sub' => $subtotal]);

                $stmtDesc = $pdo->prepare("UPDATE repuestos SET stock = GREATEST(0, stock - :cant) WHERE id = :id");
                $stmtDesc->execute([':cant' => $cantidad, ':id' => $repuestoId]);

                recalcularTotalesOrden($pdo, $ordenId);
                setFlash('success', "Repuesto añadido y descontado de almacén.");
            }
        }
        header("Location: detalle.php?id=" . $ordenId);
        exit;
    }
}

function recalcularTotalesOrden($pdo, $ordenId) {
    $stmtS = $pdo->prepare("SELECT COALESCE(SUM(precio), 0) as s FROM orden_servicios WHERE orden_id = :id");
    $stmtS->execute([':id' => $ordenId]);
    $totalS = (float)$stmtS->fetch()['s'];

    $stmtR = $pdo->prepare("SELECT COALESCE(SUM(subtotal), 0) as r FROM orden_repuestos WHERE orden_id = :id");
    $stmtR->execute([':id' => $ordenId]);
    $totalR = (float)$stmtR->fetch()['r'];

    $costoTotal = $totalS + $totalR;

    $stmtP = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) as p FROM pagos WHERE orden_id = :id");
    $stmtP->execute([':id' => $ordenId]);
    $montoPagado = (float)$stmtP->fetch()['p'];

    $saldoPendiente = max(0, $costoTotal - $montoPagado);

    $stmtUpd = $pdo->prepare("
        UPDATE ordenes_trabajo 
        SET total_servicios = :ts, total_repuestos = :tr, costo_total = :ct, monto_pagado = :mp, saldo_pendiente = :sp
        WHERE id = :id
    ");
    $stmtUpd->execute([
        ':ts' => $totalS,
        ':tr' => $totalR,
        ':ct' => $costoTotal,
        ':mp' => $montoPagado,
        ':sp' => $saldoPendiente,
        ':id' => $ordenId
    ]);
}

header("Location: index.php");
exit;
