/* =======================================
   LOCATIONS TREE FOR LIST TABLES
   ======================================= */

/**
 * Third party plugins
 */
import '../../../vendor/jquery.easytree.min';         // This has no package available for npm

import Settings from '../../config/_settings';
import Globals from './_globals';
import Tooltip from '../_tooltip';

export default class LocationsTree {
	
	locationsSet: string[] = [];
	toSetLocations: string[] = [];
	productId: number = null;
	swal: any = window['swal'];
	
	constructor(
		private settings: Settings,
		private globals: Globals,
		private tooltip: Tooltip
	) {
		
		this.globals.$atumList.on('click', '.show-locations', (evt: JQueryEventObject) => {
			evt.preventDefault();
			this.showLocationsPopup($(evt.currentTarget));
		})
		
	}
	
	/**
	 * Opens a popup with the locations' tree and allows to edit locations
	 *
	 * @param jQuery $button
	 */
	showLocationsPopup($button: JQuery) {
		
		this.productId = $button.closest('tr').data('id');
		
		// Open the view popup.
		this.swal({
			title             : this.settings.get('productLocations'),
			html              : '<div id="atum-locations-tree" class="atum-tree"></div>',
			showCancelButton  : false,
			showConfirmButton : true,
			confirmButtonText : this.settings.get('editProductLocations'),
			confirmButtonColor: '#00b8db',
			showCloseButton   : true,
			onOpen            : () => this.onOpenViewPopup(),
			onClose           : () => this.onCloseViewPopup(),
		})
		// Click on edit: open the edit popup.
		.then( () => this.openEditPopup() )
		.catch(this.swal.noop);
		
	}
	
	/**
	 * Triggers when the view popup opens
	 */
	onOpenViewPopup() {
		
		let $locationsTreeContainer: JQuery = $('#atum-locations-tree');
		
		this.tooltip.destroyTooltips();
		
		$.ajax({
			url       : window['ajaxurl'],
			dataType  : 'json',
			method    : 'post',
			data      : {
				action    : 'atum_get_locations_tree',
				token     : this.settings.get('nonce'),
				product_id: this.productId,
			},
			beforeSend: () => $locationsTreeContainer.append('<div class="atum-loading" />'),
			success   : (response: any) => {
				
				if (response.success === true) {
					
					$locationsTreeContainer.html(response.data);
					
					// If answer is like <span class="no-locations-set">...  don't put easytree on work.
					// It will remove the span message.
					if (!(response.data.indexOf('no-locations-set') > -1)) {
						
						(<any>$locationsTreeContainer).easytree();
						
						// Fill locationsSet.
						$('#atum-locations-tree span[class^="cat-item-"], #atum-locations-tree span[class*="cat-item-"]').each( (index: number, elem: Element) => {
							
							const classList = $(elem).attr('class').split(/\s+/);
							
							$.each(classList, (index: number, item: string) => {
								if (item.startsWith('cat-item-')) {
									this.locationsSet.push(item);
								}
							});
							
						});
					}
				
				}
				else {
					$('#atum-locations-tree').html('<h4 class="color-danger">' + response.data + '</h4>');
				}
				
			},
		});
		
	}
	
	/**
	 * Triggers when the view popup is closed
	 */
	onCloseViewPopup() {
		this.tooltip.addTooltips();
	}
	
	/**
	 * Opens the edit popup
	 */
	openEditPopup() {
		
		this.swal({
			title              : this.settings.get('editProductLocations'),
			html               : '<div id="atum-locations-tree" class="atum-tree"></div>',
			text               : this.settings.get('textToShow'),
			confirmButtonText  : this.settings.get('saveButton'),
			confirmButtonColor : '#00b8db',
			showCloseButton    : true,
			showCancelButton   : true,
			showLoaderOnConfirm: true,
			onOpen             : () => this.onOpenEditPopup(),
			preConfirm         : () => this.saveLocations(),
		})
		.then( () => this.onCloseEditPopup() )
		.catch(this.swal.noop);
		
	}
	
