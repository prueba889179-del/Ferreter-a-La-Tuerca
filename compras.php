<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Módulo de Compras';
$mensajeExito = '';
$mensajeError = '';

// Registrar nueva compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'nueva_compra') {
        $proveedor_id  = (int)($_POST['proveedor_id'] ?? 0);
        $fecha         = $_POST['fecha'] ?? date('Y-m-d');
        $observaciones = trim($_POST['observaciones'] ?? '');
        $productos     = $_POST['producto_id'] ?? [];
        $cantidades    = $_POST['cantidad'] ?? [];
        $precios       = $_POST['precio_unitario'] ?? [];

        if ($proveedor_id > 0 && !empty($productos)) {
            $pdo->beginTransaction();
            try {
                // Generar folio de compra
                $ultimoId = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM compras")->fetchColumn();
                $folio = 'COM-' . date('Y') . '-' . str_pad($ultimoId, 4, '0', STR_PAD_LEFT);

                $itemsCompra = [];
                $totalCompra = 0;

                for ($i = 0; $i < count($productos); $i++) {
                    $prodId = (int)$productos[$i];
                    $cant   = (int)($cantidades[$i] ?? 0);
                    $precio = (float)($precios[$i] ?? 0);

                    if ($prodId > 0 && $cant > 0 && $precio >= 0) {
                        $stmtP = $pdo->prepare("SELECT * FROM productos WHERE id = :id FOR UPDATE");
                        $stmtP->execute([':id' => $prodId]);
                        $prod = $stmtP->fetch();

                        if (!$prod) {
                            throw new Exception("Producto no encontrado.");
                        }

                        $subtotal = $precio * $cant;
                        $totalCompra += $subtotal;

                        $itemsCompra[] = [
                            'producto_id'    => $prodId,
                            'nombre'         => $prod['nombre'],
                            'cantidad'       => $cant,
                            'precio_unitario'=> $precio,
                            'subtotal'       => $subtotal,
                            'stock_actual'   => (int)$prod['stock']
                        ];
                    }
                }

                if (empty($itemsCompra)) {
                    throw new Exception("Debe añadir al menos un producto a la compra.");
                }

                // Insertar encabezado de compra
                $stmtCompra = $pdo->prepare("
                    INSERT INTO compras (folio, proveedor_id, usuario_id, fecha, total, estado, observaciones)
                    VALUES (:folio, :prov_id, :usr_id, :fecha, :total, 'recibida', :obs)
                ");
                $stmtCompra->execute([
                    ':folio'   => $folio,
                    ':prov_id' => $proveedor_id,
                    ':usr_id'  => $_SESSION['usuario_id'],
                    ':fecha'   => $fecha,
                    ':total'   => $totalCompra,
                    ':obs'     => $observaciones
                ]);
                $compraId = $pdo->lastInsertId();

                // Detalle, aumento de stock y registro en Kardex
                $stmtDetalle = $pdo->prepare("
                    INSERT INTO detalle_compras (compra_id, producto_id, cantidad, precio_unitario, subtotal)
                    VALUES (:c_id, :p_id, :cant, :precio, :subtotal)
                ");
                $stmtStock = $pdo->prepare("
                    UPDATE productos 
                    SET stock = stock + :cant, precio_compra = :nuevo_precio
                    WHERE id = :p_id
                ");
                $stmtKardex = $pdo->prepare("
                    INSERT INTO movimientos (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                    VALUES (:p_id, :u_id, 'entrada', :cant, :ant, :nuevo, :motivo)
                ");

                foreach ($itemsCompra as $item) {
                    $stmtDetalle->execute([
                        ':c_id'     => $compraId,
                        ':p_id'     => $item['producto_id'],
                        ':cant'     => $item['cantidad'],
                        ':precio'   => $item['precio_unitario'],
                        ':subtotal' => $item['subtotal']
                    ]);

                    $stmtStock->execute([
                        ':cant'         => $item['cantidad'],
                        ':nuevo_precio' => $item['precio_unitario'],
                        ':p_id'         => $item['producto_id']
                    ]);

                    $nuevoStock = $item['stock_actual'] + $item['cantidad'];
                    $stmtKardex->execute([
                        ':p_id'   => $item['producto_id'],
                        ':u_id'   => $_SESSION['usuario_id'],
                        ':cant'   => $item['cantidad'],
                        ':ant'    => $item['stock_actual'],
                        ':nuevo'  => $nuevoStock,
                        ':motivo' => "Ingreso por compra $folio"
                    ]);
                }

                $pdo->commit();
                $mensajeExito = "Compra $folio procesada correctamente. Stock actualizado.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $mensajeError = $e->getMessage();
            }
        } else {
            $mensajeError = "Seleccione un proveedor y productos válidos.";
        }
    }
}

// Proveedores y Productos disponibles
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre ASC")->fetchAll();
$productos   = $pdo->query("SELECT id, codigo, nombre, precio_compra, stock FROM productos ORDER BY nombre ASC")->fetchAll();

// Historial de compras
$stmtCompras = $pdo->query("
    SELECT c.*, p.nombre AS proveedor_nombre, u.nombre AS usuario_nombre,
           (SELECT COUNT(*) FROM detalle_compras dc WHERE dc.compra_id = c.id) as total_items
    FROM compras c
    JOIN proveedores p ON c.proveedor_id = p.id
    JOIN usuarios u ON c.usuario_id = u.id
    ORDER BY c.creado_en DESC
    LIMIT 50
");
$compras = $stmtCompras->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Módulo de Compras y Proveedores</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Reabastecimiento de inventario y registro de órdenes a proveedores.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevaCompra')">
            + Registrar Compra
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
    <input type="text" id="tableSearch" class="form-input" placeholder="Buscar compra por folio o proveedor...">
</div>

<!-- TABLA DE COMPRAS -->
<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Responsable</th>
                    <th>Artículos</th>
                    <th>Total Compra</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($compras)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No hay compras registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($compras as $c): ?>
                        <tr>
                            <td><strong><?php echo e($c['folio']); ?></strong></td>
                            <td><?php echo formato_fecha($c['fecha']); ?></td>
                            <td><strong><?php echo e($c['proveedor_nombre']); ?></strong></td>
                            <td><?php echo e($c['usuario_nombre']); ?></td>
                            <td><?php echo $c['total_items']; ?> art.</td>
                            <td><strong style="color: var(--gold-light); font-size: 1rem;"><?php echo formato_dinero($c['total']); ?></strong></td>
                            <td><span class="badge badge-success"><?php echo e(ucfirst($c['estado'])); ?></span></td>
                            <td><span style="color: var(--text-secondary); font-size: 0.82rem;"><?php echo e($c['observaciones'] ?: '-'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA COMPRA -->
<div class="modal-backdrop" id="modalNuevaCompra">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Registrar Orden de Compra / Ingreso</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevaCompra')">&times;</button>
        </div>
        <form method="POST" action="compras.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="nueva_compra">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label">Proveedor *</label>
                    <select name="proveedor_id" class="form-select" required>
                        <option value="">-- Seleccionar Proveedor --</option>
                        <?php foreach ($proveedores as $pr): ?>
                            <option value="<?php echo $pr['id']; ?>"><?php echo e($pr['nombre']); ?> (<?php echo e($pr['rut']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Fecha de Compra</label>
                    <input type="date" name="fecha" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label" style="display: flex; justify-content: space-between;">
                    <span>Detalle de Artículos</span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="agregarLineaCompra()">+ Añadir Línea</button>
                </label>
                <div id="contenedorItemsCompra" style="display: flex; flex-direction: column; gap: 0.6rem;">
                    <div class="item-compra-fila" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 0.6rem; align-items: center;">
                        <select name="producto_id[]" class="form-select comp-prod" onchange="seleccionarProdCompra(this)" required>
                            <option value="">-- Seleccionar Producto --</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" data-costo="<?php echo $p['precio_compra']; ?>">
                                    [<?php echo e($p['codigo']); ?>] <?php echo e($p['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="cantidad[]" class="form-input comp-cant" min="1" value="1" placeholder="Cant." oninput="recalcularTotalCompra()" required>
                        <input type="number" step="0.01" name="precio_unitario[]" class="form-input comp-costo" placeholder="Costo Unit." oninput="recalcularTotalCompra()" required>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaCompra(this)">&times;</button>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Observaciones / Factura Proveedor</label>
                <input type="text" name="observaciones" class="form-input" placeholder="Ej: Factura N° 45802 de Distribuidora Central">
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: right;">
                <span style="font-size: 1rem; color: var(--text-secondary); margin-right: 1rem;">Total Compra:</span>
                <strong id="displayTotalCompra" style="font-size: 1.6rem; color: var(--gold-light); font-family: 'Oswald', sans-serif;">$ 0</strong>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevaCompra')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar e Incrementar Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function agregarLineaCompra() {
    const contenedor = document.getElementById('contenedorItemsCompra');
    const primeraFila = contenedor.querySelector('.item-compra-fila');
    const clon = primeraFila.cloneNode(true);
    clon.querySelector('.comp-prod').value = '';
    clon.querySelector('.comp-cant').value = '1';
    clon.querySelector('.comp-costo').value = '';
    contenedor.appendChild(clon);
}

function eliminarFilaCompra(btn) {
    const contenedor = document.getElementById('contenedorItemsCompra');
    if (contenedor.children.length > 1) {
        btn.closest('.item-compra-fila').remove();
        recalcularTotalCompra();
    }
}

function seleccionarProdCompra(selectElem) {
    const fila = selectElem.closest('.item-compra-fila');
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    const costo = parseFloat(selectedOption.getAttribute('data-costo')) || 0;
    fila.querySelector('.comp-costo').value = costo;
    recalcularTotalCompra();
}

function recalcularTotalCompra() {
    let granTotal = 0;
    const filas = document.querySelectorAll('.item-compra-fila');
    filas.forEach(f => {
        const cant = parseInt(f.querySelector('.comp-cant').value) || 0;
        const costo = parseFloat(f.querySelector('.comp-costo').value) || 0;
        granTotal += (cant * costo);
    });
    document.getElementById('displayTotalCompra').textContent = '$ ' + granTotal.toLocaleString('es-CL');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
