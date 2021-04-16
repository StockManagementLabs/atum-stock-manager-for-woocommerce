/* =======================================
   ATUM DASHBOARD
   ======================================= */

import CurrentStockValueWidget from './widgets/_current-stock-value';
import NiceScroll from '../_nice-scroll';
import Settings from '../../config/_settings';
import SalesStatsWidget from './widgets/_sales-stats';
import StatisticsWidget from './widgets/_statistics';
import StockControlWidget from './widgets/_stock-control';
import Swal, { SweetAlertResult } from 'sweetalert2';
import Tooltip from '../_tooltip';
import VideosWidget from './widgets/_videos';

export default class Dashboard {
	
	$atumDashboard: JQuery;
	$widgetsContainer: JQuery;
	$addWidgetModalContent: JQuery;
	grid: any;
	
	constructor(
		private settings: Settings,
		private tooltip: Tooltip
	) {
		
		this.$atumDashboard = $('.atum-dashboard');
		this.$widgetsContainer = this.$atumDashboard.find('.atum-widgets');
		this.$addWidgetModalContent = this.$atumDashboard.find('#tmpl-atum-modal-add-widgets');
		
		this.buildWidgetsGrid();
		this.bindDashButtons();
		this.bindWidgetControls();
		this.bindConfigControls();
		this.initWidgets();
		this.marketingBannerConfig();
		
		$(window).resize( () => this.onResize() ).resize();
	
	}
	
	onResize() {
		
		let width: number      = $(window).width(),
		    $dashCards: any = $('.dash-cards'),
		    $videoList: any = $('.video-list-wrapper');
		
		if (width <= 480) {
			
			// Apply the caousel to dashboard cards in mobiles.
			$dashCards.addClass('owl-carousel owl-theme').owlCarousel({
				items       : 1,
				margin      : 15,
				stagePadding: 30,
			});
			
			const videoCarousel: JQuery = $videoList.find('.scroll-box').addClass('owl-carousel owl-theme').owlCarousel({
				items       : 2,
				margin      : 1,
				dots        : false,
				stagePadding: 15
			});
			
			// Video Carousel nav.
			const $videoNextNav: JQuery = $videoList.find('.carousel-nav-next'),
			      $videoPrevNav: JQuery = $videoList.find('.carousel-nav-prev');
			
			$videoNextNav.click( () => videoCarousel.trigger('next.owl.carousel') );
			$videoPrevNav.click( () => videoCarousel.trigger('prev.owl.carousel') );
			
			videoCarousel.on('changed.owl.carousel', (evt: any) => {
				
				if (evt.item.index === 0) {
					$videoPrevNav.addClass('disabled');
				}
				else if (evt.item.index === evt.item.count - 2) {
					$videoNextNav.addClass('disabled');
				}
				else {
					$videoPrevNav.add($videoNextNav).removeClass('disabled');
				}
				
			});
			
		}
		else {
			
			// Remove the carousel in screens wider than mobile.
			$('.owl-carousel').removeClass('owl-carousel owl-theme').trigger('destroy.owl.carousel');
			
		}
	
	}
	
	buildWidgetsGrid() {
		
		const $gridStackElem: any = this.$widgetsContainer.find('.grid-stack');
		
		this.grid = $gridStackElem.gridstack({
			handle                : '.widget-header',
			alwaysShowResizeHandle: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
			verticalMargin        : 30,
			resizable             : {
				autoHide   : true,
				handles    : 'se, sw',
				containment: 'parent',
			},
		}).data('gridstack');
		
		// Bind events.
		$gridStackElem.on('change', () => {
			this.saveWidgetsLayout();
			NiceScroll.addScrollBars(this.$widgetsContainer);
		});
		
		// Dynamic min height for widgets.
		$gridStackElem.on('resizestart', (evt: any, ui: any) =>  {
			const minHeight = ui.element.find('.widget-body').outerHeight() + ui.element.find('.widget-header').outerHeight();
			ui.element.closest('.atum-widget').css('min-height', minHeight);
		});
		
	}
	
