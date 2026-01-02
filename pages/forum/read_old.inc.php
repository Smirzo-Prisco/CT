<?php
/*$RECORDPERPAGE = 15;
$OFFSET = isset($_REQUEST['offset'])===true? gdrcd_filter('num', $_REQUEST['offset']) :0;
$flshowconvstart = true;

//Determinazione pagina (paginazione)
$pagebegin = (int) $OFFSET * $RECORDPERPAGE;
$pageend = $RECORDPERPAGE;

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

//tutti gli altri
$result = gdrcd_query("SELECT messaggioaraldo.id_messaggio, messaggioaraldo.id_messaggio_padre, messaggioaraldo.titolo, messaggioaraldo.messaggio, messaggioaraldo.autore, messaggioaraldo.data_messaggio, messaggioaraldo.chiuso, messaggioaraldo.anonimo, messaggioaraldo.giornalista, araldo.tipo, araldo.nome, araldo.proprietari, araldo.corrente, personaggio.url_img, personaggio.url_img_chat, araldo.id_araldo FROM messaggioaraldo LEFT JOIN araldo ON messaggioaraldo.id_araldo = araldo.id_araldo LEFT JOIN personaggio ON messaggioaraldo.autore = personaggio.nome WHERE (messaggioaraldo.id_messaggio_padre = ".gdrcd_filter('num', $_REQUEST['what'])." AND messaggioaraldo.id_messaggio_padre != -1) OR messaggioaraldo.id_messaggio = ".gdrcd_filter('num', $_REQUEST['what'])." ORDER BY id_messaggio_padre, data_messaggio", 'result');

/*$variabile = "SELECT messaggioaraldo.id_messaggio, messaggioaraldo.id_messaggio_padre, messaggioaraldo.titolo, messaggioaraldo.messaggio, messaggioaraldo.autore, messaggioaraldo.data_messaggio, messaggioaraldo.chiuso, messaggioaraldo.anonimo, messaggioaraldo.giornalista, araldo.tipo, araldo.nome, araldo.proprietari, personaggio.url_img, personaggio.url_img_chat, araldo.id_araldo 
FROM messaggioaraldo LEFT JOIN araldo ON messaggioaraldo.id_araldo = araldo.id_araldo 
LEFT JOIN personaggio ON messaggioaraldo.autore = personaggio.nome 
WHERE (messaggioaraldo.id_messaggio_padre = ".gdrcd_filter('num', $_REQUEST['what'])." AND messaggioaraldo.id_messaggio_padre != -1) OR messaggioaraldo.id_messaggio = ".gdrcd_filter('num', $_REQUEST['what'])." 
ORDER BY id_messaggio_padre, data_messaggio";

$total_result = gdrcd_query($variabile, 'result');
$totaleresults = gdrcd_query($total_result, 'num_rows'); //numero risultati
gdrcd_query($total_result, 'free');

//Messaggi (della pagina richiesta)
			$variabile .= " "." LIMIT ".$pagebegin.", ".$pageend;
			$total_result = gdrcd_query($variabile,'result');	*/
            
 $row = gdrcd_query($result, 'fetch');



