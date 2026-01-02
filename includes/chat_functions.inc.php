<?php
/************* CHAT di gioco ******************************/
/************* Inserimento azione ******************************/
/** Configura il sistema esperienza  */
function setupExperienceSystem($session) {
    $result_exp = gdrcd_query("SELECT count_exp, last_date_exp, last_date_mestiere FROM personaggio WHERE nome = '" . $session['login'] . "'");
    
    return array(
        'count_exp' => $result_exp['count_exp'],
        'last_date_exp' => $result_exp['last_date_exp'],
        'last_date_mestiere' => $result_exp['last_date_mestiere'],
        'exp_bonus' => 1
    );
}

/** * Gestisce i messaggi normali della chat */
function handleNormalMessage(&$chat_message, $action_tag, &$sender, $m_type, $first_char, $second_char, $actual_healt, &$session, $parameters, $id_role, $pgIsInRole) {
    if (empty($chat_message)) return;
    
    $imgs = $session['sesso'] . ";" . $session['img_razza'];
    
    // Inserisci il messaggio
    chatInsertMessage($session['luogo'], $sender, null, $chat_message, $m_type, null, $action_tag, $imgs);
    
    // Gestione esperienza se c'è una role attiva
    if ($parameters['mode']['exp_by_chat'] == 'ON' && $id_role != false) handleExperienceRewards($id_role, $session);
    
    // Se è l'azione di un pg, controllo il turno
    if($m_type === 'P') checkTurn($session['luogo'], $session['login'], $id_role);
}

/**  * Determina il tipo di messaggio  */
function determineMessageType($type, $first_char, $second_char, &$chat_message, &$tag_n_beyond, $actual_healt, &$session) {
    global $MESSAGE;
    
    // Sussurro normale
    if (($type == "4") || ($first_char == "@")) return handleWhisperMessage($type, $chat_message, $tag_n_beyond, 'S', $session);
    // Sussurro sistema
    if (($type == "11") || ($second_char == "@|")) return handleWhisperMessage($type, $chat_message, $tag_n_beyond, 'Q', $session);
    // Azione
    if (($type == "1") || ($first_char == "+")) return handleActionMessage($chat_message, $tag_n_beyond, $actual_healt, $session);
    // Azione OFF
    if (($type == "10") || in_array($first_char, array("/", "^", "|"))) return handleOffActionMessage($chat_message, $tag_n_beyond, $session);
    // Messaggi Master/Admin
    if ((($type == "2") || in_array($first_char, array("=", "-|", "*"))) && ($session['master'] == 1 || $session['admin'] == 1)) return handleMasterMessage($chat_message, $first_char);
    // PNG (Master/Admin only)
    if (($type == "3") && ($session['master'] == 1 || $session['admin'] == 1)) {
        $session['tag'] = $tag_n_beyond;
        return 'N';
    }
    
    // Globale (Master/Admin only)
    if ((($type == "9") || $first_char == "$") && ($session['master'] == 1 || $session['admin'] == 1)) {
        if ($first_char == "$") $chat_message = substr($chat_message, 1);
        $session['tag'] = $tag_n_beyond;
        return 'G';
    }
    
    // Moderatore (Moderatore/Admin only)
    if ((($type == "8") || $first_char == "%") && ($session['moderatore'] == 1 || $session['admin'] == 1)) {
        if ($first_char == "%") $chat_message = substr($chat_message, 1);
        $session['tag'] = $tag_n_beyond;
        return 'X';
    }
    
    // Parlato normale (default)
    return handleNormalChatMessage($chat_message, $tag_n_beyond, $session);
}

/**  * Gestisce i messaggi sussurro  */
function handleWhisperMessage($type, &$chat_message, &$tag_n_beyond, $whisper_type, &$session) {
    global $MESSAGE;
    
    $m_type = $whisper_type;
    
    // Estrai destinatario dal messaggio se necessario
    if ($type != $whisper_type) {
        $dest_end = strpos(substr($chat_message, 1), ($whisper_type == 'S') ? "@" : "|@");
        
        if ($dest_end === FALSE) {
            $m_type = 'P';
        } else {
            $tag_n_beyond = gdrcd_capital_letter(substr($chat_message, 1, $dest_end));
            $chat_message = substr($chat_message, $dest_end + 2);
        }
    }
    
    // Verifica destinatario
    if ($m_type == $whisper_type) {
        $r_check_dest = gdrcd_query("SELECT nome FROM personaggio 
                                   WHERE DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW() 
                                   AND ultimo_luogo = " . $session['luogo'] . " 
                                   AND nome = '" . $tag_n_beyond . "' 
                                   LIMIT 1", 'result');
        
        if (gdrcd_query($r_check_dest, 'num_rows') < 1) {
            $chat_message = $tag_n_beyond . ' ' . gdrcd_filter('in', $MESSAGE['chat']['whisper']['no']);
            $tag_n_beyond = $session['login'];
        }
    } else $tag_n_beyond = $session['tag'];
    
    return $m_type;
}

/**  * Gestisce i messaggi azione  */
function handleActionMessage(&$chat_message, $tag_n_beyond, $actual_healt, &$session) {
    global $MESSAGE;
    
    if ($actual_healt['salute'] > 0) {
        if (substr($chat_message, 0, 1) == "+") $chat_message = substr($chat_message, 1);
        
        $session['tag'] = $tag_n_beyond;
        return 'A';
    } else {
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['exausted']);
        return 'S';
    }
}

