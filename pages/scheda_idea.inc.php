<?php
// Se non è stato specificato il nome del pg
if (!isset($_REQUEST['pg'])) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
    exit();
}

$pg = gdrcd_filter('in', $_REQUEST['pg']);

// Query principale personaggio con join
$query = "SELECT personaggio.*, razza.sing_m, razza.sing_f, razza.id_razza, razza.bonus_car0, razza.bonus_car1, razza.bonus_car2, razza.bonus_car3, razza.bonus_car4, razza.bonus_car5, gilda.nome as nome_gilda, ruolo.nome_ruolo, mestiere.nome as nome_mestiere, ruolo_mestiere.nome_ruolo as nome_ruolo_mestiere, ruolo.immagine as immagine_famiglia, ruolo_mestiere.immagine as immagine_mestiere FROM personaggio LEFT JOIN razza ON personaggio.id_razza=razza.id_razza LEFT JOIN gilda ON personaggio.id_gilda = gilda.id_gilda LEFT JOIN ruolo ON personaggio.id_ruolo_gilda = ruolo.id_ruolo LEFT JOIN mestiere ON mestiere.id_mestiere = mestiere.id_mestiere LEFT JOIN ruolo_mestiere ON personaggio.id_ruolo_mestiere = ruolo_mestiere.id_ruolo WHERE personaggio.nome = '".$pg."'";

$personaggi = gdrcd_query($query, 'result');

if (gdrcd_query($personaggi, 'num_rows') == 0) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']).'</div>';
    exit();
}

$personaggio = gdrcd_query($personaggi, 'fetch');
gdrcd_query($personaggi, 'free');

// Cariche
$cariche_q = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".$pg."'", 'result');
$cariche = gdrcd_query($cariche_q, 'fetch');
gdrcd_query($cariche_q, 'free');

// Ultima giocata libera
$ultima_role_q = gdrcd_query("SELECT data_evento FROM Punti WHERE nome = '".$pg."' AND commento LIKE 'Giocata Libera - %' ORDER BY data_evento DESC LIMIT 1", 'result');
$ultima_role = (gdrcd_query($ultima_role_q, 'num_rows') > 0) ? gdrcd_query($ultima_role_q, 'fetch')['data_evento'] : '-';
gdrcd_query($ultima_role_q, 'free');

// Ultima quest
$ultima_quest_q = gdrcd_query("SELECT data_evento FROM Punti WHERE nome = '".$pg."' AND id_messaggio != 0 AND esperienza > 1 ORDER BY data_evento DESC LIMIT 1", 'result');
$ultima_quest = (gdrcd_query($ultima_quest_q, 'num_rows') > 0) ? gdrcd_query($ultima_quest_q, 'fetch')['data_evento'] : '-';
gdrcd_query($ultima_quest_q, 'free');

$io_stesso = ($_SESSION['login'] == $pg);
$is_admin = ($_SESSION['admin'] == 1);
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Scheda di <?php echo gdrcd_filter('out', $pg); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #111;
      font-family: 'Lato', sans-serif;
      color: #ccc;
      margin: 0;
      padding: 0;
    }

    .scheda_container {
      display: flex;
      padding: 30px;
      gap: 30px;
    }

    .avatar_column {
      width: 300px;
    }

    .avatar {
      width: 300px;
      height: 400px;
      object-fit: cover;
      display: block;
      margin-bottom: 20px;
    }

    .timeline_box {
      width: 300px;
      height: 400px;
      background-color: #1a1a1a;
      padding: 20px;
      box-sizing: border-box;
      position: relative;
    }

    .timeline_box::before {
      content: '';
      position: absolute;
      left: 25px;
      top: 10px;
      bottom: 10px;
      width: 2px;
      background-color: #666;
    }

    .timeline_item {
      position: relative;
      margin-left: 40px;
      margin-bottom: 30px;
    }

    .timeline_item::before {
      content: '';
      position: absolute;
      left: -25px;
      top: 4px;
      width: 12px;
      height: 12px;
      background-color: #ce846f;
      border-radius: 50%;
    }

    .timeline_item .label {
      font-weight: bold;
      color: #ce846f;
      margin-right: 5px;
    }

    .right_panel {
      flex: 1;
    }

    .nome_pg {
      font-size: 32px;
      color: #ce846f;
      margin-bottom: 5px;
    }

    .famiglia {
      font-size: 18px;
      color: #999;
      margin-bottom: 20px;
    }

    .sms_button {
      display: inline-block;
      background-color: #ce846f;
      color: #fff;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      margin-bottom: 20px;
    }

    .section_title {
      font-weight: bold;
      margin-top: 20px;
      margin-bottom: 10px;
      color: #ce846f;
      font-size: 20px;
    }

    .stat_block, .text_block {
      background-color: #1a1a1a;
      padding: 10px 15px;
      margin-bottom: 20px;
    }

    .stat_item {
      display: inline-block;
      width: 48%;
      margin: 1% 1%;
    }
  </style>
