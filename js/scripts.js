document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    const filterSelect = document.getElementById('tableFilterSelect');
    if (filterSelect) {
        filterSelect.addEventListener('change', (e) => {
            const val = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            rows.forEach(row => {
                if (!val) {
                    row.style.display = '';
                } else {
                    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
                    row.style.display = rowStatus === val ? '' : 'none';
                }
            });
        });
    }

    const audioToggleBtn = document.getElementById('toggleAmbientAudio');
    const ambientAudio = document.getElementById('ambientAudio');
    if (audioToggleBtn && ambientAudio) {
        ambientAudio.volume = 0.25;
        audioToggleBtn.addEventListener('click', () => {
            if (ambientAudio.paused) {
                ambientAudio.play().then(() => {
                    audioToggleBtn.innerHTML = '🔊 Música Ambiente: ON';
                    audioToggleBtn.classList.add('btn-primary');
                    audioToggleBtn.classList.remove('btn-secondary');
                }).catch(err => {
                    console.log('Audio autoplay prevented:', err);
                });
            } else {
                ambientAudio.pause();
                audioToggleBtn.innerHTML = '🔈 Música Ambiente: OFF';
                audioToggleBtn.classList.remove('btn-primary');
                audioToggleBtn.classList.add('btn-secondary');
            }
        });
    }
});

function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('show');
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('show');
}

window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('show');
    }
});

function calculateTaxTotal() {
    const subtotalInput = document.getElementById('factura_subtotal');
    const impuestosInput = document.getElementById('factura_impuestos');
    const totalInput = document.getElementById('factura_total');

    if (subtotalInput && impuestosInput && totalInput) {
        const subtotal = parseFloat(subtotalInput.value) || 0;
        const taxRate = 0.19;
        const tax = Math.round(subtotal * taxRate * 100) / 100;
        const total = Math.round((subtotal + tax) * 100) / 100;

        impuestosInput.value = tax.toFixed(2);
        totalInput.value = total.toFixed(2);
    }
}
