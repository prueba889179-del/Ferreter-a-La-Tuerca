<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $rut       = trim($_POST['rut_identificacion'] ?? '');
        $nombre    = trim($_POST['nombre'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (!empty($rut) && !empty($nombre)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO clientes (rut_identificacion, nombre, correo, telefono, direccion)
                    VALUES (:rut, :nombre, :correo, :telefono, :direccion)
                ");
                $stmt->execute([
                    ':rut'       => $rut,
                    ':nombre'    => $nombre,
                    ':correo'    => $correo,
                    ':telefono'  => $telefono,
                    ':direccion' => $direccion
                ]);
                $mensajeExito = "Cliente '$nombre' registrado exitosamente.";
            } catch (PDOException $e) {
                $mensajeError = "Error: El R.U.T. o identificación ya se encuentra registrado.";
            }
        } else {
            $mensajeError = "R.U.T. y Nombre del cliente son obligatorios.";
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "Cliente eliminado.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar: el cliente tiene ventas registradas a su nombre.";
            }
        }
    }
}

$clientes = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM ventas v WHERE v.cliente_id = c.id) AS total_compras_realizadas
    FROM clientes c
    ORDER BY c.nombre ASC
")->fetchAll();

$pageTitle = 'Directorio de Clientes';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Clientes Registrados</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Empresas, constructoras y compradores habituales de Ferretería La Tuerca.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoCliente')">
            + Nuevo Cliente
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
    <input type="text" id="tableSearch" class="form-input" placeholder="Buscar cliente por RUT, nombre o teléfono...">
</div>

<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>RUT</th>
                    <th>Nombre / Razón Social</th>
                    <th>Teléfono</th>
                    <th>Correo Electrónico</th>
                    <th>Dirección</th>
                    <th>Compras</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted);">No hay clientes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cl): ?>
                        <tr>
                            <td><strong><?php echo e($cl['rut_identificacion']); ?></strong></td>
                            <td><strong><?php echo e($cl['nombre']); ?></strong></td>
                            <td><?php echo e($cl['telefono'] ?: '-'); ?></td>
                            <td><?php echo e($cl['correo'] ?: '-'); ?></td>
                            <td><?php echo e($cl['direccion'] ?: '-'); ?></td>
                            <td><span class="badge badge-info"><?php echo $cl['total_compras_realizadas']; ?> compras</span></td>
                            <td>
                                <form method="POST" action="clientes.php" onsubmit="return confirm('¿Eliminar cliente?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $cl['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO CLIENTE -->
<div class="modal-backdrop" id="modalNuevoCliente">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Registrar Nuevo Cliente</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoCliente')">&times;</button>
        </div>
        <form method="POST" action="clientes.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">RUT *</label>
                    <input type="text" name="rut_identificacion" class="form-input" placeholder="12.345.678-9" required>
                </div>
                <div>
                    <label class="form-label">Nombre / Razón Social *</label>
                    <input type="text" name="nombre" class="form-input" placeholder="Nombre completo o Empresa" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" class="form-input" placeholder="correo@ejemplo.cl">
                </div>
                <div>
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-input" placeholder="+56 9 ...">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Dirección / Obra</label>
                <input type="text" name="direccion" class="form-input" placeholder="Dirección de despacho o facturación">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
