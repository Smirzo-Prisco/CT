<link rel="stylesheet" href="themes/crystal/mestieri.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['plur']); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
        <?php /*Visualizzaione elenco gilde*/
        if(isset($_REQUEST['id_mestiere']) === false) {
            $query = "SELECT mestiere.nome, mestiere.id_mestiere, mestiere.tipo, mestiere.immagine, mestiere.url_sito, codtipomestiere.descrizione FROM mestiere JOIN codtipomestiere ON mestiere.tipo = codtipomestiere.cod_tipo WHERE mestiere.visibile = 1 ORDER BY mestiere.tipo, mestiere.nome";
            $result = gdrcd_query($query, 'result');

            $last_type = -1; ?>
            <div class="titolo">
            	<img src="/themes/crystal/imgs/mestieri/titolo_mestieri.png" />
            </div>
                   <br> <table class="customTable">
                        <?php
                        while($row = gdrcd_query($result, 'fetch')) {
                            /*Conteggio i membri di gilda*/
                            $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = ".$row['id_mestiere']." && clgpersonaggiomestiere.conferma_mestiere = 1");
                            /*Stampo la riga dell'allineamento gilde*/
                            if($row['tipo'] != $last_type) { ?>
                                
                                <tr class="second_header">
                                    <td>
                                        
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['sing']); ?>
                                        
                                    </td>
                                    <td>
                                        
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['members']); ?>
                                        
                                    </td>
                                    
                                </tr>
                                <?php $last_type = $row['tipo'];
                            } ?>
                            <!--Elenco gilde-->
                            <tr>
                                <td width="80%">
                                    <div>
                                        <a href="main.php?page=servizi_mestieri&id_mestiere=<?php echo $row['id_mestiere']; ?>">
                                            <?php echo gdrcd_filter('out', $row['nome']); ?>
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
                        }//while
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                <br><br>
            <!--tabella-->
            <?php
            
                        $query_job = "
            SELECT * FROM ruolo_mestiere JOIN clgpersonaggiolavoro ON ruolo_mestiere.id_ruolo = clgpersonaggiolavoro.id_ruolo JOIN personaggio ON clgpersonaggiolavoro.personaggio = personaggio.nome WHERE mestiere = -1 && personaggio.esperienza > 9 GROUP BY nome_ruolo
            ";    
            $result_job = gdrcd_query($query_job, 'result');

            $last_type = -1; ?>
<table class="customTable">
 
                                 <tr class="second_header">
                                    <td>
                                        
                                            Lavoro Libero
                                        
                                    </td>
                                    <td>
                                        
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['members']); ?>
                                        
                                    </td>
                                    
                                </tr>
<?php
                        while($row_job = gdrcd_query($result_job, 'fetch')) {
                            /*Conteggio i membri di gilda*/
                            $numb_job = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiolavoro JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo JOIN personaggio ON clgpersonaggiolavoro.personaggio = personaggio.nome WHERE ruolo_mestiere.id_ruolo = ".$row_job['id_ruolo']." && personaggio.esperienza > 9");
                            /*Stampo la riga dell'allineamento gilde*/
                            ?>
                           <tr>
                                <td width="80%">
                                    <div>
                                        <a href="main.php?page=servizi_mestieri2&id_ruolo=<?php echo $row_job['id_ruolo']; ?>">
                                            <?php echo gdrcd_filter('out', $row_job['nome_ruolo']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                        <?php echo $numb_job['COUNT(*)'];; ?>
                                    </div>
                                </td>
                                
                            </tr>
                           
<?php }//while 
gdrcd_query($result_job, 'free');
                        ?>
                    </table>
                    
            <?php /*Visualizzazione estesa gilda*/
        } else {
            /*elenco ruoli*/
            $query = "SELECT * FROM ruolo_mestiere WHERE mestiere = ".gdrcd_filter('num', $_REQUEST['id_mestiere'])." ORDER BY stipendio DESC";
            $result = gdrcd_query($query, 'result'); ?>

                    <table class="customTable">
                        
                        <tr class="second_header">
                            
                            
                            <td>
                                <div>
                                    &nbsp;
                                </div>
                            </td>
                            <td>
                                <div>
GRADO                                </div>
                            </td>
                        </tr>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>
                           
                           
                            <td>
                                <div>
                                    <img src="imgs/mestieri/<?php echo gdrcd_filter('out',
                                                                                                                                                                    $row['immagine']
                                    ); ?>" />
                                </div>
                            </td>
                            <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                    <?php /*Elenco affiliati*/
                    $query = "SELECT clgpersonaggiomestiere.personaggio, personaggio.cognome, ruolo_mestiere.immagine, ruolo_mestiere.capo, ruolo_mestiere.nome_ruolo FROM ruolo_mestiere JOIN clgpersonaggiomestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggiomestiere.personaggio WHERE ruolo_mestiere.mestiere = ".gdrcd_filter('num', $_REQUEST['id_mestiere'])." && clgpersonaggiomestiere.conferma_mestiere = 1 ORDER BY ruolo_mestiere.capo DESC, ruolo_mestiere.stipendio DESC";
                    $result = gdrcd_query($query, 'result'); ?>
                    <br><br>
                    
                    <table class="customTable">

                        <tr class="second_header">
                            
                            
                            <td>
                                <div>
                                    &nbsp;
                                </div>
                            </td>
                            <td>
                                <div>
MEMBRI                                </div>
                            </td>
                            <td>
                                <div>
GRADO                                </div>
                            </td>
                        </tr>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>
                           
                           
                            <td>
                                <div>
                                    <img src="imgs/mestieri/<?php echo gdrcd_filter('out', $row['immagine']); ?>" />
                                </div>
                            </td>
                            <td>
                                <div>
                                    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['personaggio']); ?>">
                                        <?php echo gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
					<!--tabelle-->

            <?php /*statuto*/
            $statuto = gdrcd_query("SELECT statuto FROM mestiere WHERE id_mestiere = ".gdrcd_filter('num', $_REQUEST['id_mestiere'])."");

            if(empty($statuto['statuto']) === false) { ?>
                <table class="customTable">
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
                <a href="main.php?page=servizi_mestieri"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
            </div>
        <?php } ?>
    
   
    
    </div>
    <!-- Box principale -->
</div>
