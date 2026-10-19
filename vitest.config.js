import {defineConfig} from 'vitest/config';

// Dedicated test config so it never interferes with the BUILD_TARGET-driven
// production build in vite.config.js. JS unit tests live in tests-js/.
export default defineConfig({
  test: {
    environment: 'jsdom',
    include: ['tests-js/**/*.test.js'],
    globals: true
  }
});
