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
    
    gdrcd_query("UPDATE personaggio SET esperienza = esperienza + 1, esperienza_r = esperienza_r + 1, shin = shin + 2, last_date_exp = NOW() WHERE nome = '" . $session['login'] . "' LIMIT 1");
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
        'risultato' => $numtot_finale
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
function addPgToRole($id_role, $pg_name, $location, $png = 0) {
    // can_send = 1 garantisce che il pg possa vedere dado/scudo sin dal primo turno
    gdrcd_query("INSERT INTO role_session_players (id_role, pg_name, png, can_send) VALUES ($id_role, '$pg_name', $png, 1)");
    chatInsertMessage($location, 'System', $pg_name, " si è ha aggiunto alla role", 'N');
}

// Controlla se il personaggio ha role attive non congelate
function pgIsInRole($userName = '', $location = null, $active = true) {
    $user_filter = $userName != '' ? "AND role_session_players.pg_name = '$userName' " : ''; // Cerco lo specifico pg in role
    $location_filter = $location ? "AND role_sessions.location = $location" : ''; // Cerco nella role della chat
    $active_filter = $active ? "AND role_session_players.end IS NULL " : ''; // Cerco pg che non sono usciti dalla role (di default)
    
    // Seleziono tutti i pg in role
    $result = gdrcd_query("SELECT role_session_players.* FROM role_session_players 
                            INNER JOIN role_sessions ON role_session_players.id_role = role_sessions.id_role 
                            WHERE role_sessions.end IS NULL 
                            AND role_sessions.freezed IS NULL  
                            $user_filter
                            $location_filter
                            $active_filter", 'result');

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
    gdrcd_query("UPDATE role_sessions SET `end` = NOW() WHERE `location` = $location");

    chatInsertMessage($location, 'System', null, 'Role conclusa!', 'N');
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
        $last_id = (int)gdrcd_query("SELECT MAX(id) AS last_id FROM chat")['last_id'];

        while ($pg = gdrcd_query($pgs, 'fetch')) {
            $pgName = $pg['pg_name'];

            if (hasTurnLaunch($id_role, $pgName, $turn)) {
                // Ha già un lancio nel turno: chiusura automatica senza chiedere
                gdrcd_query("UPDATE role_session_players SET close_turn = 1 WHERE id_role = $id_role AND pg_name = '$pgName' AND `end` IS NULL");
            } else {
                // Nessun lancio: chiede conferma via sussurro
                $last_id++;
                $msg = '<button onclick="closePgTurn('.$id_role.', '.$last_id.', `'.$pgName.'`);">Chiudi il turno</button>';
                chatInsertMessage($location, 'System', $pgName, $msg, 'Q');
            }
        }

        // Se tutti sono stati auto-chiusi (nessun bottone necessario), chiude il turno subito (solo se nessun attacco è in sospeso)
        $stillOpen = gdrcd_query("SELECT id FROM role_session_players WHERE id_role = $id_role AND close_turn = 0 AND `end` IS NULL LIMIT 1", 'result');
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
    gdrcd_query("UPDATE role_sessions SET turn = (turn + 1) WHERE id_role = $id_role"); // Passo al turno successivo
    gdrcd_query("UPDATE role_session_players SET `sent` = 0, close_turn = 0 WHERE id_role = $id_role"); // Riporto tutti i pg a sent = 0 e riapro il turno per tutti
    chatInsertMessage($location, 'System', NULL, $msgElaboration, 'N');
    chatInsertMessage($location, 'System', NULL, 'Turno chiuso! Iniziate il turno successivo...', 'N');
}

/*************************  ELABORAZIONE TURNO */
function elaboratePrint($riepilogo) {
    $msg = "";

    foreach ($riepilogo as $pg => $dati) {
        $pgTag = "<font color='#9a6353'><b>$pg</b></font>";

        /************** DIFESA - Stampo eventuali difese riuscite o fallite **********/

        // Difeso (può subire molteplici difese da diversi difensori)
        if (isset($dati['difeso'])) {
            $msg .= "$pgTag tenta di proteggere ";

            foreach ($dati['difeso'] as $k => $difeso) {
                $msg .= ($k > 0) ? " e anche da " : ""; // E anche da...
                $msg .= $difeso['pg'] === $pg ? "se stesso" : $difeso['pg']; // Difensore

                // Se la difesa ha successo
                if($difeso['esito'] === 1) $msg .= " con successo, al netto di eventuali skill generiche.<br>";
                else $msg .= " senza successo<br>";
            }
        }

        // Difende (uno solo, non può difendere più volte in un turno)
        if (isset($dati['difende'][0]) && $dati['difende'][0]['pg'] !== $pg) {
            $difende = $dati['difende'][0];
            $msg .= "$pgTag difende {$difende['pg']}";
            $msg .= $difende['esito'] === 1 ? " con successo.<br>" : " senza successo.<br>";
        }

        /************** GENERICHE - Stampo eventuali generiche attive ****************/
        if (isset($dati['generica']) && is_array($dati['generica'])) {
            foreach ($dati['generica'] as $generica) $msg .= "$generica<br>";
        }

        /******************************** ATTACCO ************************************/
        $ps = 0;
        $pi = 0;

        if (isset($dati['attacca'])) {
            foreach ($dati['attacca'] as $attacco) {
                $punti_type = $attacco['punti_type'] ?? 'salute';
                $danno = $attacco['danno'];
                $msg .= "$pgTag attacca ".($attacco['pg'] === $pg ? "se stesso" : $attacco['pg']);

                if($attacco['danno'] > 0) $msg .= " con successo, togliendogli $danno punti $punti_type. <br>";
                else $msg .= " ma va a vuoto.<br>";
            }
        }

        // Subisce (può subire molteplici danni da diversi attaccanti)
        if (isset($dati['subisce'])) {
            foreach ($dati['subisce'] as $subisce) {
                $danno = $subisce['danno'] ?? 0;
                $punti = $subisce['punti'] ?? 0;
                $punti_post = $punti - $danno;
                $punti_type = $subisce['punti_type'] ?? 'salute';

                // Sommo i danni per avere i totali
                if ($subisce['punti_type'] === 'salute') $ps += $subisce['danno'];
                elseif ($subisce['punti_type'] === 'integrita') $pi += $subisce['danno'];

                // Se è già settato un messaggio specifico, uso quello
                if (isset($subisce['msg'])) {
                    $msg .= $subisce['msg'] . "<br>";
                    continue;
                }

                // Verifico se è stato scudato
                if (isset($subisce['intoccabile']) && $subisce['intoccabile']) {
                    $msg .= "$pgTag subisce un attacco da " . ($subisce['pg'] === $pg ? "se stesso" : $subisce['pg']) . " ma lo scudo lo protegge e non riporta alcun danno.<br>";
                    continue;
                }

                // Non può lanciare a causa dello scudo lanciato nel turno precedente, quindi perde i punti di default
                if (isset($subisce['can_send']) && $subisce['can_send'] === 0) {
                    $msg .= "$pgTag non può difendere perché ha già tirato un'abilità', perciò perde $danno punti $punti_type,";
                    $msg .= " passando da $punti a ".($punti - $danno)."<br>";
                    continue;
                }

                // Se ha lanciato lo scudo e ha fallito, subisce il danno di default (perché non può lanciare il dado di difesa)
                if (isset($subisce['scudo_fallito']) && $subisce['scudo_fallito']) {
                    $msg .= "$pgTag ha utilizzato uno scudo e non riesce a difendersi, perdendo $danno punti $punti_type,";
                    $msg .= " passando da $punti a ".($punti - $danno)."<br>";
                    continue;
                }

                if(isset($subisce['formula'])) {
                    $formula    = $subisce['formula'];
                    $formulaStr = "<c><b>({$formula['dadoAttacco']} {$formula['car_attacco']} - {$formula['dadoDifesa']} {$formula['car_difesa']}) * {$formula['moltiplicatore']}</b></c>";

                    // Se la difesa supera l'attacco, significa che $pg respinge l'attacco e non subisce danni
                    if ($formula['dadoDifesa'] >= $formula['dadoAttacco']) {
                        $msg .= "$pgTag respinge l'attacco di {$subisce['pg']} $formulaStr<br>";
                    } else {
                        $msg .= "$pgTag perde $danno punti $punti_type $formulaStr";
                        $msg .= " passando da $punti punti $punti_type a ".($punti_post <= 0 ? "0 <strong>(è svampato signò!)</strong><br>" : "$punti_post $punti_type<br>");

                        // Se il bersaglio subisce danni sull'integrità e scende sotto un tot, subisce la durata
                        if (is_numeric($subisce['durata']) && $subisce['durata'] > 0) $msg .= " Inoltre, gli effetti della skill dureranno per {$subisce['durata']} turni.<br>";
                        else $msg .= $subisce['durata'];
                    }
                }
            }
        }

        /************** CALCOLO DANNI *******************************************/
        // Se è presente $pg['totale_salute'], non devo calcolare il danno totale, altrimenti lo calcolo sommando tutti i danni subiti
        if ($ps > 0 || $pi > 0 || isset($dati['totale_salute'])) {
            $msg .= "$pgTag in questo turno perde in totale ";
            $msg .= isset($dati['totale_salute'])
                    ? $dati['totale_salute'] . " punti salute e " . $dati['totale_integrita'] . " punti integrità<br>"
                    : $ps . " punti salute e " . $pi . " punti integrità<br>";
        }

        /****************************** SCALO PUNTI  **************************************/
        scaloPunti($pg, $ps, 'salute'); // Scalo i punti al bersaglio
        scaloPunti($pg, $pi, 'integrita'); // Scalo i punti al bersaglio

        // Se è un png e la salute scende sotto zero, lo elimino
        if ($ps <= 0) {
            $isPng = gdrcd_query("SELECT png FROM role_session_players WHERE pg_name = '$pg'")['png'] ?? 0;

            if ($isPng == 1) {
                $msg .= "$pgTag viene eliminato dallo scontro.<br>";
                killPng($pg); // Elimino il png
            }
        }

        $msg .= "<hr>"; // Separatore tra i bersagli
    }

    return $msg;
}

// Elabora i dati sul combattimento nel singolo turno
function elaborateTurn($id_role) {
    $turn = getTurn($id_role);
    $esito = "<b><u>Risultati turno $turn:</u></b><br>";
    $riepilogo = array();
    
    /****************************** ELABORA LE DIFESE  **************************************/
    $elaborateDefence = elaborateDefence($id_role, $turn, $riepilogo);
    $intoccabili = $elaborateDefence['intoccabili'];
    $difensori = $elaborateDefence['difensori'];

    /****************************** GENERICHE PRE  **************************************/
    $elaborateGenerichePre = elaborateGenerichePre($id_role, $turn, $intoccabili, $riepilogo);
    $intoccabili = $elaborateGenerichePre['intoccabili']; // Aggiorno gli intoccabili con eventuali protezioni generiche riuscite
    
    /****************************** ELABORA GLI ATTACCHI  **************************************/
    elaborateAttack($id_role, $turn, $intoccabili, $difensori, $riepilogo);
    
    /****************************** GENERICHE POST  **************************************/
    // Elaboro eventuali skill generiche che agiscono sul danno (calcolato dopo l'attacco)
    elaborateGenerichePost($id_role, $turn, $riepilogo);

    /****************************** STAMPA RIEPILOGO e TOGLIE I PUNTI  **************************************/
    $msg = elaboratePrint($riepilogo);
    // Manca la creazione della creatura quando lancio le skill generiche di un certo tipo
    // Mi manca da calcolare la durata
    // Mi manca la gestione dei turni di durata di una generica (in base al d20 delle generiche)

    if (empty($riepilogo)) $msg = "Nessuno scontro in questo turno.";

    return $esito.$msg;
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
        $res = gdrcd_query("SELECT sottotipo FROM abilita WHERE id_abilita = {$r['id_skill']} AND sottotipo IS NOT NULL", 'result');

        if ($res && gdrcd_query($res, 'num_rows') > 0) {
            $sottotipo = gdrcd_query($res, 'fetch')['sottotipo'];

            switch ($sottotipo) {
                case 'usa_creatura': // Indipendentemente dal bersaglio selezionato, il castatore dà vita alla sua creatura
                    if($dice >= 10) {
                        $exists = gdrcd_query("SELECT count(*) as creatura FROM personaggio WHERE nome = 'creatura di $striker'")['creatura'];
                        
                        // Controllo se la creatura è già presente
                        if($exists > 0) $msg .= $pgTag." ha già una creatura in gioco.<br>";
                        else{
                            gdrcd_query("INSERT INTO personaggio (nome, car2, salute) VALUES ('creatura di $striker', 80, 30)");
                            gdrcd_query("INSERT INTO role_session_players (id_role, pg_name, png) VALUES ($id_role, 'creatura di $striker', 1)");
                            $msg .= $pgTag." lancia una skill generica che evoca una creatura al suo servizio.<br>";
                        }
                    } else $msg .= $pgTag." tenta di evocare una creatura al suo servizio, ma fallisce.<br>";
                break;
                case 'danni_dimezzati_nonostante_scudo': // Se il bersaglio è scudato, subisce comunque metà danno
                    // Tolgo il bersaglio dagli intoccabili, così da poter elaborare l'attacco con danni dimezzati nella fase di elaborazione degli attacchi
                    unset($intoccabili[$target]);
                break;
                case 'creatura_attacca_padrone': // L'attacco con creatura viene rivolto verso colui che lo esegue
                    if($dice >= 10) {
                        gdrcd_query("UPDATE role_fights SET target = '$striker' WHERE id_role = $id_role AND turn = $turn AND car = 'creatura'");
                        $msg .= $pgTag." lancia una skill generica che costringe la creatura di $target ad attaccare $target.<br>";
                    } else $msg .= $pgTag." tenta di lanciare una skill generica che costringe la creatura di $target ad attaccare $target, ma fallisce.<br>";
                break;
                case 'annulla_lancio_bersaglio': // Annullo ogni lancio del bersaglio
                    if($dice >= 10) {
                        gdrcd_query("DELETE FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$target'");
                        $msg .= $pgTag." lancia una skill generica che annulla ogni lancio di $target.<br>";
                    } else $msg .= $pgTag." tenta di lanciare una skill generica che annulla ogni lancio di $target, ma fallisce.<br>";
                break;
                case 'malus_10ps_scudo_30ps_bersaglio_meno30ps': // Non si può fare
                    // Controllo se il bersaglio ha meno di 30 salute
                    // Se si, tolgo 10 salute allo striker e
                break;
                case 'annulla_scudo': // Cancello tutti i record con car = difesa per target
                    if($dice >= 10) {
                        gdrcd_query("DELETE FROM role_fights WHERE id_role = $id_role AND turn = $turn AND car = 'difesa' AND target = '$target'");
                        $msg .= $pgTag." lancia una skill generica che annulla ogni scudo di $target per questo turno.<br>";
                    } else $msg .= $pgTag." tenta di lanciare una skill generica che annulla ogni scudo di $target per questo turno, ma fallisce.<br>";
                break;
                case 'prolunga_effetti_un_turno': // Prolunga gli effetti della skill generica lanciata dal bersaglio
                    if($dice >= 10) {
                        gdrcd_query("INSERT INTO role_fights (id_role, turn, striker, target, car, id_skill, level, dice)
                                    VALUES ($id_role, $turn + 1, '$striker', '$target', 'generica', ".$r['id_skill'].", 1, $dice)");
                        $msg .= $pgTag." lancia una skill generica che prolunga gli effetti di una skill generica lanciata da $target anche al turno successivo.<br>";
                    } else $msg .= $pgTag." tenta di lanciare una skill generica che prolunga gli effetti di una skill generica lanciata da $target anche al turno successivo, ma fallisce.<br>";
                break;
                case 'più_15_punti_salute': // Cura
                    if($dice >= 10) {
                        gdrcd_query("UPDATE personaggio SET salute = salute + 15 WHERE nome = '$target'");
                        $msg .= $pgTag." lancia una skill generica che cura $target con 15 punti salute.<br>";
                    } else $msg .= $pgTag." tenta di lanciare una skill generica che cura $target con 15 punti salute, ma fallisce.<br>";
                break;
            }

            $riepilogo[$striker]['generica'][] = $msg; // Salvo la generica lanciata
        }
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

            $riepilogo[$target]['subisce'][] = array(
                'esito' => 1, // 1 = perde punti, 0 = respinge l'attacco
                'pg' => $target,
                'danno' => $integrita,
                'punti' => 'integrita', // Salute o integrità
                'msg' => " e perde $integrita punti integrità per aver lanciato nuovamente una mentale di tipo comando"
            );
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
    if (!isset($riepilogo[$striker])) $riepilogo[$striker] = array(); // Inizializzo l'array del pg

    // Per ogni bersaglio di questo attaccato
    foreach($targets as $k => $target) {
        $carDifesa = getDefenceCar(strtolower($r['car']), $target); // Recupero le info sulla caratteristica usata per il tiro di difesa
        if ($carDifesa === null) continue; // car d'attacco sconosciuta, non elaborabile
        $damage = 0; // Inizializzo il danno subito dal bersaglio
        $durata = 0; // Inizializzo la durata (in turni) di eventuali effetti applicati al bersaglio
        $moltiplicatore = 1; // Inizializzo il moltiplicatore di danno
        $dadoDifesa = 0; // Inizializzo il dado di difesa, che servirà per il calcolo del danno in caso di difese fallite che consentono comunque di lanciare un dado di difesa

        if (!isset($riepilogo[$target])) $riepilogo[$target] = array(); // Inizializzo l'array del pg

        $canRow = gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$target'");
        $can_send = $canRow ? (int)$canRow['can_send'] : 1;

        // Risposta immediata: il bersaglio ha scelto esplicitamente come reagire prima della fine turno
        $dadoRisposta = gdrcd_query("SELECT dice FROM role_fights WHERE id_role=$id_role AND turn=$turn AND striker='$target' AND target='$striker' AND car='dado_risposta' LIMIT 1");
        $subisce      = gdrcd_query("SELECT id   FROM role_fights WHERE id_role=$id_role AND turn=$turn AND striker='$target' AND target='$striker' AND car='subisce'       LIMIT 1");

        // Se il bersaglio può lanciare un dado automatico di difesa perché non ha lanciato uno scudo in questo turno e neanche nel precedente
        if($can_send === 1) {
            if(!isset($difensori[$target])) { // Se non ha usato lo scudo in questo turno, significa che deve difendersi con un dado

                if ($subisce) {
                    // Il bersaglio ha scelto esplicitamente di subire: danno fisso, nessun dado di difesa
                    $damage = $defaultDamage;
                } elseif ($dadoRisposta) {
                    // Il bersaglio ha già tirato il dado in risposta immediata: usa quel risultato
                    $dadoDifesa = (int)$dadoRisposta['dice'];
                    if($dice > $dadoDifesa) {
                        $sgRow = gdrcd_query("SELECT danno FROM gilda_soglie WHERE livello = ".$r['level']);
                        $moltiplicatore = $sgRow ? (float)$sgRow['danno'] : 1.0;
                        $damage = (($dice - $dadoDifesa) * $moltiplicatore / count($targets));
                        $durata = registraDurata($carDifesa['type'], $carDifesa['punti'], $damage, $target, $id_role);
                    }
                } else {
                    // Nessuna risposta immediata: auto-lancia il dado di difesa
                    $dadoDifesa = lanciaStat($id_role, $striker, $target, true, $carDifesa['nome'], $carDifesa['nome'], $carDifesa['car'], $carDifesa['punti'], 0, 0)['risultato'];
                    if($dice > $dadoDifesa) {
                        $sgRow = gdrcd_query("SELECT danno FROM gilda_soglie WHERE livello = ".$r['level']);
                        $moltiplicatore = $sgRow ? (float)$sgRow['danno'] : 1.0;
                        $damage = (($dice - $dadoDifesa) * $moltiplicatore / count($targets));
                        $durata = registraDurata($carDifesa['type'], $carDifesa['punti'], $damage, $target, $id_role);
                    }
                }

            } else $damage = isset($intoccabili[$target]) ? 0 : $defaultDamage; // In questo caso il bersaglio ha usato lo scudo su qualcuno o su se stesso
        } else $damage = $defaultDamage; // Se non può lanciare il dado di difesa, subisce il danno di default
    
        // Salvo tutti gli attacchi ricevuti
        $riepilogo[$target]['subisce'][] = array(
            'pg' => $striker,
            'danno' => $damage,
            'punti' => $carDifesa['punti'], // Punti di salute o integrità prima dell'attacco
            'punti_type' => $carDifesa['type'], // Salute o integrità
            'intoccabile' => isset($intoccabili[$target]), // Se è scudato o meno
            'can_send' => $can_send, // Se può lanciare un dado di difesa o meno
            'durata' => $durata, // Durata di eventuali mentali (in turni)
            'scudo_fallito' => isset($difensori[$target]), // Se ha tentato di difendersi con lo scudo ma ha fallito
            'formula' => [
                'dadoAttacco' => $dice,
                'dadoDifesa' => $dadoDifesa,
                'car_attacco' => $r['car'],
                'car_difesa' => $carDifesa['nome'],
                'moltiplicatore' => $moltiplicatore
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
                'moltiplicatore' => $moltiplicatore
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

        $res = gdrcd_query("SELECT sottotipo FROM abilita WHERE id_abilita = {$r['id_skill']} AND sottotipo IS NOT NULL", 'result');
        if (!$res || gdrcd_query($res, 'num_rows') === 0) continue;

        $sottotipo  = gdrcd_query($res, 'fetch')['sottotipo'];
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
                    $msg       .= "<br>$striker lancia a $target una skill generica che infligge danni $label. ";
                    $targetMsg .= "<br>$target subisce l'effetto della skill generica di $striker e subisce danni $label. ";
                } else $msg .= "<br>$striker tenta di lanciare a $target una skill generica sui danni, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
            } else $msg .= "<br>$striker tenta di lanciare a $target una skill generica sui danni, ma fallisce. ";
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
                        'msg'        => "Subisce l'effetto della skill generica di $striker che infligge $label. "
                    ];
                } else $msg .= "<br>$striker tenta di lanciare a $target una skill generica, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
            } else $msg .= "<br>$striker tenta di lanciare a $target una skill generica, ma fallisce. ";
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
                            $msg       .= "<br>$striker lancia a $target una skill generica che infligge danni dimezzati nonostante lo scudo. ";
                            $targetMsg .= "<br>$target, nonostante lo scudo, subisce la metà dei danni per effetto della skill generica di $striker. ";
                        } else $msg .= "<br>$striker tenta la skill su $target, ma $target non subisce danni fisici in questo turno, quindi la generica non ha effetto. ";
                    } else $msg .= "<br>$striker tenta di lanciare a $target una skill generica che infligge danni dimezzati nonostante lo scudo, ma fallisce. ";
                break;

                case 'sposta_danni_bersaglio_su_castatore':
                    if ($dice >= 10) {
                        if ($hasSubisce) {
                            $tot = sumDanni($riepilogo[$target]['subisce']);
                            $riepilogo[$striker]['subisce'][] = ['pg' => $striker, 'danno' => $tot['salute'],    'punti_type' => 'salute',    'msg' => " e subisce l'effetto della skill generica di $striker che sposta i danni (ps) subiti da $target su $striker. "];
                            $riepilogo[$striker]['subisce'][] = ['pg' => $striker, 'danno' => $tot['integrita'], 'punti_type' => 'integrita', 'msg' => " e subisce l'effetto della skill generica di $striker che sposta i danni (pi) subiti da $target su $striker. "];
                            $msg .= "<br>$striker lancia a $target una skill generica che sposta i danni subiti da $target su $striker. ";
                        } else $msg .= "<br>$striker tenta di spostare i danni di $target su se stesso, ma $target non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di spostare i danni di $target su se stesso, ma fallisce. ";
                break;

                case 'sposta_danni_castatore_su_bersaglio':
                    $hasStrikerSubisce = isset($riepilogo[$striker]['subisce']) && count($riepilogo[$striker]['subisce']) > 0;
                    if ($dice >= 10) {
                        if ($hasStrikerSubisce) {
                            $tot = sumDanni($riepilogo[$striker]['subisce']);
                            $riepilogo[$target]['subisce'][] = ['pg' => $striker, 'danno' => $tot['salute'],    'punti_type' => 'salute',    'msg' => " e subisce l'effetto della skill generica di $striker che sposta i danni (ps) subiti da $striker su $target. "];
                            $riepilogo[$target]['subisce'][] = ['pg' => $striker, 'danno' => $tot['integrita'], 'punti_type' => 'integrita', 'msg' => " e subisce l'effetto della skill generica di $striker che sposta i danni (pi) subiti da $striker su $target. "];
                            $msg .= "<br>$striker lancia a $target una skill generica che sposta i propri danni su $target. ";
                        } else $msg .= "<br>$striker tenta di spostare i propri danni su $target, ma $striker non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di spostare i propri danni su $target, ma fallisce. ";
                break;

                case 'converti_danni_bersaglio_in_salute_castatore':
                    if ($dice >= 10) {
                        if ($hasSubisce) {
                            $tot = sumDanni($riepilogo[$target]['subisce']);
                            gdrcd_query("UPDATE personaggio SET salute = salute + {$tot['salute']} WHERE nome = '$striker'");
                            $msg .= "<br>$striker lancia a $target una skill generica che converte i danni subiti da $target in punti salute per se stesso. ";
                        } else $msg .= "<br>$striker tenta di convertire i danni di $target in salute, ma $target non subisce danni fisici in questo turno. ";
                    } else $msg .= "<br>$striker tenta di convertire i danni di $target in salute, ma fallisce. ";
                break;

                case 'meno_50_danni_tutti_con_durata':
                    // TODO
                break;
            }
        }

        $riepilogo[$striker]['generica'][] = $msg;
        $riepilogo[$target]['generica'][]  = $targetMsg;
    }

    return; // array('riepilogo' => $riepilogo);
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
    

    switch($attack_car) {
        /* In caso l'attacco sia stato fatto con forza, destrezza o potere, la difesa avviene con destrezza,
        prelevo i punti del bersaglio su destrezza e i suoi punti salute */
        case 'forza':
        case 'destrezza':
        case 'potere': return array('nome' => 'destrezza', 'car' => $pg['car2'], 'punti' => $pg['salute'], 'type' => 'salute');
        // Se l'attacco è con Mente, ritorno Tempra, i punti su Tempra e i punti integrità del bersaglio
        case 'mente': return array('nome' => 'tempra', 'car' => $pg['car6'], 'punti' => $pg['integrita'], 'type' => 'integrità');
        default: return null;
    }
}

