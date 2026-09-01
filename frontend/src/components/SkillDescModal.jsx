/**
 * SkillDescModal.jsx — Modale globale "descrizione abilità" (link "Leggi"
 * nelle liste skill di chat, scheda personaggio e mercato abilità).
 *
 * Sostituisce il vecchio iframe (#id01 in layouts/left-right_frames.php +
 * skill_desc.proc.php) e la copia duplicata in MercatoAbilita.jsx
 * (#skill-modal). Un solo componente, montato una volta in header.inc.php,
 * pilotato da qualunque punto (React o PHP legacy via onclick) con
 * window.CT.openSkillDesc(id) oppure window.CT.openSkillDesc({ nome, descrizione })
 * quando il dato è già noto in pagina (nessun fetch).
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useCallback } from 'react'
import { createPortal } from 'react-dom'
import styles from './SkillDescModal.module.css'

export default function SkillDescModal() {
    const [open, setOpen] = useState(false)
    const [loading, setLoading] = useState(false)
    const [data, setData] = useState(null)
    const [error, setError] = useState(null)

    const close = useCallback(() => setOpen(false), [])

    useEffect(() => {
        const onOpen = e => {
            const detail = e.detail || {}
            setError(null)
            setOpen(true)

            if (detail.id) {
                setLoading(true)
                setData(null)
                fetch(`pages/api_global.php?op=getSkillDesc&id=${encodeURIComponent(detail.id)}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) setData({ nome: d.nome, descrizione: d.descrizione })
                        else setError(d.message || 'Abilità non trovata')
                    })
                    .catch(() => setError('Errore di rete'))
                    .finally(() => setLoading(false))
            } else {
                setData({ nome: detail.nome, descrizione: detail.descrizione })
            }
        }

        window.addEventListener('ct:skill-desc-open', onOpen)
        return () => window.removeEventListener('ct:skill-desc-open', onOpen)
    }, [])

    useEffect(() => {
        if (!open) return
        const onKey = e => { if (e.key === 'Escape') close() }
        document.addEventListener('keydown', onKey)
        return () => document.removeEventListener('keydown', onKey)
    }, [open, close])

    if (!open) return null

    return createPortal(
        <div className={styles.backdrop} onClick={close}>
            <div className={styles.modal} onClick={e => e.stopPropagation()}>
                <div className={styles.header}>
                    <span>{data?.nome || (loading ? 'Caricamento…' : 'Descrizione abilità')}</span>
                    <button className={styles.close} onClick={close} aria-label="Chiudi">✕</button>
                </div>
                <div className={styles.content}>
                    {loading && <p className={styles.hint}>Caricamento…</p>}
                    {error && <p className={styles.hint}>{error}</p>}
                    {!loading && !error && (
                        data?.descrizione
                            ? <div dangerouslySetInnerHTML={{ __html: data.descrizione }} />
                            : <p className={styles.hint}>Nessuna descrizione</p>
                    )}
                </div>
            </div>
        </div>,
        document.body
    )
}
