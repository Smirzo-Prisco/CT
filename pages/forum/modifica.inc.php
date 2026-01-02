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
$row = gdrcd_query("SELECT titolo, autore, id_araldo, messaggio, id_messaggio_padre FROM messaggioaraldo WHERE id_messaggio=".gdrcd_filter('num', $_REQUEST['what'])."");
?>
<div class="panels_box">
    <div class="form_gioco">
    <?php
    //genero i permessi di chi può modificare
    
    if($row['autore'] == $_SESSION['login'] || ($row['autore'] != $_SESSION['login'] && $_SESSION['admin'] == 1)) {
    
    ?>
        <form action="main.php?page=forum" method="post" name="rapido">
            <?php
            if($row['id_messaggio_padre'] == -1) {
                /*Se è il primo di un topic serve un titolo*/
                ?>
                <div class="form_label">
                    <?php echo $MESSAGE['interface']['forums']['insert']['title']; ?>
                </div>
                <div class="form_field">
                    <input name="titolo" value="<?php echo gdrcd_filter('out', $row['titolo']); ?>" />
                </div>
                <?php
            }//if
            ?>
            <div class="form_label">
                <?php echo $MESSAGE['interface']['forums']['insert']['message']; ?>
            </div>
            <div class="form_field">
                <textarea name="messaggio" rows="25" cols="20" id="messaggio_bacheca" /><?php echo $row['messaggio']; ?></textarea>
            </div>
            <div class="form_info">
                <!-- BBCODE -->
                <p>formattazione testo: <br>
							<a href="javascript: bold();"><b>b</b></a>&nbsp;&nbsp;
							<a href="javascript: italic();"><i>i</i></a>&nbsp;&nbsp;
							<a href="javascript: underline();"><u>u</u></a>&nbsp;&nbsp;
							<a href="javascript: center();">Centro</a>&nbsp;&nbsp;
							<a href="javascript: img();">IMG</a>&nbsp;&nbsp;
							<a href="javascript: url();">URL</a>&nbsp;&nbsp;<br>
                            <a href="javascript: azzurro();"><font color="lightblue">Azzurro</font></a>&nbsp;&nbsp;
                            <a href="javascript: rosso();"><font color="red">Rosso</font></a>&nbsp;&nbsp;
                            <a href="javascript: verde();"><font color="green">Verde</font></a>&nbsp;&nbsp;
                            <a href="javascript: giallo();"><font color="yellow">Giallo</font></a>&nbsp;&nbsp;
						</p>
            </div>
            
            <div class="form_field">
<?php
//anonimo
if($_REQUEST["prev"] == 1) {
?>

<tr>
<td><br><center>
<select name="anonimo">
<option value="no"><?php echo gdrcd_filter('out', $MESSAGE['forum']['anon'][0]);//parlato?></option>
<?php if ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1) { ?>
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

if($_REQUEST["prev"] == 17  && $tae['id_mestiere'] == 2) {
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


                <input type="hidden" name="op" value="edit" />
                <input type="hidden" name="araldo" value="<?php echo gdrcd_filter('num', $row['id_araldo']); ?>" />
                <input type="hidden" name="prev" value="<?php echo gdrcd_filter('num', $_REQUEST['prev']); ?>" />
                <input type="hidden" name="messaggio_padre" value="<?php echo gdrcd_filter('num', $row['id_messaggio_padre']); ?>" />
                <input type="hidden" name="id_messaggio" value="<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>" />
                <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
        </form>
        <?php }//fine if permessi ?>
    </div>
</div>
<div class="link_back">
    <a href="main.php?page=forum">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
    </a>
</div>
