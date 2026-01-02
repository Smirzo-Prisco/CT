<?php
session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

// Stringa DSN per MySQL
$dsn = "mysql:host=".$PARAMETERS['database']['url'].";dbname=".$PARAMETERS['database']['database_name'].";charset=utf8mb4";

// Connessione sicura con PDO
$pdo = new PDO($dsn, $PARAMETERS['database']['username'], $PARAMETERS['database']['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$pg = $_SESSION['login'];
?>

<head>
    <link rel="stylesheet" href="../themes/crystal/famiglie.css">

<?php
// Cancellazione appuntamenti
if (isset($_POST['delete']) && !empty($_POST['checkbox'])) {
    $checkbox = $_POST['checkbox'];
    
    $placeholders = rtrim(str_repeat('?,', count($checkbox)), ',');
    
    // Query sicura con PDO
    $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id IN ($placeholders)");
    $stmt->execute($checkbox);

    $stmt = $pdo->prepare("DELETE FROM BakCalendario WHERE id_app IN ($placeholders)");
    $stmt->execute($checkbox);

    echo '<div class="error">Cancellazione eseguita</div>';
}

// Inserimento appuntamenti
if (isset($_POST['submit']) && $_POST['submit'] === "Invia") {
    // Filtraggio degli input
    $titolo = gdrcd_filter('in', $_POST['titolo']);
    $testo = gdrcd_filter('in', $_POST['testo']);
    $str_data = strtotime(gdrcd_filter('out', $_POST['data']));
    $orario = gdrcd_filter('out', date('H:i', $str_data));
    $autore = gdrcd_filter('out', $_POST['autore']);
    $luogo = gdrcd_filter('out', $_POST['luogo']);
    $destinatario = gdrcd_filter('out', $_POST['destinatario']);
    $data_object = new DateTime();
    $data_object->setTimestamp($str_data);
    $data_object->setTime(0, 0, 0);
    $str_data = $data_object->getTimestamp();

    // Query preparata per inserire appuntamenti
    $stmt = $pdo->prepare("
        INSERT INTO appuntamenti (titolo, testo, autore, luogo, destinatario, str_data, orario) 
        VALUES (:titolo, :testo, :autore, :luogo, :destinatario, :str_data, :orario)
    ");

    $stmt->execute([
        ':titolo' => $titolo,
        ':testo' => $testo,
        ':autore' => $autore,
        ':luogo' => $luogo,
        ':destinatario' => $destinatario,
        ':str_data' => $str_data,
        ':orario' => $orario
    ]);

    echo "Inserimento avvenuto con successo.<br>
    Torna al <a href=\"/main.php?page=agenda_center&op=add_role\">Registro</a>";

    // Notifiche per "Giocata personale"
    if ($titolo === 'Giocata personale') {
        $id_app = $pdo->lastInsertId();
        $destinatari = array_map('trim', explode(',', $destinatario));

        foreach ($destinatari as $destinat) {
            $stmt = $pdo->prepare("
                INSERT INTO BakCalendario (id_app, Mittente, Destinatario, Spedito, Letto, Inviato, Testo) 
                VALUES (:id_app, 'Calendario', :destinat, :str_data, '0', '0', :messaggio)
            ");
            $stmt->execute([
                ':id_app' => $id_app,
                ':destinat' => $destinat,
                ':str_data' => $str_data,
                ':messaggio' => "Hai una $titolo con $autore in $luogo alle $orario"
            ]);
        }

        // Inserimento per autore
        $stmt = $pdo->prepare("
            INSERT INTO BakCalendario (id_app, Mittente, Destinatario, Spedito, Letto, Inviato, Testo) 
            VALUES (:id_app, 'Calendario', :autore, :str_data, '0', '0', :messaggio)
        ");
        $stmt->execute([
            ':id_app' => $id_app,
            ':autore' => $autore,
            ':str_data' => $str_data,
            ':messaggio' => "Hai una $titolo con $destinatario in $luogo alle $orario"
        ]);
    }
} else {
    ?>
    <form action="/main.php?page=agenda_center&op=add_role" method="post">
        <table class="customTable">
            <tr>
                <td style="font-size: 12px;color: #a7a7a8;">
                    PRENOTAZIONI ROLE 
                    <?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
                    O QUEST
                    <?php } ?>
                </td>
            </tr>
            <tr class="second_header">
                <td><b>Tipo di giocata</b></td>
            </tr>
            <tr>
                <td>
                    <select name="titolo" class="ares" style="background-color: #0f111d;">
                        <option>Giocata personale</option>
                        <option>Giocata di gilda</option>
                        <option>Giocata di mestiere</option>
                        <?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
                        <option>Quest</option>
                        <option>Evento</option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="second_header">
                <td><b>Tuo personaggio</b></td>
            </tr>
            <tr>
                <td>
                    <select name="autore" class="ares" style="background-color: #0f111d;">
                        <option><?= $pg ?></option>
                        <?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
                        <option>Master</option>
                        <?php } ?>
                    </select>
                </td>
            </tr>  
            <tr class="second_header">
                <td><b>Giocare con:</b></td>
            </tr>
            <tr>
                <td>
                    <input type="text" size="40" name="destinatario" placeholder="Usare virgola se presenti più personaggi" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
            <tr class="second_header">
                <td><b>Luogo:</b></td>
            </tr>
            <tr>
                <td>
                    <select name="luogo" class="ares" style="background-color: #0f111d;">
                        <?php
                        $result = $pdo->query("SELECT nome FROM mappa ORDER BY nome ASC");
                        while ($row = $result->fetch()) {
                            echo '<option>' . $row['nome'] . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr class="second_header">
                <td><b>Data e ora:</b></td>
            </tr>
            <tr>
                <td>
                    <input name="data" type="datetime-local" value="<?=date("Y-m-d");?>T<?=date("H:i");?>" min="<?=date("Y-m-d");?>T<?=date("H:i");?>" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
            <tr>
                <td><input name="submit" type="submit" value="Invia" class="ares" style="background-color: #0f111d;"></td>
            </tr>
        </table>
    </form>
    <?php
}

// Visualizzazione appuntamenti
?>
<form action="/main.php?page=agenda_center&op=add_role" method="post" name="cancellaselezione">
    <table class="customTable">
        <tr>
            <td colspan="4" style="font-size: 12px;color: #a7a7a8;">
                AGENDA DI <?= $pg ?>
            </td>
        </tr>
        <tr class="second_header">
            <td width="20%"><b>Data e ora</b></td>
            <td width="50%"><b>Informazioni</b></td>
            <td width="10%"></td>
            <td width="5%"></td>
        </tr>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM appuntamenti WHERE autore = ? OR destinatario = ? ORDER BY str_data ASC, orario ASC");
        $stmt->execute([$pg, $pg]);
        while ($fetch = $stmt->fetch()) {
            $data = date("d-m-Y", $fetch['str_data']);
            ?>
            <tr>
                <td><?= $data ?> alle <?= $fetch['orario'] ?></td>
                <td><?= $fetch['titolo'] ?> con <?= ($fetch['autore'] == $pg) ? $fetch['destinatario'] : $fetch['autore'] ?></td>
                <td><a href="/main.php?page=agenda_center&id=<?= $fetch['id'] ?>&op=edit_role"><img src="../../themes/crystal/imgs/scheda/edit_affetti.png" style="width: 26px; height: 26px;"></a></td>
                <td><input type="checkbox" name="checkbox[]" value="<?= $fetch['id'] ?>"></td>
            </tr>
            <?php
        }
        ?>
    </table>
    <center><input name="delete" type="submit" value="delete"></center>
</form>

<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<form action="/main.php?page=agenda_center&op=add_role" method="post" name="cancellaselezione">
    <table class="customTable">
        <tr>
            <td colspan="4" style="font-size: 12px;color: #a7a7a8;">
                REGISTRO QUEST
            </td>
        </tr>
        <tr class="second_header">
            <td width="20%"><b>Data e ora</b></td>
            <td width="50%"><b>Informazioni</b></td>
            <td width="5%"></td>
        </tr>
        <?php
        $stmt = $pdo->query("SELECT * FROM appuntamenti WHERE autore = 'Master' ORDER BY str_data ASC, orario ASC");
        while ($fetch = $stmt->fetch()) {
            $data = date("d-m-Y", $fetch['str_data']);
            ?>
            <tr>
                <td><?= $data ?> alle <?= $fetch['orario'] ?></td>
                <td><?= $fetch['titolo'] ?> <b>"<?= $fetch['testo'] ?>"</b> presso <b><?= $fetch['luogo'] ?></b></td>
                <td><a href="/main.php?page=agenda_center&id=<?= $fetch['id'] ?>&op=edit_role"><img src="../../themes/crystal/imgs/scheda/edit_affetti.png" style="width: 26px; height: 26px;"></a></td>
                <td><input type="checkbox" name="checkbox[]" value="<?= $fetch['id'] ?>"></td>
            </tr>
            <?php
        }
        ?>
    </table>
    <center><input name="delete" type="submit" value="delete"></center>
    </form>
<?php } ?>

<script type="text/javascript">
function SelezTT()
{
    var cancellaselezione = document.cancellaselezione.elements;
    for (var i = 0; i < cancellaselezione.length; i++) {
        if (cancellaselezione[i].type === "checkbox") {
            cancellaselezione[i].checked = !cancellaselezione[i].checked;
        }
    }
}
</script>