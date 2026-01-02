<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<table class="customTable">
<tr class="second_header">
<td>
        COORDINATORI
</td>
</tr>
<tr>
<td>
                                        
                                        <?php

    $elenco_admin = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.admin = 1", 'result');
    while ($row = gdrcd_query($elenco_admin, 'fetch')) {
                                        ?>
                                        
                                        
                                   
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a>

<br>

<?php
}
 ?>
</td>
</tr>



		<!-- MASTER -->
<tr class="second_header">
<td>        MASTER
    </td>
    <tr>
                        <td>
                                        
                                        <?php

    $elenco_admin = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.master = 1", 'result');
    while ($row = gdrcd_query($elenco_admin, 'fetch')) {
                                        ?>
                                        
                                        
                                       
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a>

<br>

<?php
}
 ?>
</td>
</tr>

		<!-- MODERATORI -->
<tr class="second_header">
<td>        MODERATORI
</td>
</tr>
<tr>
                        <td>
                                        
                                        <?php

    $elenco_admin = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.moderatore = 1", 'result');
    while ($row = gdrcd_query($elenco_admin, 'fetch')) {
                                        ?>
                                        
                                        
                                       
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a><br>



<?php
}
 ?>
</td>
</tr>



		<!-- GUIDA -->
<tr class="second_header">
<td>        GUIDE
    </td>
</tr>
<tr>
                        <td>
                                        
                                        <?php

    $elenco_admin = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.guida = 1", 'result');
    while ($row = gdrcd_query($elenco_admin, 'fetch')) {
                                        ?>
                                        
                                        
                                       
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
<?php echo gdrcd_filter('out', $row['nome']); ?>
</a>
<br>


<?php
}
 ?>
</td>
</tr>
</table>
<br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>
