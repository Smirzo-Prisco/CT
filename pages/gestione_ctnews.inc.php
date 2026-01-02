<?php
// Controlla se l'utente è admin o master
if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    exit;
}

// Recupera i valori dell'enum "tipologia" dalla tabella `ctnews`
$query_enum = "SHOW COLUMNS FROM ctnews LIKE 'tipologia'";
$result_enum = gdrcd_query($query_enum, 'result');
$row_enum = gdrcd_query($result_enum, 'fetch');
$enum_values = str_getcsv(str_replace("'", "", substr($row_enum['Type'], 5, (strlen($row_enum['Type']) - 6))));

// Inserire una nuova notizia
if (gdrcd_filter('get', $_POST['op']) == 'imposta') {
    $query = "INSERT INTO ctnews (titolo, contenuto, data, tipologia, zona, autore) 
              VALUES ('".gdrcd_filter('in', $_POST['titolo'])."', '".gdrcd_filter('in', $_POST['contenuto'])."', NOW(), '".gdrcd_filter('in', $_POST['tipologia'])."', '".gdrcd_filter('in', $_POST['zona'])."', '".gdrcd_filter('in', $_SESSION['login'])."')";
    gdrcd_query($query);

    // Aggiorna `ctnews_letto` per tutti i personaggi
    $query_update = "UPDATE personaggio SET ctnews_letto = 0";
    gdrcd_query($query_update);

    echo "<script type='text/javascript'>alert('Notizia inserita con successo');</script>";
}

// Modificare una notizia esistente
if (gdrcd_filter('get', $_POST['op']) == 'modifica') {
    $news_id = gdrcd_filter('num', $_POST['news_id']);
    $query_update = "UPDATE ctnews SET titolo = '".gdrcd_filter('in', $_POST['titolo'])."', contenuto = '".gdrcd_filter('in', $_POST['contenuto'])."', tipologia = '".gdrcd_filter('in', $_POST['tipologia'])."', zona = '".gdrcd_filter('in', $_POST['zona'])."' WHERE id = $news_id";
    gdrcd_query($query_update);

    echo "<script type='text/javascript'>alert('Notizia modificata con successo');</script>";
}

// Paginazione
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Assicurati che la pagina sia almeno 1
if ($page < 1) {
    $page = 1;
}

$start = ($page - 1) * $limit;

// Aggiungi un controllo per evitare che $start sia negativo
if ($start < 0) {
    $start = 0;
}

// Query per contare tutte le notizie (per la paginazione)
if ($_SESSION['admin'] == 1) {
    $query_total = "SELECT COUNT(*) as total FROM ctnews WHERE autore != 'System' AND tipologia != 'archivio'";
} else if ($_SESSION['master'] == 1) {
    $query_total = "SELECT COUNT(*) as total FROM ctnews WHERE autore = '".gdrcd_filter('in', $_SESSION['login'])."' AND tipologia != 'archivio'";
}
$total_result = gdrcd_query($query_total, 'result');
$total_news = gdrcd_query($total_result, 'fetch')['total'];
$total_pages = ceil($total_news / $limit);

// Query per recuperare le notizie limitate per pagina
if ($_SESSION['admin'] == 1) {
    // Admin vede tutte le notizie tranne quelle di "System"
    $query_news = "SELECT * FROM ctnews WHERE autore != 'System' AND tipologia != 'archivio' ORDER BY data DESC LIMIT $start, $limit";
} else if ($_SESSION['master'] == 1) {
    // I master vedono solo le loro notizie
    $query_news = "SELECT * FROM ctnews WHERE autore = '".gdrcd_filter('in', $_SESSION['login'])."' AND tipologia != 'archivio' ORDER BY data DESC LIMIT $start, $limit";
}
$result_news = gdrcd_query($query_news, 'result');
?>

