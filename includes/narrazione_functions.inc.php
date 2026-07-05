<?php
/**
 * narrazione_functions.inc.php — Funzioni condivise per la narrazione IA
 *
 * Genera riassunti delle giocate concluse tramite Claude Haiku (API
 * Anthropic, stessa chiave/integrazione di Crystal Bot — pages/api_chatbot.php).
 * L'LLM locale (llama.cpp + Qwen2.5-3B, vedi memoria di progetto
 * "project_local_llm") resta disponibile sul server ma non è più usato in
 * questa pipeline: introduceva errori fattuali nell'estrazione che si
 * propagavano nel risultato finale. Usato sia dall'enqueue automatico in
 * endRoleSession() (includes/chat_functions.inc.php) sia dal worker
 * cron/narrazione_worker.php.
 */

// Filtro di lancio: finché la feature non è validata, attiva solo su questo
// personaggio. Rimuovere per estendere a tutti (vedi memoria di progetto
// "project_local_llm" / narrazione IA).
const NARRAZIONE_PERSONAGGI_ABILITATI = ['Latino'];

function narrazione_abilitata_per(string $pg_name): bool {
    return in_array($pg_name, NARRAZIONE_PERSONAGGI_ABILITATI, true);
}

/**
 * Chiamata all'API Anthropic (Claude Haiku), stesso pattern di
 * pages/api_chatbot.php. Ritorna null in caso di errore.
 *
 * Nota storica: in una versione precedente un "passo di map" sull'LLM
 * locale (llama.cpp) pre-condensava le giocate lunghe prima di questa
 * chiamata, per contenere i costi. Rimosso il 2026-07-05: il modello
 * locale (3B, quantizzato) introduceva errori fattuali nell'estrazione
 * (nomi storpiati, soggetti invertiti) che Claude poi "rifiniva" in bella
 * prosa senza sapere che erano già sbagliati — un problema di correttezza,
 * non solo di stile. Il contesto di Claude è ampiamente sufficiente per una
 * giocata intera in un colpo solo, e il costo resta comunque basso (vedi
 * memoria di progetto "project_local_llm" per i conti fatti).
 */
function narrazione_claude_call(string $prompt, int $max_tokens = 300): ?string {
    global $PARAMETERS;
    $api_key = $PARAMETERS['anthropic']['api_key'] ?? '';
    if (empty($api_key)) {
        error_log('[narrazione] api_key Anthropic non configurata in config.inc.php');
        return null;
    }

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => $max_tokens,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
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
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err || $http_code !== 200) {
        error_log("[narrazione] Anthropic API errore (HTTP $http_code): $curl_err $response");
        return null;
    }

    $data     = json_decode($response, true);
    $risposta = $data['content'][0]['text'] ?? null;
    if ($risposta === null || trim($risposta) === '') return null;

    $tokens_tot = (int)($data['usage']['input_tokens'] ?? 0) + (int)($data['usage']['output_tokens'] ?? 0);
    error_log("[narrazione] chiamata Claude Haiku: $tokens_tot token totali");

    return $risposta;
}

/**
 * Trascrizione COMPLETA di una giocata (una riga per messaggio, ordine
 * cronologico), senza alcun troncamento.
 */
function narrazione_righe_giocata(int $id_role): array {
    $result = gdrcd_query("SELECT mittente, testo FROM chat WHERE id_role = $id_role AND testo IS NOT NULL ORDER BY ora ASC", 'result');
    $righe = [];
    while ($row = gdrcd_query($result, 'fetch')) {
        $righe[] = $row['mittente'] . ': ' . strip_tags($row['testo']);
    }
    gdrcd_query($result, 'free');
    return $righe;
}

/**
 * Riassunto finale (2-3 frasi) a partire dalla trascrizione completa di una
 * giocata. Chiama Claude Haiku direttamente sul testo grezzo: niente
 * pre-condensazione locale, per evitare che errori di lettura di un modello
 * più debole si propaghino nel risultato finale (vedi nota storica sopra).
 */
