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

<link rel="stylesheet" href="../themes/crystal/gestione_gilde.css">

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
                    $statuto = gdrcd_query("SELECT * FROM statuti_new WHERE id_gilda = ".$gilda['id_gilda'], 'result');
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
                        <input type="radio" name="sottotipo" value="comando" id="comando">
                        <label for="comando">La skill può essere utilizzata per imporre un comando al bersaglio.</label>
                    </div>
                </div>

                <!-- Pulsanti -->
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSkillModal('statuto')">Annulla</button>
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
<h1>Lascio il vecchio pannello per sicurezza, per un po di tempo.</h1>




    <!-- Titolo della pagina -->
    <div class="page_title">
        <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['page_name']); ?></h2>
    </div>
    <!-- Corpo della pagina -->
    <div class="page_body">
        <?php /*Inserimento di un nuovo ruolo nella gilda corrente*/
        if(gdrcd_filter('get', $_POST['op']) == 'nuovo_ruolo') {
            /*Processo le informazioni ricevute dal form*/
            $is_capo = (isset($_POST['capo']) == true) && ($_POST['capo'] == 'is_capo') ? 1 : 0;

            // $immagine = ($_POST['immagine'] == '') ? "standard_gilda.png" : gdrcd_filter('in', $_POST['immagine']);
            if($_FILES['img_oggetto']['name'] == '') $immagine_oggetto = 'standard_oggetto.png';
            else $immagine_oggetto = $_FILES['img_oggetto']['name'];
            
            /*Eseguo l'inserimento*/
            gdrcd_query("INSERT INTO ruolo (nome_ruolo, gilda, immagine, stipendio, capo, livello) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".gdrcd_filter('num', $_POST['gilda']).", '".gdrcd_filter('in', $immagine_oggetto)."', '".gdrcd_filter('num', $_POST['stipendio'])."', '".$is_capo."','".gdrcd_filter('num', $_POST['livello'])."')");
            upload_image();
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde&op=edit&id_record=<?php echo gdrcd_filter('num', $_POST['gilda']); ?>">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }
            
        /*Inserimento di un nuovo record*/
        if(gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['guilds']['submit']['insert']) {
            /*Processo le informazioni ricevute dal form*/
            $is_visible = ((isset($_POST['visible']) == true) && ($_POST['visible'] == 'is_visible')) ? 1 : 0;

            $url_sito = ((isset($_POST['url_sito']) == true) && ($_POST['url_sito'] == 'http://')) ? '' :  $_POST['url_sito'];

            // $immagine = ($_POST['immagine'] == '') ? "standard_gilda.png" : gdrcd_filter('in', $_POST['immagine']);
            if($_FILES['img_oggetto']['name'] == '') {
                $immagine_oggetto = 'standard_oggetto.png';
            } else {
                $immagine_oggetto = $_FILES['img_oggetto']['name'];
            }
            /*Eseguo l'inserimento*/
            gdrcd_query("INSERT INTO gilda (nome, tipo, immagine, url_sito, visibile, statuto) VALUES ('".gdrcd_filter('in', $_POST['nome'])."', ".gdrcd_filter('in', $_POST['tipo']).", '".gdrcd_filter('in', $immagine_oggetto)."', '".gdrcd_filter('in', $_POST['url_sito'])."', '".$is_visible."', '".gdrcd_filter('in', $_POST['statuto'])."')");
            upload_image();
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['inserted']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }
            
        /* Cancellatura in un record */
        if(gdrcd_filter('get', $_POST['op']) == 'erase') {
            /*Eseguo la cancellatura*/
            $result = gdrcd_query("SELECT id_ruolo FROM ruolo WHERE gilda = ".gdrcd_filter('num', $_POST['id_record'])."", 'result');

            while($row = gdrcd_query($result, 'fetch')) {
                gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE id_ruolo=".gdrcd_filter('num', $row['id_ruolo'])."");
            }
            gdrcd_query($result, 'free');
            gdrcd_query("DELETE FROM ruolo WHERE gilda = ".gdrcd_filter('num', $_POST['id_record'])."");
            gdrcd_query("DELETE FROM gilda WHERE id_gilda=".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }
            
        /* Cancellatura in un ruolo */
        if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['guilds']['role']['submit']['delete']) && ($_POST['provenienza'] == 'ruolo')) { /*Eseguo la cancellatura*/
            gdrcd_query("DELETE FROM clgpersonaggioruolo WHERE id_ruolo=".gdrcd_filter('num', $_POST['id_ruolo'])."");
            gdrcd_query("DELETE FROM ruolo WHERE id_ruolo=".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
            gdrcd_query("DELETE FROM clgruoloabilita WHERE ruolo='". $_POST['nome_vecchio'] ."'");
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['deleted']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde&op=edit&id_record=<?php echo gdrcd_filter('num', $_POST['gilda']) ?>"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?></a>
            </div>
        <?php
        }
            
        /*Modifica di un record*/
        if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['guilds']['submit']['edit']) && (isset($_POST['provenienza']) == false)) {
            /*Processo le informazioni ricevute dal form*/
            $is_visible = ((isset($_POST['visible']) == true) && ($_POST['visible'] == 'is_visible')) ? 1 : 0;

            $url_sito = ((isset($_POST['url_sito']) == true) && ($_POST['url_sito'] == 'http://')) ? '' :  $_POST['url_sito'];

            $immagine = ($_POST['immagine'] == '') ? "standard_gilda.png" : gdrcd_filter('in', $_POST['immagine']);

            /*Eseguo l'aggiornamento*/
            gdrcd_query("UPDATE gilda SET nome ='".gdrcd_filter('in', $_POST['nome'])."', visibile = ".$is_visible.", immagine = '".gdrcd_filter('in', $immagine)."', tipo = ".gdrcd_filter('in', $_POST['tipo']).", url_sito = '".gdrcd_filter('in', $url_sito)."', statuto='".gdrcd_filter('in', $_POST['statuto'])."' WHERE id_gilda = ".gdrcd_filter('num', $_POST['id_record'])." LIMIT 1");
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }
            
        /*Modifica di un ruolo*/
        if((gdrcd_filter('get', $_POST['op']) == $MESSAGE['interface']['administration']['guilds']['role']['submit']['edit']) && ($_POST['provenienza'] == 'ruolo')) {
            /*Processo le informazioni ricevute dal form*/
            $is_capo = (isset($_POST['capo']) == true) && ($_POST['capo'] == 'is_capo') ? 1 : 0;

            #$immagine = ($_POST['immagine'] == '') ? "standard_gilda.png" : gdrcd_filter('in', $_POST['immagine']);
            
            if($_FILES['img_oggetto']['name'] == '') {
                /*Eseguo l'aggiornamento SENZA immagine*/
            gdrcd_query("UPDATE ruolo SET nome_ruolo ='".gdrcd_filter('in', $_POST['nome'])."', capo = ".$is_capo.", livello = '".gdrcd_filter('num', $_POST['livello'])."', gilda = ".gdrcd_filter('num', $_POST['gilda']).", stipendio = ".gdrcd_filter('num', $_POST['stipendio'])." WHERE id_ruolo = ".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
            gdrcd_query("UPDATE clgruoloabilita SET ruolo ='".gdrcd_filter('in', $_POST['nome'])."' WHERE ruolo = '".gdrcd_filter('in', $_POST['nome_vecchio']) ."'"); 
            } else {
                $immagine_oggetto = $_FILES['img_oggetto']['name'];
                /*Eseguo l'aggiornamento CON immagine*/
            gdrcd_query("UPDATE ruolo SET nome_ruolo ='".gdrcd_filter('in', $_POST['nome'])."', capo = ".$is_capo.", immagine = '".gdrcd_filter('in', $immagine_oggetto)."', livello = '".gdrcd_filter('num', $_POST['livello'])."', gilda = ".gdrcd_filter('num', $_POST['gilda']).", stipendio = ".gdrcd_filter('num', $_POST['stipendio'])." WHERE id_ruolo = ".gdrcd_filter('num', $_POST['id_ruolo'])." LIMIT 1");
            upload_image();
            gdrcd_query("UPDATE clgruoloabilita SET ruolo ='".gdrcd_filter('in', $_POST['nome'])."' WHERE ruolo = '". $_POST['nome_vecchio'] ."'"); 
            }
            
            ?>
            <!-- Conferma -->
            <div class="warning">
                <?php echo gdrcd_filter('out', $MESSAGE['warning']['modified']); ?>
            </div>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde&op=edit&id_record=<?php echo $_POST['gilda'] ?>">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }
            
        /*Form di inserimento/modifica*/
        if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') || (gdrcd_filter('get', $_REQUEST['op']) == 'new')) {
            /*Preseleziono l'operazione di inserimento*/
            $operation = 'insert';

            /*Se è stata richiesta una modifica*/
            if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') && (gdrcd_filter('get', $_REQUEST['id_record'] > -1))) {
                /*Carico il record da modificare*/
                $loaded_record = gdrcd_query("SELECT * FROM gilda WHERE id_gilda=".gdrcd_filter('get', $_REQUEST['id_record'])." LIMIT 1 ");
                /*Cambio l'operazione in modifica*/
                $operation = 'edit';
            }//if

            if((isset($_REQUEST['id_record']) === false) || (gdrcd_filter('get', $_REQUEST['id_record'] > -1))) { ?>
                <!-- Form di inserimento/modifica -->
                <div class="panels_box">
                    <form action="main.php?page=gestione_gilde" method="post" class="form_gestione" enctype="multipart/form-data">
                        <div class='form_label'><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['name']); ?></div>
                        <div class='form_field'><input name="nome" value="<?php echo gdrcd_filter('out', $loaded_record['nome']); ?>" /></div>
                        <div class='form_label'><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['type']); ?></div>
                        <div class='form_field'>
                            <?php /* Carico l'elenco dei tipi di gilda */
                            $tipi = gdrcd_query("SELECT cod_tipo, descrizione FROM codtipogilda", 'result');
                            /*Se sono presenti tipi sul database*/
                            if(gdrcd_query($tipi, 'num_rows') > 0) { ?>
                                <!-- Elenco dei tipi -->
                                <select name="tipo">
                                    <?php while($option = gdrcd_query($tipi, 'fetch')) { ?>
                                        <option value="<?php echo $option['cod_tipo']; ?>" <?php if($loaded_record['tipo'] == $option['cod_tipo']) {echo 'SELECTED';} ?>>
                                            <?php echo gdrcd_filter('out', $option['descrizione']); ?>
                                        </option>
                                    <?php }
                                    gdrcd_query($tipi, 'free');
                                    ?>
                                </select>
                            <?php
                            } else echo gdrcd_filter('out', $MESSAGE['interface']['administration']['locations']['type_err']); ?>
                        </div>
                        <div class="link_back">
                            <a href="main.php?page=gestione_tipi&types=guilds">
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['menage_types']); ?>
                            </a>
                        </div>

                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['image']); ?>
                        </div>
                        <div class='form_field'>
                        <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                        </div>

                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['site']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="url_sito" value="<?php if(isset($loaded_record['url_sito']) === true) {
                                        echo gdrcd_filter('out', $loaded_record['url_sito']);
                                    } else {
                                        echo "http://";
                                    } ?>" />
                        </div>
                        <div class='form_label'>Statuto</div>
                        <div class='form_field'><textarea name="statuto"><?php echo gdrcd_filter('out', $loaded_record['statuto']); ?></textarea></div>
                        <div class='form_label'><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['visible']); ?></div>
                        <div class='form_field'>
                            <input type="checkbox" name="visible"
                                <?php if(gdrcd_filter('out', $loaded_record['visibile']) == 1) { ?>
                                    checked="checked"
                                <?php } ?>
                                    value="is_visible" />
                        </div>
                        <div class='form_info'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['visible_info']); ?>
                        </div>
                        <!-- bottoni -->
                        <div class='form_submit'>
                            <?php /* Se l'operazione è una modifica stampo i tasti modifica e annulla */
                            if($operation == "edit") {  ?>
                                <input type="hidden" name="id_record" value="<?php echo gdrcd_filter('out', $loaded_record['id_gilda']); ?>">
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['submit']['edit']); ?>" name="op" />
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['submit']['undo']); ?>" name="cancel" />
                            <?php
                            } else { /* Altrimenti il tasto inserisci */ ?>
                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['submit']['insert']); ?>" name="op" />
                            <?php
                            } ?>
                        </div>
                    </form>
                </div>
            <?php
            }//if

            if((gdrcd_filter('get', $_REQUEST['op']) == 'edit') && (isset($_REQUEST['id_record']) === true)) { ?>
                <!-- Titolo della pagina -->
                <div class="page_title">
                    <h2><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['page_name']); ?></h2>
                </div>
                <div class="page_body">
                    <?php $id_gilda_padre = (0) ? -1 : gdrcd_filter('get', $_REQUEST['id_record']); ?>
                    <!-- Nuovo ruolo -->
                    <form action="main.php?page=gestione_gilde" method="post" class="form_gestione" enctype="multipart/form-data">
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['name_new']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="nome" value="" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['image']); ?>
                        </div>
                        <div class='form_field'>
                        <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['pay']); ?>
                        </div>
                        <div class='form_field'>
                            <input name="stipendio" value="0" />
                        </div>
                        <div class='form_label'>
                            <?php echo gdrcd_filter('out', "Livello"); ?>
                        </div>
                        <div class='form_field'>
                            <input name="livello" value="1" />
                        </div>
                        <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                            <div class='form_label'>
                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['head']); ?>
                            </div>
                            <div class='form_field'>
                                <input type="checkbox" name="capo" value="is_capo" />
                            </div>
                        <?php } else { ?>
                            <div class='form_field'>
                                <input type="hidden" name="capo" value="is_not_capo" />
                            </div>
                        <?php } ?>
                        <div class='form_info'>
                            <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['head_info']); ?>
                        </div>
                        <div class='form_submit'>
                            <input type="hidden" name="gilda" value="<?php echo gdrcd_filter('out', $id_gilda_padre); ?>" />
                            <input type="hidden" name="op" value="nuovo_ruolo" />
                            <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['submit']['insert']); ?>" name="submit" />
                        </div>
                    </form>
                    <?php /*Carico i ruoli della gilda corrende*/
                    $result = gdrcd_query("SELECT * FROM ruolo WHERE gilda=".gdrcd_filter('num', $id_gilda_padre)." ORDER BY capo DESC, livello DESC", 'result');
                    /*Elenco ruoli*/
                    while($row = gdrcd_query($result, 'fetch')) { ?>
                        <form action="main.php?page=gestione_gilde" method="post" class="form_gestione" enctype="multipart/form-data">
                            <div class="elenco_record_gestione">
                                <table>
                                    <tr>
                                        <td>
                                            <div class='titoli_elenco'>
                                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class='titoli_elenco'>
                                                <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['image']); ?>
                                            </div>
                                        </td>

                                        
                                        <td>
                                            <div class='titoli_elenco'>
                                                <?php echo Livello ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class='form_field'>
                                                <input name="nome" value="<?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>" />
                                                <input type="hidden" name="nome_vecchio" value="<?php echo gdrcd_filter('out', $row['nome_ruolo']); ?>" />
                                            </div>
                                        </td>
                                        <!-- immagine modifica ruolo -->
            
                                        <td>
                                            <div class='form_field'>
                                        <input type="file" name="img_oggetto" value="<?php echo $loaded_item['urlimg']; ?>" />                                                </div>
                                        </td>

                                        
                                        <!-- livello -->
                                        
                                        <td>
                                            <div class='form_field'>
                                                <input name="livello" value="<?php echo 0 + gdrcd_filter('out', $row['livello']); ?>" />
                                            </div>
                                        </td>
                    
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                                                <div class='form_label'>
                                                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['head']); ?>
                                                </div>
                                            <?php } else { ?>
                                                &nbsp;
                                            <?php } ?>
                                        </td>
                                        <td>
                                        </td>
                                        <td>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php if(gdrcd_filter('get', $_REQUEST['id_record'] > -1)) { ?>
                                                <div class='form_field'>
                                                    <input type="checkbox" name="capo" <?php if($row['capo'] == 1) {echo 'checked';} ?> value="is_capo" />
                                                </div>
                                            <?php } else { ?>
                                                <div class='form_field'>
                                                    <input type="hidden" name="capo" value="is_not_capo" />
                                                </div>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <div class='form_submit'>
                                                <input type="hidden" name="provenienza" value="ruolo" />
                                                <input type="hidden" name="id_ruolo" value="<?php echo gdrcd_filter('out', $row['id_ruolo']); ?>" />
                                                <input type="hidden" name="gilda" value="<?php echo gdrcd_filter('out', $id_gilda_padre); ?>" />
                                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['submit']['edit']); ?>" name="op" />
                                                <input type="submit" value="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['role']['submit']['delete']); ?>" name="op" />
                                            </div>
                                        </td>
                                        <td>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <!-- elenco_record_gestione -->
                        </form>
                    <?php
                    }//while
                    gdrcd_query($result, 'free');
                    ?>
                </div>
            <?php
            }//if
            ?>
            <!-- Link di ritorno alla visualizzazione di base -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['back']); ?>
                </a>
            </div>
        <?php
        }//if
        
        /*Elenco dei record*/
        if((isset($_POST['op']) === false) && (isset($_REQUEST['op']) === false)) { /*Elenco record (Visualizzaione di base della pagina)*/
            //Determinazione pagina (paginazione)
            $pagebegin = (int) gdrcd_filter('get', $_REQUEST['offset']) * $PARAMETERS['settings']['records_per_page'];
            $pageend = $PARAMETERS['settings']['records_per_page'];
            //Conteggio record totali
            $record_globale = gdrcd_query("SELECT COUNT(*) FROM gilda");
            $totaleresults = $record_globale['COUNT(*)'];
            //Lettura record
            $result = gdrcd_query("SELECT gilda.id_gilda, gilda.nome, gilda.visibile, codtipogilda.descrizione FROM gilda LEFT JOIN codtipogilda ON gilda.tipo = codtipogilda.cod_tipo ORDER BY nome LIMIT ".$pagebegin.", ".$pageend."", 'result');
            $numresults = gdrcd_query($result, 'num_rows');

            /* Se esistono record */
            if($numresults > 0) { ?>
                <!-- Elenco dei record paginato -->
                <div class="elenco_record_gestione">
                    <table>
                        <!-- Intestazione tabella -->
                        <tr>
                            <td class="casella_titolo">
                                <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['name_col']); ?></div>
                            </td>
                            <td class="casella_titolo">
                                <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['type']); ?></div>
                            </td>
                            <td class="casella_titolo">
                                <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['visible']); ?></div>
                            </td>
                            <td class="casella_titolo">
                                <div class="titoli_elenco"><?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops_col']); ?></div>
                            </td>
                        </tr>
                        <!-- Record -->
                        <?php while($row = gdrcd_query($result, 'fetch')) { ?>
                            <tr>
                                <td class="casella_elemento">
                                    <div class="elementi_elenco"><?php echo gdrcd_filter('out', $row['nome']); ?></div>
                                </td>
                                <td class="casella_elemento">
                                    <div class="elementi_elenco"><?php echo gdrcd_filter('out', $row['descrizione']); ?></div>
                                </td>
                                <td class="casella_elemento">
                                    <div class="elementi_elenco"><?php if($row['visibile'] == 1) {
                                            echo gdrcd_filter('out', $MESSAGE['interface']['administration']['yes']);
                                        } else {
                                            echo gdrcd_filter('out', $MESSAGE['interface']['administration']['no']);
                                        } ?></div>
                                </td>
                                <td class="casella_controlli"><!-- Iconcine dei controlli -->
                                                                <!-- Modifica -->
                                    <div class="controlli_elenco">
                                        <div class="controllo_elenco">
                                            <form class="opzioni_elenco_record_gestione"
                                                    action="main.php?page=gestione_gilde" method="post">
                                                <input type="hidden" name="id_record"
                                                        value="<?php echo gdrcd_filter('out', $row['id_gilda']) ?>" />
                                                <input type="hidden" name="op" value="edit" />
                                                <input type="image"
                                                        src="imgs/icons/edit.png"
                                                        alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>"
                                                        title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['edit']); ?>" />
                                            </form>
                                        </div>
                                        <!-- Elimina -->
                                        <div class="controllo_elenco">
                                            <form class="opzioni_elenco_record_gestione"
                                                    action="main.php?page=gestione_gilde" method="post">
                                                <input type="hidden" name="id_record"
                                                        value="<?php echo gdrcd_filter('out', $row['id_gilda']) ?>" />
                                                <input type="hidden" name="op" value="erase" />
                                                <input type="image"
                                                        src="imgs/icons/erase.png"
                                                        alt="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']); ?>"
                                                        title="<?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['ops']['erase']
                                                        ); ?>" />
                                            </form>
                                        </div>
                                        <div class="controlli_elenco">
                                </td>
                            </tr>
                        <?php
                        } //while
                        gdrcd_query($result, 'free');
                        ?>
                    </table>
                </div>
            <?php
            }//if
            ?>
            <!-- Paginatore elenco -->
            <div class="pager">
                <?php if($totaleresults > $PARAMETERS['settings']['records_per_page']) {
                    echo gdrcd_filter('out', $MESSAGE['interface']['pager']['pages_name']);
                    for($i = 0; $i <= floor($totaleresults / $PARAMETERS['settings']['records_per_page']); $i++) {
                        if($i != gdrcd_filter('num', $_REQUEST['offset'])) { ?>
                            <a href="main.php?page=gestione_gilde&offset=<?php echo $i; ?>"><?php echo $i + 1; ?></a>
                        <?php } else {
                            echo ' '.($i + 1).' ';
                        }
                    } //for
                }//if
                ?>
            </div>
            <!-- link crea nuovo -->
            <div class="link_back">
                <a href="main.php?page=gestione_gilde&op=new">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['new']); ?>
                </a><br />
                <a href="main.php?page=gestione_gilde&op=edit&id_record=-1">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['new_role']); ?>
                </a><br />
                <a href="main.php?page=gestione_tipi&types=guilds">
                    <?php echo gdrcd_filter('out', $MESSAGE['interface']['administration']['guilds']['link']['menage_types']); ?>
                </a>
            </div>
    <?php } ?>
    </div><!-- page_body -->
<?php
    }

    function upload_image(){
        $allow = array("jpg", "jpeg", "gif", "png");

        $todir = "imgs/guilds/";
        $test = $_FILES['img_oggetto']['name'];

        // is the file uploaded yet?
        if ( !!$_FILES['img_oggetto']['tmp_name'] ) $info = explode('.', strtolower( $_FILES['img_oggetto']['name']) ); // whats the extension of the file
    } ?>
</div><!-- pagina -->