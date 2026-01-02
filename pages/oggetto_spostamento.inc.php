<?php
// Connessione al database (assumendo che sia già attiva)

// FASE 1: Selezione tipologia
$tipologia_selezionata = isset($_POST['tipologia']) ? (int)$_POST['tipologia'] : null;

// FASE 6: Salvataggio se inviato
if (isset($_POST['salva_oggetti'])) {
    foreach ($_POST['oggetti'] as $id => $dati) {
        // Sanifica e prepara i dati per l'inserimento nella tabella oggetto
        $id = (int)$id;
        $categoria = gdrcd_filter('in', $dati['categoria']);
        $cariche = ($dati['cariche'] === 'illimitato') ? 'illimitato' : (int)$dati['cariche'];
        $ricarica_massima = (int)$dati['ricarica_massima'];
        $richiede_ricarica = (int)$dati['richiede_ricarica'];
        $tipo_arma = (int)$dati['tipo_arma'];
        $bonus_arma = (int)$dati['bonus_arma'];
        $heal = (int)$dati['heal'];
        $integrita = (int)$dati['integrita'];
        $bonus_car1 = (int)$dati['bonus_car1'];
        $bonus_car2 = (int)$dati['bonus_car2'];
        $bonus_car3 = (int)$dati['bonus_car3'];
        $bonus_car4 = (int)$dati['bonus_car4'];
        $bonus_car5 = (int)$dati['bonus_car5'];
        $isTemp = isset($dati['isTemp']) ? 1 : 0;
        $temp_giorni = (int)$dati['temp_giorni'];

        // Recupero oggetto da oggetto2 per info base
        $oggetto_base = gdrcd_query("SELECT * FROM oggetto2 WHERE id_oggetto = $id LIMIT 1");

        gdrcd_query("INSERT INTO oggetto (
    tipo, nome, creatore, data_inserimento, descrizione, ubicabile,
    cariche, heal, bonus_car1_extra, bonus_car2_extra, bonus_car3_extra,
    bonus_car4_extra, bonus_car5_extra, urlimg, isTemp, temp_giorni,
    tipo_arma, attacco, richiede_ricarica, ricarica_massima, categoria, costo
) VALUES (
    {$oggetto_base['tipo']}, '{$oggetto_base['nome']}', '{$oggetto_base['creatore']}', '{$oggetto_base['data_inserimento']}',
    '".addslashes($oggetto_base['descrizione'])."', {$oggetto_base['ubicabile']},
    " . (is_numeric($cariche) ? $cariche : "'".$cariche."'") . ",
    $heal, $bonus_car1, $bonus_car2, $bonus_car3, $bonus_car4, $bonus_car5,
    '{$oggetto_base['urlimg']}', $isTemp, $temp_giorni,
    $tipo_arma, $bonus_arma, $richiede_ricarica, $ricarica_massima, '$categoria', {$oggetto_base['costo']})");


        // Inserimento in mercato
        $new_id = gdrcd_query("SELECT MAX(id_oggetto) AS id FROM oggetto", 'result');
        $id_nuovo = gdrcd_query($new_id, 'fetch')['id'];
        gdrcd_query("INSERT INTO mercato (id_oggetto, numero) VALUES ($id_nuovo, 1)");
        gdrcd_query($new_id, 'free');
    }

    echo "<div class='success'>Oggetti trasferiti correttamente.</div>";
}
?>

<form method="post" action="">
    <label>Filtra per tipologia (campo tipo):</label>
    <select name="tipologia" onchange="this.form.submit()">
        <option value="">-- Seleziona --</option>
        <?php for ($i = 0; $i <= 20; $i++) echo "<option value='$i'".($tipologia_selezionata == $i ? " selected" : "").">$i</option>"; ?>
    </select>
</form>

<?php
if ($tipologia_selezionata !== null) {
    $oggetti = gdrcd_query("SELECT o2.* FROM oggetto2 o2 INNER JOIN mercato2 m2 ON o2.id_oggetto = m2.id_oggetto WHERE o2.tipo = $tipologia_selezionata", 'result');

    echo "<form method='post'><table class='customTable'>";
    echo "<tr><th>Nome</th><th>Descrizione</th><th>Categoria</th><th>Extra</th></tr>";

    while ($row = gdrcd_query($oggetti, 'fetch')) {
        echo "<tr><td>{$row['nome']}</td><td>{$row['descrizione']}</td><td>";
        echo "<select name='oggetti[{$row['id_oggetto']}][categoria]' onchange='mostraExtra(this, {$row['id_oggetto']})'>
                <option value=''>--</option>
                <option value='arma'>Arma</option>
                <option value='curativo'>Curativo</option>
                <option value='statistica'>Statistica</option>
                <option value='magico'>Magico</option>
                <option value='standard'>Standard</option>
              </select></td>";
        echo "<td><div id='extra_{$row['id_oggetto']}'></div></td></tr>";
    }

    gdrcd_query($oggetti, 'free');
    echo "</table><input type='submit' name='salva_oggetti' value='Salva e Sposta' class='ares'></form>";
}
?>

