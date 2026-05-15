<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
/***************    Stampa correnti e controllo permessi di visualizzazione    *****************************/
$pg = gdrcd_query("SELECT personaggio.*, clgpersonaggioinclinazione.*
                    FROM personaggio
                    LEFT JOIN clgpersonaggioinclinazione on clgpersonaggioinclinazione.personaggio = personaggio.nome
                    WHERE personaggio.nome = '". $_SESSION['login'] ."'");
$inclinazione = $pg['id_ruolo'] ?? 0; // i ruoli sono da 1, 2 e 3. Zero non deve esistere
?>

<link rel="stylesheet" href="/themes/crystal/famiglie.css">

<div class="pagina_servizi_lavoro">
    <div class="page_title"><h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['inclinazioni']['page_name']); ?></h2></div>

<?php /* Elenco inclinazioni */
if(isset($_POST['op']) === false) { ?>
        <table class="customTable">
            <thead><tr><th colspan="8"><?php echo 'ELENCO CORRENTI' ?></th></tr></thead>
            <tbody>
                <tr class="second_header"><td></td><td>NOME</td><td></td></tr>
<?php
    try {
        $result = gdrcd_query("SELECT * FROM inclinazione WHERE visibile > 0 ORDER BY id_inclinazione", 'result', true);
    } catch (Exception $e) {
        echo '</tbody></table><div class="error">Tabella correnti non disponibile.</div>';
        $result = false;
    }

    while($result && $row = gdrcd_query($result, 'fetch')) { ?>
                <!--table class="lavori_box" border="1"-->
                <tr>
                    <td><img class="" src="imgs/inclinazioni/<?php echo $row['immagine']; ?>" /></td>
                    <td style="color: #8f8f8f;"><?php echo $row['nome']; ?></td>
                    <!-- Submit di scelta -->
                    <td>
<?php
        if($inclinazione > 0 || $pg['esperienza'] < 10 || $pg['id_gilda'] > 0) echo 'Non puoi scegliere una corrente';
        else { ?>
                        <form method="post" action="main.php?page=scegli_inclinazione">
                            <input type="submit" value="Scegli">
                            <input type="hidden" name="op" value="change">
                            <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>">
                            <input type="hidden" name="id_record" value="<?php echo $row['id_inclinazione']; ?>">
                        </form>
<?php
        } ?>
                    </td>
                </tr>
<?php
    } // while
    gdrcd_query($result, 'free'); ?>
            </table>
<?php
        if($inclinazione > 0) { ?>
            <br><br>
            <center>
                <form method="post" action="main.php?page=scegli_inclinazione">
                    <input type="submit" value="Abbandona corrente" style="width:150px"/>
                    <input type="hidden" name="op" value="quit" />
                    <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                    <input type="hidden" name="id_record" value="<?php echo $row['id_inclinazione']; ?>" />
                </form>
            </center>
<? } ?>
        </div>
<?php
}

