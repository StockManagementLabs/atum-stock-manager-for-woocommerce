<?php
/**
 * Add ATUM color schemes
 *
 * @package        Atum
 * @subpackage     Components
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2022 Stock Management Labs™
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
	 * Defaults values for custom colors
	 *
	 * @var array
	 */
	private $defaults;

	/**
	 * The ATUM colors
	 *
	 * @var array
	 */
	private $colors = array(
		'gray_100'   => '#F8F9FA',
		'gray_200'   => '#E9ECEF',
		'gray_500'   => '#ADB5BD',
		'gray_600'   => '#6C757D',
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
	 * The default color schemes values for ATUM
	 *
	 * @since 1.6.2
	 */
	const DEFAULT_COLOR_SCHEMES = array(
		'dm_primary_color'         => '#A8F1FF',
		'dm_primary_color_light'   => '#DBF9FF',
		'dm_primary_color_dark'    => '#00B8DB',
		'dm_secondary_color'       => '#FFDF89',
		'dm_secondary_color_light' => '#FFDF89',
		'dm_secondary_color_dark'  => '#EFAF00',
		'dm_tertiary_color'        => '#BAEF8D',
		'dm_tertiary_color_light'  => '#69C61D',
		'dm_text_color'            => '#FFFFFF',
		'dm_text_color_2'          => '#31324A',
		'dm_text_color_expanded'   => '#27283B',
		'dm_border_color'          => '#ADB5BD',
		'dm_bg_1_color'            => '#31324A',
		'dm_bg_2_color'            => '#3B3D5A',
		'dm_danger_color'          => '#FFAEAE',
		'dm_title_color'           => '#FFFFFF',

		'hc_primary_color'         => '#016B7F',
		'hc_primary_color_light'   => '#F5FDFF',
		'hc_primary_color_dark'    => '#E6FBFF',
		'hc_secondary_color'       => '#016B7F',
		'hc_secondary_color_light' => '#F5FDFF',
		'hc_secondary_color_dark'  => '#E6FBFF',
		'hc_tertiary_color'        => '#016B7F',
		'hc_tertiary_color_light'  => '#F5FDFF',
		'hc_text_color'            => '#016B7F',
		'hc_text_color_2'          => '#27283B',
		'hc_text_color_expanded'   => '#FFFFFF',
		'hc_border_color'          => '#ADB5BD',
		'hc_bg_1_color'            => '#FFFFFF',
		'hc_bg_2_color'            => '#FFFFFF',
		'hc_danger_color'          => '#FF4848',
		'hc_title_color'           => '#27283B',

		'bm_primary_color'         => '#00B8DB',
		'bm_primary_color_light'   => '#F5FDFF',
		'bm_primary_color_dark'    => '#DBF9FF',
		'bm_secondary_color'       => '#EFAF00',
		'bm_secondary_color_light' => '#FFF4D6',
		'bm_secondary_color_dark'  => '#FFEDBC',
		'bm_tertiary_color'        => '#69C61D',
		'bm_tertiary_color_light'  => '#69C61D',
		'bm_text_color'            => '#6C757D',
		'bm_text_color_2'          => '#ADB5BD',
		'bm_text_color_expanded'   => '#FFFFFF',
		'bm_border_color'          => '#E9ECEF',
		'bm_bg_1_color'            => '#FFFFFF',
		'bm_bg_2_color'            => '#F8F9FA',
		'bm_danger_color'          => '#FF4848',
		'bm_title_color'           => '#27283B',
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

		$this->colors['gray_500_rgb']          = self::convert_hexadecimal_to_rgb( $this->colors['gray_500'] );
		$this->colors['gray_600_rgb']          = self::convert_hexadecimal_to_rgb( $this->colors['gray_600'] );
		$this->colors['blue_dark']             = self::convert_hexadecimal_to_rgb( $this->colors['blue_dark'] );
		$this->colors['white_rgb']             = self::convert_hexadecimal_to_rgb( $this->colors['white'] );
		$this->colors['black_rgb']             = self::convert_hexadecimal_to_rgb( $this->colors['black'] );
		$this->colors['primary_color']         = ! empty( $visual_settings[ "{$prefix}primary_color" ] ) ? $visual_settings[ "{$prefix}primary_color" ] : $this->colors['blue'];
		$this->colors['primary_color_rgb']     = self::convert_hexadecimal_to_rgb( $this->colors['primary_color'] );
		$this->colors['primary_color_light']   = ! empty( $visual_settings[ "{$prefix}primary_color_light" ] ) ? $visual_settings[ "{$prefix}primary_color_light" ] : '#F5FDFF';
		$this->colors['primary_color_dark']    = ! empty( $visual_settings[ "{$prefix}primary_color_dark" ] ) ? $visual_settings[ "{$prefix}primary_color_dark" ] : '#DBF9FF';
		$this->colors['secondary_color']       = ! empty( $visual_settings[ "{$prefix}secondary_color" ] ) ? $visual_settings[ "{$prefix}secondary_color" ] : $this->colors['orange'];
		$this->colors['secondary_color_rgb']   = self::convert_hexadecimal_to_rgb( $this->colors['secondary_color'] );
		$this->colors['secondary_color_light'] = ! empty( $visual_settings[ "{$prefix}secondary_color_light" ] ) ? $visual_settings[ "{$prefix}secondary_color_light" ] : '#FFF4D6';
		$this->colors['secondary_color_dark']  = ! empty( $visual_settings[ "{$prefix}secondary_color_dark" ] ) ? $visual_settings[ "{$prefix}secondary_color_dark" ] : '#FFEDBC';
		$this->colors['tertiary_color']        = ! empty( $visual_settings[ "{$prefix}tertiary_color" ] ) ? $visual_settings[ "{$prefix}tertiary_color" ] : $this->colors['green'];
		$this->colors['tertiary_color_rgb']    = self::convert_hexadecimal_to_rgb( $this->colors['tertiary_color'] );
		$this->colors['tertiary_color_light']  = ! empty( $visual_settings[ "{$prefix}tertiary_color_light" ] ) ? $visual_settings[ "{$prefix}tertiary_color_light" ] : $this->colors['green'];
		$this->colors['tertiary_color_dark']   = ! empty( $visual_settings[ "{$prefix}tertiary_color_dark" ] ) ? $visual_settings[ "{$prefix}tertiary_color_dark" ] : '#B4F0C9';
		$this->colors['text_color']            = ! empty( $visual_settings[ "{$prefix}text_color" ] ) ? $visual_settings[ "{$prefix}text_color" ] : '#6C757D';
		$this->colors['text_color_rgb']        = self::convert_hexadecimal_to_rgb( $this->colors['text_color'] );
		$this->colors['text_color_2']          = ! empty( $visual_settings[ "{$prefix}text_color_2" ] ) ? $visual_settings[ "{$prefix}text_color_2" ] : $this->colors['gray_600'];
		$this->colors['text_color_2_rgb']      = self::convert_hexadecimal_to_rgb( $this->colors['text_color_2'] );
		$this->colors['text_color_expanded']   = ! empty( $visual_settings[ "{$prefix}text_color_expanded" ] ) ? $visual_settings[ "{$prefix}text_color_expanded" ] : $this->colors['white'];
		$this->colors['border_color']          = ! empty( $visual_settings[ "{$prefix}border_color" ] ) ? $visual_settings[ "{$prefix}border_color" ] : '#E9ECEF';
		$this->colors['border_color_rgb']      = self::convert_hexadecimal_to_rgb( $this->colors['border_color'] );
		$this->colors['bg_1_color']            = ! empty( $visual_settings[ "{$prefix}bg_1_color" ] ) ? $visual_settings[ "{$prefix}bg_1_color" ] : $this->colors['white'];
		$this->colors['bg_1_color_rgb']        = self::convert_hexadecimal_to_rgb( $this->colors['bg_1_color'] );
		$this->colors['bg_2_color']            = ! empty( $visual_settings[ "{$prefix}bg_2_color" ] ) ? $visual_settings[ "{$prefix}bg_2_color" ] : $this->colors['gray_100'];
		$this->colors['danger_color']          = ! empty( $visual_settings[ "{$prefix}danger_color" ] ) ? $visual_settings[ "{$prefix}danger_color" ] : '#FF4848';
		$this->colors['danger_color_rgb']      = self::convert_hexadecimal_to_rgb( $this->colors['danger_color'] );
		$this->colors['title_color']           = ! empty( $visual_settings[ "{$prefix}title_color" ] ) ? $visual_settings[ "{$prefix}title_color" ] : $this->colors['blue_dark'];
		$this->colors['main_border_alt']       = ! empty( $visual_settings[ "{$prefix}main_border_alt" ] ) ? $visual_settings[ "{$prefix}main_border_alt" ] : '#6C757D';

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
	public static function convert_hexadecimal_to_rgb( $hex_value ) {

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

		$scheme = ":root {
			--atum-border-expanded: $border_color;
			--atum-border-var: $border_color;
			--atum-cloned-list-table-shadow: rgba({$this->colors['black_rgb']}, 0.04); 
			--atum-column-groups-bg: {$this->colors['gray_200']};
			--atum-dropdown-toggle-bg: {$this->colors['gray_100']};
			--atum-expanded-bg: $bg_1_color;
			--atum-footer-title: $text_color_2;
			--atum-menu-text: {$this->colors['primary_color']};
			--atum-menu-text-highlight: {$this->colors['primary_color']};
			--atum-pagination-border-disabled: $border_color;
			--atum-pagination-disabled: $text_color_2;
			--atum-pagination-text: $text_color_2;			
			--atum-settings-heads-bg: {$this->colors['primary_color']};
			--atum-settings-input-border: $border_color;
			--atum-settings-nav-link: {$this->colors['primary_color']};
			--atum-table-bg: $bg_1_color;
			--atum-table-bg2: $bg_2_color;
			--atum-table-filter-dropdown: $text_color_2;
			--atum-table-link-text: $text_color;
			--atum-table-search-text-disabled: $text_color_2;
			--atum-table-views-tabs: $text_color;
			--atum-text-color-dark2: $text_color;
			--atum-text-color-var1:$text_color_2;
			--atum-text-color-var2: $text_color_2;
			--atum-text-color-var3: $text_color_2;
			--atum-text-modal-title: {$this->colors['blue_dark']};
			--atum-setting-info: {$this->colors['gray_500']};
			--atum-version: $text_color_2;
			--atum-version-bg: rgba({$this->colors['black_rgb']}, 0.1);
			--blue-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--danger: $danger_color;
			--danger-hover: rgba({$this->colors['danger_color_rgb']}, 0.6);
			--danger-hover-border: none;
			--danger-hover-text: {$this->colors['primary_color']};
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-blue-trans: {$this->colors['primary_color_light']};
			--dash-card-bg: $bg_1_color;
			--dash-card-text: $text_color_2;
			--dash-input-group-bg: rgba({$this->colors['bg_1_color_rgb']}, 0.3);
			--dash-input-group-shadow: rgba({$this->colors['bg_1_color_rgb']}, 0.3);
			--dash-next-text: $text_color_2;
			--dash-nice-select-bg: $bg_1_color;
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
		    --dash-nice-select-hover: {$this->colors['primary_color']};
			--dash-nice-select-list-bg: $bg_1_color;
		    --dash-nice-select-option-hover-bg: {$this->colors['primary_color_light']};
		    --dash-nice-select-option-selected-bg: {$this->colors['primary_color_light']};
			--dash-statistics-chart-type-bg: transparent;
			--dash-statistics-chart-type-selected-bg: $secondary_color;
			--dash-statistics-chart-type-selected-text: $text_color_expanded;
			--dash-statistics-grid-lines: rgba({$this->colors['text_color_rgb']}, 0.2);
			--dash-statistics-legend-switch-bg: transparent;
			--dash-statistics-ticks: $text_color;
			--dash-stats-data-widget-primary: {$this->colors['primary_color']};
			--dash-stats-data-widget-success: {$this->colors['primary_color']};
			--dash-subscription-input: transparent;
			--dash-video-subs-text: $text_color_2;
			--dash-video-title: {$this->colors['dark']};
			--dash-widget-current-stock-value-bg: {$this->colors['primary_color']};
			--dash-widget-current-stock-value-text: white;
			--dash-widget-icon: $border_color;
			--green-light: {$this->colors['primary_color_dark']};
			--js-scroll-bg: {$this->colors['primary_color']};
			--main-border: $border_color;
			--main-border-alt: $border_color;
			--main-dropdown-border: $border_color;
			--main-text: $text_color;
			--main-text-2: {$this->colors['text_color_2']};
			--main-text-expanded: $text_color_expanded;
			--main-title: {$this->colors['title_color']};
			--overflow-opacity-rigth: linear-gradient(to right, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--primary: {$this->colors['primary_color']};
		    --primary-dark: {$this->colors['primary_color_dark']};
		    --primary-switcher-bg: {$this->colors['primary_color_dark']};
			--primary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-var: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-border: none;
			--primary-hover-text: {$this->colors['primary_color']};
			--primary-light: {$this->colors['primary_color_light']};
			--primary-var-dark: {$this->colors['primary_color']};
			--primary-var-text2: {$this->colors['primary_color']};
			--purple-pl: {$this->colors['primary_color']};
		    --purple-pl-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--secondary: $secondary_color;
			--secondary-dark: $secondary_color_dark;
			--secondary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--secondary-hover-text: $secondary_color;
			--secondary-hover-border: none;
			--secondary-light: $secondary_color_light;
			--secondary-shadow: rgba({$this->colors['primary_color_rgb']}, 0.2);
			--secondary-var: $secondary_color;
			--success: $tertiary_color;
			--success-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--success-hover-border: none;
			--success-hover-text: $tertiary_color;
			--tertiary: $tertiary_color;
			--tertiary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--tertiary-hover-border: none;
			--tertiary-hover-text: $tertiary_color;
			--tertiary-light: $tertiary_color_light;
			--tertiary-var: $tertiary_color;
			--warning: $secondary_color;
			--warning-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--warning-hover-text: $secondary_color;
			--warning-hover-border: none;
			--white: {$this->colors['white']};
			--white-shadow: rgba({$this->colors['border_color_rgb']}, 0.2);
			--wp-yiq-text-light: $text_color_expanded;
			--wp-link-hover: {$this->colors['primary_color']};
		    --wp-pink-darken-expanded: {$this->colors['primary_color']};
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
		$bg_1_color_rgb = self::convert_hexadecimal_to_rgb( $bg_1_color );
		$bg_2_color     = '#3B3D5A';

		$scheme = ":root {
			--atum-add-widget-separator: rgba({$this->colors['border_color_rgb']},0.2);
			--atum-add-widget-title: {$this->colors['text_color']};
		    --atum-border-expanded: {$this->colors['border_color']};
			--atum-border-var: rgba({$this->colors['text_color_rgb']}, 0.5);
			--atum-cloned-list-table-shadow: rgba({$this->colors['white_rgb']}, 0.04); 
		    --atum-column-groups-bg: {$this->colors['text_color_expanded']};
			--atum-dropdown-toggle-bg: $bg_2_color;
			--atum-easytree-node: {$this->colors['gray_600_rgb']};
			--atum-expanded-bg: {$this->colors['text_color_expanded']};
			--atum-footer-link: {$this->colors['primary_color']};
			--atum-footer-text: {$this->colors['white']};
			--atum-footer-title: {$this->colors['white']};
			--atum-menu-text: {$this->colors['white']};
			--atum-menu-text-highlight: $bg_2_color;
			--atum-pagination-border-disabled: rgba({$this->colors['border_color_rgb']}, 0.0);
			--atum-pagination-disabled: {$this->colors['text_color']};
			--atum-pagination-text: {$this->colors['text_color_2']};	
			--atum-settings-btn-save: {$this->colors['primary_color']};
			--atum-settings-btn-save-hover: rgba({$this->colors['primary_color_rgb']},0.7);
			--atum-settings-heads-bg: $bg_1_color;
			--atum-settings-nav-bg: $bg_1_color;
			--atum-settings-nav-link: {$this->colors['text_color']};
			--atum-settings-section-bg: $bg_1_color;
			--atum-settings-text-logo: {$this->colors['text_color']};
			--atum-table-bg: $bg_1_color;
			--atum-table-bg2: $bg_2_color;
			--atum-table-filter-dropdown: {$this->colors['white']};
			--atum-table-link-text: {$this->colors['gray_500']};
		    --atum-table-row-variation-text: {$this->colors['text_color_2']};
			--atum-table-search-text-disabled: {$this->colors['text_color_expanded']};
			--atum-table-views-tabs: {$this->colors['text_color']};
			--atum-table-views-tabs-active-text: {$this->colors['text_color_2']};
			--atum-table-text: {$this->colors['gray_200']};
			--atum-table-text-hover: {$this->colors['dark']};
			--atum-text-color-dark2: {$this->colors['text_color_2']};
			--atum-text-color-var1: {$this->colors['text_color']};
			--atum-text-color-var2: {$this->colors['text_color']};
			--atum-text-color-var3: {$this->colors['text_color']};
			--atum-text-modal-title: {$this->colors['text_color']};
			--atum-checkbox-label: {$this->colors['white']};
			--atum-setting-info: {$this->colors['gray_500']};
			--atum-section-field: {$this->colors['gray_600']};
			--blue-hover: rgba({$this->colors['primary_color_rgb']},0.6);
			--blue-light: $bg_1_color;
			--danger: {$this->colors['danger_color']};
			--danger-hover: rgba({$this->colors['danger_color_rgb']}, 0.6);
			--danger-hover-border: none;
			--danger-hover-text: {$this->colors['text_color_expanded']};
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-add-widget-color-dark: {$this->colors['text_color_expanded']};
			--dash-card-bg: $bg_1_color;
			--dash-card-text: {$this->colors['text_color']};
			--dash-next-text: {$this->colors['gray_500']};
			--dash-nice-select-bg: $bg_1_color;
			--dash-nice-select-list-bg: $bg_1_color;
		    --dash-nice-select-hover: {$this->colors['primary_color_light']};
		    --dash-nice-select-option-hover-bg: {$this->colors['primary_color_light']};
		    --dash-nice-select-option-selected-bg: {$this->colors['primary_color_light']};
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
			--dash-input-group-bg: rgba(0, 0, 0, 0.3);
			--dash-input-group-shadow: rgba(0, 0, 0, 0.3);
			--dash-statistics-ticks: {$this->colors['text_color']};
			--dash-statistics-grid-lines: rgba({$this->colors['text_color_rgb']}, 0.2);
			--dash-statistics-chart-type-bg: transparent;
			--dash-statistics-chart-type-selected-bg: $bg_1_color;
			--dash-statistics-chart-type-selected-text: {$this->colors['secondary_color']};
			--dash-statistics-legend-switch-bg: transparent;
			--dash-stats-data-widget-primary: {$this->colors['primary_color']};
			--dash-stats-data-widget-success: {$this->colors['tertiary_color']};
			--dash-subscription-input: transparent;
			--dash-video-title: {$this->colors['text_color']};
			--dash-video-subs-text: {$this->colors['gray_500']};
			--green-light: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
		    --js-scroll-bg: {$this->colors['text_color']};
		    --main-border: rgba({$this->colors['border_color_rgb']}, 0.2);
		    --main-border-alt: rgba({$this->colors['border_color_rgb']},0.5);
		    --main-dropdown-border: rgba({$this->colors['border_color_rgb']},0.5);
			--main-text: {$this->colors['text_color']};
			--main-text-2: {$this->colors['text_color_2']};
			--main-text-expanded: {$this->colors['text_color_expanded']};
			--main-title: {$this->colors['title_color']};
			--overflow-opacity-rigth: linear-gradient(to right, rgba($bg_1_color_rgb,0), rgba($bg_1_color_rgb,0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba($bg_1_color_rgb,0), rgba($bg_1_color_rgb,0.9));
		    --popover-black-shadow: rgba({$this->colors['border_color_rgb']}, 0.2);
			--primary: {$this->colors['primary_color']};
		    --primary-dark: {$this->colors['primary_color_light']};
			--primary-hover: rgba({$this->colors['primary_color_rgb']}, 0.7);
			--primary-hover-var: rgba({$this->colors['primary_color_rgb']}, 0.7);
			--primary-hover-border: none;
			--primary-hover-text: {$this->colors['text_color_expanded']};
			--primary-light: {$this->colors['primary_color_light']};
			--primary-switcher-bg: rgba({$this->colors['primary_color_rgb']}, 0.7);
			--primary-var-dark: {$this->colors['primary_color_dark']};
			--primary-var-text2: {$this->colors['text_color_2']};
			--secondary: {$this->colors['secondary_color']}; 
			--secondary-dark: {$this->colors['secondary_color']};
			--secondary-hover: rgba({$this->colors['secondary_color_rgb']}, 0.7);
			--secondary-hover-border: none;
			--secondary-hover-text: {$this->colors['text_color_expanded']};
			--secondary-light: {$this->colors['secondary_color']};
			--secondary-shadow: rgba({$this->colors['secondary_color_rgb']}, 0.2);
			--secondary-var: {$this->colors['secondary_color_dark']};
			--success: {$this->colors['tertiary_color']};
			--success-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.7);
			--success-hover-text: {$this->colors['text_color_expanded']};
			--success-hover-border: none;
			--tertiary: {$this->colors['tertiary_color']};
			--tertiary-var: {$this->colors['tertiary_color_light']};
			--tertiary-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.7);
			--tertiary-hover-text: {$this->colors['text_color_expanded']};
			--tertiary-hover-border: none;
			--tertiary-light: {$this->colors['tertiary_color_light']};
			--warning: {$this->colors['secondary_color']};
			--warning-hover: rgba({$this->colors['secondary_color_rgb']}, 0.7);
			--warning-hover-border: none;
			--warning-hover-text: {$this->colors['text_color_expanded']};
			--white: {$this->colors['white']};
			--wp-link-hover: {$this->colors['primary_color']};
			--wp-yiq-text-light: {$this->colors['text_color_2']};
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
			--atum-border-expanded: {$this->colors['border_color']};
			--atum-border-var: rgba({$this->colors['text_color_rgb']}, 0.5);
			--atum-cloned-list-table-shadow: rgba({$this->colors['black_rgb']}, 0.04); 
			--atum-column-groups-bg: {$this->colors['gray_200']};
			--atum-dropdown-toggle-bg: {$this->colors['gray_200']};
			--atum-expanded-bg: {$this->colors['bg_1_color']};
		    --atum-footer-title: {$this->colors['title_color']};
		    --atum-menu-text: {$this->colors['primary_color']};
		    --atum-menu-text-highlight: {$this->colors['primary_color']};
			--atum-pagination-border-disabled: {$this->colors['border_color']};
			--atum-pagination-disabled: {$this->colors['text_color']};
			--atum-pagination-text: {$this->colors['text_color']};		   
			--atum-settings-btn-save: {$this->colors['white']};
			--atum-settings-btn-save-hover: rgba({$this->colors['white_rgb']}, 0.7);
			--atum-settings-heads-bg: {$this->colors['primary_color']};
			--atum-settings-input-border: {$this->colors['border_color']};
			--atum-settings-nav-link: {$this->colors['primary_color']};
		    --atum-table-bg: {$this->colors['bg_1_color']};
			--atum-table-bg2: {$this->colors['bg_2_color']};
		    --atum-table-filter-dropdown: {$this->colors['gray_500']};
		    --atum-table-link-text: {$this->colors['text_color']};
			--atum-table-search-text-disabled: {$this->colors['text_color_expanded']};
			--atum-table-views-tabs: {$this->colors['text_color_2']};
			--atum-text-color-dark2: {$this->colors['text_color']};
			--atum-text-color-var1: {$this->colors['text_color']};
			--atum-text-color-var2: {$this->colors['text_color_2']};
			--atum-text-color-var3: {$this->colors['text_color']};
			--atum-text-modal-title: {$this->colors['blue_dark']};
			--blue-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--danger: {$this->colors['danger_color']};
			--danger-hover: rgba({$this->colors['danger_color_rgb']}, 0.6);
			--danger-hover-border: none;
			--danger-hover-text: {$this->colors['text_color_expanded']};;
			--dash-card-text: {$this->colors['text_color']};
			--dash-nice-select-disabled-after: lighten({$this->colors['text_color_rgb']}, 20%);
			--dash-add-widget-color: {$this->colors['gray_500']};
			--dash-add-widget-color-dark: {$this->colors['primary_color']};
		    --dash-statistics-chart-type-selected-text: {$this->colors['secondary_color']};
			--dash-stats-data-widget-primary: {$this->colors['primary_color']};
			--dash-stats-data-widget-success: {$this->colors['tertiary_color']};
			--dash-video-title: {$this->colors['dark']};
			--dash-video-subs-text: {$this->colors['text_color']};
			--green: {$this->colors['tertiary_color']};
			--green-light: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
		    --js-scroll-bg: {$this->colors['text_color_2']};
			--main-border: {$this->colors['border_color']};
			--main-border-alt: {$this->colors['main_border_alt']};
			--main-dropdown-border: {$this->colors['border_color']};
			--main-text: {$this->colors['text_color']};
			--main-text-2: {$this->colors['text_color_2']};
			--main-text-expanded: {$this->colors['text_color_expanded']};
			--main-title: {$this->colors['title_color']};
			--overflow-opacity-rigth: linear-gradient(to right, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--overflow-opacity-left: linear-gradient(to left, rgba({$this->colors['bg_1_color_rgb']}, 0), rgba({$this->colors['bg_1_color_rgb']}, 0.9));
			--primary: {$this->colors['primary_color']};
		    --primary-dark: {$this->colors['primary_color_dark']};
			--primary-hover: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-var: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-hover-text: {$this->colors['text_color_expanded']};
			--primary-hover-border: none;
			--primary-light: {$this->colors['primary_color_light']};
			--primary-switcher-bg: rgba({$this->colors['primary_color_rgb']}, 0.6);
			--primary-var-dark: {$this->colors['primary_color']};
			--primary-var-text2: {$this->colors['primary_color']};
			--secondary: {$this->colors['secondary_color']}; 
			--secondary-dark: {$this->colors['secondary_color_dark']};
			--secondary-hover: rgba({$this->colors['secondary_color_rgb']}, 0.6);
			--secondary-hover-text: {$this->colors['text_color_expanded']};
			--secondary-hover-border: none;
			--secondary-light: {$this->colors['secondary_color_light']};
			--secondary-shadow: rgba({$this->colors['secondary_color_rgb']}, 0.2);
			--secondary-var: {$this->colors['secondary_color']};
			--success: {$this->colors['tertiary_color']};
			--success-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--success-hover-text: {$this->colors['text_color_expanded']};
			--success-hover-border: none;
			--tertiary: {$this->colors['tertiary_color']};
			--tertiary-var: {$this->colors['tertiary_color']};
			--tertiary-hover: rgba({$this->colors['tertiary_color_rgb']}, 0.6);
			--tertiary-hover-text: {$this->colors['text_color_expanded']};
			--tertiary-hover-border: none;
			--tertiary-light: {$this->colors['tertiary_color_light']};
			--warning: {$this->colors['secondary_color']};
			--warning-hover: rgba({$this->colors['secondary_color_rgb']}, 0.6);
			--warning-hover-text: {$this->colors['text_color_expanded']};
			--warning-hover-border: none;
			--white: {$this->colors['white']};
			--wp-link-hover: {$this->colors['primary_color']};
			--wp-yiq-text-light: {$this->colors['text_color_expanded']};
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
			'label'    => __( 'Visual Settings', ATUM_TEXT_DOMAIN ),
			'icon'     => 'atmi-highlight',
			'sections' => array(
				'color_mode'   => __( 'Color Mode', ATUM_TEXT_DOMAIN ),
				'color_scheme' => __( 'Color Scheme', ATUM_TEXT_DOMAIN ),
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
				'group'        => 'visual_settings',
				'section'      => 'color_mode',
				'name'         => __( 'Theme settings', ATUM_TEXT_DOMAIN ),
				'desc'         => '',
				'default'      => 'branded_mode',
				'type'         => 'theme_selector',
				'options'      => array(
					'values' => array(
						array(
							'key'   => 'branded_mode',
							'name'  => __( 'Branded mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'branded-mode.png',
							'desc'  => __( 'Activate the branded mode. Color mode for the ATUM default branded colors.', ATUM_TEXT_DOMAIN ),
						),
						array(
							'key'   => 'dark_mode',
							'name'  => __( 'Dark mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'dark-mode.png',
							'desc'  => __( 'Activate the dark mode. Color mode for tired/weary eyes.', ATUM_TEXT_DOMAIN ),
						),
						array(
							'key'   => 'hc_mode',
							'name'  => __( 'High contrast mode', ATUM_TEXT_DOMAIN ),
							'thumb' => 'hc-mode.png',
							'desc'  => __( "Activate the high contrast mode. This mode is for users that find difficult viewing data while browsing the interface in ATUM's branded colors.", ATUM_TEXT_DOMAIN ),
						),
					),
				),
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color'         => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Primary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_primary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color'         => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Primary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['hc_primary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color'         => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Primary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for links and editable values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_primary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color'       => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Secondary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_secondary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color'       => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Secondary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_secondary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_tertiary_color'        => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Tertiary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_tertiary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_tertiary_color'        => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Tertiary color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_tertiary_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_tertiary_color_light'  => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Outside elements color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons outside of ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_tertiary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_danger_color'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Danger color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for highlighted text and edited values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_danger_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_danger_color'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Danger color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for highlighted text and edited values in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_danger_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_title_color'           => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Titles text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for titles.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_title_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_title_color'           => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Titles text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for titles.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_title_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color'            => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Main text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_text_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color'            => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Main text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_text_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color_2'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Soft text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for secondary texts and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_text_color_2'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color_2'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Soft text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for secondary texts and UX elements in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_text_color_2'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_text_color_expanded'   => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Light text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons text and expanded row text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_text_color_expanded'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_text_color_expanded'   => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Light text color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for buttons text and expanded row text in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_text_color_expanded'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_border_color'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Borders color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for borders in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_border_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_border_color'          => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Borders color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for borders in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_border_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_bg_1_color'            => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Primary background color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for background color in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_bg_1_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_bg_2_color'            => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Secondary background color', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the background color of striped rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_bg_2_color'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color_light'   => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_primary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color_light'   => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['hc_primary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color_light'   => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 1', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_primary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_primary_color_dark'    => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_primary_color_dark'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'hc_primary_color_dark'    => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'hc_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['hc_primary_color_dark'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_primary_color_dark'    => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 2', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_primary_color_dark'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color_light' => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 3', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_secondary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color_light' => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 3', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_secondary_color_light'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'bm_secondary_color_dark'  => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 4', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'branded_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['bm_secondary_color_dark'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
			'dm_secondary_color_dark'  => array(
				'group'        => 'visual_settings',
				'section'      => 'color_scheme',
				'name'         => __( 'Colored background color 4', ATUM_TEXT_DOMAIN ),
				'desc'         => __( 'Mainly used for the striped background of expanded rows in ATUM tables.', ATUM_TEXT_DOMAIN ),
				'type'         => 'color',
				'display'      => 'dark_mode',
				'default'      => self::DEFAULT_COLOR_SCHEMES['dm_secondary_color_dark'],
				'to_user_meta' => self::VISUAL_SETTINGS_USER_META,
			),
		);

		foreach ( $color_settings as $key => $data ) {
			if ( 'color' !== $data['type'] ) continue;
			$this->defaults[ $key ] = $data['default'];
		}

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

		return ! empty( $visual_settings['theme'] ) ? $visual_settings['theme'] : 'branded_mode';

	}

	/**
	 * Get a specified color for the selected user
	 *
	 * @since 1.5.9
	 *
	 * @param string $color_name
	 * @param int    $user_id Optional. If not passed will return the theme for the current user.
	 *
	 * @return string
	 */
	public static function get_user_color( $color_name, $user_id = 0 ) {

		$visual_settings = Helpers::get_atum_user_meta( self::VISUAL_SETTINGS_USER_META, $user_id );

		return isset( $visual_settings[ $color_name ] ) ? $visual_settings[ $color_name ] : FALSE;

	}

	/**
	 * Get the default value for a color
	 *
	 * @since 1.5.9
	 *
	 * @param string $color_name
	 *
	 * @return string
	 */
	public function get_default_color( $color_name ) {

		return $this->defaults[ $color_name ];

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