/**  * Gestisce le azioni OFF  */
function handleOffActionMessage(&$chat_message, $tag_n_beyond, &$session) {
    global $MESSAGE;
    
    // Controllo messaggi OFF
    $counts = getOffActionCounts($session);
    
    if ($counts['off'] == 0) {
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['nooff']);
        return 'S';
    }
    
    if ($counts['stopoff'] > 4) {
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['stopoff']);
        return 'S';
    }
    
    if (in_array(substr($chat_message, 0, 1), array("/", "^", "|"))) $chat_message = substr($chat_message, 1);
    
    $session['tag'] = $tag_n_beyond;
    return 'Z';
}

/**  * Conta le azioni OFF  */
function getOffActionCounts($session) {
    $query = "SELECT tipo, COUNT(*) as count FROM chat 
              WHERE stanza = " . $session['luogo'] . " 
              AND mittente = '" . $session['login'] . "'
              AND DATE_ADD(ora, INTERVAL 7 HOUR) >= NOW()
              AND tipo IN ('P', 'A', 'Z')
              GROUP BY tipo";
    
    $result = gdrcd_query($query, 'result');
    $count_off = 0;
    $count_stopoff = 0;
    
    while ($row = gdrcd_query($result, 'fetch')) {
        if (in_array($row['tipo'], array('P', 'A'))) $count_off = $row['count'];
        elseif ($row['tipo'] == 'Z') $count_stopoff = $row['count'];
    }
    
    return array('off' => $count_off, 'stopoff' => $count_stopoff);
}

/**  * Gestisce i messaggi master  */
function handleMasterMessage(&$chat_message, $first_char) {
    $m_type = 'M';
    
    if (in_array($first_char, array("=", "-|", "|", "*"))) {
        $chat_message = substr($chat_message, 1);
    }
    
    if ($first_char == "|") $m_type = 'I';
    if ($first_char == "*") $m_type = 'Y';
    
    return $m_type;
}

/**  * Gestisce la chat normale  */
function handleNormalChatMessage(&$chat_message, &$tag_n_beyond, &$session) {
    $session['tag'] = $tag_n_beyond;
        
    return 'P';
}

/**  * Gestisce le ricompense esperienza  */
function handleExperienceRewards($id_role, $session) {
    // Recupera se la location è pubblica
    $info_privacy = gdrcd_query("SELECT privata, nome FROM mappa WHERE id = " . $session['luogo'] . "");
    
    if ($info_privacy['privata'] == 0) handlePublicLocationExperience($id_role, $session);
}

/**  * Gestisce l'esperienza per location pubbliche  */
function handlePublicLocationExperience($id_role, $session) {
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome = '" . $session['login'] . "'");
    
    // Exp automatica
    $check_actions = getPgTotalRoleActions($session['luogo'], $session['login'], $id_role);

    if (($check_actions >= 4)) awardExperience($session);
    
    // Exp mestiere
    if (shouldAwardCraftExperience($check_backing, $check_actions, $session)) awardCraftExperience($session);
}

// Recupera la quantità totale delle azioni inviate dal pg nella role specifica
function getPgTotalRoleActions($luogo, $login, $id_role) {
    $query = "SELECT COUNT(*) AS total_actions FROM chat 
              WHERE stanza = '$luogo' 
              AND mittente = '$login' 
              AND id_role = $id_role 
              AND (tipo = 'P' OR tipo = 'M')";
    
    $result = gdrcd_query($query);
    return $result['total_actions'];
}

/**  * Assegna esperienza normale  */
function awardExperience($session) {
    $nome_luogo = gdrcd_query("SELECT nome FROM mappa WHERE id=" . $session['luogo'] . "");
    $resoconto = "Giocata libera - " . $nome_luogo['nome'] . "";
    
    gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 1, esperienza_r = esperienza_r + 1, last_date_exp = NOW() WHERE nome = '" . $session['login'] . "' LIMIT 1");
    gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, commento) VALUES ('" . $session['login'] . "', '1', NOW(), '" . gdrcd_filter('in', $resoconto) . "')");
    
    chatInsertMessage($session['luogo'], 'System', $session['login'], 'Punto esperienza assegnato', 'S');
}

/**  * Verifica se assegnare esperienza mestiere  */
function shouldAwardCraftExperience($check_backing, $check_actions, $session) {
    if ($check_backing['esperienza_mestiere'] >= 55) return false;
    if ($check_actions != 4) return false;
    
    $craft_locations = array(
        1 => 20,   // Mestiere 1 -> Location 20
        2 => 30,   // Mestiere 2 -> Location 30  
        3 => 24,   // Mestiere 3 -> Location 24
        10 => 25,  // Mestiere 10 -> Location 25
        4 => 14    // Mestiere 4 -> Location 14
    );
    
    return isset($craft_locations[$check_backing['id_mestiere']]) && $session['luogo'] == $craft_locations[$check_backing['id_mestiere']];
}

/**  * Assegna esperienza mestiere  */
function awardCraftExperience($session) {
    $nome_luogo_mest = gdrcd_query("SELECT nome FROM mappa WHERE id=" . $session['luogo'] . "");
    $resoconto_mest = "Giocata di mestiere - " . $nome_luogo_mest['nome'] . "";
    
    gdrcd_query("UPDATE personaggio SET esperienza_mestiere = esperienza_mestiere + 1, last_date_mestiere = NOW() WHERE nome = '" . $session['login'] . "' LIMIT 1");
    gdrcd_query("INSERT INTO PuntiMestiere (nome, mestiere, data_evento, commento) VALUES ('" . $session['login'] . "', '1', NOW(), '" . gdrcd_filter('in', $resoconto_mest) . "')");
    
    chatInsertMessage($session['luogo'], 'System', $session['login'], 'Punto mestiere assegnato', 'S');
}

