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
        $expediente = trim($_POST['numero_expediente'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = $_POST['tipo_contrato'] ?? 'Mercantil';
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $abogadoId = (int)($_POST['abogado_id'] ?? $_SESSION['usuario_id']);
        $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $fechaVencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
        $monto = (float)($_POST['monto_acordado'] ?? 0);
        $estado = $_POST['estado'] ?? 'Borrador';
        $clausulas = trim($_POST['clausulas_resumen'] ?? '');

        if (!empty($expediente) && !empty($titulo) && $clienteId > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contratos (numero_expediente, titulo, tipo_contrato, cliente_id, abogado_id, fecha_inicio, fecha_vencimiento, monto_acordado, estado, clausulas_resumen)
                    VALUES (:expediente, :titulo, :tipo, :cliente_id, :abogado_id, :fecha_inicio, :fecha_vencimiento, :monto, :estado, :clausulas)
                ");
                $stmt->execute([
                    ':expediente' => $expediente,
                    ':titulo' => $titulo,
                    ':tipo' => $tipo,
                    ':cliente_id' => $clienteId,
                    ':abogado_id' => $abogadoId,
                    ':fecha_inicio' => $fechaInicio,
                    ':fecha_vencimiento' => $fechaVencimiento,
                    ':monto' => $monto,
                    ':estado' => $estado,
                    ':clausulas' => $clausulas
                ]);
                $mensajeExito = "Contrato '$expediente' registrado exitosamente en la base de datos.";
            } catch (PDOException $e) {
                $mensajeError = "Error al guardar el contrato: " . ($e->getCode() == 23000 ? 'El número de expediente ya existe.' : $e->getMessage());
            }
        } else {
            $mensajeError = "Por favor complete los campos obligatorios (Expediente, Título y Cliente).";
        }
    }

    if ($accion === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $tipo = $_POST['tipo_contrato'] ?? 'Mercantil';
        $monto = (float)($_POST['monto_acordado'] ?? 0);
        $estado = $_POST['estado'] ?? 'Borrador';
        $fechaVencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
        $clausulas = trim($_POST['clausulas_resumen'] ?? '');

        if ($id > 0 && !empty($titulo)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE contratos 
                    SET titulo = :titulo, tipo_contrato = :tipo, monto_acordado = :monto, estado = :estado, fecha_vencimiento = :fecha_vencimiento, clausulas_resumen = :clausulas
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':tipo' => $tipo,
                    ':monto' => $monto,
                    ':estado' => $estado,
                    ':fecha_vencimiento' => $fechaVencimiento,
                    ':clausulas' => $clausulas,
                    ':id' => $id
                ]);
                $mensajeExito = "El contrato fue actualizado correctamente.";
            } catch (PDOException $e) {
                $mensajeError = "Error al actualizar contrato: " . $e->getMessage();
            }
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM contratos WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $mensajeExito = "El contrato ha sido eliminado de los registros.";
            } catch (PDOException $e) {
                $mensajeError = "No se puede eliminar el contrato: posee facturas vinculadas.";
            }
        }
    }
}

