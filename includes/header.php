<?php
/**
 * Header Global para Abogados & Socios
 * Requisito: Logotipo de empresa, Menú con enlaces a cada módulo, visible en todas las páginas, rol y sesión
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['usuario_nombre'] ?? 'Invitado';
$userRole = $_SESSION['usuario_rol'] ?? 'abogado';
$isLoggedIn = isset($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema Integral de Gestión Jurídica, Contratos y Facturación - Abogados & Socios">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | Abogados & Socios' : 'Abogados & Socios | Gestión Legal'; ?></title>
    <!-- Favicon SVG -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%20%220%22%20%22100%22%20%22100%22><text y=%22.9em%22 font-size=%2290%22>⚖️</text></svg>">
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<header class="app-header">
    <div class="nav-container">
        <!-- Logotipo e Identidad Corporativa -->
        <a href="index.php" class="brand-logo" id="headerLogoLink">
            <div class="brand-icon">⚖️</div>
            <div>
                <div class="brand-text-title brand-font">ABOGADOS &amp; SOCIOS</div>
                <div class="brand-text-sub">ESTUDIO JURÍDICO &bull; LEGALTECH</div>
            </div>
        </a>

        <!-- Menú de Navegación Principal -->
        <nav>
            <ul class="nav-menu">
                <li>
                    <a href="index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" id="navInicio">
                        <span>🏛️</span> Inicio
                    </a>
                </li>
                <li>
                    <a href="contratos.php" class="nav-link <?php echo ($currentPage == 'contratos.php') ? 'active' : ''; ?>" id="navContratos">
                        <span>📜</span> Contratos
                    </a>
                </li>
                <li>
                    <a href="facturacion.php" class="nav-link <?php echo ($currentPage == 'facturacion.php') ? 'active' : ''; ?>" id="navFacturacion">
                        <span>💳</span> Facturación
                    </a>
                </li>
                <li>
                    <a href="clientes.php" class="nav-link <?php echo ($currentPage == 'clientes.php') ? 'active' : ''; ?>" id="navClientes">
                        <span>👥</span> Clientes
                    </a>
                </li>

                <?php if ($isLoggedIn && $userRole === 'administrador'): ?>
                <li>
                    <a href="usuarios.php" class="nav-link <?php echo ($currentPage == 'usuarios.php') ? 'active' : ''; ?>" id="navUsuarios">
                        <span>🛡️</span> Usuarios
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                <li>
                    <div class="user-profile-badge">
                        <span style="font-size: 0.85rem; font-weight: 600;"><?php echo e($userName); ?></span>
                        <span class="role-tag <?php echo ($userRole === 'administrador') ? 'role-admin' : 'role-abogado'; ?>">
                            <?php echo e(ucfirst($userRole)); ?>
                        </span>
                        <a href="logout.php" class="btn btn-secondary btn-sm" style="margin-left: 0.4rem; padding: 0.25rem 0.6rem;" title="Cerrar Sesión">
                            🚪 Salir
                        </a>
                    </div>
                </li>
                <?php else: ?>
                <li>
                    <a href="login.php" class="btn btn-primary btn-sm" id="navLogin">
                        🔑 Iniciar Sesión
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main class="main-content">
