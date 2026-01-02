<?php

$statistiche = [
    'car0' => 'Forza',
    'car4' => 'Mente',
    'car6' => 'Tempra',
    'car2' => 'Destrezza',
    'car8' => 'Potere'
];

echo "<!DOCTYPE html><html lang='it'><head><meta charset='UTF-8'>";
echo "<title>Classifiche Statistiche</title>";
echo "<style>
body { background-color: #14172a; color: #cecece; font-family: Lato, sans-serif; padding: 20px; }
h2 { color: #ce846f; border-bottom: 1px solid #ce846f; margin-top: 40px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 8px; text-align: left; border-bottom: 1px solid #555; }
th { color: #ce846f; }
a { color: #8f8f8f; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>";
echo "</head><body>";
echo "<h1>Classifiche Statistiche - Crystal Tokyo</h1>";

foreach ($statistiche as $campo => $etichetta) {
    echo "<h2>Top 10 - $etichetta</h2>";
    $query = "SELECT nome, $campo FROM personaggio WHERE $campo IS NOT NULL ORDER BY $campo DESC LIMIT 10";
    $result = gdrcd_query($query, 'result');

    echo "<table>";
    echo "<tr><th>#</th><th>Nome</th><th>$etichetta</th></tr>";

    $pos = 1;
    while ($row = gdrcd_query($result, 'fetch')) {
        echo "<tr>";
        echo "<td>$pos</td>";
        echo "<td><a href='../scheda.php?pg=" . urlencode($row['nome']) . "'>" . gdrcd_filter_out($row['nome']) . "</a></td>";
        echo "<td>" . (int)$row[$campo] . "</td>";
        echo "</tr>";
        $pos++;
    }

    echo "</table>";
}

echo "</body></html>";
?>