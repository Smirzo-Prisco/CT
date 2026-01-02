<?php
session_start();
include('../../includes/constant_values.inc.php');
include('../../config.inc.php');
include('../../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../../includes/functions.inc.php');

// Imposta il mittente come "System" o un altro utente di default
$mittente = 'System';
$testo_messaggio = 'Questo è un messaggio globale per tutti gli utenti della land.';

// Trova l'ultimo id_conversazione e calcola il nuovo id_conversazione
$sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
$result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
$row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
$nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

// Il nuovo id_conversazione sarà anche il gruppo_id per il messaggio globale
$gruppo_id = $nuovo_id_conversazione;

// Inserisci il messaggio globale nella tabella sms
$sql_inserisci_messaggio = "
    INSERT INTO sms (mittente_nome, testo, gruppo_id, id_conversazione, tipo_messaggio, ongame, is_globale, ora_spedizione)
    VALUES ('$mittente', '$testo_messaggio', $gruppo_id, $nuovo_id_conversazione, 'gruppo', 0, 1, NOW())
";
gdrcd_query($sql_inserisci_messaggio);

// Recupera tutti i personaggi
$sql_personaggi = "SELECT nome FROM personaggio";
$result_personaggi = gdrcd_query($sql_personaggi, 'result');

// Inserisci tutti i personaggi nella tabella partecipazione_gruppo
while ($row_personaggio = gdrcd_query($result_personaggi, 'fetch')) {
    $nome_personaggio = $row_personaggio['nome'];
    
    $sql_inserisci_partecipante = "
        INSERT INTO partecipazione_gruppo (gruppo_id, utente_nome, lettura)
        VALUES ($gruppo_id, '$nome_personaggio', 0)
    ";
    gdrcd_query($sql_inserisci_partecipante);
}

// Messaggio di conferma
echo "Messaggio globale creato e inviato a tutti i personaggi.";
?>
