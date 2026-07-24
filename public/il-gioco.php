<?php
/**
 * il-gioco.php — Pagina pubblica "Il Gioco" di Crystal Tokyo GDR.
 *
 * Presenta il gioco ai potenziali nuovi giocatori:
 *   - Cos'è Crystal Tokyo GDR (storia, piattaforma)
 *   - La storia in breve (Sorgente → Silver Millennium → Mana Cerace → Risveglio → 3060)
 *   - Allineamento (scomparsa di Cosmos/Caos — tendenze di razza, non destini)
 *   - Le Razze (sette razze flat, nessuna gerarchia)
 *   - Le Gilde (gruppi creati dai giocatori)
 *   - Come funziona il gioco (dadi, stats, shin)
 *   - CTA registrazione
 *
 * URL pulito: /il-gioco → location block nginx
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
    // "Il Gioco" da solo non conteneva nessuna delle parole chiave generiche
    // (Search Console: zero impressioni su "gdr play by chat" e varianti),
    // nonostante questa sia la pagina con più contenuto testuale sul tema.
    'Il Gioco: Cos\'è un GDR Play by Chat',
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
            Le trame si sviluppano grazie all'interazione tra i giocatori e al supporto
            dello staff narrativo. Crystal Tokyo è attivo da oltre vent'anni e continua
            a evolversi.
        </p>
    </section>


    <!-- ── Sezione 2: La storia ──────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>La storia in breve</h2>
        <p>
            All'origine di tutto non vi è un dio, ma qualcosa di più antico: la <strong>Sorgente</strong>.
            Da essa hanno preso forma lo spazio, il tempo, la magia, la vita e la morte.
            Ciò che gli esseri chiamano "magia" ne è solo una minima parte.
        </p>
        <p>
            Per secoli il Sistema Solare fu un impero pacifico, il <strong>Silver Millennium</strong>,
            sotto l'egida della Famiglia Imperiale della Luna. Le razze magiche ne erano le guardiane,
            ognuna con una natura, un potere e un ruolo diversi. Ma la guerra e le faide antiche
            spezzarono l'equilibrio, e l'Impero crollò. La Terra fu l'unico pianeta a sopravvivere.
        </p>
        <p>
            Alle soglie del XXI secolo emerse il <strong>Mana Cerace</strong>: le paure degli uomini
            presero forma e divennero letali. L'alleanza tra le razze che avrebbe potuto fermarlo
            non si trovò — le faide erano troppo vecchie. L'unica alternativa fu congelare il mondo.
            Il ghiaccio coprì ogni superficie per <strong>mille anni</strong>, ibernando l'umanità intera.
        </p>
        <p>
            Un cristallo antico risvegliò il pianeta. Il fuoco sciolse il ghiaccio, i mari tornarono
            ad agitarsi. L'umanità si svegliò con i ricordi intatti ma senza la Paura — e senza
            memoria della magia. Tokyo fu la prima città a rifiorire, diventando centro di una nuova civiltà.
        </p>
        <p>
            Oggi è il <strong>3060</strong>. Tokyo ha tecnologie moderne, costumi metropolitani,
            una Corte imperiale — vita ordinaria in apparenza. Ma sotto la superficie, la magia
            si è risvegliata di nuovo, lenta e personale. Le razze sono tornate.
            Le faide antiche si sono riaccese. E al centro di tutto, come sempre, c'è Tokyo.
        </p>
        <p>
            <a href="/ambientazione" class="pub-link">Approfondisci l'ambientazione completa &rsaquo;</a>
        </p>
    </section>


    <!-- ── Sezione 3: Allineamento ──────────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Allineamento</h2>
        <p>
            Con la scomparsa di Cosmos e Caos, la magia che un tempo definiva l'allineamento
            non esiste più. Non c'è un positivo, un negativo o un neutrale imposto dall'esterno —
            nessuna divinità a orientare la bussola morale, nessuna appartenenza che vincoli le scelte.
        </p>
        <p>
            L'allineamento di ogni personaggio dipende esclusivamente da chi è: dal suo carattere,
            dalla sua storia, dalle scelte che compie giorno per giorno. Può avere sfumature
            contraddittorie, può cambiare nel tempo, può non essere facilmente etichettabile —
            e va bene così.
        </p>
        <p>
            Ogni razza mantiene delle <strong>tendenze naturali</strong> ereditate dalla propria stirpe,
            ma queste sono inclinazioni, non destini. I Demoni hanno una tendenza negativa,
            i Celestiali positiva — ma sta al giocatore decidere se assecondare questa inclinazione
            o contrastarla. Un Demone potrebbe opporsi ogni giorno alla tentazione di prevaricare;
            un Celestiale potrebbe sfruttare le sue capacità benevole per manipolare gli altri.
            Alcuni abbinamenti saranno più difficili di altri, ma la scelta è sempre del giocatore.
        </p>
    </section>


    <!-- ── Sezione 4: Le Razze ──────────────────────────────────────────── -->
    <section class="pub-section">
        <h2>Le Razze</h2>
        <p>
            In Crystal Tokyo esistono <strong>sette razze</strong>, ognuna con caratteristiche,
            origini e poteri propri: <strong>Adamanti</strong>, <strong>Celestiali</strong>,
            <strong>Elementali</strong>, <strong>Beast</strong>, <strong>Fiori</strong>,
            <strong>Demoni</strong> e <strong>Lancaster</strong>.
        </p>
        <p>
            Non esiste una gerarchia tra le razze, né un percorso predestinato.
            La razza definisce la natura profonda del personaggio e le sue inclinazioni naturali,
            ma non ne determina il destino — quello è in mano esclusivamente al giocatore.
        </p>
        <p>
            <a href="/razze" class="pub-btn pub-btn--ghost">Scopri tutte le razze &rsaquo;</a>
        </p>
    </section>


    <!-- ── Sezione 5: Le Gilde ──────────────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Le Gilde</h2>
        <p>
            Le <strong>Gilde</strong> sono gruppi creati dai giocatori all'interno della trama.
            Possono nascere per qualsiasi scopo — un'organizzazione criminale, una compagnia di
            mercenari, un circolo di studiosi, una banda di strada, una confraternita segreta.
            Non esistono limiti di tipo o di natura: una Gilda può essere legale o illegale,
            pubblica o clandestina, con una gerarchia rigida o completamente orizzontale.
        </p>
        <p>
            La struttura, il regolamento, le mansioni e gli obiettivi sono decisi interamente
            dai giocatori che la compongono. Per fondarne una sono necessari almeno
            <strong>3 personaggi</strong>.
        </p>
        <p>
            A renderla possibile è un sistema di gestione che lo staff ha messo per la prima
            volta nelle mani della community: fondazione autonoma, gerarchia interna
            configurabile, statuto e immagine personalizzabili, piena libertà di decidere chi
            accogliere e chi allontanare. Nessuna approvazione preventiva, nessun'infrastruttura
            calata dall'alto — solo un nucleo di almeno tre personaggi disposti a scrivere
            insieme la propria storia.
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


    <!-- ── Sezione 7: La piattaforma ────────────────────────────────────── -->
    <section class="pub-section pub-section--highlight">
        <h2>Una piattaforma moderna</h2>
        <p>
            Crystal Tokyo è più di un motore di gioco ereditato dal passato: negli ultimi anni
            la piattaforma è stata riscritta quasi interamente per offrire un'esperienza fluida,
            reattiva e stabile — indipendentemente dal dispositivo e dalla connessione.
        </p>
        <p>
            <strong>Se non ti interessa la tecnologia</strong>, l'effetto concreto è questo:
            il vecchio sito funzionava come un ristorante in cui ogni volta che volevate
            cambiare piatto, il cameriere vi faceva uscire e rientrare dalla porta principale.
            Ogni click causava il ricaricamento completo della pagina.
            Oggi le colonne laterali, i menu e la struttura vengono caricati una volta sola.
            Quando navigate tra scheda personaggio, forum, mappa e messaggi, cambia solo
            il contenuto al centro — senza lampeggi, senza attese.
        </p>
        <p>
            La chat funziona in modo altrettanto diverso: invece di interrogare il server ogni
            pochi secondi sperando in novità, esiste ora una linea diretta sempre aperta.
            I messaggi arrivano nell'istante esatto in cui vengono scritti.
        </p>
        <p>
            <strong>Per chi vuole i dettagli tecnici:</strong> il frontend è stato migrato
            verso una Single Page Application basata su React, con il backend PHP ridotto
            ad API JSON pure. La chat usa connessioni WebSocket persistenti, suddivise per
            stanza, utente e presenza globale — zero polling, aggiornamenti istantanei.
        </p>
        <table class="pub-tech-table" aria-label="Confronto prima e dopo">
            <thead>
                <tr>
                    <th>Aspetto</th>
                    <th>Prima</th>
                    <th>Adesso</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Navigazione</td>
                    <td>Ricaricamento completo ad ogni click</td>
                    <td>Solo il contenuto centrale cambia</td>
                </tr>
                <tr>
                    <td>Chat in tempo reale</td>
                    <td>Polling ogni 5–30 secondi</td>
                    <td>WebSocket push istantaneo</td>
                </tr>
                <tr>
                    <td>Frontend</td>
                    <td>Rendering PHP classico</td>
                    <td>React — interfaccia reattiva</td>
                </tr>
            </tbody>
        </table>
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
