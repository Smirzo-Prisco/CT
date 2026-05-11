
<?php add_script("/includes/mercato_abilita.js"); ?>

<style>
    #skillPanel {
        font-family: inherit;
        color: white;
    }

    /* ---- ACCORDION ---- */

    #skillPanel .skill-accordion {
        width: 100%;
        margin-top: 20px;
    }

    #skillPanel .accordion-item {
        margin-bottom: 10px;
        border-radius: 8px;
        overflow: hidden;
        background: #1c2033;
        border: 1px solid #2f3550;
    }

    #skillPanel .accordion-header {
        width: 100%;
        padding: 12px 16px;
        font-size: 16px;
        text-align: left;
        background: #181c31;
        color: #fff;
        border: none;
        cursor: pointer;
        outline: none;
    }

    #skillPanel .accordion-header:hover {
        background: #111423;
    }

    #skillPanel .accordion-body {
        display: none;
        background: #1c2033;
        padding: 10px 15px;
    }

    /* ---- SKILL CARD ---- */

    #skillPanel .skill-card {
        background: #111423;
        padding: 10px;
        margin: 8px 0;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        border: 1px solid #2f3550;
    }

    #skillPanel .skill-card strong {
        color: white;
    }

    #skillPanel .btn-up {
        background: #2f3550;
        border: none;
        color: white;
        padding: 6px 8px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
    }

    /* ---- PULSANTE SALVA ---- */

    #skillPanel .btn-save {
        background: #2f3550;
        color: white;
        border: 1px solid #fff;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        width: 100%;
        margin: 10px;
    }

    #skillPanel .skill-info-bar {
        background: #2f3550;
        border: 1px solid #111423;
        padding: 12px 16px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        font-size: 16px;
    }

    #skillPanel .skill-info-bar span {
        font-weight: bold;
        color: #00eaff;
    }

    #skillPanel .skill-row {
        display: grid;
        grid-template-columns: 25% 55% 20%;
        gap: 10px;
        align-items: center;

        background: rgba(255,255,255,0.06);
        padding: 10px 12px;
        margin-bottom: 8px;

        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
    }

    #skillPanel .skill-left {
        font-size: 14px;
        font-weight: bold;
    }

    #skillPanel .skill-center small {
        font-size: 13px;
        opacity: 0.8;
    }

    #skillPanel .skill-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    #skillPanel .skill-level {
        font-size: 14px;
        font-weight: bold;
        min-width: 20px;
        text-align: center;
    }

    #skillPanel .btn-up {
        background: #3b82f6;
        border: none;
        color: white;
        font-size: 14px;
        width: 26px;
        height: 26px;
        border-radius: 5px;
        cursor: pointer;
        transition: 0.2s;
    }

    #skillPanel .btn-up:hover {
        background: #2563eb;
    }

    /* Pulsante "+" disabilitato */
    #skillPanel .btn-up.disabled {
        background: #6b7280 !important;  /* grigio */
        cursor: not-allowed;
        opacity: 0.6;
    }

    #skillPanel .save-bar {
        background: rgba(17, 20, 35, 0.95); /* colore del tuo tema */
        padding: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.08);

        display: flex;
        justify-content: flex-end;
    }

    #skillPanel .sticky-header {
        position: sticky;
        top: 0;
        z-index: 20;
    }

    /* ---- MODALE DESCRIZIONE SKILL ---- */
    .skill-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.55);
        backdrop-filter: blur(4px);
        display: none; /* nascosta */
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    /* Quando attiva */
    .skill-modal.open {
        display: flex;
        opacity: 1;
    }

    /* Contenuto modale */
    .skill-modal-content {
        background: #ffffffcc; /* semi-trasparente */
        backdrop-filter: blur(8px);
        padding: 24px 32px;
        width: 90%;
        max-width: 520px;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transform: scale(0.85);
        animation: modal-pop 0.25s ease forwards;
    }

    /* Animazione ingresso */
    @keyframes modal-pop {
        from {
            opacity: 0;
            transform: scale(0.85);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Testo */
    #skill-modal-descrizione {
        font-size: 1rem;
        color: #333;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    /* Bottone */
    #close-modal {
        padding: 10px 18px;
        border: none;
        background: #4a6fff;
        color: white;
        font-size: 0.95rem;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.15s ease;
    }

    #close-modal:hover {
        background: #3956d4;
        transform: translateY(-2px);
    }

    #close-modal:active {
        transform: translateY(0);
    }
</style>

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