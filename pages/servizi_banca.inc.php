<link rel="stylesheet" href="themes/crystal/banca.css">

<?php /*HELP: */
$row = gdrcd_query("SELECT soldi, banca, ultimo_stipendio FROM personaggio WHERE nome = '".$_SESSION['login']."' LIMIT 1");
$soldi = 0 + $row['soldi'];
$banca = 0 + $row['banca'];
$ultimo = $row['ultimo_stipendio'];

/* Stipendio mestiere */
$query = "SELECT ruolo_mestiere.stipendio FROM clgpersonaggiomestiere LEFT JOIN ruolo_mestiere on clgpersonaggiomestiere.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiomestiere.personaggio = '".$_SESSION['login']."'";
$result = gdrcd_query($query, 'result');
$stipendio = 0;
while($row = gdrcd_query($result, 'fetch')) {
    $stipendio += $row['stipendio'];
}
gdrcd_query($result, 'free');

/* Stipendio lavoro easy */
$query_job = "SELECT ruolo_mestiere.stipendio FROM clgpersonaggiolavoro LEFT JOIN ruolo_mestiere on clgpersonaggiolavoro.id_ruolo = ruolo_mestiere.id_ruolo WHERE clgpersonaggiolavoro.personaggio = '".$_SESSION['login']."'";
$result_job = gdrcd_query($query_job, 'result');
$stipendio_job = 0;
while($row_job = gdrcd_query($result_job, 'fetch')) {
    $stipendio_job += $row_job['stipendio'];
}
gdrcd_query($result_job, 'free');

$stipendio_tot = $stipendio+$stipendio_job;

?>

<!-- Iniziamo struttura -->

<div class="pagina_servizi_prenotazioni">
<!-- Box principale -->
<div class="page_body">  


<table class="customTable">
    <tr class="second_header" style="filter: drop-shadow(0 0 5px rgba(0,0,0,0.57)); font-family: DejaVu Sans; font-size:12px;">
                                    <td>
                                        <div>
                                        BANCA DI CRYSTAL TOKYO
                                        </div>
                                    </td>
    </tr>
    </table>
    <br><br>
    
