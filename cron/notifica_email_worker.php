<?php
/**
 * notifica_email_worker.php — invia le notifiche email accodate in "notifiche"
 *
 * Raggruppa per (nome, riferimento_id): più notifiche pendenti per lo stesso
 * post entro l'intervallo fra due esecuzioni diventano una sola email
 * riepilogativa invece di N email separate (anti-spam, vedi brief Fase E).
 * L'intervallo stesso (ogni 5 minuti) è la "finestra" — nessun timer per
 * singola notifica, il batch naturale del cron basta.
 *
 * nuovo_dm: riferimento_id e' l'id della riga sms (vedi
 * queueNewDmEmailNotification in custom_functions.inc.php) — usato per
 * includere mittente e testo del messaggio nell'email invece del solo
 * avviso generico. Ogni riga resta comunque un gruppo a se', un'email
 * per DM (niente post da raggruppare come per gli altri eventi).
 *
 * Aggiungere al crontab del server (ogni 5 minuti):
 *   * /5 * * * * php /var/www/crystaltokyo/gdrcd/cron/notifica_email_worker.php >> /var/log/notifica_email_worker.log 2>&1
 */

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();

$res = gdrcd_query("SELECT id, nome, evento, riferimento_id FROM notifiche
    WHERE canale = 'email' AND stato = 'pending'
    ORDER BY nome, riferimento_id", 'result');

$gruppi = [];
while ($row = gdrcd_query($res, 'fetch')) {
    // nuovo_dm: includiamo comunque l'id della riga nella chiave (non solo
    // riferimento_id) cosi' ogni DM resta un gruppo separato invece di
    // fondersi con gli altri DM in coda.
    $key = $row['evento'] === 'nuovo_dm'
        ? $row['nome'] . '|nuovo_dm|' . $row['id']
        : $row['nome'] . '|' . $row['evento'] . '|' . $row['riferimento_id'];

    if (!isset($gruppi[$key])) {
        $gruppi[$key] = ['nome' => $row['nome'], 'evento' => $row['evento'], 'riferimento_id' => (int)$row['riferimento_id'], 'ids' => []];
    }
    $gruppi[$key]['ids'][] = (int)$row['id'];
}
gdrcd_query($res, 'free');

$inviate  = 0;
$fallite  = 0;

foreach ($gruppi as $g) {
    $nome_f = gdrcd_filter('in', $g['nome']);
    $pg     = gdrcd_query("SELECT email FROM personaggio WHERE nome = '$nome_f' LIMIT 1");

    if (!$pg || empty($pg['email'])) {
        // Nessuna email da usare: non e' un errore di invio, non ritentare —
        // segna failed cosi' la riga esce dalla coda "pending" per sempre.
        $ids_list = implode(',', $g['ids']);
        gdrcd_query("UPDATE notifiche SET stato = 'failed' WHERE id IN ($ids_list)");
        $fallite++;
        continue;
    }

    $count = count($g['ids']);

    if ($g['evento'] === 'nuovo_dm') {
        // riferimento_id = sms.id: recupera mittente e testo per mostrarli
        // direttamente nell'email invece del solo avviso generico.
        $sms = gdrcd_query("SELECT mittente_nome, testo FROM sms WHERE id = " . $g['riferimento_id']);

        if ($sms) {
            $mittente_html = htmlspecialchars($sms['mittente_nome'], ENT_QUOTES, 'UTF-8');
            // testo e' gia' salvato pronto per il rendering HTML in chat
            // (stesso campo usato da MessagesInbox.jsx), non serve escaping.
            $subject = "Nuovo messaggio privato da $mittente_html — Crystal Tokyo";
            $body    = "<b>$mittente_html</b> ti ha scritto:<br><br>"
                     . '<div style="padding:10px;background:#f5f5f5;border-radius:6px;">' . $sms['testo'] . '</div><br>'
                     . '<a href="https://crystaltokyo.it/main.php?page=messages_center">Rispondi</a>';
        } else {
            // sms cancellato/non trovato: fallback all'avviso generico di prima
            $subject = 'Hai un nuovo messaggio privato — Crystal Tokyo';
            $body    = 'Hai ricevuto un nuovo messaggio privato su Crystal Tokyo.<br><br>'
                     . '<a href="https://crystaltokyo.it/main.php?page=messages_center">Accedi per leggerlo</a>';
        }
    } elseif ($g['evento'] === 'chat_off_non_letta') {
        // Niente URL diretto: la chattina off e' un popover della HUD, non
        // una pagina propria — il link porta alla home.
        $subject = 'Hai messaggi non letti nella chattina off — Crystal Tokyo';
        $body    = 'Ci sono messaggi non letti nella chattina off su Crystal Tokyo.<br><br>'
                 . '<a href="https://crystaltokyo.it/main.php">Accedi per leggerli</a>';
    } elseif ($g['evento'] === 'calendario_nuovo_impegno') {
        // riferimento_id = calendario_eventi.id — niente pagina propria per
        // un singolo evento, il link porta al calendario.
        $subject = $count > 1
            ? "Sei stato aggiunto a $count impegni nel calendario — Crystal Tokyo"
            : 'Sei stato aggiunto a un impegno nel calendario — Crystal Tokyo';
        $body = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '<br><br>'
              . '<a href="https://crystaltokyo.it/main.php?page=agenda_center">Apri il calendario</a>';
    } else {
        $thread = gdrcd_query("SELECT titolo FROM messaggioaraldo
            WHERE id_messaggio = " . $g['riferimento_id'] . " AND id_messaggio_padre = -1 LIMIT 1");
        $titolo = $thread['titolo'] ?? 'un thread del forum';

        $subject = $count > 1
            ? "Ci sono $count nuovi commenti su \"$titolo\" — Crystal Tokyo"
            : "Nuovo commento su \"$titolo\" — Crystal Tokyo";
        $body = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '<br><br>'
              . '<a href="https://crystaltokyo.it/main.php?page=forum&thread=' . $g['riferimento_id'] . '">Accedi al forum per leggerli</a>';
    }

    $ids_list = implode(',', $g['ids']);
    if (send_mail($pg['email'], $subject, $body)) {
        gdrcd_query("UPDATE notifiche SET stato = 'sent', data_invio = NOW() WHERE id IN ($ids_list)");
        $inviate++;
    } else {
        gdrcd_query("UPDATE notifiche SET stato = 'failed' WHERE id IN ($ids_list)");
        $fallite++;
    }
}

echo date('Y-m-d H:i:s') . " — notifica email worker: $inviate email inviate, $fallite fallite/saltate\n";
