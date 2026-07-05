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
function narrazione_llm_call(string $prompt, int $max_tokens = 300): ?string {
    $payload = json_encode([
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'max_tokens'  => $max_tokens,
        // Bassa: per un riassunto fattuale conviene un modello piccolo poco
        // "creativo" — riduce sia le invenzioni sia gli errori grammaticali
        // dovuti a un campionamento troppo libero.
        'temperature' => 0.3,
    ]);
    $ch = curl_init('http://127.0.0.1:8090/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        // Generoso: l'LLM locale è lento (~1-3 token/sec) e girando in background
        // non c'è nessuna richiesta HTTP utente in attesa — meglio un timeout
        // ampio che troncare un riassunto a metà per giocate con molti messaggi.
        CURLOPT_TIMEOUT        => 1200,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $http_code !== 200) return null;

    $data    = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    if ($content === null || trim($content) === '') return null;

    // Se il modello è stato interrotto da max_tokens a metà frase, tronchiamo
    // all'ultimo punto di fine frase per non salvare un riassunto spezzato.
    if (($data['choices'][0]['finish_reason'] ?? null) === 'length') {
        $fine_frase = array_filter([strrpos($content, '.'), strrpos($content, '!'), strrpos($content, '?')], fn($p) => $p !== false);
        if ($fine_frase) $content = substr($content, 0, max($fine_frase) + 1);
    }

    return $content;
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
 * Divide le righe di una trascrizione in blocchi che stanno nella context
 * window del modello (--ctx-size 4096 in llama-server.service), senza mai
 * spezzare un messaggio a metà.
 */
function narrazione_suddividi_in_blocchi(array $righe, int $max_chars_blocco = 5000): array {
    $blocchi   = [];
    $corrente  = '';
    foreach ($righe as $riga) {
        $candidato = $corrente === '' ? $riga : $corrente . "\n" . $riga;
        if (mb_strlen($candidato) > $max_chars_blocco && $corrente !== '') {
            $blocchi[]= $corrente;
            $corrente = $riga;
        } else {
            $corrente = $candidato;
        }
    }
    if ($corrente !== '') $blocchi[] = $corrente;
    return $blocchi;
}

/** Riassunto finale (2-3 frasi) a partire da una trascrizione (o da una fusione di mini-riassunti) che sta nel contesto. */
function narrazione_riassunto_da_testo(string $testo, string $pg_name): ?string {
    $prompt = "Sei un narratore per un gioco di ruolo testuale ambientato in una città cyberpunk. " .
        "Di seguito la trascrizione (o un elenco di fatti già estratti) di una scena di gioco di ruolo (giocata) conclusa.\n\n" .
        "Prima di rispondere, individua internamente: chi è presente nella scena, cosa fa concretamente " .
        "\"$pg_name\" (azioni, decisioni, eventi che gli accadono) e come si conclude la scena. " .
        "Basati SOLO su quello che è scritto, senza inventare dettagli assenti.\n\n" .
        "Poi scrivi un brevissimo riassunto (massimo 2-3 frasi, in italiano, senza markdown, in terza persona) " .
        "dal punto di vista di \"$pg_name\", riportando solo il riassunto finale (non l'analisi).\n\n" .
        "Testo:\n$testo";

    return narrazione_llm_call($prompt, 300);
}

/**
 * Riassunto breve (2-3 frasi) di una giocata dal punto di vista di un
 * singolo personaggio. Ritorna null se la giocata non ha messaggi o l'LLM
 * non risponde correttamente.
 *
 * Le giocate che stanno in un solo blocco (context window) vengono
 * riassunte con una sola chiamata. Quelle più lunghe vengono prima divise
 * in blocchi e riassunte fattualmente blocco per blocco (map), poi i
 * mini-riassunti vengono fusi in un'unica chiamata finale (reduce): così
 * nessuna parte della giocata viene troncata/ignorata, a costo di qualche
 * chiamata in più all'LLM per le giocate lunghe.
 */
function narrazione_riassunto_giocata(int $id_role, string $pg_name): ?string {
    $righe = narrazione_righe_giocata($id_role);
    if (!$righe) return null;

    $blocchi = narrazione_suddividi_in_blocchi($righe);

    if (count($blocchi) === 1) {
        return narrazione_riassunto_da_testo($blocchi[0], $pg_name);
    }

    $mini_riassunti = [];
    foreach ($blocchi as $i => $blocco) {
        $prompt = "Sei un narratore per un gioco di ruolo testuale ambientato in una città cyberpunk. " .
            "Di seguito la parte " . ($i + 1) . " di " . count($blocchi) . " della trascrizione di una scena di gioco di ruolo (le altre parti seguono/precedono cronologicamente). " .
            "Elenca in modo sintetico e fattuale (poche righe, senza markdown, in italiano) solo ciò che succede in questa parte " .
            "riguardo al personaggio \"$pg_name\": azioni, decisioni, eventi. Basati SOLO su questo testo, senza inventare nulla.\n\n" .
            "Trascrizione (parte " . ($i + 1) . "):\n$blocco";
        $mini = narrazione_llm_call($prompt, 150);
        if ($mini !== null) $mini_riassunti[] = trim($mini);
    }
    if (!$mini_riassunti) return null;

    return narrazione_riassunto_da_testo(implode("\n", $mini_riassunti), $pg_name);
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
