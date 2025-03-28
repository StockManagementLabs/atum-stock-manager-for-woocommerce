/*
 * =======================================
 * ATUM ORDERS
 * =======================================
 */

import AddItemsPopup from './_add-items-popup';
import AtumOrderItems from './_order-items';
import Blocker from '../_blocker';
import DateTimePicker from '../_date-time-picker';
import OrdersBulkActions from './_bulk-actions';
import Tooltip from '../_tooltip';
import Settings from '../../config/_settings';
import StupidTable from '../_stupid-table';
import Swal from 'sweetalert2-neutral';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';
import EnhancedSelect from '../_enhanced-select';

export default class AtumOrders {

    $container        : JQuery;
    $itemsBlocker     : JQuery;
    $supplierDropdown : JQuery;
    $multipleSuppliers: JQuery;
    areItemsSelectable: boolean;
    isEditable        : string;
    wpHooks           : WPHooks = window[ 'wp' ][ 'hooks' ]; // WP hooks.

    constructor(
        private settings: Settings,
        private tooltip: Tooltip,
        private dateTimePicker: DateTimePicker,
        private enhancedSelect: EnhancedSelect,
    ) {

        this.$container = $( '#atum_order_items' );
        this.$itemsBlocker = this.$container.find( '.items-blocker' );
        this.$supplierDropdown = $( '.dropdown_supplier' );
        this.$multipleSuppliers = $( '#multiple_suppliers' );
        this.areItemsSelectable = this.settings.get( 'enableSelectItems' );
        this.isEditable = $( '#atum_order_is_editable' ).val();

        StupidTable.init( $( 'table.atum_order_items' ) );
        new AtumOrderItems( this.settings, this.$container, this );
        new OrdersBulkActions( this.settings, this.$container, this );
        new AddItemsPopup( this.settings, this.$container, this, this.tooltip );
        this.dateTimePicker.addDateTimePickers( $( '.atum-datepicker' ), { minDate: false } );

        this.bindEvents();

        if ( undefined !== this.settings.get( 'wpmlActive' ) && '1' === this.settings.get( 'wpmlActive' ) ) {
            this.initWpml();
        }

        // Add this component to the global scope so can be accessed by other add-ons.
        if ( !window.hasOwnProperty( 'atum' ) ) {
            window[ 'atum' ] = {};
        }

        window[ 'atum' ][ 'AtumOrders' ] = this;
    }
	
    bindEvents() {
		
        // Bind items' events.
        this.$container

            // Qty.
            .on( 'change', 'input.quantity', ( evt: JQueryEventObject ) => this.quantityChanged( evt ) )

            // Subtotal/total.
            .on( 'keyup change', '.split-input :input', ( evt: JQueryEventObject ) => {

                const $input: JQuery    = $( evt.currentTarget ),
                      $subtotal: JQuery = $input.parent().prev().find( ':input' );

                if ( $subtotal && ( $subtotal.val() === '' || $subtotal.is( '.match-total' ) ) ) {
                    $subtotal.val( $input.val() ).addClass( 'match-total' );
                }

            } )

            .on( 'keyup', '.split-input :input', ( evt: JQueryEventObject ) => {
                $( evt.currentTarget ).removeClass( 'match-total' );
            } );

        if ( this.areItemsSelectable ) {

            this.$container

                .on( 'click', 'tr.item, tr.fee, tr.shipping', ( evt: JQueryEventObject ) => this.selectRow( evt ) )
                .on( 'click', 'tr.item :input, tr.fee :input, tr.shipping :input, tr.item a, tr.fee a, tr.shipping a', ( evt: JQueryEventObject ) => evt.stopPropagation() );

        }
		
        // Trigger ATUM order type dependent fields.
        $( '#atum_order_type' ).on( 'change', ( evt: JQueryEventObject ) => this.toggleExtraFields( evt ) ).trigger( 'change' );

        // Hide/show the blocker section on supplier dropdown changes.
        this.$supplierDropdown.on( 'change', () => this.savePurchaseOrderSupplier() );

        // Trigger multiple suppliers' dependent fields.
        this.$multipleSuppliers.on( 'change', () => this.toggleSupplierField() );

        // Ask for importing the order items after linking an order.
        $( '#wc_order' ).on( 'change', ( evt: JQueryEventObject ) => this.importOrderItems( $( evt.currentTarget ), 'IL' ) );

        // Change button page-title-action position.
        $( '.wp-heading-inline' ).append( $( '.page-title-action' ).show() );
		
        // Footer position.
        $( window ).on( 'load', () => {

            if ( $( '.footer-box' ).hasClass( 'no-style' ) ) {
                $( '#wpfooter' ).css( 'position', 'relative' ).show();
                $( '#wpcontent' ).css( 'min-height', '95vh' );
            }

        } );
		
    }

