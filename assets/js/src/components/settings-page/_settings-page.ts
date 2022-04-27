/* =======================================
   SETTINGS PAGE
   ======================================= */

import EnhancedSelect from '../_enhanced-select';
import ButtonGroup from '../_button-group';
import ColorPicker from '../_color-picker';
import DateTimePicker from '../_date-time-picker';
import FileUploader, { WPMediaModalOptions } from '../_file-uploader';
import Settings from '../../config/_settings';
import SmartForm from '../_smart-form';
import Swal, { SweetAlertResult } from 'sweetalert2';
import TabLoader from '../_tab-loader';
import Tooltip from '../_tooltip';
import WPHooks from '../../interfaces/wp.hooks';

export default class SettingsPage {
	
	$settingsWrapper: JQuery;
	$nav: JQuery;
	$form: JQuery;
	navigationReady: boolean = false;
	numHashParameters: number = 0;
	tabLoader: TabLoader;
	wpHooks: WPHooks = window['wp']['hooks']; // WP hooks.

	constructor(
		private settings: Settings,
		private enhancedSelect: EnhancedSelect,
		private tooltip: Tooltip,
		private dateTimePicker: DateTimePicker
	) {
		
		// Initialize selectors.
		this.$settingsWrapper = $( '.atum-settings-wrapper' );
		this.$nav = this.$settingsWrapper.find( '.atum-nav' );
		this.$form = this.$settingsWrapper.find( '#atum-settings' );
		
		// URL hash navigation.
		this.setupNavigation();

		// Enable DateTimePickers
		this.initRangeDateTimePicker();

		// Enable Tooltips.
		this.tooltip.addTooltips( this.$form );

		// Enable ColoPickers.
		ColorPicker.doColorPickers( this.settings.get( 'selectColor' ) );

		// Enable Select2.
		this.enhancedSelect.doSelect2( this.$settingsWrapper.find( 'select' ), {}, true );

		// Enable button groups.
		ButtonGroup.doButtonGroups( this.$form );

		// Enable image uploader with the default options.
		this.doFileUploaders();

		// Enable image selectors
        this.doImageSelector();

		// Enable WP editors.
		this.doEditors();

		// Toggle Menu.
		this.toggleMenu();

		new SmartForm( this.$form, this.settings.get( 'atumPrefix' ) );

		this.bindEvents();

		// Adjust the nav height.
		this.$nav.css( 'min-height', `${ this.$nav.find( '.atum-nav-list' ).outerHeight() + 200 }px` );
	
	}

	/**
	 * Bind events
	 */
	bindEvents() {

		this.$form

			// Out of stock threshold option updates.
			.on( 'change', '#atum_out_stock_threshold', ( evt: JQueryEventObject ) => this.maybeClearOutStockThreshold( $( evt.currentTarget ) ) )

			// Script Runner fields.
			.on( 'click', '.script-runner .tool-runner', ( evt: JQueryEventObject ) => this.runScript( $( evt.currentTarget ) ) )

			// Theme selector fields.
			.on( 'click', '.selector-box', ( evt: JQueryEventObject ) => this.doThemeSelector( $( evt.currentTarget ) ) )

			// Toggle checkboxes.
			.on( 'click', '.atum-settings-input[type=checkbox]', ( evt: JQueryEventObject ) => this.clickCheckbox( $( evt.currentTarget ) ) )

			// Default color fields.
			.on( 'click', '.reset-default-colors', ( evt: JQueryEventObject ) => this.doResetDefaultColors( $( evt.currentTarget ) ) )

			// Switcher multi-checkbox.
			.on( 'change', '.atum-multi-checkbox-main', ( evt: JQueryEventObject ) => this.toggleMultiCheckboxPanel( $( evt.currentTarget ) ) )

			.on( 'change', '.remove-datepicker-range', ( evt: JQueryEventObject ) => this.toggleRangeDateTimeRemove( $( evt.currentTarget ) ) )

			.on( 'change update blur', '.range-datepicker.range-from, .range-datepicker.range-to, .remove-datepicker-range', () => this.setRangeDateTimeInputs() );

		// Footer positioning.
		$( window ).on( 'load', () => {

			if ( $( '.footer-box' ).hasClass( 'no-style' ) ) {
				$( '#wpfooter' ).css( 'position', 'relative' ).show();
				$( '#wpcontent' ).css( 'min-height', '95vh' );
			}

		} );


	}

