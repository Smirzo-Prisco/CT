/**
 * PresentiEstesi.jsx
 *
 * Componente React per la pagina "Presenti Estesi" (main.php?page=presenti_estesi).
 *
 * Logica identica a OnlineUsers.jsx — stessa fetch, stesso listener socket —
 * ma con una visualizzazione a tabella raggruppata per mappa e stanza.
 *
 * Aggiornamento real-time:
 *   - Carica i dati al mount da api_map.php?op=presenti_estesi
 *   - Ri-renderizza ad ogni evento socket 'users:update'
 *
 * Note sul raggruppamento:
 *   - I pg sulla mappa aperta (ultimo_luogo = -1) hanno stanza vuota:
 *     vengono mostrati direttamente sotto l'header mappa, senza sub-header stanza
 *   - I pg in stanza hanno sia l'header mappa che l'header stanza con link
 */

import { useState, useEffect, useCallback, Fragment } from 'react'
import styles from './PresentiEstesi.module.css'

// ---------------------------------------------------------------------------
// COSTANTI
// ---------------------------------------------------------------------------

/**
 * Icone dello staff con testo alternativo e percorso immagine.
 * L'ordine rispecchia quello della vecchia implementazione PHP.
 */
const STAFF_ICONS = [
    { key: 'admin',      src: 'themes/crystal/imgs/staff/Admin.png',      title: 'Coordinatore' },
    { key: 'moderatore', src: 'themes/crystal/imgs/staff/Moderatore.png', title: 'Moderatore'   },
    { key: 'master',     src: 'themes/crystal/imgs/staff/Master.png',     title: 'Master'        },
    { key: 'guida',      src: 'themes/crystal/imgs/staff/Guida.png',      title: 'Guida'         },
    { key: 'grafico',    src: 'themes/crystal/imgs/staff/Grafico.png',    title: 'Grafico'       },
]

// ---------------------------------------------------------------------------
// FUNZIONI DI SUPPORTO
// ---------------------------------------------------------------------------

/**
 * Raggruppa l'array flat di utenti in una struttura annidata:
 *   { nomeMappa: { nomeStanza: [utente, ...] } }
 *
 * Se 'stanza' è vuota (pg sulla mappa aperta, ultimo_luogo = -1)
 * viene usata la chiave '' — il rendering salterà l'header stanza.
 *
 * @param   {Array}  users - Array restituito dall'API
 * @returns {Object} Struttura annidata mappa → stanza → utenti
 */
function groupUsers(users) {
    const grouped = {}
    for (const u of users) {
        const mappa  = u.mappa  || '(mappa sconosciuta)'
        const stanza = u.stanza || ''   // vuoto = pg sulla mappa, nessun sub-header
        if (!grouped[mappa])         grouped[mappa]         = {}
        if (!grouped[mappa][stanza]) grouped[mappa][stanza] = []
        grouped[mappa][stanza].push(u)
    }
    return grouped
}

// ---------------------------------------------------------------------------
// SUB-COMPONENTE: riga singola di un personaggio
// ---------------------------------------------------------------------------

/**
 * Riga della tabella per un singolo personaggio.
 *
 * @param {Object}  props.user    - Dati utente restituiti dall'API
 * @param {boolean} props.isStaff - true se il viewer è staff
 */
