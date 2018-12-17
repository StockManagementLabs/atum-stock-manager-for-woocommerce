module.exports = {
	"root"         : true,
	"extends"      : "eslint:recommended",
	"globals"      : {
		"wp": true,
	},
	"env"          : {
		"node"   : true,
		"es6"    : true,
		"amd"    : true,
		"browser": true,
		"jquery" : true
	},
	"parserOptions": {
		"ecmaFeatures": {
			"globalReturn"                    : true,
			"generators"                      : false,
			"objectLiteralDuplicateProperties": false
		},
		"ecmaVersion" : 2015,
		"sourceType"  : "module"
	},
	"plugins"      : [
		"import"
	],
	"settings"     : {
		"import/core-modules": [],
		"import/ignore"      : [
			"node_modules",
			"\\.(coffee|scss|css|less|hbs|svg|json)$"
		]
	},
	"rules"        : {
		"no-alert"                       : "error",
		"array-bracket-newline"          : ["error", "consistent"],
		"array-bracket-spacing"          : ["error", "never"],
		"array-element-newline"          : ["error", { "multiline": true }],
		"block-spacing"                  : ["error", "always"],
		"brace-style"                    : ["error", "stroustrup"],
		"camelcase"                      : ["error", {"properties": "always"}],
		"capitalized-comments"           : ["error", "always", {"ignoreConsecutiveComments": true}],
		"comma-dangle"                   : [2,
			{
				"arrays"   : "always-multiline",
				"objects"  : "always-multiline",
				"imports"  : "always-multiline",
				"exports"  : "always-multiline",
				"functions": "ignore"
			}
		],
		"comma-spacing"                  : ["error", {"before": false, "after": true}],
		"comma-style"                    : ["error", "last"],
		"consistent-this"                : ["error", "self"],
		"eqeqeq"                         : ["error", "smart"],
		"func-call-spacing"              : ["error", "never"],
		"global-require"                 : "error",
		"id-length"                      : ["error", {"min": 3, "max": 30}],
		"indent"                         : ["error", "tab", { "SwitchCase": 1 , "VariableDeclarator": { "var": 2, "let": 2, "const": 3 }}],
		"key-spacing"                    : ["error", {
			"singleLine": {
				"beforeColon": false,
				"afterColon" : true
			},
			"multiLine" : {
				"afterColon" : true,
				"align"      : "colon"
			}
		}],
		"keyword-spacing"                : ["error", {"before": true}],
		"new-cap"                        : "error",
		"new-parens"                     : "error",
		"no-console"                     : process.env.NODE_ENV === 'production' ? 2 : 0,
		"no-eval"                        : "error",
		"no-multi-spaces"                : ["error", {
			exceptions: {
				"Property"          : true,
				"ImportDeclaration" : true,
				"VariableDeclarator": true
			}
		}],
		"no-lonely-if"                   : "error",
		"no-mixed-spaces-and-tabs"       : ["error", "smart-tabs"],
		"no-multi-assign"                : "error",
		"no-new-object"                  : "error",
		"no-new-wrappers"                : "error",
		"no-return-assign"               : "error",
		"no-self-compare"                : "error",
		"no-trailing-spaces"             : ["error", { "skipBlankLines": true }],
		"no-useless-concat"              : "error",
		"no-useless-return"              : "error",
		"no-undefined"                   : "error",
		"no-unneeded-ternary"            : "error",
		"no-use-before-define"           : ["error", {"functions": false, "classes": false}],
		"no-whitespace-before-property"  : "error",
		"object-curly-newline"           : ["error", {"consistent": true}],
		"one-var"                        : ["error", "always"],
		"one-var-declaration-per-line"   : ["error", "initializations"],
		"operator-assignment"            : ["error", "always"],
		"padding-line-between-statements": [
			"error",
			{blankLine: "always", prev: "*", next: "return"},
			{blankLine: "any", prev: ["const", "let", "var"], next: ["const", "let", "var"]}
		],
		"quote-props"                    : ["error", "consistent-as-needed"],
		"quotes"                         : ["error", "single", {"avoidEscape": true, "allowTemplateLiterals": true}],
		"semi-spacing"                   : ["error", {"before": false, "after": true}],
		"space-before-blocks"            : "error",
		"space-before-function-paren"    : ["error", "never"],
		"space-infix-ops"                : "error",
		"spaced-comment"                 : ["error", "always"],
		"switch-colon-spacing"           : "error",
		"vars-on-top"                    : "error"
	}
}
