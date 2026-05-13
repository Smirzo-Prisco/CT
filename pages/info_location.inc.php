<?php
/**
 * info_location.inc.php — contenitore per il componente React InfoLocation
 *
 * Fornisce il div #info-location-container su cui React monterà
 * il componente InfoLocation.jsx che mostra immagine del luogo,
 * nome, anno, stato, descrizione e icona breaking news.
 * Si aggiorna automaticamente via socket 'users:update' quando
 * il personaggio cambia stanza o mappa.
 */
?>
<div id="info-location-container"></div>
