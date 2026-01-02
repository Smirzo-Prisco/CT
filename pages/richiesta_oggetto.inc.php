<div class="pagina_gestione_mercato">

<?php
/* richiamo tutto */

 $tipi_oggetto = gdrcd_query("SELECT * FROM codtipooggetto WHERE (cod_tipo = 1 || cod_tipo = 2 || cod_tipo = 4 || cod_tipo = 6 || cod_tipo = 13 || cod_tipo = 14) ORDER BY descrizione", 'result');
 
 if(isset($_POST['op']) === true) {
 /*Carichiamo l'oggetto tra quelli da controllare*/
 if(gdrcd_filter('get', $_POST['op']) == 'insert') {
                if($_FILES['img_oggetto']['name'] == '') {
                    $immagine_oggetto = 'standard_oggetto.png';
                } else {
                    $immagine_oggetto = $_FILES['img_oggetto']['name'];
                }
                gdrcd_query("INSERT INTO oggetto (tipo, nome, urlimg, descrizione, ubicabile, cariche, tipo_arma, creatore, data_inserimento,richiesto) VALUES (".gdrcd_filter('in', $_POST['tipo_oggetto']).", '".gdrcd_filter('in', $_POST['nome_oggetto'])."', '".gdrcd_filter('in', $immagine_oggetto)."', '".gdrcd_filter('in', $_POST['descrizione_oggetto'])."', ".gdrcd_filter('num', $_POST['fit_in']).", ".gdrcd_filter('num', $_POST['cariche']).", '".gdrcd_filter('num', $_POST['tipo_arma'])."', '".$_SESSION['login']."', NOW(), '1')");
                upload_image();
               $soldi_taken = gdrcd_query("UPDATE personaggio SET soldi = soldi - 300 WHERE nome = '".$_SESSION['login']."'");

                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['inserted']).'</div>';
            }
             } elseif(isset($_POST['op']) === false) {
/* Controlliamo se tiene li sordi */

$checksoldi = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

if ($checksoldi['soldi'] < 300) {

     echo '<div class="error">Accesso negato.<br> Hai bisogno di almeno 300 CY per richiedere un oggetto personalizzato.</div>';

} else {

?>
<center>
<font size=4>Richiedi Oggetto</font><br/><br/>
<font size=1>Le immagini devono essere in formato <b>100x100</b> per una corretta risoluzione.<br>
             Il costo per ogni oggetto &egrave; di <b>300 yen</b>.</font>
</center><br><br>




<!-- Form di impostazione dei campi -->
<form class="form_gestione" action="main.php?page=richiesta_oggetto" method="post" enctype="multipart/form-data">
<!-- tipo oggetto -->
<table class="customTable">
<tr class="second_header">
<td>
<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_type']); ?>
</td>
<tr>
<td>
                    <?php if(gdrcd_query($tipi_oggetto, 'num_rows') > 0) { ?>
                        <select name="tipo_oggetto">
                            <?php while($option = gdrcd_query($tipi_oggetto, 'fetch')) { ?>
                                <option value="<?php echo $option['cod_tipo']; ?>" <?php if($loaded_item['tipo'] == $option['cod_tipo']) {echo 'SELECTED';} ?>>
                                    <?php echo gdrcd_filter('out', $option['descrizione']); ?>
                                </option>
                            <?php
                            }
                            gdrcd_query($tipi_oggetto, 'free');
                            ?>
                        </select>
                        <?php } ?>
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_name']); ?>
</td>
</tr>
<tr>
<td>
                    <input type="text" name="nome_oggetto" value="<?php echo $loaded_item['nome']; ?>" placeholder="Nome Oggetto" />
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_image']); ?>
</td>
</tr>
<tr>
<td>
                    <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_info']); ?>
</td>
</tr>
<tr>
<td>
                    <textarea name="descrizione_oggetto" maxlength="255" placeholder="Descrizione di max 255 caratteri. Per andare a capo <br>"><?php echo $loaded_item['descrizione']; ?></textarea>
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo "Numero usi"; ?>
</td>
</tr>
<tr>
<td>
                    <select name="cariche">
                   <option value="-1">Utilizzo illimitato</option>
                   <option value="1">Utilizzo limitato</option>
                  </select>
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo "Tipo di Arma"; ?>
</td>
</tr>
<tr>
<td>
                    <select name="tipo_arma">
                    <option value="0">---</option>
                    <option value="1">Arma bianca</option>
                    <option value="2">Arma da lancio</option>
                    <option value="3">Arma da fuoco</option>
                    </select> 
</td>
</tr>
<tr class="second_header">
<td>
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['item_fit_in']); ?>
</td>
</tr>
<tr>
<td>
                    <select name="fit_in">
                        <option value="<?php echo INVENTARIO; ?>" <?php if($loaded_item['ubicabile'] == INVENTARIO) {echo 'selected';} ?>>
                            <?php echo "Non indossabile"; ?>
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
</td>
</tr>
<tr>
<td>
                    
                <input type="hidden" name="op" value="insert" />
                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
</td>
</tr>
</table>
                </form>



<br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>



<?php }//if post false ?>



<?php

}

function upload_image(){
$allow = array("jpg", "jpeg", "gif", "png");

$todir = 'imgs/items/';
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