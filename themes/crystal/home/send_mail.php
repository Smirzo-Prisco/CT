<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

/*
$adminMail = "test@prova.it";
$message ="Ciao!!!!";

$header = "From: $adminMail\n";
			$header .= "MIME-Version: 1.0\n";
			$header .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
			$header .= "Content-Transfer-Encoding: 7bit\n\n";
			
			$bodyMessage = "<html>
								<body>
									$message
								</body>
							</html>";
			
$send = mail("redito@hotmail.it", "Richiesta registrazione su", $bodyMessage, $header);
*/


$to      = 'redito@hotmail.it';
$subject = 'the subject';
$message = 'hello';
$headers = 'From: crystaltokyogdr@altervista.org' . "\r\n" .
    'Reply-To: crystaltokyogdr@altervista.org' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

$send = mail($to, $subject, $message, $headers);

if($send) echo "DAJE";
else echo "FUCK!";