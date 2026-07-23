<?php
/**
 * sitemap-documentazione.php — Sitemap dinamica per gli articoli di regolamento.
 *
 * sitemap.xml (statico) copre solo le pagine pubbliche fisse; gli articoli di
 * `regolamento` cambiano nel tempo (aggiunte/modifiche staff), quindi qui vengono
 * elencati leggendo direttamente la tabella invece di mantenerli a mano.
 * Referenziata come sitemap aggiuntiva in robots.txt.
 */

require_once('includes/required.php');
$handleDBConnection = gdrcd_connect();

header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$res = gdrcd_query("SELECT articolo FROM regolamento ORDER BY articolo", 'result');
while ($row = gdrcd_query($res, 'fetch')) {
    $art = (int)$row['articolo'];
    echo "  <url>\n";
    echo "    <loc>https://crystaltokyo.it/documentazione_main.php?articolo={$art}</loc>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.60</priority>\n";
    echo "  </url>\n";
}
gdrcd_query($res, 'free');

echo '</urlset>' . "\n";

gdrcd_close_connection($handleDBConnection);
