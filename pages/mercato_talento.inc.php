<link rel="stylesheet" href="../themes/crystal/famiglie.css">


<div class="elenco_abilita">
<!-- Elenco talenti-->
<?php
//carico i soli talenti del pg
$abilita = gdrcd_query("SELECT id_abilita, grado FROM clgpersonaggioabilita WHERE nome='".$_SESSION['login']."'", 'result');

?>
    <div class="titolo_box">
        <?php echo "Mercato Talenti"; ?>
    </div>

<?php

//acquista
if (gdrcd_filter('get', $_POST['op']) == "acquista") {
$query_insert = "INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('". $_SESSION["login"]."', '". $_POST["id_abilita"] ."', '1')";
gdrcd_query($query_insert);

$degrade = gdrcd_query("UPDATE personaggio SET punto_razza = punto_razza - 1 WHERE nome = '". $_SESSION['login'] ."' LIMIT 1");

echo "<script type='text/javascript'>alert('Hai acquisito un nuovo talento');</script>";
}//fine acquista

//controllo punti razza
$check_pr = gdrcd_query("SELECT punto_razza FROM personaggio WHERE nome ='".$_SESSION['login']."'");
/*if ($check_pr['punto_razza'] == 0) {
     echo '<div class="error">Accesso negato.<br> 
                              Non hai abbastanza punti spirito. I punti spiriti si guadagnano potenziando il proprio spirito.<br>
                              <a href="main.php?page=level_up"><b>Potenzialo qui</b></a></div>';
    } else {*/
    
 $pg = $_SESSION['login'];
 
 $elenco_talenti = gdrcd_query("SELECT abilita.*, abilita.nome as nome_abilita, clgpersonaggioabilita.*
                                FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita 
                                WHERE abilita.tipo='Talento' AND clgpersonaggioabilita.nome ='$pg'", 'result');

$p_r = floor($check_pr['punto_razza']);
?>

<div class="div_colonne_abilita_scheda">
<center><b>Hai <?php echo $p_r; ?> punto spirito</b></center>

<table class="customTable">
<tr>
<td style="background-color: #181c31; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
TALENTI ACQUISITI
</td>
</tr>
<tr class="second_header">
<td>
Nome
</td>
</tr>
<?php
while($row = gdrcd_query($elenco_talenti, 'fetch')) {

                            $id = gdrcd_filter('out', $row['id_abilita']);
                            $addr = "skill_desc.proc.php".$id;
                            $to = "window.open('skill_desc.proc.php?id=$id','Log','toolbar=no,width=500,height=500');";
?>
<tr>
<td>
<?php
$nome = gdrcd_filter('out', $row['nome_abilita']);
$livello = 0 + gdrcd_filter('out', $row['grado']);
?>
<a href="javascript:void(0);" onClick="<?php echo $to ?>">
<?php
echo "<br>$nome";
?>
</a>
</td>
</tr>
<?php }//fine while ?>
</table>

<br>

<table class="customTable">
<tr>
<td colspan="2" style="background-color: #181c31; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
MERCATO TALENTI GENERICI
</td>
</tr>
<tr class="second_header">
<td>
Nome
</td>
<td>
</td>
</tr>
<?php
$elenco_talenti_generici = gdrcd_query("SELECT * FROM abilita WHERE id_razza = '-1' && tipo = 'Talento'", 'result');
while($ro = gdrcd_query($elenco_talenti_generici, 'fetch')) {

                            $id = gdrcd_filter('out', $ro['id_abilita']);
                            $addr = "skill_desc.proc.php".$id;
                            $to = "window.open('skill_desc.proc.php?id=$id','Log','toolbar=no,width=500,height=500');";
?>
<tr>
<td width="30%">
<?php
$nome = gdrcd_filter('out', $ro['nome']);
$livello = 0 + gdrcd_filter('out', $ro['grado']);
?>
<a href="javascript:void(0);" onClick="<?php echo $to ?>">
<?php
echo "<br>$nome";
?>
</a>
</td>
<td width="20%">
<form action="main.php?page=mercato_talento" method="post">
<input type ="hidden" value="<?php echo $ro['id_abilita']; ?>" name="id_abilita"/>
<?php
$check_id = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome = '". $_SESSION['login'] ."' && id_abilita = ". $ro['id_abilita'] ."", 'result');
$numresults = gdrcd_query($check_id, 'num_rows');
if($numresults < 1 && $check_pr['punto_razza'] > 0) { ?>
<input type="hidden" value="acquista" name="op"/>
<input type="submit" value="Acquista"/>
<?php
} else if($numresults > 0) {
?>
<input type="submit" style="width: 35%;" value="Già acquistata" disabled/>
<?php
}
?>
</form>
</td>
</tr>
<?php }//fine while ?>
</table>
</div>
<?php //}fine controllo pr ?>
</div>
    