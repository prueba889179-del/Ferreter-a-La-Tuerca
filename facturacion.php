<?php
/**
 * Módulo de Facturación y Cobranza Legal (CRUD Completo)
 * Funcionalidades:
 * - Emisión de facturas vinculadas a contratos y clientes
 * - Cálculo automático de impuestos (IVA 19%) y total
 * - Control de estados de pago (Pendiente, Pagado, Anulada)
 * - Filtrado y búsqueda interactiva
 */
require_once __DIR__ . '/db/conexion.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$mensajeExito = '';
$mensajeError = '';

// Procesar Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $accion = $_POST['accion'] ?? '';

    // CREAR FACTURA
    if ($accion === 'crear') {
        $folio = trim($_POST['folio_factura'] ?? '');
        $contratoId = (int)($_POST['contrato_id'] ?? 0);
        $fechaEmision = $_POST['fecha_emision'] ?? date('Y-m-d');
        $fechaVencimiento = $_POST['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+15 days'));
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $impuestos = (float)($_POST['impuestos'] ?? 0);
        $total = (float)($_POST['total'] ?? 0);
        $metodoPago = $_POST['metodo_pago'] ?? 'Transferencia Bancaria';
        $estadoPago = $_POST['estado_pago'] ?? 'Pendiente';
        $notas = trim($_POST['notas'] ?? '');

        // Obtener el cliente_id asociado al contrato
        $stmtContrato = $pdo->prepare("SELECT cliente_id FROM contratos WHERE id = :id");
        $stmtContrato->execute([':id' => $contratoId]);
        $clienteId = $stmtContrato->fetchColumn();

        if (!empty($folio) && $contratoId > 0 && $clienteId && $total > 0) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO facturas (folio_factura, contrato_id, cliente_id, fecha_emision, fecha_vencimiento, subtotal, impuestos, total, metodo_pago, estado_pago, notas)
                    VALUES (:folio, :contrato_id, :cliente_id, :fecha_emision, :fecha_vencimiento, :subtotal, :impuestos, :total, :metodo_pago, :estado_pago, :notas)
                ");
                $stmt->execute([
                    ':folio' => $folio,
                    ':contrato_id' => $contratoId,
                    ':cliente_id' => $clienteId,
                    ':fecha_emision' => $fechaEmision,
                    ':fecha_vencimiento' => $fechaVencimiento,
                    ':subtotal' => $subtotal,
                    ':impuestos' => $impuestos,
                    ':total' => $total,
                    ':metodo_pago' => $metodoPago,
                    ':estado_pago' => $estadoPago,
                    ':notas' => $notas
                ]);
                $mensajeExito = "Factura '$folio' emitida con éxito.";
            } catch (PDOException $e) {
                $mensajeError = "Error al emitir factura: " . ($e->getCode() == 23000 ? 'El folio ya fue emitido.' : $e->getMessage());
            }
        } else {
            $mensajeError = "Por favor verifique los datos obligatorios y montos.";
        }
    }

    // ACTUALIZAR ESTADO DE PAGO
    if ($accion === 'actualizar_estado') {
        $id = (int)($_POST['id'] ?? 0);
        $nuevoEstado = $_POST['estado_pago'] ?? 'Pendiente';

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE facturas SET estado_pago = :estado WHERE id = :id");
            $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
            $mensajeExito = "Estado de factura actualizado a '$nuevoEstado'.";
        }
    }

    // ELIMINAR FACTURA
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM facturas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensajeExito = "Factura eliminada del registro contable.";
        }
    }
}

