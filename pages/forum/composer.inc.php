<?php add_script('/includes/forum.js'); ?>
<script language="JavaScript">
function quote(NewCode) {
document.getElementById('messaggio').value+=NewCode;
document.rapido.messaggio.focus;
</script>

<style type="text/css">
#messaggio_bacheca {
height: 100%;
width: 80%;
}
</style>
<?php
$padre = gdrcd_filter('num', $_REQUEST['what']);
$araldo = gdrcd_filter('num', $_REQUEST['where']);

$quote = gdrcd_filter('num', $_REQUEST['quote']);

$join = '';
$cond = '';
if($padre != -1) {
    //Se sto inserendo in un thread, verifico che esista
    $join = ' INNER JOIN messaggioaraldo AS MA ON araldo.id_araldo=MA.id_araldo ';
    $cond = ' AND id_messaggio='.$padre." AND id_messaggio_padre=-1";
}

$araldoData = gdrcd_query("SELECT count(*) AS N FROM araldo".$join." WHERE araldo.id_araldo = ".$araldo.$cond);

if($araldoData['N'] > 0) {
    ?>
    <div class="panels_box">
        <div class="form_gioco">
            <form action="main.php?page=forum" method="post" name="rapido">
                <?php
                if($padre == -1) {
                    /*Se e' il primo post di un topic serve il titolo*/
                    ?>
                    <div class="form_label">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['insert']['title']); ?>
                    </div>
                    <div class="form_field">
                        <input name="titolo" />
                    </div>
                    <?php
                }
                ?>
                <div class="form_label">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['insert']['message']); ?>
                </div>
                <div class="form_field">
    <textarea name="messaggio" rows="25" cols="20" id="messaggio_bacheca">
<?php
if($quote) {
    $query = "SELECT messaggio, autore, anonimo, giornalista FROM messaggioaraldo WHERE id_messaggio=".$quote;
    $result = gdrcd_query($query);
    
    if ($result['anonimo'] == 'si' || $result['giornalista'] == 'si') {

    echo gdrcd_filter('out', "[quote]".$result['messaggio']."[/quote]");
        
        } else { 
        
    echo gdrcd_filter('out', "[quote=".$result['autore']."]".$result['messaggio']."[/quote]");
}
}
?>
</textarea>
                </div>
                <div class="form_info">
                   <!-- tasto BBCODE -->
                   <p>formattazione testo: <br>
							<a href="javascript: bold();"><b>b</b></a>&nbsp;&nbsp;
							<a href="javascript: italic();"><i>i</i></a>&nbsp;&nbsp;
							<a href="javascript: underline();"><u>u</u></a>&nbsp;&nbsp;
							<a href="javascript: center();">Centro</a>&nbsp;&nbsp;
							<a href="javascript: img();">IMG</a>&nbsp;&nbsp;
							<a href="javascript: url();">URL</a>&nbsp;&nbsp;<br>
							<a href="javascript: spoiler();">SPOILER</a>&nbsp;&nbsp;<br>
                            <a href="javascript: azzurro();"><font color="lightblue">Azzurro</font></a>&nbsp;&nbsp;
                            <a href="javascript: rosso();"><font color="red">Rosso</font></a>&nbsp;&nbsp;
                            <a href="javascript: verde();"><font color="green">Verde</font></a>&nbsp;&nbsp;
                            <a href="javascript: giallo();"><font color="yellow">Giallo</font></a>&nbsp;&nbsp;
						</p>
                </div>
                <div class="form_field">
<?php
//anonimo
if($araldo == 1) {
?>

<tr>
<td><br><center>
<select name="anonimo">
<option value="no"><?php echo gdrcd_filter('out', $MESSAGE['forum']['anon'][0]);//parlato?></option>
<?php if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1 || $tae['id_ruolo_mestiere'] == 2 || $tae['id_ruolo_mestiere'] == 37 || $tae['id_ruolo_mestiere'] == 32) { ?>
<option value="si"><?php echo gdrcd_filter('out', $MESSAGE['forum']['anon'][1]);//parlato?></option>
<?php }

//check alias

$check_alias = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio = '".$_SESSION['login']."'", 'result');
if (gdrcd_query($check_alias, 'num_rows') > 0) {
?>
<option value="ni">Usa alias</option>
<?php } ?>
</select>
</center><br></td>
</tr>

<?php } ?>

<?php

//selezionamo i pg che sono nel mestiere TAE

$tae = gdrcd_query("SELECT nome, id_mestiere FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
//giornalisti

if($araldo == 17 && $tae['id_mestiere'] == 2) {
?>

<tr>
<td><br><center>
<select name="giornalista">
<option value="no"><?php echo gdrcd_filter('out', $MESSAGE['forum']['giorn'][0]);//parlato?></option>
<option value="si"><?php echo gdrcd_filter('out', $MESSAGE['forum']['giorn'][1]);//parlato?></option>
</select>
</center><br></td>
</tr>

<?php } ?>  


                    <input type="hidden" name="op" value="insert" />
                    <input type="hidden" name="araldo" value="<?php echo $araldo; ?>" />
                    <input type="hidden" name="padre" value="<?php echo $padre; ?>" />
                    <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                </div>
            </form>
        </div>
    </div>
    <div class="link_back">
        <a href="main.php?page=forum">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
        </a>
    </div>
    <?php
} else {
    echo '<div class="warning">', $MESSAGE['interface']['administration']['forums']['not_exists'], '</div>';
}