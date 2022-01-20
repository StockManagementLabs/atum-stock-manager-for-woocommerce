<?php
/**
 * Statistics Widget for ATUM Dashboard
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
use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;

class Statistics extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'statistics_widget';

	/**
	 * Statistics constructor
	 */
	public function __construct() {

		$this->title       = __( 'ATUM Statistics', ATUM_TEXT_DOMAIN );
		$this->description = __( 'Graphical Preview of Periodic Sales', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-statistics.png';

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

			// Get sales data.
			// TODO: GET THE RIGHT INITIAL CHART DATA FROM WIDGET CONFIG.
			$dataset = WidgetHelpers::get_sales_chart_data( 'this_year' );
			$period  = 'month';
			$legends = array(
				'value'    => __( 'Sales', ATUM_TEXT_DOMAIN ),
				'products' => __( 'Products', ATUM_TEXT_DOMAIN ),
			);

			$config = $this->get_config();

			Helpers::load_view( 'widgets/statistics', compact( 'dataset', 'period', 'legends', 'config' ) );

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
		return ''; // Helpers::load_view_to_string( 'widgets/statistics-config' );.
	}

}
