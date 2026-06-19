<div class="pagina_gestione_mercato">

    <form class="form_gestione" action="main.php?page=oggetto_ricarica" method="post">
        <table class="customTable">
            <tr>
                <td style="font-size: 12px; color: #a7a7a8;">
                    RICARICA OGGETTO SCADUTO
                </td>
            </tr>

            <!-- Seleziona Personaggio -->
            <tr class="second_header">
                <td><b>Seleziona Personaggio</b></td>
            </tr>
            <tr>
                <td>
                    <select name="pg_selezionato" class="ares" style="background-color: #0f111d;">
                        <?php
                        $login = $_SESSION['login'];
                        $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".$login."'");
                        
                        // Aggiungi sempre se stesso
                        echo "<option value='".$login."'>".$login." (Te Stesso)</option>";

                        // Admin: vede tutti i PG
                        if ($_SESSION['admin'] == 1) {
                            $pg_list = gdrcd_query("SELECT nome FROM personaggio WHERE nome != '".$login."' ORDER BY nome", 'result');
                        }
                        // Master: vede PG con oggetti creati da lui
                        elseif ($_SESSION['master'] == 1) {
                            $pg_list = gdrcd_query("
                                SELECT DISTINCT c.nome 
                                FROM clgpersonaggiooggetto c
                                JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                                WHERE o.creatore = '".$login."' 
                            ", 'result');
                        }
                        // Magic Shop
                        elseif ($mestiere['id_mestiere'] == 3) {
                            $pg_list = gdrcd_query("
                                SELECT DISTINCT c.nome 
                                FROM clgpersonaggiooggetto c
                                JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                                WHERE o.tipo = 8
                            ", 'result');
                        }
                        // Secret Pandora
                        elseif ($mestiere['id_mestiere'] == 4) {
                            $pg_list = gdrcd_query("
                                SELECT DISTINCT c.nome 
                                FROM clgpersonaggiooggetto c
                                JOIN oggetto o ON c.id_oggetto = o.id_oggetto
                                WHERE o.tipo = 9
                            ", 'result');
                        }

                        if (isset($pg_list)) {
                            while($pg = gdrcd_query($pg_list, 'fetch')) {
                                echo "<option value='".$pg['nome']."'>".$pg['nome']."</option>";
                            }
                            gdrcd_query($pg_list, 'free');
                        }
                        ?>
                    </select>
                </td>
            </tr>

            <!-- Submit per caricare gli oggetti del PG -->
            <tr>
                <td>
                    <input type="submit" name="seleziona_pg" value="Mostra Oggetti Scaduti" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
        </table>
    </form>

<?php
// STEP 2: Se il PG è stato selezionato, mostra gli oggetti scaduti ricaricabili
if(isset($_POST['seleziona_pg'])) {
    $pg_selezionato = gdrcd_filter('in', $_POST['pg_selezionato']);

    // Recupera oggetti scaduti ricaricabili
    $oggetti_scaduti = gdrcd_query("
        SELECT c.id_oggetto, o.nome, c.cariche, o.ricarica_massima
        FROM clgpersonaggiooggetto c
        JOIN oggetto o ON c.id_oggetto = o.id_oggetto
        WHERE c.nome = '".$pg_selezionato."'
          AND c.cariche = 0
          AND o.richiede_ricarica = 1
    ", 'result');

    if (gdrcd_query($oggetti_scaduti, 'num_rows') > 0) {
        echo '
        <form class="form_gestione" action="main.php?page=oggetto_ricarica" method="post">
            <input type="hidden" name="pg_selezionato" value="'.$pg_selezionato.'">
            <table class="customTable">
                <tr class="second_header">
                    <td><b>Seleziona Oggetto da Ricaricare</b></td>
                </tr>
                <tr>
                    <td>
                        <select name="id_oggetto" class="ares" style="background-color: #0f111d;">';
                        
                        while($obj = gdrcd_query($oggetti_scaduti, 'fetch')) {
                            echo "<option value='".$obj['id_oggetto']."'>".gdrcd_filter('out', $obj['nome'])." [Max: ".$obj['ricarica_massima']."]</option>";
                        }

        echo        '</select>
                    </td>
                </tr>
                <tr>
                    <td><input type="submit" name="ricarica" value="Ricarica Oggetto" class="ares" style="background-color: #0f111d;"></td>
                </tr>
            </table>
        </form>';
    } else {
        echo "<div class='error'>Nessun oggetto scaduto da ricaricare per questo personaggio.</div>";
    }

    gdrcd_query($oggetti_scaduti, 'free');
}
?>
<?php
if(isset($_POST['ricarica'])) {
    $pg_selezionato = gdrcd_filter('in', $_POST['pg_selezionato']);
    $id_oggetto = (int) $_POST['id_oggetto'];
    $login = $_SESSION['login'];
    $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome = '".$login."'");

    // Recupera dati oggetto
    $oggetto = gdrcd_query("SELECT nome, ricarica_massima FROM oggetto WHERE id_oggetto = ".$id_oggetto);

    $permesso = false;

    // Caso 1: Sta ricaricando se stesso (paga 100 monete)
    if ($pg_selezionato == $login) {
        // Scala 100 monete
        gdrcd_query("UPDATE personaggio SET soldi = soldi - 100 WHERE nome = '".$login."'");
        $permesso = true;
    }
    // Caso 2: Admin può sempre
    elseif ($_SESSION['admin'] == 1) {
        $permesso = true;
    }
    // Caso 3: Magic Shop
    elseif ($mestiere['id_mestiere'] == 3) {
        $check_azioni = gdrcd_query("
            SELECT COUNT(*) as azioni 
            FROM chat 
            WHERE mittente = '".$login."' 
              AND stanza = 24 
              AND tipo = 'P' 
              AND ora >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
        ");
        if ($check_azioni['azioni'] >= 2) {
            $permesso = true;
        }
    }
    // Caso 4: Secret Pandora
    elseif ($mestiere['id_mestiere'] == 4) {
        $check_azioni = gdrcd_query("
            SELECT COUNT(*) as azioni 
            FROM chat 
            WHERE mittente = '".$login."' 
              AND stanza = 14 
              AND tipo = 'P' 
              AND ora >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
        ");
        if ($check_azioni['azioni'] >= 2) {
            $permesso = true;
        }
    }
    // Caso 5: Master
    elseif ($_SESSION['master'] == 1) {
        $check_master = gdrcd_query("
            SELECT COUNT(*) as masterate 
            FROM chat 
            WHERE mittente = '".$login."' 
              AND tipo = 'M' 
              AND ora >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
        ");
        if ($check_master['masterate'] >= 2) {
            $permesso = true;
        }
    }

    if ($permesso) {
        // Aggiorna le cariche
        gdrcd_query("
            UPDATE clgpersonaggiooggetto 
            SET cariche = ".$oggetto['ricarica_massima']." 
            WHERE nome = '".$pg_selezionato."' 
              AND id_oggetto = ".$id_oggetto."
        ");

        echo "<div class='success'>Oggetto '".gdrcd_filter('out', $oggetto['nome'])."' ricaricato con successo per il personaggio ".$pg_selezionato."!</div>";

        // Inserisci Master Screen solo se NON è admin e NON è un'auto-ricarica
        if ($pg_selezionato != $login && $_SESSION['admin'] != 1) {
            $zona_pg = gdrcd_query("SELECT ultimo_luogo FROM personaggio WHERE nome = '".$login."'");
            $messaggio = "L'oggetto <b>".$oggetto['nome']."</b> di <b>".$pg_selezionato."</b> è stato ricaricato.";
            gdrcd_query("INSERT INTO chat (stanza, mittente, ora, tipo, testo) VALUES ('".$zona_pg['ultimo_luogo']."', 'Sistema', NOW(), 'M', '".$messaggio."')");
        }

    } else {
        echo "<div class='error'>Non hai i requisiti per ricaricare questo oggetto (azioni recenti insufficienti).</div>";
    }
}
?>

</div>
