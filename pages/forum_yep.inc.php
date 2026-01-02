<link rel="stylesheet" href="themes/crystal/bacheca.css">
<div class="pagina_forum">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2>
            <?php echo gdrcd_filter('out', $PARAMETERS['names']['forum']['plur']); ?>
        </h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
        <?php
        /*
         * Richieste POST
         */
        switch(gdrcd_filter_get($_POST['op'])) {
            case 'edit': //Modifica messaggio o topic
                include ('forum/edit.inc.php');
                break;
            case 'edit_quest': //Modifica messaggio o topic
                include ('forum/edit_quest.inc.php');
                break;            case 'insert': //Inserimento messaggio o topic
                include ('forum/insert.inc.php');
                break;
            case 'insert_quest': //Inserimento quest
                include ('forum/insert_quest.inc.php');
                break;
            case 'insert_role': //Inserimento role
                include ('forum/insert_role.inc.php');
                break;    
            case 'readall': //Funzione segna tutto come letto
                include ('forum/readall.inc.php');
                break;
            default:
                break;
        }
        /*
         * Richieste GET
         */
        switch(gdrcd_filter_get($_GET['op'])) {
            case 'composer': //Creazione nuovi messaggi e topic
                include ('forum/composer.inc.php');
                break;
            case 'composer_quest': //Creazione quest
                include ('forum/composer_quest.inc.php');
                break;
            case 'search': //Ricerca messaggio o topic
                include ('forum/search.inc.php');
                break;
            case 'delete': //Cancellazione messaggio o topic
                include ('forum/delete.inc.php');
                break;            
            case 'delete_conf':
                include ('forum/delete_conf.inc.php');
                break;
            case 'delete_quest': //Cancellazione quest
                include ('forum/delete_quest.inc.php');
                break;            
            case 'delete_conf_quest':
                include ('forum/delete_conf_quest.inc.php');
                break;
            case 'segnala':
                include ('forum/segnala.inc.php');
                break;
            case 'modifica': //Form modifica
                include ('forum/modifica.inc.php');
                break;
            case 'modifica_quest': //Form modifica
                include ('forum/modifica_quest.inc.php');
                break;
            case 'move': //Visualizzazione dei topic
                include ('forum/move.inc.php');
                break;
            case 'read': //Visualizzazione topic
                include ('forum/read.inc.php');
                break;
            case 'visit': //Visualizzazione dei topic
                include ('forum/visit.inc.php');
                break;
            case false: //Visualizzazione di base (Elenco forum)
            default:
                include ('forum/index_yep.inc.php');
                break;
        }

        ?>
    </div>
    <!-- Box principale -->

</div><!-- Pagina -->
