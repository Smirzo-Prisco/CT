<?php
require_once(__DIR__ . '../../includes/custom_functions.inc.php');

add_script("/includes/abilita_master.js");

// Stesso gate dell'intera pagina originale: chi la vede puo' anche creare,
// modificare, cancellare e assegnare — nessuna azione ha un permesso piu'
// stretto delle altre, qui non serve distinguerle come in gestione_personaggio.
$permessi_azioni = ['gestisci' => ['admin', 'master']];

if (!hasPermesso($_SESSION, $permessi_azioni['gestisci'])) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
} elseif ($PARAMETERS['mode']['skillsystem'] == 'OFF') {
    echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['unactive']) . '</div>';
} else {
    $skills = gdrcd_query("SELECT id_abilita, nome, descrizione FROM abilita WHERE tipo = 'Skill temporanea' ORDER BY nome ASC", 'result');
    $personaggi = gdrcd_query("SELECT nome FROM personaggio WHERE esperienza > 0 ORDER BY nome ASC", 'result');
?>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">

    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <div class="gp-topbar__center">
        <input type="text" id="searchAbilita" class="gp-search" placeholder="Cerca skill temporanea…">
    </div>

    <div class="gp-topbar__right">
        <button type="button" class="btn btn--primary btn-sm" title="Nuova skill temporanea" onclick="apriModaleCreazioneAbilita()">
            <i class="fa-solid fa-plus"></i>&nbsp; Nuova skill
        </button>
    </div>

</div>

<!-- ── Legenda icone azioni ──────────────────────────────────── -->
<div class="gp-legend">
    <div class="gp-legend__item">
        <span class="btn-action btn-action--edit btn-action--icon"><i class="fa-solid fa-pen-to-square"></i></span>
        <span class="gp-legend__text"><strong>Modifica</strong> — cambia nome e descrizione della skill</span>
    </div>
    <div class="gp-legend__item">
        <span class="btn-action btn-action--members btn-action--icon"><i class="fa-solid fa-user-plus"></i></span>
        <span class="gp-legend__text"><strong>Assegna a personaggio</strong> — la concede a un pg per un numero di usi limitato</span>
    </div>
    <div class="gp-legend__item">
        <span class="btn-action btn-action--delete btn-action--icon"><i class="fa-solid fa-trash"></i></span>
        <span class="gp-legend__text"><strong>Elimina</strong> — cancellazione definitiva <span class="gp-legend__confirm">richiede conferma</span></span>
    </div>
</div>

<!-- ── Elenco skill temporanee ────────────────────────────────── -->
<div class="gp-list">
    <table id="abilitaTable" class="gp-table--abilita">
        <thead>
            <tr>
                <th class="gp-th-name">Nome</th>
                <th>Descrizione</th>
                <th class="gp-th-actions">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (gdrcd_query($skills, 'num_rows') > 0): ?>
            <?php while ($skill = gdrcd_query($skills, 'fetch')): ?>
            <tr>
                <td class="gp-cell--name"><?= gdrcd_filter('out', $skill['nome']) ?></td>
                <td><?= mb_substr(gdrcd_filter('out', $skill['descrizione']), 0, 140, 'UTF-8') ?></td>
                <td class="gp-cell--actions">
                    <div class="gp-actions">
                        <button type="button" class="btn-action btn-action--edit btn-action--icon"
                                title="Modifica"
                                onclick="apriModaleModificaAbilita(<?= (int)$skill['id_abilita'] ?>)">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button type="button" class="btn-action btn-action--members btn-action--icon"
                                title="Assegna a personaggio"
                                onclick="apriModaleAssegnaAbilita(<?= (int)$skill['id_abilita'] ?>, '<?= addslashes($skill['nome']) ?>')">
                            <i class="fa-solid fa-user-plus"></i>
                        </button>
                        <button type="button" class="btn-action btn-action--delete btn-action--icon"
                                title="Elimina"
                                onclick="eliminaAbilita(<?= (int)$skill['id_abilita'] ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="3" class="gp-empty">Nessuna skill temporanea creata finora.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── Modale crea/modifica skill ─────────────────────────────── -->
<div class="pg-edit-container" id="abilitaModal" role="dialog" aria-modal="true">
    <div class="modal-content">

        <div class="gp-modal-header">
            <h2 class="gp-modal-title" id="abilitaModalTitle">
                <i class="fa-solid fa-wand-sparkles"></i>
                Nuova skill temporanea
            </h2>
            <div class="gp-modal-header-actions">
                <button type="button" class="gp-modal-close" id="closeAbilitaModal" aria-label="Chiudi">✕</button>
            </div>
        </div>

        <form id="abilitaForm">
            <input type="hidden" name="id_abilita" id="abilita_id">

            <div class="form-section">
                <div class="form-group">
                    <label for="abilita_nome">Nome</label>
                    <input type="text" name="nome" id="abilita_nome" required>
                </div>
                <div class="form-group">
                    <label for="abilita_descrizione">Descrizione</label>
                    <textarea name="descrizione" id="abilita_descrizione" rows="4" required></textarea>
                </div>
            </div>
        </form>

        <div class="gp-modal-footer">
            <button type="button" class="btn btn--ghost" onclick="document.getElementById('abilitaModal').style.display = 'none';">Annulla</button>
            <button type="submit" form="abilitaForm" class="btn-action btn-action--edit">
                <i class="fa-solid fa-floppy-disk"></i> Salva
            </button>
        </div>

    </div>
</div>

<!-- ── Modale assegna a personaggio ───────────────────────────── -->
<div class="pg-edit-container" id="assegnaModal" role="dialog" aria-modal="true">
    <div class="modal-content">

        <div class="gp-modal-header">
            <h2 class="gp-modal-title">
                <i class="fa-solid fa-user-plus"></i>
                Assegna — <span id="assegnaModalSkill">…</span>
            </h2>
            <div class="gp-modal-header-actions">
                <button type="button" class="gp-modal-close" id="closeAssegnaModal" aria-label="Chiudi">✕</button>
            </div>
        </div>

        <form id="assegnaForm">
            <input type="hidden" name="id_abilita" id="assegna_id_abilita">

            <div class="form-section">
                <div class="form-group">
                    <label for="assegna_personaggio">Personaggio</label>
                    <select name="personaggio" id="assegna_personaggio" required>
                        <?php while ($pg = gdrcd_query($personaggi, 'fetch')): ?>
                        <option value="<?= gdrcd_filter('out', $pg['nome']) ?>"><?= gdrcd_filter('out', $pg['nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assegna_usi">Numero usi</label>
                    <select name="usi" id="assegna_usi">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="gp-modal-footer">
            <button type="button" class="btn btn--ghost" onclick="document.getElementById('assegnaModal').style.display = 'none';">Annulla</button>
            <button type="submit" form="assegnaForm" class="btn-action btn-action--members">
                <i class="fa-solid fa-user-plus"></i> Assegna
            </button>
        </div>

    </div>
</div>

<?php } ?>
