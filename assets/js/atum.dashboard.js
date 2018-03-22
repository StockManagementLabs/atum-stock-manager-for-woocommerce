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
			
			// Dynamic min height for widgets
			$gridStackElem.on('resizestart', function (event, ui) {
				var minHeight = ui.element.find('.widget-body').outerHeight() + ui.element.find('.widget-header').outerHeight();
				ui.element.closest('.atum-widget').css('min-height', minHeight);
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
			
			// Restore default layout and widgets
			var $restoreDashDefaults = $('.restore-defaults');
			$restoreDashDefaults.click(function() {
				
				swal({
					title              : atumDashVars.areYouSure,
					text               : atumDashVars.defaultsWillRestore,
					type               : 'warning',
					showCancelButton   : true,
					confirmButtonText  : atumDashVars.continue,
					cancelButtonText   : atumDashVars.cancel,
					reverseButtons     : true,
					allowOutsideClick  : false,
					showLoaderOnConfirm: true,
					preConfirm: function() {
						
						return new Promise(function (resolve, reject) {
							
							$.ajax({
								url       : ajaxurl,
								method    : 'POST',
								data      : {
									token : self.$widgetsContainer.data('nonce'),
									action: 'atum_dashboard_restore_layout'
								},
								dataType  : 'json',
								beforeSend: function () {
									$restoreDashDefaults.tooltip('destroy');
									$(self.elem).addClass('overlay');
								},
								success   : function () {
									resolve();
								},
								error: function() {
									reject();
								}
							});
							
						});
						
					}
				}).then(function () {
					location.reload();
				}).catch(swal.noop);
			
			});
			
			// Toltips
			$('[data-toggle="tooltip"]').tooltip();
		
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
				
				// TODO: IMPLEMENT WIDGET CONFIG
				console.log($(this).serialize());
			});
		
		},
		// If no widgets param is passed, all the widgets will be initialized
		initWidgets: function(widgets) {
			
			// TODO: DO NOT INIT WIDGETS THAT ARE NOT BEING DISPLAYED
			
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
					    id                  : 'value-chart',
					    curSymbol           : atumDashVars.statsValueCurSymbol,
					    curPosition         : atumDashVars.statsValueCurPosition,
					    label               : chartLegends.value,
					    data                : chartData.value || [],
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
				new Switchery($('#value-chart').get(0), {
					size : 'small',
					color: self.settings.chartColors.green
				});
				
				new Switchery($('#products-chart').get(0), {
					size : 'small',
					color: self.settings.chartColors.blue
				});
				
				// Hide/show charts with legend switches
				$('#value-chart, #products-chart').change(function (e) {
					
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
									
									if (dataset.id === 'value-chart') {
										
										// Update the data
										statsDataSets[index].data = typeof response.data.dataset !== 'undefined' && typeof response.data.dataset.value !== 'undefined' ? response.data.dataset.value : [];
										
										// Update the legend
										if (typeof response.data.legends !== 'undefined' && typeof response.data.legends.value !== 'undefined') {
											statsDataSets[index].label = response.data.legends.value;
											$('#value-chart').siblings('label').text(response.data.legends.value);
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
								    $pieCanvas.data('instock'),
								    $pieCanvas.data('unmanaged')
							    ],
							    backgroundColor: [
								    self.settings.chartColors.orange,
								    self.settings.chartColors.red,
								    self.settings.chartColors.green,
								    self.settings.chartColors.blue
							    ]
						    }],
						    labels  : [
							    atumDashVars.lowStockLabel,
							    atumDashVars.outStockLabel,
							    atumDashVars.inStockLabel,
							    atumDashVars.unmanagedLabel
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

/*!
 * Bootstrap v3.3.7 (http://getbootstrap.com)
 * Copyright 2011-2017 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

/*!
 * Tooltip plugin
 */
+function(t){"use strict";var e=t.fn.jquery.split(" ")[0].split(".");if(e[0]<2&&e[1]<9||1==e[0]&&9==e[1]&&e[2]<1||e[0]>3)throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 4")}(jQuery),+function(t){"use strict";function e(e){return this.each(function(){var o=t(this),n=o.data("bs.tooltip"),s="object"==typeof e&&e;!n&&/destroy|hide/.test(e)||(n||o.data("bs.tooltip",n=new i(this,s)),"string"==typeof e&&n[e]())})}var i=function(t,e){this.type=null,this.options=null,this.enabled=null,this.timeout=null,this.hoverState=null,this.$element=null,this.inState=null,this.init("tooltip",t,e)};i.VERSION="3.3.7",i.TRANSITION_DURATION=150,i.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1,viewport:{selector:"body",padding:0}},i.prototype.init=function(e,i,o){if(this.enabled=!0,this.type=e,this.$element=t(i),this.options=this.getOptions(o),this.$viewport=this.options.viewport&&t(t.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport),this.inState={click:!1,hover:!1,focus:!1},this.$element[0]instanceof document.constructor&&!this.options.selector)throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!");for(var n=this.options.trigger.split(" "),s=n.length;s--;){var r=n[s];if("click"==r)this.$element.on("click."+this.type,this.options.selector,t.proxy(this.toggle,this));else if("manual"!=r){var a="hover"==r?"mouseenter":"focusin",l="hover"==r?"mouseleave":"focusout";this.$element.on(a+"."+this.type,this.options.selector,t.proxy(this.enter,this)),this.$element.on(l+"."+this.type,this.options.selector,t.proxy(this.leave,this))}}this.options.selector?this._options=t.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},i.prototype.getDefaults=function(){return i.DEFAULTS},i.prototype.getOptions=function(e){return e=t.extend({},this.getDefaults(),this.$element.data(),e),e.delay&&"number"==typeof e.delay&&(e.delay={show:e.delay,hide:e.delay}),e},i.prototype.getDelegateOptions=function(){var e={},i=this.getDefaults();return this._options&&t.each(this._options,function(t,o){i[t]!=o&&(e[t]=o)}),e},i.prototype.enter=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusin"==e.type?"focus":"hover"]=!0),i.tip().hasClass("in")||"in"==i.hoverState?void(i.hoverState="in"):(clearTimeout(i.timeout),i.hoverState="in",i.options.delay&&i.options.delay.show?void(i.timeout=setTimeout(function(){"in"==i.hoverState&&i.show()},i.options.delay.show)):i.show())},i.prototype.isInStateTrue=function(){for(var t in this.inState)if(this.inState[t])return!0;return!1},i.prototype.leave=function(e){var i=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i)),e instanceof t.Event&&(i.inState["focusout"==e.type?"focus":"hover"]=!1),i.isInStateTrue()?void 0:(clearTimeout(i.timeout),i.hoverState="out",i.options.delay&&i.options.delay.hide?void(i.timeout=setTimeout(function(){"out"==i.hoverState&&i.hide()},i.options.delay.hide)):i.hide())},i.prototype.show=function(){var e=t.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);var o=t.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(e.isDefaultPrevented()||!o)return;var n=this,s=this.tip(),r=this.getUID(this.type);this.setContent(),s.attr("id",r),this.$element.attr("aria-describedby",r),this.options.animation&&s.addClass("fade");var a="function"==typeof this.options.placement?this.options.placement.call(this,s[0],this.$element[0]):this.options.placement,l=/\s?auto?\s?/i,p=l.test(a);p&&(a=a.replace(l,"")||"top"),s.detach().css({top:0,left:0,display:"block"}).addClass(a).data("bs."+this.type,this),this.options.container?s.appendTo(this.options.container):s.insertAfter(this.$element),this.$element.trigger("inserted.bs."+this.type);var h=this.getPosition(),u=s[0].offsetWidth,f=s[0].offsetHeight;if(p){var c=a,d=this.getPosition(this.$viewport);a="bottom"==a&&h.bottom+f>d.bottom?"top":"top"==a&&h.top-f<d.top?"bottom":"right"==a&&h.right+u>d.width?"left":"left"==a&&h.left-u<d.left?"right":a,s.removeClass(c).addClass(a)}var v=this.getCalculatedOffset(a,h,u,f);this.applyPlacement(v,a);var g=function(){var t=n.hoverState;n.$element.trigger("shown.bs."+n.type),n.hoverState=null,"out"==t&&n.leave(n)};t.support.transition&&this.$tip.hasClass("fade")?s.one("bsTransitionEnd",g).emulateTransitionEnd(i.TRANSITION_DURATION):g()}},i.prototype.applyPlacement=function(e,i){var o=this.tip(),n=o[0].offsetWidth,s=o[0].offsetHeight,r=parseInt(o.css("margin-top"),10),a=parseInt(o.css("margin-left"),10);isNaN(r)&&(r=0),isNaN(a)&&(a=0),e.top+=r,e.left+=a,t.offset.setOffset(o[0],t.extend({using:function(t){o.css({top:Math.round(t.top),left:Math.round(t.left)})}},e),0),o.addClass("in");var l=o[0].offsetWidth,p=o[0].offsetHeight;"top"==i&&p!=s&&(e.top=e.top+s-p);var h=this.getViewportAdjustedDelta(i,e,l,p);h.left?e.left+=h.left:e.top+=h.top;var u=/top|bottom/.test(i),f=u?2*h.left-n+l:2*h.top-s+p,c=u?"offsetWidth":"offsetHeight";o.offset(e),this.replaceArrow(f,o[0][c],u)},i.prototype.replaceArrow=function(t,e,i){this.arrow().css(i?"left":"top",50*(1-t/e)+"%").css(i?"top":"left","")},i.prototype.setContent=function(){var t=this.tip(),e=this.getTitle();t.find(".tooltip-inner")[this.options.html?"html":"text"](e),t.removeClass("fade in top bottom left right")},i.prototype.hide=function(e){function o(){"in"!=n.hoverState&&s.detach(),n.$element&&n.$element.removeAttr("aria-describedby").trigger("hidden.bs."+n.type),e&&e()}var n=this,s=t(this.$tip),r=t.Event("hide.bs."+this.type);return this.$element.trigger(r),r.isDefaultPrevented()?void 0:(s.removeClass("in"),t.support.transition&&s.hasClass("fade")?s.one("bsTransitionEnd",o).emulateTransitionEnd(i.TRANSITION_DURATION):o(),this.hoverState=null,this)},i.prototype.fixTitle=function(){var t=this.$element;(t.attr("title")||"string"!=typeof t.attr("data-original-title"))&&t.attr("data-original-title",t.attr("title")||"").attr("title","")},i.prototype.hasContent=function(){return this.getTitle()},i.prototype.getPosition=function(e){e=e||this.$element;var i=e[0],o="BODY"==i.tagName,n=i.getBoundingClientRect();null==n.width&&(n=t.extend({},n,{width:n.right-n.left,height:n.bottom-n.top}));var s=window.SVGElement&&i instanceof window.SVGElement,r=o?{top:0,left:0}:s?null:e.offset(),a={scroll:o?document.documentElement.scrollTop||document.body.scrollTop:e.scrollTop()},l=o?{width:t(window).width(),height:t(window).height()}:null;return t.extend({},n,a,l,r)},i.prototype.getCalculatedOffset=function(t,e,i,o){return"bottom"==t?{top:e.top+e.height,left:e.left+e.width/2-i/2}:"top"==t?{top:e.top-o,left:e.left+e.width/2-i/2}:"left"==t?{top:e.top+e.height/2-o/2,left:e.left-i}:{top:e.top+e.height/2-o/2,left:e.left+e.width}},i.prototype.getViewportAdjustedDelta=function(t,e,i,o){var n={top:0,left:0};if(!this.$viewport)return n;var s=this.options.viewport&&this.options.viewport.padding||0,r=this.getPosition(this.$viewport);if(/right|left/.test(t)){var a=e.top-s-r.scroll,l=e.top+s-r.scroll+o;a<r.top?n.top=r.top-a:l>r.top+r.height&&(n.top=r.top+r.height-l)}else{var p=e.left-s,h=e.left+s+i;p<r.left?n.left=r.left-p:h>r.right&&(n.left=r.left+r.width-h)}return n},i.prototype.getTitle=function(){var t,e=this.$element,i=this.options;return t=e.attr("data-original-title")||("function"==typeof i.title?i.title.call(e[0]):i.title)},i.prototype.getUID=function(t){do t+=~~(1e6*Math.random());while(document.getElementById(t));return t},i.prototype.tip=function(){if(!this.$tip&&(this.$tip=t(this.options.template),1!=this.$tip.length))throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!");return this.$tip},i.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},i.prototype.enable=function(){this.enabled=!0},i.prototype.disable=function(){this.enabled=!1},i.prototype.toggleEnabled=function(){this.enabled=!this.enabled},i.prototype.toggle=function(e){var i=this;e&&(i=t(e.currentTarget).data("bs."+this.type),i||(i=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,i))),e?(i.inState.click=!i.inState.click,i.isInStateTrue()?i.enter(i):i.leave(i)):i.tip().hasClass("in")?i.leave(i):i.enter(i)},i.prototype.destroy=function(){var t=this;clearTimeout(this.timeout),this.hide(function(){t.$element.off("."+t.type).removeData("bs."+t.type),t.$tip&&t.$tip.detach(),t.$tip=null,t.$arrow=null,t.$viewport=null,t.$element=null})};var o=t.fn.tooltip;t.fn.tooltip=e,t.fn.tooltip.Constructor=i,t.fn.tooltip.noConflict=function(){return t.fn.tooltip=o,this}}(jQuery),+function(t){"use strict";function e(){var t=document.createElement("bootstrap"),e={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var i in e)if(void 0!==t.style[i])return{end:e[i]};return!1}t.fn.emulateTransitionEnd=function(e){var i=!1,o=this;t(this).one("bsTransitionEnd",function(){i=!0});var n=function(){i||t(o).trigger(t.support.transition.end)};return setTimeout(n,e),this},t(function(){t.support.transition=e(),t.support.transition&&(t.event.special.bsTransitionEnd={bindType:t.support.transition.end,delegateType:t.support.transition.end,handle:function(e){return t(e.target).is(this)?e.handleObj.handler.apply(this,arguments):void 0}})})}(jQuery);

jQuery.noConflict();