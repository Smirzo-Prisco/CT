<?php

/*Livelli di accesso utente*/
define('DELETED', -1);
define('USER', 0);
define('SUPERUSER', 4);
define('MODERATOR', 3);
define('GAMEMASTER', 2);
define('GUILDMODERATOR', 1);

/*Codici di log*/
define('ACCOUNTMULTIPLO', 1);
define('BLOCKED', 2);
define('BONIFICO', 3);
define('DELETEPG', 4);
define('CHANGEDNAME', 5);
define('LOGGEDIN', 6);
define('ERRORELOGIN', 7);
define('NUOVOLAVORO', 8);
define('DIMISSIONE', 9);
define('CHANGEDROLE', 10);
define('CHANGEDPASS', 11);
define('PX', 12);
define('STIPENDIO', 13);

/*Stati di disponibilità*/
define('NONDISPONIBILE', 0);
define('DISPONIBILE', 1);
define('SOLOSUINVITO', 2);

/*Tipi di forum VECCHI 
define('INGIOCO', 0);
define('PERTUTTI', 1);
define('SOLORAZZA', 2);
define('SOLOGILDA', 3);
define('SOLOMASTERS', 4);
define('SOLOMODERATORS', 5);
define('SOLOMESTIERE', 6); */

/*Tipi di forum NUOVI */
define('ONGAME', 0);
define('INFO', 1);
define('COMUNICAZIONI', 2);
define('PERTUTTI', 3);
define('SOLORAZZA', 4);
define('SOLOGILDA', 5);
define('SOLOMESTIERE', 6); 
define('SOLOMASTERS', 7);
define('SOLOMODERATORS', 8);
define('SOLOGUIDES', 9);
define('SOLOCAPOGILDA', 10);
define('SOLOCAPOMESTIERI', 11);
define('SOLOADMIN', 12);

/*Posizione degli oggetti*/
define('INVENTARIO', 0);
define('ZAINO', 1);
define('MANODX', 2);
define('MANOSX', 3);
define('TORSO', 4);
define('GAMBE', 5);
define('PIEDI', 6);
define('TESTA', 7);
define('ANELLO', 8);
define('COLLO', 9);

/*Stati della mappa*/
define('INVIAGGIO', -1);

/**
 * Livelli di filtro html
 */
define('HTML_FILTER_BASE', 0);
define('HTML_FILTER_HIGH', 1);

/*Vettori globali dei parametri*/
$PARAMETER = array();
$MESSAGES = array();
