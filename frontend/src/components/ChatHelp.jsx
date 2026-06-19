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
                            Nella barra superiore del form sono presenti le icone di accesso rapido alle funzioni della chat.
                            La sezione <b>Icone della Chat</b> qui sotto riporta la descrizione completa di ciascuna.
                        </p>
                    </div>

                    {/* ── ICONE DELLA CHAT ─────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Icone della Chat</h2>
                        <p className="text-content">
                            Le icone nella barra in alto al form di azione e nel Pannello GDR:
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/chat_panel.png" className="chat_icon" title="Pannello GDR" /> <b>Pannello GDR</b> —
                            Apre il pannello per lanciare dadi, usare abilità, armi e oggetti. Compare solo durante una role attiva (o sempre per Master e Admin).
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/cura.png" className="chat_icon" alt="Cura PG" /> <b>Cura PG</b> —
                            Permette di ripristinare i punti vita del personaggio. Compare solo quando il personaggio è in grado di curarsi.
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/help.png" className="chat_icon" alt="Guida" /> <b>Guida</b> —
                            Apre questa guida alla chat.
                        </p>
                        <p className="text-content">
                            <i className="fa-solid fa-play chat-avvia-btn"></i> <b>Avvia / Entra in Role</b> —
                            Avvia una nuova giocata oppure ti aggiunge a quella già in corso nella stanza.
                            Compare quando non sei ancora dentro una role.
                        </p>
                        <p className="text-content">
                            <i className="fa-solid fa-users" style={{ fontSize: '16px' }}></i> <b>Personaggi in Role</b> —
                            Mostra la lista di tutti i personaggi attivi nella giocata corrente con il loro stato
                            (in role, azione inviata, turno chiuso). Compare solo durante la role.
                        </p>
                        <p className="text-content">
                            <i className="fa-solid fa-power-off" style={{ fontSize: '16px' }}></i> <b>Esci dalla Role</b> —
                            Chiude la tua partecipazione alla giocata in corso. Ricordarsi sempre di cliccarla al termine della sessione. Compare solo quando sei dentro una role.
                        </p>
                        <p className="text-content">
                            All'interno del <b>Pannello GDR</b> (in alto a destra) sono presenti ulteriori icone:
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/blocca_salva.png" className="chat_icon" alt="Salva" /> <b>Salva Giocata</b> —
                            Apre in una nuova scheda la pagina di salvataggio della giocata corrente.
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/BackchatOFF.png" className="chat_icon" alt="Backchat" /> <b>Backchat</b> —
                            Attiva o disattiva il backchat (registro storico delle azioni della stanza).
                            Visibile solo ai personaggi con un livello di esperienza sufficiente.
                        </p>
                        <p className="text-content">
                            <img src="themes/crystal/imgs/chat/writer.png" className="chat_icon" alt="Scrittura Libera" /> <b>Scrittura Libera</b> —
                            Apre un editor per comporre testi lunghi senza il limite caratteri della textarea principale.
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
                            Per ogni lancio occorre sempre specificare almeno un bersaglio.
                            La quantità di bersagli attaccabili aumenta con il livello dell'abilità utilizzata.
                            Le icone relative alla role (pannello GDR, personaggi, uscita) sono descritte nella sezione <b>Icone della Chat</b>.
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
                            Esempio: <i>Entra nella stanza e si guarda intorno. [Non vedo nessuno...]</i>
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
                            Il sussurro non è visibile agli altri presenti nella chat.
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
                            Il Pannello GDR si apre cliccando l'icona dedicata.
                            È disponibile durante una role attiva (o sempre per Master e Admin).
                            Contiene tre sezioni principali.
                        </p>
                        <p className="text-content">
                            <b>D20 e Oggetti</b> — permette di lanciare un dado generico con un numero
                            di facce a scelta, oppure di usare un oggetto dall'inventario. L'effetto viene pubblicato automaticamente in chat.
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
                            Le abilità (o skills) si dividono in più categorie: <b>attacco</b> (fisico o mentale),
                            <b> difensiva</b> (scudo) e <b>generica</b>.
                            Ogni abilità ha un livello che ne determina sia la potenza che il numero
                            massimo di bersagli selezionabili.
                        </p>
                        <p className="text-content">
                            Quando lanci un'abilità, il sistema esegue un tiro D20 e aggiunge
                            il bonus della caratteristica associata (destrezza per il fisico,
                            mente per il mentale).
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
                            E' consentito utilizzare un'abilità difensiva dopo un lancio qualunque nello stesso turno, ma questo preclude la possibilità di effettuare qualunque lancio nel turno successivo, ad eccezione del dado di difesa.
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
                            della giocata attiva.
                        </p>
                    </div>

                    {/* ── TURNI ────────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Turni</h2>
                        <p className="text-content">
                            Nel turno è consentito lanciare solamente ciò che è disponibile dal pannello.
                            Dopo un'azione il sistema invia un messaggio proponendo la chiudura del turno.
                            Il turno viene chiuso solamente quando tutti i partecipanti hanno cliccato sul pulsante di chiusura
                            del turno, per dare la possibilità a tutti di lanciare un'eventuale scudo o attacco.
                            Quando il turno viene chiuso, il sistema invia automaticamente un riepilogo in chat.
                        </p>
                        <p className="text-content">
                            Quando si riceve un attacco, il sistema propone tre opzioni di risposta immediata:
                            <b> Dado</b> (tira il dado di difesa senza chiudere il turno),
                            <b> Scudo</b> (usa una skill difensiva se in possesso, chiude automaticamente il tuo turno),
                            oppure <b> Subisci</b> (accetti il danno senza risposta).
                        </p>
                    </div>

                    {/* ── DANNI ────────────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Danni</h2>
                        <p className="text-content">
                            Calcolo del danno: attacco meno difesa per il moltiplicatore del livello d'attacco (vedi tabella).
                            Ogni danno viene calcolato a fine turno e applicato ad ogni personaggio colpito.
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
                            Le abilità mentali agiscono sull'<b>integrità</b> del bersaglio.
                            Alcune skill mentali hanno un <b>effetto di durata</b>: una volta attivato,
                            il bersaglio perde 5 punti integrità per ogni turno successivo,
                            per un numero di turni determinato dall'entità del danno iniziale.
                        </p>
                        <p className="text-content">
                            La durata si attiva solo se l'integrità del bersaglio è <b>inferiore a 60</b>.
                            Cessa automaticamente se l'integrità scende <b>sotto 20</b>.
                        </p>
                        <p className="text-content">
                            Per le abilità mentali di tipo "comando" vigono due regole distinte sui turni consecutivi:
                        </p>
                        <p className="text-content">
                            — Lanciare la stessa abilità di comando <b>sullo stesso bersaglio</b> per due turni
                            di fila è <b>vietato</b>: il lancio viene bloccato dal sistema.
                        </p>
                        <p className="text-content">
                            — Lanciare un'abilità di comando <b>su un bersaglio diverso</b> per due turni di fila
                            è invece consentito, ma il sistema applica automaticamente una <b>perdita di integrità
                            all'attaccante</b>, determinata dal suo livello (vedi tabella).
                        </p>
                        {soglie.length > 0 && (
                            <table className="help-soglie-table">
                                <thead>
                                    <tr><th>Livello</th><th>Integrità persa</th></tr>
                                </thead>
                                <tbody>
                                    {soglie.map(s => (
                                        <tr key={s.livello}><td>{s.livello}</td><td>{s.integrita}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {/* ── ESPERIENZA ───────────────────────────────── */}
                    <div className="command-card">
                        <h2 className="section-title">Esperienza</h2>
                        <p className="text-content">
                            Il sistema assegna automaticamente <b>punti esperienza</b> durante la role.
                            Dopo aver inviato 4 azioni all'interno della stessa giocata in una
                            location pubblica, il personaggio riceve +1 esperienza.
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
