<?php
/**
 * api_chatbot.php — API endpoint per il chatbot Crystal Tokyo AI
 *
 * Operazioni (GET ?op=):
 *   status  → C-token usati/rimasti oggi per l'utente corrente
 *   ask     → POST JSON { "domanda": "..." } → risposta Claude AI
 *
 * Rate limit: TOKEN_LIMIT C-token/giorno per utente autenticato.
 * Modello: claude-haiku-4-5-20251001 (Anthropic).
 *
 * @author Crystal Tokyo Dev
 */

session_start();
ob_start(); // buffer output: evita che warning/notice PHP corrompano il JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/required.php';
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}
// Nessun punto di questo file scrive in $_SESSION: richiude subito il lock invece
// di tenerlo per tutta la chiamata a Claude (op=ask puo' durare secondi).
session_write_close();

$op               = $_GET['op'] ?? '';
$nome             = gdrcd_filter('in', $_SESSION['login']);
$TOKEN_LIMIT_BASE = (int)($PARAMETERS['anthropic']['daily_token_limit'] ?? 5000);
$MAX_CHARS        = 500;

// Limite dinamico in base ai giorni di iscrizione:
//   ≤ 7 giorni  → 3× (nuovi utenti, prima settimana)
//   ≤ 30 giorni → 2× (utenti in rodaggio, fino al primo mese)
//   > 30 giorni → 1× (standard)
$pg_row      = gdrcd_query("SELECT DATEDIFF(CURDATE(), DATE(data_iscrizione)) AS giorni FROM personaggio WHERE nome = '$nome'");
$giorni_iscr = (int)($pg_row['giorni'] ?? 9999);
$TOKEN_LIMIT = $giorni_iscr <= 7  ? $TOKEN_LIMIT_BASE * 3
             : ($giorni_iscr <= 30 ? $TOKEN_LIMIT_BASE * 2
             : $TOKEN_LIMIT_BASE);

// ── op=status ──────────────────────────────────────────────────────────────

