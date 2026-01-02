<div class="pagina_gestione_manutenzione">
    <?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else {
    /*Puoi entrare*/
    
    /*Selezioniamo liv*/
    $liv_1 = gdrcd_query("SELECT * FROM personaggio WHERE esperienza < 25 && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_2 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 25 && esperienza < 100) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_3 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 100 && esperienza < 200) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_4 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 200 && esperienza < 500) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_5 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 500 && esperienza < 1000) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_6 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 1000 && esperienza < 2000) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_7 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 2000 && esperienza < 5000) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_8 = gdrcd_query("SELECT * FROM personaggio WHERE (esperienza >= 5000 && esperienza < 10000) && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    $liv_9 = gdrcd_query("SELECT * FROM personaggio WHERE esperienza > 10000 && (esilio < NOW() || esilio IS NULL) ORDER BY nome", 'result');
    
    $race = gdrcd_query("SELECT * FROM razza WHERE (id_razza != 12000 && id_razza != 110000) ORDER BY sing_m", 'result');
    /*cambiamo la razza e i talenti al pg*/
    
    if(isset($_POST['op']) === true) {
    if(gdrcd_filter('get', $_POST['op']) == 'delete') {
    $nome = $_POST['pg'];
    $razza = $_POST['razza'];
    //cambio razza a pg
    
    gdrcd_query("UPDATE personaggio SET id_razza = $razza WHERE nome = '$nome'");
    //ora pensiamo ai talenti.
    //FASE 1: cancello tutti i talenti
    gdrcd_query("DELETE from clgpersonaggioabilita WHERE id_abilita < 31 && nome = '$nome'"); /* CANCELLO SMS PG*/
    //FASE 2: richiamo i talenti e inserisco
    $talento_done = gdrcd_query("SELECT * FROM abilita WHERE (id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza'])) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -1) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -2) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -3) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -4) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -5) . " || id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -6) . "|| id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -7) . "|| id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza']) -8) . ")", 'result');
    while ($row = gdrcd_query($talento_done, 'fetch')) {
                gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['pg']))) . "', " . gdrcd_filter('num', $row['id_abilita']) . ", '1')");
                }
   //FASE 3: Avviso l'utente
   if($_SESSION['login'] != $_POST['pg']) {
   $mex = "Ti è stato cambiato lo spirito come richiesto. I talenti a esso legati sono stati cambiati e settati sul liv 1 come da manuale nella sezione: GlI SPIRITI E I TALENTI";
   gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('Staff', '".gdrcd_filter('in', $_POST['pg'])."', NOW(), '".$mex."')");
                }
   echo "<font color='red'><b>Spirito a $nome cambiato + Talenti cambiati + Messaggio inoltrato</b></font><br>";
    }
    }
    ?>
    
    <center>
<font size=4>Cambia spirito a personaggio</font><br/><br/>
          <br><br> 
          <form action="main.php?page=gestione_cambio_razza" method="Post">
          
                        <select name="pg">
                            <?php      
                                echo '<optgroup label="Liv 1">';
                                while($option = gdrcd_query($liv_1, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 2">';
                                while($option = gdrcd_query($liv_2, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 3">';
                                while($option = gdrcd_query($liv_3, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 4">';
                                while($option = gdrcd_query($liv_4, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 5">';
                                while($option = gdrcd_query($liv_5, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 6">';
                                while($option = gdrcd_query($liv_6, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 7">';
                                while($option = gdrcd_query($liv_7, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 8">';
                                while($option = gdrcd_query($liv_8, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            echo '<optgroup label="Liv 9">';
                                while($option = gdrcd_query($liv_9, 'fetch')) { ?>
                                <option value="<?php echo $option['nome']; ?>">
                                    <?php echo gdrcd_filter('out', $option['nome']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($liv_1, 'free');
                            gdrcd_query($liv_2, 'free');
                            gdrcd_query($liv_3, 'free');
                            gdrcd_query($liv_4, 'free');
                            gdrcd_query($liv_5, 'free');
                            gdrcd_query($liv_6, 'free');
                            gdrcd_query($liv_7, 'free');
                            gdrcd_query($liv_8, 'free');
                            gdrcd_query($liv_9, 'free');
                            ?>
                        </select>
                    
                        <select name="razza">
                         <?php                          
                             while($option = gdrcd_query($race, 'fetch')) { ?>
                                <option value="<?php echo $option['id_razza']; ?>">
                                    <?php echo gdrcd_filter('out', $option['sing_m']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($race, 'free');
                            ?>
                            </select>
 
 <input type="hidden" name="op" value="delete" />
 <input type="submit" value="Cambia spirito"> 
  
 </form>

    <?php
    
    
    }//fine