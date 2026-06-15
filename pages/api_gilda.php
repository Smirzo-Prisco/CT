<?php
if(isset($_GET['op']) && $_GET['op'] != '') {
    session_start();
    require_once(__DIR__ . '/../config.inc.php');
    require_once(__DIR__ . '/../includes/required.php');
    require_once(__DIR__ . '/../includes/functions.inc.php');
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');
    
    // IMPORTANTE: Solo per le richieste AJAX
    header('Content-Type: application/json');

    if (empty($_SESSION['login'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    /*********************  Recupero i dati dell'utente che voglio modificare   */
    switch ($_GET['op']) {
        case 'getGuild':  // Recupero i dati della gilda
            $id = (int)$_GET['id'];
            $gilda = gdrcd_query("SELECT * FROM gilda WHERE id_gilda = $id");
            
            if ($gilda) echo json_encode($gilda);
            else echo json_encode(['error' => false, 'message' => 'Gilda non trovata']);
            
            break;
        case 'deleteGuild':  // Elimino la gilda
            $id = (int)$_GET['id'];
            $gilda = gdrcd_query("DELETE FROM gilda WHERE id_gilda = $id");
            $ruoli = gdrcd_query("DELETE FROM ruolo WHERE gilda = $id");
            
            if ($gilda && $ruoli) echo json_encode(['success' => true, 'message' => 'Gilda eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Gilda non trovata']);
            
            break;
        case 'saveGuild':   // Salvo la gilda
            try {
                $id = isset($_POST['id_gilda']) ? (int)$_POST['id_gilda'] : 0;
                $isEdit = ($id > 0);
                $_POST['visibile'] = isset($_POST['visibile']) ? 1 : 0;
                
                // Verifica dati obbligatori
                if (empty($_POST['nome']) || empty($_POST['tipo'])) echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                
                // Verifica permessi per modifica
                if ($isEdit) {
                    $guildEsistente = gdrcd_query("SELECT * FROM gilda WHERE id_gilda = $id");

                    if (!$guildEsistente || !canEditOggetto($guildEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questa gilda']);
                }

                // Gestione immagine
                $dati = saveImgGuild($_POST, $isEdit ? $guildEsistente['immagine'] : null, $_FILES);
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) $setParts[] = "`$campo` = '" . gdrcd_filter('in', $valore) . "'";
                    
                    $query = "UPDATE gilda SET ".implode(', ', $setParts)." WHERE id_gilda = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_gilda') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".gdrcd_filter('in', $valore)."'";
                    }
                    
                    $query = "INSERT INTO gilda (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }
                
                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Gilda salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della gilda']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'removeGuildPg':  // Elimino il ruolo del personaggio
            $nome = $data['nome'];
            $role = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'");
            $pg = gdrcd_query("UPDATE personaggio SET id_gilda = 0, id_ruolo_gilda = 0 WHERE nome = '$nome'");
            
            if ($pg && $role) echo json_encode(['success' => true, 'message' => 'Personaggio rimosso dalla gilda', 'query' => "DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'"]);
            else echo json_encode(['error' => false, 'message' => 'Impossibile rimuovere il personaggio']);
            
            break;
        case 'addGuildPg':  // Aggiungo il personaggio alla gilda, assegnandogli il ruolo di base
            $nome = $data['nome'];
            $id_gilda = $data['id_gilda'];
            $remRole = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$nome'");
            $role = gdrcd_query("SELECT * FROM ruolo WHERE gilda = $id_gilda AND livello = 1");
            $addRole = gdrcd_query("INSERT INTO clgpersonaggioruolo (personaggio, id_ruolo) VALUES ('$nome', ".$role['id_ruolo'].")");
            $pg = gdrcd_query("UPDATE personaggio SET id_gilda = $id_gilda, id_ruolo_gilda = ".$role['id_ruolo']." WHERE nome = '$nome'");
            
            if ($remRole && $addRole && $pg) echo json_encode(['success' => true, 'message' => 'Personaggio aggiunto alla gilda']);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata']);
            
            break;
        case 'getRole':  // Recupero i dati del ruolo
            $id = (int)$_GET['id'];
            $ruolo = gdrcd_query("SELECT * FROM ruolo WHERE id_ruolo = $id");
            
            if ($ruolo) echo json_encode($ruolo);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'deleteRole':  // Elimino il ruolo
            $id = (int)$_GET['id'];
            $ruolo = gdrcd_query("DELETE FROM ruolo WHERE id_ruolo = $id");
            $ruolo_pg = gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE id_ruolo = $id");
            
            if ($ruolo && $ruolo_pg) echo json_encode(['success' => true, 'message' => 'Ruolo eliminato con successo']);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'saveRole':    // Salvo il ruolo della gilda
            try {
                $id = isset($_POST['id_ruolo']) ? (int)$_POST['id_ruolo'] : 0;
                $isEdit = ($id > 0);
                $_POST['capo'] = isset($_POST['capo']) ? 1 : 0;
                
                // Verifica dati obbligatori
                if (empty($_POST['nome_ruolo']) || !is_numeric($_POST['gilda']) || !is_numeric($_POST['capo']) || !is_numeric($_POST['livello'])){
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                    exit;
                }

                // Verifica permessi per modifica
                if ($isEdit) {
                    $guildEsistente = gdrcd_query("SELECT * FROM ruolo WHERE id_ruolo = $id");

                    if (!$guildEsistente || !canEditOggetto($guildEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questo ruolo']);
                }

                // Gestione immagine
                $dati = saveImgGuild($_POST, $isEdit ? $guildEsistente['immagine'] : null, $_FILES);
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) $setParts[] = "`$campo` = '" . gdrcd_filter('in', $valore) . "'";
                    
                    $query = "UPDATE ruolo SET ".implode(', ', $setParts)." WHERE id_ruolo = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_ruolo') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".gdrcd_filter('in', $valore)."'";
                    }
                    
                    $query = "INSERT INTO ruolo (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }
                
                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Ruolo salvato con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio del ruolo']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'deleteSoglia':  // Elimino la soglia del livello gilda
            $id = (int)$_GET['id'];
            $soglia = gdrcd_query("DELETE FROM gilda_soglie WHERE id_soglia = $id");
            
            if ($soglia) echo json_encode(['success' => true, 'message' => 'Soglia eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Ruolo non trovato']);
            
            break;
        case 'saveSoglia':  // Salvo la soglia del livello gilda
            $id_soglia = isset($data['id_soglia']) && is_numeric($data['id_soglia']) ? (int)$data['id_soglia'] : 0;
            $livello_soglia = isset($data['livello_soglia']) && is_numeric($data['livello_soglia']) ? (int)$data['livello_soglia'] : 0;
            $soglia = isset($data['soglia']) && is_numeric($data['soglia']) ? (int)$data['soglia'] : 0;
            $danno = isset($data['danno']) && is_numeric($data['danno']) ? (float)$data['danno'] : 0;
            $integrita = isset($data['integrita']) && is_numeric($data['integrita']) ? (int)$data['integrita'] : 0;

            // Verifica dati obbligatori
            if (!is_numeric($id_soglia) || !is_numeric($livello_soglia) || !is_numeric($soglia) || !is_numeric($danno)) {
                echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $data]);
                return;
            }

            // Recupero tutte le soglie per controllare che quella che salvo sia giusta. Mi raccomando, mantenere l'ORDER BY nella query
            $soglie = gdrcd_query("SELECT * FROM gilda_soglie WHERE id_soglia NOT IN ($id_soglia) ORDER BY livello", 'result');
            
            foreach ($soglie as $s) {
                // La soglia che si vuole impostare per questo livello è maggiore rispetto a quella dei livelli più alti o minore rispetto a quella dei livelli più bassi
                if($id_soglia == $s['id_soglia']) $exists = true;

                // Esiste già una soglia impostata per questo livello. Modificare direttamente la soglia esistente.
                if($livello_soglia == $s['livello']) {
                    echo json_encode(['id_soglia' => $id_soglia, 'success' => false, 'message' => 'Esiste già una soglia impostata per questo livello. Modificare direttamente la soglia esistente.']);
                    return;
                }

                // La soglia che si vuole impostare per questo livello è maggiore rispetto a quella dei livelli più alti o minore rispetto a quella dei livelli più bassi
                if(($livello_soglia > $s['livello'] && $soglia <= $s['soglia']) || ($livello_soglia < $s['livello'] && $soglia > $s['soglia'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La soglia non può essere inferiore o uguale a quella impostata per un livello più basso o viceversa.',
                        'Soglie' => "$soglia - ".$s['soglia'],
                        'Livelli' => "$livello_soglia - ".$s['livello']
                    ]);
                    return;
                }
            }

            $query = $id_soglia > 0 ? "UPDATE gilda_soglie SET livello = $livello_soglia, soglia = $soglia, danno = $danno, integrita = $integrita WHERE id_soglia = $id_soglia" :
                                        "INSERT INTO gilda_soglie (livello, soglia, danno, integrità) VALUES ($livello_soglia, $soglia, $danno, $integrita)";

            if (gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Soglia impostata con successo per questo livello!']);
            else echo json_encode(['error' => false, 'message' => 'Errore nella query!']);

            break;
        case 'getVoceStatuto':  // Recupero i dati dello statuto della gilda
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("SELECT * FROM statuti_new WHERE articolo = $id");
            
            if ($voce_statuto) echo json_encode($voce_statuto);
            else echo json_encode(['error' => false, 'message' => 'Voce statuto non trovata']);
            
            break;
        case 'deleteVoceStatuto':  // Elimino lo statuto
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("DELETE FROM statuti_new WHERE articolo = $id");
            
            if ($voce_statuto) echo json_encode(['success' => true, 'message' => 'Voce eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Voce non trovata']);
            
            break;
        case 'saveVoceStatuto': // Salvo la voce dello statuto
            try {
                $dati = $_POST;
                $id = isset($dati['articolo']) ? (int)$dati['articolo'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($dati['titolo']) || empty($dati['testo'])) {
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $dati]);
                    return;
                }
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) {
                        if($campo == 'articolo') continue; // Salto l'id in fase di inserimento
                        
                        $setParts[] = "`$campo` = '".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "UPDATE statuti_new SET ".implode(', ', $setParts)." WHERE articolo = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'articolo') continue; // Salto l'id in fase di inserimento

                        $campi[] = "`$campo`";
                        $valori[] = "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "INSERT INTO statuti_new (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }

                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Voce salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della voce']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'getSkill':  // Recupero i dati della skill
            $id = (int)$_GET['id'];
            $skill = gdrcd_query("SELECT * FROM abilita WHERE id_abilita = $id");
            
            if ($skill) echo json_encode($skill);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata - '."SELECT * FROM abilita WHERE id_gilda = $id"]);
            
            break;
        case 'deleteSkill':  // Elimino la skill
            $id = (int)$_GET['id'];
            $voce_statuto = gdrcd_query("DELETE FROM abilita WHERE id_abilita = $id");
            
            if ($voce_statuto) echo json_encode(['success' => true, 'message' => 'Abilità eliminata con successo']);
            else echo json_encode(['error' => false, 'message' => 'Abilità non trovata']);
            
            break;
        case 'saveSkill':   // Salvo l'abilità
            try {
                $dati = $_POST;
                $id = isset($dati['id_abilita']) ? (int)$dati['id_abilita'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($dati['nome']) || empty($dati['descrizione'])) {
                    echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $dati]);
                    return;
                }
                
                // Modifica
                if ($isEdit) {
                    $setParts = [];

                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_abilita') continue; // Salto l'id in fase di inserimento
                        $val_sql = ($campo === 'sottotipo' && $valore === '') ? 'NULL' : "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                        $setParts[] = "`$campo` = $val_sql";
                    }
                    
                    $query = "UPDATE abilita SET ".implode(', ', $setParts)." WHERE id_abilita = $id";
                } else {
                // Creazione
                    $campi = [];
                    $valori = [];
                    
                    foreach ($dati as $campo => $valore) {
                        if($campo == 'id_abilita') continue; // Salto l'id in fase di inserimento
                        $campi[]  = "`$campo`";
                        $valori[] = ($campo === 'sottotipo' && $valore === '') ? 'NULL' : "'".mysqli_real_escape_string(gdrcd_connect(), $valore)."'";
                    }
                    
                    $query = "INSERT INTO abilita (".implode(', ', $campi).") VALUES (".implode(', ', $valori).")";
                }

                if(gdrcd_query($query)) echo json_encode(['success' => true, 'message' => 'Abilità salvata con successo!', 'query' => $query]);
                else echo json_encode(['success' => false, 'message' => 'Errore nel salvataggio della abilità']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        // ── Selezione razza (ScegliRazza SPA) ────────────────────────────────

        case 'getGuildState':  // Stato del PG + elenco gilde 1-7
            $login_f = gdrcd_filter('in', $_SESSION['login']);
            $pg = gdrcd_query("SELECT p.nome, p.id_gilda,
                                      g.nome AS gilda_nome, g.immagine AS gilda_immagine
                               FROM personaggio p
                               LEFT JOIN gilda g ON g.id_gilda = p.id_gilda
                               WHERE p.nome = '$login_f'");

            $res_gilde = gdrcd_query("SELECT g.id_gilda, g.nome, g.immagine,
                                             r.id_ruolo AS ruolo_id, r.nome_ruolo AS ruolo_nome
                                      FROM gilda g
                                      LEFT JOIN ruolo r ON r.gilda = g.id_gilda AND r.livello = 1
                                      WHERE g.id_gilda BETWEEN 1 AND 7
                                      ORDER BY g.nome ASC", 'result');
            $guilds = [];
            while ($r = gdrcd_query($res_gilde, 'fetch')) {
                $guilds[] = [
                    'id'         => (int)$r['id_gilda'],
                    'nome'       => $r['nome'],
                    'immagine'   => $r['immagine'],
                    'ruolo_id'   => (int)$r['ruolo_id'],
                    'ruolo_nome' => $r['ruolo_nome'],
                ];
            }

            echo json_encode([
                'success' => true,
                'pg'      => [
                    'nome'           => $pg['nome'],
                    'id_gilda'       => (int)($pg['id_gilda'] ?? 0),
                    'gilda_nome'     => $pg['gilda_nome']     ?? null,
                    'gilda_immagine' => $pg['gilda_immagine'] ?? null,
                ],
                'guilds' => $guilds,
            ]);
            break;

        case 'joinGuild':  // Il PG sceglie la sua razza
            $login_f  = gdrcd_filter('in', $_SESSION['login']);
            $id_gilda = isset($data['id_gilda']) ? (int)$data['id_gilda'] : 0;

            if ($id_gilda < 1 || $id_gilda > 7) {
                echo json_encode(['success' => false, 'message' => 'Razza non valida']);
                break;
            }

            $pg_now = gdrcd_query("SELECT id_gilda FROM personaggio WHERE nome = '$login_f'");
            if ((int)($pg_now['id_gilda'] ?? 0) !== 0) {
                echo json_encode(['success' => false, 'message' => 'Sei già affiliato ad una razza']);
                break;
            }

            $ruolo_base = gdrcd_query("SELECT id_ruolo FROM ruolo WHERE gilda = $id_gilda AND livello = 1 LIMIT 1");
            if (!$ruolo_base) {
                echo json_encode(['success' => false, 'message' => 'Ruolo base non trovato per questa razza']);
                break;
            }
            $id_ruolo = (int)$ruolo_base['id_ruolo'];

            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$login_f'");
            gdrcd_query("INSERT INTO clgpersonaggioruolo (personaggio, id_ruolo) VALUES ('$login_f', $id_ruolo)");
            $ok = gdrcd_query("UPDATE personaggio SET id_gilda = $id_gilda, id_ruolo_gilda = $id_ruolo WHERE nome = '$login_f'");

            if ($ok) echo json_encode(['success' => true, 'message' => 'Benvenuto nella tua nuova razza!']);
            else echo json_encode(['success' => false, 'message' => 'Errore durante l\'affiliazione']);
            break;

        case 'leaveGuild':  // Il PG abbandona la razza (reset completo)
            $login_f = gdrcd_filter('in', $_SESSION['login']);

            $pg_now = gdrcd_query("SELECT id_gilda FROM personaggio WHERE nome = '$login_f'");
            if ((int)($pg_now['id_gilda'] ?? 0) === 0) {
                echo json_encode(['success' => false, 'message' => 'Non sei affiliato ad alcuna razza']);
                break;
            }

            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$login_f'");
            gdrcd_query("UPDATE personaggio SET
                             id_gilda = 0, id_ruolo_gilda = 0, shin = 0,
                             car0 = car0 - car1, car1 = 0,
                             car2 = car2 - car3, car3 = 0,
                             car4 = car4 - car5, car5 = 0,
                             car6 = car6 - car7, car7 = 0,
                             car8 = car8 - car9, car9 = 0
                         WHERE nome = '$login_f'");
            gdrcd_query("DELETE clgpersonaggioabilita
                         FROM clgpersonaggioabilita
                         JOIN abilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita
                         WHERE clgpersonaggioabilita.nome = '$login_f'
                         AND abilita.tipo NOT IN ('Talento', 'Skill temporanea')");
            gdrcd_query("DELETE FROM log_spesa WHERE nome = '$login_f'");

            echo json_encode(['success' => true, 'message' => 'Hai abbandonato la tua razza. Tutti i bonus sono stati rimossi.']);
            break;

        default: echo json_encode(['error' => 'Operazione non valida']); break;
    }
    /*********************  FINE    Recupero i dati dell'utente che voglio modificare   */
} else {
    error_log("Parametri mancanti");
    echo json_encode(['error' => 'Parametri mancanti'], JSON_PRETTY_PRINT);
}

exit();
?>