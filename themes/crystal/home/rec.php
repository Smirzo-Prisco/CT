<?php
// Includi il file di configurazione
require_once 'config.inc.php';

// Includi la protezione se attivata
if ($PARAMETERS['settings']['protection'] == 'ON'){
    require 'protezione.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Crystal Tokyo GDR</title>
    <!-- Collegamento ai fogli di stile CSS -->
    <link rel="stylesheet" href="../themes/crystal/home/login_provvisorio.css">
    <!-- Collegamento al file JavaScript -->
    <script src="../themes/crystal/registrazione/registrazione.js" defer></script>
</head>
<body>

    <header>
        <!-- Intestazione del sito -->
    </header>

    <div class="main-content">
        <!-- Contenuto principale della pagina -->
        <div id="registrationModal" class="custom-content registration-modal">
            <div class="registration-custom-box">
                <span class="close">&times;</span>
                <h2 style="font-size: 24px;">Registrazione</h2>
                <div id="registrationContent">
                <?php /**** Fase 0 ****/
                  if (isset($_POST['fase']) === false)
                  { ?>
                  
                  
                  
                    <form id="registrationForm" action="../themes/crystal/home/registrazione.php" method="post">
                    <!-- Campi per la registrazione -->
                    <center><h4>Fase 1: Inserisci il nome e il cognome del tuo personaggio, scegli lo spirito e il mestiere</h4></center>
                    <div class="input-group registration-input-group">
                            <label for="nomePersonaggio">Nome Personaggio</label>
                            <input type="text" id="nomePersonaggio" name="nomePersonaggio" placeholder="Inserisci il nome del personaggio" required>
                        </div>
                        <div class="input-group registration-input-group">
                            <label for="cognomePersonaggio">Cognome Personaggio</label>
                            <input type="text" id="cognomePersonaggio" name="cognomePersonaggio" placeholder="Inserisci il cognome del personaggio" required>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="emailPersonaggio">E-Mail</label>
                            <input type="text" id="cognomePersonaggio" name="cognomePersonaggio" placeholder="Inserisci il tuo indirizzo mail" required>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="spiritoPersonaggio">Spirito del Personaggio</label>
                            <select id="generePersonaggio" name="generePersonaggio" required>
                                <option value="">Seleziona il genere del personaggio</option>
                                <option value="maschio">Maschio</option>
                                <option value="femmina">Femmina</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="generePersonaggio">Mestiere del Personaggio</label>
                            <select id="mestierePersonaggio" name="generePersonaggio" required>
                                <option value="">Seleziona il genere del personaggio</option>
                                <option value="maschio">Maschio</option>
                                <option value="femmina">Femmina</option>
                                <option value="altro">Altro</option>
                            </select>
                        </div>
                        
                        <div class="input-group registration-input-group">
    <input type="checkbox" id="accettoTermini" name="accettoTermini" required>
    <label for="accettoTermini">ACCETTO i Termini e Condizioni</label>
</div>

<div class="input-group registration-input-group">
    <input type="checkbox" id="consensoTrattamentoDati" name="consensoTrattamentoDati" required>
    <label for="consensoTrattamentoDati">ESPRIMO IL CONSENSO al trattamento dei miei dati personali secondo quanto espresso dalla Privacy Policy e Cookie Policy</label>
</div>

    <input type="hidden" name="fase" value="2">
    <div class="input-group">
        <button id="passaFaseDueButton" type="button">Passa alla Fase 2</button>
    </div>
</form>



                    <!-- Altri campi per le fasi successive, se necessario -->
                    <?php }  if ($_POST['fase'] == 2) { 
                    echo "<script>goToPhaseTwo();</script>";?>
        <!-- Fase 2: Visualizzazione dati inseriti -->
        <h4>FASE 2</h4>
        <!-- Aggiungi qui la visualizzazione dei dati inseriti nella fase 1 -->
    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <!-- Footer del sito -->
    </footer>

</body>
</html>