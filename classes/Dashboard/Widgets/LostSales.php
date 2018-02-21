<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.3.9
 *
 * Lost Sales Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class LostSales extends AtumWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'lost_sales_widget';

	/**
	 * LostSales constructor
	 */
	public function __construct() {

		$this->title = __('Lost Sales', ATUM_TEXT_DOMAIN);
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
			'types'      => array( 'lost_sales' ),
			'products'   => $products,
			'date_start' => 'first day of this month 00:00:00'
		) );

		$stats_today = WidgetHelpers::get_sales_stats( array(
			'types'      => array( 'lost_sales' ),
			'products'   => $products,
			'date_start' => 'today 00:00:00',
			'days'       => 1
		) );

		Helpers::load_view( 'widgets/lost-sales', compact('stats_this_month', 'stats_today') );

	}

	/**
	 * @inheritDoc
	 */
	public function config() {
		// TODO: Implement config() method.
	}

}