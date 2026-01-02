<link rel="stylesheet" href="../themes/crystal/scheda_equip.css">

<div class="pagina_scheda_oggetti">
    <?php /*Permessi: */
    $tae = gdrcd_query("SELECT nome, id_mestiere FROM personaggio WHERE nome = '". $_SESSION['login'] ."'");
    $cod = gdrcd_query("SELECT cod_tipo FROM codtipooggetto WHERE cod_tipo = '".gdrcd_filter('get', $_REQUEST['what'])."'");
    if (($_SESSION['login'] == $_REQUEST['pg'] || $_SESSION['admin'] == 1) || ($tae['id_mestiere'] == 3 && $cod['cod_tipo'] == 8) || ($tae['id_mestiere'] == 4 && $cod['cod_tipo'] == 9)) {
    ?>
    <?php
    //Se non e' stato specificato il nome del pg
    if (isset($_REQUEST['pg']) === false)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknonw_character_sheet']) . '</div>';
    } else
    {

    /*Visualizzo la pagina*/
    /*Verifico l'esistenza del PG*/
    $query = "SELECT nome FROM personaggio WHERE personaggio.nome = '" . gdrcd_filter('get', $_REQUEST['pg']) . "'";
    $result = gdrcd_query($query, 'result');
    //Se non esiste il pg
    if (gdrcd_query($result, 'num_rows') == 0)
    {
        echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']) . '</div>';
    }
    else
    {

    gdrcd_query($result, 'free');

    /* Spostamento di un oggetto dallo zaino nell'inventario*/
    if (($_POST['op'] == "togli") && ($_SESSION['login'] == $_REQUEST['pg']))
    {
        gdrcd_query("UPDATE clgpersonaggiooggetto SET posizione = 0 WHERE id_oggetto = " . gdrcd_filter('num',
                $_POST['id_oggetto']) . " AND nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "' LIMIT 1 ");

        echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';
    }


    /* Aggiungi/modifica un commento*/
    if ((gdrcd_filter('get', $_POST['op']) == "commenta") && ($_SESSION['login'] == $_REQUEST['pg']))
    {
        gdrcd_query("UPDATE clgpersonaggiooggetto SET commento = '" . gdrcd_filter('in',
                $_POST['commento']) . "' WHERE id_oggetto = " . gdrcd_filter('num',
                $_POST['id_oggetto']) . " AND nome = '" . $_SESSION['login'] . "' LIMIT 1 ");

        echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';
    }
    
    /* Aggiungi/modifica un commento MASTER*/
    if (gdrcd_filter('get', $_POST['op']) == "commenta_master")
    {
        gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_master = '" . gdrcd_filter('in',
                $_POST['commento_master']) . "' WHERE id_oggetto = " . gdrcd_filter('num',
                $_POST['id_oggetto']) . " AND nome = '" . $_REQUEST['pg'] . "' LIMIT 1 ");

        echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';
    }
    
    /* Aggiungi/modifica un NEGOZIO*/
    if (gdrcd_filter('get', $_POST['op']) == "commenta_shop")
    {
        gdrcd_query("UPDATE clgpersonaggiooggetto SET commento_shop = '" . gdrcd_filter('in',
                $_POST['commento_shop']) . "' WHERE id_oggetto = " . gdrcd_filter('num',
                $_POST['id_oggetto']) . " AND nome = '" . $_REQUEST['pg'] . "' LIMIT 1 ");

        echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['done']) . '</div>';
    }


    /*Rimuovo un oggetto dall'inventario o dallo zaino*/
    if ((gdrcd_filter('get',
                $_POST['op']) == "abbandona") && ($_SESSION['login'] == $_REQUEST['pg'])
    )
    { /*Rimuovo un oggetto*/
        /*Se ne possiedo più di uno ne rimuovo uno solo*/
        if ($_POST['numero'] <= 1)
        {
            $query = "DELETE FROM clgpersonaggiooggetto WHERE id_oggetto = " . gdrcd_filter('num',
                    $_POST['id_oggetto']) . " AND nome = '" . gdrcd_filter('in', $_REQUEST['pg']) . "' LIMIT 1 ";
        } else
        {
            $query = "UPDATE clgpersonaggiooggetto SET numero = numero - 1 WHERE id_oggetto = " . gdrcd_filter('num',
                    $_POST['id_oggetto']) . " AND nome = '" . gdrcd_filter('get', $_REQUEST['pg']) . "' LIMIT 1 ";
        }
        gdrcd_query($query);

        echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['items']['warning']['done']);

    } ?>


    <!-- Elenco oggetti nello zaino -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['menu']['inventory']); ?></h2>
    </div>
    <div class="menu_scheda"><!-- Menu scheda -->
            <?php include ('scheda/menu.inc.php'); ?>
        </div>
        
        
        
    <div class="page_body">
    <?php
  if((isset($_POST['op']) === false) && (isset($_REQUEST['op']) === false)) {

        #---------------- ELENCO I TIPI DI OGGETTI POSSEDUTI ------------------
        ?>
        <table class="customTable">
                        <!-- Intestazione tabella -->
                        <tr class="second_header">
                            <td colspan=3>
                                <div>
                                    Tipo
                                </div>
                            </td>
                            <td colspan=3>
                                <div>
                                    Numero oggetti
                                </div>
                            </td>
                        </tr>
        <?php
        
        
        $query_tipo = "SELECT count(*) AS numero, codtipooggetto.cod_tipo, codtipooggetto.descrizione FROM oggetto, codtipooggetto, clgpersonaggiooggetto WHERE oggetto.tipo = codtipooggetto.cod_tipo AND clgpersonaggiooggetto.id_oggetto = oggetto.id_oggetto AND clgpersonaggiooggetto.nome = '" . gdrcd_filter('in',
                  $_REQUEST['pg']) . "' AND clgpersonaggiooggetto.posizione = 0 GROUP BY codtipooggetto.cod_tipo";
        $result_tipo = gdrcd_query($query_tipo, 'result');
        while ($recor = gdrcd_query($result_tipo, 'fetch'))
                    {
        echo '<tr>';

echo '<td class="casella_elemento" colspan=3>'; ?>

<a href="main.php?page=scheda_oggetti&op=visit&pg=<?php echo $_REQUEST['pg']; ?>&what=<?php echo gdrcd_filter('out', $recor['cod_tipo']); ?>"><?php echo gdrcd_filter('out',
                                                                                                                                                                           $recor['descrizione']
                                            ); ?></a>
<?php
echo '<td class="casella_elemento" colspan=3>'.$recor['numero'].'</td>';

echo '</tr>';

}//fine while categoria

gdrcd_query($result_tipo, 'free');

echo '</table></center>';            
}//if
?>
    
