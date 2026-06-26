/**
 * public.js — JavaScript condiviso per le pagine pubbliche di Crystal Tokyo GDR.
 *
 * Gestisce:
 *   1. Header sticky con cambio stile allo scroll
 *   2. Hamburger menu mobile
 *   3. Sistema modali (login, registrazione, segnalazione)
 *   4. Switch tra modale login e registrazione
 *   5. Recupero password (POST a recupero_password.php)
 *   6. Caricamento razze/mestieri da API (/pages/api_iscrizione.php?op=options)
 *   7. Caricamento termini/informazioni da API (/pages/api_iscrizione.php?op=termini)
 *   8. Toggle sezioni accordion (FAQ, info, termini)
 *
 * NON dipende da jQuery o da altri framework.
 * Caricato con defer — il DOM è pronto quando il codice esegue.
 */

'use strict';

/* ============================================================
   1. HEADER STICKY
   Aggiunge la classe .scrolled all'header quando si scrolla
   verso il basso: attiva lo sfondo opaco nella navigazione.
   ============================================================ */
(function initStickyHeader() {
    const header = document.getElementById('pub-header');
    if (!header) return;

    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });
}());


/* ============================================================
   2. HAMBURGER MENU MOBILE
   Toggle della classe .open su nav e hamburger.
   Chiude il menu cliccando fuori o su un link.
   ============================================================ */
(function initHamburger() {
    const btn = document.getElementById('pubHamburger');
    const nav = document.getElementById('pubNav');
    if (!btn || !nav) return;

    btn.addEventListener('click', e => {
        e.stopPropagation();
        const open = nav.classList.toggle('open');
        btn.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', open);
    });

    /* Chiudi cliccando su un link del menu */
    nav.addEventListener('click', e => {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
            nav.classList.remove('open');
            btn.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    /* Chiudi cliccando fuori dal menu */
    document.addEventListener('click', e => {
        if (!nav.contains(e.target) && !btn.contains(e.target)) {
            nav.classList.remove('open');
            btn.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}());


/* ============================================================
   3. SISTEMA MODALI
   Una modale è un .pub-modal-overlay con id univoco.
   openModal(id) → mostra; closeModal(id) → nasconde.
   Chiusura anche cliccando overlay o premendo Escape.
   ============================================================ */

/** Apre la modale con l'id specificato, chiude tutte le altre. */
function openModal(id) {
    /* Chiudi tutte le modali aperte */
    document.querySelectorAll('.pub-modal-overlay').forEach(m => {
        m.style.display = 'none';
    });

    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = 'flex';
    /* Focus sull'interno per accessibilità */
    const focusable = modal.querySelector('input, button, select, textarea, [tabindex]');
    if (focusable) focusable.focus();
}

/** Chiude la modale con l'id specificato. */
function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}

(function initModals() {
    /* Bottoni "Accedi" nella nav */
    document.querySelectorAll('#pubLoginBtn, #footerLoginLink').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            openModal('pubLoginModal');
            ensureOptionsLoaded();
        });
    });

    /* Bottoni "Registrati" nella nav */
    document.querySelectorAll('#pubRegBtn, #footerRegLink').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            openModal('pubRegModal');
            ensureOptionsLoaded();
            ensureTerminiLoaded();
        });
    });

    /* Chiusura con tasto × (data-modal="id") */
    document.querySelectorAll('.pub-modal-close').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.modal));
    });

    /* Chiusura cliccando sull'overlay (fuori dalla box) */
    document.querySelectorAll('.pub-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });

    /* Chiusura con tasto Escape */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.pub-modal-overlay').forEach(m => {
                m.style.display = 'none';
            });
        }
    });
}());


/* ============================================================
   4. SWITCH LOGIN ↔ REGISTRAZIONE
   I link "Registrati qui" / "Accedi qui" nelle modali
   passano da una modale all'altra senza chiudere e riaprire.
   ============================================================ */
(function initModalSwitch() {
    const switchToReg = document.getElementById('pubSwitchToReg');
    if (switchToReg) {
        switchToReg.addEventListener('click', e => {
            e.preventDefault();
            openModal('pubRegModal');
            ensureOptionsLoaded();
            ensureTerminiLoaded();
        });
    }

    const switchToLogin = document.getElementById('pubSwitchToLogin');
    if (switchToLogin) {
        switchToLogin.addEventListener('click', e => {
            e.preventDefault();
            openModal('pubLoginModal');
        });
    }
}());


/* ============================================================
   5. RECUPERO PASSWORD
   Mostra/nasconde il form di recupero nella modale login.
   Invia la email a recupero_password.php via fetch JSON.
   ============================================================ */
(function initPasswordRecovery() {
    const link     = document.getElementById('pubPwdRecoveryLink');
    const recovery = document.getElementById('pubPwdRecovery');
    const form     = document.getElementById('pubPwdRecoveryForm');
    const msg      = document.getElementById('pubPwdMsg');

    if (!link || !recovery || !form) return;

    link.addEventListener('click', e => {
        e.preventDefault();
        recovery.style.display = recovery.style.display === 'none' ? 'block' : 'none';
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const email = document.getElementById('pubRecoveryEmail').value;
        try {
            const res  = await fetch('/themes/crystal/home/recupero_password.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ email }),
            });
            const data = await res.json();
            if (msg) {
                msg.textContent = data.response ?? 'Email inviata.';
                msg.style.display = 'block';
            }
        } catch {
            if (msg) {
                msg.textContent = 'Errore durante l\'invio — riprova.';
                msg.style.display = 'block';
            }
        }
    });
}());


