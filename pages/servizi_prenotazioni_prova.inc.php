<link rel="stylesheet" href="themes/crystal/albergo.css">

<div class="pagina_servizi_prenotazioni">
    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['hotel']['page_name']); ?></h2>
    </div>
    
    <!-- Box principale -->
    <div class="page_body">   
    <table class="customTable">
    <tr class="second_header" style="filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">
                                    <td>
                                        <div>
                                        ALBERGO
                                        </div>
                                    </td>
    </tr>
    </table>
    <br><br>
    <center>
    Per prenotare una stanza, clicca su prenota.<br>
    Per invitare qualcuno in una stanza, bisogna selezionare INVITA nella barra chat e scrivere il nome dell'invitato.<br>
    </center>
    <br>
    <!-- stanze -->
    
    <table class="tg">
    
<tbody>
  <tr>
    <td class="tg-73oq">CAMERA<br>1</td>
    <td class="tg-73oq">CAMERA<br>2</td>
    <td class="tg-73oq">CAMERA<br>3</td>
    <td class="tg-73oq">CAMERA<br>4</td>
  </tr>
  
</tbody>
</table>
    <br><br>
    <center>
    Scegli una stanza privata da usare per massimo <b>4 ore</b>.<br>
    Ricordiamo che le chat private <b>non garantiscono il punto esperienza</b><br>
    <b>Non è possibile svolgere giocate di trama o di gilda o di scambio di informazioni</b>
    <br><br>
    </center>            
       
            <table class="tg">
    
<tbody>
  <tr>
    <td class="tg-73oq">CAMERA 1<br>150 CY</td>
    <td class="tg-73oq">CAMERA 2<br>150 CY</td>
    <td class="tg-73oq">CAMERA 3<br>150 CY</td>
    <td class="tg-73oq">CAMERA 4<br>150 CY</td>
  </tr>
  
</tbody>
</table>
<?php
                /*Seleziono i ruoli su cui l'account ha competenza*/
                $query = "SELECT mappa.id, mappa.nome AS luogo, mappa.costo, mappa.proprietario, mappa.invitati, mappa.scadenza, mappa_click.nome FROM mappa JOIN mappa_click on mappa.id_mappa = mappa_click.id_click WHERE mappa.privata = 1 ORDER BY mappa.nome, mappa.costo DESC";
                $result = gdrcd_query($query, 'result');
                
?>
<!-- form di prenotazione -->
<form action="main.php?page=servizi_prenotazioni_prova" method="post">

<table class="tg_no">
    
<tbody>
  <tr>
<?php
while($row = gdrcd_query($result, 'fetch')) { ?>
<?php if(($row['scadenza'] > strftime('%Y-%m-%d %H:%M:%S')) && ($row['proprietario'] == $_SESSION['login'] || (strpos($row['invitati'], gdrcd_capital_letter($_SESSION['login'])) != false)))  { ?>
<td><a href="main.php?dir=<?php echo $row['id']; ?>"><b>Entra nella stanza</b></a></td>
<?php } elseif(($row['scadenza'] > strftime('%Y-%m-%d %H:%M:%S')) && ($row['proprietario'] != $_SESSION['login'] || (strpos($row['invitati'], gdrcd_capital_letter($_SESSION['login'])) != true)))  { ?>
<td>Occupata</td>
<?php } else { ?>
<td><input type="radio"name="id" value="<?php echo $row['id']; ?>"></td>
<?php
 }//if
   }//while
gdrcd_query($result, 'free');
?>
   </tr>
  
</tbody>
</table>

<?php

/* Controlliamo se tiene li sordi */

$checksoldi = gdrcd_query("SELECT * FROM personaggio WHERE nome ='".$_SESSION['login']."'");

if ($checksoldi['soldi'] < 150) {

echo "Non hai abbastanza soldi";

} else {
?>
<div class="form_submit">
<input type="hidden" name="op" value="book" />
<input type="submit" name="submit" value="Prenota" style="width: 68px;" />
</div>
<?php }//fine check_soldi ?>
</form>

<?php



//avvio la prenotazione

/*Prenota*/
        if(gdrcd_filter('get', $_POST['op']) == 'book') {

if(empty($_POST['id']) === true) {
echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['warning']['cant_do']).'</div>';
            } else {
            
            /*Opero la prenotazione*/
            $id = $_POST['id'];
            gdrcd_query("UPDATE mappa SET proprietario = '".$_SESSION['login']."', invitati='', ora_prenotazione=NOW(), scadenza=DATE_ADD(NOW(), INTERVAL 4 HOUR) WHERE id = ".gdrcd_filter('num', $id)." and scadenza < NOW() LIMIT 1");
            gdrcd_query("UPDATE personaggio SET soldi = soldi - 150 WHERE nome = '".$_SESSION['login']."' LIMIT 1");

}

            echo "<script type='text/javascript'>alert('Stanza prenotata correttamente');</script>";
?>

<script language="JavaScript" type="text/javascript">
<!--  
location.href="main.php?page=servizi_prenotazioni_prova";
</script>
<?php
}
            
 ?>     
 
  <div class="panels_link">
                <a href="main.php?page=uffici">Torna indietro</a>
            </div>
</div>

</div>