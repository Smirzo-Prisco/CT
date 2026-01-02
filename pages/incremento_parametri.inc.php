<?php add_script("/includes/incremento_parametri.js"); ?>

<link rel="stylesheet" href="../themes/crystal/incremento_parametri.css">

<div id="stats_panel" aria-live="polite">
	<h1>Pannello Assegnazione Punti</h1>
	<div class="sub">
		<p>Gestisci le caratteristiche usando Esperienza e Shin</p>
		<p>Ricorda che, una volta messi, i punti non possono essere recuperati.</p>
		<p>Fai salire il livello di <?=$_SESSION['login']?> aumentando le sue caratteristiche - 
			<span class="help-icon" title="Legenda azioni">Consulta la tabella<div id="tooltip_soglie" class="legend-compact tooltip-sell"></div></span>
		</p>
	</div>

  <!-- livello -->
  <div class="row">
    <div class="col total-card">
      <div style="font-size:13px; color:var(--muted)">Livello Personaggio</div>
      <div style="font-size:20px; font-weight:700; margin-top:6px" id="characterLevel"></div>
    </div>

    <div class="col total-card">
      <div style="font-size:13px; color:var(--muted)">Totale punti assegnati</div>
      <div class="total-bar-container" aria-hidden="true">
        <div class="total-bar-xp" id="totalBarXP"></div>
        <div class="total-bar-shin" id="totalBarShin"></div>
        <div class="total-bar-label" id="levelTotalLabel">0 punti</div>
      </div>
    </div>
  </div>

  <!-- available totals -->
  <div class="row">
    <div class="col total-card">
      <div class="total-label" style="color:var(--muted)">Esperienza disponibile</div>
      <div style="font-weight:700; font-size:20px; margin-top:6px" id="xpDisponibili">0</div>
    </div>
    <div class="col total-card">
      <div class="total-label" style="color:var(--muted)">Shin disponibili</div>
      <div style="font-weight:700; font-size:20px; margin-top:6px" id="shinDisponibili">0</div>
    </div>
  </div>

  <!-- attributes container -->
  <div class="attributes" id="attributesContainer" aria-live="polite"></div>

  <!-- save -->
  <div class="save-area"><button id="saveBtn" onclick="savePuntiPg(this);">💾 Salva modifiche</button></div>
</div>