// Impedisco o consento al pg di lanciare nel prossimo turno
function setCanSend($id_role) {
    $turn = getTurn($id_role);
    $pgsGiocanti = getRolePgs($id_role, true);
    
    foreach($pgsGiocanti as $pg) {
        $canRow = gdrcd_query("SELECT can_send FROM role_session_players WHERE id_role = $id_role AND pg_name = '$pg'");
        $can_send = $canRow ? (int)$canRow['can_send'] : 1;
        // Prendo tutti i lanci fatti dal pg nel turno corrente
        $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pg'", 'result');
        $lanci = gdrcd_query($result, 'num_rows');

        /* Dato che già impedisco di lanciare due scudi o due attacchi nello stesso turno,
        se trovo più di un lancio, significa che il pg ha lanciato sia uno scudo che un attacco.
        Se trovo un solo lancio e can_send = 0, significa che il pg ha lanciato uno scudo (dato che non è possibile lanciare attacchi se can_send = 0)
        rinunciando anche all'attacco nel turno successivo.
        Quindi metto il campo can_send = 0 per il prossimo turno */
        $can = (($lanci > 1 || ($lanci === 1 && $can_send === 0)) ? 0 : 1);
        gdrcd_query("UPDATE role_session_players SET can_send = $can WHERE id_role = $id_role AND pg_name = '$pg'", 'result');
    }
}

