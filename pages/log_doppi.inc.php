<?php
// Accesso solo ad admin e moderatori
if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo "<div class='error'>Accesso non autorizzato.</div>";
    exit;
}

// Variabili
$pg = isset($_GET['pg']) ? gdrcd_filter('in', $_GET['pg']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Conteggio totale
$where = $pg != '' ? "WHERE Nome = '$pg' OR Doppio = '$pg'" : '';
$count_query = gdrcd_query("SELECT COUNT(*) AS tot FROM log_doppi $where", 'result');
$total = gdrcd_query($count_query, 'fetch')['tot'];
gdrcd_query($count_query, 'free');
$pages = ceil($total / $limit);

// Query principale
$result = gdrcd_query("SELECT * FROM log_doppi $where ORDER BY DataEvento DESC LIMIT $limit OFFSET $offset", 'result');
?>

<style>
    .log_doppi_wrapper {
        font-family: 'Lato', sans-serif;
        background-color: #14172a;
        padding: 20px;
        color: #cecece;
    }

    .log_doppi_wrapper h2 {
        color: #ce846f;
        margin-bottom: 10px;
    }

    .log_doppi_form {
        margin-bottom: 20px;
    }

    .log_doppi_form input[type="text"] {
        padding: 5px;
        border: 1px solid #888;
        border-radius: 5px;
        background-color: #1e2238;
        color: #cecece;
    }

    .log_doppi_form input[type="submit"] {
        background-color: #ce846f;
        color: #14172a;
        border: none;
        padding: 6px 12px;
        margin-left: 5px;
        border-radius: 5px;
        cursor: pointer;
    }

    .log_doppi_table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .log_doppi_table th, .log_doppi_table td {
        border: 1px solid #333;
        padding: 8px;
        text-align: left;
        background-color: #1e2238;
    }

    .log_doppi_table th {
        background-color: #2b2f4a;
        color: #ce846f;
    }

    .log_doppi_pagination {
        margin-top: 10px;
        font-size: 14px;
    }

    .log_doppi_pagination a {
        color: #ce846f;
        margin: 0 8px;
        text-decoration: none;
    }

    .log_doppi_pagination span {
        color: #888;
    }
</style>

<div class="log_doppi_wrapper">
    <h2>Accessi sospetti - Log doppi</h2>

    <form class="log_doppi_form" method="get" action="main.php">
        <input type="hidden" name="page" value="log_doppi">
        <label for="pg">Cerca PG:</label>
        <input type="text" name="pg" id="pg" value="<?php echo htmlspecialchars($pg); ?>">
        <input type="submit" value="Cerca">
    </form>

    <table class="log_doppi_table">
        <tr>
            <th>PG</th>
            <th>Potenziale Doppio</th>
            <?php if ($_SESSION['admin'] == 1): ?>
                <th>IP</th>
                <th>Host</th>
            <?php endif; ?>
            <th>Browser</th>
            <th>Data</th>
        </tr>
        <?php while ($row = gdrcd_query($result, 'fetch')): ?>
            <tr>
                <td><?php echo $row['Nome']; ?></td>
                <td><?php echo $row['Doppio']; ?></td>
                <?php if ($_SESSION['admin'] == 1): ?>
                    <td><?php echo $row['IP']; ?></td>
                    <td><?php echo $row['Host']; ?></td>
                <?php endif; ?>
                <td style="max-width: 300px;"><?php echo htmlspecialchars($row['Browser']); ?></td>
                <td><?php echo $row['DataEvento']; ?></td>
            </tr>
        <?php endwhile; ?>
        <?php gdrcd_query($result, 'free'); ?>
    </table>

    <div class="log_doppi_pagination">
        <?php if ($page > 1): ?>
            <a href="main.php?page=log_doppi<?php echo $pg ? '&pg='.urlencode($pg) : ''; ?>&page=<?php echo $page - 1; ?>">« Precedente</a>
        <?php else: ?>
            <span>« Precedente</span>
        <?php endif; ?>

        <span>Pagina <?php echo $page; ?> di <?php echo $pages; ?></span>

        <?php if ($page < $pages): ?>
            <a href="main.php?page=log_doppi<?php echo $pg ? '&pg='.urlencode($pg) : ''; ?>&page=<?php echo $page + 1; ?>">Successiva »</a>
        <?php else: ?>
            <span>Successiva »</span>
        <?php endif; ?>
    </div>
</div>
