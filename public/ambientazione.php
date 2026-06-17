<?php
/**
 * ambientazione.php — Pagina pubblica "Ambientazione" di Crystal Tokyo GDR.
 *
 * Descrive il mondo di Crystal Tokyo ai potenziali nuovi giocatori:
 *   - Premessa: la glaciazione e la rinascita
 *   - La Città di Cristallo (Tokyo)
 *   - La Sorgente: origine della magia
 *   - Cosmos e Caos
 *   - Le Vie Magiche
 *   - L'Impero Lunare (cenni storici)
 *   - CTA verso registrazione e pagina razze
 *
 * URL pulito: /ambientazione → RewriteRule in .htaccess
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
    'name'       => 'Ambientazione — Crystal Tokyo GDR',
    'description'=> 'Esplora il mondo di Crystal Tokyo GDR: la Città di Cristallo, la magia di Cosmos e Caos, le Vie Magiche e la storia di una Tokyo futuristica sopravvissuta alla glaciazione.',
    'url'        => 'https://crystaltokyo.it/ambientazione',
    'isPartOf'   => [
        '@type' => 'WebSite',
        'name'  => 'Crystal Tokyo GDR',
        'url'   => 'https://crystaltokyo.it',
    ],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',          'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Ambientazione', 'item' => 'https://crystaltokyo.it/ambientazione'],
        ],
    ],
];

/* ── <head> ──────────────────────────────────────────────────────────────── */
public_head(
    'Ambientazione',
    'Esplora il mondo di Crystal Tokyo GDR: la Città di Cristallo, la magia di Cosmos e Caos, le Vie Magiche e la storia di una Tokyo futuristica sopravvissuta alla glaciazione.',
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
            Una Tokyo del futuro sopravvissuta a mille anni di ghiaccio,
            dove la magia scorre sotto la superficie di una città in apparenza normale.
        </p>
    </div>
</section>


<!-- ── CONTENUTO PRINCIPALE ──────────────────────────────────────────────── -->
<main class="pub-page-content">

    <!-- Breadcrumb -->
    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Ambientazione</span>
    </nav>


    <!-- ── Sezione 1: Premessa ──────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Premessa: il Grande Gelo</h2>
        <p>
            Molto tempo fa, senza preavviso e senza spiegazione, il mondo fu colpito da una
            glaciazione catastrofica. Le temperature precipitarono in pochi mesi e l'intera
            civiltà umana si trovò a combattere per la sopravvivenza sotto metri di ghiaccio
            e neve. La glaciazione durò oltre <strong>mille anni</strong>.
        </p>
        <p>
            Eppure l'umanità sopravvisse. Non senza perdite enormi, non senza trasformazioni
            profonde — ma sopravvisse. E quando il ghiaccio cominciò a ritirarsi, emerse
            una civiltà radicalmente nuova, plasmata sia dalla tecnologia che dalla riscoperta
            di forze più antiche: la magia.
        </p>
    </section>


    <!-- ── Sezione 2: La Città di Cristallo ─────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>La Città di Cristallo</h2>
        <p>
            <strong>Tokyo</strong> fu la prima città a liberarsi dal ghiaccio.
            Questa primogenitura le valse un nome che ancora oggi risuona in tutto il mondo:
            <em>Crystal Tokyo</em>, la Città di Cristallo. Simbolo di speranza, di rinascita
            e di ciò che l'umanità può diventare quando unisce ingegno e magia.
        </p>
        <p>
            Oggi Crystal Tokyo è una metropoli moderna e rutilante, con grattacieli che toccano
            le nuvole, tecnologia avanzatissima e una vita sociale intensa. In superficie,
            potrebbe sembrare una qualsiasi grande città. Ma chiunque abbia occhi aperti
            sa che sotto questa normalità scorre qualcosa di antico e potentissimo.
        </p>
        <p>
            La magia <strong>non è segreta</strong>. È conosciuta, è pubblica, è discussa.
            Semplicemente, la maggior parte delle persone sceglie di ignorarla, come si ignora
            il ronzio di un elettrodomestico di sfondo: sempre presente, radicato nella vita
            quotidiana, ma raramente al centro dell'attenzione.
        </p>
    </section>


    <!-- ── Sezione 3: La Sorgente ──────────────────────────────────────── -->
    <section class="pub-section">
        <h2>La Sorgente</h2>
        <p>
            Al cuore di Crystal Tokyo — non geograficamente, ma metafisicamente — esiste
            ciò che i maghi chiamano <strong>la Sorgente</strong>: il punto di confluenza
            di tutte le energie magiche del mondo. È da qui che Cosmos e Caos si alimentano,
            è da qui che le Vie Magiche traggono la loro linfa, ed è attorno a questo luogo
            di potere che si giocano gli equilibri più delicati della storia.
        </p>
        <p>
            La natura precisa della Sorgente è oggetto di dibattito tra gli studiosi:
            c'è chi la considera una divinità in sé, chi una frattura nel tessuto della realtà,
            chi la porta verso un'altra dimensione. Nessuno ha ancora una risposta definitiva —
            e forse nessuno la vuole davvero.
        </p>
    </section>


    <!-- ── Sezione 4: Cosmos e Caos ─────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Cosmos e Caos</h2>

        <div class="pub-cards pub-cards--2">

            <div class="pub-card pub-card--cosmos">
                <div class="pub-card-icon"><i class="fas fa-sun"></i></div>
                <h3>Cosmos</h3>
                <p>
                    Cosmos è la forza dell'ordine, della luce e della protezione.
                    Chi ne segue la via crede che la civiltà, la legge e l'equilibrio
                    tra le creature siano beni da difendere a ogni costo.
                    I seguaci di Cosmos traggono potere dai pianeti, dagli spiriti benevoli
                    e dagli elementi in armonia.
                </p>
            </div>

            <div class="pub-card pub-card--caos">
                <div class="pub-card-icon"><i class="fas fa-moon"></i></div>
                <h3>Caos</h3>
                <p>
                    Caos è la forza della dissoluzione, del cambiamento e dell'istinto primordiale.
                    Chi ne segue la via crede che ogni struttura sia una prigione e che solo
                    abbattendo le convenzioni si possa raggiungere la vera libertà — o il vero potere.
                    I seguaci di Caos traggono forza dall'oscurità e dalla natura selvaggia.
                </p>
            </div>

        </div>

        <p>
            Cosmos e Caos non sono semplicemente il Bene e il Male del mondo di Crystal Tokyo.
            Entrambi hanno luci e ombre, entrambi attraggono personaggi mossi da motivazioni
            genuine. La guerra tra le due forze non ha un esito scontato — e la posizione
            di un personaggio può cambiare nel corso della storia.
        </p>
    </section>


    <!-- ── Sezione 5: Le Vie Magiche ─────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Le Vie Magiche</h2>
        <p>
            Da Cosmos e Caos discendono le <strong>Vie Magiche</strong>: percorsi spirituali,
            mistici e pratici che un personaggio può intraprendere per sviluppare il proprio potere.
            Ogni Via Magica è legata alla razza del personaggio e ne amplifica le caratteristiche
            naturali, conferendo abilità uniche e spesso visivamente spettacolari.
        </p>
        <p>
            Le Vie Magiche determinano quali incantesimi, tecniche di combattimento e poteri
            speciali il personaggio può acquisire man mano che cresce. Non esiste una Via
            superiore alle altre: tutto dipende da come il giocatore sceglie di interpretare
            il proprio personaggio e costruire la sua storia.
        </p>
    </section>


    <!-- ── Sezione 6: L'Impero Lunare ───────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>L'Impero Lunare</h2>
        <p>
            Nei secoli del Grande Gelo, mentre il mondo di superficie era sepolto sotto il ghiaccio,
            si narra che prosperasse un <strong>Impero Lunare</strong>: una civiltà sviluppatasi
            sulla Luna e nelle dimensioni adiacenti, custode di conoscenze magiche che l'umanità
            aveva quasi del tutto dimenticato.
        </p>
        <p>
            Il legame tra l'Impero Lunare e Crystal Tokyo è ancora oggi oggetto di studio e leggenda.
            Alcuni sostengono che la rinascita della città dopo la glaciazione non sia stata casuale,
            ma guidata da mani invisibili provenienti dalla Luna. Le prove sono frammentarie,
            le interpretazioni contrastanti — e le conseguenze di questa storia antica si riverberano
            ancora sulla città presente.
        </p>
        <p>
            <a href="/documentazione_main.php" class="pub-link">
                Approfondisci nel Manuale di Gioco &rsaquo;
            </a>
        </p>
    </section>


    <!-- ── CTA finale ───────────────────────────────────────────────────── -->
    <section class="pub-section pub-section--cta">
        <h2>Esplora Crystal Tokyo in prima persona</h2>
        <p>
            Leggere dell'ambientazione è solo il primo passo.
            La vera storia di Crystal Tokyo la scrivono i giocatori, ogni giorno, nelle chat.
        </p>
        <div class="pub-cta-btns">
            <button class="pub-btn pub-btn--gold" id="ctaRegBtn">Crea il tuo personaggio</button>
            <a href="/razze" class="pub-btn pub-btn--ghost">Scopri le Razze</a>
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
