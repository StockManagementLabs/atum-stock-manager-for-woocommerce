/**
 * Atum List Tables
 *
 * @copyright Stock Management Labs ©2018
 *
 * @since 0.0.1
 */

;( function( $, window, document, undefined ) {
	"use strict";
	
	// Create the defaults once
	var pluginName = 'atumListTable',
	    defaults   = {
		    ajaxFilter     : 'yes',
		    view           : 'all_stock',
		    order          : 'desc',
		    orderby        : 'date',
		    paged          : 1,
			searchDropdown : 'no'
	    };
	
	// The actual plugin constructor
	function Plugin ( element, options ) {
		
		// Initialize selectors
		this.$atumList             = $(element);
		this.$atumTable            = this.$atumList.find('.atum-list-table');
		this.$editInput            = this.$atumList.find('#atum-column-edits');
		this.$searchInput          = this.$atumList.find('.atum-post-search');
		this.$searchColumnBtn      = this.$atumList.find('#search_column_btn');
		this.$searchColumnDropdown = this.$atumList.find('#search_column_dropdown');
		this.$bulkButton           = $('.apply-bulk-action');
		
		// We don't want to alter the default options for future instances of the plugin
		// Load the localized vars to the plugin settings too
		this.settings = $.extend( {}, defaults, atumListVars || {}, options || {} );
		
		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}
	
	// Avoid Plugin.prototype conflicts
	$.extend( Plugin.prototype, {
		
		doingAjax            : null,
		jScrollApi           : null,
		$scrollPane          : null,
		timer                : null,
		isRowExpanding       : {},
		filterData           : {},
		navigationReady      : false,
		numHashParameters    : 0,
		delayTimer           : 0,
		$collapsedGroups     : null,
		$stickyCols          : null,
		$floatTheadStickyCols: null,
		
		/**
		 * Register our events and initialize the UI
		 */
		init: function () {
			
			var self         = this,
			    inputPerPage = this.$atumList.parent().siblings('#screen-meta').find('.screen-per-page').val(),
			    perPage;
			
			//
			// Initialize the filters' data
			//-----------------------------
			if (!$.isNumeric(inputPerPage)) {
				perPage = this.settings.perPage || 20;
			}
			else {
				perPage = parseInt(inputPerPage);
			}
			
			this.filterData = {
				token          : this.settings.nonce,
				action         : this.$atumList.data('action'),
				screen         : this.$atumList.data('screen'),
				per_page       : perPage,
				show_cb        : this.settings.showCb,
				show_controlled: (this.__query(location.search.substring(1), 'uncontrolled') !== '1' && $.address.parameter('uncontrolled') !== '1') ? 1 : 0,
				order          : this.settings.order,
				orderby        : this.settings.orderby,
			};

            //
			// Init search by column if .atum-post-search-with-dropdown exists, and listen screen option checkboxes
            //--------------------------------
            if ( $('.atum-post-search-with-dropdown').length) {

                this.settings.searchDropdown = 'yes';
                this.setupSearchColumnDropdown();

                $('#adv-settings input[type=checkbox]').change(function () {
                    setTimeout(self.setupSearchColumnDropdown, 500); // performance
                });
            }
			
			//
			// Setup the URL navigation
			//--------------------------
			this.setupNavigation();
			
			//
			// Init the table scrollbar
			//--------------------------
			this.addScrollBar();
			
			$(window).resize(function() {
				
				if (self.$scrollPane && self.$scrollPane.length && typeof self.$scrollPane.data('jsp') !== 'undefined') {
					self.jScrollApi.reinitialise();
				}
				
			}).resize();
			
			//
			// Make the first columns sticky
			//------------------------------
			this.$stickyCols = this.createStickyColumns(this.$atumTable);
			
			//
			// Hide/Show the toggleable group of columns with the toggler button
			//------------------------------------------------------------------
			this.$atumList.on('click', '.group-toggler', function () {
				self.toggleGroupColumns($(this));
				$(this).tooltip('hide');
			}).find('.column-groups th[data-collapsed="1"] .group-toggler').click();
			
			//
			// Show the toggleable group columns when opening the screen options
			// to avoid the hidden columns to be disabled when switching column visibilities
			//-------------------------------------------------------------------------------
			$('#show-settings-link').click(function () {
				
				if (!$(this).hasClass('screen-meta-active')) {
					self.$atumTable.find('.column-groups').find('th.collapsed').find('.group-toggler').click();
				}
				
			});
			
			//
			// Add the floating table header
			//------------------------------
			this.addFloatThead();
			
			// This event will trigger on the table when the header is floated and unfloated
			this.$atumTable.on('floatThead', function(e, isFloated, $floatContainer){
				
				if (isFloated) {
					
					$floatContainer.css('height', 'auto');
					
					// Hide search dropdown on sticky
					if (self.settings.searchDropdown === 'yes') {
						$('#search_column_dropdown').hide();
					}
					
					// Hide on mobile view
					if ($('#wpadminbar').css('position') === 'absolute') {
						$floatContainer.hide();
					}
					else {
						$floatContainer.show();
					}
					
					// Reposition the sticky cols to fit the floating header
					if (self.$stickyCols !== null){
						self.$stickyCols.css('top', -1 * ($floatContainer.height() - 1));
					}
					
					// Add the sticky columns to the floating header too
					var $floatTheadTable = self.$atumList.find('.floatThead-table');
					self.$floatTheadStickyCols = self.createStickyColumns($floatTheadTable);
					
					if (self.$floatTheadStickyCols !== null) {
						
						$floatTheadTable.after(self.$floatTheadStickyCols);
						self.$floatTheadStickyCols.css('width', self.$stickyCols.width() + 1);
						
						// Add the colgroup tag with column widths
						self.$floatTheadStickyCols.prepend('<colgroup />');
						
						var $colGroup = self.$floatTheadStickyCols.find('colgroup');
						
						$floatTheadTable.find('thead .item-heads').children().each(function() {
							
							var $cell       = $(this),
								id = $cell.attr('id');
							
							if ($cell.hasClass('hidden')) {
								return;
							}
							
							if (self.$floatTheadStickyCols.find('thead .item-heads').children('#' + id).length ) {
								$colGroup.append('<col style="width:' + $cell.width() + 'px;">');
							}
						});
						
						// Remove the manage-column class to not conflict with the WP's Screen Options functionality
						self.$floatTheadStickyCols.find('.manage-column').removeClass('manage-column');
						
						$colGroup.prependTo(self.$floatTheadStickyCols);
						
						self.adjustStickyHeaders(self.$floatTheadStickyCols, $floatTheadTable);
						
					}
					
				}
				else {
					
					$floatContainer.css('height', 0);
					
					// Reset the sticky columns position
					if (self.$stickyCols !== null){
						self.$stickyCols.css('top', 0);
					}
					
					// Remove the floating header's sticky columns
					if (self.$floatTheadStickyCols !== null) {
						self.$floatTheadStickyCols.remove();
					}
					
				}
				
			});
			
			//
			// Init tooltips
			//---------------
			this.addTooltips();
			
			//
			// Init popovers
			//---------------
			this.setFieldPopover();
			
			// Hide any other opened popover before opening a new one
			this.$atumList.click( function(e) {
				
				var $target   = $(e.target),
				    // If we are clicking on a editable cell, get the other opened popovers, if not, get all them all
				    $metaCell = $target.hasClass('set-meta') ? $('.set-meta').not($target) : $('.set-meta');
				
				// Get only the cells with an opened popover
				$metaCell = $metaCell.filter(function() {
					return $(this).data('bs.popover') !== 'undefined' && ($(this).data('bs.popover').inState || false) && $(this).data('bs.popover').inState.click === true;
				});
				
				self.destroyPopover($metaCell);
				
			});

            //
			// Handle the sales N days siwtcher
			//---------------------------------
            this.setupSalesLastNDaysVal();

			// Popover's "Set" button
			$('body').on('click', '.popover button.set', function() {
				
				var $button   = $(this),
				    $popover  = $button.closest('.popover'),
				    popoverId = $popover.attr('id'),
				    $setMeta  = $('[data-popover="' + popoverId + '"]');
				
				if ($setMeta.length) {
					self.maybeAddSaveButton();
					self.updateEditedColsInput($setMeta, $popover);
				}
				
			});

			//
			// Hide/Show/Colspan column groups
			//--------------------------------
			$('#adv-settings .metabox-prefs input').change(function () {
				self.$atumList.find('thead .column-groups th').each(function () {
					
					var $this = $(this),
					    // these th only have one class
					    cols  = self.$atumList.find('thead .col-' + $this.attr('class') + ':visible').length;
					
					if (cols) {
						$this.show().attr('colspan', cols)
					}
					else {
						$this.hide();
					}
					
				});
			});
			
			//
			// Views, Pagination and Sortable links
			//-------------------------------------
			this.$atumList.on('click', '.tablenav-pages a, .item-heads a, .subsubsub a', function (e) {
				e.preventDefault();
				self.updateHash();
			});

            //
			// Functions to set $searchColumnBtn data-value and html content
			//--------------------------------------------------------------
            this.$searchColumnBtn
	            
	            .on('setHtmlAndDataValue', function (event, value, html) {
            	
	                var $searchColBtn = $(this);
		
		            $searchColBtn.html(html);
		            $searchColBtn.data('value', value);
	
	                self.$searchColumnDropdown.children('a.active').removeClass('active');
	                self.$searchColumnDropdown.children('a').filterByData('value', value).addClass('active');
	
	            })
	            .on('setDataValue', function (event, value) {
	                $(this).data('value', value);
		
		            self.$searchColumnDropdown.children('a.active').removeClass('active');
		            self.$searchColumnDropdown.children('a').filterByData('value', value).addClass('active');
	            });


            // TODO Improve performance: ajaxFilter yes or not
            if (this.settings.ajaxFilter === 'yes') {

                this.$searchInput.on('input', function() {
                	
                    var searchColumnBtnVal = self.$searchColumnBtn.data('value'),
                        searchInputVal = $(this).val();
                    
                    self.delay(function () {
                        self.pseudoKeyUpAjax(searchColumnBtnVal, searchInputVal);
                    }, 500);
                    
                });

                this.$atumList
	                
	                .on('keyup paste search', '.atum-post-search', function() {
	
		                var searchColumnBtnVal = self.$searchColumnBtn.data('value'),
		                    $searchInputVal    = $(this).val();
	                    
	                    self.delay(function () {
	                        self.pseudoKeyUpAjax(searchColumnBtnVal, $searchInputVal);
	                    }, 500);
	                    
	                })
	                .on('change', '.dropdown_product_cat, .dropdown_product_type, .dropdown_supplier, .dropdown_extra_filter', function (e) {
	                    self.keyUp(e);
	                });

                if (this.settings.searchDropdown === 'yes') {
                	
                    this.$searchColumnBtn.on('search_column_data_changed', function() {
                        self.pseudoKeyUpAjax($(this).data('value'), self.$searchInput.val());
                    });
                    
                }

            }


            //
            // Non-ajax filters
            //-----------------
            else {
            	
            	var $searchSubmitBtn = this.$searchInput.siblings('.search-submit');
	
                if (!self.$searchInput.val()) {
	                $searchSubmitBtn.prop('disabled', true);
                }

            	// if s is empty, search-submit must be disabled and ?s removed
				// if s and searchColumnBtnVal have values, then we can push over search
                this.$searchInput.bind('input', function () {
	
	                var searchColumnBtnVal = self.$searchColumnBtn.data('value'),
	                    inputVal           = $(this).val();

                    if (!inputVal) {
	                    $searchSubmitBtn.prop('disabled', true);

                        if (inputVal != $.address.parameter('s')) {
                            $.address.parameter('s', '');
                            $.address.parameter('search_column', '');
                            self.updateHash(); // force clean search
                        }
                    }
                    // Uncaught TypeError: Cannot read property 'length' of undefined (redundant check fails)
                    else if (searchColumnBtnVal.length > 0) {
	                    $searchSubmitBtn.prop('disabled', false);
                    }
                    
                });

                // TODO on init address, check s i search_column values, and disable or not

                // when a search_column changes, set ?s and ?search_column if s has value. If s is empty, clean this two parameters
                if (this.settings.searchDropdown === 'yes') {
                    this.$searchColumnBtn.on('search_column_data_changed', function (e) {
	
	                    var searchInputVal     = self.$searchInput.val(),
	                        searchColumnBtnVal = self.$searchColumnBtn.data('value');

                        if (searchInputVal.length > 0) {
                            $.address.parameter('s', searchInputVal);
                            $.address.parameter('search_column', searchColumnBtnVal);
                            self.keyUp(e);
                        }
                        // Force clean s when required
                        else {
                            $.address.parameter('s', '');
                            $.address.parameter('search_column', '');
                        }
                        
                    });
                }


                this.$atumList.on('click', '.search-category, .search-submit', function () {
	
	                var searchInputVal     = self.$searchInput.val(),
	                    searchColumnBtnVal = self.$searchColumnBtn.data('value');
	
	                $searchSubmitBtn.prop('disabled', searchColumnBtnVal.length === 0 ? true : false);

                    if (searchInputVal.length > 0) {
                        $.address.parameter('s', self.$searchInput.val());
                        $.address.parameter('search_column', self.$searchColumnBtn.data('value'));

                        self.updateHash();

                    }
                    // Force clean s when required
                    else {
                        $.address.parameter('s', '');
                        $.address.parameter('search_column', '');
                        self.updateHash();
                    }
                    
                });

            }
			
			//
			// Pagination text box
			//--------------------
			this.$atumList
				
				.on('keyup paste', '.current-page', function (e) {
					self.keyUp(e);
				})
			
				//
				// Expanding/Collapsing inheritable products
				//-------------------------------------------
				.on('click', '.calc_type .has-child', function() {
					self.expandRow( $(this).closest('tr') );
				})
				
				//
				// Bulk actions dropdown
				//----------------------
				.on('change', '.bulkactions select', function() {
					
					self.updateBulkButton();
					
					if ($(this).val() !== '-1') {
						self.$bulkButton.show();
					}
					else {
						self.$bulkButton.hide();
					}
				})
				
				//
				// Change the Bulk Button text when selecting boxes
				//-------------------------------------------------
				.on('change', '.check-column input:checkbox', function() {
					self.updateBulkButton();
				})
				
				//
				// Expandable rows' checkboxes
				//----------------------------
				.on('change', '.check-column input:checkbox', function() {
					self.checkDescendats($(this));
				})
				
				//
				// Locations tree
				//---------------
				.on('click', '.show-locations', function(e) {
					
					e.preventDefault();
					self.openLocationsPopup($(this));
					
				})
				
				//
				// Reset Filters button
				//---------------------
				.on('click', '.reset-filters', function() {
					
	                self.destroyTooltips();
	                
	                //TODO reset s and column search
	                $.address.queryString('');
	                self.$searchInput.val('');
	                
	                if (self.settings.searchDropdown === 'yes' && self.$searchColumnBtn.data('value') != 'title') {
						self.$searchColumnBtn.trigger('setHtmlAndDataValue', ['title', $('#search_column_dropdown').data('product-title') + ' <span class="caret"></span>']);
	                }
	                
					self.updateTable();
	    
				})
				
				//
				// "Control all products" button
				//------------------------------
				.on('click', '#control-all-products', function() {
					
					var $button = $(this);
					
					$.ajax({
						url     : ajaxurl,
						method  : 'POST',
						dataType: 'json',
						beforeSend: function() {
							$button.prop('disabled', true).after('<span class="atum-spinner"><span></span></span>');
						},
						data    : {
							token : $(this).data('nonce'),
							action: 'atum_control_all_products'
						},
						success: function() {
							location.reload();
						}
					});
					
				});
				
			//
			// Global save for edited cells
			//-----------------------------
			$('body').on('click', '#atum-update-list', function() {
				self.saveData($(this));
			})
			
			//
			// Apply Bulk Actions
			//-------------------
			this.$bulkButton.click(function() {
				
				if (!self.$atumList.find('.check-column input:checked').length) {
					
					swal({
						title            : self.settings.noItemsSelected,
						text             : self.settings.selectItems,
						type             : 'info',
						confirmButtonText: self.settings.ok
					});
					
				}
				else {
					self.applyBulk();
				}
				
			});
			
			//
			// Warn the user about unsaved changes before navigating away
			//----------------------------------------------------------
			$(window).bind('beforeunload', function() {
				
				if (!self.$editInput.val()) {
					return;
				}
				
				// Prevent multiple prompts - seen on Chrome and IE
				if (navigator.userAgent.toLowerCase().match(/msie|chrome/)) {
					
					if (window.aysHasPrompted) {
						return;
					}
					
					window.aysHasPrompted = true;
					window.setTimeout(function() {
						window.aysHasPrompted = false;
					}, 900);
					
				}
				
				return false;
				
			});
			
		},
		
		/**
		 * Sales Last Days switcher
		 */
        setupSalesLastNDaysVal: function() {
	
	        var self            = this,
	            $selectDays     = $('#sales_last_ndays_val'),
	            selectDaysText  = $selectDays.text(),
	            days            = Array.apply(null, {length: 31}).map(Number.call, Number), //var o = [0, 1, 2, 3 ... 30];
	            $selectableDays = $('<select/>');
	        
	        days.shift();
	        
	        for (var i in days) {
		        $selectableDays.append($('<option/>').html(days[i]));
	        }
	
	        $selectDays.html('<span class="textvalue">' + selectDaysText + '</span>');
	        $selectDays.append($selectableDays);
	        $selectDays.find('select').hide().val(selectDaysText);
	
	        $selectableDays.change(function () {
	        	
	        	var $select = $(this);
	        	
		        $selectDays.find('.textvalue').text($select.val());
		        $select.hide();
		        $selectDays.find('.textvalue').show();
		        
		        $.address.parameter('sold_last_days', parseInt($select.val()));
		        self.updateHash();
		        
	        });
	
	        $selectDays.find('.textvalue').click(function () {
		        $(this).hide();
		        $selectDays.find('select').show();
	        });

        },

		/**
		 * Fill the search by column dropdown with the active screen options checkboxes
		 */
        setupSearchColumnDropdown: function() {
        
			var self                  = this,
			    $searchColumnBtn      = $('#search_column_btn'),
			    $searchColumnDropdown = $('#search_column_dropdown');

            // No option and Product title moved to /view/mc-sc-etc . We can set new future values for new views, and also, now they are not dependent of AtumListTable.php
            $searchColumnDropdown.empty();
            $searchColumnDropdown.append($('<a class="dropdown-item" href="#">-</a>').data('value', 'title').text($searchColumnDropdown.data('product-title'))); // 'Product Name'

            $('#adv-settings input:checked').each(function () {
            	
                var optionVal = $(this).val();
                
                if (optionVal.search('calc_') < 0 && optionVal != 'thumb') { // calc values are not searchable, also we can't search on thumb
                    
                    $searchColumnDropdown.append($('<a class="dropdown-item" href="#">-</a>').data('value', optionVal).text($(this).parent().text()));

                    // Most probably, we are on init and ?search_column has a value. Or maybe not, but, if this happens, force change
                    if ($.address.parameter('search_column') != $searchColumnBtn.data('value') && $searchColumnBtn.data('value') == optionVal) {
                        self.$searchColumnBtn.trigger('setHtmlAndDataValue', [optionVal, $(this).parent().text() + ' <span class="caret"></span>']);
                    }
                    
                }
                
            });
			
			$searchColumnBtn.click(function (e) {
                $(this).parent().find('.dropdown-menu').toggle();
                e.stopPropagation();
            });

            // TODO click on drop element
			$searchColumnDropdown.find('a').click(function (e) {

                e.preventDefault();

                self.$searchColumnBtn.trigger('setHtmlAndDataValue', [$(this).data('value'), $(this).text() + ' <span class="caret"></span>']);

                $(this).parents().find('.dropdown-menu').hide();
                $searchColumnDropdown.children('a.active').removeClass('active');
                $(this).addClass('active');

                var fieldTye = $.inArray($(this).data('value'), self.settings.searchableColumns.numeric) > -1 ? 'number' : 'text';
				self.$searchInput.attr('type', fieldTye);

                if (self.settings.ajaxFilter === 'yes') {
                    $searchColumnBtn.trigger('search_column_data_changed');
                }
               
            });

            $(document).click(function () {
	            $searchColumnDropdown.hide();
            });

        },

        /**
		 * Setup the URL state navigation
		 */
		setupNavigation: function() {
			
			if (typeof $.address === 'undefined') {
				return;
			}
			
			var self = this;
			
			this.bindListLinks();
			
			// Hash history navigation
			$.address.externalChange(function(e) {

                if (self.settings.ajaxFilter != 'yes') {
                	// Force enabled or disabled search button
                    var searchInputVal = self.$searchInput.val();
	                $('.search-submit').prop('disabled', searchInputVal.length > 0 ? false : true);
                }

				var numCurrentParams = $.address.parameterNames().length;
				if(self.navigationReady === true && (numCurrentParams || self.numHashParameters !== numCurrentParams)) {
					self.updateTable();
				}
				
				self.navigationReady = true;
				
			})
			.init(function() {
				
				// When accessing externally or reloading the page, update the fields and the list
                if ($.address.parameterNames().length) {

                    // Init fields from hash parameters
                    var s = $.address.parameter('s');
                    if (s) {
                        self.$searchInput.val(s);
                    }

                    var search_column = $.address.parameter('search_column');
                    if (search_column) {
                        var optionVal = "";

                        $('#adv-settings :checkbox').each(function () {
                            optionVal = $(this).val();
                            if (optionVal.search("calc_") < 0) { // calc values are not searchable, also we can't search on thumb

                                if (optionVal != 'thumb' && optionVal == search_column) {
                                    self.$searchColumnBtn.trigger('setHtmlAndDataValue', [optionVal, $(this).parent().text() + ' <span class="caret"></span>']);
                                    return false;
                                }
                            }
                        });
                    }

                    self.updateTable();
					
				}
				
			});
		
		},
		
		/**
		 * Bind the List Table links that will trigger URL hash changes
		 */
		bindListLinks: function () {
			this.$atumList.find('.subsubsub a, .tablenav-pages a, .item-heads a').address();
		},
		
		/**
		 * Add the horizontal scroll bar to the table
		 */
		addScrollBar: function() {
			
			if (this.jScrollApi !== null) {
				this.reloadScrollbar();
				return;
			}
			
			// Wait until the thumbs are loaded and enable JScrollpane
			var self          = this,
			    $tableWrapper = $('.atum-table-wrapper'),
			    scrollOpts    = {
				    horizontalGutter: 0,
				    verticalGutter  : 0,
				    resizeSensor    : true
			    };
			
			// Reset the sticky cols position and visibility to avoid flickering
			if (self.$stickyCols !== null) {
				self.$stickyCols.hide().css('left', 0);
			}
			
			$tableWrapper.imagesLoaded().then(function () {
				
				self.$scrollPane = $tableWrapper.jScrollPane(scrollOpts);
				self.jScrollApi  = self.$scrollPane.data('jsp');
				
				// Bind events
				self.$scrollPane
					.on('jsp-initialised', function (event, isScrollable) {
						
						// Add the stickyCols table
						if (self.$stickyCols !== null && !self.$atumList.find('.atum-list-table.cloned').length) {
							self.$atumTable.after(self.$stickyCols);
							self.addTooltips();
							self.$atumList.trigger('atum-added-sticky-columns');
						}
						
					})
					.on('jsp-scroll-x', function (event, scrollPositionX, isAtLeft, isAtRight) {
						
						// Handle the sticky cols position and visibility when scrolling
						if (self.$stickyCols !== null) {
							
							// Hide the sticky cols when reaching the left side of the panel
							if (scrollPositionX <= 0) {
								self.$stickyCols.hide().css('left', 0);
								
								if (self.$floatTheadStickyCols !== null) {
									self.$floatTheadStickyCols.hide().css('left', 0);
								}
								
							}
							// Reposition the sticky cols while scrolling the pane
							else {
								
								self.$stickyCols.show().css('left', scrollPositionX);
								
								if (self.$floatTheadStickyCols !== null) {
									self.$floatTheadStickyCols.show().css('left', scrollPositionX);
								}
								
								// Ensure sticky column heights are matching
								self.adjustStickyHeaders(self.$stickyCols, self.$atumTable);
								
							}
							
						}
						
					});
				
				// Drag and drop scrolling on desktops
				var hammertime = new Hammer(self.$scrollPane.get(0), {});
				
				hammertime.on('panright panleft', function (ev) {
					
					var paneStartX   = self.jScrollApi.getContentPositionX(),
					    offset       = 20, // Move 20px each time (knowing that hammer gives the pan event a default threshold of 10)
					    displacement = ev.type === 'panright' ? paneStartX - offset : paneStartX + offset
					
					self.jScrollApi.scrollToX( displacement, false)
					
				});
				
				self.$atumList.trigger('atum-scroll-bar-loaded');
				
			});
			
		},
		
		/**
		 * Reload the scrollbar
		 */
		reloadScrollbar: function() {
			
			var self      = this,
			    positionX = 0;
			
			if (this.jScrollApi !== null) {
				positionX = this.jScrollApi.getContentPositionX();
				this.jScrollApi.destroy();
				this.jScrollApi = null;
			}
			
			this.addScrollBar();
			
			if (positionX > 0) {
				// Wait until the scroll bar is re-added to restore the position
				this.$atumList.on('atum-scroll-bar-loaded', function () {
					self.jScrollApi.scrollToX(positionX);
				});
			}
		},
		
		/**
		 * Add the floating header to the table
		 */
		addFloatThead: function() {
			
			if (typeof this.$atumTable.data('floatTheadAttached') !== 'undefined' && this.$atumTable.data('floatTheadAttached') !== false) {
				this.reloadFloatThead();
				return;
			}
			
			this.$atumTable.floatThead({
				responsiveContainer: function ($table) {
					return $table.closest('.jspContainer');
				},
				position           : 'absolute',
				top                : $('#wpadminbar').height(),
				autoReflow         : true
			});
				
		},
		
		/**
		 * Reload the floating table header
		 */
		reloadFloatThead: function() {
			this.$atumTable.floatThead('destroy');
			this.addFloatThead();
		},
		
		/**
		 * Make the first table columns sticky
		 *
		 * @param jQuery The table that will be used as a base to generate the sticky columns
		 *
		 * @return jQuery|null The sticky cols (if enabled) or null
		 */
		createStickyColumns: function($table) {
			
			// If there are no sticky columns in this table, do not continue
			if (!this.settings.stickyColumns.length) {
				return null;
			}
			
			var self        = this,
			    $stickyCols = $table.clone();
			
			// Remove table header and footer
			$stickyCols.addClass('cloned').removeAttr('style').hide().find('colgroup, fthfoot').remove();
			
			// Remove all the columns that won't be sticky
			$stickyCols.find('tr').each(function () {
				
				var $row = $(this);
				
				// Add a prefix to the row ID to avoid problems when expanding/collapsing rows
				$row.data('id', 'c' + $row.data('id'));
				
				// Remove all the column groups except first one
				if ($row.hasClass('column-groups')) {
					var $colGroups = $row.children();
					$colGroups.not(':first-child').remove();
					$colGroups.first().attr('colspan', self.settings.stickyColumns.length);
				}
				// Remove all the non-sticky columns
				else {
					
					var columnNames   = self.settings.stickyColumns,
					    columnClasses = [];
					
					$.each(columnNames, function (index, elem) {
						columnClasses.push('.column-' + elem);
					});
					
					$row.children().not(columnClasses.join(',')).remove();
					
				}
	
			});
			
			// Do not add sticky columns with a low columns number
			if ($stickyCols.find('thead .item-heads').children().not('.hidden').length <= 2) {
				return null;
			}
			
			// Remove the manage-column class to not conflict with the WP's Screen Options functionality
			$stickyCols.find('.manage-column').removeClass('manage-column');
			
			return $stickyCols;
		
		},
		
		/**
		 * Adjust the header heights to match the List Table heights
		 *
		 * @param jQuery $stickyTable
		 * @param jQuery $origTable
		 */
		adjustStickyHeaders: function($stickyTable, $origTable) {
			
			$.each( ['column-groups', 'item-heads'], function(index, className) {
				$stickyTable.find('.' + className + ' > th').first().css('height', $origTable.find('.' + className + ' > th').first().height());
			});
			
		},
		
		/**
		 * Show/Hide the group of columns with the group-toggler button
		 *
		 * @param jQuery $toggler
		 */
		toggleGroupColumns: function($toggler) {
			
			var $curGroupCell = $toggler.closest('th'),
			    groupClass    = $curGroupCell.attr('class').replace('collapsed', ''),
			    $groupCells   = this.$atumTable.find('.item-heads, tbody, .totals').find('th, td').filter('.' + groupClass);
			
			// Show/hide the column group text
			$toggler.siblings().toggle();
			
			// Expand group columns
			if ($curGroupCell.hasClass('collapsed')) {
				
				// Remove the ghost column
				this.$atumTable.find('.ghost-column.' + groupClass).remove();
				$curGroupCell.attr('colspan', $curGroupCell.data('colspan')).removeData('colspan');
				$groupCells.removeAttr('style');
				
			}
			// Collapse group columns
			else {
				
				$groupCells.hide();
				$curGroupCell.data('colspan', $curGroupCell.attr('colspan')).removeAttr('colspan');
				
				// Add a ghost column
				var ghostColOpts = {
					class: 'ghost-column ' + groupClass
				};
				
				// The header could be floating (so in another table)
				$('<th />', ghostColOpts).insertBefore( this.$atumTable.find('thead .item-heads th.' + groupClass).first() );
				$('<th />', ghostColOpts).insertBefore( this.$atumTable.find('tfoot .item-heads th.' + groupClass).first() );
				$('<th />', ghostColOpts).insertBefore( this.$atumTable.find('tfoot .totals th.' + groupClass).first() );
				
				this.$atumTable.find('tbody tr').each(function () {
					$('<td />', ghostColOpts).insertBefore( $(this).find('td.' + groupClass).first() );
				});
				
			}
			
			$curGroupCell.toggleClass('collapsed');
			
			// Set the collapsed group columns array
			this.$collapsedGroups = this.$atumTable.find('.column-groups').children('.collapsed');
			
			this.reloadScrollbar();
			this.reloadFloatThead();
			
		},
		
		/**
		 * Restore all the collapsed groups to its collapsed stage
		 */
		restoreCollapsedGroups: function() {
			
			var self = this;
			
			this.$collapsedGroups.each(function () {
				var $groupCell = $(this);
				$groupCell.removeClass('collapsed').attr('colspan', $groupCell.data('colspan'));
				$groupCell.children('span').not('.group-toggler').show();
				
				self.toggleGroupColumns($groupCell.find('.group-toggler'));
			});
			
		},
		
		/**
		 * Search box keyUp event callback
		 *
		 * @param object  e       The event data object
		 * @param boolean noTimer Whether to delay before triggering the update (used for autosearch)
		 */
		keyUp: function (e, noTimer) {

			var self    = this,
			    delay   = 500,
			    noTimer = noTimer || false;

            var searchInputVal = this.$searchInput.val();

			/*
			 * If user hit enter, we don't want to submit the form
			 * We don't preventDefault() for all keys because it would
			 * also prevent to get the page number!
			 *
			 * Also, if the S param is empty, we don't want to search anything
			 */

        	if( e.type != 'keyup' || searchInputVal.length > 0 ) {
        	
				if (13 === e.which) {
					e.preventDefault();
				}

				if (noTimer) {
					self.updateHash();
				}
				else {
					/*
					 * Now the timer comes to use: we wait half a second after
					 * the user stopped typing to actually send the call. If
					 * we don't, the keyup event will trigger instantly and
					 * thus may cause duplicate calls before sending the intended value
					 */
                    clearTimeout(self.timer);

                    self.timer = setTimeout(function () {
                        // TODO force ?vars on updateHash when ajax
                        self.updateHash();
                    }, delay);

                }
                
            }
            else {
                e.preventDefault();
            }

        },

        /**
		 * Enable tooltips
		 */
		addTooltips: function () {
	
	        $('.tips').each(function() {
	        	var $tipEl = $(this);
		
		        $tipEl.tooltip({
			        html     : true,
			        title    : $tipEl.data('tip'),
			        container: 'body'
	            });
	        });

		},
		
		/**
		 * Destroy all the tooltips
		 */
		destroyTooltips: function() {
			$('.tips').tooltip('destroy');
		},
		
		/**
		 * Enable "Set Field" popovers
		 */
		setFieldPopover: function ($metaCells) {
			
			var self = this;
			
			if (typeof $metaCells === 'undefined') {
				$metaCells = $('.set-meta');
			}
			
			// Set meta value for listed products
			$metaCells.each(function() {
				self.bindPopover($(this));
			});
			
			// Focus on the input field and set a reference to the popover to the editable column
			$metaCells.on('shown.bs.popover', function () {
				var $activePopover = $('.popover.in');
				$activePopover.find('.meta-value').focus();
				self.setDatePickers();
				$(this).attr('data-popover', $activePopover.attr('id'));
			});
			
		},
		
		/**
		 * Bind the editable column's popovers
		 *
		 * @param jQuery $metaCell The cell where the popover will be attached
		 */
		bindPopover: function($metaCell) {
			
			var self              = this,
				symbol            = $metaCell.data('symbol') || '',
			    currentColumnText = this.$atumTable.find('tfoot tr.item-heads').children().eq($metaCell.closest('td').index()).text(),
			    inputType         = $metaCell.data('input-type') || 'number',
			    inputAtts         = {
				    type : $metaCell.data('input-type') || 'number',
				    value: $metaCell.text().replace(symbol, '').replace('—', ''),
				    class: 'meta-value'
			    };
			
			if (inputType === 'number') {
				inputAtts.min = '0';
				// Allow decimals only for the pricing fields for now
				inputAtts.step = symbol ? '0.1' : '1';
			}
			
			
			var $input       = $('<input />', inputAtts),
			    $setButton   = $('<button />', {type: 'button', class: 'set button button-primary button-small', text: self.settings.setButton}),
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
				title    : self.settings.setValue.replace('%%', currentColumnText),
				content  : $content,
				html     : true,
				template : '<div class="popover' + popoverClass + '" role="tooltip"><div class="popover-arrow"></div>' +
				'<h3 class="popover-title"></h3><div class="popover-content"></div></div>',
				placement: 'bottom',
				trigger  : 'click',
				container: 'body'
			});
			
		},
		
		/**
		 * Destroy a popover attached to a specified table cell
		 *
		 * @param jQuery $metaCell The table cell where is attached the visible popover
		 */
		destroyPopover: function($metaCell) {
			
			if ($metaCell.length) {
				var self = this;
				$metaCell.popover('destroy');
				$metaCell.removeAttr('data-popover');
				
				// Give a small lapse to complete the 'fadeOut' animation before re-binding
				setTimeout(function() {
					self.setFieldPopover($metaCell);
				}, 300);
				
			}
			
		},
		
		/**
		 * Add the jQuery UI datepicker to input fields
		 */
		setDatePickers: function() {
			
			if (typeof $.fn.datepicker !== 'undefined') {
				var $datepickers = $('.datepicker').datepicker({
					defaultDate    : '',
					dateFormat     : 'yy-mm-dd',
					numberOfMonths : 1,
					showButtonPanel: true,
					onSelect       : function (selectedDate) {
						
						var $this = $(this);
						if ($this.hasClass('from') || $this.hasClass('to')) {
							var option   = $this.hasClass('from') ? 'minDate' : 'maxDate',
							    instance = $this.data('datepicker'),
							    date     = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
							
							$datepickers.not(this).datepicker('option', option, date);
						}
						
					}
				});
			}
			
		},
		
		/**
		 * Every time a cell is edited, update the input value
		 *
		 * @param jQuery $metaCell  The table cell that is being edited
		 * @param jQuery $popover   The popover attached to the above cell
		 */
		updateEditedColsInput: function($metaCell, $popover) {
			
			var editedCols = this.$editInput.val(),
			    itemId     = $metaCell.closest('tr').data('id'),
			    meta       = $metaCell.data('meta'),
			    symbol     = $metaCell.data('symbol') || '',
			    custom     = $metaCell.data('custom') || 'no',
			    currency   = $metaCell.data('currency') || '',
			    value      = (symbol) ? $metaCell.text().replace(symbol, '') : $metaCell.text(),
			    newValue   = $popover.find('.meta-value').val();
			
			// Update the cell value
			this.setCellValue($metaCell, newValue);
			
			// Initialize the JSON object
			if (editedCols) {
				editedCols = $.parseJSON(editedCols);
			}
			
			editedCols = editedCols || {};
			
			if (!editedCols.hasOwnProperty(itemId)) {
				editedCols[itemId] = {};
			}
			
			if (!editedCols[itemId].hasOwnProperty(meta)) {
				editedCols[itemId][meta] = {};
			}
			
			// Add the meta value to the object
			editedCols[itemId][meta] = newValue;
			editedCols[itemId][meta + '_custom'] = custom;
			editedCols[itemId][meta + '_currency'] = currency;
			
			// Add the extra meta data (if any)
			if ($popover.hasClass('with-meta')) {
				
				var extraMeta = $metaCell.data('extra-meta');
				
				$popover.find('input').not('.meta-value').each(function(index, input) {
					
					var value = $(input).val();
					editedCols[itemId][input.name] = value;
					
					// Save the meta values in the cell data for future uses
					if (typeof extraMeta === 'object') {
						$.each(extraMeta, function (index, elem) {
							if (elem.name === input.name) {
								extraMeta[index]['value'] = value;
								return false;
							}
						});
					}
					
				});
				
			}
			
			this.$editInput.val( JSON.stringify(editedCols) );
			this.destroyPopover($metaCell);
			
		},
		
		/**
		 * Check if we need to add the Update button
		 */
		maybeAddSaveButton: function() {
			
			var self        = this,
				$tableTitle = this.$atumList.siblings('.wp-heading-inline');
			
			if (!$tableTitle.find('#atum-update-list').length) {
				$tableTitle.append( $('<button/>', {
					id: 'atum-update-list',
					class: 'page-title-action button-primary',
					text: self.settings.saveButton
				}) );
				
				// Check whether to show the first edit popup
				if (typeof swal === 'function' && typeof this.settings.firstEditKey !== 'undefined') {
					
					swal({
						title            : self.settings.important,
						text             : self.settings.preventLossNotice,
						type             : 'warning',
						confirmButtonText: self.settings.ok
					});
					
				}
			}
			
		},
		
		/**
		 * Save the edited columns
		 *
		 * @param jQuery $button The "Save Data" button
		 */
		saveData: function($button) {
			
			if (typeof $.atumDoingAjax === 'undefined') {
				
				var self = this,
				    data = {
					    token : self.settings.nonce,
					    action: 'atum_update_data',
					    data  : self.$editInput.val()
				    };
				
				if (typeof this.settings.firstEditKey !== 'undefined') {
					data.first_edit_key = this.settings.firstEditKey;
				}
				
				$.atumDoingAjax = $.ajax({
					url       : ajaxurl,
					method    : 'POST',
					dataType  : 'json',
					data      : data,
					beforeSend: function () {
						$button.prop('disabled', true);
						self.addOverlay();
					},
					success   : function (response) {
						
						if (typeof response === 'object') {
							var noticeType = (response.success) ? 'updated' : 'error';
							self.addNotice(noticeType, response.data);
						}
						
						if (response.success) {
							$button.remove();
							self.$editInput.val('');
							self.updateTable();
						}
						else {
							$button.prop('disabled', false);
						}
						
						$.atumDoingAjax = undefined;
						
						if (typeof self.settings.firstEditKey !== 'undefined') {
							delete self.settings.firstEditKey;
						}
						
					},
					error: function() {
						$.atumDoingAjax = undefined;
						$button.prop('disabled', false);
						self.removeOverlay();
						
						if (typeof self.settings.firstEditKey !== 'undefined') {
							delete self.settings.firstEditKey;
						}
					}
				});
				
			}
			
		},
		
		/**
		 * Apply a bulk action for the selected rows
		 */
		applyBulk: function() {
			
			var self          = this,
			    bulkAction    = this.$atumList.find('.bulkactions select').filter(function () {
				    return $(this).val() !== '-1'
			    }).val(),
			    selectedItems = [];
			
			this.$atumList.find('tbody .check-column input:checkbox').filter(':checked').each(function() {
				selectedItems.push($(this).val());
			});
			
			$.ajax({
				url       : ajaxurl,
				method    : 'POST',
				dataType  : 'json',
				data: {
					token      : self.settings.nonce,
					action     : 'atum_apply_bulk_action',
					bulk_action: bulkAction,
					ids        : selectedItems
				},
				beforeSend: function () {
					self.$bulkButton.prop('disabled', true);
					self.addOverlay();
				},
				success   : function (response) {
					
					if (typeof response === 'object') {
						var noticeType = (response.success) ? 'updated' : 'error';
						self.addNotice(noticeType, response.data);
					}
					
					self.$bulkButton.prop('disabled', false);
					
					if (response.success) {
						self.$bulkButton.hide();
						self.updateTable();
					}
					
				},
				error: function() {
					self.$bulkButton.prop('disabled', false);
					self.removeOverlay();
				}
			});
			
		},
		
		/**
		 * Update the URL hash with the current filters
		 */
		updateHash: function () {

			var self = this;
			
			this.filterData   = $.extend(this.filterData, {
				view          : $.address.parameter('view') || self.$atumList.find('.subsubsub a.current').attr('id') || '',
				product_cat   : self.$atumList.find('.dropdown_product_cat').val() || '',
				product_type  : self.$atumList.find('.dropdown_product_type').val() || '',
				supplier      : self.$atumList.find('.dropdown_supplier').val() || '',
				extra_filter  : self.$atumList.find('.dropdown_extra_filter').val() || '',
				paged         : parseInt(  $.address.parameter('paged') || self.$atumList.find('.current-page').val() || self.settings.paged ),
				//s             : self.$searchInput.val() || '',
                //search_column : self.$searchColumnBtn.data('value') || '',
                s             : $.address.parameter('s') || '',
                search_column : $.address.parameter('search_column') || '',
                sold_last_days: $.address.parameter('sold_last_days') || '',
				orderby       : $.address.parameter('orderby') || self.settings.orderby,
				order         : $.address.parameter('order') || self.settings.order
			});
			
			// Update the URL hash parameters
			$.each(['view', 'product_cat', 'product_type', 'supplier', 'paged', 'order', 'orderby', 's', 'search_column', 'extra_filter', 'sold_last_days'], function(index, elem) {
				
				// Disable auto-update on each iteration until all the parameters have been set
				self.navigationReady = false;
				
				// If it's not saved on the filter data, continue
				if ( typeof self.filterData[elem] === 'undefined' ) {
					return true;
				}
				
				// If it's the default value, is not needed
				if (self.settings.hasOwnProperty(elem) && self.settings[elem] === self.filterData[elem]) {
					$.address.parameter(elem, '');
					return true;
				}
				
				$.address.parameter(elem, self.filterData[elem]);
				
			});
			
			// Restore navigation and update if needed
			var numCurrentParams = $.address.parameterNames().length;
			if (numCurrentParams || this.numHashParameters !== numCurrentParams) {
				this.updateTable();
			}
			
			this.navigationReady   = true;
			this.numHashParameters = numCurrentParams
			
		},
		
		/**
		 * Send the ajax call and replace table parts with updated version
		 */
		updateTable: function () {
			
			var self = this;
			
			if (this.doingAjax && this.doingAjax.readyState !== 4) {
				this.doingAjax.abort();
			}
			
			// Overwrite the filterData with the URL hash parameters
			this.filterData = $.extend(this.filterData, {
				view        	: $.address.parameter('view') || '',
				product_cat 	: $.address.parameter('product_cat') || '',
				product_type	: $.address.parameter('product_type') || '',
				supplier    	: $.address.parameter('supplier') || '',
				extra_filter	: $.address.parameter('extra_filter') || '',
				paged       	: $.address.parameter('paged') || '',
				order       	: $.address.parameter('order') || '',
				orderby     	: $.address.parameter('orderby') || '',
                search_column 	: $.address.parameter('search_column') || '',
                sold_last_days 	: $.address.parameter('sold_last_days') || '',
				s           	: $.address.parameter('s') || '',
			});
			
			this.doingAjax = $.ajax({
				url       : ajaxurl,
				dataType  : 'json',
				method    : 'GET',
				data      : self.filterData,
				beforeSend: function () {
					self.destroyTooltips();
					self.addOverlay();
				},
				// Handle the successful result
				success   : function (response) {

					self.doingAjax = null;

					if (typeof response === 'undefined' || !response) {
						return false;
					}
					
					// Update table with the coming rows
					if (typeof response.rows !== 'undefined' && response.rows.length) {
						self.$atumList.find('#the-list').html(response.rows);
						self.restoreMeta();
						self.setFieldPopover();
					}
					
					// Update column headers for sorting
					if (typeof response.column_headers !== 'undefined' && response.column_headers.length) {
						self.$atumList.find('tr.item-heads').html(response.column_headers);
					}
					
					// Update the views filters
					if (typeof response.views !== 'undefined' && response.views.length) {
						self.$atumList.find('.subsubsub').replaceWith(response.views);
					}
					
					// Update table navs
					if (typeof response.extra_t_n !== 'undefined') {
						
						if (response.extra_t_n.top.length) {
							self.$atumList.find('.tablenav.top').replaceWith(response.extra_t_n.top);
						}
						
						if (response.extra_t_n.bottom.length) {
							self.$atumList.find('.tablenav.bottom').replaceWith(response.extra_t_n.bottom);
						}
						
					}
					
					// Update the totals row
					if (typeof response.totals !== 'undefined') {
						self.$atumList.find('tfoot tr.totals').html(response.totals);
					}
					
					// Re-bind the jQuery address links
					self.bindListLinks();
					
					// If there are active filters, show the reset button
					if ($.address.parameterNames().length) {
						self.$atumList.find('.reset-filters').removeClass('hidden');
					}
					
					// Regenerate the UI
					self.addTooltips();
					self.maybeRestoreEnhancedSelect();
					
					// Restore toggled column groups
					if (self.$collapsedGroups !== null) {
						self.restoreCollapsedGroups();
					}
					else {
						self.reloadScrollbar();
					}
					
					self.removeOverlay();
                    self.setupSalesLastNDaysVal();
                    
                    // Custom trigger after updating
                    self.$atumList.trigger('atum-table-updated');
					
				},
				error     : function (error) {
					self.removeOverlay();
				}
			});
			
		},
		
		/**
		 * Filter the URL Query to extract variables
		 *
		 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
		 *
		 * @param string query    The URL query part containing the variables
		 * @param string variable Name of the variable we want to get
		 *
		 * @return string|boolean The variable value if available, false else.
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
		},
		
		/**
		 * Add the overlay effect while loading data
		 */
		addOverlay: function() {
			$('.atum-table-wrapper').block({
				message   : null,
				overlayCSS: {
					background: '#000',
					opacity   : 0.5
				}
			});
		},
		
		/**
		 * Remove the overlay effect once the data is fully loaded
		 */
		removeOverlay: function() {;
			$('.atum-table-wrapper').unblock();
		},
		
		/**
		 * Set the table cell value with right format
		 *
		 * @param jQuery        $metaCell  The cell where will go the value
		 * @param string|number value      The value to set in the cell
		 */
		setCellValue: function($metaCell, value) {
			
			var symbol      = $metaCell.data('symbol') || '',
			    currencyPos = this.$atumTable.data('currency-pos');
			
			if (symbol) {
				value = (currencyPos === 'left') ? symbol + value : value + symbol;
			}
			
			$metaCell.addClass('unsaved').text(value);
			
		},
		
		/**
		 * Restore the edited meta after loading new table rows
		 */
		restoreMeta: function() {
			
			var self       = this,
			    editedCols = this.$editInput.val();
			
			if (editedCols) {
				
				editedCols = $.parseJSON(editedCols);
				$.each( editedCols, function(itemId, meta) {
					
					// Filter the meta cell that was previously edited
					var $metaCell = $('tr[data-id="' + itemId + '"] .set-meta');
					if ($metaCell.length) {
						$.each(meta, function(key, value) {
							
							$metaCell = $metaCell.filter('[data-meta="' + key + '"]');
							if ($metaCell.length) {
								self.setCellValue($metaCell, value);
								
								// Add the extra meta too
								var extraMeta = $metaCell.data('extra-meta');
								if (typeof extraMeta === 'object') {
									$.each(extraMeta, function(index, extraMetaObj) {
										
										// Restore the extra meta from the edit input
										if (editedCols[itemId].hasOwnProperty(extraMetaObj.name)) {
											extraMeta[index]['value'] = editedCols[itemId][extraMetaObj.name];
										}
										
									});
									
									$metaCell.data('extra-meta', extraMeta);
								}
							}
						});
					}
					
				});
			}
			
		},
		
		/**
		 * Add a notice after saving data
		 *
		 * @param string type The notice type. Can be "updated" or "error"
		 * @param string msg  The message
		 */
		addNotice: function(type, msg) {
			
			var $notice        = $('<div class="' + type + ' notice is-dismissible"><p><strong>' + msg + '</strong></p></div>').hide(),
			    $dismissButton = $('<button />', {type: 'button', class: 'notice-dismiss'});
			
			this.$atumList.siblings('.notice').remove();
			this.$atumList.before($notice.append($dismissButton));
			$notice.slideDown(100);
			
			$dismissButton.on('click.wp-dismiss-notice', function (e) {
				e.preventDefault();
				$notice.fadeTo(100, 0, function () {
					$notice.slideUp(100, function () {
						$notice.remove();
					});
				});
			});
			
		},
		
		/**
		 * Restore the enhanced select filters (if any)
		 */
		maybeRestoreEnhancedSelect: function() {
			
			$('.select2-container--open').remove();
			$('body').trigger('wc-enhanced-select-init');
			
		},
		
		/**
		 * Expand/Collapse rows with childrens
		 *
		 * @param jQuery $row
		 * @param string expandableRowClass
		 * @param string stopRowSelector
		 * @param boolean stopPropagation
		 *
		 * @return void|boolean
		 */
		expandRow: function($row, expandableRowClass, stopRowSelector, stopPropagation) {
			
			var rowId = $row.data('id');
			
			if (typeof expandableRowClass === 'undefined') {
				expandableRowClass = 'expandable';
			}
			
			if (typeof stopRowSelector === 'undefined') {
				stopRowSelector = '.main-row';
			}
			
			// Sync the sticky columns table
			if (this.$stickyCols !== null && (typeof stopPropagation === 'undefined' || stopPropagation !== true)) {
				
				var $siblingTable = $row.closest('.atum-list-table').siblings('.atum-list-table'),
				    $syncRow      = $siblingTable.find('tr[data-id=' + rowId.toString().replace('c', '') + ']');
				
				this.expandRow($syncRow, expandableRowClass, stopRowSelector, true);
				
			}
			
			// Avoid multiple clicks before expanding
			if (typeof this.isRowExpanding[rowId] !== 'undefined' && this.isRowExpanding[rowId] === true) {
				return false;
			}
			
			this.isRowExpanding[rowId] = true;
			
			var self      = this,
			    $rowTable = $row.closest('table'),
			    $nextRow  = $row.next(),
			    childRows = [];
			
			if ($nextRow.length) {
				$row.toggleClass('expanded');
				this.destroyTooltips();
			}
			
			// Loop until reaching the next main row
			while (!$nextRow.filter(stopRowSelector).length) {
				
				if (!$nextRow.length) {
					break;
				}
				
				if (!$nextRow.hasClass(expandableRowClass)) {
					$nextRow = $nextRow.next();
					continue;
				}
				
				childRows.push($nextRow);
				
				if ( ($rowTable.is(':visible') && !$nextRow.is(':visible')) || (!$rowTable.is(':visible') && $nextRow.css('display') === 'none')) {
					$nextRow.addClass('expanding').show(300);
				}
				else {
					$nextRow.addClass('collapsing').hide(300);
				}
				
				$nextRow = $nextRow.next();
				
			}
			
			// Re-enable the expanding again once the animation is completed
			setTimeout(function () {
				
				delete self.isRowExpanding[rowId];
				
				// Do this only when all the rows has been already expanded
				if (!Object.keys(self.isRowExpanding).length && (typeof stopPropagation === 'undefined' || stopPropagation !== true)) {
					self.addTooltips();
				}
				
				$.each(childRows, function (index, $childRow) {
					$childRow.removeClass('expanding collapsing');
				});
				
			}, 320);
			
			self.$atumList.trigger('atum-after-expand-row', [$row, expandableRowClass, stopRowSelector]);
			
		},
		
		/**
		 * Update the Bulk Button text depending on the number of checkboxes selected
		 */
		updateBulkButton: function() {
			var numChecked = this.$atumList.find('.check-column input:checkbox:checked').length,
			    buttonText = numChecked > 1 ? this.settings.applyBulkAction : this.settings.applyAction;
			
			this.$bulkButton.text(buttonText);
		},
		
		/**
		 * Checks/Unchecks the descendants rows when checking/unchecking their container
		 *
		 * @param jQuery $parentCheckbox
		 */
		checkDescendats: function($parentCheckbox) {
			
			var $containerRow = $parentCheckbox.closest('tr');
			
			// Handle clicks on the header checkbox
			if ($parentCheckbox.closest('td').hasClass('manage-column')) {
				// Call this method recursively for all the checkboxes in the current page
				this.$atumTable.find('tr.variable, tr.group').find('input:checkbox').change();
			}
			
			if (!$containerRow.hasClass('variable') && !$containerRow.hasClass('group')) {
				return;
			}
			
			var $nextRow = $containerRow.next('.expandable');
			
			// If is not expanded, expand it
			if (!$containerRow.hasClass('expanded') && $parentCheckbox.is(':checked')) {
				$containerRow.find('.calc_type .has-child').click();
			}
			
			// Check/Uncheck all the children rows
			while ($nextRow.length) {
				$nextRow.find('.check-column input:checkbox').prop('checked', $parentCheckbox.is(':checked'));
				$nextRow = $nextRow.next('.expandable');
			}
			
		},
		
		/**
		 * Apply a delay
		 *
		 * @return Function
		 */
		delay: function(callback, ms) {
			
			clearTimeout(this.delayTimer);
			this.delayTimer = setTimeout(callback, ms);
			
		},
		
		pseudoKeyUpAjax: function(searchColumnBtnVal, searchInputVal) {
			
			var self = this;
			
			if (searchInputVal.length == 0) {
				
				if (searchInputVal != $.address.parameter('s')) {
					$.address.parameter('s', '');
					$.address.parameter('search_column', '');
					self.updateHash(); // force clean search
				}
			}
			else if (searchColumnBtnVal.length > 0) {
				$.address.parameter('s', searchInputVal);
				$.address.parameter('search_column', searchColumnBtnVal);
				self.updateHash();
			}
		
		},
		
		/**
		 * Opens a popup with the locations' tree and allows to edit locations
		 *
		 * @param jQuery $button
		 */
		openLocationsPopup: function($button) {
			
			// Component vars
			var self           = this,
			    productId      = $button.closest('tr').data('id'),
			    locationsSet   = [],
			    toSetLocations = [];
			
			// Open on view
			swal({
				title            : this.settings.productLocations,
				html             : '<div id="atum-locations-tree" class="atum-tree"></div>',
				showCancelButton : false,
				showConfirmButton: true,
				confirmButtonText: this.settings.editProductLocations,
				showCloseButton  : true,
				onOpen           : function() {
					
					var $locationsTreeContainer = $('#atum-locations-tree');
					
					self.destroyTooltips();
					
					$.ajax({
						url       : ajaxurl,
						dataType  : 'json',
						method    : 'post',
						data      : {
							action    : 'atum_get_locations_tree',
							token     : self.settings.nonce,
							product_id: productId,
						},
						beforeSend: function() {
							$locationsTreeContainer.append('<div class="atum-loading" />');
						},
						success   : function(response) {
							
							if (response.success === true) {
								$locationsTreeContainer.html(response.data);
								
								// If answer is like <span class="no-locations-set">...  don't put easytree on work.
								// It will remove the span message
								if (!(response.data.indexOf('no-locations-set') > -1)) {
									
									$locationsTreeContainer.easytree();
									
									// Fill setedLocations
									$('#atum-locations-tree span[class^="cat-item-"], #atum-locations-tree span[class*="cat-item-"]').each(function() {
										var classList = $(this).attr('class').split(/\s+/);
										
										$.each(classList, function(index, item) {
											if (item.startsWith('cat-item-')) {
												locationsSet.push(item);
											}
										});
									})
								}
							}
						},
					});
					
				},
				onClose          : function() {
					self.addTooltips();
				},
			// Click on edit
			}).then(function() {
				
				swal({
					title              : self.settings.editProductLocations,
					html               : '<div id="atum-locations-tree" class="atum-tree"></div>',
					text               : self.settings.textToShow,
					confirmButtonText  : self.settings.saveButton,
					showCloseButton    : true,
					showCancelButton   : true,
					showLoaderOnConfirm: true,
					onOpen             : function() {
						
						var $locationsTreeContainer = $('#atum-locations-tree');
						
						$.ajax({
							url       : ajaxurl,
							dataType  : 'json',
							method    : 'post',
							data      : {
								action    : 'atum_get_locations_tree',
								token     : self.settings.nonce,
								product_id: -1 // Send -1 to get all the terms
							},
							beforeSend: function() {
								$locationsTreeContainer.append('<div class="atum-loading" />');
							},
							success   : function(response) {
								
								if (response.success === true) {
									
									$locationsTreeContainer.html(response.data);
									$locationsTreeContainer.easytree();
									
									// Add instructions alert
									$locationsTreeContainer.append('<div class="alert alert-primary"><i class="dashicons dashicons-info"></i> ' + self.settings.editLocationsInfo + '</div>');
									
									toSetLocations = locationsSet;
									
									// When clicking on link or icon, set node as checked
									$locationsTreeContainer.find('a, .easytree-icon').click(function(event) {
										
										var $this     = $(this),
										    catItem   = '',
										    classList = $this.closest('.easytree-node').attr('class').split(/\s+/);
										
										event.preventDefault();
										
										$.each(classList, function(index, item) {
											if (item.lastIndexOf('cat-item-', 0) === 0) {
												catItem = item;
												
												return false;
											}
										});
										
										$('.' + catItem).toggleClass('checked');
										
										if ($('.' + catItem).hasClass('checked')) {
											toSetLocations.push(catItem);
										}
										else {
											var pos = toSetLocations.indexOf(catItem);
											
											if (pos > -1) {
												toSetLocations.splice(pos, 1);
											}
										}
										
									});
									
									// Set class checked the actual values on load
									$locationsTreeContainer.find('span[class^="cat-item-"], span[class*="cat-item-"]').each(function() {
										
										var classList = $(this).attr('class').split(/\s+/);
										
										$.each(classList, function(index, item) {
											
											if (item.startsWith('cat-item-')) {
												if ($.inArray(item, locationsSet) !== -1) {
													$('.' + item).addClass('checked');
												}
											}
											
										});
										
									});
									
								}
							},
						});
						
					},
					// Save clicked
					preConfirm         : function () {
						return new Promise(function (resolve, reject) {
							
							// ["cat-item-40", "cat-item-39"] -> [40, 39]
							var toSetTerms = toSetLocations.map(function(x) {
								return parseInt(x.substring(9));
							});
							
							$.ajax({
								url       : ajaxurl,
								dataType  : 'json',
								method    : 'post',
								data      : {
									action    : 'atum_set_locations_tree',
									token     : self.settings.nonce,
									product_id: productId,
									terms     : toSetTerms,
								},
								success   : function(response) {
									
									if (response.success === true) {
										resolve();
									}
									else {
										reject();
									}
								},
							});
							
						});
					},
				}).then(function() {
					
					swal({
						title            : self.settings.done,
						type             : 'success',
						text             : self.settings.locationsSaved,
						confirmButtonText: self.settings.ok,
					});
					
				});
				
			}).catch(swal.noop);
			
		},
		
	} );
	
	
	// A really lightweight plugin wrapper around the constructor, preventing against multiple instantiations
	$.fn[ pluginName ] = function( options ) {
		return this.each( function() {
			if ( !$.data( this, 'plugin_' + pluginName ) ) {
				$.data( this, 'plugin_' +
					pluginName, new Plugin( this, options ) );
			}
		} );
	};
	
	
	// Allow an event to fire after all images are loaded
	$.fn.imagesLoaded = function () {
		
		// Get all the images (excluding those with no src attribute)
		var $imgs = this.find('img[src!=""]');
		// If there's no images, just return an already resolved promise
		if (!$imgs.length) {
			return $.Deferred().resolve().promise();
		}
		
		// For each image, add a deferred object to the array which resolves when the image is loaded (or if loading fails)
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
		
		// Return a master promise object which will resolve when all the deferred objects have resolved
		// IE - when all the images are loaded
		return $.when.apply($, dfds);
		
	};
	
	// Filter by data
	$.fn.filterByData = function(prop, val) {
		var self = this;
		if (typeof val === 'undefined') {
			return self.filter(
				
				function() {
					return typeof $(this).data(prop) !== 'undefined';
				});
		}
		
		return self.filter(
			
			function() {
				return $(this).data(prop) == val;
			}
		);
	};
	
	
	// Init the plugin on document ready
	$(function() {
		
		// Init ATUM List Table
		$('.atum-list-wrapper').atumListTable();
		
	});
	
} )( jQuery, window, document );

