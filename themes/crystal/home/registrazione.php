<?php
// Includi il file di configurazione
require_once 'config.inc.php';

// Includi la protezione se attivata
if ($PARAMETERS['settings']['protection'] == 'ON'){
    require 'protezione.php';
}

// Inizializza la variabile della fase
$fase = isset($_POST['fase']) ? $_POST['fase'] : 1;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Crystal Tokyo GDR</title>
    <!-- Collegamento ai fogli di stile CSS -->
    <link rel="stylesheet" href="../themes/crystal/home/login_provvisorio.css">
    <!-- Collegamento al file JavaScript -->
    <script src="../themes/crystal/home/script_login.js" defer></script>
</head>
<body>

        <div class="main-content">
        <!-- Contenuto principale della pagina -->
        <div id="registrationModal" class="custom-content registration-modal">
        
            <div class="registration-custom-box">
                <span class="close">&times;</span>
                <h2 style="font-size: 24px;">Registrazione</h2>
                <div id="registrationContent">
                
                    
                <!-- Fase 1: Inserimento Dati -->
                <?php if ($fase == 1) { ?>
                <div id="fase1">

                    <!-- Campi per la registrazione -->
                    <center><h4>Fase 1: Inserisci il nome e il cognome del tuo personaggio, scegli lo spirito e il mestiere</h4></center>
                        <form id="registrazioneForm" action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                          method="post">
                        <input type="hidden" name="fase" value="2">
                        
                        <div class="input-group registration-input-group">
                            <label for="nomePersonaggio">Nome Personaggio</label>
                            <input type="text" id="nomePersonaggio" name="nome" value="<?php echo gdrcd_filter('out', $_POST['nome']); ?>" placeholder="Inserisci il nome del personaggio" required>
                        </div>
                        <div class="input-group registration-input-group">
                            <label for="cognomePersonaggio">Cognome Personaggio</label>
                            <input type="text" id="cognomePersonaggio" name="cognome" value="<?php echo gdrcd_filter('out', $_POST['cognome']); ?>" placeholder="Inserisci il cognome del personaggio" required>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="emailPersonaggio">E-Mail</label>
                            <input type="text" id="emailPersonaggio" name="email" value="<?php echo gdrcd_filter('email', $_POST['email']); ?>" placeholder="Inserisci il tuo indirizzo mail" required>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="spiritoPersonaggio">Spirito del Personaggio</label>
                        <?php $result = gdrcd_query("SELECT id_razza, nome_razza FROM razza WHERE iscrizione=1 ORDER BY nome_razza",
                            'result'); ?>                            
                            <select id="generePersonaggio" name="razza" required>
                        <?php while ($row = gdrcd_query($result, 'fetch'))
                                     { ?>
                                <option value="<?php echo $row['id_razza']; ?>" <?php if (gdrcd_filter('get',
                                            $_POST['razza']) == $row['id_razza']
                                    )
                                    {
                                        echo 'SELECTED';
                                    } ?>>
                                        <?php echo gdrcd_filter('out', $row['nome_razza']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="input-group registration-input-group">
                            <label for="generePersonaggio">Mestiere del Personaggio</label>
                        <?php $result = gdrcd_query("SELECT id_ruolo, nome_ruolo FROM ruolo_mestiere WHERE livello_mestiere = 3 ORDER BY nome_ruolo",
                            'result'); ?>
                            <select id="mestierePersonaggio" name="mestiere" required>
                                 <?php while ($row = gdrcd_query($result, 'fetch'))
                                { ?>
                                    <option value="<?php echo $row['id_ruolo']; ?>" <?php if (gdrcd_filter('get',
                                            $_POST['nome_ruolo']) == $row['id_ruolo']
                                    )
                                    {
                                        echo 'SELECTED';
                                    } ?>>
                                        <?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <!-- Genere -->
                        <div class="form_field" style="display: none">
                            <select name="genere">
                                <option value="m" <?php if (gdrcd_filter('get', $_POST['genere']) == 'm')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_m']); ?>
                                </option>
                                <option value="f" <?php if (gdrcd_filter('get', $_POST['genere']) == 'f')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_f']); ?>
                                </option>
                                <option value="b" <?php if (gdrcd_filter('get', $_POST['genere']) == 'b')
                                {
                                    echo 'SELECTED';
                                } ?> >
                                    <?php echo gdrcd_filter('out', $MESSAGE['register']['fields']['gender_b']); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="input-group registration-input-group">
    <input type="checkbox" id="accettoTermini" name="accettoTermini" required>
    <label for="accettoTermini">ACCETTO i Termini e Condizioni</label>
</div>

<div class="input-group registration-input-group">
    <input type="checkbox" id="consensoTrattamentoDati" name="consensoTrattamentoDati" required>
    <label for="consensoTrattamentoDati">ESPRIMO IL CONSENSO al trattamento dei miei dati personali secondo quanto espresso dalla <a href="https://www.iubenda.com/privacy-policy/18155810" class="iubenda-black iubenda-noiframe iubenda-embed" title="Privacy Policy">Privacy Policy</a> e <a href="https://www.iubenda.com/privacy-policy/18155810/cookie-policy" class="iubenda-black iubenda-noiframe iubenda-embed" title="Cookie Policy">Cookie Policy</a></label>
    <script type="text/javascript">(function (w,d) {var loader = function () {var s = d.createElement("script"), tag = d.getElementsByTagName("script")[0]; s.src="https://cdn.iubenda.com/iubenda.js"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener("load", loader, false);}else if(w.attachEvent){w.attachEvent("onload", loader);}else{w.onload = loader;}})(window, document);</script>
</div>


<div class="input-group registration-input-group">
            <button type="submit" class="submit-button">Prosegui</button>
                    </div>
                    </form>
                </div>
                 <?php } ?>
                
                
                
                
                
                
                

                <!-- Fase 2: Riepilogo Dati -->
                <?php if ($fase == 2) { ?>
                <div id="fase2" style="display: none;">                
                                
                    <h4>Riepilogo dati</h4>
                    
                    <?php
                    // Determinazione del genere
if ($_POST['genere'] == 'm')
                    {
                        $r_gen = 'm';
                    } else
                    {
                        $r_gen = 'f';
                    }
                    
                    //sigla
                    
                    if ($_POST['genere'] == 'm')
                    {
                        $sesso = 'Uomo';
                    } else if ($_POST['genere'] == 'f')
                    {
                        $sesso = 'Donna';
                    } else
                    {
                        $sesso = 'Non Binario';
                    }                    // Associazione mestiere a ruolo
                    $mestiereMapping = [
                        '1' => 'Disoccupato',
                        '38' => 'Operatore',
                        '76' => 'Stagista della TAE',
                        '64' => 'Apprendista del Magic Shop',
                        '83' => 'Dipendente del Pandora',
                        '90' => 'Parlamentare',
                        '12' => 'Nobile di Corte'
                    ];
                    $nome_lavoro = isset($mestiereMapping[$_POST['mestiere']]) ? $mestiereMapping[$_POST['mestiere']] : 'N/A';
                    $razza = gdrcd_query("SELECT sing_" . gdrcd_filter('in',
                            $r_gen) . " AS nome_razza FROM razza WHERE id_razza = " . (0 + gdrcd_filter('num',
                                $_POST['razza'])) . " LIMIT 1");    
                                ?>
                    
                    <div class="input-group registration-input-group">
                            <label>Nome Personaggio:</label>
                            <span><?php echo gdrcd_filter('out', $_POST['nome']) ?></span>
                        </div>
                        <div class="input-group registration-input-group">
                            <label>Cognome Personaggio:</label>
                            <span><?php echo gdrcd_filter('out', $_POST['cognome']) ?></span>
                        </div>
                        <div class="input-group registration-input-group">
                            <label>E-Mail:</label>
                            <span><?php echo gdrcd_filter('out', $_POST['email']) ?></span>
                        </div>
                        <div class="input-group registration-input-group">
                            <label>Spirito del Personaggio:</label>
                            <span><?php echo gdrcd_filter('out', $razza['nome_razza']) . '&nbsp;' ?></span>
                        </div>
                        <div class="input-group registration-input-group">
                            <label>Mestiere del Personaggio:</label>
                            <span><?php echo gdrcd_filter('out', $nome_lavoro) . '&nbsp;' ?></span>
                        </div>
                        <div class="input-group registration-input-group">
                            <form method="post" action="registrazione.php">
                            <input type="hidden" name="fase" value="1">
                            <button type="submit" class="submit-button">Indietro</button>
                        </form>
                        <form method="post" action="registrazione.php">
                            <input type="hidden" name="fase" value="3">
                            <button type="submit" class="submit-button">Conferma</button>
                        </form>
                        </div>
                </div>
                <?php } ?>
                
        </div>
        </div>
    </div>
</div>
    <script>
        function vaiAllaFase2() {
            document.getElementById('fase1').style.display = 'none';
            document.getElementById('fase2').style.display = 'block';
        }

        function vaiAllaFase1() {
            document.getElementById('fase2').style.display = 'none';
            document.getElementById('fase1').style.display = 'block';
        }
    </script>
</body>
</html>