const ns_chat = {
    api_file: 'pages/api_chat.php',
    param: 'op'
};

/***********    GESTIONE CHAT di gioco  *********************/
function openSection(evt, sectionName) {
    var i, tabcontent, tablinks;

    // Nasconde tutte le sezioni
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Rimuove la classe "active" da tutti i pulsanti
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Mostra la sezione corrente e aggiunge la classe "active" al pulsante che l'ha aperta
    document.getElementById(sectionName).style.display = "block";
    evt.currentTarget.className += " active";
}

// Ruoli apicali cancellano la chat di gioco
async function pulisciChat() {
    if (confirm("Sei sicuro di voler pulire questa chat?")) {
        fetch(ns_chat.api_file + '?' + ns_chat.param + '=pulisciChat')
            .then(res => res.json())
            .then(data => {
                if (window.clearChat) window.clearChat();

                window.closeChatPanel?.();
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Cura il personaggio in ospedale (+10 HP, una volta al giorno)
async function curaPg() {
    fetch(ns_chat.api_file + '?' + ns_chat.param + '=curaPg')
        .then(res => res.json())
        .then(data => {
            if (!data.success) alert(data.message);
        })
        .catch(err => console.error('Errore cura pg:', err));
}

// Applica la funzione di debounce al pulsante del back_chat
function toggleBackChat(a) {
    const img = a.querySelector("img"); // recupera l'immagine dentro <a>

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=setBackChat')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                img.src = "themes/crystal/imgs/chat/" + data.image;
                if (data.title) img.title = data.title;

            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

/** Inserisco l'azione dell'utente  */
async function sendChatMessage() {
    const form = document.getElementById("chat_form_messages");
    if (!form) {
        console.error("❌ Form della chat non trovato (chat_form_messages).");
        return;
    }

    // Recupera i campi
    const message = form.elements["message"]?.value?.trim() || "";
    const action_tag = form.elements["action_tag"]?.value || "";
    const tag = form.elements["tag"]?.value || "";
    const type = form.elements["type"]?.value || "";

    if (!message) {
        console.warn("⚠️ Messaggio vuoto, annullato.");
        return;
    }

    try {
        const response = await fetch(ns_chat.api_file + '?' + ns_chat.param + '=new_chat_message', {
            credentials: "same-origin", // mantiene la sessione PHP
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message, action_tag, tag, type })
        });

        if (!response.ok) {
            throw new Error(`Errore HTTP ${response.status}`);
        }

        const result = await response.json();

        // Gestisci la risposta del server
        if (result.success) {
            form.reset();

            // Rimuove l'altezza inline impostata da autoGrow: la textarea torna alle dimensioni CSS di default
            const ta = form.elements["message"]
            if (ta) {
                ta.style.height = ''
                // form.reset() non emette 'keyup'/'input', quindi conta() non viene
                // richiamata da sola: senza questa chiamata esplicita il contatore
                // caratteri resta fermo all'ultimo valore invece di tornare a 0.
                if (typeof conta === 'function') conta(ta)
            }

            // Ripristina il tag salvato in sessione, se necessario
            if (form.elements["tag"]) form.elements["tag"].value = result.tag ?? "";

        } else showNotification(result.message, 'error');
    } catch (err) {
        console.error("💥 Errore nell’invio del messaggio:", err);
    }
}

// ChatViewer è ora nel bundle Vite (frontend/src/components/ChatViewer.jsx)
// e viene montato da frame_chat.inc.php via ct:ready su #pagina_chat.

// Funzione per il lancio di un dado in chat
function tiraDadoChat() {
    // Per il dado caratteristica serve esattamente 1 bersaglio
    if (window.setMaxTargetExternal) window.setMaxTargetExternal(1);

    // Parametri del lancio dado
    const dice_type = document.getElementById('dice_type').value;
    const dice_bonus = document.getElementById('dice_bonus').value;
    const dice_malus = document.getElementById('dice_malus').value;

    // Legge i bersagli selezionati da TargetSelector.jsx tramite la callback esposta
    const target = window.getSelectedNamesCallback ? window.getSelectedNamesCallback() : [];
    if (window.setMaxTargetExternal) window.setMaxTargetExternal(1);

    if (target.length != 1 || dice_type == 0) {
        if (window.setMaxTargetExternal) window.setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // Azzera la selezione bersagli
        showNotification('Attenzione! Seleziona una caratteristica e un solo bersaglio', 'warning');
        return;
    }

    // Controllo che ci sia almeno il tipo di dado
    if (dice_type && dice_type != '' && target.length > 0 && target.length < 2) {
        fetch(ns_chat.api_file + '?' + ns_chat.param + '=tiraDadoChat', {
            credentials: "same-origin",
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dice_type, dice_bonus, dice_malus, target })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.closeChatPanel?.();
                    // Esegue refresh della chat
                    } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    } else showNotification('Attenzione! Devi selezionare il bersaglio', 'warning');
}

// Serve per usare un'arma in chat
function usaAttaccoChat() {
    const tipo_attacco = document.getElementById('tipo_attacco').value;
    const arma_body = document.getElementById('arma_body').value;

    // Legge i bersagli selezionati da TargetSelector.jsx
    const target = window.getSelectedNamesCallback ? window.getSelectedNamesCallback() : [];

    if (target.length != 1 || tipo_attacco == 0) {
        if (window.setMaxTargetExternal) window.setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // Azzera la selezione bersagli
        showNotification('Attenzione! Seleziona una tipologia di attacco e un solo bersaglio', 'warning');
        return;
    }

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=usaAttaccoChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo_attacco, arma_body, target })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.closeChatPanel?.();

            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Rimuove il limite di caratteri per la textarea del master
function masterMessageLength(textarea, maxLen) {
    const value = textarea.value;

    // Controlla se il primo carattere è "="
    if (value.length > 0 && value.charAt(0) === '=') {
        // Rimuove l'attributo maxlength
        textarea.removeAttribute('maxlength');
    } else {
        // Ripristina maxlength se non c'è più il "=" iniziale
        if (!textarea.hasAttribute('maxlength')) textarea.setAttribute('maxlength', maxLen);
    }
}

// Funzione per il lancio di una skill in chat
function tiraSkillChat() {
    // Parametri
    const chat_skill = document.getElementById('chat_skill').value;
    const livello_skill = document.getElementById('livello_skill').value;
    // Legge i bersagli selezionati da TargetSelector.jsx
    const target = window.getSelectedNamesCallback ? window.getSelectedNamesCallback() : [];
    const id_role = document.getElementById('id_role').value;

    // Controllo che ci sia la skill e il bersaglio
    if (chat_skill == 0 || target.length > 0) {
        // Chiamata
        fetch(ns_chat.api_file + '?' + ns_chat.param + '=tiraSkillChat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_skill,
                livello_skill,
                target,
                id_role
            }),
            credentials: "same-origin"
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.closeChatPanel?.();
                    // Esegue refresh della chat
                    } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    } else showNotification('Attenzione! Devi selezionare il bersaglio', 'warning');
}

