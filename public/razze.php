<?php
/**
 * razze.php — Pagina pubblica "Le Razze" di Crystal Tokyo GDR.
 *
 * Contenuto caricato dinamicamente dalla tabella `gilda` (visibile = 1).
 * Le razze non hanno gerarchia né allineamento predefinito — sono sette
 * razze allo stesso livello. Il campo `tipo` in gilda è un refuso e
 * viene ignorato.
 *
 * Se lo staff aggiorna nome, statuto o immagine di una razza nel DB,
 * la pagina si aggiorna automaticamente al prossimo caricamento.
 *
 * URL pulito: /razze → location block nginx
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/required.php';
require_once __DIR__ . '/_head.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/_footer.php';
require_once __DIR__ . '/_modals.php';

/* ── Lettura razze dal DB ────────────────────────────────────────────────── */
$handleDBConnection = gdrcd_connect();

$razze = [];
$r = gdrcd_query(
    "SELECT id_gilda, nome, statuto, immagine
       FROM gilda
      WHERE visibile = 1
      ORDER BY nome ASC",
    'result'
);
while ($row = gdrcd_query($r, 'fetch')) {
    $razze[] = $row;
}
gdrcd_query($r, 'free');
gdrcd_close_connection($handleDBConnection);

/* ── Icone di fallback per razza (usate quando immagine è NULL) ─────────── */
$razza_icons = [
    'Adamanti'   => 'fas fa-shield-alt',
    'Celestiali' => 'fas fa-sun',
    'Elementali' => 'fas fa-wind',
    'Beast'      => 'fas fa-paw',
    'Fiori'      => 'fas fa-leaf',
    'Demoni'     => 'fas fa-fire',
    'Lancaster'  => 'fas fa-eye',
];

/* ── JSON-LD ─────────────────────────────────────────────────────────────── */
$json_ld = [
    '@context'   => 'https://schema.org',
    '@type'      => 'WebPage',
    'name'       => 'Le Razze — Crystal Tokyo GDR',
    'description'=> 'Scopri le sette razze giocabili di Crystal Tokyo GDR: Adamanti, Celestiali, Elementali, Beast, Fiori, Demoni e Lancaster. Ognuna con poteri e storia propri.',
    'url'        => 'https://crystaltokyo.it/razze',
    'isPartOf'   => ['@type' => 'WebSite', 'name' => 'Crystal Tokyo GDR', 'url' => 'https://crystaltokyo.it'],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',     'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Le Razze', 'item' => 'https://crystaltokyo.it/razze'],
        ],
    ],
];

public_head(
    'Le Razze',
    'Scopri le sette razze giocabili di Crystal Tokyo GDR: Adamanti, Celestiali, Elementali, Beast, Fiori, Demoni e Lancaster. Ognuna con poteri e storia propri.',
    'https://crystaltokyo.it/razze',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld
);
?>
<body>
<?php public_nav('razze'); ?>
<?php public_modals(); ?>

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
<section class="pub-hero pub-hero--short">
    <div class="pub-hero-content">
        <h1>Le Razze</h1>
        <p class="pub-hero-sub">
            Sette razze, ognuna con origini e poteri propri.
            Non esiste una gerarchia — solo la storia che scegli di raccontare.
        </p>
    </div>
</section>

<main class="pub-page-content">

    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Le Razze</span>
    </nav>

    <!-- ── Intro ────────────────────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Scegli la tua natura</h2>
        <p>
            In Crystal Tokyo esistono sette <strong>razze</strong>, ognuna con caratteristiche,
            origini e legami magici propri. La razza definisce la natura profonda del personaggio
            e ne determina le abilità disponibili nel corso del gioco.
        </p>
        <p>
            Non esiste una razza migliore delle altre, né un percorso obbligato:
            ogni personaggio costruisce la propria storia indipendentemente dalla razza scelta.
        </p>
    </section>

    <!-- ── Griglia razze ─────────────────────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <?php if (empty($razze)): ?>
        <p style="text-align:center; color:var(--pub-muted);">
            Nessuna razza disponibile al momento.
        </p>
        <?php else: ?>
        <div class="pub-razze-grid">
            <?php foreach ($razze as $razza):
                $nome    = htmlspecialchars($razza['nome']);
                $statuto = nl2br(gdrcd_filter('out', $razza['statuto'] ?? ''));
                $icon    = $razza_icons[$razza['nome']] ?? 'fas fa-star';
                $img     = !empty($razza['immagine']) ? htmlspecialchars($razza['immagine']) : null;
            ?>
            <article class="pub-razza-card">
                <div class="pub-razza-card-header">
                    <?php if ($img): ?>
                    <img src="<?= $img ?>" alt="<?= $nome ?>" class="pub-razza-img" loading="lazy">
                    <?php else: ?>
                    <span class="pub-razza-icon"><i class="<?= $icon ?>"></i></span>
                    <?php endif; ?>
                </div>
                <h3><?= $nome ?></h3>
                <?php if ($statuto): ?>
                <p class="pub-razza-desc"><?= $statuto ?></p>
                <?php else: ?>
                <p class="pub-razza-desc"><em>Descrizione in arrivo.</em></p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── CTA ──────────────────────────────────────────────────────────── -->
    <section class="pub-section pub-section--cta">
        <h2>Hai scelto la tua razza?</h2>
        <p>
            La scelta della razza è solo l'inizio: sarà il tuo gioco, le tue decisioni
            e le tue alleanze a costruire la storia del tuo personaggio.
        </p>
        <div class="pub-cta-btns">
            <button class="pub-btn pub-btn--gold" id="ctaRegBtn">Crea il tuo personaggio</button>
            <a href="/come-si-gioca" class="pub-btn pub-btn--ghost">Come si gioca</a>
        </div>
    </section>

</main>

<?php public_footer(); ?>

<script>
document.getElementById('ctaRegBtn').addEventListener('click', function () {
    openModal('pubRegModal');
    ensureOptionsLoaded();
    ensureTerminiLoaded();
});
</script>

</body>
</html>
