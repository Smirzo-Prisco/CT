<?php

//Elenco messaggi paginato OFF
if($_GET['op'] == 'inviati') {
    $result = gdrcd_query("SELECT * FROM messaggi WHERE mittente = '".$_SESSION['login']."' AND mittente_del = 0 AND tipo = 'on' ORDER BY spedito DESC LIMIT ".$pagebegin.", ".$pageend."", 'result');
    $record = gdrcd_query("SELECT COUNT(*) FROM messaggi WHERE mittente = '".$_SESSION['login']."' AND mittente_del = 0 AND tipo = 'on'");
    $delType = 'mittente_del';
    $totaleresults = $record['COUNT(*)'];

} else {
    $result = gdrcd_query("SELECT * FROM messaggi WHERE destinatario = '".$_SESSION['login']."' AND tipo = 'on' AND destinatario_del = 0 ".$extracond." ORDER BY spedito DESC LIMIT ".$pagebegin.", ".$pageend."", 'result');
    $record = gdrcd_query("SELECT COUNT(*) FROM messaggi WHERE destinatario = '".$_SESSION['login']."' AND tipo = 'on' AND destinatario_del = 0 ".$extracond."");
    $delType = 'destinatario_del';
    $totaleresults = $record['COUNT(*)'];
}

$numresults = gdrcd_query($result, 'num_rows');
?>
    <div class="link_back">
        <b>MESSAGGI ON-GAME</b>
    </div>
    <?php
    if($numresults > 0) { ?>
        <table class="customTable">
            <tr class="second_header">
                <td>
                    <!-- Checkbox -->
                </td>
                <td>
                    <!-- Icona -->
                </td>
                <td>
            <span>
                <?php if($_GET['op'] == 'inviati') {
                    echo "Destinatario";
                } else {
                    echo gdrcd_filter('out', $MESSAGE['interface']['messages']['sender']);
                }
                ?>
            </span>
                </td>
                <td width="185" align="left" valign="bottom">
            <span style="font-weight:bold;">
                <?php
                if($_GET['op'] == 'inviati') {
                    echo "Inviato il";
                } else {
                    echo gdrcd_filter('out', $MESSAGE['interface']['messages']['date']);
                }
                ?>
            </span>
                </td>
                <td width="192" align="left" valign="bottom">
            <span class="titoli_elenco" style="font-weight:bold;">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['title']); ?>
 	        </span>
                </td>
                <td>
 	        <span>
 	            
 	        </span>
                </td>

            </tr>
            <?php
            while($row = gdrcd_query($result, 'fetch')) {
                ?>
                <tr>
                    <td>
                        <input type="checkbox" class="message_check_on" value="<?php echo (int) $row['id'] ?>" />
                    </td>
                    <td>
                        <div>
                            <?php
                            if($row['letto'] == 0) { ?>
                                <img src="imgs/icons/mail_new.png" class="colonna_elengo_messaggi_icon">
                                <?php
                            } else { ?>
                                <img src="imgs/icons/mail_read.png" class="colonna_elengo_messaggi_icon">
                                <?php
                            } ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php
                            if($_GET['op'] == 'inviati') {
                                echo '<a href="main.php?page=scheda&pg='.$row['destinatario'].'">'.$row['destinatario'].'</a>';
                            } elseif(is_numeric($row['mittente']) == true) {
                                echo gdrcd_filter('out', $MESSAGE['interface']['messages']['to_guild']);
                            } else {
                                echo '<a href="main.php?page=scheda&pg='.$row['mittente'].'">'.$row['mittente'].'</a>';
                            } ?>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php
                            $quando = explode(" ", $row['spedito']);

                            echo gdrcd_format_date($quando[0]).'<br/>'.gdrcd_filter('out', $MESSAGE['interface']['messages']['time']).' '.gdrcd_format_time($quando[1]);
                            ?>
                        </div>
                    </td>
                    <td>
                        <div>
                        <?php
                        if ($row['titolo'] == '') {
                        $row['titolo'] = 'Nuovo SMS';
                        }
                        ?>
                            <a target="readMessage" href="pages/messages/read.inc.php?op=read&id_messaggio=<?php echo $row['id'] ?>"><?php echo gdrcd_filter('out', substr($row['titolo'], 0, 40)); ?> 
                            
                            </a>
                        </div>
                    </td>
                    <td>
                        <?php
                        if($_GET['op'] != 'inviati') { ?>
                            <div>
                                <div>
                                    <!-- reply -->
                                   <!-- <form action="main.php?page=messages_center" method="post" target="readMessage"> -->
                                        <form action="pages/messages/create.inc.php" method="post" target="readMessage">
                                        <input type="hidden" name="reply_dest" value="<?php echo $row['mittente']; ?>" />
                                        <input type="hidden" name="genitore" value="<?php echo $row['id']; ?>" />
                                        <input type="hidden" name="op" value="reply" />
                                        <input type="submit" value="Rispondi" />
                                    </form>
                                </div>
                            </div>
                            <?php
                        } else { ?>
                            <div>
                                <div>
                                    <!-- reply -->
                                    <form action="pages/messages/create.inc.php" method="post" target="readMessage">
                                        <input type="hidden" name="reply_dest" value="<?php echo $row['destinatario']; ?>" />
                                        <input type="hidden" name="genitore" value="<?php echo $row['id']; ?>" />
                                        <input type="hidden" name="op" value="reply" />
                                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['reply']); ?>" />
                                    </form>
                                </div>
                            </div>
                            <?php
                        } ?>
                    </td>
                </tr>
                <?php
                $_SESSION['last_istant_message'] = $row['id'];
            }//while

            gdrcd_query($result, 'free');
            gdrcd_query("UPDATE personaggio SET ultimo_messaggio = ".$_SESSION['last_istant_message']." WHERE nome='".$_SESSION['login']."'");
            ?>
        </table>
        <?php
        echo '<div>
          <form id="multiple_delete_on" method="post" action="main.php?page=messages_center" onSubmit="return checked_copy_on();">
            <input type="hidden" name="op" value="erase_checked" />
            <input type="hidden" name="type" value="'.$delType.'" />
            <input type="submit" value="Cancella Messaggi Selezionati">
          </form>
        </div>';
    } else {
        if($totaleresults > $PARAMETERS['settings']['messages_limit']) {
            echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['messages']['please_erase']).'</div>';
        }
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['messages']['no_message']).'</div>';
    }
    ?>
    <div class="pager">
        <?php if($totaleresults > $PARAMETERS['settings']['messages_per_page']) {
            echo 'Vai a pagina: ';
            echo '<select onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">';
            echo '<option value=""></option>';
            for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['messages_per_page']); $i++) {
                if ($_GET['op'] == 'inviati') {
                if($i != $_REQUEST['offset']) { ?>
            
            <option value="main.php?page=messages_center&op=inviati&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>
            <?php } else {
            ?><option selected disabled="disabled" value="main.php?page=messages_center&op=inviati&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>   <?php 
                 
                } 
                } else if ($_GET['op'] != 'inviati') {
                if($i != $_REQUEST['offset']) { ?>
            
            <option value="main.php?page=messages_center&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>
            <?php } else {
            ?><option selected disabled="disabled" value="main.php?page=messages_center&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></option>   <?php 
                 
                }
                }
            }
            echo '</select>';
        } ?>
    </div>
