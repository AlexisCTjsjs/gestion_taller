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
        $nombres      = trim($_POST['nombres'] ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $dni          = trim($_POST['dni'] ?? '');
        $telefono     = trim($_POST['telefono'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $especialidad = trim($_POST['especialidad'] ?? 'Mantenimiento General');
        $estado       = trim($_POST['estado'] ?? 'Disponible');
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));

        if (empty($nombres) || empty($apellidos) || empty($dni) || empty($telefono)) {
            setFlash('error', "Complete todos los campos obligatorios (*).");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM mecanicos WHERE dni = :dni");
        $check->execute([':dni' => $dni]);
        if ($check->fetch()) {
            setFlash('error', "Ya existe un mecánico registrado con el DNI $dni.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO mecanicos (nombres, apellidos, dni, telefono, email, especialidad, estado, fecha_ingreso)
            VALUES (:nom, :ape, :dni, :tel, :email, :esp, :est, :fecha)
        ");
        $stmt->execute([
            ':nom'   => $nombres,
            ':ape'   => $apellidos,
            ':dni'   => $dni,
            ':tel'   => $telefono,
            ':email' => $email,
            ':esp'   => $especialidad,
            ':est'   => $estado,
            ':fecha' => $fechaIngreso
        ]);

        setFlash('success', "Mecánico '$nombres $apellidos' registrado exitosamente.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id           = (int)($_POST['id'] ?? 0);
        $nombres      = trim($_POST['nombres'] ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $dni          = trim($_POST['dni'] ?? '');
        $telefono     = trim($_POST['telefono'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $especialidad = trim($_POST['especialidad'] ?? 'Mantenimiento General');
        $estado       = trim($_POST['estado'] ?? 'Disponible');
        $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');

        if ($id <= 0 || empty($nombres) || empty($apellidos) || empty($dni) || empty($telefono)) {
            setFlash('error', "Datos de mecánico inválidos.");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM mecanicos WHERE dni = :dni AND id != :id");
        $check->execute([':dni' => $dni, ':id' => $id]);
        if ($check->fetch()) {
            setFlash('error', "El DNI $dni ya pertenece a otro mecánico.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE mecanicos 
            SET nombres = :nom, apellidos = :ape, dni = :dni, telefono = :tel,
                email = :email, especialidad = :esp, estado = :est, fecha_ingreso = :fecha
            WHERE id = :id
        ");
        $stmt->execute([
            ':nom'   => $nombres,
            ':ape'   => $apellidos,
            ':dni'   => $dni,
            ':tel'   => $telefono,
            ':email' => $email,
            ':esp'   => $especialidad,
            ':est'   => $estado,
            ':fecha' => $fechaIngreso ?: null,
            ':id'    => $id
        ]);

        setFlash('success', "Datos del mecánico actualizados correctamente.");
        header("Location: index.php");
        exit;
    }
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $checkOT = $pdo->prepare("SELECT COUNT(*) as total FROM ordenes_trabajo WHERE mecanico_id = :id");
        $checkOT->execute([':id' => $id]);
        if ($checkOT->fetch()['total'] > 0) {
            setFlash('error', "No se puede eliminar el mecánico porque tiene órdenes de trabajo registradas. Se recomienda cambiar su estado a 'Inactivo'.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM mecanicos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('success', "Mecánico eliminado del sistema.");
        }
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
