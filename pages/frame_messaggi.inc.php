<?php
/**
 * frame_messaggi.inc.php — contenitore per il componente React FrameMessaggi
 *
 * Fornisce il div #frame-messaggi-container su cui React monterà
 * FrameMessaggi.jsx, che gestisce:
 *   - Meteo (oggi/ieri con toggle)
 *   - Griglia 3×3 icone di navigazione
 *   - Notifiche messaggi privati real-time (socket dm:update)
 *   - Notifiche chat off real-time (socket chatoff:update)
 *
 * Nota: frame_messaggi.js non viene più incluso perché la sua
 * logica (getMessages, getChatOff, updateMessageIcon, getChatOff)
 * è ora gestita direttamente da FrameMessaggi.jsx.
 */
?>
<div id="frame-messaggi-container"></div>
