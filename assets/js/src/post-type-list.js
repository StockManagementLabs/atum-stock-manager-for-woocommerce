/**
 * Atum Post Type List Tables
 *
 * @copyright Stock Management Labs ©2019
 *
 * @since 1.5.0
 */

// Only load Babel Polyfill if is not being included by another library
if (!global._babelPolyfill) {
	require('babel-polyfill');
}

window.$ = window.jQuery;

/**
 * Third Party Plugins
 */

import '../vendor/jquery.jscrollpane';               // A fixed version compatible with webpack
import 'hammerjs/hammer.min';                        // From node_modules
import '../vendor/select2';                          // A fixed version compatible with webpack


/**
 * Components
 */

import Settings from './config/_settings';


// Modules that need to execute when the DOM is ready should go here.
jQuery( ($) => {
	
	// Get the settings from localized var.
	Settings.init('atumPostTypeListVars');
	
	// Initialize components.
	// TODO: NEED A REFACTORY AND TO EXTERNALIZE IT AS A MODULE.
	let postTypeList =  {
		init: function() {
			
			this.$atumTable = $('.wp-list-table');
			$('.top').after('<div id="table-container"></div>');
			this.$tableContainer = $('#table-container');
			
			// Add placeholder to input search.
			$('#post-search-input').attr('placeholder', Settings.get('placeholderSearch'));
			
			// Change nav and search div position.
			$('#posts-filter').prepend($('.subsubsub'));
			$('.subsubsub').append($('.search-box'));
			$('.wp-heading-inline').append($('.page-title-action'));
			$('.page-title-action').show();
			
			// Table position and id.
			this.$tableContainer.append(this.$atumTable);
			this.$atumTable.attr('id', 'list-table');
			
			// Add active class row function.
			this.addActiveClassRow();
			this.addScrollBar();
			
			// Footer position.
			$(window).on('load', function () {
				$('#wpfooter').show();
			});
			
			$('select').select2({
				minimumResultsForSearch: 10
			});
		},
		/**
		 * Add/remove row active class when checkbox is clicked
		 */
		addActiveClassRow: function() {
			
			let self = this;
			
			this.$atumTable.find('tbody .check-column input:checkbox').change(function () {
				
				let $checkboxRow = self.$atumTable.find("#post-" + $(this).val());
				
				if ( $(this).is(':checked') ) {
					$checkboxRow.addClass('active-row');
				}
				else{
					$checkboxRow.removeClass('active-row');
				}
				
			});
			
			$('#cb-select-all-1').change(function () {
				
				$('tbody tr').each(function () {
					
					let $row = $(this);
					
					if ( $row.find('input[type=checkbox]').is(':checked') ) {
						$row.addClass('active-row');
					}
					else {
						$row.removeClass('active-row');
					}
					
				});
				
			});
			
		},
		/**
		 * Add the horizontal scroll bar to the table
		 */
		addScrollBar: function() {
			
			// Wait until the thumbs are loaded and enable JScrollpane.
			let self          = this,
			    $tableWrapper = this.$tableContainer,
			    scrollOpts    = {
				    horizontalGutter: 0,
				    verticalGutter  : 0,
				    resizeSensor    : true
			    };
			
			this.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
			this.jScrollApi  = this.$scrollPane.data('jsp');
			
			// Drag and drop scrolling on desktops
			let hammertime = new Hammer(this.$scrollPane.get(0), {});
			
			hammertime.on('panright panleft', (evt) => {
				
				const paneStartX   = self.jScrollApi.getContentPositionX(),
				      offset       = 20, // Move 20px each time (knowing that hammer gives the pan event a default threshold of 10)
				      displacement = evt.type === 'panright' ? paneStartX - offset : paneStartX + offset;
				
				self.jScrollApi.scrollToX( displacement, false);
				
			});
		},
	};
	
	postTypeList.init();
	
});