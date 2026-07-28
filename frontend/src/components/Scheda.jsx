/**
 * Scheda.jsx — Scheda personaggio (Phase SPA)
 *
 * Legge ?pg=NomePg dalla URL corrente e carica i dati via:
 *   api_scheda.php?op=profile&pg=X
 *
 * Sezioni renderizzate:
 *   - Menu sub-sezioni (link PHP, causano reload — non ancora migrate)
 *   - Avatar + blocco profilo/statistiche
 *   - Note del Fato collassabili
 *   - Background principale
 *
 * Flag di visibilità (is_own, is_staff, is_admin, is_master) vengono
 * restituiti dall'API stessa, che li calcola server-side dalla sessione.
 *
 * I nomi delle statistiche (car2, car4, ecc.) vengono letti da
 * profile.config.stat_names, che rispecchia $PARAMETERS['names']['stats']
 * configurati nel backend.
 *
 * @author Crystal Tokyo Dev
 */

import { useState, useEffect, useRef, useCallback, forwardRef } from 'react'
import { createPortal } from 'react-dom'
import SchedaMenu from './SchedaMenu'
import styles from './Scheda.module.css'
import { extractAndScopeStyles } from '../utils/schedaStyles'

/**
 * Formatta una stringa data MySQL in formato italiano leggibile.
 * @param {string|null} dateStr
 * @returns {string}
 */
function formatDate(dateStr) {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d)) return dateStr
    return d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}

/**
 * Estrae il video ID da un URL YouTube (youtube.com/watch?v=, youtu.be/, /embed/).
 * Restituisce null se l'URL non è YouTube.
 */
