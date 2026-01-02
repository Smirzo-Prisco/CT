/** * Libreria javascript di base
 * @author Blancks
 */

/** * Funzione selettore rapido per JS
 * @author Blancks
 */
function gdrcd_selector(id) {
    return document.getElementById(id);
}

/** * Gestore del fade in/out
 * @author Blancks
 */
function start_fade(id, op) {
    if (op == '+') {
        set_fade(id, 0);
        toggle_fade(id, 0, op);
    } else
        toggle_fade(id, 100, op);
}

function toggle_fade(id, opacity, op) {
    if (opacity <= 100 && opacity >= 0) {
        set_fade(id, opacity);
        opacity = (op == '+') ? opacity + 5 : opacity - 5;

        setTimeout("toggle_fade('" + id + "'," + opacity + ", '" + op + "')", 20);
    }
}

function set_fade(id, opacity) {
    var obj = gdrcd_selector(id);

    obj.style.filter = "alpha(opacity:" + opacity + ")";
    obj.style.KHTMLOpacity = opacity / 100;
    obj.style.MozOpacity = opacity / 100;
    obj.style.opacity = opacity / 100;
}

function ctNews(pgName) {
    var xhttp = new XMLHttpRequest();
    xhttp.open("POST", window.location.origin + "/pages/leggi_ctnews.php", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    var param = "pg=" + pgName;
    xhttp.send(param);

    var x = document.getElementById("ctnews");
    if (x.style.height === '0px' && x.style.opacity === '0') {
        x.style.height = 'auto';
        x.style.opacity = '1';
        new Audio(window.location.origin + '/sounds/tg5.mp3').play();
    } else {
        x.style.height = '0px';
        x.style.opacity = '0';
    }
}

// Funzione che resetta tutti i campi all'interno di un <div>
function resettaCampiDiv(divId) {
    const div = document.getElementById(divId);
    if (!div) {
        console.error(`Div #${divId} non trovato`);
        return;
    }

    console.log(`Resetto campi nel div: #${divId}`);

    // Seleziona TUTTI i campi input, select, textarea nel div
    const campi = div.querySelectorAll('input, select, textarea');

    campi.forEach(campo => {
        const tipo = campo.type ? campo.type.toLowerCase() : '';
        const tagName = campo.tagName.toLowerCase();

        console.log(`Resetto: ${tagName}#${campo.id || campo.name || 'senza-nome'} (${tipo})`);

        if (tagName === 'select') {
            // Per select: torna alla prima opzione
            campo.selectedIndex = 0;

            // Se ha option con value="", seleziona quella
            const emptyOption = campo.querySelector('option[value=""]');
            if (emptyOption) {
                emptyOption.selected = true;
            }
        }
        else if (tagName === 'textarea') {
            // Per textarea: svuota
            campo.value = '';
        }
        else if (tipo === 'text' || tipo === 'email' || tipo === 'password' ||
            tipo === 'number' || tipo === 'tel' || tipo === 'url') {
            // Per input di testo: svuota
            campo.value = '';
        }
        else if (tipo === 'checkbox' || tipo === 'radio') {
            // Per checkbox/radio: deseleziona
            campo.checked = false;

            // Se c'è un default checked, ripristinalo
            if (campo.hasAttribute('data-default-checked')) {
                campo.checked = campo.getAttribute('data-default-checked') === 'true';
            }
        }
        else if (tipo === 'hidden') {
            // Per hidden: di solito non si resetta
            // Ma se vuoi, puoi:
            if (campo.hasAttribute('data-default-value')) {
                campo.value = campo.getAttribute('data-default-value');
            }
        }
        // Per altri tipi di input
        else {
            campo.value = '';
        }

        // Trigger evento change per aggiornare eventuali listener
        campo.dispatchEvent(new Event('change', { bubbles: true }));
    });

    console.log(`Reset completato: ${campi.length} campi resettati`);

    // Opzionale: focus sul primo campo
    const primoCampo = campi[0];
    if (primoCampo && primoCampo.type !== 'hidden') {
        primoCampo.focus();
    }
}

/****************************   ERANO NEL FOOTER    ************************/
function modalWindow(name, title, url, width, height) {
    // per width ed height imposto dei valori di default così non occorre specificarli in ogni occasione
    width = typeof width === 'undefined' ? 800 : width;
    height = typeof height === 'undefined' ? 600 : height;

    // verifichiamo se nel body non esiste il sorgente per la dialog
    if ($('#dialog-' + name).length == 0) {

        // in questo caso lo creiamo:
        $('body').append('<div id="dialog-' + name + '" title="' + title + '" style="padding:0;"><iframe src="' + url + '" frameborder="no" style="position:absolute;width:100%;height:100%;" scrolling="yes"></div>');

    } else {

        // se il sorgente invece esiste già assegnamo la nuova url all´iframe:
        $('#dialog-' + name).attr('title', title);
        $('#dialog-' + name + ' iframe').attr('src', url);
    }

    // Ok, adesso siamo pronti per lanciare la modale!
    $('#dialog-' + name).dialog({ width: width, height: height });
}

// dichiaro il nome della funzione
// prende come parametro l\'input da analizzare
function conta(el) {
    // imposto il limite massimo di caratteri consentiti
    var max_char = 200000;
    // conto il numero di caratteri nell\'input
    var conta_caratteri = el.value.length;
    // verifico se i caratteri hanno superato il limite
    if (conta_caratteri >= max_char) {
        // riporto i caratteri al limite massimo
        conta_caratteri = 200000;
        // cancello dall\'input i caratteri in eccesso
        el.value = el.value.substring(0, max_char);
    }
    // aggiorno il contatore testuale
    document.getElementById("rimanenti").innerHTML = 0 + conta_caratteri;
    // calcolo la lungezza per il contatore grafico
    var width = (0 + conta_caratteri) * 5;
    // aggiorno il contatore grafico
    const countdown = document.getElementById('countdown');
    if (countdown) countdown.style.width = width + "px";
}

var modal = document.getElementById('id01');

window.onclick = function (event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function changeFrame(input_text) {
    document.getElementById("myframe").src = input_text;
}

var modal = document.getElementById('id02');

window.onclick = function (event) {
    if (event.target == modal) modal.style.display = "none";
}

function changeFrame(input_text) {
    document.getElementById("myframe").src = input_text;
}

// JavaScript per aprire e chiudere la modale delle breaking news -->
const openModalBreaking = document.getElementById("openModalBreaking");
if (openModalBreaking) {
    document.getElementById("openModalBreaking").onclick = function () {
        document.getElementById("modalBreaking").style.display = "block";
    }
}

const closeModalBreaking = document.getElementById("closeModalBreaking");
if (closeModalBreaking) {
    document.getElementById("closeModalBreaking").onclick = function () {
        document.getElementById("modalBreaking").style.display = "none";
    }
}

// Chiudere la modale cliccando fuori di essa
window.onclick = function (event) { if (event.target == document.getElementById("modalBreaking")) document.getElementById("modalBreaking").style.display = "none"; }

// Funzione per mostrare notifiche
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `save-notification ${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => { notification.remove(); }, 10000);
}

/***********    GESTIONE PERSONAGGI  *********************/
function popolaPgForm(nome) {
    // Fetch dei dati completi dell'utente
    fetch(window.location.origin + "/pages/ajax_engine.php?op=getUsr&name=" + encodeURIComponent(nome))
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
        fetch(window.location.origin + "/pages/ajax_engine.php?op=checkVolto&volto=" + encodeURIComponent(volto))
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

    fetch('/pages/ajax_engine.php?op=savePg', {
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
        fetch('/pages/ajax_engine.php?op=deleteEsiliati')
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
        fetch('/pages/ajax_engine.php?op=resetPg', {
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
/***********    FINE    GESTIONE PERSONAGGI  *********************/

/***********    GESTIONE OGGETTI  *********************/
// Variabili globali oggetto
let modalObj = null;
let isEditMode = false;

function gestioneCampiDinamici(categoriaObj) {
    const categoria = categoriaObj.value;

    if (categoria) {
        abilitaCampiCategoria(categoria);
        caricaTipiPerCategoriaObj(categoria);
    } else {
        // Se non c'è categoria, disabilita tutto
        document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
            sec.style.display = 'none';
            sec.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = true;
            });
        });
    }
}

// Popola il form con i dati
function popolaFormObj(oggetto) {
    document.getElementById('id_oggetto').value = oggetto.id_oggetto;
    document.getElementById('nome').value = oggetto.nome;
    document.getElementById('descrizione').value = oggetto.descrizione;
    document.getElementById('ubicabile').value = oggetto.ubicabile;
    document.getElementById('categoriaObj').value = oggetto.categoria;

    // Immagine preview
    if (oggetto.urlimg) {
        document.getElementById('previewImgObj').src = 'imgs/items/' + oggetto.urlimg;
        document.getElementById('imagePreviewObj').style.display = 'block';
    }

    // Popola sezioni dinamiche (il tipo verrà impostato dopo)
    popolaCampiDinamiciObj(oggetto);
}

// Apertura modale per CREAZIONE
function caricaTipiPerCategoriaObj(categoria, tipoSelezionato = '') {
    return fetch(`/pages/ajax_engine.php?op=getTipiObj&categoria=${categoria}`)
        .then(response => response.json())
        .then(data => {
            const selectTipo = document.getElementById('tipo');
            selectTipo.innerHTML = '<option value="">Seleziona tipo</option>';

            data.tipi.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.cod_tipo;
                option.textContent = tipo.descrizione;
                selectTipo.appendChild(option);
            });

            // IMPOSTA IL VALORE SOLO DOPO AVER POPOLATO LA SELECT
            if (tipoSelezionato) {
                selectTipo.value = tipoSelezionato;
            }

            return data;
        })
        .catch(error => {
            console.error('Errore nel caricamento tipi:', error);
            showNotification('Errore nel caricamento dei tipi', 'error');
        });
}

// Apertura modale per MODIFICA
function apriModaleModificaObj(idOggetto) {
    isEditMode = true;
    document.getElementById('modalTitleObj').textContent = 'Modifica Oggetto';

    // Carica dati oggetto via AJAX
    fetch(`/pages/ajax_engine.php?op=getObj&id=${idOggetto}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const oggetto = data.oggetto;

                // Popola i campi base
                document.getElementById('id_oggetto').value = oggetto.id_oggetto;
                document.getElementById('nome').value = oggetto.nome;
                document.getElementById('descrizione').value = oggetto.descrizione;
                document.getElementById('ubicabile').value = oggetto.ubicabile;
                document.getElementById('categoriaObj').value = oggetto.categoria;

                // Immagine preview
                if (oggetto.urlimg) {
                    document.getElementById('previewImgObj').src = 'imgs/items/' + oggetto.urlimg;
                    document.getElementById('imagePreviewObj').style.display = 'block';
                }

                // MOSTRA LA MODALE
                modalObj.style.display = 'block';

                // CARICA I TIPI E POI GESTISCI I CAMPI DINAMICI
                caricaTipiPerCategoriaObj(oggetto.categoria, oggetto.tipo)
                    .then(() => {
                        // RIABILITA ESPLICITAMENTE I CAMPI DELLA CATEGORIA CORRETTA
                        abilitaCampiCategoria(oggetto.categoria);
                        // Popola i campi dinamici
                        popolaCampiDinamiciObj(oggetto);

                        // AGGIUNGI EVENT LISTENER PER CAMBIO CATEGORIA ANCHE IN MODIFICA
                        setupCategoriaChangeListener();
                    });

            } else {
                showNotification('Errore nel caricamento oggetto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore: ' + error.message, 'error');
        });
}

