<?php
/**
 * il-gioco.php — Pagina pubblica "Il Gioco" di Crystal Tokyo GDR.
 *
 * Presenta il gioco ai potenziali nuovi giocatori:
 *   - Cos'è Crystal Tokyo GDR (storia, piattaforma)
 *   - L'ambientazione: la Città di Cristallo
 *   - Cosmos, Caos e le Vie Magiche
 *   - Le Razze (rimpiazzano le vecchie famiglie — nessun allineamento fisso)
 *   - Le Gilde (gruppi creati dai giocatori)
 *   - Come funziona il gioco (dadi, stats, shin)
 *   - CTA registrazione
 *
 * OBSOLETO DA ELIMINARE: docs/il_gioco.html — ancora esistente ma
 * TODO: DELETE docs/il_gioco.html una volta che questa pagina è live e indicizzata.
 *
 * URL pulito: /il-gioco → RewriteRule in .htaccess
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
    'name'       => 'Il Gioco — Crystal Tokyo GDR',
    'description'=> 'Scopri Crystal Tokyo GDR: un gioco di ruolo play by chat gratuito, attivo da oltre vent\'anni, ambientato in una Tokyo futuristica dove tecnologia e magia coesistono.',
    'url'        => 'https://crystaltokyo.it/il-gioco',
    'isPartOf'   => [
        '@type' => 'WebSite',
        'name'  => 'Crystal Tokyo GDR',
        'url'   => 'https://crystaltokyo.it',
    ],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',     'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Il Gioco', 'item' => 'https://crystaltokyo.it/il-gioco'],
        ],
    ],
];

/* ── <head> ──────────────────────────────────────────────────────────────── */
public_head(
    'Il Gioco',
    'Scopri Crystal Tokyo GDR: un GDR play by chat gratuito, attivo da oltre vent\'anni, in una Tokyo futuristica dove tecnologia e magia convivono.',
    'https://crystaltokyo.it/il-gioco',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld
);
?>
<body>
<?php public_nav('il-gioco'); ?>
<?php public_modals(); ?>

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
<section class="pub-hero pub-hero--short">
    <div class="pub-hero-content">
        <h1>Cos'è Crystal Tokyo GDR</h1>
        <p class="pub-hero-sub">
            Un gioco di ruolo testuale gratuito, attivo da oltre vent'anni,
            in una Tokyo del futuro dove magia e tecnologia convivono in un equilibrio fragile.
        </p>
    </div>
</section>


