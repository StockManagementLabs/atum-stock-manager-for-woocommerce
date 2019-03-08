/* =======================================
   PRODUCT DATA META BOXES
   ======================================= */

import { Switcher } from '../_switcher';
import Settings from '../../config/_settings';

export default class ProductDataMetaBoxes {
	
	settings: Settings;
	
	constructor(settingsObj: Settings) {
		
		this.settings = settingsObj;
		
		// Add switchery.
		Switcher.doSwitchers();
		
		// Add switches to variations once are loaded by WC.
		$('#woocommerce-product-data').on('woocommerce_variations_added woocommerce_variations_loaded', () => {
			Switcher.doSwitchers();
		});
		
		// Toggle the "Out of Stock Threshold" field visibility.
		$('#_manage_stock').change( (evt: JQueryEventObject) => {
			$('#_out_stock_threshold').closest('.options_group').css('display', $(evt.currentTarget).is(':checked') ? 'block' : 'none');
		}).change();
		
		// Change stock control for all variations at once.
		$('.product-tab-runner').find('.run-script').click( (evt: JQueryEventObject) => {
			
			const $button: JQuery = $(evt.currentTarget),
			      status: string  = $button.siblings('select').val(),
			      swal: any       = window['swal'];
			
			swal({
				title              : this.settings.get('areYouSure'),
				text               : $button.data('confirm').replace('%s', '"' + status + '"'),
				type               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get('continue'),
				cancelButtonText   : this.settings.get('cancel'),
				reverseButtons     : true,
				showLoaderOnConfirm: true,
				preConfirm         : () => {
					
					return new Promise( (resolve: Function, reject: Function) => {
						
						$.ajax({
							url     : window['ajaxurl'],
							data    : {
								action   : $button.data('action'),
								security : this.settings.get('nonce'),
								parent_id: $('#post_ID').val(),
								status   : status
							},
							method  : 'POST',
							dataType: 'json',
							success : (response: any) => {
								
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
				allowOutsideClick  : () => {
					return !swal.isLoading();
				}
			}).then( (result: string) => {
				
				swal({
					type : 'success',
					title: this.settings.get('success'),
					text : result
				}).then( () => {
					location.reload();
				});
				
			}).catch(swal.noop);
			
		});
		
	}
	
}