<?php
/**
 * documentazione_testo.php — Frammento HTML testo articolo o risultati ricerca.
 * Chiamato via fetch da documentazione_main.php e Documentazione.jsx.
 */

session_start();
require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();

$op      = $_REQUEST['op'] ?? '';
$ricerca = trim($_POST['ricerca'] ?? '');

header('Content-Type: text/html; charset=UTF-8');

if ($op === 'search' && $ricerca !== '') {
    $safe = gdrcd_filter('in', $ricerca);
    echo '<div class="testo">';
    echo '<div class="titoli">PAROLA CERCATA: ' . gdrcd_filter('out', $ricerca) . '</div><br>';
    $res = gdrcd_query(
        "SELECT articolo, titolo FROM regolamento WHERE (titolo LIKE '%$safe%' OR testo LIKE '%$safe%') ORDER BY articolo",
        'result'
    );
    while ($row = gdrcd_query($res, 'fetch')) {
        $art = (int)$row['articolo'];
        $tit = gdrcd_filter('out', $row['titolo']);
        echo "<center><a href=\"#\" class=\"doc-link\" data-articolo=\"$art\">$tit</a><br></center>\n";
    }
    gdrcd_query($res, 'free');
    echo '</div>';
    exit;
}

$articolo = (int)($_GET['articolo'] ?? 0);
if (!$articolo) exit;

$result = gdrcd_query("SELECT titolo, testo FROM regolamento WHERE articolo='$articolo'");
if (!$result) exit;

$titolo = gdrcd_filter('out', $result['titolo'] ?? '');
$testo  = $result['testo'] ?? '';

$testo = str_replace("\n",       "<br>",                                                                   $testo);
$testo = str_replace("[BR]",     "<br>",                                                                   $testo);
$testo = str_replace("[B]",      "<b>",                                                                    $testo);
$testo = str_replace("[/B]",     "</b>",                                                                   $testo);
$testo = str_replace("[I]",      "<i>",                                                                    $testo);
$testo = str_replace("[/I]",     "</i>",                                                                   $testo);
$testo = str_replace("[U]",      "<u>",                                                                    $testo);
$testo = str_replace("[/U]",     "</u>",                                                                   $testo);
$testo = str_replace("[C]",      "<div align='center'>",                                                   $testo);
$testo = str_replace("[/C]",     "</div>",                                                                 $testo);
$testo = str_replace("[quote]",  "<table border=1 bordercolor=#a9cded align=center width=80%><tr><td>",   $testo);
$testo = str_replace("[/quote]", "</td></tr></table>",                                                     $testo);
$testo = str_replace("[D]",      "<div align='right'>",                                                    $testo);
$testo = str_replace("[/D]",     "</div>",                                                                 $testo);

echo '<div class="testo">';
echo '<div class="titoli" style="padding-top:20px;padding-bottom:5px;">' . $titolo . '</div><br>';
echo $testo;
echo '</div>';
