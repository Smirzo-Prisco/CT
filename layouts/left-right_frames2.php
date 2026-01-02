<?php
/** * Pagina di layout.
 * E' selezionabile come layout principale per il proprio gdr semplicemente da config.inc.php
 * Contiene il css che viene richiamato separatamente come file esterno e il markup
 *
 * Il layout è a piena compatibilità con i browser.
 * La scelta di inserire qui il css ad esso destinato è per limitarne la modifica da parte dell'utente
 * consentendogli di personalizzare tutto il resto senza rovinare la compatibilità cross browser
 *
 * @author Blancks
 */
if (isset($_GET['css']))
{
    header('Content-Type:text/css; charset=utf-8');


    ?>@charset "utf-8";
	
    body{
    margin: 0;
    padding: 0;
    border: 0;
    overflow: hidden;
    height: 100%;
    max-height: 100%;
    overflow-y: auto;
    }

    #framecontentLeft, #framecontentRight{
	position: absolute;
    top: 0;
    left: 0;
    width: 260px; /*Width of left frame div*/
    height: 100%;
    overflow: auto; /*Disable scrollbars. Set to "scroll" to enable*/
    }

    #framecontentRight{
    left: auto;
    right: 0;
    width: 260px; /*Width of right frame div*/
    overflow: auto; /*Disable scrollbars. Set to "scroll" to enable*/
    color: white;
	}
   

    #maincontent{
    position: fixed;
    top: 0;
    left: 260px; /*Set left value to WidthOfLeftFrameDiv*/
    right: 260px; /*Set right value to WidthOfRightFrameDiv*/
    bottom: 0;
    overflow: auto;
    }

    .innertube{
    margin: 5px 5px 5px 10px; /*Margins for inner DIV inside each DIV (to provide padding)*/
    }

    * html body{ /*IE6/IE9 hack*/
    padding: 10px 215px 10px 210px; /*Set value to (0 WidthOfRightFrameDiv 0 WidthOfLeftFrameDiv)*/
    }

    * html #maincontent{ /*IE6/IE9 hack*/
    height: 100%;
    width: 100%;
    }


    <?php

} else
{


    if ($PARAMETERS['left_column']['activate'] == 'ON')
    {

        ?>
        <!-- Colonna sinistra -->
        <div id="framecontentLeft">
            <div class="innertube">

                <div class="colonne_sx">
                    <?php
                    foreach ($PARAMETERS['left_column']['box'] as $box)
                    {
                        echo '<div class="' . $box['class'] . '">';

                        gdrcd_load_modules('pages/' . $box['page'] . '.inc.php', $box);

                        echo '</div>';
                    }

                    ?>
                </div>

            </div>
        </div>
        <?php

    }


    if ($PARAMETERS['right_column']['activate'] == 'ON')
    {
        ?>

        <!-- Colonna destra -->
        <div id="framecontentRight">
            <div class="innertube">

                <div class="colonne_dx">
                    <?php

                    foreach ($PARAMETERS['right_column']['box'] as $box)
                    {
                        echo '<div class="' . $box['class'] . '">';

                        gdrcd_load_modules('pages/' . $box['page'] . '.inc.php', $box);

                        echo '</div>';

                    }

                    ?>
                </div>

            </div>
        </div>

        <?php

    }
    ?>

    <div id="maincontent">
        <div class="output">
        <!-- Popup per mostrare cose -->
			<div id="id01" class="modal">
  
              <form class="modal-content animate" action="/action_page.php" method="post">
                <div class="imgcontainer">
                  <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">Chiudi</span>
                </div>

                <div class="container2">
                  <iframe id="myframe" src="/default.php"></iframe>
                </div>
              </form>
            </div>
            <?php gdrcd_load_modules('pages/' . $strInnerPage); ?>
        </div>
    </div>

    <?php

}

?>
<?php
	$news = gdrcd_query("SELECT * FROM ctnews ORDER BY data DESC LIMIT 1");
?>
<div id="ctnews" style="height: 0px; opacity: 0;">
<div class="left">
<img src="themes/crystal/imgs/icone/CTNEWS.png">
</div>
<div class="right">
<marquee align="middle" behavior="scroll" direction="left"scrolldelay="10"><?php echo $news['titolo'];?>: <?php echo $news['contenuto'];?></marquee>
</div>
</div>
<script>
var modal = document.getElementById('id01');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function changeFrame(input_text) {
  document.getElementById("myframe").src = input_text;
}
</script>