/**  * Gestisce i comandi della stanza privata  */
function handleRoomCommand($type, $tag_n_beyond, $session, $postData) {
    global $MESSAGE;
    
    $info = gdrcd_query("SELECT invitati, nome, proprietario FROM mappa WHERE id=" . $session['luogo'] . "");
    $ok_command = hasRoomCommandPermission($info, $session);
    
    if (!$ok_command) return;
    
    switch($type) {
        case "5": // invita
            handleInviteCommand($info, $tag_n_beyond, $session, $postData);
            break;
        case "6": // caccia
            handleKickCommand($info, $tag_n_beyond, $session);
            break;
        default: // elenco
            handleListCommand($info, $session);
            break;
    }
}

/**  * Verifica i permessi per i comandi stanza  */
function hasRoomCommandPermission($info, $session) {
    if ($info['proprietario'] == $session['login']) return true;
    if (strpos($session['gilda'], $info['proprietario']) !== false) return true;
    return false;
}

/**  * Gestisce il comando invita  */
function handleInviteCommand($info, $tag_n_beyond, $session, $postData) {
    global $MESSAGE;
    
    gdrcd_query("UPDATE mappa SET invitati = '" . $info['invitati'] . ',' . gdrcd_capital_letter(strtolower(gdrcd_filter('in', $tag_n_beyond))) . "' WHERE id=" . $session['luogo'] . " LIMIT 1");
    
    // Messaggio in chat
    $message_text = gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)) . ' ' . $MESSAGE['chat']['warning']['invited'];
    insertSystemMessage($session, $message_text);
    
    // Messaggio privato
    if (!empty($postData['tag'])) {
        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, letto, testo) 
                    VALUES ('System message', '" . gdrcd_capital_letter(gdrcd_filter('in', $postData['tag'])) . "', 
                    NOW(), 0, '" . $session['login'] . ' ' . $MESSAGE['chat']['warning']['invited_message'] . ' ' . $info['nome'] . "')");
    }
}

/**  * Gestisce il comando caccia  */
function handleKickCommand($info, $tag_n_beyond, $session) {
    global $MESSAGE;
    
    $scaccia = str_replace(',' . gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)), '', $info['invitati']);
    gdrcd_query("UPDATE mappa SET invitati = '" . $scaccia . "' WHERE id=" . $session['luogo'] . " LIMIT 1");
    
    $message_text = gdrcd_capital_letter(gdrcd_filter('in', $tag_n_beyond)) . ' ' . $MESSAGE['chat']['warning']['expelled'];
    insertSystemMessage($session, $message_text);
}

/**  * Gestisce il comando elenco  */
function handleListCommand($info, $session) {
    global $MESSAGE;
    
    $ospiti = str_replace(',', '', $info['invitati']);
    $message_text = $MESSAGE['chat']['warning']['list'] . ': ' . $ospiti;
    insertSystemMessage($session, $message_text);
}

