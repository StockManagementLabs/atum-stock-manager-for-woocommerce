<?php
/**
 * @package         Atum\DataExport
 * @subpackage      Reports
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.5
 *
 * Extends the Stock Central's List Table and exports only the table as HTML report
 */

namespace Atum\DataExport\Reports;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\StockCentral\Inc\ListTable;


class HtmlReport extends ListTable {

	/**
	 * Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args.
	 *
	 * @since 1.2.5
	 *
	 * @param array|string $args {
	 *      Array or string of arguments.
	 *
	 *      @type array $selected   Optional. The posts selected on the list table
	 *      @type bool  $show_cb    Optional. Whether to show the row selector checkbox as first table column
	 *      @type int   $per_page   Optional. The number of posts to show per page (-1 for no pagination)
	 * }
	 */
	public function __construct( $args ) {

		parent::__construct( $args );
		
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function display_tablenav( $which ) {}
	
	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function extra_tablenav( $which ) {}
	
	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function single_expandable_row( $item, $type ) {
		echo '<tr class="' . $type . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function column_title( $item ) {
		
		$title = '';
		if ( $this->product->get_type() == 'variation' ) {
			
			$attributes = wc_get_product_variation_attributes( $this->get_current_product_id($this->product) );
			if ( ! empty($attributes) ) {
				$title = ucfirst( implode(' ', $attributes) );
			}
			
		}
		else {
			$title = $this->product->get_title();
		}
		
		return apply_filters( 'atum/data_export/html_report/column_title', $title, $item, $this->product );
	}
	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 *
	 * @TODO: DO WE NEED TO OVERRIDE THIS METHOD?
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {
			
		$stock = intval( $this->product->get_stock_quantity() );
		
		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales
		if (! $this->allow_calcs) {
			$content = '&mdash;';
		}
		elseif ( $stock <= 0 ) {
			// no stock
			$classes .= ' cell-red';
			$content = '<span class="dashicons dashicons-dismiss"></span>';
		}
		elseif ( isset( $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) ) {
			
			// stock ok
			if ( $stock >= $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) {
				$classes .= ' cell-green';
				$content = '<span class="dashicons dashicons-yes"></span>';
			}
			// stock low
			else {
				$classes .= ' cell-yellow';
				$content = '<span class="dashicons dashicons-warning"></span>';
			}
			
		}
		else {
			$classes .= ' cell-green';
			$content = '<span class="dashicons dashicons-yes"></span>';
		}
		
		$classes = ( $classes ) ? ' class="' . $classes . '"' : '';
		
		echo '<td ' . $data . $classes . '>' .
		     apply_filters( 'atum/stock_central_list/column_stock_indicator', $content, $item, $this->product ) .
		     $this->handle_row_actions( $item, 'calc_stock_indicator', $primary ) . '</td>';
		
	}

	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	protected function get_views() {
		return apply_filters( 'atum/data_export/html_report_views' );
	}
	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	public function prepare_items() {

		// TODO: ADD THE EXPORT FILTERS TO THE $_REQUEST
		
		// Add product category to the tax query
		if ( ! empty( $_REQUEST['category'] ) ) {

		}
		
		// Change the product type tax query (initialized in constructor) to the current queried type
		if ( ! empty( $_REQUEST['type'] ) ) {

		}
		
		parent::prepare_items();
		
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function display() {

		// Add the report header


		parent::display();

	}
	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	protected function set_views_data( $args ) {}
	
}