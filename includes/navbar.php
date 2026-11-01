<?php
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';

function isActiveNav($pattern, $currentScript) {
    return (strpos($currentScript, $pattern) !== false) ? 'active' : '';
}
?>
<nav class="main-navbar">
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= (basename($currentScript) == 'index.php' && strpos($currentScript, 'modules') === false) ? 'active' : '' ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/clientes/index.php" class="nav-link <?= isActiveNav('/modules/clientes/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Clientes
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/vehiculos/index.php" class="nav-link <?= isActiveNav('/modules/vehiculos/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="22" height="13" rx="2" ry="2"></rect><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"></path><circle cx="6" cy="12" r="2"></circle><circle cx="18" cy="12" r="2"></circle></svg>
                Vehículos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/mecanicos/index.php" class="nav-link <?= isActiveNav('/modules/mecanicos/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                Mecánicos
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/citas/index.php" class="nav-link <?= isActiveNav('/modules/citas/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Citas Mantenimiento
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/ordenes/index.php" class="nav-link <?= isActiveNav('/modules/ordenes/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Órdenes de Trabajo
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/servicios/index.php" class="nav-link <?= isActiveNav('/modules/servicios/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Servicios
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/repuestos/index.php" class="nav-link <?= isActiveNav('/modules/repuestos/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                Repuestos & Stock
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/pagos/index.php" class="nav-link <?= isActiveNav('/modules/pagos/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Pagos (Efectivo/Tarjetas/Yape)
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/modules/reportes/index.php" class="nav-link <?= isActiveNav('/modules/reportes/', $currentScript) ?>">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Reportes Gerenciales
            </a>
        </li>
    </ul>
</nav>
