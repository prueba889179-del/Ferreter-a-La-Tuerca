<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Módulo de Ventas';
$mensajeExito = '';
$mensajeError = '';

// Registrar nueva venta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'nueva_venta') {
        $cliente_id  = !empty($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
        $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
        $productos   = $_POST['producto_id'] ?? [];
        $cantidades  = $_POST['cantidad'] ?? [];

        if (!empty($productos)) {
            $pdo->beginTransaction();
            try {
                // Generar folio único
                $ultimoId = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM ventas")->fetchColumn();
                $folio = 'VEN-' . date('Y') . '-' . str_pad($ultimoId, 4, '0', STR_PAD_LEFT);

                // Calcular total y validar stock
                $itemsVenta = [];
                $totalVenta = 0;

                for ($i = 0; $i < count($productos); $i++) {
                    $prodId = (int)$productos[$i];
                    $cant   = (int)($cantidades[$i] ?? 0);

                    if ($prodId > 0 && $cant > 0) {
                        $stmtP = $pdo->prepare("SELECT * FROM productos WHERE id = :id FOR UPDATE");
                        $stmtP->execute([':id' => $prodId]);
                        $prod = $stmtP->fetch();

                        if (!$prod || $prod['stock'] < $cant) {
                            throw new Exception("Stock insuficiente para: " . ($prod ? $prod['nombre'] : "Producto ID $prodId") . ". Stock disponible: " . ($prod ? $prod['stock'] : 0));
                        }

                        $precio = (float)$prod['precio_venta'];
                        $subtotal = $precio * $cant;
                        $totalVenta += $subtotal;

                        $itemsVenta[] = [
                            'producto_id'    => $prodId,
                            'nombre'         => $prod['nombre'],
                            'cantidad'       => $cant,
                            'precio_unitario'=> $precio,
                            'subtotal'       => $subtotal,
                            'stock_actual'   => (int)$prod['stock']
                        ];
                    }
                }

                if (empty($itemsVenta)) {
                    throw new Exception("Debe agregar al menos un producto con cantidad válida.");
                }

                // Insertar encabezado de venta
                $stmtVenta = $pdo->prepare("
                    INSERT INTO ventas (folio, cliente_id, usuario_id, fecha, total, metodo_pago, estado)
                    VALUES (:folio, :cli_id, :usr_id, CURDATE(), :total, :metodo, 'completada')
                ");
                $stmtVenta->execute([
                    ':folio'  => $folio,
                    ':cli_id' => $cliente_id,
                    ':usr_id' => $_SESSION['usuario_id'],
                    ':total'  => $totalVenta,
                    ':metodo' => $metodo_pago
                ]);
                $ventaId = $pdo->lastInsertId();

                // Insertar detalle y descontar stock con kardex
                $stmtDetalle = $pdo->prepare("
                    INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                    VALUES (:v_id, :p_id, :cant, :precio, :subtotal)
                ");
                $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - :cant WHERE id = :p_id");
                $stmtKardex = $pdo->prepare("
                    INSERT INTO movimientos (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                    VALUES (:p_id, :u_id, 'salida', :cant, :ant, :nuevo, :motivo)
                ");

                foreach ($itemsVenta as $item) {
                    $stmtDetalle->execute([
                        ':v_id'     => $ventaId,
                        ':p_id'     => $item['producto_id'],
                        ':cant'     => $item['cantidad'],
                        ':precio'   => $item['precio_unitario'],
                        ':subtotal' => $item['subtotal']
                    ]);

                    $stmtStock->execute([
                        ':cant' => $item['cantidad'],
                        ':p_id' => $item['producto_id']
                    ]);

                    $nuevoStock = $item['stock_actual'] - $item['cantidad'];
                    $stmtKardex->execute([
                        ':p_id'   => $item['producto_id'],
                        ':u_id'   => $_SESSION['usuario_id'],
                        ':cant'   => $item['cantidad'],
                        ':ant'    => $item['stock_actual'],
                        ':nuevo'  => $nuevoStock,
                        ':motivo' => "Venta de mostrador $folio"
                    ]);
                }

                $pdo->commit();
                $mensajeExito = "Venta $folio realizada con éxito por " . formato_dinero($totalVenta);
            } catch (Exception $e) {
                $pdo->rollBack();
                $mensajeError = $e->getMessage();
            }
        } else {
            $mensajeError = "No se especificaron productos para la venta.";
        }
    }
}

// Clientes y Productos disponibles
$clientes  = $pdo->query("SELECT id, rut_identificacion, nombre FROM clientes ORDER BY nombre ASC")->fetchAll();
$productos = $pdo->query("SELECT id, codigo, nombre, precio_venta, stock FROM productos WHERE stock > 0 ORDER BY nombre ASC")->fetchAll();

// Historial de ventas
$stmtVentas = $pdo->query("
    SELECT v.*, COALESCE(c.nombre, 'Venta General / Mostrador') AS cliente_nombre, u.nombre AS vendedor_nombre,
           (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as total_items
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    JOIN usuarios u ON v.usuario_id = u.id
    ORDER BY v.creado_en DESC
    LIMIT 50
");
$ventas = $stmtVentas->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Módulo de Ventas y Facturación</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Registro de boletas, facturas y salidas automáticas de stock.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevaVenta')">
            + Realizar Venta
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
    <input type="text" id="tableSearch" class="form-input" placeholder="Buscar por folio, cliente o vendedor...">
</div>

<!-- TABLA DE VENTAS -->
<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Método de Pago</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No hay ventas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td><strong><?php echo e($v['folio']); ?></strong></td>
                            <td><?php echo formato_fecha($v['fecha']); ?></td>
                            <td><?php echo e($v['cliente_nombre']); ?></td>
                            <td><?php echo e($v['vendedor_nombre']); ?></td>
                            <td><?php echo e($v['metodo_pago']); ?></td>
                            <td><?php echo $v['total_items']; ?> art.</td>
                            <td><strong style="color: var(--gold-light); font-size: 1rem;"><?php echo formato_dinero($v['total']); ?></strong></td>
                            <td><span class="badge badge-success"><?php echo e(ucfirst($v['estado'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA VENTA -->
<div class="modal-backdrop" id="modalNuevaVenta">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Punto de Venta / Salida de Mostrador</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevaVenta')">&times;</button>
        </div>
        <form method="POST" action="ventas.php" id="formVenta">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="nueva_venta">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Cliente (Opcional)</label>
                    <select name="cliente_id" class="form-select">
                        <option value="">Cliente Ocasional / Mostrador</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo e($c['nombre']); ?> (<?php echo e($c['rut_identificacion']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Método de Pago</label>
                    <select name="metodo_pago" class="form-select">
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label" style="display: flex; justify-content: space-between;">
                    <span>Productos a Vender</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="agregarLineaVenta()">+ Añadir Producto</button>
                </label>
                <div id="contenedorItemsVenta" style="display: flex; flex-direction: column; gap: 0.6rem;">
                    <!-- FILA DE PRODUCTO 1 -->
                    <div class="item-venta-fila" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 0.6rem; align-items: center;">
                        <select name="producto_id[]" class="form-select prod-select" onchange="actualizarPrecioVenta(this)" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" data-precio="<?php echo $p['precio_venta']; ?>" data-stock="<?php echo $p['stock']; ?>">
                                    [<?php echo e($p['codigo']); ?>] <?php echo e($p['nombre']); ?> - $<?php echo number_format($p['precio_venta']); ?> (Stock: <?php echo $p['stock']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="cantidad[]" class="form-input prod-cant" min="1" value="1" placeholder="Cant." oninput="recalcularTotalVenta()" required>
                        <input type="text" class="form-input prod-subtotal" placeholder="Subtotal" readonly style="background: rgba(255,255,255,0.05);">
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaVenta(this)">&times;</button>
                    </div>
                </div>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: right;">
                <span style="font-size: 1rem; color: var(--text-secondary); margin-right: 1rem;">Total a Pagar:</span>
                <strong id="displayTotalVenta" style="font-size: 1.6rem; color: var(--gold-light); font-family: 'Oswald', sans-serif;">$ 0</strong>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevaVenta')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cobrar y Descontar Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function agregarLineaVenta() {
    const contenedor = document.getElementById('contenedorItemsVenta');
    const primeraFila = contenedor.querySelector('.item-venta-fila');
    const clon = primeraFila.cloneNode(true);
    clon.querySelector('.prod-select').value = '';
    clon.querySelector('.prod-cant').value = '1';
    clon.querySelector('.prod-subtotal').value = '';
    contenedor.appendChild(clon);
}

function eliminarFilaVenta(btn) {
    const contenedor = document.getElementById('contenedorItemsVenta');
    if (contenedor.children.length > 1) {
        btn.closest('.item-venta-fila').remove();
        recalcularTotalVenta();
    }
}

function actualizarPrecioVenta(selectElem) {
    const fila = selectElem.closest('.item-venta-fila');
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const precio = parseFloat(selectedOption.getAttribute('data-precio')) || 0;
    const maxStock = parseInt(selectedOption.getAttribute('data-stock')) || 1;
    const cantInput = fila.querySelector('.prod-cant');
    cantInput.max = maxStock;
    recalcularTotalVenta();
}

function recalcularTotalVenta() {
    let granTotal = 0;
    const filas = document.querySelectorAll('.item-venta-fila');
    filas.forEach(f => {
        const select = f.querySelector('.prod-select');
        const selectedOption = select.options[select.selectedIndex];
        const precio = parseFloat(selectedOption ? selectedOption.getAttribute('data-precio') : 0) || 0;
        const cant = parseInt(f.querySelector('.prod-cant').value) || 0;
        const subtotal = precio * cant;
        f.querySelector('.prod-subtotal').value = '$ ' + subtotal.toLocaleString('es-CL');
        granTotal += subtotal;
    });
    document.getElementById('displayTotalVenta').textContent = '$ ' + granTotal.toLocaleString('es-CL');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
