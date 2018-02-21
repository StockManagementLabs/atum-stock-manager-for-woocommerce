<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.3.9
 *
 * Orders Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class Orders extends AtumWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'orders_widget';

	/**
	 * Orders constructor
	 */
	public function __construct() {

		$this->title = __('Orders', ATUM_TEXT_DOMAIN);
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

		$order_status = (array) apply_filters( 'atum/dashboard/promo_sales_widget/order_status', ['wc-processing', 'wc-completed'] );

		/**
		 * This month
		 */
		$stats_this_month = WidgetHelpers::get_orders_stats( array(
			'status'     => $order_status,
			'date_start' => 'first day of this month 00:00:00'
		) );

		/**
		 * Previous month
		 */
		$stats_previous_month = WidgetHelpers::get_orders_stats( array(
			'status'     => $order_status,
			'date_start' => 'first day of last month 00:00:00',
			'date_end'   => 'last day of last month 23:59:59'
		) );

		/**
		 * This week
		 */
		$stats_this_week = WidgetHelpers::get_orders_stats( array(
			'status'     => $order_status,
			'date_start' => 'this week 00:00:00'
		) );

		/**
		 * Today
		 */
		$stats_today = WidgetHelpers::get_orders_stats( array(
			'status'     => $order_status,
			'date_start' => 'today 00:00:00'
		) );

		Helpers::load_view( 'widgets/orders', compact('stats_this_month', 'stats_previous_month', 'stats_this_week', 'stats_today') );

	}

	/**
	 * @inheritDoc
	 */
	public function config() {
		// TODO: Implement config() method.
	}

}