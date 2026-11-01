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
        $clienteId  = (int)($_POST['cliente_id'] ?? 0);
        $vehiculoId = (int)($_POST['vehiculo_id'] ?? 0);
        $mecanicoId = !empty($_POST['mecanico_id']) ? (int)$_POST['mecanico_id'] : null;
        $fechaCita  = trim($_POST['fecha_cita'] ?? '');
        $horaCita   = trim($_POST['hora_cita'] ?? '');
        $motivo     = trim($_POST['motivo'] ?? '');
        $servicio   = trim($_POST['servicio_sugerido'] ?? '');
        $estado     = trim($_POST['estado'] ?? 'Pendiente');
        $obs        = trim($_POST['observaciones'] ?? '');

        if ($clienteId <= 0 || $vehiculoId <= 0 || empty($fechaCita) || empty($horaCita) || empty($motivo)) {
            setFlash('error', "Complete los campos obligatorios para agendar la cita (*).");
            header("Location: index.php");
            exit;
        }

        $stmtCount = $pdo->query("SELECT MAX(id) as max_id FROM citas");
        $nextId = ($stmtCount->fetch()['max_id'] ?? 0) + 1;
        $codigoCita = 'CIT-' . date('Y') . '-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO citas (codigo_cita, cliente_id, vehiculo_id, mecanico_id, fecha_cita, hora_cita, motivo, servicio_sugerido, estado, observaciones)
            VALUES (:codigo, :cliente_id, :vehiculo_id, :mecanico_id, :fecha, :hora, :motivo, :servicio, :estado, :obs)
        ");
        $stmt->execute([
            ':codigo'      => $codigoCita,
            ':cliente_id'  => $clienteId,
            ':vehiculo_id' => $vehiculoId,
            ':mecanico_id' => $mecanicoId,
            ':fecha'       => $fechaCita,
            ':hora'        => $horaCita,
            ':motivo'      => $motivo,
            ':servicio'    => $servicio,
            ':estado'      => $estado,
            ':obs'         => $obs
        ]);

        setFlash('success', "Cita $codigoCita agendada correctamente para el " . formatDate($fechaCita) . " a las " . date('h:i A', strtotime($horaCita)));
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id         = (int)($_POST['id'] ?? 0);
        $clienteId  = (int)($_POST['cliente_id'] ?? 0);
        $vehiculoId = (int)($_POST['vehiculo_id'] ?? 0);
        $mecanicoId = !empty($_POST['mecanico_id']) ? (int)$_POST['mecanico_id'] : null;
        $fechaCita  = trim($_POST['fecha_cita'] ?? '');
        $horaCita   = trim($_POST['hora_cita'] ?? '');
        $motivo     = trim($_POST['motivo'] ?? '');
        $servicio   = trim($_POST['servicio_sugerido'] ?? '');
        $estado     = trim($_POST['estado'] ?? 'Pendiente');
        $obs        = trim($_POST['observaciones'] ?? '');

        if ($id <= 0 || $clienteId <= 0 || $vehiculoId <= 0 || empty($fechaCita) || empty($horaCita)) {
            setFlash('error', "Datos de cita inválidos.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE citas 
            SET cliente_id = :cliente_id, vehiculo_id = :vehiculo_id, mecanico_id = :mecanico_id,
                fecha_cita = :fecha, hora_cita = :hora, motivo = :motivo,
                servicio_sugerido = :servicio, estado = :estado, observaciones = :obs
            WHERE id = :id
        ");
        $stmt->execute([
            ':cliente_id'  => $clienteId,
            ':vehiculo_id' => $vehiculoId,
            ':mecanico_id' => $mecanicoId,
            ':fecha'       => $fechaCita,
            ':hora'        => $horaCita,
            ':motivo'      => $motivo,
            ':servicio'    => $servicio,
            ':estado'      => $estado,
            ':obs'         => $obs,
            ':id'          => $id
        ]);

        setFlash('success', "Cita actualizada correctamente.");
        header("Location: index.php");
        exit;
    }
}

if ($action === 'cancelar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE citas SET estado = 'Cancelada' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', "La cita fue marcada como cancelada.");
    }
    header("Location: index.php");
    exit;
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM citas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', "Cita eliminada del registro.");
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
