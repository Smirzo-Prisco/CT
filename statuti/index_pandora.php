<?php
session_start();
include('../header.inc.php');
?>
<head>
<script src="http://code.jquery.com/jquery-latest.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="general_script.js"></script>
<title>.:: Secret Pandora ::.</title>
<link href="general_css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php
$query = "SELECT * FROM Statuti WHERE ID = 14";
$personaggi = gdrcd_query($query, 'result');
$risultato = gdrcd_query($personaggi, 'fetch');
gdrcd_query($risultato, 'free');
?>
<div id="contenitore">
<img src="header/header_pandora.png">
<br><br>
<table class="customTable">
<tr class="second_header">
<td>
<a href="#dialog" name="modal"><center>STATUTO</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog2" name="modal2"><center>DESCRIZIONE</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog3" name="modal3"><center>MAESTRO - ARMI E TECNICHE</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog4" name="modal4"><center>CHIMICO - DROGHE E M7</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog5" name="modal5"><center>DISTILLATORE - SIERI E POLVERI</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog6" name="modal6"><center>CRAFTER - MASCHERE E SANGUE</center></a>
</td>
</tr>

</table><br><br>
</div>

<div id="box"> 
		<div id="dialog" class="window"> 
			<p class="titoletti">Statuto</p><br /><br />
            <?php echo gdrcd_filter('out', $risultato['Testo']); ?>
            </div>
            <div id="mask">
		</div> 
	</div>
    <div id="box"> 
		<div id="dialog2" class="window"> 
			<p class="titoletti">Descrizioni</p><br /><br />
            <?php echo gdrcd_filter('out', $risultato['Descrizione']); ?>
            <div id="mask">
		</div> 
	</div>
     <div id="box"> 
		<div id="dialog3" class="window"> 
			<p class="titoletti">Maestro - Armi e Tecniche</p><br /><br /> 
            <?php echo gdrcd_filter('out', $risultato['Incantesimi']); ?>
            <div id="mask">
		</div> 
	</div>  
    <div id="box"> 
		<div id="dialog4" class="window"> 
			<p class="titoletti">Chimico - Droghe e M7</p><br /><br /> 
            <?php echo gdrcd_filter('out', $risultato['Alchimisti']); ?>
            <div id="mask">
		</div> 
	</div>  
    <div id="box"> 
		<div id="dialog5" class="window"> 
			<p class="titoletti">Distillatore - Sieri e Polveri</p><br /><br /> 
            <?php echo gdrcd_filter('out', $risultato['Occultisti']); ?>
            <div id="mask">
		</div> 
	</div>  
    <div id="box"> 
		<div id="dialog6" class="window"> 
			<p class="titoletti">Crafter - Maschere e Sangue</p><br /><br /> 
            <?php echo gdrcd_filter('out', $risultato['Divinatori']); ?>
            <div id="mask">
		</div> 
	</div>  
</body>