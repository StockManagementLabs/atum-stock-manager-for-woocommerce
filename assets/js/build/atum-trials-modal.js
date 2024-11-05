/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/src/components/addons/_trials.ts":
/*!****************************************************!*\
  !*** ./assets/js/src/components/addons/_trials.ts ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! sweetalert2 */ "sweetalert2");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(sweetalert2__WEBPACK_IMPORTED_MODULE_0__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");

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
        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default().fire({
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
                        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default().showValidationMessage(response.data);
                    }
                    else {
                        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default().fire({
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Trials);


/***/ }),

/***/ "./assets/js/src/config/_settings.ts":
/*!*******************************************!*\
  !*** ./assets/js/src/config/_settings.ts ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Settings);


/***/ }),

/***/ "sweetalert2":
/*!***********************!*\
  !*** external "Swal" ***!
  \***********************/
/***/ ((module) => {

module.exports = Swal;

/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

module.exports = jQuery;

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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./assets/js/src/trials-modal.ts ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_addons_trials__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/addons/_trials */ "./assets/js/src/components/addons/_trials.ts");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");


jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_0__["default"]('atumTrialsModal');
    new _components_addons_trials__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
});

})();

/******/ })()
;
//# sourceMappingURL=atum-trials-modal.js.map