<?php
/**
 * documentazione_main.php — Ambientazione & Regolamento
 *
 * Standalone: aperto in _blank dalle home page.
 * Menu accordion renderizzato inline via PHP.
 * Testo articoli e risultati ricerca caricati via fetch → documentazione_testo.php.
 * Non usa iframe.
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
        $links .= "<a href=\"#\" class=\"doc-link\" data-articolo=\"$art\">$titolo</a><br>\n";
    }
    gdrcd_query($res, 'free');
    if (!$links) return '';
    return "
<li>
  <div class=\"dropdownlink $classe\"><i class=\"fa fa-chevron-down\"></i></div>
  <ul class=\"submenuItems\"><li><p><center>$links</center></p></li></ul>
</li>\n";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>CT - Documentazione</title>
<link rel="stylesheet" href="themes/crystal/documentazione.css">
<link rel="stylesheet" href="themes/crystal/documentazione_menu.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<style>
/* override documentazione_menu.css z-index:-1 */
.pos-menu { z-index: 1; }
</style>
</head>
<body>

<div class="logo">
    <img src="themes/crystal/imgs/documentazione/titolo.png" alt="">
</div>

<div class="openmenu">
    <div class="pos-menu">
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
        <form id="doc-search-form" class="searchBar">
            <div align="center">
                <input id="doc-search-input" name="ricerca" style="width:200px;" placeholder="Inserire parola chiave"><br>
                <input type="submit" value="cerca">
            </div>
        </form>
    </div>
</div>

<div class="content">
    <div id="doc-content"></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function loadArticolo(id) {
    fetch('documentazione_testo.php?articolo=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) { document.getElementById('doc-content').innerHTML = html; });
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
        .then(function(html) { document.getElementById('doc-content').innerHTML = html; });
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