function UserRow({ user, isStaff }) {
    /** Naviga alla pagina DM con il destinatario pre-selezionato */
    const openSms = () => window.CT.navigate(`main.php?page=messages_center&to=${encodeURIComponent(user.nome)}`)

    const morto = user.salute === 0

    return (
        <tr className={`presente${!user.disponibile ? ' presente-assente' : ''}${morto ? ' pg-morto' : ''}`}>

            {/* Pallino In Role */}
            <td style={{ textAlign: 'center', width: '24px' }}>
                <span
                    title={user.in_role ? 'In giocata' : 'Non in giocata'}
                    style={{
                        display: 'inline-block',
                        width: '10px',
                        height: '10px',
                        borderRadius: '50%',
                        backgroundColor: user.in_role ? '#4caf50' : '#757575',
                    }}
                />
            </td>

            {/* Avatar del personaggio — grayscale se morto via CSS su .pg-morto */}
            <td width="10%" style={{ textAlign: 'center' }}>
                <img width="50" height="50" src={user.url_img_chat} alt={user.nome} />
            </td>

            {/* Link per messaggio privato */}
            <td style={{ textAlign: 'center' }}>
                <a href="#" onClick={e => { e.preventDefault(); openSms() }}>
                    <img src="themes/crystal/imgs/presenti/sms_presenti.png" alt="Invia SMS" />
                </a>
            </td>

            {/* Icona razza — nascosta */}
            <td style={{ display: 'none' }}>
                <img src={user.razza_img} alt={user.razza_nome} title={user.razza_nome} />
            </td>

            {/* Icona famiglia / inclinazione / gilda (rinominata Razza nell'header) */}
            <td style={{ textAlign: 'center' }}>
                {user.gruppo_img && (
                    <img width="25" height="25" src={user.gruppo_img} alt={user.gruppo_nome} title={user.gruppo_nome} />
                )}
            </td>

            {/* Icona mestiere */}
            <td style={{ textAlign: 'center' }}>
                {user.mestiere_img && (
                    <img width="25" height="25" src={user.mestiere_img} alt={user.mestiere_nome} title={user.mestiere_nome} />
                )}
            </td>

            {/* Icona grado di gilda — colonna separata dal mestiere: le gilde giocatore
                riusano la stessa struttura dati dei mestieri, ma sono concettualmente diverse */}
            <td style={{ textAlign: 'center' }}>
                {user.gilda_img && (
                    <img width="25" height="25" src={user.gilda_img} alt={user.gilda_nome} title={user.gilda_nome} />
                )}
            </td>

            {/* Nome e cognome con link alla scheda */}
            <td>
                {morto && <i className="fa-solid fa-skull pg-morto-icon" title="Morto" />}
                <a href={`main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`} className={`link_sheet gender_${user.sesso}`}>
                    {user.nome}{user.cognome ? ` ${user.cognome}` : ''}
                    {/* Flag visibilità — visibile solo allo staff */}
                    {user.is_invisible && <em> (inv)</em>}
                    {/* Badge bot — visibile solo allo staff, coerente con OnlineUsers */}
                    {isStaff && user.sesso === 'b' && <span className="badge-bot">B</span>}
                </a>
            </td>

            {/* Icone cariche staff */}
            <td style={{ textAlign: 'center' }}>
                <span style={{ display: 'flex', justifyContent: 'center', gap: '2px' }}>
                    {STAFF_ICONS.filter(ic => user.staff[ic.key]).map(ic => (
                        <img key={ic.key} src={ic.src} width="20" height="20" title={ic.title} alt={ic.title} />
                    ))}
                </span>
            </td>
        </tr>
    )
}

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function PresentiEstesi({ isStaff = false }) {

    /** Lista flat degli utenti online restituita dall'API */
    const [users,   setUsers]   = useState([])

    /** Totale utenti online (inclusi invisibili per lo staff) */
    const [total,   setTotal]   = useState(0)

    /** true solo durante il primissimo caricamento */
    const [loading, setLoading] = useState(true)

    /**
     * Recupera la lista aggiornata dall'API.
     * Stessa logica di OnlineUsers.jsx: chiamata semplice, nessun heartbeat
     * (questa è una pagina di sola lettura, non la frame di gioco).
     */
    const fetchPresenti = useCallback(() => {
        fetch('pages/api_map.php?op=presenti_estesi')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    setUsers(data.users)
                    setTotal(data.total)
                }
                setLoading(false)
            })
            .catch(err => {
                console.error('[PresentiEstesi] Errore:', err)
                setLoading(false)
            })
    }, [])

    useEffect(() => {
        // Caricamento iniziale
        fetchPresenti()

        const sock = window.ctSocket
        if (sock) {
            // 'users:update' — qualcuno si è mosso dentro la stessa stanza del viewer
            sock.on('users:update', fetchPresenti)

            // 'presenti:update' — evento globale (login/logout di chiunque nel gioco).
            // Inviato alla room 'global' da login.php e logout.php.
            // Necessario perché 'users:update' è specifico per stanza: se il pg che
            // fa logout è in una stanza diversa dal viewer, 'users:update' non arriva.
            sock.on('presenti:update', fetchPresenti)
        }

        // Cleanup al dismount per evitare listener orfani
        return () => {
            if (sock) {
                sock.off('users:update',   fetchPresenti)
                sock.off('presenti:update', fetchPresenti)
            }
        }
    }, [fetchPresenti])

    // --- Rendering ---

    if (loading) {
        return <div className="centered">Caricamento presenti...</div>
    }

    /** Struttura annidata { mappa: { stanza: [utenti] } } per il rendering gerarchico */
    const grouped = groupUsers(users)

    return (
        <div className="presenti_estesi">
            <table className={`customTable ${styles.fullTable}`}>
                <thead>
                    <tr>
                        {/* Header con conteggio — si aggiorna automaticamente ad ogni evento socket */}
                        <th colSpan="10" className={styles.schedaSerial}>
                            PRESENTI: {total}
                        </th>
                    </tr>
                </thead>
                <tbody>

                    {/* Intestazioni colonne */}
                    <tr className="second_header">
                        <td></td>
                        <td>AVATAR</td>
                        <td>SMS</td>
                        <td style={{ display: 'none' }}>RAZZA ICO</td>
                        <td>RAZZA</td>
                        <td>LAVORO</td>
                        <td>GILDA</td>
                        <td>NOME E COGNOME</td>
                        <td>CARICHE</td>
                    </tr>

                    {/* Iterazione per mappa — Fragment con key per evitare warning React e re-mount indesiderati */}
                    {Object.entries(grouped).map(([mappa, stanze]) => (
                        <Fragment key={`mappa-${mappa}`}>

                            {/* Header mappa */}
                            <tr className="mappa">
                                <td colSpan="10" className={styles.schedaUpper}>{mappa}</td>
                            </tr>

                            {/* Iterazione per stanza — Fragment con key, obbligatorio per liste React */}
                            {Object.entries(stanze).map(([stanza, utenti]) => (
                                <Fragment key={`stanza-${mappa}-${stanza}`}>

                                    {/*
                                      * Header stanza: mostrato solo se c'è una stanza.
                                      * Se stanza è vuota il pg è sulla mappa aperta (ultimo_luogo = -1):
                                      * in quel caso si mostra direttamente la riga utente, come nel vecchio PHP.
                                      */}
                                    {stanza && (
                                        <tr className="third_header">
                                            <td colSpan="10" className={styles.schedaUpper}>
                                                <a href={`main.php?dir=${utenti[0].ultimo_luogo}`}>
                                                    {stanza}
                                                </a>
                                            </td>
                                        </tr>
                                    )}

                                    {/* Righe utente */}
                                    {utenti.map(u => <UserRow key={u.nome} user={u} isStaff={isStaff} />)}

                                </Fragment>
                            ))}

                        </Fragment>
                    ))}

                    {/* Messaggio quando non ci sono utenti online */}
                    {users.length === 0 && (
                        <tr>
                            <td colSpan="9" style={{ textAlign: 'center', padding: '20px', color: 'var(--color-text-secondary)', fontStyle: 'italic' }}>
                                Nessun utente online
                            </td>
                        </tr>
                    )}

                </tbody>
            </table>
        </div>
    )
}
