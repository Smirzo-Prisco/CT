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
import { createPortal } from 'react-dom'
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
 * Icona con popup al click/tap del proprio ALT — il title nativo (hover)
 * non raggiunge chi naviga da mobile, che non ha un vero "hover". iconKey
 * deve essere univoca nella pagina: usata per sapere quale popup e' aperto
 * e per chiuderlo ri-cliccando la stessa icona. Il popup vero e proprio non
 * viene renderizzato qui (vedi PopupPortal in PresentiEstesi): serve solo
 * la posizione dell'icona al click, il contenuto lo decide il padre.
 */
function IconWithPopup({ iconKey, openPopup, onOpen, ...imgProps }) {
    return (
        <img
            {...imgProps}
            onClick={e => {
                e.stopPropagation()
                if (openPopup?.key === iconKey) onOpen(null)
                else onOpen({ key: iconKey, rect: e.currentTarget.getBoundingClientRect(), kind: 'text', text: imgProps.alt })
            }}
            style={{ cursor: 'pointer' }}
        />
    )
}

/** Colori del pallino stato — rosso automatico (in giocata), giallo/verde a scelta. */
const STATO_COLORI = { rosso: '#e74c3c', giallo: '#f1c40f', verde: '#4caf50' }

/** Persistenza dell'espansione colonna stato (dot+testo vs solo dot) fra le sessioni. */
const STATO_EXPANDED_KEY = 'ct-presenti-stato-expanded'

function loadStatoExpanded() {
    try {
        const v = localStorage.getItem(STATO_EXPANDED_KEY)
        return v === null ? true : v === '1'
    } catch {
        return true
    }
}

/** Ricava il colore/etichetta del pallino: il rosso (in giocata) e' sempre automatico. */
function statoDelPallino(user) {
    if (user.in_role) return { colore: 'rosso', label: 'In giocata' }
    if (user.stato_pallino === 'occupato') return { colore: 'giallo', label: 'Occupato' }
    return { colore: 'verde', label: 'Libero' }
}

/**
 * Contenuto del form di modifica del proprio stato (solo sulla propria
 * riga): scelta libero/occupato (il rosso resta automatico, non
 * selezionabile) + nota breve. Salva su api_map.php?op=set_stato —
 * l'aggiornamento agli altri arriva via socket 'presenti:update' (gia'
 * ascoltato dal componente padre), non serve un refetch esplicito qui.
 */
function StatoEditForm({ user, onClose }) {
    const [stato, setStato] = useState(user.stato_pallino === 'occupato' ? 'occupato' : 'libero')
    const [nota, setNota]   = useState(user.nota || '')
    const [saving, setSaving] = useState(false)

    const salva = () => {
        setSaving(true)
        fetch('pages/api_map.php?op=set_stato', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ stato_pallino: stato, nota }),
        })
            .then(r => r.json())
            .then(d => { if (d.success) onClose() })
            .catch(() => {})
            .finally(() => setSaving(false))
    }

    return (
        <>
            {user.in_role && (
                <div className="presenti-stato-popup__hint">Sei in giocata: il pallino resta rosso finché non finisce.</div>
            )}
            <div className="presenti-stato-popup__choice">
                <button type="button" className={stato === 'libero' ? 'is-active' : ''} onClick={() => setStato('libero')}>
                    <span style={{ background: STATO_COLORI.verde }} /> Libero
                </button>
                <button type="button" className={stato === 'occupato' ? 'is-active' : ''} onClick={() => setStato('occupato')}>
                    <span style={{ background: STATO_COLORI.giallo }} /> Occupato
                </button>
            </div>
            <input
                type="text"
                value={nota}
                onChange={e => setNota(e.target.value.slice(0, 25))}
                placeholder="Nota breve (max 25 caratteri)"
                maxLength={25}
            />
            <button type="button" className="presenti-stato-popup__save" onClick={salva} disabled={saving}>
                {saving ? 'Salvataggio…' : 'Salva'}
            </button>
        </>
    )
}

/**
 * Riga della tabella per un singolo personaggio.
 *
 * @param {Object}   props.user      - Dati utente restituiti dall'API
 * @param {boolean}  props.isStaff   - true se il viewer è staff
 * @param {Object?}  props.openPopup - { key, rect, kind, ... } del popup aperto (condiviso fra le righe)
 * @param {Function} props.onOpen    - apre/chiude il popup (null per chiudere)
 * @param {boolean}  props.statoExpanded - true se la colonna stato mostra anche il testo
 */
