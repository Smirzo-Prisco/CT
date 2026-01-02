<?php
$login = gdrcd_filter('in', $_SESSION['login']);
$luogo = $_SESSION['luogo'];

// RESETTA: rimuove le preferenze
if (isset($_POST['reset_preferenze'])) gdrcd_query("UPDATE personaggio SET preferenze_chat = NULL WHERE nome = '$login' LIMIT 1");
elseif (isset($_POST['salva_preferenze'])) {
    $preferenze = [
        'font'             => gdrcd_filter('post', $_POST['font_chat']),
        'colore_testo'     => gdrcd_filter('post', $_POST['colore_chat']),
        'colore_dialogo'   => gdrcd_filter('post', $_POST['colore_dialogo_chat']),
        'grandezza'        => gdrcd_filter('post', $_POST['grandezza_chat']),
        'interlinea'       => gdrcd_filter('post', $_POST['interlinea_chat'])
    ];

    // Rimuovi valori vuoti
    $preferenze = array_filter($preferenze, function($val) { return $val !== null && $val !== ''; });

    if (empty($preferenze)) $json_preferenze = null;
    else $json_preferenze = json_encode($preferenze);

    gdrcd_query("UPDATE personaggio SET preferenze_chat = ".(is_null($json_preferenze) ? "NULL" : "'".gdrcd_filter('in', $json_preferenze)."'")." WHERE nome = '".gdrcd_filter('in', $login)."'");
}
?>