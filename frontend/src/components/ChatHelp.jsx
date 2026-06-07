/**
 * ChatHelp.jsx — Guida ai comandi chat integrata nella SPA
 *
 * Navigazione: raggiungibile da ChatShell via CT.navigate('main.php?page=chat_help').
 * Il pulsante "Indietro" usa history.back() per tornare alla stanza precedente.
 * La tabella moltiplicatori è caricata da api_chatHelp.php (soglie dinamiche da DB).
 */

import { useState, useEffect } from 'react'

export default function ChatHelp({ onBack }) {
    const [soglie, setSoglie] = useState([])

    useEffect(() => {
        const href = '/themes/crystal/chat_help.css'
        if (!document.querySelector(`link[href="${href}"]`)) {
            const link = document.createElement('link')
            link.rel = 'stylesheet'
            link.href = href
            document.head.appendChild(link)
        }
    }, [])

    useEffect(() => {
        fetch('/pages/api_chatHelp.php')
            .then(r => r.json())
            .then(data => setSoglie(data.soglie || []))
            .catch(err => console.error('Errore caricamento soglie:', err))
    }, [])

    return (
        <div className="chat-help-page">

            <button className="chat-help-back" onClick={() => onBack ? onBack() : window.history.back()}>
                <i className="fa-solid fa-arrow-left"></i> Torna alla chat
            </button>

            <div className="help-container">
                <h1 className="intestazione">Istruzioni per la Chat</h1>

                <div className="commands-grid">

                    {/* ── IL FORM ──────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Il Form di Azione</h2>
                        <p className="text-content">
                            Nella parte bassa della chat sono presenti due campi per comporre le proprie azioni.
                        </p>
                        <p className="text-content">
                            <b>TAG</b> — campo breve (max 30 caratteri) che precede l'azione. Serve a indicare
                            la posizione o la condizione del personaggio all'interno della stanza
                            (es. <i>Centro</i>, <i>Parco | Demone</i>, <i>Tavolo in fondo</i>).
                            Il tag viene visualizzato tra parentesi quadre prima del testo dell'azione.
                            Non usare il TAG per riportare informazioni che possono essere descritte nel narrato.
                        </p>
                        <p className="text-content">
                            <b>Testo</b> — la textarea principale dove scrivere narrato e dialoghi.
                            Il contatore in basso a destra mostra i caratteri rimanenti (limite di default: 2000).
                            Premi <b>Invio</b> o clicca il pulsante <b>Invia</b> per pubblicare l'azione.
                        </p>
                        <p className="text-content">
                            Nella barra superiore del form sono presenti altri comandi:
                            l'icona <i className="fa-solid fa-dice"></i> apre il <b>Pannello GDR</b> (skill, dadi, oggetti),
                            l'icona <i className="fa-solid fa-circle-question"></i> apre questa guida.
                        </p>
                    </div>

                    {/* ── ROLE ─────────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Role</h2>
                        <p className="text-content">
                            La <b>role</b> è una sessione di gioco in cui due o più personaggi interpretano
                            una situazione comune. Si svolge in una delle chat della mappa.
                        </p>
                        <p className="text-content">
                            Clicca sul pulsante <i className="fa-solid fa-play chat-avvia-btn"></i> per avviare
                            oppure unirti alla giocata. Se sei il primo a entrare puoi selezionare gli altri
                            partecipanti dalla lista dei presenti.
                        </p>
                        <p className="text-content">
                            L'icona del pannello chat <img title="Pannello chat" src="themes/crystal/imgs/chat/chat_panel.png" className="chat_icon" /> compare
                            soltanto se sei coinvolto nella giocata attiva.
                            L'icona <i className="fa-solid fa-users" style={{ cursor: 'pointer', display: 'inline-block', fontSize: '16px' }}></i> mostra
                            la lista di tutti i personaggi attualmente nella giocata, con il loro stato
                            (in role, azione inviata, turno chiuso).
                        </p>
                        <p className="text-content">
                            Alla fine della giocata ricordarsi di uscire dalla role tramite
                            l'icona <i className="fa-solid fa-power-off" style={{ cursor: 'pointer', display: 'inline-block', fontSize: '16px' }}></i>.
                            Per ogni lancio occorre sempre specificare almeno un bersaglio.
                            La quantità di bersagli attaccabili aumenta con il livello dell'abilità utilizzata.
                        </p>
                    </div>

                    {/* ── AZIONE ───────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Azione</h2>
                        <p className="text-content">
                            Il <b>narrato</b> descrive ciò che il personaggio fa, percepisce o manifesta
                            in modo palese (movimenti, aspetto, gestualità, combattimento). Va scritto in
                            <b> terza persona al presente</b>: il soggetto è il personaggio che sta agendo
                            in quel preciso momento.
                        </p>
                        <p className="text-content">
                            Per inviare un'azione narrativa, fai precedere il testo dal simbolo <b>+</b>.
                            Esempio: <i>+Si avvicina lentamente al tavolo, senza alzare lo sguardo.</i>
                        </p>
                        <p className="text-content">
                            È vietato descrivere in un solo blocco più azioni contemporanee o impossibili
                            da compiere nello stesso istante.
                        </p>
                    </div>

                    {/* ── DIALOGHI ─────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Dialoghi</h2>
                        <p className="text-content">
                            Il <b>parlato</b> è ciò che il personaggio dice ad alta voce. Per distinguerlo
                            dal narrato è necessario racchiudere il testo tra parentesi quadre <b>[ ]</b>
                            oppure tra i simboli <b>&lt; &gt;</b>.
                        </p>
                        <p className="text-content">
                            Esempio: <i>+Entra nella stanza e si guarda intorno. [Non vedo nessuno...]</i>
                        </p>
                        <p className="text-content">
                            Un'azione tipica è composta da: <b>TAG</b> (campo apposito) + <b>narrato</b> + <b>parlato</b>.
                        </p>
                    </div>

                    {/* ── SUSSURRI ─────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Sussurri</h2>
                        <p className="text-content">
                            Il sussurro è un messaggio privato leggibile solo da chi lo riceve.
                            Per inviarlo, fai precedere il testo dal simbolo <b>@</b> seguito
                            immediatamente dal nome del destinatario.
                        </p>
                        <p className="text-content">
                            Esempio: <i>@NomePersonaggio Vieni con me, ho qualcosa da dirti.</i>
                        </p>
                        <p className="text-content">
                            Il sussurro non è visibile agli altri presenti nella chat, nemmeno al Master
                            (a meno che il Master non abbia abilitato la visualizzazione dei sussurri).
                        </p>
                    </div>

                    {/* ── DADI PURI ────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Dadi Puri</h2>
                        <p className="text-content">
                            Quando il Master lo richiede, o per determinate situazioni, è possibile
                            lanciare un dado con qualsiasi numero di facce (fino a 1000).
                        </p>
                        <p className="text-content">
                            Scrivi <b>#d</b> seguito dal numero di facce desiderate direttamente
                            nel campo testo. Esempio: <i>#d20</i> lancia un dado a 20 facce.
                        </p>
                        <p className="text-content">
                            In alternativa, dal <b>Pannello GDR</b> è disponibile un dado generico
                            configurabile che pubblica automaticamente il risultato in chat.
                        </p>
                    </div>

                    {/* ── MESSAGGIO OFF ─────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Messaggio OFF</h2>
                        <p className="text-content">
                            I messaggi OFF (fuori dal gioco) servono per comunicazioni organizzative
                            o chiarimenti tecnici senza uscire dal personaggio.
                        </p>
                        <p className="text-content">
                            Per inviare un messaggio OFF, far precedere il testo dal simbolo <b>/</b>.
                            È possibile inviare <b>al massimo 3 messaggi OFF</b> nella stessa sessione,
                            e solo dopo aver già inviato almeno un'azione regolare.
                        </p>
                    </div>

                    {/* ── PANNELLO GDR ─────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Pannello GDR</h2>
                        <p className="text-content">
                            Il Pannello GDR si apre cliccando l'icona in alto a sinistra nel form.
                            È disponibile durante una role attiva (o sempre per Master e Admin).
                            Contiene tre sezioni principali.
                        </p>
                        <p className="text-content">
                            <b>D20 e Oggetti</b> — permette di lanciare un dado generico con un numero
                            di facce a scelta, oppure di usare un oggetto dall'inventario (medicine,
                            potenziamenti, oggetti magici). L'effetto viene pubblicato automaticamente in chat.
                        </p>
                        <p className="text-content">
                            <b>Abilità e Armi</b> — la sezione principale per il combattimento. Permette
                            di selezionare i bersagli, scegliere un'abilità (o un attacco fisico/arma)
                            e lanciarlo. Il sistema calcola automaticamente il risultato del tiro e lo
                            invia come sussurro privato al Master.
                        </p>
                        <p className="text-content">
                            <b>Scrittura</b> — consente di impostare un limite di caratteri personalizzato
                            per le azioni nella stanza (solo staff).
                        </p>
                    </div>

                    {/* ── ABILITÀ E LANCI ──────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Abilità e Lanci</h2>
                        <p className="text-content">
                            Le abilità si dividono in più categorie: <b>attacco</b> (fisico o mentale),
                            <b> difensiva</b> (scudo), <b>generica</b> e <b>talento</b>.
                            Ogni abilità ha un livello che ne determina la potenza e il numero
                            massimo di bersagli selezionabili.
                        </p>
                        <p className="text-content">
                            Quando lanci un'abilità, il sistema esegue un tiro D20 e aggiunge
                            il bonus della caratteristica associata (destrezza per il fisico,
                            mente per il mentale). Il risultato viene inviato come sussurro
                            privato visibile solo al Master.
                        </p>
                        <p className="text-content">
                            Le <b>skill temporanee</b> hanno un numero limitato di utilizzi,
                            mostrato tra parentesi (es. <i>Abilità speciale (x3)</i>).
                            Si decrementano a ogni uso e scompaiono a zero.
                        </p>
                        <p className="text-content">
                            Usare uno <b>scudo (difensiva)</b> conta come chiusura automatica
                            del proprio turno: non sarà più possibile attaccare nello stesso turno.
                            Non è consentito lanciare più di un'abilità di attacco per turno.
                        </p>
                    </div>

                    {/* ── SELEZIONE BERSAGLI ───────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Selezione Bersagli</h2>
                        <p className="text-content">
                            Prima di lanciare un'abilità occorre selezionare almeno un bersaglio
                            dal menu apposito nel Pannello GDR.
                            Il numero massimo di bersagli selezionabili è pari al <b>livello dell'abilità</b> scelta.
                        </p>
                        <p className="text-content">
                            Esempio: un'abilità di livello 1 ammette un solo bersaglio;
                            un'abilità di livello 3 ne ammette fino a tre.
                            In caso di bersagli multipli, il danno totale viene diviso equamente tra tutti.
                        </p>
                        <p className="text-content">
                            Durante una role vengono mostrati solo i personaggi che fanno parte
                            della giocata attiva. I Master vedono sempre tutti i presenti nella stanza.
                        </p>
                    </div>

                    {/* ── TURNI ────────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Turni</h2>
                        <p className="text-content">
                            Nel turno è consentito lanciare solamente ciò che è disponibile dal pannello.
                            Quando tutti i partecipanti hanno inviato la loro azione e l'eventuale lancio, il sistema invia
                            un sussurro con un pulsante che consente la chiusura del turno.
                            Il turno viene chiuso solamente quando tutti i partecipanti hanno cliccato sul pulsante di chiusura
                            del turno, per dare la possibilità a tutti di lanciare un'eventuale scudo o attacco.
                            Quando il turno viene chiuso, il sistema invia automaticamente un riepilogo in chat.
                        </p>
                        <p className="text-content">
                            Quando si riceve un attacco, il sistema propone tre opzioni di risposta immediata:
                            <b> Dado</b> (tira il dado di difesa senza chiudere il turno),
                            <b> Scudo</b> (usa una skill difensiva, chiude automaticamente il tuo turno),
                            oppure <b> Subisci</b> (accetti il danno senza risposta).
                        </p>
                        <p className="text-content">
                            <b>Esempio 1</b><br />
                            - A attacca B<br />
                            - B descrive una difesa e attacca A<br />
                            - Il sistema manda ai partecipanti la richiesta di chiusura turno<br />
                            - A clicca sul pulsante di chiusura turno<br />
                            - B clicca sul pulsante di chiusura turno<br />
                            - Il sistema manda il riepilogo del turno in chat
                        </p>
                        <p className="text-content">
                            <b>Esempio 2</b><br />
                            - A attacca B<br />
                            - B descrive una difesa e attacca C<br />
                            - C descrive una difesa e attacca A<br />
                            - D lancia uno scudo per difendere A<br />
                            - Il sistema manda ai 4 partecipanti la richiesta di chiusura turno<br />
                            - Prima di chiudere il turno, A lancia uno scudo per difendere se stesso<br />
                            - Tutti cliccano sul pulsante di chiusura turno<br />
                            - Il sistema manda il riepilogo del turno in chat<br /><br />
                            Turno successivo...<br /><br />
                            - Il sistema impedisce ad A di lanciare un attacco, poiché ha già utilizzato un attacco e uno
                            scudo nel turno precedente. Ma può continuare a lanciare uno scudo rinunciando nuovamente
                            all'attacco nel turno successivo<br />
                            - B può continuare ad attaccare<br />
                            - C può continuare ad attaccare<br />
                            - D può continuare ad attaccare perché nel turno precedente ha utilizzato il suo lancio per
                            lanciare uno scudo, quindi ha a disposizione un nuovo lancio per il turno corrente
                        </p>
                    </div>

                    {/* ── DANNI ────────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Danni</h2>
                        <p className="text-content">
                            Calcolo del danno: attacco meno difesa per il moltiplicatore del livello d'attacco (vedi tabella).
                            Durante uno scontro è consentito lanciare soltanto abilità di attacco e di difesa.
                            Non c'è bisogno di lanciare dadi relativi alle caratteristiche, poiché il sistema calcola ogni
                            danno a fine turno sulla base delle abilità lanciate.
                            In caso di attacco su bersaglio multiplo, l'eventuale danno viene diviso per tutti i bersagli.
                        </p>
                        {soglie.length > 0 && (
                            <table className="help-soglie-table">
                                <thead>
                                    <tr><th>Livello</th><th>Moltiplicatore</th></tr>
                                </thead>
                                <tbody>
                                    {soglie.map(s => (
                                        <tr key={s.livello}><td>{s.livello}</td><td>{s.danno}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {/* ── EFFETTI MENTALI ───────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Effetti Mentali</h2>
                        <p className="text-content">
                            Le abilità mentali agiscono sull'<b>integrità</b> del bersaglio (valore
                            massimo 100 per tutti i personaggi).
                            Alcune skill mentali hanno un <b>effetto di durata</b>: una volta attivato,
                            il bersaglio perde 5 punti integrità per ogni turno successivo,
                            per un numero di turni determinato dall'entità del danno iniziale.
                        </p>
                        <p className="text-content">
                            La durata si attiva solo se l'integrità del bersaglio è <b>inferiore a 60</b>.
                            Cessa automaticamente se l'integrità scende <b>sotto 20</b>.
                        </p>
                        <p className="text-content">
                            Non è consentito lanciare due abilità mentali di tipo "comando" consecutive
                            sullo stesso bersaglio: il secondo lancio comporta una perdita di integrità
                            per l'attaccante stesso.
                        </p>
                    </div>

                    {/* ── ESPERIENZA ───────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Esperienza</h2>
                        <p className="text-content">
                            Il sistema assegna automaticamente <b>punti esperienza</b> durante la role.
                            Dopo aver inviato 4 azioni all'interno della stessa giocata in una
                            location pubblica, il personaggio riceve +1 esperienza e +2 shin.
                        </p>
                        <p className="text-content">
                            Chi lancia almeno <b>3 abilità</b> in un giorno riceve +1 shin bonus.
                            Questo bonus è assegnabile una volta ogni 24 ore.
                        </p>
                    </div>

                    {/* ── MASTER ───────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Master</h2>
                        <p className="text-content">
                            Il Master è un utente che ricopre la funzione di narratore e/o arbitro all'interno del GdR.
                            Per inviare il Master Screen (MS), far precedere al testo il simbolo <b>=</b>.
                            Solo i Master possono utilizzare questo comando.
                        </p>
                    </div>

                    {/* ── MODERATORI ───────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Moderatori</h2>
                        <p className="text-content">
                            I Moderatori vigilano le giocate, imponendosi solo quando necessario.
                            Per inviare lo Screen della Moderazione, far precedere al testo il simbolo <b>%</b>.
                            Solo i Moderatori possono utilizzare questo comando.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    )
}
