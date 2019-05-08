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
		
		// Toggle Menu.
		this.toggleMenu();
		
		// Change menu theme
		$('input[name="interface_style"]').on('change', () => {
			this.changeTheme();
		});
		
		
		this.$form
			
			// Out of stock threshold option updates.
			.on('change', '#atum_out_stock_threshold', (evt: JQueryEventObject) => this.maybeClearOutStockThreshold($(evt.currentTarget)) )
			
			// Script Runner fields.
			.on('click', '.script-runner .tool-runner', (evt: JQueryEventObject) => this.runScript($(evt.currentTarget)) );
		
		
		new SmartForm(this.$form, this.settings);
		
		
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
	
	changeTheme() {
		
		// Change Settings theme.
		this.$nav.toggleClass('atum-nav-light');
		$('.section-general-title').toggleClass('section-general-title-light');
		$('.section-title').toggleClass('section-title-light');
		$('.section-fields').toggleClass('section-field-light');
		$('.switch-interface-style').toggleClass('bg-light');
		
		$.ajax({
			url   : window['ajaxurl'],
			method: 'POST',
			data  : {
				token : this.settings.get('menuThemeNonce'),
				action: this.settings.get('changeSettingsMenuStyle'),
				theme : $('.js-switch-menu').is(':checked') ? 1 : 0,
			},
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
	
}