/**  * Inserisce un messaggio di sistema  */
function insertSystemMessage($session, $message_text) {
    chatInsertMessage($session['luogo'], 'System', $session['login'], $message_text, 'S');

    gdrcd_query("INSERT INTO bak_chat (stanza, mittente, destinatario, ora, tipo, testo) 
                VALUES (" . $session['luogo'] . ", 'System message', '" . $session['login'] . "', NOW(), 'S', '" . $message_text . "')");
}
/************* Fine inserimento azione ******************************/

/************* Lancio STATS della chat di gioco ******************************/
function lanciaStat($user, $luogo, $azione, $bonus, $malus) {
    $pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$user'");
    $salute = $pg["salute"];
    $caratteristica = '';
    $nome_tiro = '';
    $usa_bonus = true;

    switch ($azione) {
        case 'Usa destrezza':
            $caratteristica = $pg["car2"];
            $nome_tiro = 'destrezza';
            break;
        case 'Usa potere':
            $caratteristica = $pg["car8"];
            $nome_tiro = 'potere magico';
            break;
        case 'Usa forza':
            $caratteristica = $pg["car0"];
            $nome_tiro = 'forza';
            break;
        case 'Usa mente':
            $caratteristica = $pg["car4"];
            $nome_tiro = 'mente';
            break;
        case 'Usa tempra':
            $caratteristica = $pg["car6"];
            $nome_tiro = 'tempra';
            break;
        case 'Usa dadi20':
            $caratteristica = 0;
            $nome_tiro = "d20";
            break;
        case 'Usa dado master':
            $caratteristica = 0;
            $nome_tiro = "Master esegue un tiro totale di";
            $usa_bonus = false;
            break;
        case 'AttCreatura':
            $caratteristica = 0;
            $nome_tiro = "La creatura di $user attacca";
            $usa_bonus = false;
            break;
        case 'DifCreatura':
            $caratteristica = 0;
            $nome_tiro = "La creatura di $user si difende";
            $usa_bonus = false;
            break;
    }

    if ($caratteristica !== '') {
        $maxnum = 20;  // Usa un dado da 20 per il tiro
        $d20 = mt_rand(1, $maxnum);
        $numtot_finale = $d20;
        $malus_salute = 0;
        $sussurro = null;

        if ($usa_bonus) {
            if ($azione !== 'Usa dadi20') {
                $bonus_caratteristica = (($caratteristica / 10) - 1);
                $numtot_finale += $bonus_caratteristica;
            }

            // Aggiungi il malus per la salute se necessario
            if ($salute <= 50) {
                if ($salute > 40) $malus_salute = 1;
                elseif ($salute > 30) $malus_salute = 3;
                elseif ($salute > 20) $malus_salute = 5;
                elseif ($salute > 0) $malus_salute = 10;

                $numtot_finale -= $malus_salute;
            }

            // Applicazione del bonus e del malus selezionato dall'utente
            $numtot_finale += $bonus;
            $numtot_finale -= $malus;

            // Costruzione del messaggio2 se applicabile
            if ($azione !== 'Usa dado master' && $azione !== 'AttCreatura' && $azione !== 'DifCreatura') {
                $sussurro = "$d20/20";

                if ($bonus_caratteristica > 0) $sussurro .= " + $bonus_caratteristica";
                if ($bonus > 0) $sussurro .= " + $bonus di bonus";
                if ($malus > 0) $sussurro .= " - $malus di malus";
                if ($malus_salute > 0) $sussurro .= " - $malus_salute di malus per la salute";
                
                $sussurro .= " = $numtot_finale";
            }
        }

        if ($azione == 'AttCreatura' || $azione == 'DifCreatura') $messaggio = "$nome_tiro con un tiro totale di $numtot_finale/20";
        elseif ($azione == 'Usa dado master') $messaggio = "$nome_tiro $numtot_finale/20";
        else $messaggio = "$user ha lanciato $nome_tiro totalizzando $numtot_finale";

        // Aggiunta descrizione bonus/malus selezionato, solo se presente
        $note_bonus_malus = [];

        if ($bonus > 0) $note_bonus_malus[] = "$bonus di bonus";
        if ($malus > 0) $note_bonus_malus[] = "$malus di malus";
        if (!empty($note_bonus_malus)) $messaggio .= " (comprensivo di " . implode(" e ", $note_bonus_malus) . ")";

        chatInsertMessage($luogo, $user, null, $messaggio, 'C', $sussurro);
    }
}
/************* FINE Lancio STATS della chat di gioco ******************************/

/************* Lancio SKILL della chat di gioco ******************************/
function creaMessaggioSkill($tipo, $nome, $liv, $login) {
    switch ($tipo) {
        case 'Attacco base':
        case 'Attacco medio':
        case 'Attacco avanzato': return "$login usa la skill di attacco $nome di livello $liv";
        case 'Mentale base':
        case 'Mentale media':
        case 'Mentale avanzata':
        case 'Mentale di attacco': return "$login usa la skill mentale $nome di livello $liv";
        case 'Generica base':
        case 'Generica avanzata': return "$login usa la skill generica $nome di livello $liv";
        case 'Skill Temporanea': return "$login usa la skill $nome di livello $liv";
        case 'Difensiva': return "$login usa la skill difensiva $nome di livello $liv";
        case 'Default': return "$login usa la skill di default $nome di livello $liv";
        case 'Potere speciale': return "$login usa la skill potere speciale $nome di livello $liv";
        case 'Talento': return "$login usa $nome";
        default: return "$login usa una skill sconosciuta $nome di livello $liv";
    }
}

function calcolaLivelloTalento($tempra) {
    $num = mt_rand(1, 20);
    $bonus_tempra = (($tempra / 10) - 1);
    $numtot = $num + $bonus_tempra;

    if ($numtot < 17) $livello = "di livello 1";
    elseif ($numtot >= 26) $livello = "di livello 3";
    else $livello = "di livello 2";

    return [
        'livello' => $livello,
        'testo' => "tot. tempra per la valutazione del livello: $num/20 + $bonus_tempra = $numtot"
    ];
}

function gestisciProntoSoccorso($livello, $login, $avversario, $luogo, $salute_login) {
    $incremento_salute = 0;
    $soglia_salute = 0;

    switch ($livello) {
        case "di livello 1":
            $incremento_salute = 10;
            $soglia_salute = 45;
            break;
        case "di livello 2":
            $incremento_salute = 15;
            $soglia_salute = 40;
            break;
        case "di livello 3":
            $incremento_salute = 20;
            $soglia_salute = 35;
            break;
    }

    // Verifica se è già stato usato su se stesso o sull'avversario
    $check_usage = gdrcd_query("SELECT COUNT(*) AS cnt FROM chat WHERE stanza = '$luogo' AND mittente = '$login' AND testo LIKE '%Pronto soccorso%'");
    $check_avversario_usage = ($avversario != "---") ? gdrcd_query("SELECT COUNT(*) AS cnt FROM chat WHERE stanza = '$luogo' AND destinatario = '$avversario' AND testo LIKE '%Pronto soccorso%'") : ['cnt' => 0];

    if ($check_usage['cnt'] > 0 || $check_avversario_usage['cnt'] > 0) return "System: Non puoi riusare questo talento.";

    // Gestione dell'uso su "Tutti"
    if ($avversario == "tutti") return "System: Non puoi curare tutti.";

    // Determina se la cura è possibile
    if ($salute_login > $soglia_salute) {
        if ($avversario == "---" || $avversario == $login) {
            $nuova_salute = min(100, $salute_login + $incremento_salute);
            gdrcd_query("UPDATE personaggio SET salute = $nuova_salute WHERE nome = '$login'");
            return "$login ha usato Pronto Soccorso di $livello e ha incrementato la propria salute di $incremento_salute";
        } else {
            $avversario_param = gdrcd_query("SELECT salute FROM personaggio WHERE nome = '$avversario'");
            $nuova_salute = min(100, $avversario_param['salute'] + $incremento_salute);
            gdrcd_query("UPDATE personaggio SET salute = $nuova_salute WHERE nome = '$avversario'");
            return "$login ha usato Pronto Soccorso di $livello su $avversario e ha incrementato la sua salute di $incremento_salute";
        }
    } else return "$login ha tentato di usare Pronto Soccorso di $livello, ma la ferita è troppo grave per essere curata.";
}

/***************    Inserimento messaggio in chat *********************/
function chatInsertMessage($place, $sender, $target = null, $msg, $m_type, $sussurro = null, $action_tag = '', $imgs = null) {
    $activeRole = locationActiveRole($place);
    $id_role = !$activeRole ? 0 : $activeRole; // Forzo il id_role a zero se non c'è una role attiva ma un admin/master/mod decide di scrivere in chat
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$sender'");
    $back_chat = ($check_backing['back_chat'] == 1) ? 1 : 0;

    // Aggiungo il tag se presente
    if($action_tag != '')  $msg = "[$action_tag] $msg";

    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo, backing, id_role) VALUES ('$place', '$sender', '$target', NOW(), '$m_type', '".gdrcd_filter('in', $msg)."', $back_chat, $id_role)");
    if ($sussurro !== null) gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo, id_role) VALUES ('$place', 'System', '$sender', NOW(), 'Q', '".gdrcd_filter('in', $sussurro)."', $id_role)");

    return true;
}
/***************    FINE    Inserimento messaggio in chat *********************/

