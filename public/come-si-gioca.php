<?php
/**
 * come-si-gioca.php — Pagina pubblica "Come Si Gioca" di Crystal Tokyo GDR.
 *
 * Guida pratica per i potenziali nuovi giocatori:
 *   - Cos'è un play by chat (PbC)
 *   - Creazione del personaggio
 *   - Statistiche e sistema a dadi
 *   - Punti esperienza e punti shin
 *   - Mestieri
 *   - Gilde (gruppi creati dai giocatori)
 *   - FAQ con JSON-LD FAQPage (Google rich results)
 *
 * URL pulito: /come-si-gioca → location block nginx
 *
 * Dipendenze condivise: _head.php, _nav.php, _footer.php, _modals.php
 */

require_once __DIR__ . '/_head.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/_footer.php';
require_once __DIR__ . '/_modals.php';

/*
 * ── FAQ ──────────────────────────────────────────────────────────────────────
 * Array con domande e risposte frequenti.
 * Usato sia per il rendering HTML che per il JSON-LD FAQPage (Google rich results).
 */
$faq = [
    [
        'q' => 'Crystal Tokyo GDR è gratuito?',
        'a' => 'Sì, completamente. Non esistono abbonamenti, acquisti in-app o contenuti a pagamento. '
             . 'Registrarsi e giocare è gratis, ora e sempre.',
    ],
    [
        'q' => 'Devo scaricare qualcosa per giocare?',
        'a' => 'No. Crystal Tokyo GDR è interamente basato su browser: basta una connessione internet '
             . 'e un dispositivo con un browser moderno (Chrome, Firefox, Safari, Edge). '
             . 'Nessun download, nessuna installazione.',
    ],
    [
        'q' => 'Come funziona il gioco di ruolo testuale?',
        'a' => 'Nel play by chat, ogni giocatore descrive le azioni e i dialoghi del proprio personaggio '
             . 'in una chat condivisa. Le scene si svolgono in tempo reale: i personaggi interagiscono, '
             . 'creano storie e affrontano conflitti attraverso il testo. I dadi entrano in gioco nei '
             . 'combattimenti e nelle situazioni ad alto rischio.',
    ],
    [
        'q' => 'Come si crea un personaggio?',
        'a' => 'La registrazione guidata ti fa scegliere nome, razza e mestiere del personaggio. '
             . 'Le statistiche di base sono uguali per tutti all\'inizio: 100 punti salute, '
             . '100 punti integrità e 4 caratteristiche (Potere, Destrezza, Mente, Tempra). '
             . 'Crescere richiede tempo e gioco — non esistono scorciatoie a pagamento.',
    ],
    [
        'q' => 'Cosa sono i punti shin?',
        'a' => 'I punti shin sono il riconoscimento della qualità del tuo gioco. Lo staff e gli altri '
             . 'giocatori possono assegnare shin per un\'interpretazione particolarmente brillante, '
             . 'per aver creato trame interessanti o per contributi straordinari alla storia collettiva. '
             . 'I shin si usano per migliorare il personaggio, al pari dei punti esperienza.',
    ],
    [
        'q' => 'Posso giocare senza partecipare ai combattimenti?',
        'a' => 'Assolutamente sì. Crystal Tokyo GDR è prima di tutto un gioco narrativo: molti personaggi '
             . 'vivono storie ricchissime senza mai combattere. I mestieri narrativi sono pensati '
             . 'per chi preferisce la diplomazia, l\'esplorazione, il commercio o la trama pura.',
    ],
    [
        'q' => 'Cos\'è una gilda?',
        'a' => 'Le gilde sono gruppi creati dai giocatori stessi: si organizzano in autonomia, '
             . 'scelgono i propri obiettivi e tematiche, e partecipano attivamente alle trame del gioco. '
             . 'Non esiste un\'ideologia predefinita per le gilde: ogni gilda costruisce la propria '
             . 'identità, dai clan di guerrieri alle confraternite di maghi, dalle organizzazioni '
             . 'criminali ai circoli culturali.',
    ],
    [
        'q' => 'C\'è uno staff che gestisce le storie?',
        'a' => 'Sì. Lo staff narrativo di Crystal Tokyo GDR propone eventi, trame ufficiali e scenari '
             . 'che coinvolgono l\'intera comunità. Ma le storie più belle nascono spesso dall\'iniziativa '
             . 'dei giocatori stessi — lo staff è lì per supportarle, non per controllarle.',
    ],
    [
        'q' => 'Posso giocare da mobile?',
        'a' => 'Sì, il sito è ottimizzato per dispositivi mobili. Puoi giocare comodamente da smartphone '
             . 'o tablet, anche se per sessioni di gioco lunghe un computer offre un\'esperienza migliore.',
    ],
];

