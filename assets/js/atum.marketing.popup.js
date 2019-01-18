/**
 * Atum Marketing Popup
 *
 * @copyright Stock Management Labs ©2018
 *
 * @since 1.5.2
 */

;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumMarketingPopup',
	    defaults   = {
		
	    };
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		
		// Initialize selectors
		this.$settingsWrapper = $(element);
		
		// We don't want to alter the default options for future instances of the plugin
		// Load the localized vars to the plugin settings too
		this.settings = $.extend( {}, defaults, atumMarketingPopupVars || {}, options || {} );
		
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend(Plugin.prototype, {
		
		navigationReady  : false,
		numHashParameters: 0,
		
		init: function() {
			
			this.getPopupInfo();
			
		},
		getPopupInfo: function() {
			var self = this;
			$.ajax({
				url       : ajaxurl,
				dataType  : 'json',
				method    : 'post',
				data      : {
					action    : 'atum_get_marketing_popup_info',
					token     : self.settings.nonce,
				},
				success   : function(response) {
					
					if (response.success === true) {
						var $descriptionColor    = response.data.marketing_popup.description.text_color ? 'color:' + response.data.marketing_popup.description.text_color + ';' : '',
						    $descriptionFontSize = response.data.marketing_popup.description.text_size ? 'font-size:' + response.data.marketing_popup.description.text_size + ';' : '',
						    $descriptionAlign    = response.data.marketing_popup.description.text_align ? 'text-align:' + response.data.marketing_popup.description.text_align + ';' : '',
						    $descriptionPadding  = response.data.marketing_popup.description.padding ? 'padding:' + response.data.marketing_popup.description.padding + ';' : '',
						    $description         = '<p data-transient-key="' + response.data.marketing_popup.transient_key + '" style="' + $descriptionColor + $descriptionFontSize + $descriptionAlign + $descriptionPadding + '">' + response.data.marketing_popup.description.text + '</p>',
						    $titleColor          = response.data.marketing_popup.description.text_color ? 'color:' + response.data.marketing_popup.title.text_color + ';' : '',
						    $titleFontSize       = response.data.marketing_popup.description.text_size ? 'font-size:' + response.data.marketing_popup.title.text_size + ';' : '',
						    $titleAlign          = response.data.marketing_popup.description.text_align ? 'text-align:' + response.data.marketing_popup.title.text_align + ';' : '',
						    $title               = '<h1 style="' + $titleColor + $titleFontSize + $titleAlign + '">' + response.data.marketing_popup.title.text + '</h1>',
						    $buttonsArray        = response.data.marketing_popup.buttons,
						    $buttons             = '',
						    $imageTopLeft        = response.data.marketing_popup.images.top_left,
						    $logo                = '<img class="mp-logo" src="' + response.data.marketing_popup.images.logo + '">';
						
						// Add buttons
						if ( $buttonsArray.length > 0 ) {
							$buttonsArray.forEach(function (button) {
								$buttons += '<button data-url="' + button.url + '" class="' + button.class + ' popup-button" style="' + button.css + '">' + button.text + '</button>'
							});
						}
						
						swal({
							width             : 520,
							padding           : null,
							customClass       : 'marketing-popup',
							background        : response.data.marketing_popup.background,
							showCloseButton   : true,
							showConfirmButton : false,
							html              : $logo + $title + $description + $buttons,
							imageUrl          : $imageTopLeft,
						}).catch(swal.noop);
						
						// Redirect to button url
						$('.popup-button').on('click', function () {
							window.open($(this).data('url'), '_blank');
						});
						
						// Hide popup when click un close button for this user
						$('.marketing-popup .swal2-close').on('click',function () {
							self.hideMarketingPopup($('.swal2-content p').data('transient-key'));
						});
					}
				},
			});
			
		},
		hideMarketingPopup: function (transientKey) {
			var self = this;
			$.ajax({
				url       : ajaxurl,
				dataType  : 'json',
				method    : 'post',
				data      : {
					action       : 'atum_hide_marketing_popup',
					token        : self.settings.nonce,
					transientKey : transientKey,
				},
			});
		},
	});
	
	// A really lightweight plugin wrapper around the constructor, preventing against multiple instantiations
	$.fn[ pluginName ] = function( options ) {
		return this.each( function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
				$.data( this, "plugin_" +
					pluginName, new Plugin( this, options ) );
			}
		} );
	};
	
	
	// Init the plugin on document ready
	$(function () {
		
		// Init ATUM Settings
		$('#wpwrap').atumMarketingPopup();
		
	});
	
	/**
	 * --------------------------------------------------------------------------
	 * Bootstrap (v4.1.1): button.js
	 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
	 * --------------------------------------------------------------------------
	 */
	function _defineProperties(e,t){for(var n=0;n<t.length;n++){var s=t[n];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,n){return t&&_defineProperties(e.prototype,t),n&&_defineProperties(e,n),e}var Button=function(e){var t="button",n="bs.button",s="."+n,a=".data-api",i=e.fn[t],r="active",o="btn",l="focus",u='[data-toggle^="button"]',c='[data-toggle="buttons"]',f="input",_=".active",d=".btn",h={CLICK_DATA_API:"click"+s+a,FOCUS_BLUR_DATA_API:"focus"+s+a+" blur"+s+a},g=function(){function t(e){this._element=e}var s=t.prototype;return s.toggle=function(){var t=!0,n=!0,s=e(this._element).closest(c)[0];if(s){var a=e(this._element).find(f)[0];if(a){if("radio"===a.type)if(a.checked&&e(this._element).hasClass(r))t=!1;else{var i=e(s).find(_)[0];i&&e(i).removeClass(r)}if(t){if(a.hasAttribute("disabled")||s.hasAttribute("disabled")||a.classList.contains("disabled")||s.classList.contains("disabled"))return;a.checked=!e(this._element).hasClass(r),e(a).trigger("change")}a.focus(),n=!1}}n&&this._element.setAttribute("aria-pressed",!e(this._element).hasClass(r)),t&&e(this._element).toggleClass(r)},s.dispose=function(){e.removeData(this._element,n),this._element=null},t._jQueryInterface=function(s){return this.each(function(){var a=e(this).data(n);a||(a=new t(this),e(this).data(n,a)),"toggle"===s&&a[s]()})},_createClass(t,null,[{key:"VERSION",get:function(){return"4.1.1"}}]),t}();return e(document).on(h.CLICK_DATA_API,u,function(t){t.preventDefault();var n=t.target;e(n).hasClass(o)||(n=e(n).closest(d)),g._jQueryInterface.call(e(n),"toggle")}).on(h.FOCUS_BLUR_DATA_API,u,function(t){var n=e(t.target).closest(d)[0];e(n).toggleClass(l,/^focus(in)?$/.test(t.type))}),e.fn[t]=g._jQueryInterface,e.fn[t].Constructor=g,e.fn[t].noConflict=function(){return e.fn[t]=i,g._jQueryInterface},g}($);
	
} )( jQuery, window, document );