<?php
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');

    add_script("/includes/role_session.js");
    add_script("/includes/chat.js");

    $login = $_SESSION['login'];
    $luogo = $_SESSION['luogo'];
    $info = gdrcd_query("SELECT nome, descrizione, stanza_apparente, invitati, privata, proprietario, scadenza FROM mappa WHERE id = $luogo LIMIT 1");
    $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login' LIMIT 1");
    $preferenze = json_decode($pg['preferenze_chat'], true);

    $chat_style = '';

    if (!empty($preferenze)) {
        $selettore = '.chat_row_P .chat_msg';
        
        $chat_style .= '<style>';
        if (!empty($preferenze['font'])) $chat_style .= $selettore . ' { font-family: ' . htmlspecialchars($preferenze['font']) . ' !important; }';
        if (!empty($preferenze['colore_testo'])) $chat_style .= $selettore . ' { color: ' . htmlspecialchars($preferenze['colore_testo']) . ' !important; }';
        if (!empty($preferenze['grandezza'])) $chat_style .= $selettore . ' { font-size: ' . htmlspecialchars($preferenze['grandezza']) . ' !important; }';
        if (!empty($preferenze['interlinea'])) $chat_style .= $selettore . ' { line-height: ' . htmlspecialchars($preferenze['interlinea']) . ' !important; }';
        if (!empty($preferenze['colore_dialogo'])) $chat_style .= '.chat_row_P font { color: ' . htmlspecialchars($preferenze['colore_dialogo']) . ' !important; }';
        $chat_style .= '</style>';
    }
?>

<head>
    <link rel="stylesheet" href="../themes/crystal/mestieri.css">
    <link rel="stylesheet" href="../themes/crystal/chat.css?<?=time()?>">
    <?=$chat_style?>
</head>

