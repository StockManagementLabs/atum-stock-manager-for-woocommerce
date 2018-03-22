/**
 * Atum Stock Settings
 *
 * @copyright Stock Management Labs ©2018
 * @since 0.0.2
 */
(function ($) {
	'use strict';
	
	$(function () {
		
		// Enable switchers
		atumDoSwitchers();
		
		maybeRestoreEnhancedSelect();
		
		var $atumNav      = $('.atum-nav'),
		    $atumSettings = $('#atum-settings');
		
		// Nav menu
		$atumNav.on('click', '.atum-nav-link', function(e) {
			e.preventDefault();
			
			var $navLink = $(this);
			
			if ($atumSettings.find('.dirty').length) {
				
				// Warn the user about unsaved data
				swal({
					title              : atumSettings.areYouSure,
					text               : atumSettings.unsavedData,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumSettings.continue,
					cancelButtonText   : atumSettings.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false
				}).then(function () {
					atumMoveToTab($navLink);
				}, function (dismiss) {
					$navLink.blur();
				});
			
			}
			else {
				atumMoveToTab($navLink);
			}
			
		});
		
		// Set the dirty fields
		$atumSettings.on('change', 'input, select, textarea', function () {
			$(this).addClass('dirty');
		});
		
		// Remove the dirty mark if the user tries to save
		$atumSettings.on('click', 'input[type=submit]', function() {
			$atumSettings.find('.dirty').removeClass('dirty');
		});
		
		// Before unload alert
		$(window).bind('beforeunload', function() {
			
			if (!$atumSettings.find('.dirty').length) {
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
		
		// Manage Shipping address switch
		$atumSettings.on('change','#atum_same_ship_address', function() {
			
			if ( this.checked ) {
				$('#atum_setting_shipping').slideUp();
			}
			else {
				$('#atum_setting_shipping').slideDown();
			}
		} ).find('#atum_same_ship_address').change();
		
		/**
		 * Move to a new tab in the Settings nav bar
		 */
		function atumMoveToTab($navLink) {
			
			var $formSettingsWrapper = $('.form-settings-wrapper');
			
			$atumNav.find('.atum-nav-link.active').not($navLink).removeClass('active');
			$navLink.addClass('active');
			
			$formSettingsWrapper.addClass('overlay');
			$atumSettings.load( $navLink.attr('href') + ' .form-settings-wrapper', function() {
				atumDoSwitchers();
				maybeRestoreEnhancedSelect();
				$atumSettings.find('#atum_same_ship_address').change();
			});
		
		}
		
		/**
		 * Enable switchers
		 */
		function atumDoSwitchers() {
			
			$('.js-switch').each(function () {
				new Switchery(this, { size: 'small' });
			});
		
		}
		
		/**
		 * Destroy and reload wc enhanced selects
		 */
		function maybeRestoreEnhancedSelect() {
			$('.select2-container--open').remove();
			$('body').trigger( 'wc-enhanced-select-init' );
		}
		
	});
	
})(jQuery);

jQuery.noConflict();