// Popola i campi dinamici
function popolaCampiDinamiciObj(oggetto) {
    const categoria = oggetto.categoria;

    // Popola i campi specifici della categoria
    switch (categoria) {
        case 'arma':
            setSafeValue('[name="tipo_arma"]', oggetto.tipo_arma || 1);
            setSafeValue('[name="bonus_arma"]', oggetto.attacco || 1);
            setSafeValue('[name="ricarica_massima"]', oggetto.ricarica_massima || 1);
            break;
        case 'curativo':
            setSafeValue('[name="salute_integra"]', oggetto.heal || 0);
            setSafeValue('[name="integrita_integra"]', oggetto.bonus_car0 || 0);
            setSafeValue('[name="cariche_curativo"]', oggetto.cariche || 1);
            break;
        case 'statistica':
            setSafeValue('[name="temp_giorni"]', oggetto.temp_giorni || 1);
            setSafeValue('[name="bonus_car1_extra"]', oggetto.bonus_car1_extra || 0);
            setSafeValue('[name="bonus_car2_extra"]', oggetto.bonus_car2_extra || 0);
            setSafeValue('[name="bonus_car3_extra"]', oggetto.bonus_car3_extra || 0);
            setSafeValue('[name="bonus_car4_extra"]', oggetto.bonus_car4_extra || 0);
            setSafeValue('[name="bonus_car5_extra"]', oggetto.bonus_car5_extra || 0);
            setSafeValue('[name="cariche_statistica"]', oggetto.cariche || 1);
            setSafeValue('[name="ricarica_massima_statistica"]', oggetto.ricarica_massima || 0);
            break;
        case 'magico':
            setSafeValue('[name="cariche_magico"]', oggetto.cariche || 1);
            setSafeValue('[name="ricarica_massima_magico"]', oggetto.ricarica_massima || 1);
            break;
        case 'standard':
            setSafeValue('[name="cariche_standard"]', oggetto.cariche || 'illimitato');
            break;
    }
}

