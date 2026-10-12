<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['usuario_nombre'] ?? 'Invitado';
$userRole = $_SESSION['usuario_rol'] ?? 'vendedor';
$isLoggedIn = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión Ferretería La Tuerca - Control de Stock, Entradas, Salidas, Compras y Ventas">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | Ferretería La Tuerca' : 'Ferretería La Tuerca | Sistema de Gestión'; ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="app-header">
    <div class="nav-container">
        <a href="index.php" class="brand-logo" id="headerLogoLink">
            <div>
                <div class="brand-text-title brand-font">FERRETERÍA LA TUERCA</div>
                <div class="brand-text-sub">SISTEMA INTEGRAL DE CONTROL</div>
            </div>
        </a>

        <nav>
            <ul class="nav-menu">
                <?php if ($isLoggedIn): ?>
                <li>
                    <a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" id="navInicio">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="stock.php" class="nav-link <?php echo ($currentPage == 'stock.php') ? 'active' : ''; ?>" id="navStock">
                        Stock
                    </a>
                </li>
                <li>
                    <a href="movimientos.php" class="nav-link <?php echo ($currentPage == 'movimientos.php') ? 'active' : ''; ?>" id="navMovimientos">
                        Entradas / Salidas
                    </a>
                </li>
                <li>
                    <a href="ventas.php" class="nav-link <?php echo ($currentPage == 'ventas.php') ? 'active' : ''; ?>" id="navVentas">
                        Ventas
                    </a>
                </li>
                <li>
                    <a href="compras.php" class="nav-link <?php echo ($currentPage == 'compras.php') ? 'active' : ''; ?>" id="navCompras">
                        Compras
                    </a>
                </li>
                <?php if ($userRole === 'administrador'): ?>
                <li>
                    <a href="usuarios.php" class="nav-link <?php echo ($currentPage == 'usuarios.php') ? 'active' : ''; ?>" id="navUsuarios">
                        Usuarios
                    </a>
                </li>
                <?php endif; ?>

                <li>
                    <div class="user-profile-badge">
                        <span style="font-size: 0.85rem; font-weight: 600;"><?php echo e($userName); ?></span>
                        <span class="role-tag role-<?php echo e($userRole); ?>">
                            <?php echo e(ucfirst($userRole)); ?>
                        </span>
                        <a href="logout.php" class="btn btn-secondary btn-sm" style="margin-left: 0.4rem; padding: 0.25rem 0.6rem;" title="Cerrar Sesión">
                            Salir
                        </a>
                    </div>
                </li>
                <?php else: ?>
                <li>
                    <a href="login.php" class="btn btn-secondary btn-sm" id="navLogin">
                        Iniciar Sesión
                    </a>
                </li>
                <li>
                    <a href="registro.php" class="btn btn-primary btn-sm" id="navRegistro">
                        Registrarse
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main class="main-content">
