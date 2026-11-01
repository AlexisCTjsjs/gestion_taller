<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellido  = trim($_POST['apellido'] ?? '');
    $usuario   = trim($_POST['usuario'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $confirm   = trim($_POST['confirm_password'] ?? '');
    $rol       = trim($_POST['rol'] ?? 'Recepcionista');
    $telefono  = trim($_POST['telefono'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = "Token de seguridad inválido.";
    } elseif (empty($nombre) || empty($apellido) || empty($usuario) || empty($email) || empty($password)) {
        $error = "Todos los campos obligatorios deben ser completados.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas ingresadas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $pdo = getDBConnection();
        $checkStmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u OR email = :e LIMIT 1");
        $checkStmt->execute([':u' => $usuario, ':e' => $email]);
        if ($checkStmt->fetch()) {
            $error = "El nombre de usuario o el correo electrónico ya se encuentran registrados.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, apellido, usuario, email, password, rol, telefono, estado)
                VALUES (:nombre, :apellido, :usuario, :email, :password, :rol, :telefono, 'Activo')
            ");
            $insertStmt->execute([
                ':nombre'   => $nombre,
                ':apellido' => $apellido,
                ':usuario'  => $usuario,
                ':email'    => $email,
                ':password' => $hash,
                ':rol'      => $rol,
                ':telefono' => $telefono
            ]);

            header("Location: login.php?success=" . urlencode("¡Usuario registrado exitosamente! Inicia sesión ahora."));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario | Motor Express</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚗</text></svg>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body.auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9)), url('assets/images/workshop_hero.jpg') center/cover no-repeat;
            padding: 2rem 1rem;
        }
        .auth-container {
            width: 100%;
            max-width: 580px;
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 1.75rem 2rem;
            text-align: center;
            color: white;
            border-bottom: 3px solid var(--primary);
        }
        .auth-body {
            padding: 2rem;
        }
    </style>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-header">
        <h2 style="color: white; font-size: 1.5rem;">Registro de Nuevo Personal</h2>
        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 4px;">Motor Express - Acceso al Sistema Web</p>
    </div>

    <div class="auth-body">
        <?php if ($error): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 1.25rem;">
                <span>⚠️ <?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Nombres: *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Carlos" required value="<?= sanitize($_POST['nombre'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Apellidos: *</label>
                    <input type="text" name="apellido" class="form-control" placeholder="Ej: Mendoza" required value="<?= sanitize($_POST['apellido'] ?? '') ?>">
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Usuario (Username): *</label>
                    <input type="text" name="usuario" class="form-control" placeholder="Ej: cmendoza" required value="<?= sanitize($_POST['usuario'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Teléfono / Celular:</label>
                    <input type="text" name="telefono" class="form-control" placeholder="Ej: 987654321" value="<?= sanitize($_POST['telefono'] ?? '') ?>">
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 1rem;">
                <div class="form-group">
                    <label>Correo Electrónico: *</label>
                    <input type="email" name="email" class="form-control" placeholder="carlos@motorexpress.pe" required value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Rol en el Taller: *</label>
                    <select name="rol" class="form-control" required>
                        <option value="Recepcionista">Recepcionista / Atención al Cliente</option>
                        <option value="Mecánico">Mecánico Especialista</option>
                        <option value="Administrador">Administrador de Taller</option>
                    </select>
                </div>
            </div>

            <div class="form-grid" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Contraseña: *</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Contraseña: *</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repite la contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 0.95rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                Crear Cuenta de Usuario
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.25rem; font-size: 0.85rem;">
            ¿Ya tienes una cuenta registrada? <a href="login.php" style="font-weight: 600;">Iniciar Sesión Aquí</a>
        </div>
    </div>
</div>

</body>
</html>
