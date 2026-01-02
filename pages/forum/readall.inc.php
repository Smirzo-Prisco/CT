<?php
// Cancella tutte le letture per il login corrente
$erase = gdrcd_query("DELETE FROM araldo_letto WHERE nome = '" . $_SESSION['login'] . "'", 'result');

// Recupera i messaggi principali della tabella messaggioaraldo (solo quelli con id_messaggio_padre = -1)
$result = gdrcd_query("SELECT m.id_messaggio, m.id_araldo, a.tipo 
                       FROM messaggioaraldo m 
                       INNER JOIN araldo a ON m.id_araldo = a.id_araldo 
                       WHERE m.id_messaggio_padre = -1", 'result');

// Definisci il login corrente e i privilegi
$login = $_SESSION['login'];
$is_admin = $_SESSION['admin'] == 1;
$is_master = $_SESSION['master'] == 1;
$is_moderatore = $_SESSION['moderatore'] == 1;
$is_guida = $_SESSION['guida'] == 1;

// Ciclo su tutti i messaggi trovati
while ($row = gdrcd_query($result, 'fetch')) {
    $id_araldo = $row['id_araldo'];
    $tipo_araldo = $row['tipo'];

    // Controlla se l'utente ha già letto il thread
    $esiste = gdrcd_query("SELECT id FROM araldo_letto WHERE thread_id = " . $row['id_messaggio'] . " AND nome = '$login'");

    // Se non è già letto
    if ($esiste['id'] <= 0) {
        // Condizione base: se tipo = 0, ignora gli id_araldo specifici
        $escludi_araldi = [12, 140];
        if ($tipo_araldo == 0 && in_array($id_araldo, $escludi_araldi)) {
            continue;
        }

        // Condizioni in base ai privilegi
        if ($is_admin) {
            // Se è admin, inserisce tutti i messaggi
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('$login', $id_araldo, " . $row['id_messaggio'] . ")");
        } elseif ($is_master && in_array($tipo_araldo, [0, 1, 2, 3, 5, 6, 7])) {
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('$login', $id_araldo, " . $row['id_messaggio'] . ")");
        } elseif ($is_moderatore && in_array($tipo_araldo, [0, 1, 2, 3, 5, 6, 8])) {
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('$login', $id_araldo, " . $row['id_messaggio'] . ")");
        } elseif ($is_guida && in_array($tipo_araldo, [0, 1, 2, 3, 5, 6, 9])) {
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('$login', $id_araldo, " . $row['id_messaggio'] . ")");
        } elseif (in_array($tipo_araldo, [0, 1, 2, 3, 5, 6])) {
            // Nessun privilegio particolare
            gdrcd_query("INSERT INTO araldo_letto (nome, araldo_id, thread_id) VALUES ('$login', $id_araldo, " . $row['id_messaggio'] . ")");
        }
    }
}

// Libera i risultati dalla memoria
gdrcd_query($result, 'free');



// Step 6: Aggiornamento o inserimento nella tabella `tokyobook_lettura` con `NOW()`

// Verifica se l'utente esiste già nella tabella tokyobook_lettura
$query_lettura = "
    SELECT *
    FROM tokyobook_lettura
    WHERE login = '" . $_SESSION['login'] . "'";

// Step 3: Esecuzione della query e controllo del numero di righe restituite
$lettura_result = gdrcd_query($query_lettura, 'result');
$num_rows = gdrcd_query($lettura_result, 'num_rows');

// Se l'utente esiste, aggiorna tutti i campi con NOW()
if ($num_rows > 0) {
    $query_update = "
        UPDATE tokyobook_lettura 
        SET tokyobook = NOW(), pagina_personale = NOW(), icc = NOW(), corte = NOW(), crystal_news = NOW()
        WHERE login = '" . $_SESSION['login'] . "'";
    gdrcd_query($query_update);
} else {
    // Se l'utente non esiste, inserisce un nuovo record con tutti i campi su NOW()
    $query_insert = "
        INSERT INTO tokyobook_lettura (login, tokyobook, pagina_personale, icc, corte, crystal_news)
        VALUES ('" . $_SESSION['login'] . "', NOW(), NOW(), NOW(), NOW(), NOW())";
    gdrcd_query($query_insert);
}