function gestisciSkillTemporanea($magia, $login) {
    $ris_usi = gdrcd_query("SELECT usi FROM clgpersonaggioabilita WHERE id_abilita = '$magia' AND nome = '$login'");
    $usi = $ris_usi['usi'];

    // Verifica se la skill è temporanea
    if ($skill_info['tipo'] == 'Skill temporanea') {
        $ris_usi = gdrcd_query("SELECT usi FROM clgpersonaggioabilita WHERE id_abilita = '$magia' AND nome = '$login'");
        $usi = $ris_usi['usi'];

        if ($usi > 1) gdrcd_query("UPDATE clgpersonaggioabilita SET usi = usi-1 WHERE id_abilita = '$magia' AND nome = '$login'");
        else gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = '$magia' AND nome = '$login'");
    }
}

function assegnaPuntoShin($luogo, $login) {
    $result_exp = gdrcd_query("SELECT count_exp, last_date_shin FROM personaggio WHERE nome = '$login'");
    $count_exp = $result_exp['count_exp'];
    $last_date_exp = new DateTime($result_exp['last_date_shin']);

    $current = new DateTime('now');
    $new_day = new DateTime('today 7:00');

    if ($current < $new_day) $new_day->modify('-1 day');

    $check_actions = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND mittente = '$login' AND tipo = 'C' 
        AND (testo LIKE '%usa la skill generica%' OR 
             testo LIKE '%usa la skill di attacco%' OR
             testo LIKE '%usa la skill mentale%' OR
             testo LIKE '%usa la skill difensiva%' OR
             testo LIKE '%usa la skill potere speciale%' OR
             testo LIKE '%usa la skill di default%')
        AND DATE_ADD(ora, INTERVAL 12 HOUR) >= NOW()", 'result');

    // Do il punto shin se il pg ha lanciato più di due skill
    if ((gdrcd_query($check_actions, 'num_rows') > 2) && ($last_date_exp < $new_day)) {
        $nome_luogo = gdrcd_query("SELECT nome FROM mappa WHERE id = '$luogo'");
        chatInsertMessage($luogo, 'System', $login, 'Punto shin assegnato', 'S');
        gdrcd_query("UPDATE personaggio SET shin = shin + 1, last_date_shin = NOW() WHERE nome = '$login' LIMIT 1");
    }
}

function gestionePoliziaAutomatica($luogo) {
    $zona = gdrcd_query("SELECT * FROM mappa WHERE id = '$luogo'");
    $num_police = mt_rand(1, 20);
    $turni = calcolaTurniPolizia($zona['id_mappa'], $num_police);
    $check_skill_attacco = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND (testo LIKE '%usa la skill di attacco%' || testo LIKE '%attacca con%' || testo LIKE '%attacca fisicamente%') AND DATE_ADD(ora, INTERVAL 4 HOUR) >= NOW() AND tipo = 'C'", 'result');
    $check_master_nope = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND DATE_ADD(ora, INTERVAL 4 HOUR) >= NOW() AND tipo = 'M'", 'result');

    if (verificaArrivoPolizia($zona['id_mappa'], gdrcd_query($check_skill_attacco, 'num_rows'), gdrcd_query($check_master_nope, 'num_rows'))) {
        $responso = 'A seguito di ripetuti attacchi, alcuni cittadini hanno avvisato la polizia. <b>Tempo di arrivo della pattuglia:</b> ' . $turni . '.';
        chatInsertMessage($luogo, 'Sistema automatico', null, $responso, 'M', null);
        notificaMasterEStaff();
    }
}

function calcolaTurniPolizia($id_mappa, $num_police) {
    if (in_array($id_mappa, [3, 8, 10])) {
        if ($num_police <= 6) return 'non arriva';
        elseif ($num_police <= 13) return '3 turni';
        else return '2 turni';
    } elseif (in_array($id_mappa, [7, 9, 11])) {
        if ($num_police <= 6) return '4 turni';
        elseif ($num_police <= 13) return '3 turni';
        else return '2 turni';
    } elseif (in_array($id_mappa, [4, 5, 6])) {
        if ($num_police <= 6) return '3 turni';
        elseif ($num_police <= 13) return '2 turni';
        else return 'al prossimo turno';
    }
}

function verificaArrivoPolizia($id_mappa, $num_skill_attacco, $num_master_nope) {
    $condizione_verde = in_array($id_mappa, [4, 5, 6]) && $num_skill_attacco == 2 && $num_master_nope == 0;
    $condizione_gialla = in_array($id_mappa, [7, 9, 11]) && $num_skill_attacco == 3 && $num_master_nope == 0;
    $condizione_rossa = in_array($id_mappa, [3, 8, 10]) && $num_skill_attacco == 4 && $num_master_nope == 0;

    return $condizione_verde || $condizione_gialla || $condizione_rossa;
}

