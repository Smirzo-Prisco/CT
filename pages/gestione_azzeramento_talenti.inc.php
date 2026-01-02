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
            
   gdrcd_query("UPDATE clgpersonaggioabilita SET grado= 1 WHERE nome = '$nome' && id_abilita < 31"); /* Metto a 1 i talenti standard*/
   gdrcd_query("DELETE from clgpersonaggioabilita WHERE (id_abilita >= '31' && id_abilita <= '36' || id_abilita > 41 && id_abilita <= '42') && nome = '$nome'"); /* CANCELLO talenti comprati*/
   
   $check_exp = gdrcd_query("SELECT * FROM personaggio WHERE nome ='$nome'");
   
   if ($check_exp['esperienza'] >= 25 && $check_exp['esperienza'] < 100) {
   $ps = 1;
   } else if ($check_exp['esperienza'] >= 100 && $check_exp['esperienza'] < 200) {
   $ps = 2;
   } else if ($check_exp['esperienza'] >= 200 && $check_exp['esperienza'] < 500) {
   $ps = 3;
   } else if ($check_exp['esperienza'] >= 500 && $check_exp['esperienza'] < 1000) {
   $ps = 4;
   } else if ($check_exp['esperienza'] >= 1000 && $check_exp['esperienza'] < 2000) {
   $ps = 5;
   } else if ($check_exp['esperienza'] >= 2000 && $check_exp['esperienza'] < 5000) {
   $ps = 6;
   } else if ($check_exp['esperienza'] >= 5000 && $check_exp['esperienza'] < 10000) {
   $ps = 7;
   } else if ($check_exp['esperienza'] >= 10000) {
   $ps = 8;
   }
   
   gdrcd_query("UPDATE personaggio SET punto_razza = $ps WHERE nome = '$nome'"); /* Metto i punti spirito*/

   
   
   
     }

        }

    }

   echo "<script type='text/javascript'>alert('Talenti azzerati e messaggi di notifica inviati');</script>";
}
?>

<?php
    //richiamo tutti pg
    
     $tutti_pg = gdrcd_query("SELECT * FROM personaggio WHERE esperienza > 24 ORDER BY nome", 'result');
?>
<form action="main.php?page=gestione_azzeramento_talenti" method="post" name="cancellaselezione">
<table class="customTable">
<tr>
<td colspan="2">Azzera talenti pg</td>
</tr>
<tr class="second_header">
<td>Nome</td>
<td></td>
</tr>
<?php while ($row = mysqli_fetch_array($tutti_pg)){ ?>
<tr>
<td>
<a href="main.php?page=scheda&pg=<?php echo $row['nome']; ?>"><?php echo $row['nome']; ?></a>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['nome'] ?>">
</td>
</tr>
<?php } ?>
<tr>
<td colspan="2">
<center><input name="delete" type="submit" id="delete" value="Azzera">
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