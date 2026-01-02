	
<?/** * Chat Off Per GDRCD Extreme
	@Author Axel di Fairy Tail Universe GDR
	*** Potrai liberamente utilizzare questa chat off compatibile in tutto e per tutto con GDRCD fino alla versione Extreme riconoscendo la paternit� del codice all'autore e non rimuovendo questo commento. 
	*** Nel caso in cui il commento venisse rimosso, l'autore dei codici non doner� il consenso all'utilizzo del codice sottostante.
	*** La vendita del codice � vietata, poich� rilasciata in termini d'uso totalmente gratuiti.
	*** Il creatore del codice non si assume nessuna responsabilit� in merito a malfunzionamenti o bug.
	*** Potrai modificare la grafica della chat off tramite il file style22.css. Vietato modificare il codice PHP. Per modifiche varie ed eventuali che si vuole apportare al codice, � necessaria l'autorizzazione di Axel, reperibile alla mail: stafffairytail@gmail.com
    *** Fermo restando i punti sopra citati, la chat non dovrai far altro che inserire i file nella directory principale del tuo sito e poi creare il link al file "chatoff.php" dove pi� ritieni opportuno sul tuo sito.
	*** Enjoy
	*/
session_start();
	header('Content-Type:text/html; charset=UTF-8');													  

	//Includio i parametri, la configurazione, la lingua e le funzioni
	require 'includes/constant_values.inc.php';
	require 'config.inc.php';
	require 'vocabulary/'.$PARAMETERS['languages']['set'].'.vocabulary.php';
	require 'includes/functions.inc.php';

	//Eseguo la connessione al database
	$handleDBConnection = gdrcd_connect();


if(isset($_SESSION['name'])){
	$filename = "log.html";
	$text = $_POST['text'];

    $updatesep = "<div class='msgln'>(".date("m.d.y, g:i A").") <b>".$_SESSION['name']."</b>: ".stripslashes(htmlspecialchars($text))."<br></div>";
    $content = file_get_contents($filename);
    
	$fp = fopen($filename, "w");
    
    if($fp){
    fwrite($fp, ($updatesep . $content));
    fclose($fp);
           }
           
                      $_SESSION['name'] = $_SESSION['login'];
           

$query = gdrcd_query("SELECT nome FROM personaggio WHERE nome != '" . $_SESSION['login'] . "' AND DATE_ADD(ora_entrata, INTERVAL 14 DAY) >= NOW()", 'result');
while ($row = gdrcd_query($query, 'fetch'))
            {
                gdrcd_query("INSERT INTO chat_letta (nome) VALUES ('" . $row['nome'] . "')");
            }

}
?>