function notificaMasterEStaff() {
    // Recupera il nome del luogo
    $zona_result = gdrcd_query("SELECT nome FROM mappa WHERE id = '".$_SESSION['luogo']."'");
    
    if ($zona_result) $zona = $zona_result['nome'];
    else $zona = "sconosciuto"; // In caso di errore o se non viene trovato il luogo
    
    // Prepara i testi per i diversi scenari
    $tipo_evento = determinaTipoEvento(); // Funzione che determina il tipo di evento (armi, attacco fisico, ibrido, skill)
    
    // Prepara un testo generico per la notizia
    $titolo = "Intervento della polizia - $zona";
    
    // Varianti per solo armi
    $varianti_armi = [
        "Un violento scontro a fuoco è avvenuto in $zona. La polizia è stata allertata per mettere fine alla situazione.",
        "Nella zona di $zona si sono udite diverse detonazioni. Gli agenti sono stati chiamati per investigare.",
        "Diversi testimoni hanno segnalato un conflitto armato in $zona. Le forze dell'ordine sono state dispiegate per riportare la calma.",
        "In $zona è stato segnalato un uso massiccio di armi, e la polizia è intervenuta immediatamente.",
        "Diverse segnalazioni di spari in $zona hanno richiesto l'intervento delle forze dell'ordine per sedare la situazione."
    ];

    // Varianti per attacco fisico
    $varianti_collutazione = [
        "Una collutazione violenta è scoppiata in $zona, costringendo i residenti a chiamare la polizia per fermare la rissa.",
        "In $zona è scoppiata una rissa tra più individui. Le forze dell'ordine sono intervenute per sedare il caos.",
        "Numerose segnalazioni di una collutazione in $zona hanno spinto la polizia a intervenire rapidamente.",
        "In $zona si è verificata una rissa, costringendo la polizia a intervenire per fermare gli scontri fisici.",
        "Una rissa violenta è esplosa in $zona, richiedendo l'intervento delle forze dell'ordine per placare la situazione."
    ];

    // Varianti per uso ibrido (armi e attacco fisico)
    $varianti_ibrido_armi_fisico = [
        "Un violento scontro a $zona ha coinvolto sia armi che attacchi fisici. La polizia è intervenuta per sedare gli scontri.",
        "In $zona si è verificato un misto di scontri armati e colluttazioni, costringendo la polizia a intervenire.",
        "Un'escalation di violenza a $zona ha visto l'uso di armi e attacchi fisici. Le forze dell'ordine sono intervenute.",
        "Gli scontri a $zona hanno coinvolto sia l'uso di armi che scontri fisici. La polizia è arrivata per sedare il caos.",
        "A $zona si è verificato un violento scontro che ha coinvolto armi e attacchi fisici, portando all'intervento delle forze dell'ordine."
    ];
    
    // Varianti del testo
    $testi_varianti = [
        "In seguito a recenti disordini, la polizia è stata chiamata per riportare l'ordine a $zona. Alcuni cittadini hanno segnalato l'accaduto, e la situazione è ora in fase di verifica.",
        "Numerose chiamate nella zona in prossimità di $zona hanno richiesto l'intervento della polizia a causa di disordini. Gli agenti sono intervenuti per sedare le tensioni.",
        "Dopo ripetuti attacchi in $zona, la polizia è intervenuta su segnalazione di alcuni cittadini. La situazione è stata messa sotto controllo.",
        "In $zona si sono verificati disordini, e la polizia è stata chiamata per intervenire prontamente e riportare l'ordine.",
        "Gli abitanti di $zona hanno segnalato disordini alle forze dell'ordine. La polizia è intervenuta per ristabilire la calma.",
        "Disordini in $zona hanno richiesto l'intervento delle forze dell'ordine, che sono arrivate sul posto per sedare la situazione.",
        "Un'escalation di violenza a $zona ha portato all'intervento immediato della polizia, dopo segnalazioni da parte dei residenti.",
        "Segnalazioni di disordini in $zona hanno fatto scattare l'intervento della polizia per mettere fine agli scontri.",
        "La polizia è intervenuta in $zona per sedare i disordini segnalati dai residenti locali, ripristinando l'ordine.",
        "Gli agenti sono arrivati in $zona dopo che diversi cittadini hanno segnalato violenti disordini nella zona."
    ];
    
    // Scelta del testo in base al tipo di evento
    switch ($tipo_evento) {
        case 'solo_armi': $contenuto = $varianti_armi[array_rand($varianti_armi)]; break;
        case 'solo_attacco_fisico': $contenuto = $varianti_collutazione[array_rand($varianti_collutazione)]; break;
        case 'ibrido_armi_fisico': $contenuto = $varianti_ibrido_armi_fisico[array_rand($varianti_ibrido_armi_fisico)]; break;
        case 'solo_skill':
        case 'ibrido_skill_armi_fisico': $contenuto = $testi_varianti[array_rand($testi_varianti)]; break;
        default: $contenuto = "Disordini sono stati segnalati a $zona. La polizia è intervenuta.";
    }
    
    // Scegli un testo casuale dalla lista
    $contenuto = $testi_varianti[array_rand($testi_varianti)];
    
    // Inserisci la notizia nella tabella ctnews
    $query_insert_news = "INSERT INTO ctnews (titolo, contenuto, data, autore, tipologia, zona) 
                          VALUES ('".gdrcd_filter('in', $titolo)."', 
                                  '".gdrcd_filter('in', $contenuto)."', 
                                  NOW(), 
                                  'System', 
                                  'segnalazioni', 
                                  '".gdrcd_filter('in', $zona)."')";
    
    // Esegui l'inserimento
    gdrcd_query($query_insert_news);
}

