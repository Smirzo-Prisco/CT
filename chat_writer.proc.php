<?php

require'header.inc.php'; /*Header comune*/

?>
<!DOCTYPE html>
<html>
<head>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<meta charset="utf-8">
<title>Writer</title>
 
</head>
<body>
<center>
<textarea id="textarea" maxlength="10000" style="width:700px; height:400px;" onKeyup="conta(this);"></textarea>
Hai usato: <b><span id="rimanenti">0 caratteri</span></b>
</form>
</body>
<script type="text/javascript"> 
    function conta(el) {
        // imposto il limite massimo di caratteri consentiti
        var max_char = 0;
        // conto il numero di caratteri nell\'input
        var conta_caratteri = el.value.length;
        // verifico se i caratteri hanno superato il limite
        if(conta_caratteri <= max_char) {
            // riporto i caratteri al limite massimo
            conta_caratteri = 0;
            // cancello dall\'input i caratteri in eccesso
            el.value = el.value.substring(0, max_char);
        }
        // aggiorno il contatore testuale
        document.getElementById("rimanenti").innerHTML = conta_caratteri - max_char;
        // calcolo la lungezza per il contatore grafico
        var width =  (conta_caratteri - max_char)*5;
        // aggiorno il contatore grafico
        document.getElementById("countdown").style.width = width + "px";
    }
</script>
</html>