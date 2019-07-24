<?php
/**
 * Add ATUM color schemes
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2019 Stock Management Labs™
 *
 * @since          1.5.9
 */

namespace Atum\Components;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;


class AtumColors {

	/**
	 * User meta key where are saved the visual settings per user
	 */
	const VISUAL_SETTINGS_USER_META = 'visual_settings';

	/**
	 * The singleton instance holder
	 *
	 * @var AtumColors
	 */
	private static $instance;

	/**
	 * The ATUM colors
	 *
	 * @var array
	 */
	private $colors = array(
		'gray_100'   => '#F8F9FA',
		'gray_500'   => '#ADB5BD',
		'dark'       => '#343A40',
		'white'      => '#FFFFFF',
		'black'      => '#000000',
		'blue'       => '#00B8DB',
		'blue_dark'  => '#27283B',
		'blue_light' => '#DBF9FF',
		'green'      => '#69C61D',
		'orange'     => '#EFAF00',
	);

	/**
	 * AtumColors singleton constructor
	 * 
	 * @since 1.5.9
	 */
	private function __construct() {

		// Get the Visual Settings from the user meta.
		$visual_settings = Helpers::get_atum_user_meta( self::VISUAL_SETTINGS_USER_META );
		$visual_settings = $visual_settings ?: [];
		$theme           = ! empty( $visual_settings['theme'] ) ? $visual_settings['theme'] : 'branded_mode';

		switch ( $theme ) {
			case 'dark_mode':
				$prefix = 'dm_';
				break;

			case 'hc_mode':
				$prefix = 'hc_';
				break;

			default:
				$prefix = 'bm_';
				break;
		}

		$this->colors['gray_500_rgb']          = $this->convert_hexadecimal_to_rgb( $this->colors['gray_500'] );
		$this->colors['white_rgb']             = $this->convert_hexadecimal_to_rgb( $this->colors['white'] );
		$this->colors['black_rgb']             = $this->convert_hexadecimal_to_rgb( $this->colors['black'] );
		$this->colors['primary_color']         = ! empty( $visual_settings[ "{$prefix}primary_color" ] ) ? $visual_settings[ "{$prefix}primary_color" ] : $this->colors['blue'];
		$this->colors['primary_color_rgb']     = $this->convert_hexadecimal_to_rgb( $this->colors['primary_color'] );
		$this->colors['primary_color_light']   = ! empty( $visual_settings[ "{$prefix}primary_color_light" ] ) ? $visual_settings[ "{$prefix}primary_color_light" ] : '#F5FDFF';
		$this->colors['primary_color_dark']    = ! empty( $visual_settings[ "{$prefix}primary_color_dark" ] ) ? $visual_settings[ "{$prefix}primary_color_dark" ] : '#DBF9FF';
		$this->colors['secondary_color']       = ! empty( $visual_settings[ "{$prefix}secondary_color" ] ) ? $visual_settings[ "{$prefix}secondary_color" ] : $this->colors['orange'];
		$this->colors['secondary_color_rgb']   = $this->convert_hexadecimal_to_rgb( $this->colors['secondary_color'] );
		$this->colors['secondary_color_light'] = ! empty( $visual_settings[ "{$prefix}secondary_color_light" ] ) ? $visual_settings[ "{$prefix}secondary_color_light" ] : '#FFF4D6';
		$this->colors['secondary_color_dark']  = ! empty( $visual_settings[ "{$prefix}secondary_color_dark" ] ) ? $visual_settings[ "{$prefix}secondary_color_dark" ] : '#FFEDBC';
		$this->colors['tertiary_color']        = ! empty( $visual_settings[ "{$prefix}tertiary_color" ] ) ? $visual_settings[ "{$prefix}tertiary_color" ] : $this->colors['green'];
		$this->colors['tertiary_color_rgb']    = $this->convert_hexadecimal_to_rgb( $this->colors['tertiary_color'] );
		$this->colors['tertiary_color_light']  = ! empty( $visual_settings[ "{$prefix}tertiary_color_light" ] ) ? $visual_settings[ "{$prefix}tertiary_color_light" ] : $this->colors['green'];
		$this->colors['tertiary_color_dark']   = ! empty( $visual_settings[ "{$prefix}tertiary_color_dark" ] ) ? $visual_settings[ "{$prefix}tertiary_color_dark" ] : '#B4F0C9';
		$this->colors['text_color']            = ! empty( $visual_settings[ "{$prefix}text_color" ] ) ? $visual_settings[ "{$prefix}text_color" ] : '#6C757D';
		$this->colors['text_color_rgb']        = $this->convert_hexadecimal_to_rgb( $this->colors['text_color'] );
		$this->colors['text_color_2']          = ! empty( $visual_settings[ "{$prefix}text_color_2" ] ) ? $visual_settings[ "{$prefix}text_color_2" ] : $this->colors['gray_500'];
		$this->colors['text_color_2_rgb']      = $this->convert_hexadecimal_to_rgb( $this->colors['text_color_2'] );
		$this->colors['text_color_expanded']   = ! empty( $visual_settings[ "{$prefix}text_color_expanded" ] ) ? $visual_settings[ "{$prefix}text_color_expanded" ] : $this->colors['white'];
		$this->colors['border_color']          = ! empty( $visual_settings[ "{$prefix}border_color" ] ) ? $visual_settings[ "{$prefix}border_color" ] : '#E9ECEF';
		$this->colors['border_color_rgb']      = $this->convert_hexadecimal_to_rgb( $this->colors['border_color'] );
		$this->colors['bg_1_color']            = ! empty( $visual_settings[ "{$prefix}bg_1_color" ] ) ? $visual_settings[ "{$prefix}bg_1_color" ] : $this->colors['white'];
		$this->colors['bg_1_color_rgb']        = $this->convert_hexadecimal_to_rgb( $this->colors['bg_1_color'] );
		$this->colors['bg_2_color']            = ! empty( $visual_settings[ "{$prefix}bg_2_color" ] ) ? $visual_settings[ "{$prefix}bg_2_color" ] : $this->colors['gray_100'];
		$this->colors['danger_color']          = ! empty( $visual_settings[ "{$prefix}danger_color" ] ) ? $visual_settings[ "{$prefix}danger_color" ] : '#FF4848';
		$this->colors['title_color']           = ! empty( $visual_settings[ "{$prefix}title_color" ] ) ? $visual_settings[ "{$prefix}title_color" ] : $this->colors['blue_dark'];

		// Add the Visual Settings to ATUM settings.
		if ( ModuleManager::is_module_active( 'visual_settings' ) && AtumCapabilities::current_user_can( 'edit_visual_settings' ) ) {
			add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ) );
			add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ) );
		}

	}

	/**
	 * Convert hexadecimal to rgb
	 *
	 * @param string $hex_value
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public function convert_hexadecimal_to_rgb( $hex_value ) {

		list( $r, $g, $b ) = sscanf( $hex_value, '#%02x%02x%02x' );

		return "$r, $g, $b";

	}

	/**
	 * Get High Contrast mode colors
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public function get_high_contrast_mode_colors() {

		$secondary_color       = $this->colors['primary_color'];
		$secondary_color_light = $this->colors['primary_color_light'];
		$secondary_color_dark  = $this->colors['primary_color_dark'];
		$tertiary_color        = $this->colors['primary_color'];
		$tertiary_color_light  = $this->colors['primary_color_light'];
		$text_color            = '#016B7F';
		$text_color_2          = '#27283B';
		$text_color_expanded   = '#FFFFFF';
		$border_color          = '#ADB5BD';
		$bg_1_color            = '#FFFFFF';
		$bg_2_color            = '#FFFFFF';
		$danger_color          = '#FF4848';
		$title_color           = $this->colors['primary_color'];

		$scheme = ":root {
			--primary: {$this->colors['primary_color']};
			--primary-hover: {$this->colors['white']};
			--primary-hover-text: {$this->colors['primary_color']};
			--primary-hover-border: solid 1px {$this->colors['primary_color']};
			--primary-var-dark: {$this->colors['primary_color']};
			--primary-var-text2: {$this->colors['primary_color']};
			--wp-yiq-text-light: $text_color_expanded;
			--danger: $danger_color;
			--orange: $secondary_color;
			--dash-blue-trans: {$this->colors['primary_color_light']};
			--orange-light-2: $secondary_color_light;
			--orange-light: $secondary_color_dark;
			--secondary: $secondary_color;
			--atum-pagination-border-disabled: $border_color;
			--secondary-hover: {$this->colors['white']};
			--secondary-hover-text: $secondary_color;
			--secondary-hover-border: solid 1px $secondary_color;
			--purple-pl: {$this->colors['primary_color']};
		    --purple-pl-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
		    --wp-pink-darken-expanded: {$this->colors['primary_color']};
			--text-color-var1:$text_color_2;
			--text-color-var2: $text_color_2;
			--white: {$this->colors['white']};
			--white-shadow: rgba({$this->colors['border_color_rgb']}, 0.2);
			--green-light: {$this->colors['primary_color_dark']};
			--green-light-2: $tertiary_color_light;
			--tertiary: $tertiary_color;
			--tertiary-var: $tertiary_color;
			--tertiary-hover: {$this->colors['white']};
			--tertiary-hover-text: $tertiary_color;
			--tertiary-hover-border: solid 1px $tertiary_color;
			--atum-table-row-even: $bg_2_color;
			--blue-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
		    --atum-table-row-odd: $bg_1_color;
		    --atum-row-active: {$this->colors['primary_color_dark']};
			--atum-table-views-tabs: $text_color;
			--atum-input-text-search: $text_color;
			--atum-select2-selected-bg: {$this->colors['primary_color_dark']};
			--atum-table-bg: $bg_1_color;
			--js-scroll-bg: {$this->colors['primary_color']};
			--atum-table-icon-active: $text_color;
			--atum-table-text-active: $text_color;
			--atum-table-text-expanded: $text_color_expanded;
			--atum-table-search-text: $text_color_expanded;
			--atum-table-search-text-disabled: $text_color_2;
			--atum-settings-heads-bg: {$this->colors['primary_color']};
			--atum-settings-nav-link: {$this->colors['primary_color']};
			--atum-pagination-bg-disabled: $bg_2_color;
			--atum-pagination-disabled: $text_color_2;
			--atum-pagination-text: $text_color_2;
			--atum-border: $border_color;
			--atum-settings-input-border: $border_color;
			--atum-settings-border-color: $border_color;
			--atum-settings-separator: $border_color;
			--dash-border: $border_color;
			--atum-border-expanded: $border_color;
			--atum-footer-totals: $bg_1_color;
			--atum-footer-bg: {$this->colors['white']};
			--atum-dropdown-toggle-bg: {$this->colors['gray_100']};
			--atum-icon-tree: $text_color;
			--atum-settings-nav-link-selected-bg: {$this->colors['primary_color_dark']};
			--title-color: $title_color;
			--overflow-opacity-rigth: linear-gradient(to right, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--dash-card-text: {$this->colors['blue_dark']};
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-input-placeholder: $text_color;
			--atum-settings-input-text: $text_color;
			--atum-settings-wp-color-text: $text_color;
			--atum-table-row-child-variation: {$this->colors['primary_color_dark']};
			--atum-table-row-child-variation-even: {$this->colors['primary_color_light']};
			--dash-subscription-input: transparent;
			--dash-card-bg: $bg_1_color;
			--dash-h5-text: {$this->colors['primary_color']};
			--dash-nice-select-bg: $bg_1_color;
			--dash-nice-select-list-bg: $bg_1_color;
			--dash-nice-select-border: $border_color;
		    --dash-nice-select-hover: {$this->colors['primary_color']};
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
		    --dash-nice-select-arrow-color: $text_color;
		    --dash-nice-select-option-hover: {$this->colors['primary_color_light']};
		    --dash-nice-select-option-selected-bg: {$this->colors['primary_color_light']};
			--dash-input-group-bg: rgba({$this->colors['bg_1_color_rgb']}, 0.3);
			--dash-input-group-shadow: rgba({$this->colors['bg_1_color_rgb']}, 0.3);
			--dash-input-placeholder: {$this->colors['primary_color']};
			--dash-statistics-ticks: $text_color;
			--dash-statistics-grid-lines: rgba({$this->colors['text_color_rgb']}, 0.2);
			--dash-statistics-chart-type-bg: transparent;
			--dash-statistics-chart-type-selected-bg: $secondary_color;
			--dash-statistics-chart-type-selected-text: $text_color_expanded;
			--dash-statistics-legend-switch-bg: transparent;
			--dash-widget-separator: $border_color;
			--dash-widget-icon: $border_color;
			--dash-stats-data-widget-primary: {$this->colors['primary_color']};
			--dash-stock-control-chart-border: $bg_1_color;
			--dash-next-text: $text_color_2;
			--dash-video-title: {$this->colors['dark']};
			--dash-video-subs-text: $text_color_2;
			--atum-marketing-popup-bg: $bg_1_color;
			--dash-widget-current-stock-value-text: white;
			--dash-widget-current-stock-value-bg: {$this->colors['primary_color']};
			--atum-select2-border: $border_color;
			--atum-version: {$this->colors['blue_dark']};
			--atum-version-bg: rgba({$this->colors['black_rgb']}, 0.1);
			--atum-table-filter-dropdown: {$this->colors['blue_dark']};
			--atum-footer-title: {$this->colors['blue_dark']};
			--atum-table-link-text: $text_color;
		}";

		return apply_filters( 'atum/get_high_contrast_mode_colors', $scheme );

	}

	/**
	 * Get Dark Mode colors
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public function get_dark_mode_colors() {

		$bg_1_color     = '#31324A';
		$bg_1_color_rgb = $this->convert_hexadecimal_to_rgb( $bg_1_color );
		$bg_2_color     = '#3B3D5A';

		$scheme = ":root {
			--wp-yiq-text-light: {$this->colors['text_color_2']};
			--danger: {$this->colors['danger_color']};
			--blue-light: $bg_1_color;
			--primary: {$this->colors['primary_color']};
			--primary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-text: {$this->colors['blue_dark']};
			--primary-hover-border: 1px solid transparent;
			--primary-var-dark: {$this->colors['primary_color_dark']};
			--primary-var-text2: {$this->colors['text_color_2']};
			--orange: {$this->colors['secondary_color_dark']};
			--atum-pagination-border-disabled: rgba({$this->colors['border_color_rgb']}, 0.0);
			--orange-light-2: {$this->colors['secondary_color']};
			--orange-light: {$this->colors['secondary_color']};
			--secondary: {$this->colors['secondary_color']}; 
			--secondary-hover: rgba({$this->colors['secondary_color_rgb']}, 0.6); 
			--secondary-hover-text: {$this->colors['blue_dark']};
			--secondary-hover-border: 1px solid transparent;
			--text-color-var1: {$this->colors['text_color']};
			--text-color-var2: {$this->colors['text_color']};
			--gray-500: {$this->colors['text_color']};
			--green-light: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--green-light-2: {$this->colors['tertiary_color_light']};
			--white: {$this->colors['white']};
			--tertiary: {$this->colors['tertiary_color']};
			--tertiary-var: {$this->colors['tertiary_color_light']};
			--tertiary-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--tertiary-hover-text: {$this->colors['blue_dark']};
			--tertiary-hover-border: 1px solid transparent;
			--atum-table-row-even: $bg_2_color;
			--blue-hover: rgba({$this->colors['primary_color_rgb']},0.6);
		    --atum-table-row-odd: $bg_1_color;
		    --atum-column-groups-bg: {$this->colors['blue_dark']};
		    --atum-border: rgba({$this->colors['border_color_rgb']}, 0.2);
		    --atum-border-expanded: rgba({$this->colors['border_color_rgb']}, 0.2);
		    --popover-black-shadow: rgba({$this->colors['border_color_rgb']}, 0.2);
		    --atum-table-row-variation-text: {$this->colors['text_color_2']};
		    --atum-row-active: {$this->colors['primary_color_light']};
		    --js-scroll-bg: {$this->colors['text_color']};
			--atum-table-views-tabs: {$this->colors['text_color']};
			--atum-table-views-tabs-active-text: {$this->colors['text_color_2']};
			--atum-input-text-search: {$this->colors['text_color_2']};
			--atum-select2-selected-bg: {$this->colors['primary_color_light']};
			--atum-table-bg: $bg_1_color;
			--atum-table-icon-active: {$this->colors['text_color_2']};
			--atum-table-text-active: {$this->colors['text_color_2']};
			--atum-table-text-expanded: {$this->colors['text_color_expanded']};
			--atum-table-search-text: {$this->colors['blue_dark']};
			--atum-table-search-text-disabled: {$this->colors['blue_dark']};
			--atum-pagination-bg-disabled: $bg_2_color;
			--atum-pagination-disabled: {$this->colors['text_color']};
			--atum-pagination-text: {$this->colors['text_color_2']};
			--atum-footer-totals: {$this->colors['blue_dark']};
			--atum-footer-bg: {$this->colors['blue_dark']};
			--atum-dropdown-toggle-bg: $bg_2_color;
			--atum-icon-tree: {$this->colors['text_color']};
			--atum-settings-nav-bg: $bg_1_color;
			--atum-settings-heads-bg: $bg_1_color;
			--atum-settings-nav-link: {$this->colors['text_color']};
			--atum-settings-section-bg: $bg_1_color;
			--atum-settings-nav-link-selected-bg: {$this->colors['primary_color_light']};
			--title-color: {$this->colors['title_color']};
			--atum-settings-btn-save: {$this->colors['primary_color']};
			--atum-settings-btn-save-hover: rgba({$this->colors['primary_color_rgb']},0.7);
			--atum-settings-text-logo: {$this->colors['text_color']};
			--atum-settings-separator: rgba({$this->colors['border_color_rgb']},0.2);
			--overflow-opacity-rigth: linear-gradient(to right, rgba($bg_1_color_rgb,0), rgba($bg_1_color_rgb,0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba($bg_1_color_rgb,0), rgba($bg_1_color_rgb,0.9));
			--dash-border: rgba({$this->colors['text_color_rgb']}, 0.5);
			--dash-card-text: {$this->colors['text_color']};
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-add-widget-color-dark: {$this->colors['blue_dark']};
			--dash-input-placeholder: {$this->colors['text_color']};
			--atum-settings-input-text: {$this->colors['text_color_2']};
			--atum-settings-border-color: rgba({$this->colors['border_color_rgb']}, 0.2);
			--atum-settings-wp-color-text: {$this->colors['text_color']};
			--atum-table-row-child-variation: {$this->colors['primary_color_light']};
			--atum-table-row-child-variation-even: {$this->colors['primary_color_light']};
			--dash-subscription-input: transparent;
			--dash-card-bg: $bg_1_color;
			--dash-h5-text: {$this->colors['primary_color']};
			--dash-nice-select-bg: $bg_1_color;
			--dash-nice-select-list-bg: $bg_1_color;
			--dash-nice-select-border: rgba({$this->colors['text_color_rgb']}, 0.5);
		    --dash-nice-select-hover: {$this->colors['primary_color_light']};
		    --dash-nice-select-arrow-color: {$this->colors['text_color']};
		    --dash-nice-select-option-hover: {$this->colors['primary_color_light']};
		    --dash-nice-select-option-selected-bg: {$this->colors['primary_color_light']};
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
			--dash-input-group-bg: rgba(0, 0, 0, 0.3);
			--dash-input-group-shadow: rgba(0, 0, 0, 0.3);
			--dash-input-placeholder: {$this->colors['primary_color']};
			--dash-statistics-ticks: {$this->colors['text_color']};
			--dash-statistics-grid-lines: rgba({$this->colors['text_color_rgb']}, 0.2);
			--dash-statistics-chart-type-bg: transparent;
			--dash-statistics-chart-type-selected-bg: $bg_1_color;
			--dash-statistics-chart-type-selected-text: {$this->colors['secondary_color']};
			--dash-statistics-legend-switch-bg: transparent;
			--dash-widget-separator: rgba({$this->colors['border_color_rgb']}, 0.2);
		    --dash-widget-warning: {$this->colors['secondary_color']};
			--dash-stats-data-widget-primary: {$this->colors['primary_color_light']};
			--dash-stock-control-chart-border: {$this->colors['blue_dark']};
			--dash-next-text: {$this->colors['gray_500']};
			--dash-video-title: {$this->colors['text_color']};
			--dash-video-subs-text: {$this->colors['gray_500']};
			--atum-marketing-popup-bg: $bg_1_color;
			--atum-select2-border: rgba({$this->colors['border_color_rgb']},0.5);
			--atum-add-widget-bg: {$this->colors['blue_dark']};
			--atum-add-widget-title: {$this->colors['text_color']};
			--atum-add-widget-separator: rgba({$this->colors['border_color_rgb']},0.2);
			--atum-table-filter-dropdown: {$this->colors['white']};
			--atum-footer-title: {$this->colors['white']};
			--atum-footer-link: {$this->colors['primary_color']};
			--atum-footer-text: {$this->colors['white']};
			--atum-table-link-text: {$this->colors['text_color_expanded']};
		}";

		return apply_filters( 'atum/get_dark_mode_colors', $scheme );

	}

	/**
	 * Get Branded Mode colors
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public function get_branded_mode_colors() {

		$scheme = ":root {
			--wp-yiq-text-light: {$this->colors['text_color_expanded']};
			--danger: {$this->colors['danger_color']};
			--primary: {$this->colors['primary_color']};
			--primary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-text: {$this->colors['text_color_expanded']};
			--primary-hover-border: 1px solid transparent;
			--primary-var-dark: {$this->colors['primary_color']};
			--primary-var-text2: {$this->colors['primary_color']};
			--orange: {$this->colors['secondary_color']};
			--orange-light-2: {$this->colors['secondary_color_light']};
			--orange-light: {$this->colors['secondary_color_dark']};
			--secondary: {$this->colors['secondary_color']}; 
			--secondary-hover: rgba({$this->colors['secondary_color_rgb']}, 0.6);
			--secondary-hover-text: {$this->colors['text_color_expanded']};
			--secondary-hover-border: 1px solid transparent;
			--text-color-var1: {$this->colors['text_color']};
			--text-color-var2: {$this->colors['text_color_2']};
			--green-light: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--atum-pagination-border-disabled: {$this->colors['border_color']};
			--tertiary: {$this->colors['tertiary_color']};
			--tertiary-var: {$this->colors['tertiary_color']};
			--tertiary-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--tertiary-hover-text: {$this->colors['text_color_expanded']};
			--tertiary-hover-border: 1px solid transparent;
			--atum-table-row-even: {$this->colors['bg_2_color']};
			--blue-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
		    --atum-table-row-odd: {$this->colors['bg_1_color']};
		    --js-scroll-bg: {$this->colors['text_color_2']};
		    --atum-row-active: {$this->colors['primary_color_dark']};
			--atum-table-views-tabs: {$this->colors['text_color_2']};
			--atum-input-text-search: {$this->colors['text_color']};
			--atum-select2-selected-bg: {$this->colors['primary_color_dark']};
			--atum-table-bg: {$this->colors['bg_1_color']};
			--atum-border: {$this->colors['border_color']};
			--atum-border-expanded: rgba({$this->colors['border_color_rgb']}, 0.2);
			--atum-table-icon-active: {$this->colors['text_color']};
			--atum-table-text-active: {$this->colors['text_color']};
			--atum-table-text-expanded: {$this->colors['text_color_expanded']};
			--atum-table-search-text: {$this->colors['text_color_expanded']};
			--atum-table-search-text-disabled: {$this->colors['text_color_expanded']};
			--atum-settings-heads-bg: {$this->colors['primary_color']};
			--atum-settings-nav-link: {$this->colors['primary_color']};
			--atum-pagination-bg-disabled: {$this->colors['bg_2_color']};
			--atum-pagination-disabled: {$this->colors['text_color']};
			--atum-pagination-text: {$this->colors['text_color']};
			--atum-footer-totals: {$this->colors['bg_1_color']};
			--atum-footer-bg: {$this->colors['white']};
			--atum-dropdown-toggle-bg: {$this->colors['bg_2_color']};
			--atum-icon-tree: {$this->colors['text_color']};
			--atum-settings-nav-link-selected-bg: {$this->colors['primary_color_dark']};
			--title-color: {$this->colors['title_color']};
			--atum-settings-btn-save: {$this->colors['white']};
			--atum-settings-btn-save-hover: rgba({$this->colors['white_rgb']}, 0.7);
			--atum-settings-input-border: {$this->colors['border_color']};
			--atum-settings-border-color: {$this->colors['border_color']};
			--atum-settings-separator: {$this->colors['border_color']};
			--overflow-opacity-rigth: linear-gradient(to right, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--dash-border: rgba({$this->colors['text_color_rgb']}, 0.5);
			--dash-card-text: {$this->colors['text_color']};
			--dash-nice-select-border: rgba({$this->colors['text_color_rgb']}, 0.5);
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
			--dash-nice-select-arrow-color: {$this->colors['text_color']};
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-add-widget-color-dark: {$this->colors['primary_color']};
			--dash-input-placeholder: {$this->colors['text_color']};
			--dash-video-title: {$this->colors['dark']};
			--dash-video-subs-text: {$this->colors['text_color']};
			--dash-widget-separator: {$this->colors['border_color']};
			--atum-settings-input-text: {$this->colors['text_color']};
			--atum-settings-wp-color-text: {$this->colors['text_color']};
			--atum-table-row-child-variation: {$this->colors['primary_color_dark']};
			--atum-table-row-child-variation-even: {$this->colors['primary_color_light']};
		    --dash-widget-warning: {$this->colors['secondary_color']};
		    --dash-statistics-chart-type-selected-text: {$this->colors['secondary_color']};
			--dash-stats-data-widget-primary: {$this->colors['primary_color']};
		    --atum-select2-border: {$this->colors['border_color']};
		    --atum-table-filter-dropdown: {$this->colors['gray_500']};
		    --atum-footer-title: {$this->colors['blue_dark']};
		    --atum-table-link-text: {$this->colors['text_color']};
		}";

		return apply_filters( 'atum/get_branded_mode_colors', $scheme );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 1.5.9
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		$tabs['visual_settings'] = array(
			'tab_name' => __( 'Visual Settings', ATUM_TEXT_DOMAIN ),
			'icon'     => 'atmi-highlight',
			'sections' => array(
				'color_mode'   => __( 'Color Mode', ATUM_TEXT_DOMAIN ),
				'scheme_color' => __( 'Scheme Color', ATUM_TEXT_DOMAIN ),
			),
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 1.5.9
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$color_settings = array(
			'theme'                    => array(
				'section'      => 'color_mode',
				'name'         => __( 'Theme settings', ATUM_TEXT_DOMAIN ),
				'desc'         => '',
				'default'      => '',
				'type'         => 'theme_selector',
				'options'      => array(
					'values' => array(
						array(
							'key'   => 'branded_mode',
							'name'  => __( 'Branded Mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'branded-mode.png',
							'desc'  => __( 'Activate the Branded mode. Colour mode for the ATUM default branded colours.', ATUM_TEXT_DOMAIN ),
						),
						array(
							'key'   => 'dark_mode',
							'name'  => __( 'Dark Mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'dark-mode.png',
							'desc'  => __( 'Activate the Dark mode. Colour Mode for tired/weary eyes.', ATUM_TEXT_DOMAIN ),
						),
						array(
							'key'   => 'hc_mode',
							'name'  => __( 'High Contrast Mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'hc-mode.png',
							'desc'  => __( "Activate the High Contrast mode. This mode is for users that find difficult viewing data while browsing the interface in ATUM's branded colours.", ATUM_TEXT_DOMAIN ),
						),
					),
				),
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color'         => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Primary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#00B8DB',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color'         => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Primary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => '#016B7F',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color'         => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Primary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#A8F1FF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color'       => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Secondary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#EFAF00',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color'       => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Secondary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFDF89',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_tertiary_color'        => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Tertiary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#69C61D',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_tertiary_color'        => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Tertiary Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#BAEF8D',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_tertiary_color_light'  => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Outside Elements Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons outside of ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#69C61D',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_danger_color'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Danger Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for highlighted text and edited values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#FF4848',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_danger_color'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Danger Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for highlighted text and edited values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFAEAE',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_title_color'           => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Titles Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for titles.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#27283B',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_title_color'           => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Titles Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for titles.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color'            => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Main Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#6C757D',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color'            => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Main Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color_2'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Soft Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for secondary texts and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#ADB5BD',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color_2'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Soft Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for secondary texts and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#31324A',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color_expanded'   => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Light Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons text and expanded row text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color_expanded'   => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Light Text Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons text and expanded row text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_border_color'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Borders Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for borders in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#E9ECEF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_border_color'          => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Borders Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for borders in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_bg_1_color'            => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Primary Background Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for background color in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#FFFFFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_bg_2_color'            => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Secondary Background Color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the background color of striped rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#F8F9FA',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color_light'   => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#F5FDFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color_light'   => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => '#F5FDFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color_light'   => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#DBF9FF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color_dark'    => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#DBF9FF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color_dark'    => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => '#E6FBFF',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color_dark'    => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#00B8DB',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color_light' => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 3', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#FFF4D6',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color_light' => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 3', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#FFDF89',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color_dark'  => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 4', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => '#FFEDBC',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color_dark'  => array(
				'section'      => 'scheme_color',
				'name'         => __( 'Colored Background Color 4', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => '#EFAF00',
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
		);

		return array_merge( $defaults, $color_settings );

	}

	/**
	 * Get the theme for the selected user
	 *
	 * @since 1.5.9
	 *
	 * @param int $user_id  Optional. If not passed will return the theme for the current user.
	 *
	 * @return string
	 */
	public static function get_user_theme( $user_id = 0 ) {

		$visual_settings = Helpers::get_atum_user_meta( self::VISUAL_SETTINGS_USER_META, $user_id );
		$theme           = isset( $visual_settings['theme'] ) ? $visual_settings['theme'] : 'branded_mode';

		return $theme;

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return AtumColors instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}
