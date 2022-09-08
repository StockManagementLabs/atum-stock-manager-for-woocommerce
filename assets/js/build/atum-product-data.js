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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/js/src/product-data.ts");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/js/src/components/_button-group.ts":
/*!***************************************************!*\
  !*** ./assets/js/src/components/_button-group.ts ***!
  \***************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {var ButtonGroup = {
    doButtonGroups: function ($container) {
        var _this = this;
        $container.on('click', '.btn-group .btn', function (evt) {
            var $button = $(evt.currentTarget);
            if ($button.find(':checkbox').length) {
                $button.toggleClass('active');
            }
            else {
                $button.siblings('.active').removeClass('active');
                $button.addClass('active');
            }
            _this.updateChecked($button.closest('.btn-group'));
            $button.find('input').change();
            return false;
        });
    },
    updateChecked: function ($buttonGroup) {
        $buttonGroup.find('.btn').each(function (index, elem) {
            var $button = $(elem);
            $button.find('input').prop('checked', $button.hasClass('active'));
        });
    }
};
/* harmony default export */ __webpack_exports__["default"] = (ButtonGroup);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/_enhanced-select.ts":
/*!******************************************************!*\
  !*** ./assets/js/src/components/_enhanced-select.ts ***!
  \******************************************************/
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
var EnhancedSelect = (function () {
    function EnhancedSelect() {
        var _this = this;
        this.addAtumClasses();
        $('body').on('wc-enhanced-select-init', function () { return _this.addAtumClasses(); });
    }
    EnhancedSelect.prototype.maybeRestoreEnhancedSelect = function () {
        $('.select2-container--open').remove();
        $('body').trigger('wc-enhanced-select-init');
    };
    EnhancedSelect.prototype.doSelect2 = function ($selector, options, avoidEmptySelections) {
        var _this = this;
        if (options === void 0) { options = {}; }
        if (avoidEmptySelections === void 0) { avoidEmptySelections = false; }
        if (typeof $.fn['select2'] !== 'function') {
            return;
        }
        options = Object.assign({
            minimumResultsForSearch: 10,
        }, options);
        $selector.each(function (index, elem) {
            var $select = $(elem), selectOptions = __assign({}, options);
            if ($select.hasClass('atum-select-multiple') && $select.prop('multiple') === false) {
                $select.prop('multiple', true);
            }
            if (!$select.hasClass('atum-select2')) {
                $select.addClass('atum-select2');
                _this.addAtumClasses($select);
            }
            if (avoidEmptySelections) {
                $select.on('select2:selecting', function (evt) {
                    var $select = $(evt.currentTarget), value = $select.val();
                    if (Array.isArray(value) && ($.inArray('', value) > -1 || $.inArray('-1', value) > -1)) {
                        $.each(value, function (index, elem) {
                            if (elem === '' || elem === '-1') {
                                value.splice(index, 1);
                            }
                        });
                        $select.val(value);
                    }
                });
            }
            $select.select2(selectOptions);
            $select.siblings('.select2-container').addClass('atum-select2');
            _this.maybeAddTooltip($select);
        });
    };
    EnhancedSelect.prototype.addAtumClasses = function ($selects) {
        var _this = this;
        if ($selects === void 0) { $selects = null; }
        $selects = $selects || $('select').filter('.atum-select2, .atum-enhanced-select');
        if (!$selects.length) {
            return;
        }
        $selects
            .each(function (index, elem) {
            var $select = $(elem), $select2Container = $select.siblings('.select2-container').not('.atum-select2, .atum-enhanced-select');
            if ($select2Container.length) {
                $select2Container.addClass($select.hasClass('atum-select2') ? 'atum-select2' : 'atum-enhanced-select');
                _this.maybeAddTooltip($select);
            }
        })
            .on('select2:opening', function (evt) {
            var $select = $(evt.currentTarget), select2Data = $select.data();
            if (select2Data.hasOwnProperty('select2')) {
                var $dropdown = select2Data.select2.dropdown.$dropdown;
                if ($dropdown.length) {
                    $dropdown.addClass('atum-select2-dropdown');
                }
            }
        });
    };
    EnhancedSelect.prototype.maybeAddTooltip = function ($select) {
        if ($select.hasClass('atum-tooltip')) {
            var $select2Rendered = $select.siblings('.select2-container').find('.select2-selection__rendered');
            $select2Rendered.addClass('atum-tooltip');
        }
    };
    return EnhancedSelect;
}());
/* harmony default export */ __webpack_exports__["default"] = (EnhancedSelect);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/_file-uploader.ts":
/*!****************************************************!*\
  !*** ./assets/js/src/components/_file-uploader.ts ***!
  \****************************************************/
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
var FileUploader = (function () {
    function FileUploader($buttons, options, preview) {
        if (preview === void 0) { preview = false; }
        this.$buttons = $buttons;
        this.options = options;
        this.preview = preview;
        this.defaultOptions = {
            frame: 'select',
            multiple: false,
        };
        this.wpHooks = window['wp']['hooks'];
        this.doFileUploaders();
    }
    FileUploader.prototype.doFileUploaders = function () {
        var _this = this;
        if (window['wp'].hasOwnProperty('media')) {
            this.$buttons.click(function (evt) {
                var $button = $(evt.currentTarget);
                var modalOptions = __assign(__assign({}, _this.defaultOptions), _this.options);
                if ($button.data('modal-title')) {
                    modalOptions.title = $button.data('modal-title');
                }
                if ($button.data('modal-button')) {
                    modalOptions.button = {
                        text: $button.data('modal-button')
                    };
                }
                var uploader = window['wp'].media(modalOptions)
                    .on('select', function () {
                    var selection = uploader.state().get('selection'), attachment = modalOptions.multiple ? selection.toJSON() : selection.first().toJSON(), $input = $button.siblings('input:hidden');
                    if (modalOptions.multiple) {
                        var attachmentIds_1 = [];
                        attachment.forEach(function (att) {
                            attachmentIds_1.push(att.id);
                        });
                        $input.val(JSON.stringify(_this.wpHooks.applyFilters('atum_fileUploader_inputVal', attachmentIds_1, $input)));
                    }
                    else {
                        $input.val(_this.wpHooks.applyFilters('atum_fileUploader_inputVal', attachment.id, $input));
                    }
                    if (_this.preview && (!modalOptions.library.type || modalOptions.library.type.indexOf('image') > -1)) {
                        $button.siblings('img').remove();
                        if (modalOptions.multiple) {
                            attachment.forEach(function (att) {
                                $button.after("<img class=\"atum-file-uploader__preview\" src=\"" + att.url + "\">");
                            });
                        }
                        else {
                            $button.after("<img class=\"atum-file-uploader__preview\" src=\"" + attachment.url + "\">");
                        }
                    }
                    _this.wpHooks.doAction('atum_fileUploader_selected', uploader, $button);
                })
                    .open();
            });
        }
    };
    return FileUploader;
}());
/* harmony default export */ __webpack_exports__["default"] = (FileUploader);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/product-data/_file-attachments.ts":
/*!********************************************************************!*\
  !*** ./assets/js/src/components/product-data/_file-attachments.ts ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var _file_uploader__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../_file-uploader */ "./assets/js/src/components/_file-uploader.ts");