function editAction(content, id) {
    document.getElementById('editAction-modal').style.display = 'block';
    document.getElementById("edit_action_textarea").value = content;
    document.getElementById("edit_action_id").value = id;
}

function saveEditAction() {
    const content = document.getElementById("edit_action_textarea").value;
    const id = document.getElementById("edit_action_id").value;

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=saveEditAction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content, id })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editAction-modal").style.display = "none";
                showNotification(data.message, 'success');
                // Full-refresh locale: il server ha già notificato gli altri via socket chat:edit.
                // Necessario perché il messaggio modificato ha id < lastIdRef, non tornerebbe altrimenti.
                window.clearChat?.();
                window.refreshChat?.();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore modifica azione:', err));
}

// Imposta limite caratteri
function setCharLimit() {
    const charLimit = document.getElementById('caratteri').value;

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=setCharLimit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ charLimit })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.closeChatPanel?.();

            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}
// Revoca il nuovo limite di caratteri se un utente non è d'accordo
function revocaLimiteCaratteri(nuovo_limite, vecchio_limite, luogo, user) {
    if (confirm("Sei sicuro di voler revocare il limite?")) {
        fetch(ns_chat.api_file + '?' + ns_chat.param + '=revocaLimiteCaratteri', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                luogo: luogo,
                vecchio_limite: vecchio_limite,
                nuovo_limite: nuovo_limite,
                user: user
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) showNotification('Limite rimosso', 'success');
                else showNotification(data.message, 'error');
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}

// Usa oggetto in chat
function usaOggettoChat() {
    const objChat = document.getElementById('objChat').value;

    if (objChat == 0) {
        showNotification('Attenzione! Devi selezionare un oggetto.', 'warning');
        return;
    }

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=usaOggettoChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ objChat })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.closeChatPanel?.();

            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Tiro dado generico in chat
