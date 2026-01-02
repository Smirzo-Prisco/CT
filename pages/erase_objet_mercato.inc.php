<div class="pagina_gestione_manutenzione">
<?php
    /*HELP: */
    /*Controllo permessi utente*/
    
    /*Controllo permessi*/
    $mestiere = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
    
    if($_SESSION['admin'] != 1 && ($mestiere['id_mestiere'] != 3 && $mestiere['id_mestiere'] != 4)) {
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
            
   gdrcd_query("DELETE FROM mercato WHERE id_oggetto = '$nome'"); /* CANCELLO PG*/
   
     }

        }

    }


}
?>

<?php
    //richiamo tutti pg
    
     $tutti_pg = gdrcd_query("SELECT codtipooggetto.*, mercato.*, oggetto.* FROM oggetto JOIN mercato ON oggetto.id_oggetto=mercato.id_oggetto JOIN codtipooggetto ON codtipooggetto.cod_tipo=oggetto.tipo ORDER BY oggetto.nome, oggetto.tipo ASC", 'result');
     $elenco_magic = gdrcd_query("SELECT codtipooggetto.*, mercato.*, oggetto.* FROM oggetto JOIN mercato ON oggetto.id_oggetto=mercato.id_oggetto JOIN codtipooggetto ON codtipooggetto.cod_tipo=oggetto.tipo WHERE oggetto.tipo = '8' ORDER BY oggetto.nome, oggetto.tipo ASC", 'result');
     $elenco_pandora = gdrcd_query("SELECT codtipooggetto.*, mercato.*, oggetto.* FROM oggetto JOIN mercato ON oggetto.id_oggetto=mercato.id_oggetto JOIN codtipooggetto ON codtipooggetto.cod_tipo=oggetto.tipo WHERE oggetto.tipo = '9' ORDER BY oggetto.nome, oggetto.tipo ASC", 'result');

?>
<form action="main.php?page=erase_objet_mercato" method="post" name="cancellaselezione">
<table class="customTable">
<tr>
<td colspan="3">Elimina oggetti dal mercato</td>
</tr>
<tr class="second_header">
<td>Nome Oggetto</td>
<td>Categoria</td>
<td></td>
</tr>
<?php if ($mestiere['id_mestiere'] == 3) {

//MAGIC

while ($row = mysqli_fetch_array($elenco_magic)){ ?>
<tr>
<td>
<?php echo $row['nome']; ?>
</td>
<td>
<?php echo 'Magic Shop'; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['id_oggetto'] ?>">
</td>
</tr>
<?php } 
} //FINE MAGIC

if ($mestiere['id_mestiere'] == 4) {

//MAGIC

while ($row = mysqli_fetch_array($elenco_secret)){ ?>
<tr>
<td>
<?php echo $row['nome']; ?>
</td>
<td>
<?php echo 'Secret Pandora'; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['id_oggetto'] ?>">
</td>
</tr>
<?php } 
} //FINE SECRET

if ($_SESSION['admin'] == 1) {

while ($row = mysqli_fetch_array($tutti_pg)){ ?>
<tr>
<td>
<?php echo $row['nome']; ?>
</td>
<td>
<?php echo $row['tipo']; ?>
</td>
<td>
<input type="checkbox" name="checkbox[]" value="<?= $row['id_oggetto'] ?>">
</td>
</tr>
<?php } 
}//fine admin
?>
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