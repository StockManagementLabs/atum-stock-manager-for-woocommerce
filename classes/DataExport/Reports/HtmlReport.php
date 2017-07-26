<?php
/**
 * @package         Atum\DataExport
 * @subpackage      Reports
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           1.2.5
 *
 * Extends the Stock Central's List Table and exports it as HTML report
 */

namespace Atum\DataExport\Reports;

defined( 'ABSPATH' ) or die;

use Atum\Inc\Helpers;
use Atum\StockCentral\Inc\ListTable;


class HtmlReport extends ListTable {

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function display_tablenav( $which ) {
		// Table nav not needed in reports
	}
	
	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function extra_tablenav( $which ) {
		// Extra table nav not needed in reports
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		// Row actions not needed in reports
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		// Row actions not needed in reports
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function single_row( $item ) {

		$this->product = wc_get_product( $item );
		$type = $this->product->get_type();

		$this->allow_calcs = ( in_array($type, ['variable', 'grouped']) ) ? FALSE : TRUE;
		$row_style = '';

		// mPDF has problems reading multiple classes so we have to add the row bg color inline
		if (!$this->allow_calcs) {
			$row_color = ($type == 'grouped') ? '#FEC007' : '#0073AA';
			$row_style .= ' style="background-color:' . $row_color . '" class="expanded"';
		}

		echo '<tr' . $row_style . '>';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Add the children products of each Variable and Grouped product
		if ( in_array($type, ['variable', 'grouped']) ) {

			$product_class = '\\WC_Product_' . ucfirst($type);
			$parent_product = new $product_class( $this->product->get_id() );
			$child_products = $parent_product->get_children();

			if ( ! empty($child_products) ) {

				$this->allow_calcs = TRUE;

				foreach ($child_products as $child_id) {

					// Exclude some children if there is a "Views Filter" active
					if ( ! empty($_REQUEST['v_filter']) ) {

						$v_filter = esc_attr( $_REQUEST['v_filter'] );
						if ( ! in_array($child_id, $this->id_views[ $v_filter ]) ) {
							continue;
						}

					}

					$this->is_child = TRUE;
					$this->product = wc_get_product($child_id);
					$this->single_expandable_row($this->product, ($type == 'grouped' ? $type : 'variation'));
				}
			}

		}

		// Reset the child value
		$this->is_child = FALSE;

	}
	
	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function single_expandable_row( $item, $type ) {
		// Show the child columns expanded in reports
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
		// Views not needed in reports
		return apply_filters( 'atum/data_export/html_report_views', array() );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function display() {

		// Add the report template
		ob_start();
		parent::display();

		// The title column cannot be disabled, so we must add 1 to the count
		$columns = count($this->table_columns) + 1;
		$max_columns = count($this->_args['table_columns']);
		$count_views = $this->count_views;

		if ( ! empty($_REQUEST['product_type']) ) {

			$type =  esc_attr( $_REQUEST['product_type'] );
			switch ($type) {
				case 'grouped' :
					$product_type = __( 'Grouped', ATUM_TEXT_DOMAIN );
					break;

				case 'variable' :
					$product_type = __( 'Variable', ATUM_TEXT_DOMAIN );
					break;

				case 'simple' :
					$product_type = __( 'Simple', ATUM_TEXT_DOMAIN );
					break;

				case 'downloadable' :
					$product_type = __( 'Downloadable', ATUM_TEXT_DOMAIN );
					break;

				case 'virtual' :
					$product_type = __( 'Virtual', ATUM_TEXT_DOMAIN );
					break;

				// Assuming that we'll have other types in future
				default :
					$product_type = ucfirst( $type );
					break;
			}

		}

		if ( ! empty($_REQUEST['product_cat']) ) {
			$category = ucfirst( esc_attr($_REQUEST['product_cat']) );
		}

		$report = ob_get_clean();

		Helpers::load_view('reports/stock-central-report-html', compact('report', 'columns', 'max_columns', 'product_type', 'category', 'count_views'));

	}
	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	/*protected function set_views_data( $args ) {
		// Bypass the set_views_data method overriding it
	}*/
	
}