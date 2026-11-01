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
        $codigo     = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre     = trim($_POST['nombre'] ?? '');
        $categoria  = trim($_POST['categoria'] ?? 'Filtros');
        $desc       = trim($_POST['descripcion'] ?? '');
        $stock      = (int)($_POST['stock'] ?? 0);
        $stockMin   = (int)($_POST['stock_minimo'] ?? 5);
        $precioComp = (float)($_POST['precio_compra'] ?? 0);
        $precioVent = (float)($_POST['precio_venta'] ?? 0);
        $ubicacion  = trim($_POST['ubicacion_almacen'] ?? 'Almacén Principal');
        $estado     = trim($_POST['estado'] ?? 'Activo');

        if (empty($codigo) || empty($nombre) || $precioVent <= 0) {
            setFlash('error', "Complete todos los campos obligatorios del repuesto (*).");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM repuestos WHERE codigo = :cod");
        $check->execute([':cod' => $codigo]);
        if ($check->fetch()) {
            setFlash('error', "Ya existe un repuesto con el código $codigo.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO repuestos (codigo, nombre, categoria, descripcion, stock, stock_minimo, precio_compra, precio_venta, ubicacion_almacen, estado)
            VALUES (:cod, :nom, :cat, :desc, :stock, :min, :pc, :pv, :ubi, :est)
        ");
        $stmt->execute([
            ':cod'   => $codigo,
            ':nom'   => $nombre,
            ':cat'   => $categoria,
            ':desc'  => $desc,
            ':stock' => $stock,
            ':min'   => $stockMin,
            ':pc'    => $precioComp,
            ':pv'    => $precioVent,
            ':ubi'   => $ubicacion,
            ':est'   => $estado
        ]);

        setFlash('success', "Repuesto '$nombre' registrado en inventario.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'editar') {
        $id         = (int)($_POST['id'] ?? 0);
        $codigo     = strtoupper(trim($_POST['codigo'] ?? ''));
        $nombre     = trim($_POST['nombre'] ?? '');
        $categoria  = trim($_POST['categoria'] ?? 'Filtros');
        $desc       = trim($_POST['descripcion'] ?? '');
        $stock      = (int)($_POST['stock'] ?? 0);
        $stockMin   = (int)($_POST['stock_minimo'] ?? 5);
        $precioComp = (float)($_POST['precio_compra'] ?? 0);
        $precioVent = (float)($_POST['precio_venta'] ?? 0);
        $ubicacion  = trim($_POST['ubicacion_almacen'] ?? '');
        $estado     = trim($_POST['estado'] ?? 'Activo');

        if ($id <= 0 || empty($codigo) || empty($nombre) || $precioVent <= 0) {
            setFlash('error', "Datos de repuesto inválidos.");
            header("Location: index.php");
            exit;
        }

        $check = $pdo->prepare("SELECT id FROM repuestos WHERE codigo = :cod AND id != :id");
        $check->execute([':cod' => $codigo, ':id' => $id]);
        if ($check->fetch()) {
            setFlash('error', "El código $codigo ya pertenece a otro repuesto.");
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE repuestos 
            SET codigo = :cod, nombre = :nom, categoria = :cat, descripcion = :desc,
                stock = :stock, stock_minimo = :min, precio_compra = :pc,
                precio_venta = :pv, ubicacion_almacen = :ubi, estado = :est
            WHERE id = :id
        ");
        $stmt->execute([
            ':cod'   => $codigo,
            ':nom'   => $nombre,
            ':cat'   => $categoria,
            ':desc'  => $desc,
            ':stock' => $stock,
            ':min'   => $stockMin,
            ':pc'    => $precioComp,
            ':pv'    => $precioVent,
            ':ubi'   => $ubicacion,
            ':est'   => $estado,
            ':id'    => $id
        ]);

        setFlash('success', "Repuesto '$nombre' actualizado con éxito.");
        header("Location: index.php");
        exit;
    }

    if ($action === 'ajustar_stock') {
        $id       = (int)($_POST['id'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $tipo     = trim($_POST['tipo'] ?? 'entrada');

        if ($id > 0 && $cantidad > 0) {
            if ($tipo === 'entrada') {
                $stmt = $pdo->prepare("UPDATE repuestos SET stock = stock + :cant WHERE id = :id");
            } else {
                $stmt = $pdo->prepare("UPDATE repuestos SET stock = GREATEST(0, stock - :cant) WHERE id = :id");
            }
            $stmt->execute([':cant' => $cantidad, ':id' => $id]);
            setFlash('success', "Stock actualizado correctamente.");
        }
        header("Location: index.php");
        exit;
    }
}

if ($action === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $check = $pdo->prepare("SELECT COUNT(*) as total FROM orden_repuestos WHERE repuesto_id = :id");
        $check->execute([':id' => $id]);
        if ($check->fetch()['total'] > 0) {
            setFlash('error', "No se puede eliminar el repuesto porque ya fue utilizado en órdenes de reparación registradas.");
        } else {
            $stmt = $pdo->prepare("DELETE FROM repuestos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('success', "Repuesto eliminado del inventario.");
        }
    }
    header("Location: index.php");
    exit;
}

header("Location: index.php");
exit;
