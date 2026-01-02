<!-- Container principale responsive -->
<div class="table-container">

    <!-- Prima Sezione - Regolamento -->
    <div class="table-section">
        <div class="table-title">
            <img src="../themes/crystal/imgs/pre_doc/regolamento_manuali.png" alt="Regolamento" class="title-image">
        </div>
        <div class="content-box regolamento-box">
            <div class="content-wrapper">
                <a href="../documentazione_main.php" target="_blank">AMBIENTAZIONE & REGOLAMENTO</a>
                <a href="../statuti/spiriti/spiriti.html" target="_blank">SPIRITI</a>
            </div>
        </div>
    </div>

    <div class="spacing"></div>

    <!-- Seconda Sezione - Famiglie -->
    <div class="table-section">
        <div class="table-title">
            <img src="../themes/crystal/imgs/pre_doc/famiglie_manuali.png" alt="Famiglie" class="title-image">
        </div>
        <div class="content-box famiglie-box">
            <div class="content-wrapper">
                <a href="../statuto_main.php?id=9" target="_blank">CITTADINI POTENZIATI</a>
                <?php
                $query = "SELECT nome, id_gilda, url_sito FROM gilda WHERE visibile = 1";
                $result = gdrcd_query($query, 'result');    
                while($record = gdrcd_query($result, 'fetch')) {
                ?>
                <a href="../statuto_main.php?id=<?= $record['id_gilda'] ?>" target="_blank"><?= $record['nome'] ?></a>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="spacing"></div>

    <!-- Terza Sezione - Mestieri -->
    <div class="table-section">
        <div class="table-title">
            <img src="../themes/crystal/imgs/pre_doc/mestieri_manuali.png" alt="Mestieri" class="title-image">
        </div>
        <div class="content-box mestieri-box">
            <div class="content-wrapper">
                <?php
                $query = "SELECT * FROM mestiere WHERE visibile = 1";
                $result = gdrcd_query($query, 'result');    
                while($record = gdrcd_query($result, 'fetch')) {
                ?>
                <a href="<?= $record['url_sito'] ?>?id2=<?= $record['id_mestiere'] ?>" target="_blank"><?= $record['nome'] ?></a>
                <?php } ?>
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
.table-container {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

/* Sezione tabella con titolo */
.table-section {
    text-align: center;
    margin-bottom: 60px;
    position: relative;
}

/* Titoli delle tabelle */
.table-title {
    margin-bottom: 20px;
    position: relative;
    z-index: 10;
}

.title-image {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
}

/* Spaziatura */
.spacing {
    height: 40px;
}

/* Box del contenuto con sfondo */
.content-box {
    width: 400px;
    height: 160px;
    background-repeat: no-repeat;
    background-position: center;
    background-size: 100% 100%; /* Copre tutto il box */
    position: relative;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

/* Background images specifici */
.regolamento-box {
    background-image: url('../themes/crystal/imgs/pre_doc/box_regolamento_spiriti.png');
}

.famiglie-box,
.mestieri-box {
    background-image: url('../themes/crystal/imgs/pre_doc/box_famiglie_e_mestieri.png');
    height: 300px; /* Più alto per contenere più voci */
    width: 280px;
}

.content-wrapper {
    width: 90%;
    text-align: center;
    position: relative;
    z-index: 2;
}

/* Link */
.content-box a {
    display: block;
    margin: 6px 0;
    text-decoration: none;
    color: inherit;
    font-weight: bold;
    line-height: 1.2;
    font-size: 14px;
    padding: 2px 0;
}

/* RESPONSIVE - TABLET */
@media (max-width: 768px) {
    .table-container {
        padding: 15px;
    }
    
    .content-box {
        width: 350px;
        height: 140px;
    }
    
    .famiglie-box,
    .mestieri-box {
        width: 250px;
        height: 260px;
    }
    
    .content-box a {
        font-size: 13px;
        margin: 5px 0;
    }
}

/* RESPONSIVE - MOBILE LARGE */
@media (max-width: 650px) {
    .content-box {
        width: 300px;
        height: 120px;
    }
    
    .famiglie-box,
    .mestieri-box {
        width: 220px;
        height: 240px;
    }
    
    .content-box a {
        font-size: 12px;
        margin: 4px 0;
    }
    
    .table-section {
        margin-bottom: 50px;
    }
}

/* RESPONSIVE - MOBILE SMALL */
@media (max-width: 480px) {
    .table-container {
        padding: 10px;
    }
    
    .spacing {
        height: 30px;
    }
    
    .table-section {
        margin-bottom: 40px;
    }
    
    .content-box {
        width: 280px;
        height: 110px;
    }
    
    .famiglie-box,
    .mestieri-box {
        width: 200px;
        height: 220px;
    }
    
    .content-box a {
        font-size: 11px;
        margin: 3px 0;
    }
}

/* RESPONSIVE - MOBILE EXTRA SMALL */
@media (max-width: 380px) {
    .content-box {
        width: 260px;
        height: 100px;
    }
    
    .famiglie-box,
    .mestieri-box {
        width: 180px;
        height: 230px;
    }
    
    .content-box a {
        font-size: 10px;
        margin: 2px 0;
    }
}

/* Garantisce che le immagini non superino mai la larghezza del container */
.title-image {
    max-width: 100%;
    height: auto;
}

/* Previene lo scroll orizzontale e sovrapposizioni */
body {
    overflow-x: hidden;
    position: relative;
}

/* Migliora la visualizzazione su tutti i dispositivi */
.content-wrapper {
    position: relative;
    z-index: 2;
}

/* Assicura che i titoli siano sopra tutto */
.table-title {
    position: relative;
    z-index: 10;
}

/* Assicura che i content box non si sovrappongano ai titoli */
.table-section {
    position: relative;
    z-index: 1;
}
</style>