	/**
	 * Setup the settings navigation
	 */
	setupNavigation() {

		// Instantiate the loader to register the jQuery.address and the events.
		this.tabLoader = new TabLoader( this.$settingsWrapper, this.$nav );
		
		this.$settingsWrapper

			// Show the form after the page is loaded.
			.on( 'atum-tab-loader-init', () => this.$form.show() )

			// Tab clicked.
			.on( 'atum-tab-loader-clicked-tab', ( evt: JQueryEventObject, $navLink: JQuery, tab: string ) => {

				if ( this.$form.find( '.dirty' ).length ) {

					// Warn the user about unsaved data.
					Swal.fire( {
						title            : this.settings.get( 'areYouSure' ),
						text             : this.settings.get( 'unsavedData' ),
						icon             : 'warning',
						showCancelButton : true,
						confirmButtonText: this.settings.get( 'continue' ),
						cancelButtonText : this.settings.get( 'cancel' ),
						reverseButtons   : true,
						allowOutsideClick: false,
					} )
					.then( ( result: SweetAlertResult ) => {

						if ( result.isConfirmed ) {
							this.moveToTab( $navLink );
						}
						else {
							$navLink.blur();
						}

					} );

				}
				else {
					this.moveToTab( $navLink );
				}

			} );
		
	}

	/**
	 * Hide colors
	 */
	hideColors() {

		const $tableColorSettings: JQuery = $( '#atum_setting_color_scheme #atum-table-color-settings' );

		if ( $tableColorSettings.length > 0 ) {

			const mode = $tableColorSettings.data( 'display' );

			$tableColorSettings.find( '.atum-settings-input.atum-color' ).not( '[data-display=' + mode + ']' ).closest( 'tr' ).hide();

			$tableColorSettings.find( 'tr' ).each( ( index: number, elem: Element ) => {
				if ( $( elem ).css( 'display' ) === 'none' ) {
					$( elem ).prependTo( $tableColorSettings.find( 'tbody' ) );
				}
			} );

		}

	}

	/**
	 * Move to a new settings tab
	 *
	 * @param {JQuery} $navLink
	 */
	moveToTab( $navLink: JQuery ) {

		const $formSettingsWrapper: JQuery = this.$form.find( '.form-settings-wrapper' );

		this.$nav.find( '.atum-nav-link.active' ).not( $navLink ).removeClass( 'active' );
		$navLink.addClass( 'active' );

		$formSettingsWrapper.addClass( 'overlay' );

		this.$form.load( `${ $navLink.attr( 'href' ) } .form-settings-wrapper`, () => {

			ColorPicker.doColorPickers( this.settings.get( 'selectColor' ) );
			this.initRangeDateTimePicker();
			this.enhancedSelect.maybeRestoreEnhancedSelect();
			this.enhancedSelect.doSelect2( this.$settingsWrapper.find( 'select' ), {}, true );
			this.doFileUploaders();
			this.doImageSelector();
			this.doEditors();
			this.$form.find( '[data-dependency]' ).change().removeClass( 'dirty' );
			this.$form.show();

			const $inputButton: JQuery = this.$form.find( 'input:submit' );

			if ( $navLink.parent().hasClass( 'no-submit' ) ) {
				$inputButton.hide();
			}
			else {
				$inputButton.show();
			}

			// Enable Tooltips.
			this.tooltip.addTooltips( this.$form );

			this.$settingsWrapper.trigger( 'atum-settings-page-loaded', [ $navLink.data( 'tab' ) ] );

			if ( 'visual_settings' === $navLink.data( 'tab' ) ) {
				this.hideColors();
			}

			this.wpHooks.doAction( 'atum_settingsPage_moveToTab', $navLink );

		} );
		
	}

	/**
	 * Toggle menu
	 */
	toggleMenu() {

		const $navList: JQuery = this.$nav.find( '.atum-nav-list' );

		$( '.toogle-menu, .atum-nav-link' ).click( () => $navList.toggleClass( 'expand-menu' ) );

		$( window ).resize( () => $navList.removeClass( 'expand-menu' ) );
		
	}