<!-- Operazioni bancarie -->
        <?php /*Prelievo*/
        if((isset($_POST['op']) === true) && (gdrcd_filter('get', $_POST['op']) == 'preleva')) {
            if(($_POST['ammontare'] <= 0) || (is_numeric($_POST['ammontare']) === false)) {
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['error']).'</div>';
            } else {
                if($_POST['ammontare'] > $banca) {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['withdraw_no']).'</div>';
                } else {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['done']).'</div>';
                    /*Eseguo la transazione*/
                    gdrcd_query("UPDATE personaggio SET soldi = soldi + ".$_POST['ammontare'].", banca = banca - ".$_POST['ammontare']." WHERE nome = '".$_SESSION['login']."' LIMIT 1");
                }
            } ?>
            <div class="link_back">
                <a href="main.php?page=servizi_banca"><?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['back']); ?></a>
            </div>
        <?php
        }
        /*Deposito*/
        if((isset($_POST['op']) === true) && (gdrcd_filter('get', $_POST['op']) == 'deposita')) {
            if(($_POST['ammontare'] <= 0) || (is_numeric($_POST['ammontare']) === false)) {
                echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['error']).'</div>';
            } else {
                if($_POST['ammontare'] > $soldi) {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['deposit_no']).'</div>';
                } else {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['done']).'</div>';
                    /*Eseguo la transazione*/
                    gdrcd_query("UPDATE personaggio SET soldi = soldi - ".gdrcd_filter('num', $_POST['ammontare']).", banca = banca + ".$_POST['ammontare']." WHERE nome = '".$_SESSION['login']."' LIMIT 1");
                }
            } ?>
            <div class="link_back">
                <a href="main.php?page=servizi_banca"><?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['back']); ?></a>
            </div>
        <?php
        }
        /*Bonifico*/
        if((isset($_POST['op']) === true) && ($_POST['op'] == 'bonifico')) {
            $query = gdrcd_query("SELECT nome FROM personaggio WHERE nome = '".$_POST['beneficiario']."' LIMIT 1");
            if(empty($_POST['beneficiario'])) {
                echo '<div class="warning">Il beneficiario che hai inserito non esiste o non &egrave; valido!</div>';
            } else {
                if(($_POST['ammontare'] <= 0) || (is_numeric($_POST['ammontare']) === false)) {
                    echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['error']).'</div>';
                } else {
                    if($_POST['ammontare'] > $banca) {
                        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['withdraw_no']).'</div>';
                    } else {
                        echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['done']).'</div>';
                        /*Eseguo la transazione*/
                        gdrcd_query("UPDATE personaggio SET banca = banca - ".gdrcd_filter('num', $_POST['ammontare'])." WHERE nome = '".$_SESSION['login']."' LIMIT 1");
                        gdrcd_query("UPDATE personaggio SET banca = banca + ".gdrcd_filter('num', $_POST['ammontare'])." WHERE nome = '".$_POST['beneficiario']."' LIMIT 1");

                        /*Registro l'evento (Passaggio di danaro)*/
                        gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".gdrcd_filter('in', $_POST['beneficiario'])."', '".$_SESSION['login']."', NOW(), ".BONIFICO.", '".'('.gdrcd_filter('num', $_POST['ammontare']).' '.$PARAMETERS['names']['currency']['plur'].') '.gdrcd_filter('in', $_POST['causale'])."')");
                       
                    }
                }
            } ?>
            <div class="link_back">
                <a href="main.php?page=servizi_banca"><?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['back']); ?></a>
            </div>
        <?php
        }
        /*Stipendio*/
        /**    * Correzione dell'exploit che rendeva possibile accreditarsi un numero illimitato di soldi in banca
         * Il controllo è eseguito anche nella query con la condizione 'AND ultimo_stipendio < NOW()'.
         * Un grazie a Dyrr per la segnalazione.
         * @author Blancks
         */
        if((isset($_POST['op']) === true) && ($_POST['op'] == 'incassa') && ($ultimo != strftime("%Y-%m-%d"))) {
            $mex = "Stipendio ($stipendio_tot CY)";
            echo '<div class="warning">'.gdrcd_filter('out', $MESSAGE['interface']['bank']['done']).'</div>';
            gdrcd_query("UPDATE personaggio SET banca = banca + ".$stipendio_tot.", ultimo_stipendio = NOW() WHERE nome = '".$_SESSION['login']."' AND ultimo_stipendio < NOW() LIMIT 1");
            /*gdrcd_query("INSERT INTO log (nome_interessato, autore, data_evento, codice_evento ,descrizione_evento) VALUES ('".$_SESSION['login']."', '".$_SESSION['login']."', NOW(), ".STIPENDIO.", '$mex')");
            */
            ?>
            <div class="link_back">
                <a href="main.php?page=servizi_banca"><?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['back']); ?></a>
            </div>
        <?php
        }
        
        
        
        
        
        
        
        
        
        
        
        if(isset($_POST['op']) === false) { ?>
            <div class="panels_box" style="font-family: DejaVu Sans; font-size:13px;">
                <div class="status_bancario">
                    <!-- Saldo bancario -->
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['amount'].": ".$banca." ".$PARAMETERS['names']['currency']['plur']); ?>
                    <br />
                    <!-- In tasca -->
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['pocket'].": ".$soldi." ".$PARAMETERS['names']['currency']['plur']); ?>
                    <br />
                </div>
                <br>
                <!-- bonifico -->
                
                <table border=0 cellpadding=5 cellspacing=0 class="customTable">
                <div class="form_gioco">                    
                <form action="main.php?page=servizi_banca" method="post">
                    
                 

