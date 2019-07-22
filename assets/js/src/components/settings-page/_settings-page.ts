/* =======================================
   SETTINGS PAGE
   ======================================= */

import EnhancedSelect from '../_enhanced-select';
import { ButtonGroup } from '../_button-group';
import { ColorPicker } from '../_color-picker';
import Settings from '../../config/_settings';
import { Switcher } from '../_switcher';
import SmartForm from '../_smart-form';
import TabLoader from '../_tab-loader';

export default class SettingsPage {
	
	$settingsWrapper: JQuery;
	$nav: JQuery;
	$form: JQuery;
	navigationReady: boolean = false;
	numHashParameters: number = 0;
	swal: any = window['swal'];
	tabLoader: TabLoader;
	
	constructor(
		private settings: Settings,
		private enhancedSelect: EnhancedSelect
	) {
		
		// Initialize selectors.
		this.$settingsWrapper = $('.atum-settings-wrapper');
		this.$nav = this.$settingsWrapper.find('.atum-nav');
		this.$form = this.$settingsWrapper.find('#atum-settings');
		
		// URL hash navigation.
		this.setupNavigation();
		
		// Enable switchers.
		Switcher.doSwitchers();
		Switcher.doSwitchers('.js-switch-menu', {
			color    : '#dbf9ff',
			jackColor: '#00b8db',
		});
		
		// Enable ColoPickers.
		ColorPicker.doColorPickers(this.settings.get('selectColor'));
		
		// Enable Select2.
		this.enhancedSelect.doSelect2($('.atum-select2'), {}, true);
		
		// Enable button groups.
		ButtonGroup.doButtonGroups(this.$form);

		// Enable theme selector
        // this.doThemeSelector();

		// Toggle Menu.
		this.toggleMenu();
		
		
		this.$form
			
			// Out of stock threshold option updates.
			.on('change', '#atum_out_stock_threshold', (evt: JQueryEventObject) => this.maybeClearOutStockThreshold($(evt.currentTarget)) )
			
			// Script Runner fields.
			.on('click', '.script-runner .tool-runner', (evt: JQueryEventObject) => this.runScript($(evt.currentTarget)) )

            // Theme selector fields.
            .on('click', '.selector-box, .reset-default-colors', (evt: JQueryEventObject) => this.doThemeSelector($(evt.currentTarget)) );
		
		
		new SmartForm(this.$form, this.settings.get('atumPrefix'));
		
		
		// Footer positioning.
		$(window).on('load', () => {
			
			if ( $('.footer-box').hasClass('no-style') ) {
				$('#wpfooter').css('position', 'relative').show();
				$('#wpcontent').css('min-height', '95vh');
			}
			
		});
	
	}
	
	setupNavigation() {
		
		// Instantiate the loader to register the jQuery.address and the events.
		this.tabLoader = new TabLoader(this.$settingsWrapper, this.$nav);
		
		
		this.$settingsWrapper
		
			// Show the form after the page is loaded.
			.on('atum-tab-loader-init', () => this.$form.show())
		
			// Tab clicked.
			.on('atum-tab-loader-clicked-tab', (evt: JQueryEventObject, $navLink: JQuery, tab: string) => {
			
				if (this.$form.find('.dirty').length) {
					
					// Warn the user about unsaved data.
					this.swal({
						title              : this.settings.get('areYouSure'),
						text               : this.settings.get('unsavedData'),
						type               : 'warning',
						showCancelButton   : true,
						confirmButtonText  : this.settings.get('continue'),
						cancelButtonText   : this.settings.get('cancel'),
						reverseButtons     : true,
						allowOutsideClick  : false
					})
					.then( () => {
						this.moveToTab($navLink);
					},
					(dismiss: string) => {
						$navLink.blur();
					});
					
				}
				else {
					this.moveToTab($navLink);
				}
			
			});
		
	}
	
	hideColors() {
		//console.log("Yeeee haaaaa");
		
		if($("#atum-table-color-settings").length>0) {
			let mode = $("#atum-table-color-settings").data('display');
			$("#atum-table-color-settings .atum-settings-input.atum-color").each(function() {
				if($(this).data('display')!=mode) {
					$(this).parents('tr').hide();
				}
			});
			$("#atum-table-color-settings tr").each(function() {
				if($(this).css('display') == 'none')
					$(this).prependTo($("#atum-table-color-settings tbody"));
			});
		}
	}
	
