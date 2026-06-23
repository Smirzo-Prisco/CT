<div class="pagina_gestione_gilde">
<?php
if ($_SESSION['admin'] != 1) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
} else {

// ── Elaborazione POST ──────────────────────────────────────────────────────────

// Inserimento nuovo ruolo
if (gdrcd_filter('get', $_POST['op']) == 'nuovo_ruolo') {
    $is_capo = (isset($_POST['capo']) && $_POST['capo'] == 'is_capo') ? 1 : 0;
    $immagine_oggetto = ($_FILES['img_oggetto']['name'] == '') ? 'standard_gilda.png' : $_FILES['img_oggetto']['name'];
    gdrcd_query("INSERT INTO ruolo_mestiere (nome_ruolo, mestiere, immagine, stipendio, capo, livello_mestiere) VALUES ('" . gdrcd_filter('in', $_POST['nome']) . "', " . gdrcd_filter('num', $_POST['mestiere']) . ", '" . gdrcd_filter('in', $immagine_oggetto) . "', '" . gdrcd_filter('num', $_POST['stipendio']) . "', '" . $is_capo . "', '" . gdrcd_filter('num', $_POST['livello_mestiere']) . "')");
    upload_image_mestieri();
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['inserted']);
    $feedback_back = 'main.php?page=gestione_mestieri&op=edit&id_record=' . gdrcd_filter('num', $_POST['mestiere']);
}

// Inserimento nuovo mestiere
if (gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['submit']['insert']) {
    $is_visible = (isset($_POST['visible']) && $_POST['visible'] == 'is_visible') ? 1 : 0;
    $immagine_oggetto = ($_FILES['img_oggetto']['name'] == '') ? 'standard_gilda.png' : $_FILES['img_oggetto']['name'];
    gdrcd_query("INSERT INTO mestiere (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('" . gdrcd_filter('in', $_POST['nome']) . "', " . gdrcd_filter('in', $_POST['tipo']) . ", '" . gdrcd_filter('in', $immagine_oggetto) . "', '" . gdrcd_filter('in', $_POST['url_sito']) . "', '" . $is_visible . "', '" . gdrcd_filter('in', $_POST['statuto']) . "')");
    upload_image_mestieri();
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['inserted']);
    $feedback_back = 'main.php?page=gestione_mestieri';
}

// Eliminazione mestiere — nasconde il record (visibile=0), non cancella
if (gdrcd_filter('get', $_POST['op']) == 'erase') {
    gdrcd_query("UPDATE mestiere SET visibile=0 WHERE id_mestiere=" . gdrcd_filter('num', $_POST['id_record']) . " LIMIT 1");
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['modified']);
    $feedback_back = 'main.php?page=gestione_mestieri';
}

// Eliminazione ruolo
if (gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['role']['submit']['delete'] && ($_POST['provenienza'] ?? '') == 'ruolo') {
    gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=" . gdrcd_filter('num', $_POST['id_ruolo']));
    gdrcd_query("DELETE FROM ruolo_mestiere WHERE id_ruolo=" . gdrcd_filter('num', $_POST['id_ruolo']) . " LIMIT 1");
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['deleted']);
    $feedback_back = 'main.php?page=gestione_mestieri&op=edit&id_record=' . gdrcd_filter('num', $_POST['mestiere']);
}

// Modifica mestiere
if (gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['submit']['edit'] && !isset($_POST['provenienza'])) {
    $is_visible = (isset($_POST['visible']) && $_POST['visible'] == 'is_visible') ? 1 : 0;
    $immagine   = ($_POST['immagine'] == '') ? 'standard_mestiere.png' : gdrcd_filter('in', $_POST['immagine']);
    gdrcd_query("UPDATE mestiere SET nome='" . gdrcd_filter('in', $_POST['nome']) . "', visibile=" . $is_visible . ", immagine='" . $immagine . "', tipo=" . gdrcd_filter('in', $_POST['tipo']) . ", url_sito='" . gdrcd_filter('in', $_POST['url_sito']) . "', statuto='" . gdrcd_filter('in', $_POST['statuto']) . "' WHERE id_mestiere=" . gdrcd_filter('num', $_POST['id_record']) . " LIMIT 1");
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['modified']);
    $feedback_back = 'main.php?page=gestione_mestieri';
}

