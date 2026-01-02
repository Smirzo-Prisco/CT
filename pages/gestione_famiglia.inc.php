<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_adm_gilde">

<!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['page_name'].' '.strtolower($PARAMETERS['names']['guild_name']['plur'])); ?></h2>
    </div>
    
    <!-- Box principale -->
    <div class="page_body">
    <?php   
            
    
    
            /*modifica o elimina*/
            if(gdrcd_filter('get', $_POST['op']) == 'Modifica') {
            gdrcd_query("UPDATE clgpersonaggioruolo SET id_ruolo=".gdrcd_filter('num', $_POST['ruolo'])." WHERE personaggio='".gdrcd_filter('in', $_POST['nome'])."'");
            
            gdrcd_query("UPDATE personaggio SET id_ruolo_gilda=".gdrcd_filter('num', $_POST['ruolo'])." WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            
             /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
                $new_level = $nome['livello'];
                
                if ($new_level < $_POST['old_level']) {
                #echo "<script type='text/javascript'>alert('Degradazione');</script>";
                //seleziono log parametri
                
                $spesa = "SELECT sum(forza) AS total_for, sum(destrezza) AS total_des, sum(mente) AS total_men, sum(tempra) AS total_tem, sum(punto_skill) AS total_pun FROM log_spesa WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."' AND livello > '".gdrcd_filter('num', $new_level)."'";                
                $record_spesa = gdrcd_query($spesa);
                $forza = $record_spesa['total_for'];
                $destrezza = $record_spesa['total_des'];
                $mente = $record_spesa['total_men'];
                $tempra = $record_spesa['total_tem'];
                $punto = $record_spesa['total_pun'];

                gdrcd_query("UPDATE personaggio SET car0 = car0 - $forza, car1 = car1 - $forza,
                             car2 = car2 - $destrezza, car3 = car3 - $destrezza,
                             car4 = car4 - $mente, car5 = car5 - $mente,
                             car6 = car6 - $tempra, car7 = car7 - $tempra,
                             punto_skill = punto_skill - $punto
                             WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
                
                //ora pensiamo alle abilità  
                 
                 $query_skill = gdrcd_query("SELECT id_abilita, nome, SUM(grado) AS totale
                                             FROM log_spesa
                                             WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."' AND livello > '".gdrcd_filter('num', $new_level)."'
                                             GROUP BY id_abilita, nome
                                             HAVING COUNT(*) > 0", 'result');

                 while($row = gdrcd_query($query_skill, 'fetch')) {
                 $id_abilita = $row['id_abilita'];
                 $tot = $row['totale'];
               
               gdrcd_query("UPDATE clgpersonaggioabilita SET grado = grado - $tot 
                            WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'
                            AND id_abilita = '".gdrcd_filter('num', $id_abilita)."'");
                
                gdrcd_query("DELETE FROM clgpersonaggioabilita
                            WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'
                            AND grado < 1");
                            
                }
                //ora cancelliamo i log
                gdrcd_query("DELETE FROM log_spesa WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."' AND livello > '".gdrcd_filter('num', $new_level)."'");             
                //FINE
                
                
                }
                
                
                
            /*Valuto l'esistenza di un capo o meno*/
            
           $capo = "SELECT capo FROM ruolo WHERE id_ruolo = ".$_POST['ruolo']."";
           $capo_result = gdrcd_query($capo, 'result');
           $capo = gdrcd_query($capo_result, 'fetch');
            //se il capo è 1
            if ($capo['capo'] == 1) {
            
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = "UPDATE privilegi SET capogilda = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
            } else {
            $query = "INSERT INTO privilegi (nome, capogilda) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
            }//fine controllo nomina
            gdrcd_query($query);
            
            
            //se il capo è 0
            } else if ($capo['capo'] == 0) {
            
            $nomina = gdrcd_query("SELECT admin, master, moderatore, capomestiere, guida, grafico FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = gdrcd_query("UPDATE privilegi SET capogilda = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            }//fine controllo nomina
            }//fine capo
            
            
            } elseif(gdrcd_filter('get', $_POST['op']) == 'Elimina'){
            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE id_ruolo=".gdrcd_filter('num', $_POST['ruolo'])." AND personaggio='".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome ='".gdrcd_filter('in', $_POST['nome'])."' AND id_abilita > '42'");
            gdrcd_query("DELETE FROM log_spesa WHERE nome ='".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET id_gilda=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET id_ruolo_gilda=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            /*Confermo l'operazione*/
            
             /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
            
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['fire'].' '.$titolo)."')");
                }
            
            /*Rimuovo l'eventuale capogildatura*/
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = gdrcd_query("UPDATE privilegi SET capogilda = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            }
            
            /*Rimuovo le stats*/
            gdrcd_query("UPDATE personaggio SET car0=car0-car1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car2=car2-car3 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car4=car4-car5 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car6=car6-car7 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car1=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car3=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car5=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET car7=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET punto_skill=0, esperienza_s=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");

            
            } elseif(gdrcd_filter('get', $_POST['op']) == 'Inserisci'){
                       $jobs = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioruolo WHERE personaggio = '".gdrcd_filter('in', $_POST['nome'])."'");

            /*Se il personaggio ha raggiunto il limite*/
            if($jobs['COUNT(*)'] >= $PARAMETERS['settings']['guilds_limit']) {
                echo '<div class="warning">'.gdrcd_filter('out', $_POST['nome'].' '.$MESSAGE['interface']['adm_guilds']['cannot_hire']).'</div>';
            } else {
                /*Opero l'affiliazione*/
                
                gdrcd_query("INSERT INTO clgpersonaggioruolo  (personaggio, id_ruolo, scadenza) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".$_POST['ruolo'].", NOW())");
                gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio='".gdrcd_filter('in', $_POST['nome'])."' LIMIT 1");
                gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE nome='".gdrcd_filter('in', $_POST['nome'])."' && (id_abilita = '37' || id_abilita = '38' || id_abilita > 42)");
                gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE nome='".gdrcd_filter('in', $_POST['nome'])."' && (id_oggetto = '10207' || id_oggetto = '10125' || id_oggetto = '10210' || id_oggetto = '10266')");

                $gilda = "SELECT gilda FROM ruolo WHERE id_ruolo = ".$_POST['ruolo']."";
                $gilda_result = gdrcd_query($gilda, 'result');
                $gilda = gdrcd_query($gilda_result, 'fetch');
				gdrcd_query("UPDATE personaggio SET id_gilda = ".$gilda['gilda'].", id_ruolo_gilda = ".$_POST['ruolo']." WHERE nome = '".$_POST['nome']."'");      
                /*Confermo l'operazione*/
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_hire']).'</div>';


                /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
            
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$titolo)."')");
                }
                
                /*Eventuale Capogildatura*/
           $capo = "SELECT capo FROM ruolo WHERE id_ruolo = ".$_POST['ruolo']."";
           $capo_result = gdrcd_query($capo, 'result');
           $capo = gdrcd_query($capo_result, 'fetch');
            //se il capo è 1
            if ($capo['capo'] == 1) {
            
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = "UPDATE privilegi SET capogilda = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
            } else {
            $query = "INSERT INTO privilegi (nome, capogilda) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
            }//fine controllo nomina
            gdrcd_query($query);
            }//fine capo
            }
            }
            
    /*************** PERMESSI DI ACCESSO ***************/ 
    if ($_SESSION['admin'] != 1 && $_SESSION['capogilda'] != 1) {
    echo "<img src='https://gifrific.com/wp-content/uploads/2017/11/you-shall-not-pass-gandalf-lotr.gif' align='center'>";
    } else {
    ?>
    
    
    
    
    <!----------- INIZIAMO CON I GILDATI ------------->
    <table class="customTable">
    <tr>
    <td colspan="3">
    Gestione Famiglia
    </td>
    </tr>
    <tr class="second_header">
    <td>
    <div>
    Personaggio
    </div>
    </td>
    <td>
    <div>
    Ruoli
    </div>
    </td>
	<td>
    <div>
	</div>
    </td>
    </tr>
    
    <?php
    /***************** ELENCO PG NELLA GILDA *****************/
    
    /*Seleziono i ruoli su cui l'account ha competenza*/
    
                        $people = "SELECT * FROM personaggio WHERE id_gilda < 1 ORDER BY nome";
                        $members = "SELECT clgpersonaggioruolo.*, ruolo.* FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio= '".$_SESSION['login']."' AND ruolo.gilda>-1)  ORDER BY ruolo.id_ruolo, clgpersonaggioruolo.personaggio ASC";
                        $people_result = gdrcd_query($people, 'result');
                        $members_result = gdrcd_query($members, 'result');
    
                
                
                
                
                
                /*Seleziono i membri*/
                while($row = gdrcd_query($members_result, 'fetch')) {
                ?>
    
    <form action="main.php?page=gestione_famiglia" method="post">
    <tr>
    <td>
    <input name="nome" type=hidden value="<?php echo $row['personaggio']; ?>">
    <img src="imgs/guilds/<? echo $row['immagine']; ?>" border=0 title="<? echo $row['nome_ruolo']; ?>">
    <b><a href="main.php?page=scheda&pg=<?php echo $row['personaggio']; ?>" target=_top><?php echo  $row['personaggio']; ?></a></b>
    </td>
    
    <td>
    <select name="ruolo">
    <?php
    //seleziono la gerarchia della gilda
    $query = "SELECT ruolo.id_ruolo, ruolo.nome_ruolo, gilda.nome FROM ruolo JOIN gilda ON ruolo.gilda = gilda.id_gilda WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio= '".$_SESSION['login']."' AND ruolo.gilda>-1) ORDER BY gilda.nome, ruolo.id_ruolo ASC, ruolo.nome_ruolo";
    $result = gdrcd_query($query, 'result');

    while($option = gdrcd_query($result, 'fetch')) { ?>
                                <option value="<?php echo $option['id_ruolo']; ?>" <?php if($row['id_ruolo'] == $option['id_ruolo']) {echo 'SELECTED';} ?>>
                                    <?php echo gdrcd_filter('out', $option['nome_ruolo']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($query, 'free');
                            ?>    
                            
    </select>
    </td>
    <td>
     <div class="form_submit">
                            
                            
                            <input type="submit" name="op" value="Modifica" /><br>
                            <input name="old_level" type="hidden" value="<?php echo $row['livello']; ?>">
                            <input type="submit" name="op" value="Elimina" />
                        </div>
    
    </td>
    </tr>
    </form>
                <?php }//fine while ?>
    </table>
    
    
    
    
    
    
    <br><br>
    
    
    
    
    
    
    <!---------- NUOVI MEMBRI ----------------->
    <table class="customTable">
    <tr>
    <td colspan="3">
    Gilda nuovo personaggio
    </td>
    </tr>
    <tr class="second_header">
    <td>
    <div>
    Personaggio
    </div>
    </td>
    <td>
    <div>
    Ruoli
    </div>
    </td>
	<td>
    <div>
	</div>
    </td>
    </tr>
    <form action="main.php?page=gestione_famiglia" method="post">
    <tr>
    <td>
    <select name="nome">
                                <?php
                                while($row = gdrcd_query($people_result, 'fetch')) { ?>
                                    <option value="<?php echo $row['nome']; ?>">
                                        <?php echo $row['nome'].' '.$row['cognome']; ?>
                                    </option>
                                <?php }
                                gdrcd_query($people_result, 'free');
                                ?>
                            </select>
                            </td>
    
    <td>
    <select name="ruolo">
    <?php
    //seleziono la gerarchia della gilda
    $query = "SELECT ruolo.*, gilda.nome FROM ruolo JOIN gilda ON ruolo.gilda = gilda.id_gilda WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio= '".$_SESSION['login']."' AND ruolo.gilda>-1) ORDER BY gilda.nome, ruolo.id_ruolo ASC, ruolo.nome_ruolo";
    $result = gdrcd_query($query, 'result');

    while($option = gdrcd_query($result, 'fetch')) { ?>
                                <option value="<?php echo $option['id_ruolo']; ?>" <?php if($row['id_ruolo'] == $option['id_ruolo']) {echo 'SELECTED';} ?>>
                                    <?php echo gdrcd_filter('out', $option['nome_ruolo']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($query, 'free');
                            ?>    
                            
    </select>
    </td>
    <td>
     <div class="form_submit">
     <input type="hidden" name="op" value="Inserisci" />
     <input type="submit" name="submit" value="Gilda personaggio" /><br>
                        </div>
    
    </td>
    </tr>
    </form>
    </table>



























<?php
/************** ISTRUZIONI MARCO ************************
Allora Mà, ho diviso il tutto in 2 tabelle.
Nella prima tabella il capogilda promuove/degrada/toglie i propri gildati.
Il campo NOME è un input hidden alla riga 63
<input name=nome type=hidden value="<?php echo $row['personaggio']; ?>">

A destra ci sono i ruoli con un select (riga 69)
<select name="ruolo">

Poi i due submit: modifica per degradare/promuovere ||| Elimina per cacciare
<input type="hidden" name="op" value="update" />
<input type="submit" name="grade" value="Modifica" /><br>
<input type="submit" name="fire" value="Elimina" />


La seconda tabella è per gildare qualcuno. 
A sx ci sono i personaggio che nella tabella personaggio hanno id_gilda < 1 (dunque o sono SIT, SCORPION, WIKKAN o CITTADINI)
Riga 142 (<select name="nome">)
A destra un select con i ruoli.


***************************FINE**************************/
?>














<?php

}//fine if permessi
?>
</div>    
</div>