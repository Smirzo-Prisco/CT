<link rel="stylesheet" href="../themes/crystal/hover.css">

<?php
        include('../ref_header.inc.php');

        if(empty($_SESSION['last_istant_message']) === true) $_SESSION['last_istant_message'] = 0;

        // Query per ottenere il numero di messaggi non letti nelle conversazioni individuali
        $messaggi_non_letti_individuali = gdrcd_query("SELECT COUNT(*) AS cnt FROM conversazioni_individuali WHERE utente_nome = '".gdrcd_filter('in', $_SESSION['login'])."' AND lettura = 0", 'result');
        $row_individuali = gdrcd_query($messaggi_non_letti_individuali, 'fetch');
        $cntNewMessageIndividuali = $row_individuali['cnt'];
        gdrcd_query($messaggi_non_letti_individuali, 'free');

        // Query per ottenere il numero di messaggi non letti nei gruppi
        $messaggi_non_letti_gruppo = gdrcd_query("SELECT COUNT(*) AS cnt FROM partecipazione_gruppo WHERE utente_nome = '".gdrcd_filter('in', $_SESSION['login'])."' AND lettura = 0", 'result');
        $row_gruppo = gdrcd_query($messaggi_non_letti_gruppo, 'fetch');
        $cntNewMessageGruppo = $row_gruppo['cnt'];
        gdrcd_query($messaggi_non_letti_gruppo, 'free');

        // Somma i messaggi non letti delle conversazioni individuali e dei gruppi
        $cntNewMessage = $cntNewMessageIndividuali + $cntNewMessageGruppo;
        $hasNewMessage = ($cntNewMessage > 0);

        $max_id = gdrcd_query("SELECT max(id) as max FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0"); 
        $news = gdrcd_query("SELECT * FROM ctnews ORDER BY data DESC LIMIT 1");

        // Controlliamo se il meteo deve essere aggiornato
        $meteo_query = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
        $meteo = gdrcd_query($meteo_query, 'fetch');

        if ($meteo) {
            $ora_corrente = new DateTime();
            $ultima_aggiornamento = new DateTime($meteo['datetime_aggiornamento']);
            
            // Se l'ultimo aggiornamento è avvenuto più di 24 ore fa o non è stato aggiornato oggi
            if ($ora_corrente->diff($ultima_aggiornamento)->h >= 24 || $ultima_aggiornamento->format('Y-m-d') != $ora_corrente->format('Y-m-d')) {

                // Funzione per generare condizioni meteo
                function genera_meteo($mese) {
            switch ($mese) {
                case 12: case 1: case 2: // Inverno
                    $condizioni = ['nuvoloso', 'pioggia', 'neve', 'sole_nebbia', 'sole_nuvoloso', 'temporale']; // Più possibilità di neve e maltempo
                    break;
                case 3: case 4: case 5: // Primavera
                    $condizioni = ['sole', 'nuvoloso', 'sole_nuvoloso', 'pioggia', 'temporale']; // Maggiori possibilità di tempo sereno
                    break;
                case 6: case 7: case 8: // Estate
                    $condizioni = ['sole', 'nuvoloso', 'pioggia', 'temporale']; // Temporali estivi e caldo
                    break;
                case 9: case 10: case 11: // Autunno
                    $condizioni = ['sole', 'sole_nuvoloso', 'nuvoloso', 'pioggia', 'sole_nebbia']; // Prevalenza di piogge autunnali
                    break;
                default:
                    $condizioni = ['sole', 'nuvoloso']; // Caso predefinito per sicurezza
            }
            return $condizioni[array_rand($condizioni)];
        }

        // Funzione per generare temperatura in base al mese e al tipo (giorno o notte)
        function genera_temperatura($mese, $tipo = 'giorno') {
            switch ($mese) {
                case 12: case 1: case 2: // Inverno
                    return ($tipo === 'giorno') ? rand(-5, 10) : rand(-10, 5);
                case 3: case 4: case 5: // Primavera
                    return ($tipo === 'giorno') ? rand(10, 20) : rand(5, 15);
                case 6: case 7: case 8: // Estate
                    return ($tipo === 'giorno') ? rand(25, 35) : rand(15, 25);
                case 9: case 10: case 11: // Autunno
                    return ($tipo === 'giorno') ? rand(10, 20) : rand(5, 15);
                default:
                    return rand(10, 25); // Valore di default
            }
        }

        // Funzione per generare vento in base alla stagione
        function genera_vento($mese) {
            switch ($mese) {
                case 12: case 1: case 2: // Inverno
                    return ['assente', 'brezza', 'medio', 'forte'][array_rand(['assente', 'brezza', 'medio', 'forte'])];
                case 3: case 4: case 5: // Primavera
                    return ['assente', 'brezza', 'medio'][array_rand(['assente', 'brezza', 'medio'])];
                case 6: case 7: case 8: // Estate
                    return ['brezza', 'medio', 'forte'][array_rand(['brezza', 'medio', 'forte'])];
                case 9: case 10: case 11: // Autunno
                    return ['assente', 'brezza', 'medio', 'forte'][array_rand(['assente', 'brezza', 'medio', 'forte'])];
                default:
                    return 'assente';
            }
        }

        // Ottieni i nuovi dati per giorno e notte
        $mese = date('n'); // Otteniamo il mese attuale (1-12)
        $meteo_giorno = genera_meteo($mese);
        $meteo_notte = genera_meteo($mese);
        $temperatura_giorno = genera_temperatura($mese, 'giorno');
        $temperatura_notte = genera_temperatura($mese, 'notte');
        $vento_giorno = genera_vento($mese);
        $vento_notte = genera_vento($mese);

        // Collegamento delle immagini meteo
        $MESSAGE['interface']['meteo']['status_img'] = [
            // Immagini per il giorno
            'sole' => 'sole.png',
            'sole_nuvoloso' => 'sole_nuvoloso.png',
            'sole_nebbia' => 'sole_nebbia.png',       
            'nuvoloso' => 'nuvoloso.png',
            'pioggia' => 'pioggia.png',
            'temporale' => 'temporale.png',
            'neve' => 'neve.png',
        ];

        $img_giorno = $MESSAGE['interface']['meteo']['status_img'][$meteo_giorno_attuale . '_giorno'];
        $img_notte = $MESSAGE['interface']['meteo']['status_img'][$meteo_notte_attuale . '_notte'];

        // Aggiorna i dati nella tabella
        gdrcd_query("
            UPDATE meteo SET 
                meteo_giorno_precedente = meteo_giorno_attuale,
                meteo_notte_precedente = meteo_notte_attuale,
                temperatura_giorno_precedente = temperatura_giorno_attuale,
                temperatura_notte_precedente = temperatura_notte_attuale,
                vento_giorno_precedente = vento_giorno_attuale,
                vento_notte_precedente = vento_notte_attuale,
                meteo_giorno_attuale = '".gdrcd_filter('in', $meteo_giorno)."',
                meteo_notte_attuale = '".gdrcd_filter('in', $meteo_notte)."',
                temperatura_giorno_attuale = ".gdrcd_filter('in', $temperatura_giorno).",
                temperatura_notte_attuale = ".gdrcd_filter('in', $temperatura_notte).",
                vento_giorno_attuale = '".gdrcd_filter('in', $vento_giorno)."',
                vento_notte_attuale = '".gdrcd_filter('in', $vento_notte)."',
                datetime_aggiornamento = NOW()
            WHERE id = 1
        ");
    }
}

// Recuperiamo i dati aggiornati
$meteo_query = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
$meteo = gdrcd_query($meteo_query, 'fetch');

// Recupera i dati del meteo
$meteo_giorno_attuale = $meteo['meteo_giorno_attuale'];
$meteo_notte_attuale = $meteo['meteo_notte_attuale'];
$temperatura_giorno_attuale = $meteo['temperatura_giorno_attuale'];
$temperatura_notte_attuale = $meteo['temperatura_notte_attuale'];
$vento_giorno_attuale = $meteo['vento_giorno_attuale'];
$vento_notte_attuale = $meteo['vento_notte_attuale'];

$meteo_giorno_precedente = $meteo['meteo_giorno_precedente'];
$meteo_notte_precedente = $meteo['meteo_notte_precedente'];
$temperatura_giorno_precedente = $meteo['temperatura_giorno_precedente'];
$temperatura_notte_precedente = $meteo['temperatura_notte_precedente'];
$vento_giorno_precedente = $meteo['vento_giorno_precedente'];
$vento_notte_precedente = $meteo['vento_notte_precedente'];

// Array per gestire le condizioni speciali notturne
$meteo_img_notte = [
    'sole_nuvoloso' => 'luna_nuvoloso',
    'sole_nebbia' => 'luna_nebbia', 
    'nuvoloso' => 'luna_nuvoloso',
    'sole' => 'mezza_luna'
];

// Usa isset() per controllare se esiste l'immagine notturna speciale
$img_notte_attuale = isset($meteo_img_notte[$meteo_notte_attuale]) ? $meteo_img_notte[$meteo_notte_attuale] : $meteo_notte_attuale;
$img_notte_precedente = isset($meteo_img_notte[$meteo_notte_precedente]) ? $meteo_img_notte[$meteo_notte_precedente] : $meteo_notte_precedente;

?>

<div class="news">
    <!-- Titolo Meteo di Oggi/Ieri -->
    <div class="meteo_titolo" id="meteo_titolo">
        METEO OGGI
    </div>

    <!-- Meteo del giorno attuale -->
    <div class="meteo_box meteo_attuale">
        <div class="meteo_colonna_sx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?php echo $meteo_giorno_attuale; ?>.png" alt="Meteo Giorno" class="meteo_immagine">
            </div>
            <div class="meteo_temp">
                <span>Max: <span class="temp_max"><?php echo $temperatura_giorno_attuale; ?>°C</span></span>
            </div>
            <div class="meteo_vento">
                Vento: <span class="vento_velocità"><?php echo $vento_giorno_attuale; ?></span>
            </div>
        </div>
        <div class="meteo_colonna_dx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?php echo $img_notte_attuale; ?>.png" alt="Meteo Notte" class="meteo_immagine">
            </div>
            <div class="meteo_temp">
                <span>Min: <span class="temp_min"><?php echo $temperatura_notte_attuale; ?>°C</span></span>
            </div>
            <div class="meteo_vento">
                Vento: <span class="vento_velocità"><?php echo $vento_notte_attuale; ?></span>
            </div>
        </div>
    </div>

    <!-- Meteo del giorno precedente -->
    <div class="meteo_box meteo_precedente" style="display:none;">
        <div class="meteo_colonna_sx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?php echo $meteo_giorno_precedente; ?>.png" alt="Meteo Giorno Precedente" class="meteo_immagine">
            </div>
            <div class="meteo_temp">
                <span>Max: <span class="temp_max"><?php echo $temperatura_giorno_precedente; ?>°C</span></span>
            </div>
            <div class="meteo_vento">
                Vento: <span class="vento_velocità"><?php echo $vento_giorno_precedente; ?></span>
            </div>
        </div>
        <div class="meteo_colonna_dx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?php echo $img_notte_precedente; ?>.png" alt="Meteo Notte Precedente" class="meteo_immagine">
            </div>
            <div class="meteo_temp">
                <span>Min: <span class="temp_min"><?php echo $temperatura_notte_precedente; ?>°C</span></span>
            </div>
            <div class="meteo_vento">
                Vento: <span class="vento_velocità"><?php echo $vento_notte_precedente; ?></span>
            </div>
        </div>
    </div>

    <!-- Freccia per switch -->
    <div class="meteo_freccia">
        <img src="../themes/crystal/imgs/forum/freccia_giu.png" alt="Switch Meteo" class="switch_meteo" onclick="switchMeteo()">

    </div>
</div>

<!-- MESSAGGI -->
<div class="pagina_messaggi">
<table class="tg">
<tbody>
  <tr>
  <?php
    if(date("G")>=18 OR date("G")<=6){
    ?>
    <td class="tg-0lax">
    <div id="mappa">
    <div id="pic-wrapper">
    <a href="../main.php?page=mappaclick&map_id=<?php echo $_SESSION['mappa']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_mappa.png" title="homepage" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_mappa_night.gif" title="homepage" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="aggiorna">
    <div id="pic-wrapper">
    <a href="../main.php?dir=<?php echo $_SESSION['luogo']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_aggiorna.png" title="aggiorna" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_aggiorna_night.gif" title="aggiorna" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="forum">
    <div id="pic-wrapper">
    <a href="../main.php?page=forum" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_forum.png" title="Bacheche" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_forum_night.gif" title="Bacheche" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="uffici">
    <div id="pic-wrapper">
    <a href="../main.php?page=uffici" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_uff.png" title="Uffici" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_uff_night.gif" title="Uffici" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="doc">
    <div id="pic-wrapper">
    <a href="../main.php?page=pre_doc" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_doc.png" title="Manuali" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_doc_night.gif" title="Manuali" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="mex">
    <div id="pic-wrapper">
    <?php
if($PARAMETERS['mode']['check_messages'] === 'ON') {
    if(!$hasNewMessage) {
        echo '<div class="messaggio_forum">';

        gdrcd_query($messaggi_non_letti, 'free');

        if(empty ($PARAMETERS['names']['private_message']['image_file']) === false) {
            if(($PARAMETERS['names']['private_message']['image_file_onclick']) === true) {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file'];
            } else {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file_onclick'];
            }

            echo '<script type="text/javascript"> if (document.images) { var msg_button1_up = new Image(); msg_button1_up.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_up.'"; var msg_button1_over = new Image(); msg_button1_over.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_down.'";} function msg_over_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_over.src;}} function msg_up_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_up.src}}</script>';
            //if ($_SESSION['login'] == 'Lii') {
            echo '<a id="message-link" onMouseOver="msg_over_button()" onMouseOut="msg_up_button()" href="../main.php?page=messages_center&offset=0"  target="_top">';
            //}
            echo '<img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file'].'" alt="'.gdrcd_filter('out',
                                                                                                                                                                                                                                                                                                          $PARAMETERS['names']['private_message']['plur']
                ).'" id=\"pic\"title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" name="msg_buttonOne" />';
            //if ($_SESSION['login'] == 'Lii') {
