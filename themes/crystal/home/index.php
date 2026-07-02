<?php
/**
 * index.php — Homepage pubblica di Crystal Tokyo GDR.
 *
 * Inclusa da /index.php dopo header.inc.php (vedi architettura progetto).
 * Emette un DOCTYPE completo: il doppio DOCTYPE nel DOM finale è gestito
 * correttamente dai browser moderni — non modificare senza aggiornare anche
 * il flusso di inclusione in index.php.
 *
 * File sostituiti/resi obsoleti da questa revisione:
 *   TODO: DELETE themes/crystal/home/login_index_nuova.css — ora usiamo /public/public.css
 *   TODO: DELETE themes/crystal/home/script_login_nuova.js — ora usiamo /public/public.js
 *
 * Componenti condivisi (da /public/):
 *   _head.php    → <head> con SEO, OG, JSON-LD, Google Fonts, public.css
 *   _nav.php     → header sticky + hamburger + bottoni Accedi/Registrati
 *   _modals.php  → modali login, registrazione, segnalazione
 *   _footer.php  → footer con colonne link + copyright + Palestine
 *   public.js    → JS condiviso (modale, hamburger, API razze/termini)
 */

/* ── Protezione (solo se attiva nelle settings) ──────────────────────── */
require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON') {
    require 'protezione.php';
}

/* ── Statistiche live per la homepage ───────────────────────────────── */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
$_db_home = gdrcd_connect();
$stat_iscritti    = (int)(gdrcd_query("SELECT COUNT(nome) AS n FROM personaggio WHERE permessi >= 0 AND sesso != 'b'")['n'] ?? 0);
$stat_online      = (int)(gdrcd_query("SELECT COUNT(*) AS n FROM personaggio WHERE ora_entrata > ora_uscita AND DATE_ADD(ultimo_refresh, INTERVAL 4 MINUTE) > NOW() AND is_invisible = 0")['n'] ?? 0);
$stat_azioni_oggi = (int)(gdrcd_query("SELECT COUNT(id) AS n FROM chat WHERE DATE(ora) = CURDATE() AND tipo IN ('P','A')")['n'] ?? 0);
$_mesi = ['','gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'];
$_data_iso  = date('Y-m-d');
$_data_it   = (int)date('j') . ' ' . $_mesi[(int)date('n')] . ' ' . date('Y');

/* ── Include componenti condivisi da /public/ ───────────────────────── */
/* Percorso assoluto: questa pagina è inclusa dalla root del sito */
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/_head.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/_nav.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/_modals.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/_footer.php';

/* ── JSON-LD: VideoGame schema ───────────────────────────────────────── */
$json_ld_home = [
    '@context'    => 'https://schema.org',
    '@type'       => 'VideoGame',
    'name'        => 'Crystal Tokyo GDR',
    'description' => 'Gioco di ruolo online play by chat gratuito, attivo da oltre vent\'anni, ambientato in una Tokyo futuristica dove tecnologia e magia convivono.',
    'url'         => 'https://crystaltokyo.it',
    'image'       => 'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    'gamePlatform'=> 'Browser',
    'genre'       => ['Gioco di ruolo', 'Play by chat', 'Fantasy urbano'],
    'inLanguage'  => 'it',
    'isAccessibleForFree' => true,
    'applicationCategory' => 'Game',
    'operatingSystem'     => 'Web browser',
    'offers' => [
        '@type'       => 'Offer',
        'price'       => '0',
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name'  => 'Crystal Tokyo GDR',
        'url'   => 'https://crystaltokyo.it',
    ],
];

/* ── <head> ──────────────────────────────────────────────────────────── */
public_head(
    'Crystal Tokyo GDR',
    'Gioco di ruolo online play by chat gratuito, attivo da oltre vent\'anni. Crea il tuo personaggio, scegli la tua razza e unisciti alla storia della Città di Cristallo.',
    'https://crystaltokyo.it/',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld_home
);
?>
<body>
<?php public_nav('home'); ?>
<?php public_modals(); ?>
<main id="main-content">

