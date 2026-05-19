<?php
$dont_check = true;
$check_for_update = false;

require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON') {
    require 'protezione.php';
}

require 'header.inc.php';
require 'includes/credits.inc.php';
?>
<link rel="stylesheet" href="themes/crystal/iscrizione_new.css">
<script>
function centreAlign() {
  let a = document.getElementById("fase_0").getBoundingClientRect().width / 100;
  document.documentElement.style.setProperty("--width", a + "px");
}
centreAlign()
</script>

<div class="pagina_iscrizione">
    <div class="page_title"><h2>
            <?php echo gdrcd_filter('out', $MESSAGE['register']['page_name']); ?>
        </h2></div>
    <div class="page_body">

        <?php /**** Fase 0 ****/
        if (isset($_POST['fase']) === false)
        { ?>

        <div id="fase_0">

        <div class="disclaimer">
        <?php echo gdrcd_filter('out', $MESSAGE['register']['disclaimer']); ?>
        </div>
        <br>
        <div class="info">
        <?php echo gdrcd_filter('out', $MESSAGE['register']['rules_read']); ?>
        </div>

        <div class="accetto">
        <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                          method="post">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="hidden" name="fase" value="1"/>
                            <input type="submit" style="width: 250px; margin: 0px auto;"
                                   value="<?php echo gdrcd_filter('out', $MESSAGE['register']['forms']['accept']); ?>"/>
                    </form>
        </div>

        </div>

        <?php } ?>



        <?php /**** Fase 1 ****/
        if (gdrcd_filter('get', $_POST['fase']) == 1)
        { ?>
            <div id="fase_uno">
            <div class="container_dettagli">
            <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                          method="post">

                        <!-- EMail -->
<span style="font-size:18px; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
E-MAIL:</span> &nbsp;
 <input style="width: 70%; height: 3%; border-radius: 10px; background-color: #181c31; border: 2px solid #07080e;" name="email" value="<?php echo gdrcd_filter('email', $_POST['email']); ?>"/>
 <br>
 <span style="font-size:12px; text-align: center; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
<?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['email_info']); ?></span>
<br>
                        <!-- Nome pg -->
<span style="font-size:18px; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
NOME:</span> &nbsp;
 <input style="width: 70%; height: 3%; border-radius: 10px; background-color: #181c31; border: 2px solid #07080e;" name="nome" value="<?php echo gdrcd_filter('out', $_POST['nome']); ?>"/>
 <br>
 <span style="font-size:12px; text-align: center; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
<?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['name_info']); ?></span>
<br>
                        <!-- Cognome pg -->
<span style="font-size:18px; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
COGNOME:</span> &nbsp;
 <input style="width: 70%; height: 3%; border-radius: 10px; background-color: #181c31; border: 2px solid #07080e;" name="cognome" value="<?php echo gdrcd_filter('out', $_POST['cognome']); ?>"/>
 <br>
 <span style="font-size:12px; text-align: center; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
<?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['name_info']); ?></span>
<br><br>

 <!-- Spirito -->
<span style="font-size:18px; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
SPIRITO (<a style="font-size: 18px; color: #8f8f8f; font-family: DejaVu Serif; font-weight:bold; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));" href="/statuti/spiriti/spiriti.html" target="_new">?</a>)</span> &nbsp;
<br>
<?php $result = gdrcd_query("SELECT id_razza, nome_razza FROM razza WHERE iscrizione=1 ORDER BY nome_razza",
                            'result'); ?>
                            <select name="razza" style="border-radius: 10px; background-color: #181c31; border: 2px solid #07080e;">
                                <?php while ($row = gdrcd_query($result, 'fetch'))
                                { ?>
                                    <option value="<?php echo $row['id_razza']; ?>" <?php if (gdrcd_filter('get',
                                            $_POST['razza']) == $row['id_razza']
                                    )
                                    {
                                        echo 'SELECTED';
                                    } ?>>
                                        <?php echo gdrcd_filter('out', $row['nome_razza']); ?>
                                    </option>
                                <?php } ?>
                            </select><br><br>

                             <!-- Mestiere -->
