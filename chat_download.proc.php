<?php
#################################################################################
#                                                                               ##
#                Save Chat HTML 1.3 - Author eLDiabolo                          ##
#                                                                               ##
#      e-mail: http://www.gdr-online.com/email.asp?email=eldiabolo              ##
#                                                                               ##
##################################################################################
#################################################################################

session_start();

/* Includo i file necessari */
include('includes/constant_values.inc.php');
include('config.inc.php');
include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('includes/functions.inc.php');
require ('header.inc.php'); /*Header comune*/

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

$typeOrder = ($PARAMETERS['mode']['chat_from_bottom'] == 'ON') ? 'DESC' : 'ASC';

/*Query per caricamento dati dalla chat corrente, carica le azioni degli ultimi 240 min - 4 ore !! NON SALVA LE CHAT PRIVATE !!*/



        /*Inizio a preparare il testo da inserire poi nel file da salvare.*/
        ?>
        
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="it" lang="it">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

        <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/presenti.css" TYPE="text/css">
        <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/main.css" TYPE="text/css">
        <link rel="stylesheet" href="../themes/<?php echo $PARAMETERS['themes']['current_theme'];?>/chat.css" TYPE="text/css">
        
        <script language="Javascript">
function SaveToDisk(fileURL, fileName) {
    // for non-IE
    if (!window.ActiveXObject) {
        var save = document.createElement('a');
        save.href = 'chat_download.proc.php';
        save.target = '_blank';
        save.download = fileName || 'Role del <? echo date('d-m-Y')?>.html';

        var event = new MouseEvent('click');
        // event.initEvent('click', true, true);
        save.dispatchEvent(event);
        (window.URL || window.webkitURL).revokeObjectURL(save.href);
    }

    // for IE
    else if ( !! window.ActiveXObject && document.execCommand)     {
        var _window = window.open(fileURL, '_blank');
        _window.document.close();
        _window.document.execCommand('SaveAs', true, fileName || fileURL);
        _window.close();
    }
}
</script>

<style type="text/css">
body {
    padding: 0;
    margin: 0;
    color: #b4b6bf;
    font-family: Verdana, Sans;
    font-size: 11px;
    background-color: #111423;
}
iframe.iframe_chat {
    border: none;
    width: 1px;
    height: 1px;
    margin: 0px;
    }

div.chat_window {
    text-align: center;
}

/**	* Fix per layout elastico
	* @author Blancks
*/
div.chat_box {
    position: absolute;
    top: 50px;
    bottom: 120px;
    left: 10px;
    right: 10px;

    overflow: auto;
    font-size: 16px;
    text-align: left;
}

div.pagina_frame_chat div.page_body div.panels_box {
    position: absolute;
    bottom: 9px;
    left: 5px;
    right: 5px;
}

/**	* Fine fix */

/** * Avatar di chat
	* @author Blancks
*/
img.chat_avatar {
    margin: 8px;
    display: block;
    float: left;
    position: relative;
}

/***** testo della chat *****/

/* Tipi messaggio: (A azione, P parlato, N PNG, M Master, X Moderazione, I Immagine, S sussurro, D dado, C skill check, O uso oggetto) */

div.chat_row_A {
    margin-top: 3px;
    text-align: justify;
}

div.chat_row_A span.color2 {
    margin-top: 3px;
    color: #000;
    font-weight: bolder;
}

div.chat_row_P {
    margin-top: 3px;
}

div.chat_row_P span.color2 {
    margin-top: 3px;
    color: #000;
    font-weight: bolder;
    
}
div.chat_row_Z {
    margin-top: 3px;
}
div.chat_row_Z span.color2 {
    margin-top: 3px;
    color: blue;
    font-weight: bold;
}
/*  div.chat_row_M {
    margin-top: 3px;
    border-width: 2px;
    border-color: green;
    border-style: solid;
    text-align: justify;
    color: green;
}
*/

div.chat_row_M {
    FONT-WEIGHT: bold;
    FONT-SIZE: 11px;
    COLOR: #b4b6bf;
    TEXT-ALIGN: justify;
}
/* div.chat_row_X {
    margin-top: 3px;

    border-width: 2px;
    border-color: red;
    border-style: solid;
    text-align: center;
    color: red;
}
*/
div.chat_row_X {
    FONT-WEIGHT: bold;
    FONT-SIZE: 11px;
    COLOR: #b4b6bf;
    TEXT-ALIGN: justify;
}
/*div.chat_row_G {
    margin-top: 3px;

    border-width: 2px;
    border-color: green;
    border-style: solid;
    text-align: justify;
    color: green;
}
*/
div.chat_row_G {
    FONT-WEIGHT: bold;
    FONT-SIZE: 11px;
    COLOR: #b4b6bf;
    TEXT-ALIGN: justify;
}
div.chat_row_N {
    margin-top: 3px;
}

