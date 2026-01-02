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
<title>.:: Guardiani di Cosmos ::.</title>
<link href="general_css.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php
$query = "SELECT * FROM Statuti WHERE ID = 4";
$personaggi = gdrcd_query($query, 'result');
$risultato = gdrcd_query($personaggi, 'fetch');
gdrcd_query($risultato, 'free');
include('formattazione_testo.php');

?>
<div id="contenitore">
<img src="header/header_guardiani.png">
<br><br>
<table class="customTable">
<tr class="second_header">
<td>
<a href="#dialog" name="modal"><center>STORIA</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog2" name="modal2"><center>STATUTO</center></a>
</td>
</tr>

<tr class="blank_row">
    <td bgcolor="#111423" colspan="3"></td>
</tr>

<tr class="second_header">
<td>
<a href="#dialog3" name="modal3"><center>SKILL</center></a>
</td>
</tr>
</table><br><br>
</div>

<div id="box"> 
		<div id="dialog" class="window"> 
			<p class="titoletti">Storia</p><br /><br />
            <?php echo $storia; ?>
            </div>
            <div id="mask">
		</div> 
	</div>
    <div id="box"> 
		<div id="dialog2" class="window"> 
			<p class="titoletti">Statuto</p><br /><br />
            <?php echo $testo; ?>
            <div id="mask">
		</div> 
	</div>
     <div id="box"> 
		<div id="dialog3" class="window"> 
			<p class="titoletti">Skill</p><br /><br /> 
            <?php echo gdrcd_filter('out', $risultato['Skill']); ?>
            <div id="mask">
		</div> 
	</div>  
</body>