/* ── JSON-LD: FAQPage + breadcrumb ───────────────────────────────────────── */
$json_ld = [
    '@context'   => 'https://schema.org',
    '@type'      => ['WebPage', 'FAQPage'],
    'name'       => 'Come Si Gioca — Crystal Tokyo GDR',
    'description'=> 'Guida completa su come giocare a Crystal Tokyo GDR: personaggio, statistiche, combattimenti a dadi, mestieri, gilde e tutto ciò che serve per iniziare.',
    'url'        => 'https://crystaltokyo.it/come-si-gioca',
    'isPartOf'   => [
        '@type' => 'WebSite',
        'name'  => 'Crystal Tokyo GDR',
        'url'   => 'https://crystaltokyo.it',
    ],
    'breadcrumb' => [
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',          'item' => 'https://crystaltokyo.it'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Come Si Gioca', 'item' => 'https://crystaltokyo.it/come-si-gioca'],
        ],
    ],
    /* Domande e risposte in formato FAQPage per Google rich results */
    'mainEntity' => array_map(fn($f) => [
        '@type'          => 'Question',
        'name'           => $f['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => $f['a'],
        ],
    ], $faq),
];

/* ── <head> ──────────────────────────────────────────────────────────────── */
public_head(
    'Come Si Gioca',
    'Guida completa su come giocare a Crystal Tokyo GDR: crea il tuo personaggio, scegli razza e mestiere, accumula esperienza e shin, entra nelle gilde.',
    'https://crystaltokyo.it/come-si-gioca',
    'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    $json_ld
);
?>
<body>
<?php public_nav('come-si-gioca'); ?>
<?php public_modals(); ?>

<!-- ── HERO ──────────────────────────────────────────────────────────────── -->
<section class="pub-hero pub-hero--short">
    <div class="pub-hero-content">
        <h1>Come Si Gioca</h1>
        <p class="pub-hero-sub">
            Tutto quello che ti serve sapere per iniziare a giocare —
            dalla creazione del personaggio alle prime trame.
        </p>
    </div>
</section>


