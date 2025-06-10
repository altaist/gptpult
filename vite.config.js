import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { quasar, transformAssetUrls } from '@quasar/vite-plugin'
import { fileURLToPath } from 'node:url'

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: { transformAssetUrls }
          }),
      
          // @quasar/plugin-vite options list:
          // https://github.com/quasarframework/quasar/blob/dev/vite-plugin/index.d.ts
          quasar({
            sassVariables: fileURLToPath(
              new URL('./resources/js/quasar-variables.sass', import.meta.url)
            )
          })
    ],
});
