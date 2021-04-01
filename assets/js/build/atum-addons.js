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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/addons.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/addons.ts":
/*!*********************************!*\
  !*** ./assets/js/src/addons.ts ***!
  \*********************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");
/* harmony import */ var _components_addons_page_addons_page__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/addons-page/_addons-page */ "./assets/js/src/components/addons-page/_addons-page.ts");


jQuery(function ($) {
    window['$'] = $;
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_0__["default"]('atumAddons');
    new _components_addons_page_addons_page__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
});


/***/ }),

/***/ "./assets/js/src/components/addons-page/_addons-page.ts":
/*!**************************************************************!*\
  !*** ./assets/js/src/components/addons-page/_addons-page.ts ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! sweetalert2 */ "sweetalert2");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(sweetalert2__WEBPACK_IMPORTED_MODULE_0__);

var AddonsPage = (function () {
    function AddonsPage(settings) {
        var _this = this;
        this.settings = settings;
        this.$addonsList = $('.atum-addons');
        this.$addonsList
            .on('click', '.addon-key button', function (evt) {
            evt.preventDefault();
            var $button = $(evt.currentTarget), key = $button.siblings('input').val();
            if (!key) {
                _this.showErrorAlert(_this.settings.get('invalidKey'));
                return false;
            }
            if ($button.hasClass('deactivate-key')) {
                sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
                    title: _this.settings.get('limitedDeactivations'),
                    html: _this.settings.get('allowedDeactivations'),
                    icon: 'warning',
                    confirmButtonText: _this.settings.get('continue'),
                    cancelButtonText: _this.settings.get('cancel'),
                    showCancelButton: true,
                }).then(function (result) {
                    if (result.isConfirmed) {
                        _this.requestLicenseChange($button, key);
                    }
                });
            }
            else {
                _this.requestLicenseChange($button, key);
            }
        })
            .on('click', '.install-addon', function (evt) {
            var $button = $(evt.currentTarget), $addonBlock = $button.closest('.theme');
            $.ajax({
                url: window['ajaxurl'],
                method: 'POST',
                dataType: 'json',
                data: {
                    token: _this.$addonsList.data('nonce'),
                    action: 'atum_install_addon',
                    addon: $addonBlock.data('addon'),
                    slug: $addonBlock.data('addon-slug'),
                    key: $addonBlock.find('.addon-key input').val(),
                },
                beforeSend: function () {
                    _this.beforeAjax($button);
                },
                success: function (response) {
                    _this.afterAjax($button);
                    if (response.success === true) {
                        _this.showSuccessAlert(response.data);
                    }
                    else {
                        _this.showErrorAlert(response.data);
                    }
                },
            });
        })
            .on('click', '.show-key', function (evt) {
            $(evt.currentTarget).closest('.theme').find('.addon-key').slideToggle('fast');
        });
    }
    AddonsPage.prototype.requestLicenseChange = function ($button, key) {
        var _this = this;
        $.ajax({
            url: window['ajaxurl'],
            method: 'POST',
            dataType: 'json',
            data: {
                token: this.$addonsList.data('nonce'),
                action: $button.data('action'),
                addon: $button.closest('.theme').data('addon'),
                key: key
            },
            beforeSend: function () {
                _this.beforeAjax($button);
            },
            success: function (response) {
                _this.afterAjax($button);
                switch (response.success) {
                    case false:
                        _this.showErrorAlert(response.data);
                        break;
                    case true:
                        _this.showSuccessAlert(response.data);
                        break;
                    case 'activate':
                        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
                            title: _this.settings.get('activation'),
                            html: response.data,
                            icon: 'info',
                            showCancelButton: true,
                            showLoaderOnConfirm: true,
                            confirmButtonText: _this.settings.get('activate'),
                            allowOutsideClick: false,
                            preConfirm: function () {
                                return new Promise(function (resolve, reject) {
                                    $.ajax({
                                        url: window['ajaxurl'],
                                        method: 'POST',
                                        dataType: 'json',
                                        data: {
                                            token: _this.$addonsList.data('nonce'),
                                            action: 'atum_activate_license',
                                            addon: $button.closest('.theme').data('addon'),
                                            key: key
                                        },
                                        success: function (response) {
                                            if (response.success !== true) {
                                                sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.showValidationMessage(response.data);
                                            }
                                            resolve();
                                        }
                                    });
                                });
                            }
                        })
                            .then(function (result) {
                            if (result.isConfirmed) {
                                _this.showSuccessAlert(_this.settings.get('addonActivated'), _this.settings.get('activated'));
                            }
                        });
                        break;
                }
            }
        });
    };
    AddonsPage.prototype.showSuccessAlert = function (message, title) {
        if (!title) {
            title = this.settings.get('success');
        }
        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
            title: title,
            html: message,
            icon: 'success',
            confirmButtonText: this.settings.get('ok'),
        })
            .then(function () { return location.reload(); });
    };
    AddonsPage.prototype.showErrorAlert = function (message) {
        sweetalert2__WEBPACK_IMPORTED_MODULE_0___default.a.fire({
            title: this.settings.get('error'),
            html: message,
            icon: 'error',
            confirmButtonText: this.settings.get('ok'),
        });
    };
    AddonsPage.prototype.beforeAjax = function ($button) {
        $('.theme').find('.button').prop('disabled', true);
        $button.css('visibility', 'hidden').after('<div class="atum-loading"></div>');
    };
    AddonsPage.prototype.afterAjax = function ($button) {
        $('.atum-loading').remove();
        $('.theme').find('.button').prop('disabled', false);
        $button.css('visibility', 'visible');
    };
    return AddonsPage;
}());
/* harmony default export */ __webpack_exports__["default"] = (AddonsPage);


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

/***/ "sweetalert2":
/*!***********************!*\
  !*** external "Swal" ***!
  \***********************/
/*! no static exports found */
/***/ (function(module, exports) {

module.exports = Swal;

/***/ })

/******/ });
//# sourceMappingURL=atum-addons.js.map