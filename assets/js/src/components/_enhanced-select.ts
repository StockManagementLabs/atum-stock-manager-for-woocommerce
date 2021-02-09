/* =======================================
   ENHANCED SELECT
   ======================================= */

export default class EnhancedSelect {
	
	constructor() {
		
		this.addAtumClasses();
		
		$( 'body' ).on( 'wc-enhanced-select-init', () => this.addAtumClasses() );
		
	}
	
	/**
	 * Restore the enhanced select filters (if any)
	 */
	maybeRestoreEnhancedSelect() {
		
		$( '.select2-container--open' ).remove();
		$( 'body' ).trigger( 'wc-enhanced-select-init' );
		
	}
	
	/**
	 * Add the select2 to the specified selectors
	 *
	 * @param {JQuery}  $selector
	 * @param {any}     options
	 * @param {boolean} avoidEmptySelections
	 */
	doSelect2( $selector: JQuery, options: any = {}, avoidEmptySelections: boolean = false ) {
		
		if ( typeof $.fn[ 'select2' ] !== 'function' ) {
			return;
		}

		options = Object.assign( {
			minimumResultsForSearch: 10,
		}, options );
		
		$selector.each( ( index: number, elem: Element ) => {

			const $select: JQuery    = $( elem ),
			      selectOptions: any = { ...options };
			
			if ( $select.hasClass( 'atum-select-multiple' ) && $select.prop( 'multiple' ) === false ) {
				$select.prop( 'multiple', true );
			}

			// Add the classes for not well-prepared selects.
			if ( ! $select.hasClass( 'atum-select2') ) {
				$select.addClass('atum-select2');
				this.addAtumClasses( $select );
			}
			
			if ( avoidEmptySelections ) {
				
				$select.on( 'select2:selecting', ( evt: Event ) => {
					
					const $select: JQuery = $( evt.currentTarget ),
					      value: any      = $select.val();
					
					if ( Array.isArray( value ) && ( $.inArray( '', value ) > -1 || $.inArray( '-1', value ) > -1 ) ) {
						
						// Avoid selecting the "None" option (empty value or -1 in some cases).
						$.each( value, ( index: number, elem: string ) => {
							if ( elem === '' || elem === '-1' ) {
								value.splice( index, 1 );
							}
						} );
						
						$select.val( value );
						
					}
					
				} );
				
			}
			
			( <any>$select ).select2( selectOptions );
			$select.siblings( '.select2-container' ).addClass( 'atum-select2' );
			this.maybeAddTooltip( $select );
			
		} );
	
	}
	
	/**
	 * Add the ATUM classes to all the enhanced selects to avoid conflicts with other selects
	 *
	 * @param {JQuery} $selects
	 */
	addAtumClasses( $selects: JQuery = null ) {

		$selects = $selects || $( 'select' ).filter( '.atum-select2, .atum-enhanced-select' );

		if ( ! $selects.length ) {
			return;
		}

		$selects

			// Add custom classes to the select2 containers.
			.each( ( index: number, elem: Element ) => {
			
				const $select: JQuery           = $( elem ),
				      $select2Container: JQuery = $select.siblings( '.select2-container' ).not( '.atum-select2, .atum-enhanced-select' )

				if ( $select2Container.length ) {
					$select2Container.addClass( $select.hasClass( 'atum-select2' ) ? 'atum-select2' : 'atum-enhanced-select' );

					// Pass any attached tooltip
					this.maybeAddTooltip( $select );
				}

			} )

			// Add custom class to the select2 dropdown on opening.
			.on( 'select2:opening', ( evt: JQueryEventObject ) => {

				const $select: JQuery  = $( evt.currentTarget ),
				      select2Data: any = $select.data();

				if ( select2Data.hasOwnProperty( 'select2' ) ) {

					const $dropdown: JQuery = select2Data.select2.dropdown.$dropdown;

					if ( $dropdown.length ) {
						$dropdown.addClass( 'atum-select2-dropdown' );
					}
				}

			} );
		
	}
	
	/**
	 * Add tooltip to the select if needed
	 *
	 * @param {JQuery} $select
	 */
	maybeAddTooltip( $select: JQuery ) {
		
		if ( $select.hasClass( 'atum-tooltip' ) ) {
			const $select2Rendered: JQuery = $select.siblings( '.select2-container' ).find( '.select2-selection__rendered' );
			$select2Rendered.addClass( 'atum-tooltip' );
		}
		
	}
	
}