<div class="pagina_frame_chat">
    <div class="page_body">
        <?php
        // e' una stanza privata?
        if($info['privata'] == 1) {
            $allowance = false;

            if((($info['proprietario'] == gdrcd_capital_letter($login)) || (strpos($_SESSION['gilda'], $info['proprietario']) != false) || (strpos($info['invitati'], gdrcd_capital_letter($login)) != false) || (($PARAMETERS['mode']['spyprivaterooms'] == 'ON') && ($_SESSION['admin']==1 || $_SESSION['moderatore']==1))) && ($info['scadenza'] > strftime('%Y-%m-%d %H:%M:%S'))) {
                $allowance = true;
            }
        } else $allowance = true;
            
        // se e' privata e l'utente non ha titolo di leggerla
        if($allowance === false) echo '<div class="warning">'.$MESSAGE['chat']['whisper']['privat'].'</div>';
        else $_SESSION['last_message'] = 0;

        /****************** TEXTAREA - Imposto placeholder e lunghezza massima del testo ***********************/
        $maxlength_value = 2000; // Valore di default

        // Se non esiste un limite del master, verifica la tabella mappa
        $result_mappa = gdrcd_query("SELECT limite_lunghezza_massima FROM mappa WHERE id = '$luogo'", 'result');
        if ($result_mappa_row = gdrcd_query($result_mappa, 'fetch')) {
            $maxlength_value = $result_mappa_row['limite_lunghezza_massima'] !== null ? $result_mappa_row['limite_lunghezza_massima'] : $maxlength_value; // Imposta il valore da mappa
        }
        
        // Se sono master o admin, aggiungo la funzione js che rimuove il limite di caratteri quando trova "=" a inizio testo
        $masterMessageLength = $_SESSION['admin'] == 1 || $_SESSION['master'] == 1 ? 'masterMessageLength(this, '.$maxlength_value.');' : '';
        $textarea_field = '<textarea class="chat_textarea" name="message" id="message" placeholder="Scrivi la tua azione" maxlength="'.$maxlength_value.'" 
                            onKeyup="conta(this);'.$masterMessageLength.'"></textarea>';
        /****************** FINE    TEXTAREA    ***********************/

        /****************** PULSANTI - Controllo su visibilità pulsanti ***********************/
        // BACKCHAT
        // Se l'esperienza è maggiore di 19 mostro il pulsante
        if ($pg['esperienza'] > 19) {
            $back_chat_status = $pg['back_chat'] == 1 ? 'ON' : 'OFF';
            $title = $back_chat_status == 'ON' ? 'Disattiva Backchat' : 'Attiva Backchat';
            $pulsante_backchat = '<a href="#" onclick="toggleBackChat(this);" class="gdr-control-btn">
                    <img id="backChatToggle" title="'.$title.'" src="themes/crystal/imgs/chat/Backchat'.$back_chat_status.'.png">
                </a>';
        }
        
        // CURA PG
        $pulsante_cura = '';
        $salute = $pg['salute'];
        $num_azioni_cura = gdrcd_query("SELECT COUNT(*) AS num FROM chat WHERE stanza = $luogo AND mittente = '$login' AND tipo = 'P'")['num'];
        // Se sto giocando nella chat dell'ospedale, ho inviato almeno tre azioni e la salute è tra 1 e 49
        if ($luogo == '25' && $num_azioni_cura > 3 && $salute > 0 && $salute < 50) {
            $pulsante_cura = '<a href="#" onclick="curaPg();" class="gdr-control-btn"><img src="themes/crystal/imgs/chat/cura.png" alt="Cura pg"></a>';
        }
        // PULISCI CHAT
        $pulsante_pulisci_chat = '';
        // Se sono admin, master o moderatore mostro il pulsante
        if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['moderatore'] == 1) {
            $pulsante_pulisci_chat = '<a href="#" onclick="pulisciChat();" class="gdr-control-btn"><img src="themes/crystal/imgs/chat/pulisci_chat.png" alt="Pulisci chat"></a>';
        }
        // SCACCHIERA
        $pulsante_scacchiera = '';
        // Se sono admin o master e NON sto giocando nella chat dell'ospedale, mostro il pulsante
        if (($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) && $luogo != '25') {    
            $pulsante_scacchiera = '<a href="#" onClick="window.open(\'pages/chess.inc.php?id=' . $luogo . '\', \'Log\', \'fullscreen,toolbar=no\');" class="gdr-control-btn">
                <img src="themes/crystal/imgs/chess/scacchiera.png" alt="Icon 6">
            </a>';
        }
        /****************** FINE    PULSANTI    ***********************/
        ?>

        <!-- CHAT CONTENT -->
        <div id="pagina_chat" class="chat_box"></div>
        <script>
        document.addEventListener('ct:ready', function() {
            CT.mount('ChatViewer', 'pagina_chat', {});
        });
        </script>


        <!-- FORM DI INSERIMENTO AZIONE + PULSANTI -->
        <div class="panels_box">
            <div class="form_chat">
                <form method="post" target="chat_frame" id="chat_form_messages">
                    <div class="form_row">
                        <div class="casella_chat">
                            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                                <span class="gdr-char-counter"><span id="rimanenti">0</span> caratteri</span> <!-- CONTEGGIO CARATTERI -->
                                <a href="#"><img id="openPanelBtn" title="Help" src="themes/crystal/imgs/chat/chat_panel.png" class="chat_icon"></a> <!-- PANNELLO CHAT -->
                                <a href="#" onClick="window.open('chat_help.proc.php','Help','toolbar=no,width=500,height=500');" title="Info">
                                    <img src="themes/crystal/imgs/chat/help.png" alt="Info" class="chat_icon"> <!-- ISTRUZIONI CHAT -->
                                </a>
                                <input type="text" name="action_tag" class="action-tag" maxlength="30" placeholder="TAG max 30"> <!-- TAG AZIONE -->
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 10px;">
                                <!-- ROLES -->
                                <input type="hidden" id="id_role" value=""> <!-- IMPOSTO L'ID DELLA ROLE -->
                                <div class="gdr-session-status inactive" id="gdrSessionStatus">
                                    <div class="gdr-pulse-dot"></div>
                                    <span class="gdr-status-text"><span id="roleInProgress" style="display:none;">Role in Corso...</span></span> <!-- TESTO STATUS -->
                                    <div class="gdr-animated-border"></div> <!-- BORDO ANIMATO -->
                                    <i id="quitRole" onclick="quitRole('<?=$_SESSION['login']?>');" class="fa-solid fa-power-off" style="cursor:pointer;display:none;font-size:16px;"></i> <!-- PULSANTE QUIT ROLE -->
                                    <i id="pgRolePlaying" onclick="document.getElementById('pgRolePlayingPanel').style.display = 'block';" class="fa-solid fa-users" style="cursor:pointer;display:none;font-size:16px;"></i> <!-- PULSANTE VISUALIZZA UTENTI IN ROLE -->
                                    <i id="addPgToRoleBtn" onclick="addPgToRole();" class="fa-solid fa-play" style="cursor:pointer;color:green;"> Avvia!</i> <!-- PULSANTE AVVIO/JOIN ROLE -->
                                </div>
                                <!-- FINE ROLES -->
                                <input type="submit" value="<?=gdrcd_filter('out', $MESSAGE['interface']['forms']['submit'])?>"> <!-- PULSANTE INVIO AZIONE -->
                            </div>
                        </div>
                    </div>
                    <?=$textarea_field?> <!-- TEXTAREA inserimento azione -->
                </form>
            </div>
        </div>
        <!-- FINE   FORM DI INSERIMENTO AZIONE + PULSANTI -->
    </div>
