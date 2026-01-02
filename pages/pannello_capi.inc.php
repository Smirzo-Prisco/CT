<?php
if ($_SESSION['admin'] == 1 || $_SESSION['capogilda'] == 1 || $_SESSION['capomestiere'] == 1) {
?>

<table class="tg">
<thead>
  <tr>
    <th onclick="showHideRow('row5')" style="cursor: pointer; padding: 25px; background: url('themes/crystal/imgs/uffici/strumenti_admin.png') center no-repeat;" class="tg-73oq" colspan="4"></th>
  </tr>
</thead>
<tbody id="row5" class="hidden_row">
  <tr>
    <td class="tg-73oq"><a href="main.php?page=gestione_famiglia" alt="_top">AMMINISTRA<br>GILDA</a></td>
    <td class="tg-73oq"><a href="main.php?page=gestione_mestiere" alt="_top">AMMINISTRA<br>MESTIERE</a></td>
    <td class="tg-73oq"></td>
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

<? } ?>