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
function get_diff_excerpt($string_old, $string_new, $context = 6, $max_hunks = 5) {
    $old_array = explode(' ', $string_old);
    $new_array = explode(' ', $string_new);

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

        $bit = ($from > 0) ? '… ' : '';
        for ($k = $from; $k <= $to; $k++) {
            $word = htmlspecialchars($ops[$k]['word'], ENT_QUOTES, 'UTF-8');
            switch ($ops[$k]['type']) {
                case 'del': $bit .= "<del>$word</del> "; break;
                case 'add': $bit .= "<b>$word</b> "; break;
                default:    $bit .= "$word "; break;
            }
        }
        $bit = rtrim($bit);
        if ($to < $n - 1) $bit .= ' …';

        $parts[] = $bit;
    }

    return implode(' &nbsp;&nbsp; ', $parts);
}
