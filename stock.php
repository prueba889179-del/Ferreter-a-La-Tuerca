<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$pageTitle = 'Control de Stock e Inventario';
$mensajeExito = '';
$mensajeError = '';

// Procesar Creación o Edición de Producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $codigo        = trim($_POST['codigo'] ?? '');
        $nombre        = trim($_POST['nombre'] ?? '');
        $categoria     = trim($_POST['categoria'] ?? 'Ferretería General');
        $descripcion   = trim($_POST['descripcion'] ?? '');
        $precio_compra = (float)($_POST['precio_compra'] ?? 0);
        $precio_venta  = (float)($_POST['precio_venta'] ?? 0);
        $stock         = (int)($_POST['stock'] ?? 0);
        $stock_minimo  = (int)($_POST['stock_minimo'] ?? 5);
        $unidad        = trim($_POST['unidad'] ?? 'unidad');

        if (!empty($codigo) && !empty($nombre)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO productos (codigo, nombre, categoria, descripcion, precio_compra, precio_venta, stock, stock_minimo, unidad)
                    VALUES (:codigo, :nombre, :categoria, :descripcion, :precio_compra, :precio_venta, :stock, :stock_minimo, :unidad)
                ");
                $stmt->execute([
                    ':codigo'        => $codigo,
                    ':nombre'        => $nombre,
                    ':categoria'     => $categoria,
                    ':descripcion'   => $descripcion,
                    ':precio_compra' => $precio_compra,
                    ':precio_venta'  => $precio_venta,
                    ':stock'         => $stock,
                    ':stock_minimo'  => $stock_minimo,
                    ':unidad'        => $unidad
                ]);
                $prodId = $pdo->lastInsertId();

                // Registrar kardex inicial si hay stock
                if ($stock > 0) {
                    $stmtKardex = $pdo->prepare("
                        INSERT INTO movimientos (producto_id, usuario_id, tipo, cantidad, stock_anterior, stock_nuevo, motivo)
                        VALUES (:prod_id, :usr_id, 'entrada', :cant, 0, :cant, 'Inventario inicial al crear producto')
                    ");
                    $stmtKardex->execute([
                        ':prod_id' => $prodId,
                        ':usr_id'  => $_SESSION['usuario_id'],
                        ':cant'    => $stock
                    ]);
                }

                $mensajeExito = "Producto '$nombre' registrado con éxito.";
            } catch (PDOException $e) {
                $mensajeError = "Error al crear: El código '$codigo' ya está en uso.";
            }
        } else {
            $mensajeError = "Código y Nombre de producto son obligatorios.";
        }
    }

    if ($accion === 'editar') {
        $id            = (int)($_POST['id'] ?? 0);
        $nombre        = trim($_POST['nombre'] ?? '');
        $categoria     = trim($_POST['categoria'] ?? 'Ferretería General');
        $descripcion   = trim($_POST['descripcion'] ?? '');
        $precio_compra = (float)($_POST['precio_compra'] ?? 0);
        $precio_venta  = (float)($_POST['precio_venta'] ?? 0);
        $stock_minimo  = (int)($_POST['stock_minimo'] ?? 5);

        if ($id > 0 && !empty($nombre)) {
            $stmt = $pdo->prepare("
                UPDATE productos 
                SET nombre = :nombre, categoria = :categoria, descripcion = :descripcion, 
                    precio_compra = :precio_compra, precio_venta = :precio_venta, stock_minimo = :stock_minimo
                WHERE id = :id
            ");
            $stmt->execute([
                ':id'            => $id,
                ':nombre'        => $nombre,
                ':categoria'     => $categoria,
                ':descripcion'   => $descripcion,
                ':precio_compra' => $precio_compra,
                ':precio_venta'  => $precio_venta,
                ':stock_minimo'  => $stock_minimo
            ]);
            $mensajeExito = "Producto actualizado.";
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "Producto eliminado del catálogo.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar: el producto tiene ventas o compras asociadas.";
            }
        }
    }
}

// Listar productos
$productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Catálogo y Control de Stock</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Consulta de existencias, precios y administración de productos ferreteros.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoProducto')">
            + Nuevo Producto
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
    <div style="display: flex; gap: 1rem; align-items: center;">
        <input type="text" id="tableSearch" class="form-input" placeholder="Buscar por código, nombre o categoría...">
    </div>