// Modifica ruolo
if (gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['jobs']['role']['submit']['edit'] && ($_POST['provenienza'] ?? '') == 'ruolo') {
    $is_capo = (isset($_POST['capo']) && $_POST['capo'] == 'is_capo') ? 1 : 0;
    if ($_FILES['img_oggetto']['name'] == '') {
        gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo='" . gdrcd_filter('in', $_POST['nome']) . "', capo=" . $is_capo . ", mestiere=" . gdrcd_filter('num', $_POST['mestiere']) . ", livello_mestiere=" . gdrcd_filter('num', $_POST['livello_mestiere']) . ", stipendio=" . gdrcd_filter('num', $_POST['stipendio']) . " WHERE id_ruolo=" . gdrcd_filter('num', $_POST['id_ruolo']) . " LIMIT 1");
    } else {
        $immagine_oggetto = $_FILES['img_oggetto']['name'];
        gdrcd_query("UPDATE ruolo_mestiere SET nome_ruolo='" . gdrcd_filter('in', $_POST['nome']) . "', capo=" . $is_capo . ", immagine='" . gdrcd_filter('in', $immagine_oggetto) . "', mestiere=" . gdrcd_filter('num', $_POST['mestiere']) . ", livello_mestiere=" . gdrcd_filter('num', $_POST['livello_mestiere']) . ", stipendio=" . gdrcd_filter('num', $_POST['stipendio']) . " WHERE id_ruolo=" . gdrcd_filter('num', $_POST['id_ruolo']) . " LIMIT 1");
        upload_image_mestieri();
    }
    $feedback_msg  = gdrcd_filter('out', $MESSAGE['warning']['modified']);
    $feedback_back = 'main.php?page=gestione_mestieri&op=edit&id_record=' . $_POST['mestiere'];
}

// ── Feedback post-operazione ───────────────────────────────────────────────────

if (isset($feedback_msg)): ?>
<div class="guild-container">
    <div class="topbar">
        <a href="<?= $feedback_back ?>" class="back">⬅️ Indietro</a>
        <span style="font-weight:bold;">Gestione Mestieri</span>
    </div>
    <div style="padding:30px; text-align:center;">
        <p style="color:#4caf50; font-size:1.1em; margin-bottom:20px;"><?= $feedback_msg ?></p>
        <a href="<?= $feedback_back ?>" class="btn btn--primary">Continua</a>
    </div>
</div>

<?php
// Vista lista mestieri (default)
elseif (!isset($_POST['op']) && !isset($_REQUEST['op'])):
    $pagebegin    = (int)gdrcd_filter('get', $_REQUEST['offset']) * $PARAMETERS['settings']['records_per_page'];
    $pageend      = $PARAMETERS['settings']['records_per_page'];
    $totale       = gdrcd_query("SELECT COUNT(*) FROM mestiere")['COUNT(*)'];
    $result       = gdrcd_query("SELECT m.id_mestiere, m.nome, m.visibile, c.descrizione FROM mestiere m LEFT JOIN codtipomestiere c ON m.tipo = c.cod_tipo ORDER BY m.nome LIMIT $pagebegin, $pageend", 'result');
    $numresults   = gdrcd_query($result, 'num_rows');
