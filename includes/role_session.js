const ns_role_session = {
    api_file: 'pages/api_roleSession.php',
    param: 'op'
};

function addPgToRole() {
    if (confirm("Sei sicuro di voler entrare?")) {
        fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=addPgToRole')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.updateRoleActive?.(true)
                    if (window.refreshChat) window.refreshChat();
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Pg esce dalla role
function quitRole(user) {
    if (confirm("Sei sicuro di voler espellere " + user + " dalla role?")) {
        fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=quitRole', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.updateRoleActive?.(false)
                    if (window.refreshChat) window.refreshChat();

                    // Chiudi il popup dopo successo
                    document.getElementById('pgRolePlayingPanel').style.display = 'none';
                } else showNotification(data.message, 'error');
            })
            .catch(err => console.error('Errore caricamento chat:', err));
    }
}

// Carica gli utenti
function getPgRolePlaying() {
    fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=getPgRolePlaying')
        .then(res => res.json())
        .then(data => {
            const pgRolePlayingList = document.getElementById('pgRolePlayingList');
            const users = data.users || [];

            pgRolePlayingList.innerHTML = '';

            users.forEach(user => {
                const row = document.createElement('div');
                row.style.cssText = 'display: grid; grid-template-columns: 1fr 60px 60px 60px 80px; gap: 8px; align-items: center; padding: 6px 8px; background-color: rgba(30, 40, 60, 0.3); border-radius: 4px; margin-bottom: 3px; font-size: 0.9rem;';

                // Nome
                const nameCol = document.createElement('div');
                nameCol.textContent = user.name;
                nameCol.style.cssText = 'color: #e0e0e0;';

                // Spia luminosa
                const statusCol = document.createElement('div');
                statusCol.style.cssText = 'text-align: center;';
                const dot = document.createElement('div');
                dot.style.cssText = `width: 10px; height: 10px; border-radius: 50%; display: inline-block; background-color: ${user.inRole ? '#27ae60' : '#e74c3c'}; box-shadow: 0 0 ${user.inRole ? '8px' : '0'} ${user.inRole ? '#27ae60' : 'transparent'};`;

                if (user.inRole) dot.style.animation = 'pulse 2s infinite';

                statusCol.appendChild(dot);

                // Inviato
                const hasSent = document.createElement('div');
                hasSent.style.cssText = 'text-align: center;';
                const sent = document.createElement('div');
                sent.style.cssText = `width: 10px; height: 10px; border-radius: 50%; display: inline-block; background-color: ${user.sent ? '#27ae60' : '#e74c3c'}; box-shadow: 0 0 ${user.sent ? '8px' : '0'} ${user.sent ? '#27ae60' : 'transparent'};`;
                hasSent.appendChild(sent);

                // Chiuso
                const hasClosed = document.createElement('div');
                hasClosed.style.cssText = 'text-align: center;';
                const closed = document.createElement('div');
                closed.style.cssText = `width: 10px; height: 10px; border-radius: 50%; display: inline-block; background-color: ${user.closed ? '#27ae60' : '#e74c3c'}; box-shadow: 0 0 ${user.closed ? '8px' : '0'} ${user.closed ? '#27ae60' : 'transparent'};`;
                hasClosed.appendChild(closed);

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
                row.appendChild(hasSent);
                row.appendChild(hasClosed);
                row.appendChild(actionCol);
                pgRolePlayingList.appendChild(row);
            });
        })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Carica i PNG attivi nella sezione del pannello chat dedicata ai master
function getPngRolePlaying() {
    fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=getPngRolePlaying')
        .then(res => res.json())
        .then(data => {
            const pngSelect = document.getElementById('pngName');

            pngSelect.innerHTML = ''; // Reset

            // Popola con i livelli disponibili
            data.png.forEach(png => {
                const option = document.createElement('option');

                option.value = png;
                option.textContent = png;

                pngSelect.appendChild(option);
            });
        })
        .catch(err => console.error('Errore caricamento PNGs:', err));
}

/******************************** Selezione bersagli *******************************************/
async function getRolePgs() {
    try {
        const response = await fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=getRolePgs', { method: 'POST' });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Errore nel caricamento utenti:', error);
        return [];
    }
}

// Il pg chiude il proprio turno
function closePgTurn(id_role, suss_id, pgName) {
    // Rimuovo 5 messaggi dal sussurro in poi
    if (suss_id > 0 && document.getElementById(suss_id)) {
        for (let i = suss_id; i < (suss_id + 5); i++) {
            if (document.getElementById(i)) document.getElementById(i).remove();
        }
    }

    fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=closePgTurn', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_role, suss_id, pgName })
    })
        .then(res => res.json())
        .then(data => { if (window.refreshChat) window.refreshChat(); })
        .catch(err => console.error('Errore caricamento chat:', err));
}

// Chiudo il turno fornzatamente, indipendentemente da tutto
function closeTurn() {
    fetch(ns_role_session.api_file + '?' + ns_role_session.param + '=closeTurn', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Esegue refresh della chat
                if (window.refreshChat) window.refreshChat();

                window.closeChatPanel?.();
            } else showNotification(data.message, 'error');
        })
        .catch(err => console.error('Errore caricamento chat:', err));
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

/*****************************************************************************/
/*********************** Pannello utenti giocanti ****************************/
/*****************************************************************************/
function closePgRolePlayingPanel() {
    document.getElementById('pgRolePlayingPanel').style.display = 'none';
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
    const addPgToRoleBtn = document.getElementById('addPgToRoleBtn');

    if (isActive) {
        statusElement.classList.add('active');
        statusElement.classList.remove('inactive');
        addPgToRoleBtn.textContent = ' Join!';
    } else {
        statusElement.classList.add('inactive');
        statusElement.classList.remove('active');
        addPgToRoleBtn.textContent = ' Avvia!';
    }
}

// Esposta globalmente: ChatShell.jsx la chiama dopo il mount in navigazione SPA.
// Al primo caricamento PHP viene chiamata da DOMContentLoaded come prima.
window.initRoleSession = function () {
    new pgRolePlayingPanel();
};

document.addEventListener('DOMContentLoaded', window.initRoleSession);