    /**
     * Add WPML behaviour
     */
    initWpml() {

        /**
         * Adds the flag image if set.
         *
         * @param state
         * @param {JQuery} $element
         *
         * @returns {string}
         */
        const $select: JQuery = $( '#wpml_lang' );
        const addFlag: Function = ( state: any, $element: JQuery ) => {

            if ( !state.id ) {
                return state.text;
            }

            return this.genLangOptionsContent( $( state.element ) );

        };

        if ( $select.length ) {

            this.enhancedSelect.doSelect2( $select, {
                templateResult: addFlag,
            } );

            $select.on( 'select2:select', ( evt: Event ) => {

                $( '#select2-wpml_lang-container' ).html( this.genLangOptionsContent( $select.find( ':selected' ) ).html() );

            } );

            $( '#select2-wpml_lang-container' ).html( this.genLangOptionsContent( $select.find( ':selected' ) ).html() );

        }

    }

    /**
     * Generate options and selection content for the WPML lang select2
     * @param {JQuery} $option
     * @returns {JQuery}
     */
    genLangOptionsContent( $option:JQuery ) {

        const flag : any = $option.data( 'flag' ),
              $state       = $( `<span class="${ flag.code }"><img src="${ flag.flag_url } " alt="${ flag.flag_alt }" class="${ $option.data( 'flagClasses' ) }"/> <span>${ $option.text() }</span></span>` ),
              $img: JQuery = $state.find( 'img' );

        if ( flag.flag_width ) {
            $img.width( flag.flag_width );
        }

        if ( flag.flag_height ) {
            $img.height( flag.flag_height );
        }

        return $state;
    }

    /**
     * Save Supplier on change field
     */
    savePurchaseOrderSupplier() {

        const $searcher: JQuery   = $( '#add_item_id' ),
              atumOrderId: number = this.settings.get( 'postId' ),
              supplierId: number  = this.$supplierDropdown.val();

        this.toggleItemsBlocker( !( !supplierId && !this.$multipleSuppliers.is( ':checked' ) ) );

        $.ajax( {
            url : window[ 'ajaxurl' ],
            data: {
                action       : 'atum_save_po_supplier',
                security     : this.settings.get( 'atumOrderItemNonce' ),
                atum_order_id: atumOrderId,
                supplier     : supplierId,
            },
            dataType: 'json',
            method  : 'POST',
            success : ( response: any ) => {

                if ( supplierId && !this.$multipleSuppliers.is( ':checked' ) ) {
                    this.toggleItemsBlocker( 0 !== response.data.supplier );
                }

                if ( !$searcher.data( 'limit' ) ) {
                    $searcher.data( 'limit', atumOrderId );
                }

            },
        } );

    }

    /**
     * Save the multiple suppliers state
     *
     * @param {string} val
     */
    saveMultipleSuppliers( val: string ) {

        $.ajax( {
            url : window[ 'ajaxurl' ],
            data: {
                action       : 'atum_save_po_multiple_supplier',
                security     : this.settings.get( 'atumOrderItemNonce' ),
                atum_order_id: this.settings.get( 'postId' ),
                multiple     : val,
            },
            dataType: 'json',
            method  : 'POST',
        } );

    }

