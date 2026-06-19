<div class="pagina_gestione_mercato">
<?php
/*Controllo permessi utente*/
                if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) {
                
                if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/
                //Determinazione pagina (paginazione)
                $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM messaggio_moderazione");
                $totaleresults = $record_globale['COUNT(*)'];
                //Lettura record
                $result = gdrcd_query("SELECT * FROM messaggio_moderazione ORDER BY id DESC LIMIT ".$pagebegin.", ".$pageend."", 'result');
                $numresults = gdrcd_query($result, 'num_rows');

                /* Se esistono record */
                 ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table class="customTable">
                            <!-- Intestazione tabella -->
                            <tr>
                                <td colspan="2">
                            LISTA RICHIESTE DI MODERAZIONE
                                </td>
                                </tr>
                                <tr class="second_header">
                                <td width=50%>
                                    <b>Titolo</b>
                                </td>
                                <td width=20%>
                                    Rispondi
                                </td>
                                
                            </tr>
                            <!-- Record -->
                            <?php if($numresults > 0) {
                            while($row = gdrcd_query($result, 'fetch')) { ?>
                                <tr>
                                    <td>
                                        <div class="elementi_elenco">
                                            <?php echo $row['titolo']; ?>
                                        </div>
                                    </td>
                                    <?php
                                    if ($row['stato'] == 0) {
                                    ?>
                                    <td><!-- Iconcine dei controlli -->
                                     <!-- Modifica -->
                                    <div class="controlli_elenco">
                                    <div class="controllo_elenco">
                                    <form class="opzioni_elenco_record_gestione" action="main.php?page=contatta_moderatore_elenco" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" src="imgs/icons/edit.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                           title="Rispondi" />
                                    </form>
                                    </div>
                                            
                                        </div>
                                    </td>
                                    <?php } ?>
                                </tr>
                            <?php } //while
                            gdrcd_query($result, 'free');
                            ?>
                        </table>
                    </div>
                <?php }//if ?>
                <!-- Paginatore elenco -->
                <div class="pager">
                    <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                        echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                        for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                            if($i != gdrcd_filter('num', $_REQUEST['offset'])) {
                                ?>
                                <a href="main.php?page=contatta_moderatore_elenco&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                            <?php } else {
                                echo ' '.($i + 1).' ';
                            }
                        } //for
                    }//if
                    ?>
                </div>
            <?php }//else 
            
            
            
            /*Form di inserimento/modifica*/
            if (gdrcd_filter('get', $_POST['op'] == 'edit')) {
            
            if($_POST['op'] == 'edit') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM messaggio_moderazione WHERE id=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                }
            ?>
            <div class="panels_box">
                    <form action="main.php?page=contatta_moderatore_elenco" method="post" class="form_gestione">
                    
                        <div class='form_field'>
                            <input type = "hidden" name="id" value="<?php echo 0 + $loaded_record['id']; ?>" />
                        </div> 
                        
                        <div class='form_field'>
                            <input type = "hidden" name="autore" value="<?php echo $loaded_record['autore']; ?>" />
                        </div> 
                        
                         <div class='form_label'>
                            Risposta
                        </div>
                        <div class='form_field'>
                            <textarea name="risposta"></textarea>
                        </div>
                        
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica*/
                            if($operation == "edit") {
                                ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id']; ?>">
                                <input type="hidden" name="autore" value="<?php echo $loaded_record['autore']; ?>">
                                <input type="submit" value="Rispondi" />
                                <input type="hidden" name="art" value="<?php echo 0 + $loaded_record['id']; ?>">
                                <input type="hidden" name="op" value="doedit">
                            <?php } ?>
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=contatta_moderatore_elenco">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['back']); ?>
                    </a>
                </div>
                
                <?php  }
                
                /*Modifica di un record*/
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

