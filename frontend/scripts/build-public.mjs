/**
 * build-public.mjs — Minifica public.css, public.js e critical.css
 * dopo il build Vite. Eseguito da npm run build.
 *
 * public.css e public.js vengono sovrascritti in-place (il sorgente
 * leggibile resta in git; il server riceve sempre la versione minificata
 * dopo ogni deploy via GitHub Actions).
 *
 * critical.min.css viene inlinato in header.inc.php per gli ospiti
 * per eliminare il render-blocking di public.css.
 */

import { build } from 'esbuild';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '../..');
const pub  = resolve(root, 'public');

await Promise.all([
    build({
        entryPoints: [`${pub}/public.css`],
        bundle: false,
        minify: true,
        outfile: `${pub}/public.css`,
        allowOverwrite: true,
        loader: { '.css': 'css' },
    }),
    build({
        entryPoints: [`${pub}/critical.css`],
        bundle: false,
        minify: true,
        outfile: `${pub}/critical.min.css`,
        loader: { '.css': 'css' },
    }),
    build({
        entryPoints: [`${pub}/public.js`],
        bundle: false,
        minify: true,
        outfile: `${pub}/public.js`,
        allowOverwrite: true,
    }),
]);

console.log('public.css, critical.min.css e public.js minificati');
