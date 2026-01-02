<div class="pagina_scheda">
    <?php /* Permessi */
    if ($_SESSION['login'] == $_REQUEST['pg'] || $_SESSION['admin'] == 1) {
    ?>

    <?php

    //Se non e' stato specificato il nome del pg
    if (isset($_REQUEST['pg']) === false)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknonw_character_sheet']) . '</div>';
    } else
    {
    /*Visualizzo la pagina*/
    /*Verifico l'esistenza del PG*/
    $query = "SELECT nome FROM personaggio WHERE personaggio.nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "'";
    $result = gdrcd_query($query, 'result');
    //Se non esiste il pg
    if (gdrcd_query($result, 'num_rows') == 0)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']) . '</div>';
    }
    else
    {

    gdrcd_query($result, 'free');

    $num_logs = $PARAMETERS['settings']['view_logs'];
    ?>

    <!-- Riepilogo PX -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['px']['page_name']); ?></h2>
    </div>
    <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
    </div>
    <div class="pagina_scheda_px">

<div class="slider">

        <?php
        /********* CHIUSURA SCHEDA **********/
		echo '<a href="#slide-1">Punti Exp</a>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="#slide-2">Punti Mestiere</a>';
        ?>
        
        <div class="slides">
        <div id="slide-1">
<?php
include ('scheda_px.inc.php');
?>
    </div>
    <div id="slide-2">
<?php
include ('scheda_px_mestiere.inc.php');
?>
    </div>
        </div>
        </div>

        
        <?php
        }//op
        
        }
        ?>
    </div>
    <?php } else { ?>
    Ce sta un motivo se 'sto link nun compare :)<br>
    Nun fa er furb*!<br><br>
    <?php } ?>
    <!-- Link a piè di pagina -->
        <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div>
        
            
</div><!-- Pagina -->