<!-- ── HERO ───────────────────────────────────────────────────────────────
     Sfondo: immagine principale del gioco con overlay scuro.
     Il CSS gestisce il fallback al gradiente scuro se l'immagine non carica.
     ─────────────────────────────────────────────────────────────────────── -->
<section class="pub-hero"
         style="background-image: url('/themes/crystal/imgs/homepage.png');">

    <!-- Overlay sfumato per leggibilità testo -->
    <div class="pub-hero-overlay"></div>

    <div class="pub-hero-content">
        <!-- Eyebrow tag sopra il titolo -->
        <span class="pub-hero-eyebrow">Attivo da oltre vent'anni &mdash; Gratis</span>

        <h1 class="pub-hero-title">
            Entra nella<br>
            <em>Città di Cristallo</em>
        </h1>

        <p class="pub-hero-subtitle">
            Crystal Tokyo è un gioco di ruolo online play by chat ambientato in una Tokyo futuristica
            dove tecnologia e magia convivono. Scrivi la storia del tuo personaggio insieme
            a una comunità attiva da oltre vent'anni.
        </p>

        <!-- CTA principale -->
        <div class="pub-hero-actions">
            <button class="pub-btn pub-btn--gold" id="heroRegBtn">
                <i class="fas fa-feather-alt"></i> Crea il tuo personaggio
            </button>
            <a href="/il-gioco" class="pub-btn pub-btn--ghost">
                Scopri il gioco
            </a>
        </div>
    </div>
</section>


<!-- ── STATISTICHE LIVE ──────────────────────────────────────────────────── -->
<section class="pub-stat-strip">
    <div class="pub-section-inner pub-center">
        <p class="pub-stat-strip-date">Crystal Tokyo oggi &mdash; <time datetime="<?= $_data_iso ?>"><?= $_data_it ?></time></p>
        <div class="pub-stats-grid pub-stats-grid--4">
            <div class="pub-stat-item">
                <span class="pub-stat-value"><?= number_format($stat_iscritti, 0, ',', '.') ?></span>
                <span class="pub-stat-label">Personaggi</span>
                <span class="pub-stat-desc">iscritti al gioco</span>
            </div>
            <div class="pub-stat-item">
                <span class="pub-stat-value"><?= $stat_online ?></span>
                <span class="pub-stat-label">Online ora</span>
                <span class="pub-stat-desc">giocatori connessi</span>
            </div>
            <div class="pub-stat-item">
                <span class="pub-stat-value"><?= number_format($stat_azioni_oggi, 0, ',', '.') ?></span>
                <span class="pub-stat-label">Azioni oggi</span>
                <span class="pub-stat-desc">narrativa scritta</span>
            </div>
            <div class="pub-stat-item">
                <span class="pub-stat-value">20+</span>
                <span class="pub-stat-label">Anni attivi</span>
                <span class="pub-stat-desc">comunità online</span>
            </div>
        </div>
    </div>
</section>

<!-- ── SEZIONE: COSA TI ASPETTA ──────────────────────────────────────────── -->
<section class="pub-section pub-section--dark">
    <div class="pub-section-inner">
        <p class="pub-section-label">Perché Crystal Tokyo</p>
        <h2 class="pub-section-title">Cosa ti aspetta</h2>
        <p class="pub-section-subtitle">
            Un'esperienza narrativa completa, collaborativa e gratuita.
        </p>

        <div class="pub-cards">

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-scroll"></i></div>
                <h3 class="pub-card-title">Narrazione condivisa</h3>
                <p class="pub-card-text">
                    Ogni personaggio contribuisce a una storia collettiva in continua evoluzione.
                    Le tue scelte hanno conseguenze reali nel mondo di gioco.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-dice-d20"></i></div>
                <h3 class="pub-card-title">Sistema a dadi</h3>
                <p class="pub-card-text">
                    Combatti, esplora e sfida il destino con un sistema a dadi bilanciato.
                    Quattro caratteristiche, 100 punti salute, infinite possibilità.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-users"></i></div>
                <h3 class="pub-card-title">Comunità attiva</h3>
                <p class="pub-card-text">
                    Una comunità di giocatori appassionati, uno staff narrativo dedicato
                    e trame sempre nuove. Non sei mai solo nella Città di Cristallo.
                </p>
            </div>

        </div>
    </div>
