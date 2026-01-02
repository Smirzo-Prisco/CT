<link rel="stylesheet" href="../themes/crystal/mestieri.css">

<div class="pagina_schedam_odifica">
    <?php /*HELP: */

    if (isset($_REQUEST['pg']) === false)
    {
        echo gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']);
    } else
    {
    /*Se ho ricevuto informazioni per modificare*/


    $confirm_updating = true;


    /** * Controllo sul file audio
     * @author Blancks
     */
    if ($PARAMETERS['mode']['allow_audio'] == 'ON')
    {

        if ( ! empty($_POST['modifica_url_media']) && ! isset($PARAMETERS['settings']['audiotype']['.' . strtolower(end(explode('.',
                    $_POST['modifica_url_media'])))])
        )
        {
            echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['media_not_allowed']) . '</div>';
            $confirm_updating = false;
        }


    } elseif ($_POST['op'] == 'modify')
    {
        $_POST['modifica_url_media'] = '';
    }


    /** * Se non sono occorsi errori durante i controlli precedenti
     * @author Blancks
     */
    if ($confirm_updating)
    {

        if (isset($_POST['op']) === true)
        {

            /** * Controllo sul bloccaggio dei suoni per l'utente
             * @author Blancks
             */
            $blocca_media = (strtolower($_POST['blocca_media']) == 'on') ? 1 : 0;

            if ($_SESSION['login'] == $_REQUEST['pg'])
            {
                $_SESSION['blocca_media'] = $blocca_media;
            }


            /*Se l'utente ha richiesto di modificare la propria scheda*/
            if ((gdrcd_filter('get', $_REQUEST['pg']) == $_SESSION['login']) && (gdrcd_filter('get',$_POST['op']) == 'modify'))
            {
                /** * Html, BBcode or both ?
                 * @author Blancks
                 */
                $modifica_affetti = gdrcd_filter('in', $_POST['modifica_affetti']);
                $modifica_background = gdrcd_filter('in', $_POST['modifica_background']);
                $modifica_volto = gdrcd_filter('in', $_POST['modifica_volto']);
                $modifica_descrizione = gdrcd_filter('in', $_POST['modifica_descrizione']);
                $modifica_place = gdrcd_filter('in', $_POST['modifica_place']);
                $modifica_age = gdrcd_filter('num', $_POST['modifica_age']);
                $modifica_off = gdrcd_filter('in', $_POST['modifica_off']);
                $modifica_note_fato = gdrcd_filter('in', $_POST['modifica_note_fato']);
                $modifica_particolari = gdrcd_filter('in', $_POST['modifica_particolari']);
                $modifica_principale = gdrcd_filter('in', $_POST['modifica_principale']);
                $nickname = gdrcd_filter('in', $_POST['nickname']);

               
                if ($PARAMETERS['mode']['user_bbcode'] == 'OFF' || ($PARAMETERS['mode']['user_bbcode'] == 'ON' && $PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd' && $PARAMETERS['settings']['bbd']['free_html'] == 'ON'))
                {
                    $modifica_affetti = gdrcd_filter('addslashes', $_POST['modifica_affetti']);
                    $modifica_background = gdrcd_filter('addslashes', $_POST['modifica_background']);
                    $modifica_volto = gdrcd_filter('addslashes', $_POST['modifica_volto']);
                    $modifica_descrizione = gdrcd_filter('addslashes', $_POST['modifica_descrizione']);
                    $modifica_place = gdrcd_filter('addslashes', $_POST['modifica_place']);
                    $modifica_age = gdrcd_filter('addslashes', $_POST['modifica_age']);
                    $modifica_off = gdrcd_filter('addslashes', $_POST['modifica_off']);
                	$modifica_note_fato = gdrcd_filter('addslashes', $_POST['modifica_note_fato']);
                	$modifica_particolari = gdrcd_filter('addslashes', $_POST['modifica_particolari']);
                    $modifica_principale = gdrcd_filter('addslashes', $_POST['modifica_principale']);
                    $nickname = gdrcd_filter('addslashes', $_POST['nickname']);
                }

                /** * Online status allowed ?
                 * @author Blancks
                 */
                $online_state = '';

                if ($PARAMETERS['mode']['user_online_state'] == 'ON')
                {
                    $online_state = gdrcd_filter('in', $_POST['online_state']);
                }


                gdrcd_query("UPDATE personaggio SET cognome = '" . gdrcd_filter('in',
                        $_POST['modifica_cognome']) . "', volto = '" . $modifica_volto . "', affetti = '" . $modifica_affetti . "', storia = '" . $modifica_background . "', off = '" . $modifica_off . "', principale = '" . $modifica_principale . "', descrizione = '" . $modifica_descrizione . "', 
                        eta = " . (int) $modifica_age . ", natoa = '" . $modifica_place . "', url_media = '" . gdrcd_filter('in',
                        gdrcd_filter('fullurl',
                            $_POST['modifica_url_media'])) . "', blocca_media = " . (int) $blocca_media . ", url_img = '" . gdrcd_filter('in',
                        gdrcd_filter('fullurl',
                            $_POST['modifica_url_img'])) . "', url_img_chat = '" . gdrcd_filter('in',
                        gdrcd_filter('fullurl',
                            $_POST['modifica_url_img_chat'])) . "' WHERE nome = '" . gdrcd_filter('in',
                        $_REQUEST['pg']) . "'");



                /*Se un master o superiore ha richiesto di modificare lo status del pg*/
            } elseif ((gdrcd_filter('get', $_REQUEST['pg']) == $_SESSION['login']) && (gdrcd_filter('get',$_POST['op']) == 'insert_tokyobook')) {
                
                if ($_POST['nickname'] != NULL || $_POST['nickname'] != '') {
                
                $nickname = gdrcd_filter('addslashes', $_POST['nickname']);
                gdrcd_query("INSERT INTO tokyobook (personaggio, nickname) VALUES ('" . gdrcd_filter('in',
                $_REQUEST['pg']) . "', '" . gdrcd_filter('in',
                $nickname) . "')");
                echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['modified']) . '</div>';
                
                } else {
                
                echo '<div class="error">Non sono ammessi campi vuoti</div>';

                }
            
            } elseif ((gdrcd_filter('get', $_REQUEST['pg']) == $_SESSION['login'] || $_SESSION['admin'] == 1) && (gdrcd_filter('post',$_POST['op']) == 'insert_alias_gilda')) {
                
                   if (!empty($_POST['nickname_gilda']) || $_SESSION['admin'] == 1) {

                $nickname_gilda = gdrcd_filter('addslashes', $_POST['nickname_gilda']);
                gdrcd_query("UPDATE clgpersonaggioruolo SET nickname = '" . gdrcd_filter('in',
                $nickname_gilda) . "' WHERE personaggio = '" . gdrcd_filter('in',
                $_REQUEST['pg']) . "'");
                echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['modified']) . '</div>';
                
                } else {
                
                echo '<div class="error">Non sono ammessi campi vuoti</div>';

                }
            
            } elseif (($_SESSION['admin']==1 || $_SESSION['moderatore']==1 || $_SESSION['master']==1) && (gdrcd_filter('get', $_POST['op']) == 'modify_status'))
            {
                gdrcd_query("UPDATE personaggio SET stato = '" . gdrcd_filter('in',
                        $_POST['modifica_status']) . "', salute = " . gdrcd_filter('num',
                        $_POST['modifica_salute']) . ", note_fato = '" . gdrcd_filter('in',
                        $_POST['modifica_note_fato']) . "', particolari = '" . gdrcd_filter('in',
                        $_POST['modifica_particolari']) . "', notorieta = " . gdrcd_filter('num',
                        $_POST['modifica_notorieta']) . "  WHERE nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "'");
                        

                echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['modified']) . '</div>';

                /*Se un master o superiore ha richiesto l'arresto del pg*/
            } elseif (($_SESSION['admin']==1 || $_SESSION['moderatore']==1) && (gdrcd_filter('get', $_POST['op']) == 'arrest'))
            {
                /** * Da implementare */


                /*Se un admin o superiore ha richiesto l'esilio dell'utente*/
            } elseif (($_SESSION['admin']==1 || $_SESSION['moderatore']==1) && (gdrcd_filter('get', $_POST['op']) == 'exile'))
            {
                gdrcd_query("UPDATE personaggio SET esilio = '" . gdrcd_filter('num',
                        $_POST['year']) . '-' . gdrcd_filter('num', $_POST['month']) . '-' . gdrcd_filter('num',
                        $_POST['day']) . "', data_esilio=NOW(), autore_esilio = '" . $_SESSION['login'] . "', motivo_esilio = '" . gdrcd_filter('in',
                        $_POST['causale']) . "' WHERE nome = '" . gdrcd_filter('in',
                        $_REQUEST['pg']) . "' AND permessi <=" . $_SESSION['permessi'] . "");

                echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';

            } else
            {
                echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_operation']) . '</div>';

            }

        } else
        {
            /*Carico le informazioni del PG*/
            $record = gdrcd_query("SELECT descrizione, affetti, principale, storia, off, particolari, note_fato, eta, natoa, cognome, volto, online_status, url_img, url_img_chat, url_media, blocca_media, stato, salute, notorieta FROM personaggio WHERE nome='" . gdrcd_filter('get',
                    $_REQUEST['pg']) . "'");
        }

    }
    ?>

    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['page_name']); ?></h2>
    </div>
    
       <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        <br><br>
    <div class="page_body">
        <?php if (isset($_POST['op']) === false)
        { ?>
            <div class="panels_box">
                <?php
                if ($_SESSION['login'] == $_REQUEST['pg'] || $_SESSION['admin'] == '1')
                {
                    ?>
                    <div class="form_gioco">
                        <!-- Form utente modifica -->
                        <form action="main.php?page=scheda_modifica" method="post">

                            <table class="customTable">
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['last_name']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="modifica_cognome"
                                       value="<?php echo gdrcd_filter('out', $record['cognome']); ?>"
                                       class="form_input"/>
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                            <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['age']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="modifica_age"
                                       value="<?php echo gdrcd_filter('out', $record['eta']); ?>"
                                       class="form_input"/>
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['place']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="modifica_place"
                                       value="<?php echo gdrcd_filter('out', $record['natoa']); ?>"
                                       class="form_input"/>
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['volto']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="modifica_volto" id="volto"
                                       value="<?php echo gdrcd_filter('out', $record['volto']); ?>"
                                       class="form_input"/><br>
                                       
                                       <input type=button value="Controlla" onClick="checkVolto()">
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['url_img']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="modifica_url_img"
                                       value="<?php echo gdrcd_filter('out', $record['url_img']); ?>"
                                       class="form_input"/>
                            </td>
                            </tr>
                            <?php
                            /** * Avatar di chat
                             * @author Blancks
                             */
                                ?>
                                <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                    <?php echo gdrcd_filter('out',
                                        $MESSAGE['interface']['sheet']['modify_form']['url_img_chat']); ?>
                                </td>
                                </tr>
                                <tr>
                                <td>
                                    <input type="text" name="modifica_url_img_chat"
                                           value="<?php echo gdrcd_filter('out', $record['url_img_chat']); ?>"
                                           placeholder="No gif animate" class="form_input"/>
                                </td>
                                </tr>

                            <?php
                            if ($PARAMETERS['mode']['user_online_state'] == 'ON')
                            {
                              
                            
                                
                            }
                            ?>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['principale']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <textarea type="textbox" name="modifica_principale"
                                          cols=80 rows=15 class="ares"><?php echo gdrcd_filter('out',
                                        $record['principale']); ?></textarea>
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['background']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <textarea type="textbox" name="modifica_background"
                                          cols=80 rows=15 class="ares"><?php echo gdrcd_filter('out',
                                        $record['storia']); ?></textarea>
                            </td>
                            </tr>
                            

                            <div class='form_label' style="display:none;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['friends']); ?>
                            </div>
                            <div class='form_field' style="display:none;">
                                <textarea type="textbox" name="modifica_affetti"
                                          class="form_textarea"><?php echo gdrcd_filter('out',
                                        $record['affetti']); ?></textarea>
                            </div>
                            
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['description']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <textarea type="textbox" name="modifica_descrizione"
                                          cols=80 rows=15 class="ares"><?php echo gdrcd_filter('out',
                                        $record['descrizione']); ?></textarea>
                            </td>
                            </tr>
   						 
                         <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['off']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <textarea type="textbox" name="modifica_off"
                                          cols=80 rows=15 class="ares"><?php echo gdrcd_filter('out',
                                        $record['off']); ?></textarea>
                            </td>
                            </tr>
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['block_media']); ?>
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="checkbox"
                                       name="blocca_media" <?php echo ($record['blocca_media']) ? 'checked="checked"' : ''; ?>
                                       class="form_input"/>
                            </td>
                            </tr>

                            <?php
                            if ($PARAMETERS['mode']['allow_audio'] == 'ON')
                            {
                                ?>
                                <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                                    MUSICA IN SCHEDA
                             </td>
                             </tr>
                             <tr>
                             <td>
                                    <input type="text" name="modifica_url_media"
                                           value="<?php echo gdrcd_filter('out', $record['url_media']); ?>"
                                           class="form_input"/>
                                </td>
                                </tr>
                                <?php
                            }
                            ?>
                            
                            <tr>
                            <td>
                            <input type="hidden" name="op" value="modify"/>

                            
                                <input type="submit" value="<?php echo $MESSAGE['interface']['forms']['submit']; ?>"
                                       class="form_submit"/>
                                <input type="hidden"
                                       value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                       name="pg"/>
                            </td>
                            </tr>
                            </table>

                        </form>
                        

                            <form action="main.php?page=scheda_modifica" method="post">
                            <?php
                        /*Carico le informazioni del PG*/
                    $tokyo = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio = '" . gdrcd_filter('get',
                    $_REQUEST['pg']) . "'");
                                ?>
                    <table class="customTable">
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                            Alias da usare nel Tokyobook<br> (<i>Non sarà più possibile modificarlo</i>)
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <input type="text" name="nickname"
                                       value="<?php echo gdrcd_filter('out', $tokyo['nickname']); ?>"
                                       class="form_input"/>
                            </td>
                            </tr>
                            <?php 
                            $check_alias = gdrcd_query("SELECT * FROM tokyobook WHERE personaggio = '".$_SESSION['login']."'", 'result');
                            if (gdrcd_query($check_alias, 'num_rows') == 0) {
                            ?>
                            <tr>
                            <td>
                            <input type="hidden" name="op" value="insert_tokyobook"/>

                            
                                <input style="width: 110px;" type="submit" value="Modifica alias"
                                       class="form_submit"/>
                                <input type="hidden"
                                       value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                       name="pg"/>
                            </td>
                            </tr>
                             <?php } ?>
                            </table>
                            </form>
                            
                            
                            <!-- alias ruolo gilda -->
                            <?php
                            $check_alias_gilda = gdrcd_query("SELECT * FROM clgpersonaggioruolo WHERE personaggio = '" . gdrcd_filter('get',
                    $_REQUEST['pg']) . "'", 'result');
                            if (gdrcd_query($check_alias_gilda, 'num_rows') > 0) {
                            ?>
                             <form action="main.php?page=scheda_modifica" method="post">
                            <?php
                        /*Carico le informazioni del PG*/
                    $alias_gilda = gdrcd_query("SELECT * FROM clgpersonaggioruolo WHERE personaggio = '" . gdrcd_filter('get',
                    $_REQUEST['pg']) . "'");
                                ?>
                    <table class="customTable">
                            <tr class="second_header">
                            <td style="text-transform:uppercase; font-size: 15px;">
                            Alias con cui si è noti nella Via<br> (<i>Non sarà più possibile modificarlo</i>)
                            </td>
                            </tr>
                            <tr>
                            <td>
                                <?php if ($alias_gilda['nickname'] === NULL || $_SESSION['admin'] == 1) { ?>
                        <!-- Se il campo nickname è NULL, permetti la modifica -->
                        <input type="text" name="nickname_gilda"
                               value="<?php echo gdrcd_filter('out', $alias_gilda['nickname']); ?>"
                               class="form_input"/>
                    <?php } else { ?>
                        <!-- Altrimenti, mostra semplicemente il valore attuale senza possibilità di modifica -->
                        <input type="text" name="nickname_gilda"
                               value="<?php echo gdrcd_filter('out', $alias_gilda['nickname']); ?>"
                               class="form_input" readonly/>
                    <?php } ?>
                            </td>
                            </tr>
                           
                            <tr>
                            <td>
                            <input type="hidden" name="op" value="insert_alias_gilda"/>

                            
                                <input style="width: 110px;" type="submit" value="Modifica alias via"
                                       class="form_submit"/>
                                <input type="hidden"
                                       value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                       name="pg"/>
                            </td>
                            </tr>
                             
                            </table>
                            </form>
                            <?php } ?>
                    </div>

























                    <?php
                }//if
                if (($_SESSION['admin']==1 || $_SESSION['moderatore']==1))
                {
                    ?>

                    <div class='form_gioco' style="display:none;">

                        <!-- Form master status -->
                        <form action="main.php?page=scheda_modifica" method="post">

                            <input type="hidden" name="op" value="modify_status"/>

                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['status']); ?>
                            </div>
                            <div class='form_field'>
                                <textarea type="textbox" name="modifica_status"
                                          class="form_textarea"><?php echo gdrcd_filter('out',
                                        $record['stato']); ?></textarea>
                            </div>

                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['healt']); ?>
                            </div>
                            <div class='form_field'>
                                <input class="healt_input" name="modifica_salute"
                                       value="<?php echo $record['salute']; ?>"/>/100
                            </div>
							
							<div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['notorieta']); ?>
                            </div>
                            <div class='form_field'>
                                <input class="healt_input" name="modifica_notorieta"
                                       value="<?php echo $record['notorieta']; ?>"/>/6
                            </div>
                            
                            							<div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['note_fato']); ?>
                            </div>
                            <div class='form_field'>
                                <textarea type="textbox" name="modifica_note_fato"
                                          class="form_textarea"><?php echo gdrcd_filter('out',
                                        $record['note_fato']); ?></textarea>
                            </div>
                            
                            							<div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['particolari']); ?>
                            </div>
                            <div class='form_field'>
                                <textarea type="textbox" name="modifica_particolari"
                                          class="form_textarea"><?php echo gdrcd_filter('out',
                                        $record['particolari']); ?></textarea>
                            </div>

                            <div class='form_submit'>
                                <input type="submit" value="<?php echo $MESSAGE['interface']['forms']['submit']; ?>"/>
                                <input type="hidden"
                                       value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                       name="pg"/>
                            </div>

                        </form>
                        <!-- Form master esilio -->
                        <form action="main.php?page=scheda_modifica" method="post">


                            <input type="hidden" name="op" value="exile"/>

                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['exile']); ?>
                            </div>
                            <div class='form_field'>
                                <!-- Giorno -->
                                <select name="day" class="day">
                                    <?php for ($i = 1; $i <= 31; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>" <?php if (strftime('%d') == $i)
                                        {
                                            echo 'selected';
                                        } ?>><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Mese -->
                                <select name="month" class="month">
                                    <?php for ($i = 1; $i <= 12; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>" <?php if (strftime('%m') == $i)
                                        {
                                            echo 'selected';
                                        } ?>><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                                <!-- Anno -->
                                <select name="year" class="year">
                                    <?php for ($i = strftime('%Y'); $i <= strftime('%Y') + 20; $i++)
                                    { ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php }//for
                                    ?>
                                </select>
                            </div>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out',
                                    $MESSAGE['interface']['sheet']['modify_form']['why_exiled']); ?>
                            </div>
                            <div class='form_field'>
                                <input name="causale"/>
                            </div>

                            <div class='form_submit'>
                                <input type="submit" value="<?php echo $MESSAGE['interface']['forms']['submit']; ?>"/>
                                <input type="hidden"
                                       value="<?php echo gdrcd_filter('get', $_REQUEST['pg']); ?>"
                                       name="pg"/>
                            </div>

                        </form>


                    </div>
                    <?php
                }//if
                ?>
            </div>

            <?php
        }//else

        }//if?>


    </div>
    <!-- Link a piè di pagina -->
    <div class="link_back">
        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url',
            $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?></a>
    </div>

</div><!-- pagina -->