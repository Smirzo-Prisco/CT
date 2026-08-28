<?php

function get_decorated_diff($string_old, $string_new) {
    // Converti le stringhe in array di parole
    $old_array = explode(' ', $string_old);
    $new_array = explode(' ', $string_new);

    $old_length = count($old_array);
    $new_length = count($new_array);

    // Guardia di sicurezza: la matrice LCS sotto occupa O(old_length * new_length)
    // celle. Un articolo di ~2200 parole (es. "Regolamento Master", il piu'
    // lungo del regolamento) genera una matrice di ~5 milioni di celle che da
    // sola esaurisce il memory_limit di 128MB in produzione (Fatal error
    // verificato il 2026-08-28). Oltre soglia si rinuncia all'evidenziazione
    // parola per parola e si restituisce il testo grezzo prima/dopo, senza
    // calcolare la matrice.
    if ($old_length * $new_length > 500000) {
        return ['old' => $string_old, 'new' => $string_new];
    }

    // Creazione della matrice per la ricerca della Longest Common Subsequence (LCS)
    $matrix = [];

    for ($i = 0; $i <= $old_length; $i++) {
        $matrix[$i] = array_fill(0, $new_length + 1, 0);
    }

    // Calcolo della LCS
    for ($i = 1; $i <= $old_length; $i++) {
        for ($j = 1; $j <= $new_length; $j++) {
            if ($old_array[$i - 1] == $new_array[$j - 1]) {
                $matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
            } else {
                $matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
            }
        }
    }

    // Backtrack per trovare le differenze
    $old_decorated = '';
    $new_decorated = '';
    $i = $old_length;
    $j = $new_length;

    while ($i > 0 && $j > 0) {
        if ($old_array[$i - 1] == $new_array[$j - 1]) {
            // Parola comune
            $old_decorated = $old_array[$i - 1] . ' ' . $old_decorated;
            $new_decorated = $new_array[$j - 1] . ' ' . $new_decorated;
            $i--;
            $j--;
        } elseif ($matrix[$i - 1][$j] >= $matrix[$i][$j - 1]) {
            // Parola rimossa nel nuovo testo
            $old_decorated = "<span style='color: red; text-decoration: line-through;'>" . $old_array[$i - 1] . "</span> " . $old_decorated;
            $i--;
        } else {
            // Parola aggiunta nel nuovo testo
            $new_decorated = "<span style='color: green; font-weight: bold;'>" . $new_array[$j - 1] . "</span> " . $new_decorated;
            $j--;
        }
    }

    // Gestione delle parole rimanenti nel testo vecchio
    while ($i > 0) {
        $old_decorated = "<span style='color: red; text-decoration: line-through;'>" . $old_array[$i - 1] . "</span> " . $old_decorated;
        $i--;
    }

    // Gestione delle parole rimanenti nel testo nuovo
    while ($j > 0) {
        $new_decorated = "<span style='color: green; font-weight: bold;'>" . $new_array[$j - 1] . "</span> " . $new_decorated;
        $j--;
    }

    // Rimuovi spazi iniziali e finali
    $old_decorated = trim($old_decorated);
    $new_decorated = trim($new_decorated);

    // Restituisci le stringhe decorate con le differenze colorate
    return [
        'old' => $old_decorated,
        'new' => $new_decorated
    ];
}
?>