div.chat_row_N span.color2 {
    color: #000;
    font-weight: bolder;
}

div.chat_row_I {
    margin-top: 3px;
    text-align: center;
}

div.chat_row_S {
    margin-top: 3px;
    FONT-SIZE: x-small;
    COLOR: #000000;
    BACKGROUND-COLOR: #FFFFFF
}

/*div.chat_row_D {
    margin-top: 3px;
    border-width: 2px;
    border-color: blue;
    border-style: solid;
    color: cyan;
}

div.chat_row_C {
    margin-top: 3px;
    border-width: 2px;
    border-color: blue;
    border-style: solid;
    color: cyan;
}*/

div.chat_row_D {
    margin-top: 3px;
    FONT-WEIGHT: bold;
    FONT-SIZE: x-small;
    COLOR: #3267a5;
}

div.chat_row_C {
    margin-top: 3px;
    FONT-WEIGHT: bold;
    FONT-SIZE: x-small;
}

span.chat_time {
    font-size: 10px;
    font-weight: bolder;
    margin-right: 3px;
}

span.chat_name {
    font-weight: bolder;
    font-size: 12px;
    color: #000;
}

span.chat_msg {
    color: #b4b6bf;
    font-size: 11px;     
}

span.chat_msg_off {
    color: #a1c3c8;
    font-size: 10px;
     
}
span.chat_skill {
    color: #b4b6bf;
    font-size: 11px; 
    color: #3267a5;
}
span.chat_master {
}

