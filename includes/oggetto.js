const ns_oggetti = {
    api_file: 'pages/api_oggetto.php',
    param: 'op'
};

/***********    GESTIONE OGGETTI  *********************/
// Variabili globali oggetto
let modalObj = null;
let isEditMode = false;

function gestioneCampiDinamici(categoriaObj) {
    const categoria = categoriaObj.value;

    if (categoria) {
        abilitaCampiCategoria(categoria);
        caricaTipiPerCategoriaObj(categoria);
    } else {
        // Se non c'è categoria, disabilita tutto
        document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
            sec.style.display = 'none';
            sec.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = true;
            });
        });
    }
}

// Popola il form con i dati
function popolaFormObj(oggetto) {
    document.getElementById('id_oggetto').value = oggetto.id_oggetto;
    document.getElementById('nome').value = oggetto.nome;
    document.getElementById('descrizione').value = oggetto.descrizione;
    document.getElementById('ubicabile').value = oggetto.ubicabile;
    document.getElementById('categoriaObj').value = oggetto.categoria;

    // Immagine preview
    if (oggetto.urlimg) {
        document.getElementById('previewImgObj').src = 'imgs/items/' + oggetto.urlimg;
        document.getElementById('imagePreviewObj').style.display = 'block';
    }

    // Popola sezioni dinamiche (il tipo verrà impostato dopo)
    popolaCampiDinamiciObj(oggetto);
}

