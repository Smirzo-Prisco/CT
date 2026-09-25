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
    // Niente controllo su session_status(): diversi endpoint (api_map.php,
    // api_scheda.php, ecc.) chiamano session_write_close() subito dopo il
    // controllo di login per non tenere il lock di sessione — da quel momento
    // session_status() torna PHP_SESSION_NONE, ma $_SESSION resta leggibile
    // in memoria per tutto il resto della richiesta. Un controllo su
    // PHP_SESSION_ACTIVE qui perdeva silenziosamente l'intera riga (non solo
    // il nome) per quegli endpoint.
    if (empty($_SESSION['login'])) {
        return;
    }

    // Endpoint+operazione esclusi dal log Movimenti: azioni troppo frequenti/
    // poco significative per un'indagine (rumore puro), non vere "azioni" nel
    // senso per cui è nata questa tabella.
    $movimenti_esclusi = [
        'api_map.php' => ['setIdle'],
    ];
    $operazioneRichiesta = (string)($_GET['op'] ?? $_POST['op'] ?? '');
    if (in_array($operazioneRichiesta, $movimenti_esclusi[$script] ?? [], true)) {
        return;
    }

    try {
        $nome = gdrcd_filter('in', $_SESSION['login']);
        $op   = gdrcd_filter('in', $operazioneRichiesta);
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

// Log "Injection" (main.php?page=log&tab=injection): scansione euristica
// leggera di ogni valore in ingresso (query string, form POST, body JSON)
// alla ricerca di pattern tipici di SQL injection / XSS / path traversal /
// command injection. Deliberatamente globale su TUTTE le richieste (non solo
// POST, non solo per personaggi già noti come doppi, non richiede nemmeno una
// sessione attiva): un alias non ancora riconosciuto come doppio, o un
// tentativo contro login/iscrizione prima ancora di avere un account,
// sarebbero altrimenti invisibili fino a identificazione — lo stesso
// problema già visto con Tyler (rilevato doppio solo al login). Nessun
// blocco, solo log per revisione manuale.
register_shutdown_function(function () {
    try {
        $pattern_regex = [
            'sqli-union'      => '/\bunion\b[^;]{0,60}\bselect\b/i',
            'sqli-tautologia' => '/\bor\b\s*[\'"]?\s*\d+\s*[\'"]?\s*=\s*[\'"]?\s*\d+/i',
            'sqli-comando'    => '/\b(drop|alter)\s+table\b|\bxp_cmdshell\b|\binto\s+outfile\b|\bload_file\s*\(/i',
            'sqli-tempo'      => '/\bsleep\s*\(\s*\d|\bbenchmark\s*\(/i',
            // Richiede una virgoletta subito prima di "--" o "/*" (payload classico
            // tipo ' -- o '/**/): un doppio trattino da solo è troppo comune nel
            // testo libero di gioco (usato come lineetta), e un commento /* */ da
            // solo è normalissimo nell'HTML/CSS che i personaggi possono inserire
            // nella propria scheda (personalizzazione legittima, es. Liva il
            // 26/09) — senza la virgoletta prima erano entrambi solo rumore.
            'sqli-commento'   => '/([\'"])\s*(--|\/\*)|;\s*--/i',
            'xss-script'      => '/<script\b|<\/script>/i',
            'xss-handler'     => '/\bon(error|load|click|mouseover|focus)\s*=/i',
            'xss-javascript'  => '/javascript\s*:/i',
            'path-traversal'  => '#\.\.[/\\\\]#',
            'php-injection'   => '/<\?php|\bsystem\s*\(|\bexec\s*\(|\bshell_exec\s*\(|\bpassthru\s*\(|\beval\s*\(/i',
        ];

        // Campi mai scansionati: una password vera che contenesse per caso un
        // sottostringa "sospetta" finirebbe altrimenti salvata in chiaro nel log.
        $campi_esclusi = ['pass', 'passwd'];

        $valori = [];
        $raccogli = function ($dati, $prefisso = '') use (&$valori, &$raccogli, $campi_esclusi) {
            if (!is_array($dati)) {
                return;
            }
            foreach ($dati as $chiave => $valore) {
                $percorso = $prefisso === '' ? (string)$chiave : $prefisso . '.' . $chiave;
                if (is_array($valore)) {
                    $raccogli($valore, $percorso);
                    continue;
                }
                if (!is_string($valore) || $valore === '') {
                    continue;
                }
                foreach ($campi_esclusi as $escluso) {
                    if (stripos((string)$chiave, $escluso) !== false) {
                        continue 2;
                    }
                }
                $valori[$percorso] = $valore;
            }
        };

        $raccogli($_GET);
        $raccogli($_POST);
        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ctype, 'application/json') !== false) {
            $decoded = json_decode((string)file_get_contents('php://input'), true);
            if (is_array($decoded)) {
                $raccogli($decoded);
            }
        }

        foreach ($valori as $campo => $valore) {
            foreach ($pattern_regex as $nomePattern => $regex) {
                if (preg_match($regex, $valore) !== 1) {
                    continue;
                }

                // Stesso motivo del log Movimenti sopra: niente controllo su
                // session_status(), $_SESSION resta leggibile anche dopo un
                // eventuale session_write_close() a monte.
                $nome = !empty($_SESSION['login'])
                    ? "'" . gdrcd_filter('in', $_SESSION['login']) . "'"
                    : 'NULL';
                $script = gdrcd_filter('in', basename($_SERVER['SCRIPT_NAME'] ?? ''));
                $op     = gdrcd_filter('in', (string)($_GET['op'] ?? $_POST['op'] ?? ''));
                $ip     = gdrcd_filter('in', $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

                gdrcd_query(
                    "INSERT INTO log_injection (Nome, Endpoint, Operazione, Campo, Valore, Pattern, IP, DataEvento) VALUES ($nome, '$script', '$op', '" .
                    gdrcd_filter('in', substr($campo, 0, 60)) . "', '" .
                    gdrcd_filter('in', substr($valore, 0, 500)) . "', '" .
                    gdrcd_filter('in', $nomePattern) . "', '$ip', NOW())",
                    'query',
                    true
                );
                break; // un pattern trovato basta a segnalare il campo
            }
        }
    } catch (\Exception $e) {
        // Non critico: non deve mai alterare una risposta già inviata al client.
    }
});

