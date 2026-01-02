<?php
include('../../ref_header.inc.php');

$Login = $_SESSION['login'];
?>

<head>
    <br><br>
    <center><img src="../../themes/crystal/imgs/calendario/title_calendario.png"></center>
    <div id="overDiv" style="position: absolute; visibility: hidden; z-index: 1000;"></div>
    <script type="text/JavaScript" src="overlib_mini.js"></script>

<?php
function ShowCalendar($m, $y) {
    if (!isset($_GET['d']) || $_GET['d'] == "") {
        $m = date('n');
        $y = date('Y');
    } else {
        $m = (int)strftime("%m", (int)$_GET['d']);
        $y = (int)strftime("%Y", (int)$_GET['d']);
    }

    $precedente = mktime(0, 0, 0, $m - 1, 1, $y);
    $successivo = mktime(0, 0, 0, $m + 1, 1, $y);

    $nomi_mesi = ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];
    $nomi_giorni = ["L", "M", "M", "G", "V", "S", "D"];

    $cols = 7;
    $days = date("t", mktime(0, 0, 0, $m, 1, $y));
    $lunedi = date("w", mktime(0, 0, 0, $m, 1, $y));
    if ($lunedi == 0) $lunedi = 7;
    $anno_futuro = date('Y', strtotime('+1053 years'));

    echo "<table class='mainTable'>";
    echo "<tr><td class='monthRow' colspan=\"$cols\">
        <a href=\"../main.php?page=agenda_center&d=$precedente\" target=\"_parent\">‹</a> 
        {$nomi_mesi[$m-1]} $anno_futuro 
        <a href=\"../main.php?page=agenda_center&d=$successivo\">›</a>
        </td></tr>";

    echo "<tr>";
    foreach ($nomi_giorni as $giorno) {
        echo "<td class='dayNamesText' style='width: 63px; height: 28px; background-color: #181a2b; border: 1px solid #020106;'><b>$giorno</b></td>";
    }
    echo "</tr>";

    for ($j = 1; $j < $days + $lunedi; $j++) {
        if($j%$cols+1 == 0) echo "<tr class='rows'>";

        if ($j < $lunedi) echo "<td></td>";
        else {
            $day = $j - ($lunedi - 1);
            $data = strtotime("$y-$m-$day");
            $oggi = strtotime(date("Y-m-d"));

            // Query per ottenere appuntamenti
            $result = gdrcd_query("SELECT * FROM appuntamenti WHERE str_data = '$data'", 'result');

            if (gdrcd_query($result, 'num_rows') > 0) {
                while ($fetch = gdrcd_query($result, 'fetch')) {
                    $titolo = htmlspecialchars($fetch['titolo']);
                    $destinatari_raw = htmlspecialchars($fetch['destinatario']); // Manteniamo i destinatari originali
                    $autore = htmlspecialchars($fetch['autore']);
                    $luogo = htmlspecialchars($fetch['luogo']);
                    $orario = $fetch['orario'];
                    $testo = addslashes($fetch['testo']);
                    
                    // Creiamo un array con i destinatari separati dalla virgola
                    $destinatari_array = array_map('trim', explode(',', $destinatari_raw));

                    if ($autore == $_SESSION['login'] && in_array($titolo, ["Giocata personale", "Giocata di gilda", "Giocata di mestiere"])) {
                        // L'autore vede tutti i destinatari
                        $day = "<a href=\"#\" 
                                style=\"color: #ce846f; font-weight: bold; font-family: DejaVu Serif; font-size: 15px; cursor: pointer;\" 
                                onmouseover=\"return overlib('&lt;table width=&quot;100%&quot; border=&quot;0&quot; class=&quot;popupDateTable&quot;&gt;&lt;tr&gt;&lt;td align=&quot;center&quot; class=&quot;popupDate&quot;&gt;$titolo con: &lt;font color=&quot;#ce846f&quot;&gt;$destinatari_raw&lt;/font&gt; alle &lt;font color=&quot;#ce846f&quot;&gt;$orario&lt;/font&gt; in &lt;font color=&quot;#ce846f&quot;&gt;$luogo&lt;/font&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;');\" 
                                onmouseout=\"return nd();\">$day</a>";
                    } elseif (in_array($_SESSION['login'], $destinatari_array)) {
                        // Se l'utente è un destinatario
                        // Rimuoviamo il destinatario corrente dall'array
                        $destinatari_array = array_diff($destinatari_array, [$_SESSION['login']]);
                        $altri_destinatari = implode(', ', $destinatari_array);

                        // I destinatari vedono l'autore e gli altri destinatari
                        $day = "<a href=\"#\" 
                                style=\"color: #ce846f; font-weight: bold; font-family: DejaVu Serif; font-size: 15px; cursor: pointer;\" 
                                onmouseover=\"return overlib('&lt;table width=&quot;100%&quot; border=&quot;0&quot; class=&quot;popupDateTable&quot;&gt;&lt;tr&gt;&lt;td align=&quot;center&quot; class=&quot;popupDate&quot;&gt;$titolo con: &lt;font color=&quot;#ce846f&quot;&gt;$autore, $altri_destinatari&lt;/font&gt; alle &lt;font color=&quot;#ce846f&quot;&gt;$orario&lt;/font&gt; in &lt;font color=&quot;#ce846f&quot;&gt;$luogo&lt;/font&gt;&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;');\" 
                                onmouseout=\"return nd();\">$day</a>";
                    } elseif ($autore != $_SESSION['login'] && in_array($titolo, ["Quest", "Evento"])) {
                        // Visualizza l'appuntamento per eventi e quest
                        $day = "<a href=\"#\" 
                                style=\"color: #ce846f; font-weight: bold; font-family: DejaVu Serif; font-size: 15px; cursor: pointer;\" 
                                onmouseover=\"return overlib('&lt;table width=&quot;100%&quot; border=&quot;0&quot; class=&quot;popupDateTable&quot;&gt;&lt;tr&gt;&lt;td align=&quot;center&quot; class=&quot;popupDate&quot;&gt;$titolo (<b>$testo</b>) presso $luogo alle $orario&lt;/td&gt;&lt;/tr&gt;&lt;/table&gt;');\" 
                                onmouseout=\"return nd();\">$day</a>";
                    }
                }
            }

            echo $data != $oggi ? "<td class='dayNamesText'><center>$day</center></td>" : "<td class='dayNamesTextToday'><b><center>$day</center></b></td>";
        }

        if ($j % $cols == 0) echo "</tr>";
    }

    echo "<tr><td colspan='7' style='font-size: 12px; color: #8f8f8f; text-transform: uppercase;'><center><a href='../main.php?page=agenda_center&op=add_role' target='_top'>Prenota role</a></center></td></tr>";
    echo "</table>";
}

