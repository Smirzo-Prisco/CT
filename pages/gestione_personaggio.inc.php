<?php
/*
error_reporting(E_ALL);
ini_set('display_errors', '1');
*/

require_once(__DIR__ . '../../includes/custom_functions.inc.php');

$permessi_azioni = [
    'modifica' => ['admin', 'moderatore', 'master', 'custode'], // ,'moderatore','guida', 'grafico'
    'skill' => ['admin'],
    'cancella' => ['admin'],
    'esilia' => ['admin', 'moderatore'],
    'reset' => ['admin']
];

$permessi_sezioni = [
    'area_admin' => ['admin'],
    'anagrafica' => ['admin', 'moderatore'], // ,'moderatore','guida', 'grafico'
    'area_master' => ['admin', 'master'],
    'area_custodi' => ['admin', 'master', 'custode']
];

// filtri
$filtro = (isset($_POST['filtro']) ? $_POST['filtro'] : 'tutti');

switch($filtro) {
    case 'inattivi': $where = "AND DATE_ADD(ora_entrata, INTERVAL 150 DAY) <= NOW() AND (esilio < NOW() || esilio IS NULL)"; break;
    case 'mai_entrati': $where = "AND esperienza = '0.0000' AND (DATE(ora_entrata) = DATE(data_iscrizione))"; break;
    case 'esiliati': $where = "AND (esilio > NOW() AND esilio != '0000-00-00' AND esilio IS NOT NULL)"; break;
    default: $where = '';
}
// FINE filtri

