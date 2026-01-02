<?php
require('../../header.inc.php');

if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) {
$place = "Nome (o nomi separati da virgola)";
} else {
$place = "Nome";
}
?>

<link rel="stylesheet" href="../../themes/crystal/messaggi.css" type="text/css">
<link rel="stylesheet" href="../../themes/crystal/messages.css" type="text/css">
<div id="messages_container">
<div class="panels_box">
    <form target="_parent" class="form_messaggi" action="../../main.php?page=messages_center" method="post">
        <!-- Destinatario -->
        <div class='form_label' style="display: none;">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['destination']); ?>
        </div>
        <div class='form_field'>
            <input type="text" list="personaggi" name="destinatario" placeholder="<?php echo $place ?>" value="<?php echo gdrcd_filter('get', $_REQUEST['reply_dest']); ?>" />
        </div>
        <?php
        echo gdrcd_list('personaggi'); ?>
            
            
            <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) { ?>
                <div class="form_field">
                <select name="multipli">
                <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) { ?>

                    <option value="---" selected>
                        -------
                    </option>
                  <?php } ?>





                    <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
                    <option value="presenti">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['online']); ?>
                    </option>
                    <?php }//fine presenti
                    if($_SESSION['admin'] == 1) { ?>
                        <option value="broadcast">
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['multiple']['all']); ?>
                        </option>
                     <?php }//fine globale ?>
                    <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capogilda'] == 1) { ?>
                     <option value="capogilda">
                        <?php echo "Tutti i capogilda"; ?>
                    </option>
                     <?php }//fine capogilda ?>
                    <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['capomestiere'] == 1) { ?>
                    <option value="capomestiere">
                        <?php echo "Tutti i capomestiere"; ?>
                    </option>
                     <?php }//fine capomestiere ?>
                    <?php if($_SESSION['admin'] == 1 || $_SESSION['capogilda'] == 1) { ?>
                     <option value="gilda">
                        <?php echo "Tutta la famiglia"; ?>
                    </option>
                    <?php }//fine famiglia ?>
                    <?php if($_SESSION['admin'] == 1 || $_SESSION['capomestiere'] == 1) { ?>
                    <option value="tutto_mestiere">
                        <?php echo "Tutto il mestiere"; ?>
                    </option>
                    <?php }//fine mestiere ?> 
                    <?php if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) { ?>
                    <option value="tutto_inclinati">
                        <?php echo "Tutti gli inclinati"; ?>
                    </option>
                    <?php }//fine inclinati ?> 
                </select>
            </div>
           <?php }//fine mestiere ?> 
           
        <!-- Titolo -->
        <div class="form_label" style="display: none;">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['title']); ?>
                    </div>
                    <div class="form_field">
                        <input name="titolo" placeholder="Titolo" />
                        
                                <select name="tipo">
        <option value="off"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][0]);//parlato?></option>
        <option value="on"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][1]);//parlato?></option>
        </select>
                    </div>

        <!-- Testo -->
        <div class='form_label' style="display: none;">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['body']); ?>
        </div>
        <div class='form_field'>
 	  	    <textarea type="textbox" name="testo"><?php
                /**    * Fix per evitare le parentesi quadre vuote quando si compone un nuovo messaggio
                 * @author Blancks
                 */
                if(isset($_POST['testo'])) {
                    echo "\n\n\n[".gdrcd_filter('out', trim($_POST['testo']))."]";
                }
                ?></textarea>
        </div>
        <!-- Tipologia SMS -->
       <?php /* <div class='form_field'>
        <select name="tipo">
        <option value="off"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][0]);//parlato?></option>
        <option value="on"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['type'][1]);//parlato?></option>
        </select>
        </div> */?>
        <!-- Submit -->
        <input type="hidden" name="op" value="send_message" />
        <input type="hidden" name="reply_attach" value="<?php echo gdrcd_filter('get', $_POST['reply_attach']); ?>" />
        <div class='form_submit'>
            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
        </div>
    </form>
</div>
<div class="link_back" style="display: none;">
    <a href="main.php?page=messages_center&offset=0"><?php echo gdrcd_filter('out', $MESSAGE['interface']['messages']['go_back']); ?></a>
</div>
</div>
<script type="text/javascript">
var parentW = window.parent.location.href;
if(parentW.includes("presenti") || parentW.includes("scheda")){
document.getElementById('messages_container').className="special_container";
}
else{
document.getElementById('messages_container').className="container";
}
</script>