	moveToTab($navLink: JQuery) {
		
		const $formSettingsWrapper: JQuery = this.$form.find('.form-settings-wrapper');
		
		this.$nav.find('.atum-nav-link.active').not($navLink).removeClass('active');
		$navLink.addClass('active');
		
		$formSettingsWrapper.addClass('overlay');
		
		this.$form.load( `${ $navLink.attr('href') } .form-settings-wrapper`, () => {
			
			Switcher.doSwitchers();
			ColorPicker.doColorPickers(this.settings.get('selectColor'));
			this.enhancedSelect.maybeRestoreEnhancedSelect();
			this.enhancedSelect.doSelect2($('.atum-select2'), {}, true);
			this.$form.find('[data-dependency]').change().removeClass('dirty');
			this.$form.show();
			
			const $inputButton: JQuery = this.$form.find('input:submit');
			
			if ($navLink.parent().hasClass('no-submit')) {
				$inputButton.hide();
			}
			else {
				$inputButton.show();
			}
			
			this.$settingsWrapper.trigger('atum-settings-page-loaded', [ $navLink.data('tab') ]);
			
			if ( 'visual_settings' === $navLink.data('tab') ) {
				this.hideColors();
			}
		});
		
	}
	
	toggleMenu() {
		
		const $navList: JQuery = this.$nav.find('.atum-nav-list');
		
		$('.toogle-menu, .atum-nav-link').click( () => {
			$navList.toggleClass('expand-menu');
		});
		
		$(window).resize( () => {
			$navList.removeClass('expand-menu');
		});
		
	}
	
	maybeClearOutStockThreshold($checkbox: JQuery) {
		
		if ($checkbox.is(':checked') && this.settings.get('isAnyOutStockThresholdSet')) {
			
			this.swal({
				title              : this.settings.get('areYouSure'),
				text               : this.settings.get('outStockThresholdSetClearText'),
				type               : 'question',
				showCancelButton   : true,
				confirmButtonText  : this.settings.get('startFresh'),
				cancelButtonText   : this.settings.get('cancel'),
				reverseButtons     : true,
				allowOutsideClick  : false,
				showLoaderOnConfirm: true,
				preConfirm         : (): Promise<any> => {
					
					return new Promise( (resolve: Function, reject: Function) => {
						
						const data: any = {
							action: this.settings.get('outStockThresholdSetClearScript'),
							token : this.settings.get('runnerNonce'),
						};
						
						$.ajax({
							url     : window['ajaxurl'],
							method  : 'POST',
							dataType: 'json',
							data    : data,
							success : (response: any) => {
								
								if (response.success === true) {
									resolve(response.data);
								}
								else {
									reject(response.data);
								}
								
							}
						});
						
					});
					
				}
			}).then( (message: string) => {
				
				this.swal({
					title            : this.settings.get('done'),
					type             : 'success',
					text             : message,
					confirmButtonText: this.settings.get('ok')
				});
				
			}).catch(this.swal.noop);
			
		}
		else if (!$checkbox.is(':checked')) {
			
			this.swal({
				title: this.settings.get('areYouSure'),
				text : this.settings.get('outStockThresholdDisable'),
				type : 'info'
			});
		}
		
	}
	
