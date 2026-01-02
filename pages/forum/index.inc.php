<?php
/*carico dati pg*/
$result_pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
$id_gilda = $result_pg['id_gilda'];
$id_mestiere = $result_pg['id_mestiere'];

/*sezione tokyobook*/
$login = $_SESSION['login']; // Definiamo $login da usare nei check

// Step 3: Esecuzione della query e controllo del numero di righe restituite
$lettura_result = gdrcd_query("SELECT * FROM tokyobook_lettura WHERE login = '$login'", 'result');
$num_rows = gdrcd_query($lettura_result, 'num_rows');

// Step 4: Controllo se ci sono risultati e assegnazione delle variabili
if ($num_rows > 0) {
    $lettura_dati = gdrcd_query($lettura_result, 'fetch');

    // Assegna le variabili con i dati estratti
    $tokyobook_date = $lettura_dati['tokyobook'];
    $pagina_personale_date = $lettura_dati['pagina_personale'];
    $icc_date = $lettura_dati['icc'];
    $corte_date = $lettura_dati['corte'];
    $crystal_news_date = $lettura_dati['crystal_news'];
} else {
    // Inizializza le date con il valore predefinito se non c'è alcun risultato
    $tokyobook_date = '1970-01-01 00:00:00';
    $pagina_personale_date = '1970-01-01 00:00:00';
    $icc_date = '1970-01-01 00:00:00';
    $corte_date = '1970-01-01 00:00:00';
    $crystal_news_date = '1970-01-01 00:00:00';
}

// Step 5: Controllo se ci sono nuovi post rispetto alle date salvate
$nuovi_messaggi = gdrcd_query("SELECT tipo, MAX(data) AS last_post_date FROM tokyobook_bacheca WHERE tipo IN ('tokyobook', 'pagina_personale', 'icc', 'corte', 'crystal_news') GROUP BY tipo", 'result');

$nuovi_trovati = false;

// Step 6: Verifica se ci sono nuovi messaggi in ogni categoria
while ($msg = gdrcd_query($nuovi_messaggi, 'fetch')) {
    switch ($msg['tipo']) {
        case 'tokyobook':
            if ($msg['last_post_date'] > $tokyobook_date) $nuovi_trovati = true;
            break;
        case 'pagina_personale':
            if ($msg['last_post_date'] > $pagina_personale_date) $nuovi_trovati = true;
            break;
        case 'icc':
            if ($msg['last_post_date'] > $icc_date) $nuovi_trovati = true;
            break;
        case 'corte':
            if ($msg['last_post_date'] > $corte_date) $nuovi_trovati = true;
            break;
        case 'crystal_news':
            if ($msg['last_post_date'] > $crystal_news_date) $nuovi_trovati = true;
            break;
    }
}

/*carico dati pg in mestiere*/
$result_pg_mestiere = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."'");
$con_job = $result_pg_mestiere['conferma_mestiere'];

// Prendi i dati del personaggio corrente
$result_corrente = gdrcd_query("SELECT p.*, g.tipo AS tipo_gilda FROM personaggio p INNER JOIN gilda g ON p.id_gilda = g.id_gilda WHERE p.nome = '". $_SESSION['login'] ."'");

// Assumi che il risultato sia un array associativo
$id_gilda_corrente = $result_corrente['id_gilda'];
$tipo_specifico_gilda = $result_corrente['tipo_gilda'];

// Query per selezionare i personaggi in base a id_gilda e tipo di gilda
if ($id_gilda_corrente > 0) $result_corrente1 = gdrcd_query("SELECT p.* FROM personaggio p INNER JOIN gilda g ON p.id_gilda = g.id_gilda WHERE p.id_gilda = $id_gilda_corrente AND g.tipo = '$tipo_specifico_gilda'");

/*Carico l'elenco dei forum*/
$result = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo < 5) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_master = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 7) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_mod = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 8) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_guida = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 9) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_cg = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 10) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_cm = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 11) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_admin = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 12) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_super = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = -1) AND (tipo = 13) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');