<!-- ── CONTENUTO PRINCIPALE ──────────────────────────────────────────────── -->
<main class="pub-page-content">

    <!-- Breadcrumb (visibilità SEO + usabilità) -->
    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Il Gioco</span>
    </nav>


    <!-- ── Sezione 1: Cos'è ─────────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Un gioco di ruolo play by chat</h2>
        <p>
            <strong>Crystal Tokyo GDR</strong> è un gioco di ruolo online <em>play by chat</em> gratuito,
            attivo da oltre vent'anni, che unisce narrazione collaborativa, interpretazione testuale
            e un'ambientazione urban fantasy originale.
        </p>
        <p>
            I giocatori interpretano personaggi che vivono e agiscono in una Tokyo futuristica,
            dove tecnologia e magia convivono in un equilibrio fragile e costantemente minacciato.
            Ogni chat rappresenta un luogo iconico della città: strade, quartieri, edifici e zone
            simboliche di una Tokyo futura, che prende vita attraverso le azioni e le parole
            dei personaggi.
        </p>
        <p>
            Il gioco è costruito su una versione avanzata della piattaforma GDRCD, completamente
            rivisitata per offrire un'esperienza stabile, profonda e orientata alla narrazione
            condivisa. Le trame si sviluppano grazie all'interazione tra i giocatori e al supporto
            dello staff narrativo.
        </p>
    </section>


    <!-- ── Sezione 2: Ambientazione ─────────────────────────────────────── -->
    <section class="pub-section">
        <h2>L'ambientazione: la Città di Cristallo</h2>
        <p>
            Crystal Tokyo è ambientata in un futuro magico, successivo a una glaciazione durata
            oltre mille anni che ha colpito il mondo intero. L'umanità è sopravvissuta e ha ripreso
            a prosperare, raggiungendo nuovi livelli di sviluppo tecnologico e magico.
        </p>
        <p>
            Tokyo è stata la prima città a disgelarsi, diventando il simbolo della rinascita
            e guadagnandosi il nome di <strong>Città di Cristallo</strong>. In apparenza la vita
            scorre normalmente, ma sotto questa superficie si nasconde la vera natura del mondo:
            la magia è una forza onnipresente, conosciuta da tutti e spesso volutamente ignorata.
        </p>
        <p>
            <a href="/ambientazione" class="pub-link">Scopri l'ambientazione completa &rsaquo;</a>
        </p>
    </section>


    <!-- ── Sezione 3: Cosmos e Caos ─────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Cosmos, Caos e le Vie Magiche</h2>
        <p>
            L'esistenza magica di Crystal Tokyo è dominata da due grandi forze primordiali:
            <strong>Cosmos</strong> e <strong>Caos</strong>. Entrambe sono considerate vere e
            proprie divinità da coloro che ne traggono potere e devozione.
        </p>
        <p>
            Da queste forze discendono le <strong>Vie Magiche</strong>: percorsi spirituali e mistici
            che plasmano l'identità dei personaggi e ne determinano abilità e ideali.
            Le Vie Magiche alimentano una lotta costante per il dominio della città e del mondo,
            ma nessun personaggio è obbligato a scegliere una parte — l'ambiguità è parte
            integrante di questa storia.
        </p>
    </section>


    <!-- ── Sezione 4: Le Razze ──────────────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Le Razze</h2>
        <p>
            In Crystal Tokyo esistono diverse <strong>razze</strong>, ognuna con caratteristiche,
            origini e legami magici propri. La razza definisce la natura profonda del personaggio
            e ne influenza le abilità, ma <em>non ne determina l'allineamento</em>: ogni personaggio
            è libero di scegliere il proprio percorso tra Cosmos, Caos o la via di mezzo.
        </p>
        <p>
            Le razze spaziano da creature legate alle forze di Caos — come i Demoni del Regno
            oscuro e i Lancaster, entità simili a vampiri — a quelle vicine a Cosmos, come i
            Giustizieri e i Guardiani, fino alle razze neutrali che cercano il proprio equilibrio
            tra le due grandi forze.
        </p>
        <p>
            <a href="/razze" class="pub-btn pub-btn--ghost">Scopri tutte le razze &rsaquo;</a>
        </p>
    </section>


    <!-- ── Sezione 5: Le Gilde ──────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Le Gilde</h2>
        <p>
            Le <strong>gilde</strong> sono gruppi creati direttamente dai giocatori, con obiettivi
            e tematiche scelte in piena autonomia. Si organizzano da soli e partecipano attivamente
            alle trame del gioco, costruendo storie, alleanze e conflitti che arricchiscono
            l'intera comunità.
        </p>
        <p>
            Non esiste un'ideologia predefinita: ogni gilda si costruisce la propria identità,
            può essere un'organizzazione criminale, una confraternita di guerrieri, un circolo
            di studiosi della magia, o qualsiasi altra cosa i giocatori immaginino.
            Le gilde sono il cuore pulsante della vita sociale e politica di Crystal Tokyo.
        </p>
    </section>


    <!-- ── Sezione 6: Come funziona ─────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Come funziona il gioco</h2>

        <!-- Tre colonne informative -->
        <div class="pub-cards pub-cards--3">

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-dice-d20"></i></div>
                <h3>Sistema a dadi</h3>
                <p>
                    I conflitti si risolvono con lanci di dadi. Ogni personaggio ha
                    <strong>100 punti salute</strong>, <strong>100 punti integrità</strong>
                    e quattro caratteristiche: Potere, Destrezza, Mente e Tempra.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-star"></i></div>
                <h3>Crescita del personaggio</h3>
                <p>
                    Accumuli <strong>punti esperienza</strong> giocando e
                    <strong>punti shin</strong> grazie alla qualità dell'interpretazione.
                    Entrambi servono a migliorare le caratteristiche e a sbloccare abilità magiche.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-scroll"></i></div>
                <h3>Mestieri e trame</h3>
                <p>
                    Scegli un <strong>mestiere narrativo</strong> che contribuisce allo sviluppo
                    delle trame. Lo staff narrativo supporta le storie create dai giocatori,
                    rendendo ogni personaggio parte di un mondo vivo.
                </p>
            </div>

        </div>

        <p style="margin-top:2rem;">
            <a href="/come-si-gioca" class="pub-link">Leggi la guida completa su come si gioca &rsaquo;</a>
        </p>
    </section>


    <!-- ── CTA finale ───────────────────────────────────────────────────── -->
    <section class="pub-section pub-section--cta">
        <h2>Inizia a giocare — è gratis</h2>
        <p>
            Crystal Tokyo GDR è un'esperienza narrativa, collaborativa e in continua evoluzione.
            Crea il tuo personaggio, scegli la tua razza, forgia le tue alleanze e lascia
            il tuo segno nella storia della Città di Cristallo.
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