// Obtener facturas con contratos y clientes
$stmtFacturas = $pdo->query("
    SELECT f.*, c.numero_expediente, c.titulo AS contrato_titulo, cl.nombre AS cliente_nombre, cl.rut_identificacion
    FROM facturas f
    JOIN contratos c ON f.contrato_id = c.id
    JOIN clientes cl ON f.cliente_id = cl.id
    ORDER BY f.id DESC
");
$facturas = $stmtFacturas->fetchAll();

// Listado de contratos disponibles para vincular factura
$contratosLista = $pdo->query("
    SELECT c.id, c.numero_expediente, c.titulo, c.monto_acordado, cl.nombre AS cliente_nombre 
    FROM contratos c 
    JOIN clientes cl ON c.cliente_id = cl.id 
    ORDER BY c.numero_expediente DESC
")->fetchAll();

$pageTitle = 'Módulo de Facturación';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="brand-font" style="font-size: 1.8rem; color: #fff;">💳 Módulo de Facturación &amp; Honorarios</h1>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Emisión de recibos de honorarios, control tributario y conciliación de pagos.</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openModal('modalNuevaFactura')" id="btnNuevaFactura">
        ➕ Emitir Nueva Factura
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

<!-- TABLA DE FACTURAS -->
<div class="data-card">
    <div class="card-header-flex">
        <h3 class="card-title">Registro Contable de Facturas</h3>
        <div class="search-filter-box">
            <input type="text" id="tableSearch" class="input-control" placeholder="🔍 Buscar por folio, cliente o expediente..." style="width: 280px;">
            <select id="tableFilterSelect" class="select-control">
                <option value="">Todos los Estados</option>
                <option value="Pagado">Pagado</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Anulada">Anulada</option>
            </select>
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Contrato Vinculado</th>
                    <th>Cliente / R.U.T.</th>
                    <th>Emisión / Vencimiento</th>
                    <th>Subtotal</th>
                    <th>IVA (19%)</th>
                    <th>Total</th>
                    <th>Método</th>
                    <th>Estado</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facturas)): ?>
                    <tr><td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay facturas emitidas. Haga clic en "Emitir Nueva Factura".</td></tr>
                <?php else: ?>
                    <?php foreach ($facturas as $f): ?>
                    <tr data-status="<?php echo e($f['estado_pago']); ?>">
                        <td><strong style="color: var(--gold-light);"><?php echo e($f['folio_factura']); ?></strong></td>
                        <td>
                            <div><strong style="color: #fff;"><?php echo e($f['numero_expediente']); ?></strong></div>
                            <small style="color: var(--text-muted);"><?php echo e($f['contrato_titulo']); ?></small>
                        </td>
                        <td>
                            <div><?php echo e($f['cliente_nombre']); ?></div>
                            <small style="color: var(--text-muted);"><?php echo e($f['rut_identificacion']); ?></small>
                        </td>
                        <td>
                            <div><?php echo formato_fecha($f['fecha_emision']); ?></div>
                            <small style="color: var(--text-muted);">Vence: <?php echo formato_fecha($f['fecha_vencimiento']); ?></small>
                        </td>
                        <td><?php echo formato_dinero($f['subtotal']); ?></td>
                        <td><?php echo formato_dinero($f['impuestos']); ?></td>
                        <td><strong style="color: #34d399; font-size: 1.05rem;"><?php echo formato_dinero($f['total']); ?></strong></td>
                        <td><span style="font-size: 0.82rem; color: var(--text-secondary);"><?php echo e($f['metodo_pago']); ?></span></td>
                        <td>
                            <span class="status-badge status-<?php echo e($f['estado_pago']); ?>">
                                <?php echo e($f['estado_pago']); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 0.4rem;">
                                <!-- Formulario rápido para alternar estado de pago -->
                                <form method="POST" action="facturacion.php" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="accion" value="actualizar_estado">
                                    <input type="hidden" name="id" value="<?php echo e($f['id']); ?>">
                                    <?php if ($f['estado_pago'] === 'Pendiente'): ?>
                                        <input type="hidden" name="estado_pago" value="Pagado">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #34d399; border-color: rgba(16,185,129,0.3);" title="Marcar como Pagado">✓ Pagado</button>
                                    <?php else: ?>
                                        <input type="hidden" name="estado_pago" value="Pendiente">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Marcar como Pendiente">⏳</button>
                                    <?php endif; ?>
                                </form>

                                <form method="POST" action="facturacion.php" onsubmit="return confirm('¿Desea eliminar la factura?');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo e($f['id']); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Eliminar Factura">🗑️</button>
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

<!-- MODAL: REGISTRAR NUEVA FACTURA -->
<div class="modal-backdrop" id="modalNuevaFactura">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="card-title brand-font">💳 Emisión de Factura de Honorarios</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalNuevaFactura')">✕</button>
        </div>
        <form method="POST" action="facturacion.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="folioInput">Folio de Factura *</label>
                        <input type="text" id="folioInput" name="folio_factura" class="form-input" value="FACT-2026-<?php echo rand(200, 999); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="contratoSelect">Contrato Asociado *</label>
                        <select id="contratoSelect" name="contrato_id" class="form-input" required onchange="setMontoFromContrato(this)">
                            <option value="">Seleccione Contrato...</option>
                            <?php foreach ($contratosLista as $cItem): ?>
                                <option value="<?php echo $cItem['id']; ?>" data-monto="<?php echo $cItem['monto_acordado']; ?>">
                                    <?php echo e($cItem['numero_expediente']) . ' - ' . e($cItem['cliente_nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="fechaEmision">Fecha Emisión *</label>
                        <input type="date" id="fechaEmision" name="fecha_emision" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="fechaVenc">Fecha Vencimiento *</label>
                        <input type="date" id="fechaVenc" name="fecha_vencimiento" class="form-input" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>" required>
                    </div>
                    <div>
                        <label class="form-label" for="factura_subtotal">Subtotal Neto *</label>
                        <input type="number" step="0.01" id="factura_subtotal" name="subtotal" class="form-input" placeholder="0.00" required oninput="calculateTaxTotal()">
                    </div>
                    <div>
                        <label class="form-label" for="factura_impuestos">IVA Estimado (19%)</label>
                        <input type="number" step="0.01" id="factura_impuestos" name="impuestos" class="form-input" placeholder="0.00" readonly>
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="factura_total">Total a Pagar *</label>
                        <input type="number" step="0.01" id="factura_total" name="total" class="form-input" placeholder="0.00" style="font-weight: bold; font-size: 1.1rem; color: #34d399;" readonly>
                    </div>
                    <div>
                        <label class="form-label" for="metodoSelect">Método de Pago</label>
                        <select id="metodoSelect" name="metodo_pago" class="form-input">
                            <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                            <option value="Tarjeta de Credito">Tarjeta de Crédito</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Efectivo">Efectivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="estadoPagoSelect">Estado Inicial</label>
                        <select id="estadoPagoSelect" name="estado_pago" class="form-input">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Pagado">Pagado</option>
                        </select>
                    </div>
                    <div class="form-col-full">
                        <label class="form-label" for="notasFactura">Detalle de Concepto / Notas</label>
                        <textarea id="notasFactura" name="notas" class="form-input" rows="2" placeholder="Ej. Honorarios por concepto de revisión estatutaria..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNuevaFactura')">Cancelar</button>
                <button type="submit" class="btn btn-primary">💳 Emitir Factura</button>
            </div>
        </form>
    </div>
</div>

<script>
function setMontoFromContrato(select) {
    const selectedOption = select.options[select.selectedIndex];
    const monto = selectedOption.getAttribute('data-monto');
    if (monto) {
        document.getElementById('factura_subtotal').value = parseFloat(monto).toFixed(2);
        calculateTaxTotal();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