</section>


<!-- ── SEZIONE: AMBIENTAZIONE ────────────────────────────────────────────── -->
<section class="pub-section pub-section--darker">
    <div class="pub-section-inner">
        <div class="pub-two-col">

            <!-- Testo -->
            <div>
                <p class="pub-section-label">Il mondo di gioco</p>
                <h2 class="pub-section-title">Una Tokyo del futuro sopravvissuta al gelo</h2>
                <p>
                    Dopo una glaciazione durata oltre mille anni, Tokyo è stata la prima città a disgelarsi,
                    guadagnandosi il nome di <strong>Città di Cristallo</strong>. In superficie la vita
                    scorre normale, ma sotto questa quiete si nasconde la vera natura del mondo:
                    la magia è una forza onnipresente che nessuno può ignorare a lungo.
                </p>
                <p style="margin-top:16px;">
                    Le razze magiche si sono risvegliate. Le faide antiche, sopite per mille anni
                    di ghiaccio, si sono riaccese. Il tuo personaggio entra in questo mondo
                    con una storia, delle scelte e la libertà di decidere da che parte stare —
                    o di non stare da nessuna parte.
                </p>
                <a href="/ambientazione" class="pub-btn pub-btn--ghost" style="margin-top:24px;">
                    Leggi l'ambientazione &rsaquo;
                </a>
            </div>

            <div class="pub-two-col-img">
                <picture>
                    <source
                        type="image/webp"
                        srcset="/themes/crystal/imgs/gioco_preview-700.webp 700w,
                                /themes/crystal/imgs/gioco_preview-1400.webp 1400w,
                                /themes/crystal/imgs/gioco_preview.webp 2857w"
                        sizes="(max-width: 767px) calc(100vw - 48px), calc(50vw - 54px)">
                    <img src="/themes/crystal/imgs/gioco_preview.png"
                         alt="Interfaccia di gioco di Crystal Tokyo"
                         width="2857" height="1452"
                         style="width:100%; border-radius:12px; display:block; object-fit:cover;">
                </picture>
            </div>

        </div>
    </div>
</section>


<!-- ── SEZIONE: LE RAZZE ──────────────────────────────────────────────────── -->
<section class="pub-section pub-section--dark">
    <div class="pub-section-inner">
        <p class="pub-section-label">Scegli la tua natura</p>
        <h2 class="pub-section-title">Le Razze</h2>
        <p class="pub-section-subtitle">
            Sette razze, ognuna con origini e poteri propri. Nessuna gerarchia,
            nessun allineamento imposto — la storia del tuo personaggio la scrivi tu.
        </p>

        <!-- Tre card rappresentative (non tutte le razze, per non appesantire la homepage) -->
        <div class="pub-cards">

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-sun"></i></div>
                <h3 class="pub-card-title">Celestiali</h3>
                <p class="pub-card-text">
                    Portatori naturali di luce e protezione. La loro inclinazione benevola
                    è una tendenza, non un obbligo — e i Celestiali che scelgono strade oscure
                    sono tra i più temuti.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-fire"></i></div>
                <h3 class="pub-card-title">Demoni</h3>
                <p class="pub-card-text">
                    Creature di potere grezzo, legate a forze primordiali.
                    Si oppongono alla propria natura ogni giorno — o vi si abbandonano.
                    La scelta è sempre del giocatore.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-eye"></i></div>
                <h3 class="pub-card-title">Lancaster</h3>
                <p class="pub-card-text">
                    Affascinanti e pericolosi, i Lancaster traggono forza dalle ombre.
                    La loro storia nella città è lunga quanto quella della magia stessa.
                </p>
            </div>

        </div>

        <div style="text-align:center; margin-top:32px;">
            <a href="/razze" class="pub-btn pub-btn--ghost">Scopri tutte le razze &rsaquo;</a>
        </div>
    </div>
