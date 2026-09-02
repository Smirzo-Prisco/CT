<?php
/**
 * 2026_09_02_regolamento_notifiche_excerpt.php — riformatta i post storici
 * di notifica modifica regolamento (thread 264815 in messaggioaraldo) nel
 * nuovo formato "estratto compatto + link", introdotto in api_regolamento.php
 * al posto del vecchio "intero articolo due volte, ogni parola diversa colorata".
 *
 * Il testo vecchio/nuovo non e' salvato separatamente: viene ricostruito
 * dagli span colorati gia' presenti nell'HTML salvato (<span style='color:red...'>
 * = parola rimossa, <span style='color:green...'> = parola aggiunta, testo
 * semplice = parola invariata) — spogliando i tag si riottiene l'old_testo/
 * new_testo originale, passato a get_diff_excerpt() (stessa funzione usata
 * dal codice live). Il link all'articolo viene risolto per titolo (best
 * effort: se l'articolo e' stato rinominato o cancellato da allora, il post
 * riformattato semplicemente non avra' il link).
 *
 * Uso via SSH:
 *   php migrations/2026_09_02_regolamento_notifiche_excerpt.php            (dry-run, non scrive nulla)
 *   php migrations/2026_09_02_regolamento_notifiche_excerpt.php --apply    (backup + scrive davvero)
 *
 * Una tantum. Rieseguirlo con --apply dopo un primo --apply e' innocuo ma
 * inutile (i messaggi sarebbero gia' nel nuovo formato, che il parser sotto
 * non riconosce piu' come "vecchio formato" e quindi salta).
 */

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../pages/function_color.php');
gdrcd_connect();

const ID_MEX = 264815;

$apply = in_array('--apply', $argv, true);

// ── Backup (solo in modalita' --apply, solo le righe che stiamo per toccare) ──
if ($apply) {
    $backup_table = 'messaggioaraldo_backup_regolamento_20260902';
    gdrcd_query("DROP TABLE IF EXISTS `$backup_table`");
    gdrcd_query("CREATE TABLE `$backup_table` AS SELECT * FROM messaggioaraldo WHERE id_messaggio_padre = " . ID_MEX);
    $n_backup = gdrcd_query("SELECT COUNT(*) AS n FROM `$backup_table`")['n'];
    echo "Backup creato: $backup_table ($n_backup righe)\n\n";
}

// ── Regex sul template esatto scritto dal vecchio api_regolamento.php ──
// (vedi git history del file prima di questa migrazione)
const PATTERN = '/Prima della modifica<\/b><br>\[spoiler\]<b>Titolo<\/b>:(.*?)<br><b>Testo<\/b>:(.*?)\[\/spoiler\].*?'
              . '<b>Modifica<\/b><br>\[spoiler\]<b>Titolo<\/b>:(.*?)<br><b>Testo<\/b>:(.*?)\[\/spoiler\]/s';

/** Rimuove i <span style='color:...'>...</span> mantenendo il testo, per riottenere la parola originale. */
function strip_diff_spans(string $decorated): string {
    return preg_replace('/<span[^>]*>|<\/span>/', '', $decorated);
}

$result = gdrcd_query("SELECT id_messaggio, messaggio FROM messaggioaraldo WHERE id_messaggio_padre = " . ID_MEX . " ORDER BY id_messaggio", 'result');

// Su richiesta esplicita: aggiornati solo i post con SIA estratto SIA link
// risolti — quelli senza uno dei due (modifica troppo estesa, o articolo
// rinominato/cancellato da allora) restano intoccati nel vecchio formato.
$n_ok = 0; $n_skip = 0; $n_no_excerpt = 0; $n_no_link = 0; $n_applied = 0; $n_ignorato = 0;

while ($row = gdrcd_query($result, 'fetch')) {
    $id = (int)$row['id_messaggio'];

    if (!preg_match(PATTERN, $row['messaggio'], $m)) {
        echo "[$id] SALTATO — non corrisponde al formato atteso (post non standard o gia' migrato)\n";
        $n_skip++;
        continue;
    }

    [, , $old_decorated, $new_titolo, $new_decorated] = $m;

    $old_testo = strip_diff_spans($old_decorated);
    $new_testo = strip_diff_spans($new_decorated);
    $new_titolo = trim($new_titolo);

    $excerpt = get_diff_excerpt($old_testo, $new_testo);
    if (!$excerpt) $n_no_excerpt++;

    $titolo_esc = gdrcd_filter('in', $new_titolo);
    $art = gdrcd_query("SELECT articolo FROM regolamento WHERE titolo = '$titolo_esc' LIMIT 1");
    if (!$art) $n_no_link++;

    if (!$excerpt || !$art) {
        $n_ignorato++;
        if (!$apply) {
            echo "[$id] \"$new_titolo\" — IGNORATO"
                . (!$excerpt ? " (nessun estratto)" : "")
                . (!$art ? " (nessun link)" : "") . "\n";
        }
        continue;
    }

    // Niente mb_substr: il limite di lunghezza e' gia' applicato dentro
    // get_diff_excerpt() a blocchi interi — un mb_substr qui poteva tagliare
    // a meta' un tag e inghiottire tutto cio' che segue, link compreso
    // (bug trovato e corretto il 2026-09-02 sul post live 300042).
    $titolo_html = htmlspecialchars($new_titolo, ENT_QUOTES, 'UTF-8');
    $nuovo_messaggio = "<b>" . $titolo_html . "</b> è stato modificato.<br>"
        . "<br><b>Cosa è cambiato</b><br>" . $excerpt . "<br>"
        . "<br><a href=\"user_regolamento_testo.php?articolo=" . (int)$art['articolo'] . "\" target=\"_blank\">→ Vai all'articolo</a>";

    $n_ok++;

    if (!$apply) {
        echo "[$id] \"$new_titolo\" — vecchio: " . strlen($row['messaggio']) . " char, nuovo: " . strlen($nuovo_messaggio) . " char\n";
        continue;
    }

    gdrcd_query("UPDATE messaggioaraldo SET messaggio = '" . gdrcd_filter('in', $nuovo_messaggio) . "' WHERE id_messaggio = $id");
    $n_applied++;
}

echo "\n---\n";
echo "Totale: $n_ok riformattati, $n_ignorato ignorati (senza estratto e/o senza link), $n_skip saltati (formato non riconosciuto)\n";
if ($apply) {
    echo "Applicati: $n_applied UPDATE\n";
} else {
    echo "DRY RUN — nessuna scrittura eseguita. Rilancia con --apply per applicare davvero.\n";
}
