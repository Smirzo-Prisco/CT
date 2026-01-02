<div class="pagina_servizi_adm_mestieri">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_jobs']['page_name'].' '.strtolower($PARAMETERS['names']['job_name']['plur'])); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
        <?php /*Elenco lavori*/
        if(isset($_POST['op']) === false) {
            echo '<div class="form_gioco">';
                /*Seleziono i ruoli su cui l'account ha competenza*/
                if($_SESSION['admin']==1) {
                    $people = "SELECT nome, cognome FROM personaggio  WHERE permessi > -1 ORDER BY nome";
                    $query = "SELECT ruolo_mestiere.id_ruolo, ruolo_mestiere.nome_ruolo, mestiere.nome FROM ruolo_mestiere LEFT JOIN mestiere ON ruolo_mestiere.mestiere = mestiere.id_mestiere ORDER BY mestiere.nome, ruolo_mestiere.capo DESC, ruolo_mestiere.stipendio DESC, ruolo_mestiere.nome_ruolo";
                    $members = "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo ORDER BY ruolo_mestiere.mestiere DESC, ruolo_mestiere.stipendio DESC";
                } else {
                    if($_SESSION['capomestiere']==1) {
                        $people = "SELECT nome, cognome FROM personaggio  WHERE permessi > -1 ORDER BY nome";
                        $query = "SELECT ruolo_mestiere.id_ruolo, ruolo_mestiere.nome_ruolo, mestiere.nome FROM ruolo_mestiere JOIN mestiere ON ruolo_mestiere.mestiere = mestiere.id_mestiere WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1) ORDER BY mestiere.nome, ruolo_mestiere.capo DESC, ruolo_mestiere.stipendio DESC, ruolo_mestiere.nome_ruolo";
                        $members = "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1)  OR ruolo_mestiere.mestiere=-1  ORDER BY ruolo_mestiere.mestiere DESC, ruolo_mestiere.stipendio DESC";
                    } else {
                        $people = "SELECT nome, cognome FROM personaggio  WHERE permessi > -1 ORDER BY nome";
                        $query = "SELECT ruolo_mestiere.id_ruolo, ruolo_mestiere.nome_ruolo, mestiere.nome FROM ruolo_mestiere JOIN mestiere ON ruolo_mestiere.mestiere = mestiere.id_mestiere WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1 AND ruolo_mestiere.capo=1) ORDER BY mestiere.nome, ruolo_mestiere.capo DESC, ruolo_mestiere.stipendio DESC, ruolo_mestiere.nome_ruolo";
                        $members = "SELECT clgpersonaggiomestiere.personaggio, clgpersonaggiomestiere.id_ruolo, ruolo_mestiere.nome_ruolo, ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo=ruolo_mestiere.id_ruolo WHERE ruolo_mestiere.mestiere IN (SELECT ruolo_mestiere.mestiere FROM clgpersonaggiomestiere JOIN ruolo_mestiere ON clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio= '".$_SESSION['login']."' AND ruolo_mestiere.mestiere>-1 AND capo=1) OR ruolo_mestiere.mestiere=-1 ORDER BY ruolo_mestiere.mestiere DESC, ruolo_mestiere.stipendio DESC";
                    }
                }
                $result = gdrcd_query($query, 'result');
                $people_result = gdrcd_query($people, 'result');
                $members_result = gdrcd_query($members, 'result');
                /*Se non c'e' titolo per gestire una mestiere*/
                if(gdrcd_query($result, 'num_rows') == 0) {
                    echo '<div class="warning">'.$MESSAGE['interface']['adm_guilds']['no_adm'].' '.strtolower($PARAMETERS['names']['guild_name']['sing']).'</div>';
                } else { ?>
                    <form action="main.php?page=servizi_adm_mestieri" method="post">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['new_member'].' '.strtolower($PARAMETERS['names']['guild_name']['members']); ?>
                        </div>
                        <div class="form_element">
                            <select name="ruolo_mestiere">
                                <?php
                                while($row = gdrcd_query($result, 'fetch')) { ?>
                                    <option value="<?php echo $row['id_ruolo'].'-'.$row['nome_ruolo']; ?>">
                                        <?php echo $row['nome_ruolo']; ?>
                                        (<?php if($row['nome'] != '') {
                                            echo $row['nome'];
                                        } else {
                                            echo $MESSAGE['interface']['adm_guilds']['freelance'];
                                        } ?>)
                                    </option>
                                <?php }
                                gdrcd_query($result, 'free');
                                ?>
                            </select>
                            <select name="nome">
                                <?php
                                while($row = gdrcd_query($people_result, 'fetch')) { ?>
                                    <option value="<?php echo $row['nome']; ?>">
                                        <?php echo $row['nome'].' '.$row['cognome']; ?>
                                    </option>
                                <?php }
                                gdrcd_query($people_result, 'free');
                                ?>
                            </select>
                        </div>
                        <div class="form_submit">
                            <input type="hidden" name="op" value="hire" />
                            <input type="submit" name="submit" value="<?php echo $MESSAGE['interface']['adm_guilds']['hire']; ?>" />
                        </div>
                    </form>
                    <form action="main.php?page=servizi_adm_mestieri" method="post">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['fire_member'].' '.strtolower($PARAMETERS['names']['guild_name']['members']); ?>
                        </div>
                        <div class="form_element">
                            <select name="ruolo_mestiere">
                                <?php
                                $echoed_null_row = false;
                                while($row = gdrcd_query($members_result, 'fetch')) {
                                    if(($echoed_null_row === false) && ($row['mestiere'] == -1)) {
                                        echo '<option value="" disabled>-------</option>';
                                        $echoed_null_row = true;
                                    }
                                    ?>
                                    <option value="<?php echo $row['personaggio']."-".$row['id_ruolo']."-".$row['nome_ruolo']; ?>">
                                        <?php echo $row['personaggio']." (".$row['nome_ruolo'].")"; ?>
                                    </option>
                                <?php }
                                gdrcd_query($members_result, 'free');
                                ?>
                            </select>
                        </div>
                        <div class="form_submit">
                            <input type="hidden" name="op" value="fire" />
                            <input type="submit" name="submit" value="<?php echo $MESSAGE['interface']['adm_guilds']['fire']; ?>" />
                        </div>
                    </form>
                <?php
                }//else
                $affiliazioni = "SELECT mestiere.id_mestiere, ruolo_mestiere.nome_ruolo, mestiere.nome, ruolo_mestiere.id_ruolo FROM ruolo_mestiere LEFT JOIN mestiere ON mestiere.id_mestiere = ruolo_mestiere.mestiere WHERE ruolo_mestiere.id_ruolo IN (SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '".$_SESSION['login']."' AND scadenza < NOW()) ";
                $affiliazioni_result = gdrcd_query($affiliazioni, 'result');

                if(gdrcd_query($affiliazioni_result, 'num_rows') > 0) { ?>
                    <form action="" method="">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['quit']; ?>
                        </div>
                    </form>
                    <?php while($row = gdrcd_query($affiliazioni_result, 'fetch')) { ?>
                        <form action="main.php?page=servizi_adm_mestieri" method="post">
                            <div style="float: left; width: 70%">
                                <?php echo $row['nome_ruolo'];
                                if(empty($row['nome']) === false) {
                                    echo ' ('.$row['nome'].') ';
                                } ?>
                            </div>
                            <div class="form_submit">
                                <input type="hidden" name="ruolo_mestiere" value="<?php echo $_SESSION['login']."-".$row['id_ruolo_mestiere']."-".$row['nome_ruolo_mestiere']; ?>" />
                                <input type="hidden" name="op" value="fire" />
                                <input type="submit" name="submit" value="<?php echo $MESSAGE['interface']['adm_guilds']['quit']; ?>" />
                            </div>
                        </form>
                    <?php
                    }//while
                }
                 gdrcd_query($affiliazioni_result, 'free');
                ?>
            </div>
            <div class="link_back">
                <a href="main.php?page=servizi_adm_mestieri"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guids']['back']); ?></a>
            </div>
        <?php
        } //if
        /*Affiliazione*/
        if(gdrcd_filter('get', $_POST['op']) == 'hire') {
            /*Controllo il numero di affiliazioni correnti del personaggio*/
            $jobs = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiomestiere WHERE personaggio = '".gdrcd_filter('in', $_POST['nome'])."'");

            /*Se il personaggio ha raggiunto il limite*/
            if($jobs['COUNT(*)'] >= $PARAMETERS['settings']['jobs_limit']) {
                echo '<div class="warning">'.gdrcd_filter('out', $_POST['nome'].' '.$MESSAGE['interface']['adm_guilds']['cannot_hire']).'</div>';
            } else {
                /*Opero l'affiliazione*/
                $subject = explode('-', gdrcd_filter('in', $_POST['ruolo_mestiere']));
                gdrcd_query("INSERT INTO clgpersonaggiomestiere  (personaggio, id_ruolo, scadenza) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".$subject[0].", NOW())");
				$mestiere = "SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".$subject[0]."";
                $mestiere_result = gdrcd_query($mestiere, 'result');
                $mestiere = gdrcd_query($mestiere_result, 'fetch');
				gdrcd_query("UPDATE personaggio SET id_mestiere = ".$mestiere['mestiere'].", id_ruolo_mestiere = ".$subject[0]." WHERE nome = '".$_POST['nome']."'");      
                /*Confermo l'operazione*/
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_hire']).'</div>';
                /*Registro l'operazione
                gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '".$_SESSION['login']."', NOW(), ".NUOVOLAVORO.", '".gdrcd_filter('out', $subject[1])."')");
*/
                /*Avviso l'utente*/
                if($_SESSION['login'] != $_POST['nome']) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$subject[1])."')");
                }
            }//else
            ?>
            <div class="panels_link">
                <a href="main.php?page=servizi_adm_mestieri"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
        <?php
        }
        /*Espulsione*/
        if($_POST['op'] == 'fire') {
            $subject = explode('-', gdrcd_filter('in', $_POST['ruolo_mestiere']));
            gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio='".$subject[0]."' AND id_ruolo = ".gdrcd_filter('num', $subject[1])." LIMIT 1");
            gdrcd_query("UPDATE personaggio SET id_mestiere=0 WHERE nome = '".$subject[0]."'");
            gdrcd_query("UPDATE personaggio SET id_ruolo_mestiere=1 WHERE nome = '".$subject[0]."'");
            /*Confermo l'operazione*/
            echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_fire']).'</div>';
            /*Registro l'operazione
            gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".$subject[0]."', '".$_SESSION['login']."', NOW(), ".DIMISSIONE.", '".gdrcd_filter('out', $subject[2])."')");
*/
            /*Avviso l'utente*/
            if($_SESSION['login'] != $subject[0]) {
                gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".$subject[0]."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['fire'].' '.$subject[2])."')");
            }
            ?>
            <div class="panels_link">
                <a href="main.php?page=servizi_adm_mestieri"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
        <?php } ?>
    </div>
</div><!-- Box principale -->