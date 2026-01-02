<div class="pagina_gestione_mercato">

    <?php /*HELP: Pagina di gestione del mercato */

    
     
     if(isset($_POST['op']) === true) {
            /*Se e' stato richiesto di caricare un oggetto*/
            if($_POST['op'] == 'load') {
                $loaded_item = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['load_item'])."");
                
                $characters = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome", 'result');
            }//fine load
            
            /*Se e' stato richiesto di modificare un oggetto...*/
            if($_POST['op'] == 'update') {
            
            /*...modificando i campi*/
                if(isset($_POST['modifica']) === true) {
                
                /*MODIFICA IMMAGINE*/
                
                if($_FILES['img_oggetto']['name'] == '') {
                    /*Eseguo l'aggiornamento SENZA immagine*/
                    gdrcd_query("UPDATE oggetto SET tipo=".gdrcd_filter('in', $_POST['tipo_oggetto']).", nome='".gdrcd_filter('in', $_POST['nome_oggetto'])."', descrizione='".gdrcd_filter('in', $_POST['descrizione_oggetto'])."', ubicabile=".gdrcd_filter('num', $_POST['fit_in']).", costo=".gdrcd_filter('num', $_POST['costo']).", attacco=".gdrcd_filter('num', $_POST['attacco_oggetto']).", cariche=".gdrcd_filter('num', $_POST['cariche']).", heal=".gdrcd_filter('num', $_POST['heal']).", tipo_arma=".gdrcd_filter('num', $_POST['tipo_arma']).", bonus_car1_extra=".gdrcd_filter('num', $_POST['bonus_car1_extra']).", bonus_car2_extra=".gdrcd_filter('num', $_POST['bonus_car2_extra']).", bonus_car3_extra=".gdrcd_filter('num', $_POST['bonus_car3_extra']).", bonus_car4_extra=".gdrcd_filter('num', $_POST['bonus_car4_extra']).", temp_giorni=".gdrcd_filter('num', $_POST['temp_giorni']).", isTemp=".gdrcd_filter('num', $_POST['isTemp'])." WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto'])."");

                  } else {
                    $immagine_oggetto = $_FILES['img_oggetto']['name'];
                    /*Eseguo l'aggiornamento CON immagine*/
                    gdrcd_query("UPDATE oggetto SET tipo=".gdrcd_filter('in', $_POST['tipo_oggetto']).", nome='".gdrcd_filter('in', $_POST['nome_oggetto'])."', urlimg = '".gdrcd_filter('in', $immagine_oggetto)."', descrizione='".gdrcd_filter('in', $_POST['descrizione_oggetto'])."', ubicabile=".gdrcd_filter('num', $_POST['fit_in']).", costo=".gdrcd_filter('num', $_POST['costo']).", attacco=".gdrcd_filter('num', $_POST['attacco_oggetto']).", cariche=".gdrcd_filter('num', $_POST['cariche']).", heal=".gdrcd_filter('num', $_POST['heal']).", tipo_arma=".gdrcd_filter('num', $_POST['tipo_arma']).", bonus_car1_extra=".gdrcd_filter('num', $_POST['bonus_car1_extra']).", bonus_car2_extra=".gdrcd_filter('num', $_POST['bonus_car2_extra']).", bonus_car3_extra=".gdrcd_filter('num', $_POST['bonus_car3_extra']).", bonus_car4_extra=".gdrcd_filter('num', $_POST['bonus_car4_extra']).", temp_giorni=".gdrcd_filter('num', $_POST['temp_giorni']).", isTemp=".gdrcd_filter('num', $_POST['isTemp'])." WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto'])."");
                    upload_image();
                }
                
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';

                }/*...eliminandolo*/ else if(isset($_POST['elimina']) === true) {
                        /*Risarcisco gli eventuali possessori */
                        $rec = gdrcd_query("SELECT costo FROM oggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto'])." LIMIT 1");

                        $refound = gdrcd_query("SELECT nome FROM clgpersonaggiooggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto'])."", 'result');
              
                                      while($do_refound = gdrcd_query($refound, 'fetch')) {
                            gdrcd_query("UPDATE personaggio SET soldi = soldi + ".gdrcd_filter('num', $rec['costo'])." WHERE nome = '".gdrcd_filter_in($do_refound['nome'])."'");
                        }
                        gdrcd_query($refound, 'free');
                        
                        /*Elimino l'oggetto*/
                        gdrcd_query("DELETE FROM oggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto'])." LIMIT 1");

                        gdrcd_query("DELETE FROM clgpersonaggiooggetto WHERE id_oggetto=".gdrcd_filter('num', $_POST['id_oggetto']));

                        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['modified']).'</div>';
                        
            }//fine else elimina            
            
            }//fine update
            
            }//fine op

        $elenco_oggetti = gdrcd_query("SELECT id_oggetto, nome FROM oggetto ORDER BY nome", 'result');
        $tipi_oggetto = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
    
    ?>
            
       <!-- Iniziamo la costruzione della pagina -->
       <table width="232" border="1" align="center">
<tr>
    <td width="108"><center><b>Armi</b></center></td>
    <td width="108"><center><b>Bonus</b></center></td>
  </tr>
  <tr>
    <td width="108">Armi da lama</td>
    <td width="108">+ 1</td>
  </tr>
  <tr>
    <td>Armi da lancio</td>
    <td>+ 1</td>
  </tr>
  <tr>
    <td>Bastoni</td>
    <td>+ 1</td>
  </tr>
  <tr>
    <td>Fucili</td>
    <td>+ 2</td>
  </tr>
  <tr>
    <td>Pistole</td>
    <td>+ 3</td>
  </tr>
</table>
    
    <div class="panels_box">
            <!-- Elenco degli oggetti esistenti -->
            <div class="panels_box">
                <form class="form_gestione" action="main.php?page=oggetto_modifica_mad" method="post">
                    <div class='form_label'>
                        <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['load_item']); ?>
                    </div>
                    <div class='form_field'>
                        <?php if(gdrcd_query($elenco_oggetti, 'num_rows') > 0) { ?>
                            <select name="load_item">
                             <?php                            
                            
                                while($option = gdrcd_query($elenco_oggetti, 'fetch')) { ?>
                                    <option value="<?php echo $option['id_oggetto']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                    </option>
                                <?php }
                                gdrcd_query($elenco_oggetti, 'free');
                                ?>
                            </select>
                        <?php } ?>
                    </div>
                    <input type="hidden" name="op" value="load" />
                    <div class='form_submit'>
                        <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                    </div>
                </form>
            </div>
            
            <?php if ($_POST['load_item'] != "") { ?>
            
            
                      <form class="form_gestione" action="main.php?page=oggetto_modifica_mad" method="post" enctype="multipart/form-data">

            
            <!-- INIZIO CAMPI DI MODIFICA -->
            
            <div class='form_label'>
       <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_type']); ?>
       </div>
       
       <div class='form_field'>
                    <?php 
                        $tipi_oggetto = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
                        if(gdrcd_query($tipi_oggetto, 'num_rows') > 0) { ?>
                        <select name="tipo_oggetto">
                        <?php                            
                            while($option = gdrcd_query($tipi_oggetto, 'fetch')) {  ?>
                                <option value="<?php echo $option['cod_tipo']; ?>" <?php if($loaded_item['tipo'] == $option['cod_tipo']) {echo 'SELECTED';} ?>>
                                    <?php echo gdrcd_filter('out', $option['descrizione']); ?>
                                </option>
                            <?php
                            
                            }//fine if
                            gdrcd_query($tipi_oggetto, 'free');
                            
                            ?>
                        </select>
                    <?php } ?>
                </div>
                
                <!-- nome oggetto -->
                <div class='form_label'>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_name']); ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="nome_oggetto" value="<?php echo $loaded_item['nome']; ?>" />
                </div>
                
                <!-- descrizione -->
                <div class='form_label'>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_info']); ?>
                </div>
                <div class='form_field'>
                    <textarea type="textbox" name="descrizione_oggetto"><?php echo $loaded_item['descrizione']; ?></textarea>
                </div>
                
                <!-- immagine -->
                <div class='form_label'>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_image']); ?>
                </div>
                <div class='form_field'>
                    <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                </div>
                
                <!-- posizione -->
                <div class='form_label'>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_fit_in']); ?>
                </div>
                <div class='form_field'>
                    <select name="fit_in">
                        <option value="<?php echo INVENTARIO; ?>" <?php if($loaded_item['ubicabile'] == INVENTARIO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['inventory']); ?>
                        </option>
                        <option value="<?php echo ZAINO; ?>" <?php if($loaded_item['ubicabile'] == ZAINO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['bag']); ?>
                        </option>
                        <option value="<?php echo MANODX; ?>" <?php if($loaded_item['ubicabile'] == MANODX) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['hand_dx']); ?>
                        </option>
                        <option value="<?php echo MANOSX; ?>" <?php if($loaded_item['ubicabile'] == MANOSX) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['hand_sx']); ?>
                        </option>
                        <option value="<?php echo TORSO; ?>" <?php if($loaded_item['ubicabile'] == TORSO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['chest']); ?>
                        </option>
                        <option value="<?php echo GAMBE; ?>" <?php if($loaded_item['ubicabile'] == GAMBE) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['legs']); ?>
                        </option>
                        <option value="<?php echo PIEDI; ?>" <?php if($loaded_item['ubicabile'] == PIEDI) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['feet']); ?>
                        </option>
                        <option value="<?php echo TESTA; ?>" <?php if($loaded_item['ubicabile'] == TESTA) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['head']); ?>
                        </option>
                        <option value="<?php echo ANELLO; ?>" <?php if($loaded_item['ubicabile'] == ANELLO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['ring']); ?>
                        </option>
                        <option value="<?php echo COLLO; ?>" <?php if($loaded_item['ubicabile'] == COLLO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['neck']); ?>
                        </option>
                    </select>
                </div>
                
                 <!-- cariche -->
                <div class='form_label'>
                    <?php echo "Tipo utiizzo"; ?>
                </div>
                <div class='form_field'>
                <select name="cariche">
                <option value="-1" <?php if($loaded_item['cariche'] == -1) {echo 'selected';} ?>>Utilizzo illimitato</option>
                <option value="1" <?php if($loaded_item['cariche'] == 1) {echo 'selected';} ?>>Utilizzo limitato</option>
                </select>
                </div>
                
                <!-- tipo arma -->
                <div class='form_label'>
                    <?php echo "Tipo Arma"; ?>
                </div>
                <div class='form_field'>
                <select name="tipo_arma">
                <option value="0" <?php if($loaded_item['tipo_arma'] == 0) {echo 'selected';} ?>>---</option>
                <option value="1" <?php if($loaded_item['tipo_arma'] == 1) {echo 'selected';} ?>>Arma bianca</option>
                <option value="2" <?php if($loaded_item['tipo_arma'] == 2) {echo 'selected';} ?>>Arma da lancio</option>
                <option value="3" <?php if($loaded_item['tipo_arma'] == 3) {echo 'selected';} ?>>Arma da fuoco</option>
                </select>
                </div>
                
                <!-- bonus attacco -->
                
                <div class='form_label'>
                    <?php echo "Bonus Arma"; ?>
                </div>
                <div class='form_field'>
                <select name="attacco_oggetto">
                <?php

                                for ($j = 0; $j <= 3; $j++) {
                                echo '<option value=\''.($j).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j).'</option>';
                        }
