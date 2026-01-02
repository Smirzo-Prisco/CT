<link rel="stylesheet" href="themes/crystal/famiglie.css">
<div class="pagina_servizi_gilde">
    <!-- Titolo della pagina -->
    <div class="page_title"><h2>Inclinazioni</h2></div>
    <!-- Box principale -->
    <div class="page_body">
        <?php /*Visualizzaione elenco gilde*/
        if(isset($_REQUEST['id_inclinazione']) === false) {
            $result = gdrcd_query("SELECT * FROM inclinazione WHERE visibile = 1 ORDER BY tipo, id_inclinazione", 'result');

            $last_type = -1;
        ?>
        <table class="customTable">
            <tr><td colspan="3" style="font-size: 13px; color: #9a6353; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));">ELENCO CORRENTI</td></tr>
            <tr class="second_header">
                <td width="50%"><?=gdrcd_filter('out', $PARAMETERS['names']['corrente_name']['sing'])?></td>
                <td width="40%">Referente</td>
                <td><?=gdrcd_filter('out', $PARAMETERS['names']['guild_name']['members'])?></td>
            </tr>
    <?php
            $last_type = $row['tipo'];

            while($row = gdrcd_query($result, 'fetch')) {
                $numb = gdrcd_query("SELECT COUNT(*) FROM clgpersonaggioinclinazione 
                                    JOIN ruolo_inclinazione ON clgpersonaggioinclinazione.id_ruolo = ruolo_inclinazione.id_ruolo 
                                    WHERE ruolo_inclinazione.inclinazione = ".$row['id_inclinazione']); ?>
                <tr>
                    <td width="50%"><a href="main.php?page=servizi_inclinazione&id_inclinazione=<?=$row['id_inclinazione']?>"><?=gdrcd_filter('out', $row['nome'])?></a></td>
                    <td width="40%"><a href="main.php?page=scheda&pg=<?=$row['referente']?>"><?=gdrcd_filter('out', $row['referente'])?></a></td>
                    <td style="font-size: 12px; color: #8f8f8f; font-family: DejaVu Serif; filter: drop-shadow(0 0 5px rgba(0,0,0,0.57));"><?=$numb['COUNT(*)']?></td>
                </tr>
        <?php
            }
    ?> 
        </table>  
<?php /* Visualizzazione estesa gilda */
        } else {
            /* Elenco affiliati */
            $query = "SELECT clgpersonaggioinclinazione.*, inclinazione.* FROM inclinazione JOIN clgpersonaggioinclinazione ON clgpersonaggioinclinazione.id_ruolo = inclinazione.id_inclinazione WHERE id_inclinazione = ".gdrcd_filter('num', $_REQUEST['id_inclinazione'])."";                    
            $result = gdrcd_query($query, 'result'); ?>
                    <table class="customTable">
                        <tr><td colspan="3"><div><?=gdrcd_filter('out', $MESSAGE['interface']['guilds']['members'])?></div></td><tr>
                        <tr class="second_header">
                            <td><div>&nbsp;</div></td>
                            <td><div><?=gdrcd_filter('out', $MESSAGE['interface']['guilds']['member'])?></div></td>
                            <!-- Elenco -->
                            <?php
                            while($row = gdrcd_query($result, 'fetch')){
                            ?>
                                <tr>
                                    <td><div><img src="imgs/inclinazioni/<?=gdrcd_filter('out', $row['immagine'])?>"></div></td>
                                    <td><div><a href="main.php?page=scheda&pg=<?=gdrcd_filter('out', $row['personaggio'])?>"><?=gdrcd_filter('out', $row['personaggio'].' '.$row['cognome'])?></a></div></td>
                                </tr>
                        <?php
                            }
                            gdrcd_query($result, 'free');
                        ?>
                    </table>
                <!--elenco_breve-->
            <?php /* statuto */
            $statuto = gdrcd_query("SELECT statuto FROM gilda WHERE id_gilda = ".gdrcd_filter('num', $_REQUEST['id_gilda'])."");

            if(empty($statuto['statuto']) === false) { ?>
                <table>
                    <tr class="second_header"><td colspan="4"><div>Statuto</div></td><tr>
                    <tr><td><div style="text-align: justify;"><?=gdrcd_bbcoder(gdrcd_filter('out', $statuto['statuto']))?></div></td></tr>
                </table>
            <?php } ?>
            <div class="link_back"><a href="main.php?page=servizi_gilde"><?=gdrcd_filter('out', $MESSAGE['interface']['guilds']['back'])?></a></div>
        <?php
        } ?>
    </div>
    <!-- Box principale -->
</div>