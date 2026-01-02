<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title"><h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['plur']); ?></h2></div>
    <br><br>
    <!-- Box principale -->
    <div class="page_body">
    <?php /* Visualizzaione elenco Correnti */
        if(isset($_REQUEST['id_gilda']) === false) {
            include('servizi_inclinazione.inc.php');

            $query = "SELECT gilda.nome, gilda.id_gilda, gilda.tipo, gilda.immagine, gilda.url_sito, codtipogilda.descrizione FROM gilda JOIN codtipogilda ON gilda.tipo = codtipogilda.cod_tipo WHERE gilda.visibile = 1 and gilda.tipo != 4 ORDER BY gilda.tipo, gilda.id_gilda";
            $result = gdrcd_query($query, 'result');

            $last_type = -1;
            
            while($row = gdrcd_query($result, 'fetch')) {
                /* Conteggio i membri di gilda */
                $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = ".$row['id_gilda']."");
                
                /* Stampo la riga dell'allineamento gilde */
                if($row['tipo'] != $last_type) { ?>
                    <br>
                    <table class="customTable">
                        <tr>
                            <td colspan="3">
                            <div style="font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php
                                    /** * Ometto la dicitura "Allineamento:" cos� che il campo consenta pi� libert� di modifica.
                                     * @author Blancks
                                     */
                                    #echo gdrcd_filter('out',$PARAMETERS['names']['guild_name']['type']).": ";
                                    echo gdrcd_filter('out', $row['descrizione']); ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="second_header">
                            <td width="50%"><div><?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['sing']); ?></div></td>
                            <td width="40%"><div>Reliquia</div></td>
                            <td><div><?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['members']); ?></div></td>
                        </tr>
                        <?php $last_type = $row['tipo'];
                } ?>
                <!--Elenco gilde-->
                
                <tr>
                    <td width="50%">
                        <div>
                            <a href="main.php?page=servizi_gilde&id_gilda=<?php echo $row['id_gilda']; ?>">
                                <?php echo gdrcd_filter('out', $row['nome']); ?>
                            </a>
                        </div>
                    </td>
                    <td width="40%">
                        <div>
                            <?php
                                switch ($row['id_gilda']) {
                                    case 1: $reliquia = 'Reidh'; break;
                                    case 2: $reliquia = 'Corona di Caos'; break;
                                    case 3: $reliquia = 'Silver Crystal'; break;
                                    case 4: $reliquia = 'Nihil'; break;
                                    case 5: $reliquia = 'Coppa Lunare'; break;
                                    case 6: $reliquia = 'Albero della Vita'; break;
                                    case 7: $reliquia = 'Cristallo Corvino'; break;
                                    default: '';
                                }
                            ?>
                            <a href="main.php?page=elenco_reliquie&IDPng=<?php echo $row['id_gilda']; ?>">
                                <?php echo gdrcd_filter('out', $reliquia); ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                            <?php echo $numb['COUNT(*)'];; ?>
                        </div>
                    </td>
                    
                </tr>
            <?php
            /************************ CITTADINI POTENZIATI ****************************
            (Attenzione: sono anche nella sezione in cui si conteggiano i pg)*********/
            if($row['tipo'] == 4) { ?>
            
                </table><br> 
            <?php
            }
        }//while
        gdrcd_query($result, 'free');
    ?>
                    <br>
                    
            <!--INDIPENDENTI -->
            <?php /*Visualizzaione elenco gilde*/
            $query = "SELECT gilda.nome, gilda.id_gilda, gilda.tipo, gilda.immagine, gilda.url_sito, codtipogilda.descrizione FROM gilda JOIN codtipogilda ON gilda.tipo = codtipogilda.cod_tipo WHERE gilda.visibile = 1 and gilda.tipo = 4 ORDER BY gilda.tipo, gilda.id_gilda";
            $result = gdrcd_query($query, 'result');

            $last_type = -1;
                        
            while($row = gdrcd_query($result, 'fetch')) {
                /*Conteggio i membri di gilda*/
                $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = ".$row['id_gilda']."");
                
                /*Stampo la riga dell'allineamento gilde*/
                if($row['tipo'] != $last_type) { ?>
                    <table class="customTable">
                        <tr>
                        <td colspan="3">
                        <div style="font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                <?php
                                /** * Ometto la dicitura "Allineamento:" cos� che il campo consenta pi� libert� di modifica.
                                 * @author Blancks
                                 */
                                #echo gdrcd_filter('out',$PARAMETERS['names']['guild_name']['type']).": ";
                                echo gdrcd_filter('out', $row['descrizione']); ?>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="second_header">
                        <td width="50%">
                            <div>
                                <?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['sing']); ?>
                            </div>
                        </td>
                        <td width="40%">
                            <div>
                            Reliquia
                            </div>
                        </td>
                        <td>
                            <div>
                                <?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['members']); ?>
                            </div>
                        </td>
                            
                    </tr>
                    <?php $last_type = $row['tipo'];
                } ?>

                <!--Elenco gilde-->
                <tr>
                    <td width="50%">
                        <div>
                            <a href="main.php?page=servizi_gilde&id_gilda=<?php echo $row['id_gilda']; ?>">
                                <?php echo gdrcd_filter('out', $row['nome']); ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?php
                                switch ($row['id_gilda']) {
                                    case 1: $reliquia = 'Reidh'; break;
                                    case 2: $reliquia = 'Corona di Caos'; break;
                                    case 3: $reliquia = 'Silver Crystal'; break;
                                    case 4: $reliquia = 'Nihil'; break;
                                    case 5: $reliquia = 'Coppa Lunare'; break;
                                    case 6: $reliquia = 'Albero della Vita'; break;
                                    case 7: $reliquia = 'Cristallo Corvino'; break;
                                    default: '';
                                }
                            ?>
                            <a href="main.php?page=elenco_reliquie&IDPng=<?php echo $row['id_gilda']; ?>">
                                <?php echo gdrcd_filter('out', $reliquia); ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                            <?php echo $numb['COUNT(*)'];; ?>
                        </div>
                    </td>
                    
                </tr>
            <?php
            /************************ CITTADINI POTENIATI ****************************
            (Attenzione: sono anche nella sezione in cui si conteggiano i pg)*********/
            if($row['tipo'] == 4) { ?>
            <br><br><tr>
                    <td>
                        <div>
                            <a href="main.php?page=servizi_gilde&id_gilda=-1">
                                Cittadini potenziati
                            </a>
                        </div>
                    </td>
                    <td>
                        <div>
                            
                            <a href="main.php?page=scegli_umano">
                                <?php echo "Scegli la fazione"; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                            <?php /*Conteggio i membri DEI CITTADINI POTENZIATI*/
                $numb_power = gdrcd_query("SELECT COUNT(*) FROM personaggio JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo WHERE ruolo.gilda = '-1'");
                echo $numb_power['COUNT(*)'];; ?>
                        </div>
                    </td>
                    
                </tr>
                </table>
            <?php
            }
            }//while
            gdrcd_query($result, 'free');
            ?>
        <br><br>
            <?php 
           /*Visualizzazione estesa gilda*/
        } else {
            /*elenco ruoli*/
            $query = "SELECT * FROM ruolo WHERE gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY livello DESC, nome_ruolo DESC";
            $result = gdrcd_query($query, 'result'); ?>

            <table class="customTable">
                <tr class="second_header">
                    <td><div>&nbsp;</div></td>
                    <td><div>GRADO</div></td>
                    <!-- Elenco -->
<?php       while($row = gdrcd_query($result, 'fetch')) { ?>
                <tr>
                    <td><div><img src="imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>"></div></td>
                    <td>
                        <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                            <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                        </div>
                    </td>
                </tr>
<?php       }
            gdrcd_query($result, 'free');
?>
            </table>
                    <?php /*Elenco affiliati*/
                    if ($_REQUEST['id_gilda'] == '-1') {
                    $query = "SELECT personaggio.*, ruolo.immagine, ruolo.capo, ruolo.livello,  ruolo.nome_ruolo FROM ruolo JOIN personaggio ON personaggio.id_ruolo_gilda = ruolo.id_ruolo WHERE ruolo.gilda = -1 ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    } else {
                    //$query = "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.nickname, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo, ruolo.livello FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio WHERE ruolo.gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    $query = "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.nickname, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo, ruolo.livello,
                              CASE
                                  WHEN personaggio.nome IN ('Acamar', 'Kirari', 'Evan') THEN 1
                                  ELSE 0
                              END AS special_character
                              FROM ruolo 
                              JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo 
                              JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio 
                              WHERE ruolo.gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." 
                              ORDER BY special_character DESC, ruolo.livello DESC, ruolo.nome_ruolo DESC";
                              }
                    $result = gdrcd_query($query, 'result'); ?>
                    <br><br>
                    <table class="customTable">
                        <tr class="second_header">
                            <td><div>&nbsp;</div></td>
                            <td><div>MEMBRI</div></td>
                            <td><div>GRADO</div></td>
                        </tr>
                        <!-- Elenco -->
                <?php   while($row = gdrcd_query($result, 'fetch')) { ?>
                        <tr>
                            <td><div><img src="imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>" /></div></td>
                            <td>
                                <div>
                                    <?php if ($_REQUEST['id_gilda'] == '-1') { ?>
                                    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                                        <?php                                        
                                        echo gdrcd_filter('out', $row['nome'].' '.$row['cognome']); 
                                        ?>
                                        </a>
                                        <?php } else {
                                        ?>                                    
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['personaggio']); ?>">
                                        <?php 
                                        if ($row['special_character'] == 1) {
                                        echo '<span style="color: #9a6353;">'.gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']).'</span>'; 
                                        } else {
                                        echo gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']); 
                                        }
                                        ?>
                                    </a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                <?php 
                                if ($row['nickname'] === NULL) {
                                    echo gdrcd_filter('out', $row['nome_ruolo']);
                                } else {
                                    echo gdrcd_filter('out', $row['nickname']);
                                }
                                ?>
                                </div>
                            </td>
                        </tr>
                    <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                    <!--elenco_breve-->

                <?php /*statuto*/
                $statuto = gdrcd_query("SELECT statuto FROM gilda WHERE id_gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])."");

                if(empty($statuto['statuto']) === false) { ?>
                    <table>
                        <tr class="second_header"><td colspan="4"><div>Statuto</div></td><tr>
                        <tr><td><div style="text-align: justify;"><?php echo gdrcd_bbcoder(gdrcd_filter('out', $statuto['statuto'])); ?></div></td></tr>
                    </table>
                <?php } ?>
                <div class="link_back">
                    <a href="main.php?page=servizi_gilde"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
                </div>
            <?php } ?>
        </div>
        <!-- Box principale -->
    </div>