document.addEventListener("DOMContentLoaded", function() {
    // Ottieni il colore di sfondo del body
    var bodyBgColor = window.getComputedStyle(document.body).backgroundColor;

    // Applica il colore di sfondo al custom-box
    var customBox = document.querySelector('.custom-box');
    customBox.style.backgroundColor = bodyBgColor;
});

const loginBtn = document.getElementById('loginBtn');
const loginContent = document.getElementById('loginContent');
const closeBtn = document.querySelector('.close');

// Aggiungi un gestore di eventi al pulsante di login per aprire la finestra modale
loginBtn.addEventListener('click', () => {
    loginContent.style.display = 'block';
});

// Aggiungi un gestore di eventi per il pulsante di chiusura della finestra modale
closeBtn.addEventListener('click', () => {
    loginContent.style.display = 'none';
});

// Chiudi la finestra modale cliccando al di fuori di essa
window.addEventListener('click', (event) => {
    if (event.target === loginContent) {
        loginContent.style.display = 'none';
    }
});

// Aggiungi un gestore di eventi al link "Recupero password"
document.getElementById('passwordRecoveryLink').addEventListener('click', function(event) {
    event.preventDefault(); // Previeni il comportamento predefinito dell'ancora (apertura di una nuova pagina)
    
    // Mostra il modulo di recupero password
    var passwordRecoveryDiv = document.getElementById('passwordRecoveryDiv');
    passwordRecoveryDiv.style.display = 'block';
});

// ── Hamburger menu ────────────────────────────────────────────────────────

(function () {
    const hamburger = document.getElementById('hamburgerBtn');
    const nav       = document.getElementById('mainNav');
    if (!hamburger || !nav) return;

    hamburger.addEventListener('click', e => {
        e.stopPropagation();
        const open = nav.classList.toggle('open');
        hamburger.classList.toggle('open', open);
    });

    // Chiudi il menu cliccando su un link
    nav.addEventListener('click', e => {
        if (e.target.tagName === 'A') {
            nav.classList.remove('open');
            hamburger.classList.remove('open');
        }
    });

    // Chiudi il menu cliccando fuori (esclude hamburger e i suoi span interni)
    document.addEventListener('click', e => {
        if (!nav.contains(e.target) && !hamburger.contains(e.target)) {
            nav.classList.remove('open');
            hamburger.classList.remove('open');
        }
    });
}());

// ── Modale Registrazione ──────────────────────────────────────────────────

