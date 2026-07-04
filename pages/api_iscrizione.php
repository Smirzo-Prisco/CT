<?php
/**
 * api_iscrizione.php — API pubblica per la pagina di iscrizione
 *
 * op=options  → razze e mestieri disponibili per i dropdown
 * op=termini  → testo dei termini e condizioni (disclaimer + regolamento)
 */

require_once dirname(__DIR__) . '/includes/required.php';

$handleDBConnection = gdrcd_connect();

header('Content-Type: application/json; charset=utf-8');

$op = $_GET['op'] ?? '';

if ($op === 'options') {

    $razze = [];
    $r = gdrcd_query("SELECT id_gilda, nome FROM gilda WHERE visibile=1 ORDER BY nome", 'result');
    while ($row = gdrcd_query($r, 'fetch')) {
        $razze[] = ['id' => (int)$row['id_gilda'], 'nome' => $row['nome']];
    }
    gdrcd_query($r, 'free');

    $mestieri = [];
    // Solo i mestieri veri (tipo=1): esclude le gilde giocatore, che condividono
    // la stessa tabella ruolo_mestiere ma non sono scelte in fase di iscrizione
    $m = gdrcd_query("SELECT rm.id_ruolo, rm.nome_ruolo FROM ruolo_mestiere rm JOIN mestiere m ON rm.mestiere = m.id_mestiere WHERE rm.livello_mestiere = 3 AND m.tipo = 1 ORDER BY rm.nome_ruolo", 'result');
    while ($row = gdrcd_query($m, 'fetch')) {
        $mestieri[] = ['id' => (int)$row['id_ruolo'], 'nome' => $row['nome_ruolo']];
    }
    gdrcd_query($m, 'free');

    echo json_encode(['success' => true, 'razze' => $razze, 'mestieri' => $mestieri]);

} elseif ($op === 'termini') {

    echo json_encode([
        'success'    => true,
        'disclaimer' => $MESSAGE['register']['disclaimer'] ?? '',
        'regolamento' => $MESSAGE['register']['rules_read'] ?? '',
    ]);

} else {
    echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}

gdrcd_close_connection($handleDBConnection);
