<?php
/**
 * Stock Control Widget for ATUM Dashboard
 *
 * @package         Atum
 * @subpackage      Dashboard\Widgets
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.4.0
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumColors;
use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;
use Atum\StockCentral\StockCentral;

class StockControl extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'stock_control_widget';

	/**
	 * Stock Control constructor
	 */
	public function __construct() {

		$this->title       = __( 'Stock Control', ATUM_TEXT_DOMAIN );
		$this->description = __( 'In, Low and Out of Stock Statistics', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-stock-control.png';

		parent::__construct();

	}

	/**
	 * Widget initialization
	 *
	 * @since 1.4.0
	 */
	public function init() {

		// TODO: Load the config for this widget??
	}

	/**
	 * Load the widget view
	 *
	 * @since 1.4.0
	 */
	public function render() {

		if ( ! AtumCapabilities::current_user_can( 'view_statistics' ) ) {
			Helpers::load_view( 'widgets/not-allowed' );
		}
		else {

			$stock_counters = WidgetHelpers::get_stock_levels();

			$sc_url   = add_query_arg( 'page', StockCentral::UI_SLUG, admin_url( 'admin.php' ) );
			$sc_links = array(
				'in_stock'       => add_query_arg( 'view', 'in_stock', $sc_url ),
				'out_stock'      => add_query_arg( 'view', 'out_stock', $sc_url ),
				'restock_status' => add_query_arg( 'view', 'restock_status', $sc_url ),
				'unmanaged'      => add_query_arg( 'view', 'unmanaged', $sc_url ),
			);

			$config = $this->get_config();
			$mode   = AtumColors::get_user_theme();

			Helpers::load_view( 'widgets/stock-control', compact( 'stock_counters', 'sc_links', 'config', 'mode' ) );

		}

	}

	/**
	 * Load widget config view
	 * This is what will display when an admin clicks "Configure" at widget header
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_config() {
		// TODO: IMPLEMENT WIDGET SETTINGS.
		return ''; // Helpers::load_view_to_string( 'widgets/stock-control-config' );.
	}

}