var FileAttachments = (function () {
    function FileAttachments(settings) {
        var _this = this;
        this.settings = settings;
        this.$emailSelector = $('<select>', { class: 'attach-to-email' });
        this.wpHooks = window['wp']['hooks'];
        this.$attachmentsList = $('.atum-attachments-list');
        this.$input = $('#atum-attachments');
        $.each(this.settings.get('emailNotifications'), function (key, title) {
            _this.$emailSelector.append("\n\t\t\t\t<option value=\"" + key + "\">" + title + "</option>\n\t\t\t");
        });
        this.addHooks();
        this.bindEvents();
        var uploaderOptions = {
            multiple: true,
        };
        new _file_uploader__WEBPACK_IMPORTED_MODULE_0__["default"]($('#atum_files').find('.atum-file-uploader'), uploaderOptions);
    }
    FileAttachments.prototype.addHooks = function () {
        var _this = this;
        this.wpHooks.addAction('atum_fileUploader_selected', 'atum', function (uploader) {
            var attachments = uploader.state().get('selection').toJSON();
            attachments.forEach(function (attachment) {
                var $listItem = $('<li>').data('id', attachment.id), url = attachment.hasOwnProperty('url') ? attachment.url : attachment.sizes.full.url;
                $listItem
                    .append("<label>" + _this.settings.get('attachToEmail') + "</label>")
                    .append(_this.$emailSelector.clone());
                $listItem.append("\n\t\t\t\t\t<a href=\"" + url + "\" target=\"_blank\" title=\"" + attachment.title + "\">\n\t\t\t\t\t\t<img src=\"" + attachment.sizes.medium.url + "\" alt=\"" + attachment.title + "\">\n\t\t\t\t\t</a>\n\t\t\t\t");
                _this.$attachmentsList.append($listItem);
            });
            _this.updateInput();
        });
    };
    FileAttachments.prototype.bindEvents = function () {
        var _this = this;
        this.$attachmentsList
            .on('change', '.attach-to-email', function () { return _this.updateInput(); })
            .on('click', '.delete-attachment', function (evt) {
            var $button = $(evt.currentTarget), tooltipId = $button.attr('aria-describedby');
            $button.closest('li').remove();
            $("#" + tooltipId).remove();
            _this.updateInput();
        });
    };
    FileAttachments.prototype.updateInput = function () {
        var value = [];
        this.$attachmentsList.find('li').each(function (index, elem) {
            var $elem = $(elem);
            value.push({
                id: $elem.data('id'),
                email: $elem.find('.attach-to-email').val(),
            });
        });
        this.$input.val(JSON.stringify(value));
    };
    return FileAttachments;
}());
/* harmony default export */ __webpack_exports__["default"] = (FileAttachments);

