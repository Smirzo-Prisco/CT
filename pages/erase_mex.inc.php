<div class="pagina_gestione_manutenzione">
<?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    //inizio le operazioni di cancellazione
    
    if (isset($_POST['delete'])){
    $checkbox = $_POST['checkbox'];
    $count = is_array($checkbox) ? count($checkbox) : false;
    if($count !== false) {
    
        for($i=0;$i<$count;$i++){

            if(!empty($checkbox[$i])){
            $nome= $checkbox[$i];
            
   gdrcd_query("DELETE FROM msg WHERE idgroup = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM msggrp WHERE idgroup = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM msggrpuser WHERE idgroup = '$nome'"); /* CANCELLO SMS PG*/
   
     }

        }

    }


}
?>

<?php
    //richiamo tutti pg
    
     $tutti_pg = gdrcd_query("SELECT * FROM msggrp WHERE tpgroup = 'OFF' AND (ctgroup != 'GLOBAL' AND ctgroup != 'ARCHIVIO') ORDER BY idgroup", 'result');
?>
<form action="main.php?page=erase_mex" method="post" name="cancellaselezione">
<table class="customTable">
<tr>
<td colspan="2">Mex off</td>
</tr>
<tr class="second_header">
<td>ID</td>
<td></td>
</tr>
<?php while ($row = mysqli_fetch_array($tutti_pg)){ ?>
<tr>
<td>
<?php echo $row['idgroup']; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['idgroup'] ?>">
</td>
</tr>
<?php } ?>
<tr>
<td colspan="2">
<center><input name="delete" type="submit" id="delete" value="delete">
&nbsp;&nbsp;
<input type="button" value="Seleziona tutto" onClick="SelezTT()" style="background-color: #070a1b; border: 1px solid rgba(58, 72, 86, 0.49); color: #8a9ca0; font-size: 10px; font-family: Tahoma, Geneva, sans-serif; padding: 4px; text-transform: uppercase;">
</center></td>
</tr>
</table>
</form>



<script type="text/javascript">
function SelezTT()
{
    var i = 0;
    var cancellaselezione = document.cancellaselezione.elements;
    for (i=0; i<cancellaselezione.length; i++)
    {
        if(cancellaselezione[i].type == "checkbox")
        {
            cancellaselezione[i].checked = !(cancellaselezione[i].checked);
        }
    }
}

</script>

<?php
}//fine permessi
?>
</div>