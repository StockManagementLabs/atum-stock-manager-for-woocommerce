/* =======================================
   LIST TABLE
   ======================================= */

import ActiveRow from './_active-row';
import BeforeUnload from '../_before-unload';
import Blocker from '../_blocker';
import EnhancedSelect from '../_enhanced-select';
import Globals from './_globals';
import Settings from '../../config/_settings';
import Swal from 'sweetalert2';
import Tooltip from '../_tooltip';
import Utils from '../../utils/_utils';
import WPHooks from '../../interfaces/wp.hooks';
import StickyColumns from './_sticky-columns';

export default class ListTable {
	
	doingAjax: JQueryXHR  = null;
	isRowExpanding = {};
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private toolTip: Tooltip,
		private enhancedSelect: EnhancedSelect,
		private stickyCols: StickyColumns
	) {

		// Bind events.
		this.bindEvents();

		// Add this component to the global scope so can be accessed by other add-ons.
		if ( ! window.hasOwnProperty( 'atum' ) ) {
			window[ 'atum' ] = {};
		}

		window[ 'atum' ][ 'ListTable' ] = this;
		
	}
	
	/**
	 * Bind List Table events
	 */
	bindEvents() {

		// Bind active class rows.
		ActiveRow.addActiveClassRow( this.globals.$atumTable );

		// Calculate compounded stocks.
		this.calculateCompoundedStocks();

		this.globals.$atumList

			//
			// Trigger expanding/collapsing event in inheritable products.
			// -----------------------------------------------------------
			.on( 'click', '.calc_type .has-child', ( evt: JQueryEventObject ) => $( evt.currentTarget ).closest( 'tr' ).trigger( 'atum-list-expand-row' ) )

			//
			// Triggers the expand/collapse row action.
			//-----------------------------------------
			.on( 'atum-list-expand-row', 'tbody tr', ( evt: JQueryEventObject, expandableRowClass: string, stopRowSelector: string, stopPropagation: boolean ) => {
				this.expandRow( $( evt.currentTarget ), expandableRowClass, stopRowSelector, stopPropagation );
			} )

			//
			// Expandable rows' checkboxes.
			// ----------------------------
			.on( 'change', '.check-column :checkbox', ( evt: JQueryEventObject ) => this.checkDescendats( $( evt.currentTarget ) ) )

			//
			// Check all the checkboxes.
			// -------------------------
			.on( 'click', '.manage-column :checkbox', ( evt: JQueryEventObject ) => this.checkAll( evt ) )

			//
			// "Control all products" button.
			// ------------------------------
			.on( 'click', '#control-all-products', ( evt: JQueryEventObject ) => this.controlAllProducts( evt ) );
		
		
		//
		// Global save for edited cells.
		// -----------------------------
		$( 'body' ).on( 'click', '#atum-update-list', ( evt: JQueryEventObject ) => this.saveData( $( evt.currentTarget ) ) );


		//
		// Warn the user about unsaved changes before navigating away.
		// -----------------------------------------------------------
		BeforeUnload.addPrompt( () => ! this.globals.$editInput.val() );

		// Display hidden footer.
		$( window ).on( 'load', () => $( '#wpfooter' ).show() );
		
	}
	
	/**
	 * Send the ajax call and replace table parts with updated version
	 */
	updateTable() {

		if ( this.doingAjax && this.doingAjax.readyState !== 4 ) {
			this.doingAjax.abort();
		}

		// Overwrite the filterData with the URL hash parameters
		this.globals.filterData = $.extend( this.globals.filterData, {
			view          : $.address.parameter( 'view' ) || '',
			paged         : $.address.parameter( 'paged' ) || 1,
			order         : $.address.parameter( 'order' ) || '',
			orderby       : $.address.parameter( 'orderby' ) || '',
			search_column : $.address.parameter( 'search_column' ) || '',
			sold_last_days: $.address.parameter( 'sold_last_days' ) || '',
			s             : $.address.parameter( 's' ) || '',
			...this.globals.getAutoFiltersValues( true ),
		} );

		this.doingAjax = $.ajax( {
			url       : window[ 'ajaxurl' ],
			dataType  : 'json',
			method    : 'GET',
			data      : this.globals.filterData,
			beforeSend: () => {
				this.toolTip.destroyTooltips();
				this.addOverlay();
			},
			// Handle the successful result.
			success   : ( response: any ) => {

				this.doingAjax = null;

				if ( typeof response === 'undefined' || ! response ) {
					return false;
				}

				// Update table with the coming rows.
				if ( typeof response.rows !== 'undefined' && response.rows.length ) {
					this.globals.$atumList.find( '#the-list' ).html( response.rows );
					this.restoreMeta();
				}

				// Change page url parameter.
				if ( response.paged > 0 ) {
					$.address.parameter( 'paged', response.paged );
				}

				// Update column headers for sorting.
				if ( typeof response.column_headers !== 'undefined' && response.column_headers.length ) {
					this.globals.$atumList.find( 'table' ).not( '.cloned' ).find( 'tr.item-heads' ).html( response.column_headers );
				}

				// Update the views filters.
				if ( typeof response.views !== 'undefined' && response.views.length ) {
					this.globals.$atumList.find( '.subsubsub' ).replaceWith( response.views );
				}

				// Update table navs.
				if ( typeof response.extra_t_n !== 'undefined' ) {

					if ( response.extra_t_n.top.length ) {
						this.globals.$atumList.find( '.tablenav.top' ).replaceWith( response.extra_t_n.top );
					}

					if ( response.extra_t_n.bottom.length ) {
						this.globals.$atumList.find( '.tablenav.bottom' ).replaceWith( response.extra_t_n.bottom );
					}

					// Update the autoFilters prop.
					this.globals.$autoFilters = this.globals.$atumList.find( '#filters_container .auto-filter' );

				}

				// Update the totals row.
				if ( typeof response.totals !== 'undefined' ) {
					this.globals.$atumList.find( 'table' ).not( '.cloned' ).find( 'tfoot tr.totals' ).html( response.totals );
				}

				// If there are active filters, show the reset button.
				if ( $.address.parameterNames().length ) {
					this.globals.$atumList.find( '.reset-filters' ).removeClass( 'hidden' );
				}

				// Regenerate the UI.
				this.toolTip.addTooltips();
				this.enhancedSelect.maybeRestoreEnhancedSelect();
				ActiveRow.addActiveClassRow( this.globals.$atumTable );
				this.removeOverlay();
				this.calculateCompoundedStocks();

				// Reload stickyColumns if needed.
				if ( this.globals.enabledStickyColumns ) {
					this.stickyCols.refreshStickyColumns();
				}

				// Trigger action after updating.
				this.wpHooks.doAction( 'atum_listTable_tableUpdated' );

			},
			error     : () => this.removeOverlay(),
		} );
		
	}
	
	/**
	 * Add the overlay effect while loading data
	 */
	addOverlay() {

		Blocker.block( $( '.atum-table-wrapper' ), {
			message   : null,
			overlayCSS: {
				background: '#000',
				opacity   : 0.5,
			},
		} );
		
	}
	
	/**
	 * Remove the overlay effect once the data is fully loaded
	 */
	removeOverlay() {

		Blocker.unblock( $( '.atum-table-wrapper' ) );
		
	}
	
	/**
	 * Set the table cell value with right format
	 *
	 * @param {JQuery}        $metaCell  The cell where will go the value.
	 * @param {string|number} value      The value to set in the cell.
	 */
	setCellValue( $metaCell: JQuery, value: string ) {

		let symbol: string      = $metaCell.data( 'symbol' ) || '',
		    currencyPos: string = this.globals.$atumTable.data( 'currency-pos' );

		if ( value === '' ) {
			value = this.settings.get( 'emptyCol' );
		}
		else if ( symbol ) {

			const precision: number = this.settings.get( 'currencyFormatNumDecimals' ),
			      thousand: string  = '',
			      decimal: string   = this.settings.get( 'currencyFormatDecimalSeparator' ),
			      format: string    = this.settings.get( 'currencyFormat' );

			let numericValue: number = parseFloat( value );

			value = <string> Utils.formatMoney( numericValue, symbol, precision, thousand, decimal, format );

		}

		$metaCell.addClass( 'unsaved' ).text( value );
		
	}
	
	/**
	 * Restore the edited meta after loading new table rows
	 */
	restoreMeta() {

		let editedCols: any = this.globals.$editInput.val();

		if ( editedCols ) {

			editedCols = JSON.parse( editedCols );

			$.each( editedCols, ( itemId: string, meta: any ) => {

				// Filter the meta cell that was previously edited.
				let $metaCell: JQuery = $( 'tr[data-id="' + itemId + '"] .set-meta' );

				if ( $metaCell.length ) {

					$.each( meta, ( key: string, value: any ) => {

						$metaCell = $metaCell.filter( '[data-meta="' + key + '"]' );

						if ( $metaCell.length ) {

							this.setCellValue( $metaCell, value );

							// Add the extra meta too.
							let extraMeta: any = $metaCell.data( 'extra-meta' );

							if ( typeof extraMeta === 'object' ) {

								$.each( extraMeta, ( index: number, extraMetaObj: any ) => {

									// Restore the extra meta from the edit input
									if ( editedCols[ itemId ].hasOwnProperty( extraMetaObj.name ) ) {
										extraMeta[ index ][ 'value' ] = editedCols[ itemId ][ extraMetaObj.name ];
									}

								} );

								$metaCell.data( 'extra-meta', extraMeta );

							}

						}

					} );

				}

			} );

		}
		
	}
	
	/**
	 * Every time a cell is edited, update the input value
	 *
	 * @param {JQuery} $metaCell The table cell that is being edited.
	 * @param {JQuery} $popover  The popover attached to the above cell.
	 */
	updateEditedColsInput( $metaCell: JQuery, $popover: JQuery ) {

		let editedCols: any  = this.globals.$editInput.val();

		const itemId: number = $metaCell.closest( 'tr' ).data( 'id' ),
		      meta: string   = $metaCell.data( 'meta' ),
		      newValue: any  = $popover.find( '.meta-value' ).val();

		// Update the cell value.
		this.setCellValue( $metaCell, newValue );

		// Initialize the JSON object.
		if ( editedCols ) {
			editedCols = JSON.parse( editedCols );
		}

		editedCols = editedCols || {};

		if ( ! editedCols.hasOwnProperty( itemId ) ) {
			editedCols[ itemId ] = {};
		}

		if ( ! editedCols[ itemId ].hasOwnProperty( meta ) ) {
			editedCols[ itemId ][ meta ] = {};
		}

		// Add the meta value to the object.
		editedCols[ itemId ][ meta ] = newValue;

		// WPML compatibility.
		if ( typeof $metaCell.data( 'custom' ) !== 'undefined' ) {
			editedCols[ itemId ][ `${ meta }_custom` ] = $metaCell.data( 'custom' ) || 'no';
		}

		// WPML compatibility.
		if ( typeof $metaCell.data( 'currency' ) !== 'undefined' ) {
			editedCols[ itemId ][ `${ meta }_currency` ] = $metaCell.data( 'currency' );
		}

		// Add the extra meta data (if any).
		if ( $popover.hasClass( 'with-meta' ) ) {

			let extraMeta: any = $metaCell.data( 'extra-meta' );

			$popover.find( 'input' ).not( '.meta-value' ).each( ( index: number, input: Element ) => {

				const $input: JQuery = $( input ),
				      value: any     = $input.val(),
				      name: string   = $input.attr( 'name' );

				editedCols[ itemId ][ name ] = value;

				// Save the meta values in the cell data for future uses.
				if ( typeof extraMeta === 'object' ) {

					$.each( extraMeta, ( index: number, elem: any ) => {

						if ( elem.name === name ) {
							extraMeta[ index ][ 'value' ] = value;

							return false;
						}

					} );

				}

			} );

		}

		this.globals.$editInput.val( JSON.stringify( editedCols ) );
		this.globals.$atumList.trigger( 'atum-edited-cols-input-updated', [ $metaCell ] );
		
	}
	
	/**
	 * Check if we need to add the Update button
	 */
	maybeAddSaveButton() {

		let $tableTitle: JQuery = this.globals.$atumList.siblings( '.wp-heading-inline' );

		if ( ! $tableTitle.find( '#atum-update-list' ).length ) {

			$tableTitle.append( $( '<button/>', {
				id   : 'atum-update-list',
				class: 'page-title-action button-primary',
				text : this.settings.get( 'saveButton' ),
			} ) );

			// Check whether to show the first edit popup.
			if ( typeof this.settings.get( 'firstEditKey' ) !== 'undefined' ) {

				Swal.fire( {
					title             : this.settings.get( 'important' ),
					text              : this.settings.get( 'preventLossNotice' ),
					icon              : 'warning',
					confirmButtonText : this.settings.get( 'ok' ),
					confirmButtonColor: 'var(--primary)',
				} );

			}
		}
		
	}
	
	/**
	 * Save the edited columns
	 *
	 * @param {JQuery} $button The "Save Data" button.
	 */
	saveData( $button: JQuery ) {

		if ( this.doingAjax && this.doingAjax.readyState !== 4 ) {
			this.doingAjax.abort();
		}

		let data: any = {
			token         : this.settings.get( 'nonce' ),
			action        : 'atum_update_data',
			data          : this.globals.$editInput.val(),
			first_edit_key: null,
		};

		if ( typeof this.settings.get( 'firstEditKey' ) !== 'undefined' ) {
			data.first_edit_key = this.settings.get( 'firstEditKey' );
		}

		this.doingAjax = $.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			data      : data,
			beforeSend: () => {
				$button.prop( 'disabled', true );
				this.addOverlay();
			},
			success   : ( response: any ) => {

				if ( typeof response === 'object' && typeof response.success !== 'undefined' ) {
					const noticeType = response.success ? 'updated' : 'error';
					Utils.addNotice( noticeType, response.data );
				}

				if ( typeof response.success !== 'undefined' && response.success === true ) {
					$button.remove();
					this.globals.$editInput.val( '' );
					this.updateTable();
				}
				else {
					$button.prop( 'disabled', false );
					this.removeOverlay();
				}

				this.doingAjax = null;
				this.settings.delete( 'firstEditKey' );

			},
			error     : () => {

				this.doingAjax = null;
				$button.prop( 'disabled', false );
				this.removeOverlay();

				this.settings.delete( 'firstEditKey' );

			},
		} );
		
	}
	
	/**
	 * Expand/Collapse rows with childrens
	 *
	 * @param {JQuery}  $row
	 * @param {string}  expandableRowClass
	 * @param {string}  stopRowSelector
	 * @param {boolean} stopPropagation
	 *
	 * @return void|boolean
	 */
	expandRow( $row: JQuery, expandableRowClass?: string, stopRowSelector?: string, stopPropagation?: boolean ): boolean | void {

		const rowId: number = $row.data( 'id' );

		if ( typeof expandableRowClass === 'undefined' ) {
			expandableRowClass = 'expandable';
		}

		if ( typeof stopRowSelector === 'undefined' ) {
			stopRowSelector = '.main-row';
		}

		// Sync the sticky columns table.
		if ( this.globals.$stickyCols !== null && ( typeof stopPropagation === 'undefined' || stopPropagation !== true ) ) {

			let $siblingTable: JQuery = $row.closest( '.atum-list-table' ).siblings( '.atum-list-table' ),
			    $syncRow: JQuery      = $siblingTable.find( 'tr[data-id=' + rowId.toString().replace( 'c', '' ) + ']' );

			this.expandRow( $syncRow, expandableRowClass, stopRowSelector, true );

		}
		
		// Avoid multiple clicks before expanding.
		if ( typeof this.isRowExpanding[ rowId ] !== 'undefined' && this.isRowExpanding[ rowId ] === true ) {
			return false;
		}

		this.isRowExpanding[ rowId ] = true;

		let $rowTable: JQuery   = $row.closest( 'table' ),
		    $nextRow: JQuery    = $row.next(),
		    childRows: JQuery[] = [];

		if ( $nextRow.length ) {
			$row.toggleClass( 'expanded' );
			this.toolTip.destroyTooltips();
		}
		
		// Loop until reaching the next main row.
		while ( ! $nextRow.filter( stopRowSelector ).length ) {

			if ( ! $nextRow.length ) {
				break;
			}

			if ( ! $nextRow.hasClass( expandableRowClass ) ) {
				$nextRow = $nextRow.next();
				continue;
			}

			childRows.push( $nextRow );

			if ( ( $rowTable.is( ':visible' ) && ! $nextRow.is( ':visible' ) ) || ( ! $rowTable.is( ':visible' ) && $nextRow.css( 'display' ) === 'none' ) ) {
				$nextRow.addClass( 'expanding' ).show();
			}
			else {
				$nextRow.addClass( 'collapsing' ).hide();
			}

			$nextRow = $nextRow.next();

		}
		
		// Re-enable the expanding again once the animation is completed.
		setTimeout( () => {

			delete this.isRowExpanding[ rowId ];

			// Do this only when all the rows has been already expanded.
			if ( ! Object.keys( this.isRowExpanding ).length && ( typeof stopPropagation === 'undefined' || stopPropagation !== true ) ) {
				this.toolTip.addTooltips();
			}

			$.each( childRows, ( index: number, $childRow: JQuery ) => {
				$childRow.removeClass( 'expanding collapsing' );
			} );

		}, 320 );

		this.globals.$atumList.trigger( 'atum-after-expand-row', [ $row, expandableRowClass, stopRowSelector ] );
		
	}
	
	/**
	 * Checks/Unchecks the descendants rows when checking/unchecking their container
	 *
	 * @param {JQuery} $parentCheckbox
	 */
	checkDescendats( $parentCheckbox: JQuery ) {

		let $containerRow: JQuery = $parentCheckbox.closest( 'tr' );
		const isChecked: boolean = $parentCheckbox.is( ':checked' );

		if ( $containerRow.find( '.has-child' ).length ) {

			// If is not expanded, expand it.
			if ( $containerRow.hasClass( 'main-row' ) && ! $containerRow.hasClass( 'expanded' ) && isChecked ) {
				$containerRow.find( '.has-child' ).click();
			}

			let $nextRow: JQuery = $containerRow.next();
			
			// Check/Uncheck all the children rows.
			while ( $nextRow.length && ! $nextRow.hasClass( 'main-row' ) ) {

				const $checkbox: JQuery = $nextRow.find( '.check-column input:checkbox' );

				$checkbox.prop( 'checked', isChecked );
				ActiveRow.switchActiveClass( $checkbox );

				if ( isChecked ) {
					$nextRow.find( '.has-child' ).click();
				}

				$nextRow = $nextRow.next();

				if ( ! $containerRow.hasClass( 'main-row' ) && $nextRow.hasClass( 'expandable' ) ) {
					break;
				}

			}
			
		}
		
	}

	/**
	 * Check all the checkboxes
	 *
	 * @param {JQueryEventObject} evt
	 */
	checkAll( evt: JQueryEventObject ) {

		// Prevent the WP default behaviour.
		evt.stopImmediatePropagation();

		const $checkAll: JQuery  = $( evt.currentTarget ),
		      isChecked: boolean = $checkAll.is( ':checked' );

		let $checkboxes: JQuery = this.globals.$atumTable.find( 'tbody .check-column :checkbox:visible' );

		if ( isChecked ) {
			$checkboxes = $checkboxes.not( ':checked' );
		}
		else {
			$checkboxes = $checkboxes.filter( ':checked' );
		}

		$checkboxes.prop( 'checked', isChecked ).change();
		this.globals.$atumTable.find( '.manage-column :checkbox' ).not( $checkAll ).prop( 'checked', isChecked );

	}

	/**
	 * Control all the products using the initial button
	 *
	 * @param {JQueryEventObject} evt
	 */
	controlAllProducts( evt: JQueryEventObject ) {

		const $button: JQuery = $( evt.currentTarget );

		$.ajax( {
			url       : window[ 'ajaxurl' ],
			method    : 'POST',
			dataType  : 'json',
			beforeSend: () => $button.prop( 'disabled', true ).after( '<span class="atum-spinner"><span></span></span>' ),
			data      : {
				token : $button.data( 'nonce' ),
				action: 'atum_control_all_products',
			},
			success   : () => location.reload(),
		} );

	}
	
	/**
	 * Calculate the compounded stock amounts for all the inheritable products
	 */
	calculateCompoundedStocks() {

		this.globals.$atumTable.find( '.compounded, .compounded-available' ).each( ( index: number, elem: Element ) => {

			let $compoundedCell: JQuery = $( elem ),
			    $row: JQuery            = $compoundedCell.closest( 'tr' ),
			    $nextRow: JQuery        = $row.next( '.has-compounded' ),
				compoundedAmt: number = 0;

			if ( $row.hasClass( 'expandable' ) ) {
				return;
			}

			while ( $nextRow.length ) {
				
				if ( $compoundedCell.hasClass( 'compounded-available' ) ) {

					const $availableStockCell = $nextRow.find( '.calc_available_to_produce .set-meta, .calc_available_to_produce .calculated span' ),
					      availableStockValue = ! $availableStockCell.length ? '0' : $availableStockCell.text().trim();

					compoundedAmt += parseFloat( availableStockValue ) || 0;
				}
				else {

					const $stockCell = $nextRow.find( '._stock .set-meta, ._stock .calculated span' ),
					      stockValue = ! $stockCell.length ? '0' : $stockCell.text().trim();

					compoundedAmt += parseFloat( stockValue ) || 0;

				}

				if ( 0 === compoundedAmt && $compoundedCell.attr( 'class' ).includes( 'compounded-available' ) ) {
					$compoundedCell.text( '-' );
				}
				else {
					$compoundedCell.text( compoundedAmt );
				}

				$nextRow = $nextRow.next( '.has-compounded' );

			}

		} );

	}

}

