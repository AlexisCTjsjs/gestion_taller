<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = "Token de seguridad inválido o expirado. Intente nuevamente.";
    } elseif (empty($usuario) || empty($password)) {
        $error = "Por favor ingrese su usuario o correo y su contraseña.";
    } else {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (usuario = :u OR email = :e) AND estado = 'Activo' LIMIT 1");
        $stmt->execute([':u' => $usuario, ':e' => $usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_apellido'] = $user['apellido'];
            $_SESSION['user_usuario'] = $user['usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_rol'] = $user['rol'];
            $_SESSION['user_avatar'] = $user['avatar'];

            $updateStmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => $user['id']]);

            setFlash('success', "¡Bienvenido de nuevo, " . $user['nombre'] . "!");
            header("Location: " . BASE_URL . "/index.php");
            exit;
        } else {
            $error = "Credenciales incorrectas o usuario inactivo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Motor Express - Taller Automotriz</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚗</text></svg>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body.auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9)), url('assets/images/workshop_hero.jpg') center/cover no-repeat;
            padding: 1.5rem;
        }
        .auth-container {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
        }
        .auth-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 2.25rem 2rem 2rem 2rem;
            text-align: center;
            color: white;
            border-bottom: 3px solid var(--primary);
        }
        .auth-logo-badge {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: var(--radius-md);
            margin: 0 auto 1rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }
        .auth-body {
            padding: 2rem;
        }
    </style>
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-header">
        <div class="auth-logo-badge">
            <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
        </div>
        <h2 style="color: white; font-size: 1.6rem; letter-spacing: -0.5px;">MOTOR <span style="color: #60a5fa;">EXPRESS</span></h2>
        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 4px;">Sistema de Gestión de Taller Mecánico</p>
    </div>

    <div class="auth-body">
        <?php
        // Verificar si es la primera vez que se ingresa y no hay usuarios aún
        try {
            $pdoCheck = getDBConnection();
            $stmtCount = $pdoCheck->query("SELECT COUNT(*) as total FROM usuarios");
            $totalUsers = (int)($stmtCount->fetch()['total'] ?? 0);
            if ($totalUsers === 0):
        ?>
            <div class="flash-alert flash-success" style="margin-bottom: 1.25rem;">
                <span>ℹ️ Bienvenido. La base de datos está lista. Por favor <a href="register.php" style="text-decoration: underline; font-weight: 700;">regístrate aquí</a> para crear tu cuenta.</span>
            </div>
        <?php
            endif;
        } catch (Exception $e) {}
        ?>

        <?php if ($error): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 1.25rem;">
                <span>⚠️ <?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-alert flash-success" style="margin-bottom: 1.25rem;">
                <span>✅ <?= sanitize($success) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="usuario">Usuario o Correo Electrónico:</label>
                <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Ingrese su usuario o correo" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password">Contraseña de Acceso:</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 0.95rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                Ingresar al Sistema
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
            ¿No tienes una cuenta aún? <a href="register.php" style="font-weight: 600;">Registrarse</a>
        </div>
    </div>
</div>

</body>
</html>
