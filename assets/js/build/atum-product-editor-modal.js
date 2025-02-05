/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

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
/*!***********************************************!*\
  !*** ./assets/js/src/product-editor-modal.ts ***!
  \***********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _config_constants__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_constants */ "./assets/js/src/config/_constants.ts");
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! sweetalert2-neutral */ "sweetalert2-neutral");
/* harmony import */ var sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_2__);
/* provided dependency */ var $ = __webpack_require__(/*! jquery */ "jquery");
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");



var ProductEditorModal = (function () {
    function ProductEditorModal(settings) {
        this.settings = settings;
        this.bindEvents();
    }
    ProductEditorModal.prototype.bindEvents = function () {
        var _this = this;
        $('body').on('change', '#woocommerce_feature_product_block_editor_enabled', function (evt) {
            var $checkbox = $(evt.currentTarget);
            if ($checkbox.is(':checked')) {
                _this.showModal();
            }
        });
    };
    ProductEditorModal.prototype.showModal = function () {
        sweetalert2_neutral__WEBPACK_IMPORTED_MODULE_2___default().fire({
            icon: 'warning',
            title: this.settings.get('title'),
            html: this.settings.get('text'),
            confirmButtonText: this.settings.get('confirm'),
            showCancelButton: true,
            cancelButtonText: this.settings.get('cancel'),
            confirmButtonColor: _config_constants__WEBPACK_IMPORTED_MODULE_0__.COLORS.warning,
            cancelButtonColor: _config_constants__WEBPACK_IMPORTED_MODULE_0__.COLORS.primary,
            focusConfirm: false,
            allowEscapeKey: false,
            allowOutsideClick: false,
            allowEnterKey: false,
        })
            .then(function (result) {
            if (result.isDismissed) {
                $('#woocommerce_feature_product_block_editor_enabled').prop('checked', false);
            }
        });
    };
    return ProductEditorModal;
}());
jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_1__["default"]('atumProductEditorModalVars');
    new ProductEditorModal(settings);
});

})();

/******/ })()
;
//# sourceMappingURL=atum-product-editor-modal.js.map