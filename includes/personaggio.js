const ns_personaggio = {
    api_file: 'pages/api_personaggio.php',
    param: 'op'
};

function popolaPgForm(nome) {
    // Fetch dei dati completi dell'utente
    fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=getUsr&name=' + encodeURIComponent(nome))
        .then(response => response.json())
        .then(user => {
            // SALVA I DATI ORIGINALI per il confronto
            window.originalData = {
                personaggio: user.nome || '',
                nome: user.nome || '',
                cognome: user.cognome || '',
                eta: user.eta || '',
                email: user.email || '',
                luogo: user.natoa || '',
                volto: user.volto || '',
                musica: user.url_media || '',
                alias: user.nickname || '',
                img: user.url_img || '',
                imgchat: user.url_img_chat || '',
                background: user.principale || '',
                storia: user.storia || '',
                dice: user.descrizione || '',
                off: user.off || '',
                salute: user.salute || '',
                integrita: user.integrita || '',
                shin: user.shin || '',
                soldi: user.soldi || '',
                notorieta: user.notorieta || '',
                mestiere_punti: user.esperienza_mestiere || '',
                particolari: user.particolari || '',
                note_master: user.note_fato || '',
                razza: user.id_razza || '0',
                suoni: user.suoni || '0'
            };

            // Lista dei campi con mapping chiaro
            const fields = [
                { id: 'personaggio', value: user.nome },
                { id: 'nome', value: user.nome },
                { id: 'cognome', value: user.cognome },
                { id: 'eta', value: user.eta },
                { id: 'email', value: user.email },
                { id: 'luogo', value: user.natoa },
                { id: 'volto', value: user.volto },
                { id: 'musica', value: user.url_media },
                { id: 'alias', value: user.nickname },
                { id: 'img', value: user.url_img },
                { id: 'imgchat', value: user.url_img_chat },
                { id: 'background', value: user.principale },
                { id: 'storia', value: user.storia },
                { id: 'dice', value: user.descrizione },
                { id: 'off', value: user.off },
                { id: 'salute', value: user.salute },
                { id: 'integrita', value: user.integrita },
                { id: 'shin', value: user.shin },
                { id: 'soldi', value: user.soldi },
                { id: 'notorieta', value: user.notorieta },
                { id: 'mestiere_punti', value: user.esperienza_mestiere },
                { id: 'particolari', value: user.particolari },
                { id: 'note_master', value: user.note_fato },
                { id: 'razza', value: user.id_razza }
            ];

            fields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element) element.value = field.value || '';
            });

            // Gestione checkbox suoni
            const suoniCheckbox = document.getElementById('suoni');
            if (suoniCheckbox) {
                suoniCheckbox.checked = (user.suoni == '1');
            }

            // max dinamico per salute e integrità dal DB
            const saluteInput = document.getElementById('salute');
            const saluteNote  = document.getElementById('salute-max-note');
            if (saluteInput && user.salute_max != null) {
                saluteInput.max = user.salute_max;
                if (saluteNote) saluteNote.textContent = '/ ' + user.salute_max;
            }
            const integritaInput = document.getElementById('integrita');
            const integritaNote  = document.getElementById('integrita-max-note');
            if (integritaInput && user.integrita_max != null) {
                integritaInput.max = user.integrita_max;
                if (integritaNote) integritaNote.textContent = '/ ' + user.integrita_max;
            }

            const pgNameEl = document.getElementById('gp-modal-pg-name');
            if (pgNameEl) pgNameEl.textContent = nome;

            document.getElementById('pg_edit_container').style.display = 'flex';
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore nel caricamento dati');
        });
}

function checkVolto() {
    var volto = document.getElementById('volto').value;

    if (volto.length > 2) {
        fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=checkVolto&volto=' + encodeURIComponent(volto))
            .then(response => response.json())
            .then(user => { alert(user[0]); })
            .catch(error => {
                console.error('Errore:', error);
                alert('Errore nel caricamento dati');
            });
    }
}

