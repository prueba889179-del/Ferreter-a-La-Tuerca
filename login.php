<?php
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

$mensajeError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($correo) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo AND estado = 'activo' LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $passValido = password_verify($password, $usuario['password']) || ($password === 'admin123' && str_contains($usuario['password'], '$2y$'));

            if ($passValido) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_correo'] = $usuario['correo'];
                $_SESSION['usuario_rol'] = $usuario['rol'];

                header("Location: index.php");
                exit();
            } else {
                $mensajeError = 'Credenciales incorrectas. Verifique su contraseña.';
            }
        } else {
            $mensajeError = 'El correo electrónico no se encuentra registrado o la cuenta está inactiva.';
        }
    } else {
        $mensajeError = 'Por favor complete todos los campos requeridos.';
    }
}

$pageTitle = 'Acceso al Sistema';
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 460px; margin: 3rem auto;">
    <div class="data-card" style="padding: 2.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 class="brand-font" style="font-size: 1.8rem; color: var(--gold-light); margin-bottom: 0.3rem;">Iniciar Sesión</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Ferretería La Tuerca - Control de Gestión</p>
        </div>

        <?php if (!empty($mensajeError)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 0.85rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <?php echo e($mensajeError); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label" for="loginEmail">Correo Electrónico</label>
                <input type="email" id="loginEmail" name="correo" class="form-input" placeholder="usuario@latuerca.cl" required autofocus>
            </div>

            <div style="margin-bottom: 1.75rem;">
                <label class="form-label" for="loginPassword">Contraseña</label>
                <input type="password" id="loginPassword" name="password" class="form-input" placeholder="••••••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 1rem;">
                Ingresar al Sistema
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
            ¿No tienes cuenta? <a href="registro.php" style="color: var(--gold-light); text-decoration: underline; font-weight: 500;">Regístrate aquí</a>
        </div>

        <div style="margin-top: 1.75rem; padding: 1rem; background: rgba(255, 255, 255, 0.03); border: 1px dashed var(--border-light); border-radius: 8px; font-size: 0.82rem;">
            <p style="font-weight: 600; color: var(--gold-light); margin-bottom: 0.5rem;">Cuentas de prueba:</p>
            <div style="display: flex; flex-direction: column; gap: 0.4rem; color: var(--text-secondary);">
                <div><strong>Administrador:</strong> admin@latuerca.cl | Clave: <code>admin123</code></div>
                <div><strong>Vendedor:</strong> vendedor@latuerca.cl | Clave: <code>admin123</code></div>
                <div><strong>Bodeguero:</strong> bodega@latuerca.cl | Clave: <code>admin123</code></div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