    /**
     * When the qty is changed, increase or decrease costs
     *
     * @param {JQueryEventObject} evt
     */
    quantityChanged( evt: JQueryEventObject ) {

        const $input: JQuery        = $( evt.currentTarget ),
              $row: JQuery          = $input.closest( 'tr.item' ),
              qty: number           = $input.val(),
              oQty: number          = $input.data( 'qty' ),
              $lineTotal: JQuery    = $row.find( 'input.line_total' ),
              $lineSubtotal: JQuery = $row.find( 'input.line_subtotal' ),
              decimalSep: string    = this.settings.get( 'priceDecimalSep' ),
              precision: number     = this.settings.get( 'roundingPrecision' );
		
        // Totals
        const unitTotal: number = Utils.divideDecimals( Utils.unformat( $lineTotal.data( 'total' ), decimalSep ), oQty );

        $lineTotal.val(
            Utils.formatNumber( Utils.multiplyDecimals( unitTotal, qty ), precision, '', decimalSep ),
        );

        const unitSubtotal: number = Utils.divideDecimals( Utils.unformat( $lineSubtotal.data( 'subtotal' ), decimalSep ), oQty );

        $lineSubtotal.val(
            Utils.formatNumber( Utils.multiplyDecimals( unitSubtotal, qty ), precision, '', decimalSep ),
        );
		
        // Taxes
        $row.find( 'input.line_tax' ).each( ( i: number, elem: Element ) => {

            const $lineTotalTax: JQuery    = $( elem ),
                  taxId: string            = $lineTotalTax.data( 'tax_id' ),
                  unitTotalTax: number     = Utils.divideDecimals( Utils.unformat( $lineTotalTax.data( 'total_tax' ), decimalSep ), oQty ),
                  $lineSubtotalTax: JQuery = $row.find( `input.line_subtotal_tax[data-tax_id="${ taxId }"]` ),
                  unitSubtotalTax: number  = Utils.divideDecimals( Utils.unformat( $lineSubtotalTax.data( 'subtotal_tax' ), decimalSep ), oQty );

            if ( 0 < unitTotalTax ) {
                $lineTotalTax.val(
                    Utils.formatNumber( Utils.multiplyDecimals( unitTotalTax, qty ), precision, '', decimalSep ),
                );
            }

            if ( 0 < unitSubtotalTax ) {
                $lineSubtotalTax.val(
                    Utils.formatNumber( Utils.multiplyDecimals( unitSubtotalTax, qty ), precision, '', decimalSep ),
                );
            }
			
        } );
		
        $input.trigger( 'quantity_changed' );
		
    }

    /**
     * Load the items table from backend
     *
     * @param {any}    data
     * @param {string} dataType
     *
     * @return {Promise<void>}
     */
    loadItemsTable( data: any, dataType?: string ): Promise<void> {

        return new Promise( ( resolve: Function ) => {

            Blocker.block( this.$container );
            dataType = dataType || 'html';

            $.ajax( {
                url     : window[ 'ajaxurl' ],
                data    : data,
                dataType: dataType,
                method  : 'POST',
                success : ( response: any ) => {

                    if ( ( typeof response === 'object' && response.success === true ) || typeof response !== 'object' ) {

                        const itemsTable: string = dataType === 'html' ? response : response.data.html;

                        this.$container.find( '.inside' ).empty().append( itemsTable );
                        this.tooltip.addTooltips();
                        StupidTable.init( $( 'table.atum_order_items' ) );

                    }
                    else if ( typeof response === 'object' && response.success === false ) {
                        this.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
                    }

                    Blocker.unblock( this.$container );

                    this.wpHooks.doAction( 'atum_orders_afterLoadItemsTable' );

                    if ( 'atum_order_import_items' === data.action ) {
                        this.wpHooks.doAction( 'atum_orders_afterImportItems' );
                    }

                    resolve();

                },
                error: () => resolve(),
            } );

        } );

    }

    /**
     * Reload the order items
     */
    reloadItems(): Promise<void> {

        return this.loadItemsTable( {
            atum_order_id: this.settings.get( 'postId' ),
            action       : 'atum_order_load_items',
            security     : this.settings.get( 'atumOrderItemNonce' ),
        }, 'html' );

    }

    /**
     * Show an alert with a message
     *
     * @param {"warning" | "error" | "success" | "info" | "question"} type
     * @param {string} title
     * @param {string} message
     */
    showAlert( type: 'warning' | 'error' | 'success' | 'info' | 'question', title: string, message: string ) {

        Swal.fire( {
            title            : title,
            text             : message,
            icon             : type,
            confirmButtonText: this.settings.get( 'ok' ),
        } );

    }

    /**
     * Selects an item row when clicking it
     *
     * @param {JQueryEventObject} evt
     */
    selectRow( evt: JQueryEventObject ) {

        const $row: JQuery   = $( evt.currentTarget ).is( 'tr' ) ? $( evt.currentTarget ) : $( evt.currentTarget ).closest( 'tr' ),
              $table: JQuery = $row.closest( 'table' );

        if ( $row.is( '.selected' ) ) {
            $row.removeClass( 'selected' );
        }
        else {
            $row.addClass( 'selected' );
        }

        const $rows: JQuery                = $table.find( 'tr.selected' ),
              $editControlsWrapper: JQuery = $( 'div.atum-order-item-bulk-edit' );

        if ( $rows.length ) {

            // The Increase/Decrease stock buttons must be only visible when at least one product is selected
            const $stockChangeButtons: JQuery = $( '.bulk-decrease-stock, .bulk-increase-stock' );

            if ( $( 'table.atum_order_items' ).find( 'tr.item.selected' ).length ) {
                $stockChangeButtons.show();
            }
            else {
                $stockChangeButtons.hide();
            }

            $editControlsWrapper.slideDown();

        }
        else {
            $editControlsWrapper.slideUp();
        }

    }

