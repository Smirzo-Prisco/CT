    <?php /*HELP: */


        /*************** PERMESSI DI ACCESSO ***************/ 
        if ($_SESSION['admin'] != 1 && $_SESSION['capogilda'] != 1 && $_SESSION['capomestiere'] != 1 && $_SESSION['master'] != 1 && $_SESSION['moderatore'] != 1) {

         echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
         
         } else { ?>
         
         <!-- Titolo della pagina -->
        <div class="page_title">
            <h2><?php echo gdrcd_filter('out',
                    $MESSAGE['interface']['administration']['log']['chat']['page_name']); ?></h2>
        </div>
        
        <!-- Corpo della pagina -->
        <div class="page_body">
        <?php /*Form di scelta del log (visualizzazione di base)*/
            if ((isset($_POST['op']) === false) && (isset($_REQUEST['op']) === false))
            { ?>
            
         
                <div class="panels_box">
                    <div class="form_gestione">
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    <?php //log utente solo ad admin e capogilda
                    if ($_SESSION['admin'] == 1 || $_SESSION['capogilda'] == 1) {
                    ?>
                    <form action="main.php?page=log_chat" method="post">
                    <?php
                    if ($_SESSION['admin'] == 1) {
                    $result = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome", 'result'); 
                    } else if ($_SESSION['capogilda'] == 1) {
                    $result = gdrcd_query("SELECT clgpersonaggioruolo.*, ruolo.* FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo=ruolo.id_ruolo WHERE ruolo.gilda IN (SELECT ruolo.gilda FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio= '".$_SESSION['login']."' AND ruolo.gilda>-1) ORDER BY clgpersonaggioruolo.personaggio", 'result'); 
                    } else if ($_SESSION['capomestiere'] == 2) {
                    $result = gdrcd_query("SELECT clgpersonaggiomestiere.*, ruolo_mestiere.* FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1) ORDER BY clgpersonaggiomestiere.personaggio", 'result'); 
                    }
                    ?>
                    
                    <div class='form_label'>
                    <?php echo gdrcd_filter('out',
                    $MESSAGE['interface']['administration']['log']['chat']['log_by_user']); ?>
                    </div>
                    
                    <div class='form_field'>
                                <select name="pg">
                                    <?php 
                                    if ($_SESSION['capogilda'] == 1) {
                                    echo '<optgroup label="Membri">'; 
                                    }
                                    while ($row = gdrcd_query($result, 'fetch'))
                                    { 
                                    $id_inclinazione = $row['gilda'];                                    
                                    ?>
                                        <option value="<?php if ($_SESSION['admin'] == 1) { echo gdrcd_filter('out', $row['nome']); } else if ($_SESSION['capogilda'] == 1) { echo gdrcd_filter('out', $row['personaggio']); } else if ($_SESSION['capomestiere'] == 2) { echo gdrcd_filter('out', $row['personaggio']); } ?>">
                                            <?php if ($_SESSION['admin'] == 1) { echo gdrcd_filter('out', $row['nome']); } else if ($_SESSION['capogilda'] == 1) { echo gdrcd_filter('out', $row['personaggio']); } 
                                            ?>
                                        </option>
                                    <?php
                                    }//fine while
                                    
                                    gdrcd_query($result, 'free');
                                    ?>
                                </select>
                            </div>
                            
                            <!-- bottoni -->
                            <div class='form_submit'>
                                <input type="hidden"
                                       value="view_user"
                                       name="op"/>
                                <input type="submit"
                                       value="<?php echo gdrcd_filter('out',
                                           $MESSAGE['interface']['forms']['submit']); ?>"/>
                            </div>
                    </form>
                    <?php }//fine log utente ?>
                    
                    
                    
 <?php //log chat solo ad admin e master
                    if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1 || $_SESSION['moderatore'] == 1) {
                    ?>
                    <form action="main.php?page=log_chat" method="post">
                    
                    <?php
                            $result = gdrcd_query("SELECT nome, id FROM mappa WHERE chat=1 && privata=0 ORDER BY nome", 'result'); 
                     ?>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['administration']['log']['chat']['log_by_room']); ?>
                            </div>
                            
                            <div class='form_field'>
                                <select name="luogo">
                                    <?php while ($row = gdrcd_query($result, 'fetch'))
                                    { ?>
                                        <option value="<?php echo gdrcd_filter('out', $row['id']); ?>">
                                            <?php echo gdrcd_filter('out', $row['nome']); ?>
                                        </option>
                                    <?php }//while

                                    gdrcd_query($result, 'free');
                                    ?>
                                </select>
                            </div>
                            
                    <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['administration']['log']['chat']['begin']); ?>
                            </div>
                            
                            <div class='form_field'>
                                <!-- Giorno -->
                                <select name="day_b" class="day">
                                    <?php for ($i = 1; $i <= 31; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Mese -->
                                <select name="month_b" class="month">
                                    <?php for ($i = 1; $i <= 12; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Anno -->
                                <select name="year_b" class="year">
                                    <?php for ($i = 2020; $i <= strftime('%Y') + 5; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select> -
                                <!-- Ora -->
                                <select name="hour_b" class="month">
                                    <?php for ($i = 0; $i <= 23; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02s', $i); ?></option>
                                    <?php }//for
                                    ?>
                                </select>:
                                <!-- Minuto -->
                                <select name="minut_b" class="month">
                                    <?php for ($i = 0; $i <= 60; $i += 5)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02s', $i); ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['administration']['log']['chat']['end']); ?>
                            </div>
                            
                            <div class='form_field'>
                                <!-- Giorno -->
                                <select name="day_e" class="day">
                                    <?php for ($i = 1; $i <= 31; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Mese -->
                                <select name="month_e" class="month">
                                    <?php for ($i = 1; $i <= 12; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Anno -->
                                <select name="year_e" class="year">
                                    <?php for ($i = 2020; $i <= strftime('%Y') + 5; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select> -
                                <!-- Ora -->
                                <select name="hour_e" class="month">
                                    <?php for ($i = 0; $i <= 23; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02s', $i); ?></option>
                                    <?php }//for
                                    ?>
                                </select>:
                                <!-- Minuto -->
                                <select name="minut_e" class="month">
                                    <?php for ($i = 0; $i <= 60; $i += 5)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo sprintf('%02s', $i); ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                            </div>
                            <!-- bottoni -->
                            <div class='form_submit'>
                                <input type="hidden"
                                       value="view_date"
                                       name="op"/>
                                <input type="submit"
                                       value="<?php echo gdrcd_filter('out',
                                           $MESSAGE['interface']['forms']['submit']); ?>"/>
                            </div>
                    </form>
                    <?php }//fine log ?>
                    </div>
                </div>                   
                    
                    
                    
                    
                    
                    
                    
                    
        <?php }//fine form di scelta ?>
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
       <!---------------- LOG UTENTE ------------------------->
       <?php //*Elenco log*/

            if (isset($_REQUEST['op']) == 'view_user')
            {
                
                //Lettura record
                $result = gdrcd_query("SELECT chat.*, mappa.nome FROM chat LEFT JOIN mappa ON chat.stanza=mappa.id WHERE (chat.tipo = 'P' || chat.tipo = 'C') && chat.mittente = '" . $_REQUEST['pg'] . "' GROUP BY ora ORDER by date(ora) DESC, id ASC LIMIT 0,1000", 'result');
                $data_corrente = '';                
                
                
                
                while ($row = gdrcd_query($result, 'fetch'))                
                 { 
                // prendiamo solo la data: "2022-01-01 23:59:59" -> "2022-01-01"
                $data = substr($row['ora'], 0, 10);

                if ($data != $data_corrente) {

                // chiude la lista precedente, stampa la data, apre una nuova lista
                if ($data_corrente) { echo "<hr>"; }
                echo "<font color=white><h2>" . gdrcd_format_date($data) . "</h2></font>";
            
                $data_corrente = $data;
                                               }
                
                switch ($row['tipo'])
                {
                case 'P':		  
                $add_chat.= '<div align="justify">';
				$row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>»', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                ?>
                
                <div align="justify">
                <span class="chat_time"><?= gdrcd_format_time($row['ora']) ?></span>
                <b><?= htmlspecialchars($row['mittente']) ?></b>
                <span class="chat_msg"><?= gdrcd_chatme($_REQUEST['pg'], $row['testo']) ?></span>
                </div>  
                
                <?php
                
                break;
                
                case 'C':
                
                ?>
                
                <div align="justify">
                <span class="chat_row__C"><?= gdrcd_format_time($row['ora']) ?></span>
                <span class="chat_skill"><?= $row['testo'] ?></span>
                </div>  
                
                <?php
                
                break;
                            } //switch
                            

                            } //while
                            gdrcd_query($result, 'free');

                ?>
                
                

            <?php }//fine log pg  ?>
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            <!------------------------- LOG CHAT ------------------------->
            
            <?php //*Elenco log*/

            if (isset($_REQUEST['op']) == 'view_date')
            {
            //Conteggio record totali
                $record_globale = gdrcd_query("SELECT COUNT(*) FROM chat WHERE stanza = '" . gdrcd_filter('get', $_REQUEST['luogo']) . "'");
                $totaleresults = $record_globale['COUNT(*)'];

                //Lettura record
                $date_b = gdrcd_filter('num', $_REQUEST['year_b']) . '-' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['month_b'])) . '-' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['day_b'])) . ' ' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['hour_b'])) . ':' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['minut_b'])) . ':00';
                $date_e = gdrcd_filter('num', $_REQUEST['year_e']) . '-' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['month_e'])) . '-' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['day_e'])) . ' ' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['hour_e'])) . ':' . sprintf('%02s',
                        gdrcd_filter('num', $_REQUEST['minut_e'])) . ':00';

                
                //admin leggono tutto
                if ($_SESSION['admin'] == 1) {
                $result = gdrcd_query("SELECT * FROM chat WHERE stanza = '" . gdrcd_filter('get',
                        $_REQUEST['luogo']) . "' AND ora >= '" . $date_b . "' AND ora <= '" . $date_e . "' ORDER BY id ASC",
                    'result');
                    } else {
                $result = gdrcd_query("SELECT * FROM chat WHERE tipo != 'S' && backing = '0' && stanza = '" . gdrcd_filter('get',
                        $_REQUEST['luogo']) . "' AND ora >= '" . $date_b . "' AND ora <= '" . $date_e . "' ORDER BY id ASC",
                    'result');    
                    }
                while ($row = gdrcd_query($result, 'fetch'))
                            {
                 
                 //stampiamo la pagina chat
                
                switch ($row['tipo'])
                {
                case 'P':		  
                $add_chat.= '<div align="justify">';
				$row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>»', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#ce846f>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                $row['testo'] = str_replace('{', '<font color=green>', $row['testo']);
                $row['testo'] = str_replace('}', '</font>', $row['testo']);
                
                $add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
                $add_chat.= '<b>'.$row['mittente'].'</b>';
                $add_chat.= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                $add_chat.= '</div>';   
                break;
                
                case 'C':
                $add_chat.= '<div class="chat_row_'.$row['tipo'].'" align="justify">';
                $add_chat.= '<span class="chat_skill">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_skill">'.gdrcd_filter('out',$row['testo']).'</span>';
                $add_chat.= '</div>'; 
                break;
                
                case 'M':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
                
                $row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '[<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>]', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
                $row['testo'] = str_replace('{', '<font color=green>', $row['testo']);
                $row['testo'] = str_replace('}', '</font>', $row['testo']);
                
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                
                $add_chat.= '<TABLE style="width:100%;"><TR><TD align=justify>
                <fieldset style="border:1px solid #888888; padding: 10px; text-align:justify;">';
				$add_chat.= '<legend style="border:1px solid purple; color:purple; font-size:11px; text-align: center;">'.gdrcd_format_time($row['ora']).' : Master Screen</legend>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $row['testo'], true).'</span>';
                $add_chat.= '</td></tr></table>';
                
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			    break;
                
                case 'G':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				#$add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                
                $add_chat.= '<TABLE style="width:100%;"><TR><TD align=justify>
                <fieldset style="border:1px solid lightblue; padding: 10px; text-align:justify;">';
				$add_chat.= '<legend style="border:1px solid lightblue; color:lightblue; font-size:11px; text-align: center;">'.gdrcd_format_time($row['ora']).' : Fato Globale</legend>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                $add_chat.= '</td></tr></table>';
                
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			    break;
                            } //switch
                            } //while
                            echo $add_chat;
                            gdrcd_query($result, 'free');

                ?>
            
            
            
            
            <?php }//fine log chat ?>
        </div>
<?php }//fine permessi ?>