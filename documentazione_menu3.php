<?php
/* Includo i file necessari */
include('includes/constant_values.inc.php');
include('config.inc.php');
include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('includes/functions.inc.php');
require('header.inc.php'); /*Header comune*/

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
$tipo = $_GET['tipo'];

?>

<head>
    <link href="themes/crystal/documentazione.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<style>
ul {
  list-style: none;
}

a {
  text-decoration: none;
}

.accordion-menu {
  width: 100%;
  max-width: 350px;
}

.accordion-menu li.open .dropdownlink .fa-chevron-down {
  transform: rotate(180deg);
}

.accordion-menu li:last-child .dropdownlink {
  border-bottom: 0;
}

.dropdownlink {
  cursor: pointer;
  display: block;
  width: 285px;
  height: 55px;
  font-size: 18px;
  color: #212121;
  position: relative;
  transition: all 0.4s ease-out;
}
.dropdownlink i {
  position: absolute;
  top: 17px;
  left: 16px;
}
.dropdownlink .fa-chevron-down {
  right: 12px;
  left: auto;
}

.submenuItems {
  display: none;
}
.submenuItems li {
  width: 271px;
  background-color: #0f111d;
  border: 1px solid #000000;
  padding-left: 15px;
}

.submenuItems a {
  transition: all 0.4s ease-out;
}
.submenuItems a:hover {
  background-color: transparent;
  color: #fff;
}

.pos-menu {
   position: absolute;
   top: 109px;
   left: 18px;
   z-index: -1;
}

.ambientazione {
   padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_ambientazione.png") no-repeat left center transparent;
   }
.ambientazione:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/ambientazione_manuale_mouseover.gif") no-repeat left center transparent;
}
.regolamento {
  padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_regolamento.png") no-repeat left center transparent;
}
.regolamento:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/regolamento_manuale_mouseover.gif") no-repeat left center transparent;
}
.primi_passi {
  padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_primi_passi.png") no-repeat left center transparent;
}
.primi_passi:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/primi_passi_manuale_mouseover.gif") no-repeat left center transparent;
}
.manuale {
  padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_manuale.png") no-repeat left center transparent;
}
.manuale:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/manuale_manuale_mouseover.gif") no-repeat left center transparent;
}
.combattimento {
  padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_combattimento.png") no-repeat left center transparent;
}
.combattimento:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/combattimento_manuale_mouseover.gif") no-repeat left center transparent;
}
.staff {
  padding: 4px;
   background: url("../themes/crystal/imgs/documentazione/manuale_staff.png") no-repeat left center transparent;
}
.staff:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/documentazione/staff_manuale_mouseover.gif") no-repeat left center transparent;
}
.ricerca {
  padding: 4px;
}
</style>
</head>

<body style="background:transparent;">
<div class="logo"><img src="../themes/crystal/imgs/documentazione/titolo.png"></div>

<div class="pos-menu">
<ul class="accordion-menu">
  <li>
    <div class="dropdownlink ambientazione">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems active">
      <li><p> 
      <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'ambientazione' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?>
  </p></li>
    </ul>
  </li>
  <li>
    <div class="dropdownlink regolamento">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'regolamento' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  <li>
    <div class="dropdownlink primi_passi">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'primipassi' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  <li>
    <div class="dropdownlink manuale">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'manuali' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  <li>
    <div class="dropdownlink combattimento">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'combattimento' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
    <li>
    <div class="dropdownlink staff">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM regolamento WHERE tipo = 'staff' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
</ul>

<form class="searchBar" action="documentazione_testo.php?op=search" method="post" target="opendocframe">
<div align="center"> 
<input name="ricerca" style="width: 200px;" placeholder="Inserire parola chiave" /> <br>
<input type="submit" value="cerca" />
</div>
</form>
</div>
 
  <script src="../includes/popup_docu.js"></script>
      <script>
$(function () {
  var Accordion = function (el, multiple) {
    this.el = el || {};
    // more then one submenu open?
    this.multiple = multiple || false;

    var dropdownlink = this.el.find('.dropdownlink');
    dropdownlink.on('click',
    { el: this.el, multiple: this.multiple },
    this.dropdown);
  };

  Accordion.prototype.dropdown = function (e) {
    var $el = e.data.el,
    $this = $(this),
    //this is the ul.submenuItems
    $next = $this.next();

    $next.slideToggle();
    $this.parent().toggleClass('open');

    if (!e.data.multiple) {
      //show only one menu at the same time
      $el.find('.submenuItems').not($next).slideUp().parent().removeClass('open');
    }
	$el.find('.active').$("li").slideDown();
  };

  var accordion = new Accordion($('.accordion-menu'), false);
});

    </script>
 
</body>