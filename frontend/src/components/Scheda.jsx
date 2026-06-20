/**
 * Scheda.jsx — Scheda personaggio (Phase SPA)
 *
 * Legge ?pg=NomePg dalla URL corrente e carica i dati via:
 *   api_scheda.php?op=profile&pg=X
 *
 * Sezioni renderizzate:
 *   - Menu sub-sezioni (link PHP, causano reload — non ancora migrate)
 *   - Avatar + blocco profilo/statistiche
 *   - Note & Fato collassabili
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

import { useState, useEffect, useRef, useCallback } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './Scheda.module.css'

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

/**
 * Estrae i blocchi <style> dall'HTML, aggiunge il prefisso .pagina_scheda
 * a ogni selettore CSS e restituisce { html, css } separati.
 * I selettori body/html vengono sostituiti con .pagina_scheda.
 * Questo consente al CSS scritto nei campi del personaggio di operare
 * solo all'interno della scheda senza colpire il resto della pagina.
 */
function extractAndScopeStyles(html) {
    if (!html) return { html: '', css: '' }
    let css = ''
    const cleaned = html.replace(/<style[\s\S]*?>([\s\S]*?)<\/style>/gi, (_, block) => {
        css += block
        return ''
    })
    if (!css) return { html, css: '' }
    // Prefissa ogni selettore con .pagina_scheda
    const scoped = css.replace(/([^@{}]+)\{/g, (_, selector) => {
        const prefixed = selector
            .split(',')
            .map(s => {
                const t = s.trim()
                if (!t) return ''
                // body e html → .pagina_scheda come radice
                return t.replace(/^(body|html)\b/, '.pagina_scheda')
                        .replace(/^(?!\.pagina_scheda)/, '.pagina_scheda ')
            })
            .filter(Boolean)
            .join(', ')
        return `${prefixed} {`
    })
    return { html: cleaned, css: scoped }
}

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
 * Blocco profilo sinistro: avatar grande del personaggio.
 */
function SchedaAvatar({ urlImg, nome }) {
    return (
        <div className="ritratto_avatar">
            <img src={urlImg} className="ritratto_avatar_immagine" alt={nome} />
        </div>
    )
}

/**
 * Blocco profilo destro: stats pubbliche + statistiche private + info.
 * stat_names proviene da profile.config.stat_names (configurazione backend).
 */
function SchedaProfilo({ profile }) {
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
        <div className="profilo">
            <div className="titolo_box">Profilo</div>
            <div className="primo_box">

                {/* ── PROFILO ───────────────────────────────────────────── */}
                <div className="header_box">▪ PROFILO ▪</div><br />

                <span className={styles.labelLeft}>Età:</span>
                <span className={styles.valueRight}>{eta}</span>
                <br />

                <span className={styles.labelLeft}>Luogo:</span>
                <span className={styles.valueRight}>{natoa}</span>
                <br />

                {/* lavoro && (
                    <>
                        <span className={styles.labelLeft}>Lavoro:</span>
                        <span className={styles.valueRight}>{lavoro}</span>
                        <br />
                    </>
                )*/}

                {/* <span className={styles.labelLeft}>{sn.race_sing ?? 'Spirito'}:</span>
                <span className={styles.valueRight}>{razza}</span>
                <br /> */}

                <span className={styles.labelLeft}>Razza:</span>
                <span className={styles.valueRight}>{nome_ruolo}</span>
                <br />

                <span className={styles.labelLeft}>Mestiere:</span>
                <span className={styles.valueRight}>{nome_ruolo_mestiere}</span>
                <br /><br />

                {/* ── STATISTICHE (solo proprio pg o staff) ─────────────── */}
                {statistiche && (
                    <>
                        <div className="header_box">▪ STATISTICHE ▪</div><br />

                        <span className="level-stat">
                            Livello
                            <span className="help-animated">?</span>
                            <div className="tooltip-animated">
                                <strong>Il livello si calcola in base alla somma di tutte le statistiche, se il personaggio appartiene ad una famiglia</strong><br />
                                <table className="level-table">
                                    <tbody>
                                        <tr><th className="form-group form-column">Livello</th><th className="form-group form-column">Fino a</th></tr>
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
                        <span className={styles.levelValue}>
                            {statistiche.livello}
                        </span>
                        <br /><br />

                        <span className={styles.labelLeft}>Tot. Caratteristiche</span>
                        <span className={styles.boldValue}>
                            {statistiche.totale}
                        </span>
                        <br />

                        {['car8', 'car2', 'car4', 'car6'].map(k => (
                            <span key={k}>
                                <span className={styles.labelLeft}>{sn[k] ?? k}:</span>
                                <span className={styles.valueRight}>{statistiche[k]}</span>
                                <br />
                            </span>
                        ))}
                    </>
                )}

                {/* ── INFO ──────────────────────────────────────────────── */}
                <div className="header_box">▪ INFO ▪</div><br />

                <span className={styles.labelLeft}>{sn.hitpoints ?? 'Salute'}:</span>
                <span className={styles.valueRight}>{salute}/{salute_max}</span>
                <br />

                <span className={styles.labelLeft}>{sn.integrita ?? 'Integrità'}:</span>
                <span className={styles.valueRight}>{integrita}/{integrita_max}</span>

                {statistiche && (
                    <>
                        <br />
                        <span className={styles.labelLeft}>Esperienza:</span>
                        <span className={styles.valueRight}>
                            {Math.floor(esperienza ?? 0)}
                        </span>
                        <br />
                        <span className={styles.labelLeft}>Shin:</span>
                        <span className={styles.valueRight}>
                            {Math.floor(shin ?? 0)}
                        </span>
                    </>
                )}

                <br />
                {/* <span className={styles.labelLeft}>{sn.notorieta ?? 'Notorietà'}:</span>
                <span className={styles.valueRight}>{Math.floor(notorieta)}</span>
                <br /><br /> */}

                {/* ── CARICHE STAFF ─────────────────────────────────────── */}
                <span className={styles.labelLeft}>Cariche</span>
                <span className={styles.valueRight}>
                    {staffIcons.map(({ key, file, label }) =>
                        privilegi?.[key] == 1 ? (
                            <img key={key}
                                src={`themes/crystal/imgs/staff/${file}`}
                                width="20" height="20"
                                alt={label} title={label} />
                        ) : null
                    )}
                </span>
                <br /><br />

            </div>
        </div>
    )
}

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

    // Reagisce in real-time al cambio preferenza musica schede
    useEffect(() => {
        function onSoundUpdate(e) { setSoundScheda(e.detail?.scheda ?? 1) }
        document.addEventListener('ct:soundprefs:update', onSoundUpdate)
        return () => document.removeEventListener('ct:soundprefs:update', onSoundUpdate)
    }, [])

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

    const { html: principaleHtml } = extractAndScopeStyles(principale)
    const { html: particolariHtml } = extractAndScopeStyles(particolari)
    const { html: noteFatoHtml }    = extractAndScopeStyles(note_fato)

    return (
        <div className="pagina_scheda">
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

                {/* ── Nome e cognome ───────────────────────────────────── */}
                <div className="title scheda-pg-name">{nome} {cognome}</div>

                {/* ── Riga principale: avatar + profilo ────────────────── */}
                <div className="pg-infos">
                    <SchedaAvatar urlImg={profile.url_img} nome={nome} />
                    <SchedaProfilo profile={profile} />
                </div>

                {/* ── Seconda riga: Note&Fato + accessi + SMS ──────────── */}
                <div className="pg-infos">
                    <div className="secondo_box">
                        {/* Toggle Note & Fato collassabili */}
                        <div
                            className="titolo_box_scheda"
                            onClick={() => setNoteFatoOpen(o => !o)}
                            style={{ cursor: 'pointer' }}
                        >
                            Note e Fato
                        </div>
                    </div>
                    <div className="terzo_box">
                        {data_iscrizione && <>Primo accesso: {formatDate(data_iscrizione)}<br /></>}
                        {ora_entrata    && <>Ultimo accesso: {formatDate(ora_entrata)}<br /></>}
                        <a href="#" onClick={e => { e.preventDefault(); openSmsFrame(nome) }}>
                            ▪ INVIA SMS ▪
                        </a>
                    </div>
                </div>

                {/* ── Note & Fato (collassabili) ───────────────────────── */}
                {noteFatoOpen && (
                    <div className="hidden_row" id="NoteEFato">
                        <div className="particolari">
                            {/* Contenuto HTML grezzo dal DB — solo utenti autenticati */}
                            <div className="green"
                                dangerouslySetInnerHTML={{ __html: particolariHtml }} />
                            <div className="blue"
                                dangerouslySetInnerHTML={{ __html: noteFatoHtml }} />
                        </div>
                    </div>
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
