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
    var passwordRecoveryForm = document.getElementById('passwordRecoveryForm');
    passwordRecoveryForm.style.display = 'block';
});










//PARTE SULLA SEGNALAZIONE DA INVIARE AI GESTORI


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
});














//PARTE SULLA REGISTRAZIONE DEL PERSONAGGIO
document.addEventListener("DOMContentLoaded", function() {
    // Ottieni il riferimento all'icona di chiusura nella modale di registrazione
    var closeBtn = document.querySelector('#registrationModal .close');

    // Ottieni il riferimento alla modale di registrazione
    var registrationModal = document.getElementById('registrationModal');

    // Aggiungi un gestore di eventi per il clic sull'icona di chiusura
    closeBtn.addEventListener('click', function(event) {
        // Nascondi la modale di registrazione
        registrationModal.style.display = 'none';
    });

    // Ottieni il riferimento al link di registrazione
    var registrationLink = document.getElementById('openRegistrationModal');

    // Aggiungi un gestore di eventi al clic sul link di registrazione
    registrationLink.addEventListener('click', function(event) {
        event.preventDefault(); // Previeni il comportamento predefinito del link
    
        // Visualizza la modale di registrazione
        registrationModal.style.display = 'block';
    });

    // Aggiungi un gestore di eventi per il submit del form di registrazione
    var registrationForm = document.getElementById('registrationForm');
    registrationForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Previeni il comportamento predefinito del form

        // Verifica se tutti i campi sono compilati prima di inviare il form
        var inputs = registrationForm.querySelectorAll('input');
        var checkboxes = registrationForm.querySelectorAll('input[type="checkbox"]');
        var allFieldsFilled = true;

        // Controlla tutti i campi di input
        inputs.forEach(function(input) {
            if (!input.value) {
                allFieldsFilled = false;
                return;
            }
        });

        // Controlla tutti i checkbox
        checkboxes.forEach(function(checkbox) {
            if (!checkbox.checked) {
                allFieldsFilled = false;
                return;
            }
        });

        // Se non tutti i campi sono compilati, visualizza un messaggio di avviso
        if (!allFieldsFilled) {
            alert('Devi compilare tutti i campi del modulo di registrazione.');
            return;
        }

        // Serializza i dati del form
        var formData = new FormData(registrationForm);

        
        xhr.onerror = function() {
            // Se si è verificato un errore durante la richiesta, mostra un messaggio di errore
            alert('Si è verificato un errore durante la richiesta.');
        };
        xhr.send(formData);
    });
});








//PARTEE SUL COMPILARE E SULLO SPUNTARE TUTTI I CAMPI


document.addEventListener("DOMContentLoaded", function() {
    // Ottieni il riferimento al link di registrazione
    var registrationLink = document.getElementById('openRegistrationModal');

    // Ottieni il riferimento alla modale di registrazione
    var registrationModal = document.getElementById('registrationModal');

    // Aggiungi un gestore di eventi al clic sul link di registrazione
    registrationLink.addEventListener('click', function(event) {
        event.preventDefault(); // Previeni il comportamento predefinito del link

        // Visualizza la modale di registrazione
        registrationModal.style.display = 'block';
    });

    // Aggiungi un gestore di eventi al form di registrazione per la validazione
    var registrationForm = document.getElementById('registrationForm');
    registrationForm.addEventListener('submit', function(event) {
        if (!validateRegistrationForm()) {
            event.preventDefault(); // Previeni l'invio del form se la validazione fallisce
        }
    });

    // Funzione di validazione del form di registrazione
    function validateRegistrationForm() {
        var nomePersonaggio = document.getElementById("nomePersonaggio").value;
        var cognomePersonaggio = document.getElementById("cognomePersonaggio").value;
        var emailPersonaggio = document.getElementById("emailPersonaggio").value;
        var spiritoPersonaggio = document.getElementById("spiritoPersonaggio").value;
        var mestierePersonaggio = document.getElementById("mestierePersonaggio").value;
        var accettoTermini = document.getElementById("accettoTermini").checked;
        var consensoTrattamentoDati = document.getElementById("consensoTrattamentoDati").checked;

        if (nomePersonaggio.trim() === "") {
            alert("Il campo 'Nome Personaggio' è obbligatorio.");
            return false;
        }
        if (cognomePersonaggio.trim() === "") {
            alert("Il campo 'Cognome Personaggio' è obbligatorio.");
            return false;
        }
        if (emailPersonaggio.trim() === "") {
            alert("Il campo 'E-Mail' è obbligatorio.");
            return false;
        }
        if (spiritoPersonaggio.trim() === "") {
            alert("Il campo 'Spirito del Personaggio' è obbligatorio.");
            return false;
        }
        if (mestierePersonaggio.trim() === "") {
            alert("Il campo 'Mestiere del Personaggio' è obbligatorio.");
            return false;
        }
        if (!accettoTermini) {
            alert("Devi accettare i Termini e Condizioni.");
            return false;
        }
        if (!consensoTrattamentoDati) {
            alert("Devi dare il consenso al trattamento dei dati personali.");
            return false;
        }

        return true;
    }
});































document.addEventListener("DOMContentLoaded", function() {
    // Ottieni il riferimento al form di registrazione
    var registrationForm = document.getElementById('registrationForm');

    // Aggiungi un gestore di eventi per il submit del form
    registrationForm.addEventListener('submit', function(event) {
        // Impedisci il comportamento predefinito del form (l'invio della richiesta HTTP)
        event.preventDefault();

        // Ottieni l'URL del form
        var formAction = registrationForm.getAttribute('action');

        // Invia una richiesta HTTP POST all'URL del form
        fetch(formAction, {
            method: 'POST',
            body: new FormData(registrationForm)
        })
        .then(function(response) {
            // Controlla lo stato della risposta
            if (response.ok) {
                // Se la risposta è ok, apri la pagina di riepilogo nella finestra modale
                openRiepilogoPage();
            } else {
                // Se la risposta non è ok, gestisci l'errore
                console.error('Si è verificato un errore durante l\'invio del form');
            }
        })
        .catch(function(error) {
            // Gestisci gli errori di rete o di altro tipo
            console.error('Si è verificato un errore:', error);
        });
    });

    // Funzione per aprire la pagina di riepilogo nella finestra modale
    function openRiepilogoPage() {
        // Ottieni il riferimento alla finestra modale di registrazione
        var registrationModal = document.getElementById('registrationModal');

        // Imposta l'URL della pagina di riepilogo
        var riepilogoPageURL = '../themes/crystal/home/registrazione_riepilogo.php';

        // Carica la pagina di riepilogo nella finestra modale
        registrationModal.innerHTML = '<iframe src="' + riepilogoPageURL + '" style="width: 100%; height: 100%; border: none;"></iframe>';

        // Mostra la finestra modale di registrazione
        registrationModal.style.display = 'block';
    }
});