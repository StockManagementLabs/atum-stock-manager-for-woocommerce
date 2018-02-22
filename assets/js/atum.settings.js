/**
 * Atum Stock Settings
 *
 * @copyright Stock Management Labs (c)2017
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
		
		// Manage Stock switch
		$('#atum_manage_stock').change(function () {
			
			if (!$(this).is(':checked')) {
				var $hidden = $('#atum_restore_option_stock')
				
				swal({
					title             : atumSettings.stockMsgTitle,
					html              : atumSettings.stockMsgText,
					type              : 'warning',
					showCancelButton  : true,
					confirmButtonColor: '#00B050',
					cancelButtonColor : '#0073AA',
					confirmButtonText : atumSettings.restoreThem,
					cancelButtonText  : atumSettings.keepThem,
					allowOutsideClick : false,
					allowEscapeKey    : false
				}).then(function () {
					$hidden.val('yes');
				},
				function (dismiss) {
					// dismiss can be 'cancel', 'overlay', 'close', and 'timer'
					if (dismiss === 'cancel') {
						$hidden.val('no');
					}
				});
			}
			
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
				else
				{
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
				var switcher = new Switchery(this, { size: 'small' });
			});
		
		}
		
		/**
		 * Destroy and reload wc enhanced selects
		 */
		function maybeRestoreEnhancedSelect() {
			$('.select2-container--open').remove();
			$( 'body' ).trigger( 'wc-enhanced-select-init' );
		}
		
	});
	
})(jQuery);

jQuery.noConflict();