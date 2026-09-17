const ns_abilita_master = {
    api_file: 'pages/api_abilita_master.php',
    param: 'op'
};

let modalAbilita = null;
let modalAssegnaAbilita = null;

// Apertura modale per CREAZIONE skill temporanea
function apriModaleCreazioneAbilita() {
    document.getElementById('abilitaModalTitle').innerHTML = '<i class="fa-solid fa-wand-sparkles"></i> Nuova skill temporanea';
    document.getElementById('abilitaForm').reset();
    document.getElementById('abilita_id').value = '';
    modalAbilita.style.display = 'flex';
}

// Apertura modale per MODIFICA skill temporanea — carica i dati via AJAX
function apriModaleModificaAbilita(idAbilita) {
    fetch(ns_abilita_master.api_file + '?' + ns_abilita_master.param + `=getAbilita&id=${idAbilita}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification(data.message || 'Errore nel caricamento', 'error');
                return;
            }
            document.getElementById('abilitaModalTitle').innerHTML = '<i class="fa-solid fa-wand-sparkles"></i> Modifica skill temporanea';
            document.getElementById('abilita_id').value = data.abilita.id_abilita;
            document.getElementById('abilita_nome').value = data.abilita.nome;
            document.getElementById('abilita_descrizione').value = data.abilita.descrizione;
            modalAbilita.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore nel caricamento', 'error');
        });
}

// Apertura modale ASSEGNA — la skill è già nota (riga di partenza), va scelto solo il personaggio
function apriModaleAssegnaAbilita(idAbilita, nomeAbilita) {
    document.getElementById('assegnaModalSkill').textContent = nomeAbilita;
    document.getElementById('assegna_id_abilita').value = idAbilita;
    modalAssegnaAbilita.style.display = 'flex';
}

// SALVA skill (creazione/modifica)
const abilitaForm = document.getElementById('abilitaForm');
if (abilitaForm) {
    abilitaForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(ns_abilita_master.api_file + '?' + ns_abilita_master.param + '=saveAbilita', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (modalAbilita) modalAbilita.style.display = 'none';
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

// ASSEGNA a personaggio
const assegnaForm = document.getElementById('assegnaForm');
if (assegnaForm) {
    assegnaForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(ns_abilita_master.api_file + '?' + ns_abilita_master.param + '=assegnaAbilita', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    if (modalAssegnaAbilita) modalAssegnaAbilita.style.display = 'none';
                } else {
                    showNotification('Errore nell\'assegnazione: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nell\'assegnazione', 'error');
            });
    });
}

// ELIMINA skill temporanea
function eliminaAbilita(idAbilita) {
    if (!confirm('Eliminare definitivamente questa skill temporanea?')) return;

    fetch(ns_abilita_master.api_file + '?' + ns_abilita_master.param + '=deleteAbilita', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_abilita: idAbilita })
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
    modalAbilita = document.getElementById('abilitaModal');
    modalAssegnaAbilita = document.getElementById('assegnaModal');

    const closeAbilitaModal = document.getElementById('closeAbilitaModal');
    if (closeAbilitaModal) closeAbilitaModal.onclick = () => modalAbilita.style.display = 'none';

    const closeAssegnaModal = document.getElementById('closeAssegnaModal');
    if (closeAssegnaModal) closeAssegnaModal.onclick = () => modalAssegnaAbilita.style.display = 'none';

    window.addEventListener('click', function (e) {
        if (e.target === modalAbilita) modalAbilita.style.display = 'none';
        if (e.target === modalAssegnaAbilita) modalAssegnaAbilita.style.display = 'none';
    });

    // Motore di ricerca skill (stesso pattern di #searchPg in personaggio.js)
    const inputSearchAbilita = document.getElementById('searchAbilita');
    const tabellaAbilita = document.getElementById('abilitaTable');

    if (inputSearchAbilita && tabellaAbilita) {
        inputSearchAbilita.addEventListener('keyup', function () {
            const righe = tabellaAbilita.querySelectorAll('tbody tr');
            const filtro = inputSearchAbilita.value.toLowerCase();

            righe.forEach(riga => {
                const nome = riga.querySelector('.gp-cell--name');
                if (!nome) return; // riga "nessuna skill" senza .gp-cell--name
                riga.style.display = nome.textContent.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    }
});
