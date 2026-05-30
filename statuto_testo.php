<?php
/**
 * statuto_testo.php — Restituisce solo il frammento HTML del testo di un articolo.
 * Chiamato via fetch da statuto_main.php, non più in iframe.
 */

session_start();
require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();

$articolo = (int)($_GET['articolo'] ?? 0);
if (!$articolo) exit;

$result = gdrcd_query("SELECT titolo, testo FROM statuti_new WHERE articolo='$articolo'");
if (!$result) exit;

$titolo = gdrcd_filter('out', $result['titolo'] ?? '');
$testo  = $result['testo'] ?? '';

$testo = str_replace("\n",       "<br>",                                                   $testo);
$testo = str_replace("[BR]",     "<br>",                                                   $testo);
$testo = str_replace("[B]",      "<b>",                                                    $testo);
$testo = str_replace("[/B]",     "</b>",                                                   $testo);
$testo = str_replace("[I]",      "<i>",                                                    $testo);
$testo = str_replace("[/I]",     "</i>",                                                   $testo);
$testo = str_replace("[U]",      "<u>",                                                    $testo);
$testo = str_replace("[/U]",     "</u>",                                                   $testo);
$testo = str_replace("[C]",      "<div align='center'>",                                   $testo);
$testo = str_replace("[/C]",     "</div>",                                                 $testo);
$testo = str_replace("[quote]",  "<table border=1 bordercolor=#a9cded align=center width=80%><tr><td>", $testo);
$testo = str_replace("[/quote]", "</td></tr></table>",                                     $testo);
$testo = str_replace("[D]",      "<div align='right'>",                                    $testo);
$testo = str_replace("[/D]",     "</div>",                                                 $testo);

header('Content-Type: text/html; charset=UTF-8');
echo '<div class="testo">';
echo '<div class="titoli" style="padding-top:20px;padding-bottom:5px;">' . $titolo . '</div><br>';
echo $testo;
echo '</div>';
