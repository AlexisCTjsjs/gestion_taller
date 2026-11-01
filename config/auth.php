<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function getBaseUrl() {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if (strpos($scriptDir, 'gestion_taller') !== false) {
        $pos = strpos($scriptDir, 'gestion_taller');
        return substr($scriptDir, 0, $pos + strlen('gestion_taller'));
    }
    return '/gestion_taller';
}

define('BASE_URL', getBaseUrl());

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php?error=" . urlencode("Debes iniciar sesión para acceder al sistema."));
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'nombre'   => $_SESSION['user_nombre'] ?? 'Usuario',
        'apellido' => $_SESSION['user_apellido'] ?? '',
        'usuario'  => $_SESSION['user_usuario'] ?? '',
        'email'    => $_SESSION['user_email'] ?? '',
        'rol'      => $_SESSION['user_rol'] ?? 'Recepcionista',
        'avatar'   => $_SESSION['user_avatar'] ?? 'assets/images/default_avatar.png'
    ];
}

function requireRole($roles = []) {
    requireAuth();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $user = currentUser();
    if (!in_array($user['rol'], $roles)) {
        $_SESSION['flash_error'] = "No tienes permisos suficientes para realizar esta acción.";
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
}

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}
