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

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\StockCentral\Inc\ListTable;
use Atum\StockCentral\StockCentral;


class HtmlReport extends ListTable {

	/**
	 * Max length for the product titles in reports
	 * @var int
	 */
	protected $title_max_length;

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	public function __construct( $args = array() ) {

		// Avoid a PHP Notice error when loading the PDF report
		$args['screen'] = 'toplevel_page_' . StockCentral::UI_SLUG;

		if ( isset( $args['title_max_length'] ) ) {
			$this->title_max_length = absint( $args['title_max_length'] );
		}

		parent::__construct( $args );

		// Add the font icons inline for thumb and product type columns
		$this->table_columns['thumb'] = '<span class="wc-image" style="font-family: dashicons">&#xf128;</span>';
		$this->table_columns['calc_type'] = '<span class="wc-type" style="font-family: woocommerce">&#xe006;</span>';
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

		$this->allow_calcs = ( in_array( $type, Globals::get_inheritable_product_types() ) ) ? FALSE : TRUE;
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
		if ( in_array( $type, Globals::get_inheritable_product_types() ) ) {

			$product_class = '\WC_Product_' . ucwords( str_replace('-', '_', $type), '_' );
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
	protected function column_title( $item ) {
		
		$title = '';
		if ( $this->product->get_type() == 'variation' ) {
			
			$attributes = wc_get_product_variation_attributes( $this->get_current_product_id() );
			if ( ! empty($attributes) ) {
				$title = ucfirst( implode(' ', $attributes) );
			}
			
		}
		else {
			$title = $this->product->get_title();

			// Limit the title length to 20 characters
			if ( $this->title_max_length && strlen($title) > $this->title_max_length ) {
				$title = trim( substr( $title, 0, $this->title_max_length ) ) . '...';
			}
		}
		
		return apply_filters( 'atum/data_export/html_report/column_title', $title, $item, $this->product );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.3.3
	 */
	protected function column__supplier( $item ) {

		$supplier = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can('read_supplier') ) {
			return $supplier;
		}

		$supplier_id = get_post_meta( $this->get_current_product_id(), '_supplier', TRUE );

		if ($supplier_id) {

			$supplier_post = get_post($supplier_id);

			if ($supplier_post) {
				$supplier = $supplier_post->post_title;
			}

		}

		return apply_filters( 'atum/data_export/html_report/column_supplier', $supplier, $item, $this->product );
	}

	/**
	 * @inheritdoc
	 *
	 * @since 1.2.5
	 */
	protected function column_calc_type( $item ) {

		$type = $this->product->get_type();
		$product_types = wc_get_product_types();

		if ( isset($product_types[$type]) || $this->is_child ) {

			/**
			 * @see https://rawgit.com/woothemes/woocommerce-icons/master/demo.html
			 */
			$icon_char = 'e006';

			switch ( $type ) {
				case 'simple':

					if ($this->is_child) {
						$type = 'grouped-item';
						$icon_char = 'e039';

					}
					elseif ( $this->product->is_downloadable() ) {
						$type = 'downloadable';
						$icon_char = 'e001';
					}
					elseif ( $this->product->is_virtual() ) {
						$type = 'virtual';
						$icon_char = 'e000';
					}

					break;

				case 'variable':
				case 'grouped':
				case 'variable-subscription': // WC Subscriptions compatibility

					if ($this->is_child) {
						$type = 'grouped-item';
						$icon_char = 'e039';
					}
					elseif ( $this->product->has_child() ) {
						$icon_char = ($type == 'grouped') ? 'e002' : 'e003';
						$type .= ' has-child';
					}

					break;

			}

			return apply_filters( 'atum/data_export/html_report/column_type', '<span class="product-type ' . $type . '" style="font-family: woocommerce; font-size: 20px">&#x' . $icon_char . '</span>', $item, $this->product );

		}

		return '';
	}
	
	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {
			
		$stock = intval( $this->product->get_stock_quantity() );
		$dashicons_style = ' style="font-family: dashicons; font-size: 20px;"';
		
		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales
		if (! $this->allow_calcs) {
			$content = '&mdash;';
		}
		elseif ( $stock <= 0 ) {
			// no stock
			$classes .= ' cell-red';
			$content = '<span class="dashicons dashicons-dismiss"' . $dashicons_style . '>&#xf153;</span>';
		}
		elseif ( isset( $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) ) {
			
			// stock ok
			if ( $stock >= $this->calc_columns[ $this->product->get_id() ]['sold_last_days'] ) {
				$classes .= ' cell-green';
				$content = '<span class="dashicons dashicons-yes"' . $dashicons_style . '>&#xf147;</span>';
			}
			// stock low
			else {
				$classes .= ' cell-yellow';
				$content = '<span class="dashicons dashicons-warning"' . $dashicons_style . '>&#xf534;</span>';
			}
			
		}
		else {
			$classes .= ' cell-green';
			$content = '<span class="dashicons dashicons-yes"' . $dashicons_style . '>&#xf147;</span>';
		}
		
		$classes = ( $classes ) ? ' class="' . $classes . '"' : '';
		
		echo '<td ' . $data . $classes . '>' .
		     apply_filters( 'atum/data_export/html_report/column_stock_indicator', $content, $item, $this->product ) .
		     $this->handle_row_actions( $item, 'calc_stock_indicator', $primary ) . '</td>';
		
	}

	/**
	 * @inheritDoc
	 *
	 * @since 1.2.5
	 */
	protected function get_views() {
		// Views not needed in reports
		return apply_filters( 'atum/data_export/html_report/views', array() );
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

				case 'variable-subscription' :
					$product_type = __( 'Variable Subscription', ATUM_TEXT_DOMAIN );
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
	
}