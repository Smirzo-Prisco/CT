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

                    <div className="command-card">
                        <h2 className="section-title">Role</h2>
                        <p className="text-content">
                            Clicca sul pulsante <i className="fa-solid fa-play chat-avvia-btn"></i> per avviare oppure unirti alla giocata.
                            L'icona del pannello chat <img title="Pannello chat" src="themes/crystal/imgs/chat/chat_panel.png" class="chat_icon"> compare soltanto se sei coinvolto nella giocata attiva.
                                L'icona <i class="fa-solid fa-users" style="cursor: pointer; display: block; font-size: 16px;"></i> permette di visualizzare tutti i personaggi giocanti.
                                Alla fine di ogni giocata, ricordarsi di effettuare l'uscita dalla role attraverso l'apposita icona.
                                Per ogni lancio occorre sempre specificare almeno un bersaglio.
                                La quantità di bersagli attaccabili aumenta con il livello dell'abilità utilizzata.
                        </p>
                    </div>

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

                    <div className="command-card">
                        <h2 className="section-title">Azione</h2>
                        <p className="text-content">
                            Scrivere normalmente il contenuto della propria azione nella barra in basso specificando
                            il tag nell'apposito campo.
                        </p>
                    </div>

                    <div className="command-card">
                        <h2 className="section-title">Dialoghi</h2>
                        <p className="text-content">
                            Per il parlato, è necessario inserire il testo tra parentesi quadre <b>[]</b> o tra i simboli
                            di maggiore/minore <b>&lt;&gt;</b>.
                        </p>
                    </div>

                    <div className="command-card">
                        <h2 className="section-title">Sussurri</h2>
                        <p className="text-content">
                            I sussurri possono essere letti solo da chi riceve il sussurro.
                            Scrivere normalmente la frase da sussurrare preceduta da <b>@nomedestinatario@</b>.
                        </p>
                    </div>

                    <div className="command-card">
                        <h2 className="section-title">Dadi Puri</h2>
                        <p className="text-content">
                            Quando richiesto dal Master (o per utilizzare determinate skill) bisogna lanciare dei dadi.
                            Si possono lanciare dadi con qualsiasi numero di facce fino a mille.
                            Per farlo, scrivere <b>#d</b> seguito dal numero di facce desiderate.
                        </p>
                    </div>

                    <div className="command-card">
                        <h2 className="section-title">Messaggio OFF</h2>
                        <p className="text-content">
                            È possibile inviare fino ad un <b>massimo di 3 messaggi OFF</b> in chat,
                            solo se si è precedentemente già scritto all'interno della stessa.
                            Per inviare un messaggio off, far precedere il testo dal simbolo <b>/</b>.
                        </p>
                    </div>

                    <div className="command-card">
                        <h2 className="section-title">Master</h2>
                        <p className="text-content">
                            Il Master è un utente che ricopre la funzione di narratore e/o arbitro all'interno del GdR.
                            Per inviare il Master Screen (MS), far precedere al testo il simbolo <b>=</b>.
                            Solo i Master possono utilizzare questo comando.
                        </p>
                    </div>

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
