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
        setFlash('error', "Error de validación de seguridad (CSRF).");
        header("Location: index.php");
        exit;
    }

    if ($action === 'crear') {
        $clienteId    = (int)($_POST['cliente_id'] ?? 0);
        $placa        = strtoupper(trim($_POST['placa'] ?? ''));
        $marca        = trim($_POST['marca'] ?? '');
        $modelo       = trim($_POST['modelo'] ?? '');
        $anio         = (int)($_POST['anio_fabricacion'] ?? 0);
        $color        = trim($_POST['color'] ?? '');
        $combustible  = trim($_POST['tipo_combustible'] ?? 'Gasolina');
        $kilometraje  = (int)($_POST['kilometraje'] ?? 0);
        $vin          = trim($_POST['vin_chasis'] ?? '');
        $notas        = trim($_POST['notas'] ?? '');

        if ($clienteId <= 0 || empty($placa) || empty($marca) || empty($modelo) || $anio <= 1900) {
            setFlash('error', "Complete todos los campos obligatorios del vehículo (*).");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM vehiculos WHERE placa = :placa");
        $check->execute([':placa' => $placa]);
        if ($check->fetch()) {
            setFlash('error', "Ya existe un vehículo registrado con la placa $placa.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehiculos (cliente_id, placa, marca, modelo, anio_fabricacion, color, tipo_combustible, kilometraje, vin_chasis, notas)
            VALUES (:cliente_id, :placa, :marca, :modelo, :anio, :color, :combustible, :km, :vin, :notas)
        ");
        $stmt->execute([
            ':cliente_id'   => $clienteId,
            ':placa'        => $placa,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':anio'         => $anio,
            ':color'        => $color,
            ':combustible'  => $combustible,
            ':km'           => $kilometraje,
            ':vin'          => $vin,
            ':notas'        => $notas
        ]);

        setFlash('success', "Vehículo con placa '$placa' registrado exitosamente.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id           = (int)($_POST['id'] ?? 0);
        $clienteId    = (int)($_POST['cliente_id'] ?? 0);
        $placa        = strtoupper(trim($_POST['placa'] ?? ''));
        $marca        = trim($_POST['marca'] ?? '');
        $modelo       = trim($_POST['modelo'] ?? '');
        $anio         = (int)($_POST['anio_fabricacion'] ?? 0);
        $color        = trim($_POST['color'] ?? '');
        $combustible  = trim($_POST['tipo_combustible'] ?? 'Gasolina');
        $kilometraje  = (int)($_POST['kilometraje'] ?? 0);
        $vin          = trim($_POST['vin_chasis'] ?? '');
        $notas        = trim($_POST['notas'] ?? '');

        if ($id <= 0 || $clienteId <= 0 || empty($placa) || empty($marca) || empty($modelo)) {
            setFlash('error', "Datos de vehículo inválidos.");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM vehiculos WHERE placa = :placa AND id != :id");
        $check->execute([':placa' => $placa, ':id' => $id]);
        if ($check->fetch()) {
            setFlash('error', "La placa $placa ya pertenece a otro vehículo.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE vehiculos 
            SET cliente_id = :cliente_id, placa = :placa, marca = :marca, modelo = :modelo,
                anio_fabricacion = :anio, color = :color, tipo_combustible = :combustible,
                kilometraje = :km, vin_chasis = :vin, notas = :notas
            WHERE id = :id
        ");
        $stmt->execute([
            ':cliente_id'   => $clienteId,
            ':placa'        => $placa,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':anio'         => $anio,
            ':color'        => $color,
            ':combustible'  => $combustible,
            ':km'           => $kilometraje,
            ':vin'          => $vin,
            ':notas'        => $notas,
            ':id'           => $id
        ]);

        setFlash('success', "Datos del vehículo actualizados correctamente.");
        header("Location: index.php");
        exit;
    }
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $checkOT = $pdo->prepare("SELECT COUNT(*) as total FROM ordenes_trabajo WHERE vehiculo_id = :id");
        $checkOT->execute([':id' => $id]);
        if ($checkOT->fetch()['total'] > 0) {
            setFlash('error', "No se puede eliminar el vehículo porque cuenta con órdenes de trabajo o reparaciones en el historial.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('success', "Vehículo eliminado del sistema.");
        }
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
