<?php
include('../ref_header.inc.php');

if(empty($_SESSION['last_istant_message']) === true) {
    $_SESSION['last_istant_message'] = 0;
}

$non_letti = gdrcd_query("SELECT id FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0 AND id > ".$_SESSION['last_istant_message']."", 'result');

$max_id = gdrcd_query("SELECT max(id) as max FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0"); ?>

<link rel="stylesheet" href="../themes/crystal/hover.css" type="text/css" />


<div class="pagina_messaggi">
<table class="tg">
<tbody>
  <tr>
    <td class="tg-0lax">
    <a href="../main.php?page=mappaclick&map_id=<?php echo $_SESSION['mappa']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_mappa_off.png" title="homepage" id="pic"/>
    <img src="../themes/crystal/imgs/icone/icon_mappa_gif.gif" title="homepage" id="pic-inner"/>
    </a>
    </td>
    <td class="tg-0lax">
    <a href="../main.php?dir=<?php echo $_SESSION['luogo']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_aggiorna_off.png" title="aggiorna" id="pic"/>
    <img src="../themes/crystal/imgs/icone/icon_aggiorna_gif.gif" title="aggiorna" id="pic-inner"/>    </a>
    </td>
	<td class="tg-0lax">
<?php
if($PARAMETERS['mode']['check_messages'] === 'ON') {
    if((gdrcd_query($non_letti, 'num_rows') == 0) || ($max_id['max'] < $_SESSION['last_istant_message'])) {
        echo '<div class="messaggio_forum">';

        gdrcd_query($non_letti, 'free');

        if(empty ($PARAMETERS['names']['private_message']['image_file']) === false) {
            if(($PARAMETERS['names']['private_message']['image_file_onclick']) === true) {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file'];
            } else {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file_onclick'];
            }

            echo '<script type="text/javascript"> if (document.images) { var msg_button1_up = new Image(); msg_button1_up.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_up.'"; var msg_button1_over = new Image(); msg_button1_over.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_down.'";} function msg_over_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_over.src;}} function msg_up_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_up.src}}</script>';

            echo '<a onMouseOver="msg_over_button()" onMouseOut="msg_up_button()" href="../main.php?page=messages_center&offset=0"  target="_top"><img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file'].'" alt="'.gdrcd_filter('out',
                                                                                                                                                                                                                                                                                                          $PARAMETERS['names']['private_message']['plur']
                ).'" title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" name="msg_buttonOne" /></a>';
        } else {
            echo '<a href="../main.php?page=messages_center&offset=0" target="_top">'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'</a>';
        }
        echo '</div>';

        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON') { ?>
        <script type="text/javascript">
            parent.stop_blinking_title();
        </script>
        <?php
        }
    } else { //$_SESSION['last_istant_message']=$max_id['max']; ?>
        <div class="messaggio_forum_nuovo">
            <a href="../main.php?page=messages_center&offset=0" target="_top">
                <?php
                if(empty ($PARAMETERS['names']['private_message']['image_file_new']) === false) {
                    echo '<img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file_new'].'" alt="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" />';
                } else {
                    echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']);
                } ?>
            </a>
        </div>
        <?php
        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON'){ ?>
            <script type="text/javascript">
                parent.blink_title("(<?php echo $MESSAGE['interface']['forums']['topic']['new_posts']['sing']; ?>) <?php echo $PARAMETERS['info']['site_name']; ?>", true);
            </script>
        <?php
        }

        if($PARAMETERS['mode']['allow_audio'] == 'ON' && $_SESSION['blocca_media'] != 1 && ! empty($PARAMETERS['settings']['audio_new_messagges'])) {
            $ext = explode('.', $PARAMETERS['settings']['audio_new_messagges']);
            if(isset($PARAMETERS['settings']['audiotype']['.'.strtolower(end($ext))])) { ?>
                <object data="../sounds/<?php echo $PARAMETERS['settings']['audio_new_messagges']; ?>"
                        type="<?php echo $PARAMETERS['settings']['audiotype']['.'.strtolower(end(explode('.', $PARAMETERS['settings']['audio_new_messagges'])))]; ?>"
                        autostart="true"
                        style="width:1px; height:0px;">
                    <embed src="../sounds/<?php echo $PARAMETERS['settings']['audio_new_messagges']; ?>" autostart="true"
                           hidden="true" hidden="true" style="width:1px; height:0px;" />
                </object>

                <!--[if IE 9]>
                <embed src="../sounds/<?php echo $PARAMETERS['settings']['audio_new_messagges']; ?>" autostart="true"
                       hidden="true"/>
                <![endif]-->
            <?php
            }
        }
    }
}
?>
</td>
  </tr>
    <tr>
    
    <td class="tg-0lax">
    <a href="javascript:;" onClick="window.open('../chattina_off.php', 'titolo', 'width=550, height=500, resizable, status, scrollbars=1, location');" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_chat_off.png" title="Chat OFF">
    </a>
    </td>
    
    <td class="tg-0lax">
    <a href="../main.php?page=gestione" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_strumenti_off.png" title="gestione">
    
    </a>
    </td>
    
    <td class="tg-0lax">
    <a href="../logout.php" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_exit.png" title="esci" id="pic"/>
    <img src="../themes/crystal/imgs/icone/icon_exit_gif.gif" title="esci" id="pic-inner"/>
    </a>
    </td>
    
   </tr>
</tbody>
</table>
</div>
<?php include('../footer.inc.php');  /*Footer comune*/ ?>
