<?php
session_start();
include('includes/constant_values.inc.php');
include('config.inc.php');
include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('includes/functions.inc.php');
$handleDBConnection = gdrcd_connect();
$id = $_GET['id'];
$id2 = $_GET['id2'];

//carico l'elenco delle abilità
if ($id == 1) {
$background = 'paladini.png';
} else if ($id == 2) {
$background = 'demoni.png';
} else if ($id == 3) {
$background = 'setta.png';
} else if ($id == 4) {
$background = 'custodi.png';
} else if ($id == 5) {
$background = 'guardiani.png';
} else if ($id == 6) {
$background = 'fiori.png';
} else if ($id == 7) {
$background = 'lancaster.png';
} else if ($id == 8) {
$background = 'muryu.png';
} else if ($id == 9) {
$background = 'manuale_cittadini.png';
} else if ($id2 == 1) {
$background = 'icc.png';
} else if ($id2 == 2) {
$background = 'tae.png';
} else if ($id2 == 3) {
$background = 'magic.png';
} else if ($id2 == 4) {
$background = 'pandora.png';
} else if ($id2 == 6) {
$background = 'corte.png';
} elseif ($id2 == 10) {
$background = 'corte.png';
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<link href="https://fonts.googleapis.com/css?family=Amatic+SC" rel="stylesheet">
<link href="themes/crystal/statuti.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<title>CT - Documentazione</title>  
</head>

<?php if ($id > 0 || $id2 > 0) { ?>
<body>

<div id="main-container">
<div class="chapter"> 
<img src="themes/crystal/imgs/statuti/<?php echo $background; ?>">
</div>
<div class="openmenu">
<?php if ($id > 0) { ?>
<iframe name="openmenuframe" src ="statuto_menu3.php?id=<?= $id ?>" class="iframemenu" allowtransparency="true" frameborder="0"></iframe>
<?php } else if ($id2 > 0) { ?>
<iframe name="openmenuframe" src ="statuto_menu3.php?id2=<?= $id2 ?>" class="iframemenu" allowtransparency="true" frameborder="0"></iframe>
<?php } ?>
</div>
<div class="content">  
  <div class="opendoc">
      <iframe name="opendocframe" src ="statuto_testo.php" class="iframedoc" allowtransparency="true" frameborder="0"></iframe>
  </div>
</div>
</div>
</body>
</html>
<?php } ?>