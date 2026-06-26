<?php
/**
 * _modals.php — Modali condivise per le pagine pubbliche.
 *
 * Include tre modali:
 *   1. #pubLoginModal    — Form di login
 *   2. #pubRegModal      — Form di registrazione (razze/mestieri caricati via API)
 *   3. #pubReportModal   — Form di segnalazione problema
 *
 * Tutte le modali sono hidden per default (display:none).
 * Il JS in public.js gestisce l'apertura/chiusura.
 *
 * Le API usate dal form di registrazione:
 *   GET /pages/api_iscrizione.php?op=options  → { razze, mestieri }
 *   GET /pages/api_iscrizione.php?op=termini  → { regolamento, disclaimer }
 *   POST /themes/crystal/home/recupero_password.php → recupero password
 */
function public_modals(): void {
?>

<!-- ── MODALE LOGIN ──────────────────────────────────────────────────────── -->
<div class="pub-modal-overlay" id="pubLoginModal" role="dialog" aria-modal="true" aria-label="Accedi al gioco" style="display:none;">
    <div class="pub-modal-box">

        <!-- Tasto chiusura -->
        <button class="pub-modal-close" data-modal="pubLoginModal" aria-label="Chiudi">&times;</button>

        <h2 class="pub-modal-title">Accedi al Gioco</h2>

        <!-- Form di login — gestito via fetch da public.js → api_auth.php?op=login -->
        <form class="pub-form" id="pubDoLogin">
            <div class="pub-form-group">
                <label for="pubUsername">Nome personaggio</label>
                <input type="text" id="pubUsername" name="login1" placeholder="Il tuo nome personaggio" required autocomplete="username">
            </div>
            <div class="pub-form-group">
                <label for="pubPassword">Password</label>
                <input type="password" id="pubPassword" name="pass1" placeholder="Password" required autocomplete="current-password">
            </div>
            <div class="pub-form-error" id="pubLoginError" style="display:none;" role="alert"></div>
            <button type="submit" class="pub-btn pub-btn--gold pub-btn--full">Entra nel gioco</button>
            <!-- Link recupero password -->
            <p class="pub-form-link">
                <a href="#" id="pubPwdRecoveryLink">Password dimenticata?</a>
            </p>
        </form>

        <!-- Sezione recupero password (nascosta inizialmente) -->
        <div id="pubPwdRecovery" style="display:none;">
            <p class="pub-form-hint">Inserisci la tua email — riceverai una nuova password.</p>
            <form class="pub-form" id="pubPwdRecoveryForm">
                <div class="pub-form-group">
                    <label for="pubRecoveryEmail">Indirizzo email</label>
                    <input type="email" id="pubRecoveryEmail" name="email" placeholder="La tua email" required autocomplete="email">
                </div>
                <button type="submit" class="pub-btn pub-btn--ghost pub-btn--full">Invia nuova password</button>
            </form>
            <!-- Messaggio di feedback post-invio -->
            <p id="pubPwdMsg" style="display:none;"></p>
        </div>

        <!-- Link per aprire la modale registrazione -->
        <p class="pub-modal-switch">
            Non hai un personaggio?
            <a href="#" id="pubSwitchToReg">Registrati qui</a>
        </p>
    </div>
</div>



<!-- ── MODALE REGISTRAZIONE ──────────────────────────────────────────────── -->
<div class="pub-modal-overlay" id="pubRegModal" role="dialog" aria-modal="true" aria-label="Registrazione" style="display:none;">
    <div class="pub-modal-box pub-modal-box--wide">

        <button class="pub-modal-close" data-modal="pubRegModal" aria-label="Chiudi">&times;</button>

        <h2 class="pub-modal-title">Crea il tuo personaggio</h2>

        <!-- Form di registrazione — invia a iscrizione.php (step 2 del GDRCD) -->
        <form class="pub-form" id="pubRegForm" method="post" action="/iscrizione.php">
            <input type="hidden" name="fase"   value="2">
            <input type="hidden" name="genere" value="m">

            <div class="pub-form-group">
                <label for="pubRegEmail">E-mail <small>(usata per recupero password)</small></label>
                <input type="email" id="pubRegEmail" name="email" placeholder="La tua e-mail" required autocomplete="email">
            </div>

            <div class="pub-form-group">
                <label for="pubRegNome">Nome personaggio <small>(max 20 caratteri)</small></label>
                <input type="text" id="pubRegNome" name="nome" placeholder="Nome" required maxlength="20" autocomplete="off">
            </div>

            <div class="pub-form-group">
                <label for="pubRegCognome">Cognome personaggio <small>(opzionale)</small></label>
                <input type="text" id="pubRegCognome" name="cognome" placeholder="Cognome" maxlength="20" autocomplete="off">
            </div>

            <div class="pub-form-group">
                <label for="pubRegRazza">
                    Razza
                    <!-- Link alla pagina razze per approfondire la scelta -->
                    <a href="/razze" target="_blank" class="pub-form-help" title="Scopri le razze disponibili">&#9432; scopri le razze</a>
                </label>
                <select id="pubRegRazza" name="razza" required>
                    <option value="">Caricamento razze...</option>
                </select>
            </div>

            <div class="pub-form-group">
                <label for="pubRegMestiere">
                    Mestiere
                    <a href="/statuti/mestieri/mestieri_info.html" target="_blank" class="pub-form-help" title="Scopri i mestieri">&#9432; scopri i mestieri</a>
                </label>
                <select id="pubRegMestiere" name="mestiere" required>
                    <option value="">Caricamento mestieri...</option>
                </select>
            </div>

            <!-- Sezione informazioni sul gioco (toggle) -->
            <div class="pub-form-group pub-form-toggle-group">
                <button type="button" class="pub-toggle-btn" id="pubInfoToggle">
                    &#9432; Informazioni sul gioco
                </button>
                <div class="pub-toggle-content" id="pubInfoContent" style="display:none;">
                    <div id="pubInfoText"><em>Caricamento...</em></div>
                </div>
            </div>

            <!-- Termini e condizioni (toggle + checkbox obbligatoria) -->
            <div class="pub-form-group pub-form-toggle-group">
                <button type="button" class="pub-toggle-btn" id="pubTcToggle">
                    &#9432; Termini e Condizioni
                </button>
                <div class="pub-toggle-content" id="pubTcContent" style="display:none;">
                    <div id="pubTcText"><em>Caricamento...</em></div>
                </div>
                <label class="pub-checkbox-label" style="margin-top:10px;">
                    <input type="checkbox" id="pubRegTc" required>
                    Ho letto e accetto i Termini e Condizioni
                </label>
            </div>

            <button type="submit" class="pub-btn pub-btn--gold pub-btn--full">Avanti &rsaquo;</button>
        </form>

        <p class="pub-modal-switch">
            Hai già un personaggio?
            <a href="#" id="pubSwitchToLogin">Accedi qui</a>
        </p>
    </div>
</div>


<!-- ── MODALE SEGNALA PROBLEMA ───────────────────────────────────────────── -->
<div class="pub-modal-overlay" id="pubReportModal" role="dialog" aria-modal="true" aria-label="Segnala problema" style="display:none;">
    <div class="pub-modal-box">

        <button class="pub-modal-close" data-modal="pubReportModal" aria-label="Chiudi">&times;</button>

        <h2 class="pub-modal-title">Segnala un problema</h2>

        <form class="pub-form" id="pubReportForm" method="post" action="/invio_segnalazione.php">
            <div class="pub-form-group">
                <label for="pubReportEmail">La tua email</label>
                <input type="email" id="pubReportEmail" name="email" placeholder="email@esempio.it" required autocomplete="email">
            </div>
            <div class="pub-form-group">
                <label for="pubReportText">Descrivi il problema</label>
                <textarea id="pubReportText" name="problem" rows="4" placeholder="Descrivi il problema riscontrato..." required></textarea>
            </div>
            <button type="submit" class="pub-btn pub-btn--gold pub-btn--full">Invia segnalazione</button>
        </form>
    </div>
</div>

<?php
}
