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

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">
    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>
    <div class="gp-topbar__center">
        <span class="gp-title">Azzera skill personaggi</span>
    </div>
</div>

<form action="main.php?page=gestione_azzeramento_skill" method="post" name="cancellaselezione">
    <div class="gp-list">
        <table class="gp-table--azzeramento">
            <thead>
                <tr>
                    <th class="gp-th-name">Nome</th>
                    <th>Punti</th>
                    <th class="gp-th-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_array($tutti_pg)) :
                    $check_punti = gdrcd_query("SELECT sum(grado) AS total FROM clgpersonaggioabilita WHERE nome = '". $row['nome'] ."' AND (id_abilita > 42 AND id_abilita < 349)"); ?>
                <tr>
                    <td class="gp-cell--name"><a href="main.php?page=scheda&pg=<?=$row['nome']?>"><?=$row['nome']?></a></td>
                    <td>
                        <?=$check_punti['total']?>
                        <input type="hidden" name="punti" value="<?=$check_punti['total']?>">
                    </td>
                    <td class="gp-cell--actions"><input type="checkbox" name="checkbox[]" value="<?=$row['nome']?>"></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="gp-form-actions">
        <input type="button" value="Seleziona tutto" onClick="SelezTT()" class="btn btn--ghost btn-sm">
        <input type="submit" name="delete" id="delete" value="Azzera" class="btn btn--primary btn-sm">
    </div>
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