if ($op === 'status') {
    $row  = gdrcd_query("SELECT COALESCE(SUM(tokens_usati), 0) AS used FROM chatbot_log WHERE nome_personaggio = '$nome' AND DATE(created_at) = CURDATE()");
    $used = (int)($row['used'] ?? 0);
    echo json_encode([
        'success'          => true,
        'tokens_used'      => $used,
        'tokens_limit'     => $TOKEN_LIMIT,
        'tokens_remaining' => max(0, $TOKEN_LIMIT - $used),
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

    // Controllo C-token giornalieri
    $row  = gdrcd_query("SELECT COALESCE(SUM(tokens_usati), 0) AS used FROM chatbot_log WHERE nome_personaggio = '$nome' AND DATE(created_at) = CURDATE()");
    $used = (int)($row['used'] ?? 0);
    if ($used >= $TOKEN_LIMIT) {
        echo json_encode(['success' => false, 'message' => 'Hai esaurito i C-token di oggi. Torna domani!']);
        exit;
    }

    // RAG semplificato: FULLTEXT search per articoli rilevanti, fallback su tutti
    $domanda_ft = gdrcd_filter('in', $domanda);
    $result = gdrcd_query(
        "SELECT titolo, testo, tipo,
                MATCH(titolo, testo) AGAINST('$domanda_ft' IN NATURAL LANGUAGE MODE) AS score
         FROM regolamento
         WHERE tipo != 'staff'
           AND MATCH(titolo, testo) AGAINST('$domanda_ft' IN NATURAL LANGUAGE MODE) > 0
         ORDER BY score DESC
         LIMIT 5",
        'result'
    );

    $context = '';
    $trovati = 0;
    while ($art = gdrcd_query($result, 'fetch')) {
        $context .= "## [{$art['tipo']}] {$art['titolo']}\n{$art['testo']}\n\n";
        $trovati++;
    }
    gdrcd_query($result, 'free');

    // Fallback: se FULLTEXT non trova nulla, carica tutti gli articoli pubblici
    if ($trovati === 0) {
        $result = gdrcd_query("SELECT titolo, testo, tipo FROM regolamento WHERE tipo != 'staff' ORDER BY tipo, articolo", 'result');
        while ($art = gdrcd_query($result, 'fetch')) {
            $context .= "## [{$art['tipo']}] {$art['titolo']}\n{$art['testo']}\n\n";
        }
        gdrcd_query($result, 'free');
    }

    // Statuti delle razze: stessa ricerca FULLTEXT, tabella separata (statuti,
    // filtrata a id_gilda > 0 — la stessa tabella contiene anche voci mestiere
    // con id_mestiere > 0, non pertinenti qui). Solo se rilevanti (nessun
    // fallback "carica tutto": 10 razze x ~10 voci ciascuna gonfierebbe il
    // contesto anche per domande che non c'entrano nulla con le razze).
    $result_statuti = gdrcd_query(
        "SELECT g.nome AS razza, s.titolo, s.testo,
                MATCH(s.titolo, s.testo) AGAINST('$domanda_ft' IN NATURAL LANGUAGE MODE) AS score
         FROM statuti s
         JOIN gilda g ON g.id_gilda = s.id_gilda
         WHERE s.id_gilda > 0
           AND MATCH(s.titolo, s.testo) AGAINST('$domanda_ft' IN NATURAL LANGUAGE MODE) > 0
         ORDER BY score DESC
         LIMIT 3",
        'result'
    );
    while ($art = gdrcd_query($result_statuti, 'fetch')) {
        $context .= "## [Statuto razza: {$art['razza']}] {$art['titolo']}\n{$art['testo']}\n\n";
    }
    gdrcd_query($result_statuti, 'free');

    // Chiave API Anthropic
    $api_key = $PARAMETERS['anthropic']['api_key'] ?? '';
    if (empty($api_key)) {
        error_log('[chatbot] api_key Anthropic non configurata in config.inc.php');
        echo json_encode(['success' => false, 'message' => 'Servizio temporaneamente non disponibile.']);
        exit;
    }

    $system = "Sei Crystal Bot, l'assistente virtuale del gioco di ruolo Crystal Tokyo. "
            . "Non aggiungere saluti o presentazioni: vai direttamente alla risposta. "
            . "Rispondi ESCLUSIVAMENTE basandoti sulle informazioni fornite qui sotto (regolamento e statuti delle razze). "
            . "Se la risposta non è tra queste informazioni, dillo chiaramente senza inventare. "
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
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Errore di rete. Riprova.']);
        exit;
    }

    if ($httpCode !== 200) {
        error_log("[chatbot] Anthropic API HTTP $httpCode: $response");
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Errore del servizio AI. Riprova più tardi.']);
        exit;
    }

    $result_data   = json_decode($response, true);
    $risposta      = $result_data['content'][0]['text'] ?? '';
    $tokens_input  = (int)($result_data['usage']['input_tokens']  ?? 0);
    $tokens_output = (int)($result_data['usage']['output_tokens'] ?? 0);
    $tokens_tot    = $tokens_input + $tokens_output;

    // Salva nel log per monitoraggio spesa (tronca a 10000 char per sicurezza colonna)
    $d_safe = gdrcd_filter('in', mb_substr($domanda, 0, 500));
    $r_safe = gdrcd_filter('in', mb_substr($risposta, 0, 10000));
    try {
        gdrcd_query("INSERT INTO chatbot_log (nome_personaggio, domanda, risposta, tokens_usati) VALUES ('$nome', '$d_safe', '$r_safe', $tokens_tot)");
    } catch (Throwable $e) {
        error_log('[chatbot] log insert failed: ' . $e->getMessage());
    }

    $used_after = $used + $tokens_tot;
    ob_end_clean();
    echo json_encode([
        'success'          => true,
        'risposta'         => $risposta,
        'tokens_used'      => $used_after,
        'tokens_limit'     => $TOKEN_LIMIT,
        'tokens_remaining' => max(0, $TOKEN_LIMIT - $used_after),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Operazione non valida.']);
