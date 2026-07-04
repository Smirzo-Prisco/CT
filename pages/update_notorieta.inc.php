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
    $noto = $_POST['old_noto'];
    $count = is_array($checkbox) ? count($checkbox) : false;
    if($count !== false) {
    
        for($i=0;$i<$count;$i++){

            if(!empty($checkbox[$i])){
            $nome= $checkbox[$i];
            
   if ($noto > 0 AND $noto < 5) {
   $valore = 1;
   } else if ($noto > 4 AND $noto < 11) {
   $valore = 5;
   } else if ($noto > 10 AND $noto < 21) {
   $valore = 10;
   } else if ($noto > 20 AND $noto < 31) {
   $valore = 15;
   } else if ($noto > 30 AND $noto < 41) {
   $valore = 20;
   } else if ($noto > 40 AND $noto < 51) {
   $valore = 25;
   } else if ($noto > 50 AND $noto < 61) {
   $valore = 30;
   } else if ($noto > 60 AND $noto < 71) {
   $valore = 35;
   } else if ($noto > 70 AND $noto < 81) {
   $valore = 40;
   } else if ($noto > 80 AND $noto < 91) {
   $valore = 45;
   } else if ($noto > 90) {
   $valore = 50;
   }
   
   gdrcd_query("UPDATE personaggio SET notorieta = $valore WHERE nome = '$nome'");
   
        }
        }

    }


}
?>

<?php
    //richiamo tutti pg
    
     $tutti_pg = gdrcd_query("SELECT * FROM personaggio WHERE notorieta > 0 ORDER BY nome", 'result');
?>
<form action="main.php?page=update_notorieta" method="post" name="cancellaselezione">
<table class="customTable">
<tr>
<td colspan="4">Cambia la notorietà</td>
</tr>
<tr class="second_header">
<td>Nome</td>
<td>Notorietà</td>
<td></td>
</tr>
<?php while ($row = mysqli_fetch_array($tutti_pg)){ ?>
<tr>
<td>
<a href="main.php?page=scheda&pg=<?php echo $row['nome']; ?>"><?php echo $row['nome']; ?></a>
</td>
<td>
<?php echo $row['notorieta']; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['nome'] ?>">
<input type="hidden" name="old_noto" value="<?= $row['notorieta'] ?>">

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