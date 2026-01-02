<div class="pagina_gestione_mercato">
<?php
if($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {
    /* Se e' stato richiesto di caricare un oggetto */
    if($_POST['op'] == 'load') {
        $loaded_character = gdrcd_query("SELECT * FROM personaggio WHERE nome='".gdrcd_filter('out', $_POST['load_pg'])."'");
    }
    
    /* Facciamo partire l'esilio */
    if(gdrcd_filter('get', $_POST['op']) == 'exile') {
        gdrcd_query("UPDATE personaggio SET esilio = '" . gdrcd_filter('num',
                    $_POST['year']) . '-' . gdrcd_filter('num', $_POST['month']) . '-' . gdrcd_filter('num',
                    $_POST['day']) . "', data_esilio=NOW(), autore_esilio = '" . $_SESSION['login'] . "', motivo_esilio = '" . gdrcd_filter('in',
                    $_POST['causale']) . "' WHERE nome = '" . gdrcd_filter('in',
                    $_POST['pg']) . "'");
        echo '<div class="warning">Personaggio'. $_POST['pg'].' esiliato!</div>';
    }
    
    /* Facciamo partire la grazia de dio */
    if(gdrcd_filter('get', $_POST['op']) == 'grazia') {
        gdrcd_query("UPDATE personaggio SET esilio = NULL, data_esilio='2009-07-01', autore_esilio = NULL, motivo_esilio = NULL WHERE nome = '" . gdrcd_filter('in', $_POST['pg']) . "'");
        echo '<div class="warning">Personaggio'. $_POST['pg'].' graziato!</div>';
    }
    
    // carichiamo i personaggi
    $tutti_pg = gdrcd_query("SELECT nome FROM personaggio WHERE (esilio < NOW() Or esilio IS NULL) ORDER BY nome", 'result');
    $tutti_exile = gdrcd_query("SELECT nome FROM personaggio WHERE esilio > NOW() ORDER BY nome", 'result');
?>

<div class="panels_box">
    <center>
        <font size=4>Esilia/Imprigiona personaggio</font><br/><br/>
        <font size=1>Un personaggio esiliato non avr&agrave; pi&ugrave; l'opportunit&agrave; di entrare in land.<br>
        Un personaggio imprigionato non potr&agrave; entrare in land per un lasso di tempo limitato e concordato con la moderazione.</font>
    </center>
    <br><br> 

    <form class="form_gestione" action="main.php?page=gestione_esilio" method="post">
        <div class='form_label'>Scegli personaggio</div>
            <div class='form_field'>
            <?php
            if(gdrcd_query($tutti_pg, 'num_rows') > 0) { ?>
                <select name="load_pg">
                    <optgroup label="Personaggi">
                    <?php
                    while($option = gdrcd_query($tutti_pg, 'fetch')) { ?>
                        <option value="<?php echo $option['nome']; ?>">
                            <?php echo gdrcd_filter('out', $option['nome']); ?>
                        </option>
                    <?php
                    }
                    echo '<optgroup label="Esiliati">';

                    while($option_exile = gdrcd_query($tutti_exile, 'fetch')) { ?>
                        <option value="<?php echo $option_exile['nome']; ?>">
                            <?php echo gdrcd_filter('out', $option_exile['nome']); ?>
                        </option>
                    <?php
                    }
                    gdrcd_query($tutti_pg, 'free');
                    gdrcd_query($tutti_exile, 'free');
                    ?>
                </select>
            <?php
            } ?>
            </div>
            <input type="hidden" name="op" value="load" />
            <div class='form_submit'>
                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
            </div>
    </form>
                        
    <?php
    if ($_POST['load_pg'] != "") { 
        if ($loaded_character['esilio'] == NULL) { 
    ?>
            <!-- INIZIAMO L'ESILIO o LA GRAZIE -->
            <br>
            <form action="main.php?page=gestione_esilio" method="post">
                <div class='form_label'>
                    <?php echo gdrcd_filter('out',
                        $MESSAGE['interface']['sheet']['modify_form']['exile']); ?>
                </div>
                <div class='form_field'>
                    <!-- Giorno -->
                    <select name="day" class="day">
                        <?php for ($i = 1; $i <= 31; $i++)
                        { ?>
                            <option value="<?php echo $i; ?>" <?php if (strftime('%d') == $i)
                            {
                                echo 'selected';
                            } ?>><?php echo $i; ?></option>
                        <?php }//for
                        ?>
                    </select>
                    <!-- Mese -->
                    <select name="month" class="month">
                        <?php for ($i = 1; $i <= 12; $i++)
                        { ?>
                            <option value="<?php echo $i; ?>" <?php if (strftime('%m') == $i)
                            {
                                echo 'selected';
                            } ?>><?php echo $i; ?></option>
                        <?php }//for
                        ?>
                    </select>
                    <!-- Anno -->
                    <select name="year" class="year">
                        <?php for ($i = strftime('%Y'); $i <= strftime('%Y') + 20; $i++)
                        { ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php }//for
                        ?>
                    </select>
                </div>
                <div class='form_label'>
                    <?php echo gdrcd_filter('out',
                        $MESSAGE['interface']['sheet']['modify_form']['why_exiled']); ?>
                </div>
                <div class='form_field'>
                    <input name="causale"/>
                </div>

                <div class='form_submit'>
                    <input type="submit" value="<?php echo $MESSAGE['interface']['forms']['submit']; ?>"/>
                    <input type="hidden" name="op" value="exile" />
                    <input type="hidden"
                            value="<?php echo $loaded_character['nome']; ?>"
                            name="pg"/>
                </div>
            </form>
        <?php
        } else { ?>
            <br>
            <!-- GRAZIA -->
            <form class="form_gestione" action="main.php?page=gestione_esilio" method="post">
                <div class='form_label'>
                    <?php echo "Grazia <b>". $loaded_character['nome']."</b>"; ?>
                </div>
                <input type="hidden" name="op" value="grazia" />
                <input type="hidden" name="pg" value="<?php echo $loaded_character['nome']; ?>" />
                <div class='form_submit'>
                    <input type="submit" value="Grazia <?php echo $loaded_character['nome']; ?>" />
                </div>
            </form>     
                
        <?php
        }//fine grazia ?>
    </div>
<?php
    } ?>
<?php
}//fine permessi ?>
</div>