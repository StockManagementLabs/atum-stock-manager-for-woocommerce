/* =======================================
   PRODUCT DATA META BOXES
   ======================================= */

import { ButtonGroup } from '../_button-group';
import EnhancedSelect from '../_enhanced-select';
import Settings from '../../config/_settings';
import { Switcher } from '../_switcher';

export default class ProductDataMetaBoxes {
	
	$productDataMetaBox: JQuery;
	swal: any = window['swal'];
	
	constructor(
		private settings: Settings
	) {
		
		this.$productDataMetaBox = $('#woocommerce-product-data');

		// Customize enhanced selects.
		new EnhancedSelect();
		
		// Add switchery.
		Switcher.doSwitchers();
		
		// Enable button groups.
		ButtonGroup.doButtonGroups(this.$productDataMetaBox);
		
		// Add switches to variations once are loaded by WC.
		this.$productDataMetaBox.on('woocommerce_variations_loaded', () => {
			Switcher.doSwitchers();
			ButtonGroup.doButtonGroups(this.$productDataMetaBox.find('.woocommerce_variations'));
		});

		this.$productDataMetaBox.on('woocommerce_variations_added', () => {
            Switcher.doSwitchers();
            ButtonGroup.doButtonGroups(this.$productDataMetaBox.find('.woocommerce_variations'));
		});
		
		// Toggle the "Out of Stock Threshold" field visibility.
		$('#_manage_stock').change( (evt: JQueryEventObject) => $('#_out_stock_threshold').closest('.options_group').css('display', $(evt.currentTarget).is(':checked') ? 'block' : 'none') ).change();
		
		// Run scripts for all the variations at once.
		$('.product-tab-runner').find('.run-script').click( (evt: JQueryEventObject) => {
			
			const $button: JQuery = $(evt.currentTarget),
			      value: string   = $button.siblings('select').val();
			
			this.swal({
				title              : this.settings.get('areYouSure'),
				text               : $button.data('confirm').replace('%s', `"${ value }"`),
				type               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get('continue'),
				cancelButtonText   : this.settings.get('cancel'),
				reverseButtons     : true,
				showLoaderOnConfirm: true,
				preConfirm         : (): Promise<any> => {
					
					return new Promise( (resolve: Function, reject: Function) => {
						
						$.ajax({
							url     : window['ajaxurl'],
							data    : {
								action   : $button.data('action'),
								security : this.settings.get('nonce'),
								parent_id: $('#post_ID').val(),
								value    : value
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
				allowOutsideClick  : (): boolean => !this.swal.isLoading()
			})
			.then( (result: string) => {
				
				this.swal({
					type : 'success',
					title: this.settings.get('success'),
					text : result
				})
				.then( () => {
					location.reload();
				});
				
			})
			.catch(this.swal.noop);
			
		});
		
		// Activate the focus for ATUM fields.
		this.$productDataMetaBox
			.on('focus select2:opening', '.atum-field :input', (evt: JQueryEventObject) => $(evt.target).siblings('.input-group-prepend').addClass('focus') )
			.on('blur select2:close', '.atum-field :input', (evt: JQueryEventObject) => $(evt.target).siblings('.input-group-prepend').removeClass('focus') );
		
	}
	
}