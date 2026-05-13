<?php
/**
 * link_menu.inc.php — contenitore per il componente React LinkMenu
 *
 * Passa come props le voci di menu da config.inc.php e il flag gotomap_list.
 * LinkMenu.jsx gestisce il select navigazione (aggiornato via socket) e
 * i link del menu con hover effect.
 */

$mkey = !empty($params['menu_key']) ? $params['menu_key'] : 'menu';

// Costruisce l'array di voci di menu da passare come props a React
$menuItems = [];
if (!empty($PARAMETERS[$mkey])) {
    foreach ($PARAMETERS[$mkey] as $key => $link_menu) {
        if (!empty($link_menu['url'])) {
            $menuItems[] = [
                'key'                => $key,
                'url'                => $link_menu['url'],
                'text'               => $link_menu['text']               ?? '',
                'image_file'         => $link_menu['image_file']         ?? '',
                'image_file_onclick' => $link_menu['image_file_onclick'] ?? '',
            ];
        }
    }
}

$menuTitle   = $PARAMETERS['names']['gamemenu'][$mkey] ?? '';
$theme       = $PARAMETERS['themes']['current_theme'];
$showGotomap = ($PARAMETERS['mode']['gotomap_list'] === 'ON' && empty($params['no_gotomap_list']));
?>
<div id="link-menu-container"></div>
<script>
/**
 * Monta LinkMenu con i dati di configurazione da PHP.
 * Usa uno script inline (non ct:ready) perché ha bisogno dei props dalla sessione.
 * Attende ct:ready prima di montare per garantire che il bundle sia caricato.
 */
document.addEventListener('ct:ready', function() {
    CT.mount('LinkMenu', 'link-menu-container', {
        menuItems:   <?= json_encode($menuItems) ?>,
        menuTitle:   <?= json_encode($menuTitle) ?>,
        theme:       <?= json_encode($theme) ?>,
        showGotomap: <?= $showGotomap ? 'true' : 'false' ?>
    });
});
</script>
