<?php
/**
 * documentazione_main.php — Ambientazione & Regolamento
 *
 * Standalone: aperto in _blank dalle pagine pubbliche.
 * Menu accordion renderizzato inline via PHP.
 * Testo articoli e risultati ricerca caricati via fetch → documentazione_testo.php.
 */

session_start();
require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();

/**
 * Renderizza una sezione accordion del menu.
 * Restituisce HTML stringa o '' se non ci sono articoli.
 * href reale (non piu' "#"): un crawler senza JS puo' seguire il link e
 * atterrare sull'articolo server-renderizzato sotto; l'event delegation in
 * fondo alla pagina intercetta comunque il click per chi ha JS (nessun reload).
 */
function menuSection($tipo, $classe) {
    $res   = gdrcd_query(
        "SELECT articolo, titolo FROM regolamento WHERE tipo='$tipo' ORDER BY articolo",
        'result'
    );
    $links = '';
    while ($row = gdrcd_query($res, 'fetch')) {
        $art    = (int)$row['articolo'];
        $titolo = gdrcd_filter('out', $row['titolo']);
        $links .= "<a href=\"?articolo=$art\" class=\"doc-link\" data-articolo=\"$art\">$titolo</a>\n";
    }
    gdrcd_query($res, 'free');
    if (!$links) return '';
    return "
<li>
  <div class=\"dropdownlink $classe\"><i class=\"fas fa-chevron-down\"></i></div>
  <ul class=\"submenuItems\"><li>$links</li></ul>
</li>\n";
}

/**
 * Converte i tag BB-style di regolamento.testo in HTML.
 * Stessa trasformazione di documentazione_testo.php (duplicata volutamente:
 * quel file resta un frammento puro usato anche da Documentazione.jsx per gli
 * utenti loggati, non va toccato per questo SSR lato pagina pubblica).
 */
function renderTestoArticolo(string $testo): string {
    $testo = str_replace("\n",       "<br>",                                                                 $testo);
    $testo = str_replace("[BR]",     "<br>",                                                                 $testo);
    $testo = str_replace("[B]",      "<b>",                                                                  $testo);
    $testo = str_replace("[/B]",     "</b>",                                                                 $testo);
    $testo = str_replace("[I]",      "<i>",                                                                  $testo);
    $testo = str_replace("[/I]",     "</i>",                                                                 $testo);
    $testo = str_replace("[U]",      "<u>",                                                                  $testo);
    $testo = str_replace("[/U]",     "</u>",                                                                 $testo);
    $testo = str_replace("[C]",      "<div align='center'>",                                                 $testo);
    $testo = str_replace("[/C]",     "</div>",                                                               $testo);
    $testo = str_replace("[quote]",  "<table border=1 bordercolor=#a9cded align=center width=80%><tr><td>", $testo);
    $testo = str_replace("[/quote]", "</td></tr></table>",                                                   $testo);
    $testo = str_replace("[D]",      "<div align='right'>",                                                  $testo);
    $testo = str_replace("[/D]",     "</div>",                                                               $testo);
    return $testo;
}

/** Estrae un estratto testuale pulito (niente tag BB/HTML, alcuni articoli usano entrambi) per la meta description. */
function estrattoDescrizione(string $testo, int $max = 155): string {
    $plain = str_replace(["\n", "\r"], ' ', $testo);
    $plain = preg_replace('#\[/?[A-Za-z]+\]#', '', $plain);
    $plain = strip_tags($plain);
    $plain = trim(preg_replace('/\s+/', ' ', $plain));
    return mb_strlen($plain) > $max ? mb_substr($plain, 0, $max - 1) . '…' : $plain;
}

/* ── Articolo richiesto in deep-link (?articolo=N): SSR per SEO ──────────── */
$articolo_id  = isset($_GET['articolo']) ? (int)$_GET['articolo'] : 0;
$articolo_row = $articolo_id ? gdrcd_query("SELECT articolo, tipo, titolo, testo FROM regolamento WHERE articolo=$articolo_id LIMIT 1") : null;

