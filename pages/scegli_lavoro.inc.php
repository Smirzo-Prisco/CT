<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<?php
     
     /* iniziamo */
?>

    <div class="pagina_servizi_lavoro"> 


    
    <?php /*Elenco inclinazioni*/
        if(isset($_POST['op']) === false) {
            $query = "SELECT * FROM ruolo_mestiere WHERE mestiere = '-1' ORDER BY nome_ruolo";
            $result = gdrcd_query($query, 'result'); ?>
           <table class="customTable">
  <thead>
    <tr>
      <th colspan="8" style="background-color: #181c31; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?php echo 'ELENCO LAVORI' ?></th>
    </tr>
  </thead>
  <tbody>
    <tr class="second_header">
      <td>
      IMPIEGO		
		</td>	
        <td>
			
			PAGA
		
		</td>
        <td>
			
			
		
		</td>
	</tr>
                    
                    <?php
                    while($row = gdrcd_query($result, 'fetch')) { ?>
                    
                    <!--table class="lavori_box" border="1"-->
                        <tr>
                            
                            <td style="color: #8f8f8f;">
                                <?php echo $row['nome_ruolo']; ?>
                            </td>
                            <td style="color: #8f8f8f;">
                                <?php echo $row['stipendio']; ?>
                            </td>
                            <!-- Submit di scelta -->
                            <td>
                                
                                 <form method="post" action="main.php?page=scegli_lavoro">
                                
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['job']['submit']['pick']); ?>" />
                                <input type="hidden" name="op" value="pick" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo $row['id_ruolo']; ?>" />
                                
                                </form>
                                
                                </td>
                        </tr>
                     <?php }//while
                    gdrcd_query($result, 'free');
                     
                    ?>
                    
                </table>
            </div>
            <!--elenco_record_gioco-->
        <?php
        }//if-op
        
        /* scelta inclinazione */
        
        if($_POST['op'] == 'pick') {
         
         /* controllamo che non abbia già lavoro */

     $check_job = gdrcd_query("SELECT * FROM clgpersonaggiolavoro WHERE personaggio ='".$_SESSION['login']."'");
     if ($check_job['id_ruolo'] == 0) {
     $insert = gdrcd_query("INSERT INTO clgpersonaggiolavoro (id_ruolo, personaggio) VALUES (".gdrcd_filter('num', $_POST['id_record']).", '".$_SESSION['login']."')");
         
      echo '<div class="warning">Lavoro scelto</div>';
      
           // $prova = $_POST['nome_lavoro'];
      //echo "<script type='text/javascript'>alert('$prova');</script>";
              if($_POST['nome_lavoro'] == "Influencer"){
              
      	echo "<script>

           var audio = new Audio('../plugins/babykchiara.mp3');
           audio.play();

		</script>";
        
      } 
      } else {
      
      /* Altrimenti modifico quello vecchio */
      
      $edit = gdrcd_query("UPDATE clgpersonaggiolavoro SET id_ruolo = '".gdrcd_filter('num', $_POST['id_record'])."' WHERE personaggio = '".$_SESSION['login']."'");
         
      echo '<div class="warning">Lavoro modificato</div>';
      
      if($_POST['nome_lavoro'] == "Influencer"){
              
      	echo "<script>

           var audio = new Audio('../plugins/babykchiara.mp3');
           audio.play();

		</script>";
        
      } 
         
      }
      
      ?>
            <div class="link_back">
                <a href="main.php?page=scegli_lavoro"><?php echo gdrcd_filter('out', $MESSAGE['interface']['job']['back']); ?></a>
            </div>
            
            <?php
         
        }
                     
                     

?>

<br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>