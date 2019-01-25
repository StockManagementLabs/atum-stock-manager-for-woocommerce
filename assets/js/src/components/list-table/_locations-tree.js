/* =======================================
   LOCATIONS TREE FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings'
import Globals from './_globals'
import Tooltip from '../_tooltip'

let LocationsTree = {
	
	locationsSet  : [],
	toSetLocations: [],
	productId     : null,
	
	init() {
		
		let self = this
		
		Globals.$atumList.on('click', '.show-locations', (evt) => {
			evt.preventDefault()
			self.showLocationsPopup($(evt.target))
		})
		
	},
	
	/**
	 * Opens a popup with the locations' tree and allows to edit locations
	 *
	 * @param jQuery $button
	 */
	showLocationsPopup($button) {
		
		let self = this
		this.productId = $button.closest('tr').data('id')
		
		// Open the view popup.
		swal({
			title             : Settings.get('productLocations'),
			html              : '<div id="atum-locations-tree" class="atum-tree"></div>',
			showCancelButton  : false,
			showConfirmButton : true,
			confirmButtonText : Settings.get('editProductLocations'),
			confirmButtonColor: '#00b8db',
			showCloseButton   : true,
			onOpen            : () => {
				self.onOpenViewPopup()
			},
			onClose           : self.onCloseViewPopup,
		})
		// Click on edit: open the edit popup.
		.then( () => {
			self.openEditPopup()
		})
		.catch(swal.noop)
		
	},
	
	/**
	 * Triggers when the view popup opens
	 */
	onOpenViewPopup() {
		
		let self                    = this,
			$locationsTreeContainer = $('#atum-locations-tree')
		
		Tooltip.destroyTooltips();
		
		$.ajax({
			url       : ajaxurl,
			dataType  : 'json',
			method    : 'post',
			data      : {
				action    : 'atum_get_locations_tree',
				token     : Settings.get('nonce'),
				product_id: self.productId,
			},
			beforeSend: () => {
				$locationsTreeContainer.append('<div class="atum-loading" />')
			},
			success   : (response) => {
				
				if (response.success === true) {
					
					$locationsTreeContainer.html(response.data)
					
					// If answer is like <span class="no-locations-set">...  don't put easytree on work.
					// It will remove the span message.
					if (!(response.data.indexOf('no-locations-set') > -1)) {
						
						$locationsTreeContainer.easytree()
						
						// Fill setedLocations
						$('#atum-locations-tree span[class^="cat-item-"], #atum-locations-tree span[class*="cat-item-"]').each( (index, elem) => {
							
							const classList = $(elem).attr('class').split(/\s+/)
							
							$.each(classList, (index, item) => {
								if (item.startsWith('cat-item-')) {
									self.locationsSet.push(item)
								}
							})
							
						})
					}
				
				}
				else {
					$('#atum-locations-tree').html('<h4 class="color-danger">' + response.data + '</h4>')
				}
				
			},
		})
		
	},
	
	/**
	 * Triggers when the view popup is closed
	 */
	onCloseViewPopup() {
		Tooltip.addTooltips()
	},
	
	/**
	 * Opens the edit popup
	 */
	openEditPopup() {
		
		let self = this
		
		swal({
			title              : Settings.get('editProductLocations'),
			html               : '<div id="atum-locations-tree" class="atum-tree"></div>',
			text               : Settings.get('textToShow'),
			confirmButtonText  : Settings.get('saveButton'),
			confirmButtonColor : '#00b8db',
			showCloseButton    : true,
			showCancelButton   : true,
			showLoaderOnConfirm: true,
			onOpen             : () => {
				self.onOpenEditPopup()
			},
			preConfirm         : () => {
				self.saveLocations()
			},
		})
		.then(self.onCloseEditPopup)
		.catch(swal.noop)
		
	},
	
	/**
	 * Triggers when the edit popup opens
	 */
	onOpenEditPopup() {
		
		let self                    = this,
		    $locationsTreeContainer = $('#atum-locations-tree')
		
		$.ajax({
			url       : ajaxurl,
			dataType  : 'json',
			method    : 'post',
			data      : {
				action    : 'atum_get_locations_tree',
				token     : Settings.get('nonce'),
				product_id: -1, // Send -1 to get all the terms.
			},
			beforeSend: () => {
				$locationsTreeContainer.append('<div class="atum-loading" />')
			},
			success   : (response) => {
				
				if (response.success === true) {
					
					$locationsTreeContainer.html(response.data);
					$locationsTreeContainer.easytree()
					
					// Add instructions alert.
					$locationsTreeContainer.append('<div class="alert alert-primary"><i class="atmi-info"></i> ' + Settings.get('editLocationsInfo') + '</div>')
					
					self.bindEditTreeEvents($locationsTreeContainer)
					
				}
			},
		})
		
	},
	
	/**
	 * Triggers when the view popup is closed
	 */
	onCloseEditPopup() {
		
		swal({
			title             : Settings.get('done'),
			type              : 'success',
			text              : Settings.get('locationsSaved'),
			confirmButtonText : Settings.get('ok'),
			confirmButtonColor: '#00b8db',
		})
		
	},
	
	/**
	 * Saves the checked locations
	 *
	 * @return {Promise}
	 */
	saveLocations() {
		
		let self = this
		
		return new Promise( (resolve, reject) => {
			
			// ["cat-item-40", "cat-item-39"] -> [40, 39]
			const toSetTerms = self.toSetLocations.map( (elem) => {
				return parseInt(elem.substring(9));
			})
			
			$.ajax({
				url       : ajaxurl,
				dataType  : 'json',
				method    : 'post',
				data      : {
					action    : 'atum_set_locations_tree',
					token     : Settings.get('nonce'),
					product_id: self.productId,
					terms     : toSetTerms,
				},
				success   : (response) => {
					
					if (response.success === true) {
						resolve()
					}
					else {
						reject()
					}
				},
			})
			
		})
		
	},
	
	/**
	 * Bind the events for the editable tree
	 */
	bindEditTreeEvents($locationsTreeContainer) {
		
		let self = this
		
		this.toSetLocations = this.locationsSet;
		
		// When clicking on link or icon, set node as checked.
		$locationsTreeContainer.find('a, .easytree-icon').click( (evt) => {
			
			evt.preventDefault()
			
			let $this     = $(evt.target),
			    catItem   = '',
			    classList = $this.closest('.easytree-node').attr('class').split(/\s+/)
			
			$.each(classList, (index, item) => {
				if (item.lastIndexOf('cat-item-', 0) === 0) {
					catItem = item
					
					return false
				}
			})
			
			$('.' + catItem).toggleClass('checked')
			
			if ($('.' + catItem).hasClass('checked')) {
				self.toSetLocations.push(catItem)
			}
			else {
				const pos = self.toSetLocations.indexOf(catItem)
				
				if (pos > -1) {
					self.toSetLocations.splice(pos, 1)
				}
			}
			
		})
		
		// Set class checked the actual values on load.
		$locationsTreeContainer.find('span[class^="cat-item-"], span[class*="cat-item-"]').each( (index, elem) => {
			
			const classList = $(elem).attr('class').split(/\s+/)
			
			$.each(classList, (index, item) => {
				
				if (item.startsWith('cat-item-') && $.inArray(item, self.locationsSet) !== -1) {
					$('.' + item).addClass('checked')
				}
				
			})
			
		})
		
	},
	
}

module.exports = LocationsTree