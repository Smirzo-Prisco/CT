<?php
/**
 * statuto_main.inc.php — Statuto gilda o mestiere
 *
 * Incluso da main.php nel layout di gioco (center content).
 * Menu accordion inline, testo articoli caricato via fetch.
 * Non è una pagina standalone: sessione e DB già aperti da main.php.
 */

$id  = (int)($_GET['id']  ?? 0);
$id2 = (int)($_GET['id2'] ?? 0);

$bgGilde = [
    1 => 'paladini.png', 2 => 'demoni.png',    3 => 'setta.png',
    4 => 'custodi.png',  5 => 'guardiani.png',  6 => 'fiori.png',
    7 => 'lancaster.png',8 => 'muryu.png',       9 => 'manuale_cittadini.png',
];
$bgMestieri = [
    1 => 'icc.png', 2 => 'tae.png',  3 => 'magic.png',
    4 => 'pandora.png', 6 => 'corte.png', 10 => 'corte.png',
];

$background = $bgGilde[$id] ?? ($bgMestieri[$id2] ?? null);
if (!$background) { echo '<p>Pagina non trovata.</p>'; return; }

/**
 * Renderizza una sezione del menu accordion.
 * Restituisce stringa HTML o '' se non ci sono articoli.
 */
function statutoSection($tipo, $campo, $valore, $classe) {
    $valore = (int)$valore;
    $res    = gdrcd_query(
        "SELECT articolo, titolo FROM statuti_new WHERE tipo='$tipo' AND $campo='$valore' ORDER BY articolo",
        'result'
    );
    $links = '';
    while ($row = gdrcd_query($res, 'fetch')) {
        $art    = (int)$row['articolo'];
        $titolo = gdrcd_filter('out', $row['titolo']);
        $links .= "<a href=\"#\" onclick=\"statutoLoad($art);return false;\">$titolo</a><br>\n";
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

<link rel="stylesheet" href="themes/crystal/statuto_menu.css">

<style>
#statuto-wrap {
    position: relative;
    width: 985px;
    min-height: 640px;
    margin: 0 auto;
    overflow: hidden;
}
#statuto-wrap .statuto-bg {
    position: absolute;
    top: 0; left: 0;
    z-index: 0;
    pointer-events: none;
}
#statuto-wrap .statuto-bg img { display: block; }

#statuto-wrap .pos-menu {
    position: absolute;
    top: 148px;
    left: 24px;
    z-index: 1;
}
#statuto-wrap #statuto-content {
    position: absolute;
    top: 15px;
    left: 337px;
    width: 527px;
    min-height: 400px;
    max-height: 580px;
    overflow-y: auto;
    z-index: 1;
    padding: 8px;
}
/* Stili testo articolo (da statuti.css, scoped) */
#statuto-content .titoli {
    color: #ce846f;
    text-transform: uppercase;
    font-weight: normal;
    background-image: url(imgs/documentazione/barra_titolo.png);
    background-repeat: no-repeat;
    text-align: center;
    padding: 10px;
    text-shadow: 3px 2px 3px #000;
}
#statuto-content .testo {
    color: #8f8f8f;
    line-height: 18px;
    text-align: justify;
    padding: 20px;
}
</style>

<div id="statuto-wrap">

  <div class="statuto-bg">
    <img src="themes/crystal/imgs/statuti/<?= $background ?>">
  </div>

  <div class="pos-menu">
    <ul class="accordion-menu">
      <?php
      if ($id > 0 && $id < 9) {
          echo statutoSection('storia',    'id_gilda', $id, 'ambientazione');
          echo statutoSection('statuto',   'id_gilda', $id, 'regolamento');
          echo statutoSection('skill',     'id_gilda', $id, 'primi_passi');
          echo statutoSection('requisiti', 'id_gilda', $id, 'manuale');
      }
      if ($id == 9) {
          echo statutoSection('cittadini', 'id_gilda', $id, 'cittadini');
          echo statutoSection('sit',       'id_gilda', $id, 'sit');
          echo statutoSection('wiccan',    'id_gilda', $id, 'wikkan');
          echo statutoSection('scorpion',  'id_gilda', $id, 'scorpion');
      }
      if ($id2 > 0) {
          echo statutoSection('storia',    'id_mestiere', $id2, 'statutom');
          echo statutoSection('statuto',   'id_mestiere', $id2, 'descrizione');
          echo statutoSection('skill',     'id_mestiere', $id2, 'cariche');
          echo statutoSection('requisiti', 'id_mestiere', $id2, 'specifiche');
      }
      ?>
    </ul>
  </div>

  <div id="statuto-content"></div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
function statutoLoad(id) {
    fetch('statuto_testo.php?articolo=' + id)
        .then(function(r) { return r.text(); })
        .then(function(html) { document.getElementById('statuto-content').innerHTML = html; });
}

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
