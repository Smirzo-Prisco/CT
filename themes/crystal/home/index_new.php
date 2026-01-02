<link href="themes/crystal/login.css" rel="stylesheet" />
<div class="navbar">
  <div class="container">
    <div class="navbar-collapse">
        <div class="menu-icon">
		<a onclick="document.getElementById('id01').style.display='block'"><img src="themes/crystal/imgs/login/affiliati.png"></a>
		</div>
        <div class="menu-icon">
          <a href=""><img src="themes/crystal/imgs/login/facebook.png"></a>
        </div>
        <div class="menu-icon">
          <a href=""><img src="themes/crystal/imgs/login/credit_staff.png"></a>
        </div>
        <div class="menu-icon">
          <a href=""><img src="themes/crystal/imgs/login/informativa_privacy.png"></a>
        </div>
    </div>
  </div>
</div>

<div class="container">
  <form class="myForm" method="post" action="login.php" id="do_login">
  <div class="group">
    <div class="form-group">
      <label for="username"><img  src="themes/crystal/imgs/login/nome.png"></label><br>
      <input class="form-control" type="text" name="login1" id="username" placeholder="nome" />
    </div>
    <div class="form-group">
      <label for="password"><img  src="themes/crystal/imgs/login/password.png"></label><br>
      <input class="form-control" id="password" type="password" name="pass1" placeholder="password" />
    </div>
    <div class="form-group">
      <input type="submit" value="<?php echo $MESSAGE['homepage']['forms']['login']; ?>" />
    </div>
    </div>
  </form>
  
  <div id="id01" class="modal">
  
  <form class="modal-content animate" action="/action_page.php" method="post">
    <div class="imgcontainer">
      <span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">X</span>
    </div>

    <div class="container2">
      <h2>Contenuto qua</h2>
      <span>Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
      Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a 
      galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the 
      leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of 
      Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker 
      including versions of Lorem Ipsum. </span>
    </div>
  </form>
</div>
</div>

<script>
var modal = document.getElementById('id01');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>