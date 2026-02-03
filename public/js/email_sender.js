
document.addEventListener('DOMContentLoaded', function () {
    const startBatchBtn = document.getElementById('startBatchEmailBtn');
    if (!startBatchBtn) return;

    const modal = document.getElementById('emailModal');
    const closeBtn = document.getElementById('closeModalBtn');
    const sendBtn = document.getElementById('sendBatchBtn');
    const templateSelect = document.getElementById('templateSelect');
    const subjectInput = document.getElementById('emailSubject');
    const bodyInput = document.getElementById('emailBody');
    const selectedCountSpan = document.getElementById('selectedCount');
    const saveTemplateBtn = document.getElementById('saveTemplateBtn');
    const newTemplateName = document.getElementById('newTemplateName');

    let processing = false;

    // Open Modal
    startBatchBtn.addEventListener('click', () => {
        const checkboxes = document.querySelectorAll('.company-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Por favor, selecciona al menos una empresa.');
            return;
        }
        selectedCountSpan.textContent = checkboxes.length;
        modal.classList.remove('hidden');
        loadTemplates(); // Fetch templates when opening
    });

    // Close Modal
    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Load Templates
    async function loadTemplates() {
        try {
            const apiBase = window.PRACTICALIA_API || '/api';
            const res = await fetch(`${apiBase}/templates.php`);
            if (!res.ok) throw new Error('Error al cargar plantillas');
            const templates = await res.json();
            templateSelect.innerHTML = '<option value="">-- Seleccionar Plantilla --</option>';
            templates.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.titulo;
                opt.dataset.subject = t.asunto;
                opt.dataset.body = t.cuerpo;
                templateSelect.appendChild(opt);
            });
        } catch (e) {
            console.error(e);
            // Don't alert here to avoid spamming if just empty
        }
    }

    // Select Template
    templateSelect.addEventListener('change', () => {
        const opt = templateSelect.selectedOptions[0];
        if (opt && opt.value) {
            subjectInput.value = opt.dataset.subject;
            bodyInput.value = opt.dataset.body;
        }
    });

    // Save New Template
    saveTemplateBtn.addEventListener('click', async () => {
        const name = newTemplateName.value.trim();
        const subject = subjectInput.value.trim();
        const body = bodyInput.value.trim();

        if (!name || !subject || !body) {
            alert('Para guardar una plantilla necesitas nombre, asunto y cuerpo.');
            return;
        }

        try {
            const apiBase = window.PRACTICALIA_API || '/api';
            const res = await fetch(`${apiBase}/templates.php?t=${Date.now()}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ titulo: name, asunto: subject, cuerpo: body })
            });

            if (!res.ok) {
                const text = await res.text();
                console.error("Server Response:", text);
                throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
            }

            const data = await res.json();
            if (data.id) {
                alert('Plantilla guardada');
                newTemplateName.value = '';
                loadTemplates(); // Reload to show the new one
            } else {
                alert('Error al guardar: ' + (data.error || 'Desconocido'));
            }
        } catch (e) {
            console.error(e);
            alert('Error al guardar plantilla: ' + e.message);
        }
    });

    // Send Emails
    sendBtn.addEventListener('click', async () => {
        if (processing) return;

        const checkboxes = document.querySelectorAll('.company-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => cb.value);
        const subject = subjectInput.value.trim();
        const body = bodyInput.value.trim();

        if (!subject || !body) {
            alert('Asunto y cuerpo son obligatorios');
            return;
        }

        if (!confirm(`¿Estás seguro de enviar ${ids.length} correos?`)) return;

        processing = true;
        sendBtn.textContent = 'Enviando...';
        sendBtn.disabled = true;

        try {
            const apiBase = window.PRACTICALIA_API || '/api';
            // Cache busting and AJAX header
            const res = await fetch(`${apiBase}/send_batch_emails.php?t=${Date.now()}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    company_ids: ids,
                    subject: subject,
                    body: body
                })
            });

            if (!res.ok) {
                const text = await res.text();
                console.error("Server Response:", text);
                throw new Error(`HTTP ${res.status}: ${text.substring(0, 100)}`);
            }

            const result = await res.json();
            let msg = `Enviados: ${result.success}, Fallidos: ${result.failed}`;
            if (result.errors && result.errors.length > 0) {
                msg += '\n\nErrores encontrados:\n' + result.errors.slice(0, 5).join('\n');
                if (result.errors.length > 5) msg += '\n...';
            }
            alert(msg);

            modal.classList.add('hidden');
            checkboxes.forEach(cb => cb.checked = false);
        } catch (e) {
            console.error(e);
            alert('Error al enviar correos: ' + e.message);
        } finally {
            processing = false;
            sendBtn.textContent = 'Enviar Correos';
            sendBtn.disabled = false;
        }
    });

    // Select All Logic
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', (e) => {
            const checks = document.querySelectorAll('.company-checkbox');
            checks.forEach(c => c.checked = e.target.checked);
        });
    }
});
