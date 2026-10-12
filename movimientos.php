<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Entradas y Salidas de Inventario';
$mensajeExito = '';
$mensajeError = '';

// Procesar Entrada / Salida / Ajuste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $tipo        = $_POST['tipo'] ?? 'entrada';
    $cantidad    = (int)($_POST['cantidad'] ?? 0);
    $motivo      = trim($_POST['motivo'] ?? '');

    if ($producto_id > 0 && $cantidad > 0 && !empty($motivo)) {
        // Obtener stock actual con bloqueo
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE id = :id FOR UPDATE");
        $stmtProd->execute([':id' => $producto_id]);
        $producto = $stmtProd->fetch();

        if ($producto) {
            $stockActual = (int)$producto['stock'];
            $stockNuevo  = $stockActual;

            if ($tipo === 'entrada') {
                $stockNuevo = $stockActual + $cantidad;
            } elseif ($tipo === 'salida') {
                if ($cantidad > $stockActual) {
                    $mensajeError = "No hay suficiente stock disponible. Stock actual: $stockActual.";
                } else {
                    $stockNuevo = $stockActual - $cantidad;
                }
            } elseif ($tipo === 'ajuste') {
                $stockNuevo = $cantidad; // Ajuste directo al nuevo stock físico
                $diferencia = $stockNuevo - $stockActual;
                $motivo = "Ajuste físico de inventario: $motivo";
            }

            if (!$mensajeError) {
                // Actualizar stock de producto
                $stmtUpdate = $pdo->prepare("UPDATE productos SET stock = :nuevo WHERE id = :id");
                $stmtUpdate->execute([':nuevo' => $stockNuevo, ':id' => $producto_id]);

                // Registrar en kardex
                $stmtMov = $pdo->prepare("
                    INSERT INTO movimientos (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                    VALUES (:p_id, :u_id, :tipo, :cant, :ant, :nuevo, :motivo)
                ");
                $stmtMov->execute([
                    ':p_id'   => $producto_id,
                    ':u_id'   => $_SESSION['usuario_id'],
                    ':tipo'   => $tipo,
                    ':cant'   => $cantidad,
                    ':ant'    => $stockActual,
                    ':nuevo'  => $stockNuevo,
                    ':motivo' => $motivo
                ]);

                $mensajeExito = "Movimiento registrado con éxito. Nuevo stock: $stockNuevo.";
            }
        } else {
            $mensajeError = "Producto no encontrado.";
        }
    } else {
        $mensajeError = "Debe seleccionar un producto, cantidad mayor a 0 y especificar el motivo.";
    }
}

// Obtener productos para el selector
$productosLista = $pdo->query("SELECT id, codigo, nombre, stock FROM productos ORDER BY nombre ASC")->fetchAll();

// Obtener historial de movimientos
$stmtMovimientos = $pdo->query("
    SELECT m.*, p.codigo, p.nombre AS producto_nombre, u.nombre AS usuario_nombre
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    ORDER BY m.fecha DESC
    LIMIT 100
");
$movimientos = $stmtMovimientos->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Entradas, Salidas y Kardex</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Registro y trazabilidad de todos los ingresos y egresos de mercadería.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoMovimiento')">
            + Registrar Movimiento
        </button>
    </div>
</div>

<?php if ($mensajeExito): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <?php echo e($mensajeExito); ?>
    </div>
<?php endif; ?>

<?php if ($mensajeError): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <?php echo e($mensajeError); ?>
    </div>
<?php endif; ?>

<!-- BUSCADOR -->
<div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem;">
    <input type="text" id="tableSearch" class="form-input" placeholder="Buscar movimientos por producto, motivo o usuario...">
</div>

<!-- HISTORIAL DE MOVIMIENTOS -->
<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>Tipo</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Stock Ant.</th>
                    <th>Stock Nuevo</th>
                    <th>Motivo / Justificación</th>
                    <th>Operador</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No hay movimientos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($m['fecha'])); ?></td>
                            <td>
                                <?php if ($m['tipo'] === 'entrada'): ?>
                                    <span class="badge badge-success">Entrada</span>
                                <?php elseif ($m['tipo'] === 'salida'): ?>
                                    <span class="badge badge-danger">Salida</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Ajuste</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($m['codigo']); ?></strong> - <?php echo e($m['producto_nombre']); ?>
                            </td>
                            <td>
                                <strong><?php echo ($m['tipo'] === 'salida' ? '-' : '+') . number_format($m['cantidad']); ?></strong>
                            </td>
                            <td><?php echo number_format($m['stock_anterior']); ?></td>
                            <td><strong><?php echo number_format($m['stock_nuevo']); ?></strong></td>
                            <td><?php echo e($m['motivo']); ?></td>
                            <td><?php echo e($m['usuario_nombre']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO MOVIMIENTO -->
<div class="modal-backdrop" id="modalNuevoMovimiento">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Registrar Movimiento de Inventario</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoMovimiento')">&times;</button>
        </div>
        <form method="POST" action="movimientos.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label">Producto *</label>
                <select name="producto_id" class="form-select" required>
                    <option value="">-- Seleccionar Producto --</option>
                    <?php foreach ($productosLista as $pl): ?>
                        <option value="<?php echo $pl['id']; ?>">
                            [<?php echo e($pl['codigo']); ?>] <?php echo e($pl['nombre']); ?> (Stock: <?php echo $pl['stock']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Tipo de Operación *</label>
                    <select name="tipo" class="form-select" required>
                        <option value="entrada">Entrada (Ingreso a bodega)</option>
                        <option value="salida">Salida (Merma / Retiro / Devolución)</option>
                        <option value="ajuste">Ajuste de Conteo Físico</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Cantidad / Nuevo Stock *</label>
                    <input type="number" name="cantidad" class="form-input" min="1" placeholder="Ej: 10" required>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Motivo o Justificación *</label>
                <input type="text" name="motivo" class="form-input" placeholder="Ej: Mercadería dañada, recepción de pedido, inventario semestral..." required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoMovimiento')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar en Kardex</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
