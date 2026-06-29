<?php
/**
 * api_gestione.php — API JSON per il pannello di gestione staff
 *
 * Endpoint:
 *   ?op=menu — Restituisce il menu della dashboard in base ai permessi della
 *              sessione corrente, le statistiche del sito e gli ultimi iscritti.
 *              Il filtro dei permessi è server-side: il client riceve solo le
 *              voci a cui l'utente ha effettivamente accesso.
 *
 * Permessi speciali che richiedono una query DB:
 *   custode → personaggio.id_gilda = 4
 *   magic   → personaggio.id_mestiere = 3 (Magic Shop) o 4 (Secret Pandora)
 *
 * @author Crystal Tokyo Dev
 */

session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
$handleDBConnection = gdrcd_connect();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$op = $_GET['op'] ?? '';

switch ($op) {

    // -------------------------------------------------------------------------
    // MENU — dashboard filtrata per permessi
    // -------------------------------------------------------------------------
    case 'menu':
        // Permessi da sessione + controlli DB per custode e magic
        $pg_row = gdrcd_query(
            "SELECT id_gilda, id_mestiere FROM personaggio WHERE nome = '" .
            gdrcd_filter('in', $_SESSION['login']) . "' LIMIT 1"
        );

        $perms = [
            'user'         => true,
            'admin'        => (bool)($_SESSION['admin']        ?? 0),
            'master'       => (bool)($_SESSION['master']       ?? 0),
            'moderatore'   => (bool)($_SESSION['moderatore']   ?? 0),
            'capogilda'    => (bool)($_SESSION['capogilda']    ?? 0),
            'capomestiere' => (bool)($_SESSION['capomestiere'] ?? 0),
            'guida'        => (bool)($_SESSION['guida']        ?? 0),
            // Custode: membro della gilda 4 (Custodi del Primordio)
            'custode'      => $pg_row && ((int)$pg_row['id_gilda'] === 4),
            // Magic: id_mestiere 3 (Shirokuro) o 4 (Secret Pandora)
            'magic'        => $pg_row && in_array((int)$pg_row['id_mestiere'], [3, 4]),
            'magic_tipo'   => $pg_row ? (int)$pg_row['id_mestiere'] : 0,
        ];

        $shop_name = match($perms['magic_tipo']) {
            3       => 'SHIROKURO MAGIC SHOP',
            4       => 'SECRET PANDORA',
            default => 'NEGOZIO',
        };

        // ── Costruzione del menu filtrato ────────────────────────────────
        $menu = [];

        // GESTIONE PERSONAGGI
        if ($perms['admin'] || $perms['master'] || $perms['moderatore'] || $perms['custode']) {
            $voci = [['label' => 'Personaggi', 'url' => 'gestione.php?page=gestione_personaggio']];
            if ($perms['admin'])
                $voci[] = ['label' => 'Azzera punti', 'url' => 'gestione.php?page=gestione_azzeramento_skill'];
            if ($perms['admin'] || $perms['master'])
                $voci[] = ['label' => 'Crea e assegna skill temporanee', 'url' => 'gestione.php?page=gestione_abilita_master'];
            if ($perms['admin'])
                $voci[] = ['label' => 'Bot', 'url' => 'gestione.php?page=gestione_bot'];
            $menu[] = ['key' => 'gestione_pg', 'label' => 'Gestione pg', 'icon' => 'fa-user-gear', 'voci' => $voci];
        }

        // GILDE
        if ($perms['admin'] || $perms['capogilda']) {
            $voci = [['label' => 'Gilde e gradi', 'url' => 'gestione.php?page=gestione_gilde']];
            if ($perms['admin']) {
                $voci[] = ['label' => 'Famiglie indipendenti', 'url' => 'gestione.php?page=gestione_gilde&op=edit&id_record=-1'];
                $voci[] = ['label' => 'Correnti',              'url' => 'gestione.php?page=gestione_tipi&types=guilds'];
                $voci[] = ['label' => 'Reliquie',              'url' => 'gestione.php?page=punti_png'];
            }
            $menu[] = ['key' => 'gilde', 'label' => 'Gilde', 'icon' => 'fa-users', 'voci' => $voci];
        }

        // RAZZE
        if ($perms['admin']) {
            $menu[] = ['key' => 'razze', 'label' => 'Razze', 'icon' => 'fa-users', 'voci' => [
                ['label' => 'Razze e spiriti', 'url' => 'gestione.php?page=gestione_razze'],
            ]];
        }

        // MESTIERI
        if ($perms['admin'] || $perms['master'] || $perms['capogilda']) {
            $voci = [];
            if ($perms['admin']) {
                $voci[] = ['label' => 'Mestieri',             'url' => 'gestione.php?page=gestione_mestieri'];
                $voci[] = ['label' => 'Assegna mestiere',    'url' => 'gestione.php?page=gestione_mestiere'];
                $voci[] = ['label' => 'Lavori indipendenti', 'url' => 'gestione.php?page=gestione_mestieri&op=edit&id_record=-1'];
            }
            if ($perms['admin'] || $perms['capogilda'])
                $voci[] = ['label' => 'Statuti', 'url' => 'gestione.php?page=gestione_statuti_new'];
            if (!empty($voci))
                $menu[] = ['key' => 'mestieri', 'label' => 'Mestieri', 'icon' => 'fa-briefcase', 'voci' => $voci];
        }

        // OGGETTI
        /*
        if ($perms['admin'] || $perms['moderatore'] || $perms['master'] || $perms['magic']) {
            $voci = [['label' => 'Oggetti', 'url' => 'gestione.php?page=gestione_oggetti']];
            if ($perms['admin'] || $perms['moderatore'] || $perms['master'] || $perms['magic'])
                $voci[] = ['label' => 'Ricarica oggetto', 'url' => 'gestione.php?page=oggetto_ricarica'];
            if ($perms['admin'])
                $voci[] = ['label' => 'Tipi di oggetto', 'url' => 'gestione.php?page=gestione_tipi&types=items'];
            $menu[] = ['key' => 'oggetti', 'label' => 'Oggetti', 'icon' => 'fa-box', 'voci' => $voci];
        }
        */

        // STRUMENTI (solo admin)
        if ($perms['admin']) {
            $menu[] = ['key' => 'strumenti', 'label' => 'Strumenti', 'icon' => 'fa-wrench', 'voci' => [
                ['label' => 'Assegna ruoli apicali', 'url' => 'gestione.php?page=gestione_nomine'],
                ['label' => 'Bacheche',              'url' => 'gestione.php?page=gestione_bacheche'],
                ['label' => 'Luoghi',                'url' => 'gestione.php?page=gestione_luoghi'],
                ['label' => 'Mappa',                 'url' => 'gestione.php?page=gestione_mappe'],
                ['label' => 'Regolamento',           'url' => 'gestione.php?page=gestione_regolamento'],
                ['label' => 'Manutenzione',          'url' => 'gestione.php?page=gestione_manutenzione'],
            ]];
        }

        // LOG
        if ($perms['admin'] || $perms['master'] || $perms['capomestiere'] || $perms['moderatore']) {
            $voci = [];
            if ($perms['admin'])                              $voci[] = ['label' => 'Tutti i log',        'url' => 'gestione.php?page=log'];
            if ($perms['admin'] || $perms['master'])          $voci[] = ['label' => 'Richiesta log chat', 'url' => 'gestione.php?page=richiesta_log'];
            if ($perms['admin'] || $perms['moderatore'])      $voci[] = ['label' => 'Log chat',           'url' => 'gestione.php?page=log_chat'];
            if (!empty($voci))
                $menu[] = ['key' => 'log', 'label' => 'Log', 'icon' => 'fa-file-lines', 'voci' => $voci];
        }

        // ── Statistiche sito (visibili a tutti) ──────────────────────────
        $iscritti    = (int)gdrcd_query("SELECT COUNT(nome) AS num FROM personaggio")['num'];
        $esiliati    = (int)gdrcd_query("SELECT COUNT(nome) AS num FROM personaggio WHERE esilio > NOW()")['num'];
        $bacheca     = (int)gdrcd_query("SELECT COUNT(id_messaggio) AS num FROM messaggioaraldo")['num'];
        $azioni_sett = (int)gdrcd_query("SELECT COUNT(id) AS num FROM chat WHERE ora > DATE_SUB(NOW(), INTERVAL 7 DAY)")['num'];

        $menu[] = ['key' => 'stats', 'label' => 'Informazioni', 'icon' => 'fa-chart-line', 'voci' => [
            ['label' => 'Contatta la moderazione',    'url' => 'gestione.php?page=contatta_moderazione'],
            ['label' => "Iscritti: $iscritti",        'url' => '#'],
            ['label' => "Esiliati: $esiliati",        'url' => '#'],
            ['label' => "Post in bacheca: $bacheca",  'url' => '#'],
            ['label' => "Azioni settimana: $azioni_sett", 'url' => '#'],
        ]];

        // Ultimi iscritti — nascosta

        echo json_encode([
            'success'   => true,
            'menu'      => $menu,
            'shop_name' => $shop_name,
        ]);
        break;

    // -------------------------------------------------------------------------
    // Default
    // -------------------------------------------------------------------------
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Operazione non valida']);
}

exit();
