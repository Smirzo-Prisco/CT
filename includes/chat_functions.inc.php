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
function handleNormalMessage(&$chat_message, $action_tag, &$sender, $m_type, &$session, $parameters, $id_role) {
    if (empty($chat_message)) return;
    
    $imgs = $session['sesso'] . ";" . $session['img_razza'];
    $target = null;

    // Inserisci il messaggio
    chatInsertMessage($session['luogo'], $sender, $targt, $chat_message, $m_type, null, $action_tag, $imgs);

    if ($parameters['mode']['exp_by_chat'] == 'ON' && $id_role != false && $m_type === 'P') handleExperienceRewards($id_role, $session); // Assegnazione punti
    if($m_type === 'P' || $m_type === 'M') checkTurnEnd($session['luogo'], $session['login'], $id_role); // Se è un'azione o un master, controllo il turno

    // Traccia il turno in cui il master ha scritto, per l'opzione "richiedi azione master
    // a inizio turno" (questCheckTimerAndOrder) — vale anche per i sotto-tipi I/Y.
    if ($id_role && in_array($m_type, ['M', 'I', 'Y'], true)) {
        gdrcd_query("UPDATE role_sessions SET master_last_turn = turn WHERE id_role = $id_role");
    }
}

/**  * Determina il tipo di messaggio  */
function determineMessageType($type, $first_char, $second_char, &$chat_message, &$tag_n_beyond, $actual_healt, &$session) {
    global $MESSAGE;
    
    // Sussurro normale
    if (($type == "4") || ($first_char == "@")) return 'S'; // return handleWhisperMessage($type, $chat_message, $tag_n_beyond, 'S', $session);
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
    $session['tag'] = $tag_n_beyond;
    return 'P';
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
                                   AND nome = '$tag_n_beyond' 
                                   LIMIT 1", 'result');
        
        if (gdrcd_query($r_check_dest, 'num_rows') < 1) {
            $chat_message = $tag_n_beyond . ' ' . gdrcd_filter('in', $MESSAGE['chat']['whisper']['no']);
            $tag_n_beyond = $session['login'];
        }
    } else $tag_n_beyond = $session['tag'];
    
    chatInsertMessage($session['luogo'], $session['login'], $tag_n_beyond, $chat_message, $m_type);
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
    
    if ($counts['off'] == 0 && isset($session['admin']) && $session['admin'] <> 1) {
        $chat_message = gdrcd_filter('in', $MESSAGE['status_pg']['nooff']);
        return 'S';
    }
    
    if ($counts['stopoff'] > 4 && isset($session['admin']) && $session['admin'] <> 1) {
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
    
    if (in_array($first_char, array("=", "-|", "|", "*"))) $chat_message = substr($chat_message, 1);
    
    if ($first_char == "|") $m_type = 'I';
    if ($first_char == "*") $m_type = 'Y';
    
    return $m_type;
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

    if ($check_actions == 4) awardExperience($session);
    
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
    return (int)$result['total_actions'];
}

/**  * Assegna esperienza normale  */
function awardExperience($session) {
    $nome_luogo = gdrcd_query("SELECT nome FROM mappa WHERE id=" . $session['luogo'] . "");
    $resoconto = "Giocata libera - " . $nome_luogo['nome'] . "";
    
    gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 1, esperienza_r = esperienza_r + 1, last_date_exp = NOW() WHERE nome = '" . $session['login'] . "' LIMIT 1");
    gdrcd_query("INSERT INTO Punti (nome, esperienza, data_evento, commento) VALUES ('" . $session['login'] . "', '1', NOW(), '" . gdrcd_filter('in', $resoconto) . "')");
    
    chatInsertMessage($session['luogo'], 'System', $session['login'], 'Punto esperienza assegnato', 'Q');
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
    
    chatInsertMessage($session['luogo'], 'System', $session['login'], 'Punto mestiere assegnato', 'Q');
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
    chatInsertMessage($session['luogo'], 'System', $session['login'], $message_text, 'Q');

    gdrcd_query("INSERT INTO bak_chat (stanza, mittente, destinatario, ora, tipo, testo) 
                VALUES (" . $session['luogo'] . ", 'System message', '" . $session['login'] . "', NOW(), 'Q', '" . $message_text . "')");
}

/***************    Inserimento messaggio in chat *********************/
function chatInsertMessage($location, $sender, $target = null, $msg, $m_type, $sussurro = null, $action_tag = '', $imgs = null) {
    $activeRole = locationActiveRole($location);
    $id_role = !$activeRole ? 0 : $activeRole; // Forzo il id_role a zero se non c'è una role attiva ma un admin/master/mod decide di scrivere in chat
    $check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$sender'");
    $back_chat = ($check_backing['back_chat'] == 1) ? 1 : 0;

    // Aggiungo il tag se presente
    if($action_tag != '')  $msg = "[$action_tag] $msg";

    gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo, backing, id_role) VALUES ('$location', '$sender', '$target', NOW(), '$m_type', '".gdrcd_filter('in', $msg)."', $back_chat, $id_role)");
    if ($sussurro !== null) gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo, id_role) VALUES ('$location', 'System', '$sender', NOW(), 'Q', '".gdrcd_filter('in', $sussurro)."', $id_role)");

    notifySocketServer('chat:update', 'chat:' . (int)$location);
    return true;
}
/***************    FINE    Inserimento messaggio in chat *********************/
/************* FINE INSERIMENTO AZIONE ******************************/