	bindDashButtons() {
		
		// "Add more widgets" popup.
		$('.add-dash-widget').click( () => {

			Swal.fire( {
				title            : this.settings.get( 'availableWidgets' ),
				html             : this.$addWidgetModalContent.html(),
				showConfirmButton: false,
				showCloseButton  : true,
				customClass      : {
					container: 'add-widget-popup',
				},
				width            : '620px',
				didOpen          : ( elem: any ) => {

					// Wait until the show animation is complete
					setTimeout( () => NiceScroll.addScrollBars( $( elem ) ), 300 );

				},
				willClose        : ( elem: any ) => NiceScroll.removeScrollBars( $( elem ) ),
			} );
			
		});
		
		// Add widget.
		$('body').on('click', '.add-widget-popup .add-widget', (evt: JQueryEventObject) => {
			
			const $button: JQuery          = $(evt.currentTarget),
			      widgetId: string         = $button.closest('li').data('widget'),
			      $widgetContainer: JQuery = $('.add-widget-popup');
			
			$.ajax({
				url       : window['ajaxurl'],
				method    : 'POST',
				data      : {
					token : this.$widgetsContainer.data('nonce'),
					action: 'atum_dashboard_add_widget',
					widget: widgetId
				},
				dataType  : 'json',
				beforeSend: () => $widgetContainer.addClass('overlay'),
				success   : (response: any) => {
					
					if (typeof response === 'object' && response.success === true) {
						const layout = response.data.layout;
						this.grid.addWidget($(response.data.widget), null, null, layout.min, layout.height, true);
						this.initWidgets([widgetId]);
						$button.hide().siblings('.btn-info').show();
						this.toggleModalTemplateButtons(widgetId);
						$widgetContainer.removeClass('overlay');
						this.bindWidgetControls();
					}
					
				}
			});
			
		});
		
		// Restore default layout and widgets
		const $restoreDashDefaults: JQuery = $('.restore-defaults');
		$restoreDashDefaults.click( () => {
			
			Swal.fire({
				title              : this.settings.get('areYouSure'),
				text               : this.settings.get('defaultsWillRestore'),
				icon               : 'warning',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get('continue'),
				cancelButtonText   : this.settings.get('cancel'),
				reverseButtons     : true,
				allowOutsideClick  : false,
				showLoaderOnConfirm: true,
				preConfirm: (): Promise<any> => {
					
					return new Promise( (resolve: Function, reject: Function) => {
						
						$.ajax({
							url       : window['ajaxurl'],
							method    : 'POST',
							data      : {
								token : this.$widgetsContainer.data('nonce'),
								action: 'atum_dashboard_restore_layout'
							},
							dataType  : 'json',
							beforeSend: () => {
								this.tooltip.destroyTooltips($restoreDashDefaults);
								this.$atumDashboard.addClass('overlay');
							},
							success   : () => {
								this.saveWidgetsLayout();
								resolve();
							},
							error: () => resolve()
						});
						
					});
					
				}
			})
			.then( ( result: SweetAlertResult ) => {

				if ( result.isConfirmed ) {
					location.reload();
				}

			} );
			
		});
		
	}
	
	toggleModalTemplateButtons(widgetId: string) {
		this.$addWidgetModalContent.find('[data-widget="' + widgetId + '"]').find('.add-widget').toggle().siblings('.btn-info').toggle();
	}
	
	bindWidgetControls() {
		
		// Remove widget.
		$('.atum-widget').find('.widget-close').click( (evt:JQueryEventObject) => {
			const $widget: JQuery = $(evt.currentTarget).closest('.atum-widget');
			this.grid.removeWidget($widget);
			this.toggleModalTemplateButtons( $widget.data('gs-id') );
		});
		
		// Widget settings.
		$('.atum-widget').find('.widget-settings').click( (evt: JQueryEventObject) => {
			$(evt.currentTarget).closest('.widget-wrapper').find('.widget-config').show().siblings().hide();
		});
		
	}
	
