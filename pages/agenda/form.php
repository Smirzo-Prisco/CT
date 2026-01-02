<?php
if (isset($_POST['submit']) && $_POST['submit']=="invia")
{
  $titolo = addslashes($_POST['titolo']);
  $testo = addslashes($_POST['testo']);
  $str_data = strtotime($_POST['data']);
  include 'config.php';
  $sql = "INSERT INTO appuntamenti (titolo,testo,str_data ) VALUES ('$titolo', '$testo', '$str_data')";
  if($result = mysql_query($sql) or die (mysql_error()))
  {
    echo "Inserimento avvenuto con successo.<br>
    Vai al <a href=\"index.php\">Calendario</a>";
  }
}else{
  ?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
Titolo:<br>
<input name="titolo" type="text"><br>
Testo:<br>
<textarea name="testo" cols="30" rows="8"></textarea><br>
Data:<br>
<input name="data" type="text" value="gg-mm-aaaa"><br>
<input name="submit" type="submit" value="invia">
</form>
  <?php
}
?>