<?php /* HELP: Frame della chat */
/* Tipi messaggio: (A azione, P parlato, N PNG, M Master, X Moderatore, G Globale, Z OFF, I Immagine, S sussurro, D dado, C skill check, O uso oggetto) */

/*Seleziono le info sulla chat corrente*/
$info = gdrcd_query("SELECT nome, stanza_apparente, invitati, privata, proprietario, scadenza FROM mappa WHERE id=".$_SESSION['luogo']." LIMIT 1");


?>
<head>
<script>
function myFunctionChat(){
  document.querySelector("#slide").classList.toggle("start");
}
</script>
</head>
<link rel="stylesheet" href="themes/crystal/chat.css">
<div class="pagina_frame_chat">
    <div class="page_title"><h2><?php echo $info['nome']; ?></h2></div>
    <div class="page_body">
        <?php
        if($PARAMETERS['mode']['allow_new_chat_audio'] === 'ON') {
            echo '<div style="height:0;">
            <audio id="sound_player_chat">
                <source src="/sounds/'.$PARAMETERS['settings']['audio_new_messagges'].'" type="audio/wav">
            </audio>
        </div>';
        }
        //e' una stanza privata?
        if($info['privata'] == 1) {
            $allowance = false;

            if((($info['proprietario'] == gdrcd_capital_letter($_SESSION['login'])) || (strpos($_SESSION['gilda'], $info['proprietario']) != false) || (strpos($info['invitati'], gdrcd_capital_letter($_SESSION['login'])) != false) || (($PARAMETERS['mode']['spyprivaterooms'] == 'ON') && ($_SESSION['admin']==1 || $_SESSION['moderatore']==1))) && ($info['scadenza'] > strftime('%Y-%m-%d %H:%M:%S'))) {
                $allowance = true;
            }
        } else {
            $allowance = true;
        }
        //se e' privata e l'utente non ha titolo di leggerla
        if($allowance === false) {
            echo '<div class="warning">'.$MESSAGE['chat']['whisper']['privat'].'</div>';

            //echo $info['invitati']; echo gdrcd_capital_letter($_SESSION['login']);
        } else {
            ?>
            <?php $_SESSION['last_message'] = 0; ?>
            <div style="height: 1px; width: 1px;">
                <iframe src="pages/chat.inc.php?ref=30&chat=yes" class="iframe_chat" id="chat_frame" name="chat_frame" frameborder="0" allowtransparency="true">
                </iframe>
            </div>
            <div id='pagina_chat' class="chat_box">
            </div>
            <div class="panels_box">
                <div class="form_chat">
                    <!-- Form messaggi -->
                    <div class="form_row">
                        <form action="pages/chat.inc.php?ref=10&chat=yes" method="post" target="chat_frame" id="chat_form_messages">
                        <?php if($info['privata'] == 1) { ?>
                            <div class="casella_chat">
                                <select name="type" id="type">
                                    <option value="1"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][1]);//azione?></option>
                                    <option value="4"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][4]);//sussurro?></option>
                                    <option value="10"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][10]);//OFF?></option>
                                    <?php if($_SESSION['permessi'] >= GAMEMASTER) { ?>
                                        <option value="2"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][2]);//master?></option>
                                        <option value="3"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][3]);//png?></option>
                                        <option value="9"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][9]);//globale?></option>
                                    <?php } ?>
                                    <?php if($_SESSION['permessi'] >= MODERATOR) { ?>
                                       <option value="8"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][8]);//moderatore?></option>
                                    <?php } ?>
                                    <?php if(($info['privata'] == 1) && (($info['proprietario'] == $_SESSION['login']) || ((is_numeric($info['proprietario']) === true) && (strpos($_SESSION['gilda'], ''.$info['proprietario']))))) { ?>
                                        <option value="5"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][5]);//invita?></option>
                                        <option value="6"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][6]);//caccia?></option>
                                        <option value="7"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type'][7]);//elenco?></option>
                                 
                                    ?>
                                </select>
                                <br /><span class="casella_info"><?php echo gdrcd_filter('out', $MESSAGE['chat']['type']['info']); ?></span>
                            </div>
                            <?php } ?>
                            <div class="casella_chat">
                                <input name="tag" id="tag" value="" />
                                <br /><span class="casella_info">
                                    <?php echo gdrcd_filter('out', $MESSAGE['chat']['tag']['info']['tag'].$MESSAGE['chat']['tag']['info']['dst']);
                                    if($_SESSION['permessi'] >= GAMEMASTER) {
                                        echo gdrcd_filter('out', $MESSAGE['chat']['tag']['info']['png']);
                                    } ?>
	                            </span>
                            </div>
                              <?php }//if ?>
                            <div class="casella_chat">
                            <b><span id="rimanenti">2000</span></b>
                                <input name="message" id="message" onKeyup="conta(this);" maxlength="2000" value="" />
                                <br /><span class="casella_info">
	                                    <?php echo gdrcd_filter('out', $MESSAGE['chat']['tag']['info']['msg']); ?>
	                                </span>
                                <?php if($PARAMETERS['mode']['chatsave'] == 'ON') { ?>
                                    <span class="casella_info">
                                        <a href="javascript:void(0);" onClick="window.open('chat_save.proc.php','Log','width=1,height=1,toolbar=no');">
                                            Salva Chat
                                        </a>
                                    </span>
                                <?php } ?>
								    <span class="casella_info">
                                        <a href="javascript:void(0);" onClick="window.open('chat_help.proc.php','Log','toolbar=no,width=500,height=500');">
                                            Aiuto
                                        </a>
                                    </span>
                                    <span class="casella_info">
                                        <a href="javascript:void(0);" onClick="window.open('chat_writer.proc.php','Log','toolbar=no,width=600,height=600');">
                                            Writer
                                        </a>
                                    </span>
                            </div>
                            <div class="casella_chat">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                                <input type="hidden" name="op" value="new_chat_message" />
                                </div>
                                </form>
                            <?php
                            
                            if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['moderatore'] == 1) {
                            
                            ?>
                            
                            <form action="pages/chat.inc.php?ref=10&chat=yes" method="post" target="chat_frame" id="chat_form_messages">

                                <!--Pulisci chat -->
                                <input type="image" class="chat_button" src="themes/crystal/imgs/chat/pulisci_chat.png" title="Pulisci la chat" />
                                <input type="hidden" name="op" value="pulisci_chat" />
                                
                            
                        </form>
                        
                         <!--Pulisci chat -->
                         <?php } ?>
     
                    </div>
                    <div class="form_row">
                    <div class="strumenti_chat">
                    
                    <?php /* echo '<img src="themes/crystal/imgs/chat/invita.png">' */?>
                    <a href="javascript:void(0);" onClick="window.open('chat_help.proc.php','Log','toolbar=no,width=500,height=500');"><img src="themes/crystal/imgs/chat/help.png"></a>
                    <a href="javascript:void(0);" onClick="window.open('chat_writer.proc.php','Log','toolbar=no,width=600,height=600');"><img src="themes/crystal/imgs/chat/writer.png"></a>
                    <a href="javascript:void(0);" onClick="window.open('chat_save.proc.php','Log','width=1,height=1,toolbar=no');"><img src="themes/crystal/imgs/chat/blocca_salva.png"></a>
                    <a href="#"><img class="clickme" onclick="myFunctionChat();" src="themes/crystal/imgs/chat/tools.png"></a>
                    </div>
                    </div>
                    <!-- Form messaggi -->
                    <?php if(($PARAMETERS['mode']['skillsystem'] == 'ON') || ($PARAMETERS['mode']['dices'] == 'ON')) { ?>
                        <div class="form_row" id="slide">
                            <!--<form action="pages/frame_chat" method="post" target="chat_frame" id="chat_form_actions"> -->
                            <form action="pages/chat.inc.php?ref=30&chat=yes" method="post" target="chat_frame" id="chat_form_actions">
                            <?php if($PARAMETERS['mode']['skillsystem'] == 'ON') { ?>
                                    <div class="casella_chat">
                                        <?php $result = gdrcd_query("SELECT id_abilita, nome FROM abilita WHERE id_razza=-1 OR id_razza IN (SELECT id_razza FROM personaggio WHERE nome = '".$_SESSION['login']."') ORDER BY nome", 'result'); ?>
                                        <select name="id_ab" id="id_ab">
                                        <option selected disabled>Usa abilità</option>
                                            <option value="no_skill"></option>
                                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                                <option value="<?php echo $row['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $row['nome']); ?>
                                                </option>
                                            <?php }//while
                                            gdrcd_query($result, 'free');
                                            ?>
                                        </select>
                                        <br /><span class="casella_info"><?php echo gdrcd_filter('out', $MESSAGE['chat']['commands']['skills']); ?></span>
                                    </div>
                                    <div class="casella_chat">
                                        <select name="id_stats" id="id_stats">
                                        	<option selected disabled>Usa caratteristica</option>
                                            <option value="no_stats"></option>
                                            <?php
                                            /** * Questo modulo aggiunge la possibilità di eseguire prove col dado e caratteristica.
                                             * Pertanto sono qui elencate tutte le caratteristiche del pg.
                                             * @author Blancks
                                             */
                                            foreach($PARAMETERS['names']['stats'] as $id_stats => $name_stats) {
                                                if(is_numeric(substr($id_stats, 3))) {
                                                    ?>
                                                    <option value="stats_<?php echo substr($id_stats, 3); ?>"><?php echo $name_stats; ?></option>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </select>
                                        <br /><span class="casella_info"><?php echo gdrcd_filter('out', $MESSAGE['chat']['commands']['stats']); ?></span>
                                    </div>
                                <?php
                                } else {
                                    echo '<input type="hidden" name="id_ab" id="id_ab" value="no_skill">';
                                }
                                if($PARAMETERS['mode']['dices'] == 'ON') { ?>
                                    <div class="casella_chat">
                                        <select name="dice" id="dice">
                                        <option selected disabled>Tira dado</option>
                                            <option value="no_dice"></option>
                                            <?php
                                            /** * Tipi di dado personalizzati da config
                                             * @author Blancks
                                             */
                                            foreach($PARAMETERS['settings']['skills_dices'] as $dice_name => $dice_value) { ?>
                                                <option
                                                        value="<?php echo $dice_value; ?>"><?php echo $dice_name; ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                        <br /><span class="casella_info"><?php echo gdrcd_filter('out', $MESSAGE['chat']['commands']['dice']); ?></span>
                                    </div>
                                <?php
                                } else {
                                    echo '<input type="hidden" name="dice" id="dice" value="no_dice">';
                                }
                                if($PARAMETERS['mode']['skillsystem'] == 'ON') { ?>
                                    <div class="casella_chat">
                                        <?php
                                        $result = gdrcd_query("SELECT clgpersonaggiooggetto.id_oggetto, oggetto.nome, clgpersonaggiooggetto.cariche FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND posizione > 0 ORDER BY oggetto.nome", 'result'); ?>
                                        <select name="id_item" id="id_item">
                                        <option selected disabled>Usa oggetto</option>
                                            <option value="no_item"></option>
                                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                                <option value="<?php echo $row['id_oggetto'].'-'.$row['cariche'].'-'.gdrcd_filter('out', $row['nome']); ?>">
                                                    <?php echo $row['nome']; ?>
                                                </option>
                                            <?php
                                            }//while
                                            gdrcd_query($result, 'free');
                                            ?>
                                        </select>
                                        <br /><span class="casella_info"><?php echo gdrcd_filter('out', $MESSAGE['chat']['commands']['item']); ?></span>
                                    </div>
                                <?php
                                } else {
                                    echo '<input type="hidden" name="id_item" id="id_item" value="no_item">';
                                } ?>
                                <div class="casella_chat">
                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                                    <input type="hidden" name="op" value="take_action">
                                </div>
                            </form>
                        </div>
                    <?php } ?>
                    
                </div>
            </div>
        <?php }//else?>
    </div>
    <!-- Page-Body -->
</div><!-- Pagina -->

