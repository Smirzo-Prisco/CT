<?php
session_start();
include_once('../header.inc.php');
/*Header comune*/

    $result = gdrcd_query("SELECT mappa.nome, mappa.descrizione, mappa.descrizione_immagine, mappa.stato, mappa.immagine, mappa.stanza_apparente, mappa.scadenza, mappa_click.meteo FROM  mappa_click LEFT JOIN mappa ON mappa_click.id_click = mappa.id_mappa WHERE id = ".$_SESSION['luogo'],'result');
    $record_exists = gdrcd_query($result, 'num_rows');
    $record = gdrcd_query($result, 'fetch');
?>
<div id="container">
<img src="/themes/crystal/imgs/descrizioni/<?php echo $record['descrizione_immagine']; ?>"><br>
<span class="luogo"><?php echo $record['nome']?></span><br>

<span class="sottotitolo">Stato: <?php echo $record['stato']?></span>
</div>

<div class="container2">
<span class="testo">
<?php echo $record['descrizione']?>
</span>
</div>