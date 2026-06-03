<?php
require_once(__DIR__ . '../../includes/custom_functions.inc.php');

add_script("/includes/oggetto.js");

$azioni_permessi = [
    'crea' => ['admin','master'],
    'modifica' => ['admin','master'], // ,'moderatore','guida', 'grafico'
    'cancella' => ['admin'],
    'assegna'  => ['admin'],
    'approva'  => ['admin']
];

// Ognuno di questi mestieri corrisponde a un tipo di oggetto
switch ($_SESSION['mestiere']) {
  case 3: $mestiere = 8; break; // Magic
  case 4: $mestiere = 9; break; // Secret
  case 1: $mestiere = 10; break; // ICC
  default: $mestiere = -1; break; // Nessun mestiere
}

// filtri
$filtro = (isset($_POST['filtro']) ? $_POST['filtro'] : 'tutti');

switch($filtro) {
    case 'creati_da_me': $where = "WHERE creatore = '".$_SESSION['login']."'"; break;
    case 'tutti': $where = ''; break;
    default: $_POST[$filtro] != '' ? $where = "WHERE $filtro = '".$_POST[$filtro]."'" : '';
}

// FINE filtri
$oggetti = gdrcd_query("SELECT oggetto.*, codtipooggetto.descrizione AS desc_tipo
                        FROM oggetto
                        LEFT JOIN codtipooggetto ON oggetto.tipo = codtipooggetto.cod_tipo 
                        $where
                        ORDER BY nome ASC", 'result');
?>


<!-- Top bar -->
<div class="topbar">
    <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
    <!-- Icona help che mostra la legenda sull'hover -->
    <div class="help-icon" title="Legenda azioni">
        <i class="fa-solid fa-circle-question"></i>
        <div class="legend-compact tooltip">
            <div><i class="fa-solid fa-pen-to-square"></i><span>Modifica</span></div>
            <div><i class="fa-solid fa-trash"></i><span>Cancella</span></div>
            <div><i class="fa-solid fa-user-plus"></i><span>Assegna</span></div>
            <div><i class="fa-solid fa-check-circle"></i><span>Approva</span></div>
        </div>
    </div>
    <!-- Filtri -->
    <form method="post" class="filter-form" action="main.php?page=gestione_oggetti">
        <label><input type="radio" name="filtro" value="tutti" <?= $filtro=='tutti'?'checked':'' ?> onchange="this.form.submit()">Tutti</label>
        <label><input type="radio" name="filtro" value="creati_da_me" <?= $filtro=='creati_da_me'?'checked':'' ?> onchange="this.form.submit()">Creati da me</label>
        <input id="filtroTipoObj" type="radio" name="filtro" value="" onchange="this.form.submit()" <?= $filtro=='tipo'?'checked':'' ?> style="display:none;">
        <input id="filtroCategoriaObj" type="radio" name="filtro" value="" onchange="this.form.submit()" <?= $filtro=='categoria'?'checked':'' ?> style="display:none;">
        <?php
            // Per tipo
            $tipi = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione ASC", 'result');

            echo '<select name="tipo" id="selectTipoObj"><option value="">Tutti</option>';
            while ($row = gdrcd_query($tipi, 'fetch')) { echo '<option value="' . $row['cod_tipo'] . '" '.($_POST[$filtro]==$row['cod_tipo']?'selected':'').'>' . htmlspecialchars($row['descrizione']) . '</option>'; }
            echo '</select>';

            // Per categoria
            $tipi = gdrcd_query("SELECT DISTINCT categoria FROM oggetto ORDER BY categoria ASC", 'result');

            echo '<select name="categoria" id="selectCategoriaObj"><option value="">Tutti</option>';
            while ($row = gdrcd_query($tipi, 'fetch')) { echo '<option '.($_POST[$filtro]==htmlspecialchars($row['categoria'])?'selected':'').'>' . htmlspecialchars($row['categoria']) . '</option>'; }
            echo '</select>';
        ?>
    </form>
    <?php if (hasPermesso($_SESSION, $azioni_permessi['crea'])): ?>
	    <a href="javascript:apriModaleCreazioneObj()" class="back">➕ Nuovo</a> <!-- ELIMINARE: main.php?page=oggetto_aggiungi e main.php?page=gestione_mercato -->
    <?php endif; ?>
</div>

<!-- Lista oggetti -->
<div id="item-list" class="item-list">
  <div class="ct-table-responsive">
    <table>
      <tr>
        <!-- <th>ID</th> -->
        <th>Immagine</th>
        <th>Nome</th>
        <th>Descrizione</th>
        <th>Tipo</th>
        <th>Categoria</th>
        <th>Azioni</th>
      </tr>
      <?php foreach ($oggetti as $obj): ?>
      <tr>
        <td width="5%" data-label="Immagine"><img width="25" height="25" src="imgs/items/<?=$obj['urlimg']?>" alt="Immagine"/></td>
        <td><?= htmlspecialchars($obj['nome']) ?></td>
        <td><?= mb_substr(htmlspecialchars($obj['descrizione']), 0, 120, "UTF-8").'...' ?></td>
        <td><?= htmlspecialchars($obj['desc_tipo']) ?></td>
        <td><?= htmlspecialchars($obj['categoria']) ?></td>
        <!-- Colonna Azioni -->
        <td class="azioni">
          <?php if (hasPermesso($_SESSION, $azioni_permessi['modifica']) || $mestiere == $obj['tipo']): ?>
            <button type="button" class="btn-action" title="Modifica" onclick="apriModaleModificaObj(<?= $obj['id_oggetto'] ?>)"><i class="fa-solid fa-pen-to-square"></i></button>
          <?php endif; ?>

          <?php if (hasPermesso($_SESSION, $azioni_permessi['cancella']) || $mestiere == $obj['tipo']): ?>
            <button type="submit" class="btn-action" title="Elimina" onclick="deleteObj(<?=$obj['id_oggetto']?>)"><i class="fa-solid fa-trash"></i></button>
          <?php endif; ?>
          <?php if (hasPermesso($_SESSION, $azioni_permessi['assegna']) || $obj['descrizione'] == $_SESSION['login'] || $mestiere == $obj['tipo']): ?>
            <form action="main.php?page=oggetto_assegna" method="POST">
              <button type="submit" class="btn-action" title="Assegna"><i class="fa-solid fa-user-plus"></i></button>
            </form>
          <?php endif; ?>

          <?php if (hasPermesso($_SESSION, $azioni_permessi['approva']) && $obj['richiesto'] == 2): ?>
            <form action="main.php?page=oggetto_approvazione" method="POST">
              <button type="submit" class="btn-action" title="Approva"><i class="fa-solid fa-check-circle"></i></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<!-- Form di creazione e di modifica -->
<div class="pg-edit-container" role="dialog" aria-modal="true" id="oggettoModal">
    <div class="modal-content">
        <span class="close" id="closeObj">&times;</span>
        <h3 id="modalTitleObj">Gestione Oggetto</h3>
        <form id="oggettoForm" enctype="multipart/form-data">
            <input type="hidden" name="id_oggetto" id="id_oggetto" value="">
            <!-- Campi Base -->
            <div class="form-group">
                <label for="nome">Nome Oggetto *</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="descrizione">Descrizione *</label>
                <textarea name="descrizione" id="descrizione" rows="5" required></textarea>
            </div>
            <!-- Immagine con preview -->
			<div class="form-row">
				<div class="form-group form-column">
					<label for="immagine">Immagine</label>
					<div id="imagePreviewObj" style="margin-bottom: 10px; display: none;">
						<img id="previewImgObj" src="" alt="Preview" style="max-width: 100px; max-height: 100px;">
					</div>
				</div>
				<div class="form-group form-column">
					<input type="file" name="img_oggetto" id="img_oggetto" accept="image/*">
				</div>
			</div>
            <div class="form-group">
                <label for="ubicabile">Posizionabile in</label>
                <select name="ubicabile" id="ubicabile">
                    <?php
                    $posizioni = [
                        0 => 'Non trasportabile', 1 => 'Equipaggia', 2 => 'Mano Dx', 3 => 'Mano Sx',
                        4 => 'Torso', 5 => 'Gambe', 6 => 'Piedi', 7 => 'Testa',
                        8 => 'Anello', 9 => 'Collo'
                    ];

                    foreach ($posizioni as $val => $label) {
                        echo "<option value='$val'>$label</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Categoria (modificabile solo in creazione) -->
            <div class="form-group">
                <label for="categoria">Categoria *</label>
                <select name="categoria" id="categoriaObj" required>
                    <option value="">Seleziona categoria</option>
                    <option value="standard">Standard</option>
                    <option value="arma">Arma</option>
                    <option value="curativo">Curativo</option>
                    <option value="statistica">Statistica</option>
                    <option value="magico">Magico</option>
                </select>
            </div>

            <!-- Tipo Oggetto (dinamico) -->
            <div class="form-group">
                <label for="tipo">Tipo Oggetto *</label>
                <select name="tipo" id="tipo" required>
                    <option value="">Seleziona tipo</option>
                </select>
            </div>

            <!-- SEZIONI DINAMICHE -->

            <!-- ARMA -->
            <div id="sezione_arma" class="sezione-dinamica-obj" style="display: none;">
                <div class="form-group">
                    <label for="tipo_arma">Tipo di arma</label>
                    <select name="tipo_arma">
                        <option value="1">Arma bianca</option>
                        <option value="2">Arma da lancio</option>
                        <option value="3">Arma da fuoco</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bonus_arma">Bonus arma</label>
                    <select name="bonus_arma">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ricarica_massima">Ricarica massima</label>
                    <select name="ricarica_massima">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <!-- CURATIVO -->
            <div id="sezione_curativo" class="sezione-dinamica-obj" style="display: none;">
                <div class="form-group">
                    <label for="salute_integra">Salute integra</label>
                    <select name="salute_integra">
                        <?php for ($i = 0; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="integrita_integra">Integrità integra</label>
                    <select name="integrita_integra">
                        <?php for ($i = 0; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cariche_curativo">Cariche iniziali</label>
                    <select name="cariche_curativo">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <!-- STATISTICA -->
            <div id="sezione_statistica" class="sezione-dinamica-obj" style="display: none;">
                <div class="form-group">
                    <label for="temp_giorni">Durata (giorni)</label>
                    <select name="temp_giorni">
                        <?php for ($i = 1; $i <= 30; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php 
                $bonus_extra = [
                    'bonus_car1_extra' => 'Forza',
                    'bonus_car2_extra' => 'Destrezza', 
                    'bonus_car3_extra' => 'Mente',
                    'bonus_car4_extra' => 'Tempra',
                    'bonus_car5_extra' => 'Potere'
                ];

                foreach ($bonus_extra as $campo => $nome): ?>
                <div class="form-group">
                    <label for="<?= $campo ?>">Bonus <?= $nome ?></label>
                    <select name="<?= $campo ?>">
                        <?php for ($i = -10; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i > 0 ? '+' : '' ?><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <label for="cariche_statistica">Cariche iniziali</label>
                    <select name="cariche_statistica">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ricarica_massima_statistica">Ricarica massima</label>
                    <select name="ricarica_massima_statistica">
                        <?php for ($i = 0; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <!-- MAGICO -->
            <div id="sezione_magico" class="sezione-dinamica-obj" style="display: none;">
                <div class="form-group">
                    <label for="cariche_magico">Cariche iniziali</label>
                    <select name="cariche_magico">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ricarica_massima_magico">Ricarica massima</label>
                    <select name="ricarica_massima_magico">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <!-- STANDARD -->
            <div id="sezione_standard" class="sezione-dinamica-obj" style="display: none;">
                <div class="form-group">
                    <label for="cariche_standard">Utilizzo</label>
                    <select name="cariche_standard">
                        <option value="illimitato">Illimitato</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Salva</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('oggettoModal').style.display = 'none';">Annulla</button>
            </div>
        </form>
    </div>
</div>
<!-- FINE Form di creazione e di modifica -->