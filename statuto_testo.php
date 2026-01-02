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

$result = gdrcd_query("SELECT * FROM statuti_new WHERE articolo='".$articolo."'");
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
  <link href="themes/crystal/statuti.css" rel="stylesheet" type="text/css">
  <title></title>
</head>
<body style="background: transparent;">
<div class="testo">
<div class="titoli" style="padding-top:20px; padding-bottom:5px;"><?php echo $titolo; ?></div><br>
<?php echo $testo; ?>
</div>
</body>