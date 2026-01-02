<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<link rel="stylesheet" href="../themes/crystal/main.css">

<?php
if(!isset($_REQUEST['pg']))
{
	$_REQUEST['pg'] = $_SESSION['login']; 
}
    //Incremento skill
    if((gdrcd_filter('get', $_REQUEST['op']) == 'addskill') && (($_SESSION['login'] == gdrcd_filter('out', $_REQUEST['pg'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1))) {
		

		$esperienza_rimanente = gdrcd_query("SELECT esperienza_s FROM personaggio WHERE nome='".gdrcd_filter('in', $_REQUEST['pg'])."'");

		$lvl_ruolo_pg = gdrcd_query("SELECT ruolo.livello FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio='".gdrcd_filter('in', $_REQUEST['pg'])."'");
        $lvl_ruolo_pg = $lvl_ruolo_pg['livello'];
        $conteggio_generiche = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioabilita  JOIN abilita ON clgpersonaggioabilita.id_abilita=abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_REQUEST['pg'])."' AND abilita.tipo = 'Generica'");
        $conteggio_generiche = $conteggio_generiche['COUNT(*)'];
        $conteggio_attacco = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioabilita  JOIN abilita ON clgpersonaggioabilita.id_abilita=abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_REQUEST['pg'])."' AND abilita.tipo = 'Attacco'");
        $conteggio_attacco = $conteggio_attacco['COUNT(*)'];
        $abilita = gdrcd_query("SELECT tipo, max_lvl FROM abilita WHERE id_abilita = ".gdrcd_filter('num', $_REQUEST['what'])."");
        $livello = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE id_abilita='".gdrcd_filter('num', $_REQUEST['what'])."' AND nome='".gdrcd_filter('in', $_REQUEST['pg'])."'");

        
		if(!isset($livello['grado']) || !isset($abilita['max_lvl'])){
        $livello['livello'] = -1; 
        $abilita['max_lvl'] = -1; 
        }

		
        if(($conteggio_generiche < $lvl_ruolo_pg+1 || ($conteggio_generiche >= $lvl_ruolo_pg+1 && $abilita['tipo']!='Generica') || ($conteggio_generiche==8 && $lvl_ruolo_pg==8 && $abilita['tipo']=='Generica')) && (isset($_REQUEST['pg']) && isset($_REQUEST['what']))){
        $esperienza_rimanente = $esperienza_rimanente['esperienza_s'];
        ##check per capire se la sta apprendendo o meno
        $check_zero = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioabilita WHERE id_abilita = ".gdrcd_filter('num', $_REQUEST['what'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'");
        $check_zero = $check_zero['COUNT(*)'];
   

        if($check_zero == 0 && ($abilita['tipo']=='Default' || $abilita['tipo']=='Difesa')){

         $query = "INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('".gdrcd_filter('in', $_REQUEST['pg'])."', ".gdrcd_filter('num', $_REQUEST['what']).", 1)";
		 gdrcd_query($query);
         echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';
        }
        elseif(((int)$esperienza_rimanente / 1) >= 1 && $livello['grado'] < $abilita['max_lvl']) {
        	
            $ranks[$_REQUEST['what']]++;
            $query = "INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('".gdrcd_filter('in', $_REQUEST['pg'])."', ".gdrcd_filter('num', $_REQUEST['what']).", ".$ranks[$_REQUEST['what']].") ON DUPLICATE KEY UPDATE grado = grado + 1";
            //$query = "UPDATE clgpersonaggioabilita SET grado = ".$ranks[$_REQUEST['what']]." WHERE id_abilita = ".gdrcd_filter('num', $_REQUEST['what'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
            gdrcd_query($query);
            $query = "UPDATE personaggio SET esperienza_s = esperienza_s - 1 WHERE nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
            gdrcd_query($query);
            
            //prelevo il livello
            $id_ruolo_pg = gdrcd_query("SELECT id_ruolo FROM clgpersonaggioruolo WHERE personaggio='".gdrcd_filter('in', $_SESSION['login'])."'");
            $id_ruolo_pg = $id_ruolo_pg['id_ruolo'];
            $livello_ruolo_gilda = gdrcd_query("SELECT livello FROM ruolo WHERE id_ruolo='".gdrcd_filter('in', $id_ruolo_pg)."'");
            $livello_ruolo_gilda = $livello_ruolo_gilda['livello'];
            
            //inserisco
            $log_registro = "INSERT INTO log_spesa (nome, id_abilita, grado, livello) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('num', $_REQUEST['what'])."', '1', '".$livello_ruolo_gilda."')";
            gdrcd_query($log_registro);
            echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';
        	}
            }else{
            echo '<div class="warning">Hai già appreso il massimo numero di generiche per il tuo grado: '.$conteggio_generiche.' oppure hai provato a fare il birbante.</div>';
            }
        }//Fine incremento skill
    //Decremento skill
    /*
    if((gdrcd_filter('get', $_REQUEST['op']) == 'subskill') && ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) {
        if($ranks[$_REQUEST['what']] == 1) {
            $query = "DELETE FROM clgpersonaggioabilita WHERE id_abilita = ".$_REQUEST['what']." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1";
            $ranks[$_REQUEST['what']] = 0;
        } else {
            $ranks[$_REQUEST['what']]--;
            $query = "UPDATE clgpersonaggioabilita SET grado = ".$ranks[$_REQUEST['what']]." WHERE id_abilita = ".$_REQUEST['what']." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
        }//else
        gdrcd_query($query);
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';
    }*/ //Fine decremento skill 
    //conteggio le abilità
    //$row = gdrcd_query("SELECT COUNT(*) FROM abilita WHERE id_razza=-1 OR id_razza= ".$personaggio['id_razza']."");
    //$num = $row['COUNT(*)'];

    //carico l'elenco delle abilità
    $result = gdrcd_query("SELECT nome, car, id_abilita FROM abilita WHERE id_razza=-1 AND id_gilda != 0 ORDER BY id_gilda DESC, nome", 'result');
    $result_total = array();
    $request_pg = $_SESSION['login'];
    $query = "SELECT ruolo.nome_ruolo as nome_ruolo FROM ruolo LEFT JOIN personaggio ON ruolo.id_ruolo=personaggio.id_ruolo_gilda WHERE personaggio.nome = '".$request_pg."'";
    $nome_ruolo = gdrcd_query($query);
    $nome_ruolo = $nome_ruolo['nome_ruolo'];
    $result_total[0] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl,  car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita=clgpersonaggioabilita.id_abilita WHERE abilita.tipo='Default' AND ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[1] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita=clgpersonaggioabilita.id_abilita WHERE abilita.tipo='Generica' AND ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[2] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita=clgpersonaggioabilita.id_abilita WHERE abilita.tipo='Attacco' AND ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[3] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita=clgpersonaggioabilita.id_abilita WHERE abilita.tipo='Mentale' AND ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[4] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita=clgpersonaggioabilita.id_abilita WHERE abilita.tipo='Difesa' AND ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    //$result_total[5] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.id_razza!=-1 AND abilita.tipo='Talento' AND clgpersonaggioabilita.nome ='$request_pg'", 'result');    $count = 0;
    $total = 0;
    ?>
