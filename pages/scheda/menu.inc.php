<link rel="stylesheet" href="../../themes/crystal/scheda_menu.css" type="text/css" />
<ul class="menu">
 <li class="menuItem"><a href="#">PERSONAGGIO</a>
    <ul class="subMenu">
        <p>
            <li class="subMenuItem">
                <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo "Scheda"; ?>
                </a>
            </li>
            <li class="subMenuItem">
                <a href="main.php?page=scheda_storia&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['history']); ?>
                </a>
            </li>
            <li class="subMenuItem">
                <a href="main.php?page=scheda_dice&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['description']); ?>
                </a>
            </li>
            <li class="subMenuItem">
                <a href="main.php?page=scheda_affetti&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['friends']); ?>
                </a>
            </li>
            <li class="subMenuItem">
                <a href="main.php?page=scheda_off&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['off']); ?>
                </a>
            </li>
            <li class="subMenuItem">
                <a href="main.php?page=scheda_trans&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['transictions']); ?>
                </a>
            </li>
            </p>
        </ul>
    </li>
            
            
     
<li class="menuItem"><a href="#">SKILL&OGGETTI</a>
<ul class="subMenu"><p>
<?php
    if($_REQUEST['pg'] == $_SESSION['login'] || ($_SESSION['admin'] == 1 OR $_SESSION['master'] == 1)) { ?>
      <li class="subMenuItem"><a href="main.php?page=scheda_skills&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['skills']); ?>
    </a></li>
    <?php } ?>
                        <li class="subMenuItem"><a href="main.php?page=scheda_equip&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['equipment']); ?>
    </a></li>
    <?php
    if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) { ?>
                        <li class="subMenuItem"><a href="main.php?page=scheda_oggetti&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['inventory']); ?>
    </a></li>
<?php }
      $magic = gdrcd_query("SELECT nome, id_mestiere FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
    if ($magic['id_mestiere'] == 3 && $_REQUEST['pg'] != $_SESSION['login']) { ?>
                        <li class="subMenuItem"><a href="main.php?page=scheda_oggetti&op=visit&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>&what=8" style="letter-spacing: 0px; line-height: 20px;">
    <?php echo "Inventario Magic Shop"; ?>
    </a></li>
    <?php } ?>
    
                    </p></ul>
            </li>
    <?php
    if($_REQUEST['pg'] == $_SESSION['login'] || $_SESSION['admin'] == 1) { 
    ?>
    <li class="menuItem"><a href="#">PUNTI</a>
<ul class="subMenu"><p>
      <li class="subMenuItem"><a href="main.php?page=scheda_px&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['experience']); ?>
    </a></li>
    
      <li class="subMenuItem"><a href="main.php?page=scheda_px_shin&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo 'Punti Shin'; ?>
    </a></li>
    <li class="subMenuItem"><a href="main.php?page=scheda_px_mestiere&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
        <?php echo "Punti Mestiere"; ?>
    </a></li>
    </p></ul>
    </li>
    <li class="menuItem">
        <a href="main.php?page=scheda_modifica&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>" style="letter-spacing: 0px; line-height: 20px;">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['update']); ?>
        </a>
    </li>
    <?php } ?>
</ul>
