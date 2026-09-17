const ns_gestione_tipi = {
    api_file: 'pages/api_gestione_tipi.php',
    param: 'op'
};

let modalTipo = null;

function tipoTypes() {
    return document.getElementById('tipiTable').dataset.types;
}

function apriModaleCreazioneTipo() {
    document.getElementById('tipoModalTitle').textContent = 'Nuovo tipo';
    document.getElementById('tipoForm').reset();
    document.getElementById('tipo_cod').value = '';
    modalTipo.style.display = 'flex';
}

function apriModaleModificaTipo(codTipo, nome) {
    document.getElementById('tipoModalTitle').textContent = 'Modifica tipo';
    document.getElementById('tipo_cod').value = codTipo;
    document.getElementById('tipo_nome').value = nome;
    modalTipo.style.display = 'flex';
}

const tipoForm = document.getElementById('tipoForm');
if (tipoForm) {
    tipoForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(ns_gestione_tipi.api_file + '?' + ns_gestione_tipi.param + '=saveTipo', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (modalTipo) modalTipo.style.display = 'none';
                    window.location.reload();
                } else {
                    showNotification('Errore nel salvataggio: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    });
}

function eliminaTipo(codTipo) {
    if (!confirm('Eliminare questo tipo? I riferimenti esistenti verranno azzerati.')) return;

    fetch(ns_gestione_tipi.api_file + '?' + ns_gestione_tipi.param + '=deleteTipo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ types: tipoTypes(), cod_tipo: codTipo })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                window.location.reload();
            } else {
                showNotification('Errore nella cancellazione: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore nella cancellazione', 'error');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    modalTipo = document.getElementById('tipoModal');

    const closeTipoModal = document.getElementById('closeTipoModal');
    if (closeTipoModal) closeTipoModal.onclick = () => modalTipo.style.display = 'none';

    window.addEventListener('click', function (e) {
        if (e.target === modalTipo) modalTipo.style.display = 'none';
    });

    // Motore di ricerca tipi (stesso pattern di #searchPg in personaggio.js)
    const inputSearchTipo = document.getElementById('searchTipo');
    const tabellaTipi = document.getElementById('tipiTable');

    if (inputSearchTipo && tabellaTipi) {
        inputSearchTipo.addEventListener('keyup', function () {
            const righe = tabellaTipi.querySelectorAll('tbody tr');
            const filtro = inputSearchTipo.value.toLowerCase();

            righe.forEach(riga => {
                const nome = riga.querySelector('.gp-cell--name');
                if (!nome) return;
                riga.style.display = nome.textContent.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    }
});