</div>

<!-- MODALE AVVIO ROLE -->
<div id="userSearchPopup" class="user-search-popup__overlay">
    <div class="user-search-popup__container">
        <!-- Header -->
        <div class="user-search-popup__header">
            <h3 class="user-search-popup__title">Seleziona Utenti</h3>
            <button id="closePopup" class="user-search-popup__close-btn">&times;</button>
        </div>
        <!-- Campo Ricerca -->
        <div class="user-search-popup__search-section">
            <input type="text" id="userSearch" class="user-search-popup__search-input" placeholder="Cerca utenti..." autocomplete="off">
            <div class="user-search-popup__loading">Ricerca in corso</div>
            <div id="autocompleteResults" class="user-search-popup__autocomplete-list"></div>
        </div>
        <!-- Lista Utenti Selezionati -->
        <div class="user-search-popup__selected-section">
            <h4 class="user-search-popup__selected-title">Utenti Selezionati:</h4>
            <div id="selectedUsersList" class="user-search-popup__selected-list"><div class="user-search-popup__empty-message">Nessun utente selezionato</div></div>
        </div>
        <!-- Footer -->
        <div class="user-search-popup__footer">
            <button id="confirmSelection" class="user-search-popup__confirm-btn" disabled>Conferma</button>
            <button id="cancelSelection" class="user-search-popup__cancel-btn">Annulla</button>
        </div>
    </div>
</div>

<!-- MODALE VISUALIZZA UTENTI IN ROLE -->
<div id="pgRolePlayingPanel" class="user-search-popup__overlay">
    <div class="user-search-popup__container">
        <!-- Header -->
        <div class="user-search-popup__header">
            <h3 class="user-search-popup__title">Personaggi giocanti</h3>
            <button id="closePopupAdd" class="user-search-popup__close-btn">&times;</button>
        </div>
        <!-- Lista utenti -->
        <div style="padding: 12px;">
            <div id="simpleUsersTable">
                <!-- Intestazione -->
                <div style="display: grid; grid-template-columns: 1fr 60px 60px 60px 80px; gap: 8px; padding: 6px 8px; background-color: rgba(42, 63, 118, 0.3); border-radius: 6px; margin-bottom: 4px; font-size: 0.85rem;">
                    <div>Nome</div>
                    <div style="text-align: center;">Giocante</div>
                    <div style="text-align: center;">Turno inviato</div>
                    <div style="text-align: center;">Turno chiuso</div>
                    <div style="text-align: center;">Azione</div>
                </div>
                <div id="pgRolePlayingList" style="max-height: 220px; overflow-y: auto;"><!-- Contenuto dinamico --></div>
            </div>
        </div>
    </div>
</div>

