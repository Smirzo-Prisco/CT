<?php

/**
 * get_diff_excerpt() — estratto compatto delle differenze parola per parola
 * tra due testi, con solo le porzioni cambiate (più un po' di contesto),
 * invece dell'intero testo vecchio+nuovo duplicato.
 *
 * Usato dalla notifica di modifica al regolamento (api_regolamento.php):
 * prima mostrava l'articolo intero due volte con ogni parola diversa colorata
 * — illeggibile per una modifica di poche parole in un articolo lungo.
 *
 * @param string $string_old
 * @param string $string_new
 * @param int    $context   parole di contesto invariato da mostrare attorno a ogni cambiamento
 * @param int    $max_hunks oltre questa soglia di blocchi di modifica sparsi l'estratto
 *                          non sarebbe più utile di leggere l'intero articolo: si rinuncia
 * @return string|null stringa HTML pronta per il post (span dentro per del/b),
 *                      stringa vuota se i testi sono identici, null se non è
 *                      stato possibile calcolare un estratto sensato (testo troppo
 *                      lungo per il diff, o troppi punti di modifica sparsi)
 */
/**
 * Spezza il testo in token per il diff: ogni tag HTML (<b>, </b>, <li>...) e'
 * un token a se', il resto e' spezzato sugli spazi come parole normali.
 * Necessario perche' il regolamento contiene tag scritti a mano dagli admin
 * per la formattazione (es. "gilda.<b>Come fondare...") — senza questo, un
 * explode(' ', ...) tratterebbe "gilda.<b>Come" come un'unica parola,
 * confrontando il diff a un livello troppo grezzo per riconoscere che il tag
 * e' markup invariato, non testo cambiato.
 */
function tokenize_diff_text(string $text): array {
    preg_match_all('/<[^>]+>|[^\s<]+/u', $text, $m);
    return $m[0];
}

