<div class="pagina_gestione_mercato">
<?php /*Controllo permessi*/
if($_SESSION['admin'] != 1) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {
    //depotenzia
    
    if($_POST['op'] == 'depotenzia') {
        gdrcd_query("UPDATE clgpersonaggioabilita SET grado = grado - 1 WHERE id_abilita = ".gdrcd_filter('num', $_POST['id_record'])." AND nome = '".gdrcd_filter('in', $_POST['pg'])."'"); 
        gdrcd_query("UPDATE personaggio SET punto_skill = punto_skill + 1, esperienza_s = esperienza_s + 1 WHERE nome = '".gdrcd_filter('in', $_POST['pg'])."'"); 
    } else if($_POST['op'] == 'cancella') {
    
    $totale = $_POST['totale'];
    
    // Eliminazione dell'abilità dal personaggio
    gdrcd_query("DELETE FROM clgpersonaggioabilita WHERE id_abilita = " . gdrcd_filter('num', $_POST['id_record']) . " AND nome = '" . gdrcd_filter('in', $_POST['pg']) . "'");

    // Aggiornamento dei punti skill nel personaggio
    gdrcd_query("UPDATE personaggio SET punto_skill = punto_skill + $totale, esperienza_s = esperienza_s + $totale WHERE nome = '" . gdrcd_filter('in', $_POST['pg']) . "'");
    
    //echo "<script type='text/javascript'>alert('$totale');</script>";
    }
    
    $elenco_pg = gdrcd_query("SELECT nome, cognome FROM personaggio ORDER BY nome", 'result');
    ?>
    <div class="panels_box">
            <!-- Elenco pg -->
    <div class="panels_box">
                <form class="form_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                    <div class='form_label'>
                        <?php echo 'Seleziona personaggio'; ?>
                    </div>
                    <div class='form_field'>
                        <?php if(gdrcd_query($elenco_pg, 'num_rows') > 0) { ?>
                            <select name="load_item">
                             <?php                            
                            //facciamo visualizzare a magic e pandora solo la loro categoria//
                              while($option = gdrcd_query($elenco_pg, 'fetch')) { ?>
                                    <option value="<?php echo $option['nome']; ?>">
                                        <?php echo gdrcd_filter('out', $option['nome']); ?>
                                        <?php echo gdrcd_filter('out', $option['cognome']); ?>
                                    </option>
                                <?php }
                                gdrcd_query($elenco_pg, 'free');
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
    
                <?php
function calcolaSpesaAcquisto($tipo, $grado) {
    switch ($tipo) {
        case 'Default':
            return ($grado > 1) ? $grado - 1 : 0; // 0 Punti skill per il Lv0, poi 1 punti skill per livelli successivi
        case 'Difensiva':
            return 0; // 0 Punti skill
        case 'Generica base':
            return 1; // 1 Punto skill
        case 'Generica avanzata':
            return 3; // 3 Punti skill
        case 'Attacco base':
        case 'Mentale base':
            return ($grado > 1) ? $grado : 1; // 1 Punto skill per il Lv0, poi 1 Punti skill per i livelli successivi
        case 'Attacco medio':
        case 'Mentale media':
            return ($grado > 1) ? $grado + 2 : 3; // 3 Punti skill per il Lv0, poi 1 Punti skill per i livelli successivi
        case 'Attacco avanzato':
        case 'Mentale avanzata':
            return ($grado > 1) ? $grado + 4 : 5; // 5 Punti skill per il Lv0, poi 1 Punti skill per i livelli successivi
        case 'Potere speciale':
            return 50; // 50 Punti skill
        default:
            return 0; // Per tipologie non definite, consideriamo 0
    }
}

if ($_POST['load_item'] != "") {
    // Recupera il nome del personaggio selezionato
    $personaggio_selezionato = gdrcd_filter('in', $_POST['load_item']);

    // Query per recuperare le abilità del personaggio raggruppate per tipo
    $query_abilita = "
        SELECT abilita.id_abilita, abilita.nome AS nome_abilita, abilita.tipo AS tipo_abilita, clgpersonaggioabilita.grado
        FROM abilita
        LEFT JOIN clgpersonaggioabilita ON abilita.id_abilita = clgpersonaggioabilita.id_abilita 
        WHERE clgpersonaggioabilita.nome = '$personaggio_selezionato' AND abilita.tipo != 'Talento'
        ORDER BY abilita.tipo, abilita.nome
    ";
    $result_abilita = gdrcd_query($query_abilita, 'result');

    // Array per raggruppare le abilità per tipo
    $abilita_per_tipo = array();

    // Itera sui risultati della query e raggruppa le abilità per tipo
    while ($abilita = gdrcd_query($result_abilita, 'fetch')) {
        $tipo_abilita = $abilita['tipo_abilita'];

        // Se il tipo non esiste ancora nell'array, crea un nuovo array vuoto per quel tipo
        if (!isset($abilita_per_tipo[$tipo_abilita])) {
            $abilita_per_tipo[$tipo_abilita] = array();
        }

        // Aggiungi l'abilità all'array del tipo corrispondente
        $abilita_per_tipo[$tipo_abilita][] = $abilita;
    }

    // Rilascia la risorsa del risultato della query
    gdrcd_query($result_abilita, 'free');
}

?>

<!-- Visualizzazione delle abilità raggruppate per tipo -->
<div class="panels_box">
    <?php foreach ($abilita_per_tipo as $tipo => $abilita_array) : ?>
        <div class="panels_box">
            <div class="form_label">
                <?php echo 'Elenco abilità di tipo ' . htmlspecialchars($tipo); ?>
            </div>

            <!-- Elenco delle abilità del tipo corrente -->
            <div class="elenco_record_gestione">
                <table>
                    <!-- Intestazione tabella -->
                    <tr>
                        <td class="casella_titolo">
                            <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']); ?></div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco"><?php echo 'Livello'; ?></div>
                        </td>
                        <td class="casella_titolo">
                            <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                        </td>
                    </tr>
                    <!-- Record delle abilità del tipo corrente -->
                    <?php foreach ($abilita_array as $abilita) : ?>
                        <tr class="risultati_elenco_record_gestione">
                            <td class="casella_elemento">
                                <div class="elementi_elenco">
                                    <?php echo $abilita['nome_abilita']; ?>
                                </div>
                            </td>
                            <td class="casella_elemento">
                                <div class="elementi_elenco">
                                    <?php echo $abilita['grado']; 
                                    
                                    $grado = $abilita['grado'];
                                    
                                    // Calcolo dei punti skill restituiti
                                    $puntiRestituiti = calcolaSpesaAcquisto($tipo, $grado);
                                    ?>
                                    <input type="hidden" name="totale" value="<? echo $puntiRestituiti ?>" />
                                    
                                </div>
                            </td>
                            <td class="controlli_elenco">
                                <div class="controlli_elenco_wrapper">
                                    <?php if ($abilita['grado'] > 1) : ?>
                                        <div class="controllo_elenco">
                                            <form class="opzioni_elenco_record_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                                                <input type="hidden" name="id_record" value="<?php echo $abilita['id_abilita']; ?>" />
                                                <input type="hidden" name="pg" value="<?php echo $personaggio_selezionato; ?>" />
                                                <input type="hidden" name="op" value="depotenzia" />
                                                <input type="submit" value="-" />
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <div class="controllo_elenco">
                                        <form class="opzioni_elenco_record_gestione" action="main.php?page=gst_personaggio_skill" method="post">
                                            <input type="hidden" name="id_record" value="<?php echo $abilita['id_abilita']; ?>" />
                                            <input type="hidden" name="pg" value="<?php echo $personaggio_selezionato; ?>" />
                                            <input type="hidden" name="op" value="cancella" />
                                            <input type="submit" value="X" />
                                            <input type="hidden" name="totale" value="<? echo $puntiRestituiti ?>" />
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
    }//fine else permessi
?>
</div>

<style>
/* Aggiungi un po' di stile per migliorare l'allineamento delle tabelle */
.panels_box {
    margin-bottom: 20px;
}

.elenco_record_gestione {
    margin: 10px 0;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.casella_titolo, .casella_elemento, .controlli_elenco {
    padding: 10px;
    border: 1px solid #ccc;
}

.titoli_elenco, .elementi_elenco, .controlli_elenco_wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
}

.controlli_elenco_wrapper {
    display: flex;
    justify-content: space-around;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px; /* Aggiungi uno spazio tra i form */
}

.opzioni_elenco_record_gestione {
    margin: 0;
}
</style>