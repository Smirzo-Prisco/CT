<?php
session_start();
include('../../config.inc.php');
include('../../includes/functions.inc.php');

// Verifica che l'utente sia loggato e che l'id della conversazione sia valido
if (!isset($_SESSION['login']) || !isset($_GET['id_conversazione']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    die("Accesso non autorizzato.");
}

$id_conversazione = intval($_GET['id_conversazione']);
$start_date = gdrcd_filter('in', $_GET['start_date']);
$end_date = gdrcd_filter('in', $_GET['end_date']);

// Recupera la conversazione nell'intervallo di date
$query = "SELECT mittente_nome, destinatario_nome, testo, ora_spedizione 
          FROM sms 
          WHERE id_conversazione = $id_conversazione
          AND DATE(ora_spedizione) BETWEEN '$start_date' AND '$end_date'
          ORDER BY ora_spedizione ASC";
$result = gdrcd_query($query, 'result');

// Crea un file di testo con il contenuto della conversazione
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="conversazione_' . $id_conversazione . '_dal_' . $start_date . '_al_' . $end_date . '.txt"');

while ($row = gdrcd_query($result, 'fetch')) {
    echo "[" . date('d-m-Y H:i', strtotime($row['ora_spedizione'])) . "] " . $row['mittente_nome'] . ": " . $row['testo'] . "\n";
}

exit();
?>
