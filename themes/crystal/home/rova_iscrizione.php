<?php
if(mail('espositoclemente84@gmail.com','prova GMAIL','messaggio di prova per GMAIL','From: zona@altervista.org'))
echo 'email inviata correttamente a GMAIL'."<br>";
else echo 'Errore! a GMAIL'."<br>";

if(mail('espositoclemente84@gmail.com','prova GMAIL con link','messaggio di prova per GMAIL http://zona.altervista.org','From: zona@altervista.org'))
echo 'email inviata correttamente a GMAIL con link'."<br>";
else echo 'Errore! a GMAIL con link'."<br>";

if(mail('espositoclemente84@tin.it','prova TIN.IT','messaggio di prova per TIN.IT','From: zona@altervista.org'))
echo 'email inviata correttamente a TIN.IT'."<br>";
else echo 'Errore! a TIN.IT'."<br>";

if(mail('espositoclemente84@alice.it','prova ALICE','messaggio di prova per ALICE','From: zona@altervista.org'))
echo 'email inviata correttamente a ALICE'."<br>";
else echo 'Errore! a ALICE'."<br>";
?>