<!-- Modale del pannello chat -->
<div class="gdr-modal-overlay" id="chatPanel" style="display:none;">
    <div class="gdr-panel-container">
        <!-- Header -->
        <div class="gdr-header">
            <div class="gdr-logo">Pannello Gestione GDR</div>
            <div class="gdr-main-controls">
                <a href="chat_save.proc.php" target="_blank" class="gdr-control-btn" title="Salva giocata"><img src="themes/crystal/imgs/chat/blocca_salva.png" alt="Salva"></a>
                <?=$pulsante_backchat . $pulsante_cura . $pulsante_pulisci_chat . $pulsante_scacchiera?>
            </div>
            <button class="gdr-close-btn" id="gdrCloseBtn">&times;</button>
        </div>
        <!-- Tabs -->
        <div class="gdr-tabs">
            <div class="gdr-tab active" data-tab="dice">Dadi e Tiri</div>
            <div class="gdr-tab" data-tab="skills">Abilità e Armi</div>
            <div class="gdr-tab" data-tab="master" onclick="getPngRolePlaying();">Gestione Master</div>
            <div class="gdr-tab" data-tab="writing">Scrittura</div>
        </div>
        <!-- DADI E TIRI -->
        <div class="gdr-tab-content active" id="dice-tab">
            <div class="gdr-grid">
                <!-- Dado Generico -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Dado Generico</div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="dado">Tipo di Dado</label>
                        <select class="gdr-select" name="dado" id="dado">
                            <option value="0">Dado da</option>
                            <?php for ($i = 1; $i <= 100; $i++) echo "<option value=\"$i\">$i</option>"; ?>
                        </select>
                    </div>
                    <button class="gdr-button gdr-btn-success" onclick="tiraDadoGenericoChat();">Tira Dado Generico</button>
                </div>
                <!-- Oggetti -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Oggetti</div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="objChat">Oggetto</label>
                        <select class="gdr-select" id="objChat" name="id_item">
                            <option value="0">Usa oggetto</option>

                            <?php
                            function stampa_oggetti($categoria, $label, $condizioni_extra = '') {
                                $login = $_SESSION['login'];

                                $query = "SELECT c.id_oggetto, o.nome, c.cariche
                                    FROM clgpersonaggiooggetto c
                                    JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                                    WHERE c.nome = '".$login."'
                                    AND o.categoria = '".$categoria."'
                                    AND c.posizione > 0
                                    AND c.cariche > 0
                                    ".$condizioni_extra."
                                    ORDER BY o.nome
                                ";

                                $items = gdrcd_query($query, 'result');

                                if (gdrcd_query($items, 'num_rows') > 0) {
                                    echo '<optgroup label="'.$label.'">';
                                    
                                    while ($item = gdrcd_query($items, 'fetch')) {
                                        $cariche = '';
                                        if ($item['cariche'] == -1) $cariche = " (∞)";
                                        elseif ($item['cariche'] > 1) $cariche = " (x ".$item['cariche'].")";

                                        echo "<option value='".$item['id_oggetto']."'>".gdrcd_filter('out', $item['nome']).$cariche."</option>";
                                    }
                                    echo '</optgroup>';
                                }
                                gdrcd_query($items, 'free');
                            }

                            // Recupera la salute del personaggio
                            $salute = $pg['salute'];
                            $integrita = $pg['integrita'];
                            $login = $_SESSION['login'];

                            // OGGETTI CURATIVI - solo se la salute è inferiore al 100 e superiore al 49, oppure l'integrità non è full
                            if (($salute < 100 && $salute > 49) || $integrita < 10) stampa_oggetti('curativo', 'Medicine');

                            // POTENZIAMENTI
                            $potenziamento_attivo = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE nome = '$login' AND used = 1 AND isTemp = 1", 'result');

                            if (gdrcd_query($potenziamento_attivo, 'num_rows') == 0) stampa_oggetti('statistica', 'Potenziamenti');

                            gdrcd_query($potenziamento_attivo, 'free');

                            // OGGETTI MAGICI
                            stampa_oggetti('magico', 'Oggetti Magici');

                            // OGGETTI GENERICI - con condizione diversa per le cariche
                            $query_standard = "SELECT c.id_oggetto, o.nome, c.cariche
                                FROM clgpersonaggiooggetto c
                                JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                                WHERE c.nome = '$login'
                                AND o.categoria = 'standard'
                                AND c.posizione > 0
                                AND (c.cariche > 0 OR c.cariche = -1)
                                ORDER BY o.nome
                            ";

                            $items_standard = gdrcd_query($query_standard, 'result');

                            if (gdrcd_query($items_standard, 'num_rows') > 0) {
                                echo '<optgroup label="Oggetti Generici">';
                                while ($item = gdrcd_query($items_standard, 'fetch')) {
                                    echo "<option value='".$item['id_oggetto']."'>";
                                    echo gdrcd_filter('out', $item['nome']);
                                    if ($item['cariche'] == -1) echo " (∞)";
                                    elseif ($item['cariche'] > 1) echo " (x ".$item['cariche'].")";
                                    echo "</option>";
                                }
                                echo '</optgroup>';
                            }
                            gdrcd_query($items_standard, 'free');
                            ?>
                        </select>
                    </div>
                    <button class="gdr-button gdr-btn-success" onclick="usaOggettoChat();">Usa Oggetto</button>
                </div>
            </div>
        </div>
        <!-- ABILITA' E ARMI -->
        <div class="gdr-tab-content" id="skills-tab">
            <div class="gdr-master-panel">
                <div class="gdr-master-panel-title">Attenzione!</div>
                <p>E' possibile selezionare una quantità di bersagli pari al livello selezionato dell'abilità.</p>
            </div>
            <div class="gdr-grid">
                <!-- Selezione Bersagli -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Bersaglio (*)</div>
                    <div id="user-selection-box"></div> <!-- BERSAGLIO -->
                </div>
                <!-- Abilità -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Abilità</div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="chat_skill">Abilità</label>
                        <select class="gdr-select" id="chat_skill" name="magie">
                            <option value="0">Usa skill</option>
                            <?php
                            // Query unica per tutte le abilità
                            $abilita = gdrcd_query("SELECT * FROM clgpersonaggioabilita 
                                                    LEFT JOIN abilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita 
                                                    WHERE clgpersonaggioabilita.nome = '$login' 
                                                    ORDER BY abilita.tipo DESC, abilita.id_abilita DESC", 'result');

                            // Inizializza un array per memorizzare le opzioni delle abilità raggruppate per tipo
                            $gruppi_abilita = [
                                'Default e Difensiva' => [],
                                'Generiche' => [],
                                'Attacchi' => [],
                                'Mentali' => [],
                                'Poteri speciali' => [],
                                'Skill Temporanee' => [],
                                'Talenti' => []
                            ];

                            // Popola l'array con le abilità, raggruppate per tipo
                            while($row = gdrcd_query($abilita, 'fetch')) {
                                if ($row['tipo'] == 'Default' || $row['tipo'] == 'Difensiva') $gruppi_abilita['Default e Difensiva'][] = $row;
                                elseif (in_array($row['tipo'], ['Generica base', 'Generica avanzata'])) $gruppi_abilita['Generiche'][] = $row;
                                elseif (in_array($row['tipo'], ['Attacco base', 'Attacco medio', 'Attacco avanzato'])) $gruppi_abilita['Attacchi'][] = $row;
                                elseif (in_array($row['tipo'], ['Mentale base', 'Mentale media', 'Mentale avanzata', 'Mentale di attacco'])) $gruppi_abilita['Mentali'][] = $row;
                                elseif ($row['tipo'] == 'Potere speciale') $gruppi_abilita['Poteri speciali'][] = $row;
                                elseif ($row['tipo'] == 'Skill Temporanea') $gruppi_abilita['Skill Temporanee'][] = $row;
                                elseif ($row['tipo'] == 'Talento') $gruppi_abilita['Talenti'][] = $row;
                            }
                            gdrcd_query($abilita, 'free'); // Libera la risorsa

                            // Visualizza le abilità per gruppo
                            foreach($gruppi_abilita as $categoria => $abilita) {
                                if (!empty($abilita)) {
                                    echo "<optgroup label='".gdrcd_filter('out', $categoria)."'>";
                                    
                                    foreach($abilita as $abilita_item) {
                                        echo "<option value='".$abilita_item['id_abilita']."' data-max-level='".$abilita_item['grado']."'>";
                                        echo gdrcd_filter('out', $abilita_item['nome']);
                                        // Aggiungi il numero di usi se la skill è temporanea
                                        if ($categoria == 'Skill Temporanee') echo " (<i>x". gdrcd_filter('out', $abilita_item['usi']) ." usi</i>)";
                                        echo "</option>";
                                    }
                                    echo "</optgroup>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <!-- Livello skill -->
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="livello_skill">Livello Abilità</label>
                        <select class="gdr-select" id="livello_skill" name="livello"><option value="0">0</option></select>
                    </div>
                    <button class="gdr-button gdr-btn-success" onclick="tiraSkillChat();">Usa Abilità</button>
                </div>
                <!-- Armi -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Attacchi</div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="tipo_attacco">Tipo di attacco</label>
                        <select class="gdr-select" id="tipo_attacco" name="arma">
                            <option value="0">Attacca</option>
                            <optgroup label="Attacco fisico"><option value="attacco_fisico">Attacco fisico</option></optgroup>
                            <optgroup label="Armi">
                                <?php // Armi
                                $result = gdrcd_query("SELECT oggetto.id_oggetto, oggetto.nome, clgpersonaggiooggetto.cariche 
                                    FROM clgpersonaggiooggetto 
                                    LEFT JOIN oggetto ON oggetto.id_oggetto = clgpersonaggiooggetto.id_oggetto 
                                    WHERE clgpersonaggiooggetto.nome = '$login' 
                                    AND oggetto.categoria = 'arma'
                                    AND (clgpersonaggiooggetto.cariche > 0 OR clgpersonaggiooggetto.cariche = -1)
                                    ORDER BY oggetto.nome", 'result');

                                while($row = gdrcd_query($result, 'fetch')) echo "<option value='".$row['id_oggetto']."'>".gdrcd_filter('out', $row['nome'])." (".$row['cariche'].")</option>";

                                gdrcd_query($result, 'free');
                                ?>
                            </optgroup>
                            <?php // Creatura
                            $result_livello = gdrcd_query("SELECT clgpersonaggioruolo.personaggio, ruolo.livello
                                                            FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
                                                            JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio
                                                            WHERE clgpersonaggioruolo.personaggio = '$login'");
                            if ($result_livello['livello'] > 0) echo '<option value="creatura">Attacca con creatura</option>';
                            ?>
                        </select>
                    </div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="arma_body">Parte del Corpo</label>
                        <select class="gdr-select" id="arma_body" name="target_area">
                            <option value="volto">Volto</option>
                            <option value="pancia">Pancia</option>
                            <option value="gamba">Gamba</option>
                            <option value="punto_vitale">Punto vitale</option>
                        </select>
                    </div>
                    <button class="gdr-button gdr-btn-success" onclick="usaAttaccoChat();">Usa Arma</button>
                </div>
            </div>
        </div>
        <!-- GESTIONE MASTER -->
        <div class="gdr-tab-content" id="master-tab">
            <div class="gdr-master-panel">
                <div class="gdr-master-panel-title">Area Master</div>
                <p>Questa area è riservata ai Master per la gestione dei personaggi e PNG.</p>
            </div>
            <?php if(isAdminMasterMod($_SESSION)) : ?>
                <button class="btn" id="endTurn" onclick="closeTurn();">Chiudi turno</button> <!-- PULSANTE GLOBAL END TURN -->
                <br>
                <br>
                <div class="gdr-grid">
                    <!-- Modifica Personaggio -->
                    <div class="gdr-card">
                        <div class="gdr-card-title">Modifica Personaggio</div>
                        <div class="gdr-form-group">
                            <label class="gdr-label" for="nome_personaggio">Personaggio</label>
                            <select class="gdr-select" name="nome_personaggio" id="nome_personaggio">
                                <option value="pg1">Personaggio 1</option>
                                <option value="pg2">Personaggio 2</option>
                                <option value="pg3">Personaggio 3</option>
                            </select>
                        </div>
                        
                        <div id="modificaParametri">
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="note_fato">Note del Fato</label>
                                <textarea class="gdr-textarea" name="note_fato" id="note_fato" rows="3"></textarea>
                            </div>
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="particolari">Particolari</label>
                                <textarea class="gdr-textarea" name="particolari" id="particolari" rows="3"></textarea>
                            </div>
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="salute">Salute</label>
                                <input class="gdr-input" type="number" name="salute" id="salute">
                            </div>
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="integrita">Integrità (0-10)</label>
                                <input class="gdr-input" type="number" name="integrita" min="0" max="10" id="integrita">
                            </div>
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="notorieta">Notorietà (0-100)</label>
                                <input class="gdr-input" type="number" name="notorieta" min="0" max="100" id="notorieta">
                            </div>
                            <div class="gdr-form-group">
                                <label class="gdr-label" for="soldi">Soldi</label>
                                <input class="gdr-input" type="number" name="soldi" min="0" id="soldi">
                            </div>
                            <button class="gdr-button gdr-btn-success" onclick="gdrEditMasterPgChat();">Modifica Personaggio</button>
                        </div>
                    </div>

                    <!-- Gestione PNG -->
                    <div class="gdr-card">
                        <div class="gdr-card-title">Gestione PNG</div>
                        
                        <div class="gdr-form-group" style="flex:1;">
                            <label class="gdr-label" for="pngNew" style="flex:1;">Nome PNG</label>
                            <input class="gdr-input" id="pngNew" placeholder="Nome PNG" style="flex:2;">
                        </div>
                        <div class="gdr-form-group" style="flex:1;">
                            <label class="gdr-label" for="pngNew">Destrezza PNG</label>
                            <input class="gdr-input" id="pngNew" placeholder="Nome PNG">
                        </div>
                        <button class="gdr-button gdr-btn-success" onclick="newMasterPng();">Aggiungi</button>

                        <div class="gdr-card-title"></div>
                        <div class="gdr-form-group">
                            <label class="gdr-label" for="pngName">PNG attivi</label>
                            <select class="gdr-select" id="pngName"></select>
                        </div>
                        <div class="gdr-form-group">
                            <label class="gdr-label" for="pngMessage">Azione PNG</label>
                            <textarea class="gdr-textarea" id="pngMessage" placeholder="Azione PNG" rows="3"></textarea>
                        </div>
                        <div class="gdr-form-group">
                            <label class="gdr-label" for="pngCar">Caratteristica PNG</label>
                            <select class="gdr-select" id="pngCar">
                                <option value="destrezza">Usa destrezza</option>
                                <option value="potere">Usa potere</option>
                                <option value="mente">Usa mente</option>
                                <option value="tempra">Usa tempra</option>
                            </select>
                        </div>
                        <div class="gdr-form-group">
                            <label class="gdr-label" for="pngBonus">Bonus caratteristica PNG</label>
                            <input class="gdr-input" id="pngBonus" placeholder="Bonus sul dado">
                        </div>
                        <button class="gdr-button gdr-btn-success" onclick="newMasterPngAction();">Invia</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- SCRITTURA -->
        <div class="gdr-tab-content" id="writing-tab">
            <div class="gdr-grid">
                <!-- Limite caratteri -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Imposta caratteri</div>
                    <div class="gdr-form-group">
                        <label class="gdr-label" for="caratteri">Limite Caratteri Azione</label>
                        <input class="gdr-input" name="caratteri" id="caratteri" placeholder="Limite caratteri">
                    </div>
                    <button class="gdr-button gdr-btn-success" onclick="setCharLimit();">Imposta Limite</button>
                </div>
                <!-- Scrittura Libera -->
                <div class="gdr-card">
                    <div class="gdr-card-title">Scrittura Libera</div>
                    <button class="gdr-button gdr-btn-warning" id="gdrOpenTextareaButton">Apri Scrittura Libera in Popup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALE MODIFICA AZIONE -->
<div class="pg-edit-container" id="editAction-modal" role="dialog" aria-modal="true">
    <div class="modal-content">
        <span class="close" id="closePgModal">&times;</span>
        <h2>Modifica Azione</h2>
        <textarea id="edit_action_textarea" rows="10"></textarea>
    </div>
    <input type="hidden" id="edit_action_id" value="">
    <div class="actions"><button onclick="saveEditAction();">Salva modifiche</button></div>
</div>
<!-- FINE modale col form di modifica -->