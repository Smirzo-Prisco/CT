<?php /* HELP: Frame della chat */
/* Tipi messaggio: (A azione, P parlato, N PNG, M Master, X Moderatore, G Globale, Z OFF, I Immagine, S sussurro, D dado, C skill check, O uso oggetto) */

/*Seleziono le info sulla chat corrente*/
$info = gdrcd_query("SELECT nome, stanza_apparente, invitati, privata, proprietario, scadenza FROM mappa WHERE id=".$_SESSION['luogo']." LIMIT 1");


?><head>


<script>
function myFunctionChat(){
  document.querySelector("#slide").classList.toggle("start");
  if(document.querySelector("#slide_weapon").classList.contains("start")){
  	document.querySelector("#slide_weapon").classList.toggle("start");
  }
  if(document.querySelector("#slide_object").classList.contains("start")){
  	document.querySelector("#slide_object").classList.toggle("start");
  }
  if(document.querySelector("#slide_master").classList.contains("start")){
  	document.querySelector("#slide_master").classList.toggle("start");
  }
}
function myFunctionChatWeapon(){
  document.querySelector("#slide_weapon").classList.toggle("start");
    if(document.querySelector('#slide').classList.contains("start")){
  	document.querySelector("#slide").classList.toggle("start");
  }
  if(document.querySelector("#slide_object").classList.contains("start")){
  	document.querySelector("#slide_object").classList.toggle("start");
  }
  if(document.querySelector("#slide_master").classList.contains("start")){
  	document.querySelector("#slide_master").classList.toggle("start");
  }
}
function myFunctionChatObject(){
  document.querySelector("#slide_object").classList.toggle("start");
    if(document.querySelector("#slide_weapon").classList.contains("start")){
  	document.querySelector("#slide_weapon").classList.toggle("start");
  }
  if(document.querySelector("#slide").classList.contains("start")){
  	document.querySelector("#slide").classList.toggle("start");
  }
  if(document.querySelector("#slide_master").classList.contains("start")){
  	document.querySelector("#slide_master").classList.toggle("start");
  }
}
function myFunctionChatMaster(){
  document.querySelector("#slide_master").classList.toggle("start");
  if(document.querySelector("#slide_object").classList.contains("start")){
  	document.querySelector("#slide_object").classList.toggle("start");
  }
    if(document.querySelector("#slide_weapon").classList.contains("start")){
  	document.querySelector("#slide_weapon").classList.toggle("start");
  }
  if(document.querySelector("#slide").classList.contains("start")){
  	document.querySelector("#slide").classList.toggle("start");
  }
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
                                        <a href="javascript:void(0);" onClick="window.open('chat_save.proc.php','Log','toolbar=no,width=500,height=500');">
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
                                
                             <!-- LANCIO DADI/PARAMETRI -->
                            <form action="main.php?page=input_valori" method="post" target="_top" id="chat_form_actions">
                            <div class="casella_chat">
                            <SELECT name=valori>
						    <option>--dadi--</option>
                            <option value="Usa destrezza">Usa destrezza</option>
                            <option value="Usa forza">Usa forza</option>
                            <option value="Usa mente">Usa mente</option>
                            <option value="Usa tempra">Usa tempra</option>
                            <option value="Usa dadi20">Usa dadi20</option> 
                            <?php
                            $result_livello = gdrcd_query("SELECT clgpersonaggioruolo.personaggio, ruolo.livello FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio WHERE clgpersonaggioruolo.personaggio = '".$_SESSION['login']."'");
                            if ($result_livello['livello'] > 3) 
                            {
                            ?>
                            <option value="AttCreatura">Attacca con creatura</option> 
                            <option value="DifCreatura">Schiva con creatura</option>
                            <?php
                            }
                            ?>
                            
                            <?php
                            //secret e magic dopo 2 azioni
                            $result_lavoro = gdrcd_query("SELECT nome, id_mestiere FROM personaggio WHERE nome = '".$_SESSION['login']."'");
                            if (
                                 ($result_lavoro['id_mestiere'] == 3 && $_SESSION['luogo'] == 24) ||
                                 ($result_lavoro['id_mestiere'] == 4 && $_SESSION['luogo'] == 14)
                               ) {
                            //controllo che ci siano minimo 2 azioni
                            
                            $check_negozio_azioni = gdrcd_query("SELECT * FROM chat WHERE stanza = ".$_SESSION['luogo']." AND mittente = '".$_SESSION['login']."' AND tipo = 'P'", 'result');
                            if (gdrcd_query($check_negozio_azioni, 'num_rows') > 1) {
                            ?>
                            <option value="oggetto_vendi">Vendi Oggetto</option> 
                            <?php
                                                                            }
                               }
                            ?>
			                </SELECT>
                           </div>  
                                <div class="casella_chat">
                                    <input type="submit" value="Lancia" />
                                <input type="hidden" name="op" value="take_action">
                                </div>
                            </form>
                            <!-- FINE LANCIO DADI/PARAMETRI -->   
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
                    <a href="javascript:void(0);" onClick="window.open('chat_help.proc.php','Help','toolbar=no,width=500,height=500');"><img title="Help" src="themes/crystal/imgs/chat/help.png"></a>
                    <a href="javascript:void(0);" onClick="window.open('chat_writer.proc.php','Writer','toolbar=no,width=600,height=600');"><img title="Notepad" src="themes/crystal/imgs/chat/writer.png"></a>
                    <a href="chat_save.proc.php" target="_blank"><img title="Blocca & Salva" src="themes/crystal/imgs/chat/blocca_salva.png"></a>
                    <a href="#"><img class="clickme" onclick="myFunctionChat();" src="themes/crystal/imgs/chat/skill.png" title="Skill"></a>
                    <a href="#"><img class="clickme" onclick="myFunctionChatWeapon();" src="themes/crystal/imgs/chat/armi.png" title="Armi"></a>
                    <a href="#"><img class="clickme" onclick="myFunctionChatObject();" src="themes/crystal/imgs/chat/object.png" title="Oggetti"></a>
                    <a href="javascript:void(0);" onClick="window.open('pages/chess.inc.php?id=<?php echo $_SESSION['luogo'];?>','Log','fullscreen,toolbar=no');"><img title="Chess" src="themes/crystal/imgs/chess/scacchiera.png"></a> 
                    <?php
                    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
                    if ($check_backing['esperienza'] > 19 && $check_backing['back_chat'] == 1) {
                    ?>
                    <a href="javascript:void(0);" onClick="window.open('main.php?page=back_chat','Log','toolbar=no,width=1,height=1');"><img title="Disattiva Backchat" src="themes/crystal/imgs/chat/BackchatON.png"></a>
                    <?php } else if ($check_backing['esperienza'] > 49 && $check_backing['back_chat'] == 0) { ?>
                    <a href="javascript:void(0);" onClick="window.open('main.php?page=back_chat','Log','toolbar=no,width=1,height=1');"><img title="Attiva Backchat" src="themes/crystal/imgs/chat/BackchatOFF.png"></a>
                    <?php } ?>
                    <?php 
                    if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) {
                    ?>
                    <a href="javascript:void(0);" onClick="window.open('pages/input_png.php','Log','toolbar=no,height=500,width=850');"><img title="Usa PNG" src="themes/crystal/imgs/chat/png_master.png"></a>
                    <a href="javascript:void(0);" onClick="window.open('main.php?page=gestione_personaggio','Log','fullscreen,toolbar=no');"><img title="Modifica salute pg" src="themes/crystal/imgs/chat/master.png"></a> 
                    <?php
                    }
                    ?>
                    </div>
                    </div>
                    <!-- Form messaggi -->
                    <?php if(($PARAMETERS['mode']['skillsystem'] == 'ON') || ($PARAMETERS['mode']['dices'] == 'ON')) { ?>
                        <div class="form_row" id="slide">
                            <!--<form action="pages/frame_chat" method="post" target="chat_frame" id="chat_form_actions"> -->
                            
                            <!-- ABILITA' e TALENTO -->
                  <form action="main.php?page=input_skill" method="post" target="_top" id="chat_form_actions">

                            <div class="casella_chat">
                                        <?php $result = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC, abilita.tipo DESC", 'result'); ?>
                                        
                                        <select name="magie">
<option selected disabled>Usa skill</option>
<optgroup label="Default">
<?php $result_default = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Default' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($default = gdrcd_query($result_default, 'fetch')) { ?>
<option value="<?php echo $default['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $default['nome']); ?>
                                                </option>
<?php }//while