	runScript($button: JQuery) {
		
		const $scriptRunner = $button.closest('.script-runner');
		
		this.swal({
			title              : this.settings.get('areYouSure'),
			text               : $scriptRunner.data('confirm'),
			type               : 'warning',
			showCancelButton   : true,
			confirmButtonText  : this.settings.get('run'),
			cancelButtonText   : this.settings.get('cancel'),
			reverseButtons     : true,
			allowOutsideClick  : false,
			showLoaderOnConfirm: true,
			preConfirm: (): Promise<any> => {
				
				return new Promise( (resolve: Function, reject: Function) => {
					
					let $input: JQuery = $scriptRunner.find('#' + $scriptRunner.data('input')),
					    data: any      = {
						    action: $scriptRunner.data('action'),
						    token : this.settings.get('runnerNonce'),
					    };
					
					if ($input.length) {
						data.option = $input.val();
					}
					
					$.ajax({
						url       : window['ajaxurl'],
						method    : 'POST',
						dataType  : 'json',
						data      : data,
						beforeSend: () => {
							$button.prop('disabled', true);
						},
						success   : (response: any) => {
							
							$button.prop('disabled', false);
							
							if (response.success === true) {
								resolve(response.data);
							}
							else {
								reject(response.data);
							}
							
						}
					});
					
				});
				
			}
			
		}).then( (message: string) => {
			
			this.swal({
				title            : this.settings.get('done'),
				type             : 'success',
				text             : message,
				confirmButtonText: this.settings.get('ok')
			}).then( () => {
				this.$settingsWrapper.trigger('atum-settings-script-runner-done', [ $scriptRunner ]);
			});
			
		}).catch(this.swal.noop);
		
	}

