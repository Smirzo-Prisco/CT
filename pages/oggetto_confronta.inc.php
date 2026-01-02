<?php
// Confronto oggetti tra oggetto2 e oggetto (solo quelli nel mercato)
$query = "
    SELECT 
        o2.id_oggetto AS id_oggetto2,
        o.id_oggetto AS id_oggetto,
        o.nome AS nome
    FROM oggetto2 o2
    INNER JOIN oggetto o ON o.nome = o2.nome
    INNER JOIN mercato m ON m.id_oggetto = o.id_oggetto
    ORDER BY o.nome ASC
";

$result = gdrcd_query($query, 'result');

// Se è stato inviato il modulo per trasferire oggetti
if (isset($_POST['trasferisci']) && is_array($_POST['oggetti_da_trasferire'])) {
    foreach ($_POST['oggetti_da_trasferire'] as $nome => $ids) {
        $id_oggetto2 = (int)$ids['id_oggetto2'];
        $id_oggetto = (int)$ids['id_oggetto'];

        // Trova i personaggi che possiedono quell'oggetto nella vecchia tabella
        $possessori = gdrcd_query("SELECT * FROM clgpersonaggiooggetto2 WHERE id_oggetto = $id_oggetto2", 'result');

        while ($pg = gdrcd_query($possessori, 'fetch')) {
            gdrcd_query("
                INSERT INTO clgpersonaggiooggetto (
                    nome, id_oggetto, numero, cariche, commento, commento_master, commento_shop, posizione,
                    isTemp, used, data_scadenza, temp_giorni
                ) VALUES (
                    '" . gdrcd_filter('in', $pg['nome']) . "',
                    $id_oggetto,
                    " . (int)$pg['numero'] . ",
                    " . (int)$pg['cariche'] . ",
                    '" . gdrcd_filter('in', $pg['commento']) . "',
                    '" . gdrcd_filter('in', $pg['commento_master']) . "',
                    '" . gdrcd_filter('in', $pg['commento_shop']) . "',
                    " . (int)$pg['posizione'] . ",
                    " . (int)$pg['isTemp'] . ",
                    " . (int)$pg['used'] . ",
                    '" . gdrcd_filter('in', $pg['data_scadenza']) . "',
                    " . (int)$pg['temp_giorni'] . "
                )
            ");
        }
        gdrcd_query($possessori, 'free');
    }
    echo "<div class='success' style='text-align: center; margin: 20px;'>Oggetti trasferiti con successo.</div>";
}
?>

<div class="pagina_gestione_mercato">
    <h2 style="text-align: center; color: #ce846f; margin-bottom: 20px;">Confronto Oggetti nel Mercato</h2>

    <form method="post">
        <table class="customTable" style="width: 100%; text-align: left;">
            <thead>
                <tr>
                    <th style="color: #ce846f;">Nome Oggetto</th>
                    <th style="color: #ce846f;">ID (oggetto)</th>
                    <th style="color: #ce846f;">ID (oggetto2)</th>
                    <th style="color: #ce846f;">Trasferisci</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = gdrcd_query($result, 'fetch')) { ?>
                    <tr>
                        <td><?php echo gdrcd_filter('out', $row['nome']); ?></td>
                        <td><?php echo (int) $row['id_oggetto']; ?></td>
                        <td><?php echo (int) $row['id_oggetto2']; ?></td>
                        <td>
                            <input type="checkbox" name="oggetti_da_trasferire[<?php echo htmlspecialchars($row['nome']); ?>][id_oggetto]" value="<?php echo (int)$row['id_oggetto']; ?>" checked>
                            <input type="hidden" name="oggetti_da_trasferire[<?php echo htmlspecialchars($row['nome']); ?>][id_oggetto2]" value="<?php echo (int)$row['id_oggetto2']; ?>">
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 20px;">
            <input type="submit" name="trasferisci" value="Trasferisci Oggetti" class="ares" style="background-color: #0f111d; color: #ce846f; padding: 5px 10px; border-radius: 5px;">
        </div>
    </form>
</div>

<?php gdrcd_query($result, 'free'); ?>
