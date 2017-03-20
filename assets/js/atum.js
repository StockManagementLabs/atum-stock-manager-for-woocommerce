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
			    //$inputSelectedIds = $listWrapper.find('.atum_selected_ids'),
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
					
					// Add popovers
					this.setFieldPopover();
					
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
						
						// The search event is triggered when cliking on the clear field button within the seach input
						$listWrapper.on('keyup paste search', '.atum-post-search', function (e) {
							self.keyUp(e, $(this).closest('.search-box'));
						})
						
						.on('change', '#filter-by-date, .dropdown_product_cat, #dropdown_product_type', function (e) {
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
					$listWrapper.on('keyup paste', '.current-page', function (e) {
						self.keyUp(e, $(this).closest('.tablenav-pages'));
					})
					
					// Variation products expanding/collapsing
					.on('click', '.product-type.has-child', function() {
						
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
				 * Enable tooltips
				 *
				 * @since 0.0.8
				 */
				tooltip: function () {
					
					if (typeof $.fn.tipTip === 'function') {
						
						$('.tips').each(function() {
							
							$(this).tipTip({
								attribute: 'data-tip',
								fadeIn   : 50,
								fadeOut  : 50,
								delay    : 200,
								defaultPosition: $(this).data('position') || 'bottom'
							});
							
						});
					}
					
				},
				
				/**
				 * Enable "Set Field" popovers
				 *
				 * @since 1.1.2
				 */
				setFieldPopover: function () {
					
					var self = this;
					
					// Set meta value for listed products
					$('.set-meta').each(function() {
						
						var $metaCell         = $(this),
						    symbol            = $metaCell.data('symbol') || '',
						    currentColumnText = $atumTable.find('tfoot th').eq($metaCell.closest('td').index()).text().toLowerCase(),
						    inputType         = $metaCell.data('input-type') || 'number',
						    inputAtts         = {
							    type : $metaCell.data('input-type') || 'number',
							    value: $metaCell.text().replace(symbol, '').replace('—', ''),
							    class: 'meta-value'
						    };
						
						if (inputType === 'number') {
							inputAtts.min = '0';
							// Allow decimals only for the pricing fields for now
							inputAtts.step = (symbol) ? '0.1' : '1';
						}
						
						
						var $input       = $('<input />', inputAtts),
						    $setButton   = $('<button />', {type: 'button', class: 'set button button-primary button-small', text: atumListTable.setButton}),
						    extraMeta    = $metaCell.data('extra-meta'),
						    $extraFields = '',
							popoverClass = '';
						
						// Check whether to add extra fields to the popover
						if (typeof extraMeta !== 'undefined') {
							
							popoverClass = ' with-meta';
							$extraFields = $('<hr>');
							
							$.each(extraMeta, function(index, metaAtts) {
								$extraFields = $extraFields.add($('<input />', metaAtts));
							});
							
						}
						
						var $content = ($extraFields.length) ? $input.add($extraFields).add($setButton) : $input.add($setButton);
						
						// Create the meta edit popover
						$metaCell.popover({
							title    : atumListTable.setValue.replace('%%', currentColumnText),
							content  : $content,
							html     : true,
							template : '<div class="popover' + popoverClass + '" role="tooltip"><div class="popover-arrow"></div>' +
									   '<h3 class="popover-title"></h3><div class="popover-content"></div></div>',
							placement: 'bottom',
							trigger  : 'click',
							container: 'body'
						});
						
					});
					
					// Focus on the input field
					$('.set-meta').on('shown.bs.popover', function () {
						$('.popover').find('.meta-value').focus();
						self.setDatePickers();
					});
					
					// Hide other popovers
					$listWrapper.click( function(e) {
						
						var $target = $(e.target),
						    $selector = ($target.hasClass('set-meta')) ? $('.set-meta').not($target) : $('.set-meta');
						
						$selector.popover('hide');
						
					});
					
					// Send the ajax request when clicking the "Set" button
					$('body').on('click', '.popover button.set', function(e) {
						
						var $button   = $(this),
						    $popover  = $button.closest('.popover'),
						    popoverId = $popover.attr('id'),
						    $setMeta  = $('[aria-describedby="' + popoverId + '"]'),
						    data      = {
							    token : atumListTable.nonce,
							    action: 'atum_update_meta',
							    item  : $setMeta.data('item'),
							    meta  : $setMeta.data('meta'),
							    value : $button.siblings('.meta-value').val()
						    },
						    extraMeta = {};
						
						if ($popover.hasClass('with-meta')) {
							$button.siblings('input').not('.meta-value').each(function(index, elem) {
								extraMeta[elem.name] = $(elem).val();
							});
							
							data.extraMeta = extraMeta;
						}
						
						$.ajax({
							url     : ajaxurl,
							method  : 'POST',
							dataType: 'json',
							data    : data,
							beforeSend: function() {
								$button.prop('disabled', true);
							},
							success : function(response) {
								
								var noticeClass    = (response.success) ? 'updated' : 'error',
								    $stockNotice   = $('<div class="' + noticeClass + ' notice is-dismissible"><p><strong>' + response.data + '</strong></p></div>').hide(),
								    $dismissButton = $('<button />', {type: 'button', class: 'notice-dismiss'});
								
								$listWrapper.siblings('.notice').remove();
								$listWrapper.before($stockNotice.append($dismissButton));
								$stockNotice.slideDown(100);
								
								$dismissButton.on( 'click.wp-dismiss-notice', function(e) {
									e.preventDefault();
									$stockNotice.fadeTo( 100, 0, function() {
										$stockNotice.slideUp( 100, function() {
											$stockNotice.remove();
										});
									});
								});
								
								if (response.success) {
									$setMeta.popover('hide');
									$('.atum-post-search').keyup();
								}
								else {
									$button.prop('disabled', false);
								}
							}
						});
						
					});
										
				},
				
				/**
				 * Add the jQuery UI datepicker to input fields
				 */
				setDatePickers: function() {
				
					var $datepickers = $('.datepicker').datepicker({
						defaultDate: '',
						dateFormat: 'yy-mm-dd',
						numberOfMonths: 1,
						showButtonPanel: true,
						onSelect: function( selectedDate ) {
							
							var $this = $(this);
							if ($this.hasClass('from') || $this.hasClass('to')) {
								var option = $this.hasClass('from') ? 'minDate' : 'maxDate',
									instance = $this.data('datepicker'),
									date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
								
								$datepickers.not(this).datepicker('option', option, date);
							}
							
						}
					});
					
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
						action     : $listWrapper.data('action'),
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
								stockCentralTable.setFieldPopover();
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

/*!
 * Bootstrap v3.3.7 (http://getbootstrap.com)
 * Copyright 2011-2017 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

/*!
 * Popover and Tooltips plugins
 */
+function(t){"use strict";var e=t.fn.jquery.split(" ")[0].split(".");if(e[0]<2&&e[1]<9||1==e[0]&&9==e[1]&&e[2]<1||e[0]>3)throw new Error("Bootstrap's JavaScript requires jQuery version 1.9.1 or higher, but lower than version 4")}(jQuery),+function(t){"use strict";function e(e){return this.each(function(){var i=t(this),n=i.data("bs.tooltip"),s="object"==typeof e&&e;!n&&/destroy|hide/.test(e)||(n||i.data("bs.tooltip",n=new o(this,s)),"string"==typeof e&&n[e]())})}var o=function(t,e){this.type=null,this.options=null,this.enabled=null,this.timeout=null,this.hoverState=null,this.$element=null,this.inState=null,this.init("tooltip",t,e)};o.VERSION="3.3.7",o.TRANSITION_DURATION=150,o.DEFAULTS={animation:!0,placement:"top",selector:!1,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:!1,container:!1,viewport:{selector:"body",padding:0}},o.prototype.init=function(e,o,i){if(this.enabled=!0,this.type=e,this.$element=t(o),this.options=this.getOptions(i),this.$viewport=this.options.viewport&&t(t.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport),this.inState={click:!1,hover:!1,focus:!1},this.$element[0]instanceof document.constructor&&!this.options.selector)throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!");for(var n=this.options.trigger.split(" "),s=n.length;s--;){var r=n[s];if("click"==r)this.$element.on("click."+this.type,this.options.selector,t.proxy(this.toggle,this));else if("manual"!=r){var p="hover"==r?"mouseenter":"focusin",l="hover"==r?"mouseleave":"focusout";this.$element.on(p+"."+this.type,this.options.selector,t.proxy(this.enter,this)),this.$element.on(l+"."+this.type,this.options.selector,t.proxy(this.leave,this))}}this.options.selector?this._options=t.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()},o.prototype.getDefaults=function(){return o.DEFAULTS},o.prototype.getOptions=function(e){return e=t.extend({},this.getDefaults(),this.$element.data(),e),e.delay&&"number"==typeof e.delay&&(e.delay={show:e.delay,hide:e.delay}),e},o.prototype.getDelegateOptions=function(){var e={},o=this.getDefaults();return this._options&&t.each(this._options,function(t,i){o[t]!=i&&(e[t]=i)}),e},o.prototype.enter=function(e){var o=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return o||(o=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,o)),e instanceof t.Event&&(o.inState["focusin"==e.type?"focus":"hover"]=!0),o.tip().hasClass("in")||"in"==o.hoverState?void(o.hoverState="in"):(clearTimeout(o.timeout),o.hoverState="in",o.options.delay&&o.options.delay.show?void(o.timeout=setTimeout(function(){"in"==o.hoverState&&o.show()},o.options.delay.show)):o.show())},o.prototype.isInStateTrue=function(){for(var t in this.inState)if(this.inState[t])return!0;return!1},o.prototype.leave=function(e){var o=e instanceof this.constructor?e:t(e.currentTarget).data("bs."+this.type);return o||(o=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,o)),e instanceof t.Event&&(o.inState["focusout"==e.type?"focus":"hover"]=!1),o.isInStateTrue()?void 0:(clearTimeout(o.timeout),o.hoverState="out",o.options.delay&&o.options.delay.hide?void(o.timeout=setTimeout(function(){"out"==o.hoverState&&o.hide()},o.options.delay.hide)):o.hide())},o.prototype.show=function(){var e=t.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);var i=t.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(e.isDefaultPrevented()||!i)return;var n=this,s=this.tip(),r=this.getUID(this.type);this.setContent(),s.attr("id",r),this.$element.attr("aria-describedby",r),this.options.animation&&s.addClass("fade");var p="function"==typeof this.options.placement?this.options.placement.call(this,s[0],this.$element[0]):this.options.placement,l=/\s?auto?\s?/i,a=l.test(p);a&&(p=p.replace(l,"")||"top"),s.detach().css({top:0,left:0,display:"block"}).addClass(p).data("bs."+this.type,this),this.options.container?s.appendTo(this.options.container):s.insertAfter(this.$element),this.$element.trigger("inserted.bs."+this.type);var h=this.getPosition(),f=s[0].offsetWidth,c=s[0].offsetHeight;if(a){var u=p,d=this.getPosition(this.$viewport);p="bottom"==p&&h.bottom+c>d.bottom?"top":"top"==p&&h.top-c<d.top?"bottom":"right"==p&&h.right+f>d.width?"left":"left"==p&&h.left-f<d.left?"right":p,s.removeClass(u).addClass(p)}var v=this.getCalculatedOffset(p,h,f,c);this.applyPlacement(v,p);var g=function(){var t=n.hoverState;n.$element.trigger("shown.bs."+n.type),n.hoverState=null,"out"==t&&n.leave(n)};t.support.transition&&this.$tip.hasClass("fade")?s.one("bsTransitionEnd",g).emulateTransitionEnd(o.TRANSITION_DURATION):g()}},o.prototype.applyPlacement=function(e,o){var i=this.tip(),n=i[0].offsetWidth,s=i[0].offsetHeight,r=parseInt(i.css("margin-top"),10),p=parseInt(i.css("margin-left"),10);isNaN(r)&&(r=0),isNaN(p)&&(p=0),e.top+=r,e.left+=p,t.offset.setOffset(i[0],t.extend({using:function(t){i.css({top:Math.round(t.top),left:Math.round(t.left)})}},e),0),i.addClass("in");var l=i[0].offsetWidth,a=i[0].offsetHeight;"top"==o&&a!=s&&(e.top=e.top+s-a);var h=this.getViewportAdjustedDelta(o,e,l,a);h.left?e.left+=h.left:e.top+=h.top;var f=/top|bottom/.test(o),c=f?2*h.left-n+l:2*h.top-s+a,u=f?"offsetWidth":"offsetHeight";i.offset(e),this.replaceArrow(c,i[0][u],f)},o.prototype.replaceArrow=function(t,e,o){this.arrow().css(o?"left":"top",50*(1-t/e)+"%").css(o?"top":"left","")},o.prototype.setContent=function(){var t=this.tip(),e=this.getTitle();t.find(".tooltip-inner")[this.options.html?"html":"text"](e),t.removeClass("fade in top bottom left right")},o.prototype.hide=function(e){function i(){"in"!=n.hoverState&&s.detach(),n.$element&&n.$element.removeAttr("aria-describedby").trigger("hidden.bs."+n.type),e&&e()}var n=this,s=t(this.$tip),r=t.Event("hide.bs."+this.type);return this.$element.trigger(r),r.isDefaultPrevented()?void 0:(s.removeClass("in"),t.support.transition&&s.hasClass("fade")?s.one("bsTransitionEnd",i).emulateTransitionEnd(o.TRANSITION_DURATION):i(),this.hoverState=null,this)},o.prototype.fixTitle=function(){var t=this.$element;(t.attr("title")||"string"!=typeof t.attr("data-original-title"))&&t.attr("data-original-title",t.attr("title")||"").attr("title","")},o.prototype.hasContent=function(){return this.getTitle()},o.prototype.getPosition=function(e){e=e||this.$element;var o=e[0],i="BODY"==o.tagName,n=o.getBoundingClientRect();null==n.width&&(n=t.extend({},n,{width:n.right-n.left,height:n.bottom-n.top}));var s=window.SVGElement&&o instanceof window.SVGElement,r=i?{top:0,left:0}:s?null:e.offset(),p={scroll:i?document.documentElement.scrollTop||document.body.scrollTop:e.scrollTop()},l=i?{width:t(window).width(),height:t(window).height()}:null;return t.extend({},n,p,l,r)},o.prototype.getCalculatedOffset=function(t,e,o,i){return"bottom"==t?{top:e.top+e.height,left:e.left+e.width/2-o/2}:"top"==t?{top:e.top-i,left:e.left+e.width/2-o/2}:"left"==t?{top:e.top+e.height/2-i/2,left:e.left-o}:{top:e.top+e.height/2-i/2,left:e.left+e.width}},o.prototype.getViewportAdjustedDelta=function(t,e,o,i){var n={top:0,left:0};if(!this.$viewport)return n;var s=this.options.viewport&&this.options.viewport.padding||0,r=this.getPosition(this.$viewport);if(/right|left/.test(t)){var p=e.top-s-r.scroll,l=e.top+s-r.scroll+i;p<r.top?n.top=r.top-p:l>r.top+r.height&&(n.top=r.top+r.height-l)}else{var a=e.left-s,h=e.left+s+o;a<r.left?n.left=r.left-a:h>r.right&&(n.left=r.left+r.width-h)}return n},o.prototype.getTitle=function(){var t,e=this.$element,o=this.options;return t=e.attr("data-original-title")||("function"==typeof o.title?o.title.call(e[0]):o.title)},o.prototype.getUID=function(t){do t+=~~(1e6*Math.random());while(document.getElementById(t));return t},o.prototype.tip=function(){if(!this.$tip&&(this.$tip=t(this.options.template),1!=this.$tip.length))throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!");return this.$tip},o.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")},o.prototype.enable=function(){this.enabled=!0},o.prototype.disable=function(){this.enabled=!1},o.prototype.toggleEnabled=function(){this.enabled=!this.enabled},o.prototype.toggle=function(e){var o=this;e&&(o=t(e.currentTarget).data("bs."+this.type),o||(o=new this.constructor(e.currentTarget,this.getDelegateOptions()),t(e.currentTarget).data("bs."+this.type,o))),e?(o.inState.click=!o.inState.click,o.isInStateTrue()?o.enter(o):o.leave(o)):o.tip().hasClass("in")?o.leave(o):o.enter(o)},o.prototype.destroy=function(){var t=this;clearTimeout(this.timeout),this.hide(function(){t.$element.off("."+t.type).removeData("bs."+t.type),t.$tip&&t.$tip.detach(),t.$tip=null,t.$arrow=null,t.$viewport=null,t.$element=null})};var i=t.fn.tooltip;t.fn.tooltip=e,t.fn.tooltip.Constructor=o,t.fn.tooltip.noConflict=function(){return t.fn.tooltip=i,this}}(jQuery),+function(t){"use strict";function e(e){return this.each(function(){var i=t(this),n=i.data("bs.popover"),s="object"==typeof e&&e;!n&&/destroy|hide/.test(e)||(n||i.data("bs.popover",n=new o(this,s)),"string"==typeof e&&n[e]())})}var o=function(t,e){this.init("popover",t,e)};if(!t.fn.tooltip)throw new Error("Popover requires tooltip.js");o.VERSION="3.3.7",o.DEFAULTS=t.extend({},t.fn.tooltip.Constructor.DEFAULTS,{placement:"right",trigger:"click",content:"",template:'<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'}),o.prototype=t.extend({},t.fn.tooltip.Constructor.prototype),o.prototype.constructor=o,o.prototype.getDefaults=function(){return o.DEFAULTS},o.prototype.setContent=function(){var t=this.tip(),e=this.getTitle(),o=this.getContent();t.find(".popover-title")[this.options.html?"html":"text"](e),t.find(".popover-content").children().detach().end()[this.options.html?"string"==typeof o?"html":"append":"text"](o),t.removeClass("fade top bottom left right in"),t.find(".popover-title").html()||t.find(".popover-title").hide()},o.prototype.hasContent=function(){return this.getTitle()||this.getContent()},o.prototype.getContent=function(){var t=this.$element,e=this.options;return t.attr("data-content")||("function"==typeof e.content?e.content.call(t[0]):e.content)},o.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".arrow")};var i=t.fn.popover;t.fn.popover=e,t.fn.popover.Constructor=o,t.fn.popover.noConflict=function(){return t.fn.popover=i,this}}(jQuery);