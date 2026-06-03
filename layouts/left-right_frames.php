<?php
/**
 * left-right_frames.php — Layout a tre colonne per Crystal Tokyo GDR
 *
 * Struttura:
 *   - #framecontentLeft  (260px fissa, sinistra):  InfoLocation, LinkMenu, FrameMessaggi
 *   - #maincontent       (centro adattivo):         pagina corrente (SPA React o PHP)
 *   - #framecontentRight (260px fissa, destra):     AnteprimaScheda, OnlineUsers
 *
 * I componenti React vengono montati via ct:ready (header.inc.php).
 *
 * @author Crystal Tokyo Dev
 */
if (isset($_GET['css'])) { header('Content-Type:text/css; charset=utf-8'); exit; }
?>
<!-- COLONNA SINISTRA -->
<div id="framecontentLeft">
    <div id="info-location-container"></div>
    <!-- <div id="link-menu-container"></div> -->
    <div class="presenti_button">
        <a href="main.php?page=presenti_estesi">
            <img src="themes/crystal/imgs/menu/presenti.png" alt="Presenti" />
        </a>
    </div>
    <div id="online-users-container"></div>
    <div id="chattina-off-container"></div>
</div>

<!-- COLONNA DESTRA -->
<div id="framecontentRight">
    <div id="anteprima-scheda-container"></div>
    <div id="frame-messaggi-container"></div>
    <div id="meteo-container"></div>
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

<button class="mobile-toggle" id="toggleLeft">☰</button>
<button class="mobile-toggle" id="toggleRight">☰</button>

<script>
    // Gestione modale skill
    var modal = document.getElementById('id01');
    window.onclick = function(event) {
        if (event.target == modal) { modal.style.display = 'none'; }
    }
    function changeFrame(input_text) {
        document.getElementById('myframe').src = input_text;
    }

    // Gestione sidebar mobile: toggle, overlay, ESC, resize
    document.addEventListener('DOMContentLoaded', function() {
        const toggleLeft  = document.getElementById('toggleLeft');
        const toggleRight = document.getElementById('toggleRight');
        const sidebarLeft  = document.getElementById('framecontentLeft');
        const sidebarRight = document.getElementById('framecontentRight');
        const mainContent  = document.getElementById('maincontent');
        const overlay = document.createElement('div');

        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);

        let currentOpenSidebar = null;
        let isMobile = window.innerWidth <= 768;

        // Forza un reset completo del layout (usato dopo chiusura sidebar su mobile)
        function forceLayoutReset() {
            document.body.style.overflowX = 'hidden';
            document.body.style.width     = '100%';
            document.body.classList.add('sidebars-closed');
            // Rimuoviamo il transform invece di impostarlo a translateX(0):
            // translateX(0) è visivamente no-op ma crea un containing block per
            // position:fixed, spostando fuori schermo i popup (chat, role, ecc.)
            mainContent.style.transform  = '';
            mainContent.style.width      = '100%';
            mainContent.style.left       = '0';
            mainContent.style.right      = '0';
            mainContent.style.overflowX  = 'hidden';
            void document.body.offsetHeight; // forza reflow
            document.documentElement.style.overflowX = 'hidden';
        }

        function closeAllSidebars() {
            sidebarLeft.classList.remove('expanded');
            sidebarRight.classList.remove('expanded');
            overlay.classList.remove('active');
            currentOpenSidebar = null;
            mainContent.style.overflow = 'auto';
            document.body.style.overflowY = 'auto';
            setTimeout(forceLayoutReset, 50);
            setTimeout(forceLayoutReset, 200);
        }

        function toggleSidebar(sidebar) {
            const isExpanded = sidebar.classList.contains('expanded');
            closeAllSidebars();
            if (!isExpanded) {
                setTimeout(() => {
                    sidebar.classList.add('expanded');
                    overlay.classList.add('active');
                    currentOpenSidebar = sidebar;
                    mainContent.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                }, 100);
            }
        }

        toggleLeft.addEventListener('click',  function(e) { e.stopPropagation(); e.preventDefault(); toggleSidebar(sidebarLeft);  });
        toggleRight.addEventListener('click', function(e) { e.stopPropagation(); e.preventDefault(); toggleSidebar(sidebarRight); });

        overlay.addEventListener('click', function(e) {
            e.stopPropagation(); e.preventDefault(); closeAllSidebars();
        });

        mainContent.addEventListener('click', function(e) {
            if (currentOpenSidebar) { e.stopPropagation(); e.preventDefault(); closeAllSidebars(); }
        });

        // Previeni che un click dentro la sidebar la chiuda
        sidebarLeft.addEventListener('click',  function(e) { e.stopPropagation(); });
        sidebarRight.addEventListener('click', function(e) { e.stopPropagation(); });

        window.addEventListener('resize', function() {
            const newIsMobile = window.innerWidth <= 768;
            if (newIsMobile !== isMobile) {
                isMobile = newIsMobile;
                closeAllSidebars();
                if (!isMobile) {
                    // Desktop: ripristina visibilità colonne
                    sidebarLeft.style.visibility  = 'visible';
                    sidebarRight.style.visibility = 'visible';
                    sidebarLeft.style.transform   = 'translateX(0)';
                    sidebarRight.style.transform  = 'translateX(0)';
                    document.body.classList.remove('sidebars-closed');
                } else {
                    // Mobile: nascondi colonne
                    sidebarLeft.style.visibility  = 'hidden';
                    sidebarRight.style.visibility = 'hidden';
                    sidebarLeft.style.transform   = 'translateX(-100%)';
                    sidebarRight.style.transform  = 'translateX(100%)';
                    forceLayoutReset();
                }
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && currentOpenSidebar) { closeAllSidebars(); }
        });

        // MutationObserver per prevenire overflow orizzontale su mobile
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.type === 'childList' && isMobile) { setTimeout(forceLayoutReset, 100); }
            });
        });
        observer.observe(mainContent, { childList: true, subtree: true });

        if (isMobile) forceLayoutReset();
    });
</script>
