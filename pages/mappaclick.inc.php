<link rel="stylesheet" href="../themes/crystal/mappa_principale.css">

<?php
add_script("/includes/mappa_principale.js");
add_script("https://unpkg.com/image-map-resizer@1.0.10/js/imageMapResizer.min.js");

if(date("G")>=18 OR date("G")<=6) $img = "themes/crystal/imgs/maps/mappa_notte.png";
else $img = "themes/crystal/imgs/maps/mappa_giorno.png";
?>

<center>
<img src="<?=$img?>" usemap="#<?=$img?>" class="map-responsive" style="max-width: 100%;height: auto;">
<map id="ctMap" name="<?=$img?>" style="cursor:pointer;">
    <area shape="rect" coords="689,377,723,403" data-menu="menu1">
    <area shape="rect" coords="448,129,477,153" data-menu="menu2">
    <area shape="rect" coords="433,277,459,301" data-menu="menu3">
    <area shape="rect" coords="32,281,62,315" data-menu="menu4">
    <area shape="rect" coords="315,352,346,378" data-menu="menu5">
    <area shape="rect" coords="145,306,178,335" data-menu="menu6">
    <area shape="rect" coords="63,391,97,419" data-menu="menu7">
    <area shape="rect" coords="568,227,594,250" data-menu="menu8">
    <area shape="rect" coords="656,283,693,307" data-menu="menu9">
</map>
</center>

