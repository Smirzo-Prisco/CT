<?php
session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
?>

<link rel="stylesheet" href="../themes/crystal/main.css" TYPE="text/css">
<link rel="stylesheet" href="../themes/crystal/banca.css" TYPE="text/css">
<link rel="stylesheet" href="../themes/crystal/chat.css" TYPE="text/css">

<div class="pagina_gestione_mercato">
<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$message = $_POST['message'];
$tag = $_POST['tag'];
$valore = $_POST['valore'];
$caratteri = $_POST['caratteri'];
$limite = $_POST['limite'];

if ($luogo < 1) echo '<div class="error">Devi essere in una location di gioco</div>';
else {
    // controllo se c'è almeno un'azione da parte dell'utente (azione o master screen)
    $check_action = gdrcd_query("SELECT * FROM chat WHERE stanza = ".$_SESSION['luogo']." AND mittente = '".$_SESSION['login']."' AND DATE_ADD(ora, INTERVAL 7 HOUR)  >= NOW() AND (tipo = 'P' || tipo = 'M')", 'result');

    if(($_SESSION['admin'] != 1) AND (gdrcd_query($check_action, 'num_rows') < 1)) echo '<div class="warning">Devi aver scritto almeno un\' azione per attivare il controllo caratteri';
    else {
    ?>
        <!-- IMPOSTO INPUT PER SETTARE I CARATTERI -->
        <form action="input_limite.php" method="post">
            <table class="customTable" align="center">
                <tr><td align="center">GESTIONE NUMERO CARATTERI</td></tr>
                <tr class="second_header"><td align="center"><b>NUMERO CARATTERI PROPOSTI (MINIMO 150)</b></td></tr>
                <tr><td align="center"><input name="caratteri" id="caratteri" placeholder="Inserire numero caratteri"></td></tr>
                <tr>
                    <td align="center">
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                        <input type="hidden" name="op" value="new_master_message" />
                    </td>
                </tr>
            </table>
        </form>
    <?php
    }
}
?>
</div>

<?php
if (gdrcd_filter('get', $_POST['op']) == 'new_master_message')	{
    // Caratteri che si vogliono impostare
    $lunghezza = $_POST["caratteri"];

    // Controllo che i caratteri siano almeno 150
    if ($lunghezza < 150) echo "<script type='text/javascript'>alert('I caratteri devono essere almeno 150');</script>";
    else $query_check = gdrcd_query("SELECT * FROM scelte_utenti WHERE nome = '$login' AND id_luogo = '$luogo'", 'result');

    // Se l'utente ha già inserito un limite per la chat, lo aggiorno, altrimenti lo inserisco
    if(gdrcd_query($query_check, 'num_rows') > 0) gdrcd_query("UPDATE scelte_utenti SET lunghezza_massima = '$lunghezza', timestamp_modifica = NOW() WHERE nome = '$login' AND id_luogo = '$luogo'");
    else gdrcd_query("INSERT INTO scelte_utenti (nome, id_luogo, lunghezza_massima, timestamp_modifica) VALUES ('$login', '$luogo', '$lunghezza', NOW())");

    // Query per trovare il valore minimo della lunghezza impostata dagli utenti per questo luogo
    $query_min_lunghezza = gdrcd_query("SELECT MIN(lunghezza_massima) AS min_lunghezza, nome FROM scelte_utenti WHERE id_luogo = '$luogo' AND lunghezza_massima = MIN(lunghezza_massima)");
    $lunghezza_minima = $query_min_lunghezza['min_lunghezza'];

    gdrcd_query("UPDATE mappa SET limite_lunghezza_massima = $lunghezza_minima, timestamp_modifica_limite = NOW() WHERE id = $luogo");

    // Calcola il timestamp di +1 minuto da ora
    $timestamp_minuto_successivo = date('Y-m-d H:i:s', strtotime('+1 minute'));

    // Query per trovare il valore minimo della lunghezza impostata dagli utenti per questo luogo
    // $query_min_lunghezza = gdrcd_query("SELECT MIN(lunghezza_massima) AS min_lunghezza FROM scelte_utenti WHERE id_luogo = '$luogo'");
    // $lunghezza_minima = $query_min_lunghezza['min_lunghezza'];

    if ($lunghezza_minima !== null) {
        // Ottieni il nome del personaggio che ha impostato il limite minimo
        // $query_personaggio = gdrcd_query("SELECT nome FROM scelte_utenti WHERE lunghezza_massima = '$lunghezza_minima' AND id_luogo = '$luogo'");
        // $personaggio = $query_personaggio['nome'];
        $personaggio = $query_min_lunghezza['nome'];

        // Esegui l'inserimento del messaggio nella chat
        $testo = "Il personaggio " . $personaggio . " ha scelto di settare per tutti una lunghezza massima di " . $lunghezza_minima;
        gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', 'System', '$timestamp_minuto_successivo', 'M', '$testo')");
    }
}
?>