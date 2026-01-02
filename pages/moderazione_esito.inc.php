<link rel="stylesheet" href="../themes/crystal/mestieri.css">
<div class="pagina_gestione_mercato">
<?php
/*Controllo permessi utente*/
if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) {
if(isset($_REQUEST['op']) === false) { /*pagine principale di mex*/
?>

<div class="panels_box">
<form action="main.php?page=moderazione_esito" method="post" class="form_gestione">
<div class='form_field'>
<input type = "text" name="autore">
</div>
<div class='form_field'>
<textarea name="risposta"></textarea>
</div>
<!-- bottoni -->
<div class='form_submit'>
<input type="submit" value="Rispondi" />
<input type="hidden" name="op" value="doedit">
</div>
</form>

<!-- Link di ritorno alla visualizzazione di base -->
<div class="link_back">
<a href="main.php?page=contatta_moderatore_elenco">
<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
</a>
</div>
</div>

<?php  }
                
/*Inoltro mex*/
if($_POST['op'] == 'doedit') { 
            /*Processo le informazioni ricevute dal form*/
                if((is_numeric($_POST['art']) == true) && (is_numeric($_POST['id']) == true)) {
                    /*Eseguo l'aggiornamento*/
                gdrcd_query("UPDATE messaggio_moderazione SET risposta ='".gdrcd_filter('in', $_POST['risposta'])."', stato = 1 WHERE id = ".gdrcd_filter('num', $_POST['art'])." LIMIT 1");

//inizio iter messaggio
        
    
   $destinatario = gdrcd_filter('in', $_POST['autore']);

// 1. Verifica se esiste una conversazione tra "Segnalazione" e il destinatario
$sql_verifica_conversazione = "
    SELECT id_conversazione 
    FROM sms 
    WHERE 
    (
        (mittente_nome = 'Segnalazione' AND destinatario_nome = '$destinatario') 
        OR (mittente_nome = '$destinatario' AND destinatario_nome = 'Segnalazione')
    )
    LIMIT 1
";
$result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

// Se esiste una conversazione
if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
    // Recuperiamo l'ID della conversazione esistente
    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
    $id_conversazione = $row_conversazione['id_conversazione'];

    // Inseriamo il messaggio nella conversazione esistente
    $messaggio = "La tua richiesta di moderazione è conclusa. Trovi il responso nella tabella delle tue segnalazioni in <b>Gestione - Contatta Moderazione</b>.";
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
        VALUES ('Segnalazione', '$destinatario', '$messaggio', $id_conversazione, 'individuale', 0, NOW())
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Aggiorniamo lo stato di lettura della conversazione per il destinatario (lettura = 0)
    $sql_aggiorna_lettura = "
        UPDATE conversazioni_individuali 
        SET lettura = 0 
        WHERE id_conversazione = $id_conversazione 
        AND utente_nome = '$destinatario'
    ";
    gdrcd_query($sql_aggiorna_lettura);
    
} else {
    // 2. Se la conversazione non esiste, creiamo una nuova conversazione
    $messaggio = "La tua richiesta di moderazione è conclusa. Trovi il responso nella tabella delle tue segnalazioni in <b>Gestione - Contatta Moderazione</b>.";

    // Trova il massimo ID della conversazione e aggiungi 1 per il nuovo
    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

    // Inseriamo il nuovo messaggio nella tabella sms
    $sql_inserisci_messaggio = "
        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
        VALUES ('Segnalazione', '$destinatario', '$messaggio', $nuovo_id_conversazione, 'individuale', 0, NOW())
    ";
    gdrcd_query($sql_inserisci_messaggio);

    // Inseriamo il destinatario nella tabella `conversazioni_individuali` con lettura = 0
    $sql_inserisci_conversazione = "
        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
        VALUES 
        ($nuovo_id_conversazione, '$destinatario', 0)
    ";
    gdrcd_query($sql_inserisci_conversazione);
}
         
                ?>
                    <div class="warning">
                        Risposta inoltrata all'utente e segnalazione chiusa.
                    </div>
                <?php } ?>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=contatta_moderatore_elenco">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
            
            
            
            <?php }
            }//fine permessi
            
            ?>
            
</div>