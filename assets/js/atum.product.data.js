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
		
		// Toggle the "Out of Stock Threshold" field visibility
		$('#_manage_stock').change(function () {
			$('#_out_stock_threshold').closest('.options_group').css('display', this.checked ? 'block' : 'none');
		}).change();

		// Add switches to variations once are loaded by WC
		$('#woocommerce-product-data').on('woocommerce_variations_added woocommerce_variations_loaded', function() {
			atumDoSwitchers();
		});
		
		// Change stock control for all variations at once
		$('.product-tab-runner').find('.run-script').click(function() {
			
			var $button = $(this),
			    status  = $button.siblings('select').val();
			
			swal({
				title              : atumProductData.areYouSure,
				text               : $button.data('confirm').replace('%s', '"' + status + '"'),
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
								action   : $button.data('action'),
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
			$(this).removeClass('js-switch');
		});
		
	}
	
	/**
	 * --------------------------------------------------------------------------
	 * Bootstrap (v4.1.1): button.js
	 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
	 * --------------------------------------------------------------------------
	 */
	function _defineProperties(e,t){for(var n=0;n<t.length;n++){var s=t[n];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,n){return t&&_defineProperties(e.prototype,t),n&&_defineProperties(e,n),e}var Button=function(e){var t="button",n="bs.button",s="."+n,a=".data-api",i=e.fn[t],r="active",o="btn",l="focus",u='[data-toggle^="button"]',c='[data-toggle="buttons"]',f="input",_=".active",d=".btn",h={CLICK_DATA_API:"click"+s+a,FOCUS_BLUR_DATA_API:"focus"+s+a+" blur"+s+a},g=function(){function t(e){this._element=e}var s=t.prototype;return s.toggle=function(){var t=!0,n=!0,s=e(this._element).closest(c)[0];if(s){var a=e(this._element).find(f)[0];if(a){if("radio"===a.type)if(a.checked&&e(this._element).hasClass(r))t=!1;else{var i=e(s).find(_)[0];i&&e(i).removeClass(r)}if(t){if(a.hasAttribute("disabled")||s.hasAttribute("disabled")||a.classList.contains("disabled")||s.classList.contains("disabled"))return;a.checked=!e(this._element).hasClass(r),e(a).trigger("change")}a.focus(),n=!1}}n&&this._element.setAttribute("aria-pressed",!e(this._element).hasClass(r)),t&&e(this._element).toggleClass(r)},s.dispose=function(){e.removeData(this._element,n),this._element=null},t._jQueryInterface=function(s){return this.each(function(){var a=e(this).data(n);a||(a=new t(this),e(this).data(n,a)),"toggle"===s&&a[s]()})},_createClass(t,null,[{key:"VERSION",get:function(){return"4.1.1"}}]),t}();return e(document).on(h.CLICK_DATA_API,u,function(t){t.preventDefault();var n=t.target;e(n).hasClass(o)||(n=e(n).closest(d)),g._jQueryInterface.call(e(n),"toggle")}).on(h.FOCUS_BLUR_DATA_API,u,function(t){var n=e(t.target).closest(d)[0];e(n).toggleClass(l,/^focus(in)?$/.test(t.type))}),e.fn[t]=g._jQueryInterface,e.fn[t].Constructor=g,e.fn[t].noConflict=function(){return e.fn[t]=i,g._jQueryInterface},g}($);
	
})(jQuery);

jQuery.noConflict();