<?php
$dont_check = true;
$check_for_update = false;

require_once 'config.inc.php';

if ($PARAMETERS['settings']['protection'] == 'ON') {
    require 'protezione.php';
}

require 'header.inc.php';
require 'includes/credits.inc.php';

include 'themes/' . $PARAMETERS['themes']['current_theme'] . '/home/iscrizione.php';

require 'footer.inc.php';
