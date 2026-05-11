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

            document.getElementById('pg_edit_container').style.display = 'block';
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

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.innerHTML = '<span class="save-loader"></span> Salvando...';
    submitBtn.disabled = true;

    console.log('Campi modificati:', Object.keys(modifiedData).length, modifiedData);

    fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=savePg', {
        method: 'POST',
        body: submitData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification('Modifiche salvate con successo! (' + result.campi_modificati + ' campi)', 'success');
                document.getElementById('pg_edit_container').style.display = 'none';

                // Aggiorna i dati originali con quelli nuovi
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
            console.error('Errore:', error);
            showNotification('Errore: ' + error.message, 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
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
        'particolari': 'particolari',
        'note_master': 'note_master',
        'razza': 'razza',
        'suoni': 'suoni'
    };

    Object.keys(fieldMappings).forEach(formField => {
        const currentValue = getFormFieldValue(formData, formField);
        const originalValue = window.originalData[formField] || '';

        // Confronta i valori (gestendo tipi diversi)
        if (String(currentValue) != '' && String(currentValue) !== String(originalValue)) {
            modified[formField] = currentValue;
        }
    });

    return modified;
}

// Funzione helper per ottenere il valore di un campo form
function getFormFieldValue(formData, fieldName) {
    if (fieldName === 'suoni') {
        // Checkbox special handling
        return formData.get(fieldName) ? '1' : '0';
    }
    return formData.get(fieldName) || '';
}
// Cancellazione esiliati
function eliminaEsiliati() {
    if (confirm('Eliminare definitivamente tutti i personaggi esiliati? Questa operazione è irreversibile.')) {
        fetch(ns_personaggio.api_file + '?' + ns_personaggio.param + '=deleteEsiliati')
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, 'success');
                window.location.reload();
            })
            .catch(error => {
                console.error('Errore durante la cancellazione dei pg esiliati:', error);
                showNotification('Errore durante la cancellazione dei pg esiliati', 'error');
            });
    }
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

/****************   Al completo caricamento del DOM della pagina... **********************************/
document.addEventListener('DOMContentLoaded', function () {
    // Gestione modale di modifica personaggio
    const closePgModal = document.getElementById("closePgModal");

    if (closePgModal) {
        const modalPG = closePgModal.closest('.pg-edit-container') || this.previousElementSibling?.closest('.pg-edit-container');
        closePgModal.onclick = () => modalPG.style.display = "none";

        window.onclick = e => { if (e.target === modalPG) modalPG.style.display = "none"; };
    }

    // Salvataggio dei dati del personaggio nel database
    const formPg = document.querySelector('#pg_edit_container form');
    if (formPg) formPg.addEventListener('submit', savePgForm);

    // Motore di ricerca personaggio
    const inputSearchPg = document.getElementById("searchPg");
    const listaPg = document.getElementById("pgTable");

    if (inputSearchPg) {
        inputSearchPg.addEventListener("keyup", function () {
            const allPg = listaPg.getElementsByTagName("a");
            const filtroPg = inputSearchPg.value.toLowerCase();

            for (let i = 0; i < allPg.length; i++) {
                let testoSearchPg = allPg[i].textContent.toLowerCase();
                allPg[i].closest("tr").style.display = testoSearchPg.includes(filtroPg) ? "" : "none";
            }
        });
    }
});