/* ============================================================
   6. CARICAMENTO RAZZE E MESTIERI
   Chiamata a /pages/api_iscrizione.php?op=options.
   Popola i <select> nel form di registrazione.
   Eseguita una sola volta (flag optionsLoaded).
   ============================================================ */
let optionsLoaded = false;

function ensureOptionsLoaded() {
    if (optionsLoaded) return;

    fetch('/pages/api_iscrizione.php?op=options')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            optionsLoaded = true;

            const selRazza = document.getElementById('pubRegRazza');
            if (selRazza && data.razze) {
                selRazza.innerHTML =
                    '<option value="0">Decidi poi</option>' +
                    data.razze.map(r => `<option value="${r.id}">${r.nome}</option>`).join('');
            }

            const selMest = document.getElementById('pubRegMestiere');
            if (selMest && data.mestieri) {
                selMest.innerHTML = data.mestieri.map(
                    m => `<option value="${m.id}">${m.nome}</option>`
                ).join('');
            }
        })
        .catch(() => {/* Fallback silenzioso: l'utente vedrà "Caricamento..." */});
}


/* ============================================================
   7. CARICAMENTO TERMINI E INFORMAZIONI
   Chiamata a /pages/api_iscrizione.php?op=termini.
   Popola i div delle sezioni termini/informazioni nel form registrazione.
   Eseguita una sola volta (flag terminiLoaded).
   ============================================================ */
let terminiLoaded = false;

function ensureTerminiLoaded() {
    if (terminiLoaded) return;

    fetch('/pages/api_iscrizione.php?op=termini')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            terminiLoaded = true;

            const infoEl = document.getElementById('pubInfoText');
            if (infoEl && data.regolamento) infoEl.innerHTML = data.regolamento;

            const tcEl = document.getElementById('pubTcText');
            if (tcEl && data.disclaimer) tcEl.innerHTML = data.disclaimer;
        })
        .catch(() => {});
}


/* ============================================================
   8. TOGGLE ACCORDION
   Per le sezioni FAQ, termini, informazioni nel form di registrazione.
   Il tasto toggle aggiunge/rimuove .open al div contenuto.
   ============================================================ */
(function initToggles() {
    /**
     * Crea un toggle tra un bottone e un div contenuto.
     * @param {string} btnId  - id del bottone
     * @param {string} divId  - id del div da mostrare/nascondere
     */
    function makeToggle(btnId, divId) {
        const btn = document.getElementById(btnId);
        const div = document.getElementById(divId);
        if (!btn || !div) return;

        btn.addEventListener('click', () => {
            const open = div.style.display !== 'none';
            div.style.display = open ? 'none' : 'block';
            btn.classList.toggle('open', !open);
        });
    }

    /* Toggle nel form di registrazione */
    makeToggle('pubInfoToggle', 'pubInfoContent');
    makeToggle('pubTcToggle',   'pubTcContent');

    /* Toggle FAQ nella pagina come-si-gioca
       Ogni .faq-item ha un bottone .faq-q e un div .faq-a */
    document.querySelectorAll('.faq-item').forEach(item => {
        const q = item.querySelector('.faq-q');
        const a = item.querySelector('.faq-a');
        if (!q || !a) return;

        q.addEventListener('click', () => {
            const open = a.style.display !== 'none';
            /* Chiudi tutti gli altri */
            document.querySelectorAll('.faq-a').forEach(el => {
                el.style.display = 'none';
                el.closest('.faq-item')?.classList.remove('open');
            });
            /* Apri/chiudi quello cliccato */
            if (!open) {
                a.style.display = 'block';
                item.classList.add('open');
            }
        });
    });
}());


/* ============================================================
   9. LOGIN VIA FETCH
   Intercetta il submit del form di login, invia le credenziali
   a api_auth.php?op=login via JSON, mostra l'errore inline
   nella modale oppure reindirizza al gioco sul successo.
   Nessun reload di pagina.
   ============================================================ */
(function initLoginForm() {
    const form  = document.getElementById('pubDoLogin');
    const error = document.getElementById('pubLoginError');
    if (!form) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();

        /* Nascondi errore precedente e disabilita il bottone */
        if (error) error.style.display = 'none';
        const btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;

        const login    = document.getElementById('pubUsername')?.value ?? '';
        const password = document.getElementById('pubPassword')?.value ?? '';

        try {
            const res  = await fetch('/pages/api_auth.php?op=login', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ login, password }),
            });
            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect;
                return;
            }

            if (error) {
                error.textContent   = data.message || 'Nome personaggio o password non riconosciuti.';
                error.style.display = 'block';
            }
        } catch {
            if (error) {
                error.textContent   = 'Errore di connessione — riprova tra qualche istante.';
                error.style.display = 'block';
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    });
}());


/* ============================================================
   VALIDAZIONE FORM REGISTRAZIONE
   Verifica che i T&C siano accettati prima dell'invio.
   ============================================================ */
(function initRegFormValidation() {
    const form = document.getElementById('pubRegForm');
    if (!form) return;

    form.addEventListener('submit', e => {
        const tc = document.getElementById('pubRegTc');
        if (tc && !tc.checked) {
            e.preventDefault();
            alert('Devi accettare i Termini e Condizioni per procedere.');
        }
    });
}());
