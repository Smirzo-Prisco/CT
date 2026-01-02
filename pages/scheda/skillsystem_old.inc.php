<script type="text/javascript"> 
  function showHideRow(row) { 
    $("#" + row).toggle(); 
  } 
</script> 
<link rel="stylesheet" href="../themes/crystal/famiglie.css">
<?php
//carico le sole abilità del pg
$abilita = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome='".gdrcd_filter('in', $_REQUEST['pg'])."'", 'result');

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
<div class="elenco_abilita"><!-- Elenco abilità gilda-->
    <div class="titolo_box">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['guild_skills']); ?>
    </div>
    <?php
    //Incremento skill
    if((gdrcd_filter('get', $_REQUEST['op']) == 'addskill') && (($_SESSION['login'] == gdrcd_filter('out', $_REQUEST['pg'])) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1))) {
		
		$esperienza_rimanente = gdrcd_query("SELECT esperienza_r FROM personaggio WHERE nome='".gdrcd_filter('in', $_REQUEST['pg'])."'");

		
        if(($esperienza_rimanente/5) >= 1) {
            $ranks[$_REQUEST['what']]++;
            $query = "UPDATE clgpersonaggioabilita SET grado = ".$ranks[$_REQUEST['what']]." WHERE id_abilita = ".gdrcd_filter('num', $_REQUEST['what'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
            gdrcd_query($query);
            $query = "UPDATE personaggio SET esperienza_r = esperienza_r - 5 WHERE nome = '".gdrcd_filter('in', $_REQUEST['pg'])."'";
            gdrcd_query($query);
            echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';
        	}
        }//Fine incremento skill
    //Decremento skill
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
    }//Fine decremento skill
    //conteggio le abilità
    $row = gdrcd_query("SELECT COUNT(*) FROM abilita WHERE id_razza=-1 OR id_razza= ".$personaggio['id_razza']."");
    $num = $row['COUNT(*)'];

    //carico l'elenco delle abilità
    $result = gdrcd_query("SELECT nome, car, id_abilita FROM abilita WHERE id_razza=-1 AND id_gilda != 0 ORDER BY id_gilda DESC, nome", 'result');
    $result_total = array();
    $request_pg = $_REQUEST['pg'];
    $result_total[0] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Default' AND abilita.id_gilda != 0 AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[1] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Generica base' AND abilita.tipo='Generica avanzata' AND abilita.id_gilda != 0 AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[2] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Attacco base' AND abilita.tipo='Attacco medio' AND abilita.tipo='Attacco avanzato' AND abilita.id_gilda != 0 AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[3] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Mentale base' AND abilita.tipo='Mentale media' AND abilita.tipo='Mentale avanzata' AND abilita.tipo='Mentale di attacco' AND abilita.id_gilda != 0 AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[4] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Difesa' AND abilita.id_gilda != 0 AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    $result_total[5] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.car, abilita.descrizione, abilita.id_razza, abilita.id_gilda, abilita.max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, clgpersonaggioabilita.grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Talento' AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.nome", 'result');
    $result_total[6] = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.car, abilita.descrizione, abilita.id_razza, abilita.id_gilda, abilita.max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, clgpersonaggioabilita.grado, clgpersonaggioabilita.usi FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE abilita.tipo='Skill temporanea' AND clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.nome", 'result');


    $total = 0;
    ?>

    <div class="div_colonne_abilita_scheda">
    					
                        <?php 
                        $total_count = 0;
                        foreach ($result_total as $skills_set){
                        $count = 0; 
                         while($row = gdrcd_query($skills_set, 'fetch')) {
                                                 
                    if($count == 0) {
                        echo '<table class="customTable"><tr class="second_header"><td>'.$row['tipo'].'</td></tr>';
                    } ?>
                    <tr>
                        <td>
                          
                            <?php
                            $id = gdrcd_filter('out', $row['id_abilita']);
                            $addr = "skill_desc.proc.php".$id;
                            #$to = "window.open('skill_desc.proc.php?id=$id','Log','toolbar=no,width=500,height=500');";
                            $to = "showHideRow('row$total_count')";
                            ?>
                                
                                      
                                      
                              <a href="javascript:void(0);" onClick="<?php echo $to ?>">
                               <span style="color: #8f8f8f; text-transform: none; font-size: 12px;">
                               <?php 
                               //prova mex
                                            $nome = gdrcd_filter('out', $row['nome_abilita']);
                                           
                                            $livello = 0 + gdrcd_filter('out', $ranks[$row['id_abilita']]);
                                            echo "<b>$nome</b> <i>Livello $livello</i> "; 
                                            if ($row['tipo'] == 'Temporanea') {
                                            echo " (n° usi: x".$row['usi'].")";
                                            } 
                                            ?>
                                        </span>    
                                        </a>
                       
                        </td>
                       <?php /* <td>
                            <div class="abilita_scheda_car">
                                <?php echo '('.gdrcd_filter('out', $PARAMETERS['names']['stats']['car'.$row['car']]).')'; ?>
                            </div>
                        </td> 
                        <td>
                            <div class="abilita_scheda_tank">
                                <?php
                                $livello = 0 + gdrcd_filter('out', $ranks[$row['id_abilita']]);
                                echo "Livello $livello"; ?>
                            </div>
                        </td>
                        <td>
                            <div class="abilita_scheda_sub">
                                <?php 
                                if((((($ranks[$row['id_abilita']] + 1) * $PARAMETERS['settings']['px_x_rank']) <= ($px_totali_pg - $px_spesi)) && (gdrcd_filter('get', $_REQUEST['pg']) == $_SESSION['login']) && ($ranks[$row['id_abilita']] < $PARAMETERS['settings']['skills_cap'])) || ($_SESSION['permessi'] >= MODERATOR)) { ?>
                                    [<a href="main.php?page=scheda_skills&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']
                                    ) ?>&op=addskill&what=<?php echo $row['id_abilita'] ?>">+</a>]
                                    <?php if(($_SESSION['permessi'] >= MODERATOR) && ($ranks[$row['id_abilita']] > 0)) { ?>
                                        [<a href="main.php?page=scheda_skills&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']) ?>&op=subskill&what=<?php echo $row['id_abilita'] ?>">-</a>]
                                    <?php
                                    }
                                } else {
                                    echo '&nbsp;';
                                }
                                ?>
                            </div>
                        </td> */?>
                    </tr>
                    <tr id="row<?php echo $total_count;?>" class="hidden_row">
                    <td style="color: #8f8f8f; text-transform: none;">
                    <?php echo $row['descrizione']; ?>
                    </td>
                    </tr>
                    <?php
                    
                    $total++;
                    if($count == mysqli_num_rows($skills_set)) {
                        
                        echo '</table>';
                    }
                    $count++;
                    $total_count++;
                }//while
                
            gdrcd_query($skills_set, 'free');
            }
            ?>
        
</div>