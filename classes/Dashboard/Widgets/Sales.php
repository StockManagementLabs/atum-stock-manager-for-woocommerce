<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.4.0
 *
 * Sales Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class Sales extends AtumWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'sales_widget';

	/**
	 * Sales constructor
	 */
	public function __construct() {

		$this->title       = __( 'Sales', ATUM_TEXT_DOMAIN );
		$this->description = __( 'Periodic Sales Statistics', ATUM_TEXT_DOMAIN );
		$this->thumbnail   = ATUM_URL . 'assets/images/dashboard/widget-thumb-sales.png';

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function init() {

		// TODO: Load the config for this widget??
	}

	/**
	 * @inheritDoc
	 */
	public function render() {

		$products = WidgetHelpers::get_all_product_ids();

		if ( empty($products) ) {
			return;
		}

		$stats_this_month = WidgetHelpers::get_sales_stats( array(
			'types'      => array( 'sales' ),
			'products'   => $products,
			'date_start' => 'first day of this month 00:00:00'
		) );

		$stats_today = WidgetHelpers::get_sales_stats( array(
			'types'      => array( 'sales' ),
			'products'   => $products,
			'date_start' => 'today 00:00:00'
		) );

		$config = $this->get_config();

		Helpers::load_view( 'widgets/sales', compact('stats_this_month', 'stats_today', 'config') );

	}

	/**
	 * @inheritDoc
	 */
	public function get_config() {
		// TODO: IMPLEMENT WIDGET SETTINGS
		return '';//Helpers::load_view_to_string( 'widgets/sales-config' );
	}

}