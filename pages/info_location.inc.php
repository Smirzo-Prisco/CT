<div class="pagina_info_location">
    <?php /* HELP: Il box delle informazioni carica l'immagine del luogo corrente, lo stato e la descrizione. Genera, inoltre, il meteo */

    $result = gdrcd_query("SELECT mappa.nome, mappa.descrizione, mappa.stato, mappa.immagine, mappa.stanza_apparente, mappa.scadenza, mappa_click.meteo FROM  mappa_click LEFT JOIN mappa ON mappa_click.id_click = mappa.id_mappa WHERE id = ".$_SESSION['luogo'],'result');
    $record_exists = gdrcd_query($result, 'num_rows');
    $record = gdrcd_query($result, 'fetch');

    /** * Fix: quando non si � in una mappa visualizza il nome della chat
     * Quando si � in una mappa si visualizza il nome della mappa
     * @author Blancks
     */
    if(empty($record['nome'])) {
        $nome_mappa = gdrcd_query("SELECT nome FROM mappa_click WHERE id_click = ".(int) $_SESSION['mappa']);
        $nome_luogo = $nome_mappa['nome'];
    } else {
        $nome_luogo = $record['nome'];
    }
    ?>
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $nome_luogo); ?></h2>
    </div>
    <div class="page_body">
        <?php
        if($record_exists > 0 || $_SESSION['luogo'] == -1) {
            gdrcd_query($result, 'free');

            if(empty($record['nome']) === false) {
                $nome_luogo = $record['nome'];
            } elseif($_SESSION['mappa'] >= 0) {
                $nome_luogo = $PARAMETERS['names']['maps_location'];
            } else {
                $nome_luogo = $PARAMETERS['names']['base_location'];
            }
            ?>
            <!--Immagine/descrizione -->
            <?php
            if(date("G")>=18 OR date("G")<=6){
            ?>
            <div class="info_image_night">
            <?php } else { ?>
            <div class="info_image">
            <?php } ?>
                <?php
                if(empty($record['immagine']) === false) {
                    $immagine_luogo = $record['immagine'];
                } else {
                    $immagine_luogo = 'ingresso.png';
                }
                ?>
                <img src="themes/<?php echo gdrcd_filter('out', $PARAMETERS['themes']['current_theme']); ?>/imgs/locations/<?php echo $immagine_luogo ?>"
                     class="immagine_luogo" title="<?php echo gdrcd_filter('out', $record['descrizione']); ?>">
                <?php
                if ($_SESSION['luogo'] > 0) {
                ?>
                <a href="#" onclick="changeFrame('pages/descrizione_chat.php?id=<?php echo $_SESSION['luogo']?>');document.getElementById('id01').style.display='block'">
                <?php
                }
                ?>
                <img class="descrizione" src="themes/crystal/imgs/colonna_sx/descrizione_luogo.png" align="center">
                <?php
                if ($_SESSION['luogo'] > 0) {
                ?>
                </a>
                <?php
                }
                ?>
                </div>
            <?php if((isset($record['stato']) === true) || (isset($record['descrizione']) === true)) {
                echo '<div class="box_stato_luogo"><marquee onmouseover="this.stop()" onmouseout="this.start()" direction="left" scrollamount="3" class="stato_luogo">&nbsp;'.$MESSAGE['interface']['maps']['Status'].': '.gdrcd_filter('out', $record['stato']).' -  '.gdrcd_filter('out', $record['descrizione']).'</marquee></div>';
            } else {
                echo '<div class="box_stato_luogo">&nbsp;</div>';
            }
            
            ?>
            
             <?php
            
            /* Recupera l'ultimo accesso dell'utente */
$login = $_SESSION['login']; // Assumendo che tu abbia una sessione per il login dell'utente
$query = "SELECT last_date_news FROM personaggio WHERE nome = '" . gdrcd_filter('in', $login) . "'";
$result = gdrcd_query($query, 'result');
$row = gdrcd_query($result, 'fetch');

/* Imposta un valore di default se last_date_news è vuoto */
$last_date_news = !empty($row['last_date_news']) ? $row['last_date_news'] : '1970-01-01 00:00:00';

/* Verifica se ci sono nuove notizie */
$query = "SELECT COUNT(*) AS new_count FROM ctnews WHERE data > '" . $last_date_news . "'";
$result = gdrcd_query($query, 'result');
$row = gdrcd_query($result, 'fetch');
$new_news_count = $row['new_count'];

/* Decidi se l'utente ha nuove notizie */
$has_new_news = ($new_news_count > 0) ? true : false;

?>
            <div class="meteo_image_replacement">
    <img src="../themes/crystal/imgs/menu/BNews_Off.png" alt="BNews Image" class="meteo_image" id="openModalBreaking">
            </div>
            
            <!-- Modale -->
<div id="modalBreaking" class="modal_breaking">
    <div class="modal_content_breaking">
        <span class="close_breaking" id="closeModalBreaking">&times;</span>
        <iframe src="../pages/breaking_news.inc.php" frameborder="0" class="modal_iframe_breaking"></iframe>
    </div>
</div>


        <?php
        } else {
            echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['location_doesnt_exist']).'</div>';
        } ?>
    </div>
    <!-- page_body -->
    
    
<script>
    // Funzione per cambiare immagine in base all'ora del giorno
    function changeBreakingNewsImage() {
        const hasNewNews = <?php echo $has_new_news ? 'true' : 'false'; ?>;
        const imgElement = document.getElementById('openModalBreaking');
        
        if (hasNewNews) {
            const now = new Date();
            const hour = now.getHours();

            if (hour >= 6 && hour <= 17) {
                // Mattina
                imgElement.src = '../themes/crystal/imgs/menu/BNews_Mattina.gif';
            } else {
                // Sera/notte
                imgElement.src = '../themes/crystal/imgs/menu/BNews_Notte.gif';
            }
        } else {
            // Se non ci sono nuove notizie, mantieni l'immagine predefinita
            imgElement.src = '../themes/crystal/imgs/menu/BNews_Off.png';
        }
    }

    // Esegui il cambio dell'immagine quando la pagina è pronta
    document.addEventListener("DOMContentLoaded", function() {
        changeBreakingNewsImage;

        const openModalBreaking = document.getElementById("openModalBreaking");
        if(openModalBreaking) {
            openModalBreaking.onclick = function() {
                document.getElementById("modalBreaking").style.display = "block";
            }
        }

        const closeModalBreaking = document.getElementById("closeModalBreaking");
        if(closeModalBreaking) {
            document.getElementById("closeModalBreaking").onclick = function() {
                document.getElementById("modalBreaking").style.display = "none";
            }
        }

        // Chiudere la modale cliccando fuori di essa
        window.onclick = function(event) {
            if (event.target == document.getElementById("modalBreaking")) {
                document.getElementById("modalBreaking").style.display = "none";
            }
        }
    });
</script>
</div><!-- Pagina -->