<!-- ── CONTENUTO PRINCIPALE ──────────────────────────────────────────────── -->
<main class="pub-page-content">

    <!-- Breadcrumb -->
    <nav class="pub-breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> &rsaquo; <span>Come Si Gioca</span>
    </nav>


    <!-- ── Sezione 1: Il play by chat ──────────────────────────────────── -->
    <section class="pub-section">
        <h2>Cos'è un gioco di ruolo play by chat</h2>
        <p>
            In un <strong>play by chat</strong> (PbC), ogni giocatore scrive le azioni, i pensieri
            e i dialoghi del proprio personaggio in una chat condivisa con altri giocatori.
            Non c'è un master che legge le istruzioni a voce: tutto avviene attraverso il testo,
            in tempo reale, in un ambiente condiviso.
        </p>
        <p>
            Ogni chat di Crystal Tokyo rappresenta un luogo fisico della città: una strada,
            un bar, un edificio in rovina, un porto. I personaggi si incontrano, parlano,
            stringono alleanze, si scontrano — e costruiscono storie che durano nel tempo.
        </p>
        <p>
            Non serve esperienza nel gioco di ruolo. L'unica abilità richiesta è la voglia
            di raccontare una storia — il resto si impara giocando.
        </p>
    </section>


    <!-- ── Sezione 2: Creazione del PG ─────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Crea il tuo personaggio</h2>
        <p>
            La registrazione su Crystal Tokyo GDR ti chiede di definire le basi del tuo personaggio:
        </p>

        <div class="pub-cards pub-cards--3">

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-id-card"></i></div>
                <h3>Nome e identità</h3>
                <p>
                    Scegli il nome e il cognome del personaggio (non il tuo nome reale).
                    Il nome è l'identità con cui gli altri giocatori ti conosceranno nella città.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-dna"></i></div>
                <h3>Razza</h3>
                <p>
                    La razza determina la natura magica del personaggio e le abilità che potrà
                    sviluppare. Porta con sé tendenze naturali ereditate dalla stirpe, ma queste
                    sono inclinazioni, non destini: l'allineamento del personaggio dipende
                    esclusivamente dalle scelte del giocatore.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-hammer"></i></div>
                <h3>Mestiere</h3>
                <p>
                    Il mestiere definisce il ruolo sociale e narrativo del personaggio.
                    Alcuni mestieri sono più orientati al combattimento, altri alla diplomazia
                    o alla costruzione delle trame.
                </p>
            </div>

        </div>
    </section>


    <!-- ── Sezione 3: Statistiche ───────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Le statistiche del personaggio</h2>
        <p>
            Ogni personaggio parte con le stesse statistiche base, così che nessun
            personaggio nuovo sia avvantaggiato o svantaggiato rispetto agli altri.
        </p>

        <!-- Tabella statistiche base -->
        <div class="pub-stats-grid">
            <div class="pub-stat-item">
                <span class="pub-stat-value">100</span>
                <span class="pub-stat-label">Punti Salute</span>
                <span class="pub-stat-desc">Salute fisica del personaggio</span>
            </div>
            <div class="pub-stat-item">
                <span class="pub-stat-value">100</span>
                <span class="pub-stat-label">Punti Integrità</span>
                <span class="pub-stat-desc">Salute mentale del personaggio</span>
            </div>
            <div class="pub-stat-item">
                <span class="pub-stat-value">4</span>
                <span class="pub-stat-label">Caratteristiche</span>
                <span class="pub-stat-desc">Potere · Destrezza · Mente · Tempra</span>
            </div>
        </div>

        <p>
            Le quattro <strong>caratteristiche</strong> (Potere, Destrezza, Mente, Tempra)
            entrano in gioco nei lanci di dadi: influenzano i tuoi attacchi, le difese
            e le abilità speciali della razza. Crescono acquistando livelli con i punti esperienza.
        </p>
    </section>


    <!-- ── Sezione 4: I livelli e le abilità ───────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>I livelli e le abilità</h2>
        <p>
            Man mano che le caratteristiche del personaggio crescono, il sistema calcola
            in automatico il tuo <strong>livello</strong>: un numero che riflette la potenza
            complessiva raggiunta. Non serve fare nulla di speciale — basta giocare.
        </p>
        <p>
            Il livello dipende dalla somma delle quattro caratteristiche (Potere, Destrezza,
            Mente, Tempra). Più è alto, più il personaggio può acquistare
            <strong>abilità</strong> di grado superiore: attacchi più potenti,
            difese più solide, poteri speciali avanzati.
        </p>
        <p>
            Le abilità si dividono in più categorie — offensive, difensive, mentali,
            poteri speciali — e si scelgono liberamente all'interno di quelle
            rese disponibili dal livello raggiunto. Ogni scelta contribuisce a rendere
            il personaggio unico e difficile da replicare.
        </p>
    </section>


    <!-- ── Sezione 5: Role e turni ───────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Role e turni</h2>
        <p>
            Una <strong>role</strong> è una sessione di gioco strutturata che si avvia
            quando i personaggi presenti in una chat decidono di aprirla. Da quel momento
            la sessione è registrata, numerata turno per turno e visibile a tutti i partecipanti.
        </p>
        <p>
            Ogni <strong>turno</strong> funziona così: ciascun partecipante scrive la
            propria azione narrativa e, se lo desidera, attiva le proprie abilità.
            Quando tutti hanno agito, il turno si chiude: il sistema elabora l'esito,
            mostra un <strong>riepilogo automatico</strong> di tutto ciò che è successo
            e scala automaticamente i punti salute e integrità in base ai danni subiti.
            Poi si passa al turno successivo.
        </p>
        <p>
            Le role concluse restano accessibili a tempo indefinito: ogni giocatore può
            rileggerle come un archivio vivo della propria storia nel gioco.
        </p>
    </section>


    <!-- ── Sezione 6: Combattimento e dadi ──────────────────────────────── -->
    <section class="pub-section">
        <h2>Combattimento e dadi</h2>
        <p>
            Nei combattimenti e nelle situazioni ad alto rischio entrano in gioco i
            <strong>dadi</strong> — ma non devi lanciarli tu. Il sistema li tira in
            automatico nel momento in cui compi un'azione: basta scegliere cosa fare,
            e il risultato arriva istantaneamente, già elaborato in base alle tue
            caratteristiche e all'abilità usata.
        </p>
        <p>
            Quando qualcuno ti attacca, ricevi un <strong>avviso</strong> direttamente
            in chat, a cui puoi rispondere scegliendo come reagire — deviare il colpo,
            assorbirlo, contrattaccare. Il tutto si svolge all'interno del turno corrente,
            in modo trasparente e leggibile per tutti i partecipanti.
        </p>
        <p>
            Dialoghi, esplorazioni e costruzione delle trame non richiedono dadi: il sistema
            interviene solo quando c'è davvero qualcosa in gioco, rendendo ogni lancio
            significativo.
        </p>
        <p>
            <a href="/documentazione_main.php" class="pub-link" target="_blank" rel="noopener">
                Leggi le regole complete del combattimento nel Manuale di Gioco &rsaquo;
            </a>
        </p>
    </section>


    <!-- ── Sezione 5: Esperienza e shin ─────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Come cresce il personaggio</h2>
        <p>
            In Crystal Tokyo esistono due valute di crescita, entrambe acquisibili solo giocando:
        </p>

        <div class="pub-cards pub-cards--2">

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Punti Esperienza</h3>
                <p>
                    Si accumulano automaticamente in base alla <strong>frequenza di gioco</strong>.
                    Più partecipi, più il tuo personaggio cresce. Servono a migliorare le
                    caratteristiche e a sbloccare nuove abilità.
                </p>
            </div>

            <div class="pub-card">
                <div class="pub-card-icon"><i class="fas fa-star"></i></div>
                <h3>Punti Shin</h3>
                <p>
                    Si ottengono per la <strong>qualità dell'interpretazione</strong>: trame
                    originali, dialoghi ben costruiti, contributi significativi alla storia
                    collettiva. Assegnati dallo staff e dai giocatori più esperti.
                </p>
            </div>

        </div>
    </section>


    <!-- ── Sezione 6: Le Gilde ──────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Le Gilde</h2>
        <p>
            Le <strong>gilde</strong> sono il cuore della vita sociale di Crystal Tokyo.
            Sono gruppi creati dai giocatori stessi — non dallo staff — con obiettivi,
            gerarchie e tematiche scelte in piena autonomia.
        </p>
        <p>
            Una gilda può essere qualsiasi cosa: un clan di guerrieri d'élite, una rete di spionaggio,
            un circolo di studiosi della magia, un'organizzazione criminale, una congregazione religiosa.
            I membri si organizzano da soli, scelgono il loro posto nella storia della città e
            partecipano — talvolta da protagonisti — alle grandi trame del gioco.
        </p>
        <p>
            Non esiste un'ideologia predefinita: ogni gilda costruisce la propria identità
            attraverso il gioco.
        </p>
    </section>


    <!-- ── Sezione 7: Passi per iniziare ────────────────────────────────── -->
    <section class="pub-section">
        <h2>Come iniziare in tre passi</h2>

        <ol class="pub-steps">
            <li>
                <span class="pub-step-num">1</span>
                <div>
                    <strong>Registrati</strong> — scegli nome, razza e mestiere del tuo personaggio.
                    È gratuito e richiede meno di cinque minuti.
                </div>
            </li>
            <li>
                <span class="pub-step-num">2</span>
                <div>
                    <strong>Entra in una chat</strong> — scegli un luogo della città e presentati.
                    Gli altri giocatori ti accoglieranno: la comunità di Crystal Tokyo è abituata
                    ai nuovi arrivi.
                </div>
            </li>
            <li>
                <span class="pub-step-num">3</span>
                <div>
                    <strong>Inizia a raccontare</strong> — descrivi le azioni del tuo personaggio,
                    dialoga con gli altri, esplora la città. Il resto viene da sé.
                </div>
            </li>
        </ol>

        <div class="pub-cta-btns" style="margin-top:2rem;">
            <button class="pub-btn pub-btn--gold" id="ctaRegBtn">Registrati ora — è gratis</button>
        </div>
    </section>


    <!-- ── FAQ ──────────────────────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Domande frequenti</h2>

        <div class="pub-faq">
            <?php foreach ($faq as $item): ?>
            <div class="faq-item">
                <button class="faq-q" type="button">
                    <?= htmlspecialchars($item['q']) ?>
                </button>
                <div class="faq-a" style="display:none;">
                    <p><?= htmlspecialchars($item['a']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
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
