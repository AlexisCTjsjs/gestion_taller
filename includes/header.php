<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

requireAuth();
$currentUser = currentUser();
$alerts = getSidebarAlerts();
$totalAlertCount = $alerts['repuestos_bajo_stock'] + $alerts['citas_hoy'];

$pageTitle = $pageTitle ?? 'Panel de Control - Motor Express';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Taller Automotriz Motor Express</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚗</text></svg>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/print.css" media="print">
</head>
<body>

<header class="header-wrapper">
    <div class="top-bar">
        <div class="top-bar-left">
            <span class="top-bar-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Av. Automotriz 450, Lima - Perú
            </span>
            <span class="top-bar-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                Central / Emergencias: (01) 458-9920 / +51 987 654 321
            </span>
            <span class="top-bar-item">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                Lun - Sáb: 8:00 AM - 7:00 PM
            </span>
        </div>
        <div class="top-bar-right">
            <span class="workshop-status-live">
                <span class="pulse-dot"></span> Taller Operativo y Bahías Activas
            </span>
            <span class="top-bar-item" style="color: #94a3b8;">
                Usuario: <strong style="color: #f8fafc; margin-left: 4px;"><?= sanitize($currentUser['nombre']) ?> (<?= sanitize($currentUser['rol']) ?>)</strong>
            </span>
        </div>
    </div>

    <div class="main-header">
        <a href="<?= BASE_URL ?>/index.php" class="brand-container">
            <div class="brand-logo-icon">
                <svg width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </div>
            <div class="brand-text">
                <h1>MOTOR <span>EXPRESS</span></h1>
                <p>Centro Técnico Automotriz</p>
            </div>
        </a>

        <div class="header-search">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="globalFilterInput" placeholder="Buscar por placa, cliente u orden..." onkeyup="if(event.key==='Enter'){ window.location.href='<?= BASE_URL ?>/modules/ordenes/index.php?buscar='+encodeURIComponent(this.value); }">
        </div>

        <div class="header-actions">
            <a href="<?= BASE_URL ?>/modules/citas/index.php?action=nueva" class="btn-header-quick btn-quick-accent" title="Programar nueva cita">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                + Nueva Cita
            </a>
            <a href="<?= BASE_URL ?>/modules/ordenes/crear.php" class="btn-header-quick btn-quick-primary" title="Generar nueva orden de trabajo">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                + Nueva Orden
            </a>

            <button type="button" class="btn-drawer-trigger" id="drawerTrigger" title="Abrir Menú Rápido y Notificaciones">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <?php if ($totalAlertCount > 0): ?>
                    <span class="drawer-badge"><?= $totalAlertCount ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>
</header>

<?php require_once __DIR__ . '/navbar.php'; ?>
<?php require_once __DIR__ . '/right_sidebar.php'; ?>

<main class="main-content">
    <?php if ($successMsg = getFlash('success')): ?>
        <div class="flash-alert flash-success">
            <span>✅ <?= sanitize($successMsg) ?></span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:inherit;">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg = getFlash('error')): ?>
        <div class="flash-alert flash-error">
            <span>⚠️ <?= sanitize($errorMsg) ?></span>
            <button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:inherit;">&times;</button>
        </div>
    <?php endif; ?>
