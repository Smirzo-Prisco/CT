<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once(__DIR__ . '../../includes/custom_functions.inc.php');

// Definizione menu con permessi
$menu = [
  'gestione_pg' => [
      'label' => 'Gestione pg',
      'icon'  => 'fa-user-gear',
      // 'url'   => 'gestione_pg.php',
      'permessi' => ['admin', 'master', 'moderatore', 'custode'],
      'voci' => [
          ['label'=>'Personaggi',
            'url'=>'main.php?page=gestione_personaggio',
            'permessi' => ['admin', 'master', 'moderatore', 'custode']
          ],
          ['label'=>'Azzera punti', // spostare in gestione pg
            'url'=>'main.php?page=gestione_azzeramento_skill',
            'permessi' => ['admin']
          ],
          ['label'=>'Crea e assegna skill temporanee',
            'url'=>'main.php?page=main.php?page=gestione_abilita_master',
            'permessi' => ['admin', 'master']
          ]
          // ['label'=>'Cambia nome','url'=>'main.php?page=gestione_cambio_nome'], // spostare in modifica pg
          // ['label'=>'Cambia spirito','url'=>'main.php?page=gestione_cambio_razza'], // spostare in modifica pg
      ]
  ],
  'gilde' => [
      'label' => 'Gilde',
      'icon'  => 'fa-users',
      'permessi' => ['admin', 'capogilda'],
      'voci' => [
          ['label'=>'Gilde e gradi',
            'url'=>'main.php?page=gestione_gilde',
            'permessi' => ['admin', 'capogilda']
          ],
          ['label'=>'Famiglie indipendenti',
            'url'=>'main.php?page=gestione_gilde&op=edit&id_record=-1',
            'permessi' => ['admin']
          ],
          ['label'=>'Correnti',
            'url'=>'main.php?page=gestione_tipi&types=guilds',
            'permessi' => ['admin']
          ],
          ['label'=>'Reliquie',
            'url'=>'main.php?page=punti_png',
            'permessi' => ['admin']
          ]
      ]

  ],
  'razze' => [
      'label' => 'Razze',
      'icon'  => 'fa-users',
      // 'url'   => 'gilde.php',
      'permessi' => ['admin'],
      'voci' => [
          ['label'=>'Razze e spiriti',
            'url'=>'main.php?page=gestione_razze',
            'permessi' => ['admin']
          ]
      ]
  ],
  'mestieri' => [
      'label' => 'Mestieri',
      'icon'  => 'fa-briefcase',
      // 'url'   => 'mestieri.php',
      'permessi' => ['admin', 'master'],
      'voci' => [
          ['label'=>'Mestieri',
            'url'=>'main.php?page=gestione_mestieri',
            'permessi' => ['admin']
          ],
          ['label'=>'Assegna mestiere',
            'url'=>'main.php?page=gestione_mestiere',
            'permessi' => ['admin']
          ],
          ['label'=>'Lavori indipendenti',
            'url'=>'main.php?page=gestione_mestieri&op=edit&id_record=-1',
            'permessi' => ['admin']
          ],
          ['label'=>'Statuti',
            'url'=>'main.php?page=gestione_statuti_new',
            'permessi' => ['admin', 'capogilda']
          ]
      ]
  ],
  'oggetti' => [
      'label' => 'Oggetti',
      'icon'  => 'fa-box',
      'url'   => 'main.php?page=gestione_oggetti',
      'permessi' => ['admin', 'moderatore', 'master', 'capogilda', 'capomestiere', 'magic'], // moderatore, guida, grafico
      'voci' => [
          ['label'=>'Oggetti',
            'url'=>'main.php?page=gestione_oggetti',
            'permessi' => ['admin', 'moderatore', 'master', 'capogilda', 'capomestiere', 'magic']
          ],
          ['label'=>'Ricarica oggetto',
            'url'=>'main.php?page=oggetto_ricarica',
            'permessi' => ['admin', 'moderatore', 'master', 'magic']
          ],
          ['label'=>'Tipi di oggetto',
            'url'=>'main.php?page=gestione_tipi&types=items',
            'permessi' => ['admin']
          ]
      ]

  ],
  'strumenti' => [
      'label' => 'Strumenti',
      'icon'  => 'fa-wrench',
      // 'url'   => 'strumenti.php',
      'permessi' => ['admin'],
      'voci' => [
          ['label'=>'Assegna ruoli apicali',
            'url'=>'main.php?page=gestione_nomine',
            'permessi' => ['admin']
          ],
          ['label'=>'CT news',
            'url'=>'main.php?page=gestione_ctnews',
            'permessi' => ['admin']
          ],
          ['label'=>'Bacheche',
            'url'=>'main.php?page=gestione_bacheche',
            'permessi' => ['admin']
          ],
          ['label'=>'Luoghi',
            'url'=>'main.php?page=gestione_luoghi',
            'permessi' => ['admin']
          ],
          ['label'=>'Mappa',
            'url'=>'main.php?page=gestione_mappe',
            'permessi' => ['admin']
          ],
          ['label'=>'Regolamento',
            'url'=>'main.php?page=gestione_regolamento',
            'permessi' => ['admin']
          ],
          ['label'=>'Manutenzione',
            'url'=>'main.php?page=gestione_manutenzione',
            'permessi' => ['admin']
          ]
      ]
  ],
  'log' => [
      'label' => 'Log',
      'icon'  => 'fa-file-lines',
      // 'url'   => 'log.php',
      'permessi' => ['admin', 'master', 'capomestiere', 'moderatore'],
      'voci' => [
          ['label'=>'Tutti i log',
            'url'=>'main.php?page=log',
            'permessi' => ['admin']
          ],
          ['label'=>'Richiesta log chat',
            'url'=>'main.php?page=richiesta_log',
            'permessi' => ['admin', 'master']
          ],
          ['label'=>'Log chat',
            'url'=>'main.php?page=log_chat',
            'permessi' => ['admin', 'moderatore']
          ]
      ]
  ],
  'stats' => [
      'label' => 'Informazioni',
      'icon'  => 'fa-chart-line',
      'permessi' => ['user'],
      'voci' => [
          ['label'=>'Contatta la moderazione',
            'url'=>'main.php?page=contatta_moderazione',
            'permessi' => ['user']
          ],
          ['label'=>'Iscritti: '.gdrcd_query("SELECT Count(nome) AS num FROM personaggio")['num'],
            'url'=>'#',
            'permessi' => ['user']
          ],
          ['label'=>'Esiliati: '.gdrcd_query("SELECT Count(nome) AS num FROM personaggio WHERE esilio > NOW()")['num'],
            'url'=>'#',
            'permessi' => ['user']
          ],
          ['label'=>'Post in bacheca: '.gdrcd_query("SELECT Count(id_messaggio) AS num FROM messaggioaraldo")['num'],
            'url'=>'#',
            'permessi' => ['user']
          ],
          ['label'=>'Azioni settimana: '.gdrcd_query("SELECT COUNT(id) AS num FROM chat WHERE ora > DATE_SUB(NOW(), INTERVAL 7 DAY)")['num'],
            'url'=>'main.php?page=gestione_azzeramento_skill',
            'permessi' => ['user']
          ]
          // ['label'=>'SMS ON settimana: '.gdrcd_query("SELECT COUNT(id) AS num FROM messaggi WHERE spedito > DATE_SUB(NOW(), INTERVAL 7 DAY) AND tipo = 'on'")['num'],'url'=>'main.php?page=gestione_azzeramento_pg']
      ]
  ],
  'ultimi_iscritti' => [
      'label' => 'Informazioni',
      'icon'  => 'fa-users',
      'permessi' => ['user'],
      'voci' => [
        ['label'=>'Ultimi iscritti:',
          'url'=>'#',
          'permessi' => ['user']
        ]
      ]
  ]
];

