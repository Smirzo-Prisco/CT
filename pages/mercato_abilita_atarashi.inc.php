<link rel="stylesheet" href="../themes/crystal/main.css">

<?php

//prendo le informazioni del personaggio
$info_pg = gdrcd_query("SELECT esperienza, esperienza_s, shin, car0, car1, car2, car3, car4, car5, car6, car7, car8, car9, punto_skill FROM personaggio WHERE nome='".gdrcd_filter('in', $_SESSION['login'])."'");
$esperienza_s = intval($info_pg['esperienza_s']);
$esperienza = intval($info_pg['esperienza']);

//valuto il massimale
$stats_gilda = $info_pg['car1'] + $info_pg['car3']+ $info_pg['car5'] + $info_pg['car7'] + $info_pg['car9'] + $info_pg['punto_skill'];
//se il massimalenon è stato superato:
if($stats_gilda < 240){


// Funzione per calcolare il costo di acquisto in base al tipo di abilità
function costoAcquisto($tipo) {
    switch ($tipo) {
        case 'Default':
        case 'Difensiva':
            return 0;
        case 'Attacco base':
        case 'Mentale base':
        case 'Generica base':
             return 1;
        case 'Attacco medio':
        case 'Mentale media':
        case 'Mentale di attacco':
        case 'Generica avanzata':
            return 3;
        case 'Attacco avanzato':
        case 'Mentale avanzata':
            return 5;
        case 'Potere speciale':
            return 50;
        default:
            return 0; // Ritorna 0 per i tipi non gestiti
    }
}

// Funzione per calcolare il costo di potenziamento in base al grado attuale
function costoPotenziamento($grado) {
    // Ogni potenziamento costa 1 shin
    return $grado > 0 ? 1 : 0;
}

if((isset($_POST['op']) === false) && (isset($_REQUEST['op']) === false)) {

?>

<div class="form_info">
    <div class='warning'>Il reset del personaggio avviene solo per errori tecnici, non per errori di distribuzione</div>
    <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['avalaible_skill_points']).': '.$esperienza_s; ?>
</div>

<div class="elenco_abilita">

    <!-- Elenco abilità gilda-->
    <div class="div_colonne_abilita_scheda">
    <table class="customTable">
    
    <?php
    // Inizializzazione degli array per le abilità di ogni tipo
    $abilita_per_tipo = array(
        'Default/Difensiva' => array(),
        'Generica base' => array(),
        'Generica avanzata' => array(),
        'Attacco base' => array(),
        'Attacco medio' => array(),
        'Attacco avanzato' => array(),
        'Mentale base' => array(),
        'Mentale media' => array(),
        'Mentale avanzata' => array(),
        'Mentale di attacco' => array(),
        'Potere speciale' => array()
    );

    // Caricamento dell'elenco delle abilità con i relativi costi
    $result = gdrcd_query("
    SELECT a.*, 
           CASE
               WHEN a.tipo IN ('Difensiva', 'Generica base', 'Potere speciale') THEN 1
               WHEN a.tipo IN ('Attacco base', 'Mentale base') THEN 2
               WHEN a.tipo IN ('Attacco medio', 'Mentale media', 'Mentale di attacco', 'Generica avanzata') THEN 3
               WHEN a.tipo IN ('Default', 'Attacco avanzato', 'Mentale avanzata') THEN 4
               ELSE NULL
           END AS max_lvl,
           pa.grado
    FROM abilita a
    LEFT JOIN clgpersonaggioabilita pa ON a.id_abilita = pa.id_abilita AND pa.nome = '". $_SESSION['login'] ."'
    WHERE a.id_gilda != 0 
      AND a.id_gilda = (SELECT id_gilda FROM personaggio WHERE nome = '". $_SESSION['login'] ."') 
    ORDER BY a.tipo, a.nome", 'result');


    // Assegnazione delle abilità ai rispettivi array associativi in base al tipo
    while ($row = gdrcd_query($result, 'fetch')) {
        $tipo = $row['tipo'];

        if ($tipo == 'Default' || $tipo == 'Difensiva') {
            $abilita_per_tipo['Default/Difensiva'][] = $row;
        } elseif ($tipo == 'Generica base' || $tipo == 'Generica avanzata') {
            $abilita_per_tipo['Generica'][] = $row;
        } elseif ($tipo == 'Attacco base' || $tipo == 'Attacco medio' || $tipo == 'Attacco avanzato') {
            $abilita_per_tipo['Attacco'][] = $row;
        } elseif ($tipo == 'Mentale base' || $tipo == 'Mentale media' || $tipo == 'Mentale avanzata' || $tipo == 'Mentale di attacco') {
            $abilita_per_tipo['Mentale'][] = $row;
        } elseif ($tipo == 'Potere speciale' && $esperienza >= 200) {
            $abilita_per_tipo['Potere speciale'][] = $row;
        }
    }

    // Stampa delle abilità organizzate per tipo
    foreach ($abilita_per_tipo as $tipo => $abilita) {
        if (!empty($abilita)) {
            echo '<tr class="second_header"><td colspan="3" style="text-transform: uppercase; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">' . $tipo . '</td></tr>';

            foreach ($abilita as $row) {
            
                            $id = gdrcd_filter('out', $row['id_abilita']);
                            $addr = "skill_desc.proc.php".$id;
                            $to = "changeFrame('skill_desc.proc.php?id=$id');document.getElementById('id01').style.display='block'";
                            $costo_acquisto = $row['costo_acquisto'];
                            $livello_massimo = $row['max_lvl'];
                            $grado_attuale = $row['grado'];
                            $costo_potenziamento = costoPotenziamento($costo_acquisto, $livello_massimo, $grado_attuale);
                            
                echo '<tr>';
                echo '<td width="40%"><a href="#" onClick="'. $to .'">' . gdrcd_filter('out', $row['nome']) . '</a></td>';
                echo '<td width="30%" style="color: #8f8f8f;">';
                
                // Controllo se la skill è già stata acquistata dal personaggio
                $acquistata = false; // Imposto la variabile di controllo a false di default
                // Query per verificare se la skill è stata acquistata dal personaggio
                $query_skill_acquistata = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE id_abilita = '" . $row['id_abilita'] . "' AND nome = '" . $_SESSION['login'] . "'", 'result');

                if (gdrcd_query($query_skill_acquistata, 'num_rows') > 0) {
                    $acquistata = true; // La skill è stata acquistata dal personaggio
                }

                // Se la skill è stata acquistata, determina se può essere potenziata o se è già al livello massimo
                if ($acquistata) {
                    // Visualizza il tipo di abilità e il livello attuale
                    echo 'Tipo: ' . gdrcd_filter('out', $row['tipo']) . ' <br>Livello attuale: ' . $row['grado'] . '/' . $row['max_lvl'] . '';
                } else {
                    // Visualizza solo il costo della skill
                    echo 'Tipo: ' . gdrcd_filter('out', $row['tipo']) . ' <br>Costo: ' . costoAcquisto($row['tipo']) .'';
                }

                echo '</td>';                
                echo '<td width="15%" style="color: #8f8f8f;">';
                ?>
                <form action="main.php?page=mercato_abilita_atarashi" method="post">

                    <?php
    // Controllo se la skill è già stata acquistata dal personaggio
    $acquistata = false; // Imposto la variabile di controllo a false di default
    // Query per verificare se la skill è stata acquistata dal personaggio
    $query_skill_acquistata = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE id_abilita = '" . $row['id_abilita'] . "' AND nome = '" . $_SESSION['login'] . "'", 'result');
    
    if (gdrcd_query($query_skill_acquistata, 'num_rows') > 0) {
        $acquistata = true; // La skill è stata acquistata dal personaggio
    }

    // Se la skill è stata acquistata, determina se può essere potenziata o se è già al livello massimo
    if ($acquistata) {
        if ($row['grado'] < $row['max_lvl'] AND $esperienza_s > 0) {
            // La skill può essere potenziata
            echo '<input type="submit" value="Potenzia"/>';
            echo '<input type ="hidden" value="'. $row['id_abilita'] .'" name="id_abilita"/>';
            echo '<input type ="hidden" value="1" name="costo"/>';
            echo '<input type="hidden" value="potenzia" name="op"/>';
            } else {
            // La skill è già al livello massimo
            echo '<span>Acquistata</span>';
        }
    } else {
        // La skill non è stata ancora acquistata dal personaggio
        $costo_acquisto = costoAcquisto($row['tipo']);
        if ($esperienza_s >= $costo_acquisto) {
        echo '<input type="submit" value="Acquista"/>';
        echo '<input type ="hidden" value="'. $row['id_abilita'] .'" name="id_abilita"/>';
        echo '<input type ="hidden" value="'. costoAcquisto($row['tipo']) .'" name="costo"/>';
        echo '<input type="hidden" value="acquista" name="op"/>';
        } else {
            // Il personaggio non ha abbastanza esperienza_s per acquistare questa skill
            echo '';
        }
    }
    
    ?>
                </form>
                <?php
                echo '</td>';
            }
        }
    }
    ?>
    </table>
</div>
<?php
          } else {
?>

<div class="form_info">
    <div class='warning'>Hai consumato tutti i punti shin disponibili per potenziarti</div>
</div>
<?php
          }
          
          
  }        
          
          
          
          
          
          
          //inizio l'acquisto
          
