<div class="pagina_gestione_mercato">

    <form class="form_gestione" action="main.php?page=oggetto_mercato" method="post">
        <table class="customTable">
            <tr>
                <td style="font-size: 12px; color: #a7a7a8;">
                    CARICA OGGETTI NEL MERCATO
                </td>
            </tr>

            <!-- Seleziona Oggetti e Prezzo -->
            <tr class="second_header">
                <td>
                    <b>Oggetto e Prezzo</b>
                    <button type="button" onclick="aggiungiOggetto()" style="
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
                        margin-left: 10px;"
                        onmouseover="this.style.backgroundColor='#ce846f'; this.style.color='#0f111d';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.color='#ce846f';">+</button>
                </td>
            </tr>
            <tr>
                <td id="oggetti_container">
                    <div class="oggetto_row">
                        <select name="id_oggetto[]" class="ares" style="background-color: #0f111d; width: 60%;">
                            <?php
                            $mestiere = gdrcd_query("SELECT id_mestiere FROM personaggio WHERE nome ='".$_SESSION['login']."'");
                            
                            if ($_SESSION['admin'] == 1) {
                                $query = "SELECT id_oggetto, nome FROM oggetto ORDER BY nome";
                            } elseif ($mestiere['id_mestiere'] == 3) { // Magic Shop
                                $query = "SELECT id_oggetto, nome FROM oggetto WHERE tipo = 8 ORDER BY nome";
                            } else {
                                echo "<option value=''>Nessun oggetto disponibile</option>";
                                $query = null;
                            }

                            if ($query) {
                                $oggetti = gdrcd_query($query, 'result');
                                while($obj = gdrcd_query($oggetti, 'fetch')) {
                                    echo '<option value="'.$obj['id_oggetto'].'">'.gdrcd_filter('out', $obj['nome']).'</option>';
                                }
                                gdrcd_query($oggetti, 'free');
                            }
                            ?>
                        </select>
                        <input type="number" name="prezzo[]" placeholder="Prezzo" min="0" class="ares" style="background-color: #0f111d; width: 35%; margin-left:5px;">
                    </div>
                </td>
            </tr>

            <!-- Submit -->
            <tr>
                <td>
                    <input type="submit" name="submit" value="Carica nel Mercato" class="ares" style="background-color: #0f111d;">
                </td>
            </tr>
        </table>
    </form>

    <?php
    if(isset($_POST['submit'])) {
        $id_oggetti = $_POST['id_oggetto'];
        $prezzi = $_POST['prezzo'];

        // Controllo duplicati
        if(count($id_oggetti) !== count(array_unique($id_oggetti))) {
            echo "<div class='error'>Attenzione: Hai selezionato più volte lo stesso oggetto. Controlla la lista prima di confermare.</div>";
        } else {
            foreach($id_oggetti as $index => $id_ogg) {
                $id = (int) $id_ogg;
                $prezzo = (int) $prezzi[$index];

                // Aggiorna il prezzo
                gdrcd_query("UPDATE oggetto SET costo = ".$prezzo." WHERE id_oggetto = ".$id);

                // Verifica presenza nel mercato
                $exists = gdrcd_query("SELECT 1 FROM mercato WHERE id_oggetto = ".$id, 'result');

                if(gdrcd_query($exists, 'num_rows') > 0) {
                    gdrcd_query("UPDATE mercato SET numero = 9999 WHERE id_oggetto = ".$id);
                } else {
                    gdrcd_query("INSERT INTO mercato (id_oggetto, numero) VALUES (".$id.", 9999)");
                }

                gdrcd_query($exists, 'free');
            }

            echo "<div class='success'>Oggetti caricati nel mercato con successo!</div>";
        }
    }
    ?>

</div>

<!-- JavaScript per aggiungere nuovi oggetti -->
<script>
function aggiungiOggetto() {
    const container = document.getElementById('oggetti_container');
    const firstRow = container.querySelector('.oggetto_row');
    const newRow = firstRow.cloneNode(true);

    // Reset del campo prezzo nella nuova riga
    newRow.querySelector('input[name="prezzo[]"]').value = '';

    container.appendChild(document.createElement("br"));
    container.appendChild(newRow);
}
</script>
