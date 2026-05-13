/**
 * PresentiEstesi.jsx
 *
 * Componente React per la pagina "Presenti Estesi" (main.php?page=presenti_estesi).
 *
 * Mostra la lista completa di tutti i personaggi online, raggruppati per:
 *   1. Mappa (es. "Crystal Tokyo", "Lunare")
 *   2. Stanza (es. "Piazza Centrale") — con link per teletrasportarsi
 *
 * Per ogni personaggio mostra:
 *   - Avatar (url_img_chat)
 *   - Link SMS per messaggi privati
 *   - Icona razza
 *   - Icona famiglia / inclinazione / gilda
 *   - Icona mestiere
 *   - Nome e cognome (con link alla scheda)
 *   - Cariche staff (admin, moderatore, master, guida, grafico)
 *
 * Aggiornamento real-time:
 *   - Al mount chiama api_map.php?op=presenti_estesi
 *   - Si ri-renderizza automaticamente ad ogni evento socket 'users:update'
 *     (emesso quando qualcuno entra/esce da una stanza o cambia mappa)
 *
 * Montaggio: via ct:ready su #presenti-estesi-container in presenti_estesi.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'

/**
 * Icone dello staff con etichetta e percorso immagine.
 * L'ordine rispecchia quello della vecchia implementazione PHP.
 */
const STAFF_ICONS = [
    { key: 'admin',      src: 'themes/crystal/imgs/staff/Admin.png',      title: 'Coordinatore' },
    { key: 'moderatore', src: 'themes/crystal/imgs/staff/Moderatore.png', title: 'Moderatore'   },
    { key: 'master',     src: 'themes/crystal/imgs/staff/Master.png',     title: 'Master'        },
    { key: 'guida',      src: 'themes/crystal/imgs/staff/Guida.png',      title: 'Guida'         },
    { key: 'grafico',    src: 'themes/crystal/imgs/staff/Grafico.png',    title: 'Grafico'       },
]

/**
 * Raggruppa un array di utenti in una struttura annidata:
 *   { nomeMappa: { nomeStanza: [utente, ...], ... }, ... }
 *
 * L'ordine rispecchia già quello restituito dall'API (ordinato per mappa, stanza, nome).
 *
 * @param {Array} users - Array di oggetti utente restituito dall'API
 * @returns {Object} Struttura annidata mappa → stanza → utenti
 */
function groupUsers(users) {
    const grouped = {}
    for (const u of users) {
        const mappa  = u.mappa  || '(mappa sconosciuta)'
        const stanza = u.stanza || '(stanza sconosciuta)'
        if (!grouped[mappa])         grouped[mappa]         = {}
        if (!grouped[mappa][stanza]) grouped[mappa][stanza] = []
        grouped[mappa][stanza].push(u)
    }
    return grouped
}

/**
 * Riga singola di un personaggio nella tabella.
 *
 * @param {Object} props.user - Oggetto utente restituito dall'API
 */
function UserRow({ user }) {
    /**
     * Apre la finestra popup per l'invio di un messaggio privato.
     * Replica il comportamento del vecchio onclick inline PHP.
     */
    const openSms = () => {
        window.open(
            `pages/mex_privati/multi_message.php?destinatari=${encodeURIComponent(user.nome)}`,
            'titolo',
            'width=650,height=600,resizable,status,scrollbars=1,location'
        )
    }

    return (
        <tr className="presente">

            {/* Colonna 1: avatar del personaggio */}
            <td width="5%">
                <img width="50" height="50" src={user.url_img_chat} alt={user.nome} />
            </td>

            {/* Colonna 2: link per messaggio privato */}
            <td>
                <a href="#" onClick={e => { e.preventDefault(); openSms() }}>
                    <img src="themes/crystal/imgs/presenti/sms_presenti.png" alt="Invia SMS" />
                </a>
            </td>

            {/* Colonna 3: icona razza */}
            <td>
                <img
                    src={user.razza_img}
                    alt={user.razza_nome}
                    title={user.razza_nome}
                />
            </td>

            {/* Colonna 4: icona famiglia / inclinazione / gilda */}
            <td>
                {user.gruppo_img && (
                    <img
                        width="25" height="25"
                        src={user.gruppo_img}
                        alt={user.gruppo_nome}
                        title={user.gruppo_nome}
                    />
                )}
            </td>

            {/* Colonna 5: icona mestiere */}
            <td>
                {user.mestiere_img && (
                    <img
                        width="25" height="25"
                        src={user.mestiere_img}
                        alt={user.mestiere_nome}
                        title={user.mestiere_nome}
                    />
                )}
            </td>

            {/* Colonna 6: nome e cognome con link alla scheda */}
            <td>
                <a
                    href={`main.php?page=scheda&pg=${encodeURIComponent(user.nome)}`}
                    className={`link_sheet gender_${user.sesso}`}
                >
                    {user.nome}{user.cognome ? ` ${user.cognome}` : ''}
                    {/* Indica i personaggi invisibili allo staff */}
                    {user.is_invisible && <em> (inv)</em>}
                </a>
            </td>

            {/* Colonna 7: icone cariche staff */}
            <td>
                {STAFF_ICONS.filter(ic => user.staff[ic.key]).map(ic => (
                    <img
                        key={ic.key}
                        src={ic.src}
                        width="20" height="20"
                        title={ic.title}
                        alt={ic.title}
                    />
                ))}
            </td>
        </tr>
    )
}

