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
        case 'getObj':  // Recupero i dati dell'oggetto
            $id = (int)$_GET['id'];
            $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id");
            
            if ($oggetto) echo json_encode(['success' => true, 'oggetto' => $oggetto]);
            else echo json_encode(['error' => false, 'message' => 'Oggetto non trovato']);
            
            break;
        case 'getTipiObj':  // Recupero i tipi di oggetto
            $categoria = gdrcd_filter('in', $_GET['categoria']);
    
            // Applica filtri in base alla categoria (stessa logica del codice originale)
            $categorie_escluse = [];
            $categorie_permesse = [];
            
            switch ($categoria) {
                case 'standard': $categorie_escluse = ['Arma di Gilda', 'Armi', 'Droga', 'Magic Shop', 'Medicine', 'Mods', 'STRIKE', 'Secret Pandora']; break;
                case 'arma': $categorie_permesse = ['Arma di Gilda', 'Armi', 'STRIKE', 'Secret Pandora']; break;
                case 'curativo': $categorie_permesse = ['Magic Shop', 'Medicine', 'STRIKE', 'Secret Pandora']; break;
                case 'statistica': $categorie_permesse = ['Droga', 'Magic Shop', 'Mods', 'STRIKE', 'Secret Pandora']; break;
                case 'magico': $categorie_permesse = ['Magic Shop', 'Secret Pandora', 'Generico', 'Gioielli']; break;
            }
            
            $tipi = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
            $tipi_filtrati = [];
            
            while($t = gdrcd_query($tipi, 'fetch')) {
                $descrizione = gdrcd_filter('out', $t['descrizione']);
                $mostra = true;
                
                if (!empty($categorie_escluse) && in_array($descrizione, $categorie_escluse)) $mostra = false;
                if (!empty($categorie_permesse) && !in_array($descrizione, $categorie_permesse)) $mostra = false;
                if ($mostra) $tipi_filtrati[] = $t;
            }
            
            echo json_encode(['success' => true, 'tipi' => $tipi_filtrati]);
            
            break;
        case 'saveObj':  // Salvo i dati dell'oggetto
            try {
                $id = isset($_POST['id_oggetto']) ? (int)$_POST['id_oggetto'] : 0;
                $isEdit = ($id > 0);
                
                // Verifica dati obbligatori
                if (empty($_POST['nome']) || empty($_POST['descrizione']) || empty($_POST['categoria']) || empty($_POST['tipo'])) echo json_encode(['success' => false, 'message' => 'Campi obbligatori non ricevuti', 'dati' => $_POST]);
                
                // Verifica permessi per modifica
                if ($isEdit) {
                    $oggettoEsistente = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id");

                    if (!$oggettoEsistente || !canEditOggetto($oggettoEsistente)) echo json_encode(['success' => false, 'message' => 'Non hai i permessi per modificare questo oggetto', 'dati' => canEditOggetto($oggettoEsistente)]);
                }
                
                // Prepara dati base
                $dati = prepareDatiBase($_POST);
                $categoria = gdrcd_filter('in', $_POST['categoria']);
                
                // Aggiungi campi specifici per categoria
                $dati = array_merge($dati, getCampiSpecificiCategoria($categoria, $_POST));
                
                // Gestione immagine
                $dati = saveImgObj($dati, $isEdit ? $oggettoEsistente['urlimg'] : null, $_FILES);
                
                // Esegui query
                if ($isEdit) updateOggetto($id, $dati);
                else insertOggetto($dati);
                
                echo json_encode(['success' => true, 'message' => 'Oggetto salvato con successo!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
        case 'deleteObj':  // Elimino un oggetto
            $id_oggetto = (int)$data['id_oggetto'];
            $obj = gdrcd_query("SELECT clgpersonaggiooggetto.nome, clgpersonaggiooggetto.numero, oggetto.costo, oggetto.urlimg
                                FROM clgpersonaggiooggetto
                                LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto
                                WHERE clgpersonaggiooggetto.id_oggetto = $id_oggetto");
            
            if ($obj['numero']) {
                // Risarcisco gli eventuali possessori
                while($refound = gdrcd_query($obj, 'fetch')) {
                    gdrcd_query("UPDATE personaggio SET soldi = (soldi + (".gdrcd_filter('num', $obj['costo'])." * ".gdrcd_filter('num', $obj['numero']).")) WHERE nome = '".gdrcd_filter_in($obj['nome'])."'");
                }
                // Elimino gli oggetti assegnati ai pg dal database
                $queryDelete = gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto");
            }
            // Elimino l'immagine associata all'oggetto se esiste
            if (!empty($obj['urlimg']) && file_exists(__DIR__ . '/../uploads/oggetti/' . $obj['urlimg'])) {
                unlink(__DIR__ . '/../uploads/oggetti/' . $obj['urlimg']);
            }
            // Elimino l'oggetto dal database
            $queryDelete = gdrcd_query("DELETE FROM oggetto WHERE id_oggetto = $id_oggetto");
            
            if ($queryDelete) echo json_encode(['success' => true, 'message' => 'Oggetto eliminato con successo']);
            else echo json_encode(['error' => false, 'message' => 'Errore nella cancellazione']);

            break;
        case 'assegnaObj':  // Assegna un oggetto a uno o più personaggi (ex oggetto_assegna.inc.php,
            // ora richiamato in modale dalla riga dell'oggetto invece che da una pagina a sé)
            $id_oggetto = (int)($_POST['id_oggetto'] ?? 0);
            $personaggi = $_POST['personaggi'] ?? [];
            $num_oggetti = (int)($_POST['num_oggetti'] ?? 1);

            if ($id_oggetto <= 0 || empty($personaggi) || $num_oggetti < 1) {
                echo json_encode(['success' => false, 'message' => 'Dati mancanti o non validi']);
                break;
            }

            $oggetto = gdrcd_query("SELECT tipo, categoria, cariche, isTemp, temp_giorni, ricarica_massima, creatore FROM oggetto WHERE id_oggetto = $id_oggetto");
            if (!$oggetto) {
                echo json_encode(['success' => false, 'message' => 'Oggetto non trovato']);
                break;
            }

            // Stessa regola di canEditOggetto() (custom_functions.inc.php): chi può
            // modificare un oggetto può anche assegnarlo. Riusata qui invece di
            // ripetere la logica ad-hoc che c'era in oggetto_assegna.inc.php (solo
            // mestiere Magic Shop hardcoded, non generalizzata su ogni mestiere con
            // un "negozio" tramite getTipoOggettoMestiere()).
            if (!canEditOggetto($oggetto)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Permessi insufficienti per assegnare questo oggetto']);
                break;
            }

            // Stessa logica cariche di oggetto_assegna.inc.php, per categoria
            switch ($oggetto['categoria']) {
                case 'arma':
                    $cariche = ((int)$oggetto['tipo'] === 15) ? -1 : (int)$oggetto['ricarica_massima']; // 15 = Arma di Gilda, cariche infinite
                    break;
                case 'statistica': $cariche = (int)$oggetto['ricarica_massima']; break;
                case 'curativo':
                case 'magico':     $cariche = (int)$oggetto['cariche']; break;
                case 'standard':   $cariche = ($oggetto['cariche'] == 'illimitato') ? -1 : (int)$oggetto['cariche']; break;
                default:           $cariche = 0;
            }

            $isTemp = $oggetto['isTemp'];
            $temp_giorni = $oggetto['temp_giorni'];
            $assegnati = 0;

            foreach ($personaggi as $pg) {
                $pg_f = gdrcd_filter('in', trim($pg));
                if ($pg_f === '') continue;

                $check = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto AND nome = '$pg_f'", 'result');

                if (gdrcd_query($check, 'num_rows') > 0) {
                    // Già posseduto: ricarica solo se scarico, non tocca il numero
                    $dati_pg_oggetto = gdrcd_query("SELECT cariche FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto AND nome = '$pg_f'");
                    if ($dati_pg_oggetto['cariche'] == 0) {
                        gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = $cariche WHERE id_oggetto = $id_oggetto AND nome = '$pg_f'");
                    }
                } else {
                    gdrcd_query("INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero, isTemp, temp_giorni)
                                VALUES ('$pg_f', $id_oggetto, $cariche, $num_oggetti, $isTemp, $temp_giorni)");
                }
                gdrcd_query($check, 'free');
                $assegnati++;
            }

            echo json_encode(['success' => true, 'message' => "Oggetto assegnato a $assegnati personaggi con successo"]);
            break;
        case 'buyObj':  // Acquisto oggetti dal mercato
            $id_oggetto = $data['id_oggetto'];
            $user = $data['user'];
            $costo = ($data['costo'] * $data['qty']);
            $qty = $data['qty'];
            $obj = gdrcd_query("SELECT oggetto.*, mercato.numero as numero_mercato, GROUP_CONCAT(clgpersonaggiooggetto.nome SEPARATOR ', ') AS nomi
								FROM oggetto
								LEFT JOIN clgpersonaggiooggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto
                                LEFT JOIN mercato ON oggetto.id_oggetto = mercato.id_oggetto
								WHERE oggetto.id_oggetto = $id_oggetto
                                GROUP BY oggetto.id_oggetto");
                                
            // Se acquisto un oggetto del pandora, specifico che non è utilizzabile finché non viene giocato in ON
            $comment = $obj['tipo'] == 9 ? gdrcd_filter('in', "Oggetto comprato nel mercato, ma non ancora comprato in ON, per questo è inutilizzabile") : '';
            // Normalizzo cariche: se illimitato, lo metto a -1, altrimenti lo lascio com'è
            $cariche = $obj['cariche'] === 'illimitato' ? -1 : (int)$obj['cariche'];
            
            // Se già possiedo l'oggetto, ne aumento il numero nel mio equipaggiamento
            $nomi = array_map('trim', explode(',', $obj['nomi'])); // Se ci sono utenti che hanno già acquistato questo oggetto, prendo i loro nomi
            if(in_array($user, $nomi)) $query_clgPgObj = "UPDATE clgpersonaggiooggetto SET numero = (numero + $qty) WHERE id_oggetto = $id_oggetto AND nome = '$user'";
            else $query_clgPgObj = "INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, commento_shop, cariche, numero, posizione, isTemp, temp_giorni) VALUES ('$user', $id_oggetto, '$comment', ".$cariche.", $qty, 0, ".gdrcd_filter('num', $obj['isTemp']).", ".gdrcd_filter('num', $obj['temp_giorni']).")";
            // Scalo i soldi del pg
            $query_pg = "UPDATE personaggio SET soldi = (soldi - $costo) WHERE nome = '$user' LIMIT 1";
            // Scalo la quantità di pezzi per l'oggetto presente nel mercato
            $query_mercato = $obj['numero_mercato'] > 1 ? "UPDATE mercato SET numero = (numero - $qty) WHERE id_oggetto = $id_oggetto LIMIT 1" : "DELETE FROM mercato WHERE id_oggetto = $id_oggetto LIMIT 1";

            if (gdrcd_query($query_clgPgObj) && gdrcd_query($query_pg) && gdrcd_query($query_mercato) ) echo json_encode(['success' => true, 'message' => 'Oggetto acquistato con successo!']);
            else echo json_encode(['error' => false, 'message' => 'Errore nelle query di acquisto']);
            
            break;
        case 'sellObj':  // Vendita oggetti del mercato
            $id_oggetto = $data['id_oggetto'];
            $user = $data['user'];
            $qty = $data['qty'];

            // Controllo che il PG abbia l'oggetto in inventario
            $query = "SELECT clgpersonaggiooggetto.numero, oggetto.costo FROM clgpersonaggiooggetto LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.id_oggetto = $id_oggetto AND clgpersonaggiooggetto.nome = '$user'";
            $result = gdrcd_query($query, 'result');

            if(gdrcd_query($result, 'num_rows') > 0) {
                $row = gdrcd_query($result, 'fetch');
                gdrcd_query($result, 'free');

                // Calcolo il costo di vendita in base alla percentuale di rivendita
                $costo = floor(($row['costo'] / 100) * (100 - $PARAMETERS['settings']['resell_price']));

                // Se vendo tutti gli oggetti in mio possesso li elimino dall'inventario, altrimenti tolgo la quantità venduta, altrimenti significa che non ho abbastanza oggetti da vendere
                if($row['numero'] == $qty) $query_clgPgObj = "DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = $id_oggetto AND nome = '$user' LIMIT 1";
                elseif($row['numero'] > $qty) $query_clgPgObj = "UPDATE clgpersonaggiooggetto SET numero = (numero - $qty) WHERE id_oggetto = $id_oggetto AND nome = '$user' LIMIT 1";
                else {
                    echo json_encode(['error' => false, 'message' => 'Non hai abbastanza oggetti da vendere']);
                    break;
                }

                // Aggiungo i soldi della vendita al pg
                $query_pg = "UPDATE personaggio SET soldi = (soldi + ".gdrcd_filter('num', $costo).") WHERE nome = '$user' LIMIT 1";
                // Rimetto i pezzi disponibili nel mercato
                $query_mercato = "UPDATE mercato SET numero = (numero + $qty) WHERE id_oggetto = $id_oggetto LIMIT 1";

                if (gdrcd_query($query_clgPgObj) && gdrcd_query($query_pg) && gdrcd_query($query_mercato)) echo json_encode(['success' => true, 'message' => 'Oggetto venduto con successo!']);
                else echo json_encode(['error' => false, 'message' => 'Errore nelle query di vendita']);
            } else echo json_encode(['error' => false, 'message' => 'Non hai questo oggetto da vendere']);
            
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