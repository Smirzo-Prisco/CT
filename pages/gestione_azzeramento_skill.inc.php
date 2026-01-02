<div class="pagina_gestione_manutenzione">

<?php
/*Controllo permessi utente*/
if($_SESSION['admin'] != 1) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    die();
}
//inizio le operazioni di cancellazione
if (isset($_POST['delete'])){
    $checkbox = $_POST['checkbox'];
    $count = is_array($checkbox) ? count($checkbox) : false;
    if($count !== false) {
        for($i=0;$i<$count;$i++){
            if(!empty($checkbox[$i])) {
                $nome= $checkbox[$i];
                $points = gdrcd_query("SELECT sum(punto_skill) AS total FROM log_spesa WHERE nome = '". $nome ."' AND id_abilita > 0");
                $punti = $points['total'] * 1;      
                gdrcd_query("UPDATE personaggio SET esperienza_s = esperienza_s + $punti WHERE nome = '$nome'"); /* CANCELLO PG */
                gdrcd_query("DELETE from clgpersonaggioabilita WHERE id_abilita > 44 AND nome = '$nome'"); /* CANCELLO SMS PG */
                gdrcd_query("DELETE from log_spesa WHERE nome = '$nome' AND id_abilita > 0");   
            }
        }
    }
    // echo "<script type='text/javascript'>alert('Personaggi azzerati e messaggi di notifica inviati');</script>";
} // fine azzeramento

// richiamo tutti pg tranne me stesso
$login_corrente = gdrcd_filter('in', $_SESSION['login']);
$tutti_pg = gdrcd_query("SELECT * FROM personaggio WHERE id_gilda > 1 AND nome != '$login_corrente' ORDER BY nome", 'result');
?>
    <form action="main.php?page=gestione_azzeramento_skill" method="post" name="cancellaselezione">
        <table class="customTable">
            <tr><td colspan="2">Azzerare skill pg</td></tr>
            <tr class="second_header"><td>Nome</td><td>Punti</td><td></td></tr>
            <?php while ($row = mysqli_fetch_array($tutti_pg)) :
                $check_punti = gdrcd_query("SELECT sum(grado) AS total FROM clgpersonaggioabilita WHERE nome = '". $row['nome'] ."' AND (id_abilita > 42 AND id_abilita < 349)"); ?>
                <tr>
                    <td><a href="main.php?page=scheda&pg=<?=$row['nome']?>"><?=$row['nome']?></a></td>
                    <td>
                        <?=$check_punti['total']?>
                        <input type="hidden" name="punti" value="<?=$check_punti['total']?>">
                    </td>
                    <td><input type="checkbox" name="checkbox[]" value="<?=$row['nome']?>"></td>
                </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="2">
                    <center>
                        <input type="submit" name="delete" id="delete" value="Azzera">
                        &nbsp;&nbsp;
                        <input type="button" value="Seleziona tutto" onClick="SelezTT()" style="background-color: #070a1b; border: 1px solid rgba(58, 72, 86, 0.49); color: #8a9ca0; font-size: 10px; font-family: Tahoma, Geneva, sans-serif; padding: 4px; text-transform: uppercase;">
                    </center>
                </td>
            </tr>
        </table>
    </form>
</div>

<script type="text/javascript">
    function SelezTT() {
        var i = 0;
        var cancellaselezione = document.cancellaselezione.elements;

        for (i=0; i<cancellaselezione.length; i++) {
            if(cancellaselezione[i].type == "checkbox") cancellaselezione[i].checked = !(cancellaselezione[i].checked);
        }
    }
</script>