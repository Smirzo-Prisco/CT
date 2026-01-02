<?php

session_start();

/* Includo i file necessari */
include('../includes/constant_values.inc.php');
include('../config.inc.php');
include('../vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('../includes/functions.inc.php');

/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();

//valorizzo tutto così mi levo dalle balle sta cosa

$output     = "";
$refresh    = false;
$login      = $_SESSION['login'];
$username   = $_REQUEST['username'];
$id   = $_REQUEST['id'];
$pg         = null;

if ($_GET['pg']) {
    $pg = $_GET['pg'];
}

 if(isset($_POST['op']) === true) {
 
 /*Modifica di un record*/
 if(gdrcd_filter('get', $_POST['op']) == 'modify') {
 /*Processo le informazioni ricevute dal form*/
                gdrcd_query("UPDATE struttura_affetti SET nomePg ='".gdrcd_filter('in', $_POST['nomePg'])."', contenuto = '".gdrcd_filter('in', $_POST['contenuto'])."', titolo = '".gdrcd_filter('in', $_POST['titolo'])."', tipologia = '".gdrcd_filter('in', $_POST['tipologia'])."', avatar = '".gdrcd_filter('in', $_POST['avatar'])."' WHERE id = ".gdrcd_filter('num', $_POST['id'])." LIMIT 1");
                echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nomePg"]." modificato');</script>";
 
 }
                
 /*Carichiamo l'oggetto tra quelli da controllare*/
 if(gdrcd_filter('get', $_POST['op']) == 'insert') {

	$username =$_POST['username'];
    $nomePg = $_POST['nomePg'];
	$titolo = $_POST['titolo'];
 	$contenuto = $_POST['contenuto'];
    $tipologia =$_POST['tipologia'];
    $avatar =$_POST['avatar'];
    
  
 echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nomePg"]." inserito');</script>";

    gdrcd_query("INSERT INTO struttura_affetti (username, nomePg, titolo, tipologia, avatar, contenuto) VALUES ('".gdrcd_filter('in', $username)."', '".gdrcd_filter('in', $nomePg)."', '".gdrcd_filter('in', $titolo)."', '".gdrcd_filter('in', $tipologia)."', '".gdrcd_filter('in', $avatar)."', '".gdrcd_filter('in', $contenuto)."')");

    echo "<script type='text/javascript'>alert('Affetto caricato!');</script>";
    $refresh= true;

}
}

                   if($_POST['op'] == 'edit') {
                   /*Carico il record da modificare*/
                    $loaded_location = gdrcd_query("SELECT * FROM struttura_affetti WHERE id=".gdrcd_filter('num', $_POST['id'])." LIMIT 1 ");
                    /*Cambio l'operazione in modifica*/
                    $operation = 'edit';
                }
$affection_types = array('legami', 'nemici', 'famiglia', 'conoscenze', 'memories');

?>


<script src="core/js/libs/jquery/jquery-1.11.2.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $(document).on("click", "a.tile-content", function (e) {
            e.preventDefault();
            var link = $(this).attr("href");
            var iframe = $("iframe#content");
            iframe.attr("src", link);
            iFrameResize();
        });
    });

    function iFrameResize() {
        try {
            var iframe = document.getElementById("content");
            iframe.height = (iframe.contentWindow.document.body.scrollHeight) + "px";
        } catch (e) {

        }
    }

    try {
        var inIframe = (window.self !== window.top);
        if (!inIframe) {
            var head = document.getElementsByTagName('head')[0];
            var link = document.createElement('link');
            link.rel    = 'stylesheet';
            link.type   = 'text/css';
            link.href   = '../../css/iframe.css';
            link.media  = 'all';
            head.appendChild(link);
        }
    } catch (e) {
    }
</script>
<link rel="stylesheet" href="../themes/crystal/scheda_affetto.css" TYPE="text/css">
<div class="pagina_scheda">

<!-- INIZIO -->

