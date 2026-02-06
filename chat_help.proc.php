<?php
require 'header.inc.php'; /*Header comune*/
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <meta charset="utf-8">
        <title>Chat Help - Comandi</title>

        <style>
            /* ===== RESET & BASE STYLES ===== */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                background: linear-gradient(135deg, #0f111d 0%, #1a1a2e 100%);
                font-family: "DejaVu Serif", Georgia, serif;
                color: #e0e0e0;
                line-height: 1.6;
                padding: 15px;
                min-height: 100vh;
            }
            
            /* ===== TYPOGRAPHY ===== */
            .intestazione {
                display: block;
                font-size: 1.8rem;
                text-transform: uppercase;
                color: #ce846f;
                font-weight: bold;
                text-shadow: -2px 2px 3px rgba(0, 0, 0, 0.7);
                padding-bottom: 10px;
                border-bottom: 2px dashed #ce846f;
                text-align: center;
                margin: 0 auto 30px;
                max-width: 600px;
                letter-spacing: 1px;
            }
            
            .section-title {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 1.2rem;
                color: #ce846f;
                text-transform: uppercase;
                font-weight: bold;
                text-shadow: -2px 2px 3px rgba(0, 0, 0, 0.7);
                padding: 8px 0;
                border-bottom: 1px dashed rgba(206, 132, 111, 0.4);
                margin-bottom: 15px;
            }
            
            .section-title::before {
                content: "›";
                font-size: 1.4rem;
                color: #b56d57;
            }
            
            .text-content {
                color: #a8a8a8;
                font-size: 0.95rem;
                line-height: 1.7;
                margin-bottom: 20px;
                padding: 0 5px;
            }
            
            .text-content b {
                color: #ce846f;
                font-weight: bold;
            }
            
            /* ===== LAYOUT CONTAINERS ===== */
            .help-container {
                max-width: 1000px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .commands-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
                gap: 25px;
                margin-bottom: 40px;
            }
            
            @media (max-width: 768px) {
                .commands-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            /* ===== COMMAND CARDS ===== */
            .command-card {
                background: rgba(20, 21, 35, 0.85);
                border-radius: 10px;
                padding: 25px;
                border: 1px solid rgba(206, 132, 111, 0.2);
                transition: all 0.3s ease;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                height: 100%;
            }
            
            .command-card:hover {
                transform: translateY(-3px);
                border-color: rgba(206, 132, 111, 0.4);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            }
            
            /* ===== IMAGE STYLES ===== */
            .examples-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .example-img {
                width: 100%;
                height: auto;
                border-radius: 6px;
                border: 2px solid rgba(206, 132, 111, 0.3);
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
                transition: transform 0.3s ease;
            }
            
            .example-img:hover {
                transform: scale(1.02);
                border-color: rgba(206, 132, 111, 0.5);
            }
            
            /* ===== DIVIDER ===== */
            .divider {
                text-align: center;
                color: #8f8f8f;
                margin: 30px 0;
                position: relative;
            }
            
            .divider::before,
            .divider::after {
                content: "";
                position: absolute;
                top: 50%;
                width: 40%;
                height: 1px;
                background: linear-gradient(90deg, transparent, #8f8f8f, transparent);
            }
            
            .divider::before {
                left: 0;
            }
            
            .divider::after {
                right: 0;
            }
            
            /* ===== UTILITY CLASSES ===== */
            .mb-10 { margin-bottom: 10px; }
            .mb-15 { margin-bottom: 15px; }
            .mb-20 { margin-bottom: 20px; }
            .mb-30 { margin-bottom: 30px; }
            
            .text-center { text-align: center; }
            .text-italic { font-style: italic; }
            
            /* ===== SCROLLBAR STYLING ===== */
            ::-webkit-scrollbar {
                width: 10px;
            }
            
            ::-webkit-scrollbar-track {
                background: rgba(20, 21, 35, 0.8);
            }
            
            ::-webkit-scrollbar-thumb {
                background: rgba(206, 132, 111, 0.5);
                border-radius: 5px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: rgba(206, 132, 111, 0.7);
            }
        </style>
    </head>
    <body>

        <div class="help-container">
            <!-- HEADER -->
            <h1 class="intestazione">Istruzioni per la Chat</h1>
            
            <!-- COMMANDS GRID -->
            <div class="commands-grid">
                <!-- ROLE -->
                <div class="command-card">
                    <h2 class="section-title">Role</h2>
                    <p class="text-content">
                        Clicca su "Avvia role" oppure su "Join" per avviare la giocata o aggiungerti ad una giocata in corso.
                        L'icona del pannello chat compare soltanto se sei coinvolto nella giocata attiva.
                        L'cona degli utenti permette di visualizzare tutti i personaggi giocanti.
                        Alla fine di ogni giocata, ricordarsi di effettuare l'uscita dalla role attraverso l'apposita icona.
                        Per ogni lancio occorre sempre specificare almeno un bersaglio.
                        La quantità di bersagli attaccabili aumenta con il livello dell'abilità utilizzata.
                    </p>
                </div>

                <!-- TURNI -->
                <div class="command-card">
                    <h2 class="section-title">Turni</h2>
                    <p class="text-content">
                        I turni vengono calcolati automaticamente dal sistema. Un turno viene chiuso automaticamente quando tutti i giocatori coinvolti hanno confermato la conclusione del proprio turno.
                        Al termine di ogni turno, il sistema manda un messaggio in chat con il riepilogo di eventuali attacchi e/o difese.
                        Non è consentito lanciare un qualsiasi tipo di abilità nel primo turno, a meno che non sia un master a consentirlo
                        Durante il turno è concesso lanciare una sola abilità di attacco e, se si viene attaccati successivamente alla propria azione, è possibile lanciare un'abilità di difesa, ma il sistema impedirà un attacco nel turno successivo. Concederà soltanto il lancio di una ulteriore abilità difensiva continuando ad impedire il lancio di attacchi nel turno seguente.
                    </p>
                </div>

                <!-- DANNI -->
                <div class="command-card">
                    <h2 class="section-title">Danni</h2>
                    <p class="text-content">
                        Calcolo del danno: attacco meno difesa per il moltiplicatore del livello d'attacco (consultabile nella scheda alla voce "Livello").
                        Durante uno scontro è consentito lanciare soltanto abilità di attacco e di difesa.
                        Non c'è bisogno di lanciare dadi relativi alle caratteristiche, poiché il sistema calcola ogni danno a fine turno sulla base delle abilità lanciate.
                        In caso di attacco su bersaglio multiplo, l'eventuale danno viene diviso per tutti i bersagli.
                    </p>
                </div>

                <!-- AZIONE -->
                <div class="command-card">
                    <h2 class="section-title">Azione</h2>
                    <p class="text-content">
                        Scrivere normalmente il contenuto della propria azione nella barra in basso specificando il tag nell'apposito campo.
                    </p>
                </div>
                
                <!-- DIALOGHI -->
                <div class="command-card">
                    <h2 class="section-title">Dialoghi</h2>
                    <p class="text-content">
                        Per il parlato, è necessario inserire il testo dalle parentesi quadre <b>[]</b> 
                        o tra i simboli di maggiore/minore <b>&lt;&gt;</b>.
                    </p>
                </div>
                
                <!-- SUSSURRI -->
                <div class="command-card">
                    <h2 class="section-title">Sussurri</h2>
                    <p class="text-content">
                        I sussurri possono essere letti solo da chi riceve il sussurro. 
                        Scrivere normalmente la frase da sussurrare preceduta da <b>@nomedestinatario@</b>.
                    </p>
                </div>
                
                <!-- DADI PURI -->
                <div class="command-card">
                    <h2 class="section-title">Dadi Puri</h2>
                    <p class="text-content">
                        Quando richiesto dal Master (o per utilizzare determinate skill) bisogna lanciare dei dadi. 
                        Si possono lanciare dadi con qualsiasi numero di facce fino a mille. 
                        Per farlo, scrivere <b>#d</b> seguito dal numero di facce desiderate.
                    </p>
                </div>
                
                <!-- MESSAGGIO OFF -->
                <div class="command-card">
                    <h2 class="section-title">Messaggio OFF</h2>
                    <p class="text-content">
                        È possibile inviare fino ad un <b>massimo di 3 messaggi OFF</b> in chat, 
                        solo se si è precedentemente già scritto all'interno della stessa. 
                        Per inviare un messaggio off, far precedere il testo dal simbolo <b>/</b>.
                    </p>
                </div>
                
                <!-- MASTER -->
                <div class="command-card">
                    <h2 class="section-title">Master</h2>
                    <p class="text-content">
                        Il Master è un utente che ricopre la funzione di narratore e/o arbitro all'interno del GdR. 
                        Per inviare il Master Screen (MS), far precedere al testo il simbolo <b>=</b>. 
                        Solo i Master possono utilizzare questo comando.
                    </p>
                </div>
                
                <!-- MODERATORI -->
                <div class="command-card">
                    <h2 class="section-title">Moderatori</h2>
                    <p class="text-content">
                        I Moderatori vigilano le giocate, imponendosi solo quando necessario. 
                        Per inviare lo Screen della Moderazione, far precedere al testo il simbolo <b>%</b>. 
                        Solo i Moderatori possono utilizzare questo comando.
                    </p>
                </div>
                
            </div> <!-- Fine commands-grid -->
        </div> <!-- Fine help-container -->
    </body>
</html>