<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');

$destinatari = isset($_GET['destinatari']) ? gdrcd_filter('in', $_GET['destinatari']) : '';

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaggio Multiplo</title>
    <style>
        /* Imposta il body per occupare tutta l'altezza e larghezza */
        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #111423;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Contenitore per centrare il contenuto */
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            width: 100%;
        }

        /* Stile per il form */
        .multi-message-form {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: #111423;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            color: #fff;
        }

        .multi-message-form label {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .multi-message-form input[type="text"],
        .multi-message-form textarea {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            background-color: #222;
            color: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        .multi-message-form select {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #444;
            background-color: #222;
            color: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        .multi-message-form button {
            padding: 10px 15px;
            border-radius: 4px;
            border: none;
            background-color: #1E90FF;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .multi-message-form button:hover {
            background-color: #1c86ee;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="multi-message-form">
        <form action="send_multiple_sms.php" method="post">
            <label for="destinatari">Destinatari (separati da virgola):</label>
            <input type="text" id="destinatari" name="destinatari" value="<?php echo htmlspecialchars($destinatari); ?>" required>

            <label for="messaggio">Messaggio:</label>
            <textarea id="messaggio" name="messaggio" rows="8" required></textarea>

            <label for="ongame_offgame">Tipologia:</label>
            <select id="ongame_offgame" name="ongame_offgame">
                <option value="0">OFF</option>
                <option value="1">ON</option>
            </select>

            <button type="submit">Invia Messaggio</button>
        </form>
    </div>
</div>

</body>



</html>
