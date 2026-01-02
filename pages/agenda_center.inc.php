<?php
session_start();
include_once('../header.inc.php');
/*Header comune*/
?>
<link rel="stylesheet" href="../pages/agenda/style.css" TYPE="text/css">
<script type="text/JavaScript" src="../pages/agenda/overlib_mini.js"></script>
<script language="JavaScript" type="text/javascript">
	window.defaultStatus="Calendar Script";
</script>
<script type="text/JavaScript">
function popupEvent(day, month, year, w, h) {
	var winl = (screen.width - w) / 2;
	var wint = (screen.height - h) / 2;
	win = window.open("popup.php?day=" + day + "&month=" + month + "&year=" + year + "","Calendar","scrollbars=yes, status=yes, location=no, toolbar=no, menubar=no, directories=no, resizable=yes, width=" + w + ", height=" + h + ", top=" + wint + ", left=" + winl + "");
	if (parseInt(navigator.appVersion) >= 4) {
		win.window.focus();
	}
}
</script>

<script type="text/JavaScript">
  var ol_width=175;
  var ol_delay=10;
  var ol_fgcolor="#020106";
  var ol_bgcolor="#0b0c14";
  var ol_offsetx=-250;
  var ol_offsety=10;
  var ol_border=0;
  var ol_vauto=1;
  var ol_borderTopLeftRadius = "10px";
</script>
<div class="pagina_messages_center">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['agenda']['sing']); ?></h2>
    </div>
    <div class="page_body">
        <?php
        /*
         * Richieste POST
         */
        switch(gdrcd_filter_get($_GET['op'])) {
            case 'remove_role': //Eliminazione di un messaggio
                include('agenda/cancella.php');
                break;
            case 'edit_role': //Eliminazione di un messaggio
                include('agenda/edit.php');
                break;
            case 'erase_checked': //Controllo eliminazione di un messaggio
                include('agenda/');
                break;
            case 'eraseall': //Eliminazione di tutti i messaggi
                include ('agenda/');
                break;
            case 'add_role': //Inserimento nuovo messaggio nel db
                include('agenda/form_multiplo.php');
                break;
            case 'attach': //Form di composizione di un messaggio
            case 'send':
            case 'reply':
                include('agenda/');
                break;
            default:
                include('agenda/index.php');
                break;
        }
      
    ?>
    </div><!-- page_body -->
</div><!-- Pagina -->