</head>
<body>
<div class="scheda_container">
  <div class="avatar_column">
    <img src="<?php echo gdrcd_filter('out', $personaggio['url_img']); ?>" class="avatar" alt="Avatar">
    <div class="timeline_box">
      <div class="timeline_item"><span class="label">Iscr.</span><span class="value"><?php echo gdrcd_format_date($personaggio['data_iscrizione']); ?></span></div>
      <div class="timeline_item"><span class="label">Ult. log.</span><span class="value"><?php echo gdrcd_format_date($personaggio['ultimo_login']); ?></span></div>
      <div class="timeline_item"><span class="label">Ult. az.</span><span class="value"><?php echo gdrcd_format_date($personaggio['ultimo_refresh']); ?></span></div>
      <div class="timeline_item"><span class="label">Ult. quest</span><span class="value"><?php echo ($ultima_quest != '-') ? gdrcd_format_date($ultima_quest) : '-'; ?></span></div>
      <div class="timeline_item"><span class="label">Ult. zona</span><span class="value"><?php echo ($ultima_role != '-') ? gdrcd_format_date($ultima_role) : '-'; ?></span></div>
    </div>
  </div>

  <div class="right_panel">
    <div class="nome_pg"><?php echo gdrcd_filter('out', $personaggio['nome']); ?></div>
    <div class="famiglia"><?php echo gdrcd_filter('out', $personaggio['affiliazione']); ?> • <?php echo gdrcd_filter('out', $personaggio['titolo']); ?></div>
    <a href="main.php?page=messages_center&op=create&reply_dest=<?php echo urlencode($pg); ?>" class="sms_button">Invia SMS</a>

    <div class="section_title">Statistiche</div>
    <div class="stat_block">
      <div class="stat_item">Forza: <?php echo $personaggio['forza']; ?></div>
      <div class="stat_item">Potere: <?php echo $personaggio['potere']; ?></div>
      <div class="stat_item">Mente: <?php echo $personaggio['mente']; ?></div>
      <div class="stat_item">Tempra: <?php echo $personaggio['tempra']; ?></div>
      <div class="stat_item">Destrezza: <?php echo $personaggio['destrezza']; ?></div>
    </div>

    <div class="section_title">Salute e Integrità</div>
    <div class="stat_block">
      <div class="stat_item">Salute: <?php echo $personaggio['salute']; ?></div>
      <div class="stat_item">Integrità: <?php echo $personaggio['integrita']; ?></div>
    </div>

    <div class="section_title">Background</div>
    <div class="text_block"><?php echo nl2br(gdrcd_filter('out', $personaggio['background'])); ?></div>

    <div class="section_title">Note del Fato</div>
    <div class="text_block"><?php echo nl2br(gdrcd_filter('out', $personaggio['osservazioni'])); ?></div>

    <div class="section_title">Esperienze</div>
    <div class="stat_block">
      <div class="stat_item">Exp Totale: <?php echo $personaggio['esperienza']; ?></div>
      <div class="stat_item">Notorietà: <?php echo $personaggio['notorieta']; ?></div>
      <div class="stat_item">Mestiere: <?php echo $personaggio['mestiere']; ?></div>
      <div class="stat_item">Shin: <?php echo $personaggio['shin']; ?></div>
    </div>

    <div class="section_title">Cariche</div>
    <div class="stat_block">
      <?php if ($cariche['admin']) echo '<div class="stat_item">Admin</div>'; ?>
      <?php if ($cariche['master']) echo '<div class="stat_item">Master</div>'; ?>
      <?php if ($cariche['moderatore']) echo '<div class="stat_item">Moderatore</div>'; ?>
      <?php if ($cariche['capogilda']) echo '<div class="stat_item">Capogilda</div>'; ?>
      <?php if ($cariche['capomestiere']) echo '<div class="stat_item">Capomestiere</div>'; ?>
      <?php if ($cariche['guida']) echo '<div class="stat_item">Guida</div>'; ?>
      <?php if ($cariche['grafico']) echo '<div class="stat_item">Grafico</div>'; ?>
    </div>
  </div>
</div>
</body>
</html>