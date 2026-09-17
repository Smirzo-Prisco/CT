const ns_oggetto_ricarica = {
    api_file: 'pages/api_oggetto_ricarica.php',
    param: 'op'
};

// Carica gli oggetti scaduti (cariche=0, richiede_ricarica=1) del personaggio
// selezionato, sostituendo il vecchio "step 2" a pagina intera con un fetch.
function caricaOggettiScaduti(pg) {
    const gruppoOggetto = document.getElementById('ricarica_oggetto_group');
    const selectOggetto = document.getElementById('ricarica_oggetto');
    const vuoto = document.getElementById('ricarica_vuoto');
    const submit = document.getElementById('ricarica_submit');

    submit.disabled = true;
    gruppoOggetto.hidden = true;
    vuoto.hidden = true;
    selectOggetto.innerHTML = '';

    fetch(ns_oggetto_ricarica.api_file + '?' + ns_oggetto_ricarica.param + '=getOggettiScaduti&pg=' + encodeURIComponent(pg))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification(data.message || 'Errore nel caricamento', 'error');
                return;
            }
            if (data.oggetti.length === 0) {
                vuoto.hidden = false;
                return;
            }
            data.oggetti.forEach(obj => {
                const option = document.createElement('option');
                option.value = obj.id_oggetto;
                option.textContent = obj.nome + ' [Max: ' + obj.ricarica_massima + ']';
                selectOggetto.appendChild(option);
            });
            gruppoOggetto.hidden = false;
            submit.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore nel caricamento degli oggetti scaduti', 'error');
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const selectPg = document.getElementById('ricarica_pg');
    if (selectPg) {
        caricaOggettiScaduti(selectPg.value); // precarica per il personaggio già selezionato
        selectPg.addEventListener('change', () => caricaOggettiScaduti(selectPg.value));
    }

    const form = document.getElementById('ricaricaForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const pg = document.getElementById('ricarica_pg').value;
            const idOggetto = document.getElementById('ricarica_oggetto').value;
            if (!idOggetto) return;

            fetch(ns_oggetto_ricarica.api_file + '?' + ns_oggetto_ricarica.param + '=ricaricaObj', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pg, id_oggetto: idOggetto })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        caricaOggettiScaduti(pg); // aggiorna l'elenco (l'oggetto ricaricato non e' piu' scaduto)
                    } else {
                        showNotification('Errore: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nella ricarica', 'error');
                });
        });
    }
});
