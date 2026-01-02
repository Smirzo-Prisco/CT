<link rel="stylesheet" href="themes/crystal/mestieri.css">
<style type="text/css">
<!--
div.scroll {
height: 200px;
width: 270px;
overflow: auto;
padding: 0px 10px 0px 5px;
text-align: justify;
font-size: 12px;
}
-->
</style>

<?php
/*elenco ruoli*/
            $query = gdrcd_query("SELECT * FROM PNG WHERE IDPng = ".gdrcd_filter('num', $_REQUEST['IDPng'])."", 'result');
            $row = gdrcd_query($query, 'fetch'); 
?>
            
<div class="pagina_servizi_gilde">
<!-- Titolo della pagina -->
    <div class="page_title">
        <h2>Reliquia</h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
    <div class="titolo">
            	<img src="/imgs/guilds/png/reliquie.png" />
            </div>
            
    <table class="customTable">
    <tr>
    <th colspan="2" scope="col"></th>
    </tr>
    <tr class="second_header">
    <td colspan="2">
    <?php echo gdrcd_filter('out', $row['Nome']); ?>
    </td>
    </tr>
    <tr>
    <td>
    <img src="/imgs/guilds/png/<?php echo gdrcd_filter('out', $row['URLImg']); ?>" />
    </td>
    <td>
    <div class="scroll" style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57)); text-transform: none;">
    <?php echo gdrcd_filter('out', $row['Descrizione']); ?>
    </div>
    </td>
    </tr>
    </table>
    <br><br>
    
    
    
    <br><br>

<table class="customTable">
<tr>
<td colspan="4">
<span style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><b>Punti accumulati: <?php echo gdrcd_filter('out', $row['Esperienza']); ?></b></span>
</td>
</tr>
<tr class="second_header">
<td width=20%>
<b>Data/Ora</b>
</td>
<td width=40%>
<b>Commento</b>
</td>
<td width=30%>
<b>Master</b>
</td>
<td width=10%>
<b>Exp.</b>
</td>
</tr>
<?php

/*elenco punti PNG*/
            $px_png = gdrcd_query("SELECT * FROM PuntiPNG
                                   LEFT JOIN PNG ON PNG.IDPng = PuntiPNG.IDPng
                                   WHERE PuntiPNG.IDPng = ".gdrcd_filter('num', $_REQUEST['IDPng'])."", 'result');
            while ($riga = gdrcd_query($px_png, 'fetch')) {
            
            ?>

            <tr>
		<td><?php echo gdrcd_filter('out', $riga['DataEvento']); ?></td>
		<td><?php echo gdrcd_filter('out', $riga['Commento']); ?></td>
    	<td><?php echo gdrcd_filter('out', $riga['Master']); ?></td>
		<td><?php echo gdrcd_filter('out', $riga['Esperienza2']); ?></td>
		</tr>
        <?php
	}
	
    
?>
</table>
    </div>
</div>