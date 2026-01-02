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

<?php
if (isset($_POST['submit']) && $_POST['submit']=="invia")
{
  $titolo = addslashes($_POST['titolo']);
  $testo = addslashes($_POST['testo']);
  $autore = addslashes($_POST['autore']);
  $luogo = addslashes($_POST['luogo']);
  $destinatario = addslashes($_POST['destinatario']);
  $str_data = strtotime($_POST['data']);
  include 'config.php';
  $sql = "INSERT INTO appuntamenti (titolo,testo,autore,luogo,destinatario,str_data ) VALUES ('$titolo', '$testo', '$autore', '$luogo', '$destinatario', '$str_data')";
  if($result = mysql_query($sql) or die (mysql_error()))
  {
    echo "Inserimento avvenuto con successo.<br>
    Torna al <a href=\"form2.php\">Registro</a>"; 
    if ($titolo == 'Giocata personale') {
    $sql2 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$autore', '$str_data', '0', '0', 'Hai una $titolo con $destinatario in $luogo')";
    $result2 = mysql_query($sql2);
    $sql3 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$destinatario', '$str_data', '0', '0', 'Hai una $titolo con $autore in $luogo')";
    $result3 = mysql_query($sql3);
    }
  }
}else{
  ?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">

<center>Tipo di giocata: <select name="titolo" class="ares">
    <option>Giocata personale</option>
    <option>Giocata di gilda</option>
    <option>Giocata di mestiere</option>
    <? if ($_SESSION['Master'] > 0) { ?>
    <option>Quest</option>
    <option>Evento</option>
    <? } ?>
    </select>
    <br><br>

<? if ($_SESSION['Master'] > 0) { ?> Titolo: 
<input type=text size=20 name="testo" value="Inserisci titolo" class=ares> (<i>Solo per quest</i>)<br><br><? }?>

Con: <input type=text size=20 name="destinatario" value="Inserisci nome" class=ares> <? if ($_SESSION['Master'] > 0) { ?> (<i>Se Quest o Evento, lasciare vuoto questo campo</i>) <? }?><br><br>

Tuo personaggio: <select name="autore" class="ares">
<option><?= $pg ?></option>
    <? if ($_SESSION['Master'] > 0) { ?>
    <option>Master</option>
    <? } ?>
</select> <? if ($_SESSION['Master'] > 0) { ?> (<i>Se Quest o Evento, impostare su <B>master</B></i>) <? }?><br><br>

<? if ($_SESSION['Master'] > 0) { ?>
Luogo:
<input name="luogo" type="text" value="Inserisci luogo" class="ares"> (<i>Solo per quest</i>)<br><br>
<? } ?>

Data:<br>
<input name="data" type="text" value="<?= date("d-m-Y"); ?>" class="ares"><br><br>


<input name="submit" type="submit" value="invia" class="ares">
</center></form>
  <?php
}
?>
<br><br>

<table border="1" bordercolor="#F8E9AA" cellspacing="0" cellpadding="2" width="60%" align=center>
<tr>
    <td colspan=4 align=center><font class=Titolo>Agenda di <?= htmlspecialchars($pg) ?></font></td>
</tr>
<tr>
	<td width=15% align=center>Data/Ora</td>
    <td width=50% align=center>Informazione</td>
</tr> 
<? if ($pg) {
      include 'config.php';
      $sql = "SELECT * FROM appuntamenti WHERE autore = '$pg'";
      $result = mysql_query($sql) or die (mysql_error());
      if(mysql_num_rows($result) > 0)
      {
        while($fetch = mysql_fetch_array($result))
        {
          $id = stripslashes($fetch['id']);
          $titolo = stripslashes($fetch['titolo']);
          $testo = stripslashes($fetch['testo']);
          $autore = stripslashes($fetch['autore']);
          $luogo = stripslashes($fetch['luogo']);
          $destinatario = stripslashes($fetch['destinatario']);
          $str_data = $fetch['str_data'];
          $data = date("d-m-Y", $fetch['str_data']);
    ?>
    <tr>
	<td valign=top><font color=white><?= $data ?></font></td>
    <td valign=top><font color=white><center><?= $titolo ?> con <?= $destinatario ?></center></font></td>
    <td align=center width="5%">[<a href="cancella.php?id=<?= $id ?>">X</a>]</td></tr>
    <? 	}
    }
}
 ?>
</table><br><br> 
 <?php if ($_SESSION['Master'] > 0) { ?>
 
 <table border="1" bordercolor="#F8E9AA" cellspacing="0" cellpadding="2" width="60%" align=center>
<tr>
    <td colspan=4 align=center><font class=Titolo>Registro di quest</font></td>
</tr>
<tr>
	<td width=15% align=center>Data/Ora</td>
    <td width=50% align=center>Informazione</td>
</tr> 

<?php
     
      include 'config.php';
      $sql = "SELECT * FROM appuntamenti WHERE autore = 'Master'";
      $result = mysql_query($sql) or die (mysql_error());
      if(mysql_num_rows($result) > 0)
      {
        while($fetch = mysql_fetch_array($result))
        {
          $id = stripslashes($fetch['id']);
          $titolo = stripslashes($fetch['titolo']);
          $testo = stripslashes($fetch['testo']);
          $autore = stripslashes($fetch['autore']);
          $luogo = stripslashes($fetch['luogo']);
          $destinatario = stripslashes($fetch['destinatario']);
          $str_data = $fetch['str_data'];
          $data = date("d-m-Y", $fetch['str_data']);
    ?>
    <tr>
	<td valign=top><font color=white><?= $data ?></font></td>
    <td valign=top><font color=white><center><?= $titolo ?> <b>"<?= $testo ?>"</b> presso <b><?= $luogo ?></b></center></font></td>
    <td align=center width="5%">[<a href="cancella.php?id=<?= $id ?>">X</a>]</td></tr>
    <? 	}
    }
}
 ?>
</table>