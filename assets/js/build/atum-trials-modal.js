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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/trials-modal.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/components/addons/_trials.ts":
/*!****************************************************!*\
  !*** ./assets/js/src/components/addons/_trials.ts ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! sweetalert2 */ "sweetalert2");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(sweetalert2__WEBPACK_IMPORTED_MODULE_0__);

var Trials = (function () {
    function Trials(settings, successCallback) {
        this.settings = settings;
        this.successCallback = successCallback;
        this.bindEvents();
    }
    Trials.prototype.bindEvents = function () {
        var _this = this;
        $('body').on('click', '.extend-atum-trial', function (evt) {
            evt.preventDefault();
            evt.stopImmediatePropagation();
            var $button = $(evt.currentTarget);
            _this.extendTrialConfirmation($button.closest('.atum-addon').data('addon'), $button.data('key'));
        });
    };
    Trials.prototype.extendTrialConfirmation = function (addon, key) {
        var _this = this;
        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
            title: this.settings.get('trialExtension'),
            text: this.settings.get('trialWillExtend'),
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: this.settings.get('extend'),
            cancelButtonText: this.settings.get('cancel'),
            showCloseButton: true,
            allowEnterKey: false,
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return _this.extendTrial(addon, key, true, function (response) {
                    if (!response.success) {
                        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.showValidationMessage(response.data);
                    }
                    else {
                        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
                            title: _this.settings.get('success'),
                            html: response.data,
                            icon: 'success',
                            confirmButtonText: _this.settings.get('ok'),
                        })
                            .then(function (result) {
                            if (_this.successCallback && result.isConfirmed) {
                                _this.successCallback();
                            }
                        });
                    }
                });
            },
        });
    };
    Trials.prototype.extendTrial = function (addon, key, isSwal, callback) {
        var _this = this;
        if (isSwal === void 0) { isSwal = false; }
        if (callback === void 0) { callback = null; }
        return new Promise(function (resolve) {
            $.ajax({
                url: window['ajaxurl'],
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'atum_extend_trial',
                    security: _this.settings.get('nonce'),
                    addon: addon,
                    key: key,
                },
                success: function (response) {
                    if (callback) {
                        callback(response);
                    }
                    resolve();
                },
            });
        });
    };
    return Trials;
}());
/* harmony default export */ __webpack_exports__["default"] = (Trials);

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

/***/ "./assets/js/src/trials-modal.ts":
/*!***************************************!*\
  !*** ./assets/js/src/trials-modal.ts ***!
  \***************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_addons_trials__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/addons/_trials */ "./assets/js/src/components/addons/_trials.ts");


jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_0__["default"]('atumTrialsModal');
    new _components_addons_trials__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
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
//# sourceMappingURL=atum-trials-modal.js.map