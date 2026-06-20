
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['user']['races']['page_name'].' '.strtolower($PARAMETERS['names']['race']['plur'])); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
        <?php /*Visualizzaione elenco razze*/
        if(isset($_REQUEST['id_razza']) === false) {
            $query = "SELECT * FROM razza WHERE visibile = 1 ORDER BY id_razza";
            $result = gdrcd_query($query, 'result');

            $last_type = -1; ?>
            <div class="titolo">
            	<img src="/themes/crystal/imgs/razze/titolo_razze.png" />
            </div>
                    <table class="customTable">
                        <?php
                        while($row = gdrcd_query($result, 'fetch')) {
                            /*Conteggio i membri di razza*/
                            $tot_razza = $row['id_razza'];
                            $numb = gdrcd_query("SELECT COUNT(*) FROM personaggio WHERE id_razza >= ".$tot_razza." AND id_razza < ".($tot_razza + 99)." ");
                            /*Stampo la riga dell'allineamento gilde*/
                            if($row['tipo'] != $last_type) { ?>
                                
                                <tr class="second_header">
                                    <td>
                                        
                                        &nbsp;
                                        
                                    </td>
                                    <td>
                                        
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['race']['sing']); ?>
                                        
                                    </td>
                                    <td>
                                        
                                            <?php echo gdrcd_filter('out', $PARAMETERS['names']['job_name']['members']); ?>
                                        
                                    </td>
                                    
                                </tr>
                                <?php $last_type = $row['tipo'];
                            } ?>
                            <!--Elenco razze-->
                            <tr>
                                <td>
                                    <div>
                                        <img src="/imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>" />
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <a href="main.php?page=user_razze&id_razza=<?php echo $row['id_razza']; ?>">
                                            <?php echo gdrcd_filter('out', $row['nome_razza']); ?>
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
                
            <!--tabella-->
            <?php /*Visualizzazione estesa razza*/
        } else {
            /*elenco ruoli*/
            $tot_razza = $_REQUEST['id_razza'];
            $query = "SELECT * FROM razza WHERE id_razza >= ".$tot_razza." AND id_razza < ".($tot_razza + 99)." ORDER BY id_razza ASC";            $result = gdrcd_query($query, 'result'); ?>

                    <table class="customTable">
                        
                        <tr class="second_header">
                            
                            <td>
                                <div>
                                   &nbsp;
                                </div>
                            </td>
                            <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php echo 'Lista'; ?>
                                </div>
                            </td>
                        </tr>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>
                            
                            <td>
                                <div>
                                    <img src="/imgs/guilds/<?php echo gdrcd_filter('out',
                                                                                                                                                                    $row['immagine']
                                    ); ?>" />
                                </div>
                            </td>
                            <td>
                                    <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <?php echo gdrcd_filter('out', $row['nome_razza']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                    <?php /*Elenco affiliati*/
                            $tot_razza = $_REQUEST['id_razza'];
                            $query = "SELECT * FROM personaggio WHERE id_razza >= ".$tot_razza." AND id_razza < ".($tot_razza + 99)." AND (esilio < NOW() || esilio IS NULL) ORDER BY id_razza DESC, nome ASC";
                            $result = gdrcd_query($query, 'result'); ?>
                   <br><br> <table class="customTable">
                        
                        <tr class="second_header">
                            
                            <td>
                                <div>
                                    &nbsp;
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?php echo 'Membri'; ?>
                                </div>
                            </td>
                           
                        </tr>
                            <!-- Elenco -->
                            <?php while($row = gdrcd_query($result, 'fetch'))
                            { ?>
                        <tr>
                            
                            <td>
                                <div>
                                <!-- razza -->

<?php $id_img_razza = $row['id_razza'];
      $mestiere = "SELECT * FROM razza WHERE id_razza = '$id_img_razza'";
      $row_razza = gdrcd_query($mestiere);
 ?>
 
                                    <img src="/imgs/guilds/<?php echo gdrcd_filter('out', $row_razza['immagine']); ?>" />
                                </div>
                            </td>
                            <td>
                                <div>
                                    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                                        <?php echo gdrcd_filter('out', $row['nome'].' '.$row['cognome']); ?>
                                    </a>
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
                    
                    
                </table>
            <?php } ?>
            <div class="link_back">
                <a href="main.php?page=user_razze"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
            </div>
        <?php } ?>
    
   
    <br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>
    </div>
    <!-- Box principale -->
</div>