//skill capogilda
if ($_SESSION['capogilda'] == 1) {

$check_famiglia = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
$capogilda_famiglia = $check_famiglia['id_gilda'];

$query = gdrcd_query("SELECT * FROM PNG
                      WHERE IDPng = ".gdrcd_filter('num', $capogilda_famiglia)."
                     ", 'result');
$row = gdrcd_query($query, 'fetch');

if ($row['Esperienza'] >= 50) {
$result_generiche_area = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Default' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result');
while($generiche_area = gdrcd_query($result_generiche_area, 'fetch')) { ?>
<option value="<?php echo $generiche_area['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $generiche_area['nome']); ?> [Skill ad area]
                                                </option>
<?php }//while

}//esperienza 50
}//fine capogilda

?>
<optgroup label="Generiche">
<?php $result_generiche = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Generica' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($generiche = gdrcd_query($result_generiche, 'fetch')) { ?>
<option value="<?php echo $generiche['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $generiche['nome']); ?>
                                                </option>
<?php }//while ?>
<optgroup label="Scudi">
<?php $result_difesa = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Difesa' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($difesa = gdrcd_query($result_difesa, 'fetch')) { ?>
<option value="<?php echo $difesa['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $difesa['nome']); ?>
                                                </option>
<?php }//while ?>
<optgroup label="Attacchi">
<?php $result_attacco = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Attacco' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($attacco = gdrcd_query($result_attacco, 'fetch')) { ?>
<option value="<?php echo $attacco['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $attacco['nome']); ?>
                                                </option>
