<?php

	/** * Chat Off Per GDRCD Extreme
	@Author Axel di Fairy Tail Universe GDR
	*** Potrai liberamente utilizzare questa chat off compatibile in tutto e per tutto con GDRCD fino alla versione Extreme riconoscendo la paternità del codice all'autore e non rimuovendo questo commento. 
	*** Nel caso in cui il commento venisse rimosso, l'autore dei codici non donerà il consenso all'utilizzo del codice sottostante.
	*** La vendita del codice è vietata, poiché rilasciata in termini d'uso totalmente gratuiti.
	*** Il creatore del codice non si assume nessuna responsabilità in merito a malfunzionamenti o bug.
	*** Potrai modificare la grafica della chat off tramite il file style22.css. Vietato modificare il codice PHP. Per modifiche varie ed eventuali che si vuole apportare al codice, è necessaria l'autorizzazione di Axel, reperibile alla mail: stafffairytail@gmail.com
    *** Fermo restando i punti sopra citati, la chat non dovrai far altro che inserire i file nella directory principale del tuo sito e poi creare il link al file "chatoff.php" dove più ritieni opportuno sul tuo sito.
	*** Enjoy
	*/




    session_start();
								  

	header('Content-Type:text/html; charset=UTF-8');													  

	//Includio i parametri, la configurazione, la lingua e le funzioni
	require 'includes/constant_values.inc.php';
	require 'config.inc.php';
	require 'vocabulary/'.$PARAMETERS['languages']['set'].'.vocabulary.php';
	require 'includes/functions.inc.php';
    require'header.inc.php'; /*Header comune*/

	//Eseguo la connessione al database
	$handleDBConnection = gdrcd_connect();



if(gdrcd_filter('get', $_POST['op']) == 'erase') {

gdrcd_query("DELETE FROM chat_letta"); /* CANCELLO PG*/

$fp = fopen("log.html", 'w');
	  fwrite($fp, "<div class='msgln'></div>");
	  fclose($fp);
    
}

		$_SESSION['name'] = $_SESSION['login'];
	
gdrcd_query("DELETE FROM chat_letta WHERE nome = '". $_SESSION['login'] ."'"); /* CANCELLO PG*/
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Chattina</title>
<link rel="stylesheet" href="themes/<?php echo $PARAMETERS['themes']['current_theme']; ?>/chattina.css" type="text/css" />

</head>


<div id="wrapper">
	<div id="menu">
		<p class="welcome">Welcome, <b><?php echo $_SESSION['login']; ?></b></p>
		<p class="logout"></p>
		<div style="clear:both"></div>
	</div>	
	<div id="chatbox"><?php
	if(file_exists("log.html") && filesize("log.html") > 0){
		$handle = fopen("log.html", "r");
		$contents = fread($handle, filesize("log.html"));
		fclose($handle);
		
		echo $contents;
	}
	?></div>
	
	<form name="message" action="">
		<input name="usermsg" type="text" id="usermsg" size="63" />
		<input name="submitmsg" type="submit"  id="submitmsg" value="Send" />
	</form>
         
         <?php
         if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['moderatore'] == 1) { ?>
         <form name=comandimaster2 action="chattina_off.php" target=chatinput method=POST>
         <input type="hidden" name="op" value="erase" />
         <input type="submit" value="Pulisci la chat"/>
         <?php } ?>
</div>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>
<script type="text/javascript">
// jQuery Document
$(document).ready(function(){
	//If user submits the form
	$("#submitmsg").click(function(){	
		var clientmsg = $("#usermsg").val();
		$.post("post.php", {text: clientmsg});				
		$("#usermsg").attr("value", "");
		return false;
	});
	
	//Load the file containing the chat log
	function loadLog(){		
		var oldscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
		$.ajax({
			url: "log.html",
			cache: false,
			success: function(html){		
				$("#chatbox").html(html); //Insert chat log into the #chatbox div				
				var newscrollHeight = $("#chatbox").attr("scrollHeight") - 20;
				if(newscrollHeight > oldscrollHeight){
					$("#chatbox").scrollTop(0); //Autoscroll to bottom of div
				}				
		  	},
		});
	}
	setInterval (loadLog, 2500);	//Reload file every 2.5 seconds
	
	//If user wants to end session
	$("#exit").click(function(){
		var exit = confirm("Are you sure you want to end the session?");
		if(exit==true){window.location = 'chattina_off.php?logout=true';}		
	});
});
</script>

</body>
</html>