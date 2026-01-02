<?php
if ($_SESSION['admin'] == 1) {

// Definizione degli scaglioni di esperienza e dei relativi rapporti punti esperienza - punti statistica
$scaglioni_esperienza = array(
    array("inizio" => 0, "fine" => 100, "rapporto" => 5),
    array("inizio" => 101, "fine" => 500, "rapporto" => 10),
    array("inizio" => 501, "fine" => 800, "rapporto" => 15),
    array("inizio" => 801, "fine" => 1200, "rapporto" => 20),
    array("inizio" => 1201, "fine" => 1600, "rapporto" => 25),
    array("inizio" => 1601, "fine" => PHP_INT_MAX, "rapporto" => 30)
);

// Query per ottenere tutti i personaggi che hanno usato almeno 1 punto esperienza
$query = "SELECT nome, esperienza, 
                 ((car0 - 10 - car1) + 
                  (car2 - 10 - car3) + 
                  (car4 - 10 - car5) + 
                  (car6 - 10 - car7) + 
                  (car8 - 10 - car9)) AS esperienza_assegnata 
          FROM personaggio 
          WHERE (car0 - 10 - car1) > 0 
             OR (car2 - 10 - car3) > 0 
             OR (car4 - 10 - car5) > 0 
             OR (car6 - 10 - car7) > 0 
             OR (car8 - 10 - car9) > 0
             ORDER BY esperienza_assegnata desc";
$risultato = gdrcd_query($query, 'result');

?>



    <h2>Lista personaggi e incremento</h2>
    <table border='1' cellpadding='10' cellspacing='0'>
            <tr>
                <th>Nome</th>
                <th>Esperienza Assegnata</th>
                <th>Esperienza Prevista dallo Scaglione</th>
            </tr>
             <?php
            while ($personaggio = gdrcd_query($risultato, 'fetch')) {
                $esperienza = $personaggio['esperienza'];
                $esperienza_assegnata = $personaggio['esperienza_assegnata'];
                $esperienza_prevista = 0;

                // Calcola l'esperienza prevista sommando tutti gli scaglioni fino a quello corrente
                foreach ($scaglioni_esperienza as $scaglione) {
                    if ($esperienza > $scaglione["fine"]) {
                        // Aggiungi il contributo di tutto lo scaglione se l'esperienza del personaggio è superiore al massimo dello scaglione
                        $esperienza_prevista += ($scaglione["fine"] - $scaglione["inizio"] + 1) / $scaglione["rapporto"];
                    } else {
                        // Aggiungi il contributo parziale dello scaglione corrente
                        $esperienza_prevista += ($esperienza - $scaglione["inizio"]) / $scaglione["rapporto"];
                        break;
                    }
                }

                // Stampa la riga della tabella con i dati del personaggio
                echo "<tr>";
                echo "<td>" . gdrcd_filter('out', $personaggio['nome']) . "</td>";
                echo "<td>" . number_format($esperienza_assegnata, 1) . "</td>";
                echo "<td>" . number_format($esperienza_prevista, 1) . "</td>";
                echo "</tr>";
            }
            ?>
    </table>



<?php } ?>