</div>

<!-- TABLA DE PRODUCTOS -->
<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre / Descripción</th>
                    <th>Categoría</th>
                    <th>P. Compra</th>
                    <th>P. Venta</th>
                    <th>Stock Actual</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted);">No hay productos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><strong><?php echo e($p['codigo']); ?></strong></td>
                            <td>
                                <strong><?php echo e($p['nombre']); ?></strong>
                                <?php if (!empty($p['descripcion'])): ?>
                                    <br><span style="color: var(--text-secondary); font-size: 0.8rem;"><?php echo e($p['descripcion']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($p['categoria']); ?></td>
                            <td><?php echo formato_dinero($p['precio_compra']); ?></td>
                            <td><strong><?php echo formato_dinero($p['precio_venta']); ?></strong></td>
                            <td>
                                <strong style="font-size: 1.05rem;"><?php echo number_format($p['stock']); ?></strong> <?php echo e($p['unidad']); ?>
                            </td>
                            <td>
                                <?php if ($p['stock'] <= 0): ?>
                                    <span class="badge badge-danger">Agotado</span>
                                <?php elseif ($p['stock'] <= $p['stock_minimo']): ?>
                                    <span class="badge badge-warning">Bajo Mínimo (<?php echo $p['stock_minimo']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick='editarProducto(<?php echo json_encode($p); ?>)'>
                                        Editar
                                    </button>
                                    <form method="POST" action="stock.php" onsubmit="return confirm('¿Eliminar producto?');" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO PRODUCTO -->
<div class="modal-backdrop" id="modalNuevoProducto">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Registrar Nuevo Producto</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoProducto')">&times;</button>
        </div>
        <form method="POST" action="stock.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Código *</label>
                    <input type="text" name="codigo" class="form-input" placeholder="Ej: PER-001" required>
                </div>
                <div>
                    <label class="form-label">Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-input" placeholder="Ej: Perno Hexagonal 1/2" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Categoría</label>
                    <input type="text" name="categoria" class="form-input" placeholder="Ej: Fijaciones, Herramientas" value="Ferretería General">
                </div>
                <div>
                    <label class="form-label">Unidad de Medida</label>
                    <input type="text" name="unidad" class="form-input" placeholder="unidad, metro, kilo" value="unidad">
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-textarea" rows="2" placeholder="Detalles técnicos o medidas"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Precio Compra ($)</label>
                    <input type="number" step="0.01" name="precio_compra" class="form-input" placeholder="0" required>
                </div>
                <div>
                    <label class="form-label">Precio Venta ($) *</label>
                    <input type="number" step="0.01" name="precio_venta" class="form-input" placeholder="0" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="form-label">Stock Inicial</label>
                    <input type="number" name="stock" class="form-input" placeholder="0" value="0" required>
                </div>
                <div>
                    <label class="form-label">Stock Mínimo (Alerta)</label>
                    <input type="number" name="stock_minimo" class="form-input" placeholder="5" value="5" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoProducto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR PRODUCTO -->
<div class="modal-backdrop" id="modalEditarProducto">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Editar Producto</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalEditarProducto')">&times;</button>
        </div>
        <form method="POST" action="stock.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" id="edit_nombre" class="form-input" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Categoría</label>
                <input type="text" name="categoria" id="edit_categoria" class="form-input">
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion" class="form-textarea" rows="2"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Precio Compra ($)</label>
                    <input type="number" step="0.01" name="precio_compra" id="edit_precio_compra" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Precio Venta ($)</label>
                    <input type="number" step="0.01" name="precio_venta" id="edit_precio_venta" class="form-input" required>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Stock Mínimo (Alerta)</label>
                <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-input" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarProducto')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarProducto(p) {
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_nombre').value = p.nombre;
    document.getElementById('edit_categoria').value = p.categoria;
    document.getElementById('edit_descripcion').value = p.descripcion || '';
    document.getElementById('edit_precio_compra').value = p.precio_compra;
    document.getElementById('edit_precio_venta').value = p.precio_venta;
    document.getElementById('edit_stock_minimo').value = p.stock_minimo;
    openModal('modalEditarProducto');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
