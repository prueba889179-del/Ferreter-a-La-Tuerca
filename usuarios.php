<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$mensajeExito = '';
$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre   = trim($_POST['nombre'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol      = $_POST['rol'] ?? 'vendedor';
        $telefono = trim($_POST['telefono'] ?? '');

        if (!empty($nombre) && !empty($correo) && !empty($password)) {
            $hashPass = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nombre, correo, password, rol, telefono, estado)
                    VALUES (:nombre, :correo, :password, :rol, :telefono, 'activo')
                ");
                $stmt->execute([
                    ':nombre'   => $nombre,
                    ':correo'   => $correo,
                    ':password' => $hashPass,
                    ':rol'      => $rol,
                    ':telefono' => $telefono
                ]);
                $mensajeExito = "Usuario '$nombre' creado exitosamente como '$rol'.";
            } catch (PDOException $e) {
                $mensajeError = "Error: El correo electrónico ya se encuentra registrado.";
            }
        } else {
            $mensajeError = "Complete Nombre, Correo y Contraseña.";
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['usuario_id']) {
            $mensajeError = "No puedes eliminar tu propio usuario en sesión activa.";
        } elseif ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "Usuario eliminado del sistema.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar: el usuario tiene ventas, compras o movimientos asignados.";
            }
        }
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nombre ASC")->fetchAll();

$pageTitle = 'Gestión de Usuarios';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.8rem; color: var(--gold-light);">Gestión de Usuarios y Personal</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Control de accesos y roles (Administradores, Vendedores y Bodegueros).</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoUsuario')">
            + Nuevo Usuario
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

<div class="data-card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Rol en Ferretería</th>
                    <th>Teléfono</th>
                    <th>Fecha Alta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?php echo e($u['nombre']); ?></strong></td>
                        <td><?php echo e($u['correo']); ?></td>
                        <td>
                            <span class="role-tag role-<?php echo e($u['rol']); ?>">
                                <?php echo e(ucfirst($u['rol'])); ?>
                            </span>
                        </td>
                        <td><?php echo e($u['telefono'] ?: '-'); ?></td>
                        <td><?php echo formato_fecha($u['creado_en']); ?></td>
                        <td>
                            <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                <form method="POST" action="usuarios.php" onsubmit="return confirm('¿Seguro que deseas eliminar a este usuario?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">(Sesión activa)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO USUARIO -->
<div class="modal-backdrop" id="modalNuevoUsuario">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color: var(--gold-light);">Crear Nuevo Usuario</h3>
            <button type="button" class="modal-close" onclick="closeModal('modalNuevoUsuario')">&times;</button>
        </div>
        <form method="POST" action="usuarios.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Nombre Completo *</label>
                <input type="text" name="nombre" class="form-input" placeholder="Ej: Marcos Silva" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="form-label">Correo Electrónico *</label>
                <input type="email" name="correo" class="form-input" placeholder="marcos@latuerca.cl" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="form-label">Rol Operativo *</label>
                    <select name="rol" class="form-select" required>
                        <option value="vendedor">Vendedor</option>
                        <option value="bodeguero">Bodeguero / Almacén</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-input" placeholder="+56 9 ...">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Contraseña de Acceso *</label>
                <input type="password" name="password" class="form-input" placeholder="Mínimo 6 caracteres" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoUsuario')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
