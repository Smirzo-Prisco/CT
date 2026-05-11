<link rel="stylesheet" href="../themes/crystal/mestieri.css">

<div class="pagina_schedam_odifica">
<?php
    if (isset($_REQUEST['pg']) === false) echo gdrcd_filter('out', $MESSAGE['error']['unknown_character_sheet']);
    else {
        add_script("/includes/personaggio.js");
        
        // Inizializzazione variabili
        $confirm_updating = true;
        $pg_name = gdrcd_filter('get', $_REQUEST['pg'] ?? '');
        $current_op = $_POST['op'] ?? '';

        // Validazione del nome personaggio
        if (empty($pg_name)) {
            echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['invalid_character']) . '</div>';
            $confirm_updating = false;
        }

        /**
         * Validazione file audio
         * @author Blancks
         */
        if ($confirm_updating && $PARAMETERS['mode']['allow_audio'] == 'ON') {
            $media_url = $_POST['modifica_url_media'] ?? '';
            
            if (!empty($media_url)) {
                $file_extension = strtolower(pathinfo($media_url, PATHINFO_EXTENSION));
                $is_extension_allowed = isset($PARAMETERS['settings']['audiotype']['.' . $file_extension]);
                
                if (!$is_extension_allowed) {
                    echo '<div class="warning">' . gdrcd_filter('out', $MESSAGE['warning']['media_not_allowed']) . '</div>';
                    $confirm_updating = false;
                }
            }
        } elseif ($current_op == 'modify') $_POST['modifica_url_media'] = '';

        /**
         * Elaborazione principale
         * @author Blancks
         */
        if ($confirm_updating) {
            if ($current_op === 'modify') {
                // Verifica che l'utente stia modificando il proprio personaggio
                $is_own_character = ($_SESSION['login'] ?? '') === $pg_name;
                
                if (!$is_own_character) echo '<div class="error">' . gdrcd_filter('out', $MESSAGE['error']['unknown_operation']) . '</div>';
                else {
                    // Gestione blocco media
                    $blocca_media = (strtolower($_POST['blocca_media'] ?? '') === 'on') ? 1 : 0;
                    
                    if ($_SESSION['login'] === $pg_name) $_SESSION['blocca_media'] = $blocca_media;

                    // Funzione helper per filtrare i campi
                    $filter_field = function($field, $type = 'in') { return isset($_POST[$field]) ? gdrcd_filter($type, $_POST[$field]) : ''; };

                    // Raccolta dati con filtro appropriato
                    $form_data = [
                        'affetti' => $filter_field('modifica_affetti'),
                        'background' => $filter_field('modifica_background'),
                        'volto' => $filter_field('modifica_volto'),
                        'descrizione' => $filter_field('modifica_descrizione'),
                        'place' => $filter_field('modifica_place'),
                        'off' => $filter_field('modifica_off'),
                        'note_fato' => $filter_field('modifica_note_fato'),
                        'particolari' => $filter_field('modifica_particolari'),
                        'principale' => $filter_field('modifica_principale'),
                        'nickname_tokyo' => $filter_field('nickname_tokyo'),
                        'nickname_gilda' => $filter_field('nickname_gilda'),
                        'cognome' => $filter_field('modifica_cognome'),
                        'url_media' => gdrcd_filter('in', gdrcd_filter('fullurl', $_POST['modifica_url_media'] ?? '')),
                        'url_img' => gdrcd_filter('in', gdrcd_filter('fullurl', $_POST['modifica_url_img'] ?? '')),
                        'url_img_chat' => gdrcd_filter('in', gdrcd_filter('fullurl', $_POST['modifica_url_img_chat'] ?? ''))
                    ];

                    // Campo età con validazione numerica
                    $form_data['age'] = $filter_field('modifica_age', 'num') ?: 0;

                    // Applica addslashes se necessario
                    $use_addslashes = (
                        $PARAMETERS['mode']['user_bbcode'] == 'OFF' || 
                        (
                            $PARAMETERS['mode']['user_bbcode'] == 'ON' && 
                            $PARAMETERS['settings']['forum_bbcode']['type'] == 'bbd' && 
                            $PARAMETERS['settings']['bbd']['free_html'] == 'ON'
                        )
                    );

                    if ($use_addslashes) {
                        $slashable_fields = ['affetti', 'background', 'volto', 'descrizione', 'place', 
                                            'age', 'off', 'note_fato', 'particolari', 'principale', 
                                            'nickname_tokyo', 'nickname_gilda'];
                        
                        foreach ($slashable_fields as $field) {
                            if (!empty($form_data[$field])) $form_data[$field] = gdrcd_filter('addslashes', $form_data[$field]);
                        }
                    }

                    // Gestione stato online
                    $online_state = '';
                    if ($PARAMETERS['mode']['user_online_state'] == 'ON') $online_state = $filter_field('online_state');

                    // Query di aggiornamento personaggio
                    $update_query = sprintf(
                        "UPDATE personaggio SET 
                            cognome = '%s',
                            volto = '%s',
                            affetti = '%s',
                            storia = '%s',
                            off = '%s',
                            principale = '%s',
                            descrizione = '%s',
                            eta = %d,
                            natoa = '%s',
                            url_media = '%s',
                            blocca_media = %d,
                            url_img = '%s',
                            url_img_chat = '%s'
                        WHERE nome = '%s'",
                            $form_data['cognome'],
                            $form_data['volto'],
                            $form_data['affetti'],
                            $form_data['background'],
                            $form_data['off'],
                            $form_data['principale'],
                            $form_data['descrizione'],
                            (int)$form_data['age'],
                            $form_data['place'],
                            $form_data['url_media'],
                            $blocca_media,
                            $form_data['url_img'],
                            $form_data['url_img_chat'],
                            $pg_name
                    );
                    
                    gdrcd_query($update_query);

                    /**
                     * Gestione nickname TokyoBook
                     * Aggiorna solo se il nickname è cambiato rispetto al precedente
                     */
                    if (!empty($form_data['nickname_tokyo'])) {
                        $query_tokyo = gdrcd_query(sprintf("SELECT nickname FROM tokyobook WHERE personaggio = '%s'", $pg_name), 'result');

                        // Se esiste già un record, verifica se è cambiato
                        if (gdrcd_query($query_tokyo, 'num_rows') > 0) {
                            $existing = gdrcd_query($query_tokyo, 'fetch');
                            
                            // Aggiorna solo se il nickname è diverso
                            if ($existing['nickname'] !== $form_data['nickname_tokyo'])
                                gdrcd_query(sprintf("UPDATE tokyobook SET nickname = '%s' WHERE personaggio = '%s'", $form_data['nickname_tokyo'], $pg_name));
                        } else gdrcd_query(sprintf("INSERT INTO tokyobook (personaggio, nickname) VALUES ('%s', '%s')", $pg_name, $form_data['nickname_tokyo'])); // Nuovo record
                    }

                    /**
                     * Gestione nickname Gilda
                     * Aggiorna solo se il nickname è cambiato rispetto al precedente
                     */
                    if (!empty($form_data['nickname_gilda'])) {
                        $query_gilda = gdrcd_query(sprintf("SELECT * FROM clgpersonaggioruolo WHERE personaggio = '%s'", $pg_name), 'result');
                        
                        // Recupera il nickname attuale
                        $current_gilda = gdrcd_query($query_gilda, 'fetch');
                        
                        // Aggiorna solo se il nickname è diverso o se non esiste
                        if ($current_gilda) {
                            if ($current_gilda['nickname'] !== $form_data['nickname_gilda']) {
                                gdrcd_query(sprintf(
                                    "UPDATE clgpersonaggioruolo SET nickname = '%s' WHERE personaggio = '%s'",
                                    $form_data['nickname_gilda'],
                                    $pg_name
                                ));
                            }
                        } else {
                            // Se non esiste un record, potresti voler gestire questo caso
                            // Opzionale: inserire un nuovo record se necessario
                            error_log("Tentativo di aggiornare nickname gilda per personaggio senza ruolo: " . $pg_name);
                        }
                    }
                }
            }
            
            if ($_SESSION['login'] == $_REQUEST['pg'] || $_SESSION['admin'] == '1'):
                // Caricamento informazioni personaggio
                $record = gdrcd_query(sprintf("SELECT * FROM personaggio WHERE nome = '%s'", $pg_name));

                // Recupero alias con il pattern corretto
                $alias_tokyo = null;
                $alias_gilda = null;
                
                // Query TokyoBook
                $query_tokyo = gdrcd_query(
                    sprintf("SELECT nickname FROM tokyobook WHERE personaggio = '%s'", gdrcd_filter('get', $_REQUEST['pg'])), 
                    'result'
                );
                
                if (gdrcd_query($query_tokyo, 'num_rows') > 0) {
                    $tokyo_data = gdrcd_query($query_tokyo, 'fetch');
                    $alias_tokyo = $tokyo_data['nickname'];
                }
                
                // Query Gilda
                $query_gilda = gdrcd_query(
                    sprintf("SELECT nickname FROM clgpersonaggioruolo WHERE personaggio = '%s'", gdrcd_filter('get', $_REQUEST['pg'])), 
                    'result'
                );
                
                if (gdrcd_query($query_gilda, 'num_rows') > 0) {
                    $gilda_data = gdrcd_query($query_gilda, 'fetch');
                    $alias_gilda = $gilda_data['nickname'];
                }
            endif;
        }

        /*****************************************************************
         * Funzioni helper per la generazione dei campi del form
         *****************************************************************/
        function render_input_row($label, $name, $value, $placeholder = '') {
            $placeholder_attr = $placeholder ? "placeholder=\"$placeholder\"" : '';
            
            return "
                <tr>
                    <td class=\"second_header\" style=\"text-transform:uppercase; font-size: 12px; width: 200px;\">
                        " . gdrcd_filter('out', $label) . "
                    </td>
                    <td>
                        <input type=\"text\" name=\"$name\" value=\"" . gdrcd_filter('out', $value) . "\" 
                            $placeholder_attr class=\"form_input\" style=\"width: 100%;\"/>
                    </td>
                </tr>
            ";
        }

        function render_textarea_row($label, $name, $value) {
            return "
                <tr>
                    <td class=\"second_header\" style=\"text-transform:uppercase; font-size: 12px; width: 200px; vertical-align: top;\">
                        " . gdrcd_filter('out', $label) . "
                    </td>
                    <td>
                        <textarea name=\"$name\" cols=\"80\" rows=\"15\" class=\"ares\" style=\"width: 100%;\">" 
                            . gdrcd_filter('out', $value) . 
                        "</textarea>
                    </td>
                </tr>
            ";
        }
    ?>

    <div class="page_title">
        <h2><?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['page_name']) ?></h2>
    </div>

    <!-- Menu scheda -->
    <div class="menu_scheda">
        <?php include 'scheda/menu.inc.php'; ?>
    </div>

    <div class="page_body">
        <div class="panels_box">
            <!-- Form utente modifica -->
            <form action="main.php?page=scheda_modifica" method="post">
                <table class="customTable">
                    <!-- DATI ANAGRAFICI -->
                    <tr class="second_header">
                        <td colspan="2" style="text-transform:uppercase; font-size: 15px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['personal_data'] ?? 'DATI PERSONALI') ?>
                        </td>
                    </tr>
                    
                    <!-- Cognome -->
                    <?= render_input_row(
                        $MESSAGE['interface']['sheet']['modify_form']['last_name'] ?? 'Cognome',
                        'modifica_cognome',
                        $record['cognome'] ?? ''
                    ) ?>
                    
                    <!-- Età -->
                    <?= render_input_row(
                        $MESSAGE['interface']['sheet']['modify_form']['age'] ?? 'Età',
                        'modifica_age',
                        $record['eta'] ?? ''
                    ) ?>
                    
                    <!-- Luogo di nascita -->
                    <?= render_input_row(
                        $MESSAGE['interface']['sheet']['modify_form']['place'] ?? 'Nato a',
                        'modifica_place',
                        $record['natoa'] ?? ''
                    ) ?>
                    
                    <!-- ASPETTO E IMMAGINI -->
                    <tr class="second_header">
                        <td colspan="2" style="text-transform:uppercase; font-size: 15px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['appearance'] ?? 'ASPETTO E IMMAGINI') ?>
                        </td>
                    </tr>
                    
                    <!-- Volto personaggio con pulsante controllo -->
                    <tr>
                        <td class="second_header" style="text-transform:uppercase; font-size: 12px; width: 200px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['volto'] ?? 'Volto') ?>
                        </td>
                        <td>
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 70%;">
                                        <input type="text" name="modifica_volto" id="volto" 
                                                value="<?= gdrcd_filter('out', $record['volto'] ?? '') ?>" 
                                                class="form_input" style="width: 100%;"/>
                                    </td>
                                    <td style="width: 30%; padding-left: 10px;">
                                        <input type="button" value="Controlla" onClick="checkVolto()" class="form_button" style="width: 100%;"/>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Indirizzo immagine -->
                    <?= render_input_row(
                        $MESSAGE['interface']['sheet']['modify_form']['url_img'] ?? 'URL Immagine',
                        'modifica_url_img',
                        $record['url_img'] ?? ''
                    ) ?>
                    
                    <!-- Indirizzo immagine chat -->
                    <?= render_input_row(
                        $MESSAGE['interface']['sheet']['modify_form']['url_img_chat'] ?? 'URL Immagine Chat',
                        'modifica_url_img_chat',
                        $record['url_img_chat'] ?? '',
                        'No gif animate'
                    ) ?>
                    
                    <!-- DESCRIZIONI -->
                    <tr class="second_header">
                        <td colspan="2" style="text-transform:uppercase; font-size: 15px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['descriptions'] ?? 'DESCRIZIONI') ?>
                        </td>
                    </tr>
                    
                    <!-- Background (Principale) -->
                    <?= render_textarea_row(
                        $MESSAGE['interface']['sheet']['modify_form']['principale'] ?? 'Background',
                        'modifica_principale',
                        $record['principale'] ?? ''
                    ) ?>
                    
                    <!-- Storia -->
                    <?= render_textarea_row(
                        $MESSAGE['interface']['sheet']['modify_form']['background'] ?? 'Storia',
                        'modifica_background',
                        $record['storia'] ?? ''
                    ) ?>
                    
                    <!-- Dice di sé -->
                    <?= render_textarea_row(
                        $MESSAGE['interface']['sheet']['modify_form']['description'] ?? 'Descrizione',
                        'modifica_descrizione',
                        $record['descrizione'] ?? ''
                    ) ?>
                    
                    <!-- Off -->
                    <?= render_textarea_row(
                        $MESSAGE['interface']['sheet']['modify_form']['off'] ?? 'Off',
                        'modifica_off',
                        $record['off'] ?? ''
                    ) ?>
                    
                    <!-- IMPOSTAZIONI -->
                    <tr class="second_header">
                        <td colspan="2" style="text-transform:uppercase; font-size: 15px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['settings'] ?? 'IMPOSTAZIONI') ?>
                        </td>
                    </tr>
                    
                    <!-- Disabilita suoni gioco -->
                    <tr>
                        <td class="second_header" style="text-transform:uppercase; font-size: 12px;">
                            <?= gdrcd_filter('out', $MESSAGE['interface']['sheet']['modify_form']['block_media'] ?? 'Blocca media') ?>
                        </td>
                        <td>
                            <input type="checkbox" name="blocca_media" 
                                    <?= ($record['blocca_media'] ?? 0) ? 'checked="checked"' : '' ?> 
                                    class="form_input"/>
                        </td>
                    </tr>
                    
                    <!-- Musica in scheda (se abilitata) -->
                    <?php if ($PARAMETERS['mode']['allow_audio'] == 'ON'): ?>
                        <?= render_input_row(
                            'MUSICA IN SCHEDA',
                            'modifica_url_media',
                            $record['url_media'] ?? ''
                        ) ?>
                    <?php endif; ?>
                    
                    <!-- ALIAS -->
                    <tr class="second_header">
                        <td colspan="2" style="text-transform:uppercase; font-size: 15px;">
                            ALIAS E SOPRANNOMI
                        </td>
                    </tr>
                    
                    <!-- Alias Tokyobook -->
                    <tr>
                        <td class="second_header" style="text-transform:uppercase; font-size: 12px;">
                            Tokyobook<br>
                            <i>(<?= $alias_tokyo === null ? 'Puoi impostarlo' : 'Modificabile una sola volta' ?>)</i>
                        </td>
                        <td>
                            <input type="text" name="nickname_tokyo" 
                                    value="<?= gdrcd_filter('out', $alias_tokyo ?? '') ?>" 
                                    class="form_input" style="width: 100%;"
                                    <?= ($alias_tokyo !== null && $_SESSION['admin'] != 1) ? 'readonly' : '' ?>/>
                        </td>
                    </tr>
                    
                    <!-- Alias Ruolo Gilda -->
                    <?php if ($alias_gilda !== null): ?>
                    <tr>
                        <td class="second_header" style="text-transform:uppercase; font-size: 12px;">
                            Famiglia<br>
                            <i>(Modificabile una sola volta)</i>
                        </td>
                        <td>
                            <input type="text" name="nickname_gilda" 
                                    value="<?= gdrcd_filter('out', $alias_gilda) ?>" 
                                    class="form_input" style="width: 100%;"
                                    <?= ($alias_gilda !== null && $_SESSION['admin'] != 1) ? 'readonly' : '' ?>/>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Pulsanti di invio -->
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 20px;">
                            <input type="hidden" name="op" value="modify"/>
                            <input type="hidden" name="pg" value="<?= gdrcd_filter('get', $_REQUEST['pg']) ?>"/>
                            <input type="submit" value="Salva scheda" class="form_submit"/>
                        </td>
                    </tr>
                    
                </table>
            </form>
        </div>

    <?php
        
    }//if?>


    </div>

    <!-- Torna indietro -->
    <div class="link_back">
        <a href="main.php?page=scheda&pg=<?=gdrcd_filter('url', $_REQUEST['pg'])?>"><?=gdrcd_filter('out', $MESSAGE['interface']['sheet']['link']['back'])?></a>
    </div>
</div><!-- pagina -->