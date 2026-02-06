<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<div class="pagina_gestione_mercato">

<?php
$mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".$_SESSION['login']."'");

// STEP 1: Selezione Oggetto da Modificare
if (!isset($_POST['seleziona']) && !isset($_POST['modifica'])) {

    echo '<form class="form_gestione" action="main.php?page=oggetto_modifica" method="post">
        <table class="customTable">
            <tr><td style="font-size: 12px; color: #a7a7a8;">SELEZIONA OGGETTO DA MODIFICARE</td></tr>
            <tr class="second_header"><td><b>Oggetto</b></td></tr>
            <tr><td><select name="id_oggetto" class="ares" style="background-color: #0f111d;">';

    // QUERY in base ai permessi
    if ($_SESSION['admin'] == 1) {
        $query = "SELECT id_oggetto, nome FROM oggetto ORDER BY nome";
    } elseif ($_SESSION['master'] == 1) {
        $query = "SELECT id_oggetto, nome FROM oggetto WHERE creatore = '".$_SESSION['login']."' 
                  OR tipo IN (8,9,10)";
    } elseif ($mestiere['id_mestiere'] == 3) {
        $query = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 8 ORDER BY nome";
    } elseif ($mestiere['id_mestiere'] == 4) {
        $query = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 9 ORDER BY nome";
    } elseif ($mestiere['id_mestiere'] == 1) {
        $query = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 10 ORDER BY nome";
    } else {
        $query = null;
    }

    if ($query) {
        $oggetti = gdrcd_query($query, 'result');

        while($obj = gdrcd_query($oggetti, 'fetch')) {
            echo '<option value="'.$obj['id_oggetto'].'">'.gdrcd_filter('out', $obj['nome']).'</option>';
        }
        gdrcd_query($oggetti, 'free');
    }

    echo '</select></td></tr>
            <tr><td><input type="submit" name="seleziona" value="Modifica" class="ares" style="background-color: #0f111d;"></td></tr>
        </table>
    </form>';

}