<?php }//while ?>
<optgroup label="Mentali">
<?php $result_mentale = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Mentale' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($mentale = gdrcd_query($result_mentale, 'fetch')) { ?>
<option value="<?php echo $mentale['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $mentale['nome']); ?>
                                                </option>
<?php }//while ?>
<optgroup label="Temporanee">
<?php $result_temp = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Temporanea' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($temp = gdrcd_query($result_temp, 'fetch')) { ?>
<option value="<?php echo $temp['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $temp['nome']); ?>
                                                    <?php echo "(<i>x". $temp['usi'] ."</i>)"; ?>
                                                </option>
<?php }//while ?>
<optgroup label="Talenti">
<?php $result_talenti = gdrcd_query("SELECT * FROM clgpersonaggioabilita LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita WHERE abilita.tipo = 'Talento' && clgpersonaggioabilita.nome = '".$_SESSION['login']."' ORDER BY abilita.id_abilita DESC", 'result'); ?>
<?php while($talenti = gdrcd_query($result_talenti, 'fetch')) { ?>
<option value="<?php echo $talenti['id_abilita']; ?>">
                                                    <?php echo gdrcd_filter('out', $talenti['nome']); ?>
                                                </option>
<?php }//while ?>
</select>
                            
                          
                            
                            &nbsp; verso &nbsp;
