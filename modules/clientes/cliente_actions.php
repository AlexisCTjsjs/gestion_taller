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
        $tipoDoc   = trim($_POST['tipo_documento'] ?? 'DNI');
        $numDoc    = trim($_POST['numero_documento'] ?? '');
        $nombres   = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (empty($numDoc) || empty($nombres) || empty($apellidos) || empty($telefono)) {
            setFlash('error', "Complete todos los campos obligatorios (*).");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM clientes WHERE numero_documento = :doc");
        $check->execute([':doc' => $numDoc]);
        if ($check->fetch()) {
            setFlash('error', "Ya existe un cliente con el documento " . sanitize($numDoc));
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO clientes (tipo_documento, numero_documento, nombres, apellidos, telefono, email, direccion)
            VALUES (:tipo, :doc, :nom, :ape, :tel, :email, :dir)
        ");
        $stmt->execute([
            ':tipo'  => $tipoDoc,
            ':doc'   => $numDoc,
            ':nom'   => $nombres,
            ':ape'   => $apellidos,
            ':tel'   => $telefono,
            ':email' => $email,
            ':dir'   => $direccion
        ]);

        setFlash('success', "Cliente '$nombres $apellidos' registrado con éxito.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $tipoDoc   = trim($_POST['tipo_documento'] ?? 'DNI');
        $numDoc    = trim($_POST['numero_documento'] ?? '');
        $nombres   = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($id <= 0 || empty($numDoc) || empty($nombres) || empty($apellidos) || empty($telefono)) {
            setFlash('error', "Datos de cliente inválidos.");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM clientes WHERE numero_documento = :doc AND id != :id");
        $check->execute([':doc' => $numDoc, ':id' => $id]);
        if ($check->fetch()) {
            setFlash('error', "El documento $numDoc ya pertenece a otro cliente.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE clientes 
            SET tipo_documento = :tipo, numero_documento = :doc, nombres = :nom, 
                apellidos = :ape, telefono = :tel, email = :email, direccion = :dir
            WHERE id = :id
        ");
        $stmt->execute([
            ':tipo'  => $tipoDoc,
            ':doc'   => $numDoc,
            ':nom'   => $nombres,
            ':ape'   => $apellidos,
            ':tel'   => $telefono,
            ':email' => $email,
            ':dir'   => $direccion,
            ':id'    => $id
        ]);

        setFlash('success', "Datos del cliente actualizados correctamente.");
        header("Location: index.php");
        exit;
    }
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $checkVeh = $pdo->prepare("SELECT COUNT(*) as total FROM vehiculos WHERE cliente_id = :id");
        $checkVeh->execute([':id' => $id]);
        $hasVeh = $checkVeh->fetch()['total'] ?? 0;

        if ($hasVeh > 0) {
            setFlash('error', "No se puede eliminar el cliente porque tiene vehículos asociados en el taller.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('success', "Cliente eliminado del sistema.");
        }
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
