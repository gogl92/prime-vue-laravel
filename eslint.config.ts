import vue from 'eslint-plugin-vue';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';

export default [
    // Global ignores
    {
        ignores: [
            'node_modules',
            'vendor',
            'dist',
            'public',
            'bootstrap/ssr',
        ],
    },
    // Vue and TypeScript files
    ...defineConfigWithVueTs(
        vue.configs['flat/recommended'],
        vueTsConfigs.recommended,
        {
            rules: {
                // Vue Rules
                'vue/multi-word-component-names': 'off',
                'vue/html-indent': ['error', 4],
                'vue/block-order': ['error', {
                    order: ['script', 'template', 'style'],
                }],
                'vue/component-api-style': ['error', ['script-setup']],
                'vue/define-macros-order': ['error', {
                    order: ['defineProps', 'defineEmits'],
                }],
                'vue/no-v-html': 'warn',
                'vue/prefer-true-attribute-shorthand': 'error',
                'vue/prefer-separate-static-class': 'error',
                'vue/padding-line-between-blocks': ['error', 'always'],
                'vue/component-name-in-template-casing': ['error', 'PascalCase'],
                'vue/custom-event-name-casing': ['error', 'camelCase'],
                'vue/no-unused-refs': 'error',
                'vue/no-useless-v-bind': 'error',
                'vue/no-useless-mustaches': 'error',
                'vue/require-default-prop': 'warn',

                // TypeScript Rules - More Strict
                '@typescript-eslint/no-explicit-any': 'warn',
                '@typescript-eslint/explicit-function-return-type': 'off',
                '@typescript-eslint/explicit-module-boundary-types': 'off',
                '@typescript-eslint/no-unused-vars': ['error', {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                }],
                '@typescript-eslint/no-non-null-assertion': 'warn',
                '@typescript-eslint/prefer-nullish-coalescing': 'error',
                '@typescript-eslint/prefer-optional-chain': 'error',
                '@typescript-eslint/no-floating-promises': 'error',
                '@typescript-eslint/await-thenable': 'error',
                '@typescript-eslint/no-misused-promises': 'error',
                '@typescript-eslint/consistent-type-imports': ['error', {
                    prefer: 'type-imports',
                    fixStyle: 'inline-type-imports',
                }],

                // General Rules
                'indent': ['error', 4],
                'semi': ['error', 'always'],
                'quotes': ['error', 'single'],
                'linebreak-style': ['error', 'unix'],
                'comma-dangle': ['error', 'always-multiline'],
                'no-console': ['warn', { allow: ['warn', 'error'] }],
                'no-debugger': 'warn',
                'no-unused-vars': 'off', // Use TypeScript's instead
                'prefer-const': 'error',
                'no-var': 'error',
            },
        },
    ),
];