// aggiungo gli ultimi iscritti alla card
$result = gdrcd_query("SELECT nome, cognome FROM personaggio WHERE data_iscrizione > DATE_SUB(NOW(), INTERVAL 7 DAY)", 'result');
while($row = gdrcd_query($result, 'fetch')) $menu['ultimi_iscritti']['voci'][] = ['label'=>$row['nome']." ".$row['cognome'],'url'=>"main.php?page=scheda&pg=".$row['nome'], 'permessi' => ['user']];
?>

<link rel="stylesheet" href="../themes/crystal/uffici_nuovi.css">

<div class="pagina_gestione">
  <header>⚙️ Pannello di Gestione</header>
  <div class="dashboard">
    <?php foreach ($menu as $item): ?>
      <?php if (hasPermesso($_SESSION, $item['permessi'])): ?>
        <div class="card">
<!--
          <a href="<?= $item['url'] ?>">
            <i class="fa-solid <?= $item['icon'] ?>"></i>
            <h3><?= $item['label'] ?></h3>
          </a>
-->
          <i class="fa-solid <?= $item['icon'] ?>"></i>
          <?php if (!empty($item['voci'])): ?>
            <ul class="sub-menu">
            <?php foreach ($item['voci'] as $voce):
                    if (hasPermesso($_SESSION, $voce['permessi'])): ?>
                      <li><a href="<?= $voce['url'] ?>"><?= $voce['label'] ?></a></li>
              <?php endif;
                endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
