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
            
   gdrcd_query("DELETE FROM personaggio WHERE nome = '$nome'"); /* CANCELLO PG*/
   gdrcd_query("DELETE FROM appuntamenti WHERE autore = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM BakCalendario WHERE Destinatario = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome = '$nome'"); /* CANCELLO SKILL PG*/
   gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome = '$nome'"); /* CANCELLO OGGETTI PG*/
   gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'"); /* CANCELLO GILDA PG*/
   gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio = '$nome'"); /* CANCELLO MESTIERE PG*/
   gdrcd_query("DELETE FROM clgpersonaggiolavoro WHERE personaggio = '$nome'"); /* CANCELLO LAVORO PG*/
   gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '$nome'"); /* CANCELLO INCLINAZIONE PG*/
   gdrcd_query("DELETE FROM clgpersonaggiomostrine WHERE nome = '$nome'"); /* CANCELLO MOSTRINE PG*/
   gdrcd_query("DELETE FROM log WHERE nome_interessato = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM log_entrate WHERE Nome = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM log_doppi WHERE Nome = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM messaggi WHERE destinatario = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM messaggi WHERE mittente = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM privilegi WHERE nome = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM Punti WHERE nome = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM araldo_letto WHERE nome = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE FROM struttura_affetti WHERE username = '$nome'"); /* CANCELLO SMS PG*/
   gdrcd_query("DELETE from log_spesa WHERE nome = '$nome'");

        }

    }

}
}
?>

<?php
    //richiamo tutti pg
    
     $tutti_pg = gdrcd_query("SELECT * FROM personaggio WHERE esperienza = '0.0000' AND (DATE(ora_entrata) = DATE(data_iscrizione)) ORDER BY nome", 'result');
?>
<form action="main.php?page=erase_inactive" method="post" name="cancellaselezione">
<table class="customTable">
<tr>
<td colspan="4">Personaggi inattivi da 5 mesi</td>
</tr>
<tr class="second_header">
<td>Nome</td>
<td>Iscrizione</td>
<td>Ultimo accesso</td>
<td></td>
</tr>
<?php while ($row = mysqli_fetch_array($tutti_pg)){ ?>
<tr>
<td>
<a href="main.php?page=scheda&pg=<?php echo $row['nome']; ?>"><?php echo $row['nome']; ?></a>
</td>
<td>
<?php echo $row['data_iscrizione']; ?>
</td>
<td>
<?php 
if ($row['ultimo_refresh'] == NULL) {
$row['ultimo_refresh'] = "<b><u>Mai loggato</u></b>";
}
echo $row['ultimo_refresh']; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['nome'] ?>">
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