// Funzione helper per settare valori in modo sicuro
function setSafeValue(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        const oldValue = element.value;
        element.value = value;

        return true;
    } else return false;
}

// Variabile per memorizzare la categoria precedente
let previousCategoria = '';

// Apertura modale per CREAZIONE
function apriModaleCreazioneObj() {
    isEditMode = false;
    document.getElementById('modalTitleObj').textContent = 'Crea Nuovo Oggetto';
    document.getElementById('oggettoForm').reset();
    document.getElementById('id_oggetto').value = '';
    document.getElementById('imagePreviewObj').style.display = 'none';
    // document.getElementById('categoriaObj').disabled = false;

    // Reset sezioni dinamiche
    document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
        sec.style.display = 'none';
    });

    // Mostra modale e carica tipi
    modalObj.style.display = 'block';

    // Setup del listener per cambio categoria
    setupCategoriaChangeListener();

    caricaTipiPerCategoriaObj('standard');
}

// Funzione per abilitare esplicitamente i campi di una categoria
function abilitaCampiCategoria(categoria) {
    // Disabilita TUTTI i campi dinamici prima
    document.querySelectorAll('.sezione-dinamica-obj input, .sezione-dinamica-obj select, .sezione-dinamica-obj textarea').forEach(field => {
        field.disabled = true;
    });

    // Nascondi TUTTE le sezioni
    document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
        sec.style.display = 'none';
    });

    // Mostra e abilita solo la sezione della categoria corrente
    const sezioneAttiva = document.getElementById(`sezione_${categoria}`);
    if (sezioneAttiva) {
        sezioneAttiva.style.display = 'block';
        sezioneAttiva.querySelectorAll('input, select, textarea').forEach(field => {
            // In modifica, alcuni campi potrebbero essere disabilitati, ma i campi dinamici li abilitiamo
            if (!field.classList.contains('disabled-permanently')) field.disabled = false;
        });
    }
}

