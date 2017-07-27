/**
 * Atum Data Export
 *
 * @copyright ©2017 Stock Management Labs
 * @since 1.2.5
 */

;( function( $, window, document ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumDataExport',
	    defaults = {};
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		this.element = element;
		this.settings = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend( Plugin.prototype, {
		init: function() {
			
			var self = this;
			this.$tabContentWrapper = $('#screen-meta');
			this.$tabsWrapper = $('#screen-meta-links');
			
			this.createExportTab();
			
			// Export button
			$(this.element).on('submit', '#atum-export-settings', function(e) {
				e.preventDefault();
				self.downloadReport();
			});
			
		},
		
		// Duplicate the "Screen Options" tab
		createExportTab: function() {
			
			var $tab        = this.$tabsWrapper.find('#screen-options-link-wrap').clone(),
			    $tabContent = this.$tabContentWrapper.find('#screen-options-wrap').clone();
			
			$tabContent.attr({
				'id'        : 'atum-export-wrap',
				'aria-label': atumExport.tabTitle
			});
			
			$tabContent.find('form').attr('id', 'atum-export-settings');
			$tabContent.find('.screen-options').remove();
			$tabContent.find('input[type=submit]').val(atumExport.submitTitle);
			$tabContent.find('#screenoptionnonce').remove();
			
			// Add a new fieldset for product type selection
			var $typeFieldset = $('<fieldset class="product-type" />');
			$typeFieldset.append('<legend>' + atumExport.productTypesTitle + '</legend>');
			$typeFieldset.append(atumExport.productTypes);
			$typeFieldset.insertAfter( $tabContent.find('fieldset').last() );
			
			// Add a new fieldset for product category selection
			var $typeFieldset = $('<fieldset class="product-category" />');
			$typeFieldset.append('<legend>' + atumExport.categoriesTitle + '</legend>');
			$typeFieldset.append(atumExport.categories);
			$typeFieldset.insertAfter( $tabContent.find('fieldset').last() );
			
			// Add a new fieldset for format output
			var $formatFieldset = $('<fieldset class="output-format" />');
			$formatFieldset.append('<legend>' + atumExport.outputFormatTitle + '</legend>');
			
			$.each(atumExport.outputFormats, function(key, value) {
				$formatFieldset.append('<label><input type="radio" name="output-format" value="' + key + '">' + value + '</label>');
			});
			
			$formatFieldset.find('input[name=output-format]').first().prop('checked', true);
			$formatFieldset.insertAfter( $tabContent.find('fieldset').last() );
			
			$tab.attr('id', 'atum-export-link-wrap')
				.find('button').attr({
					'id'           : 'show-export-settings-link',
					'aria-controls': 'atum-export-wrap'
				}).text(atumExport.tabTitle);
			
			this.$tabContentWrapper.append($tabContent);
			this.$tabsWrapper.prepend($tab);
			
			// Use the WP's screenMeta toggleEvent method
			$('#show-export-settings-link').click(screenMeta.toggleEvent);
			
			this.$exportForm = $(this.element).find('#atum-export-settings');
		
		},
		
		// Download the report
		downloadReport: function() {
			
			/*$('#export-file-downloader').remove();
			
			var $iframe = $('<iframe/>', {
				id  : 'export-file-downloader',
				src : ajaxurl + '?action=atum_export_data&token=' + atumExport.exportNonce + '&' + this.$exportForm.serialize()
			});
			
			$iframe.appendTo('body');*/
			
			window.open(ajaxurl + '?action=atum_export_data&token=' + atumExport.exportNonce + '&' + this.$exportForm.serialize(), '_blank');
		
		}
		
	} );
	
	// A really lightweight plugin wrapper around the constructor, preventing against multiple instantiations
	$.fn[ pluginName ] = function( options ) {
		return this.each( function() {
			if ( !$.data( this, 'plugin_' + pluginName ) ) {
				$.data( this, 'plugin_' +
					pluginName, new Plugin( this, options ) );
			}
		} );
	};
	
	// Init the plugin on document ready
	$(function () {
		$('#wpbody-content').atumDataExport();
	});
	
} )( jQuery, window, document );

jQuery.noConflict();
