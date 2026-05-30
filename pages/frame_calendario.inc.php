<?php include('../ref_header.inc.php'); /*Header comune*/ ?>
    <!-- Box calendario-->
	   <div id="calendario">
    <iframe src ="pages/agenda/index.php" frameborder="0" scrolling="no" width="220px" height="250">
</iframe>
    </div>
        <div id="calendario_role">
    <a href="../main.php?page=agenda_center&op=add_role" target="_top"">Prenota role <?php if ($_SESSION['Master'] > 0) {?>o Evento<?php }?></a>
    </div>
	
    <!-- Chiudura finestra del gioco -->
<?php include('../footer.inc.php');  /*Footer comune*/ ?>