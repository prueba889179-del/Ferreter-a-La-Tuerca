</main>

<!-- Pie de Página (Footer Requerido: Información del desarrollador, empresa y año) -->
<footer class="app-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <h4 class="brand-font">⚖️ Abogados &amp; Socios</h4>
            <p style="color: var(--text-secondary); font-size: 0.88rem; max-width: 420px;">
                Firma legal líder especializada en contratos mercantiles, derecho societario, propiedad intelectual y litigios de alta complejidad. Innovación jurídica a través de nuestra plataforma LegalTech.
            </p>
            <div style="margin-top: 1rem;">
                <div class="multimedia-bar" style="display: inline-flex;">
                    <audio id="ambientAudio" loop preload="none">
                        <!-- Audio ambiental relajante para lectura de contratos -->
                        <source src="https://assets.mixkit.co/music/preview/mixkit-serene-view-443.mp3" type="audio/mpeg">
                    </audio>
                    <span>🎵 Experiencia Acústica de Oficina Legal:</span>
                    <button type="button" id="toggleAmbientAudio" class="btn btn-secondary btn-sm" style="border-radius: 20px;">
                        🔈 Música Ambiente: OFF
                    </button>
                </div>
            </div>
        </div>

        <div>
            <h5 style="color: var(--gold-light); margin-bottom: 0.75rem; font-size: 0.95rem;">Módulos del Sistema</h5>
            <ul style="list-style: none; font-size: 0.88rem; display: flex; flex-direction: column; gap: 0.4rem;">
                <li><a href="index.php" style="color: var(--text-secondary); text-decoration: none;">🏛️ Panel de Control</a></li>
                <li><a href="contratos.php" style="color: var(--text-secondary); text-decoration: none;">📜 Gestión de Contratos</a></li>
                <li><a href="facturacion.php" style="color: var(--text-secondary); text-decoration: none;">💳 Facturación y Honorarios</a></li>
                <li><a href="clientes.php" style="color: var(--text-secondary); text-decoration: none;">👥 Clientes y Empresas</a></li>
            </ul>
        </div>

        <div>
            <h5 style="color: var(--gold-light); margin-bottom: 0.75rem; font-size: 0.95rem;">Información del Proyecto</h5>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.3rem;"><strong>Alumno Desarrollador:</strong> Benjamin</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.3rem;"><strong>Asignatura:</strong> Desarrollo de Aplicaciones Web</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.3rem;"><strong>Stack:</strong> PHP 8 + MySQL + Railway</p>
            <p style="font-size: 0.85rem; color: var(--text-secondary);"><strong>Herramienta BD:</strong> DBeaver / XAMPP</p>
        </div>
    </div>

    <div class="footer-copy">
        <p>&copy; <?php echo date('Y'); ?> <strong>Abogados &amp; Socios LegalTech</strong>. Todos los derechos reservados. Desarrollado por Benjamin para el Caso Práctico de Desarrollo Web.</p>
    </div>
</footer>

<!-- Scripts JavaScript -->
<script src="js/scripts.js"></script>
</body>
</html>