<? if(date("G")>=18 OR date("G")<=6){ ?>
<div id="menu1" class="menu_mappa">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Odaiba (<span dir="ltr" lang="ja" xml:lang="ja">お台場</span>) &egrave; una grande isola artificiale collocata a Est della citt&agrave;. Meta preferita di molti turisti, &egrave; nota soprattutto per il famoso lungomare.</font></p>
      <a href="main.php?dir=50" target="_top"><img src="themes/crystal/imgs/maps/faro.png" border=0></a>
      <a href="main.php?dir=2" target="_top"><img src="themes/crystal/imgs/maps//porto.png" border=0></a>
      <a href="main.php?dir=3" target="_top"><img src="themes/crystal/imgs/maps/ponte.png" border=0></a>
      <a href="main.php?dir=22" target="_top"><img src="themes/crystal/imgs/maps/spiaggia.png" border=0></a>
      <a href="main.php?dir=32" target="_top"><img src="themes/crystal/imgs/maps/regno_di_caos.png" border=0></a>
      </center>

    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu2">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Il Monte Fuji (<span dir="ltr" lang="ja" xml:lang="ja">富士山</span> Fuji-san) &egrave; il vulcano pi&ugrave; alto di tutto il Giappone. Luogo dalla bellezza paesaggistica straordinaria, &egrave; considerato uno dei luoghi sacri pi&ugrave; importanti.

</font></p>
      <a href="main.php?dir=9" target="_top"><img src="themes/crystal/imgs/maps/bosco.png" border=0></a>
      <a href="main.php?dir=28" target="_top"><img src="themes/crystal/imgs/maps/terme.png" border=0></a>
      <a href="main.php?dir=11" target="_top"><img src="themes/crystal/imgs/maps/stella_prima.png" border=0></a>
      <a href="main.php?dir=428" target="_top"><img src="themes/crystal/imgs/maps/nikigori.png" border=0></a>
      <a href="main.php?dir=10" target="_top"><img src="themes/crystal/imgs/maps/altri_luoghi.png" border=0></a>

      </center>
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu3">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Ueno (<span dir="ltr" lang="ja" xml:lang="ja">上野</span>) &egrave; il quartiere in cui risiedono i pi&ugrave; importanti musei e parchi di tutta la citt&agrave;. Densamente popolato soprattutto durante la fioritura dei sakura, i famosi fiori di ciliegio.</font></p>
      <a href="main.php?dir=43" target="_top"><img src="themes/crystal/imgs/maps/giardini_dei_fiori_del_male.png" border=0></a>
      <a href="main.php?dir=16" target="_top"><img src="themes/crystal/imgs/maps/luna_park.png" border=0></a>
      <a href="main.php?dir=4" target="_top"><img src="themes/crystal/imgs/maps/parco_di_ueno.png" border=0 align=center></a>
      <a href="main.php?dir=12" target="_top"><img src="themes/crystal/imgs/maps/periferia_nord.png" border=0 align=center></a>
      <a href="main.php?dir=7" target="_top"><img src="themes/crystal/imgs/maps/zoo.png" border=0></a> 
      </center>
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu4">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Shinjuku (<span dir="ltr" lang="ja" xml:lang="ja">新宿区</span>) &egrave; il pi&ugrave; importante e trafficato nodo di trasporto urbano della metropoli.
</font></p>
      <a href="main.php?dir=27" target="_top"><img src="themes/crystal/imgs/maps/villa_lancaster.png" border=0></a>
      <a href="main.php?dir=14" target="_top"><img src="themes/crystal/imgs/maps/secret_pandora.png" border=0></a>
      <a href="main.php?dir=38" target="_top"><img src="themes/crystal/imgs/maps/stazione.png" border=0></a>
      <a href="main.php?dir=18" target="_top"><img src="themes/crystal/imgs/maps/zona_malfamata.png" border=0></a>
</center>
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu5">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Chiyoda (<span dir="ltr" lang="ja" xml:lang="ja">千代田</span>) &egrave; il centro amministrativo di Tokyo dentro cui &egrave; possibile trovare, oltre che molte istituzioni governative, il famoso Palazzo di Cristallo.</font></p>
      <a href="main.php?dir=26" target="_top"><img src="themes/crystal/imgs/maps/chitoku_academy.png" border=0></a>
      <a href="main.php?dir=36" target="_top"><img src="themes/crystal/imgs/maps/corte.png" border=0></a>
      <a href="main.php?dir=25" target="_top"><img src="themes/crystal/imgs/maps/ospedale.png" border=0></a>
      <a href="main.php?dir=17" target="_top"><img src="themes/crystal/imgs/maps/palazzo_di_cristallo.png" border=0></a>
</center>  
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu6">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Roppongi (<span dir="ltr" lang="ja" xml:lang="ja">六本木</span>) &egrave; nota per l'ingente numero di locali notturni e, per questo, meta di numerosi turisti ed espatriati occidentali.</font></p>
      <a href="main.php?page=servizi_mercato" target="_top"><img src="themes/crystal/imgs/maps/centro_commerciale.png" border=0></a>
      <a href="main.php?dir=13" target="_top"><img src="themes/crystal/imgs/maps/gatto_nero.png" border=0></a>
      <a href="main.php?page=servizi_prenotazioni_prova" target="_top"><img src="themes/crystal/imgs/maps/hotel_inn.png" border=0></a>
      <a href="main.php?dir=47" target="_top"><img src="themes/crystal/imgs/maps/terrazza_panoramica.png" border=0></a>
      <a href="main.php?dir=35" target="_top"><img src="themes/crystal/imgs/maps/tokyo_tower.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu7">
  <div align="center">
    <ul class="Stile1">
<center>
<p align="justify"><font color="#b4b6bf">Shibuya (<span dir="ltr" lang="ja" xml:lang="ja">渋谷</span>) &egrave; la zona pi&ugrave; conosciuta e affollata di tutta la capitale giapponese, perennemente illuminata da megaschermi e luci. &Eacute; il quartiere preferito dai giovani.</font></p>
      <a href="main.php?dir=23" target="_top"><img src="themes/crystal/imgs/maps/centro.png" border=0></a>
      <a href="main.php?dir=24" target="_top"><img src="themes/crystal/imgs/maps/magic_shop.png" border=0></a>
      <a href="main.php?dir=30" target="_top"><img src="themes/crystal/imgs/maps/Tae.png" border=0></a>
      <a href="main.php?dir=33" target="_top"><img src="themes/crystal/imgs/maps/harajuku.png" border=0></a>
      <a href="main.php?dir=8" target="_top"><img src="themes/crystal/imgs/maps/zona_residenziale_ovest.png" border=0></a>
</center>
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu8">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Asakusa (<span dir="ltr" lang="ja" xml:lang="ja">浅草</span>) viene spesso associata alla zona spirituale. Dominata da templi e santuari shinto, &egrave; possibile notare persone vestite con abiti tradizionali.</font></p>
      <a href="main.php?dir=34" target="_top"><img src="themes/crystal/imgs/maps/cimitero.png" border=0></a>
      <!--- <a href="main.php?Dir=31" target="_top"><img src="themes/crystal/img/maps/dojo.png" border=0></a> -->
      <a href="main.php?dir=19" target="_top"><img src="themes/crystal/imgs/maps/santuario_di_cosmos.png" border=0></a>
      <a href="main.php?dir=31" target="_top"><img src="themes/crystal/imgs/maps/reggia_lunare.png" border=0></a>
      <a href="main.php?dir=48" target="_top"><img src="themes/crystal/imgs/maps/zona_residenziale_est.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu9">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Tsukiji (<span dir="ltr" lang="ja" xml:lang="ja">築地</span>) deve la sua fama al celeberrimo mercato del pesce data la vicinanza con il fiume Sumida. A seguito di un terremoto non ancora compreso, molta della zona &egrave; costituita da palazzi abbandonati.</font></p>
      <a href="main.php?dir=21" target="_top"><img src="themes/crystal/imgs/maps/fiume.png" border=0></a>
      <a href="main.php?dir=49" target="_top"><img src="themes/crystal/imgs/maps/palazzo_abbandonato.png" border=0></a>
      <a href="main.php?dir=37" target="_top"><img src="themes/crystal/imgs/maps/periferia_sud.png" border=0></a>
      <a href="main.php?dir=20" target="_top"><img src="themes/crystal/imgs/maps/quartier_generale.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>
<? } else { ?>
<div id="menu1" class="menu_mappa">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Odaiba (<span dir="ltr" lang="ja" xml:lang="ja">お台場</span>) &egrave; una grande isola artificiale collocata a Est della citt&agrave;. Meta preferita di molti turisti, &egrave; nota soprattutto per il famoso lungomare.</font></p>
      <a href="main.php?dir=50" target="_top"><img src="themes/crystal/imgs/maps/faro.png" border=0></a>
      <a href="main.php?dir=2" target="_top"><img src="themes/crystal/imgs/maps//porto.png" border=0></a>
      <a href="main.php?dir=3" target="_top"><img src="themes/crystal/imgs/maps/ponte.png" border=0></a>
      <a href="main.php?dir=22" target="_top"><img src="themes/crystal/imgs/maps/spiaggia.png" border=0></a>
      <a href="main.php?dir=32" target="_top"><img src="themes/crystal/imgs/maps/regno_di_caos.png" border=0></a>
      </center>

    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu2">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Il Monte Fuji (<span dir="ltr" lang="ja" xml:lang="ja">富士山</span> Fuji-san) &egrave; il vulcano pi&ugrave; alto di tutto il Giappone. Luogo dalla bellezza paesaggistica straordinaria, &egrave; considerato uno dei luoghi sacri pi&ugrave; importanti.

</font></p>
      <a href="main.php?dir=9" target="_top"><img src="themes/crystal/imgs/maps/bosco.png" border=0></a>
      <a href="main.php?dir=28" target="_top"><img src="themes/crystal/imgs/maps/terme.png" border=0></a>
      <a href="main.php?dir=11" target="_top"><img src="themes/crystal/imgs/maps/stella_prima.png" border=0></a>
      <a href="main.php?dir=428" target="_top"><img src="themes/crystal/imgs/maps/nikigori.png" border=0></a>
      <a href="main.php?dir=10" target="_top"><img src="themes/crystal/imgs/maps/altri_luoghi.png" border=0></a>

      </center>
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu3">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Ueno (<span dir="ltr" lang="ja" xml:lang="ja">上野</span>) &egrave; il quartiere in cui risiedono i pi&ugrave; importanti musei e parchi di tutta la citt&agrave;. Densamente popolato soprattutto durante la fioritura dei sakura, i famosi fiori di ciliegio.</font></p>
      <a href="main.php?dir=43" target="_top"><img src="themes/crystal/imgs/maps/giardini_dei_fiori_del_male.png" border=0></a>
      <a href="main.php?dir=16" target="_top"><img src="themes/crystal/imgs/maps/luna_park.png" border=0></a>
      <a href="main.php?dir=4" target="_top"><img src="themes/crystal/imgs/maps/parco_di_ueno.png" border=0 align=center></a>
      <a href="main.php?dir=12" target="_top"><img src="themes/crystal/imgs/maps/periferia_nord.png" border=0 align=center></a>
      <a href="main.php?dir=7" target="_top"><img src="themes/crystal/imgs/maps/zoo.png" border=0></a> 
      </center>
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu4">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Shinjuku (<span dir="ltr" lang="ja" xml:lang="ja">新宿区</span>) &egrave; il pi&ugrave; importante e trafficato nodo di trasporto urbano della metropoli.
</font></p>
      <a href="main.php?dir=27" target="_top"><img src="themes/crystal/imgs/maps/villa_lancaster.png" border=0></a>
      <a href="main.php?dir=14" target="_top"><img src="themes/crystal/imgs/maps/secret_pandora.png" border=0></a>
      <a href="main.php?dir=38" target="_top"><img src="themes/crystal/imgs/maps/stazione.png" border=0></a>
      <a href="main.php?dir=18" target="_top"><img src="themes/crystal/imgs/maps/zona_malfamata.png" border=0></a>
</center>
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu5">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Chiyoda (<span dir="ltr" lang="ja" xml:lang="ja">千代田</span>) &egrave; il centro amministrativo di Tokyo dentro cui &egrave; possibile trovare, oltre che molte istituzioni governative, il famoso Palazzo di Cristallo.</font></p>
      <a href="main.php?dir=36" target="_top"><img src="themes/crystal/imgs/maps/corte.png" border=0></a>
      <a href="main.php?dir=25" target="_top"><img src="themes/crystal/imgs/maps/ospedale.png" border=0></a>
      <a href="main.php?dir=17" target="_top"><img src="themes/crystal/imgs/maps/palazzo_di_cristallo.png" border=0></a>
      <a href="main.php?dir=26" target="_top"><img src="themes/crystal/imgs/maps/chitoku_academy.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu6">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Roppongi (<span dir="ltr" lang="ja" xml:lang="ja">六本木</span>) &egrave; nota per l'ingente numero di locali notturni e, per questo, meta di numerosi turisti ed espatriati occidentali.</font></p>
      <a href="main.php?page=servizi_mercato" target="_top"><img src="themes/crystal/imgs/maps/centro_commerciale.png" border=0></a>
      <a href="main.php?dir=13" target="_top"><img src="themes/crystal/imgs/maps/gatto_nero.png" border=0></a>
      <a href="main.php?page=servizi_prenotazioni_prova" target="_top"><img src="themes/crystal/imgs/maps/hotel_inn.png" border=0></a>
      <a href="main.php?dir=47" target="_top"><img src="themes/crystal/imgs/maps/terrazza_panoramica.png" border=0></a>
      <a href="main.php?dir=35" target="_top"><img src="themes/crystal/imgs/maps/tokyo_tower.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu7">
  <div align="center">
    <ul class="Stile1">
<center>
<p align="justify"><font color="#b4b6bf">Shibuya (<span dir="ltr" lang="ja" xml:lang="ja">渋谷</span>) &egrave; la zona pi&ugrave; conosciuta e affollata di tutta la capitale giapponese, perennemente illuminata da megaschermi e luci. &Eacute; il quartiere preferito dai giovani.</font></p>
      <a href="main.php?dir=23" target="_top"><img src="themes/crystal/imgs/maps/centro.png" border=0></a>
      <a href="main.php?dir=24" target="_top"><img src="themes/crystal/imgs/maps/magic_shop.png" border=0></a>
      <a href="main.php?dir=30" target="_top"><img src="themes/crystal/imgs/maps/Tae.png" border=0></a>
      <a href="main.php?dir=33" target="_top"><img src="themes/crystal/imgs/maps/harajuku.png" border=0></a>
      <a href="main.php?dir=8" target="_top"><img src="themes/crystal/imgs/maps/zona_residenziale_ovest.png" border=0></a>
</center>
    </ul>
  </div>
</div>
<div class="menu_mappa" id="menu8">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Asakusa (<span dir="ltr" lang="ja" xml:lang="ja">浅草</span>) viene spesso associata alla zona spirituale. Dominata da templi e santuari shinto, &egrave; possibile notare persone vestite con abiti tradizionali.</font></p>
      <a href="main.php?dir=34" target="_top"><img src="themes/crystal/imgs/maps/cimitero.png" border=0></a>
      <!--- <a href="main.php?Dir=31" target="_top"><img src="themes/crystal/img/maps/dojo.png" border=0></a> -->
      <a href="main.php?dir=19" target="_top"><img src="themes/crystal/imgs/maps/santuario_di_cosmos.png" border=0></a>
      <a href="main.php?dir=31" target="_top"><img src="themes/crystal/imgs/maps/reggia_lunare.png" border=0></a>
      <a href="main.php?dir=48" target="_top"><img src="themes/crystal/imgs/maps/zona_residenziale_est.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>

<div class="menu_mappa" id="menu9">
  <div align="center">
    <ul class="Stile1">
      <center>
      <p align="justify"><font color="#b4b6bf">Tsukiji (<span dir="ltr" lang="ja" xml:lang="ja">築地</span>) deve la sua fama al celeberrimo mercato del pesce data la vicinanza con il fiume Sumida. A seguito di un terremoto non ancora compreso, molta della zona &egrave; costituita da palazzi abbandonati.</font></p>
      <a href="main.php?dir=21" target="_top"><img src="themes/crystal/imgs/maps/fiume.png" border=0></a>
      <a href="main.php?dir=49" target="_top"><img src="themes/crystal/imgs/maps/palazzo_abbandonato.png" border=0></a>
      <a href="main.php?dir=37" target="_top"><img src="themes/crystal/imgs/maps/periferia_sud.png" border=0></a>
      <a href="main.php?dir=20" target="_top"><img src="themes/crystal/imgs/maps/quartier_generale.png" border=0></a>
      </center>  
    </ul>
  </div>
</div>
<?
}

