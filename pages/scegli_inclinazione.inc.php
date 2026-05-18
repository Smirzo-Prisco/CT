<?php
/***************    Dati del personaggio corrente    *****************************/
$pg = gdrcd_query("SELECT personaggio.*, clgpersonaggioinclinazione.id_ruolo AS id_inclinazione_corrente
                    FROM personaggio
                    LEFT JOIN clgpersonaggioinclinazione ON clgpersonaggioinclinazione.personaggio = personaggio.nome
                    WHERE personaggio.nome = '". gdrcd_filter('in', $_SESSION['login']) ."' LIMIT 1");

$inclinazione = (int)($pg['id_inclinazione_corrente'] ?? 0);
$login_f      = gdrcd_filter('in', $_SESSION['login']);

$op = isset($_POST['op']) ? $_POST['op'] : '';
?>

<link rel="stylesheet" href="/themes/crystal/famiglie.css">

<div class="pagina_servizi_lavoro">
    <div class="page_title"><h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['inclinazioni']['page_name']); ?></h2></div>

    <?php /* ──────────── SEZIONE 1: ELENCO CORRENTI ──────────── */
    if($op === '') { ?>
        <table class="customTable">
            <thead><tr><th colspan="3">ELENCO CORRENTI</th></tr></thead>
            <tbody>
                <tr class="second_header"><td></td><td>NOME</td><td></td></tr>
                <?php
                $result = gdrcd_query("SELECT * FROM inclinazione WHERE visibile > 0 ORDER BY id_inclinazione", 'result');
                while($result && $row = gdrcd_query($result, 'fetch')) { ?>
                <tr>
                    <td><img src="imgs/inclinazioni/<?php echo gdrcd_filter('out', $row['immagine']); ?>" /></td>
                    <td style="color: #8f8f8f;"><?php echo gdrcd_filter('out', $row['nome']); ?></td>
                    <td>
                        <?php if($inclinazione > 0 || $pg['esperienza'] < 10 || $pg['id_gilda'] > 0): ?>
                            Non puoi scegliere una corrente
                        <?php else: ?>
                            <form method="post" action="main.php?page=scegli_inclinazione">
                                <input type="submit" value="Scegli">
                                <input type="hidden" name="op" value="change">
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome']); ?>">
                                <input type="hidden" name="id_record" value="<?php echo (int)$row['id_inclinazione']; ?>">
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } // while
                if($result) gdrcd_query($result, 'free'); ?>
            </tbody>
        </table>

        <?php if($inclinazione > 0):
            // Recupera il nome della corrente del pg (non da $row che è fuori scope)
            $incl_corrente = gdrcd_query("SELECT nome FROM inclinazione WHERE id_inclinazione = $inclinazione LIMIT 1");
        ?>
        <br><br>
        <center>
            <form method="post" action="main.php?page=scegli_inclinazione">
                <input type="submit" value="Abbandona corrente" style="width:150px"/>
                <input type="hidden" name="op" value="quit" />
                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $incl_corrente['nome'] ?? ''); ?>" />
                <input type="hidden" name="id_record" value="<?php echo $inclinazione; ?>" />
            </form>
        </center>
        <?php endif; ?>

    <?php } // if op === '' — fine sezione 1


    /* ──────────── HANDLER: cambio/ingresso in una corrente ──────────── */
    if($op === 'change' && isset($_POST['id_record']) && $_POST['id_record'] !== '') {
        if($inclinazione > 0) {
            // Pg ha già una corrente: aggiorna
            gdrcd_query("UPDATE clgpersonaggioinclinazione SET id_ruolo = " . gdrcd_filter('num', $_POST['id_record']) . " WHERE personaggio = '$login_f'");
        } else {
            // Pg non ha ancora una corrente: inserisce
            gdrcd_query("INSERT INTO clgpersonaggioinclinazione (id_ruolo, personaggio) VALUES (" . gdrcd_filter('num', $_POST['id_record']) . ", '$login_f')");
        }
        // Notifica al referente
        $referente = gdrcd_query("SELECT referente FROM inclinazione WHERE id_inclinazione = " . gdrcd_filter('num', $_POST['id_record']) . " LIMIT 1");
        if(!empty($referente['referente'])) {
            send_sms('System', $referente['referente'], 'Notifica cambio corrente',
                'Messaggio automatico: ' . $_SESSION['login'] . ' si è unito alla tua corrente.');
        }
        // Ricarica $inclinazione dopo la modifica
        $inclinazione = (int)gdrcd_filter('num', $_POST['id_record']);
    }

    /* ──────────── HANDLER: abbandono corrente ──────────── */
    if($op === 'quit') {
        gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '$login_f'");
        $inclinazione = 0;
    }
    ?>

    <br><br>

    <?php /* ──────────── SEZIONE 2: ELENCO VIE MAGICHE ──────────── */
    if($op === '') {
        $check_corrente = gdrcd_query("SELECT inclinazione.tipo FROM clgpersonaggioinclinazione
                                       JOIN inclinazione ON clgpersonaggioinclinazione.id_ruolo = inclinazione.id_inclinazione
                                       WHERE clgpersonaggioinclinazione.personaggio = '$login_f' LIMIT 1");

        // Query vie magiche in base al tipo di corrente; $result = null se nessuna corrente
        $result_vie = null;
        if(!empty($check_corrente['tipo'])) {
            if($check_corrente['tipo'] == 1) {
                $result_vie = gdrcd_query("SELECT * FROM ruolo WHERE gilda IN (1, 5) ORDER BY gilda", 'result');
            } elseif($check_corrente['tipo'] == 2) {
                $result_vie = gdrcd_query("SELECT * FROM ruolo WHERE gilda IN (3, 4, 6) ORDER BY gilda", 'result');
            } elseif($check_corrente['tipo'] == 3) {
                $result_vie = gdrcd_query("SELECT * FROM ruolo WHERE gilda IN (2, 7) ORDER BY gilda", 'result');
            }
        }

        if($result_vie): ?>
        <table class="customTable">
            <thead><tr><th colspan="3">ELENCO VIE MAGICHE</th></tr></thead>
            <tbody>
                <tr class="second_header"><td></td><td>NOME</td><td></td></tr>
                <?php
                // Dati del pg calcolati una sola volta fuori dal loop (fix N+1)
                $can_pick = ($inclinazione > 0 && $pg['esperienza'] >= 10 && $pg['id_gilda'] == 0);

                while($row = gdrcd_query($result_vie, 'fetch')): ?>
                <tr>
                    <td><img src="imgs/guilds/<?php echo gdrcd_filter('out', $row['immagine']); ?>" /></td>
                    <td style="color: #8f8f8f;"><?php echo gdrcd_filter('out', $row['nome_ruolo']); ?></td>
                    <td>
                        <?php if(!$can_pick): ?>
                            Non hai abbastanza punti esperienza o hai già una gilda
                        <?php else: ?>
                            <form method="post" action="main.php?page=scegli_inclinazione">
                                <input type="submit" value="Scegli" />
                                <input type="hidden" name="op" value="pick_via" />
                                <input type="hidden" name="nome_lavoro" value="<?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>" />
                                <input type="hidden" name="id_record" value="<?php echo (int)$row['id_ruolo']; ?>" />
                                <input type="hidden" name="gilda" value="<?php echo (int)$row['gilda']; ?>" />
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile;
                gdrcd_query($result_vie, 'free'); ?>
            </tbody>
        </table>

        <?php if($pg['id_gilda'] > 0): ?>
        <br><br>
        <center>
            <form method="post" action="main.php?page=scegli_inclinazione">
                <input type="submit" value="Abbandona corrente" style="width:150px"/>
                <input type="hidden" name="op" value="exit" />
            </form>
        </center>
        <?php endif; ?>

        <?php endif; // $result_vie
    } // if op === '' — fine sezione 2


    /* ──────────── HANDLER: scelta via magica ──────────── */
    if($op === 'pick_via' && isset($_POST['id_record'], $_POST['gilda'])) {
        $check_incl   = gdrcd_query("SELECT COUNT(*) AS n FROM clgpersonaggioinclinazione WHERE personaggio = '$login_f'");
        $ha_inclinazione = (int)($check_incl['n'] ?? 0);

        if($ha_inclinazione === 1) {
            gdrcd_query("DELETE FROM clgpersonaggioinclinazione WHERE personaggio = '$login_f'");
            gdrcd_query("INSERT INTO clgpersonaggioruolo (id_ruolo, personaggio) VALUES (" . gdrcd_filter('num', $_POST['id_record']) . ", '$login_f')");
            gdrcd_query("UPDATE personaggio SET id_gilda = " . gdrcd_filter('num', $_POST['gilda']) . ", id_ruolo_gilda = " . gdrcd_filter('num', $_POST['id_record']) . ", shin = shin + 30 WHERE nome = '$login_f'");
        }
    }

    /* ──────────── HANDLER: uscita dalla famiglia/gilda ──────────── */
    if($op === 'exit') {
        gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE personaggio = '$login_f'");
        gdrcd_query("UPDATE personaggio SET id_gilda = 0, id_ruolo_gilda = 0, shin = 0,
                        car0 = car0-car1, car1 = 0,
                        car2 = car2-car3, car3 = 0,
                        car4 = car4-car5, car5 = 0,
                        car6 = car6-car7, car7 = 0,
                        car8 = car8-car9, car9 = 0
                        WHERE nome = '$login_f'");
        gdrcd_query("DELETE clgpersonaggioabilita FROM clgpersonaggioabilita
                     JOIN abilita ON clgpersonaggioabilita.id_abilita = abilita.id_abilita
                     WHERE clgpersonaggioabilita.nome = '$login_f'
                     AND abilita.tipo NOT IN ('Talento', 'Skill temporanea')");
        gdrcd_query("DELETE FROM log_spesa WHERE nome = '$login_f'");
    }
    ?>

    <br><br>
    <div class="panels_link"><a href="main.php?page=scegli_inclinazione">Torna indietro</a></div>

</div>

<script>
function Conferma() {
    return confirm('Sei sicuro? Una volta confermata non potrai più cambiarla!');
}
</script>
