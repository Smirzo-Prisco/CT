<?php
require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON'){
    require 'protezione.php';
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="Content-Type" content="text/html;">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="description" content="Scopri Crystal Tokyo GDR, un GDR play by chat gratuito con combattimenti a dadi, famiglie magiche, crescita del personaggio e gioco narrativo condiviso.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="canonical" href="https://crystaltokyo.it/">
        
        <link rel="icon" type="image/x-icon" href="imgs/favicon.ico">
        <title>Crystal Tokyo GDR</title>
        <link rel="stylesheet" href="../themes/crystal/home/login_index_nuova.css?<?=time()?>">
        <!-- Aggiungi il nuovo nome del file JavaScript -->
        <script src="../themes/crystal/home/script_login_nuova.js" defer></script>
    </head>
    <body>
        <header>
            <div class="logo">Crystal Tokyo GDR</div>
            <nav>
                <ul class="navigation">
                    <li><a href="#" id="loginBtn">Login</a></li>
                    <li><a href="#" id="registrazioneBtn">REGISTRAZIONE</a></li>
                    <li><a href="../documentazione_main.php" target="_blank">AMBIENTAZIONE</a></li>
                    <li><a href="/docs/il_gioco.html">Il Gioco</a></li>
                    <li><a href="#" id="reportLink">SEGNALA</a></li>
                </ul>
            </nav>
        </header>

        <section class="hero">
            <div class="overlay-container">
                <div class="content-box">
                    <h1>Crystal Tokyo – GDR play by chat</h1>
                    <p>Crystal Tokyo è un gioco di ruolo online play by chat gratuito, attivo da oltre vent’anni, ambientato in un mondo urban fantasy ricco di trame e interazioni. Il gioco utilizza un sistema a dadi per la gestione dei conflitti e offre sette famiglie magiche diverse, suddivise in tre correnti, ognuna con abilità e caratteristiche uniche.

