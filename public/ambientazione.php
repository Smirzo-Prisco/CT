<?php
/**
 * ambientazione.php — Pagina pubblica "Ambientazione" di Crystal Tokyo GDR.
 *
 * Contenuto caricato dinamicamente dalla tabella `regolamento`
 * dove tipo = 'ambientazione'. Gli articoli vengono mostrati in ordine
 * di `articolo` (id): per riordinare basta aggiornare i valori nel DB.
 *
 * Il testo degli articoli può contenere HTML usato anche nel gioco
 * (es. <span class="look">). La classe .look viene stilata in public.css
 * per adattarsi al design delle pagine pubbliche.
 *
 * URL pulito: /ambientazione → location block nginx
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/required.php';
require_once __DIR__ . '/_head.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/_footer.php';
require_once __DIR__ . '/_modals.php';

/* ── Lettura articoli ambientazione dal DB ───────────────────────────────── */
$handleDBConnection = gdrcd_connect();

$articoli = [];
$r = gdrcd_query(
    "SELECT articolo, titolo, testo
       FROM regolamento
      WHERE tipo = 'ambientazione'
      ORDER BY articolo ASC",
    'result'
);
while ($row = gdrcd_query($r, 'fetch')) {
    $articoli[] = $row;
}
gdrcd_query($r, 'free');
gdrcd_close_connection($handleDBConnection);

/* ── JSON-LD ─────────────────────────────────────────────────────────────── */
$json_ld = [
    '@context'   => 'https://schema.org',
    '@type'      => 'WebPage',
    'name'       => 'Ambientazione — Crystal Tokyo GDR',
    'description'=> 'Esplora il mondo di Crystal Tokyo GDR: la storia della Città di Cristallo, la Grande Glaciazione, l\'Impero Lunare e tutto ciò che ha plasmato questo universo urban fantasy.',
    'url'        => 'https://crystaltokyo.it/ambientazione',
    'isPartOf'   => ['@type' => 'WebSite', 'name' => 'Crystal Tokyo GDR', 'url' => 'https://crystaltokyo.it'],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',          'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Ambientazione', 'item' => 'https://crystaltokyo.it/ambientazione'],
        ],
    ],
];

public_head(
    'Ambientazione',
    'Esplora il mondo di Crystal Tokyo GDR: la storia della Città di Cristallo, la Grande Glaciazione, l\'Impero Lunare e tutto ciò che ha plasmato questo universo urban fantasy.',
    'https://crystaltokyo.it/ambientazione',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld
);
?>
<body>
<?php public_nav('ambientazione'); ?>
<?php public_modals(); ?>

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
<section class="pub-hero pub-hero--short">
    <div class="pub-hero-content">
        <h1>Ambientazione</h1>
        <p class="pub-hero-sub">
            La storia della Città di Cristallo: mille anni di ghiaccio,
            una rinascita e un mondo dove la magia scorre sotto la superficie.
        </p>
    </div>
</section>

<main class="pub-page-content">

    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Ambientazione</span>
    </nav>

    <?php if (empty($articoli)): ?>
    <!-- Fallback se il DB non restituisce articoli -->
    <section class="pub-section">
        <p style="color:var(--pub-muted); text-align:center;">
            Contenuto in arrivo.
        </p>
    </section>

    <?php else: ?>

    <?php foreach ($articoli as $i => $art): ?>
    <!-- Sezione alternata chiara/scura per separare visivamente gli articoli -->
    <section class="pub-section <?= $i % 2 === 1 ? 'pub-section--highlight' : '' ?>">
        <h2><?= htmlspecialchars($art['titolo']) ?></h2>
        <div class="pub-regolamento-content">
            <?= $art['testo'] ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php endif; ?>

    <!-- ── CTA ──────────────────────────────────────────────────────────── -->
    <section class="pub-section pub-section--cta">
        <h2>Esplora Crystal Tokyo in prima persona</h2>
        <p>
            Leggere dell'ambientazione è solo il primo passo.
            La vera storia di Crystal Tokyo la scrivono i giocatori, ogni giorno.
        </p>
        <div class="pub-cta-btns">
            <button class="pub-btn pub-btn--gold" id="ctaRegBtn">Crea il tuo personaggio</button>
            <a href="/razze" class="pub-btn pub-btn--ghost">Scopri le Razze</a>
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
