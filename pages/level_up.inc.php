<div class="pagina_gestione_mercato">
<table class="customTable" style="width: 20%">
<tr class="second_header" >
<td colspan="2">
    Tabella Riassuntiva
</td>
</tr>
  <tr>
    <td width="74" style="color: #8f8f8f;"><strong>Livello 1</strong></td>
    <td width="25" style="color: #8f8f8f;">Da 0 a 24 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 2</strong></td>
    <td style="color: #8f8f8f;">Da 25 a 99 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 3</strong></td>
    <td style="color: #8f8f8f;">Da 100 a 199 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 4</strong></td>
    <td style="color: #8f8f8f;">Da 200 a 499 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 5</strong></td>
    <td style="color: #8f8f8f;">Da 500 a 999 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 6</strong></td>
    <td style="color: #8f8f8f;">Da 1000 a 1999 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 7</strong></td>
    <td style="color: #8f8f8f;">Da 2000 a 4999 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 8</strong></td>
    <td style="color: #8f8f8f;">Da 5000 a 9999 px</td>
  </tr>
  <tr>
    <td style="color: #8f8f8f;"><strong>Livello 9</strong></td>
    <td style="color: #8f8f8f;">Da 10.000 px</td>
  </tr>
</table><br><br>

<?php
/* richiamo dati principali del pg */
$pg = $_SESSION['login'];
$personaggio = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".$_SESSION['login']."'", 'result');

       if(isset($_POST['op']) === true) {
       /*Iniziamo a aumentare il livello razza*/
       if(gdrcd_filter('get', $_POST['op']) == 'insert') {
       $level_up = gdrcd_query("UPDATE personaggio SET id_razza = id_razza + 1, punto_razza = punto_razza + 1 WHERE nome = '".$_SESSION['login']."'");
       
       echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['level_up']['inserted']).'</div>';
       
       }
             }
             
/* Controlliamo se ha l'esperienza e la razza per fare tutto */
$check_pg = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

if (
   (($check_pg['id_razza'] == 1000 || $check_pg['id_razza'] == 2000 || $check_pg['id_razza'] == 3000 || $check_pg['id_razza'] == 4000 || $check_pg['id_razza'] == 5000 || $check_pg['id_razza'] == 6000 || $check_pg['id_razza'] == 7000 || $check_pg['id_razza'] == 8000 || $check_pg['id_razza'] == 9000 || $check_pg['id_razza'] == 10000) 
   && ($check_pg['esperienza'] >= 25 && $check_pg['esperienza'] < 100)) ||
   (($check_pg['id_razza'] == 1001 || $check_pg['id_razza'] == 2001 || $check_pg['id_razza'] == 3001 || $check_pg['id_razza'] == 4001 || $check_pg['id_razza'] == 5001 || $check_pg['id_razza'] == 6001 || $check_pg['id_razza'] == 7001 || $check_pg['id_razza'] == 8001 || $check_pg['id_razza'] == 9001 || $check_pg['id_razza'] == 10001) 
   && ($check_pg['esperienza'] >= 100 && $check_pg['esperienza'] < 200)) ||
   (($check_pg['id_razza'] == 1002 || $check_pg['id_razza'] == 2002 || $check_pg['id_razza'] == 3002 || $check_pg['id_razza'] == 4002 || $check_pg['id_razza'] == 5002 || $check_pg['id_razza'] == 6002 || $check_pg['id_razza'] == 7002 || $check_pg['id_razza'] == 8002 || $check_pg['id_razza'] == 9002 || $check_pg['id_razza'] == 10002) 
   && ($check_pg['esperienza'] >= 200 && $check_pg['esperienza'] < 500)) ||
   (($check_pg['id_razza'] == 1003 || $check_pg['id_razza'] == 2003 || $check_pg['id_razza'] == 3003 || $check_pg['id_razza'] == 4003 || $check_pg['id_razza'] == 5003 || $check_pg['id_razza'] == 6003 || $check_pg['id_razza'] == 7003 || $check_pg['id_razza'] == 8003 || $check_pg['id_razza'] == 9003 || $check_pg['id_razza'] == 10003) 
   && ($check_pg['esperienza'] >= 500 && $check_pg['esperienza'] < 1000)) ||
   (($check_pg['id_razza'] == 1004 || $check_pg['id_razza'] == 2004 || $check_pg['id_razza'] == 3004 || $check_pg['id_razza'] == 4004 || $check_pg['id_razza'] == 5004 || $check_pg['id_razza'] == 6004 || $check_pg['id_razza'] == 7004 || $check_pg['id_razza'] == 8004 || $check_pg['id_razza'] == 9004 || $check_pg['id_razza'] == 10004) 
   && ($check_pg['esperienza'] >= 1000 && $check_pg['esperienza'] < 2000)) ||
   (($check_pg['id_razza'] == 1005 || $check_pg['id_razza'] == 2005 || $check_pg['id_razza'] == 3005 || $check_pg['id_razza'] == 4005 || $check_pg['id_razza'] == 5005 || $check_pg['id_razza'] == 6005 || $check_pg['id_razza'] == 7005 || $check_pg['id_razza'] == 8005 || $check_pg['id_razza'] == 9005 || $check_pg['id_razza'] == 10005) 
   && ($check_pg['esperienza'] >= 2000 && $check_pg['esperienza'] < 5000)) ||
   (($check_pg['id_razza'] == 1006 || $check_pg['id_razza'] == 2006 || $check_pg['id_razza'] == 3006 || $check_pg['id_razza'] == 4006 || $check_pg['id_razza'] == 5006 || $check_pg['id_razza'] == 6006 || $check_pg['id_razza'] == 7006 || $check_pg['id_razza'] == 8006 || $check_pg['id_razza'] == 9006 || $check_pg['id_razza'] == 10006) 
   && ($check_pg['esperienza'] >= 5000 && $check_pg['esperienza'] < 10000)) ||
   (($check_pg['id_razza'] == 1007 || $check_pg['id_razza'] == 2007 || $check_pg['id_razza'] == 3007 || $check_pg['id_razza'] == 4007 || $check_pg['id_razza'] == 5007 || $check_pg['id_razza'] == 6007 || $check_pg['id_razza'] == 7007 || $check_pg['id_razza'] == 8007 || $check_pg['id_razza'] == 9007 || $check_pg['id_razza'] == 10007) 
   && ($check_pg['esperienza'] >= 10000 && $check_pg['esperienza'] < 20000))
   ) {
   
   ?>
   
   <form class="form_gestione" action="main.php?page=level_up" method="post">
   <input type="hidden" name="op" value="insert" />
   <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['level_up']); ?>" />
   </form>

   <?php
   
   } else {

     echo '<div class="error">Non hai abbastanza esperienza per potenziare il tuo spirito.</div>';

}
             
             ?>
             
             <br><br>

  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>