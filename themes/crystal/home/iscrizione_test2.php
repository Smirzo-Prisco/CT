<link rel="stylesheet" href="themes/crystal/iscrizione_new.css">
<script>
function centreAlign() {
  let a = document.getElementById("fase_0").getBoundingClientRect().width / 100;
  document.documentElement.style.setProperty("--width", a + "px");
}
centreAlign()
</script>

<body translate="no">
<div class="pagina_iscrizione">
    <div class="page_title"><h2>
            <?php echo gdrcd_filter('out', $MESSAGE['register']['page_name']); ?>
        </h2></div>
    <div class="page_body">

        <?php /**** Fase 0 ****/
        if (isset($_POST['fase']) === false)
        { ?>
       
        <div id="fase_0">
        
        <div class="disclaimer">
        <?php echo gdrcd_filter('out', $MESSAGE['register']['disclaimer']); ?>
        </div> 
        <br>
        <div class="info">
        <?php echo gdrcd_filter('out', $MESSAGE['register']['rules_read']); ?>
        </div> 
        
        <div class="accetto">
        <form action="<?php echo $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING']; ?>"
                          method="post">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="hidden" name="fase" value="1"/>
                            <input type="submit" style="width: 250px; margin: 0px auto;"
                                   value="<?php echo gdrcd_filter('out', $MESSAGE['register']['forms']['accept']); ?>"/>
                    </form>
        </div> 
        
        </div>

        <?php } ?>



        <?php /**** Fase 1 ****/
        if (gdrcd_filter('get', $_POST['fase']) == 1)
        {
        
        

			/***********PHP MAILER *****************/
			
//includiamo la classe PHPMailer
require "phpmailer/src/class.phpmailer.php";
require 'phpmailer/PHPMailerAutoload.php';

//istanziamo la classe
$messaggio = new PHPmailer();

//settiamo su true il metodo che indica alla classe il formato HTML
$messaggio->IsHTML(true);

//intestazioni e corpo dell'email
$messaggio->From='info@mittente.it';
$messaggio->AddAddress('info@destinatario.it');
$messaggio->AddReplyTo('espositoclemente84@gmail.com'); 
$messaggio->Subject='Prova formato HTML';

//inseriamo i tag HTML e i CSS per formattare il messaggio
$messaggio->Body = '<html><head><style>';
$messaggio->Body .= 'p {color:#444444;text-align:left;font-size:15px}';
$messaggio->Body .= 'span.evidenziatore {background-color:#FF0000;color:#000000;}';
$messaggio->Body .= '</style></head><body>';
$messaggio->Body .= '<center><p><span class="evidenziatore">Lorem ipsum dolor sit amet</span>, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p></center>';
$messaggio->Body .= '</body></html>';

//parte relativa all'invio
if(!$messaggio->Send()){ 
  echo $messaggio->ErrorInfo; 
}else{ 
  echo 'Email inviata correttamente!';
}
unset($messaggio);
					
					/***********fine PHP MAILER *****************/


         } ?>



        

    <!-- Chiudura finestra iscizione -->
</div>
</div>
</body>