	/**
	 * Clear Out of Stock Threshold
	 *
	 * @param {JQuery} $checkbox
	 */
	maybeClearOutStockThreshold( $checkbox: JQuery ) {

		if ( $checkbox.is( ':checked' ) && this.settings.get( 'isAnyOostSet' ) ) {

			Swal.fire( {
				title              : '',
				text               : this.settings.get( 'oostSetClearText' ),
				icon               : 'question',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get( 'startFresh' ),
				cancelButtonText   : this.settings.get( 'useSavedValues' ),
				reverseButtons     : true,
				allowOutsideClick  : false,
				allowEscapeKey     : false,
				allowEnterKey      : false,
				showLoaderOnConfirm: true,
				preConfirm         : (): Promise<any> => {

					return new Promise( ( resolve: Function, reject: Function ) => {

						$.ajax( {
							url     : window[ 'ajaxurl' ],
							method  : 'POST',
							dataType: 'json',
							data    : {
								action  : this.settings.get( 'oostSetClearScript' ),
								security: this.settings.get( 'runnerNonce' ),
							},
							success : ( response: any ) => {

								if ( response.success !== true ) {
									Swal.showValidationMessage( response.data );
								}

								resolve( response.data );

							},
						} );

					} );

				},
			} )
			.then( ( result: SweetAlertResult ) => {

				if ( result.isConfirmed ) {
					Swal.fire( {
						title            : this.settings.get( 'done' ),
						icon             : 'success',
						text             : result.value,
						confirmButtonText: this.settings.get( 'ok' ),
					} );
				}

			} );
			
		}
		else if ( ! $checkbox.is( ':checked' ) ) {

			Swal.fire( {
				title            : '',
				text             : this.settings.get( 'oostDisableText' ),
				icon             : 'info',
				confirmButtonText: this.settings.get( 'ok' ),
			} );

		}
		
	}

	/**
	 * Run a tool script
	 *
	 * @param {JQuery} $button
	 */
	runScript( $button: JQuery ) {

		const $scriptRunner = $button.closest( '.script-runner' );

		if ( $scriptRunner.is( '.recurrent' ) ) {
			this.runRecurrentScript( $button, $scriptRunner );
		}
		else {
			this.runSingleScript( $button, $scriptRunner );
		}
	}