$stmtContratos = $pdo->query("
    SELECT c.*, cl.nombre AS cliente_nombre, cl.empresa AS cliente_empresa, u.nombre AS abogado_nombre 
    FROM contratos c
    JOIN clientes cl ON c.cliente_id = cl.id
    JOIN usuarios u ON c.abogado_id = u.id
    ORDER BY c.id DESC
");
$contratos = $stmtContratos->fetchAll();

$clientes = $pdo->query("SELECT id, nombre, empresa FROM clientes ORDER BY nombre ASC")->fetchAll();
$abogados = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol IN ('administrador', 'abogado') AND estado = 'activo' ORDER BY nombre ASC")->fetchAll();

$pageTitle = 'Módulo de Contratos';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="brand-font" style="font-size: 1.8rem; color: #fff;">📜 Módulo de Contratos Legales</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Registro, seguimiento de vigencia, cláusulas y fiscalización de expedientes.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openModal('modalNuevoContrato')" id="btnNuevoContrato">
        ➕ Redactar Nuevo Contrato
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
        <h3 class="card-title">Listado General de Expedientes Contractuales</h3>
        <div class="search-filter-box">
            <input type="text" id="tableSearch" class="input-control" placeholder="🔍 Buscar por expediente, título o cliente..." style="width: 280px;">
            <select id="tableFilterSelect" class="select-control">
                <option value="">Todos los Estados</option>
                <option value="Borrador">Borrador</option>
                <option value="En Revision">En Revisión</option>
                <option value="Activo">Activo</option>
                <option value="Finalizado">Finalizado</option>
                <option value="Rescindido">Rescindido</option>
            </select>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Expediente</th>
                    <th>Título y Objeto</th>
                    <th>Tipo</th>
                    <th>Cliente / Empresa</th>
                    <th>Abogado Responsable</th>
                    <th>Monto Honorarios</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contratos)): ?>
                    <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No existen contratos en la base de datos. Haz clic en "Redactar Nuevo Contrato".</td></tr>
                <?php else: ?>
                    <?php foreach ($contratos as $c): ?>
                    <tr data-status="<?php echo e($c['estado']); ?>">
                        <td><strong style="color: var(--gold-light);"><?php echo e($c['numero_expediente']); ?></strong></td>
                        <td>
                            <div style="font-weight: 600; color: #fff;"><?php echo e($c['titulo']); ?></div>
                            <small style="color: var(--text-muted);"><?php echo e(mb_strimwidth($c['clausulas_resumen'] ?? '', 0, 45, '...')); ?></small>
                        </td>
                        <td><span style="font-size: 0.85rem; color: #94a3b8;"><?php echo e($c['tipo_contrato']); ?></span></td>
                        <td>
                            <div><?php echo e($c['cliente_nombre']); ?></div>
                            <small style="color: var(--text-muted);"><?php echo e($c['cliente_empresa'] ?: 'Particular'); ?></small>
                        </td>
                        <td><?php echo e($c['abogado_nombre']); ?></td>
                        <td><strong><?php echo formato_dinero($c['monto_acordado']); ?></strong></td>
                        <td>
                            <div style="font-size: 0.82rem;">Inic: <?php echo formato_fecha($c['fecha_inicio']); ?></div>
                            <div style="font-size: 0.82rem; color: var(--text-muted);">Venc: <?php echo formato_fecha($c['fecha_vencimiento']); ?></div>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '_', $c['estado']); ?>">
                                <?php echo e($c['estado']); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.4rem;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='abrirModalEditar(<?php echo json_encode($c); ?>)' title="Editar Contrato">
                                    ✏️
                                </button>
                                <form method="POST" action="contratos.php" onsubmit="return confirm('¿Está seguro de eliminar este contrato de forma permanente?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo e($c['id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Contrato">🗑️</button>
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

