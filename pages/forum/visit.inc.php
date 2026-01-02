<?php
//Permessi
$row = gdrcd_query("SELECT * FROM araldo WHERE id_araldo = ".gdrcd_filter('num', $_REQUEST['what'])."");
$tipo = $row['tipo'];
$op=gdrcd_filter_get($_REQUEST['op']);
        

/*carico dati pg in mestiere*/
$result_pg_mestiere = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."'");
$con_job = $result_pg_mestiere['conferma_mestiere'];

// Prendi i dati del personaggio corrente
$result_corrente = gdrcd_query("SELECT p.*, g.tipo AS tipo_gilda 
                          FROM personaggio p 
                          INNER JOIN gilda g ON p.id_gilda = g.id_gilda 
                          WHERE p.nome = '". $_SESSION['login'] ."'");

// Assumi che il risultato sia un array associativo
$id_gilda_corrente = $result_corrente['id_gilda'];
$tipo_specifico_gilda = $result_corrente['tipo_gilda'];

if ($id_gilda_corrente > 0) {
// Query per selezionare i personaggi in base a id_gilda e tipo di gilda
$query_corrente = "
    SELECT p.*
    FROM personaggio p
    INNER JOIN gilda g ON p.id_gilda = g.id_gilda
    WHERE p.id_gilda = $id_gilda_corrente AND g.tipo = '$tipo_specifico_gilda'";

$result_corrente1 = gdrcd_query($query_corrente);
}

if(!(($_SESSION['admin']==1) || (($row['tipo'] <= PERTUTTI) || 
    (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || 
    (($row['tipo'] == SOLOGILDA) && ((strpos($_SESSION['gilda'],'*'.$row['proprietari'].'*') != false)) OR
    ($row['corrente'] > 0 && $row['corrente'] == $tipo_specifico_gilda)) || 
    (($row['tipo'] == SOLOMESTIERE) && ($_SESSION['mestiere']==$row['proprietari'] && $con_job == 1 || $_SESSION['capomestiere'] == 1)) || 
    (($row['tipo'] == SOLOMASTERS) && ($_SESSION['master']==1)) || 
    (($row['tipo'] == SOLOMODERATORS) && ($_SESSION['moderatore']==1)) || 
    (($row['tipo'] == SOLOGUIDES) && ($_SESSION['guida']==1)) || 
    (($row['tipo'] == SOLOCAPOGILDA) && ($_SESSION['capogilda']==1)) || 
    (($row['tipo'] == SOLOCAPOMESTIERI) && ($_SESSION['capomestiere']==1)) || 
    (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1))))){
    
    /*Restrizione di visualizzazione solo master e admin*/
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    ?>
    <div class="link_back">
        <a href="main.php?page=forum">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['back']); ?>
        </a>
    </div>
    <?php
} else {
    /*
     * Procedure messaggi importanti e chiusi
     * @author Blancks <s.rotondo90@gmail.com>
     */
    if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) {
        switch($_POST['ops']) {
            case 'important':
                $id_record = (int) $_POST['id_record'];
                $status_imp = (int) $_POST['status_imp'];
                
                if ($status_imp == '0') {
                gdrcd_query("UPDATE messaggioaraldo SET importante = $status_imp WHERE id_messaggio = $id_record") or die(mysql_error());
                } else {
                
                //se ci sono più di 4 post, ciaone! Abbassane uno!
                $what = $_REQUEST['what'];
                $check_important = gdrcd_query("SELECT * FROM messaggioaraldo WHERE importante = 1 AND id_araldo = $what", 'result');
                if(gdrcd_query($check_important, 'num_rows') > 3) {
                echo "<script type='text/javascript'>alert('Troppi topic importanti. Abbassane uno!');</script>";
                } else {
                gdrcd_query("UPDATE messaggioaraldo SET importante = $status_imp WHERE id_messaggio = $id_record") or die(mysql_error());
                }
                }
                break;

            case 'close':
                $id_record = (int) $_POST['id_record'];
                $status_cls = (int) $_POST['status_cls'];

                gdrcd_query("UPDATE messaggioaraldo SET chiuso = $status_cls WHERE id_messaggio = $id_record") or die(mysql_error());

                break;
        }
    }
    /*
     *  Fine Procedura per topic importanti/chiusi
     */
    //Determinazione pagina (paginazione)
    $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['posts_per_page'];
    $pageend = $pagebegin + $PARAMETERS['settings']['posts_per_page'];

    //Conteggio record totali
    $record_globale = gdrcd_query("SELECT COUNT(*) FROM messaggioaraldo WHERE id_messaggio_padre = -1 AND id_araldo = ".gdrcd_filter('num', $_REQUEST['what']));
    $totaleresults = $record_globale['COUNT(*)'];

    /*Carico l'elenco dei forum*/
    
    if(gdrcd_filter('get', $_POST['op']) == 'search') {
    $ricerca = $_POST['ricerca'];
    echo "<script type='text/javascript'>alert('entro');</script>";
    //$result = gdrcd_query("SELECT MA.id_messaggio, MA.id_messaggio_padre, MA.titolo, MA.autore, MA.data_messaggio, MA.data_ultimo_messaggio, MA.importante, MA.chiuso, MA.anonimo, MA.giornalista, AL.id AS new_msg FROM messaggioaraldo AS MA LEFT JOIN araldo_letto AS AL ON MA.id_messaggio=AL.thread_id AND AL.nome='".$_SESSION['login']."' WHERE MA.id_messaggio_padre = -1 AND MA.id_araldo = ".gdrcd_filter('num', $_REQUEST['what'])." AND (MA.titolo LIKE '%$ricerca%' OR MA.messaggio LIKE '%$ricerca%') GROUP BY id_messaggio_padre ORDER BY MA.importante DESC, MA.data_ultimo_messaggio DESC LIMIT ".$pagebegin.", ".$PARAMETERS['settings']['posts_per_page']."", 'result');
    } else {
    $result = gdrcd_query("SELECT MA.id_messaggio, MA.titolo, MA.autore, MA.data_messaggio, MA.data_ultimo_messaggio, MA.importante, MA.chiuso, MA.anonimo, MA.giornalista, AL.id AS new_msg FROM messaggioaraldo AS MA LEFT JOIN araldo_letto AS AL ON MA.id_messaggio=AL.thread_id AND AL.nome='".$_SESSION['login']."' WHERE MA.id_messaggio_padre = -1 AND MA.id_araldo = ".gdrcd_filter('num', $_REQUEST['what'])." ORDER BY MA.importante DESC, MA.data_ultimo_messaggio DESC LIMIT ".$pagebegin.", ".$PARAMETERS['settings']['posts_per_page']."", 'result');
    }
    
    if(gdrcd_query($result, 'num_rows') == 0) {
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['forums']['warning']['no_topic']).'</div>';
    ?>
    <div style="text-align: center; width:auto; margin: 10px auto;">