<tr>
    <td rowspan=2 align=left valign=center width=100><b>Trasferimento</b></td>
    <td align=left valign=center>Importo: 
    <input type="text" name="ammontare" class="form_gestione_input" value="0" />
    </td>
    
    <td align=left valign=center>Beneficiario: 
    <input name="op" type="hidden" class="form_gestione_input" value="bonifico" />
    <input type="text" name="beneficiario" class="form_gestione_input" />
    </td>
    
    <td rowspan=2 align=left valign=center>
    <input name="conferma" type="submit" class="form_gestione_input" value="TRASFERISCI" style="width: 80px;" />
    </td>
</tr> 

	<td colspan=2 align=left valign=center>Causale: 
    <input type="text" name="causale" class="form_gestione_input" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['cause']); ?>" />
    </td>   
</form>
</div>

<!-- Deposito -->
<div class="form_gioco">
<form action="main.php?page=servizi_banca" method="post">
<tr>
    <td align=left valign=center width=100><b>Deposito</b></td>
    <td align=left valign=center colspan=2>Importo: 
    <input type="text" name="ammontare" class="form_gestione_input" value="0" />
    </td>
    
    <td align=left valign=center>
    <div class='form_submit'>
    <input name="op" type="hidden" class="form_gestione_input" value="deposita" />
    <input name="conferma" type="submit" class="form_gestione_input" value="DEPOSITA" style="width: 80px;" />
    </div>
    </td>
</tr>  
</form>
</div>

<!-- Prelievo -->
<div class="form_gioco">
<form action="main.php?page=servizi_banca" method="post">
<tr>

    <td align=left valign=center width=100><b>Prelievo</b></td>
    <td align=left valign=center colspan=2>Importo: 
    <input type="text" name="ammontare" class="form_gestione_input" value="0" />
    </td>
    
    <td align=left valign=center>
    <div class='form_submit'>
    <input name="op" type="hidden" class="form_gestione_input" value="preleva" />
    <input name="conferma" type="submit" class="form_gestione_input" value="PRELEVA" style="width: 80px;" />
    </div>
    </td>
</tr>
</form>
</div>


 <!-- Stipendio -->
<tr>
    <td align=left valign=center width=100><b>Paga</b></td>
    <td align=left valign=center colspan=2>Importo: 
    <?php echo gdrcd_filter('out', $MESSAGE['interface']['bank']['pay']).' ('.gdrcd_filter('out', $MESSAGE['interface']['bank']['credit']
                                        ).': '.$stipendio_tot.' '.$PARAMETERS['names']['currency']['plur'].') '; ?>
    </td>
    
    <td align=left valign=center>
    <?php
                if($ultimo >= strftime("%Y-%m-%d")) {
                    echo gdrcd_filter('out', $MESSAGE['interface']['bank']['credit_no']);
                } else {
                    if($stipendio_tot > 0) {
                    ?>
                           <div class="form_gioco">
                           <form action="main.php?page=servizi_banca" method="post">
                           
                                <div class='form_submit'>
                                    <input name="ammontare" type="hidden" class="form_gestione_input" value="<?php echo $stipendio; ?>" />
                                    <input name="op" type="hidden" class="form_gestione_input" value="incassa" />
                                    <input name="conferma" type="submit" class="form_gestione_input" value="RITIRA" />
                                </div>
                            </form>
                        </div>
                         
                         
                       <?php } else {
                        echo gdrcd_filter('out', $MESSAGE['interface']['bank']['credit']).": ".$stipendio." ".$PARAMETERS['names']['currency']['plur']." ";
                    }
                } ?>




</td>
</tr>
</table>
                    
                
                
                </div>
                
                
                
                
                
                
                
                
                
         <?php       
                
                }//chiusura op
        ?>
          <div class="panels_link">
                <br><a href="main.php?page=uffici">Torna indietro</a>
            </div>
</div>
</div>