// Funzione che serve per salvare i dati del form di un personaggio in fase di modifica
function savePgForm(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    // CONFRONTA I DATI E INVIA SOLO QUELLI MODIFICATI
    const modifiedData = getModifiedFields(formData);

    if (Object.keys(modifiedData).length === 0) {
        showNotification('Nessuna modifica da salvare.', 'info');
        return;
    }

    // Prepara i dati da inviare (solo modificati)
    const submitData = new FormData();
    submitData.append('ajax_request', 'true');
    submitData.append('personaggio', formData.get('personaggio'));

    // Aggiungi solo i campi modificati
    Object.keys(modifiedData).forEach(field => {
        submitData.append(field, modifiedData[field]);
    });

    const submitBtn = document.getElementById('gp-save-btn') || form.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<span class="save-loader"></span> Salvando...';
    submitBtn.disabled = true;

    fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=savePg', {
        method: 'POST',
        body: submitData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification('Modifiche salvate con successo!', 'success');

                submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Salvato!';
                submitBtn.classList.add('btn-action--saved');
                submitBtn.disabled = false;
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva modifiche';
                    submitBtn.classList.remove('btn-action--saved');
                }, 2500);

                if (window.originalData) {
                    Object.keys(modifiedData).forEach(field => {
                        window.originalData[field] = modifiedData[field];
                    });
                }
            } else {
                throw new Error(result.error || 'Errore nel salvataggio');
            }
        })
        .catch(error => {
            showNotification('Errore: ' + error.message, 'error');
        })
        .finally(() => {
            if (!submitBtn.classList.contains('btn-action--saved')) {
                submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salva modifiche';
                submitBtn.disabled = false;
            }
        });
}

// Normalizza un valore testuale: unifica i line-ending e rimuove spazi finali.
// I browser normalizzano \r\n → \n nei textarea; senza questa funzione i campi
// HTML dal DB (che arrivano con \r\n) risultano sempre "modificati".
function normalizeText(val) {
    return String(val ?? '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').trimEnd();
}

// Funzione per confrontare i dati e trovare i campi modificati
function getModifiedFields(formData) {
    if (!window.originalData) return {};

    const modified = {};
    const fieldMappings = {
        'personaggio': 'nome',
        'nome': 'nome',
        'cognome': 'cognome',
        'eta': 'eta',
        'email': 'email',
        'luogo': 'luogo',
        'volto': 'volto',
        'musica': 'musica',
        'alias': 'alias',
        'img': 'img',
        'imgchat': 'imgchat',
        'background': 'background',
        'storia': 'storia',
        'dice': 'dice',
        'off': 'off',
        'salute': 'salute',
        'integrita': 'integrita',
        'shin': 'shin',
        'soldi': 'soldi',
        'notorieta': 'notorieta',
        'mestiere_punti': 'mestiere_punti',
        'particolari': 'particolari',
        'note_master': 'note_master',
        'razza': 'razza',
        'suoni': 'suoni'
    };

    Object.keys(fieldMappings).forEach(formField => {
        // Campo non nel form (sezione nascosta per permessi) → ignora
        if (formField !== 'suoni' && formData.get(formField) === null) return;

        const currentValue  = getFormFieldValue(formData, formField);
        const originalValue = String(window.originalData[formField] ?? '');

        if (normalizeText(currentValue) !== normalizeText(originalValue)) {
            modified[formField] = currentValue;
        }
    });

    return modified;
}

// Funzione helper per ottenere il valore di un campo form
function getFormFieldValue(formData, fieldName) {
    if (fieldName === 'suoni') {
        return formData.get(fieldName) ? '1' : '0';
    }
    return formData.get(fieldName) ?? '';
}
// Scorrimento rapido del form nel modale (top / bottom)
function gpScrollForm(dir) {
    const form = document.getElementById('formSavePg');
    if (!form) return;
    form.scrollTo({ top: dir === 'top' ? 0 : form.scrollHeight, behavior: 'smooth' });
}

