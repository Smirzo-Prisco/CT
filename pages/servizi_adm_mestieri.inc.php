<div class="pagina_servizi_adm_mestieri">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_jobs']['page_name'].' '.strtolower($PARAMETERS['names']['job_name']['plur'])); ?></h2>
    </div>
    <!-- Box principale -->
    <div class="page_body">
    <?php
    require_once(__DIR__ . '/../includes/custom_functions.inc.php');

    $is_admin              = ($_SESSION['admin'] == 1);
    $is_staff_capomestiere = ($_SESSION['capomestiere'] == 1);
    $login_esc             = gdrcd_filter('in', $_SESSION['login']);

    /** Vero se l'utente corrente può gestire (assumi/espelli) questo specifico mestiere/gilda */
    function puo_gestire_mestiere($id_mestiere, $is_admin, $is_staff_capomestiere, $login_esc) {
        if ($id_mestiere === null) return false;
        if ($is_admin) return true;
        if ($id_mestiere === -1) return false; // i lavori indipendenti restano riservati agli admin
        if ($is_staff_capomestiere && mestiere_e_membro_di($login_esc, $id_mestiere)) return true;
        return mestiere_e_capo_di($login_esc, $id_mestiere);
    }

    // ── Determina il mestiere/gilda "in oggetto" ──────────────────────────────
    $id_mestiere = (isset($_REQUEST['id_mestiere']) && $_REQUEST['id_mestiere'] !== '')
        ? (int)gdrcd_filter('num', $_REQUEST['id_mestiere'])
        : null;

    if ($id_mestiere === null && !$is_admin) {
        // jobs_limit di default è 1: un utente normale ha al più un'affiliazione,
        // quindi la deduciamo automaticamente senza chiedere di sceglierla
        $auto = gdrcd_query(
            "SELECT rm.mestiere FROM clgpersonaggiomestiere cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
             WHERE cpm.personaggio = '$login_esc' AND rm.mestiere > -1 LIMIT 1"
        );
        if ($auto) $id_mestiere = (int)$auto['mestiere'];
    }

    $autorizzato = puo_gestire_mestiere($id_mestiere, $is_admin, $is_staff_capomestiere, $login_esc);

    if ($id_mestiere === null && $is_admin):
        // Admin senza mestiere specificato: selettore
        $elenco = gdrcd_query("SELECT id_mestiere, nome FROM mestiere ORDER BY nome", 'result');
        ?>
        <div class="form_gioco">
            <form action="main.php?page=servizi_adm_mestieri" method="get">
                <div class="form_label">Seleziona il mestiere o la gilda da gestire</div>
                <div class="form_element">
                    <select name="id_mestiere">
                        <?php while ($m = gdrcd_query($elenco, 'fetch')): ?>
                        <option value="<?php echo (int)$m['id_mestiere']; ?>"><?php echo gdrcd_filter('out', $m['nome']); ?></option>
                        <?php endwhile; gdrcd_query($elenco, 'free'); ?>
                    </select>
                </div>
                <div class="form_submit">
                    <input type="submit" value="Vai" />
                </div>
            </form>
        </div>
    <?php
    elseif ($id_mestiere === null || !$autorizzato):
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    else:
        /*Elenco lavori*/
        if(isset($_POST['op']) === false) {
            echo '<div class="form_gioco">';
                $people  = "SELECT nome, cognome FROM personaggio WHERE permessi > -1 AND nome NOT IN (SELECT personaggio FROM clgpersonaggiomestiere) ORDER BY nome";
                $query   = "SELECT id_ruolo, nome_ruolo FROM ruolo_mestiere WHERE mestiere = $id_mestiere ORDER BY capo DESC, stipendio DESC, nome_ruolo";
                $members = "SELECT cpm.personaggio, cpm.id_ruolo, rm.nome_ruolo FROM clgpersonaggiomestiere cpm JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo WHERE rm.mestiere = $id_mestiere ORDER BY rm.capo DESC, rm.stipendio DESC";

                $result         = gdrcd_query($query, 'result');
                $people_result  = gdrcd_query($people, 'result');
                $members_result = gdrcd_query($members, 'result');

                /*Se non ci sono ruoli definiti per questo mestiere*/
                if(gdrcd_query($result, 'num_rows') == 0) {
                    echo '<div class="warning">'.$MESSAGE['interface']['adm_guilds']['no_adm'].' '.strtolower($PARAMETERS['names']['guild_name']['sing']).'</div>';
                } else { ?>
                    <form action="main.php?page=servizi_adm_mestieri" method="post">
                        <input type="hidden" name="id_mestiere" value="<?php echo $id_mestiere; ?>">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['new_member'].' '.strtolower($PARAMETERS['names']['guild_name']['members']); ?>
                        </div>
                        <div class="form_element">
                            <select name="ruolo_mestiere">
                                <?php
                                while($row = gdrcd_query($result, 'fetch')) { ?>
                                    <option value="<?php echo $row['id_ruolo'].'-'.$row['nome_ruolo']; ?>">
                                        <?php echo $row['nome_ruolo']; ?>
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
                        <input type="hidden" name="id_mestiere" value="<?php echo $id_mestiere; ?>">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['fire_member'].' '.strtolower($PARAMETERS['names']['guild_name']['members']); ?>
                        </div>
                        <div class="form_element">
                            <select name="ruolo_mestiere">
                                <?php
                                while($row = gdrcd_query($members_result, 'fetch')) { ?>
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

                /*Dimissioni: affiliazioni scadute del login corrente (self-quit) — logica invariata*/
                $affiliazioni        = "SELECT mestiere.id_mestiere, ruolo_mestiere.nome_ruolo, mestiere.nome, ruolo_mestiere.id_ruolo FROM ruolo_mestiere LEFT JOIN mestiere ON mestiere.id_mestiere = ruolo_mestiere.mestiere WHERE ruolo_mestiere.id_ruolo IN (SELECT id_ruolo FROM clgpersonaggiomestiere WHERE personaggio = '".$_SESSION['login']."' AND scadenza < NOW()) ";
                $affiliazioni_result = gdrcd_query($affiliazioni, 'result');

                if(gdrcd_query($affiliazioni_result, 'num_rows') > 0) { ?>
                    <form action="" method="">
                        <div class="form_label">
                            <?php echo $MESSAGE['interface']['adm_guilds']['quit']; ?>
                        </div>
                    </form>
                    <?php while($row = gdrcd_query($affiliazioni_result, 'fetch')) { ?>
                        <form action="main.php?page=servizi_adm_mestieri" method="post">
                            <input type="hidden" name="id_mestiere" value="<?php echo $id_mestiere; ?>">
                            <div style="float: left; width: 70%">
                                <?php echo $row['nome_ruolo'];
                                if(empty($row['nome']) === false) {
                                    echo ' ('.$row['nome'].') ';
                                } ?>
                            </div>
                            <div class="form_submit">
                                <input type="hidden" name="ruolo_mestiere" value="<?php echo $_SESSION['login']."-".$row['id_ruolo']."-".$row['nome_ruolo']; ?>" />
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
                <a href="main.php?page=servizi_adm_mestieri&id_mestiere=<?php echo $id_mestiere; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
        <?php
        } //if isset POST op === false

        /*Affiliazione*/
        if(gdrcd_filter('get', $_POST['op']) == 'hire') {
            $id_mestiere_post = (int)($_POST['id_mestiere'] ?? 0);
            $subject          = explode('-', gdrcd_filter('in', $_POST['ruolo_mestiere']));
            $id_ruolo_scelto  = (int)$subject[0];

            // Il ruolo scelto deve appartenere davvero al mestiere autorizzato (il form non basta: qualcuno potrebbe forgiare il POST)
            $ruolo_check = gdrcd_query("SELECT rm.mestiere, m.tipo FROM ruolo_mestiere rm LEFT JOIN mestiere m ON rm.mestiere = m.id_mestiere WHERE rm.id_ruolo = $id_ruolo_scelto");
            if (!$ruolo_check || (int)$ruolo_check['mestiere'] !== $id_mestiere_post || !puo_gestire_mestiere($id_mestiere_post, $is_admin, $is_staff_capomestiere, $login_esc)) {
                echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
            } else {
                /*Controllo il numero di affiliazioni correnti del personaggio*/
                $jobs = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggiomestiere WHERE personaggio = '".gdrcd_filter('in', $_POST['nome'])."'");

                /*Se il personaggio ha raggiunto il limite*/
                if($jobs['COUNT(*)'] >= $PARAMETERS['settings']['jobs_limit']) {
                    echo '<div class="warning">'.gdrcd_filter('out', $_POST['nome'].' '.$MESSAGE['interface']['adm_guilds']['cannot_hire']).'</div>';
                } else {
                    /*Opero l'affiliazione*/
                    // Per le gilde giocatore (tipo != 1) l'affiliazione è confermata subito: l'assunzione
                    // è diretta da parte del capo, non passa dal flusso di conferma dei mestieri globali
                    $e_gilda_giocatore = $ruolo_check['tipo'] !== null && (int)$ruolo_check['tipo'] !== 1;
                    $conferma_sql      = $e_gilda_giocatore ? ', conferma_mestiere' : '';
                    $conferma_valori   = $e_gilda_giocatore ? ', 1' : '';
                    gdrcd_query("INSERT INTO clgpersonaggiomestiere (personaggio, id_ruolo$conferma_sql, scadenza) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".$subject[0]."$conferma_valori, NOW())");
                    $mestiere        = "SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".$subject[0]."";
                    $mestiere_result = gdrcd_query($mestiere, 'result');
                    $mestiere        = gdrcd_query($mestiere_result, 'fetch');
                    gdrcd_query("UPDATE personaggio SET id_mestiere = ".$mestiere['mestiere'].", id_ruolo_mestiere = ".$subject[0]." WHERE nome = '".$_POST['nome']."'");
                    /*Confermo l'operazione*/
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_hire']).'</div>';
                    /*Avviso l'utente*/
                    if($_SESSION['login'] != $_POST['nome']) {
                        gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".gdrcd_filter('in', $_POST['nome'])."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['hire'].' '.$subject[1])."')");
                    }
                }//else
            }
            ?>
            <div class="panels_link">
                <a href="main.php?page=servizi_adm_mestieri&id_mestiere=<?php echo $id_mestiere_post; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
        <?php
        }
        /*Espulsione*/
        if($_POST['op'] == 'fire') {
            $subject          = explode('-', gdrcd_filter('in', $_POST['ruolo_mestiere']));
            $id_mestiere_post = (int)($_POST['id_mestiere'] ?? 0);

            // Chiunque può dimettersi da sé stesso; altrimenti serve essere autorizzati sul mestiere del ruolo
            $ruolo_check      = gdrcd_query("SELECT mestiere FROM ruolo_mestiere WHERE id_ruolo = ".(int)$subject[1]);
            $e_se_stesso      = ($subject[0] === $_SESSION['login']);
            $autorizzato_fire = $e_se_stesso || ($ruolo_check && puo_gestire_mestiere((int)$ruolo_check['mestiere'], $is_admin, $is_staff_capomestiere, $login_esc));

            if (!$autorizzato_fire) {
                echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
            } else {
                gdrcd_query("DELETE FROM clgpersonaggiomestiere WHERE personaggio='".$subject[0]."' AND id_ruolo = ".gdrcd_filter('num', $subject[1])." LIMIT 1");
                gdrcd_query("UPDATE personaggio SET id_mestiere=0 WHERE nome = '".$subject[0]."'");
                gdrcd_query("UPDATE personaggio SET id_ruolo_mestiere=1 WHERE nome = '".$subject[0]."'");
                /*Confermo l'operazione*/
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['ok_fire']).'</div>';
                /*Avviso l'utente*/
                if($_SESSION['login'] != $subject[0]) {
                    gdrcd_query("INSERT INTO messaggi (mittente, destinatario, spedito, testo) VALUES ('".$_SESSION['login']."', '".$subject[0]."', NOW(), '".gdrcd_filter('in', $MESSAGE['interface']['adm-guilds']['message_body']['fire'].' '.$subject[2])."')");
                }
            }
            ?>
            <div class="panels_link">
                <a href="main.php?page=servizi_adm_mestieri<?php echo $id_mestiere_post > 0 ? '&id_mestiere='.$id_mestiere_post : ''; ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['adm_guilds']['back']); ?></a>
            </div>
        <?php } ?>
    <?php endif; ?>
    </div>
</div>
