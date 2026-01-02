<?php
/* Pagina di controllo per i parametri dei personaggi - Versione semplificata per Crystal Tokyo */

// Controllo accesso solo admin
if ($_SESSION['admin'] != 1) {
    echo '<div class="error">Accesso negato.</div>';
    return;
}

// Recupera tutti i personaggi
// $personaggi = gdrcd_query("SELECT * FROM personaggio", 'result');
$personaggi = gdrcd_query("SELECT 
                                ROW_NUMBER() OVER (ORDER BY (tot_shin + tot_xp + shin_to_spend) DESC) AS riga,
                                nome,
                                shin_to_spend,
                                tot_shin,
                                tot_xp,
                                (tot_shin + tot_xp + shin_to_spend) as tot
                            FROM (
                                SELECT 
                                    nome,
                                    CAST(COALESCE(shin, 0) AS UNSIGNED) as shin_to_spend,
                                    SUM(COALESCE(car1, 0) + COALESCE(car3, 0) + COALESCE(car5, 0) + COALESCE(car7, 0) + COALESCE(car9, 0) + COALESCE(punto_skill, 0)) as tot_shin,
                                    SUM((COALESCE(car0, 0) - COALESCE(car1, 0)) + (COALESCE(car2, 0) - COALESCE(car3, 0)) + (COALESCE(car4, 0) - COALESCE(car5, 0)) + (COALESCE(car6, 0) - COALESCE(car7, 0)) + (COALESCE(car8, 0) - COALESCE(car9, 0))) as tot_xp
                                FROM personaggio
                                GROUP BY nome
                            ) AS subquery
                            ORDER BY tot DESC", 'result');
?>

<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<div class="pagina_gestione_manutenzione">
    <div class="page_title">Controllo Parametri Personaggi</div>
    <table class="customTable" style="width:95%; margin:auto; text-align:left;">
        <tr>
            <th>Riga</th>    
            <th>Nome</th>
            <th>Stats da Exp</th>
            <th>Stats da Shin</th>
            <th>Shin da spendere</th>
            <th>Totale</th>
            <!-- <th>Esito</th> -->
            <th>Note</th>
        </tr>
<?php
while ($pg = gdrcd_query($personaggi, 'fetch')) {
    // Calcolo punti esperienza usati per le statistiche (escludendo 50 iniziali)
    // $exp_stats = ($pg['car0'] - $pg['car1']) + ($pg['car2'] - $pg['car3']) + ($pg['car4'] - $pg['car5']) + ($pg['car6'] - $pg['car7']) + ($pg['car8'] - $pg['car9']) - 50;
    $exp_stats = ($pg['tot_xp'] - 50);

    // Calcolo punti shin usati
    // $shin_stats = $pg['car1'] + $pg['car3'] + $pg['car5'] + $pg['car7'] + $pg['car9'] + $pg['punto_skill'];
    $shin_stats = $pg['tot_shin'];

    // Calcolo totale
    // $totale = $exp_stats + 50 + $shin_stats;
    $totale = $pg['tot'];

    // Controllo limiti
    $errore = false;
    $note = [];

    if ($exp_stats > 260) {
        $errore = true;
        $note[] = "Exp > 260";
    }
    if ($shin_stats > 240) {
        $errore = true;
        $note[] = "Shin > 240";
    }
    if ($totale > 550) {
        $errore = true;
        $note[] = "Totale > 550";
    }

    echo '<tr' . ($errore ? ' style="background-color: rgba(255, 0, 0, 0.2);"' : '') . '>';
    echo '<td align="right">' . $pg['riga'] . '</td>';
    echo '<td>' . $pg['nome'] . '</td>';
    echo '<td align="right">' . ($exp_stats + 50) . '/260</td>';
    echo '<td align="right">' . $shin_stats . '/240</td>';
    echo '<td align="right">' . $pg['shin_to_spend'] . '</td>';
    echo '<td align="right">' . $totale . '/550</td>';
    // echo '<td>' . ($errore ? '<b style="color:red">FUORI LIMITE</b>' : 'OK') . '</td>';
    echo '<td>' . ($errore ? implode(', ', $note) : '-') . '</td>';
    echo '</tr>';
}
?>
    </table>
</div>