// Cancellazione esiliati
function eliminaEsiliati() {
    if (confirm('Eliminare definitivamente tutti i personaggi esiliati? Questa operazione è irreversibile.')) {
        fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=deleteEsiliati')
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, 'success');
                // In SPA (GestionePersonaggio.jsx) window.refreshPgList ricarica solo la
                // tabella; nel vecchio caricamento PHP (dove non e' definita) resta il reload.
                if (window.refreshPgList) window.refreshPgList(); else window.location.reload();
            })
            .catch(error => {
                console.error('Errore durante la cancellazione dei pg esiliati:', error);
                showNotification('Errore durante la cancellazione dei pg esiliati', 'error');
            });
    }
}
// Ripristino personaggio cancellato (permessi=-1 -> 0). Endpoint diverso dal
// resto di questo file (api_account.php, non api_personaggio.php): riusa la
// stessa logica gia' presente li' per l'autocancellazione, invece di
// duplicarla qui.
function ripristinaPg(nome) {
    if (!confirm('Ripristinare ' + nome + '?')) return;
    fetch('pages/api_account.php?op=restore', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ account: nome })
    })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) { if (window.refreshPgList) window.refreshPgList(); else window.location.reload(); }
        })
        .catch(error => {
            console.error('Errore durante il ripristino:', error);
            showNotification('Errore durante il ripristino', 'error');
        });
}
// Cancellazione logica (permessi=-1), reversibile con ripristinaPg(). Stesso
// endpoint/funzione condivisa (softDeletePg) di api_account.php op=delete
// (autocancellazione) e della pulizia automatica inattivi.
function disattivaPg(nome) {
    if (!confirm('Disattivare ' + nome + '? Il personaggio verra\' azzerato (statistiche, skill, affiliazioni) e marcato come cancellato — reversibile da "Ripristina".')) return;
    fetch('pages/api_account.php?op=admin_delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ account: nome })
    })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) { if (window.refreshPgList) window.refreshPgList(); else window.location.reload(); }
        })
        .catch(error => {
            console.error('Errore durante la disattivazione:', error);
            showNotification('Errore durante la disattivazione', 'error');
        });
}
// Cancellazione fisica e irreversibile di un singolo personaggio. Sostituisce la
// vecchia form POST verso main.php?page=erasepg_scelta, che portava fuori dalla SPA.
function eliminaPg(nome) {
    if (!confirm('Eliminare definitivamente ' + nome + '? Operazione irreversibile.')) return;
    fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=deletePg', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pg: nome })
    })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) { if (window.refreshPgList) window.refreshPgList(); else window.location.reload(); }
        })
        .catch(error => {
            console.error('Errore durante la cancellazione:', error);
            showNotification('Errore durante la cancellazione', 'error');
        });
}

// Reset punti personaggio
function resetPg(pgs) {
    if (confirm('Stai per azzerare i personaggi selezionati. Procedere?')) {
        fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=resetPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pgs: pgs })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    // window.location.reload();
                } else {
                    showNotification('Errore nella cancellazione: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}

/**
 * Attacca gli event listener della pagina gestione_personaggio.
 * Esposta globalmente come window.initPersonaggioListeners() per essere
 * richiamata da GestionePersonaggio.jsx in navigazione SPA, dove
 * DOMContentLoaded e' gia' scattato quando questo script viene iniettato
 * (stesso problema/soluzione di window.initChatListeners() in chat.js).
 *
 * Nota: qui non si teletrasporta piu' #pg_edit_container sotto <body> via
 * appendChild — in SPA quel nodo e' gia' renderizzato li' direttamente da
 * React tramite createPortal (stesso scopo: evitare che lo stacking context
 * di #maincontent copra il modal), quindi il teleport manuale non serve piu'.
 * Nel caricamento PHP tradizionale (gestione.php?page=gestione_personaggio,
 * ancora servito dal vecchio .inc.php) il modal resta dentro #maincontent:
 * non e' un problema li' perche' quella pagina non ha le sidebar con
 * z-index:1000 di main.php.
 */
window.initPersonaggioListeners = function () {
    // Gestione modale di modifica personaggio
    const closePgModal = document.getElementById("closePgModal");

    if (closePgModal) {
        const modalPG = closePgModal.closest('.pg-edit-container');
        if (modalPG) {
            closePgModal.onclick = () => modalPG.style.display = "none";
            window.onclick = e => { if (e.target === modalPG) modalPG.style.display = "none"; };
        }
    }

    // Salvataggio dei dati del personaggio nel database — .onsubmit invece di
    // addEventListener: idempotente, puo' essere richiamato piu' volte senza
    // accumulare listener (navigazione SPA avanti/indietro sulla pagina)
    const formPg = document.querySelector('#pg_edit_container form');
    if (formPg) formPg.onsubmit = savePgForm;

    // Motore di ricerca personaggio — stesso motivo, .onkeyup invece di addEventListener
    const inputSearchPg = document.getElementById("searchPg");
    const listaPg = document.getElementById("pgTable");

    if (inputSearchPg && listaPg) {
        inputSearchPg.onkeyup = function () {
            const allPg = listaPg.getElementsByTagName("a");
            const filtroPg = inputSearchPg.value.toLowerCase();

            for (let i = 0; i < allPg.length; i++) {
                let testoSearchPg = allPg[i].textContent.toLowerCase();
                allPg[i].closest("tr").style.display = testoSearchPg.includes(filtroPg) ? "" : "none";
            }
        };
    }
};

// Al primo caricamento PHP la funzione viene chiamata automaticamente da DOMContentLoaded.
// In navigazione SPA, GestionePersonaggio.jsx la chiama esplicitamente dopo aver montato il DOM.
document.addEventListener('DOMContentLoaded', window.initPersonaggioListeners);