    /**
     * Show/Hide extra fields on the order data meta box
     *
     * @param {JQueryEventObject} evt
     */
    toggleExtraFields( evt: JQueryEventObject ) {

        const $atumOrderType: JQuery = $( evt.currentTarget ),
              typeValue: string      = $atumOrderType.val();

        $( '[data-dependency]' ).each( ( index: number, elem: Element ) => {

            const $elem: JQuery        = $( elem ),
                  dependency: string[] = $elem.data( 'dependency' ).split( ':' );

            if ( dependency[ 0 ] === $atumOrderType.attr( 'id' ) ) {

                if ( dependency[ 1 ] === typeValue ) {
                    $elem.fadeIn();
                }
                else if ( $elem.is( ':visible' ) ) {
                    $elem.hide();
                }

            }

        } );

    }

    /**
     * Show/Hide the supplier field on POs
     */
    toggleSupplierField() {

        const $body: JQuery            = $( 'body' ),
              $dropdownWrapper: JQuery = this.$supplierDropdown.parent();

        if ( this.$multipleSuppliers.is( ':checked' ) ) {
            this.saveMultipleSuppliers( 'yes' );
            this.$supplierDropdown.val( '' ).trigger( 'change' );
            $body.addClass( 'allow-multiple-suppliers' );
            this.toggleItemsBlocker();
            $dropdownWrapper.slideUp();
        }
        else {
            this.saveMultipleSuppliers( 'no' );
            $body.removeClass( 'allow-multiple-suppliers' );
            this.toggleItemsBlocker( this.$supplierDropdown.val() !== null ); // Only block the items if there is no supplier selected.
            $dropdownWrapper.slideDown();
        }

    }

    /**
     * Checks whether a PO ID exists in URL's query string
     *
     * @return {boolean}
     * TODO: THIS IS NOT BEING USED. WHY IS STILL HERE??
     */
    checkPoIdExistsInQuerystring(): boolean {

        const queryString: string = window.location.search.substring( 1 );
        let check: boolean = false;

        const queries = queryString.split( '&' );

        queries.forEach( ( indexQuery: string ) => {
            const indexPair = indexQuery.split( '=' );

            const queryKey: string = decodeURIComponent( indexPair[ 0 ] );
            const queryValue: string = decodeURIComponent( indexPair.length > 1 ? indexPair[ 1 ] : '' );

            if ( 'post' === queryKey && queryValue.length > 0 ) {
                check = true;
            }
        } );

        return check;

    }

    /**
     * Show/Hide the items blocker on POs
     *
     * @param {boolean} on
     */
    toggleItemsBlocker( on: boolean = true ) {

        // If (!this.$itemsBlocker.length || !this.checkPoIdExistsInQuerystring() ) {
        if ( !this.$itemsBlocker.length ) {
            return;
        }

        if ( on === true ) {
            this.$itemsBlocker.addClass( 'unblocked' );
        }
        else {
            this.$itemsBlocker.removeClass( 'unblocked' );
        }

    }

    /**
     * Import items from related WC order
     *
     * @param {JQuery} $wcOrder
     * @param {string} orderType
     */
    importOrderItems( $wcOrder: JQuery, orderType: string ) {

        const orderId: number  = $wcOrder.val();

        if ( !orderId || this.isEditable == 'false' ) {
            return;
        }

        Swal.fire( {
            text               : this.settings.get( `importOrderItems${ orderType }` ),
            icon               : 'question',
            showCancelButton   : true,
            confirmButtonText  : this.settings.get( 'yes' ),
            cancelButtonText   : this.settings.get( 'no' ),
            reverseButtons     : true,
            allowOutsideClick  : false,
            showLoaderOnConfirm: true,
            preConfirm         : (): Promise<void> => {

                return this.loadItemsTable( {
                    action       : 'atum_order_import_items',
                    wc_order_id  : orderId,
                    atum_order_id: this.settings.get( 'postId' ),
                    supplier_id  : $( '#supplier' ).val(),
                    security     : this.settings.get( 'importOrderItemsNonce' ),
                }, 'json' );

            },
        } );

    }
	
}
