import tsParser from '@typescript-eslint/parser';
import pluginStylistic from '@stylistic/eslint-plugin';

export default [
	pluginStylistic.configs[ 'recommended-flat' ],
	{
		plugins: { '@stylistic': pluginStylistic },
		rules  : {
			'@stylistic/indent'    : [ 'warn', 'tab' ],
			'@stylistic/jsx-indent': [ 'error', 'tab', {
				checkAttributes         : true,
				indentLogicalExpressions: true,
			} ],
			'@stylistic/indent-binary-ops': [ 'error', 'tab' ],
			'@stylistic/jsx-indent-props' : [ 'error', 'tab' ],
			'@stylistic/semi'             : [ 'warn', 'always' ],
			'@stylistic/quotes'           : [ 'error', 'single', {
				avoidEscape          : true,
				allowTemplateLiterals: true,
			} ],
			'@stylistic/array-bracket-spacing'    : [ 'warn', 'always' ],
			'@stylistic/no-tabs'                  : [ 'error', { allowIndentationTabs: true } ],
			'@stylistic/computed-property-spacing': [ 'warn', 'always' ],
			'@stylistic/jsx-curly-spacing'        : [ 'warn', 'always' ],
			'@stylistic/key-spacing'              : [ 'warn', {
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
			'@stylistic/space-in-parens'                : [ 'warn', 'always' ],
			'@stylistic/no-extra-semi'                  : 'error',
			'@stylistic/padding-line-between-statements': [
				'error',
				{ blankLine: 'always', prev: '*', next: 'return' },
				{ blankLine: 'always', prev: [ 'const', 'let', 'var' ], next: '*' },
				{ blankLine: 'any', prev: [ 'const', 'let', 'var' ], next: [ 'const', 'let', 'var' ] },
				{ blankLine: 'always', prev: [ 'case', 'default' ], next: '*' },
				{ blankLine: 'always', prev: [ 'block', 'block-like' ], next: '*' },
			],
			'@stylistic/switch-colon-spacing'   : 'warn',
			'@stylistic/template-curly-spacing' : [ 'warn', 'always' ],
			'@stylistic/no-multi-spaces'        : 'off', // TODO...
			'@stylistic/type-annotation-spacing': 'off', // TODO...
			'@stylistic/no-trailing-spaces'     : [ 'error', { skipBlankLines: true } ],
			'@stylistic/arrow-parens'           : [ 'warn', 'always' ],
			'@stylistic/member-delimiter-style' : [
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
			'@stylistic/multiline-comment-style': [ 'warn', 'starred-block' ],
			'@stylistic/padded-blocks'          : 'off',
			'capitalized-comments'              : [ 'warn', 'always' ],
			
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
