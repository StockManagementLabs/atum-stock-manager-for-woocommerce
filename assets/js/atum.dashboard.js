/**
 * Atum Dashboard
 *
 * @copyright Stock Management Labs ©2018
 * @since 1.4.0
 */
;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumDashboard',
	    defaults   = {
		    chartColors: {
			    red       : '#ff4848',
			    orange    : '#efaf00',
			    green     : '#69c61d',
			    greenTrans: 'rgba(106, 200, 30, 0.79)',
			    greenLight: 'rgba(180, 240, 0, 0.79)',
			    greenBlue : 'rgba(30, 200, 149, 0.79)',
			    blue      : '#00b8db',
			    blueTrans : 'rgba(0, 183, 219, 0.79)'
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
			this.$addWidgetModalContent = $(this.elem).find('#tmpl-atum-modal-add-widgets');
			
			this.buildWidgetsGrid();
			this.bindDashButtons();
			this.bindWidgetControls();
			this.bindConfigControls();
			this.initWidgets();
			
			$(window).resize(function() {
				
				var width      = $(window).width(),
				    $dashCards = $('.dash-cards'),
					$videoList = $('.video-list-wrapper');
				
				if (width <= 480) {
					
					// Apply the caousel to dashboard cards in mobiles
					$dashCards.addClass('owl-carousel owl-theme').owlCarousel({
						items       : 1,
						margin      : 15,
						//autoplay: true,
						stagePadding: 30,
					});
					
					var videoCarousel = $videoList.find('.scroll-box').addClass('owl-carousel owl-theme').owlCarousel({
						items       : 2,
						margin      : 1,
						dots        : false,
						stagePadding: 15
					});
					
					// Video Carousel nav
					var $videoNextNav = $videoList.find('.carousel-nav-next'),
					    $videoPrevNav = $videoList.find('.carousel-nav-prev');
					
					$videoNextNav.click(function() {
						videoCarousel.trigger('next.owl.carousel');
					});
					
					$videoPrevNav.click(function() {
						videoCarousel.trigger('prev.owl.carousel');
					});
					
					videoCarousel.on('changed.owl.carousel', function(event) {
						if (event.item.index === 0) {
							$videoPrevNav.addClass('disabled');
						}
						else if (event.item.index === event.item.count - 2) {
							$videoNextNav.addClass('disabled');
						}
						else {
							$videoPrevNav.add($videoNextNav).removeClass('disabled');
						}
					});
					
				}
				else {
					
					// Remove the carousel in screens wider than mobile
					$('.owl-carousel').removeClass('owl-carousel owl-theme').trigger('destroy.owl.carousel');
						
				}
			
			}).resize();
			
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
		bindDashButtons: function() {
			
			var self = this;
		
			// "Add more widgets" popup
			$('.add-dash-widget').click(function() {
				
				swal({
					title            : atumDashVars.availableWidgets,
					html             : self.$addWidgetModalContent.html(),
					showConfirmButton: false,
					showCloseButton  : true,
					customClass      : 'add-widget-popup',
					width            : '620px',
					onOpen           : function (elem) {
						
						// Wait until the show animation is complete
						setTimeout(function() {
							self.addScrollBars($(elem));
						}, 300);
						
					},
					onClose          : function(elem) {
						self.removeScrollBars($(elem));
					}
				}).catch(swal.noop);
			
			});
			
			// Add widget
			$('body').on('click', '.add-widget-popup .add-widget', function() {
				
				var $button    = $(this),
				    widgetId = $button.closest('li').data('widget');
				
				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					data      : {
						token : self.$widgetsContainer.data('nonce'),
						action: 'atum_dashboard_add_widget',
						widget: widgetId
					},
					dataType  : 'json',
					beforeSend: function () {
					
					},
					success   : function (response) {
						
						if (typeof response === 'object' && response.success === true) {
							var layout = response.data.layout;
							self.grid.addWidget($(response.data.widget), null, null, layout.min, layout.height, true);
							self.initWidgets([widgetId]);
							$button.hide().siblings('.btn-info').show();
							self.toggleModalTemplateButtons(widgetId);
						}
						
					}
				});
				
			});
		
		},
		toggleModalTemplateButtons: function(widgetId) {
			this.$addWidgetModalContent.find('[data-widget="' + widgetId + '"]').find('.add-widget').toggle().siblings('.btn-info').toggle();
		},
		bindWidgetControls: function() {
			
			var self = this;
		
			// Remove widget
			$('.atum-widget').find('.widget-close').click(function() {
				var $widget = $(this).closest('.atum-widget');
				self.grid.removeWidget($widget);
				self.toggleModalTemplateButtons( $widget.data('gs-id') );
			});
			
			// Widget settings
			$('.atum-widget').find('.widget-settings').click(function() {
				$(this).closest('.widget-wrapper').find('.widget-config').show().siblings().hide();
			});
		
		},
		bindConfigControls: function() {
		
			// Cancel config
			$('.widget-config').find('.cancel-config').click(function() {
				$(this).closest('.widget-wrapper').find('.widget-config').hide().siblings().show();
			});
			
			// Save config
			$('.widget-config').submit(function(e) {
				e.preventDefault();
				
				console.log($(this).serialize());
			});
		
		},
		// If no widgets param is passed, all the widgets will be initialized
		initWidgets: function(widgets) {
			
			/*
			 * Statistics widget
			 */
			if (typeof widgets === 'undefined' || $.inArray('atum_statistics_widget', widgets) > -1) {
				this.initStatisticsWidget();
			}
		
			/*
			 * Sales Data widgets
			 */
			if (
				typeof widgets === 'undefined' || $.inArray('atum_sales_widget', widgets) > -1 ||
				$.inArray('atum_lost_sales_widget', widgets) > -1 || $.inArray('atum_orders_widget', widgets) > -1 ||
				$.inArray('atum_promo_sales_widget', widgets) > -1
			) {
				this.initSalesStatsWidget();
			}
			
			/*
			 * Stock Control widget
			 */
			if (typeof widgets === 'undefined' || $.inArray('atum_stock_control_widget', widgets) > -1) {
				this.initStockControlWidget();
			}
			
			/*
			 * Videos widget
			 */
			if (typeof widgets === 'undefined' || $.inArray('atum_videos_widget', widgets) > -1) {
				this.initVideosWidget();
			}
			
			// Lists' Scrollbars
			this.addScrollBars();
			
			// Selects
			this.buildNiceSelect();
		
		},
		initStatisticsWidget: function() {
			
			// TODO: MOVE TO A NEW FILE (MODULE)
			
			var self              = this,
			    $statisticsWidget = $('.statistics-widget');
			
			if ($statisticsWidget.length) {
				
				var $statsCanvas   = $statisticsWidget.find('canvas'),
				    statsCanvasCtx = $statsCanvas.get(0).getContext('2d'),
				    chartData      = $statsCanvas.data('chartdata'),
				    chartLabels    = this.getChartLabels($statsCanvas.data('period')),
				    chartLegends   = $statsCanvas.data('legends'),
				    gradientGreen  = statsCanvasCtx.createLinearGradient(0, 0, 1200, 0),
				    gradientBlue   = statsCanvasCtx.createLinearGradient(0, 0, 1200, 0);
				
				gradientGreen.addColorStop(0, self.settings.chartColors.greenLight);
				gradientGreen.addColorStop(1, self.settings.chartColors.greenTrans);
				
				gradientBlue.addColorStop(0, self.settings.chartColors.greenBlue);
				gradientBlue.addColorStop(1, self.settings.chartColors.blueTrans);
				
				var statsDataSets    = [{
					    id                  : 'earnings-chart',
					    curSymbol           : atumDashVars.statsEarningsCurSymbol,
					    curPosition         : atumDashVars.statsEarningsCurPosition,
					    label               : chartLegends.earnings,
					    data                : chartData.earnings || [],
					    backgroundColor     : self.settings.chartColors.greenLight,
					    borderColor         : gradientGreen,
					    borderWidth         : 8,
					    pointRadius         : 6,
					    pointBackgroundColor: self.settings.chartColors.green,
					    pointBorderColor    : '#FFF',
					    pointBorderWidth    : 2,
					    pointHoverRadius    : 6,
					    tooltipBackground   : 'linear-gradient(135deg, ' + self.settings.chartColors.green + ', ' + self.settings.chartColors.greenLight + ')',
					    fill                : false
				    }, {
					    id                  : 'products-chart',
					    units               : '',
					    label               : chartLegends.products,
					    data                : chartData.products || [],
					    backgroundColor     : self.settings.chartColors.greenBlue,
					    borderColor         : gradientBlue,
					    borderWidth         : 8,
					    pointRadius         : 6,
					    pointBackgroundColor: self.settings.chartColors.blue,
					    pointBorderColor    : '#FFF',
					    pointBorderWidth    : 2,
					    pointHoverRadius    : 6,
					    tooltipBackground   : 'linear-gradient(135deg, ' + self.settings.chartColors.blue + ', ' + self.settings.chartColors.greenBlue + ')',
					    fill                : false
				    }],
				    statsChartConfig = {
					    type   : 'line',
					    data   : {
						    labels  : chartLabels,
						    datasets: statsDataSets
					    },
					    options: {
						    responsive         : true,
						    maintainAspectRatio: false,
						    layout             : {
							    padding: {
								    top: 10
							    }
						    },
						    legend             : {
							    display: false
						    },
						    hover              : {
							    mode     : 'nearest',
							    intersect: true
						    },
						    scales             : {
							    xAxes: [{
								    gridLines: {
									    display        : false,
									    drawBorder     : false,
									    drawOnChartArea: true,
									    drawTicks      : true
								    }
							    }],
							    yAxes: [{
								    gridLines: {
									    display        : true,
									    drawBorder     : false,
									    drawOnChartArea: true,
									    drawTicks      : true
								    }
							    }]
						    },
						    tooltips           : {
							    enabled  : false,
							    mode     : 'nearest',
							    intersect: false,
							    custom   : function (tooltip) {
								
								    $(this._chart.canvas).css('cursor', 'pointer');
								
								    var positionY = this._chart.canvas.offsetTop;
								    var positionX = this._chart.canvas.offsetLeft;
								
								    $('.stats-chart-tooltip').css({
									    opacity: 0,
								    });
								
								    if (!tooltip || !tooltip.opacity) {
									    return;
								    }
								
								    if (tooltip.dataPoints.length > 0) {
									    tooltip.dataPoints.forEach(function (dataPoint) {
										
										    if (typeof statsDataSets[dataPoint.datasetIndex].curSymbol !== 'undefined') {
											    var curSymbol   = statsDataSets[dataPoint.datasetIndex].curSymbol,
											        curPosition = statsDataSets[dataPoint.datasetIndex].curPosition;
											
											    if (curPosition === 'left') {
												    dataPoint.yLabel = curSymbol + dataPoint.yLabel;
											    }
											    else {
												    dataPoint.yLabel += curSymbol;
											    }
										    }
										
										    var content  = dataPoint.yLabel,
										        $tooltip = $('#stats-chart-tooltip-' + dataPoint.datasetIndex);
										
										    $tooltip.html(content);
										    $tooltip.css({
											    opacity   : 1,
											    top       : positionY + dataPoint.y + 'px',
											    left      : positionX + dataPoint.x + 'px',
											    background: statsDataSets[dataPoint.datasetIndex].tooltipBackground
										    });
										
									    });
								    }
								
							    }
						    }
					    }
				    },
				    statsChart       = new Chart(statsCanvasCtx, statsChartConfig);
				
				// Enable switches
				new Switchery($('#earnings-chart').get(0), {
					size : 'small',
					color: self.settings.chartColors.green
				});
				
				new Switchery($('#products-chart').get(0), {
					size : 'small',
					color: self.settings.chartColors.blue
				});
				
				// Hide/show charts with legend switches
				$('#earnings-chart, #products-chart').change(function (e) {
					
					e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
					
					var $activeCharts  = $('.chart-legend').find('input:checkbox:checked'),
					    activeDatasets = [];
					
					if ($activeCharts.length) {
						
						$activeCharts.each(function () {
							var id = $(this).attr('id');
							
							$.each(statsDataSets, function (index, dataset) {
								if (dataset.id === id) {
									activeDatasets.push(dataset);
									return false;
								}
							});
						});
						
					}
					
					statsChartConfig.data.datasets = activeDatasets;
					statsChart.update();
					
				});
				
				// Change chart type
				$('.chart-type a').click(function (e) {
					
					e.preventDefault();
					
					var $chartTypeButton = $(this),
					    chartType        = $chartTypeButton.data('view');
					
					if ($chartTypeButton.hasClass('active')) {
						return false;
					}
					
					$chartTypeButton.siblings('.active').removeClass('active');
					$chartTypeButton.addClass('active');
					
					if (chartType === 'bar') {
						statsChartConfig.type = 'bar';
					}
					else {
						
						var fillingMode = chartType === 'line' ? false : 'start';
						statsChartConfig.type = 'line';
						
						$.each(statsChartConfig.data.datasets, function (index, dataset) {
							statsChartConfig.data.datasets[index].fill = fillingMode;
						});
						
					}
					
					statsChart.destroy();
					statsChart = new Chart(statsCanvasCtx, statsChartConfig);
					
				});
				
				// Sortable legends
				// TODO: WHEN SWITCHING POSITIONS, THE DATA IS SHOWED WRONGLY
				var $chartLegend = $('.chart-legend');
				$chartLegend.sortable({
					revert              : true,
					placeholder         : 'legend-switch legend-placeholder',
					forcePlaceholderSize: true,
					update              : function (event, ui) {
						
						var sort = [];
						
						$chartLegend.find('input:checkbox').each(function () {
							sort.push($(this).attr('id'));
						});
						
						$.each(statsChartConfig.data.datasets, function (index, dataset) {
							
							if (statsChartConfig.data.datasets[index].id !== sort[index]) {
								statsChartConfig.data.datasets.reverse();
							}
							
						});
						
						statsChart.update();
						
					}
				}).draggable({
					connectToSortable: '.chart-legend',
					helper           : 'clone',
					revert           : 'invalid'
				}).disableSelection();
				
				// Change chart data
				var $chartFilter = $('.chart-filter'),
				    statsAjaxFiltering;
				
				$chartFilter.find('select').change(function (e) {
					
					e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
					
					var $select = $(this);
					
					if (statsAjaxFiltering) {
						statsAjaxFiltering.abort();
					}
					
					statsAjaxFiltering = $.ajax({
						url       : ajaxurl,
						method    : 'POST',
						data      : {
							token       : self.$widgetsContainer.data('nonce'),
							action      : 'atum_statistics_widget_chart',
							chart_data  : $chartFilter.find('select.chart-data').val(),
							chart_period: $chartFilter.find('select.chart-period').val()
						},
						dataType  : 'json',
						beforeSend: function () {
							$select.siblings('.nice-select.loading').removeClass('loading');
							$select.next('.nice-select').addClass('loading');
						},
						success   : function (response) {
							
							if (typeof response === 'object' && response.success === true) {
								
								$.each(statsDataSets, function (index, dataset) {
									
									if (dataset.id === 'earnings-chart') {
										
										// Update the data
										statsDataSets[index].data = typeof response.data.dataset !== 'undefined' && typeof response.data.dataset.earnings !== 'undefined' ? response.data.dataset.earnings : [];
										
										// Update the legend
										if (typeof response.data.legends !== 'undefined' && typeof response.data.legends.earnings !== 'undefined') {
											statsDataSets[index].label = response.data.legends.earnings;
											$('#earnings-chart').siblings('label').text(response.data.legends.earnings);
										}
										
									}
									else {
										
										// Update the data
										statsDataSets[index].data = typeof response.data.dataset !== 'undefined' && typeof response.data.dataset.products !== 'undefined' ? response.data.dataset.products : [];
										
										// Updated the legend
										if (typeof response.data.legends !== 'undefined' && typeof response.data.legends.products !== 'undefined') {
											statsDataSets[index].label = response.data.legends.products;
											$('#products-chart').siblings('label').text(response.data.legends.products);
										}
										
									}
								});
								
								statsChartConfig.data.labels = self.getChartLabels(response.data.period);
								statsChart.update();
								$select.siblings('.nice-select.loading').removeClass('loading');
								
							}
						}
					});
					
				});
				
				// Mobile filter nav
				$statisticsWidget.find('.mobile-filter-nav li').click(function() {
				
					var $navItem = $(this);
					
					if ($navItem.hasClass('active')) {
						$navItem.removeClass('active').find('.status').text('+');
						$statisticsWidget.find('.chart-filter .visible-filter').removeClass('visible-filter');
					}
					else {
						$navItem.siblings('.active').removeClass('active').find('.status').text('+');
						$navItem.addClass('active').find('.status').text('-');
						
						$statisticsWidget.find('.chart-filter .visible-filter').removeClass('visible-filter');
						$statisticsWidget.find( $navItem.data('show-filter') ).addClass('visible-filter');
					}
				
				});
				
			}
			
		},
		initSalesStatsWidget: function() {
			
			// TODO: MOVE TO A NEW FILE (MODULE)
			
			$('.stats-data-widget').find('select').change(function(e) {
				e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
				
				var $select          = $(this),
				    $widgetContainer = $select.closest('.stats-data-widget');
				
				$widgetContainer.find('.data:visible').hide();
				$widgetContainer.find('[data-value="' + $select.val() + '"]').fadeIn('fast');
				
			});
		
		},
		initStockControlWidget: function() {
			
			// TODO: MOVE TO A NEW FILE (MODULE)
			
			var self                = this,
			    $stockControlWidget = $('.stock-chart');
			
			if ($stockControlWidget.length) {
				
				var $pieCanvas = $stockControlWidget.find('canvas'),
				    pieChart   = new Chart($pieCanvas.get(0).getContext('2d'), {
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
						    legend             : {
							    display: false
						    },
						    maintainAspectRatio: false,
						    animation          : {
							    animateScale : true,
							    animateRotate: true
						    },
						    cutoutPercentage   : 25,
						    tooltips           : {
							    enabled: false,
							    custom : function (tooltip) {
								
								    // Tooltip Element
								    var tooltipEl = $('.stock-chart-tooltip').get(0);
								
								    // Hide if no tooltip
								    if (tooltip.opacity === 0) {
									    tooltipEl.style.opacity = 0;
									    return;
								    }
								
								    // Set caret Position
								    tooltipEl.classList.remove('above', 'below', 'no-transform');
								
								    if (tooltip.yAlign) {
									    tooltipEl.classList.add(tooltip.yAlign);
								    }
								    else {
									    tooltipEl.classList.add('no-transform');
								    }
								
								    function getBody(bodyItem) {
									    return bodyItem.lines;
								    }
								
								    // Set Text
								    if (tooltip.body) {
									    var titleLines = tooltip.title || [],
									        bodyLines  = tooltip.body.map(getBody),
									        innerHtml  = '<thead>';
									
									    titleLines.forEach(function (title) {
										    innerHtml += '<tr><th>' + title + '</th></tr>';
									    });
									    innerHtml += '</thead><tbody>';
									
									    bodyLines.forEach(function (body, i) {
										    var colors = tooltip.labelColors[i],
										        style  = 'background:' + colors.backgroundColor + '; border-color:' + colors.borderColor + '; border-width: 2px',
										        span   = '<span class="stock-chart-tooltip-key" style="' + style + '"></span>';
										
										    innerHtml += '<tr><td>' + span + body + '</td></tr>';
									    });
									    innerHtml += '</tbody>';
									
									    var tableRoot = tooltipEl.querySelector('table');
									    tableRoot.innerHTML = innerHtml;
								    }
								
								    var positionY = this._chart.canvas.offsetTop;
								    var positionX = this._chart.canvas.offsetLeft;
								
								    // Display, position, and set styles for font
								    tooltipEl.style.opacity = 1;
								    tooltipEl.style.left = positionX + tooltip.caretX + 'px';
								    tooltipEl.style.top = positionY + tooltip.caretY + 'px';
								    tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
								
							    }
						    }
					    }
				    });
				
			}
		
		},
		initVideosWidget: function () {
			
			// TODO: MOVE TO A NEW FILE (MODULE)
			
			var self         = this,
				$videoWidget = $('.videos-widget');
			
			// Video Switcher
			$videoWidget.on('click', 'article a', function(e) {
				e.preventDefault();
				
				var $videoItem   = $(this).closest('article'),
				    $videoPlayer = $videoWidget.find('.video-player'),
				    videoId      = $videoItem.data('video');
				
				$videoItem.siblings('.active').removeClass('active');
				$videoItem.addClass('active');
				
				$videoPlayer.find('iframe').attr('src', '//www.youtube.com/embed/' + videoId + '?rel=0&modestbranding=1');
				$videoPlayer.find('.video-title').text($videoItem.find('.video-title').text());
				$videoPlayer.find('.video-meta').html($videoItem.find('.video-meta').html());
				$videoPlayer.find('.video-desc').text($videoItem.find('.video-desc').text());
				$videoItem.data('video', videoId);
			});
			
			// Video Layout Switcher
			$videoWidget.find('.video-list-layout a').click(function(e) {
				e.preventDefault();
				var $button = $(this);
				
				if ($button.hasClass('active')) {
					return false;
				}
				
				$videoWidget.find('.video-list').attr('data-view', $button.data('view'));
				
				self.removeScrollBars($videoWidget);
				
				setTimeout(function() {
					self.addScrollBars($videoWidget);
				}, 400);
				
				$button.siblings('.active').removeClass('active');
				$button.addClass('active');
			});
			
			// Filter Videos
			$videoWidget.find('.video-filter-by').change(function(e) {
				e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
				self.filterVideos($videoWidget);
			});
			
			// Sort Videos
			$videoWidget.find('.video-sort-by').change(function(e) {
				
				e.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
				
				var sortBy         = $(this).val(),
				    $videosWrapper = $videoWidget.find('.scroll-box');
				
				$.ajax({
					url       : ajaxurl,
					method    : 'POST',
					data      : {
						token : self.$widgetsContainer.data('nonce'),
						action: 'atum_videos_widget_sorting',
						sortby: sortBy
					},
					beforeSend: function () {
						$videosWrapper.addClass('overlay');
					},
					success   : function (response) {
						
						if (response != -1) {
							$videosWrapper.html($(response).find('.scroll-box').html());
							self.filterVideos($videoWidget);
						}
						
						$videosWrapper.removeClass('overlay');
					},
					error     : function () {
						$videosWrapper.removeClass('overlay');
					}
				});
				
			});
			
		},
		filterVideos: function($videoWidget) {
			
			var $videos  = $videoWidget.find('article'),
			    filterBy = $videoWidget.find('.video-filter-by').val();
			
			if (filterBy === '') {
				$videos.fadeIn('fast');
			}
			else {
				$videos.not('.' + filterBy).hide();
				$videos.filter('.' + filterBy).fadeIn('fast');
			}
		
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
					action: 'atum_dashboard_save_layout',
					token : this.$widgetsContainer.data('nonce'),
					layout: this.serializeLayout(this.grid.grid.nodes)
				}
			});
			
		},
		buildNiceSelect: function($widget) {
			
			var $container = (typeof $widget !== 'undefined') ? $widget : this.$widgetsContainer;
			$container.find('select').niceSelect();
			
		},
		getChartLabels: function(dataPeriod) {
			
			var chartLabels = [];
			
			switch (dataPeriod) {
				case 'month':
					chartLabels = atumDashVars.months;
					break;
				
				case 'monthDay':
					var i = 1;
					for(; i <= atumDashVars.numDaysCurMonth; i++) {
						chartLabels.push(i);
					}
					break;
				
				case 'weekDay':
					chartLabels = atumDashVars.days;
					break;
			}
			
			return chartLabels;
			
		},
		getScrollBars: function($elem) {
			return (typeof $elem !== 'undefined') ? $elem.find('.scroll-box') : this.$widgetsContainer.find('.scroll-box');
		},
		addScrollBars: function($elem) {
			
			var $boxSelector  = this.getScrollBars($elem);
			
			if ($boxSelector.length) {
				$boxSelector.niceScroll({
					cursorcolor       : '#e1e1e1',
					cursoropacitymin  : 0.8,
					cursorwidth       : '4px',
					cursorborderradius: '3px',
					background        : 'rgba(225, 225, 225, 0.3)',
					bouncescroll      : false
				});
			}
			
		},
		removeScrollBars: function($elem) {
			var $boxSelector = this.getScrollBars($elem);
			
			if ($boxSelector.length) {
				$boxSelector.getNiceScroll().remove();
			}
		},
		resizeScrollBars: function($elem) {
			var $boxSelector = this.getScrollBars($elem);
			
			if ($boxSelector.length) {
				$boxSelector.getNiceScroll().resize();
			}
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