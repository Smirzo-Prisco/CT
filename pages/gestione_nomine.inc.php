<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '../../includes/custom_functions.inc.php');

$azioni_permessi = [
    'admin' => ['admin'],
    'moderatore' => ['admin'],
    'master' => ['admin'],
    'capogilda' => ['admin'],
    'capomestiere' => ['admin'],
    'guida' => ['admin'],
    'grafico' => ['admin']
];

/************   Inserisci nuovo utente per assegnargli i privilegi  */
if(isset($_POST['op']) && gdrcd_filter('get', $_POST['op']) == 'new_privilege') {
    $exists = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['personaggio'])."'", 'result');
    // controllo prima se il personaggio esiste già. Se esiste lo aggiorno, altrimenti lo inserisco
    if(gdrcd_query($exists, 'num_rows') > 0) gdrcd_query("UPDATE privilegi SET ".$_POST['privilegio']." = 1 WHERE nome = '".gdrcd_filter('in', $_POST['personaggio'])."'");
    else gdrcd_query("INSERT INTO privilegi (nome, ".$_POST['privilegio'].") VALUES ('".gdrcd_filter('in', $_POST['personaggio'])."', '1')");
                
    echo "<script type='text/javascript'>alert('Personaggio ".$_POST["personaggio"]." nominato ".$_POST['privilegio']."');</script>";
}
    
/************   Modifica/elimina privilegi  */
if(isset($_POST["edit_pg"]) && $_POST["edit_pg"] != '') {
    $pg_to_edit = $_POST["edit_pg"]; // Prendo il primo valore, che corrisponde sempre al nome del pg da modificare

    // Se l'utente non ha più privilegi, lo elimino dalla tabella
    if(empty($_POST["privilegi"][$pg_to_edit])) {
        gdrcd_query("DELETE FROM privilegi where nome = '".$pg_to_edit."'");

        echo "<script type='text/javascript'>alert('$pg_to_edit rimosso per mancanza di privilegi.');</script>";
    } else { // Altrimenti modifica i privilegi dell'utente
        $ruolo = [];
        $privilegi = gdrcd_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$PARAMETERS['database']['database_name']."' AND TABLE_NAME = 'privilegi'", 'result');

        while($row = gdrcd_query($privilegi, 'fetch')) {
            // Se la colonna è tra i ruoli che devo portare a uno, altrimenti lo riporto a zero
            // sempre_online non è un ruolo/nomina gestito da questa pagina: escluso per non
            // farlo azzerare inavvertitamente ogni volta che si modifica un altro privilegio
            if($row['COLUMN_NAME'] != 'nome' && $row['COLUMN_NAME'] != 'sempre_online') $ruolo[] = in_array($row['COLUMN_NAME'], $_POST["privilegi"][$pg_to_edit]) ? $row['COLUMN_NAME'].'= 1' : $row['COLUMN_NAME'].'= 0';
        }

        gdrcd_query("UPDATE privilegi SET " . implode(", ", $ruolo) . " WHERE nome = '".gdrcd_filter('in', $pg_to_edit)."'");

        echo "<script type='text/javascript'>alert('Privilegi modificati per $pg_to_edit');</script>";
    }
}
/*********  FINE modifica ai privilegi */

// filtri
$filtro = (isset($_POST['filtro']) ? $_POST['filtro'] : 'tutti');

switch($filtro) {
    case 'admin': $where = "WHERE admin = 1"; break;
    case 'moderatore': $where = "WHERE moderatore = 1"; break;
    case 'master': $where = "WHERE master = 1"; break;
    case 'capogilda': $where = "WHERE capogilda = 1"; break;
    case 'capomestiere': $where = "WHERE capomestiere = 1"; break;
    case 'guida': $where = "WHERE guida = 1"; break;
    case 'grafico': $where = "WHERE grafico = 1"; break;
    default: $where = '';
}
// FINE filtri