function UserRow({ user, isStaff, openPopup, onOpen, statoExpanded }) {
    /** Naviga alla pagina DM con il destinatario pre-selezionato */
    const openSms = () => window.CT.navigate(`main.php?page=messages_center&to=${encodeURIComponent(user.nome)}`)

    const morto  = user.salute === 0
    const isOwn  = user.nome === (window.CT_USER?.login ?? '')
    const statoKey = `${user.nome}-stato`
    const { colore, label } = statoDelPallino(user)

    return (
        <tr className={`presente${!user.disponibile ? ' presente-assente' : ''}${morto ? ' pg-morto' : ''}`}>

            {/* Pallino stato: rosso automatico (in giocata) o giallo/verde a
                scelta manuale. Click sulla propria riga apre il form di
                modifica, sulle altre mostra la nota impostata (se c'è). */}
            <td
                title={user.nota ? `${label} — ${user.nota}` : label}
                onClick={e => {
                    e.stopPropagation()
                    if (openPopup?.key === statoKey) { onOpen(null); return }
                    const rect = e.currentTarget.getBoundingClientRect()
                    onOpen(isOwn
                        ? { key: statoKey, rect, kind: 'stato', user }
                        : { key: statoKey, rect, kind: 'text', text: user.nota || 'Nessuna nota' })
                }}
                style={{
                    textAlign: statoExpanded ? 'left' : 'center',
                    cursor: 'pointer',
                    whiteSpace: 'nowrap',
                }}
            >
                <span
                    style={{
                        display: 'inline-block',
                        width: '10px',
                        height: '10px',
                        borderRadius: '50%',
                        backgroundColor: STATO_COLORI[colore],
                        verticalAlign: 'middle',
                        marginRight: statoExpanded ? '6px' : 0,
                    }}
                />
                {statoExpanded && user.nota && <span style={{ verticalAlign: 'middle' }}>{user.nota}</span>}
            </td>

            {/* Avatar del personaggio — grayscale se morto via CSS su .pg-morto */}
            <td width="10%" style={{ textAlign: 'center' }}>
                <img className="presenti-avatar" width="50" height="50" src={user.url_img_chat} alt={user.nome} />
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
                    <IconWithPopup iconKey={`${user.nome}-gruppo`} openPopup={openPopup} onOpen={onOpen}
                        width="25" height="25" src={user.gruppo_img} alt={user.gruppo_nome} title={user.gruppo_nome} />
                )}
            </td>

            {/* Icona mestiere */}
            <td style={{ textAlign: 'center' }}>
                {user.mestiere_img && (
                    <IconWithPopup iconKey={`${user.nome}-mestiere`} openPopup={openPopup} onOpen={onOpen}
                        width="25" height="25" src={user.mestiere_img} alt={user.mestiere_nome} title={user.mestiere_nome} />
                )}
            </td>

            {/* Icona grado di gilda — colonna separata dal mestiere: le gilde giocatore
                riusano la stessa struttura dati dei mestieri, ma sono concettualmente diverse */}
            <td style={{ textAlign: 'center' }}>
                {user.gilda_img && (
                    <IconWithPopup iconKey={`${user.nome}-gilda`} openPopup={openPopup} onOpen={onOpen}
                        width="25" height="25" src={user.gilda_img} alt={user.gilda_nome} title={user.gilda_nome} />
                )}
            </td>

            {/* Nome con link alla scheda — il cognome non viene piu' mostrato qui,
                solo il nome (richiesta esplicita, la scheda resta comunque completa).
                presenti-nome-cell: l'opacita' di riga assente si applica solo qui,
                non a icone/avatar (vedi _presenti.scss). */}
            <td className="presenti-nome-cell">
                {morto && <i className="fa-solid fa-skull pg-morto-icon" title="Morto" />}
                <a href={`main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`} className={`link_sheet gender_${user.sesso}`}>
                    {user.nome}
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
                        <IconWithPopup key={ic.key} iconKey={`${user.nome}-${ic.key}`} openPopup={openPopup} onOpen={onOpen}
                            src={ic.src} width="20" height="20" title={ic.title} alt={ic.title} />
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
     * Popup aperto (icona ALT o form stato) — una sola alla volta, condiviso
     * fra tutte le righe: { key, rect, kind: 'text'|'stato', text?, user? }.
     * rect e' il DOMRect dell'elemento cliccato al momento del click, usato
     * per posizionare il popup via portal (vedi PopupPortal sotto).
     */
    /** Colonna stato: dot+testo (espansa) o solo dot (collassata) — persistita in localStorage. */
    const [statoExpanded, setStatoExpanded] = useState(loadStatoExpanded)
    const toggleStatoExpanded = () => {
        setStatoExpanded(prev => {
            const next = !prev
            try { localStorage.setItem(STATO_EXPANDED_KEY, next ? '1' : '0') } catch {}
            return next
        })
    }

    const [openPopup, setOpenPopup] = useState(null)
    useEffect(() => {
        if (!openPopup) return
        const close = () => setOpenPopup(null)
        document.addEventListener('click', close)
        return () => document.removeEventListener('click', close)
    }, [openPopup])

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
                        <td
                            onClick={toggleStatoExpanded}
                            title={statoExpanded ? 'Comprimi stato' : 'Espandi stato'}
                            style={{ width: statoExpanded ? '90px' : '24px', cursor: 'pointer' }}
                        >
                            <span style={{ fontSize: '2em', lineHeight: 1 }}>{statoExpanded ? '◂' : '▸'}</span>
                        </td>
                        <td>AVATAR</td>
                        <td>SMS</td>
                        <td style={{ display: 'none' }}>RAZZA ICO</td>
                        <td>RAZZA</td>
                        <td>MESTIERE</td>
                        <td>GILDA</td>
                        <td>NOME</td>
                        <td>CARICHE</td>
                    </tr>

                    {/* Iterazione per mappa — Fragment con key per evitare warning React e re-mount indesiderati */}
                    {Object.entries(grouped).map(([mappa, stanze]) => {
                        const mapId = Object.values(stanze)[0]?.[0]?.ultima_mappa
                        return (
                        <Fragment key={`mappa-${mappa}`}>

                            {/* Header mappa — stesso stile cliccabile delle stanze (third_header),
                                rimanda alla mappa stessa (main.php?page=mappaclick&map_id=...) */}
                            <tr className="third_header">
                                <td colSpan="10" className={styles.schedaUpper}>
                                    <a href={`main.php?page=mappaclick&map_id=${mapId}`}>
                                        {mappa}
                                    </a>
                                </td>
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
                                    {utenti.map(u => (
                                        <UserRow key={u.nome} user={u} isStaff={isStaff} openPopup={openPopup} onOpen={setOpenPopup} statoExpanded={statoExpanded} />
                                    ))}

                                </Fragment>
                            ))}

                        </Fragment>
                        )
                    })}

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

            {/* Popup (icona ALT o form stato) in portal su document.body: la
                tabella ha overflow-x:hidden (per non spingere la pagina in
                orizzontale sulle celle strette), che taglierebbe un popup
                posizionato solo via CSS relative/absolute all'interno — vedi
                anche il fix identico gia' fatto per la modale SMS del log
                admin. position:fixed + coordinate calcolate in JS dal rect
                dell'icona cliccata, con clamp ai bordi del viewport. */}
            {openPopup && createPortal(
                <div
                    className={`presenti-icon-popup${openPopup.kind === 'stato' ? ' presenti-stato-popup' : ''}`}
                    style={popupPosition(openPopup.rect, openPopup.kind === 'stato' ? 190 : 220)}
                    onClick={e => e.stopPropagation()}
                >
                    {openPopup.kind === 'stato'
                        ? <StatoEditForm user={openPopup.user} onClose={() => setOpenPopup(null)} />
                        : openPopup.text}
                </div>,
                document.body
            )}
        </div>
    )
}

/** Coordinate fixed per il popup: centrato sotto l'icona, clampato ai bordi del viewport. */
function popupPosition(rect, assumedWidth) {
    const left = Math.max(8, Math.min(
        rect.left + rect.width / 2 - assumedWidth / 2,
        window.innerWidth - assumedWidth - 8
    ))
    return { position: 'fixed', top: rect.bottom + 6, left }
}
