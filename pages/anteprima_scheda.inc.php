<div class="pagina_info_location">
    <?php /* HELP: Il box delle informazioni carica l'immagine del personaggio. */

    $result = gdrcd_query("SELECT nome, url_img_chat FROM personaggio WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'",'result');
    $record = gdrcd_query($result, 'fetch');
    
    ?>
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $record['nome']); ?></h2>
    </div>
    <div class="page_body">
            <!--Immagine/descrizione -->
            <?php
            if(date("G")>=18 OR date("G")<=6){
            ?>
            <div class="info_pg_night">
            <?php } else { ?>
            <div class="info_pg">
            <?php } ?>
                <img src="<?php echo gdrcd_filter('out', $record['url_img_chat']); ?>"
                     class="immagine_pg">
<a alt="_top" href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out',$_SESSION['login']);?>"><img class="scheda_pg" src="themes/crystal/imgs/colonna_dx/scheda_personaggio.png"></a>
            </div>
            
                <div class="presenti_button">
                    <a href="main.php?page=presenti_estesi" alt=_top><img src="../themes/crystal/imgs/menu/presenti.png"></a>
                </div>
            </div>
    <!-- page_body -->
</div><!-- Pagina -->