<span style="font-size:18px; color:#8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
MESTIERE (<a style="font-size: 18px; color: #8f8f8f; font-family: DejaVu Serif; font-weight:bold; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));" href="/statuti/mestieri/mestieri_info.html" target="_new">?</a>)</span> &nbsp;
<br>
<?php $result = gdrcd_query("SELECT id_ruolo, nome_ruolo FROM ruolo_mestiere WHERE livello_mestiere = 3 ORDER BY nome_ruolo",
                            'result'); ?>
                            <select name="mestiere" style="border-radius: 10px; background-color: #181c31; border: 2px solid #07080e;">
                                <?php while ($row = gdrcd_query($result, 'fetch'))
                                { ?>
                                    <option value="<?php echo $row['id_ruolo']; ?>" <?php if (gdrcd_filter('get',
                                            $_POST['nome_ruolo']) == $row['id_ruolo']
                                    )
                                    {
                                        echo 'SELECTED';
                                    } ?>>
                                        <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <br>

                             <!-- Genere -->
                        <div class="form_field" style="display: none">
                            <select name="genere">
                                <option value="m" <?php if (gdrcd_filter('get', $_POST['genere']) == 'm')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_m']); ?>
                                </option>
                                <option value="f" <?php if (gdrcd_filter('get', $_POST['genere']) == 'f')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_f']); ?>
                                </option>
                                <option value="b" <?php if (gdrcd_filter('get', $_POST['genere']) == 'b')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_b']); ?>
                                </option>
                            </select>
                        </div>

                        <!-- Invio -->
                            <input type="hidden" name="fase" value="2"/>
                            <input style="width: 80px;" type="submit"
                                   value="<?php echo gdrcd_filter('out', $MESSAGE['register']['forms']['next']); ?>"/>

                        </form>
           </div>
</div>
        <?php } ?>



        <?php /***** Fase 2 *****/
        if (gdrcd_filter('get', $_POST['fase']) == 2)
        {

            $ok = true;

            ?>

              <div id="fase_due">
              <div class="container_recap">

              <?php //controlli validità

               $result = gdrcd_query("SELECT email FROM personaggio WHERE email='" . gdrcd_filter('in',
                        $_POST['email']) . "' LIMIT 1", 'result');

                if (gdrcd_query($result, 'num_rows') > 0)
                {
                    gdrcd_query($result, 'free');
                    $ok = false;
                    echo '<div class="error">' . gdrcd_filter('out',
                            $MESSAGE['register']['error']['email_taken']) . '</div>';
                }

                if ((gdrcd_filter('get', $_POST['email']) == '') || (strpos(gdrcd_filter('get', $_POST['email']),
                            '@') == false) || (strpos(gdrcd_filter('get', $_POST['email']), '.') == false)
                )
                {
                    $ok = false;
                    echo '<div class="error">' . gdrcd_filter('out',
                            $MESSAGE['register']['error']['email_needed']) . '</div>';
                }


                $result = gdrcd_query("SELECT nome FROM personaggio WHERE nome='" . gdrcd_capital_letter(gdrcd_filter('get',
                        $_POST['nome'])) . "' LIMIT 1", 'result');

                if (gdrcd_query($result, 'num_rows') > 0)
                {
                    gdrcd_query($result, 'free');
                    $ok = false;
                    echo '<div class="error">' . gdrcd_filter('out',
                            $MESSAGE['register']['error']['name_taken']) . '</div>';
                }


                if ($ok == false)
                { ?>

                <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                              method="post">
                 <input type="hidden" name="fase" value="1"/>
                                <input type="hidden" name="email"
                                       value="<?php echo gdrcd_filter('out', $_POST['email']) ?>"/>
                                <input type="hidden" name="nome"
                                       value="<?php echo gdrcd_filter('out', $_POST['nome']) ?>"/>
                                <input type="hidden" name="cognome"
                                       value="<?php echo gdrcd_filter('out', $_POST['cognome']) ?>"/>
                                <input type="hidden" name="genere"
                                       value="<?php echo gdrcd_filter('out', $_POST['genere']) ?>"/>
                                <input type="hidden" name="razza"
                                       value="<?php echo gdrcd_filter('num', $_POST['razza']) ?>"/>
                                <input type="hidden" name="mestiere"
                                       value="<?php echo gdrcd_filter('num', $_POST['mestiere']) ?>"/>

                                <input type="submit" value="<?php echo gdrcd_filter('out',
                                    $MESSAGE['register']['forms']['try_again']); ?>"/>
                </form>

              <?php } else
                {

                    if ($_POST['genere'] == 'm')
                    {
                        $r_gen = 'm';
                    } else
                    {
                        $r_gen = 'f';
                    }

                    //sigla

                    if ($_POST['genere'] == 'm')
                    {
                        $sesso = 'Uomo';
                    } else if ($_POST['genere'] == 'f')
                    {
                        $sesso = 'Donna';
                    } else
                    {
                        $sesso = 'Non Binario';
                    }


                    //associo mestiere a ruolo

                    if ($_POST['mestiere'] == '1')
                    {
                        $lavoro = 0;
                        $nome_lavoro = 'Disoccupato';
                    } else if ($_POST['mestiere'] == '38')
                    {
                        $lavoro = 1;
                        $nome_lavoro = 'Operatore';
                    } else if ($_POST['mestiere'] == '76')
                    {
                        $lavoro = 2;
                        $nome_lavoro = 'Stagista della TAE';
                    } else if ($_POST['mestiere'] == '64')
                    {
                        $lavoro = 3;
                        $nome_lavoro = 'Apprendista del Magic Shop';
                    } else if ($_POST['mestiere'] == '83')
                    {
                        $lavoro = 4;
                        $nome_lavoro = 'Dipendente del Pandora';
                    } else if ($_POST['mestiere'] == '90')
                    {
                        $lavoro = 6;
                        $nome_lavoro = 'Parlamentare';
                    } else if ($_POST['mestiere'] == '12')
                    {
                        $lavoro = 6;
                        $nome_lavoro = 'Nobile di Corte';
                    }



                    $razza = gdrcd_query("SELECT sing_" . gdrcd_filter('in',
                            $r_gen) . " AS nome_razza FROM razza WHERE id_razza = " . (0 + gdrcd_filter('num',
                                $_POST['razza'])) . " LIMIT 1");

                    ?>

                    <table style="width: 250px;" class="customTable">
                            <tr>
                                <td class='casella_titolo'>
                                    <div class='titoli_elenco'><?php echo gdrcd_filter('out',
                                            $MESSAGE['register']['summary']) ?></div>
                                </td>
                            </tr>
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $_POST['email']) ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $_POST['nome']) ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $_POST['cognome']) ?>
                                    </div>
                                </td>
                            </tr>


                                    <div class='elementi_elenco' style="display: none">
                                        <?php echo gdrcd_filter('out', $sesso) ?>
                                    </div>

                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $razza['nome_razza']) . '&nbsp;' ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $nome_lavoro) . '&nbsp;' ?>
                                    </div>
                                </td>
                            </tr>
                            </table><br><br>
                        <table style="width: 250px;" class="customTable">
                        <tr>
                                <td class='casella_titolo'>
                                    <div class='titoli_elenco'><b>Talenti</b></div>
                                </td>
                            </tr>
                        <?php

                        $talento = gdrcd_query("SELECT * FROM abilita WHERE id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza'])) . "", 'result');
                        while ($row = gdrcd_query($talento, 'fetch')) {

                        ?>

                        <tr>
                                <td class='casella_elemento'>
                                    <div class='elementi_elenco'>
                                        <?php echo gdrcd_filter('out', $row['nome']) . '&nbsp;' ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                        </table>
                        <br>
                        <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                              method="post">
                                <input type="hidden" name="fase" value="3"/>
                                <input type="hidden" name="email"
                                       value="<?php echo gdrcd_filter('out', $_POST['email']) ?>"/>
                                <input type="hidden" name="nome"
                                       value="<?php echo gdrcd_filter('out', $_POST['nome']) ?>"/>
                                <input type="hidden" name="cognome"
                                       value="<?php echo gdrcd_filter('out', $_POST['cognome']) ?>"/>
                                <input type="hidden" name="genere"
                                       value="<?php echo gdrcd_filter('out', $_POST['genere']) ?>"/>
                                <input type="hidden" name="razza"
                                       value="<?php echo gdrcd_filter('num', $_POST['razza']) ?>"/>
                                <input type="hidden" name="mestiere"
                                       value="<?php echo gdrcd_filter('num', $_POST['mestiere']) ?>"/>

                                <input style="width: 80px;" type="submit"
                                       value="<?php echo gdrcd_filter('out', $MESSAGE['register']['forms']['ok']); ?>"/>
                        </form>

                        <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                              method="post">
                                <input type="hidden" name="fase" value="1"/>
                                <input type="hidden" name="email"
                                       value="<?php echo gdrcd_filter('out', $_POST['email']) ?>"/>
                                <input type="hidden" name="nome"
                                       value="<?php echo gdrcd_filter('out', $_POST['nome']) ?>"/>
                                <input type="hidden" name="cognome"
                                       value="<?php echo gdrcd_filter('out', $_POST['cognome']) ?>"/>
                                <input type="hidden" name="genere"
                                       value="<?php echo gdrcd_filter('out', $_POST['genere']) ?>"/>
                                <input type="hidden" name="razza"
                                       value="<?php echo gdrcd_filter('num', $_POST['razza']) ?>"/>
                                <input type="hidden" name="mestiere"
                                       value="<?php echo gdrcd_filter('num', $_POST['mestiere']) ?>"/>

                                <input style="width: 80px;" type="submit" value="<?php echo gdrcd_filter('out',
                                    $MESSAGE['register']['forms']['back']); ?>"/>
                        </form>
                    </div>
                <?php } ?>
            </div>
              </div>
              </div>


        <?php } ?>



        <?php /***** Fase 3 *****/
        if ($_POST['fase'] == 3)
        {
        ?>
        <div id="fase_due">
        <div class="container_recap">
        <?php
        //associo mestiere a ruolo

                    if ($_POST['mestiere'] == '1')
                    {
                        $lavoro = 0;
                    } else if ($_POST['mestiere'] == '38')
                    {
                        $lavoro = 1;
                    } else if ($_POST['mestiere'] == '76')
                    {
                        $lavoro = 2;
                    } else if ($_POST['mestiere'] == '64')
                    {
                        $lavoro = 3;
                    } else if ($_POST['mestiere'] == '83')
                    {
                        $lavoro = 4;
                    } else if ($_POST['mestiere'] == '90')
                    {
                        $lavoro = 6;
                    } else if ($_POST['mestiere'] == '12')
                    {
                        $lavoro = 6;
                    }

        if ((gdrcd_filter('num', $_POST['car0']) + gdrcd_filter('num', $_POST['car1']) + gdrcd_filter('num',
                    $_POST['car2']) + gdrcd_filter('num', $_POST['car3']) + gdrcd_filter('num',
                    $_POST['car4']) + gdrcd_filter('num', $_POST['car5'])) == '360'
        )
        {
            echo '<div class="error">' . gdrcd_filter('out',
                    $MESSAGE['register']['fields']['stats_info'] . ' ' . $PARAMETERS['settings']['cars_sum']) . '</div>';
        } else
        {

            $pass = gdrcd_genera_pass();

            $lastpasschange_field = "";
            $lastpasschange_value = "";

            if ($PARAMETERS['mode']['alert_password_change'] == 'ON' && $PARAMETERS['settings']['alert_password_change']['alert_from_signup'] == 'OFF')
            {
                $lastpasschange_field = ", ultimo_cambiopass";
                $lastpasschange_value = ", NOW()";
            }

                gdrcd_query("INSERT INTO personaggio (nome, cognome, pass, data_iscrizione, email, sesso, id_razza, id_mestiere, id_ruolo_mestiere, url_img, url_img_chat, car0, car1, car2, car3, car4, car5, car6, car7, car8, car9, shin, salute, salute_max, soldi, punto_razza, esperienza_mestiere, esperienza $lastpasschange_field) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['nome']))) . "', '" . trim(gdrcd_filter('in', $_POST['cognome'])) . "', '" . gdrcd_encript($pass) . "', NOW(), '" . gdrcd_filter('in', $_POST['email']) . "', '" . gdrcd_filter('in', $_POST['genere']) . "', " . gdrcd_filter('num', $_POST['razza']) . ", " . gdrcd_filter('num', $lavoro) . ", " . gdrcd_filter('num', $_POST['mestiere']) . ", 'http://crystaltokyogdr.altervista.org/imgs/avatars/avatar_empty.png', 'http://crystaltokyogdr.altervista.org/imgs/avatars/avatar_mini_empty.png', '10.0', '0', '10.0', '0', '10.0', '0', '10.0', '0', '10.0', '0', '0', " . gdrcd_filter('num', $PARAMETERS['settings']['max_hp']) . ", " . gdrcd_filter('num', $PARAMETERS['settings']['max_hp']) . ", " . gdrcd_filter('num',  $PARAMETERS['settings']['first_money']) . ", '0.0', " . gdrcd_filter('num',  $PARAMETERS['settings']['first_px_job']) . ", " . gdrcd_filter('num',  $PARAMETERS['settings']['first_px']) . " $lastpasschange_value)");
                gdrcd_query("INSERT INTO clgpersonaggiomestiere (personaggio, id_ruolo) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['nome']))) . "', " . gdrcd_filter('num', $_POST['mestiere']) . ")");

                $talento_done = gdrcd_query("SELECT * FROM abilita WHERE id_razza = " . (0 + gdrcd_filter('num',
                                                $_POST['razza'])) . "", 'result');
                while ($row = gdrcd_query($talento_done, 'fetch')) {
                gdrcd_query("INSERT INTO clgpersonaggioabilita (nome, id_abilita, grado) VALUES ('" . trim(gdrcd_capital_letter(gdrcd_filter('in', $_POST['nome']))) . "', " . gdrcd_filter('num', $row['id_abilita']) . ", '1')");
                }

            echo '<div class="page_title_finale"</div><img src="themes/crystal/imgs/iscrizione/iscrizione_completata.png"></div><br>';

            if ($PARAMETERS['mode']['emailconfirmation'] == 'ON') {
                echo '<div class="page_title"><h2>' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message']['ok']) . '</h2></div>';
                echo '<div class="panels_box"><div class="welcome_message">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][0]) . ' <b>' . gdrcd_filter('out',
                        $PARAMETERS['info']['site_name']) . '</b> ' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][1]) . '</div><div class="welcome_message">&nbsp;</div><div class="username">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][3]) . ' <b>' . gdrcd_filter('get',
                        $_POST['email']) . '</b><br>
                        <b><u>Password (ti invitiamo a cambiarla)</u></b>: '. $pass .'</div>';

                $text = $MESSAGE['register']['welcome']['message'][0] . ' ' . $PARAMETERS['info']['site_name'] . "\n\n " . $MESSAGE['register']['welcome']['message'][1] . "\n     " . $MESSAGE['register']['welcome']['message'][2] . "\n\n    " . $MESSAGE['register']['welcome']['message']['user'] . ' ' . gdrcd_filter('get',
                        $_POST['nome']) . "\n" . $MESSAGE['register']['welcome']['message']['pass'] . ' ' . $pass . "\n\n    " . $PARAMETERS['info']['webmaster_name'];

                $subject = $PARAMETERS['info']['site_name'] . ' - Registrazione di ' . gdrcd_filter('get',
                        $_POST['nome']) . ' ' . gdrcd_filter('get', $_POST['cognome']);

                mail(gdrcd_filter('get', $_POST['email']), $subject, $text,
                    'From: ' . gdrcd_filter('out', $PARAMETERS['info']['webmaster_email']));

            } else {

                echo '<div class="page_title"><h2>' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message']['ok']) . '</h2></div>';
                echo '<div class="panels_box"><div class="welcome_message">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][0]) . ' <b>' . gdrcd_filter('out',
                        $PARAMETERS['info']['site_name']) . '</b> ' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][1]) . '</div><div class="welcome_message">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message'][2]) . '</div><div class="username">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message']['user']) . ' <b>' . gdrcd_filter('get',
                        $_POST['nome']) . '</b></div><div class="username">' . gdrcd_filter('out',
                        $MESSAGE['register']['welcome']['message']['pass']) . ' <b>' . $pass . '</b></div></div>';

            }

        }//else

        ?>

    </div>

    <div class="link_back">
        <a href="index.php">
            <?php echo gdrcd_filter('out', $MESSAGE['register']['welcome']['back'] . ' ' . gdrcd_filter('out',
                    strtolower($PARAMETERS['info']['homepage_name']))); ?>
        </a>
    </div>
    </div>
    </div>

    <?php } ?>

</div>
</div>

<?php
$scripts = $scripts ?? [];
require 'footer.inc.php';
?>