I giocatori possono creare il proprio personaggio scegliendo una famiglia e un mestiere narrativo, partecipare alle trame e farlo crescere attraverso punti esperienza e punti shin, ottenuti rispettivamente dalla frequenza e dalla qualità del gioco. Crystal Tokyo è pensato sia per chi si avvicina per la prima volta ai GDR play by chat, sia per chi cerca un’esperienza profonda, collaborativa e orientata alla narrazione.</p>
                </div>
            </div>
        </section>

        <!-- MODALE LOGIN -->
        <div class="custom-content" id="loginContent" style="display: none;">
            <div class="custom-box">
                <span class="close">&times;</span>
                <!-- <h2>Effettua il login</h2> -->
                <!-- Modulo di login -->
                <form class="myForm" method="post" action="login.php" id="do_login">
                    <div class="input-group">
                        <label for="nomePersonaggio">NOME PERSONAGGIO</label>
                        <input type="text" name="login1" id="username" placeholder="Nome personaggio" required>
                    </div>
                    <div class="input-group">
                        <label for="password">PASSWORD</label>
                        <input type="password" id="password" name="pass1" placeholder="Inserisci la tua password" required>
                    </div>
                    <div class="input-group">
                        <button type="submit" class="submit-button">Entra nel gioco</button>
                        <br><br>
                        <center>
                            <a href="#" id="passwordRecoveryLink" class="password-recovery-link">Recupero password</a>
                        </center>
                    </div>
                </form>

                <!-- Modulo di recupero password -->
                <div id="passwordRecoveryDiv" style="display: none;">
                    <form id="passwordRecoveryForm" action="index3.php" method="post">
                        <div class="input-group">
                            <label for="email">Indirizzo Email:</label>
                            <input type="email" id="recoveryEmail" name="recoveryEmail" required>
                        </div>
                        <input type="hidden" name="op" value="recupera_pass" />
                        <button type="submit" class="submit-button">Recupera password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODALE REGISTRAZIONE -->
        <div id="registrazioneContent" class="custom-content" style="display:none;">
            <div class="custom-box reg-box">
                <span id="closeRegModal" class="close">&times;</span>
                <h2>Registrazione</h2>

                <form id="regForm" method="post" action="iscrizione.php">
                    <input type="hidden" name="fase" value="2">
                    <input type="hidden" name="genere" value="m">

                    <div class="input-group">
                        <label>E-MAIL</label>
                        <input type="email" name="email" placeholder="Inserisci la tua e-mail" required>
                        <small>Inserire un'e-mail valida, altrimenti non sarà possibile completare la registrazione</small>
                    </div>

                    <div class="input-group">
                        <label>NOME</label>
                        <input type="text" name="nome" placeholder="Nome personaggio" required maxlength="20">
                        <small>Max 20 caratteri, solo lettere, iniziale maiuscola</small>
                    </div>

                    <div class="input-group">
                        <label>COGNOME</label>
                        <input type="text" name="cognome" placeholder="Cognome personaggio" maxlength="20">
                        <small>Max 20 caratteri, solo lettere, iniziale maiuscola</small>
                    </div>

                    <div class="input-group">
                        <label>SPIRITO (<a href="/statuti/spiriti/spiriti.html" target="_blank">?</a>)</label>
                        <select name="razza" id="regRazza" required>
                            <option value="">Caricamento...</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>MESTIERE (<a href="/statuti/mestieri/mestieri_info.html" target="_blank">?</a>)</label>
                        <select name="mestiere" id="regMestiere" required>
                            <option value="">Caricamento...</option>
                        </select>
                    </div>

                    <!-- Informazioni sul gioco -->
                    <div class="input-group tc-group">
                        <button type="button" id="infoToggleBtn" class="tc-link">&#9432; Informazioni sul gioco</button>
                        <div id="infoContent" class="tc-content" style="display:none;">
                            <div id="infoText"><em>Caricamento...</em></div>
                        </div>
                    </div>

                    <!-- Termini e condizioni -->
                    <div class="input-group tc-group">
                        <button type="button" id="tcToggleBtn" class="tc-link">&#9432; Termini e Condizioni</button>
                        <div id="tcContent" class="tc-content" style="display:none;">
                            <div id="tcText"><em>Caricamento...</em></div>
                        </div>
                        <div class="tc-check-row" style="margin-top:8px;">
                            <input type="checkbox" id="regTc" required>
                            <label for="regTc">Ho letto e accetto i Termini e Condizioni</label>
                        </div>
                    </div>

                    <div class="input-group">
                        <button type="submit" class="submit-button">Avanti &rsaquo;</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODALE SEGNALA -->
        <div id="reportContent" class="custom-content" style="display: none;">
            <div class="custom-box">
                <span id="closeReportModal" class="close">&times;</span>
                <h2>Segnala un problema</h2>
                <form id="reportForm" method="post" action="invio_segnalazione.php">
                    <div class="input-group">
                        <label for="email">Indirizzo Email</label>
                        <input type="email" id="email" name="email" placeholder="Inserisci il tuo indirizzo email" required>
                    </div>
                    <div class="input-group">
                        <label for="problem">Descrivi il problema</label>
                        <textarea id="problem" name="problem" rows="4" placeholder="Descrivi il problema" required></textarea>
                    </div>
                    <div class="input-group">
                        <button type="submit" class="submit-button">Invia</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bandiera Palestina con scritta -->
        <div id="bottomBanner" style="position:fixed; bottom:20px; right:20px; z-index:9999; background:rgba(255,255,255,0.2); padding:10px; border-radius:8px; text-align:center; max-width:250px;">
            <a href="https://globalsumudflotilla.org/" target="_blank" style="display:block; width:120px; height:80px; margin:0 auto 10px auto; background-image:url('https://upload.wikimedia.org/wikipedia/commons/0/00/Flag_of_Palestine.svg'); background-size:cover; background-position:center; border:2px solid #000; border-radius:6px;">
            </a>
            <div style="font-size:12px; color:white; font-weight:bold; line-height:1.2;">
                Crystal Tokyo GDR supporta la Palestina<br>e la Global Sumud Flottilla
            </div>
        </div>
    </body>
</html>