/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./assets/js/src/components/product-data/_product-data-meta-boxes.ts":
/*!***************************************************************************!*\
  !*** ./assets/js/src/components/product-data/_product-data-meta-boxes.ts ***!
  \***************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function($) {/* harmony import */ var _button_group__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../_button-group */ "./assets/js/src/components/_button-group.ts");
/* harmony import */ var _enhanced_select__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../_enhanced-select */ "./assets/js/src/components/_enhanced-select.ts");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! sweetalert2 */ "sweetalert2");
/* harmony import */ var sweetalert2__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(sweetalert2__WEBPACK_IMPORTED_MODULE_2__);



var ProductDataMetaBoxes = (function () {
    function ProductDataMetaBoxes(settings) {
        var _this = this;
        this.settings = settings;
        this.$productDataMetaBox = $('#woocommerce-product-data');
        new _enhanced_select__WEBPACK_IMPORTED_MODULE_1__["default"]();
        _button_group__WEBPACK_IMPORTED_MODULE_0__["default"].doButtonGroups(this.$productDataMetaBox);
        this.$productDataMetaBox.on('woocommerce_variations_loaded woocommerce_variations_added', function () {
            _button_group__WEBPACK_IMPORTED_MODULE_0__["default"].doButtonGroups(_this.$productDataMetaBox.find('.woocommerce_variations'));
            _this.maybeBlockFields();
        });
        $('#_manage_stock').change(function (evt) { return $('#_out_stock_threshold').closest('.options_group').css('display', $(evt.currentTarget).is(':checked') ? 'block' : 'none'); }).change();
        $('.product-tab-runner').find('.run-script').click(function (evt) {
            var $button = $(evt.currentTarget), value = $button.siblings('select').val();
            sweetalert2__WEBPACK_IMPORTED_MODULE_2___default.a.fire({
                title: _this.settings.get('areYouSure'),
                text: $button.data('confirm').replace('%s', "\"" + value + "\""),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: _this.settings.get('continue'),
                cancelButtonText: _this.settings.get('cancel'),
                reverseButtons: true,
                showLoaderOnConfirm: true,
                preConfirm: function () {
                    return new Promise(function (resolve, reject) {
                        $.ajax({
                            url: window['ajaxurl'],
                            data: {
                                action: $button.data('action'),
                                security: _this.settings.get('nonce'),
                                parent_id: $('#post_ID').val(),
                                value: value,
                            },
                            method: 'POST',
                            dataType: 'json',
                            success: function (response) {
                                if (typeof response !== 'object' || response.success !== true) {
                                    sweetalert2__WEBPACK_IMPORTED_MODULE_2___default.a.showValidationMessage(response.data);
                                }
                                resolve(response.data);
                            },
                        });
                    });
                },
                allowOutsideClick: function () { return !sweetalert2__WEBPACK_IMPORTED_MODULE_2___default.a.isLoading(); },
            })
                .then(function (result) {
                if (result.isConfirmed) {
                    sweetalert2__WEBPACK_IMPORTED_MODULE_2___default.a.fire({
                        icon: 'success',
                        title: _this.settings.get('success'),
                        text: result.value,
                    })
                        .then(function () { return location.reload(); });
                }
            });
        });
        this.$productDataMetaBox
            .on('focus select2:opening', '.atum-field :input', function (evt) { return $(evt.target).siblings('.input-group-prepend').addClass('focus'); })
            .on('blur select2:close', '.atum-field :input', function (evt) { return $(evt.target).siblings('.input-group-prepend').removeClass('focus'); });
        this.maybeBlockFields();
    }
    ProductDataMetaBoxes.prototype.maybeBlockFields = function () {
        if (typeof this.settings.get('lockFields') !== 'undefined' && 'yes' === this.settings.get('lockFields')) {
            $('.atum-field input').each(function (index, elem) {
                $(elem).prop('readonly', true).next().after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
            });
            $('.atum-field select').each(function (index, elem) {
                $(elem).prop('disabled', true).next().next().after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
            });
        }
    };
    return ProductDataMetaBoxes;
}());
/* harmony default export */ __webpack_exports__["default"] = (ProductDataMetaBoxes);

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

/***/ "./assets/js/src/product-data.ts":
/*!***************************************!*\
  !*** ./assets/js/src/product-data.ts ***!
  \***************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* WEBPACK VAR INJECTION */(function(jQuery) {/* harmony import */ var _components_product_data_file_attachments__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components/product-data/_file-attachments */ "./assets/js/src/components/product-data/_file-attachments.ts");
/* harmony import */ var _components_product_data_product_data_meta_boxes__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/product-data/_product-data-meta-boxes */ "./assets/js/src/components/product-data/_product-data-meta-boxes.ts");
/* harmony import */ var _config_settings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./config/_settings */ "./assets/js/src/config/_settings.ts");



jQuery(function ($) {
    var settings = new _config_settings__WEBPACK_IMPORTED_MODULE_2__["default"]('atumProductData');
    new _components_product_data_product_data_meta_boxes__WEBPACK_IMPORTED_MODULE_1__["default"](settings);
    new _components_product_data_file_attachments__WEBPACK_IMPORTED_MODULE_0__["default"](settings);
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
//# sourceMappingURL=atum-product-data.js.map