<script>
function mostraExtra(select, id) {
    const categoria = select.value;
    const contenitore = document.getElementById('extra_' + id);
    let html = '';

    if (categoria === 'arma') {
        html = `
            Tipo arma: <select name="oggetti[${id}][tipo_arma]">
                <option value="1">Bianca</option>
                <option value="2">Lancio</option>
                <option value="3">Fuoco</option>
            </select><br>
            Bonus arma: <select name="oggetti[${id}][bonus_arma]">
                ${[0,1,2,3].map(v => `<option value='${v}'>${v}</option>`).join('')}
            </select><br>
            Ricarica max: <select name="oggetti[${id}][ricarica_massima]">
                ${[...Array(11).keys()].map(v => `<option value='${v}'>${v}</option>`).join('')}
            </select><br>
            <input type="hidden" name="oggetti[${id}][richiede_ricarica]" value="1">
        `;
    } else if (categoria === 'curativo') {
        html = `
            Salute: <select name="oggetti[${id}][heal]">${[...Array(21).keys()].map(v => `<option value='${v}'>${v}</option>`).join('')}</select><br>
            Integrità: <select name="oggetti[${id}][integrita]">${[...Array(21).keys()].map(v => `<option value='${v}'>${v}</option>`).join('')}</select><br>
            Cariche: <select name="oggetti[${id}][cariche]">${[...Array(11).keys()].map(v => `<option value='${v}'>${v}</option>`).join('')}</select>
            <input type="hidden" name="oggetti[${id}][richiede_ricarica]" value="0">
        `;
    } else if (categoria === 'statistica') {
    const bonusOptions = [...Array(21).keys()].map(v => {
        const val = v - 10;
        const label = (val > 0 ? '+' : '') + val;
        return `<option value='${val}'>${label}</option>`;
    }).join('');

    html = `
        Durata giorni: <select name="oggetti[${id}][temp_giorni]">
            ${[...Array(31).keys()].slice(1).map(v => `<option value='${v}'>${v}</option>`).join('')}
        </select><br>
        Bonus 1: <select name="oggetti[${id}][bonus_car1]">${bonusOptions}</select>
        Bonus 2: <select name="oggetti[${id}][bonus_car2]">${bonusOptions}</select>
        Bonus 3: <select name="oggetti[${id}][bonus_car3]">${bonusOptions}</select>
        Bonus 4: <select name="oggetti[${id}][bonus_car4]">${bonusOptions}</select>
        Bonus 5: <select name="oggetti[${id}][bonus_car5]">${bonusOptions}</select><br>
        Ricarica max: <select name="oggetti[${id}][ricarica_massima]">
            ${[...Array(11).keys()].map(v => `<option value='${v}'>${v}</option>`).join('')}
        </select>
        <input type="hidden" name="oggetti[${id}][richiede_ricarica]" value="1">
        <input type="hidden" name="oggetti[${id}][isTemp]" value="1">
    `;
} else if (categoria === 'magico') {
        html = `
            Cariche: <select name="oggetti[${id}][cariche]">${[...Array(11).keys()].slice(1).map(v => `<option value='${v}'>${v}</option>`).join('')}</select>
            Ricarica max: <select name="oggetti[${id}][ricarica_massima]">${[...Array(11).keys()].slice(1).map(v => `<option value='${v}'>${v}</option>`).join('')}</select>
            <input type="hidden" name="oggetti[${id}][richiede_ricarica]" value="1">
        `;
    } else if (categoria === 'standard') {
        html = `
            Cariche: <select name="oggetti[${id}][cariche]">
                <option value='illimitato'>Illimitato</option>
                ${[...Array(11).keys()].slice(1).map(v => `<option value='${v}'>${v}</option>`).join('')}
            </select>
            <input type="hidden" name="oggetti[${id}][richiede_ricarica]" value="0">
        `;
    }

    contenitore.innerHTML = html;
}
</script>
