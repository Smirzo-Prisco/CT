const ns_global = {
    api_file: 'pages/api_global.php',
    param: 'op'
};

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
    fetch(ns_global.api_file + '?' + ns_global.param + '=getUsr&name=' + encodeURIComponent(nome))
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
        fetch(ns_global.api_file + '?' + ns_global.param + '=checkVolto&volto=' + encodeURIComponent(volto))
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

    fetch(ns_global.api_file + '?' + ns_global.param + '=savePg', {
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
        fetch(ns_global.api_file + '?' + ns_global.param + '=deleteEsiliati')
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
        fetch(ns_global.api_file + '?' + ns_global.param + '=resetPg', {
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

    loadOnlineUsers(container);

    if (window.ctSocket) {
        window.ctSocket.on('users:update', () => loadOnlineUsers(container));
    }
}
/** Carica gli utenti online via AJAX */
function loadOnlineUsers(container) {
    const timestamp = new Date().getTime();

    fetch(ns_global.api_file + '?' + ns_global.param + `=getPresentiOnline&t=${timestamp}`)
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
    // online-users-container è gestito dal bundle React (OnlineUsers.jsx via ct:ready)
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
});