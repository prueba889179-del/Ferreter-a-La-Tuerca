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
        $rut = trim($_POST['rut_identificacion'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $empresa = trim($_POST['empresa'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if (!empty($rut) && !empty($nombre) && !empty($correo)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO clientes (rut_identificacion, nombre, empresa, correo, telefono, direccion)
                    VALUES (:rut, :nombre, :empresa, :correo, :telefono, :direccion)
                ");
                $stmt->execute([
                    ':rut' => $rut,
                    ':nombre' => $nombre,
                    ':empresa' => $empresa,
                    ':correo' => $correo,
                    ':telefono' => $telefono,
                    ':direccion' => $direccion
                ]);
                $mensajeExito = "Cliente '$nombre' agregado exitosamente.";
            } catch (PDOException $e) {
                $mensajeError = "Error: El R.U.T. o Identificación ya se encuentra registrado.";
            }
        } else {
            $mensajeError = "R.U.T., Nombre y Correo son obligatorios.";
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "Cliente eliminado correctamente.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar el cliente: tiene contratos o facturas asociadas.";
            }
        }
    }
}

$stmtClientes = $pdo->query("
    SELECT cl.*, COUNT(c.id) AS total_contratos
    FROM clientes cl
    LEFT JOIN contratos c ON cl.id = c.cliente_id
    GROUP BY cl.id
    ORDER BY cl.id DESC
");
$clientes = $stmtClientes->fetchAll();

$pageTitle = 'Directorio de Clientes';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: gap: 1rem;">
    <div>
        <h1 class="brand-font" style="font-size: 1.8rem; color: #fff;">👥 Directorio de Clientes y Empresas</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Registro comercial, datos de contacto y fiscalización de representación legal.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoCliente')" id="btnNuevoCliente">
        ➕ Registrar Nuevo Cliente
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
        <h3 class="card-title">Listado de Clientes y Representadas</h3>
        <div class="search-filter-box">
            <input type="text" id="tableSearch" class="input-control" placeholder="🔍 Buscar cliente, RUT o empresa..." style="width: 280px;">
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>R.U.T. / ID</th>
                    <th>Nombre / Representante</th>
                    <th>Razón Social / Empresa</th>
                    <th>Contacto Electrónico</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Contratos</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientes)): ?>
                    <tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No existen clientes registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientes as $cli): ?>
                    <tr>
                        <td><strong style="color: var(--gold-light);"><?php echo e($cli['rut_identificacion']); ?></strong></td>
                        <td><strong style="color: #fff;"><?php echo e($cli['nombre']); ?></strong></td>
                        <td><?php echo e($cli['empresa'] ?: '<span style="color:var(--text-muted);">Particular</span>'); ?></td>
                        <td><a href="mailto:<?php echo e($cli['correo']); ?>" style="color: #60a5fa; text-decoration: none;"><?php echo e($cli['correo']); ?></a></td>
                        <td><?php echo e($cli['telefono'] ?: '-'); ?></td>
                        <td><span style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo e($cli['direccion'] ?: '-'); ?></span></td>
                        <td><span class="status-badge" style="background: rgba(37,99,235,0.2); color: #93c5fd;"><?php echo e($cli['total_contratos']); ?> activos</span></td>
                        <td style="text-align: right;">
                            <form method="POST" action="clientes.php" onsubmit="return confirm('¿Eliminar este cliente?');" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo e($cli['id']); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Cliente">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="modalNuevoCliente">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="card-title brand-font">👥 Alta de Cliente o Representada</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalNuevoCliente')">✕</button>
        </div>
        <form method="POST" action="clientes.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="rutCli">R.U.T. / Identificación *</label>
                        <input type="text" id="rutCli" name="rut_identificacion" class="form-input" placeholder="Ej. 76.543.210-K" required>
                    </div>
                    <div>
                        <label class="form-label" for="nombreCli">Nombre Completo *</label>
                        <input type="text" id="nombreCli" name="nombre" class="form-input" placeholder="Ej. Juan Pérez González" required>
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="empresaCli">Empresa o Razón Social (Opcional)</label>
                        <input type="text" id="empresaCli" name="empresa" class="form-input" placeholder="Ej. Corporación Minera del Norte S.A.">
                    </div>
                    <div>
                        <label class="form-label" for="correoCli">Correo Electrónico *</label>
                        <input type="email" id="correoCli" name="correo" class="form-input" placeholder="contacto@empresa.com" required>
                    </div>
                    <div>
                        <label class="form-label" for="fonoCli">Teléfono</label>
                        <input type="text" id="fonoCli" name="telefono" class="form-input" placeholder="+56 9 1234 5678">
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="dirCli">Dirección Comercial</label>
                        <input type="text" id="dirCli" name="direccion" class="form-input" placeholder="Av. Andrés Bello 2711, Las Condes">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar Cliente</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
