<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheda Personaggio Unica</title>
    <link href="themes/crystal/documentazione.css" rel="stylesheet" type="text/css">
    <style>
        body {
            background-color: #14172a;
            font-family: Lato, sans-serif;
            color: #8f8f8f;
            overflow: hidden;
        }

        /* Container principale */
        .container_new {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            background: url('imgs/sfondo_parallasse.jpg') fixed no-repeat center;
            background-size: cover;
            height: 100vh;
        }

        /* Colonna Sinistra: Avatar */
        .left_column {
            width: 300px;
            height: 380px; /* Altezza ridotta */
            margin-right: 20px;
            margin-top: -10px;
            position: relative;
        }

        /* Effetto Torcia soffuso sull'Avatar */
        .profile_image {
            width: 300px;
            height: 380px; /* Altezza ridotta */
            border-radius: 15px;
            background-image: url("https://i.imgur.com/hRkBDqL.gif"); /* Avatar fornito */
            background-size: cover;
            background-position: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(206, 132, 111, 0.7);
        }

        .torch_effect {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            animation: torchMove 8s infinite ease-in-out;
        }

        @keyframes torchMove {
            0% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        /* Colonna Centrale: Profilo e Timeline */
        .center_column {
            width: 40%;
            height: 320px; /* Altezza ridotta per allinearsi con la linea rossa */
            background-color: rgba(28, 33, 55, 0.85);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
            transform-style: preserve-3d; /* Necessario per l'effetto 3D */
            transition: transform 0.6s ease-in-out;
            margin-right: 20px;
        }

        /* Pulsante per ruotare il div del profilo */
        .rotate_button {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #ce846f;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .rotate_button:hover {
            color: #ffffff;
        }

        /* Contenuto del Profilo */
        .profile_content {
            backface-visibility: hidden;
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .profile_label {
            font-weight: bold; /* Imposta il grassetto */
            margin-right: 5px; /* Spazio tra l'etichetta e il testo */
        }

        /* Stile per il testo del profilo */
        .profile_content p {
            text-align: left; /* Allinea il testo a sinistra */
            margin: 5px 0; /* Spazio tra le righe */
            font-size: 12px;
        }

        .section_title {
            font-size: 22px;
            color: #ce846f;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        /* Effetto rotazione */
        .center_column.rotated {
            transform: rotateY(180deg);
        }

        /* Timeline visibile sul retro */
        .timeline_content {
            backface-visibility: hidden;
            position: absolute;
            width: 100%;
            height: 100%;
            transform: rotateY(180deg); /* Rotazione del contenuto ma non testo */
        }

        /* Stile per la timeline */
        .timeline {
            position: relative;
            margin-top: 10px; /* Riduce lo spazio superiore */
            padding: 10px 0;  /* Riduce il padding interno */
            width: 100%;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 2px;
            background-color: #ce846f;
            top: 0;
            bottom: 0;
            left: 30px; /* Posizione della linea verticale */
            margin: auto;
        }

        /* Stile per i punti della timeline */
        .timeline_point {
            position: relative;
            width: 10px; /* Dimensione ridotta dei punti */
            height: 10px; /* Dimensione ridotta dei punti */
            background-color: #ce846f;
            border: 1px solid #8f8f8f; /* Spessore bordo ridotto */
            border-radius: 50%;
            margin: 10px 0; /* Spazio ridotto tra i punti */
            left: 25px; /* Allinea i punti con la linea */
            box-shadow: 0 0 10px rgba(206, 132, 111, 0.7);
        }

        /* Testo associato ai punti della timeline */
        .timeline_text {
    display: flex;
    flex-wrap: wrap; /* Permette al testo di andare a capo */
    align-items: flex-start; /* Allinea il contenuto all'inizio */
    margin-left: 50px; /* Sposta il contenuto a destra rispetto ai pallini */
    width: calc(100% - 60px); /* Limita la larghezza del div */
    color: #8f8f8f;
    font-size: 12px;
    line-height: 1.2;
    text-align: left;
}

/* Stile per la data */
.timeline_text strong {
    flex: 0 0 auto; /* Fissa la larghezza della data */
    margin-right: 5px; /* Spazio tra la data e il testo */
    font-weight: bold;
    color: #ce846f;
}

/* Stile per il testo successivo alla data */
.timeline_text span {
    flex: 1; /* Il testo successivo occupa tutto lo spazio rimanente */
    white-space: normal; /* Permette al testo di andare a capo */
}



/* Aggiungi un margine superiore ai punti */
.timeline_point + .timeline_text {
    margin-top: -22px; /* Posiziona il testo più vicino ai punti */
}

        /* Colonna Destra: Statistiche */
        .right_column {
            width: 30%;
            height: 320px; /* Altezza ridotta per allinearsi con la linea rossa */
            background-color: rgba(28, 33, 55, 0.85);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Barre delle statistiche */
        .stats_bar[data-value]:before {
            content: ''; /* Elimina il testo visualizzato */
         }
         
         .stats_bar {
            background-color: #8f8f8f;
            height: 25px;
            margin: 10px 0;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            position: relative;
        }
        
        .stats_value {
           height: 100%;
           background-color: #ce846f;
           text-align: center;
           color: #14172a;
           line-height: 25px;
           font-size: 14px;
           width: 0; /* Inizia con larghezza 0 */
           transition: width 1.5s ease-in-out; /* Transizione animata per riempimento */
       }

/* Riempimento dinamico */
.stats_bar[data-value] .stats_value {
    animation: fillBar 1.5s ease forwards;
}




 /* Div per le Note del Fato e Particolari in basso */
        .bottom_div {
            width: 60%; /* Ridotto ulteriormente */
            margin-top: 10px; /* Ridotto il margine */
            background-color: #1c2137;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .bottom_div select {
            width: 100%;
            padding: 10px;
            background-color: #14172a;
            border: 2px solid #ce846f;
            color: #ce846f;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .bottom_div p {
            margin-top: 10px;
            color: #8f8f8f;
        }
        
    </style>
</head>
<body>

<div class="container_new">
    <!-- Colonna Sinistra: Avatar -->
    <div class="left_column">
        <div class="profile_image">
            <div class="torch_effect"></div> <!-- Effetto luce "torcia" sull'avatar -->
        </div>
    </div>

    <!-- Colonna Centrale: Profilo e Timeline -->
    <div class="center_column" id="profileDiv">
        <div class="rotate_button" onclick="toggleRotation()">↺</div>

        <!-- Profilo (lato frontale) -->
        <div class="profile_content">
            <div class="section_title">Profilo</div>
            <p><span class="profile_label">Età:</span> 31</p>
            <p><span class="profile_label">Nato a:</span> Tokyo</p>
            <p><span class="profile_label">Spirito:</span> Spirito di Saturno</p>
            <p><span class="profile_label">Famiglia:</span> Cittadino</p>
            <p><span class="profile_label">Ruolo:</span> Senatore Imperiale</p>
            
            <br><br>
            
            <div class="section_title">PUNTI</div>
            <p><span class="profile_label">Punti Salute:</span> 31</p>
            <p><span class="profile_label">Punti Integrità:</span> Tokyo</p>
            <p><span class="profile_label">Punti Notorietà:</span> Spirito di Saturno</p>
            <p><span class="profile_label">Punti Shin:</span> Cittadino</p>
        </div>
        
        <!-- Timeline (lato posteriore) -->
        <div class="timeline_content">
            <div class="section_title">Timeline</div>
            
            <div class="timeline">
    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>30/09/2024:</strong> <span>Iscrizione del Personaggio.</span></div>

    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>01/10/2024:</strong> <span>Ingresso nella Via della Setta dei Miracoli.</span></div>

    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>10/10/2024:</strong> <span>Ingresso nel mestiere della Corte.</span></div>

    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>20/10/2024:</strong> <span>Partecipazione alla Quest "<i>Nome quest</i>".</span></div>

    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>30/10/2024:</strong> <span>Ultima Giocata al Faro.</span></div>

    <div class="timeline_point"></div>
    <div class="timeline_text"><strong>01/11/2024:</strong> <span>Ultimo Login.</span></div>
</div>

        </div>
    </div>

    <!-- Colonna Destra: Statistiche -->
<div class="right_column">
    <div class="section_title">Statistiche</div>
    <div class="stats_bar" data-value="50">
        <div class="stats_value">Forza: 50</div>
    </div>
    <div class="stats_bar" data-value="40">
        <div class="stats_value">Potere: 40</div>
    </div>
    <div class="stats_bar" data-value="30">
        <div class="stats_value">Destrezza: 30</div>
    </div>
    <div class="stats_bar" data-value="30">
        <div class="stats_value">Mente: 30</div>
    </div>
    <div class="stats_bar" data-value="50">
        <div class="stats_value">Tempra: 50</div>
    </div>
</div>

<!-- Div in basso per Note del Fato e Particolari -->
    <div class="bottom_div">
        <select id="noteFatoSelect" onchange="showNoteContent(this)">
            <option value="note_fato">Note del Fato</option>
            <option value="particolari">Particolari</option>
        </select>
        <p id="note_fato" class="note_content">
            Le Note del Fato sono descrizioni aggiuntive che indicano particolari accaduti al personaggio o eventi che lo hanno segnato. Queste note possono includere stati d'animo, reazioni a determinate situazioni, o modifiche al carattere del personaggio a seguito di esperienze significative.
        </p>
        <p id="particolari" class="note_content" style="display: none;">
            I Particolari includono tratti unici del personaggio come tatuaggi, cicatrici, oggetti personali di grande valore affettivo, o eventi segreti che sono noti solo al personaggio stesso o a pochi altri. Possono anche includere informazioni che potrebbero influenzare le trame future del gioco.
        </p>
    </div>
    
    
</div>



<script>
    // Funzione per ruotare il div del profilo e visualizzare la timeline
    function toggleRotation() {
        const profileDiv = document.getElementById('profileDiv');
        profileDiv.classList.toggle('rotated');
    }
    
// Script per riempire dinamicamente le barre delle statistiche in base ai valori
document.querySelectorAll('.stats_bar').forEach(bar => {
    const value = bar.getAttribute('data-value'); // Ottiene il valore dal data-attribute
    const barFill = bar.querySelector('.stats_value');
    
    // Imposta la larghezza dopo un breve ritardo per far vedere l'animazione di riempimento
    setTimeout(() => {
        barFill.style.width = `${value}%`; // Applica la larghezza in base al valore
    }, 100); // Imposta un ritardo di 100ms per avviare l'animazione
});


// Funzione per mostrare il contenuto delle note a seconda dell'opzione selezionata nel dropdown
    function showNoteContent(selectElement) {
        var selectedValue = selectElement.value;
        var noteContents = document.querySelectorAll('.note_content');

        // Nascondi tutte le sezioni note
        noteContents.forEach(function(note) {
            note.style.display = 'none';
        });

        // Mostra solo la sezione selezionata
        document.getElementById(selectedValue).style.display = 'block';
    }
</script>

</body>
</html>
