<?php
//carico tutto ciò di cui ho bisogno
$abilita = gdrcd_query("SELECT clgpersonaggioabilita.id_abilita, grado FROM clgpersonaggioabilita LEFT JOIN abilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_SESSION['login'])."' AND abilita.tipo!='Talento' AND abilita.tipo!='Default' AND abilita.tipo!='Difesa' AND abilita.tipo!='Temporanea'", 'result');
$info_pg = gdrcd_query("SELECT esperienza, esperienza_r, car0, car1, car2, car3, car4, car5, car6, car7, punto_skill FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
$id_ruolo_pg = gdrcd_query("SELECT id_ruolo FROM clgpersonaggioruolo WHERE personaggio='".gdrcd_filter('in', $_SESSION['login'])."'");
$id_ruolo_pg = $id_ruolo_pg['id_ruolo'];
$livello_ruolo_gilda = gdrcd_query("SELECT livello FROM ruolo WHERE id_ruolo='".gdrcd_filter('in', $id_ruolo_pg)."'");
$livello_ruolo_gilda = $livello_ruolo_gilda['livello'];
        #controllo per il tetto massimo
//calcolo l'esperienza che il personaggio ha già speso per le abilità 
$tot_esperienza_abilita = 0;
while($row = gdrcd_query($abilita, 'fetch')){
		if($row['grado'] >= 1){
	$tot_esperienza_abilita = $tot_esperienza_abilita + (($row['grado']*5));
    }
	}
//calcolo i punti per stats avuti per il grado in gilda 
$tot_stats_ruolo = $livello_ruolo_gilda * 40;
//calcolo la somma di tutte le sue stats
$tot_stats = $info_pg['car0'] + $info_pg['car2'] + $info_pg['car4'] + $info_pg['car6'];
//calcolo le stats che ha aumentato senza il ruolo 
$exp_usata = $info_pg['esperienza'] - $info_pg['esperienza_r'];
//stats solo esperienza
$tot_stats_pre = $info_pg['car0']-$info_pg['car1'] + $info_pg['car2']-$info_pg['car3']+ $info_pg['car4']-$info_pg['car5'] + $info_pg['car6']-$info_pg['car7'];

if($tot_stats_pre-40 >=260){
    $info_pg['esperienza_r'] = 0;
    }
//Se si tolgono le tre skill di default a 1 è da togliere il + 15
$exp_no_abilita = $exp_usata - $tot_esperienza_abilita;

//Valuto l'esperienza e in base a quello devo fare tutto il calcolo
$exp_personaggio = gdrcd_query("SELECT esperienza FROM personaggio WHERE nome='".$_SESSION['login']."'");
$esperienza_divisione = $exp_personaggio['esperienza'];

if ($esperienza_divisione > 0 AND $esperienza_divisione < 101) {
$risultato_divisione=5;
} else if ($esperienza_divisione > 100 AND $esperienza_divisione < 501) {
$risultato_divisione=10;
} else if ($esperienza_divisione > 500 AND $esperienza_divisione < 751) {
$risultato_divisione=15;
} else if ($esperienza_divisione > 750 AND $esperienza_divisione < 1001) {
$risultato_divisione=20;
} else if ($esperienza_divisione > 1000 AND $esperienza_divisione < 1051) {
$risultato_divisione=25;
} else if ($esperienza_divisione > 1500) {
$risultato_divisione=30;
}

//calcolo quindi le stats incrementate 
$tot_stats_incremento = ceil(($exp_no_abilita/5)); 

$stats_rimanenti = $tot_stats - $tot_stats_incremento - 40; 

      if(!isset($_REQUEST['pg'])){
      		$_REQUEST['pg'] = $_SESSION['login']; 
            }
			
$stats_gilda = $info_pg['car1'] + $info_pg['car3']+ $info_pg['car5'] + $info_pg['car7'] + $info_pg['punto_skill'];
$tot_stats_ruolo = $livello_ruolo_gilda * 40;
$remaining = $tot_stats_ruolo - $stats_gilda;

$somma_stats = $_POST['car0'] + $_POST['car2'] + $_POST['car4'] + $_POST['car6']; 
$tot_togliere = $somma_stats*5; 
$somma_stats_gilda = $_POST['car1'] + $_POST['car3'] + $_POST['car5'] + $_POST['car7'] + $_POST['skill']; 

if((gdrcd_filter('get', $_REQUEST['op']) == 'addstat') && (($_SESSION['login'] == gdrcd_filter('out', $_REQUEST['pg'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==2))) {

     if((($info_pg['esperienza_r']/5 >= 1) && (gdrcd_filter('get', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) {
           
           //vedo se può
           if ($somma_stats <= $info_pg['esperienza_r']/5) {
           $query = "UPDATE personaggio SET car0 = car0 + ".$_POST['car0'].", car2 = car2 + ".$_POST['car2'].", car4 = car4 + ".$_POST['car4'].", car6 = car6 + ".$_POST['car6'].", esperienza_r = esperienza_r - $tot_togliere WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";
           gdrcd_query($query);
           echo "<script type='text/javascript'>alert('Parametri aggiornati!');</script>";
           } else {
           echo "<script type='text/javascript'>alert('Non hai abbastanza punti! Riprova!');</script>";
           }
            
    }
} else if((gdrcd_filter('get', $_REQUEST['op']) == 'addstatgilda') && (($_SESSION['login'] == gdrcd_filter('out', $_REQUEST['pg'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==2))){
	if($remaining >=1){}
	//vedo se può
           if ($somma_stats_gilda <= $remaining) {
           $query = "UPDATE personaggio SET car0 = car0 + ".$_POST['car0'].", car1 = car1 + ".$_POST['car0'].", car2 = car2 + ".$_POST['car2'].", car3 = car3 + ".$_POST['car2'].", car4 = car4 + ".$_POST['car4'].", car5 = car5 + ".$_POST['car4'].", car6 = car6 + ".$_POST['car6'].", car7 = car7 + ".$_POST['car6'].", punto_skill = punto_skill + ".$_POST['skill']." WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";
           gdrcd_query($query);
           
           if ($_POST['skill'] > 0) {
           $exp_s = "UPDATE personaggio SET esperienza_s = esperienza_s + ".$_POST['skill']." WHERE nome = '".gdrcd_filter('get', $_SESSION['login'])."'";
           gdrcd_query($exp_s);
           }
           
           //inserisco nei log
           $log_registro = "INSERT INTO log_spesa (nome, destrezza, forza, mente, tempra, punto_skill, livello) VALUES ('".$_SESSION['login']."', ".$_POST['car2'].", ".$_POST['car0'].", ".$_POST['car4'].", ".$_POST['car6'].", ".$_POST['skill'].", ".$livello_ruolo_gilda.")";
		   gdrcd_query($log_registro);
           
           echo "<script type='text/javascript'>alert('Parametri aggiornati!');</script>";
           } else {
           echo "<script type='text/javascript'>alert('Non hai abbastanza punti! Riprova!');</script>";
           }
}

?>
<div class="pagina_gestione_mercato">
<div class="page_title"><h2><?php echo gdrcd_filter('out','Aumento statistiche'); ?></h2>
    </div>
    <div class="page_body">
    <?php 
    //ricalcolo per i valori aggiornati
	$abilita = gdrcd_query("SELECT clgpersonaggioabilita.id_abilita, grado FROM clgpersonaggioabilita LEFT JOIN abilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_SESSION['login'])."' AND abilita.tipo!='Talento' AND abilita.tipo!='Default' AND abilita.tipo!='Difesa' AND abilita.tipo!='Temporanea'", 'result');
    $info_pg = gdrcd_query("SELECT esperienza, esperienza_r, car0, car1, car2, car3, car4, car5, car6, car7, punto_skill FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
    $id_ruolo_pg = gdrcd_query("SELECT id_ruolo FROM clgpersonaggioruolo WHERE personaggio='".gdrcd_filter('in', $_SESSION['login'])."'");
    $id_ruolo_pg = $id_ruolo_pg['id_ruolo'];
    $livello_ruolo_gilda = gdrcd_query("SELECT livello FROM ruolo WHERE id_ruolo='".gdrcd_filter('in', $id_ruolo_pg)."'");
    $livello_ruolo_gilda = $livello_ruolo_gilda['livello'];
    
    
    //calcolo l'esperienza che il personaggio ha già speso per le abilità 
    $tot_esperienza_abilita = 0;
while($row = gdrcd_query($abilita, 'fetch')){
		if($row['grado'] >= 1){
	$tot_esperienza_abilita = $tot_esperienza_abilita + (($row['grado']*5));
    }
	}
//calcolo i punti per stats avuti per il grado in gilda 
$tot_stats_ruolo = $livello_ruolo_gilda * 40;
//calcolo la somma di tutte le sue stats meno quelle iniziali 
$tot_stats = $info_pg['car0'] + $info_pg['car2'] + $info_pg['car4'] + $info_pg['car6'];
//calcolo le stats che ha aumentato senza il ruolo 
$exp_usata = $info_pg['esperienza'] - $info_pg['esperienza_r'];

        #controllo per il tetto massimo
        $tot_stats_pre = $info_pg['car0']-$info_pg['car1'] + $info_pg['car2']-$info_pg['car3']+ $info_pg['car4']-$info_pg['car5'] + $info_pg['car6']-$info_pg['car7'];
        if($tot_stats_pre-40 >=260){
        	$info_pg['esperienza_r'] = 0;
        }
        
#echo "<script type='text/javascript'>alert('$exp_usata');</script>";
#echo "<script type='text/javascript'>alert('$tot_esperienza_abilita');</script>";
//Se si tolgono le tre skill di default a 1 è da togliere il + 15
$exp_no_abilita = $exp_usata - $tot_esperienza_abilita;
//calcolo quindi le stats incrementate 
$tot_stats_incremento = ceil(($exp_no_abilita/5)); 

#$exppp = $info_pg['esperienza'];
#echo "<script type='text/javascript'>alert('$exppp');</script>";
#echo "<script type='text/javascript'>alert('$exp_usata');</script>";
#echo "<script type='text/javascript'>alert('$tot_esperienza_abilita');</script>";
#$calcolo_matto = $info_pg['esperienza'] - $info_pg['esperienza_r'] - $tot_esperienza_abilita - ($tot_stats_pre-40)*5;
#$calcolo_matto = abs($calcolo_matto); 
#echo "<script type='text/javascript'>alert('$calcolo_matto');</script>";
#echo "<script type='text/javascript'>alert('$exp_usata');</script>";
#echo "<script type='text/javascript'>alert('$tot_esperienza_abilita');</script>";
#echo "<script type='text/javascript'>alert('$exp_no_abilita');</script>";
#echo "<script type='text/javascript'>alert('$tot_stats');</script>";
#echo "<script type='text/javascript'>alert('$tot_stats_incremento');</script>";
$stats_rimanenti = $tot_stats - $tot_stats_incremento - 40; 
echo "<div class='warning'>Il reset del personaggio avviene solo per errori tecnici, non per errori di distribuzione</div>";

    ?>
    <h3>La tua esperienza ti dà diritto a <u><?php echo ($info_pg['esperienza_r']/5); ?></u> (max: <u>260</u>) punti per incrementare le tue statistiche.</h3>
    <br>
    <?php 
    $da_mostrare = $tot_stats_ruolo-$stats_rimanenti;
    if ($da_mostrare < 0){
    	$da_mostrare = 0;
    }
    ?>
 <br><br>
<form action="main.php?page=stats_level_up" method="post">
<table width="215" border="0" align="center" class="customTable">
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Forza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if((($info_pg['esperienza_r']/5 >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==2)) { ?>
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
    <td width="74" style="color: #8f8f8f;"><strong>Destrezza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if((($info_pg['esperienza_r']/5 >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
      if((($info_pg['esperienza_r']/5 >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
      if((($info_pg['esperienza_r']/5 >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
<input type="hidden" name="op" value="addstat" />
 <input type="submit" value="Aumenta parametri" style="width: 150px;"> 
</form>
<?php

$stats_gilda = $info_pg['car1'] + $info_pg['car3']+ $info_pg['car5'] + $info_pg['car7'] + $info_pg['punto_skill'];
$tot_stats_ruolo = $livello_ruolo_gilda * 40;
#echo "<script type='text/javascript'>alert('$stats_gilda');</script>";
#echo "<script type='text/javascript'>alert('$tot_stats_ruolo');</script>";
$remaining = $tot_stats_ruolo - $stats_gilda;

?>

<br><br>

    <h3>Il tuo ruolo ti dà diritto a <u><?php echo ($remaining) ?></u> punti per incrementare le tue statistiche.</h3> 
 <br><br>
<form action="main.php?page=stats_level_up" method="post">
<table width="215" border="0" align="center" class="customTable">
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Forza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      // This is in the PHP file and sends a Javascript alert to the client
      if((($remaining >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
    <td width="74" style="color: #8f8f8f;"><strong>Destrezza</strong></td>
    <td width="25" style="color: #8f8f8f;">
      <?php /*Stampo il form di incremento se il pg ha abbastanza px*/
      if((($remaining >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
      if((($remaining >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
      if((($remaining >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
      if((($remaining >= 1) && (gdrcd_filter('out', $_REQUEST['pg']) == $_SESSION['login'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
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
<input type="hidden" name="op" value="addstatgilda" />
 <input type="submit" value="Aumenta parametri" style="width: 150px;"> 
</form>
</div>