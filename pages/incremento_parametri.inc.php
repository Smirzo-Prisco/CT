<?php add_script("/includes/incremento_parametri.js"); ?>

<link rel="stylesheet" href="../themes/crystal/incremento_parametri.css">
<style>
  /* ICONA */
  .help-animated {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 18px; /* Più piccolo per testo */
      height: 18px;
      background: linear-gradient(135deg, #3a6ea5, #2a5685);
      color: white;
      border-radius: 50%;
      cursor: help;
      font-weight: bold;
      font-size: 11px;
      box-shadow: 0 2px 4px rgba(58, 110, 165, 0.3);
      transition: all 0.3s;
      margin: 0 3px;
      vertical-align: middle;
      position: relative;
      z-index: 10;
  }

  /* TOOLTIP */
  .tooltip-animated {
      position: absolute;
      top: 15%;
      left: 60%;
      transform: translateX(-50%) translateY(5px);
      background: #1a1a2e;
      color: #e0e0e0;
      padding: 10px 12px;
      border-radius: 6px;
      width: 180px; /* Larghezza fissa */
      font-size: 11px;
      line-height: 1.2;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
      border: 1px solid #3a6ea5;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
      z-index: 1000;
      /* Non selezionabile */
      user-select: none;
      pointer-events: none;
  }

  /* FRECCETTA */
  .tooltip-animated::after {
      text-align: center;
      content: '';
      position: absolute;
      bottom: 100%; /* Sopra il tooltip */
      left: 50%;
      transform: translateX(-50%);
      border-width: 6px;
      border-style: solid;
      border-color: transparent transparent #3a6ea5 transparent;
  }

  /* MOSTRA IL TOOLTIP AL HOVER */
  .help-container:hover .tooltip-animated {
      opacity: 1;
      visibility: visible;
      transform: translateX(-50%) translateY(0);
      pointer-events: auto;
      transform: scale(1.2);
      box-shadow: 0 3px 8px rgba(58, 110, 165, 0.5);
  }
</style>

<div id="stats_panel" aria-live="polite" style="position:relative;">
	<h1>Pannello Assegnazione Punti
    <span class="help-container" title="Legenda livelli">
      <span class="help-animated">?</span>
      <div id="tooltip_soglie" class="tooltip-animated"></div>
    </span>
  </h1>
	<div class="sub">
		<p>Gestisci le caratteristiche usando Esperienza e Shin</p>
		<p>Ricorda che, una volta messi, i punti non possono essere recuperati.</p>
		<p>Fai salire il livello di <?=$_SESSION['login']?> aumentando le sue caratteristiche</p>
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
      <div class="total-label" style="color:var(--xp);font-weight:700;">Esperienza disponibile</div>
      <div style="font-weight:700; font-size:20px; margin-top:6px" id="xpDisponibili">0</div>
    </div>
    <div class="col total-card">
      <div class="total-label" style="color:var(--shin);font-weight:700;">Shin disponibili</div>
      <div style="font-weight:700; font-size:20px; margin-top:6px" id="shinDisponibili">0</div>
    </div>
  </div>

  <!-- attributes container -->
  <div class="attributes" id="attributesContainer" aria-live="polite"></div>

  <!-- save -->
  <div class="save-area"><button id="saveBtn" onclick="savePuntiPg(this);">💾 Salva modifiche</button></div>
</div>