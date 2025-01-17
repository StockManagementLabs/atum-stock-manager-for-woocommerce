/*
 * =======================================
 * ADD ITEMS POPUP FOR ATUM ORDERS
 * =======================================
 */

import AtumOrders from './_atum-orders';
import Blocker from '../_blocker';
import Settings from '../../config/_settings';
import Tooltip from '../_tooltip';
import WPHooks from '../../interfaces/wp.hooks';

export default class AddItemsPopup {

    wpHooks: WPHooks = window[ 'wp' ][ 'hooks' ]; // WP hooks.

    addedItems: string[] = [];
	
    constructor(
        private settings: Settings,
        private $container: JQuery,
        private atumOrders: AtumOrders,
        private tooltip: Tooltip,
    ) {
		
        $( 'body' )
            .on( 'wc_backbone_modal_loaded', ( evt: Event, target: string ) => this.init( evt, target ) )
            .on( 'wc_backbone_modal_response', ( evt: Event, target: string, data: any ) => this.response( evt, target, data ) );
		
    }

    /**
     * Initialize the popup
     *
     * @param {Event}  evt
     * @param {string} target
     */
    init( evt: Event, target: string ) {

        const $select: JQuery = $( '#wc-backbone-modal-dialog [name="add_atum_order_items[]"]' );

        // Add the items to the excluded data so they cannot be added twice.
        if ( this.addedItems.length && $select.length ) {

            let excludedItems: string[] = [];
            const excluded: string = $select.data( 'exclude' );

            if ( excluded ) {
                excludedItems = excluded.split( ',' );
            }

            $select.data( 'exclude', [ ...excludedItems, ...this.addedItems ].join( ',' ) );

        }
		
        if ( 'atum-modal-add-products' === target ) {
            $( 'body' ).trigger( 'wc-enhanced-select-init' );
        }
		
    }

    /**
     * Response to the popup
     *
     * @param {Event}  evt
     * @param {string} target
     * @param {any}    data
     */
    response( evt: Event, target: string, data: any ) {
		
        if ( 'atum-modal-add-tax' === target ) {
			
            let rateId        = data.add_atum_order_tax,
                manualRateId = '';
			
            if ( data.manual_tax_rate_id ) {
                manualRateId = data.manual_tax_rate_id;
            }
			
            this.addTax( rateId, manualRateId );
			
        }
		
        if ( 'atum-modal-add-products' === target ) {
            this.addItem( data.add_atum_order_items );
        }

    }

    /**
     * Add items to the order
     *
     * @param {string | string[]} itemIds
     */
    addItem( itemIds: string | string[] ) {

        if ( itemIds ) {

            Blocker.block( this.$container );

            const data: any = {
                action       : 'atum_order_add_item',
                item_to_add  : itemIds,
                atum_order_id: this.settings.get( 'postId' ),
                security     : this.settings.get( 'atumOrderItemNonce' ),
            };

            $.post( window[ 'ajaxurl' ], data, ( response: any ) => {

                if ( response.success ) {
                    $( '#atum_order_line_items' ).append( response.data.html );
                    this.wpHooks.doAction( 'atum_orderItems_addItem_added', itemIds, this.settings.get( 'postId' ) );
                }
                else {
                    this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), response.data.error );
                }

                this.tooltip.addTooltips();
                Blocker.unblock( this.$container );

                if ( Array.isArray( itemIds ) ) {
                    this.addedItems = [ ...this.addedItems, ...itemIds  ];
                }
                else {
                    this.addedItems.push( itemIds );
                }

            }, 'json' );
        }
		
    }

    /**
     * Add tax to the order
     *
     * @param {string} rateId
     * @param {string} manualRateId
     */
    addTax( rateId: string, manualRateId: string ) {
		
        if ( manualRateId ) {
            rateId = manualRateId;
        }
		
        if ( !rateId ) {
            return false;
        }
		
        const rates: any[] = $( '.atum-order-tax-id' ).map( ( index: number, elem: Element ) => {
            return $( elem ).val();
        } ).get();

        // Test if already exists
        if ( -1 === $.inArray( rateId, rates ) ) {

            this.atumOrders.loadItemsTable( {
                action       : 'atum_order_add_tax',
                rate_id      : rateId,
                atum_order_id: this.settings.get( 'postId' ),
                security     : this.settings.get( 'atumOrderItemNonce' ),
            }, 'json' );

        }
        else {
            this.atumOrders.showAlert( 'error', this.settings.get( 'error' ), this.settings.get( 'taxRateAlreadyExists' ) );
        }
		
    }
	
}
