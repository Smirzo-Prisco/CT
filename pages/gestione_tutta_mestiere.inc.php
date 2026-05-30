<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_adm_gilde">

<!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['page_name'].' '.strtolower($PARAMETERS['names']['guild_name']['plur'])); ?></h2>
    </div>
    
    <!-- Box principale -->
    <div class="page_body">
    <?php /*modifica o elimina*/
            if(gdrcd_filter('get', $_POST['op']) == 'Modifica') {
            gdrcd_query("UPDATE clgpersonaggiomestiere SET id_ruolo=".gdrcd_filter('num', $_POST['ruolo'])." WHERE personaggio='".gdrcd_filter('in', $_POST['nome'])."'");
            
            $gilda = "SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".$_POST['ruolo']."";
            $gilda_result = gdrcd_query($gilda, 'result');
            $gilda = gdrcd_query($gilda_result, 'fetch');
            
            gdrcd_query("UPDATE personaggio SET id_mestiere = ".$gilda['mestiere'].", id_ruolo_mestiere=".gdrcd_filter('num', $_POST['ruolo'])." WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            
            /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo_mestiere WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
            
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$titolo)."')");
                }
            /*Valuto l'esistenza di un capo o meno*/
            
           $capo = "SELECT capo FROM ruolo_mestiere WHERE id_ruolo = ".$_POST['ruolo']."";
           $capo_result = gdrcd_query($capo, 'result');
           $capo = gdrcd_query($capo_result, 'fetch');
            //se il capo è 1
            if ($capo['capo'] == 1) {
            
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = "UPDATE privilegi SET capomestiere = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
            } else {
            $query = "INSERT INTO privilegi (nome, capomestiere) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
            }//fine controllo nomina
            gdrcd_query($query);
            
            
            //se il capo è 0
            } else if ($capo['capo'] == 0) {
            
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = gdrcd_query("UPDATE privilegi SET capomestiere = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            }//fine controllo nomina
            }//fine capo
            
            
            } elseif(gdrcd_filter('get', $_POST['op']) == 'Elimina'){
            gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE id_ruolo=".gdrcd_filter('num', $_POST['ruolo'])." AND personaggio='".gdrcd_filter('in', $_POST['nome'])."'");
            
            gdrcd_query("UPDATE personaggio SET id_mestiere=0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET id_ruolo_mestiere=1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            gdrcd_query("UPDATE personaggio SET esperienza_mestiere = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            
            /*Elimino oggetti strike */
            
            $strike = "SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".$_POST['ruolo']."";
            $strike_result = gdrcd_query($strike, 'result');
            $jpa = gdrcd_query($strike_result, 'fetch');

            if ($jpa['mestiere'] == 1) {
            
            $delete_strike_item = gdrcd_query("SELECT clgpersonaggiooggetto.*, oggetto.* FROM clgpersonaggiooggetto LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto WHERE oggetto.tipo = '10' AND clgpersonaggiooggetto.nome = '".gdrcd_filter('in', $_POST['nome'])."' ", 'result');            
            while ($rowe = mysqli_fetch_array($delete_strike_item)){
            
            $pg = $_POST['nome'];
            $delete_strike_obj = $rowe['id_oggetto'];
            echo "<script type='text/javascript'>alert('$pg');</script>";
            echo "<script type='text/javascript'>alert('$delete_strike_obj');</script>";
            
            gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto=".gdrcd_filter('num', $delete_strike_obj)." AND nome='".gdrcd_filter('in', $pg)."'");
            }
            gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita= 40 AND nome='".gdrcd_filter('in', $_POST['nome'])."'");
            }
            
            /*Confermo l'operazione*/
            
            /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo_mestiere WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
            
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$titolo)."')");
                }
            
            /*Rimuovo l'eventuale capogildatura*/
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = gdrcd_query("UPDATE privilegi SET capomestiere = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
            }
            
            
            } elseif(gdrcd_filter('get', $_POST['op']) == 'Inserisci'){
                       $jobs = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiomestiere WHERE personaggio = '".gdrcd_filter('in', $_POST['nome'])."'");

            /*Se il personaggio ha raggiunto il limite*/
            if($jobs['COUNT(*)'] >= $PARAMETERS['settings']['guilds_limit']) {
                echo '<div class="warning">'.gdrcd_filter('out', $_POST['nome'].' '.$MESSAGE['interface']['adm_guilds']['cannot_hire']).'</div>';
            } else {
                /*Opero l'affiliazione*/
                
                gdrcd_query("INSERT INTO clgpersonaggiomestiere  (personaggio, id_ruolo, scadenza) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".$_POST['ruolo'].", NOW())");

                $gilda = "SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".$_POST['ruolo']."";
                $gilda_result = gdrcd_query($gilda, 'result');
                $gilda = gdrcd_query($gilda_result, 'fetch');
				gdrcd_query("UPDATE personaggio SET id_mestiere = ".$gilda['mestiere'].", id_ruolo_mestiere = ".$_POST['ruolo']." WHERE nome = '".$_POST['nome']."'");      
                
                if ($gilda['mestiere'] == 1) {
                $check_fuoco = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome = '".$_POST['nome']."' && id_abilita = 40", 'result');
                if(gdrcd_query($check_fuoco, 'num_rows') == 0) {
                gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['nome']))) . "', '40', '1')");
                }//fine numrow
                
                $check_bianca = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome = '".$_POST['nome']."' && id_abilita = 41", 'result');
                if(gdrcd_query($check_bianca, 'num_rows') == 0) {
                gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['nome']))) . "', '41', '1')");
                }//fine numrow
                }//fine if
                
                /*Confermo l'operazione*/
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_hire']).'</div>';

                /*Avviso l'utente*/
                $ruolo_name = "SELECT * FROM ruolo_mestiere WHERE id_ruolo =". $_POST['ruolo'] ."";
                $nome = gdrcd_query($ruolo_name);
                $titolo = $nome['nome_ruolo'];
            
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$titolo)."')");
                }
                
                /*Eventuale Capogildatura*/
           $capo = "SELECT capo FROM ruolo_mestiere WHERE id_ruolo = ".$_POST['ruolo']."";
           $capo_result = gdrcd_query($capo, 'result');
           $capo = gdrcd_query($capo_result, 'fetch');
            //se il capo è 1
            if ($capo['capo'] == 1) {
            
            $nomina = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
            if(gdrcd_query($nomina, 'num_rows') > 0) {
            $query = "UPDATE privilegi SET capomestiere = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
            } else {
            $query = "INSERT INTO privilegi (nome, capomestiere) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
            }//fine controllo nomina
            gdrcd_query($query);
            }//fine capo
            }
            }
            
    /*************** PERMESSI DI ACCESSO ***************/ 
    if ($_SESSION['admin'] != 1) {
    echo "<img src='https://gifrific.com/wp-content/uploads/2017/11/you-shall-not-pass-gandalf-lotr.gif' align='center'>";
    } else {
    ?>
    
    
    
    
    <!----------- INIZIAMO CON I GILDATI ------------->
    <table class="customTable">
    <tr>
    <td colspan="3">
    Gestione Mestiere
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

                    $people = "SELECT * FROM personaggio WHERE id_mestiere < 1 ORDER BY nome";
                    $members = "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.* FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo ORDER BY ruolo_mestiere.mestiere DESC";
                    $people_result = gdrcd_query($people, 'result');
                    $members_result = gdrcd_query($members, 'result');
    
                
                
                
                
                
                /*Seleziono i membri*/
                while($row = gdrcd_query($members_result, 'fetch')) {
                ?>
    
    <form action="main.php?page=gestione_tutta_mestiere" method="post">
    <tr>
    <td>
    <input name="nome" type=hidden value="<?php echo $row['personaggio']; ?>">
    <img src="imgs/mestieri/<?php echo $row['immagine']; ?>" border=0 title="<?php echo $row['nome_ruolo']; ?>">
    <b><a href="main.php?page=scheda&pg=<?php echo $row['personaggio']; ?>" target=_top><?php echo  $row['personaggio']; ?></a></b>
    </td>
    
    <td>
    <select name="ruolo">
    <?php
    //seleziono la gerarchia del mestiere

    $query = "SELECT ruolo_mestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.capo, ruolo_mestiere.mestiere, mestiere.nome FROM ruolo_mestiere LEFT JOIN mestiere ON ruolo_mestiere.mestiere = mestiere.id_mestiere WHERE ruolo_mestiere.mestiere > 0 ORDER BY mestiere.nome, ruolo_mestiere.capo DESC, ruolo_mestiere.nome_ruolo";
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
    Assumi nuovo personaggio
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
    <form action="main.php?page=gestione_tutta_mestiere" method="post">
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
    //seleziono la gerarchia del mestiere
    $query = "SELECT ruolo_mestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.capo, ruolo_mestiere.mestiere, mestiere.nome FROM ruolo_mestiere LEFT JOIN mestiere ON ruolo_mestiere.mestiere = mestiere.id_mestiere WHERE ruolo_mestiere.mestiere > 0 ORDER BY mestiere.nome, ruolo_mestiere.capo DESC, ruolo_mestiere.nome_ruolo";
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
     <input type="submit" name="submit" value="Assumi personaggio" /><br>
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