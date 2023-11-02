/* =======================================
   MARKETING POPUP
   ======================================= */

import Settings from '../config/_settings';
import Swal from 'sweetalert2';

// Interfaces.
interface MPopupText {
	text: string;
	text_color: string;
	text_size: string;
	text_align: string;
	dash_text_align?: string;
	padding?: string;
}

interface MPopupLabel {
	text: string;
	text_color: string;
	background: string;
}

interface MPopupButton {
	text: string;
	url: string;
	class: string;
	css: string;
}

interface MPopupFooterNotice {
	text: string;
	bg_color: string;
}

interface MPopupSettings {
	background: string;
	title: MPopupText;
	additionalClass?: string;
	description: MPopupText;
	version?: MPopupLabel;
	buttons?: MPopupButton[];
	hoverButtons?: string;
	images: any;
	footerNotice?: MPopupFooterNotice;
	transient_key: string;
}


export default class MarketingPopup {

	key: string = '';
	
	constructor(
		private settings: Settings
	) {

		// Do not show the marketing modal if there are ATUM admin modals to show.
		if (
			! window.hasOwnProperty( 'atum' ) ||
			( window.hasOwnProperty( 'atum' ) && ! window[ 'atum' ].hasOwnProperty( 'AdminModal' ) )
		) {
			this.getPopupInfo();
		}

	}

	/**
	 * Get the marketing popup info via Ajax
	 */
	getPopupInfo() {
		
		$.ajax({
			url     : window[ 'ajaxurl' ],
			dataType: 'json',
			method  : 'post',
			data    : {
				action  : 'atum_get_marketing_popup_info',
				security: this.settings.get( 'nonce' ),
			},
			success : async ( response: any ) => {

				if ( response.success === true ) {

					const popupSettings: MPopupSettings = response.data,
					      descriptionColor: string      = popupSettings.description.text_color ? `color:${ popupSettings.description.text_color };` : '',
					      descriptionFontSize: string   = popupSettings.description.text_size ? `font-size:${ popupSettings.description.text_size };` : '',
					      descriptionAlign: string      = popupSettings.description.text_align ? `text-align:${ popupSettings.description.text_align };` : '',
					      descriptionPadding: string    = popupSettings.description.padding ? `padding:${ popupSettings.description.padding };` : '',
					      description: string           = `<p style="${ descriptionColor + descriptionFontSize + descriptionAlign + descriptionPadding }">${ popupSettings.description.text }</p>`,
					      titleColor: string            = popupSettings.title.text_color ? `color:${ popupSettings.title.text_color };` : '',
					      titleFontSize: string         = popupSettings.title.text_size ? `font-size:${ popupSettings.title.text_size };` : '',
					      titleAlign: string            = popupSettings.title.text_align ? `text-align:${ popupSettings.title.text_align };` : '',
					      hoverButtons                  = popupSettings.hoverButtons || '',
					      imageTopLeft: string          = popupSettings.images.top_left,
					      footerNoticeStyle: string     = popupSettings.footerNotice.bg_color ? ` style="background-color:${ popupSettings.footerNotice.bg_color };"` : '',
					      footerNotice: string          = popupSettings.footerNotice.text ? `<div class="footer-notice"${ footerNoticeStyle }>${ popupSettings.footerNotice.text }</div>` : '';

					this.key = popupSettings.transient_key;

					let logo: string              = `<img class="mp-logo" src="${ popupSettings.images.logo }">`,
					    versionColor: string      = '',
					    versionBackground: string = '',
					    version: string           = '',
					    buttons: string           = '';

					if ( popupSettings.images.hasOwnProperty( 'logo_css' ) && popupSettings.images.logo_css ) {
						logo = logo.replace( '>', ` style="${ popupSettings.images.logo_css }">` );
					}

					if ( popupSettings.version && Object.keys( popupSettings.version ).length && popupSettings.version.text ) {
						versionColor = popupSettings.version.text_color ? `color:${ popupSettings.version.text_color };` : '';
						versionBackground = popupSettings.version.background ? `background:${ popupSettings.version.background };` : '';
						version = `<span class="version" style="${ versionBackground + versionColor }">${ popupSettings.version.text }</span>`;
					}
					
					const title: string = popupSettings.title.text ? `<h1 style="${ titleColor + titleFontSize + titleAlign }"><span>${ popupSettings.title.text + version }</span></h1>` : '',
					      additionalClass: string = ` ${popupSettings.additionalClass}` ?? '';
					
					// Add buttons.
					if ( popupSettings.buttons && popupSettings.buttons.length ) {

						if ( hoverButtons ) {
							$( hoverButtons ).appendTo( 'body' );
						}

						popupSettings.buttons.forEach( ( button: MPopupButton ) => {
							buttons += `<button data-url="${ button.url }" class="${ button.class } popup-button" style="${ button.css }">${ button.text }</button>`;
						} );

					}

					// Redirect to button URL.
					$( 'body' ).on( 'click', '.swal2-container button[data-url]', ( evt: JQueryEventObject ) => {
						evt.preventDefault();
						window.open( $( evt.currentTarget ).data( 'url' ), '_blank' );
					} );

					// By using await, we can queue multiple Swals on the same page.
					// NOTE: Make sure to not add any other code after this or won't be executed until the swal is closed.
					await Swal.fire( {
						width            : 520,
						padding          : null,
						customClass      : {
							popup: `marketing-popup${additionalClass}`,
						},
						background       : popupSettings.background,
						showCloseButton  : true,
						showConfirmButton: false,
						html             : logo + title + description + buttons + footerNotice,
						imageUrl         : imageTopLeft,
						allowEscapeKey   : false,
						allowOutsideClick: false,
						allowEnterKey    : false,
					} ).then( () => {

						// Hide popup for this user when clicking the close button.
						this.hideMarketingPopup();

					} );
					
				}
			},
		});
		
	}

	/**
	 * Hide the modal and save the closed state for the current user
	 */
	hideMarketingPopup() {

		$.ajax( {
			url     : window[ 'ajaxurl' ],
			dataType: 'json',
			method  : 'post',
			data    : {
				action      : 'atum_hide_marketing_popup',
				security    : this.settings.get( 'nonce' ),
				transientKey: this.key,
			},
		} );

	}
	
}