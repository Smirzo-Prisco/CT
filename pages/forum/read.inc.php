<?php
/*carico dati pg in mestiere*/
$result_pg_mestiere = gdrcd_query("SELECT * FROM clgpersonaggiomestiere WHERE personaggio = '". $_SESSION['login'] ."'");
$con_job = $result_pg_mestiere['conferma_mestiere'];

// Prendi i dati del personaggio corrente e, se ha una gilda, ottieni i personaggi della stessa gilda
$result_corrente = gdrcd_query("
    SELECT p.*, g.tipo AS tipo_gilda 
    FROM personaggio p 
    INNER JOIN gilda g ON p.id_gilda = g.id_gilda 
    WHERE p.nome = '". $_SESSION['login'] ."'");

// Se il personaggio appartiene a una gilda
if (!empty($result_corrente['id_gilda'])) {
    $id_gilda_corrente = $result_corrente['id_gilda'];
    $tipo_specifico_gilda = $result_corrente['tipo_gilda'];

    // Query per selezionare i personaggi della stessa gilda
    $result_corrente1 = gdrcd_query("
        SELECT p.* 
        FROM personaggio p 
        INNER JOIN gilda g ON p.id_gilda = g.id_gilda 
        WHERE p.id_gilda = $id_gilda_corrente AND g.tipo = '$tipo_specifico_gilda'");
}

// Ottenere tutti gli altri messaggi
$result = gdrcd_query("
    SELECT 
        ma.id_messaggio, ma.id_messaggio_padre, ma.titolo, ma.messaggio, ma.autore, 
        ma.data_messaggio, ma.chiuso, ma.anonimo, ma.giornalista, 
        a.tipo, a.nome, a.proprietari, a.corrente, p.url_img, p.url_img_chat, a.id_araldo 
    FROM messaggioaraldo ma
    LEFT JOIN araldo a ON ma.id_araldo = a.id_araldo
    LEFT JOIN personaggio p ON ma.autore = p.nome
    WHERE (ma.id_messaggio_padre = ".gdrcd_filter('num', $_REQUEST['what'])." 
           AND ma.id_messaggio_padre != -1) 
       OR ma.id_messaggio = ".gdrcd_filter('num', $_REQUEST['what'])."
    ORDER BY ma.id_messaggio_padre, ma.data_messaggio", 'result');
$row = gdrcd_query($result, 'fetch');


                $full_message = $row['messaggio'];
                $pos = strrpos($full_message, 'Modificato da');

                // Se trovi la dicitura di modifica
                if ($pos !== false) {
                    // Estrarre la parte di modifica
                    $text_edit = substr($full_message, $pos);
                    // Rimuovere la parte di modifica dal messaggio originale
                    $clean_message = substr($full_message, 0, $pos);
                } else {
                    // Se non c'è la modifica, il messaggio è completo
                    $clean_message = $full_message;
                }
                // Sostituisci </3 con il simbolo del cuore spezzato 
                $clean_message = preg_replace('/<\/3/', '&#60;/3', $clean_message);
                
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
      (($row['tipo'] == SOLOADMIN) && ($_SESSION['admin']==1))))) {

    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {
    
    // Verifica se il thread è già stato letto
     $check_letto = gdrcd_query("SELECT COUNT(*) AS count FROM araldo_letto WHERE nome = '".$_SESSION['login']."' AND thread_id = ".gdrcd_filter('num', $_REQUEST['what']));
     
     // Se non esiste un record, inserisci come thread letto
     if($check_letto['count'] == 0) {
         gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('".$_SESSION['login']."', ".gdrcd_filter('num', $_REQUEST['where']).", ".gdrcd_filter('num', $_REQUEST['what']).")");
     }
     ?>
     <div class="forum-container_ct">
     
     <!-- Titolo del thread con link all'ultimo post -->
    <div class="forum-thread-title_ct">
        <?php echo gdrcd_filter('out', $row['nome']); ?>
        <span class="goto-last-post_ct" onclick="scrollToLastPost()">Vai all'ultimo post</span>
    </div>
    
    <!-- Messaggio del forum -->
    <div class="forum-post_ct">
        <div class="forum-author_ct">
        <?php
        // Inizializza variabili di autore e immagine
        $autore = '';
        $img_src = '';

        // Verifica il tipo di autore e imposta variabili di conseguenza
        if ($row['anonimo'] == 'si') {
            $autore = 'Anonimo';
            $img_src = 'themes/crystal/imgs/forum/anonimo.png';
        } elseif ($row['anonimo'] == 'ni') {
            $check_alias = gdrcd_query("SELECT nickname FROM tokyobook WHERE personaggio ='".$row['autore']."'");
            $autore = $check_alias['nickname'];
            $img_src = 'themes/crystal/imgs/forum/anonimo.png';
        } elseif ($row['giornalista'] == 'si') {
            $autore = 'Giornalista';
            $img_src = 'themes/crystal/imgs/forum/tae.png';
        } else {
            $autore = '<a href="main.php?page=scheda&pg='.gdrcd_filter('out', $row['autore']).'">'.gdrcd_filter('out', $row['autore']).'</a>';
            $img_src = gdrcd_filter('out', $row['url_img_chat']);
        }

        // Aggiungi il nome dell'autore per l'admin
        if ($_SESSION['admin'] == 1 && ($row['anonimo'] == 'si' || $row['anonimo'] == 'ni' || $row['giornalista'] == 'si')) {
            $autore .= ' <i>(' . $row['autore'] . ')</i>';
        }

        // Stampa il blocco HTML
        ?>
            <img src="<?php echo $img_src; ?>" alt="Avatar">
            <div class="author-name_ct">
            <?php echo $autore; ?>
            </div>
        </div>
        <div class="forum-content_ct">
            <div class="post-title_ct"><?php echo gdrcd_filter('out', $row['titolo']); ?></div>
            <div class="post-message_ct">
                <?php
            /** * Se è disponibile il plugin bbd per il trattamento del bbcode usiamo quella
            * @author Blancks
            */
            if($PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd') {
                echo bbdecoder($clean_message, true);
            } else {
                echo gdrcd_bbcoder($clean_message);
            }
            ?>
            </div>
            <div class="post-date_ct">Pubblicato il: <?php echo gdrcd_format_date($row['data_messaggio']).' '.gdrcd_format_time($row['data_messaggio']); ?></div>
            <?php if (!empty($text_edit)) { ?>
            <div class="post-date_ct">
                <?php echo gdrcd_filter('out', $text_edit); ?>
           </div>
        <?php } ?>
        
                <div class="forum-actions_ct">
                  <a href="main.php?page=forum&op=segnala&what=<?php echo $row['id_messaggio'];?>&where=<?php echo $row['id_araldo'];?>"><?php echo gdrcd_filter('out',$MESSAGE['interface']['forums']['link']['segnala']); ?></a>
                <?php if($chiuso == 0) { ?>           
                | <a href="javascript:void(0);" class="quote-button" data-autore="<?php echo htmlspecialchars(gdrcd_filter('out', $row['autore']), ENT_QUOTES); ?>" data-messaggio="<?php echo htmlspecialchars(gdrcd_filter('out', $row['messaggio']), ENT_QUOTES); ?>">Quota</a>
                <?php }  ?>
                <?php 
                if ($_REQUEST['where'] != 10 && $_REQUEST['where'] != 7) {
    // Controlla se l'utente può modificare il post
    if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1)) {
    ?>
        | <a href="main.php?page=forum&op=modifica&what=<?php echo $row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?></a>
    <?php 
    } 
                
    // Controlla se l'utente può eliminare il post
    if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1)) {
    ?>
        | <a href="main.php?page=forum&op=delete_conf&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?></a>
    <?php 
    }
} else {
    // Caso in cui $_REQUEST['where'] == 10 o $_REQUEST['where'] == 7
    // Controlla se l'utente può modificare il post
    if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1)) {
    ?>
        | <a href="main.php?page=forum&op=modifica_quest&what=<?php echo $row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?> Quest</a>
    <?php 
    } 
                
    // Controlla se l'utente può eliminare il post
    if(($_SESSION['login'] == $row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1)) {
    ?>
        | <a href="main.php?page=forum&op=delete_conf_quest&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?> Quest</a>
    <?php 
    }
}
                ?>
            </div>
            
            <?php
                            if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) {
                            /*Se ci sono px */
                            $result_px = gdrcd_query("SELECT * FROM Punti WHERE id_messaggio = ".gdrcd_filter('num', $_REQUEST['what'])." ORDER by nome", 'result');
                            if(gdrcd_query($result_px, 'num_rows') > 0) {
                            if ($_SESSION['login'] == $row['autore'] || $_SESSION['admin'] == 1) {
                            /*inizio a visualizzare i px*/
             ?>
            <!-- Inizio visualizzazione PX -->
            <div class="forum-extra-info_ct">
                <table cellpadding="10" class="customTable_ct" style="width: 100%; margin-top: 10px; background-color: #282b3a; border-radius: 8px;">
                    <thead>
                        <tr style="background-color: #ce846f; color: #1e2133; text-align: left;">
                            <th style="padding: 10px;">Personaggio</th>
                            <th style="padding: 10px;">Esperienza</th>
                            <th style="padding: 10px;">Shin</th>
                            <th style="padding: 10px;">Notorietà</th>
                            <th style="padding: 10px;">Commento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = gdrcd_query($result_px, 'fetch')) {
                            $nomepg = stripslashes(trim(htmlspecialchars($row['nome'])));
                            ?>
                            <tr style="background-color: #242635; color: #8f8f8f;">
                                <td style="padding: 10px;"><a href="/main.php?page=scheda&pg=<?= $nomepg; ?>" style="color: #ce846f; text-decoration: none;"><?= $nomepg; ?></a></td>
                                <td style="padding: 10px;"><?= $row['esperienza']; ?></td>
                                <td style="padding: 10px;"><?= $row['shin']; ?></td>
                                <td style="padding: 10px;"><?= $row['notorieta']; ?></td>
                                <td style="padding: 10px;"><?= stripslashes(ucfirst($row['commento'])); ?></td>
                            </tr>
                            <?php
                        }/*fine while*/
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
                } else {
            ?>
            <table cellpadding="10" style="margin-bottom: 20px;" width="100%" class="customTable_ct">
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
    </div>
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    <!-- risposte -->
    
     <?php
while($message_row = gdrcd_query($result, 'fetch')) {

$full_message = $message_row['messaggio'];
    $pos = strrpos($full_message, 'Modificato da');
    
    // Se trovi la dicitura di modifica
    if ($pos !== false) {
        // Estrarre la parte di modifica
        $text_edit = substr($full_message, $pos);
        // Rimuovere la parte di modifica dal messaggio originale
        $clean_message = substr($full_message, 0, $pos);
    } else {
        // Se non c'è la modifica, il messaggio è completo
        $clean_message = $full_message;
        $text_edit = ''; // Nessuna modifica
    }
    // Sostituisci </3 con il simbolo del cuore spezzato 
    $clean_message = preg_replace('/<\/3/', '&#60;/3', $clean_message);
?>
<!-- Messaggio del forum -->
<div class="forum-post_ct">
    <div class="forum-author_ct">
    <?php
    // Inizializza variabili di autore e immagine
    $autore = '';
    $img_src = '';

    // Verifica il tipo di autore e imposta variabili di conseguenza
    if ($message_row['anonimo'] == 'si') {
        $autore = 'Anonimo';
        $img_src = 'themes/crystal/imgs/forum/anonimo.png';
    } elseif ($message_row['anonimo'] == 'ni') {
        $check_alias = gdrcd_query("SELECT nickname FROM tokyobook WHERE personaggio ='".$message_row['autore']."'");
        $autore = $check_alias['nickname'];
        $img_src = 'themes/crystal/imgs/forum/anonimo.png';
    } elseif ($message_row['giornalista'] == 'si') {
        $autore = 'Giornalista';
        $img_src = 'themes/crystal/imgs/forum/tae.png';
    } else {
        $autore = '<a href="main.php?page=scheda&pg='.gdrcd_filter('out', $message_row['autore']).'">'.gdrcd_filter('out', $message_row['autore']).'</a>';
        $img_src = gdrcd_filter('out', $message_row['url_img_chat']);
    }

    // Aggiungi il nome dell'autore per l'admin
    if ($_SESSION['admin'] == 1 && ($message_row['anonimo'] == 'si' || $message_row['anonimo'] == 'ni' || $message_row['giornalista'] == 'si')) {
        $autore .= ' <i>(' . $message_row['autore'] . ')</i>';
    }

    // Stampa il blocco HTML
    ?>
        <img src="<?php echo $img_src; ?>" alt="Avatar">
        <div class="author-name_ct">
        <?php echo $autore; ?>
        </div>
    </div>
    <div class="forum-content_ct">
        <div class="post-message_ct">
            <?php
            /** * Se è disponibile il plugin bbd per il trattamento del bbcode usiamo quella
            * @author Blancks
            */
            if($PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd') {
                echo bbdecoder($clean_message, true);
            } else {
                echo gdrcd_bbcoder($clean_message);
            }
            ?>
        </div>
        <div class="post-date_ct">Pubblicato il: <?php echo gdrcd_format_date($message_row['data_messaggio']).' '.gdrcd_format_time($message_row['data_messaggio']); ?></div>
        <?php if (!empty($text_edit)) { ?>
                <div class="post-date_ct">
                    <?php echo gdrcd_filter('out', $text_edit); ?>
                </div>
            <?php } ?>
            
            <div class="forum-actions_ct">
            <?php if($chiuso == 0) { ?> 
            <a href="javascript:void(0);" class="quote-button" data-autore="<?php echo htmlspecialchars(gdrcd_filter('out', $message_row['autore']), ENT_QUOTES); ?>" data-messaggio="<?php echo htmlspecialchars(gdrcd_filter('out', $message_row['messaggio']), ENT_QUOTES); ?>">Quota</a>
            <?php }  ?>
            <?php 
            // Controlla se l'utente può modificare il post
            if(($_SESSION['login'] == $message_row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1)) {
            ?>
                | <a href="main.php?page=forum&op=modifica&what=<?php echo $message_row['id_messaggio']; ?>&prev=<?php echo $_REQUEST['where']; ?>"><?php echo $MESSAGE['interface']['forums']['link']['edit']; ?></a>
            <?php 
            } 
            
            // Controlla se l'utente può eliminare il post
            if(($_SESSION['login'] == $message_row['autore'] && $chiuso == 0) || ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1)) {
            ?>
                | <a href="main.php?page=forum&op=delete_conf&id_record=<?php echo $message_row['id_messaggio']; ?>&padre=<?php echo $message_row['id_messaggio_padre']; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']); ?></a>
            <?php 
            }
            ?>
            </div>
            
            <?php
                            if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) {
                            /*Se ci sono px */
                           $result_px_risposta = gdrcd_query("SELECT * FROM Punti WHERE id_messaggio = ".$message_row['id_messaggio']." ORDER by nome", 'result');
                            if (gdrcd_query($result_px_risposta, 'num_rows') > 0) {
                            if ($_SESSION['login'] == $row['autore'] || $_SESSION['admin'] == 1) {
                            /*inizio a visualizzare i px*/
             ?>
            <!-- Inizio visualizzazione PX -->
            <div class="forum-extra-info_ct">
                <table cellpadding="10" class="customTable_ct" style="width: 100%; margin-top: 10px; background-color: #282b3a; border-radius: 8px;">
                    <thead>
                        <tr style="background-color: #ce846f; color: #1e2133; text-align: left;">
                            <th style="padding: 10px;">Personaggio</th>
                            <th style="padding: 10px;">Esperienza</th>
                            <th style="padding: 10px;">Notorietà</th>
                            <th style="padding: 10px;">Commento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($message_row = gdrcd_query($result_px, 'fetch')) {
                        $nomepg = stripslashes(trim(htmlspecialchars($message_row['nome'])));
                        ?>
                        <tr style="background-color: #242635; color: #8f8f8f;">
                            <td style="padding: 10px;">
                                <a href="/main.php?page=scheda&pg=<?= $nomepg; ?>" style="color: #ce846f; text-decoration: none;">
                                    <?= $nomepg; ?>
                                </a>
                            </td>
                            <td style="padding: 10px;">
                                <?= $message_row['esperienza']; ?>
                            </td>
                            <td style="padding: 10px;">
                                <?= $message_row['notorieta']; ?>
                            </td>
                            <td style="padding: 10px;">
                                <?= stripslashes(ucfirst($message_row['commento'])); ?>
                            </td>
                        </tr>
                        <?php
                        }/*fine while*/
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
                } else {
            ?>
            <table cellpadding="10" style="margin-bottom: 20px;" width="100%" class="customTable_ct">
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
    </div>
      
      <?php
                }
      ?>
              <!-- Aggiungi questo ID all'ultimo post per lo scroll -->
        <div id="last-post"></div>      
                    
    <?php if ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) { ?> 
    <!-- Se l'utente è admin o moderatore, mostra il select per spostare il thread -->
    <div class="move-thread-container">
        <form action="main.php?page=forum&op=move&id_record=<?php echo $row['id_messaggio']; ?>&padre=<?php echo $row['id_messaggio_padre']; ?>" method="post">
            <label for="sposta_thread">Sposta discussione in: </label>
            <select name="newid" id="sposta_thread">
                <?php
                #------------- ELENCO ---------------
                $elenco_forum = gdrcd_query("SELECT * FROM araldo ORDER BY id_araldo, tipo", 'result');
                while ($rw = gdrcd_query($elenco_forum, 'fetch')): ?>
                    <option value="<?php echo gdrcd_filter('out', $rw['id_araldo']); ?>">
                        <?php echo gdrcd_filter('out', $rw['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>         
            <button type="submit">Sposta</button>
        </form>
    </div>
    <?php } ?>

           
</div>
<!-- fine div forum container -->
     
    
    
    
    
     <?php
            $padre = gdrcd_filter('num', $_REQUEST['what']);
            $araldo = gdrcd_filter('num', $_REQUEST['where']);          
           

            if($chiuso == 0 || $_SESSION['admin'] == 1) {
     ?>   
     
     <div class="panels_box_ct">
    <div class="form_gioco_ct">
        <form action="main.php?page=forum" method="post">
            <div class="form_label_ct">
                Risposta rapida
            </div>
            <div class="form_field_ct">
                <textarea name="messaggio" class="textarea_ct"></textarea>
            </div>
            <div class="form_info_ct">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']); ?>
            </div>

            <div class="form_submit_ct">
            <?php
    if ($araldo == 1) {
        // Verifica se il PG ha un alias
        $alias_check = gdrcd_query("SELECT nickname FROM tokyobook WHERE personaggio = '".gdrcd_filter('in', $_SESSION['login'])."'");
        ?>
        <select name="anonimo" style="height: 30px; font-size: 14px;">
            <option value="no">Non anonimo</option>
            <?php if (!empty($alias_check['nickname'])): ?>
                <option value="ni">Usa il tuo alias</option>
            <?php endif; ?>
            <option value="si">Anonimo</option>
        </select>
    <?php } ?>
                <input type="hidden" name="op" value="insert" />
                <input type="hidden" name="araldo" value="<?php echo $araldo; ?>" />
                <input type="hidden" name="padre" value="<?php echo $padre; ?>" />
                <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" class="submit_btn_ct"/>
            </div>
        </form>
    </div>
</div>
     
     
<?
}

if($araldo == 0){
$araldo = $_REQUEST["prev"];
}
?>
    <a href="main.php?page=forum&op=visit&what=<?php echo $araldo; ?>">
        <img src="../../themes/crystal/imgs/forum/tasto_indietro.png">
    </a>     
     
<?php }
   }
?>

<script>
function scrollToLastPost() {
    // Trova l'elemento con id "last-post"
    var lastPost = document.getElementById("last-post");
    
    // Scorri la pagina fino all'elemento "last-post" con un comportamento smooth
    lastPost.scrollIntoView({ behavior: 'smooth' });
}



document.addEventListener('DOMContentLoaded', function() {
    var quoteButtons = document.querySelectorAll('.quote-button');
    
    quoteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var autore = button.getAttribute('data-autore');
            var messaggio = button.getAttribute('data-messaggio');
            
            var textarea = document.querySelector('.textarea_ct');
            var quoteText = "[quote=" + autore + "]" + messaggio + "[/quote]\n";
            textarea.value += quoteText;
            textarea.focus();
        });
    });
});

</script>
