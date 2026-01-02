<link rel="stylesheet" href="../themes/crystal/famiglie.css">

<div class="pagina_gestione_mercato">

<?php
    // Definizione query oggetti in base ai permessi
    if ($_SESSION['admin'] == 1) {
        // Admin vede tutto
        $query_oggetti = "SELECT id_oggetto, nome FROM oggetto ORDER BY nome";
    } elseif ($_SESSION['master'] == 1) {
        $query_oggetti = "SELECT id_oggetto, nome FROM oggetto WHERE creatore = '".$_SESSION['login']."'";
        
        // Recupero il mestiere del pg
        $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome ='".$_SESSION['login']."'");
        
        // Master vede solo oggetti creati da lui + Magic/Secret/ICC se fa parte del mestiere
        $tipi_extra = [];

        if ($mestiere['id_mestiere'] == 3) { $tipi_extra[] = 8; }   // Magic Shop
        if ($mestiere['id_mestiere'] == 4) { $tipi_extra[] = 9; }   // Secret Pandora
        if ($mestiere['id_mestiere'] == 1) { $tipi_extra[] = 10; }  // ICC

        if (!empty($tipi_extra)) $query_oggetti .= " OR tipo IN (".implode(',', $tipi_extra).")";
        
        $query_oggetti .= " ORDER BY nome";
    } else {
        
        // Recupero il mestiere del pg
        $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome ='".$_SESSION['login']."'");
        
        // Altri utenti: solo Magic, Secret o ICC
        switch ($mestiere['id_mestiere']) {
            case 3: $query_oggetti = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 8 ORDER BY nome"; break;
            case 4: $query_oggetti = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 9 ORDER BY nome"; break;
            case 1: $query_oggetti = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 10 ORDER BY nome"; break;
            default: echo "<div class='error'>Non hai i permessi per assegnare oggetti.</div>"; exit;
        }
    }
?>
    
    <form class="form_gestione" action="main.php?page=oggetto_assegna" method="post">
        <table class="customTable">
            <tr>
                <td style="font-size: 12px; color: #a7a7a8;">
                    ASSEGNA OGGETTO A PIÙ PERSONAGGI
                </td>
            </tr>

            <!-- Seleziona Oggetto -->
            <tr class="second_header">
                <td><b>Seleziona Oggetto</b></td>
            </tr>
            <tr>
                <td>
                    <select name="id_oggetto" class="ares" style="background-color: #0f111d;">
                        <?php
                        $oggetti = gdrcd_query($query_oggetti, 'result');
                        while($obj = gdrcd_query($oggetti, 'fetch')) {
                            echo '<option value="'.$obj['id_oggetto'].'">'.gdrcd_filter('out', $obj['nome']).'</option>';
                        }
                        gdrcd_query($oggetti, 'free');
                        ?>
                    </select>
                </td>
            </tr>

            <!-- Seleziona Personaggi -->
            <tr class="second_header">
                <td><b>Seleziona Personaggi</b> <button type="button" onclick="aggiungiSelect()" style="
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 2px solid #ce846f;
                    background-color: transparent;
                    color: #ce846f;
                    font-size: 18px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    margin-left: 10px;
                    "
                    onmouseover="this.style.backgroundColor='#ce846f'; this.style.color='#0f111d';"
                    onmouseout="this.style.backgroundColor='transparent'; this.style.color='#ce846f';">+</button></td>
            </tr>
            <tr>
                <td id="select_pg_container">
                    <select name="personaggi[]" class="ares" style="background-color: #0f111d;">
                        <?php
                        $pg_list = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome", 'result');
                        while($pg = gdrcd_query($pg_list, 'fetch')) {
                            echo '<option value="'.$pg['nome'].'">'.gdrcd_filter('out', $pg['nome']).'</option>';
                        }
                        gdrcd_query($pg_list, 'free');
                        ?>
                    </select>
                </td>
            </tr>

            <!-- Numero Oggetti -->
            <tr class="second_header"><td><b>Numero di Oggetti da Assegnare</b></td></tr>
            <tr><td><input type="number" name="num_oggetti" value="1" min="1" class="ares" style="background-color: #0f111d;"></td></tr>

            <!-- Submit -->
            <tr><td><input type="submit" name="submit" value="Assegna Oggetto" class="ares" style="background-color: #0f111d;"></td></tr>
        </table>
    </form>

    <?php
    if(isset($_POST['submit'])) {
        $id_oggetto = (int) $_POST['id_oggetto'];
        $num_oggetti = (int) $_POST['num_oggetti'];
        $personaggi = $_POST['personaggi'];

        // Recupera dati oggetto
        $oggetto = gdrcd_query("SELECT tipo, categoria, cariche, isTemp, temp_giorni, ricarica_massima FROM oggetto WHERE id_oggetto = ".$id_oggetto."");

        switch($oggetto['categoria']) {
            case 'arma':
                if ($oggetto['tipo'] == 15) { // 15 = Arma di Gilda
                    $cariche = -1; // Cariche infinite
                } else {
                    $cariche = $oggetto['ricarica_massima']; // Armi normali: usa ricarica_massima
                }
                break;
            case 'statistica':
                $cariche = (int) $oggetto['ricarica_massima'];
                break;
            case 'curativo':
            case 'magico':
                $cariche = (int) $oggetto['cariche'];  // Sempre numero tra 1 e 10
                break;
            case 'standard':
                $cariche = ($oggetto['cariche'] == 'illimitato') ? -1 : (int) $oggetto['cariche'];
                break;
            default:
                $cariche = 0;  // Fallback di sicurezza in caso di errore
        }

        $isTemp = $oggetto['isTemp'];
        $temp_giorni = $oggetto['temp_giorni'];

        foreach($personaggi as $pg) {
            $pg = gdrcd_filter('in', $pg);

            // Controllo se il PG ha già l'oggetto
            $check = gdrcd_query("SELECT id_oggetto FROM clgpersonaggiooggetto WHERE id_oggetto = ".$id_oggetto." AND nome = '".$pg."'", 'result');

            if(gdrcd_query($check, 'num_rows') > 0) {
                // Controlla lo stato delle cariche attuali
                $dati_pg_oggetto = gdrcd_query("SELECT cariche FROM clgpersonaggiooggetto WHERE id_oggetto = ".$id_oggetto." AND nome = '".$pg."'");

                if ($dati_pg_oggetto['cariche'] == 0) {
                    // Se l'oggetto è scarico, ricaricalo
                    gdrcd_query("UPDATE clgpersonaggiooggetto 
                                SET cariche = ".$cariche." 
                                WHERE id_oggetto = ".$id_oggetto." AND nome = '".$pg."'");
                } 
                // Altrimenti NON aggiungo copie e NON faccio nulla
            } else {
                // Inserisci nuovo oggetto
                gdrcd_query("INSERT INTO clgpersonaggiooggetto (nome, id_oggetto, cariche, numero, isTemp, temp_giorni) 
                            VALUES ('".$pg."', ".$id_oggetto.", ".$cariche.", ".$num_oggetti.", ".$isTemp.", ".$temp_giorni.")");
            }
            gdrcd_query($check, 'free');
        }
        echo "<div class='success'>Oggetto assegnato a ".count($personaggi)." personaggi con successo!</div>";
    }
    ?>

</div>

<!-- JavaScript per aggiungere Select -->
<script>
function aggiungiSelect() {
    const container = document.getElementById('select_pg_container');
    const selectList = container.querySelector('select');
    const newSelect = selectList.cloneNode(true);
    container.appendChild(document.createElement("br"));
    container.appendChild(newSelect);
}
</script>
