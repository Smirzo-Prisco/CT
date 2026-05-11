<style>
	/* Stili per il menu mobile unificato */
	.mobile-container {
		max-width: 1200px;
		margin: 0 auto;
	}

	.mobile-container .mobile-menu {
		/* RIMUOVI position: relative - causa conflitti */
		height: auto; /* Cambia da 100vh ad auto */
		overflow: visible; /* Cambia da hidden a visible */
	}

	/* Barra inferiore FISSA */
	.mobile-container .mobile-bar {
		position: fixed; /* Cambia da absolute a fixed */
		bottom: 0;
		left: 0;
		width: 100%;
		height: 80px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 0 20px;
		z-index: 10000; /* Alto z-index per stare sopra tutto */
		background: transparent; /* Sfondo trasparente */
	}

	/* Cerchio utente in basso a sinistra */
	.mobile-container .user-circle {
		width: 60px;
		height: 60px;
		border-radius: 50%;
		background: linear-gradient(45deg, #ff7e5f, #feb47b);
		display: flex;
		justify-content: center;
		align-items: center;
		cursor: pointer;
		z-index: 10001; /* Ancora più alto */
		box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
		transition: transform 0.3s ease;
	}

	.mobile-container .user-circle:active {
		transform: scale(0.95);
	}

	.mobile-container .user-image {
		width: 50px;
		height: 50px;
		border-radius: 50%;
		background-color: #fff;
		display: flex;
		justify-content: center;
		align-items: center;
		overflow: hidden;
	}

	.mobile-container .user-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	/* Menu a discesa - FISSO e che si espande verso destra */
	.mobile-container .mobile-dropdown-menu {
		position: fixed; /* Cambia da absolute a fixed */
		bottom: 90px; /* Posizionato sopra la barra con margine */
		left: 20px;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		z-index: 10000; /* Alto z-index */
		opacity: 0;
		visibility: hidden;
		transform: translateX(-20px); /* Animazione verso destra */
		transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
	}

	.mobile-container .mobile-dropdown-menu.active {
		opacity: 1;
		visibility: visible;
		transform: translateX(0);
	}

	.mobile-container .mobile-menu-item {
		display: flex;
		align-items: center;
		margin: 4px 0;
		cursor: pointer;
		transition: all 0.3s ease;
	}

	.mobile-container .mobile-menu-item a {
		margin: 0px;
	}

	.mobile-container .mobile-menu-item:hover {
		background: rgba(255, 255, 255, 0.25);
		transform: translateX(10px);
	}

	.mobile-container .mobile-menu-item a {
		display: flex;
		align-items: center;
		text-decoration: none;
		color: inherit;
		width: 100%;
	}

	.mobile-container .mobile-menu-item i {
		width: 30px;
		text-align: center;
	}

	/* Media query per desktop */
	@media (min-width: 768px) {
		.mobile-container .mobile-menu { 
			display: none; 
		}
	}



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
		top: 10px;
		z-index: 1001;
		background: rgba(0,0,0,0.7);
		color: white;
		border: none;
		padding: 10px 15px;
		cursor: pointer;
		border-radius: 5px;
		font-size: 14px;
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
			right: 0;
		}
		
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
		
		table.customTable {
			width: 100%;
		}
	}
	
	/* Stili aggiuntivi per prevenire overflow nel maincontent */
	#maincontent .output {
		width: 100%;
		max-width: 100%;
		box-sizing: border-box;
		overflow-x: hidden;
	}
	
	#maincontent img {
		max-width: 100%;
		height: auto;
	}
	
	#maincontent table {
		max-width: 100%;
		overflow-x: auto;
		/* display: block; */
	}
	
	#maincontent iframe {
		max-width: 100%;
	}
	
	* html body {
		padding: 10px 215px 10px 210px;
	}
	
	* html #maincontent {
		height: 100%;
		width: 100%;
	}
</style>

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







	

    <!-- Versione mobile unificata delle due colonne. Le nascondo. Lascio intatto il maincontent -->
    <?php
    $record = gdrcd_query(gdrcd_query("SELECT nome, url_img_chat FROM personaggio WHERE nome = '".gdrcd_filter('in', $_SESSION['login'])."'", 'result'), 'fetch');
    ?>
	
	<div class="mobile-container">
        <!-- Versione mobile -->
        <div class="mobile-menu">
            <div class="mobile-bar">
                <div class="user-circle" id="userCircle">
                    <div class="user-image"><img src="<?=gdrcd_filter('out', $record['url_img_chat'])?>" alt="<?=gdrcd_filter('out', $record['nome'])?>"></div>
                </div>
            </div>
            
            <div class="mobile-dropdown-menu" id="dropdownMenu">
				<div class="mobile-menu-item"> <!-- Logout -->
					<a href="../logout.php" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_exit.png" alt="Esci">
					</a>
				</div>
				<div class="mobile-menu-item"> <!-- Gestione -->
					<a href="../main.php?page=gestione" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_strumenti.png" alt="Gestione">
					</a>
				</div>
                <div class="mobile-menu-item"> <!-- Forum -->
					<a href="../main.php?page=forum" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_forum.png" alt="Forum">
					</a>
				</div>
                <div class="mobile-menu-item"> <!-- Uffici -->
					<a href="../main.php?page=uffici" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_uff.png" alt="Uffici">
					</a>
				</div>
                <div class="mobile-menu-item"> <!-- Manuali -->
					<a href="../main.php?page=pre_doc" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_doc.png" alt="Manuali">
					</a>
				</div>
				<div class="mobile-menu-item"> <!-- Chat off -->
					<a id="chatoff-link" href="javascript:;" onclick="window.open('../chattina_off.php','chat','width=650,height=600,resizable,status,scrollbars=1');" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_chat_off.png" alt="Chat">
					</a>
				</div>
                <div class="mobile-menu-item"> <!-- Messaggi Privati -->
					<a href="../main.php?page=messages_center&offset=0" target="_top" id="message-link">
						<img src="../themes/crystal/imgs/icone/icona_base_mex.png" 
							alt="<?=gdrcd_filter('out',$PARAMETERS['names']['private_message']['plur'])?>" 
							title="<?=gdrcd_filter('out',$PARAMETERS['names']['private_message']['plur'])?>">
					</a>
				</div>
				<div class="mobile-menu-item"> <!-- Mappa -->
					<a href="../main.php?page=mappaclick&map_id=<?=$_SESSION['mappa']?>" target="_top">
						<img src="../themes/crystal/imgs/icone/icon_mappa.png" alt="Mappa">
					</a>
				</div>
				<div class="mobile-menu-item"> <!-- Presenti -->
					<a href="main.php?page=presenti_estesi" alt="_top"><img src="../themes/crystal/imgs/menu/presenti.png"></a>
				</div>
            </div>
        </div>
    </div>
	











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

	// MOBILE
	document.addEventListener('DOMContentLoaded', function() {
		// MAPPA MOBILE
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
		}
		
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
		// FINE	MAPPA MOBILE

		// MENU MOBILE
		const userCircle = document.getElementById('userCircle');
		const dropdownMenu = document.getElementById('dropdownMenu');
		
		userCircle.addEventListener('click', function() {
			dropdownMenu.classList.toggle('active');
		});
		
		// Chiudi il menu se si clicca fuori
		document.addEventListener('click', function(event) {
			if (!userCircle.contains(event.target) && !dropdownMenu.contains(event.target)) {
				dropdownMenu.classList.remove('active');
			}
		});
		// FINE	MENU MOBILE
	});
</script>