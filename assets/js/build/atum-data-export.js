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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/data-export.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/components/export/_export.ts":
/*!****************************************************!*\
  !*** ./assets/js/src/components/export/_export.ts ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var _utils_utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../utils/_utils */ "./assets/js/src/utils/_utils.ts");

var DataExport = (function () {
    function DataExport(settings) {
        var _this = this;
        this.settings = settings;
        this.$pageWrapper = $('#wpbody-content');
        this.$tabContentWrapper = $('#screen-meta');
        this.$tabsWrapper = $('#screen-meta-links');
        this.createExportTab();
        this.$pageWrapper
            .on('submit', '#atum-export-settings', function (evt) {
            evt.preventDefault();
            _this.downloadReport();
        })
            .on('change', '#disableMaxLength', function (evt) {
            var $checkbox = $(evt.currentTarget), $input = $checkbox.parent().siblings('input[type=number]');
            if ($checkbox.is(':checked')) {
                $input.prop('disabled', true);
            }
            else {
                $input.prop('disabled', false);
            }
        });
    }
    DataExport.prototype.createExportTab = function () {
        var $tab = this.$tabsWrapper.find('#screen-options-link-wrap').clone(), $tabContent = this.$tabContentWrapper.find('#screen-options-wrap').clone();
        $tabContent.attr({
            'id': 'atum-export-wrap',
            'aria-label': this.settings.get('tabTitle'),
        });
        $tabContent.find('form').attr('id', 'atum-export-settings').find('input').removeAttr('id');
        $tabContent.find('.screen-options').remove();
        $tabContent.find('input[type=submit]').val(this.settings.get('submitTitle'));
        $tabContent.find('#screenoptionnonce').remove();
        if (typeof this.settings.get('productTypes') !== 'undefined') {
            var $typeFieldset = $('<fieldset class="product-type" />');
            $typeFieldset.append("<legend>" + this.settings.get('productTypesTitle') + "</legend>");
            $typeFieldset.append(this.settings.get('productTypes'));
            $typeFieldset.insertAfter($tabContent.find('fieldset').last());
        }
        if (typeof this.settings.get('categories') !== 'undefined') {
            var $catFieldset = $('<fieldset class="product-category" />');
            $catFieldset.append("<legend>" + this.settings.get('categoriesTitle') + "</legend>");
            $catFieldset.append(this.settings.get('categories'));
            $catFieldset.insertAfter($tabContent.find('fieldset').last());
        }
        var $titleLengthFieldset = $('<fieldset class="title-length" />');
        $titleLengthFieldset.append("<legend>" + this.settings.get('titleLength') + "</legend>");
        $titleLengthFieldset.append("<input type=\"number\" step=\"1\" min=\"0\" name=\"title_max_length\" value=\"" + this.settings.get('maxLength') + "\"> ");
        $titleLengthFieldset.append("<label><input type=\"checkbox\" id=\"disableMaxLength\" value=\"yes\">" + this.settings.get('disableMaxLength') + "</label>");
        $titleLengthFieldset.insertAfter($tabContent.find('fieldset').last());
        var $formatFieldset = $('<fieldset class="output-format" />');
        $formatFieldset.append("<legend>" + this.settings.get('outputFormatTitle') + "</legend>");
        $.each(this.settings.get('outputFormats'), function (key, value) {
            $formatFieldset.append("<label><input type=\"radio\" name=\"output-format\" value=\"" + key + "\">" + value + "</label>");
        });
        $formatFieldset.find('input[name=output-format]').first().prop('checked', true);
        $formatFieldset.insertAfter($tabContent.find('fieldset').last());
        $tabContent.find('.submit').before('<div class="clear"></div>');
        $tab.attr('id', 'atum-export-link-wrap')
            .find('button').attr({
            'id': 'show-export-settings-link',
            'aria-controls': 'atum-export-wrap'
        }).text(this.settings.get('tabTitle'));
        this.$tabContentWrapper.append($tabContent);
        this.$tabsWrapper.prepend($tab);
        $('#show-export-settings-link').click(window['screenMeta'].toggleEvent);
        this.$exportForm = this.$pageWrapper.find('#atum-export-settings');
    };
    DataExport.prototype.downloadReport = function () {
        window.open(window['ajaxurl'] + "?action=atum_export_data&page=" + _utils_utils__WEBPACK_IMPORTED_MODULE_0__["default"].getUrlParameter('page') + "&screen=" + this.settings.get('screen') + "&security=" + this.settings.get('exportNonce') + "&" + this.$exportForm.serialize(), '_blank');
    };
    return DataExport;
}());
/* harmony default export */ __webpack_exports__["default"] = (DataExport);

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

