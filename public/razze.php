<?php
/**
 * razze.php — Pagina pubblica "Le Razze" di Crystal Tokyo GDR.
 *
 * Presenta le razze giocabili ai potenziali nuovi giocatori.
 * Le razze sostituiscono il vecchio sistema delle famiglie:
 *   - Nessun allineamento fisso per razza
 *   - Ogni personaggio sceglie il proprio percorso tra Cosmos, Caos o neutro
 *   - Descrizioni, immagini e nomi vanno completati con i contenuti reali
 *
 * TODO: Riempire le card con nomi, descrizioni e immagini reali delle razze.
 *       I dati sono in tabella `razze` nel DB di gioco.
 *       In alternativa, questa pagina può includere required.php e fare
 *       una query diretta — valutare se il costo in accoppiamento vale la pena.
 *
 * URL pulito: /razze → RewriteRule in .htaccess
 *
 * Dipendenze condivise: _head.php, _nav.php, _footer.php, _modals.php
 */

require_once __DIR__ . '/_head.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/_footer.php';
require_once __DIR__ . '/_modals.php';

/* ── JSON-LD: WebPage + breadcrumb ───────────────────────────────────────── */
$json_ld = [
    '@context'   => 'https://schema.org',
    '@type'      => 'WebPage',
    'name'       => 'Le Razze — Crystal Tokyo GDR',
    'description'=> 'Scopri le razze giocabili di Crystal Tokyo GDR: creature legate a Cosmos, Caos o alle forze neutrali. Ogni razza ha abilità uniche — nessun allineamento predefinito.',
    'url'        => 'https://crystaltokyo.it/razze',
    'isPartOf'   => [
        '@type' => 'WebSite',
        'name'  => 'Crystal Tokyo GDR',
        'url'   => 'https://crystaltokyo.it',
    ],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',     'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Le Razze', 'item' => 'https://crystaltokyo.it/razze'],
        ],
    ],
];

/* ── <head> ──────────────────────────────────────────────────────────────── */
public_head(
    'Le Razze',
    'Scopri le razze giocabili di Crystal Tokyo GDR: creature legate a Cosmos, Caos o alle forze neutrali. Ogni razza ha abilità uniche — nessun allineamento predefinito.',
    'https://crystaltokyo.it/razze',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld
);

/*
 * ── RAZZE ───────────────────────────────────────────────────────────────────
 * Array con i dati delle razze giocabili.
 *
 * TODO: Sostituire questo array con i dati reali (dal DB o da file di config).
 *       Campi: name, tagline, description, icon (Font Awesome class), affinity
 *       affinity: 'cosmos' | 'caos' | 'neutro' (solo per lo stile card, non
 *                 implica allineamento obbligatorio del personaggio)
 */
$razze = [
    [
        'name'        => 'Demoni del Regno di Caos',
        'tagline'     => 'Potere primordiale nato dall\'oscurità',
        'description' => 'Creature legate alle forze demoniache del Regno oscuro, con origini '
                       . 'che affondano in tradizioni scintoiste e mitologie antiche. '
                       . 'Possiedono una forza naturale straordinaria e un istinto bellicoso '
                       . 'che pochi sapranno domare — o sceglieranno di domare.',
        'icon'        => 'fas fa-fire',
        'affinity'    => 'caos',
    ],
    [
        'name'        => 'Lancaster',
        'tagline'     => 'Eterni signori del desiderio e dell\'ombra',
        'description' => 'Entità simili ai vampiri delle leggende, capaci di nutrirsi dei vizi, '
                       . 'dei sogni e dei desideri altrui. I Lancaster sono creature di un\'eleganza '
                       . 'quasi soprannaturale, con una pazienza affinata da secoli di esistenza '
                       . 'e poteri legati all\'oscurità e alla mente.',
        'icon'        => 'fas fa-eye',
        'affinity'    => 'caos',
    ],
    [
        'name'        => 'Giustizieri di Cosmos',
        'tagline'     => 'Guerrieri della luce, paladini dei pianeti',
        'description' => 'Combattenti che traggono potere direttamente dai pianeti del sistema solare. '
                       . 'I Giustizieri sono la spada di Cosmos: aggressivi, diretti e pronti alla '
                       . 'battaglia contro le forze del disordine. Le loro tecniche di combattimento '
                       . 'sono tra le più spettacolari del mondo magico.',
        'icon'        => 'fas fa-bolt',
        'affinity'    => 'cosmos',
    ],
    [
        'name'        => 'Guardiani di Cosmos',
        'tagline'     => 'Protettori del mondo e custodi degli spiriti',
        'description' => 'Protettori silenziosi e tenaci, i Guardiani evocano il potere ancestrale '
                       . 'di Cosmos e comunicano con gli spiriti benevoli dell\'Oltretomba. '
                       . 'Meno inclini alla battaglia frontale dei Giustizieri, compensano con '
                       . 'abilità difensive, curative e di sostegno senza eguali.',
        'icon'        => 'fas fa-shield-alt',
        'affinity'    => 'cosmos',
    ],
    [
        'name'        => 'Setta dei Miracoli',
        'tagline'     => 'Maghi elementali in cerca dell\'equilibrio',
        'description' => 'Convinti che solo l\'equilibrio tra Cosmos e Caos possa portare vera pace, '
                       . 'i membri della Setta dei Miracoli padroneggiato gli elementi naturali '
                       . 'con maestria rara. Né servi della luce né dell\'oscurità, cercano la '
                       . 'propria strada tra le grandi forze del mondo.',
        'icon'        => 'fas fa-wind',
        'affinity'    => 'neutro',
    ],
    [
        'name'        => 'Fiori del Male',
        'tagline'     => 'Creature fatate che esaltano arte e bellezza eterna',
        'description' => 'Creature dai tratti quasi fatati, i Fiori del Male vivono secondo una '
                       . 'natura caotica che non si piega a nessun ordine morale. Esaltano l\'arte, '
                       . 'la bellezza e l\'eternità come valori supremi. La loro magia è tra le '
                       . 'più versatili e imprevedibili dell\'intero setting.',
        'icon'        => 'fas fa-leaf',
        'affinity'    => 'neutro',
    ],
    [
        'name'        => 'Custodi del Primordio',
        'tagline'     => 'Creature stellari che venerano il Nihil',
        'description' => 'Entità dai tratti animali che venerano il Nihil — un\'entità purificata '
                       . 'da ogni influenza di Cosmos e Caos. I Custodi del Primordio rappresentano '
                       . 'la forza più antica e misteriosa del mondo di Crystal Tokyo, con poteri '
                       . 'legati alle stelle e alla natura primordiale.',
        'icon'        => 'fas fa-star',
        'affinity'    => 'neutro',
    ],
];
?>
<body>
<?php public_nav('razze'); ?>
<?php public_modals(); ?>

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
<section class="pub-hero pub-hero--short">
    <div class="pub-hero-content">
        <h1>Le Razze</h1>
        <p class="pub-hero-sub">
            Sette razze giocabili, ognuna con poteri e origini uniche.
            Il tuo allineamento lo scegli tu — la razza ti dà solo la natura di partenza.
        </p>
    </div>
