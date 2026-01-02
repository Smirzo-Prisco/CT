<?php
include("../../ref_header.inc.php");
gdrcd_connect();
if (isset($_GET['id']) && is_numeric($_GET['id']))
{
  $del_id = $_GET['id'];
  $query = mysql_query("SELECT titolo, str_data FROM appuntamenti WHERE id ='$del_id'");
  $fetch = mysql_fetch_array($query);
  $titolo = $fetch['titolo'];
  $data = date("d-m-Y", $fetch['str_data']); 
  ?>
<head>
<head>
<link href="../main.css" rel="stylesheet" type="text/css" />
<body>
<form method="post" action="main.php?page=agenda_center&op=remove_role">
<h1>Attenzione!</h1>
Si sta per eliminare l'appuntamento
<b><?php echo stripslashes($titolo); ?></b> 
del <?php echo stripslashes($data); ?>.<br>
Confermare per eseguire l'operazione.<br>
<br>
<input name="del_id" type="hidden" value="<?php echo $del_id; ?>">
<input name="submit" type="submit" value="Cancella" class="ares">
</form>
  <?php
}
elseif(isset($_POST['del_id']) && is_numeric($_POST['del_id']))
{
  $del_id2 = $_POST['del_id'];
  if (gdrcd_query("DELETE FROM appuntamenti WHERE id = '$del_id2'"))
  {
    echo "Cancellazione del servizio avvenuta con successo<br>
    <a href=\"main.php?page=agenda_center&op=add_role\">Torna al registro</a>";
    $MySql2 = "DELETE FROM BakCalendario WHERE id_app = '$del_id2'";
    gdrcd_query($MySql2);

  }
}
?>