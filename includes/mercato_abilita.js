const ns_abilita = {
    api_file: 'pages/api_personaggio.php',
    param: 'op'
};

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
    await fetch(ns_abilita.api_file + '?' + ns_abilita.param + '=saveSkillPg', {
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
        const response = await fetch(ns_abilita.api_file + '?' + ns_abilita.param + '=getSkillPg');
        if (!response.ok) throw new Error("Errore nella risposta del server");

        const data = await response.json();

        // 🔥 AGGIORNA le variabili
        skillPoints = data.shinDisponibili;
        playerLevel = data.livelloPg;

        // 🔥 AGGIORNA il DOM
        if (skillPointsEl) skillPointsEl.textContent = data.shinDisponibili;
        if (playerLevelEl) playerLevelEl.textContent = data.livelloPg;
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
            if (skillPointsEl) skillPointsEl.textContent = skillPoints;

            // aggiorno livello skill
            btn.parentElement.querySelector('.skill-level').textContent = nextLevel + '/' + maxSklLvl[skill];

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
    // Specchio esatto della logica di deducazione: 1 shin per level-up, solo se itCosts è true
    for (const id in skillLevels) {
        const oldL = originalSkills[id];
        const newL = skillLevels[id];

        if (newL > oldL && itCosts[id]) {
            refund += (newL - oldL);
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
        row.querySelector(".skill-level").textContent = originalSkills[id] + '/' + maxSklLvl[id];
    });

    // 5️⃣ Nascondo il pulsante reset
    document.getElementById("btnReset").style.display = "none";

    showNotification("Modifiche annullate!", "success");
}

document.addEventListener("click", function (e) {
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

// Lo script è caricato dinamicamente dopo il mount React: il DOM è già pronto,
// DOMContentLoaded non scatterà mai → chiamo direttamente senza attendere l'evento.
document.getElementById("close-modal")?.addEventListener("click", () =>
    document.getElementById("skill-modal").classList.remove("open")
);
loadSkills();