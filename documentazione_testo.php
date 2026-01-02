<?php
/* Includo i file necessari */
include('includes/constant_values.inc.php');
include('config.inc.php');
include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('includes/functions.inc.php');
require('header.inc.php'); /*Header comune*/

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
$articolo = $_GET['articolo'];

$result = gdrcd_query("SELECT * FROM regolamento WHERE articolo='".$articolo."'");
$titolo = $result['titolo'];
$testo = $result['testo'];

$convtesto = ($result['testo']);
                $convtesto = (str_replace("\n", "<br>", $convtesto));
                $convtesto = (str_replace("[BR]", "<br>", $convtesto));
                $convtesto = (str_replace("[B]", "<b>", $convtesto));
                $convtesto = (str_replace("[/B]", "</b>", $convtesto));
                $convtesto = (str_replace("[I]", "<i>", $convtesto));
                $convtesto = (str_replace("[/I]", "</i>", $convtesto));
                $convtesto = (str_replace("[U]", "<u>", $convtesto));
                $convtesto = (str_replace("[/U]", "</u>", $convtesto));
                $convtesto = (str_replace("[C]", "<div align='center'>", $convtesto));
                $convtesto = (str_replace("[/C]", "</div>", $convtesto));
                $convtesto = (str_replace("[quote]", "<table border=1 bordercolor=#a9cded align=center width=80%><tr><td>", $convtesto));
                $convtesto = (str_replace("[/quote]", "</td></tr></table>", $convtesto));
                $convtesto = (str_replace("[D]", "<div align='right'>", $convtesto));
                $convtesto = (str_replace("[/D]", "</div>", $convtesto));

                $testo = $convtesto;
?>
<head>
  <meta charset="utf-8">
  <link href="themes/crystal/documentazione.css" rel="stylesheet" type="text/css">
  <title></title>
</head>
<body style="background: transparent;">
<div class="testo">

<?php 
$ricerca = $_POST['ricerca'];
if(gdrcd_filter('get', $_REQUEST['op']) == 'search') {
?>
<div class="titoli" style="background-image: url(../themes/crystal/imgs/documentazione/barra_titolo.png);"><b>PAROLA CERCATA</b>: <?php echo $ricerca; ?></div><br>
  <?php $query = "SELECT * FROM regolamento WHERE (titolo LIKE '%$ricerca%' OR testo LIKE '%$ricerca%')  ORDER BY articolo";
  $result = gdrcd_query($query, 'result');
  while ($row = gdrcd_query($result, 'fetch')) { ?>
  <center><a href="documentazione_testo.php?articolo=<?php echo gdrcd_filter('num', $row['articolo']); ?>" target="opendocframe"><?php echo gdrcd_filter('out', $row['titolo']); ?></a><br></center>
  <? }//while ?>
  
  <?php } else { ?>
<div class="titoli" style="background-image: url(../themes/crystal/imgs/documentazione/barra_titolo.png);"><?php echo $titolo; ?></div><br>
<?php echo $testo; ?>
</div>
<?php } ?>
</body>