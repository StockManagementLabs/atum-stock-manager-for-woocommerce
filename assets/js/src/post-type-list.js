/**
 * Atum Post Type List Tables
 *
 * @copyright Stock Management Labs ©2018
 *
 * @since 1.5.0
 */

;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumPostTypeList',
	    defaults   = {
		
	    };
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		
		// Initialize selectors
		this.$settingsWrapper = $(element);
		
		// We don't want to alter the default options for future instances of the plugin
		// Load the localized vars to the plugin settings too
		this.settings = $.extend( {}, defaults, atumPostTypeListVars || {}, options || {} );
		
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend(Plugin.prototype, {
		
		navigationReady  : false,
		numHashParameters: 0,
		
		init: function() {
			
			var self = this;
			
			this.$atumTable = $('.wp-list-table');
			$('.top').after('<div id="table-container"></div>');
			this.$tableContainer = $('#table-container');
			
			// Add placeholder to input search
			$('#post-search-input').attr('placeholder', self.settings.placeholderSearch);
			// Change nav and search div position
			$('#posts-filter').prepend($('.subsubsub'));
			$('.subsubsub').append($('.search-box'));
			$('.wp-heading-inline').append($('.page-title-action'));
			$('.page-title-action').show();
			
			// Table position and id
			this.$tableContainer.append(this.$atumTable);
			this.$atumTable.attr('id', 'list-table');
			
			// Add active class row function
			this.addActiveClassRow();
			this.addScrollBar();
			
			// Footer position
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
			var self = this;
			self.$atumTable.find('tbody .check-column input:checkbox').change(function () {
				var $checkboxRow = self.$atumTable.find("#post-" + $(this).val());
				if ( $(this).is(':checked') ) {
					$checkboxRow.addClass('active-row');
				}
				else{
					$checkboxRow.removeClass('active-row');
				}
			});
			
			$('#cb-select-all-1').change(function () {
				$('tbody tr').each(function () {
					var $checkbox = $(this).find('input[type=checkbox]');
					if ( $checkbox.is(':checked') ) {
						$(this).addClass('active-row');
					}
					else {
						$(this).removeClass('active-row');
					}
				});
			});
		},
		/**
		 * Add the horizontal scroll bar to the table
		 */
		addScrollBar: function() {
			
			// Wait until the thumbs are loaded and enable JScrollpane
			var self          = this,
			    $tableWrapper = self.$tableContainer,
			    scrollOpts    = {
				    horizontalGutter: 0,
				    verticalGutter  : 0,
				    resizeSensor    : true
			    };
			
			self.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
			self.jScrollApi  = self.$scrollPane.data('jsp');
			
			// Bind events
			self.$scrollPane
				.on('jsp-initialised', function (event, isScrollable) {
				
				
				})
				.on('jsp-scroll-x', function (event, scrollPositionX, isAtLeft, isAtRight) {
				
				});
			
			// Drag and drop scrolling on desktops
			var hammertime = new Hammer(self.$scrollPane.get(0), {});
			
			hammertime.on('panright panleft', function (ev) {
				
				var paneStartX   = self.jScrollApi.getContentPositionX(),
				    offset       = 20, // Move 20px each time (knowing that hammer gives the pan event a default threshold of 10)
				    displacement = ev.type === 'panright' ? paneStartX - offset : paneStartX + offset
				
				self.jScrollApi.scrollToX( displacement, false)
				
			});
		},
	});
	
	// A really lightweight plugin wrapper around the constructor, preventing against multiple instantiations
	$.fn[ pluginName ] = function( options ) {
		return this.each( function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
				$.data( this, "plugin_" +
					pluginName, new Plugin( this, options ) );
			}
		} );
	};
	
	
	// Init the plugin on document ready
	$(function () {
		
		// Init ATUM Settings
		$('.wrap').atumPostTypeList();
		
	});
	
	/**
	 * --------------------------------------------------------------------------
	 * Bootstrap (v4.1.1): button.js
	 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
	 * --------------------------------------------------------------------------
	 */
	function _defineProperties(e,t){for(var n=0;n<t.length;n++){var s=t[n];s.enumerable=s.enumerable||!1,s.configurable=!0,"value"in s&&(s.writable=!0),Object.defineProperty(e,s.key,s)}}function _createClass(e,t,n){return t&&_defineProperties(e.prototype,t),n&&_defineProperties(e,n),e}var Button=function(e){var t="button",n="bs.button",s="."+n,a=".data-api",i=e.fn[t],r="active",o="btn",l="focus",u='[data-toggle^="button"]',c='[data-toggle="buttons"]',f="input",_=".active",d=".btn",h={CLICK_DATA_API:"click"+s+a,FOCUS_BLUR_DATA_API:"focus"+s+a+" blur"+s+a},g=function(){function t(e){this._element=e}var s=t.prototype;return s.toggle=function(){var t=!0,n=!0,s=e(this._element).closest(c)[0];if(s){var a=e(this._element).find(f)[0];if(a){if("radio"===a.type)if(a.checked&&e(this._element).hasClass(r))t=!1;else{var i=e(s).find(_)[0];i&&e(i).removeClass(r)}if(t){if(a.hasAttribute("disabled")||s.hasAttribute("disabled")||a.classList.contains("disabled")||s.classList.contains("disabled"))return;a.checked=!e(this._element).hasClass(r),e(a).trigger("change")}a.focus(),n=!1}}n&&this._element.setAttribute("aria-pressed",!e(this._element).hasClass(r)),t&&e(this._element).toggleClass(r)},s.dispose=function(){e.removeData(this._element,n),this._element=null},t._jQueryInterface=function(s){return this.each(function(){var a=e(this).data(n);a||(a=new t(this),e(this).data(n,a)),"toggle"===s&&a[s]()})},_createClass(t,null,[{key:"VERSION",get:function(){return"4.1.1"}}]),t}();return e(document).on(h.CLICK_DATA_API,u,function(t){t.preventDefault();var n=t.target;e(n).hasClass(o)||(n=e(n).closest(d)),g._jQueryInterface.call(e(n),"toggle")}).on(h.FOCUS_BLUR_DATA_API,u,function(t){var n=e(t.target).closest(d)[0];e(n).toggleClass(l,/^focus(in)?$/.test(t.type))}),e.fn[t]=g._jQueryInterface,e.fn[t].Constructor=g,e.fn[t].noConflict=function(){return e.fn[t]=i,g._jQueryInterface},g}($);
	
} )( jQuery, window, document );