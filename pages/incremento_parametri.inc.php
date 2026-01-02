<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<?php
//prendo le informazioni del personaggio
$info_pg = gdrcd_query("SELECT esperienza, esperienza_r, shin, car0, car1, car2, car3, car4, car5, car6, car7, car8, car9, punto_skill FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
//subito dopo calcolo il totale delle stats che si è potenziato
$tot_stats = $info_pg['car0'] + $info_pg['car2'] + $info_pg['car4'] + $info_pg['car6'] + $info_pg['car8'];
//stats solo esperienza
$tot_stats_pre = $info_pg['car0']-$info_pg['car1'] + $info_pg['car2']-$info_pg['car3']+ $info_pg['car4']-$info_pg['car5'] + $info_pg['car6']-$info_pg['car7'] + $info_pg['car8']-$info_pg['car9'];




//se tutto queste superano i 260, esclusi i 50 iniziali, blocco tutto
//if($tot_stats_pre-50 >=260){
if($tot_stats_pre-50 >=260){
$info_pg['esperienza_r'] = 0;
}
$esperienza_r = $info_pg['esperienza_r'];
$esperienza = $info_pg['esperienza'];

// Ottenere il valore dell'esperienza del personaggio
$info_pg1 = gdrcd_query("SELECT esperienza, shin FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");

    // Ottieni il valore dell'esperienza dal risultato della query
    $esperienza_personaggio = $info_pg1['esperienza'];
    $shin = number_format($info_pg1['shin'], 1);

    // Inizializza il numero di punti statistica disponibili
$punti_statistica_disponibili = 0;
// Definisci gli scaglioni di esperienza e i relativi rapporti punti esperienza - punti statistica
$scaglioni_esperienza = array(
    array("inizio" => 0, "fine" => 100, "rapporto" => 5),
    array("inizio" => 101, "fine" => 500, "rapporto" => 10),
    array("inizio" => 501, "fine" => 800, "rapporto" => 15),
    array("inizio" => 801, "fine" => 1200, "rapporto" => 20),
    array("inizio" => 1201, "fine" => 1600, "rapporto" => 25),
    array("inizio" => 1601, "fine" => PHP_INT_MAX, "rapporto" => 30)
);

// Trova lo scaglione corretto dell'esperienza
$scaglione_esperienza_personaggio = -1;
foreach ($scaglioni_esperienza as $scaglione) {
    if ($esperienza_personaggio >= $scaglione["inizio"] && $esperienza_personaggio <= $scaglione["fine"]) {
        $scaglione_esperienza_personaggio = $scaglione;
        break;
    }
}

// Calcola i punti statistica disponibili escludendo lo scaglione in cui rientra l'esperienza del personaggio
$escludi_scaglione = false;
foreach ($scaglioni_esperienza as $scaglione) {
    if ($scaglione == $scaglione_esperienza_personaggio) {
        $escludi_scaglione = true;
        continue;
    }
    
    if (!$escludi_scaglione) {
        // Calcola i punti statistica per lo scaglione corrente
        $punti_scaglione = floor(($scaglione["fine"] - $scaglione["inizio"] + 1) / $scaglione["rapporto"]);
        $punti_statistica_disponibili += $punti_scaglione;
        
        // Stampa i dettagli dello scaglione
        //echo "Scaglione: da {$scaglione['inizio']} a {$scaglione['fine']} - Punti statistica: $punti_scaglione<br>";
    }
}

// Stampa il conteggio dei punti statistica disponibili
//echo "Punti statistica totali disponibili: " . $punti_statistica_disponibili;
// Calcola quanta esperienza è esclusa dal conteggio
$esperienza_esclusa = ($esperienza_personaggio - $scaglione_esperienza_personaggio['inizio']);

//echo "<br>Esperienza esclusa dal conteggio: $esperienza_esclusa";

// Calcola i punti statistica da aggiungere
$punti_da_aggiungere = $esperienza_esclusa / $scaglione_esperienza_personaggio["rapporto"];
//echo "<br>Punti statistica da aggiungere: $punti_da_aggiungere";


// Calcola il risultato dei punti statistica disponibili dopo l'aggiunta dei punti da aggiungere
$risultato_punti_statistica_disponibili = $punti_statistica_disponibili + $punti_da_aggiungere;

// Stampiamo i risultati
//echo "<br>Risultato dei punti statistica disponibili dopo l'aggiunta dei punti da aggiungere:". number_format($risultato_punti_statistica_disponibili, 1) ."";

$punti_esperienza_r_necessari = $risultato_punti_statistica_disponibili * $scaglione_esperienza_personaggio["rapporto"];
//echo "<br>Punti esperienza_r necessari: $punti_esperienza_r_necessari";






?>

<div class="pagina_gestione_mercato">
<div class="page_title"><h2><?php echo gdrcd_filter('out','Aumento statistiche'); ?></h2>
</div>
    <div class="page_body">
<?php
//avviso reset
echo "<div class='warning'>Il reset del personaggio avviene solo per errori tecnici, non per errori di distribuzione</div>";
?>



<?php 
// Ottenere il valore dell'esperienza del personaggio
$info_pg1 = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");

// Ottieni il valore dell'esperienza dal risultato della query
$esperienza_personaggio = $info_pg1['esperienza'];

// Inizializza il numero di punti statistica disponibili
$punti_statistica_disponibili = 0;

// Trova lo scaglione corretto dell'esperienza
$scaglione_esperienza_personaggio = -1;
foreach ($scaglioni_esperienza as $scaglione) {
    if ($esperienza_personaggio >= $scaglione["inizio"] && $esperienza_personaggio <= $scaglione["fine"]) {
        $scaglione_esperienza_personaggio = $scaglione;
        break;
    }
}

// Calcola il rapporto per lo scaglione corrente
$rapporto_scaglione = $scaglione_esperienza_personaggio["rapporto"];

// Calcola i punti statistica disponibili utilizzando il rapporto corretto
$punti_statistica_corretti = $info_pg['esperienza_r'] / $rapporto_scaglione;

// Stampa il risultato corretto
echo "<h3>La tua esperienza ti dà diritto a <u>". number_format($punti_statistica_corretti, 1) ."</u> punti statistica</h3>";









// Calcolo dei punti già assegnati alle statistiche basate sull'esperienza
$punti_assegnati = $info_pg['car0']  - $info_pg['car1'] + $info_pg['car2']  - $info_pg['car3'] + $info_pg['car4'] - $info_pg['car5'] + $info_pg['car6'] - $info_pg['car7'] + $info_pg['car8'] - $info_pg['car9'];

// Punti assegnati escluse le prime 50 unità (punti iniziali)
$punti_assegnati_effettivi = $punti_assegnati - 50;

// Calcolo dei punti rimanenti
$punti_rimanenti = 260 - $punti_assegnati_effettivi;

// Garantiamo che il calcolo non vada in negativo
if ($punti_rimanenti < 0) {
    $punti_rimanenti = 0;
}




// Calcolo dei punti Shin già utilizzati (somma delle caratteristiche dispari e dei punti skill)
$punti_shin_assegnati = $info_pg['car1'] + $info_pg['car3'] + $info_pg['car5'] + $info_pg['car7'] + $info_pg['car9'] + $info_pg['punto_skill'];

// Calcolo dei punti Shin rimanenti
$punti_shin_rimanenti = 240 - $punti_shin_assegnati;
if ($punti_shin_rimanenti < 0) {
    $punti_shin_rimanenti = 0;
}


?>

<!-- pannello parametri in base all'exp -->

<button onclick="apriPopup()" style="background-color: #444; color: #fff; border: 1px solid #777; padding: 10px; cursor: pointer;">Visualizza riepilogo punti</button>



<form action="main.php?page=incremento_parametri" method="post">
<table width="215" border="0" align="center" class="customTable">
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Forza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) { ?>
      <select name="car0">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Potere</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if ($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) { ?>
      <select name="car8">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Destrezza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) { ?>
      <select name="car2">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                         
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Mente</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) { ?>
      <select name="car4">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                         
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
      <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Tempra</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) { ?>
      <select name="car6">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                        
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
    
  </tr>
</table>
<?php
if($tot_stats_pre-50 < 260){
?>
<input type="hidden" name="op" value="addstat" />
 <input type="submit" value="Aumenta parametri" style="width: 150px;"> 
</form>
<?php
} else {
echo 'Hai raggiunto il massimo potenziabile';
}
?>
<br><br>

    <h3>Il tuo shin ti dà diritto a <u><?php echo ($shin) ?></u> punti per incrementare le tue statistiche.</h3> 
 <br><br>












<!-- qui inizia il pannello legato agli shin -->

<form action="main.php?page=incremento_parametri" method="post">
<table width="215" border="0" align="center" class="customTable">
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Forza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if ($shin >= 1) { ?>
      <select name="car0">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                         
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Potere</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if($shin >= 1) { ?>
      <select name="car8">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                         
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Destrezza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($shin >= 1) { ?>
      <select name="car2">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                       
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
    <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Mente</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($shin >= 1) { ?>
      <select name="car4">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                       
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
  </tr>
      <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Tempra</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($shin >= 1) { ?>
      <select name="car6">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                     
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
    
  </tr>
  
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Punti skill</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if ($shin >= 1) { ?>
      <select name="skill">
      <?php
      for ($j = 0; $j <= 100; $j++) {
      echo '<option value=\''.($j).'\'';
      if ($j == 0) {
      echo ' SELECTED';
      }
      echo '>'.($j).'</option>';
      }
      ?>
      </select>                                                                                     
       <?php } else {
        echo '&nbsp;';
      } ?>
    </td>
    
  </tr>
</table>
<?php

$stats_gildaa = $info_pg['car1'] + $info_pg['car3']+ $info_pg['car5'] + $info_pg['car7'] + $info_pg['car9'] + $info_pg['punto_skill'];

if($shin < 1) {
echo 'Non hai abbastanza punti shin';
} else if ($stats_gildaa >= 240) {
echo 'Hai raggiunto il massimo di shin utilizzabili (240)';
} else {
?>
<input type="hidden" name="op" value="addstatgilda" />
 <input type="submit" value="Aumenta parametri" style="width: 150px;"> 
</form>
<?php } ?>
</div>
</div>



















<div id="riepilogoPopup" class="popup">
  <div class="popup-content">
    <span class="close" onclick="chiudiPopup()">&times;</span>
    <h2>Riepilogo Punti</h2>

    <h3>Punti Esperienza (sono esclusi i 50 iniziali)</h3>
    <table>
      <tr><td>Forza</td><td><?php echo $info_pg['car0'] - $info_pg['car1'] - 10; ?></td></tr>
      <tr><td>Destrezza</td><td><?php echo $info_pg['car2'] - $info_pg['car3'] - 10; ?></td></tr>
      <tr><td>Mente</td><td><?php echo $info_pg['car4'] - $info_pg['car5'] - 10; ?></td></tr>
      <tr><td>Tempra</td><td><?php echo $info_pg['car6'] - $info_pg['car7'] - 10; ?></td></tr>
      <tr><td>Potere</td><td><?php echo $info_pg['car8'] - $info_pg['car9'] - 10; ?></td></tr>
      <tr><td colspan="2">Punti Esperienza usati: <?php echo $punti_assegnati_effettivi; ?></td></tr>
      <tr><td colspan="2">Punti Esperienza Rimanenti: <?php echo $punti_rimanenti; ?></td></tr>
    </table>

    <h3>Punti Shin</h3>
    <table>
      <tr><th>Statistica</th><th>Punti Assegnati</th></tr>
      <tr><td>Forza</td><td><?php echo $info_pg['car1']; ?></td></tr>
      <tr><td>Destrezza</td><td><?php echo $info_pg['car3']; ?></td></tr>
      <tr><td>Mente</td><td><?php echo $info_pg['car5']; ?></td></tr>
      <tr><td>Tempra</td><td><?php echo $info_pg['car7']; ?></td></tr>
      <tr><td>Potere</td><td><?php echo $info_pg['car9']; ?></td></tr>
      <tr><td>Punti Skill</td><td><?php echo $info_pg['punto_skill']; ?></td></tr>
      <tr><td colspan="2">Punti Shin usati: <?php echo $punti_shin_assegnati; ?></td></tr>
      <tr><td colspan="2">Punti Shin Rimanenti: <?php echo $punti_shin_rimanenti; ?></td></tr>
    </table>
  </div>
</div>

<style>
.popup {
  display: none; 
  position: fixed;
  z-index: 1; 
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%); /* Per centrare il popup */
  width: 50%; /* Ridimensiona la larghezza del popup */
  height: auto; /* Imposta l'altezza automatica per adattarsi al contenuto */
  background-color: rgba(0, 0, 0, 0.8); /* Sfondo semi-trasparente scuro */
  padding: 20px;
  box-sizing: border-box; /* Include padding e bordo nella larghezza e altezza */
  border-radius: 10px; /* Angoli arrotondati */
}

.popup-content {
  background-color: #222; /* Colore di sfondo scuro */
  color: #fff; /* Testo bianco */
  margin: auto;
  padding: 20px;
  border: 1px solid #777; /* Bordi più chiari per contrasto */
  width: 100%; /* Mantiene la larghezza del contenuto */
  text-align: center; /* Centra il testo */
}

.close {
  color: #bbb;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #fff;
  text-decoration: none;
  cursor: pointer;
}

</style>

<script>
function apriPopup() {
  document.getElementById("riepilogoPopup").style.display = "block";
}

function chiudiPopup() {
  document.getElementById("riepilogoPopup").style.display = "none";
}
</script>


<?php

//inizio aumento punti statistica in base all'exp

$somma_stats = $_POST['car0'] + $_POST['car2'] + $_POST['car4'] + $_POST['car6'] + $_POST['car8']; 
$somma_stats_gilda = $_POST['car0'] + $_POST['car2'] + $_POST['car4'] + $_POST['car6'] + $_POST['car8'] + $_POST['skill']; 
$tot_togliere = $somma_stats*$scaglione_esperienza_personaggio['rapporto'];

//stats gilda
$stats_gilda = $info_pg['car1'] + $info_pg['car3']+ $info_pg['car5'] + $info_pg['car7'] + $info_pg['car9'] + $info_pg['punto_skill'];
$tot_stats_ruolo = 240;
$remaining = $tot_stats_ruolo - $stats_gilda;

if (gdrcd_filter('get', $_REQUEST['op']) == 'addstat') {

     if ($info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto'] >= 1) {
           
           //vedo se può
           if ($somma_stats <= $info_pg['esperienza_r']/$scaglione_esperienza_personaggio['rapporto']) {
           $query = "UPDATE personaggio SET car0 = car0 + ".$_POST['car0'].", car2 = car2 + ".$_POST['car2'].", car4 = car4 + ".$_POST['car4'].", car6 = car6 + ".$_POST['car6'].", car8 = car8 + ".$_POST['car8'].", esperienza_r = esperienza_r - $tot_togliere WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";
           gdrcd_query($query);
           
           echo "<script type='text/javascript'>alert('Parametri aggiornati!');</script>";
           echo "<script type='text/javascript'> document.location = 'main.php?page=incremento_parametri'; </script>";
           } else {
           echo "<script type='text/javascript'>alert('Non hai abbastanza punti! Riprova!');</script>";
           echo "<script type='text/javascript'> document.location = 'main.php?page=incremento_parametri'; </script>";
           }
            
    }
}

//inizio aumento punti statistica in base agli shin

else if (gdrcd_filter('get', $_REQUEST['op']) == 'addstatgilda') {
	if($remaining >=1){}
	//vedo se può
           if ($stats_gilda < 240 AND $somma_stats_gilda <= $shin) {
           echo "<script type='text/javascript'>alert('Parametri aggiornati!');</script>";
           echo "<script type='text/javascript'> document.location = 'main.php?page=incremento_parametri'; </script>";
           
           $query = "UPDATE personaggio SET shin = shin - $somma_stats_gilda, car0 = car0 + ".$_POST['car0'].", car1 = car1 + ".$_POST['car0'].", car2 = car2 + ".$_POST['car2'].", car3 = car3 + ".$_POST['car2'].", car4 = car4 + ".$_POST['car4'].", car5 = car5 + ".$_POST['car4'].", car6 = car6 + ".$_POST['car6'].", car7 = car7 + ".$_POST['car6'].", car8 = car8 + ".$_POST['car8'].", car9 = car9 + ".$_POST['car8'].", punto_skill = punto_skill + ".$_POST['skill'].", esperienza_s = esperienza_s + ".$_POST['skill']." WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";           
           gdrcd_query($query);
           
           //inserisco nei log
           $log_registro = "INSERT INTO log_spesa (nome, destrezza, forza, mente, tempra, potere, punto_skill) VALUES ('".$_SESSION['login']."', ".$_POST['car2'].", ".$_POST['car0'].", ".$_POST['car4'].", ".$_POST['car6'].", ".$_POST['car8'].", ".$_POST['skill'].")";		   
           gdrcd_query($log_registro);
           
      } else {
           echo "<script type='text/javascript'>alert('Non hai abbastanza punti! Riprova!');</script>";
           echo "<script type='text/javascript'> document.location = 'main.php?page=incremento_parametri'; </script>";
           }
}
?>