</section>


<!-- ── CONTENUTO PRINCIPALE ──────────────────────────────────────────────── -->
<main class="pub-page-content">

    <!-- Breadcrumb -->
    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Le Razze</span>
    </nav>


    <!-- ── Introduzione ─────────────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Scegli la tua natura</h2>
        <p>
            In Crystal Tokyo esistono diverse <strong>razze</strong>, ognuna con caratteristiche,
            origini e legami magici propri. La razza definisce la natura profonda del personaggio
            e ne determina le abilità disponibili — ma <strong>non ne impone l'allineamento</strong>.
        </p>
        <p>
            Un Demone può servire Cosmos. Un Guardiano può abbracciare il Caos. Un Custode del Primordio
            può rifiutare entrambe le grandi forze. La storia di Crystal Tokyo è scritta da personaggi
            che sfidano le aspettative — e il tuo potrebbe essere il prossimo.
        </p>
    </section>


    <!-- ── Legenda affinità ──────────────────────────────────────────────── -->
    <section class="pub-section pub-razze-legenda">
        <div class="pub-razze-legenda-items">
            <span class="pub-affinity-badge pub-affinity-badge--cosmos">
                <i class="fas fa-sun"></i> Affinità Cosmos
            </span>
            <span class="pub-affinity-badge pub-affinity-badge--caos">
                <i class="fas fa-moon"></i> Affinità Caos
            </span>
            <span class="pub-affinity-badge pub-affinity-badge--neutro">
                <i class="fas fa-yin-yang"></i> Neutro / Primordiale
            </span>
            <em class="pub-razze-legenda-note">
                L'affinità è la natura originaria della razza, non l'allineamento del tuo personaggio.
            </em>
        </div>
    </section>


    <!-- ── Griglia razze ─────────────────────────────────────────────────── -->
    <section class="pub-section pub-razze-grid-section">
        <div class="pub-razze-grid">
            <?php foreach ($razze as $razza): ?>
            <article class="pub-razza-card pub-razza-card--<?= htmlspecialchars($razza['affinity']) ?>">
                <!-- Icona e badge affinità -->
                <div class="pub-razza-card-header">
                    <span class="pub-razza-icon"><i class="<?= htmlspecialchars($razza['icon']) ?>"></i></span>
                    <span class="pub-affinity-badge pub-affinity-badge--<?= htmlspecialchars($razza['affinity']) ?> pub-affinity-badge--sm">
                        <?= htmlspecialchars(ucfirst($razza['affinity'])) ?>
                    </span>
                </div>
                <!-- Nome e tagline -->
                <h3><?= htmlspecialchars($razza['name']) ?></h3>
                <p class="pub-razza-tagline"><em><?= htmlspecialchars($razza['tagline']) ?></em></p>
                <!-- Descrizione -->
                <p class="pub-razza-desc"><?= htmlspecialchars($razza['description']) ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </section>


    <!-- ── CTA finale ───────────────────────────────────────────────────── -->
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

<!-- Apre la modale registrazione dal CTA in-page -->
<script>
document.getElementById('ctaRegBtn').addEventListener('click', function () {
    openModal('pubRegModal');
    ensureOptionsLoaded();
    ensureTerminiLoaded();
});
</script>

</body>
</html>
