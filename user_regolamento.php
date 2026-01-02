<?php
/* Includo i file necessari */
include('includes/constant_values.inc.php');
include('config.inc.php');
include('vocabulary/' . $PARAMETERS['languages']['set'] . '.vocabulary.php');
include('includes/functions.inc.php');
require('header.inc.php'); /*Header comune*/


/* Eseguo la connessione al database */
$handleDBConnection = gdrcd_connect();
?>
<head>
<link rel="stylesheet" href="themes/crystal/manuali.css" type="text/css" />
<style type="text/css">
body {   background-image: url('themes/crystal/imgs/bgmanuali.png');
         background-repeat: repeat;
}
.tabella
{
width:600px;
overflow:auto;
background: url('themes/crystal/imgs/bgmanuali.png') repeat scroll 0 0 #333333;
height:550px; 
top:10px;
border-top: 0px dotted #333333;
border-bottom: 1px  #333333;
border-left: 2px double #333333;
border-right: 2px double #333333;
}

 .sezione {
color: #b4b6bf;
background-color: #101e2c;
letter-spacing: 0px;
text-transform: uppercase;
font-family: Tahoma;
font-size: 12px;
text-align: center;
padding: 4px;
margin-right:-2px;
border-top:1px  #333333;
border-left:1px  #333333;border-bottom:1px  #333333;background-image: url(themes/crystal/imgs/bgmanuali.png) border-right: trasparent;
}

.sezione2 {
color: #b4b6bf;
background-color: #101e2c; 
letter-spacing: 0px;
text-transform: uppercase;
font-family: Tahoma;
font-size: 12px;
text-align: center;
padding: 4px;
margin-left:-2px;
border-top:1px #333333;
border-right:1px #333333; border-bottom:1px #333333; background-image: url()
}

.menu1{
margin-top: 5px;
margin-bottom: 5px;
margin-right:-2px;
line-height: 16px;
border-left: 0px dotted #333333;
}
.menu2{
margin-top: 5px;
margin-bottom: 5px;
border-right: 0px dotted #333333;
margin-left:-2px;
line-height: 16px;
}

</style>
</head>
<table align="center">
<tr>
<td>
<div class="sinistra">
<div class="sezione">AMBIENTAZIONE</div>
<div class="menu1">
<ul>
<?php /*AMBEINTAZIONE: */
        $query = "SELECT * FROM regolamento WHERE tipo = 'ambientazione' ORDER BY articolo";
        $result = gdrcd_query($query, 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
?>
<li><a href="user_regolamento_testo.php?articolo=<?php echo gdrcd_filter('out', $row['articolo']); ?>" target="mainFrame"><?php echo gdrcd_filter('out', $row['titolo']); ?></a></li>
<?php 
}
?>
</ul>
</div>

<div class="sezione">REGOLAMENTO</div>
<div class="menu1">
<ul>
<?php /*REGOLAMENTO: */
        $query = "SELECT * FROM regolamento WHERE tipo = 'regolamento' ORDER BY articolo";
        $result = gdrcd_query($query, 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
?>
<li><a href="user_regolamento_testo.php?articolo=<?php echo gdrcd_filter('out', $row['articolo']); ?>" target="mainFrame"><?php echo gdrcd_filter('out', $row['titolo']); ?></a></li>
<?php 
}
?>
</ul>
</div>


<div class="sezione">MANUALI</div>
<ul>

<?php /*MANUALI: */
        $query = "SELECT * FROM regolamento WHERE tipo = 'manuali' ORDER BY articolo";
        $result = gdrcd_query($query, 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
?>
<li><a href="user_regolamento_testo.php?articolo=<?php echo gdrcd_filter('out', $row['articolo']); ?>" target="mainFrame"><?php echo gdrcd_filter('out', $row['titolo']); ?></a></li>
<?php 
}
?>
</ul>
</div>


</td>
<td>
<div style="border-top: 2px double #333333;border-left: 2px double #333333;
border-right: 2px double #333333;background-image: url(imgsito/bgmanuali.png);color: #414141;
text-transform: uppercase;font-family: Tahoma;font-size: 14px;text-align: center; padding-top:5px; padding-right:15px;">
</div>
<div class="tabella"><iframe name="mainFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
</div>
</td>
<td>

<div class="destra">
<div class="sezione2">Manuale combattimento</div>
<div class="menu2">
<ul>
<?php /*AMBEINTAZIONE: */
        $query = "SELECT * FROM regolamento WHERE tipo = 'combattimento' ORDER BY articolo";
        $result = gdrcd_query($query, 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
?>
<li><a href="user_regolamento_testo.php?articolo=<?php echo gdrcd_filter('out', $row['articolo']); ?>" target="mainFrame"><?php echo gdrcd_filter('out', $row['titolo']); ?></a></li>
<?php 
}
?>
</ul>
</div>

<div class="menudx">
<div class="sezione2">STAFF</div>
<div class="menu2">
<ul>
<?php /*AMBEINTAZIONE: */
        $query = "SELECT * FROM regolamento WHERE tipo = 'staff' ORDER BY articolo";
        $result = gdrcd_query($query, 'result');
        while ($row = gdrcd_query($result, 'fetch')) {
?>
<li><a href="user_regolamento_testo.php?articolo=<?php echo gdrcd_filter('out', $row['articolo']); ?>" target="mainFrame"><?php echo gdrcd_filter('out', $row['titolo']); ?></a></li>
<?php 
}
?>
</ul>
</div>

<div class="menudx">

<div class="menu2">

<div class="menu2" style="border-bottom: 1px dotted #333333;">

            </div>
           
</div>
</td>
</tr>
</table>