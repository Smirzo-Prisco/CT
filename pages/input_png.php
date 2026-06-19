<?php

session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

?>
<link rel="stylesheet" href="../themes/crystal/main.css" TYPE="text/css">
<link rel="stylesheet" href="../themes/crystal/banca.css" TYPE="text/css">

<div class="pagina_gestione_mercato">
<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$message = $_POST['message'];
$tag = $_POST['tag'];
$valore = $_POST['valore'];
$caratteri = $_POST['caratteri'];
$limite = $_POST['limite'];

if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1) {
echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {

if ($luogo < 1) {
echo '<div class="error">Devi essere in una location di gioco</div>';
} else {

//Mostro azione
if (gdrcd_filter('get',$_POST['op'])=='new_master_message')	
    {
    
    gdrcd_query("DELETE FROM scelte_utenti WHERE id_luogo = '".$luogo."'");
    gdrcd_query("UPDATE mappa SET timestamp_modifica_limite = NULL, limite_lunghezza_massima = NULL WHERE id = '".$luogo."'");
    
    if ($caratteri == NULL) {
    $caratteri ='2000';
    } elseif ($limite == NULL) {
    $limite = '500';
    }
    $time = gdrcd_query("SELECT * FROM chat_master WHERE luogo = ".$_SESSION['luogo']."", 'result');
  	if(gdrcd_query($time, 'num_rows') > 0) {
    gdrcd_query("UPDATE chat_master SET caratteri = '$caratteri', limite= '$limite', date_end = DATE_ADD(NOW(), INTERVAL $limite MINUTE), date_start = NOW() WHERE luogo = ".$_SESSION['luogo']."");        
    } else {
    gdrcd_query("INSERT INTO chat_master (luogo, pg, caratteri, limite, date_start, date_end) VALUES ('$luogo', '$login', '$caratteri', '$limite', NOW(), DATE_ADD(NOW(), INTERVAL $limite MINUTE))");
    }
    }

//Mostro azione
if (gdrcd_filter('get',$_POST['op'])=='new_chat_message')	
    {
    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('$luogo', '$login', '$tag', NOW(), 'N', '" . gdrcd_filter('in', $message) . "')");
    }
    
//Mostro parametro
if (gdrcd_filter('get',$_POST['op'])=='take_action')
	{
    if ($tag == '') {
    $tag = 'PNG';
    }
    
    $maxnum = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum == 0) {$maxnum = 20;};
	$num = mt_rand(1, $maxnum);
    $numtot = $num + $valore;
    
    //in caso di dado
    $maxnum_dado = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
	if ($maxnum_dado == 0) {$maxnum_dado = $valore;};
	$num_dado = mt_rand(1, $maxnum_dado);
    
    if (gdrcd_filter('get',$_POST['valori'])=='Usa destrezza') {
    
    $messaggio = "$tag esegue un tiro totale di destrezza di $numtot ($num/20 + $valore)";
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$tag', NOW(), 'C', '$messaggio')");

    } else if (gdrcd_filter('get',$_POST['valori'])=='Usa forza') {
    
    $messaggio = "$tag esegue un tiro totale di forza di $numtot ($num/20 + $valore)";
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$tag', NOW(), 'C', '$messaggio')");

    } else if (gdrcd_filter('get',$_POST['valori'])=='Usa mente') {
    
    $messaggio = "$tag esegue un tiro totale di mente di $numtot ($num/20 + $valore)";
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$tag', NOW(), 'C', '$messaggio')");

    } else if (gdrcd_filter('get',$_POST['valori'])=='Usa tempra') {
    
    $messaggio = "$tag esegue un tiro totale di tempra di $numtot ($num/20 + $valore)";
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$tag', NOW(), 'C', '$messaggio')");

    } else if (gdrcd_filter('get',$_POST['valori'])=='Usa dado') {
    
    $messaggio = "$tag lancia $num_dado/$valore";
    
    gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$tag', NOW(), 'C', '$messaggio')");

    }
    }
?>
<form action="input_png.php" method="post">
<table class="customTable" align="center">
<tr>
<td align="center">
GESTIONE QUEST
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>NUMERO CARATTERI CONSENTITI</b>
</td>
</tr>
<tr>
<td align="center">
<input name="caratteri" id="caratteri" placeholder="Inserire numero caratteri">
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>TEMPISTICA</b>
</td>
</tr>
<tr>
<td align="center">
<input name="limite" id="limite" placeholder="Inserire i minuti">
</td>
</tr>
<tr>
<td align="center">
<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
<input type="hidden" name="op" value="new_master_message" /></td>
</tr>
</table>
</form>
<br><br>



<form action="input_png.php" method="post">
<table class="customTable" align="center">
<tr>
<td align="center">
MUOVI PNG
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>Nome png</b>
</td>
</tr>
<tr>
<td align="center">
<input name="tag" id="tag" placeholder="Nome png">
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>Azione</b>
</td>
</tr>
<tr>
<td align="center">
<textarea name="message" id="message" onKeyup="conta(this);" maxlength="2000" placeholder="Azione png"></textarea>
<br>
<center><b><span id="rimanenti">2000</span></b></center>
</td>
</tr>
<tr>
<td align="center">
<input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
<input type="hidden" name="op" value="new_chat_message" /></td>
</tr>
</table>
</form>
<br><br>

<form action="input_png.php" method="post">
<table class="customTable" align="center">
<tr>
<td align="center">
USA STATISTICA O DADO PNG
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>Nome png (Se lasciato vuoto, il nome sar&agrave; PNG)</b>
</td>
</tr>
<tr>
<td align="center">
<input name="tag" id="tag" placeholder="Nome png">
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>Valore</b>
</td>
</tr>
<tr>
<td align="center">
<input name="valore" id="valore" placeholder="Valore parametro o numero dado" style="width:180px;">
</td>
</tr>
<tr class="second_header">
<td align="center">
<b>Usa parametro</b>
</td>
</tr>
<tr>
<td align="center">
<SELECT name=valori>
                            <option value="Usa destrezza">Usa destrezza</option>
                            <option value="Usa forza">Usa forza</option>
                            <option value="Usa mente">Usa mente</option>
                            <option value="Usa tempra">Usa tempra</option>
                            <option value="Usa dado">Usa dado</option>
</SELECT>
</td>
</tr>
<tr>
<td align="center">
<input type="submit" value="Lancia" />
<input type="hidden" name="op" value="take_action">
</td>
</tr>
</table>
</form>
<?php }//fine luogo
}//fine permessi pagina ?>
</div>

<!-- Conta caratteri chat -->

<script type="text/javascript"> 
    // dichiaro il nome della funzione
    // prende come parametro l\'input da analizzare
    function conta(el) {
        // imposto il limite massimo di caratteri consentiti
        var max_char = 2000;
        // conto il numero di caratteri nell\'input
        var conta_caratteri = el.value.length;
        // verifico se i caratteri hanno superato il limite
        if(conta_caratteri >= max_char) {
            // riporto i caratteri al limite massimo
            conta_caratteri = 2000;
            // cancello dall\'input i caratteri in eccesso
            el.value = el.value.substring(0, max_char);
        }
        // aggiorno il contatore testuale
        document.getElementById("rimanenti").innerHTML = max_char - conta_caratteri;
        // calcolo la lungezza per il contatore grafico
        var width =  (max_char-conta_caratteri)*5;
        // aggiorno il contatore grafico
        document.getElementById("countdown").style.width = width + "px";
    }
</script>