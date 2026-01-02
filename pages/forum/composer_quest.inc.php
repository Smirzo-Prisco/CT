<link rel="stylesheet" href="../themes/crystal/mestieri.css">
<?php
$padre = gdrcd_filter('num', $_REQUEST['what']);
$araldo = gdrcd_filter('num', $_REQUEST['where']);

$quote = gdrcd_filter('num', $_REQUEST['quote']);

$join = '';
$cond = '';
if ($araldo != 10 AND $araldo != 7) {
echo '<div class="error">Non puoi usare la funzione <b>Inserisci Quest</b> in questa bacheca!</div>';
} else {
if($padre != -1) {
    //Se sto inserendo in un thread, verifico che esista
    $join = ' INNER JOIN messaggioaraldo AS MA ON araldo.id_araldo=MA.id_araldo ';
    $cond = ' AND id_messaggio='.$padre." AND id_messaggio_padre=-1";
}

$araldoData = gdrcd_query("SELECT count(*) AS N FROM araldo".$join." WHERE araldo.id_araldo = ".$araldo.$cond);

if($araldoData['N'] > 0) {
    ?>
    <div class="panels_box">
        <div class="form_gioco">
            <form action="main.php?page=forum" method="post" name="rapido">
<table class="customTable">
<tr>
<td>
INSERIMENTO QUEST
</td>
</tr>
<tr class="second_header">
<td>
<b>TITOLO QUEST</b>
</td>
</tr>
                <?php
                if($padre == -1) {
                    /*Se e' il primo post di un topic serve il titolo*/
                    ?>

                    <tr>
                    <td>
                        <input name="titolo" placeholder="Inserire titolo quest" class="ares" />
                    </td>
                    </tr>
                    <?php
                }
                ?>
<tr class="second_header">
<td>
<b>TIPOLOGIA QUEST</b>
</td>
</tr>
<tr>
<td>
<select name="tipologia" class="ares">
      <option>Assegnazione Esperienza o Notorietà</option>
      <option>Quest Singola</option>
      <option>Quest di Gilda</option>
      <option>Evento</option>
      <option>Prima parte</option>
      <option>Seconda parte</option>
      <option>Terza parte</option>
      <option>Quarta parte</option>
      <option>Quinta parte</option>
      <option>Sesta parte</option>
      <option>Settima parte</option>
      <option>Ottava parte</option>
      <option>Nona parte</option>
      <option>Finale</option>
    </select>
</td>
</tr>
<tr class="second_header">
<td>
<b>PARTECIPANTI</b>
</td>
</tr>
<tr>
<td>
<input type=text name="partecipanti" placeholder="Inserire partecipanti" class=ares> 
</td>
</tr> 
<tr class="second_header">
<td>
<b>LOCATION</b>
</td>
</tr>
<tr>
<td>
<input type=text name="location" placeholder="Inserire location" class=ares> 
</td>
</tr> 
<tr class="second_header">
<td>
<b>RIASSUNTO QUEST</b>
</td>
</tr>
<tr>
<td>
    <textarea name="riassunto" placeholder="Inserire riassunto quest (max 500 caratteri)" class="ares"></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>CONSEGUENZE QUEST</b>
</td>
</tr>
<tr>
<td>
    <textarea name="conseguenze" placeholder="Inserire conseguenze quest (eventi, conseguenze pg, alterazioni ambientazioni ecc)" class="ares"></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>NOTE</b>
</td>
</tr>
<tr>
<td>
    <textarea name="note" placeholder="Inserire note quest (punti salute, potenziamento oggetti di gilda, skill acquisite, oggetti ecc)" class="ares"></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>VALUTAZIONI</b>
</td>
</tr>
<tr>
<td>
    <textarea name="valutazioni" placeholder="Inserire valutazioni pg" class="ares"></textarea>
</td>
</tr>
</table>                
                <div class="form_field">


<?php //Qualora fosse una bacheca da punti esperienza//
$araldoPunti = gdrcd_query("SELECT * FROM araldo WHERE id_araldo = $araldo");

if($araldoPunti['punti'] > 0) {
echo '<tr>';
for ($i = 1; $i <= 20; $i++) {
?>
<td>
Pg: 
<?php
echo "<input style='width: 150px;' name='nome$i'>";
?>
<!--Commento: -->
<?php
//echo "<input style='width: 200px;' name='commento$i'>";
?>
Exp: 
<?php
                        echo "<select  style='width: 50px;' name=esperienza$i>";
                        for ($j = -10; $j <= 20; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>

Shin: 
<?php
                        echo "<select  style='width: 50px;' name=shin$i>";
                        for ($j = -10; $j <= 20; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>

Notoriet&agrave;: 
<?php
                        echo "<select  style='width: 50px;' name=notorieta$i>";
                        for ($j = -10; $j <= 10; $j++) {
                                echo '<option value=\''.($j).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j).'</option>';
                        }
                echo '</select>';
?>
P Mestiere: 
<?php
                        echo "<select  style='width: 50px;' name=mestiere$i>";
                        for ($j = -10; $j <= 20; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>
<br>
</td>
<?php
                       }//chiusura for
echo '</tr>';
                             }//chiusura if
?>
                </div>
                <div class="form_submit">
                    <input type="hidden" name="op" value="insert_quest" />
                    <input type="hidden" name="araldo" value="<?php echo $araldo; ?>" />
                    <input type="hidden" name="padre" value="<?php echo $padre; ?>" />
                    <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
                </div>
            </form>
        </div>
    </div>
    <div class="link_back">
        <a href="main.php?page=forum">
            <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
        </a>
    </div>
    <?php
} else {
    echo '<div class="warning">', $MESSAGE['interface']['administration']['forums']['not_exists'], '</div>';
}
}