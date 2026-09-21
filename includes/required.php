<?php

require_once(dirname(__FILE__) . '/constant_values.inc.php');
require_once(dirname(__FILE__) . '/../config.inc.php');
require_once(dirname(__FILE__) . '/../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
require_once(dirname(__FILE__) . '/functions.inc.php');
/** Gestione dei Suoni */
require_once(dirname(__FILE__) . '/AudioController.class.php');

// Log "Movimenti" (main.php?page=log&tab=movimenti): traccia ogni azione di
// ogni personaggio loggato, per poter ricostruire lo storico completo di un
// account sospetto fin dal suo primo utilizzo — non solo da quando è stato
// identificato come doppio (vedi log_doppi). Agganciato qui una sola volta,
// come shutdown function, invece che in ognuno dei ~50 file pages/api_*.php:
// required.php è già incluso ovunque. Solo le richieste POST: nel frontend
// il metodo distingue già le azioni vere (POST) dalle semplici letture o dal
// polling periodico (GET, es. op=ping/presenti/profile) — stesso confine già
// seguito in tutto il codice esistente, nessuna whitelist di endpoint da
// mantenere a mano man mano che se ne aggiungono di nuovi.
register_shutdown_function(function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($script, 'api_') !== 0) {
        return;
    }
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['login'])) {
        return;
    }

    try {
        $nome = gdrcd_filter('in', $_SESSION['login']);
        $op   = gdrcd_filter('in', (string)($_GET['op'] ?? $_POST['op'] ?? ''));
        $ip   = gdrcd_filter('in', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
        gdrcd_query(
            "INSERT INTO log_movimenti (Nome, Endpoint, Operazione, IP, DataEvento) VALUES ('$nome', '" . gdrcd_filter('in', $script) . "', '$op', '$ip', NOW())",
            'query',
            true
        );
    } catch (\Exception $e) {
        // Non critico: non deve mai alterare una risposta già inviata al client.
    }
});

