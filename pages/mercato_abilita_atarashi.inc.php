
<?php add_script("/includes/mercato_abilita.js"); ?>



<div id="skillPanel">
    <div class="sticky-header">
        <div class="skill-info-bar">
            <div>Punti Shin: <span id="skillPoints">0</span></div>
            <div>
                Ogni livello di un'abilità richiede la spesa di un punto shin.
                <br>
                Il livello di un'abilità non può essere superiore al livello del personaggio
            </div>
            <div>Livello PG: <span id="playerLevel">0</span></div>
        </div>
        <div class="save-bar">
            <button id="btnSave" class="btn-save" onclick="saveSkill();">💾 Salva Skill</button>
            <button id="btnReset" class="btn-save" onclick="resetSkills();" style="display:none;">Reset</button>
        </div>
    </div>
    <div class="skill-accordion">
        <div class="accordion-item">
            <button class="accordion-header">🛡️ Default / Difensiva</button>
            <div class="accordion-body" data-cat="Default"></div>
        </div>
        <div class="accordion-item">
            <button class="accordion-header">✨ Speciale</button>
            <div class="accordion-body" data-cat="Speciale"></div>
        </div>
        <div class="accordion-item">
            <button class="accordion-header">📘 Generica</button>
            <div class="accordion-body" data-cat="Generica"></div>
        </div>
        <div class="accordion-item">
            <button class="accordion-header">🗡️ Attacco</button>
            <div class="accordion-body" data-cat="Attacco"></div>
        </div>
        <div class="accordion-item">
            <button class="accordion-header">🧠 Mentale</button>
            <div class="accordion-body" data-cat="Mentale"></div>
        </div>
    </div>
</div>
<div id="skill-modal" class="skill-modal">
    <div class="skill-modal-content">
        <h2 id="skill-modal-titolo"></h2>
        <div id="skill-modal-descrizione"></div>
        <button id="close-modal">Chiudi</button>
    </div>
</div>