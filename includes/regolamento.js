const ns_regolamento = {
    api_file: 'pages/api_regolamento.php',
    param: 'op'
};

// Apre il modale di inserimento (articolo = null) o modifica (articolo = numero)
function apriRegolamentoForm(articolo) {
    const modal = document.getElementById('regolamento_edit_container');
    const form  = document.getElementById('formSaveRegolamento');
    const title = document.getElementById('gp-modal-regolamento-title');

    form.reset();
    document.getElementById('reg-art-originale').value = '';
    document.getElementById('reg-old-titolo').value = '';
    document.getElementById('reg-old-testo').value = '';

    if (!articolo) {
        title.textContent = 'Nuovo articolo';
        modal.style.display = 'flex';
        return;
    }

    fetch(ns_regolamento.api_file + '?' + ns_regolamento.param + '=getArticolo&id=' + encodeURIComponent(articolo))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification(data.message || 'Errore nel caricamento', 'error');
                return;
            }
            const a = data.articolo;
            document.getElementById('reg-titolo').value = a.titolo;
            document.getElementById('reg-testo').value  = a.testo;
            document.getElementById('reg-tipo').value   = a.tipo;

            // Valori "originali" per il calcolo del diff lato server al salvataggio
            document.getElementById('reg-art-originale').value = a.articolo;
            document.getElementById('reg-old-titolo').value    = a.titolo;
            document.getElementById('reg-old-testo').value     = a.testo;

            title.textContent = 'Modifica — ' + a.titolo;
            modal.style.display = 'flex';
        })
        .catch(() => showNotification('Errore nel caricamento', 'error'));
}

// Salva (inserimento o modifica) il form del modale
function saveRegolamentoForm(event) {
    event.preventDefault();

    const form = event.target;
    const fd = new FormData(form);
    const submitBtn = document.getElementById('gp-save-regolamento-btn');

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="save-loader"></span> Salvando...';

    fetch(ns_regolamento.api_file + '?' + ns_regolamento.param + '=save', { method: 'POST', body: fd })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                window.location.reload();
            } else {
                showNotification(data.message || 'Errore nel salvataggio', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva';
            }
        })
        .catch(() => {
            showNotification('Errore nel salvataggio', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva';
        });
}

// Cancellazione di un articolo
function eliminaArticoloRegolamento(id) {
    if (!confirm('Eliminare questo articolo del regolamento?')) return;

    const fd = new FormData();
    fd.append('id', id);

    fetch(ns_regolamento.api_file + '?' + ns_regolamento.param + '=erase', { method: 'POST', body: fd })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) window.location.reload();
        })
        .catch(() => showNotification('Errore nell\'eliminazione', 'error'));
}

document.addEventListener('DOMContentLoaded', function () {
    // Sposta il modale sotto <body> — stesso motivo di personaggio.js: il modale ha
    // position:fixed ma se resta dentro #maincontent finisce sotto le sidebar (z-index)
    const modal = document.getElementById('regolamento_edit_container');
    if (modal) document.body.appendChild(modal);

    const closeBtn = document.getElementById('closeRegolamentoModal');
    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = 'none';
        window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });
    }

    const form = document.getElementById('formSaveRegolamento');
    if (form) form.addEventListener('submit', saveRegolamentoForm);

    // Ricerca client-side per titolo, stessa logica del filtro personaggi
    const inputSearch = document.getElementById('searchRegolamento');
    const table = document.getElementById('regolamentoTable');
    if (inputSearch && table) {
        inputSearch.addEventListener('keyup', function () {
            const filtro = inputSearch.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    }
});
