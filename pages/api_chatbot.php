<?php
/**
 * api_chatbot.php — API endpoint per il chatbot Crystal Tokyo AI
 *
 * Operazioni (GET ?op=):
 *   status  → domande rimaste oggi per l'utente corrente
 *   ask     → POST JSON { "domanda": "..." } → risposta Claude AI
 *
 * Rate limit: 5 domande/giorno per utente autenticato.
 * Modello: claude-haiku-4-5-20251001 (Anthropic).
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/required.php';
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$op         = $_GET['op'] ?? '';
$nome       = gdrcd_filter('in', $_SESSION['login']);
$DAILY_LIMIT = 5;
$MAX_CHARS   = 500;

// ── op=status ──────────────────────────────────────────────────────────────

if ($op === 'status') {
    $row  = gdrcd_query("SELECT COUNT(*) AS n FROM chatbot_log WHERE nome_personaggio = '$nome' AND DATE(created_at) = CURDATE()");
    $used = (int)($row['n'] ?? 0);
    echo json_encode([
        'success'   => true,
        'used'      => $used,
        'limit'     => $DAILY_LIMIT,
        'remaining' => max(0, $DAILY_LIMIT - $used),
    ]);
    exit;
}

// ── op=ask ─────────────────────────────────────────────────────────────────

if ($op === 'ask') {
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $domanda = trim($data['domanda'] ?? '');

    if ($domanda === '') {
        echo json_encode(['success' => false, 'message' => 'Scrivi una domanda.']);
        exit;
    }

    if (mb_strlen($domanda) > $MAX_CHARS) {
        echo json_encode(['success' => false, 'message' => 'Domanda troppo lunga (max ' . $MAX_CHARS . ' caratteri).']);
        exit;
    }

    // Controllo limite giornaliero
    $row  = gdrcd_query("SELECT COUNT(*) AS n FROM chatbot_log WHERE nome_personaggio = '$nome' AND DATE(created_at) = CURDATE()");
    $used = (int)($row['n'] ?? 0);
    if ($used >= $DAILY_LIMIT) {
        echo json_encode(['success' => false, 'message' => 'Hai esaurito le 5 domande di oggi. Torna domani!']);
        exit;
    }

    // Carica articoli pubblici dal DB (escluso tipo "staff")
    $result  = gdrcd_query("SELECT titolo, testo, tipo FROM regolamento WHERE tipo != 'staff' ORDER BY tipo, articolo", 'result');
    $context = '';
    while ($art = gdrcd_query($result, 'fetch')) {
        $context .= "## [{$art['tipo']}] {$art['titolo']}\n{$art['testo']}\n\n";
    }
    gdrcd_query($result, 'free');

    // Chiave API Anthropic
    $api_key = $PARAMETERS['anthropic']['api_key'] ?? '';
    if (empty($api_key)) {
        error_log('[chatbot] api_key Anthropic non configurata in config.inc.php');
        echo json_encode(['success' => false, 'message' => 'Servizio temporaneamente non disponibile.']);
        exit;
    }

    $system = "Sei Crystal Bot, l'assistente virtuale del gioco di ruolo Crystal Tokyo. "
            . "Quando ti presenti, dì che ti chiami Crystal Bot. "
            . "Rispondi ESCLUSIVAMENTE basandoti sulle informazioni del regolamento fornite qui sotto. "
            . "Se la risposta non è nel regolamento, dillo chiaramente senza inventare. "
            . "Rispondi in italiano, in modo chiaro e conciso.\n\n---\n\n"
            . $context;

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 512,
        'system'     => $system,
        'messages'   => [
            ['role' => 'user', 'content' => $domanda],
        ],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $api_key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("[chatbot] curl error: $curlErr");
        echo json_encode(['success' => false, 'message' => 'Errore di rete. Riprova.']);
        exit;
    }

    if ($httpCode !== 200) {
        error_log("[chatbot] Anthropic API HTTP $httpCode: $response");
        echo json_encode(['success' => false, 'message' => 'Errore del servizio AI. Riprova più tardi.']);
        exit;
    }

    $result_data   = json_decode($response, true);
    $risposta      = $result_data['content'][0]['text'] ?? '';
    $tokens_input  = (int)($result_data['usage']['input_tokens']  ?? 0);
    $tokens_output = (int)($result_data['usage']['output_tokens'] ?? 0);
    $tokens_tot    = $tokens_input + $tokens_output;

    // Salva nel log per monitoraggio spesa token
    $d_safe = gdrcd_filter('in', $domanda);
    $r_safe = gdrcd_filter('in', $risposta);
    gdrcd_query("INSERT INTO chatbot_log (nome_personaggio, domanda, risposta, tokens_usati) VALUES ('$nome', '$d_safe', '$r_safe', $tokens_tot)");

    echo json_encode([
        'success'   => true,
        'risposta'  => $risposta,
        'remaining' => $DAILY_LIMIT - $used - 1,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Operazione non valida.']);
