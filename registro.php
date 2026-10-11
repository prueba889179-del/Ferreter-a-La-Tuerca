<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

// Si ya está logueado, redirigir al index
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$mensajeError = '';
$mensajeExito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nombre       = trim($_POST['nombre'] ?? '');
    $correo       = trim($_POST['correo'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $especialidad = trim($_POST['especialidad'] ?? 'Derecho General');
    $telefono     = trim($_POST['telefono'] ?? '');

    if (empty($nombre) || empty($correo) || empty($password)) {
        $mensajeError = 'Por favor complete todos los campos obligatorios.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = 'El formato del correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $mensajeError = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm_pass) {
        $mensajeError = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el correo ya existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmtCheck->execute([':correo' => $correo]);
        if ($stmtCheck->fetch()) {
            $mensajeError = 'Este correo electrónico ya está registrado.';
        } else {
            $hashPass = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO usuarios (nombre, correo, password, rol, especialidad, telefono, estado)
                    VALUES (:nombre, :correo, :password, 'abogado', :especialidad, :telefono, 'activo')
                ");
                $stmtInsert->execute([
                    ':nombre'       => $nombre,
                    ':correo'       => $correo,
                    ':password'     => $hashPass,
                    ':especialidad' => $especialidad ?: 'Derecho General',
                    ':telefono'     => $telefono
                ]);

                $nuevoId = $pdo->lastInsertId();

                // Autologin tras registro exitoso
                $_SESSION['usuario_id'] = $nuevoId;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_correo'] = $correo;
                $_SESSION['usuario_rol'] = 'abogado';
                $_SESSION['usuario_especialidad'] = $especialidad;

                header("Location: index.php");
                exit();
            } catch (PDOException $e) {
                $mensajeError = 'Error al registrar: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$pageTitle = 'Crear Cuenta';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 520px; margin: 2.5rem auto;">
    <div class="data-card" style="padding: 2.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 class="brand-font" style="font-size: 1.8rem; color: var(--gold-light); margin-bottom: 0.3rem;">Crear Cuenta</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Registrarse en Abogados &amp; Socios</p>
        </div>

        <?php if (!empty($mensajeError)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <?php echo e($mensajeError); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="registro.php" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label" for="regNombre">Nombre Completo *</label>
                <input type="text" id="regNombre" name="nombre" class="form-input" placeholder="Ej. Juan Pérez" value="<?php echo e($_POST['nombre'] ?? ''); ?>" required autofocus>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label" for="regCorreo">Correo Electrónico *</label>
                <input type="email" id="regCorreo" name="correo" class="form-input" placeholder="ejemplo@abogados.com" value="<?php echo e($_POST['correo'] ?? ''); ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label class="form-label" for="regEspecialidad">Especialidad</label>
                    <input type="text" id="regEspecialidad" name="especialidad" class="form-input" placeholder="Ej. Derecho Civil" value="<?php echo e($_POST['especialidad'] ?? ''); ?>">
                </div>
                <div>
                    <label class="form-label" for="regTelefono">Teléfono</label>
                    <input type="text" id="regTelefono" name="telefono" class="form-input" placeholder="+56 9 1234 5678" value="<?php echo e($_POST['telefono'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.75rem;">
                <div>
                    <label class="form-label" for="regPassword">Contraseña *</label>
                    <input type="password" id="regPassword" name="password" class="form-input" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div>
                    <label class="form-label" for="regConfirmPassword">Confirmar Clave *</label>
                    <input type="password" id="regConfirmPassword" name="confirm_password" class="form-input" placeholder="Repite la clave" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 1rem;">
                Registrarse
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
            ¿Ya tienes una cuenta? <a href="login.php" style="color: var(--gold-light); text-decoration: underline; font-weight: 500;">Inicia sesión aquí</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
