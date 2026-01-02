<?php
include('../ref_header.inc.php');

if(empty($_SESSION['last_istant_message']) === true) {
    $_SESSION['last_istant_message'] = 0;
}

$non_letti = gdrcd_query("SELECT id FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0 AND id > ".$_SESSION['last_istant_message']."", 'result');

$max_id = gdrcd_query("SELECT max(id) as max FROM messaggi WHERE destinatario = '".gdrcd_filter('in', $_SESSION['login'])."' AND letto=0"); 
?>
<link rel="stylesheet" href="../themes/crystal/hover.css">
<div class="iframe_icone">
<table class="tg">
<tbody>
  <tr>
    <td class="tg-0lax">
    <?php
    if(date("G")>=18 OR date("G")<=6){
    ?>
    <div id="famiglie">
    <div id="pic-wrapper">
    <a href="../main.php?page=servizi_gilde" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_fam.png" title="Famiglie" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_fam_night.gif" title="Famiglie" id="pic-inner">
    </a>
    </div>
    </div>
    
    <div id="mestieri">
    <div id="pic-wrapper">
    <a href="../main.php?page=servizi_mestieri" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_job.png" title="Mestieri" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_job_night.gif" title="Mestieri" id="pic-inner">
    </a>
    </div>
    </div>
   
    <div id="tv">
    <div id="pic-wrapper">
    <a href="../main.php?dir=<?php echo $_SESSION['luogo']?>" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_news.png" title="CTNews" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_news_night.gif" title="CTNews" id="pic-inner">
    </a>
    </div>
    </div>
    
    <?php } else { ?>
    <div id="famiglie">
    <div id="pic-wrapper">
    <a href="../main.php?page=servizi_gilde" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_fam.png" title="Famiglie" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_fam_hover.gif" title="Famiglie" id="pic-inner">
    </a>
    </div>
    </div>
    
    <div id="mestieri">
    <div id="pic-wrapper">
    <a href="../main.php?page=servizi_mestieri" target="_top">
    <img src="../themes/crystal/imgs/icone/icon_job.png" title="Mestieri" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_job_hover.gif" title="Mestieri" id="pic-inner">
    </a>
    </div>
    </div>
    
    <div id="tv">
    <div id="pic-wrapper">
    <a target='_parent' href="#" onclick="changeFrame('../pages/frame_calendario.inc.php');document.getElementById('id01').style.display='block';">
    <img src="../themes/crystal/imgs/icone/icon_news.png" title="Calendario" id="pic">
    <img src="../themes/crystal/imgs/icone/icon_news_hover.gif" title="Calendario" id="pic-inner">
    </a>
    </div>
    </div>
    
    <?php } ?>
  
  </tr>
</tbody>
</table>
</div><br><br>
<?php include('../footer.inc.php');  /*Footer comune*/ ?>