function determinaTipoEvento() {
    // Recupera le ultime azioni in chat relative alla stanza (luogo)
    $luogo = $_SESSION['luogo'];
    $check_skill_attacco = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND (testo LIKE '%usa la skill di attacco%' OR testo LIKE '%attacca con%' OR testo LIKE '%attacca fisicamente%') AND DATE_ADD(ora, INTERVAL 4 HOUR) >= NOW() AND tipo = 'C'", 'result');

    // Analizza il tipo di azioni avvenute per determinare il tipo di evento
    $count_armi = 0;
    $count_fisico = 0;
    $count_skill = 0;

    while ($row = gdrcd_query($check_skill_attacco, 'fetch')) {
        if (strpos($row['testo'], 'attacca con') !== false) $count_armi++;
        elseif (strpos($row['testo'], 'attacca fisicamente') !== false) $count_fisico++;
        elseif (strpos($row['testo'], 'usa la skill di attacco') !== false) $count_skill++;
    }

    // Determina il tipo di evento
    if ($count_armi > 0 && $count_fisico > 0) return 'ibrido_armi_fisico';
    elseif ($count_armi > 0) return 'solo_armi';
    elseif ($count_fisico > 0) return 'solo_attacco_fisico';
    elseif ($count_skill > 0) return 'solo_skill';
    else return 'ibrido_skill_armi_fisico';
}
/************* FINE Lancio SKILL della chat di gioco ******************************/

// Funzione per gestire il limite di caratteri impostato dall'utente
function imposta_limite_caratteri_utente($login, $luogo, $caratteri) {
    $result = gdrcd_query("SELECT * FROM scelte_utenti WHERE nome = '$login' AND id_luogo = '$luogo'", 'result');

    if (gdrcd_query($result, 'num_rows') > 0) $query = "UPDATE scelte_utenti SET lunghezza_massima = '$caratteri', timestamp_modifica = NOW() WHERE nome = '$login' AND id_luogo = '$luogo'";
    else $query = "INSERT INTO scelte_utenti (nome, id_luogo, lunghezza_massima, timestamp_modifica) VALUES ('$login', '$luogo', '$caratteri', NOW())";

    if(gdrcd_query($query)) return true;

    return false;
}

// Funzione per aggiornare il limite globale nella mappa
function aggiorna_limite_globale($luogo, $caratteri) {
    // Recupero il vecchio limite prima di sovrascriverlo
    $vecchio_limite = gdrcd_query("SELECT limite_lunghezza_massima FROM mappa WHERE id = $luogo")["limite_lunghezza_massima"];
    // Sofrascrivo il vecchio limite con il nuovo
    gdrcd_query("UPDATE mappa SET limite_lunghezza_massima = $caratteri, timestamp_modifica_limite = NOW() WHERE id = $luogo");

    $personaggio = gdrcd_query($result, 'fetch')['nome'];
    $testo = "Il personaggio ". $_SESSION['login'] ." ha scelto di settare per tutti una lunghezza massima di $caratteri";
    
    // Se il nuovo limite è maggiore del vecchio, aggiungo il pulsante di revoca
    $testo .= $caratteri > $vecchio_limite ? " <button class='btn-revoca' onclick='revocaLimiteCaratteri($caratteri, $vecchio_limite, \"$luogo\", \"$personaggio\");'>Revoca</button>" : '';
    $testo = gdrcd_filter('in', $testo);
    $timestamp_minuto_successivo = date('Y-m-d H:i:s', strtotime('+1 minute'));
    
    // Inserisco il messaggio di sistema in chat
    chatInsertMessage($luogo, 'System', null, $testo, 'N');
}

// Funzione per gestire l'inventario in base alle cariche e al numero di oggetti
function gestisciInventario($login, $id_oggetto, $cariche) {
    // Recupera l'oggetto dal DB
    $info = gdrcd_query("SELECT cariche, numero FROM clgpersonaggiooggetto WHERE id_oggetto = '$id_oggetto' AND nome = '$login'");

    $oggetto = gdrcd_query("SELECT cariche AS cariche_default, richiede_ricarica FROM oggetto WHERE id_oggetto = '$id_oggetto'");

    if (!$info || !$oggetto) return;

    $cariche = (int)$info['cariche'];
    $numero = (int)$info['numero'];
    $cariche_default = (int)$oggetto['cariche_default'];
    $ricaricabile = (int)$oggetto['richiede_ricarica'];

    // Se ha cariche illimitate
    if ($cariche == -1) return;

    // Se ha più di 1 carica, scala normalmente
    if ($cariche > 1) {
        $cariche--;
        gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = $cariche WHERE id_oggetto = '$id_oggetto' AND nome = '$login' LIMIT 1");

        return;
    }

    // cariche == 1 ➜ consumo l'ultima carica
    if ($cariche == 1) {
        if ($numero > 1) { // Scala il numero e resetta le cariche
            $numero--;
            gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = $cariche_default, numero = $numero WHERE id_oggetto = '$id_oggetto' AND nome = '$login' LIMIT 1");
        } else { // numero == 1 ➜ ultimo oggetto
            if ($ricaricabile) gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = 0, numero = 0 WHERE id_oggetto = '$id_oggetto' AND nome = '$login' LIMIT 1");
            else gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = '$id_oggetto' AND nome = '$login' LIMIT 1");
        }
    }
}
/************* FINE CHAT di gioco ******************************/

