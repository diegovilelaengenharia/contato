import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from 'tailwindcss'
import autoprefixer from 'autoprefixer'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  css: {
    postcss: {
      plugins: [
        tailwindcss,
        autoprefixer,
      ],
    },
  },
  server: {
    proxy: {
      // Proxy API requests to PHP backend
      '/area-cliente': {
        target: 'http://localhost', // Change this if your PHP server runs on a different port
        changeOrigin: true,
        secure: false,
      }
    }
  },
  base: './' // Use relative paths for assets
})
