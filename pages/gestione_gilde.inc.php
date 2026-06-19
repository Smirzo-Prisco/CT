<div class="pagina_gestione_gilde">
<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

if($_SESSION['admin'] != 1) {
    echo '<div class="error">'.gdrcd_filter('out', $MESSAGE['error']['not_allowed']).'</div>';
} else {
    $gilde = gdrcd_query("SELECT gilda.*, codtipogilda.* FROM gilda LEFT JOIN codtipogilda ON gilda.tipo = codtipogilda.cod_tipo ORDER BY nome", 'result');

    add_script("/includes/gilda.js");
?>

<div class="guild-container">
    <!-- Top bar -->
    <div class="topbar">
        <a href="javascript:history.back()" class="back">⬅️ Indietro</a>
        <!-- Bottone a destra -->
        <button class="btn btn-sm btn-primary" onclick="openGuildModal('soglia', <?=$gilda['id_gilda']?>)"><i class="fa-solid fa-pencil"></i>&nbsp;&nbsp;Soglie livelli</button>
        <button class="btn btn-sm btn-primary" onclick="openGuildModal('guild', <?=$gilda['id_gilda']?>)"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Nuova Gilda</button>
        <button class="btn btn-sm btn-primary" onclick="openGuildModal('skill', <?=$gilda['id_gilda']?>)"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Nuova Abilità</button>
    </div>

    <!-- Lista -->
    <div class="item-list">
        <div class="ct-table-responsive">
        <?php foreach ($gilde as $gilda): ?>
            <div class="guild-accordion">
                <!-- Header Gilda -->
                <div class="guild-header">
                    <div class="guild-info">
                        <h3><?= htmlspecialchars($gilda['nome']) ?></h3>
                        <span class="guild-desc"><?=htmlspecialchars($gilda['descrizione'])?></span>
                    </div>
                    <div class="guild-actions">
                        <button class="btn btn-primary" title="Statuto" onclick="toggleAccordion(<?=$gilda['id_gilda']?>, 'statuto')"><i class="fa-solid fa-scroll"></i></button>
                        <button class="btn btn-primary" title="Skills" onclick="toggleAccordion(<?=$gilda['id_gilda']?>, 'skill')"><i class="fa-solid fa-star"></i></button>
                        <button class="btn btn-primary" title="Gradi" onclick="toggleAccordion(<?=$gilda['id_gilda']?>, 'role')"><i class="fa-solid fa-layer-group"></i></button>
                        <button class="btn btn-primary" title="Membri" onclick="toggleAccordion(<?=$gilda['id_gilda']?>, 'pg')"><i class="fa-solid fa-users"></i></button>
                        <button class="btn btn-edit" title="Modifica" onclick="editGuild(<?= $gilda['id_gilda'] ?>)"><i class="fa-solid fa-pencil"></i></button>
                        <button class="btn btn-delete" title="Elimina" onclick="deleteGuild(<?= $gilda['id_gilda'] ?>)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <!-- Contenuto Ruoli -->
                <div class="guild-content" id="content-role-<?=$gilda['id_gilda']?>">
                    <div class="guild-header">
                        <h4>👥 Ruoli della Gilda</h4>
                        <button class="btn btn-sm btn-primary" onclick="openGuildModal('role', <?=$gilda['id_gilda']?>)"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Nuovo Ruolo</button>
                    </div>
                    <?php
                    $ruoli = gdrcd_query("SELECT * FROM ruolo WHERE gilda = ".$gilda['id_gilda']." ORDER BY nome_ruolo", 'result');
                    foreach ($ruoli as $ruolo): ?>
                    <div class="guild-info">
                        <div width="5%" data-label="Simbolo ruolo"><img width="25" height="25" src="../imgs/guilds/<?=$ruolo['immagine']?>" alt="Simbolo ruolo"/></div>
                        <h3><?=htmlspecialchars($ruolo['nome_ruolo'])?></h3>
                        <div class="role-actions">
                            <button class="btn btn-edit" onclick="editRole(<?=$ruolo['id_ruolo']?>)"><i class="fa-solid fa-pencil"></i></button>
                            <button class="btn btn-delete" onclick="deleteRole(<?=$ruolo['id_ruolo']?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Contenuto Abilità -->
                <div class="guild-content" id="content-skill-<?=$gilda['id_gilda']?>">
                    <div class="guild-header">
                        <h4>Abilità</h4>
                    </div>
                    <?php
                    $skill = gdrcd_query("SELECT * FROM abilita WHERE id_gilda = ".$gilda['id_gilda']." ORDER BY tipo", 'result');
                    foreach ($skill as $skl): ?>
                    <div class="guild-info">
                        <div class="truncate-text"><?=$skl['tipo']?></div>
                        <h3><?=$skl['nome']?></h3>
                        <div class="truncate-text"><?=$skl['descrizione']?></div>
                        <div class="role-actions">
                            <button class="btn btn-edit" onclick="editSkill(<?=$skl['id_abilita']?>)"><i class="fa-solid fa-pencil"></i></button>
                            <button class="btn btn-delete" onclick="deleteSkill(<?=$skl['id_abilita']?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Contenuto Statuti -->
                <div class="guild-content" id="content-statuto-<?=$gilda['id_gilda']?>">
                    <div class="guild-header"><h4>Statuto</h4></div>
                    <?php
                    $statuto = gdrcd_query("SELECT * FROM statuti WHERE id_gilda = ".$gilda['id_gilda'], 'result');
                    foreach ($statuto as $voce): ?>
                    <div class="guild-info">
                        <div><?= htmlspecialchars($voce['articolo']) ?></div>
                        <h3><?= htmlspecialchars($voce['titolo']) ?></h3>
                        <div class="role-actions">
                            <button class="btn btn-edit" onclick="editVoceStatuto(<?=$voce['articolo']?>)"><i class="fa-solid fa-pencil"></i></button>
                            <button class="btn btn-delete" onclick="deleteVoceStatuto(<?=$voce['articolo']?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Contenuto Personaggi -->
                <div class="guild-content" id="content-pg-<?=$gilda['id_gilda']?>">
                    <div class="guild-header">
                        <h4>Gildati</h4>
                        <select class="btn btn-edit" name="personaggio" id="addGuildPg<?=$gilda['id_gilda']?>" required>
                            <?php
                            $sql = "SELECT personaggio.*, gilda.nome AS nome_gilda
                                    FROM personaggio
                                    LEFT JOIN gilda on personaggio.id_gilda = gilda.id_gilda
                                    WHERE personaggio.id_gilda <> ".$gilda['id_gilda']."
                                    ORDER BY personaggio.nome ASC";
                            $personaggi = gdrcd_query($sql, 'result');
                            foreach ($personaggi as $pg): ?>
                                <option value="<?=$pg['nome']?>"><?=$pg['nome']?> - <?=$pg['nome_gilda']?$pg['nome_gilda']:'Libero'?></option>
                            <?php endforeach ?>
                        </select>
                        <button class="btn btn-edit" onclick="addGuildPg(<?=$gilda['id_gilda']?>)"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Aggiungi pg</button>
                    </div>
                    <?php
                    $personaggi_gilda = gdrcd_query("SELECT clgpersonaggioruolo.*, ruolo.nome_ruolo, ruolo.immagine
                                                FROM clgpersonaggioruolo
                                                INNER JOIN ruolo ON clgpersonaggioruolo.id_ruolo = ruolo.id_ruolo
                                                WHERE ruolo.gilda = ".$gilda['id_gilda']."
                                                ORDER BY clgpersonaggioruolo.personaggio ASC", 'result');
                    foreach ($personaggi_gilda as $pgGuild): ?>
                    <div class="guild-info">
                        <div width="5%" data-label="Simbolo ruolo"><img width="25" height="25" src="../imgs/guilds/<?=$pgGuild['immagine']?>" title="<?=$pgGuild['nome_ruolo']?>" alt="<?=$pgGuild['nome_ruolo']?>"/></div>
                        <h3><?=$pgGuild['personaggio']?></h3>
                        <div><?=htmlspecialchars($pgGuild['nickname'])?></div>
                        <div class="role-actions">
                            <button class="btn btn-delete" onclick="removeGuildPg('<?=$pgGuild['personaggio']?>')"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($gilde)): ?>
            <div class="no-gilde"><p>Nessuna gilda configurata</p></div>
        <?php endif; ?>
        </div>
    </div>
    <!-- FINE lista -->

    <!-- Modale GILDA -->
    <div id="modalGuild" class="pg-edit-container dialog" role="dialog" aria-modal="true">
        <div class="modal-content">
        <span class="close" onclick="closeGuildModal('guild')">&times;</span>
            <h3 id="modalGildaTitle">Nuova Gilda</h1>
            <form id="formGuild" method="post">
                <div class="form-group">
                    <label for="nome">Nome gilda</label>
                    <input id="nome" name="nome" value="" required>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select name="tipo" id="tipo" required>
                        <?php
                        $tipi = gdrcd_query("SELECT * FROM codtipogilda", 'result');
                        foreach ($tipi as $tipo): ?>
                            <option value="<?=$tipo['cod_tipo']?>"><?=gdrcd_filter('out', $tipo['descrizione'])?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="immagine">Immagine</label>
                        <div id="imagePreview" style="margin-bottom: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                    <div class="form-group form-column">
                        <input type="file" name="immagine" id="immagine" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label for="visibile">Visibile</label>
                    <input type="checkbox" name="visibile" id="visibile">
                </div>
                <input type="hidden" name="id_gilda" id="guild_id" value="">
                <div class="actions"><button type="submit" onclick="saveGuild()">Salva modifiche</button></div>
            </form>
        </div>
    </div>
    <!-- FINE modale GILDA -->

    <!-- Modale RUOLO -->
    <div id="modalRole" class="pg-edit-container dialog" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalRuoloTitle">Nuovo Ruolo</h3>
                <span class="close" onclick="closeGuildModal('role')">&times;</span>
            </div>
            <form id="formRole" method="POST">
                <input type="hidden" name="id_ruolo" id="role_id">
                <input type="hidden" name="gilda" id="guild_role_id">
                <div class="form-group">
                    <label for="nome_ruolo">Nome Ruolo</label>
                    <input type="text" id="nome_ruolo" name="nome_ruolo" required>
                </div>
                <div class="form-row">
                    <div class="form-group form-column">
                        <label for="immagine">Immagine</label>
                        <div id="imagePreview" style="margin-bottom: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                    <div class="form-group form-column">
                        <input type="file" name="immagine" id="immagine" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label for="capo">Capo</label>
                    <input type="checkbox" name="capo" id="capo">
                </div>
                <div class="form-group">
                    <label for="livello">Livello</label>
                    <input type="number" name="livello" id="livello">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeGuildModal('role')">Annulla</button>
                    <button type="submit" class="btn btn-primary" onclick="saveRole()">Salva</button>
                </div>
            </form>
        </div>
    </div>
    <!-- FINE   Modale RUOLO -->

    <!-- Modale SOGLIE -->
    <div id="modalSoglia" class="pg-edit-container dialog" role="dialog" aria-modal="true">
        <div class="modal-content" id="sogliaContainer">
            <span class="close" onclick="closeGuildModal('soglia')">&times;</span>
            <h3 id="modalSogliaTitle">Modifica le soglie dei livelli</h1>
            <div class="form-row">
                <div class="form-group form-column"><label for="livello_soglia">Livello</label></div>
                <div class="form-group form-column"><label for="soglia">Fino a (px)</label></div>
                <div class="form-group form-column"><label for="soglia">Moltiplicatore</label></div>
                <div class="form-group form-column">
                    <span style="float:left; margin-left:5px; position:relative;">
                        <label for="soglia">Integrità <span class="help-animated">?</span></label>
                        <div class="tooltip-bottom">
                            Quantità di integrità persa in caso di attacco mentale con skill di comando per due o più turni di seguito su pg diversi
                        </div>
                    </span>
                </div>
                <div class="form-group form-column"><span></span></div>
            </div>
            <?php
            $soglie = gdrcd_query("SELECT * FROM gilda_soglie ORDER BY livello", 'result');
            foreach ($soglie as $soglia): ?>
                <div class="form-row" style="margin-bottom:0px;">
                    <div class="form-group form-column">
                        <input type="number" id="livello_soglia<?=$soglia['id_soglia']?>" value="<?=$soglia['livello']?>" required>
                    </div>
                    <div class="form-group form-column">
                        <input type="number" id="soglia<?=$soglia['id_soglia']?>" value="<?=$soglia['soglia']?>" required>
                    </div>
                    <div class="form-group form-column">
                        <input type="number" id="danno<?=$soglia['id_soglia']?>" value="<?=$soglia['danno']?>" required>
                    </div>
                    <div class="form-group form-column">
                        <input type="number" id="integrita<?=$soglia['id_soglia']?>" value="<?=$soglia['integrita']?>" required>
                    </div>
                    <div class="form-group form-column">
                        <div class="actions" style="margin-top:0px;">
                            <button class="btn btn-sm btn-primary" onclick="saveSoglia(<?=$soglia['id_soglia']?>)"><i class="fas fa-save"></i></button>
                            <button class="btn btn-sm btn-primary" onclick="deleteSoglia(<?=$soglia['id_soglia']?>)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="form-row" style="margin-bottom:0px;"><div class="form-group form-column"><label for="nuovo">Nuovo</label></div></div>
            <div class="form-row">
                <div class="form-group form-column"><input type="number" id="livello_soglia0" placeholder="Nuovo..."></div>
                <div class="form-group form-column"><input type="number" id="soglia0" placeholder="Nuovo..."></div>
                <div class="form-group form-column"><input type="number" id="danno0" placeholder="Nuovo..."></div>
                <div class="form-group form-column"><input type="number" id="integrita0" placeholder="Nuovo..."></div>
                <div class="form-group form-column">
                    <div class="actions" style="margin-top:0px;">
                        <button class="btn btn-sm btn-primary" onclick="saveSoglia(0)"><i class="fas fa-paper-plane"></i></button>
                        <button class="btn btn-sm btn-primary" onclick="deleteSoglia(0)"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- FINE Modale SOGLIE -->

    <!-- Modale SKILL/ABILITA -->
    <div id="modalSkill" class="pg-edit-container dialog" role="dialog" aria-modal="true">
        <div class="modal-content" id="skillContainer">
            <div class="modal-header">
                <span class="close" onclick="closeGuildModal('skill')">&times;</span>
                <h3 id="modalSkillTitle">Nuova abilità</h1>
            </div>
            <form id="formSkill" method="POST">
                <input type="hidden" name="id_abilita" id="id_abilita">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome_skill" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="descrizione">Descrizione</label>
                    <textarea id="descrizione_skill" name="descrizione" required></textarea>
                </div>
                <div class="form-group">
                    <label for="descrizione">Livello di sblocco</label>
                    <select id="livello_sblocco" class="livello_sblocco" name="livello_sblocco">
                    <?php $result = gdrcd_query("SELECT * FROM gilda_soglie ORDER BY livello", 'result');
                        while($livelli = gdrcd_query($result, 'fetch')) : ?>
                            <option value="<?=$livelli['livello']?>"><?=gdrcd_filter('out', $livelli['livello'])?></option>
                    <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="car">Caratteristica</label>
                    <select name='car' id="car">
                        <option value="0">Nessuna</option>
                        <option value="8"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car8'])?></option>
                        <!-- <option value="0"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car0'])?></option> -->
                        <option value="2"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car2'])?></option>
                        <option value="4"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car4'])?></option>
                        <option value="6"><?=gdrcd_filter('out', $PARAMETERS['names']['stats']['car6'])?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipologia</label>
                    <select name="tipo" id="tipo_skill">
                        <option value="Default"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['2'])?></option>
                        <option value="Difensiva"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['5'])?></option>
                        <option value="Generica base"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['1'])?></option>
                        <option value="Generica avanzata"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['7'])?></option>
                        <option value="Attacco base"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['3'])?></option>
                        <option value="Attacco medio"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['8'])?></option>
                        <option value="Attacco avanzato"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['9'])?></option>
                        <option value="Mentale base"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['4'])?></option>
                        <option value="Mentale media"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['10'])?></option>
                        <option value="Mentale avanzata"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['11']); ?></option>
                        <option value="Mentale di attacco"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['13']); ?></option>
                        <option value="Potere speciale"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['12']); ?></option>
                        <option value="Talento"><?=gdrcd_filter('out', $MESSAGE['names']['skill']['6']); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo">Gilda</label>
                    <select id="id_gilda" class="id_gilda" name="id_gilda">
                    <?php $result = gdrcd_query("SELECT id_gilda, nome, tipo FROM gilda ORDER BY nome", 'result');
                        while($guild = gdrcd_query($result, 'fetch')) : ?>
                            <option value="<?=$guild['id_gilda']?>"><?=gdrcd_filter('out', $guild['nome'])?></option>
                    <?php endwhile; ?>
                    </select>
                </div>

                <!-- Opzioni avanzate per skill generiche -->
                <div id="genericaSection" class="form-group" style="display: none;">
                    <label>Opzioni avanzate per skill generiche (seleziona una sola clausola):</label>

                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="" id="nessuna_opzione" checked>
                        <label for="nessuna_opzione">
                            Nessuna opzione
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="usa_creatura" id="usa_creatura">
                        <label for="usa_creatura">
                            Mette in gioco una creatura al suo servizio.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="sposta_danni_castatore_su_bersaglio" id="sposta_danni_castatore_su_bersaglio">
                        <label for="sposta_danni_castatore_su_bersaglio">
                            Trasferisce il proprio danno sul bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="sposta_danni_bersaglio_su_castatore" id="sposta_danni_bersaglio_su_castatore">
                        <label for="sposta_danni_bersaglio_su_castatore">
                            Trasferisce su di se i danni subiti dal bersaglio della skill.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="danni_dimezzati_nonostante_scudo" id="danni_dimezzati_nonostante_scudo">
                        <label for="danni_dimezzati_nonostante_scudo">
                            Nonostante il bersaglio abbia lanciato la barriera, subisce la metà dei danni.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="creatura_attacca_padrone" id="creatura_attacca_padrone">
                        <label for="creatura_attacca_padrone">
                            La creatura si ribella contro il padrone.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="annulla_lancio_bersaglio" id="annulla_lancio_bersaglio">
                        <label for="annulla_lancio_bersaglio">
                            Annulla ogni lancio del bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="più_10_al_danno" id="più_10_al_danno">
                        <label for="più_10_al_danno">
                            Incrementa di 10 punti il danno subito dal bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="più_5_danno" id="più_5_danno">
                        <label for="più_5_danno">
                            Incrementa di 5 punti il danno subito dal bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="dimezza_danno_bersaglio_selezionato" id="dimezza_danno_bersaglio_selezionato">
                        <label for="dimezza_danno_bersaglio_selezionato">
                            Dimezza il danno subito dal bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="meno_50_danni_tutti_con_durata" id="meno_50_danni_tutti_con_durata">
                        <label for="meno_50_danni_tutti_con_durata">
                            Tutti i presenti in role subiscono il 50% dei danni.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="danno_doppio" id="danno_doppio">
                        <label for="danno_doppio">
                            Raddoppia il danno subito dal bersaglio.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="meno_5_danno" id="meno_5_danno">
                        <label for="meno_5_danno">
                            Riduce di 5 punti il danno subito dal bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="annulla_scudo" id="annulla_scudo">
                        <label for="annulla_scudo">
                            Annulla lo scudo lanciato dal bersaglio selezionato.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="prolunga_effetti_un_turno" id="prolunga_effetti_un_turno">
                        <label for="prolunga_effetti_un_turno">
                        Prolunga gli effetti della skill generica lanciata dal bersaglio.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="converti_danni_bersaglio_in_salute_castatore" id="converti_danni_bersaglio_in_salute_castatore">
                        <label for="converti_danni_bersaglio_in_salute_castatore">
                            Tutti i danni subiti dal bersaglio si trasformano in ps per il castatore.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="converti_meta_danni_bersaglio_in_salute_castatore" id="converti_meta_danni_bersaglio_in_salute_castatore">
                        <label for="converti_meta_danni_bersaglio_in_salute_castatore">
                            Trasforma la metà del danno subito dal bersaglio in punti salute per se stesso.
                        </label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="più_15_punti_salute" id="più_15_punti_salute">
                        <label for="più_15_punti_salute">
                            Toglie 15 ps al castatore e li assegna al bersaglio.
                        </label>
                    </div>
                </div>

                <!-- Opzioni avanzate per skill mentali -->
                <div id="mentaleSection" class="form-group" style="display: none;">
                    <label>Opzioni avanzate per skill mentali:</label>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="" id="nessuna_opzione_mentale" checked>
                        <label for="nessuna_opzione_mentale">Nessuna opzione</label>
                    </div>
                    <div class="radio-group">
                        <input type="radio" name="sottotipo" value="comando" id="comando">
                        <label for="comando">La skill può essere utilizzata per imporre un comando al bersaglio.</label>
                    </div>
                </div>

                <!-- Pulsanti -->
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeGuildModal('skill')">Annulla</button>
                    <button type="submit" class="btn btn-primary" onclick="saveSkill()">Salva</button>
                </div>
            </form>
        </div>
    </div>
    <!-- FINE Modale SKILL/ABILITA -->

    <!-- Modale voce STATUTO -->
    <div id="modalStatuto" class="pg-edit-container dialog" role="dialog" aria-modal="true">
        <div class="modal-content" id="statutoContainer">
            <div class="modal-header">
                <span class="close" onclick="closeGuildModal('statuto')">&times;</span>
                <h3 id="modalStatutoTitle">Nuova voce statuto</h1>
            </div>
            <form id="formStatuto" method="POST">
                <input type="hidden" name="articolo" id="articolo">
                <div class="form-group">
                    <label for="titolo_voce_statuto">Titolo</label>
                    <input type="text" id="titolo_voce_statuto" name="titolo" required>
                </div>
                <div class="form-group">
                    <label for="testo_voce_statuto">Testo</label>
                    <textarea id="testo_voce_statuto" name="testo" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeGuildModal('statuto')">Annulla</button>
                    <button type="submit" class="btn btn-primary" onclick="saveVoceStatuto()">Salva</button>
                </div>
            </form>
        </div>
    </div>
    <!-- FINE Modale voce STATUTO -->
</div>