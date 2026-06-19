<?php

// Accesso solo a Moderatori e Admin
if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo "<div class='error' style='text-align:center;'>Accesso non autorizzato.</div>";
    exit;
}

// Se inviato il messaggio
if (isset($_POST['destinatario']) && isset($_POST['messaggio'])) {
    $destinatario = gdrcd_filter('in', $_POST['destinatario']);
    $messaggio = gdrcd_filter('in', $_POST['messaggio']);

    inviaMessaggioModerazione($destinatario, $messaggio);

    echo "<div class='success' style='text-align:center;'>Messaggio inviato con successo a <b>$destinatario</b>.</div>";
}
?>

<div class="pagina_gestione_mercato" style="max-width:600px; margin:auto; padding:20px; background-color:#1b1f30; border-radius:10px; box-shadow:0 0 10px #000; color:#ce846f; font-family:Lato;">

    <h2 style="text-align:center; margin-bottom:20px;">Invia Messaggio della Moderazione</h2>

    <form method="post" action="main.php?page=sms_moderatore">

        <!-- SELECT DESTINATARIO -->
        <label for="destinatario">Seleziona personaggio:</label><br>
        <select id="destinatario" name="destinatario" style="width:100%; padding:10px; margin:10px 0 20px 0; font-family:Lato; background:#2c2f45; color:#fff; border:1px solid #444; border-radius:5px;">
            <?php
            $pg_loggato = gdrcd_filter('in', $_SESSION['login']);
            $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome != '$pg_loggato' ORDER BY nome ASC", 'result');
            while ($row = gdrcd_query($result, 'fetch')) {
                echo '<option value="' . $row['nome'] . '">' . $row['nome'] . '</option>';
            }
            gdrcd_query($result, 'free');
            ?>
        </select>

        <!-- TEXTAREA MESSAGGIO -->
        <label for="messaggio">Scrivi il messaggio:</label><br>
        <textarea id="messaggio" name="messaggio" rows="8" style="width:100%; padding:10px; background:#2c2f45; color:#fff; font-family:Lato; border:1px solid #444; border-radius:5px;"></textarea>

        <br><br>
        <input type="submit" value="Invia Messaggio" style="background-color:#ce846f; border:none; padding:10px 20px; font-family:Lato; font-weight:bold; color:#000; border-radius:5px; cursor:pointer;">

    </form>
</div>

<?php
// Funzione dedicata all'invio con mittente "Moderazione"
function inviaMessaggioModerazione($destinatario, $messaggio) {
    $mittente = 'Segnalazione';

    // Verifica se esiste già una conversazione
    $sql_check = "
        SELECT id_conversazione 
        FROM sms 
        WHERE 
        (
            (mittente_nome = '$mittente' AND destinatario_nome = '$destinatario') 
            OR (mittente_nome = '$destinatario' AND destinatario_nome = '$mittente')
        )
        LIMIT 1
    ";
    $result = gdrcd_query($sql_check, 'result');

    if (gdrcd_query($result, 'num_rows') > 0) {
        $row = gdrcd_query($result, 'fetch');
        $id_conversazione = $row['id_conversazione'];

        gdrcd_query("INSERT INTO sms (
                        mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione
                    ) VALUES (
                        '$mittente', '$destinatario', '".gdrcd_filter('in', $messaggio)."', $id_conversazione, 'individuale', 0, NOW()
                    )");

        gdrcd_query("UPDATE conversazioni_individuali 
                     SET lettura = 0 
                     WHERE id_conversazione = $id_conversazione AND utente_nome = '$destinatario'");

    } else {
        $row = gdrcd_query("SELECT MAX(id_conversazione) as max_id FROM sms", 'fetch');
        $new_id = $row['max_id'] + 1;

        gdrcd_query("INSERT INTO sms (
                        mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione
                    ) VALUES (
                        '$mittente', '$destinatario', '".gdrcd_filter('in', $messaggio)."', $new_id, 'individuale', 0, NOW()
                    )");

        gdrcd_query("INSERT INTO conversazioni_individuali (
                        id_conversazione, utente_nome, lettura
                    ) VALUES (
                        $new_id, '$destinatario', 0
                    )");
    }
}

?>