(function () {
    const btn       = document.getElementById('registrazioneBtn');
    const modal     = document.getElementById('registrazioneContent');
    const closeEl   = document.getElementById('closeRegModal');
    const tcBtn     = document.getElementById('tcToggleBtn');
    const tcDiv     = document.getElementById('tcContent');
    const tcText    = document.getElementById('tcText');
    const infoBtn   = document.getElementById('infoToggleBtn');
    const infoDiv   = document.getElementById('infoContent');
    const infoText  = document.getElementById('infoText');

    let optionsLoaded = false;
    let terminiLoaded = false;

    const contentBox  = document.querySelector('.content-box');
    const bottomBanner = document.getElementById('bottomBanner');

    function positionModal() {
        if (window.innerWidth <= 768) {
            const header  = document.querySelector('header');
            const headerH = header ? header.getBoundingClientRect().bottom : 60;
            const top     = headerH + 10;
            const maxHeight = window.innerHeight - top - 10;
            modal.style.top       = top + 'px';
            modal.style.transform = 'translateX(-50%)';
            modal.style.maxHeight = maxHeight + 'px';
        } else {
            modal.style.top       = '';
            modal.style.transform = '';
            modal.style.maxHeight = '';
        }
    }

    function openModal() {
        if (bottomBanner) bottomBanner.style.display = 'none';
        if (contentBox)   contentBox.style.display   = 'none';
        modal.style.display = 'block';
        positionModal();
        if (!optionsLoaded) loadOptions();
        if (!terminiLoaded) loadTermini();
    }

    function closeModal() {
        modal.style.display = 'none';
        if (bottomBanner) bottomBanner.style.display = '';
        if (contentBox)   contentBox.style.display   = '';
    }

    function makeToggle(btn, div, labelOpen, labelClose) {
        btn.addEventListener('click', () => {
            const visible = div.style.display !== 'none';
            div.style.display = visible ? 'none' : 'block';
            btn.textContent = visible ? labelOpen : labelClose;
        });
    }

    function loadOptions() {
        fetch('/pages/api_iscrizione.php?op=options')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                optionsLoaded = true;
                document.getElementById('regRazza').innerHTML = data.razze.map(
                    r => `<option value="${r.id}">${r.nome}</option>`
                ).join('');
                document.getElementById('regMestiere').innerHTML = data.mestieri.map(
                    m => `<option value="${m.id}">${m.nome}</option>`
                ).join('');
            })
            .catch(() => {});
    }

    function loadTermini() {
        fetch('/pages/api_iscrizione.php?op=termini')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                terminiLoaded = true;
                infoText.innerHTML = data.regolamento;
                tcText.innerHTML   = data.disclaimer;
            })
            .catch(() => {});
    }

    btn.addEventListener('click', e => { e.preventDefault(); openModal(); });
    closeEl.addEventListener('click', closeModal);
    window.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    makeToggle(infoBtn, infoDiv, 'ℹ Informazioni sul gioco', '✕ Chiudi informazioni');
    makeToggle(tcBtn,  tcDiv,  'ℹ Termini e Condizioni',   '✕ Chiudi termini');

    document.getElementById('regForm').addEventListener('submit', function (e) {
        if (!document.getElementById('regTc').checked) {
            e.preventDefault();
            alert('Devi accettare i Termini e Condizioni per procedere.');
        }
    });
}());

document.addEventListener("DOMContentLoaded", function() {
    // Ottieni il riferimento al link "SEGNALA"
    var reportLink = document.querySelector('#reportLink');

    // Aggiungi un gestore di eventi al click sul link
    reportLink.addEventListener('click', function(event) {
        event.preventDefault(); // Previeni il comportamento predefinito dell'ancora (apertura di una nuova pagina)
        
        // Mostra la modale di segnalazione
        var reportContent = document.getElementById('reportContent');
        reportContent.style.display = 'block';
    });

    // Ottieni il riferimento alla "x" per chiudere la modale
    var closeReportModal = document.getElementById('closeReportModal');

    // Aggiungi un gestore di eventi per il pulsante di chiusura della finestra modale di segnalazione
    closeReportModal.addEventListener('click', () => {
        var reportContent = document.getElementById('reportContent');
        reportContent.style.display = 'none';
    });

    // Chiudi la modale cliccando al di fuori di essa
    window.addEventListener('click', (event) => {
        var reportContent = document.getElementById('reportContent');
        if (event.target === reportContent) {
            reportContent.style.display = 'none';
        }
    });

    // Chiamata per recupero password
    document.getElementById('passwordRecoveryForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Previeni l'invio tradizionale del form

        var pwdRecoveryMail = document.getElementById('recoveryEmail').value;

        // nascondo il form di recupero password
        document.getElementById('passwordRecoveryDiv').style.display = 'none';

        // Ottieni i dati che vuoi inviare
        var datiUtente = { email: pwdRecoveryMail };

        // Invia la richiesta POST con un oggetto JSON
        fetch('/themes/crystal/home/recupero_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json' // Specifica il tipo di contenuto JSON
            },
            body: JSON.stringify(datiUtente) // Converte l'oggetto JavaScript in una stringa JSON
        })
        .then(response => response.json()) // Attende una risposta JSON dal server
        .then(data => {
            console.log('Success:', data);
            alert(data.response);
        })
        .catch((error) => {
            console.error('Error:', error);
            alert(error);
        });
    });
});