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

?>
<link rel="stylesheet" href="themes/crystal/manuali.css" type="text/css" />

<?php

$result = gdrcd_query("SELECT * FROM regolamento WHERE articolo=".$articolo."");

/*FORMATTO*/

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

<p style="text-align: justify"><font style="font-family: Verdana, serif; font-size: 10.5px;">
<?php echo $testo; ?>
</font></p>
</div>
