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
        fetch('pages/ajax_engine.php?op=pulisciChat')
            .then(res => res.json())
            .then(data => {
                const chatContainer = document.getElementById('pagina_chat');
                if (chatContainer) chatContainer.innerHTML = ''; // Svuota la chat

                // Esegue refresh della chat
                if (window.refreshChat) window.refreshChat();
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Ruoli apicali cancellano la chat di gioco
async function curaPg() {
    fetch('pages/ajax_engine.php?op=curaPg')
        .then(res => res.json())
        .then(data => { if (window.refreshChat) window.refreshChat(); })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Applica la funzione di debounce al pulsante del back_chat
function toggleBackChat(a) {
    const img = a.querySelector("img"); // recupera l'immagine dentro <a>

    fetch('pages/ajax_engine.php?op=setBackChat')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                img.src = "themes/crystal/imgs/chat/" + data.image;
                if (data.title) img.title = data.title;

                if (window.refreshChat) window.refreshChat();
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
        const response = await fetch("/pages/ajax_engine.php?op=new_chat_message", {
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

            // Ripristina il tag salvato in sessione, se necessario
            if (form.elements["tag"]) form.elements["tag"].value = result.tag ?? "";

            // Esegue refresh della chat
            if (window.refreshChat) window.refreshChat();
        } else showNotification(result.message, 'error');
    } catch (err) {
        console.error("💥 Errore nell’invio del messaggio:", err);
    }
}

// Load chat di gioco
const e = React.createElement;

// Load messaggi in chat /**************************************************************************************************************************************** */
function ChatViewer() {
    const [lastId, setLastId] = React.useState(0);

    // Funzione per recuperare i messaggi
    const fetchMessages = () => {
        fetch('pages/ajax_engine.php?op=get_chat_messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ last: lastId })
        })
            .then(res => res.json())
            .then(data => {
                if (data && data.status === 'ok' && Array.isArray(data.messages)) {
                    const chatContainer = document.getElementById('pagina_chat');
                    if (chatContainer) {
                        document.getElementById('id_role').value = data.activeRole; // Imposto l'eventuale id della role in un campo nascosto della chat
                        gdrSetSessionActive(data.activeRole); // Aggiorna lo stato della sessione di gioco
                        data.messages.forEach(msg => { if (msg.html) chatContainer.innerHTML += msg.html; }); // Aggiungo i nuovi messaggi in chat
                        if (data.charLimit != null && data.charLimit > 0) document.getElementById('message').maxLength = data.charLimit; // Aggiorna il limite di caratteri
                        document.getElementById('quitRole').style.display = data.canQuit ? 'block' : 'none'; // Mostra o nasconde il pulsante di uscita dalla role
                        document.getElementById('openPanelBtn').style.display = data.canUsePanel ? 'block' : 'none'; // Mostra o nasconde il pulsante di apertura del pannello chat
                        document.getElementById('pgRolePlaying').style.display = data.activeRole ? 'block' : 'none'; // Mostra o nasconde il pannello con l'elenco degli utenti giocanti
                        document.getElementById('addPgToRoleBtn').style.display = data.canQuit ? 'none' : 'block'; // Mostra o nasconde il pulsante per avviare o aggiungersi alla role
                        // Aggiorna l'ultimo ID (prende l'ultimo messaggio disponibile)
                        const lastMessage = data.messages[data.messages.length - 1];
                        if (lastMessage) setLastId(parseInt(lastMessage.id, 10));
                        if (data.messages.length > 0) setTimeout(() => { chatContainer.scrollTop = chatContainer.scrollHeight; }, 100); // Scroll automatico
                        // Riproduco l'auDIO
                        const audio = new Audio('../sounds/beep.wav');
                        if (data.play === true) audio.play().catch(e => console.log('Audio error:', e));
                    }
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    };

    // Recupera i messaggi ogni 5 secondi
    React.useEffect(() => {
        fetchMessages();
        const interval = setInterval(fetchMessages, 8000);
        return () => clearInterval(interval);
    }, [lastId]);

    // espone la funzione per richiami esterni
    React.useEffect(() => {
        window.refreshChat = fetchMessages;
    }, [fetchMessages]);

    return e('div', { className: 'chat_inner' }, null);
}

// Funzione per il lancio di un dado in chat
function tiraDadoChat() {
    if (setMaxTargetExternal) setMaxTargetExternal(1); // AGGIORNA il limite dei bersagli consentiti

    // Parametri
    const dice_type = document.getElementById('dice_type').value;
    const dice_bonus = document.getElementById('dice_bonus').value;
    const dice_malus = document.getElementById('dice_malus').value;
    const target = getSelectedNamesCallback();
    const id_role = document.getElementById('id_role').value;
    setMaxTargetExternal(1);

    if (target.length != 1 || dice_type == 0) {
        setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // AGGIORNA il limite dei bersagli consentiti
        showNotification('Attenzione! Seleziona una caratteristica e un solo bersaglio', 'warning');
        return;
    }

    // Controllo che ci sia almeno il tipo di dado
    if (dice_type && dice_type != '' && target.length > 0 && target.length < 2) {
        fetch('pages/ajax_engine.php?op=tiraDadoChat', {
            credentials: "same-origin",
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                dice_type,
                dice_bonus,
                dice_malus,
                target,
                id_role
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("chatPanel").style.display = "none";
                    // Esegue refresh della chat
                    if (window.refreshChat) window.refreshChat();
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    } else showNotification('Attenzione! Devi selezionare il bersaglio', 'warning');
}

// Funzione per il lancio di una skill in chat
function tiraSkillChat() {
    // Parametri
    const chat_skill = document.getElementById('chat_skill').value;
    const livello_skill = document.getElementById('livello_skill').value;
    const target = getSelectedNamesCallback();
    const id_role = document.getElementById('id_role').value;

    // Controllo che ci sia la skill e il bersaglio
    if (chat_skill == 0 || target.length > 0) {
        // Chiamata
        fetch('pages/ajax_engine.php?op=tiraSkillChat', {
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
                    document.getElementById("chatPanel").style.display = "none";
                    // Esegue refresh della chat
                    if (window.refreshChat) window.refreshChat();
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    } else showNotification('Attenzione! Devi selezionare il bersaglio', 'warning');
}

// Serve per usare un'arma in chat
function usaArmaChat() {
    const arma_weapon = document.getElementById('arma_weapon').value;
    const arma_body = document.getElementById('arma_body').value;
    const target = getSelectedNamesCallback();

    if (target.length != 1 || arma_weapon == 0) {
        setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // AGGIORNA il limite dei bersagli consentiti
        showNotification('Attenzione! Seleziona un\'arma e un solo bersaglio', 'warning');
        return;
    }

    fetch('pages/ajax_engine.php?op=usaArmaChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ arma_weapon, arma_body, target })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chatPanel").style.display = "none";

                if (window.refreshChat) window.refreshChat();
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

function editAction(content, id) {
    document.getElementById('editAction-modal').style.display = 'block';
    document.getElementById("edit_action_textarea").value = content;
    document.getElementById("edit_action_id").value = id;
}

function saveEditAction() {
    const content = document.getElementById("edit_action_textarea").value;
    const id = document.getElementById("edit_action_id").value;

    fetch('pages/ajax_engine.php?op=saveEditAction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content, id })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("editAction-modal").style.display = "none";

                if (window.refreshChat) window.refreshChat();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Imposta limite caratteri
function setCharLimit() {
    const charLimit = document.getElementById('caratteri').value;

    fetch('pages/ajax_engine.php?op=setCharLimit', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ charLimit })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chatPanel").style.display = "none";

                if (window.refreshChat) window.refreshChat();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}
// Revoca il nuovo limite di caratteri se un utente non è d'accordo
function revocaLimiteCaratteri(nuovo_limite, vecchio_limite, luogo, user) {
    if (confirm("Sei sicuro di voler revocare il limite?")) {
        fetch('/pages/ajax_engine.php?op=revocaLimiteCaratteri', {
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

    fetch('pages/ajax_engine.php?op=usaOggettoChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ objChat })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chatPanel").style.display = "none";

                if (window.refreshChat) window.refreshChat();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Tiro dado generico in chat
function tiraDadoGenericoChat() {
    const dado = document.getElementById('dado').value;

    if (dado == 0) {
        showNotification('Attenzione! Devi selezionare un dado.', 'warning');
        return;
    }

    fetch('pages/ajax_engine.php?op=tiraDadoGenericoChat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dado })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("chatPanel").style.display = "none";

                if (window.refreshChat) window.refreshChat();
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

    fetch('pages/ajax_engine.php?op=editMasterPgChat', {
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
            document.getElementById("chatPanel").style.display = "none";

            if (window.refreshChat) window.refreshChat();
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Crea un png in chat
function newMasterPngChat() {
    // Non modificare! - I parametri vengono usati anche in fondo al file
    const pngName = document.getElementById('pngName').value;
    const pngMessage = document.getElementById('pngMessage').value;
    const pngBonus = document.getElementById('pngBonus').value;
    const pngCar = document.getElementById('pngCar').value;

    fetch('pages/ajax_engine.php?op=newMasterPngChat', {
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
            document.getElementById("chatPanel").style.display = "none";

            if (window.refreshChat) window.refreshChat();
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Allungo il turno perché l'ultimo utente che invia ha scelto di lanciare un attacco
function closeTurn(id_role, suss_id) {
    // Rimuovo 5 messaggi dal sussurro in poi
    if (suss_id > 0 && document.getElementById(suss_id)) {
        for (let i = suss_id; i < (suss_id + 5); i++) {
            if (document.getElementById(i)) document.getElementById(i).remove();
        }
    }

    fetch('pages/ajax_engine.php?op=closeTurn', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_role, suss_id })
    })
        .then(res => res.json())
        .then(data => { if (window.refreshChat) window.refreshChat(); })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Lancio lo scudo prima di chiudere sicuramente il turno
function lanciaScudo(id_role, yes, suss_id) {
    const removeSuss = document.getElementById(suss_id);
    if (suss_id > 0 && removeSuss) removeSuss.remove();

    fetch('pages/ajax_engine.php?op=lanciaScudo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_role, yes, suss_id })
    });
}

// Animazioni CSS aggiuntive
const additionalStyles = `
    @keyframes user-search-popup__slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes user-search-popup__slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }

    .user-search-popup__autocomplete-item--error {
        color: #ef4444 !important;
        font-style: italic;
    }

    .user-search-popup__autocomplete-item--empty {
        color: #94a3b8 !important;
        font-style: italic;
        cursor: default !important;
    }

    .user-search-popup__autocomplete-item--empty:hover {
        background: #1a1f36 !important;
    }

    .user-search-popup__autocomplete-item--hover {
        background: #252a45 !important;
        transform: translateX(4px);
        transition: all 0.2s ease;
    }
`;
// Aggiungi gli stili aggiuntivi al documento
if (!document.querySelector('#user-search-popup-additional-styles')) {
    const styleEl = document.createElement('style');
    styleEl.id = 'user-search-popup-additional-styles';
    styleEl.textContent = additionalStyles;
    document.head.appendChild(styleEl);
}

/*****************************************************************************/
/************* Serve per cercare gli utenti in fase di creazione role ********/
/*****************************************************************************/
class pgRolePlayingPanel {
    constructor() {
        this.isOpen = false;
        this.init();
    }

    init() {
        // Elementi DOM con classi incapsulate
        this.elements = {
            popupPanel: document.getElementById('pgRolePlayingPanel'),
            openBtn: document.getElementById('pgRolePlaying'),
            closeBtn: document.getElementById('closePopupAdd'),
        };

        this.bindEvents();
    }

    bindEvents() {
        // Apertura popup
        if (this.elements.openBtn) this.elements.openBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.openPopup();
        });

        // Chiusura popup
        this.elements.closeBtn.addEventListener('click', () => this.closePanel());

        // Click outside per chiudere
        this.elements.popupPanel.addEventListener('click', (e) => { if (e.target === this.elements.popupPanel) this.closePanel(); });

        // Keyboard events globali
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && this.isOpen) this.closePanel(); });
    }

    openPopup() {
        this.elements.popupPanel.style.display = 'flex';
        this.isOpen = true;
        document.body.style.overflow = 'hidden'; // Blocca scroll body

        getPgRolePlaying(); // Carica gli utenti
    }

    closePanel() {
        this.elements.popupPanel.style.display = 'none';
        this.isOpen = false;
        document.body.style.overflow = ''; // Ripristina scroll body
    }

    // Metodo per distruggere l'istanza e pulire gli event listeners
    destroy() {
        if (this.searchTimeout) clearTimeout(this.searchTimeout);

        // Rimuovi tutti gli event listeners
        this.elements.openBtn.removeEventListener('click', this.openPopup);
        this.elements.closeBtn.removeEventListener('click', this.closePanel);
        this.elements.cancelBtn.removeEventListener('click', this.closePanel);
    }
}

function addPgToRole() {
    if (confirm("Sei sicuro di voler entrare?")) {
        fetch('pages/ajax_engine.php?op=addPgToRole')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.refreshChat) window.refreshChat();
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Pg esce dalla role
function quitRole(user) {
    if (confirm("Sei sicuro di voler espellere " + user + " dalla role?")) {
        fetch('pages/ajax_engine.php?op=quitRole', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.refreshChat) window.refreshChat();

                    // Chiudi il popup dopo successo
                    document.getElementById('closePopup').style.display = 'none';
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

/*****************************************************************************/
/*********************** Pannello utenti giocanti ****************************/
/*****************************************************************************/
function closePgRolePlayingPanel() {
    document.getElementById('pgRolePlayingPanel').style.display = 'none';
}

// Carica gli utenti
function getPgRolePlaying() {
    fetch('pages/ajax_engine.php?op=getPgRolePlaying')
        .then(res => res.json())
        .then(data => {
            const pgRolePlayingList = document.getElementById('pgRolePlayingList');
            const users = data.users || [];

            pgRolePlayingList.innerHTML = '';

            users.forEach(user => {
                const row = document.createElement('div');
                row.style.cssText = 'display: grid; grid-template-columns: 1fr 60px 80px; gap: 8px; align-items: center; padding: 6px 8px; background-color: rgba(30, 40, 60, 0.3); border-radius: 4px; margin-bottom: 3px; font-size: 0.9rem;';

                // Nome
                const nameCol = document.createElement('div');
                nameCol.textContent = user.name;
                nameCol.style.cssText = 'color: #e0e0e0;';

                // Spia luminosa
                const statusCol = document.createElement('div');
                statusCol.style.cssText = 'text-align: center;';
                const dot = document.createElement('div');
                dot.style.cssText = `width: 10px; height: 10px; border-radius: 50%; display: inline-block; background-color: ${user.inRole ? '#27ae60' : '#e74c3c'}; box-shadow: 0 0 ${user.inRole ? '8px' : '0'} ${user.inRole ? '#27ae60' : 'transparent'};`;
                if (user.inRole) {
                    dot.style.animation = 'pulse 2s infinite';
                }
                statusCol.appendChild(dot);

                // Pulsante
                const actionCol = document.createElement('div');
                actionCol.style.cssText = 'text-align: center;';
                const button = document.createElement('button');
                button.textContent = user.inRole ? 'Espelli' : 'Uscito';
                button.style.cssText = 'background: ' + (user.inRole ? '#27ae60' : '#7f8c8d') + '; color: white; border: none; border-radius: 4px; padding: 4px 8px; font-size: 0.8rem; cursor: ' + (user.inRole ? 'pointer' : 'not-allowed') + '; width: 70px;';
                button.disabled = !user.inRole;

                if (user.inRole) button.onclick = function () { quitRole(user.name); };
                if (data.canQuit) actionCol.appendChild(button);

                row.appendChild(nameCol);
                row.appendChild(statusCol);
                row.appendChild(actionCol);
                pgRolePlayingList.appendChild(row);
            });
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Stile per l'animazione della spia
const stylePginRole = document.createElement('style');
stylePginRole.textContent = `
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(39, 174, 96, 0); }
        100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0); }
    }
`;
document.head.appendChild(stylePginRole);

/*****************************************************************************/
/*********************** Session role status *********************************/
/*****************************************************************************/
function gdrSetSessionActive(isActive) {
    const statusElement = document.getElementById('gdrSessionStatus');
    const roleInProgress = document.getElementById('roleInProgress');
    const addPgToRoleBtn = document.getElementById('addPgToRoleBtn');

    if (isActive) {
        statusElement.classList.add('active');
        statusElement.classList.remove('inactive');
        addPgToRoleBtn.textContent = ' Join!';
        roleInProgress.style.display = 'block';
    } else {
        statusElement.classList.add('inactive');
        statusElement.classList.remove('active');
        addPgToRoleBtn.textContent = ' Avvia!';
        roleInProgress.style.display = 'none';
    }
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
// Gestione apertura/chiusura modale principale
document.getElementById('openPanelBtn').addEventListener('click', function () {
    initUserSelectionBox(); // Carico i bersagli possibili
    document.getElementById('chatPanel').style.display = 'flex';
});

document.getElementById('gdrCloseBtn').addEventListener('click', function () {
    document.getElementById('chatPanel').style.display = 'none';
});

// Gestione Tabs
document.querySelectorAll('.gdr-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        // Rimuovi classe active da tutti i tab
        document.querySelectorAll('.gdr-tab').forEach(t => {
            t.classList.remove('active');
        });

        // Aggiungi classe active al tab cliccato
        tab.classList.add('active');

        // Nascondi tutti i contenuti
        document.querySelectorAll('.gdr-tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Mostra il contenuto corrispondente
        const tabId = tab.getAttribute('data-tab');
        document.getElementById(`${tabId}-tab`).classList.add('active');
    });
});

// Contatore caratteri per azione PNG
document.getElementById('message').addEventListener('input', function () {
    const charCount = this.value.length;
    this.parentNode.querySelector('.gdr-char-counter').textContent = `Caratteri: ${charCount}/${maxLength}`;
});

/******************************** Selezione bersagli *******************************************/
async function getRolePgs() {
    const id_role = document.getElementById('id_role').value;

    try {
        const response = await fetch('pages/ajax_engine.php?op=getRolePgs', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_role })
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Errore nel caricamento utenti:', error);
        return [];
    }
}

// VARIABILE per memorizzare la funzione di callback
let getSelectedNamesCallback = null;
let setMaxTargetExternal = null; // Limite massimo di bersagli

function UserSelectionBox() {
    const [selectedNames, setSelectedNames] = React.useState([]);
    const [users, setUsers] = React.useState([]);
    const [maxTarget, setMaxTarget] = React.useState(1);
    const selectedNamesRef = React.useRef(selectedNames);
    React.useEffect(() => { selectedNamesRef.current = selectedNames; }, [selectedNames]); // Aggiorna il ref ogni volta che selectedNames cambia

    // Esponi la funzione setMaxTarget all'esterno
    React.useEffect(() => {
        setMaxTargetExternal = (nuovoLimite) => {
            setMaxTarget(nuovoLimite); // Imposto il nuovo limite

            // Riduco i bersagli selezionati, se sono più di quelli consentiti dal nuovo limite
            setTimeout(() => {
                const currentSelected = selectedNamesRef.current;

                if (nuovoLimite > 0 && currentSelected.length > nuovoLimite) {
                    console.log(`Riduco selezione da ${currentSelected.length} a ${nuovoLimite}`);
                    const nuovaSelezione = currentSelected.slice(0, nuovoLimite);
                    setSelectedNames(nuovaSelezione);

                    // Mostra un messaggio all'utente
                    if (nuovaSelezione.length < currentSelected.length) {
                        setMaxTargetExternal = null;
                        alert(`Limite ridotto a ${nuovoLimite}. Sono stati rimossi ${currentSelected.length - nuovaSelezione.length} bersagli: ${currentSelected.slice(nuovoLimite).join(', ')}`);
                        return false;
                    }
                }
            }, 0);
        };
        return () => { setMaxTargetExternal = null; }; // Pulisco
    }, []);

    // Carica i dati all'avvio
    React.useEffect(() => { getRolePgs().then(data => { setUsers(data.users); }); }, []);

    const handleToggle = (userName) => {
        setSelectedNames(prev => {
            // Se già selezionato, rimuovi
            if (prev.includes(userName)) return prev.filter(name => name !== userName);

            // Se c'è un limite e lo superiamo, non aggiungere
            if (maxTarget > 0 && prev.length >= maxTarget) {
                alert(`Puoi selezionare al massimo ${maxTarget} utenti`);
                return prev;
            }

            // Altrimenti aggiungi
            return [...prev, userName];
        });
    };

    // Esponi la funzione per ottenere i nomi selezionati
    React.useEffect(() => {
        getSelectedNamesCallback = () => selectedNames;
    }, [selectedNames]);

    if (!users || users.length === 0) return;

    return React.createElement('div', null,
        // Mostra il limite se esiste
        maxTarget > 0 && React.createElement('div', {
            style: {
                marginBottom: '10px',
                padding: '5px 10px',
                backgroundColor: '#fff3cd',
                border: '1px solid #ffeaa7',
                borderRadius: '4px',
                fontSize: '13px',
                color: '#856404'
            }
        }, `📝 Selezionati: ${selectedNames.length}` +
        (maxTarget > 0 ? ` / ${maxTarget}` : '')),

        // Lista utenti
        users.map((userName) => {
            const isSelected = selectedNames.includes(userName);
            const isDisabled = maxTarget > 0 &&
                !isSelected &&
                selectedNames.length >= maxTarget;

            return React.createElement('div', {
                key: userName,
                style: {
                    padding: '8px',
                    margin: '5px 0',
                    backgroundColor: isSelected ? '#3a4f86' :
                        isDisabled ? '#f8f9fa' : '#ffffff1a',
                    border: '1px solid ' + (isSelected ? '#007bff' : '#dee2e6'),
                    borderRadius: '4px',
                    cursor: isDisabled ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    color: isSelected ? 'white' :
                        isDisabled ? '#adb5bd' : 'inherit',
                    opacity: isDisabled ? 0.6 : 1
                },
                onClick: isDisabled ? null : () => handleToggle(userName)
            },
                React.createElement('div', {
                    style: {
                        width: '18px',
                        height: '18px',
                        border: '2px solid ' + (isSelected ? 'white' :
                            isDisabled ? '#dee2e6' : '#adb5bd'),
                        borderRadius: '3px',
                        marginRight: '10px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        backgroundColor: isSelected ? '#007bff' :
                            isDisabled ? '#e9ecef' : 'white',
                        color: 'white',
                        fontSize: '12px'
                    }
                }, isSelected ? '✓' : ''),
                userName
            );
        })
    );
}

// Funzione per inizializzare React
function initUserSelectionBox() {
    const container = document.getElementById('user-selection-box');

    if (ReactDOM.createRoot) {
        try {
            const root = ReactDOM.createRoot(container);
            root.render(React.createElement(UserSelectionBox));
        } catch (error) {
            container.innerHTML = '<div style="color: red; padding: 20px;">Errore React</div>';
        }
    }
}

// Recupero i pg selezionati
function getSelectedUserNames() {
    if (getSelectedNamesCallback) return getSelectedNamesCallback();

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
    if (setMaxTargetExternal) setMaxTargetExternal(1); // Imposta di default a 1

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

    if (setMaxTargetExternal) setMaxTargetExternal(livelloSelezionato);
}

/*****************************************************************************/
/*********************** CARICAMENTO DOM *************************************/
/*****************************************************************************/
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('chat_skill').addEventListener('change', aggiornaLivelli); // Se cambio skill, aggiorno il suo livello massimo consentito per il pg
    document.getElementById('livello_skill').addEventListener('change', aggiornaLimiteDaLivello); // Se cambio il livello skill, aggiorno il limite di bersagli

    // new UserSearchPopup();  // Inizializza la ricerca utenti da aggiungere alla role
    new pgRolePlayingPanel();  // Inizializza l'elenco dei pg giocanti

    // LOAD DELLA CHAT DI GIOCO
    const container = document.getElementById('pagina_chat');
    if (container) ReactDOM.render(e(ChatViewer), container);

    // Il campo è una textarea, quindi devo catturare l'evento scatenato dal pulsante "Enter" della tastiera
    var chat_message = document.getElementById("message");
    if (chat_message) chat_message.addEventListener("keydown", function (event) { if (event.key === "Enter" && !event.shiftKey) sendChatMessage(); });

    // Devo catturare l'evento "submit" del form di invio azione al click su invia
    var chat_form = document.getElementById("chat_form_messages");
    if (chat_form) {
        chat_form.addEventListener("submit", function (event) {
            event.preventDefault();
            sendChatMessage();
        });
    }

    // Apertura funestra di scrittura libera
    var btn_scritturaLibera = document.getElementById("gdrOpenTextareaButton");
    if (btn_scritturaLibera) {
        btn_scritturaLibera.onclick = function () {
            var popupFreeWrite = window.open("", "ScritturaLiberaPopup", "width=600,height=800,resizable=yes,scrollbars=yes");

            popupFreeWrite.document.write(
                '<!DOCTYPE html>' +
                '<html lang="it">' +
                '<head>' +
                '<title>Scrittura Libera</title>' +
                '<style>' +
                'body {' +
                'font-family: Arial, sans-serif;' +
                'margin: 0;' +
                'padding: 20px;' +
                'background-color: #1a2240;' +
                'color: #b4b6bf;' +
                'height: 100vh;' +
                '}' +
                'textarea {' +
                'width: 100%;' +
                'height: 90vh;' +
                'padding: 10px;' +
                'border: 1px solid #070a1b;' +
                'background-color: #161827;' +
                'color: white;' +
                'resize: none;' +
                'font-size: 14px;' +
                'box-sizing: border-box;' +
                '}' +
                'h3 {' +
                'margin-top: 0;' +
                '}' +
                '</style>' +
                '</head>' +
                '<body>' +
                '<h3>Scrittura Libera</h3>' +
                '<textarea id="message" placeholder="Scrivi qui..." name="message"></textarea><br>' +
                '<span class="char_count">Hai usato <span id="rimanenti">0</span> caratteri</span>' +
                '</body>' +
                '</html>'
            );

            // Attendi che il popup sia completamente caricato
            popupFreeWrite.document.close();

            // Funzione per il conteggio dei caratteri nel popup
            popupFreeWrite.document.getElementById('message').onkeyup = function () {
                var textLength = popupFreeWrite.document.getElementById('message').value.length;
                popupFreeWrite.document.getElementById('rimanenti').textContent = textLength;
            }
        }
    }

    // Script per gestire la nuova modale
    var customImg = document.getElementById("helpImg");
    var customSpan = document.getElementsByClassName("custom-close")[0];
    var chatPanel = document.getElementById("chatPanel");

    if (customImg) customImg.onclick = function () { chatPanel.style.display = "block"; }
    if (customSpan) customSpan.onclick = function () { chatPanel.style.display = "none"; }

    window.onclick = function (event) { if (event.target == chatPanel) chatPanel.style.display = "none"; }

    // Apre la prima tab per default
    var chat_panel = document.getElementById("defaultOpen");
    if (chat_panel) chat_panel.click();

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
});