/************* LANCIO SKILL DELLA CHAT ******************************/
function calcolaLivelloTalento($tempra) {
    $d20 = mt_rand(1, 20);
    $bonus = (($tempra / 10) - 1);
    $totale = $d20 + $bonus;
    $bonus_talento = 1;

    if ($totale < 17) {
        $bonus_talento = 0.5;
        $livello = "di livello 1";
    } elseif ($totale > 25) {
        $bonus_talento = 1.5;
        $livello = "di livello 3";
    } else $livello = "di livello 2";

    return [
        'livello' => $livello,
        'testo' => "tot. tempra per la valutazione del livello: $d20/20 + $bonus = $numtotaletot",
        'bonus_talento' => $bonus_talento
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
    $check_avversario_usage = gdrcd_query("SELECT COUNT(*) AS cnt FROM chat WHERE stanza = '$luogo' AND destinatario = '$avversario' AND testo LIKE '%Pronto soccorso%'");

    if ($check_usage['cnt'] > 0 || $check_avversario_usage['cnt'] > 0) return "System: Non puoi riusare questo talento.";

    // Determina se la cura è possibile
    if ($salute_login > $soglia_salute) {
        if ($avversario == $login) {
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
        chatInsertMessage($luogo, 'System', $login, 'Punto shin assegnato', 'Q');
        gdrcd_query("UPDATE personaggio SET shin = shin + 1, last_date_shin = NOW() WHERE nome = '$login' LIMIT 1");
    }
}

function determinaTipoEvento($luogo) {
    // Recupera le ultime azioni in chat relative alla stanza (luogo)
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

/****** Gestione polizia automatica */
function gestionePoliziaAutomatica($luogo) {
    $zona = gdrcd_query("SELECT * FROM mappa WHERE id = '$luogo'");
    $num_police = mt_rand(1, 20);
    $turni = calcolaTurniPolizia($zona['id_mappa'], $num_police);
    $check_skill_attacco = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND (testo LIKE '%usa la skill di attacco%' || testo LIKE '%attacca con%' || testo LIKE '%attacca fisicamente%') AND DATE_ADD(ora, INTERVAL 4 HOUR) >= NOW() AND tipo = 'C'", 'result');
    $check_master_nope = gdrcd_query("SELECT * FROM chat WHERE stanza = '$luogo' AND DATE_ADD(ora, INTERVAL 4 HOUR) >= NOW() AND tipo = 'M'", 'result');

    if (verificaArrivoPolizia($zona['id_mappa'], gdrcd_query($check_skill_attacco, 'num_rows'), gdrcd_query($check_master_nope, 'num_rows'))) {
        $msg = 'A seguito di ripetuti attacchi, alcuni cittadini hanno avvisato la polizia. <b>Tempo di arrivo della pattuglia:</b> ' . $turni . '.';
        chatInsertMessage($luogo, 'System', null, $msg, 'M');
        notificaMasterEStaff($luogo);
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

function notificaMasterEStaff($luogo) {
    // Recupera il nome del luogo
    $zona_result = gdrcd_query("SELECT nome FROM mappa WHERE id = '$luogo'");
    
    if ($zona_result) $zona = $zona_result['nome'];
    else $zona = "sconosciuto"; // In caso di errore o se non viene trovato il luogo
    
    // Prepara i testi per i diversi scenari
    $tipo_evento = determinaTipoEvento($luogo); // Funzione che determina il tipo di evento (armi, attacco fisico, ibrido, skill)
    
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
/****** FINE    Gestione polizia automatica */
/************* FINE LANCIO SKILL DELLA CHAT ******************************/

/************* LANCIO STATS DELLA CHAT ******************************/
function lanciaStat($id_role, $login, $bersaglio, $bonus_stats, $dice_type, $nome_tiro, $caratteristica, $salute, $dice_bonus, $dice_malus) {
    $num = mt_rand(1, 20);
    $numtot_finale = $num;
    $bonus_caratteristica = 0;
    $malus_salute = 0;
    $sussurro = '';

    // Se il tipo di lancio consente l'aggiunta dei bonus statistiche (caratteristiche)
    if ($bonus_stats) {
        // Se non è un D20 random, aggiungo al risultato anche i bonus per i punti stats
        if ($dice_type !== 'D20') {
            $bonus_caratteristica = (($caratteristica / 10) - 1);
            $numtot_finale += $bonus_caratteristica;
        }

        // A seconda della salute del pg, applico un malus
        if ($salute <= 50) {
            if ($salute > 40) $malus_salute = 1;
            elseif ($salute > 30) $malus_salute = 3;
            elseif ($salute > 20) $malus_salute = 5;
            elseif ($salute > 0) $malus_salute = 10;
            
            $numtot_finale -= $malus_salute;
        }
        // Buff pendenti da oggetti usati nella role (consumati al primo lancio sulla caratteristica corrispondente)
        $buff_col_map = [
            'Destrezza' => 'bonus_destrezza',
            'Mente'     => 'bonus_mente',
            'Tempra'    => 'bonus_tempra',
            'Potere'    => 'bonus_potere',
        ];
        if ($id_role && isset($buff_col_map[$dice_type])) {
            $col     = $buff_col_map[$dice_type];
            $lgn_f   = gdrcd_filter('in', $login);
            $buf_res = gdrcd_query(
                "SELECT id, $col AS bonus FROM role_item_buffs WHERE id_role = $id_role AND pg_name = '$lgn_f' AND $col > 0",
                'result'
            );
            $item_bonus = 0;
            $buff_ids   = [];
            while ($b = gdrcd_query($buf_res, 'fetch')) {
                $item_bonus += (int)$b['bonus'];
                $buff_ids[]  = (int)$b['id'];
            }
            gdrcd_query($buf_res, 'free');
            if (!empty($buff_ids)) {
                $dice_bonus += $item_bonus;
                gdrcd_query("DELETE FROM role_item_buffs WHERE id IN (" . implode(',', $buff_ids) . ")");
            }
        }

        // Buff persistenti da abilità speciali riuscite nella role (non consumati al lancio)
        if ($id_role && isset($buff_col_map[$dice_type])) {
            $col    = $buff_col_map[$dice_type];
            $lgn_f  = gdrcd_filter('in', $login);
            $sk_row = gdrcd_query("SELECT COALESCE(SUM($col), 0) AS bonus FROM role_skill_buffs WHERE id_role = $id_role AND pg_name = '$lgn_f'");
            $skill_bonus = (int)($sk_row['bonus'] ?? 0);
            if ($skill_bonus > 0) $dice_bonus += $skill_bonus;
        }

        // Aggiungo bonus e malus selezionati dall'utente
        $numtot_finale += $dice_bonus;
        $numtot_finale -= $dice_malus;

        // Costruzione del sussurro dettagliato se applicabile
        if ($dice_type !== 'Usa dado master' && $dice_type !== 'AttCreatura' && $dice_type !== 'DifCreatura') {
            $sussurro = "$num/20";

            if ($bonus_caratteristica > 0)      $sussurro .= " + $bonus_caratteristica";
            elseif ($bonus_caratteristica < 0)  $sussurro .= " - " . abs($bonus_caratteristica);
            if ($dice_bonus > 0) $sussurro .= " + $dice_bonus di bonus";
            if ($dice_malus > 0) $sussurro .= " - $dice_malus di malus";
            if ($malus_salute > 0) $sussurro .= " - $malus_salute di malus per la salute";
            
            $sussurro .= " = $numtot_finale";
            if ($num === 20) $sussurro = "⚡ CRITICO! " . $sussurro;
        }
    }

    // Costruzione del messaggio in chat
    $messaggio = "$login ha lanciato $nome_tiro verso $bersaglio totalizzando $numtot_finale";

    // Aggiunta descrizione bonus/malus selezionato, solo se presente
    $note_bonus_malus = [];

    if ($dice_bonus > 0) $note_bonus_malus[] = "$dice_bonus di bonus";
    if ($dice_malus > 0) $note_bonus_malus[] = "$dice_malus di malus";
    if (!empty($note_bonus_malus)) $messaggio .= " (comprensivo di " . implode(" e ", $note_bonus_malus) . ")";

    return array(
        'messaggio' => $messaggio,
        'sussurro' => $sussurro,
        'risultato' => $numtot_finale,
        'dado_raw' => $num
    );
}
/************* FINE LANCIO STATS DELLA CHAT ******************************/

// Funzione per gestire il limite di caratteri impostato dall'utente
function imposta_limite_caratteri_utente($login, $luogo, $caratteri) {
    $result = gdrcd_query("SELECT * FROM scelte_utenti WHERE nome = '$login' AND id_luogo = '$luogo'", 'result');

    if (gdrcd_query($result, 'num_rows') > 0) $query = "UPDATE scelte_utenti SET lunghezza_massima = '$caratteri', timestamp_modifica = NOW() WHERE nome = '$login' AND id_luogo = '$luogo'";
    else $query = "INSERT INTO scelte_utenti (nome, id_luogo, lunghezza_massima, timestamp_modifica) VALUES ('$login', '$luogo', '$caratteri', NOW())";

    if(gdrcd_query($query)) return true;

    return false;
}

// Funzione per aggiornare il limite globale nella mappa
function aggiorna_limite_globale($luogo, $caratteri, $login) {
    // Recupero il vecchio limite prima di sovrascriverlo
    $vecchio_limite = gdrcd_query("SELECT limite_lunghezza_massima FROM mappa WHERE id = $luogo")["limite_lunghezza_massima"];
    // Sofrascrivo il vecchio limite con il nuovo
    gdrcd_query("UPDATE mappa SET limite_lunghezza_massima = $caratteri, timestamp_modifica_limite = NOW() WHERE id = $luogo");

    $personaggio = gdrcd_query($result, 'fetch')['nome'];
    $testo = "Il personaggio $login ha scelto di settare per tutti una lunghezza massima di $caratteri";
    
    // Se il nuovo limite è maggiore del vecchio, aggiungo il pulsante di revoca
    $testo .= $caratteri > $vecchio_limite ? " <button class='btn-revoca' onclick='revocaLimiteCaratteri($caratteri, $vecchio_limite, \"$luogo\", \"$personaggio\");'>Revoca</button>" : '';
    $msg = gdrcd_filter('in', $testo);
    $timestamp_minuto_successivo = date('Y-m-d H:i:s', strtotime('+1 minute'));
    
    // Inserisco il messaggio di sistema in chat
    chatInsertMessage($luogo, 'System', null, $msg, 'N');
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

/************* LANCIO ATTACCO DELLA CHAT (Armi, fisico, creatura) ******************************/
function getWeaponAttack ($id_arma, $pg_name, $bonus_talento) {
    $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = $id_arma");
    $nome_arma = $oggetto['nome'];
    $bonus_arma = $oggetto['attacco'];
    $tipo_arma = $oggetto['tipo_arma'];
    $obj_cat = $oggetto['categoria'];
    $descrizione_attacco = "attacca con $nome_arma";

    switch ($tipo_arma) {
        case 1: // arma bianca
            $check_abilita = gdrcd_query("SELECT COUNT(*) AS n_bianca FROM clgpersonaggioabilita WHERE id_abilita = 41 AND nome = '$pg_name'")['n_bianca'];
            $sussurro_specifico = "+ $bonus_talento (talento arma bianca)";
            
            break;
        case 2: // arma da lancio
            $check_abilita = gdrcd_query("SELECT COUNT(*) AS n_lancio FROM clgpersonaggioabilita WHERE id_abilita = 42 AND nome = '$pg_name'")['n_lancio'];
            $sussurro_specifico = "+ $bonus_talento (talento arma da lancio)";
            
            break;
        case 3: // arma da fuoco
            $check_abilita = gdrcd_query("SELECT COUNT(*) AS n_fuoco FROM clgpersonaggioabilita WHERE id_abilita = 40 AND nome = '$pg_name'")['n_fuoco'];
            $sussurro_specifico = "+ $bonus_talento (talento arma da fuoco)";
            
            break;
        default: // arma sconosciuta
            $sussurro_specifico = '';
            $check_abilita = 0;
            break;
    }

    // Se è un'arma, consumo una carica
    if ($obj_cat === 'arma') gdrcd_query("UPDATE clgpersonaggiooggetto SET cariche = (cariche - 1) WHERE nome = '$pg_name' AND id_oggetto = $id_arma AND cariche > 0");

    return [
        'descrizione_attacco' => $descrizione_attacco,
        'sussurro_specifico' => $sussurro_specifico,
        'bonus_arma' => $bonus_arma,
        'check_abilita' => $check_abilita
    ];
}
/************* FINE LANCIO ATTACCO DELLA CHAT (Armi, fisico, creatura) ******************************/
/************* FINE CHAT di gioco ******************************/

/************* Gestione ROLEPLAYS ******************************/

function ensureQuestSchema() {
    static $done = false;
    if ($done) return;
    $done = true;
    $cols = gdrcd_query("SHOW COLUMNS FROM role_sessions", 'result');
    $existing = [];
    while ($r = gdrcd_query($cols, 'fetch')) $existing[] = $r['Field'];
    if (!in_array('timer_end', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN timer_end BIGINT NULL DEFAULT NULL");
    if (!in_array('turn_mode', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN turn_mode ENUM('liberi','fissi') NOT NULL DEFAULT 'liberi'");
    if (!in_array('turn_order_idx', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN turn_order_idx INT NOT NULL DEFAULT 0");
    if (!in_array('audio_video_id', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN audio_video_id VARCHAR(20) NULL DEFAULT NULL");
    if (!in_array('audio_started_at', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN audio_started_at BIGINT NULL DEFAULT NULL");
    if (!in_array('is_quest', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN is_quest TINYINT(1) NOT NULL DEFAULT 0");
    if (!in_array('quest_recap_thread_id', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN quest_recap_thread_id INT NULL DEFAULT NULL");
    // Master tracciato direttamente (chi ha avviato/attivato la quest): evita di dover
    // scandagliare la chat (tipo M/I/Y) per risalirci in role_recap — vedi getPgAllRoles.
    if (!in_array('master', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN master VARCHAR(20) NULL DEFAULT NULL, ADD INDEX idx_master (master)");
    // Opzione "richiedi azione master a inizio turno": require_master_first è lo switch,
    // master_last_turn tiene traccia dell'ultimo turno in cui il master ha scritto un
    // messaggio (tipo M/I/Y) — aggiornato in handleNormalMessage(). Il confronto
    // master_last_turn < turn (fatto in questCheckTimerAndOrder) dice se il master ha
    // già "aperto" il turno corrente, senza dover associare un numero di turno ai
    // messaggi in chat (che non lo tracciano).
    if (!in_array('require_master_first', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN require_master_first TINYINT(1) NOT NULL DEFAULT 0");
    if (!in_array('master_last_turn', $existing))
        gdrcd_query("ALTER TABLE role_sessions ADD COLUMN master_last_turn INT NOT NULL DEFAULT 0");
    gdrcd_query("CREATE TABLE IF NOT EXISTS quest_turn_order (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_role INT NOT NULL,
        pg_name VARCHAR(50) NOT NULL,
        position INT NOT NULL DEFAULT 0,
        UNIQUE KEY uk_role_pg (id_role, pg_name)
    )");
}

// Estrae l'ID video (11 caratteri) da un URL YouTube in uno dei formati comuni
// (watch?v=, youtu.be/, embed/, shorts/). Restituisce false se non riconosciuto.
function extractYoutubeId($url) {
    if (preg_match('/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m))
        return $m[1];
    return false;
}

function questCheckTimerAndOrder($id_role, $login, $is_roll = false) {
    if (!$id_role) return null;
    $role = gdrcd_query("SELECT timer_end, turn_mode, turn_order_idx, require_master_first, master_last_turn, turn FROM role_sessions WHERE id_role = $id_role");
    if (!$role) return null;
    if ($role['timer_end'] !== null) {
        $now_ms = (int)(microtime(true) * 1000);
        if ($now_ms >= (int)$role['timer_end'])
            return 'Tempo scaduto! Attendi che il Master avvii il prossimo turno.';
    }
    // Vale per azioni testuali e lanci: il master deve aver scritto qualcosa nel turno
    // corrente prima che chiunque altro possa agire.
    if (!empty($role['require_master_first']) && (int)$role['master_last_turn'] < (int)$role['turn']) {
        return 'Attendi che il Master apra il turno prima di agire.';
    }
    if (!$is_roll && $role['turn_mode'] === 'fissi') {
        $idx = (int)$role['turn_order_idx'];
        $res = gdrcd_query("SELECT pg_name FROM quest_turn_order WHERE id_role = $id_role ORDER BY position ASC", 'result');
        $players = [];
        while ($r = gdrcd_query($res, 'fetch')) $players[] = $r['pg_name'];
        if (!empty($players) && (!isset($players[$idx]) || $players[$idx] !== $login))
            return 'Turni fissi: è il turno di <b>' . ($players[$idx] ?? '—') . '</b>. Attendi il tuo.';
    }
    return null;
}

function addPgToRole($id_role, $pg_name, $location, $png = 0) {
    // can_send = 1 garantisce che il pg possa vedere dado/scudo sin dal primo turno
    gdrcd_query("INSERT INTO role_session_players (id_role, pg_name, png, can_send) VALUES ($id_role, '$pg_name', $png, 1)");
    chatInsertMessage($location, 'System', $pg_name, " si è aggiunto alla role", 'N');
}

// Controlla se il personaggio ha role attive non congelate
function pgIsInRole($userName = '', $location = null, $active = true, $excludePng = false) {
    $user_filter = $userName != '' ? "AND role_session_players.pg_name = '$userName' " : ''; // Cerco lo specifico pg in role
    $location_filter = $location ? "AND role_sessions.location = $location" : ''; // Cerco nella role della chat
    $active_filter = $active ? "AND role_session_players.end IS NULL " : ''; // Cerco pg che non sono usciti dalla role (di default)
    $png_filter = $excludePng ? "AND role_session_players.png = 0 " : ''; // Esclude i png: solo pg reali

    // Seleziono tutti i pg in role
    $result = gdrcd_query("SELECT role_session_players.* FROM role_session_players
                            INNER JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role
                            WHERE role_sessions.end IS NULL
                            AND role_sessions.freezed IS NULL
                            $user_filter
                            $location_filter
                            $active_filter
                            $png_filter", 'result');

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

function endRoleSession($location) {
    $role_row = gdrcd_query("SELECT id_role FROM role_sessions WHERE location = $location AND end IS NULL");
    if ($role_row) {
        $rid = (int)$role_row['id_role'];
        gdrcd_query("DELETE FROM role_item_buffs WHERE id_role = $rid");
        gdrcd_query("DELETE FROM role_skill_buffs WHERE id_role = $rid");
    }

    gdrcd_query("UPDATE role_sessions SET `end` = NOW() WHERE `location` = $location");
    notifySocketServer('quest:audio_stop', 'loc:' . $location, []);

    if ($role_row) deleteRolePngs($rid);

    chatInsertMessage($location, 'System', null, 'Role conclusa!', 'N');
}

// Elimina fisicamente dalla tabella personaggio i png della role appena conclusa,
// a meno che lo stesso nome non sia ancora impegnato in un'altra role ancora attiva
// altrove (es. la stessa "creatura di X" evocata più volte).
function deleteRolePngs($id_role) {
    $result = gdrcd_query("SELECT pg_name FROM role_session_players WHERE id_role = $id_role AND png = 1", 'result');
    $png_names = [];
    while ($row = gdrcd_query($result, 'fetch')) $png_names[] = $row['pg_name'];

    foreach ($png_names as $png_name) {
        $png_name_f = gdrcd_filter('in', $png_name);
        $still_active = gdrcd_query("SELECT 1 FROM role_session_players rsp
            INNER JOIN role_sessions rs ON rs.id_role = rsp.id_role
            WHERE rsp.pg_name = '$png_name_f' AND rsp.png = 1 AND rs.end IS NULL");
        if (!$still_active) gdrcd_query("DELETE FROM personaggio WHERE nome = '$png_name_f' LIMIT 1");
    }
}

// Controllo se tutti i pg hanno inviato, così propongo la chiusura del turno a tutti quanti
function checkTurnEnd($location, $user, $id_role) {
    gdrcd_query("UPDATE role_session_players SET `sent` = 1 WHERE id_role = $id_role AND pg_name LIKE '%$user%'"); // Il pg ha azionato, sent = 1
    // Cerco tutti i pg che non hanno ancora azionato nel turno corrente
    $result = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role AND `sent` = 0 AND role_session_players.end IS NULL", 'result');

    // Se non li trovo, significa che tutti hanno azionato
    if ($result && gdrcd_query($result, 'num_rows') === 0) {
        // Recupero tutti i pg che hanno azionato ma non ancora chiuso il turno
        $pgs = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role AND `sent` = 1 AND close_turn = 0 AND role_session_players.end IS NULL", 'result');

        // Se c'è un solo pg TOTALE nella role, è da solo: non proporgli la chiusura
        // (non usare num_rows di $pgs: conta solo i pg con close_turn=0, non quelli
        // già auto-chiusi, e farebbe scattare il return in modo errato nelle sessioni
        // a due giocatori dove uno ha già chiuso il turno con un lancio)
        $totalActive = (int)(gdrcd_query("SELECT COUNT(*) AS n FROM role_session_players WHERE id_role = $id_role AND `end` IS NULL")['n'] ?? 0);
        if ($totalActive <= 1) return;

        $turn = getTurn($id_role);

        // Se il timer è attivo, auto-conferma tutti senza inviare il prompt
        $timerActive = false;
        $roleRow = gdrcd_query("SELECT timer_end FROM role_sessions WHERE id_role = $id_role");
        if ($roleRow && $roleRow['timer_end'] !== null) {
            $now_ms = (int)(microtime(true) * 1000);
            if ($now_ms < (int)$roleRow['timer_end']) $timerActive = true;
        }

        while ($pg = gdrcd_query($pgs, 'fetch')) {
            $pgName = $pg['pg_name'];

            if ($timerActive || hasShieldLaunch($id_role, $pgName, $turn)) {
                // Timer attivo o scudo già lanciato: chiusura automatica senza chiedere
                gdrcd_query("UPDATE role_session_players SET close_turn = 1 WHERE id_role = $id_role AND pg_name = '$pgName' AND `end` IS NULL");
            } else {
                // Nessun scudo: notifica diretta alla room personale dm:$pgName (evita
                // broadcast + filtro client-side che può fallire su mismatch nome)
                notifySocketServer('combat:close_turn', 'dm:' . $pgName, [
                    'id_role' => (int)$id_role,
                    'pg_name' => $pgName,
                ]);
            }
        }

        // Se tutti sono stati auto-chiusi (nessun bottone necessario), chiude il turno subito (solo se nessun attacco è in sospeso)
        $stillOpen = gdrcd_query("SELECT 1 FROM role_session_players WHERE id_role = $id_role AND close_turn = 0 AND `end` IS NULL LIMIT 1", 'result');
        if ($stillOpen && gdrcd_query($stillOpen, 'num_rows') === 0) checkTurnCanClose($id_role, $location);
    }
}

// Segna il turno come completato e resetta i flag "inviato" per tutti i pg
function closePgTurn($id_role, $pgName, $location) {
    // Il pg ha scelto di terminare il suo turno
    gdrcd_query("UPDATE role_session_players SET close_turn = 1 WHERE id_role = $id_role AND pg_name LIKE '%$pgName%' AND `end` IS NULL");

    // Verifico se tutti i pg, ignorando i png, hanno scelto di chiudere il turno
    $result = gdrcd_query("SELECT * FROM role_session_players WHERE id_role = $id_role AND close_turn = 0 AND `end` IS NULL", 'result');

    // Se tutti hanno scelto di chiudere il turno, chiudo il turno (solo se nessun attacco è in sospeso)
    if ($result && gdrcd_query($result, 'num_rows') == 0) checkTurnCanClose($id_role, $location);
}

// Chiudo il turno
function closeTurn($id_role, $location) {
    // Rispettare l'ordine dell'esecuzione dei metodi
    setCanSend($id_role); // Impedisco o consento al pg di lanciare nel prossimo turno
    $msgElaboration = elaborateTurn($id_role); // Elaboro il turno per calcolare eventuali danni
    gdrcd_query("UPDATE role_sessions SET turn = (turn + 1), timer_end = NULL, turn_order_idx = 0 WHERE id_role = $id_role"); // Passo al turno successivo e resetto lo stato quest
    gdrcd_query("UPDATE role_session_players SET `sent` = 0, close_turn = 0 WHERE id_role = $id_role"); // Riporto tutti i pg a sent = 0 e riapro il turno per tutti
    if ($msgElaboration !== '') chatInsertMessage($location, 'System', NULL, $msgElaboration, 'N');
    chatInsertMessage($location, 'System', NULL, 'Turno chiuso! Iniziate il turno successivo...', 'N');
}

/*************************  ELABORAZIONE TURNO */

/**
 * Genera il riepilogo del turno come HTML card-based.
 * Stile gestito da .ct-turn__* in _chat.scss (compilato in ct-main.css).
 * Solo background:hsl() sugli avatar e width:% sulla barra HP restano inline
 * perché calcolati dinamicamente.
 *
 * Struttura output:
 *   1. Header turno (se $turn passato)
 *   2. Action cards — una per ogni azione (difesa, devia, generica, attacco)
 *   3. Kill notifications (PNG eliminati)
 *   4. Summary grid — una card per PG con danni totali PS/INT
 *
 * Esegue anche gli effetti collaterali: scaloPunti() e killPng().
 *
 * @param array    $riepilogo  Dati strutturati del turno
 * @param int|null $turn       Numero turno per l'header
 * @return string              HTML pronto per l'inserimento in chat
 */
function elaboratePrint($riepilogo, $turn = null) {
    if (empty($riepilogo)) return '';

    // ── Helper closures ───────────────────────────────────────────────────────

    // Solo background:hsl() inline — tutto il resto dalla classe .ct-turn__avatar
    $mkAvatar = function(string $name) : string {
        $init = strtoupper(mb_substr($name, 0, 2));
        $hue  = abs(crc32($name) % 360);
        return "<span class=\"ct-turn__avatar\" style=\"background:hsl($hue,50%,60%)\">{$init}</span>";
    };

    // Solo width:% inline — tutto il resto dalle classi .ct-turn__hpbar__*
    $mkHpBar = function(int $after, string $label = 'PS', int $max = 100) : string {
        $pct   = $max > 0 ? max(0, min(100, (int)round($after / $max * 100))) : 0;
        $level = $pct > 50 ? 'high' : ($pct > 25 ? 'mid' : 'low');
        return "<div class=\"ct-turn__hpbar\">"
             . "<span class=\"ct-turn__hpbar__label\">{$label}</span>"
             . "<div class=\"ct-turn__hpbar__track\">"
             . "<div class=\"ct-turn__hpbar__fill ct-turn__hpbar__fill--{$level}\" style=\"width:{$pct}%\"></div>"
             . "</div>"
             . "<span class=\"ct-turn__hpbar__value\">{$after}/{$max}</span>"
             . "</div>";
    };

    $mkBadge = function(string $txt, string $variant) : string {
        return "<span class=\"ct-turn__badge ct-turn__badge--{$variant}\">{$txt}</span>";
    };

    // Indice subisce: "bersaglio|attaccante" → dati per arricchire le attack card
    $subIdx = [];
    foreach ($riepilogo as $pg => $dati) {
        if (!isset($dati['subisce'])) continue;
        foreach ($dati['subisce'] as $sub) {
            $subIdx["{$pg}|" . ($sub['pg'] ?? '')] = $sub;
        }
    }

    // ── Assemblaggio HTML ─────────────────────────────────────────────────────

    $html = "<div class=\"ct-turn\">";

    if ($turn !== null) {
        $html .= "<div class=\"ct-turn__header\">&#9876; Risultati Turno {$turn}</div>";
    }

    $cardsHtml = '';
    foreach ($riepilogo as $pg => $dati) {

        // Difeso: questo PG è il difensore/protettore
        if (isset($dati['difeso'])) {
            foreach ($dati['difeso'] as $difeso) {
                $badge = $difeso['esito'] === 1
                       ? $mkBadge('Protezione riuscita', 'ok')
                       : $mkBadge('Protezione fallita', 'fail');
                $av2   = ($difeso['pg'] !== $pg)
                       ? $mkAvatar($difeso['pg']) . "<span class=\"ct-turn__name\">{$difeso['pg']}</span>"
                       : "<span class=\"ct-turn__name\">se stesso</span>";
                $cardsHtml .= "<div class=\"ct-turn__card\"><div class=\"ct-turn__row\">"
                            . $mkAvatar($pg) . "<span class=\"ct-turn__name\">{$pg}</span>"
                            . "<span class=\"ct-turn__connector\">&#128737;</span>"
                            . $av2 . $badge . "</div></div>";
            }
        }

        // Difende: volutamente omesso — già coperto dalla card difeso del protettore

        // Devia
        if (isset($dati['devia'])) {
            foreach ($dati['devia'] as $dev) {
                if ($dev['success']) {
                    $badge = $mkBadge('Deviato', 'ok');
                } elseif (($dev['reason'] ?? '') === 'no_attack') {
                    $badge = $mkBadge('Nessun attacco', 'noatk');
                } else {
                    $badge = $mkBadge('Fallito', 'fail');
                }
                $detail = isset($dev['executor_die'], $dev['attacker_die'])
                        ? "<div class=\"ct-turn__formula\">Dado: {$dev['executor_die']} vs {$dev['attacker_die']}</div>"
                        : (($dev['reason'] ?? '') === 'no_attack'
                            ? "<div class=\"ct-turn__formula\">{$dev['attacker']} non ha effettuato attacchi fisici</div>"
                            : '');
                $cardsHtml .= "<div class=\"ct-turn__card\"><div class=\"ct-turn__row\">"
                            . $mkAvatar($pg) . "<span class=\"ct-turn__name\">{$pg}</span>"
                            . "<span class=\"ct-turn__connector\">&#8617;</span>"
                            . $mkAvatar($dev['attacker']) . "<span class=\"ct-turn__name\">{$dev['attacker']}</span>"
                            . $badge . "</div>{$detail}</div>";
            }
        }

        // Generiche
        if (isset($dati['generica']) && is_array($dati['generica'])) {
            foreach ($dati['generica'] as $gen) {
                $cardsHtml .= "<div class=\"ct-turn__card ct-turn__card--generic\">"
                            . "<div class=\"ct-turn__generic-text\">{$gen}</div></div>";
            }
        }

        // Poteri speciali
        if (isset($dati['potere_speciale']) && is_array($dati['potere_speciale'])) {
            $statNames = ['bonus_destrezza' => 'Destrezza', 'bonus_mente' => 'Mente', 'bonus_tempra' => 'Tempra', 'bonus_potere' => 'Potere'];
            foreach ($dati['potere_speciale'] as $ps) {
                if ($ps['esito']) {
                    $badge = $mkBadge('Potere attivato', 'ok');
                    $parts = [];
                    foreach (($ps['bonuses'] ?? []) as $col => $val) {
                        if ($val > 0 && isset($statNames[$col])) $parts[] = "+{$val} {$statNames[$col]}";
                    }
                    $detail = !empty($parts)
                        ? "<div class=\"ct-turn__formula\">" . implode(', ', $parts) . " per tutta la role</div>"
                        : '';
                } else {
                    $badge  = $mkBadge('Potere fallito', 'fail');
                    $detail = "<div class=\"ct-turn__formula\">Dado: {$ps['dice']}/20 — soglia non raggiunta</div>";
                }
                $cardsHtml .= "<div class=\"ct-turn__card\"><div class=\"ct-turn__row\">"
                            . $mkAvatar($pg) . "<span class=\"ct-turn__name\">{$pg}</span>"
                            . $badge . "</div>{$detail}</div>";
            }
        }

        // Attacca: una card per attacco
        if (isset($dati['attacca'])) {
            foreach ($dati['attacca'] as $att) {
                $target   = $att['pg'];
                $danno    = (int)($att['danno'] ?? 0);
                $ptype    = $att['punti_type'] ?? 'salute';
                // getDefenceCar() restituisce 'integrità' (con accento): confrontare con la
                // forma senza accento faceva sempre fallire il match, mostrando "PS" anche
                // sugli attacchi mentali (il totale in fondo era corretto perché lì si usa
                // un else, non un confronto esatto sulla stringa).
                $ptLabel  = $ptype === 'integrità' ? 'INT' : 'PS';
                $sub      = $subIdx["{$target}|{$pg}"] ?? null;
                $shielded = $sub && !empty($sub['intoccabile']);

                $isCritico = isset($att['formula']['critico']) && $att['formula']['critico'];

                if ($shielded) {
                    $badge = $mkBadge('Scudato', 'shield');
                } elseif ($danno <= 0) {
                    $badge = $mkBadge('Mancato', 'miss');
                } elseif ($isCritico) {
                    $badge = $mkBadge('Colpito', 'hit') . "<span class=\"ct-turn__badge ct-turn__badge--critico\">&#9889; Critico!</span>";
                } else {
                    $badge = $mkBadge('Colpito', 'hit');
                }

                $formulaHtml = '';
                if (isset($att['formula']) && is_array($att['formula'])) {
                    $f      = $att['formula'];
                    $dA     = (int)($f['dadoAttacco']    ?? 0);
                    $dD     = (int)($f['dadoDifesa']     ?? 0);
                    $cA     = htmlspecialchars(ucfirst((string)($f['car_attacco'] ?? '')));
                    $cD     = htmlspecialchars(ucfirst((string)($f['car_difesa']  ?? '')));
                    $mul    = (int)($f['moltiplicatore'] ?? 1);
                    $critHtml = $isCritico ? " &times; <b style=\"color:#ffd700;\">2 (critico)</b>" : '';
                    $res    = $danno > 0
                            ? " = <b>{$danno}&nbsp;{$ptLabel}</b>"
                            : " = <b style=\"color:#888;\">0</b>";
                    $formulaHtml = "<div class=\"ct-turn__formula\">"
                                 . "({$dA}&nbsp;{$cA} &#8722; {$dD}&nbsp;{$cD}) &times; {$mul}{$critHtml}{$res}</div>";
                } elseif ($sub && isset($sub['msg'])) {
                    $formulaHtml = "<div class=\"ct-turn__formula\">{$sub['msg']}</div>";
                }

                $hpHtml = '';
                if (!$shielded && $sub && !isset($sub['msg'])) {
                    $punti  = max(0, (int)($sub['punti'] ?? 100));
                    $after  = max(0, $punti - $danno);
                    $hpHtml = $mkHpBar($after, $ptLabel, max(1, (int)($sub['punti_max'] ?? 100)));
                    if (!empty($sub['durata_msg'])) {
                        $hpHtml .= "<div class=\"ct-turn__note ct-turn__note--duration\">{$sub['durata_msg']}</div>";
                    }
                }

                $noteHtml = '';
                if ($sub && !empty($sub['scudo_fallito'])) {
                    $noteHtml = "<div class=\"ct-turn__note\">&#9888; Attacco fallito</div>";
                } elseif ($sub && isset($sub['can_send']) && (int)$sub['can_send'] === 0) {
                    $noteHtml = "<div class=\"ct-turn__note\">&#9888; Nessuna difesa disponibile</div>";
                }

                $cardsHtml .= "<div class=\"ct-turn__card\"><div class=\"ct-turn__row\">"
                            . $mkAvatar($pg) . "<span class=\"ct-turn__name\">{$pg}</span>"
                            . "<span class=\"ct-turn__connector\">&#x2192;</span>"
                            . $mkAvatar($target) . "<span class=\"ct-turn__name\">{$target}</span>"
                            . $badge . "</div>{$formulaHtml}{$hpHtml}{$noteHtml}</div>";
            }
        }
    }

    if ($cardsHtml) $html .= "<div class=\"ct-turn__cards\">{$cardsHtml}</div>";

    // Kill notifications + Summary grid (chiama anche scaloPunti / killPng)
    $killHtml    = '';
    $summaryHtml = '';

    foreach ($riepilogo as $pg => $dati) {
        $ps = 0;
        $pi = 0;

        if (isset($dati['subisce'])) {
            foreach ($dati['subisce'] as $sub) {
                $d = (int)($sub['danno'] ?? 0);
                if (($sub['punti_type'] ?? 'salute') === 'salute') $ps += $d;
                else $pi += $d;
            }
        }

        scaloPunti($pg, $ps, 'salute');
        scaloPunti($pg, $pi, 'integrita');

        // Elimina il PNG solo se ha ricevuto danni PS questo turno E i PS residui sono <= 0
        if ($ps > 0) {
            $isPng = gdrcd_query("SELECT png FROM role_session_players WHERE pg_name = '$pg'")['png'] ?? 0;
            if ($isPng == 1) {
                $psResidui = (int)(gdrcd_query("SELECT salute FROM personaggio WHERE nome = '$pg'")['salute'] ?? 1);
                if ($psResidui <= 0) {
                    $killHtml .= "<div class=\"ct-turn__card ct-turn__card--kill\">&#x2620; <b>{$pg}</b> viene eliminato dallo scontro.</div>";
                    killPng($pg);
                }
            }
        }

        $hasData = isset($dati['attacca']) || isset($dati['subisce'])
                || isset($dati['difende']) || isset($dati['difeso'])
                || isset($dati['devia'])   || isset($dati['generica']);
        if (!$hasData) continue;

        // Override totali da generiche post, altrimenti somma subisce
        $dispPs = isset($dati['totale_salute'])    ? (int)$dati['totale_salute']    : $ps;
        $dispPi = isset($dati['totale_integrita']) ? (int)$dati['totale_integrita'] : $pi;

        $hue  = abs(crc32($pg) % 360);
        $init = strtoupper(mb_substr($pg, 0, 2));
        $dmgH = '';
        if ($dispPs > 0) $dmgH .= "<div class=\"ct-turn__pg-dmg--ps\">&#x2212;{$dispPs} PS</div>";
        if ($dispPi > 0) $dmgH .= "<div class=\"ct-turn__pg-dmg--int\">&#x2212;{$dispPi} INT</div>";
        if (!$dmgH)      $dmgH  = "<div class=\"ct-turn__pg-ok\">Illeso</div>";

        $summaryHtml .= "<div class=\"ct-turn__pg-card\">"
                      . "<div class=\"ct-turn__pg-avatar\" style=\"background:hsl($hue,50%,60%)\">{$init}</div>"
                      . "<div class=\"ct-turn__pg-name\">{$pg}</div>"
                      . $dmgH . "</div>";
    }

    if ($killHtml)    $html .= $killHtml;
    if ($summaryHtml) $html .= "<div class=\"ct-turn__summary\">{$summaryHtml}</div>";

    $html .= '</div>';
    return $html;
}

// Elabora i dati sul combattimento nel singolo turno
function elaborateTurn($id_role) {
    $turn = getTurn($id_role);
    $riepilogo = array();
    
    /****************************** ELABORA LE DIFESE  **************************************/
    $elaborateDefence = elaborateDefence($id_role, $turn, $riepilogo);
    $intoccabili = $elaborateDefence['intoccabili'];
    $difensori = $elaborateDefence['difensori'];

    /****************************** GENERICHE PRE  **************************************/
    $elaborateGenerichePre = elaborateGenerichePre($id_role, $turn, $intoccabili, $riepilogo);
    $intoccabili = $elaborateGenerichePre['intoccabili']; // Aggiorno gli intoccabili con eventuali protezioni generiche riuscite
    
    /****************************** DEVIA ATTACCO  **************************************/
    // Elaboro prima le deviazioni: se riescono cancellano l'attacco fisico dal DB prima di elaborateAttack
    elaborateDevia($id_role, $turn, $riepilogo);

    /****************************** ELABORA GLI ATTACCHI  **************************************/
    elaborateAttack($id_role, $turn, $intoccabili, $difensori, $riepilogo);
    
    /****************************** GENERICHE POST  **************************************/
    // Elaboro eventuali skill generiche che agiscono sul danno (calcolato dopo l'attacco)
    elaborateGenerichePost($id_role, $turn, $riepilogo);

    /****************************** POTERI SPECIALI  **************************************/
    // Registra i buff persistenti per tutta la role se il tiro è riuscito (dado >= 10)
    elaboratePotereSpeciale($id_role, $turn, $riepilogo);

    /****************************** STAMPA RIEPILOGO e TOGLIE I PUNTI  **************************************/
    $msg = elaboratePrint($riepilogo, $turn);
    // Manca la creazione della creatura quando lancio le skill generiche di un certo tipo
    // Mi manca da calcolare la durata
    // Mi manca la gestione dei turni di durata di una generica (in base al d20 delle generiche)

    if (empty($riepilogo)) return '';

    return $msg;
}

// Elaboro le deviazioni: confronto il dado dell'esecutore con quello dell'attaccante dichiarato.
// Se l'esecutore supera l'attaccante, l'attacco fisico (car='destrezza') viene cancellato prima dell'elaborazione danni.
function elaborateDevia($id_role, $turn, &$riepilogo) {
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car = 'devia' ORDER BY id ASC", 'result');
    if (!$result) return;

    while ($r = gdrcd_query($result, 'fetch')) {
        $executor = $r['striker'];
        $attacker = $r['target'];
        $executor_die = (int)$r['dice'];

        if (!isset($riepilogo[$executor])) $riepilogo[$executor] = [];

        // Cerco l'attacco fisico dell'attaccante dichiarato in questo turno
        $attackRow = gdrcd_query("SELECT dice FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$attacker' AND car = 'destrezza' LIMIT 1");

        if (!$attackRow) {
            $riepilogo[$executor]['devia'][] = [
                'attacker' => $attacker,
                'success'  => false,
                'reason'   => 'no_attack',
            ];
            continue;
        }

        $attacker_die = (int)$attackRow['dice'];

        if ($executor_die > $attacker_die) {
            gdrcd_query("DELETE FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$attacker' AND car = 'destrezza'");
            $riepilogo[$executor]['devia'][] = [
                'attacker'     => $attacker,
                'success'      => true,
                'executor_die' => $executor_die,
                'attacker_die' => $attacker_die,
            ];
        } else {
            $riepilogo[$executor]['devia'][] = [
                'attacker'     => $attacker,
                'success'      => false,
                'reason'       => 'failed',
                'executor_die' => $executor_die,
                'attacker_die' => $attacker_die,
            ];
        }
    }
}

// Elaboro tutte le azioni di difesa del turno
function elaborateDefence($id_role, $turn, &$riepilogo) {
    $difensori = [];
    $intoccabili = [];

    // Prendo tutte le azioni di difesa del turno
    $defences = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car IN ('difesa') ORDER BY id ASC", 'result');
    
    // Per ogni azione di difesa scrivo solo se fallisce, altrimenti salvo il successo in un array per la successiva elaborazione degli attacchi
    while ($r = gdrcd_query($defences, 'fetch')) {
        $striker = $r['striker'];
        $target = $r['target'];
        $d20 = $r['dice'];
        
        $difensori[$striker] = true; // Salvo colui che lancia lo scudo. Mi serve per impedire che possa difendersi con i dadi
        
        // Se il dado è 10 o superiore, la difesa ha successo
        if($d20 < 10) $scudo = 0;
        else {
            $scudo = 1;
            $intoccabili[$target] = true; // Salvo i bersagli difesi come intoccabili
        }

        if (!isset($riepilogo[$target])) $riepilogo[$target] = array(); // Inizializzo l'array del pg
        // $target difeso da $striker
        $riepilogo[$target]['difeso'][] = array( // Salvo tutte le difese ricevute
            'esito' => $scudo,
            'pg' => $striker
        );

        // $striker difende $target
        if (!isset($riepilogo[$striker])) $riepilogo[$striker] = array(); // Inizializzo l'array del pg
        $riepilogo[$striker]['difende'][] = array( // Salvo la difesa tentata (una sola al massimo per il turno)
            'esito' => $scudo,
            'pg' => $target
        );
    }

    return array(
        'intoccabili' => $intoccabili,
        'difensori' => $difensori
    );
}

// Elaboro tutte le skill generiche preparatorie agli attacchi successivi, tenendo conto delle difese riuscite e fallite
function elaborateGenerichePre($id_role, $turn, $intoccabili, &$riepilogo) {
    // Prendo tutte le azioni d'attacco del turno
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car IN ('generica') ORDER BY id ASC", 'result');
   
    // Per ogni colpo
    while ($r = gdrcd_query($result, 'fetch')) {
        $striker = $r['striker']; // Castatore
        $pgTag = "<font color='#9a6353'><b>$striker</b></font>";
        $target = $r['target']; // Bersaglio
        $dice = $r['dice']; // Dado lanciato
        $msg = "";

        if (!isset($riepilogo[$striker])) $riepilogo[$striker] = array(); // Inizializzo l'array del pg

        // Devo recuperare il sottotipo della skill. Se il sottotipo è presente, devo elaborare il singolo sottotipo, altrimenti significa che la skill non richiede alcun intervento
        $res = gdrcd_query("SELECT sottotipo, nome FROM abilita WHERE id_abilita = {$r['id_skill']}", 'result');
        $abilitaRow = ($res && gdrcd_query($res, 'num_rows') > 0) ? gdrcd_query($res, 'fetch') : null;
        $sottotipo  = $abilitaRow['sottotipo'] ?? null;
        $skillNome  = trim($abilitaRow['nome'] ?? '') ?: 'Sconosciuta';
        $skillTag   = "<i>$skillNome</i>";

        // $handled distingue "sottotipo riconosciuto" (anche se il suo effetto è
        // volutamente silenzioso, es. danni_dimezzati_nonostante_scudo) da "nessun
        // sottotipo o sottotipo non gestito": solo in quest'ultimo caso mostro l'esito
        // generico riuscito/fallito richiesto per le skill generiche senza meccanica propria.
        $handled = true;
        if ($sottotipo) {
            switch ($sottotipo) {
                case 'usa_creatura': // Indipendentemente dal bersaglio selezionato, il castatore dà vita alla sua creatura
                    if($dice >= 10) {
                        $exists = gdrcd_query("SELECT count(*) as creatura FROM personaggio WHERE nome = 'creatura di $striker'")['creatura'];

                        // Controllo se la creatura è già presente
                        if($exists > 0) $msg .= $pgTag." ha già una creatura in gioco e non può lanciare $skillTag.<br>";
                        else{
                            gdrcd_query("INSERT INTO personaggio (nome, car2, salute) VALUES ('creatura di $striker', 80, 30)");
                            gdrcd_query("INSERT INTO role_session_players (id_role, pg_name, png) VALUES ($id_role, 'creatura di $striker', 1)");
                            $msg .= $pgTag." lancia la skill generica $skillTag che evoca una creatura al suo servizio.<br>";
                        }
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag per evocare una creatura al suo servizio, ma fallisce.<br>";
                break;
                case 'danni_dimezzati_nonostante_scudo': // Se il bersaglio è scudato, subisce comunque metà danno
                    // Tolgo il bersaglio dagli intoccabili, così da poter elaborare l'attacco con danni dimezzati nella fase di elaborazione degli attacchi
                    unset($intoccabili[$target]);
                break;
                case 'creatura_attacca_padrone': // L'attacco con creatura viene rivolto verso colui che lo esegue
                    if($dice >= 10) {
                        gdrcd_query("UPDATE role_fights SET target = '$striker' WHERE id_role = $id_role AND turn = $turn AND car = 'creatura'");
                        $msg .= $pgTag." lancia la skill generica $skillTag che costringe la creatura di $target ad attaccare $target.<br>";
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag che costringe la creatura di $target ad attaccare $target, ma fallisce.<br>";
                break;
                case 'annulla_lancio_bersaglio': // Annullo ogni lancio del bersaglio
                    if($dice >= 10) {
                        gdrcd_query("DELETE FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$target'");
                        $msg .= $pgTag." lancia la skill generica $skillTag che annulla ogni lancio di $target.<br>";
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag che annulla ogni lancio di $target, ma fallisce.<br>";
                break;
                case 'malus_10ps_scudo_30ps_bersaglio_meno30ps': // Non si può fare
                    // Controllo se il bersaglio ha meno di 30 salute
                    // Se si, tolgo 10 salute allo striker e
                break;
                case 'annulla_scudo': // Cancello tutti i record con car = difesa per target
                    if($dice >= 10) {
                        gdrcd_query("DELETE FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car = 'difesa' AND target = '$target'");
                        $msg .= $pgTag." lancia la skill generica $skillTag che annulla ogni scudo di $target per questo turno.<br>";
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag che annulla ogni scudo di $target per questo turno, ma fallisce.<br>";
                break;
                case 'prolunga_effetti_un_turno': // Prolunga gli effetti della skill generica lanciata dal bersaglio
                    if($dice >= 10) {
                        gdrcd_query("INSERT INTO role_fights (id_role, turn, striker, target, car, id_skill, level, dice)
                                    VALUES ($id_role, $turn + 1, '$striker', '$target', 'generica', ".$r['id_skill'].", 1, $dice)");
                        $msg .= $pgTag." lancia la skill generica $skillTag che prolunga gli effetti di una skill generica lanciata da $target anche al turno successivo.<br>";
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag che prolunga gli effetti di una skill generica lanciata da $target anche al turno successivo, ma fallisce.<br>";
                break;
                case 'più_15_punti_salute': // Cura
                    if($dice >= 10) {
                        gdrcd_query("UPDATE personaggio SET salute = salute - 15 WHERE nome = '$striker'");
                        gdrcd_query("UPDATE personaggio SET salute = salute + 15 WHERE nome = '$target'");
                        $msg .= $pgTag." lancia la skill generica $skillTag che sottrae a $striker 15 punti salute e li dona a $target.<br>";
                    } else $msg .= $pgTag." tenta di lanciare la skill generica $skillTag che sottrae a $striker 15 punti salute e li dona a $target, ma fallisce.<br>";
                break;
                default:
                    $handled = false;
                break;
            }
        } else {
            $handled = false;
        }

        // Sottotipo assente/vuoto o non riconosciuto: nessuna meccanica automatica da
        // applicare, ma segnalo comunque nel recap se il lancio è riuscito o fallito
        // (>= 10), cosi' come per le altre generiche.
        if (!$handled) {
            $msg = $dice >= 10
                 ? $pgTag." lancia la skill generica $skillTag con successo.<br>"
                 : $pgTag." tenta di lanciare la skill generica $skillTag, ma fallisce.<br>";
        }

        if ($msg !== '') $riepilogo[$striker]['generica'][] = $msg;
    }

    return array('intoccabili' => $intoccabili); // , 'riepilogo' => $riepilogo
}

// Elaboro tutte le azioni di attacco del turno, tenendo conto delle difese riuscite e fallite
function elaborateAttack($id_role, $turn, $intoccabili, $difensori, &$riepilogo) {
    $defaultDamage = 15;

    // Prendo tutte le azioni d'attacco del turno
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car IN ('destrezza', 'mente', 'potere') ORDER BY id ASC", 'result');
   
    // Per ogni colpo
    while ($r = gdrcd_query($result, 'fetch')) {
        $striker = $r['striker']; // Attaccante
        $target = $r['target']; // Bersaglio
        $targets = explode(',', $target); // Prendo eventuali bersagli e processo le difese

        // Se l'attacco avviene con una skill mentale di tipo "comando" eseguita anche nel turno precedente verso un bersaglio differente
        if(strtolower($r['car']) === 'mente' && scaloIntegritaDoppioComando($id_role, $striker, $turn)) {
            // Livello del pg che ha lanciato l'attacco mentale
            $level = (int)getLevelPg(getTotStatsPg($striker));
            
            // In base al suo livello, prendo l'integrità stabilita da scalare
            $sgRow = gdrcd_query("SELECT integrita FROM gilda_soglie WHERE livello = $level");
            $integrita = $sgRow ? (int)$sgRow['integrita'] : 0;

            $riepilogo[$striker]['subisce'][] = array(
                'esito' => 1, // 1 = perde punti, 0 = respinge l'attacco
                'pg' => $striker,
                'danno' => $integrita,
                'punti_type' => 'integrita', // Bug storico: la chiave era 'punti' invece di
                // 'punti_type', quella letta dal totale danni in elaboratePrint() — di
                // conseguenza il default 'salute' scalava Salute invece di Integrità.
                'msg' => " e perde $integrita punti integrità per aver lanciato nuovamente una mentale di tipo comando"
            );

            // Card esplicativa nel riepilogo turno: senza questa, la perdita di
            // integrità comparirebbe nel totale finale senza alcuna spiegazione visibile
            // (le voci 'subisce' senza un 'attacca' corrispondente non generano card).
            $riepilogo[$striker]['generica'][] = "$striker perde $integrita punti integrità per aver lanciato una mentale di tipo comando in due turni consecutivi.";
        }

        // Elaboro l'attacco verso tutti i bersagli tenendo conto delle difese riuscite e fallite
        elaborateAttackTarget($id_role, $r, $targets, $intoccabili, $difensori, $defaultDamage, $riepilogo, $turn);
    }

    return;
}

// Per ogni azione di attacco del turno, per ogni bersaglio...
function elaborateAttackTarget($id_role, $r, $targets, $intoccabili, $difensori, $defaultDamage, &$riepilogo, $turn) {
    $striker = $r['striker']; // Attaccante
    $dice = $r['dice']; // Dado di attacco
    $critico = ((int)($r['dado_raw'] ?? 0) === 20); // Colpo critico: d20 naturale = 20
    if (!isset($riepilogo[$striker])) $riepilogo[$striker] = array(); // Inizializzo l'array del pg

    // Per ogni bersaglio di questo attaccato
    foreach($targets as $k => $target) {
        $carDifesa = getDefenceCar(strtolower($r['car']), $target); // Recupero le info sulla caratteristica usata per il tiro di difesa
        if ($carDifesa === null) continue; // car d'attacco sconosciuta, non elaborabile
        $damage = 0; // Inizializzo il danno subito dal bersaglio
        $durata = 0;    // Inizializzo la durata (in turni) di eventuali effetti applicati al bersaglio
        $durataMsg = ''; // Messaggio da mostrare nel riepilogo per effetti di durata
        $moltiplicatore = 1; // Inizializzo il moltiplicatore di danno
        $dadoDifesa = 0; // Inizializzo il dado di difesa, che servirà per il calcolo del danno in caso di difese fallite che consentono comunque di lanciare un dado di difesa

        if (!isset($riepilogo[$target])) $riepilogo[$target] = array(); // Inizializzo l'array del pg

        $canRow = gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$target'");
        $can_send = $canRow ? (int)$canRow['can_send'] : 1;

        // Risposta immediata: il bersaglio ha scelto esplicitamente come reagire prima della fine turno.
        // Il filtro su id_fight (l'id di QUESTO attacco, $r['id']) e' quello che rende la ricerca
        // univoca: prima si usava solo lo striker/target in OR, che confondeva la risposta a
        // QUESTO attacco con la risposta di un attacco opposto fra le stesse due persone nello
        // stesso turno (es. un pg attacca un PNG che lo ha attaccato a sua volta prima).
        $idFightAttacco = (int)$r['id'];
        $dadoRisposta = gdrcd_query("SELECT dice FROM role_fights WHERE id_role=$id_role AND turn=$turn AND id_fight=$idFightAttacco AND (striker='$target' OR target='$target') AND car='dado_risposta' LIMIT 1");
        $subisce      = gdrcd_query("SELECT id   FROM role_fights WHERE id_role=$id_role AND turn=$turn AND id_fight=$idFightAttacco AND (striker='$target' OR target='$target') AND car='subisce'       LIMIT 1");

        // Se il bersaglio può lanciare un dado automatico di difesa perché non ha lanciato uno scudo in questo turno e neanche nel precedente
        if($can_send === 1) {
            if(!isset($difensori[$target])) { // Se non ha usato lo scudo in questo turno, significa che deve difendersi con un dado

                // Se damage_percent > 0 (attacchi PNG master), ogni bersaglio riceve quella
                // percentuale del danno calcolato invece della divisione per count($targets).
                $damagePercent = isset($r['damage_percent']) ? (int)$r['damage_percent'] : 0;

                if ($subisce) {
                    // Il bersaglio ha scelto esplicitamente di subire: danno fisso, nessun dado di difesa
                    $damage = $critico ? ($defaultDamage * 2) : $defaultDamage;
                } elseif ($dadoRisposta) {
                    // Il bersaglio ha già tirato il dado in risposta immediata: usa quel risultato
                    $dadoDifesa = (int)$dadoRisposta['dice'];
                    if($dice > $dadoDifesa) {
                        $sgRow = gdrcd_query("SELECT danno FROM gilda_soglie WHERE livello = ".$r['level']);
                        $moltiplicatore = $sgRow ? (float)$sgRow['danno'] : 1.0;
                        $baseDmg = ($dice - $dadoDifesa) * $moltiplicatore;
                        $damage = $damagePercent > 0
                            ? round($baseDmg * ($damagePercent / 100))
                            : round($baseDmg / count($targets));
                        if ($critico && $damage > 0) $damage *= 2;
                        $durataResult = registraDurata($carDifesa['type'], $carDifesa['punti'], $damage, $target, $id_role);
                        $durata = $durataResult['turni'];
                        $durataMsg = $durataResult['msg'];
                    }
                } else {
                    // Nessuna risposta immediata: auto-lancia il dado di difesa
                    $dadoDifesa = lanciaStat($id_role, $striker, $target, true, $carDifesa['nome'], $carDifesa['nome'], $carDifesa['car'], $carDifesa['punti'], 0, 0)['risultato'];
                    if($dice > $dadoDifesa) {
                        $sgRow = gdrcd_query("SELECT danno FROM gilda_soglie WHERE livello = ".$r['level']);
                        $moltiplicatore = $sgRow ? (float)$sgRow['danno'] : 1.0;
                        $baseDmg = ($dice - $dadoDifesa) * $moltiplicatore;
                        $damage = $damagePercent > 0
                            ? round($baseDmg * ($damagePercent / 100))
                            : round($baseDmg / count($targets));
                        if ($critico && $damage > 0) $damage *= 2;
                        $durataResult = registraDurata($carDifesa['type'], $carDifesa['punti'], $damage, $target, $id_role);
                        $durata = $durataResult['turni'];
                        $durataMsg = $durataResult['msg'];
                    }
                }

            } else $damage = isset($intoccabili[$target]) ? 0 : $defaultDamage; // In questo caso il bersaglio ha usato lo scudo su qualcuno o su se stesso
        } else $damage = $defaultDamage; // Se non può lanciare il dado di difesa, subisce il danno di default
    
        // Salvo tutti gli attacchi ricevuti
        $riepilogo[$target]['subisce'][] = array(
            'pg' => $striker,
            'danno' => $damage,
            'punti' => $carDifesa['punti'], // Punti di salute o integrità prima dell'attacco
            'punti_max' => $carDifesa['punti_max'] ?? 100, // Massimo per la barra HP
            'punti_type' => $carDifesa['type'], // Salute o integrità
            'intoccabile' => isset($intoccabili[$target]), // Se è scudato o meno
            'can_send' => $can_send, // Se può lanciare un dado di difesa o meno
            'durata' => $durata,         // Durata di eventuali mentali (in turni)
            'durata_msg' => $durataMsg,  // Messaggio descrittivo dell'effetto di durata
            'scudo_fallito' => isset($difensori[$target]), // Se ha tentato di difendersi con lo scudo ma ha fallito
            'formula' => [
                'dadoAttacco' => $dice,
                'dadoDifesa' => $dadoDifesa,
                'car_attacco' => $r['car'],
                'car_difesa' => $carDifesa['nome'],
                'moltiplicatore' => $moltiplicatore,
                'critico' => $critico
            ]
        );

        // Salvo l'attacco effettuato
        $riepilogo[$striker]['attacca'][] = array(
            'danno' => $damage,
            'pg' => $target,
            'punti' => $carDifesa['punti'], // Punti di salute o integrità prima dell'attacco
            'punti_type' => $carDifesa['type'], // Salute o integrità
            'formula' => [
                'dadoAttacco' => $dice,
                'dadoDifesa' => $dadoDifesa,
                'car_attacco' => $r['car'],
                'car_difesa' => $carDifesa['nome'],
                'moltiplicatore' => $moltiplicatore,
                'critico' => $critico
            ]
        );
    }

    return; // array('riepilogo' => $riepilogo);
}

// Helper: somma i danni di salute e integrità da un array subisce
function sumDanni(array $subisce): array {
    $salute = 0; $integrita = 0;
    foreach ($subisce as $a) {
        if (($a['punti_type'] ?? '') === 'salute')    $salute    += $a['danno'];
        if (($a['punti_type'] ?? '') === 'integrita') $integrita += $a['danno'];
    }
    return ['salute' => $salute, 'integrita' => $integrita];
}

// Elaboro tutte le skill generiche del turno, tenendo conto delle difese riuscite e fallite
function elaborateGenerichePost($id_role, $turn, &$riepilogo) {
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car IN ('generica') ORDER BY id ASC", 'result');

    // Pattern 1: moltiplicatore sul totale danni
    $moltiplicatori = [
        'dimezza_danno_bersaglio_selezionato' => 0.5,
        'danno_doppio'                        => 2.0,
    ];

    // Pattern 2: danno fisso aggiuntivo
    $danniExtra = [
        'più_10_al_danno' => 10,
        'più_5_danno'     => 5,
        'meno_5_danno'    => -5,
    ];

    while ($r = gdrcd_query($result, 'fetch')) {
        $striker   = $r['striker'];
        $target    = $r['target'];
        $dice      = $r['dice'];
        $msg       = '';
        $targetMsg = '';

        if (!isset($riepilogo[$striker])) $riepilogo[$striker] = [];
        if (!isset($riepilogo[$target]))  $riepilogo[$target]  = [];

        $res = gdrcd_query("SELECT sottotipo, nome FROM abilita WHERE id_abilita = {$r['id_skill']} AND sottotipo IS NOT NULL", 'result');
        if (!$res || gdrcd_query($res, 'num_rows') === 0) continue;

        $abilitaRow = gdrcd_query($res, 'fetch');
        $sottotipo  = $abilitaRow['sottotipo'];
        $skillNome  = trim($abilitaRow['nome'] ?? '') ?: 'Sconosciuta';
        $skillTag   = "<i>$skillNome</i>";
        $hasSubisce = isset($riepilogo[$target]['subisce']) && count($riepilogo[$target]['subisce']) > 0;

        if (isset($moltiplicatori[$sottotipo])) {
            // Pattern 1 — moltiplicatore danni totali
            if ($dice >= 10) {
                if ($hasSubisce) {
                    $m   = $moltiplicatori[$sottotipo];
                    $tot = sumDanni($riepilogo[$target]['subisce']);
                    $riepilogo[$target]['totale_salute']    = $tot['salute']    * $m;
                    $riepilogo[$target]['totale_integrita'] = $tot['integrita'] * $m;
                    $label     = $m < 1 ? 'dimezzati' : 'doppi';
                    $msg       .= "<br>$striker lancia a $target la skill generica $skillTag che infligge danni $label. ";
                    $targetMsg .= "<br>$target subisce l'effetto della skill generica $skillTag di $striker e subisce danni $label. ";
                } else $msg .= "<br>$striker tenta di lanciare a $target la skill generica $skillTag sui danni, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
            } else $msg .= "<br>$striker tenta di lanciare a $target la skill generica $skillTag sui danni, ma fallisce. ";
        } elseif (isset($danniExtra[$sottotipo])) {
            // Pattern 2 — danno fisso aggiuntivo
            if ($dice >= 10) {
                if ($hasSubisce) {
                    $extra = $danniExtra[$sottotipo];
                    $label = $extra > 0 ? "$extra danni aggiuntivi" : abs($extra) . " danni in meno";
                    $riepilogo[$target]['subisce'][] = [
                        'pg'         => $striker,
                        'danno'      => $extra,
                        'punti_type' => 'salute',
                        'msg'        => "Subisce l'effetto della skill generica $skillTag di $striker che infligge $label. "
                    ];
                } else $msg .= "<br>$striker tenta di lanciare a $target la skill generica $skillTag, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
            } else $msg .= "<br>$striker tenta di lanciare a $target la skill generica $skillTag, ma fallisce. ";
        } else {
            // Casi speciali
            switch ($sottotipo) {
                case 'danni_dimezzati_nonostante_scudo':
                    $scudo = 0;
                    foreach ($riepilogo[$target]['difeso'] ?? [] as $difesa) {
                        if ($difesa['esito'] === 1) { $scudo = 1; break; }
                    }
                    if ($dice >= 10 && $scudo === 1) {
                        if ($hasSubisce) {
                            $tot = sumDanni($riepilogo[$target]['subisce']);
                            $riepilogo[$target]['totale_salute']    = $tot['salute']    / 2;
                            $riepilogo[$target]['totale_integrita'] = $tot['integrita'] / 2;
                            $msg       .= "<br>$striker lancia a $target la skill generica $skillTag che infligge danni dimezzati nonostante lo scudo. ";
                            $targetMsg .= "<br>$target, nonostante lo scudo, subisce la metà dei danni per effetto della skill generica $skillTag di $striker. ";
                        } else $msg .= "<br>$striker tenta la skill generica $skillTag su $target, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
                    } else $msg .= "<br>$striker tenta di lanciare a $target la skill generica $skillTag che infligge danni dimezzati nonostante lo scudo, ma fallisce. ";
                break;

                case 'sposta_danni_bersaglio_su_castatore':
                    if ($dice >= 10) {
                        if ($hasSubisce) {
                            $tot = sumDanni($riepilogo[$target]['subisce']);
                            $riepilogo[$striker]['subisce'][] = ['pg' => $striker, 'danno' => $tot['salute'],    'punti_type' => 'salute',    'msg' => " e subisce l'effetto della skill generica $skillTag di $striker che sposta i danni (ps) subiti da $target su $striker. "];
                            $riepilogo[$striker]['subisce'][] = ['pg' => $striker, 'danno' => $tot['integrita'], 'punti_type' => 'integrita', 'msg' => " e subisce l'effetto della skill generica $skillTag di $striker che sposta i danni (pi) subiti da $target su $striker. "];
                            $msg .= "<br>$striker lancia a $target la skill generica $skillTag che sposta i danni subiti da $target su $striker. ";
                        } else $msg .= "<br>$striker tenta di spostare i danni di $target su se stesso con la skill generica $skillTag, ma $target non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di spostare i danni di $target su se stesso con la skill generica $skillTag, ma fallisce. ";
                break;

                case 'sposta_danni_castatore_su_bersaglio':
                    $hasStrikerSubisce = isset($riepilogo[$striker]['subisce']) && count($riepilogo[$striker]['subisce']) > 0;
                    if ($dice >= 10) {
                        if ($hasStrikerSubisce) {
                            $tot = sumDanni($riepilogo[$striker]['subisce']);
                            $riepilogo[$target]['subisce'][] = ['pg' => $striker, 'danno' => $tot['salute'],    'punti_type' => 'salute',    'msg' => " e subisce l'effetto della skill generica $skillTag di $striker che sposta i danni (ps) subiti da $striker su $target. "];
                            $riepilogo[$target]['subisce'][] = ['pg' => $striker, 'danno' => $tot['integrita'], 'punti_type' => 'integrita', 'msg' => " e subisce l'effetto della skill generica $skillTag di $striker che sposta i danni (pi) subiti da $striker su $target. "];
                            $msg .= "<br>$striker lancia a $target la skill generica $skillTag che sposta i propri danni su $target. ";
                        } else $msg .= "<br>$striker tenta di spostare i propri danni su $target con la skill generica $skillTag, ma $striker non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di spostare i propri danni su $target con la skill generica $skillTag, ma fallisce. ";
                break;

                case 'converti_danni_bersaglio_in_salute_castatore':
                    if ($dice >= 10) {
                        if ($hasSubisce) {
                            $tot = sumDanni($riepilogo[$target]['subisce']);
                            gdrcd_query("UPDATE personaggio SET salute = salute + {$tot['salute']} WHERE nome = '$striker'");
                            $msg .= "<br>$striker lancia a $target la skill generica $skillTag che converte i danni subiti da $target in punti salute per se stesso. ";
                        } else $msg .= "<br>$striker tenta di convertire i danni di $target in salute con la skill generica $skillTag, ma $target non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di convertire i danni di $target in salute con la skill generica $skillTag, ma fallisce. ";
                break;

                case 'converti_meta_danni_bersaglio_in_salute_castatore':
                    if ($dice >= 10) {
                        if ($hasSubisce) {
                            $tot  = sumDanni($riepilogo[$target]['subisce']);
                            $meta = (int) floor($tot['salute'] / 2);
                            gdrcd_query("UPDATE personaggio SET salute = salute + $meta WHERE nome = '$striker'");
                            $msg .= "<br>$striker lancia a $target la skill generica $skillTag che converte metà dei danni subiti da $target ({$meta} ps) in punti salute per se stesso. ";
                        } else $msg .= "<br>$striker tenta di convertire metà dei danni di $target in salute con la skill generica $skillTag, ma $target non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di convertire metà dei danni di $target in salute con la skill generica $skillTag, ma fallisce. ";
                break;

                case 'meno_50_danni_tutti_con_durata':
                    // TODO
                break;
            }
        }

        // Stesso motivo del filtro in elaborateGenerichePre(): sottotipi non gestiti da
        // nessun ramo sopra (es. 'annulla_lancio_bersaglio', gestito solo in Pre) o skill
        // senza sottotipo lasciano $msg/$targetMsg vuoti, producendo card fantasma.
        if ($msg !== '')       $riepilogo[$striker]['generica'][] = $msg;
        if ($targetMsg !== '') $riepilogo[$target]['generica'][]  = $targetMsg;
    }

    return; // array('riepilogo' => $riepilogo);
}

/**
 * Elabora i poteri speciali del turno.
 *
 * Per ogni azione car='speciale' del turno corrente:
 *  - Se dado >= 10: inserisce i bonus in role_skill_buffs (INSERT IGNORE: non sovrascrive se già attivo per lo stesso pg + abilità)
 *  - Aggiunge la card di risultato al riepilogo (riuscito/fallito + bonus attivati)
 *
 * I bonus restano attivi fino a fine role e vengono sommati a ogni tiro in lanciaStat().
 * La tabella viene svuotata da endRoleSession() al termine della role.
 */
function elaboratePotereSpeciale($id_role, $turn, &$riepilogo) {
    // Mappa id_gilda → colonne bonus da inserire in role_skill_buffs
    $guild_buffs = [
        1 => ['bonus_potere' => 5],                                                         // Adamanti
        2 => ['bonus_potere' => 5],                                                         // Demoni
        3 => ['bonus_potere' => 2, 'bonus_destrezza' => 1, 'bonus_mente' => 1, 'bonus_tempra' => 1], // Elementali
        4 => ['bonus_destrezza' => 5],                                                      // Beast
        5 => ['bonus_destrezza' => 3, 'bonus_tempra' => 2],                                 // Celestiali
        6 => ['bonus_mente' => 5],                                                          // Fiori
        7 => ['bonus_mente' => 5],                                                          // Lancaster
    ];

    $all_cols = ['bonus_destrezza', 'bonus_mente', 'bonus_tempra', 'bonus_potere'];

    $result = gdrcd_query(
        "SELECT rf.id, rf.striker, rf.dice, rf.id_skill, a.id_gilda
         FROM role_fights rf
         JOIN abilita a ON a.id_abilita = rf.id_skill
         WHERE rf.id_role = $id_role AND rf.turn = $turn AND rf.car = 'speciale'
         ORDER BY rf.id ASC",
        'result'
    );
    if (!$result) return;

    while ($r = gdrcd_query($result, 'fetch')) {
        $striker  = $r['striker'];
        $dice     = (int)$r['dice'];
        $id_gilda = (int)$r['id_gilda'];
        $id_skill = (int)$r['id_skill'];

        if (!isset($riepilogo[$striker])) $riepilogo[$striker] = [];

        if ($dice < 10) {
            $riepilogo[$striker]['potere_speciale'][] = ['esito' => false, 'dice' => $dice];
            continue;
        }

        $partial_bonuses = $guild_buffs[$id_gilda] ?? null;
        if (!$partial_bonuses) {
            // Gilda sconosciuta — non applicare alcun buff ma segnalare nel riepilogo
            $riepilogo[$striker]['potere_speciale'][] = ['esito' => false, 'dice' => $dice];
            continue;
        }

        // Normalizza: assicura che tutte e 4 le colonne siano presenti (0 se non specificate)
        $col_vals = [];
        foreach ($all_cols as $c) $col_vals[$c] = $partial_bonuses[$c] ?? 0;

        $striker_f = gdrcd_filter('in', $striker);

        // INSERT IGNORE: se il pg ha già attivato la stessa abilità in questa role, non sovrascrive
        gdrcd_query(
            "INSERT IGNORE INTO role_skill_buffs (id_role, pg_name, id_abilita, bonus_destrezza, bonus_mente, bonus_tempra, bonus_potere)
             VALUES ($id_role, '$striker_f', $id_skill, {$col_vals['bonus_destrezza']}, {$col_vals['bonus_mente']}, {$col_vals['bonus_tempra']}, {$col_vals['bonus_potere']})"
        );

        $riepilogo[$striker]['potere_speciale'][] = [
            'esito'   => true,
            'dice'    => $dice,
            'bonuses' => $col_vals,
        ];
    }
    gdrcd_query($result, 'free');
}

/*************************  FINE  ELABORAZIONE TURNO */

// In base alla caratteristica di attacco, torno quella di difesa
function getDefenceCar($attack_car, $target) {
    $result = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$target'", 'result');

    if(gdrcd_query($result, 'num_rows') > 0) $pg = gdrcd_query($result, 'fetch');
    else {
        $pg['car2'] = 0; // destrezza
        $pg['car6'] = 0; // tempra
    }
    

    // $attack_car arriva minuscolo da alcuni punti del codice ma nel caso normale
    // (Attacco/Mentale) e' il nome configurato in $PARAMETERS['names']['stats']
    // (es. "Potere", "Destrezza"), quindi confrontato senza normalizzare non
    // corrispondeva mai: la difesa risultava sempre null (parentesi vuote nel
    // messaggio, bonus -1 spurio da "null - 1").
    switch(strtolower($attack_car)) {
        /* In caso l'attacco sia stato fatto con forza, destrezza o potere, la difesa avviene con destrezza,
        prelevo i punti del bersaglio su destrezza e i suoi punti salute */
        case 'forza':
        case 'destrezza':
        case 'potere': return array('nome' => 'destrezza', 'car' => $pg['car2'], 'punti' => $pg['salute'], 'punti_max' => $pg['salute_max'] ?? 100, 'type' => 'salute');
        // Se l'attacco è con Mente, ritorno Tempra, i punti su Tempra e i punti integrità del bersaglio
        case 'mente': return array('nome' => 'tempra', 'car' => $pg['car6'], 'punti' => $pg['integrita'], 'punti_max' => $pg['integrita_max'] ?? 100, 'type' => 'integrità');
        default: return null;
    }
}

// Impedisco o consento al pg di lanciare nel prossimo turno
function setCanSend($id_role) {
    $turn = getTurn($id_role);
    $pgsGiocanti = getRolePgs($id_role, true);

    foreach($pgsGiocanti as $pg) {
        // Conta solo le azioni attive del turno (esclude dado_risposta e subisce, che sono reazioni passive a un attacco subìto)
        $result = gdrcd_query(
            "SELECT 1 FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pg' AND car NOT IN ('dado_risposta', 'subisce')",
            'result'
        );
        $lanci = $result ? gdrcd_query($result, 'num_rows') : 0;

        // Verifica se il pg ha usato lo scudo (car='difesa') in questo turno
        $shRes = gdrcd_query(
            "SELECT 1 FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pg' AND car = 'difesa' LIMIT 1",
            'result'
        );
        $hasShield = $shRes && gdrcd_query($shRes, 'num_rows') > 0;

        // can_send = 0 solo se ha usato lo scudo E ha anche eseguito un'altra azione nello stesso turno.
        // Lo scudo da solo non penalizza il turno successivo.
        $can = ($hasShield && $lanci > 1) ? 0 : 1;
        gdrcd_query("UPDATE role_session_players SET can_send = $can WHERE id_role = $id_role AND pg_name = '$pg'", 'result');
    }
}

// Recupera i personaggi di una role
function getRolePgs($id_role, $active = false, $excludePng = false) {
    $users = [];
    $activePgs = $active ? "AND `end` IS NULL" : ''; // Se voglio solo i pg attivi, aggiungo al filtro "end is null" per escludere quelli usciti dalla role
    $pngFilter = $excludePng ? "AND png = 0" : ''; // Esclude i png: solo pg reali
    $result = gdrcd_query("SELECT DISTINCT pg_name FROM role_session_players WHERE id_role = $id_role $activePgs $pngFilter", 'result');

    while($row = gdrcd_query($result, 'fetch')) $users[] = $row['pg_name'];

    return $users;
}

// Registra un'azione di combattimento nella role e restituisce l'ID inserito.
// $damage_percent > 0: ogni bersaglio subisce X% del danno calcolato (attacchi PNG master).
// $id_fight: per le risposte (dado_risposta/subisce/difesa) collega la riga all'attacco
// originale a cui rispondono, cosi' elaborateAttackTarget() non deve piu' indovinare la
// riga giusta confrontando solo striker/target (ambiguo quando due pg si attaccano a
// vicenda nello stesso turno).
function fight($id_role, $striker, $target, $id_skill, $level, $car, $dice, $recap='', $damage_percent=0, $dado_raw=0, $id_fight=null) {
    $turn = getTurn($id_role);
    $id_fight_sql = $id_fight === null ? 'NULL' : (int)$id_fight;
    $query = "INSERT INTO role_fights (id_role, turn, striker, `target`, car, id_skill, level, dice, result, damage_percent, dado_raw, id_fight)
            VALUES ($id_role, $turn, '$striker', '$target', '$car', $id_skill, $level, $dice, '$recap', $damage_percent, $dado_raw, $id_fight_sql)";
    gdrcd_query($query);
    return (int)gdrcd_query("SELECT LAST_INSERT_ID() as id")['id'];
}

/**
 * Emette via socket l'evento 'combat:attack_incoming' verso tutti i pg nella stanza.
 * Ogni bersaglio riceve le opzioni disponibili (dado/scudo/subisce) calcolate server-side:
 * - scudo: solo se il pg ha la skill difensiva e non l'ha già usata nel turno corrente.
 * - dado/subisce: sempre disponibili se can_send = 1.
 */
function notifyAttackIncoming($id_role, $luogo, $striker, array $targets, $car, $dice, $id_fight, $turn) {
    $targetsInfo = [];
    foreach ($targets as $t) {
        $t = trim($t);
        if (!$t) continue;

        $canSend = (int)(gdrcd_query(
            "SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$t'"
        )['can_send'] ?? 1);

        $choices = ['dado'];

        if ($canSend === 1) {
            $hasShield = (int)gdrcd_query(
                "SELECT COUNT(*) as n FROM clgpersonaggioabilita cpa
                 JOIN abilita a ON cpa.id_abilita = a.id_abilita
                 WHERE cpa.nome = '$t' AND a.tipo = 'Difensiva'"
            )['n'];
            $alreadyShielded = checkMultipleLounch($id_role, $t, ["'difesa'"], $turn);

            if ($hasShield > 0 && !$alreadyShielded) $choices[] = 'scudo';
        }
        $choices[] = 'subisce';

        $targetsInfo[$t] = ['can_send' => $canSend, 'choices' => $choices];
    }

    notifySocketServer('combat:attack_incoming', 'chat:' . (int)$luogo, [
        'id_fight' => (int)$id_fight,
        'attacker' => $striker,
        'targets'  => $targetsInfo,
        'car'      => $car,
        'dice'     => (int)$dice,
        'turn'     => (int)$turn,
    ]);
}

/**
 * Verifica se esistono attacchi nel turno corrente a cui i bersagli non hanno ancora risposto.
 * Un attacco è "in sospeso" se il bersaglio non ha registrato né dado_risposta,
 * né difesa (scudo), né subisce per quell'attaccante.
 * Usato da checkTurnCanClose per bloccare la chiusura automatica del turno
 * finché il bersaglio non ha avuto modo di scegliere come difendersi.
 */
function hasPendingUnrespondedAttacks($id_role, $turn) {
    $attacks = gdrcd_query(
        "SELECT striker, target FROM role_fights
         WHERE id_role = $id_role AND turn = $turn AND car IN ('destrezza', 'mente', 'potere')",
        'result'
    );
    if (!$attacks || gdrcd_query($attacks, 'num_rows') === 0) return false;

    while ($row = gdrcd_query($attacks, 'fetch')) {
        $attacker = $row['striker'];
        $targets  = array_map('trim', explode(',', $row['target']));
        foreach ($targets as $target) {
            if (!$target) continue;
            // Risposta diretta (dado_risposta o subisce) verso l'attaccante specifico
            $directResponse = gdrcd_query(
                "SELECT id FROM role_fights
                 WHERE id_role = $id_role AND turn = $turn AND striker = '$target'
                 AND target = '$attacker' AND car IN ('dado_risposta', 'subisce') LIMIT 1",
                'result'
            );
            // Scudo (difesa) — copre tutti gli attacchi del turno per quel pg
            $shield = gdrcd_query(
                "SELECT id FROM role_fights
                 WHERE id_role = $id_role AND turn = $turn AND striker = '$target' AND car = 'difesa' LIMIT 1",
                'result'
            );
            if (
                (!$directResponse || gdrcd_query($directResponse, 'num_rows') === 0) &&
                (!$shield         || gdrcd_query($shield,          'num_rows') === 0)
            ) return true; // Questo bersaglio non ha ancora risposto
        }
    }
    return false;
}

/**
 * Chiude il turno quando tutti i pg attivi hanno close_turn = 1
 * e non ci sono attacchi in sospeso senza risposta.
 *
 * Se un pg ha chiuso il turno ma riceve un attacco dopo la chiusura,
 * il turno rimane aperto finché il bersaglio non risponde (dado/scudo/subisce).
 * Questo garantisce che il bersaglio possa sempre scegliere come difendersi.
 *
 * Viene chiamata da closePgTurn, checkTurnEnd e risposta_immediata.
 */
function checkTurnCanClose($id_role, $location) {
    // Prima condizione: tutti i pg attivi devono aver chiuso il turno
    $stillOpen = gdrcd_query(
        "SELECT 1 FROM role_session_players WHERE id_role = $id_role AND close_turn = 0 AND `end` IS NULL LIMIT 1",
        'result'
    );
    if (!$stillOpen || gdrcd_query($stillOpen, 'num_rows') > 0) return;

    // Seconda condizione: nessun attacco deve essere rimasto senza risposta da un pg attivo (png esclusi)
    $turn = getTurn($id_role);
    $pendingAttacks = gdrcd_query("
        SELECT 1
        FROM role_fights rf
        JOIN role_session_players rsp
            ON rsp.id_role = rf.id_role
            AND FIND_IN_SET(rsp.pg_name, rf.target) > 0
            AND rsp.end IS NULL
            AND rsp.png = 0
        WHERE rf.id_role = $id_role
          AND rf.turn    = $turn
          AND rf.car     IN ('destrezza', 'mente', 'potere')
          AND NOT EXISTS (
              SELECT 1 FROM role_fights r2
              WHERE r2.id_role = rf.id_role
                AND r2.turn    = rf.turn
                AND r2.striker = rf.target
                AND (
                  (r2.car IN ('dado_risposta', 'subisce') AND r2.target = rf.striker)
                  OR r2.car = 'difesa'
                )
          )
        LIMIT 1
    ", 'result');
    if ($pendingAttacks && gdrcd_query($pendingAttacks, 'num_rows') > 0) return;

    closeTurn($id_role, $location);
}

// Controlla che non siano stati inviati più lanci in un singolo turno
function checkMultipleLounch($id_role, $striker, $type, $turn) {
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$striker' AND car IN (".implode(',', $type).")", 'result');

    return ($result && gdrcd_query($result, 'num_rows') > 0);
}

// Verifica se il pg ha già effettuato almeno un lancio (attacco o difesa) nel turno corrente
function hasTurnLaunch($id_role, $pgName, $turn) {
    $result = gdrcd_query("SELECT 1 FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pgName' AND car NOT IN ('dado_risposta', 'subisce') LIMIT 1", 'result');
    return $result && gdrcd_query($result, 'num_rows') > 0;
}

// Verifica se il pg ha lanciato uno scudo (car='difesa') nel turno corrente
function hasShieldLaunch($id_role, $pgName, $turn) {
    $result = gdrcd_query("SELECT 1 FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pgName' AND car = 'difesa' LIMIT 1", 'result');
    return $result && gdrcd_query($result, 'num_rows') > 0;
}

// Se il pg ha già inviato l'azione testuale, chiude il suo turno automaticamente dopo un lancio
function checkAutoCloseAfterLaunch($id_role, $pgName, $location) {
    $row = gdrcd_query("SELECT sent FROM role_session_players WHERE id_role = $id_role AND pg_name = '$pgName' AND `end` IS NULL");
    if ($row && (int)$row['sent'] === 1) closePgTurn($id_role, $pgName, $location);
}

function getTurn($id_role) {
    return gdrcd_query("SELECT turn FROM role_sessions WHERE id_role = $id_role")['turn'];
}

// Quando lancio una skill mentale di comando, controllo che non sia stata lanciata un'altra mentale di comando sullo stesso pg nel turno precedente
function checkMentaleComando($id_role, $striker, $bersaglio, $turn) {
    $query = "SELECT role_fights.* FROM role_fights
            INNER JOIN abilita ON role_fights.id_skill = abilita.id_abilita
            WHERE role_fights.id_role = $id_role AND role_fights.turn = ($turn-1)
            AND role_fights.striker = '$striker' AND role_fights.target = '$bersaglio'
            AND role_fights.car = 'mente'
            AND abilita.sottotipo = 'comando'";
    $result = gdrcd_query($query, 'result');

    return ($result && gdrcd_query($result, 'num_rows') > 0);
}

// Serve per capire se l'attaccante ha già lanciato una skill mentale di comando nel turno precedente, così da scalare l'integrità al pg che ha lanciato l'attacco mentale
function scaloIntegritaDoppioComando($id_role, $striker, $turn) {
    $query = "SELECT role_fights.* FROM role_fights
            INNER JOIN abilita ON role_fights.id_skill = abilita.id_abilita
            WHERE role_fights.id_role = $id_role AND role_fights.turn = ($turn-1)
            AND role_fights.striker = '$striker'
            AND role_fights.car = 'mente'
            AND abilita.sottotipo = 'comando'";
    $result = gdrcd_query($query, 'result');

    return ($result && gdrcd_query($result, 'num_rows') > 0);
}

// Funzione per scalare i punti del pg, che siano salute o integrità
function scaloPunti($pg, $damage, $type) {
    gdrcd_query("UPDATE personaggio SET $type = ($type - $damage) WHERE nome = '$pg'");
}

// Se l'integrità scende sotto una certa soglia, registro la durata della skill in base al danno provocato
function registraDurata($type, $punti, $danno, $pg, $id_role) {
    $salute = ($punti - $danno);
    $turni = 0;
    $msg = '';

    // Se parliamo di integrità ed è compresa tra 60 e 20, registro la durata in base al danno provocato
    if($type === 'integrità') {
        if ($salute < 20) return ['turni' => 0, 'msg' => "L'$type di $pg è troppo bassa per subire gli effetti della skill."];
        if ($salute < 60) {
            // Calcolo i turni in base al danno provocato
            if      ($danno >= 50) $turni = 12;
            elseif  ($danno >= 40) $turni = 8;
            elseif  ($danno >= 30) $turni = 4;
            elseif  ($danno >= 20) $turni = 2;
            elseif  ($danno >= 1)  $turni = 1;

            // Elimino eventuali durate ancora attive per evitare sovrapposizioni
            gdrcd_query("DELETE FROM role_durations WHERE pg_name = '$pg'");

            // Registro la durata della skill in base al danno provocato
            gdrcd_query("INSERT INTO role_durations (id_role, pg_name, duration, current_turn, `type`) VALUES ($id_role, '$pg', $turni, 0, '$type')");

            $msg = "A causa del danno subito $pg perderà 5 punti $type per i prossimi $turni turni.";
        }
    }

    return ['turni' => $turni, 'msg' => $msg];
}

// Controllo se il pg è sottoposto a una durata e, in caso affermativo, scalare i punti per la durata della skill, aggiornare i turni e cancellare la durata al termine
function checkSkillEffect($pg, $location) {
    $damage = 5; // Punti da scalare

    // Controllo se il pg deve essere sottoposto allo scalo dei punti per skill di durata
    $checkPg = gdrcd_query("SELECT * FROM role_durations WHERE pg_name = '$pg'", 'result');
    if ($checkPg && gdrcd_query($checkPg, 'num_rows') > 0) {
        $duration = gdrcd_query($checkPg, 'fetch');
        $type = $duration['type'];

        // Se la salute del pg è sotto i 20, cancello ogni effetto di durata ancora attivo, altrimenti continuo a scalare i punti
        $salute = gdrcd_query("SELECT $type FROM personaggio WHERE nome = '$pg'")[$type];
        if ($salute < 20) {
            gdrcd_query("DELETE FROM role_durations WHERE pg_name = '$pg'");
            chatInsertMessage($location, 'System', NULL, "Essendo sceso sotto i 20 punti $type, $pg smette di subire gli effetti della skill in corso", 'N');
        } elseif ($duration['current_turn'] < $duration['duration']) {
            // Se la durata è ancora in corso, scalare i punti al pg e aggiornare il turno corrente
            scaloPunti($pg, $damage, $type);
            gdrcd_query("UPDATE role_durations SET current_turn = (current_turn + 1) WHERE pg_name = '$pg'");
            chatInsertMessage($location, 'System', NULL, "$pg subisce gli effetti della skill in corso e perde $damage punti $type", 'N');
        } else {
            // Scalo gli effetti per l'ultima volta, poi cancello il record dal db
            scaloPunti($pg, $damage, $type);
            gdrcd_query("DELETE FROM role_durations WHERE pg_name = '$pg'");
            chatInsertMessage($location, 'System', NULL, "$pg subisce gli effetti della skill in corso e perde $damage punti $type. Questo è l'ultimo turno, gli effetti della skill sono svaniti", 'N');
        }
    }
}

function killPng($pg) {
    gdrcd_query("DELETE FROM personaggio WHERE nome = '$pg'");
    gdrcd_query("DELETE FROM role_session_players WHERE pg_name = '$pg'");
}
?>