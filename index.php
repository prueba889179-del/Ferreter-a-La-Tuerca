<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Panel Principal';

$totalContratos = $pdo->query("SELECT COUNT(*) FROM contratos")->fetchColumn();
$contratosActivos = $pdo->query("SELECT COUNT(*) FROM contratos WHERE estado = 'Activo'")->fetchColumn();
$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$montoFacturado = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE estado_pago = 'Pagado'")->fetchColumn();

$stmtRecientes = $pdo->query("
    SELECT c.*, cl.nombre AS cliente_nombre, u.nombre AS abogado_nombre 
    FROM contratos c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN usuarios u ON c.abogado_id = u.id
    ORDER BY c.creado_en DESC
    LIMIT 5
");
$contratosRecientes = $stmtRecientes->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-banner">
    <div style="max-width: 800px;">
        <div style="display: inline-block; padding: 0.3rem 0.8rem; background: rgba(212, 175, 55, 0.15); border: 1px solid var(--gold-primary); border-radius: 20px; font-size: 0.8rem; color: var(--gold-light); margin-bottom: 1rem; font-weight: 600;">
            PLATAFORMA LEGALTECH &bull; CASO PRÁCTICO
        </div>
        <h1 class="hero-title">Gestión de Contratos &amp; <span>Facturación Jurídica</span></h1>
        <p class="hero-desc">
            Bienvenido a <strong>Abogados &amp; Socios</strong>, sistema centralizado para el control del ciclo de vida contractual, supervisión de aranceles y honorarios profesionales con estricta gobernanza jurídica y roles diferenciados.
        </p>
        <div class="hero-actions">
            <a href="contratos.php" class="btn btn-primary" id="btnIrContratos">
                📜 Gestionar Contratos
            </a>
            <a href="facturacion.php" class="btn btn-secondary" id="btnIrFacturacion">
                💳 Módulo de Facturación
            </a>
            <a href="clientes.php" class="btn btn-secondary" id="btnIrClientes">
                👥 Directorio de Clientes
            </a>
            <?php if ($_SESSION['usuario_rol'] === 'administrador'): ?>
            <a href="usuarios.php" class="btn btn-secondary" id="btnIrUsuarios">
                🛡️ Control de Usuarios
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="data-card" style="padding: 1.5rem; margin-bottom: 2.5rem; background: linear-gradient(180deg, rgba(17, 24, 39, 0.9), rgba(10, 15, 29, 0.95));">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="card-title">🎥 Complemento Multimedia: Presentación del Estudio</h3>
        <span style="font-size: 0.82rem; color: var(--gold-light); border: 1px solid var(--border-color); padding: 0.2rem 0.6rem; border-radius: 4px;">Recurso Educativo</span>
    </div>
    <div style="grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: center; display: grid;">
        <div style="border-radius: 12px; overflow: hidden; border: 1px solid var(--border-light); aspect-ratio: 16/9; background: #000;">
            <iframe width="100%" height="100%" src="https://www.youtube-nocookie.com/embed/ScMzIvxBSi4?si=demo" title="Legaltech Overview" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div>
            <h4 style="color: var(--gold-light); margin-bottom: 0.5rem; font-size: 1.1rem;">Eficacia Operativa y Rigor Normativo</h4>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                Nuestro módulo de contratos permite redactar, auditar y fiscalizar acuerdos de confidencialidad, mandatos judiciales y estatutos societarios en estricta coordinación con el departamento de contabilidad y facturación.
            </p>
            <ul style="color: var(--text-secondary); font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.4rem; padding-left: 1.2rem;">
                <li>Seguimiento de estados: <em>Borrador &rarr; En Revisión &rarr; Activo &rarr; Finalizado</em>.</li>
                <li>Integración directa con facturas fiscales correlativas.</li>
                <li>Acceso controlado: Administrador (acceso total) y Abogado (gestión operativa).</li>
            </ul>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📜</div>
        <div>
            <div class="stat-val"><?php echo e($totalContratos); ?></div>
            <div class="stat-lbl">Expedientes Contractuales</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #34d399; background: rgba(16, 185, 129, 0.1);">✅</div>
        <div>
            <div class="stat-val"><?php echo e($contratosActivos); ?></div>
            <div class="stat-lbl">Contratos Vigentes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #60a5fa; background: rgba(37, 99, 235, 0.1);">👥</div>
        <div>
            <div class="stat-val"><?php echo e($totalClientes); ?></div>
            <div class="stat-lbl">Empresas &amp; Clientes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color: #fbbf24; background: rgba(245, 158, 11, 0.1);">💰</div>
        <div>
            <div class="stat-val"><?php echo formato_dinero($montoFacturado); ?></div>
            <div class="stat-lbl">Honorarios Cobrados</div>
        </div>
    </div>
</div>

<div class="data-card">
    <div class="card-header-flex">
        <h3 class="card-title">⚖️ Contratos Recientes en Cartera</h3>
        <a href="contratos.php" class="btn btn-secondary btn-sm">Ver todos los contratos &rarr;</a>
    </div>
    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Expediente</th>
                    <th>Título del Contrato</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Abogado Asignado</th>
                    <th>Monto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contratosRecientes)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No hay contratos registrados en la plataforma.</td></tr>
                <?php else: ?>
                    <?php foreach ($contratosRecientes as $c): ?>
                    <tr>
                        <td><strong style="color: var(--gold-light);"><?php echo e($c['numero_expediente']); ?></strong></td>
                        <td><?php echo e($c['titulo']); ?></td>
                        <td><span style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo e($c['tipo_contrato']); ?></span></td>
                        <td><?php echo e($c['cliente_nombre']); ?></td>
                        <td><?php echo e($c['abogado_nombre']); ?></td>
                        <td><strong><?php echo formato_dinero($c['monto_acordado']); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '_', $c['estado']); ?>">
                                <?php echo e($c['estado']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
