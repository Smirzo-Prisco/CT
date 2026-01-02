<?php
echo '
<h3>'.gdrcd_filter('out', $MESSAGE['interface']['forums']['delete']['title']).'</h3>
<form action="main.php?page=forum&op=delete" method="post">
  <input type="hidden" name="id_record" value="'.(int) $_REQUEST['id_record'].'" />
  <input type="hidden" name="padre" value="-1" />
  <p>'.gdrcd_filter('out', $MESSAGE['interface']['forums']['delete']['ask']).'</p>';
  

echo '<b>Attenzione</b>: la cancellazione di questo post comporter&agrave; la perdita di tutte le risposte';
echo '<br><br>';

echo '<input style="width:100px;" type="submit" value="'.gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['delete']).'" />
</form>
';