/**
 * Componente principale: tabella completa dei presenti estesi.
 */
export default function PresentiEstesi() {

    /** Lista completa degli utenti online (struttura flat restituita dall'API) */
    const [users, setUsers]   = useState([])

    /** Numero totale di utenti online (inclusi invisibili per lo staff) */
    const [total, setTotal]   = useState(0)

    /** true durante il primo caricamento, per mostrare un indicatore di attesa */
    const [loading, setLoading] = useState(true)

    /**
     * Recupera la lista aggiornata dall'API.
     * Chiamato al mount e ad ogni evento socket 'users:update'.
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
                console.error('[PresentiEstesi] Errore nel recupero presenti:', err)
                setLoading(false)
            })
    }, [])

    useEffect(() => {
        // Caricamento iniziale al mount del componente
        fetchPresenti()

        // Aggiorna la lista ogni volta che qualcuno entra/esce da una stanza.
        // 'users:update' è emesso da api_map.php nei case move e changemap,
        // e da main.php quando il personaggio cambia stanza o mappa.
        const sock = window.ctSocket
        if (sock) sock.on('users:update', fetchPresenti)

        // Cleanup al dismount: rimuove il listener per evitare memory leak
        return () => { if (sock) sock.off('users:update', fetchPresenti) }
    }, [fetchPresenti])

    // --- Rendering ---

    if (loading) {
        return <div style={{ padding: '20px', color: '#aaa' }}>Caricamento presenti...</div>
    }

    // Raggruppa gli utenti per mappa e stanza per il rendering gerarchico
    const grouped = groupUsers(users)

    return (
        <div className="presenti_estesi">

            {/* Tabella principale */}
            <table className="customTable">
                <thead>
                    <tr>
                        {/* Header con conteggio totale — si aggiorna automaticamente */}
                        <th colSpan="7" style={{ fontSize: '12px', color: '#8f8f8f', fontFamily: "'DejaVu Serif'" }}>
                            PRESENTI: {total}
                        </th>
                    </tr>
                </thead>
                <tbody>

                    {/* Riga con le intestazioni delle colonne */}
                    <tr className="second_header">
                        <td>AVATAR</td>
                        <td>SMS</td>
                        <td>RAZZA</td>
                        <td>FAMIGLIA</td>
                        <td>LAVORO</td>
                        <td>NOME E COGNOME</td>
                        <td>CARICHE</td>
                    </tr>

                    {/* Iterazione per mappa */}
                    {Object.entries(grouped).map(([mappa, stanze]) => (
                        <>
                            {/* Riga separatore di mappa */}
                            <tr key={`mappa-${mappa}`} className="mappa">
                                <td colSpan="8" style={{ textTransform: 'uppercase' }}>
                                    {mappa}
                                </td>
                            </tr>

                            {/* Iterazione per stanza all'interno della mappa */}
                            {Object.entries(stanze).map(([stanza, utenti]) => (
                                <>
                                    {/* Riga separatore di stanza con link per teletrasportarsi */}
                                    <tr key={`stanza-${mappa}-${stanza}`} className="third_header">
                                        <td colSpan="8" style={{ textTransform: 'uppercase' }}>
                                            <a href={`main.php?dir=${utenti[0].ultimo_luogo}&map_id=${utenti[0].ultima_mappa}`}>
                                                {stanza}
                                            </a>
                                        </td>
                                    </tr>

                                    {/* Righe utente per questa stanza */}
                                    {utenti.map(u => (
                                        <UserRow key={u.nome} user={u} />
                                    ))}
                                </>
                            ))}
                        </>
                    ))}

                    {/* Messaggio se non ci sono utenti online */}
                    {users.length === 0 && (
                        <tr>
                            <td colSpan="7" style={{ textAlign: 'center', padding: '20px', color: '#666', fontStyle: 'italic' }}>
                                Nessun utente online
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    )
}