span.chat_me, span.chat_me_master {
    text-decoration: underline;
}
</style>
</head>

        <body class="main_body" style="overflow:auto; text-align:justify;">
        
        <?php
        
        //controllo se c'è almeno un'azione di login (azione o master screen)
        
        $check_action = gdrcd_query("SELECT * FROM chat WHERE stanza = ".$_SESSION['luogo']." AND mittente = '".$_SESSION['login']."' AND DATE_ADD(ora, INTERVAL 7 HOUR)  >= NOW() AND (tipo = 'P' || tipo = 'M')", 'result');
        if(($_SESSION['admin'] == 1) || (gdrcd_query($check_action, 'num_rows') > 0)) {
        //se c'è 1, stampo tutto
        $query = gdrcd_query("SELECT * FROM chat WHERE stanza = '" . $_SESSION['luogo'] . "' AND DATE_ADD(ora, INTERVAL 7 HOUR)  >= NOW() ORDER BY ora ASC", 'result');
         

        //*Stampo nome luogo e link download//
            $id_luogo = $_SESSION['luogo'];
            $luogo = "SELECT * FROM mappa WHERE id = '$id_luogo'";
            $row_luogo = gdrcd_query($luogo);
        	echo "<center><h3><font color='white'><b>Log ". $row_luogo['nome'] ."</b></font></h3>";
    ?>
    <br>
    <?
    echo "</center><hr>";
    
    
        /* Eseguo la query e le formattazioni */

        while ($row = gdrcd_query($query, 'fetch'))
        {

            /** INIZIAMO A STAMPARE   
            */

         
         
         
         
         
         /** BEGIN "Icone di Chat by eLDiabolo"
            *
            * Modifica immagini di chat. Icone razza, genere e gilda.
            * Per farle apparire impostare i parametri relativi nel file config.inc.php
            * se impostato su On compaiono le icone di gilda, in automatico riempie gli spazi vuoti
            *    per chi non ha raggiunto il limite dei simboli possibili così da avere la chat più ordinata
            *
            * v 1.3
            * @author eLDiabolo
            */

            $add_icon = '';

            if ($PARAMETERS['mode']['chaticons'] == 'ON')
            {
                $add_icon .= '<span class="chat_icons">';

                $icone_chat = explode(";", gdrcd_filter('out', $row['imgs']));

                /*Aggiunta per rendere utilizzabile la chat anche in mancanza dell'installazione della patch Icone Chat
                * Save Chat HTML 1.3
                *@author eLDiabolo
                */
                if (isset($PARAMETERS['settings']['chat']['guilds']))
                {

                    if ($PARAMETERS['settings']['chat']['race'] == 'ON')
                    {
                        $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/themes/' . $PARAMETERS['themes']['current_theme'] . '/imgs/icons/races/' . $icone_chat[1] . '">';
                    }
                    if ($PARAMETERS['settings']['chat']['gender'] == 'ON')
                    {
                        $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/imgs/icons/testamini' . $icone_chat[0] . '.png">';
                    }
                    if ($PARAMETERS['settings']['chat']['guilds'] == 'ON')
                    {

                        $query_ruoli = "SELECT 	clgpersonaggioruolo.id_ruolo,	ruolo.nome_ruolo,	ruolo.immagine FROM clgpersonaggioruolo INNER JOIN ruolo ON ruolo.id_ruolo = clgpersonaggioruolo.id_ruolo WHERE clgpersonaggioruolo.personaggio='" . $row['mittente'] . "'";
                        $result_ruoli = gdrcd_query($query_ruoli, 'result');
                        $gilde = 0;

                        if (gdrcd_query($result_ruoli, 'num_rows') > 0)
                        {
                            while ($ruoli = gdrcd_query($result_ruoli, 'fetch'))
                            {
                                $gilde++;
                                $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/themes/' . $PARAMETERS['themes']['current_theme'] . '/imgs/guilds/' . $ruoli['immagine'] . '" alt="' . gdrcd_filter('out',
                                $record3['nome_ruolo']) . '" title="' . gdrcd_filter('out',
                                $ruoli['nome_ruolo']) . '" />';
                            }
                        }

                        for ($i = $PARAMETERS['settings']['guilds_limit']; $i > $gilde; $i--)
                        {
                            $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/imgs/icons/guilds/null.png" alt="" title="" />';
                        }
                    }
                } else
                {
                    /*Aggiunta per rendere utilizzabile la chat anche in mancanza dell'installazione della patch Icone Chat
                    * Save Chat HTML 1.3
                    *@author eLDiabolo
                    */
                    $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/themes/' . $PARAMETERS['themes']['current_theme'] . '/imgs/icons/races/' . $icone_chat[1] . '">';
                    $add_icon .= '<img class="presenti_ico" src="' . $PARAMETERS['info']['site_url'] . '/imgs/icons/testamini' . $icone_chat[0] . '.png">';
                }

                /*Corretta la svista riportata nel pacchetto "Icone Chat v 1.1"
                *@author eLDiabolo
                */
                $add_icon .= '</span>';

            }

            /** END "Icone di Chat by eLDiabolo"
            *
            * @author eLDiabolo
            */
            
            
            
            
            
            
            
            
            
            

            switch ($row['tipo'])
            {
                case 'P':
	 
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

						  
				$row['testo'] = $row['testo'];
                $row['testo'] = str_replace('[', '[<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace(']', '</font>]', $row['testo']);
                $row['testo'] = str_replace('«', '«<font color=#7f99bc>', $row['testo']);
                $row['testo'] = str_replace('»', '</font>»', $row['testo']);
				 
				/** * Avatar di chat
					*@author Blancks
				*/

																							 

														  
									   
			 
																																																																																																			 

														
				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
			 
																			 
																																				   

																			

						  

				$add_chat.= '<span class="chat_msg">'.$row['mittente'].'';

				if(empty ($row['destinatario']) === FALSE )
				{
					$add_chat.= '<span class="chat_tag"> ['.gdrcd_filter('out',$row['destinatario']).']</span>';
				}

				$add_chat.=': </span> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					if ($PARAMETERS['mode']['chat_avatar']=='ON')
						$add_chat .= '<br style="clear:both;" />';

					$add_chat.= '</div>';

			break;


			case 'A':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                
                /** colori dialogo **/
                
                $testo = $row['testo'];
                $testo = str_replace('[', '[<font color=#7f99bc>', $testo);
                $testo = str_replace(']', '</font>]', $testo);
                $testo = str_replace('«', '«<font color=#7f99bc>', $testo);
                $testo = str_replace('»', '</font>»', $testo);

				/** * Avatar di chat
					*@author Blancks
				*/



				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';



				$add_chat.= '<span class="chat_name">'.$row['mittente'].'';

				if(empty ($row['destinatario']) === FALSE )
				{
					$add_chat.= '<span class="chat_tag"> ['.gdrcd_filter('out',$row['destinatario']).']</span>';
				}
				$add_chat.='</span> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatme($_SESSION['login'], $testo).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					if ($PARAMETERS['mode']['chat_avatar']=='ON')
						$add_chat .= '<br style="clear:both;" />';

					$add_chat.= '</div>';

			break;


			case 'S':
				if ($_SESSION['login']==$row['destinatario'])
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                    $add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
					$add_chat.= '<span class="chat_name">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['by'].': </span> ';
					$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
					$add_chat.= '</div>';

				} else if ($_SESSION['login']==$row['mittente'])
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

					$add_chat.= '<span class="chat_msg">'.$MESSAGE['chat']['whisper']['to'].' '.gdrcd_filter('out',$row['destinatario']).': </span>';
					$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '</div>';

				} else if (($_SESSION['permessi']>=MODERATOR)&&($PARAMETERS['mode']['spyprivaterooms']=='ON'))
				{
					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

					$add_chat.= '<span class="chat_msg">'.$row['mittente'].' '.$MESSAGE['chat']['whisper']['from_to'].' '.gdrcd_filter('out',$row['destinatario']).' </span>';
					$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

					/**	* Fix problema visualizzazione spazi vuoti con i sussurri
						* @author eLDiabolo
					*/
					$add_chat.= '</div>';

				}
			break;


			case 'N':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_name">'.$row['destinatario'].'</span> ';
				$add_chat.= '<span class="chat_msg">'.gdrcd_chatcolor(gdrcd_filter('out',$row['testo'])).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'M':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
                
                $testo = $row['testo'];
                $testo = str_replace('[', '[<font color=#7f99bc>', $testo);
                $testo = str_replace(']', '</font>]', $testo);
                $testo = str_replace('«', '«<font color=#7f99bc>', $testo);
                $testo = str_replace('»', '</font>»', $testo);
                $testo = str_replace('{', '<font color=green>', $testo);
                $testo = str_replace('}', '</font>', $testo);
                $testo = str_replace('(br)', '<br>', $testo);
                $testo = str_replace('(center)', '<center>', $testo);
                $testo = str_replace('(/center)', '</center>', $testo);
                $testo = str_replace('(cor)', '<i>', $testo);
                $testo = str_replace('(/cor)', '</i>', $testo);
                $testo = str_replace('(link)', '<a href=', $testo);
                $testo = str_replace('(/link)', ' target="_blank"><b>Clikka qui</b></a>', $testo);
                
                
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
                
                $add_chat.= '<TABLE style="width:100%;"><TR><TD align=justify>
                <fieldset style="border:1px solid #888888; padding: 10px; text-align:justify;">';
				$add_chat.= '<legend style="border:1px solid purple; color:purple; font-size:11px; text-align: center;">'.gdrcd_format_time($row['ora']).' : Master Screen</legend>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], $testo, true).'</span>';
                $add_chat.= '</td></tr></table>';
                
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'I':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<img class="chat_img" src="'.gdrcd_filter('fullurl',$row['testo']).'" />';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'C':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'D':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;


			case 'O':
				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				$add_chat.= '<span class="chat_time">'.gdrcd_format_time($row['ora']).'</span>';
				$add_chat.= '<span class="chat_msg">'.gdrcd_filter('out',$row['testo']).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
			
			case 'X':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';

				#$add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
                
                $add_chat.= '<TABLE style="width:100%;"><TR><TD align=justify>
                <fieldset style="border:1px solid red; padding: 10px; text-align:justify;">';
				$add_chat.= '<legend style="border:1px solid red; color:red; font-size:11px; text-align: center;">'.gdrcd_format_time($row['ora']).' : Moderazione</legend>';
                $add_chat.= '<span class="chat_master">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';
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
            
            case 'Z':
							/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '<div class="chat_row_'.$row['tipo'].'">';
				$add_chat.= '<span class="chat_msg_off">'.$row['mittente'].' scrive in OFF: ';
				$add_chat.= '<span class="chat_msg_off">'.gdrcd_chatme($_SESSION['login'], gdrcd_filter('out', $row['testo']), true).'</span>';

				/**	* Fix problema visualizzazione spazi vuoti con i sussurri
					* @author eLDiabolo
				*/
				$add_chat.= '</div>';
			break;
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
             }//fine switch
          
          
        }//fine while
        echo $add_chat;
        } else {
        
        //non vedi un cazzus de niente, tiè!
        
echo '<TABLE width="60%" align="center" cellpadding="2" cellspacing="2" border=0 class="table2" background="img/barraluogo.png" align=center>';
echo '<tr class="table2" >';
echo '<td class="table2" width="100%" valign=top>';
echo '<TABLE width="100%"><TBODY><TR>';
echo '<td colspan=3 align=left class="Stile1"><center><font face=Georgia size=2>';
echo 'Non hai ancora giocato in questa location. Ti ricordiamo che la funzione "<b>Salva chat</b>" &egrave; disponibile solo <u>dopo</u> aver postato <u>minimo</u> 3 azioni. Clikka nuovamente qui una volta raggiunto il minimo.';
echo '</font></center></td></TR></TBODY></TABLE></td></tr></TABLE>';
}   //fine if



        $add_chat .= '
        </body>
        </html>
        ';
