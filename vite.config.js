import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
  plugins: lazyPlugins(() => [
    laravel({
      input: ['resources/css/site.css', 'resources/js/site.js'],
      refresh: ['resources/views/**', 'app/Livewire/**'],
    }),
  ]),
  fmt: {
    printWidth: 100,
    tabWidth: 2,
    singleQuote: true,
    semi: true,
  },
  server: {
    watch: {
      ignored: [
        '**/.agents/**',
        '**/.claude/**',
        '**/.cursor/**',
        '**/.codex/**',
        '**/storage/framework/views/**',
        '**/vendor/**',
      ],
    },
  },
});