/***************    cambio corrente o ingresso in una nuova corrente    **********************************************/
if(isset($_POST['op']) && isset($_POST['id_record']) && $_POST['op'] == 'change' && $_POST['id_record'] != '') {
    // Se il pg ha già una corrente, devo cambiargli la corrente con quella nuova
    if($inclinazione > 0) gdrcd_query("UPDATE clgpersonaggioinclinazione SET id_ruolo = ".gdrcd_filter('num', $_POST['id_record'])." WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
    
    // Se il pg non ha già una corrente, devo inserirlo nella corrente
    if($num_results == 0) gdrcd_query("INSERT INTO clgpersonaggioinclinazione (id_ruolo, personaggio) VALUES (".gdrcd_filter('num', $_POST['id_record']).", '".$_SESSION['login']."')");
    
    // Devo prendere il referente della corrente per inviargli la notifica
    $referente = gdrcd_query("SELECT * FROM inclinazione WHERE id_inclinazione ='".$_POST['id_record']."'");
    $title = 'Notifica cambio corrente';
    $text = 'Messaggio automatico per avvisarti che '.$_SESSION['login'].' si è unito alla tua corrente.';
    // Mando la notifica al referente di corrente
    send_sms('System', $referente["referente"], $title, $text);
}







if($_POST['op'] == 'quit') $quit_incli = gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");    
?>

<br><br>

<?php
    /* Ora controllamo che non sia in una famiglia */
    $check_ruolo = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    
    ?>
    <div class="pagina_servizi_lavoro">
        <!-- Titolo della pagina -->
        <div class="page_title"><h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['inclinazioni']['page_name']); ?></h2></div>
        
        <?php /*Elenco inclinazioni*/
            if(isset($_POST['op']) === false) {
                $check_corrente = gdrcd_query("SELECT clgpersonaggioinclinazione.*, inclinazione.* FROM clgpersonaggioinclinazione 
                                    JOIN inclinazione ON clgpersonaggioinclinazione.id_ruolo = inclinazione.id_inclinazione
                                    WHERE clgpersonaggioinclinazione.personaggio = '". $_SESSION['login'] ."'");
                                    
                                    if ($check_corrente['tipo'] == 1) {
    
                $query = "SELECT * FROM ruolo WHERE gilda = 1 OR gilda = 5 ORDER BY gilda";
                $result = gdrcd_query($query, 'result');
            } elseif ($check_corrente['tipo'] == 2) {
                $query = "SELECT * FROM ruolo WHERE gilda = 3 OR gilda = 4 OR gilda = 6 ORDER BY gilda";
                $result = gdrcd_query($query, 'result');
            } elseif ($check_corrente['tipo'] == 3) {
                $query = "SELECT * FROM ruolo WHERE gilda = 2 OR gilda = 7 ORDER BY gilda";
                $result = gdrcd_query($query, 'result');
            }
        ?>
            <table class="customTable">
                <thead><tr><th colspan="8"><?php echo 'ELENCO VIE MAGICHE' ?></th></tr></thead>
                <tbody>
                    <tr class="second_header"><td></td><td>NOME</td><td></td></tr>
                <?php
                    while($row = gdrcd_query($result, 'fetch')) { ?>
                    <tr>
                        <td><img class="" src="imgs/guilds/<?php echo $row['immagine']; ?>" /></td>
                        <td style="color: #8f8f8f;"><?=$row['nome_ruolo']?></td>
                        <!-- Submit di scelta -->
                        <td>
                    <?php
                        $check_id = gdrcd_query("SELECT * FROM clgpersonaggioinclinazione WHERE personaggio = '". $_SESSION['login'] ."'", 'result');
                        $numresults = gdrcd_query($check_id, 'num_rows');
                        $check_exp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
                        $check_ruolo = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
                        
                        if($numresults == 0 || $check_exp['esperienza'] < 10 || $check_ruolo['id_gilda'] > 0) echo 'Non hai abbastanza punti esperienza o hai già una gilda';                                
                        else { ?>
                            <form method="post" action="main.php?page=scegli_inclinazione">
                                <input type="submit" value="Scegli" />
                                <input type="hidden" name="op" value="pick_via" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                                <input type="hidden" name="gilda" value="<?php echo $row['gilda']; ?>" />
                            </form>
                        <?php } ?>
                        </td>
                    </tr>
                    <?php
                    }//while
                    gdrcd_query($result, 'free');
                ?>
            </table>
                
            <?php
            $check_id = gdrcd_query("SELECT * FROM clgpersonaggioinclinazione WHERE personaggio = '". $_SESSION['login'] ."'", 'result');
            $numresults = gdrcd_query($check_id, 'num_rows');
            $check_ruolo = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

            if($numresults == 0 AND $check_ruolo['id_gilda'] > 0) {
                ?>
                <br><br>
                <center>
                    <form method="post" action="main.php?page=scegli_inclinazione">
                        <input type="submit" value="Abbandona corrente" style="width:150px"/>
                        <input type="hidden" name="op" value="exit" />
                        <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
                        <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                        <input type="hidden" name="gilda" value="<?php echo $row['gilda']; ?>" />
                    </form>
                </center>
            <? } ?>
                                
            </div>
            <!--elenco_record_gioco-->
        <?php
        }//if-op
             
        //cambio/inserimento
        if($_POST['op'] == 'pick_via') {
            $check_incl = gdrcd_query("SELECT * FROM clgpersonaggioinclinazione WHERE personaggio = '". $_SESSION['login'] ."'", 'result');
            $num_results = gdrcd_query($check_incl, 'num_rows');
            
            if($num_results == 1) {
                $change_incli = gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");         
                $insert_mestiere = gdrcd_query("INSERT INTO clgpersonaggioruolo (id_ruolo, personaggio) VALUES (".gdrcd_filter('num', $_POST['id_record']).", '".$_SESSION['login']."')");
                $change_personaggio = gdrcd_query("UPDATE personaggio SET id_gilda = ".gdrcd_filter('num', $_POST['gilda']).", id_ruolo_gilda = ".gdrcd_filter('num', $_POST['id_record']).", shin = shin + 30 WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
            }//fine inserimento
        }
        
        // uscita dalla famiglia/gilda
        if($_POST['op'] == 'exit') {
            // Tolgo tutti i gradi al pg
            $exit_incli = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
            // Tolgo al pg: GILDA, RUOLO, SHIN ancora da utilizzare, SHIN utilizzati per le stats (lascio i punti assegnati tramite punti esperienza)
            $change_exit = gdrcd_query("UPDATE personaggio SET id_gilda = 0, id_ruolo_gilda = 0, shin = 0,
                                        car0 = car0-car1, car1 = 0, 
                                        car2 = car2-car3, car3 = 0, 
                                        car4 = car4-car5, car5 = 0, 
                                        car6 = car6-car7, car7 = 0, 
                                        car8 = car8-car9, car9 = 0
                                        WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");
            // Tolgo al pg tutte le SKILL di gilda (non di talento o temporanee)
            $exit_skill = gdrcd_query("DELETE clgpersonaggioabilita 
                                    FROM clgpersonaggioabilita 
                                    JOIN abilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita 
                                    WHERE clgpersonaggioabilita.nome = '" . gdrcd_filter('in', $_SESSION['login']) . "' 
                                    AND abilita.tipo NOT IN ('Talento', 'Skill temporanea')");

            $exit_log = gdrcd_query("DELETE FROM log_spesa WHERE nome = '" . gdrcd_filter('in', $_SESSION['login']) . "'");         
        }
?>

<br><br>
<div class="panels_link"><a href="main.php?page=scegli_inclinazione">Torna indietro</a></div>

<script language="JavaScript">
function Conferma(){ 
    if (confirm('Sei sicuro? Una volta confermata non potrai più cambiarla!'))
        return true;
    else 
        return false;
	}
</script>