// Handler per il cambio categoria
function handleCategoriaChange(e) {
    const categoria = e.target.value;

    if (categoria) {
        // Se siamo in modifica, mostra un avviso
        if (isEditMode) {
            if (!confirm("Attenzione! Cambiando la categoria, potresti perdere alcuni dati specifici. Vuoi procedere?")) {
                // Ripristina il valore precedente
                e.target.value = previousCategoria;
                return;
            }
        }
        // Gestisci i campi dinamici
        abilitaCampiCategoria(categoria);
        caricaTipiPerCategoriaObj(categoria);

        // Salva la categoria corrente per eventuale annullamento
        previousCategoria = categoria;
    }
}

// Funzione per gestire il cambio categoria in entrambe le modalità
function setupCategoriaChangeListener() {
    const categoriaObj = document.getElementById('categoriaObj');

    // Rimuovi eventuali listener precedenti
    categoriaObj.removeEventListener('change', handleCategoriaChange);

    // Aggiungi nuovo listener
    categoriaObj.addEventListener('change', handleCategoriaChange);
}

function deleteObj(idOggetto) {
    if (confirm('Sei sicuro di voler cancellare?') && idOggetto > 0) {
        fetch('/pages/ajax_engine.php?op=deleteObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_oggetto: idOggetto })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
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
/***********    FINE    GESTIONE OGGETTI  *********************/

/***********    GESTIONE SHOP  *********************/
// Motore di ricerca shop
function buyObj(user, id_oggetto, costo, budget) {
    const qty = document.getElementById(`qty_${id_oggetto}`).value;

    if (qty == '' || qty == 0) {
        showNotification('Inserisci una quantità valida da comprare.', 'error');
        return;
    }

    if (confirm('Stai acquistando ' + qty + ' pezzi di questo oggetto per un costo totale di ' + (costo * qty) + ' CTY. Vuoi procedere?')) {
        // Se non ho abbastanza soldi per effettuare l'acquisto
        if ((budget - (costo * qty)) < 0) {
            showNotification('Fondi insufficienti per questo acquisto.', 'error');
            return;
        }

        fetch('/pages/ajax_engine.php?op=buyObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_oggetto: id_oggetto,
                user: user,
                costo: costo,
                qty: qty
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
                } else showNotification(data.message, data.error);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}

function sellObj(user, id_oggetto) {
    const qty = document.getElementById(`qty_${id_oggetto}`).value;

    if (qty == '' || qty == 0) {
        showNotification('Inserisci una quantità valida da vendere.', 'error');
        return;
    }

    if (confirm('Stai vendendo ' + qty + ' pezzi di questo oggetto. Vuoi procedere?')) {
        fetch('/pages/ajax_engine.php?op=sellObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_oggetto: id_oggetto,
                user: user,
                qty: qty
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
                } else showNotification(data.message, 'error');
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}
/***********    FINE    GESTIONE SHOP  *********************/

/***********    LOG  *********************/
function changeTab(tab) {
    window.location.href = 'main.php?page=log&tab=' + tab;
}

// Ordinamento tabella log
let _lastSort = { col: null, dir: 'asc' };

function sortTable(n) {
    const table = document.getElementById('logTable');
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // se stesso cliccato, inverti direzione; altrimenti imposta asc
    let dir = (_lastSort.col === n && _lastSort.dir === 'asc') ? 'desc' : 'asc';

    rows.sort((a, b) => {
        let x = a.children[n].innerText.trim();
        let y = b.children[n].innerText.trim();

        // confronto testuale numerico-friendly per altre colonne
        const cmp = x.localeCompare(y, undefined, { numeric: true, sensitivity: 'base' });
        return dir === 'asc' ? cmp : -cmp;
    });

    // riattacca le righe ordinate nel tbody
    rows.forEach(r => tbody.appendChild(r));

    // aggiorna stato ultimo sort (per alternanza)
    _lastSort.col = n;
    _lastSort.dir = dir;
}
/***********    FINE    LOG  *********************/

// =============================================================================
// GESTIONE UTENTI ONLINE MODERNA (AJAX)
// =============================================================================

/** Inizializza la lista utenti online moderna */
function initModernOnlineUsers(containerId = 'online-users-container') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('Container online users non trovato');
        return;
    }

    // Carica i dati iniziali
    loadOnlineUsers(container);

    // Aggiorna ogni TOT secondi
    setInterval(() => loadOnlineUsers(container), 14000);
}
/** Carica gli utenti online via AJAX */
function loadOnlineUsers(container) {
    const timestamp = new Date().getTime();

    fetch(`/pages/ajax_engine.php?op=getPresentiOnline&t=${timestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => { renderOnlineUsers(container, data); })
        .catch(error => {
            console.error('Errore caricamento utenti online:', error);
            container.innerHTML = '<div class="error">Errore nel caricamento</div>';
        });
}
/** Renderizza la lista utenti online */
function renderOnlineUsers(container, data) {
    const users = data.users || [];
    const totalOnline = data.total_online || 0;

    // 🎯 USA LO STESSO IDENTICO HTML DELL'IFRAME ORIGINALE
    let html = `
<body class="transparent_body">
<div class="pagina_presenti">
    <div class="page_title">
        <h2>Presenti</h2>
    </div>
    <div class="iframe_online">
        <div class="contenitore_presenti">`;

    if (users.length === 0) {
        html += '<div style="text-align: center; padding: 80px 10px; color: #666; font-style: italic;">Nessun utente online</div>';
    } else {
        users.forEach(user => {
            html += `
            <div class="presente">
                <a href="javascript:;" onclick="window.open('../pages/mex_privati/multi_message.php?destinatari=${user.nome}', 'titolo', 'width=650, height=600, resizable, status, scrollbars=1, location');" target="_top">
                    <img src="../themes/crystal/imgs/race_presenti/Sms.png">
                </a>
                &nbsp;&nbsp;
                <img src="../themes/crystal/imgs/race_presenti/${user.race_icon}">
                &nbsp;&nbsp;
                <a href="../main.php?page=scheda&amp;pg=${user.nome}" target="_top" style="font-size: 13px; font-family: DejaVu Serif; text-transform: capitalize; color: #c0a49e; letter-spacing: 0px;">${user.nome}</a>
            </div>`;
        });
    }

    html += `
        </div>
    </div>
    <div class="link_presenti">
        <a href="../main.php?page=presenti_estesi" target="_top">
            <div class="page_title"><h2 class="presenti_title">${totalOnline} ${totalOnline === 1 ? 'Utente Presente' : 'Utenti Presenti'}</h2></div>
        </a>
    </div>
</div>
</body>`;

    container.innerHTML = html;
}
/** Renderizza un singolo utente */
function renderUserItem(user) {
    return `
        <div class="presente">
            <a href="javascript:openMultiMessage('${user.nome}')"><img src="themes/crystal/imgs/race_presenti/Sms.png" alt="Invia messaggio"></a>
            <img src="themes/crystal/imgs/race_presenti/${user.race_icon}" alt="${user.race_icon}">
            <a href="javascript:openCharacterSheet('${user.nome}')">${user.nome}</a>
        </div>
    `;
}
/** Apri SMS (compatibilità con funzioni esistenti) */
function openMultiMessage(userName) {
    window.open(`../pages/mex_privati/multi_message.php?destinatari=${encodeURIComponent(userName)}`,
        'titolo',
        'width=650, height=600, resizable, status, scrollbars=1, location');
}
/** Apri scheda personaggio (compatibilità con funzioni esistenti) */
function openCharacterSheet(userName) { window.top.location.href = `main.php?page=scheda&pg=${userName}`; }

/****************   Al completo caricamento del DOM della pagina... **********************************/
document.addEventListener('DOMContentLoaded', function () {
    /***********    REACT - Utenti online  *********************/
    // Cerca il container moderno (senza iframe)
    if (document.getElementById('online-users-container')) initModernOnlineUsers('online-users-container');
    // Cerca il container messaggi
    if (document.getElementById('menu-messages-container')) initModernMenuMessages('menu-messages-container');

    // Se esiste il container per la chat moderna, inizializza
    if (document.getElementById('modern-chat-container')) {
        initModernChat();
    }
    /***********    GESTIONE PERSONAGGI  *********************/
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
    /***********    FINE    GESTIONE PERSONAGGI  *********************/

    /***********    GESTIONE OGGETTI  *********************/
    // Filtro tipo oggetto
    const selectTipoObj = document.getElementById("selectTipoObj");
    if (selectTipoObj) {
        document.getElementById("selectTipoObj").addEventListener("change", function () {
            var tipo = document.getElementById('filtroTipoObj');
            tipo.value = 'tipo';
            tipo.checked = true;
            tipo.dispatchEvent(new Event("change"));
        });
    }
    // Filtro categoria oggetto
    const selectCategoriaObj = document.getElementById("selectCategoriaObj");
    if (selectCategoriaObj) {
        document.getElementById("selectCategoriaObj").addEventListener("change", function () {
            var categoria = document.getElementById('filtroCategoriaObj');
            categoria.value = 'categoria';
            categoria.checked = true;
            categoria.dispatchEvent(new Event("change"));
        });
    }

    // Variabili globali oggetto
    modalObj = document.getElementById('oggettoModal');

    // Gestione cambio categoria
    const categoriaObj = document.getElementById('categoriaObj');
    if (categoriaObj) categoriaObj.addEventListener('change', gestioneCampiDinamici(categoriaObj));

    // SALVA OGGETTO
    const oggettoForm = document.getElementById("oggettoForm");

    if (oggettoForm) {
        oggettoForm.addEventListener('submit', function (e) {
            document.getElementById('categoriaObj').disabled = false;
            e.preventDefault();

            const formData = new FormData(this);
            const categoria = document.getElementById('categoriaObj').value;

            // Mappa i nomi specifici per categoria ai nomi generici per il database
            switch (categoria) {
                case 'curativo':
                    if (formData.has('cariche_curativo')) formData.set('cariche', formData.get('cariche_curativo'));
                    // curativo non ha ricarica_massima
                    break;
                case 'statistica':
                    if (formData.has('cariche_statistica')) formData.set('cariche', formData.get('cariche_statistica'));
                    if (formData.has('ricarica_massima_statistica')) formData.set('ricarica_massima', formData.get('ricarica_massima_statistica'));
                    break;
                case 'magico':
                    if (formData.has('cariche_magico')) formData.set('cariche', formData.get('cariche_magico'));
                    if (formData.has('ricarica_massima_magico')) formData.set('ricarica_massima', formData.get('ricarica_massima_magico'));
                    break;
                case 'standard':
                    if (formData.has('cariche_standard')) formData.set('cariche', formData.get('cariche_standard'));
                    // standard non ha ricarica_massima
                    break;
                // arma usa già i nomi originali
            }

            fetch('/pages/ajax_engine.php?op=saveObj', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Oggetto salvato con successo!', 'success');
                        if (modalObj) modalObj.style.display = 'none';
                        window.location.reload();
                    } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nel salvataggio', 'error');
                });
        });
    }

    // Event listeners per chiusura modale
    const closeObj = document.getElementById('closeObj');
    if (closeObj) closeObj.addEventListener('click', function () {
        if (modalObj) modalObj.style.display = 'none';
    });
    window.addEventListener('click', function (e) {
        if (e.target == modalObj) {
            modalObj.style.display = 'none';
        }
    });

    // Preview immagine
    const img_oggetto = document.getElementById("img_oggetto");
    if (img_oggetto) {
        document.getElementById('img_oggetto').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('previewImgObj').src = e.target.result;
                    document.getElementById('imagePreviewObj').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
    /***********    FINE    GESTIONE OGGETTI  *********************/

    /***********    GESTIONE SHOP  *********************/
    // Motore di ricerca shop
    const inputSearchShop = document.getElementById("searchShop");
    const listaShop = document.getElementById("shop");

    if (inputSearchShop && listaShop) {
        inputSearchShop.addEventListener("keyup", function () {
            const allShop = listaShop.getElementsByClassName("prodotto");
            const filtroShop = inputSearchShop.value.toLowerCase();

            for (let i = 0; i < allShop.length; i++) {
                let testoSearchShop = allShop[i].textContent.toLowerCase();
                allShop[i].style.display = testoSearchShop.includes(filtroShop) ? "" : "none";
            }
        });
    }
    /***********    FINE    GESTIONE SHOP  *********************/
});