/* =======================================
   SMART FORM
   ======================================= */

import { BeforeUnload } from './_before-unload';
import Settings from '../config/_settings';

export default class SmartForm {
	
	/**
	 * Constructor.
	 *
	 * @param JQuery $form  The form selector that will control.
	 */
	constructor(
		private $form: JQuery,
		private settings: Settings
	) {
		
		this.$form
		
			// Set the dirty fields.
			.on('change', ':input, select, textarea', (evt: JQueryEventObject) => {
				
				if(!$('.atum-nav-link.active').parent().hasClass('no-submit')) {
					$(evt.currentTarget).addClass('dirty');
				}
				
			})
			
			// Remove the dirty mark if the user tries to save.
			.on('click', 'input[type=submit]', (evt: JQueryEventObject) => {
				$form.find('.dirty').removeClass('dirty');
				$form.find('.form-settings-wrapper').addClass('overlay');
			})
			
			// Field dependencies.
			.on('change','[data-dependency]', (evt: JQueryEventObject) => {
				
				let $field: JQuery  = $(evt.currentTarget),
				    value: any      = $field.val(),
				    dependency: any = $field.data('dependency');
				
				if ($.isArray(dependency)) {
					
					$.each(dependency, (index: number, dependencyElem: any) =>  {
						this.checkDependency($field, dependencyElem, value);
					});
					
				}
				else {
					this.checkDependency($field, dependency, value);
				}
				
			})
			
			.find('[data-dependency]').each( (index: number, elem: Element) => {
			
				$(elem).change().removeClass('dirty');
				
			});
		
		
		// Before unload alert.
		BeforeUnload.addPrompt( () => !$form.find('.dirty').length );
		
	}
	
	checkDependency($field: JQuery, dependency: any, value: any) {
		
		let $dependantInput: JQuery,
		    $dependantWraper: JQuery,
		    visibility: boolean;
		
		// Do no apply to not checked radio buttons.
		if ($field.is(':radio') && !$field.is(':checked')) {
			return;
		}
		
		if ($field.is(':checkbox') || $field.is(':radio')) {
			visibility = (value === dependency.value && $field.is(':checked')) || (value !== dependency.value && !$field.is(':checked'));
		}
		else {
			visibility = value === dependency.value;
		}
		
		if (dependency.hasOwnProperty('section')) {
			$dependantWraper = this.$form.find('[data-section="' + dependency.section + '"]');
		}
		else if (dependency.hasOwnProperty('field')) {
			
			$dependantInput = $( `#${ this.settings.get('atumPrefix') + dependency.field}` );
			
			if ($dependantInput.length) {
				$dependantWraper = $dependantInput.closest('tr').find('th, td');
			}
			
		}
		
		if (typeof $dependantWraper !== 'undefined' && $dependantWraper.length) {
			
			// Show/Hide the field.
			if (visibility === true) {
				if (!dependency.hasOwnProperty('animated') || dependency.animated === true) {
					$dependantWraper.slideDown('fast');
				}
				else {
					$dependantWraper.show();
				}
			}
			else {
				
				if (!dependency.hasOwnProperty('animated') || dependency.animated === true) {
					$dependantWraper.slideUp('fast');
				}
				else {
					$dependantWraper.hide();
				}
				
				// Check if we have to reset the dependant input to default when hiding the field.
				if (dependency.hasOwnProperty('resetDefault') && dependency.resetDefault === true) {
					
					const defaultValue: string = $dependantInput.data('default'),
					      curValue: string     = $dependantInput.val();
					
					if ($dependantInput.is(':radio') || $dependantInput.is(':checkbox')) {
						$dependantInput.prop('checked', defaultValue === curValue);
					}
					else {
						$dependantInput.val(defaultValue);
					}
					
					$dependantInput.change();
					
				}
				
			}
			
		}
		
	}
	
}