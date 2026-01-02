<!-- Box presenti-->
<div class="pagina_presenti_estesa">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['logged_users']['page_title']); ?></h2>
    </div>
    <div class="presenti_estesi">
    <?php
    //Numero dei presenti
    $presenti = gdrcd_query("SELECT COUNT(*) AS numero FROM personaggio WHERE (personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW())");

    ?>
    <br> <br>
    
    <table class="customTable">
  <thead>
    <tr>
      <th colspan="8">PRESENTI: <?php echo $presenti['numero']?></th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>AVATAR</td>
      <td>SMS</td>
      <td>RAZZA</td>
      <td>FAMIGLIA</td>
      <td>LAVORO</td>
      <td>NOME E COGNOME</td>
      <td>CARICHE</td>
      <td>OFF</td>
    </tr>
    <?php 
    if ($_SESSION['login'] == 'Mino' || $_SESSION['login'] == 'Lii') {
    $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() ORDER BY personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.nome";
    } else if ($_SESSION['login'] == 'Jamal' || $_SESSION['login'] == 'Alice') {
    $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE (personaggio.nome != 'Megan' || personaggio.nome != 'Niklaus') AND personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() ORDER BY personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.nome";
    } else {
    $query = "SELECT personaggio.nome, personaggio.cognome, personaggio.permessi, personaggio.sesso, personaggio.id_razza, razza.sing_m, razza.sing_f, razza.icon, personaggio.disponibile, personaggio.online_status, personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.posizione, personaggio.ora_entrata, personaggio.ora_uscita, personaggio.ultimo_refresh, mappa.stanza_apparente, mappa.nome as luogo, mappa_click.nome as mappa FROM personaggio LEFT JOIN mappa ON personaggio.ultimo_luogo = mappa.id LEFT JOIN mappa_click ON personaggio.ultima_mappa = mappa_click.id_click LEFT JOIN razza ON personaggio.id_razza = razza.id_razza WHERE personaggio.nome != 'Mino' AND personaggio.ora_entrata > personaggio.ora_uscita AND DATE_ADD(personaggio.ultimo_refresh, INTERVAL 4 MINUTE) > NOW() ORDER BY personaggio.is_invisible, personaggio.ultima_mappa, personaggio.ultimo_luogo, personaggio.nome";
    }
    $result = gdrcd_query($query, 'result');
    $ultimo_luogo_corrente = '';
    $mappa_corrente = '';
    
            while ($record = gdrcd_query($result, 'fetch')) {

            //Stampo il nome del luogo
            if ($record['is_invisible'] == 1)  {
                $luogo_corrente = $MESSAGE['status_pg']['invisible'][1];
            } else {
                if ($record['mappa'] != $mappa_corrente)  {
                    $mappa_corrente = $record['mappa'];
                    echo '<tr class="mappa"><td colspan="8" style="font-weight:bold; text-transform: uppercase;">'.gdrcd_filter('out', $mappa_corrente).'</span></td></tr>';
                }//if

                if (empty($record['stanza_apparente'])) {
                    $luogo_corrente = $record['luogo'];
                } else  {
                    $luogo_corrente = $record['stanza_apparente'];
                }//else
            }
            //Stampo il nome del luogo solo per il primo PG che vi e' posizionato
            if (empty($luogo_corrente) === true) {
                #echo 'ok';
                /*if ($record['mappa']>=0){
                    $luogo_corrente = $PARAMETERS['names']['maps_location'];
                } else {
                    $luogo_corrente = $PARAMETERS['names']['base_location'];
                }//else*/
                if ($ultimo_luogo_corrente != $luogo_corrente)  {
                    $ultimo_luogo_corrente = $luogo_corrente;
                    echo '<tr class="luogo"><td colspan="8" style="font-weight:bold; text-transform: uppercase;">'.gdrcd_filter('out', $luogo_corrente).'</td></tr>';
                } //if
            } else {
                if ($ultimo_luogo_corrente != $luogo_corrente)   {
                    $ultimo_luogo_corrente = $luogo_corrente;
                    if ($record['is_invisible'] == 0)  {
                        if (($PARAMETERS['mode']['mapwise_links'] == 'OFF')) { #||($record['ultima_mappa']==$_SESSION['mappa'])
                            echo '<tr class="luogo"><td colspan="8" style="font-weight:bold; text-transform: uppercase;"><a href="main.php?dir='.$record['ultimo_luogo'].'&map_id='.$record['ultima_mappa'].'">'.gdrcd_filter('out', $luogo_corrente).'</a></td></tr>';
                        } else  {
                            echo '<tr class="luogo">'.gdrcd_filter('out', $luogo_corrente).'</li>';
                        }
                    } else  {
                        echo '<tr class="luogo"><td colspan="8" style="font-weight:bold; text-transform: uppercase;">'.gdrcd_filter('out', $luogo_corrente).'</td></tr>';
                    }//else
                }
            }//if
            /** * Parametro di personalizzazione di uno stato online via tooltip
             * @author Blancks
             */
            $online_state = '';
            if ($PARAMETERS['mode']['user_online_state'] == 'ON' && ! empty($record['online_status']) && $record['online_status'] != null) {
                $record['online_status'] = trim(nl2br(gdrcd_filter('in', $record['online_status'])));
                $record['online_status'] = strtr($record['online_status'], ["\n\r" => '', "\n" => '', "\r" => '', '"' => '&quot;']);
                $online_state = 'onmouseover="show_desc(event, \''.$record['online_status'].'\');" onmouseout="hide_desc();""';
            }
            //Stampo il PG
            echo '<tr class="presente"'.$online_state.'>';
                
        
            switch ($record['permessi']) {
                case USER:
                    $alt_permessi = '';
                    break;
                case GUILDMODERATOR:
                    $alt_permessi = $PARAMETERS['names']['guild_name']['lead'];
                    break;
                case GAMEMASTER:
                    $alt_permessi = $PARAMETERS['names']['master']['sing'];
                    break;
                case MODERATOR:
                    $alt_permessi = $PARAMETERS['names']['moderators']['sing'];
                    break;
                case SUPERUSER:
                    $alt_permessi = $PARAMETERS['names']['administrator']['sing'];
                    break;
            }//else
            //qua va l'avatirino 
            $query = "SELECT url_img_chat FROM personaggio WHERE personaggio.nome=".'"'.gdrcd_filter('out', $record['nome']).'"';
            $row = gdrcd_query($query);
            echo '<td><img width="50" height="50" src="'.$row['url_img_chat'].'"/></td>';
            
            //SMS
           // echo '<td><a href="main.php?page=messages_center&op=create&reply_dest='.$record['nome'].'" class="link_sheet"><img src="themes/crystal/imgs/presenti/sms_presenti.png"/></a></td>';
				echo'<td>';?>
                <a href="#" onclick="changeFrame('pages/messages/create.inc.php?reply_dest=<?php echo $record['nome']; ?>');document.getElementById('id01').style.display='block';"><img src="themes/crystal/imgs/presenti/sms_presenti.png"/></a>
				<?php
                echo '</td>';
            
            //Icona della razza pg
            if ($record['icon'] == '') {
                $record['icon'] = 'standard_razza.png';
            }
            $query = "SELECT razza.*, personaggio.* FROM razza JOIN personaggio ON personaggio.id_razza = razza.id_razza WHERE personaggio.nome=".'"'.gdrcd_filter('out', $record['nome']).'"';
            $row = gdrcd_query($query);
            echo '<td><img src="imgs/races/'.$row['immagine'].'" alt="'.gdrcd_filter('out', $record['sing_'.$record['sesso']]).'" title="'.gdrcd_filter('out', $record['sing_'.$record['sesso']]).'" /></td>';

			//Ruolo famiglia o inclinazione
            
            $check_id = gdrcd_query("SELECT * FROM clgpersonaggioinclinazione WHERE personaggio = '". $record['nome'] ."'", 'result');
            $num_incl = gdrcd_query($check_id, 'num_rows');
            if ($num_incl == 1) {
            $query = "SELECT inclinazione.*, clgpersonaggioinclinazione.* FROM inclinazione JOIN clgpersonaggioinclinazione ON clgpersonaggioinclinazione.id_ruolo = inclinazione.id_inclinazione WHERE clgpersonaggioinclinazione.personaggio=".'"'.gdrcd_filter('out', $record['nome']).'"';
            $row = gdrcd_query($query);
            
            echo '<td><img width="25" height="25" src="imgs/inclinazioni/'.$row['immagine'].'" title="'.gdrcd_filter('out', $row['nome']).'"/></td>';
            } else if ($row['id_gilda'] > '0') {
            $query = "SELECT clgpersonaggioruolo.personaggio, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio WHERE personaggio.nome=".'"'.gdrcd_filter('out', $record['nome']).'"';
            $row = gdrcd_query($query);
            echo '<td><img width="25" height="25" src="imgs/guilds/'.$row['immagine'].'" title="'.gdrcd_filter('out', $row['nome_ruolo']).'"/></td>';
               } else {
           $id_famiglia = $row['id_ruolo_gilda'];
           $query = "SELECT * FROM ruolo WHERE id_ruolo = '$id_famiglia'";
           $row = gdrcd_query($query);
            echo '<td><img width="25" height="25" src="imgs/guilds/'.$row['immagine'].'" title="'.gdrcd_filter('out', $row['nome_ruolo']).'"/></td>';
               }
            

         
            
            //Mestiere
            
            $query = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere, ruolo.immagine as immagine_famiglia, ruolo_mestiere.immagine as immagine_mestiere FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo WHERE personaggio.nome = '".gdrcd_filter('out', $record['nome'])."'";
            $personaggi = gdrcd_query($query, 'result');     
            $personaggio = gdrcd_query($personaggi, 'fetch');
            echo '<td><img width="25" height="25" src="imgs/mestieri/'.$personaggio['immagine_mestiere'].'" title="'.gdrcd_filter('out', $personaggio['nome_ruolo_mestiere']).'"/></td>';
                      
                      
                      
            //Nome pg e link alla sua scheda
            echo ' <td><a href="main.php?page=scheda&pg='.$record['nome'].'" class="link_sheet gender_'.$record['sesso'].'">'.gdrcd_filter('out', $record['nome']);
            if (empty($record['cognome']) === false) {
                echo ' '.gdrcd_filter('out', $record['cognome']);
            }
            
            echo '</a></td>';
            echo '<td>';
                    
                    //Mestiere
                    $privilegi = "SELECT * FROM privilegi WHERE nome=".'"'.gdrcd_filter('out', $record['nome']).'"';
                    $row_pri = gdrcd_query($privilegi);
            
                    if($row_pri['admin']==1){
                    echo '<img src="themes/crystal/imgs/staff/Admin.png">';
                    }
                    if($row_pri['moderatore']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Moderatore.png">';
                    }
                    if($row_pri['master']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Master.png">';
                    }
                    if($row_pri['guida']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Guida.png">';
                    }
                    if($row_pri['grafico']==1){
                    	echo '<img src="themes/crystal/imgs/staff/Grafico.png">';
                    }
                                 
            
            echo '</td>';
            echo '<td>'.htmlentities($record['online_status']).'</td>';
            echo '</tr>';
        }//while
        gdrcd_query($result, 'free');       
        
        ?>
  </tbody>
</table>
</div>
<!-- Chiusura finestra del gioco -->