echo '                </a>';
            //}

        } else {
            echo '<a id="message-link" href="../main.php?page=messages_center&offset=0" target="_top">'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'</a>';
        }
        echo '</div>';

        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON') { ?>
        <script type="text/javascript">
            parent.stop_blinking_title();
        </script>
        <?php
        }
    } else { //$_SESSION['last_istant_message']=$max_id['max']; ?>
        <div class="messaggio_forum_nuovo">
        <?php //if ($_SESSION['login'] == 'Lii') { ?>
            <a id="message-link" href="../main.php?page=messages_center&offset=0" target="_top">
            <?php // } ?>
                <?php 
                if(empty ($PARAMETERS['names']['private_message']['image_file_new_night']) === false) {
                    echo '<img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file_new_night'].'" id=\"pic\" alt="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" />';
                } else {
                    echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']);
                } ?>
                        <?php //if ($_SESSION['login'] == 'Lii') { ?>
            </a>
                        <?php //} ?>
        </div>
        <?php
        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON'){ ?>
            <script type="text/javascript">
                parent.blink_title("(<?php echo $MESSAGE['interface']['forums']['topic']['new_posts']['sing']; ?>) <?php echo $PARAMETERS['info']['site_name']; ?>", true);
            </script>
        <?php
        }

        if($PARAMETERS['mode']['allow_audio'] == 'ON' && $_SESSION['blocca_media'] != 1 && !empty($PARAMETERS['settings']['audio_new_messagges'])) {
    $audio_file = $PARAMETERS['settings']['audio_new_messagges'];
?>
    <script>
        const audioPath = '../sounds/<?php echo $audio_file; ?>';
        const now = Date.now();
        const lastPlay = localStorage.getItem('last_audio_play');

        if (!lastPlay || now - parseInt(lastPlay) > 600000) { // 600000 ms = 10 minuti
            const audio = new Audio(audioPath);
            audio.play().catch(() => {}); // evita errori se autoplay è bloccato
            localStorage.setItem('last_audio_play', now.toString());
        }
    </script>
<?php } 

    }
}
?>
    </div>
    </div>
    </td>
  </tr>
  
  
  
  <tr>
  <td class="tg-0lax">
    <div id="chat">
    <div id="pic-wrapper">
    <a href="javascript:;" onClick="window.open('../chattina_off.php', 'titolo', 'width=650, height=600, resizable, status, scrollbars=1, location');" target="_top">
    <?php
    $check_chat = gdrcd_query("SELECT COUNT(*) AS presenza FROM chat_letta WHERE nome ='".$_SESSION['login']."'");
    if ($check_chat['presenza'] == 0) {
    ?>
    <img src="../themes/crystal/imgs/icone/icon_chat_off.png" title="Chat Off" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_chat_off.png" title="Chat Off" id="pic-inner">
    <?php
    } else { ?>
    <img src="../themes/crystal/imgs/icone/icon_chat_off_night.gif" title="Chat Off" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_chat_off_night.gif" title="Chat Off" id="pic-inner">
    <?php
    } 
    ?>
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
        <div id="gestione">
            <div id="pic-wrapper">
                <a href="../main.php?page=gestione" target="_top">
                    <img src="../themes/crystal/imgs/icone/icon_strumenti.png" title="Gestione" id="pic">
                    <img src="../themes/crystal/imgs/icone/icon_strumenti_night.gif" title="Gestione" id="pic-inner">
                </a>
            </div>
        </div>
    </td>
    
    <td class="tg-0lax">
    <div id="logout">
    <div id="pic-wrapper">
    <a href="../logout.php" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_exit.png" title="Esci" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_exit_night.png" title="Esci" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    
    
    
    
    <?php } else { ?>
    
    
    <td class="tg-0lax">
    <div id="mappa">
    <div id="pic-wrapper">
    <a href="../main.php?page=mappaclick&map_id=<?php echo $_SESSION['mappa']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_mappa.png" title="homepage" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_mappa_hover.gif" title="homepage" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="aggiorna">
    <div id="pic-wrapper">
    <a href="../main.php?dir=<?php echo $_SESSION['luogo']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_aggiorna.png" title="aggiorna" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_aggiorna_hover.gif" title="aggiorna" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="forum">
    <div id="pic-wrapper">
    <a href="../main.php?page=forum" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_forum.png" title="Bacheche" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_forum_hover.gif" title="Bacheche" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="uffici">
    <div id="pic-wrapper">
    <a href="../main.php?page=uffici" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_uff.png" title="Uffici" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_uff_hover.gif" title="Uffici" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="doc">
    <div id="pic-wrapper">
    <a href="../main.php?page=pre_doc" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_doc.png" title="Manuali" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_doc_hover.gif" title="Manuali" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="mex">
    <div id="pic-wrapper">
    <?php
if($PARAMETERS['mode']['check_messages'] === 'ON') {
    if(!$hasNewMessage) {
        echo '<div class="messaggio_forum">';

        gdrcd_query($messaggi_non_letti, 'free');

        if(empty ($PARAMETERS['names']['private_message']['image_file']) === false) {
            if(($PARAMETERS['names']['private_message']['image_file_onclick']) === true) {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file'];
            } else {
                $img_up = $PARAMETERS['names']['private_message']['image_file'];
                $img_down = $PARAMETERS['names']['private_message']['image_file_onclick'];
            }

            echo '<script type="text/javascript"> if (document.images) { var msg_button1_up = new Image(); msg_button1_up.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_up.'"; var msg_button1_over = new Image(); msg_button1_over.src = "../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$img_down.'";} function msg_over_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_over.src;}} function msg_up_button() { if (document.images) { document["msg_buttonOne"].src = msg_button1_up.src}}</script>';

            //if ($_SESSION['login'] == 'Lii') {
echo '<a id="message-link" onMouseOver="msg_over_button()" onMouseOut="msg_up_button()" href="../main.php?page=messages_center&offset=0"  target="_top">';
//}
echo '<img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file'].'" alt="'.gdrcd_filter('out',
                                                                                                                                                                                                                                                                                                          $PARAMETERS['names']['private_message']['plur']
                ).'" id=\"pic\"title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" name="msg_buttonOne" />';
//if ($_SESSION['login'] == 'Lii') {
echo '</a>';
//}
        } else {
            echo '<a id="message-link" href="../main.php?page=messages_center&offset=0" target="_top">'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'</a>';
        }
        echo '</div>';

        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON') { ?>
        <script type="text/javascript">
            parent.stop_blinking_title();
        </script>
        <?php
        }
    } else { //$_SESSION['last_istant_message']=$max_id['max']; ?>
        <div class="messaggio_forum_nuovo">
           <?php //if ($_SESSION['login'] == 'Lii') { ?>
            <a id="message-link" href="../main.php?page=messages_center&offset=0" target="_top">
              <?php //} ?>
                <?php
                if(empty ($PARAMETERS['names']['private_message']['image_file_new']) === false) {
                    echo '<img src="../themes/'.$PARAMETERS['themes']['current_theme'].'/imgs/menu/'.$PARAMETERS['names']['private_message']['image_file_new'].'" id=\"pic\" alt="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" title="'.gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']).'" />';
                } else {
                    echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['plur']);
                } ?>
                           <?php //if ($_SESSION['login'] == 'Lii') { ?>
            </a>
            <?php //} ?>
        </div>
        <?php
        if($PARAMETERS['mode']['alert_pm_via_pagetitle'] == 'ON'){ ?>
            <script type="text/javascript">
                parent.blink_title("(<?php echo $MESSAGE['interface']['forums']['topic']['new_posts']['sing']; ?>) <?php echo $PARAMETERS['info']['site_name']; ?>", true);
            </script>
        <?php
        }

       if($PARAMETERS['mode']['allow_audio'] == 'ON' && $_SESSION['blocca_media'] != 1 && !empty($PARAMETERS['settings']['audio_new_messagges'])) {
    $audio_file = $PARAMETERS['settings']['audio_new_messagges'];
?>
    <script>
        const audioPath = '../sounds/<?php echo $audio_file; ?>';
        const now = Date.now();
        const lastPlay = localStorage.getItem('last_audio_play');

        if (!lastPlay || now - parseInt(lastPlay) > 600000) { // 600000 ms = 10 minuti
            const audio = new Audio(audioPath);
            audio.play().catch(() => {}); // evita errori se autoplay è bloccato
            localStorage.setItem('last_audio_play', now.toString());
        }
    </script>
<?php } 

    }
}
?>
    </div>
    </div>
    </td>
  </tr>
  
  
  
  <tr>
  <td class="tg-0lax">
    <div id="chat">
    <div id="pic-wrapper">
    <a href="javascript:;" onClick="window.open('../chattina_off.php', 'titolo', 'width=650, height=600, resizable, status, scrollbars=1, location');" target="_top">
    <?php
    $check_chat = gdrcd_query("SELECT COUNT(*) AS presenza FROM chat_letta WHERE nome ='".$_SESSION['login']."'");
    if ($check_chat['presenza'] == 0) {
    ?>
    <img src="../themes/crystal/imgs/icone/icon_chat_off.png" title="Chat Off" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_chat_off.png" title="Chat Off" id="pic-inner">
    <?php
    } else { ?>
    <img src="../themes/crystal/imgs/icone/icon_chat_off_hover.gif" title="Chat Off" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_chat_off_hover.gif" title="Chat Off" id="pic-inner">
    <?php
    } 
    ?>
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="gestione">
    <div id="pic-wrapper">
    <a href="../main.php?page=gestione" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_strumenti.png" title="Gestione" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_strumenti_hover.gif" title="Gestione" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    <td class="tg-0lax">
    <div id="logout">
    <div id="pic-wrapper">
    <a href="../logout.php" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_exit.png" title="Esci" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_exit_hover.gif" title="Esci" id="pic-inner">
    </a>
    </div>
    </div>
    </td>
    
    
    <?php } ?>
  </tr>
