<?php
$id = $_POST['cancella'];
require('../../header.inc.php');
$query = gdrcd_query("SELECT * FROM messaggi WHERE id = $id");
$destinatario = $query['destinatario'];
$mittente = $query['mittente'];
$destinatario_del = $query['destinatario_del'];
$mittente_mel = $query['mittente_mel'];

if(($destinatario_del == 0 AND $mittente_del == 1) || ($destinatario_del == 1 AND $mittente_del == 0)) {
$delete = "DELETE FROM messaggi WHERE id = $id";
    gdrcd_query($delete);
    } else {
if ($destinatario == $_SESSION['login']) {   
   $change = "UPDATE messaggi SET destinatario_del = 1 WHERE id = $id";
   gdrcd_query($change);
   }
   
if ($mittente == $_SESSION['login']) {   
   $change_mitt = "UPDATE messaggi SET mittente_del = 1 WHERE id = $id";
   gdrcd_query($change_mitt);
   }
    }
echo "<script type=\"text/javascript\">parent.location.reload();</script>";
 ?>
