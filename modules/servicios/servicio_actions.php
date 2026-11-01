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
        $codigo    = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre    = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Mantenimiento Preventivo');
        $desc      = trim($_POST['descripcion'] ?? '');
        $precio    = (float)($_POST['precio'] ?? 0);
        $tiempo    = (int)($_POST['tiempo_estimado_min'] ?? 60);
        $estado    = trim($_POST['estado'] ?? 'Activo');

        if (empty($codigo) || empty($nombre) || $precio <= 0) {
            setFlash('error', "Complete todos los campos obligatorios del servicio (*).");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM servicios WHERE codigo = :cod");
        $check->execute([':cod' => $codigo]);
        if ($check->fetch()) {
            setFlash('error', "Ya existe un servicio registrado con el código $codigo.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO servicios (codigo, nombre, categoria, descripcion, precio, tiempo_estimado_min, estado)
            VALUES (:cod, :nom, :cat, :desc, :precio, :tiempo, :est)
        ");
        $stmt->execute([
            ':cod'    => $codigo,
            ':nom'    => $nombre,
            ':cat'    => $categoria,
            ':desc'   => $desc,
            ':precio' => $precio,
            ':tiempo' => $tiempo,
            ':est'    => $estado
        ]);

        setFlash('success', "Servicio '$nombre' añadido al catálogo correctamente.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $codigo    = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre    = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Mantenimiento Preventivo');
        $desc      = trim($_POST['descripcion'] ?? '');
        $precio    = (float)($_POST['precio'] ?? 0);
        $tiempo    = (int)($_POST['tiempo_estimado_min'] ?? 60);
        $estado    = trim($_POST['estado'] ?? 'Activo');

        if ($id <= 0 || empty($codigo) || empty($nombre) || $precio <= 0) {
            setFlash('error', "Datos de servicio inválidos.");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM servicios WHERE codigo = :cod AND id != :id");
        $check->execute([':cod' => $codigo, ':id' => $id]);
        if ($check->fetch()) {
            setFlash('error', "El código $codigo ya pertenece a otro servicio.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE servicios 
            SET codigo = :cod, nombre = :nom, categoria = :cat, descripcion = :desc,
                precio = :precio, tiempo_estimado_min = :tiempo, estado = :est
            WHERE id = :id
        ");
        $stmt->execute([
            ':cod'    => $codigo,
            ':nom'    => $nombre,
            ':cat'    => $categoria,
            ':desc'   => $desc,
            ':precio' => $precio,
            ':tiempo' => $tiempo,
            ':est'    => $estado,
            ':id'     => $id
        ]);

        setFlash('success', "Servicio actualizado correctamente.");
        header("Location: index.php");
        exit;
    }
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $check = $pdo->prepare("SELECT COUNT(*) as total FROM orden_servicios WHERE servicio_id = :id");
        $check->execute([':id' => $id]);
        if ($check->fetch()['total'] > 0) {
            setFlash('error', "No se puede eliminar el servicio porque ya fue aplicado a órdenes de trabajo históricas. Se recomienda cambiar su estado a 'Inactivo'.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM servicios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('success', "Servicio eliminado del catálogo.");
        }
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
