<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title"><h2><?php echo gdrcd_filter('out', $PARAMETERS['names']['guild_name']['plur']); ?></h2></div>
    <br><br>
    <!-- Box principale -->
    <div class="page_body">
    <?php // CORRENTI
        if(isset($_REQUEST['id_gilda']) === false) { ?>
            <table class="customTable">
                <tr><td><div><a class="table-title" href="../documentazione_main.php" target="_blank">AMBIENTAZIONE & REGOLAMENTO</a></div></td></tr>
            </table>
            <br><br>
    <?php   // Unica tabella "Razze" — unifica Correnti di cosmos, neutrali e caos
            $query = "SELECT gilda.nome, gilda.id_gilda FROM gilda WHERE gilda.visibile = 1 AND gilda.tipo != 4 ORDER BY gilda.tipo, gilda.id_gilda";
            $result = gdrcd_query($query, 'result'); ?>
            <br>
            <table class="customTable">
                <tr>
                    <td colspan="3">
                        <div style="font-size:13px;color:#9a6353;font-family:DejaVu Serif;filter:drop-shadow(0 0 5px rgba(0,0,0,0.57));">Razze</div>
                    </td>
                </tr>
                <tr class="second_header">
                    <td width="60%"><div><?=gdrcd_filter('out', $PARAMETERS['names']['guild_name']['sing'])?></div></td>
                    <td><div>Statuto</div></td>
                    <td><div><?=gdrcd_filter('out', $PARAMETERS['names']['guild_name']['members'])?></div></td>
                </tr>
            <?php while($row = gdrcd_query($result, 'fetch')):
                $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioruolo JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo WHERE ruolo.gilda = ".$row['id_gilda'].""); ?>
                <tr>
                    <td width="60%"><div><a href="main.php?page=servizi_gilde&id_gilda=<?=$row['id_gilda']?>"><?=gdrcd_filter('out', $row['nome'])?></a></div></td>
                    <td><a href="../statuto_main.php?id=<?=$row['id_gilda']?>" target="_blank"><i class="fa-solid fa-scroll"></i></a></td>
                    <td><div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?=$numb['COUNT(*)']?></div></td>
                </tr>
            <?php endwhile;
            gdrcd_query($result, 'free'); ?>
            </table>
            <br>

            <?php // MESTIERI — immagine + elenco + lavoro libero
            $query = "SELECT mestiere.nome, mestiere.id_mestiere, mestiere.tipo, mestiere.url_sito, codtipomestiere.descrizione FROM mestiere JOIN codtipomestiere ON mestiere.tipo = codtipomestiere.cod_tipo WHERE mestiere.visibile = 1 ORDER BY mestiere.tipo, mestiere.nome";
            $result = gdrcd_query($query, 'result');
            $last_type = -1; ?>
            <div class="titolo">
                <img src="/themes/crystal/imgs/mestieri/titolo_mestieri.png" />
            </div>
            <br>
            <table class="customTable">
                <?php while($row = gdrcd_query($result, 'fetch')):
                    $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere = ".$row['id_mestiere']." && clgpersonaggiomestiere.conferma_mestiere = 1");
                    if($row['tipo'] != $last_type): ?>
                        <tr class="second_header">
                            <td><?=gdrcd_filter('out', $PARAMETERS['names']['job_name']['sing'])?></td>
                            <td>Statuto</td>
                            <td><?=gdrcd_filter('out', $PARAMETERS['names']['job_name']['members'])?></td>
                        </tr>
                    <?php $last_type = $row['tipo'];
                    endif; ?>
                    <tr>
                        <td width="60%"><div><a href="main.php?page=servizi_mestieri&id_mestiere=<?=$row['id_mestiere']?>"><?=gdrcd_filter('out', $row['nome'])?></a></div></td>
                        <td width="20%"><div><a href="<?=$row['url_sito']?>?id2=<?=$row['id_mestiere']?>" target="_blank"><i class="fa-solid fa-scroll"></i></a></div></td>
                        <td><div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?=$numb['COUNT(*)']?></div></td>
                    </tr>
                <?php endwhile;
                gdrcd_query($result, 'free'); ?>
            </table>
            <br><br>

            <?php // LAVORO LIBERO
            $query_job = "SELECT * FROM ruolo_mestiere JOIN clgpersonaggiolavoro ON ruolo_mestiere.id_ruolo = clgpersonaggiolavoro.id_ruolo JOIN personaggio ON clgpersonaggiolavoro.personaggio = personaggio.nome WHERE mestiere = -1 && personaggio.esperienza > 9 GROUP BY nome_ruolo";
            $result_job = gdrcd_query($query_job, 'result'); ?>
            <table class="customTable">
                <tr class="second_header">
                    <td>Lavoro Libero</td>
                    <td><?=gdrcd_filter('out', $PARAMETERS['names']['job_name']['members'])?></td>
                </tr>
                <?php while($row_job = gdrcd_query($result_job, 'fetch')):
                    $numb_job = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiolavoro JOIN ruolo_mestiere ON clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo JOIN personaggio ON clgpersonaggiolavoro.personaggio = personaggio.nome WHERE ruolo_mestiere.id_ruolo = ".$row_job['id_ruolo']." && personaggio.esperienza > 9"); ?>
                    <tr>
                        <td width="80%"><div><a href="main.php?page=servizi_mestieri2&id_ruolo=<?=$row_job['id_ruolo']?>"><?=gdrcd_filter('out', $row_job['nome_ruolo'])?></a></div></td>
                        <td><div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?=$numb_job['COUNT(*)']?></div></td>
                    </tr>
                <?php endwhile;
                gdrcd_query($result_job, 'free'); ?>
            </table>
            <br><br>

    <?php
        } else {
            // RUOLI
            $query = "SELECT * FROM ruolo WHERE gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY livello DESC, nome_ruolo DESC";
            $result = gdrcd_query($query, 'result'); ?>

            <table class="customTable">
                <tr class="second_header">
                    <td><div>&nbsp;</div></td>
                    <td><div>GRADO</div></td>
                    <!-- Elenco -->
<?php       while($row = gdrcd_query($result, 'fetch')) { ?>
                <tr>
                    <td><div><img src="imgs/guilds/<?=gdrcd_filter('out', $row['immagine'])?>"></div></td>
                    <td><div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?=gdrcd_filter('out', $row['nome_ruolo'])?></div></td>
                </tr>
<?php       }
            gdrcd_query($result, 'free');
?>
            </table>
                    <?php /*Elenco affiliati*/
                    if ($_REQUEST['id_gilda'] == '-1') {
                    $query = "SELECT personaggio.*, ruolo.immagine, ruolo.capo, ruolo.livello,  ruolo.nome_ruolo FROM ruolo JOIN personaggio ON personaggio.id_ruolo_gilda = ruolo.id_ruolo WHERE ruolo.gilda = -1 ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    } else {
                    //$query = "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.nickname, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo, ruolo.livello FROM ruolo JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio WHERE ruolo.gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." ORDER BY ruolo.livello DESC, ruolo.nome_ruolo DESC";
                    $query = "SELECT clgpersonaggioruolo.personaggio, clgpersonaggioruolo.nickname, personaggio.cognome, ruolo.immagine, ruolo.capo, ruolo.nome_ruolo, ruolo.livello,
                              CASE
                                  WHEN personaggio.nome IN ('Acamar', 'Kirari', 'Etsuya') THEN 1
                                  ELSE 0
                              END AS special_character
                              FROM ruolo 
                              JOIN clgpersonaggioruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo 
                              JOIN personaggio ON personaggio.nome = clgpersonaggioruolo.personaggio 
                              WHERE ruolo.gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])." 
                              ORDER BY special_character DESC, ruolo.livello DESC, ruolo.nome_ruolo DESC";
                              }
                    $result = gdrcd_query($query, 'result'); ?>
                    <br><br>
                    <table class="customTable">
                        <tr class="second_header">
                            <td><div>&nbsp;</div></td>
                            <td><div>MEMBRI</div></td>
                            <td><div>GRADO</div></td>
                        </tr>
                        <!-- Elenco -->
                <?php   while($row = gdrcd_query($result, 'fetch')) { ?>
                        <tr>
                            <td><div><img src="imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>" /></div></td>
                            <td>
                                <div>
                                    <?php if ($_REQUEST['id_gilda'] == '-1') { ?>
                                    <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['nome']); ?>">
                                        <?php                                        
                                        echo gdrcd_filter('out', $row['nome'].' '.$row['cognome']); 
                                        ?>
                                        </a>
                                        <?php } else {
                                        ?>                                    
                                        <a href="main.php?page=scheda&pg=<?php echo gdrcd_filter('out', $row['personaggio']); ?>">
                                        <?php 
                                        if ($row['special_character'] == 1) {
                                        echo '<span style="color: #9a6353;">'.gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']).'</span>'; 
                                        } else {
                                        echo gdrcd_filter('out', $row['personaggio'].' '.$row['cognome']); 
                                        }
                                        ?>
                                    </a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                <?php 
                                if ($row['nickname'] === NULL) {
                                    echo gdrcd_filter('out', $row['nome_ruolo']);
                                } else {
                                    echo gdrcd_filter('out', $row['nickname']);
                                }
                                ?>
                                </div>
                            </td>
                        </tr>
                    <?php }
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                    <!--elenco_breve-->

                <?php /*statuto*/
                $statuto = gdrcd_query("SELECT statuto FROM gilda WHERE id_gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])."");

                if(empty($statuto['statuto']) === false) { ?>
                    <table>
                        <tr class="second_header"><td colspan="4"><div>Statuto</div></td><tr>
                        <tr><td><div style="text-align: justify;"><?php echo gdrcd_bbcoder(gdrcd_filter('out', $statuto['statuto'])); ?></div></td></tr>
                    </table>
                <?php } ?>
                <div class="link_back">
                    <a href="main.php?page=servizi_gilde"><?php echo gdrcd_filter('out', $MESSAGE['interface']['guilds']['back']); ?></a>
                </div>
            <?php } ?>
        </div>
        <!-- Box principale -->
    </div>