/**!
 * Bootstrap v3.3.6 (http://getbootstrap.com)
 * Copyright 2011-2017 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 *
 * IMPORTANT NOTE: new 3.3.7 version has an issue with Popovers that won't be fixed
 * @see: https://github.com/twbs/bootstrap/issues/20511
 */

/*!
 * Popover, Tooltip and Transition plugins
 */
+function($){"use strict";function transitionEnd(){var el=document.createElement("bootstrap");var transEndEventNames={WebkitTransition:"webkitTransitionEnd",MozTransition:"transitionend",OTransition:"oTransitionEnd otransitionend",transition:"transitionend"};for(var name in transEndEventNames){if(el.style[name]!==undefined){return{end:transEndEventNames[name]}}}return false}$.fn.emulateTransitionEnd=function(duration){var called=false;var $el=this;$(this).one("bsTransitionEnd",function(){called=true});var callback=function(){if(!called)$($el).trigger($.support.transition.end)};setTimeout(callback,duration);return this};$(function(){$.support.transition=transitionEnd();if(!$.support.transition)return;$.event.special.bsTransitionEnd={bindType:$.support.transition.end,delegateType:$.support.transition.end,handle:function(e){if($(e.target).is(this))return e.handleObj.handler.apply(this,arguments)}}})}(jQuery);+function($){"use strict";var Tooltip=function(element,options){this.type=null;this.options=null;this.enabled=null;this.timeout=null;this.hoverState=null;this.$element=null;this.inState=null;this.init("tooltip",element,options)};Tooltip.VERSION="3.3.6";Tooltip.TRANSITION_DURATION=150;Tooltip.DEFAULTS={animation:true,placement:"top",selector:false,template:'<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>',trigger:"hover focus",title:"",delay:0,html:false,container:false,viewport:{selector:"body",padding:0}};Tooltip.prototype.init=function(type,element,options){this.enabled=true;this.type=type;this.$element=$(element);this.options=this.getOptions(options);this.$viewport=this.options.viewport&&$($.isFunction(this.options.viewport)?this.options.viewport.call(this,this.$element):this.options.viewport.selector||this.options.viewport);this.inState={click:false,hover:false,focus:false};if(this.$element[0]instanceof document.constructor&&!this.options.selector){throw new Error("`selector` option must be specified when initializing "+this.type+" on the window.document object!")}var triggers=this.options.trigger.split(" ");for(var i=triggers.length;i--;){var trigger=triggers[i];if(trigger=="click"){this.$element.on("click."+this.type,this.options.selector,$.proxy(this.toggle,this))}else if(trigger!="manual"){var eventIn=trigger=="hover"?"mouseenter":"focusin";var eventOut=trigger=="hover"?"mouseleave":"focusout";this.$element.on(eventIn+"."+this.type,this.options.selector,$.proxy(this.enter,this));this.$element.on(eventOut+"."+this.type,this.options.selector,$.proxy(this.leave,this))}}this.options.selector?this._options=$.extend({},this.options,{trigger:"manual",selector:""}):this.fixTitle()};Tooltip.prototype.getDefaults=function(){return Tooltip.DEFAULTS};Tooltip.prototype.getOptions=function(options){options=$.extend({},this.getDefaults(),this.$element.data(),options);if(options.delay&&typeof options.delay=="number"){options.delay={show:options.delay,hide:options.delay}}return options};Tooltip.prototype.getDelegateOptions=function(){var options={};var defaults=this.getDefaults();this._options&&$.each(this._options,function(key,value){if(defaults[key]!=value)options[key]=value});return options};Tooltip.prototype.enter=function(obj){var self=obj instanceof this.constructor?obj:$(obj.currentTarget).data("bs."+this.type);if(!self){self=new this.constructor(obj.currentTarget,this.getDelegateOptions());$(obj.currentTarget).data("bs."+this.type,self)}if(obj instanceof $.Event){self.inState[obj.type=="focusin"?"focus":"hover"]=true}if(self.tip().hasClass("in")||self.hoverState=="in"){self.hoverState="in";return}clearTimeout(self.timeout);self.hoverState="in";if(!self.options.delay||!self.options.delay.show)return self.show();self.timeout=setTimeout(function(){if(self.hoverState=="in")self.show()},self.options.delay.show)};Tooltip.prototype.isInStateTrue=function(){for(var key in this.inState){if(this.inState[key])return true}return false};Tooltip.prototype.leave=function(obj){var self=obj instanceof this.constructor?obj:$(obj.currentTarget).data("bs."+this.type);if(!self){self=new this.constructor(obj.currentTarget,this.getDelegateOptions());$(obj.currentTarget).data("bs."+this.type,self)}if(obj instanceof $.Event){self.inState[obj.type=="focusout"?"focus":"hover"]=false}if(self.isInStateTrue())return;clearTimeout(self.timeout);self.hoverState="out";if(!self.options.delay||!self.options.delay.hide)return self.hide();self.timeout=setTimeout(function(){if(self.hoverState=="out")self.hide()},self.options.delay.hide)};Tooltip.prototype.show=function(){var e=$.Event("show.bs."+this.type);if(this.hasContent()&&this.enabled){this.$element.trigger(e);var inDom=$.contains(this.$element[0].ownerDocument.documentElement,this.$element[0]);if(e.isDefaultPrevented()||!inDom)return;var that=this;var $tip=this.tip();var tipId=this.getUID(this.type);this.setContent();$tip.attr("id",tipId);this.$element.attr("aria-describedby",tipId);if(this.options.animation)$tip.addClass("fade");var placement=typeof this.options.placement=="function"?this.options.placement.call(this,$tip[0],this.$element[0]):this.options.placement;var autoToken=/\s?auto?\s?/i;var autoPlace=autoToken.test(placement);if(autoPlace)placement=placement.replace(autoToken,"")||"top";$tip.detach().css({top:0,left:0,display:"block"}).addClass(placement).data("bs."+this.type,this);this.options.container?$tip.appendTo(this.options.container):$tip.insertAfter(this.$element);this.$element.trigger("inserted.bs."+this.type);var pos=this.getPosition();var actualWidth=$tip[0].offsetWidth;var actualHeight=$tip[0].offsetHeight;if(autoPlace){var orgPlacement=placement;var viewportDim=this.getPosition(this.$viewport);placement=placement=="bottom"&&pos.bottom+actualHeight>viewportDim.bottom?"top":placement=="top"&&pos.top-actualHeight<viewportDim.top?"bottom":placement=="right"&&pos.right+actualWidth>viewportDim.width?"left":placement=="left"&&pos.left-actualWidth<viewportDim.left?"right":placement;$tip.removeClass(orgPlacement).addClass(placement)}var calculatedOffset=this.getCalculatedOffset(placement,pos,actualWidth,actualHeight);this.applyPlacement(calculatedOffset,placement);var complete=function(){var prevHoverState=that.hoverState;that.$element.trigger("shown.bs."+that.type);that.hoverState=null;if(prevHoverState=="out")that.leave(that)};$.support.transition&&this.$tip.hasClass("fade")?$tip.one("bsTransitionEnd",complete).emulateTransitionEnd(Tooltip.TRANSITION_DURATION):complete()}};Tooltip.prototype.applyPlacement=function(offset,placement){var $tip=this.tip();var width=$tip[0].offsetWidth;var height=$tip[0].offsetHeight;var marginTop=parseInt($tip.css("margin-top"),10);var marginLeft=parseInt($tip.css("margin-left"),10);if(isNaN(marginTop))marginTop=0;if(isNaN(marginLeft))marginLeft=0;offset.top+=marginTop;offset.left+=marginLeft;$.offset.setOffset($tip[0],$.extend({using:function(props){$tip.css({top:Math.round(props.top),left:Math.round(props.left)})}},offset),0);$tip.addClass("in");var actualWidth=$tip[0].offsetWidth;var actualHeight=$tip[0].offsetHeight;if(placement=="top"&&actualHeight!=height){offset.top=offset.top+height-actualHeight}var delta=this.getViewportAdjustedDelta(placement,offset,actualWidth,actualHeight);if(delta.left)offset.left+=delta.left;else offset.top+=delta.top;var isVertical=/top|bottom/.test(placement);var arrowDelta=isVertical?delta.left*2-width+actualWidth:delta.top*2-height+actualHeight;var arrowOffsetPosition=isVertical?"offsetWidth":"offsetHeight";$tip.offset(offset);this.replaceArrow(arrowDelta,$tip[0][arrowOffsetPosition],isVertical)};Tooltip.prototype.replaceArrow=function(delta,dimension,isVertical){this.arrow().css(isVertical?"left":"top",50*(1-delta/dimension)+"%").css(isVertical?"top":"left","")};Tooltip.prototype.setContent=function(){var $tip=this.tip();var title=this.getTitle();$tip.find(".tooltip-inner")[this.options.html?"html":"text"](title);$tip.removeClass("fade in top bottom left right")};Tooltip.prototype.hide=function(callback){var that=this;var $tip=$(this.$tip);var e=$.Event("hide.bs."+this.type);function complete(){if(that.hoverState!="in")$tip.detach();that.$element.removeAttr("aria-describedby").trigger("hidden.bs."+that.type);callback&&callback()}this.$element.trigger(e);if(e.isDefaultPrevented())return;$tip.removeClass("in");$.support.transition&&$tip.hasClass("fade")?$tip.one("bsTransitionEnd",complete).emulateTransitionEnd(Tooltip.TRANSITION_DURATION):complete();this.hoverState=null;return this};Tooltip.prototype.fixTitle=function(){var $e=this.$element;if($e.attr("title")||typeof $e.attr("data-original-title")!="string"){$e.attr("data-original-title",$e.attr("title")||"").attr("title","")}};Tooltip.prototype.hasContent=function(){return this.getTitle()};Tooltip.prototype.getPosition=function($element){$element=$element||this.$element;var el=$element[0];var isBody=el.tagName=="BODY";var elRect=el.getBoundingClientRect();if(elRect.width==null){elRect=$.extend({},elRect,{width:elRect.right-elRect.left,height:elRect.bottom-elRect.top})}var elOffset=isBody?{top:0,left:0}:$element.offset();var scroll={scroll:isBody?document.documentElement.scrollTop||document.body.scrollTop:$element.scrollTop()};var outerDims=isBody?{width:$(window).width(),height:$(window).height()}:null;return $.extend({},elRect,scroll,outerDims,elOffset)};Tooltip.prototype.getCalculatedOffset=function(placement,pos,actualWidth,actualHeight){return placement=="bottom"?{top:pos.top+pos.height,left:pos.left+pos.width/2-actualWidth/2}:placement=="top"?{top:pos.top-actualHeight,left:pos.left+pos.width/2-actualWidth/2}:placement=="left"?{top:pos.top+pos.height/2-actualHeight/2,left:pos.left-actualWidth}:{top:pos.top+pos.height/2-actualHeight/2,left:pos.left+pos.width}};Tooltip.prototype.getViewportAdjustedDelta=function(placement,pos,actualWidth,actualHeight){var delta={top:0,left:0};if(!this.$viewport)return delta;var viewportPadding=this.options.viewport&&this.options.viewport.padding||0;var viewportDimensions=this.getPosition(this.$viewport);if(/right|left/.test(placement)){var topEdgeOffset=pos.top-viewportPadding-viewportDimensions.scroll;var bottomEdgeOffset=pos.top+viewportPadding-viewportDimensions.scroll+actualHeight;if(topEdgeOffset<viewportDimensions.top){delta.top=viewportDimensions.top-topEdgeOffset}else if(bottomEdgeOffset>viewportDimensions.top+viewportDimensions.height){delta.top=viewportDimensions.top+viewportDimensions.height-bottomEdgeOffset}}else{var leftEdgeOffset=pos.left-viewportPadding;var rightEdgeOffset=pos.left+viewportPadding+actualWidth;if(leftEdgeOffset<viewportDimensions.left){delta.left=viewportDimensions.left-leftEdgeOffset}else if(rightEdgeOffset>viewportDimensions.right){delta.left=viewportDimensions.left+viewportDimensions.width-rightEdgeOffset}}return delta};Tooltip.prototype.getTitle=function(){var title;var $e=this.$element;var o=this.options;title=$e.attr("data-original-title")||(typeof o.title=="function"?o.title.call($e[0]):o.title);return title};Tooltip.prototype.getUID=function(prefix){do prefix+=~~(Math.random()*1e6);while(document.getElementById(prefix));return prefix};Tooltip.prototype.tip=function(){if(!this.$tip){this.$tip=$(this.options.template);if(this.$tip.length!=1){throw new Error(this.type+" `template` option must consist of exactly 1 top-level element!")}}return this.$tip};Tooltip.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".tooltip-arrow")};Tooltip.prototype.enable=function(){this.enabled=true};Tooltip.prototype.disable=function(){this.enabled=false};Tooltip.prototype.toggleEnabled=function(){this.enabled=!this.enabled};Tooltip.prototype.toggle=function(e){var self=this;if(e){self=$(e.currentTarget).data("bs."+this.type);if(!self){self=new this.constructor(e.currentTarget,this.getDelegateOptions());$(e.currentTarget).data("bs."+this.type,self)}}if(e){self.inState.click=!self.inState.click;if(self.isInStateTrue())self.enter(self);else self.leave(self)}else{self.tip().hasClass("in")?self.leave(self):self.enter(self)}};Tooltip.prototype.destroy=function(){var that=this;clearTimeout(this.timeout);this.hide(function(){that.$element.off("."+that.type).removeData("bs."+that.type);if(that.$tip){that.$tip.detach()}that.$tip=null;that.$arrow=null;that.$viewport=null})};function Plugin(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.tooltip");var options=typeof option=="object"&&option;if(!data&&/destroy|hide/.test(option))return;if(!data)$this.data("bs.tooltip",data=new Tooltip(this,options));if(typeof option=="string")data[option]()})}var old=$.fn.tooltip;$.fn.tooltip=Plugin;$.fn.tooltip.Constructor=Tooltip;$.fn.tooltip.noConflict=function(){$.fn.tooltip=old;return this}}(jQuery);+function($){"use strict";var Popover=function(element,options){this.init("popover",element,options)};if(!$.fn.tooltip)throw new Error("Popover requires tooltip.js");Popover.VERSION="3.3.6";Popover.DEFAULTS=$.extend({},$.fn.tooltip.Constructor.DEFAULTS,{placement:"right",trigger:"click",content:"",template:'<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'});Popover.prototype=$.extend({},$.fn.tooltip.Constructor.prototype);Popover.prototype.constructor=Popover;Popover.prototype.getDefaults=function(){return Popover.DEFAULTS};Popover.prototype.setContent=function(){var $tip=this.tip();var title=this.getTitle();var content=this.getContent();$tip.find(".popover-title")[this.options.html?"html":"text"](title);$tip.find(".popover-content").children().detach().end()[this.options.html?typeof content=="string"?"html":"append":"text"](content);$tip.removeClass("fade top bottom left right in");if(!$tip.find(".popover-title").html())$tip.find(".popover-title").hide()};Popover.prototype.hasContent=function(){return this.getTitle()||this.getContent()};Popover.prototype.getContent=function(){var $e=this.$element;var o=this.options;return $e.attr("data-content")||(typeof o.content=="function"?o.content.call($e[0]):o.content)};Popover.prototype.arrow=function(){return this.$arrow=this.$arrow||this.tip().find(".arrow")};function Plugin(option){return this.each(function(){var $this=$(this);var data=$this.data("bs.popover");var options=typeof option=="object"&&option;if(!data&&/destroy|hide/.test(option))return;if(!data)$this.data("bs.popover",data=new Popover(this,options));if(typeof option=="string")data[option]()})}var old=$.fn.popover;$.fn.popover=Plugin;$.fn.popover.Constructor=Popover;$.fn.popover.noConflict=function(){$.fn.popover=old;return this}}(jQuery);