<div class="destra">
<!--Immagine/descrizione -->
<div class="sfondo_dx">
</div>

<div id="avvy">
	<img src="../themes/CT_1/imgs/main_ct/colonna_dx/mini_avatar.png">
    </div> 
    
    <div id="gestione">
    <div id="pic-wrapper">
	<a href="gestione.php" target="palestra">
    <img src="../themes/CT_1/imgs/main_ct/colonna_dx/uffici_off.png" alt="" id="pic"/>
	<img src="../themes/CT_1/imgs/main_ct/colonna_dx/uffici_on.png" alt="" id="pic-inner"/></a>
    </div>
    </div> 
    
    <div id="chat">
    <div id="pic-wrapper">
	<?
    $MySql1 = "Select * From Personaggio Where Nome = '".$Login."'";
    $Result1 = mysql_query($MySql1);
    $rs1 = mysql_fetch_array($Result1);
    $Chattina = $rs1["Chattina"];
    $rs1->close;
    mysql_free_result($Result1);

    if($Chattina < 1 ){
    // Today
?>
<a href="javascript:;" onClick="window.open('chatoff.php', 'titolo', 'width=550, height=460, resizable, status, scrollbars=1, location');" target="palestra">
    <img src="../themes/CT_1/imgs/main_ct/colonna_dx/chat_off.png" alt="" id="pic"/>
	<img src="../themes/CT_1/imgs/main_ct/colonna_dx/chat_on.png" alt="" id="pic-inner"/></a>
    <?
}else {
    ?>
    <a href="javascript:;" onClick="window.open('chatoff.php', 'titolo', 'width=550, height=460, resizable, status, scrollbars=1, location');" target="palestra">
    <img src="../themes/CT_1/imgs/main_ct/colonna_dx/chat_s.png">
	</a>
    <?
}
?>
    </div>
    </div> 
    
    <div id="sms">
    <? if ($NonLetti == 0 ){ ?>
	<a href="serviziosms.php?page=sms" target="palestra">
    <img src="../themes/CT_1/imgs/main_ct/colonna_dx/sms_off.png"></a>
    <? } else { ?>
	<a href="serviziosms.php?page=sms" target="palestra">
    <img src="../themes/CT_1/imgs/main_ct/colonna_dx/sms_on.png"></a>
    <? } ?>
    </div>


    <div id="avvy_img">
    <img src="<?= $imgavvy ?>" width="100" height="100">
    </div>
    
    <div id="calendario_role">
    <a href="agenda/form2.php" target="palestra">Prenota role <?php if ($_SESSION['Master'] > 0) {?>o Evento<? }?></a>
    </div>
</div>