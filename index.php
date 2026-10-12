<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Dashboard';

// Métricas de Ferretería
$totalProductos   = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$totalStock       = $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM productos")->fetchColumn();
$bajoStock        = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo")->fetchColumn();
$totalVentasMonto = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'completada'")->fetchColumn();
$totalComprasMonto= $pdo->query("SELECT COALESCE(SUM(total), 0) FROM compras WHERE estado = 'recibida'")->fetchColumn();

// Últimos movimientos de inventario
$stmtMovs = $pdo->query("
    SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    ORDER BY m.fecha DESC
    LIMIT 6
");
$movimientosRecientes = $stmtMovs->fetchAll();

// Productos con stock crítico
$stmtCriticos = $pdo->query("
    SELECT * FROM productos
    WHERE stock <= stock_minimo
    ORDER BY stock ASC
    LIMIT 5
");
$productosCriticos = $stmtCriticos->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; color: var(--gold-light); margin-bottom: 0.3rem;">Ferretería La Tuerca</h1>
    <p style="color: var(--text-secondary); font-size: 0.95rem;">Panel de control y resumen operativo de inventario, compras y ventas.</p>
</div>

<!-- METRICAS PRINCIPALES -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-title">Total Productos</div>
        <div class="metric-value"><?php echo number_format($totalProductos); ?></div>
        <div class="metric-subtitle">Artículos en catálogo</div>
    </div>

    <div class="metric-card">
        <div class="metric-title">Unidades en Stock</div>
        <div class="metric-value"><?php echo number_format($totalStock); ?></div>
        <div class="metric-subtitle">Existencias físicas globales</div>
    </div>

    <div class="metric-card" style="<?php echo $bajoStock > 0 ? 'border-color: rgba(239, 68, 68, 0.4);' : ''; ?>">
        <div class="metric-title" style="<?php echo $bajoStock > 0 ? 'color: #fca5a5;' : ''; ?>">Stock Crítico</div>
        <div class="metric-value" style="<?php echo $bajoStock > 0 ? 'color: #f87171;' : ''; ?>"><?php echo number_format($bajoStock); ?></div>
        <div class="metric-subtitle"><?php echo $bajoStock > 0 ? 'Requiere reabastecimiento' : 'Stock en niveles óptimos'; ?></div>
    </div>

    <div class="metric-card">
        <div class="metric-title">Total Ventas</div>
        <div class="metric-value"><?php echo formato_dinero($totalVentasMonto); ?></div>
        <div class="metric-subtitle">Ventas facturadas en sistema</div>
    </div>

    <div class="metric-card">
        <div class="metric-title">Total Compras</div>
        <div class="metric-value"><?php echo formato_dinero($totalComprasMonto); ?></div>
        <div class="metric-subtitle">Inversión en reposición</div>
    </div>
</div>

<!-- ACCIONES RÁPIDAS -->
<div class="data-card" style="margin-bottom: 2rem; padding: 1.25rem;">
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 600; color: var(--gold-light); font-size: 0.9rem; margin-right: 0.5rem;">Acciones Rápidas:</span>
        <a href="ventas.php" class="btn btn-primary btn-sm">Nueva Venta</a>
        <a href="compras.php" class="btn btn-secondary btn-sm">Registrar Compra</a>
        <a href="movimientos.php" class="btn btn-secondary btn-sm">Ajuste de Stock</a>
        <a href="stock.php" class="btn btn-secondary btn-sm">Ver Catálogo Completo</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
    <!-- ÚLTIMOS MOVIMIENTOS -->
    <div class="data-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary);">Últimos Movimientos de Stock</h3>
            <a href="movimientos.php" class="btn btn-secondary btn-sm">Ver Todos</a>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Stock Nuevo</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientosRecientes)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No hay movimientos registrados aún.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movimientosRecientes as $mov): ?>
                            <tr>
                                <td><?php echo formato_fecha($mov['fecha']); ?></td>
                                <td>
                                    <?php if ($mov['tipo'] === 'entrada'): ?>
                                        <span class="badge badge-success">Entrada</span>
                                    <?php elseif ($mov['tipo'] === 'salida'): ?>
                                        <span class="badge badge-danger">Salida</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Ajuste</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo e($mov['codigo']); ?></strong><br>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem;"><?php echo e($mov['producto_nombre']); ?></span>
                                </td>
                                <td><strong><?php echo ($mov['tipo'] === 'salida' ? '-' : '+') . number_format($mov['cantidad']); ?></strong></td>
                                <td><?php echo number_format($mov['stock_nuevo']); ?></td>
                                <td><?php echo e($mov['usuario_nombre']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PRODUCTOS EN ALERTA DE STOCK -->
    <div class="data-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary);">Stock Bajo / Mínimo</h3>
            <a href="stock.php" class="btn btn-secondary btn-sm">Inventario</a>
        </div>

        <?php if (empty($productosCriticos)): ?>
            <p style="color: var(--text-muted); font-size: 0.88rem;">Todos los productos tienen existencias suficientes.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($productosCriticos as $prod): ?>
                    <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 0.75rem 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <strong style="color: var(--text-primary); font-size: 0.88rem;"><?php echo e($prod['nombre']); ?></strong>
                            <span class="badge badge-danger"><?php echo $prod['stock']; ?> un.</span>
                        </div>
                        <div style="font-size: 0.78rem; color: var(--text-secondary); display: flex; justify-content: space-between;">
                            <span>Cód: <?php echo e($prod['codigo']); ?></span>
                            <span>Mínimo: <?php echo $prod['stock_minimo']; ?> un.</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
