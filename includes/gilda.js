const ns_gilda = {
    api_file: 'pages/api_gilda.php',
    param: 'op'
};

// GILDE
function toggleAccordion(gildaId, type) {
    // Previeni la propagazione dell'evento per evitare conflitti
    event.stopPropagation();

    const content = document.getElementById('content-' + type + '-' + gildaId);

    // Se stiamo APRIENDO questa sezione
    if (!content.classList.contains('active')) {
        // Chiudi TUTTI i contenuti delle gilde
        const allContents = document.querySelectorAll('.guild-content.active');
        allContents.forEach(contentElem => { contentElem.classList.remove('active'); });
    }

    // Toggle del contenuto cliccato
    content.classList.toggle('active');
}

// Gestione Modal
function openGuildModal(tipo, id = null) {
    document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'block';

    if (id) {
        resetForm(tipo);

        if (tipo === 'role') document.getElementById('guild_role_id').value = id;
    }
}
function closeGuildModal(tipo) {
    document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'none';
}
function resetForm(tipo) {
    document.getElementById('form' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).reset();
    document.getElementById(tipo + '_id').value = '';
}
function editGuild(id) {
    fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=getGuild&id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('guild_id').value = data.id_gilda;
            document.getElementById('nome').value = data.nome;
            document.getElementById('tipo').value = data.tipo;
            document.getElementById('previewImg').src = 'imgs/guilds/' + data.immagine;
            document.getElementById('imagePreview').style.display = 'block';
            document.getElementById('visibile').checked = data.visibile == '1' ? true : false;
            document.getElementById('modalGildaTitle').textContent = 'Modifica Gilda';
            openGuildModal('guild');
        });
}
function deleteGuild(id) {
    if (confirm('Sei sicuro di voler eliminare questa gilda e tutti i suoi livelli?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=deleteGuild&id=' + id, { method: 'DELETE' })
            .then(response => { if (response.ok) location.reload(); });
    }
}
function saveGuild() {
    const formGuild = document.getElementById("formGuild");

    if (formGuild) {
        formGuild.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            /*
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            //*/

            fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=saveGuild', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        window.location.reload();
                    } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nel salvataggio', 'error');
                });
        });
    }
}
function removeGuildPg(nome) {
    if (confirm('Sei sicuro di voler rimuovere ' + nome + ' dalla gilda?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=removeGuildPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nome })
        })
            .then(response => { });//if (response.ok) location.reload(); });
    }
}
function addGuildPg(id_gilda) {
    const nome = document.getElementById('addGuildPg' + id_gilda).value;

    if (confirm('Inserire ' + nome + ' in questa gilda?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=addGuildPg', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nome: nome,
                id_gilda: id_gilda
            })
        })
            .then(response => { if (response.ok) location.reload(); });
    }
}
//  RUOLI
function editRole(id) {
    fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=getRole&id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('role_id').value = data.id_ruolo;
            document.getElementById('guild_role_id').value = data.gilda;
            document.getElementById('nome_ruolo').value = data.nome_ruolo;
            document.getElementById('previewImg').src = 'imgs/guilds/' + data.immagine;
            document.getElementById('imagePreview').style.display = 'block';
            document.getElementById('livello').value = data.livello;
            document.getElementById('capo').checked = data.capo == '1' ? true : false;
            document.getElementById('modalRuoloTitle').textContent = 'Modifica Ruolo';
            openGuildModal('role');
        });
}
function deleteRole(id) {
    if (confirm('Sei sicuro di voler eliminare questo ruolo?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=deleteRole&id=' + id)
            .then(response => { if (response.ok) location.reload(); });
    }
}
function saveRole() {
    const formRole = document.getElementById("formRole");

    if (formRole) {
        formRole.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=saveRole', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        window.location.reload();
                    } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nel salvataggio', 'error');
                });
        });
    }
}

// SOGLIE
function deleteSoglia(id) {
    if (confirm('Sei sicuro di voler eliminare questa soglia?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=deleteSoglia&id=' + id)
            .then(response => { if (response.ok) location.reload(); });
    }
}
function saveSoglia(id_soglia) {
    const livello_soglia = document.getElementById('livello_soglia' + id_soglia).value;
    const soglia = document.getElementById('soglia' + id_soglia).value;
    const danno = document.getElementById('danno' + id_soglia).value;
    const integrita = document.getElementById('integrita' + id_soglia).value;

    fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=saveSoglia', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_soglia, livello_soglia, soglia, danno, integrita })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // window.location.reload();
                // Chiudo modale
                document.getElementById('modalSoglia').style.display = 'none';
            } else showNotification(data.message, 'error');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Errore nel salvataggio', 'error');
        });
}

