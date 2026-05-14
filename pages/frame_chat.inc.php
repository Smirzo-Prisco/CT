<!--
    frame_chat.inc.php

    Phase 4b: lo shell completo della chat è gestito da ChatShell.jsx via AppRouter.

    Flusso visita PHP diretta (main.php?dir=X):
      1. main.php aggiorna ultimo_luogo in DB e $_SESSION['luogo'] (linee 36-46)
      2. Carica questo file → browser riceve CSS + div#ct-app-content
      3. AppRouter monta, legge ?dir=X, renderizza ChatShell
      4. ChatShell chiama api_chat.php?op=shell → $_SESSION['luogo'] già corretto

    Flusso navigazione SPA (click stanza in MapClick o qualsiasi dir=X link):
      1. CT.navigate('main.php?dir=X') intercettato dal click interceptor
      2. AppRouter chiama api_map.php?op=move (POST) per aggiornare DB+sessione
      3. AppRouter renderizza ChatShell con key={dir}
      4. ChatShell chiama op=shell → stessa risposta del flusso PHP
-->
<link rel="stylesheet" href="../themes/crystal/mestieri.css">
<link rel="stylesheet" href="../themes/crystal/chat.css">

<!-- AppRouter legge ?dir=X e renderizza il componente ChatShell -->
<div id="ct-app-content"></div>
