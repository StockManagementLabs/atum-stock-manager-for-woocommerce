/* =======================================
   DASHBOARD STOCK CONTROL WIDGET
   ======================================= */

import Chart from 'chart.js/dist/Chart.bundle.min';
import Settings from '../../../config/_settings';

export default class StockControlWidget {
	
	$stockControlWidget: JQuery;
	stockControlChart: any;
	
	constructor(
		private settings: Settings
	) {
		
		this.$stockControlWidget = $('.stock-chart');
		
		if (this.$stockControlWidget.length) {
			this.initChart();
		}
		
	}
	
	initChart() {
        let style = getComputedStyle(document.body);

		const $pieCanvas: JQuery = this.$stockControlWidget.find('canvas'),
		      chartOptions: any  = {
			      type   : 'doughnut',
			      data   : {
				      datasets: [{
					      data           : [
						      $pieCanvas.data('lowstock'),
						      $pieCanvas.data('outstock'),
						      $pieCanvas.data('instock'),
						      $pieCanvas.data('unmanaged'),
					      ],
					      backgroundColor: [
                              style.getPropertyValue('--orange'),
                              style.getPropertyValue('--danger'),
                              style.getPropertyValue('--green'),
                              style.getPropertyValue('--blue'),
					      ],
                          hoverBackgroundColor: [
                              style.getPropertyValue('--orange'),
                              style.getPropertyValue('--danger'),
                              style.getPropertyValue('--green'),
                              style.getPropertyValue('--blue'),
                          ],
                          hoverBorderColor: style.getPropertyValue('--main-text-expanded'),
                          borderColor: style.getPropertyValue('--main-text-expanded')
				      }],
				      labels  : [
					      this.settings.get('lowStockLabel'),
					      this.settings.get('outStockLabel'),
					      this.settings.get('inStockLabel'),
					      this.settings.get('unmanagedLabel'),
				      ],
			      },
			      options: {
				      responsive         : true,
				      legend             : {
					      display: false,
				      },
				      maintainAspectRatio: false,
				      animation          : {
					      animateScale : true,
					      animateRotate: true,
				      },
				      cutoutPercentage   : 25,
				      tooltips           : {
					      enabled: false,
					      custom : (tooltip: any) => {
						
						      // Tooltip Element.
						      const tooltipEl: HTMLElement = (<HTMLElement>$('.stock-chart-tooltip').get(0));
						
						      // Hide if no tooltip.
						      if (tooltip.opacity === 0) {
							      tooltipEl.style.opacity = '0';
							      return;
						      }
						
						      // Set caret Position.
						      tooltipEl.classList.remove('above', 'below', 'no-transform');
						
						      if (tooltip.yAlign) {
							      tooltipEl.classList.add(tooltip.yAlign);
						      }
						      else {
							      tooltipEl.classList.add('no-transform');
						      }
						
						      // Set Text.
						      if (tooltip.body) {
							
							      let titleLines: string[] = tooltip.title || [],
							          bodyLines: string[]  = tooltip.body.map((bodyItem: any) => {
								          return bodyItem.lines;
							          }),
							          innerHtml: string    = '<thead>';
							
							      titleLines.forEach((title: string) => {
								      innerHtml += '<tr><th>' + title + '</th></tr>';
							      });
							
							      innerHtml += '</thead><tbody>';
							
							      bodyLines.forEach((body: string, i: number) => {
								
								      const colors: any   = tooltip.labelColors[i],
								            style: string = `background:${colors.backgroundColor}; border-color:${colors.borderColor}; border-width: 2px`,
								            span: string  = `<span class="stock-chart-tooltip-key" style="${style}"></span>`;
								
								      innerHtml += `<tr><td>${span + body}</td></tr>`;
								
							      });
							
							      innerHtml += '</tbody>';
							
							      tooltipEl.querySelector('table').innerHTML = innerHtml;
							
						      }
						
						      const positionY: number = this.stockControlChart.canvas.offsetTop,
						            positionX: number = this.stockControlChart.canvas.offsetLeft;
						
						      // Display, position, and set styles for font.
						      tooltipEl.style.opacity = '1';
						      tooltipEl.style.left = positionX + tooltip.caretX + 'px';
						      tooltipEl.style.top = positionY + tooltip.caretY + 'px';
						      tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
						
					      },
				      },
			      },
		      };
		
		this.stockControlChart = new Chart( (<HTMLCanvasElement>$pieCanvas.get(0)).getContext('2d'), chartOptions);
		
	}
	
}