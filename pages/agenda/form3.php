<?php
session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

$Login = $_SESSION['login'];
$pg = $_SESSION['login'];
?>
<head>
<link rel="stylesheet" href="../themes/crystal/mestieri.css">
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
<table class="customTable">
<tr>
<td>
PRENOTAZIONI ROLE 
<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
O QUEST <?php } ?>
</td>
</tr>
<tr class="second_header">
<td>
<b>Tipo di giocata</b>
</td>
</tr>
<tr>
<td>
<select name="titolo" class="ares">
    <option>Giocata personale</option>
    <option>Giocata di gilda</option>
    <option>Giocata di mestiere</option>
    <? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
    <option>Quest</option>
    <option>Evento</option>
    <? } ?>
    </select>
</td>
</tr>



<tr class="second_header">
<td>
<b>Tuo personaggio</b>
<? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<b> o Master</b>
<? } ?>
</td>
</tr>
<tr>
<td>
<select name="autore" class="ares">
<option><?= $pg ?></option>
    <? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
    <option>Master</option>
    <? } ?>
</select>
</td>
</tr>  



<tr class="second_header">
<td>
<b>Giocare con:</b>
<? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<br><small><u>Se <u>QUEST</u> o <u>EVENTO</u>: lasciare vuoto questo campo</u></small>
<? } ?>
</td>
</tr>
<tr>
<td>
<select name="destinatario" class="ares">
<? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<option></option>
<?
}
$result = gdrcd_query("SELECT * FROM personaggio WHERE nome != '$pg' && (esilio < NOW() || esilio IS NULL) ORDER BY nome ASC", "result");
while ($rs = gdrcd_query($result, 'fetch')) {
	echo '<option value='.gdrcd_filter('out', $rs['nome']).'>'.gdrcd_filter('out', $rs['nome']).'</option>';
	#echo '<option value='.$rs['nome'].'>'.$rs['nome'].'</option>';
}
$rs->close;

?>
</select>
</td>
</tr> 




<tr class="second_header">
<td>
<b>Luogo:</b>
</td>
</tr>
<tr>
<td>
<select name="luogo" class="ares">
<?
$result = gdrcd_query("SELECT * FROM mappa ORDER BY nome ASC", "result");

while ($rs = gdrcd_query($result, 'fetch')) {
	echo '<option>'.$rs['nome'].'</option>';
}
$rs->close;
?>
</select>
</td>
</tr> 

<tr class="second_header">
<td>
<b>Data e ora:</b>
</td>
</tr>
<tr>
<td>
<input name="data" type="text" value="<?= date("d-m-Y"); ?>" class="ares">
<input type=text size=20 name="orario" value="00:00" class=ares>
</td>
</tr> 
<? if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<tr class="second_header">
<td>
<b>Titolo quest:</b>
</td>
</tr>
<tr>
<td>
<input type=text size=20 name="testo" value="Inserisci titolo" class=ares> 
</td>
</tr> 
<?php } ?>
<tr>
<td>
<input name="submit" type="submit" value="Invia" class="ares">
</td>
</tr>
<?php
}
?>
</table>
</form>
<br><br>

<table class="customTable">
<tr>
<td colspan="3">
AGENDA DI <font style="text-transform: uppercase;"><?php echo "$pg"; ?></font>
</td>
</tr>
<tr class="second_header">
<td width=20%>
<b>Data e ora</b>
</td>
<td width=50%>
<b>Informazioni</b>
</td>
<td width=5%>
</td>
</tr>
<?php
      if ($pg) {
      $sql = "SELECT * FROM appuntamenti WHERE (autore = '$pg' || destinatario = '$pg') ORDER BY str_data ASC, orario ASC";
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
	<td><?= $data ?> alle <?= $orario ?></td>
    <?php if ($autore == $pg) { ?>
    <td><?= $titolo ?> con <?= $destinatario ?></td>
    <?php } else { ?>
    <td><?= $titolo ?> con <?= $autore ?></td>
    <?php } ?>    
    <td>[<a href="main.php?page=agenda_center&op=remove_role&id=<?= $id ?>">X</a>]
    </td>
    </tr>
    <? 	}
    }
}
 ?>
</table><br><br> 




<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<table class="customTable">
<tr>
<td colspan="3">
REGISTRO QUEST
</td>
</tr>
<tr class="second_header">
<td width=20%>
<b>Data e ora</b>
</td>
<td width=50%>
<b>Informazioni</b>
</td>
<td width=5%>
</td>
</tr>
<?php
      $sql = "SELECT * FROM appuntamenti WHERE autore = 'Master' ORDER BY str_data ASC, orario ASC";
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
	<td><?= $data ?> alle <?= $orario ?></td>
    <td><?= $titolo ?> <b>"<?= $testo ?>"</b> presso <b><?= $luogo ?></b></td>
    <td>[<a href="main.php?page=agenda_center&op=remove_role&id=<?= $id ?>">X</a>]</td></tr>
    <? 	}
    }
}
 ?>
</table>