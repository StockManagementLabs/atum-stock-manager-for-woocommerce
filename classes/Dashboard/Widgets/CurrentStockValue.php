<?php
/**
 * Current Stock Value Widget for ATUM Dashboard
 *
 * @package         Atum
 * @subpackage      Dashboard\Widgets
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.5.0
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class CurrentStockValue extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'current_stock_value_widget';

	/**
	 * Current Stock Value constructor
	 */
	public function __construct() {

		$this->title          = __( 'Current Stock Value', ATUM_TEXT_DOMAIN );
		$this->description    = __( 'Get current all items stock value', ATUM_TEXT_DOMAIN );
		$this->thumbnail      = ATUM_URL . 'assets/images/dashboard/widget-thumb-stock-control.png';
		$this->default_layout = array(
			'x'          => 0,
			'y'          => 10,
			'width'      => 6,
			'height'     => 4,
			'min-height' => 5,
		);

		parent::__construct();

	}

	/**
	 * Widget initialization
	 *
	 * @since 1.5.0
	 */
	public function init() {

		// TODO: Load the config for this widget??
	}

	/**
	 * Load the widget view
	 *
	 * @since 1.5.0
	 */
	public function render() {

		if ( ! AtumCapabilities::current_user_can( 'view_statistics' ) ) {
			Helpers::load_view( 'widgets/not-allowed' );
		}
		else {

			$current_stock_values = WidgetHelpers::get_items_in_stock();
			$config               = $this->get_config();

			Helpers::load_view( 'widgets/current-stock-value', compact( 'config', 'current_stock_values' ) );

		}

	}

	/**
	 * Load widget config view
	 * This is what will display when an admin clicks "Configure" at widget header
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_config() {
		// TODO: IMPLEMENT WIDGET SETTINGS.
		return ''; // Helpers::load_view_to_string( 'widgets/stock-control-config' );.
	}

}
