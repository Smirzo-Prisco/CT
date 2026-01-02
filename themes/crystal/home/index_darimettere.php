<?php
require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON'){
    require 'protezione.php';
}

?>
<link href="themes/crystal/login.css" rel="stylesheet" />


<div id="container">
</div>

<div class="navbar" style="text-align: left; padding-left: 10px;">
  <a onclick="changeFrame('themes/crystal/home/privacy.php');document.getElementById('id01').style.display='block'"><img src="../themes/crystal/imgs/login/informativa_e_privacy.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/affiliati.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/crediti_e_staff.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/facebook.png"></a>
</div>

<form method="post" action="login.php" id="do_login">
<div class="input_nome">
<input type="text" name="login1" id="username" style="font-size: 11px; outline: none !important; background-color: transparent !important; border: solid 0px;" />
</div>

<div class="input_pass">
<input id="password" type="password" name="pass1" style="font-size: 11px; outline: none !important; background-color: transparent !important; border: solid 0px;" />
</div>

<div class="tasto">
<input type="submit" value="<?php echo $MESSAGE['homepage']['forms']['login']; ?>" />
</div>
</form>

<div class="recuperopass">
<a href="#" onclick="changeFrame('themes/crystal/home/recupero_password.php');document.getElementById('id01').style.display='block'">
<img src="../themes/crystal/imgs/login/spacer.png">
</a>
</div>

<div class="iscrizione">
<a href="index.php?page=iscrizione">
<img src="../themes/crystal/imgs/login/spacer.png">
</a>
</div>

<div class="manuali">
<a href="../documentazione_main.php" target="_blank">
<img src="../themes/crystal/imgs/login/spacer.png">
</a>
</div>



<div id="id01" class="modal">
  
  <form class="modal-content animate" action="/action_page.php" method="post">
    <div id="imgcontainer" style="background-color: #000001; margin: 0px auto">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">X</span>
    </div>

    <div class="container2">
      <iframe id="myframe" src="/default.asp" style="border: none; position: relative; top: 5%; left: -3%; width: 107%; height: 105%;"></iframe>
    </div>
    
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
</script>