<div class="pagina_gestione_permessi">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['roles']['page_name']); ?></h2>
    </div>

    <!-- Corpo della pagina -->
    <div class="page_body">
        <!-- Form per inserire una nuova notizia -->
        <form class="form_gestione" action="main.php?page=gestione_ctnews" method="post">
            <div class='form_label'><?php echo "Imposta nuova notizia"; ?></div>

            <!-- Titolo della notizia -->
            <div class='form_label'><?php echo "Titolo"; ?></div>
            <div class='form_field'>
                <input placeholder="Max 50 caratteri" type="text" name="titolo" maxlength="50" required />
            </div>

            <!-- Contenuto della notizia -->
            <div class='form_label'><?php echo "Contenuto"; ?></div>
            <div class='form_field'>
                <textarea name="contenuto" placeholder="Max 500 caratteri" maxlength="500" required></textarea>
            </div>

            <!-- Tipologia della notizia -->
            <div class='form_label'><?php echo "Tipologia"; ?></div>
            <div class='form_field'>
                <select name="tipologia" required>
                    <?php
                    // Crea le opzioni per ogni valore ENUM, escludendo l'archivio
                    foreach ($enum_values as $value) {
                        if ($value !== 'archivio') {
                            echo '<option value="'.gdrcd_filter('out', $value).'">'.ucfirst($value).'</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Zona della notizia -->
            <div class='form_label'><?php echo "Zona"; ?></div>
            <div class='form_field'>
                <input placeholder="Inserisci la zona" type="text" name="zona" maxlength="100" required />
            </div>

            <!-- Operazione nascosta -->
            <input type="hidden" name="op" value="imposta" />

            <!-- Pulsante di invio -->
            <div class='form_submit'>
                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
            </div>
        </form>

        <!-- Registro delle notizie -->
<h3><?php echo "Registro delle notizie"; ?></h3>
<table>
    <thead>
        <tr>
            <th>Titolo</th>
            <th>Zona</th>
            <th>Data</th>
            <th>Opzioni</th>
        </tr>
    </thead>
    <tbody>
    <?php
    while ($news = gdrcd_query($result_news, 'fetch')) {
        echo '<tr>';
        echo '<td>' . gdrcd_filter('out', $news['titolo']) . '</td>';
        echo '<td>' . gdrcd_filter('out', $news['zona']) . '</td>';
        echo '<td>' . date('d F Y', strtotime($news['data'])) . '</td>';
        echo '<td style="display: none;">' . gdrcd_filter('out', $news['contenuto']) . '</td>';
        echo '<td style="display: none;">' . gdrcd_filter('out', $news['tipologia']) . '</td>';
        echo '<td>';
        echo '<button class="edit_news_button" data-id="' . $news['id'] . '">Modifica</button>';
        echo '</td>';
        echo '</tr>';
    }
    ?>
    </tbody>
</table>

<!-- Paginazione -->
<div class="pagination">
    <?php
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $page) {
            echo "<strong>$i</strong> ";
        } else {
            echo "<a href='main.php?page=gestione_ctnews&page=$i'>$i</a> ";
        }
    }
    ?>
</div>

<!-- Area per visualizzare/modificare la notizia selezionata -->
<div id="news_details" style="display:none;">
    <!-- Form di modifica della notizia -->
    <form id="edit_form" method="post" style="display:none;">
        <div class='form_label'><?php echo "Modifica notizia"; ?></div>
        <div class='form_field'>
            <input type="hidden" name="news_id" id="news_id" value="" />
        </div>

        <div class='form_label'><?php echo "Titolo"; ?></div>
        <div class='form_field'>
            <input type="text" name="titolo" id="edit_titolo" maxlength="50" required />
        </div>

        <div class='form_label'><?php echo "Zona"; ?></div>
        <div class='form_field'>
            <input type="text" name="zona" id="edit_zona" maxlength="100" required />
        </div>

        <div class='form_label'><?php echo "Contenuto"; ?></div>
        <div class='form_field'>
            <textarea name="contenuto" id="edit_contenuto" maxlength="500" required></textarea>
        </div>
        
        <div class='form_label'><?php echo "Tipologia"; ?></div>
    <div class='form_field'>
        <select name="tipologia" id="edit_tipologia" required>
            <?php
            // Crea le opzioni per ogni valore ENUM, escludendo l'archivio
            foreach ($enum_values as $value) {
                if ($value !== 'archivio') {
                    echo '<option value="'.gdrcd_filter('out', $value).'">'.ucfirst($value).'</option>';
                }
            }
            ?>
        </select>
    </div>

        <input type="hidden" name="op" value="modifica" />
        <div class='form_submit'>
            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
        </div>
    </form>
</div>
</div>
</div>

<script>
// Mostra il form di modifica per la notizia selezionata
document.querySelectorAll('.edit_news_button').forEach(button => {
    button.addEventListener('click', function() {
        const newsId = this.getAttribute('data-id');
        const row = this.closest('tr');
        const titolo = row.querySelector('td:nth-child(1)').innerText;
        const zona = row.querySelector('td:nth-child(2)').innerText;
        const contenuto = row.querySelector('td:nth-child(4)').innerText;
        const tipologia = row.querySelector('td:nth-child(5)').innerText; // Assumi che la tipologia sia nella quinta colonna

        // Popola i campi del form con i valori della notizia selezionata
        document.getElementById('news_id').value = newsId;
        document.getElementById('edit_titolo').value = titolo;
        document.getElementById('edit_zona').value = zona;
        document.getElementById('edit_contenuto').value = contenuto;

        // Imposta la tipologia corretta nel select
        document.getElementById('edit_tipologia').value = tipologia;

        // Mostra il form di modifica
        document.getElementById('news_details').style.display = 'block';
        document.getElementById('edit_form').style.display = 'block';
    });
});

</script>
