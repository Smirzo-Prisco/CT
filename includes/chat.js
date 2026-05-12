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
                const chatContainer = document.getElementById('pagina_chat');
                if (chatContainer) chatContainer.innerHTML = ''; // Svuota la chat

                // Esegue refresh della chat
                if (window.refreshChat) window.refreshChat();

                document.getElementById("chatPanel").style.display = "none";
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Ruoli apicali cancellano la chat di gioco
async function curaPg() {
    fetch(ns_chat.api_file + '?' + ns_chat.param + '=curaPg')
        .then(res => res.json())
        .then(data => { if (window.refreshChat) window.refreshChat(); })
        .catch(err => console.error('Errore caricamento chat:', err));
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
        fetch(ns_chat.api_file + '?' + ns_chat.param + '=get_chat_messages', {
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
                        if (data.charLimit != null && data.charLimit > 0) document.getElementById('message').maxLength = data.charLimit; // Aggiorna il limite di caratteri
                        document.getElementById('quitRole').style.display = data.canQuit ? 'block' : 'none'; // Mostra o nasconde il pulsante di uscita dalla role
                        document.getElementById('openPanelBtn').style.display = data.canUsePanel ? 'block' : 'none'; // Mostra o nasconde il pulsante di apertura del pannello chat
                        document.getElementById('pgRolePlaying').style.display = data.activeRole ? 'block' : 'none'; // Mostra o nasconde il pannello con l'elenco degli utenti giocanti
                        document.getElementById('addPgToRoleBtn').style.display = data.canQuit ? 'none' : 'block'; // Mostra o nasconde il pulsante per avviare o aggiungersi alla role
                        isInRole = data.canQuit; // Variabile globale per sapere se l'utente è dentro una role o no (usata per limitare alcune azioni)
                        // Aggiorna l'ultimo ID (prende l'ultimo messaggio disponibile)
                        const lastMessage = data.messages[data.messages.length - 1];
                        if (lastMessage) setLastId(parseInt(lastMessage.id, 10));
                        // Aggiorno la chat con i nuovi messaggi
                        if (data.messages.length > 0) {
                            data.messages.forEach(msg => { if (msg.html) chatContainer.innerHTML += msg.html; }); // Aggiungo i nuovi messaggi in chat
                            setTimeout(() => { chatContainer.scrollTop = chatContainer.scrollHeight; }, 100); // Scroll automatico
                        }
                        // Riproduco l'auDIO
                        const audio = new Audio('../sounds/beep.wav');
                        if (data.play === true) audio.play().catch(e => console.log('Audio error:', e));
                    }
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    };

    // Ref sempre aggiornata alla closure corrente di fetchMessages (cattura lastId aggiornato)
    const fetchRef = React.useRef(fetchMessages);
    fetchRef.current = fetchMessages;

    React.useEffect(() => {
        fetchRef.current(); // caricamento iniziale

        if (window.ctSocket) {
            window.ctSocket.on('chat:update', () => fetchRef.current());
        }

        window.refreshChat = () => fetchRef.current();
    }, []); // solo al mount

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
    setMaxTargetExternal(1);

    if (target.length != 1 || dice_type == 0) {
        setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // AGGIORNA il limite dei bersagli consentiti
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
                    document.getElementById("chatPanel").style.display = "none";
                    // Esegue refresh della chat
                    if (window.refreshChat) window.refreshChat();
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    } else showNotification('Attenzione! Devi selezionare il bersaglio', 'warning');
}

// Serve per usare un'arma in chat
function usaAttaccoChat() {
    const tipo_attacco = document.getElementById('tipo_attacco').value;
    const arma_body = document.getElementById('arma_body').value;
    const target = getSelectedNamesCallback();

    if (target.length != 1 || tipo_attacco == 0) {
        setMaxTargetExternal(0);
        resettaCampiDiv('skills-tab'); // AGGIORNA il limite dei bersagli consentiti
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
                    document.getElementById("chatPanel").style.display = "none";
                    // Esegue refresh della chat
                    if (window.refreshChat) window.refreshChat();
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

                if (window.refreshChat) window.refreshChat();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
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
                document.getElementById("chatPanel").style.display = "none";

                if (window.refreshChat) window.refreshChat();
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

    fetch(ns_chat.api_file + '?' + ns_chat.param + '=tiraDadoGenericoChat', {
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
            document.getElementById("chatPanel").style.display = "none";

            if (window.refreshChat) window.refreshChat();
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
            document.getElementById("chatPanel").style.display = "none";

            if (window.refreshChat) window.refreshChat();

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
            document.getElementById("chatPanel").style.display = "none";

            if (window.refreshChat) window.refreshChat();
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
document.addEventListener('DOMContentLoaded', function () {
    console.log('TEST di prova');
    document.getElementById('chat_skill').addEventListener('change', aggiornaLivelli); // Se cambio skill, aggiorno il suo livello massimo consentito per il pg
    document.getElementById('livello_skill').addEventListener('change', aggiornaLimiteDaLivello); // Se cambio il livello skill, aggiorno il limite di bersagli

    // LOAD DELLA CHAT DI GIOCO
    const container = document.getElementById('pagina_chat');
    console.log('TEST', container);
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