/**
 * Atum Product Data
 *
 * @copyright Stock Management Labs ©2018
 * @since 1.4.1
 */
(function ($) {
	'use strict';
	
	$(function () {
		
		// Enable switchers
		atumDoSwitchers();
		
		// Add switches to variations once are loaded by WC
		$('#woocommerce-product-data').on('woocommerce_variations_added woocommerce_variations_loaded', function() {
			atumDoSwitchers();
		});
		
		// Change stock control for all variations at once
		$('.change-stock-control').click(function() {
			
			var $button = $(this),
			    status  = $button.siblings('select').val();
			
			swal({
				title              : atumProductData.areYouSure,
				text               : atumProductData.confirmNotice.replace('%s', '"' + status + '"'),
				type               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : atumProductData.continue,
				cancelButtonText   : atumProductData.cancel,
				reverseButtons     : true,
				showLoaderOnConfirm: true,
				preConfirm         : function () {
					return new Promise(function (resolve, reject) {
						
						$.ajax({
							url     : ajaxurl,
							data    : {
								action   : 'atum_set_variations_control_status',
								security : atumProductData.nonce,
								parent_id: $('#post_ID').val(),
								status   : status
							},
							method  : 'POST',
							dataType: 'json',
							success : function (response) {
								
								if (typeof response === 'object' && response.success === true) {
									resolve( response.data );
								}
								else {
									reject( response.data );
								}
							}
						});
						
					});
				},
				allowOutsideClick  : function () {
					return !swal.isLoading();
				}
			}).then(function (result) {
				
				swal({
					type : 'success',
					title: atumProductData.success,
					text : result
				}).then(function() {
					location.reload();
				});
				
			}).catch(swal.noop);
		
		});
		
	});
	
	/**
	 * Enable switchers
	 */
	function atumDoSwitchers() {
		
		$('.js-switch').each(function () {
			new Switchery(this, { size: 'small' });
			jQuery(this).removeClass('js-switch');
		});
		
	}
	
})(jQuery);

jQuery.noConflict();