if ($articolo_row) {
    $page_title       = gdrcd_filter('out', $articolo_row['titolo']) . ' — Regolamento Crystal Tokyo GDR';
    $page_description = estrattoDescrizione($articolo_row['testo']);
    $page_canonical    = 'https://crystaltokyo.it/documentazione_main.php?articolo=' . $articolo_id;
} else {
    $page_title       = 'Regolamento e Ambientazione — Crystal Tokyo GDR';
    $page_description = 'Il regolamento completo di Crystal Tokyo GDR: ambientazione, sistema di gioco, combattimento, manuali e primi passi per chi inizia questo gioco di ruolo online.';
    $page_canonical    = 'https://crystaltokyo.it/documentazione_main.php';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?></title>
<meta name="description" content="<?= htmlspecialchars($page_description) ?>">
<link rel="canonical" href="<?= htmlspecialchars($page_canonical) ?>">
<?php if ($articolo_row): ?>
<script type="application/ld+json">
<?= json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => gdrcd_filter('out', $articolo_row['titolo']),
    'description'   => $page_description,
    'url'           => $page_canonical,
    'inLanguage'    => 'it',
    'isPartOf'      => ['@type' => 'WebSite', 'name' => 'Crystal Tokyo GDR', 'url' => 'https://crystaltokyo.it'],
    'publisher'     => ['@type' => 'Organization', 'name' => 'Crystal Tokyo GDR', 'url' => 'https://crystaltokyo.it'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<?php endif; ?>
<link rel="stylesheet" href="themes/crystal/documentazione.css">
<link rel="stylesheet" href="themes/crystal/documentazione_menu.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="doc-layout">

    <!-- ── Sidebar: logo + menu accordion + ricerca ────────────────── -->
    <aside class="doc-sidebar">

        <div class="doc-logo">
            <img src="themes/crystal/imgs/documentazione/titolo.png"
                 alt="Crystal Tokyo — Documentazione">
        </div>

        <nav class="doc-nav" aria-label="Sezioni documentazione">
            <ul class="accordion-menu">
                <?php
                echo menuSection('ambientazione', 'ambientazione');
                echo menuSection('regolamento',   'regolamento');
                echo menuSection('primipassi',    'primi_passi');
                echo menuSection('manuali',       'manuale');
                echo menuSection('combattimento', 'combattimento');
                echo menuSection('staff',         'staff');
                ?>
            </ul>
        </nav>

        <form id="doc-search-form" class="doc-search">
            <input id="doc-search-input" name="ricerca"
                   placeholder="Cerca nella documentazione…" autocomplete="off">
            <button type="submit" aria-label="Cerca"><i class="fas fa-search"></i></button>
        </form>

    </aside>

    <!-- ── Area contenuto principale ───────────────────────────────── -->
    <main class="doc-content" id="doc-main">
        <div id="doc-content">
            <?php if ($articolo_row): ?>
            <div class="testo">
                <div class="titoli" style="padding-top:20px;padding-bottom:5px;"><?= gdrcd_filter('out', $articolo_row['titolo']) ?></div><br>
                <?= renderTestoArticolo($articolo_row['testo']) ?>
            </div>
            <?php else: ?>
            <p class="doc-welcome">Seleziona una sezione dal menu per iniziare a leggere.</p>
            <?php endif; ?>
        </div>
    </main>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
/**
 * pushState aggiorna la barra indirizzi a ?articolo=N senza reload: mantiene
 * l'URL condivisibile/crawlabile (il server-side render di questo stesso file
 * copre il caricamento diretto), mentre la navigazione via click resta istantanea.
 */
function loadArticolo(id, pushState) {
    fetch('documentazione_testo.php?articolo=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('doc-content').innerHTML = html;
            if (pushState !== false) history.pushState({ articolo: id }, '', '?articolo=' + id);
            /* Su mobile scrolla automaticamente all'area contenuto */
            if (window.innerWidth <= 768) {
                document.getElementById('doc-main').scrollIntoView({ behavior: 'smooth' });
            }
        });
}

/* Event delegation: gestisce link data-articolo nel menu e nei risultati ricerca */
document.addEventListener('click', function(e) {
    var t = e.target.closest('.doc-link');
    if (t) { e.preventDefault(); loadArticolo(t.dataset.articolo); }
});

/* Tasto indietro/avanti del browser: ricarica l'articolo corrispondente allo stato */
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.articolo) loadArticolo(e.state.articolo, false);
});

document.getElementById('doc-search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var q = document.getElementById('doc-search-input').value;
    fetch('documentazione_testo.php?op=search', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    'ricerca=' + encodeURIComponent(q),
    })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('doc-content').innerHTML = html;
            if (window.innerWidth <= 768) {
                document.getElementById('doc-main').scrollIntoView({ behavior: 'smooth' });
            }
        });
});

$(function() {
    var Accordion = function(el, multiple) {
        this.el       = el || {};
        this.multiple = multiple || false;
        this.el.find('.dropdownlink').on('click', { el: this.el, multiple: this.multiple }, this.dropdown);
    };
    Accordion.prototype.dropdown = function(e) {
        var $el   = e.data.el,
            $this = $(this),
            $next = $this.next();
        $next.slideToggle();
        $this.parent().toggleClass('open');
        if (!e.data.multiple) {
            $el.find('.submenuItems').not($next).slideUp().parent().removeClass('open');
        }
    };
    new Accordion($('.accordion-menu'), false);

    // Se la pagina e' stata aperta direttamente su ?articolo=N (deep-link/SEO),
    // espande la sezione corrispondente cosi' il menu riflette il contenuto gia' visibile.
    <?php if ($articolo_row): ?>
    var $activeLink = $('.doc-link[data-articolo="<?= (int)$articolo_id ?>"]');
    if ($activeLink.length) {
        var $subMenu = $activeLink.closest('.submenuItems');
        $subMenu.show();
        $subMenu.parent('li').addClass('open');
    }
    <?php endif; ?>
});
</script>
</body>
</html>