function getYoutubeId(url) {
    if (!url) return null
    const patterns = [
        /[?&]v=([^&#]+)/,
        /youtu\.be\/([^?&#]+)/,
        /\/embed\/([^?&#]+)/,
    ]
    for (const re of patterns) {
        const m = url.match(re)
        if (m?.[1]) return m[1]
    }
    return null
}

/**
 * Apre il frame modale per l'invio di un SMS privato al personaggio.
 * changeFrame() è definita in left-right_frames.php ed è globale.
 * @param {string} nome - Nome del destinatario
 */
function openSmsFrame(nome) {
    window.CT.navigate(`main.php?page=messages_center&to=${encodeURIComponent(nome)}`)
}

// ---------------------------------------------------------------------------
// SOTTO-COMPONENTI
// ---------------------------------------------------------------------------

// SchedaMenu è ora in SchedaMenu.jsx (condiviso con le sotto-sezioni)

/**
 * Modale Note del Fato — portaled su document.body per evitare clip da transform.
 * Chiudibile con Esc, clic sul backdrop o il pulsante ✕.
 */
function NoteFatoModal({ nome, particolariHtml, noteFatoHtml, onClose }) {
    useEffect(() => {
        const onKey = e => { if (e.key === 'Escape') onClose() }
        document.addEventListener('keydown', onKey)
        return () => document.removeEventListener('keydown', onKey)
    }, [onClose])

    return createPortal(
        <div className={styles.noteFatoBackdrop} onClick={onClose}>
            <div className={styles.noteFatoModal} onClick={e => e.stopPropagation()}>
                <div className={styles.noteFatoHeader}>
                    <span>✦ Note del Fato — {nome}</span>
                    <button className={styles.noteFatoClose} onClick={onClose} aria-label="Chiudi">✕</button>
                </div>
                <div className={styles.noteFatoContent}>
                    {particolariHtml && (
                        <div dangerouslySetInnerHTML={{ __html: particolariHtml }} />
                    )}
                    {particolariHtml && noteFatoHtml && (
                        <hr className={styles.noteFatoSep} />
                    )}
                    {noteFatoHtml && (
                        <div dangerouslySetInnerHTML={{ __html: noteFatoHtml }} />
                    )}
                </div>
            </div>
        </div>,
        document.body
    )
}

/**
 * Blocco profilo sinistro: avatar grande del personaggio.
 */
const SchedaAvatar = forwardRef(function SchedaAvatar({ urlImg, nome }, ref) {
    return (
        <div className={styles.avatarFrame} ref={ref}>
            <img src={urlImg} alt={nome} />
        </div>
    )
})

/**
 * Blocco profilo destro: stats pubbliche + statistiche private + info.
 * stat_names proviene da profile.config.stat_names (configurazione backend).
 */
const SchedaProfilo = forwardRef(function SchedaProfilo({ profile }, ref) {
    const { nome, cognome, eta, natoa, lavoro, razza, nome_ruolo, nome_ruolo_mestiere,
            salute, salute_max, integrita, integrita_max, /* notorieta, */
            esperienza, shin, statistiche, privilegi, config } = profile
    const sn = config?.stat_names ?? {}

    // Icone staff visibili sulle cariche
    const staffIcons = [
        { key: 'admin',      file: 'Admin.png',      label: 'Admin' },
        { key: 'moderatore', file: 'Moderatore.png', label: 'Moderatore' },
        { key: 'master',     file: 'Master.png',     label: 'Master' },
        { key: 'guida',      file: 'Guida.png',      label: 'Guida' },
        { key: 'grafico',    file: 'Grafico.png',    label: 'Grafico' },
    ]

    return (
        <div className={styles.profileCard} ref={ref}>

            {/* ── PROFILO ──────────────────────────────────────────────── */}
            <div className={styles.cardSection}>
                <div className={styles.sectionLabel}>Profilo</div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>Età</span>
                    <span className={styles.infoValue}>{eta}</span>
                </div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>Luogo</span>
                    <span className={styles.infoValue}>{natoa}</span>
                </div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>Razza</span>
                    <span className={styles.infoValue}>{nome_ruolo}</span>
                </div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>Mestiere</span>
                    <span className={styles.infoValue}>{nome_ruolo_mestiere}</span>
                </div>
            </div>

            {/* ── STATISTICHE (solo proprio pg o staff) ────────────────── */}
            {statistiche && (
                <div className={styles.cardSection}>
                    <div className={styles.sectionLabel}>Statistiche</div>
                    <div className={styles.infoRow}>
                        <span className={styles.infoLabel}>
                            Livello
                            <span className="help-animated">?</span>
                            <div className="tooltip-animated">
                                <strong>Il livello si calcola in base alla somma di tutte le statistiche, se il personaggio appartiene ad una famiglia</strong><br />
                                <table className="level-table">
                                    <tbody>
                                        <tr><th>Livello</th><th>Fino a</th></tr>
                                        <tr><td>1</td><td>50</td></tr>
                                        <tr><td>2</td><td>75</td></tr>
                                        <tr><td>3</td><td>105</td></tr>
                                        <tr><td>4</td><td>140</td></tr>
                                        <tr><td>5</td><td>180</td></tr>
                                        <tr><td>6</td><td>225</td></tr>
                                        <tr><td>7</td><td>275</td></tr>
                                        <tr><td>8</td><td>330</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </span>
                        <span className={`${styles.infoValue} ${styles.infoValueAccent}`}>
                            {statistiche.livello}
                        </span>
                    </div>
                    <div className={styles.infoRow}>
                        <span className={styles.infoLabel}>Tot. Caratteristiche</span>
                        <span className={`${styles.infoValue} ${styles.infoValueBold}`}>
                            {statistiche.totale}
                        </span>
                    </div>
                    {['car8', 'car2', 'car4', 'car6'].map(k => (
                        <div key={k} className={styles.infoRow}>
                            <span className={styles.infoLabel}>{sn[k] ?? k}</span>
                            <span className={styles.infoValue}>{statistiche[k]}</span>
                        </div>
                    ))}
                </div>
            )}

            {/* ── INFO ─────────────────────────────────────────────────── */}
            <div className={styles.cardSection}>
                <div className={styles.sectionLabel}>Info</div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>{sn.hitpoints ?? 'Salute'}</span>
                    <span className={styles.infoValue}>{salute}/{salute_max}</span>
                </div>
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>{sn.integrita ?? 'Integrità'}</span>
                    <span className={styles.infoValue}>{integrita}/{integrita_max}</span>
                </div>
                {statistiche && (
                    <>
                        <div className={styles.infoRow}>
                            <span className={styles.infoLabel}>Esperienza</span>
                            <span className={styles.infoValue}>{Math.floor(esperienza ?? 0)}</span>
                        </div>
                        <div className={styles.infoRow}>
                            <span className={styles.infoLabel}>Shin</span>
                            <span className={styles.infoValue}>{Math.floor(shin ?? 0)}</span>
                        </div>
                    </>
                )}
                <div className={styles.infoRow}>
                    <span className={styles.infoLabel}>Cariche</span>
                    <span className={styles.infoValue}>
                        {staffIcons.map(({ key, file, label }) =>
                            privilegi?.[key] == 1 ? (
                                <img key={key}
                                    src={`themes/crystal/imgs/staff/${file}`}
                                    width="16" height="16"
                                    alt={label} title={label}
                                    style={{ marginLeft: '2px' }} />
                            ) : null
                        )}
                    </span>
                </div>
            </div>

        </div>
    )
})

// ---------------------------------------------------------------------------
// COMPONENTE PRINCIPALE
// ---------------------------------------------------------------------------

export default function Scheda() {
    // Legge il nome del pg dalla URL (?page=scheda&pg=NomePg)
    const pg = new URLSearchParams(window.location.search).get('pg') ?? ''

    const [profile, setProfile] = useState(null)
    const [error, setError]     = useState(null)
    const [noteFatoOpen, setNoteFatoOpen] = useState(false)
    const [soundScheda, setSoundScheda] = useState(() => window.CT_USER?.soundPrefs?.scheda ?? 1)
    const scopedStyleEl = useRef(null)
    const schedaRef     = useRef(null)
    const avatarRef     = useRef(null)
    const profileRef    = useRef(null)

    // Sincronizza l'altezza dell'avatarFrame con quella del profileCard su desktop.
    // Necessario perché in CSS non è possibile far sì che la larghezza dell'immagine
    // segua le proprie proporzioni mantenendo come vincolo l'altezza del sibling,
    // senza che l'immagine grande non spinga verso l'alto il profileCard.
    useEffect(() => {
        const avatar  = avatarRef.current
        const profile = profileRef.current
        if (!avatar || !profile) return

        const sync = () => {
            if (window.innerWidth > 1024) {
                avatar.style.height = profile.offsetHeight + 'px'
            } else {
                avatar.style.height = ''
            }
        }

        sync()
        const ro = new ResizeObserver(sync)
        ro.observe(profile)
        window.addEventListener('resize', sync)
        return () => {
            ro.disconnect()
            window.removeEventListener('resize', sync)
        }
    }, [profile])

    // Reagisce in real-time al cambio preferenza musica schede
    useEffect(() => {
        function onSoundUpdate(e) { setSoundScheda(e.detail?.scheda ?? 1) }
        document.addEventListener('ct:soundprefs:update', onSoundUpdate)
        return () => document.removeEventListener('ct:soundprefs:update', onSoundUpdate)
    }, [])

    // Muta/smuta TUTTI gli elementi audio presenti nell'HTML inserito dall'utente.
    // Copre <audio>, <video>, <iframe>, <embed>, <object> — inclusi quelli incollati
    // manualmente dall'utente nei campi descrizione/particolari/note.
    // Gira ogni volta che cambia la preferenza, il profilo o si apre la sezione Note.
    useEffect(() => {
        const container = schedaRef.current
        if (!container) return
        const mute = !soundScheda

        // <audio> e <video>: mute diretto + pausa quando si disattiva
        container.querySelectorAll('audio, video').forEach(el => {
            el.muted = mute
            if (mute) el.pause()
        })

        // <iframe>: impossibile controllare l'audio cross-origin dall'esterno,
        // quindi scambiamo il src con about:blank. L'originale viene preservato
        // in data-original-src la prima volta che viene silenziato.
        container.querySelectorAll('iframe').forEach(el => {
            if (mute) {
                if (el.dataset.originalSrc === undefined) {
                    el.dataset.originalSrc = el.getAttribute('src') ?? ''
                }
                el.src = 'about:blank'
            } else if (el.dataset.originalSrc !== undefined) {
                el.src = el.dataset.originalSrc
            }
        })

        // <embed>: stessa logica di src-swap
        container.querySelectorAll('embed').forEach(el => {
            if (mute) {
                if (el.dataset.originalSrc === undefined) el.dataset.originalSrc = el.src ?? ''
                el.src = ''
            } else if (el.dataset.originalSrc !== undefined) {
                el.src = el.dataset.originalSrc
            }
        })

        // <object>: stessa logica sul campo data
        container.querySelectorAll('object').forEach(el => {
            if (mute) {
                if (el.dataset.originalData === undefined) el.dataset.originalData = el.data ?? ''
                el.data = ''
            } else if (el.dataset.originalData !== undefined) {
                el.data = el.dataset.originalData
            }
        })
    }, [soundScheda, profile, noteFatoOpen])

    useEffect(() => {
        if (!pg) { setError('Personaggio non specificato'); return }
        fetch(`/pages/api_scheda.php?op=profile&pg=${encodeURIComponent(pg)}`)
            .then(r => r.json())
            .then(d => {
                if (d.success) setProfile(d)
                else           setError(d.message ?? 'Errore nel caricamento della scheda')
            })
            .catch(() => setError('Errore di rete'))
    }, [pg])

    // Inietta in <head> il CSS dai campi DB, limitato a .pagina_scheda.
    // Rimosso automaticamente quando si lascia la scheda.
    useEffect(() => {
        if (!profile) return
        const allCss = [profile.principale, profile.particolari, profile.note_fato]
            .map(s => extractAndScopeStyles(s).css)
            .filter(Boolean)
            .join('\n')
        if (scopedStyleEl.current) scopedStyleEl.current.remove()
        if (allCss) {
            const el = document.createElement('style')
            el.textContent = allCss
            document.head.appendChild(el)
            scopedStyleEl.current = el
        }
        return () => { if (scopedStyleEl.current) scopedStyleEl.current.remove() }
    }, [profile])

    if (error) {
        return (
            <div className="pagina_scheda">
                <div className="error">{error}</div>
            </div>
        )
    }

    if (!profile) {
        return (
            <div className="pagina_scheda">
                <div className="loading">Caricamento scheda…</div>
            </div>
        )
    }

    const { nome, cognome, is_own, is_staff, is_admin, is_master,
            particolari, note_fato, principale,
            data_iscrizione, ora_entrata, url_media } = profile

    const lvNum   = profile.statistiche?.livello
    const lvClass = `${styles.pgCardWrapper}${lvNum >= 1 && lvNum <= 8 ? ` ${styles[`lv-${lvNum}`]}` : ''}`

    const { html: principaleHtml } = extractAndScopeStyles(principale)
    const { html: particolariHtml } = extractAndScopeStyles(particolari)
    const { html: noteFatoHtml }    = extractAndScopeStyles(note_fato)

    return (
        <div className="pagina_scheda" ref={schedaRef}>
            <div className="page_title">
                <h2>Scheda del personaggio</h2>
            </div>

            <div className="scheda_page_body">

                {/* ── Menu navigazione scheda ──────────────────────────── */}
                <SchedaMenu
                    pg={pg}
                    isOwn={is_own}
                    isAdmin={is_admin}
                    isStaff={is_staff}
                    isMaster={is_master}
                />

                {/* ── Card pg: nome + hero + meta — glow visivo in base al livello ── */}
                <div className={lvClass}>

                    {/* ── Nome e cognome ───────────────────────────────────── */}
                    <div className="title scheda-pg-name">{nome} {cognome}</div>

                    {/* ── Riga principale: avatar + profilo ────────────────── */}
                    <div className={styles.schedaHero}>
                        <SchedaAvatar ref={avatarRef} urlImg={profile.url_img} nome={nome} />
                        <SchedaProfilo ref={profileRef} profile={profile} />
                    </div>

                    {/* ── Seconda riga: Note&Fato + accessi + SMS ──────────── */}
                    <div className={styles.schedaMeta}>
                        <div className={styles.metaBox}>
                            <button
                                className={styles.noteFatoBtn}
                                onClick={() => setNoteFatoOpen(true)}
                            >
                                ✦ Note del Fato
                            </button>
                        </div>
                        <div className={styles.metaBox}>
                            {data_iscrizione && <div>Iscrizione: {formatDate(data_iscrizione)}</div>}
                            {ora_entrata    && <div>Ultimo accesso: {formatDate(ora_entrata)}</div>}
                            <a
                                href="#"
                                className={styles.smsLink}
                                onClick={e => { e.preventDefault(); openSmsFrame(nome) }}
                            >
                                ▪ INVIA SMS ▪
                            </a>
                        </div>
                    </div>

                </div>

                {/* ── Modale Note del Fato ───────────────────────────────── */}
                {noteFatoOpen && (
                    <NoteFatoModal
                        nome={nome}
                        particolariHtml={particolariHtml}
                        noteFatoHtml={noteFatoHtml}
                        onClose={() => setNoteFatoOpen(false)}
                    />
                )}

                {/* ── Background principale ────────────────────────────── */}
                <div className="background">
                    <br />
                    <div className="body_box"
                        dangerouslySetInnerHTML={{ __html: principaleHtml }} />
                </div>

            </div>{/* fine scheda_page_body */}

            {/* ── Audio scheda: file MP3 diretto o embed YouTube invisibile ── */}
            {url_media && !!soundScheda && (() => {
                const ytId = getYoutubeId(url_media)
                return ytId ? (
                    <iframe
                        key={ytId}
                        src={`https://www.youtube.com/embed/${ytId}?autoplay=1`}
                        allow="autoplay"
                        style={{ position: 'absolute', width: 0, height: 0, border: 0 }}
                        title="Musica scheda"
                    />
                ) : (
                    <audio autoPlay>
                        <source src={url_media} type="audio/mpeg" />
                    </audio>
                )
            })()}

        </div>
    )
}
