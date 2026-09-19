<?php
// Permessi
if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    die();
}

require_once(__DIR__ . '/../includes/custom_functions.inc.php');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chatbot';

// Tab "accessi": filtro per giorno, default oggi — la tabella non aveva alcun
// filtro e scaricava tutta la cronologia di log_entrate (11.000+ righe da
// quando è stata rimossa la deduplica), lenta da caricare e poco utile per
// capire chi ha fatto accesso in una giornata specifica. "tutti" = storico
// completo, con un limite di sicurezza sulle righe mostrate.
if ($currentTab === 'accessi') {
    $filtroDataAccessi = $_GET['data'] ?? date('Y-m-d');
    if ($filtroDataAccessi !== 'tutti' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroDataAccessi)) {
        $filtroDataAccessi = date('Y-m-d');
    }
    $whereDataAccessi = $filtroDataAccessi === 'tutti'
        ? ''
        : "WHERE DATE(DataEvento) = '" . gdrcd_filter('in', $filtroDataAccessi) . "'";
    $riepilogoAccessi = gdrcd_query("SELECT COUNT(*) AS totali, COUNT(DISTINCT Nome) AS distinti FROM log_entrate $whereDataAccessi");
}

// Stima quanto sia plausibile che un doppio segnalato sia davvero lo stesso giocatore:
// più personaggi DISTINTI sono mai passati da quell'IP (in tutta la storia di
// log_entrate, senza limite di tempo), più è probabile che sia una connessione
// condivisa/dinamica (rete mobile, CGNAT, wifi pubblico) invece di una vera
// coincidenza — un IP usato solo da questi due personaggi resta un segnale forte
// anche a distanza di mesi o anni, che un taglio temporale perderebbe.
function classificaProbabilitaDoppio($n) {
    if ($n <= 2)     return 'Alta';
    elseif ($n <= 5) return 'Media';
    else             return 'Bassa';
}
?>