<SELECT name=avversario>
<option>---</option>
<?php $avversario = gdrcd_query("SELECT * FROM personaggio WHERE (ora_entrata > ora_uscita AND DATE_ADD(ultimo_refresh, INTERVAL 4 MINUTE) > NOW()) AND ultimo_luogo = ".$_SESSION['luogo']."", 'result'); ?>
<?php while($row_avv = gdrcd_query($avversario, 'fetch')) { ?>
                                                <option value="<?php echo $row_avv['nome']; ?>">
                                                    <?php echo gdrcd_filter('out', $row_avv['nome']); ?>
                                                </option>
                                            <?php }//while
                                            gdrcd_query($avversario, 'free');
                                            ?>
<option>tutti</option>
</SELECT>

&nbsp; di livello &nbsp;
<SELECT name=livello>
<?php

for ($j = 1; $j <= 5; $j++) {
                                echo '<option value=\''.($j).'\'';
                                if ($j == 1) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j).'</option>';
                        }
?>
</SELECT>

                                    <input type="submit" value="Lancia" />
                                <input type="hidden" name="op" value="take_action">
                                </div>
                            </form>                            
                    <?php } ?>
                    
                </div>
                
                
                
                
                
                
                
                
                
                
                
                
                
                
<!----------- INIZIO ARMA ------------------------------>
<div class="form_row" id="slide_weapon">
<form action="main.php?page=input_arma" method="post" target="_top" id="chat_form_actions">

                            <div class="casella_chat">
                                        <?php $result = gdrcd_query("SELECT * FROM clgpersonaggiooggetto LEFT JOIN oggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND (oggetto.tipo = '5' || oggetto.tipo = '9' || oggetto.tipo = '10' || oggetto.tipo = '15')", 'result'); ?>
                                        
                                        <select name="arma">
                                        <option selected disabled>Usa arma</option>
                                            <option value="no_skill"></option>
                                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                                <option value="<?php echo $row['id_oggetto']; ?>">
                                                    <?php echo gdrcd_filter('out', $row['nome']); ?>
                                                </option>
                                            <?php }//while
                                            gdrcd_query($result, 'free');
                                            ?>
                                        </select>
                            
                          
                            
                            &nbsp; verso &nbsp;
<SELECT name=avversario>
<option>---</option>
<?php $avversario = gdrcd_query("SELECT * FROM personaggio WHERE (ora_entrata > ora_uscita AND DATE_ADD(ultimo_refresh, INTERVAL 4 MINUTE) > NOW()) AND ultimo_luogo = ".$_SESSION['luogo']."", 'result'); ?>
<?php while($row_avv = gdrcd_query($avversario, 'fetch')) { ?>
                                                <option value="<?php echo $row_avv['nome']; ?>">
                                                    <?php echo gdrcd_filter('out', $row_avv['nome']); ?>
                                                </option>
                                            <?php }//while
                                            gdrcd_query($avversario, 'free');
                                            ?>
<option>tutti</option>
</SELECT>

                                    <input type="submit" value="Usa" />
                                <input type="hidden" name="op" value="take_action">
                                </div>
                            </form>       
</div>


















<!----------- INIZIO OGGETTO ------------------------------>
<div class="form_row" id="slide_object">
<form action="pages/chat.inc.php?ref=30&chat=yes" method="post" target="chat_frame" id="chat_form_actions">
<div class="casella_chat">
                                        
