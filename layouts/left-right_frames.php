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
	
	/* Pulsanti per mobile (nascosti di default) */
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
	
	#toggleLeft {
		left: 10px;
	}
	
	#toggleRight {
		right: 10px;
	}
	
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
			transform: translateX(0) !important;
			/* Importante: previeni overflow orizzontale */
			overflow-x: hidden !important;
		}
		
		.mobile-toggle {
			display: block;
		}
		
		/* Overlay per quando una colonna è espansa */
		.sidebar-overlay {
			display: none;
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0,0,0,0.5);
			z-index: 999;
		}
		
		.sidebar-overlay.active {
			display: block;
		}
		
		/* Forza il reset completo quando le colonne sono chiuse */
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
	
	/* Stili aggiuntivi per prevenire overflow nel maincontent */
	#maincontent .output {
		width: 100%;
		max-width: 100%;
		box-sizing: border-box;
		overflow-x: hidden;
	}
</style>

<?php
if (isset($_GET['css'])) header('Content-Type:text/css; charset=utf-8');
else {
    if ($PARAMETERS['left_column']['activate'] == 'ON') {
    ?>
        <!-- Colonna sinistra -->
        <div id="framecontentLeft" style="background-image:url('../themes/crystal/imgs/colonna_sx/colonna_sinistra.png'); background-repeat: no-repeat;">
            <div class="innertube">
                <div class="colonne_sx">
                    <?php
                    foreach ($PARAMETERS['left_column']['box'] as $box) {
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

    if ($PARAMETERS['right_column']['activate'] == 'ON'): ?>
        <!-- Colonna destra -->
        <div id="framecontentRight" style="background-image:url('../themes/crystal/imgs/colonna_dx/colonna_destra.png'); background-repeat: no-repeat;">
            <div class="innertube">
                <div class="colonne_dx">
					<?php
						foreach ($PARAMETERS['right_column']['box'] as $box) {
							echo '<div class="' . $box['class'] . '">';
							gdrcd_load_modules('pages/' . $box['page'] . '.inc.php', $box);
							echo '</div>';
						}
					?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div id="maincontent">
        <div class="output">
            <!-- Popup per mostrare cose -->
			<div id="id01" class="modal">
				<form class="modal-content animate" action="/action_page.php" method="post">
					<div class="imgcontainer">
						<span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">Chiudi</span>
					</div>
					<div class="container2"> <!-- Modale per aprire la descrizione delle skill lanciate in chat -->
						<iframe id="myframe" src="/default.php"></iframe>
					</div>
				</form>
            </div>
            <?php gdrcd_load_modules('pages/' . $strInnerPage); ?>
        </div>
    </div>

	<button class="mobile-toggle" id="toggleLeft">☰</button>
	<button class="mobile-toggle" id="toggleRight">☰</button>
















<?php
}

// $news = gdrcd_query("SELECT * FROM ctnews ORDER BY data DESC LIMIT 1");
?>
<!--
<div id="ctnews" style="height: 0px; opacity: 0;">
    <div class="left"><img src="themes/crystal/imgs/icone/CTNEWS.png"></div>
    <div class="right"><marquee align="middle" behavior="scroll" direction="left"scrolldelay="10"><?php echo $news['titolo'];?>: <?php echo $news['contenuto'];?></marquee></div>
</div>
-->

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






	
	
	document.addEventListener('DOMContentLoaded', function() {
		const toggleLeft = document.getElementById('toggleLeft');
		const toggleRight = document.getElementById('toggleRight');
		const sidebarLeft = document.getElementById('framecontentLeft');
		const sidebarRight = document.getElementById('framecontentRight');
		const mainContent = document.getElementById('maincontent');
		const overlay = document.createElement('div');
		
		overlay.className = 'sidebar-overlay';
		document.body.appendChild(overlay);
		
		let currentOpenSidebar = null;
		let isMobile = window.innerWidth <= 768;
		
		// Funzione per forzare il reset completo del layout
		function forceLayoutReset() {
			console.log('Forzando reset del layout');
			
			// Reset forzato del body
			document.body.style.overflowX = 'hidden';
			document.body.style.width = '100%';
			document.body.classList.add('sidebars-closed');
			
			// Reset forzato del main content
			mainContent.style.transform = 'translateX(0)';
			mainContent.style.width = '100%';
			mainContent.style.left = '0';
			mainContent.style.right = '0';
			mainContent.style.overflowX = 'hidden';
			
			// Forza un reflow
			void document.body.offsetHeight;
			
			// Nascondi le scrollbar orizzontali
			document.documentElement.style.overflowX = 'hidden';
			
			console.log('Layout reset completato');
		}
		
		// Funzione per chiudere tutte le colonne con reset completo
		function closeAllSidebars() {
			console.log('Chiudendo tutte le colonne');
			
			sidebarLeft.classList.remove('expanded');
			sidebarRight.classList.remove('expanded');
			overlay.classList.remove('active');
			currentOpenSidebar = null;
			
			// Riabilita scroll verticale
			mainContent.style.overflow = 'auto';
			document.body.style.overflowY = 'auto';
			
			// Reset completo del layout
			setTimeout(forceLayoutReset, 50);
			setTimeout(forceLayoutReset, 200); // Doppio reset per sicurezza
			
			console.log('Colonne chiuse e reset applicato');
		}
		
		// Funzione per espandere una colonna
		function toggleSidebar(sidebar, toggleButton) {
			const isExpanded = sidebar.classList.contains('expanded');
			
			// Chiudi tutto prima di aprire
			closeAllSidebars();
			
			if (!isExpanded) {
				// Piccolo delay per assicurarsi che il reset sia completato
				setTimeout(() => {
					sidebar.classList.add('expanded');
					overlay.classList.add('active');
					currentOpenSidebar = sidebar;
					
					// Disabilita scroll
					mainContent.style.overflow = 'hidden';
					document.body.style.overflow = 'hidden';
					
					console.log('Colonna aperta:', sidebar.id);
				}, 100);
			}
		}
		
		// Event listeners
		toggleLeft.addEventListener('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			toggleSidebar(sidebarLeft, toggleLeft);
		});
		
		toggleRight.addEventListener('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			toggleSidebar(sidebarRight, toggleRight);
		});
		
		overlay.addEventListener('click', function(e) {
			e.stopPropagation();
			e.preventDefault();
			closeAllSidebars();
		});
		
		mainContent.addEventListener('click', function(e) {
			if (currentOpenSidebar) {
				e.stopPropagation();
				e.preventDefault();
				closeAllSidebars();
			}
		});
		
		// Previeni la chiusura quando si clicca all'interno di una colonna
		sidebarLeft.addEventListener('click', function(e) {
			e.stopPropagation();
		});
		
		sidebarRight.addEventListener('click', function(e) {
			e.stopPropagation();
		});
		
		// Gestione resize della finestra
		window.addEventListener('resize', function() {
			const newIsMobile = window.innerWidth <= 768;
			
			if (newIsMobile !== isMobile) {
				isMobile = newIsMobile;
				closeAllSidebars();
				
				if (!isMobile) {
					// Desktop: ripristina visibilità colonne
					sidebarLeft.style.visibility = 'visible';
					sidebarRight.style.visibility = 'visible';
					sidebarLeft.style.transform = 'translateX(0)';
					sidebarRight.style.transform = 'translateX(0)';
					document.body.classList.remove('sidebars-closed');
				} else {
					// Mobile: nascondi colonne
					sidebarLeft.style.visibility = 'hidden';
					sidebarRight.style.visibility = 'hidden';
					sidebarLeft.style.transform = 'translateX(-100%)';
					sidebarRight.style.transform = 'translateX(100%)';
					forceLayoutReset();
				}
			}
		});
		
		// Gestione tasto ESC
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && currentOpenSidebar) {
				closeAllSidebars();
			}
		});
		
		// Osserva cambiamenti nel DOM per prevenire overflow
		const observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				if (mutation.type === 'childList' && isMobile) {
					setTimeout(forceLayoutReset, 100);
				}
			});
		});
		
		observer.observe(mainContent, {
			childList: true,
			subtree: true
		});
		
		// Inizializzazione
		if (isMobile) forceLayoutReset();
	});
</script>