// Paginazione (decommentare LIMIT nella query)
// $offset = isset($_REQUEST['offset']) && $_REQUEST['offset'] > 0 ? (int)$_REQUEST['offset'] : 1;
// $pagebegin = (int) gdrcd_filter('get', ($offset-1)) * $PARAMETERS['settings']['records_per_page'];
// Query
$personaggi = gdrcd_query("SELECT personaggio.*, razza.nome_razza, mestiere.id_mestiere, mestiere.nome AS nome_mestiere, gilda.nome AS nome_gilda FROM personaggio
                        LEFT JOIN razza ON personaggio.id_razza = razza.id_razza
                        LEFT JOIN mestiere ON personaggio.id_mestiere = mestiere.id_mestiere
                        LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda
                        WHERE 1=1
                        $where
                        ORDER BY nome ASC", 'result');
                        // LIMIT ".$pagebegin.", ".$PARAMETERS['settings']['records_per_page'].
// FINE query

// Recupero le razze per utilizzarle nella select del form di modifica personaggio
$listaRazze = gdrcd_query("SELECT * FROM razza ORDER BY nome_razza", 'result');

/* Controllo se sono un custode */
$beast = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

if($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $_SESSION['moderatore'] != 1 && $beast['id_gilda'] != 4) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {
    if(!isset($_POST['load_item'])) {
?>

<link rel="stylesheet" href="../themes/crystal/uffici_nuovi.css">

<!-- Top bar -->
<div class="topbar">
    <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
    <!-- Icona help che mostra la legenda sull'hover -->
    <div class="help-icon" title="Legenda azioni">
        <i class="fa-solid fa-circle-question"></i>
        <div class="legend-compact tooltip">
            <div><i class="fa-solid fa-pen-to-square"></i><span>Modifica</span></div>
            <div><i class="fa-solid fa-trash"></i><span>Cancella</span></div>
            <div><i class="fa-solid fa-user-xmark"></i><span>Esilia</span></div>
            <div><i class="fa-solid fa-wrench"></i><span>Skill</span></div>
            <div><i class="fa-solid fa-magic"></i><span>Reset punti</span></div>
        </div>
    </div>
    <!-- Filtri -->
    <form method="post" class="filter-form" action="main.php?page=gestione_personaggio">
        <select name="filtro" id="selectFiltroPg" class="form-select" onchange="this.form.submit()">
            <option value="tutti" <?=$filtro=='tutti'?'selected':''?>>Tutti</option>
            <option value="inattivi" <?=$filtro=='inattivi'?'selected':''?>>Inattivi da 5 mesi</option>
            <option value="mai_entrati" <?=$filtro=='mai_entrati'?'selected':''?>>Mai entrati</option>
            <option value="esiliati" <?=$filtro=='esiliati'?'selected':''?>>Esiliati</option>
        </select>
        <input type="text" class="searchField" id="searchPg" placeholder="Cerca personaggio...">
    </form>
    <!-- Bottone a destra -->
    <button class="btn-action right" title="Reset personaggi" onclick="resetPg([])"><i class="fa-solid fa-magic"></i> Tutti</button>
    <button class="btn-action right" title="Elimina esiliati" onclick="eliminaEsiliati()"><i class="fa-solid fa-trash"></i> Esiliati</button>
</div>

<!-- Lista personaggi -->
<div class="item-list">
    <div class="ct-table-responsive">
        <table id="pgTable">
            <thead>
                <tr>
                    <th>Avatar</th>    
                    <th>Nome</th>
                    <th>Razza</th>
                    <th>Esiliato</th>
                    <th>Gilda</th>
                    <th>Mestiere</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personaggi as $pg): ?>
                <tr>
                    <td width="5%" data-label="Avatar"><img width="25" height="25" src="<?=$pg['url_img_chat']?>" alt="Avatar"/></td>
                    <td data-label="Nome"><a href="main.php?page=scheda&pg=<?= htmlspecialchars($pg['nome']) ?>" class="link_sheet gender_'.$record['sesso'].'"><?= htmlspecialchars($pg['nome']) ?></a></td>
                    <td data-label="Razza"><?= htmlspecialchars($pg['nome_razza']) ?></td>
                    <td data-label="Esiliato"><?= $pg['esilio'] != null ? gdrcd_format_date($pg['esilio']) : '' ?></td>
                    <td data-label="Gilda"><?= htmlspecialchars($pg['nome_gilda']) ?></td>
                    <td data-label="Mestiere"><?= htmlspecialchars($pg['nome_mestiere']) ?></td>
                    <!-- Colonna Azioni -->
                    <td class="azioni" data-label="Azioni">
                        <div class="action-buttons">
                            <?php if (hasPermesso($_SESSION, $permessi_azioni['modifica'])): ?>
                                <button class="btn-action" title="Modifica" onclick="popolaPgForm('<?=$pg['nome']?>')"><i class="fa-solid fa-pen-to-square"></i></button>
                            <?php endif; ?>
                            <?php if (hasPermesso($_SESSION, $permessi_azioni['cancella']) && $_SESSION['login'] != $pg['nome']): ?>
                                <form action="main.php?page=erasepg_scelta" method="POST">
                                    <input type="hidden" name="op" value="delete">
                                    <input type="hidden" name="pg" value="<?= $pg['nome'] ?>">
                                    <button type="submit" class="btn-action" title="Elimina" onclick="return confirm('Sei sicuro di voler cancellare?');"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (hasPermesso($_SESSION, $permessi_azioni['esilia']) && $_SESSION['login'] != $pg['nome']): ?>
                                <form action="main.php?page=gestione_esilio" method="POST">
                                    <input type="hidden" name="load_pg" value="<?= $pg['nome'] ?>">
                                    <button type="submit" class="btn-action" title="Esilia" onclick="return confirm('Sei sicuro di voler esiliare?');"><i class="fa-solid fa-user-slash"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (hasPermesso($_SESSION, $permessi_azioni['skill'])): ?>
                                <form action="main.php?page=gst_personaggio_skill" method="POST">
                                    <input type="hidden" name="load_item" value="<?= $pg['nome'] ?>">
                                    <button type="submit" class="btn-action" title="Gestione skill"><i class="fa-solid fa-wrench"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (hasPermesso($_SESSION, $permessi_azioni['reset'])): ?>
                                <button class="btn-action" title="Reset pg" onclick='resetPg([{ nome: "<?= $pg['nome'] ?>" }])'><i class="fa-solid fa-magic"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- FINE lista personaggi -->

<!-- Paginazione --
<div class="pagination">
    <?php
    // Genera i link di paginazione
    // $totaleresults = gdrcd_query("SELECT COUNT(*) AS total FROM personaggio $where")['total'];
    // $total_pages = ceil($totaleresults / $PARAMETERS['settings']['records_per_page']);
    // echo generateLinks($offset, $total_pages, preg_replace('/&.*/', '', $_SERVER["REQUEST_URI"]));
    // echo "<p>Pagina $offset di $total_pages (Totale: $totaleresults record)</p>";
    ?>
</div>
<!-- FINE paginazione -->

<!-- Modale col form di modifica -->
<div class="pg-edit-container" id="pg_edit_container" role="dialog" aria-modal="true">
    <div class="modal-content">
        <span class="close" id="closePgModal">&times;</span>
        <h1>Modifica Personaggio</h1>
        <form id="formSavePg" method="post">
            <?php if (hasPermesso($_SESSION, $permessi_sezioni['anagrafica'])): ?>
            <div class="form-section">
                <h2 class="section-title">Anagrafica</h2>
                <div class="form-container">
                    <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_admin'])): ?>
                    <div class="form-row">
                        <div class="form-group form-column">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" value="" required>
                        </div>
                        <div class="form-group form-column">
                            <label for="razza">Razza</label>
                            <select id="razza" name="razza">
                                <option value="0">Nessuna</option>
                                <?php foreach($listaRazze as $razza): ?>
                                    <option value="<?= $razza['id_razza'] ?>">
                                        <?= htmlspecialchars($razza['nome_razza']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-column">
                            <label for="eta">Età</label>
                            <input type="number" id="eta" name="eta" value="">
                        </div>
                        <div class="form-group form-column">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group form-column">
                            <label for="cognome">Cognome</label>
                            <input type="text" id="cognome" name="cognome" value="">
                        </div>
                        <div class="form-group form-column">
                            <label for="luogo">Luogo di nascita</label>
                            <input type="text" id="luogo" name="luogo" value="">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="inline-group">
                            <label for="volto">Volto personaggio</label>
                            <div class="input-with-button">
                                <input type="text" id="volto" name="volto" value="">
                                <button type="button" onclick="checkVolto()">Controlla</button>
                            </div>
                        </div>
                        <div class="form-group form-column">
                            <label for="suoni">Disabilita i suoni</label>
                            <input type="checkbox" id="suoni" name="suoni" value="">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-column">
                            <label for="musica">Musica in scheda (URL)</label>
                            <input type="url" id="musica" name="musica" value="">
                        </div>
                        <div class="form-group form-column">
                            <label for="alias">Alias Tokyobook</label>
                            <input type="text" id="alias" name="alias" value="">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-column">
                            <label for="img">URL immagine</label>
                            <input type="url" id="img" name="img" value="">
                        </div>
                        <div class="form-group form-column">
                            <label for="imgchat">URL immagine di chat</label>
                            <input type="url" id="imgchat" name="imgchat" value="">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="background">Background</label>
                    <textarea id="background" name="background" value=""></textarea>
                </div>
                <div class="form-group">
                    <label for="storia">Storia</label>
                    <textarea id="storia" name="storia" value=""></textarea>
                </div>
                <div class="form-group">
                    <label for="dice">Dice di sé</label>
                    <textarea id="dice" name="dice" value=""></textarea>
                </div>
                <div class="form-group">
                    <label for="off">Off</label>
                    <textarea id="off" name="off" value=""></textarea>
                </div>
            </div>
            <?php endif; ?>
            <div class="form-section">
                <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_custodi'])): ?>
                <h2 class="section-title">Caratteristiche</h2>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="salute">Salute / 100</label>
                        <input type="number" id="salute" name="salute" value="">
                    </div>
                    <div class="form-group form-column">
                        <label for="integrita">Integrità / 10</label>
                        <input type="number" id="integrita" name="integrita" value="">
                    </div>
                </div>
                <?php endif; ?>
                <?php if (hasPermesso($_SESSION, $permessi_sezioni['area_master'])): ?>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="shin">Punti shin</label>
                        <input type="number" id="shin" name="shin" value="" disabled>
                    </div>
                    <div class="form-group form-column">
                        <label for="soldi">Soldi</label>
                        <input type="number" id="soldi" name="soldi" value="">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="notorieta">Notorietà</label>
                        <input type="number" id="notorieta" name="notorieta" value="">
                    </div>
                    <div class="form-group form-column">
                        
                    </div>
                </div>
                <div class="form-group">
                    <label for="note_master">Note del Master</label>
                    <textarea id="note_master" name="note_master" value=""></textarea>
                </div>
                <div class="form-group">
                    <label for="particolari">Particolari</label>
                    <textarea id="particolari" name="particolari" value=""></textarea>
                </div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="personaggio" id="personaggio" value="">
            <div class="actions"><button type="submit">Salva modifiche</button></div>
        </form>
    </div>
</div>
<!-- FINE modale col form di modifica -->


<!-- ***************************************    INIZIO VECCHIO CODICE   ****************************************************************** -->

<?php
    }
if(isset($_POST['load_item'])) $loaded_item = gdrcd_query("SELECT * FROM personaggio WHERE nome='".$_POST['load_item']."'");
if($_POST['op'] == 'update') {
    gdrcd_query("UPDATE personaggio SET note_fato='".gdrcd_filter('in', $_POST['note'])."', particolari='".gdrcd_filter('in', $_POST['particolari'])."', salute=".gdrcd_filter('num', $_POST['salute']).", integrita=".gdrcd_filter('num', $_POST['integrita']).", shin= ".gdrcd_filter('num', $_POST['shin']).", soldi = soldi + ". $_POST['soldi']." WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
    echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." modificato');</script>";
}

if($_POST['op'] == 'update_beast') {
    gdrcd_query("UPDATE personaggio SET salute=".gdrcd_filter('num', $_POST['salute'])." WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'");
    echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." modificato');</script>";

}//fine load 

if ($_SESSION['admin'] == 1) $elenco_oggetti = gdrcd_query("SELECT * FROM personaggio ORDER BY nome", 'result');
else if ($_SESSION['master'] == 1 || $beast['id_gilda'] == 4) $elenco_oggetti = gdrcd_query("SELECT * FROM personaggio WHERE nome != '". $_SESSION['login'] ."' ORDER BY nome", 'result');
?>

<div class="panels_box">
    <!-- Elenco dei personaggi --
    <div class="panels_box">
        <form class="form_gestione" action="main.php?page=gestione_personaggio" method="post">
            <div class='form_label'>Scegli personaggio</div>
            <div class='form_field'>
    <?php if(gdrcd_query($elenco_oggetti, 'num_rows') > 0) { ?>
                <select name="load_item">
            <?php
                while($option = gdrcd_query($elenco_oggetti, 'fetch')) { ?>
                    <option value="<?php echo $option['nome']; ?>">
                        <?php //echo gdrcd_filter('out', $option['nome']); ?>
                    </option>
                <?php }
                gdrcd_query($elenco_oggetti, 'free');
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
    -->

<?php if ($_POST['load_item'] != "") { ?>
             <!-- INIZIO CAMPI DI MODIFICA -->

    <br>
    <hr>
    <br>


           <form class="form_gestione" action="main.php?page=gestione_personaggio" method="post">
           <div class='form_label'>
                    <?php echo "Dettagli del personaggio <b>". $_POST['load_item'] ."</b>"; ?>
                </div>
                
                <!-- salute -->
                <div class='form_label'>
                    <?php echo "Salute"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="salute" style="width: 20px;" value="<?php echo $loaded_item['salute']; ?>" />/100
                </div>
                
                <?php 
                if($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) {
                ?>
                
                <div class='form_label'>
                    <?php echo "Integrità"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="integrita" style="width: 20px;" value="<?php echo $loaded_item['integrita']; ?>" />/10
                </div>
                
                <?php if ($_POST['load_item'] != $_SESSION['login']) { ?>
    <div class='form_label'>
        <?php echo "Punti Shin"; ?>
    </div>
    <div class='form_field'>
        <input type="text" name="shin" style="width: 20px;" value="<?php echo $loaded_item['shin']; ?>" />
    </div>
<?php } ?>
                
                <div class='form_label'>
                    <?php echo "Note del Fato"; ?>
                </div>
                <div class='form_field'>
                <textarea type="textbox" name="note"><?php echo $loaded_item['note_fato']; ?></textarea>                
                </div>
                
                <div class='form_label'>
                    <?php echo "Particolari"; ?>
                </div>
                <div class='form_field'>
                    <textarea type="textbox" name="particolari"><?php echo $loaded_item['particolari']; ?></textarea>
                </div>
                
                <div class='form_label'>
                    <?php echo "Soldi"; ?>
                </div>
                <div class='form_field'>
                    <input type="text" name="soldi" style="width: 20px;" value="0" />
                </div>
               
           <input type="hidden" name="op" value="update" />
           <input type="hidden" name="nome" value="<?php echo $loaded_item['nome']; ?>" />
           <div class='form_submit'>
           <input type="submit" name="modifica" value="Modifica" />     
           </div>
           
           <?php
            }
            if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1 && $beast['id_gilda'] == 4) {
            ?>
            <input type="hidden" name="op" value="update_beast" />
           <input type="hidden" name="nome" value="<?php echo $loaded_item['nome']; ?>" />
           <div class='form_submit'>
           <input type="submit" name="modifica" value="Modifica" />     
           </div>
           <?php } ?>
           </form>
           
           <?php
            }//fine permessi
            
    }//fine permessi
    ?>