// STATUTO
function editVoceStatuto(id) {
    fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=getVoceStatuto&id=' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('articolo').value = data.articolo;
            document.getElementById('titolo_voce_statuto').value = data.titolo;
            document.getElementById('testo_voce_statuto').value = data.testo;
            document.getElementById('modalStatutoTitle').textContent = 'Modifica Statuto';
            openGuildModal('statuto');
        });
}
function deleteVoceStatuto(id) {
    if (confirm('Sei sicuro di voler eliminare questa voce?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=deleteVoceStatuto&id=' + id)
            .then(response => { if (response.ok) location.reload(); });
    }
}
function saveVoceStatuto() {
    const formRole = document.getElementById("formStatuto");

    if (formRole) {
        formRole.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=saveVoceStatuto', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        window.location.reload();
                    } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nel salvataggio', 'error');
                });
        });
    }
}

// ABILITA'/SKILL
function editSkill(id) {
    fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=getSkill&id=' + id)
        .then(response => response.json())
        .then(data => {
            // Sottotipo
            let sottotipo = document.getElementById(data.sottotipo);
            if (sottotipo) sottotipo.checked = true;
            // FINE sottotipo

            document.getElementById('id_abilita').value = data.id_abilita;
            document.getElementById('nome_skill').value = data.nome;
            document.getElementById('descrizione_skill').value = data.descrizione;
            document.getElementById('car').value = data.car;
            document.getElementById('tipo_skill').value = data.tipo;
            document.getElementById('id_gilda').value = data.id_gilda;
            document.getElementById('livello_sblocco').value = data.livello_sblocco;
            document.getElementById('modalSkillTitle').textContent = 'Modifica Abilità';
            openGuildModal('skill');
            checkSkillType(); // Se generica, mostra le opzioni avanzate
        });
}
function deleteSkill(id) {
    if (confirm('Sei sicuro di voler eliminare questa abilità?')) {
        fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=deleteSkill&id=' + id)
            .then(response => { if (response.ok) location.reload(); });
    }
}
function saveSkill() {
    const formRole = document.getElementById("formSkill");

    if (formRole) {
        formRole.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            // Sottotipo
            const sottotipo = document.querySelector('input[name="sottotipo"]:checked');
            formData.set('sottotipo', sottotipo ? sottotipo.value : '');
            // FINE sottotipo

            /*
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            return;
            //*/

            fetch(ns_gilda.api_file + '?' + ns_gilda.param + '=saveSkill', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        // window.location.reload();
                        // Chiudo modale
                        document.getElementById('modalSkill').style.display = 'none';
                    } else showNotification('Errore nel salvataggio: ' + data.message, 'error');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Errore nel salvataggio', 'error');
                });
        });
    }
}

// Preview immagine
const imgGuild = document.getElementById("immagine");
if (imgGuild) {
    document.getElementById('immagine').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
}

// Funzione per controllare il tipo di skill selezionato
function checkSkillType() {
    const tipoSkill = document.getElementById('tipo_skill');

    // Tipi di skill generiche
    const genericaSection = document.getElementById('genericaSection');
    const tipiGeneriche = ['Generica base', 'Generica avanzata'];
    if (tipiGeneriche.includes(tipoSkill.value)) genericaSection.style.display = 'block';
    else genericaSection.style.display = 'none';

    // Tipi di skill mentali
    const mentaleSection = document.getElementById('mentaleSection');
    const tipiMentali = ['Mentale base', 'Mentale media', 'Mentale avanzata', 'Mentale di attacco'];
    if (tipiMentali.includes(tipoSkill.value)) mentaleSection.style.display = 'block';
    else mentaleSection.style.display = 'none';

}

// Inizializza l'event listener quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function () {
    const tipoSkill = document.getElementById('tipo_skill');
    // Aggiungi listener per cambiamenti
    if (tipoSkill) tipoSkill.addEventListener('change', checkSkillType);
});
/***********    FINE    GILDE  *********************/