</div>

<!------------------- VECCHIO CODICE ---------------------------------->
<!------------------- VECCHIO CODICE ---------------------------------->
<!------------------- VECCHIO CODICE ---------------------------------->
<?php // die(); ?>

    <div class="page_title"><h2><?php echo gdrcd_filter('out', $PARAMETERS['administration_page_name']); ?></h2></div>
    <div class="page_body">
      <?php
        $mestiere = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");
        
        switch ($mestiere['id_mestiere']) {
          case 3: $intestazione = "SHIROKURO MAGIC SHOP"; break;
          case 4: $intestazione = "SECRET PANDORA"; break;
          default: $intestazione = "NEGOZIO"; break;
        }
        
        if(gdrcd_filter('get', $_REQUEST['op']) == 'admin') {
          if($_SESSION['admin'] != 1) echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
          else {
      ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI GESTIONE</td></tr>
        <tr><td><a href="main.php?page=gestione_abilita_gilde" alt="_top">GESTIONE ABILIT&Agrave;</a></td></tr>
        <tr><td><a href="main.php?page=gestione_abilita" alt="_top">GESTIONE TALENTI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_ctnews" alt="_top">CT NEWS</a></td></tr>
        <tr><td><a href="main.php?page=gestione_bacheche" alt="_top">GESTIONE BACHECHE</a></td></tr>
        <tr><td><a href="main.php?page=gestione_luoghi" alt="_top">GESTIONE LUOGHI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_gilde" alt="_top">GESTIONE GILDE E RUOLI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_mestieri" alt="_top">GESTIONE MESTIERI E RUOLI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_inclinazione" alt="_top">GESTIONE INCLINAZIONI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_mappe" alt="_top">GESTIONE MAPPE</a></td></tr>
        <tr><td><a href="main.php?page=gestione_mercato" alt="_top">GESTIONE OGGETTI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_nomine" alt="_top">GESTIONE PERMESSI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_razze" alt="_top">GESTIONE RAZZE</a></td></tr>
        <tr><td><a href="main.php?page=gestione_tutta_famiglia" alt="_top">NOMINA CAPOFAMIGLIA</a></td></tr>
        <tr><td><a href="main.php?page=gestione_tutta_mestiere" alt="_top">NOMINA CAPOMESTIERE</a></td></tr>
      </table>
      <br><br>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI ADMIN</td></tr>
        <tr><td><a href="main.php?page=gestione_regolamento" alt="_top">MODIFICA REGOLAMENTO</a></td></tr>
        <tr><td><a href="main.php?page=gestione_statuti_new" alt="_top">MODIFICA STATUTI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_manutenzione" alt="_top">MANUTENZIONE</a></td></tr>
        <tr><td><a href="main.php?page=gestione_stats_skills" alt="_top">CONTROLLO STATS/SKILL PERSONAGGI</a></td></tr>
      </table>
      <br><br>
      <table class="customTable">
        <tr class="second_header"><td>GESTIONE PERSONAGGI</td></tr>
        <tr><td><a href="main.php?page=gestione_personaggio" alt="_top">GESTIONE PERSONAGGIO</a></td></tr>
        <tr><td><a href="main.php?page=erase_pg" alt="_top">CANCELLA PG INATTIVI</a></td></tr>
        <tr><td><a href="main.php?page=erase_inactive" alt="_top">CANCELLA PG MAI ENTRATI</a></td></tr>
        <tr><td><a href="main.php?page=erasepg_scelta" alt="_top">CANCELLA PERSONAGGIO</a></td></tr>
        <tr><td><a href="main.php?page=punti_png" alt="_top">POTENZIA PNG</a></td></tr>
        <tr><td><a href="main.php?page=gestione_esilio" alt="_top">ESILIA PG</a></td></tr>
        <tr><td><a href="main.php?page=gestione_cambio_nome" alt="_top">CAMBIA NOME A PG</a></td></tr>
        <tr><td><a href="main.php?page=gestione_cambio_razza" alt="_top">CAMBIA SPIRITO A PG</a></td></tr>
        <tr><td><a href="main.php?page=gst_personaggio_skill" alt="_top">GESTIONE SKILL PG</a></td></tr>
        <tr><td><a href="main.php?page=gestione_azzeramento_skill" alt="_top">AZZERA SKILL PG</a></td></tr>
        <tr><td><a href="main.php?page=gestione_azzeramento_pg" alt="_top">AZZERA PG</a></td></tr>
      </table>
      <br><br>
      <table class="customTable">
        <tr class="second_header"><td>LOG</td></tr>
        <tr><td><a href="main.php?page=log_chat" alt="_top">LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=log_eventi" alt="_top">LOG GENERALI</a></td></tr>
        <tr><td><a href="main.php?page=log_msg" alt="_top">LOG MESSAGGI</a></td></tr>
        <tr><td><a href="main.php?page=log_doppi" alt="_top">LOG DOPPI</a></td></tr>     
        <?php // if ($_SESSION['login'] == 'Lii') { ?>
        <tr><td><a href="main.php?page=log_entrate" alt="_top">LOG ENTRATE</a></td></tr>  
        <?php // } ?>
      </table>
      <br><br>
      <table class="customTable">
        <tr class="second_header"><td>GESTIONE OGGETTI</td></tr>
        <tr><td><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_modifica" alt="_top">MODIFICA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA OGGETTO</a></td></tr> 
        <tr><td><a href="main.php?page=oggetto_approvazione" alt="_top">APPROVA/NEGA OGGETTO</a></td></tr>   
      </table>
      <br><br>
  <?php
    }
  } 
    
  if(gdrcd_filter('get', $_REQUEST['op']) == 'master') {
    if ($_SESSION['admin'] != 1 && $_SESSION['master'] != 1) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    else {
    ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI MASTER</td></tr>
        <tr><td><a href="main.php?page=richiesta_log" alt="_top">RICHIESTA LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=log_chat" alt="_top">LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=gestione_personaggio" alt="_top">GESTISCI PERSONAGGIO</a></td></tr>
        <tr><td><a href="main.php?page=punti_png" alt="_top">GESTIONE OGGETTO DI GILDA</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_modifica" alt="_top">MODIFICA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=gestione_abilita_master" alt="_top">CREA E ASSEGNA SKILL TEMPORANEE</a></td></tr>
        <tr><td><a href="main.php?page=gestione_ctnews" alt="_top">CT NEWS</a></td></tr>
      </table>
      <br><br>
    <?php
    }     
  } 
    
  if(gdrcd_filter('get', $_REQUEST['op']) == 'moderatore') {
    if ($_SESSION['admin'] != 1 && $_SESSION['moderatore'] != 1) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    else {
    ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI MODERATORE</td></tr>
        <tr><td><a href="main.php?page=richiesta_log" alt="_top">RICHIESTA LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=log_eventi" alt="_top">LOG GENERALI</a></td></tr>
        <tr><td><a href="main.php?page=log_doppi" alt="_top">LOG DOPPI</a></td></tr>
        <tr><td><a href="main.php?page=gestione_esilio" alt="_top">ESILIA PERSONAGGIO</a></td></tr>
        <tr><td><a href="main.php?page=contatta_moderatore_elenco" alt="_top">RISPONDI A SEGNALAZIONE</a></td></tr>
        <tr><td><a href="main.php?page=log_chat" alt="_top">LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=sms_moderatore" alt="_top">SCRIVI COME MODERATORE A UTENTE</a></td></tr>
      </table>
      <br><br>
  <?php
    }     
  } 
    
  if(gdrcd_filter('get', $_REQUEST['op']) == 'capogilda') {
    if ($_SESSION['admin'] != 1 && $_SESSION['capogilda'] != 1) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    else {
    ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI CAPOGILDA</td></tr>
        <tr><td><a href="main.php?page=gestione_famiglia" alt="_top">GESTIONE FAMIGLIA</a></td></tr>
        <tr><td><a href="main.php?page=punti_png" alt="_top">GESTIONE OGGETTO DI GILDA</a></td></tr>
        <tr><td><a href="main.php?page=gestione_abilita_gilde" alt="_top">CREA SKILL</a></td></tr>
        <tr><td><a href="main.php?page=log_chat" alt="_top">LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=gestione_statuti_new">GESTIONE STATUTI</a></td></tr>
      </table>
      <br><br>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI OGGETTO</td></tr>
        <tr><td><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_modifica" alt="_top">MODIFICA OGGETTO</a></td></tr>
      </table>
  <?php
    } 
  } 
    
  if(gdrcd_filter('get', $_REQUEST['op']) == 'capomestiere') {
    if ($_SESSION['admin'] != 1 && $_SESSION['capomestiere'] != 1) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    else {
    ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI CAPOMESTIERE</td></tr>
        <tr><td><a href="main.php?page=gestione_mestiere" alt="_top">GESTIONE MESTIERE</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=log_chat" alt="_top">LOG CHAT</a></td></tr>
        <tr><td><a href="main.php?page=gestione_statuti_new">GESTIONE STATUTI</a></td></tr>
      </table>
      <br><br>
  <?php
    } 
  } 
  
  if(gdrcd_filter('get', $_REQUEST['op']) == 'shop') {
    if ($_SESSION['admin'] != 1 && $mestiere['id_mestiere'] != 3 && $mestiere['id_mestiere'] != 4) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['not_allowed']) . '</div>';
    else {
    ?>
      <table class="customTable">
        <tr class="second_header"><td>STRUMENTI <?php echo $intestazione ?></td></tr>
        <tr><td><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=oggetto_modifica" alt="_top">MODIFICA OGGETTO</a></td></tr>
        <tr><td><a href="main.php?page=erase_objet_mercato" alt="_top">CANCELLA DAL MERCATO</a></td></tr>
      </table>
      <br><br>
    <?php
    } 
  }
    
  if(isset($_REQUEST['op']) === false) {
    if ($_SESSION['admin'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=admin">PANNELLO ADMIN</a></td></tr>
      </table>
      <br><br>
    <?php
    }
      
    if ($_SESSION['admin'] == 1 || $_SESSION['master'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=master">PANNELLO MASTER</a></td></tr>
      </table>
      <br><br>
    <?php
    }
      
    if ($_SESSION['admin'] == 1 || $_SESSION['moderatore'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=moderatore">PANNELLO MODERATORE</a></td></tr>
      </table>
      <br><br>
    <?php
    }
    
    if ($_SESSION['admin'] == 1 || $_SESSION['capogilda'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=capogilda">PANNELLO CAPOGILDA</a></td></tr>
      </table>
      <br><br>
    <?php
    }
      
    if ($_SESSION['admin'] == 1 || $_SESSION['capomestiere'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=capomestiere">PANNELLO CAPOMESTIERE</a></td></tr>
      </table>
      <br><br>
    <?php
    }

    // se admin oppure se del magic o del pandora
    if ($_SESSION['admin'] == 1 || ($mestiere['id_mestiere'] == 3 || $mestiere['id_mestiere'] == 4)) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione&op=shop">PANNELLO <?php echo $intestazione ?></a></td></tr>
      </table>
      <br><br>
    <?php
    }
      // se admin oppure custodi
    if ($_SESSION['admin'] == 1 || $mestiere['id_gilda'] == 4) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione_personaggio" target="_top">PANNELLO CURE CUSTODI DEL PRIMORDIO</a></td></tr>
      </table>
      <br><br>
    <?php
    }
    
    if ($_SESSION['guida'] == 1) {
    ?>
      <table class="customTable">
        <tr class="second_header"><td><a href="main.php?page=gestione_regolamento" target="_top">MODIFICA REGOLAMENTO</a></td></tr>
      </table>
      <br><br>
    <?php
    }
    ?>

    <table class="customTable">
      <tr class="second_header"><td><a href="main.php?page=contatta_moderazione">CONTATTA LA MODERAZIONE</a></td></tr>
    </table>
    <br>
    <table class="customTable">
      <tr class="second_header"><td><a href="main.php?page=oggetto_ricarica">RICARICA OGGETTO</a></td></tr>
    </table>
    <br><br>
    <table class="customTable">
      <tr class="second_header"><td colspan="2">STATISTICHE SITO</td></tr>
      <tr>
        <td><b>Personaggi iscritti</b></td>
        <td>
        <?php
          $conteggio = gdrcd_query("SELECT Count(nome) AS num FROM personaggio");     
          echo $conteggio['num'];
        ?>
        </td>
      </tr>
      <tr>
        <td><b>Personaggi esiliati</b></td>
        <td>
        <?php
          $conteggio = gdrcd_query("SELECT Count(nome) AS num FROM personaggio WHERE esilio > NOW()");     
          echo $conteggio['num'];
        ?>
        </td>
      </tr>
      <tr>
        <td><b>Messaggi in bacheca</b></td>
        <td>
        <?php
          $conteggio = gdrcd_query("SELECT Count(id_messaggio) AS num FROM messaggioaraldo");     
          echo $conteggio['num'];
        ?>
        </td>
      </tr>
      <tr>
        <td><b>Azioni ultima settimana</b></td>
        <td>
        <?php
          $conteggio = gdrcd_query("SELECT COUNT(id) AS num FROM chat WHERE ora > DATE_SUB(NOW(), INTERVAL 7 DAY)");
          echo $conteggio['num'];
        ?>
        </td>
      </tr>
      <tr>
        <td><b>SMS ON ultima settimana</b></td>
        <td>
        <?php
          $conteggio = gdrcd_query("SELECT COUNT(id) AS num FROM messaggi WHERE spedito > DATE_SUB(NOW(), INTERVAL 7 DAY) AND tipo = 'on'");
          echo $conteggio['num'];
        ?>
        </td>
      </tr>
    </table>
    <br>
    <table class="customTable">
      <tr class="second_header"><td>ISCRITTI ULTIMI 7 GIORNI</td></tr>       
      <?php
        $result = gdrcd_query("SELECT nome, cognome FROM personaggio WHERE data_iscrizione > DATE_SUB(NOW(), INTERVAL 7 DAY)", 'result');
        while($row = gdrcd_query($result, 'fetch')) {
        echo '<tr><td>';
      ?>
      <a href="main.php?page=scheda&pg=<?= $row['nome'] ?>" target="_top">
      <?php
        echo $row['nome'];
        echo " ".$row['cognome'];
      ?>
      </a>
      <?php
        echo '</td></tr>';
      }
      ?>
    </table><br><br>
  <?php } ?>
  </div>
</div>