// STEP 2: Mostra il form con i dati precompilati
elseif (isset($_POST['seleziona'])) {
    $id_oggetto = (int) $_POST['id_oggetto'];
    $oggetto = gdrcd_query("SELECT * FROM oggetto WHERE id_oggetto = ".$id_oggetto);
    ?>

    <form class="form_gestione" action="main.php?page=oggetto_modifica" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id_oggetto" value="<?php echo $id_oggetto; ?>">
        <table class="customTable">
            <tr><td style="font-size: 12px; color: #a7a7a8;">MODIFICA OGGETTO</td></tr>

            <!-- Nome -->
            <tr class="second_header"><td><b>Nome Oggetto</b></td></tr>
            <tr><td><input type="text" name="nome" value="<?php echo gdrcd_filter('out', $oggetto['nome']); ?>" class="ares" style="background-color: #0f111d;"></td></tr>

            <!-- Descrizione -->
            <tr class="second_header"><td><b>Descrizione</b></td></tr>
            <tr><td><textarea name="descrizione" rows="5" class="ares" style="background-color: #0f111d;"><?php echo gdrcd_filter('out', $oggetto['descrizione']); ?></textarea></td></tr>

            <!-- Immagine -->
            <tr class="second_header"><td><b>Immagine (carica solo se vuoi cambiarla)</b></td></tr>
            <tr><td><input type="file" name="img_oggetto"></td></tr>

            <!-- Posizionabile in -->
            <tr class="second_header"><td><b>Posizionabile in</b></td></tr>
            <tr><td>
                <select name="ubicabile" class="ares" style="background-color: #0f111d;">
                    <?php
                    $posizioni = [
                        0 => 'Non trasportabile', 1 => 'Equipaggia', 2 => 'Mano Dx', 3 => 'Mano Sx',
                        4 => 'Torso', 5 => 'Gambe', 6 => 'Piedi', 7 => 'Testa',
                        8 => 'Anello', 9 => 'Collo'
                    ];
                    foreach ($posizioni as $val => $label) {
                        $sel = ($oggetto['ubicabile'] == $val) ? 'selected' : '';
                        echo "<option value='$val' $sel>$label</option>";
                    }
                    ?>
                </select>
            </td></tr>

            <!-- Tipo Oggetto -->
<tr class="second_header"><td><b>Tipo Oggetto</b></td></tr>
<tr><td>
    <select name="tipo" class="ares" style="background-color: #0f111d;">
        <?php
        $tipi = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione", 'result');

        // Applichiamo lo stesso filtro usato nel caricamento
        $categorie_escluse = [];
        $categorie_permesse = [];

        switch ($oggetto['categoria']) {
            case 'standard':
                $categorie_escluse = ['Arma di Gilda', 'Armi', 'Droga', 'Magic Shop', 'Medicine', 'Mods', 'STRIKE', 'Secret Pandora'];
                break;
            case 'arma':
                $categorie_permesse = ['Arma di Gilda', 'Armi', 'STRIKE', 'Secret Pandora'];
                break;
            case 'curativo':
                $categorie_permesse = ['Magic Shop', 'Medicine', 'STRIKE', 'Secret Pandora'];
                break;
            case 'statistica':
                $categorie_permesse = ['Droga', 'Magic Shop', 'Mods', 'STRIKE', 'Secret Pandora'];
                break;
            case 'magico':
                $categorie_permesse = ['Magic Shop', 'Secret Pandora', 'Generico', 'Gioielli'];
                break;
        }

        while($t = gdrcd_query($tipi, 'fetch')) {
            $descrizione = gdrcd_filter('out', $t['descrizione']);
            $valore = $t['cod_tipo'];
            $selected = ($oggetto['tipo'] == $valore) ? 'selected' : '';

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
        gdrcd_query($tipi, 'free');
        ?>
    </select>
</td></tr>

<?php if ($oggetto['categoria'] === 'arma') { ?>
    <!-- Sezione per le Armi -->
    <!-- Tipo di arma -->
    <tr class="second_header">
        <td><b>Tipo di arma</b></td>
    </tr>
    <tr>
        <td>
            <select name="tipo_arma" class="ares">
                <option value="1" <?php if($oggetto['tipo_arma'] == 1) echo 'selected'; ?>>Arma bianca</option>
                <option value="2" <?php if($oggetto['tipo_arma'] == 2) echo 'selected'; ?>>Arma da lancio</option>
                <option value="3" <?php if($oggetto['tipo_arma'] == 3) echo 'selected'; ?>>Arma da fuoco</option>
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
                    <option value="<?php echo $i; ?>" <?php if($oggetto['attacco'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
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
                    <option value="<?php echo $i; ?>" <?php if($oggetto['ricarica_massima'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
<?php } ?>


<?php if ($oggetto['categoria'] === 'curativo') { ?>
    <!-- Quanta salute integra -->
    <tr class="second_header">
        <td><b>Quanta salute integra</b></td>
    </tr>
    <tr>
        <td>
            <select name="salute_integra" class="ares">
                <?php for ($i = 0; $i <= 20; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['heal'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
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
                    <option value="<?php echo $i; ?>" <?php if($oggetto['bonus_car0'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
    
    <!-- Cariche iniziali -->
    <tr class="second_header">
        <td><b>Cariche iniziali</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['cariche'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
<?php } ?>


<?php if ($oggetto['categoria'] === 'statistica') { ?>
    <!-- Durata (in giorni) -->
    <tr class="second_header">
        <td><b>Durata (in giorni)</b></td>
    </tr>
    <tr>
        <td>
            <select name="temp_giorni" class="ares">
                <?php for ($i = 1; $i <= 30; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['temp_giorni'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <!-- Bonus caratteristiche -->
    <?php 
    $bonus_extra = [
        'bonus_car1_extra' => 'Forza',
        'bonus_car2_extra' => 'Destrezza',
        'bonus_car3_extra' => 'Mente',
        'bonus_car4_extra' => 'Tempra',
        'bonus_car5_extra' => 'Potere'
    ];

    foreach ($bonus_extra as $campo => $nome_stat) { ?>
        <tr class="second_header">
            <td><b>Bonus <?php echo $nome_stat; ?></b></td>
        </tr>
        <tr>
            <td>
                <select name="<?php echo $campo; ?>" class="ares">
                    <?php for ($i = -10; $i <= 10; $i++) { ?>
                       <option value="<?php echo $i; ?>" <?php if($oggetto[$campo] == $i) echo 'selected'; ?>>
                           <?php echo ($i > 0 ? '+' : '') . $i; ?>
                       </option>
                   <?php } ?>
                </select>
            </td>
        </tr>
    <?php } ?>
    
    <!-- Cariche iniziali -->
    <tr class="second_header">
        <td><b>Cariche iniziali</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['cariche'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
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
                    <option value="<?php echo $i; ?>" <?php if($oggetto['ricarica_massima'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
<?php } ?>


<?php if ($oggetto['categoria'] === 'magico') { ?>
    <!-- Cariche iniziali -->
    <tr class="second_header">
        <td><b>Cariche iniziali</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['cariche'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
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
                    <option value="<?php echo $i; ?>" <?php if($oggetto['ricarica_massima'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
<?php } ?>


<?php if ($oggetto['categoria'] === 'standard') { ?>
    <!-- Utilizzo (non ricaricabile) -->
    <tr class="second_header">
        <td><b>Utilizzo</b></td>
    </tr>
    <tr>
        <td>
            <select name="cariche" class="ares">
                <option value="illimitato" <?php if($oggetto['cariche'] == 'illimitato') echo 'selected'; ?>>Illimitato</option>
                <?php for ($i = 1; $i <= 10; $i++) { ?>
                    <option value="<?php echo $i; ?>" <?php if($oggetto['cariche'] == $i) echo 'selected'; ?>><?php echo $i; ?></option>
                <?php } ?>
            </select>
        </td>
    </tr>
<?php } ?>

            <tr><td><input type="submit" name="modifica" value="Salva Modifiche" class="ares" style="background-color: #0f111d;"></td></tr>
        </table>
    </form>

    <?php
}

// STEP 3: Salvataggio modifiche
elseif (isset($_POST['modifica'])) {
    $id = (int) $_POST['id_oggetto'];
    $nome = gdrcd_filter('in', $_POST['nome']);
    $descrizione = gdrcd_filter('in', $_POST['descrizione']);
    $ubicabile = (int) $_POST['ubicabile'];
    $tipo = (int) $_POST['tipo'];

    // Gestione immagine solo se caricata
    if (!empty($_FILES['img_oggetto']['tmp_name'])) {
        $img_tmp = $_FILES['img_oggetto']['tmp_name'];
        $img_ext = strtolower(pathinfo($_FILES['img_oggetto']['name'], PATHINFO_EXTENSION));
        $target_file = "imgs/items/oggetto_".uniqid().".".$img_ext;
        move_uploaded_file($img_tmp, $target_file);
        $img_sql = ", urlimg = '".basename($target_file)."'";
    } else {
        $img_sql = "";
    }

   // Recuperiamo la categoria attuale dell'oggetto
$oggetto = gdrcd_query("SELECT categoria FROM oggetto WHERE id_oggetto = $id");
$categoria = $oggetto['categoria'];

// Inizio query base
$update = "UPDATE oggetto SET nome = '$nome', descrizione = '$descrizione', ubicabile = $ubicabile, tipo = $tipo $img_sql";

// Gestione campi dinamici
if ($categoria === 'arma') {
    $tipo_arma = (int) $_POST['tipo_arma'];
    $bonus_arma = (int) $_POST['bonus_arma'];
    $cariche = (int) $_POST['cariche'];
    $ricarica_massima = (int) $_POST['ricarica_massima'];
    $update .= ", tipo_arma = $tipo_arma, attacco = $bonus_arma, ricarica_massima = $ricarica_massima, richiede_ricarica = 1";
}

if ($categoria === 'curativo') {
    $heal = (int) $_POST['salute_integra'];
    $integrita = (int) $_POST['integrita_integra'];
    $ricarica_massima = (int) $_POST['ricarica_massima'];
    $update .= ", heal = $heal, bonus_car0 = $integrita, cariche = 1, cariche = $cariche, ricarica_massima = $ricarica_massima, richiede_ricarica = 0";
}

if ($categoria === 'statistica') {
    $temp_giorni = (int) $_POST['temp_giorni'];
    $bonus1 = (int) $_POST['bonus_car1_extra'];
    $bonus2 = (int) $_POST['bonus_car2_extra'];
    $bonus3 = (int) $_POST['bonus_car3_extra'];
    $bonus4 = (int) $_POST['bonus_car4_extra'];
    $bonus5 = (int) $_POST['bonus_car5_extra'];
    $cariche = (int) $_POST['cariche'];
    $ricarica_massima = (int) $_POST['ricarica_massima'];
    $update .= ", temp_giorni = $temp_giorni, isTemp = 1, cariche = $cariche, ricarica_massima = $ricarica_massima, richiede_ricarica = 1,
                 bonus_car1_extra = $bonus1, bonus_car2_extra = $bonus2, bonus_car3_extra = $bonus3,
                 bonus_car4_extra = $bonus4, bonus_car5_extra = $bonus5";
}

if ($categoria === 'magico') {
    $cariche = (int) $_POST['cariche'];
    $ricarica_massima = (int) $_POST['ricarica_massima'];
    $update .= ", cariche = $cariche, ricarica_massima = $ricarica_massima, richiede_ricarica = 1";
}

if ($categoria === 'standard') {
    $cariche = ($_POST['cariche'] === 'illimitato') ? "'illimitato'" : (int) $_POST['cariche'];
    $update .= ", cariche = $cariche, richiede_ricarica = 0";
}

// Completa la query
$update .= " WHERE id_oggetto = $id";

// Esegui
gdrcd_query($update);


    echo "<div class='success'>Modifiche salvate con successo!</div>";
}
?>

</div>
