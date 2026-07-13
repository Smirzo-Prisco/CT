<?php
/**
 * left-right_frames.php — Layout Crystal Tokyo GDR
 *
 * Struttura:
 *   - #hud-container (HUD immersivo, fixed overlay): due anelli (luogo a
 *     sinistra, personaggio a destra) + topbar comune, ciascuno espandibile
 *     in un arco di icone — sostituisce le vecchie colonne framecontentLeft/
 *     framecontentRight (InfoLocation, PresentiBadge, OnlineUsers,
 *     ChattingOff, AnteprimaScheda, FrameMessaggi, Meteo).
 *   - #maincontent (centro, a schermo intero): pagina corrente (SPA React o PHP)
 *
 * Il componente React viene montato via ct:ready (header.inc.php).
 *
 * @author Crystal Tokyo Dev
 */
if (isset($_GET['css'])) { header('Content-Type:text/css; charset=utf-8'); exit; }
?>
<div id="hud-container"></div>

<div id="iubenda-container">
    <a href="https://www.iubenda.com/privacy-policy/18155810" class="iubenda-black iubenda-noiframe iubenda-embed" title="Privacy Policy">Privacy Policy</a>
    &nbsp;·&nbsp;
    <a href="https://www.iubenda.com/privacy-policy/18155810/cookie-policy" class="iubenda-black iubenda-noiframe iubenda-embed" title="Cookie Policy">Cookie Policy</a>
    <script type="text/javascript">(function (w,d) {var loader = function () {var s = d.createElement("script"), tag = d.getElementsByTagName("script")[0]; s.src="https://cdn.iubenda.com/iubenda.js"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener("load", loader, false);}else if(w.attachEvent){w.attachEvent("onload", loader);}else{w.onload = loader;}})(window, document);</script>
</div>

<!-- ====================================================================== -->
<!-- CONTENUTO PRINCIPALE: pagina corrente (SPA React o PHP classico)        -->
<!-- ====================================================================== -->
<div id="maincontent">
    <div class="output">

        <!-- Modale per la descrizione delle skill lanciate in chat -->
        <div id="id01" class="modal">
            <form class="modal-content animate" action="/action_page.php" method="post">
                <div class="imgcontainer">
                    <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">Chiudi</span>
                </div>
                <div class="container2">
                    <iframe id="myframe" src="about:blank"></iframe>
                </div>
            </form>
        </div>

        <?php
        // Thin shell: pagine migrate → container React diretto, senza I/O su file .inc.php.
        // Pagine non migrate (gestione sub-pagine, tool staff) → include PHP classico.
        if ($strInnerPage === null):
        ?>
        <div id="ct-app-content"></div>
        <?php else: ?>
        <?php gdrcd_load_modules('pages/' . $strInnerPage); ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Gestione modale skill
    var modal = document.getElementById('id01');
    window.onclick = function(event) {
        if (event.target == modal) { modal.style.display = 'none'; }
    }
    function changeFrame(input_text) {
        document.getElementById('myframe').src = input_text;
    }
</script>