/************* Gestione ROLEPLAYS ******************************/
// Controlla se il personaggio ha role attive non congelate
function pgIsInRole($userName = '', $location = null, $active = true) {
    $location_filter = $location ? "AND role_sessions.location = $location" : '';
    $active_filter = $active ? "AND role_session_players.end IS NULL " : ''; // Controlla solo le role attive
    $user_filter = $userName != '' ? "AND role_session_players.pg_name = '$userName' " : '';
    $result = gdrcd_query("SELECT role_session_players.* FROM role_session_players 
                            INNER JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role 
                            WHERE role_sessions.end IS NULL 
                            AND role_sessions.freezed IS NULL  
                            $user_filter
                            $active_filter
                            $location_filter", 'result');

    if ($result && gdrcd_query($result, 'num_rows') > 0) return true;
    
    return false;
}

// Controlla se nella location ci sono già role attive
function locationActiveRole($location) {
    $result = gdrcd_query("SELECT id_role FROM role_sessions WHERE `location` = $location AND role_sessions.freezed IS NULL AND role_sessions.end IS NULL", 'result');

    if ($result && gdrcd_query($result, 'num_rows') > 0) {
        $row = gdrcd_query($result, 'fetch');
        return (int)$row['id_role'];
    }
    
    return false;
}

function startRoleSession($selectedUsers, $location) {
    gdrcd_query("INSERT INTO role_sessions (`location`, `start`) VALUES ($location, NOW())");

    $sess_id = gdrcd_query("SELECT LAST_INSERT_ID() AS id")['id'];
    
    // Costruisco la query per l'inserimento dei giocatori
    $users_insert = '';
    foreach($selectedUsers as $key => $player) {
        $users_insert .= "($sess_id, '".$player."')";
        $users_insert .= ($key < count($selectedUsers) - 1) ? ',' : ''; // Aggiungo la virgola se non è l'ultimo
    }

    chatInsertMessage($location, 'System', 'System', 'Role attiva per '.implode(', ', $selectedUsers).', buon divertimento!', 'N');

    return gdrcd_query("INSERT INTO role_session_players (id_role, pg_name) VALUES $users_insert");
}

function addPgToRoleSession($selectedUsers, $location) {
    $sess_id = locationActiveRole($location); // Recupero l'id della role
    
    // Costruisco la query per l'inserimento dei giocatori
    $users_insert = '';
    foreach($selectedUsers as $key => $player) {
        $users_insert .= "($sess_id, '".$player."')";
        $users_insert .= ($key < count($selectedUsers) - 1) ? ',' : ''; // Aggiungo la virgola se non è l'ultimo
    }

    chatInsertMessage($location, 'System', 'Aggiunti nuovi personaggi alla role!', 'N');

    return gdrcd_query("INSERT INTO role_session_players (id_role, pg_name) VALUES $users_insert");
}

function endRoleSession($location) {
    gdrcd_query("UPDATE role_sessions SET `end` = NOW() WHERE `location` = $location");

    chatInsertMessage($location, 'System', null, 'Role conclusa!', 'N');
}

// Controlla se il turno è stato completato, altrimenti segna il messaggio del pg come "inviato"
function checkTurn($location, $user, $id_role) {
    // Metto subito l'invio da parte del pg per il turno corrente
    gdrcd_query("UPDATE role_session_players SET `sent` = 1 WHERE id_role = $id_role AND pg_name = '$user'");

    // Poi controllo se tutti hanno inviato nel turno corrente
    $result = gdrcd_query("SELECT role_session_players.* FROM role_session_players LEFT JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role WHERE role_session_players.id_role = $id_role AND role_session_players.sent = 0 AND role_session_players.end IS NULL AND role_sessions.long_turn IS NULL", 'result');

    // Se nel turno tutti i pg hanno inviato l'azione 'P', domando all'ultimo pg se vuole lanciare un attacco (skill/dado)
    if ($result && gdrcd_query($result, 'num_rows') == 0) {
        $msg = '<button onclick="longTurn('.$id_role.', 1);">SI</button> Procederai con un attacco prima di chiudere il turno? <button onclick="longTurn('.$id_role.', 0);">NO</button>';
        chatInsertMessage($location, 'System', $user, $msg, 'S');
    }
}

// Se l'ultimo pg che aziona deve lanciare un attacco, setta il campo long_turn a 1
function longTurn($id_role, $user) {
    return gdrcd_query("UPDATE role_sessions SET long_turn = '$user' WHERE id_role = $id_role AND `end` IS NULL");
}

// Lancia lo scudo finale prima della fine del turno
function lanciaScudo($id_role, $location) {
    closeTurn($id_role, $location);
}

// Segna il turno come completato e resetta i flag "inviato" per tutti i pg
function closeTurn($id_role, $location) {
    // Riporto gli invii a zero per tutti i pg della role
    gdrcd_query("UPDATE role_session_players SET `sent` = 0 WHERE id_role = $id_role AND `end` IS NULL");

    // Elaboro il turno per calcolare eventuali danni
    // elaborateTurn($id_role);

    // Passo al turno successivo
    gdrcd_query("UPDATE role_sessions SET turn = (turn + 1), long_turn = '' WHERE id_role = $id_role AND freezed IS NULL AND `end` IS NULL");

    chatInsertMessage($location, 'System', NULL, 'Turno chiuso! Iniziate il turno successivo...', 'M');
}

function elaborateTurn($id_role) {
    // Prendo tutti i personaggi della role per ciclarli
    $result = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role", 'result');

    // Prendo l'ultima azione di tipo 'C' di ogni pg coinvolto nella role
    foreach($result as $player) {
        $last_action = gdrcd_query("SELECT * FROM chat WHERE mittente = '".$player['pg_name']."' AND id_role = $id_role AND tipo = 'C' ORDER BY ora DESC LIMIT 1");

        // Qui puoi aggiungere la logica per elaborare l'azione del pg
        // Ad esempio, calcolare danni, effetti, ecc.
    }
}
/************* FINE Gestione ROLEPLAYS ******************************/
?>