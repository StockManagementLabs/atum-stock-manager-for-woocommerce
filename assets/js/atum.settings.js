/**
 * Atum Stock Settings
 *
 * @copyright Stock Management Labs (c)2017
 * @since 0.0.2
 */
(function ($) {
	'use strict';
	
	$(function () {
		
		$('.js-switch').each(function () {
			var switcher = new Switchery(this);
		});
		
		// Using the WooCommerce's tooltip plugin here
		if (typeof $.fn.tipTip === 'function') {
			$('.tips').tipTip({
				'attribute'      : 'data-tip',
				'fadeIn'         : 50,
				'fadeOut'        : 50,
				'delay'          : 200,
				'defaultPosition': 'right'
			});
		}
		
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
				}, function (dismiss) {
					// dismiss can be 'cancel', 'overlay', 'close', and 'timer'
					if (dismiss === 'cancel') {
						$hidden.val('no');
					}
				})
			}
			
		});
		
	});
	
})(jQuery);

jQuery.noConflict();