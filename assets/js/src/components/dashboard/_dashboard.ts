/*
 *┌────────────────┐
 *│                │
 *│ ATUM DASHBOARD │
 *│                │
 *└────────────────┘
 */

import CurrentStockValueWidget from './widgets/_current-stock-value';
import NiceScroll from '../_nice-scroll';
import Settings from '../../config/_settings';
import SalesStatsWidget from './widgets/_sales-stats';
import StatisticsWidget from './widgets/_statistics';
import StockControlWidget from './widgets/_stock-control';
import Swal, { SweetAlertResult } from 'sweetalert2-neutral';
import Tooltip from '../_tooltip';

// Gridstack v12: vanilla TS, no jQuery / lodash / jquery-ui dependencies.
import { GridStack, type GridStackNode } from 'gridstack';
import 'gridstack/dist/gridstack.min.css';

export default class Dashboard {
	
    $atumDashboard        : JQuery;
    $widgetsContainer     : JQuery;
    $addWidgetModalContent: JQuery;
    grid                  : GridStack;
	
    constructor(
        private settings: Settings,
        private tooltip: Tooltip,
    ) {

        this.$atumDashboard = $( '.atum-dashboard' );
        this.$widgetsContainer = this.$atumDashboard.find( '.atum-widgets' );
        this.$addWidgetModalContent = this.$atumDashboard.find( '#tmpl-atum-modal-add-widgets' );

        this.buildWidgetsGrid();
        this.bindDashButtons();
        this.bindWidgetControls();
        this.bindConfigControls();
        this.initWidgets();
        this.marketingBannerConfig();

        $( window ).on( 'resize', () => this.onResize() ).trigger( 'resize' );
	
    }
	
    onResize() {

        const width: number   = $( window ).width(),
              $dashCards: any = $( '.dash-cards' );

        if ( width <= 480 ) {

            // Apply the carousel to dashboard cards in mobiles.
            $dashCards.addClass( 'owl-carousel owl-theme' ).owlCarousel( {
                items       : 1,
                margin      : 15,
                stagePadding: 30,
            } );
			
        }
        else {

            // Remove the carousel in screens wider than mobile.
            $( '.owl-carousel' ).removeClass( 'owl-carousel owl-theme' ).trigger( 'destroy.owl.carousel' );

        }
	
    }
	
    buildWidgetsGrid() {

        const gridEl: HTMLElement | undefined = this.$widgetsContainer.find( '.grid-stack' ).get( 0 ) as HTMLElement | undefined;

        if ( !gridEl ) {
            return;
        }

        const isMobile: boolean = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent );

        this.grid = GridStack.init( {
            handle                : '.widget-header',
            alwaysShowResizeHandle: isMobile,
            /*
             * Reproduce the legacy gridstack v1 metrics so existing user
             * layouts (saved as integer rows) render at the SAME pixel height:
             *   v1: cellHeight 60 + verticalMargin 30 → row pitch 90px,
             *       widget height = 60h + 30(h-1) = 90h - 30, gaps of 30px.
             *   v12: height = h × cellHeight, gap = 2 × margin.
             *   → cellHeight 90 + margin 15 gives height = 90h - 30 and 30px
             *     gaps — identical to v1 for every h.
             */
            cellHeight            : 90,
            margin                : 15,
            resizable             : {
                autoHide: true,
                handles : 'se, sw',
            },
        }, gridEl );

        // Layout change → persist + refresh scrollbars.
        this.grid.on( 'change', () => {
            this.saveWidgetsLayout();
            NiceScroll.addScrollBars( this.$widgetsContainer );
        } );