<?php 
if (($_REQUEST['what'] == 10 || $_REQUEST['what'] == 7) && ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1)) {
?>
<a href="main.php?page=forum&op=composer_quest&what=-1&where=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>">
  <img src="../../themes/crystal/imgs/forum/tasto_nuova_quest.png">
  </a>
<?php
      } else if ($_REQUEST['what'] != 10 || $_REQUEST['what'] != 141) {
?>
<a href="main.php?page=forum&op=composer&what=-1&where=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>"><img src="../../themes/crystal/imgs/forum/nuovo_messaggio.png"></a>

<?php } 
 ?>
<?php
if ($_REQUEST['what'] == 141 && ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1)) {
?>
 <form action="main.php?page=forum" method="post" name="rapido">
  <input type="hidden" name="op" value="insert_role" />
  <input type="hidden" name="araldo" value="10" />
  <input type="hidden" name="padre" value="-1" />
  <input type="image" src="../../themes/crystal/imgs/forum/topic_mensile.png">
</form>
<?php
      }
      
 ?>
 <div style="display:inline-block; float: center;">

<a href="main.php?page=forum"><img src="../../themes/crystal/imgs/forum/torna_indietro.png"></a>

</div>
</div>
</div>

<?php } else {
        ?>
        <!-- Elenco forum -->
        <div class="elenco_esteso" style="text-align:center;">
        
        <!-- comando cerca -->

<form class="searchBar" action="main.php?page=forum&op=search&where=<?php echo gdrcd_filter('out', $row['id_araldo']); ?>" method="post">
<div align="center"> 
<input name="ricerca" style="width: 200px;" placeholder="Cerca per termine, titolo o autore" />
<input type="submit" value="cerca" />
</div>
</form>


<?php 
if (($_REQUEST['what'] == 10 || $_REQUEST['what'] == 7) && ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1)) {
?>
<div style="display:inline-block; float: center;">

<a href="main.php?page=forum&op=composer_quest&what=-1&where=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>">
  <img src="../../themes/crystal/imgs/forum/tasto_nuova_quest.png">
  </a>
  
  </div>
<?php
      } else if ($_REQUEST['what'] != 10 && $_REQUEST['what'] != 141) {
?>
<div style="display:inline-block; float: center;">

<a href="main.php?page=forum&op=composer&what=-1&where=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>"><img src="../../themes/crystal/imgs/forum/nuovo_messaggio.png"></a>

</div>
<?php } 
 ?>
