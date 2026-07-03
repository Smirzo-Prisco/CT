<?php
/**
 * gestione_regolamento.inc.php — Pannello admin/guida gestione regolamento
 *
 * Stessa architettura grafica e funzionale di gestione_personaggio.inc.php:
 * topbar con filtro, tabella con azioni a icona, un unico modale nella pagina
 * popolato/salvato via fetch (pages/api_regolamento.php + includes/regolamento.js)
 * invece del vecchio giro di reload PHP per ogni inserimento/modifica/cancellazione.
 *
 * La logica di diff/notifica in bacheca sulle modifiche (invariata) è ora
 * in pages/api_regolamento.php.
 */

require_once(__DIR__ . '/../includes/custom_functions.inc.php');

add_script('/includes/regolamento.js');

if (!hasPermesso($_SESSION, ['admin', 'guida'])) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    return;
}

$is_admin = ($_SESSION['admin'] == 1);

// ── Filtro per tipo (nessuna paginazione: mostra sempre tutti i record) ──
$valid_tipi  = ['ambientazione', 'primipassi', 'regolamento', 'regolegioco', 'manuali', 'combattimento', 'staff'];
$tipo_filter = (isset($_GET['tipo_filter']) && in_array($_GET['tipo_filter'], $valid_tipi)) ? $_GET['tipo_filter'] : '';

if ($is_admin) {
    $where = $tipo_filter ? "WHERE tipo = '$tipo_filter'" : '';
} else {
    $where = $tipo_filter ? "WHERE tipo != 'ambientazione' AND tipo = '$tipo_filter'" : "WHERE tipo != 'ambientazione'";
}

$result = gdrcd_query("SELECT articolo, titolo, tipo, testo FROM regolamento $where ORDER BY articolo", 'result');

$articoli = [];
while ($row = gdrcd_query($result, 'fetch')) $articoli[] = $row;
gdrcd_query($result, 'free');

$tipi_disponibili = $is_admin
    ? $valid_tipi
    : ['primipassi', 'regolamento', 'regolegioco', 'manuali', 'combattimento', 'staff'];

$tipo_label = [
    'ambientazione' => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][4]),
    'primipassi'    => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][6]),
    'regolamento'   => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][0]),
    'regolegioco'   => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][5]),
    'manuali'       => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][1]),
    'combattimento' => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][2]),
    'staff'         => gdrcd_filter('out', $MESSAGE['manuale']['tipo'][3]),
];
?>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">

    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <div class="gp-topbar__center">
        <form method="get" action="main.php" class="gp-filter-form">
            <input type="hidden" name="page" value="gestione_regolamento">
            <select name="tipo_filter" onchange="this.form.submit()">
                <option value="">— Tutti i tipi —</option>
                <?php foreach ($tipi_disponibili as $t): ?>
                <option value="<?= $t ?>" <?= $tipo_filter === $t ? 'selected' : '' ?>><?= $tipo_label[$t] ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <input type="text" id="searchRegolamento" class="gp-search" placeholder="Cerca articolo…">
    </div>

    <div class="gp-topbar__right">
        <button class="btn btn--primary btn-sm" onclick="apriRegolamentoForm(null)">
            <i class="fa-solid fa-plus"></i>&nbsp; <?= gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['link']['new']) ?>
        </button>
    </div>

</div>

<!-- ── Elenco articoli ────────────────────────────────────────── -->
<div class="gp-list">
    <table id="regolamentoTable">
        <thead>
            <tr>
                <th>Articolo</th>
                <th>Titolo</th>
                <th>Tipo</th>
                <th class="gp-th-actions">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articoli)): ?>
            <tr><td colspan="4" style="text-align:center; padding:20px; font-style:italic; color:var(--color-text-muted);">Nessun articolo trovato.</td></tr>
            <?php else: foreach ($articoli as $row): ?>
            <tr>
                <td class="gp-cell--meta"><?= (int)$row['articolo'] ?></td>
                <td class="gp-cell--name"><?= gdrcd_filter('out', $row['titolo']) ?></td>
                <td class="gp-cell--meta"><?= $tipo_label[$row['tipo']] ?? gdrcd_filter('out', $row['tipo']) ?></td>
                <td class="gp-cell--actions">
                    <div class="gp-actions">
                        <button class="btn-action btn-action--edit btn-action--icon" title="Modifica" onclick="apriRegolamentoForm(<?= (int)$row['articolo'] ?>)">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-action btn-action--delete btn-action--icon" title="Elimina" onclick="eliminaArticoloRegolamento(<?= (int)$row['articolo'] ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ── Modale inserimento/modifica articolo ─────────────────────── -->
<div class="pg-edit-container" id="regolamento_edit_container" role="dialog" aria-modal="true">
    <div class="modal-content">

        <div class="gp-modal-header">
            <h2 class="gp-modal-title">
                <i class="fa-solid fa-book"></i>
                <span id="gp-modal-regolamento-title">Nuovo articolo</span>
            </h2>
            <div class="gp-modal-header-actions">
                <button type="button" class="gp-modal-close" id="closeRegolamentoModal" aria-label="Chiudi">✕</button>
            </div>
        </div>

        <form id="formSaveRegolamento">
            <div class="form-section">
                <div class="form-group">
                    <label for="reg-tipo"><?= gdrcd_filter('out', $MESSAGE['manuale']['tipo_info']) ?></label>
                    <select id="reg-tipo" name="tipo">
                        <?php foreach ($valid_tipi as $t): ?>
                        <option value="<?= $t ?>"><?= $tipo_label[$t] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reg-titolo"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['title']) ?></label>
                    <input type="text" id="reg-titolo" name="titolo" required>
                </div>
                <div class="form-group">
                    <label for="reg-testo"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['rules']['infos']) ?></label>
                    <textarea id="reg-testo" name="testo" rows="10"></textarea>
                    <p class="gp-label-note"><?= gdrcd_filter('out', $MESSAGE['interface']['help']['bbcode']) ?></p>
                </div>

                <!-- Valori originali usati lato server per calcolare il diff da notificare in bacheca -->
                <input type="hidden" id="reg-art-originale" name="art">
                <input type="hidden" id="reg-old-titolo" name="old_titolo">
                <textarea id="reg-old-testo" name="old_testo" style="display:none;"></textarea>
            </div>
        </form>

        <div class="gp-modal-footer">
            <button type="submit" id="gp-save-regolamento-btn" form="formSaveRegolamento" class="btn-action btn-action--edit">
                <i class="fa-solid fa-floppy-disk"></i> Salva
            </button>
        </div>

    </div>
</div>
