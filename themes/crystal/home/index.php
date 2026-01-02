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
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Crystal Tokyo GDR</title>
        <link rel="stylesheet" href="../themes/crystal/home/login_index_nuova.css">
        <!-- Aggiungi il nuovo nome del file JavaScript -->
        <script src="../themes/crystal/home/script_login_nuova.js" defer></script>
    </head>
    <body>
        <header>
            <div class="logo">Crystal Tokyo GDR</div>
            <nav>
                <ul class="navigation">
                    <li><a href="#" id="loginBtn">Login</a></li>
                    <li><a href="index.php?page=iscrizione" target="_blank">REGISTRAZIONE</a></li>
                    <li><a href="../documentazione_main.php" target="_blank">AMBIENTAZIONE</a></li>
                    <!-- <li><a href="#">PRIVACY</a></li> -->
                    <li><a href="#" id="reportLink">SEGNALA</a></li>
                </ul>
            </nav>
        </header>
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
        <div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:rgba(255,255,255,0.2); padding:10px; border-radius:8px; text-align:center; max-width:250px;">
            <a href="https://globalsumudflotilla.org/" target="_blank" style="display:block; width:120px; height:80px; margin:0 auto 10px auto; background-image:url('https://upload.wikimedia.org/wikipedia/commons/0/00/Flag_of_Palestine.svg'); background-size:cover; background-position:center; border:2px solid #000; border-radius:6px;">
            </a>
            <div style="font-size:12px; color:white; font-weight:bold; line-height:1.2;">
                Crystal Tokyo GDR supporta la Palestina<br>e la Global Sumud Flottilla
            </div>
        </div>
    </body>
</html>