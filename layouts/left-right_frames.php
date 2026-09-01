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

<!-- Link semplici (nessuna classe iubenda-embed/badge): iubenda.js "badgeifica"
     ogni .iubenda-embed in un pulsante con width/height fissi via JS
     (116x25px ciascuno) che ignora completamente il nostro CSS — su mobile
     finiva sempre a ridosso di qualche pulsante reale (menu HUD, composer
     messaggi), qualunque fosse la posizione del contenitore. Un <a> semplice
     resta piccolo e discreto come da _layout.scss, senza che iubenda.js lo
     tocchi (nessuno script da caricare). target=_blank: sono link esterni,
     non deve far perdere lo stato della sessione/mappa in corso. -->
<div id="iubenda-container">
    <a href="https://www.iubenda.com/privacy-policy/18155810" target="_blank" rel="noopener" title="Privacy Policy">Privacy Policy</a>
    &nbsp;·&nbsp;
    <a href="https://www.iubenda.com/privacy-policy/18155810/cookie-policy" target="_blank" rel="noopener" title="Cookie Policy">Cookie Policy</a>
</div>

<!-- ====================================================================== -->
<!-- CONTENUTO PRINCIPALE: pagina corrente (SPA React o PHP classico)        -->
<!-- ====================================================================== -->
<div id="maincontent">
    <div class="output">

        <!-- Placeholder inerte: contenuti storici in DB (es. mappa.descrizione,
             mai sanitizzati) possono ancora referenziare document.getElementById('id01')
             dal vecchio modale skill (sostituito da SkillDescModal.jsx) — senza
             questo elemento quella chiamata lancerebbe un TypeError. Vedi anche
             lo shim changeFrame() in corefunctions.js. -->
        <div id="id01" style="display:none"></div>

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