$result_gilda = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari, corrente FROM araldo WHERE (proprietari = '$id_gilda' OR corrente = '$tipo_specifico_gilda') AND (tipo = 5) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_gilda_super = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari, corrente FROM araldo WHERE (proprietari > 0 OR corrente > 0) AND (tipo = 5) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');

$result_mestiere = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari = '$id_mestiere') AND (tipo = 6) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');
$result_mestiere_super = gdrcd_query("SELECT id_araldo, nome, descrizione, tipo, proprietari FROM araldo WHERE (proprietari > 0) AND (tipo = 6) AND (invisibile = 0) ORDER BY tipo, id_araldo", 'result');

$ultimotipo = -1;
?>
<!-- Elenco forum -->
        <table class="customTable">
            <?php
            while($row = gdrcd_query($result, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <!-- LINK TOKYOBOOK -->
                        <?php
                        
                        // Stampa di debug temporanea per verificare le date
                        //echo "Ultimo post Tokyobook: " . ($msg['tipo'] == 'tokyobook' ? $msg['last_post_date'] : 'N/A') . "<br>";




                                    /*        if ($row['tipo'] == 0) {
                                            
                                            // Definisci il link in base al dispositivo
                        $link_tokyobook = "main.php?page=tokyobook&tipo=tokyobook";
                        if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|BlackBerry|IEMobile|Silk/', $_SERVER['HTTP_USER_AGENT'])) {
                            $link_tokyobook = "/pages/tokyobook_mobile.php?tipo=tokyobook";
                        }

                        echo '<tr>
                                <td class="forum_main_post_author up">
                                    <div>';
                        
                        // Verifica se ci sono nuovi messaggi per Tokyobook
                        if ($nuovi_trovati) {
                            echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                        } else {
                            echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                        }

                        echo '  </div>
                            </td>
                            <td class="up">
                                <div><a href="' . $link_tokyobook . '">Tokyobook</a></div>
                            </td>
                            <td valign="top" class="up">
                                <div>Social network ufficiale di Crystal Tokyo.</div>
                            </td>
                        </tr>';
                } */
                         ?>
                         <!-- FINE TOKYOBOOK -->
                         
                         
                         
                         
                         
                         
                        <?php
                   /* } //if permessi*/
                } //if

                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result, 'free');
        ?>
            
        
        <!-- Tabella gilda NON ADMIN -->
        <?php
        if ($id_gilda!=0 AND $_SESSION['admin'] != 1) {
            while($row = gdrcd_query($result_gilda, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || ($row['corrente'] == $tipo_specifico_gilda) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_gilda, 'free');
            
        }//fine gilda
        ?>
            
        
        <!-- Tabella gilda NON ADMIN -->
        <?php
        if ($_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_gilda_super, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            else echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_gilda_super, 'free');
            
        }//fine gilda super
        ?>
            
        
        <!-- Tabella mestiere NON ADMIN -->
        <?php
        if ($id_mestiere!=0 AND $con_job != 0 AND $_SESSION['admin'] != 1) {
            while($row = gdrcd_query($result_mestiere, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_mestiere, 'free');
            
        }//fine gilda
        ?>
            
        
        <!-- Tabella gilda NON ADMIN -->
        <?php
        if ($_SESSION['admin'] == 1 OR $_SESSION['capomestiere'] == 1) {
            while($row = gdrcd_query($result_mestiere_super, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_mestiere_super, 'free');
            
        }//fine gilda super
        ?>
           
        
        <!-- Tabella master -->
        <?php
        if ($_SESSION['master'] == 1 OR $_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_master, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div><?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?></div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            else echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up"><div><?php echo gdrcd_filter('out', $row['descrizione']); ?></div></td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_master, 'free');
            
        }//fine master
        ?>
            
            
        <!-- Tabella moderatore -->
        <?php
        if ($_SESSION['moderatore'] == 1 OR $_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_mod, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div><?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?></div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            else echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_mod, 'free');
            
        }//fine mod
        ?>
            
            
        <!-- Tabella guida -->
        <?php
        if ($_SESSION['guida'] == 1 OR $_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_guida, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_guida, 'free');
            
        }//fine guida
        ?>
            
            
        <!-- Tabella capogilda -->
        <?php
        if ($_SESSION['capogilda'] == 1 OR $_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_cg, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_cg, 'free');
            
        }//fine cg
        ?>
          
          
        <!-- Tabella cm -->
        <?php
        if ($_SESSION['capomestiere'] == 1 OR $_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_cm, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                   /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                            	echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_cm, 'free');
            
        }//fine cm
        ?>
           
        <!-- Tabella admin -->
        <?php
        if ($_SESSION['admin'] == 1) {
            while($row = gdrcd_query($result_admin, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            else echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                            <?php
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                            /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                                <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <?php
                            }
                            echo gdrcd_filter('out', $row['nome']);
                            
                            if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                                ?>
                                </a>
                                <?php
                            }//if
                            ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
                <?php
            }//while
            gdrcd_query($result_admin, 'free');
        
        }//fine admin
        ?>
            
        <!-- Tabella coordinatori -->
        <?php
        if ($_SESSION['login'] == 'Lii' OR $_SESSION['login'] == 'Acamar') {
            while($row = gdrcd_query($result_super, 'fetch')) {
                if($row['tipo'] != $ultimotipo) {
                    /*Sono ordinati per tipo, se cambia stampo il nuovo tipo come capoverso*/
                    $ultimotipo = $row['tipo'];

                    /*if(($row['tipo'] != SOLORAZZA || $_SESSION['id_razza'] == $row['proprietari'] || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] != SOLOGILDA || strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') !== false || $_SESSION['permessi'] >= MODERATOR) && ($row['tipo'] < SOLOMASTERS || $_SESSION['permessi'] >= GAMEMASTER) && ($row['tipo'] < SOLOMODERATORS || $_SESSION['permessi'] >= MODERATOR)) {*/
                        ?>
                        <tr class="second_header"><!-- Intestazione tabella -->
                            <td class="up_logo" colspan="3">
                                <div>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur'].' '.strtolower($MESSAGE['interface']['forums']['type'][$ultimotipo])); ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                /* } //if permessi*/
                } //if
                $new_msg = gdrcd_query("SELECT COUNT(id) AS num FROM araldo_letto WHERE araldo_id = ".$row['id_araldo']." AND nome = '".$_SESSION['login']."';");

                $new_msg2 = gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo WHERE id_araldo = ".$row['id_araldo']." AND id_messaggio_padre = -1");
                ?>
                <tr><!-- Forum della categoria -->
                    <td class="forum_main_post_author up">
                        <div>
                            <?php
                            if($new_msg2['num'] > $new_msg['num']) {
                                echo '<img src="themes/crystal/imgs/forum/luna_accesa.png" />';
                            }
                            else{
                                echo '<img src="themes/crystal/imgs/forum/luna_spenta.png" />';
                            }
                            ?>
                        </div>
                    </td>
                    <td class="up">
                        <div>
                        <?php
                        if(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){
                        /*Restrizione di visualizzazione solo master e admin*/
                        ?>
                            <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>&name=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                        <?php
                        }
                        echo gdrcd_filter('out', $row['nome']);
                        
                        if(($_SESSION['admin'] == 1) || (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false))) || (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] || $_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1)))){ /*Restrizione di visualizzazione solo master e admin*/
                            ?>
                            </a>
                            <?php
                        }//if
                        ?>
                        </div>
                    </td>
                    <td valign=top class="up">
                        <div>
                        <?php echo gdrcd_filter('out', $row['descrizione']); ?>
                        </div>
                        </td>
                </tr>
            <?php
            }//while
            gdrcd_query($result_super, 'free');
        
        } // fine coordinatori
        ?>
            
        </table>
        <?php //Pulsante segna tutto come letto ?>
        <div class="panels_box">
            <div class="form_gioco">
                <form action="main.php?page=forum" method="post">
                    <input type="hidden" name="op" value="readall" />
                    <input type="submit" style="width:170px;" name="dummy" value="Segna tutto come letto" />
                </form>
            </div>
        </div>