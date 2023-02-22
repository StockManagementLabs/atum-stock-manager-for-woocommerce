/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/stock-central-kb.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/components/_help-guide.ts":
/*!*************************************************!*\
  !*** ./assets/js/src/components/_help-guide.ts ***!
  \*************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var intro_js_minified_intro_min__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! intro.js/minified/intro.min */ "./node_modules/intro.js/minified/intro.min.js");
/* harmony import */ var intro_js_minified_intro_min__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(intro_js_minified_intro_min__WEBPACK_IMPORTED_MODULE_0__);

var HelpGuide = (function () {
    function HelpGuide(settings) {
        this.settings = settings;
        this.IntroJs = intro_js_minified_intro_min__WEBPACK_IMPORTED_MODULE_0___default()();
        this.introSteps = [];
        this.step = 0;
        this.isAuto = false;
        this.guide = null;
        this.wpHooks = window['wp']['hooks'];
        var autoGuide = this.settings.get('autoHelpGuide');
        if (autoGuide && Array.isArray(autoGuide) && autoGuide.length) {
            this.introSteps = autoGuide;
            this.isAuto = true;
        }
        this.introOptions = (this.settings.get('introJsOptions') || {});
        this.prepareOptions();
        this.bindEvents();
    }
    HelpGuide.prototype.prepareOptions = function () {
        if (this.introSteps && Array.isArray(this.introSteps) && this.introSteps.length) {
            this.introSteps.forEach(function (step) {
                if (step.element) {
                    step.element = $(step.element).get(0);
                }
            });
            this.introOptions.steps = this.introSteps;
            this.runGuide();
        }
    };
    HelpGuide.prototype.bindEvents = function () {
        var _this = this;
        $('body').on('click', '.show-intro-guide', function (evt) {
            var $button = $(evt.currentTarget);
            _this.guide = $button.data('guide');
            if (!_this.guide) {
                return;
            }
            if ($button.data('guide-step')) {
                _this.step = parseInt($button.data('guide-step'));
            }
            if (_this.settings.get(_this.guide) && Array.isArray(_this.settings.get(_this.guide))) {
                _this.introSteps = _this.settings.get(_this.guide);
                _this.isAuto = false;
                _this.prepareOptions();
            }
            else {
                var data = {
                    action: 'atum_get_help_guide_steps',
                    security: _this.settings.get('helpGuideNonce'),
                    guide: _this.guide,
                };
                if ($button.data('guide-path')) {
                    data.path = $button.data('guide-path');
                }
                if (_this.settings.get('screenId')) {
                    data.screen = _this.settings.get('screenId');
                }
                $.ajax({
                    url: window['ajaxurl'],
                    method: 'post',
                    dataType: 'json',
                    data: data,
                    beforeSend: function () { return $button.addClass('loading-guide'); },
                    success: function (response) {
                        $button.removeClass('loading-guide');
                        if (response.success) {
                            _this.introSteps = response.data;
                            _this.isAuto = false;
                            _this.prepareOptions();
                        }
                    },
                });
            }
        });
    };
    HelpGuide.prototype.runGuide = function () {
        var _this = this;
        $('body').addClass('running-atum-help-guide');
        this.IntroJs.setOptions(this.introOptions);
        if (this.step) {
            this.IntroJs._currentStepNumber = this.step;
        }
        this.IntroJs.start();
        this.IntroJs.onexit(function () {
            $('body').removeClass('running-atum-help-guide');
            if (_this.isAuto && _this.settings.get('screenId')) {
                _this.saveClosedAutoGuide(_this.settings.get('screenId'));
            }
            _this.wpHooks.doAction('atum_helpGuide_onExit', _this.guide);
        });
    };
    HelpGuide.prototype.getHelpGuideButton = function (guide, path, icon, step) {
        if (path === void 0) { path = ''; }
        if (icon === void 0) { icon = 'indent-increase'; }
        if (step === void 0) { step = 0; }
        var dataAtts = "data-guide=\"".concat(guide, "\"");
        if (path) {
            dataAtts += " data-path=\"".concat(path, "\"");
        }
        if (step) {
            dataAtts += " data-guide-step=\"".concat(step, "\"");
        }
        return "<i class=\"atum-icon atmi-".concat(icon, " show-intro-guide atum-tooltip\" ").concat(dataAtts, " title=\"").concat(this.settings.get('showHelpGuide'), "\"></i>");
    };
    HelpGuide.prototype.setIntroSteps = function (introSteps) {
        this.introSteps = introSteps;
    };
    HelpGuide.prototype.saveClosedAutoGuide = function (screen) {
        $.ajax({
            url: window['ajaxurl'],
            method: 'post',
            data: {
                action: 'atum_save_closed_auto_guide',
                security: this.settings.get('helpGuideNonce'),
                screen: screen
            },
        });
    };
    return HelpGuide;
}());
/* harmony default export */ __webpack_exports__["default"] = (HelpGuide);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/_tooltip.ts":
/*!**********************************************!*\
  !*** ./assets/js/src/components/_tooltip.ts ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! bootstrap/js/dist/tooltip */ "./node_modules/bootstrap/js/dist/tooltip.js");
/* harmony import */ var bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_0__);

var Tooltip = (function () {
    function Tooltip(initialize) {
        if (initialize === void 0) { initialize = true; }
        if (initialize) {
            this.addTooltips();
        }
    }
    Tooltip.prototype.addTooltips = function ($wrapper) {
        var _this = this;
        if (!$wrapper) {
            $wrapper = $('body');
        }
        $wrapper.find('.tips, .atum-tooltip').each(function (index, elem) {
            var $tipEl = $(elem), title = $tipEl.data('tip') || $tipEl.attr('title') || $tipEl.attr('data-bs-original-title');
            if (title) {
                if (_this.getInstance($tipEl)) {
                    return;
                }
                new bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_0___default.a($tipEl.get(0), {
                    html: true,
                    title: title,
                    container: 'body',
                    delay: {
                        show: 100,
                        hide: 200,
                    },
                });
                $tipEl.on('inserted.bs.tooltip', function (evt) {
                    var tooltipId = $(evt.currentTarget).attr('aria-describedby');
                    $('.tooltip[class*="bs-tooltip-"]').not("#".concat(tooltipId)).remove();
                });
            }
        });
    };
    Tooltip.prototype.destroyTooltips = function ($wrapper) {
        var _this = this;
        if (!$wrapper) {
            $wrapper = $('body');
        }
        $wrapper.find('.tips, .atum-tooltip').each(function (index, elem) {
            var tooltip = _this.getInstance($(elem));
            if (tooltip) {
                tooltip.dispose();
            }
        });
    };
    Tooltip.prototype.getInstance = function ($tipEl) {
        return bootstrap_js_dist_tooltip__WEBPACK_IMPORTED_MODULE_0___default.a.getInstance($tipEl.get(0));
    };
    return Tooltip;
}());
/* harmony default export */ __webpack_exports__["default"] = (Tooltip);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/knowledge-base/_stock-central.ts":
/*!*******************************************************************!*\
  !*** ./assets/js/src/components/knowledge-base/_stock-central.ts ***!
  \*******************************************************************/
/*! exports provided: StockCentralKnowledgeBase */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "StockCentralKnowledgeBase", function() { return StockCentralKnowledgeBase; });
var StockCentralKnowledgeBase = (function () {
    function StockCentralKnowledgeBase(globals, settings, toolTip, helpGuide) {
        this.globals = globals;
        this.settings = settings;
        this.toolTip = toolTip;
        this.helpGuide = helpGuide;
        this.knowledgeEnabled = false;
        this.wpHooks = window['wp']['hooks'];
        this.guides = this.settings.get('AKBGuides');
        this.$akbButtonsWrapper = $(document).find('.akb-buttons-wrapper');
        this.bindEvents();
        this.addHooks();
        this.addHelpGuides();
        this.checkKnowledgeVisibility();
    }
    StockCentralKnowledgeBase.prototype.bindEvents = function () {
        var _this = this;
        this.$akbButtonsWrapper
            .on('click', '.display-akb-button', function (e) {
            var $btn = $(e.currentTarget);
            _this.knowledgeEnabled = !_this.knowledgeEnabled;
            $btn.toggleClass('btn-success', _this.knowledgeEnabled);
            _this.checkKnowledgeVisibility();
        });
    };
    StockCentralKnowledgeBase.prototype.addHooks = function () {
        var _this = this;
        this.wpHooks.addAction('atum_listTable_tableUpdated', 'atum', function () { return _this.addRefreshedHelpGuides(); });
    };
    StockCentralKnowledgeBase.prototype.addHelpGuides = function () {
        var _this = this;
        this.guides.forEach(function (guide, index) {
            if ('init' === guide.load) {
                var $elem = guide.absolute ? $(guide.element) : _this.globals.$atumList.find(guide.element);
                if (guide.first) {
                    $elem = $elem.first();
                }
                _this.addWrapper($elem, index + 1);
            }
        });
        this.addRefreshedHelpGuides();
    };
    StockCentralKnowledgeBase.prototype.addRefreshedHelpGuides = function () {
        var _this = this;
        this.guides.forEach(function (guide, index) {
            if ('lazzy' === guide.load) {
                var $elem = guide.absolute ? $(guide.element) : _this.globals.$atumList.find(guide.element);
                if (guide.first) {
                    $elem = $elem.first();
                }
                _this.addWrapper($elem, index + 1);
            }
        });
        this.checkKnowledgeVisibility();
    };
    StockCentralKnowledgeBase.prototype.addWrapper = function ($elem, step) {
        if (!$elem.length) {
            return;
        }
        var $wrapper = $('<span class="akb-wrapper">');
        $elem.data('step', step);
        $elem.before($wrapper);
        $elem.appendTo($wrapper);
        $elem.after(this.addButton(step));
    };
    StockCentralKnowledgeBase.prototype.addButton = function (step) {
        if (step === void 0) { step = 0; }
        return this.helpGuide.getHelpGuideButton('stock-central', '', 'question atum-kb', step);
    };
    StockCentralKnowledgeBase.prototype.checkKnowledgeVisibility = function () {
        var $akb = $('.atum-kb');
        if (this.knowledgeEnabled) {
            $akb.show();
        }
        else {
            $akb.hide();
        }
    };
    return StockCentralKnowledgeBase;
}());


/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/list-table/_globals.ts":
/*!*********************************************************!*\
  !*** ./assets/js/src/components/list-table/_globals.ts ***!
  \*********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/_utils */ "./assets/js/src/utils/_utils.ts");
var __assign = (undefined && undefined.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};

var Globals = (function () {
    function Globals(settings, defaults) {
        this.settings = settings;
        this.defaults = defaults;
        this.$atumList = null;
        this.$atumTable = null;
        this.$editInput = null;
        this.$searchInput = null;
        this.$autoFilters = null;
        this.autoFiltersNames = [];
        this.$searchColumnBtn = null;
        this.$searchColumnDropdown = null;
        this.$stickyCols = null;
        this.$floatTheadStickyCols = null;
        this.enabledStickyColumns = false;
        this.enabledStickyHeader = false;
        this.$scrollPane = null;
        this.jScrollApi = null;
        this.$collapsedGroups = null;
        this.filterData = {};
        this.initProps();
    }
    Globals.prototype.initProps = function () {
        var _this = this;
        this.$atumList = (this.defaults && this.defaults.$atumList) || $('.atum-list-wrapper');
        this.$atumTable = (this.defaults && this.defaults.$atumTable) || this.$atumList.find('.atum-list-table');
        this.$editInput = (this.defaults && this.defaults.$editInput) || this.$atumList.find('#atum-column-edits');
        this.$searchInput = (this.defaults && this.defaults.$searchInput) || this.$atumList.find('.atum-post-search');
        this.$autoFilters = this.$atumList.find('#filters_container .auto-filter');
        this.$searchColumnBtn = (this.defaults && this.defaults.$searchColumnBtn) || this.$atumList.find('#search_column_btn');
        this.$searchColumnDropdown = (this.defaults && this.defaults.$searchColumnDropdown) || this.$atumList.find('#search_column_dropdown');
        this.$autoFilters.each(function (index, elem) {
            _this.autoFiltersNames.push($(elem).attr('name'));
        });
        var inputPerPage = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val();
        var perPage;
        if (!_utils_utils__WEBPACK_IMPORTED_MODULE_0__["default"].isNumeric(inputPerPage)) {
            perPage = this.settings.get('perPage') || 20;
        }
        else {
            perPage = parseInt(inputPerPage);
        }
        this.filterData = (this.defaults && this.defaults.filterData) || __assign({ action: this.$atumList.data('action'), security: this.settings.get('nonce'), screen: this.$atumList.data('screen'), per_page: perPage, paged: 1, show_cb: this.settings.get('showCb'), show_controlled: (_utils_utils__WEBPACK_IMPORTED_MODULE_0__["default"].filterQuery(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0, order: this.settings.get('order'), orderby: this.settings.get('orderby'), s: '', search_column: '', sold_last_days: '', view: '' }, this.getAutoFiltersValues(false, true));
    };
    Globals.prototype.getAutoFiltersValues = function (getFromAddress, emptyValues) {
        if (getFromAddress === void 0) { getFromAddress = false; }
        if (emptyValues === void 0) { emptyValues = false; }
        var autoFiltersValues = {};
        this.$autoFilters.each(function (index, elem) {
            var $elem = $(elem), name = $elem.attr('name');
            var value;
            if (getFromAddress) {
                value = $.address.parameter(name) || '';
            }
            else {
                value = emptyValues ? '' : $elem.val() || '';
            }
            autoFiltersValues[name] = value;
        });
        return autoFiltersValues;
    };
    return Globals;
}());
/* harmony default export */ __webpack_exports__["default"] = (Globals);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/config/_settings.ts":
/*!*******************************************!*\
  !*** ./assets/js/src/config/_settings.ts ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Settings = (function () {
    function Settings(varName, defaults) {
        if (defaults === void 0) { defaults = {}; }
        this.varName = varName;
        this.defaults = defaults;
        this.settings = {};
        var localizedOpts = typeof window[varName] !== 'undefined' ? window[varName] : {};
        Object.assign(this.settings, defaults, localizedOpts);
    }
    Settings.prototype.get = function (prop) {
        if (typeof this.settings[prop] !== 'undefined') {
            return this.settings[prop];
        }
        return undefined;
    };
    Settings.prototype.getAll = function () {
        return this.settings;
    };
    Settings.prototype.delete = function (prop) {
        if (this.settings.hasOwnProperty(prop)) {
            delete this.settings[prop];
        }
    };
    return Settings;
}());
/* harmony default export */ __webpack_exports__["default"] = (Settings);


/***/ }),

/***/ "./assets/js/src/stock-central-kb.ts":
/*!*******************************************!*\
  !*** ./assets/js/src/stock-central-kb.ts ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var _vendor_jquery_address_min__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../vendor/jquery.address.min */ "./assets/js/vendor/jquery.address.min.js");
/* harmony import */ var _vendor_jquery_address_min__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_vendor_jquery_address_min__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _vendor_jquery_jscrollpane__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../vendor/jquery.jscrollpane */ "./assets/js/vendor/jquery.jscrollpane.js");
/* harmony import */ var _vendor_jquery_jscrollpane__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_vendor_jquery_jscrollpane__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _vendor_select2__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../vendor/select2 */ "./assets/js/vendor/select2.js");
/* harmony import */ var _vendor_select2__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_vendor_select2__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_list_table_globals__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/list-table/_globals */ "./assets/js/src/components/list-table/_globals.ts");
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_tooltip__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/_tooltip */ "./assets/js/src/components/_tooltip.ts");
/* harmony import */ var _components_help_guide__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./components/_help-guide */ "./assets/js/src/components/_help-guide.ts");
/* harmony import */ var _components_knowledge_base_stock_central__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./components/knowledge-base/_stock-central */ "./assets/js/src/components/knowledge-base/_stock-central.ts");








jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_4__["default"]('atumStockCentralKB');
    var globals = new _components_list_table_globals__WEBPACK_IMPORTED_MODULE_3__["default"](settings);
    var tooltip = new _components_tooltip__WEBPACK_IMPORTED_MODULE_5__["default"]();
    var helpGuide = new _components_help_guide__WEBPACK_IMPORTED_MODULE_6__["default"](settings);
    new _components_knowledge_base_stock_central__WEBPACK_IMPORTED_MODULE_7__["StockCentralKnowledgeBase"](globals, settings, tooltip, helpGuide);
});

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/utils/_utils.ts":
/*!***************************************!*\
  !*** ./assets/js/src/utils/_utils.ts ***!
  \***************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var js_big_decimal__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! js-big-decimal */ "./node_modules/js-big-decimal/dist/node/js-big-decimal.js");
/* harmony import */ var js_big_decimal__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(js_big_decimal__WEBPACK_IMPORTED_MODULE_0__);
var __spreadArray = (undefined && undefined.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};

var Utils = {
    settings: {
        delayTimer: 0,
        number: {
            precision: 0,
            grouping: 3,
            thousand: ',',
            decimal: '.',
        },
        currency: {
            symbol: '$',
            format: '%s%v',
            decimal: '.',
            thousand: ',',
            precision: 2,
            grouping: 3,
        },
    },
    delay: function (callback, ms) {
        clearTimeout(this.settings.delayTimer);
        this.settings.delayTimer = setTimeout(callback, ms);
        return this.settings.delayTimer;
    },
    filterQuery: function (query, variable) {
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            if (pair[0] === variable) {
                return pair[1];
            }
        }
        return false;
    },
    filterByData: function ($elem, prop, val) {
        if (typeof val === 'undefined') {
            return $elem.filter(function (index, elem) {
                return typeof $(elem).data(prop) !== 'undefined';
            });
        }
        ;
        return $elem.filter(function (index, elem) {
            return $(elem).data(prop) == val;
        });
    },
    addNotice: function (type, msg, autoDismiss, dismissSeconds) {
        if (autoDismiss === void 0) { autoDismiss = false; }
        if (dismissSeconds === void 0) { dismissSeconds = 5; }
        var $notice = $("<div class=\"".concat(type, " notice is-dismissible\"><p><strong>").concat(msg, "</strong></p></div>")).hide(), $dismissButton = $('<button />', { type: 'button', class: 'notice-dismiss' }), $headerEnd = $('.wp-header-end');
        $headerEnd.siblings('.notice').remove();
        $headerEnd.before($notice.append($dismissButton));
        $notice.slideDown(100);
        $dismissButton.on('click.wp-dismiss-notice', function (evt) {
            evt.preventDefault();
            $notice.fadeTo(100, 0, function () {
                $notice.slideUp(100, function () {
                    $notice.remove();
                });
            });
        });
        if (autoDismiss) {
            setTimeout(function () {
                $dismissButton.trigger('click.wp-dismiss-notice');
            }, dismissSeconds * 1000);
        }
    },
    imagesLoaded: function ($wrapper) {
        var $imgs = $wrapper.find('img[src!=""]');
        if (!$imgs.length) {
            return $.Deferred().resolve().promise();
        }
        var dfds = [];
        $imgs.each(function (index, elem) {
            var dfd = $.Deferred(), img = new Image();
            dfds.push(dfd);
            img.onload = function () { return dfd.resolve(); };
            img.onerror = function () { return dfd.resolve(); };
            img.src = $(elem).attr('src');
        });
        return $.when.apply($, dfds);
    },
    getUrlParameter: function (name) {
        if (typeof URLSearchParams !== 'undefined') {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        else {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)'), results = regex.exec(window.location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }
    },
    htmlDecode: function (input) {
        var e = document.createElement('div');
        e.innerHTML = input;
        return e.childNodes[0].nodeValue;
    },
    areEquivalent: function (a, b, strict) {
        if (strict === void 0) { strict = false; }
        var aProps = Object.getOwnPropertyNames(a), bProps = Object.getOwnPropertyNames(b);
        if (aProps.length != bProps.length) {
            return false;
        }
        for (var i = 0; i < aProps.length; i++) {
            var propName = aProps[i];
            if ((strict && a[propName] !== b[propName]) || (!strict && a[propName] != b[propName])) {
                return false;
            }
        }
        return true;
    },
    toggleNodes: function (nodes, openOrClose) {
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].isExpanded = openOrClose == 'open';
            if (nodes[i].children && nodes[i].children.length > 0) {
                this.toggleNodes(nodes[i].children, openOrClose);
            }
        }
    },
    formatNumber: function (number, precision, thousand, decimal, stripZeros) {
        var _this = this;
        if (precision === void 0) { precision = this.settings.number.precision; }
        if (thousand === void 0) { thousand = this.settings.number.thousand; }
        if (decimal === void 0) { decimal = this.settings.number.decimal; }
        if (stripZeros === void 0) { stripZeros = false; }
        if (Array.isArray(number)) {
            return $.map(number, function (val) { return _this.formatNumber(val, precision, thousand, decimal, stripZeros); });
        }
        number = this.unformat(number);
        var usePrecision = this.checkPrecision(precision), negative = number < 0 ? '-' : '', base = parseInt(this.toFixed(Math.abs(number || 0), usePrecision), 10) + '', mod = base.length > 3 ? base.length % 3 : 0;
        var decimalsPart = '';
        if (usePrecision) {
            decimalsPart = this.toFixed(Math.abs(number), usePrecision);
            if (stripZeros) {
                decimalsPart = Number(decimalsPart).toString();
            }
            decimalsPart = decimalsPart.includes('.') ? decimal + decimalsPart.split('.')[1] : '';
        }
        return negative + (mod ? base.substr(0, mod) + thousand : '') + base.substr(mod).replace(/(\d{3})(?=\d)/g, '$1' + thousand) + decimalsPart;
    },
    formatMoney: function (number, symbol, precision, thousand, decimal, format) {
        var _this = this;
        if (symbol === void 0) { symbol = this.settings.currency.symbol; }
        if (precision === void 0) { precision = this.settings.currency.precision; }
        if (thousand === void 0) { thousand = this.settings.currency.thousand; }
        if (decimal === void 0) { decimal = this.settings.currency.decimal; }
        if (format === void 0) { format = this.settings.currency.format; }
        if (Array.isArray(number)) {
            return $.map(number, function (val) { return _this.formatMoney(val, symbol, precision, thousand, decimal, format); });
        }
        number = this.unformat(number);
        var formats = this.checkCurrencyFormat(format), useFormat = number > 0 ? formats.pos : number < 0 ? formats.neg : formats.zero;
        return useFormat.replace('%s', symbol).replace('%v', this.formatNumber(Math.abs(number), this.checkPrecision(precision), thousand, decimal));
    },
    unformat: function (value, decimal) {
        var _this = this;
        if (decimal === void 0) { decimal = this.settings.number.decimal; }
        if (Array.isArray(value)) {
            return $.map(value, function (val) { return _this.unformat(val, decimal); });
        }
        value = value || 0;
        if (typeof value === 'number') {
            return value;
        }
        var regex = new RegExp("[^0-9-".concat(decimal, "]"), 'g'), unformatted = parseFloat(('' + value)
            .replace(/\((.*)\)/, '-$1')
            .replace(regex, '')
            .replace(decimal, '.'));
        return !isNaN(unformatted) ? unformatted : 0;
    },
    checkPrecision: function (val, base) {
        if (base === void 0) { base = 0; }
        val = Math.round(Math.abs(val));
        return isNaN(val) ? base : val;
    },
    toFixed: function (value, precision) {
        precision = this.checkPrecision(precision, this.settings.number.precision);
        var power = Math.pow(10, precision);
        return (Math.round(this.unformat(value) * power) / power).toFixed(precision);
    },
    checkCurrencyFormat: function (format) {
        var defaults = this.settings.currency.format;
        if (typeof format === 'function') {
            format = format();
        }
        else if (typeof format === 'string' && format.match('%v')) {
            return {
                pos: format,
                neg: format.replace('-', '').replace('%v', '-%v'),
                zero: format,
            };
        }
        else if (!format || !format.pos || !format.pos.match('%v')) {
            return (typeof defaults !== 'string') ? defaults : this.settings.currency.format = {
                pos: defaults,
                neg: defaults.replace('%v', '-%v'),
                zero: defaults,
            };
        }
        return format;
    },
    isNumeric: function (n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    },
    convertElemsToString: function ($elems) {
        return $('<div />').append($elems).html();
    },
    mergeArrays: function (arr1, arr2) {
        return Array.from(new Set(__spreadArray(__spreadArray([], arr1, true), arr2, true)));
    },
    restrictNumberInputValues: function ($input) {
        if ($input.attr('type') !== 'number') {
            return;
        }
        var qty = $input.val();
        var value = parseFloat(qty || '0'), min = parseFloat($input.attr('min') || '0'), max = parseFloat($input.attr('max') || '0');
        if (!$.isNumeric(qty)) {
            $input.val(undefined !== $input.attr('min') && !isNaN(min) && min > 0 ? min : 0);
        }
        else if (undefined !== $input.attr('min') && value < min) {
            $input.val(min);
        }
        else if (undefined !== $input.attr('max') && value > max) {
            $input.val(max);
        }
    },
    checkRTL: function (value) {
        var isRTL = false;
        if ($('html[ dir="rtl" ]').length > 0) {
            isRTL = true;
        }
        switch (value) {
            case 'isRTL':
            case 'reverse':
                return isRTL;
                break;
            case 'xSide':
                if (isRTL) {
                    return 'right';
                }
                else {
                    return 'left';
                }
                break;
            default:
                return false;
                break;
        }
    },
    multiplyDecimal: function (multiplicand, multiplier) {
        return parseFloat(js_big_decimal__WEBPACK_IMPORTED_MODULE_0___default.a.multiply(multiplicand.toString(), multiplier.toString()));
    },
    divideDecimal: function (dividend, divisor, precision) {
        return parseFloat(js_big_decimal__WEBPACK_IMPORTED_MODULE_0___default.a.divide(dividend.toString(), divisor.toString(), precision));
    },
    sumDecimal: function (summand1, summand2) {
        return parseFloat(js_big_decimal__WEBPACK_IMPORTED_MODULE_0___default.a.add(summand1.toString(), summand2.toString()));
    },
    subtractDecimal: function (minuend, subtrahend) {
        return parseFloat(js_big_decimal__WEBPACK_IMPORTED_MODULE_0___default.a.subtract(minuend.toString(), subtrahend.toString()));
    },
    calcTaxesFromBase: function (price, rates) {
        var taxes = [0], preCompoundTaxes;
        $.each(rates, function (i, rate) {
            if ('yes' === rate['compound']) {
                return true;
            }
            taxes.push(price * rate['rate'] / 100);
        });
        preCompoundTaxes = taxes.reduce(function (a, b) { return a + b; }, 0);
        $.each(rates, function (i, rate) {
            var currentTax;
            if ('no' === rate['compound']) {
                return true;
            }
            currentTax = (price + preCompoundTaxes) * rate['rate'] / 100;
            taxes.push(currentTax);
            preCompoundTaxes += currentTax;
        });
        return taxes.reduce(function (a, b) { return a + b; }, 0);
    }
};
/* harmony default export */ __webpack_exports__["default"] = (Utils);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/vendor/jquery.address.min.js":
/*!************************************************!*\
  !*** ./assets/js/vendor/jquery.address.min.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/*! jQuery Address v${version} | (c) 2009, 2013 Rostislav Hristov | jquery.org/license */
!function(t){t.address=function(){var e,r,n,a,i=function(e){var r=t.extend(t.Event(e),function(){for(var e={},r=t.address.parameterNames(),n=0,a=r.length;n<a;n++)e[r[n]]=t.address.parameter(r[n]);return{value:t.address.value(),path:t.address.path(),pathNames:t.address.pathNames(),parameterNames:r,parameters:e,queryString:t.address.queryString()}}.call(t.address));return t(t.address).trigger(r),r},s=function(t){return Array.prototype.slice.call(t)},o=function(e,r,n){return t().bind.apply(t(t.address),Array.prototype.slice.call(arguments)),t.address},c=function(){return C.pushState&&A.state!==e},u=function(){return("/"+M.pathname.replace(new RegExp(A.state),"")+M.search+(d()?"#"+d():"")).replace(z,"/")},d=function(){var t=M.href.indexOf("#");return-1!=t?M.href.substr(t+1):""},l=function(){return c()?u():d()},p=function(){try{return top.document!==e&&top.document.title!==e&&top.jQuery!==e&&top.jQuery.address!==e&&!1!==top.jQuery.address.frames()?top:window}catch(t){return window}},h=function(t){return t=t.toString(),(A.strict&&"/"!=t.substr(0,1)?"/":"")+t},f=function(t,e){return parseInt(t.css(e),10)},v=function(){if(!G){var t=l();decodeURI(X)!=decodeURI(t)&&(O&&N<7?M.reload():(O&&!P&&A.history&&_(y,50),X=t,g(T)))}},g=function(t){return _(m,10),i(j).isDefaultPrevented()||i(t?I:U).isDefaultPrevented()},m=function(){if("null"!==A.tracker&&A.tracker!==x){var r=t.isFunction(A.tracker)?A.tracker:F[A.tracker],n=(M.pathname+M.search+(t.address&&!c()?t.address.value():"")).replace(/\/\//,"/").replace(/^\/$/,"");t.isFunction(r)?r(n):(t.isFunction(F.urchinTracker)&&F.urchinTracker(n),F.pageTracker!==e&&t.isFunction(F.pageTracker._trackPageview)&&F.pageTracker._trackPageview(n),F._gaq!==e&&t.isFunction(F._gaq.push)&&F._gaq.push(["_trackPageview",decodeURI(n)]),t.isFunction(F.ga)&&F.ga("send","pageview",n))}},y=function(){var t="javascript:"+T+";document.open();document.writeln('<html><head><title>"+L.title.replace(/\'/g,"\\'")+"</title><script>var "+k+' = "'+encodeURIComponent(l()).replace(/\'/g,"\\'")+(L.domain!=M.hostname?'";document.domain="'+L.domain:"")+"\";<\/script></head></html>');document.close();";N<7?r.src=t:r.contentWindow.location.replace(t)},w=function(){if(Q&&-1!=D){var t,e,r=Q.substr(D+1).split("&");for(t=0;t<r.length;t++)e=r[t].split("="),/^(autoUpdate|history|strict|wrap)$/.test(e[0])&&(A[e[0]]=isNaN(e[1])?/^(true|yes)$/i.test(e[1]):0!==parseInt(e[1],10)),/^(state|tracker)$/.test(e[0])&&(A[e[0]]=e[1]);Q=x}X=l()},b=function(){if(!H){if(H=R,w(),t('a[rel*="address:"]').address(),A.wrap){var n=t("body");t("body > *").wrapAll('<div style="padding:'+(f(n,"marginTop")+f(n,"paddingTop"))+"px "+(f(n,"marginRight")+f(n,"paddingRight"))+"px "+(f(n,"marginBottom")+f(n,"paddingBottom"))+"px "+(f(n,"marginLeft")+f(n,"paddingLeft"))+'px;" />').parent().wrap('<div id="'+k+'" style="height:100%;overflow:auto;position:relative;'+($&&!window.statusbar.visible?"resize:both;":"")+'" />');t("html, body").css({height:"100%",margin:0,padding:0,overflow:"hidden"}),$&&t('<style type="text/css" />').appendTo("head").text("#"+k+"::-webkit-resizer { background-color: #fff; }")}if(O&&!P){var a=L.getElementsByTagName("frameset")[0];(r=L.createElement((a?"":"i")+"frame")).src="javascript:"+T,a?(a.insertAdjacentElement("beforeEnd",r),a[a.cols?"cols":"rows"]+=",0",r.noResize=R,r.frameBorder=r.frameSpacing=0):(r.style.display="none",r.style.width=r.style.height=0,r.tabIndex=-1,L.body.insertAdjacentElement("afterBegin",r)),_(function(){t(r).bind("load",function(){var t=r.contentWindow;(X=t[k]!==e?t[k]:"")!=l()&&(g(T),M.hash=X)}),r.contentWindow[k]===e&&y()},50)}_(function(){i("init"),g(T)},1),c()||(O&&N>7||!O&&P?F.addEventListener?F.addEventListener(E,v,T):F.attachEvent&&F.attachEvent("on"+E,v):W(v,50)),"state"in window.history&&t(window).trigger("popstate")}},x=null,k="jQueryAddress",E="hashchange",S="init",j="change",I="internalChange",U="externalChange",R=!0,T=!1,A={autoUpdate:R,history:R,strict:R,frames:R,wrap:T},q=(n={},(a=function(t){t=t.toLowerCase();var e=/(chrome)[ \/]([\w.]+)/.exec(t)||/(webkit)[ \/]([\w.]+)/.exec(t)||/(opera)(?:.*version|)[ \/]([\w.]+)/.exec(t)||/(msie) ([\w.]+)/.exec(t)||t.indexOf("compatible")<0&&/(mozilla)(?:.*? rv:([\w.]+)|)/.exec(t)||[];return{browser:e[1]||"",version:e[2]||"0"}}(navigator.userAgent)).browser&&(n[a.browser]=!0,n.version=a.version),n.chrome?n.webkit=!0:n.webkit&&(n.safari=!0),n),N=parseFloat(q.version),$=q.webkit||q.safari,O=q.msie,F=p(),L=F.document,C=F.history,M=F.location,W=setInterval,_=setTimeout,z=/\/{2,9}/g,B=navigator.userAgent,P="on"+E in F,Q=t("script:last").attr("src"),D=Q?Q.indexOf("?"):-1,K=L.title,G=T,H=T,J=R,V=T,X=l();if(O){N=parseFloat(B.substr(B.indexOf("MSIE")+4)),L.documentMode&&L.documentMode!=N&&(N=8!=L.documentMode?7:8);var Y=L.onpropertychange;L.onpropertychange=function(){Y&&Y.call(L),L.title!=K&&-1!=L.title.indexOf("#"+l())&&(L.title=K)}}if(C.navigationMode&&(C.navigationMode="compatible"),"complete"==document.readyState)var Z=setInterval(function(){t.address&&(b(),clearInterval(Z))},50);else w(),t(b);return t(window).bind("popstate",function(){decodeURI(X)!=decodeURI(l())&&(X=l(),g(T))}).bind("unload",function(){F.removeEventListener?F.removeEventListener(E,v,T):F.detachEvent&&F.detachEvent("on"+E,v)}),{bind:function(t,e,r){return o.apply(this,s(arguments))},unbind:function(e,r){return function(e,r){return t().unbind.apply(t(t.address),Array.prototype.slice.call(arguments)),t.address}.apply(this,s(arguments))},init:function(t,e){return o.apply(this,[S].concat(s(arguments)))},change:function(t,e){return o.apply(this,[j].concat(s(arguments)))},internalChange:function(t,e){return o.apply(this,[I].concat(s(arguments)))},externalChange:function(t,e){return o.apply(this,[U].concat(s(arguments)))},baseURL:function(){var t=M.href;return-1!=t.indexOf("#")&&(t=t.substr(0,t.indexOf("#"))),/\/$/.test(t)&&(t=t.substr(0,t.length-1)),t},autoUpdate:function(t){return t!==e?(A.autoUpdate=t,this):A.autoUpdate},history:function(t){return t!==e?(A.history=t,this):A.history},state:function(t){if(t!==e){A.state=t;var r=u();return A.state!==e&&(C.pushState?"/#/"==r.substr(0,3)&&M.replace(A.state.replace(/^\/$/,"")+r.substr(2)):"/"!=r&&r.replace(/^\/#/,"")!=d()&&_(function(){M.replace(A.state.replace(/^\/$/,"")+"/#"+r)},1)),this}return A.state},frames:function(t){return t!==e?(A.frames=t,F=p(),this):A.frames},strict:function(t){return t!==e?(A.strict=t,this):A.strict},tracker:function(t){return t!==e?(A.tracker=t,this):A.tracker},wrap:function(t){return t!==e?(A.wrap=t,this):A.wrap},update:function(){return V=R,this.value(X),V=T,this},title:function(t){return t!==e?(_(function(){K=L.title=t,J&&r&&r.contentWindow&&r.contentWindow.document&&(r.contentWindow.document.title=t,J=T)},50),this):L.title},value:function(t){if(t!==e){if("/"==(t=h(t))&&(t=""),X==t&&!V)return;if(X=t,A.autoUpdate||V){if(g(R))return this;c()?C[A.history?"pushState":"replaceState"]({},"",A.state.replace(/\/$/,"")+(""===X?"/":X)):(G=R,$?A.history?M.hash="#"+X:M.replace("#"+X):X!=l()&&(A.history?M.hash="#"+X:M.replace("#"+X)),O&&!P&&A.history&&_(y,50),$?_(function(){G=T},1):G=T)}return this}return h(X)},path:function(t){if(t!==e){var r=this.queryString(),n=this.hash();return this.value(t+(r?"?"+r:"")+(n?"#"+n:"")),this}return h(X).split("#")[0].split("?")[0]},pathNames:function(){var t=this.path(),e=t.replace(z,"/").split("/");return"/"!=t.substr(0,1)&&0!==t.length||e.splice(0,1),"/"==t.substr(t.length-1,1)&&e.splice(e.length-1,1),e},queryString:function(t){if(t!==e){var r=this.hash();return this.value(this.path()+(t?"?"+t:"")+(r?"#"+r:"")),this}var n=X.split("?");return n.slice(1,n.length).join("?").split("#")[0]},parameter:function(r,n,a){var i,s;if(n!==e){var o=this.parameterNames();for(s=[],n=n===e||n===x?"":n.toString(),i=0;i<o.length;i++){var c=o[i],u=this.parameter(c);"string"==typeof u&&(u=[u]),c==r&&(u=n===x||""===n?[]:a?u.concat([n]):[n]);for(var d=0;d<u.length;d++)s.push(c+"="+u[d])}return-1==t.inArray(r,o)&&n!==x&&""!==n&&s.push(r+"="+n),this.queryString(s.join("&")),this}if(n=this.queryString()){var l=[];for(s=n.split("&"),i=0;i<s.length;i++){var p=s[i].split("=");p[0]==r&&l.push(p.slice(1).join("="))}if(0!==l.length)return 1!=l.length?l:l[0]}},parameterNames:function(){var e=this.queryString(),r=[];if(e&&-1!=e.indexOf("="))for(var n=e.split("&"),a=0;a<n.length;a++){var i=n[a].split("=")[0];-1==t.inArray(i,r)&&r.push(i)}return r},hash:function(t){if(t!==e)return this.value(X.split("#")[0]+(t?"#"+t:"")),this;var r=X.split("#");return r.slice(1,r.length).join("#")}}}(),t.fn.address=function(e){return t(this).each(function(r){t(this).data("address")||t(this).on("click",function(r){if(r.shiftKey||r.ctrlKey||r.metaKey||2==r.which)return!0;var n=r.currentTarget;if(t(n).is("a")){r.preventDefault();var a=e?e.call(n):/address:/.test(t(n).attr("rel"))?t(n).attr("rel").split("address:")[1].split(" ")[0]:void 0===t.address.state()||/^\/?$/.test(t.address.state())?t(n).attr("href").replace(/^(#\!?|\.)/,""):t(n).attr("href").replace(new RegExp("^(.*"+t.address.state()+"|\\.)"),"");t.address.value(a)}}).on("submit",function(r){var n=r.currentTarget;if(t(n).is("form")){r.preventDefault();var a=t(n).attr("action"),i=e?e.call(n):(-1!=a.indexOf("?")?a.replace(/&$/,""):a+"?")+t(n).serialize();t.address.value(i)}}).data("address",!0)}),this}}(jQuery);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/vendor/jquery.jscrollpane.js":
/*!************************************************!*\
  !*** ./assets/js/vendor/jquery.jscrollpane.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(jQuery) {/*!
 * jScrollPane - v2.2.1 - 2018-09-27
 * http://jscrollpane.kelvinluck.com/
 *
 * Copyright (c) 2014 Kelvin Luck
 * Copyright (c) 2017-2018 Tuukka Pasanen
 * Dual licensed under the MIT or GPL licenses.
 *
 * MODIFIED BY ATUM TO BE COMPATIBLE WITH WEBPACK
 */

(function($){
	
	$.fn.jScrollPane = function(settings)
	{
		// JScrollPane "class" - public methods are available through $('selector').data('jsp')
		function JScrollPane(elem, s)
		{
			var settings, jsp = this, pane, paneWidth, paneHeight, container, contentWidth, contentHeight,
			    percentInViewH, percentInViewV, isScrollableV, isScrollableH, verticalDrag, dragMaxY,
			    verticalDragPosition, horizontalDrag, dragMaxX, horizontalDragPosition,
			    verticalBar, verticalTrack, scrollbarWidth, verticalTrackHeight, verticalDragHeight, arrowUp, arrowDown,
			    horizontalBar, horizontalTrack, horizontalTrackWidth, horizontalDragWidth, arrowLeft, arrowRight,
			    reinitialiseInterval, originalPadding, originalPaddingTotalWidth, previousContentWidth,
			    wasAtTop = true, wasAtLeft = true, wasAtBottom = false, wasAtRight = false,
			    originalElement = elem.clone(false, false).empty(), resizeEventsAdded = false,
			    mwEvent = $.fn.mwheelIntent ? 'mwheelIntent.jsp' : 'mousewheel.jsp';
			
			var reinitialiseFn = function() {
				// if size has changed then reinitialise
				if (settings.resizeSensorDelay > 0) {
					setTimeout(function() {
						initialise(settings);
					}, settings.resizeSensorDelay);
				}
				else {
					initialise(settings);
				}
			};
			
			if (elem.css('box-sizing') === 'border-box') {
				originalPadding = 0;
				originalPaddingTotalWidth = 0;
			} else {
				originalPadding = elem.css('paddingTop') + ' ' +
					elem.css('paddingRight') + ' ' +
					elem.css('paddingBottom') + ' ' +
					elem.css('paddingLeft');
				originalPaddingTotalWidth = (parseInt(elem.css('paddingLeft'), 10) || 0) +
					(parseInt(elem.css('paddingRight'), 10) || 0);
			}
			
			function initialise(s)
			{
				
				var /*firstChild, lastChild, */isMaintainingPositon, lastContentX, lastContentY,
				    hasContainingSpaceChanged, originalScrollTop, originalScrollLeft,
				    newPaneWidth, newPaneHeight, maintainAtBottom = false, maintainAtRight = false;
				
				settings = s;
				
				if (pane === undefined) {
					originalScrollTop = elem.scrollTop();
					originalScrollLeft = elem.scrollLeft();
					
					elem.css(
						{
							overflow: 'hidden',
							padding: 0
						}
					);
					// TODO: Deal with where width/ height is 0 as it probably means the element is hidden and we should
					// come back to it later and check once it is unhidden...
					paneWidth = elem.innerWidth() + originalPaddingTotalWidth;
					paneHeight = elem.innerHeight();
					
					elem.width(paneWidth);
					
					pane = $('<div class="jspPane" />').css('padding', originalPadding).append(elem.children());
					container = $('<div class="jspContainer" />')
						.css({
								'width': paneWidth + 'px',
								'height': paneHeight + 'px'
							}
						).append(pane).appendTo(elem);
					
					/*
					// Move any margins from the first and last children up to the container so they can still
					// collapse with neighbouring elements as they would before jScrollPane
					firstChild = pane.find(':first-child');
					lastChild = pane.find(':last-child');
					elem.css(
						{
							'margin-top': firstChild.css('margin-top'),
							'margin-bottom': lastChild.css('margin-bottom')
						}
					);
					firstChild.css('margin-top', 0);
					lastChild.css('margin-bottom', 0);
					*/
				} else {
					elem.css('width', '');
					
					// To measure the required dimensions accurately, temporarily override the CSS positioning
					// of the container and pane.
					container.css({width: 'auto', height: 'auto'});
					pane.css('position', 'static');
					
					newPaneWidth = elem.innerWidth() + originalPaddingTotalWidth;
					newPaneHeight = elem.innerHeight();
					pane.css('position', 'absolute');
					
					maintainAtBottom = settings.stickToBottom && isCloseToBottom();
					maintainAtRight  = settings.stickToRight  && isCloseToRight();
					
					hasContainingSpaceChanged = newPaneWidth !== paneWidth || newPaneHeight !== paneHeight;
					
					paneWidth = newPaneWidth;
					paneHeight = newPaneHeight;
					container.css({width: paneWidth, height: paneHeight});
					
					// If nothing changed since last check...
					if (!hasContainingSpaceChanged && previousContentWidth == contentWidth && pane.outerHeight() == contentHeight) {
						elem.width(paneWidth);
						return;
					}
					previousContentWidth = contentWidth;
					
					pane.css('width', '');
					elem.width(paneWidth);
					
					container.find('>.jspVerticalBar,>.jspHorizontalBar').remove().end();
				}
				
				pane.css('overflow', 'auto');
				if (s.contentWidth) {
					contentWidth = s.contentWidth;
				} else {
					contentWidth = pane[0].scrollWidth;
				}
				contentHeight = pane[0].scrollHeight;
				pane.css('overflow', '');
				
				percentInViewH = contentWidth / paneWidth;
				percentInViewV = contentHeight / paneHeight;
				isScrollableV = percentInViewV > 1 || settings.alwaysShowVScroll;
				isScrollableH = percentInViewH > 1 || settings.alwaysShowHScroll;
				
				if (!(isScrollableH || isScrollableV)) {
					elem.removeClass('jspScrollable');
					pane.css({
						top: 0,
						left: 0,
						width: container.width() - originalPaddingTotalWidth
					});
					removeMousewheel();
					removeFocusHandler();
					removeKeyboardNav();
					removeClickOnTrack();
				} else {
					elem.addClass('jspScrollable');
					
					isMaintainingPositon = settings.maintainPosition && (verticalDragPosition || horizontalDragPosition);
					if (isMaintainingPositon) {
						lastContentX = contentPositionX();
						lastContentY = contentPositionY();
					}
					
					initialiseVerticalScroll();
					initialiseHorizontalScroll();
					resizeScrollbars();
					
					if (isMaintainingPositon) {
						scrollToX(maintainAtRight  ? (contentWidth  - paneWidth ) : lastContentX, false);
						scrollToY(maintainAtBottom ? (contentHeight - paneHeight) : lastContentY, false);
					}
					
					initFocusHandler();
					initMousewheel();
					initTouch();
					
					if (settings.enableKeyboardNavigation) {
						initKeyboardNav();
					}
					if (settings.clickOnTrack) {
						initClickOnTrack();
					}
					
					observeHash();
					if (settings.hijackInternalLinks) {
						hijackInternalLinks();
					}
				}
				
				if (!settings.resizeSensor && settings.autoReinitialise && !reinitialiseInterval) {
					reinitialiseInterval = setInterval(
						function()
						{
							initialise(settings);
						},
						settings.autoReinitialiseDelay
					);
				} else if (!settings.resizeSensor && !settings.autoReinitialise && reinitialiseInterval) {
					clearInterval(reinitialiseInterval);
				}
				
				if(settings.resizeSensor && !resizeEventsAdded) {
					
					// detect size change in content
					detectSizeChanges(pane, reinitialiseFn);
					
					// detect size changes of scroll element
					detectSizeChanges(elem, reinitialiseFn);
					
					// detect size changes of container
					detectSizeChanges(elem.parent(), reinitialiseFn);
					
					// add a reinit on window resize also for safety
					window.addEventListener('resize', reinitialiseFn);
					
					resizeEventsAdded = true;
				}
				
				if(originalScrollTop && elem.scrollTop(0)) {
					scrollToY(originalScrollTop, false);
				}
				
				if(originalScrollLeft && elem.scrollLeft(0)) {
					scrollToX(originalScrollLeft, false);
				}
				
				elem.trigger('jsp-initialised', [isScrollableH || isScrollableV]);
			}
			
			function detectSizeChanges(element, callback) {
				
				// create resize event elements - based on resize sensor: https://github.com/flowkey/resize-sensor/
				var resizeWidth, resizeHeight;
				var resizeElement = document.createElement('div');
				var resizeGrowElement = document.createElement('div');
				var resizeGrowChildElement = document.createElement('div');
				var resizeShrinkElement = document.createElement('div');
				var resizeShrinkChildElement = document.createElement('div');
				
				// add necessary styling
				resizeElement.style.cssText = 'position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: scroll; z-index: -1; visibility: hidden;';
				resizeGrowElement.style.cssText = 'position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: scroll; z-index: -1; visibility: hidden;';
				resizeShrinkElement.style.cssText = 'position: absolute; left: 0; top: 0; right: 0; bottom: 0; overflow: scroll; z-index: -1; visibility: hidden;';
				
				resizeGrowChildElement.style.cssText = 'position: absolute; left: 0; top: 0;';
				resizeShrinkChildElement.style.cssText = 'position: absolute; left: 0; top: 0; width: 200%; height: 200%;';
				
				// Create a function to programmatically update sizes
				var updateSizes = function() {
					
					resizeGrowChildElement.style.width = resizeGrowElement.offsetWidth + 10 + 'px';
					resizeGrowChildElement.style.height = resizeGrowElement.offsetHeight + 10 + 'px';
					
					resizeGrowElement.scrollLeft = resizeGrowElement.scrollWidth;
					resizeGrowElement.scrollTop = resizeGrowElement.scrollHeight;
					
					resizeShrinkElement.scrollLeft = resizeShrinkElement.scrollWidth;
					resizeShrinkElement.scrollTop = resizeShrinkElement.scrollHeight;
					
					resizeWidth = element.width();
					resizeHeight = element.height();
				};
				
				// create functions to call when content grows
				var onGrow = function() {
					
					// check to see if the content has change size
					if (element.width() > resizeWidth || element.height() > resizeHeight) {
						
						// if size has changed then reinitialise
						callback.apply(this, []);
					}
					// after reinitialising update sizes
					updateSizes();
				};
				
				// create functions to call when content shrinks
				var onShrink = function() {
					
					// check to see if the content has change size
					if (element.width() < resizeWidth || element.height() < resizeHeight) {
						
						// if size has changed then reinitialise
						callback.apply(this, []);
					}
					// after reinitialising update sizes
					updateSizes();
				};
				
				// bind to scroll events
				resizeGrowElement.addEventListener('scroll', onGrow.bind(this));
				resizeShrinkElement.addEventListener('scroll', onShrink.bind(this));
				
				// nest elements before adding to pane
				resizeGrowElement.appendChild(resizeGrowChildElement);
				resizeShrinkElement.appendChild(resizeShrinkChildElement);
				
				resizeElement.appendChild(resizeGrowElement);
				resizeElement.appendChild(resizeShrinkElement);
				
				element.append(resizeElement);
				
				// ensure parent element is not statically positioned
				if(window.getComputedStyle(element[0], null).getPropertyValue('position') === 'static') {
					element[0].style.position = 'relative';
				}
				
				// update sizes initially
				updateSizes();
			}
			
			function initialiseVerticalScroll()
			{
				if (isScrollableV) {
					
					container.append(
						$('<div class="jspVerticalBar" />').append(
							$('<div class="jspCap jspCapTop" />'),
							$('<div class="jspTrack" />').append(
								$('<div class="jspDrag" />').append(
									$('<div class="jspDragTop" />'),
									$('<div class="jspDragBottom" />')
								)
							),
							$('<div class="jspCap jspCapBottom" />')
						)
					);
					
					verticalBar = container.find('>.jspVerticalBar');
					verticalTrack = verticalBar.find('>.jspTrack');
					verticalDrag = verticalTrack.find('>.jspDrag');
					
					if (settings.showArrows) {
						arrowUp = $('<a class="jspArrow jspArrowUp" />').on(
							'mousedown.jsp', getArrowScroll(0, -1)
						).on('click.jsp', nil);
						arrowDown = $('<a class="jspArrow jspArrowDown" />').on(
							'mousedown.jsp', getArrowScroll(0, 1)
						).on('click.jsp', nil);
						if (settings.arrowScrollOnHover) {
							arrowUp.on('mouseover.jsp', getArrowScroll(0, -1, arrowUp));
							arrowDown.on('mouseover.jsp', getArrowScroll(0, 1, arrowDown));
						}
						
						appendArrows(verticalTrack, settings.verticalArrowPositions, arrowUp, arrowDown);
					}
					
					verticalTrackHeight = paneHeight;
					container.find('>.jspVerticalBar>.jspCap:visible,>.jspVerticalBar>.jspArrow').each(
						function()
						{
							verticalTrackHeight -= $(this).outerHeight();
						}
					);
					
					
					verticalDrag.on(
						"mouseenter",
						function()
						{
							verticalDrag.addClass('jspHover');
						}
					).on(
						"mouseleave",
						function()
						{
							verticalDrag.removeClass('jspHover');
						}
					).on(
						'mousedown.jsp',
						function(e)
						{
							// Stop IE from allowing text selection
							$('html').on('dragstart.jsp selectstart.jsp', nil);
							
							verticalDrag.addClass('jspActive');
							
							var startY = e.pageY - verticalDrag.position().top;
							
							$('html').on(
								'mousemove.jsp',
								function(e)
								{
									positionDragY(e.pageY - startY, false);
								}
							).on('mouseup.jsp mouseleave.jsp', cancelDrag);
							return false;
						}
					);
					sizeVerticalScrollbar();
				}
			}
			
			function sizeVerticalScrollbar()
			{
				verticalTrack.height(verticalTrackHeight + 'px');
				verticalDragPosition = 0;
				scrollbarWidth = settings.verticalGutter + verticalTrack.outerWidth();
				
				// Make the pane thinner to allow for the vertical scrollbar
				pane.width(paneWidth - scrollbarWidth - originalPaddingTotalWidth);
				
				// Add margin to the left of the pane if scrollbars are on that side (to position
				// the scrollbar on the left or right set it's left or right property in CSS)
				try {
					if (verticalBar.position().left === 0) {
						pane.css('margin-left', scrollbarWidth + 'px');
					}
				} catch (err) {
				}
			}
			
			function initialiseHorizontalScroll()
			{
				if (isScrollableH) {
					
					container.append(
						$('<div class="jspHorizontalBar" />').append(
							$('<div class="jspCap jspCapLeft" />'),
							$('<div class="jspTrack" />').append(
								$('<div class="jspDrag" />').append(
									$('<div class="jspDragLeft" />'),
									$('<div class="jspDragRight" />')
								)
							),
							$('<div class="jspCap jspCapRight" />')
						)
					);
					
					horizontalBar = container.find('>.jspHorizontalBar');
					horizontalTrack = horizontalBar.find('>.jspTrack');
					horizontalDrag = horizontalTrack.find('>.jspDrag');
					
					if (settings.showArrows) {
						arrowLeft = $('<a class="jspArrow jspArrowLeft" />').on(
							'mousedown.jsp', getArrowScroll(-1, 0)
						).on('click.jsp', nil);
						arrowRight = $('<a class="jspArrow jspArrowRight" />').on(
							'mousedown.jsp', getArrowScroll(1, 0)
						).on('click.jsp', nil);
						if (settings.arrowScrollOnHover) {
							arrowLeft.on('mouseover.jsp', getArrowScroll(-1, 0, arrowLeft));
							arrowRight.on('mouseover.jsp', getArrowScroll(1, 0, arrowRight));
						}
						appendArrows(horizontalTrack, settings.horizontalArrowPositions, arrowLeft, arrowRight);
					}
					
					horizontalDrag.on(
						"mouseenter",
						function()
						{
							horizontalDrag.addClass('jspHover');
						}
					).on(
						"mouseleave",
						function()
						{
							horizontalDrag.removeClass('jspHover');
						}
					).on(
						'mousedown.jsp',
						function(e)
						{
							// Stop IE from allowing text selection
							$('html').on('dragstart.jsp selectstart.jsp', nil);
							
							horizontalDrag.addClass('jspActive');
							
							var startX = e.pageX - horizontalDrag.position().left;
							
							$('html').on(
								'mousemove.jsp',
								function(e)
								{
									positionDragX(e.pageX - startX, false);
								}
							).on('mouseup.jsp mouseleave.jsp', cancelDrag);
							return false;
						}
					);
					horizontalTrackWidth = container.innerWidth();
					sizeHorizontalScrollbar();
				}
			}
			
			function sizeHorizontalScrollbar()
			{
				container.find('>.jspHorizontalBar>.jspCap:visible,>.jspHorizontalBar>.jspArrow').each(
					function()
					{
						horizontalTrackWidth -= $(this).outerWidth();
					}
				);
				
				horizontalTrack.width(horizontalTrackWidth + 'px');
				horizontalDragPosition = 0;
			}
			
			function resizeScrollbars()
			{
				if (isScrollableH && isScrollableV) {
					var horizontalTrackHeight = horizontalTrack.outerHeight(),
					    verticalTrackWidth = verticalTrack.outerWidth();
					verticalTrackHeight -= horizontalTrackHeight;
					$(horizontalBar).find('>.jspCap:visible,>.jspArrow').each(
						function()
						{
							horizontalTrackWidth += $(this).outerWidth();
						}
					);
					horizontalTrackWidth -= verticalTrackWidth;
					paneHeight -= verticalTrackWidth;
					paneWidth -= horizontalTrackHeight;
					horizontalTrack.parent().append(
						$('<div class="jspCorner" />').css('width', horizontalTrackHeight + 'px')
					);
					sizeVerticalScrollbar();
					sizeHorizontalScrollbar();
				}
				// reflow content
				if (isScrollableH) {
					pane.width((container.outerWidth() - originalPaddingTotalWidth) + 'px');
				}
				contentHeight = pane.outerHeight();
				percentInViewV = contentHeight / paneHeight;
				
				if (isScrollableH) {
					horizontalDragWidth = Math.ceil(1 / percentInViewH * horizontalTrackWidth);
					if (horizontalDragWidth > settings.horizontalDragMaxWidth) {
						horizontalDragWidth = settings.horizontalDragMaxWidth;
					} else if (horizontalDragWidth < settings.horizontalDragMinWidth) {
						horizontalDragWidth = settings.horizontalDragMinWidth;
					}
					horizontalDrag.css('width', horizontalDragWidth + 'px');
					dragMaxX = horizontalTrackWidth - horizontalDragWidth;
					_positionDragX(horizontalDragPosition); // To update the state for the arrow buttons
				}
				if (isScrollableV) {
					verticalDragHeight = Math.ceil(1 / percentInViewV * verticalTrackHeight);
					if (verticalDragHeight > settings.verticalDragMaxHeight) {
						verticalDragHeight = settings.verticalDragMaxHeight;
					} else if (verticalDragHeight < settings.verticalDragMinHeight) {
						verticalDragHeight = settings.verticalDragMinHeight;
					}
					verticalDrag.css('height', verticalDragHeight + 'px');
					dragMaxY = verticalTrackHeight - verticalDragHeight;
					_positionDragY(verticalDragPosition); // To update the state for the arrow buttons
				}
			}
			
			function appendArrows(ele, p, a1, a2)
			{
				var p1 = "before", p2 = "after", aTemp;
				
				// Sniff for mac... Is there a better way to determine whether the arrows would naturally appear
				// at the top or the bottom of the bar?
				if (p == "os") {
					p = /Mac/.test(navigator.platform) ? "after" : "split";
				}
				if (p == p1) {
					p2 = p;
				} else if (p == p2) {
					p1 = p;
					aTemp = a1;
					a1 = a2;
					a2 = aTemp;
				}
				
				ele[p1](a1)[p2](a2);
			}
			
			function getArrowScroll(dirX, dirY, ele)
			{
				return function()
				{
					arrowScroll(dirX, dirY, this, ele);
					this.blur();
					return false;
				};
			}
			
			function arrowScroll(dirX, dirY, arrow, ele)
			{
				arrow = $(arrow).addClass('jspActive');
				
				var eve,
				    scrollTimeout,
				    isFirst = true,
				    doScroll = function()
				    {
					    if (dirX !== 0) {
						    jsp.scrollByX(dirX * settings.arrowButtonSpeed);
					    }
					    if (dirY !== 0) {
						    jsp.scrollByY(dirY * settings.arrowButtonSpeed);
					    }
					    scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.arrowRepeatFreq);
					    isFirst = false;
				    };
				
				doScroll();
				
				eve = ele ? 'mouseout.jsp' : 'mouseup.jsp';
				ele = ele || $('html');
				ele.on(
					eve,
					function()
					{
						arrow.removeClass('jspActive');
						if(scrollTimeout) {
							clearTimeout(scrollTimeout);
						}
						scrollTimeout = null;
						ele.off(eve);
					}
				);
			}
			
			function initClickOnTrack()
			{
				removeClickOnTrack();
				if (isScrollableV) {
					verticalTrack.on(
						'mousedown.jsp',
						function(e)
						{
							if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
								var clickedTrack = $(this),
								    offset = clickedTrack.offset(),
								    direction = e.pageY - offset.top - verticalDragPosition,
								    scrollTimeout,
								    isFirst = true,
								    doScroll = function()
								    {
									    var offset = clickedTrack.offset(),
									        pos = e.pageY - offset.top - verticalDragHeight / 2,
									        contentDragY = paneHeight * settings.scrollPagePercent,
									        dragY = dragMaxY * contentDragY / (contentHeight - paneHeight);
									    if (direction < 0) {
										    if (verticalDragPosition - dragY > pos) {
											    jsp.scrollByY(-contentDragY);
										    } else {
											    positionDragY(pos);
										    }
									    } else if (direction > 0) {
										    if (verticalDragPosition + dragY < pos) {
											    jsp.scrollByY(contentDragY);
										    } else {
											    positionDragY(pos);
										    }
									    } else {
										    cancelClick();
										    return;
									    }
									    scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
									    isFirst = false;
								    },
								    cancelClick = function()
								    {
									    if(scrollTimeout) {
										    clearTimeout(scrollTimeout);
									    }
									    scrollTimeout = null;
									    $(document).off('mouseup.jsp', cancelClick);
								    };
								doScroll();
								$(document).on('mouseup.jsp', cancelClick);
								return false;
							}
						}
					);
				}
				
				if (isScrollableH) {
					horizontalTrack.on(
						'mousedown.jsp',
						function(e)
						{
							if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
								var clickedTrack = $(this),
								    offset = clickedTrack.offset(),
								    direction = e.pageX - offset.left - horizontalDragPosition,
								    scrollTimeout,
								    isFirst = true,
								    doScroll = function()
								    {
									    var offset = clickedTrack.offset(),
									        pos = e.pageX - offset.left - horizontalDragWidth / 2,
									        contentDragX = paneWidth * settings.scrollPagePercent,
									        dragX = dragMaxX * contentDragX / (contentWidth - paneWidth);
									    if (direction < 0) {
										    if (horizontalDragPosition - dragX > pos) {
											    jsp.scrollByX(-contentDragX);
										    } else {
											    positionDragX(pos);
										    }
									    } else if (direction > 0) {
										    if (horizontalDragPosition + dragX < pos) {
											    jsp.scrollByX(contentDragX);
										    } else {
											    positionDragX(pos);
										    }
									    } else {
										    cancelClick();
										    return;
									    }
									    scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
									    isFirst = false;
								    },
								    cancelClick = function()
								    {
									    if(scrollTimeout) {
										    clearTimeout(scrollTimeout);
									    }
									    scrollTimeout = null;
									    $(document).off('mouseup.jsp', cancelClick);
								    };
								doScroll();
								$(document).on('mouseup.jsp', cancelClick);
								return false;
							}
						}
					);
				}
			}
			
			function removeClickOnTrack()
			{
				if (horizontalTrack) {
					horizontalTrack.off('mousedown.jsp');
				}
				if (verticalTrack) {
					verticalTrack.off('mousedown.jsp');
				}
			}
			
			function cancelDrag()
			{
				$('html').off('dragstart.jsp selectstart.jsp mousemove.jsp mouseup.jsp mouseleave.jsp');
				
				if (verticalDrag) {
					verticalDrag.removeClass('jspActive');
				}
				if (horizontalDrag) {
					horizontalDrag.removeClass('jspActive');
				}
			}
			
			function positionDragY(destY, animate)
			{
				if (!isScrollableV) {
					return;
				}
				if (destY < 0) {
					destY = 0;
				} else if (destY > dragMaxY) {
					destY = dragMaxY;
				}
				
				// allow for devs to prevent the JSP from being scrolled
				var willScrollYEvent = new $.Event("jsp-will-scroll-y");
				elem.trigger(willScrollYEvent, [destY]);
				
				if (willScrollYEvent.isDefaultPrevented()) {
					return;
				}
				
				var tmpVerticalDragPosition = destY || 0;
				
				var isAtTop = tmpVerticalDragPosition === 0,
				    isAtBottom = tmpVerticalDragPosition == dragMaxY,
				    percentScrolled = destY/ dragMaxY,
				    destTop = -percentScrolled * (contentHeight - paneHeight);
				
				// can't just check if(animate) because false is a valid value that could be passed in...
				if (animate === undefined) {
					animate = settings.animateScroll;
				}
				if (animate) {
					jsp.animate(verticalDrag, 'top', destY,	_positionDragY, function() {
						elem.trigger('jsp-user-scroll-y', [-destTop, isAtTop, isAtBottom]);
					});
				} else {
					verticalDrag.css('top', destY);
					_positionDragY(destY);
					elem.trigger('jsp-user-scroll-y', [-destTop, isAtTop, isAtBottom]);
				}
				
			}
			
			function _positionDragY(destY)
			{
				if (destY === undefined) {
					destY = verticalDrag.position().top;
				}
				
				container.scrollTop(0);
				verticalDragPosition = destY || 0;
				
				var isAtTop = verticalDragPosition === 0,
				    isAtBottom = verticalDragPosition == dragMaxY,
				    percentScrolled = destY/ dragMaxY,
				    destTop = -percentScrolled * (contentHeight - paneHeight);
				
				if (wasAtTop != isAtTop || wasAtBottom != isAtBottom) {
					wasAtTop = isAtTop;
					wasAtBottom = isAtBottom;
					elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
				}
				
				updateVerticalArrows(isAtTop, isAtBottom);
				pane.css('top', destTop);
				elem.trigger('jsp-scroll-y', [-destTop, isAtTop, isAtBottom]).trigger('scroll');
			}
			
			function positionDragX(destX, animate)
			{
				if (!isScrollableH) {
					return;
				}
				if (destX < 0) {
					destX = 0;
				} else if (destX > dragMaxX) {
					destX = dragMaxX;
				}
				
				
				// allow for devs to prevent the JSP from being scrolled
				var willScrollXEvent = new $.Event("jsp-will-scroll-x");
				elem.trigger(willScrollXEvent, [destX]);
				
				if (willScrollXEvent.isDefaultPrevented()) {
					return;
				}
				
				var tmpHorizontalDragPosition = destX ||0;
				
				var isAtLeft = tmpHorizontalDragPosition === 0,
				    isAtRight = tmpHorizontalDragPosition == dragMaxX,
				    percentScrolled = destX / dragMaxX,
				    destLeft = -percentScrolled * (contentWidth - paneWidth);
				
				if (animate === undefined) {
					animate = settings.animateScroll;
				}
				if (animate) {
					jsp.animate(horizontalDrag, 'left', destX,	_positionDragX, function() {
						elem.trigger('jsp-user-scroll-x', [-destLeft, isAtLeft, isAtRight]);
					});
				} else {
					horizontalDrag.css('left', destX);
					_positionDragX(destX);
					elem.trigger('jsp-user-scroll-x', [-destLeft, isAtLeft, isAtRight]);
				}
			}
			
			function _positionDragX(destX)
			{
				if (destX === undefined) {
					destX = horizontalDrag.position().left;
				}
				
				container.scrollTop(0);
				horizontalDragPosition = destX ||0;
				
				var isAtLeft = horizontalDragPosition === 0,
				    isAtRight = horizontalDragPosition == dragMaxX,
				    percentScrolled = destX / dragMaxX,
				    destLeft = -percentScrolled * (contentWidth - paneWidth);
				
				if (wasAtLeft != isAtLeft || wasAtRight != isAtRight) {
					wasAtLeft = isAtLeft;
					wasAtRight = isAtRight;
					elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
				}
				
				updateHorizontalArrows(isAtLeft, isAtRight);
				pane.css('left', destLeft);
				elem.trigger('jsp-scroll-x', [-destLeft, isAtLeft, isAtRight]).trigger('scroll');
			}
			
			function updateVerticalArrows(isAtTop, isAtBottom)
			{
				if (settings.showArrows) {
					arrowUp[isAtTop ? 'addClass' : 'removeClass']('jspDisabled');
					arrowDown[isAtBottom ? 'addClass' : 'removeClass']('jspDisabled');
				}
			}
			
			function updateHorizontalArrows(isAtLeft, isAtRight)
			{
				if (settings.showArrows) {
					arrowLeft[isAtLeft ? 'addClass' : 'removeClass']('jspDisabled');
					arrowRight[isAtRight ? 'addClass' : 'removeClass']('jspDisabled');
				}
			}
			
			function scrollToY(destY, animate)
			{
				var percentScrolled = destY / (contentHeight - paneHeight);
				positionDragY(percentScrolled * dragMaxY, animate);
			}
			
			function scrollToX(destX, animate)
			{
				var percentScrolled = destX / (contentWidth - paneWidth);
				positionDragX(percentScrolled * dragMaxX, animate);
			}
			
			function scrollToElement(ele, stickToTop, animate)
			{
				var e, eleHeight, eleWidth, eleTop = 0, eleLeft = 0, viewportTop, viewportLeft, maxVisibleEleTop, maxVisibleEleLeft, destY, destX;
				
				// Legal hash values aren't necessarily legal jQuery selectors so we need to catch any
				// errors from the lookup...
				try {
					e = $(ele);
				} catch (err) {
					return;
				}
				eleHeight = e.outerHeight();
				eleWidth= e.outerWidth();
				
				container.scrollTop(0);
				container.scrollLeft(0);
				
				// loop through parents adding the offset top of any elements that are relatively positioned between
				// the focused element and the jspPane so we can get the true distance from the top
				// of the focused element to the top of the scrollpane...
				while (!e.is('.jspPane')) {
					eleTop += e.position().top;
					eleLeft += e.position().left;
					e = e.offsetParent();
					if (/^body|html$/i.test(e[0].nodeName)) {
						// we ended up too high in the document structure. Quit!
						return;
					}
				}
				
				viewportTop = contentPositionY();
				maxVisibleEleTop = viewportTop + paneHeight;
				if (eleTop < viewportTop || stickToTop) { // element is above viewport
					destY = eleTop - settings.horizontalGutter;
				} else if (eleTop + eleHeight > maxVisibleEleTop) { // element is below viewport
					destY = eleTop - paneHeight + eleHeight + settings.horizontalGutter;
				}
				if (!isNaN(destY)) {
					scrollToY(destY, animate);
				}
				
				viewportLeft = contentPositionX();
				maxVisibleEleLeft = viewportLeft + paneWidth;
				if (eleLeft < viewportLeft || stickToTop) { // element is to the left of viewport
					destX = eleLeft - settings.horizontalGutter;
				} else if (eleLeft + eleWidth > maxVisibleEleLeft) { // element is to the right viewport
					destX = eleLeft - paneWidth + eleWidth + settings.horizontalGutter;
				}
				if (!isNaN(destX)) {
					scrollToX(destX, animate);
				}
				
			}
			
			function contentPositionX()
			{
				return -pane.position().left;
			}
			
			function contentPositionY()
			{
				return -pane.position().top;
			}
			
			function isCloseToBottom()
			{
				var scrollableHeight = contentHeight - paneHeight;
				return (scrollableHeight > 20) && (scrollableHeight - contentPositionY() < 10);
			}
			
			function isCloseToRight()
			{
				var scrollableWidth = contentWidth - paneWidth;
				return (scrollableWidth > 20) && (scrollableWidth - contentPositionX() < 10);
			}
			
			function initMousewheel()
			{
				container.off(mwEvent).on(
					mwEvent,
					function (event, delta, deltaX, deltaY) {
						
						if (!horizontalDragPosition) horizontalDragPosition = 0;
						if (!verticalDragPosition) verticalDragPosition = 0;
						
						var dX = horizontalDragPosition, dY = verticalDragPosition, factor = event.deltaFactor || settings.mouseWheelSpeed;
						jsp.scrollBy(deltaX * factor, -deltaY * factor, false);
						// return true if there was no movement so rest of screen can scroll
						return dX == horizontalDragPosition && dY == verticalDragPosition;
					}
				);
			}
			
			function removeMousewheel()
			{
				container.off(mwEvent);
			}
			
			function nil()
			{
				return false;
			}
			
			function initFocusHandler()
			{
				pane.find(':input,a').off('focus.jsp').on(
					'focus.jsp',
					function(e)
					{
						scrollToElement(e.target, false);
					}
				);
			}
			
			function removeFocusHandler()
			{
				pane.find(':input,a').off('focus.jsp');
			}
			
			function initKeyboardNav()
			{
				var keyDown, elementHasScrolled, validParents = [];
				if(isScrollableH) {
					validParents.push(horizontalBar[0]);
				}
				
				if(isScrollableV) {
					validParents.push(verticalBar[0]);
				}
				
				// IE also focuses elements that don't have tabindex set.
				pane.on(
					'focus.jsp',
					function()
					{
						elem.focus();
					}
				);
				
				elem.attr('tabindex', 0)
					.off('keydown.jsp keypress.jsp')
					.on(
						'keydown.jsp',
						function(e)
						{
							if (e.target !== this && !(validParents.length && $(e.target).closest(validParents).length)){
								return;
							}
							var dX = horizontalDragPosition, dY = verticalDragPosition;
							switch(e.keyCode) {
								case 40: // down
								case 38: // up
								case 34: // page down
								case 32: // space
								case 33: // page up
								case 39: // right
								case 37: // left
									keyDown = e.keyCode;
									keyDownHandler();
									break;
								case 35: // end
									scrollToY(contentHeight - paneHeight);
									keyDown = null;
									break;
								case 36: // home
									scrollToY(0);
									keyDown = null;
									break;
							}
							
							elementHasScrolled = e.keyCode == keyDown && dX != horizontalDragPosition || dY != verticalDragPosition;
							return !elementHasScrolled;
						}
					).on(
					'keypress.jsp', // For FF/ OSX so that we can cancel the repeat key presses if the JSP scrolls...
					function(e)
					{
						if (e.keyCode == keyDown) {
							keyDownHandler();
						}
						// If the keypress is not related to the area, ignore it. Fixes problem with inputs inside scrolled area. Copied from line 955.
						if (e.target !== this && !(validParents.length && $(e.target).closest(validParents).length)){
							return;
						}
						return !elementHasScrolled;
					}
				);
				
				if (settings.hideFocus) {
					elem.css('outline', 'none');
					if ('hideFocus' in container[0]){
						elem.attr('hideFocus', true);
					}
				} else {
					elem.css('outline', '');
					if ('hideFocus' in container[0]){
						elem.attr('hideFocus', false);
					}
				}
				
				function keyDownHandler()
				{
					var dX = horizontalDragPosition, dY = verticalDragPosition;
					switch(keyDown) {
						case 40: // down
							jsp.scrollByY(settings.keyboardSpeed, false);
							break;
						case 38: // up
							jsp.scrollByY(-settings.keyboardSpeed, false);
							break;
						case 34: // page down
						case 32: // space
							jsp.scrollByY(paneHeight * settings.scrollPagePercent, false);
							break;
						case 33: // page up
							jsp.scrollByY(-paneHeight * settings.scrollPagePercent, false);
							break;
						case 39: // right
							jsp.scrollByX(settings.keyboardSpeed, false);
							break;
						case 37: // left
							jsp.scrollByX(-settings.keyboardSpeed, false);
							break;
					}
					
					elementHasScrolled = dX != horizontalDragPosition || dY != verticalDragPosition;
					return elementHasScrolled;
				}
			}
			
			function removeKeyboardNav()
			{
				elem.attr('tabindex', '-1')
					.removeAttr('tabindex')
					.off('keydown.jsp keypress.jsp');
				
				pane.off('.jsp');
			}
			
			function observeHash()
			{
				if (location.hash && location.hash.length > 1) {
					var e,
					    retryInt,
					    hash = escape(location.hash.substr(1)) // hash must be escaped to prevent XSS
					;
					try {
						e = $('#' + hash + ', a[name="' + hash + '"]');
					} catch (err) {
						return;
					}
					
					if (e.length && pane.find(hash)) {
						// nasty workaround but it appears to take a little while before the hash has done its thing
						// to the rendered page so we just wait until the container's scrollTop has been messed up.
						if (container.scrollTop() === 0) {
							retryInt = setInterval(
								function()
								{
									if (container.scrollTop() > 0) {
										scrollToElement(e, true);
										$(document).scrollTop(container.position().top);
										clearInterval(retryInt);
									}
								},
								50
							);
						} else {
							scrollToElement(e, true);
							$(document).scrollTop(container.position().top);
						}
					}
				}
			}
			
			function hijackInternalLinks()
			{
				// only register the link handler once
				if ($(document.body).data('jspHijack')) {
					return;
				}
				
				// remember that the handler was bound
				$(document.body).data('jspHijack', true);
				
				// use live handler to also capture newly created links
				$(document.body).delegate('a[href*="#"]', 'click', function(event) {
					// does the link point to the same page?
					// this also takes care of cases with a <base>-Tag or Links not starting with the hash #
					// e.g. <a href="index.html#test"> when the current url already is index.html
					var href = this.href.substr(0, this.href.indexOf('#')),
					    locationHref = location.href,
					    hash,
					    element,
					    container,
					    jsp,
					    scrollTop,
					    elementTop;
					if (location.href.indexOf('#') !== -1) {
						locationHref = location.href.substr(0, location.href.indexOf('#'));
					}
					if (href !== locationHref) {
						// the link points to another page
						return;
					}
					
					// check if jScrollPane should handle this click event
					hash = escape(this.href.substr(this.href.indexOf('#') + 1));
					
					// find the element on the page
					try {
						element = $('#' + hash + ', a[name="' + hash + '"]');
					} catch (e) {
						// hash is not a valid jQuery identifier
						return;
					}
					
					if (!element.length) {
						// this link does not point to an element on this page
						return;
					}
					
					container = element.closest('.jspScrollable');
					jsp = container.data('jsp');
					
					// jsp might be another jsp instance than the one, that bound this event
					// remember: this event is only bound once for all instances.
					jsp.scrollToElement(element, true);
					
					if (container[0].scrollIntoView) {
						// also scroll to the top of the container (if it is not visible)
						scrollTop = $(window).scrollTop();
						elementTop = element.offset().top;
						if (elementTop < scrollTop || elementTop > scrollTop + $(window).height()) {
							container[0].scrollIntoView();
						}
					}
					
					// jsp handled this event, prevent the browser default (scrolling :P)
					event.preventDefault();
				});
			}
			
			// Init touch on iPad, iPhone, iPod, Android
			function initTouch()
			{
				var startX,
				    startY,
				    touchStartX,
				    touchStartY,
				    moved,
				    moving = false;
				
				container.off('touchstart.jsp touchmove.jsp touchend.jsp click.jsp-touchclick').on(
					'touchstart.jsp',
					function(e)
					{
						var touch = e.originalEvent.touches[0];
						startX = contentPositionX();
						startY = contentPositionY();
						touchStartX = touch.pageX;
						touchStartY = touch.pageY;
						moved = false;
						moving = true;
					}
				).on(
					'touchmove.jsp',
					function(ev)
					{
						if(!moving) {
							return;
						}
						
						var touchPos = ev.originalEvent.touches[0],
						    dX = horizontalDragPosition, dY = verticalDragPosition;
						
						jsp.scrollTo(startX + touchStartX - touchPos.pageX, startY + touchStartY - touchPos.pageY);
						
						moved = moved || Math.abs(touchStartX - touchPos.pageX) > 5 || Math.abs(touchStartY - touchPos.pageY) > 5;
						
						// return true if there was no movement so rest of screen can scroll
						return dX == horizontalDragPosition && dY == verticalDragPosition;
					}
				).on(
					'touchend.jsp',
					function(e)
					{
						moving = false;
						/*if(moved) {
							return false;
						}*/
					}
				).on(
					'click.jsp-touchclick',
					function(e)
					{
						if(moved) {
							moved = false;
							return false;
						}
					}
				);
			}
			
			function destroy(){
				var currentY = contentPositionY(),
				    currentX = contentPositionX();
				elem.removeClass('jspScrollable').off('.jsp');
				pane.off('.jsp');
				elem.replaceWith(originalElement.append(pane.children()));
				originalElement.scrollTop(currentY);
				originalElement.scrollLeft(currentX);
				
				// clear reinitialize timer if active
				if (reinitialiseInterval) {
					clearInterval(reinitialiseInterval);
				}
			}
			
			// Public API
			$.extend(
				jsp,
				{
					// Reinitialises the scroll pane (if it's internal dimensions have changed since the last time it
					// was initialised). The settings object which is passed in will override any settings from the
					// previous time it was initialised - if you don't pass any settings then the ones from the previous
					// initialisation will be used.
					reinitialise: function(s)
					{
						s = $.extend({}, settings, s);
						initialise(s);
					},
					// Scrolls the specified element (a jQuery object, DOM node or jQuery selector string) into view so
					// that it can be seen within the viewport. If stickToTop is true then the element will appear at
					// the top of the viewport, if it is false then the viewport will scroll as little as possible to
					// show the element. You can also specify if you want animation to occur. If you don't provide this
					// argument then the animateScroll value from the settings object is used instead.
					scrollToElement: function(ele, stickToTop, animate)
					{
						scrollToElement(ele, stickToTop, animate);
					},
					// Scrolls the pane so that the specified co-ordinates within the content are at the top left
					// of the viewport. animate is optional and if not passed then the value of animateScroll from
					// the settings object this jScrollPane was initialised with is used.
					scrollTo: function(destX, destY, animate)
					{
						scrollToX(destX, animate);
						scrollToY(destY, animate);
					},
					// Scrolls the pane so that the specified co-ordinate within the content is at the left of the
					// viewport. animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					scrollToX: function(destX, animate)
					{
						scrollToX(destX, animate);
					},
					// Scrolls the pane so that the specified co-ordinate within the content is at the top of the
					// viewport. animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					scrollToY: function(destY, animate)
					{
						scrollToY(destY, animate);
					},
					// Scrolls the pane to the specified percentage of its maximum horizontal scroll position. animate
					// is optional and if not passed then the value of animateScroll from the settings object this
					// jScrollPane was initialised with is used.
					scrollToPercentX: function(destPercentX, animate)
					{
						scrollToX(destPercentX * (contentWidth - paneWidth), animate);
					},
					// Scrolls the pane to the specified percentage of its maximum vertical scroll position. animate
					// is optional and if not passed then the value of animateScroll from the settings object this
					// jScrollPane was initialised with is used.
					scrollToPercentY: function(destPercentY, animate)
					{
						scrollToY(destPercentY * (contentHeight - paneHeight), animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollBy: function(deltaX, deltaY, animate)
					{
						jsp.scrollByX(deltaX, animate);
						jsp.scrollByY(deltaY, animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollByX: function(deltaX, animate)
					{
						var destX = contentPositionX() + Math[deltaX<0 ? 'floor' : 'ceil'](deltaX),
						    percentScrolled = destX / (contentWidth - paneWidth);
						positionDragX(percentScrolled * dragMaxX, animate);
					},
					// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
					// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
					scrollByY: function(deltaY, animate)
					{
						var destY = contentPositionY() + Math[deltaY<0 ? 'floor' : 'ceil'](deltaY),
						    percentScrolled = destY / (contentHeight - paneHeight);
						positionDragY(percentScrolled * dragMaxY, animate);
					},
					// Positions the horizontal drag at the specified x position (and updates the viewport to reflect
					// this). animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					positionDragX: function(x, animate)
					{
						positionDragX(x, animate);
					},
					// Positions the vertical drag at the specified y position (and updates the viewport to reflect
					// this). animate is optional and if not passed then the value of animateScroll from the settings
					// object this jScrollPane was initialised with is used.
					positionDragY: function(y, animate)
					{
						positionDragY(y, animate);
					},
					// This method is called when jScrollPane is trying to animate to a new position. You can override
					// it if you want to provide advanced animation functionality. It is passed the following arguments:
					//  * ele          - the element whose position is being animated
					//  * prop         - the property that is being animated
					//  * value        - the value it's being animated to
					//  * stepCallback - a function that you must execute each time you update the value of the property
					//  * completeCallback - a function that will be executed after the animation had finished
					// You can use the default implementation (below) as a starting point for your own implementation.
					animate: function(ele, prop, value, stepCallback, completeCallback)
					{
						var params = {};
						params[prop] = value;
						ele.animate(
							params,
							{
								'duration'	: settings.animateDuration,
								'easing'	: settings.animateEase,
								'queue'		: false,
								'step'		: stepCallback,
								'complete'	: completeCallback
							}
						);
					},
					// Returns the current x position of the viewport with regards to the content pane.
					getContentPositionX: function()
					{
						return contentPositionX();
					},
					// Returns the current y position of the viewport with regards to the content pane.
					getContentPositionY: function()
					{
						return contentPositionY();
					},
					// Returns the width of the content within the scroll pane.
					getContentWidth: function()
					{
						return contentWidth;
					},
					// Returns the height of the content within the scroll pane.
					getContentHeight: function()
					{
						return contentHeight;
					},
					// Returns the horizontal position of the viewport within the pane content.
					getPercentScrolledX: function()
					{
						return contentPositionX() / (contentWidth - paneWidth);
					},
					// Returns the vertical position of the viewport within the pane content.
					getPercentScrolledY: function()
					{
						return contentPositionY() / (contentHeight - paneHeight);
					},
					// Returns whether or not this scrollpane has a horizontal scrollbar.
					getIsScrollableH: function()
					{
						return isScrollableH;
					},
					// Returns whether or not this scrollpane has a vertical scrollbar.
					getIsScrollableV: function()
					{
						return isScrollableV;
					},
					// Gets a reference to the content pane. It is important that you use this method if you want to
					// edit the content of your jScrollPane as if you access the element directly then you may have some
					// problems (as your original element has had additional elements for the scrollbars etc added into
					// it).
					getContentPane: function()
					{
						return pane;
					},
					// Scrolls this jScrollPane down as far as it can currently scroll. If animate isn't passed then the
					// animateScroll value from settings is used instead.
					scrollToBottom: function(animate)
					{
						positionDragY(dragMaxY, animate);
					},
					// Hijacks the links on the page which link to content inside the scrollpane. If you have changed
					// the content of your page (e.g. via AJAX) and want to make sure any new anchor links to the
					// contents of your scroll pane will work then call this function.
					hijackInternalLinks: $.noop,
					// Removes the jScrollPane and returns the page to the state it was in before jScrollPane was
					// initialised.
					destroy: function()
					{
						destroy();
					}
				}
			);
			
			initialise(s);
		}
		
		// Pluginifying code...
		settings = $.extend({}, $.fn.jScrollPane.defaults, settings);
		
		// Apply default speed
		$.each(['arrowButtonSpeed', 'trackClickSpeed', 'keyboardSpeed'], function() {
			settings[this] = settings[this] || settings.speed;
		});
		
		return this.each(
			function()
			{
				var elem = $(this), jspApi = elem.data('jsp');
				if (jspApi) {
					jspApi.reinitialise(settings);
				} else {
					$("script",elem).filter('[type="text/javascript"],:not([type])').remove();
					jspApi = new JScrollPane(elem, settings);
					elem.data('jsp', jspApi);
				}
			}
		);
	};
	
	$.fn.jScrollPane.defaults = {
		showArrows					: false,
		maintainPosition			: true,
		stickToBottom				: false,
		stickToRight				: false,
		clickOnTrack				: true,
		autoReinitialise			: false,
		autoReinitialiseDelay		: 500,
		verticalDragMinHeight		: 0,
		verticalDragMaxHeight		: 99999,
		horizontalDragMinWidth		: 0,
		horizontalDragMaxWidth		: 99999,
		contentWidth				: undefined,
		animateScroll				: false,
		animateDuration				: 300,
		animateEase					: 'linear',
		hijackInternalLinks			: false,
		verticalGutter				: 4,
		horizontalGutter			: 4,
		mouseWheelSpeed				: 3,
		arrowButtonSpeed			: 0,
		arrowRepeatFreq				: 50,
		arrowScrollOnHover			: false,
		trackClickSpeed				: 0,
		trackClickRepeatFreq		: 70,
		verticalArrowPositions		: 'split',
		horizontalArrowPositions	: 'split',
		enableKeyboardNavigation	: true,
		hideFocus					: false,
		keyboardSpeed				: 0,
		initialDelay                : 300,        // Delay before starting repeating
		speed						: 30,		// Default speed when others falsey
		scrollPagePercent			: 0.8,		// Percent of visible area scrolled when pageUp/Down or track area pressed
		alwaysShowVScroll			: false,
		alwaysShowHScroll			: false,
		resizeSensor				: false,
		resizeSensorDelay			: 0,
	};
	
}(jQuery));
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/vendor/select2.js":
/*!*************************************!*\
  !*** ./assets/js/vendor/select2.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function($) {var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;/*!
 * Select2 4.0.13
 * https://select2.github.io
 *
 * Released under the MIT license
 * https://github.com/select2/select2/blob/master/LICENSE.md
 */
;(function (factory) {
	if (true) {
		// AMD. Register as an anonymous module.
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [__webpack_require__(/*! jquery */ "jquery")], __WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
} (function (jQuery) {
	// This is needed so we can catch the AMD loader configuration and use it
	// The inner file should be wrapped (by `banner.start.js`) in a function that
	// returns the AMD loader references.
	var S2 =(function () {
		// Restore the Select2 AMD loader so it can be used
		// Needed mostly in the language files, where the loader is not inserted
		if (jQuery && jQuery.fn && jQuery.fn.select2 && jQuery.fn.select2.amd) {
			var S2 = jQuery.fn.select2.amd;
		}
		var S2;(function () { if (!S2 || !S2.requirejs) {
			if (!S2) { S2 = {}; } else { require = S2; }
			/**
			 * @license almond 0.3.3 Copyright jQuery Foundation and other contributors.
			 * Released under MIT license, http://github.com/requirejs/almond/LICENSE
			 */
//Going sloppy to avoid 'use strict' string cost, but strict practices should
//be followed.
			/*global setTimeout: false */
			
			var requirejs, require, define;
			(function (undef) {
				var main, req, makeMap, handlers,
				    defined = {},
				    waiting = {},
				    config = {},
				    defining = {},
				    hasOwn = Object.prototype.hasOwnProperty,
				    aps = [].slice,
				    jsSuffixRegExp = /\.js$/;
				
				function hasProp(obj, prop) {
					return hasOwn.call(obj, prop);
				}
				
				/**
				 * Given a relative module name, like ./something, normalize it to
				 * a real name that can be mapped to a path.
				 * @param {String} name the relative name
				 * @param {String} baseName a real name that the name arg is relative
				 * to.
				 * @returns {String} normalized name
				 */
				function normalize(name, baseName) {
					var nameParts, nameSegment, mapValue, foundMap, lastIndex,
					    foundI, foundStarMap, starI, i, j, part, normalizedBaseParts,
					    baseParts = baseName && baseName.split("/"),
					    map = config.map,
					    starMap = (map && map['*']) || {};
					
					//Adjust any relative paths.
					if (name) {
						name = name.split('/');
						lastIndex = name.length - 1;
						
						// If wanting node ID compatibility, strip .js from end
						// of IDs. Have to do this here, and not in nameToUrl
						// because node allows either .js or non .js to map
						// to same file.
						if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
							name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
						}
						
						// Starts with a '.' so need the baseName
						if (name[0].charAt(0) === '.' && baseParts) {
							//Convert baseName to array, and lop off the last part,
							//so that . matches that 'directory' and not name of the baseName's
							//module. For instance, baseName of 'one/two/three', maps to
							//'one/two/three.js', but we want the directory, 'one/two' for
							//this normalization.
							normalizedBaseParts = baseParts.slice(0, baseParts.length - 1);
							name = normalizedBaseParts.concat(name);
						}
						
						//start trimDots
						for (i = 0; i < name.length; i++) {
							part = name[i];
							if (part === '.') {
								name.splice(i, 1);
								i -= 1;
							} else if (part === '..') {
								// If at the start, or previous value is still ..,
								// keep them so that when converted to a path it may
								// still work when converted to a path, even though
								// as an ID it is less than ideal. In larger point
								// releases, may be better to just kick out an error.
								if (i === 0 || (i === 1 && name[2] === '..') || name[i - 1] === '..') {
									continue;
								} else if (i > 0) {
									name.splice(i - 1, 2);
									i -= 2;
								}
							}
						}
						//end trimDots
						
						name = name.join('/');
					}
					
					//Apply map config if available.
					if ((baseParts || starMap) && map) {
						nameParts = name.split('/');
						
						for (i = nameParts.length; i > 0; i -= 1) {
							nameSegment = nameParts.slice(0, i).join("/");
							
							if (baseParts) {
								//Find the longest baseName segment match in the config.
								//So, do joins on the biggest to smallest lengths of baseParts.
								for (j = baseParts.length; j > 0; j -= 1) {
									mapValue = map[baseParts.slice(0, j).join('/')];
									
									//baseName segment has  config, find if it has one for
									//this name.
									if (mapValue) {
										mapValue = mapValue[nameSegment];
										if (mapValue) {
											//Match, update name to the new value.
											foundMap = mapValue;
											foundI = i;
											break;
										}
									}
								}
							}
							
							if (foundMap) {
								break;
							}
							
							//Check for a star map match, but just hold on to it,
							//if there is a shorter segment match later in a matching
							//config, then favor over this star map.
							if (!foundStarMap && starMap && starMap[nameSegment]) {
								foundStarMap = starMap[nameSegment];
								starI = i;
							}
						}
						
						if (!foundMap && foundStarMap) {
							foundMap = foundStarMap;
							foundI = starI;
						}
						
						if (foundMap) {
							nameParts.splice(0, foundI, foundMap);
							name = nameParts.join('/');
						}
					}
					
					return name;
				}
				
				function makeRequire(relName, forceSync) {
					return function () {
						//A version of a require function that passes a moduleName
						//value for items that may need to
						//look up paths relative to the moduleName
						var args = aps.call(arguments, 0);
						
						//If first arg is not require('string'), and there is only
						//one arg, it is the array form without a callback. Insert
						//a null so that the following concat is correct.
						if (typeof args[0] !== 'string' && args.length === 1) {
							args.push(null);
						}
						return req.apply(undef, args.concat([relName, forceSync]));
					};
				}
				
				function makeNormalize(relName) {
					return function (name) {
						return normalize(name, relName);
					};
				}
				
				function makeLoad(depName) {
					return function (value) {
						defined[depName] = value;
					};
				}
				
				function callDep(name) {
					if (hasProp(waiting, name)) {
						var args = waiting[name];
						delete waiting[name];
						defining[name] = true;
						main.apply(undef, args);
					}
					
					if (!hasProp(defined, name) && !hasProp(defining, name)) {
						throw new Error('No ' + name);
					}
					return defined[name];
				}
				
				//Turns a plugin!resource to [plugin, resource]
				//with the plugin being undefined if the name
				//did not have a plugin prefix.
				function splitPrefix(name) {
					var prefix,
					    index = name ? name.indexOf('!') : -1;
					if (index > -1) {
						prefix = name.substring(0, index);
						name = name.substring(index + 1, name.length);
					}
					return [prefix, name];
				}
				
				//Creates a parts array for a relName where first part is plugin ID,
				//second part is resource ID. Assumes relName has already been normalized.
				function makeRelParts(relName) {
					return relName ? splitPrefix(relName) : [];
				}
				
				/**
				 * Makes a name map, normalizing the name, and using a plugin
				 * for normalization if necessary. Grabs a ref to plugin
				 * too, as an optimization.
				 */
				makeMap = function (name, relParts) {
					var plugin,
					    parts = splitPrefix(name),
					    prefix = parts[0],
					    relResourceName = relParts[1];
					
					name = parts[1];
					
					if (prefix) {
						prefix = normalize(prefix, relResourceName);
						plugin = callDep(prefix);
					}
					
					//Normalize according
					if (prefix) {
						if (plugin && plugin.normalize) {
							name = plugin.normalize(name, makeNormalize(relResourceName));
						} else {
							name = normalize(name, relResourceName);
						}
					} else {
						name = normalize(name, relResourceName);
						parts = splitPrefix(name);
						prefix = parts[0];
						name = parts[1];
						if (prefix) {
							plugin = callDep(prefix);
						}
					}
					
					//Using ridiculous property names for space reasons
					return {
						f: prefix ? prefix + '!' + name : name, //fullName
						n: name,
						pr: prefix,
						p: plugin
					};
				};
				
				function makeConfig(name) {
					return function () {
						return (config && config.config && config.config[name]) || {};
					};
				}
				
				handlers = {
					require: function (name) {
						return makeRequire(name);
					},
					exports: function (name) {
						var e = defined[name];
						if (typeof e !== 'undefined') {
							return e;
						} else {
							return (defined[name] = {});
						}
					},
					module: function (name) {
						return {
							id: name,
							uri: '',
							exports: defined[name],
							config: makeConfig(name)
						};
					}
				};
				
				main = function (name, deps, callback, relName) {
					var cjsModule, depName, ret, map, i, relParts,
					    args = [],
					    callbackType = typeof callback,
					    usingExports;
					
					//Use name if no relName
					relName = relName || name;
					relParts = makeRelParts(relName);
					
					//Call the callback to define the module, if necessary.
					if (callbackType === 'undefined' || callbackType === 'function') {
						//Pull out the defined dependencies and pass the ordered
						//values to the callback.
						//Default to [require, exports, module] if no deps
						deps = !deps.length && callback.length ? ['require', 'exports', 'module'] : deps;
						for (i = 0; i < deps.length; i += 1) {
							map = makeMap(deps[i], relParts);
							depName = map.f;
							
							//Fast path CommonJS standard dependencies.
							if (depName === "require") {
								args[i] = handlers.require(name);
							} else if (depName === "exports") {
								//CommonJS module spec 1.1
								args[i] = handlers.exports(name);
								usingExports = true;
							} else if (depName === "module") {
								//CommonJS module spec 1.1
								cjsModule = args[i] = handlers.module(name);
							} else if (hasProp(defined, depName) ||
								hasProp(waiting, depName) ||
								hasProp(defining, depName)) {
								args[i] = callDep(depName);
							} else if (map.p) {
								map.p.load(map.n, makeRequire(relName, true), makeLoad(depName), {});
								args[i] = defined[depName];
							} else {
								throw new Error(name + ' missing ' + depName);
							}
						}
						
						ret = callback ? callback.apply(defined[name], args) : undefined;
						
						if (name) {
							//If setting exports via "module" is in play,
							//favor that over return value and exports. After that,
							//favor a non-undefined return value over exports use.
							if (cjsModule && cjsModule.exports !== undef &&
								cjsModule.exports !== defined[name]) {
								defined[name] = cjsModule.exports;
							} else if (ret !== undef || !usingExports) {
								//Use the return value from the function.
								defined[name] = ret;
							}
						}
					} else if (name) {
						//May just be an object definition for the module. Only
						//worry about defining if have a module name.
						defined[name] = callback;
					}
				};
				
				requirejs = require = req = function (deps, callback, relName, forceSync, alt) {
					if (typeof deps === "string") {
						if (handlers[deps]) {
							//callback in this case is really relName
							return handlers[deps](callback);
						}
						//Just return the module wanted. In this scenario, the
						//deps arg is the module name, and second arg (if passed)
						//is just the relName.
						//Normalize module name, if it contains . or ..
						return callDep(makeMap(deps, makeRelParts(callback)).f);
					} else if (!deps.splice) {
						//deps is a config object, not an array.
						config = deps;
						if (config.deps) {
							req(config.deps, config.callback);
						}
						if (!callback) {
							return;
						}
						
						if (callback.splice) {
							//callback is an array, which means it is a dependency list.
							//Adjust args if there are dependencies
							deps = callback;
							callback = relName;
							relName = null;
						} else {
							deps = undef;
						}
					}
					
					//Support require(['a'])
					callback = callback || function () {};
					
					//If relName is a function, it is an errback handler,
					//so remove it.
					if (typeof relName === 'function') {
						relName = forceSync;
						forceSync = alt;
					}
					
					//Simulate async callback;
					if (forceSync) {
						main(undef, deps, callback, relName);
					} else {
						//Using a non-zero value because of concern for what old browsers
						//do, and latest browsers "upgrade" to 4 if lower value is used:
						//http://www.whatwg.org/specs/web-apps/current-work/multipage/timers.html#dom-windowtimers-settimeout:
						//If want a value immediately, use require('id') instead -- something
						//that works in almond on the global level, but not guaranteed and
						//unlikely to work in other AMD implementations.
						setTimeout(function () {
							main(undef, deps, callback, relName);
						}, 4);
					}
					
					return req;
				};
				
				/**
				 * Just drops the config on the floor, but returns req in case
				 * the config return value is used.
				 */
				req.config = function (cfg) {
					return req(cfg);
				};
				
				/**
				 * Expose module registry for debugging and tooling
				 */
				requirejs._defined = defined;
				
				define = function (name, deps, callback) {
					if (typeof name !== 'string') {
						throw new Error('See almond README: incorrect module build, no module name');
					}
					
					//This module may not have dependencies
					if (!deps.splice) {
						//deps is not an array, so probably means
						//an object literal or factory function for
						//the value. Adjust args.
						callback = deps;
						deps = [];
					}
					
					if (!hasProp(defined, name) && !hasProp(waiting, name)) {
						waiting[name] = [name, deps, callback];
					}
				};
				
				define.amd = {
					jQuery: true
				};
			}());
			
			S2.requirejs = requirejs;S2.require = require;S2.define = define;
		}
		}());
		S2.define("almond", function(){});
		
		/* global jQuery:false, $:false */
		S2.define('jquery',[],function () {
			var _$ = jQuery || $;
			
			if (_$ == null && console && console.error) {
				console.error(
					'Select2: An instance of jQuery or a jQuery-compatible library was not ' +
					'found. Make sure that you are including jQuery before Select2 on your ' +
					'web page.'
				);
			}
			
			return _$;
		});
		
		S2.define('select2/utils',[
			'jquery'
		], function ($) {
			var Utils = {};
			
			Utils.Extend = function (ChildClass, SuperClass) {
				var __hasProp = {}.hasOwnProperty;
				
				function BaseConstructor () {
					this.constructor = ChildClass;
				}
				
				for (var key in SuperClass) {
					if (__hasProp.call(SuperClass, key)) {
						ChildClass[key] = SuperClass[key];
					}
				}
				
				BaseConstructor.prototype = SuperClass.prototype;
				ChildClass.prototype = new BaseConstructor();
				ChildClass.__super__ = SuperClass.prototype;
				
				return ChildClass;
			};
			
			function getMethods (theClass) {
				var proto = theClass.prototype;
				
				var methods = [];
				
				for (var methodName in proto) {
					var m = proto[methodName];
					
					if (typeof m !== 'function') {
						continue;
					}
					
					if (methodName === 'constructor') {
						continue;
					}
					
					methods.push(methodName);
				}
				
				return methods;
			}
			
			Utils.Decorate = function (SuperClass, DecoratorClass) {
				var decoratedMethods = getMethods(DecoratorClass);
				var superMethods = getMethods(SuperClass);
				
				function DecoratedClass () {
					var unshift = Array.prototype.unshift;
					
					var argCount = DecoratorClass.prototype.constructor.length;
					
					var calledConstructor = SuperClass.prototype.constructor;
					
					if (argCount > 0) {
						unshift.call(arguments, SuperClass.prototype.constructor);
						
						calledConstructor = DecoratorClass.prototype.constructor;
					}
					
					calledConstructor.apply(this, arguments);
				}
				
				DecoratorClass.displayName = SuperClass.displayName;
				
				function ctr () {
					this.constructor = DecoratedClass;
				}
				
				DecoratedClass.prototype = new ctr();
				
				for (var m = 0; m < superMethods.length; m++) {
					var superMethod = superMethods[m];
					
					DecoratedClass.prototype[superMethod] =
						SuperClass.prototype[superMethod];
				}
				
				var calledMethod = function (methodName) {
					// Stub out the original method if it's not decorating an actual method
					var originalMethod = function () {};
					
					if (methodName in DecoratedClass.prototype) {
						originalMethod = DecoratedClass.prototype[methodName];
					}
					
					var decoratedMethod = DecoratorClass.prototype[methodName];
					
					return function () {
						var unshift = Array.prototype.unshift;
						
						unshift.call(arguments, originalMethod);
						
						return decoratedMethod.apply(this, arguments);
					};
				};
				
				for (var d = 0; d < decoratedMethods.length; d++) {
					var decoratedMethod = decoratedMethods[d];
					
					DecoratedClass.prototype[decoratedMethod] = calledMethod(decoratedMethod);
				}
				
				return DecoratedClass;
			};
			
			var Observable = function () {
				this.listeners = {};
			};
			
			Observable.prototype.on = function (event, callback) {
				this.listeners = this.listeners || {};
				
				if (event in this.listeners) {
					this.listeners[event].push(callback);
				} else {
					this.listeners[event] = [callback];
				}
			};
			
			Observable.prototype.trigger = function (event) {
				var slice = Array.prototype.slice;
				var params = slice.call(arguments, 1);
				
				this.listeners = this.listeners || {};
				
				// Params should always come in as an array
				if (params == null) {
					params = [];
				}
				
				// If there are no arguments to the event, use a temporary object
				if (params.length === 0) {
					params.push({});
				}
				
				// Set the `_type` of the first object to the event
				params[0]._type = event;
				
				if (event in this.listeners) {
					this.invoke(this.listeners[event], slice.call(arguments, 1));
				}
				
				if ('*' in this.listeners) {
					this.invoke(this.listeners['*'], arguments);
				}
			};
			
			Observable.prototype.invoke = function (listeners, params) {
				for (var i = 0, len = listeners.length; i < len; i++) {
					listeners[i].apply(this, params);
				}
			};
			
			Utils.Observable = Observable;
			
			Utils.generateChars = function (length) {
				var chars = '';
				
				for (var i = 0; i < length; i++) {
					var randomChar = Math.floor(Math.random() * 36);
					chars += randomChar.toString(36);
				}
				
				return chars;
			};
			
			Utils.bind = function (func, context) {
				return function () {
					func.apply(context, arguments);
				};
			};
			
			Utils._convertData = function (data) {
				for (var originalKey in data) {
					var keys = originalKey.split('-');
					
					var dataLevel = data;
					
					if (keys.length === 1) {
						continue;
					}
					
					for (var k = 0; k < keys.length; k++) {
						var key = keys[k];
						
						// Lowercase the first letter
						// By default, dash-separated becomes camelCase
						key = key.substring(0, 1).toLowerCase() + key.substring(1);
						
						if (!(key in dataLevel)) {
							dataLevel[key] = {};
						}
						
						if (k == keys.length - 1) {
							dataLevel[key] = data[originalKey];
						}
						
						dataLevel = dataLevel[key];
					}
					
					delete data[originalKey];
				}
				
				return data;
			};
			
			Utils.hasScroll = function (index, el) {
				// Adapted from the function created by @ShadowScripter
				// and adapted by @BillBarry on the Stack Exchange Code Review website.
				// The original code can be found at
				// http://codereview.stackexchange.com/q/13338
				// and was designed to be used with the Sizzle selector engine.
				
				var $el = $(el);
				var overflowX = el.style.overflowX;
				var overflowY = el.style.overflowY;
				
				//Check both x and y declarations
				if (overflowX === overflowY &&
					(overflowY === 'hidden' || overflowY === 'visible')) {
					return false;
				}
				
				if (overflowX === 'scroll' || overflowY === 'scroll') {
					return true;
				}
				
				return ($el.innerHeight() < el.scrollHeight ||
					$el.innerWidth() < el.scrollWidth);
			};
			
			Utils.escapeMarkup = function (markup) {
				var replaceMap = {
					'\\': '&#92;',
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					'\'': '&#39;',
					'/': '&#47;'
				};
				
				// Do not try to escape the markup if it's not a string
				if (typeof markup !== 'string') {
					return markup;
				}
				
				return String(markup).replace(/[&<>"'\/\\]/g, function (match) {
					return replaceMap[match];
				});
			};
			
			// Append an array of jQuery nodes to a given element.
			Utils.appendMany = function ($element, $nodes) {
				// jQuery 1.7.x does not support $.fn.append() with an array
				// Fall back to a jQuery object collection using $.fn.add()
				if ($.fn.jquery.substr(0, 3) === '1.7') {
					var $jqNodes = $();
					
					$.map($nodes, function (node) {
						$jqNodes = $jqNodes.add(node);
					});
					
					$nodes = $jqNodes;
				}
				
				$element.append($nodes);
			};
			
			// Cache objects in Utils.__cache instead of $.data (see #4346)
			Utils.__cache = {};
			
			var id = 0;
			Utils.GetUniqueElementId = function (element) {
				// Get a unique element Id. If element has no id,
				// creates a new unique number, stores it in the id
				// attribute and returns the new id.
				// If an id already exists, it simply returns it.
				
				var select2Id = element.getAttribute('data-select2-id');
				if (select2Id == null) {
					// If element has id, use it.
					if (element.id) {
						select2Id = element.id;
						element.setAttribute('data-select2-id', select2Id);
					} else {
						element.setAttribute('data-select2-id', ++id);
						select2Id = id.toString();
					}
				}
				return select2Id;
			};
			
			Utils.StoreData = function (element, name, value) {
				// Stores an item in the cache for a specified element.
				// name is the cache key.
				var id = Utils.GetUniqueElementId(element);
				if (!Utils.__cache[id]) {
					Utils.__cache[id] = {};
				}
				
				Utils.__cache[id][name] = value;
			};
			
			Utils.GetData = function (element, name) {
				// Retrieves a value from the cache by its key (name)
				// name is optional. If no name specified, return
				// all cache items for the specified element.
				// and for a specified element.
				var id = Utils.GetUniqueElementId(element);
				if (name) {
					if (Utils.__cache[id]) {
						if (Utils.__cache[id][name] != null) {
							return Utils.__cache[id][name];
						}
						return $(element).data(name); // Fallback to HTML5 data attribs.
					}
					return $(element).data(name); // Fallback to HTML5 data attribs.
				} else {
					return Utils.__cache[id];
				}
			};
			
			Utils.RemoveData = function (element) {
				// Removes all cached items for a specified element.
				var id = Utils.GetUniqueElementId(element);
				if (Utils.__cache[id] != null) {
					delete Utils.__cache[id];
				}
				
				element.removeAttribute('data-select2-id');
			};
			
			return Utils;
		});
		
		S2.define('select2/results',[
			'jquery',
			'./utils'
		], function ($, Utils) {
			function Results ($element, options, dataAdapter) {
				this.$element = $element;
				this.data = dataAdapter;
				this.options = options;
				
				Results.__super__.constructor.call(this);
			}
			
			Utils.Extend(Results, Utils.Observable);
			
			Results.prototype.render = function () {
				var $results = $(
					'<ul class="select2-results__options" role="listbox"></ul>'
				);
				
				if (this.options.get('multiple')) {
					$results.attr('aria-multiselectable', 'true');
				}
				
				this.$results = $results;
				
				return $results;
			};
			
			Results.prototype.clear = function () {
				this.$results.empty();
			};
			
			Results.prototype.displayMessage = function (params) {
				var escapeMarkup = this.options.get('escapeMarkup');
				
				this.clear();
				this.hideLoading();
				
				var $message = $(
					'<li role="alert" aria-live="assertive"' +
					' class="select2-results__option"></li>'
				);
				
				var message = this.options.get('translations').get(params.message);
				
				$message.append(
					escapeMarkup(
						message(params.args)
					)
				);
				
				$message[0].className += ' select2-results__message';
				
				this.$results.append($message);
			};
			
			Results.prototype.hideMessages = function () {
				this.$results.find('.select2-results__message').remove();
			};
			
			Results.prototype.append = function (data) {
				this.hideLoading();
				
				var $options = [];
				
				if (data.results == null || data.results.length === 0) {
					if (this.$results.children().length === 0) {
						this.trigger('results:message', {
							message: 'noResults'
						});
					}
					
					return;
				}
				
				data.results = this.sort(data.results);
				
				for (var d = 0; d < data.results.length; d++) {
					var item = data.results[d];
					
					var $option = this.option(item);
					
					$options.push($option);
				}
				
				this.$results.append($options);
			};
			
			Results.prototype.position = function ($results, $dropdown) {
				var $resultsContainer = $dropdown.find('.select2-results');
				$resultsContainer.append($results);
			};
			
			Results.prototype.sort = function (data) {
				var sorter = this.options.get('sorter');
				
				return sorter(data);
			};
			
			Results.prototype.highlightFirstItem = function () {
				var $options = this.$results
					.find('.select2-results__option[aria-selected]');
				
				var $selected = $options.filter('[aria-selected=true]');
				
				// Check if there are any selected options
				if ($selected.length > 0) {
					// If there are selected options, highlight the first
					$selected.first().trigger('mouseenter');
				} else {
					// If there are no selected options, highlight the first option
					// in the dropdown
					$options.first().trigger('mouseenter');
				}
				
				this.ensureHighlightVisible();
			};
			
			Results.prototype.setClasses = function () {
				var self = this;
				
				this.data.current(function (selected) {
					var selectedIds = $.map(selected, function (s) {
						return s.id.toString();
					});
					
					var $options = self.$results
						.find('.select2-results__option[aria-selected]');
					
					$options.each(function () {
						var $option = $(this);
						
						var item = Utils.GetData(this, 'data');
						
						// id needs to be converted to a string when comparing
						var id = '' + item.id;
						
						if ((item.element != null && item.element.selected) ||
							(item.element == null && $.inArray(id, selectedIds) > -1)) {
							$option.attr('aria-selected', 'true');
						} else {
							$option.attr('aria-selected', 'false');
						}
					});
					
				});
			};
			
			Results.prototype.showLoading = function (params) {
				this.hideLoading();
				
				var loadingMore = this.options.get('translations').get('searching');
				
				var loading = {
					disabled: true,
					loading: true,
					text: loadingMore(params)
				};
				var $loading = this.option(loading);
				$loading.className += ' loading-results';
				
				this.$results.prepend($loading);
			};
			
			Results.prototype.hideLoading = function () {
				this.$results.find('.loading-results').remove();
			};
			
			Results.prototype.option = function (data) {
				var option = document.createElement('li');
				option.className = 'select2-results__option';
				
				var attrs = {
					'role': 'option',
					'aria-selected': 'false'
				};
				
				var matches = window.Element.prototype.matches ||
					window.Element.prototype.msMatchesSelector ||
					window.Element.prototype.webkitMatchesSelector;
				
				if ((data.element != null && matches.call(data.element, ':disabled')) ||
					(data.element == null && data.disabled)) {
					delete attrs['aria-selected'];
					attrs['aria-disabled'] = 'true';
				}
				
				if (data.id == null) {
					delete attrs['aria-selected'];
				}
				
				if (data._resultId != null) {
					option.id = data._resultId;
				}
				
				if (data.title) {
					option.title = data.title;
				}
				
				if (data.children) {
					attrs.role = 'group';
					attrs['aria-label'] = data.text;
					delete attrs['aria-selected'];
				}
				
				for (var attr in attrs) {
					var val = attrs[attr];
					
					option.setAttribute(attr, val);
				}
				
				if (data.children) {
					var $option = $(option);
					
					var label = document.createElement('strong');
					label.className = 'select2-results__group';
					
					var $label = $(label);
					this.template(data, label);
					
					var $children = [];
					
					for (var c = 0; c < data.children.length; c++) {
						var child = data.children[c];
						
						var $child = this.option(child);
						
						$children.push($child);
					}
					
					var $childrenContainer = $('<ul></ul>', {
						'class': 'select2-results__options select2-results__options--nested'
					});
					
					$childrenContainer.append($children);
					
					$option.append(label);
					$option.append($childrenContainer);
				} else {
					this.template(data, option);
				}
				
				Utils.StoreData(option, 'data', data);
				
				return option;
			};
			
			Results.prototype.bind = function (container, $container) {
				var self = this;
				
				var id = container.id + '-results';
				
				this.$results.attr('id', id);
				
				container.on('results:all', function (params) {
					self.clear();
					self.append(params.data);
					
					if (container.isOpen()) {
						self.setClasses();
						self.highlightFirstItem();
					}
				});
				
				container.on('results:append', function (params) {
					self.append(params.data);
					
					if (container.isOpen()) {
						self.setClasses();
					}
				});
				
				container.on('query', function (params) {
					self.hideMessages();
					self.showLoading(params);
				});
				
				container.on('select', function () {
					if (!container.isOpen()) {
						return;
					}
					
					self.setClasses();
					
					if (self.options.get('scrollAfterSelect')) {
						self.highlightFirstItem();
					}
				});
				
				container.on('unselect', function () {
					if (!container.isOpen()) {
						return;
					}
					
					self.setClasses();
					
					if (self.options.get('scrollAfterSelect')) {
						self.highlightFirstItem();
					}
				});
				
				container.on('open', function () {
					// When the dropdown is open, aria-expended="true"
					self.$results.attr('aria-expanded', 'true');
					self.$results.attr('aria-hidden', 'false');
					
					self.setClasses();
					self.ensureHighlightVisible();
				});
				
				container.on('close', function () {
					// When the dropdown is closed, aria-expended="false"
					self.$results.attr('aria-expanded', 'false');
					self.$results.attr('aria-hidden', 'true');
					self.$results.removeAttr('aria-activedescendant');
				});
				
				container.on('results:toggle', function () {
					var $highlighted = self.getHighlightedResults();
					
					if ($highlighted.length === 0) {
						return;
					}
					
					$highlighted.trigger('mouseup');
				});
				
				container.on('results:select', function () {
					var $highlighted = self.getHighlightedResults();
					
					if ($highlighted.length === 0) {
						return;
					}
					
					var data = Utils.GetData($highlighted[0], 'data');
					
					if ($highlighted.attr('aria-selected') == 'true') {
						self.trigger('close', {});
					} else {
						self.trigger('select', {
							data: data
						});
					}
				});
				
				container.on('results:previous', function () {
					var $highlighted = self.getHighlightedResults();
					
					var $options = self.$results.find('[aria-selected]');
					
					var currentIndex = $options.index($highlighted);
					
					// If we are already at the top, don't move further
					// If no options, currentIndex will be -1
					if (currentIndex <= 0) {
						return;
					}
					
					var nextIndex = currentIndex - 1;
					
					// If none are highlighted, highlight the first
					if ($highlighted.length === 0) {
						nextIndex = 0;
					}
					
					var $next = $options.eq(nextIndex);
					
					$next.trigger('mouseenter');
					
					var currentOffset = self.$results.offset().top;
					var nextTop = $next.offset().top;
					var nextOffset = self.$results.scrollTop() + (nextTop - currentOffset);
					
					if (nextIndex === 0) {
						self.$results.scrollTop(0);
					} else if (nextTop - currentOffset < 0) {
						self.$results.scrollTop(nextOffset);
					}
				});
				
				container.on('results:next', function () {
					var $highlighted = self.getHighlightedResults();
					
					var $options = self.$results.find('[aria-selected]');
					
					var currentIndex = $options.index($highlighted);
					
					var nextIndex = currentIndex + 1;
					
					// If we are at the last option, stay there
					if (nextIndex >= $options.length) {
						return;
					}
					
					var $next = $options.eq(nextIndex);
					
					$next.trigger('mouseenter');
					
					var currentOffset = self.$results.offset().top +
						self.$results.outerHeight(false);
					var nextBottom = $next.offset().top + $next.outerHeight(false);
					var nextOffset = self.$results.scrollTop() + nextBottom - currentOffset;
					
					if (nextIndex === 0) {
						self.$results.scrollTop(0);
					} else if (nextBottom > currentOffset) {
						self.$results.scrollTop(nextOffset);
					}
				});
				
				container.on('results:focus', function (params) {
					params.element.addClass('select2-results__option--highlighted');
				});
				
				container.on('results:message', function (params) {
					self.displayMessage(params);
				});
				
				if ($.fn.mousewheel) {
					this.$results.on('mousewheel', function (e) {
						var top = self.$results.scrollTop();
						
						var bottom = self.$results.get(0).scrollHeight - top + e.deltaY;
						
						var isAtTop = e.deltaY > 0 && top - e.deltaY <= 0;
						var isAtBottom = e.deltaY < 0 && bottom <= self.$results.height();
						
						if (isAtTop) {
							self.$results.scrollTop(0);
							
							e.preventDefault();
							e.stopPropagation();
						} else if (isAtBottom) {
							self.$results.scrollTop(
								self.$results.get(0).scrollHeight - self.$results.height()
							);
							
							e.preventDefault();
							e.stopPropagation();
						}
					});
				}
				
				this.$results.on('mouseup', '.select2-results__option[aria-selected]',
					function (evt) {
						var $this = $(this);
						
						var data = Utils.GetData(this, 'data');
						
						if ($this.attr('aria-selected') === 'true') {
							if (self.options.get('multiple')) {
								self.trigger('unselect', {
									originalEvent: evt,
									data: data
								});
							} else {
								self.trigger('close', {});
							}
							
							return;
						}
						
						self.trigger('select', {
							originalEvent: evt,
							data: data
						});
					});
				
				this.$results.on('mouseenter', '.select2-results__option[aria-selected]',
					function (evt) {
						var data = Utils.GetData(this, 'data');
						
						self.getHighlightedResults()
							.removeClass('select2-results__option--highlighted');
						
						self.trigger('results:focus', {
							data: data,
							element: $(this)
						});
					});
			};
			
			Results.prototype.getHighlightedResults = function () {
				var $highlighted = this.$results
					.find('.select2-results__option--highlighted');
				
				return $highlighted;
			};
			
			Results.prototype.destroy = function () {
				this.$results.remove();
			};
			
			Results.prototype.ensureHighlightVisible = function () {
				var $highlighted = this.getHighlightedResults();
				
				if ($highlighted.length === 0) {
					return;
				}
				
				var $options = this.$results.find('[aria-selected]');
				
				var currentIndex = $options.index($highlighted);
				
				var currentOffset = this.$results.offset().top;
				var nextTop = $highlighted.offset().top;
				var nextOffset = this.$results.scrollTop() + (nextTop - currentOffset);
				
				var offsetDelta = nextTop - currentOffset;
				nextOffset -= $highlighted.outerHeight(false) * 2;
				
				if (currentIndex <= 2) {
					this.$results.scrollTop(0);
				} else if (offsetDelta > this.$results.outerHeight() || offsetDelta < 0) {
					this.$results.scrollTop(nextOffset);
				}
			};
			
			Results.prototype.template = function (result, container) {
				var template = this.options.get('templateResult');
				var escapeMarkup = this.options.get('escapeMarkup');
				
				var content = template(result, container);
				
				if (content == null) {
					container.style.display = 'none';
				} else if (typeof content === 'string') {
					container.innerHTML = escapeMarkup(content);
				} else {
					$(container).append(content);
				}
			};
			
			return Results;
		});
		
		S2.define('select2/keys',[
		
		], function () {
			var KEYS = {
				BACKSPACE: 8,
				TAB: 9,
				ENTER: 13,
				SHIFT: 16,
				CTRL: 17,
				ALT: 18,
				ESC: 27,
				SPACE: 32,
				PAGE_UP: 33,
				PAGE_DOWN: 34,
				END: 35,
				HOME: 36,
				LEFT: 37,
				UP: 38,
				RIGHT: 39,
				DOWN: 40,
				DELETE: 46
			};
			
			return KEYS;
		});
		
		S2.define('select2/selection/base',[
			'jquery',
			'../utils',
			'../keys'
		], function ($, Utils, KEYS) {
			function BaseSelection ($element, options) {
				this.$element = $element;
				this.options = options;
				
				BaseSelection.__super__.constructor.call(this);
			}
			
			Utils.Extend(BaseSelection, Utils.Observable);
			
			BaseSelection.prototype.render = function () {
				var $selection = $(
					'<span class="select2-selection" role="combobox" ' +
					' aria-haspopup="true" aria-expanded="false">' +
					'</span>'
				);
				
				this._tabindex = 0;
				
				if (Utils.GetData(this.$element[0], 'old-tabindex') != null) {
					this._tabindex = Utils.GetData(this.$element[0], 'old-tabindex');
				} else if (this.$element.attr('tabindex') != null) {
					this._tabindex = this.$element.attr('tabindex');
				}
				
				$selection.attr('title', this.$element.attr('title'));
				$selection.attr('tabindex', this._tabindex);
				$selection.attr('aria-disabled', 'false');
				
				this.$selection = $selection;
				
				return $selection;
			};
			
			BaseSelection.prototype.bind = function (container, $container) {
				var self = this;
				
				var resultsId = container.id + '-results';
				
				this.container = container;
				
				this.$selection.on('focus', function (evt) {
					self.trigger('focus', evt);
				});
				
				this.$selection.on('blur', function (evt) {
					self._handleBlur(evt);
				});
				
				this.$selection.on('keydown', function (evt) {
					self.trigger('keypress', evt);
					
					if (evt.which === KEYS.SPACE) {
						evt.preventDefault();
					}
				});
				
				container.on('results:focus', function (params) {
					self.$selection.attr('aria-activedescendant', params.data._resultId);
				});
				
				container.on('selection:update', function (params) {
					self.update(params.data);
				});
				
				container.on('open', function () {
					// When the dropdown is open, aria-expanded="true"
					self.$selection.attr('aria-expanded', 'true');
					self.$selection.attr('aria-owns', resultsId);
					
					self._attachCloseHandler(container);
				});
				
				container.on('close', function () {
					// When the dropdown is closed, aria-expanded="false"
					self.$selection.attr('aria-expanded', 'false');
					self.$selection.removeAttr('aria-activedescendant');
					self.$selection.removeAttr('aria-owns');
					
					self.$selection.trigger('focus');
					
					self._detachCloseHandler(container);
				});
				
				container.on('enable', function () {
					self.$selection.attr('tabindex', self._tabindex);
					self.$selection.attr('aria-disabled', 'false');
				});
				
				container.on('disable', function () {
					self.$selection.attr('tabindex', '-1');
					self.$selection.attr('aria-disabled', 'true');
				});
			};
			
			BaseSelection.prototype._handleBlur = function (evt) {
				var self = this;
				
				// This needs to be delayed as the active element is the body when the tab
				// key is pressed, possibly along with others.
				window.setTimeout(function () {
					// Don't trigger `blur` if the focus is still in the selection
					if (
						(document.activeElement == self.$selection[0]) ||
						($.contains(self.$selection[0], document.activeElement))
					) {
						return;
					}
					
					self.trigger('blur', evt);
				}, 1);
			};
			
			BaseSelection.prototype._attachCloseHandler = function (container) {
				
				$(document.body).on('mousedown.select2.' + container.id, function (e) {
					var $target = $(e.target);
					
					var $select = $target.closest('.select2');
					
					var $all = $('.select2.select2-container--open');
					
					$all.each(function () {
						if (this == $select[0]) {
							return;
						}
						
						var $element = Utils.GetData(this, 'element');
						
						$element.select2('close');
					});
				});
			};
			
			BaseSelection.prototype._detachCloseHandler = function (container) {
				$(document.body).off('mousedown.select2.' + container.id);
			};
			
			BaseSelection.prototype.position = function ($selection, $container) {
				var $selectionContainer = $container.find('.selection');
				$selectionContainer.append($selection);
			};
			
			BaseSelection.prototype.destroy = function () {
				this._detachCloseHandler(this.container);
			};
			
			BaseSelection.prototype.update = function (data) {
				throw new Error('The `update` method must be defined in child classes.');
			};
			
			/**
			 * Helper method to abstract the "enabled" (not "disabled") state of this
			 * object.
			 *
			 * @return {true} if the instance is not disabled.
			 * @return {false} if the instance is disabled.
			 */
			BaseSelection.prototype.isEnabled = function () {
				return !this.isDisabled();
			};
			
			/**
			 * Helper method to abstract the "disabled" state of this object.
			 *
			 * @return {true} if the disabled option is true.
			 * @return {false} if the disabled option is false.
			 */
			BaseSelection.prototype.isDisabled = function () {
				return this.options.get('disabled');
			};
			
			return BaseSelection;
		});
		
		S2.define('select2/selection/single',[
			'jquery',
			'./base',
			'../utils',
			'../keys'
		], function ($, BaseSelection, Utils, KEYS) {
			function SingleSelection () {
				SingleSelection.__super__.constructor.apply(this, arguments);
			}
			
			Utils.Extend(SingleSelection, BaseSelection);
			
			SingleSelection.prototype.render = function () {
				var $selection = SingleSelection.__super__.render.call(this);
				
				$selection.addClass('select2-selection--single');
				
				$selection.html(
					'<span class="select2-selection__rendered"></span>' +
					'<span class="select2-selection__arrow" role="presentation">' +
					'<b role="presentation"></b>' +
					'</span>'
				);
				
				return $selection;
			};
			
			SingleSelection.prototype.bind = function (container, $container) {
				var self = this;
				
				SingleSelection.__super__.bind.apply(this, arguments);
				
				var id = container.id + '-container';
				
				this.$selection.find('.select2-selection__rendered')
					.attr('id', id)
					.attr('role', 'textbox')
					.attr('aria-readonly', 'true');
				this.$selection.attr('aria-labelledby', id);
				
				this.$selection.on('mousedown', function (evt) {
					// Only respond to left clicks
					if (evt.which !== 1) {
						return;
					}
					
					self.trigger('toggle', {
						originalEvent: evt
					});
				});
				
				this.$selection.on('focus', function (evt) {
					// User focuses on the container
				});
				
				this.$selection.on('blur', function (evt) {
					// User exits the container
				});
				
				container.on('focus', function (evt) {
					if (!container.isOpen()) {
						self.$selection.trigger('focus');
					}
				});
			};
			
			SingleSelection.prototype.clear = function () {
				var $rendered = this.$selection.find('.select2-selection__rendered');
				$rendered.empty();
				$rendered.removeAttr('title'); // clear tooltip on empty
			};
			
			SingleSelection.prototype.display = function (data, container) {
				var template = this.options.get('templateSelection');
				var escapeMarkup = this.options.get('escapeMarkup');
				
				return escapeMarkup(template(data, container));
			};
			
			SingleSelection.prototype.selectionContainer = function () {
				return $('<span></span>');
			};
			
			SingleSelection.prototype.update = function (data) {
				if (data.length === 0) {
					this.clear();
					return;
				}
				
				var selection = data[0];
				
				var $rendered = this.$selection.find('.select2-selection__rendered');
				var formatted = this.display(selection, $rendered);
				
				$rendered.empty().append(formatted);
				
				var title = selection.title || selection.text;
				
				if (title) {
					$rendered.attr('title', title);
				} else {
					$rendered.removeAttr('title');
				}
			};
			
			return SingleSelection;
		});
		
		S2.define('select2/selection/multiple',[
			'jquery',
			'./base',
			'../utils'
		], function ($, BaseSelection, Utils) {
			function MultipleSelection ($element, options) {
				MultipleSelection.__super__.constructor.apply(this, arguments);
			}
			
			Utils.Extend(MultipleSelection, BaseSelection);
			
			MultipleSelection.prototype.render = function () {
				var $selection = MultipleSelection.__super__.render.call(this);
				
				$selection.addClass('select2-selection--multiple');
				
				$selection.html(
					'<ul class="select2-selection__rendered"></ul>'
				);
				
				return $selection;
			};
			
			MultipleSelection.prototype.bind = function (container, $container) {
				var self = this;
				
				MultipleSelection.__super__.bind.apply(this, arguments);
				
				this.$selection.on('click', function (evt) {
					self.trigger('toggle', {
						originalEvent: evt
					});
				});
				
				this.$selection.on(
					'click',
					'.select2-selection__choice__remove',
					function (evt) {
						// Ignore the event if it is disabled
						if (self.isDisabled()) {
							return;
						}
						
						var $remove = $(this);
						var $selection = $remove.parent();
						
						var data = Utils.GetData($selection[0], 'data');
						
						self.trigger('unselect', {
							originalEvent: evt,
							data: data
						});
					}
				);
			};
			
			MultipleSelection.prototype.clear = function () {
				var $rendered = this.$selection.find('.select2-selection__rendered');
				$rendered.empty();
				$rendered.removeAttr('title');
			};
			
			MultipleSelection.prototype.display = function (data, container) {
				var template = this.options.get('templateSelection');
				var escapeMarkup = this.options.get('escapeMarkup');
				
				return escapeMarkup(template(data, container));
			};
			
			MultipleSelection.prototype.selectionContainer = function () {
				var $container = $(
					'<li class="select2-selection__choice">' +
					'<span class="select2-selection__choice__remove" role="presentation">' +
					'&times;' +
					'</span>' +
					'</li>'
				);
				
				return $container;
			};
			
			MultipleSelection.prototype.update = function (data) {
				this.clear();
				
				if (data.length === 0) {
					return;
				}
				
				var $selections = [];
				
				for (var d = 0; d < data.length; d++) {
					var selection = data[d];
					
					var $selection = this.selectionContainer();
					var formatted = this.display(selection, $selection);
					
					$selection.append(formatted);
					
					var title = selection.title || selection.text;
					
					if (title) {
						$selection.attr('title', title);
					}
					
					Utils.StoreData($selection[0], 'data', selection);
					
					$selections.push($selection);
				}
				
				var $rendered = this.$selection.find('.select2-selection__rendered');
				
				Utils.appendMany($rendered, $selections);
			};
			
			return MultipleSelection;
		});
		
		S2.define('select2/selection/placeholder',[
			'../utils'
		], function (Utils) {
			function Placeholder (decorated, $element, options) {
				this.placeholder = this.normalizePlaceholder(options.get('placeholder'));
				
				decorated.call(this, $element, options);
			}
			
			Placeholder.prototype.normalizePlaceholder = function (_, placeholder) {
				if (typeof placeholder === 'string') {
					placeholder = {
						id: '',
						text: placeholder
					};
				}
				
				return placeholder;
			};
			
			Placeholder.prototype.createPlaceholder = function (decorated, placeholder) {
				var $placeholder = this.selectionContainer();
				
				$placeholder.html(this.display(placeholder));
				$placeholder.addClass('select2-selection__placeholder')
					.removeClass('select2-selection__choice');
				
				return $placeholder;
			};
			
			Placeholder.prototype.update = function (decorated, data) {
				var singlePlaceholder = (
					data.length == 1 && data[0].id != this.placeholder.id
				);
				var multipleSelections = data.length > 1;
				
				if (multipleSelections || singlePlaceholder) {
					return decorated.call(this, data);
				}
				
				this.clear();
				
				var $placeholder = this.createPlaceholder(this.placeholder);
				
				this.$selection.find('.select2-selection__rendered').append($placeholder);
			};
			
			return Placeholder;
		});
		
		S2.define('select2/selection/allowClear',[
			'jquery',
			'../keys',
			'../utils'
		], function ($, KEYS, Utils) {
			function AllowClear () { }
			
			AllowClear.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				decorated.call(this, container, $container);
				
				if (this.placeholder == null) {
					if (this.options.get('debug') && window.console && console.error) {
						console.error(
							'Select2: The `allowClear` option should be used in combination ' +
							'with the `placeholder` option.'
						);
					}
				}
				
				this.$selection.on('mousedown', '.select2-selection__clear',
					function (evt) {
						self._handleClear(evt);
					});
				
				container.on('keypress', function (evt) {
					self._handleKeyboardClear(evt, container);
				});
			};
			
			AllowClear.prototype._handleClear = function (_, evt) {
				// Ignore the event if it is disabled
				if (this.isDisabled()) {
					return;
				}
				
				var $clear = this.$selection.find('.select2-selection__clear');
				
				// Ignore the event if nothing has been selected
				if ($clear.length === 0) {
					return;
				}
				
				evt.stopPropagation();
				
				var data = Utils.GetData($clear[0], 'data');
				
				var previousVal = this.$element.val();
				this.$element.val(this.placeholder.id);
				
				var unselectData = {
					data: data
				};
				this.trigger('clear', unselectData);
				if (unselectData.prevented) {
					this.$element.val(previousVal);
					return;
				}
				
				for (var d = 0; d < data.length; d++) {
					unselectData = {
						data: data[d]
					};
					
					// Trigger the `unselect` event, so people can prevent it from being
					// cleared.
					this.trigger('unselect', unselectData);
					
					// If the event was prevented, don't clear it out.
					if (unselectData.prevented) {
						this.$element.val(previousVal);
						return;
					}
				}
				
				this.$element.trigger('input').trigger('change');
				
				this.trigger('toggle', {});
			};
			
			AllowClear.prototype._handleKeyboardClear = function (_, evt, container) {
				if (container.isOpen()) {
					return;
				}
				
				if (evt.which == KEYS.DELETE || evt.which == KEYS.BACKSPACE) {
					this._handleClear(evt);
				}
			};
			
			AllowClear.prototype.update = function (decorated, data) {
				decorated.call(this, data);
				
				if (this.$selection.find('.select2-selection__placeholder').length > 0 ||
					data.length === 0) {
					return;
				}
				
				var removeAll = this.options.get('translations').get('removeAllItems');
				
				var $remove = $(
					'<span class="select2-selection__clear" title="' + removeAll() +'">' +
					'&times;' +
					'</span>'
				);
				Utils.StoreData($remove[0], 'data', data);
				
				this.$selection.find('.select2-selection__rendered').prepend($remove);
			};
			
			return AllowClear;
		});
		
		S2.define('select2/selection/search',[
			'jquery',
			'../utils',
			'../keys'
		], function ($, Utils, KEYS) {
			function Search (decorated, $element, options) {
				decorated.call(this, $element, options);
			}
			
			Search.prototype.render = function (decorated) {
				var $search = $(
					'<li class="select2-search select2-search--inline">' +
					'<input class="select2-search__field" type="search" tabindex="-1"' +
					' autocomplete="off" autocorrect="off" autocapitalize="none"' +
					' spellcheck="false" role="searchbox" aria-autocomplete="list" />' +
					'</li>'
				);
				
				this.$searchContainer = $search;
				this.$search = $search.find('input');
				
				var $rendered = decorated.call(this);
				
				this._transferTabIndex();
				
				return $rendered;
			};
			
			Search.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				var resultsId = container.id + '-results';
				
				decorated.call(this, container, $container);
				
				container.on('open', function () {
					self.$search.attr('aria-controls', resultsId);
					self.$search.trigger('focus');
				});
				
				container.on('close', function () {
					self.$search.val('');
					self.$search.removeAttr('aria-controls');
					self.$search.removeAttr('aria-activedescendant');
					self.$search.trigger('focus');
				});
				
				container.on('enable', function () {
					self.$search.prop('disabled', false);
					
					self._transferTabIndex();
				});
				
				container.on('disable', function () {
					self.$search.prop('disabled', true);
				});
				
				container.on('focus', function (evt) {
					self.$search.trigger('focus');
				});
				
				container.on('results:focus', function (params) {
					if (params.data._resultId) {
						self.$search.attr('aria-activedescendant', params.data._resultId);
					} else {
						self.$search.removeAttr('aria-activedescendant');
					}
				});
				
				this.$selection.on('focusin', '.select2-search--inline', function (evt) {
					self.trigger('focus', evt);
				});
				
				this.$selection.on('focusout', '.select2-search--inline', function (evt) {
					self._handleBlur(evt);
				});
				
				this.$selection.on('keydown', '.select2-search--inline', function (evt) {
					evt.stopPropagation();
					
					self.trigger('keypress', evt);
					
					self._keyUpPrevented = evt.isDefaultPrevented();
					
					var key = evt.which;
					
					if (key === KEYS.BACKSPACE && self.$search.val() === '') {
						var $previousChoice = self.$searchContainer
							.prev('.select2-selection__choice');
						
						if ($previousChoice.length > 0) {
							var item = Utils.GetData($previousChoice[0], 'data');
							
							self.searchRemoveChoice(item);
							
							evt.preventDefault();
						}
					}
				});
				
				this.$selection.on('click', '.select2-search--inline', function (evt) {
					if (self.$search.val()) {
						evt.stopPropagation();
					}
				});
				
				// Try to detect the IE version should the `documentMode` property that
				// is stored on the document. This is only implemented in IE and is
				// slightly cleaner than doing a user agent check.
				// This property is not available in Edge, but Edge also doesn't have
				// this bug.
				var msie = document.documentMode;
				var disableInputEvents = msie && msie <= 11;
				
				// Workaround for browsers which do not support the `input` event
				// This will prevent double-triggering of events for browsers which support
				// both the `keyup` and `input` events.
				this.$selection.on(
					'input.searchcheck',
					'.select2-search--inline',
					function (evt) {
						// IE will trigger the `input` event when a placeholder is used on a
						// search box. To get around this issue, we are forced to ignore all
						// `input` events in IE and keep using `keyup`.
						if (disableInputEvents) {
							self.$selection.off('input.search input.searchcheck');
							return;
						}
						
						// Unbind the duplicated `keyup` event
						self.$selection.off('keyup.search');
					}
				);
				
				this.$selection.on(
					'keyup.search input.search',
					'.select2-search--inline',
					function (evt) {
						// IE will trigger the `input` event when a placeholder is used on a
						// search box. To get around this issue, we are forced to ignore all
						// `input` events in IE and keep using `keyup`.
						if (disableInputEvents && evt.type === 'input') {
							self.$selection.off('input.search input.searchcheck');
							return;
						}
						
						var key = evt.which;
						
						// We can freely ignore events from modifier keys
						if (key == KEYS.SHIFT || key == KEYS.CTRL || key == KEYS.ALT) {
							return;
						}
						
						// Tabbing will be handled during the `keydown` phase
						if (key == KEYS.TAB) {
							return;
						}
						
						self.handleSearch(evt);
					}
				);
			};
			
			/**
			 * This method will transfer the tabindex attribute from the rendered
			 * selection to the search box. This allows for the search box to be used as
			 * the primary focus instead of the selection container.
			 *
			 * @private
			 */
			Search.prototype._transferTabIndex = function (decorated) {
				this.$search.attr('tabindex', this.$selection.attr('tabindex'));
				this.$selection.attr('tabindex', '-1');
			};
			
			Search.prototype.createPlaceholder = function (decorated, placeholder) {
				this.$search.attr('placeholder', placeholder.text);
			};
			
			Search.prototype.update = function (decorated, data) {
				var searchHadFocus = this.$search[0] == document.activeElement;
				
				this.$search.attr('placeholder', '');
				
				decorated.call(this, data);
				
				this.$selection.find('.select2-selection__rendered')
					.append(this.$searchContainer);
				
				this.resizeSearch();
				if (searchHadFocus) {
					this.$search.trigger('focus');
				}
			};
			
			Search.prototype.handleSearch = function () {
				this.resizeSearch();
				
				if (!this._keyUpPrevented) {
					var input = this.$search.val();
					
					this.trigger('query', {
						term: input
					});
				}
				
				this._keyUpPrevented = false;
			};
			
			Search.prototype.searchRemoveChoice = function (decorated, item) {
				this.trigger('unselect', {
					data: item
				});
				
				this.$search.val(item.text);
				this.handleSearch();
			};
			
			Search.prototype.resizeSearch = function () {
				this.$search.css('width', '25px');
				
				var width = '';
				
				if (this.$search.attr('placeholder') !== '') {
					width = this.$selection.find('.select2-selection__rendered').width();
				} else {
					var minimumWidth = this.$search.val().length + 1;
					
					width = (minimumWidth * 0.75) + 'em';
				}
				
				this.$search.css('width', width);
			};
			
			return Search;
		});
		
		S2.define('select2/selection/eventRelay',[
			'jquery'
		], function ($) {
			function EventRelay () { }
			
			EventRelay.prototype.bind = function (decorated, container, $container) {
				var self = this;
				var relayEvents = [
					'open', 'opening',
					'close', 'closing',
					'select', 'selecting',
					'unselect', 'unselecting',
					'clear', 'clearing'
				];
				
				var preventableEvents = [
					'opening', 'closing', 'selecting', 'unselecting', 'clearing'
				];
				
				decorated.call(this, container, $container);
				
				container.on('*', function (name, params) {
					// Ignore events that should not be relayed
					if ($.inArray(name, relayEvents) === -1) {
						return;
					}
					
					// The parameters should always be an object
					params = params || {};
					
					// Generate the jQuery event for the Select2 event
					var evt = $.Event('select2:' + name, {
						params: params
					});
					
					self.$element.trigger(evt);
					
					// Only handle preventable events if it was one
					if ($.inArray(name, preventableEvents) === -1) {
						return;
					}
					
					params.prevented = evt.isDefaultPrevented();
				});
			};
			
			return EventRelay;
		});
		
		S2.define('select2/translation',[
			'jquery',
			'require'
		], function ($, require) {
			function Translation (dict) {
				this.dict = dict || {};
			}
			
			Translation.prototype.all = function () {
				return this.dict;
			};
			
			Translation.prototype.get = function (key) {
				return this.dict[key];
			};
			
			Translation.prototype.extend = function (translation) {
				this.dict = $.extend({}, translation.all(), this.dict);
			};
			
			// Static functions
			
			Translation._cache = {};
			
			Translation.loadPath = function (path) {
				if (!(path in Translation._cache)) {
					var translations = require(path);
					
					Translation._cache[path] = translations;
				}
				
				return new Translation(Translation._cache[path]);
			};
			
			return Translation;
		});
		
		S2.define('select2/diacritics',[
		
		], function () {
			var diacritics = {
				'\u24B6': 'A',
				'\uFF21': 'A',
				'\u00C0': 'A',
				'\u00C1': 'A',
				'\u00C2': 'A',
				'\u1EA6': 'A',
				'\u1EA4': 'A',
				'\u1EAA': 'A',
				'\u1EA8': 'A',
				'\u00C3': 'A',
				'\u0100': 'A',
				'\u0102': 'A',
				'\u1EB0': 'A',
				'\u1EAE': 'A',
				'\u1EB4': 'A',
				'\u1EB2': 'A',
				'\u0226': 'A',
				'\u01E0': 'A',
				'\u00C4': 'A',
				'\u01DE': 'A',
				'\u1EA2': 'A',
				'\u00C5': 'A',
				'\u01FA': 'A',
				'\u01CD': 'A',
				'\u0200': 'A',
				'\u0202': 'A',
				'\u1EA0': 'A',
				'\u1EAC': 'A',
				'\u1EB6': 'A',
				'\u1E00': 'A',
				'\u0104': 'A',
				'\u023A': 'A',
				'\u2C6F': 'A',
				'\uA732': 'AA',
				'\u00C6': 'AE',
				'\u01FC': 'AE',
				'\u01E2': 'AE',
				'\uA734': 'AO',
				'\uA736': 'AU',
				'\uA738': 'AV',
				'\uA73A': 'AV',
				'\uA73C': 'AY',
				'\u24B7': 'B',
				'\uFF22': 'B',
				'\u1E02': 'B',
				'\u1E04': 'B',
				'\u1E06': 'B',
				'\u0243': 'B',
				'\u0182': 'B',
				'\u0181': 'B',
				'\u24B8': 'C',
				'\uFF23': 'C',
				'\u0106': 'C',
				'\u0108': 'C',
				'\u010A': 'C',
				'\u010C': 'C',
				'\u00C7': 'C',
				'\u1E08': 'C',
				'\u0187': 'C',
				'\u023B': 'C',
				'\uA73E': 'C',
				'\u24B9': 'D',
				'\uFF24': 'D',
				'\u1E0A': 'D',
				'\u010E': 'D',
				'\u1E0C': 'D',
				'\u1E10': 'D',
				'\u1E12': 'D',
				'\u1E0E': 'D',
				'\u0110': 'D',
				'\u018B': 'D',
				'\u018A': 'D',
				'\u0189': 'D',
				'\uA779': 'D',
				'\u01F1': 'DZ',
				'\u01C4': 'DZ',
				'\u01F2': 'Dz',
				'\u01C5': 'Dz',
				'\u24BA': 'E',
				'\uFF25': 'E',
				'\u00C8': 'E',
				'\u00C9': 'E',
				'\u00CA': 'E',
				'\u1EC0': 'E',
				'\u1EBE': 'E',
				'\u1EC4': 'E',
				'\u1EC2': 'E',
				'\u1EBC': 'E',
				'\u0112': 'E',
				'\u1E14': 'E',
				'\u1E16': 'E',
				'\u0114': 'E',
				'\u0116': 'E',
				'\u00CB': 'E',
				'\u1EBA': 'E',
				'\u011A': 'E',
				'\u0204': 'E',
				'\u0206': 'E',
				'\u1EB8': 'E',
				'\u1EC6': 'E',
				'\u0228': 'E',
				'\u1E1C': 'E',
				'\u0118': 'E',
				'\u1E18': 'E',
				'\u1E1A': 'E',
				'\u0190': 'E',
				'\u018E': 'E',
				'\u24BB': 'F',
				'\uFF26': 'F',
				'\u1E1E': 'F',
				'\u0191': 'F',
				'\uA77B': 'F',
				'\u24BC': 'G',
				'\uFF27': 'G',
				'\u01F4': 'G',
				'\u011C': 'G',
				'\u1E20': 'G',
				'\u011E': 'G',
				'\u0120': 'G',
				'\u01E6': 'G',
				'\u0122': 'G',
				'\u01E4': 'G',
				'\u0193': 'G',
				'\uA7A0': 'G',
				'\uA77D': 'G',
				'\uA77E': 'G',
				'\u24BD': 'H',
				'\uFF28': 'H',
				'\u0124': 'H',
				'\u1E22': 'H',
				'\u1E26': 'H',
				'\u021E': 'H',
				'\u1E24': 'H',
				'\u1E28': 'H',
				'\u1E2A': 'H',
				'\u0126': 'H',
				'\u2C67': 'H',
				'\u2C75': 'H',
				'\uA78D': 'H',
				'\u24BE': 'I',
				'\uFF29': 'I',
				'\u00CC': 'I',
				'\u00CD': 'I',
				'\u00CE': 'I',
				'\u0128': 'I',
				'\u012A': 'I',
				'\u012C': 'I',
				'\u0130': 'I',
				'\u00CF': 'I',
				'\u1E2E': 'I',
				'\u1EC8': 'I',
				'\u01CF': 'I',
				'\u0208': 'I',
				'\u020A': 'I',
				'\u1ECA': 'I',
				'\u012E': 'I',
				'\u1E2C': 'I',
				'\u0197': 'I',
				'\u24BF': 'J',
				'\uFF2A': 'J',
				'\u0134': 'J',
				'\u0248': 'J',
				'\u24C0': 'K',
				'\uFF2B': 'K',
				'\u1E30': 'K',
				'\u01E8': 'K',
				'\u1E32': 'K',
				'\u0136': 'K',
				'\u1E34': 'K',
				'\u0198': 'K',
				'\u2C69': 'K',
				'\uA740': 'K',
				'\uA742': 'K',
				'\uA744': 'K',
				'\uA7A2': 'K',
				'\u24C1': 'L',
				'\uFF2C': 'L',
				'\u013F': 'L',
				'\u0139': 'L',
				'\u013D': 'L',
				'\u1E36': 'L',
				'\u1E38': 'L',
				'\u013B': 'L',
				'\u1E3C': 'L',
				'\u1E3A': 'L',
				'\u0141': 'L',
				'\u023D': 'L',
				'\u2C62': 'L',
				'\u2C60': 'L',
				'\uA748': 'L',
				'\uA746': 'L',
				'\uA780': 'L',
				'\u01C7': 'LJ',
				'\u01C8': 'Lj',
				'\u24C2': 'M',
				'\uFF2D': 'M',
				'\u1E3E': 'M',
				'\u1E40': 'M',
				'\u1E42': 'M',
				'\u2C6E': 'M',
				'\u019C': 'M',
				'\u24C3': 'N',
				'\uFF2E': 'N',
				'\u01F8': 'N',
				'\u0143': 'N',
				'\u00D1': 'N',
				'\u1E44': 'N',
				'\u0147': 'N',
				'\u1E46': 'N',
				'\u0145': 'N',
				'\u1E4A': 'N',
				'\u1E48': 'N',
				'\u0220': 'N',
				'\u019D': 'N',
				'\uA790': 'N',
				'\uA7A4': 'N',
				'\u01CA': 'NJ',
				'\u01CB': 'Nj',
				'\u24C4': 'O',
				'\uFF2F': 'O',
				'\u00D2': 'O',
				'\u00D3': 'O',
				'\u00D4': 'O',
				'\u1ED2': 'O',
				'\u1ED0': 'O',
				'\u1ED6': 'O',
				'\u1ED4': 'O',
				'\u00D5': 'O',
				'\u1E4C': 'O',
				'\u022C': 'O',
				'\u1E4E': 'O',
				'\u014C': 'O',
				'\u1E50': 'O',
				'\u1E52': 'O',
				'\u014E': 'O',
				'\u022E': 'O',
				'\u0230': 'O',
				'\u00D6': 'O',
				'\u022A': 'O',
				'\u1ECE': 'O',
				'\u0150': 'O',
				'\u01D1': 'O',
				'\u020C': 'O',
				'\u020E': 'O',
				'\u01A0': 'O',
				'\u1EDC': 'O',
				'\u1EDA': 'O',
				'\u1EE0': 'O',
				'\u1EDE': 'O',
				'\u1EE2': 'O',
				'\u1ECC': 'O',
				'\u1ED8': 'O',
				'\u01EA': 'O',
				'\u01EC': 'O',
				'\u00D8': 'O',
				'\u01FE': 'O',
				'\u0186': 'O',
				'\u019F': 'O',
				'\uA74A': 'O',
				'\uA74C': 'O',
				'\u0152': 'OE',
				'\u01A2': 'OI',
				'\uA74E': 'OO',
				'\u0222': 'OU',
				'\u24C5': 'P',
				'\uFF30': 'P',
				'\u1E54': 'P',
				'\u1E56': 'P',
				'\u01A4': 'P',
				'\u2C63': 'P',
				'\uA750': 'P',
				'\uA752': 'P',
				'\uA754': 'P',
				'\u24C6': 'Q',
				'\uFF31': 'Q',
				'\uA756': 'Q',
				'\uA758': 'Q',
				'\u024A': 'Q',
				'\u24C7': 'R',
				'\uFF32': 'R',
				'\u0154': 'R',
				'\u1E58': 'R',
				'\u0158': 'R',
				'\u0210': 'R',
				'\u0212': 'R',
				'\u1E5A': 'R',
				'\u1E5C': 'R',
				'\u0156': 'R',
				'\u1E5E': 'R',
				'\u024C': 'R',
				'\u2C64': 'R',
				'\uA75A': 'R',
				'\uA7A6': 'R',
				'\uA782': 'R',
				'\u24C8': 'S',
				'\uFF33': 'S',
				'\u1E9E': 'S',
				'\u015A': 'S',
				'\u1E64': 'S',
				'\u015C': 'S',
				'\u1E60': 'S',
				'\u0160': 'S',
				'\u1E66': 'S',
				'\u1E62': 'S',
				'\u1E68': 'S',
				'\u0218': 'S',
				'\u015E': 'S',
				'\u2C7E': 'S',
				'\uA7A8': 'S',
				'\uA784': 'S',
				'\u24C9': 'T',
				'\uFF34': 'T',
				'\u1E6A': 'T',
				'\u0164': 'T',
				'\u1E6C': 'T',
				'\u021A': 'T',
				'\u0162': 'T',
				'\u1E70': 'T',
				'\u1E6E': 'T',
				'\u0166': 'T',
				'\u01AC': 'T',
				'\u01AE': 'T',
				'\u023E': 'T',
				'\uA786': 'T',
				'\uA728': 'TZ',
				'\u24CA': 'U',
				'\uFF35': 'U',
				'\u00D9': 'U',
				'\u00DA': 'U',
				'\u00DB': 'U',
				'\u0168': 'U',
				'\u1E78': 'U',
				'\u016A': 'U',
				'\u1E7A': 'U',
				'\u016C': 'U',
				'\u00DC': 'U',
				'\u01DB': 'U',
				'\u01D7': 'U',
				'\u01D5': 'U',
				'\u01D9': 'U',
				'\u1EE6': 'U',
				'\u016E': 'U',
				'\u0170': 'U',
				'\u01D3': 'U',
				'\u0214': 'U',
				'\u0216': 'U',
				'\u01AF': 'U',
				'\u1EEA': 'U',
				'\u1EE8': 'U',
				'\u1EEE': 'U',
				'\u1EEC': 'U',
				'\u1EF0': 'U',
				'\u1EE4': 'U',
				'\u1E72': 'U',
				'\u0172': 'U',
				'\u1E76': 'U',
				'\u1E74': 'U',
				'\u0244': 'U',
				'\u24CB': 'V',
				'\uFF36': 'V',
				'\u1E7C': 'V',
				'\u1E7E': 'V',
				'\u01B2': 'V',
				'\uA75E': 'V',
				'\u0245': 'V',
				'\uA760': 'VY',
				'\u24CC': 'W',
				'\uFF37': 'W',
				'\u1E80': 'W',
				'\u1E82': 'W',
				'\u0174': 'W',
				'\u1E86': 'W',
				'\u1E84': 'W',
				'\u1E88': 'W',
				'\u2C72': 'W',
				'\u24CD': 'X',
				'\uFF38': 'X',
				'\u1E8A': 'X',
				'\u1E8C': 'X',
				'\u24CE': 'Y',
				'\uFF39': 'Y',
				'\u1EF2': 'Y',
				'\u00DD': 'Y',
				'\u0176': 'Y',
				'\u1EF8': 'Y',
				'\u0232': 'Y',
				'\u1E8E': 'Y',
				'\u0178': 'Y',
				'\u1EF6': 'Y',
				'\u1EF4': 'Y',
				'\u01B3': 'Y',
				'\u024E': 'Y',
				'\u1EFE': 'Y',
				'\u24CF': 'Z',
				'\uFF3A': 'Z',
				'\u0179': 'Z',
				'\u1E90': 'Z',
				'\u017B': 'Z',
				'\u017D': 'Z',
				'\u1E92': 'Z',
				'\u1E94': 'Z',
				'\u01B5': 'Z',
				'\u0224': 'Z',
				'\u2C7F': 'Z',
				'\u2C6B': 'Z',
				'\uA762': 'Z',
				'\u24D0': 'a',
				'\uFF41': 'a',
				'\u1E9A': 'a',
				'\u00E0': 'a',
				'\u00E1': 'a',
				'\u00E2': 'a',
				'\u1EA7': 'a',
				'\u1EA5': 'a',
				'\u1EAB': 'a',
				'\u1EA9': 'a',
				'\u00E3': 'a',
				'\u0101': 'a',
				'\u0103': 'a',
				'\u1EB1': 'a',
				'\u1EAF': 'a',
				'\u1EB5': 'a',
				'\u1EB3': 'a',
				'\u0227': 'a',
				'\u01E1': 'a',
				'\u00E4': 'a',
				'\u01DF': 'a',
				'\u1EA3': 'a',
				'\u00E5': 'a',
				'\u01FB': 'a',
				'\u01CE': 'a',
				'\u0201': 'a',
				'\u0203': 'a',
				'\u1EA1': 'a',
				'\u1EAD': 'a',
				'\u1EB7': 'a',
				'\u1E01': 'a',
				'\u0105': 'a',
				'\u2C65': 'a',
				'\u0250': 'a',
				'\uA733': 'aa',
				'\u00E6': 'ae',
				'\u01FD': 'ae',
				'\u01E3': 'ae',
				'\uA735': 'ao',
				'\uA737': 'au',
				'\uA739': 'av',
				'\uA73B': 'av',
				'\uA73D': 'ay',
				'\u24D1': 'b',
				'\uFF42': 'b',
				'\u1E03': 'b',
				'\u1E05': 'b',
				'\u1E07': 'b',
				'\u0180': 'b',
				'\u0183': 'b',
				'\u0253': 'b',
				'\u24D2': 'c',
				'\uFF43': 'c',
				'\u0107': 'c',
				'\u0109': 'c',
				'\u010B': 'c',
				'\u010D': 'c',
				'\u00E7': 'c',
				'\u1E09': 'c',
				'\u0188': 'c',
				'\u023C': 'c',
				'\uA73F': 'c',
				'\u2184': 'c',
				'\u24D3': 'd',
				'\uFF44': 'd',
				'\u1E0B': 'd',
				'\u010F': 'd',
				'\u1E0D': 'd',
				'\u1E11': 'd',
				'\u1E13': 'd',
				'\u1E0F': 'd',
				'\u0111': 'd',
				'\u018C': 'd',
				'\u0256': 'd',
				'\u0257': 'd',
				'\uA77A': 'd',
				'\u01F3': 'dz',
				'\u01C6': 'dz',
				'\u24D4': 'e',
				'\uFF45': 'e',
				'\u00E8': 'e',
				'\u00E9': 'e',
				'\u00EA': 'e',
				'\u1EC1': 'e',
				'\u1EBF': 'e',
				'\u1EC5': 'e',
				'\u1EC3': 'e',
				'\u1EBD': 'e',
				'\u0113': 'e',
				'\u1E15': 'e',
				'\u1E17': 'e',
				'\u0115': 'e',
				'\u0117': 'e',
				'\u00EB': 'e',
				'\u1EBB': 'e',
				'\u011B': 'e',
				'\u0205': 'e',
				'\u0207': 'e',
				'\u1EB9': 'e',
				'\u1EC7': 'e',
				'\u0229': 'e',
				'\u1E1D': 'e',
				'\u0119': 'e',
				'\u1E19': 'e',
				'\u1E1B': 'e',
				'\u0247': 'e',
				'\u025B': 'e',
				'\u01DD': 'e',
				'\u24D5': 'f',
				'\uFF46': 'f',
				'\u1E1F': 'f',
				'\u0192': 'f',
				'\uA77C': 'f',
				'\u24D6': 'g',
				'\uFF47': 'g',
				'\u01F5': 'g',
				'\u011D': 'g',
				'\u1E21': 'g',
				'\u011F': 'g',
				'\u0121': 'g',
				'\u01E7': 'g',
				'\u0123': 'g',
				'\u01E5': 'g',
				'\u0260': 'g',
				'\uA7A1': 'g',
				'\u1D79': 'g',
				'\uA77F': 'g',
				'\u24D7': 'h',
				'\uFF48': 'h',
				'\u0125': 'h',
				'\u1E23': 'h',
				'\u1E27': 'h',
				'\u021F': 'h',
				'\u1E25': 'h',
				'\u1E29': 'h',
				'\u1E2B': 'h',
				'\u1E96': 'h',
				'\u0127': 'h',
				'\u2C68': 'h',
				'\u2C76': 'h',
				'\u0265': 'h',
				'\u0195': 'hv',
				'\u24D8': 'i',
				'\uFF49': 'i',
				'\u00EC': 'i',
				'\u00ED': 'i',
				'\u00EE': 'i',
				'\u0129': 'i',
				'\u012B': 'i',
				'\u012D': 'i',
				'\u00EF': 'i',
				'\u1E2F': 'i',
				'\u1EC9': 'i',
				'\u01D0': 'i',
				'\u0209': 'i',
				'\u020B': 'i',
				'\u1ECB': 'i',
				'\u012F': 'i',
				'\u1E2D': 'i',
				'\u0268': 'i',
				'\u0131': 'i',
				'\u24D9': 'j',
				'\uFF4A': 'j',
				'\u0135': 'j',
				'\u01F0': 'j',
				'\u0249': 'j',
				'\u24DA': 'k',
				'\uFF4B': 'k',
				'\u1E31': 'k',
				'\u01E9': 'k',
				'\u1E33': 'k',
				'\u0137': 'k',
				'\u1E35': 'k',
				'\u0199': 'k',
				'\u2C6A': 'k',
				'\uA741': 'k',
				'\uA743': 'k',
				'\uA745': 'k',
				'\uA7A3': 'k',
				'\u24DB': 'l',
				'\uFF4C': 'l',
				'\u0140': 'l',
				'\u013A': 'l',
				'\u013E': 'l',
				'\u1E37': 'l',
				'\u1E39': 'l',
				'\u013C': 'l',
				'\u1E3D': 'l',
				'\u1E3B': 'l',
				'\u017F': 'l',
				'\u0142': 'l',
				'\u019A': 'l',
				'\u026B': 'l',
				'\u2C61': 'l',
				'\uA749': 'l',
				'\uA781': 'l',
				'\uA747': 'l',
				'\u01C9': 'lj',
				'\u24DC': 'm',
				'\uFF4D': 'm',
				'\u1E3F': 'm',
				'\u1E41': 'm',
				'\u1E43': 'm',
				'\u0271': 'm',
				'\u026F': 'm',
				'\u24DD': 'n',
				'\uFF4E': 'n',
				'\u01F9': 'n',
				'\u0144': 'n',
				'\u00F1': 'n',
				'\u1E45': 'n',
				'\u0148': 'n',
				'\u1E47': 'n',
				'\u0146': 'n',
				'\u1E4B': 'n',
				'\u1E49': 'n',
				'\u019E': 'n',
				'\u0272': 'n',
				'\u0149': 'n',
				'\uA791': 'n',
				'\uA7A5': 'n',
				'\u01CC': 'nj',
				'\u24DE': 'o',
				'\uFF4F': 'o',
				'\u00F2': 'o',
				'\u00F3': 'o',
				'\u00F4': 'o',
				'\u1ED3': 'o',
				'\u1ED1': 'o',
				'\u1ED7': 'o',
				'\u1ED5': 'o',
				'\u00F5': 'o',
				'\u1E4D': 'o',
				'\u022D': 'o',
				'\u1E4F': 'o',
				'\u014D': 'o',
				'\u1E51': 'o',
				'\u1E53': 'o',
				'\u014F': 'o',
				'\u022F': 'o',
				'\u0231': 'o',
				'\u00F6': 'o',
				'\u022B': 'o',
				'\u1ECF': 'o',
				'\u0151': 'o',
				'\u01D2': 'o',
				'\u020D': 'o',
				'\u020F': 'o',
				'\u01A1': 'o',
				'\u1EDD': 'o',
				'\u1EDB': 'o',
				'\u1EE1': 'o',
				'\u1EDF': 'o',
				'\u1EE3': 'o',
				'\u1ECD': 'o',
				'\u1ED9': 'o',
				'\u01EB': 'o',
				'\u01ED': 'o',
				'\u00F8': 'o',
				'\u01FF': 'o',
				'\u0254': 'o',
				'\uA74B': 'o',
				'\uA74D': 'o',
				'\u0275': 'o',
				'\u0153': 'oe',
				'\u01A3': 'oi',
				'\u0223': 'ou',
				'\uA74F': 'oo',
				'\u24DF': 'p',
				'\uFF50': 'p',
				'\u1E55': 'p',
				'\u1E57': 'p',
				'\u01A5': 'p',
				'\u1D7D': 'p',
				'\uA751': 'p',
				'\uA753': 'p',
				'\uA755': 'p',
				'\u24E0': 'q',
				'\uFF51': 'q',
				'\u024B': 'q',
				'\uA757': 'q',
				'\uA759': 'q',
				'\u24E1': 'r',
				'\uFF52': 'r',
				'\u0155': 'r',
				'\u1E59': 'r',
				'\u0159': 'r',
				'\u0211': 'r',
				'\u0213': 'r',
				'\u1E5B': 'r',
				'\u1E5D': 'r',
				'\u0157': 'r',
				'\u1E5F': 'r',
				'\u024D': 'r',
				'\u027D': 'r',
				'\uA75B': 'r',
				'\uA7A7': 'r',
				'\uA783': 'r',
				'\u24E2': 's',
				'\uFF53': 's',
				'\u00DF': 's',
				'\u015B': 's',
				'\u1E65': 's',
				'\u015D': 's',
				'\u1E61': 's',
				'\u0161': 's',
				'\u1E67': 's',
				'\u1E63': 's',
				'\u1E69': 's',
				'\u0219': 's',
				'\u015F': 's',
				'\u023F': 's',
				'\uA7A9': 's',
				'\uA785': 's',
				'\u1E9B': 's',
				'\u24E3': 't',
				'\uFF54': 't',
				'\u1E6B': 't',
				'\u1E97': 't',
				'\u0165': 't',
				'\u1E6D': 't',
				'\u021B': 't',
				'\u0163': 't',
				'\u1E71': 't',
				'\u1E6F': 't',
				'\u0167': 't',
				'\u01AD': 't',
				'\u0288': 't',
				'\u2C66': 't',
				'\uA787': 't',
				'\uA729': 'tz',
				'\u24E4': 'u',
				'\uFF55': 'u',
				'\u00F9': 'u',
				'\u00FA': 'u',
				'\u00FB': 'u',
				'\u0169': 'u',
				'\u1E79': 'u',
				'\u016B': 'u',
				'\u1E7B': 'u',
				'\u016D': 'u',
				'\u00FC': 'u',
				'\u01DC': 'u',
				'\u01D8': 'u',
				'\u01D6': 'u',
				'\u01DA': 'u',
				'\u1EE7': 'u',
				'\u016F': 'u',
				'\u0171': 'u',
				'\u01D4': 'u',
				'\u0215': 'u',
				'\u0217': 'u',
				'\u01B0': 'u',
				'\u1EEB': 'u',
				'\u1EE9': 'u',
				'\u1EEF': 'u',
				'\u1EED': 'u',
				'\u1EF1': 'u',
				'\u1EE5': 'u',
				'\u1E73': 'u',
				'\u0173': 'u',
				'\u1E77': 'u',
				'\u1E75': 'u',
				'\u0289': 'u',
				'\u24E5': 'v',
				'\uFF56': 'v',
				'\u1E7D': 'v',
				'\u1E7F': 'v',
				'\u028B': 'v',
				'\uA75F': 'v',
				'\u028C': 'v',
				'\uA761': 'vy',
				'\u24E6': 'w',
				'\uFF57': 'w',
				'\u1E81': 'w',
				'\u1E83': 'w',
				'\u0175': 'w',
				'\u1E87': 'w',
				'\u1E85': 'w',
				'\u1E98': 'w',
				'\u1E89': 'w',
				'\u2C73': 'w',
				'\u24E7': 'x',
				'\uFF58': 'x',
				'\u1E8B': 'x',
				'\u1E8D': 'x',
				'\u24E8': 'y',
				'\uFF59': 'y',
				'\u1EF3': 'y',
				'\u00FD': 'y',
				'\u0177': 'y',
				'\u1EF9': 'y',
				'\u0233': 'y',
				'\u1E8F': 'y',
				'\u00FF': 'y',
				'\u1EF7': 'y',
				'\u1E99': 'y',
				'\u1EF5': 'y',
				'\u01B4': 'y',
				'\u024F': 'y',
				'\u1EFF': 'y',
				'\u24E9': 'z',
				'\uFF5A': 'z',
				'\u017A': 'z',
				'\u1E91': 'z',
				'\u017C': 'z',
				'\u017E': 'z',
				'\u1E93': 'z',
				'\u1E95': 'z',
				'\u01B6': 'z',
				'\u0225': 'z',
				'\u0240': 'z',
				'\u2C6C': 'z',
				'\uA763': 'z',
				'\u0386': '\u0391',
				'\u0388': '\u0395',
				'\u0389': '\u0397',
				'\u038A': '\u0399',
				'\u03AA': '\u0399',
				'\u038C': '\u039F',
				'\u038E': '\u03A5',
				'\u03AB': '\u03A5',
				'\u038F': '\u03A9',
				'\u03AC': '\u03B1',
				'\u03AD': '\u03B5',
				'\u03AE': '\u03B7',
				'\u03AF': '\u03B9',
				'\u03CA': '\u03B9',
				'\u0390': '\u03B9',
				'\u03CC': '\u03BF',
				'\u03CD': '\u03C5',
				'\u03CB': '\u03C5',
				'\u03B0': '\u03C5',
				'\u03CE': '\u03C9',
				'\u03C2': '\u03C3',
				'\u2019': '\''
			};
			
			return diacritics;
		});
		
		S2.define('select2/data/base',[
			'../utils'
		], function (Utils) {
			function BaseAdapter ($element, options) {
				BaseAdapter.__super__.constructor.call(this);
			}
			
			Utils.Extend(BaseAdapter, Utils.Observable);
			
			BaseAdapter.prototype.current = function (callback) {
				throw new Error('The `current` method must be defined in child classes.');
			};
			
			BaseAdapter.prototype.query = function (params, callback) {
				throw new Error('The `query` method must be defined in child classes.');
			};
			
			BaseAdapter.prototype.bind = function (container, $container) {
				// Can be implemented in subclasses
			};
			
			BaseAdapter.prototype.destroy = function () {
				// Can be implemented in subclasses
			};
			
			BaseAdapter.prototype.generateResultId = function (container, data) {
				var id = container.id + '-result-';
				
				id += Utils.generateChars(4);
				
				if (data.id != null) {
					id += '-' + data.id.toString();
				} else {
					id += '-' + Utils.generateChars(4);
				}
				return id;
			};
			
			return BaseAdapter;
		});
		
		S2.define('select2/data/select',[
			'./base',
			'../utils',
			'jquery'
		], function (BaseAdapter, Utils, $) {
			function SelectAdapter ($element, options) {
				this.$element = $element;
				this.options = options;
				
				SelectAdapter.__super__.constructor.call(this);
			}
			
			Utils.Extend(SelectAdapter, BaseAdapter);
			
			SelectAdapter.prototype.current = function (callback) {
				var data = [];
				var self = this;
				
				this.$element.find(':selected').each(function () {
					var $option = $(this);
					
					var option = self.item($option);
					
					data.push(option);
				});
				
				callback(data);
			};
			
			SelectAdapter.prototype.select = function (data) {
				var self = this;
				
				data.selected = true;
				
				// If data.element is a DOM node, use it instead
				if ($(data.element).is('option')) {
					data.element.selected = true;
					
					this.$element.trigger('input').trigger('change');
					
					return;
				}
				
				if (this.$element.prop('multiple')) {
					this.current(function (currentData) {
						var val = [];
						
						data = [data];
						data.push.apply(data, currentData);
						
						for (var d = 0; d < data.length; d++) {
							var id = data[d].id;
							
							if ($.inArray(id, val) === -1) {
								val.push(id);
							}
						}
						
						self.$element.val(val);
						self.$element.trigger('input').trigger('change');
					});
				} else {
					var val = data.id;
					
					this.$element.val(val);
					this.$element.trigger('input').trigger('change');
				}
			};
			
			SelectAdapter.prototype.unselect = function (data) {
				var self = this;
				
				if (!this.$element.prop('multiple')) {
					return;
				}
				
				data.selected = false;
				
				if ($(data.element).is('option')) {
					data.element.selected = false;
					
					this.$element.trigger('input').trigger('change');
					
					return;
				}
				
				this.current(function (currentData) {
					var val = [];
					
					for (var d = 0; d < currentData.length; d++) {
						var id = currentData[d].id;
						
						if (id !== data.id && $.inArray(id, val) === -1) {
							val.push(id);
						}
					}
					
					self.$element.val(val);
					
					self.$element.trigger('input').trigger('change');
				});
			};
			
			SelectAdapter.prototype.bind = function (container, $container) {
				var self = this;
				
				this.container = container;
				
				container.on('select', function (params) {
					self.select(params.data);
				});
				
				container.on('unselect', function (params) {
					self.unselect(params.data);
				});
			};
			
			SelectAdapter.prototype.destroy = function () {
				// Remove anything added to child elements
				this.$element.find('*').each(function () {
					// Remove any custom data set by Select2
					Utils.RemoveData(this);
				});
			};
			
			SelectAdapter.prototype.query = function (params, callback) {
				var data = [];
				var self = this;
				
				var $options = this.$element.children();
				
				$options.each(function () {
					var $option = $(this);
					
					if (!$option.is('option') && !$option.is('optgroup')) {
						return;
					}
					
					var option = self.item($option);
					
					var matches = self.matches(params, option);
					
					if (matches !== null) {
						data.push(matches);
					}
				});
				
				callback({
					results: data
				});
			};
			
			SelectAdapter.prototype.addOptions = function ($options) {
				Utils.appendMany(this.$element, $options);
			};
			
			SelectAdapter.prototype.option = function (data) {
				var option;
				
				if (data.children) {
					option = document.createElement('optgroup');
					option.label = data.text;
				} else {
					option = document.createElement('option');
					
					if (option.textContent !== undefined) {
						option.textContent = data.text;
					} else {
						option.innerText = data.text;
					}
				}
				
				if (data.id !== undefined) {
					option.value = data.id;
				}
				
				if (data.disabled) {
					option.disabled = true;
				}
				
				if (data.selected) {
					option.selected = true;
				}
				
				if (data.title) {
					option.title = data.title;
				}
				
				var $option = $(option);
				
				var normalizedData = this._normalizeItem(data);
				normalizedData.element = option;
				
				// Override the option's data with the combined data
				Utils.StoreData(option, 'data', normalizedData);
				
				return $option;
			};
			
			SelectAdapter.prototype.item = function ($option) {
				var data = {};
				
				data = Utils.GetData($option[0], 'data');
				
				if (data != null) {
					return data;
				}
				
				if ($option.is('option')) {
					data = {
						id: $option.val(),
						text: $option.text(),
						disabled: $option.prop('disabled'),
						selected: $option.prop('selected'),
						title: $option.prop('title')
					};
				} else if ($option.is('optgroup')) {
					data = {
						text: $option.prop('label'),
						children: [],
						title: $option.prop('title')
					};
					
					var $children = $option.children('option');
					var children = [];
					
					for (var c = 0; c < $children.length; c++) {
						var $child = $($children[c]);
						
						var child = this.item($child);
						
						children.push(child);
					}
					
					data.children = children;
				}
				
				data = this._normalizeItem(data);
				data.element = $option[0];
				
				Utils.StoreData($option[0], 'data', data);
				
				return data;
			};
			
			SelectAdapter.prototype._normalizeItem = function (item) {
				if (item !== Object(item)) {
					item = {
						id: item,
						text: item
					};
				}
				
				item = $.extend({}, {
					text: ''
				}, item);
				
				var defaults = {
					selected: false,
					disabled: false
				};
				
				if (item.id != null) {
					item.id = item.id.toString();
				}
				
				if (item.text != null) {
					item.text = item.text.toString();
				}
				
				if (item._resultId == null && item.id && this.container != null) {
					item._resultId = this.generateResultId(this.container, item);
				}
				
				return $.extend({}, defaults, item);
			};
			
			SelectAdapter.prototype.matches = function (params, data) {
				var matcher = this.options.get('matcher');
				
				return matcher(params, data);
			};
			
			return SelectAdapter;
		});
		
		S2.define('select2/data/array',[
			'./select',
			'../utils',
			'jquery'
		], function (SelectAdapter, Utils, $) {
			function ArrayAdapter ($element, options) {
				this._dataToConvert = options.get('data') || [];
				
				ArrayAdapter.__super__.constructor.call(this, $element, options);
			}
			
			Utils.Extend(ArrayAdapter, SelectAdapter);
			
			ArrayAdapter.prototype.bind = function (container, $container) {
				ArrayAdapter.__super__.bind.call(this, container, $container);
				
				this.addOptions(this.convertToOptions(this._dataToConvert));
			};
			
			ArrayAdapter.prototype.select = function (data) {
				var $option = this.$element.find('option').filter(function (i, elm) {
					return elm.value == data.id.toString();
				});
				
				if ($option.length === 0) {
					$option = this.option(data);
					
					this.addOptions($option);
				}
				
				ArrayAdapter.__super__.select.call(this, data);
			};
			
			ArrayAdapter.prototype.convertToOptions = function (data) {
				var self = this;
				
				var $existing = this.$element.find('option');
				var existingIds = $existing.map(function () {
					return self.item($(this)).id;
				}).get();
				
				var $options = [];
				
				// Filter out all items except for the one passed in the argument
				function onlyItem (item) {
					return function () {
						return $(this).val() == item.id;
					};
				}
				
				for (var d = 0; d < data.length; d++) {
					var item = this._normalizeItem(data[d]);
					
					// Skip items which were pre-loaded, only merge the data
					if ($.inArray(item.id, existingIds) >= 0) {
						var $existingOption = $existing.filter(onlyItem(item));
						
						var existingData = this.item($existingOption);
						var newData = $.extend(true, {}, item, existingData);
						
						var $newOption = this.option(newData);
						
						$existingOption.replaceWith($newOption);
						
						continue;
					}
					
					var $option = this.option(item);
					
					if (item.children) {
						var $children = this.convertToOptions(item.children);
						
						Utils.appendMany($option, $children);
					}
					
					$options.push($option);
				}
				
				return $options;
			};
			
			return ArrayAdapter;
		});
		
		S2.define('select2/data/ajax',[
			'./array',
			'../utils',
			'jquery'
		], function (ArrayAdapter, Utils, $) {
			function AjaxAdapter ($element, options) {
				this.ajaxOptions = this._applyDefaults(options.get('ajax'));
				
				if (this.ajaxOptions.processResults != null) {
					this.processResults = this.ajaxOptions.processResults;
				}
				
				AjaxAdapter.__super__.constructor.call(this, $element, options);
			}
			
			Utils.Extend(AjaxAdapter, ArrayAdapter);
			
			AjaxAdapter.prototype._applyDefaults = function (options) {
				var defaults = {
					data: function (params) {
						return $.extend({}, params, {
							q: params.term
						});
					},
					transport: function (params, success, failure) {
						var $request = $.ajax(params);
						
						$request.then(success);
						$request.fail(failure);
						
						return $request;
					}
				};
				
				return $.extend({}, defaults, options, true);
			};
			
			AjaxAdapter.prototype.processResults = function (results) {
				return results;
			};
			
			AjaxAdapter.prototype.query = function (params, callback) {
				var matches = [];
				var self = this;
				
				if (this._request != null) {
					// JSONP requests cannot always be aborted
					if ($.isFunction(this._request.abort)) {
						this._request.abort();
					}
					
					this._request = null;
				}
				
				var options = $.extend({
					type: 'GET'
				}, this.ajaxOptions);
				
				if (typeof options.url === 'function') {
					options.url = options.url.call(this.$element, params);
				}
				
				if (typeof options.data === 'function') {
					options.data = options.data.call(this.$element, params);
				}
				
				function request () {
					var $request = options.transport(options, function (data) {
						var results = self.processResults(data, params);
						
						if (self.options.get('debug') && window.console && console.error) {
							// Check to make sure that the response included a `results` key.
							if (!results || !results.results || !$.isArray(results.results)) {
								console.error(
									'Select2: The AJAX results did not return an array in the ' +
									'`results` key of the response.'
								);
							}
						}
						
						callback(results);
					}, function () {
						// Attempt to detect if a request was aborted
						// Only works if the transport exposes a status property
						if ('status' in $request &&
							($request.status === 0 || $request.status === '0')) {
							return;
						}
						
						self.trigger('results:message', {
							message: 'errorLoading'
						});
					});
					
					self._request = $request;
				}
				
				if (this.ajaxOptions.delay && params.term != null) {
					if (this._queryTimeout) {
						window.clearTimeout(this._queryTimeout);
					}
					
					this._queryTimeout = window.setTimeout(request, this.ajaxOptions.delay);
				} else {
					request();
				}
			};
			
			return AjaxAdapter;
		});
		
		S2.define('select2/data/tags',[
			'jquery'
		], function ($) {
			function Tags (decorated, $element, options) {
				var tags = options.get('tags');
				
				var createTag = options.get('createTag');
				
				if (createTag !== undefined) {
					this.createTag = createTag;
				}
				
				var insertTag = options.get('insertTag');
				
				if (insertTag !== undefined) {
					this.insertTag = insertTag;
				}
				
				decorated.call(this, $element, options);
				
				if ($.isArray(tags)) {
					for (var t = 0; t < tags.length; t++) {
						var tag = tags[t];
						var item = this._normalizeItem(tag);
						
						var $option = this.option(item);
						
						this.$element.append($option);
					}
				}
			}
			
			Tags.prototype.query = function (decorated, params, callback) {
				var self = this;
				
				this._removeOldTags();
				
				if (params.term == null || params.page != null) {
					decorated.call(this, params, callback);
					return;
				}
				
				function wrapper (obj, child) {
					var data = obj.results;
					
					for (var i = 0; i < data.length; i++) {
						var option = data[i];
						
						var checkChildren = (
							option.children != null &&
							!wrapper({
								results: option.children
							}, true)
						);
						
						var optionText = (option.text || '').toUpperCase();
						var paramsTerm = (params.term || '').toUpperCase();
						
						var checkText = optionText === paramsTerm;
						
						if (checkText || checkChildren) {
							if (child) {
								return false;
							}
							
							obj.data = data;
							callback(obj);
							
							return;
						}
					}
					
					if (child) {
						return true;
					}
					
					var tag = self.createTag(params);
					
					if (tag != null) {
						var $option = self.option(tag);
						$option.attr('data-select2-tag', true);
						
						self.addOptions([$option]);
						
						self.insertTag(data, tag);
					}
					
					obj.results = data;
					
					callback(obj);
				}
				
				decorated.call(this, params, wrapper);
			};
			
			Tags.prototype.createTag = function (decorated, params) {
				var term = $.trim(params.term);
				
				if (term === '') {
					return null;
				}
				
				return {
					id: term,
					text: term
				};
			};
			
			Tags.prototype.insertTag = function (_, data, tag) {
				data.unshift(tag);
			};
			
			Tags.prototype._removeOldTags = function (_) {
				var $options = this.$element.find('option[data-select2-tag]');
				
				$options.each(function () {
					if (this.selected) {
						return;
					}
					
					$(this).remove();
				});
			};
			
			return Tags;
		});
		
		S2.define('select2/data/tokenizer',[
			'jquery'
		], function ($) {
			function Tokenizer (decorated, $element, options) {
				var tokenizer = options.get('tokenizer');
				
				if (tokenizer !== undefined) {
					this.tokenizer = tokenizer;
				}
				
				decorated.call(this, $element, options);
			}
			
			Tokenizer.prototype.bind = function (decorated, container, $container) {
				decorated.call(this, container, $container);
				
				this.$search =  container.dropdown.$search || container.selection.$search ||
					$container.find('.select2-search__field');
			};
			
			Tokenizer.prototype.query = function (decorated, params, callback) {
				var self = this;
				
				function createAndSelect (data) {
					// Normalize the data object so we can use it for checks
					var item = self._normalizeItem(data);
					
					// Check if the data object already exists as a tag
					// Select it if it doesn't
					var $existingOptions = self.$element.find('option').filter(function () {
						return $(this).val() === item.id;
					});
					
					// If an existing option wasn't found for it, create the option
					if (!$existingOptions.length) {
						var $option = self.option(item);
						$option.attr('data-select2-tag', true);
						
						self._removeOldTags();
						self.addOptions([$option]);
					}
					
					// Select the item, now that we know there is an option for it
					select(item);
				}
				
				function select (data) {
					self.trigger('select', {
						data: data
					});
				}
				
				params.term = params.term || '';
				
				var tokenData = this.tokenizer(params, this.options, createAndSelect);
				
				if (tokenData.term !== params.term) {
					// Replace the search term if we have the search box
					if (this.$search.length) {
						this.$search.val(tokenData.term);
						this.$search.trigger('focus');
					}
					
					params.term = tokenData.term;
				}
				
				decorated.call(this, params, callback);
			};
			
			Tokenizer.prototype.tokenizer = function (_, params, options, callback) {
				var separators = options.get('tokenSeparators') || [];
				var term = params.term;
				var i = 0;
				
				var createTag = this.createTag || function (params) {
					return {
						id: params.term,
						text: params.term
					};
				};
				
				while (i < term.length) {
					var termChar = term[i];
					
					if ($.inArray(termChar, separators) === -1) {
						i++;
						
						continue;
					}
					
					var part = term.substr(0, i);
					var partParams = $.extend({}, params, {
						term: part
					});
					
					var data = createTag(partParams);
					
					if (data == null) {
						i++;
						continue;
					}
					
					callback(data);
					
					// Reset the term to not include the tokenized portion
					term = term.substr(i + 1) || '';
					i = 0;
				}
				
				return {
					term: term
				};
			};
			
			return Tokenizer;
		});
		
		S2.define('select2/data/minimumInputLength',[
		
		], function () {
			function MinimumInputLength (decorated, $e, options) {
				this.minimumInputLength = options.get('minimumInputLength');
				
				decorated.call(this, $e, options);
			}
			
			MinimumInputLength.prototype.query = function (decorated, params, callback) {
				params.term = params.term || '';
				
				if (params.term.length < this.minimumInputLength) {
					this.trigger('results:message', {
						message: 'inputTooShort',
						args: {
							minimum: this.minimumInputLength,
							input: params.term,
							params: params
						}
					});
					
					return;
				}
				
				decorated.call(this, params, callback);
			};
			
			return MinimumInputLength;
		});
		
		S2.define('select2/data/maximumInputLength',[
		
		], function () {
			function MaximumInputLength (decorated, $e, options) {
				this.maximumInputLength = options.get('maximumInputLength');
				
				decorated.call(this, $e, options);
			}
			
			MaximumInputLength.prototype.query = function (decorated, params, callback) {
				params.term = params.term || '';
				
				if (this.maximumInputLength > 0 &&
					params.term.length > this.maximumInputLength) {
					this.trigger('results:message', {
						message: 'inputTooLong',
						args: {
							maximum: this.maximumInputLength,
							input: params.term,
							params: params
						}
					});
					
					return;
				}
				
				decorated.call(this, params, callback);
			};
			
			return MaximumInputLength;
		});
		
		S2.define('select2/data/maximumSelectionLength',[
		
		], function (){
			function MaximumSelectionLength (decorated, $e, options) {
				this.maximumSelectionLength = options.get('maximumSelectionLength');
				
				decorated.call(this, $e, options);
			}
			
			MaximumSelectionLength.prototype.bind =
				function (decorated, container, $container) {
					var self = this;
					
					decorated.call(this, container, $container);
					
					container.on('select', function () {
						self._checkIfMaximumSelected();
					});
				};
			
			MaximumSelectionLength.prototype.query =
				function (decorated, params, callback) {
					var self = this;
					
					this._checkIfMaximumSelected(function () {
						decorated.call(self, params, callback);
					});
				};
			
			MaximumSelectionLength.prototype._checkIfMaximumSelected =
				function (_, successCallback) {
					var self = this;
					
					this.current(function (currentData) {
						var count = currentData != null ? currentData.length : 0;
						if (self.maximumSelectionLength > 0 &&
							count >= self.maximumSelectionLength) {
							self.trigger('results:message', {
								message: 'maximumSelected',
								args: {
									maximum: self.maximumSelectionLength
								}
							});
							return;
						}
						
						if (successCallback) {
							successCallback();
						}
					});
				};
			
			return MaximumSelectionLength;
		});
		
		S2.define('select2/dropdown',[
			'jquery',
			'./utils'
		], function ($, Utils) {
			function Dropdown ($element, options) {
				this.$element = $element;
				this.options = options;
				
				Dropdown.__super__.constructor.call(this);
			}
			
			Utils.Extend(Dropdown, Utils.Observable);
			
			Dropdown.prototype.render = function () {
				var $dropdown = $(
					'<span class="select2-dropdown">' +
					'<span class="select2-results"></span>' +
					'</span>'
				);
				
				$dropdown.attr('dir', this.options.get('dir'));
				
				this.$dropdown = $dropdown;
				
				return $dropdown;
			};
			
			Dropdown.prototype.bind = function () {
				// Should be implemented in subclasses
			};
			
			Dropdown.prototype.position = function ($dropdown, $container) {
				// Should be implemented in subclasses
			};
			
			Dropdown.prototype.destroy = function () {
				// Remove the dropdown from the DOM
				this.$dropdown.remove();
			};
			
			return Dropdown;
		});
		
		S2.define('select2/dropdown/search',[
			'jquery',
			'../utils'
		], function ($, Utils) {
			function Search () { }
			
			Search.prototype.render = function (decorated) {
				var $rendered = decorated.call(this);
				
				var $search = $(
					'<span class="select2-search select2-search--dropdown">' +
					'<input class="select2-search__field" type="search" tabindex="-1"' +
					' autocomplete="off" autocorrect="off" autocapitalize="none"' +
					' spellcheck="false" role="searchbox" aria-autocomplete="list" />' +
					'</span>'
				);
				
				this.$searchContainer = $search;
				this.$search = $search.find('input');
				
				$rendered.prepend($search);
				
				return $rendered;
			};
			
			Search.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				var resultsId = container.id + '-results';
				
				decorated.call(this, container, $container);
				
				this.$search.on('keydown', function (evt) {
					self.trigger('keypress', evt);
					
					self._keyUpPrevented = evt.isDefaultPrevented();
				});
				
				// Workaround for browsers which do not support the `input` event
				// This will prevent double-triggering of events for browsers which support
				// both the `keyup` and `input` events.
				this.$search.on('input', function (evt) {
					// Unbind the duplicated `keyup` event
					$(this).off('keyup');
				});
				
				this.$search.on('keyup input', function (evt) {
					self.handleSearch(evt);
				});
				
				container.on('open', function () {
					self.$search.attr('tabindex', 0);
					self.$search.attr('aria-controls', resultsId);
					
					self.$search.trigger('focus');
					
					window.setTimeout(function () {
						self.$search.trigger('focus');
					}, 0);
				});
				
				container.on('close', function () {
					self.$search.attr('tabindex', -1);
					self.$search.removeAttr('aria-controls');
					self.$search.removeAttr('aria-activedescendant');
					
					self.$search.val('');
					self.$search.trigger('blur');
				});
				
				container.on('focus', function () {
					if (!container.isOpen()) {
						self.$search.trigger('focus');
					}
				});
				
				container.on('results:all', function (params) {
					if (params.query.term == null || params.query.term === '') {
						var showSearch = self.showSearch(params);
						
						if (showSearch) {
							self.$searchContainer.removeClass('select2-search--hide');
						} else {
							self.$searchContainer.addClass('select2-search--hide');
						}
					}
				});
				
				container.on('results:focus', function (params) {
					if (params.data._resultId) {
						self.$search.attr('aria-activedescendant', params.data._resultId);
					} else {
						self.$search.removeAttr('aria-activedescendant');
					}
				});
			};
			
			Search.prototype.handleSearch = function (evt) {
				if (!this._keyUpPrevented) {
					var input = this.$search.val();
					
					this.trigger('query', {
						term: input
					});
				}
				
				this._keyUpPrevented = false;
			};
			
			Search.prototype.showSearch = function (_, params) {
				return true;
			};
			
			return Search;
		});
		
		S2.define('select2/dropdown/hidePlaceholder',[
		
		], function () {
			function HidePlaceholder (decorated, $element, options, dataAdapter) {
				this.placeholder = this.normalizePlaceholder(options.get('placeholder'));
				
				decorated.call(this, $element, options, dataAdapter);
			}
			
			HidePlaceholder.prototype.append = function (decorated, data) {
				data.results = this.removePlaceholder(data.results);
				
				decorated.call(this, data);
			};
			
			HidePlaceholder.prototype.normalizePlaceholder = function (_, placeholder) {
				if (typeof placeholder === 'string') {
					placeholder = {
						id: '',
						text: placeholder
					};
				}
				
				return placeholder;
			};
			
			HidePlaceholder.prototype.removePlaceholder = function (_, data) {
				var modifiedData = data.slice(0);
				
				for (var d = data.length - 1; d >= 0; d--) {
					var item = data[d];
					
					if (this.placeholder.id === item.id) {
						modifiedData.splice(d, 1);
					}
				}
				
				return modifiedData;
			};
			
			return HidePlaceholder;
		});
		
		S2.define('select2/dropdown/infiniteScroll',[
			'jquery'
		], function ($) {
			function InfiniteScroll (decorated, $element, options, dataAdapter) {
				this.lastParams = {};
				
				decorated.call(this, $element, options, dataAdapter);
				
				this.$loadingMore = this.createLoadingMore();
				this.loading = false;
			}
			
			InfiniteScroll.prototype.append = function (decorated, data) {
				this.$loadingMore.remove();
				this.loading = false;
				
				decorated.call(this, data);
				
				if (this.showLoadingMore(data)) {
					this.$results.append(this.$loadingMore);
					this.loadMoreIfNeeded();
				}
			};
			
			InfiniteScroll.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				decorated.call(this, container, $container);
				
				container.on('query', function (params) {
					self.lastParams = params;
					self.loading = true;
				});
				
				container.on('query:append', function (params) {
					self.lastParams = params;
					self.loading = true;
				});
				
				this.$results.on('scroll', this.loadMoreIfNeeded.bind(this));
			};
			
			InfiniteScroll.prototype.loadMoreIfNeeded = function () {
				var isLoadMoreVisible = $.contains(
					document.documentElement,
					this.$loadingMore[0]
				);
				
				if (this.loading || !isLoadMoreVisible) {
					return;
				}
				
				var currentOffset = this.$results.offset().top +
					this.$results.outerHeight(false);
				var loadingMoreOffset = this.$loadingMore.offset().top +
					this.$loadingMore.outerHeight(false);
				
				if (currentOffset + 50 >= loadingMoreOffset) {
					this.loadMore();
				}
			};
			
			InfiniteScroll.prototype.loadMore = function () {
				this.loading = true;
				
				var params = $.extend({}, {page: 1}, this.lastParams);
				
				params.page++;
				
				this.trigger('query:append', params);
			};
			
			InfiniteScroll.prototype.showLoadingMore = function (_, data) {
				return data.pagination && data.pagination.more;
			};
			
			InfiniteScroll.prototype.createLoadingMore = function () {
				var $option = $(
					'<li ' +
					'class="select2-results__option select2-results__option--load-more"' +
					'role="option" aria-disabled="true"></li>'
				);
				
				var message = this.options.get('translations').get('loadingMore');
				
				$option.html(message(this.lastParams));
				
				return $option;
			};
			
			return InfiniteScroll;
		});
		
		S2.define('select2/dropdown/attachBody',[
			'jquery',
			'../utils'
		], function ($, Utils) {
			function AttachBody (decorated, $element, options) {
				this.$dropdownParent = $(options.get('dropdownParent') || document.body);
				
				decorated.call(this, $element, options);
			}
			
			AttachBody.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				decorated.call(this, container, $container);
				
				container.on('open', function () {
					self._showDropdown();
					self._attachPositioningHandler(container);
					
					// Must bind after the results handlers to ensure correct sizing
					self._bindContainerResultHandlers(container);
				});
				
				container.on('close', function () {
					self._hideDropdown();
					self._detachPositioningHandler(container);
				});
				
				this.$dropdownContainer.on('mousedown', function (evt) {
					evt.stopPropagation();
				});
			};
			
			AttachBody.prototype.destroy = function (decorated) {
				decorated.call(this);
				
				this.$dropdownContainer.remove();
			};
			
			AttachBody.prototype.position = function (decorated, $dropdown, $container) {
				// Clone all of the container classes
				$dropdown.attr('class', $container.attr('class'));
				
				$dropdown.removeClass('select2');
				$dropdown.addClass('select2-container--open');
				
				$dropdown.css({
					position: 'absolute',
					top: -999999
				});
				
				this.$container = $container;
			};
			
			AttachBody.prototype.render = function (decorated) {
				var $container = $('<span></span>');
				
				var $dropdown = decorated.call(this);
				$container.append($dropdown);
				
				this.$dropdownContainer = $container;
				
				return $container;
			};
			
			AttachBody.prototype._hideDropdown = function (decorated) {
				this.$dropdownContainer.detach();
			};
			
			AttachBody.prototype._bindContainerResultHandlers =
				function (decorated, container) {
					
					// These should only be bound once
					if (this._containerResultsHandlersBound) {
						return;
					}
					
					var self = this;
					
					container.on('results:all', function () {
						self._positionDropdown();
						self._resizeDropdown();
					});
					
					container.on('results:append', function () {
						self._positionDropdown();
						self._resizeDropdown();
					});
					
					container.on('results:message', function () {
						self._positionDropdown();
						self._resizeDropdown();
					});
					
					container.on('select', function () {
						self._positionDropdown();
						self._resizeDropdown();
					});
					
					container.on('unselect', function () {
						self._positionDropdown();
						self._resizeDropdown();
					});
					
					this._containerResultsHandlersBound = true;
				};
			
			AttachBody.prototype._attachPositioningHandler =
				function (decorated, container) {
					var self = this;
					
					var scrollEvent = 'scroll.select2.' + container.id;
					var resizeEvent = 'resize.select2.' + container.id;
					var orientationEvent = 'orientationchange.select2.' + container.id;
					
					var $watchers = this.$container.parents().filter(Utils.hasScroll);
					$watchers.each(function () {
						Utils.StoreData(this, 'select2-scroll-position', {
							x: $(this).scrollLeft(),
							y: $(this).scrollTop()
						});
					});
					
					$watchers.on(scrollEvent, function (ev) {
						var position = Utils.GetData(this, 'select2-scroll-position');
						$(this).scrollTop(position.y);
					});
					
					$(window).on(scrollEvent + ' ' + resizeEvent + ' ' + orientationEvent,
						function (e) {
							self._positionDropdown();
							self._resizeDropdown();
						});
				};
			
			AttachBody.prototype._detachPositioningHandler =
				function (decorated, container) {
					var scrollEvent = 'scroll.select2.' + container.id;
					var resizeEvent = 'resize.select2.' + container.id;
					var orientationEvent = 'orientationchange.select2.' + container.id;
					
					var $watchers = this.$container.parents().filter(Utils.hasScroll);
					$watchers.off(scrollEvent);
					
					$(window).off(scrollEvent + ' ' + resizeEvent + ' ' + orientationEvent);
				};
			
			AttachBody.prototype._positionDropdown = function () {
				var $window = $(window);
				
				var isCurrentlyAbove = this.$dropdown.hasClass('select2-dropdown--above');
				var isCurrentlyBelow = this.$dropdown.hasClass('select2-dropdown--below');
				
				var newDirection = null;
				
				var offset = this.$container.offset();
				
				offset.bottom = offset.top + this.$container.outerHeight(false);
				
				var container = {
					height: this.$container.outerHeight(false)
				};
				
				container.top = offset.top;
				container.bottom = offset.top + container.height;
				
				var dropdown = {
					height: this.$dropdown.outerHeight(false)
				};
				
				var viewport = {
					top: $window.scrollTop(),
					bottom: $window.scrollTop() + $window.height()
				};
				
				var enoughRoomAbove = viewport.top < (offset.top - dropdown.height);
				var enoughRoomBelow = viewport.bottom > (offset.bottom + dropdown.height);
				
				var css = {
					left: offset.left,
					top: container.bottom
				};
				
				// Determine what the parent element is to use for calculating the offset
				var $offsetParent = this.$dropdownParent;
				
				// For statically positioned elements, we need to get the element
				// that is determining the offset
				if ($offsetParent.css('position') === 'static') {
					$offsetParent = $offsetParent.offsetParent();
				}
				
				var parentOffset = {
					top: 0,
					left: 0
				};
				
				if (
					$.contains(document.body, $offsetParent[0]) ||
					$offsetParent[0].isConnected
				) {
					parentOffset = $offsetParent.offset();
				}
				
				css.top -= parentOffset.top;
				css.left -= parentOffset.left;
				
				if (!isCurrentlyAbove && !isCurrentlyBelow) {
					newDirection = 'below';
				}
				
				if (!enoughRoomBelow && enoughRoomAbove && !isCurrentlyAbove) {
					newDirection = 'above';
				} else if (!enoughRoomAbove && enoughRoomBelow && isCurrentlyAbove) {
					newDirection = 'below';
				}
				
				if (newDirection == 'above' ||
					(isCurrentlyAbove && newDirection !== 'below')) {
					css.top = container.top - parentOffset.top - dropdown.height;
				}
				
				if (newDirection != null) {
					this.$dropdown
						.removeClass('select2-dropdown--below select2-dropdown--above')
						.addClass('select2-dropdown--' + newDirection);
					this.$container
						.removeClass('select2-container--below select2-container--above')
						.addClass('select2-container--' + newDirection);
				}
				
				this.$dropdownContainer.css(css);
			};
			
			AttachBody.prototype._resizeDropdown = function () {
				var css = {
					width: this.$container.outerWidth(false) + 'px'
				};
				
				if (this.options.get('dropdownAutoWidth')) {
					css.minWidth = css.width;
					css.position = 'relative';
					css.width = 'auto';
				}
				
				this.$dropdown.css(css);
			};
			
			AttachBody.prototype._showDropdown = function (decorated) {
				this.$dropdownContainer.appendTo(this.$dropdownParent);
				
				this._positionDropdown();
				this._resizeDropdown();
			};
			
			return AttachBody;
		});
		
		S2.define('select2/dropdown/minimumResultsForSearch',[
		
		], function () {
			function countResults (data) {
				var count = 0;
				
				for (var d = 0; d < data.length; d++) {
					var item = data[d];
					
					if (item.children) {
						count += countResults(item.children);
					} else {
						count++;
					}
				}
				
				return count;
			}
			
			function MinimumResultsForSearch (decorated, $element, options, dataAdapter) {
				this.minimumResultsForSearch = options.get('minimumResultsForSearch');
				
				if (this.minimumResultsForSearch < 0) {
					this.minimumResultsForSearch = Infinity;
				}
				
				decorated.call(this, $element, options, dataAdapter);
			}
			
			MinimumResultsForSearch.prototype.showSearch = function (decorated, params) {
				if (countResults(params.data.results) < this.minimumResultsForSearch) {
					return false;
				}
				
				return decorated.call(this, params);
			};
			
			return MinimumResultsForSearch;
		});
		
		S2.define('select2/dropdown/selectOnClose',[
			'../utils'
		], function (Utils) {
			function SelectOnClose () { }
			
			SelectOnClose.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				decorated.call(this, container, $container);
				
				container.on('close', function (params) {
					self._handleSelectOnClose(params);
				});
			};
			
			SelectOnClose.prototype._handleSelectOnClose = function (_, params) {
				if (params && params.originalSelect2Event != null) {
					var event = params.originalSelect2Event;
					
					// Don't select an item if the close event was triggered from a select or
					// unselect event
					if (event._type === 'select' || event._type === 'unselect') {
						return;
					}
				}
				
				var $highlightedResults = this.getHighlightedResults();
				
				// Only select highlighted results
				if ($highlightedResults.length < 1) {
					return;
				}
				
				var data = Utils.GetData($highlightedResults[0], 'data');
				
				// Don't re-select already selected resulte
				if (
					(data.element != null && data.element.selected) ||
					(data.element == null && data.selected)
				) {
					return;
				}
				
				this.trigger('select', {
					data: data
				});
			};
			
			return SelectOnClose;
		});
		
		S2.define('select2/dropdown/closeOnSelect',[
		
		], function () {
			function CloseOnSelect () { }
			
			CloseOnSelect.prototype.bind = function (decorated, container, $container) {
				var self = this;
				
				decorated.call(this, container, $container);
				
				container.on('select', function (evt) {
					self._selectTriggered(evt);
				});
				
				container.on('unselect', function (evt) {
					self._selectTriggered(evt);
				});
			};
			
			CloseOnSelect.prototype._selectTriggered = function (_, evt) {
				var originalEvent = evt.originalEvent;
				
				// Don't close if the control key is being held
				if (originalEvent && (originalEvent.ctrlKey || originalEvent.metaKey)) {
					return;
				}
				
				this.trigger('close', {
					originalEvent: originalEvent,
					originalSelect2Event: evt
				});
			};
			
			return CloseOnSelect;
		});
		
		S2.define('select2/i18n/en',[],function () {
			// English
			return {
				errorLoading: function () {
					return 'The results could not be loaded.';
				},
				inputTooLong: function (args) {
					var overChars = args.input.length - args.maximum;
					
					var message = 'Please delete ' + overChars + ' character';
					
					if (overChars != 1) {
						message += 's';
					}
					
					return message;
				},
				inputTooShort: function (args) {
					var remainingChars = args.minimum - args.input.length;
					
					var message = 'Please enter ' + remainingChars + ' or more characters';
					
					return message;
				},
				loadingMore: function () {
					return 'Loading more results…';
				},
				maximumSelected: function (args) {
					var message = 'You can only select ' + args.maximum + ' item';
					
					if (args.maximum != 1) {
						message += 's';
					}
					
					return message;
				},
				noResults: function () {
					return 'No results found';
				},
				searching: function () {
					return 'Searching…';
				},
				removeAllItems: function () {
					return 'Remove all items';
				}
			};
		});
		
		S2.define('select2/defaults',[
			'jquery',
			'require',
			
			'./results',
			
			'./selection/single',
			'./selection/multiple',
			'./selection/placeholder',
			'./selection/allowClear',
			'./selection/search',
			'./selection/eventRelay',
			
			'./utils',
			'./translation',
			'./diacritics',
			
			'./data/select',
			'./data/array',
			'./data/ajax',
			'./data/tags',
			'./data/tokenizer',
			'./data/minimumInputLength',
			'./data/maximumInputLength',
			'./data/maximumSelectionLength',
			
			'./dropdown',
			'./dropdown/search',
			'./dropdown/hidePlaceholder',
			'./dropdown/infiniteScroll',
			'./dropdown/attachBody',
			'./dropdown/minimumResultsForSearch',
			'./dropdown/selectOnClose',
			'./dropdown/closeOnSelect',
			
			'./i18n/en'
		], function ($, require,
		
		             ResultsList,
		
		             SingleSelection, MultipleSelection, Placeholder, AllowClear,
		             SelectionSearch, EventRelay,
		
		             Utils, Translation, DIACRITICS,
		
		             SelectData, ArrayData, AjaxData, Tags, Tokenizer,
		             MinimumInputLength, MaximumInputLength, MaximumSelectionLength,
		
		             Dropdown, DropdownSearch, HidePlaceholder, InfiniteScroll,
		             AttachBody, MinimumResultsForSearch, SelectOnClose, CloseOnSelect,
		
		             EnglishTranslation) {
			function Defaults () {
				this.reset();
			}
			
			Defaults.prototype.apply = function (options) {
				options = $.extend(true, {}, this.defaults, options);
				
				if (options.dataAdapter == null) {
					if (options.ajax != null) {
						options.dataAdapter = AjaxData;
					} else if (options.data != null) {
						options.dataAdapter = ArrayData;
					} else {
						options.dataAdapter = SelectData;
					}
					
					if (options.minimumInputLength > 0) {
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							MinimumInputLength
						);
					}
					
					if (options.maximumInputLength > 0) {
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							MaximumInputLength
						);
					}
					
					if (options.maximumSelectionLength > 0) {
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							MaximumSelectionLength
						);
					}
					
					if (options.tags) {
						options.dataAdapter = Utils.Decorate(options.dataAdapter, Tags);
					}
					
					if (options.tokenSeparators != null || options.tokenizer != null) {
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							Tokenizer
						);
					}
					
					if (options.query != null) {
						var Query = require(options.amdBase + 'compat/query');
						
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							Query
						);
					}
					
					if (options.initSelection != null) {
						var InitSelection = require(options.amdBase + 'compat/initSelection');
						
						options.dataAdapter = Utils.Decorate(
							options.dataAdapter,
							InitSelection
						);
					}
				}
				
				if (options.resultsAdapter == null) {
					options.resultsAdapter = ResultsList;
					
					if (options.ajax != null) {
						options.resultsAdapter = Utils.Decorate(
							options.resultsAdapter,
							InfiniteScroll
						);
					}
					
					if (options.placeholder != null) {
						options.resultsAdapter = Utils.Decorate(
							options.resultsAdapter,
							HidePlaceholder
						);
					}
					
					if (options.selectOnClose) {
						options.resultsAdapter = Utils.Decorate(
							options.resultsAdapter,
							SelectOnClose
						);
					}
				}
				
				if (options.dropdownAdapter == null) {
					if (options.multiple) {
						options.dropdownAdapter = Dropdown;
					} else {
						var SearchableDropdown = Utils.Decorate(Dropdown, DropdownSearch);
						
						options.dropdownAdapter = SearchableDropdown;
					}
					
					if (options.minimumResultsForSearch !== 0) {
						options.dropdownAdapter = Utils.Decorate(
							options.dropdownAdapter,
							MinimumResultsForSearch
						);
					}
					
					if (options.closeOnSelect) {
						options.dropdownAdapter = Utils.Decorate(
							options.dropdownAdapter,
							CloseOnSelect
						);
					}
					
					if (
						options.dropdownCssClass != null ||
						options.dropdownCss != null ||
						options.adaptDropdownCssClass != null
					) {
						var DropdownCSS = require(options.amdBase + 'compat/dropdownCss');
						
						options.dropdownAdapter = Utils.Decorate(
							options.dropdownAdapter,
							DropdownCSS
						);
					}
					
					options.dropdownAdapter = Utils.Decorate(
						options.dropdownAdapter,
						AttachBody
					);
				}
				
				if (options.selectionAdapter == null) {
					if (options.multiple) {
						options.selectionAdapter = MultipleSelection;
					} else {
						options.selectionAdapter = SingleSelection;
					}
					
					// Add the placeholder mixin if a placeholder was specified
					if (options.placeholder != null) {
						options.selectionAdapter = Utils.Decorate(
							options.selectionAdapter,
							Placeholder
						);
					}
					
					if (options.allowClear) {
						options.selectionAdapter = Utils.Decorate(
							options.selectionAdapter,
							AllowClear
						);
					}
					
					if (options.multiple) {
						options.selectionAdapter = Utils.Decorate(
							options.selectionAdapter,
							SelectionSearch
						);
					}
					
					if (
						options.containerCssClass != null ||
						options.containerCss != null ||
						options.adaptContainerCssClass != null
					) {
						var ContainerCSS = require(options.amdBase + 'compat/containerCss');
						
						options.selectionAdapter = Utils.Decorate(
							options.selectionAdapter,
							ContainerCSS
						);
					}
					
					options.selectionAdapter = Utils.Decorate(
						options.selectionAdapter,
						EventRelay
					);
				}
				
				// If the defaults were not previously applied from an element, it is
				// possible for the language option to have not been resolved
				options.language = this._resolveLanguage(options.language);
				
				// Always fall back to English since it will always be complete
				options.language.push('en');
				
				var uniqueLanguages = [];
				
				for (var l = 0; l < options.language.length; l++) {
					var language = options.language[l];
					
					if (uniqueLanguages.indexOf(language) === -1) {
						uniqueLanguages.push(language);
					}
				}
				
				options.language = uniqueLanguages;
				
				options.translations = this._processTranslations(
					options.language,
					options.debug
				);
				
				return options;
			};
			
			Defaults.prototype.reset = function () {
				function stripDiacritics (text) {
					// Used 'uni range + named function' from http://jsperf.com/diacritics/18
					function match(a) {
						return DIACRITICS[a] || a;
					}
					
					return text.replace(/[^\u0000-\u007E]/g, match);
				}
				
				function matcher (params, data) {
					// Always return the object if there is nothing to compare
					if ($.trim(params.term) === '') {
						return data;
					}
					
					// Do a recursive check for options with children
					if (data.children && data.children.length > 0) {
						// Clone the data object if there are children
						// This is required as we modify the object to remove any non-matches
						var match = $.extend(true, {}, data);
						
						// Check each child of the option
						for (var c = data.children.length - 1; c >= 0; c--) {
							var child = data.children[c];
							
							var matches = matcher(params, child);
							
							// If there wasn't a match, remove the object in the array
							if (matches == null) {
								match.children.splice(c, 1);
							}
						}
						
						// If any children matched, return the new object
						if (match.children.length > 0) {
							return match;
						}
						
						// If there were no matching children, check just the plain object
						return matcher(params, match);
					}
					
					var original = stripDiacritics(data.text).toUpperCase();
					var term = stripDiacritics(params.term).toUpperCase();
					
					// Check if the text contains the term
					if (original.indexOf(term) > -1) {
						return data;
					}
					
					// If it doesn't contain the term, don't return anything
					return null;
				}
				
				this.defaults = {
					amdBase: './',
					amdLanguageBase: './i18n/',
					closeOnSelect: true,
					debug: false,
					dropdownAutoWidth: false,
					escapeMarkup: Utils.escapeMarkup,
					language: {},
					matcher: matcher,
					minimumInputLength: 0,
					maximumInputLength: 0,
					maximumSelectionLength: 0,
					minimumResultsForSearch: 0,
					selectOnClose: false,
					scrollAfterSelect: false,
					sorter: function (data) {
						return data;
					},
					templateResult: function (result) {
						return result.text;
					},
					templateSelection: function (selection) {
						return selection.text;
					},
					theme: 'default',
					width: 'resolve'
				};
			};
			
			Defaults.prototype.applyFromElement = function (options, $element) {
				var optionLanguage = options.language;
				var defaultLanguage = this.defaults.language;
				var elementLanguage = $element.prop('lang');
				var parentLanguage = $element.closest('[lang]').prop('lang');
				
				var languages = Array.prototype.concat.call(
					this._resolveLanguage(elementLanguage),
					this._resolveLanguage(optionLanguage),
					this._resolveLanguage(defaultLanguage),
					this._resolveLanguage(parentLanguage)
				);
				
				options.language = languages;
				
				return options;
			};
			
			Defaults.prototype._resolveLanguage = function (language) {
				if (!language) {
					return [];
				}
				
				if ($.isEmptyObject(language)) {
					return [];
				}
				
				if ($.isPlainObject(language)) {
					return [language];
				}
				
				var languages;
				
				if (!$.isArray(language)) {
					languages = [language];
				} else {
					languages = language;
				}
				
				var resolvedLanguages = [];
				
				for (var l = 0; l < languages.length; l++) {
					resolvedLanguages.push(languages[l]);
					
					if (typeof languages[l] === 'string' && languages[l].indexOf('-') > 0) {
						// Extract the region information if it is included
						var languageParts = languages[l].split('-');
						var baseLanguage = languageParts[0];
						
						resolvedLanguages.push(baseLanguage);
					}
				}
				
				return resolvedLanguages;
			};
			
			Defaults.prototype._processTranslations = function (languages, debug) {
				var translations = new Translation();
				
				for (var l = 0; l < languages.length; l++) {
					var languageData = new Translation();
					
					var language = languages[l];
					
					if (typeof language === 'string') {
						try {
							// Try to load it with the original name
							languageData = Translation.loadPath(language);
						} catch (e) {
							try {
								// If we couldn't load it, check if it wasn't the full path
								language = this.defaults.amdLanguageBase + language;
								languageData = Translation.loadPath(language);
							} catch (ex) {
								// The translation could not be loaded at all. Sometimes this is
								// because of a configuration problem, other times this can be
								// because of how Select2 helps load all possible translation files
								if (debug && window.console && console.warn) {
									console.warn(
										'Select2: The language file for "' + language + '" could ' +
										'not be automatically loaded. A fallback will be used instead.'
									);
								}
							}
						}
					} else if ($.isPlainObject(language)) {
						languageData = new Translation(language);
					} else {
						languageData = language;
					}
					
					translations.extend(languageData);
				}
				
				return translations;
			};
			
			Defaults.prototype.set = function (key, value) {
				var camelKey = $.camelCase(key);
				
				var data = {};
				data[camelKey] = value;
				
				var convertedData = Utils._convertData(data);
				
				$.extend(true, this.defaults, convertedData);
			};
			
			var defaults = new Defaults();
			
			return defaults;
		});
		
		S2.define('select2/options',[
			'require',
			'jquery',
			'./defaults',
			'./utils'
		], function (require, $, Defaults, Utils) {
			function Options (options, $element) {
				this.options = options;
				
				if ($element != null) {
					this.fromElement($element);
				}
				
				if ($element != null) {
					this.options = Defaults.applyFromElement(this.options, $element);
				}
				
				this.options = Defaults.apply(this.options);
				
				if ($element && $element.is('input')) {
					var InputCompat = require(this.get('amdBase') + 'compat/inputData');
					
					this.options.dataAdapter = Utils.Decorate(
						this.options.dataAdapter,
						InputCompat
					);
				}
			}
			
			Options.prototype.fromElement = function ($e) {
				var excludedData = ['select2'];
				
				if (this.options.multiple == null) {
					this.options.multiple = $e.prop('multiple');
				}
				
				if (this.options.disabled == null) {
					this.options.disabled = $e.prop('disabled');
				}
				
				if (this.options.dir == null) {
					if ($e.prop('dir')) {
						this.options.dir = $e.prop('dir');
					} else if ($e.closest('[dir]').prop('dir')) {
						this.options.dir = $e.closest('[dir]').prop('dir');
					} else {
						this.options.dir = 'ltr';
					}
				}
				
				$e.prop('disabled', this.options.disabled);
				$e.prop('multiple', this.options.multiple);
				
				if (Utils.GetData($e[0], 'select2Tags')) {
					if (this.options.debug && window.console && console.warn) {
						console.warn(
							'Select2: The `data-select2-tags` attribute has been changed to ' +
							'use the `data-data` and `data-tags="true"` attributes and will be ' +
							'removed in future versions of Select2.'
						);
					}
					
					Utils.StoreData($e[0], 'data', Utils.GetData($e[0], 'select2Tags'));
					Utils.StoreData($e[0], 'tags', true);
				}
				
				if (Utils.GetData($e[0], 'ajaxUrl')) {
					if (this.options.debug && window.console && console.warn) {
						console.warn(
							'Select2: The `data-ajax-url` attribute has been changed to ' +
							'`data-ajax--url` and support for the old attribute will be removed' +
							' in future versions of Select2.'
						);
					}
					
					$e.attr('ajax--url', Utils.GetData($e[0], 'ajaxUrl'));
					Utils.StoreData($e[0], 'ajax-Url', Utils.GetData($e[0], 'ajaxUrl'));
				}
				
				var dataset = {};
				
				function upperCaseLetter(_, letter) {
					return letter.toUpperCase();
				}
				
				// Pre-load all of the attributes which are prefixed with `data-`
				for (var attr = 0; attr < $e[0].attributes.length; attr++) {
					var attributeName = $e[0].attributes[attr].name;
					var prefix = 'data-';
					
					if (attributeName.substr(0, prefix.length) == prefix) {
						// Get the contents of the attribute after `data-`
						var dataName = attributeName.substring(prefix.length);
						
						// Get the data contents from the consistent source
						// This is more than likely the jQuery data helper
						var dataValue = Utils.GetData($e[0], dataName);
						
						// camelCase the attribute name to match the spec
						var camelDataName = dataName.replace(/-([a-z])/g, upperCaseLetter);
						
						// Store the data attribute contents into the dataset since
						dataset[camelDataName] = dataValue;
					}
				}
				
				// Prefer the element's `dataset` attribute if it exists
				// jQuery 1.x does not correctly handle data attributes with multiple dashes
				if ($.fn.jquery && $.fn.jquery.substr(0, 2) == '1.' && $e[0].dataset) {
					dataset = $.extend(true, {}, $e[0].dataset, dataset);
				}
				
				// Prefer our internal data cache if it exists
				var data = $.extend(true, {}, Utils.GetData($e[0]), dataset);
				
				data = Utils._convertData(data);
				
				for (var key in data) {
					if ($.inArray(key, excludedData) > -1) {
						continue;
					}
					
					if ($.isPlainObject(this.options[key])) {
						$.extend(this.options[key], data[key]);
					} else {
						this.options[key] = data[key];
					}
				}
				
				return this;
			};
			
			Options.prototype.get = function (key) {
				return this.options[key];
			};
			
			Options.prototype.set = function (key, val) {
				this.options[key] = val;
			};
			
			return Options;
		});
		
		S2.define('select2/core',[
			'jquery',
			'./options',
			'./utils',
			'./keys'
		], function ($, Options, Utils, KEYS) {
			var Select2 = function ($element, options) {
				if (Utils.GetData($element[0], 'select2') != null) {
					Utils.GetData($element[0], 'select2').destroy();
				}
				
				this.$element = $element;
				
				this.id = this._generateId($element);
				
				options = options || {};
				
				this.options = new Options(options, $element);
				
				Select2.__super__.constructor.call(this);
				
				// Set up the tabindex
				
				var tabindex = $element.attr('tabindex') || 0;
				Utils.StoreData($element[0], 'old-tabindex', tabindex);
				$element.attr('tabindex', '-1');
				
				// Set up containers and adapters
				
				var DataAdapter = this.options.get('dataAdapter');
				this.dataAdapter = new DataAdapter($element, this.options);
				
				var $container = this.render();
				
				this._placeContainer($container);
				
				var SelectionAdapter = this.options.get('selectionAdapter');
				this.selection = new SelectionAdapter($element, this.options);
				this.$selection = this.selection.render();
				
				this.selection.position(this.$selection, $container);
				
				var DropdownAdapter = this.options.get('dropdownAdapter');
				this.dropdown = new DropdownAdapter($element, this.options);
				this.$dropdown = this.dropdown.render();
				
				this.dropdown.position(this.$dropdown, $container);
				
				var ResultsAdapter = this.options.get('resultsAdapter');
				this.results = new ResultsAdapter($element, this.options, this.dataAdapter);
				this.$results = this.results.render();
				
				this.results.position(this.$results, this.$dropdown);
				
				// Bind events
				
				var self = this;
				
				// Bind the container to all of the adapters
				this._bindAdapters();
				
				// Register any DOM event handlers
				this._registerDomEvents();
				
				// Register any internal event handlers
				this._registerDataEvents();
				this._registerSelectionEvents();
				this._registerDropdownEvents();
				this._registerResultsEvents();
				this._registerEvents();
				
				// Set the initial state
				this.dataAdapter.current(function (initialData) {
					self.trigger('selection:update', {
						data: initialData
					});
				});
				
				// Hide the original select
				$element.addClass('select2-hidden-accessible');
				$element.attr('aria-hidden', 'true');
				
				// Synchronize any monitored attributes
				this._syncAttributes();
				
				Utils.StoreData($element[0], 'select2', this);
				
				// Ensure backwards compatibility with $element.data('select2').
				$element.data('select2', this);
			};
			
			Utils.Extend(Select2, Utils.Observable);
			
			Select2.prototype._generateId = function ($element) {
				var id = '';
				
				if ($element.attr('id') != null) {
					id = $element.attr('id');
				} else if ($element.attr('name') != null) {
					id = $element.attr('name') + '-' + Utils.generateChars(2);
				} else {
					id = Utils.generateChars(4);
				}
				
				id = id.replace(/(:|\.|\[|\]|,)/g, '');
				id = 'select2-' + id;
				
				return id;
			};
			
			Select2.prototype._placeContainer = function ($container) {
				$container.insertAfter(this.$element);
				
				var width = this._resolveWidth(this.$element, this.options.get('width'));
				
				if (width != null) {
					$container.css('width', width);
				}
			};
			
			Select2.prototype._resolveWidth = function ($element, method) {
				var WIDTH = /^width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/i;
				
				if (method == 'resolve') {
					var styleWidth = this._resolveWidth($element, 'style');
					
					if (styleWidth != null) {
						return styleWidth;
					}
					
					return this._resolveWidth($element, 'element');
				}
				
				if (method == 'element') {
					var elementWidth = $element.outerWidth(false);
					
					if (elementWidth <= 0) {
						return 'auto';
					}
					
					return elementWidth + 'px';
				}
				
				if (method == 'style') {
					var style = $element.attr('style');
					
					if (typeof(style) !== 'string') {
						return null;
					}
					
					var attrs = style.split(';');
					
					for (var i = 0, l = attrs.length; i < l; i = i + 1) {
						var attr = attrs[i].replace(/\s/g, '');
						var matches = attr.match(WIDTH);
						
						if (matches !== null && matches.length >= 1) {
							return matches[1];
						}
					}
					
					return null;
				}
				
				if (method == 'computedstyle') {
					var computedStyle = window.getComputedStyle($element[0]);
					
					return computedStyle.width;
				}
				
				return method;
			};
			
			Select2.prototype._bindAdapters = function () {
				this.dataAdapter.bind(this, this.$container);
				this.selection.bind(this, this.$container);
				
				this.dropdown.bind(this, this.$container);
				this.results.bind(this, this.$container);
			};
			
			Select2.prototype._registerDomEvents = function () {
				var self = this;
				
				this.$element.on('change.select2', function () {
					self.dataAdapter.current(function (data) {
						self.trigger('selection:update', {
							data: data
						});
					});
				});
				
				this.$element.on('focus.select2', function (evt) {
					self.trigger('focus', evt);
				});
				
				this._syncA = Utils.bind(this._syncAttributes, this);
				this._syncS = Utils.bind(this._syncSubtree, this);
				
				if (this.$element[0].attachEvent) {
					this.$element[0].attachEvent('onpropertychange', this._syncA);
				}
				
				var observer = window.MutationObserver ||
					window.WebKitMutationObserver ||
					window.MozMutationObserver
				;
				
				if (observer != null) {
					this._observer = new observer(function (mutations) {
						self._syncA();
						self._syncS(null, mutations);
					});
					this._observer.observe(this.$element[0], {
						attributes: true,
						childList: true,
						subtree: false
					});
				} else if (this.$element[0].addEventListener) {
					this.$element[0].addEventListener(
						'DOMAttrModified',
						self._syncA,
						false
					);
					this.$element[0].addEventListener(
						'DOMNodeInserted',
						self._syncS,
						false
					);
					this.$element[0].addEventListener(
						'DOMNodeRemoved',
						self._syncS,
						false
					);
				}
			};
			
			Select2.prototype._registerDataEvents = function () {
				var self = this;
				
				this.dataAdapter.on('*', function (name, params) {
					self.trigger(name, params);
				});
			};
			
			Select2.prototype._registerSelectionEvents = function () {
				var self = this;
				var nonRelayEvents = ['toggle', 'focus'];
				
				this.selection.on('toggle', function () {
					self.toggleDropdown();
				});
				
				this.selection.on('focus', function (params) {
					self.focus(params);
				});
				
				this.selection.on('*', function (name, params) {
					if ($.inArray(name, nonRelayEvents) !== -1) {
						return;
					}
					
					self.trigger(name, params);
				});
			};
			
			Select2.prototype._registerDropdownEvents = function () {
				var self = this;
				
				this.dropdown.on('*', function (name, params) {
					self.trigger(name, params);
				});
			};
			
			Select2.prototype._registerResultsEvents = function () {
				var self = this;
				
				this.results.on('*', function (name, params) {
					self.trigger(name, params);
				});
			};
			
			Select2.prototype._registerEvents = function () {
				var self = this;
				
				this.on('open', function () {
					self.$container.addClass('select2-container--open');
				});
				
				this.on('close', function () {
					self.$container.removeClass('select2-container--open');
				});
				
				this.on('enable', function () {
					self.$container.removeClass('select2-container--disabled');
				});
				
				this.on('disable', function () {
					self.$container.addClass('select2-container--disabled');
				});
				
				this.on('blur', function () {
					self.$container.removeClass('select2-container--focus');
				});
				
				this.on('query', function (params) {
					if (!self.isOpen()) {
						self.trigger('open', {});
					}
					
					this.dataAdapter.query(params, function (data) {
						self.trigger('results:all', {
							data: data,
							query: params
						});
					});
				});
				
				this.on('query:append', function (params) {
					this.dataAdapter.query(params, function (data) {
						self.trigger('results:append', {
							data: data,
							query: params
						});
					});
				});
				
				this.on('keypress', function (evt) {
					var key = evt.which;
					
					if (self.isOpen()) {
						if (key === KEYS.ESC || key === KEYS.TAB ||
							(key === KEYS.UP && evt.altKey)) {
							self.close(evt);
							
							evt.preventDefault();
						} else if (key === KEYS.ENTER) {
							self.trigger('results:select', {});
							
							evt.preventDefault();
						} else if ((key === KEYS.SPACE && evt.ctrlKey)) {
							self.trigger('results:toggle', {});
							
							evt.preventDefault();
						} else if (key === KEYS.UP) {
							self.trigger('results:previous', {});
							
							evt.preventDefault();
						} else if (key === KEYS.DOWN) {
							self.trigger('results:next', {});
							
							evt.preventDefault();
						}
					} else {
						if (key === KEYS.ENTER || key === KEYS.SPACE ||
							(key === KEYS.DOWN && evt.altKey)) {
							self.open();
							
							evt.preventDefault();
						}
					}
				});
			};
			
			Select2.prototype._syncAttributes = function () {
				this.options.set('disabled', this.$element.prop('disabled'));
				
				if (this.isDisabled()) {
					if (this.isOpen()) {
						this.close();
					}
					
					this.trigger('disable', {});
				} else {
					this.trigger('enable', {});
				}
			};
			
			Select2.prototype._isChangeMutation = function (evt, mutations) {
				var changed = false;
				var self = this;
				
				// Ignore any mutation events raised for elements that aren't options or
				// optgroups. This handles the case when the select element is destroyed
				if (
					evt && evt.target && (
						evt.target.nodeName !== 'OPTION' && evt.target.nodeName !== 'OPTGROUP'
					)
				) {
					return;
				}
				
				if (!mutations) {
					// If mutation events aren't supported, then we can only assume that the
					// change affected the selections
					changed = true;
				} else if (mutations.addedNodes && mutations.addedNodes.length > 0) {
					for (var n = 0; n < mutations.addedNodes.length; n++) {
						var node = mutations.addedNodes[n];
						
						if (node.selected) {
							changed = true;
						}
					}
				} else if (mutations.removedNodes && mutations.removedNodes.length > 0) {
					changed = true;
				} else if ($.isArray(mutations)) {
					$.each(mutations, function(evt, mutation) {
						if (self._isChangeMutation(evt, mutation)) {
							// We've found a change mutation.
							// Let's escape from the loop and continue
							changed = true;
							return false;
						}
					});
				}
				return changed;
			};
			
			Select2.prototype._syncSubtree = function (evt, mutations) {
				var changed = this._isChangeMutation(evt, mutations);
				var self = this;
				
				// Only re-pull the data if we think there is a change
				if (changed) {
					this.dataAdapter.current(function (currentData) {
						self.trigger('selection:update', {
							data: currentData
						});
					});
				}
			};
			
			/**
			 * Override the trigger method to automatically trigger pre-events when
			 * there are events that can be prevented.
			 */
			Select2.prototype.trigger = function (name, args) {
				var actualTrigger = Select2.__super__.trigger;
				var preTriggerMap = {
					'open': 'opening',
					'close': 'closing',
					'select': 'selecting',
					'unselect': 'unselecting',
					'clear': 'clearing'
				};
				
				if (args === undefined) {
					args = {};
				}
				
				if (name in preTriggerMap) {
					var preTriggerName = preTriggerMap[name];
					var preTriggerArgs = {
						prevented: false,
						name: name,
						args: args
					};
					
					actualTrigger.call(this, preTriggerName, preTriggerArgs);
					
					if (preTriggerArgs.prevented) {
						args.prevented = true;
						
						return;
					}
				}
				
				actualTrigger.call(this, name, args);
			};
			
			Select2.prototype.toggleDropdown = function () {
				if (this.isDisabled()) {
					return;
				}
				
				if (this.isOpen()) {
					this.close();
				} else {
					this.open();
				}
			};
			
			Select2.prototype.open = function () {
				if (this.isOpen()) {
					return;
				}
				
				if (this.isDisabled()) {
					return;
				}
				
				this.trigger('query', {});
			};
			
			Select2.prototype.close = function (evt) {
				if (!this.isOpen()) {
					return;
				}
				
				this.trigger('close', { originalEvent : evt });
			};
			
			/**
			 * Helper method to abstract the "enabled" (not "disabled") state of this
			 * object.
			 *
			 * @return {true} if the instance is not disabled.
			 * @return {false} if the instance is disabled.
			 */
			Select2.prototype.isEnabled = function () {
				return !this.isDisabled();
			};
			
			/**
			 * Helper method to abstract the "disabled" state of this object.
			 *
			 * @return {true} if the disabled option is true.
			 * @return {false} if the disabled option is false.
			 */
			Select2.prototype.isDisabled = function () {
				return this.options.get('disabled');
			};
			
			Select2.prototype.isOpen = function () {
				return this.$container.hasClass('select2-container--open');
			};
			
			Select2.prototype.hasFocus = function () {
				return this.$container.hasClass('select2-container--focus');
			};
			
			Select2.prototype.focus = function (data) {
				// No need to re-trigger focus events if we are already focused
				if (this.hasFocus()) {
					return;
				}
				
				this.$container.addClass('select2-container--focus');
				this.trigger('focus', {});
			};
			
			Select2.prototype.enable = function (args) {
				if (this.options.get('debug') && window.console && console.warn) {
					console.warn(
						'Select2: The `select2("enable")` method has been deprecated and will' +
						' be removed in later Select2 versions. Use $element.prop("disabled")' +
						' instead.'
					);
				}
				
				if (args == null || args.length === 0) {
					args = [true];
				}
				
				var disabled = !args[0];
				
				this.$element.prop('disabled', disabled);
			};
			
			Select2.prototype.data = function () {
				if (this.options.get('debug') &&
					arguments.length > 0 && window.console && console.warn) {
					console.warn(
						'Select2: Data can no longer be set using `select2("data")`. You ' +
						'should consider setting the value instead using `$element.val()`.'
					);
				}
				
				var data = [];
				
				this.dataAdapter.current(function (currentData) {
					data = currentData;
				});
				
				return data;
			};
			
			Select2.prototype.val = function (args) {
				if (this.options.get('debug') && window.console && console.warn) {
					console.warn(
						'Select2: The `select2("val")` method has been deprecated and will be' +
						' removed in later Select2 versions. Use $element.val() instead.'
					);
				}
				
				if (args == null || args.length === 0) {
					return this.$element.val();
				}
				
				var newVal = args[0];
				
				if ($.isArray(newVal)) {
					newVal = $.map(newVal, function (obj) {
						return obj.toString();
					});
				}
				
				this.$element.val(newVal).trigger('input').trigger('change');
			};
			
			Select2.prototype.destroy = function () {
				this.$container.remove();
				
				if (this.$element[0].detachEvent) {
					this.$element[0].detachEvent('onpropertychange', this._syncA);
				}
				
				if (this._observer != null) {
					this._observer.disconnect();
					this._observer = null;
				} else if (this.$element[0].removeEventListener) {
					this.$element[0]
						.removeEventListener('DOMAttrModified', this._syncA, false);
					this.$element[0]
						.removeEventListener('DOMNodeInserted', this._syncS, false);
					this.$element[0]
						.removeEventListener('DOMNodeRemoved', this._syncS, false);
				}
				
				this._syncA = null;
				this._syncS = null;
				
				this.$element.off('.select2');
				this.$element.attr('tabindex',
					Utils.GetData(this.$element[0], 'old-tabindex'));
				
				this.$element.removeClass('select2-hidden-accessible');
				this.$element.attr('aria-hidden', 'false');
				Utils.RemoveData(this.$element[0]);
				this.$element.removeData('select2');
				
				this.dataAdapter.destroy();
				this.selection.destroy();
				this.dropdown.destroy();
				this.results.destroy();
				
				this.dataAdapter = null;
				this.selection = null;
				this.dropdown = null;
				this.results = null;
			};
			
			Select2.prototype.render = function () {
				var $container = $(
					'<span class="select2 select2-container">' +
					'<span class="selection"></span>' +
					'<span class="dropdown-wrapper" aria-hidden="true"></span>' +
					'</span>'
				);
				
				$container.attr('dir', this.options.get('dir'));
				
				this.$container = $container;
				
				this.$container.addClass('select2-container--' + this.options.get('theme'));
				
				Utils.StoreData($container[0], 'element', this.$element);
				
				return $container;
			};
			
			return Select2;
		});
		
		S2.define('jquery-mousewheel',[
			'jquery'
		], function ($) {
			// Used to shim jQuery.mousewheel for non-full builds.
			return $;
		});
		
		S2.define('jquery.select2',[
			'jquery',
			'jquery-mousewheel',
			
			'./select2/core',
			'./select2/defaults',
			'./select2/utils'
		], function ($, _, Select2, Defaults, Utils) {
			if ($.fn.select2 == null) {
				// All methods that should return the element
				var thisMethods = ['open', 'close', 'destroy'];
				
				$.fn.select2 = function (options) {
					options = options || {};
					
					if (typeof options === 'object') {
						this.each(function () {
							var instanceOptions = $.extend(true, {}, options);
							
							var instance = new Select2($(this), instanceOptions);
						});
						
						return this;
					} else if (typeof options === 'string') {
						var ret;
						var args = Array.prototype.slice.call(arguments, 1);
						
						this.each(function () {
							var instance = Utils.GetData(this, 'select2');
							
							if (instance == null && window.console && console.error) {
								console.error(
									'The select2(\'' + options + '\') method was called on an ' +
									'element that is not using Select2.'
								);
							}
							
							ret = instance[options].apply(instance, args);
						});
						
						// Check if we should be returning `this`
						if ($.inArray(options, thisMethods) > -1) {
							return this;
						}
						
						return ret;
					} else {
						throw new Error('Invalid arguments for Select2: ' + options);
					}
				};
			}
			
			if ($.fn.select2.defaults == null) {
				$.fn.select2.defaults = Defaults;
			}
			
			return Select2;
		});
		
		// Return the AMD loader configuration so it can be used outside of this file
		return {
			define: S2.define,
			require: S2.require
		};
	}());
	
	// Autoload the jQuery bindings
	// We know that all of the modules exist above this, so we're safe
	var select2 = S2.require('jquery.select2');
	
	// Hold the AMD module references on the jQuery function that was just loaded
	// This allows Select2 to use the internal loader outside of this file, such
	// as in the language files.
	jQuery.fn.select2.amd = S2;
	
	// Return the Select2 instance for anyone who is importing it.
	return select2;
}));

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/createPopper.js":
/*!*********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/createPopper.js ***!
  \*********************************************************/
/*! exports provided: popperGenerator, createPopper, detectOverflow */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "popperGenerator", function() { return popperGenerator; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createPopper", function() { return createPopper; });
/* harmony import */ var _dom_utils_getCompositeRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./dom-utils/getCompositeRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./dom-utils/listScrollParents.js */ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./dom-utils/getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _utils_orderModifiers_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./utils/orderModifiers.js */ "./node_modules/@popperjs/core/lib/utils/orderModifiers.js");
/* harmony import */ var _utils_debounce_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./utils/debounce.js */ "./node_modules/@popperjs/core/lib/utils/debounce.js");
/* harmony import */ var _utils_validateModifiers_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./utils/validateModifiers.js */ "./node_modules/@popperjs/core/lib/utils/validateModifiers.js");
/* harmony import */ var _utils_uniqueBy_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./utils/uniqueBy.js */ "./node_modules/@popperjs/core/lib/utils/uniqueBy.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_mergeByName_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./utils/mergeByName.js */ "./node_modules/@popperjs/core/lib/utils/mergeByName.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "detectOverflow", function() { return _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_11__["default"]; });

/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./enums.js */ "./node_modules/@popperjs/core/lib/enums.js");














var INVALID_ELEMENT_ERROR = 'Popper: Invalid reference or popper argument provided. They must be either a DOM element or virtual element.';
var INFINITE_LOOP_ERROR = 'Popper: An infinite loop in the modifiers cycle has been detected! The cycle has been interrupted to prevent a browser crash.';
var DEFAULT_OPTIONS = {
  placement: 'bottom',
  modifiers: [],
  strategy: 'absolute'
};

function areValidElements() {
  for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
    args[_key] = arguments[_key];
  }

  return !args.some(function (element) {
    return !(element && typeof element.getBoundingClientRect === 'function');
  });
}

function popperGenerator(generatorOptions) {
  if (generatorOptions === void 0) {
    generatorOptions = {};
  }

  var _generatorOptions = generatorOptions,
      _generatorOptions$def = _generatorOptions.defaultModifiers,
      defaultModifiers = _generatorOptions$def === void 0 ? [] : _generatorOptions$def,
      _generatorOptions$def2 = _generatorOptions.defaultOptions,
      defaultOptions = _generatorOptions$def2 === void 0 ? DEFAULT_OPTIONS : _generatorOptions$def2;
  return function createPopper(reference, popper, options) {
    if (options === void 0) {
      options = defaultOptions;
    }

    var state = {
      placement: 'bottom',
      orderedModifiers: [],
      options: Object.assign({}, DEFAULT_OPTIONS, defaultOptions),
      modifiersData: {},
      elements: {
        reference: reference,
        popper: popper
      },
      attributes: {},
      styles: {}
    };
    var effectCleanupFns = [];
    var isDestroyed = false;
    var instance = {
      state: state,
      setOptions: function setOptions(setOptionsAction) {
        var options = typeof setOptionsAction === 'function' ? setOptionsAction(state.options) : setOptionsAction;
        cleanupModifierEffects();
        state.options = Object.assign({}, defaultOptions, state.options, options);
        state.scrollParents = {
          reference: Object(_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_12__["isElement"])(reference) ? Object(_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(reference) : reference.contextElement ? Object(_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(reference.contextElement) : [],
          popper: Object(_dom_utils_listScrollParents_js__WEBPACK_IMPORTED_MODULE_2__["default"])(popper)
        }; // Orders the modifiers based on their dependencies and `phase`
        // properties

        var orderedModifiers = Object(_utils_orderModifiers_js__WEBPACK_IMPORTED_MODULE_5__["default"])(Object(_utils_mergeByName_js__WEBPACK_IMPORTED_MODULE_10__["default"])([].concat(defaultModifiers, state.options.modifiers))); // Strip out disabled modifiers

        state.orderedModifiers = orderedModifiers.filter(function (m) {
          return m.enabled;
        }); // Validate the provided modifiers so that the consumer will get warned
        // if one of the modifiers is invalid for any reason

        if (true) {
          var modifiers = Object(_utils_uniqueBy_js__WEBPACK_IMPORTED_MODULE_8__["default"])([].concat(orderedModifiers, state.options.modifiers), function (_ref) {
            var name = _ref.name;
            return name;
          });
          Object(_utils_validateModifiers_js__WEBPACK_IMPORTED_MODULE_7__["default"])(modifiers);

          if (Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_9__["default"])(state.options.placement) === _enums_js__WEBPACK_IMPORTED_MODULE_13__["auto"]) {
            var flipModifier = state.orderedModifiers.find(function (_ref2) {
              var name = _ref2.name;
              return name === 'flip';
            });

            if (!flipModifier) {
              console.error(['Popper: "auto" placements require the "flip" modifier be', 'present and enabled to work.'].join(' '));
            }
          }

          var _getComputedStyle = Object(_dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__["default"])(popper),
              marginTop = _getComputedStyle.marginTop,
              marginRight = _getComputedStyle.marginRight,
              marginBottom = _getComputedStyle.marginBottom,
              marginLeft = _getComputedStyle.marginLeft; // We no longer take into account `margins` on the popper, and it can
          // cause bugs with positioning, so we'll warn the consumer


          if ([marginTop, marginRight, marginBottom, marginLeft].some(function (margin) {
            return parseFloat(margin);
          })) {
            console.warn(['Popper: CSS "margin" styles cannot be used to apply padding', 'between the popper and its reference element or boundary.', 'To replicate margin, use the `offset` modifier, as well as', 'the `padding` option in the `preventOverflow` and `flip`', 'modifiers.'].join(' '));
          }
        }

        runModifierEffects();
        return instance.update();
      },
      // Sync update – it will always be executed, even if not necessary. This
      // is useful for low frequency updates where sync behavior simplifies the
      // logic.
      // For high frequency updates (e.g. `resize` and `scroll` events), always
      // prefer the async Popper#update method
      forceUpdate: function forceUpdate() {
        if (isDestroyed) {
          return;
        }

        var _state$elements = state.elements,
            reference = _state$elements.reference,
            popper = _state$elements.popper; // Don't proceed if `reference` or `popper` are not valid elements
        // anymore

        if (!areValidElements(reference, popper)) {
          if (true) {
            console.error(INVALID_ELEMENT_ERROR);
          }

          return;
        } // Store the reference and popper rects to be read by modifiers


        state.rects = {
          reference: Object(_dom_utils_getCompositeRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(reference, Object(_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(popper), state.options.strategy === 'fixed'),
          popper: Object(_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(popper)
        }; // Modifiers have the ability to reset the current update cycle. The
        // most common use case for this is the `flip` modifier changing the
        // placement, which then needs to re-run all the modifiers, because the
        // logic was previously ran for the previous placement and is therefore
        // stale/incorrect

        state.reset = false;
        state.placement = state.options.placement; // On each update cycle, the `modifiersData` property for each modifier
        // is filled with the initial data specified by the modifier. This means
        // it doesn't persist and is fresh on each update.
        // To ensure persistent data, use `${name}#persistent`

        state.orderedModifiers.forEach(function (modifier) {
          return state.modifiersData[modifier.name] = Object.assign({}, modifier.data);
        });
        var __debug_loops__ = 0;

        for (var index = 0; index < state.orderedModifiers.length; index++) {
          if (true) {
            __debug_loops__ += 1;

            if (__debug_loops__ > 100) {
              console.error(INFINITE_LOOP_ERROR);
              break;
            }
          }

          if (state.reset === true) {
            state.reset = false;
            index = -1;
            continue;
          }

          var _state$orderedModifie = state.orderedModifiers[index],
              fn = _state$orderedModifie.fn,
              _state$orderedModifie2 = _state$orderedModifie.options,
              _options = _state$orderedModifie2 === void 0 ? {} : _state$orderedModifie2,
              name = _state$orderedModifie.name;

          if (typeof fn === 'function') {
            state = fn({
              state: state,
              options: _options,
              name: name,
              instance: instance
            }) || state;
          }
        }
      },
      // Async and optimistically optimized update – it will not be executed if
      // not necessary (debounced to run at most once-per-tick)
      update: Object(_utils_debounce_js__WEBPACK_IMPORTED_MODULE_6__["default"])(function () {
        return new Promise(function (resolve) {
          instance.forceUpdate();
          resolve(state);
        });
      }),
      destroy: function destroy() {
        cleanupModifierEffects();
        isDestroyed = true;
      }
    };

    if (!areValidElements(reference, popper)) {
      if (true) {
        console.error(INVALID_ELEMENT_ERROR);
      }

      return instance;
    }

    instance.setOptions(options).then(function (state) {
      if (!isDestroyed && options.onFirstUpdate) {
        options.onFirstUpdate(state);
      }
    }); // Modifiers have the ability to execute arbitrary code before the first
    // update cycle runs. They will be executed in the same order as the update
    // cycle. This is useful when a modifier adds some persistent data that
    // other modifiers need to use, but the modifier is run after the dependent
    // one.

    function runModifierEffects() {
      state.orderedModifiers.forEach(function (_ref3) {
        var name = _ref3.name,
            _ref3$options = _ref3.options,
            options = _ref3$options === void 0 ? {} : _ref3$options,
            effect = _ref3.effect;

        if (typeof effect === 'function') {
          var cleanupFn = effect({
            state: state,
            name: name,
            instance: instance,
            options: options
          });

          var noopFn = function noopFn() {};

          effectCleanupFns.push(cleanupFn || noopFn);
        }
      });
    }

    function cleanupModifierEffects() {
      effectCleanupFns.forEach(function (fn) {
        return fn();
      });
      effectCleanupFns = [];
    }

    return instance;
  };
}
var createPopper = /*#__PURE__*/popperGenerator(); // eslint-disable-next-line import/no-unused-modules



/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/contains.js":
/*!***************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/contains.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return contains; });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

function contains(parent, child) {
  var rootNode = child.getRootNode && child.getRootNode(); // First, attempt with faster native method

  if (parent.contains(child)) {
    return true;
  } // then fallback to custom implementation with Shadow DOM support
  else if (rootNode && Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__["isShadowRoot"])(rootNode)) {
      var next = child;

      do {
        if (next && parent.isSameNode(next)) {
          return true;
        } // $FlowFixMe[prop-missing]: need a better way to handle this...


        next = next.parentNode || next.host;
      } while (next);
    } // Give up, the result is false


  return false;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js":
/*!****************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js ***!
  \****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getBoundingClientRect; });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isLayoutViewport.js */ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js");




function getBoundingClientRect(element, includeScale, isFixedStrategy) {
  if (includeScale === void 0) {
    includeScale = false;
  }

  if (isFixedStrategy === void 0) {
    isFixedStrategy = false;
  }

  var clientRect = element.getBoundingClientRect();
  var scaleX = 1;
  var scaleY = 1;

  if (includeScale && Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__["isHTMLElement"])(element)) {
    scaleX = element.offsetWidth > 0 ? Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_1__["round"])(clientRect.width) / element.offsetWidth || 1 : 1;
    scaleY = element.offsetHeight > 0 ? Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_1__["round"])(clientRect.height) / element.offsetHeight || 1 : 1;
  }

  var _ref = Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__["isElement"])(element) ? Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element) : window,
      visualViewport = _ref.visualViewport;

  var addVisualOffsets = !Object(_isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__["default"])() && isFixedStrategy;
  var x = (clientRect.left + (addVisualOffsets && visualViewport ? visualViewport.offsetLeft : 0)) / scaleX;
  var y = (clientRect.top + (addVisualOffsets && visualViewport ? visualViewport.offsetTop : 0)) / scaleY;
  var width = clientRect.width / scaleX;
  var height = clientRect.height / scaleY;
  return {
    width: width,
    height: height,
    top: y,
    right: x + width,
    bottom: y + height,
    left: x,
    x: x,
    y: y
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getClippingRect; });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _getViewportRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getViewportRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js");
/* harmony import */ var _getDocumentRect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getDocumentRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js");
/* harmony import */ var _listScrollParents_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./listScrollParents.js */ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js");
/* harmony import */ var _getOffsetParent_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _contains_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./contains.js */ "./node_modules/@popperjs/core/lib/dom-utils/contains.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../utils/rectToClientRect.js */ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");















function getInnerBoundingClientRect(element, strategy) {
  var rect = Object(_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_8__["default"])(element, false, strategy === 'fixed');
  rect.top = rect.top + element.clientTop;
  rect.left = rect.left + element.clientLeft;
  rect.bottom = rect.top + element.clientHeight;
  rect.right = rect.left + element.clientWidth;
  rect.width = element.clientWidth;
  rect.height = element.clientHeight;
  rect.x = rect.left;
  rect.y = rect.top;
  return rect;
}

function getClientRectFromMixedType(element, clippingParent, strategy) {
  return clippingParent === _enums_js__WEBPACK_IMPORTED_MODULE_0__["viewport"] ? Object(_utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__["default"])(Object(_getViewportRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element, strategy)) : Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__["isElement"])(clippingParent) ? getInnerBoundingClientRect(clippingParent, strategy) : Object(_utils_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_12__["default"])(Object(_getDocumentRect_js__WEBPACK_IMPORTED_MODULE_2__["default"])(Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(element)));
} // A "clipping parent" is an overflowable container with the characteristic of
// clipping (or hiding) overflowing elements with a position different from
// `initial`


function getClippingParents(element) {
  var clippingParents = Object(_listScrollParents_js__WEBPACK_IMPORTED_MODULE_3__["default"])(Object(_getParentNode_js__WEBPACK_IMPORTED_MODULE_9__["default"])(element));
  var canEscapeClipping = ['absolute', 'fixed'].indexOf(Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_6__["default"])(element).position) >= 0;
  var clipperElement = canEscapeClipping && Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__["isHTMLElement"])(element) ? Object(_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_4__["default"])(element) : element;

  if (!Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__["isElement"])(clipperElement)) {
    return [];
  } // $FlowFixMe[incompatible-return]: https://github.com/facebook/flow/issues/1414


  return clippingParents.filter(function (clippingParent) {
    return Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_7__["isElement"])(clippingParent) && Object(_contains_js__WEBPACK_IMPORTED_MODULE_10__["default"])(clippingParent, clipperElement) && Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_11__["default"])(clippingParent) !== 'body';
  });
} // Gets the maximum area that the element is visible in due to any number of
// clipping parents


function getClippingRect(element, boundary, rootBoundary, strategy) {
  var mainClippingParents = boundary === 'clippingParents' ? getClippingParents(element) : [].concat(boundary);
  var clippingParents = [].concat(mainClippingParents, [rootBoundary]);
  var firstClippingParent = clippingParents[0];
  var clippingRect = clippingParents.reduce(function (accRect, clippingParent) {
    var rect = getClientRectFromMixedType(element, clippingParent, strategy);
    accRect.top = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_13__["max"])(rect.top, accRect.top);
    accRect.right = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_13__["min"])(rect.right, accRect.right);
    accRect.bottom = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_13__["min"])(rect.bottom, accRect.bottom);
    accRect.left = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_13__["max"])(rect.left, accRect.left);
    return accRect;
  }, getClientRectFromMixedType(element, firstClippingParent, strategy));
  clippingRect.width = clippingRect.right - clippingRect.left;
  clippingRect.height = clippingRect.bottom - clippingRect.top;
  clippingRect.x = clippingRect.left;
  clippingRect.y = clippingRect.top;
  return clippingRect;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getCompositeRect.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getCompositeRect; });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getNodeScroll_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getNodeScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");









function isElementScaled(element) {
  var rect = element.getBoundingClientRect();
  var scaleX = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_7__["round"])(rect.width) / element.offsetWidth || 1;
  var scaleY = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_7__["round"])(rect.height) / element.offsetHeight || 1;
  return scaleX !== 1 || scaleY !== 1;
} // Returns the composite rect of an element relative to its offsetParent.
// Composite means it takes into account transforms as well as layout.


function getCompositeRect(elementOrVirtualElement, offsetParent, isFixed) {
  if (isFixed === void 0) {
    isFixed = false;
  }

  var isOffsetParentAnElement = Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(offsetParent);
  var offsetParentIsScaled = Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(offsetParent) && isElementScaled(offsetParent);
  var documentElement = Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(offsetParent);
  var rect = Object(_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(elementOrVirtualElement, offsetParentIsScaled, isFixed);
  var scroll = {
    scrollLeft: 0,
    scrollTop: 0
  };
  var offsets = {
    x: 0,
    y: 0
  };

  if (isOffsetParentAnElement || !isOffsetParentAnElement && !isFixed) {
    if (Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent) !== 'body' || // https://github.com/popperjs/popper-core/issues/1078
    Object(_isScrollParent_js__WEBPACK_IMPORTED_MODULE_6__["default"])(documentElement)) {
      scroll = Object(_getNodeScroll_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent);
    }

    if (Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(offsetParent)) {
      offsets = Object(_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(offsetParent, true);
      offsets.x += offsetParent.clientLeft;
      offsets.y += offsetParent.clientTop;
    } else if (documentElement) {
      offsets.x = Object(_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_4__["default"])(documentElement);
    }
  }

  return {
    x: rect.left + scroll.scrollLeft - offsets.x,
    y: rect.top + scroll.scrollTop - offsets.y,
    width: rect.width,
    height: rect.height
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getComputedStyle; });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");

function getComputedStyle(element) {
  return Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element).getComputedStyle(element);
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js":
/*!*************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js ***!
  \*************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getDocumentElement; });
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

function getDocumentElement(element) {
  // $FlowFixMe[incompatible-return]: assume body is always available
  return ((Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_0__["isElement"])(element) ? element.ownerDocument : // $FlowFixMe[prop-missing]
  element.document) || window.document).documentElement;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getDocumentRect.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getDocumentRect; });
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");




 // Gets the entire size of the scrollable document area, even extending outside
// of the `<html>` and `<body>` rect bounds if horizontally scrollable

function getDocumentRect(element) {
  var _element$ownerDocumen;

  var html = Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var winScroll = Object(_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_3__["default"])(element);
  var body = (_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body;
  var width = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_4__["max"])(html.scrollWidth, html.clientWidth, body ? body.scrollWidth : 0, body ? body.clientWidth : 0);
  var height = Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_4__["max"])(html.scrollHeight, html.clientHeight, body ? body.scrollHeight : 0, body ? body.clientHeight : 0);
  var x = -winScroll.scrollLeft + Object(_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element);
  var y = -winScroll.scrollTop;

  if (Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_1__["default"])(body || html).direction === 'rtl') {
    x += Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_4__["max"])(html.clientWidth, body ? body.clientWidth : 0) - width;
  }

  return {
    width: width,
    height: height,
    x: x,
    y: y
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js ***!
  \***************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getHTMLElementScroll; });
function getHTMLElementScroll(element) {
  return {
    scrollLeft: element.scrollLeft,
    scrollTop: element.scrollTop
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getLayoutRect; });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
 // Returns the layout rect of an element relative to its offsetParent. Layout
// means it doesn't take into account transforms.

function getLayoutRect(element) {
  var clientRect = Object(_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element); // Use the clientRect sizes if it's not been transformed.
  // Fixes https://github.com/popperjs/popper-core/issues/1223

  var width = element.offsetWidth;
  var height = element.offsetHeight;

  if (Math.abs(clientRect.width - width) <= 1) {
    width = clientRect.width;
  }

  if (Math.abs(clientRect.height - height) <= 1) {
    height = clientRect.height;
  }

  return {
    x: element.offsetLeft,
    y: element.offsetTop,
    width: width,
    height: height
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js":
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getNodeName; });
function getNodeName(element) {
  return element ? (element.nodeName || '').toLowerCase() : null;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getNodeScroll.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getNodeScroll; });
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _getHTMLElementScroll_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getHTMLElementScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getHTMLElementScroll.js");




function getNodeScroll(node) {
  if (node === Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(node) || !Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_2__["isHTMLElement"])(node)) {
    return Object(_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node);
  } else {
    return Object(_getHTMLElementScroll_js__WEBPACK_IMPORTED_MODULE_3__["default"])(node);
  }
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getOffsetParent; });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _isTableElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./isTableElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/userAgent.js */ "./node_modules/@popperjs/core/lib/utils/userAgent.js");








function getTrueOffsetParent(element) {
  if (!Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(element) || // https://github.com/popperjs/popper-core/issues/837
  Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element).position === 'fixed') {
    return null;
  }

  return element.offsetParent;
} // `.offsetParent` reports `null` for fixed elements, while absolute elements
// return the containing block


function getContainingBlock(element) {
  var isFirefox = /firefox/i.test(Object(_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__["default"])());
  var isIE = /Trident/i.test(Object(_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_6__["default"])());

  if (isIE && Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(element)) {
    // In IE 9, 10 and 11 fixed elements containing block is always established by the viewport
    var elementCss = Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element);

    if (elementCss.position === 'fixed') {
      return null;
    }
  }

  var currentNode = Object(_getParentNode_js__WEBPACK_IMPORTED_MODULE_5__["default"])(element);

  if (Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isShadowRoot"])(currentNode)) {
    currentNode = currentNode.host;
  }

  while (Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(currentNode) && ['html', 'body'].indexOf(Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(currentNode)) < 0) {
    var css = Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(currentNode); // This is non-exhaustive but covers the most common CSS properties that
    // create a containing block.
    // https://developer.mozilla.org/en-US/docs/Web/CSS/Containing_block#identifying_the_containing_block

    if (css.transform !== 'none' || css.perspective !== 'none' || css.contain === 'paint' || ['transform', 'perspective'].indexOf(css.willChange) !== -1 || isFirefox && css.willChange === 'filter' || isFirefox && css.filter && css.filter !== 'none') {
      return currentNode;
    } else {
      currentNode = currentNode.parentNode;
    }
  }

  return null;
} // Gets the closest ancestor positioned element. Handles some edge cases,
// such as table ancestors and cross browser bugs.


function getOffsetParent(element) {
  var window = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var offsetParent = getTrueOffsetParent(element);

  while (offsetParent && Object(_isTableElement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(offsetParent) && Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent).position === 'static') {
    offsetParent = getTrueOffsetParent(offsetParent);
  }

  if (offsetParent && (Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent) === 'html' || Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_1__["default"])(offsetParent) === 'body' && Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_2__["default"])(offsetParent).position === 'static')) {
    return window;
  }

  return offsetParent || getContainingBlock(element) || window;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getParentNode; });
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");



function getParentNode(element) {
  if (Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element) === 'html') {
    return element;
  }

  return (// this is a quicker (but less type safe) way to save quite some bytes from the bundle
    // $FlowFixMe[incompatible-return]
    // $FlowFixMe[prop-missing]
    element.assignedSlot || // step into the shadow DOM of the parent of a slotted node
    element.parentNode || ( // DOM Element detected
    Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_2__["isShadowRoot"])(element) ? element.host : null) || // ShadowRoot detected
    // $FlowFixMe[incompatible-call]: HTMLElement is a Node
    Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element) // fallback

  );
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getScrollParent; });
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _instanceOf_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");




function getScrollParent(node) {
  if (['html', 'body', '#document'].indexOf(Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_2__["default"])(node)) >= 0) {
    // $FlowFixMe[incompatible-return]: assume body is always available
    return node.ownerDocument.body;
  }

  if (Object(_instanceOf_js__WEBPACK_IMPORTED_MODULE_3__["isHTMLElement"])(node) && Object(_isScrollParent_js__WEBPACK_IMPORTED_MODULE_1__["default"])(node)) {
    return node;
  }

  return getScrollParent(Object(_getParentNode_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node));
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getViewportRect.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getViewportRect; });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScrollBarX.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js");
/* harmony import */ var _isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isLayoutViewport.js */ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js");




function getViewportRect(element, strategy) {
  var win = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var html = Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element);
  var visualViewport = win.visualViewport;
  var width = html.clientWidth;
  var height = html.clientHeight;
  var x = 0;
  var y = 0;

  if (visualViewport) {
    width = visualViewport.width;
    height = visualViewport.height;
    var layoutViewport = Object(_isLayoutViewport_js__WEBPACK_IMPORTED_MODULE_3__["default"])();

    if (layoutViewport || !layoutViewport && strategy === 'fixed') {
      x = visualViewport.offsetLeft;
      y = visualViewport.offsetTop;
    }
  }

  return {
    width: width,
    height: height,
    x: x + Object(_getWindowScrollBarX_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element),
    y: y
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js":
/*!****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindow.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getWindow; });
function getWindow(node) {
  if (node == null) {
    return window;
  }

  if (node.toString() !== '[object Window]') {
    var ownerDocument = node.ownerDocument;
    return ownerDocument ? ownerDocument.defaultView || window : window;
  }

  return node;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getWindowScroll; });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");

function getWindowScroll(node) {
  var win = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node);
  var scrollLeft = win.pageXOffset;
  var scrollTop = win.pageYOffset;
  return {
    scrollLeft: scrollLeft,
    scrollTop: scrollTop
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js":
/*!**************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/getWindowScrollBarX.js ***!
  \**************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getWindowScrollBarX; });
/* harmony import */ var _getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _getWindowScroll_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindowScroll.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindowScroll.js");



function getWindowScrollBarX(element) {
  // If <html> has a CSS width greater than the viewport, then this will be
  // incorrect for RTL.
  // Popper 1 is broken in this case and never had a bug report so let's assume
  // it's not an issue. I don't think anyone ever specifies width on <html>
  // anyway.
  // Browsers where the left scrollbar doesn't cause an issue report `0` for
  // this (e.g. Edge 2019, IE11, Safari)
  return Object(_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(Object(_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(element)).left + Object(_getWindowScroll_js__WEBPACK_IMPORTED_MODULE_2__["default"])(element).scrollLeft;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js ***!
  \*****************************************************************/
/*! exports provided: isElement, isHTMLElement, isShadowRoot */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isElement", function() { return isElement; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isHTMLElement", function() { return isHTMLElement; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "isShadowRoot", function() { return isShadowRoot; });
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");


function isElement(node) {
  var OwnElement = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).Element;
  return node instanceof OwnElement || node instanceof Element;
}

function isHTMLElement(node) {
  var OwnElement = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).HTMLElement;
  return node instanceof OwnElement || node instanceof HTMLElement;
}

function isShadowRoot(node) {
  // IE 11 has no ShadowRoot
  if (typeof ShadowRoot === 'undefined') {
    return false;
  }

  var OwnElement = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(node).ShadowRoot;
  return node instanceof OwnElement || node instanceof ShadowRoot;
}



/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isLayoutViewport.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return isLayoutViewport; });
/* harmony import */ var _utils_userAgent_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/userAgent.js */ "./node_modules/@popperjs/core/lib/utils/userAgent.js");

function isLayoutViewport() {
  return !/^((?!chrome|android).)*safari/i.test(Object(_utils_userAgent_js__WEBPACK_IMPORTED_MODULE_0__["default"])());
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return isScrollParent; });
/* harmony import */ var _getComputedStyle_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");

function isScrollParent(element) {
  // Firefox wants us to check `-x` and `-y` variations as well
  var _getComputedStyle = Object(_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element),
      overflow = _getComputedStyle.overflow,
      overflowX = _getComputedStyle.overflowX,
      overflowY = _getComputedStyle.overflowY;

  return /auto|scroll|overlay|hidden/.test(overflow + overflowY + overflowX);
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/isTableElement.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return isTableElement; });
/* harmony import */ var _getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");

function isTableElement(element) {
  return ['table', 'td', 'th'].indexOf(Object(_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) >= 0;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js":
/*!************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/dom-utils/listScrollParents.js ***!
  \************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return listScrollParents; });
/* harmony import */ var _getScrollParent_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getScrollParent.js");
/* harmony import */ var _getParentNode_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getParentNode.js */ "./node_modules/@popperjs/core/lib/dom-utils/getParentNode.js");
/* harmony import */ var _getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _isScrollParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./isScrollParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/isScrollParent.js");




/*
given a DOM element, return the list of all scroll parents, up the list of ancesors
until we get to the top window object. This list is what we attach scroll listeners
to, because if any of these parent elements scroll, we'll need to re-calculate the
reference element's position.
*/

function listScrollParents(element, list) {
  var _element$ownerDocumen;

  if (list === void 0) {
    list = [];
  }

  var scrollParent = Object(_getScrollParent_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element);
  var isBody = scrollParent === ((_element$ownerDocumen = element.ownerDocument) == null ? void 0 : _element$ownerDocumen.body);
  var win = Object(_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(scrollParent);
  var target = isBody ? [win].concat(win.visualViewport || [], Object(_isScrollParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(scrollParent) ? scrollParent : []) : scrollParent;
  var updatedList = list.concat(target);
  return isBody ? updatedList : // $FlowFixMe[incompatible-call]: isBody tells us target will be an HTMLElement here
  updatedList.concat(listScrollParents(Object(_getParentNode_js__WEBPACK_IMPORTED_MODULE_1__["default"])(target)));
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/enums.js":
/*!**************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/enums.js ***!
  \**************************************************/
/*! exports provided: top, bottom, right, left, auto, basePlacements, start, end, clippingParents, viewport, popper, reference, variationPlacements, placements, beforeRead, read, afterRead, beforeMain, main, afterMain, beforeWrite, write, afterWrite, modifierPhases */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "top", function() { return top; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "bottom", function() { return bottom; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "right", function() { return right; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "left", function() { return left; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "auto", function() { return auto; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "basePlacements", function() { return basePlacements; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "start", function() { return start; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "end", function() { return end; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clippingParents", function() { return clippingParents; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "viewport", function() { return viewport; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "popper", function() { return popper; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "reference", function() { return reference; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "variationPlacements", function() { return variationPlacements; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "placements", function() { return placements; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "beforeRead", function() { return beforeRead; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "read", function() { return read; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "afterRead", function() { return afterRead; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "beforeMain", function() { return beforeMain; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "main", function() { return main; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "afterMain", function() { return afterMain; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "beforeWrite", function() { return beforeWrite; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "write", function() { return write; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "afterWrite", function() { return afterWrite; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "modifierPhases", function() { return modifierPhases; });
var top = 'top';
var bottom = 'bottom';
var right = 'right';
var left = 'left';
var auto = 'auto';
var basePlacements = [top, bottom, right, left];
var start = 'start';
var end = 'end';
var clippingParents = 'clippingParents';
var viewport = 'viewport';
var popper = 'popper';
var reference = 'reference';
var variationPlacements = /*#__PURE__*/basePlacements.reduce(function (acc, placement) {
  return acc.concat([placement + "-" + start, placement + "-" + end]);
}, []);
var placements = /*#__PURE__*/[].concat(basePlacements, [auto]).reduce(function (acc, placement) {
  return acc.concat([placement, placement + "-" + start, placement + "-" + end]);
}, []); // modifiers that need to read the DOM

var beforeRead = 'beforeRead';
var read = 'read';
var afterRead = 'afterRead'; // pure-logic modifiers

var beforeMain = 'beforeMain';
var main = 'main';
var afterMain = 'afterMain'; // modifier with the purpose to write to the DOM (or write into a framework state)

var beforeWrite = 'beforeWrite';
var write = 'write';
var afterWrite = 'afterWrite';
var modifierPhases = [beforeRead, read, afterRead, beforeMain, main, afterMain, beforeWrite, write, afterWrite];

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/index.js":
/*!**************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/index.js ***!
  \**************************************************/
/*! exports provided: top, bottom, right, left, auto, basePlacements, start, end, clippingParents, viewport, popper, reference, variationPlacements, placements, beforeRead, read, afterRead, beforeMain, main, afterMain, beforeWrite, write, afterWrite, modifierPhases, applyStyles, arrow, computeStyles, eventListeners, flip, hide, offset, popperOffsets, preventOverflow, popperGenerator, detectOverflow, createPopperBase, createPopper, createPopperLite */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "top", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "bottom", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "right", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "left", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "auto", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["auto"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "basePlacements", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["basePlacements"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "start", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["start"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "end", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["end"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "clippingParents", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["clippingParents"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "viewport", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["viewport"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popper", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["popper"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "reference", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["reference"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "variationPlacements", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["variationPlacements"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "placements", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["placements"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "beforeRead", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["beforeRead"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "read", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["read"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "afterRead", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["afterRead"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "beforeMain", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["beforeMain"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "main", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["main"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "afterMain", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["afterMain"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "beforeWrite", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["beforeWrite"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "write", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["write"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "afterWrite", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["afterWrite"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "modifierPhases", function() { return _enums_js__WEBPACK_IMPORTED_MODULE_0__["modifierPhases"]; });

/* harmony import */ var _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modifiers/index.js */ "./node_modules/@popperjs/core/lib/modifiers/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "applyStyles", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["applyStyles"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "arrow", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["arrow"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "computeStyles", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["computeStyles"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "eventListeners", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["eventListeners"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "flip", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["flip"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "hide", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["hide"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "offset", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["offset"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperOffsets", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["popperOffsets"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "preventOverflow", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_1__["preventOverflow"]; });

/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperGenerator", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_2__["popperGenerator"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "detectOverflow", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_2__["detectOverflow"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "createPopperBase", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_2__["createPopper"]; });

/* harmony import */ var _popper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./popper.js */ "./node_modules/@popperjs/core/lib/popper.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "createPopper", function() { return _popper_js__WEBPACK_IMPORTED_MODULE_3__["createPopper"]; });

/* harmony import */ var _popper_lite_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./popper-lite.js */ "./node_modules/@popperjs/core/lib/popper-lite.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "createPopperLite", function() { return _popper_lite_js__WEBPACK_IMPORTED_MODULE_4__["createPopper"]; });


 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules



/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js":
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/applyStyles.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getNodeName.js */ "./node_modules/@popperjs/core/lib/dom-utils/getNodeName.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");

 // This modifier takes the styles prepared by the `computeStyles` modifier
// and applies them to the HTMLElements such as popper and arrow

function applyStyles(_ref) {
  var state = _ref.state;
  Object.keys(state.elements).forEach(function (name) {
    var style = state.styles[name] || {};
    var attributes = state.attributes[name] || {};
    var element = state.elements[name]; // arrow is optional + virtual elements

    if (!Object(_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__["isHTMLElement"])(element) || !Object(_dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
      return;
    } // Flow doesn't support to extend this property, but it's the most
    // effective way to apply styles to an HTMLElement
    // $FlowFixMe[cannot-write]


    Object.assign(element.style, style);
    Object.keys(attributes).forEach(function (name) {
      var value = attributes[name];

      if (value === false) {
        element.removeAttribute(name);
      } else {
        element.setAttribute(name, value === true ? '' : value);
      }
    });
  });
}

function effect(_ref2) {
  var state = _ref2.state;
  var initialStyles = {
    popper: {
      position: state.options.strategy,
      left: '0',
      top: '0',
      margin: '0'
    },
    arrow: {
      position: 'absolute'
    },
    reference: {}
  };
  Object.assign(state.elements.popper.style, initialStyles.popper);
  state.styles = initialStyles;

  if (state.elements.arrow) {
    Object.assign(state.elements.arrow.style, initialStyles.arrow);
  }

  return function () {
    Object.keys(state.elements).forEach(function (name) {
      var element = state.elements[name];
      var attributes = state.attributes[name] || {};
      var styleProperties = Object.keys(state.styles.hasOwnProperty(name) ? state.styles[name] : initialStyles[name]); // Set all values to an empty string to unset them

      var style = styleProperties.reduce(function (style, property) {
        style[property] = '';
        return style;
      }, {}); // arrow is optional + virtual elements

      if (!Object(_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_1__["isHTMLElement"])(element) || !Object(_dom_utils_getNodeName_js__WEBPACK_IMPORTED_MODULE_0__["default"])(element)) {
        return;
      }

      Object.assign(element.style, style);
      Object.keys(attributes).forEach(function (attribute) {
        element.removeAttribute(attribute);
      });
    });
  };
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'applyStyles',
  enabled: true,
  phase: 'write',
  fn: applyStyles,
  effect: effect,
  requires: ['computeStyles']
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/arrow.js":
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/arrow.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_contains_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/contains.js */ "./node_modules/@popperjs/core/lib/dom-utils/contains.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _utils_within_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/within.js */ "./node_modules/@popperjs/core/lib/utils/within.js");
/* harmony import */ var _utils_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/mergePaddingObject.js */ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js");
/* harmony import */ var _utils_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/expandToHashMap.js */ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");









 // eslint-disable-next-line import/no-unused-modules

var toPaddingObject = function toPaddingObject(padding, state) {
  padding = typeof padding === 'function' ? padding(Object.assign({}, state.rects, {
    placement: state.placement
  })) : padding;
  return Object(_utils_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_6__["default"])(typeof padding !== 'number' ? padding : Object(_utils_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_7__["default"])(padding, _enums_js__WEBPACK_IMPORTED_MODULE_8__["basePlacements"]));
};

function arrow(_ref) {
  var _state$modifiersData$;

  var state = _ref.state,
      name = _ref.name,
      options = _ref.options;
  var arrowElement = state.elements.arrow;
  var popperOffsets = state.modifiersData.popperOffsets;
  var basePlacement = Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(state.placement);
  var axis = Object(_utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(basePlacement);
  var isVertical = [_enums_js__WEBPACK_IMPORTED_MODULE_8__["left"], _enums_js__WEBPACK_IMPORTED_MODULE_8__["right"]].indexOf(basePlacement) >= 0;
  var len = isVertical ? 'height' : 'width';

  if (!arrowElement || !popperOffsets) {
    return;
  }

  var paddingObject = toPaddingObject(options.padding, state);
  var arrowRect = Object(_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_1__["default"])(arrowElement);
  var minProp = axis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_8__["top"] : _enums_js__WEBPACK_IMPORTED_MODULE_8__["left"];
  var maxProp = axis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_8__["bottom"] : _enums_js__WEBPACK_IMPORTED_MODULE_8__["right"];
  var endDiff = state.rects.reference[len] + state.rects.reference[axis] - popperOffsets[axis] - state.rects.popper[len];
  var startDiff = popperOffsets[axis] - state.rects.reference[axis];
  var arrowOffsetParent = Object(_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_3__["default"])(arrowElement);
  var clientSize = arrowOffsetParent ? axis === 'y' ? arrowOffsetParent.clientHeight || 0 : arrowOffsetParent.clientWidth || 0 : 0;
  var centerToReference = endDiff / 2 - startDiff / 2; // Make sure the arrow doesn't overflow the popper if the center point is
  // outside of the popper bounds

  var min = paddingObject[minProp];
  var max = clientSize - arrowRect[len] - paddingObject[maxProp];
  var center = clientSize / 2 - arrowRect[len] / 2 + centerToReference;
  var offset = Object(_utils_within_js__WEBPACK_IMPORTED_MODULE_5__["within"])(min, center, max); // Prevents breaking syntax highlighting...

  var axisProp = axis;
  state.modifiersData[name] = (_state$modifiersData$ = {}, _state$modifiersData$[axisProp] = offset, _state$modifiersData$.centerOffset = offset - center, _state$modifiersData$);
}

function effect(_ref2) {
  var state = _ref2.state,
      options = _ref2.options;
  var _options$element = options.element,
      arrowElement = _options$element === void 0 ? '[data-popper-arrow]' : _options$element;

  if (arrowElement == null) {
    return;
  } // CSS selector


  if (typeof arrowElement === 'string') {
    arrowElement = state.elements.popper.querySelector(arrowElement);

    if (!arrowElement) {
      return;
    }
  }

  if (true) {
    if (!Object(_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_9__["isHTMLElement"])(arrowElement)) {
      console.error(['Popper: "arrow" element must be an HTMLElement (not an SVGElement).', 'To use an SVG arrow, wrap it in an HTMLElement that will be used as', 'the arrow.'].join(' '));
    }
  }

  if (!Object(_dom_utils_contains_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state.elements.popper, arrowElement)) {
    if (true) {
      console.error(['Popper: "arrow" modifier\'s `element` must be a child of the popper', 'element.'].join(' '));
    }

    return;
  }

  state.elements.arrow = arrowElement;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'arrow',
  enabled: true,
  phase: 'main',
  fn: arrow,
  effect: effect,
  requires: ['popperOffsets'],
  requiresIfExists: ['preventOverflow']
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/computeStyles.js ***!
  \********************************************************************/
/*! exports provided: mapToStyles, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "mapToStyles", function() { return mapToStyles; });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
/* harmony import */ var _dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../dom-utils/getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../dom-utils/getComputedStyle.js */ "./node_modules/@popperjs/core/lib/dom-utils/getComputedStyle.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");







 // eslint-disable-next-line import/no-unused-modules

var unsetSides = {
  top: 'auto',
  right: 'auto',
  bottom: 'auto',
  left: 'auto'
}; // Round the offsets to the nearest suitable subpixel based on the DPR.
// Zooming can change the DPR, but it seems to report a value that will
// cleanly divide the values into the appropriate subpixels.

function roundOffsetsByDPR(_ref) {
  var x = _ref.x,
      y = _ref.y;
  var win = window;
  var dpr = win.devicePixelRatio || 1;
  return {
    x: Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_7__["round"])(x * dpr) / dpr || 0,
    y: Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_7__["round"])(y * dpr) / dpr || 0
  };
}

function mapToStyles(_ref2) {
  var _Object$assign2;

  var popper = _ref2.popper,
      popperRect = _ref2.popperRect,
      placement = _ref2.placement,
      variation = _ref2.variation,
      offsets = _ref2.offsets,
      position = _ref2.position,
      gpuAcceleration = _ref2.gpuAcceleration,
      adaptive = _ref2.adaptive,
      roundOffsets = _ref2.roundOffsets,
      isFixed = _ref2.isFixed;
  var _offsets$x = offsets.x,
      x = _offsets$x === void 0 ? 0 : _offsets$x,
      _offsets$y = offsets.y,
      y = _offsets$y === void 0 ? 0 : _offsets$y;

  var _ref3 = typeof roundOffsets === 'function' ? roundOffsets({
    x: x,
    y: y
  }) : {
    x: x,
    y: y
  };

  x = _ref3.x;
  y = _ref3.y;
  var hasX = offsets.hasOwnProperty('x');
  var hasY = offsets.hasOwnProperty('y');
  var sideX = _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"];
  var sideY = _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"];
  var win = window;

  if (adaptive) {
    var offsetParent = Object(_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_1__["default"])(popper);
    var heightProp = 'clientHeight';
    var widthProp = 'clientWidth';

    if (offsetParent === Object(_dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(popper)) {
      offsetParent = Object(_dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_3__["default"])(popper);

      if (Object(_dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__["default"])(offsetParent).position !== 'static' && position === 'absolute') {
        heightProp = 'scrollHeight';
        widthProp = 'scrollWidth';
      }
    } // $FlowFixMe[incompatible-cast]: force type refinement, we compare offsetParent with window above, but Flow doesn't detect it


    offsetParent = offsetParent;

    if (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"] || (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"] || placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"]) && variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__["end"]) {
      sideY = _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"];
      var offsetY = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.height : // $FlowFixMe[prop-missing]
      offsetParent[heightProp];
      y -= offsetY - popperRect.height;
      y *= gpuAcceleration ? 1 : -1;
    }

    if (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"] || (placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"] || placement === _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"]) && variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__["end"]) {
      sideX = _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"];
      var offsetX = isFixed && offsetParent === win && win.visualViewport ? win.visualViewport.width : // $FlowFixMe[prop-missing]
      offsetParent[widthProp];
      x -= offsetX - popperRect.width;
      x *= gpuAcceleration ? 1 : -1;
    }
  }

  var commonStyles = Object.assign({
    position: position
  }, adaptive && unsetSides);

  var _ref4 = roundOffsets === true ? roundOffsetsByDPR({
    x: x,
    y: y
  }) : {
    x: x,
    y: y
  };

  x = _ref4.x;
  y = _ref4.y;

  if (gpuAcceleration) {
    var _Object$assign;

    return Object.assign({}, commonStyles, (_Object$assign = {}, _Object$assign[sideY] = hasY ? '0' : '', _Object$assign[sideX] = hasX ? '0' : '', _Object$assign.transform = (win.devicePixelRatio || 1) <= 1 ? "translate(" + x + "px, " + y + "px)" : "translate3d(" + x + "px, " + y + "px, 0)", _Object$assign));
  }

  return Object.assign({}, commonStyles, (_Object$assign2 = {}, _Object$assign2[sideY] = hasY ? y + "px" : '', _Object$assign2[sideX] = hasX ? x + "px" : '', _Object$assign2.transform = '', _Object$assign2));
}

function computeStyles(_ref5) {
  var state = _ref5.state,
      options = _ref5.options;
  var _options$gpuAccelerat = options.gpuAcceleration,
      gpuAcceleration = _options$gpuAccelerat === void 0 ? true : _options$gpuAccelerat,
      _options$adaptive = options.adaptive,
      adaptive = _options$adaptive === void 0 ? true : _options$adaptive,
      _options$roundOffsets = options.roundOffsets,
      roundOffsets = _options$roundOffsets === void 0 ? true : _options$roundOffsets;

  if (true) {
    var transitionProperty = Object(_dom_utils_getComputedStyle_js__WEBPACK_IMPORTED_MODULE_4__["default"])(state.elements.popper).transitionProperty || '';

    if (adaptive && ['transform', 'top', 'right', 'bottom', 'left'].some(function (property) {
      return transitionProperty.indexOf(property) >= 0;
    })) {
      console.warn(['Popper: Detected CSS transitions on at least one of the following', 'CSS properties: "transform", "top", "right", "bottom", "left".', '\n\n', 'Disable the "computeStyles" modifier\'s `adaptive` option to allow', 'for smooth transitions, or remove these properties from the CSS', 'transition declaration on the popper element if only transitioning', 'opacity or background-color for example.', '\n\n', 'We recommend using the popper element as a wrapper around an inner', 'element that can have any CSS property transitioned for animations.'].join(' '));
    }
  }

  var commonStyles = {
    placement: Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_5__["default"])(state.placement),
    variation: Object(_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__["default"])(state.placement),
    popper: state.elements.popper,
    popperRect: state.rects.popper,
    gpuAcceleration: gpuAcceleration,
    isFixed: state.options.strategy === 'fixed'
  };

  if (state.modifiersData.popperOffsets != null) {
    state.styles.popper = Object.assign({}, state.styles.popper, mapToStyles(Object.assign({}, commonStyles, {
      offsets: state.modifiersData.popperOffsets,
      position: state.options.strategy,
      adaptive: adaptive,
      roundOffsets: roundOffsets
    })));
  }

  if (state.modifiersData.arrow != null) {
    state.styles.arrow = Object.assign({}, state.styles.arrow, mapToStyles(Object.assign({}, commonStyles, {
      offsets: state.modifiersData.arrow,
      position: 'absolute',
      adaptive: false,
      roundOffsets: roundOffsets
    })));
  }

  state.attributes.popper = Object.assign({}, state.attributes.popper, {
    'data-popper-placement': state.placement
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'computeStyles',
  enabled: true,
  phase: 'beforeWrite',
  fn: computeStyles,
  data: {}
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/eventListeners.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getWindow.js */ "./node_modules/@popperjs/core/lib/dom-utils/getWindow.js");
 // eslint-disable-next-line import/no-unused-modules

var passive = {
  passive: true
};

function effect(_ref) {
  var state = _ref.state,
      instance = _ref.instance,
      options = _ref.options;
  var _options$scroll = options.scroll,
      scroll = _options$scroll === void 0 ? true : _options$scroll,
      _options$resize = options.resize,
      resize = _options$resize === void 0 ? true : _options$resize;
  var window = Object(_dom_utils_getWindow_js__WEBPACK_IMPORTED_MODULE_0__["default"])(state.elements.popper);
  var scrollParents = [].concat(state.scrollParents.reference, state.scrollParents.popper);

  if (scroll) {
    scrollParents.forEach(function (scrollParent) {
      scrollParent.addEventListener('scroll', instance.update, passive);
    });
  }

  if (resize) {
    window.addEventListener('resize', instance.update, passive);
  }

  return function () {
    if (scroll) {
      scrollParents.forEach(function (scrollParent) {
        scrollParent.removeEventListener('scroll', instance.update, passive);
      });
    }

    if (resize) {
      window.removeEventListener('resize', instance.update, passive);
    }
  };
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'eventListeners',
  enabled: true,
  phase: 'write',
  fn: function fn() {},
  effect: effect,
  data: {}
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/flip.js":
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/flip.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getOppositePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/getOppositeVariationPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _utils_computeAutoPlacement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/computeAutoPlacement.js */ "./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");






 // eslint-disable-next-line import/no-unused-modules

function getExpandedFallbackPlacements(placement) {
  if (Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__["auto"]) {
    return [];
  }

  var oppositePlacement = Object(_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  return [Object(_utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(placement), oppositePlacement, Object(_utils_getOppositeVariationPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(oppositePlacement)];
}

function flip(_ref) {
  var state = _ref.state,
      options = _ref.options,
      name = _ref.name;

  if (state.modifiersData[name]._skip) {
    return;
  }

  var _options$mainAxis = options.mainAxis,
      checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis,
      _options$altAxis = options.altAxis,
      checkAltAxis = _options$altAxis === void 0 ? true : _options$altAxis,
      specifiedFallbackPlacements = options.fallbackPlacements,
      padding = options.padding,
      boundary = options.boundary,
      rootBoundary = options.rootBoundary,
      altBoundary = options.altBoundary,
      _options$flipVariatio = options.flipVariations,
      flipVariations = _options$flipVariatio === void 0 ? true : _options$flipVariatio,
      allowedAutoPlacements = options.allowedAutoPlacements;
  var preferredPlacement = state.options.placement;
  var basePlacement = Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(preferredPlacement);
  var isBasePlacement = basePlacement === preferredPlacement;
  var fallbackPlacements = specifiedFallbackPlacements || (isBasePlacement || !flipVariations ? [Object(_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(preferredPlacement)] : getExpandedFallbackPlacements(preferredPlacement));
  var placements = [preferredPlacement].concat(fallbackPlacements).reduce(function (acc, placement) {
    return acc.concat(Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__["auto"] ? Object(_utils_computeAutoPlacement_js__WEBPACK_IMPORTED_MODULE_4__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      padding: padding,
      flipVariations: flipVariations,
      allowedAutoPlacements: allowedAutoPlacements
    }) : placement);
  }, []);
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var checksMap = new Map();
  var makeFallbackChecks = true;
  var firstFittingPlacement = placements[0];

  for (var i = 0; i < placements.length; i++) {
    var placement = placements[i];

    var _basePlacement = Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement);

    var isStartVariation = Object(_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_6__["default"])(placement) === _enums_js__WEBPACK_IMPORTED_MODULE_5__["start"];
    var isVertical = [_enums_js__WEBPACK_IMPORTED_MODULE_5__["top"], _enums_js__WEBPACK_IMPORTED_MODULE_5__["bottom"]].indexOf(_basePlacement) >= 0;
    var len = isVertical ? 'width' : 'height';
    var overflow = Object(_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_3__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      altBoundary: altBoundary,
      padding: padding
    });
    var mainVariationSide = isVertical ? isStartVariation ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["right"] : _enums_js__WEBPACK_IMPORTED_MODULE_5__["left"] : isStartVariation ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["bottom"] : _enums_js__WEBPACK_IMPORTED_MODULE_5__["top"];

    if (referenceRect[len] > popperRect[len]) {
      mainVariationSide = Object(_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(mainVariationSide);
    }

    var altVariationSide = Object(_utils_getOppositePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(mainVariationSide);
    var checks = [];

    if (checkMainAxis) {
      checks.push(overflow[_basePlacement] <= 0);
    }

    if (checkAltAxis) {
      checks.push(overflow[mainVariationSide] <= 0, overflow[altVariationSide] <= 0);
    }

    if (checks.every(function (check) {
      return check;
    })) {
      firstFittingPlacement = placement;
      makeFallbackChecks = false;
      break;
    }

    checksMap.set(placement, checks);
  }

  if (makeFallbackChecks) {
    // `2` may be desired in some cases – research later
    var numberOfChecks = flipVariations ? 3 : 1;

    var _loop = function _loop(_i) {
      var fittingPlacement = placements.find(function (placement) {
        var checks = checksMap.get(placement);

        if (checks) {
          return checks.slice(0, _i).every(function (check) {
            return check;
          });
        }
      });

      if (fittingPlacement) {
        firstFittingPlacement = fittingPlacement;
        return "break";
      }
    };

    for (var _i = numberOfChecks; _i > 0; _i--) {
      var _ret = _loop(_i);

      if (_ret === "break") break;
    }
  }

  if (state.placement !== firstFittingPlacement) {
    state.modifiersData[name]._skip = true;
    state.placement = firstFittingPlacement;
    state.reset = true;
  }
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'flip',
  enabled: true,
  phase: 'main',
  fn: flip,
  requiresIfExists: ['offset'],
  data: {
    _skip: false
  }
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/hide.js":
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/hide.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");



function getSideOffsets(overflow, rect, preventedOffsets) {
  if (preventedOffsets === void 0) {
    preventedOffsets = {
      x: 0,
      y: 0
    };
  }

  return {
    top: overflow.top - rect.height - preventedOffsets.y,
    right: overflow.right - rect.width + preventedOffsets.x,
    bottom: overflow.bottom - rect.height + preventedOffsets.y,
    left: overflow.left - rect.width - preventedOffsets.x
  };
}

function isAnySideFullyClipped(overflow) {
  return [_enums_js__WEBPACK_IMPORTED_MODULE_0__["top"], _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"], _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"], _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"]].some(function (side) {
    return overflow[side] >= 0;
  });
}

function hide(_ref) {
  var state = _ref.state,
      name = _ref.name;
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var preventedOffsets = state.modifiersData.preventOverflow;
  var referenceOverflow = Object(_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state, {
    elementContext: 'reference'
  });
  var popperAltOverflow = Object(_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state, {
    altBoundary: true
  });
  var referenceClippingOffsets = getSideOffsets(referenceOverflow, referenceRect);
  var popperEscapeOffsets = getSideOffsets(popperAltOverflow, popperRect, preventedOffsets);
  var isReferenceHidden = isAnySideFullyClipped(referenceClippingOffsets);
  var hasPopperEscaped = isAnySideFullyClipped(popperEscapeOffsets);
  state.modifiersData[name] = {
    referenceClippingOffsets: referenceClippingOffsets,
    popperEscapeOffsets: popperEscapeOffsets,
    isReferenceHidden: isReferenceHidden,
    hasPopperEscaped: hasPopperEscaped
  };
  state.attributes.popper = Object.assign({}, state.attributes.popper, {
    'data-popper-reference-hidden': isReferenceHidden,
    'data-popper-escaped': hasPopperEscaped
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'hide',
  enabled: true,
  phase: 'main',
  requiresIfExists: ['preventOverflow'],
  fn: hide
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/index.js":
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/index.js ***!
  \************************************************************/
/*! exports provided: applyStyles, arrow, computeStyles, eventListeners, flip, hide, offset, popperOffsets, preventOverflow */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _applyStyles_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "applyStyles", function() { return _applyStyles_js__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _arrow_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./arrow.js */ "./node_modules/@popperjs/core/lib/modifiers/arrow.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "arrow", function() { return _arrow_js__WEBPACK_IMPORTED_MODULE_1__["default"]; });

/* harmony import */ var _computeStyles_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "computeStyles", function() { return _computeStyles_js__WEBPACK_IMPORTED_MODULE_2__["default"]; });

/* harmony import */ var _eventListeners_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "eventListeners", function() { return _eventListeners_js__WEBPACK_IMPORTED_MODULE_3__["default"]; });

/* harmony import */ var _flip_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./flip.js */ "./node_modules/@popperjs/core/lib/modifiers/flip.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "flip", function() { return _flip_js__WEBPACK_IMPORTED_MODULE_4__["default"]; });

/* harmony import */ var _hide_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./hide.js */ "./node_modules/@popperjs/core/lib/modifiers/hide.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "hide", function() { return _hide_js__WEBPACK_IMPORTED_MODULE_5__["default"]; });

/* harmony import */ var _offset_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./offset.js */ "./node_modules/@popperjs/core/lib/modifiers/offset.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "offset", function() { return _offset_js__WEBPACK_IMPORTED_MODULE_6__["default"]; });

/* harmony import */ var _popperOffsets_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperOffsets", function() { return _popperOffsets_js__WEBPACK_IMPORTED_MODULE_7__["default"]; });

/* harmony import */ var _preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./preventOverflow.js */ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "preventOverflow", function() { return _preventOverflow_js__WEBPACK_IMPORTED_MODULE_8__["default"]; });











/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/offset.js":
/*!*************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/offset.js ***!
  \*************************************************************/
/*! exports provided: distanceAndSkiddingToXY, default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "distanceAndSkiddingToXY", function() { return distanceAndSkiddingToXY; });
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");

 // eslint-disable-next-line import/no-unused-modules

function distanceAndSkiddingToXY(placement, rects, offset) {
  var basePlacement = Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  var invertDistance = [_enums_js__WEBPACK_IMPORTED_MODULE_1__["left"], _enums_js__WEBPACK_IMPORTED_MODULE_1__["top"]].indexOf(basePlacement) >= 0 ? -1 : 1;

  var _ref = typeof offset === 'function' ? offset(Object.assign({}, rects, {
    placement: placement
  })) : offset,
      skidding = _ref[0],
      distance = _ref[1];

  skidding = skidding || 0;
  distance = (distance || 0) * invertDistance;
  return [_enums_js__WEBPACK_IMPORTED_MODULE_1__["left"], _enums_js__WEBPACK_IMPORTED_MODULE_1__["right"]].indexOf(basePlacement) >= 0 ? {
    x: distance,
    y: skidding
  } : {
    x: skidding,
    y: distance
  };
}

function offset(_ref2) {
  var state = _ref2.state,
      options = _ref2.options,
      name = _ref2.name;
  var _options$offset = options.offset,
      offset = _options$offset === void 0 ? [0, 0] : _options$offset;
  var data = _enums_js__WEBPACK_IMPORTED_MODULE_1__["placements"].reduce(function (acc, placement) {
    acc[placement] = distanceAndSkiddingToXY(placement, state.rects, offset);
    return acc;
  }, {});
  var _data$state$placement = data[state.placement],
      x = _data$state$placement.x,
      y = _data$state$placement.y;

  if (state.modifiersData.popperOffsets != null) {
    state.modifiersData.popperOffsets.x += x;
    state.modifiersData.popperOffsets.y += y;
  }

  state.modifiersData[name] = data;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'offset',
  enabled: true,
  phase: 'main',
  requires: ['popperOffsets'],
  fn: offset
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_computeOffsets_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils/computeOffsets.js */ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js");


function popperOffsets(_ref) {
  var state = _ref.state,
      name = _ref.name;
  // Offsets are the actual position the popper needs to have to be
  // properly positioned near its reference element
  // This is the most basic placement, and will be adjusted by
  // the modifiers in the next step
  state.modifiersData[name] = Object(_utils_computeOffsets_js__WEBPACK_IMPORTED_MODULE_0__["default"])({
    reference: state.rects.reference,
    element: state.rects.popper,
    strategy: 'absolute',
    placement: state.placement
  });
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'popperOffsets',
  enabled: true,
  phase: 'read',
  fn: popperOffsets,
  data: {}
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js":
/*!**********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js ***!
  \**********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _utils_getAltAxis_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/getAltAxis.js */ "./node_modules/@popperjs/core/lib/utils/getAltAxis.js");
/* harmony import */ var _utils_within_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/within.js */ "./node_modules/@popperjs/core/lib/utils/within.js");
/* harmony import */ var _dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../dom-utils/getLayoutRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getLayoutRect.js");
/* harmony import */ var _dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../dom-utils/getOffsetParent.js */ "./node_modules/@popperjs/core/lib/dom-utils/getOffsetParent.js");
/* harmony import */ var _utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _utils_getVariation_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _utils_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../utils/getFreshSideObject.js */ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js");
/* harmony import */ var _utils_math_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");












function preventOverflow(_ref) {
  var state = _ref.state,
      options = _ref.options,
      name = _ref.name;
  var _options$mainAxis = options.mainAxis,
      checkMainAxis = _options$mainAxis === void 0 ? true : _options$mainAxis,
      _options$altAxis = options.altAxis,
      checkAltAxis = _options$altAxis === void 0 ? false : _options$altAxis,
      boundary = options.boundary,
      rootBoundary = options.rootBoundary,
      altBoundary = options.altBoundary,
      padding = options.padding,
      _options$tether = options.tether,
      tether = _options$tether === void 0 ? true : _options$tether,
      _options$tetherOffset = options.tetherOffset,
      tetherOffset = _options$tetherOffset === void 0 ? 0 : _options$tetherOffset;
  var overflow = Object(_utils_detectOverflow_js__WEBPACK_IMPORTED_MODULE_7__["default"])(state, {
    boundary: boundary,
    rootBoundary: rootBoundary,
    padding: padding,
    altBoundary: altBoundary
  });
  var basePlacement = Object(_utils_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state.placement);
  var variation = Object(_utils_getVariation_js__WEBPACK_IMPORTED_MODULE_8__["default"])(state.placement);
  var isBasePlacement = !variation;
  var mainAxis = Object(_utils_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(basePlacement);
  var altAxis = Object(_utils_getAltAxis_js__WEBPACK_IMPORTED_MODULE_3__["default"])(mainAxis);
  var popperOffsets = state.modifiersData.popperOffsets;
  var referenceRect = state.rects.reference;
  var popperRect = state.rects.popper;
  var tetherOffsetValue = typeof tetherOffset === 'function' ? tetherOffset(Object.assign({}, state.rects, {
    placement: state.placement
  })) : tetherOffset;
  var normalizedTetherOffsetValue = typeof tetherOffsetValue === 'number' ? {
    mainAxis: tetherOffsetValue,
    altAxis: tetherOffsetValue
  } : Object.assign({
    mainAxis: 0,
    altAxis: 0
  }, tetherOffsetValue);
  var offsetModifierState = state.modifiersData.offset ? state.modifiersData.offset[state.placement] : null;
  var data = {
    x: 0,
    y: 0
  };

  if (!popperOffsets) {
    return;
  }

  if (checkMainAxis) {
    var _offsetModifierState$;

    var mainSide = mainAxis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"] : _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"];
    var altSide = mainAxis === 'y' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"] : _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"];
    var len = mainAxis === 'y' ? 'height' : 'width';
    var offset = popperOffsets[mainAxis];
    var min = offset + overflow[mainSide];
    var max = offset - overflow[altSide];
    var additive = tether ? -popperRect[len] / 2 : 0;
    var minLen = variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__["start"] ? referenceRect[len] : popperRect[len];
    var maxLen = variation === _enums_js__WEBPACK_IMPORTED_MODULE_0__["start"] ? -popperRect[len] : -referenceRect[len]; // We need to include the arrow in the calculation so the arrow doesn't go
    // outside the reference bounds

    var arrowElement = state.elements.arrow;
    var arrowRect = tether && arrowElement ? Object(_dom_utils_getLayoutRect_js__WEBPACK_IMPORTED_MODULE_5__["default"])(arrowElement) : {
      width: 0,
      height: 0
    };
    var arrowPaddingObject = state.modifiersData['arrow#persistent'] ? state.modifiersData['arrow#persistent'].padding : Object(_utils_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_9__["default"])();
    var arrowPaddingMin = arrowPaddingObject[mainSide];
    var arrowPaddingMax = arrowPaddingObject[altSide]; // If the reference length is smaller than the arrow length, we don't want
    // to include its full size in the calculation. If the reference is small
    // and near the edge of a boundary, the popper can overflow even if the
    // reference is not overflowing as well (e.g. virtual elements with no
    // width or height)

    var arrowLen = Object(_utils_within_js__WEBPACK_IMPORTED_MODULE_4__["within"])(0, referenceRect[len], arrowRect[len]);
    var minOffset = isBasePlacement ? referenceRect[len] / 2 - additive - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis : minLen - arrowLen - arrowPaddingMin - normalizedTetherOffsetValue.mainAxis;
    var maxOffset = isBasePlacement ? -referenceRect[len] / 2 + additive + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis : maxLen + arrowLen + arrowPaddingMax + normalizedTetherOffsetValue.mainAxis;
    var arrowOffsetParent = state.elements.arrow && Object(_dom_utils_getOffsetParent_js__WEBPACK_IMPORTED_MODULE_6__["default"])(state.elements.arrow);
    var clientOffset = arrowOffsetParent ? mainAxis === 'y' ? arrowOffsetParent.clientTop || 0 : arrowOffsetParent.clientLeft || 0 : 0;
    var offsetModifierValue = (_offsetModifierState$ = offsetModifierState == null ? void 0 : offsetModifierState[mainAxis]) != null ? _offsetModifierState$ : 0;
    var tetherMin = offset + minOffset - offsetModifierValue - clientOffset;
    var tetherMax = offset + maxOffset - offsetModifierValue;
    var preventedOffset = Object(_utils_within_js__WEBPACK_IMPORTED_MODULE_4__["within"])(tether ? Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_10__["min"])(min, tetherMin) : min, offset, tether ? Object(_utils_math_js__WEBPACK_IMPORTED_MODULE_10__["max"])(max, tetherMax) : max);
    popperOffsets[mainAxis] = preventedOffset;
    data[mainAxis] = preventedOffset - offset;
  }

  if (checkAltAxis) {
    var _offsetModifierState$2;

    var _mainSide = mainAxis === 'x' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__["top"] : _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"];

    var _altSide = mainAxis === 'x' ? _enums_js__WEBPACK_IMPORTED_MODULE_0__["bottom"] : _enums_js__WEBPACK_IMPORTED_MODULE_0__["right"];

    var _offset = popperOffsets[altAxis];

    var _len = altAxis === 'y' ? 'height' : 'width';

    var _min = _offset + overflow[_mainSide];

    var _max = _offset - overflow[_altSide];

    var isOriginSide = [_enums_js__WEBPACK_IMPORTED_MODULE_0__["top"], _enums_js__WEBPACK_IMPORTED_MODULE_0__["left"]].indexOf(basePlacement) !== -1;

    var _offsetModifierValue = (_offsetModifierState$2 = offsetModifierState == null ? void 0 : offsetModifierState[altAxis]) != null ? _offsetModifierState$2 : 0;

    var _tetherMin = isOriginSide ? _min : _offset - referenceRect[_len] - popperRect[_len] - _offsetModifierValue + normalizedTetherOffsetValue.altAxis;

    var _tetherMax = isOriginSide ? _offset + referenceRect[_len] + popperRect[_len] - _offsetModifierValue - normalizedTetherOffsetValue.altAxis : _max;

    var _preventedOffset = tether && isOriginSide ? Object(_utils_within_js__WEBPACK_IMPORTED_MODULE_4__["withinMaxClamp"])(_tetherMin, _offset, _tetherMax) : Object(_utils_within_js__WEBPACK_IMPORTED_MODULE_4__["within"])(tether ? _tetherMin : _min, _offset, tether ? _tetherMax : _max);

    popperOffsets[altAxis] = _preventedOffset;
    data[altAxis] = _preventedOffset - _offset;
  }

  state.modifiersData[name] = data;
} // eslint-disable-next-line import/no-unused-modules


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'preventOverflow',
  enabled: true,
  phase: 'main',
  fn: preventOverflow,
  requiresIfExists: ['offset']
});

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/popper-lite.js":
/*!********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/popper-lite.js ***!
  \********************************************************/
/*! exports provided: createPopper, popperGenerator, defaultModifiers, detectOverflow */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createPopper", function() { return createPopper; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaultModifiers", function() { return defaultModifiers; });
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperGenerator", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_0__["popperGenerator"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "detectOverflow", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_0__["detectOverflow"]; });

/* harmony import */ var _modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modifiers/eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony import */ var _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modifiers/popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony import */ var _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modifiers/computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony import */ var _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./modifiers/applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");





var defaultModifiers = [_modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_1__["default"], _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_2__["default"], _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_3__["default"], _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_4__["default"]];
var createPopper = /*#__PURE__*/Object(_createPopper_js__WEBPACK_IMPORTED_MODULE_0__["popperGenerator"])({
  defaultModifiers: defaultModifiers
}); // eslint-disable-next-line import/no-unused-modules



/***/ }),

/***/ "./node_modules/@popperjs/core/lib/popper.js":
/*!***************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/popper.js ***!
  \***************************************************/
/*! exports provided: createPopper, popperGenerator, defaultModifiers, detectOverflow, createPopperLite, applyStyles, arrow, computeStyles, eventListeners, flip, hide, offset, popperOffsets, preventOverflow */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createPopper", function() { return createPopper; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "defaultModifiers", function() { return defaultModifiers; });
/* harmony import */ var _createPopper_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./createPopper.js */ "./node_modules/@popperjs/core/lib/createPopper.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperGenerator", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_0__["popperGenerator"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "detectOverflow", function() { return _createPopper_js__WEBPACK_IMPORTED_MODULE_0__["detectOverflow"]; });

/* harmony import */ var _modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modifiers/eventListeners.js */ "./node_modules/@popperjs/core/lib/modifiers/eventListeners.js");
/* harmony import */ var _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modifiers/popperOffsets.js */ "./node_modules/@popperjs/core/lib/modifiers/popperOffsets.js");
/* harmony import */ var _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modifiers/computeStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/computeStyles.js");
/* harmony import */ var _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./modifiers/applyStyles.js */ "./node_modules/@popperjs/core/lib/modifiers/applyStyles.js");
/* harmony import */ var _modifiers_offset_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./modifiers/offset.js */ "./node_modules/@popperjs/core/lib/modifiers/offset.js");
/* harmony import */ var _modifiers_flip_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./modifiers/flip.js */ "./node_modules/@popperjs/core/lib/modifiers/flip.js");
/* harmony import */ var _modifiers_preventOverflow_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./modifiers/preventOverflow.js */ "./node_modules/@popperjs/core/lib/modifiers/preventOverflow.js");
/* harmony import */ var _modifiers_arrow_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./modifiers/arrow.js */ "./node_modules/@popperjs/core/lib/modifiers/arrow.js");
/* harmony import */ var _modifiers_hide_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./modifiers/hide.js */ "./node_modules/@popperjs/core/lib/modifiers/hide.js");
/* harmony import */ var _popper_lite_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./popper-lite.js */ "./node_modules/@popperjs/core/lib/popper-lite.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "createPopperLite", function() { return _popper_lite_js__WEBPACK_IMPORTED_MODULE_10__["createPopper"]; });

/* harmony import */ var _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./modifiers/index.js */ "./node_modules/@popperjs/core/lib/modifiers/index.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "applyStyles", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["applyStyles"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "arrow", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["arrow"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "computeStyles", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["computeStyles"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "eventListeners", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["eventListeners"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "flip", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["flip"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "hide", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["hide"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "offset", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["offset"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "popperOffsets", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["popperOffsets"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "preventOverflow", function() { return _modifiers_index_js__WEBPACK_IMPORTED_MODULE_11__["preventOverflow"]; });











var defaultModifiers = [_modifiers_eventListeners_js__WEBPACK_IMPORTED_MODULE_1__["default"], _modifiers_popperOffsets_js__WEBPACK_IMPORTED_MODULE_2__["default"], _modifiers_computeStyles_js__WEBPACK_IMPORTED_MODULE_3__["default"], _modifiers_applyStyles_js__WEBPACK_IMPORTED_MODULE_4__["default"], _modifiers_offset_js__WEBPACK_IMPORTED_MODULE_5__["default"], _modifiers_flip_js__WEBPACK_IMPORTED_MODULE_6__["default"], _modifiers_preventOverflow_js__WEBPACK_IMPORTED_MODULE_7__["default"], _modifiers_arrow_js__WEBPACK_IMPORTED_MODULE_8__["default"], _modifiers_hide_js__WEBPACK_IMPORTED_MODULE_9__["default"]];
var createPopper = /*#__PURE__*/Object(_createPopper_js__WEBPACK_IMPORTED_MODULE_0__["popperGenerator"])({
  defaultModifiers: defaultModifiers
}); // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules

 // eslint-disable-next-line import/no-unused-modules



/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/computeAutoPlacement.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return computeAutoPlacement; });
/* harmony import */ var _getVariation_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _detectOverflow_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./detectOverflow.js */ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js");
/* harmony import */ var _getBasePlacement_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");




function computeAutoPlacement(state, options) {
  if (options === void 0) {
    options = {};
  }

  var _options = options,
      placement = _options.placement,
      boundary = _options.boundary,
      rootBoundary = _options.rootBoundary,
      padding = _options.padding,
      flipVariations = _options.flipVariations,
      _options$allowedAutoP = _options.allowedAutoPlacements,
      allowedAutoPlacements = _options$allowedAutoP === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_1__["placements"] : _options$allowedAutoP;
  var variation = Object(_getVariation_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement);
  var placements = variation ? flipVariations ? _enums_js__WEBPACK_IMPORTED_MODULE_1__["variationPlacements"] : _enums_js__WEBPACK_IMPORTED_MODULE_1__["variationPlacements"].filter(function (placement) {
    return Object(_getVariation_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement) === variation;
  }) : _enums_js__WEBPACK_IMPORTED_MODULE_1__["basePlacements"];
  var allowedPlacements = placements.filter(function (placement) {
    return allowedAutoPlacements.indexOf(placement) >= 0;
  });

  if (allowedPlacements.length === 0) {
    allowedPlacements = placements;

    if (true) {
      console.error(['Popper: The `allowedAutoPlacements` option did not allow any', 'placements. Ensure the `placement` option matches the variation', 'of the allowed placements.', 'For example, "auto" cannot be used to allow "bottom-start".', 'Use "auto-start" instead.'].join(' '));
    }
  } // $FlowFixMe[incompatible-type]: Flow seems to have problems with two array unions...


  var overflows = allowedPlacements.reduce(function (acc, placement) {
    acc[placement] = Object(_detectOverflow_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state, {
      placement: placement,
      boundary: boundary,
      rootBoundary: rootBoundary,
      padding: padding
    })[Object(_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_3__["default"])(placement)];
    return acc;
  }, {});
  return Object.keys(overflows).sort(function (a, b) {
    return overflows[a] - overflows[b];
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/computeOffsets.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return computeOffsets; });
/* harmony import */ var _getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getBasePlacement.js */ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js");
/* harmony import */ var _getVariation_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./getVariation.js */ "./node_modules/@popperjs/core/lib/utils/getVariation.js");
/* harmony import */ var _getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./getMainAxisFromPlacement.js */ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");




function computeOffsets(_ref) {
  var reference = _ref.reference,
      element = _ref.element,
      placement = _ref.placement;
  var basePlacement = placement ? Object(_getBasePlacement_js__WEBPACK_IMPORTED_MODULE_0__["default"])(placement) : null;
  var variation = placement ? Object(_getVariation_js__WEBPACK_IMPORTED_MODULE_1__["default"])(placement) : null;
  var commonX = reference.x + reference.width / 2 - element.width / 2;
  var commonY = reference.y + reference.height / 2 - element.height / 2;
  var offsets;

  switch (basePlacement) {
    case _enums_js__WEBPACK_IMPORTED_MODULE_3__["top"]:
      offsets = {
        x: commonX,
        y: reference.y - element.height
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__["bottom"]:
      offsets = {
        x: commonX,
        y: reference.y + reference.height
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__["right"]:
      offsets = {
        x: reference.x + reference.width,
        y: commonY
      };
      break;

    case _enums_js__WEBPACK_IMPORTED_MODULE_3__["left"]:
      offsets = {
        x: reference.x - element.width,
        y: commonY
      };
      break;

    default:
      offsets = {
        x: reference.x,
        y: reference.y
      };
  }

  var mainAxis = basePlacement ? Object(_getMainAxisFromPlacement_js__WEBPACK_IMPORTED_MODULE_2__["default"])(basePlacement) : null;

  if (mainAxis != null) {
    var len = mainAxis === 'y' ? 'height' : 'width';

    switch (variation) {
      case _enums_js__WEBPACK_IMPORTED_MODULE_3__["start"]:
        offsets[mainAxis] = offsets[mainAxis] - (reference[len] / 2 - element[len] / 2);
        break;

      case _enums_js__WEBPACK_IMPORTED_MODULE_3__["end"]:
        offsets[mainAxis] = offsets[mainAxis] + (reference[len] / 2 - element[len] / 2);
        break;

      default:
    }
  }

  return offsets;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/debounce.js":
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/debounce.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return debounce; });
function debounce(fn) {
  var pending;
  return function () {
    if (!pending) {
      pending = new Promise(function (resolve) {
        Promise.resolve().then(function () {
          pending = undefined;
          resolve(fn());
        });
      });
    }

    return pending;
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/detectOverflow.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/detectOverflow.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return detectOverflow; });
/* harmony import */ var _dom_utils_getClippingRect_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../dom-utils/getClippingRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getClippingRect.js");
/* harmony import */ var _dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../dom-utils/getDocumentElement.js */ "./node_modules/@popperjs/core/lib/dom-utils/getDocumentElement.js");
/* harmony import */ var _dom_utils_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../dom-utils/getBoundingClientRect.js */ "./node_modules/@popperjs/core/lib/dom-utils/getBoundingClientRect.js");
/* harmony import */ var _computeOffsets_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./computeOffsets.js */ "./node_modules/@popperjs/core/lib/utils/computeOffsets.js");
/* harmony import */ var _rectToClientRect_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./rectToClientRect.js */ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
/* harmony import */ var _dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../dom-utils/instanceOf.js */ "./node_modules/@popperjs/core/lib/dom-utils/instanceOf.js");
/* harmony import */ var _mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./mergePaddingObject.js */ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js");
/* harmony import */ var _expandToHashMap_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./expandToHashMap.js */ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js");








 // eslint-disable-next-line import/no-unused-modules

function detectOverflow(state, options) {
  if (options === void 0) {
    options = {};
  }

  var _options = options,
      _options$placement = _options.placement,
      placement = _options$placement === void 0 ? state.placement : _options$placement,
      _options$strategy = _options.strategy,
      strategy = _options$strategy === void 0 ? state.strategy : _options$strategy,
      _options$boundary = _options.boundary,
      boundary = _options$boundary === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["clippingParents"] : _options$boundary,
      _options$rootBoundary = _options.rootBoundary,
      rootBoundary = _options$rootBoundary === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["viewport"] : _options$rootBoundary,
      _options$elementConte = _options.elementContext,
      elementContext = _options$elementConte === void 0 ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["popper"] : _options$elementConte,
      _options$altBoundary = _options.altBoundary,
      altBoundary = _options$altBoundary === void 0 ? false : _options$altBoundary,
      _options$padding = _options.padding,
      padding = _options$padding === void 0 ? 0 : _options$padding;
  var paddingObject = Object(_mergePaddingObject_js__WEBPACK_IMPORTED_MODULE_7__["default"])(typeof padding !== 'number' ? padding : Object(_expandToHashMap_js__WEBPACK_IMPORTED_MODULE_8__["default"])(padding, _enums_js__WEBPACK_IMPORTED_MODULE_5__["basePlacements"]));
  var altContext = elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__["popper"] ? _enums_js__WEBPACK_IMPORTED_MODULE_5__["reference"] : _enums_js__WEBPACK_IMPORTED_MODULE_5__["popper"];
  var popperRect = state.rects.popper;
  var element = state.elements[altBoundary ? altContext : elementContext];
  var clippingClientRect = Object(_dom_utils_getClippingRect_js__WEBPACK_IMPORTED_MODULE_0__["default"])(Object(_dom_utils_instanceOf_js__WEBPACK_IMPORTED_MODULE_6__["isElement"])(element) ? element : element.contextElement || Object(_dom_utils_getDocumentElement_js__WEBPACK_IMPORTED_MODULE_1__["default"])(state.elements.popper), boundary, rootBoundary, strategy);
  var referenceClientRect = Object(_dom_utils_getBoundingClientRect_js__WEBPACK_IMPORTED_MODULE_2__["default"])(state.elements.reference);
  var popperOffsets = Object(_computeOffsets_js__WEBPACK_IMPORTED_MODULE_3__["default"])({
    reference: referenceClientRect,
    element: popperRect,
    strategy: 'absolute',
    placement: placement
  });
  var popperClientRect = Object(_rectToClientRect_js__WEBPACK_IMPORTED_MODULE_4__["default"])(Object.assign({}, popperRect, popperOffsets));
  var elementClientRect = elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__["popper"] ? popperClientRect : referenceClientRect; // positive = overflowing the clipping rect
  // 0 or negative = within the clipping rect

  var overflowOffsets = {
    top: clippingClientRect.top - elementClientRect.top + paddingObject.top,
    bottom: elementClientRect.bottom - clippingClientRect.bottom + paddingObject.bottom,
    left: clippingClientRect.left - elementClientRect.left + paddingObject.left,
    right: elementClientRect.right - clippingClientRect.right + paddingObject.right
  };
  var offsetData = state.modifiersData.offset; // Offsets can be applied only to the popper element

  if (elementContext === _enums_js__WEBPACK_IMPORTED_MODULE_5__["popper"] && offsetData) {
    var offset = offsetData[placement];
    Object.keys(overflowOffsets).forEach(function (key) {
      var multiply = [_enums_js__WEBPACK_IMPORTED_MODULE_5__["right"], _enums_js__WEBPACK_IMPORTED_MODULE_5__["bottom"]].indexOf(key) >= 0 ? 1 : -1;
      var axis = [_enums_js__WEBPACK_IMPORTED_MODULE_5__["top"], _enums_js__WEBPACK_IMPORTED_MODULE_5__["bottom"]].indexOf(key) >= 0 ? 'y' : 'x';
      overflowOffsets[key] += offset[axis] * multiply;
    });
  }

  return overflowOffsets;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/expandToHashMap.js":
/*!******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/expandToHashMap.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return expandToHashMap; });
function expandToHashMap(value, keys) {
  return keys.reduce(function (hashMap, key) {
    hashMap[key] = value;
    return hashMap;
  }, {});
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/format.js":
/*!*********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/format.js ***!
  \*********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return format; });
function format(str) {
  for (var _len = arguments.length, args = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    args[_key - 1] = arguments[_key];
  }

  return [].concat(args).reduce(function (p, c) {
    return p.replace(/%s/, c);
  }, str);
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getAltAxis.js":
/*!*************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getAltAxis.js ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getAltAxis; });
function getAltAxis(axis) {
  return axis === 'x' ? 'y' : 'x';
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getBasePlacement.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getBasePlacement.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getBasePlacement; });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");

function getBasePlacement(placement) {
  return placement.split('-')[0];
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getFreshSideObject; });
function getFreshSideObject() {
  return {
    top: 0,
    right: 0,
    bottom: 0,
    left: 0
  };
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getMainAxisFromPlacement.js ***!
  \***************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getMainAxisFromPlacement; });
function getMainAxisFromPlacement(placement) {
  return ['top', 'bottom'].indexOf(placement) >= 0 ? 'x' : 'y';
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js":
/*!***********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getOppositePlacement.js ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getOppositePlacement; });
var hash = {
  left: 'right',
  right: 'left',
  bottom: 'top',
  top: 'bottom'
};
function getOppositePlacement(placement) {
  return placement.replace(/left|right|bottom|top/g, function (matched) {
    return hash[matched];
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js":
/*!********************************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getOppositeVariationPlacement.js ***!
  \********************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getOppositeVariationPlacement; });
var hash = {
  start: 'end',
  end: 'start'
};
function getOppositeVariationPlacement(placement) {
  return placement.replace(/start|end/g, function (matched) {
    return hash[matched];
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/getVariation.js":
/*!***************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/getVariation.js ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getVariation; });
function getVariation(placement) {
  return placement.split('-')[1];
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/math.js":
/*!*******************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/math.js ***!
  \*******************************************************/
/*! exports provided: max, min, round */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "max", function() { return max; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "min", function() { return min; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "round", function() { return round; });
var max = Math.max;
var min = Math.min;
var round = Math.round;

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/mergeByName.js":
/*!**************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/mergeByName.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return mergeByName; });
function mergeByName(modifiers) {
  var merged = modifiers.reduce(function (merged, current) {
    var existing = merged[current.name];
    merged[current.name] = existing ? Object.assign({}, existing, current, {
      options: Object.assign({}, existing.options, current.options),
      data: Object.assign({}, existing.data, current.data)
    }) : current;
    return merged;
  }, {}); // IE11 does not support Object.values

  return Object.keys(merged).map(function (key) {
    return merged[key];
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js":
/*!*********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/mergePaddingObject.js ***!
  \*********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return mergePaddingObject; });
/* harmony import */ var _getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./getFreshSideObject.js */ "./node_modules/@popperjs/core/lib/utils/getFreshSideObject.js");

function mergePaddingObject(paddingObject) {
  return Object.assign({}, Object(_getFreshSideObject_js__WEBPACK_IMPORTED_MODULE_0__["default"])(), paddingObject);
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/orderModifiers.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/orderModifiers.js ***!
  \*****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return orderModifiers; });
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");
 // source: https://stackoverflow.com/questions/49875255

function order(modifiers) {
  var map = new Map();
  var visited = new Set();
  var result = [];
  modifiers.forEach(function (modifier) {
    map.set(modifier.name, modifier);
  }); // On visiting object, check for its dependencies and visit them recursively

  function sort(modifier) {
    visited.add(modifier.name);
    var requires = [].concat(modifier.requires || [], modifier.requiresIfExists || []);
    requires.forEach(function (dep) {
      if (!visited.has(dep)) {
        var depModifier = map.get(dep);

        if (depModifier) {
          sort(depModifier);
        }
      }
    });
    result.push(modifier);
  }

  modifiers.forEach(function (modifier) {
    if (!visited.has(modifier.name)) {
      // check for visited object
      sort(modifier);
    }
  });
  return result;
}

function orderModifiers(modifiers) {
  // order based on dependencies
  var orderedModifiers = order(modifiers); // order based on phase

  return _enums_js__WEBPACK_IMPORTED_MODULE_0__["modifierPhases"].reduce(function (acc, phase) {
    return acc.concat(orderedModifiers.filter(function (modifier) {
      return modifier.phase === phase;
    }));
  }, []);
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/rectToClientRect.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/rectToClientRect.js ***!
  \*******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return rectToClientRect; });
function rectToClientRect(rect) {
  return Object.assign({}, rect, {
    left: rect.x,
    top: rect.y,
    right: rect.x + rect.width,
    bottom: rect.y + rect.height
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/uniqueBy.js":
/*!***********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/uniqueBy.js ***!
  \***********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return uniqueBy; });
function uniqueBy(arr, fn) {
  var identifiers = new Set();
  return arr.filter(function (item) {
    var identifier = fn(item);

    if (!identifiers.has(identifier)) {
      identifiers.add(identifier);
      return true;
    }
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/userAgent.js":
/*!************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/userAgent.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return getUAString; });
function getUAString() {
  var uaData = navigator.userAgentData;

  if (uaData != null && uaData.brands) {
    return uaData.brands.map(function (item) {
      return item.brand + "/" + item.version;
    }).join(' ');
  }

  return navigator.userAgent;
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/validateModifiers.js":
/*!********************************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/validateModifiers.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return validateModifiers; });
/* harmony import */ var _format_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./format.js */ "./node_modules/@popperjs/core/lib/utils/format.js");
/* harmony import */ var _enums_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../enums.js */ "./node_modules/@popperjs/core/lib/enums.js");


var INVALID_MODIFIER_ERROR = 'Popper: modifier "%s" provided an invalid %s property, expected %s but got %s';
var MISSING_DEPENDENCY_ERROR = 'Popper: modifier "%s" requires "%s", but "%s" modifier is not available';
var VALID_PROPERTIES = ['name', 'enabled', 'phase', 'fn', 'effect', 'requires', 'options'];
function validateModifiers(modifiers) {
  modifiers.forEach(function (modifier) {
    [].concat(Object.keys(modifier), VALID_PROPERTIES) // IE11-compatible replacement for `new Set(iterable)`
    .filter(function (value, index, self) {
      return self.indexOf(value) === index;
    }).forEach(function (key) {
      switch (key) {
        case 'name':
          if (typeof modifier.name !== 'string') {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, String(modifier.name), '"name"', '"string"', "\"" + String(modifier.name) + "\""));
          }

          break;

        case 'enabled':
          if (typeof modifier.enabled !== 'boolean') {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"enabled"', '"boolean"', "\"" + String(modifier.enabled) + "\""));
          }

          break;

        case 'phase':
          if (_enums_js__WEBPACK_IMPORTED_MODULE_1__["modifierPhases"].indexOf(modifier.phase) < 0) {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"phase"', "either " + _enums_js__WEBPACK_IMPORTED_MODULE_1__["modifierPhases"].join(', '), "\"" + String(modifier.phase) + "\""));
          }

          break;

        case 'fn':
          if (typeof modifier.fn !== 'function') {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"fn"', '"function"', "\"" + String(modifier.fn) + "\""));
          }

          break;

        case 'effect':
          if (modifier.effect != null && typeof modifier.effect !== 'function') {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"effect"', '"function"', "\"" + String(modifier.fn) + "\""));
          }

          break;

        case 'requires':
          if (modifier.requires != null && !Array.isArray(modifier.requires)) {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"requires"', '"array"', "\"" + String(modifier.requires) + "\""));
          }

          break;

        case 'requiresIfExists':
          if (!Array.isArray(modifier.requiresIfExists)) {
            console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(INVALID_MODIFIER_ERROR, modifier.name, '"requiresIfExists"', '"array"', "\"" + String(modifier.requiresIfExists) + "\""));
          }

          break;

        case 'options':
        case 'data':
          break;

        default:
          console.error("PopperJS: an invalid property has been provided to the \"" + modifier.name + "\" modifier, valid properties are " + VALID_PROPERTIES.map(function (s) {
            return "\"" + s + "\"";
          }).join(', ') + "; but \"" + key + "\" was provided.");
      }

      modifier.requires && modifier.requires.forEach(function (requirement) {
        if (modifiers.find(function (mod) {
          return mod.name === requirement;
        }) == null) {
          console.error(Object(_format_js__WEBPACK_IMPORTED_MODULE_0__["default"])(MISSING_DEPENDENCY_ERROR, String(modifier.name), requirement, requirement));
        }
      });
    });
  });
}

/***/ }),

/***/ "./node_modules/@popperjs/core/lib/utils/within.js":
/*!*********************************************************!*\
  !*** ./node_modules/@popperjs/core/lib/utils/within.js ***!
  \*********************************************************/
/*! exports provided: within, withinMaxClamp */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "within", function() { return within; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "withinMaxClamp", function() { return withinMaxClamp; });
/* harmony import */ var _math_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./math.js */ "./node_modules/@popperjs/core/lib/utils/math.js");

function within(min, value, max) {
  return Object(_math_js__WEBPACK_IMPORTED_MODULE_0__["max"])(min, Object(_math_js__WEBPACK_IMPORTED_MODULE_0__["min"])(value, max));
}
function withinMaxClamp(min, value, max) {
  var v = within(min, value, max);
  return v > max ? max : v;
}

/***/ }),

/***/ "./node_modules/bootstrap/js/dist/base-component.js":
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/base-component.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap base-component.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ./dom/data */ "./node_modules/bootstrap/js/dist/dom/data.js"), __webpack_require__(/*! ./util/index */ "./node_modules/bootstrap/js/dist/util/index.js"), __webpack_require__(/*! ./dom/event-handler */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./util/config */ "./node_modules/bootstrap/js/dist/util/config.js")) :
  undefined;
})(this, (function (Data, index, EventHandler, Config) { 'use strict';

  const _interopDefaultLegacy = e => e && typeof e === 'object' && 'default' in e ? e : { default: e };

  const Data__default = /*#__PURE__*/_interopDefaultLegacy(Data);
  const EventHandler__default = /*#__PURE__*/_interopDefaultLegacy(EventHandler);
  const Config__default = /*#__PURE__*/_interopDefaultLegacy(Config);

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): base-component.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Constants
   */

  const VERSION = '5.2.2';
  /**
   * Class definition
   */

  class BaseComponent extends Config__default.default {
    constructor(element, config) {
      super();
      element = index.getElement(element);

      if (!element) {
        return;
      }

      this._element = element;
      this._config = this._getConfig(config);
      Data__default.default.set(this._element, this.constructor.DATA_KEY, this);
    } // Public


    dispose() {
      Data__default.default.remove(this._element, this.constructor.DATA_KEY);
      EventHandler__default.default.off(this._element, this.constructor.EVENT_KEY);

      for (const propertyName of Object.getOwnPropertyNames(this)) {
        this[propertyName] = null;
      }
    }

    _queueCallback(callback, element, isAnimated = true) {
      index.executeAfterTransition(callback, element, isAnimated);
    }

    _getConfig(config) {
      config = this._mergeConfigObj(config, this._element);
      config = this._configAfterMerge(config);

      this._typeCheckConfig(config);

      return config;
    } // Static


    static getInstance(element) {
      return Data__default.default.get(index.getElement(element), this.DATA_KEY);
    }

    static getOrCreateInstance(element, config = {}) {
      return this.getInstance(element) || new this(element, typeof config === 'object' ? config : null);
    }

    static get VERSION() {
      return VERSION;
    }

    static get DATA_KEY() {
      return `bs.${this.NAME}`;
    }

    static get EVENT_KEY() {
      return `.${this.DATA_KEY}`;
    }

    static eventName(name) {
      return `${name}${this.EVENT_KEY}`;
    }

  }

  return BaseComponent;

}));
//# sourceMappingURL=base-component.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/dom/data.js":
/*!****************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/data.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap data.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory() :
  undefined;
})(this, (function () { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): dom/data.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */

  /**
   * Constants
   */
  const elementMap = new Map();
  const data = {
    set(element, key, instance) {
      if (!elementMap.has(element)) {
        elementMap.set(element, new Map());
      }

      const instanceMap = elementMap.get(element); // make it clear we only want one instance per element
      // can be removed later when multiple key/instances are fine to be used

      if (!instanceMap.has(key) && instanceMap.size !== 0) {
        // eslint-disable-next-line no-console
        console.error(`Bootstrap doesn't allow more than one instance per element. Bound instance: ${Array.from(instanceMap.keys())[0]}.`);
        return;
      }

      instanceMap.set(key, instance);
    },

    get(element, key) {
      if (elementMap.has(element)) {
        return elementMap.get(element).get(key) || null;
      }

      return null;
    },

    remove(element, key) {
      if (!elementMap.has(element)) {
        return;
      }

      const instanceMap = elementMap.get(element);
      instanceMap.delete(key); // free up element references if there are no instances left for an element

      if (instanceMap.size === 0) {
        elementMap.delete(element);
      }
    }

  };

  return data;

}));
//# sourceMappingURL=data.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/dom/event-handler.js":
/*!*************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/event-handler.js ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap event-handler.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../util/index */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  undefined;
})(this, (function (index) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): dom/event-handler.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Constants
   */

  const namespaceRegex = /[^.]*(?=\..*)\.|.*/;
  const stripNameRegex = /\..*/;
  const stripUidRegex = /::\d+$/;
  const eventRegistry = {}; // Events storage

  let uidEvent = 1;
  const customEvents = {
    mouseenter: 'mouseover',
    mouseleave: 'mouseout'
  };
  const nativeEvents = new Set(['click', 'dblclick', 'mouseup', 'mousedown', 'contextmenu', 'mousewheel', 'DOMMouseScroll', 'mouseover', 'mouseout', 'mousemove', 'selectstart', 'selectend', 'keydown', 'keypress', 'keyup', 'orientationchange', 'touchstart', 'touchmove', 'touchend', 'touchcancel', 'pointerdown', 'pointermove', 'pointerup', 'pointerleave', 'pointercancel', 'gesturestart', 'gesturechange', 'gestureend', 'focus', 'blur', 'change', 'reset', 'select', 'submit', 'focusin', 'focusout', 'load', 'unload', 'beforeunload', 'resize', 'move', 'DOMContentLoaded', 'readystatechange', 'error', 'abort', 'scroll']);
  /**
   * Private methods
   */

  function makeEventUid(element, uid) {
    return uid && `${uid}::${uidEvent++}` || element.uidEvent || uidEvent++;
  }

  function getElementEvents(element) {
    const uid = makeEventUid(element);
    element.uidEvent = uid;
    eventRegistry[uid] = eventRegistry[uid] || {};
    return eventRegistry[uid];
  }

  function bootstrapHandler(element, fn) {
    return function handler(event) {
      hydrateObj(event, {
        delegateTarget: element
      });

      if (handler.oneOff) {
        EventHandler.off(element, event.type, fn);
      }

      return fn.apply(element, [event]);
    };
  }

  function bootstrapDelegationHandler(element, selector, fn) {
    return function handler(event) {
      const domElements = element.querySelectorAll(selector);

      for (let {
        target
      } = event; target && target !== this; target = target.parentNode) {
        for (const domElement of domElements) {
          if (domElement !== target) {
            continue;
          }

          hydrateObj(event, {
            delegateTarget: target
          });

          if (handler.oneOff) {
            EventHandler.off(element, event.type, selector, fn);
          }

          return fn.apply(target, [event]);
        }
      }
    };
  }

  function findHandler(events, callable, delegationSelector = null) {
    return Object.values(events).find(event => event.callable === callable && event.delegationSelector === delegationSelector);
  }

  function normalizeParameters(originalTypeEvent, handler, delegationFunction) {
    const isDelegated = typeof handler === 'string'; // todo: tooltip passes `false` instead of selector, so we need to check

    const callable = isDelegated ? delegationFunction : handler || delegationFunction;
    let typeEvent = getTypeEvent(originalTypeEvent);

    if (!nativeEvents.has(typeEvent)) {
      typeEvent = originalTypeEvent;
    }

    return [isDelegated, callable, typeEvent];
  }

  function addHandler(element, originalTypeEvent, handler, delegationFunction, oneOff) {
    if (typeof originalTypeEvent !== 'string' || !element) {
      return;
    }

    let [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction); // in case of mouseenter or mouseleave wrap the handler within a function that checks for its DOM position
    // this prevents the handler from being dispatched the same way as mouseover or mouseout does

    if (originalTypeEvent in customEvents) {
      const wrapFunction = fn => {
        return function (event) {
          if (!event.relatedTarget || event.relatedTarget !== event.delegateTarget && !event.delegateTarget.contains(event.relatedTarget)) {
            return fn.call(this, event);
          }
        };
      };

      callable = wrapFunction(callable);
    }

    const events = getElementEvents(element);
    const handlers = events[typeEvent] || (events[typeEvent] = {});
    const previousFunction = findHandler(handlers, callable, isDelegated ? handler : null);

    if (previousFunction) {
      previousFunction.oneOff = previousFunction.oneOff && oneOff;
      return;
    }

    const uid = makeEventUid(callable, originalTypeEvent.replace(namespaceRegex, ''));
    const fn = isDelegated ? bootstrapDelegationHandler(element, handler, callable) : bootstrapHandler(element, callable);
    fn.delegationSelector = isDelegated ? handler : null;
    fn.callable = callable;
    fn.oneOff = oneOff;
    fn.uidEvent = uid;
    handlers[uid] = fn;
    element.addEventListener(typeEvent, fn, isDelegated);
  }

  function removeHandler(element, events, typeEvent, handler, delegationSelector) {
    const fn = findHandler(events[typeEvent], handler, delegationSelector);

    if (!fn) {
      return;
    }

    element.removeEventListener(typeEvent, fn, Boolean(delegationSelector));
    delete events[typeEvent][fn.uidEvent];
  }

  function removeNamespacedHandlers(element, events, typeEvent, namespace) {
    const storeElementEvent = events[typeEvent] || {};

    for (const handlerKey of Object.keys(storeElementEvent)) {
      if (handlerKey.includes(namespace)) {
        const event = storeElementEvent[handlerKey];
        removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
      }
    }
  }

  function getTypeEvent(event) {
    // allow to get the native events from namespaced events ('click.bs.button' --> 'click')
    event = event.replace(stripNameRegex, '');
    return customEvents[event] || event;
  }

  const EventHandler = {
    on(element, event, handler, delegationFunction) {
      addHandler(element, event, handler, delegationFunction, false);
    },

    one(element, event, handler, delegationFunction) {
      addHandler(element, event, handler, delegationFunction, true);
    },

    off(element, originalTypeEvent, handler, delegationFunction) {
      if (typeof originalTypeEvent !== 'string' || !element) {
        return;
      }

      const [isDelegated, callable, typeEvent] = normalizeParameters(originalTypeEvent, handler, delegationFunction);
      const inNamespace = typeEvent !== originalTypeEvent;
      const events = getElementEvents(element);
      const storeElementEvent = events[typeEvent] || {};
      const isNamespace = originalTypeEvent.startsWith('.');

      if (typeof callable !== 'undefined') {
        // Simplest case: handler is passed, remove that listener ONLY.
        if (!Object.keys(storeElementEvent).length) {
          return;
        }

        removeHandler(element, events, typeEvent, callable, isDelegated ? handler : null);
        return;
      }

      if (isNamespace) {
        for (const elementEvent of Object.keys(events)) {
          removeNamespacedHandlers(element, events, elementEvent, originalTypeEvent.slice(1));
        }
      }

      for (const keyHandlers of Object.keys(storeElementEvent)) {
        const handlerKey = keyHandlers.replace(stripUidRegex, '');

        if (!inNamespace || originalTypeEvent.includes(handlerKey)) {
          const event = storeElementEvent[keyHandlers];
          removeHandler(element, events, typeEvent, event.callable, event.delegationSelector);
        }
      }
    },

    trigger(element, event, args) {
      if (typeof event !== 'string' || !element) {
        return null;
      }

      const $ = index.getjQuery();
      const typeEvent = getTypeEvent(event);
      const inNamespace = event !== typeEvent;
      let jQueryEvent = null;
      let bubbles = true;
      let nativeDispatch = true;
      let defaultPrevented = false;

      if (inNamespace && $) {
        jQueryEvent = $.Event(event, args);
        $(element).trigger(jQueryEvent);
        bubbles = !jQueryEvent.isPropagationStopped();
        nativeDispatch = !jQueryEvent.isImmediatePropagationStopped();
        defaultPrevented = jQueryEvent.isDefaultPrevented();
      }

      let evt = new Event(event, {
        bubbles,
        cancelable: true
      });
      evt = hydrateObj(evt, args);

      if (defaultPrevented) {
        evt.preventDefault();
      }

      if (nativeDispatch) {
        element.dispatchEvent(evt);
      }

      if (evt.defaultPrevented && jQueryEvent) {
        jQueryEvent.preventDefault();
      }

      return evt;
    }

  };

  function hydrateObj(obj, meta) {
    for (const [key, value] of Object.entries(meta || {})) {
      try {
        obj[key] = value;
      } catch (_unused) {
        Object.defineProperty(obj, key, {
          configurable: true,

          get() {
            return value;
          }

        });
      }
    }

    return obj;
  }

  return EventHandler;

}));
//# sourceMappingURL=event-handler.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/dom/manipulator.js":
/*!***********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/manipulator.js ***!
  \***********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap manipulator.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory() :
  undefined;
})(this, (function () { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): dom/manipulator.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  function normalizeData(value) {
    if (value === 'true') {
      return true;
    }

    if (value === 'false') {
      return false;
    }

    if (value === Number(value).toString()) {
      return Number(value);
    }

    if (value === '' || value === 'null') {
      return null;
    }

    if (typeof value !== 'string') {
      return value;
    }

    try {
      return JSON.parse(decodeURIComponent(value));
    } catch (_unused) {
      return value;
    }
  }

  function normalizeDataKey(key) {
    return key.replace(/[A-Z]/g, chr => `-${chr.toLowerCase()}`);
  }

  const Manipulator = {
    setDataAttribute(element, key, value) {
      element.setAttribute(`data-bs-${normalizeDataKey(key)}`, value);
    },

    removeDataAttribute(element, key) {
      element.removeAttribute(`data-bs-${normalizeDataKey(key)}`);
    },

    getDataAttributes(element) {
      if (!element) {
        return {};
      }

      const attributes = {};
      const bsKeys = Object.keys(element.dataset).filter(key => key.startsWith('bs') && !key.startsWith('bsConfig'));

      for (const key of bsKeys) {
        let pureKey = key.replace(/^bs/, '');
        pureKey = pureKey.charAt(0).toLowerCase() + pureKey.slice(1, pureKey.length);
        attributes[pureKey] = normalizeData(element.dataset[key]);
      }

      return attributes;
    },

    getDataAttribute(element, key) {
      return normalizeData(element.getAttribute(`data-bs-${normalizeDataKey(key)}`));
    }

  };

  return Manipulator;

}));
//# sourceMappingURL=manipulator.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/dom/selector-engine.js":
/*!***************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/dom/selector-engine.js ***!
  \***************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap selector-engine.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ../util/index */ "./node_modules/bootstrap/js/dist/util/index.js")) :
  undefined;
})(this, (function (index) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): dom/selector-engine.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Constants
   */

  const SelectorEngine = {
    find(selector, element = document.documentElement) {
      return [].concat(...Element.prototype.querySelectorAll.call(element, selector));
    },

    findOne(selector, element = document.documentElement) {
      return Element.prototype.querySelector.call(element, selector);
    },

    children(element, selector) {
      return [].concat(...element.children).filter(child => child.matches(selector));
    },

    parents(element, selector) {
      const parents = [];
      let ancestor = element.parentNode.closest(selector);

      while (ancestor) {
        parents.push(ancestor);
        ancestor = ancestor.parentNode.closest(selector);
      }

      return parents;
    },

    prev(element, selector) {
      let previous = element.previousElementSibling;

      while (previous) {
        if (previous.matches(selector)) {
          return [previous];
        }

        previous = previous.previousElementSibling;
      }

      return [];
    },

    // TODO: this is now unused; remove later along with prev()
    next(element, selector) {
      let next = element.nextElementSibling;

      while (next) {
        if (next.matches(selector)) {
          return [next];
        }

        next = next.nextElementSibling;
      }

      return [];
    },

    focusableChildren(element) {
      const focusables = ['a', 'button', 'input', 'textarea', 'select', 'details', '[tabindex]', '[contenteditable="true"]'].map(selector => `${selector}:not([tabindex^="-"])`).join(',');
      return this.find(focusables, element).filter(el => !index.isDisabled(el) && index.isVisible(el));
    }

  };

  return SelectorEngine;

}));
//# sourceMappingURL=selector-engine.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/tooltip.js":
/*!***************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/tooltip.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap tooltip.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! @popperjs/core */ "./node_modules/@popperjs/core/lib/index.js"), __webpack_require__(/*! ./util/index */ "./node_modules/bootstrap/js/dist/util/index.js"), __webpack_require__(/*! ./util/sanitizer */ "./node_modules/bootstrap/js/dist/util/sanitizer.js"), __webpack_require__(/*! ./dom/event-handler */ "./node_modules/bootstrap/js/dist/dom/event-handler.js"), __webpack_require__(/*! ./dom/manipulator */ "./node_modules/bootstrap/js/dist/dom/manipulator.js"), __webpack_require__(/*! ./base-component */ "./node_modules/bootstrap/js/dist/base-component.js"), __webpack_require__(/*! ./util/template-factory */ "./node_modules/bootstrap/js/dist/util/template-factory.js")) :
  undefined;
})(this, (function (Popper, index, sanitizer, EventHandler, Manipulator, BaseComponent, TemplateFactory) { 'use strict';

  const _interopDefaultLegacy = e => e && typeof e === 'object' && 'default' in e ? e : { default: e };

  function _interopNamespace(e) {
    if (e && e.__esModule) return e;
    const n = Object.create(null, { [Symbol.toStringTag]: { value: 'Module' } });
    if (e) {
      for (const k in e) {
        if (k !== 'default') {
          const d = Object.getOwnPropertyDescriptor(e, k);
          Object.defineProperty(n, k, d.get ? d : {
            enumerable: true,
            get: () => e[k]
          });
        }
      }
    }
    n.default = e;
    return Object.freeze(n);
  }

  const Popper__namespace = /*#__PURE__*/_interopNamespace(Popper);
  const EventHandler__default = /*#__PURE__*/_interopDefaultLegacy(EventHandler);
  const Manipulator__default = /*#__PURE__*/_interopDefaultLegacy(Manipulator);
  const BaseComponent__default = /*#__PURE__*/_interopDefaultLegacy(BaseComponent);
  const TemplateFactory__default = /*#__PURE__*/_interopDefaultLegacy(TemplateFactory);

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): tooltip.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Constants
   */

  const NAME = 'tooltip';
  const DISALLOWED_ATTRIBUTES = new Set(['sanitize', 'allowList', 'sanitizeFn']);
  const CLASS_NAME_FADE = 'fade';
  const CLASS_NAME_MODAL = 'modal';
  const CLASS_NAME_SHOW = 'show';
  const SELECTOR_TOOLTIP_INNER = '.tooltip-inner';
  const SELECTOR_MODAL = `.${CLASS_NAME_MODAL}`;
  const EVENT_MODAL_HIDE = 'hide.bs.modal';
  const TRIGGER_HOVER = 'hover';
  const TRIGGER_FOCUS = 'focus';
  const TRIGGER_CLICK = 'click';
  const TRIGGER_MANUAL = 'manual';
  const EVENT_HIDE = 'hide';
  const EVENT_HIDDEN = 'hidden';
  const EVENT_SHOW = 'show';
  const EVENT_SHOWN = 'shown';
  const EVENT_INSERTED = 'inserted';
  const EVENT_CLICK = 'click';
  const EVENT_FOCUSIN = 'focusin';
  const EVENT_FOCUSOUT = 'focusout';
  const EVENT_MOUSEENTER = 'mouseenter';
  const EVENT_MOUSELEAVE = 'mouseleave';
  const AttachmentMap = {
    AUTO: 'auto',
    TOP: 'top',
    RIGHT: index.isRTL() ? 'left' : 'right',
    BOTTOM: 'bottom',
    LEFT: index.isRTL() ? 'right' : 'left'
  };
  const Default = {
    allowList: sanitizer.DefaultAllowlist,
    animation: true,
    boundary: 'clippingParents',
    container: false,
    customClass: '',
    delay: 0,
    fallbackPlacements: ['top', 'right', 'bottom', 'left'],
    html: false,
    offset: [0, 0],
    placement: 'top',
    popperConfig: null,
    sanitize: true,
    sanitizeFn: null,
    selector: false,
    template: '<div class="tooltip" role="tooltip">' + '<div class="tooltip-arrow"></div>' + '<div class="tooltip-inner"></div>' + '</div>',
    title: '',
    trigger: 'hover focus'
  };
  const DefaultType = {
    allowList: 'object',
    animation: 'boolean',
    boundary: '(string|element)',
    container: '(string|element|boolean)',
    customClass: '(string|function)',
    delay: '(number|object)',
    fallbackPlacements: 'array',
    html: 'boolean',
    offset: '(array|string|function)',
    placement: '(string|function)',
    popperConfig: '(null|object|function)',
    sanitize: 'boolean',
    sanitizeFn: '(null|function)',
    selector: '(string|boolean)',
    template: 'string',
    title: '(string|element|function)',
    trigger: 'string'
  };
  /**
   * Class definition
   */

  class Tooltip extends BaseComponent__default.default {
    constructor(element, config) {
      if (typeof Popper__namespace === 'undefined') {
        throw new TypeError('Bootstrap\'s tooltips require Popper (https://popper.js.org)');
      }

      super(element, config); // Private

      this._isEnabled = true;
      this._timeout = 0;
      this._isHovered = null;
      this._activeTrigger = {};
      this._popper = null;
      this._templateFactory = null;
      this._newContent = null; // Protected

      this.tip = null;

      this._setListeners();

      if (!this._config.selector) {
        this._fixTitle();
      }
    } // Getters


    static get Default() {
      return Default;
    }

    static get DefaultType() {
      return DefaultType;
    }

    static get NAME() {
      return NAME;
    } // Public


    enable() {
      this._isEnabled = true;
    }

    disable() {
      this._isEnabled = false;
    }

    toggleEnabled() {
      this._isEnabled = !this._isEnabled;
    }

    toggle() {
      if (!this._isEnabled) {
        return;
      }

      this._activeTrigger.click = !this._activeTrigger.click;

      if (this._isShown()) {
        this._leave();

        return;
      }

      this._enter();
    }

    dispose() {
      clearTimeout(this._timeout);
      EventHandler__default.default.off(this._element.closest(SELECTOR_MODAL), EVENT_MODAL_HIDE, this._hideModalHandler);

      if (this.tip) {
        this.tip.remove();
      }

      if (this._element.getAttribute('data-bs-original-title')) {
        this._element.setAttribute('title', this._element.getAttribute('data-bs-original-title'));
      }

      this._disposePopper();

      super.dispose();
    }

    show() {
      if (this._element.style.display === 'none') {
        throw new Error('Please use show on visible elements');
      }

      if (!(this._isWithContent() && this._isEnabled)) {
        return;
      }

      const showEvent = EventHandler__default.default.trigger(this._element, this.constructor.eventName(EVENT_SHOW));
      const shadowRoot = index.findShadowRoot(this._element);

      const isInTheDom = (shadowRoot || this._element.ownerDocument.documentElement).contains(this._element);

      if (showEvent.defaultPrevented || !isInTheDom) {
        return;
      } // todo v6 remove this OR make it optional


      if (this.tip) {
        this.tip.remove();
        this.tip = null;
      }

      const tip = this._getTipElement();

      this._element.setAttribute('aria-describedby', tip.getAttribute('id'));

      const {
        container
      } = this._config;

      if (!this._element.ownerDocument.documentElement.contains(this.tip)) {
        container.append(tip);
        EventHandler__default.default.trigger(this._element, this.constructor.eventName(EVENT_INSERTED));
      }

      if (this._popper) {
        this._popper.update();
      } else {
        this._popper = this._createPopper(tip);
      }

      tip.classList.add(CLASS_NAME_SHOW); // If this is a touch-enabled device we add extra
      // empty mouseover listeners to the body's immediate children;
      // only needed because of broken event delegation on iOS
      // https://www.quirksmode.org/blog/archives/2014/02/mouse_event_bub.html

      if ('ontouchstart' in document.documentElement) {
        for (const element of [].concat(...document.body.children)) {
          EventHandler__default.default.on(element, 'mouseover', index.noop);
        }
      }

      const complete = () => {
        EventHandler__default.default.trigger(this._element, this.constructor.eventName(EVENT_SHOWN));

        if (this._isHovered === false) {
          this._leave();
        }

        this._isHovered = false;
      };

      this._queueCallback(complete, this.tip, this._isAnimated());
    }

    hide() {
      if (!this._isShown()) {
        return;
      }

      const hideEvent = EventHandler__default.default.trigger(this._element, this.constructor.eventName(EVENT_HIDE));

      if (hideEvent.defaultPrevented) {
        return;
      }

      const tip = this._getTipElement();

      tip.classList.remove(CLASS_NAME_SHOW); // If this is a touch-enabled device we remove the extra
      // empty mouseover listeners we added for iOS support

      if ('ontouchstart' in document.documentElement) {
        for (const element of [].concat(...document.body.children)) {
          EventHandler__default.default.off(element, 'mouseover', index.noop);
        }
      }

      this._activeTrigger[TRIGGER_CLICK] = false;
      this._activeTrigger[TRIGGER_FOCUS] = false;
      this._activeTrigger[TRIGGER_HOVER] = false;
      this._isHovered = null; // it is a trick to support manual triggering

      const complete = () => {
        if (this._isWithActiveTrigger()) {
          return;
        }

        if (!this._isHovered) {
          tip.remove();
        }

        this._element.removeAttribute('aria-describedby');

        EventHandler__default.default.trigger(this._element, this.constructor.eventName(EVENT_HIDDEN));

        this._disposePopper();
      };

      this._queueCallback(complete, this.tip, this._isAnimated());
    }

    update() {
      if (this._popper) {
        this._popper.update();
      }
    } // Protected


    _isWithContent() {
      return Boolean(this._getTitle());
    }

    _getTipElement() {
      if (!this.tip) {
        this.tip = this._createTipElement(this._newContent || this._getContentForTemplate());
      }

      return this.tip;
    }

    _createTipElement(content) {
      const tip = this._getTemplateFactory(content).toHtml(); // todo: remove this check on v6


      if (!tip) {
        return null;
      }

      tip.classList.remove(CLASS_NAME_FADE, CLASS_NAME_SHOW); // todo: on v6 the following can be achieved with CSS only

      tip.classList.add(`bs-${this.constructor.NAME}-auto`);
      const tipId = index.getUID(this.constructor.NAME).toString();
      tip.setAttribute('id', tipId);

      if (this._isAnimated()) {
        tip.classList.add(CLASS_NAME_FADE);
      }

      return tip;
    }

    setContent(content) {
      this._newContent = content;

      if (this._isShown()) {
        this._disposePopper();

        this.show();
      }
    }

    _getTemplateFactory(content) {
      if (this._templateFactory) {
        this._templateFactory.changeContent(content);
      } else {
        this._templateFactory = new TemplateFactory__default.default({ ...this._config,
          // the `content` var has to be after `this._config`
          // to override config.content in case of popover
          content,
          extraClass: this._resolvePossibleFunction(this._config.customClass)
        });
      }

      return this._templateFactory;
    }

    _getContentForTemplate() {
      return {
        [SELECTOR_TOOLTIP_INNER]: this._getTitle()
      };
    }

    _getTitle() {
      return this._resolvePossibleFunction(this._config.title) || this._element.getAttribute('data-bs-original-title');
    } // Private


    _initializeOnDelegatedTarget(event) {
      return this.constructor.getOrCreateInstance(event.delegateTarget, this._getDelegateConfig());
    }

    _isAnimated() {
      return this._config.animation || this.tip && this.tip.classList.contains(CLASS_NAME_FADE);
    }

    _isShown() {
      return this.tip && this.tip.classList.contains(CLASS_NAME_SHOW);
    }

    _createPopper(tip) {
      const placement = typeof this._config.placement === 'function' ? this._config.placement.call(this, tip, this._element) : this._config.placement;
      const attachment = AttachmentMap[placement.toUpperCase()];
      return Popper__namespace.createPopper(this._element, tip, this._getPopperConfig(attachment));
    }

    _getOffset() {
      const {
        offset
      } = this._config;

      if (typeof offset === 'string') {
        return offset.split(',').map(value => Number.parseInt(value, 10));
      }

      if (typeof offset === 'function') {
        return popperData => offset(popperData, this._element);
      }

      return offset;
    }

    _resolvePossibleFunction(arg) {
      return typeof arg === 'function' ? arg.call(this._element) : arg;
    }

    _getPopperConfig(attachment) {
      const defaultBsPopperConfig = {
        placement: attachment,
        modifiers: [{
          name: 'flip',
          options: {
            fallbackPlacements: this._config.fallbackPlacements
          }
        }, {
          name: 'offset',
          options: {
            offset: this._getOffset()
          }
        }, {
          name: 'preventOverflow',
          options: {
            boundary: this._config.boundary
          }
        }, {
          name: 'arrow',
          options: {
            element: `.${this.constructor.NAME}-arrow`
          }
        }, {
          name: 'preSetPlacement',
          enabled: true,
          phase: 'beforeMain',
          fn: data => {
            // Pre-set Popper's placement attribute in order to read the arrow sizes properly.
            // Otherwise, Popper mixes up the width and height dimensions since the initial arrow style is for top placement
            this._getTipElement().setAttribute('data-popper-placement', data.state.placement);
          }
        }]
      };
      return { ...defaultBsPopperConfig,
        ...(typeof this._config.popperConfig === 'function' ? this._config.popperConfig(defaultBsPopperConfig) : this._config.popperConfig)
      };
    }

    _setListeners() {
      const triggers = this._config.trigger.split(' ');

      for (const trigger of triggers) {
        if (trigger === 'click') {
          EventHandler__default.default.on(this._element, this.constructor.eventName(EVENT_CLICK), this._config.selector, event => {
            const context = this._initializeOnDelegatedTarget(event);

            context.toggle();
          });
        } else if (trigger !== TRIGGER_MANUAL) {
          const eventIn = trigger === TRIGGER_HOVER ? this.constructor.eventName(EVENT_MOUSEENTER) : this.constructor.eventName(EVENT_FOCUSIN);
          const eventOut = trigger === TRIGGER_HOVER ? this.constructor.eventName(EVENT_MOUSELEAVE) : this.constructor.eventName(EVENT_FOCUSOUT);
          EventHandler__default.default.on(this._element, eventIn, this._config.selector, event => {
            const context = this._initializeOnDelegatedTarget(event);

            context._activeTrigger[event.type === 'focusin' ? TRIGGER_FOCUS : TRIGGER_HOVER] = true;

            context._enter();
          });
          EventHandler__default.default.on(this._element, eventOut, this._config.selector, event => {
            const context = this._initializeOnDelegatedTarget(event);

            context._activeTrigger[event.type === 'focusout' ? TRIGGER_FOCUS : TRIGGER_HOVER] = context._element.contains(event.relatedTarget);

            context._leave();
          });
        }
      }

      this._hideModalHandler = () => {
        if (this._element) {
          this.hide();
        }
      };

      EventHandler__default.default.on(this._element.closest(SELECTOR_MODAL), EVENT_MODAL_HIDE, this._hideModalHandler);
    }

    _fixTitle() {
      const title = this._element.getAttribute('title');

      if (!title) {
        return;
      }

      if (!this._element.getAttribute('aria-label') && !this._element.textContent.trim()) {
        this._element.setAttribute('aria-label', title);
      }

      this._element.setAttribute('data-bs-original-title', title); // DO NOT USE IT. Is only for backwards compatibility


      this._element.removeAttribute('title');
    }

    _enter() {
      if (this._isShown() || this._isHovered) {
        this._isHovered = true;
        return;
      }

      this._isHovered = true;

      this._setTimeout(() => {
        if (this._isHovered) {
          this.show();
        }
      }, this._config.delay.show);
    }

    _leave() {
      if (this._isWithActiveTrigger()) {
        return;
      }

      this._isHovered = false;

      this._setTimeout(() => {
        if (!this._isHovered) {
          this.hide();
        }
      }, this._config.delay.hide);
    }

    _setTimeout(handler, timeout) {
      clearTimeout(this._timeout);
      this._timeout = setTimeout(handler, timeout);
    }

    _isWithActiveTrigger() {
      return Object.values(this._activeTrigger).includes(true);
    }

    _getConfig(config) {
      const dataAttributes = Manipulator__default.default.getDataAttributes(this._element);

      for (const dataAttribute of Object.keys(dataAttributes)) {
        if (DISALLOWED_ATTRIBUTES.has(dataAttribute)) {
          delete dataAttributes[dataAttribute];
        }
      }

      config = { ...dataAttributes,
        ...(typeof config === 'object' && config ? config : {})
      };
      config = this._mergeConfigObj(config);
      config = this._configAfterMerge(config);

      this._typeCheckConfig(config);

      return config;
    }

    _configAfterMerge(config) {
      config.container = config.container === false ? document.body : index.getElement(config.container);

      if (typeof config.delay === 'number') {
        config.delay = {
          show: config.delay,
          hide: config.delay
        };
      }

      if (typeof config.title === 'number') {
        config.title = config.title.toString();
      }

      if (typeof config.content === 'number') {
        config.content = config.content.toString();
      }

      return config;
    }

    _getDelegateConfig() {
      const config = {};

      for (const key in this._config) {
        if (this.constructor.Default[key] !== this._config[key]) {
          config[key] = this._config[key];
        }
      }

      config.selector = false;
      config.trigger = 'manual'; // In the future can be replaced with:
      // const keysWithDifferentValues = Object.entries(this._config).filter(entry => this.constructor.Default[entry[0]] !== this._config[entry[0]])
      // `Object.fromEntries(keysWithDifferentValues)`

      return config;
    }

    _disposePopper() {
      if (this._popper) {
        this._popper.destroy();

        this._popper = null;
      }
    } // Static


    static jQueryInterface(config) {
      return this.each(function () {
        const data = Tooltip.getOrCreateInstance(this, config);

        if (typeof config !== 'string') {
          return;
        }

        if (typeof data[config] === 'undefined') {
          throw new TypeError(`No method named "${config}"`);
        }

        data[config]();
      });
    }

  }
  /**
   * jQuery
   */


  index.defineJQueryPlugin(Tooltip);

  return Tooltip;

}));
//# sourceMappingURL=tooltip.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/util/config.js":
/*!*******************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/config.js ***!
  \*******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap config.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ./index */ "./node_modules/bootstrap/js/dist/util/index.js"), __webpack_require__(/*! ../dom/manipulator */ "./node_modules/bootstrap/js/dist/dom/manipulator.js")) :
  undefined;
})(this, (function (index, Manipulator) { 'use strict';

  const _interopDefaultLegacy = e => e && typeof e === 'object' && 'default' in e ? e : { default: e };

  const Manipulator__default = /*#__PURE__*/_interopDefaultLegacy(Manipulator);

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): util/config.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Class definition
   */

  class Config {
    // Getters
    static get Default() {
      return {};
    }

    static get DefaultType() {
      return {};
    }

    static get NAME() {
      throw new Error('You have to implement the static method "NAME", for each component!');
    }

    _getConfig(config) {
      config = this._mergeConfigObj(config);
      config = this._configAfterMerge(config);

      this._typeCheckConfig(config);

      return config;
    }

    _configAfterMerge(config) {
      return config;
    }

    _mergeConfigObj(config, element) {
      const jsonConfig = index.isElement(element) ? Manipulator__default.default.getDataAttribute(element, 'config') : {}; // try to parse

      return { ...this.constructor.Default,
        ...(typeof jsonConfig === 'object' ? jsonConfig : {}),
        ...(index.isElement(element) ? Manipulator__default.default.getDataAttributes(element) : {}),
        ...(typeof config === 'object' ? config : {})
      };
    }

    _typeCheckConfig(config, configTypes = this.constructor.DefaultType) {
      for (const property of Object.keys(configTypes)) {
        const expectedTypes = configTypes[property];
        const value = config[property];
        const valueType = index.isElement(value) ? 'element' : index.toType(value);

        if (!new RegExp(expectedTypes).test(valueType)) {
          throw new TypeError(`${this.constructor.NAME.toUpperCase()}: Option "${property}" provided type "${valueType}" but expected type "${expectedTypes}".`);
        }
      }
    }

  }

  return Config;

}));
//# sourceMappingURL=config.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/util/index.js":
/*!******************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/index.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap index.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? factory(exports) :
  undefined;
})(this, (function (exports) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): util/index.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  const MAX_UID = 1000000;
  const MILLISECONDS_MULTIPLIER = 1000;
  const TRANSITION_END = 'transitionend'; // Shout-out Angus Croll (https://goo.gl/pxwQGp)

  const toType = object => {
    if (object === null || object === undefined) {
      return `${object}`;
    }

    return Object.prototype.toString.call(object).match(/\s([a-z]+)/i)[1].toLowerCase();
  };
  /**
   * Public Util API
   */


  const getUID = prefix => {
    do {
      prefix += Math.floor(Math.random() * MAX_UID);
    } while (document.getElementById(prefix));

    return prefix;
  };

  const getSelector = element => {
    let selector = element.getAttribute('data-bs-target');

    if (!selector || selector === '#') {
      let hrefAttribute = element.getAttribute('href'); // The only valid content that could double as a selector are IDs or classes,
      // so everything starting with `#` or `.`. If a "real" URL is used as the selector,
      // `document.querySelector` will rightfully complain it is invalid.
      // See https://github.com/twbs/bootstrap/issues/32273

      if (!hrefAttribute || !hrefAttribute.includes('#') && !hrefAttribute.startsWith('.')) {
        return null;
      } // Just in case some CMS puts out a full URL with the anchor appended


      if (hrefAttribute.includes('#') && !hrefAttribute.startsWith('#')) {
        hrefAttribute = `#${hrefAttribute.split('#')[1]}`;
      }

      selector = hrefAttribute && hrefAttribute !== '#' ? hrefAttribute.trim() : null;
    }

    return selector;
  };

  const getSelectorFromElement = element => {
    const selector = getSelector(element);

    if (selector) {
      return document.querySelector(selector) ? selector : null;
    }

    return null;
  };

  const getElementFromSelector = element => {
    const selector = getSelector(element);
    return selector ? document.querySelector(selector) : null;
  };

  const getTransitionDurationFromElement = element => {
    if (!element) {
      return 0;
    } // Get transition-duration of the element


    let {
      transitionDuration,
      transitionDelay
    } = window.getComputedStyle(element);
    const floatTransitionDuration = Number.parseFloat(transitionDuration);
    const floatTransitionDelay = Number.parseFloat(transitionDelay); // Return 0 if element or transition duration is not found

    if (!floatTransitionDuration && !floatTransitionDelay) {
      return 0;
    } // If multiple durations are defined, take the first


    transitionDuration = transitionDuration.split(',')[0];
    transitionDelay = transitionDelay.split(',')[0];
    return (Number.parseFloat(transitionDuration) + Number.parseFloat(transitionDelay)) * MILLISECONDS_MULTIPLIER;
  };

  const triggerTransitionEnd = element => {
    element.dispatchEvent(new Event(TRANSITION_END));
  };

  const isElement = object => {
    if (!object || typeof object !== 'object') {
      return false;
    }

    if (typeof object.jquery !== 'undefined') {
      object = object[0];
    }

    return typeof object.nodeType !== 'undefined';
  };

  const getElement = object => {
    // it's a jQuery object or a node element
    if (isElement(object)) {
      return object.jquery ? object[0] : object;
    }

    if (typeof object === 'string' && object.length > 0) {
      return document.querySelector(object);
    }

    return null;
  };

  const isVisible = element => {
    if (!isElement(element) || element.getClientRects().length === 0) {
      return false;
    }

    const elementIsVisible = getComputedStyle(element).getPropertyValue('visibility') === 'visible'; // Handle `details` element as its content may falsie appear visible when it is closed

    const closedDetails = element.closest('details:not([open])');

    if (!closedDetails) {
      return elementIsVisible;
    }

    if (closedDetails !== element) {
      const summary = element.closest('summary');

      if (summary && summary.parentNode !== closedDetails) {
        return false;
      }

      if (summary === null) {
        return false;
      }
    }

    return elementIsVisible;
  };

  const isDisabled = element => {
    if (!element || element.nodeType !== Node.ELEMENT_NODE) {
      return true;
    }

    if (element.classList.contains('disabled')) {
      return true;
    }

    if (typeof element.disabled !== 'undefined') {
      return element.disabled;
    }

    return element.hasAttribute('disabled') && element.getAttribute('disabled') !== 'false';
  };

  const findShadowRoot = element => {
    if (!document.documentElement.attachShadow) {
      return null;
    } // Can find the shadow root otherwise it'll return the document


    if (typeof element.getRootNode === 'function') {
      const root = element.getRootNode();
      return root instanceof ShadowRoot ? root : null;
    }

    if (element instanceof ShadowRoot) {
      return element;
    } // when we don't find a shadow root


    if (!element.parentNode) {
      return null;
    }

    return findShadowRoot(element.parentNode);
  };

  const noop = () => {};
  /**
   * Trick to restart an element's animation
   *
   * @param {HTMLElement} element
   * @return void
   *
   * @see https://www.charistheo.io/blog/2021/02/restart-a-css-animation-with-javascript/#restarting-a-css-animation
   */


  const reflow = element => {
    element.offsetHeight; // eslint-disable-line no-unused-expressions
  };

  const getjQuery = () => {
    if (window.jQuery && !document.body.hasAttribute('data-bs-no-jquery')) {
      return window.jQuery;
    }

    return null;
  };

  const DOMContentLoadedCallbacks = [];

  const onDOMContentLoaded = callback => {
    if (document.readyState === 'loading') {
      // add listener on the first call when the document is in loading state
      if (!DOMContentLoadedCallbacks.length) {
        document.addEventListener('DOMContentLoaded', () => {
          for (const callback of DOMContentLoadedCallbacks) {
            callback();
          }
        });
      }

      DOMContentLoadedCallbacks.push(callback);
    } else {
      callback();
    }
  };

  const isRTL = () => document.documentElement.dir === 'rtl';

  const defineJQueryPlugin = plugin => {
    onDOMContentLoaded(() => {
      const $ = getjQuery();
      /* istanbul ignore if */

      if ($) {
        const name = plugin.NAME;
        const JQUERY_NO_CONFLICT = $.fn[name];
        $.fn[name] = plugin.jQueryInterface;
        $.fn[name].Constructor = plugin;

        $.fn[name].noConflict = () => {
          $.fn[name] = JQUERY_NO_CONFLICT;
          return plugin.jQueryInterface;
        };
      }
    });
  };

  const execute = callback => {
    if (typeof callback === 'function') {
      callback();
    }
  };

  const executeAfterTransition = (callback, transitionElement, waitForTransition = true) => {
    if (!waitForTransition) {
      execute(callback);
      return;
    }

    const durationPadding = 5;
    const emulatedDuration = getTransitionDurationFromElement(transitionElement) + durationPadding;
    let called = false;

    const handler = ({
      target
    }) => {
      if (target !== transitionElement) {
        return;
      }

      called = true;
      transitionElement.removeEventListener(TRANSITION_END, handler);
      execute(callback);
    };

    transitionElement.addEventListener(TRANSITION_END, handler);
    setTimeout(() => {
      if (!called) {
        triggerTransitionEnd(transitionElement);
      }
    }, emulatedDuration);
  };
  /**
   * Return the previous/next element of a list.
   *
   * @param {array} list    The list of elements
   * @param activeElement   The active element
   * @param shouldGetNext   Choose to get next or previous element
   * @param isCycleAllowed
   * @return {Element|elem} The proper element
   */


  const getNextActiveElement = (list, activeElement, shouldGetNext, isCycleAllowed) => {
    const listLength = list.length;
    let index = list.indexOf(activeElement); // if the element does not exist in the list return an element
    // depending on the direction and if cycle is allowed

    if (index === -1) {
      return !shouldGetNext && isCycleAllowed ? list[listLength - 1] : list[0];
    }

    index += shouldGetNext ? 1 : -1;

    if (isCycleAllowed) {
      index = (index + listLength) % listLength;
    }

    return list[Math.max(0, Math.min(index, listLength - 1))];
  };

  exports.defineJQueryPlugin = defineJQueryPlugin;
  exports.execute = execute;
  exports.executeAfterTransition = executeAfterTransition;
  exports.findShadowRoot = findShadowRoot;
  exports.getElement = getElement;
  exports.getElementFromSelector = getElementFromSelector;
  exports.getNextActiveElement = getNextActiveElement;
  exports.getSelectorFromElement = getSelectorFromElement;
  exports.getTransitionDurationFromElement = getTransitionDurationFromElement;
  exports.getUID = getUID;
  exports.getjQuery = getjQuery;
  exports.isDisabled = isDisabled;
  exports.isElement = isElement;
  exports.isRTL = isRTL;
  exports.isVisible = isVisible;
  exports.noop = noop;
  exports.onDOMContentLoaded = onDOMContentLoaded;
  exports.reflow = reflow;
  exports.toType = toType;
  exports.triggerTransitionEnd = triggerTransitionEnd;

  Object.defineProperties(exports, { __esModule: { value: true }, [Symbol.toStringTag]: { value: 'Module' } });

}));
//# sourceMappingURL=index.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/util/sanitizer.js":
/*!**********************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/sanitizer.js ***!
  \**********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap sanitizer.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? factory(exports) :
  undefined;
})(this, (function (exports) { 'use strict';

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): util/sanitizer.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  const uriAttributes = new Set(['background', 'cite', 'href', 'itemtype', 'longdesc', 'poster', 'src', 'xlink:href']);
  const ARIA_ATTRIBUTE_PATTERN = /^aria-[\w-]*$/i;
  /**
   * A pattern that recognizes a commonly useful subset of URLs that are safe.
   *
   * Shout-out to Angular https://github.com/angular/angular/blob/12.2.x/packages/core/src/sanitization/url_sanitizer.ts
   */

  const SAFE_URL_PATTERN = /^(?:(?:https?|mailto|ftp|tel|file|sms):|[^#&/:?]*(?:[#/?]|$))/i;
  /**
   * A pattern that matches safe data URLs. Only matches image, video and audio types.
   *
   * Shout-out to Angular https://github.com/angular/angular/blob/12.2.x/packages/core/src/sanitization/url_sanitizer.ts
   */

  const DATA_URL_PATTERN = /^data:(?:image\/(?:bmp|gif|jpeg|jpg|png|tiff|webp)|video\/(?:mpeg|mp4|ogg|webm)|audio\/(?:mp3|oga|ogg|opus));base64,[\d+/a-z]+=*$/i;

  const allowedAttribute = (attribute, allowedAttributeList) => {
    const attributeName = attribute.nodeName.toLowerCase();

    if (allowedAttributeList.includes(attributeName)) {
      if (uriAttributes.has(attributeName)) {
        return Boolean(SAFE_URL_PATTERN.test(attribute.nodeValue) || DATA_URL_PATTERN.test(attribute.nodeValue));
      }

      return true;
    } // Check if a regular expression validates the attribute.


    return allowedAttributeList.filter(attributeRegex => attributeRegex instanceof RegExp).some(regex => regex.test(attributeName));
  };

  const DefaultAllowlist = {
    // Global attributes allowed on any supplied element below.
    '*': ['class', 'dir', 'id', 'lang', 'role', ARIA_ATTRIBUTE_PATTERN],
    a: ['target', 'href', 'title', 'rel'],
    area: [],
    b: [],
    br: [],
    col: [],
    code: [],
    div: [],
    em: [],
    hr: [],
    h1: [],
    h2: [],
    h3: [],
    h4: [],
    h5: [],
    h6: [],
    i: [],
    img: ['src', 'srcset', 'alt', 'title', 'width', 'height'],
    li: [],
    ol: [],
    p: [],
    pre: [],
    s: [],
    small: [],
    span: [],
    sub: [],
    sup: [],
    strong: [],
    u: [],
    ul: []
  };
  function sanitizeHtml(unsafeHtml, allowList, sanitizeFunction) {
    if (!unsafeHtml.length) {
      return unsafeHtml;
    }

    if (sanitizeFunction && typeof sanitizeFunction === 'function') {
      return sanitizeFunction(unsafeHtml);
    }

    const domParser = new window.DOMParser();
    const createdDocument = domParser.parseFromString(unsafeHtml, 'text/html');
    const elements = [].concat(...createdDocument.body.querySelectorAll('*'));

    for (const element of elements) {
      const elementName = element.nodeName.toLowerCase();

      if (!Object.keys(allowList).includes(elementName)) {
        element.remove();
        continue;
      }

      const attributeList = [].concat(...element.attributes);
      const allowedAttributes = [].concat(allowList['*'] || [], allowList[elementName] || []);

      for (const attribute of attributeList) {
        if (!allowedAttribute(attribute, allowedAttributes)) {
          element.removeAttribute(attribute.nodeName);
        }
      }
    }

    return createdDocument.body.innerHTML;
  }

  exports.DefaultAllowlist = DefaultAllowlist;
  exports.sanitizeHtml = sanitizeHtml;

  Object.defineProperties(exports, { __esModule: { value: true }, [Symbol.toStringTag]: { value: 'Module' } });

}));
//# sourceMappingURL=sanitizer.js.map


/***/ }),

/***/ "./node_modules/bootstrap/js/dist/util/template-factory.js":
/*!*****************************************************************!*\
  !*** ./node_modules/bootstrap/js/dist/util/template-factory.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*!
  * Bootstrap template-factory.js v5.2.2 (https://getbootstrap.com/)
  * Copyright 2011-2022 The Bootstrap Authors (https://github.com/twbs/bootstrap/graphs/contributors)
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
  */
(function (global, factory) {
   true ? module.exports = factory(__webpack_require__(/*! ./sanitizer */ "./node_modules/bootstrap/js/dist/util/sanitizer.js"), __webpack_require__(/*! ./index */ "./node_modules/bootstrap/js/dist/util/index.js"), __webpack_require__(/*! ../dom/selector-engine */ "./node_modules/bootstrap/js/dist/dom/selector-engine.js"), __webpack_require__(/*! ./config */ "./node_modules/bootstrap/js/dist/util/config.js")) :
  undefined;
})(this, (function (sanitizer, index, SelectorEngine, Config) { 'use strict';

  const _interopDefaultLegacy = e => e && typeof e === 'object' && 'default' in e ? e : { default: e };

  const SelectorEngine__default = /*#__PURE__*/_interopDefaultLegacy(SelectorEngine);
  const Config__default = /*#__PURE__*/_interopDefaultLegacy(Config);

  /**
   * --------------------------------------------------------------------------
   * Bootstrap (v5.2.2): util/template-factory.js
   * Licensed under MIT (https://github.com/twbs/bootstrap/blob/main/LICENSE)
   * --------------------------------------------------------------------------
   */
  /**
   * Constants
   */

  const NAME = 'TemplateFactory';
  const Default = {
    allowList: sanitizer.DefaultAllowlist,
    content: {},
    // { selector : text ,  selector2 : text2 , }
    extraClass: '',
    html: false,
    sanitize: true,
    sanitizeFn: null,
    template: '<div></div>'
  };
  const DefaultType = {
    allowList: 'object',
    content: 'object',
    extraClass: '(string|function)',
    html: 'boolean',
    sanitize: 'boolean',
    sanitizeFn: '(null|function)',
    template: 'string'
  };
  const DefaultContentType = {
    entry: '(string|element|function|null)',
    selector: '(string|element)'
  };
  /**
   * Class definition
   */

  class TemplateFactory extends Config__default.default {
    constructor(config) {
      super();
      this._config = this._getConfig(config);
    } // Getters


    static get Default() {
      return Default;
    }

    static get DefaultType() {
      return DefaultType;
    }

    static get NAME() {
      return NAME;
    } // Public


    getContent() {
      return Object.values(this._config.content).map(config => this._resolvePossibleFunction(config)).filter(Boolean);
    }

    hasContent() {
      return this.getContent().length > 0;
    }

    changeContent(content) {
      this._checkContent(content);

      this._config.content = { ...this._config.content,
        ...content
      };
      return this;
    }

    toHtml() {
      const templateWrapper = document.createElement('div');
      templateWrapper.innerHTML = this._maybeSanitize(this._config.template);

      for (const [selector, text] of Object.entries(this._config.content)) {
        this._setContent(templateWrapper, text, selector);
      }

      const template = templateWrapper.children[0];

      const extraClass = this._resolvePossibleFunction(this._config.extraClass);

      if (extraClass) {
        template.classList.add(...extraClass.split(' '));
      }

      return template;
    } // Private


    _typeCheckConfig(config) {
      super._typeCheckConfig(config);

      this._checkContent(config.content);
    }

    _checkContent(arg) {
      for (const [selector, content] of Object.entries(arg)) {
        super._typeCheckConfig({
          selector,
          entry: content
        }, DefaultContentType);
      }
    }

    _setContent(template, content, selector) {
      const templateElement = SelectorEngine__default.default.findOne(selector, template);

      if (!templateElement) {
        return;
      }

      content = this._resolvePossibleFunction(content);

      if (!content) {
        templateElement.remove();
        return;
      }

      if (index.isElement(content)) {
        this._putElementInTemplate(index.getElement(content), templateElement);

        return;
      }

      if (this._config.html) {
        templateElement.innerHTML = this._maybeSanitize(content);
        return;
      }

      templateElement.textContent = content;
    }

    _maybeSanitize(arg) {
      return this._config.sanitize ? sanitizer.sanitizeHtml(arg, this._config.allowList, this._config.sanitizeFn) : arg;
    }

    _resolvePossibleFunction(arg) {
      return typeof arg === 'function' ? arg(this) : arg;
    }

    _putElementInTemplate(element, templateElement) {
      if (this._config.html) {
        templateElement.innerHTML = '';
        templateElement.append(element);
        return;
      }

      templateElement.textContent = element.textContent;
    }

  }

  return TemplateFactory;

}));
//# sourceMappingURL=template-factory.js.map


/***/ }),

/***/ "./node_modules/intro.js/minified/intro.min.js":
/*!*****************************************************!*\
  !*** ./node_modules/intro.js/minified/intro.min.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {/*!
 * Intro.js v4.3.0
 * https://introjs.com
 *
 * Copyright (C) 2012-2021 Afshin Mehrabani (@afshinmeh).
 * https://raw.githubusercontent.com/usablica/intro.js/master/license.md
 *
 * Date: Sat, 06 Nov 2021 14:22:05 GMT
 */
!function(t,e){ true?module.exports=e():undefined}(this,(function(){"use strict";function t(e){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},t(e)}var e=function(){var t={};return function(e){var n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:"introjs-stamp";return t[n]=t[n]||0,void 0===e[n]&&(e[n]=t[n]++),e[n]}}();function n(t,e,n){if(t)for(var i=0,o=t.length;i<o;i++)e(t[i],i);"function"==typeof n&&n()}var i=new function(){var t="introjs_event";this._id=function(t,n,i,o){return n+e(i)+(o?"_".concat(e(o)):"")},this.on=function(e,n,i,o,r){var a=this._id.apply(this,arguments),l=function(t){return i.call(o||e,t||window.event)};"addEventListener"in e?e.addEventListener(n,l,r):"attachEvent"in e&&e.attachEvent("on".concat(n),l),e[t]=e[t]||{},e[t][a]=l},this.off=function(e,n,i,o,r){var a=this._id.apply(this,arguments),l=e[t]&&e[t][a];l&&("removeEventListener"in e?e.removeEventListener(n,l,r):"detachEvent"in e&&e.detachEvent("on".concat(n),l),e[t][a]=null)}},o="undefined"!=typeof globalThis?globalThis:"undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:{};function r(t,e){return t(e={exports:{}},e.exports),e.exports}var a,l,s=function(t){return t&&t.Math==Math&&t},c=s("object"==typeof globalThis&&globalThis)||s("object"==typeof window&&window)||s("object"==typeof self&&self)||s("object"==typeof o&&o)||function(){return this}()||Function("return this")(),u=function(t){try{return!!t()}catch(t){return!0}},h=!u((function(){return 7!=Object.defineProperty({},1,{get:function(){return 7}})[1]})),f=Function.prototype.call,p=f.bind?f.bind(f):function(){return f.apply(f,arguments)},d={}.propertyIsEnumerable,g=Object.getOwnPropertyDescriptor,v={f:g&&!d.call({1:2},1)?function(t){var e=g(this,t);return!!e&&e.enumerable}:d},m=function(t,e){return{enumerable:!(1&t),configurable:!(2&t),writable:!(4&t),value:e}},b=Function.prototype,y=b.bind,w=b.call,_=y&&y.bind(w),x=y?function(t){return t&&_(w,t)}:function(t){return t&&function(){return w.apply(t,arguments)}},S=x({}.toString),j=x("".slice),C=function(t){return j(S(t),8,-1)},k=c.Object,A=x("".split),E=u((function(){return!k("z").propertyIsEnumerable(0)}))?function(t){return"String"==C(t)?A(t,""):k(t)}:k,I=c.TypeError,T=function(t){if(null==t)throw I("Can't call method on "+t);return t},N=function(t){return E(T(t))},O=function(t){return"function"==typeof t},P=function(t){return"object"==typeof t?null!==t:O(t)},L=function(t){return O(t)?t:void 0},R=function(t,e){return arguments.length<2?L(c[t]):c[t]&&c[t][e]},q=x({}.isPrototypeOf),B=R("navigator","userAgent")||"",M=c.process,H=c.Deno,F=M&&M.versions||H&&H.version,$=F&&F.v8;$&&(l=(a=$.split("."))[0]>0&&a[0]<4?1:+(a[0]+a[1])),!l&&B&&(!(a=B.match(/Edge\/(\d+)/))||a[1]>=74)&&(a=B.match(/Chrome\/(\d+)/))&&(l=+a[1]);var D=l,z=!!Object.getOwnPropertySymbols&&!u((function(){var t=Symbol();return!String(t)||!(Object(t)instanceof Symbol)||!Symbol.sham&&D&&D<41})),G=z&&!Symbol.sham&&"symbol"==typeof Symbol.iterator,U=c.Object,W=G?function(t){return"symbol"==typeof t}:function(t){var e=R("Symbol");return O(e)&&q(e.prototype,U(t))},V=c.String,Y=function(t){try{return V(t)}catch(t){return"Object"}},K=c.TypeError,X=function(t){if(O(t))return t;throw K(Y(t)+" is not a function")},J=function(t,e){var n=t[e];return null==n?void 0:X(n)},Q=c.TypeError,Z=Object.defineProperty,tt=function(t,e){try{Z(c,t,{value:e,configurable:!0,writable:!0})}catch(n){c[t]=e}return e},et="__core-js_shared__",nt=c[et]||tt(et,{}),it=r((function(t){(t.exports=function(t,e){return nt[t]||(nt[t]=void 0!==e?e:{})})("versions",[]).push({version:"3.19.1",mode:"global",copyright:"© 2021 Denis Pushkarev (zloirock.ru)"})})),ot=c.Object,rt=function(t){return ot(T(t))},at=x({}.hasOwnProperty),lt=Object.hasOwn||function(t,e){return at(rt(t),e)},st=0,ct=Math.random(),ut=x(1..toString),ht=function(t){return"Symbol("+(void 0===t?"":t)+")_"+ut(++st+ct,36)},ft=it("wks"),pt=c.Symbol,dt=pt&&pt.for,gt=G?pt:pt&&pt.withoutSetter||ht,vt=function(t){if(!lt(ft,t)||!z&&"string"!=typeof ft[t]){var e="Symbol."+t;z&&lt(pt,t)?ft[t]=pt[t]:ft[t]=G&&dt?dt(e):gt(e)}return ft[t]},mt=c.TypeError,bt=vt("toPrimitive"),yt=function(t,e){if(!P(t)||W(t))return t;var n,i=J(t,bt);if(i){if(void 0===e&&(e="default"),n=p(i,t,e),!P(n)||W(n))return n;throw mt("Can't convert object to primitive value")}return void 0===e&&(e="number"),function(t,e){var n,i;if("string"===e&&O(n=t.toString)&&!P(i=p(n,t)))return i;if(O(n=t.valueOf)&&!P(i=p(n,t)))return i;if("string"!==e&&O(n=t.toString)&&!P(i=p(n,t)))return i;throw Q("Can't convert object to primitive value")}(t,e)},wt=function(t){var e=yt(t,"string");return W(e)?e:e+""},_t=c.document,xt=P(_t)&&P(_t.createElement),St=function(t){return xt?_t.createElement(t):{}},jt=!h&&!u((function(){return 7!=Object.defineProperty(St("div"),"a",{get:function(){return 7}}).a})),Ct=Object.getOwnPropertyDescriptor,kt={f:h?Ct:function(t,e){if(t=N(t),e=wt(e),jt)try{return Ct(t,e)}catch(t){}if(lt(t,e))return m(!p(v.f,t,e),t[e])}},At=c.String,Et=c.TypeError,It=function(t){if(P(t))return t;throw Et(At(t)+" is not an object")},Tt=c.TypeError,Nt=Object.defineProperty,Ot={f:h?Nt:function(t,e,n){if(It(t),e=wt(e),It(n),jt)try{return Nt(t,e,n)}catch(t){}if("get"in n||"set"in n)throw Tt("Accessors not supported");return"value"in n&&(t[e]=n.value),t}},Pt=h?function(t,e,n){return Ot.f(t,e,m(1,n))}:function(t,e,n){return t[e]=n,t},Lt=x(Function.toString);O(nt.inspectSource)||(nt.inspectSource=function(t){return Lt(t)});var Rt,qt,Bt,Mt=nt.inspectSource,Ht=c.WeakMap,Ft=O(Ht)&&/native code/.test(Mt(Ht)),$t=it("keys"),Dt=function(t){return $t[t]||($t[t]=ht(t))},zt={},Gt="Object already initialized",Ut=c.TypeError,Wt=c.WeakMap;if(Ft||nt.state){var Vt=nt.state||(nt.state=new Wt),Yt=x(Vt.get),Kt=x(Vt.has),Xt=x(Vt.set);Rt=function(t,e){if(Kt(Vt,t))throw new Ut(Gt);return e.facade=t,Xt(Vt,t,e),e},qt=function(t){return Yt(Vt,t)||{}},Bt=function(t){return Kt(Vt,t)}}else{var Jt=Dt("state");zt[Jt]=!0,Rt=function(t,e){if(lt(t,Jt))throw new Ut(Gt);return e.facade=t,Pt(t,Jt,e),e},qt=function(t){return lt(t,Jt)?t[Jt]:{}},Bt=function(t){return lt(t,Jt)}}var Qt={set:Rt,get:qt,has:Bt,enforce:function(t){return Bt(t)?qt(t):Rt(t,{})},getterFor:function(t){return function(e){var n;if(!P(e)||(n=qt(e)).type!==t)throw Ut("Incompatible receiver, "+t+" required");return n}}},Zt=Function.prototype,te=h&&Object.getOwnPropertyDescriptor,ee=lt(Zt,"name"),ne={EXISTS:ee,PROPER:ee&&"something"===function(){}.name,CONFIGURABLE:ee&&(!h||h&&te(Zt,"name").configurable)},ie=r((function(t){var e=ne.CONFIGURABLE,n=Qt.get,i=Qt.enforce,o=String(String).split("String");(t.exports=function(t,n,r,a){var l,s=!!a&&!!a.unsafe,u=!!a&&!!a.enumerable,h=!!a&&!!a.noTargetGet,f=a&&void 0!==a.name?a.name:n;O(r)&&("Symbol("===String(f).slice(0,7)&&(f="["+String(f).replace(/^Symbol\(([^)]*)\)/,"$1")+"]"),(!lt(r,"name")||e&&r.name!==f)&&Pt(r,"name",f),(l=i(r)).source||(l.source=o.join("string"==typeof f?f:""))),t!==c?(s?!h&&t[n]&&(u=!0):delete t[n],u?t[n]=r:Pt(t,n,r)):u?t[n]=r:tt(n,r)})(Function.prototype,"toString",(function(){return O(this)&&n(this).source||Mt(this)}))})),oe=Math.ceil,re=Math.floor,ae=function(t){var e=+t;return e!=e||0===e?0:(e>0?re:oe)(e)},le=Math.max,se=Math.min,ce=function(t,e){var n=ae(t);return n<0?le(n+e,0):se(n,e)},ue=Math.min,he=function(t){return t>0?ue(ae(t),9007199254740991):0},fe=function(t){return he(t.length)},pe=function(t){return function(e,n,i){var o,r=N(e),a=fe(r),l=ce(i,a);if(t&&n!=n){for(;a>l;)if((o=r[l++])!=o)return!0}else for(;a>l;l++)if((t||l in r)&&r[l]===n)return t||l||0;return!t&&-1}},de={includes:pe(!0),indexOf:pe(!1)},ge=de.indexOf,ve=x([].push),me=function(t,e){var n,i=N(t),o=0,r=[];for(n in i)!lt(zt,n)&&lt(i,n)&&ve(r,n);for(;e.length>o;)lt(i,n=e[o++])&&(~ge(r,n)||ve(r,n));return r},be=["constructor","hasOwnProperty","isPrototypeOf","propertyIsEnumerable","toLocaleString","toString","valueOf"],ye=be.concat("length","prototype"),we={f:Object.getOwnPropertyNames||function(t){return me(t,ye)}},_e={f:Object.getOwnPropertySymbols},xe=x([].concat),Se=R("Reflect","ownKeys")||function(t){var e=we.f(It(t)),n=_e.f;return n?xe(e,n(t)):e},je=function(t,e){for(var n=Se(e),i=Ot.f,o=kt.f,r=0;r<n.length;r++){var a=n[r];lt(t,a)||i(t,a,o(e,a))}},Ce=/#|\.prototype\./,ke=function(t,e){var n=Ee[Ae(t)];return n==Te||n!=Ie&&(O(e)?u(e):!!e)},Ae=ke.normalize=function(t){return String(t).replace(Ce,".").toLowerCase()},Ee=ke.data={},Ie=ke.NATIVE="N",Te=ke.POLYFILL="P",Ne=ke,Oe=kt.f,Pe=function(t,e){var n,i,o,r,a,l=t.target,s=t.global,u=t.stat;if(n=s?c:u?c[l]||tt(l,{}):(c[l]||{}).prototype)for(i in e){if(r=e[i],o=t.noTargetGet?(a=Oe(n,i))&&a.value:n[i],!Ne(s?i:l+(u?".":"#")+i,t.forced)&&void 0!==o){if(typeof r==typeof o)continue;je(r,o)}(t.sham||o&&o.sham)&&Pt(r,"sham",!0),ie(n,i,r,t)}},Le={};Le[vt("toStringTag")]="z";var Re,qe="[object z]"===String(Le),Be=vt("toStringTag"),Me=c.Object,He="Arguments"==C(function(){return arguments}()),Fe=qe?C:function(t){var e,n,i;return void 0===t?"Undefined":null===t?"Null":"string"==typeof(n=function(t,e){try{return t[e]}catch(t){}}(e=Me(t),Be))?n:He?C(e):"Object"==(i=C(e))&&O(e.callee)?"Arguments":i},$e=c.String,De=function(t){if("Symbol"===Fe(t))throw TypeError("Cannot convert a Symbol value to a string");return $e(t)},ze=function(){var t=It(this),e="";return t.global&&(e+="g"),t.ignoreCase&&(e+="i"),t.multiline&&(e+="m"),t.dotAll&&(e+="s"),t.unicode&&(e+="u"),t.sticky&&(e+="y"),e},Ge=c.RegExp,Ue={UNSUPPORTED_Y:u((function(){var t=Ge("a","y");return t.lastIndex=2,null!=t.exec("abcd")})),BROKEN_CARET:u((function(){var t=Ge("^r","gy");return t.lastIndex=2,null!=t.exec("str")}))},We=Object.keys||function(t){return me(t,be)},Ve=h?Object.defineProperties:function(t,e){It(t);for(var n,i=N(e),o=We(e),r=o.length,a=0;r>a;)Ot.f(t,n=o[a++],i[n]);return t},Ye=R("document","documentElement"),Ke=Dt("IE_PROTO"),Xe=function(){},Je=function(t){return"<script>"+t+"</"+"script>"},Qe=function(t){t.write(Je("")),t.close();var e=t.parentWindow.Object;return t=null,e},Ze=function(){try{Re=new ActiveXObject("htmlfile")}catch(t){}var t,e;Ze="undefined"!=typeof document?document.domain&&Re?Qe(Re):((e=St("iframe")).style.display="none",Ye.appendChild(e),e.src=String("javascript:"),(t=e.contentWindow.document).open(),t.write(Je("document.F=Object")),t.close(),t.F):Qe(Re);for(var n=be.length;n--;)delete Ze.prototype[be[n]];return Ze()};zt[Ke]=!0;var tn,en,nn=Object.create||function(t,e){var n;return null!==t?(Xe.prototype=It(t),n=new Xe,Xe.prototype=null,n[Ke]=t):n=Ze(),void 0===e?n:Ve(n,e)},on=c.RegExp,rn=u((function(){var t=on(".","s");return!(t.dotAll&&t.exec("\n")&&"s"===t.flags)})),an=c.RegExp,ln=u((function(){var t=an("(?<a>b)","g");return"b"!==t.exec("b").groups.a||"bc"!=="b".replace(t,"$<a>c")})),sn=Qt.get,cn=it("native-string-replace",String.prototype.replace),un=RegExp.prototype.exec,hn=un,fn=x("".charAt),pn=x("".indexOf),dn=x("".replace),gn=x("".slice),vn=(en=/b*/g,p(un,tn=/a/,"a"),p(un,en,"a"),0!==tn.lastIndex||0!==en.lastIndex),mn=Ue.UNSUPPORTED_Y||Ue.BROKEN_CARET,bn=void 0!==/()??/.exec("")[1];(vn||bn||mn||rn||ln)&&(hn=function(t){var e,n,i,o,r,a,l,s=this,c=sn(s),u=De(t),h=c.raw;if(h)return h.lastIndex=s.lastIndex,e=p(hn,h,u),s.lastIndex=h.lastIndex,e;var f=c.groups,d=mn&&s.sticky,g=p(ze,s),v=s.source,m=0,b=u;if(d&&(g=dn(g,"y",""),-1===pn(g,"g")&&(g+="g"),b=gn(u,s.lastIndex),s.lastIndex>0&&(!s.multiline||s.multiline&&"\n"!==fn(u,s.lastIndex-1))&&(v="(?: "+v+")",b=" "+b,m++),n=new RegExp("^(?:"+v+")",g)),bn&&(n=new RegExp("^"+v+"$(?!\\s)",g)),vn&&(i=s.lastIndex),o=p(un,d?n:s,b),d?o?(o.input=gn(o.input,m),o[0]=gn(o[0],m),o.index=s.lastIndex,s.lastIndex+=o[0].length):s.lastIndex=0:vn&&o&&(s.lastIndex=s.global?o.index+o[0].length:i),bn&&o&&o.length>1&&p(cn,o[0],n,(function(){for(r=1;r<arguments.length-2;r++)void 0===arguments[r]&&(o[r]=void 0)})),o&&f)for(o.groups=a=nn(null),r=0;r<f.length;r++)a[(l=f[r])[0]]=o[l[1]];return o});var yn=hn;Pe({target:"RegExp",proto:!0,forced:/./.exec!==yn},{exec:yn});var wn=vt("species"),_n=RegExp.prototype,xn=function(t,e,n,i){var o=vt(t),r=!u((function(){var e={};return e[o]=function(){return 7},7!=""[t](e)})),a=r&&!u((function(){var e=!1,n=/a/;return"split"===t&&((n={}).constructor={},n.constructor[wn]=function(){return n},n.flags="",n[o]=/./[o]),n.exec=function(){return e=!0,null},n[o](""),!e}));if(!r||!a||n){var l=x(/./[o]),s=e(o,""[t],(function(t,e,n,i,o){var a=x(t),s=e.exec;return s===yn||s===_n.exec?r&&!o?{done:!0,value:l(e,n,i)}:{done:!0,value:a(n,e,i)}:{done:!1}}));ie(String.prototype,t,s[0]),ie(_n,o,s[1])}i&&Pt(_n[o],"sham",!0)},Sn=x("".charAt),jn=x("".charCodeAt),Cn=x("".slice),kn=function(t){return function(e,n){var i,o,r=De(T(e)),a=ae(n),l=r.length;return a<0||a>=l?t?"":void 0:(i=jn(r,a))<55296||i>56319||a+1===l||(o=jn(r,a+1))<56320||o>57343?t?Sn(r,a):i:t?Cn(r,a,a+2):o-56320+(i-55296<<10)+65536}},An={codeAt:kn(!1),charAt:kn(!0)}.charAt,En=function(t,e,n){return e+(n?An(t,e).length:1)},In=c.TypeError,Tn=function(t,e){var n=t.exec;if(O(n)){var i=p(n,t,e);return null!==i&&It(i),i}if("RegExp"===C(t))return p(yn,t,e);throw In("RegExp#exec called on incompatible receiver")};xn("match",(function(t,e,n){return[function(e){var n=T(this),i=null==e?void 0:J(e,t);return i?p(i,e,n):new RegExp(e)[t](De(n))},function(t){var i=It(this),o=De(t),r=n(e,i,o);if(r.done)return r.value;if(!i.global)return Tn(i,o);var a=i.unicode;i.lastIndex=0;for(var l,s=[],c=0;null!==(l=Tn(i,o));){var u=De(l[0]);s[c]=u,""===u&&(i.lastIndex=En(o,he(i.lastIndex),a)),c++}return 0===c?null:s}]}));var Nn=Array.isArray||function(t){return"Array"==C(t)},On=function(t,e,n){var i=wt(e);i in t?Ot.f(t,i,m(0,n)):t[i]=n},Pn=function(){},Ln=[],Rn=R("Reflect","construct"),qn=/^\s*(?:class|function)\b/,Bn=x(qn.exec),Mn=!qn.exec(Pn),Hn=function(t){if(!O(t))return!1;try{return Rn(Pn,Ln,t),!0}catch(t){return!1}},Fn=!Rn||u((function(){var t;return Hn(Hn.call)||!Hn(Object)||!Hn((function(){t=!0}))||t}))?function(t){if(!O(t))return!1;switch(Fe(t)){case"AsyncFunction":case"GeneratorFunction":case"AsyncGeneratorFunction":return!1}return Mn||!!Bn(qn,Mt(t))}:Hn,$n=vt("species"),Dn=c.Array,zn=function(t,e){return new(function(t){var e;return Nn(t)&&(e=t.constructor,(Fn(e)&&(e===Dn||Nn(e.prototype))||P(e)&&null===(e=e[$n]))&&(e=void 0)),void 0===e?Dn:e}(t))(0===e?0:e)},Gn=vt("species"),Un=function(t){return D>=51||!u((function(){var e=[];return(e.constructor={})[Gn]=function(){return{foo:1}},1!==e[t](Boolean).foo}))},Wn=vt("isConcatSpreadable"),Vn=9007199254740991,Yn="Maximum allowed index exceeded",Kn=c.TypeError,Xn=D>=51||!u((function(){var t=[];return t[Wn]=!1,t.concat()[0]!==t})),Jn=Un("concat"),Qn=function(t){if(!P(t))return!1;var e=t[Wn];return void 0!==e?!!e:Nn(t)};Pe({target:"Array",proto:!0,forced:!Xn||!Jn},{concat:function(t){var e,n,i,o,r,a=rt(this),l=zn(a,0),s=0;for(e=-1,i=arguments.length;e<i;e++)if(Qn(r=-1===e?a:arguments[e])){if(s+(o=fe(r))>Vn)throw Kn(Yn);for(n=0;n<o;n++,s++)n in r&&On(l,s,r[n])}else{if(s>=Vn)throw Kn(Yn);On(l,s++,r)}return l.length=s,l}});var Zn=qe?{}.toString:function(){return"[object "+Fe(this)+"]"};qe||ie(Object.prototype,"toString",Zn,{unsafe:!0});var ti=ne.PROPER,ei="toString",ni=RegExp.prototype,ii=ni.toString,oi=x(ze),ri=u((function(){return"/a/b"!=ii.call({source:"a",flags:"b"})})),ai=ti&&ii.name!=ei;(ri||ai)&&ie(RegExp.prototype,ei,(function(){var t=It(this),e=De(t.source),n=t.flags;return"/"+e+"/"+De(void 0===n&&q(ni,t)&&!("flags"in ni)?oi(t):n)}),{unsafe:!0});var li=Function.prototype,si=li.apply,ci=li.bind,ui=li.call,hi="object"==typeof Reflect&&Reflect.apply||(ci?ui.bind(si):function(){return ui.apply(si,arguments)}),fi=vt("match"),pi=function(t){var e;return P(t)&&(void 0!==(e=t[fi])?!!e:"RegExp"==C(t))},di=c.TypeError,gi=vt("species"),vi=function(t,e){var n,i=It(t).constructor;return void 0===i||null==(n=It(i)[gi])?e:function(t){if(Fn(t))return t;throw di(Y(t)+" is not a constructor")}(n)},mi=x([].slice),bi=Ue.UNSUPPORTED_Y,yi=4294967295,wi=Math.min,_i=[].push,xi=x(/./.exec),Si=x(_i),ji=x("".slice),Ci=!u((function(){var t=/(?:)/,e=t.exec;t.exec=function(){return e.apply(this,arguments)};var n="ab".split(t);return 2!==n.length||"a"!==n[0]||"b"!==n[1]}));function ki(t,e){if(t instanceof SVGElement){var i=t.getAttribute("class")||"";i.match(e)||t.setAttribute("class","".concat(i," ").concat(e))}else{if(void 0!==t.classList)n(e.split(" "),(function(e){t.classList.add(e)}));else t.className.match(e)||(t.className+=" ".concat(e))}}function Ai(t,e){var n="";return t.currentStyle?n=t.currentStyle[e]:document.defaultView&&document.defaultView.getComputedStyle&&(n=document.defaultView.getComputedStyle(t,null).getPropertyValue(e)),n&&n.toLowerCase?n.toLowerCase():n}function Ei(t){var e=t.element;if(this._options.scrollToElement){var n=function(t){var e=window.getComputedStyle(t),n="absolute"===e.position,i=/(auto|scroll)/;if("fixed"===e.position)return document.body;for(var o=t;o=o.parentElement;)if(e=window.getComputedStyle(o),(!n||"static"!==e.position)&&i.test(e.overflow+e.overflowY+e.overflowX))return o;return document.body}(e);n!==document.body&&(n.scrollTop=e.offsetTop-n.offsetTop)}}function Ii(){if(void 0!==window.innerWidth)return{width:window.innerWidth,height:window.innerHeight};var t=document.documentElement;return{width:t.clientWidth,height:t.clientHeight}}function Ti(t,e,n){var i,o=e.element;if("off"!==t&&(this._options.scrollToElement&&(i="tooltip"===t?n.getBoundingClientRect():o.getBoundingClientRect(),!function(t){var e=t.getBoundingClientRect();return e.top>=0&&e.left>=0&&e.bottom+80<=window.innerHeight&&e.right<=window.innerWidth}(o)))){var r=Ii().height;i.bottom-(i.bottom-i.top)<0||o.clientHeight>r?window.scrollBy(0,i.top-(r/2-i.height/2)-this._options.scrollPadding):window.scrollBy(0,i.top-(r/2-i.height/2)+this._options.scrollPadding)}}function Ni(t){t.setAttribute("role","button"),t.tabIndex=0}xn("split",(function(t,e,n){var i;return i="c"=="abbc".split(/(b)*/)[1]||4!="test".split(/(?:)/,-1).length||2!="ab".split(/(?:ab)*/).length||4!=".".split(/(.?)(.?)/).length||".".split(/()()/).length>1||"".split(/.?/).length?function(t,n){var i=De(T(this)),o=void 0===n?yi:n>>>0;if(0===o)return[];if(void 0===t)return[i];if(!pi(t))return p(e,i,t,o);for(var r,a,l,s=[],c=(t.ignoreCase?"i":"")+(t.multiline?"m":"")+(t.unicode?"u":"")+(t.sticky?"y":""),u=0,h=new RegExp(t.source,c+"g");(r=p(yn,h,i))&&!((a=h.lastIndex)>u&&(Si(s,ji(i,u,r.index)),r.length>1&&r.index<i.length&&hi(_i,s,mi(r,1)),l=r[0].length,u=a,s.length>=o));)h.lastIndex===r.index&&h.lastIndex++;return u===i.length?!l&&xi(h,"")||Si(s,""):Si(s,ji(i,u)),s.length>o?mi(s,0,o):s}:"0".split(void 0,0).length?function(t,n){return void 0===t&&0===n?[]:p(e,this,t,n)}:e,[function(e,n){var o=T(this),r=null==e?void 0:J(e,t);return r?p(r,e,o,n):p(i,De(o),e,n)},function(t,o){var r=It(this),a=De(t),l=n(i,r,a,o,i!==e);if(l.done)return l.value;var s=vi(r,RegExp),c=r.unicode,u=(r.ignoreCase?"i":"")+(r.multiline?"m":"")+(r.unicode?"u":"")+(bi?"g":"y"),h=new s(bi?"^(?:"+r.source+")":r,u),f=void 0===o?yi:o>>>0;if(0===f)return[];if(0===a.length)return null===Tn(h,a)?[a]:[];for(var p=0,d=0,g=[];d<a.length;){h.lastIndex=bi?0:d;var v,m=Tn(h,bi?ji(a,d):a);if(null===m||(v=wi(he(h.lastIndex+(bi?d:0)),a.length))===p)d=En(a,d,c);else{if(Si(g,ji(a,p,d)),g.length===f)return g;for(var b=1;b<=m.length-1;b++)if(Si(g,m[b]),g.length===f)return g;d=p=v}}return Si(g,ji(a,p)),g}]}),!Ci,bi);var Oi=Object.assign,Pi=Object.defineProperty,Li=x([].concat),Ri=!Oi||u((function(){if(h&&1!==Oi({b:1},Oi(Pi({},"a",{enumerable:!0,get:function(){Pi(this,"b",{value:3,enumerable:!1})}}),{b:2})).b)return!0;var t={},e={},n=Symbol(),i="abcdefghijklmnopqrst";return t[n]=7,i.split("").forEach((function(t){e[t]=t})),7!=Oi({},t)[n]||We(Oi({},e)).join("")!=i}))?function(t,e){for(var n=rt(t),i=arguments.length,o=1,r=_e.f,a=v.f;i>o;)for(var l,s=E(arguments[o++]),c=r?Li(We(s),r(s)):We(s),u=c.length,f=0;u>f;)l=c[f++],h&&!p(a,s,l)||(n[l]=s[l]);return n}:Oi;function qi(t){var e=t.parentNode;return!(!e||"HTML"===e.nodeName)&&("fixed"===Ai(t,"position")||qi(e))}function Bi(t,e){var n=document.body,i=document.documentElement,o=window.pageYOffset||i.scrollTop||n.scrollTop,r=window.pageXOffset||i.scrollLeft||n.scrollLeft;e=e||n;var a=t.getBoundingClientRect(),l=e.getBoundingClientRect(),s=Ai(e,"position"),c={width:a.width,height:a.height};return"body"!==e.tagName.toLowerCase()&&"relative"===s||"sticky"===s?Object.assign(c,{top:a.top-l.top,left:a.left-l.left}):qi(t)?Object.assign(c,{top:a.top,left:a.left}):Object.assign(c,{top:a.top+o,left:a.left+r})}Pe({target:"Object",stat:!0,forced:Object.assign!==Ri},{assign:Ri});var Mi=Math.floor,Hi=x("".charAt),Fi=x("".replace),$i=x("".slice),Di=/\$([$&'`]|\d{1,2}|<[^>]*>)/g,zi=/\$([$&'`]|\d{1,2})/g,Gi=function(t,e,n,i,o,r){var a=n+t.length,l=i.length,s=zi;return void 0!==o&&(o=rt(o),s=Di),Fi(r,s,(function(r,s){var c;switch(Hi(s,0)){case"$":return"$";case"&":return t;case"`":return $i(e,0,n);case"'":return $i(e,a);case"<":c=o[$i(s,1,-1)];break;default:var u=+s;if(0===u)return r;if(u>l){var h=Mi(u/10);return 0===h?r:h<=l?void 0===i[h-1]?Hi(s,1):i[h-1]+Hi(s,1):r}c=i[u-1]}return void 0===c?"":c}))},Ui=vt("replace"),Wi=Math.max,Vi=Math.min,Yi=x([].concat),Ki=x([].push),Xi=x("".indexOf),Ji=x("".slice),Qi="$0"==="a".replace(/./,"$0"),Zi=!!/./[Ui]&&""===/./[Ui]("a","$0");function to(t,e){if(t instanceof SVGElement){var n=t.getAttribute("class")||"";t.setAttribute("class",n.replace(e,"").replace(/^\s+|\s+$/g,""))}else t.className=t.className.replace(e,"").replace(/^\s+|\s+$/g,"")}function eo(t,e){var n="";if(t.style.cssText&&(n+=t.style.cssText),"string"==typeof e)n+=e;else for(var i in e)n+="".concat(i,":").concat(e[i],";");t.style.cssText=n}function no(t){if(t){if(!this._introItems[this._currentStep])return;var e=this._introItems[this._currentStep],n=Bi(e.element,this._targetElement),i=this._options.helperElementPadding;qi(e.element)?ki(t,"introjs-fixedTooltip"):to(t,"introjs-fixedTooltip"),"floating"===e.position&&(i=0),eo(t,{width:"".concat(n.width+i,"px"),height:"".concat(n.height+i,"px"),top:"".concat(n.top-i/2,"px"),left:"".concat(n.left-i/2,"px")})}}xn("replace",(function(t,e,n){var i=Zi?"$":"$0";return[function(t,n){var i=T(this),o=null==t?void 0:J(t,Ui);return o?p(o,t,i,n):p(e,De(i),t,n)},function(t,o){var r=It(this),a=De(t);if("string"==typeof o&&-1===Xi(o,i)&&-1===Xi(o,"$<")){var l=n(e,r,a,o);if(l.done)return l.value}var s=O(o);s||(o=De(o));var c=r.global;if(c){var u=r.unicode;r.lastIndex=0}for(var h=[];;){var f=Tn(r,a);if(null===f)break;if(Ki(h,f),!c)break;""===De(f[0])&&(r.lastIndex=En(a,he(r.lastIndex),u))}for(var p,d="",g=0,v=0;v<h.length;v++){for(var m=De((f=h[v])[0]),b=Wi(Vi(ae(f.index),a.length),0),y=[],w=1;w<f.length;w++)Ki(y,void 0===(p=f[w])?p:String(p));var _=f.groups;if(s){var x=Yi([m],y,b,a);void 0!==_&&Ki(x,_);var S=De(hi(o,void 0,x))}else S=Gi(m,a,b,y,_,o);b>=g&&(d+=Ji(a,g,b)+S,g=b+m.length)}return d+Ji(a,g)}]}),!!u((function(){var t=/./;return t.exec=function(){var t=[];return t.groups={a:"7"},t},"7"!=="".replace(t,"$<a>")}))||!Qi||Zi);var io=vt("unscopables"),oo=Array.prototype;null==oo[io]&&Ot.f(oo,io,{configurable:!0,value:nn(null)});var ro,ao=de.includes;Pe({target:"Array",proto:!0},{includes:function(t){return ao(this,t,arguments.length>1?arguments[1]:void 0)}}),ro="includes",oo[io][ro]=!0;var lo=Un("slice"),so=vt("species"),co=c.Array,uo=Math.max;Pe({target:"Array",proto:!0,forced:!lo},{slice:function(t,e){var n,i,o,r=N(this),a=fe(r),l=ce(t,a),s=ce(void 0===e?a:e,a);if(Nn(r)&&(n=r.constructor,(Fn(n)&&(n===co||Nn(n.prototype))||P(n)&&null===(n=n[so]))&&(n=void 0),n===co||void 0===n))return mi(r,l,s);for(i=new(void 0===n?co:n)(uo(s-l,0)),o=0;l<s;l++,o++)l in r&&On(i,o,r[l]);return i.length=o,i}});var ho=c.TypeError,fo=function(t){if(pi(t))throw ho("The method doesn't accept regular expressions");return t},po=vt("match"),go=x("".indexOf);Pe({target:"String",proto:!0,forced:!function(t){var e=/./;try{"/./"[t](e)}catch(n){try{return e[po]=!1,"/./"[t](e)}catch(t){}}return!1}("includes")},{includes:function(t){return!!~go(De(T(this)),De(fo(t)),arguments.length>1?arguments[1]:void 0)}});var vo=function(t,e){var n=[][t];return!!n&&u((function(){n.call(null,e||function(){throw 1},1)}))},mo=x([].join),bo=E!=Object,yo=vo("join",",");Pe({target:"Array",proto:!0,forced:bo||!yo},{join:function(t){return mo(N(this),void 0===t?",":t)}});var wo=x(x.bind),_o=x([].push),xo=function(t){var e=1==t,n=2==t,i=3==t,o=4==t,r=6==t,a=7==t,l=5==t||r;return function(s,c,u,h){for(var f,p,d=rt(s),g=E(d),v=function(t,e){return X(t),void 0===e?t:wo?wo(t,e):function(){return t.apply(e,arguments)}}(c,u),m=fe(g),b=0,y=h||zn,w=e?y(s,m):n||a?y(s,0):void 0;m>b;b++)if((l||b in g)&&(p=v(f=g[b],b,d),t))if(e)w[b]=p;else if(p)switch(t){case 3:return!0;case 5:return f;case 6:return b;case 2:_o(w,f)}else switch(t){case 4:return!1;case 7:_o(w,f)}return r?-1:i||o?o:w}},So={forEach:xo(0),map:xo(1),filter:xo(2),some:xo(3),every:xo(4),find:xo(5),findIndex:xo(6),filterReject:xo(7)}.filter;function jo(t,e,n,i,o){return t.left+e+n.width>i.width?(o.style.left="".concat(i.width-n.width-t.left,"px"),!1):(o.style.left="".concat(e,"px"),!0)}function Co(t,e,n,i){return t.left+t.width-e-n.width<0?(i.style.left="".concat(-t.left,"px"),!1):(i.style.right="".concat(e,"px"),!0)}Pe({target:"Array",proto:!0,forced:!Un("filter")},{filter:function(t){return So(this,t,arguments.length>1?arguments[1]:void 0)}});var ko=Un("splice"),Ao=c.TypeError,Eo=Math.max,Io=Math.min,To=9007199254740991,No="Maximum allowed length exceeded";function Oo(t,e){t.includes(e)&&t.splice(t.indexOf(e),1)}function Po(t,e,n){var i=this._options.positionPrecedence.slice(),o=Ii(),r=Bi(e).height+10,a=Bi(e).width+20,l=t.getBoundingClientRect(),s="floating";l.bottom+r>o.height&&Oo(i,"bottom"),l.top-r<0&&Oo(i,"top"),l.right+a>o.width&&Oo(i,"right"),l.left-a<0&&Oo(i,"left");var c,u,h=-1!==(u=(c=n||"").indexOf("-"))?c.substr(u):"";return n&&(n=n.split("-")[0]),i.length&&(s=i.includes(n)?n:i[0]),["top","bottom"].includes(s)&&(s+=function(t,e,n,i){var o=n.width,r=e/2,a=Math.min(o,window.screen.width),l=["-left-aligned","-middle-aligned","-right-aligned"];return a-t<e&&Oo(l,"-left-aligned"),(t<r||a-t<r)&&Oo(l,"-middle-aligned"),t<e&&Oo(l,"-right-aligned"),l.length?l.includes(i)?i:l[0]:"-middle-aligned"}(l.left,a,o,h)),s}function Lo(t,e,n,i){var o,r,a,l,s,c="";if(i=i||!1,e.style.top=null,e.style.right=null,e.style.bottom=null,e.style.left=null,e.style.marginLeft=null,e.style.marginTop=null,n.style.display="inherit",this._introItems[this._currentStep])switch(c="string"==typeof(o=this._introItems[this._currentStep]).tooltipClass?o.tooltipClass:this._options.tooltipClass,e.className=["introjs-tooltip",c].filter(Boolean).join(" "),e.setAttribute("role","dialog"),"floating"!==(s=this._introItems[this._currentStep].position)&&this._options.autoPosition&&(s=Po.call(this,t,e,s)),a=Bi(t),r=Bi(e),l=Ii(),ki(e,"introjs-".concat(s)),s){case"top-right-aligned":n.className="introjs-arrow bottom-right";var u=0;Co(a,u,r,e),e.style.bottom="".concat(a.height+20,"px");break;case"top-middle-aligned":n.className="introjs-arrow bottom-middle";var h=a.width/2-r.width/2;i&&(h+=5),Co(a,h,r,e)&&(e.style.right=null,jo(a,h,r,l,e)),e.style.bottom="".concat(a.height+20,"px");break;case"top-left-aligned":case"top":n.className="introjs-arrow bottom",jo(a,i?0:15,r,l,e),e.style.bottom="".concat(a.height+20,"px");break;case"right":e.style.left="".concat(a.width+20,"px"),a.top+r.height>l.height?(n.className="introjs-arrow left-bottom",e.style.top="-".concat(r.height-a.height-20,"px")):n.className="introjs-arrow left";break;case"left":i||!0!==this._options.showStepNumbers||(e.style.top="15px"),a.top+r.height>l.height?(e.style.top="-".concat(r.height-a.height-20,"px"),n.className="introjs-arrow right-bottom"):n.className="introjs-arrow right",e.style.right="".concat(a.width+20,"px");break;case"floating":n.style.display="none",e.style.left="50%",e.style.top="50%",e.style.marginLeft="-".concat(r.width/2,"px"),e.style.marginTop="-".concat(r.height/2,"px");break;case"bottom-right-aligned":n.className="introjs-arrow top-right",Co(a,u=0,r,e),e.style.top="".concat(a.height+20,"px");break;case"bottom-middle-aligned":n.className="introjs-arrow top-middle",h=a.width/2-r.width/2,i&&(h+=5),Co(a,h,r,e)&&(e.style.right=null,jo(a,h,r,l,e)),e.style.top="".concat(a.height+20,"px");break;default:n.className="introjs-arrow top",jo(a,0,r,l,e),e.style.top="".concat(a.height+20,"px")}}function Ro(){n(document.querySelectorAll(".introjs-showElement"),(function(t){to(t,/introjs-[a-zA-Z]+/g)}))}function qo(t,e){var n=document.createElement(t);e=e||{};var i=/^(?:role|data-|aria-)/;for(var o in e){var r=e[o];"style"===o?eo(n,r):o.match(i)?n.setAttribute(o,r):n[o]=r}return n}function Bo(t,e,n){if(n){var i=e.style.opacity||"1";eo(e,{opacity:"0"}),window.setTimeout((function(){eo(e,{opacity:i})}),10)}t.appendChild(e)}function Mo(){return parseInt(this._currentStep+1,10)/this._introItems.length*100}function Ho(){var t=document.querySelector(".introjs-disableInteraction");null===t&&(t=qo("div",{className:"introjs-disableInteraction"}),this._targetElement.appendChild(t)),no.call(this,t)}function Fo(t){var e=this,i=qo("div",{className:"introjs-bullets"});!1===this._options.showBullets&&(i.style.display="none");var o=qo("ul");o.setAttribute("role","tablist");var r=function(){e.goToStep(this.getAttribute("data-stepnumber"))};return n(this._introItems,(function(e,n){var i=e.step,a=qo("li"),l=qo("a");a.setAttribute("role","presentation"),l.setAttribute("role","tab"),l.onclick=r,n===t.step-1&&(l.className="active"),Ni(l),l.innerHTML="&nbsp;",l.setAttribute("data-stepnumber",i),a.appendChild(l),o.appendChild(a)})),i.appendChild(o),i}function $o(t,e){if(this._options.showBullets){var n=document.querySelector(".introjs-bullets");n.parentNode.replaceChild(Fo.call(this,e),n)}}function Do(t,e){this._options.showBullets&&(t.querySelector(".introjs-bullets li > a.active").className="",t.querySelector('.introjs-bullets li > a[data-stepnumber="'.concat(e.step,'"]')).className="active")}function zo(){var t=qo("div");t.className="introjs-progress",!1===this._options.showProgress&&(t.style.display="none");var e=qo("div",{className:"introjs-progressbar"});return this._options.progressBarAdditionalClass&&(e.className+=" "+this._options.progressBarAdditionalClass),e.setAttribute("role","progress"),e.setAttribute("aria-valuemin",0),e.setAttribute("aria-valuemax",100),e.setAttribute("aria-valuenow",Mo.call(this)),e.style.cssText="width:".concat(Mo.call(this),"%;"),t.appendChild(e),t}function Go(t){t.querySelector(".introjs-progress .introjs-progressbar").style.cssText="width:".concat(Mo.call(this),"%;"),t.querySelector(".introjs-progress .introjs-progressbar").setAttribute("aria-valuenow",Mo.call(this))}function Uo(t){var e=this;void 0!==this._introChangeCallback&&this._introChangeCallback.call(this,t.element);var n,i,o,r=this,a=document.querySelector(".introjs-helperLayer"),l=document.querySelector(".introjs-tooltipReferenceLayer"),s="introjs-helperLayer";if("string"==typeof t.highlightClass&&(s+=" ".concat(t.highlightClass)),"string"==typeof this._options.highlightClass&&(s+=" ".concat(this._options.highlightClass)),null!==a&&null!==l){var c=l.querySelector(".introjs-helperNumberLayer"),u=l.querySelector(".introjs-tooltiptext"),h=l.querySelector(".introjs-tooltip-title"),f=l.querySelector(".introjs-arrow"),p=l.querySelector(".introjs-tooltip");o=l.querySelector(".introjs-skipbutton"),i=l.querySelector(".introjs-prevbutton"),n=l.querySelector(".introjs-nextbutton"),a.className=s,p.style.opacity=0,p.style.display="none",Ei.call(r,t),no.call(r,a),no.call(r,l),Ro(),r._lastShowElementTimer&&window.clearTimeout(r._lastShowElementTimer),r._lastShowElementTimer=window.setTimeout((function(){null!==c&&(c.innerHTML="".concat(t.step," of ").concat(e._introItems.length)),u.innerHTML=t.intro,h.innerHTML=t.title,p.style.display="block",Lo.call(r,t.element,p,f),Do.call(r,l,t),Go.call(r,l),p.style.opacity=1,(null!=n&&/introjs-donebutton/gi.test(n.className)||null!=n)&&n.focus(),Ti.call(r,t.scrollTo,t,u)}),350)}else{var d=qo("div",{className:s}),g=qo("div",{className:"introjs-tooltipReferenceLayer"}),v=qo("div",{className:"introjs-arrow"}),m=qo("div",{className:"introjs-tooltip"}),b=qo("div",{className:"introjs-tooltiptext"}),y=qo("div",{className:"introjs-tooltip-header"}),w=qo("h1",{className:"introjs-tooltip-title"}),_=qo("div");eo(d,{"box-shadow":"0 0 1px 2px rgba(33, 33, 33, 0.8), rgba(33, 33, 33, ".concat(r._options.overlayOpacity.toString(),") 0 0 0 5000px")}),Ei.call(r,t),no.call(r,d),no.call(r,g),Bo(this._targetElement,d,!0),Bo(this._targetElement,g),b.innerHTML=t.intro,w.innerHTML=t.title,_.className="introjs-tooltipbuttons",!1===this._options.showButtons&&(_.style.display="none"),y.appendChild(w),m.appendChild(y),m.appendChild(b),m.appendChild(Fo.call(this,t)),m.appendChild(zo.call(this));var x=qo("div");!0===this._options.showStepNumbers&&(x.className="introjs-helperNumberLayer",x.innerHTML="".concat(t.step," of ").concat(this._introItems.length),m.appendChild(x)),m.appendChild(v),g.appendChild(m),(n=qo("a")).onclick=function(){r._introItems.length-1!==r._currentStep?Yo.call(r):/introjs-donebutton/gi.test(n.className)&&("function"==typeof r._introCompleteCallback&&r._introCompleteCallback.call(r,r._currentStep,"done"),Pr.call(r,r._targetElement))},Ni(n),n.innerHTML=this._options.nextLabel,(i=qo("a")).onclick=function(){0!==r._currentStep&&Ko.call(r)},Ni(i),i.innerHTML=this._options.prevLabel,Ni(o=qo("a",{className:"introjs-skipbutton"})),o.innerHTML=this._options.skipLabel,o.onclick=function(){r._introItems.length-1===r._currentStep&&"function"==typeof r._introCompleteCallback&&r._introCompleteCallback.call(r,r._currentStep,"skip"),"function"==typeof r._introSkipCallback&&r._introSkipCallback.call(r),Pr.call(r,r._targetElement)},y.appendChild(o),this._introItems.length>1&&_.appendChild(i),_.appendChild(n),m.appendChild(_),Lo.call(r,t.element,m,v),Ti.call(this,t.scrollTo,t,m)}var S=r._targetElement.querySelector(".introjs-disableInteraction");S&&S.parentNode.removeChild(S),t.disableInteraction&&Ho.call(r),0===this._currentStep&&this._introItems.length>1?(null!=n&&(n.className="".concat(this._options.buttonClass," introjs-nextbutton"),n.innerHTML=this._options.nextLabel),!0===this._options.hidePrev?(null!=i&&(i.className="".concat(this._options.buttonClass," introjs-prevbutton introjs-hidden")),null!=n&&ki(n,"introjs-fullbutton")):null!=i&&(i.className="".concat(this._options.buttonClass," introjs-prevbutton introjs-disabled"))):this._introItems.length-1===this._currentStep||1===this._introItems.length?(null!=i&&(i.className="".concat(this._options.buttonClass," introjs-prevbutton")),!0===this._options.hideNext?(null!=n&&(n.className="".concat(this._options.buttonClass," introjs-nextbutton introjs-hidden")),null!=i&&ki(i,"introjs-fullbutton")):null!=n&&(!0===this._options.nextToDone?(n.innerHTML=this._options.doneLabel,ki(n,"".concat(this._options.buttonClass," introjs-nextbutton introjs-donebutton"))):n.className="".concat(this._options.buttonClass," introjs-nextbutton introjs-disabled"))):(null!=i&&(i.className="".concat(this._options.buttonClass," introjs-prevbutton")),null!=n&&(n.className="".concat(this._options.buttonClass," introjs-nextbutton"),n.innerHTML=this._options.nextLabel)),null!=i&&i.setAttribute("role","button"),null!=n&&n.setAttribute("role","button"),null!=o&&o.setAttribute("role","button"),null!=n&&n.focus(),function(t){var e=t.element;ki(e,"introjs-showElement");var n=Ai(e,"position");"absolute"!==n&&"relative"!==n&&"sticky"!==n&&"fixed"!==n&&ki(e,"introjs-relativePosition")}(t),void 0!==this._introAfterChangeCallback&&this._introAfterChangeCallback.call(this,t.element)}function Wo(t){this._currentStep=t-2,void 0!==this._introItems&&Yo.call(this)}function Vo(t){this._currentStepNumber=t,void 0!==this._introItems&&Yo.call(this)}function Yo(){var t=this;this._direction="forward",void 0!==this._currentStepNumber&&n(this._introItems,(function(e,n){e.step===t._currentStepNumber&&(t._currentStep=n-1,t._currentStepNumber=void 0)})),void 0===this._currentStep?this._currentStep=0:++this._currentStep;var e=this._introItems[this._currentStep],i=!0;return void 0!==this._introBeforeChangeCallback&&(i=this._introBeforeChangeCallback.call(this,e&&e.element)),!1===i?(--this._currentStep,!1):this._introItems.length<=this._currentStep?("function"==typeof this._introCompleteCallback&&this._introCompleteCallback.call(this,this._currentStep,"end"),void Pr.call(this,this._targetElement)):void Uo.call(this,e)}function Ko(){if(this._direction="backward",0===this._currentStep)return!1;--this._currentStep;var t=this._introItems[this._currentStep],e=!0;if(void 0!==this._introBeforeChangeCallback&&(e=this._introBeforeChangeCallback.call(this,t&&t.element)),!1===e)return++this._currentStep,!1;Uo.call(this,t)}function Xo(){return this._currentStep}function Jo(t){var e=void 0===t.code?t.which:t.code;if(null===e&&(e=null===t.charCode?t.keyCode:t.charCode),"Escape"!==e&&27!==e||!0!==this._options.exitOnEsc){if("ArrowLeft"===e||37===e)Ko.call(this);else if("ArrowRight"===e||39===e)Yo.call(this);else if("Enter"===e||"NumpadEnter"===e||13===e){var n=t.target||t.srcElement;n&&n.className.match("introjs-prevbutton")?Ko.call(this):n&&n.className.match("introjs-skipbutton")?(this._introItems.length-1===this._currentStep&&"function"==typeof this._introCompleteCallback&&this._introCompleteCallback.call(this,this._currentStep,"skip"),Pr.call(this,this._targetElement)):n&&n.getAttribute("data-stepnumber")?n.click():Yo.call(this),t.preventDefault?t.preventDefault():t.returnValue=!1}}else Pr.call(this,this._targetElement)}function Qo(e){if(null===e||"object"!==t(e)||void 0!==e.nodeType)return e;var n={};for(var i in e)void 0!==window.jQuery&&e[i]instanceof window.jQuery?n[i]=e[i]:n[i]=Qo(e[i]);return n}function Zo(t){var e=document.querySelector(".introjs-hints");return e?e.querySelectorAll(t):[]}function tr(t){var e=Zo('.introjs-hint[data-step="'.concat(t,'"]'))[0];cr.call(this),e&&ki(e,"introjs-hidehint"),void 0!==this._hintCloseCallback&&this._hintCloseCallback.call(this,t)}function er(){var t=this;n(Zo(".introjs-hint"),(function(e){tr.call(t,e.getAttribute("data-step"))}))}function nr(){var t=this,e=Zo(".introjs-hint");e&&e.length?n(e,(function(e){ir.call(t,e.getAttribute("data-step"))})):ur.call(this,this._targetElement)}function ir(t){var e=Zo('.introjs-hint[data-step="'.concat(t,'"]'))[0];e&&to(e,/introjs-hidehint/g)}function or(){var t=this;n(Zo(".introjs-hint"),(function(e){rr.call(t,e.getAttribute("data-step"))})),i.off(document,"click",cr,this,!1),i.off(window,"resize",hr,this,!0),this._hintsAutoRefreshFunction&&i.off(window,"scroll",this._hintsAutoRefreshFunction,this,!0)}function rr(t){var e=Zo('.introjs-hint[data-step="'.concat(t,'"]'))[0];e&&e.parentNode.removeChild(e)}function ar(){var t=this,e=this,o=document.querySelector(".introjs-hints");null===o&&(o=qo("div",{className:"introjs-hints"}));n(this._introItems,(function(n,i){if(!document.querySelector('.introjs-hint[data-step="'.concat(i,'"]'))){var r=qo("a",{className:"introjs-hint"});Ni(r),r.onclick=function(t){return function(n){var i=n||window.event;i.stopPropagation&&i.stopPropagation(),null!==i.cancelBubble&&(i.cancelBubble=!0),sr.call(e,t)}}(i),n.hintAnimation||ki(r,"introjs-hint-no-anim"),qi(n.element)&&ki(r,"introjs-fixedhint");var a=qo("div",{className:"introjs-hint-dot"}),l=qo("div",{className:"introjs-hint-pulse"});r.appendChild(a),r.appendChild(l),r.setAttribute("data-step",i),n.targetElement=n.element,n.element=r,lr.call(t,n.hintPosition,r,n.targetElement),o.appendChild(r)}})),document.body.appendChild(o),void 0!==this._hintsAddedCallback&&this._hintsAddedCallback.call(this),this._options.hintAutoRefreshInterval>=0&&(this._hintsAutoRefreshFunction=function(t,e){var n,i=this;return function(){for(var o=arguments.length,r=new Array(o),a=0;a<o;a++)r[a]=arguments[a];clearTimeout(n),n=setTimeout((function(){t.apply(i,r)}),e)}}((function(){return hr.call(t)}),this._options.hintAutoRefreshInterval),i.on(window,"scroll",this._hintsAutoRefreshFunction,this,!0))}function lr(t,e,n){var i=e.style,o=Bi.call(this,n),r=20,a=20;switch(t){default:i.left="".concat(o.left,"px"),i.top="".concat(o.top,"px");break;case"top-right":i.left="".concat(o.left+o.width-r,"px"),i.top="".concat(o.top,"px");break;case"bottom-left":i.left="".concat(o.left,"px"),i.top="".concat(o.top+o.height-a,"px");break;case"bottom-right":i.left="".concat(o.left+o.width-r,"px"),i.top="".concat(o.top+o.height-a,"px");break;case"middle-left":i.left="".concat(o.left,"px"),i.top="".concat(o.top+(o.height-a)/2,"px");break;case"middle-right":i.left="".concat(o.left+o.width-r,"px"),i.top="".concat(o.top+(o.height-a)/2,"px");break;case"middle-middle":i.left="".concat(o.left+(o.width-r)/2,"px"),i.top="".concat(o.top+(o.height-a)/2,"px");break;case"bottom-middle":i.left="".concat(o.left+(o.width-r)/2,"px"),i.top="".concat(o.top+o.height-a,"px");break;case"top-middle":i.left="".concat(o.left+(o.width-r)/2,"px"),i.top="".concat(o.top,"px")}}function sr(t){var e=document.querySelector('.introjs-hint[data-step="'.concat(t,'"]')),n=this._introItems[t];void 0!==this._hintClickCallback&&this._hintClickCallback.call(this,e,n,t);var i=cr.call(this);if(parseInt(i,10)!==t){var o=qo("div",{className:"introjs-tooltip"}),r=qo("div"),a=qo("div"),l=qo("div");o.onclick=function(t){t.stopPropagation?t.stopPropagation():t.cancelBubble=!0},r.className="introjs-tooltiptext";var s=qo("p");if(s.innerHTML=n.hint,r.appendChild(s),this._options.hintShowButton){var c=qo("a");c.className=this._options.buttonClass,c.setAttribute("role","button"),c.innerHTML=this._options.hintButtonLabel,c.onclick=tr.bind(this,t),r.appendChild(c)}a.className="introjs-arrow",o.appendChild(a),o.appendChild(r),this._currentStep=e.getAttribute("data-step"),l.className="introjs-tooltipReferenceLayer introjs-hintReference",l.setAttribute("data-step",e.getAttribute("data-step")),no.call(this,l),l.appendChild(o),document.body.appendChild(l),Lo.call(this,e,o,a,!0)}}function cr(){var t=document.querySelector(".introjs-hintReference");if(t){var e=t.getAttribute("data-step");return t.parentNode.removeChild(t),e}}function ur(t){var e=this;if(this._introItems=[],this._options.hints)n(this._options.hints,(function(t){var n=Qo(t);"string"==typeof n.element&&(n.element=document.querySelector(n.element)),n.hintPosition=n.hintPosition||e._options.hintPosition,n.hintAnimation=n.hintAnimation||e._options.hintAnimation,null!==n.element&&e._introItems.push(n)}));else{var o=t.querySelectorAll("*[data-hint]");if(!o||!o.length)return!1;n(o,(function(t){var n=t.getAttribute("data-hintanimation");n=n?"true"===n:e._options.hintAnimation,e._introItems.push({element:t,hint:t.getAttribute("data-hint"),hintPosition:t.getAttribute("data-hintposition")||e._options.hintPosition,hintAnimation:n,tooltipClass:t.getAttribute("data-tooltipclass"),position:t.getAttribute("data-position")||e._options.tooltipPosition})}))}ar.call(this),i.on(document,"click",cr,this,!1),i.on(window,"resize",hr,this,!0)}function hr(){var t=this;n(this._introItems,(function(e){var n=e.targetElement,i=e.hintPosition,o=e.element;void 0!==n&&lr.call(t,i,o,n)}))}Pe({target:"Array",proto:!0,forced:!ko},{splice:function(t,e){var n,i,o,r,a,l,s=rt(this),c=fe(s),u=ce(t,c),h=arguments.length;if(0===h?n=i=0:1===h?(n=0,i=c-u):(n=h-2,i=Io(Eo(ae(e),0),c-u)),c+n-i>To)throw Ao(No);for(o=zn(s,i),r=0;r<i;r++)(a=u+r)in s&&On(o,r,s[a]);if(o.length=i,n<i){for(r=u;r<c-i;r++)l=r+n,(a=r+i)in s?s[l]=s[a]:delete s[l];for(r=c;r>c-i+n;r--)delete s[r-1]}else if(n>i)for(r=c-i;r>u;r--)l=r+n-1,(a=r+i-1)in s?s[l]=s[a]:delete s[l];for(r=0;r<n;r++)s[r+u]=arguments[r+2];return s.length=c-i+n,o}});var fr=Math.floor,pr=function(t,e){var n=t.length,i=fr(n/2);return n<8?dr(t,e):gr(t,pr(mi(t,0,i),e),pr(mi(t,i),e),e)},dr=function(t,e){for(var n,i,o=t.length,r=1;r<o;){for(i=r,n=t[r];i&&e(t[i-1],n)>0;)t[i]=t[--i];i!==r++&&(t[i]=n)}return t},gr=function(t,e,n,i){for(var o=e.length,r=n.length,a=0,l=0;a<o||l<r;)t[a+l]=a<o&&l<r?i(e[a],n[l])<=0?e[a++]:n[l++]:a<o?e[a++]:n[l++];return t},vr=pr,mr=B.match(/firefox\/(\d+)/i),br=!!mr&&+mr[1],yr=/MSIE|Trident/.test(B),wr=B.match(/AppleWebKit\/(\d+)\./),_r=!!wr&&+wr[1],xr=[],Sr=x(xr.sort),jr=x(xr.push),Cr=u((function(){xr.sort(void 0)})),kr=u((function(){xr.sort(null)})),Ar=vo("sort"),Er=!u((function(){if(D)return D<70;if(!(br&&br>3)){if(yr)return!0;if(_r)return _r<603;var t,e,n,i,o="";for(t=65;t<76;t++){switch(e=String.fromCharCode(t),t){case 66:case 69:case 70:case 72:n=3;break;case 68:case 71:n=4;break;default:n=2}for(i=0;i<47;i++)xr.push({k:e+i,v:n})}for(xr.sort((function(t,e){return e.v-t.v})),i=0;i<xr.length;i++)e=xr[i].k.charAt(0),o.charAt(o.length-1)!==e&&(o+=e);return"DGBEFHACIJK"!==o}}));function Ir(t){var e=this,i=t.querySelectorAll("*[data-intro]"),o=[];if(this._options.steps)n(this._options.steps,(function(t){var n=Qo(t);if(n.step=o.length+1,n.title=n.title||"","string"==typeof n.element&&(n.element=document.querySelector(n.element)),void 0===n.element||null===n.element){var i=document.querySelector(".introjsFloatingElement");null===i&&(i=qo("div",{className:"introjsFloatingElement"}),document.body.appendChild(i)),n.element=i,n.position="floating"}n.position=n.position||e._options.tooltipPosition,n.scrollTo=n.scrollTo||e._options.scrollTo,void 0===n.disableInteraction&&(n.disableInteraction=e._options.disableInteraction),null!==n.element&&o.push(n)}));else{var r;if(i.length<1)return[];n(i,(function(t){if((!e._options.group||t.getAttribute("data-intro-group")===e._options.group)&&"none"!==t.style.display){var n=parseInt(t.getAttribute("data-step"),10);r=t.hasAttribute("data-disable-interaction")?!!t.getAttribute("data-disable-interaction"):e._options.disableInteraction,n>0&&(o[n-1]={element:t,title:t.getAttribute("data-title")||"",intro:t.getAttribute("data-intro"),step:parseInt(t.getAttribute("data-step"),10),tooltipClass:t.getAttribute("data-tooltipclass"),highlightClass:t.getAttribute("data-highlightclass"),position:t.getAttribute("data-position")||e._options.tooltipPosition,scrollTo:t.getAttribute("data-scrollto")||e._options.scrollTo,disableInteraction:r})}}));var a=0;n(i,(function(t){if((!e._options.group||t.getAttribute("data-intro-group")===e._options.group)&&null===t.getAttribute("data-step")){for(;void 0!==o[a];)a++;r=t.hasAttribute("data-disable-interaction")?!!t.getAttribute("data-disable-interaction"):e._options.disableInteraction,o[a]={element:t,title:t.getAttribute("data-title")||"",intro:t.getAttribute("data-intro"),step:a+1,tooltipClass:t.getAttribute("data-tooltipclass"),highlightClass:t.getAttribute("data-highlightclass"),position:t.getAttribute("data-position")||e._options.tooltipPosition,scrollTo:t.getAttribute("data-scrollto")||e._options.scrollTo,disableInteraction:r}}}))}for(var l=[],s=0;s<o.length;s++)o[s]&&l.push(o[s]);return(o=l).sort((function(t,e){return t.step-e.step})),o}function Tr(t){var e=document.querySelector(".introjs-tooltipReferenceLayer"),n=document.querySelector(".introjs-helperLayer"),i=document.querySelector(".introjs-disableInteraction");if(no.call(this,n),no.call(this,e),no.call(this,i),t&&(this._introItems=Ir.call(this,this._targetElement),$o.call(this,e,this._introItems[this._currentStep]),Go.call(this,e)),void 0!==this._currentStep&&null!==this._currentStep){var o=document.querySelector(".introjs-arrow"),r=document.querySelector(".introjs-tooltip");r&&o&&Lo.call(this,this._introItems[this._currentStep].element,r,o)}return hr.call(this),this}function Nr(){Tr.call(this)}function Or(t,e){if(t&&t.parentElement){var n=t.parentElement;e?(eo(t,{opacity:"0"}),window.setTimeout((function(){try{n.removeChild(t)}catch(t){}}),500)):n.removeChild(t)}}function Pr(t,e){var o=!0;if(void 0!==this._introBeforeExitCallback&&(o=this._introBeforeExitCallback.call(this)),e||!1!==o){var r=t.querySelectorAll(".introjs-overlay");r&&r.length&&n(r,(function(t){return Or(t)})),Or(t.querySelector(".introjs-helperLayer"),!0),Or(t.querySelector(".introjs-tooltipReferenceLayer")),Or(t.querySelector(".introjs-disableInteraction")),Or(document.querySelector(".introjsFloatingElement")),Ro(),i.off(window,"keydown",Jo,this,!0),i.off(window,"resize",Nr,this,!0),void 0!==this._introExitCallback&&this._introExitCallback.call(this),this._currentStep=void 0}}function Lr(t){var e=this,n=qo("div",{className:"introjs-overlay"});return eo(n,{top:0,bottom:0,left:0,right:0,position:"fixed"}),t.appendChild(n),!0===this._options.exitOnOverlayClick&&(eo(n,{cursor:"pointer"}),n.onclick=function(){Pr.call(e,t)}),!0}function Rr(t){void 0!==this._introStartCallback&&this._introStartCallback.call(this,t);var e=Ir.call(this,t);return 0===e.length||(this._introItems=e,Lr.call(this,t)&&(Yo.call(this),this._options.keyboardNavigation&&i.on(window,"keydown",Jo,this,!0),i.on(window,"resize",Nr,this,!0))),!1}Pe({target:"Array",proto:!0,forced:Cr||!kr||!Ar||!Er},{sort:function(t){void 0!==t&&X(t);var e=rt(this);if(Er)return void 0===t?Sr(e):Sr(e,t);var n,i,o=[],r=fe(e);for(i=0;i<r;i++)i in e&&jr(o,e[i]);for(vr(o,function(t){return function(e,n){return void 0===n?-1:void 0===e?1:void 0!==t?+t(e,n)||0:De(e)>De(n)?1:-1}}(t)),n=o.length,i=0;i<n;)e[i]=o[i++];for(;i<r;)delete e[i++];return e}});function qr(t){this._targetElement=t,this._introItems=[],this._options={nextLabel:"Next",prevLabel:"Back",skipLabel:"×",doneLabel:"Done",hidePrev:!1,hideNext:!1,nextToDone:!0,tooltipPosition:"bottom",tooltipClass:"",group:"",highlightClass:"",exitOnEsc:!0,exitOnOverlayClick:!0,showStepNumbers:!1,keyboardNavigation:!0,showButtons:!0,showBullets:!0,showProgress:!1,scrollToElement:!0,scrollTo:"element",scrollPadding:30,overlayOpacity:.5,autoPosition:!0,positionPrecedence:["bottom","top","right","left"],disableInteraction:!1,helperElementPadding:10,hintPosition:"top-middle",hintButtonLabel:"Got it",hintShowButton:!0,hintAutoRefreshInterval:10,hintAnimation:!0,buttonClass:"introjs-button",progressBarAdditionalClass:!1}}var Br=function n(i){var o;if("object"===t(i))o=new qr(i);else if("string"==typeof i){var r=document.querySelector(i);if(!r)throw new Error("There is no element with given selector.");o=new qr(r)}else o=new qr(document.body);return n.instances[e(o,"introjs-instance")]=o,o};return Br.version="4.3.0",Br.instances={},Br.fn=qr.prototype={clone:function(){return new qr(this)},setOption:function(t,e){return this._options[t]=e,this},setOptions:function(t){return this._options=function(t,e){var n,i={};for(n in t)i[n]=t[n];for(n in e)i[n]=e[n];return i}(this._options,t),this},start:function(){return Rr.call(this,this._targetElement),this},goToStep:function(t){return Wo.call(this,t),this},addStep:function(t){return this._options.steps||(this._options.steps=[]),this._options.steps.push(t),this},addSteps:function(t){if(t.length){for(var e=0;e<t.length;e++)this.addStep(t[e]);return this}},goToStepNumber:function(t){return Vo.call(this,t),this},nextStep:function(){return Yo.call(this),this},previousStep:function(){return Ko.call(this),this},currentStep:function(){return Xo.call(this)},exit:function(t){return Pr.call(this,this._targetElement,t),this},refresh:function(t){return Tr.call(this,t),this},onbeforechange:function(t){if("function"!=typeof t)throw new Error("Provided callback for onbeforechange was not a function");return this._introBeforeChangeCallback=t,this},onchange:function(t){if("function"!=typeof t)throw new Error("Provided callback for onchange was not a function.");return this._introChangeCallback=t,this},onafterchange:function(t){if("function"!=typeof t)throw new Error("Provided callback for onafterchange was not a function");return this._introAfterChangeCallback=t,this},oncomplete:function(t){if("function"!=typeof t)throw new Error("Provided callback for oncomplete was not a function.");return this._introCompleteCallback=t,this},onhintsadded:function(t){if("function"!=typeof t)throw new Error("Provided callback for onhintsadded was not a function.");return this._hintsAddedCallback=t,this},onhintclick:function(t){if("function"!=typeof t)throw new Error("Provided callback for onhintclick was not a function.");return this._hintClickCallback=t,this},onhintclose:function(t){if("function"!=typeof t)throw new Error("Provided callback for onhintclose was not a function.");return this._hintCloseCallback=t,this},onstart:function(t){if("function"!=typeof t)throw new Error("Provided callback for onstart was not a function.");return this._introStartCallback=t,this},onexit:function(t){if("function"!=typeof t)throw new Error("Provided callback for onexit was not a function.");return this._introExitCallback=t,this},onskip:function(t){if("function"!=typeof t)throw new Error("Provided callback for onskip was not a function.");return this._introSkipCallback=t,this},onbeforeexit:function(t){if("function"!=typeof t)throw new Error("Provided callback for onbeforeexit was not a function.");return this._introBeforeExitCallback=t,this},addHints:function(){return ur.call(this,this._targetElement),this},hideHint:function(t){return tr.call(this,t),this},hideHints:function(){return er.call(this),this},showHint:function(t){return ir.call(this,t),this},showHints:function(){return nr.call(this),this},removeHints:function(){return or.call(this),this},removeHint:function(t){return rr().call(this,t),this},showHintDialog:function(t){return sr.call(this,t),this}},Br}));

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/js-big-decimal/dist/node/js-big-decimal.js":
/*!*****************************************************************!*\
  !*** ./node_modules/js-big-decimal/dist/node/js-big-decimal.js ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/* WEBPACK VAR INJECTION */(function(global) {(function webpackUniversalModuleDefinition(root, factory) {
	if(true)
		module.exports = factory();
	else {}
})(global, function() {
return /******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ 217:
/***/ (function(__unused_webpack_module, exports) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.pad = exports.trim = exports.add = void 0;
//function add {
function add(number1, number2) {
    var _a;
    if (number2 === void 0) { number2 = "0"; }
    var neg = 0, ind = -1, neg_len;
    //check for negatives
    if (number1[0] == '-') {
        neg++;
        ind = 1;
        number1 = number1.substring(1);
        neg_len = number1.length;
    }
    if (number2[0] == '-') {
        neg++;
        ind = 2;
        number2 = number2.substring(1);
        neg_len = number2.length;
    }
    number1 = trim(number1);
    number2 = trim(number2);
    _a = pad(trim(number1), trim(number2)), number1 = _a[0], number2 = _a[1];
    if (neg == 1) {
        if (ind == 1)
            number1 = compliment(number1);
        else
            number2 = compliment(number2);
    }
    var res = addCore(number1, number2);
    if (!neg)
        return trim(res);
    else if (neg == 2)
        return ('-' + trim(res));
    else {
        if (number1.length < (res.length))
            return trim(res.substring(1));
        else
            return ('-' + trim(compliment(res)));
    }
}
exports.add = add;
function compliment(number) {
    var s = '', l = number.length, dec = number.split('.')[1], ld = dec ? dec.length : 0;
    for (var i = 0; i < l; i++) {
        if (number[i] >= '0' && number[i] <= '9')
            s += (9 - parseInt(number[i]));
        else
            s += number[i];
    }
    var one = (ld > 0) ? ('0.' + (new Array(ld)).join('0') + '1') : '1';
    return addCore(s, one);
}
function trim(number) {
    var parts = number.split('.');
    if (!parts[0])
        parts[0] = '0';
    while (parts[0][0] == '0' && parts[0].length > 1)
        parts[0] = parts[0].substring(1);
    return parts[0] + (parts[1] ? ('.' + parts[1]) : '');
}
exports.trim = trim;
function pad(number1, number2) {
    var parts1 = number1.split('.'), parts2 = number2.split('.');
    //pad integral part
    var length1 = parts1[0].length, length2 = parts2[0].length;
    if (length1 > length2) {
        parts2[0] = (new Array(Math.abs(length1 - length2) + 1)).join('0') + (parts2[0] ? parts2[0] : '');
    }
    else {
        parts1[0] = (new Array(Math.abs(length1 - length2) + 1)).join('0') + (parts1[0] ? parts1[0] : '');
    }
    //pad fractional part
    length1 = parts1[1] ? parts1[1].length : 0,
        length2 = parts2[1] ? parts2[1].length : 0;
    if (length1 || length2) {
        if (length1 > length2) {
            parts2[1] = (parts2[1] ? parts2[1] : '') + (new Array(Math.abs(length1 - length2) + 1)).join('0');
        }
        else {
            parts1[1] = (parts1[1] ? parts1[1] : '') + (new Array(Math.abs(length1 - length2) + 1)).join('0');
        }
    }
    number1 = parts1[0] + ((parts1[1]) ? ('.' + parts1[1]) : '');
    number2 = parts2[0] + ((parts2[1]) ? ('.' + parts2[1]) : '');
    return [number1, number2];
}
exports.pad = pad;
function addCore(number1, number2) {
    var _a;
    _a = pad(number1, number2), number1 = _a[0], number2 = _a[1];
    var sum = '', carry = 0;
    for (var i = number1.length - 1; i >= 0; i--) {
        if (number1[i] === '.') {
            sum = '.' + sum;
            continue;
        }
        var temp = parseInt(number1[i]) + parseInt(number2[i]) + carry;
        sum = (temp % 10) + sum;
        carry = Math.floor(temp / 10);
    }
    return carry ? (carry.toString() + sum) : sum;
}


/***/ }),

/***/ 423:
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {


var add_1 = __webpack_require__(217);
var round_1 = __webpack_require__(350);
var multiply_1 = __webpack_require__(182);
var divide_1 = __webpack_require__(415);
var modulus_1 = __webpack_require__(213);
var compareTo_1 = __webpack_require__(664);
var subtract_1 = __webpack_require__(26);
var roundingModes_1 = __webpack_require__(916);
var bigDecimal = /** @class */ (function () {
    function bigDecimal(number) {
        if (number === void 0) { number = '0'; }
        this.value = bigDecimal.validate(number);
    }
    bigDecimal.validate = function (number) {
        if (number) {
            number = number.toString();
            if (isNaN(number))
                throw Error("Parameter is not a number: " + number);
            if (number[0] == '+')
                number = number.substring(1);
        }
        else
            number = '0';
        //handle missing leading zero
        if (number.startsWith('.'))
            number = '0' + number;
        else if (number.startsWith('-.'))
            number = '-0' + number.substr(1);
        //handle exponentiation
        if (/e/i.test(number)) {
            var _a = number.split(/[eE]/), mantisa = _a[0], exponent = _a[1];
            mantisa = (0, add_1.trim)(mantisa);
            var sign = '';
            if (mantisa[0] == '-') {
                sign = '-';
                mantisa = mantisa.substring(1);
            }
            if (mantisa.indexOf('.') >= 0) {
                exponent = parseInt(exponent) + mantisa.indexOf('.');
                mantisa = mantisa.replace('.', '');
            }
            else {
                exponent = parseInt(exponent) + mantisa.length;
            }
            if (mantisa.length < exponent) {
                number = sign + mantisa + (new Array(exponent - mantisa.length + 1)).join('0');
            }
            else if (mantisa.length >= exponent && exponent > 0) {
                number = sign + (0, add_1.trim)(mantisa.substring(0, exponent)) +
                    ((mantisa.length > exponent) ? ('.' + mantisa.substring(exponent)) : '');
            }
            else {
                number = sign + '0.' + (new Array(-exponent + 1)).join('0') + mantisa;
            }
        }
        return number;
    };
    bigDecimal.prototype.getValue = function () {
        return this.value;
    };
    bigDecimal.getPrettyValue = function (number, digits, separator) {
        if (!(digits || separator)) {
            digits = 3;
            separator = ',';
        }
        else if (!(digits && separator)) {
            throw Error('Illegal Arguments. Should pass both digits and separator or pass none');
        }
        number = bigDecimal.validate(number);
        var neg = number.charAt(0) == '-';
        if (neg)
            number = number.substring(1);
        var len = number.indexOf('.');
        len = len > 0 ? len : (number.length);
        var temp = '';
        for (var i = len; i > 0;) {
            if (i < digits) {
                digits = i;
                i = 0;
            }
            else
                i -= digits;
            temp = number.substring(i, i + digits) + ((i < (len - digits) && i >= 0) ? separator : '') + temp;
        }
        return (neg ? '-' : '') + temp + number.substring(len);
    };
    bigDecimal.prototype.getPrettyValue = function (digits, separator) {
        return bigDecimal.getPrettyValue(this.value, digits, separator);
    };
    bigDecimal.round = function (number, precision, mode) {
        if (precision === void 0) { precision = 0; }
        if (mode === void 0) { mode = roundingModes_1.RoundingModes.HALF_EVEN; }
        number = bigDecimal.validate(number);
        // console.log(number)
        if (isNaN(precision))
            throw Error("Precision is not a number: " + precision);
        return (0, round_1.roundOff)(number, precision, mode);
    };
    bigDecimal.prototype.round = function (precision, mode) {
        if (precision === void 0) { precision = 0; }
        if (mode === void 0) { mode = roundingModes_1.RoundingModes.HALF_EVEN; }
        if (isNaN(precision))
            throw Error("Precision is not a number: " + precision);
        return new bigDecimal((0, round_1.roundOff)(this.value, precision, mode));
    };
    bigDecimal.floor = function (number) {
        number = bigDecimal.validate(number);
        if (number.indexOf('.') === -1)
            return number;
        return bigDecimal.round(number, 0, roundingModes_1.RoundingModes.FLOOR);
    };
    bigDecimal.prototype.floor = function () {
        if (this.value.indexOf('.') === -1)
            return new bigDecimal(this.value);
        return new bigDecimal(this.value).round(0, roundingModes_1.RoundingModes.FLOOR);
    };
    bigDecimal.ceil = function (number) {
        number = bigDecimal.validate(number);
        if (number.indexOf('.') === -1)
            return number;
        return bigDecimal.round(number, 0, roundingModes_1.RoundingModes.CEILING);
    };
    bigDecimal.prototype.ceil = function () {
        if (this.value.indexOf('.') === -1)
            return new bigDecimal(this.value);
        return new bigDecimal(this.value).round(0, roundingModes_1.RoundingModes.CEILING);
    };
    bigDecimal.add = function (number1, number2) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, add_1.add)(number1, number2);
    };
    bigDecimal.prototype.add = function (number) {
        return new bigDecimal((0, add_1.add)(this.value, number.getValue()));
    };
    bigDecimal.subtract = function (number1, number2) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, subtract_1.subtract)(number1, number2);
    };
    bigDecimal.prototype.subtract = function (number) {
        return new bigDecimal((0, subtract_1.subtract)(this.value, number.getValue()));
    };
    bigDecimal.multiply = function (number1, number2) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, multiply_1.multiply)(number1, number2);
    };
    bigDecimal.prototype.multiply = function (number) {
        return new bigDecimal((0, multiply_1.multiply)(this.value, number.getValue()));
    };
    bigDecimal.divide = function (number1, number2, precision) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, divide_1.divide)(number1, number2, precision);
    };
    bigDecimal.prototype.divide = function (number, precision) {
        return new bigDecimal((0, divide_1.divide)(this.value, number.getValue(), precision));
    };
    bigDecimal.modulus = function (number1, number2) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, modulus_1.modulus)(number1, number2);
    };
    bigDecimal.prototype.modulus = function (number) {
        return new bigDecimal((0, modulus_1.modulus)(this.value, number.getValue()));
    };
    bigDecimal.compareTo = function (number1, number2) {
        number1 = bigDecimal.validate(number1);
        number2 = bigDecimal.validate(number2);
        return (0, compareTo_1.compareTo)(number1, number2);
    };
    bigDecimal.prototype.compareTo = function (number) {
        return (0, compareTo_1.compareTo)(this.value, number.getValue());
    };
    bigDecimal.negate = function (number) {
        number = bigDecimal.validate(number);
        return (0, subtract_1.negate)(number);
    };
    bigDecimal.prototype.negate = function () {
        return new bigDecimal((0, subtract_1.negate)(this.value));
    };
    bigDecimal.RoundingModes = roundingModes_1.RoundingModes;
    return bigDecimal;
}());
module.exports = bigDecimal;


/***/ }),

/***/ 664:
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.compareTo = void 0;
var add_1 = __webpack_require__(217);
function compareTo(number1, number2) {
    var _a;
    var negative = false;
    if (number1[0] == '-' && number2[0] != "-") {
        return -1;
    }
    else if (number1[0] != '-' && number2[0] == '-') {
        return 1;
    }
    else if (number1[0] == '-' && number2[0] == '-') {
        number1 = number1.substr(1);
        number2 = number2.substr(1);
        negative = true;
    }
    _a = (0, add_1.pad)(number1, number2), number1 = _a[0], number2 = _a[1];
    if (number1.localeCompare(number2) == 0) {
        return 0;
    }
    for (var i = 0; i < number1.length; i++) {
        if (number1[i] == number2[i]) {
            continue;
        }
        else if (number1[i] > number2[i]) {
            if (negative) {
                return -1;
            }
            else {
                return 1;
            }
        }
        else {
            if (negative) {
                return 1;
            }
            else {
                return -1;
            }
        }
    }
    return 0;
}
exports.compareTo = compareTo;


/***/ }),

/***/ 415:
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.divide = void 0;
var add_1 = __webpack_require__(217);
var round_1 = __webpack_require__(350);
function divide(dividend, divisor, precission) {
    if (precission === void 0) { precission = 8; }
    if (divisor == 0) {
        throw new Error('Cannot divide by 0');
    }
    dividend = dividend.toString();
    divisor = divisor.toString();
    // remove trailing zeros in decimal ISSUE#18
    dividend = dividend.replace(/(\.\d*?[1-9])0+$/g, "$1").replace(/\.0+$/, "");
    divisor = divisor.replace(/(\.\d*?[1-9])0+$/g, "$1").replace(/\.0+$/, "");
    if (dividend == 0)
        return '0';
    var neg = 0;
    if (divisor[0] == '-') {
        divisor = divisor.substring(1);
        neg++;
    }
    if (dividend[0] == '-') {
        dividend = dividend.substring(1);
        neg++;
    }
    var pt_dvsr = divisor.indexOf('.') > 0 ? divisor.length - divisor.indexOf('.') - 1 : -1;
    divisor = (0, add_1.trim)(divisor.replace('.', ''));
    if (pt_dvsr >= 0) {
        var pt_dvnd = dividend.indexOf('.') > 0 ? dividend.length - dividend.indexOf('.') - 1 : -1;
        if (pt_dvnd == -1) {
            dividend = (0, add_1.trim)(dividend + (new Array(pt_dvsr + 1)).join('0'));
        }
        else {
            if (pt_dvsr > pt_dvnd) {
                dividend = dividend.replace('.', '');
                dividend = (0, add_1.trim)(dividend + (new Array(pt_dvsr - pt_dvnd + 1)).join('0'));
            }
            else if (pt_dvsr < pt_dvnd) {
                dividend = dividend.replace('.', '');
                var loc = dividend.length - pt_dvnd + pt_dvsr;
                dividend = (0, add_1.trim)(dividend.substring(0, loc) + '.' + dividend.substring(loc));
            }
            else if (pt_dvsr == pt_dvnd) {
                dividend = (0, add_1.trim)(dividend.replace('.', ''));
            }
        }
    }
    var prec = 0, dl = divisor.length, rem = '0', quotent = '';
    var dvnd = (dividend.indexOf('.') > -1 && dividend.indexOf('.') < dl) ? dividend.substring(0, dl + 1) : dividend.substring(0, dl);
    dividend = (dividend.indexOf('.') > -1 && dividend.indexOf('.') < dl) ? dividend.substring(dl + 1) : dividend.substring(dl);
    if (dvnd.indexOf('.') > -1) {
        var shift = dvnd.length - dvnd.indexOf('.') - 1;
        dvnd = dvnd.replace('.', '');
        if (dl > dvnd.length) {
            shift += dl - dvnd.length;
            dvnd = dvnd + (new Array(dl - dvnd.length + 1)).join('0');
        }
        prec = shift;
        quotent = '0.' + (new Array(shift)).join('0');
    }
    precission = precission + 2;
    while (prec <= precission) {
        var qt = 0;
        while (parseInt(dvnd) >= parseInt(divisor)) {
            dvnd = (0, add_1.add)(dvnd, '-' + divisor);
            qt++;
        }
        quotent += qt;
        if (!dividend) {
            if (!prec)
                quotent += '.';
            prec++;
            dvnd = dvnd + '0';
        }
        else {
            if (dividend[0] == '.') {
                quotent += '.';
                prec++;
                dividend = dividend.substring(1);
            }
            dvnd = dvnd + dividend.substring(0, 1);
            dividend = dividend.substring(1);
        }
    }
    return ((neg == 1) ? '-' : '') + (0, add_1.trim)((0, round_1.roundOff)(quotent, precission - 2));
}
exports.divide = divide;


/***/ }),

/***/ 213:
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.modulus = void 0;
var divide_1 = __webpack_require__(415);
var round_1 = __webpack_require__(350);
var multiply_1 = __webpack_require__(182);
var subtract_1 = __webpack_require__(26);
var roundingModes_1 = __webpack_require__(916);
function modulus(dividend, divisor) {
    if (divisor == 0) {
        throw new Error('Cannot divide by 0');
    }
    dividend = dividend.toString();
    divisor = divisor.toString();
    validate(dividend);
    validate(divisor);
    var sign = '';
    if (dividend[0] == '-') {
        sign = '-';
        dividend = dividend.substr(1);
    }
    if (divisor[0] == '-') {
        divisor = divisor.substr(1);
    }
    var result = (0, subtract_1.subtract)(dividend, (0, multiply_1.multiply)(divisor, (0, round_1.roundOff)((0, divide_1.divide)(dividend, divisor), 0, roundingModes_1.RoundingModes.FLOOR)));
    return sign + result;
}
exports.modulus = modulus;
function validate(oparand) {
    if (oparand.indexOf('.') != -1) {
        throw new Error('Modulus of non-integers not supported');
    }
}


/***/ }),

/***/ 182:
/***/ (function(__unused_webpack_module, exports) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.multiply = void 0;
function multiply(number1, number2) {
    number1 = number1.toString();
    number2 = number2.toString();
    /*Filter numbers*/
    var negative = 0;
    if (number1[0] == '-') {
        negative++;
        number1 = number1.substr(1);
    }
    if (number2[0] == '-') {
        negative++;
        number2 = number2.substr(1);
    }
    number1 = trailZero(number1);
    number2 = trailZero(number2);
    var decimalLength1 = 0;
    var decimalLength2 = 0;
    if (number1.indexOf('.') != -1) {
        decimalLength1 = number1.length - number1.indexOf('.') - 1;
    }
    if (number2.indexOf('.') != -1) {
        decimalLength2 = number2.length - number2.indexOf('.') - 1;
    }
    var decimalLength = decimalLength1 + decimalLength2;
    number1 = trailZero(number1.replace('.', ''));
    number2 = trailZero(number2.replace('.', ''));
    if (number1.length < number2.length) {
        var temp = number1;
        number1 = number2;
        number2 = temp;
    }
    if (number2 == '0') {
        return '0';
    }
    /*
    * Core multiplication
    */
    var length = number2.length;
    var carry = 0;
    var positionVector = [];
    var currentPosition = length - 1;
    var result = "";
    for (var i = 0; i < length; i++) {
        positionVector[i] = number1.length - 1;
    }
    for (var i = 0; i < 2 * number1.length; i++) {
        var sum = 0;
        for (var j = number2.length - 1; j >= currentPosition && j >= 0; j--) {
            if (positionVector[j] > -1 && positionVector[j] < number1.length) {
                sum += parseInt(number1[positionVector[j]--]) * parseInt(number2[j]);
            }
        }
        sum += carry;
        carry = Math.floor(sum / 10);
        result = sum % 10 + result;
        currentPosition--;
    }
    /*
    * Formatting result
    */
    result = trailZero(adjustDecimal(result, decimalLength));
    if (negative == 1) {
        result = '-' + result;
    }
    return result;
}
exports.multiply = multiply;
/*
* Add decimal point
*/
function adjustDecimal(number, decimal) {
    if (decimal == 0)
        return number;
    else {
        number = (decimal >= number.length) ? ((new Array(decimal - number.length + 1)).join('0') + number) : number;
        return number.substr(0, number.length - decimal) + '.' + number.substr(number.length - decimal, decimal);
    }
}
/*
* Removes zero from front and back*/
function trailZero(number) {
    while (number[0] == '0') {
        number = number.substr(1);
    }
    if (number.indexOf('.') != -1) {
        while (number[number.length - 1] == '0') {
            number = number.substr(0, number.length - 1);
        }
    }
    if (number == "" || number == ".") {
        number = '0';
    }
    else if (number[number.length - 1] == '.') {
        number = number.substr(0, number.length - 1);
    }
    if (number[0] == '.') {
        number = '0' + number;
    }
    return number;
}


/***/ }),

/***/ 350:
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.roundOff = void 0;
var roundingModes_1 = __webpack_require__(916);
/**
 *
 * @param input the number to round
 * @param n precision
 * @param mode Rounding Mode
 */
function roundOff(input, n, mode) {
    if (n === void 0) { n = 0; }
    if (mode === void 0) { mode = roundingModes_1.RoundingModes.HALF_EVEN; }
    if (mode === roundingModes_1.RoundingModes.UNNECESSARY) {
        throw new Error("UNNECESSARY Rounding Mode has not yet been implemented");
    }
    if (typeof (input) == 'number' || typeof (input) == 'bigint')
        input = input.toString();
    var neg = false;
    if (input[0] === '-') {
        neg = true;
        input = input.substring(1);
    }
    var parts = input.split('.'), partInt = parts[0], partDec = parts[1];
    //handle case of -ve n: roundOff(12564,-2)=12600
    if (n < 0) {
        n = -n;
        if (partInt.length <= n)
            return '0';
        else {
            var prefix = partInt.substr(0, partInt.length - n);
            input = prefix + '.' + partInt.substr(partInt.length - n) + partDec;
            prefix = roundOff(input, 0, mode);
            return (neg ? '-' : '') + prefix + (new Array(n + 1).join('0'));
        }
    }
    // handle case when integer output is desired
    if (n == 0) {
        var l = partInt.length;
        if (greaterThanFive(parts[1], partInt, neg, mode)) {
            partInt = increment(partInt);
        }
        return (neg && parseInt(partInt) ? '-' : '') + partInt;
    }
    // handle case when n>0
    if (!parts[1]) {
        return (neg ? '-' : '') + partInt + '.' + (new Array(n + 1).join('0'));
    }
    else if (parts[1].length < n) {
        return (neg ? '-' : '') + partInt + '.' + parts[1] + (new Array(n - parts[1].length + 1).join('0'));
    }
    partDec = parts[1].substring(0, n);
    var rem = parts[1].substring(n);
    if (rem && greaterThanFive(rem, partDec, neg, mode)) {
        partDec = increment(partDec);
        if (partDec.length > n) {
            return (neg ? '-' : '') + increment(partInt, parseInt(partDec[0])) + '.' + partDec.substring(1);
        }
    }
    return (neg && parseInt(partInt) ? '-' : '') + partInt + '.' + partDec;
}
exports.roundOff = roundOff;
function greaterThanFive(part, pre, neg, mode) {
    if (!part || part === new Array(part.length + 1).join('0'))
        return false;
    // #region UP, DOWN, CEILING, FLOOR 
    if (mode === roundingModes_1.RoundingModes.DOWN || (!neg && mode === roundingModes_1.RoundingModes.FLOOR) ||
        (neg && mode === roundingModes_1.RoundingModes.CEILING))
        return false;
    if (mode === roundingModes_1.RoundingModes.UP || (neg && mode === roundingModes_1.RoundingModes.FLOOR) ||
        (!neg && mode === roundingModes_1.RoundingModes.CEILING))
        return true;
    // #endregion
    // case when part !== five
    var five = '5' + (new Array(part.length).join('0'));
    if (part > five)
        return true;
    else if (part < five)
        return false;
    // case when part === five
    switch (mode) {
        case roundingModes_1.RoundingModes.HALF_DOWN: return false;
        case roundingModes_1.RoundingModes.HALF_UP: return true;
        case roundingModes_1.RoundingModes.HALF_EVEN:
        default: return (parseInt(pre[pre.length - 1]) % 2 == 1);
    }
}
function increment(part, c) {
    if (c === void 0) { c = 0; }
    if (!c)
        c = 1;
    if (typeof (part) == 'number')
        part.toString();
    var l = part.length - 1, s = '';
    for (var i = l; i >= 0; i--) {
        var x = parseInt(part[i]) + c;
        if (x == 10) {
            c = 1;
            x = 0;
        }
        else {
            c = 0;
        }
        s += x;
    }
    if (c)
        s += c;
    return s.split('').reverse().join('');
}


/***/ }),

/***/ 916:
/***/ (function(__unused_webpack_module, exports) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.RoundingModes = void 0;
var RoundingModes;
(function (RoundingModes) {
    /**
     * Rounding mode to round towards positive infinity.
     */
    RoundingModes[RoundingModes["CEILING"] = 0] = "CEILING";
    /**
     * Rounding mode to round towards zero.
     */
    RoundingModes[RoundingModes["DOWN"] = 1] = "DOWN";
    /**
     * Rounding mode to round towards negative infinity.
     */
    RoundingModes[RoundingModes["FLOOR"] = 2] = "FLOOR";
    /**
     * Rounding mode to round towards "nearest neighbor" unless both neighbors are equidistant,
     * in which case round down.
     */
    RoundingModes[RoundingModes["HALF_DOWN"] = 3] = "HALF_DOWN";
    /**
     * Rounding mode to round towards the "nearest neighbor" unless both neighbors are equidistant,
     * in which case, round towards the even neighbor.
     */
    RoundingModes[RoundingModes["HALF_EVEN"] = 4] = "HALF_EVEN";
    /**
     * Rounding mode to round towards "nearest neighbor" unless both neighbors are equidistant,
     * in which case round up.
     */
    RoundingModes[RoundingModes["HALF_UP"] = 5] = "HALF_UP";
    /**
     * Rounding mode to assert that the requested operation has an exact result, hence no rounding is necessary.
     * UNIMPLEMENTED
     */
    RoundingModes[RoundingModes["UNNECESSARY"] = 6] = "UNNECESSARY";
    /**
     * Rounding mode to round away from zero.
     */
    RoundingModes[RoundingModes["UP"] = 7] = "UP";
})(RoundingModes = exports.RoundingModes || (exports.RoundingModes = {}));


/***/ }),

/***/ 26:
/***/ (function(__unused_webpack_module, exports, __webpack_require__) {


Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.negate = exports.subtract = void 0;
var add_1 = __webpack_require__(217);
function subtract(number1, number2) {
    number1 = number1.toString();
    number2 = number2.toString();
    number2 = negate(number2);
    return (0, add_1.add)(number1, number2);
}
exports.subtract = subtract;
function negate(number) {
    if (number[0] == '-') {
        number = number.substr(1);
    }
    else {
        number = '-' + number;
    }
    return number;
}
exports.negate = negate;


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module is referenced by other modules so it can't be inlined
/******/ 	var __webpack_exports__ = __webpack_require__(423);
/******/ 	
/******/ 	return __webpack_exports__;
/******/ })()
;
});
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! ./../../../webpack/buildin/global.js */ "./node_modules/webpack/buildin/global.js")))

/***/ }),

/***/ "./node_modules/webpack/buildin/global.js":
/*!***********************************!*\
  !*** (webpack)/buildin/global.js ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || new Function("return this")();
} catch (e) {
	// This works if the window reference is available
	if (typeof window === "object") g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ })

/******/ });
//# sourceMappingURL=atum-stock-central-kb.js.map