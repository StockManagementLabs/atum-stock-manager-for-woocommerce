<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.3.9
 *
 * Sales Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;


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

		$this->title = __('Sales', ATUM_TEXT_DOMAIN);
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
			'types'    => array( 'sales' ),
			'products' => $products,
			'date'     => 'first day of this month 00:00:00'
		) );

		$stats_today = WidgetHelpers::get_sales_stats( array(
			'types'    => array( 'sales' ),
			'products' => $products,
			'date'     => 'today 00:00:00'
		) );

		include ATUM_PATH . 'views/widgets/sales.php';

	}

	/**
	 * @inheritDoc
	 */
	public function config() {
		// TODO: Implement config() method.
	}

}