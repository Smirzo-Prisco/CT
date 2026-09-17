<?php
require_once(__DIR__ . '../../includes/custom_functions.inc.php');

add_script("/includes/oggetto_ricarica.js");

// Stessa lista di personaggi ricaricabili dell'originale: sempre se stesso,
// più admin (tutti), master (solo pg con oggetti creati da lui), Magic Shop
// (solo pg con oggetti di tipo 8). Nessun'altra combinazione ha diritto di
// vedere la lista — l'idoneità VERA a ricaricare si rivaluta comunque lato
// server in api_oggetto_ricarica.php (op=ricaricaObj), questa lista è solo
// una comodità in UI.
$login = $_SESSION['login'];
$mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '" . gdrcd_filter('in', $login) . "'");

$pg_list = [];
if ($_SESSION['admin'] == 1) {
    $res = gdrcd_query("SELECT nome FROM personaggio WHERE nome != '" . gdrcd_filter('in', $login) . "' ORDER BY nome", 'result');
    while ($r = gdrcd_query($res, 'fetch')) $pg_list[] = $r['nome'];
} elseif ($_SESSION['master'] == 1) {
    $res = gdrcd_query("SELECT DISTINCT c.nome FROM clgpersonaggiooggetto c
                         JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                         WHERE o.creatore = '" . gdrcd_filter('in', $login) . "'", 'result');
    while ($r = gdrcd_query($res, 'fetch')) $pg_list[] = $r['nome'];
} elseif (($mestiere['id_mestiere'] ?? 0) == 3) {
    $res = gdrcd_query("SELECT DISTINCT c.nome FROM clgpersonaggiooggetto c
                         JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                         WHERE o.tipo = 8", 'result');
    while ($r = gdrcd_query($res, 'fetch')) $pg_list[] = $r['nome'];
}
?>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">
    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>
</div>

<h2 class="gp-title">Ricarica oggetto scaduto</h2>

<div class="gp-panel">
    <form id="ricaricaForm">
        <div class="form-group">
            <label for="ricarica_pg">Personaggio</label>
            <select id="ricarica_pg" name="pg">
                <option value="<?= gdrcd_filter('out', $login) ?>"><?= gdrcd_filter('out', $login) ?> (Te stesso)</option>
                <?php foreach ($pg_list as $nome): ?>
                <option value="<?= gdrcd_filter('out', $nome) ?>"><?= gdrcd_filter('out', $nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="ricarica_oggetto_group" hidden>
            <label for="ricarica_oggetto">Oggetto scaduto da ricaricare</label>
            <select id="ricarica_oggetto" name="id_oggetto"></select>
        </div>

        <p id="ricarica_vuoto" class="gp-hint" hidden>Nessun oggetto scaduto da ricaricare per questo personaggio.</p>

        <button type="submit" id="ricarica_submit" class="btn-action btn-action--edit" disabled>
            <i class="fa-solid fa-battery-full"></i> Ricarica oggetto
        </button>
    </form>
</div>
