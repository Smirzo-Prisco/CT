<?php include('../ref_header.inc.php'); /*Header comune*/ ?>

<div class="pagina_presenti">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['logged_users']['plur']); ?></h2>
    </div>
    
    <!-- 🎯 SOSTITUISCI IL CODICE PHP CON QUESTO CONTAINER VUOTO -->
    <div id="online-users-container">
        <div class="loading">Caricamento utenti online...</div>
    </div>
</div>

<?php include('../footer.inc.php');  /*Footer comune*/ ?>