</tbody>
</table>
</div>
<?php include('../footer.inc.php');  /*Footer comune*/ ?>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var link = document.getElementById("message-link");

        // Funzione per rilevare se il dispositivo è mobile o tablet
        function isMobileDevice() {
            return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
        }

        if (isMobileDevice()) {
            // Modifica l'href e il target per dispositivi mobili o tablet
            link.href = "../pages/mex_privati/index.php";
            link.target = "_blank"; // Apri in una nuova scheda/finestra
        }
    });
    
    
    
    
    
    document.addEventListener("DOMContentLoaded", function() {
    var meteoAttuale = document.querySelector('.meteo_attuale');
    var meteoPrecedente = document.querySelector('.meteo_precedente');
    var switchButton = document.querySelector('.switch_meteo');
    var titolo = document.getElementById('meteo_titolo'); // Aggiungi l'elemento del titolo

    switchButton.addEventListener('click', function() {
        // Alterna la visibilità dei due div
        if (meteoAttuale.style.display === "none") {
            meteoAttuale.style.display = "flex";
            meteoPrecedente.style.display = "none";
            titolo.textContent = "METEO OGGI"; // Cambia il titolo a "METEO OGGI"
        } else {
            meteoAttuale.style.display = "none";
            meteoPrecedente.style.display = "flex";
            titolo.textContent = "METEO DI IERI"; // Cambia il titolo a "METEO DI IERI"
        }
    });
});

</script>
