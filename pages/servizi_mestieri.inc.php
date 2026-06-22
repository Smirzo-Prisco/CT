
<?php
/* ─────────────────────────────────────────────────────────────────────────────
   servizi_mestieri.inc.php — Elenco e dettaglio mestieri
   ───────────────────────────────────────────────────────────────────────────── */

$id_mestiere = isset($_REQUEST['id_mestiere']) ? (int)gdrcd_filter('num', $_REQUEST['id_mestiere']) : null;
?>
<div class="sm-page">

<?php if ($id_mestiere === null): /* ── VISTA ELENCO ─────────────────────────── */ ?>

    <div class="sm-header sm-header--list">
        <h2 class="sm-title">
            <i class="fas fa-briefcase"></i>
            <?= gdrcd_filter('out', $PARAMETERS['names']['job_name']['plur']) ?>
        </h2>
    </div>

    <?php
    $result = gdrcd_query(
        "SELECT m.nome, m.id_mestiere, m.tipo, m.url_sito, c.descrizione
           FROM mestiere m
           JOIN codtipomestiere c ON m.tipo = c.cod_tipo
          WHERE m.visibile = 1
          ORDER BY m.tipo, m.nome",
        'result'
    );

    $sections  = [];
    $last_tipo = null;

    while ($row = gdrcd_query($result, 'fetch')) {
        $numb = gdrcd_query(
            "SELECT COUNT(*) AS n
               FROM clgpersonaggiomestiere cpm
               JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
              WHERE rm.mestiere = " . (int)$row['id_mestiere'] . "
                AND cpm.conferma_mestiere = 1"
        );
        if ($row['tipo'] !== $last_tipo) {
            $last_tipo  = $row['tipo'];
            $sections[] = ['desc' => $row['descrizione'], 'items' => []];
        }
        $sections[count($sections) - 1]['items'][] = [
            'id'       => (int)$row['id_mestiere'],
            'nome'     => $row['nome'],
            'url_sito' => $row['url_sito'],
            'n'        => (int)($numb['n'] ?? 0),
        ];
    }
    gdrcd_query($result, 'free');

    foreach ($sections as $section):
    ?>
    <section class="sm-section">
        <div class="sm-section-title"><?= gdrcd_filter('out', $section['desc']) ?></div>
        <div class="sm-list">
            <?php foreach ($section['items'] as $item): ?>
            <div class="sm-list-item">
                <a href="main.php?page=servizi_mestieri&id_mestiere=<?= $item['id'] ?>" class="sm-list-link">
                    <i class="fas fa-briefcase sm-list-icon"></i>
                    <span class="sm-list-name"><?= gdrcd_filter('out', $item['nome']) ?></span>
                </a>
                <span class="sm-list-count"><?= $item['n'] ?></span>
                <?php if (!empty($item['url_sito'])): ?>
                <a href="<?= gdrcd_filter('out', $item['url_sito']) ?>?id2=<?= $item['id'] ?>"
                   target="_blank" class="sm-list-statute" title="Statuto">
                    <i class="fas fa-scroll"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- Lavori Liberi -->
    <?php
    $rFree = gdrcd_query(
        "SELECT rm.id_ruolo, rm.nome_ruolo, COUNT(p.nome) AS n
           FROM ruolo_mestiere rm
           JOIN clgpersonaggiolavoro cpl ON rm.id_ruolo = cpl.id_ruolo
           JOIN personaggio p ON cpl.personaggio = p.nome
          WHERE rm.mestiere = -1
            AND p.esperienza > 9
          GROUP BY rm.id_ruolo, rm.nome_ruolo
          ORDER BY rm.nome_ruolo ASC",
        'result'
    );
    $freelance = [];
    while ($r = gdrcd_query($rFree, 'fetch')) $freelance[] = $r;
    gdrcd_query($rFree, 'free');

    if (!empty($freelance)):
    ?>
    <section class="sm-section">
        <div class="sm-section-title">Lavoro Libero</div>
        <div class="sm-list">
            <?php foreach ($freelance as $f): ?>
            <div class="sm-list-item">
                <a href="main.php?page=servizi_mestieri2&id_ruolo=<?= (int)$f['id_ruolo'] ?>" class="sm-list-link">
                    <i class="fas fa-user sm-list-icon"></i>
                    <span class="sm-list-name"><?= gdrcd_filter('out', $f['nome_ruolo']) ?></span>
                </a>
                <span class="sm-list-count"><?= (int)$f['n'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

<?php else: /* ── VISTA DETTAGLIO ─────────────────────────────────────────── */

    $mestiere_info = gdrcd_query("SELECT nome FROM mestiere WHERE id_mestiere = $id_mestiere");
    $mestiere_nome = $mestiere_info ? gdrcd_filter('out', $mestiere_info['nome']) : 'Mestiere';
?>

    <div class="sm-header">
        <a href="main.php?page=servizi_mestieri" class="sm-back">
            <i class="fas fa-arrow-left"></i>
            <?= gdrcd_filter('out', $PARAMETERS['names']['job_name']['plur']) ?>
        </a>
        <h2 class="sm-title"><?= $mestiere_nome ?></h2>
    </div>

    <!-- Gerarchia dei gradi, dal più alto al più basso -->
    <?php
    $rRuoli = gdrcd_query(
        "SELECT id_ruolo, nome_ruolo, immagine, stipendio, capo
           FROM ruolo_mestiere
          WHERE mestiere = $id_mestiere
          ORDER BY stipendio DESC",
        'result'
    );
    $ruoli = [];
    while ($r = gdrcd_query($rRuoli, 'fetch')) $ruoli[] = $r;
    gdrcd_query($rRuoli, 'free');

    if (!empty($ruoli)):
    ?>
    <section class="sm-section">
        <div class="sm-section-title">
            <i class="fas fa-layer-group"></i> Gerarchia
        </div>
        <div class="sm-rank-list">
            <?php foreach ($ruoli as $i => $ruolo): ?>
            <div class="sm-rank-item<?= $ruolo['capo'] ? ' sm-rank-item--top' : '' ?>">
                <span class="sm-rank-badge"><?= $i + 1 ?></span>
                <div class="sm-img-box">
                    <?php if (!empty($ruolo['immagine'])): ?>
                    <img src="imgs/mestieri/<?= gdrcd_filter('out', $ruolo['immagine']) ?>"
                         alt="<?= gdrcd_filter('out', $ruolo['nome_ruolo']) ?>">
                    <?php else: ?>
                    <i class="fas fa-medal"></i>
                    <?php endif; ?>
                </div>
                <span class="sm-rank-name"><?= gdrcd_filter('out', $ruolo['nome_ruolo']) ?></span>
                <?php if ($ruolo['capo']): ?>
                <i class="fas fa-crown sm-rank-crown"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Affiliati confermati, dal capo in giù per stipendio -->
    <?php
    $rAff = gdrcd_query(
        "SELECT cpm.personaggio, p.cognome, rm.immagine, rm.nome_ruolo, rm.capo
           FROM ruolo_mestiere rm
           JOIN clgpersonaggiomestiere cpm ON cpm.id_ruolo = rm.id_ruolo
           JOIN personaggio p ON p.nome = cpm.personaggio
          WHERE rm.mestiere = $id_mestiere
            AND cpm.conferma_mestiere = 1
          ORDER BY rm.capo DESC, rm.stipendio DESC",
        'result'
    );
    $affiliati = [];
    while ($r = gdrcd_query($rAff, 'fetch')) $affiliati[] = $r;
    gdrcd_query($rAff, 'free');

    if (!empty($affiliati)):
    ?>
    <section class="sm-section">
        <div class="sm-section-title">
            <i class="fas fa-users"></i>
            <?= gdrcd_filter('out', $PARAMETERS['names']['job_name']['members']) ?>
        </div>
        <div class="sm-member-list">
            <?php foreach ($affiliati as $aff): ?>
            <div class="sm-member-item<?= $aff['capo'] ? ' sm-member-item--leader' : '' ?>">
                <div class="sm-img-box">
                    <?php if (!empty($aff['immagine'])): ?>
                    <img src="imgs/mestieri/<?= gdrcd_filter('out', $aff['immagine']) ?>"
                         alt="<?= gdrcd_filter('out', $aff['nome_ruolo']) ?>">
                    <?php else: ?>
                    <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="sm-member-info">
                    <a href="main.php?page=scheda&pg=<?= gdrcd_filter('out', $aff['personaggio']) ?>" class="sm-member-name">
                        <?= gdrcd_filter('out', $aff['personaggio'] . ' ' . $aff['cognome']) ?>
                    </a>
                    <span class="sm-member-role"><?= gdrcd_filter('out', $aff['nome_ruolo']) ?></span>
                </div>
                <?php if ($aff['capo']): ?>
                <i class="fas fa-crown sm-member-crown"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Statuto -->
    <?php
    $statuto = gdrcd_query("SELECT statuto FROM mestiere WHERE id_mestiere = $id_mestiere");
    if (!empty($statuto['statuto'])): ?>
    <section class="sm-section">
        <div class="sm-section-title">
            <i class="fas fa-scroll"></i> Statuto
        </div>
        <div class="sm-statute">
            <?= gdrcd_bbcoder(gdrcd_filter('out', $statuto['statuto'])) ?>
        </div>
    </section>
    <?php endif; ?>

<?php endif; ?>
</div>
