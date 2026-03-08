import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'node',
        include: ['resources/js/bank-import/__tests__/**/*.test.js'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/bank-import/**/*.js'],
            exclude: ['resources/js/bank-import/__tests__/**'],
        },
    },
});
