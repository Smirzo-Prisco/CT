
<?php // add_script("/includes/incremento_parametri.js"); ?>

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
            <div>Ogni livello di un'abilità richiede la spesa di un punto shin</div>
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

<script>
    let skillPoints = 0;
    let playerLevel = 0;
    let originalSkills = {};  // ← copia immutata dei valori iniziali
    let skillLevels = {};     // ← valori modificabili
    let maxSklLvl = {};     // ← valori modificabili
    let itCosts = {};     // ← valori modificabili

    async function saveSkill() {
        const changedSkills = {};
        let hasChanges = false;

        for (const id in skillLevels) {

            const oldLevel = originalSkills[id];
            const newLevel = skillLevels[id];

            if (oldLevel !== newLevel) {
                changedSkills[id] = {
                    old: oldLevel,
                    new: newLevel
                };
                hasChanges = true;
            }
        }

        if (!hasChanges) return showNotification('Non hai modificato nessuna skill.', 'error');

        console.log("Dati da salvare:", changedSkills);
        console.log("Shin:", skillPoints);

        // 🔥 INVIO AL SERVER
        await fetch("/pages/ajax_engine.php?op=saveSkillPg", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ skills: changedSkills, shin: skillPoints })
        });

        showNotification("Skill salvate!", "success");
    };

    async function loadSkills() {
        try {
            const playerLevelEl = document.getElementById("playerLevel");
            const skillPointsEl = document.getElementById("skillPoints");
            const response = await fetch("/pages/ajax_engine.php?op=getSkillPg"); 
            if (!response.ok) throw new Error("Errore nella risposta del server");

            const data = await response.json();

            // 🔥 AGGIORNA le variabili
            skillPoints = data.shinDisponibili;
            playerLevel = data.livelloPg;
            
            // 🔥 AGGIORNA il DOM
            if(skillPointsEl) skillPointsEl.textContent = data.shinDisponibili;
            if(playerLevel) playerLevelEl.textContent = data.livelloPg;
            skillLevels = {};
            maxSklLvl = {};
            itCosts = {};
            
            data.skills.forEach(s => {
                originalSkills[s.id] = s.livello;  // 🔥 salva livelli originali
                skillLevels[s.id] = s.livello;    // livelli attuali modificabili
                maxSklLvl[s.id] = s.maxLivello; // livello massimo skill
                itCosts[s.id] = s.itCosts; // costo incremento skill
            });

            renderSkills(data.skills);
        } catch (err) {
            console.error("Errore:", err);
            showNotification('Impossibile recuperare le skill!', 'error');
        }
    }

    function wireSkillButtons() {
        document.querySelectorAll('.btn-up').forEach(btn => {
            btn.addEventListener('click', () => {
                const skill = btn.dataset.skill;
                const currentLevel = skillLevels[skill];
                const nextLevel = currentLevel + 1;
                const skillPointsEl = document.getElementById("skillPoints");

                // 💰 il costo è il livello che si raggiunge
                const cost = itCosts[skill] ? 1 : 0;

                // ❌ punti insufficienti
                if (skillPoints < cost) {
                    return showNotification(
                        `Ti servono ${cost} punti shin per aumentare questa skill.`,
                        'error'
                    );
                }
                
                // ❌ non si può superare livello del PG
                if (nextLevel > playerLevel) {
                    return showNotification(
                        'Non puoi superare il livello del tuo personaggio!',
                        'error'
                    );
                }

                // ❌ non si può superare livello massimo della skill
                if (nextLevel > maxSklLvl[skill]) {
                    return showNotification(
                        'Non puoi superare il livello massimo consentito dalla skill!',
                        'error'
                    );
                }

                // ✔️ aggiorno i valori
                skillLevels[skill] = nextLevel;
                skillPoints -= cost;

                // Aggiorno gli shin disponibili
                if(skillPointsEl) skillPointsEl.textContent = skillPoints;

                // aggiorno livello skill
                btn.parentElement.querySelector('.skill-level').textContent = nextLevel+'/' + maxSklLvl[skill];

                // Controllo modifiche per abilitare/disabilitare il pulsante di reset
                checkForChanges();
            });
        });
    }

    // APRI/CHIUDI ACCORDION
    document.querySelectorAll(".accordion-header").forEach(btn => {
        btn.addEventListener("click", () => {
            const body = btn.nextElementSibling;
            body.style.display = body.style.display === "block" ? "none" : "block";
        });
    });

    function renderSkills(skillList) {
        const bodies = document.querySelectorAll(".accordion-body");

        // Pulizia
        bodies.forEach(b => b.innerHTML = "");

        skillList.forEach(skill => {
            const container = document.querySelector(`.accordion-body[data-cat='${skill.categoria}']`);
            if (!container) return;

            let locked = skill.locked ? 'disabled' : '';
                
            container.innerHTML += `
                <div class="skill-row" data-descrizione="${skill.descrizione ?? '<em>Nessuna descrizione</em>'}" data-titolo="${skill.nome}">
                    <div class="skill-left"><strong>${skill.nome}</strong></div>
                    <div class="skill-center truncate-text"><small>${skill.descrizione ?? "Nessuna descrizione"}</small></div>
                    <div class="skill-right">
                        <span class="skill-level">${skill.livello}/${skill.maxLivello}</span>
                        <button class="btn-up ${locked}" data-skill="${skill.id}" ${locked}>+</button>
                    </div>
                </div>
            `;
        });

        wireSkillButtons();
    }

    // Abilito o disabilito il pulsante reset
    function checkForChanges() {
        for (const id in skillLevels) {
            if (skillLevels[id] !== originalSkills[id]) {
                document.getElementById("btnReset").style.display = "inline-block";
                return;
            }
        }
        // Nessuna modifica
        document.getElementById("btnReset").style.display = "none";
    }

    function resetSkills() {
        if (!confirm("Vuoi davvero annullare tutte le modifiche non salvate?")) return;

        let refund = 0;

        // 1️⃣ Calcolo i punti da restituire PRIMA di resettare i livelli
        for (const id in skillLevels) {
            const oldL = originalSkills[id];
            const newL = skillLevels[id];

            if (newL > oldL) {
                // costo cumulativo per ogni livello aumentato
                for (let lvl = oldL + 1; lvl <= newL; lvl++) {
                    refund += lvl;
                }
            }
        }

        // 2️⃣ Ripristino i livelli originali
        for (const id in originalSkills) {
            skillLevels[id] = originalSkills[id];
        }

        // 3️⃣ Restituisco i punti shin
        skillPoints += refund;
        document.getElementById("skillPoints").textContent = skillPoints;

        // 4️⃣ Aggiorno i livelli nella UI direttamente
        document.querySelectorAll(".skill-row").forEach(row => {
            const btn = row.querySelector(".btn-up");
            const id = btn.dataset.skill;
            row.querySelector(".skill-level").textContent = originalSkills[id];
        });

        // 5️⃣ Nascondo il pulsante reset
        document.getElementById("btnReset").style.display = "none";

        showNotification("Modifiche annullate!", "success");
    }

    document.addEventListener("click", function(e) {
        const row = e.target.closest(".skill-row");
        if (!row) return;

        if (e.target.classList.contains("btn-up")) return;

        const descrizione = row.dataset.descrizione ?? "Nessuna descrizione";
        const titolo = row.dataset.titolo ?? "Nessun titolo";
        // Inserisco titolo e descrizione interpretando gli HTML
        document.getElementById("skill-modal-titolo").textContent = titolo;
        document.getElementById("skill-modal-descrizione").innerHTML = descrizione;

        document.getElementById("skill-modal").classList.add("open");
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById("close-modal").addEventListener("click", () => {
            document.getElementById("skill-modal").classList.remove("open");
        });

        document.getElementById("close-modal").onclick = () =>
        document.getElementById("skill-modal").classList.remove("open");

        loadSkills(); // chiamata all'avvio
    });
</script>