<div class="log-container">
    <div class="gp-topbar">
        <div class="gp-topbar__left">
            <button type="button" class="gp-back" title="Indietro" onclick="history.back()">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        </div>
        <div class="gp-topbar__center">
            <h1 class="gp-title">Logs</h1>
        </div>
    </div>
    <!-- TABS -->
    <div class="tabs">
        <div class="tab <?= $currentTab == 'chatbot' ? 'active' : '' ?>" onclick="changeTab('chatbot')">Domande chatbot</div>
        <div class="tab <?= $currentTab == 'sms' ? 'active' : '' ?>" onclick="changeTab('sms')">SMS</div>
        <div class="tab <?= $currentTab == 'doppi' ? 'active' : '' ?>" onclick="changeTab('doppi')">Doppi</div>
        <div class="tab <?= $currentTab == 'punti' ? 'active' : '' ?>" onclick="changeTab('punti')">Limite punti</div>
        <div class="tab <?= $currentTab == 'accessi' ? 'active' : '' ?>" onclick="changeTab('accessi')">Accessi</div>
        <div class="tab <?= $currentTab == 'generali' ? 'active' : '' ?>" onclick="changeTab('generali')">Generali</div>
    </div>
    <!-- FILTRI -->
    <div class="filters">
    <?php if ($currentTab == 'chatbot'): ?>
    <?php elseif ($currentTab == 'generali'): ?>
        <select onchange="window.location.href = 'main.php?page=log&tab=<?=$currentTab?>&generaliType=' + this.value;">
            <?php
            foreach ($MESSAGE['event'] as $key => $event) { ?>
                <option value="<?=$key?>" <?=$_GET['generaliType']==$key?'selected':''?>><?=$event?></option>
            <?php } ?>
        </select>
    <?php elseif ($currentTab == 'sms'):
        $query_pg = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome ASC", 'result'); ?>
        <select onchange="window.location.href = 'main.php?page=log&tab=<?=$currentTab?>&pg=' + this.value;">
            <option value="">Personaggio</option>
            <?php while ($pg = gdrcd_query($query_pg, 'fetch')) { ?>
                <option value="<?=htmlspecialchars($pg['nome'])?>" <?=$_GET['pg']==$pg['nome']?'selected':''?>><?=htmlspecialchars($pg['nome'])?></option>
            <?php } ?>
        </select>
    <?php elseif ($currentTab == 'doppi'):
        $query_pg = gdrcd_query("SELECT nome FROM personaggio ORDER BY nome ASC", 'result'); ?>
        <select onchange="window.location.href = 'main.php?page=log&tab=<?=$currentTab?>&pg=' + this.value;">
            <option value="">Personaggio</option>
            <?php while ($pg = gdrcd_query($query_pg, 'fetch')) { ?>
                <option value="<?=htmlspecialchars($pg['nome'])?>" <?=$_GET['pg']==$pg['nome']?'selected':''?>><?=htmlspecialchars($pg['nome'])?></option>
            <?php } ?>
        </select>
    <?php elseif ($currentTab == 'accessi'): ?>
        <input type="date"
               value="<?= $filtroDataAccessi === 'tutti' ? '' : gdrcd_filter('out', $filtroDataAccessi) ?>"
               max="<?= date('Y-m-d') ?>"
               onchange="window.location.href = 'main.php?page=log&tab=accessi&data=' + this.value;">
        <a href="main.php?page=log&tab=accessi&data=tutti" class="tab<?= $filtroDataAccessi === 'tutti' ? ' active' : '' ?>">Tutti i giorni</a>
    <?php elseif ($currentTab == 'punti'): ?>
        <b>Limiti:</b> xp 260, shin 240, totale 550</li>
        </ul>
    <?php endif; ?>
    </div>
    <?php if ($currentTab === 'accessi'): ?>
    <div class="log-summary">
        <?php if ($filtroDataAccessi === 'tutti'): ?>
            <strong><?= (int)$riepilogoAccessi['distinti'] ?></strong> personaggi distinti · <strong><?= (int)$riepilogoAccessi['totali'] ?></strong> accessi totali in tutto lo storico (mostrati gli ultimi 500)
        <?php else: ?>
            <strong><?= (int)$riepilogoAccessi['distinti'] ?></strong> personaggi distinti hanno effettuato l'accesso il <strong><?= date('d/m/Y', strtotime($filtroDataAccessi)) ?></strong> (<?= (int)$riepilogoAccessi['totali'] ?> accessi totali)
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <!-- TABELLA -->
    <div class="log-table-scroll">
    <table id="logTable">
        <?php
        switch ($currentTab) {
            case 'chatbot': //  ******************  DOMANDE CHATBOT ******************
                $chatbot_log = gdrcd_query("SELECT id, nome_personaggio, domanda, risposta, tokens_usati, created_at FROM chatbot_log ORDER BY created_at DESC", 'result'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Personaggio</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> Domanda</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> Risposta</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Token usati</th>
                        <th onclick="sortTable(4)"><i class="fa-solid fa-sort"></i> Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($chatbot_log, 'fetch')) : ?>
                    <tr>
                        <td><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['nome_personaggio'])?>"><?=gdrcd_filter('out', $row['nome_personaggio'])?></a></td>
                        <td><?=gdrcd_filter('out', $row['domanda'])?></td>
                        <td><?=gdrcd_filter('out', $row['risposta'])?></td>
                        <td style="text-align:right;"><?=$row['tokens_usati']?></td>
                        <td><?=gdrcd_format_date($row['created_at']) . ' ' . gdrcd_format_time($row['created_at'])?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'generali': // ******************  GENERALI ******************
                $query_generali = gdrcd_query("SELECT autore, nome_interessato, data_evento, descrizione_evento
                                FROM log
                                WHERE codice_evento = ".gdrcd_filter('num', $_GET['generaliType'])."
                                ORDER BY data_evento DESC", 'result'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['author'])?></th>
                        <th onclick="sortTable(1)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['dest'])?></th>
                        <th onclick="sortTable(2)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['date'])?></th>
                        <th onclick="sortTable(3)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['descr'])?></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($query_generali, 'fetch')) : ?>
                    <tr>
                        <td><?=gdrcd_filter('out', $row['autore'])?></td>
                        <td>
                            <a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['nome_interessato'])?>">
                                <?=gdrcd_filter('out', $row['nome_interessato'])?>
                            </a>
                        </td>
                        <td><?=gdrcd_format_date($row['data_evento']) . ' ' . gdrcd_format_time($row['data_evento'])?></td>
                        <td><?=gdrcd_filter('out', $row['descrizione_evento'])?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'sms': //  ******************  SMS ******************
                $pg = gdrcd_filter('in', $_GET['pg']);
                $and = $pg != '' ? " AND (mittente_nome = '$pg' OR destinatario_nome = '$pg')" : '';
                $query_sms = "SELECT id_conversazione, destinatario_nome, mittente_nome, MAX(ora_spedizione) AS ultima_ora, ongame
                                FROM sms
                                WHERE mittente_nome NOT IN ('Segnalazione', 'Calendario', 'System')
                                AND destinatario_nome NOT IN ('Segnalazione', 'Calendario', 'System')
                                $and
                                GROUP BY id_conversazione
                                ORDER BY ultima_ora DESC";
                $sms = gdrcd_query($query_sms, 'result'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Mittente</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> Destinatario</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> Data</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Tipo</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($sms, 'fetch')) :
                    $posizioni = [$row["destinatario_nome"] => "left", $row["mittente_nome"] => "right"];

                    // Query per recuperare i messaggi della conversazione con paginazione
                    $msg_result = gdrcd_query("SELECT mittente_nome, testo, ora_spedizione, ongame FROM sms WHERE id_conversazione = ".$row['id_conversazione']." ORDER BY ora_spedizione DESC", 'result');
                ?>
                    <tr onclick="document.getElementById('modalMsg<?=$row['id_conversazione']?>').style.display = 'block';" style="cursor: pointer;">
                        <td><?=$row["mittente_nome"]?></td>
                        <td><?=$row["destinatario_nome"]?></td>
                        <td><?=$row["ultima_ora"]?></td>
                        <td><?=$row["ongame"]==0?'OFF':'ON'?></td>
                    </tr>
                    <div class="modal-overlay" id="modalMsg<?=$row['id_conversazione']?>">
                        <button class="close-btn" id="closeModal" onclick="document.getElementById('modalMsg<?=$row['id_conversazione']?>').style.display = 'none';">&times;</button>
                        <h2>Conversazione tra <?=$row["mittente_nome"]?> e <?=$row["destinatario_nome"]?></h2>
                        <div class="chat-container">
                    <?php while ($msg = gdrcd_query($msg_result, 'fetch')) :
                            $mittente = htmlspecialchars($msg['mittente_nome']);
                            $testo = $msg['testo']; // nl2br(htmlspecialchars($msg['testo']));
                            $ora = $msg['ongame'] == 1 
                                ? date('d-m-', strtotime($msg['ora_spedizione'])) . '3077 ' . date('H:i', strtotime($msg['ora_spedizione']))
                                : date('d-m-Y H:i', strtotime($msg['ora_spedizione']));
                            $posizione = isset($posizioni[$mittente]) ? $posizioni[$mittente] : "left";
                            $iniziale = strtoupper($mittente[0]); ?>
                            
                                    <div class="message <?=$posizione?>">
                                        <div class="avatar"><?=htmlspecialchars($iniziale)?></div>
                                        <div class="msg-content">
                                            <div class="bubble"><?=htmlspecialchars($testo)?></div>
                                            <div class="timestamp"><?=$ora?></div>
                                        </div>
                                    </div>
                    <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'doppi': //  ******************  PG DOPPI ******************
                $pg = gdrcd_filter('in', $_GET['pg']);
                $where = $pg != '' ? "WHERE Nome = '$pg' OR Doppio = '$pg'" : '';
                $doppi = gdrcd_query("SELECT * FROM log_doppi $where ORDER BY DataEvento DESC", 'result');

                // Conteggio "quanti personaggi distinti per IP" calcolato una sola volta
                // per tutti gli IP coinvolti, invece di una query per ogni riga della
                // tabella (era il vero collo di bottiglia: centinaia di scansioni di
                // log_entrate ad ogni caricamento di questa pagina).
                $ipCount = [];
                $resIp = gdrcd_query('SELECT IP, COUNT(DISTINCT Nome) AS n FROM log_entrate GROUP BY IP', 'result');
                while ($r = gdrcd_query($resIp, 'fetch')) { $ipCount[$r['IP']] = (int)$r['n']; }
                ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Pg</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> Doppio</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> Probabilità</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Rilevato da</th>
                        <th onclick="sortTable(4)"><i class="fa-solid fa-sort"></i> IP</th>
                        <th onclick="sortTable(5)"><i class="fa-solid fa-sort"></i> Host</th>
                        <th onclick="sortTable(6)"><i class="fa-solid fa-sort"></i> Browser</th>
                        <th onclick="sortTable(7)"><i class="fa-solid fa-sort"></i> Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $metodi = ['ip' => 'Stesso IP', 'dispositivo' => 'Stesso dispositivo'];
                while ($row = gdrcd_query($doppi, 'fetch')) :
                    $n = $ipCount[$row['IP']] ?? 0;
                    $probLabel = classificaProbabilitaDoppio($n); ?>
                    <tr>
                        <td><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['Nome'])?>"><?=gdrcd_filter('out', $row['Nome'])?></a></td>
                        <td><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['Doppio'])?>"><?=gdrcd_filter('out', $row['Doppio'])?></a></td>
                        <td>
                            <span class="status <?=$probLabel?>" title="<?=$n?> personaggi distinti hanno usato questo IP">
                                <?=$probLabel?>
                            </span>
                        </td>
                        <td><?=$metodi[$row['Metodo']] ?? '—'?></td>
                        <td><?=gdrcd_filter('out', $row['IP'])?></td>
                        <td><?=gdrcd_filter('out', $row['Host'])?></td>
                        <td><?=gdrcd_filter('out', $row['Browser'])?></td>
                        <td><?=$row['DataEvento']?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'accessi': //  ******************  ACCESSI ******************
                $limiteAccessi = $filtroDataAccessi === 'tutti' ? ' LIMIT 500' : '';
                $doppi = gdrcd_query("SELECT * FROM log_entrate $whereDataAccessi ORDER BY DataEvento DESC$limiteAccessi", 'result'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Pg</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> IP</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> Host</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($doppi, 'fetch')) : ?>
                    <tr>
                        <td><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['Nome'])?>"><?=gdrcd_filter('out', $row['Nome'])?></a></td>
                        <td><?=gdrcd_filter('out', $row['IP'])?></td>
                        <td><?=gdrcd_filter('out', $row['Host'])?></td>
                        <td><?=$row['DataEvento']?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'punti': //  ******************  PUNTI POSSEDUTI ******************
                $punti = getPuntiPg(); // Tutti i punti ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Riga</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> Nome</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> Stats da Exp</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Stats da Shin</th>
                        <th onclick="sortTable(4)"><i class="fa-solid fa-sort"></i> Shin da skill</th>
                        <th onclick="sortTable(5)"><i class="fa-solid fa-sort"></i> Shin residui</th>
                        <th onclick="sortTable(6)"><i class="fa-solid fa-sort"></i> Totale</th>
                        <th onclick="sortTable(7)"><i class="fa-solid fa-sort"></i> Azzerato</th>
                        <th onclick="sortTable(8)"><i class="fa-solid fa-sort"></i> Note</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($punti, 'fetch')) :
                    // Controllo i limiti
                    $errore = false;
                    $note = [];
                    $esperienza_r = getExp_rPg($row['esperienza']); // Punti esperienza
                    $tot_shin = ($row['shin_to_spend'] + $row['tot_shin'] + $row['punto_skill']); // Punti shin
                    $azzerato = ($tot_shin + $esperienza_r + 50); // Totale azzerato

                    if (($row['tot_xp'] - 50) > 260) { $errore = true; $note[] = "Exp > 260"; }
                    if (($row['tot_shin']+$row['punto_skill']) > 240) { $errore = true; $note[] = "Shin > 240"; }
                    if (($row['tot_shin']+$row['tot_xp']) > 550) { $errore = true; $note[] = "Totale > 550"; }
                ?>
                    <tr <?=$errore ? ' style="background-color: rgba(255, 0, 0, 0.2);"' : ''?>>
                        <td style="text-align:right;"><?=$row['riga']?></td>
                        <td><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['nome'])?>"><?=gdrcd_filter('out', $row['nome'])?></a></td>
                        <td style="text-align:right;"><?=$row['tot_xp']-50?></td>
                        <td style="text-align:right;"><?=$row['tot_shin']?></td>
                        <td style="text-align:right;"><?=$row['punto_skill']?></td>
                        <td style="text-align:right;"><?=$row['shin_to_spend']?></td>
                        <td style="text-align:right;"><?=$row['tot']?></td> <!-- tot_shin + tot_xp + shin_to_spend + punto_skill -->
                        <td style="text-align:right;"><?=$azzerato?></td>
                        <td><?=$errore ? implode(', ', $note) : '-'?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            } ?>
    </table>
    </div>
</div>

<script type="text/javascript" src="/includes/changetitle.js"></script>
<script type="text/javascript" src="/includes/corefunctions.js"></script>