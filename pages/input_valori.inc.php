<?php
$login = $_SESSION['login'];
$luogo = $_SESSION['luogo'];
$parametri = gdrcd_query("SELECT * FROM personaggio WHERE nome = '$login'");
$salute = $parametri["salute"];
$check_backing = gdrcd_query("SELECT * FROM personaggio WHERE nome ='$login'");
$back_chat = ($check_backing['back_chat'] == 1) ? 1 : 0;

if (gdrcd_filter('get', $_POST['op']) == 'take_action') {
    $azione = gdrcd_filter('get', $_POST['valori']);
    $caratteristica = '';
    $nome_tiro = '';
    $usa_bonus = true;

    switch ($azione) {
        case 'Usa destrezza':
            $caratteristica = $parametri["car2"];
            $nome_tiro = 'destrezza';
            break;
        case 'Usa potere':
            $caratteristica = $parametri["car8"];
            $nome_tiro = 'potere magico';
            break;
        case 'Usa forza':
            $caratteristica = $parametri["car0"];
            $nome_tiro = 'forza';
            break;
        case 'Usa mente':
            $caratteristica = $parametri["car4"];
            $nome_tiro = 'mente';
            break;
        case 'Usa tempra':
            $caratteristica = $parametri["car6"];
            $nome_tiro = 'tempra';
            break;
        case 'Usa dadi20':
            $caratteristica = 0;
            $nome_tiro = "$login esegue un tiro totale di";
            $usa_bonus = false;
            break;
        case 'Usa dado master':
            $caratteristica = 0;
            $nome_tiro = "Master esegue un tiro totale di";
            $usa_bonus = false;
            break;
        case 'AttCreatura':
            $caratteristica = 0;
            $nome_tiro = "La creatura di $login attacca";
            $usa_bonus = false;
            break;
        case 'DifCreatura':
            $caratteristica = 0;
            $nome_tiro = "La creatura di $login si difende";
            $usa_bonus = false;
            break;
        case 'oggetto_vendi':
            echo "<script type='text/javascript'> document.location = 'main.php?page=oggetto_assegna_chat&dir=$luogo'; </script>";
            exit;
    }

    if ($caratteristica !== '') {
        $maxnum = min(floor(abs(0 + substr(trim($Msg), 5))), 1000);
        if ($maxnum == 0) {
            $maxnum = 20;
        }
        $num = mt_rand(1, $maxnum);
        $numtot_finale = $num;
        $malus = 0;

        if ($usa_bonus) {
            $bonus = (($caratteristica / 10) - 1);
            $numtot_finale += $bonus;
            $messaggio2 = "$num/20 + $bonus";

            if ($salute <= 50) {
                if ($salute > 40) {
                    $malus = 1;
                } elseif ($salute > 30) {
                    $malus = 3;
                } elseif ($salute > 20) {
                    $malus = 5;
                } elseif ($salute > 0) {
                    $malus = 10;
                }
                $numtot_finale -= $malus;
                $messaggio2 .= " - $malus di malus per la salute";
            }

            if ($malus == 0) {
                $messaggio2 = "$num/20 + $bonus = $numtot_finale";
            } else {
                $messaggio2 .= " = $numtot_finale";
            }
        } else {
            $messaggio2 = "$num/20";
        }

        if ($azione == 'AttCreatura' || $azione == 'DifCreatura') {
            $messaggio = "$nome_tiro con un tiro totale di $numtot_finale/20";
        } elseif ($azione == 'Usa dadi20' || $azione == 'Usa dado master') {
            $messaggio = "$nome_tiro $numtot_finale/20";
        } else {
            $messaggio = "$login esegue un tiro totale di $nome_tiro di $numtot_finale";
        }

        if ($back_chat) {
            gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo, backing) VALUES ('$luogo', '$login', NOW(), 'C', '$messaggio', '1')");
            gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('$luogo', 'System', '$login', NOW(), 'Q', '$messaggio2')");
        } else {
            gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('$luogo', '$login', NOW(), 'C', '$messaggio')");
            gdrcd_query("INSERT INTO chat (stanza, mittente, destinatario, ora, tipo, testo) VALUES ('$luogo', 'System', '$login', NOW(), 'Q', '$messaggio2')");
        }
    }
}

echo "<script type='text/javascript'> document.location = 'main.php?dir=$luogo'; </script>";
?>
