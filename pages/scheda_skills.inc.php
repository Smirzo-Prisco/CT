<div class="pagina_scheda">
    <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        </div>
        <div class="page_scheda_skills">
    <?php /*HELP: */ ?>

    <?php
    //Se non e' stato specificato il nome del pg
    if (isset($_REQUEST['pg']) === false)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknonw_character_sheet']) . '</div>';
    } else if($_REQUEST['pg'] != $_SESSION['login'] && ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1)) { 
    
        echo '<div class="error">Sezione riservata!</div>';
    
    } else
    {

    /*Visualizzo la pagina*/
    /*Verifico l'esistenza del PG e carico le informazioni necessarie*/
    $query = "SELECT nome FROM personaggio WHERE personaggio.nome = '" . gdrcd_filter('get', $_REQUEST['pg']) . "'";
    $query2 = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5
        FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza
        WHERE personaggio.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
    $result = gdrcd_query($query, 'result');
    $personaggi = gdrcd_query($query2, 'result'); 
    $personaggio = gdrcd_query($personaggi, 'fetch');
    //Se non esiste il pg
    if (gdrcd_query($result, 'num_rows') == 0)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']) . '</div>';
    }
    else
    {

    gdrcd_query($result, 'free');
    gdrcd_query($personaggio, 'free');


	 //Punteggi, salute, status, classe, razza.
            if($PARAMETERS['mode']['skillsystem'] == 'ON') { //solo se è attiva la modalità skillsystem
                include ('scheda/skillsystem.inc.php');
            } 
?>
        <?php
        /********* CHIUSURA SCHEDA **********/
        }//else

        }//else
        ?>
  
            <div class="link_back" style="display: none;">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
                <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?>
            </a>
        </div>
</div><!-- Pagina -->