ShowCalendar(date("m"), date("Y"));

// Invio di messaggi e gestione della conversazione
$sql = "SELECT * FROM BakCalendario WHERE DATE(FROM_UNIXTIME(Spedito)) = CURDATE()";
$result = gdrcd_query($sql, 'result');

if (gdrcd_query($result, 'num_rows') > 0) {
    while ($fetch = gdrcd_query($result, 'fetch')) {
        $id_app = $fetch['id_app'];
        $destinatario = gdrcd_filter('in', $fetch['Destinatario']);
        $testo = gdrcd_filter('in', $fetch['Testo']);

        $sql_verifica_conversazione = "SELECT id_conversazione FROM sms WHERE mittente_nome = 'Segnalazione' AND destinatario_nome = '$destinatario' LIMIT 1";
        $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

        if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
            $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
            $id_conversazione = $row_conversazione['id_conversazione'];

            // Inserimento messaggio
            $sql_inserisci_messaggio = "INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione) 
                                        VALUES ('Segnalazione', '$destinatario', '$testo', $id_conversazione, 'individuale', 0, NOW())";
            gdrcd_query($sql_inserisci_messaggio);

            // Aggiorna lettura
            $sql_aggiorna_lettura = "UPDATE conversazioni_individuali SET lettura = 0 WHERE id_conversazione = $id_conversazione AND utente_nome = '$destinatario'";
            gdrcd_query($sql_aggiorna_lettura);
        } else {
            $sql_ultimo_id = "SELECT MAX(id_conversazione) AS max_id FROM sms";
            $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
            $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
            $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

            // Inserimento nuovo messaggio
            $sql_inserisci_messaggio = "INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione) 
                                        VALUES ('Segnalazione', '$destinatario', '$testo', $nuovo_id_conversazione, 'individuale', 0, NOW())";
            gdrcd_query($sql_inserisci_messaggio);

            // Inserisci nuova conversazione
            $sql_inserisci_conversazione = "INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura) VALUES ($nuovo_id_conversazione, '$destinatario', 0)";
            gdrcd_query($sql_inserisci_conversazione);
        }

        // Cancella dalla tabella BakCalendario
        $delete_message = "DELETE FROM BakCalendario WHERE id_app = '$id_app'";
        gdrcd_query($delete_message, 'result');
    }
}
?>