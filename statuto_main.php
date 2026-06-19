<?php
/**
 * statuto_main.php — Pagina statuto gilda o mestiere
 *
 * Standalone: niente header.inc.php, niente iframe.
 * Menu accordion renderizzato inline via PHP.
 * Testo articolo caricato via fetch → statuto_testo.php?articolo=N
 * che restituisce solo il frammento HTML del contenuto.
 */

session_start();
require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();

$id  = (int)($_GET['id']  ?? 0);
$id2 = (int)($_GET['id2'] ?? 0);

// Mappa id gilda → immagine di sfondo
$bgGilde   = [1=>'paladini.png',2=>'demoni.png',3=>'setta.png',4=>'custodi.png',
               5=>'guardiani.png',6=>'fiori.png',7=>'lancaster.png',8=>'muryu.png',9=>'manuale_cittadini.png'];
$bgMestieri = [1=>'icc.png',2=>'tae.png',3=>'magic.png',4=>'pandora.png',6=>'corte.png',10=>'corte.png'];

$background = $bgGilde[$id] ?? ($bgMestieri[$id2] ?? null);
if (!$background) exit;

$firstArticolo = 0;

/**
 * Renderizza una sezione accordion del menu.
 * Restituisce HTML stringa o '' se non ci sono articoli.
 * Salva in $firstArticolo il primo articolo trovato in assoluto.
 */
function menuSection($tipo, $campo, $valore, $classe) {
    global $firstArticolo;
    $valore = (int)$valore;
    $res    = gdrcd_query("SELECT articolo, titolo FROM statuti WHERE tipo='$tipo' AND $campo='$valore' ORDER BY articolo", 'result');
    $links  = '';
    while ($row = gdrcd_query($res, 'fetch')) {
        $art   = (int)$row['articolo'];
        $tit   = gdrcd_filter('out', $row['titolo']);
        if (!$firstArticolo) $firstArticolo = $art;
        $links .= "<a href=\"#\" onclick=\"loadArticolo($art);return false;\">$tit</a><br>\n";
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
<link rel="stylesheet" href="themes/crystal/statuti.css">
<link rel="stylesheet" href="themes/crystal/statuto_menu.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
</head>
<body>

<div id="main-container">

  <!-- Immagine di sfondo -->
  <div class="chapter">
    <img src="themes/crystal/imgs/statuti/<?= $background ?>">
  </div>

  <!-- Menu accordion (ex iframe openmenuframe) -->
  <div class="openmenu">
    <div class="pos-menu">
      <ul class="accordion-menu">
        <?php if ($id > 0 && $id < 9):
            echo menuSection('storia',    'id_gilda', $id, 'ambientazione');
            echo menuSection('statuto',   'id_gilda', $id, 'regolamento');
            echo menuSection('skill',     'id_gilda', $id, 'primi_passi');
            echo menuSection('requisiti', 'id_gilda', $id, 'manuale');
        endif; ?>
        <?php if ($id == 9):
            echo menuSection('cittadini', 'id_gilda', $id, 'cittadini');
            echo menuSection('sit',       'id_gilda', $id, 'sit');
            echo menuSection('wiccan',    'id_gilda', $id, 'wikkan');
            echo menuSection('scorpion',  'id_gilda', $id, 'scorpion');
        endif; ?>
        <?php if ($id2 > 0):
            echo menuSection('storia',    'id_mestiere', $id2, 'statutom');
            echo menuSection('statuto',   'id_mestiere', $id2, 'descrizione');
            echo menuSection('skill',     'id_mestiere', $id2, 'cariche');
            echo menuSection('requisiti', 'id_mestiere', $id2, 'specifiche');
        endif; ?>
      </ul>
    </div>
  </div>

  <div style="position: fixed; bottom: 20px; left: 24px; z-index: 10;">
    <button onclick="window.history.back()">← Torna indietro</button>
  </div>

  <!-- Area contenuto (ex iframe opendocframe) -->
  <div class="content">
    <div class="opendoc" id="doc-content"></div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
/** Carica il testo di un articolo nel div #doc-content via fetch */
function loadArticolo(id) {
    fetch('statuto_testo.php?articolo=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) { document.getElementById('doc-content').innerHTML = html; });
}

/** Accordion menu */
$(function () {
    var Accordion = function (el, multiple) {
        this.el       = el || {};
        this.multiple = multiple || false;
        this.el.find('.dropdownlink').on('click', { el: this.el, multiple: this.multiple }, this.dropdown);
    };

    Accordion.prototype.dropdown = function (e) {
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

    // Auto-carica il primo articolo disponibile leggendo il DOM
    var $firstLink = $('.accordion-menu .submenuItems a').first();
    if ($firstLink.length) {
        var m = ($firstLink.attr('onclick') || '').match(/loadArticolo\((\d+)\)/);
        if (m) loadArticolo(parseInt(m[1], 10));
    }
});
</script>
</body>
</html>
