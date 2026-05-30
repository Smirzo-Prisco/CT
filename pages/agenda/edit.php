<?php
session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');
$id = $_GET['id'];
$Login = $_SESSION['login'];
$pg = $_SESSION['login'];
?>
<head>
<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<?php

if (isset($_POST['submit']) && $_POST['submit'] == "Modifica") {

  $id = gdrcd_filter('num', $_POST['id']);
  $titolo = gdrcd_filter('in', $_POST['titolo']);
  $testo = gdrcd_filter('in', $_POST['testo']);
  $str_data = strtotime(gdrcd_filter('out', $_POST['data']));
  $orario = gdrcd_filter('out', date('H:i', $str_data));
  $autore = gdrcd_filter('out', $_POST['autore']);
  $luogo = gdrcd_filter('out', $_POST['luogo']);
  $destinatario = gdrcd_filter('out', $_POST['destinatario']);
  $data_object = new DateTime();
  $data_object->setTimestamp($str_data);
  $data_object->setTime(0, 0, 0);
  $str_data = $data_object->getTimestamp();

  $sql = "UPDATE appuntamenti SET titolo = '$titolo', testo = '$testo', autore = '$autore', luogo = '$luogo', destinatario = '$destinatario', str_data = '$str_data', orario = '$orario' WHERE id = '$id'";
  $sql_erase = "DELETE FROM BakCalendario WHERE id_app = '$id'";
  gdrcd_query($sql_erase);

  if($result = gdrcd_query($sql, "result")) {
    echo "Modifica avvenuta con successo.<br>
    Torna al <a href=\"/main.php?page=agenda_center&op=add_role\">Registro</a>";
    
    if ($titolo == 'Giocata personale' || $titolo == 'Giocata di gilda' || $titolo == 'Giocata di mestiere') {
    
    //se destinatari multipli
    $Dest = explode(',', $_POST['destinatario']);
    for ($i = 0; $i <= count($Dest)-1; $i++) {
    $destinat = trim($Dest[$i]);
        
    $sql3 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$destinat', '$str_data', '0', '0', 'Hai una $titolo con $autore in $luogo alle $orario')";
    $result3 = gdrcd_query($sql3, "result");
    }//fine for
    $sql2 = "INSERT INTO BakCalendario (id_app,Mittente,Destinatario,Spedito,Letto,Inviato,Testo) VALUES ((SELECT MAX(id) FROM appuntamenti), 'Calendario', '$autore', '$str_data', '0', '0', 'Hai una $titolo con $destinatario in $luogo alle $orario')";
    $result2 = gdrcd_query($sql2, "result");
    } 
  }
}else{

$evento = "SELECT * FROM appuntamenti WHERE id = '$id'";
$skill = gdrcd_query($evento); 
?>
<form action="/main.php?page=agenda_center&op=edit_role" method="post">
<table class="customTable">
<tr>
<td style="font-size: 12px;color: #a7a7a8;font-family: DejaVu Serif;filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
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
<select name="titolo" class="ares" style="background-color: #0f111d;">
    <option  <?php if($skill['titolo'] == 'Giocata personale') {echo 'selected';} ?>>Giocata personale</option>
    <option <?php if($skill['titolo'] == 'Giocata di gilda') {echo 'selected';} ?>>Giocata di gilda</option>
    <option <?php if($skill['titolo'] == 'Giocata di mestiere') {echo 'selected';} ?>>Giocata di mestiere</option>
    <?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
    <option <?php if($skill['titolo'] == 'Quest') {echo 'selected';} ?>>Quest</option>
    <option <?php if($skill['titolo'] == 'Evento') {echo 'selected';} ?>>Evento</option>
    <?php } ?>
    </select>
</td>
</tr>



<tr class="second_header">
<td>
<b>Tuo personaggio</b>
<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<b> o Master</b>
<?php } ?>
</td>
</tr>
<tr>
<td>
<select name="autore" class="ares" style="background-color: #0f111d;">
<option><?= $pg ?></option>
    <?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
    <option>Master</option>
    <?php } ?>
</select>
</td>
</tr>  



<tr class="second_header">
<td>
<b>Giocare con:</b>
<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<br><small><u>Se <u>QUEST</u> o <u>EVENTO</u>: lasciare vuoto questo campo</u></small>
<?php } ?>
</td>
</tr>
<tr>
<td>
<input type=text size=40 name="destinatario" placeholder="Usare virgola se presenti più personaggi" class=ares style="background-color: #0f111d;" value="<?php echo $skill['destinatario']; ?>"> 
</td>
</tr> 




<tr class="second_header">
<td>
<b>Luogo:</b>
</td>
</tr>
<tr>
<td>
<select name="luogo" class="ares" style="background-color: #0f111d;">
<?
$result = gdrcd_query("SELECT * FROM mappa ORDER BY nome ASC", "result");

while ($rs = gdrcd_query($result, 'fetch')) {
?>    
<option <?php if($skill['luogo'] == $rs['nome']) {echo 'selected';} ?>>
<?php echo $rs['nome'] ?>
</option>
<?php    
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
<input name="data" type="datetime-local" value="<?=date("Y-m-d");?>T<?=date("H:i");?>" min="<?=date("Y-m-d");?>T<?=date("H:i");?>" class="ares" style="background-color: #0f111d;">
<!--
<input name="data" type="text" value="<?php if($skill['str_data'] != '--') { echo date('d-m-Y', $skill['str_data']); } else { echo date("d-m-Y"); } ?>" class="ares" style="background-color: #0f111d;">
<input type=text size=20 name="orario" value="<?php echo $skill['orario']; ?>" class=ares style="background-color: #0f111d;">
-->
</td>
</tr> 
<?php if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
<tr class="second_header">
<td>
<b>Titolo quest:</b>
</td>
</tr>
<tr>
<td>
<input type=text size=20 name="testo" value="<?php echo $skill['testo']; ?>" class=ares style="background-color: #0f111d;"> 
</td>
</tr> 
<?php } ?>
<tr>
<td>
<input name="submit" type="submit" value="Modifica" class="ares" style="background-color: #0f111d;">
<input type=hidden size=20 name="id" value="<?php echo $skill['id']; ?>" class=ares style="background-color: #0f111d;"> 
</td>
</tr>
<?php
}
?>
</table>
</form>
<br><br>