// Paginazione
$offset = isset($_REQUEST['offset']) && $_REQUEST['offset'] > 0 ? (int)$_REQUEST['offset'] : 1;
$pagebegin = (int) gdrcd_filter('get', ($offset-1)) * $PARAMETERS['settings']['records_per_page'];
// Query
$personaggi = gdrcd_query("SELECT privilegi.nome, personaggio.url_img_chat FROM privilegi
                        LEFT JOIN personaggio ON privilegi.nome = personaggio.nome
                        $where
                        ORDER BY nome ASC
                        LIMIT ".$pagebegin.", ".$PARAMETERS['settings']['records_per_page'], 'result');
// FINE query
?>

<link rel="stylesheet" href="../themes/crystal/uffici_nuovi.css">

<!-- Lista personaggi -->
<div class="topbar">
  <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
  <!-- Legenda icone azioni su oggetto -->
  <div class="legend-compact">
    <div><i class="fa-solid fa-pen-to-square"></i><span>Modifica</span></div>
  </div>
  <div></div>
</div>
<!-- Filtri -->
<form method="post" class="filter-form" action="main.php?page=gestione_nomine">
  <label><input type="radio" name="filtro" value="tutti" <?= $filtro=='tutti'?'checked':'' ?> onchange="this.form.submit()">Tutti</label>
  <label><input type="radio" name="filtro" value="admin" <?= $filtro=='admin'?'checked':'' ?> onchange="this.form.submit()">Admin</label>
  <label><input type="radio" name="filtro" value="moderatore" <?= $filtro=='moderatore'?'checked':'' ?> onchange="this.form.submit()">Moderatori</label>
  <label><input type="radio" name="filtro" value="master" <?= $filtro=='master'?'checked':'' ?> onchange="this.form.submit()">Master</label>
  <!-- <label><input type="radio" name="filtro" value="capomestiere" <?= $filtro=='capomestiere'?'checked':'' ?> onchange="this.form.submit()">Capo mestiere</label> -->
  <label><input type="radio" name="filtro" value="guida" <?= $filtro=='guida'?'checked':'' ?> onchange="this.form.submit()">Guide</label>
  <label><input type="radio" name="filtro" value="grafico" <?= $filtro=='grafico'?'checked':'' ?> onchange="this.form.submit()">Grafici</label>
</form>
<!-- Lista personaggi -->
<div class="item-list">
    <table>
        <tr>
            <th>Avatar</th>
            <th>Posseduti</th>
            <th>Nome</th>
            <th>Definisci</th>
            <th>Salva</th>
        </tr>
    <?php foreach ($personaggi as $pg):?>
        <tr>
            <td width="5%"><img width="25" height="25" src="<?=$pg['url_img_chat']?>"/></td>
            <td> <!-- Privilegi posseduti -->
                <?php
                    $row_pri = gdrcd_query("SELECT * FROM privilegi WHERE nome=".'"'.gdrcd_filter('out', $pg['nome']).'"');
            
                    if($row_pri['admin'] == 1) echo '<img src="themes/crystal/imgs/staff/Admin.png" width="20" height="20" title="Coordinatore">';
                    if($row_pri['moderatore'] == 1) echo '<img src="themes/crystal/imgs/staff/Moderatore.png" width="20" height="20" title="Moderatore">';
                    if($row_pri['master'] == 1) echo '<img src="themes/crystal/imgs/staff/Master.png" width="20" height="20" title="Master">';
                    if($row_pri['guida'] == 1) echo '<img src="themes/crystal/imgs/staff/Guida.png" width="20" height="20" title="Guida">';
                    if($row_pri['grafico'] == 1) echo '<img src="themes/crystal/imgs/staff/Grafico.png" width="20" height="20" title="Grafico">';
                ?>
            </td>
            <td><?= htmlspecialchars($pg['nome']) ?></td>
            <!-- Colonna Azioni -->
            <form action="main.php?page=gestione_nomine" method="POST">
                <td class="checkbox-img">
                    <?php // Definisci privilegi
                        // Seleziono i nomi delle colonne della tabella 'privilegi' (i nomi delle colonne corrispondono ai privilegi)
                        $privilegi = gdrcd_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$PARAMETERS['database']['database_name']."' AND TABLE_NAME = 'privilegi'", 'result');

                        while ($row = gdrcd_query($privilegi, 'fetch')):
                            $ruolo = $row['COLUMN_NAME'];

                            if ($ruolo != 'nome' && $ruolo != 'capogilda' && $ruolo != 'capomestiere' && $ruolo != 'sempre_online' && hasPermesso($_SESSION, $azioni_permessi[$ruolo])) {
                                $checked = $row_pri[$ruolo] == 1 ? 'checked' : '';

                                echo '<input type="hidden" name="edit_pg" value="'.$pg['nome'].'">';
                                echo '<input type="checkbox" id="'.$pg['nome'].'_'.$ruolo.'" name="privilegi['.$pg['nome'].'][]" value="'.$ruolo.'" '.$checked.'>';
                                echo '<label for="'.$pg['nome'].'_'.$ruolo.'">';
                                echo '<img src="themes/crystal/imgs/staff/'.ucfirst($ruolo).'.png" title="'.ucfirst($ruolo).'">';
                                echo '</label>';
                            }
                        endwhile;
                    ?>
                </td>
                <td><button type="submit" class="btn-action" title="Rendi <?= $ruolo ?>"><i class="fa-solid fa-pen-to-square"></i></button></td>
            </form>
        </tr>