<?php
//carico le sole abilità del pg
$abilita = gdrcd_query("SELECT id_abilita, grado FROM clgpersonaggioabilita WHERE nome='".gdrcd_filter('in', $_REQUEST['pg'])."'", 'result');

$px_spesi = 0;
while($row = gdrcd_query($abilita, 'fetch')) {
    /*Costo in px della singola abilità*/
    $px_abi = $PARAMETERS['settings']['px_x_rank'] * (($row['grado'] * ($row['grado'] + 1)) / 2);
    /*Costo totale*/
    $px_spesi += $px_abi;
    $ranks[$row['id_abilita']] = $row['grado'];
}
gdrcd_query($abilita, 'free');

$px_totali_pg = $personaggio['esperienza'];
?>
    <div class="form_info">
        <?php 
        $esperienza_rimanente = gdrcd_query("SELECT esperienza_s FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
        $esperienza_rimanente = floor((int)$esperienza_rimanente['esperienza_s'] / 1);
        
        echo "<div class='warning'>Il reset del personaggio avviene solo per errori tecnici, non per errori di distribuzione</div>";
        echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['avalaible_skill_points']).': '.$esperienza_rimanente; 
        ?>
    </div>
<div class="elenco_abilita"><!-- Elenco abilità gilda-->

   
    <div class="div_colonne_abilita_scheda">
                <?php 
                
                foreach ($result_total as $skills_set){
                $count = 0;
                while($row = gdrcd_query($skills_set, 'fetch')) {
              		if($count == 0) {
                        echo '<table class="customTable"><tr class="second_header"><td colspan="3">'.$row['tipo'].'</td></tr>';
                    }
					?>
                    <tr>
                        <td width="40%">
                            <?php
                            $id = gdrcd_filter('out', $row['id_abilita']);
                            $addr = "skill_desc.proc.php".$id;
                            $to = "changeFrame('skill_desc.proc.php?id=$id');document.getElementById('id01').style.display='block'";
                            ?>
                                
                                      
                                      
                              <a href="#" onClick="<?php echo $to ?>">
                                            <?php echo gdrcd_filter('out', $row['nome_abilita']); ?>
                                        </a>
                        </td>

                        <td width="30%" style="color: #8f8f8f;">
                                <?php
                                $livello = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE id_abilita='".gdrcd_filter('out', $row['id_abilita'])."' AND nome='".$request_pg."'");
                                $livello = $livello['grado']; 
                                if(empty($livello)){
                                $livello = 0;
                                }
                                $max_lvl = (int) gdrcd_filter('out', $row['max_lvl']);

                                echo "Livello $livello"; ?>
                        </td>
                        <td width="13%" style="color: #8f8f8f;">
                            <div class="abilita_scheda_sub">
                                <?php 
                                $esperienza_rimanente = gdrcd_query("SELECT esperienza_s FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
                                $esperienza_rimanente = floor((int)$esperienza_rimanente['esperienza_s'] / 1);
                                $secondaria = gdrcd_query("SELECT MIN(ruolo.livello) FROM ruolo LEFT JOIN clgruoloabilita ON ruolo.nome_ruolo = clgruoloabilita.ruolo WHERE clgruoloabilita.abilita='".gdrcd_filter('in', $row['nome_abilita'])."'");
                                $secondaria = $secondaria['MIN(ruolo.livello)']; 
                                $lvl_ruolo_pg = gdrcd_query("SELECT ruolo.livello FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio='".gdrcd_filter('in', $_SESSION['login'])."'");
                                $lvl_ruolo_pg = $lvl_ruolo_pg['livello']; 
                                $conteggio_attacco = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioabilita  JOIN abilita ON clgpersonaggioabilita.id_abilita=abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_SESSION['login'])."' AND abilita.tipo = 'Attacco'");
                                $conteggio_attacco = $conteggio_attacco['COUNT(*)'];
                                $conteggio_mentali = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioabilita  JOIN abilita ON clgpersonaggioabilita.id_abilita=abilita.id_abilita WHERE clgpersonaggioabilita.nome='".gdrcd_filter('in', $_SESSION['login'])."' AND abilita.tipo = 'Mentale'");
                                $conteggio_mentali = $conteggio_mentali['COUNT(*)'];
                                
                                //conto livello
                                $checklivello = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome ='".$_SESSION['login']."'");
                               
                               if((($esperienza_rimanente >= 1) && ((gdrcd_filter('get', $_SESSION['login']) == $_SESSION['login']) || $_SESSION['admin']==1 || $_SESSION['moderatore']==1) && ($livello < $max_lvl))) { 
                                

                                ?>
                                    [<a href="main.php?page=mercato_abilita&pg=<?php echo gdrcd_filter('url', $_SESSION['login']
                                    ) ?>&op=addskill&what=<?php echo $row['id_abilita'] ?>&tipo=<?php echo $row['tipo'] ?>"><?php 
                                    if($row['tipo'] == 'Attacco' && $secondaria==3){
                                    	if($lvl_ruolo_pg==3){
                                        if($livello<2){if($livello==0 && $conteggio_attacco < 2){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                     	if($lvl_ruolo_pg==4){
                                        if($livello<3){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==5 || $lvl_ruolo_pg==6){
                                        if($livello<4){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                    }
                                    elseif($row['tipo'] == 'Attacco' && $secondaria<3){
                                     	if($lvl_ruolo_pg==1){
                                        if($livello<1){if($livello==0 && $conteggio_attacco < 1){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==2){
                                        if($livello<2){if($livello==0 && $conteggio_attacco < 1){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                                                            
                                        elseif($lvl_ruolo_pg==3) {
                                        if($livello<3){if($livello==0 && $conteggio_attacco < 2){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }   
                                        elseif($lvl_ruolo_pg==4 || $lvl_ruolo_pg==5 || $lvl_ruolo_pg==6){
                                        if($livello<3){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                    }
                                    elseif($row['tipo'] == 'Attacco' && $secondaria>3){
                                     	if($lvl_ruolo_pg==4){
                                        if($livello<3){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==5){
                                        if($livello<4){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                       
                                        elseif($lvl_ruolo_pg==6){
                                        if($livello<5){if($livello==0 && $conteggio_attacco < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                        
                                    }
                                    
                                    elseif($row['tipo'] == 'Mentale' && $secondaria==3){
                                    	if($lvl_ruolo_pg==3){
                                        if($livello<2){if($livello==0 && $conteggio_mentali < 2){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                     	if($lvl_ruolo_pg==4){
                                        if($livello<3){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==5 || $lvl_ruolo_pg==6){
                                        if($livello<4){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                    }
                                    elseif($row['tipo'] == 'Mentale' && $secondaria<3){
                                     	if($lvl_ruolo_pg==1){
                                        if($livello<1){if($livello==0 && $conteggio_mentali < 1){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==2){
                                        if($livello<2){if($livello==0 && $conteggio_mentali < 1){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                                                            
                                        elseif($lvl_ruolo_pg==3) {
                                        if($livello<3){if($livello==0 && $conteggio_mentali < 2){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }   
                                        elseif($lvl_ruolo_pg==4 || $lvl_ruolo_pg==5 || $lvl_ruolo_pg==6){
                                        if($livello<3){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                    }
                                    elseif($row['tipo'] == 'Mentale' && $secondaria>3){
                                     	if($lvl_ruolo_pg==4){
                                        if($livello<3){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }
                                        elseif($lvl_ruolo_pg==5){
                                        if($livello<4){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                       
                                        elseif($lvl_ruolo_pg==6){
                                        if($livello<5){if($livello==0 && $conteggio_mentali < 3){echo 'APPRENDI';} elseif($livello > 0) { echo '+';}}
                                        }                                        
                                    }
                                    else{
                                    if($livello==0){echo 'APPRENDI';} else{ echo '+';}
                                    }
                                    ?></a>]
                                    
                                    <?php if(($_SESSION['admin']==1 || $_SESSION['moderatore']==2) && ($ranks[$row['id_abilita']] > 0)) { ?>
                                        [<a href="main.php?page=mercato_abilita&pg=<?php echo gdrcd_filter('url', $_SESSION['login']) ?>&op=subskill&what=<?php echo $row['id_abilita'] ?>">-</a>]
                                    <?php
                                    }

                                } else {
                                    echo '&nbsp;';
                                }
                                ?>
                            </div>
                        </td> 
                    </tr>
                    <?php
                    
                    $total++;
                    if($count == mysqli_num_rows($skills_set)) {
       
                        echo '</table>';
                    }
                    $count++;
                }//while
            gdrcd_query($skills_set, 'free');
            }
            ?>
    </div>
    <div class="form_info" style="display: none;">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['info_skill_cost']); ?>
    </div>


</div>