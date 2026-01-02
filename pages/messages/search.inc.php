<?php
//Determinazione pagina
if(isset($_REQUEST['offset']) === false) {
    $pagebegin = 0;
} else {
    $pagebegin = (int) $_REQUEST['offset'] * $PARAMETERS['settings']['messages_per_page'];
}
$pageend = $PARAMETERS['settings']['messages_per_page'];

//Conteggio messaggi totali
$record = gdrcd_query("SELECT COUNT(*) FROM messaggi WHERE destinatario = '".$_SESSION['login']."'");
$totaleresults = $record['COUNT(*)'];

//comando cerca

?>
<form class="searchBar" action="main.php?page=messages_center&op=search" method="post">
<div align="center"> Ricerca (per titolo o autore):  
<input name="ricerca" />
<input type="submit" value="cerca" />
</div>
</form>
    
<div class="slider">
  
<?php
include('../ref_header.inc.php');

if(empty($_SESSION['last_istant_message']) === true) {
    $_SESSION['last_istant_message'] = 0;
}

$non_letti = gdrcd_query("SELECT * FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0 AND id > ".$_SESSION['last_istant_message']."", 'result');

$max_id = gdrcd_query("SELECT max(id) as max FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0"); 
?>

 <?php
if($PARAMETERS['mode']['check_messages'] === 'ON') {
    if((gdrcd_query($non_letti, 'num_rows') == 0) || ($max_id['max'] < $_SESSION['last_istant_message'])) {
        

        gdrcd_query($non_letti, 'free');
		
		echo '<a href="#slide-1">OFF</a>';
		echo '<a href="#slide-2">ON</a>';
		echo '<a href="#slide-3">STAFF</a>';

    } else { //$_SESSION['last_istant_message']=$max_id['max']; ?>
        
		<?php
		$countOFF = 0;
		$countON = 0;
		$countSTAFF = 0;
            while($row = gdrcd_query($non_letti, 'fetch')){
				if($row['tipo']=='off'){
					$countOFF=$countOFF + 1;
				}
				elseif($row['tipo']=='on'){
					$countON=$countON + 1;
				}
				elseif($row['tipo']=='staff'){
					$countSTAFF=$countSTAFF + 1;
				}
				
			}
                
        if($countOFF > 0){
		echo '<a href="#slide-1" style="background-color: #8291ca; font-color: #1a2240">OFF</a>';
		} else {
		echo '<a href="#slide-1">OFF</a>';
		} 
		if($countON > 0){
		echo '<a href="#slide-2" style="background-color: #8291ca; font-color: #1a2240">ON</a>';
		} else {
		echo '<a href="#slide-2">ON</a>';
		}
		 if($countSTAFF > 0){
		echo '<a href="#slide-3" style="background-color: #8291ca; font-color: #1a2240">STAFF</a>';
		} else {
		echo '<a href="#slide-3">STAFF</a>';
		}
				?>
        
  
        <?php

        
    }
}
?>
    <div class="link_back">
        [<a href="main.php?page=messages_center">
            Ricevuti
        </a>] -
        [<a href="main.php?page=messages_center&op=inviati">
            Inviati
        </a>]
    </div>
    
   



  <div class="slides">
    <div id="slide-1">
<?php
include ('index_off_search.inc.php');
?>
    </div>
    <div id="slide-2">
<?php
include ('index_on_search.inc.php');
?>
    </div>
    <div id="slide-3">
<?php
include ('index_staff_search.inc.php');
?>
    </div>
  </div>
</div>

<!-- INVIA MESSAGGIO -->
    
    <div style="text-align: center; width: 200px; margin: 10px auto; display:none;">
   
    <div style="float: left;">
    <form action="/pages/messages/create.inc.php" target="readMessage" method="post">
    <input type="submit" value="Nuovo SMS">
    </form>    
    </div>
    
    <div style="float: right;">
    <form action="main.php?page=forum" method="post">
    <input type="submit" value="Torna Indietro">
    </form>
    </div>
    
    </div>
    
<!-- link scrivi messaggio -->
<div class="link_back">
   <?php /* <a href="main.php?page=messages_center&op=create">
        <?php echo $MESSAGE['interface']['messages']['new']; ?>
    </a> */?>
    <form action="/pages/messages/create.inc.php" target="readMessage">
    	<input style="margin-top: 0xp; margin-bottom: 0px;" type="submit" value="<?php echo $MESSAGE['interface']['messages']['new']; ?>">
    </form>
</div>
<!-- link scrivi messaggio -->
<div class="link_back">
    <form action="/pages/messages/eraseall.inc.php" target="readMessage">
        <input style="margin-top: 0xp; margin-bottom: 0px;" type="submit" value="<?php echo $MESSAGE['interface']['messages']['erase_all']; ?>">
    </form>
</div>

<div class="read_message">
<header>
CrystalApp
</header>
<iframe class="read_message" name="readMessage" src="/pages/messages/default.php">
</iframe>
</div>
<script type="text/javascript">
    function checked_copy() {
        console.log('call');
        var messages = document.getElementsByClassName('message_check');
        var form = document.getElementById('multiple_delete');
        var n_msg = messages.length;
        var i;
        var checked = false;

        for (i = 0; i < n_msg; i++) {
            if (messages[i].checked) {
                checked = true;
                var el = document.createElement('input');
                el.setAttribute('type', 'hidden');
                el.setAttribute('name', 'ids[]');
                el.setAttribute('value', messages[i].getAttribute('value')); 
                form.appendChild(el);
            }
        }

        if (checked) {
            return true;
        } else {
            return false;
        }
    }
    
        function checked_copy_on() {
        console.log('call');
        var messages = document.getElementsByClassName('message_check_on');
        var form = document.getElementById('multiple_delete_on');
        var n_msg = messages.length;
        var i;
        var checked = false;

        for (i = 0; i < n_msg; i++) {
            if (messages[i].checked) {
                checked = true;
                var el = document.createElement('input');
                el.setAttribute('type', 'hidden');
                el.setAttribute('name', 'ids[]');
                el.setAttribute('value', messages[i].getAttribute('value')); 
                form.appendChild(el);
            }
        }

        if (checked) {
            return true;
        } else {
            return false;
        }
    }
    
        function checked_copy_staff() {
        console.log('call');
        var messages = document.getElementsByClassName('message_check_staff');
        var form = document.getElementById('multiple_delete_staff');
        var n_msg = messages.length;
        var i;
        var checked = false;

        for (i = 0; i < n_msg; i++) {
            if (messages[i].checked) {
                checked = true;
                var el = document.createElement('input');
                el.setAttribute('type', 'hidden');
                el.setAttribute('name', 'ids[]');
                el.setAttribute('value', messages[i].getAttribute('value')); 
                form.appendChild(el);
            }
        }

        if (checked) {
            return true;
        } else {
            return false;
        }
    }
</script>
