<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['plur']); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
        <?php /*Visualizzaione elenco gilde*/
        if(isset($_REQUEST['id_gilda']) === false) {
            $query = "SELECT gilda.nome, gilda.id_gilda, gilda.tipo, gilda.immagine, gilda.url_sito, codtipogilda.descrizione FROM gilda JOIN codtipogilda ON gilda.tipo = codtipogilda.cod_tipo WHERE gilda.visibile = 1 ORDER BY gilda.tipo, gilda.id_gilda";
            $result = gdrcd_query($query, 'result');

            $last_type = -1; ?>
            <div class="titolo">
            	<img src="/themes/crystal/imgs/famiglie/titolo_famiglie.png">
            </div>
                    <table class="customTable">
                        <?php
                        while($row = gdrcd_query($result, 'fetch')) {
                            /*Conteggio i membri di gilda*/
                            $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = ".$row['id_gilda']."");
                            
                            /*Stampo la riga dell'allineamento gilde*/
                            if($row['tipo'] != $last_type) { ?>
                                <tr>
                                    <td colspan="3">
                                        <div>
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
                                    <td>
                                        <div>
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['sing']); ?>
                                        </div>
                                    </td>
									<td>
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
                                <td>
                                    <div>
                                        <a href="main.php?page=servizi_gilde&id_gilda=<?php echo $row['id_gilda']; ?>">
                                            <?php echo gdrcd_filter('out', $row['nome']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php
                                        if ($row['id_gilda'] == 1) {
                                        $reliquia = 'Reidh';
                                        } else if ($row['id_gilda'] == 2) {
                                        $reliquia = 'Corona di Caos';
                                        } else if ($row['id_gilda'] == 3) {
                                        $reliquia = 'Silver Crystal';
                                        } else if ($row['id_gilda'] == 4) {
                                        $reliquia = 'Nihil';
                                        } else if ($row['id_gilda'] == 5) {
                                        $reliquia = 'Coppa Lunare';
                                        } else if ($row['id_gilda'] == 6) {
                                        $reliquia = 'Albero della Vita';
                                        } else if ($row['id_gilda'] == 7) {
                                        $reliquia = 'Cristallo Corvino';
                                        } else {
                                        $reliquia = '';
                                        }
                                        ?>
                                        <a href="main.php?page=elenco_reliquie&IDPng=<?php echo $row['id_gilda']; ?>">
                                            <?php echo gdrcd_filter('out', $reliquia); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php echo $numb['COUNT(*)'];; ?>
                                    </div>
                                </td>
                                
                            </tr>
                        <?php
                        /************************ CITTADINI POTENIATI ****************************
                        (Attenzione: sono anche nella sezione in cui si conteggiano i pg)*********/
                        if($row['tipo'] == 4) { ?>
                       <tr>
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
                                    <div>
                                        <?php /*Conteggio i membri DEI CITTADINI POTENZIATI*/
                            $numb_power = gdrcd_query("SELECT COUNT(*) FROM personaggio JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo WHERE ruolo.gilda = '-1'");
                            echo $numb_power['COUNT(*)'];; ?>
                                    </div>
                                </td>
                                
                            </tr>
                       <?php
                        }
                        }//while
                        gdrcd_query($result, 'free');
                        ?>
                    </table><br><br>
                    <!--tabella -->
            <?php 
             /*include('servizi_inclinazione.inc.php');
           Visualizzazione estesa gilda*/
        } else {
            /*elenco ruoli*/
            $query = "SELECT * FROM ruolo WHERE gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY livello DESC, nome_ruolo DESC";
            $result = gdrcd_query($query, 'result'); ?>


                    <table class="customTable">
                        <tr>
                            <td colspan="4">
                                <!-- Titoletto -->
                                <div><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['roles_title']['plur']); ?></div>
                            </td>
                        </tr>
                        <tr class="second_header">
                           
                            <td>
                                <div>
                                    &nbsp;
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['roles_title']['sing']); ?>
                                </div>
                            </td>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>
                            
                            <td>
                                <div>
                                    <img src="imgs/guilds/<?php echo gdrcd_filter('out',
                                                                                                                                                                    $row['immagine']
                                    ); ?>" />
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                    <?php /*Elenco affiliati*/
                    if ($_REQUEST['id_gilda'] == '-1') {
                    $query = "SELECT personaggio.*, ruolo.immagine, ruolo.capo, ruolo.livello,  ruolo.nome_ruolo FROM ruolo JOIN personaggio ON personaggio.id_ruolo_gilda = ruolo.id_ruolo WHERE ruolo.gilda = -1 ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    } else {
                    $query = "SELECT clgpersonaggioruolo.personaggio, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo, ruolo.livello FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio WHERE ruolo.gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    }
                    $result = gdrcd_query($query, 'result'); ?>
                    <table class="customTable">
                        <tr>
                            <td colspan="4">
                                <!-- Titoletto -->
                                <div><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['members']); ?></div>
                            </td>
                        <tr>
                        <tr class="second_header">
    
                            <td>
                                <div>
                                    &nbsp;
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['member']); ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['roles_title']['sing']); ?>
                                </div>
                            </td>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>

                            <td>
                                <div>
                                    <img src="imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>" />
                                </div>
                            </td>
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
                                        echo gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']); 
                                        ?>
                                    </a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
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
                    <tr class="second_header">
                        <td colspan="4">
                            <!-- Titoletto -->
                            <div>Statuto</div>
                        </td>
                    <tr>
                    <tr>
                        <td>
                            <div style="text-align: justify;">
                                <?php echo gdrcd_bbcoder(gdrcd_filter('out', $statuto['statuto'])); ?>
                            </div>
                        </td>
                    </tr>
                </table>
            <?php } ?>
            <div class="link_back">
                <a href="main.php?page=servizi_gilde"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
            </div>
        <?php } ?>
    </div>
    <!-- Box principale -->
</div>