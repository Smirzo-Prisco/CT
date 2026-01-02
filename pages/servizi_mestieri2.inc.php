<link rel="stylesheet" href="themes/crystal/mestieri.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['plur']); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
<!-- Lavori -->
 <?php /*Visualizzaione elenco gilde*/
        if(isset($_REQUEST['id_ruolo']) === false) {
            $query_job = "
            SELECT * FROM ruolo_mestiere JOIN clgpersonaggiolavoro ON ruolo_mestiere.id_ruolo = clgpersonaggiolavoro.id_ruolo JOIN personaggio ON clgpersonaggiolavoro.personaggio = personaggio.nome WHERE mestiere = -1 && personaggio.esperienza > 9 GROUP BY nome_ruolo
            ";    
            $result_job = gdrcd_query($query_job, 'result');

            $last_type = -1; ?>
<table class="customTable">
  <tr>
                                    <td colspan="3">
                                        
                                            Lavori
                                        
                                    </td>
                                </tr>
                                 <tr class="second_header">
                                    <td>
                                        
                                            Lavoro
                                        
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
                                <td>
                                    <div>
                                        <a href="main.php?page=servizi_mestieri2&id_ruolo=<?php echo $row_job['id_ruolo']; ?>">
                                            <?php echo gdrcd_filter('out', $row_job['nome_ruolo']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php echo $numb_job['COUNT(*)'];; ?>
                                    </div>
                                </td>
                                
                            </tr>
                           
<?php }//while 
gdrcd_query($result_job, 'free');
                        ?>
                    </table>
                
            <!--tabella-->
            <?php /*Visualizzazione estesa lavori*/
        } else {
         /*Elenco affiliati*/
                    $query_elenco = "SELECT clgpersonaggiolavoro.personaggio, personaggio.cognome, personaggio.esperienza, ruolo_mestiere.nome_ruolo FROM ruolo_mestiere JOIN clgpersonaggiolavoro ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggiolavoro.personaggio WHERE ruolo_mestiere.id_ruolo = ".gdrcd_filter('num', $_REQUEST['id_ruolo'])." && personaggio.esperienza > 9 ORDER BY personaggio.esperienza DESC, personaggio.nome ASC";
                    $result_elenco = gdrcd_query($query_elenco, 'result'); ?>
                    <table class="customTable">
                        
                        <tr class="second_header">
                            
                            
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
                            <?php while($row_elenco = gdrcd_query($result_elenco, 'fetch'))
                            { ?>
                        <tr>
                           
                           
                            
                            <td>
                                <div>
                                    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row_elenco['personaggio']); ?>">
                                        <?php echo gdrcd_filter('out', $row_elenco['personaggio'].' '.$row_elenco['cognome']); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php echo gdrcd_filter('out', $row_elenco['nome_ruolo']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php }
                        gdrcd_query($result_elenco, 'free');
                        ?>
                    </table>
					<!--tabelle-->

             <div class="link_back">
                <a href="main.php?page=servizi_mestieri"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
            </div>
    <?php }//isset ?>   
    </div>
    <!-- Box principale -->
</div>