<?php
/**
 * Promo Sales Widget for ATUM Dashboard
 *
 * @package         Atum
 * @subpackage      Dashboard\Widgets
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           1.4.0
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;

class PromoSales extends AtumWidget {

	/**
	 * The id of this widget
	 *
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'promo_sales_widget';

	/**
	 * PromoSales constructor
	 */
	public function __construct() {

		$this->title       = __( 'Promo Sales', ATUM_TEXT_DOMAIN );
		$this->description = __( 'Periodic Promo Orders and Revenue Statistics', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-promo-sales.png';

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

			$order_status = (array) apply_filters( 'atum/dashboard/promo_sales_widget/order_status', [
				'wc-processing',
				'wc-completed',
			] );

			/**
			 * Today
			 */
			$stats_today = WidgetHelpers::get_promo_sales_stats( array(
				'status'     => $order_status,
				'date_start' => 'today midnight',
			) );

			$config = $this->get_config();

			Helpers::load_view( 'widgets/promo-sales', compact( 'stats_today', 'config' ) );

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
		return ''; // Helpers::load_view_to_string( 'widgets/promo-sales-config' );.
	}

}