if( ! empty($row)) {
    $araldo = (int) $row['id_araldo'];
    $chiuso = $row['chiuso'];

    if(!(($_SESSION['admin']==1) || 
      (($row['tipo'] <= PERTUTTI) || (($row['tipo'] == SOLORAZZA) && ($_SESSION['id_razza'] == $row['proprietari'])) || 
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
    } else {
        //Inserimento il record al pg come thread letto
        $check_letto = gdrcd_query("SELECT * FROM araldo_letto WHERE nome = '".$_SESSION['login']."' AND thread_id = ".gdrcd_filter('num', $_REQUEST['what']));
        if($check_letto['id'] <= 0) {
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('".$_SESSION['login']."', ".gdrcd_filter('num', $_REQUEST['where']).", ".gdrcd_filter('num', $_REQUEST['what']).")");
        }
        ?>
        <div class="panels_box">
            <table class="customTable" style="width: 80%;">
                <tr bgcolor="#15182a"><!-- Intestazione tabella -->
                    <td colspan="2">
                        <div class="header_thread">
                            <?php echo gdrcd_filter('out', $row['nome']); ?>
                        </div>
                    </td>
                </tr>
                <tr bgcolor="#15182a">
                    <td colspan="2" class="forum_main_title">
                    
                  


                        <div class="forum_post_title">
                        
                            <?php echo gdrcd_filter('out', $row['titolo']); 
                            
                            if ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) { ?>                            
                            
                            <!-- inizio della select sposta -->
                            
       <form action="main.php?page=forum&op=move&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>&newid=<?php echo $rw['id_araldo']; ?>" method="post">
       <div align="center">Sposta Discussione in:  
	   <select name="newid" style="width:150px;">
<?
    #------------- ELENCO ---------------
    $elenco_forum = gdrcd_query("SELECT * FROM araldo ORDER BY id_araldo, tipo", 'result');
    while($rw = gdrcd_query($elenco_forum, 'fetch')) {
 ?>
<option value=<?php echo gdrcd_filter('out', $rw['id_araldo']); ?>> 
<?php echo gdrcd_filter('out', $rw['nome']); ?></option>
<?
    }
?> 
</select>         
         <input type="submit" name="Submit" value="Sposta" style="width:90px;">
      
    </div></form>
    
    <!-- fine select sposta -->
    <?php } ?>
 
<!-- Link "Vai all'ultimo post" visibile solo su tablet e cellulari -->
<style>
    @media only screen and (max-width: 768px) {
        .go-to-last-post {
            display: block;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            background-color: #ce846f;
            color: white;
            text-transform: uppercase;
            font-weight: bold;
            border-radius: 5px;
            text-decoration: none;
        }
    }
</style>

<a href="#last-post" class="go-to-last-post">Vai all'ultimo post</a> 
 
 <!-- Paginatore elenco -->



<div class="pager" style="clear:both; padding: 15px 0px;">

	<?php 
/*	if($totaleresults > $RECORDPERPAGE) {
    echo 'Vai a pagina: ';
    echo '<select onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);" style="border-radius: 10px; background-color: #181c31; color: #8f8f8f; font-family: "DejaVu Serif"; border: 2px solid #07080e;">';
    echo '<option value=""></option>';
            
            
		echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
		// limita il numero di pagine da poter visualizzare a 10
		$maxPage = ceil($totaleresults / $RECORDPERPAGE-1)<=gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1? ceil($totaleresults / $RECORDPERPAGE-1):gdrcd_filter('num', $PARAMETERS['setting']['msg']['group']['maxpagemsgtoview'])-1;
		for($i = 0; $i <= $maxPage; $i++) {
			if($i != $OFFSET) {
				?>
      <option value="main.php?page=forum&op=read&what=<?php echo gdrcd_filter('out', $row['id_messaggio']
                                    ); ?>&where=<?php echo gdrcd_filter('num', $_REQUEST['where']); ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>      <?php } else { ?>
      <option selected disabled="disabled" value="../../pages/msg/msg_read_conversation.inc.php?op=read&group=<?php echo $IDGROUP; ?>&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>
	  <?php
			}
		}
        echo '</select>';
	}
	*/
    ?>
<!-- </div>

fine paginatore elenco -->
 
 </div>
                    </td>
                </tr>
                <tr>
                    <td class="author">
                        <div>
                            <?php
if ($row['anonimo'] == 'si') {
echo '<div class="autore">Anonimo';
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                <img src="themes/crystal/imgs/forum/anonimo.png"
                                     class="img_forum_avatar">
                            </div>
<?php
} else if ($row['anonimo'] == 'ni') {
$check_alias = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio ='".$row['autore']."'");
echo '<div class="autore">'.$check_alias['nickname']; 
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                <img src="themes/crystal/imgs/forum/anonimo.png"
                                     class="img_forum_avatar">
                            </div>
<?php
} else if ($row['giornalista'] == 'si') {
echo '<div class="autore">Giornalista'; 
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                <img src="themes/crystal/imgs/forum/tae.png"
                                     class="img_forum_avatar">
                            </div>
<?php
} else {
?>
<a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['autore']); ?>">
                                <div class="autore"><?php echo gdrcd_filter('out', $row['autore']); ?></div>
                            </a>

<br>                            <div class="forum_avatar">
                                <img src="<?php echo gdrcd_filter('out', $row['url_img_chat']); ?>"
                                     class="img_forum_avatar">
                            </div>
                            <?php } ?>
                            <div class="autore">
                                <br><?php echo gdrcd_format_date($row['data_messaggio']).' <br>'.gdrcd_format_time($row['data_messaggio']); ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="forum_post_message">
                            <?php

                            /** * Se è disponibile il plugin bbd per il trattamento del bbcode usiamo quella
                             * @author Blancks
                             */
                            if($PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd') {
                                echo bbdecoder(gdrcd_filter('out', $row['messaggio']), true);
                            } else {
                                echo gdrcd_bbcoder(gdrcd_filter('out', $row['messaggio']));
                            }
                            ?>
                        </div>
                        <div class="forum_post_modify">
                        
                        <a href="main.php?page=forum&op=segnala&what=<?php echo $row['id_messaggio'];?>&where=<?php echo $row['id_araldo'];?>"><?php echo gdrcd_filter('out',$MESSAGE['interface']['forums']['link']['segnala']); ?></a> |
                            <?php
                            if($chiuso == 0 || $_SESSION['permessi'] >= MODERATOR) {
                                ?>
                                <a href="main.php?page=forum&op=composer&what=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>&where=<?php echo gdrcd_filter('num', $_REQUEST['where']); ?>&quote=<?php echo $row['id_messaggio']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['quote']); ?></a> |
                                <?php
                            }
                            if ($_REQUEST['where'] == 10 || $_REQUEST['where'] == 7) {
                            if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1)) {
                                ?>
                                <a href="main.php?page=forum&op=modifica_quest&what=<?php echo $row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?> Quest</a> |
                                <a href="main.php?page=forum&op=delete_conf_quest&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>">[<?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?> Quest]</a>
                                <?php
                            }
                            }
                            
                            if ($_REQUEST['where'] != 10 && $_REQUEST['where'] != 7) {
                            if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1)) {
                                ?>
                                <a href="main.php?page=forum&op=modifica&what=<?php echo $row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?></a> |
                                <?php
                            } 
                            if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1)) {
                            ?><a href="main.php?page=forum&op=delete_conf&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?></a>
                                <?php
                            }
                            }
                            ?>
                            
                            <?php
                            if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) {
                            /*Se ci sono px */
                            $result_px = gdrcd_query("SELECT * FROM Punti WHERE id_messaggio = ".gdrcd_filter('num', $_REQUEST['what'])." ORDER by nome", 'result');
                            if(gdrcd_query($result_px, 'num_rows') > 0) {
                            if ($_SESSION['login'] == $row['autore'] || $_SESSION['admin'] == 1) {
                            /*inizio a visualizzare i px*/
                            ?>
                            
                    <table cellpadding="10" style="margin-bottom: 20px;" width="100%" class="customTable">
                        <tr align="left">
                            <th>Personaggio</th>
                            <th>Esperienza</th>
                            <th>Notoriet&agrave;</th>
                            <th>Commento</th>
                        </tr>
                        <?php
                        while ($row = gdrcd_query($result_px, 'fetch')) {
                        $nomepg = stripslashes(trim(htmlspecialchars($row['nome'])));
                        ?>
		                <tr>
			                <td style="padding-left: 10px;">
				                <a href="/main.php?page=scheda&pg=<?= $nomepg; ?>">
					                <?= $nomepg; ?>
				                </a>
				            </td>
				            <td style="padding-left: 10px;">
				                <?= $row['esperienza']; ?>
				            </td>
                            <td style="padding-left: 10px;">
				                <?= $row['notorieta']; ?>
				            </td>
				            <td style="padding-left: 10px;">
				                <?= stripslashes(ucfirst($row['commento'])); ?>
				            </td>
				        </tr>
	                <?php
                        }/*fine while*/
                        ?>
                        </table>
                        
                        <?php
                            } else {
                        ?>
                         <table cellpadding="10" style="margin-bottom: 20px;" width="100%" class="customTable">
                        <tr align="left">
                        <td>Solo l'autore del post pu&ograve; vedere i punti da lui assegnati
                        </td>
                        </tr>
                        </table>
                        <?php
                        }//fine else
                            }/*fine if*/
                               }//fine permessi master e admin punti
                            ?>
                    
                        </div>
                    </td>
                </tr>
                <?php
                while($row = gdrcd_query($result, 'fetch')) {
                    ?>
                    <tr>
                        <td class="author">
                            <div>
                                                        <?php
if ($row['anonimo'] == 'si') {
echo '<div class="autore">Anonimo';
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                    <img src="themes/crystal/imgs/forum/anonimo.png" class="img_forum_avatar">
                                </div>
<?php
} else if ($row['anonimo'] == 'ni') {
$check_alias = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio ='".$row['autore']."'");
echo '<div class="autore">'.$check_alias['nickname']; 
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                <img src="themes/crystal/imgs/forum/anonimo.png"
                                     class="img_forum_avatar">
                            </div>
<?php
}else if ($row['giornalista'] == 'si') {
echo '<div class="autore">Giornalista'; 
if ($_SESSION['admin'] == 1) {
echo ' <i>('.$row['autore'].')</i></div>';
}
?>
<br><div class="forum_avatar">
                                <img src="themes/crystal/imgs/forum/tae.png"
                                     class="img_forum_avatar">
                            </div>
<?php
} else {
?>
                                <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['autore']); ?>">
                                    <div class="autore"><?php echo gdrcd_filter('out', $row['autore']); ?></div>
                                </a>
                                
<br>                                <div class="forum_avatar">
                                    <img src="<?php echo gdrcd_filter('out', $row['url_img_chat']); ?>" class="img_forum_avatar">
                                </div>
<?php } ?>

                                <div class="autore">
                                  <br>  <?php echo gdrcd_format_date($row['data_messaggio']).' <br> '.gdrcd_format_time($row['data_messaggio']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="forum_post_message">
                                <?php
                                /** * Se è disponibile il plugin bbd per il trattamento del bbcode usiamo quella
                                 * @author Blancks
                                 */
                                if($PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd') {
                                    echo bbdecoder(gdrcd_filter('out', $row['messaggio']), true);

                                } else {
                                    echo gdrcd_bbcoder(gdrcd_filter('out', $row['messaggio']));
                                }

                                ?>
                            </div>
                            <div class="forum_post_modify">
                          
                                <?php
                                if($chiuso == 0 || $_SESSION['permessi'] >= MODERATOR) {
                                    ?>
                                    <a href="main.php?page=forum&op=composer&what=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>&where=<?php echo gdrcd_filter('num', $_REQUEST['where']); ?>&quote=<?php echo $row['id_messaggio']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['quote']); ?></a> |
                                    <?php
                                }
                                if(($_SESSION['login'] == $row['autore'] && $row['chiuso'] == 0) || ($_SESSION['admin'] == 1)) {
                                    ?>
                                	<a href="main.php?page=forum&op=modifica&what=<?php echo $row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?></a> |
                               <?php
                               }
                               
                               if(($_SESSION['login'] == $row['autore'] && $row['chiuso'] == 0) || ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1)) {
                               ?>
                               <a href="main.php?page=forum&op=delete_conf&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?></a>
                                    <?php
                                }
                                
                                
                                ?>
                              
                              <?php
                            /*Se ci sono px */
                            $result_px_risposta = gdrcd_query("SELECT * FROM Punti WHERE id_messaggio = ".$row['id_messaggio']." ORDER by nome", 'result');
                            if (gdrcd_query($result_px_risposta, 'num_rows') > 0) {
                            
                            /*inizio a visualizzare i px*/
                            ?>
                            
                        <table cellpadding="10" style="margin-bottom: 20px;" width="100%" class="customTable">
                        <tr align="left">
                            <th>Personaggio</th>
                            <th>Esperienza</th>
                            <th>Notoriet&agrave;</th>
                            <th>Commento</th>
                        </tr>
                        <?php
                        while ($row_risposta = gdrcd_query($result_px_risposta, 'fetch')) {
                        $nomepg_risposta = stripslashes(trim(htmlspecialchars($row_risposta['nome'])));
                        ?>
		                <tr>
			                <td style="padding-left: 10px;">
				                <a href="/scheda.php?pg=<?= $nomepg_risposta; ?>">
					                <?= $nomepg_risposta; ?>
				                </a>
				            </td>
				            <td style="padding-left: 10px;">
				                <?= $row_risposta['esperienza']; ?>
				            </td>
                            <td style="padding-left: 10px;">
				                <?= $row_risposta['notorieta']; ?>
				            </td>
				            <td style="padding-left: 10px;">
				                <?= stripslashes(ucfirst($row_risposta['commento'])); ?>
				            </td>
				        </tr>
	                <?php
                        }/*fine while*/
                        ?>
                        </table>
                        
                        <?php
                            }/*fine if*/
                            ?>
                            
                            </div>
                        </td>
                    </tr>
                    <?php
                }//while
                
                gdrcd_query($result, 'free');
                ?>
            </table>
        </div>
        
        <!-- Aggiungi questo ID all'ultimo post per lo scroll -->
        <div id="last-post"></div>

        <?php
        if($_SESSION['admin'] == 1) {
            $padre = gdrcd_filter('num', $_REQUEST['what']);
            $araldo = gdrcd_filter('num', $_REQUEST['where']);
            ?>
            <div class="panels_box">
                <div class="form_gioco">
                    <form action="main.php?page=forum"
                          method="post">
                        <span color="000000">
                            <div class="form_label">
                                Risposta rapida
                            </div>
                            <div class="form_field">
                                <textarea name="messaggio" /></textarea>
                            </div>
                            <div class="form_info">
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']); ?>
                            </div>
                        </span>

                        <div class="form_submit">
                            <input type="hidden" name="op" value="insert" />
                            <input type="hidden" name="araldo" value="<?php echo $araldo; ?>" />
                            <input type="hidden" name="padre" value="<?php echo $padre; ?>" />
                            <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                        </div>
                    </form>
                </div>
            </div>
            <?php
        } //Fine risposta rapida
    }//else
    ?>
    
    
    <!-- link a fondo pagina -->
    <div class="link_back">
    <?php
    if($chiuso == 0 || $_SESSION['permessi'] >= MODERATOR) {
        ?>
        <a href="main.php?page=forum&op=composer&what=<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>&where=<?php echo gdrcd_filter('num', $_REQUEST['where']); ?>">
            <img src="../../themes/crystal/imgs/forum/tasto_rispondi.png">
        </a>
        <?php
    } ?>


     <?php
   
}//!empty
else {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['interface']['forums']['warning']['topic_not_exists']).'</div>';
}
?>
<?
if($araldo == 0){
$araldo = $_REQUEST["prev"];
}
?>
    <a href="main.php?page=forum&op=visit&what=<?php echo $araldo; ?>">
        <img src="../../themes/crystal/imgs/forum/tasto_indietro.png">
    </a><br />
    </div>
    
    
    
