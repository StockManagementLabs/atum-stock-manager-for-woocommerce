/**
 * Atum Dashboard
 *
 * @copyright Stock Management Labs ©2018
 * @since 1.3.9
 */
;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = "atumDashboard",
	    defaults = {
		    chartColors: {
			    red   : '#ff4848',
			    orange: '#efaf00',
			    green : '#69c61d',
			    blue  : '#00b8db'
		    }
	    };
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		this.elem = element;
		this.settings = $.extend({}, defaults, options);
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend( Plugin.prototype, {
		init: function() {
			
			this.$widgetsContainer = $(this.elem).find('.atum-widgets');
			this.buildWidgetsGrid();
			this.bindWidgetControls();
			this.initWidgets();
			this.buildNiceSelect();
			
		},
		buildWidgetsGrid: function() {
			
			var self           = this,
			    $gridStackElem = this.$widgetsContainer.find('.grid-stack');
			
			this.grid = $gridStackElem.gridstack({
				handle                : '.widget-header',
				alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
				verticalMargin        : 30,
				cellHeight            : 'auto',
				resizable             : {
					autoHide   : true,
					handles    : 'se, sw',
					containment: 'parent'
				}
			}).data('gridstack');
			
			// Bind events
			$gridStackElem.on('change', function(event, items) {
				self.saveWidgetsLayout();
				self.addScrollBars();
			});
			
		},
		bindWidgetControls: function() {
			
			var self = this;
		
			// Remove widget
			$('.atum-widget').find('.widget-close').click(function() {
				self.grid.removeWidget( $(this).closest('.atum-widget') );
			});
		
		},
		initWidgets: function() {
			
			var self = this;
		
			/*
			 * Sales Data widgets
			 */
			$('.stats-data-widget').find('select').change(function(e) {
				e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
				
				var $select          = $(this),
				    $widgetContainer = $select.closest('.stats-data-widget');
				
				$widgetContainer.find('.data:visible').hide();
				$widgetContainer.find('[data-value="' + $select.val() + '"]').fadeIn('fast');
				
			});
			
			/*
			 * Stock Control widget
			 */
			var $pieCanvas = $('.stock-chart').find('canvas'),
			    pieChart   = new Chart( $pieCanvas.get(0).getContext('2d'), {
				    type   : 'doughnut',
				    data   : {
					    datasets: [{
						    data           : [
							    $pieCanvas.data('lowstock'),
							    $pieCanvas.data('outstock'),
							    $pieCanvas.data('instock')
						    ],
						    backgroundColor: [
							    self.settings.chartColors.orange,
							    self.settings.chartColors.red,
							    self.settings.chartColors.green,
						    ]
					    }],
					    labels  : [
						    atumDashVars.lowStockLabel,
						    atumDashVars.outStockLabel,
						    atumDashVars.inStockLabel,
					    ]
				    },
				    options: {
					    responsive         : true,
					    legend             : false,
					    maintainAspectRatio: false,
					    animation          : {
						    animateScale : true,
						    animateRotate: true
					    },
					    cutoutPercentage   : 25
				    }
			    } );
			
			/*
			 * Videos widget
			 */
			
			var $videoWidget = $('.videos-widget');
			
			// Video Switcher
			$videoWidget.find('article a').click(function(e) {
				e.preventDefault();
				
				var $videoWrapper = $(this).closest('article'),
				    $videoPlayer  = $videoWidget.find('.video-player');
				
				$videoWrapper.siblings('.active').removeClass('active');
				$videoWrapper.addClass('active');
				
				$videoPlayer.find('iframe').attr('src', '//www.youtube.com/embed/' + $videoWrapper.data('video') + '?rel=0&modestbranding=1');
				$videoPlayer.find('.video-title').text($videoWrapper.find('.video-title').text());
				$videoPlayer.find('.video-meta').html($videoWrapper.find('.video-meta').html());
				$videoPlayer.find('.video-desc').text($videoWrapper.find('.video-desc').text());
			});
			
			// Video Layout Switcher
			$videoWidget.find('.video-list-layout a').click(function(e) {
				e.preventDefault();
				var $button = $(this);
				
				if ($button.hasClass('active')) {
					return false;
				}
				
				$videoWidget.find('.video-list').attr('data-view', $button.data('view'));
				$button.siblings('.active').removeClass('active');
				$button.addClass('active');
			});
			
			// Filter Videos
			$videoWidget.find('.video-filter-by').change(function() {
				
				var filterBy = $(this).val(),
				    $videos  = $videoWidget.find('article');
				
				if (filterBy === '') {
					$videos.fadeIn('fast');
				}
				else {
					$videos.not('.' + filterBy).hide();
					$videos.filter('.' + filterBy).fadeIn('fast');
				}
				
			});
			
			// Sort Videos
			$videoWidget.find('.video-sort-by').change(function() {
				
				var sortBy         = $(this).val(),
				    $videosWrapper = $videoWidget.find('.scroll-wrapper');
				
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						token: self.$widgetsContainer.data('nonce'),
						action: 'atum_videos_widget_sorting',
						sortby: sortBy
					},
					beforeSend: function() {
						$videosWrapper.addClass('overlay');
					},
					success: function(response) {
						
						if (response != -1) {
							$videosWrapper.children('.scrollbar-inner').html($(response).find('.scrollbar-inner').html());
							$videoWidget.find('.video-filter-by').change();
						}
						
						$videosWrapper.removeClass('overlay');
					},
					error: function() {
						$videosWrapper.removeClass('overlay');
					}
				});
			
			});
			
			// List Scrollbars
			this.addScrollBars();
		
		},
		serializeLayout: function(items) {
			
			var serializedItems = {};
			
			if (typeof items === 'undefined') {
				return serializedItems;
			}
		
			$.each(items, function(index, data) {
				
				serializedItems[data.id] = {
					x     : data.x,
					y     : data.y,
					height: data.height,
					width : data.width
				};
			
			});
			
			return serializedItems;
		
		},
		saveWidgetsLayout: function() {
			
			$.ajax({
				url    : ajaxurl,
				method : 'POST',
				data   : {
					action: 'atum_save_dashboard_layout',
					token : this.$widgetsContainer.data('nonce'),
					layout: this.serializeLayout(this.grid.grid.nodes)
				}
			});
			
		},
		buildNiceSelect: function($widget) {
			
			var $container = (typeof $widget !== 'undefined') ? $widget : this.$widgetsContainer;
			$container.find('select').niceSelect();
			
		},
		addScrollBars: function() {
			
			if ($('.scroll-wrapper').length) {
				$('.scrollbar-inner').scrollbar('destroy');
			}
			
			$('.scrollbar-inner').scrollbar({
				disableBodyScroll: true,
				onInit: function() {
					
					$('.scroll-wrapper').each(function () {
						var $scrollWrapper = $(this),
						    $currentWidget = $scrollWrapper.closest('.atum-widget');
						
						$scrollWrapper.css('max-height', $currentWidget.height() - $currentWidget.find('.widget-header').outerHeight() - 37  + 'px');
					});
					
				}
			});
			
		}
	} );
	
	// Lightweight plugin wrapper around the constructor, preventing against multiple instantiations
	$.fn[ pluginName ] = function( options ) {
		return this.each( function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
				$.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
			}
		} );
	};
	
	
	$(function(){
		// Instantiate the plugin
		$('.atum-dashboard').atumDashboard();
	});
	
} )( jQuery, window, document );