    doThemeSelector($element: JQuery) {
        const $formSettingsWrapper: JQuery = this.$form.find('.form-settings-wrapper');

        let $themeSelectorWrapper = $('.theme-selector-wrapper'),
            $themeOptions         = $themeSelectorWrapper.find('.selector-container .selector-box img');

        let themeSelectedValue  = $element.data('value'),
            resetDefault        = $element.data('reset'),
            $radioInput         = $('#' + themeSelectedValue),
            $resetDefaultColors = $('.reset-default-colors');

        $radioInput.prop("checked", true);
        $themeOptions.removeClass('active');
        $element.find('img').addClass('active');
        $resetDefaultColors.data('value', themeSelectedValue);


        $.ajax({
            url   : window['ajaxurl'],
            method: 'POST',
            data  : {
                token : this.settings.get('schemeColorNonce'),
                action: this.settings.get('getSchemeColor'),
                theme : themeSelectedValue,
	            reset : resetDefault
            },
            beforeSend: () => {
                $formSettingsWrapper.addClass('overlay');
            },
            success : (response: any) => {

                if (response.success === true) {
                    console.log('Done');
                    
                    ColorPicker.updateColorPicker($('#atum_bm_primary_color'), response.data.bm_primary_color);
                    ColorPicker.updateColorPicker($('#atum_bm_primary_color_light'), response.data.bm_primary_color_light);
                    ColorPicker.updateColorPicker($('#atum_bm_primary_color_dark'), response.data.bm_primary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_bm_secondary_color'), response.data.bm_secondary_color);
                    ColorPicker.updateColorPicker($('#atum_bm_secondary_color_light'), response.data.bm_secondary_color_light);
                    ColorPicker.updateColorPicker($('#atum_bm_secondary_color_dark'), response.data.bm_secondary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_bm_tertiary_color'), response.data.bm_tertiary_color);
                    ColorPicker.updateColorPicker($('#atum_bm_tertiary_color_light'), response.data.bm_tertiary_color_light);
                    ColorPicker.updateColorPicker($('#atum_bm_tertiary_color_dark'), response.data.bm_tertiary_color_dark);
	                ColorPicker.updateColorPicker($('#atum_bm_danger_color'), response.data.bm_danger_color);
	                ColorPicker.updateColorPicker($('#atum_bm_title_color'), response.data.bm_title_color);
                    ColorPicker.updateColorPicker($('#atum_bm_text_color'), response.data.bm_text_color);
                    ColorPicker.updateColorPicker($('#atum_bm_text_color_2'), response.data.bm_text_color_2);
                    ColorPicker.updateColorPicker($('#atum_bm_text_color_expanded'), response.data.bm_text_color_expanded);
                    ColorPicker.updateColorPicker($('#atum_bm_border_color'), response.data.bm_border_color);
                    ColorPicker.updateColorPicker($('#atum_bm_bg_1_color'), response.data.bm_bg_1_color);
                    ColorPicker.updateColorPicker($('#atum_bm_bg_2_color'), response.data.bm_bg_2_color);
                    
                    ColorPicker.updateColorPicker($('#atum_dm_primary_color'), response.data.dm_primary_color);
                    ColorPicker.updateColorPicker($('#atum_dm_primary_color_light'), response.data.dm_primary_color_light);
                    ColorPicker.updateColorPicker($('#atum_dm_primary_color_dark'), response.data.dm_primary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_dm_secondary_color'), response.data.dm_secondary_color);
                    ColorPicker.updateColorPicker($('#atum_dm_secondary_color_light'), response.data.dm_secondary_color_light);
                    ColorPicker.updateColorPicker($('#atum_dm_secondary_color_dark'), response.data.dm_secondary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_dm_tertiary_color'), response.data.dm_tertiary_color);
                    ColorPicker.updateColorPicker($('#atum_dm_tertiary_color_light'), response.data.dm_tertiary_color_light);
                    ColorPicker.updateColorPicker($('#atum_dm_tertiary_color_dark'), response.data.dm_tertiary_color_dark);
	                ColorPicker.updateColorPicker($('#atum_dm_danger_color'), response.data.dm_danger_color);
	                ColorPicker.updateColorPicker($('#atum_dm_title_color'), response.data.dm_title_color);
                    ColorPicker.updateColorPicker($('#atum_dm_text_color'), response.data.dm_text_color);
                    ColorPicker.updateColorPicker($('#atum_dm_text_color_2'), response.data.dm_text_color_2);
                    ColorPicker.updateColorPicker($('#atum_dm_text_color_expanded'), response.data.dm_text_color_expanded);
                    ColorPicker.updateColorPicker($('#atum_dm_border_color'), response.data.dm_border_color);
                    ColorPicker.updateColorPicker($('#atum_dm_bg_1_color'), response.data.dm_bg_1_color);
                    ColorPicker.updateColorPicker($('#atum_dm_bg_2_color'), response.data.dm_bg_2_color);
                    
                    ColorPicker.updateColorPicker($('#atum_hc_primary_color'), response.data.hc_primary_color);
                    ColorPicker.updateColorPicker($('#atum_hc_primary_color_light'), response.data.hc_primary_color_light);
                    ColorPicker.updateColorPicker($('#atum_hc_primary_color_dark'), response.data.hc_primary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_hc_secondary_color'), response.data.hc_secondary_color);
                    ColorPicker.updateColorPicker($('#atum_hc_secondary_color_light'), response.data.hc_secondary_color_light);
                    ColorPicker.updateColorPicker($('#atum_hc_secondary_color_dark'), response.data.hc_secondary_color_dark);
                    ColorPicker.updateColorPicker($('#atum_hc_tertiary_color'), response.data.hc_tertiary_color);
                    ColorPicker.updateColorPicker($('#atum_hc_tertiary_color_light'), response.data.hc_tertiary_color_light);
                    ColorPicker.updateColorPicker($('#atum_hc_tertiary_color_dark'), response.data.hc_tertiary_color_dark);
	                ColorPicker.updateColorPicker($('#atum_hc_danger_color'), response.data.hc_danger_color);
	                ColorPicker.updateColorPicker($('#atum_hc_title_color'), response.data.hc_title_color);
                    ColorPicker.updateColorPicker($('#atum_hc_text_color'), response.data.hc_text_color);
                    ColorPicker.updateColorPicker($('#atum_hc_text_color_2'), response.data.hc_text_color_2);
                    ColorPicker.updateColorPicker($('#atum_hc_text_color_expanded'), response.data.hc_text_color_expanded);
                    ColorPicker.updateColorPicker($('#atum_hc_border_color'), response.data.hc_border_color);
                    ColorPicker.updateColorPicker($('#atum_hc_bg_1_color'), response.data.hc_bg_1_color);
                    ColorPicker.updateColorPicker($('#atum_hc_bg_2_color'), response.data.hc_bg_2_color);
                    
                    if (themeSelectedValue === 'dark_mode') {
                        $('.section-title h2 span').html('Dark');
                    }
                    else if(themeSelectedValue === 'hc_mode'){
                        $('.section-title h2 span').html('High Contrast');
                    }
                    else{
                        $('.section-title h2 span').html('Branded');
                    }

                    $formSettingsWrapper.removeClass('overlay');
                    
                    $('.button-primary').click();
                }
                else {
                    console.log('Error');
                }

            }
        });

    }
	
}