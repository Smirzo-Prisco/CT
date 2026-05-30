<?php
session_start();
include ("../inc/parametri.inc.php");
include ("../inc/controllo.php");
include ("../inc/open2.php");
include ('../inc/header.html.inc.php');

$Login = $_SESSION['Login'];
$pg = trim($pg);
if ($pg == '') {
	$pg = $Login;
}
?>
<head>
<link href="../main.css" rel="stylesheet" type="text/css" />


<table border="1" bordercolor="#F8E9AA" cellspacing="0" cellpadding="2" width="60%" align=center>
<tr>
	<td width=15% align=center>Data/Ora</td>
</tr> 
<?
      include 'config.php';
      $sql = "SELECT * FROM Personaggio WHERE Esperienza = '0.0' AND UltimoEsp >= CURDATE() - interval 2 month";
      $result = mysql_query($sql) or die (mysql_error());
      if(mysql_num_rows($result) > 0)
      {
        while($fetch = mysql_fetch_array($result))
        {
          $Nome = stripslashes($fetch['Nome']);

    ?>
    <tr>
	<td valign=top><font color=white><?= $Nome ?></font></td>
    </tr>
    <?php 	}
    }
 ?>
</table>