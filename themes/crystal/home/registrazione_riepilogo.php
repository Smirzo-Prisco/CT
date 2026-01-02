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
    <title>Riepilogo Registrazione</title>
    <link rel="stylesheet" href="path/to/your/login_provvisorio.css">
    <style>
        /* Stili per il box del riepilogo */
        .custom-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%; /* Larghezza del box */
            max-width: 600px; /* Larghezza massima */
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            z-index: 1000;
        }

        .custom-content h2 {
            font-size: 24px;
            color: #333;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .registration-custom-box {
            /* Stili per il box interno al riepilogo */
        }

        .registration-input-group {
            margin-bottom: 15px;
        }

        /* Aggiungi altri stili necessari qui */
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Contenuto principale della pagina -->
        <div id="registrationModal" class="custom-content registration-modal">
            <div class="registration-custom-box">
                <span class="close">&times;</span>
                <h2 style="font-size: 24px;">Riepilogo Registrazione</h2>
                <div id="registrationContent">
                    <!-- Contenuto del riepilogo qui -->
                    <div class="input-group registration-input-group">
                        <label for="nomePersonaggio">Nome Personaggio</label>
                        <span><?php echo htmlspecialchars($_POST['nome']); ?></span>
                    </div>
                    <!-- Aggiungi altri campi del riepilogo qui -->
                    <a href="#" onclick="history.back();">Torna indietro</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
