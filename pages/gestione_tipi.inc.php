<?php
require_once(__DIR__ . '../../includes/custom_functions.inc.php');

add_script("/includes/gestione_tipi.js");

// Pagina unica per 3 tabelle di lookup diverse, selezionate da ?types=:
// items->codtipooggetto (oggetto.tipo), guilds->codtipogilda (gilda.tipo),
// jobs->codtipomestiere (mestiere.tipo). Stessa struttura per tutte e tre,
// da qui la scelta di non triplicare il file.
$types = isset($_REQUEST['types']) ? gdrcd_filter('get', $_REQUEST['types']) : 'items';
if (!in_array($types, ['items', 'guilds', 'jobs'], true)) $types = 'items';

$config = [
    'items'  => ['tabella' => 'codtipooggetto',  'usato_da' => 'oggetto',  'titolo' => 'Tipi di oggetto',  'indietro' => 'main.php?page=gestione_oggetti'],
    'guilds' => ['tabella' => 'codtipogilda',    'usato_da' => 'gilda',    'titolo' => 'Tipi di gilda',    'indietro' => 'main.php?page=gestione_gilde'],
    'jobs'   => ['tabella' => 'codtipomestiere', 'usato_da' => 'mestiere', 'titolo' => 'Tipi di mestiere', 'indietro' => 'main.php?page=gestione_mestieri'],
][$types];

if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
} else {
    $tabella = $config['tabella'];
    $tipi = gdrcd_query("SELECT cod_tipo, descrizione FROM $tabella ORDER BY descrizione ASC", 'result');
?>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">

    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <div class="gp-topbar__center">
        <input type="text" id="searchTipo" class="gp-search" placeholder="Cerca <?= mb_strtolower(gdrcd_filter('out', $config['titolo'])) ?>…">
    </div>

    <div class="gp-topbar__right">
        <button type="button" class="btn btn--primary btn-sm" title="Nuovo tipo" onclick="apriModaleCreazioneTipo()">
            <i class="fa-solid fa-plus"></i>&nbsp; Nuovo tipo
        </button>
    </div>

</div>

<h2 class="gp-title"><?= gdrcd_filter('out', $config['titolo']) ?></h2>

<!-- ── Elenco tipi ────────────────────────────────────────────── -->
<div class="gp-list">
    <table id="tipiTable" class="gp-table--tipi" data-types="<?= gdrcd_filter('out', $types) ?>">
        <thead>
            <tr>
                <th class="gp-th-name">Nome</th>
                <th>Codice</th>
                <th class="gp-th-actions">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (gdrcd_query($tipi, 'num_rows') > 0): ?>
            <?php while ($tipo = gdrcd_query($tipi, 'fetch')): ?>
            <tr>
                <td class="gp-cell--name"><?= gdrcd_filter('out', $tipo['descrizione']) ?></td>
                <td><?= (int)$tipo['cod_tipo'] ?></td>
                <td class="gp-cell--actions">
                    <div class="gp-actions">
                        <button type="button" class="btn-action btn-action--edit btn-action--icon"
                                title="Modifica"
                                onclick="apriModaleModificaTipo(<?= (int)$tipo['cod_tipo'] ?>, '<?= addslashes($tipo['descrizione']) ?>')">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button type="button" class="btn-action btn-action--delete btn-action--icon"
                                title="Elimina"
                                onclick="eliminaTipo(<?= (int)$tipo['cod_tipo'] ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="3" class="gp-empty">Nessun tipo creato finora.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="link_back">
    <a href="<?= gdrcd_filter('out', $config['indietro']) ?>">← Torna indietro</a>
</div>

<!-- ── Modale crea/modifica tipo ──────────────────────────────── -->
<div class="pg-edit-container" id="tipoModal" role="dialog" aria-modal="true">
    <div class="modal-content">

        <div class="gp-modal-header">
            <h2 class="gp-modal-title" id="tipoModalTitle">Nuovo tipo</h2>
            <div class="gp-modal-header-actions">
                <button type="button" class="gp-modal-close" id="closeTipoModal" aria-label="Chiudi">✕</button>
            </div>
        </div>

        <form id="tipoForm">
            <input type="hidden" name="types" value="<?= gdrcd_filter('out', $types) ?>">
            <input type="hidden" name="cod_tipo" id="tipo_cod">

            <div class="form-section">
                <div class="form-group">
                    <label for="tipo_nome">Nome</label>
                    <input type="text" name="nome" id="tipo_nome" required>
                </div>
            </div>
        </form>

        <div class="gp-modal-footer">
            <button type="button" class="btn btn--ghost" onclick="document.getElementById('tipoModal').style.display = 'none';">Annulla</button>
            <button type="submit" form="tipoForm" class="btn-action btn-action--edit">
                <i class="fa-solid fa-floppy-disk"></i> Salva
            </button>
        </div>

    </div>
</div>

<?php } ?>
