

<?php
// Se è stata selezionata una tipologia, salvala
if (isset($_POST['categoria'])) {
    $tipo_oggetto_selezionato = $_POST['categoria'];
} else {
    $tipo_oggetto_selezionato = null;
}
?>

<div class="pagina_gestione_mercato">

<?php
if (isset($_POST['submit']) && $_POST['submit'] == 'Aggiungi Oggetto') {
    // Sanificazione base dei dati
    $nome = gdrcd_filter('in', $_POST['nome_oggetto']);
    $descrizione = gdrcd_filter('in', $_POST['descrizione_oggetto']);
    $categoria = gdrcd_filter('in', $_POST['categoria']);
    $ubicabile = (int) $_POST['fit_in'];
    $tipo = (int) $_POST['tipo_oggetto'];
    $richiede_ricarica = (int) $_POST['richiede_ricarica'];
    $cariche = ($_POST['cariche'] === 'illimitato') ? 'illimitato' : (int) $_POST['cariche'];
    
    // Se è arma di gilda, forza cariche illimitate e nessuna ricarica
    if (isset($_POST['tipo_oggetto']) && (int)$_POST['tipo_oggetto'] == 15) {
    $cariche = 'illimitato';
    $richiede_ricarica = 0;
    }

    $isTemp = isset($_POST['isTemp']) ? 1 : 0;
    $temp_giorni = isset($_POST['temp_giorni']) ? (int) $_POST['temp_giorni'] : 0;
    $creatore = $_SESSION['login'];
    $data_inserimento = date('Y-m-d H:i:s');
    $urlimg = '';

    // Gestione immagine
    if (!empty($_FILES['img_oggetto']['tmp_name'])) {
        $img_tmp = $_FILES['img_oggetto']['tmp_name'];
        $img_nome = basename($_FILES['img_oggetto']['name']);
        $img_ext = strtolower(pathinfo($img_nome, PATHINFO_EXTENSION));
        $target_dir = "imgs/items/";
        $target_file = $target_dir . uniqid('oggetto_') . "." . $img_ext;

        // Resize automatico se troppo grande
        list($width, $height) = getimagesize($img_tmp);
        if ($width > 200 || $height > 200) {
            $new_w = 200;
            $new_h = 200;
            $src_img = null;

            switch ($img_ext) {
                case 'jpg':
                case 'jpeg':
                    $src_img = imagecreatefromjpeg($img_tmp);
                    break;
                case 'png':
                    $src_img = imagecreatefrompng($img_tmp);
                    break;
                case 'gif':
                    $src_img = imagecreatefromgif($img_tmp);
                    break;
            }

            if ($src_img) {
                $dst_img = imagecreatetruecolor($new_w, $new_h);
                imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
                imagejpeg($dst_img, $target_file, 90); // Salva come JPG
                imagedestroy($src_img);
                imagedestroy($dst_img);
                $urlimg = basename($target_file); // Rimuove ../
            }
        } else {
            move_uploaded_file($img_tmp, $target_file);
            $urlimg = basename($target_file); // Rimuove ../
        }
    }

    // Extra: Se è un'arma
    $tipo_arma = isset($_POST['tipo_arma']) ? (int) $_POST['tipo_arma'] : 0;
    $bonus_arma = isset($_POST['bonus_arma']) ? (int) $_POST['bonus_arma'] : 0;
    $ricarica_massima = isset($_POST['ricarica_massima']) ? (int) $_POST['ricarica_massima'] : 0;

    // Heal
    $heal = isset($_POST['salute_integra']) ? (int) $_POST['salute_integra'] : 0;
    $integrita = isset($_POST['integrita_integra']) ? (int) $_POST['integrita_integra'] : 0;

    // Bonus caratteristiche extra
    $bonus_car1_extra = isset($_POST['bonus_car1_extra']) ? (int) $_POST['bonus_car1_extra'] : 0;
    $bonus_car2_extra = isset($_POST['bonus_car2_extra']) ? (int) $_POST['bonus_car2_extra'] : 0;
    $bonus_car3_extra = isset($_POST['bonus_car3_extra']) ? (int) $_POST['bonus_car3_extra'] : 0;
    $bonus_car4_extra = isset($_POST['bonus_car4_extra']) ? (int) $_POST['bonus_car4_extra'] : 0;
    $bonus_car5_extra = isset($_POST['bonus_car5_extra']) ? (int) $_POST['bonus_car5_extra'] : 0;

    // Inserimento nel DB
    gdrcd_query("
        INSERT INTO oggetto (
            tipo, nome, creatore, data_inserimento, descrizione, ubicabile,
            cariche, heal, richiesto, bonus_car1_extra, bonus_car2_extra, bonus_car3_extra,
            bonus_car4_extra, bonus_car5_extra, urlimg, isTemp, temp_giorni,
            tipo_arma, attacco, richiede_ricarica, ricarica_massima, categoria
        ) VALUES (
            $tipo, '$nome', '$creatore', '$data_inserimento', '$descrizione', $ubicabile,
            " . (is_numeric($cariche) ? $cariche : "'$cariche'") . ",
            $heal, '2', $bonus_car1_extra, $bonus_car2_extra, $bonus_car3_extra,
            $bonus_car4_extra, $bonus_car5_extra, '$urlimg', $isTemp, $temp_giorni,
            $tipo_arma, $bonus_arma, $richiede_ricarica, $ricarica_massima, '$categoria'
        )
    ");

    echo "<div class='success'>
            Oggetto <b>$nome</b> richiesto con successo! Attendi il parere degli admin
          </div>";

    echo "<div class='action-links'>
        <a href='main.php?page=oggetto_aggiungi_richiesta' class='ares'>
            Torna al caricamento oggetti
        </a>
        
      </div>";
      
      
      //parto con il mex agli admin
          $testo = gdrcd_filter_in("<b>(<i>Richiesta oggetto personalizzato. <br> Andare in pannello gestione, admin, approva/nega oggetto.</i>)</b>");
      // 1. Gestione dei destinatari multipli in base al ruolo
    $destinatari_multipli = [
        'admin' => "SELECT nome FROM privilegi WHERE admin = 1"
    ];
    
    foreach ($destinatari_multipli as $key => $query) {
            $result = gdrcd_query($query, 'result');
            while ($record = gdrcd_query($result, 'fetch')) {
                $nome = $record['nome'];

                // Invia segnalazione direttamente nel ciclo
                $sql_verifica_conversazione = "
                    SELECT id_conversazione 
                    FROM sms 
                    WHERE mittente_nome = 'Segnalazione' 
                    AND destinatario_nome = '" . gdrcd_filter('in', $nome) . "' 
                    LIMIT 1
                ";
                $result_verifica_conversazione = gdrcd_query($sql_verifica_conversazione, 'result');

                if (gdrcd_query($result_verifica_conversazione, 'num_rows') > 0) {
                    $row_conversazione = gdrcd_query($result_verifica_conversazione, 'fetch');
                    $id_conversazione = $row_conversazione['id_conversazione'];

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);
                } else {
                    $sql_ultimo_id = "SELECT MAX(id_conversazione) as max_id FROM sms";
                    $result_ultimo_id = gdrcd_query($sql_ultimo_id, 'result');
                    $row_ultimo_id = gdrcd_query($result_ultimo_id, 'fetch');
                    $nuovo_id_conversazione = $row_ultimo_id['max_id'] + 1;

                    $sql_inserisci_messaggio = "
                        INSERT INTO sms (mittente_nome, destinatario_nome, testo, id_conversazione, tipo_messaggio, ongame, ora_spedizione)
                        VALUES ('Segnalazione', '" . gdrcd_filter('in', $nome) . "', '$testo', $nuovo_id_conversazione, 'individuale', '0', NOW())
                    ";
                    gdrcd_query($sql_inserisci_messaggio);

                    $sql_inserisci_conversazione = "
                        INSERT INTO conversazioni_individuali (id_conversazione, utente_nome, lettura)
                        VALUES ($nuovo_id_conversazione, '" . gdrcd_filter('in', $nome) . "', 0)
                    ";
                    gdrcd_query($sql_inserisci_conversazione);
                }
            }
        }
} else {
?>

<?php if ($tipo_oggetto_selezionato === null) { ?>
    <!-- Prima schermata: selezione della tipologia di oggetto -->
    <form class="form_gestione" action="main.php?page=oggetto_aggiungi_richiesta" method="post">
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
                    <select name="categoria" class="ares" style="background-color: #0f111d;">
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
    <form class="form_gestione" action="main.php?page=oggetto_aggiungi_richiesta" method="post" enctype="multipart/form-data">
        <!-- Mantieni categoria e tipo di oggetto selezionati -->
        <input type="hidden" name="categoria" value="<?php echo $tipo_oggetto_selezionato; ?>">
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
                    <input type="file" name="img_oggetto">
                </td>
            </tr>
            
            <!-- Posizionabile in -->
            <tr class="second_header">
                <td><b>Posizionabile in</b></td>
            </tr>
            <tr>
                <td>
                    <?php
$posizioni = [
    INVENTARIO => $MESSAGE['interface']['administration']['items']['fit_in']['inventory'],
    ZAINO      => $MESSAGE['interface']['administration']['items']['fit_in']['bag'],
    MANODX     => $MESSAGE['interface']['administration']['items']['fit_in']['hand_dx'],
    MANOSX     => $MESSAGE['interface']['administration']['items']['fit_in']['hand_sx'],
    TORSO      => $MESSAGE['interface']['administration']['items']['fit_in']['chest'],
    GAMBE      => $MESSAGE['interface']['administration']['items']['fit_in']['legs'],
    PIEDI      => $MESSAGE['interface']['administration']['items']['fit_in']['feet'],
    TESTA      => $MESSAGE['interface']['administration']['items']['fit_in']['head'],
    ANELLO     => $MESSAGE['interface']['administration']['items']['fit_in']['ring'],
    COLLO      => $MESSAGE['interface']['administration']['items']['fit_in']['neck']
];
?>

<select name="fit_in" class="ares" style="background-color: #0f111d;">
    <?php foreach ($posizioni as $valore => $label) { ?>
        <option value="<?php echo $valore; ?>" <?php if($loaded_item['ubicabile'] == $valore) {echo 'selected';} ?>>
            <?php echo gdrcd_filter('out', $label); ?>
        </option>
    <?php } ?>
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
                        <select name="tipo_oggetto" id="tipo_oggetto" class="ares" style="background-color: #0f111d;">
<?php
$tipo_corrente = isset($_POST['tipo_oggetto']) ? $_POST['tipo_oggetto'] : null;

    $categorie_escluse = [];
    $categorie_permesse = [];

    switch ($tipo_oggetto_selezionato) {
        case 'standard':
            $categorie_escluse = ['Arma di Gilda', 'Armi', 'Droga', 'Magic Shop', 'Medicine', 'Mods', 'STRIKE', 'Secret Pandora'];
            break;
        case 'arma':
            $categorie_permesse = ['Arma di Gilda', 'Armi', 'S.T.R.I.K.E.', 'Secret Pandora'];
            break;
        case 'curativo':
            $categorie_permesse = ['Magic Shop', 'Medicine', 'S.T.R.I.K.E.', 'Secret Pandora'];
            break;
        case 'statistica':
            $categorie_escluse = ['Arma di Gilda', 'Armi', 'Medicine', 'Abitazioni'];
            break;
        case 'magico':
            $categorie_permesse = ['Magic Shop', 'Secret Pandora', 'Generici', 'Gioielli', 'Vestiti'];
            break;
    }

    while ($option = gdrcd_query($tipi_oggetto, 'fetch')) {
        $descrizione = gdrcd_filter('out', $option['descrizione']);
        $valore = $option['cod_tipo'];
        $selected = ($loaded_item['tipo'] == $valore) ? 'SELECTED' : '';

        // Controllo inclusione o esclusione
        $mostra = true;
        if (!empty($categorie_escluse) && in_array($descrizione, $categorie_escluse)) {
            $mostra = false;
        }
        if (!empty($categorie_permesse) && !in_array($descrizione, $categorie_permesse)) {
            $mostra = false;
        }

        if ($mostra) {
            echo "<option value=\"$valore\" $selected>$descrizione</option>";
        }
    }

    gdrcd_query($tipi_oggetto, 'free');
?>
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
                <?php for ($i = 0; $i <= 3; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" id="richiede_ricarica" value="1">
    
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
    
    <!-- Ricarica massima -->
    <tr class="second_header">
        <td><b>Cariche per ogni medicina</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <?php for ($i = 0; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Campo nascosto per richiede_ricarica -->
    <input type="hidden" name="richiede_ricarica" value="0">

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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
                <?php for ($i = 0; $i <= 10; $i++) { ?>
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
<?php if ($tipo_oggetto_selezionato === 'standard') { ?>
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
<?php if ($tipo_oggetto_selezionato === 'magico') { ?>
    <!-- Sezione per oggetti magici (RICARICABILI) -->

    <!-- Cariche iniziali -->
    <tr class="second_header">
        <td><b>Cariche iniziali</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Ricarica massima -->
    <tr class="second_header">
        <td><b>Ricarica massima di utilizzi</b></td>
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

    <input type="hidden" name="richiede_ricarica" value="1">
<?php } ?>
            <!-- Submit -->
            <tr>
                <td>
                    <input name="submit" type="submit" value="Aggiungi Oggetto" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
        </table>
    </form>
    
    
   <script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoOggettoSelect = document.getElementById('tipo_oggetto');
    const ricaricaMassimaSelect = document.querySelector('select[name="ricarica_massima"]');
    const richiedeRicaricaInput = document.getElementById('richiede_ricarica');

    function aggiornaRicaricaMassima() {
        const tipoSelezionato = tipoOggettoSelect.value;

        // Pulisce le opzioni
        ricaricaMassimaSelect.innerHTML = '';

        if (tipoSelezionato == '15') { // 15 = Arma di Gilda
            const optionIllimitato = document.createElement('option');
            optionIllimitato.value = 'illimitato';
            optionIllimitato.textContent = 'Illimitato';
            ricaricaMassimaSelect.appendChild(optionIllimitato);
        }

        // Sempre da 0 a 10
        for (let i = 0; i <= 10; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            ricaricaMassimaSelect.appendChild(option);
        }

        aggiornaRichiedeRicarica(); // Quando aggiorno ricarica_massima, aggiorno anche richiede_ricarica
    }

    function aggiornaRichiedeRicarica() {
        const tipoSelezionato = tipoOggettoSelect.value;
        const valoreRicarica = ricaricaMassimaSelect.value;

        if (tipoSelezionato == '15' && valoreRicarica == 'illimitato') {
            richiedeRicaricaInput.value = '0'; // Non richiede ricarica
        } else {
            richiedeRicaricaInput.value = '1'; // Richiede ricarica
        }
    }

    // Appena carica la pagina
    aggiornaRicaricaMassima();

    // Ogni volta che cambia il tipo o la ricarica_massima
    tipoOggettoSelect.addEventListener('change', aggiornaRicaricaMassima);
    ricaricaMassimaSelect.addEventListener('change', aggiornaRichiedeRicarica);
});
</script>
<?php } 

} 
?>
</div>