// Recupera i personaggi di una role
function getRolePgs($id_role, $active = false) {
    $users = [];
    $activePgs = $active ? "AND `end` IS NULL" : ''; // Se voglio solo i pg attivi, aggiungo al filtro "end is null" per escludere quelli usciti dalla role
    $result = gdrcd_query("SELECT DISTINCT pg_name FROM role_session_players WHERE id_role = $id_role $activePgs", 'result');

    while($row = gdrcd_query($result, 'fetch')) $users[] = $row['pg_name'];

    return $users;
}

// Registra un'azione di combattimento nella role e restituisce l'ID inserito
function fight($id_role, $striker, $target, $id_skill, $level, $car, $dice, $recap='') {
    $turn = getTurn($id_role);
    $query = "INSERT INTO role_fights (id_role, turn, striker, `target`, car, id_skill, level, dice, result)
            VALUES ($id_role, $turn, '$striker', '$target', '$car', $id_skill, $level, $dice, '$recap')";
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

        $choices = [];
        if ($canSend === 1) {
            $choices[] = 'dado';

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
 * Chiude il turno quando tutti i pg attivi hanno close_turn = 1.
 * Viene chiamata da closePgTurn, checkTurnEnd e risposta_immediata.
 *
 * Non blocca più su hasPendingUnrespondedAttacks: se un pg ha confermato
 * la chiusura del turno senza rispondere a un attacco, elaborateTurn gestisce
 * il caso con un tiro automatico di difesa (riga ~1240).
 * La risposta_immediata rimane utile: i pg che rispondono PRIMA di chiudere
 * vedranno il loro tiro usato da elaborateTurn; chi non risponde prende il tiro auto.
 */
function checkTurnCanClose($id_role, $location) {
    $stillOpen = gdrcd_query(
        "SELECT id FROM role_session_players WHERE id_role = $id_role AND close_turn = 0 AND `end` IS NULL LIMIT 1",
        'result'
    );
    if (!$stillOpen || gdrcd_query($stillOpen, 'num_rows') > 0) return; // Qualcuno deve ancora chiudere

    closeTurn($id_role, $location);
}

// Controlla che non siano stati inviati più lanci in un singolo turno
function checkMultipleLounch($id_role, $striker, $type, $turn) {
    $result = gdrcd_query("SELECT * FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$striker' AND car IN (".implode(',', $type).")", 'result');

    return ($result && gdrcd_query($result, 'num_rows') > 0);
}

// Verifica se il pg ha già effettuato almeno un lancio (attacco o difesa) nel turno corrente
function hasTurnLaunch($id_role, $pgName, $turn) {
    $result = gdrcd_query("SELECT id FROM role_fights WHERE id_role = $id_role AND turn = $turn AND striker = '$pgName' AND car NOT IN ('dado_risposta', 'subisce') LIMIT 1", 'result');
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
function checkMentaleComando($id_role, $turn) {
    $query = "SELECT role_fights.* FROM role_fights
            INNER JOIN abilita ON role_fights.id_skill = abilita.id_abilita
            WHERE role_fights.id_role = $id_role AND role_fights.turn = ($turn-1) AND role_fights.target = '$bersaglio' AND role_fights.car = 'Mente'
            AND abilita.sottotipo = 'comando'";
    $result = gdrcd_query($query, 'result');

    return ($result && gdrcd_query($result, 'num_rows') > 0);
}

// Serve per capire se l'attaccante ha già lanciato una skill mentale di comando nel turno precedente, così da scalare l'integrità al pg che ha lanciato l'attacco mentale
function scaloIntegritaDoppioComando($id_role, $bersaglio, $turn) {
    $query = "SELECT role_fights.* FROM role_fights
            INNER JOIN abilita ON role_fights.id_skill = abilita.id_abilita
            WHERE role_fights.id_role = $id_role AND role_fights.turn = ($turn-1) AND role_fights.car = 'Mente'
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
        if ($salute < 20) return "<br>$type di $pg è troppo bassa per subire gli effetti della skill.<br>";
        if ($salute < 60) {
            // Calcolo i turni in base al danno provocato
            switch ($danno) {
                case ($danno >= 50): $turni = 12;
                case ($danno >= 40): $turni = 8;
                case ($danno >= 30): $turni = 4;
                case ($danno >= 20): $turni = 2;
                case ($danno >= 1): $turni = 1;
                default: $turni = 0;
            }
            // Elimino eventuali durate ancora attive per evitare sovrapposizioni
            gdrcd_query("DELETE FROM role_durations WHERE pg_name = '$pg'");

            // Registro la durata della skill in base al danno provocato
            gdrcd_query("INSERT INTO role_durations (id_role, pg_name, duration, current_turn, `type`) VALUES ($id_role, '$pg', $turni, 0, '$type')");
    
            $msg .= "A causa del danno subito, oltre agli effetti della skill, $pg perderà 5 punti $type per i prossimi $turni turni.";
        }
    }

    return $turni;
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