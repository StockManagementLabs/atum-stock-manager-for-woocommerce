/* =======================================
   DASHBOARD STATISTICS WIDGET
   ======================================= */

import Chart from 'chart.js/dist/Chart.bundle.min';
import Settings from '../../../config/_settings';
import Utils from '../../../utils/_utils';

export default class StatisticsWidget {
	
	$statisticsWidget: JQuery;
	$statsCanvas: JQuery;
	statsCanvasCtx: any;
	chartData: any;
	chartLabels: string[];
	chartLegends: any;
	gradientGreen: any;
	gradientBlue: any;
	statsDataSets: any;
	statsChartConfig: any;
	statsChart: any;
	
	constructor(
		private settings: Settings,
		private $widgetsContainer: JQuery
	) {
		
		this.$statisticsWidget = $('.statistics-widget');
		
		if (this.$statisticsWidget.length) {
			this.initChart();
		}
		
	}
	
	initChart() {
		
		this.$statsCanvas   = this.$statisticsWidget.find('canvas');
		this.statsCanvasCtx = (<HTMLCanvasElement>this.$statsCanvas.get(0)).getContext('2d');
		this.chartData      = this.$statsCanvas.data('chartdata');
		this.chartLabels    = this.getChartLabels( this.$statsCanvas.data('period') );
		this.chartLegends   = this.$statsCanvas.data('legends');
		this.gradientGreen  = this.statsCanvasCtx.createLinearGradient(0, 0, 1200, 0);
		this.gradientBlue   = this.statsCanvasCtx.createLinearGradient(0, 0, 1200, 0);
		
		this.gradientGreen.addColorStop(0, this.settings.get('chartColors').greenLight);
		this.gradientGreen.addColorStop(1, this.settings.get('chartColors').greenTrans);
		
		this.gradientBlue.addColorStop(0, this.settings.get('chartColors').greenBlue);
		this.gradientBlue.addColorStop(1, this.settings.get('chartColors').blueTrans);
		
		this.statsDataSets    = [{
		    id                  : 'value-chart',
		    curSymbol           : this.settings.get('statsValueCurSymbol'),
		    curPosition         : this.settings.get('statsValueCurPosition'),
		    label               : this.chartLegends.value,
		    data                : this.chartData.value || [],
		    backgroundColor     : this.settings.get('chartColors').greenLight,
		    borderColor         : this.gradientGreen,
		    borderWidth         : 8,
		    pointRadius         : 6,
		    pointBackgroundColor: this.settings.get('chartColors').green,
		    pointBorderColor    : '#FFF',
		    pointBorderWidth    : 2,
		    pointHoverRadius    : 6,
		    tooltipBackground   : 'linear-gradient(135deg, ' + this.settings.get('chartColors').green + ', ' + this.settings.get('chartColors').greenLight + ')',
		    fill                : false
	    }, {
		    id                  : 'products-chart',
		    units               : '',
		    label               : this.chartLegends.products,
		    data                : this.chartData.products || [],
		    backgroundColor     : this.settings.get('chartColors').greenBlue,
		    borderColor         : this.gradientBlue,
		    borderWidth         : 8,
		    pointRadius         : 6,
		    pointBackgroundColor: this.settings.get('chartColors').blue,
		    pointBorderColor    : '#FFF',
		    pointBorderWidth    : 2,
		    pointHoverRadius    : 6,
		    tooltipBackground   : 'linear-gradient(135deg, ' + this.settings.get('chartColors').blue + ', ' + this.settings.get('chartColors').greenBlue + ')',
		    fill                : false
	    }];

        var style = getComputedStyle(document.body);

	    this.statsChartConfig = {
		    type   : 'line',
		    data   : {
			    labels  : this.chartLabels,
			    datasets: this.statsDataSets
		    },
		    options: {
			    responsive         : true,
			    maintainAspectRatio: false,
			    layout             : {
				    padding: {
					    top: 10,
					    left: 30
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
						    drawTicks      : true,
                            color          : style.getPropertyValue('--dash-statistics-grid-lines')
					    },
                        ticks: {
							reverse: Utils.checkRTL('reverse'),
                            fontColor: style.getPropertyValue('--dash-statistics-ticks'),
                        }

				    }],
				    yAxes: [{
					    gridLines: {
						    display        : true,
						    drawBorder     : false,
						    drawOnChartArea: true,
						    drawTicks      : true,
                            color          : style.getPropertyValue('--dash-statistics-grid-lines')
					    },
                        ticks: {
                            fontColor: style.getPropertyValue('--dash-statistics-ticks'),
                        },
                        position: Utils.checkRTL('xSide'),
				    }]
			    },
			    tooltips           : {
				    enabled  : false,
				    mode     : 'nearest',
				    intersect: false,
				    custom   : (tooltip: any) => {
					
					    $(this.statsChart.canvas).css('cursor', 'pointer');
					
					    const positionY: number = this.statsChart.canvas.offsetTop,
					          positionX: number = this.statsChart.canvas.offsetLeft;
					
					    $('.stats-chart-tooltip').css('opacity', 0);
					
					    if (!tooltip || !tooltip.opacity) {
						    return;
					    }
					
					    if (tooltip.dataPoints.length > 0) {
					    	
						    tooltip.dataPoints.forEach( (dataPoint: any) => {
							
							    if (typeof this.statsDataSets[dataPoint.datasetIndex].curSymbol !== 'undefined') {
								
								    const curSymbol: string   = this.statsDataSets[dataPoint.datasetIndex].curSymbol,
								          curPosition: string = this.statsDataSets[dataPoint.datasetIndex].curPosition;
								
								    if (curPosition === 'left') {
									    dataPoint.yLabel = curSymbol + dataPoint.yLabel;
								    }
								    else {
									    dataPoint.yLabel += curSymbol;
								    }
								    
							    }
							
							    const content: string  = dataPoint.yLabel,
							          $tooltip: JQuery = $(`#stats-chart-tooltip-${ dataPoint.datasetIndex}`);
							
							    $tooltip.html(content);
							    $tooltip.css({
								    opacity   : 1,
								    top       : positionY + dataPoint.y + 'px',
								    left      : positionX + dataPoint.x + 'px',
								    background: this.statsDataSets[dataPoint.datasetIndex].tooltipBackground,
							    });
							
						    });
						    
					    }
					
				    }
			    }
		    }
	    };
	    
	    this.statsChart = new Chart(this.statsCanvasCtx, this.statsChartConfig);
		
		this.doLegendSwitches();
		this.changeChartType();
		this.doSortableLegends();
		this.changeChartData();
		this.doMobileFilterNav();
	
	}
	
	getChartLabels(dataPeriod: string): string[] {
		
		let chartLabels = [];
		
		switch (dataPeriod) {
			case 'month':
				chartLabels = this.settings.get('months');
				break;
			
			case 'monthDay':
				for(let i = 1; i <= this.settings.get('numDaysCurMonth'); i++) {
					chartLabels.push(i);
				}
				break;
			
			case 'weekDay':
				chartLabels = this.settings.get('days');
				break;
		}
		
		return chartLabels;
		
	}
	
	doLegendSwitches() {
		
		// Hide/show charts with legend switches.
		$('#value-chart, #products-chart').change( (evt: JQueryEventObject) => {
			
			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving
			
			const $activeCharts: JQuery = $('.chart-legend').find('input:checkbox:checked'),
			      activeDatasets: any[] = [];
			
			if ($activeCharts.length) {
				
				$activeCharts.each( (index: number, elem: Element) => {
					
					$.each(this.statsDataSets, (index: number, dataset: any) => {
						
						if (dataset.id === $(elem).attr('id')) {
							activeDatasets.push(dataset);
							return false;
						}
						
					});
					
				});
				
			}
			
			this.statsChartConfig.data.datasets = activeDatasets;
			this.statsChart.update();
			
		});
		
	}
	
	changeChartType() {
		
		$('.chart-type a').click( (evt: JQueryEventObject) => {
			
			evt.preventDefault();
			
			const $chartTypeButton: JQuery = $(evt.currentTarget),
			      chartType: string        = $chartTypeButton.data('view');
			
			if ($chartTypeButton.hasClass('active')) {
				return false;
			}
			
			$chartTypeButton.siblings('.active').removeClass('active');
			$chartTypeButton.addClass('active');
			
			if (chartType === 'bar') {
				this.statsChartConfig.type = 'bar';
			}
			else {
				
				const fillingMode: any = chartType === 'line' ? false : 'start';
				this.statsChartConfig.type = 'line';
				
				$.each(this.statsChartConfig.data.datasets, (index: number, dataset: any) => {
					this.statsChartConfig.data.datasets[index].fill = fillingMode;
				});
				
			}
			
			this.statsChart.destroy();
			this.statsChart = new Chart(this.statsCanvasCtx, this.statsChartConfig);
			
		});
		
	}
	
	doSortableLegends() {
		
		// TODO: WHEN SWITCHING POSITIONS, THE DATA IS SHOWED WRONGLY
		const $chartLegend: any = $('.chart-legend');
		
		$chartLegend
			
			.sortable({
				revert              : true,
				placeholder         : 'legend-switch legend-placeholder',
				forcePlaceholderSize: true,
				update              : (evt: any, ui: any) => {
					
					let sort: any[] = [];
					
					$chartLegend.find('input:checkbox').each( (index: number, elem: Element) => {
						sort.push($(elem).attr('id'));
					});
					
					$.each(this.statsChartConfig.data.datasets, (index: number, dataset: any) => {
						
						if (this.statsChartConfig.data.datasets[index].id !== sort[index]) {
							this.statsChartConfig.data.datasets.reverse();
						}
						
					});
					
					this.statsChart.update();
					
				}
			})
			
			.draggable({
				connectToSortable: '.chart-legend',
				helper           : 'clone',
				revert           : 'invalid'
			})
			
			.disableSelection();
		
	}
	
	changeChartData() {
		
		let $chartFilter: JQuery          = $('.chart-filter'),
		    statsAjaxFiltering: JQueryXHR = null;
		
		$chartFilter.find('select').change( (evt: JQueryEventObject) => {
			
			evt.stopPropagation(); // Avoid event bubbling to not trigger the layout saving.
			
			const $select = $(evt.currentTarget);
			
			if (statsAjaxFiltering) {
				statsAjaxFiltering.abort();
			}
			
			statsAjaxFiltering = $.ajax({
				url       : window['ajaxurl'],
				method    : 'POST',
				data      : {
					action      : 'atum_statistics_widget_chart',
					security    : this.$widgetsContainer.data('nonce'),
					chart_data  : $chartFilter.find('select.chart-data').val(),
					chart_period: $chartFilter.find('select.chart-period').val()
				},
				dataType  : 'json',
				beforeSend: () => {
					$select.siblings('.nice-select.loading').removeClass('loading');
					$select.next('.nice-select').addClass('loading');
				},
				success   : (response: any) => {
					
					if (typeof response === 'object' && response.success === true) {
						
						$.each(this.statsDataSets, (index: number, dataset: any) => {
							
							if (dataset.id === 'value-chart') {
								
								// Update the data.
								this.statsDataSets[index].data = typeof response.data.dataset !== 'undefined' && typeof response.data.dataset.value !== 'undefined' ? response.data.dataset.value : [];
								
								// Update the legend.
								if (typeof response.data.legends !== 'undefined' && typeof response.data.legends.value !== 'undefined') {
									this.statsDataSets[index].label = response.data.legends.value;
									$('#value-chart').siblings('label').text(response.data.legends.value);
								}
								
							}
							else {
								
								// Update the data.
								this.statsDataSets[index].data = typeof response.data.dataset !== 'undefined' && typeof response.data.dataset.products !== 'undefined' ? response.data.dataset.products : [];
								
								// Updated the legend.
								if (typeof response.data.legends !== 'undefined' && typeof response.data.legends.products !== 'undefined') {
									this.statsDataSets[index].label = response.data.legends.products;
									$('#products-chart').siblings('label').text(response.data.legends.products);
								}
								
							}
						});
						
						this.statsChartConfig.data.labels = this.getChartLabels(response.data.period);
						this.statsChart.update();
						$select.siblings('.nice-select.loading').removeClass('loading');
						
					}
				}
			});
			
		});
		
	}
	
	doMobileFilterNav() {
		
		// Mobile filter nav.
		this.$statisticsWidget.find('.mobile-filter-nav li').click( (evt: JQueryEventObject) => {
			
			const $navItem: JQuery = $(evt.currentTarget);
			
			if ($navItem.hasClass('active')) {
				$navItem.removeClass('active').find('.status').text('+');
				this.$statisticsWidget.find('.chart-filter .visible-filter').removeClass('visible-filter');
			}
			else {
				$navItem.siblings('.active').removeClass('active').find('.status').text('+');
				$navItem.addClass('active').find('.status').text('-');
				
				this.$statisticsWidget.find('.chart-filter .visible-filter').removeClass('visible-filter');
				this.$statisticsWidget.find( $navItem.data('show-filter') ).addClass('visible-filter');
			}
			
		});
		
	}
	
}