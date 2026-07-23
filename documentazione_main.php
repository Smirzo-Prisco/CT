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
        $links .= "<a href=\"#\" class=\"doc-link\" data-articolo=\"$art\">$titolo</a>\n";
    }
    gdrcd_query($res, 'free');
    if (!$links) return '';
    return "
<li>
  <div class=\"dropdownlink $classe\"><i class=\"fas fa-chevron-down\"></i></div>
  <ul class=\"submenuItems\"><li>$links</li></ul>
</li>\n";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Regolamento e Ambientazione — Crystal Tokyo GDR</title>
<meta name="description" content="Il regolamento completo di Crystal Tokyo GDR: ambientazione, sistema di gioco, combattimento, manuali e primi passi per chi inizia questo gioco di ruolo online.">
<link rel="canonical" href="https://crystaltokyo.it/documentazione_main.php">
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
            <p class="doc-welcome">Seleziona una sezione dal menu per iniziare a leggere.</p>
        </div>
    </main>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function loadArticolo(id) {
    fetch('documentazione_testo.php?articolo=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) {
            document.getElementById('doc-content').innerHTML = html;
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
});
</script>
</body>
</html>
