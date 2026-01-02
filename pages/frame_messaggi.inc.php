<?php
    add_script("/includes/frame_messaggi.js");
    // ====================================
    // PHP: Recupero dati sessione, messaggi e meteo
    // ====================================

    // --- Ultima news ---
    $news = gdrcd_query("SELECT * FROM ctnews ORDER BY data DESC LIMIT 1");

    // ====================================
    // Meteo
    // ====================================
    $meteoQuery = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
    $meteo = gdrcd_query($meteoQuery, 'fetch');

    if ($meteo) {
        $ora_corrente = new DateTime();
        $ultima_aggiornamento = new DateTime($meteo['datetime_aggiornamento']);

        $diffOre = $ora_corrente->diff($ultima_aggiornamento)->h;
        $oggi = $ora_corrente->format('Y-m-d');

        if ($diffOre >= 24 || $ultima_aggiornamento->format('Y-m-d') != $oggi) {
            // --- Funzioni di generazione meteo ---
            function genera_meteo($mese) {
                switch ($mese) {
                    case 12: case 1: case 2: $condizioni = ['nuvoloso','pioggia','neve','sole_nebbia','sole_nuvoloso','temporale']; break;
                    case 3: case 4: case 5: $condizioni = ['sole','nuvoloso','sole_nuvoloso','pioggia','temporale']; break;
                    case 6: case 7: case 8: $condizioni = ['sole','nuvoloso','pioggia','temporale']; break;
                    case 9: case 10: case 11: $condizioni = ['sole','sole_nuvoloso','nuvoloso','pioggia','sole_nebbia']; break;
                    default: $condizioni = ['sole','nuvoloso']; break;
                }
                return $condizioni[array_rand($condizioni)];
            }

            function genera_temperatura($mese, $tipo = 'giorno') {
                switch ($mese) {
                    case 12: case 1: case 2: return ($tipo === 'giorno') ? rand(-5, 10) : rand(-10, 5);
                    case 3: case 4: case 5: return ($tipo === 'giorno') ? rand(10, 20) : rand(5, 15);
                    case 6: case 7: case 8: return ($tipo === 'giorno') ? rand(25, 35) : rand(15, 25);
                    case 9: case 10: case 11: return ($tipo === 'giorno') ? rand(10, 20) : rand(5, 15);
                    default: return rand(10, 25);
                }
            }
            
            function genera_vento($mese) {
                switch ($mese) {
                    case 12: case 1: case 2: $venti = ['assente','brezza','medio','forte']; break;
                    case 3: case 4: case 5: $venti = ['assente','brezza','medio']; break;
                    case 6: case 7: case 8: $venti = ['brezza','medio','forte']; break;
                    case 9: case 10: case 11: $venti = ['assente','brezza','medio','forte']; break;
                    default: return 'assente';
                }
                return $venti[array_rand($venti)];
            }

            $mese = date('n');
            $meteo_giorno = genera_meteo($mese);
            $meteo_notte = genera_meteo($mese);
            $temperatura_giorno = genera_temperatura($mese, 'giorno');
            $temperatura_notte = genera_temperatura($mese, 'notte');
            $vento_giorno = genera_vento($mese);
            $vento_notte = genera_vento($mese);

            // --- Aggiorna tabella meteo ---
            gdrcd_query("UPDATE meteo SET
                meteo_giorno_precedente = meteo_giorno_attuale,
                meteo_notte_precedente = meteo_notte_attuale,
                temperatura_giorno_precedente = temperatura_giorno_attuale,
                temperatura_notte_precedente = temperatura_notte_attuale,
                vento_giorno_precedente = vento_giorno_attuale,
                vento_notte_precedente = vento_notte_attuale,
                meteo_giorno_attuale = '".gdrcd_filter('in',$meteo_giorno)."',
                meteo_notte_attuale = '".gdrcd_filter('in',$meteo_notte)."',
                temperatura_giorno_attuale = ".gdrcd_filter('in',$temperatura_giorno).",
                temperatura_notte_attuale = ".gdrcd_filter('in',$temperatura_notte).",
                vento_giorno_attuale = '".gdrcd_filter('in',$vento_giorno)."',
                vento_notte_attuale = '".gdrcd_filter('in',$vento_notte)."',
                datetime_aggiornamento = NOW()
                WHERE id = 1
            ");
        }
    }

    // --- Recupera dati aggiornati ---
    $meteoQuery = gdrcd_query("SELECT * FROM meteo WHERE id = 1", 'result');
    $meteo = gdrcd_query($meteoQuery, 'fetch');

    // Variabili meteo attuali e precedenti
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

    // Mappa condizioni speciali notte
    $meteo_img_notte = [
        'sole_nuvoloso' => 'luna_nuvoloso',
        'sole_nebbia' => 'luna_nebbia',
        'nuvoloso' => 'luna_nuvoloso',
        'sole' => 'mezza_luna'
    ];

    $img_notte_attuale = isset($meteo_img_notte[$meteo_notte_attuale]) 
                        ? $meteo_img_notte[$meteo_notte_attuale] 
                        : $meteo_notte_attuale;

    $img_notte_precedente = isset($meteo_img_notte[$meteo_notte_precedente]) 
                            ? $meteo_img_notte[$meteo_notte_precedente] 
                            : $meteo_notte_precedente;
?>

<style>
    #gridPanel {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10px;
        margin-left: 22px;
    }

    #gridPanel .grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-gap: 12px;            /* distanza uniforme */
        grid-auto-rows: 60px;
        padding: 10px;
    }

    #gridPanel .grid-item {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    #gridPanel .grid-item img {
        width: 50px;
        height: 50px;
    }
