<?php
// include 'config.php';
include('../config.inc.php');
if(isset($_POST['mod_id'])&&(is_numeric($_POST['mod_id'])))
{
  $id = $_POST['mod_id'];
  $titolo = addslashes($_POST['titolo']);
  $testo = addslashes($_POST['testo']);
  $str_data = strtotime($_POST['data']);
  $sql = "UPDATE appuntamenti SET titolo='$titolo', testo='$testo', str_data='$str_data' WHERE id = $id";
  if(mysql_query($sql) or die (mysql_error()))
  {
    echo "Modifica effettuata con successo.<br>
    Vai al <a href=\"index.php\">Calendario</a>";
  }
}
elseif (isset($_GET['id']) && is_numeric($_GET['id']))
{
  $id = $_GET['id'];
  $query = mysql_query("SELECT titolo,testo,str_data FROM appuntamenti WHERE id = $id") or die (mysql_error());
  $fetch = mysql_fetch_array($query)or die (mysql_error());
  $titolo = stripslashes($fetch['titolo']);
  $testo = stripslashes($fetch['testo']);
  $data = date("d-m-Y", $fetch['str_data']); 
  ?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">Titolo:<br>
<input name="titolo" type="text" value="<?php echo $titolo; ?>">
<br>Testo:<br>
<textarea name="testo" cols="30" rows="8">
<?php echo $testo; ?>
</textarea>
<br>Data:<br>
<input name="data" type="text" value="<?php echo $data; ?>">
<br><input name="mod_id" type="hidden" value="<?php echo $id; ?>">
<input name="submit" type="submit" value="modifica">
</form>
  <?php
}
?>