/**
 * Atum Settings
 *
 * @copyright Stock Management Labs ©2018
 *
 * @since 0.0.2
 */

;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumSettings',
	    defaults   = {
		
	    };
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		
		// Initialize selectors
		this.$settingsWrapper = $(element);
		this.$nav = this.$settingsWrapper.find('.atum-nav');
		this.$form = this.$settingsWrapper.find('#atum-settings');
		
		// We don't want to alter the default options for future instances of the plugin
		// Load the localized vars to the plugin settings too
		this.settings = $.extend( {}, defaults, atumSettingsVars || {}, options || {} );
		
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend( Plugin.prototype, {
		
		navigationReady: false,
		numHashParameters: 0,
		
		init: function () {
			
			var self = this;
			
			// URL hash navigation
			this.setupNavigation();
			
			// Enable switchers
			this.doSwitchers();
			
			// Restore enhanced selects
			this.restoreEnhancedSelects();
			
			// Set the dirty fields
			this.$form.on('change', 'input, select, textarea', function () {
				$(this).addClass('dirty');
			})
			// Remove the dirty mark if the user tries to save
			.on('click', 'input[type=submit]', function() {
				self.$form.find('.dirty').removeClass('dirty');
			})
			// Manage Shipping address switch
			.on('change','#atum_same_ship_address', function() {
				
				var $shippingSection = $('#atum_setting_shipping');
				
				if ( this.checked ) {
					$shippingSection.slideUp();
				}
				else {
					$shippingSection.slideDown();
				}
				
			})
			.find('#atum_same_ship_address').change().removeClass('dirty');
			
			// Before unload alert
			$(window).bind('beforeunload', function() {
				
				if (!self.$form.find('.dirty').length) {
					return;
				}
				
				// Prevent multiple prompts - seen on Chrome and IE
				if (navigator.userAgent.toLowerCase().match(/msie|chrome/)) {
					
					if (window.aysHasPrompted) {
						return;
					}
					
					window.aysHasPrompted = true;
					window.setTimeout(function() {
						window.aysHasPrompted = false;
					}, 900);
					
				}
				
				return false;
				
			});
		
		},
		doSwitchers: function() {
			
			$('.js-switch').each(function () {
				new Switchery(this, { size: 'small' });
			});
			
		},
		doNiceSelects: function() {
		
		},
		restoreEnhancedSelects: function() {
			
			$('.select2-container--open').remove();
			$('body').trigger( 'wc-enhanced-select-init' );
			
		},
		setupNavigation: function() {
			
			if (typeof $.address === 'undefined') {
				return;
			}
			
			var self = this;
			
			// Hash history navigation
			$.address.change(function(event) {
				
				var pathNames        = $.address.pathNames(),
				    numCurrentParams = pathNames.length;
				
				if(self.navigationReady === true && (numCurrentParams || self.numHashParameters !== numCurrentParams)) {
					self.clickTab(pathNames[0]);
				}
				
				self.navigationReady = true;
				
			})
			.init(function() {
				
				var pathNames = $.address.pathNames();
				
				// When accessing externally or reloading the page, update the fields and the list
				if(pathNames.length) {
					self.clickTab(pathNames[0]);
				}
				
			});
			
		},
		clickTab: function(tab) {
			
			var self     = this,
			    $navLink = this.$nav.find('.atum-nav-link[data-tab="' + tab + '"]');
			
			if (this.$form.find('.dirty').length) {
				
				// Warn the user about unsaved data
				swal({
					title              : this.settings.areYouSure,
					text               : this.settings.unsavedData,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : this.settings.continue,
					cancelButtonText   : this.settings.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false
				}).then(function () {
					self.moveToTab($navLink);
				}, function (dismiss) {
					$navLink.blur();
				});
				
			}
			else {
				self.moveToTab($navLink);
			}
		
		},
		moveToTab: function($navLink) {
			
			var self                 = this,
			    $formSettingsWrapper = this.$form.find('.form-settings-wrapper');
			
			this.$nav.find('.atum-nav-link.active').not($navLink).removeClass('active');
			$navLink.addClass('active');
			
			$formSettingsWrapper.addClass('overlay');
			this.$form.load( $navLink.attr('href') + ' .form-settings-wrapper', function() {
				
				self.doSwitchers();
				self.restoreEnhancedSelects();
				self.$form.find('#atum_same_ship_address').change().removeClass('dirty');
				
				var $inputButton = self.$form.find('input:submit');
				
				if ($navLink.parent().hasClass('no-submit')) {
					$inputButton.hide();
				}
				else {
					$inputButton.show();
				}
				
			});
			
		}
		
	} );
	
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
		$('.atum-settings-wrapper').atumSettings();
		
	});
	
} )( jQuery, window, document );