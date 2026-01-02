<link rel="stylesheet" href="../themes/crystal/uffici.css">
<div class="pagina_uffici">
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $PARAMETERS['office_page_name']); ?></h2>
    </div>
    <div class="page_body">
        <?php /* Generazione automatica del menu del gioco 
        foreach($PARAMETERS['office'] as $link_menu) {
            if((empty($link_menu['url']) === false) && (empty($link_menu['text']) === false) && (isset($link_menu['access_level']) === true) && ($link_menu['access_level'] <= $_SESSION['permessi'])) {
                echo '<div class="link_menu">';
                if(empty($link_menu['image_file']) === false) {
                    echo '<img src="themes/'.$PARAMETERS['themes']['current_theme'].'/imgs'.$link_menu['image_file'].'" />';
                }
                echo '<a href="'.$link_menu['url'].'">'.gdrcd_filter('out', $link_menu['text']).'</a></div>';
            }//if
        }//foreach
        */?>
        
        <br><br>
        
        <table class="tg">
<thead>
  <tr>
    <th style="padding: 25px; background: url('themes/crystal/imgs/uffici/utilita.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=servizi_prenotazioni_prova" alt="_top">ALBERGO</a></td>
    <td class="tg-73oq"><a href="main.php?page=anagrafe" alt="_top">ANAGRAFE</a></td>
    <td class="tg-73oq"><a href="main.php?page=servizi_banca" alt="_top">BANCA</a></td>
    <td class="tg-73oq"><a href="main.php?page=servizi_mercato" alt="_top">CENTRO<br>COMMERCIALE</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=elenco_staff" alt="_top">ELENCO<br>STAFF</a></td>
    <td class="tg-73oq"><a href="main.php?page=elenco_volti" alt="_top">PATROCINIO<br>VOLTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=user_razze" alt="_top">LISTA<br>SPIRITI</a></td>
    <td class="tg-73oq"><a href="main.php?page=user_cambio_pass" alt="_top">CAMBIO<BR>PASSWORD</a></td>
  </tr>
</tbody>
</table>
    </div>
    
    <table class="tg">
<thead>
  <tr>
    <th style="padding: 25px; background: url('themes/crystal/imgs/uffici/strumenti_cittadino.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=scegli_lavoro" alt="_top">SCEGLI<br>LAVORO</a></td>
    <td class="tg-73oq"><a href="main.php?page=richiesta_oggetto" alt="_top">OGGETTO<br>PERSONALIZZATO</a></td>
    <td class="tg-73oq"><a href="main.php?page=scegli_umano" alt="_top">POTENZIA<br>CITTADINO</a></td>
    <td class="tg-73oq"><a href="main.php?page=scegli_inclinazione" alt="_top">INCLINAZIONE<br>MAGICA</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=scegli_mestiere" alt="_top">SCEGLI, CONFERMA, AVANZA MESTIERE</a></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table>

<table class="tg">
<thead>
  <tr>
    <th style="padding: 25px; background: url('themes/crystal/imgs/uffici/potenziamento_pg.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=mercato_abilita" alt="_top">ABILIT&Agrave;</a></td>
    <td class="tg-73oq"><a href="main.php?page=mercato_talento" alt="_top">TALENTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=stats_level_up">INCREMENTO<br>PARAMETRI</a></td>
    <td class="tg-73oq"><a href="main.php?page=level_up" alt="_top">AUMENTO<br>SPIRITO</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table>

    <!-- page_body -->
</div><!-- Pagina -->
<?php /*HELP: Il menu viene generato automaticamente attingendo dalle informazioni contenute in config.inc.php. La versione supporta link testuali ed immagini e può essere modificata direttamente nel file config.ing.php, impostando url di destinazione, testo e selezionado le immagini. Se il link è un'immagine il testo viene interpretato automaticamente come testo alternativo all'immagine. Per realizzare un menu di altro tipo suggeriamo di commentare o cancellare il contenuto di questa pagina e sostituirlo con il codice del nuovo menu. */ ?>

