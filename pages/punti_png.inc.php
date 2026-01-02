    <?php /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1 && $_SESSION['master']!= 1 && $_SESSION['capogilda']!= 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { ?>
<div class="pagina_gestione_mercato">

<?php
/* richiamo tutto */
 $pg = $_SESSION['login'];
 $tutti_png = gdrcd_query("SELECT * FROM PNG ORDER BY IDPng", 'result');
 
  if(isset($_POST['op']) === true) {
  
      /*Aggiungiamo i px*/
        if(gdrcd_filter('get', $_POST['op']) == 'insert') {
        
        gdrcd_query("INSERT INTO PuntiPNG (IDPng, Nome, Esperienza2, DataEvento, Master, Commento)
        VALUES (".gdrcd_filter('in', $_POST['png']).", '".gdrcd_filter('in', $_POST['Nome'])."', '".gdrcd_filter('in', $_POST['exp'])."', NOW(), '$pg', '".gdrcd_filter('in', $_POST['reason'])."'
        )");
        
        $png_exp = gdrcd_query("UPDATE PNG SET Esperienza = Esperienza + '".gdrcd_filter('in', $_POST['exp'])."' WHERE IDPng = '".gdrcd_filter('in', $_POST['png'])."'");
        
        echo 'PNG Potenziato';
        } 
        
           }
           
  
  ?>
  
  <!-- Iniziamo impaginazione -->
  
  <center>
  <font size=4>Potenzia PNG della Famiglia</font><br/><br/>
  
  <br><table width="768" border="0" align=center>
  <tr><td>
  <!-- Form di impostazione dei campi -->
  <form class="form_gestione" action="main.php?page=punti_png" method="post" enctype="multipart/form-data">
  <!-- Elenco PNG -->
  <div class='form_label'>
PNG
</div><br>
<div class='form_field'>
                    <?php if(gdrcd_query($tutti_png, 'num_rows') > 0) { ?>
                        <select name="png">
                            <?php while($option = gdrcd_query($tutti_png, 'fetch')) { ?>
                                <option value="<?php echo $option['IDPng']; ?>">
                                    <?php echo gdrcd_filter('out', $option['Nome']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($tutti_png, 'free');
                            ?>
                        </select>
                        <?php } ?>
                        
                </div>
                
                  <div class='form_label'>
                  Esperienza
                 </div><br>
                 <div class='form_field'>
                 <input type="text" name="exp" value="0" />
                 </div>
                 
                 <div class='form_label'>
                  Motivo
                 </div><br>
                 <div class='form_field'>
                 <input type="text" name="reason" value="(Data) Motivo" />
                 </div>
                 <input type="hidden" name="op" value="insert" />
                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />

                 </form>
                 
                 <?php
                 
                 }
                 ?>