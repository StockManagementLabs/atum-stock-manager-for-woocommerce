/* ========================================
   DATA EXPORT
   ======================================== */

import Settings from '../../config/_settings';
import Utils from '../../utils/_utils';

export default class DataExport {
	
	$pageWrapper: JQuery;
	$tabContentWrapper: JQuery;
	$tabsWrapper: JQuery;
	$exportForm: JQuery;
	
	constructor(
		private settings: Settings
	) {
		
		this.$pageWrapper = $('#wpbody-content');
		this.$tabContentWrapper = $('#screen-meta');
		this.$tabsWrapper = $('#screen-meta-links');
		
		this.createExportTab();
		
		this.$pageWrapper
		
			// Export button
			.on('submit', '#atum-export-settings', (evt: JQueryEventObject) => {
				evt.preventDefault();
				this.downloadReport();
			})
		
			// Disable max length option
			.on('change', '#disableMaxLength', (evt: JQueryEventObject) =>  {
				
				const $checkbox: JQuery = $(evt.currentTarget),
				      $input: JQuery    = $checkbox.parent().siblings('input[type=number]');
				
				if ($checkbox.is(':checked')) {
					$input.prop('disabled', true);
				}
				else {
					$input.prop('disabled', false);
				}
				
			});
		
	}
	
	/**
	 * Duplicate the "Screen Options" tab
	 */
	createExportTab() {
		
		const $tab: JQuery        = this.$tabsWrapper.find('#screen-options-link-wrap').clone(),
		      $tabContent: JQuery = this.$tabContentWrapper.find('#screen-options-wrap').clone();
		
		$tabContent.attr({
			'id'        : 'atum-export-wrap',
			'aria-label': this.settings.get('tabTitle'),
		});
		
		$tabContent.find('form').attr('id', 'atum-export-settings').find('input').removeAttr('id');
		$tabContent.find('.screen-options').remove();
		$tabContent.find('input[type=submit]').val( this.settings.get('submitTitle') );
		$tabContent.find('#screenoptionnonce').remove();
		
		// Add a fieldset for product type selection.
		if (typeof this.settings.get('productTypes') !== 'undefined') {
			
			const $typeFieldset: JQuery = $('<fieldset class="product-type" />');
			
			$typeFieldset.append(`<legend>${ this.settings.get('productTypesTitle') }</legend>`);
			$typeFieldset.append( this.settings.get('productTypes') );
			$typeFieldset.insertAfter( $tabContent.find('fieldset').last() );
			
		}
		
		// Add a fieldset for product category selection.
		if (typeof this.settings.get('categories') !== 'undefined') {
			
			const $catFieldset: JQuery = $('<fieldset class="product-category" />');
			
			$catFieldset.append(`<legend>${ this.settings.get('categoriesTitle') }</legend>`);
			$catFieldset.append( this.settings.get('categories') );
			$catFieldset.insertAfter( $tabContent.find('fieldset').last() );
			
		}
		
		// Add a fieldset for title length setup.
		const $titleLengthFieldset: JQuery = $('<fieldset class="title-length" />');
		
		$titleLengthFieldset.append(`<legend>${ this.settings.get('titleLength') }</legend>`);
		$titleLengthFieldset.append(`<input type="number" step="1" min="0" name="title_max_length" value="${ this.settings.get('maxLength') }"> `);
		$titleLengthFieldset.append(`<label><input type="checkbox" id="disableMaxLength" value="yes">${ this.settings.get('disableMaxLength') }</label>`);
		$titleLengthFieldset.insertAfter( $tabContent.find('fieldset').last() );
		
		// Add a fieldset for format output.
		const $formatFieldset: JQuery = $('<fieldset class="output-format" />');
		$formatFieldset.append(`<legend>${ this.settings.get('outputFormatTitle') }</legend>`);
		
		$.each(this.settings.get('outputFormats'), (key: string, value: string) => {
			$formatFieldset.append(`<label><input type="radio" name="output-format" value="${ key }">${ value }</label>`);
		});
		
		$formatFieldset.find('input[name=output-format]').first().prop('checked', true);
		$formatFieldset.insertAfter( $tabContent.find('fieldset').last() );
		
		// Clearfix
		$tabContent.find('.submit').before('<div class="clear"></div>');
		
		$tab.attr('id', 'atum-export-link-wrap')
			.find('button').attr({
				'id'           : 'show-export-settings-link',
				'aria-controls': 'atum-export-wrap'
			}).text( this.settings.get('tabTitle') );
		
		this.$tabContentWrapper.append($tabContent);
		this.$tabsWrapper.prepend($tab);
		
		// Use the WP's screenMeta toggleEvent method
		$('#show-export-settings-link').click( window['screenMeta'].toggleEvent );
		
		this.$exportForm = this.$pageWrapper.find('#atum-export-settings');
		
	}
	
	/**
	 * Download the report.
	 */
	downloadReport() {
		
		/*$('#export-file-downloader').remove();
		
		var $iframe = $('<iframe/>', {
			id  : 'export-file-downloader',
			src : ajaxurl + '?action=atum_export_data&token=' + atumExport.exportNonce + '&' + this.$exportForm.serialize()
		});
		
		$iframe.appendTo('body');*/
		
		window.open( `${ window['ajaxurl'] }?action=atum_export_data&page=${ Utils.getUrlParameter('page') }&screen=${ this.settings.get('screen') }&security=${ this.settings.get('exportNonce') }&${ this.$exportForm.serialize() }`, '_blank');
		
	}
	
}