$limite_role = gdrcd_query("SELECT * FROM mappa WHERE timestamp_modifica_limite IS NOT NULL AND timestamp_modifica_limite < DATE_SUB(NOW(), INTERVAL 6 HOUR)", 'result');

// Verifica se ci sono risultati
while ($row = gdrcd_query($limite_role, 'fetch')) {
    $id_mappa = $row['id'];

    // Cancella le righe corrispondenti in scelte_utenti
    gdrcd_query("DELETE FROM scelte_utenti WHERE id_luogo = $id_mappa");

    // Aggiorna i valori in mappa
    gdrcd_query("UPDATE mappa SET timestamp_modifica_limite = NULL, limite_lunghezza_massima = NULL WHERE id = $id_mappa");
}

$limite_master = gdrcd_query("SELECT * FROM chat_master WHERE date_end < DATE_SUB(NOW(), INTERVAL 6 HOUR)", 'result');

// Verifica se ci sono risultati
while ($rowm = gdrcd_query($limite_master, 'fetch')) {
    $id_mappa = $rowm['luogo'];

    // Cancella le righe corrispondenti in scelte_utenti
    gdrcd_query("DELETE FROM chat_master WHERE luogo = $id_mappa");
}
?>

<!-- Inizio Sezione JavaScript
<script src="includes/mappa_principale.js" type="text/javascript"></script>
<script src="https://unpkg.com/image-map-resizer@1.0.10/js/imageMapResizer.min.js"></script>
-->

