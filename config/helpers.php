<?php

function formatMoney($amount) {
    return 'S/ ' . number_format((float)$amount, 2, '.', ',');
}

function formatDate($dateStr) {
    if (!$dateStr) return '-';
    $timestamp = strtotime($dateStr);
    return date('d/m/Y', $timestamp);
}

function formatDateTime($dateTimeStr) {
    if (!$dateTimeStr) return '-';
    $timestamp = strtotime($dateTimeStr);
    return date('d/m/Y h:i A', $timestamp);
}

function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function getStatusBadge($status) {
    $classMap = [
        // Estados de Citas
        'Pendiente'            => 'badge-warning',
        'Confirmada'           => 'badge-info',
        'En Proceso'           => 'badge-primary',
        'Completada'           => 'badge-success',
        'Cancelada'            => 'badge-danger',
        
        // Estados de Órdenes de Trabajo
        'En espera'            => 'badge-secondary',
        'En diagnóstico'       => 'badge-info',
        'En reparación'        => 'badge-warning',
        'Esperando repuestos'  => 'badge-orange',
        'Finalizada'           => 'badge-primary',
        'Entregada'            => 'badge-success',
        
        // Estados de Mecánicos y General
        'Disponible'           => 'badge-success',
        'En Servicio'          => 'badge-primary',
        'Vacaciones'           => 'badge-secondary',
        'Activo'               => 'badge-success',
        'Inactivo'             => 'badge-danger',
        
        // Pagos
        'Pagado'               => 'badge-success',
        'Parcial'              => 'badge-warning',
        'Deuda'                => 'badge-danger',
        'Crítica'              => 'badge-danger',
        'Moderada'             => 'badge-warning',
        'Leve'                 => 'badge-info'
    ];

    $cssClass = $classMap[$status] ?? 'badge-secondary';
    return '<span class="status-badge ' . $cssClass . '">' . sanitize($status) . '</span>';
}

function getSidebarAlerts() {
    $pdo = getDBConnection();
    
    // Repuestos con stock bajo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM repuestos WHERE stock <= stock_minimo AND estado = 'Activo'");
    $repuestosBajoStock = $stmt->fetch()['total'] ?? 0;
    
    // Órdenes activas (no finalizadas ni entregadas)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ordenes_trabajo WHERE estado NOT IN ('Entregada', 'Cancelada')");
    $ordenesActivas = $stmt->fetch()['total'] ?? 0;

    // Citas de hoy
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM citas WHERE fecha_cita = CURDATE() AND estado != 'Cancelada'");
    $citasHoy = $stmt->fetch()['total'] ?? 0;

    return [
        'repuestos_bajo_stock' => $repuestosBajoStock,
        'ordenes_activas'      => $ordenesActivas,
        'citas_hoy'            => $citasHoy
    ];
}
