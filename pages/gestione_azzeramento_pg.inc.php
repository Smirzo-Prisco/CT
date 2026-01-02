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
    $selezionati = is_array($checkbox) ? count($checkbox) : false;

    if($selezionati !== false) {
        // Ciclo tutti i nomi dei pg selezionati
        for($i=0;$i<$selezionati;$i++) {
            if(!empty($checkbox[$i])) {
                $nome = $checkbox[$i];
                $points_spesa = gdrcd_query("SELECT sum(destrezza + forza + mente + tempra + potere + punto_skill) AS total FROM log_spesa WHERE nome = '$nome'");
                $punti = $points_spesa['total'] * 1;      
                gdrcd_query("UPDATE personaggio SET shin = (shin + $punti) WHERE nome = '$nome'"); /* CANCELLO PG */

                gdrcd_query("DELETE FROM clgpersonaggioabilita 
                            WHERE nome = '$nome'
                            AND id_abilita NOT IN (
                                SELECT id_abilita 
                                FROM abilita 
                                WHERE tipo IN ('Talento', 'Skill temporanea'))");
                /* CANCELLO SMS PG*/
                gdrcd_query("DELETE from log_spesa WHERE nome = '$nome'"); /* CANCELLO SMS PG */
            
                // valutare il personaggio nello scaglione dei punti statistica
                $info_pg1 = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome = '$nome'");

                // Ottieni il valore dell'esperienza dal risultato della query
                $esperienza_personaggio = $info_pg1['esperienza'];

                if ($info_pg1['esperienza'] < 101) { /* CANCELLO PG */
                    gdrcd_query("UPDATE personaggio SET esperienza_r = esperienza, car0=10, car1=0, car2=10, car3=0, car4=10, car5=0, car6=10, car7=0, car8=10, car9=0, esperienza_s = 0, punto_skill = 0.0 WHERE nome = '$nome'");
                } else {
                    // Inizializza il numero di punti statistica disponibili
                    $punti_statistica_disponibili = 0;
                    // Definisci gli scaglioni di esperienza e i relativi rapporti punti esperienza - punti statistica
                    $scaglioni_esperienza = array(
                        array("inizio" => 0, "fine" => 100, "rapporto" => 5),
                        array("inizio" => 101, "fine" => 500, "rapporto" => 10),
                        array("inizio" => 501, "fine" => 800, "rapporto" => 15),
                        array("inizio" => 801, "fine" => 1200, "rapporto" => 20),
                        array("inizio" => 1201, "fine" => 1600, "rapporto" => 25),
                        array("inizio" => 1601, "fine" => PHP_INT_MAX, "rapporto" => 30)
                    );

                    // Trova lo scaglione corretto dell'esperienza
                    $scaglione_esperienza_personaggio = -1;
                    foreach ($scaglioni_esperienza as $scaglione) {
                        if ($esperienza_personaggio >= $scaglione["inizio"] && $esperienza_personaggio <= $scaglione["fine"]) {
                            $scaglione_esperienza_personaggio = $scaglione;
                            break;
                        }
                    }

                    // Calcola i punti statistica disponibili escludendo lo scaglione in cui rientra l'esperienza del personaggio
                    $escludi_scaglione = false;
                    foreach ($scaglioni_esperienza as $scaglione) {
                        if ($scaglione == $scaglione_esperienza_personaggio) {
                            $escludi_scaglione = true;
                            continue;
                        }
                        
                        if (!$escludi_scaglione) {
                            // Calcola i punti statistica per lo scaglione corrente
                            $punti_scaglione = floor(($scaglione["fine"] - $scaglione["inizio"] + 1) / $scaglione["rapporto"]);
                            $punti_statistica_disponibili += $punti_scaglione;

                            // Calcola quanta esperienza è esclusa dal conteggio
                            $esperienza_esclusa = ($esperienza_personaggio - $scaglione_esperienza_personaggio['inizio']);

                            // Calcola i punti statistica da aggiungere
                            $punti_da_aggiungere = $esperienza_esclusa / $scaglione_esperienza_personaggio["rapporto"];

                            // Calcola il risultato dei punti statistica disponibili dopo l'aggiunta dei punti da aggiungere
                            $risultato_punti_statistica_disponibili = $punti_statistica_disponibili + $punti_da_aggiungere;

                            $punti_esperienza_r_necessari = $risultato_punti_statistica_disponibili * $scaglione_esperienza_personaggio["rapporto"];
                            /* CANCELLO PG*/
                            gdrcd_query("UPDATE personaggio SET esperienza_r = '$punti_esperienza_r_necessari', car0=10, car1=0, car2=10, car3=0, car4=10, car5=0, car6=10, car7=0, car8=10, car9=0, esperienza_s = 0, punto_skill=0.0 WHERE nome = '$nome'");
                        }
                    }
                }
            }
        }
    }
    echo "<script type='text/javascript'>alert('Personaggi azzerati e messaggi di notifica inviati');</script>";
}
// Gli altri vedono tutti tranne se stessi
$login_corrente = gdrcd_filter('in', $_SESSION['login']);
$tutti_pg = gdrcd_query("SELECT * FROM personaggio WHERE esperienza > 4 ORDER BY nome", 'result');
?>
    <form action="main.php?page=gestione_azzeramento_pg" method="post" name="cancellaselezione">
        <table class="customTable">
            <tr><td colspan="2">Azzerare pg</td></tr>
            <tr class="second_header"><td>Nome</td><td></td></tr>
            <?php while ($row = mysqli_fetch_array($tutti_pg)){ ?>
                <tr>
                    <td><a href="main.php?page=scheda&pg=<?=$row['nome']?>"><?=$row['nome']?></a></td>
                    <td><input type="checkbox" name="checkbox[]" value="<?=$row['nome']?>"></td>
                </tr>
            <?php } ?>
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