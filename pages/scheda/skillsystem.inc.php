<script type="text/javascript"> 
  function showHideRow(row) { 
    $("#" + row).toggle(); 
  } 
</script> 

<?php
//carico le sole abilità del pg
$abilita = gdrcd_query("SELECT * FROM clgpersonaggioabilita WHERE nome='".gdrcd_filter('in', $_REQUEST['pg'])."'", 'result');
?>
<div class="elenco_abilita"><!-- Elenco abilità gilda-->
    <div class="titolo_box">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['sheet']['box_title']['guild_skills']); ?>
    </div>
    
    <div class="div_colonne_abilita_scheda">
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
        'Potere speciale' => array(),
        'Talento' => array(),
        'Skill temporanea' => array()
    );
    
    
    //carico l'elenco delle abilità
    
    // Caricamento dell'elenco delle abilità con i relativi costi
    $request_pg = $_REQUEST['pg'];
    $result = gdrcd_query("
    SELECT a.*, pa.grado
    FROM abilita a
    LEFT JOIN clgpersonaggioabilita pa ON a.id_abilita = pa.id_abilita
    WHERE pa.nome = '". $request_pg ."'
    ORDER BY
      CASE
        WHEN a.tipo IN ('Default') THEN 1
        WHEN a.tipo IN ('Difensiva') THEN 2
        WHEN a.tipo IN ('Generica base') THEN 3
        WHEN a.tipo IN ('Generica avanzata') THEN 4
        WHEN a.tipo IN ('Attacco base') THEN 5
        WHEN a.tipo IN ('Attacco medio') THEN 6
        WHEN a.tipo IN ('Attacco avanzato') THEN 7
        WHEN a.tipo IN ('Mentale base') THEN 8
        WHEN a.tipo IN ('Mentale media') THEN 9
        WHEN a.tipo IN ('Mentale avanzata') THEN 10
        WHEN a.tipo IN ('Mentale di attacco') THEN 11
        WHEN a.tipo IN ('Potere speciale') THEN 12
        WHEN a.tipo IN ('Skill temporanea') THEN 13
        WHEN a.tipo IN ('Talento') THEN 14
        ELSE 15
      END,
      a.nome
    ", 'result');
    
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
        } elseif ($tipo == 'Potere speciale') {
            $abilita_per_tipo['Potere speciale'][] = $row;
        } elseif ($tipo == 'Talento') {
            $abilita_per_tipo['Talento'][] = $row;
        } elseif ($tipo == 'Skill temporanea') {
            $abilita_per_tipo['Skill temporanee'][] = $row;
        }
    }
    
    // Ordine manuale delle categorie
$ordine_categorie = array(
    'Default/Difensiva',
    'Generica',
    'Attacco',
    'Mentale',
    'Potere speciale',
    'Talento',
    'Skill temporanea'
);

// Stampa delle abilità organizzate per tipo, seguendo l'ordine manuale
foreach ($ordine_categorie as $tipo) {
    if (!empty($abilita_per_tipo[$tipo])) {
        echo '<table class="customTable"><tr class="second_header"><td colspan="3" style="text-transform: uppercase; font-size: 13px; color: #9a6353 ; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">' . $tipo . '</td></tr>';
        foreach ($abilita_per_tipo[$tipo] as $row) {
            $id = gdrcd_filter('out', $row['id_abilita']);
            $to = "changeFrame('skill_desc.proc.php?id=$id');document.getElementById('id01').style.display='block'";
            echo '<tr>';
            echo '<td width="40%"><a href="#" onClick="'. $to .'">' . gdrcd_filter('out', $row['nome']) . '</a><br>';
            if ($tipo !== 'Talento') {
            echo 'Livello attuale: ' . $row['grado'] . '/' . $row['max_lvl'] . '';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';  
    }
}

    ?>
