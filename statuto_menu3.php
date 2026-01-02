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
$id = $_GET['id'];
$id2 = $_GET['id2'];
?>

<head>
    <link href="themes/crystal/statuti.css" rel="stylesheet" type="text/css">
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
   top: 148px;
   left: 24px;
   z-index: -1;
}

.ambientazione {
   padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/storia_base.png") no-repeat left center transparent;
   }
.ambientazione:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/storia_hover.gif") no-repeat left center transparent;
}
.regolamento {
   padding: 5px;
   background: url("../themes/crystal/imgs/statuti/tasti/statuto_base.png") no-repeat left center transparent;
}
.regolamento:hover {
  padding: 5px;
  background: url("../themes/crystal/imgs/statuti/tasti/statuto_hover.gif") no-repeat left center transparent;
}
.primi_passi {
  padding: 6px;
   background: url("../themes/crystal/imgs/statuti/tasti/skill_base.png") no-repeat left center transparent;
}
.primi_passi:hover {
  padding: 6px;
  background: url("../themes/crystal/imgs/statuti/tasti/skill_hover.gif") no-repeat left center transparent;
}
.manuale {
  padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/requisiti_base.png") no-repeat left center transparent;
}
.manuale:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/requisiti_hover.gif") no-repeat left center transparent;
}

/*MESTIERI*/

.statutom {
  padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/statutom_base.png") no-repeat left center transparent;
}
.statutom:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/statutom_hover.gif") no-repeat left center transparent;
}

.descrizione {
  padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/descrizione_base.png") no-repeat left center transparent;
}
.descrizione:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/descrizione_hover.gif") no-repeat left center transparent;
}
.cariche {
  padding: 6px;
   background: url("../themes/crystal/imgs/statuti/tasti/cariche_base.png") no-repeat left center transparent;
}
.cariche:hover {
  padding: 6px;
  background: url("../themes/crystal/imgs/statuti/tasti/cariche_hover.gif") no-repeat left center transparent;
}
.specifiche {
  padding: 6px;
   background: url("../themes/crystal/imgs/statuti/tasti/specifiche_base.png") no-repeat left center transparent;
}
.specifiche:hover {
  padding: 6px;
  background: url("../themes/crystal/imgs/statuti/tasti/specifiche_hover.gif") no-repeat left center transparent;
}

/*CITTADINI*/

.cittadini {
  padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/cittadini_base.png") no-repeat left center transparent;
}
.cittadini:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/cittadini_hover.gif") no-repeat left center transparent;
}

.scorpion {
  padding: 4px;
   background: url("../themes/crystal/imgs/statuti/tasti/scorpion_base.png") no-repeat left center transparent;
}
.scorpion:hover {
  padding: 4px;
  background: url("../themes/crystal/imgs/statuti/tasti/scorpion_hover.gif") no-repeat left center transparent;
}
.sit {
  padding: 6px;
   background: url("../themes/crystal/imgs/statuti/tasti/SIT_base.png") no-repeat left center transparent;
}
.sit:hover {
  padding: 6px;
  background: url("../themes/crystal/imgs/statuti/tasti/SIT_hover.gif") no-repeat left center transparent;
}
.wikkan {
  padding: 6px;
   background: url("../themes/crystal/imgs/statuti/tasti/Wiccan_base.png") no-repeat left center transparent;
}
.wikkan:hover {
  padding: 6px;
  background: url("../themes/crystal/imgs/statuti/tasti/wiccan_hover.gif") no-repeat left center transparent;
}
</style>
</head>

<body style="background:transparent;">

<div class="pos-menu">
<ul class="accordion-menu">
  
  
 <?php if ($id > 0 && $id < 9) { ?> 
  <li>
    <div class="dropdownlink ambientazione">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'storia' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  
  <li>
    <div class="dropdownlink regolamento">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'statuto' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink primi_passi">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'skill' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink manuale">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'requisiti' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
<?php } 

if ($id == 9) { ?> 
  <li>
    <div class="dropdownlink cittadini">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'cittadini' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  

  <li>
    <div class="dropdownlink sit">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'sit' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink wikkan">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'wiccan' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
    <li>
    <div class="dropdownlink scorpion">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'scorpion' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
<?php } 


if ($id2 > 0) { ?>  


<li>
    <div class="dropdownlink statutom">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'storia' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  
  <li>
    <div class="dropdownlink descrizione">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'statuto' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink cariche">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'skill' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink specifiche">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti_new WHERE tipo = 'requisiti' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?></p></li>
    </ul>
  </li>
  
<?php } ?>  
</ul>
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