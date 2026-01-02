<?php
if(isset($_GET['day']) && is_numeric($_GET['day']))
{
  $day = $_GET['day'];
  // include 'config.php';
  include('../config.inc.php');
  $sql = "SELECT * FROM appuntamenti WHERE str_data=$day";
  $result = mysql_query($sql) or die (mysql_error());
  if(mysql_num_rows($result) > 0)
  {
    while($fetch = mysql_fetch_array($result))
    {
      $id = stripslashes($fetch['id']);
      $titolo = stripslashes($fetch['titolo']);
      $testo = stripslashes($fetch['testo']);
      $data = date("d-m-Y", $fetch['str_data']); 
      echo "Appuntamenti del <b>$data</b><br>" . $titolo . "<br>" . $testo . "<br>
      <a href=\"cancella.php?id=$id\">Cancella</a> |
      <a href=\"modifica.php?id=$id\">Modifica</a>
      <hr>";
    }
  } 
}
?>