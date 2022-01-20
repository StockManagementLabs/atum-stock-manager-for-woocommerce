<?php
/**
 * Lost Sales Widget for ATUM Dashboard
 *
 * @package         Atum
 * @subpackage      Dashboard\Widgets
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.3.9
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;

class LostSales extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'lost_sales_widget';

	/**
	 * LostSales constructor
	 */
	public function __construct() {

		$this->title       = __( 'Lost Sales', ATUM_TEXT_DOMAIN );
		$this->description = __( 'Periodic Lost Sales Statistics', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-lost-sales.png';

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

			/**
			 * Today
			 */
			$stats_today = WidgetHelpers::get_sales_stats( array(
				'types'      => array( 'lost_sales' ),
				'date_start' => 'midnight today',
				'days'       => 1,
			) );

			$config = $this->get_config();

			Helpers::load_view( 'widgets/lost-sales', compact( 'stats_today', 'config' ) );

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
	protected function get_config() {
		// TODO: IMPLEMENT WIDGET SETTINGS.
		return ''; // Helpers::load_view_to_string( 'widgets/lost-sales-config' );.
	}

}
