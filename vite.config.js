// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    // ✅ ativa o Tailwind v4 no Vite
    tailwindcss(),

    // ✅ inclui o CSS do tema do Filament no build
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/filament/admin/theme.css', // <<-- adicionado
      ],
      refresh: true,
      hotFile: 'storage/framework/vite.hot', // bom garantir
    }),
  ],
});