/***/ "./assets/js/src/data-export.ts":
/*!**************************************!*\
  !*** ./assets/js/src/data-export.ts ***!
  \**************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_export_export__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/export/_export */ "./assets/js/src/components/export/_export.ts");


jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_0__["default"]('atumExport');
    new _components_export_export__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
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
/* WEBPACK VAR INJECTION */(function($) {var __assign = (undefined && undefined.__assign) || function () {
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
    addNotice: function (type, msg) {
        var $notice = $("<div class=\"" + type + " notice is-dismissible\"><p><strong>" + msg + "</strong></p></div>").hide(), $dismissButton = $('<button />', { type: 'button', class: 'notice-dismiss' }), $headerEnd = $('.wp-header-end');
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
    formatNumber: function (number, precision, thousand, decimal) {
        var _this = this;
        if (Array.isArray(number)) {
            return $.map(number, function (val) { return _this.formatNumber(val, precision, thousand, decimal); });
        }
        number = this.unformat(number);
        var defaults = __assign({}, this.settings.number), paramOpts = typeof decimal === 'undefined' ? { precision: precision, thousand: thousand } : { precision: precision, thousand: thousand, decimal: decimal }, opts = __assign(__assign({}, defaults), paramOpts), usePrecision = this.checkPrecision(opts.precision), negative = number < 0 ? '-' : '', base = parseInt(this.toFixed(Math.abs(number || 0), usePrecision), 10) + '', mod = base.length > 3 ? base.length % 3 : 0;
        return negative + (mod ? base.substr(0, mod) + opts.thousand : '') + base.substr(mod).replace(/(\d{3})(?=\d)/g, '$1' + opts.thousand) + (usePrecision ? opts.decimal + this.toFixed(Math.abs(number), usePrecision).split('.')[1] : '');
    },
    formatMoney: function (number, symbol, precision, thousand, decimal, format) {
        var _this = this;
        if (Array.isArray(number)) {
            return $.map(number, function (val) { return _this.formatMoney(val, symbol, precision, thousand, decimal, format); });
        }
        number = this.unformat(number);
        var defaults = __assign({}, this.settings.currency), opts = __assign({ defaults: defaults }, {
            symbol: symbol,
            precision: precision,
            thousand: thousand,
            decimal: decimal,
            format: format,
        }), formats = this.checkCurrencyFormat(opts.format), useFormat = number > 0 ? formats.pos : number < 0 ? formats.neg : formats.zero;
        return useFormat.replace('%s', opts.symbol).replace('%v', this.formatNumber(Math.abs(number), this.checkPrecision(opts.precision), opts.thousand, opts.decimal));
    },
    unformat: function (value, decimal) {
        var _this = this;
        if (Array.isArray(value)) {
            return $.map(value, function (val) { return _this.unformat(val, decimal); });
        }
        value = value || 0;
        if (typeof value === 'number') {
            return value;
        }
        decimal = decimal || this.settings.number.decimal;
        var regex = new RegExp("[^0-9-" + decimal + "]", 'g'), unformatted = parseFloat(('' + value)
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
        if (value < min) {
            $input.val(min);
        }
        else if (value > max) {
            $input.val(max);
        }
        else if (qty === '') {
            $input.val(0);
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
                break;
        }
    }
};
/* harmony default export */ __webpack_exports__["default"] = (Utils);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

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
//# sourceMappingURL=atum-data-export.js.map