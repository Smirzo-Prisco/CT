<div class="pagina_gestione_manutenzione">

<?php
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
        /*Puoi entrare*/
        
        //richiamo tutti pg
        
        $tutti_pg = gdrcd_query("SELECT * FROM personaggio ORDER BY nome", 'result');


        /*cancelliamo il pg*/
        
        if(isset($_POST['op']) === true) {
            if(gdrcd_filter('get', $_POST['op']) == 'delete') {
                $nome = gdrcd_filter('in', $_POST['pg']);
                
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
                // gdrcd_query("UPDATE msg SET nomesender = 'pgcancellato' WHERE nomesender = '$nome'"); /* CANCELLO SMS PG*/
                // gdrcd_query("UPDATE msg SET destinatario = 'pgcancellato' WHERE destinatario = '$nome'"); /* CANCELLO SMS PG*/
                // gdrcd_query("UPDATE msggrpuser SET nome = 'pgcancellato' WHERE nome = '$nome'"); /* CANCELLO SMS PG*/
                gdrcd_query("DELETE from log_spesa WHERE nome = '$nome'");
                echo "<font color='red'><b>$nome cancellato (skill, entrate, doppi, oggetti)</b></font><br>";
            }
        }
?>
    
<center>
    <font size=4>Cancella personaggio</font><br/><br/>
    <font size=1>Il personaggio verr&agrave; cancellato insieme alle sue skill, oggetti, punti esperienza, e tutto il dato storico.<br>Non sar&agrave; possibile recuperarlo in alcun modo.</font>
    <br><br> 
    <form action="main.php?page=erasepg_scelta" method="Post">
        <?php if(gdrcd_query($tutti_pg, 'num_rows') > 0) { ?>
                    <select name="pg">
                        <?php while($option = gdrcd_query($tutti_pg, 'fetch')) { 
                            if($option['nome'] != $_SESSION['login']) { ?>
                            <option value="<?php echo $option['nome']; ?>">
                                <?php echo gdrcd_filter('out', $option['nome']); ?>
                            </option>
                        <?php
                        }
                        }
                        gdrcd_query($tutti_pg, 'free');
                        ?>
                    </select>
                    <?php } ?>

        <input type="hidden" name="op" value="delete" />
        <input type="submit" value="cancella" onclick="return confirm('Sei sicuro di voler cancellare questo personaggio? L’operazione è irreversibile.');"> 
    </form>
</center>
<?php
}