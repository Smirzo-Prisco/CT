<?php
session_start();
require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();
$tipo = $_GET['tipo'];
$id = $_GET['id'];
$id2 = $_GET['id2'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
    <link href="themes/crystal/statuti.css" rel="stylesheet" type="text/css">
    <link href="themes/crystal/statuto_menu.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

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
  $query = "SELECT * FROM statuti WHERE tipo = 'storia' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  
  <li>
    <div class="dropdownlink regolamento">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'statuto' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink primi_passi">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'skill' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink manuale">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'requisiti' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
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
  $query = "SELECT * FROM statuti WHERE tipo = 'cittadini' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  

  <li>
    <div class="dropdownlink sit">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'sit' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink wikkan">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'wiccan' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
    <li>
    <div class="dropdownlink scorpion">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'scorpion' AND id_gilda = '$id' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
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
  $query = "SELECT * FROM statuti WHERE tipo = 'storia' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  
  <li>
    <div class="dropdownlink descrizione">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'statuto' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink cariche">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'skill' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
    </ul>
  </li>
  
  <li>
    <div class="dropdownlink specifiche">
      <i class="fa fa-chevron-down"></i>
    </div>
    <ul class="submenuItems">
      <li><p>       <?php 
  $query = "SELECT * FROM statuti WHERE tipo = 'requisiti' AND id_mestiere = '$id2' ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="statuto_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" onclick="parent.document.querySelector('iframe[name=opendocframe]').src=this.href; return false;"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <?php }//while ?></p></li>
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
</html>