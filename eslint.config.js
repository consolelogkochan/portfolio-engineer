import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import prettier from 'eslint-config-prettier';

export default tseslint.config(
    { ignores: ['node_modules', 'vendor', 'public/build'] },
    js.configs.recommended,
    tseslint.configs.recommended,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        plugins: {
            'react-hooks': reactHooks,
        },
        rules: {
            ...reactHooks.configs.recommended.rules,
        },
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
    },
    prettier,
);