<script type="text/javascript">
/*************************************************************************
  This code is from Dynamic Web Coding at http://www.dyn-web.com/
  Copyright 2003 by Sharon Paine 
  See Terms of Use at http://www.dyn-web.com/bus/terms.html
  regarding conditions under which you may use this code.
  This notice must be retained in the code as is!
*************************************************************************

var menuLayers = {
  timer: null,
  activeMenuID: null,
  offX: -200,   // posizione orizziontale rispetto al cursore 
  offY: 30,   // posizione verticale rispetto al cursore
  show: function(id, e) {
    var mnu = document.getElementById? document.getElementById(id): null;
    if (!mnu) return;
    this.activeMenuID = id;
    if ( mnu.onmouseout == null ) mnu.onmouseout = this.mouseoutCheck;
    if ( mnu.onmouseover == null ) mnu.onmouseover = this.clearTimer;
    viewport.getAll();
    this.position(mnu,e);
  },
  
  hide: function() {
    this.clearTimer();
    if (this.activeMenuID && document.getElementById) 
      this.timer = setTimeout("document.getElementById('"+menuLayers.activeMenuID+"').style.visibility = 'hidden'", 200);
  },
  
  position: function(mnu, e) {
    var x = e.pageX? e.pageX: e.clientX + viewport.scrollX;
    var y = e.pageY? e.pageY: e.clientY + viewport.scrollY;
    
    if ( x + (mnu.offsetWidth+500) + this.offX > viewport.width + viewport.scrollX )
      x = x - mnu.offsetWidth - 400 - this.offX;
    else x = x + this.offX;
  
    if ( y + mnu.offsetHeight + this.offY > viewport.height + viewport.scrollY )
      y = ( y - mnu.offsetHeight - this.offY > viewport.scrollY )? y - mnu.offsetHeight - this.offY : viewport.height + viewport.scrollY - mnu.offsetHeight;
    else y = y + this.offY;
    
    mnu.style.left = x + "px"; mnu.style.top = y + "px";
    this.timer = setTimeout("document.getElementById('" + menuLayers.activeMenuID + "').style.visibility = 'visible'", 200);
  },
  
  mouseoutCheck: function(e) {
    e = e? e: window.event;
    var mnu = document.getElementById(menuLayers.activeMenuID);
    var toEl = e.relatedTarget? e.relatedTarget: e.toElement;
    if ( mnu != toEl && !menuLayers.contained(toEl, mnu) ) menuLayers.hide();
  },
  contained: function(oNode, oCont) {
    if (!oNode) return; 
    while ( oNode = oNode.parentNode ) 
      if ( oNode == oCont ) return true;
    return false;
  },

  clearTimer: function() {
    if (menuLayers.timer) clearTimeout(menuLayers.timer);
  }
  
}
*/


