import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],

  // Base URL del bundle quando viene servito da PHP
  base: '/themes/crystal/dist/',

  build: {
    // Output nella cartella del tema, ignorata da git
    outDir: '../themes/crystal/dist',
    emptyOutDir: true,

    // Nomi file fissi (no hash) così i PHP possono includerli
    // con percorsi stabili senza dover leggere il manifest
    rollupOptions: {
      input: 'src/main.jsx',
      output: {
        entryFileNames: 'ct-app.js',
        chunkFileNames: 'ct-[name].js',
        assetFileNames: 'ct-[name][extname]',
      },
    },
  },
})