?>
<div class="guild-container">
    <div class="topbar">
        <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
        <span style="font-weight:bold;">Gestione Mestieri</span>
        <div style="display:flex; gap:8px;">
            <a href="main.php?page=gestione_mestieri&op=new" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus"></i>&nbsp; Nuovo Mestiere</a>
            <a href="main.php?page=gestione_tipi&types=jobs" class="btn btn-sm btn--ghost">Tipi</a>
        </div>
    </div>

    <div class="item-list">
        <div class="ct-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']) ?></th>
                        <th><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['type']) ?></th>
                        <th><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible']) ?></th>
                        <th style="width:160px;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($numresults > 0): while ($row = gdrcd_query($result, 'fetch')): ?>
                    <tr>
                        <td><?= gdrcd_filter('out', $row['nome']) ?></td>
                        <td><?= gdrcd_filter('out', $row['descrizione']) ?></td>
                        <td><?= $row['visibile'] == 1 ? gdrcd_filter('out', $MESSAGE['interface']['administration']['yes']) : gdrcd_filter('out', $MESSAGE['interface']['administration']['no']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="main.php?page=gestione_mestieri&op=edit&id_record=<?= gdrcd_filter('out', $row['id_mestiere']) ?>"
                                   class="btn btn-sm btn-primary" title="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']) ?>">
                                    <i class="fa-solid fa-pencil"></i>
                                </a>
                                <form method="post" action="main.php?page=gestione_mestieri" style="display:inline;"
                                      onsubmit="return confirm('Nascondere il mestiere «<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>»?\nIl mestiere non sarà più visibile ma i dati verranno conservati.')">
                                    <input type="hidden" name="id_record" value="<?= gdrcd_filter('out', $row['id_mestiere']) ?>">
                                    <button type="submit" name="op" value="erase"
                                            class="btn btn-sm btn--danger" title="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; gdrcd_query($result, 'free');
                else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--color-text-muted); font-style:italic;">Nessun mestiere trovato.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totale > $PARAMETERS['settings']['records_per_page']): ?>
    <div class="pager" style="padding:12px; text-align:center;">
        <?= gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']) ?>
        <?php for ($i = 0; $i <= floor($totale / $PARAMETERS['settings']['records_per_page']); $i++): ?>
            <?php if ($i != gdrcd_filter('num', $_REQUEST['offset'])): ?>
                <a href="main.php?page=gestione_mestieri&offset=<?= $i ?>"><?= $i + 1 ?></a>
            <?php else: ?>
                <strong> <?= $i + 1 ?> </strong>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
// Vista form mestiere (new / edit) + sezione ruoli
elseif (in_array(gdrcd_filter('get', $_REQUEST['op'] ?? ''), ['new', 'edit'])):
    $operation     = 'insert';
    $loaded_record = [];

    if ($_REQUEST['op'] == 'edit' && gdrcd_filter('get', $_REQUEST['id_record']) > -1) {
        $loaded_record = gdrcd_query("SELECT * FROM mestiere WHERE id_mestiere=" . gdrcd_filter('get', $_REQUEST['id_record']) . " LIMIT 1");
        $operation = 'edit';
    }

    $id_mestiere_padre = gdrcd_filter('get', $_REQUEST['id_record'] ?? -1);
    $tipi = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipomestiere", 'result');
?>

<?php if (!isset($_REQUEST['id_record']) || gdrcd_filter('get', $_REQUEST['id_record']) > -1): ?>
<!-- ── Form mestiere ───────────────────────────────────────────────── -->
<div class="guild-container">
    <div class="topbar">
        <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
        <span style="font-weight:bold;"><?= $operation == 'edit' ? 'Modifica Mestiere' : 'Nuovo Mestiere' ?></span>
    </div>

    <div style="padding:24px; max-width:860px; margin:0 auto;">
        <form action="main.php?page=gestione_mestieri" method="post" enctype="multipart/form-data">
            <?php if ($operation == 'edit'): ?>
                <input type="hidden" name="id_record" value="<?= gdrcd_filter('out', $loaded_record['id_mestiere']) ?>">
            <?php endif; ?>

            <div style="display:flex; gap:20px; margin-bottom:16px; flex-wrap:wrap;">
                <!-- Colonna sinistra -->
                <div style="flex:1; min-width:260px; display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label style="display:block; margin-bottom:4px; font-weight:600;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['name']) ?></label>
                        <input name="nome" value="<?= gdrcd_filter('out', $loaded_record['nome'] ?? '') ?>" style="width:100%; box-sizing:border-box;" />
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:4px; font-weight:600;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['type']) ?></label>
                        <?php if (gdrcd_query($tipi, 'num_rows') > 0): ?>
                        <select name="tipo" style="width:100%; box-sizing:border-box;">
                            <?php while ($opt = gdrcd_query($tipi, 'fetch')): ?>
                                <option value="<?= $opt['cod_tipo'] ?>" <?= ($loaded_record['tipo'] ?? '') == $opt['cod_tipo'] ? 'selected' : '' ?>>
                                    <?= gdrcd_filter('out', $opt['descrizione']) ?>
                                </option>
                            <?php endwhile; gdrcd_query($tipi, 'free'); ?>
                        </select>
                        <?php else: echo gdrcd_filter('out', $MESSAGE['interface']['administration']['locations']['type_err']); endif; ?>
                        <a href="main.php?page=gestione_tipi&types=jobs" style="font-size:0.8em; margin-top:4px; display:inline-block;">Gestisci tipi</a>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:4px; font-weight:600;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['site']) ?></label>
                        <input name="url_sito" value="<?= gdrcd_filter('out', $loaded_record['url_sito'] ?? 'http://') ?>" style="width:100%; box-sizing:border-box;" />
                    </div>
                </div>
                <!-- Colonna destra -->
                <div style="flex:1; min-width:260px; display:flex; flex-direction:column; gap:14px;">
                    <div>
                        <label style="display:block; margin-bottom:4px; font-weight:600;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['image']) ?></label>
                        <input type="file" name="img_oggetto" style="width:100%; box-sizing:border-box;" />
                    </div>
                    <div>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600;">
                            <input type="checkbox" name="visible" value="is_visible"
                                   <?= ($loaded_record['visibile'] ?? 0) == 1 ? 'checked' : '' ?>
                                   style="width:auto; margin:0;" />
                            <?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible']) ?>
                        </label>
                        <small style="color:var(--color-text-muted); margin-top:4px; display:block;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['visible_info']) ?></small>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:4px; font-weight:600;">Statuto</label>
                <textarea name="statuto" style="width:100%; box-sizing:border-box; min-height:120px;"><?= gdrcd_filter('out', $loaded_record['statuto'] ?? '') ?></textarea>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <?php if ($operation == 'edit'): ?>
                    <button type="submit" name="op" value="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['edit']) ?>" class="btn btn--primary">
                        <i class="fa-solid fa-floppy-disk"></i>&nbsp; Salva modifiche
                    </button>
                <?php else: ?>
                    <button type="submit" name="op" value="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['submit']['insert']) ?>" class="btn btn--primary">
                        <i class="fa-solid fa-plus"></i>&nbsp; Crea mestiere
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($_REQUEST['op'] == 'edit' && isset($_REQUEST['id_record'])): ?>
<!-- ── Sezione ruoli ───────────────────────────────────────────────── -->
<?php
    $mestiere_info = gdrcd_query("SELECT nome FROM mestiere WHERE id_mestiere=" . gdrcd_filter('num', $id_mestiere_padre));