<select name="id_item" id="id_item">
<option selected disabled>Usa oggetto</option>
<?php
$result_livello = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".$_SESSION['login']."'");
if ($result_livello['salute'] < 100 && $result_livello['salute'] > 49) 
                            {
echo '<optgroup label="Medicine">';
//stampo solo medicine
$medicina = gdrcd_query("SELECT clgpersonaggiooggetto.id_oggetto, oggetto.tipo, oggetto.nome, clgpersonaggiooggetto.cariche FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND posizione > 0 AND oggetto.tipo = '3' ORDER BY oggetto.nome", 'result');                                        

while($heal = gdrcd_query($medicina, 'fetch')) {
?>
<option value="<?php echo $heal['id_oggetto'].'-'.$heal['cariche'].'-'.gdrcd_filter('out', $heal['nome']); ?>">
<?php echo $heal['nome']; ?>
<?php if ($heal['cariche'] > 1) { ?> (x <?php echo $heal['cariche']; ?>) <?php } ?>
<?php
}//fine while
gdrcd_query($medicina, 'free');
}//fine if medicina
?>
<optgroup label="Potenziamenti">
<?php
//Inizio potnziamento
$pp = gdrcd_query("SELECT clgpersonaggiooggetto.id_oggetto, oggetto.tipo, oggetto.nome, oggetto.isTemp, clgpersonaggiooggetto.cariche FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND posizione > 0 AND oggetto.isTemp > '0' ORDER BY oggetto.nome", 'result');                                        

while($power = gdrcd_query($pp, 'fetch')) {
?>
<option value="<?php echo $power['id_oggetto'].'-'.$power['cariche'].'-'.gdrcd_filter('out', $power['nome']); ?>">
<?php echo $power['nome']; ?>
<?php if ($power['cariche'] > 1) { ?> (x <?php echo $power['cariche']; ?>) <?php } ?>
<?php
}//fine while
gdrcd_query($pp, 'free');
?>   

<optgroup label="Oggetti">
<?php
$result = gdrcd_query("SELECT clgpersonaggiooggetto.id_oggetto, oggetto.tipo, oggetto.nome, oggetto.isTemp, clgpersonaggiooggetto.cariche FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".$_SESSION['login']."' AND posizione > 0 AND oggetto.tipo != '3' AND oggetto.tipo != '5' AND oggetto.isTemp < '1' ORDER BY oggetto.nome", 'result'); ?>                         
<?php while($row = gdrcd_query($result, 'fetch')) { ?>
<option value="<?php echo $row['id_oggetto'].'-'.$row['cariche'].'-'.gdrcd_filter('out', $row['nome']); ?>">
<?php echo $row['nome']; ?>
</option>
<?php }//while
gdrcd_query($result, 'free');
?>
</select>



                     
                                        
                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                                    <input type="hidden" name="op" value="take_action">
                                </div>
                            </form>  
</div>
           
           
           
           
           
           
           
           
           <!---------- COMANDI MASTER ------------------->

<div class="form_row" id="slide_master">
<form action="pages/chat.inc.php?ref=10&chat=yes" method="post" target="chat_frame" id="chat_form_messages">
<div class="casella_chat">
<a href="javascript:;" onClick="window.open('main.php?page=gestione_personaggio', 'titolo', 'width=550, height=500, resizable, status, scrollbars=1, location');" target="_top">
<img src="themes/crystal/imgs/chat/strumenti_master.png" title="Modifica parametri" width="15" height="15">
</a>
&nbsp;
<input name="type" type="hidden" value="3">
&nbsp;
<input name="tag" id="tag" value="" placeholder="Nome png" style="width: 50px;">
<input name="message" id="message" onKeyup="conta(this);" maxlength="2000" value="" placeholder="Azione png" style="width: 250px;">
</div>
<div class="casella_chat">
<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
<input type="hidden" name="op" value="new_chat_message" />
</div>
</form>
</div>
           
           
           
           
           </div>
        <?php }//else?>
    </div>
    <!-- Page-Body -->
</div><!-- Pagina -->

<?php

if ($check_backing['back_chat'] == 1) {
$mex_script = "Sei sicuro di voler disattivare il backchatting?";
} else {
$mex_script = "Sei sicuro di voler attivare il backchatting?";
}
?>

<script type="text/javascript">
    var elems = document.getElementsByClassName('confirmation');
    var confirmIt = function (e) {
        if (!confirm('Sei sicuro?')) e.preventDefault();
    };
    for (var i = 0, l = elems.length; i < l; i++) {
        elems[i].addEventListener('click', confirmIt, false);
    }
</script>