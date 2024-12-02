/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/src/components/_admin-modal.ts":
/*!**************************************************!*\
  !*** ./assets/js/src/components/_admin-modal.ts ***!
  \**************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _config_constants__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../config/_constants */ "./assets/js/src/config/_constants.ts");
/* harmony import */ var sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! sweetalert2-neutral */ "sweetalert2-neutral");
/* harmony import */ var sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_1__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
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
var __awaiter = (undefined && undefined.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (undefined && undefined.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};


var AdminModal = (function () {
    function AdminModal(settings) {
        this.settings = settings;
        this.defaultSwalOptions = {
            icon: 'info',
            confirmButtonColor: _config_constants__WEBPACK_IMPORTED_MODULE_0__.COLORS.primary,
            focusConfirm: false,
            showCloseButton: true,
        };
        this.swalConfigs = {};
        this.swalConfigs = this.settings.get('swal_configs');
        if (!window.hasOwnProperty('atum')) {
            window['atum'] = {};
        }
        window['atum']['AdminModal'] = this;
        this.showModal();
    }
    AdminModal.prototype.showModal = function () {
        return __awaiter(this, void 0, void 0, function () {
            var swalOpts, steps, stepNumbers, step, swalMixin, counter, _a, _b, _c, _i, key;
            return __generator(this, function (_d) {
                switch (_d.label) {
                    case 0:
                        swalOpts = __assign({}, this.defaultSwalOptions);
                        steps = Object.keys(this.swalConfigs).length;
                        if (steps > 1) {
                            stepNumbers = [];
                            for (step = 1; step <= steps; step++) {
                                stepNumbers.push(step.toString());
                            }
                            swalOpts.progressSteps = stepNumbers;
                            swalOpts.showClass = { backdrop: 'swal2-noanimation' };
                            swalOpts.hideClass = { backdrop: 'swal2-noanimation' };
                        }
                        swalMixin = sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_1___default().mixin(this.defaultSwalOptions);
                        counter = 1;
                        _a = this.swalConfigs;
                        _b = [];
                        for (_c in _a)
                            _b.push(_c);
                        _i = 0;
                        _d.label = 1;
                    case 1:
                        if (!(_i < _b.length)) return [3, 4];
                        _c = _b[_i];
                        if (!(_c in _a)) return [3, 3];
                        key = _c;
                        return [4, swalMixin.fire(__assign({ currentProgressStep: counter }, this.swalConfigs[key]))];
                    case 2:
                        _d.sent();
                        this.hideModal(key);
                        counter++;
                        _d.label = 3;
                    case 3:
                        _i++;
                        return [3, 1];
                    case 4: return [2];
                }
            });
        });
    };
    AdminModal.prototype.hideModal = function (key) {
        $.ajax({
            url: window['ajaxurl'],
            dataType: 'json',
            method: 'post',
            data: {
                action: 'atum_hide_atum_admin_modal',
                security: this.settings.get('nonce'),
                key: key,
            },
        });
    };
    return AdminModal;
}());
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AdminModal);


/***/ }),

/***/ "./assets/js/src/config/_constants.ts":
/*!********************************************!*\
  !*** ./assets/js/src/config/_constants.ts ***!
  \********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   COLORS: () => (/* binding */ COLORS)
/* harmony export */ });
var COLORS;
(function (COLORS) {
    COLORS["success"] = "#69C61D";
    COLORS["primary"] = "#00B8DB";
    COLORS["warning"] = "#EFAF00";
    COLORS["danger"] = "#FF4848";
    COLORS["grey"] = "#ADB5BD";
})(COLORS || (COLORS = {}));


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

/***/ "sweetalert2-neutral":
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
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***************************************!*\
  !*** ./assets/js/src/admin-modals.ts ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_admin_modal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/_admin-modal */ "./assets/js/src/components/_admin-modal.ts");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");


jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_0__["default"]('atumAdminModalVars');
    new _components_admin_modal__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
});

})();

/******/ })()
;
//# sourceMappingURL=atum-admin-modals.js.map