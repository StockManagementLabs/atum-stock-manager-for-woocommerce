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

        //----------------------------
        // _out_stock_threshold_field_ front
		// This binds and unbinds atumEnableOutStockThresholdField on required types (atumProductData.outStockThresholdProductTypes) when we are on the inventory tab
		//
		// For variations, we use .show_if_variation_manage_stock css class
		// check woocomere/js*/meta-boxes-product-variations.js -> $( 'input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock', wrapper ).change();
        //----------------------------
        var $productType = $('#product-type');

        console.log(atumProductData.outStockThresholdProductTypes);
        // check product-type on load, or on every change to fire
        if ($.inArray( $productType.val(), atumProductData.outStockThresholdProductTypes) >= 0 ) {

			console.log("enabled product type on load:"+$productType.val());

			//bind to inventory_tab
            $( 'li.inventory_tab>a' ).bind( "click", atumEnableOutStockThresholdField );

		}else{
        	//force unbind and hide
            $( 'li.inventory_tab>a' ).unbind( "click", atumEnableOutStockThresholdField );
            $('#_out_stock_threshold_field_div').hide();
		}

        $productType.change(function () {
            if ( $.inArray( $(this).val(), atumProductData.outStockThresholdProductTypes ) >= 0 ) {
                console.log("enabled product type on change:"+$(this).val());

                //bind to inventory_tab
                $( 'li.inventory_tab>a' ).bind( "click", atumEnableOutStockThresholdField );

            }else{
                //force unbind and hide
                $( 'li.inventory_tab>a' ).unbind( "click", atumEnableOutStockThresholdField );
                $('#_out_stock_threshold_field_div').hide();
			}
        });
        

        function atumEnableOutStockThresholdField() {
        	console.log(">atumEnableOutStockThresholdField ");
			console.log("inventory_tab  click");
			// and the checkbox _manage_stock is visible
			if( $('#_manage_stock').filter(":visible").length === 1 ){
				console.log('_manage_stock is visible and checkd?'+ $('#_manage_stock').prop('checked'));

				// make _out_stock_threshold_field_div visible if needed, and listen to _manage_stock for changes.
				$("#_out_stock_threshold_field_div").css("display", $('#_manage_stock').prop('checked') ? "block" : "none");

				$('#_manage_stock').change(function () {
					console.log("_manage_stock fired");
					$("#_out_stock_threshold_field_div").css("display", this.checked ? "block" : "none");
				});
			}else{
                // Some kind of products (Grouped products) have a _stock field without checbox.
                if( $('#_stock').filter(":visible").length === 1 ){
                    console.log('Some kind of products (Grouped products) have a _stock field without checbox.');
                    $("#_out_stock_threshold_field_div").css("display", $('#_manage_stock').prop('checked') ? "block" : "none");
                }
			}
        }


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