?>
                    </select>
                    </div>
                    
                    <?php
                    if ($_SESSION['admin'] == 1) { ?>
                    <!-- salute -->
                    <div class='form_label'>
					<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['heal']); ?>                
                </div>
                <div class='form_field'>
                    <input type="text" name="heal" value="<?php echo (int) $loaded_item['heal']; ?>" />
                </div>
                   <?php }//fine if salute ?>
                  
                  <div class='form_label'>
					<?php echo "Prezzo (solo se da inserire nel mercato)"; ?>                
                </div>
                <div class='form_field'>
                    <input type="text" name="costo" value="<?php echo (int) $loaded_item['costo']; ?>" />
                </div>
                
                  <!-- OGGETTI POTENZIATI -->
                  
                  <div class='form_label'>
                    <?php echo "Temporaneo"; ?>
                </div>
                <div class='form_field'>
                    <input type="checkbox" name="isTemp" value="1" <?php if ($loaded_item['isTemp'] == 1) echo "checked='checked'"; ?> />
                </div>
                <div class='form_label'>
                    <?php echo "Durata (in giorni)"?>
                </div>
                <div class='form_field'>
                    <input type="number" name="temp_giorni" value="<?php echo $loaded_item['temp_giorni']; ?>" />
                </div>
                <div class='form_label'>
                    <?php echo "Bonus forza"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="bonus_car1_extra" value="<?php echo (int) $loaded_item['bonus_car1_extra']; ?>" />
                </div>
				<div class='form_label'>
                    <?php echo "Bonus destrezza"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="bonus_car2_extra" value="<?php echo (int) $loaded_item['bonus_car2_extra']; ?>" />
                </div>   
                <div class='form_label'>
                    <?php echo "Bonus mente"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="bonus_car3_extra" value="<?php echo (int) $loaded_item['bonus_car3_extra']; ?>" />
                </div>
                <div class='form_label'>
                    <?php echo "Bonus tempra"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="bonus_car4_extra" value="<?php echo (int) $loaded_item['bonus_car4_extra']; ?>" />
                </div>
                
                <input type="hidden" name="op" value="update" />
                <input type="hidden" name="id_oggetto" value="<?php echo $loaded_item['id_oggetto']; ?>" />
                <div class='form_submit'>
                    <input type="submit" name="modifica" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                    <input type="submit" name="elimina" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['delete']); ?>" /></div>  
              </form>
            <?php
            }//fine load_idem           
            
            ?>
    <?php   
    
        //FILES

    function upload_image(){
$allow = array("jpg", "jpeg", "gif", "png");

$todir = "imgs/items/";
$test = $_FILES['img_oggetto']['name'];

if ( !!$_FILES['img_oggetto']['tmp_name'] ) // is the file uploaded yet?
{
    $info = explode('.', strtolower( $_FILES['img_oggetto']['name']) ); // whats the extension of the file

    if ( in_array( end($info), $allow) ) // is this file allowed
    {
        if ( move_uploaded_file( $_FILES['img_oggetto']['tmp_name'], $todir . basename($_FILES['img_oggetto']['name'] ) ) )
        {
            // the file has been moved correctly
        }
    }
    else
    {
        // error this file ext is not allowed
    }
}
}
    ?>