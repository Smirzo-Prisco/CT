/**
 * onboardingSteps.js — Contenuto del tour guidato "Crystal Bot" (OnboardingTour.jsx)
 *
 * Ogni tappa punta a un elemento REALE della pagina tramite l'attributo
 * data-tour (mai coordinate hardcoded): l'HUD ha layout diversi desktop/
 * mobile e i suoi anelli si aprono/chiudono, quindi le posizioni cambiano
 * in continuazione. `ring` indica quale anello va aperto (se serve) prima
 * di misurare/evidenziare il target — vedi OnboardingTour.jsx.
 *
 * Testo statico e pre-scritto (nessuna chiamata a Claude): affidabile,
 * gratuito, sempre coerente con le funzioni reali. Crystal Bot resta
 * comunque disponibile per domande libere dopo il tour, tramite il widget
 * assistente esistente (ChatbotWidget.jsx), invariato.
 *
 * @author Crystal Tokyo Dev
 */

const onboardingSteps = [
    {
        id: 'intro',
        ring: 'right',
        target: '[data-tour="hud-assistente"]',
        text: "Ciao! Sono Crystal Bot, l'assistente di Crystal Tokyo. Ti va se ti faccio fare un giro veloce delle funzioni principali? Puoi saltare in qualsiasi momento con il pulsante qui sotto.",
    },
    {
        id: 'mappa',
        ring: 'left',
        target: '[data-tour="hud-mappa"]',
        text: 'Questa è la Mappa: da qui esplori i distretti di Tokyo 3060. Ogni distretto con gente dentro mostra un badge dorato con il numero di presenti, così sai subito dove trovare una chat viva.',
    },
    {
        id: 'presenti',
        ring: 'left',
        target: '[data-tour="hud-presenti"]',
        text: 'Presenti ti mostra chi è online nella tua stessa stanza in questo momento.',
    },
    {
        id: 'forum',
        ring: 'left',
        target: '[data-tour="hud-forum"]',
        text: 'Il Forum è dove si discute fuori dal gioco: annunci, regolamento, richieste allo staff.',
    },
    {
        id: 'chatoff',
        ring: 'left',
        target: '[data-tour="hud-chatoff"]',
        text: 'Chat off è la chat libera, fuori dalle giocate — perfetta per due chiacchiere OOC con gli altri giocatori.',
    },
    {
        id: 'calendario',
        ring: 'right',
        target: '[data-tour="hud-calendario"]',
        text: 'Il Calendario elenca gli eventi in programma.',
    },
    {
        id: 'giocate',
        ring: 'right',
        target: '[data-tour="hud-giocate"]',
        text: 'Giocate raccoglie i riepiloghi delle tue giocate in corso e passate.',
    },
    {
        id: 'messaggi',
        ring: 'right',
        target: '[data-tour="hud-messaggi"]',
        text: 'Messaggi è la tua posta privata per parlare con altri giocatori.',
    },
    {
        id: 'scheda',
        ring: 'right',
        target: '[data-tour="hud-scheda"]',
        text: 'Questo sei tu: clicca qui in qualsiasi momento per aprire la tua scheda personaggio.',
    },
    {
        id: 'outro',
        ring: null,
        target: null,
        text: "Per ora è tutto! Se hai dubbi lungo la strada, mi trovi sempre nell'icona Assistente — chiedimi pure del regolamento o dell'ambientazione. Buon gioco a Crystal Tokyo!",
    },
]

export default onboardingSteps