document.addEventListener("DOMContentLoaded", () => {
    const aree = document.querySelectorAll("area[data-menu]");

    aree.forEach(area => {
        // Desktop hover
        area.addEventListener("mouseenter", e => showMenu(e, area));
		
        // Click desktop
        area.addEventListener("click", e => {
            e.preventDefault();
            showMenu(e, area);
        });

        // Tap mobile
        area.addEventListener("touchstart", e => {
            e.preventDefault(); // evita scroll/tap nativo
            showMenu(e, area);
        });
    });

    function showMenu(e, area) {
		hideAllMenus();

		const menuID = area.dataset.menu;
		const menu = document.getElementById(menuID);
		if (!menu) return;

		let mouseX, mouseY;
		const isTouch = e.touches && e.touches.length;

		if (isTouch) {
			// Touch / mobile: spostamento 50px a sinistra
			mouseX = e.touches[0].pageX - 50;
			mouseY = e.touches[0].pageY;
		} else {
			// Desktop: nessun offset
			mouseX = e.pageX;
			mouseY = e.pageY;
		}

		const menuWidth = menu.offsetWidth;
		const menuHeight = menu.offsetHeight;
		const viewportWidth = window.innerWidth + window.scrollX;
		const viewportHeight = window.innerHeight + window.scrollY;

		// Controllo bordi orizzontali
		if (mouseX + menuWidth/2 > viewportWidth) mouseX = viewportWidth - menuWidth/2;
		if (mouseX - menuWidth/2 < 0) mouseX = menuWidth/2;

		// Controllo bordo superiore
		if (mouseY - menuHeight < window.scrollY) mouseY = window.scrollY + menuHeight;

		menu.style.left = mouseX + "px";
		menu.style.top = mouseY + "px";
		menu.style.visibility = "visible";
	}

    function hideAllMenus() {
        document.querySelectorAll(".menu_mappa").forEach(m => {
            m.style.visibility = "hidden";
        });
    }
});

window.onload = function() {
    imageMapResize();
};
</script>
<!-- Fine Sezione JavaScript -->