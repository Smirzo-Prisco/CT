/*************  METEO   ***************/
function switchMeteo() {
    // --- Switch Meteo Oggi/Ieri ---
    const meteoAttuale = document.querySelector('.meteo_attuale');
    const meteoPrecedente = document.querySelector('.meteo_precedente');
    const switchButton = document.querySelector('.switch_meteo');
    const titolo = document.getElementById('meteo_titolo');

    switchButton.addEventListener('click', () => {
        if (meteoAttuale.style.display === "none") {
            meteoAttuale.style.display = "flex";
            meteoPrecedente.style.display = "none";
            titolo.textContent = "METEO OGGI";
        } else {
            meteoAttuale.style.display = "none";
            meteoPrecedente.style.display = "flex";
            titolo.textContent = "METEO DI IERI";
        }
    });
}

/*************  MESSAGGI   ***************/
function messageSound(allowAudio) {
    if (!allowAudio) return;

    const audioPath = '../sounds/sms.wav';
    const now = Date.now();
    const lastPlay = localStorage.getItem('last_audio_play');

    if (!lastPlay || now - parseInt(lastPlay) > 600000) { // 10 min
        const audio = new Audio(audioPath);
        audio.play().catch(() => { });
        localStorage.setItem('last_audio_play', now.toString());
    }
}

function updateMessageIcon(hasNew, allowAudio) {
    const img = document.querySelector("#message-link img");
    if (!img) return;

    if (hasNew) {
        img.src = "../themes/crystal/imgs/icone/icon_day_newmex.gif";

        messageSound(allowAudio);
    } else img.src = "../themes/crystal/imgs/icone/icona_base_mex.png";
}

async function getMessages() {
    try {
        const response = await fetch('/pages/ajax_engine.php?op=getMessages');
        const data = await response.json();

        updateMessageIcon(data.hasNew, data.allowAudio);
    } catch (error) {
        console.error("Errore getMessages:", error);
    }
}

// Esegui subito
getMessages();

// Polling ogni 8 sec
setInterval(getMessages, 12000);

/*************  CHAT OFF   ***************/
function updateChatOffIcon(hasNew) {
    const img = document.querySelector("#chatoff-link img");
    if (!img) return;

    img.src = hasNew
        ? "../themes/crystal/imgs/icone/icon_chat_off_night.gif"
        : "../themes/crystal/imgs/icone/icon_chat_off.png";
}

async function getChatOff() {
    try {
        const response = await fetch('/pages/ajax_engine.php?op=getChatOff');
        const data = await response.json();

        updateChatOffIcon(data.hasNew);
    } catch (error) {
        console.error("Errore getMessages:", error);
    }
}

// --- Link messaggi per dispositivi mobile ---
const link = document.getElementById("message-link");
if (link) {
    const isMobile = () => (typeof window.orientation !== "undefined") || navigator.userAgent.indexOf('IEMobile') !== -1;

    if (isMobile()) {
        link.href = "../pages/mex_privati/index.php";
        link.target = "_blank";
    }
}

// Esegui subito
getChatOff();

// Polling
setInterval(getChatOff, 20000);