if (gdrcd_filter('get', $_POST['op']) == "acquista") {
$costo_acquisto_2 = $_POST['costo'];

$query_insert = "INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('". $_SESSION["login"]."', '". $_POST["id_abilita"] ."', '1')";
gdrcd_query($query_insert);

$degrade = gdrcd_query("UPDATE personaggio SET esperienza_s = esperienza_s - $costo_acquisto_2 WHERE nome = '". $_SESSION['login'] ."' LIMIT 1");

echo "<script type='text/javascript'>
        alert('Hai acquisitato la skill');
    </script>";
    
echo '<div class="warning"><a href="main.php?page=mercato_abilita_atarashi">Torna indietro</a></div>';

}

if (gdrcd_filter('get', $_POST['op']) == "potenzia") {
$query_power = gdrcd_query("UPDATE clgpersonaggioabilita SET grado = grado + 1 WHERE nome = '". $_SESSION["login"]."' AND id_abilita = ". $_POST["id_abilita"] ."");

$powerdegrade = gdrcd_query("UPDATE personaggio SET esperienza_s = esperienza_s - 1 WHERE nome = '". $_SESSION['login'] ."' LIMIT 1");

echo "<script type='text/javascript'>
        alert('Hai potenziato la skill');
    </script>";
    
echo '<div class="warning"><a href="main.php?page=mercato_abilita_atarashi">Torna indietro</a></div>';

}

?>