function narrazione_riassunto_da_testo(string $testo, string $pg_name): ?string {
    $prompt = "Sei un narratore per un gioco di ruolo testuale ambientato in una città cyberpunk. " .
        "Di seguito la trascrizione completa di una scena di gioco di ruolo (giocata) conclusa.\n\n" .
        "Individua chi è presente nella scena, cosa fa concretamente \"$pg_name\" (azioni, decisioni, eventi " .
        "che gli accadono, chi dice cosa a chi) e come si conclude la scena. " .
        "Basati SOLO su quello che è scritto, senza inventare dettagli assenti e senza invertire chi fa/dice cosa a chi.\n\n" .
        "Scrivi un brevissimo riassunto narrativo (massimo 2-3 frasi, in italiano corrente, in terza persona) " .
        "dal punto di vista di \"$pg_name\".\n\n" .
        "IMPORTANTE — rispondi SOLO con quel riassunto, nient'altro: " .
        "niente elenchi puntati, niente titoli o intestazioni markdown (niente \"#\" o \"**\"), " .
        "niente analisi preliminare o ragionamento esposto, niente \"Riassunto finale:\" o frasi introduttive. " .
        "Solo il paragrafo finale, pronto per essere pubblicato così com'è.\n\n" .
        "Trascrizione:\n$testo";

    return narrazione_claude_call($prompt, 300);
}

/**
 * Riassunto breve (2-3 frasi) di una giocata dal punto di vista di un
 * singolo personaggio, a partire dalla trascrizione completa (nessun
 * pre-condensamento). Ritorna null se la giocata non ha messaggi o la
 * chiamata a Claude non va a buon fine.
 */
function narrazione_riassunto_giocata(int $id_role, string $pg_name): ?string {
    $righe = narrazione_righe_giocata($id_role);
    if (!$righe) return null;

    return narrazione_riassunto_da_testo(implode("\n", $righe), $pg_name);
}

/**
 * Accoda per il riassunto automatico i partecipanti reali (non PNG) di una
 * giocata appena chiusa. Chiamata da endRoleSession().
 */
function narrazione_enqueue_giocata_chiusa(int $id_role): void {
    $result = gdrcd_query("SELECT pg_name FROM role_session_players WHERE id_role = $id_role AND png = 0", 'result');
    while ($row = gdrcd_query($result, 'fetch')) {
        $pg_name = $row['pg_name'];
        if (!narrazione_abilitata_per($pg_name)) continue;
        $pg_esc = gdrcd_filter('in', $pg_name);
        gdrcd_query("INSERT INTO narrazione_queue (id_role, pg_name) VALUES ($id_role, '$pg_esc')");
    }
    gdrcd_query($result, 'free');
}

/**
 * Accoda nella coda automatica (narrazione_queue) le ultime N giocate
 * concluse di un personaggio, in ordine cronologico. Usata dal pannello
 * admin per un test rapido di qualità: riusa la stessa coda già gestita dal
 * worker in produzione (priorità 1), quindi non richiede alcuna azione
 * manuale sul server. Ritorna il numero di giocate accodate.
 */
function narrazione_enqueue_test(string $pg_name, int $limite = 2): int {
    $pg_esc = gdrcd_filter('in', $pg_name);
    $result = gdrcd_query("SELECT rs.id_role FROM role_sessions rs
        JOIN role_session_players rsp ON rsp.id_role = rs.id_role
        WHERE rsp.pg_name = '$pg_esc' AND rsp.png = 0 AND rs.end IS NOT NULL
        ORDER BY rs.start DESC LIMIT $limite", 'result');
    $id_roles = [];
    while ($row = gdrcd_query($result, 'fetch')) $id_roles[] = (int)$row['id_role'];
    gdrcd_query($result, 'free');

    if (!$id_roles) return 0;

    // Separatore visivo: segna dove inizia il blocco di test rispetto al testo
    // già presente (il worker non lo aggiunge per i riassunti automatici normali).
    gdrcd_query("UPDATE personaggio SET descrizione = CONCAT(COALESCE(descrizione, ''), '<hr>') WHERE nome = '$pg_esc'");

    foreach (array_reverse($id_roles) as $id_role) {
        gdrcd_query("INSERT INTO narrazione_queue (id_role, pg_name) VALUES ($id_role, '$pg_esc')");
    }
    return count($id_roles);
}

/** True se pg_name ha già una richiesta di rigenerazione non conclusa. */
function narrazione_richiesta_pendente(string $pg_name): bool {
    $pg_esc = gdrcd_filter('in', $pg_name);
    $row = gdrcd_query("SELECT id FROM narrazione_richieste
        WHERE pg_name = '$pg_esc' AND stato IN ('richiesta','approvata','in_elaborazione') LIMIT 1");
    return (bool)$row;
}