<form action="form_affetto.php" method="post" class="form_gestione">
<table width="100%" class="grid" cellpadding="2" cellspacing="1" border="0">

            <tr>
                <td align="center" class="testa_nera">
                    <span class="titoli"><?php echo "Aggiungi affetto" ?></span>
                </td>
            </tr>
            
            <tr>
                <td align="center" valign="middle" class="testa">
                    <span class="titoli sottotitoli">Personaggio</span>
                </td>
            </tr>
            <tr>
                <td class="testa_nera">
                    <input type="text" name="nomePg" class="mod-scheda" value="<?php echo gdrcd_filter('out', $loaded_location['nomePg']); ?>" placeholder="Inserisci nome personaggio">
                </td>
            </tr>
            
            <tr>
                <td align="center" valign="middle" class="testa">
                    <span class="titoli sottotitoli">Titolo</span>
                </td>
            </tr>
            <tr>
                <td class="testa_nera">
                    <input type="text" name="titolo" class="mod-scheda" value="<?php echo gdrcd_filter('out', $loaded_location['titolo']); ?>" placeholder="Es: l'amato">
                </td>
            </tr>
            <tr>
                <td align="center" valign="middle" class="testa">
                    <span class="titoli sottotitoli">Tipologia</span>
                </td>
            </tr>
            <tr>
                <td class="testa_nera">
                                    <select name="tipologia" class="mod-scheda">
                        <?php
                        foreach ($affection_types as $type) {
                            echo "<option value='".$type."'";
                            if ($type == $loaded_location['tipologia']) echo " selected";
                            echo ">".ucwords($type)."</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td align="center" valign="middle" class="testa">
                    <span class="titoli sottotitoli">Dettaglio</span>
                </td>
            </tr>
            <tr>
                <td class="testa_nera">
                    <textarea name="contenuto" rows="20" class="mod-scheda" style="width: 100%;" placeholder="Contenuto..." value="contenuto"><?php echo gdrcd_filter('out', $loaded_location['contenuto'])?></textarea>
                </td>
            </tr>
            <tr>
                <td align="center" valign="middle" class="testa">
                    <span class="titoli sottotitoli">Avatar</span>
                </td>
            </tr>
            <tr>
                <td class="testa_nera">
                    <input type="text" name="avatar" class="mod-scheda" value="<?php echo gdrcd_filter('out', $loaded_location['avatar']); ?>" placeholder="Url completo dell'immagine 100x100">
                </td>
            </tr>

            <tr>
                <td class="testa_nera" align="right" valign="middle">
                
                    <?php /* Se l'operazione è una modifica stampo i tasti modifica e annulla */
                            if($operation == "edit") { ?>
                                <input type="hidden" name="username" value="<?php echo $_SESSION['login']; ?>" />
                                <input type="hidden" name="id" value="<?php echo gdrcd_filter('out', $loaded_location['id']); ?>">
                                <input type="hidden" name="op" value="modify">
                                <input type="submit" value="Modifica" />
                            <?php } /* Altrimenti il tasto inserisci */ else { ?>
                                <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>" />
                                <input type="hidden" name="op" value="insert">
                                <input type="submit" value="Inserisci" />
                            <?php } ?>

                </td>
            </tr>

</table>
</form>

<div id='divpvt' name='divpvt' class='pvt_vdiv' style='display: inline; visibility: hidden; background-color:#000000'>
        <table align="center" width="300" cellpadding="2" cellspacing="3" style="background-color: #111111; border:1px dotted #333333;">
            <tr>
                <td style="border: none; color:#FEFEFE;" align="justify" valign="middle">
                    <span class="testo_base"><?php echo $output; ?></span>
                </td>
            </tr>
            <tr>
                <td style="border:none" align="right" valign="middle">
                    <input type='button' name="chiudi" value="Chiudi" class="bttn" onClick="hideOutput('divpvt');">
                </td>
            </tr>
        </table>
    </div>

    <?php if (!empty($output)) { ?>
        <script type="text/javascript">
            showOutput('divpvt', false);
        </script>
    <?php } ?>

    <?php if ($refresh) { ?>
        <script type="text/javascript">
            setTimeout(function() {
                parent.location.reload();
            }, 5000);
        </script>
    <?php } ?>