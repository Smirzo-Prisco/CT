<link rel="stylesheet" href="../themes/crystal/volti.css">

<div class="page_body">  


<table class="customTable">
  <thead>
    <tr>
      <th colspan="8">TOP 100 NOTORIET&Agrave;</th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>
      NOME		
		</td>	
        <td>
			
			NOTORIET&Agrave;
		
		</td>
	</tr>
    
    <?php

    $elenco_exp = gdrcd_query("SELECT * FROM personaggio WHERE notorieta > 0 ORDER BY notorieta DESC LIMIT 0, 50", 'result');
    while ($row = gdrcd_query($elenco_exp, 'fetch')) {
?>

<!-- Inizio impaginazione oggetti-->
<tr>

<td width=25 align=center class='corpo'>
<a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a>
</td>

<td width=25 align=center class='corpo'>
<?php echo gdrcd_filter('out', $row['notorieta']); ?>
</td>

</tr>
<?php 
}
?>
</table><br><br>



