/* =======================================
   FILTERS FOR LIST TABLES
   ======================================= */

import Settings from '../../config/_settings';
import Globals from './_globals';
import ListTable from './_list-table';
import Router from './_router';
import Tooltip from '../_tooltip';
import DateTimePicker from '../_date-time-picker';
import Utils from '../../utils/_utils';

let Filters = {
	
	timer: null,
	
	init() {
		
		let self = this;
		
		//
		// Ajax filters.
		// -------------
		if (Settings.get('ajaxFilter') === 'yes') {
			
			Globals.$atumList
				
				// Dropdown filters.
				.on('change', '.dropdown_product_cat, .dropdown_product_type, .dropdown_supplier, .dropdown_extra_filter', (evt) => {
					self.keyUp(evt);
				})
				
				// Search filter.
				.on('keyup paste search input', '.atum-post-search', (evt) => {
					
					let searchColumnBtnVal = Globals.$searchColumnBtn.data('value'),
					    $searchInputVal    = $(evt.target).val();
					
					Utils.delay( () => {
						self.pseudoKeyUpAjax(searchColumnBtnVal, $searchInputVal);
					}, 500);
					
				})
				
				// Pagination input changes.
				.on('keyup paste', '.current-page', (evt) => {
					self.keyUp(evt);
				});
			
			if (Settings.get('searchDropdown') === 'yes') {
				
				Globals.$searchColumnBtn.on('atum-search-column-data-changed', (evt) => {
					self.pseudoKeyUpAjax($(evt.target).data('value'), Globals.$searchInput.val());
				})
				
			}
			
		}
		
		//
		// Non-ajax filters.
		// -----------------
		else {
			
			let $searchSubmitBtn = Globals.$searchInput.siblings('.search-submit');
			
			if (!Globals.$searchInput.val()) {
				$searchSubmitBtn.prop('disabled', true);
			}
			
			// If s is empty, search-submit must be disabled and ?s removed.
			// If s and searchColumnBtnVal have values, then we can push over search.
			Globals.$searchInput.on('input', (evt) => {
				
				let searchColumnBtnVal = Globals.$searchColumnBtn.data('value'),
				    inputVal           = $(evt.target).val();
				
				if (!inputVal) {
					
					$searchSubmitBtn.prop('disabled', true);
					
					if (inputVal != $.address.parameter('s')) {
						$.address.parameter('s', '');
						$.address.parameter('search_column', '');
						Router.updateHash(); // Force clean search.
					}
					
				}
				// Uncaught TypeError: Cannot read property 'length' of undefined (redundant check fails).
				else if ( typeof searchColumnBtnVal !== 'undefined' && searchColumnBtnVal.length > 0) {
					$searchSubmitBtn.prop('disabled', false);
				}
				else if (inputVal) {
					$searchSubmitBtn.prop('disabled', false);
				}
				
			});
			
			// TODO on init address, check s i search_column values, and disable or not
			// When a search_column changes, set ?s and ?search_column if s has value. If s is empty, clean this two parameters.
			if (Settings.get('searchDropdown') === 'yes') {
				
				// TODO: IS THIS WORKING? IS NOT ONLY FOR AJAX FILTERS?
				Globals.$searchColumnBtn.on('atum-search-column-data-changed', (evt) => {
					
					let searchInputVal     = Globals.$searchInput.val(),
					    searchColumnBtnVal = $(evt.target).data('value');
					
					if (searchInputVal.length > 0) {
						$.address.parameter('s', searchInputVal);
						$.address.parameter('search_column', searchColumnBtnVal);
						self.keyUp(evt);
					}
					// Force clean s when required.
					else {
						$.address.parameter('s', '');
						$.address.parameter('search_column', '');
					}
					
				});
				
			}
			
			Globals.$atumList.on('click', '.search-category, .search-submit', () => {
				
				let searchInputVal     = Globals.$searchInput.val(),
				    searchColumnBtnVal = Globals.$searchColumnBtn.data('value');
				
				$searchSubmitBtn.prop('disabled', typeof searchColumnBtnVal !== 'undefined' && searchColumnBtnVal.length === 0 ? true : false);
				
				if (searchInputVal.length > 0) {
					$.address.parameter('s', Globals.$searchInput.val());
					$.address.parameter('search_column', Globals.$searchColumnBtn.data('value'));
					
					Router.updateHash();
				}
				// Force clean s when required.
				else {
					$.address.parameter('s', '');
					$.address.parameter('search_column', '');
					self.updateHash();
				}
				
			});
			
		}
		
		//
		// Events common to all filters.
		// -----------------------------
		Globals.$atumList
		
			//
			// Reset Filters button.
			// ---------------------
			.on('click', '.reset-filters', () => {
				
				Tooltip.destroyTooltips();
				
				// TODO reset s and column search
				$.address.queryString('');
				Globals.$searchInput.val('');
				
				if (Settings.get('searchDropdown') === 'yes' && Globals.$searchColumnBtn.data('value') !== 'title') {
					Globals.$searchColumnBtn.trigger('atum-search-column-set-data', ['title', $('#search_column_dropdown').data('product-title') + ' <span class="caret"></span>']);
				}
				
				ListTable.updateTable();
				$(this).addClass('hidden');
				
			})
			
			.on('atum-table-updated', this.addDateSelectorFilter);
		
		
		//
		// Add date selector filter.
		// -------------------------
		this.addDateSelectorFilter();
		
	},
	
	/**
	 * Search box keyUp event callback
	 *
	 * @param {Object}  evt       The event data object.
	 * @param {Boolean} noTimer   Whether to delay before triggering the update (used for autosearch).
	 */
	keyUp(evt, noTimer) {
		
		let self           = this,
		    delay          = 500,
		    searchInputVal = Globals.$searchInput.val();
		
		noTimer = noTimer || false;
		
		/*
		 * If user hits enter, we don't want to submit the form.
		 * We don't preventDefault() for all keys because it would also prevent to get the page number!
		 *
		 * Also, if the 's' param is empty, we don't want to search anything.
		 */
		if (evt.type !== 'keyup' || searchInputVal.length > 0) {
			
			if (13 === evt.which) {
				evt.preventDefault();
			}
			
			if (noTimer) {
				self.updateHash();
			}
			else {
				/*
				 * Now the timer comes to use: we wait half a second after
				 * the user stopped typing to actually send the call. If
				 * we don't, the keyup event will trigger instantly and
				 * thus may cause duplicate calls before sending the intended value.
				 */
				clearTimeout(self.timer);
				
				self.timer = setTimeout( () => {
					// TODO force ?vars on updateHash when ajax
					Router.updateHash();
				}, delay);
				
			}
			
		}
		else {
			evt.preventDefault();
		}
		
	},
	
	pseudoKeyUpAjax(searchColumnBtnVal, searchInputVal) {
		
		if (searchInputVal.length === 0) {
			
			if (searchInputVal != $.address.parameter('s')) {
				$.address.parameter('s', '');
				$.address.parameter('search_column', '');
				Router.updateHash(); // Force clean search.
			}
			
		}
		else if (typeof searchColumnBtnVal != 'undefined' && searchColumnBtnVal.length > 0) {
			$.address.parameter('s', searchInputVal);
			$.address.parameter('search_column', searchColumnBtnVal);
			Router.updateHash();
		}
		else if (searchInputVal.length > 0) {
			$.address.parameter('s', searchInputVal);
			Router.updateHash();
		}
		
	},
	
	/**
	 * Add the date selector filter
	 */
	addDateSelectorFilter() {
		
		let self          = this,
		    linkedFilters = Settings.get('dateSelectorFilters'),
		    $dateSelector = Globals.$atumList.find('.date-selector'),
		    dateFromVal   = $.address.parameter('date_from') ? $.address.parameter('date_from') : $('.date_from').val(),
		    dateToVal     = $.address.parameter('date_to') ? $.address.parameter('date_to') : $('.date_to').val();
		
		if ( ! dateToVal ) {
			
			let today = new Date(),
			    dd    = today.getDate(),
			    mm    = today.getMonth() + 1, // January is 0.
			    yyyy  = today.getFullYear();
			
			if (dd < 10) {
				dd = '0' + dd;
			}
			
			if (mm < 10) {
				mm = '0' + mm;
			}
			
			dateToVal = yyyy + '-' + mm + '-' + dd;
			
		}
		
		$dateSelector
			
			.on('select2:open', (evt) => {
				
				const $select = $(evt.target);
			
				if ( linkedFilters.indexOf($select.val()) !== -1 ) {
					$select.val('');
				}
				
			})
		
			.on('select2:select', (evt) => {
			
				if ( linkedFilters.indexOf($(evt.target).val()) !== -1 ) {
					
					const popupClass = 'filter-range-dates-modal';
					
					swal({
						customClass    : popupClass,
						width          : 440,
						showCloseButton: true,
						title          : `<h1 class="title">${ Settings.get('setTimeWindow') }</h1><span class="sub-title">${ Settings.get('selectDateRange') }</span>`,
						html           : `
							<div class="input-date">
								<label for="date_from">${ Settings.get('from') }</label><br/>
								<input type="text" placeholder="Beginning" class="date-picker date_from" name="date_from" id="date_from" maxlength="10" value="${ dateFromVal }">
							</div>
							<div class="input-date">
								<label for="date_to">${ Settings.get('to') }</label><br/>
								<input type="text" class="date-picker date_to" name="date_to" id="date_to" maxlength="10" value="${ dateToVal }">
							</div>
							<button class="btn btn-warning apply">${ Settings.get('apply') }</button>
						`,
						showConfirmButton: false,
						onOpen           : () => {
							
							// Init date time pickers.
							DateTimePicker.addDateTimePickers($('.date-picker'));
							
							$('.' + popupClass).find('.swal2-content .apply').on('click', () => {
								self.keyUp(evt);
								swal.close();
							});
							
							$('.' + popupClass).find('.swal2-close').on('click', () => {
								$('.' + popupClass).find('.date_to, .date_from').val('');
							});
						
						},
						onClose: () => {
							if ( Settings.get('ajaxFilter') === 'yes' ) {
								self.keyUp(evt);
							}
						},
						
					})
					.catch(swal.noop);
					
				}
				else {
					$dateSelector.val('');
				}
				
			})
		
	},
	
}

module.exports = Filters;