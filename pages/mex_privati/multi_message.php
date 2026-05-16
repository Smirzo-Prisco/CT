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
