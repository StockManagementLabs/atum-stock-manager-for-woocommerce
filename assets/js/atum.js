/**
 * Atum Stock Management
 *
 * @copyright Stock Management Labs (c) 2017
 * @since 0.0.1
 */

(function ($) {
	'use strict';
	
	$(function () {
		
		//-------------------
		// Stock Central List
		//-------------------
		var postTypeTableAjax = '';
		
		$('.atum-list-wrapper').each(function () {
			
			var $listWrapper      = $(this),
			    $atumTable        = $listWrapper.find('.atum-list-table'),
			    postsList         = $listWrapper.find('#the-list'),
			    $inputSelectedIds = $listWrapper.find('.atum_selected_ids'),
			    $inputPerPage     = $listWrapper.parent().siblings('#screen-meta').find('#products_per_page'),
			    $search           = $listWrapper.find('.atum-post-search'),
			    ajaxSearchEnabled = atumListTable.ajaxfilter || 'yes',
			    jScrollApi,
			    $scrollPane,
			    timer;
			
			var stockCentralTable = {
				
				/**
				 * Register our triggers
				 *
				 * We want to capture clicks on specific links, but also value change in
				 * the pagination input field. The links contain all the information we
				 * need concerning the wanted page number or ordering, so we'll just
				 * parse the URL to extract these variables.
				 *
				 * The page number input is trickier: it has no URL so we have to find a
				 * way around. We'll use the hidden inputs added in $inputSelectedIds
				 * to recover the ordering variables, and the default paged input added
				 * automatically by WordPress.
				 */
				init: function () {
					
					var self = this;
					
					// Add the table scrollbar
					this.addScrollBar();
					
					$(window).resize(function() {
						
						if ($scrollPane && $scrollPane.length) {
							
							var vwWidth        = $(this).width(),
							    isJsPaneActive = typeof $scrollPane.data('jsp') !== 'undefined';
							
							// On mobile version, we don't need scrollbars
							if (vwWidth < 782 && isJsPaneActive) {
								jScrollApi.destroy();
							}
							// Instantiate the JScrollPane again if was removed while resizing the window
							else if (vwWidth >= 782 && !isJsPaneActive) {
								self.addScrollBar();
							}
							// Reinitialize to adapt to the screen width
							else if (isJsPaneActive) {
								jScrollApi.reinitialise();
							}
						}
						
					}).resize();
					
					// Add tooltips
					this.tooltip();
					
					// Hide/Show/colspan column groups
					$('#adv-settings .metabox-prefs input').change(function () {
						$listWrapper.find('thead .group th').each(function () {
							
							var $this = $(this),
							    //these th only have one class
							    cols  = $listWrapper.find('thead .col-' + $this.attr('class') + ':visible').length;
							
							if (cols) {
								$this.show().attr('colspan', cols)
							}
							else {
								$this.hide();
							}
						});
					});
					
					// Pagination links, sortable link
					$listWrapper.on('click', '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a, .subsubsub a', function (e) {
						
						e.preventDefault();
						
						// Simple way: use the URL to extract our needed variables
						var query = this.search.substring(1),
						    $this = $(this),
						    $elem = $this.closest('.subsubsub');
						
						if (!$elem.length) {
							$elem = $this.closest('.tablenav-pages ');
							if (!$elem.length) {
								
								var $group = '.' + $this.find('span[class*=col-]').attr('class').replace('col-', '');
								$elem = $this.closest('.wp-list-table').find($group);
							}
						}
						
						var data = {
							paged   : self.__query(query, 'paged') || '1',
							order   : self.__query(query, 'order') || 'desc',
							orderby : self.__query(query, 'orderby') || 'date',
							v_filter: self.__query(query, 'v_filter') || '',
							s       : $search.val() || ''
						};
						
						self.update(data, $elem);
					});
					
					// Ajax filters binding
					if (ajaxSearchEnabled === 'yes') {
						
						$listWrapper.on('keyup', '.atum-post-search', function (e) {
							self.keyUp(e, $(this).closest('.search-box'));
						});
						
						$listWrapper.on('change', '#filter-by-date, .dropdown_product_cat, #dropdown_product_type', function (e) {
							self.keyUp(e, $(this).closest('.actions'));
						});
					}
					// Non-ajax filters binding
					else {
						
						$listWrapper.on('click', '.search-category, .search-submit', function () {
							
							var page      = $listWrapper.find('.current-page').val() || 1,
							    data      = {
								    paged   : parseInt(page),
								    order   : atumListTable.order || 'desc',
								    orderby : atumListTable.orderby || 'date',
								    v_filter: $listWrapper.find('.subsubsub a.current').attr('id') || '',
								    s       : $search.val() || ''
							    },
							    $this     = $(this),
							    elemClass = ($this.is('.search-category')) ? '.actions' : '.search-box';
							
							stockCentralTable.update(data, $this.closest(elemClass));
							
						});
						
					}
					
					// Pagination text box
					$listWrapper.on('keyup', '.current-page', function (e) {
						self.keyUp(e, $(this).closest('.tablenav-pages'));
					});
					
					// Variation products expanding/collapsing
					$listWrapper.on('click', '.product-type.has-child', function() {
						
						var typeClass      = $(this).hasClass('variable') ? 'variable' : 'group',
						    $expandebleRow = $(this).closest('tr').toggleClass('expanded ' + typeClass),
						    $nextRow       = $expandebleRow.next();
						
						do {
							$nextRow.toggle();
							$nextRow = $nextRow.next();
						} while ( $nextRow.hasClass('variation') || $nextRow.hasClass('grouped') );
						
						// Reload the scrollbar
						self.reloadScrollbar();
						
					});
					
					// Checkbox columns
					// pro version
					/*postsList.on('change', 'input[type=checkbox]', function(){
					 
					 var selectedIds =  $inputSelectedIds.val(),
					 currentId = $(this).val();
					 
					 // Convert the comma-separated list to array of IDs
					 selectedIds = (selectedIds.length) ? selectedIds.split(',') : [];
					 
					 if ( $(this).is(':checked') ){
					 
					 if ( $.inArray(currentId, selectedIds) === -1 ){
					 selectedIds.push(currentId);
					 $(this).closest('tr').addClass('selected');
					 }
					 
					 }
					 else{
					 
					 $.each(selectedIds, function(index, elem){
					 
					 if (elem === currentId){
					 selectedIds.splice(index, 1);
					 return false; // Do not continue
					 }
					 
					 });
					 
					 $(this).closest('tr').removeClass('selected');
					 
					 }
					 
					 $inputSelectedIds.val(  selectedIds.join(',') ).trigger('change');
					 $('#the-list.ui-sortable').sortable('destroy').removeClass('ui-sortable');
					 self.bindSortable();
					 
					 });*/
					
					// Check all checkbox
					// pro version
					/*$listWrapper.on('change', '.manage-column input[type=checkbox]', function(){
					 
					 var listCheckboxes = $('input[type=checkbox]', postsList);
					 if ($(this).is(':checked')){
					 listCheckboxes.attr('checked', 'checked').change();
					 }
					 else{
					 listCheckboxes.removeAttr('checked').change();
					 }
					 
					 });
					 
					 // Add selected class to rows
					 $('input[type=checkbox]:checked', postsList).closest('tr').addClass('selected');*/
					
				},
				
				addScrollBar: function() {
					
					// Wait until the thumbs are loaded and enable JScrollpane
					var $tableWrapper = $('.atum-table-wrapper'),
					    scrollOpts  = {
						    horizontalGutter: 0,
						    verticalGutter  : 0
					    };
					
					$tableWrapper.imagesLoaded().then(function () {
						$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
						jScrollApi  = $scrollPane.data('jsp');
					});
					
				},
				
				reloadScrollbar: function() {
					jScrollApi.destroy();
					this.addScrollBar();
				},
				
				keyUp: function (e, $elem) {
					
					var delay = 500;
					
					/*
					 * If user hit enter, we don't want to submit the form
					 * We don't preventDefault() for all keys because it would
					 * also prevent to get the page number!
					 */
					if (13 === e.which) {
						e.preventDefault();
					}
					
					// This time we fetch the variables in inputs
					var data = {
						paged   : parseInt($listWrapper.find('.current-page').val()) || '1',
						order   : atumListTable.order || 'desc',
						orderby : atumListTable.orderby || 'date',
						v_filter: $listWrapper.find('.subsubsub a.current').attr('id') || '',
						s       : $search.val() || ''
					};
					
					/*
					 * Now the timer comes to use: we wait half a second after
					 * the user stopped typing to actually send the call. If
					 * we don't, the keyup event will trigger instantly and
					 * thus may cause duplicate calls before sending the intended
					 * value
					 */
					window.clearTimeout(timer);
					
					timer = window.setTimeout(function () {
						stockCentralTable.update(data, $elem);
					}, delay);
					
				},
				
				/**
				 * Enables Tooltip for titles
				 *
				 * @since 0.0.8
				 */
				tooltip: function () {
					
					if (typeof $.fn.tipTip === 'function') {
						$('.tips').tipTip({
							'attribute': 'data-tip',
							'fadeIn'   : 50,
							'fadeOut'  : 50,
							'delay'    : 200
						});
					}
					
				},
				
				/**
				 * AJAX call
				 * Send the call and replace table parts with updated version!
				 *
				 * @param object data     The data to pass through AJAX
				 * @param object $elem    Selector where will be added the spinner
				 */
				update: function (data, $elem) {
					
					var self = this,
						perPage;
					
					if (postTypeTableAjax && postTypeTableAjax.readyState !== 4) {
						postTypeTableAjax.abort();
					}
					
					if (!$.isNumeric($inputPerPage.val())) {
						perPage = atumListTable.perpage || 20;
					}
					else {
						perPage = parseInt($inputPerPage.val());
					}
					
					data = $.extend({
						token      : atumListTable.nonce,
						action     : 'atum_fetch_stock_central_list',
						per_page   : perPage,
						//selected   : $inputSelectedIds.val(),
						category   : $listWrapper.find('.dropdown_product_cat').val() || '',
						m          : $listWrapper.find('#filter-by-date').val() || '',
						type       : $listWrapper.find('#dropdown_product_type').val() || '',
					}, data);
					
					postTypeTableAjax = $.ajax({
						
						url       : ajaxurl,
						dataType  : 'json',
						data      : data,
						beforeSend: function () {
							$atumTable.addClass('overlay');
							$elem.append('<div class="atum-loading"></div>');
						},
						// Handle the successful result
						success   : function (response) {
							
							postTypeTableAjax = '';
							$atumTable.removeClass('overlay');
							$('.atum-loading').remove();
							
							if (typeof response === 'undefined' || !response) {
								return false;
							}
							
							// Add the requested rows
							if (typeof response.rows !== 'undefined' && response.rows.length) {
								postsList.html(response.rows);
								stockCentralTable.tooltip();
							}
							
							// Update column headers for sorting
							if (typeof response.column_headers !== 'undefined' && response.column_headers.length) {
								$('thead tr.item-heads, tfoot tr', $listWrapper).html(response.column_headers);
							}
							
							if (typeof response.views !== 'undefined' && response.views.length) {
								$('.subsubsub', $listWrapper).replaceWith(response.views);
							}
							
							if (typeof response.pagination !== 'undefined') {
								
								// Update pagination for navigation
								if (response.pagination.top.length) {
									$('.tablenav.top .tablenav-pages', $listWrapper).html($(response.pagination.top).html());
								}
								
								if (response.pagination.bottom.length) {
									$('.tablenav.bottom .tablenav-pages', $listWrapper).html($(response.pagination.bottom).html());
								}
								
							}
							
							if (typeof response.extra_t_n !== 'undefined') {
								
								// Update extra table nav for navigation
								if (response.extra_t_n.top.length) {
									$listWrapper.find('.tablenav.top .actions')
										.replaceWith(response.extra_t_n.top);
								}
								
								if (response.extra_t_n.bottom.length) {
									$listWrapper.find('.tablenav.bottom .actions')
										.replaceWith(response.extra_t_n.bottom);
								}
								
							}
							
							// Re-add the scrollbar
							self.reloadScrollbar();
							
							// Add selected class to rows
							//pro version
							//$('input[type=checkbox]:checked', postsList).closest('tr').addClass('selected');
							
						},
						error     : function () {
							$atumTable.removeClass('overlay');
							$('.atum-loading').remove();
						}
					});
					
				},
				
				/**
				 * Filter the URL Query to extract variables
				 *
				 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
				 *
				 * @param    string    query The URL query part containing the variables
				 * @param    string    variable Name of the variable we want to get
				 *
				 * @return   string|boolean The variable value if available, false else.
				 */
				__query: function (query, variable) {
					
					var vars = query.split("&");
					for (var i = 0; i < vars.length; i++) {
						var pair = vars[i].split("=");
						if (pair[0] === variable) {
							return pair[1];
						}
					}
					return false;
				}
			};
			
			// Show time!
			stockCentralTable.init();
			
		});
		
		//-------------------------
		// Management Stock notice
		//-------------------------
		var $notice    = $('.atum-notice.notice-management-stock'),
		    noticeAjax = '';
		
		var noticeAction = {
			
			init: function () {
				var self = this;
				
				$notice.find('.add-manage-option').click( function () {
					$(this).after('<span class="atum-loading" />');
					self.send('manage');
				});
				
				$notice.click('.notice-dismiss', function () {
					self.send('dismiss');
				});
				
			},
			
			send: function (action) {
				
				if (noticeAjax && noticeAjax.readyState !== 4) {
					noticeAjax.abort();
				}
				
				noticeAjax = $.ajax({
					url     : ajaxurl,
					method  : 'POST',
					data    : {
						token : $notice.data('nonce'),
						action: 'atum_manage_stock_notice',
						data  : action
					},
					beforeSend: function() {
						
					},
					success : function() {
						location.reload();
					}
				});
				
			}
			
		};
		
		noticeAction.init();
		
		//----------------
		// Welcome notice
		//----------------
		$('.atum-notice.welcome-notice').click('.notice-dismiss', function() {
			
			var $welcomeNotice = $(this);
			
			$.ajax({
				url     : ajaxurl,
				method  : 'POST',
				data    : {
					token : $welcomeNotice.data('nonce'),
					action: 'atum_welcome_notice',
				}
			});
			
		});
		
	});
	
	// Allow an event to fire after all images are loaded
	$.fn.imagesLoaded = function () {
		
		// get all the images (excluding those with no src attribute)
		var $imgs = this.find('img[src!=""]');
		// if there's no images, just return an already resolved promise
		if (!$imgs.length) {
			return $.Deferred().resolve().promise();
		}
		
		// for each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
		var dfds = [];
		$imgs.each(function () {
			
			var dfd = $.Deferred();
			dfds.push(dfd);
			var img = new Image();
			img.onload = function () {
				dfd.resolve();
			}
			img.onerror = function () {
				dfd.resolve();
			}
			img.src = this.src;
			
		});
		
		// return a master promise object which will resolve when all the deferred objects have resolved
		// IE - when all the images are loaded
		return $.when.apply($, dfds);
		
	};
	
})(jQuery);

jQuery.noConflict();