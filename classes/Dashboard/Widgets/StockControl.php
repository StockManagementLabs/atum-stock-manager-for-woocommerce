<?php
/**
 * @package     Atum
 * @subpackage  Dashboard\Widgets
 * @author      Salva Machí and Jose Piera - https://sispixels.com
 * @copyright   ©2018 Stock Management Labs™
 *
 * @since       1.4.0
 *
 * Stock Control Widget for ATUM Dashboard
 */

namespace Atum\Dashboard\Widgets;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumWidget;
use Atum\Dashboard\WidgetHelpers;
use Atum\Inc\Helpers;


class StockControl extends AtumWidget {

	/**
	 * The id of this widget
	 * @var string
	 */
	protected $id = ATUM_PREFIX . 'stock_control_widget';

	/**
	 * Stock Control constructor
	 */
	public function __construct() {

		$this->title = __('Stock Control', ATUM_TEXT_DOMAIN);
		$this->description = __('In, Low and Out of Stock Statistics', ATUM_TEXT_DOMAIN);
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

		$stock_counters = WidgetHelpers::get_stock_levels();
		Helpers::load_view( 'widgets/stock-control', compact('stock_counters') );

	}

	/**
	 * @inheritDoc
	 */
	public function config() {
		// TODO: Implement config() method.
	}

}