<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.4.0
 *
 * Statistics Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class Statistics extends AtumWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'statistics_widget';

	/**
	 * Statistics constructor
	 */
	public function __construct() {

		$this->title = __('ATUM Statistics', ATUM_TEXT_DOMAIN);
		$this->description = __('Graphical Preview of Periodic Sales and Earnings', ATUM_TEXT_DOMAIN);
		$this->thumbnail = '';

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

		// Get sales data
		// TODO: GET THE RIGHT INITIAL CHART DATA FROM WIDGET CONFIG
		$dataset = WidgetHelpers::get_sales_chart_data('this_year');
		$period  = 'month';
		$legends  = array(
			'earnings' => __('Earnings', ATUM_TEXT_DOMAIN),
			'products' => __('Products', ATUM_TEXT_DOMAIN)
		);

		Helpers::load_view( 'widgets/statistics', compact('dataset', 'period', 'legends') );

	}

	/**
	 * @inheritDoc
	 */
	public function config() {
		// TODO: Implement config() method.
	}

}