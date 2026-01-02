module.exports = {
    content: [
        './pages/*.php',
        './**/*.html',
        './includes/*.js'
    ],
    css: ['./themes/crystal/**/*.css'],
    safelist: [
        // Classi dinamiche o generiche di GDRCD
        /^chat_/,
        /^menu_/,
        /^pg_/,
        /^npc_/,
        'meteo_box', // esempio specifico
        'slide_weapon'
    ],
    output: './themes/purged_css/'
};