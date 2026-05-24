/**
 * TargetSelector.jsx
 *
 * Componente React per la selezione dei bersagli nel pannello skill/armi della chat di gioco.
 *
 * Funzionalità principali:
 * - Carica la lista dei personaggi nella role attiva tramite API (api_roleSession.php?op=getRolePgs)
 * - Si aggiorna in tempo reale tramite socket.io: quando qualcuno entra o esce dalla stanza,
 *   la lista viene ricaricata automaticamente (evento 'users:update')
 * - Se un bersaglio già selezionato esce dalla stanza, viene auto-deselezionato con notifica
 * - Supporta un limite massimo di bersagli selezionabili (imposto dall'esterno via window.setMaxTargetExternal)
 * - Espone i nomi selezionati all'esterno tramite window.getSelectedNamesCallback (usato da chat.js)
 *
 * Montaggio: viene inizializzato a ct:ready e montato su #user-selection-box in frame_chat.inc.php
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback } from 'react'

export default function TargetSelector() {

    // --------------------------------------------------------------------------------------------
    // STATO DEL COMPONENTE
    // --------------------------------------------------------------------------------------------

    /** Lista dei nomi dei personaggi disponibili come bersaglio */
    const [users, setUsers] = useState([])

    /** Nomi dei personaggi attualmente selezionati come bersaglio */
    const [selectedNames, setSelectedNames] = useState([])

    /**
     * Numero massimo di bersagli selezionabili.
     * 0 = nessun limite.
     * Viene impostato dall'esterno tramite window.setMaxTargetExternal
     * (ad es. dipende dal livello dell'abilità selezionata).
     */
    const [maxTarget, setMaxTarget] = useState(1)

    // --------------------------------------------------------------------------------------------
    // REF — valori sempre aggiornati accessibili nelle closure senza re-render
    // --------------------------------------------------------------------------------------------

    /**
     * Ref che tiene sempre il valore aggiornato di selectedNames.
     * È necessario perché window.getSelectedNamesCallback viene chiamato dall'esterno
     * (da chat.js) e deve leggere la selezione corrente senza creare stale closures.
     */
    const selectedNamesRef = useRef([])

    // Sincronizza il ref ad ogni cambio di selectedNames
    useEffect(() => {
        selectedNamesRef.current = selectedNames
    }, [selectedNames])

    // --------------------------------------------------------------------------------------------
    // FETCH UTENTI — chiamata API + aggiornamento automatico della selezione
    // --------------------------------------------------------------------------------------------

    /**
     * Recupera la lista dei personaggi nella role attiva dall'API.
     * Viene chiamata:
     *   1. Al mount iniziale del componente
     *   2. Ad ogni evento socket 'users:update' (qualcuno entra/esce dalla stanza)
     *
     * Se un personaggio selezionato non è più nella lista aggiornata,
     * viene rimosso automaticamente dalla selezione e l'utente viene avvisato.
     */
    const fetchUsers = useCallback(async () => {
        try {
            const res  = await fetch('pages/api_roleSession.php?op=getRolePgs', { method: 'POST' })
            const data = await res.json()

            /** Lista aggiornata dei nomi disponibili (fallback a array vuoto in caso di errore) */
            const newUsers = data.users ?? []

            setUsers(newUsers)

            // Controlla se qualche bersaglio selezionato non è più presente
            setSelectedNames(prev => {
                // Filtra solo i nomi che esistono ancora nella lista aggiornata
                const ancora = prev.filter(n => newUsers.includes(n))

                if (ancora.length < prev.length) {
                    // Calcola chi è stato rimosso e notifica l'utente
                    const rimossi = prev.filter(n => !newUsers.includes(n))
                    alert(`Bersaglio/i rimosso/i perché uscito dalla stanza: ${rimossi.join(', ')}`)
                }

                return ancora
            })
        } catch (e) {
            console.error('[TargetSelector] Errore nel recupero dei bersagli:', e)
        }
    }, [])

    // --------------------------------------------------------------------------------------------
    // EFFETTI — socket, callback esterne, caricamento iniziale
    // --------------------------------------------------------------------------------------------

    useEffect(() => {
        // Caricamento iniziale della lista al mount del componente
        fetchUsers()

        const sock = window.ctSocket
        if (sock) {
            // 'users:update' — qualcuno entra o esce dalla stanza (movimento sulla mappa)
            sock.on('users:update', fetchUsers)

            // 'role:update' — un pg si è aggiunto o ha abbandonato la role nella stanza.
            // Emesso da api_roleSession.php nei case addPgToRole e quitRole.
            // Questo è l'evento che garantisce l'aggiornamento della lista bersagli
            // quando un nuovo giocatore si unisce alla giocata corrente.
            sock.on('role:update', fetchUsers)
        }

        /**
         * Espone la funzione di lettura dei nomi selezionati a chat.js.
         * chat.js chiama window.getSelectedNamesCallback() prima di inviare
         * skill, attacchi e dadi per sapere chi è il bersaglio.
         *
         * Usa il ref invece dello state direttamente per evitare stale closures.
         */
        window.getSelectedNamesCallback = () => selectedNamesRef.current

        /**
         * Espone la funzione di impostazione del limite massimo a chat.js.
         * Viene chiamata quando l'utente cambia abilità o livello skill,
         * per adeguare il numero di bersagli consentiti.
         *
         * Se il nuovo limite è inferiore alla selezione corrente,
         * taglia i bersagli in eccesso e avvisa l'utente.
         *
         * @param {number} limite - Numero massimo di bersagli (0 = nessun limite)
         */
        window.setMaxTargetExternal = (limite) => {
            setMaxTarget(limite)

            setSelectedNames(prev => {
                if (limite > 0 && prev.length > limite) {
                    const tagliati = prev.slice(limite)
                    alert(`Limite ridotto a ${limite}. Bersagli rimossi: ${tagliati.join(', ')}`)
                    return prev.slice(0, limite)
                }
                return prev
            })
        }

        // Cleanup al dismount: rimuove i listener socket e le callback globali
        return () => {
            if (sock) {
                sock.off('users:update', fetchUsers)
                sock.off('role:update',  fetchUsers)
            }
            window.getSelectedNamesCallback = null
            window.setMaxTargetExternal     = null
        }
    }, [fetchUsers])

    // --------------------------------------------------------------------------------------------
    // GESTIONE SELEZIONE
    // --------------------------------------------------------------------------------------------

    /**
     * Attiva o disattiva la selezione di un bersaglio al click.
     * Se il bersaglio era già selezionato, lo rimuove.
     * Se il limite massimo è già raggiunto, avvisa e non aggiunge.
     *
     * @param {string} nome - Nome del personaggio da selezionare/deselezionare
     */
    const toggle = (nome) => {
        setSelectedNames(prev => {
            // Se già selezionato → de-seleziona
            if (prev.includes(nome)) return prev.filter(n => n !== nome)

            // Se il limite è stato raggiunto → blocca e avvisa
            if (maxTarget > 0 && prev.length >= maxTarget) {
                alert(`Puoi selezionare al massimo ${maxTarget} bersagli`)
                return prev
            }

            // Altrimenti aggiungi alla selezione
            return [...prev, nome]
        })
    }

    // --------------------------------------------------------------------------------------------
    // RENDERING
    // --------------------------------------------------------------------------------------------

    // Se non ci sono bersagli disponibili, mostra un messaggio informativo
    if (!users.length) {
        return (
            <div style={{ padding: '10px', color: 'var(--color-text-muted)', fontStyle: 'italic' }}>
                Nessun bersaglio disponibile
            </div>
        )
    }

    return (
        <div>
            {/* Indicatore della selezione corrente — visibile solo quando c'è un limite attivo */}
            {maxTarget > 0 && (
                <div style={{
                    marginBottom: '10px',
                    padding: '5px 10px',
                    backgroundColor: '#fff3cd',
                    border: '1px solid #ffeaa7',
                    borderRadius: '4px',
                    fontSize: '13px',
                    color: '#856404'
                }}>
                    Selezionati: {selectedNames.length} / {maxTarget}
                </div>
            )}

            {/* Lista dei bersagli selezionabili */}
            {users.map(nome => {
                const selezionato = selectedNames.includes(nome)
                // Un bersaglio è disabilitato se il limite è attivo e la selezione è piena
                const disabilitato = maxTarget > 0 && !selezionato && selectedNames.length >= maxTarget

                return (
                    <div
                        key={nome}
                        onClick={disabilitato ? undefined : () => toggle(nome)}
                        style={{
                            padding: '8px',
                            margin: '5px 0',
                            backgroundColor: selezionato ? '#3a4f86' : disabilitato ? '#f8f9fa' : '#ffffff1a',
                            border: `1px solid ${selezionato ? '#007bff' : '#dee2e6'}`,
                            borderRadius: '4px',
                            cursor: disabilitato ? 'not-allowed' : 'pointer',
                            display: 'flex',
                            alignItems: 'center',
                            color: selezionato ? 'white' : disabilitato ? '#adb5bd' : 'inherit',
                            opacity: disabilitato ? 0.6 : 1,
                        }}
                    >
                        {/* Checkbox visiva personalizzata */}
                        <div style={{
                            width: '18px',
                            height: '18px',
                            border: `2px solid ${selezionato ? 'white' : disabilitato ? '#dee2e6' : '#adb5bd'}`,
                            borderRadius: '3px',
                            marginRight: '10px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            backgroundColor: selezionato ? '#007bff' : disabilitato ? '#e9ecef' : 'white',
                            color: 'white',
                            fontSize: '12px',
                            flexShrink: 0,
                        }}>
                            {selezionato ? '✓' : ''}
                        </div>

                        {/* Nome del bersaglio */}
                        {nome}
                    </div>
                )
            })}
        </div>
    )
}
