<?php
/*Visualizza il link modifica se l'utente visualizza la propria scheda o se è almeno un capogilda*/
if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) { ?>
    <a href="main.php?page=scheda_modifica&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['update']); ?>
    </a>
<?php } ?>
    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo "Scheda"; ?>
    </a>
    <a href="main.php?page=scheda_storia&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['history']); ?>
    </a>
    <a href="main.php?page=scheda_dice&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['description']); ?>
    </a>
    <a href="main.php?page=scheda_affetti&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['friends']); ?>
    </a>
    <a href="main.php?page=scheda_off&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['off']); ?>
    </a>
    <a href="main.php?page=scheda_trans&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['transictions']); ?>
    </a>
    <?php
    if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) { ?>
    <a href="main.php?page=scheda_px_general&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['experience']); ?>
    </a>
    <a href="main.php?page=scheda_oggetti&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['inventory']); ?>
    </a>
    <?php } 
    
    $magic = gdrcd_query("SELECT nome, id_mestiere FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
    if ($magic['id_mestiere'] == 3 && $_REQUEST['pg'] != $_SESSION['login']) { ?>
    <a href="main.php?page=scheda_oggetti&op=visit&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>&what=8" style="letter-spacing: 0px;">
    <?php echo "Inventario Magic Shop"; ?>
    </a>
    <?php } else if ($magic['id_mestiere'] == 4 && $_REQUEST['pg'] != $_SESSION['login']) { ?>
    <a href="main.php?page=scheda_oggetti&op=visit&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>&what=9" style="letter-spacing: 0px;">
    <?php echo "Inventario Secret Pandora"; ?>
    </a>
    <?php } ?>
        <a href="main.php?page=scheda_skills&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['skills']); ?>
    </a>
    <a href="main.php?page=scheda_equip&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['equipment']); ?>
    </a>
<?php /*Visualizza il link modifica se l'utente visualizza la propria scheda o se è almeno un capogilda*/
if($_SESSION['permessi'] >= MODERATOR) { ?>
    <a href="main.php?page=scheda_log&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['log']); ?>
    </a>
    <a href="main.php?page=scheda_gst&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['gst']); ?>
    </a>
<?php }