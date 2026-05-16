<head>
<link href="https://fonts.googleapis.com/css?family=Amatic+SC" rel="stylesheet">
<link href="themes/crystal/documentazione.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<title>CT - Documentazione</title>  
</head>

<body>

  <div class="openmenu">
      <iframe name="openmenuframe" src ="documentazione_menu3.php" class="iframemenu" allowtransparency="true" frameborder="0"></iframe>
  </div>
<div class="content">  
  <div class="opendoc">
      <iframe name="opendocframe" src ="documentazione_testo.php?articolo=-1" class="iframedoc" allowtransparency="true" frameborder="0"></iframe>
  </div>
</div>
</body>


<script type="text/javascript">
$(function() {
      $( 'ul.ul-top li' ).on( 'click', function() {
            $( this ).parent().find( 'li.active' ).removeClass( 'active' );
            $( this ).addClass( 'active' );
      });
});
</script>