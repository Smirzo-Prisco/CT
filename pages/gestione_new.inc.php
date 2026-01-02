<script type="text/javascript"> 
  function showHideRow(row) { 
    $("#" + row).toggle(); 
  } 
</script> 
<link rel="stylesheet" href="../themes/crystal/uffici.css">
<div class="pagina_gestione">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['administration_page_name']); ?></h2>
    </div>
    <div class="page_body">
        <?php
            if($_SESSION['admin'] != 1 && $_SESSION['capomestiere'] != 1 && $_SESSION['capogilda'] != 1 ) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
        /* 
        foreach($PARAMETERS['administration'] as $link_menu) {
            if((empty($link_menu['url']) === false) && (empty($link_menu['text']) === false) && (isset($link_menu['access_level']) === true) && ($link_menu['access_level'] <= $_SESSION['permessi'])) {
                echo '<div class="link_menu">';
                if(empty($link_menu['image_file']) === false) {
                    echo '<img src="'.$link_menu['image_file'].'" class "link_menu_point" />';
                }
                echo '<a href="'.$link_menu['url'].'">'.gdrcd_filter('out', $link_menu['text']).'</a></div>';
            }//if
        }//foreach 
        </div>
        */?> 
    <br><br>
        <?php if($_SESSION['admin'] == 1 ) { ?>
        <div class="slider">
  
  <a href="#slide-1">ADMIN</a>
  <a href="#slide-2">MOD</a>
  <a href="#slide-3">CAPI</a>
    <div class="slides">
    <div id="slide-1">
<?php
include ('pannello_admin.inc.php');
?>
    </div>
    <div id="slide-2">
<?php
include ('pannello_mod.inc.php');
?>
    </div>
    <div id="slide-3">
<?php
include ('pannello_capi.inc.php');
?>
    </div>
  </div>
</div>
        
        
        <?php
}
?>
   
    
<?php
}
 /* HELP: Il menu viene generato automaticamente attingendo dalle informazioni contenute in config.inc.php.
  * La versione supporta link testuali ed immagini e può essere modificata direttamente nel file config.ing.php,
  * impostando url di destinazione, testo e selezionado le immagini.
  * Se il link è un'immagine il testo viene interpretato automaticamente come testo alternativo all'immagine.
  * Per realizzare un menu di altro tipo suggeriamo di commentare o cancellare il contenuto di questa pagina e sostituirlo con il codice del nuovo menu.
  */
?>