<?php
require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON'){
    require 'protezione.php';
}

?>
<link href="themes/crystal/login2.css" rel="stylesheet" />

<div class="navbar">
  <a href="#home"><img src="../themes/crystal/imgs/login/informativa_e_privacy.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/affiliati.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/crediti_e_staff.png"></a>
  <a href="#home"><img src="../themes/crystal/imgs/login/facebook.png"></a>
</div>


<div class="div-bg" style="background-image:url('../themes/crystal/imgs/login/colonna2.png')">
<form class="myForm" method="post" action="login.php" id="do_login">
<div class="comandi_input input_nome">
<input type="text" name="login1" id="username" placeholder="nome" style="background-color: transparent; border: solid 0px;" />
</div>
<div class="comandi_input input_password">
<input id="password" type="password" name="pass1" placeholder="password" style="background-color: transparent; border: solid 0px;" />
</div>
<div class="comandi_input tasto">
<input type="submit" value="<?php echo $MESSAGE['homepage']['forms']['login']; ?>" />
</div>
<div class="comandi_input recuperopass">
<a href="#" onclick="changeFrame('themes/crystal/home/recupero_password.php');document.getElementById('id01').style.display='block'">
Recupero password
</a>
</div>

<div class="comandi_input iscrizione">
<a href="index2.php?page=iscrizione">
Recupero password
</a>
</div>

<div class="comandi_input manuale">
<a href="../documentazione_main.php" target="_blank">
Recupero password
</a>
</div>







<div id="id01" class="modal">
  
  <form class="modal-content animate" action="/action_page.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">X</span>
    </div>

    <div class="container2">
      <iframe id="myframe" src="/default.asp"></iframe>
    </div>
  </form>
  
  
  
  
    
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