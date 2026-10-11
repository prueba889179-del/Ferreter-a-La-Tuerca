<?php
/**
 * Funciones de Seguridad y Utilidades
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Token CSRF
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("Error de validación de seguridad (CSRF Token inválido o caducado).");
        }
    }
}

// Verificación de autenticación
function require_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Verificación de rol administrativo
function require_admin() {
    require_login();
    if (($_SESSION['usuario_rol'] ?? '') !== 'administrador') {
        echo "<script>alert('Acceso restringido: Se requieren permisos de Administrador.'); window.location.href='index.php';</script>";
        exit();
    }
}

// Sanitización de salida XSS
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Formateo de moneda
function formato_dinero($monto) {
    return '$ ' . number_format((float)$monto, 2, ',', '.');
}

// Formateo de fecha
function formato_fecha($fecha) {
    if (!$fecha) return '-';
    return date('d/m/Y', strtotime($fecha));
}
?>