<?php endforeach; ?>
        <!-- Aggiungi un utente per assegnargli dei privilegi -->
        <form method=Post action="main.php?page=gestione_nomine">
            <tr>
                <td></td>
                <td><input name="personaggio" value="" maxlength="20" placeholder="Personaggio..."></td>
                <td>
                    <select name="privilegio">
                        <?php
                        $privilegi = gdrcd_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$PARAMETERS['database']['database_name']."' AND TABLE_NAME = 'privilegi'", 'result');

                        while ($row = gdrcd_query($privilegi, 'fetch')):
                            echo ($row['COLUMN_NAME'] != 'nome' && $row['COLUMN_NAME'] != 'sempre_online') ? '<option value="'.$row['COLUMN_NAME'].'">'.$row['COLUMN_NAME'].'</option>' : '';
                        endwhile; ?>
                    </select>
                </td>
                <td>
                    <input type="hidden" name="op" value="new_privilege">
                    <input type="submit" value="Aggiungi">
                </td>
            </tr>
        </form>
    </table>
</div>
<!-- FINE lista personaggi -->

<!-- Paginazione -->
<div class="pagination">
    <?php
    // Genera i link di paginazione
    $totaleresults = gdrcd_query("SELECT COUNT(*) AS total FROM privilegi $where")['total'];
    $total_pages = ceil($totaleresults / $PARAMETERS['settings']['records_per_page']);
    echo generateLinks($offset, $total_pages, preg_replace('/&.*/', '', $_SERVER["REQUEST_URI"]));
    echo "<p>Pagina $offset di $total_pages (Totale: $totaleresults record)</p>";
    ?>
</div>
<!-- FINE paginazione -->


<?php die(); ?>




<!-- ***************************************    INIZIO VECCHIO CODICE   ****************************************************************** -->
<div class="pagina_gestione_permessi">
    <?php
    /*HELP: */
    /*Controllo permessi utente*/
    if($_SESSION['admin'] != 1) {
        echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
    } else { 
    
    /* Valorizziamo tutte le nomine nuove */
    // Admin
    if(gdrcd_filter('get', $_POST['op']) == 'new_admin') {
        $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
      
        if(gdrcd_query($result, 'num_rows') > 0) {
            gdrcd_query($result, 'free');
            $query = "UPDATE privilegi SET admin = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
        } else {
            $query = "INSERT INTO privilegi (nome, admin) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
        }
        gdrcd_query($query);
                    
        echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato Admin');</script>";
    }
    // Master
    if(gdrcd_filter('get', $_POST['op']) == 'new_master') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
        if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET master = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
        } else {
          $query = "INSERT INTO privilegi (nome, master) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
        }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato Master');</script>";
    }
    // Moderatore
    if(gdrcd_filter('get', $_POST['op']) == 'new_moderatore') {
        $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
        
        if(gdrcd_query($result, 'num_rows') > 0) {
            gdrcd_query($result, 'free');
            $query = "UPDATE privilegi SET moderatore = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
        } else {
            $query = "INSERT INTO privilegi (nome, moderatore) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
        }
        gdrcd_query($query);
                    
        echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato Moderatore');</script>";
    }
    // Guida
    if(gdrcd_filter('get', $_POST['op']) == 'new_guida') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET guida = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, guida) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato Guida');</script>";
    }
    // Grafico
    if(gdrcd_filter('get', $_POST['op']) == 'new_grafico') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET grafico = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, grafico) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato Grafico');</script>";
    }
    // Capo mestiere
    if(gdrcd_filter('get', $_POST['op']) == 'new_capomestiere') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET capomestiere = 1 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, capomestiere) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." nominato capomestiere');</script>";
    }
                
                
    /*Valorizziamo tutte le cancellature*/
    if(gdrcd_filter('get', $_POST['op']) == 'change_admin') {
        $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
        
        if(gdrcd_query($result, 'num_rows') > 0) {
            gdrcd_query($result, 'free');
            $query = "UPDATE privilegi SET admin = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
        } else {
            $query = "INSERT INTO privilegi (nome, admin) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
        }
        gdrcd_query($query);
                
        echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come Admin');</script>";
    }

    if(gdrcd_filter('get', $_POST['op']) == 'change_master') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET master = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, master) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come Master');</script>";
    }
          
    if(gdrcd_filter('get', $_POST['op']) == 'change_moderatore') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET moderatore = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, moderatore) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come Moderatore');</script>";
    }
          
    if(gdrcd_filter('get', $_POST['op']) == 'change_guida') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET guida = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, guida) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come Guida');</script>";
    }
          
    if(gdrcd_filter('get', $_POST['op']) == 'change_grafico') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET grafico = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, grafico) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come Grafico');</script>";
          }
          
                    if(gdrcd_filter('get', $_POST['op']) == 'change_capomestiere') {
          $result = gdrcd_query("SELECT * FROM privilegi WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'", 'result');
          
          if(gdrcd_query($result, 'num_rows') > 0) {
          gdrcd_query($result, 'free');
          $query = "UPDATE privilegi SET capomestiere = 0 WHERE nome = '".gdrcd_filter('in', $_POST['nome'])."'";
                    } else {
          $query = "INSERT INTO privilegi (nome, capomestiere) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', '1')";
                    }
                    gdrcd_query($query);
                    
          echo "<script type='text/javascript'>alert('Personaggio ".$_POST["nome"]." rimosso come capomestiere');</script>";
    }

