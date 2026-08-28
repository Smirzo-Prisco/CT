<?php
/**
 * api_gestione.php — API JSON per il pannello di gestione staff
 *
 * Endpoint:
 *   ?op=menu — Restituisce il menu della dashboard in base ai permessi della
 *              sessione corrente e gli ultimi iscritti.
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
        // L'ordine dei blocchi sotto è l'ordine di visualizzazione delle card.
        $menu = [];

        // 1. GESTIONE PERSONAGGI
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

        // 2. GILDE E MESTIERI (razze narrative — tabella gilda — + mestieri/gilde
        // giocatore). Card unica, icona presa da "Mestieri e Gilde" (era già
        // quella del card mestieri). Statuti mestiere non più linkati da qui:
        // azione icona per riga nella tabella Mestieri di GestioneMestieri.jsx.
        if ($perms['admin'] || $perms['master'] || $perms['capogilda']) {
            $voci = [];
            if ($perms['admin'] || $perms['capogilda'])
                $voci[] = ['label' => 'Nuove razze', 'url' => 'gestione.php?page=gestione_gilde'];
            if ($perms['admin'])
                $voci[] = ['label' => 'Mestieri e Gilde', 'url' => 'gestione.php?page=gestione_mestieri'];
            if (!empty($voci))
                $menu[] = ['key' => 'mestieri', 'label' => 'Mestieri e Gilde', 'icon' => 'fa-briefcase', 'voci' => $voci];
        }

        // 3. STRUMENTI (solo admin)
        // Il ripristino di un account cancellato ora e' un'icona diretta nella
        // lista di Gestione pg -> Personaggi (filtro "Eliminati"), non piu' uno
        // strumento separato qui — vedi conversazione di progetto del 2026-07-31.
        // Manutenzione spostata sotto la card Log (voce "Tutti i log").
        if ($perms['admin']) {
            $menu[] = ['key' => 'strumenti', 'label' => 'Strumenti', 'icon' => 'fa-wrench', 'voci' => [
                ['label' => 'Assegna ruoli apicali', 'url' => 'gestione.php?page=gestione_nomine'],
                ['label' => 'Bacheche',              'url' => 'gestione.php?page=gestione_bacheche'],
                ['label' => 'Luoghi',                'url' => 'gestione.php?page=gestione_luoghi'],
                ['label' => 'Mappa',                 'url' => 'gestione.php?page=gestione_mappe'],
                ['label' => 'Regolamento',           'url' => 'gestione.php?page=gestione_regolamento'],
            ]];
        }

        // 4. LOG — "Richiesta log chat" e "Log chat" rimosse (vedi conversazione
        // di progetto del 2026-08-28); "Manutenzione" spostata qui sotto "Tutti i log"
        if ($perms['admin']) {
            $menu[] = ['key' => 'log', 'label' => 'Log', 'icon' => 'fa-file-lines', 'voci' => [
                ['label' => 'Tutti i log',  'url' => 'gestione.php?page=log'],
                ['label' => 'Manutenzione', 'url' => 'gestione.php?page=gestione_manutenzione'],
            ]];
        }

        // 5. OGGETTI — admin e master (i dipendenti di un mestiere con negozio,
        // es. Shirokuro Magic Shop, hanno un punto d'ingresso proprio: il
        // pulsante "Gestisci oggetti del mestiere" nel dettaglio del proprio
        // mestiere — ScegliMestiere.jsx, non passa da qui). Vedi conversazione
        // di progetto del 2026-08-24: card riabilitata dopo il redesign di
        // gestione_oggetti.inc.php (vista ristretta per i soli dipendenti).
        if ($perms['admin'] || $perms['master']) {
            $voci = [['label' => 'Oggetti', 'url' => 'gestione.php?page=gestione_oggetti']];
            if ($perms['admin']) {
                $voci[] = ['label' => 'Ricarica oggetto', 'url' => 'gestione.php?page=oggetto_ricarica'];
                $voci[] = ['label' => 'Tipi di oggetto', 'url' => 'gestione.php?page=gestione_tipi&types=items'];
            }
            $menu[] = ['key' => 'oggetti', 'label' => 'Oggetti', 'icon' => 'fa-box', 'voci' => $voci];
        }

        // 6. Ultimi iscritti (admin, master, moderatore)
        if ($perms['admin'] || $perms['master'] || $perms['moderatore']) {
            $ultimi_res    = gdrcd_query(
                "SELECT nome, data_iscrizione FROM personaggio
                 WHERE " . sqlPgAttivo() . " AND sesso != 'b'
                 ORDER BY data_iscrizione DESC LIMIT 5",
                'result'
            );
            $voci_iscritti = [];
            while ($u = gdrcd_query($ultimi_res, 'fetch')) {
                $voci_iscritti[] = [
                    'label' => $u['nome'] . ' — ' . date('d/m/Y', strtotime($u['data_iscrizione'])),
                    'url'   => 'main.php?page=scheda&pg=' . urlencode($u['nome']),
                ];
            }
            gdrcd_query($ultimi_res, 'free');
            if (!empty($voci_iscritti)) {
                $menu[] = ['key' => 'ultimi_iscritti', 'label' => 'Ultimi iscritti', 'icon' => 'fa-user-plus', 'voci' => $voci_iscritti];
            }
        }

        // 7. GILDA (giocatore) — chiunque non abbia già una gilda (gilda_giocatore_limit permettendo)
        // o sia capo=1 di quella che ha già, non solo lo staff. Tabella dedicata
        // clgpersonaggioaffiliazione: scollegata dal mestiere vero, un personaggio può avere entrambi
        $login_esc = gdrcd_filter('in', $_SESSION['login']);
        $mia_gilda_row = gdrcd_query(
            "SELECT rm.capo FROM clgpersonaggioaffiliazione cpm
             JOIN ruolo_mestiere rm ON cpm.id_ruolo = rm.id_ruolo
             JOIN mestiere m ON rm.mestiere = m.id_mestiere
             WHERE cpm.personaggio = '$login_esc' AND m.tipo != 1 LIMIT 1"
        );
        $voce_mia_gilda = null;
        if ($mia_gilda_row) {
            // Capo: editor completo. Membro semplice: vista di sola lettura
            // (MiaGilda.jsx la supporta già, mancava solo il punto d'ingresso da qui)
            $voce_mia_gilda = ((int)$mia_gilda_row['capo'] === 1) ? 'Gestisci la tua gilda' : 'La mia gilda';
        } else {
            $affiliazioni = (int)gdrcd_query("SELECT COUNT(*) AS n FROM clgpersonaggioaffiliazione WHERE personaggio = '$login_esc'")['n'];
            if ($affiliazioni < (int)$PARAMETERS['settings']['gilda_giocatore_limit']) {
                $voce_mia_gilda = 'Crea una gilda';
            }
        }
        if ($voce_mia_gilda !== null) {
            $menu[] = ['key' => 'mia_gilda', 'label' => 'Gilda', 'icon' => 'fa-shield-halved', 'voci' => [
                ['label' => $voce_mia_gilda, 'url' => 'main.php?page=mia_gilda'],
            ]];
        }

        // 8. OLD — voci superate dal nuovo sistema (nuove razze / gilde giocatore),
        // disattivate (non cliccabili) ma tenute visibili per riferimento storico
        if ($perms['admin']) {
            $menu[] = ['key' => 'old', 'label' => 'OLD', 'icon' => 'fa-box-archive', 'voci' => [
                ['label' => 'Famiglie indipendenti', 'url' => 'gestione.php?page=gestione_gilde&op=edit&id_record=-1', 'disabled' => true],
                ['label' => 'Correnti',              'url' => 'gestione.php?page=gestione_tipi&types=guilds',          'disabled' => true],
                ['label' => 'Reliquie',              'url' => 'gestione.php?page=punti_png',                          'disabled' => true],
                ['label' => 'Razze e spiriti',       'url' => 'gestione.php?page=gestione_razze',                     'disabled' => true],
            ]];
        }

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
