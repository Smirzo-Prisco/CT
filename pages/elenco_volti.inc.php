<link rel="stylesheet" href="../themes/crystal/volti.css">

<div class="page_body">  


<table class="customTable">
    <tr class="second_header">
                                    <td>
                                        <div>
                                        PATROCINIO VOLTI
                                        </div>
                                    </td>
    </tr>
    </table>
    <br><br>
    
<table class="customTable">
  <thead>
    <tr>
      <th colspan="8">Elenco Volti</th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>
      NOME		
		</td>	
        <td>
			
			VOLTO
		
		</td>
	</tr>
    
    <?php

    $elenco_donne = gdrcd_query("SELECT * FROM personaggio WHERE volto != '' && volto != 'niente' ORDER BY volto", 'result');
    while ($row = gdrcd_query($elenco_donne, 'fetch')) {
?>

<!-- Inizio impaginazione oggetti-->
<tr>

<td width=25 align=center class='corpo'>
<a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a>
</td>

<td width=25 align=center class='corpo' style="font-size: 12px;">
<?php echo gdrcd_filter('out', $row['volto']); ?>
</td>

<?
if($_SESSION['admin'] == 1) {

?>

<td align=center width="7%">
<form action="main.php?page=elenco_volti" method="Post">
    <input type="hidden" name="nome" value="<?php echo gdrcd_filter('out', $row['nome']); ?>" />
    <input type="submit" name="Cancella" value="Cancella">  
    </form>
    </td>

<? } ?>

</tr>
<? 
}
?>
</table><br><br>

<?php
/*Avvio alla cancellazione del volto*/
 if($_POST['Cancella'] == 'Cancella') {
        $cancello_volto = gdrcd_query("UPDATE personaggio SET volto = NULL WHERE nome = '".$_POST['nome']."'");
}
?>
<br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>