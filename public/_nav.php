<?php
/**
 * _nav.php — Navigazione condivisa per tutte le pagine pubbliche.
 *
 * Incluso in cima al <body> di ogni pagina pubblica.
 * Contiene:
 *   - Logo (link alla homepage)
 *   - Link alle sezioni informative
 *   - Bottoni "Accedi" e "Registrati" che aprono le rispettive modali
 *   - Hamburger per mobile
 *
 * @param string $current  slug della pagina corrente (per marcare il link attivo).
 *                         Valori: 'home', 'il-gioco', 'ambientazione', 'razze', 'come-si-gioca'
 */
function public_nav(string $current = ''): void {
    /* Helper: aggiunge la classe 'active' se $slug corrisponde alla pagina corrente */
    $active = fn(string $slug): string => $current === $slug ? ' class="active"' : '';
?>
<header class="pub-header" id="pub-header">
    <div class="pub-header-inner">

        <!-- Logo testuale — link alla homepage -->
        <a href="/" class="pub-logo" aria-label="Crystal Tokyo GDR — Homepage">
            Crystal Tokyo <span>GDR</span>
        </a>

        <!-- Hamburger (visibile solo su mobile) -->
        <button class="pub-hamburger" id="pubHamburger" aria-label="Apri menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <!-- Menu principale -->
        <nav class="pub-nav" id="pubNav" aria-label="Navigazione principale">
            <ul>
                <li<?= $active('il-gioco') ?>><a href="/il-gioco">Il Gioco</a></li>
                <li<?= $active('ambientazione') ?>><a href="/ambientazione">Ambientazione</a></li>
                <li<?= $active('razze') ?>><a href="/razze">Razze</a></li>
                <li<?= $active('come-si-gioca') ?>><a href="/come-si-gioca">Come Si Gioca</a></li>
            </ul>

            <!-- CTA autenticazione -->
            <div class="pub-nav-auth">
                <button class="pub-btn pub-btn--ghost" id="pubLoginBtn">Accedi</button>
                <button class="pub-btn pub-btn--gold"  id="pubRegBtn">Registrati</button>
            </div>
        </nav>

    </div>
</header>
<?php
}