	/**
	 * Triggers when the edit popup opens
	 */
	onOpenEditPopup() {
		
		let $locationsTreeContainer: JQuery = $('#atum-locations-tree');
		
		$.ajax({
			url       : window['ajaxurl'],
			dataType  : 'json',
			method    : 'post',
			data      : {
				action    : 'atum_get_locations_tree',
				token     : this.settings.get('nonce'),
				product_id: -1, // Send -1 to get all the terms.
			},
			beforeSend: () => $locationsTreeContainer.append('<div class="atum-loading" />'),
			success   : (response: any) => {
				
				if (response.success === true) {
					
					$locationsTreeContainer.html(response.data);
					(<any>$locationsTreeContainer).easytree();
					
					// Add instructions alert.
					$locationsTreeContainer.append('<div class="alert alert-primary"><i class="atmi-info"></i> ' + this.settings.get('editLocationsInfo') + '</div>');
					
					this.bindEditTreeEvents($locationsTreeContainer);
					
				}
			},
		});
		
	}
	
	/**
	 * Triggers when the view popup is closed
	 */
	onCloseEditPopup() {
		
		this.swal({
			title             : this.settings.get('done'),
			type              : 'success',
			text              : this.settings.get('locationsSaved'),
			confirmButtonText : this.settings.get('ok'),
			confirmButtonColor: '#00b8db',
		});
		
	}
	
	/**
	 * Saves the checked locations
	 *
	 * @return Promise
	 */
	saveLocations(): Promise<any> {
		
		return new Promise( (resolve: Function, reject: Function) => {
			
			// ["cat-item-40", "cat-item-39"] -> [40, 39]
			const toSetTerms = this.toSetLocations.map( (elem: string) => {
				return parseInt(elem.substring(9));
			});
			
			$.ajax({
				url       : window['ajaxurl'],
				dataType  : 'json',
				method    : 'post',
				data      : {
					action    : 'atum_set_locations_tree',
					token     : this.settings.get('nonce'),
					product_id: this.productId,
					terms     : toSetTerms,
				},
				success   : (response: any) => {
					
					if (response.success === true) {
						resolve();
					}
					else {
						reject();
					}
					
				},
			});
			
		});
		
	}
	
	/**
	 * Bind the events for the editable tree
	 */
	bindEditTreeEvents($locationsTreeContainer: JQuery) {
		
		this.toSetLocations = this.locationsSet;
		
		// When clicking on link or icon, set node as checked.
		$locationsTreeContainer.find('a, .easytree-icon').click( (evt: JQueryEventObject) => {
			
			evt.preventDefault();
			
			let $this: JQuery       = $(evt.currentTarget),
			    catItem: string     = '',
			    classList: string[] = $this.closest('.easytree-node').attr('class').split(/\s+/);
			
			$.each(classList, (index: number, item: string) => {
				if (item.lastIndexOf('cat-item-', 0) === 0) {
					catItem = item;
					
					return false;
				}
			});
			
			const $catItme: JQuery = $('.' + catItem);
			
			$catItme.toggleClass('checked');
			
			if ($catItme.hasClass('checked')) {
				this.toSetLocations.push(catItem);
			}
			else {
				
				const pos: number = this.toSetLocations.indexOf(catItem);
				
				if (pos > -1) {
					this.toSetLocations.splice(pos, 1);
				}
				
			}
			
		});
		
		// Set class checked the actual values on load.
		$locationsTreeContainer.find('span[class^="cat-item-"], span[class*="cat-item-"]').each( (index: number, elem: Element) => {
			
			const classList: string[] = $(elem).attr('class').split(/\s+/);
			
			$.each(classList, (index: number, className: string) => {
				
				if (className.startsWith('cat-item-') && $.inArray(className, this.locationsSet) > -1) {
					$('.' + className).addClass('checked');
				}
				
			});
			
		});
		
	}
	
}