?>
<div class="guild-container" style="margin-top:20px;">
    <div class="topbar">
        <span style="font-weight:bold;">Ruoli — <?= gdrcd_filter('out', $mestiere_info['nome'] ?? '') ?></span>
        <small style="color:var(--color-text-muted);">Livello: 3 = più basso, 1 = più alto</small>
    </div>

    <!-- Form nuovo ruolo -->
    <div style="padding:16px 20px; border-bottom:1px solid #333;">
        <form action="main.php?page=gestione_mestieri" method="post" enctype="multipart/form-data">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:2; min-width:150px;">
                    <label style="display:block; margin-bottom:4px; font-weight:600;">Nome ruolo</label>
                    <input name="nome" placeholder="Nuovo ruolo..." style="width:100%; box-sizing:border-box;" />
                </div>
                <div style="min-width:70px;">
                    <label style="display:block; margin-bottom:4px; font-weight:600;">Livello</label>
                    <input name="livello_mestiere" value="3" type="number" min="1" max="3" style="width:70px;" />
                </div>
                <div style="min-width:90px;">
                    <label style="display:block; margin-bottom:4px; font-weight:600;">Stipendio</label>
                    <input name="stipendio" value="0" type="number" style="width:90px;" />
                </div>
                <div style="min-width:140px;">
                    <label style="display:block; margin-bottom:4px; font-weight:600;">Immagine</label>
                    <input type="file" name="img_oggetto" style="width:100%; box-sizing:border-box;" />
                </div>
                <div style="padding-bottom:10px;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600;">
                        <input type="checkbox" name="capo" value="is_capo" style="width:auto; margin:0;" />
                        <?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head']) ?>
                    </label>
                    <small style="color:var(--color-text-muted);"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head_info']) ?></small>
                </div>
                <div style="padding-bottom:10px;">
                    <input type="hidden" name="mestiere" value="<?= gdrcd_filter('out', $id_mestiere_padre) ?>" />
                    <input type="hidden" name="op" value="nuovo_ruolo" />
                    <button type="submit" class="btn btn--success">
                        <i class="fa-solid fa-plus"></i>&nbsp; Aggiungi ruolo
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Lista ruoli esistenti -->
    <?php
    $res_ruoli = gdrcd_query("SELECT * FROM ruolo_mestiere WHERE mestiere=" . gdrcd_filter('num', $id_mestiere_padre) . " ORDER BY capo DESC, stipendio DESC", 'result');
    $has_ruoli = false;
    while ($row = gdrcd_query($res_ruoli, 'fetch')):
        $has_ruoli = true;
    ?>
    <div class="guild-accordion">
        <form action="main.php?page=gestione_mestieri" method="post" enctype="multipart/form-data">
            <div class="guild-header" style="flex-wrap:wrap; gap:10px; align-items:center;">
                <!-- Immagine ruolo -->
                <img src="imgs/mestieri/<?= gdrcd_filter('out', $row['immagine']) ?>"
                     width="32" height="32" alt="" style="border-radius:4px; flex-shrink:0;"
                     onerror="this.style.display='none'" />

                <!-- Campi editabili inline -->
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; flex:1;">
                    <div>
                        <label style="display:block; font-size:0.75em; color:var(--color-text-muted); margin-bottom:2px;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['name']) ?></label>
                        <input name="nome" value="<?= gdrcd_filter('out', $row['nome_ruolo']) ?>" style="width:160px;" />
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75em; color:var(--color-text-muted); margin-bottom:2px;">Livello (1-3)</label>
                        <input name="livello_mestiere" value="<?= (int)gdrcd_filter('out', $row['livello_mestiere']) ?>" type="number" min="1" max="3" style="width:60px;" />
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75em; color:var(--color-text-muted); margin-bottom:2px;"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['pay']) ?></label>
                        <input name="stipendio" value="<?= (int)gdrcd_filter('out', $row['stipendio']) ?>" type="number" style="width:80px;" />
                    </div>
                    <div>
                        <label style="display:block; font-size:0.75em; color:var(--color-text-muted); margin-bottom:2px;">Nuova immagine</label>
                        <input type="file" name="img_oggetto" style="width:150px; margin:0;" />
                    </div>
                    <div style="padding-top:18px;">
                        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" name="capo" value="is_capo"
                                   <?= $row['capo'] == 1 ? 'checked' : '' ?> style="width:auto; margin:0;" />
                            <span style="font-size:0.85em; color:var(--color-text-muted);"><?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['head']) ?></span>
                        </label>
                    </div>
                </div>

                <!-- Azioni ruolo -->
                <div class="guild-actions" style="flex-shrink:0;">
                    <input type="hidden" name="provenienza" value="ruolo" />
                    <input type="hidden" name="id_ruolo"   value="<?= gdrcd_filter('out', $row['id_ruolo']) ?>" />
                    <input type="hidden" name="mestiere"   value="<?= gdrcd_filter('out', $id_mestiere_padre) ?>" />
                    <button type="submit"
                            name="op"
                            value="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['submit']['edit']) ?>"
                            class="btn btn-sm btn-primary"
                            title="Salva modifiche ruolo">
                        <i class="fa-solid fa-floppy-disk"></i>&nbsp; Salva
                    </button>
                    <button type="submit"
                            name="op"
                            value="<?= gdrcd_filter('out', $MESSAGE['interface']['administration']['jobs']['role']['submit']['delete']) ?>"
                            class="btn btn-sm btn--danger"
                            title="Elimina ruolo"
                            onclick="return confirm('Eliminare il ruolo «<?= htmlspecialchars($row['nome_ruolo'], ENT_QUOTES) ?>»?\nTutti i personaggi con questo ruolo ne verranno rimossi.')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endwhile;
    gdrcd_query($res_ruoli, 'free');
    if (!$has_ruoli): ?>
    <div style="padding:20px; text-align:center; color:var(--color-text-muted); font-style:italic;">Nessun ruolo definito per questo mestiere.</div>
    <?php endif; ?>

</div><!-- guild-container ruoli -->
<?php endif; ?>

<?php endif; // fine viste ?>

<?php
} // fine controllo admin

function upload_image_mestieri() {
    $allow = ['jpg', 'jpeg', 'gif', 'png'];
    $todir = 'imgs/mestieri/';
    if (!empty($_FILES['img_oggetto']['tmp_name'])) {
        $info = explode('.', strtolower($_FILES['img_oggetto']['name']));
        if (in_array(end($info), $allow)) {
            move_uploaded_file($_FILES['img_oggetto']['tmp_name'], $todir . basename($_FILES['img_oggetto']['name']));
        }
    }
}
?>
</div><!-- pagina_gestione_gilde -->