/**
 * jQuery Nice Select - v1.0
 * https://github.com/hernansartorio/jquery-nice-select
 * Made by Hernán Sartorio
 */
!function(e){e.fn.niceSelect=function(t){function s(t){t.after(e("<div></div>").addClass("nice-select").addClass(t.attr("class")||"").addClass(t.attr("disabled")?"disabled":"").attr("tabindex",t.attr("disabled")?null:"0").html('<span class="current"></span><ul class="list"></ul>'));var s=t.next(),n=t.find("option"),i=t.find("option:selected");s.find(".current").html(i.data("display")||i.text()),n.each(function(t){var n=e(this),i=n.data("display");s.find("ul").append(e("<li></li>").attr("data-value",n.val()).attr("data-display",i||null).addClass("option"+(n.is(":selected")?" selected":"")+(n.is(":disabled")?" disabled":"")).html(n.text()))})}if("string"==typeof t)return"update"==t?this.each(function(){var t=e(this),n=e(this).next(".nice-select"),i=n.hasClass("open");n.length&&(n.remove(),s(t),i&&t.next().trigger("click"))}):"destroy"==t?(this.each(function(){var t=e(this),s=e(this).next(".nice-select");s.length&&(s.remove(),t.css("display",""))}),0==e(".nice-select").length&&e(document).off(".nice_select")):console.log('Method "'+t+'" does not exist.'),this;this.hide(),this.each(function(){var t=e(this);t.next().hasClass("nice-select")||s(t)}),e(document).off(".nice_select"),e(document).on("click.nice_select",".nice-select",function(t){var s=e(this);e(".nice-select").not(s).removeClass("open"),s.toggleClass("open"),s.hasClass("open")?(s.find(".option"),s.find(".focus").removeClass("focus"),s.find(".selected").addClass("focus")):s.focus()}),e(document).on("click.nice_select",function(t){0===e(t.target).closest(".nice-select").length&&e(".nice-select").removeClass("open").find(".option")}),e(document).on("click.nice_select",".nice-select .option:not(.disabled)",function(t){var s=e(this),n=s.closest(".nice-select");n.find(".selected").removeClass("selected"),s.addClass("selected");var i=s.data("display")||s.text();n.find(".current").text(i),n.prev("select").val(s.data("value")).trigger("change")}),e(document).on("keydown.nice_select",".nice-select",function(t){var s=e(this),n=e(s.find(".focus")||s.find(".list .option.selected"));if(32==t.keyCode||13==t.keyCode)return s.hasClass("open")?n.trigger("click"):s.trigger("click"),!1;if(40==t.keyCode){if(s.hasClass("open")){var i=n.nextAll(".option:not(.disabled)").first();i.length>0&&(s.find(".focus").removeClass("focus"),i.addClass("focus"))}else s.trigger("click");return!1}if(38==t.keyCode){if(s.hasClass("open")){var l=n.prevAll(".option:not(.disabled)").first();l.length>0&&(s.find(".focus").removeClass("focus"),l.addClass("focus"))}else s.trigger("click");return!1}if(27==t.keyCode)s.hasClass("open")&&s.trigger("click");else if(9==t.keyCode&&s.hasClass("open"))return!1});var n=document.createElement("a").style;return n.cssText="pointer-events:auto","auto"!==n.pointerEvents&&e("html").addClass("no-csspointerevents"),this}}(jQuery);

jQuery.noConflict();