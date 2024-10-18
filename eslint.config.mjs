import tsParser from '@typescript-eslint/parser';
import pluginStylistic from '@stylistic/eslint-plugin';

export default [
    pluginStylistic.configs[ 'recommended-flat' ],
    {
        plugins: { '@stylistic': pluginStylistic },
        rules  : {
            'capitalized-comments'                : [ 'warn', 'always' ],
            '@stylistic/array-bracket-spacing'    : [ 'warn', 'always' ],
            '@stylistic/arrow-parens'             : [ 'warn', 'always' ],
            '@stylistic/computed-property-spacing': [ 'warn', 'always' ],
            '@stylistic/indent'                   : [ 'warn', 4, {
                VariableDeclarator: 'first',
                MemberExpression  : 1,
            } ],
            '@stylistic/indent-binary-ops': [ 'error', 'tab' ],
            '@stylistic/jsx-curly-spacing': [ 'warn', 'always' ],
            '@stylistic/jsx-indent'       : [ 'error', 'tab', {
                checkAttributes         : true,
                indentLogicalExpressions: true,
            } ],
            '@stylistic/jsx-indent-props': [ 'error', 'tab' ],
            '@stylistic/key-spacing'     : [ 'warn', {
                singleLine: {
                    beforeColon: false,
                    afterColon : true,
                },
                multiLine: {
                    beforeColon: false,
                    afterColon : true,
                    align      : 'colon',
                },
            } ],
            '@stylistic/member-delimiter-style': [
                'warn',
                {
                    multiline: {
                        delimiter  : 'semi',
                        requireLast: true,
                    },
                    singleline: {
                        delimiter  : 'semi',
                        requireLast: false,
                    },
                    multilineDetection: 'brackets',
                },
            ],
            '@stylistic/multiline-comment-style'        : [ 'warn', 'starred-block' ],
            '@stylistic/no-extra-semi'                  : 'error',
            '@stylistic/no-mixed-spaces-and-tabs'       : [ 'error', 'smart-tabs' ],
            '@stylistic/no-multi-spaces'                : 'off', // TODO...
            '@stylistic/no-tabs'                        : [ 'off' ],
            '@stylistic/no-trailing-spaces'             : [ 'error', { skipBlankLines: true } ],
            '@stylistic/padded-blocks'                  : 'off',
            '@stylistic/padding-line-between-statements': [
                'error',
                { blankLine: 'always', prev: '*', next: 'return' },
                { blankLine: 'always', prev: [ 'const', 'let', 'var' ], next: '*' },
                { blankLine: 'any', prev: [ 'const', 'let', 'var' ], next: [ 'const', 'let', 'var' ] },
                { blankLine: 'always', prev: [ 'case', 'default' ], next: '*' },
                { blankLine: 'always', prev: [ 'block', 'block-like' ], next: '*' },
            ],
            '@stylistic/quotes': [ 'error', 'single', {
                avoidEscape          : true,
                allowTemplateLiterals: true,
            } ],
            '@stylistic/semi'                   : [ 'warn', 'always' ],
            '@stylistic/space-in-parens'        : [ 'warn', 'always' ],
            '@stylistic/switch-colon-spacing'   : 'warn',
            '@stylistic/template-curly-spacing' : [ 'warn', 'always' ],
            '@stylistic/type-annotation-spacing': 'off', // TODO...
			
        },
    },
    // Parser setup
    {
        files          : [ 'assets/js/src/**/*.ts' ],
        languageOptions: { parser: tsParser },
    },
    {
        files          : [ 'assets/js/src/**/*.tsx' ],
        languageOptions: {
            parser       : tsParser,
            parserOptions: { jsx: true },
        },
    },
    {
        files: [ 'assets/js/src/**/*.js' ],
    },
    {
        files          : [ 'assets/js/src/**/*.jsx' ],
        languageOptions: { parserOptions: { jsx: true } },
    },
];
