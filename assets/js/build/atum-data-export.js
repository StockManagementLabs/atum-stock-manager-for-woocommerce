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
            $typeFieldset.append("<legend>".concat(this.settings.get('productTypesTitle'), "</legend>"));
            $typeFieldset.append(this.settings.get('productTypes'));
            $typeFieldset.insertAfter($tabContent.find('fieldset').last());
        }
        if (typeof this.settings.get('categories') !== 'undefined') {
            var $catFieldset = $('<fieldset class="product-category" />');
            $catFieldset.append("<legend>".concat(this.settings.get('categoriesTitle'), "</legend>"));
            $catFieldset.append(this.settings.get('categories'));
            $catFieldset.insertAfter($tabContent.find('fieldset').last());
        }
        var $titleLengthFieldset = $('<fieldset class="title-length" />');
        $titleLengthFieldset.append("<legend>".concat(this.settings.get('titleLength'), "</legend>"));
        $titleLengthFieldset.append("<input type=\"number\" step=\"1\" min=\"0\" name=\"title_max_length\" value=\"".concat(this.settings.get('maxLength'), "\"> "));
        $titleLengthFieldset.append("<label><input type=\"checkbox\" id=\"disableMaxLength\" value=\"yes\">".concat(this.settings.get('disableMaxLength'), "</label>"));
        $titleLengthFieldset.insertAfter($tabContent.find('fieldset').last());
        var $formatFieldset = $('<fieldset class="output-format" />');
        $formatFieldset.append("<legend>".concat(this.settings.get('outputFormatTitle'), "</legend>"));
        $.each(this.settings.get('outputFormats'), function (key, value) {
            $formatFieldset.append("<label><input type=\"radio\" name=\"output-format\" value=\"".concat(key, "\">").concat(value, "</label>"));
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
        window.open("".concat(window['ajaxurl'], "?action=atum_export_data&page=").concat(_utils_utils__WEBPACK_IMPORTED_MODULE_0__["default"].getUrlParameter('page'), "&screen=").concat(this.settings.get('screen'), "&security=").concat(this.settings.get('exportNonce'), "&").concat(this.$exportForm.serialize()), '_blank');
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
};
/* harmony default export */ __webpack_exports__["default"] = (Utils);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

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
//# sourceMappingURL=atum-data-export.js.map