<link rel="stylesheet" href="../themes/crystal/mestieri.css">

<?php
$row = gdrcd_query("SELECT * FROM messaggio_quest WHERE id_messaggio=".gdrcd_filter('num', $_REQUEST['what'])."");
$row_2 = gdrcd_query("SELECT * FROM messaggio_quest WHERE id_messaggio=".gdrcd_filter('num', $_REQUEST['what'])."");
?>
<div class="panels_box">
    <div class="form_gioco">
    <?php
    //genero i permessi di chi può modificare
    
    if($row_2['autore'] == $_SESSION['login'] || ($row_2['autore'] != $_SESSION['login'] && $_SESSION['admin'] == 1)) {
    
    ?>
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


                    <tr>
                    <td>
                        <input name="titolo" placeholder="Inserire titolo quest" class="ares" value="<?php echo gdrcd_filter('out', $row['titolo']); ?>" />
                    </td>
                    </tr>

<tr class="second_header">
<td>
<b>TIPOLOGIA QUEST</b>
</td>
</tr>
<tr>
<td>
<select name="tipologia" class="ares">
      <option <?php if($row['tipologia'] == 'Assegnazione Esperienza o Notorietà') {echo 'selected';} ?>>Assegnazione Esperienza o Notorietà</option>
      <option <?php if($row['tipologia'] == 'Quest Singola') {echo 'selected';} ?>>Quest Singola</option>
      <option <?php if($row['tipologia'] == 'Quest di Gilda') {echo 'selected';} ?>>Quest di Gilda</option>
      <option <?php if($row['tipologia'] == 'Evento') {echo 'selected';} ?>>Evento</option>
      <option <?php if($row['tipologia'] == 'Prima parte') {echo 'selected';} ?>>Prima parte</option>
      <option <?php if($row['tipologia'] == 'Seconda parte') {echo 'selected';} ?>>Seconda parte</option>
      <option <?php if($row['tipologia'] == 'Terza parte') {echo 'selected';} ?>>Terza parte</option>
      <option <?php if($row['tipologia'] == 'Quarta parte') {echo 'selected';} ?>>Quarta parte</option>
      <option <?php if($row['tipologia'] == 'Quinta parte') {echo 'selected';} ?>>Quinta parte</option>
      <option <?php if($row['tipologia'] == 'Sesta parte') {echo 'selected';} ?>>Sesta parte</option>
      <option <?php if($row['tipologia'] == 'Settima parte') {echo 'selected';} ?>>Settima parte</option>
      <option <?php if($row['tipologia'] == 'Ottava parte') {echo 'selected';} ?>>Ottava parte</option>
      <option <?php if($row['tipologia'] == 'Nona parte') {echo 'selected';} ?>>Nona parte</option>
      <option <?php if($row['tipologia'] == 'Finale') {echo 'selected';} ?>>Finale</option>
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
<input type=text name="partecipanti" placeholder="Inserire partecipanti" class=ares value="<?php echo gdrcd_filter('in', $row['partecipanti']); ?>"> 
</td>
</tr> 
<tr class="second_header">
<td>
<b>LOCATION</b>
</td>
</tr>
<tr>
<td>
<input type=text name="location" placeholder="Inserire location" class=ares value="<?php echo gdrcd_filter('in', $row['location']); ?>"> 
</td>
</tr> 
<tr class="second_header">
<td>
<b>RIASSUNTO QUEST</b>
</td>
</tr>
<tr>
<td>
    <textarea name="riassunto" <?php echo $row['riassunto']; ?> placeholder="Inserire riassunto quest (max 500 caratteri)" class="ares"><?php echo gdrcd_filter('in', $row['riassunto']); ?></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>CONSEGUENZE QUEST</b>
</td>
</tr>
<tr>
<td>
    <textarea name="conseguenze" <?php echo $row['conseguenze']; ?> placeholder="Inserire conseguenze quest (eventi, conseguenze pg, alterazioni ambientazioni ecc)" class="ares"><?php echo gdrcd_filter('in', $row['conseguenze']); ?></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>NOTE</b>
</td>
</tr>
<tr>
<td>
    <textarea name="note" <?php echo $row['note']; ?> placeholder="Inserire note quest (punti salute, potenziamento oggetti di gilda, skill acquisite, oggetti ecc)" class="ares"><?php echo gdrcd_filter('in', $row['note']); ?></textarea>
</td>
</tr>
<tr class="second_header">
<td>
<b>VALUTAZIONI</b>
</td>
</tr>
<tr>
<td>
    <textarea name="valutazioni" placeholder="Inserire valutazioni pg" class="ares"><?php echo gdrcd_filter('in', $row['valutazioni']); ?></textarea>
</td>
</tr>
</table>     
        
            <div class="form_field">

<?php //Qualora fosse una bacheca da punti esperienza//

$araldoPunti = gdrcd_query("SELECT * FROM araldo WHERE id_araldo = ".gdrcd_filter('num', $_REQUEST['prev'])."");

if($araldoPunti['punti'] > 0 && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1)) {
echo '<tr>';
$Punti = gdrcd_query("SELECT ID, nome, commento, esperienza, notorieta, mestiere, shin FROM Punti WHERE id_messaggio=".gdrcd_filter('num', $_REQUEST['what'])."", 'result');
$i=1;
while($row=gdrcd_query($Punti, 'fetch')) {
//in caso di modifica estraggo solo i risultati
echo "<input type='hidden' name='ID$i' value='" . $row["ID"] . "'>";
?>
<td>
Pg: 
<?php
echo "<input style='width: 150px;' name='nome$i' value='".$row["nome"]."'>";
?>
<!--Commento: -->
<?php
//echo "<input style='width: 200px;' name='commento$i' value='".$row["commento"]."'>";
?>
Exp: 
<?php
                        echo "<select  style='width: 40px;' name=esperienza$i>";
                        for ($j = -10; $j <= 40; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if (($row['esperienza']==($j/2)) || ((''.$row['nome']=='') && ($j == 0))) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>

Shin: 
<?php
                        echo "<select  style='width: 40px;' name=shin$i>";
                        for ($j = -10; $j <= 40; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if (($row['shin']==($j/2)) || ((''.$row['nome']=='') && ($j == 0))) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>

Notoriet&agrave;: 
<?php
                        echo "<select  style='width: 40px;' name=notorieta$i>";
                        for ($j = -10; $j <= 40; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if (($row['notorieta']==($j/2)) || ((''.$row['nome']=='') && ($j == 0))) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>
P. Mestiere: 
<?php
                        echo "<select  style='width: 40px;' name=mestiere$i>";
                        for ($j = -10; $j <= 40; $j++) {
                                echo '<option value=\''.($j/2).'\'';
                                if (($row['mestiere']==($j/2)) || ((''.$row['nome']=='') && ($j == 0))) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j/2).'</option>';
                        }
                echo '</select>';
?>
<br>
</td>
<?php
                     $i = $i+1;  }//chiusura for
echo '</tr>';
                             }//chiusura if
?>
<?php
//ne inserisco altri vuoti//
if($araldoPunti['punti'] > 0 && ($_SESSION['master'] == 1 || $_SESSION['admin'] == 1)) {
echo '<tr>';
for ($i = 1; $i <= 20; $i++) {
?>
<td>
Pg: 
<?php
echo "<input style='width: 150px;' name='nome_new$i'>";
?>
<!--Commento: -->
<?php
//echo "<input style='width: 200px;' name='commento_new$i'>";
?>
Exp: 
<?php
                        echo "<select  style='width: 50px;' name=esperienza_new$i>";
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
                        echo "<select  style='width: 50px;' name=shin_new$i>";
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
                        echo "<select  style='width: 50px;' name=notorieta_new$i>";
                        for ($j = -10; $j <= 10; $j++) {
                                echo '<option value=\''.($j).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j).'</option>';
                        }
                echo '</select>';
?>
P. Mestiere: 
<?php
                        echo "<select  style='width: 50px;' name=mestiere_new$i>";
                        for ($j = -10; $j <= 10; $j++) {
                                echo '<option value=\''.($j).'\'';
                                if ($j == 0) {
                                        echo ' SELECTED';
                                }
                                echo '>'.($j).'</option>';
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
                <input type="hidden" name="op" value="edit_quest" />
                <input type="hidden" name="araldo" value="<?php echo gdrcd_filter('num', $_REQUEST['where']); ?>" />
                <input type="hidden" name="prev" value="<?php echo gdrcd_filter('num', $_REQUEST['prev']); ?>" />
                <input type="hidden" name="messaggio_padre" value="<?php echo gdrcd_filter('num', $row['id_messaggio_padre']); ?>" />
                <input type="hidden" name="id_messaggio" value="<?php echo gdrcd_filter('num', $_REQUEST['what']); ?>" />
                <input type="submit" name="dummy" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['forms']['submit']); ?>" />
            </div>
        </form>
        <?php }//fine if permessi ?>
    </div>
</div>
<div class="link_back">
    <a href="main.php?page=forum">
        <?php echo gdrcd_filter('out', $MESSAGE['interface']['forums']['link']['topic']); ?>
    </a>
</div>