<div class="modal-backdrop" id="modalNuevoContrato">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="card-title brand-font">📜 Redacción de Contrato</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalNuevoContrato')">✕</button>
        </div>
        <form method="POST" action="contratos.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="expedienteInput">N° Expediente *</label>
                        <input type="text" id="expedienteInput" name="numero_expediente" class="form-input" placeholder="EXP-2026-<?php echo rand(100, 999); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="tipoContratoSelect">Tipo de Contrato *</label>
                        <select id="tipoContratoSelect" name="tipo_contrato" class="form-input" required>
                            <option value="Mercantil">Mercantil</option>
                            <option value="Laboral">Laboral</option>
                            <option value="Inmobiliario">Inmobiliario</option>
                            <option value="Propiedad Intelectual">Propiedad Intelectual</option>
                            <option value="Litigio Civil">Litigio Civil</option>
                            <option value="Servicios Legales">Servicios Legales</option>
                        </select>
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="tituloInput">Título del Contrato *</label>
                        <input type="text" id="tituloInput" name="titulo" class="form-input" placeholder="Ej. Prestación de Servicios de Blindaje Corporativo" required>
                    </div>
                    <div>
                        <label class="form-label" for="clienteSelect">Cliente Contratante *</label>
                        <select id="clienteSelect" name="cliente_id" class="form-input" required>
                            <option value="">Seleccione Cliente...</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo $cli['id']; ?>"><?php echo e($cli['nombre']) . ($cli['empresa'] ? ' (' . e($cli['empresa']) . ')' : ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="abogadoSelect">Abogado a Cargo *</label>
                        <select id="abogadoSelect" name="abogado_id" class="form-input" required>
                            <?php foreach ($abogados as $abg): ?>
                                <option value="<?php echo $abg['id']; ?>" <?php echo ($abg['id'] == $_SESSION['usuario_id']) ? 'selected' : ''; ?>>
                                    <?php echo e($abg['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="fechaInicioInput">Fecha de Inicio *</label>
                        <input type="date" id="fechaInicioInput" name="fecha_inicio" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="fechaVencInput">Fecha Vencimiento</label>
                        <input type="date" id="fechaVencInput" name="fecha_vencimiento" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" for="montoInput">Monto Acordado (USD/CLP) *</label>
                        <input type="number" step="0.01" id="montoInput" name="monto_acordado" class="form-input" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="form-label" for="estadoSelect">Estado Inicial</label>
                        <select id="estadoSelect" name="estado" class="form-input">
                            <option value="Borrador">Borrador</option>
                            <option value="En Revision">En Revisión</option>
                            <option value="Activo" selected>Activo</option>
                        </select>
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="clausulasInput">Resumen de Cláusulas y Términos</label>
                        <textarea id="clausulasInput" name="clausulas_resumen" class="form-input" rows="3" placeholder="Resumen de obligaciones, jurisdicción arbitral, confidencialidad..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevoContrato')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💾 Guardar Contrato</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modalEditarContrato">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="card-title brand-font">✏️ Editar Contrato: <span id="editExpedienteLabel" style="color:var(--gold-light);"></span></h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalEditarContrato')">✕</button>
        </div>
        <form method="POST" action="contratos.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="editContratoId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-col-full">
                        <label class="form-label" for="editTitulo">Título del Contrato *</label>
                        <input type="text" id="editTitulo" name="titulo" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="editTipo">Tipo de Contrato *</label>
                        <select id="editTipo" name="tipo_contrato" class="form-input" required>
                            <option value="Mercantil">Mercantil</option>
                            <option value="Laboral">Laboral</option>
                            <option value="Inmobiliario">Inmobiliario</option>
                            <option value="Propiedad Intelectual">Propiedad Intelectual</option>
                            <option value="Litigio Civil">Litigio Civil</option>
                            <option value="Servicios Legales">Servicios Legales</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="editEstado">Estado Contractual *</label>
                        <select id="editEstado" name="estado" class="form-input" required>
                            <option value="Borrador">Borrador</option>
                            <option value="En Revision">En Revisión</option>
                            <option value="Activo">Activo</option>
                            <option value="Finalizado">Finalizado</option>
                            <option value="Rescindido">Rescindido</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="editMonto">Monto Honorarios *</label>
                        <input type="number" step="0.01" id="editMonto" name="monto_acordado" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label" for="editFechaVenc">Fecha de Vencimiento</label>
                        <input type="date" id="editFechaVenc" name="fecha_vencimiento" class="form-input">
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="editClausulas">Cláusulas y Notas</label>
                        <textarea id="editClausulas" name="clausulas_resumen" class="form-input" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditarContrato')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalEditar(contrato) {
    document.getElementById('editContratoId').value = contrato.id;
    document.getElementById('editExpedienteLabel').textContent = contrato.numero_expediente;
    document.getElementById('editTitulo').value = contrato.titulo;
    document.getElementById('editTipo').value = contrato.tipo_contrato;
    document.getElementById('editEstado').value = contrato.estado;
    document.getElementById('editMonto').value = contrato.monto_acordado;
    document.getElementById('editFechaVenc').value = contrato.fecha_vencimiento || '';
    document.getElementById('editClausulas').value = contrato.clausulas_resumen || '';
    openModal('modalEditarContrato');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
