<?php
/**
 * narrazione_functions.inc.php — Funzioni condivise per la narrazione IA
 *
 * Genera riassunti delle giocate concluse tramite l'LLM locale (llama.cpp +
 * Qwen2.5-3B, http://127.0.0.1:8090, vedi memoria di progetto
 * "project_local_llm"). Usato sia dall'enqueue automatico in
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

/** Chiamata HTTP al server llama.cpp locale. Ritorna null in caso di errore. */
function narrazione_llm_call(string $prompt, int $max_tokens = 150): ?string {
    $payload = json_encode([
        'messages'   => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => $max_tokens,
    ]);
    $ch = curl_init('http://127.0.0.1:8090/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 300,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $http_code !== 200) return null;

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

/**
 * Trascrizione testuale di una giocata, troncata per stare nella context
 * window del modello (--ctx-size 4096 in llama-server.service).
 */
function narrazione_trascrizione_giocata(int $id_role, int $max_chars = 6000): string {
    $result = gdrcd_query("SELECT mittente, testo FROM chat WHERE id_role = $id_role AND testo IS NOT NULL ORDER BY ora ASC", 'result');
    $righe = [];
    while ($row = gdrcd_query($result, 'fetch')) {
        $righe[] = $row['mittente'] . ': ' . strip_tags($row['testo']);
    }
    gdrcd_query($result, 'free');

    $trascrizione = implode("\n", $righe);
    if (strlen($trascrizione) > $max_chars) {
        $trascrizione = '[...]' . substr($trascrizione, -$max_chars);
    }
    return $trascrizione;
}

/**
 * Riassunto breve (2-3 frasi) di una giocata dal punto di vista di un
 * singolo personaggio. Ritorna null se la giocata non ha messaggi o l'LLM
 * non risponde correttamente.
 */
function narrazione_riassunto_giocata(int $id_role, string $pg_name): ?string {
    $trascrizione = narrazione_trascrizione_giocata($id_role);
    if (trim($trascrizione) === '') return null;

    $prompt = "Sei un narratore per un gioco di ruolo testuale ambientato in una città cyberpunk. " .
        "Di seguito la trascrizione di una scena di gioco di ruolo (giocata) conclusa. " .
        "Scrivi un brevissimo riassunto (massimo 2-3 frasi, in italiano, senza markdown, in terza persona) " .
        "dal punto di vista del personaggio \"$pg_name\": cosa gli è successo o cosa ha fatto in questa scena.\n\n" .
        "Trascrizione:\n$trascrizione";

    return narrazione_llm_call($prompt, 150);
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

/** True se pg_name ha già una richiesta di rigenerazione non conclusa. */
function narrazione_richiesta_pendente(string $pg_name): bool {
    $pg_esc = gdrcd_filter('in', $pg_name);
    $row = gdrcd_query("SELECT id FROM narrazione_richieste
        WHERE pg_name = '$pg_esc' AND stato IN ('richiesta','approvata','in_elaborazione') LIMIT 1");
    return (bool)$row;
}