<?php
if ($_REQUEST['what'] == 141 && ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1)) {
?>
<div style="display:inline-block; float: center;">

 <form action="main.php?page=forum" method="post" name="rapido">
  <input type="hidden" name="op" value="insert_role" />
  <input type="hidden" name="araldo" value="10" />
  <input type="hidden" name="padre" value="-1" />
  <input type="image" src="../../themes/crystal/imgs/forum/topic_mensile.png" style="background-color: transparent; border: none; margin-bottom: -4px;">
</form>

</div>
<?php
      }
      
 ?>
 <div style="display:inline-block; float: center;">

<a href="main.php?page=forum"><img src="../../themes/crystal/imgs/forum/torna_indietro.png"></a>

</div>
</div>
            
                <table class="customTable">
                   
                    <tr class="second_header_inside"><!-- Intestazione tabella -->
                    <td>
                            <div>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['state']); ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['title']); ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['author']); ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['posts']); ?>
                            </div>
                        </td>
                        <?php
                        if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || ($_SESSION['capogilda'] == 1 && $row['tipo'] == 5) || ($_SESSION['capomestiere'] == 1 && $row['tipo'] == 6)) {  ?>
                            <td>
                                <div>
                                    <?php echo '&nbsp;'; ?>
                                </div>
                            </td>
                            <?php
                        } ?>
                    </tr>
                    <?php
                    while($row = gdrcd_query($result, 'fetch')) {
                        $readinfo = gdrcd_query("SELECT MAX(data_messaggio) AS latest, COUNT(*) AS replies FROM messaggioaraldo WHERE id_messaggio_padre = ".gdrcd_filter('get', $row['id_messaggio']));
                        $lastupdate = $readinfo['latest'];
                        $postsnumber = $readinfo['replies'];
                        
                        if ($row['titolo'] == '') {
                        $row['titolo'] = 'NUOVO MESSAGGIO';
                        }
                        ?>
                        <tr><!-- Topic -->
                        	<td style="text-align: center;">
                             <?php
                             echo ($row['chiuso']) ? '<img title"Chiuso" src="themes/crystal/imgs/forum/topic_chiuso.png">' : '<img title="Aperto" src="themes/crystal/imgs/forum/topic_aperto.png">';
                              ?>
                            </td>
                            <td>
                                <div><!-- Titolo -->
                                    <a href="main.php?page=forum&op=read&what=<?php echo gdrcd_filter('out', $row['id_messaggio']
                                    ); ?>&where=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>">
                                        <div class="forum_column">
                                            <?php
                                            /**    * Topic importante
                                             * @author Blancks <s.rotondo90@gmail.com>
                                             */
                                            #echo ($row['importante']) ? $MESSAGE['interface']['administration']['ops']['important'].': ' : '';
                                            /**    * Fine
                                             */
                                            
                                            if ($row['importante'] == 1) {
                                            ?>
                                            <font color="#9d9deb">
                                            <?php 
                                             
                                            echo gdrcd_filter('out', $row['titolo']);
                                            ?>
                                            </font>
                                            <?php
                                                    } else {
                                            
                                          echo gdrcd_filter('out', $row['titolo']);
                                                            
                                                            }
                                                   
                                            if($row['new_msg'] == 0) {
                                                echo ' ('.$MESSAGE['interface']['forums']['topic']['new_posts']['plur'].')';
                                            }
                                            ?>
                                        </div>
                                    </a>
                                    <?php
                                    /**    * Topic Chiuso
                                     * @author Blancks <s.rotondo90@gmail.com>
                                     
                                    echo ($row['chiuso']) ? '<div class="forum_column">'.$MESSAGE['interface']['forums']['topic']['title'].' '.$MESSAGE['interface']['administration']['ops']['close'].'</div>' : '';
                                    
                                     */
                                    ?>
                                    <div class="forum_date_big"><?php echo gdrcd_format_date($row['data_messaggio']).' '.gdrcd_format_time($row['data_messaggio']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div><!-- Autore -->
<?php
if ($row['anonimo'] == 'si') {
echo '<div class="forum_date_big_right">Anonimo</div>';
} elseif ($row['anonimo'] == 'ni') {
$check_alias = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio ='".$row['autore']."'");
echo '<div class="forum_date_big_right">'.$check_alias['nickname'].'</div>';
} elseif ($row['giornalista'] == 'si') {
echo '<div class="forum_date_big_right">Giornalista</div>';
} else {
?>
<a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['autore']); ?>">
<div class="forum_date_big_right"><?php echo gdrcd_filter('out', $row['autore']); ?></div>
</a>
<?php } ?>
</div>
                            </td>
                            <td>
                                <div class="forum_date_big_right"><!-- Data -->
                                    <?php echo $postsnumber.' '.gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['posts']); ?>
                                    <div class="forum_date_big_right">
                                        <?php if($postsnumber > 0) {
                                            echo gdrcd_filter('out', $MESSAGE['interface']['forums']['topic']['last_post']).':   '.gdrcd_format_date($lastupdate).' '.gdrcd_format_time($lastupdate);
                                        } ?>
                                    </div>
                                </div>
                            </td>
                            <?php
                            if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || ($_SESSION['capogilda'] == 1 && $tipo == 5) || ($_SESSION['capomestiere'] == 1 && $tipo == 6)) {
                                /**    * Topic importanti/chiusi
                                 * @author Blancks <s.rotondo90@gmail.com>
                                 */
                                $set_imp = ($row['importante']) ? '0' : '1';
                                $set_cls = ($row['chiuso']) ? '0' : '1';

                                $img_imp = ($row['importante']) ? 'freccia_giu.png' : 'freccia_su.png';
                                $img_cls = ($row['chiuso']) ? 'lucchetto_aperto.png' : 'lucchetto_chiuso.png';

                                $label_imp = ($row['importante']) ? 'important' : 'not_important';
                                $label_cls = ($row['chiuso']) ? 'close' : 'open';

                                /**    * Fine
                                 */
                                ?>
                                <td style="width: 15%">
                                    <div><!-- controlli -->

                                                                  <!--
                                                                  /**	* Topic importanti/chiusi
                                                                      * @author Blancks <s.rotondo90@gmail.com>
                                                                  */
                                                                  -->

                                                                  <!-- Importante -->
                                        <div style="display:inline-block; float: left;">
                                            <form class="controls" action="main.php?<?php echo $_SERVER['QUERY_STRING']; ?>" method="post">
                                                <input type="hidden" name="id_record" value="<?php echo $row['id_messaggio'] ?>" />
                                                <input type="hidden" name="status_imp" value="<?php echo $set_imp; ?>" />
                                                <input type="hidden" name="ops" value="important" />
                                                <input type="image" src="themes/crystal/imgs/forum/<?php echo $img_imp; ?>"
                                                       alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops'][$label_imp]); ?>"
                                                       title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops'][$label_imp]); ?>"
                                                       align="left"/>
                                            </form>
                                        </div>
                                                                  <!-- Topic Chiuso -->
                                        <div style="display:inline-block; padding-left: 15%; float: left;">
                                            <form class="controls" action="main.php?<?php echo $_SERVER['QUERY_STRING']; ?>" method="post">
                                                <input type="hidden" name="id_record" value="<?php echo $row['id_messaggio'] ?>" />
                                                <input type="hidden" name="status_cls" value="<?php echo $set_cls; ?>" />
                                                <input type="hidden" name="ops" value="close" />
                                                <input type="image" src="themes/crystal/imgs/forum/<?php echo $img_cls; ?>"
                                                       alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops'][$label_cls]); ?>"
                                                       title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops'][$label_cls]); ?>" />
                                            </form>
                                        </div>
                                        <?php }
                            
                                         if($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1 || ($_SESSION['capogilda'] == 1 && $tipo == 5)) {
                                               
                                               ?><!-- Elimina -->
                                        <div style="display:inline-block; padding-left: 15%; float: left;">
                                            <form class="controls" action="main.php?page=forum&op=delete_conf&id_record=<?php echo $row['id_messaggio']; ?>&padre=-1" method="post">
                                                
                                                <input type="image" src="themes/crystal/imgs/forum/cancella_topic.png"
                                                       alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                       title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" />
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php
                    }//while
                    gdrcd_query($result, 'free');
                    ?>
                </table>
            
        </div>
        <?php
    }//else
    ?>
    <!-- Paginatore elenco -->
    <div class="pager">
        <?php
        if($totaleresults > $PARAMETERS['settings']['posts_per_page']) {
            echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
            for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['posts_per_page']); $i++) {
                if($i != $_REQUEST['offset']) {
                    ?>
                    <a href="main.php?page=forum&op=visit&what=<?php echo gdrcd_filter('num', $_REQUEST['what']
                    ) ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                    <?php
                } else {
                    echo ' '.($i + 1).' ';
                }
            } //for
        }//if
        ?>
    </div>

    
    <?php
} //else