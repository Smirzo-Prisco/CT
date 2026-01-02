<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<div class="pagina_gestione_mercato">

<?php
// ACCESSO: Solo Admin e Moderatori
if ($_SESSION['admin'] != 1) {
    echo "<div class='error' style='text-align:center;'>Non hai i permessi per accedere a questa pagina.</div>";
    exit;
}

// Gestione Azioni Multiple
if(isset($_POST['azione']) && isset($_POST['selezionati'])) {

    $approvati = [];
    $rifiutati = [];

    foreach($_POST['selezionati'] as $id_oggetto) {
        $id_oggetto = (int)$id_oggetto;

        // Recupera dati oggetto
        $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = ".$id_oggetto);
        $pg_creatore = $oggetto['creatore'];

        if($_POST['azione'] == 'approva') {
            // Setta richiesto = 0
            gdrcd_query("UPDATE oggetto SET richiesto = 0 WHERE id_oggetto = ".$id_oggetto);
            gdrcd_query("UPDATE personaggio SET soldi = soldi - 200 WHERE nome = '".gdrcd_filter('in', $pg_creatore)."'");
            
            // Calcolo cariche
            switch($oggetto['categoria']) {
                case 'arma':
                    if ($oggetto['tipo'] == 15) { // 15 = Arma di Gilda
                        $cariche = -1; // Cariche infinite
                    } else {
                        $cariche = $oggetto['ricarica_massima']; // Armi normali: usa ricarica_massima
                    }
                break;
                case 'curativo':
                case 'statistica':$cariche = $oggetto['cariche']; break;
                case 'standard':  $cariche = ($oggetto['cariche'] == 'illimitato') ? -1 : $oggetto['cariche']; break;
                case 'magico':    $cariche = $oggetto['cariche']; break;
                default:          $cariche = 1;
            }

            // Inserisci nella clgpersonaggiooggetto
            gdrcd_query("INSERT INTO clgpersonaggiooggetto 
                (nome, id_oggetto, cariche, numero, isTemp, temp_giorni) 
                VALUES 
                ('".gdrcd_filter('in', $pg_creatore)."', ".$id_oggetto.", ".$cariche.", 1, ".$oggetto['isTemp'].", ".$oggetto['temp_giorni'].")");

            // Aggiungi alla lista approvati
            $approvati[$pg_creatore][] = $oggetto['nome'];

        } elseif($_POST['azione'] == 'rifiuta') {
            gdrcd_query("DELETE FROM oggetto WHERE id_oggetto = ".$id_oggetto);

            // Aggiungi alla lista rifiutati
            $rifiutati[$pg_creatore][] = $oggetto['nome'];
        }
    }

    echo "<div class='success'>Operazione completata con successo!</div>";

    // Invio Messaggi ai Creatori
    foreach (array_unique(array_merge(array_keys($approvati), array_keys($rifiutati))) as $destinatario) {
        $messaggio = "";

        if (isset($approvati[$destinatario])) {
            $messaggio .= "Oggetti approvati:\n";
            foreach ($approvati[$destinatario] as $nome) {
                $messaggio .= "- ".$nome."\n";
            }
            $messaggio .= "\n";
        }

        if (isset($rifiutati[$destinatario])) {
            $messaggio .= "Oggetti rifiutati:\n";
            foreach ($rifiutati[$destinatario] as $nome) {
                $messaggio .= "- ".$nome."\n";
            }
        }

        if ($messaggio != "") {
            inviaMessaggioSistema('Segnalazione', $destinatario, $messaggio);
        }
    }
}

// Recupero Oggetti da approvare
$richieste = gdrcd_query("SELECT * FROM oggetto WHERE richiesto = 2 ORDER BY id_oggetto DESC", 'result');

if(gdrcd_query($richieste, 'num_rows') == 0) {
    echo "<div class='warning' style='text-align:center;'>Nessuna richiesta di oggetti in attesa di approvazione.</div>";
} else {
?>

<form method="post" action="main.php?page=oggetto_approvazione">

<table class="customTable" style="width: 100%; margin-top: 20px;">
    <tr class="second_header">
        <td colspan="2" style="text-align:center;">Oggetti in Attesa di Approvazione</td>
    </tr>
</table>

<?php
    while($oggetto = gdrcd_query($richieste, 'fetch')) {
?>

<div style="border: 1px solid #2c2c2c; margin: 15px 0; padding: 15px; background-color: #0f111d; border-radius: 8px;">
    <input type="checkbox" name="selezionati[]" value="<?php echo $oggetto['id_oggetto']; ?>" style="float:right; transform: scale(1.3);">

    <h3 style="color:#ce846f; margin-bottom:10px;"><?php echo gdrcd_filter('out', $oggetto['nome']); ?></h3>
    <p><b>Creato da:</b> <?php echo gdrcd_filter('out', $oggetto['creatore']); ?> | 
       <b>Categoria:</b> <?php echo ucfirst($oggetto['categoria']); ?></p>

    <div style="margin-top:10px; padding:10px; background:#1a1d2e; color:#dcdcdc; border-radius:5px;">
        <?php echo $oggetto['descrizione']; ?>
    </div>

    <div style="margin-top:10px; font-size:12px; color:#aaa;">
        <?php
        switch($oggetto['categoria']) {
            case 'arma':
                echo "Tipo Arma: ".$oggetto['tipo_arma']." | Bonus: ".$oggetto['attacco']." | Ricarica Max: ".$oggetto['ricarica_massima'];
                break;
            case 'curativo':
                echo "Salute: ".$oggetto['heal']." | Integrità: ".$oggetto['bonus_car0'];
                break;
            case 'statistica':
                echo "Durata: ".$oggetto['temp_giorni']."gg | Bonus: ".$oggetto['bonus_car1_extra']."/".$oggetto['bonus_car2_extra']."/".$oggetto['bonus_car3_extra'];
                break;
            case 'magico':
            case 'standard':
                echo "Cariche: ".$oggetto['cariche']." | Ricarica Max: ".$oggetto['ricarica_massima'];
                break;
        }
        ?>
    </div>
</div>

<?php
    }
    gdrcd_query($richieste, 'free');
?>

<div style="text-align:center; margin-top: 20px;">
    <button type="submit" name="azione" value="approva" class="ares" style="background-color:#145214; margin-right:15px;">Approva Selezionati</button>
    <button type="submit" name="azione" value="rifiuta" class="ares" style="background-color:#7b1e1e;" onclick="return confirm('Sei sicuro di voler rifiutare gli oggetti selezionati?');">Rifiuta Selezionati</button>
</div>

</form>

<?php } ?>

</div>

<?php
// Funzione Invio Messaggio
function inviaMessaggioSistema($mittente, $destinatario, $messaggio) {

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

        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                     VALUES ('$mittente', '$destinatario', '".gdrcd_filter('in', $messaggio)."', $id_conversazione, 'individuale', 0, NOW())");

        gdrcd_query("UPDATE conversazioni_individuali 
                     SET lettura = 0 
                     WHERE id_conversazione = $id_conversazione AND utente_nome = '$destinatario'");

    } else {
        $row = gdrcd_query("SELECT MAX(id_conversazione) as max_id FROM sms", 'fetch');
        $new_id = $row['max_id'] + 1;

        gdrcd_query("INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                     VALUES ('$mittente', '$destinatario', '".gdrcd_filter('in', $messaggio)."', $new_id, 'individuale', 0, NOW())");

        gdrcd_query("INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                     VALUES ($new_id, '$destinatario', 0)");
    }
}
?>
