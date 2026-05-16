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

import { useState, useEffect } from 'react'
import SchedaMenu from './SchedaMenu'
import styles from './Scheda.module.css'

// ---------------------------------------------------------------------------
// UTILITÀ
// ---------------------------------------------------------------------------

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
 * Apre il frame modale per l'invio di un SMS privato al personaggio.
 * changeFrame() è definita in left-right_frames.php ed è globale.
 * @param {string} nome - Nome del destinatario
 */
function openSmsFrame(nome) {
    const url = `pages/mex_privati/multi_message.php?destinatari=${encodeURIComponent(nome)}`
    if (typeof window.changeFrame === 'function') window.changeFrame(url)
    const modal = document.getElementById('id01')
    if (modal) modal.style.display = 'block'
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
            salute, salute_max, integrita, integrita_max, notorieta,
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

                {lavoro && (
                    <>
                        <span className={styles.labelLeft}>Lavoro:</span>
                        <span className={styles.valueRight}>{lavoro}</span>
                        <br />
                    </>
                )}

                <span className={styles.labelLeft}>{sn.race_sing ?? 'Spirito'}:</span>
                <span className={styles.valueRight}>{razza}</span>
                <br />

                <span className={styles.labelLeft}>Famiglia:</span>
                <span className={styles.valueRight}>{nome_ruolo}</span>
                <br />

                <span className={styles.labelLeft}>Ruolo:</span>
                <span className={styles.valueRight}>{nome_ruolo_mestiere}</span>
                <br /><br />

                {/* ── STATISTICHE (solo proprio pg o staff) ─────────────── */}
                {statistiche && (
                    <>
                        <div className="header_box">▪ STATISTICHE ▪</div><br />

                        <span className={styles.labelLeft}>Livello</span>
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
                <span className={styles.labelLeft}>{sn.notorieta ?? 'Notorietà'}:</span>
                <span className={styles.valueRight}>{Math.floor(notorieta)}</span>
                <br /><br />

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
                <div className="title">{nome} {cognome}</div>

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
                                dangerouslySetInnerHTML={{ __html: particolari ?? '' }} />
                            <div className="blue"
                                dangerouslySetInnerHTML={{ __html: note_fato ?? '' }} />
                        </div>
                    </div>
                )}

                {/* ── Background principale ────────────────────────────── */}
                <div className="background">
                    <br />
                    <div className="body_box"
                        dangerouslySetInnerHTML={{ __html: principale ?? '' }} />
                </div>

            </div>{/* fine scheda_page_body */}

            {/* ── Audio (se il pg ha musica in scheda) ─────────────────── */}
            {url_media && (
                <audio autoPlay>
                    <source src={url_media} type="audio/mpeg" />
                </audio>
            )}

        </div>
    )
}
