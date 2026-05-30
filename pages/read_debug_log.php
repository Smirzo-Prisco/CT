<?php
/** Legge il log di debug generato dallo shutdown handler di api_mestiere.php. SOLO DEBUG — da rimuovere a fine debug. */
session_start();
if (empty($_SESSION['login'])) { echo 'auth required'; exit; }
session_write_close();
header('Content-Type: text/plain; charset=utf-8');
$f = __DIR__ . '/mestiere_debug.log';
if (!file_exists($f)) { echo 'log non trovato'; exit; }
echo file_get_contents($f);
