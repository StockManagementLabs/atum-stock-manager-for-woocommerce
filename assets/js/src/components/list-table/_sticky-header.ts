/* =======================================
   STICKY HEADER FOR LIST TABLES
   ======================================= */

/**
 * Third party plugins
 */
import '../../../vendor/jquery.floatThead';                // A fixed version compatible with webpack

import Settings from '../../config/_settings';
import Globals from './_globals';
import StickyColumns from './_sticky-columns';
import Tooltip from '../_tooltip';
import WPHooks from '../../interfaces/wp.hooks';

export default class StickyHeader {

	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private stickyCols: StickyColumns,
		private tooltip: Tooltip
	) {
		
		// Add the floating table header.
		this.globals.enabledStickyHeader = $( '.sticky-header-button' ).hasClass( 'active' );
		if ( this.globals.enabledStickyHeader ) {
			this.addFloatThead();
		}
		
		this.bindEvents();
		
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		// This event will trigger on the table when the header is floated and unfloated.
		this.globals.$atumTable.on( 'floatThead', ( evt: any, isFloated: boolean, $floatContainer: JQuery ) => {

			if ( isFloated ) {

				$floatContainer.css( 'height', 'auto' );
				$( '.jspContainer' ).height( $( '.jspPane' ).height() );

				// Hide search dropdown on sticky.
				this.globals.$searchColumnDropdown.hide();

				// Hide on mobile view.
				if ( $( '#wpadminbar' ).css( 'position' ) === 'absolute' ) {
					$floatContainer.hide();
				}
				else {
					$floatContainer.show();
				}

				// Add the sticky columns to the floating header if needed.
				if ( this.globals.enabledStickyColumns ) {

					// Reposition the sticky cols to fit the floating header.
					if ( this.globals.$stickyCols !== null ) {
						this.globals.$stickyCols.css( 'top', -1 * ( $floatContainer.height() - 1 ) );
					}

					const $floatTheadTable = this.globals.$atumList.find( '.floatThead-table' );
					this.globals.$floatTheadStickyCols = this.stickyCols.createStickyColumns( $floatTheadTable );

					if ( this.globals.$floatTheadStickyCols !== null ) {

						$floatTheadTable.after( this.globals.$floatTheadStickyCols );
						this.globals.$floatTheadStickyCols.css( 'width', this.globals.$stickyCols.width() + 1 );

						// Add the colgroup tag with column widths.
						this.globals.$floatTheadStickyCols.prepend( '<colgroup />' );

						const $colGroup = this.globals.$floatTheadStickyCols.find( 'colgroup' );

						$floatTheadTable.find( 'thead .item-heads' ).children().each( ( index: number, elem: Element ) => {

							let $cell = $( elem ),
							    id    = $cell.attr( 'id' );

							if ( $cell.hasClass( 'hidden' ) ) {
								return;
							}

							if ( this.globals.$floatTheadStickyCols.find( 'thead .item-heads' ).children( '#' + id ).length ) {
								$colGroup.append( '<col style="width:' + $cell.width() + 'px;">' );
							}

						} );

						// Remove the manage-column class to not conflict with the WP's Screen Options functionality.
						this.globals.$floatTheadStickyCols.find( '.manage-column' ).removeClass( 'manage-column' );

						$colGroup.prependTo( this.globals.$floatTheadStickyCols );
						this.adjustStickyHeaders( this.globals.$floatTheadStickyCols, $floatTheadTable );

					}

				}

			}
			else {

				$floatContainer.css( 'height', 0 );

				if ( this.globals.enabledStickyColumns ) {

					// Reset the sticky columns position.
					if ( this.globals.$stickyCols !== null ) {
						this.globals.$stickyCols.css( 'top', 0 );
					}

					// Remove the floating header's sticky columns.
					if ( this.globals.$floatTheadStickyCols !== null ) {
						this.globals.$floatTheadStickyCols.remove();
					}

				}

			}

		});

		// Bind Scroll-X events.
		this.globals.$atumList.on( 'atum-scroll-bar-scroll-x', ( evt: any, origEvt: any, scrollPositionX: number, isAtLeft: boolean, isAtRight: boolean ) => {

			// Handle the sticky cols position and visibility when scrolling.
			if ( this.globals.enabledStickyColumns === true && this.globals.$stickyCols !== null ) {

				// Add the stickyCols table (if enabled).
				if ( ! this.globals.$atumList.find( '.atum-list-table.cloned' ).length ) {
					this.globals.$atumTable.after( this.globals.$stickyCols );
					this.tooltip.addTooltips();
					this.wpHooks.doAction( 'atum_stickyHeaders_addedStickyColumns' );
				}

				// Hide the sticky cols when reaching the left side of the panel.
				if ( scrollPositionX <= 0 ) {

					this.globals.$stickyCols.hide().css( 'left', 0 );

					if ( this.globals.$floatTheadStickyCols !== null ) {
						this.globals.$floatTheadStickyCols.hide().css( 'left', 0 );
					}

				}
				// Reposition the sticky cols while scrolling the pane.
				else {

					this.globals.$stickyCols.show().css( 'left', scrollPositionX );

					if ( this.globals.$floatTheadStickyCols !== null ) {
						this.globals.$floatTheadStickyCols.show().css( 'left', scrollPositionX );
					}

					// Ensure sticky column heights are matching.
					this.adjustStickyHeaders( this.globals.$stickyCols, this.globals.$atumTable );

				}

			}

		});

	}
	
	/**
	 * Add the floating header to the table
	 */
	addFloatThead() {

		if ( ! this.globals.enabledStickyHeader ) {
			return false;
		}

		if ( typeof this.globals.$atumTable.data( 'floatTheadAttached' ) !== 'undefined' && this.globals.$atumTable.data( 'floatTheadAttached' ) !== false ) {
			this.reloadFloatThead();

			return;
		}

		( <any> this.globals.$atumTable ).floatThead( {
			responsiveContainer: ( $table: JQuery ) => {
				return $table.closest( '.jspContainer' );
			},
			position           : 'absolute',
			top                : $( '#wpadminbar' ).height(),
			autoReflow         : true,
		} );
		
	}
	
	/**
	 * Reload the floating table header
	 */
	reloadFloatThead() {

		if ( this.globals.enabledStickyHeader ) {
			this.destroyFloatThead();
			this.addFloatThead();
		}
		
	}
	
	/**
	 * Destroy the floating table header
	 */
	destroyFloatThead() {

		if ( typeof this.globals.$atumTable.data( 'floatTheadAttached' ) !== 'undefined' && this.globals.$atumTable.data( 'floatTheadAttached' ) !== false ) {
			( <any> this.globals.$atumTable ).floatThead( 'destroy' );
		}
		
	}
	
	/**
	 * Adjust the header heights to match the List Table heights
	 *
	 * @param jQuery $stickyTable
	 * @param jQuery $origTable
	 */
	adjustStickyHeaders( $stickyTable: JQuery, $origTable: JQuery ) {

		$.each( [ 'column-groups', 'item-heads' ], ( index: number, className: string ) => {
			$stickyTable.find( '.' + className + ' > th' ).first().css( 'height', $origTable.find( '.' + className + ' > th' ).first().height() );
		} );
		
	}
	
}
