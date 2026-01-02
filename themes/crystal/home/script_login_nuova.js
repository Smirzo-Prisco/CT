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