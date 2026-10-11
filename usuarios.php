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
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol = $_POST['rol'] ?? 'abogado';
        $especialidad = trim($_POST['especialidad'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (!empty($nombre) && !empty($correo) && !empty($password)) {
            $hashPass = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nombre, correo, password, rol, especialidad, telefono, estado)
                    VALUES (:nombre, :correo, :password, :rol, :especialidad, :telefono, 'activo')
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':correo' => $correo,
                    ':password' => $hashPass,
                    ':rol' => $rol,
                    ':especialidad' => $especialidad,
                    ':telefono' => $telefono
                ]);
                $mensajeExito = "Usuario '$nombre' creado exitosamente con rol '$rol'.";
            } catch (PDOException $e) {
                $mensajeError = "Error: El correo electrónico ya pertenece a otro usuario.";
            }
        } else {
            $mensajeError = "Por favor complete Nombre, Correo y Contraseña.";
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['usuario_id']) {
            $mensajeError = "No puedes eliminar tu propia cuenta en sesión activa.";
        } elseif ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "Usuario eliminado.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar: el abogado tiene contratos suscritos a su cargo.";
            }
        }
    }
}

$usuarios = $pdo->query("SELECT id, nombre, correo, rol, especialidad, telefono, estado, creado_en FROM usuarios ORDER BY id ASC")->fetchAll();

$pageTitle = 'Gestión de Usuarios y Roles';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="brand-font" style="font-size: 1.8rem; color: #fff;">🛡️ Control de Usuarios y Roles</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Área de seguridad: alta de letrados, definición de privilegios y control de acceso.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoUsuario')" id="btnNuevoUsuario">
        ➕ Agregar Usuario / Abogado
    </button>
</div>

<?php if (!empty($mensajeExito)): ?>
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); color: #6ee7b7; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem;">
        ✅ <?php echo e($mensajeExito); ?>
    </div>
<?php endif; ?>

<?php if (!empty($mensajeError)): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem;">
        ⚠️ <?php echo e($mensajeError); ?>
    </div>
<?php endif; ?>

<div class="data-card">
    <div class="card-header-flex">
        <h3 class="card-title">Equipo Jurídico y Cuentas de Acceso</h3>
        <div class="search-filter-box">
            <input type="text" id="tableSearch" class="input-control" placeholder="🔍 Filtrar usuarios..." style="width: 250px;">
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Correo Electrónico</th>
                    <th>Rol en el Sistema</th>
                    <th>Especialidad Jurídica</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Fecha de Registro</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><strong style="color: #fff;"><?php echo e($u['nombre']); ?></strong></td>
                    <td><?php echo e($u['correo']); ?></td>
                    <td>
                        <span class="role-tag <?php echo ($u['rol'] === 'administrador') ? 'role-admin' : 'role-abogado'; ?>">
                            <?php echo e(ucfirst($u['rol'])); ?>
                        </span>
                    </td>
                    <td><span style="color: var(--gold-light); font-size: 0.88rem;"><?php echo e($u['especialidad']); ?></span></td>
                    <td><?php echo e($u['telefono'] ?: '-'); ?></td>
                    <td><span class="status-badge status-Activo"><?php echo e($u['estado']); ?></span></td>
                    <td><?php echo formato_fecha($u['creado_en']); ?></td>
                    <td style="text-align: right;">
                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                        <form method="POST" action="usuarios.php" onsubmit="return confirm('¿Eliminar este usuario permanentemente?');" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo e($u['id']); ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Usuario">🗑️</button>
                        </form>
                        <?php else: ?>
                        <span style="font-size: 0.75rem; color: var(--gold-primary);">Sesión Actual</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoUsuario">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="card-title brand-font">🛡️ Registro de Usuario / Abogado</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalNuevoUsuario')">✕</button>
        </div>
        <form method="POST" action="usuarios.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="usrNombre">Nombre Completo *</label>
                        <input type="text" id="usrNombre" name="nombre" class="form-input" placeholder="Ej. Dra. Camila Soto" required>
                    </div>
                    <div>
                        <label class="form-label" for="usrCorreo">Correo Corporativo *</label>
                        <input type="email" id="usrCorreo" name="correo" class="form-input" placeholder="camila@abogados.com" required>
                    </div>
                    <div>
                        <label class="form-label" for="usrPass">Contraseña Inicial *</label>
                        <input type="password" id="usrPass" name="password" class="form-input" placeholder="••••••••••••" required>
                    </div>
                    <div>
                        <label class="form-label" for="usrRol">Rol Asignado *</label>
                        <select id="usrRol" name="rol" class="form-input" required>
                            <option value="abogado" selected>Abogado (Operativo)</option>
                            <option value="administrador">Administrador (Total)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="usrEsp">Especialidad Jurídica</label>
                        <input type="text" id="usrEsp" name="especialidad" class="form-input" placeholder="Derecho Tributario, Laboral...">
                    </div>
                    <div>
                        <label class="form-label" for="usrTel">Teléfono</label>
                        <input type="text" id="usrTel" name="telefono" class="form-input" placeholder="+56 9 8888 7777">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoUsuario')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Cuenta</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