function get_diff_excerpt($string_old, $string_new, $context = 6, $max_hunks = 5) {
    $old_array = tokenize_diff_text($string_old);
    $new_array = tokenize_diff_text($string_new);

    $old_length = count($old_array);
    $new_length = count($new_array);

    // Guardia di sicurezza: la matrice LCS sotto occupa O(old_length * new_length)
    // celle. Un articolo di ~2200 parole (es. "Regolamento Master", il piu'
    // lungo del regolamento) genera una matrice di ~5 milioni di celle che da
    // sola esaurisce il memory_limit di 128MB in produzione (Fatal error
    // verificato il 2026-08-28). Oltre soglia si rinuncia al diff.
    if ($old_length * $new_length > 500000) {
        return null;
    }

    // Creazione della matrice per la ricerca della Longest Common Subsequence (LCS)
    $matrix = [];
    for ($i = 0; $i <= $old_length; $i++) {
        $matrix[$i] = array_fill(0, $new_length + 1, 0);
    }
    for ($i = 1; $i <= $old_length; $i++) {
        for ($j = 1; $j <= $new_length; $j++) {
            if ($old_array[$i - 1] == $new_array[$j - 1]) {
                $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
            } else {
                $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
            }
        }
    }

    // Backtrack: a differenza del vecchio get_decorated_diff() (due stringhe
    // separate old/new), qui costruisco un'UNICA sequenza ordinata di
    // operazioni (equal/del/add) — necessaria per poter isolare i punti di
    // modifica e prendere solo il contesto attorno a ciascuno.
    $ops = [];
    $i = $old_length;
    $j = $new_length;

    while ($i > 0 && $j > 0) {
        if ($old_array[$i - 1] == $new_array[$j - 1]) {
            $ops[] = ['type' => 'equal', 'word' => $old_array[$i - 1]];
            $i--; $j--;
        } elseif ($matrix[$i - 1][$j] >= $matrix[$i][$j - 1]) {
            $ops[] = ['type' => 'del', 'word' => $old_array[$i - 1]];
            $i--;
        } else {
            $ops[] = ['type' => 'add', 'word' => $new_array[$j - 1]];
            $j--;
        }
    }
    while ($i > 0) { $ops[] = ['type' => 'del', 'word' => $old_array[$i - 1]]; $i--; }
    while ($j > 0) { $ops[] = ['type' => 'add', 'word' => $new_array[$j - 1]]; $j--; }

    $ops = array_reverse($ops);
    $n   = count($ops);

    $changeIdx = [];
    foreach ($ops as $idx => $op) {
        if ($op['type'] !== 'equal') $changeIdx[] = $idx;
    }

    if (empty($changeIdx)) return ''; // testi identici (es. solo il titolo e' cambiato)

    // Raggruppa i punti di modifica vicini (entro 2*$context parole l'uno
    // dall'altro) in un unico blocco, cosi' non si spezza inutilmente il
    // contesto in due estratti separati per due parole cambiate vicine.
    $hunks     = [];
    $hunkStart = $changeIdx[0];
    $hunkEnd   = $changeIdx[0];
    foreach ($changeIdx as $idx) {
        if ($idx - $hunkEnd > $context * 2) {
            $hunks[]   = [$hunkStart, $hunkEnd];
            $hunkStart = $idx;
        }
        $hunkEnd = $idx;
    }
    $hunks[] = [$hunkStart, $hunkEnd];

    // Troppi punti di modifica sparsi in tutto l'articolo: un estratto a
    // frammenti sarebbe piu' confuso del testo intero, meglio rinunciare.
    if (count($hunks) > $max_hunks) return null;

    $parts = [];
    foreach ($hunks as [$start, $end]) {
        $from = max(0, $start - $context);
        $to   = min($n - 1, $end + $context);

        // NIENTE htmlspecialchars qui: il testo del regolamento e' contenuto
        // fidato scritto dagli admin, che include gia' tag HTML veri e propri
        // (<b>, <i>, <ol>, <li>...) usati per la formattazione degli articoli
        // — stessa convenzione di abilita.descrizione/note_fato altrove nel
        // progetto. Scapparli li mostrerebbe come testo letterale invece che
        // renderizzarli.
        $bit = ($from > 0) ? '… ' : '';
        for ($k = $from; $k <= $to; $k++) {
            $word  = $ops[$k]['word'];
            // Un token-tag (es. "<b>") non prende uno spazio dopo di se': a
            // rendering il tag sparisce, uno spazio li' introdurrebbe uno
            // spazio visibile che nell'originale non c'era.
            $space = preg_match('/^<[^>]+>$/', $word) ? '' : ' ';
            switch ($ops[$k]['type']) {
                case 'del': $bit .= "<del>$word</del>$space"; break;
                case 'add': $bit .= "<b>$word</b>$space"; break;
                default:    $bit .= "$word$space"; break;
            }
        }
        $bit = rtrim($bit);
        if ($to < $n - 1) $bit .= ' …';

        // La finestra di contesto puo' tagliare a meta' una coppia di tag
        // dell'articolo originale (es. <i> aperto prima della finestra, mai
        // chiuso dentro l'estratto) — se restasse un tag aperto alla fine,
        // la formattazione "colerebbe" nel resto del post. Richiudo qui
        // tutto cio' che risulta ancora aperto.
        $parts[] = balance_html_tags($bit);
    }

    return implode(' &nbsp;&nbsp; ', $parts);
}

/**
 * Richiude eventuali tag HTML rimasti aperti in $html (es. una finestra di
 * contesto che taglia a meta' un <i>...</i> dell'articolo originale) —
 * altrimenti la formattazione "colerebbe" nel resto del post che segue
 * l'estratto. Tag di chiusura orfani (nessun apertura corrispondente in
 * $html) vengono lasciati: il browser li ignora, innocui.
 */
function balance_html_tags(string $html): string {
    $void = ['br', 'hr', 'img'];
    preg_match_all('/<(\/?)([a-zA-Z0-9]+)[^>]*>/', $html, $m);
    $stack = [];
    foreach ($m[1] as $idx => $isClose) {
        $tag = strtolower($m[2][$idx]);
        if (in_array($tag, $void, true)) continue;
        if ($isClose === '/') {
            for ($k = count($stack) - 1; $k >= 0; $k--) {
                if ($stack[$k] === $tag) { array_splice($stack, $k, 1); break; }
            }
        } else {
            $stack[] = $tag;
        }
    }
    foreach (array_reverse($stack) as $tag) {
        $html .= "</$tag>";
    }
    return $html;
}
