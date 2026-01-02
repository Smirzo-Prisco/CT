<?php
echo '
<h3>'.gdrcd_filter('out', $MESSAGE['interface']['forums']['delete']['title']).'</h3>
<form action="main.php?page=forum&op=delete_quest" method="post">
  <input type="hidden" name="id_record" value="'.(int) $_REQUEST['id_record'].'" />
  <input type="hidden" name="padre" value="-1" />
  <p>'.gdrcd_filter('out', $MESSAGE['interface']['forums']['delete']['ask']).'</p>';
  
/*in caso fossero presenti px o notorietà*/

$Punti = gdrcd_query("SELECT ID, nome, commento, esperienza, notorieta, mestiere, shin FROM Punti WHERE id_messaggio=".gdrcd_filter('num', $_REQUEST['id_record'])."", 'result');

if (gdrcd_query($Punti, 'num_rows') > 0) {

$i=1;
while($row=gdrcd_query($Punti, 'fetch')) {
//in caso di modifica estraggo solo i risultati
echo "<input type='hidden' name='ID$i' value='" . $row["ID"] . "'>";

echo "<input type='hidden' name='nome$i' value='".$row["nome"]."'>";

echo "<input type='hidden' name='commento$i' value='".$row["commento"]."'>";

echo "<input type='hidden'name='esperienza$i' value='".$row["esperienza"]."'>";

echo "<input type='hidden' name='notorieta$i' value='".$row["notorieta"]."'>";

echo "<input type='hidden' name='mestiere$i' value='".$row["mestiere"]."'>";

echo "<input type='hidden' name='shin$i' value='".$row["shin"]."'>";


                     $i = $i+1;  }//chiusura while
                     
                     
echo '<b>Attenzione</b>: con la cancellazione di questo post, saranno cancellati anche eventuali punti esperienza o punti notoriet&agrave;';
echo '<br><br>';
}//chiusura if
//FINE PX


echo '<input type="submit" value="'.gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']).'" />
</form>
';