<?php
add_script("/includes/oggetto.js");

// Recupero i dati del personaggio
$pg = gdrcd_query("SELECT * FROM personaggio WHERE nome = '".$_SESSION['login']."'");
$budget = $pg['soldi'];

// Filtri
$where = (isset($_POST['tipo']) && $_POST['tipo'] != '' ? "WHERE tipo = ".$_POST['tipo'] : '');
// FINE filtri

// Recupero gli oggetti in vendita nel mercato
$result = gdrcd_query("SELECT mercato.numero, oggetto.* FROM oggetto JOIN mercato ON oggetto.id_oggetto = mercato.id_oggetto $where ORDER BY nome", 'result');
$numresults = gdrcd_query($result, 'num_rows');
?>

<!-- Top bar -->
<div class="topbar">
    <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
    <!-- Filtri -->
	<form method="post" class="filter-form" action="main.php?page=servizi_mercato">
		<?php
			// Per tipo
			$tipi = gdrcd_query("SELECT * FROM codtipooggetto ORDER BY descrizione ASC", 'result');
			echo '<select name="tipo" id="selectTipoObj" class="form-select" onchange="this.form.submit()"><option value="">Tutti</option>';
			while ($row = gdrcd_query($tipi, 'fetch')) { echo '<option value="' . $row['cod_tipo'] . '" '.($_POST['tipo']==$row['cod_tipo']?'selected':'').'>' . htmlspecialchars($row['descrizione']) . '</option>'; }
			echo '</select>';
		?>
		<input type="text" class="searchField" id="searchShop" placeholder="Cerca...">
	</form>
</div>

<?php if($numresults > 0): ?>
<div class="lista-prodotti" id="shop">
	<?php
	while($row = gdrcd_query($result, 'fetch')) {
		$obj_posseduti = gdrcd_query("SELECT * FROM clgpersonaggiooggetto WHERE nome = '".$_SESSION['login']."' AND id_oggetto = ".$row['id_oggetto'])['numero'];
		// Magic ha sconto del 30%
		$costo = $pg['id_mestiere'] == 3 ? (($row['costo'] * 70) / 100) : $row['costo'];
		$maxQty = ($budget / $costo) > 0 ? floor($budget / $costo) : 0; // Calcolo la quantità massima di oggetti acquistabili
	?>
	<div class="prodotto" id="prodotto-<?= $row['id'] ?>">
		<img src="imgs/items/<?=gdrcd_filter('out', $row['urlimg'])?>" alt="<?=gdrcd_filter('out', $row['nome'])?>">
		<div class="contenuto">
			<h3><?=gdrcd_filter('out', $row['nome'])?></h3>
			<div class="prezzo"><?=$costo.' '.$PARAMETERS['names']['currency']['short']?></div>
			<p>
				<?=gdrcd_filter('out', $row['descrizione'])?>
				
				<?php if (!empty($row['categoria'])): ?>
					<b>Categoria:</b> <?=gdrcd_filter('out', $row['categoria'])?><br>
				<?php endif; ?>
				<?php if (isset($row['ricarica_massima']) && $row['ricarica_massima'] > 0): ?>
					<b>Ricarica massima:</b> <?=$row['ricarica_massima']?><br>
				<?php endif; ?>
				<?php if (isset($row['richiede_ricarica'])): ?>
					<b>Richiede ricarica:</b> <?=$row['richiede_ricarica'] ? 'Sì' : 'No'?><br>
				<?php endif; ?>
				<?php if ($row['cariche'] != 0): ?>
					<b>Utilizzi:</b> <?=($row['cariche'] == -1 || $row['cariche'] === 'illimitato') ? 'Illimitato' : $row['cariche']?><br>
				<?php endif; ?>
				<?php if ($row['isTemp'] == 1 && $row['temp_giorni'] > 0): ?>
					<b>Durata:</b> <?=$row['temp_giorni']?> giorni<br>
				<?php endif; ?>
				<?php if (!empty($row['tipo_arma'])): ?>
				<b>Tipo arma:</b> 
					<?php
					switch ($row['tipo_arma']) {
						case 1: echo 'Arma bianca'; break;
						case 2: echo 'Arma da lancio'; break;
						case 3: echo 'Arma da fuoco'; break;
						default: echo '—';
					}
					?>
				<?php endif; ?>
				<?php if (!empty($row['attacco']) && $row['attacco'] != 0): ?>
					<b>Bonus attacco:</b> <?=$row['attacco']?><br>
				<?php endif; ?>
				<?php if (!empty($row['salute']) && $row['salute'] != 0): ?>
					<b>Incremento salute:</b> <?=$row['salute']?><br>
				<?php endif; ?>
				<?php if (!empty($row['integrita']) && $row['integrita'] != 0): ?>
					<b>Incremento integrità:</b> <?=$row['integrita']?><br>
				<?php endif; ?>
				<?php
				$bonus = [
					'bonus_car1_extra' => 'Forza',
					'bonus_car2_extra' => 'Destrezza',
					'bonus_car3_extra' => 'Mente',
					'bonus_car4_extra' => 'Tempra',
					'bonus_car5_extra' => 'Potere'
				];
				foreach ($bonus as $campo => $etichetta) {
					if (isset($row[$campo]) && $row[$campo] != 0) {
						echo "<b>$etichetta:</b> {$row[$campo]}<br>";
					}
				}
				?>
			</p>
		</div>
		<div class="azioni">
			<!-- Sell button -->
			<?php if($obj_posseduti > 0): ?>
			<button class="btn-dettagli" onclick="sellObj('<?=$_SESSION['login']?>', <?=$row['id_oggetto']?>)">
				<span class="help-icon" title="Legenda azioni">
					<i class="fa-solid fa-circle-question"></i>
					<div class="legend-compact tooltip-sell">Quantità massima: <?=$obj_posseduti?> pezzi</div>
				</span>
				Vendi
			</button>
			<?php else: ?>
				<button class="btn-mercato-ko">Vendi</button>
			<?php endif; ?>
			<!-- Quantity input -->
			<input type="number" id="qty_<?=$row['id_oggetto']?>" min="1" placeholder="qty" style="width: 60px; padding: 5px; border-radius: 6px; border: 1px solid #ccc; text-align: center;">
			<!-- Buy button -->
			<?php if ($row['tipo'] == 8): ?>
				<button class="btn-mercato-ko">
					<span class="help-icon" title="Legenda azioni">
						<i class="fa-solid fa-circle-question"></i>
						<div class="legend-compact tooltip-buy">Oggetto del magic acquistabile solamente tramite giocata</div>
					</span>
					Magic
				</button>
			<?php elseif($budget < $costo): ?>
				<button class="btn-mercato-ko">Preleva</button>
			<?php else: ?>
				<button class="btn-compra-ok" onclick="buyObj('<?=$_SESSION['login']?>', <?=$row['id_oggetto']?>, <?=$costo?>, <?=$budget?>)">
					<span class="help-icon" title="Legenda azioni">
						<i class="fa-solid fa-circle-question"></i>
						<div class="legend-compact tooltip-buy">Quantità massima: <?=$maxQty?> pezzi</div>
					</span>
					<?=gdrcd_filter('out', $MESSAGE['interface']['market']['buy'])?>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<?php } ?>
</div>
<?php endif; ?>