</section>


<!-- ── SEZIONE: LE GILDE ──────────────────────────────────────────────────── -->
<section class="pub-section pub-section--darker">
    <div class="pub-section-inner" style="max-width:760px; text-align:center;">
        <p class="pub-section-label">Costruisci la tua storia</p>
        <h2 class="pub-section-title">Le Gilde</h2>
        <p>
            Le <strong>gilde</strong> sono il cuore della vita sociale di Crystal Tokyo.
            Create dai giocatori stessi, con obiettivi e tematiche scelte in piena autonomia:
            dalla confraternita di guerrieri all'organizzazione criminale, dal circolo di studiosi
            alla rete di spionaggio. Ogni gilda costruisce la propria identità attraverso il gioco.
        </p>
        <p style="margin-top:16px; color: var(--pub-muted);">
            Non esiste un'ideologia predefinita: la tua gilda è ciò che tu e i tuoi alleati
            volete che sia.
        </p>
    </div>
</section>


<!-- ── CTA FINALE ──────────────────────────────────────────────────────────── -->
<section class="pub-section pub-section--dark" style="text-align:center; padding: 100px 24px;">
    <div class="pub-section-inner" style="max-width:640px;">
        <p class="pub-section-label">Inizia ora</p>
        <h2 class="pub-section-title">Pronto a entrare nella Città di Cristallo?</h2>
        <p class="pub-section-subtitle">
            È gratis. Non serve scaricare nulla. Basta un browser e la voglia di raccontare
            una storia.
        </p>
        <div class="pub-hero-actions">
            <button class="pub-btn pub-btn--gold" id="ctaRegBtn2">
                <i class="fas fa-feather-alt"></i> Crea il tuo personaggio
            </button>
            <a href="/come-si-gioca" class="pub-btn pub-btn--ghost">Come si gioca</a>
        </div>
    </div>
</section>


<!-- ── Supporto Palestina (fixed) ────────────────────────────────────────── -->
<!--
<div id="bottomBanner"
     style="position:fixed; bottom:20px; right:20px; z-index:9999;
            background:rgba(255,255,255,0.15); backdrop-filter:blur(4px);
            padding:10px 14px; border-radius:8px; text-align:center;
            max-width:200px; border:1px solid rgba(255,255,255,0.2);">
    <a href="https://globalsumudflotilla.org/" target="_blank" rel="noopener noreferrer"
       style="display:block; width:80px; height:52px; margin:0 auto 8px;
              background-image:url('/themes/crystal/imgs/flag-palestine.svg');
              background-size:cover; background-position:center;
              border-radius:4px; border:1px solid rgba(0,0,0,0.3);">
    </a>
    <div style="font-size:10px; color:rgba(255,255,255,0.85); font-weight:700; line-height:1.3;">
        Crystal Tokyo GDR<br>supporta la Palestina
    </div>
</div>
-->

</main>
<?php public_footer(); gdrcd_close_connection($_db_home); ?>

<!-- Apertura modali dai bottoni CTA della homepage -->
<script>
['heroRegBtn', 'ctaRegBtn2'].forEach(function(id) {
    var btn = document.getElementById(id);
    if (btn) {
        btn.addEventListener('click', function () {
            openModal('pubRegModal');
            ensureOptionsLoaded();
            ensureTerminiLoaded();
        });
    }
});
</script>

</body>
</html>
