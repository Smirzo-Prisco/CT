<?php
// Verifica se il personaggio appartiene già a una gilda o fazione
$check_ruolo = gdrcd_query("SELECT id_gilda FROM personaggio WHERE nome = '".$_SESSION['login']."'");

if ($check_ruolo['id_gilda'] != 0) {
    echo '<div class="error">Accesso negato.<br> Sei già in una famiglia magia o hai già scelto una fazione.</div>';
    exit();  // Blocca l'esecuzione del codice se l'accesso è negato
}

// Mappa dei ruoli e delle abilità corrispondenti
$abilita_per_ruolo = [
    13 => 441, // S.I.T. => Scan
    11 => 442  // Wikkan => Intrusione
];

// Funzione per generare la riga della tabella per ogni ruolo
function generaRuolo($row) {
    return '
    <tr>
        <td><img class="" src="imgs/guilds/' . $row['immagine'] . '" /></td>
        <td style="color: #8f8f8f;">' . $row['nome_ruolo'] . '</td>
        <td>
            <form method="post" action="main.php?page=scegli_umano">
                <input type="submit" value="Scegli">
                <input type="hidden" name="op" value="pick">
                <input type="hidden" name="nome_lavoro" value="' . gdrcd_filter('out', $row['nome']) . '">
                <input type="hidden" name="id_record" value="' . $row['id_ruolo'] . '">
            </form>
        </td>
    </tr>';
}

// Se non è stata fatta ancora una scelta, mostra l'elenco delle inclinazioni
if (!isset($_POST['op'])) {
    $query = "SELECT * FROM ruolo WHERE gilda = -1 ORDER BY nome_ruolo";
    $result = gdrcd_query($query, 'result');
    ?>
    <div class="pagina_servizi_lavoro">
        <table class="customTable">
            <thead>
                <tr>
                    <th colspan="8" style="background-color: #181c31; font-size: 13px; color: #9a6353; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                        POTENZIA IL CITTADINO
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="second_header">
                    <td></td>
                    <td>NOME</td>
                    <td></td>
                </tr>
                <?php
                // Genera le righe della tabella
                while ($row = gdrcd_query($result, 'fetch')) {
                    echo generaRuolo($row);
                }
                gdrcd_query($result, 'free');
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Se viene selezionato un ruolo (op == 'pick'), aggiorna il personaggio
if (isset($_POST['op']) && $_POST['op'] == 'pick') {
    $id_ruolo = gdrcd_filter('num', $_POST['id_record']);
    $query = "UPDATE personaggio SET id_gilda = '-1', id_ruolo_gilda = $id_ruolo WHERE nome = '".$_SESSION['login']."'";
    gdrcd_query($query);
    
    // Controlla se il ruolo selezionato ha un'abilità corrispondente
    if (array_key_exists($id_ruolo, $abilita_per_ruolo)) {
        $id_abilita = $abilita_per_ruolo[$id_ruolo];
        
        // Assegna l'abilità al personaggio
        gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('".$_SESSION['login']."', '$id_abilita', '1')");
    }
    
    echo '<div class="warning">Operazione eseguita!</div>';
    ?>
    <div class="link_back">
        <a href="main.php?page=scegli_umano">Torna indietro</a>
    </div>
    <?php
}
?>

<div class="panels_link">
    <a href="main.php?page=uffici">Torna indietro</a>
</div>
