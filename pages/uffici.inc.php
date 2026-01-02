<!-- Container principale responsive -->
<div class="uffici-container">

    <!-- Prima Colonna - Utilità -->
    <div class="ufficio-colonna">
        <div class="ufficio-header">
            <img src="../themes/crystal/imgs/uffici/uffici_utilita.png" alt="Utilità" class="header-image">
        </div>
        <div class="ufficio-content utilita-box">
            <div class="content-links">
                <a href="main.php?page=servizi_prenotazioni_prova" alt="_top">ALBERGO</a>
                <a href="main.php?page=anagrafe" alt="_top">ANAGRAFE</a>
                <a href="main.php?page=servizi_banca" alt="_top">BANCA</a>
                <a href="main.php?page=servizi_mercato" alt="_top">CENTRO COMMERCIALE</a>
                <a href="main.php?page=user_cambio_pass" alt="_top">CAMBIO PASSWORD</a>
                <a href="main.php?page=user_razze" alt="_top">LISTA SPIRITI</a>
                <a href="main.php?page=elenco_volti" alt="_top">PATROCINIO VOLTI</a>
                <a href="main.php?page=elenco_staff" alt="_top">ELENCO STAFF</a>
            </div>
        </div>
    </div>

    <!-- Seconda Colonna - Strumenti -->
    <div class="ufficio-colonna">
        <div class="ufficio-header">
            <img src="../themes/crystal/imgs/uffici/uffici_strumenti.png" alt="Strumenti" class="header-image">
        </div>
        <div class="ufficio-content strumenti-box">
            <div class="content-links">
                <a href="main.php?page=scegli_lavoro" alt="_top">SCEGLI LAVORO</a>
                <a href="main.php?page=oggetto_aggiungi_richiesta" alt="_top">OGGETTO PERSONALIZZATO</a>
                <a href="main.php?page=scegli_umano" alt="_top">POTENZIA CITTADINO</a>
                <a href="main.php?page=scegli_inclinazione" alt="_top">SCEGLI CORRENTE E VIA</a>
                <a href="main.php?page=scegli_mestiere" alt="_top">SCEGLI, CONFERMA, AVANZA MESTIERE</a>
            </div>
        </div>
    </div>

    <!-- Terza Colonna - Potenziamento -->
    <div class="ufficio-colonna">
        <div class="ufficio-header">
            <img src="../themes/crystal/imgs/uffici/uffici_potenziamento.png" alt="Potenziamento" class="header-image">
        </div>
        <div class="ufficio-content potenziamento-box">
            <div class="content-links">
                <a href="main.php?page=mercato_abilita_atarashi" alt="_top">ABILITÀ</a>
                <a href="main.php?page=mercato_talento" alt="_top">TALENTI</a>
                <a href="main.php?page=incremento_parametri">INCREMENTO PARAMETRI</a>
                <a href="main.php?page=level_up" alt="_top">AUMENTO SPIRITO</a>
            </div>
        </div>
    </div>

</div>

<style>
/* Reset e base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Container principale */
.uffici-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

/* Colonna singola ufficio */
.ufficio-colonna {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    flex: 0 0 auto;
}

/* Intestazione */
.ufficio-header {
    margin-bottom: 15px;
}

.header-image {
    max-width: 100%;
    height: auto;
    display: block;
}

/* Contenuto */
.ufficio-content {
    width: 231px;
    height: 286px;
    background-image: url('../themes/crystal/imgs/uffici/box_uffici.png');
    background-repeat: no-repeat;
    background-position: center;
    background-size: 100% 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
}

.content-links {
    width: 90%;
    text-align: center;
}

/* Link */
.ufficio-content a {
    display: block;
    margin: 8px 0;
    text-decoration: none;
    color: inherit;
    font-weight: bold;
    line-height: 1.3;
    font-size: 13px;
    padding: 2px 0;
    transition: color 0.3s ease;
}

.ufficio-content a:hover {
    color: #666;
}

/* RESPONSIVE - TABLET */
@media (max-width: 900px) {
    .uffici-container {
        gap: 30px;
    }
    
    .ufficio-content {
        width: 210px;
        height: 260px;
    }
    
    .ufficio-content a {
        font-size: 12px;
        margin: 7px 0;
    }
}

/* RESPONSIVE - MOBILE LARGE */
@media (max-width: 768px) {
    .uffici-container {
        padding: 15px;
        gap: 25px;
    }
    
    .ufficio-content {
        width: 190px;
        height: 240px;
        padding: 15px;
    }
    
    .ufficio-content a {
        font-size: 11px;
        margin: 6px 0;
    }
}

/* RESPONSIVE - MOBILE MEDIUM */
@media (max-width: 650px) {
    .uffici-container {
        flex-direction: column;
        align-items: center;
        gap: 40px;
    }
    
    .ufficio-colonna {
        width: 100%;
        max-width: 300px;
    }
    
    .ufficio-content {
        width: 250px;
        height: 220px;
        padding: 20px;
    }
    
    .ufficio-content a {
        font-size: 12px;
        margin: 5px 0;
    }
}

/* RESPONSIVE - MOBILE SMALL */
@media (max-width: 480px) {
    .uffici-container {
        padding: 10px;
        gap: 30px;
    }
    
    .ufficio-content {
        width: 220px;
        height: 200px;
        padding: 15px;
    }
    
    .ufficio-content a {
        font-size: 11px;
        margin: 4px 0;
    }
}

/* RESPONSIVE - MOBILE EXTRA SMALL */
@media (max-width: 380px) {
    .ufficio-content {
        width: 200px;
        height: 180px;
        padding: 12px;
    }
    
    .ufficio-content a {
        font-size: 10px;
        margin: 3px 0;
    }
}

/* Garantisce che le immagini non superino mai la larghezza del container */
.header-image {
    max-width: 100%;
    height: auto;
}

/* Previene lo scroll orizzontale */
body {
    overflow-x: hidden;
}

/* Migliora l'accessibilità */
.ufficio-content a:focus {
    outline: 2px solid #333;
    outline-offset: 2px;
}

/* Assicura che ogni colonna sia indipendente */
.ufficio-colonna {
    position: relative;
}
</style>