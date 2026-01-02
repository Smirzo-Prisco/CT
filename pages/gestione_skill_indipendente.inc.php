<div class="pagina_gestione_manutenzione">
<?php
if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    /*Puoi entrare*/
    
    /*Selezioniamo liv*/
    $elenco_ruoli = gdrcd_query ("SELECT * FROM ruolo WHERE gilda > 0 AND gilda < 8 ORDER BY gilda, id_ruolo", 'result');
    $elenco_indipendenti = gdrcd_query ("SELECT * FROM ruolo WHERE gilda > 7 ORDER BY gilda, id_ruolo", 'result');
    
    //passo all'inoltro
    if(isset($_POST['op']) === true) {
    if(gdrcd_filter('get', $_POST['op']) == 'delete') {
    $nome_ruolo = $_POST['nome_ruolo'];
    $indipendente = $_POST['indipendente'];
    
    $skill_grado = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, abilita.max_lvl,  car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo FROM abilita LEFT JOIN clgruoloabilita ON abilita.nome = clgruoloabilita.abilita WHERE ruolo='".gdrcd_filter('in', $nome_ruolo)."' GROUP BY abilita.nome ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
    
    while ($row = gdrcd_query($skill_grado, 'fetch')) {
    $nome_skill = $row['nome_abilita']." - Indipendente";
    
    gdrcd_query("INSERT INTO abilita (nome, descrizione, car, id_razza, id_gilda, max_lvl, tipo) VALUES ('".gdrcd_filter('in', $nome_skill)."', '".gdrcd_filter('in', $row['descrizione'])."', ".gdrcd_filter('num', $row['car']).", -1, '8', ".gdrcd_filter('num', $row['max_lvl']).", '".gdrcd_filter('in', $row['tipo'])."')");
    gdrcd_query("INSERT INTO clgruoloabilita (ruolo, abilita) VALUES ('".gdrcd_filter('in', $indipendente)."','".gdrcd_filter('in', $nome_skill)."')");
    }
    }
    }
    ?>
    <center>
    <font size=2>Associa skill a ruolo indipendente</font><br/><br/>
    <br><br> 
          <form action="main.php?page=gestione_skill_indipendente" method="Post">
          <font size=4>Trasferisci le skill del grado</font><br/><br/>
          <select name="nome_ruolo">
          <?php      
                                
                                while($option = gdrcd_query($elenco_ruoli, 'fetch')) { ?>
                                <option value="<?php echo $option['nome_ruolo']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome_ruolo']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($elenco_ruoli, 'free');
                            ?>
          </select>
          <br><br>
          <font size=2>a</font><br/><br/>
          <select name="indipendente">
          <?php      
                                
                                while($option1 = gdrcd_query($elenco_indipendenti, 'fetch')) { ?>
                                <option value="<?php echo $option1['nome_ruolo']; ?>">
                                    <?php echo gdrcd_filter('out', $option1['nome_ruolo']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($elenco_indipendenti, 'free');
                            ?>
          </select><br><br>
           <input type="hidden" name="op" value="delete" />
           <input type="submit" value="Invio"> 
          </form>
    </center>
    <?php
    }//fine
    ?>
</div>