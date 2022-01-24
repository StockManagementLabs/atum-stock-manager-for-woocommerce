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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/marketing-popup.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/components/_marketing-popup.ts":
/*!******************************************************!*\
  !*** ./assets/js/src/components/_marketing-popup.ts ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! sweetalert2 */ "sweetalert2");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(sweetalert2__WEBPACK_IMPORTED_MODULE_0__);

var MarketingPopup = (function () {
    function MarketingPopup(settings) {
        this.settings = settings;
        this.getPopupInfo();
    }
    MarketingPopup.prototype.getPopupInfo = function () {
        var _this = this;
        $.ajax({
            url: window['ajaxurl'],
            dataType: 'json',
            method: 'post',
            data: {
                action: 'atum_get_marketing_popup_info',
                security: this.settings.get('nonce'),
            },
            success: function (response) {
                if (response.success === true) {
                    var popupSettings = response.data, descriptionColor = popupSettings.description.text_color ? "color:" + popupSettings.description.text_color + ";" : '', descriptionFontSize = popupSettings.description.text_size ? "font-size:" + popupSettings.description.text_size + ";" : '', descriptionAlign = popupSettings.description.text_align ? "text-align:" + popupSettings.description.text_align + ";" : '', descriptionPadding = popupSettings.description.padding ? "padding:" + popupSettings.description.padding + ";" : '', description = "<p data-transient-key=\"" + popupSettings.transient_key + "\" style=\"" + (descriptionColor + descriptionFontSize + descriptionAlign + descriptionPadding) + "\">" + popupSettings.description.text + "</p>", titleColor = popupSettings.title.text_color ? "color:" + popupSettings.title.text_color + ";" : '', titleFontSize = popupSettings.title.text_size ? "font-size:" + popupSettings.title.text_size + ";" : '', titleAlign = popupSettings.title.text_align ? "text-align:" + popupSettings.title.text_align + ";" : '', hoverButtons = popupSettings.hoverButtons || '', imageTopLeft = popupSettings.images.top_left, footerNoticeStyle = popupSettings.footerNotice.bg_color ? " style=\"background-color:" + popupSettings.footerNotice.bg_color + ";\"" : '', footerNotice = popupSettings.footerNotice.text ? "<div class=\"footer-notice\"" + footerNoticeStyle + ">" + popupSettings.footerNotice.text + "</div>" : '';
                    var logo = "<img class=\"mp-logo\" src=\"" + popupSettings.images.logo + "\">", versionColor = '', versionBackground = '', version = '', buttons_1 = '';
                    if (popupSettings.images.hasOwnProperty('logo_css') && popupSettings.images.logo_css) {
                        logo = logo.replace('>', " style=\"" + popupSettings.images.logo_css + "\">");
                    }
                    if (popupSettings.version && Object.keys(popupSettings.version).length) {
                        versionColor = popupSettings.version.text_color ? "color:" + popupSettings.version.text_color + ";" : '';
                        versionBackground = popupSettings.version.background ? "background:" + popupSettings.version.background + ";" : '';
                        version = "<span class=\"version\" style=\"" + (versionBackground + versionColor) + "\">" + popupSettings.version.text + "</span>";
                    }
                    var title = popupSettings.title.text ? "<h1 style=\"" + (titleColor + titleFontSize + titleAlign) + "\"><span>" + (popupSettings.title.text + version) + "</span></h1>" : '';
                    if (popupSettings.buttons && popupSettings.buttons.length) {
                        if (hoverButtons) {
                            $(hoverButtons).appendTo('body');
                        }
                        popupSettings.buttons.forEach(function (button) {
                            buttons_1 += "<button data-url=\"" + button.url + "\" class=\"" + button.class + " popup-button\" style=\"" + button.css + "\">" + button.text + "</button>";
                        });
                    }
                    sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
                        width: 520,
                        padding: null,
                        customClass: {
                            popup: 'marketing-popup',
                        },
                        background: popupSettings.background,
                        showCloseButton: true,
                        showConfirmButton: false,
                        html: logo + title + description + buttons_1 + footerNotice,
                        imageUrl: imageTopLeft,
                    });
                    $('.popup-button').click(function (evt) {
                        evt.preventDefault();
                        window.open($(evt.currentTarget).data('url'), '_blank');
                    });
                    $('.marketing-popup .swal2-close').click(function () {
                        _this.hideMarketingPopup($('.swal2-content p').data('transient-key'));
                    });
                }
            },
        });
    };
    MarketingPopup.prototype.hideMarketingPopup = function (transientKey) {
        $.ajax({
            url: window['ajaxurl'],
            dataType: 'json',
            method: 'post',
            data: {
                action: 'atum_hide_marketing_popup',
                security: this.settings.get('nonce'),
                transientKey: transientKey,
            },
        });
    };
    return MarketingPopup;
}());
/* harmony default export */ __webpack_exports__["default"] = (MarketingPopup);

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

/***/ "./assets/js/src/marketing-popup.ts":
/*!******************************************!*\
  !*** ./assets/js/src/marketing-popup.ts ***!
  \******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var _components_marketing_popup__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/_marketing-popup */ "./assets/js/src/components/_marketing-popup.ts");
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");


jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_1__["default"]('atumMarketingPopupVars');
    new _components_marketing_popup__WEBPACK_IMPORTED_MODULE_0__["default"](settings);
});

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = jQuery;

/***/ }),

/***/ "sweetalert2":
/*!***********************!*\
  !*** external "Swal" ***!
  \***********************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = Swal;

/***/ })

/******/ });
//# sourceMappingURL=atum-marketing-popup.js.map