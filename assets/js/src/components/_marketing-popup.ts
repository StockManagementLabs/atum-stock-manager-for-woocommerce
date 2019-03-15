/* =======================================
   MARKETING POPUP
   ======================================= */

import Settings from '../config/_settings';

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

interface MPopupSettings {
	background: string;
	title: MPopupText;
	description: MPopupText;
	version?: MPopupLabel;
	buttons?: MPopupButton[];
	images: any;
	transient_key: string;
}


export default class MarketingPopup {
	
	swal: any = window['swal'];
	
	constructor(
		private settings: Settings
	) {
	
		this.getPopupInfo();
		
	}
	
	getPopupInfo() {
		
		$.ajax({
			url       : window['ajaxurl'],
			dataType  : 'json',
			method    : 'post',
			data      : {
				action    : 'atum_get_marketing_popup_info',
				token     : this.settings.get('nonce'),
			},
			success   : (response: any) => {
				
				if (response.success === true) {
					
					let popupSettings: MPopupSettings = response.data;
					
					let descriptionColor: string    = popupSettings.description.text_color ? `color:${ popupSettings.description.text_color };` : '',
					    descriptionFontSize: string = popupSettings.description.text_size ? `font-size:${ popupSettings.description.text_size };` : '',
					    descriptionAlign: string    = popupSettings.description.text_align ? `text-align:${ popupSettings.description.text_align };` : '',
					    descriptionPadding: string  = popupSettings.description.padding ? `padding:${ popupSettings.description.padding };` : '',
					    description: string         = `<p data-transient-key="${ popupSettings.transient_key }" style="${ descriptionColor + descriptionFontSize + descriptionAlign + descriptionPadding }">${ popupSettings.description.text }</p>`,
					    versionColor: string        = '',
					    versionBackground: string   = '',
					    version: string             = '',
					    titleColor: string          = popupSettings.description.text_color ? `color:${ popupSettings.title.text_color };` : '',
					    titleFontSize: string       = popupSettings.description.text_size ? `font-size:${ popupSettings.title.text_size };` : '',
					    titleAlign: string          = popupSettings.description.text_align ? `text-align:${ popupSettings.title.text_align };` : '',
					    title: string               = `<h1 style="${ titleColor + titleFontSize + titleAlign }">${ popupSettings.title.text }</h1>`,
					    buttons: string             = '',
					    imageTopLeft: string        = popupSettings.images.top_left,
					    logo: string                = `<img class="mp-logo" src="${ popupSettings.images.logo }">`;
					
					if (popupSettings.version && Object.keys(popupSettings.version).length) {
						versionColor        = popupSettings.version.text_color ? `color:${ popupSettings.version.text_color };` : '';
						versionBackground   = popupSettings.version.background ? `background:${ popupSettings.version.background };` : '';
						version             = `<span class="version" style="${ versionBackground + versionColor }">${ popupSettings.version.text }</span>`;
					}
					
					// Add buttons.
					if (popupSettings.buttons && popupSettings.buttons.length) {
						popupSettings.buttons.forEach( (button: MPopupButton) => {
							buttons += `<button data-url="${ button.url }" class="${ button.class } popup-button" style="${ button.css }">${ button.text }</button>`
						});
					}
					
					this.swal({
						width             : 520,
						padding           : null,
						customClass       : 'marketing-popup',
						background        : popupSettings.background,
						showCloseButton   : true,
						showConfirmButton : false,
						html              : logo + title + version + description + buttons,
						imageUrl          : imageTopLeft,
					}).catch(this.swal.noop);
					
					// Redirect to button url
					$('.popup-button').on('click', (evt: JQueryEventObject) => {
						evt.preventDefault();
						window.open( $(evt.currentTarget).data('url'), '_blank' );
					});
					
					// Hide popup when click un close button for this user
					$('.marketing-popup .swal2-close').on('click',() => {
						this.hideMarketingPopup( $('.swal2-content p').data('transient-key') );
					});
					
				}
			},
		});
		
	}
	
	hideMarketingPopup(transientKey: string) {
		
		$.ajax({
			url       : window['ajaxurl'],
			dataType  : 'json',
			method    : 'post',
			data      : {
				action       : 'atum_hide_marketing_popup',
				token        : this.settings.get('nonce'),
				transientKey : transientKey,
			},
		});
		
	}
	
}