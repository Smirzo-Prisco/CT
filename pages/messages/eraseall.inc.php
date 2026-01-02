<?php
require('../../header.inc.php');
if($_POST['op'] == 'erase_off') {

gdrcd_query("UPDATE messaggi SET destinatario_del = 1 WHERE destinatario = '".$_SESSION['login']."' AND tipo = 'off'");
echo "<script type='text/javascript'>alert('SMS OFF CANCELLATI');</script>";
} else if($_POST['op'] == 'erase_on') {


gdrcd_query("UPDATE messaggi SET destinatario_del = 1 WHERE destinatario = '".$_SESSION['login']."' AND tipo = 'on'");
echo "<script type='text/javascript'>alert('SMS ON CANCELLATI');</script>";
} else if($_POST['op'] == 'erase_staff') {

gdrcd_query("UPDATE messaggi SET destinatario_del = 1 WHERE destinatario = '".$_SESSION['login']."' AND tipo = 'staff'");
echo "<script type='text/javascript'>alert('SMS STAFF CANCELLATI');</script>";
}

?>
<link rel="stylesheet" href="../../themes/crystal/messaggi.css" type="text/css">
<link rel="stylesheet" href="../../themes/crystal/messages.css" type="text/css">
<div class="warning">
    <?php echo gdrcd_filter('out', $PARAMETERS['names']['private_message']['sing'].$MESSAGE['interface']['messages']['erased_all']); ?>
</div>
