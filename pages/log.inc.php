<?php
// Permessi
if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) {
    echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    die();
}

require_once(__DIR__ . '/../includes/custom_functions.inc.php');

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chatbot';
?>

<div class="log-container">
    <h1>Logs</h1>
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
        <!-- No filters for accessi tab -->
    <?php elseif ($currentTab == 'punti'): ?>
        <b>Limiti:</b> xp 260, shin 240, totale 550</li>
        </ul>
    <?php endif; ?>
    </div>
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
                        <td><?=gdrcd_filter('out', $row['nome_personaggio'])?></td>
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
                                ORDER BY data_evento DESC", 'result');
                $generali = gdrcd_query($query_generali, 'fetch'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['author'])?></th>
                        <th onclick="sortTable(1)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['dest'])?></th>
                        <th onclick="sortTable(2)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['date'])?></th>
                        <th onclick="sortTable(3)"><?=gdrcd_filter('out', $MESSAGE['interface']['administration']['log']['events']['descr'])?></th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($result, 'fetch')) : ?>
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
                $doppi = gdrcd_query("SELECT * FROM log_doppi $where ORDER BY DataEvento DESC", 'result'); ?>
                <thead>
                    <tr>
                        <th onclick="sortTable(0)"><i class="fa-solid fa-sort"></i> Pg</th>
                        <th onclick="sortTable(1)"><i class="fa-solid fa-sort"></i> Doppio</th>
                        <th onclick="sortTable(2)"><i class="fa-solid fa-sort"></i> IP</th>
                        <th onclick="sortTable(3)"><i class="fa-solid fa-sort"></i> Host</th>
                        <th onclick="sortTable(4)"><i class="fa-solid fa-sort"></i> Browser</th>
                        <th onclick="sortTable(5)"><i class="fa-solid fa-sort"></i> Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = gdrcd_query($doppi, 'fetch')) : ?>
                    <tr>
                        <td><?=$row['Nome']?></td>
                        <td><?=$row['Doppio']?></td>
                        <td><?=$row['IP']?></td>
                        <td><?=$row['Host']?></td>
                        <td><?=$row['Browser']?></td>
                        <td><?=$row['DataEvento']?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            <?php break;
            case 'accessi': //  ******************  ACCESSI ******************
                $doppi = gdrcd_query("SELECT * FROM log_entrate ORDER BY DataEvento DESC", 'result'); ?>
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
                        <td><?=$row['Nome']?></td>
                        <td><?=$row['IP']?></td>
                        <td><?=$row['Host']?></td>
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
                        <td><?=$row['nome']?></td>
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