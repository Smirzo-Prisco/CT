<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<?php
// Se è stata selezionata una tipologia, salvala
if (isset($_POST['tipo_oggetto'])) {
    $tipo_oggetto_selezionato = $_POST['tipo_oggetto'];
} else {
    $tipo_oggetto_selezionato = null;
}
?>

<div class="pagina_gestione_mercato">

<?php if ($tipo_oggetto_selezionato === null) { ?>
    <!-- Prima schermata: selezione della tipologia di oggetto -->
    <form class="form_gestione" action="main.php?page=oggetto_aggiungi_vedere" method="post">
        <table class="customTable">
            <tr>
                <td style="font-size: 12px; color: #a7a7a8;">
                    SELEZIONA LA TIPOLOGIA DI OGGETTO DA CARICARE
                </td>
            </tr>
            
            <!-- Tipologia di oggetto -->
            <tr class="second_header">
                <td><b>Tipologia di oggetto</b></td>
            </tr>
            <tr>
                <td>
                    <select name="tipo_oggetto" class="ares" style="background-color: #0f111d;">
                        <option value="standard">Oggetto standard</option>
                        <option value="arma">Arma</option>
                        <option value="curativo">Oggetto curativo</option>
                        <option value="statistica">Oggetto aumenta statistica</option>
                        <option value="magico">Oggetto magico</option>
                    </select>
                </td>
            </tr>

            <!-- Pulsante Avanti -->
            <tr>
                <td>
                    <input name="submit" type="submit" value="Avanti" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
        </table>
    </form>
    
<?php } else { ?>
    <!-- Seconda schermata: aggiungi l'oggetto selezionato -->
        <!-- Mantieni il tipo di oggetto selezionato come input nascosto -->
        <input type="hidden" name="tipo_oggetto" value="<?php echo $tipo_oggetto_selezionato; ?>">

        <table class="customTable">
            <tr>
                <td style="font-size: 12px; color: #a7a7a8;">
                    AGGIUNGI UN OGGETTO
                </td>
            </tr>
            
            <!-- Nome oggetto -->
            <tr class="second_header">
                <td><b>Nome oggetto</b></td>
            </tr>
            <tr>
                <td>
                    <input type="text" name="nome_oggetto" value="<?php echo $loaded_item['nome']; ?>" size="40" placeholder="Inserisci il nome dell'oggetto" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
            
            <!-- Descrizione -->
            <tr class="second_header">
                <td><b>Descrizione (supporta HTML e invio a capo)</b></td>
            </tr>
            <tr>
                <td>
                    <textarea name="descrizione_oggetto" rows="5" cols="40" placeholder="Descrivi l'oggetto" class="ares" style="background-color: #0f111d;"><?php echo $loaded_item['descrizione']; ?></textarea>
                </td>
            </tr>

            <!-- Immagine -->
            <tr class="second_header">
                <td><b>Carica immagine</b></td>
            </tr>
            <tr>
                <td>
                    <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>">
                </td>
            </tr>
            
            <!-- Posizionabile in -->
            <tr class="second_header">
                <td><b>Posizionabile in</b></td>
            </tr>
            <tr>
                <td>
                    <select name="fit_in">
                        <option value="<?php echo INVENTARIO; ?>" <?php if($loaded_item['ubicabile'] == INVENTARIO) {echo 'selected';} ?>>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['items']['fit_in']['inventory']); ?>
                        </option>
                        <!-- Continua con altre opzioni -->
                    </select>
                </td>
            </tr>

            <!-- Categoria oggetto -->
            <tr class="second_header">
                <td><b>Categoria oggetto</b></td>
            </tr>
            <tr>
                <td>
                    <?php 
                    $tipi_oggetto = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');
                    $mestiere = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

                    if (gdrcd_query($tipi_oggetto, 'num_rows') > 0) { ?>
                        <select name="tipo_oggetto">
                        <?php
                        if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1) {
                            switch ($mestiere['id_mestiere']) {
                                case 3:
                                    echo '<option value="8">Magic Shop</option>';
                                    break;
                                case 4:
                                    echo '<option value="9">Secret Pandora</option>';
                                    break;
                                case 1:
                                    echo '<option value="10">ICC</option>';
                                    break;
                                default:
                                    if ($_SESSION['capogilda'] == 1) {
                                        echo '<option value="15">Arma di Gilda</option>';
                                    }
                                    break;
                            }
                        } else {
                            while ($option = gdrcd_query($tipi_oggetto, 'fetch')) { ?>
                                <option value="<?php echo $option['cod_tipo']; ?>" <?php if ($loaded_item['tipo'] == $option['cod_tipo']) { echo 'SELECTED'; } ?>>
                                    <?php echo gdrcd_filter('out', $option['descrizione']); ?>
                                </option>
                            <?php }
                        }
                        gdrcd_query($tipi_oggetto, 'free'); ?>
                        </select>
                    <?php } ?>
                </td>
            </tr>
            
            
            <?php if ($tipo_oggetto_selezionato === 'arma') { ?>
    <!-- Sezione aggiuntiva per le armi -->
    
    <!-- Tipo di arma -->
    <tr class="second_header">
        <td><b>Tipo di arma</b></td>
    </tr>
    <tr>
        <td>
            <select name="tipo_arma" class="ares">
                <option value="1">Arma bianca</option>
                <option value="2">Arma da lancio</option>
                <option value="3">Arma da fuoco</option>
            </select>
        </td>
    </tr>

    <!-- Bonus arma -->
    <tr class="second_header">
        <td><b>Bonus arma</b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_arma" class="ares">
                <?php for ($i = 1; $i <= 3; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Ricarica massima -->
    <tr class="second_header">
        <td><b>Ricarica massima</b></td>
    </tr>
    <tr>
        <td>
            <select name="ricarica_massima" class="ares">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" value="1">
    
<?php } ?>




<?php if ($tipo_oggetto_selezionato === 'curativo') { ?>
    <!-- Sezione aggiuntiva per gli oggetti curativi -->

    <!-- Quanta salute integra -->
    <tr class="second_header">
        <td><b>Quanta salute integra</b></td>
    </tr>
    <tr>
        <td>
            <select name="salute_integra" class="ares">
                <?php for ($i = 0; $i <= 20; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Quanta integrità integra -->
    <tr class="second_header">
        <td><b>Quanta integrità integra</b></td>
    </tr>
    <tr>
        <td>
            <select name="integrita_integra" class="ares">
                <?php for ($i = 0; $i <= 20; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" value="0">

    <!-- Campo nascosto per cariche -->
    <input type="hidden" name="cariche" value="1">

<?php } ?>






<?php if ($tipo_oggetto_selezionato === 'statistica') { ?>
    <!-- Sezione aggiuntiva per oggetti che aumentano le statistiche -->

    <!-- Durata (in giorni) -->
    <tr class="second_header">
        <td><b>Durata (in giorni)</b></td>
    </tr>
    <tr>
        <td>
            <select name="temp_giorni" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 30; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristica 1 -->
    <tr class="second_header">
        <td><b>Bonus <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car0']); ?></b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_car1_extra" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristica 2 -->
    <tr class="second_header">
        <td><b>Bonus <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car2']); ?></b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_car2_extra" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristica 3 -->
    <tr class="second_header">
        <td><b>Bonus <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car4']); ?></b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_car3_extra" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristica 4 -->
    <tr class="second_header">
        <td><b>Bonus <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car6']); ?></b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_car4_extra" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristica 5 -->
    <tr class="second_header">
        <td><b>Bonus <?php echo gdrcd_filter('out', $PARAMETERS['names']['stats']['car8']); ?></b></td>
    </tr>
    <tr>
        <td>
            <select name="bonus_car5_extra" class="ares" style="background-color: #0f111d;">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" value="1">

    <!-- Campo nascosto per isTemp -->
    <input type="hidden" name="isTemp" value="1">

<?php } ?>




<?php if ($tipo_oggetto_selezionato === 'standard' || $tipo_oggetto_selezionato === 'magico') { ?>
    <!-- Sezione aggiuntiva per oggetti standard e magici -->

    <!-- Cariche -->
    <tr class="second_header">
        <td><b>Utilizzo (non ricaricabile)</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <option value="illimitato">Illimitato</option>
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" value="0">

<?php } ?>






            <!-- Submit -->
            <tr>
                <td>
                    <input name="submit" type="submit" value="Aggiungi Oggetto" class="ares" style="background-color: #0f111d;" disabled>
                </td>
            </tr>
        </table>
<?php } ?>
</div>