<? #---------------- ELENCO OGGETTI POSSEDUTI ------------------ 

    if($_REQUEST['op'] == 'visit') {
    ?>    
    
    <div class="panels_box">
            <?php /*Oggetti nello zaino*/
            $query = "SELECT oggetto.*, oggetto.nome AS nome_oggetto, clgpersonaggiooggetto.* FROM clgpersonaggiooggetto LEFT JOIN oggetto ON clgpersonaggiooggetto.id_oggetto=oggetto.id_oggetto WHERE oggetto.tipo = '".gdrcd_filter('get', $_REQUEST['what'])."' AND clgpersonaggiooggetto.nome = '" . gdrcd_filter('in',
                    $_REQUEST['pg']) . "' AND clgpersonaggiooggetto.posizione = 0 ORDER BY oggetto.nome DESC";
            $result = gdrcd_query($query, 'result'); ?>
            <!-- Intestazione tabella elenco -->
            <div class="elenco_record_gioco">
                <table class="customTable" align="center">
                    <tr>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo "Immagine"; ?>

                            </div>
                        </td>
                        
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo "Descrizioni e Commenti" ?>
                            </div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco">
                                <?php echo "&nbsp;"; ?>
                            </div>
                        </td>
                    </tr>


                    <?php while ($record = gdrcd_query($result, 'fetch'))
                    { ?>

                        <tr>
                        
                        
                        
                            <!-- Oggetto, immagine, quantità -->
                            <td class="casella_elemento">
                                
                                    <img src="imgs/items/<?php echo gdrcd_filter('out', $record['urlimg']); ?>" align="center" style="height: 120px;width: 120px;"/>
                              
                                </td>
                            
                            
                            
                            
                            
                                <!-- Descrizione e note-->                           
                            <td class="casella_elemento">
                                <div class="inventario_riga_descrizione">
                                    <span style="text-transform: uppercase;">
                                    <center><b><?php echo gdrcd_filter('out', $record['nome_oggetto']); ?></b> (<i>x <?php echo gdrcd_filter('out', $record['numero']); ?> <?php if ($record['cariche'] > 1) { echo "Cariche:".gdrcd_filter('out', $record['cariche']).""; } ?></i>)</center></span>
                                    <?php echo nl2br(gdrcd_filter('out', $record['descrizione'])); ?>                                    
                                </div>
                                
                                
                                
                                
                                
                                <?php if (($record['commento'] != '') && ($_SESSION['login'] != gdrcd_filter('get',
                                            $_REQUEST['pg']))
                                )
                                { ?>
                                    <div class="inventario_riga_commento">
                                    <span style="text-transform: uppercase;">
                                    <center><b>COMMENTO</b></center></span>
                                    <i><?php echo $record['commento']; ?></i>
                                    </div><?php } else
                                {
                                    if ($_SESSION['login'] == gdrcd_filter('get', $_REQUEST['pg']))
                                    {//if
                                        ?>
                                        <div>
                                        <!-- Commento -->
                                        <form action="main.php?page=scheda_oggetti"
                                              method="post">
                                            <input type="hidden"
                                                   value="commenta"
                                                   name="op"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['id_oggetto']; ?>"
                                                   name="id_oggetto"/>
                                            <textarea type="textbox" name="commento"
                                                      class="form_textarea"><?php echo $record['commento']; ?></textarea>
                                            <br><center><input type="submit"
                                                   value="<?php echo gdrcd_filter('out',
                                                       $MESSAGE['interface']['sheet']['items']['list']['add_note']); ?>"/></center>
                                            <input type="hidden"
                                                   value="<?php echo $_REQUEST['pg']; ?>"
                                                   name="pg"/>
                                        </form>
                                        </div><?php }
                                } //else if
                                 /*Commenti master, pandora e magic*/
                                ?>
                               
                               
                                <?php 
                                
                                /** MASTER **/
                                if(($record['commento_master'] != '') && (($_SESSION['login'] != gdrcd_filter('get',
                                            $_REQUEST['pg']) && ($_SESSION['admin']!=1 && $_SESSION['master']!=1 )) || ($_SESSION['login'] == gdrcd_filter('get',$_REQUEST['pg']))))
                                {
                                echo '<hr>';
                                ?>
                                <span style="text-transform: uppercase;">
                                    <center><b>COMMENTO MASTER</b></center></span>
                                    <?php
                                    echo $record['commento_master']; ?>
                                    <?php } else
                                {
                                    if (($_SESSION['login'] != gdrcd_filter('get', $_REQUEST['pg']) && ($_SESSION['admin']==1 || $_SESSION['master']==1)))
                                    {//if
                                        ?>
                                        <div>
                                        <!-- Commento -->
                                        <form action="main.php?page=scheda_oggetti"
                                              method="post">
                                            <input type="hidden"
                                                   value="commenta_master"
                                                   name="op"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['id_oggetto']; ?>"
                                                   name="id_oggetto"/>
                                            <textarea type="textbox" name="commento_master"
                                                      class="form_textarea"><?php echo $record['commento_master']; ?></textarea>
                                            <input type="submit"
                                                   value="Commento Master"/>
                                            <input type="hidden"
                                                   value="<?php echo $_REQUEST['pg']; ?>"
                                                   name="pg"/>
                                        </form>
                                        </div><?php }
                                }
                                
                                
                                
                                ?>
                                
                                <?php 
                                
                                 /** check tipo **/
                                 
                                 $tipo = gdrcd_query("SELECT descrizione FROM codtipooggetto WHERE cod_tipo = '".$record['tipo']."'");
                                
                                /** PANDORA **/
                               
                                
                                if($tipo['descrizione']=='Secret Pandora'){
                                $gilda = gdrcd_query("SELECT mestiere.nome, personaggio.id_mestiere FROM mestiere JOIN personaggio ON personaggio.id_mestiere=mestiere.id_mestiere WHERE personaggio.nome = '".$_SESSION['login']."'");
                                if(($record['commento_shop'] != '') && (($_SESSION['login'] != gdrcd_filter('get',
                                            $_REQUEST['pg']) && ($_SESSION['admin']!=1 && $gilda['id_mestiere'] != '4' )) || ($_SESSION['login'] == gdrcd_filter('get',$_REQUEST['pg']))))
                                {
                                echo '<hr>';
                                ?>
                                <span style="text-transform: uppercase;">
                                    <center><b>COMMENTO SECRET PANDORA</b></center></span>
                                    <?php
                                echo $record['commento_shop']; ?>
                                    <?php } elseif($record['commento_shop']!='' || $record['commento_shop']=='')
                                {
                                    if (($_SESSION['login'] != gdrcd_filter('get', $_REQUEST['pg'])) && ($_SESSION['admin']==1 || $gilda['id_mestiere'] == '4' ))
                                    {//if
                                        ?>
                                        <div>
                                        <!-- Commento -->
                                        <form action="main.php?page=scheda_oggetti&op=visit&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>&what=9"
                                              method="post">
                                            <input type="hidden"
                                                   value="commenta_shop"
                                                   name="op"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['id_oggetto']; ?>"
                                                   name="id_oggetto"/>
                                            <textarea type="textbox" name="commento_shop"
                                                      class="form_textarea"><?php echo $record['commento_shop']; ?></textarea>
                                            <input type="submit"
                                                   value="Commento Pandora"/>
                                            <input type="hidden"
                                                   value="<?php echo $_REQUEST['pg']; ?>"
                                                   name="pg"/>
                                        </form>
                                        </div><?php }
                                }
                                
                                }
                                /*Fine commenti master, pandora e magic*/
                                
                                ?>
                               

                                <?php 
                                if($tipo['descrizione']=='Magic Shop'){
                                /** MAGIC **/
								$gilda = gdrcd_query("SELECT mestiere.nome, personaggio.id_mestiere FROM mestiere JOIN personaggio ON personaggio.id_mestiere=mestiere.id_mestiere WHERE personaggio.nome = '".$_SESSION['login']."'");
                                if(($record['commento_shop'] != '') && (($_SESSION['login'] != gdrcd_filter('get',
                                            $_REQUEST['pg']) && ($_SESSION['admin']!=1 && $gilda['id_mestiere'] != '3')) || ($_SESSION['login'] == gdrcd_filter('get',$_REQUEST['pg']))))
                                {
                                echo '<hr>';
                                ?>
                                <span style="text-transform: uppercase;">
                                    <center><b>COMMENTO MAGIC SHOP</b></center></span>
                                    <?php
                                echo $record['commento_shop']; ?>
                                    <?php } elseif($record['commento_shop']!='' || $record['commento_shop']=='')
                                {
                                    if (($_SESSION['login'] != gdrcd_filter('get', $_REQUEST['pg'])) && ($_SESSION['admin'] == 1 || $gilda['id_mestiere'] == '3'))
                                    {//if
                                        ?>
                                        <div>
                                        <!-- Commento -->
                                        <form action="main.php?page=scheda_oggetti&op=visit&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>&what=8"
                                              method="post">
                                            <input type="hidden"
                                                   value="commenta_shop"
                                                   name="op"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['id_oggetto']; ?>"
                                                   name="id_oggetto"/>
                                            <textarea type="textbox" name="commento_shop"
                                                      class="form_textarea"><?php echo $record['commento_shop']; ?></textarea>
                                            <input type="submit"
                                                   value="Commento Magic"/>
                                            <input type="hidden"
                                                   value="<?php echo $_REQUEST['pg']; ?>"
                                                   name="pg"/>
                                        </form>
                                        </div><?php }
                                }
                                
                                }
                                /*Fine commenti master, pandora e magic*/
                                
                                ?>
                            </td>
                            <!-- Comandi elenco -->
                            <td class="casella_controlli">
                                <?php if ($_SESSION['login'] == $_REQUEST['pg'])
                                { ?>
                                    <div class="form_gioco">
                                        <!-- Abbandona -->
                                        <form action="main.php?page=scheda_oggetti"
                                              method="post">
                                            <input type="hidden"
                                                   value="abbandona"
                                                   name="op"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['numero']; ?>"
                                                   name="numero"/>
                                            <input type="hidden"
                                                   value="<?php echo $record['id_oggetto']; ?>"
                                                   name="id_oggetto"/>
                                            <input type="submit"
                                                   value="<?php echo gdrcd_filter('out',
                                                       $MESSAGE['interface']['sheet']['items']['list']['drop']); ?>"/>
                                            <input type="hidden"
                                                   value="<?php echo $_REQUEST['pg']; ?>"
                                                   name="pg"/>
                                        </form>
                                        <?php if ($record['ubicabile'] > 0)
                                        { ?>
                                            <!-- Zaino -->
                                            <form action="main.php?page=scheda_equip"
                                                  method="post">
                                                <input type="hidden"
                                                       value="in_zaino"
                                                       name="op"/>
                                                <input type="hidden"
                                                       value="<?php echo $record['id_oggetto']; ?>"
                                                       name="id_oggetto"/>
                                                <input type="submit"
                                                       value="<?php echo gdrcd_filter('out',
                                                           $MESSAGE['interface']['sheet']['items']['list']['put_in']); ?>"/>
                                                <input type="hidden"
                                                       value="<?php echo $_REQUEST['pg']; ?>"
                                                       name="pg"/>
                                            </form>
                                        <?php } ?>
                                    </div>
                                <?php } else
                                {
                                    echo "&nbsp;";
                                } ?>
                            </td>
                        </tr>


                    <?php }//while

                    gdrcd_query($result, 'free');

                    ?>
                </table>
            </div>
       <?php }//fine op visit
        ?>
        </div>
        <!-- Link a piè di pagina -->
        <div class="link_back">
            <a href="main.php?page=scheda_equip&pg=<?php echo gdrcd_filter('url',
                $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out',
                        $MESSAGE['interface']['sheet']['items']['list']['put_in']) . '.'; ?></a><br/>
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url',
                $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out',
                    $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div>


        <?php
        /********* CHIUSURA SCHEDA **********/
        }//else

        }//else
        
        } else {
        ?>
        Ce sta un motivo se 'sto link nun compare :)<br>
    Nun fa er furb*!<br><br>
    
    <!-- Link a piè di pagina -->
        <div class="link_back">
            <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('url', $_REQUEST['pg']); ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back']); ?></a>
        </div>
        <?php } ?>
    </div>
</div><!-- Pagina -->