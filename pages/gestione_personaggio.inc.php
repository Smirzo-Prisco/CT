<?php
require_once(__DIR__ . '../../includes/custom_functions.inc.php');

add_script("/includes/personaggio.js");

$permessi_azioni = [
    'modifica'   => ['admin', 'moderatore', 'master', 'custode'],
    'skill'      => ['admin'],
    'cancella'   => ['admin'],
    'esilia'     => ['admin', 'moderatore'],
    'reset'      => ['admin'],
    'ripristina' => ['admin', 'moderatore'],
];

$permessi_sezioni = [
    'area_admin'   => ['admin'],
    'anagrafica'   => ['admin', 'moderatore'],
    'area_master'  => ['admin', 'master'],
    'area_custodi' => ['admin', 'master', 'custode']
];

$filtro = $_POST['filtro'] ?? 'tutti';

switch ($filtro) {
    case 'inattivi':    $where = "AND DATE_ADD(ora_entrata, INTERVAL 150 DAY) <= NOW() AND (esilio < NOW() OR esilio IS NULL)"; break;
    case 'mai_entrati': $where = "AND esperienza = '0.0000' AND (DATE(ora_entrata) = DATE(data_iscrizione))"; break;
    case 'esiliati':    $where = "AND (esilio > NOW() AND esilio != '0000-00-00' AND esilio IS NOT NULL)"; break;
    case 'eliminati':   $where = "AND " . sqlPgCancellato(); break;
    default:            $where = '';
}

$personaggi = gdrcd_query(
    "SELECT personaggio.*, razza.nome_razza, mestiere.id_mestiere, mestiere.nome AS nome_mestiere,
            gilda.nome AS nome_gilda
     FROM personaggio
     LEFT JOIN razza    ON personaggio.id_razza         = razza.id_razza
     LEFT JOIN mestiere ON personaggio.id_mestiere      = mestiere.id_mestiere
     LEFT JOIN gilda    ON personaggio.id_gilda         = gilda.id_gilda
     WHERE 1=1 $where
     ORDER BY nome ASC",
    'result'
);

$listaRazze = gdrcd_query("SELECT * FROM razza ORDER BY nome_razza", 'result');

$beast = gdrcd_query("SELECT * FROM personaggio WHERE nome='" . $_SESSION['login'] . "'");