?>
   
    <!-- ADMIN -->
    <div class='form_label'>Nomina Admin</div>
    <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
    <?php           
        $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.admin = 1 ORDER by personaggio.nome", 'result');
        while ($row = gdrcd_query($elenco, 'fetch')) {
            echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
            echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
            echo '<td><select Name=admin>';
            echo '<option value="0" style="background-color:#FF3030"> - </option>';
            echo '<option value="1"';
            if ($row['admin'] == 1) echo ' selected';
            echo ' style="background-color:#8080FF">Admin</option>';
            echo '</select></td>';
            echo '<input type="hidden" name="op" value="change_admin" />';
            echo '<td><input type=submit value="Cambia"></td>';
            echo '</tr></form>';
        }//fine while
        
        echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
        echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
        echo '<td><select Name=tipo_admin>';
        echo '<option value="1" style="background-color:gray">Admin</option>';
        echo '</select></td>';
        echo '<input type="hidden" name="op" value="new_admin" />';
        echo '<td><input type=submit value="Nuovo"></td>';
        echo '</tr></form>';            
    ?>
    </table>
    <br>
    <hr>
    <br><br>
    
     <!-- MASTER -->
           <div class='form_label'>
                    <?php echo "Nomina Master"; ?>
                </div>
           <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
           
           <?php           
           $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.master = 1 ORDER by personaggio.nome", 'result');
           while ($row = gdrcd_query($elenco, 'fetch')) {
           

           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
           echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
	       echo '<td><select Name=master>';
	       echo '<option value="0" style="background-color:#FF3030"> - </option>';
	       echo '<option value="1"';
	       if ($row['master']==1) {
		   echo ' selected';
	                             }
           echo ' style="background-color:#8080FF">Master</option>';
           echo '</select></td>';
           echo '<input type="hidden" name="op" value="change_master" />';
	       echo '<td><input type=submit value="Cambia"></td>';
	       echo '</tr></form>';
           
                               }//fine while
           
           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
	       echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
	       echo '<td><select Name=tipo_master>';
	       echo '<option value="1" style="background-color:gray">Master</option>';
	       echo '</select></td>';
           echo '<input type="hidden" name="op" value="new_master" />';
           echo '<td><input type=submit value="Nuovo"></td>';
	       echo '</tr></form>';
                                 
             ?>
             
             </table>
             <br>
             <hr>
             <br><br>
             
             <!-- MODERATORE -->
             <div class='form_label'>
                    <?php echo "Nomina Moderatore"; ?>
                </div>
           <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
           
           <?php           
           $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.moderatore = 1 ORDER by personaggio.nome", 'result');
           while ($row = gdrcd_query($elenco, 'fetch')) {
           

           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
           echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
	       echo '<td><select Name=moderatore>';
	       echo '<option value="0" style="background-color:#FF3030"> - </option>';
	       echo '<option value="1"';
	       if ($row['moderatore']==1) {
		   echo ' selected';
	                             }
           echo ' style="background-color:#8080FF">Moderatore</option>';
           echo '</select></td>';
           echo '<input type="hidden" name="op" value="change_moderatore" />';
	       echo '<td><input type=submit value="Cambia"></td>';
	       echo '</tr></form>';
           
                               }//fine while
           
           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
	       echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
	       echo '<td><select Name=tipo_moderatore>';
	       echo '<option value="1" style="background-color:gray">Moderatore</option>';
	       echo '</select></td>';
           echo '<input type="hidden" name="op" value="new_moderatore" />';
           echo '<td><input type=submit value="Nuovo"></td>';
	       echo '</tr></form>';
                                 
             ?>
             
             </table>
             <br>
             <hr>
             <br><br>
             
                          <!-- GUIDA -->
             <div class='form_label'>
                    <?php echo "Nomina Guida"; ?>
                </div>
           <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
           
           <?php           
           $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.guida = 1 ORDER by personaggio.nome", 'result');
           while ($row = gdrcd_query($elenco, 'fetch')) {
           

           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
           echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
	       echo '<td><select Name=guida>';
	       echo '<option value="0" style="background-color:#FF3030"> - </option>';
	       echo '<option value="1"';
	       if ($row['guida']==1) {
		   echo ' selected';
	                             }
           echo ' style="background-color:#8080FF">Guida</option>';
           echo '</select></td>';
           echo '<input type="hidden" name="op" value="change_guida" />';
	       echo '<td><input type=submit value="Cambia"></td>';
	       echo '</tr></form>';
           
                               }//fine while
           
           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
	       echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
	       echo '<td><select Name=tipo_guida>';
	       echo '<option value="1" style="background-color:gray">Guida</option>';
	       echo '</select></td>';
           echo '<input type="hidden" name="op" value="new_guida" />';
           echo '<td><input type=submit value="Nuovo"></td>';
	       echo '</tr></form>';
                                 
             ?>
             
             </table>
             <br>
             <hr>
             <br><br>
             
                          <!-- GRAFICO -->
             <div class='form_label'>
                    <?php echo "Nomina Grafiche"; ?>
                </div>
           <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
           
           <?php           
           $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.grafico = 1 ORDER by personaggio.nome", 'result');
           while ($row = gdrcd_query($elenco, 'fetch')) {
           

           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
           echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
	       echo '<td><select Name=grafico>';
	       echo '<option value="0" style="background-color:#FF3030"> - </option>';
	       echo '<option value="1"';
	       if ($row['grafico']==1) {
		   echo ' selected';
	                             }
           echo ' style="background-color:#8080FF">Grafico</option>';
           echo '</select></td>';
           echo '<input type="hidden" name="op" value="change_grafico" />';
	       echo '<td><input type=submit value="Cambia"></td>';
	       echo '</tr></form>';
           
                               }//fine while
           
           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
	       echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
	       echo '<td><select Name=tipo_grafico>';
	       echo '<option value="1" style="background-color:gray">Grafico</option>';
	       echo '</select></td>';
           echo '<input type="hidden" name="op" value="new_grafico" />';
           echo '<td><input type=submit value="Nuovo"></td>';
	       echo '</tr></form>';
                                 
             ?>
             
             </table>
             <br>
             <hr>
             <br><br>
             
                          <!-- CAPOMESTIERE -->
             <div class='form_label'>
                    <?php echo "Nomina capomestiere"; ?>
                </div>
           <table cellpadding=1 cellspacing=1 border=0 class=tTitolo>
           
           <?php           
           $elenco = gdrcd_query("SELECT * FROM personaggio LEFT JOIN privilegi ON personaggio.nome = privilegi.nome WHERE privilegi.capomestiere = 1 ORDER by personaggio.nome", 'result');
           while ($row = gdrcd_query($elenco, 'fetch')) {
           

           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr class=tChiaro>';
           echo '<td><input type=hidden Name="nome" value="'.htmlspecialchars($row['nome']).'"> '.htmlspecialchars($row['nome']).'</td>';
	       echo '<td><select Name=capomestiere>';
	       echo '<option value="0" style="background-color:#FF3030"> - </option>';
	       echo '<option value="1"';
	       if ($row['capomestiere']==1) {
		   echo ' selected';
	                             }
           echo ' style="background-color:#8080FF">capomestiere</option>';
           echo '</select></td>';
           echo '<input type="hidden" name="op" value="change_capomestiere" />';
	       echo '<td><input type=submit value="Cambia"></td>';
	       echo '</tr></form>';
           
                               }//fine while
           
           echo '<form method=Post action="main.php?page=gestione_nomine" class="form_gestione"><tr>';
	       echo '<td><input Name="nome" value="'.htmlspecialchars($row['nome']).'" maxlength=20></td>';
	       echo '<td><select Name=tipo_capomestiere>';
	       echo '<option value="1" style="background-color:gray">capomestiere</option>';
	       echo '</select></td>';
           echo '<input type="hidden" name="op" value="new_capomestiere" />';
           echo '<td><input type=submit value="Nuovo"></td>';
	       echo '</tr></form>';
                                 
             ?>
             
             </table>
           
<?php
     }//fine
?>
</div>