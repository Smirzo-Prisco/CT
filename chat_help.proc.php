<?php
require 'header.inc.php'; /*Header comune*/
?>
<!DOCTYPE html>
<html lang="it">
    <head>
        <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
        <meta charset="utf-8">
        <title>Chat Help - Comandi</title>
        <link rel="stylesheet" href="themes/crystal/chat_help.css" type="text/css">
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
                        Nel turno è consentito lanciare solamente ciò che è disponibile dal pannello.
                        Quando tutti i partecipanti hanno inviato la loro azione e l'eventuale lancio, il sistema invia un sussurro con un pulsante che consente la chiusura del turno.
                        Il turno viene chiuso solamente quando tutti i partecipanti hanno cliccato sul pulsante di chiusura del turno, per dare la possibilità a tutti di lanciare un'eventuale scudo o attacco.
                        Quando il turno viene chiuso, il sistema invia automaticamente un riepilogo in chat.
                    </p>
                    <p class="text-content">
                        <b>Esempio 1</b><br>
                        - A attacca B
                        <br>- B descrive una difesa e attacca A
                        <br>- Il sistema manda ai partecipanti la richiesta di chiusura turno
                        <br>- A clicca sul pulsante di chiusura turno
                        <br>- B clicca sul pulsante di chiusura turno
                        <br>- Il sistema manda il riepilogo del turno in chat
                    </p>
                    <p class="text-content">
                        <b>Esempio 2</b><br>
                        - A attacca B
                        <br>- B descrive una difesa e attacca C
                        <br>- C descrive una difesa e attacca A
                        <br>- D lancia uno scudo per difendere A
                        <br>- Il sistema manda ai 4 partecipanti la richiesta di chiusura turno
                        <br>- Prima di chiudere il turno, A lancia uno scudo per difendere se stesso
                        <br>- Tutti cliccano sul pulsante di chiusura turno
                        <br>- Il sistema manda il riepilogo del turno in chat
                        <br><br>Turno successivo...<br><br>
                        - Il sistema impedisce ad A di lanciare un attacco, poiché ha già utilizzato un attacco e uno scudo nel turno precedente. Ma può continuare a lanciare uno scudo rinunciando nuovamente all'attacco nel turno successivo
                        <br>- B può continuare ad attaccare
                        <br>- C può continuare ad attaccare
                        <br>- D può continuare ad attaccare perché nel turno precedente ha utilizzato il suo lancio per lanciare uno scudo, quindi ha a disposizione un nuovo lancio per il turno corrente
                    </p>
                </div>

                <!-- DANNI -->
                <div class="command-card">
                    <h2 class="section-title">Danni</h2>
                    <p class="text-content">
                        Calcolo del danno: attacco meno difesa per il moltiplicatore del livello d'attacco (Vedi tabella successiva).
                        Durante uno scontro è consentito lanciare soltanto abilità di attacco e di difesa.
                        Non c'è bisogno di lanciare dadi relativi alle caratteristiche, poiché il sistema calcola ogni danno a fine turno sulla base delle abilità lanciate.
                        In caso di attacco su bersaglio multiplo, l'eventuale danno viene diviso per tutti i bersagli.
                    </p>
                    <table style="width:100%; margin-top:5px; border-collapse: collapse; text-align:center;">
                        <tr><th class="form-group form-column">Livello</th><th class="form-group form-column">Moltiplicatore</th></tr>
                        <?php
                        $soglie = gdrcd_query("SELECT * FROM gilda_soglie ORDER BY livello", 'result');
                        foreach ($soglie as $soglia): ?>
                            <tr><td><?=$soglia['livello']?></td><td><?=$soglia['danno']?></td></tr>
                        <?php endforeach; ?>
                    </table>
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