if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $_SESSION['moderatore'] != 1 && $beast['id_gilda'] != 4) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
} else {
?>

<!-- ── Topbar ─────────────────────────────────────────────────── -->
<div class="gp-topbar">

    <div class="gp-topbar__left">
        <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>

    <div class="gp-topbar__center">
        <form method="post" action="main.php?page=gestione_personaggio" class="gp-filter-form">
            <select name="filtro" id="selectFiltroPg" onchange="this.form.submit()">
                <option value="tutti"       <?= $filtro === 'tutti'       ? 'selected' : '' ?>>Tutti</option>
                <option value="inattivi"    <?= $filtro === 'inattivi'    ? 'selected' : '' ?>>Inattivi (5 mesi)</option>
                <option value="mai_entrati" <?= $filtro === 'mai_entrati' ? 'selected' : '' ?>>Mai entrati</option>
                <option value="esiliati"    <?= $filtro === 'esiliati'    ? 'selected' : '' ?>>Esiliati</option>
                <option value="eliminati"   <?= $filtro === 'eliminati'   ? 'selected' : '' ?>>Eliminati</option>
            </select>
        </form>
        <input type="text" id="searchPg" class="gp-search" placeholder="Cerca personaggio…">
    </div>

    <div class="gp-topbar__right">
        <?php if (hasPermesso($_SESSION, $permessi_azioni['reset'])): ?>
        <button class="btn-action btn-action--reset btn-action--icon" title="Reset punti di tutti i personaggi" onclick="resetPg([])">
            <i class="fa-solid fa-rotate"></i>
        </button>
        <?php endif; ?>
        <?php if (hasPermesso($_SESSION, $permessi_azioni['cancella'])): ?>
        <button class="btn-action btn-action--delete btn-action--icon" title="Elimina definitivamente tutti gli esiliati" onclick="eliminaEsiliati()">
            <i class="fa-solid fa-trash"></i>
        </button>
        <?php endif; ?>
    </div>

</div>

<!-- ── Lista personaggi ──────────────────────────────────────── -->
<div class="gp-list">
    <table id="pgTable">
        <thead>
            <tr>
                <th></th>
                <th>Personaggio</th>
                <?php /* <th>Spirito</th> */ ?>
                <th>Razza</th>
                <th>Mestiere</th>
                <th>Esilio</th>
                <th class="gp-th-actions">Azioni</th>
            </tr>
        </thead>
            <tbody>
                <?php foreach ($personaggi as $pg):
                    $isExiled = !empty($pg['esilio'])
                        && $pg['esilio'] !== '0000-00-00 00:00:00'
                        && $pg['esilio'] > date('Y-m-d H:i:s');
                    $isDeleted = (int)$pg['permessi'] === -1;
                ?>
                <tr class="<?= $isExiled ? 'gp-row--exiled' : '' ?>">

                    <!-- Avatar -->
                    <td class="gp-cell--avatar">
                        <?php if (!empty($pg['url_img_chat'])): ?>
                        <img src="<?= htmlspecialchars($pg['url_img_chat']) ?>" alt="" width="32" height="32">
                        <?php else: ?>
                        <span class="gp-avatar-placeholder"><i class="fa-solid fa-user"></i></span>
                        <?php endif; ?>
                    </td>

                    <!-- Nome -->
                    <td class="gp-cell--name">
                        <a href="main.php?page=scheda&pg=<?= htmlspecialchars($pg['nome']) ?>">
                            <?= htmlspecialchars($pg['nome']) ?>
                        </a>
                        <?php if (!empty($pg['cognome'])): ?>
                        <span class="gp-cognome"><?= htmlspecialchars($pg['cognome']) ?></span>
                        <?php endif; ?>
                    </td>

                    <?php /* Spirito (ex Razza) — rimossa dalla visualizzazione:
                    <td class="gp-cell--meta"><?= htmlspecialchars($pg['nome_razza'] ?? '—') ?></td>
                    */ ?>

                    <!-- Razza (ex Gilda) -->
                    <td class="gp-cell--meta"><?= htmlspecialchars($pg['nome_gilda'] ?? '—') ?></td>

                    <!-- Mestiere -->
                    <td class="gp-cell--meta"><?= htmlspecialchars($pg['nome_mestiere'] ?? '—') ?></td>

                    <!-- Esilio -->
                    <td class="gp-cell--esilio">
                        <?php if ($isExiled): ?>
                        <span class="gp-badge gp-badge--exile">
                            <i class="fa-solid fa-ban"></i>
                            <?= gdrcd_format_date($pg['esilio']) ?>
                        </span>
                        <?php endif; ?>
                    </td>

                    <!-- Azioni -->
                    <td class="gp-cell--actions">
                        <div class="gp-actions">

                            <?php if ($isDeleted && hasPermesso($_SESSION, $permessi_azioni['ripristina'])): ?>
                            <button class="btn-action btn-action--restore btn-action--icon"
                                    title="Ripristina"
                                    onclick="ripristinaPg('<?= addslashes($pg['nome']) ?>')">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (hasPermesso($_SESSION, $permessi_azioni['modifica'])): ?>
                            <button class="btn-action btn-action--edit btn-action--icon"
                                    title="Modifica"
                                    onclick="popolaPgForm('<?= addslashes($pg['nome']) ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (hasPermesso($_SESSION, $permessi_azioni['skill'])): ?>
                            <form action="main.php?page=gst_personaggio_skill" method="POST">
                                <input type="hidden" name="load_item" value="<?= $pg['nome'] ?>">
                                <button type="submit" class="btn-action btn-action--skill btn-action--icon" title="Gestione skill">
                                    <i class="fa-solid fa-wrench"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if (hasPermesso($_SESSION, $permessi_azioni['esilia']) && $_SESSION['login'] !== $pg['nome']): ?>
                            <form action="main.php?page=gestione_esilio" method="POST">
                                <input type="hidden" name="load_pg" value="<?= $pg['nome'] ?>">
                                <button type="submit" class="btn-action btn-action--exile btn-action--icon"
                                        title="Esilia"
                                        onclick="return confirm('Esiliare <?= addslashes($pg['nome']) ?>?')">
                                    <i class="fa-solid fa-user-slash"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if (hasPermesso($_SESSION, $permessi_azioni['reset'])): ?>
                            <button class="btn-action btn-action--reset btn-action--icon"
                                    title="Reset punti"
                                    onclick='resetPg([{ nome: "<?= addslashes($pg['nome']) ?>" }])'>
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (hasPermesso($_SESSION, $permessi_azioni['cancella']) && $_SESSION['login'] !== $pg['nome']): ?>
                            <form action="main.php?page=erasepg_scelta" method="POST">
                                <input type="hidden" name="op"  value="delete">
                                <input type="hidden" name="pg"  value="<?= $pg['nome'] ?>">
                                <button type="submit" class="btn-action btn-action--delete btn-action--icon"
                                        title="Elimina definitivamente"
                                        onclick="return confirm('Eliminare definitivamente <?= addslashes($pg['nome']) ?>? Operazione irreversibile.')">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>

                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
</div>

<!-- ── Modale modifica personaggio ──────────────────────────── -->
<div class="pg-edit-container" id="pg_edit_container" role="dialog" aria-modal="true">
    <div class="modal-content">

        <div class="gp-modal-header">
            <h2 class="gp-modal-title">
                <i class="fa-solid fa-pen-to-square"></i>
                Modifica — <span id="gp-modal-pg-name">…</span>
            </h2>
            <div class="gp-modal-header-actions">
                <button type="button" class="gp-scroll-btn" title="Vai in cima" onclick="gpScrollForm('top')">▲</button>
                <button type="button" class="gp-scroll-btn" title="Vai in fondo" onclick="gpScrollForm('bottom')">▼</button>
                <button type="button" class="gp-modal-close" id="closePgModal" aria-label="Chiudi">✕</button>
            </div>
        </div>

        <form id="formSavePg" method="post">

            <!-- ── Anagrafica ─────────────────────────────────── -->
            <?php if (hasPermesso($_SESSION, $permessi_sezioni['anagrafica'])): ?>
            <div class="form-section">
                <h3 class="section-title">Anagrafica</h3>

                <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_admin'])): ?>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group form-column">
                        <label for="razza">Razza</label>
                        <select id="razza" name="razza">
                            <option value="0">Nessuna</option>
                            <?php foreach ($listaRazze as $r): ?>
                            <option value="<?= $r['id_razza'] ?>"><?= htmlspecialchars($r['nome_razza']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="eta">Età</label>
                        <input type="number" id="eta" name="eta">
                    </div>
                    <div class="form-group form-column">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email">
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome">
                    </div>
                    <div class="form-group form-column">
                        <label for="luogo">Luogo di nascita</label>
                        <input type="text" id="luogo" name="luogo">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="volto">Volto personaggio</label>
                        <div class="input-with-button">
                            <input type="text" id="volto" name="volto">
                            <button type="button" onclick="checkVolto()">Controlla</button>
                        </div>
                    </div>
                    <div class="form-group form-column gp-checkbox-field">
                        <label for="suoni">Disabilita suoni</label>
                        <input type="checkbox" id="suoni" name="suoni" value="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="musica">Musica scheda (URL)</label>
                        <input type="url" id="musica" name="musica">
                    </div>
                    <div class="form-group form-column">
                        <label for="alias">Alias Tokyobook</label>
                        <input type="text" id="alias" name="alias">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="img">URL immagine profilo</label>
                        <input type="url" id="img" name="img">
                    </div>
                    <div class="form-group form-column">
                        <label for="imgchat">URL immagine chat</label>
                        <input type="url" id="imgchat" name="imgchat">
                    </div>
                </div>
                <div class="form-group">
                    <label for="background">Background</label>
                    <textarea id="background" name="background"></textarea>
                </div>
                <div class="form-group">
                    <label for="storia">Storia</label>
                    <textarea id="storia" name="storia"></textarea>
                </div>
                <div class="form-group">
                    <label for="dice">Dice di sé</label>
                    <textarea id="dice" name="dice"></textarea>
                </div>
                <div class="form-group">
                    <label for="off">Off</label>
                    <textarea id="off" name="off"></textarea>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Caratteristiche ────────────────────────────── -->
            <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_custodi']) || hasPermesso($_SESSION, $permessi_sezioni['area_master'])): ?>
            <div class="form-section">
                <h3 class="section-title">Caratteristiche</h3>

                <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_custodi'])): ?>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="salute">Salute <span class="gp-label-note" id="salute-max-note"></span></label>
                        <input type="number" id="salute" name="salute" min="0">
                    </div>
                    <div class="form-group form-column">
                        <label for="integrita">Integrità <span class="gp-label-note" id="integrita-max-note"></span></label>
                        <input type="number" id="integrita" name="integrita" min="0">
                    </div>
                </div>
                <?php endif; ?>

                <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_master'])): ?>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="shin">Punti shin</label>
                        <input type="number" id="shin" name="shin" disabled>
                    </div>
                    <div class="form-group form-column">
                        <label for="soldi">Soldi</label>
                        <input type="number" id="soldi" name="soldi">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="notorieta">Notorietà</label>
                        <input type="number" id="notorieta" name="notorieta">
                    </div>
                    <div class="form-group form-column">
                        <label for="mestiere_punti">Punti Mestiere</label>
                        <input type="number" id="mestiere_punti" name="mestiere_punti" min="1" max="100">
                    </div>
                </div>
                <div class="form-group">
                    <label for="note_master">Note del Master</label>
                    <textarea id="note_master" name="note_master"></textarea>
                </div>
                <div class="form-group">
                    <label for="particolari">Particolari</label>
                    <textarea id="particolari" name="particolari"></textarea>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <input type="hidden" name="personaggio" id="personaggio">

        </form>

        <!-- Footer fuori dal form: flex child di .modal-content, sempre visibile -->
        <div class="gp-modal-footer">
            <button type="submit" id="gp-save-btn" form="formSavePg" class="btn-action btn-action--edit">
                <i class="fa-solid fa-floppy-disk"></i> Salva modifiche
            </button>
        </div>

    </div>
</div>

<?php } ?>