function tiraDadoGenericoChat() {
    const dado = document.getElementById('dado').value;
    const bonus_abilita = document.getElementById('dado_bonus_abilita')?.checked ?? false;

    if (dado == 0) {
        showNotification('Attenzione! Devi selezionare un dado.', 'warning');
        return;
    }

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=tiraDadoGenericoChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dado, bonus_abilita })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.closeChatPanel?.();

            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Modifica i parametri del pg in chat
function editMasterPgChat() {
    // Non modificare! - I parametri vengono usati anche in fondo al file
    const note_fato = document.getElementById('note_fato').value;
    const nome_personaggio_hidden = document.getElementById('nome_personaggio_hidden').value;
    const particolari = document.getElementById('particolari').value;
    const salute = document.getElementById('salute').value;
    const integrita = document.getElementById('integrita').value;
    const notorieta = document.getElementById('notorieta').value;
    const soldi = document.getElementById('soldi').value;

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=editMasterPgChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            note_fato,
            nome_personaggio_hidden,
            particolari,
            salute,
            integrita,
            notorieta,
            soldi
        })
    })
        .then(res => res.json())
        .then(data => {
            window.closeChatPanel?.();

        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Crea un png in chat
function newMasterPng() {
    // Non modificare! - I parametri vengono usati anche in fondo al file
    const pngName = document.getElementById('pngNew').value;

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=newMasterPng', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pngName })
    })
        .then(res => res.json())
        .then(data => {
            window.closeChatPanel?.();

            getPngRolePlaying(); // Aggiorna la lista dei png nella sezione master del pannello chat
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Manda l'azione di un png in chat
function newMasterPngAction() {
    // Non modificare! - I parametri vengono usati anche in fondo al file
    const pngName = document.getElementById('pngName').value;
    const pngMessage = document.getElementById('pngMessage').value;
    const pngBonus = document.getElementById('pngBonus').value;
    const pngCar = document.getElementById('pngCar').value;

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=newMasterPngAction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            pngName,
            pngMessage,
            pngBonus,
            pngCar
        })
    })
        .then(res => res.json())
        .then(data => {
            window.closeChatPanel?.();

        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

/*****************************************************************************/
/*********************** Limite caratteri - Grafica **************************/
/*****************************************************************************/
const textarea = document.getElementById('message');
const maxLength = textarea.maxLength;

// Crea una barra di progresso
const progressBar = document.createElement('div');
progressBar.style.cssText = `
    height: 4px;
    background: #e0e0e0;
    margin-top: 5px;
    border-radius: 2px;
    overflow: hidden;
    width: 100%;
`;

const progressFill = document.createElement('div');
progressFill.style.cssText = `
    height: 100%;
    width: 0%;
    background: #4CAF50;
    transition: width 0.3s, background 0.3s;
    border-radius: 2px;
`;
progressBar.appendChild(progressFill);
textarea.parentNode.insertBefore(progressBar, textarea.nextSibling);

textarea.addEventListener('input', function () {
    const length = this.value.length;
    const progress = Math.min(length / maxLength, 1);
    const percentage = progress * 100;

    // Aggiorna la barra di progresso
    progressFill.style.width = `${percentage}%`;

    // Cambia colore in base al progresso
    if (progress < 0.7) {
        progressFill.style.background = '#4CAF50';
        this.style.borderColor = '#4CAF50';
        this.style.boxShadow = `0 0 ${progress * 10}px rgba(76, 175, 80, ${0.3 + progress * 0.4})`;
    } else if (progress < 0.9) {
        progressFill.style.background = '#FF9800';
        this.style.borderColor = '#FF9800';
        this.style.boxShadow = `0 0 ${progress * 15}px rgba(255, 152, 0, ${0.5 + progress * 0.3})`;
    } else {
        progressFill.style.background = '#f44336';
        this.style.borderColor = '#f44336';
        this.style.boxShadow = `0 0 ${progress * 20}px rgba(244, 67, 54, ${0.6 + progress * 0.3})`;

        // Effetto vibrante quando si avvicina al limite
        if (progress > 0.95) {
            progressFill.style.animation = 'vibrate 0.1s infinite';
        }
    }
});
// Aggiungi animazione per la vibrazione
const vibrateStyle = document.createElement('style');
vibrateStyle.textContent = `
    @keyframes vibrate {
        0% { transform: translateX(0); }
        25% { transform: translateX(-1px); }
        75% { transform: translateX(1px); }
        100% { transform: translateX(0); }
    }
`;
document.head.appendChild(vibrateStyle);

/*****************************************************************************/
/*********************** PANNELLO CHAT ***************************************/
/*****************************************************************************/
// Gestione apertura/chiusura modale principale.
// Il pannello GDR è gestito da React (ChatShell.jsx via window.openChatPanel /
// window.closeChatPanel). I listener legacy qui sotto sono rimasti come
// fallback ma usano le callback React per non bypassare lo state.
// Il listener su openPanelBtn è rimosso: React gestisce onClick direttamente.
var _gdrCloseBtn = document.getElementById('gdrCloseBtn');
if (_gdrCloseBtn) _gdrCloseBtn.addEventListener('click', function () {
    window.closeChatPanel?.();
});

// Gestione Tabs: rimossa — i tab del pannello GDR sono gestiti da React
// (setActiveTab in ChatShell.jsx). Il listener precedente chiamava
// document.getElementById(`${tabId}-tab`) su un data-tab inesistente nei
// div React, causando un TypeError che rimuoveva la classe active da tutti
// i tab-content lasciando il pannello vuoto.

// Contatore caratteri per azione PNG
document.getElementById('message').addEventListener('input', function () {
    const charCount = this.value.length;
    this.parentNode.querySelector('.gdr-char-counter').textContent = `Caratteri: ${charCount}/${maxLength}`;
});

/**
 * Recupero i nomi dei bersagli selezionati dall'utente.
 *
 * La funzione legge window.getSelectedNamesCallback, esposta da TargetSelector.jsx
 * (bundle Vite) tramite un ref sempre aggiornato alla selezione corrente.
 *
 * @returns {string[]} Array di nomi dei personaggi selezionati, o array vuoto se nessuno
 */
function getSelectedUserNames() {
    // window.getSelectedNamesCallback viene impostato da TargetSelector.jsx al mount
    if (window.getSelectedNamesCallback) return window.getSelectedNamesCallback();
    return [];
}

// Funzione per verificare se la selezione è valida rispetto al limite
function isSelectionValid() {
    const selected = getSelectedUserNames();

    if (maxTarget > 0 && selected.length > maxTarget) return false;

    return true;
}

/*********************** Aggiorno il livello massimo della skill ***************************************/
function aggiornaLivelli() {
    const livelloSelect = document.getElementById('livello_skill');
    const skillSelect = document.getElementById('chat_skill');
    const selectedOption = skillSelect.options[skillSelect.selectedIndex];

    livelloSelect.innerHTML = ''; // Reset
    const maxLevel = parseInt(selectedOption.getAttribute('data-max-level')) || 1; // Ottieni il livello massimo dal data-attribute
    // Imposta il limite bersagli a 1 di default quando si cambia skill
    if (window.setMaxTargetExternal) window.setMaxTargetExternal(1);

    // Popola con i livelli disponibili
    for (let i = 1; i <= maxLevel; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        if (i === 1) option.selected = true;
        livelloSelect.appendChild(option);
    }

    // Se l'utente aveva selezionato un livello maggiore del nuovo massimo, reimposta a 1
    if (parseInt(livelloSelect.value) > maxLevel) livelloSelect.value = 1;
}

function aggiornaLimiteDaLivello() {
    const livelloSelect = document.getElementById('livello_skill');
    const livelloSelezionato = parseInt(livelloSelect.value) || 1;

    // Aggiorna il limite bersagli in base al livello skill selezionato
    if (window.setMaxTargetExternal) window.setMaxTargetExternal(livelloSelezionato);
}

/********************   Rileva quando l'utente sta per lasciare la chat *********************************/
let uscitaRilevata = false;
let isInRole = false;

// Rileva quando il mouse esce dalla finestra verso l'alto (intenzione di uscire)
document.addEventListener('mouseleave', function (event) {
    console.log('Mouse leave event:', isInRole);
    // Controlla se il mouse sta uscendo dal bordo superiore
    if (!uscitaRilevata && event.clientY < 0 && isInRole) {
        uscitaRilevata = true;

        // Evidenzia la zona per 2 secondi
        evidenziaZona();

        // Resetta il flag dopo 3 secondi (per permettere future rilevazioni)
        setTimeout(() => { uscitaRilevata = false; }, 4300);
    }
});

// Opzionale: rileva anche quando l'utente sta per chiudere la tab
// (basato sul movimento del mouse verso l'alto)
document.addEventListener('mousemove', function (event) {
    console.log('Mouse move event:', isInRole);
    if (!uscitaRilevata && event.clientY <= 5 && isInRole) { // Mouse vicino al bordo superiore
        uscitaRilevata = true;
        evidenziaZona();

        setTimeout(() => { uscitaRilevata = false; }, 4300);
    }
});

function evidenziaZona() {
    // Seleziona la zona da evidenziare
    const zona = document.querySelector('.fa-power-off');

    if (zona) {
        // Salva stili originali
        const stiliOriginali = {
            transition: zona.style.transition,
            backgroundColor: zona.style.backgroundColor,
            boxShadow: zona.style.boxShadow,
            transform: zona.style.transform
        };

        // Applica evidenziazione
        zona.style.transition = 'all 0.3s ease';
        zona.style.backgroundColor = '#ffff99';
        zona.style.boxShadow = '0 0 25px 5px #ffaa00';
        zona.style.transform = 'scale(1.02)';

        // Crea un piccolo indicatore visivo che dice "Aspetta!"
        const indicatore = document.createElement('div');
        indicatore.textContent = '⚡Se esci, chiudi la role!⚡';
        indicatore.style.position = 'fixed';
        indicatore.style.top = '100px';
        indicatore.style.right = '300px';
        indicatore.style.fontSize = '80px';
        indicatore.style.animation = 'ping 1s infinite';
        indicatore.style.zIndex = '9999';
        document.body.appendChild(indicatore);

        // Aggiungi animazione
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ping {
                0% { transform: scale(1); opacity: 1; }
                70% { transform: scale(1.3); opacity: 0.7; }
                100% { transform: scale(1); opacity: 1; }
            }
        `;
        document.head.appendChild(style);

        // Rimuovi tutto dopo 2 secondi
        setTimeout(() => {
            zona.style.transition = stiliOriginali.transition;
            zona.style.backgroundColor = stiliOriginali.backgroundColor;
            zona.style.boxShadow = stiliOriginali.boxShadow;
            zona.style.transform = stiliOriginali.transform;

            if (indicatore && indicatore.parentNode) indicatore.parentNode.removeChild(indicatore);
        }, 4000);
    }
}
/********************   FINE    rilevamento *********************************/

/*****************************************************************************/
/*********************** CARICAMENTO DOM *************************************/
/*****************************************************************************/

/**
 * Attacca tutti gli event listener del DOM della chat.
 * Esposta globalmente come window.initChatListeners() per essere richiamata
 * da ChatShell.jsx in navigazione SPA, dove DOMContentLoaded è già scattato
 * e il DOM React non era ancora presente.
 */
window.initChatListeners = function () {
    // null-check obbligatorio: in navigazione SPA questo può essere chiamato
    // anche da pagine che non hanno ancora renderizzato il pannello GDR
    var chat_skill = document.getElementById('chat_skill');
    var livello_skill = document.getElementById('livello_skill');
    // removeEventListener+addEventListener su funzioni nominate: idempotente
    if (chat_skill)    { chat_skill.removeEventListener('change', aggiornaLivelli);          chat_skill.addEventListener('change', aggiornaLivelli); }
    if (livello_skill) { livello_skill.removeEventListener('change', aggiornaLimiteDaLivello); livello_skill.addEventListener('change', aggiornaLimiteDaLivello); }

    // Assegnazione .on* invece di addEventListener: idempotente (sostituisce invece di accumulare)
    var chat_message = document.getElementById("message");
    if (chat_message) chat_message.onkeydown = function (event) { if (event.key === "Enter" && !event.shiftKey) sendChatMessage(); };

    var chat_form = document.getElementById("chat_form_messages");
    if (chat_form) chat_form.onsubmit = function (event) { event.preventDefault(); sendChatMessage(); };

    // Apertura finestra di scrittura libera.
    // Prima si usava window.open("", ...) + document.write() per iniettare l'HTML in una
    // finestra vuota: pattern fragile, spesso bloccato/svuotato da estensioni anti-popup
    // (è lo stesso schema usato dai popup pubblicitari invasivi) e da document.write, ormai
    // deprecato. Ora si naviga direttamente verso una pagina statica reale.
    var btn_scritturaLibera = document.getElementById("gdrOpenTextareaButton");
    if (btn_scritturaLibera) {
        btn_scritturaLibera.onclick = function (e) {
            e.preventDefault();
            window.open("/themes/crystal/scrittura_libera.html", "ScritturaLiberaPopup", "width=600,height=800,resizable=yes,scrollbars=yes");
        }
    }

    // Script per gestire la nuova modale
    var customImg = document.getElementById("helpImg");
    var customSpan = document.getElementsByClassName("custom-close")[0];
    var chatPanel = document.getElementById("chatPanel");

    if (customImg) customImg.onclick = function () { window.openChatPanel?.(); }
    if (customSpan) customSpan.onclick = function () { window.closeChatPanel?.(); }

    window.onclick = function (event) { if (event.target == chatPanel) window.closeChatPanel?.(); }

    // Apertura prima tab: non necessario, React inizializza activeTab = 'dice' di default.

    // Apre popup per parametri 
    /*
    var formSelectPg = document.getElementById('selezionaPersonaggioForm');
    var popupEditMaster = document.getElementById('modificaParametri');
    var nomePersonaggioHidden = document.getElementById('nome_personaggio_hidden');

    if (formSelectPg) {
        formSelectPg.addEventListener('submit', function (event) {
            event.preventDefault();

            // Ottieni il nome del personaggio selezionato
            var nomePersonaggio = document.getElementById('nome_personaggio').value;

            // Imposta il nome del personaggio nel campo nascosto
            nomePersonaggioHidden.value = nomePersonaggio;

            // Richiedi i dati del personaggio via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../pages/personaggio_data.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Parse dei dati JSON ricevuti
                    var data = JSON.parse(xhr.responseText);

                    // Riempie i campi del modulo con i dati ricevuti
                    document.getElementById('note_fato').value = data.note_fato;
                    document.getElementById('particolari').value = data.particolari;
                    document.getElementById('salute').value = data.salute;
                    document.getElementById('integrita').value = data.integrita;
                    document.getElementById('notorieta').value = data.notorieta;
                    document.getElementById('soldi').value = data.soldi;
                }
            };
            xhr.send('nome_personaggio=' + encodeURIComponent(nomePersonaggio));

            // Mostra il popup
            popupEditMaster.style.display = 'block';
        });
    }
    */
    /***********    FINE    GESTIONE CHAT di gioco  *********************/
};

// Al primo caricamento PHP la funzione viene chiamata automaticamente da DOMContentLoaded.
// In navigazione SPA, ChatShell.jsx la chiama esplicitamente dopo aver montato il DOM.
document.addEventListener('DOMContentLoaded', window.initChatListeners);