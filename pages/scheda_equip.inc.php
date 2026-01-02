<link rel="stylesheet" href="../themes/crystal/scheda_equip.css">
<div class="pagina_scheda">
    <?php /*HELP: */
    //Se non e' stato specificato il nome del pg
    if(isset($_REQUEST['pg']) === false) {
        echo gdrcd_filter('out', $MESSAGE['error']['unknonw_character_sheet']);
        exit();
    }
    /*Visualizzo la pagina*/
    /*Rilevo il genere del PG*/
    $query = "SELECT sesso FROM personaggio WHERE personaggio.nome = '".gdrcd_filter('get', $_REQUEST['pg'])."'";
    $result = gdrcd_query($query, 'result');
    //Se non esiste il pg
    if(gdrcd_query($result, 'num_rows') == 0) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
        exit();
    }

    if(($_SESSION['login'] == $_REQUEST['pg']) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) {
        switch(gdrcd_filter('get', $_POST['op'])) {
            case 'abbandona': /*Rimuovo un oggetto dall'inventario o dallo zaino*/
                /*Se ne possiedo più di uno ne rimuovo uno solo*/
                if($_POST['numero'] <= 1) {
                    $query = "DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1 ";
                } else {
                    $query = "UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1 ";
                }
                gdrcd_query($query);
                /*Registro l'evento
                gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".gdrcd_filter('in', $_REQUEST['pg'])."', '".$_SESSION['login']."', NOW(), ".BONIFICO.", ' -".gdrcd_filter('in', $_POST['checosa'])."')");
                */
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['done']).'</div>';
                break;
            case 'cedi': /*Cessione di un oggetto ad un'altro PG*/
                $result_min = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])."", 'result');
                if($_POST['numero'] <= 1) {
                    $query = "DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1 ";
                } else {
                    $query = "UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1 ";
                }
                /*Se non ci sono stati barecci*/
                gdrcd_query($query);

                if(gdrcd_query($result_min, 'num_rows') > 0) {
                    gdrcd_query($result_min, 'free');
                    $result_loc = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_POST['give_item'])."'", 'result');
                    if(gdrcd_query($result_loc, 'num_rows') > 0) {
                        gdrcd_query($result_loc, 'free');
                        $query = "UPDATE clgpersonaggiooggetto SET numero = numero + 1 WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_POST['give_item'])."'";
                    } else {
                        $query = "INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero) VALUES ('".gdrcd_filter('in', $_POST['give_item'])."',".gdrcd_filter('num', $_POST['id_oggetto']
                            ).", ".gdrcd_filter('num', $_POST['cariche']).", 1)";
                    }
                    gdrcd_query($query);
                    /*Registro l'evento
                    gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".$_POST['give_item']."', '".$_SESSION['login']."', NOW(), ".BONIFICO.", '".gdrcd_filter('in', $_POST['checosa'])."')");
                    */
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['done']).'</div>';
                } else {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['cant_do']).'</div>';
                }
                break;
            case 'indossa':    /*Indossatura un oggetto*/
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = ".gdrcd_filter('num', $_POST['posizione'])." WHERE id_oggetto = ".$_POST['id_oggetto']." AND nome = '".gdrcd_filter('get', $_REQUEST['pg'])."' LIMIT 1 ");
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['done']).'</div>';
                break;
            case 'in_zaino':    /* Spostamento di un oggetto dall'inventario nello zaino */
                gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 1 WHERE id_oggetto = ".gdrcd_filter('num', $_POST['id_oggetto'])." AND nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' LIMIT 1 ");
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['done']).'</div>';
                break;
        }
    }

    /*Caricamento "omino" con oggetti*/
    $record = gdrcd_query($result, 'fetch');
    gdrcd_query($result, 'free');

    $sesso = $record['sesso'];

    $result = gdrcd_query("SELECT oggetto.id_oggetto, oggetto.nome, oggetto.urlimg AS immagine, clgpersonaggiooggetto.posizione FROM clgpersonaggiooggetto JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE clgpersonaggiooggetto.posizione > 1 AND clgpersonaggiooggetto.nome='".gdrcd_filter('get', $_REQUEST['pg'])."'", 'result');

    while($record = gdrcd_query($result, 'fetch')) {
        $oggetti[$record['posizione']]['nome'] = $record['nome'];
        $oggetti[$record['posizione']]['immagine'] = $record['immagine'];
        $oggetti[$record['posizione']]['id_oggetto'] = $record['id_oggetto'];
    }

    gdrcd_query($result, 'free');
    ?>
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['page_name']); ?></h2>
    </div>
        <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        </div>
        <div class="page_scheda_equip">
    <!-- Immagine "omino" e oggetti indossati -->
    <div class="omino_bianco_box">
        <table class="omino_bianco_table">
            <tr>
                <td>
                    
                    <div class="omino_bianco_neck">
                        <?php if(isset($oggetti[9]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[9]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[9]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[9]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_armdx">
                        <?php if(isset($oggetti[2]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[2]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[2]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[2]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_ring">
                        <?php if(isset($oggetti[8]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[8]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[8]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[8]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_feet">
                        <?php if(isset($oggetti[6]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[6]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[6]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[6]['nome']); ?>" />
                        <?php } ?>
                    </div>
                </td>
                <td>
                    <img class="omino_bianco_img" src="themes/crystal/imgs/scheda/omino.png" />
                </td>
                <td>
                <div class="omino_bianco_head">
                        <?php if(isset($oggetti[7]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[7]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[7]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[7]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_chest">
                        <?php if(isset($oggetti[4]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[4]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[4]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[4]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_armsx">
                        <?php if(isset($oggetti[3]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[3]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[3]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[3]['nome']); ?>" />
                        <?php } ?>
                    </div>
                    <div class="omino_bianco_legs">
                        <?php if(isset($oggetti[5]) === true) { ?>
                            <img src="imgs/items/<?php echo $oggetti[5]['immagine']; ?>"
                                 alt="<?php echo gdrcd_filter('out', $oggetti[5]['nome']); ?>"
                                 title="<?php echo gdrcd_filter('out', $oggetti[5]['nome']); ?>" />
                        <?php } ?>
                    </div>
                </td>
            <tr>
        </table>
    </div>
    <!-- Elenco oggetti nello zaino -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['zaino']); ?></h2>
    </div>
    <div class="page_body box_equip">
        <div class="panels_box">
            <?php /*Oggetti nello zaino*/
            $result = gdrcd_query("SELECT oggetto.id_oggetto, oggetto.nome AS nome_oggetto, oggetto.descrizione, oggetto.urlimg, oggetto.ubicabile, oggetto.tipo, oggetto.attacco, oggetto.bonus_car0, oggetto.bonus_car1_extra, oggetto.bonus_car2_extra, oggetto.bonus_car3_extra, oggetto.temp_giorni, oggetto.bonus_car5, oggetto.isTemp, clgpersonaggiooggetto.* FROM clgpersonaggiooggetto LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE clgpersonaggiooggetto.nome = '".gdrcd_filter('in', $_REQUEST['pg'])."' AND clgpersonaggiooggetto.posizione > 0 ORDER BY oggetto.nome DESC", 'result'); ?>
            <div class="elenco_record_gioco">
            
            
            
            
            
            
            
            
            
            
            
            
            
                <!-- Intestazione tabella elenco -->
                <table class="customTable" align="center">
                    <tr>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo "Immagine"; ?>
                            </div>
                        </td>
                        <td class="casella_titolo" colspan="1">
                            <div class="titoli_elenco">
                                <?php echo "Descrizione"; ?>
                            </div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo "&nbsp;"; ?>
                            </div>
                        </td>
                    </tr>
                    <?php while($record = gdrcd_query($result, 'fetch')) { 
                    
                       //valuto i rowspan
                    
                    if ($record['commento'] == '' && $record['commento_master'] == '' && $record['commento_shop'] == '') {
                    $rowspan = 1;
                    } else if (($record['commento'] != '' && $record['commento_master'] == '' && $record['commento_shop'] == '') || ($record['commento'] == '' && $record['commento_master'] != '' && $record['commento_shop'] == '') || ($record['commento'] == '' && $record['commento_master'] == '' && $record['commento_shop'] != '')) {
                    $rowspan = 2;
                    } else if (($record['commento'] != '' && $record['commento_master'] != '' && $record['commento_shop'] == '') || ($record['commento'] == '' && $record['commento_master'] != '' && $record['commento_shop'] != '') || ($record['commento'] != '' && $record['commento_master'] == '' && $record['commento_shop'] != '')) {
                    $rowspan = 3;
                    } else if ($record['commento'] != '' && $record['commento_master'] != '' && $record['commento_shop'] != '') {
                    $rowspan = 4;
                    }
                  
                    ?>
                        <tr>
                            <!-- Oggetto, immagine, quantità -->
                            <td class="casella_elemento" rowspan="<?php echo $rowspan ?>">
                                
                                <div class="inventario_img">
                                    <img src="imgs/items/<?php echo gdrcd_filter('out', $record['urlimg']); ?>" style="max-width: 300px;" />
                                </div>
                                </td>
                            
                                <!-- Descrizione e note-->
                            
                            <td class="casella_elemento">
                                <div class="inventario_riga_descrizione">
                                <span style="text-transform: uppercase;"><center><b><?php echo gdrcd_filter('out', $record['nome_oggetto']); ?></b>
                                <?php
                                echo " x ".$record['numero']."";
                                if ($record['cariche'] > 1) { echo " (Cariche:". $record['cariche'].")"; } 
                                ?>
                                </center></span>
                                    <?php echo nl2br(gdrcd_filter('out', $record['descrizione'])); ?>
                                </div>                                
                            </td>
                            <!-- Comandi elenco -->
                            <td class="casella_elemento" rowspan="<?php echo $rowspan ?>">
                                <?php if(($_SESSION['login'] == $_REQUEST['pg']) || ($_SESSION['admin']==1 || $_SESSION['moderatore']==1)) { ?>
                                    <div class="form_gioco">
                                        <!-- Abbandona -->
                                        <form action="main.php?page=scheda_equip" method="post">
                                            <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                            <input type="hidden" value="<?php echo gdrcd_filter('out', $record['nome_oggetto']); ?>" name="checosa" />
                                            <input type="hidden" value="abbandona" name="op" />
                                            <input type="hidden" value="<?php echo $record['numero']; ?>" name="numero" />
                                            <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['drop']); ?>" />
                                        </form>
                                        <!-- Riponi -->
                                        <form action="main.php?page=scheda_oggetti" method="post">
                                            <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                            <input type="hidden" value="togli" name="op" />
                                            <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['put_away']); ?>" />
                                        </form>
                                        <?php if($record['ubicabile'] > ZAINO) {
                                            /*Se non sono ne impugnati, ne indossati*/
                                            if($record['posizione'] == 1) {
                                                /*Se la locazione è libera*/
                                                if(isset($oggetti[$record['ubicabile']]) === false) { ?>
                                                    <!-- Indossa (se indossabile) -->
                                                    <form action="main.php?page=scheda_equip" method="post">
                                                        <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                                        <input type="hidden" value="indossa" name="op" />
                                                        <input type="hidden" value="<?php echo $record['ubicabile']; ?>" name="posizione" />
                                                        <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['wear']); ?>" />
                                                    </form>
                                                <?php } //if
                                                if(isset($oggetti[2]) === false) { ?>
                                                    <!-- Impugna DX -->
                                                    <form action="main.php?page=scheda_equip" method="post">
                                                        <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                                        <input type="hidden" value="indossa" name="op" />
                                                        <input type="hidden" value="2" name="posizione" />
                                                        <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['wield'].' (DX)'); ?>" />
                                                    </form>
                                                <?php } //if
                                                if(isset($oggetti[3]) === false) { ?>
                                                    <!-- Impugna SX-->
                                                    <form action="main.php?page=scheda_equip" method="post">
                                                        <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                                        <input type="hidden" value="indossa" name="op" />
                                                        <input type="hidden" value="3" name="posizione" />
                                                        <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['wield'].' (SX)'); ?>" />
                                                    </form>
                                                <?php } //if
                                            } else { ?>
                                                <form action="main.php?page=scheda_equip" method="post">
                                                    <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                                    <input type="hidden" value="indossa" name="op" />
                                                    <input type="hidden" value="1" name="posizione" />
                                                    <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                                    <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['unwear']); ?>" />
                                                </form>
                                            <?php }//else
                                        }//if
                                        /*Personaggi nella stessa location*/
                                        if($PARAMETERS['mode']['give_only_if_online'] == 'ON') {
                                            $query = "SELECT nome FROM personaggio WHERE ultimo_luogo = ".$_SESSION['luogo']." AND ultimo_luogo  <> -1 AND nome <> '".$_SESSION['login']."' AND DATE_ADD(ultimo_refresh, INTERVAL 2 MINUTE) > NOW() ORDER BY nome";
                                        } else {
                                            $query = "SELECT nome FROM personaggio ORDER BY nome";
                                        }

                                        $characters = gdrcd_query($query, 'result');
                                        if(gdrcd_query($characters, 'num_rows') > 0) { ?>
                                            <form action="main.php?page=scheda_equip" method="post">
                                                <input type="hidden" value="<?php echo gdrcd_filter('out', $_REQUEST['pg']); ?>" name="pg" />
                                                <input type="hidden" value="<?php echo $record['id_oggetto']; ?>" name="id_oggetto" />
                                                <input type="hidden" value="<?php echo $record['cariche']; ?>" name="cariche" />
                                                <input type="hidden" value="<?php echo $record['numero']; ?>" name="numero" />
                                                <input type="hidden" value="<?php echo gdrcd_filter('out', $record['nome_oggetto']); ?>" name="checosa" />
                                                <input type="hidden"value="cedi" name="op" />
                                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['list']['give']); ?>" />
                                                <select name="give_item">
                                                    <?php while($option = gdrcd_query($characters, 'fetch')) { ?>
                                                        <option value="<?php echo $option['nome']; ?>">
                                                            <?php echo gdrcd_filter('out', $option['nome']); ?>
                                                        </option>
                                                    <?php }
                                                    gdrcd_query($characters, 'free');
                                                    ?>
                                                </select>
                                            </form>
                                        <?php }//if ?>
                                    </div>
                                <?php } else {
                                    echo "&nbsp;";
                                } ?>
                            </td>
                        </tr>
                        
                        <?php if($record['commento'] != '') { ?>
                                    <tr>
                                    <td class="casella_elemento">
                                    <div class="inventario_riga_commento">
                                    <span style="text-transform: uppercase;"><center><b>COMMENTO</b></center></span>
                                    <?php echo $record['commento']; ?>
                                    </div>
                                    </td>
                                    </tr>
                                <?php }//if ?>
                                
                                <?php if($record['commento_master'] != '') { ?>
                                    <tr>
                                    <td class="casella_elemento">
                                    <div class="inventario_riga_commento">
                                    <span style="text-transform: uppercase;"><center><b>NOTA MASTER</b></center></span>
                                    <?php echo $record['commento_master']; ?>
                                    </div>
                                    </td>
                                    </tr>
                                <?php }//if ?>
                                
                                <?php if($record['commento_shop'] != '') { ?>
                                    <tr>
                                    <td class="casella_elemento">
                                    <div class="inventario_riga_commento">
                                    <span style="text-transform: uppercase;"><center><b>NOTA 
                                    <?php if ($record['tipo'] == 2) { echo "MAGIC"; } else if ($record['tipo'] == 3) { echo "PANDORA"; } ?>
                                    </b></center></span>
                                    <?php echo $record['commento_shop']; ?>
                                    </div>
                                    </td>
                                    </tr>
                                <?php }//if ?>
                                
                    <?php }//while
                    gdrcd_query($result, 'free');
                    ?>
                </table>
            </div>
        </div>
        <!-- Link a piè di pagina -->
        <div class="link_back">
            <?php
            if ($_SESSION['admin'] == 1 || $_REQUEST['pg'] == $_SESSION['login']) {
            ?>
            <a href="main.php?page=scheda_oggetti&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['inventory']).'.'; ?></a><br />
            <?php } ?>
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div>
    </div>

</div><!-- Pagina -->