	bindConfigControls() {
		
		// Cancel config.
		$('.widget-config').find('.cancel-config').click( (evt: JQueryEventObject) => {
			$(evt.currentTarget).closest('.widget-wrapper').find('.widget-config').hide().siblings().show();
		});
		
		// Save config.
		$('.widget-config').submit( (evt: JQueryEventObject) => {
			evt.preventDefault();
			
			// TODO: IMPLEMENT WIDGET CONFIG
			//console.log($(this).serialize());
		});
		
	}
	
	/**
	 * If no widgets param is passed, all the widgets will be initialized
	 */
	initWidgets(widgets?: string[]) {
		
		// TODO: DO NOT INIT WIDGETS THAT ARE NOT BEING DISPLAYED
		const noWidgets: boolean = !widgets || !Array.isArray(widgets);
		
		// Statistics widget.
		if (noWidgets || $.inArray('atum_statistics_widget', widgets) > -1) {
			new StatisticsWidget(this.settings, this.$widgetsContainer);
		}
		
		// Sales Data widgets.
		if (
			noWidgets || $.inArray('atum_sales_widget', widgets) > -1 ||
			$.inArray('atum_lost_sales_widget', widgets) > -1 || $.inArray('atum_orders_widget', widgets) > -1 ||
			$.inArray('atum_promo_sales_widget', widgets) > -1
		) {
			new SalesStatsWidget( this.$widgetsContainer );
		}
		
		// Stock Control widget.
		if (noWidgets || $.inArray('atum_stock_control_widget', widgets) > -1) {
			new StockControlWidget(this.settings);
		}
		
		// Current Stock Value widget.
		if (noWidgets || $.inArray('atum_current_stock_value_widget', widgets) > -1) {
			new CurrentStockValueWidget(this.$widgetsContainer);
		}
		
		// Videos widget.
		if (typeof widgets === 'undefined' || $.inArray('atum_videos_widget', widgets) > -1) {
			new VideosWidget(this.$widgetsContainer);
		}
		
		// Lists' Scrollbars
		NiceScroll.addScrollBars(this.$widgetsContainer);
		
		// Nice Selects.
		this.doNiceSelects();
		
	}
	
	marketingBannerConfig() {
		
		// Hide banner.
		$('.marketing-close').on('click', ( evt: JQueryEventObject ) => {
			let transientKey: string = $(evt.currentTarget).data('transient-key');
			$('.dash-marketing-banner-container').fadeOut();
			$.ajax({
				url       : window['ajaxurl'],
				dataType  : 'json',
				method    : 'post',
				data      : {
					action       : 'atum_hide_marketing_dashboard',
					token        : this.$widgetsContainer.data('nonce'),
					transientKey : transientKey,
				},
			});

		});
		
		// Redirect to button url.
		$('.banner-button').on('click', (evt: JQueryEventObject) => {
			window.open($(evt.currentTarget).data('url'), '_blank');
		});
		
	}
	
	doNiceSelects($widget?: JQuery) {
		
		const $container: any = typeof $widget !== 'undefined' ? $widget : this.$widgetsContainer;
		$container.find('select').niceSelect();
		
	}
	
	saveWidgetsLayout() {
		
		$.ajax({
			url    : window['ajaxurl'],
			method : 'POST',
			data   : {
				action: 'atum_dashboard_save_layout',
				token : this.$widgetsContainer.data('nonce'),
				layout: this.serializeLayout(this.grid.grid.nodes)
			}
		});
		
	}
	
	serializeLayout(items?: any) {
		
		let serializedItems: any = {};
		
		if (typeof items === 'undefined') {
			return serializedItems;
		}
		
		$.each(items, (index: number, data: any) => {
			
			serializedItems[data.id] = {
				x     : data.x,
				y     : data.y,
				height: data.height,
				width : data.width
			};
			
		});
		
		return serializedItems;
		
	}
	
}