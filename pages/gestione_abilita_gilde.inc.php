<head>
    <script>
        function myFunction() {
            var data_value = document.getElementById("id_gilda").value;
            var xhttp = new XMLHttpRequest(); 
            xhttp.open("POST", window.location.origin+"/pages/ajax.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            var param = "id="+data_value;
            xhttp.send(param);
            xhttp.onreadystatechange = function() { 
            if (xhttp.readyState == 4)
                if (xhttp.status == 200)
                    document.getElementById("ruoli").innerHTML = xhttp.responseText;  
            };   
        }
    </script>
</head>

<div class="pagina_gestione_abilita">

<?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1 && $_SESSION['capogilda'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } elseif($PARAMETERS['mode']['skillsystem'] == 'OFF') {
        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['unactive']).'</div>';
    } else { ?>
        <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['page_name']); ?></h2>
        </div>
        <!-- Corpo della pagina -->
        <div class="page_body">
            <?php /*Inserimento di un nuovo record*/
            if($_POST['op'] == 'insert') {
                /*Eseguo l'inserimento*/
                gdrcd_query("INSERT INTO abilita (nome, descrizione, car, id_razza, id_gilda, max_lvl, tipo) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '".gdrcd_filter('in', $_POST['descrizione'])."', ".gdrcd_filter('num', $_POST['car']).", -1, ".gdrcd_filter('num', $_POST['id_gilda']).", ".gdrcd_filter('num', $_POST['max_lvl']).", '".gdrcd_filter('in', $_POST['tipo'])."')");
                if (isset($_POST['ruolo'])) {
                foreach ($_POST['ruolo'] as $ruolo){ 
                gdrcd_query("INSERT INTO clgruoloabilita (ruolo, abilita) VALUES ('".gdrcd_filter('in', $ruolo)."','".gdrcd_filter('in', $_POST['nome'])."')");
										}
                                        }
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Cancellatura in un record */
            if(gdrcd_filter('get', $_POST['op']) == 'erase') {
                /*Eseguo la cancellatura nella tabella abilita e clg*/
                gdrcd_query("DELETE FROM abilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
                gdrcd_query("DELETE FROM clgruoloabilita WHERE abilita='".$_POST['nome']."'");
                /*Aggiorno i personaggi*/
                gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])."");
                ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /* Modifico i ruoli*/
            if(gdrcd_filter('get', $_POST['op']) == 'reverse') {
            $nome_skill = $_POST['nome_skill'];
            gdrcd_query("DELETE FROM clgruoloabilita WHERE abilita='". gdrcd_filter('in', $_POST['nome_skill'])."'");
            
            if (isset($_POST['ruolo'])) {
                foreach ($_POST['ruolo'] as $ruolo){ 
                gdrcd_query("INSERT INTO clgruoloabilita (ruolo, abilita) VALUES ('".gdrcd_filter('in', $ruolo)."','".gdrcd_filter('in', $_POST['nome_skill'])."')");
                }
                }
                
                
                ?>
                <div class="warning">
                    <?php echo "Ruoli skill modificati"; ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Modifica di un record*/
            if(gdrcd_filter('get', $_POST['op']) == 'modify') {
                /*Eseguo l'aggiornamento*/
                gdrcd_query("UPDATE abilita SET nome ='".gdrcd_filter('in', $_POST['nome'])."', descrizione ='".gdrcd_filter('in', $_POST['descrizione'])."', car = ".gdrcd_filter('num', $_POST['car']).", id_gilda = ".gdrcd_filter('num', $_POST['id_gilda']).", max_lvl = ".gdrcd_filter('num', $_POST['max_lvl']).", tipo = '".gdrcd_filter('in', $_POST['tipo'])."' WHERE id_abilita = ".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1"); 
                gdrcd_query("UPDATE clgruoloabilita SET abilita ='".gdrcd_filter('in', $_POST['nome'])."' WHERE abilita = '".gdrcd_filter('in', $_POST['nome_vecchio'])."'"); 
                
                /*Inserisco l'aggiornamento*/
             if ($_POST['id_gilda'] == 1) {
             $id_mex = '243202';
             } elseif ($_POST['id_gilda'] == 2) {
             $id_mex = '243207';
             } elseif ($_POST['id_gilda'] == 3) {
             $id_mex = '243205';
             } elseif ($_POST['id_gilda'] == 4) {
             $id_mex = '243204';
             } elseif ($_POST['id_gilda'] == 5) {
             $id_mex = '243203';
             } elseif ($_POST['id_gilda'] == 6) {
             $id_mex = '243206';
             } elseif ($_POST['id_gilda'] == 7) {
             $id_mex = '243208';
             }
             
             
             $testo_notifica ="Modifica alle <b>skill</b>.<br><br><b>Prima della modifica</b><br>[spoiler]<b>Skill</b>:". $_POST['old_nome'] ."<br><b>Testo</b>:". $_POST['old_descrizione'] ."[/spoiler]
                               <br><br><b>Modifica</b><br>[spoiler]<b>Titolo</b>:". $_POST['nome'] ."<br><b>Testo</b>:". $_POST['descrizione'] ."[/spoiler]";
                               
             gdrcd_query("INSERT INTO messaggioaraldo (id_messaggio_padre, id_araldo, messaggio, autore, anonimo, giornalista, data_messaggio, data_ultimo_messaggio ) VALUES (".gdrcd_filter('num', $id_mex).", '142', '".gdrcd_filter('in', $testo_notifica)."', '".gdrcd_filter('in', $_SESSION['login'])."', 'no', 'no', NOW(), NOW())");
             gdrcd_query("DELETE FROM araldo_letto WHERE thread_id = ".gdrcd_filter('num', $id_mex)." AND nome != '".$_SESSION['login']."'");
             ?>
                <div class="warning">
                    <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php
            }
            /*Form di inserimento/modifica*/
            if((gdrcd_filter('get', $_POST['op']) == 'edit') || (gdrcd_filter('get', $_REQUEST['op']) == 'new')) {
                /*Preseleziono l'operazione di inserimento*/
                $operation = 'insert';
                /*Se è stata richiesta una modifica*/
                if(gdrcd_filter('get', $_POST['op']) == 'edit') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM abilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                } ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_abilita_gilde" method="post" class="form_gestione">
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['name']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="nome" value="<?php echo gdrcd_filter('out', $loaded_record['nome']); ?>" />
                            <input type="hidden" name="old_nome" value="<?php echo gdrcd_filter('out', $loaded_record['nome']); ?>" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['infos']); ?>
                        </div>
                        <div class='form_field'>
                            <textarea name="descrizione" placeholder="Per andare a capo usare i <br>"><?php echo gdrcd_filter('out', $loaded_record['descrizione']); ?></textarea>
                            <textarea name="old_descrizione" style="display:none;" placeholder="Per andare a capo usare i <br>"><?php echo gdrcd_filter('out', $loaded_record['descrizione']); ?></textarea>
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['car']); ?>
                        </div>
                        <div class='form_field'>
                            <select name='car'>
                                <option value="8" <?php if($loaded_record['car'] == 8) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car8']); ?>
                                </option>
                                <option value="0" <?php if($loaded_record['car'] == 0) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car0']); ?>
                                </option>
                                <option value="2" <?php if($loaded_record['car'] == 2) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car2']); ?>
                                </option>
                                <option value="4" <?php if($loaded_record['car'] == 4) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car4']); ?>
                                </option>
                                <option value="6" <?php if($loaded_record['car'] == 6) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car6']); ?>
                                </option>
                            </select>
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['guild']); ?>
                        </div>
                        <div class='form_field'>
                            <select onchange="myFunction();" id='id_gilda' class='id_gilda' name='id_gilda'>
                                <!-- Opzione predefinita che non può essere selezionata -->
                                <option value="" disabled selected>-- scegli via --</option>
                                <?php
                                $result = gdrcd_query("SELECT id_gilda, nome, tipo FROM gilda ORDER BY nome", 'result');
                                while($guild = gdrcd_query($result, 'fetch')) {
                                    ?>
                                    <option value="<?php echo $guild['id_gilda']; ?>" <?php if($loaded_record['id_gilda'] == $guild['id_gilda']) { echo 'SELECTED'; } ?> >
                                        <?php echo gdrcd_filter('out', $guild['nome']); ?>
                                    </option>
                                <?php
                                }
                                gdrcd_query($result, 'free');
                                ?>
                                <!-- Aggiunta di 'Cittadini potenziati' con valore -1 -->
                                    <option value="-1">
                                        Cittadini potenziati
                                    </option>
                            </select>
                        </div>
                        <div class='form_label'>
                            <?php echo 'Ruoli'; ?>
                        </div>
                        <div class='form_field'>
                        <div id="ruoli">
                        Seleziona prima la famiglia 
                        </div>
                        </div>
                        <div class='form_label'>
                            <?php echo 'Liv. Max.'; ?>
                        </div>
                        <div class='form_field'>
                            <select name='max_lvl'>
                                <option value="0" <?php if($loaded_record['max_lvl'] == 0) { echo 'SELECTED'; } ?>>
                                    0</option>
                                <option value="1" <?php if($loaded_record['max_lvl'] == 1) { echo 'SELECTED'; } ?>>
                                    1</option>
                                <option value="2" <?php if($loaded_record['max_lvl'] == 2) { echo 'SELECTED'; } ?>>
                                    2</option>
                                <option value="3" <?php if($loaded_record['max_lvl'] == 3) { echo 'SELECTED'; } ?>>
                                    3</option>
                                <option value="4" <?php if($loaded_record['max_lvl'] == 4) { echo 'SELECTED'; } ?>>
                                    4</option>
                                <option value="5" <?php if($loaded_record['max_lvl'] == 5) { echo 'SELECTED'; } ?>>
                                    5</option>
                            </select>
                        </div>
                        
                        <!-- tipologia -->
                        
                        <div class='form_label'>
                            <?php echo 'Tipo di skill'; ?>
                        </div>
                        <div class='form_field'>
                            <select name='tipo'>
                               <option value="Default" <?php if($loaded_record['tipo'] == "Default") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['2']); ?></option>
                               
                               <option value="Difensiva" <?php if($loaded_record['tipo'] == "Difensiva") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['5']); ?></option>
                                
                               <option value="Generica base" <?php if($loaded_record['tipo'] == "Generica base") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['1']); ?></option>
                                    
                               <option value="Generica avanzata" <?php if($loaded_record['tipo'] == "Generica avanzata") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['7']); ?></option>
                                
                                <option value="Attacco base" <?php if($loaded_record['tipo'] == "Attacco base") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['3']); ?></option>
                                    
                                <option value="Attacco medio" <?php if($loaded_record['tipo'] == "Attacco medio") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['8']); ?></option>
                                
                                <option value="Attacco avanzato" <?php if($loaded_record['tipo'] == "Attacco avanzato") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['9']); ?></option>
                                    
                                <option value="Mentale base" <?php if($loaded_record['tipo'] == "Mentale base") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['4']); ?></option>
                                    
                                <option value="Mentale media" <?php if($loaded_record['tipo'] == "Mentale media") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['10']); ?></option>
                                
                                <option value="Mentale avanzata" <?php if($loaded_record['tipo'] == "Mentale avanzata") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['11']); ?></option>
                                    
                                <option value="Mentale di attacco" <?php if($loaded_record['tipo'] == "Mentale di attacco") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['13']); ?></option>
                                    
                                <option value="Potere speciale" <?php if($loaded_record['tipo'] == "Potere speciale") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['12']); ?></option>
                                    
                                <option value="Talento" <?php if($loaded_record['tipo'] == "Talento") { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['names']['skill']['6']); ?></option>
                            </select>
                        </div>
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica e annulla */
                            if($operation == "edit") { ?>
                                <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                                <input type="hidden" name="nome_vecchio" value="<?php echo $loaded_record['nome']; ?>">
                                <input type="hidden" name="op" value="modify" />
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['submit']['edit']); ?>" />
                            <?php
                            }  else {  /* Altrimenti il tasto inserisci */ ?>
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['submit']['insert']); ?>" />
                                <input type="hidden" name="op" value="insert" />
                            <?php
                            } ?>
                        </div>
                    </form>
                </div>
                <!-- Link di ritorno alla visualizzazione di base -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['back']); ?>
                    </a>
                </div>
            <?php }
            
            if(gdrcd_filter('get', $_POST['op']) == 'change_ruolo') {
                    /*Carico il record da modificare*/
                    $loaded_record = gdrcd_query("SELECT * FROM abilita WHERE id_abilita=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'reverse';
               
            ?>
            <form action="main.php?page=gestione_abilita_gilde" method="post" class="form_gestione">
            <h1><center>Modifica ruolo</center></h1>
            <table style="width:100%; background-color:#13192F;"><tr><td align=justify><fieldset style="border:1px solid lightblue; padding: 10px; text-align:justify;"><legend style="border:1px solid lightblue; color:lightblue; font-size:11px; text-align: center;"><?php echo $loaded_record['nome']; ?></legend>
            
            <?php echo $loaded_record['descrizione']; ?>
            </fieldset></td></tr></table>
            <h1><center>Vanno reinseriti <u>tutti</u> i ruoli</center></h1>
            <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['guild']); ?>
                        </div>
                        <div class='form_field'>
                            <select onchange="myFunction();" id='id_gilda' class='id_gilda' name='id_gilda'>
                                <option value="-1" <?php if($loaded_record['id_guild'] == -1) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['no_guild']); ?>
                                </option>
                                <?php
                                $result = gdrcd_query("SELECT id_gilda, nome, tipo FROM gilda ORDER BY nome", 'result');
                                while($guild = gdrcd_query($result, 'fetch')) {
                                    ?>
                                    <option value="<?php echo $guild['id_gilda']; ?>" <?php if($loaded_record['id_gilda'] == $guild['id_gilda']) { echo 'SELECTED'; } ?> >
                                        <?php echo gdrcd_filter('out', $guild['nome']); ?>
                                    </option>
                                <?php
                                }
                                gdrcd_query($result, 'free');
                                ?>
                            </select>
                        </div>
                        <div class='form_label'>
                            <?php echo 'Ruoli'; ?>
                        </div>
                        <div class='form_field'>
                        <div id="ruoli">
                        Seleziona prima la famiglia 
                        </div>
                        </div>
                        
                        <div class='form_submit'>
                        <input type="hidden" name="id_record" value="<?php echo $loaded_record['id_abilita']; ?>">
                        <input type="hidden" name="nome_skill" value="<?php echo $loaded_record['nome']; ?>">
                        <input type="hidden" name="op" value="reverse" />
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['submit']['edit']); ?>" />
                          
                        </div>
                        </form>
            <?php
             }
            //if
            if(isset($_REQUEST['op']) === false) { /*Elenco record (Visualizzaione di base della pagina)*/
                // Determinazione pagina (paginazione)
                $offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 1;
                $pagebegin = (int) gdrcd_filter('get', ($offset-1)) * $PARAMETERS['settings']['records_per_page'];
                $pageend = $PARAMETERS['settings']['records_per_page'];
                // Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM abilita WHERE id_gilda != 0");
                $totaleresults = $record_globale['COUNT(*)'];

                // Lettura record
                $result = gdrcd_query("SELECT id_abilita, abilita.nome as nome, car, abilita.id_gilda as id_gilda, max_lvl, abilita.tipo, gilda.nome as nome_gilda FROM abilita LEFT JOIN gilda on abilita.id_gilda = gilda.id_gilda WHERE abilita.id_gilda != 0
 ORDER BY nome LIMIT ".$pagebegin.", ".$pageend."", 'result');
                $numresults = gdrcd_query($result, 'num_rows');

                /* Se esistono record */
                if($numresults > 0) { ?>
                    <!-- Elenco dei record paginato -->
                    <div class="elenco_record_gestione">
                        <table>
                            <!-- Intestazione tabella -->
                            <tr>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']); ?></div>
                                </td>
								<td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds_col']
                                        ); ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo 'Tipologia'; ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo 'Liv. Max.'; ?></div>
                                </td>
                                <td class="casella_titolo">
                                    <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                                </td>
                            </tr>
                            <!-- Record -->
                            <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                                <tr class="risultati_elenco_record_gestione">
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['nome']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['nome_gilda']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['tipo']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_elemento">
                                        <div class="elementi_elenco">
                                            <?php echo $row['max_lvl']; ?>
                                        </div>
                                    </td>
                                    <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                  <!-- Modifica -->
                                        <div class="controlli_elenco">
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_abilita_gilde" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="op" value="edit" />
                                                    <input type="image" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" src="imgs/icons/edit.png" />
                                                </form>
                                            </div>
                                            <!-- Elimina -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_abilita_gilde" method="post" onsubmit="return confirm('<?php echo gdrcd_filter('out', $MESSAGE['interface']['skill']['comfirmdel']) ?>');">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="nome" value="<?php echo $row['nome'] ?>" />
                                                    <input type="hidden" name="op" value="erase" />
                                                    <input type="image" src="imgs/icons/erase.png" alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>" />
                                                </form>
                                            </div>
                                            <!-- Cambia Ruolo -->
                                            <div class="controllo_elenco">
                                                <form class="opzioni_elenco_record_gestione" action="main.php?page=gestione_abilita_gilde" method="post">
                                                    <input type="hidden" name="id_record" value="<?php echo $row['id_abilita'] ?>" />
                                                    <input type="hidden" name="nome" value="<?php echo $row['nome'] ?>" />
                                                    <input type="hidden" name="op" value="change_ruolo" />
                                                    <input type="image" src="imgs/icons/reverse.png" alt="<?php echo "Modifica ruoli"; ?>" title="<?php echo "Modifica ruolo"; ?>" />
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } //while
                            gdrcd_query($result, 'free');
                            ?>
                        </table>
                    </div>
                <?php
                }//if
                ?>

                <!-- Paginazione -->
                <div class="pagination">
                    <?php
                    include __DIR__ . '/../includes/custom_functions.inc.php';

                    // Genera i link di paginazione
                    $total_pages = ceil($totaleresults / $PARAMETERS['settings']['records_per_page']);
                    echo generateLinks($offset, $total_pages, preg_replace('/&.*/', '', $_SERVER["REQUEST_URI"]));
                    echo "<p>Pagina $offset di $total_pages (Totale: $totaleresults record)</p>";
                    ?>
                </div>

                <!-- link crea nuovo -->
                <div class="link_back">
                    <a href="main.php?page=gestione_abilita_gilde&op=new">
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['skills']['link']['new']); ?>
                    </a>
                </div>
            <?php
            }//else
            ?>
        </div>
    <?php
    } // else (controllo permessi utente) ?>
</div> <!--Pagina-->
