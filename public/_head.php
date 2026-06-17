<?php
/**
 * _head.php — Helper per il tag <head> delle pagine pubbliche.
 *
 * Ogni pagina pubblica chiama public_head() passando i propri
 * meta specifici. La funzione produce un <head> completo con:
 *   - charset, viewport, canonical
 *   - meta description
 *   - Open Graph + Twitter Card
 *   - JSON-LD opzionale (array PHP → json_encode)
 *   - link a Google Fonts e public.css
 *
 * NON include nulla del gioco (header.inc.php, React bundle,
 * socket.io): le pagine pubbliche devono essere leggere e
 * indicizzabili senza JavaScript.
 *
 * @param string $title       Titolo della pagina (senza suffix)
 * @param string $description Meta description (max ~155 caratteri)
 * @param string $canonical   URL canonico completo (https://...)
 * @param string $og_image    URL assoluto dell'immagine OG (default: homepage.png)
 * @param array  $json_ld     Struttura JSON-LD come array PHP (vuoto = nessuno schema)
 */
function public_head(
    string $title,
    string $description,
    string $canonical,
    string $og_image = 'https://crystaltokyo.it/themes/crystal/imgs/homepage.png',
    array  $json_ld  = []
): void {
    /* Titolo completo con branding */
    $full_title = htmlspecialchars($title) . ' — Crystal Tokyo GDR';
    $desc_esc   = htmlspecialchars($description);
    $can_esc    = htmlspecialchars($canonical);
    $img_esc    = htmlspecialchars($og_image);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- SEO base -->
    <title><?= $full_title ?></title>
    <meta name="description" content="<?= $desc_esc ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $can_esc ?>">

    <!-- Open Graph (anteprime su social / Discord / WhatsApp) -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= $can_esc ?>">
    <meta property="og:title"       content="<?= $full_title ?>">
    <meta property="og:description" content="<?= $desc_esc ?>">
    <meta property="og:image"       content="<?= $img_esc ?>">
    <meta property="og:locale"      content="it_IT">
    <meta property="og:site_name"   content="Crystal Tokyo GDR">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $full_title ?>">
    <meta name="twitter:description" content="<?= $desc_esc ?>">
    <meta name="twitter:image"       content="<?= $img_esc ?>">

    <?php if (!empty($json_ld)): ?>
    <!-- Dati strutturati JSON-LD per Google rich results -->
    <script type="application/ld+json">
    <?= json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?>
    </script>
    <?php endif; ?>

    <!-- Google Fonts: Cinzel (headings fantasy) + Lato (body leggibile) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <!-- Font Awesome (icone — stesso file usato dal gioco) -->
    <link rel="stylesheet" href="/themes/crystal/fontawesome/css/all.min.css">

    <!-- CSS condiviso per tutte le pagine pubbliche -->
    <link rel="stylesheet" href="/public/public.css">

    <link rel="icon" type="image/x-icon" href="/imgs/favicon.ico">
</head>
<?php
}