</style>

<!-- ============================= -->
<!-- METEO -->
<!-- ============================= -->
<div class="news">
    <!-- Titolo Meteo -->
    <div id="meteo_titolo" class="meteo_titolo">METEO OGGI</div>

    <!-- Meteo Attuale -->
    <div class="meteo_box meteo_attuale">
        <div class="meteo_colonna_sx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?=$meteo_giorno_attuale; ?>.png" alt="Meteo Giorno" class="meteo_immagine">
            </div>
            <div class="meteo_temp">Max: <span class="temp_max"><?=$temperatura_giorno_attuale; ?>°C</span></div>
            <div class="meteo_vento">Vento: <span class="vento_velocità"><?=$vento_giorno_attuale; ?></span></div>
        </div>
        <div class="meteo_colonna_dx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?=$img_notte_attuale; ?>.png" alt="Meteo Notte" class="meteo_immagine">
            </div>
            <div class="meteo_temp">Min: <span class="temp_min"><?=$temperatura_notte_attuale; ?>°C</span></div>
            <div class="meteo_vento">Vento: <span class="vento_velocità"><?=$vento_notte_attuale; ?></span></div>
        </div>
    </div>

    <!-- Meteo Precedente (Ieri) -->
    <div class="meteo_box meteo_precedente" style="display:none;">
        <div class="meteo_colonna_sx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?=$meteo_giorno_precedente; ?>.png" alt="Meteo Giorno Precedente" class="meteo_immagine">
            </div>
            <div class="meteo_temp">Max: <span class="temp_max"><?=$temperatura_giorno_precedente; ?>°C</span></div>
            <div class="meteo_vento">Vento: <span class="vento_velocità"><?=$vento_giorno_precedente; ?></span></div>
        </div>
        <div class="meteo_colonna_dx">
            <div class="meteo_img">
                <img src="../themes/crystal/imgs/meteo/<?=$img_notte_precedente; ?>.png" alt="Meteo Notte Precedente" class="meteo_immagine">
            </div>
            <div class="meteo_temp">Min: <span class="temp_min"><?=$temperatura_notte_precedente; ?>°C</span></div>
            <div class="meteo_vento">Vento: <span class="vento_velocità"><?=$vento_notte_precedente; ?></span></div>
        </div>
    </div>

    <!-- Switch Meteo -->
    <div class="meteo_freccia">
        <img src="../themes/crystal/imgs/forum/freccia_giu.png" alt="Switch Meteo" class="switch_meteo" onclick="switchMeteo();">
    </div>
</div>

<!-- ============================= -->
<!-- Messaggi e icone principali -->
<!-- ============================= -->
<div id="gridPanel">
    <div class="grid">

        <!-- 1. Mappa -->
        <div class="grid-item">
            <a href="../main.php?page=mappaclick&map_id=<?=$_SESSION['mappa']?>" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_mappa.png" alt="Mappa">
            </a>
        </div>

        <!-- 2. Aggiorna -->
        <div class="grid-item">
            <a href="../main.php?dir=<?=$_SESSION['luogo']?>" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_aggiorna.png" alt="Aggiorna">
            </a>
        </div>

        <!-- 3. Messaggi Privati -->
        <div class="grid-item">
            <a href="../main.php?page=messages_center&offset=0" target="_top" id="message-link">
                <img src="../themes/crystal/imgs/icone/icona_base_mex.png" 
                    alt="<?=gdrcd_filter('out',$PARAMETERS['names']['private_message']['plur'])?>" 
                    title="<?=gdrcd_filter('out',$PARAMETERS['names']['private_message']['plur'])?>">
            </a>
        </div>

        <!-- 4. Forum -->
        <div class="grid-item">
            <a href="../main.php?page=forum" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_forum.png" alt="Forum">
            </a>
        </div>

        <!-- 5. Uffici -->
        <div class="grid-item">
            <a href="../main.php?page=uffici" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_uff.png" alt="Uffici">
            </a>
        </div>

        <!-- 6. Giocate -->
        <div class="grid-item">
            <a href="../main.php?page=role_recap" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_doc.png" alt="Giocate">
            </a>
        </div>

        <!-- 7. Chat off -->
        <div class="grid-item">
            <a id="chatoff-link" href="javascript:;" onclick="window.open('../chattina_off.php','chat','width=650,height=600,resizable,status,scrollbars=1');" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_chat_off.png" alt="Chat">
            </a>
        </div>

        <!-- 8. Gestione -->
        <div class="grid-item">
            <a href="../main.php?page=gestione" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_strumenti.png" alt="Gestione">
            </a>
        </div>

        <!-- 9. Logout -->
        <div class="grid-item">
            <a href="../logout.php" target="_top">
                <img src="../themes/crystal/imgs/icone/icon_exit.png" alt="Esci">
            </a>
        </div>

    </div>
</div>