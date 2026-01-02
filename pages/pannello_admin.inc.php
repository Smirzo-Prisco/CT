<?php
if ($_SESSION['admin'] == 1) {
?>

<table class="tg">
<thead>
  <tr>
    <th onclick="showHideRow('row1')" style="cursor: pointer; padding: 25px; background: url('themes/crystal/imgs/uffici/strumenti_gestione.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody id="row1" class="hidden_row">
  <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_abilita_gilde" alt="_top">GESTIONE<br>ABILIT&Agrave;</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_abilita" alt="_top">GESTIONE<br>TALENTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_statuti" alt="_top">GESTIONE<br>STATUTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_bacheche" alt="_top">GESTIONE<br>BACHECHE</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_luoghi" alt="_top">GESTIONE<br>LUOGHI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_gilde" alt="_top">GESTIONE<br>GILDE E RUOLI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_mestieri" alt="_top">GESTIONE<br>MESTIERI E RUOLI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_inclinazione" alt="_top">GESTIONE<br>INCLINAZIONI</a></td>
  </tr>
    <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_mappe" alt="_top">GESTIONE<br>MAPPE</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_mercato" alt="_top">GESTIONE<br>OGGETTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_nomine" alt="_top">GESTIONE<br>PERMESSI</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_razze" alt="_top">GESTIONE<br>RAZZE</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_tutta_famiglia" alt="_top">NOMINA<br>CAPOFAMIGLIA</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_tutta_mestiere" alt="_top">NOMINA<br>CAPOMESTIERE</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_ctnews" alt="_top">CT NEWS</a></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table>
    </div>
 
    <table class="tg">
<thead>
  <tr>
    <th onclick="showHideRow('row2')" style="cursor: pointer; padding: 25px; background: url('themes/crystal/imgs/uffici/strumenti_vari.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody id="row2" class="hidden_row">
  <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_regolamento" alt="_top">MODIFICA<BR>REGOLAMENTO</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_manutenzione" alt="_top">MANUTENZIONE</a></td>
    <td class="tg-73oq"><a href="main.php?page=erase_pg" alt="_top">CANCELLA<br>PG INATTIVI</a></td>
    <td class="tg-73oq"><a href="main.php?page=erase_inactive" alt="_top">CANCELLA PG<br>MAI ENTRATI</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"><a href="main.php?page=erasepg_scelta" alt="_top">CANCELLA<br>PERSONAGGIO</a></td>
    <td class="tg-73oq"><a href="main.php?page=punti_png" alt="_top">POTENZIA<br>PNG</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_esilio" alt="_top">ESILIA<br>PNG</a></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table>

<table class="tg">
<thead>
  <tr>
    <th onclick="showHideRow('row3')" style="cursor: pointer; padding: 25px; background: url('themes/crystal/imgs/uffici/log.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody id="row3" class="hidden_row">
  <tr>
    <td class="tg-73oq"><a href="main.php?page=log_chat" alt="_top">LOG<br>CHAT</a></td>
    <td class="tg-73oq"><a href="main.php?page=log_eventi">LOG<br>EVENTI</a></td>
    <td class="tg-73oq"><a href="main.php?page=log_messaggi" alt="_top">LOG<br>MESSAGGI</a></td>
    <td class="tg-73oq"></td>
  </tr>
  <tr>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table> 

<table class="tg">
<thead>
  <tr>
    <th onclick="showHideRow('row4')" style="cursor: pointer; padding: 25px; background: url('themes/crystal/imgs/uffici/gestione_oggetti.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody id="row4" class="hidden_row">
  <tr>
    <td class="tg-73oq"><a href="main.php?page=oggetto_aggiungi" alt="_top">CREA<br>OGGETTO</a></td>
    <td class="tg-73oq"><a href="main.php?page=oggetto_modifica">MODIFICA<br>OGGETTO</a></td>
    <td class="tg-73oq"><a href="main.php?page=oggetto_assegna" alt="_top">ASSEGNA<br>OGGETTO</a></td>
    <td class="tg-73oq"><a href="main.php?page=elenco_richieste_oggetti" alt="_top">APPROVA<br>OGGETTO</a></td>
  </tr>
  <tr>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
    <td class="tg-73oq"></td>
  </tr>
</tbody>
</table>

<? } ?>