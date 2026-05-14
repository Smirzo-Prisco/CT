<?php
/**
 * left-right_frames.php — Layout a tre colonne per Crystal Tokyo GDR
 *
 * Struttura visiva:
 *   - Colonna sinistra (260px fissa):  InfoLocation, LinkMenu, FrameMessaggi
 *   - Contenuto centrale (adattivo):   pagina corrente (SPA React o PHP classico)
 *   - Colonna destra   (260px fissa):  AnteprimaScheda, MenuIcons, OnlineUsers
 *
 * Phase 3.4 — i container sidebar sono hardcoded direttamente in questo file;
 * il loop dinamico su $PARAMETERS['left_column']['box'] / right_column è stato
 * rimosso. Tutti i componenti React vengono montati da footer.inc.php via ct:ready.
 *
 * Compatibilità: il parametro ?css=true era usato da header.inc.php per caricare
 * il CSS del layout come stylesheet separato. Il blocco <style> viene comunque
 * sempre emesso nell'HTML della pagina (inline), quindi il link è ridondante
 * ma mantenuto per non toccare header.inc.php in questa fase.
 *
 * @author Crystal Tokyo Dev
 */

// Quando richiesto come stylesheet (header.inc.php lo carica via <link>),
// impostiamo il Content-Type corretto. Il <style> inline nella pagina
// principale continua a funzionare indipendentemente.
if (isset($_GET['css'])) { header('Content-Type:text/css; charset=utf-8'); exit; }
?>
<style>
	@charset "utf-8";

	body {
		margin: 0;
		padding: 0;
		border: 0;
		overflow: hidden;
		height: 100%;
		max-height: 100%;
		overflow-y: auto;
		/* Previeni scroll orizzontale a livello root */
		overflow-x: hidden;
		width: 100%;
		position: relative;
	}

	#framecontentLeft, #framecontentRight {
		position: fixed;
		top: 0;
		left: 0;
		width: 260px;
		height: 100%;
		overflow: auto;
		z-index: 1000;
		transition: transform 0.3s ease;
		background: your-background-here;
	}

	#framecontentRight {
		left: auto;
		right: 0;
		color: white;
	}

	#maincontent {
		position: fixed;
		top: 0;
		left: 260px;
		right: 260px;
		bottom: 0;
		overflow: auto;
		/* Assicurati che non crei scroll orizzontale */
		width: calc(100% - 520px);
		box-sizing: border-box;
	}

	.innertube {
		margin: 5px 5px 5px 10px;
	}

	/* Pulsanti per mobile (nascosti di default su desktop) */
	.mobile-toggle {
		display: none;
		position: fixed;
		bottom: 10px;
		z-index: 1001;
		background: rgba(0,0,0,0.7);
		color: white;
		border: none;
		padding: 10px 15px;
		cursor: pointer;
		border-radius: 5px;
		font-size: 14px;
	}

	#toggleLeft  { left:  10px; }
	#toggleRight { right: 10px; }

	/* Media Query per Mobile */
	@media screen and (max-width: 768px) {
		body {
			overflow-x: hidden !important;
			width: 100% !important;
			position: relative !important;
		}

		#framecontentLeft, #framecontentRight {
			width: 280px;
			transform: translateX(-100%);
			visibility: hidden;
			transition: transform 0.3s ease, visibility 0.3s ease;
		}

		#framecontentRight {
			transform: translateX(100%);
			right: -30px;
		}

		#framecontentLeft { left: -30px; }

		#framecontentLeft.expanded,
		#framecontentRight.expanded {
			transform: translateX(0);
			visibility: visible;
		}

		#maincontent {
			left: 0 !important;
			right: 0 !important;
			width: 100% !important;
			/* RIMOSSO: transform: translateX(0) — anche translateX(0) crea un
			   containing block per position:fixed, facendo sì che i popup (es.
			   #pgRolePlayingPanel) si posizionino relativi a #maincontent
			   invece che al viewport. Reset via left/right/width è sufficiente. */
			overflow-x: auto !important;
		}

		.mobile-toggle { display: block; }

		/* Overlay semitrasparente quando una colonna è aperta */
		.sidebar-overlay {
			display: none;
			position: fixed;
			top: 0; left: 0; right: 0; bottom: 0;
			background: rgba(0,0,0,0.5);
			z-index: 999;
		}

		.sidebar-overlay.active { display: block; }

		/* Reset completo quando le colonne sono chiuse */
		body.sidebars-closed {
			overflow-x: hidden !important;
			width: 100% !important;
		}

		body.sidebars-closed #maincontent {
			transform: translateX(0) !important;
			width: 100% !important;
			left: 0 !important;
			right: 0 !important;
		}
	}

	/* Previeni overflow orizzontale nel contenuto principale */
	#maincontent .output {
		width: 100%;
		max-width: 100%;
		box-sizing: border-box;
	}
</style>

<!-- ====================================================================== -->
<!-- COLONNA SINISTRA: InfoLocation + LinkMenu + FrameMessaggi               -->
<!-- I container React sono montati da footer.inc.php via ct:ready           -->
<!-- ====================================================================== -->
<div id="framecontentLeft" style="background-image:url('../themes/crystal/imgs/colonna_sx/colonna_sinistra.png'); background-repeat: no-repeat;">
    <div class="innertube">
        <div class="colonne_sx">
            <div class="info"><div id="info-location-container"></div></div>
            <div class="menu"><div id="link-menu-container"></div></div>
            <div class="msgs"><div id="frame-messaggi-container"></div></div>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- COLONNA DESTRA: AnteprimaScheda + MenuIcons + OnlineUsers               -->
<!-- ====================================================================== -->
<link rel="stylesheet" href="../themes/crystal/hover.css">
<div id="framecontentRight" style="background-image:url('../themes/crystal/imgs/colonna_dx/colonna_destra.png'); background-repeat: no-repeat;">
    <div class="innertube">
        <div class="colonne_dx">
            <div class="anteprima_scheda"><div id="anteprima-scheda-container"></div></div>
            <div class="icons"><div id="menu-icons-container"></div></div>
            <div class="online">
                <div class="pagina_presenti">
                    <div class="page_title">
                        <h2><?= gdrcd_filter('out', $MESSAGE['interface']['logged_users']['plur']) ?></h2>
                    </div>
                    <div id="online-users-container"></div>
                </div>
            </div>
        </div>
    </div>
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
                    <iframe id="myframe" src="/default.php"></iframe>
                </div>
            </form>
        </div>

        <?php
        /**
         * TODO thin shell (passo finale SPA):
         * Quando tutte le pagine saranno migrate a React, sostituire questa
         * riga con il container diretto:
         *   <div id="ct-app-content"></div>
         * ed eliminare il blocco routing in main.php.
         */
        gdrcd_load_modules('pages/' . $strInnerPage);
        ?>
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