	/**
	 * Run single script
	 *
	 * @param {JQuery} $button
	 * @param {JQuery} $scriptRunner
	 */
	runSingleScript( $button: JQuery, $scriptRunner: JQuery ) {

		Swal.fire( {
			title              : this.settings.get( 'areYouSure' ),
			text               : $scriptRunner.data( 'confirm' ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'run' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function ) => {

					const $input: JQuery = $scriptRunner.find( `[data-tool="${ $scriptRunner.data( 'input' ) }"]` );

					let postData: any = {
						action  : $scriptRunner.data( 'action' ),
						security: this.settings.get( 'runnerNonce' ),
					};

					if ( $input.length > 1 ) {

						postData.option = {};

						$input.each( ( index: number, elem: Element ) => {
							const $field: JQuery = $( elem );
							postData.option[ $field.attr( 'name' ) ] = $field.val();
						} );

					}
					else if ( $input.length ) {
						postData.option = $input.val();
					}

					$.ajax( {
						url       : window[ 'ajaxurl' ],
						method    : 'POST',
						dataType  : 'json',
						data      : postData,
						beforeSend: () => $button.prop( 'disabled', true ),
						success   : ( response: any ) => {

							$button.prop( 'disabled', false );

							if ( response.success !== true ) {
								Swal.showValidationMessage( response.data );
							}

							resolve( response.data );

						},
						error : () => Swal.showValidationMessage( this.settings.get( 'unexpectedError' ) )
					} );

				} );

			},

		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				Swal.fire( {
					title            : this.settings.get( 'done' ),
					icon             : 'success',
					text             : result.value,
					confirmButtonText: this.settings.get( 'ok' ),
				} )
				.then( () => {
					this.$settingsWrapper.trigger( 'atum-settings-script-runner-done', [ $scriptRunner ] );
				} );
			}

		} );
		
	}

	/**
	 * Run recurrent script
	 *
	 * @param {JQuery} $button
	 * @param {JQuery} $scriptRunner
	 */
	runRecurrentScript( $button: JQuery, $scriptRunner: JQuery ) {

		Swal.fire( {
			title              : this.settings.get( 'areYouSure' ),
			text               : $scriptRunner.data( 'confirm' ),
			icon               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get( 'run' ),
			cancelButtonText   : this.settings.get( 'cancel' ),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm         : (): Promise<any> => {

				return new Promise( ( resolve: Function ) => {

					const $input: JQuery = $scriptRunner.find( `[data-tool="${ $scriptRunner.data( 'input' ) }"]` );

					let data: any = {
						action  : $scriptRunner.data( 'action' ),
						security: this.settings.get( 'runnerNonce' ),
					};

					if ( $input.length > 1 ) {

						data.option = {};

						$input.each( ( index: number, elem: Element ) => {
							const $field: JQuery = $( elem );
							data.option[ $field.attr( 'name' ) ] = $field.val();
						} );

					}
					else if ( $input.length ) {
						data.option = $input.val();
					}

					const doRecurrentAjaxCall: Function = ( offset: number = 0 ): JQueryXHR => {

						return $.ajax( {
							url     : window[ 'ajaxurl' ],
							method  : 'POST',
							dataType: 'json',
							data    : { ...data, offset },
							error   : () => Swal.showValidationMessage( this.settings.get( 'unexpectedError' ) ),
						} );

					};

					$button.prop( 'disabled', true );

					const recurrentCall: Function = ( offset: number = 0 ) =>

						doRecurrentAjaxCall( offset ).done( ( response: any ) => {

							if ( response.success === true ) {

								Swal.update( {
									text             : $scriptRunner.data( 'processing' ).replace( '{processed}', response.data.limit ).replace( '{total}', response.data.total ),
									showConfirmButton: false,
								} );

								if ( response.data.finished !== undefined && response.data.finished === true ) {
									$button.prop( 'disabled', false );
									resolve( response.data );
								}
								else {
									offset = response.data.limit !== undefined ? response.data.limit : 100;
									return recurrentCall( offset );
								}
							}
							else {
								$button.prop( 'disabled', false );
								Swal.showValidationMessage( response.data );
								resolve( response.data );
							}

						} );

					recurrentCall();

				} );

			},

		} )
		.then( ( result: SweetAlertResult ) => {

			if ( result.isConfirmed ) {
				Swal.fire( {
					title            : this.settings.get( 'done' ),
					icon             : 'success',
					text             : $scriptRunner.data( 'processed' ).replace( '{processed}', result.value.total ),
					confirmButtonText: this.settings.get( 'ok' ),
				} )
				.then( () => {
					this.$settingsWrapper.trigger( 'atum-settings-script-runner-done', [ $scriptRunner ] );
				} );
			}

		} );

	}

	/**
	 * Theme selector fields
	 *
	 * @param {JQuery} $element
	 */
    doThemeSelector( $element: JQuery ) {

	    const $formSettingsWrapper: JQuery  = this.$form.find( '.form-settings-wrapper' ),
	          $themeSelectorWrapper: JQuery = this.$form.find( '.theme-selector-wrapper' ),
	          $themeOptions: JQuery         = $themeSelectorWrapper.find( '.selector-container .selector-box img' ),
	          themeSelectedValue: string    = $element.data( 'value' ),
	          resetDefault: number          = $element.data( 'reset' ),
	          $radioInput: JQuery           = this.$form.find( `#${ themeSelectedValue }` ),
	          $resetDefaultColors           = this.$form.find( '.reset-default-colors' );

	    $radioInput.prop( 'checked', true );
	    $themeOptions.removeClass( 'active' );
	    $element.find( 'img' ).addClass( 'active' );
	    $resetDefaultColors.data( 'value', themeSelectedValue );

	    $.ajax( {
		    url       : window[ 'ajaxurl' ],
		    method    : 'POST',
		    data      : {
			    action  : this.settings.get( 'getColorScheme' ),
			    security: this.settings.get( 'colorSchemeNonce' ),
			    theme   : themeSelectedValue,
			    reset   : resetDefault,
		    },
		    beforeSend: () => $formSettingsWrapper.addClass( 'overlay' ),
		    success   : ( response: any ) => {

			    if ( response.success === true ) {

				    for ( let dataKey in response.data ) {
					    ColorPicker.updateColorPicker( this.$form.find( `#atum_${ dataKey }` ), response.data[ dataKey ] );
				    }

				    let title: string = '';

				    if ( themeSelectedValue === 'dark_mode' ) {
					    title = this.settings.get( 'dark' );
				    }
				    else if ( themeSelectedValue === 'hc_mode' ) {
					    title = this.settings.get( 'highContrast' );
				    }
				    else {
					    title = this.settings.get( 'branded' );
				    }

				    this.$form.find( '.section-title h2 span' ).html( title );
				    $formSettingsWrapper.removeClass( 'overlay' );
				    this.$form.find( 'input:submit' ).click();

			    }
			    else {
				    //console.log('Error');
			    }

		    },
	    } );

    }

	/**
	 * Reset default colors
	 *
	 * @param {JQuery} $element
	 */
	doResetDefaultColors( $element: JQuery ) {

		const themeSelectedValue: string    = $element.data( 'value' ),
		      $colorSettingsWrapper: JQuery = this.$settingsWrapper.find( '#atum_setting_color_scheme' ),
		      $colorInputs: JQuery          = $colorSettingsWrapper.find( `input.atum-settings-input[data-display='${ themeSelectedValue }']` );

		$colorInputs.each( ( index: number, elem: Element ) => {
			const $elem: JQuery = $( elem );

			$elem.val( $elem.data( 'default' ) ).change();

		} );
		
	}

	/**
	 * Toggle multi-checkbox panel
	 *
	 * @param {JQuery} $switcher
	 */
	toggleMultiCheckboxPanel( $switcher: JQuery ) {
		const $panel: JQuery = $switcher.closest( 'td' ).find( '.atum-settings-multi-checkbox' );

		$panel.css( 'display', $switcher.is( ':checked' ) ? 'block' : 'none' );
	}

	/**
	 * Multi-checkbox click
	 *
	 * @param {JQuery} $checkbox
	 */
	clickCheckbox( $checkbox: JQuery ) {

		const $wrapper: JQuery = $checkbox.parents( '.atum-multi-checkbox-option' );

		if ( $checkbox.is( ':checked' ) ) {
			$wrapper.addClass( 'setting-checked' );
		}
		else {
			$wrapper.removeClass( 'setting-checked' );
		}

	}

	/**
	 * Init range dateTime pickers
	 */
	initRangeDateTimePicker() {

		const $dateFrom: JQuery = this.$form.find( '.range-datepicker.range-from' ),
		      $dateTo: JQuery   = this.$form.find( '.range-datepicker.range-to' );

		if ( $dateFrom.length && $dateTo.length ) {
			this.dateTimePicker.addDateTimePickers( $dateFrom, { minDate: false, maxDate: new Date() } );
			this.dateTimePicker.addDateTimePickers( $dateTo, { minDate: false } );
		}

		this.dateTimePicker.addDateTimePickers( this.$form.find('.atum-datepicker') );

	}

	/**
	 * Set range dateTime inputs
	 */
	setRangeDateTimeInputs() {

		const $dateFrom: JQuery = this.$form.find( '.range-datepicker.range-from' ),
		      $dateTo: JQuery   = this.$form.find( '.range-datepicker.range-to' ),
		      $field: JQuery    = this.$form.find( '.range-value' ),
		      $checkbox: JQuery = this.$form.find( '.remove-datepicker-range' );

		$field.val( JSON.stringify( {
			checked : $checkbox.is( ':checked' ),
			dateFrom: $dateFrom.val(),
			dateTo  : $dateTo.val(),
		} ) );

	}

	/**
	 * Remove range dateTime picker
	 *
	 * @param {JQuery} $checkbox
	 */
	toggleRangeDateTimeRemove( $checkbox: JQuery ) {

		const $panel: JQuery  = $checkbox.parent().siblings( '.range-fields-block' ),
		      $button: JQuery = $checkbox.parent().siblings( '.tool-runner' );

		$panel.css( 'display', $checkbox.is( ':checked' ) ? 'block' : 'none' );
		$button.text( $checkbox.is( ':checked' ) ? this.settings.get( 'removeRange' ) : this.settings.get( 'removeAll' ) );

	}

	/**
	 * Prepare the file uploaders
	 */
	doFileUploaders() {

		// Enable image uploader with the default options.
		const uploaderOptions: WPMediaModalOptions = {
			library: {
				type: 'image', // We are only using images for now but in the future, perhaps we'll need files also...
			},
		};
		new FileUploader(  this.$settingsWrapper.find( '.atum-file-uploader' ), uploaderOptions, true );

	}

	/**
	 * Prepare the image selectors
	 */
	doImageSelector() {

		$( '.atum-image-selector' ).on( 'change', 'input', ( evt: JQueryEventObject ) => {

			const $imgRadio: JQuery = $( evt.currentTarget ).closest( '.atum-image-radio' );

			$imgRadio.siblings( '.active' ).removeClass( 'active' );
			$imgRadio.addClass( 'active' );

		} );

	}

	/**
	 * Prepare the TinyMCE editors
	 */
	doEditors() {

		if ( window.hasOwnProperty( 'wp' ) && window[ 'wp' ].hasOwnProperty( 'editor' ) ) {

			$( '.atum-settings-editor' ).find( 'textarea' ).each( ( index: number, elem: Element ) => {

				const $textarea: JQuery      = $( elem ),
				      $editorWrapper: JQuery = $textarea.closest( '.atum-settings-editor' );

				let config: any = window[ 'wp' ].editor.getDefaultSettings();

				if ( typeof $editorWrapper.data( 'tiny-mce' ) !== 'undefined' ) {
					config = {
						tinymce: $editorWrapper.data( 'tiny-mce' )
					}
				}

				window[ 'wp' ].editor.initialize( $( elem ).attr( 'id' ), config );

			} );
		}

	}

}