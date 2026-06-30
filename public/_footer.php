<?php
/**
 * _footer.php — Footer condiviso per tutte le pagine pubbliche.
 *
 * Incluso in fondo al <body> di ogni pagina pubblica.
 * Contiene: copyright, colonne link, CTA registrazione e
 * il supporto alla Palestina (coerente con la homepage attuale).
 */
function public_footer(): void {
    $year = date('Y');
?>
<footer class="pub-footer">
    <div class="pub-footer-inner">

        <!-- Colonna brand -->
        <div class="pub-footer-brand">
            <div class="pub-footer-logo">Crystal Tokyo <span>GDR</span></div>
            <p>Gioco di ruolo online play by chat gratuito, attivo da oltre vent'anni.</p>
            <!-- Supporto Palestina — coerente con la homepage -->
            <div class="pub-footer-palestine">
                <a href="https://globalsumudflotilla.org/" target="_blank" rel="noopener noreferrer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/00/Flag_of_Palestine.svg"
                         alt="Bandiera Palestina"
                         width="40" height="26"
                         loading="lazy">
                </a>
                <small>Crystal Tokyo GDR supporta la Palestina e la Global Sumud Flottilla.</small>
            </div>
        </div>

        <!-- Colonna: Il Gioco -->
        <div class="pub-footer-col">
            <h4>Il Gioco</h4>
            <ul>
                <li><a href="/il-gioco">Cos'è Crystal Tokyo</a></li>
                <li><a href="/ambientazione">Ambientazione</a></li>
                <li><a href="/razze">Le Razze</a></li>
                <li><a href="/come-si-gioca">Come Si Gioca</a></li>
            </ul>
        </div>

        <!-- Colonna: Documentazione -->
        <div class="pub-footer-col">
            <h4>Documentazione</h4>
            <ul>
                <!-- documentazione_main.php è la pagina esistente del manuale di gioco -->
                <li><a href="/documentazione_main.php">Manuale di Gioco</a></li>
                <li><a href="/statuti/mestieri/mestieri_info.html">Mestieri</a></li>
            </ul>
        </div>

        <!-- Colonna: Accesso -->
        <div class="pub-footer-col">
            <h4>Accesso</h4>
            <ul>
                <li><a href="/" id="footerLoginLink">Accedi al Gioco</a></li>
                <li><a href="/" id="footerRegLink">Registrati</a></li>
            </ul>
        </div>

    </div>

    <!-- Barra copyright -->
    <div class="pub-footer-copy">
        <p>&copy; <?= $year ?> Crystal Tokyo GDR — Tutti i diritti riservati. &nbsp;|&nbsp; <a href="/privacy">Privacy Policy</a></p>
    </div>
</footer>

<!-- Script condiviso pagine pubbliche — modale login/registrazione, hamburger -->
<?php $pjs_v = @filemtime($_SERVER['DOCUMENT_ROOT'] . '/public/public.js') ?: 0; ?>
<script src="/public/public.js?v=<?= $pjs_v ?>" defer></script>
<?php
}