        // Dynamic min height while resizing.
        this.grid.on( 'resizestart', ( _event: Event, el: HTMLElement ) => {
            const $el: JQuery = $( el );
            const minHeight: number = ( $el.find( '.widget-body' ).outerHeight() || 0 ) + ( $el.find( '.widget-header' ).outerHeight() || 0 );

            $el.css( 'min-height', minHeight );
        } );

    }
	
    bindDashButtons() {

        // "Add more widgets" popup.
        $( '.add-dash-widget' ).on( 'click', () => {

            Swal.fire( {
                title            : this.settings.get( 'availableWidgets' ),
                html             : this.$addWidgetModalContent.html(),
                showConfirmButton: false,
                showCloseButton  : true,
                customClass      : {
                    container: 'add-widget-popup',
                },
                width  : '620px',
                didOpen: ( elem: any ) => {

                    // Wait until the show animation is complete
                    setTimeout( () => NiceScroll.addScrollBars( $( elem ) ), 300 );

                },
                willClose: ( elem: any ) => NiceScroll.removeScrollBars( $( elem ) ),
            } );

        } );

        // Add widget.
        $( 'body' ).on( 'click', '.add-widget-popup .add-widget', ( evt: JQueryEventObject ) => {

            const $button: JQuery          = $( evt.currentTarget ),
                  widgetId: string         = $button.closest( 'li' ).data( 'widget' ),
                  $widgetContainer: JQuery = $( '.add-widget-popup' );

            $.ajax( {
                url   : window[ 'ajaxurl' ],
                method: 'POST',
                data  : {
                    action  : 'atum_dashboard_add_widget',
                    security: this.$widgetsContainer.data( 'nonce' ),
                    widget  : widgetId,
                },
                dataType  : 'json',
                beforeSend: () => $widgetContainer.addClass( 'overlay' ),
                success   : ( response: any ) => {

                    if ( typeof response === 'object' && response.success === true ) {

                        /*
                         * The server returns the full `<div class="atum-widget grid-stack-item">…</div>`
                         * Markup (with `data-gs-*` attributes for width/height/etc.). We append it to
                         * The grid container and let `makeWidget` adopt it; gridstack reads the
                         * Dimensions from the data-attributes — no need to pass them again here.
                         */
                        const widgetEl: HTMLElement | null = ( $( response.data.widget ).get( 0 ) as HTMLElement | undefined ) ?? null;
                        const gridEl: HTMLElement | undefined = this.$widgetsContainer.find( '.grid-stack' ).get( 0 ) as HTMLElement | undefined;

                        if ( widgetEl && gridEl ) {
                            gridEl.appendChild( widgetEl );
                            this.grid.makeWidget( widgetEl, { autoPosition: true } );
                        }

                        this.initWidgets( [ widgetId ] );
                        $button.hide().siblings( '.btn-info' ).show();
                        this.toggleModalTemplateButtons( widgetId );
                        $widgetContainer.removeClass( 'overlay' );
                        this.bindWidgetControls();
                    }

                },
            } );

        } );

        // Restore default layout and widgets
        const $restoreDashDefaults: JQuery = $( '.restore-defaults' );

        $restoreDashDefaults.on( 'click', () => {

            Swal.fire( {
                title              : this.settings.get( 'areYouSure' ),
                text               : this.settings.get( 'defaultsWillRestore' ),
                icon               : 'warning',
                showCancelButton   : true,
                confirmButtonText  : this.settings.get( 'continue' ),
                cancelButtonText   : this.settings.get( 'cancel' ),
                reverseButtons     : true,
                allowOutsideClick  : false,
                showLoaderOnConfirm: true,
                preConfirm         : (): Promise<any> => {

                    return new Promise( ( resolve: Function, reject: Function ) => {

                        $.ajax( {
                            url   : window[ 'ajaxurl' ],
                            method: 'POST',
                            data  : {
                                action  : 'atum_dashboard_restore_layout',
                                security: this.$widgetsContainer.data( 'nonce' ),
                            },
                            dataType  : 'json',
                            beforeSend: () => {
                                this.tooltip.destroyTooltips( $restoreDashDefaults );
                                this.$atumDashboard.addClass( 'overlay' );
                            },
                            success: () => {
                                this.saveWidgetsLayout();
                                resolve();
                            },
                            error: () => resolve(),
                        } );

                    } );

                },
            } )
                .then( ( result: SweetAlertResult ) => {

                    if ( result.isConfirmed ) {
                        location.reload();
                    }

                } );

        } );

    }

    toggleModalTemplateButtons( widgetId: string ) {
        this.$addWidgetModalContent.find( '[data-widget="' + widgetId + '"]' ).find( '.add-widget' ).toggle().siblings( '.btn-info' ).toggle();
    }
	
    bindWidgetControls() {

        // Remove widget.
        $( '.atum-widget' ).find( '.widget-close' ).on( 'click', ( evt: JQueryEventObject ) => {
            const $widget: JQuery = $( evt.currentTarget ).closest( '.atum-widget' );
            const widgetEl: HTMLElement | null = ( $widget.get( 0 ) as HTMLElement | undefined ) ?? null;

            if ( widgetEl ) {
                this.grid.removeWidget( widgetEl );
            }

            this.toggleModalTemplateButtons( $widget.data( 'gs-id' ) );
        } );

        // Widget settings.
        $( '.atum-widget' ).find( '.widget-settings' ).on( 'click', ( evt: JQueryEventObject ) => {
            $( evt.currentTarget ).closest( '.widget-wrapper' ).find( '.widget-config' ).show().siblings().hide();
        } );
		
    }
	
    bindConfigControls() {

        // Cancel config.
        $( '.widget-config' ).find( '.cancel-config' ).on( 'click', ( evt: JQueryEventObject ) => {
            $( evt.currentTarget ).closest( '.widget-wrapper' ).find( '.widget-config' ).hide().siblings().show();
        } );

        // Save config.
        $( '.widget-config' ).on( 'submit', ( evt: JQueryEventObject ) => {
            evt.preventDefault();

            /*
             * TODO: IMPLEMENT WIDGET CONFIG
             * console.log($(this).serialize());
             */
        } );
		
    }
	
    /**
     * If no widgets param is passed, all the widgets will be initialized
     */
    initWidgets( widgets?: string[] ) {

        // TODO: DO NOT INIT WIDGETS THAT ARE NOT BEING DISPLAYED
        const noWidgets: boolean = !widgets || !Array.isArray( widgets );

        // Statistics widget.
        if ( noWidgets || $.inArray( 'atum_statistics_widget', widgets ) > -1 ) {
            new StatisticsWidget( this.settings, this.$widgetsContainer );
        }

        // Sales Data widgets.
        if (
            noWidgets || $.inArray( 'atum_sales_widget', widgets ) > -1
            || $.inArray( 'atum_lost_sales_widget', widgets ) > -1 || $.inArray( 'atum_orders_widget', widgets ) > -1
            || $.inArray( 'atum_promo_sales_widget', widgets ) > -1
        ) {
            new SalesStatsWidget( this.$widgetsContainer );
        }

        // Stock Control widget.
        if ( noWidgets || $.inArray( 'atum_stock_control_widget', widgets ) > -1 ) {
            new StockControlWidget( this.settings );
        }

        // Current Stock Value widget.
        if ( noWidgets || $.inArray( 'atum_current_stock_value_widget', widgets ) > -1 ) {
            new CurrentStockValueWidget( this.$widgetsContainer );
        }

        // Lists' Scrollbars
        NiceScroll.addScrollBars( this.$widgetsContainer );

        // Nice Selects.
        this.doNiceSelects();

    }

    marketingBannerConfig() {

        // Hide banner.
        $( '.marketing-close' ).on( 'click', ( evt: JQueryEventObject ) => {

            const transientKey: string = $( evt.currentTarget ).data( 'transient-key' );

            $( '.dash-marketing-banner-container' ).fadeOut();

            $.ajax( {
                url     : window[ 'ajaxurl' ],
                dataType: 'json',
                method  : 'post',
                data    : {
                    action      : 'atum_hide_marketing_dashboard',
                    security    : this.$widgetsContainer.data( 'nonce' ),
                    transientKey: transientKey,
                },
            } );

        } );

        // Redirect to button url.
        $( '.banner-button' ).on( 'click', ( evt: JQueryEventObject ) => {
            window.open( $( evt.currentTarget ).data( 'url' ), '_blank' );
        } );

    }

    doNiceSelects( $widget?: JQuery ) {

        const $container: any = typeof $widget !== 'undefined' ? $widget : this.$widgetsContainer;

        $container.find( 'select' ).niceSelect();

    }

    saveWidgetsLayout() {

        $.ajax( {
            url   : window[ 'ajaxurl' ],
            method: 'POST',
            data  : {
                action  : 'atum_dashboard_save_layout',
                security: this.$widgetsContainer.data( 'nonce' ),
                /*
                 * `save(false)` returns the layout without inlining each
                 * Widget's HTML content — just position/size descriptors.
                 */
                layout  : this.serializeLayout( this.grid.save( false ) as GridStackNode[] ),
            },
        } );

    }

    serializeLayout( items?: GridStackNode[] ) {

        const serializedItems: Record<string, { x: number; y: number; w: number; h: number }> = {};

        if ( !Array.isArray( items ) ) {
            return serializedItems;
        }

        items.forEach( ( node: GridStackNode ) => {

            if ( !node.id ) {
                return;
            }

            serializedItems[ node.id ] = {
                x: node.x ?? 0,
                y: node.y ?? 0,
                w: node.w ?? 1,
                h: node.h ?? 1,
            };

        } );

        return serializedItems;

    }
	
}