// Apertura modale per MODIFICA oggetti
function apriModaleModificaObj(idOggetto) {
    isEditMode = true;
    document.getElementById('modalTitleObj').textContent = 'Modifica Oggetto';

    // Carica dati oggetto via AJAX
    fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + `=getObj&id=${idOggetto}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const oggetto = data.oggetto;

                // Popola i campi base
                document.getElementById('id_oggetto').value = oggetto.id_oggetto;
                document.getElementById('nome').value = oggetto.nome;
                document.getElementById('descrizione').value = oggetto.descrizione;
                document.getElementById('ubicabile').value = oggetto.ubicabile;
                document.getElementById('categoriaObj').value = oggetto.categoria;

                // Immagine preview
                if (oggetto.urlimg) {
                    document.getElementById('previewImgObj').src = 'imgs/items/' + oggetto.urlimg;
                    document.getElementById('imagePreviewObj').style.display = 'block';
                }

                // MOSTRA LA MODALE (flex, non block: .pg-edit-container centra il
                // contenuto via align-items/justify-content, che valgono solo su
                // un container flex)
                modalObj.style.display = 'flex';

                // CARICA I TIPI E POI GESTISCI I CAMPI DINAMICI
                caricaTipiPerCategoriaObj(oggetto.categoria, oggetto.tipo)
                    .then(() => {
                        // RIABILITA ESPLICITAMENTE I CAMPI DELLA CATEGORIA CORRETTA
                        abilitaCampiCategoria(oggetto.categoria);
                        // Popola i campi dinamici
                        popolaCampiDinamiciObj(oggetto);

                        // AGGIUNGI EVENT LISTENER PER CAMBIO CATEGORIA ANCHE IN MODIFICA
                        setupCategoriaChangeListener();
                    });

            } else {
                showNotification('Errore nel caricamento oggetto', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore: ' + error.message, 'error');
        });
}

// Apertura modale per CREAZIONE oggetti
function caricaTipiPerCategoriaObj(categoria, tipoSelezionato = '') {
    return fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + `=getTipiObj&categoria=${categoria}`)
        .then(response => response.json())
        .then(data => {
            const selectTipo = document.getElementById('tipo');
            selectTipo.innerHTML = '<option value="">Seleziona tipo</option>';

            data.tipi.forEach(tipo => {
                const option = document.createElement('option');
                option.value = tipo.cod_tipo;
                option.textContent = tipo.descrizione;
                selectTipo.appendChild(option);
            });

            // IMPOSTA IL VALORE SOLO DOPO AVER POPOLATO LA SELECT
            if (tipoSelezionato) {
                selectTipo.value = tipoSelezionato;
            }

            return data;
        })
        .catch(error => {
            console.error('Errore nel caricamento tipi:', error);
            showNotification('Errore nel caricamento dei tipi', 'error');
        });
}

// Popola i campi dinamici oggetti
function popolaCampiDinamiciObj(oggetto) {
    const categoria = oggetto.categoria;

    // Popola i campi specifici della categoria
    switch (categoria) {
        case 'arma':
            setSafeValue('[name="tipo_arma"]', oggetto.tipo_arma || 1);
            setSafeValue('[name="bonus_arma"]', oggetto.attacco || 1);
            setSafeValue('[name="ricarica_massima"]', oggetto.ricarica_massima || 1);
            break;
        case 'curativo':
            setSafeValue('[name="salute_integra"]', oggetto.heal || 0);
            setSafeValue('[name="integrita_integra"]', oggetto.bonus_car0 || 0);
            setSafeValue('[name="cariche_curativo"]', oggetto.cariche || 1);
            break;
        case 'statistica':
            setSafeValue('[name="temp_giorni"]', oggetto.temp_giorni || 1);
            setSafeValue('[name="bonus_car1_extra"]', oggetto.bonus_car1_extra || 0);
            setSafeValue('[name="bonus_car2_extra"]', oggetto.bonus_car2_extra || 0);
            setSafeValue('[name="bonus_car3_extra"]', oggetto.bonus_car3_extra || 0);
            setSafeValue('[name="bonus_car4_extra"]', oggetto.bonus_car4_extra || 0);
            setSafeValue('[name="bonus_car5_extra"]', oggetto.bonus_car5_extra || 0);
            setSafeValue('[name="cariche_statistica"]', oggetto.cariche || 1);
            setSafeValue('[name="ricarica_massima_statistica"]', oggetto.ricarica_massima || 0);
            break;
        case 'magico':
            setSafeValue('[name="cariche_magico"]', oggetto.cariche || 1);
            setSafeValue('[name="ricarica_massima_magico"]', oggetto.ricarica_massima || 1);
            break;
        case 'standard':
            setSafeValue('[name="cariche_standard"]', oggetto.cariche || 'illimitato');
            break;
    }
}

// Funzione helper per settare valori in modo sicuro
function setSafeValue(selector, value) {
    const element = document.querySelector(selector);
    if (element) {
        const oldValue = element.value;
        element.value = value;

        return true;
    } else return false;
}

// Variabile per memorizzare la categoria precedente
let previousCategoria = '';

// Apertura modale per CREAZIONE oggetto
function apriModaleCreazioneObj() {
    isEditMode = false;
    document.getElementById('modalTitleObj').textContent = 'Crea Nuovo Oggetto';
    document.getElementById('oggettoForm').reset();
    document.getElementById('id_oggetto').value = '';
    document.getElementById('imagePreviewObj').style.display = 'none';
    // document.getElementById('categoriaObj').disabled = false;

    // Reset sezioni dinamiche
    document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
        sec.style.display = 'none';
    });

    // Mostra modale e carica tipi (flex, vedi commento in apriModaleModificaObj)
    modalObj.style.display = 'flex';

    // Setup del listener per cambio categoria
    setupCategoriaChangeListener();

    caricaTipiPerCategoriaObj('standard');
}

// Funzione per abilitare esplicitamente i campi di una categoria
function abilitaCampiCategoria(categoria) {
    // Disabilita TUTTI i campi dinamici prima
    document.querySelectorAll('.sezione-dinamica-obj input, .sezione-dinamica-obj select, .sezione-dinamica-obj textarea').forEach(field => {
        field.disabled = true;
    });

    // Nascondi TUTTE le sezioni
    document.querySelectorAll('.sezione-dinamica-obj').forEach(sec => {
        sec.style.display = 'none';
    });

    // Mostra e abilita solo la sezione della categoria corrente
    const sezioneAttiva = document.getElementById(`sezione_${categoria}`);
    if (sezioneAttiva) {
        sezioneAttiva.style.display = 'block';
        sezioneAttiva.querySelectorAll('input, select, textarea').forEach(field => {
            // In modifica, alcuni campi potrebbero essere disabilitati, ma i campi dinamici li abilitiamo
            if (!field.classList.contains('disabled-permanently')) field.disabled = false;
        });
    }
}

// Handler per il cambio categoria
function handleCategoriaChange(e) {
    const categoria = e.target.value;

    if (categoria) {
        // Se siamo in modifica, mostra un avviso
        if (isEditMode) {
            if (!confirm("Attenzione! Cambiando la categoria, potresti perdere alcuni dati specifici. Vuoi procedere?")) {
                // Ripristina il valore precedente
                e.target.value = previousCategoria;
                return;
            }
        }
        // Gestisci i campi dinamici
        abilitaCampiCategoria(categoria);
        caricaTipiPerCategoriaObj(categoria);

        // Salva la categoria corrente per eventuale annullamento
        previousCategoria = categoria;
    }
}

// Funzione per gestire il cambio categoria in entrambe le modalità
function setupCategoriaChangeListener() {
    const categoriaObj = document.getElementById('categoriaObj');

    // Rimuovi eventuali listener precedenti
    categoriaObj.removeEventListener('change', handleCategoriaChange);

    // Aggiungi nuovo listener
    categoriaObj.addEventListener('change', handleCategoriaChange);
}

// SALVA OGGETTO
const oggettoForm = document.getElementById("oggettoForm");

if (oggettoForm) {
    oggettoForm.addEventListener('submit', function (e) {
        document.getElementById('categoriaObj').disabled = false;
        e.preventDefault();

        const formData = new FormData(this);
        const categoria = document.getElementById('categoriaObj').value;

        // Mappa i nomi specifici per categoria ai nomi generici per il database
        switch (categoria) {
            case 'curativo':
                if (formData.has('cariche_curativo')) formData.set('cariche', formData.get('cariche_curativo'));
                // curativo non ha ricarica_massima
                break;
            case 'statistica':
                if (formData.has('cariche_statistica')) formData.set('cariche', formData.get('cariche_statistica'));
                if (formData.has('ricarica_massima_statistica')) formData.set('ricarica_massima', formData.get('ricarica_massima_statistica'));
                break;
            case 'magico':
                if (formData.has('cariche_magico')) formData.set('cariche', formData.get('cariche_magico'));
                if (formData.has('ricarica_massima_magico')) formData.set('ricarica_massima', formData.get('ricarica_massima_magico'));
                break;
            case 'standard':
                if (formData.has('cariche_standard')) formData.set('cariche', formData.get('cariche_standard'));
                // standard non ha ricarica_massima
                break;
            // arma usa già i nomi originali
        }

        fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + '=saveObj', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Oggetto salvato con successo!', 'success');
                    if (modalObj) modalObj.style.display = 'none';
                    window.location.reload();
                } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    });
}

function deleteObj(idOggetto) {
    if (confirm('Sei sicuro di voler cancellare?') && idOggetto > 0) {
        fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + '=deleteObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_oggetto: idOggetto })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
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
/***********    FINE    GESTIONE OGGETTI  *********************/

/***********    GESTIONE SHOP  *********************/
// Motore di ricerca shop
function buyObj(user, id_oggetto, costo, budget) {
    const qty = document.getElementById(`qty_${id_oggetto}`).value;

    if (qty == '' || qty == 0) {
        showNotification('Inserisci una quantità valida da comprare.', 'error');
        return;
    }

    if (confirm('Stai acquistando ' + qty + ' pezzi di questo oggetto per un costo totale di ' + (costo * qty) + ' CTY. Vuoi procedere?')) {
        // Se non ho abbastanza soldi per effettuare l'acquisto
        if ((budget - (costo * qty)) < 0) {
            showNotification('Fondi insufficienti per questo acquisto.', 'error');
            return;
        }

        fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + '=buyObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_oggetto: id_oggetto,
                user: user,
                costo: costo,
                qty: qty
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
                } else showNotification(data.message, data.error);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}

function sellObj(user, id_oggetto) {
    const qty = document.getElementById(`qty_${id_oggetto}`).value;

    if (qty == '' || qty == 0) {
        showNotification('Inserisci una quantità valida da vendere.', 'error');
        return;
    }

    if (confirm('Stai vendendo ' + qty + ' pezzi di questo oggetto. Vuoi procedere?')) {
        fetch(ns_oggetti.api_file + '?' + ns_oggetti.param + '=sellObj', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_oggetto: id_oggetto,
                user: user,
                qty: qty
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, data.success);
                    window.location.reload();
                } else showNotification(data.message, 'error');
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore nel salvataggio', 'error');
            });
    }
}
/***********    FINE    GESTIONE SHOP  *********************/

/****************   Al completo caricamento del DOM della pagina... **********************************/
document.addEventListener('DOMContentLoaded', function () {
    // Filtro tipo oggetto
    const selectTipoObj = document.getElementById("selectTipoObj");
    if (selectTipoObj) {
        document.getElementById("selectTipoObj").addEventListener("change", function () {
            var tipo = document.getElementById('filtroTipoObj');
            tipo.value = 'tipo';
            tipo.checked = true;
            tipo.dispatchEvent(new Event("change"));
        });
    }
    // Filtro categoria oggetto
    const selectCategoriaObj = document.getElementById("selectCategoriaObj");
    if (selectCategoriaObj) {
        document.getElementById("selectCategoriaObj").addEventListener("change", function () {
            var categoria = document.getElementById('filtroCategoriaObj');
            categoria.value = 'categoria';
            categoria.checked = true;
            categoria.dispatchEvent(new Event("change"));
        });
    }

    // Variabili globali oggetto
    modalObj = document.getElementById('oggettoModal');

    // Gestione cambio categoria
    const categoriaObj = document.getElementById('categoriaObj');
    if (categoriaObj) categoriaObj.addEventListener('change', gestioneCampiDinamici(categoriaObj));

    // Event listeners per chiusura modale
    const closeObj = document.getElementById('closeObj');
    if (closeObj) closeObj.addEventListener('click', function () {
        if (modalObj) modalObj.style.display = 'none';
    });
    window.addEventListener('click', function (e) {
        if (e.target == modalObj) {
            modalObj.style.display = 'none';
        }
    });

    // Preview immagine
    const img_oggetto = document.getElementById("img_oggetto");
    if (img_oggetto) {
        document.getElementById('img_oggetto').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('previewImgObj').src = e.target.result;
                    document.getElementById('imagePreviewObj').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
    /***********    FINE    GESTIONE OGGETTI  *********************/

    /***********    GESTIONE SHOP  *********************/
    // Motore di ricerca shop
    const inputSearchShop = document.getElementById("searchShop");
    const listaShop = document.getElementById("shop");

    if (inputSearchShop && listaShop) {
        inputSearchShop.addEventListener("keyup", function () {
            const allShop = listaShop.getElementsByClassName("prodotto");
            const filtroShop = inputSearchShop.value.toLowerCase();

            for (let i = 0; i < allShop.length; i++) {
                let testoSearchShop = allShop[i].textContent.toLowerCase();
                allShop[i].style.display = testoSearchShop.includes(filtroShop) ? "" : "none";
            }
        });
    }
    /***********    FINE    GESTIONE SHOP  *********************/
});