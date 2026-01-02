<div class="pagina_gestione_mercato">
<?php /*Controllo permessi*/
if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    
    
    
    
    //depotenzia
    
    if($_POST['op'] == 'depotenzia') {
    
    gdrcd_query("UPDATE clgpersonaggioabilita SET grado = grado - 1 WHERE id_abilita = ".gdrcd_filter('num', $_POST['id_record'])." AND nome = '".gdrcd_filter('in', $_POST['pg'])."'"); 
    gdrcd_query("UPDATE personaggio SET esperienza_r = esperienza_r + 5 WHERE nome = '".gdrcd_filter('in', $_POST['pg'])."'"); 
    } else if($_POST['op'] == 'cancella') {
    
    $points = gdrcd_query("SELECT sum(grado) AS total FROM clgpersonaggioabilita WHERE id_abilita = ".gdrcd_filter('num', $_POST['id_record'])." AND nome = '".gdrcd_filter('in', $_POST['pg'])."'");
    $punti = $points['total'] * 5; 
    gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." AND nome = '".gdrcd_filter('in', $_POST['pg'])."'");
    gdrcd_query("UPDATE personaggio SET esperienza_r = esperienza_r + $punti WHERE nome = '".gdrcd_filter('in', $_POST['pg'])."'"); 
    }
    
    
    
    
    
    
    
    
    
    
    
    
    $elenco_pg = gdrcd_query("SELECT nome, cognome FROM personaggio ORDER BY nome", 'result');
    ?>
    <div class="panels_box">
            <!-- Elenco pg -->
    <div class="panels_box">
                <form class="form_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                    <div class='form_label'>
                        <?php echo 'Seleziona personaggio'; ?>
                    </div>
                    <div class='form_field'>
                        <?php if(gdrcd_query($elenco_pg, 'num_rows') > 0) { ?>
                            <select name="load_item">
                             <?php                            
                            //facciamo visualizzare a magic e pandora solo la loro categoria//
                              while($option = gdrcd_query($elenco_pg, 'fetch')) { ?>
                                    <option value="<?php echo $option['nome']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                        <?php echo gdrcd_filter('out', $option['cognome']); ?>
                                    </option>
                                <?php }
                                gdrcd_query($elenco_pg, 'free');
                                ?>
                            </select>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="op" value="load" />
                    <div class='form_submit'>
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                    </div>
                </form>
            </div>
    
                <?php if ($_POST['load_item'] != "") { ?>
                <div class='form_label'>
                        <?php echo 'Elenco Skill di'. $_POST['load_item']; ?>
                </div>
                
                            <!-- INIZIO CAMPI DI MODIFICA -->
                <?php
    $request_pg = $_POST['load_item'];
    $result_total = gdrcd_query("SELECT abilita.id_abilita as id_abilita, abilita.nome as nome_abilita, car, descrizione, id_razza, id_gilda, max_lvl, abilita.tipo as tipo, clgpersonaggioabilita.nome as nome_personaggio, grado FROM abilita LEFT JOIN clgpersonaggioabilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita WHERE clgpersonaggioabilita.nome ='$request_pg' ORDER BY abilita.id_gilda DESC, abilita.nome", 'result');
                ?>
                
                <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']); ?></div>
                                </td>
                                
								                               
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo 'Livello'; ?></div>
                                </td>
                                
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                            </tr>
                            <!-- Record -->
                            
                            <?php while($row = gdrcd_query($result_total, 'fetch')) { ?>
                            <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['nome_abilita']; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['grado']; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="controlli_elenco">
                                    <?php if ($row['grado'] > 1) { ?>
                                    <!-- Depotenzia -->
                                        <div class="controlli_elenco" style="float: left;">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="pg" value="<?php echo $request_pg ?>" />
                                                    <input type="hidden" name="op" value="depotenzia" />
                                                    <input type="submit" value="-" />
                                                </form>
                                            </div> 
                                     <?php } ?>
                                     
                                            <div class="controllo_elenco" style="float: right;">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="pg" value="<?php echo $request_pg ?>" />
                                                    <input type="hidden" name="op" value="cancella" />
                                                    <input type="submit" value="X" />
                                                </form>
                                            </div> 
                                            
                                            </div>
                                    </td>
                                    </tr>
                            <?php }//fine record ?>
                            </table>
                <?php }//fine load_item
                ?>
    
    
    
    
    
    
<?php
    }//fine else permessi
?>
</div>