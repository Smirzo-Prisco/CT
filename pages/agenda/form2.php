<?php

$Login = $_SESSION['login'];
$pg = $_SESSION['login'];
?>
<head>
<link href="../main.css" rel="stylesheet" type="text/css" />

<?php
if (isset($_POST['submit']) && $_POST['submit']=="Invia")
{

  $titolo = gdrcd_filter('out', $_REQUEST['titolo']);
  $testo = gdrcd_filter('out', $_REQUEST['testo']);
  $orario = gdrcd_filter('out', $_REQUEST['orario']);  
  $autore = gdrcd_filter('out', $_REQUEST['autore']);
  $luogo = gdrcd_filter('out', $_REQUEST['luogo']);
  $destinatario = gdrcd_filter('out', $_REQUEST['destinatario']);
  $str_data = strtotime(gdrcd_filter('out', $_REQUEST['data']));

  $sql = "INSERT INTO appuntamenti (titolo,testo,autore,luogo,destinatario,str_data,orario) VALUES ('$titolo', '$testo', '$autore', '$luogo', '$destinatario', '$str_data', '$orario')";
  if($result = gdrcd_query($sql, "result"))
  {
    echo "Inserimento avvenuto con successo.<br>
    Torna al <a href=\"/main.php?page=agenda_center&op=add_role\">Registro</a>";
    
    if ($titolo == 'Giocata personale') {
    $sql2 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$autore', '$str_data', '0', '0', 'Hai una $titolo con $destinatario in $luogo alle $orario')";
    $result2 = gdrcd_query($sql2, "result");
    $sql3 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$destinatario', '$str_data', '0', '0', 'Hai una $titolo con $autore in $luogo alle $orario')";
    $result3 = gdrcd_query($sql3, "result");
    } 
  }
}else{

  ?>
<form action="/main.php?page=agenda_center&op=add_role" method="post">

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

Con <? if ($_SESSION['Master'] > 0) { ?>(<b>Se <u>QUEST</u> o <u>EVENTO</u>: lasciare vuoto questo campo)</b><? }?>: 
<select name="destinatario" class="ares">
<?
$result = gdrcd_query("SELECT * FROM personaggio", "result");
while ($rs = gdrcd_query($result, 'fetch')) {
	echo '<option value='.gdrcd_filter('out', $rs['nome']).'>'.gdrcd_filter('out', $rs['nome']).'</option>';
	#echo '<option value='.$rs['nome'].'>'.$rs['nome'].'</option>';
}
$rs->close;

?>
</select>
<br><br>
Tuo personaggio: <select name="autore" class="ares">
<option><?= $pg ?></option>
    <? if ($_SESSION['Master'] > 0) { ?>
    <option>Master</option>
    <? } ?>
</select> <? if ($_SESSION['Master'] > 0) { ?> (<i>Se Quest o Evento, impostare su <B>master</B></i>) <? }?><br><br>


Luogo:
<select name="luogo" class="ares">
<?
$result = gdrcd_query("SELECT * FROM mappa", "result");

while ($rs = gdrcd_query($result, 'fetch')) {
	echo '<option>'.$rs['nome'].'</option>';
}
$rs->close;
?>
</select><br><br>

Data:<br>
<input name="data" type="text" value="<?= date("d-m-Y"); ?>" class="ares"><br><br>
Ora:<br>
<input type=text size=20 name="orario" value="00:00" class=ares><br><br>

<input name="submit" type="submit" value="Invia" class="ares">
</center></form>
  <?php
}
?>
<br><br>

<table border="1" bordercolor="#F8E9AA" cellspacing="0" cellpadding="2" width="60%" align=center>
<tr>
    <td colspan=4 align=center><font class=Titolo>Agenda di <?= $pg ?></font></td>
</tr>
<tr>
	<td width=15% align=center>Data/Ora</td>
    <td width=50% align=center>Informazione</td>
</tr> 
<? if ($pg) {
      $sql = "SELECT * FROM appuntamenti WHERE autore = '$pg' ORDER BY str_data ASC, orario ASC";
      $result = gdrcd_query($sql, "result");
      if(gdrcd_query($result, "num_rows") > 0)
      {
        while($fetch = gdrcd_query($result, 'fetch'))
        {
          $id = $fetch['id'];
          $titolo = $fetch['titolo'];
          $testo = $fetch['testo'];
          $orario = $fetch['orario'];
          $autore = $fetch['autore'];
          $luogo = $fetch['luogo'];
          $destinatario = $fetch['destinatario'];
          $str_data = $fetch['str_data'];
          $data = date("d-m-Y", $fetch['str_data']);
    ?>
    <tr>
	<td valign=top><font color=white><?= $data ?> alle <?= $orario ?></font></td>
    <td valign=top><font color=white><center><?= $titolo ?> con <?= $destinatario ?></center></font></td>
    <td align=center width="5%">[<a href="main.php?page=agenda_center&op=remove_role&id=<?= $id ?>">X</a>]</td></tr>
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
    
      $sql = "SELECT * FROM appuntamenti WHERE autore = 'Master' ORDER BY str_data ASC, orario ASC";
      $result = mysql_query($sql) or die (mysql_error());
      if(mysql_num_rows($result) > 0)
      {
        while($fetch = mysql_fetch_array($result))
        {
          $id = stripslashes($fetch['id']);
          $titolo = stripslashes($fetch['titolo']);
          $testo = stripslashes($fetch['testo']);
          $orario = stripslashes($fetch['orario']);
          $autore = stripslashes($fetch['autore']);
          $luogo = stripslashes($fetch['luogo']);
          $destinatario = stripslashes($fetch['destinatario']);
          $str_data = $fetch['str_data'];
          $data = date("d-m-Y", $fetch['str_data']);
    ?>
    <tr>
	<td valign=top><font color=white><?= $data ?> alle <?= $orario ?></font></td>
    <td valign=top><font color=white><center><?= $titolo ?> <b>"<?= $testo ?>"</b> presso <b><?= $luogo ?></b></center></font></td>
    <td align=center width="5%">[<a href="main.php?page=agenda_center&op=remove_role&id=<?= $id ?>">X</a>]</td></tr>
    <? 	}
    }
}
 ?>
</table>