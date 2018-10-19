<?php
/**
 * Extends the Stock Central's List Table and exports it as HTML report
 *
 * @package         Atum\DataExport
 * @subpackage      Reports
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2018 Stock Management Labs™
 *
 * @since           1.2.5
 */

namespace Atum\DataExport\Reports;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Helpers;
use Atum\StockCentral\Lists\ListTable;
use Atum\Suppliers\Suppliers;


class HtmlReport extends ListTable {

	/**
	 * Max length for the product titles in reports
	 *
	 * @var int
	 */
	protected $title_max_length;

	/**
	 * Report table flag
	 *
	 * @var bool
	 */
	protected static $is_report = TRUE;

	/**
	 * HtmlReport Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 1.2.5
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type array  $table_columns     The table columns for the list table
	 *      @type array  $group_members     The column grouping members
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination)
	 *      @type array  $selected          Optional. The posts selected on the list table
	 *      @type array  $excluded          Optional. The posts excluded from the list table
	 * }
	 */
	public function __construct( $args = array() ) {

		if ( isset( $args['title_max_length'] ) ) {
			$this->title_max_length = absint( $args['title_max_length'] );
		}

		parent::__construct( $args );

		// Add the font icons inline for thumb and product type columns.
		self::$table_columns['thumb']     = '<span class="wc-image" style="font-family: dashicons">&#xf128;</span>';
		self::$table_columns['calc_type'] = '<span class="wc-type" style="font-family: woocommerce">&#xe006;</span>';
	}

	/**
	 * Generate the table navigation above or below the table
	 * Just the parent function but removing the nonce fields that are not required here
	 *
	 * @since 1.2.5
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function display_tablenav( $which ) {
		// Table nav not needed in reports.
	}
	
	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since 1.2.5
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {
		// Extra table nav not needed in reports.
	}

	/**
	 * Generate row actions div
	 *
	 * @since 1.2.5
	 *
	 * @param array $actions        The list of actions.
	 * @param bool  $always_visible Whether the actions should be always visible.
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		// Row actions not needed in reports.
	}

	/**
	 * All columns are sortable by default except cb and thumbnail
	 *
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @since 1.2.5
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array('data_values', bool)
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Loads the current product
	 *
	 * @since 1.2.5
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 */
	public function single_row( $item ) {

		$this->product     = wc_get_product( $item );
		$type              = $this->product->get_type();
		$this->allow_calcs = Helpers::is_inheritable_type( $type ) ? FALSE : TRUE;
		$row_style         = '';

		// mPDF has problems reading multiple classes so we have to add the row bg color inline.
		if ( ! $this->allow_calcs ) {
			$row_color  = 'grouped' === $type ? '#EFAF00' : '#00B8DB';
			$row_style .= ' style="background-color:' . $row_color . '" class="expanded"';

		}

		do_action( 'atum/list_table/before_single_row', $item, $this );

		echo '<tr' . $row_style . '>'; // WPCS: XSS ok.
		$this->single_row_columns( $item );
		echo '</tr>';

		do_action( 'atum/list_table/after_single_row', $item, $this );

		// Add the children products of each Variable and Grouped product.
		if ( ! $this->allow_calcs ) {

			$product_class  = '\WC_Product_' . ucwords( str_replace( '-', '_', $type ), '_' );
			$parent_product = new $product_class( $this->product->get_id() );

			/* @noinspection PhpUndefinedMethodInspection */
			$child_products = $parent_product->get_children();

			if ( ! empty( $child_products ) ) {

				$this->allow_calcs = TRUE;

				foreach ( $child_products as $child_id ) {

					// Exclude some children if there is a "Views Filter" active.
					if ( ! empty( $_REQUEST['view'] ) ) { // WPCS: CSRF ok.

						$view = esc_attr( $_REQUEST['view'] ); // WPCS: CSRF ok.
						if ( ! in_array( $child_id, $this->id_views[ $view ] ) ) {
							continue;
						}

					}

					$this->is_child = TRUE;
					$this->product  = wc_get_product( $child_id );
					$this->single_expandable_row( $this->product, ( 'grouped' === $type ? $type : 'variation' ) );
				}
			}

		}

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * Post title column
	 *
	 * @since  1.2.5
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_title( $item ) {
		
		$title = '';
		if ( 'variation' === $this->product->get_type() ) {
			
			$attributes = wc_get_product_variation_attributes( $this->get_current_product_id() );

			if ( ! empty( $attributes ) ) {
				$title = ucfirst( implode( ' ', $attributes ) );
			}
			
		}
		else {
			$title = $this->product->get_title();

			// Limit the title length to 20 characters.
			if ( $this->title_max_length && mb_strlen( $title ) > $this->title_max_length ) {
				$title = trim( mb_substr( $title, 0, $this->title_max_length ) ) . '...';
			}
		}
		
		return apply_filters( 'atum/data_export/html_report/column_title', $title, $item, $this->product );
	}

	/**
	 * Supplier column
	 *
	 * @since 1.3.3
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column__supplier( $item ) {

		$supplier = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return $supplier;
		}

		$supplier_id = get_post_meta( $this->get_current_product_id(), Suppliers::SUPPLIER_META_KEY, TRUE );

		if ( $supplier_id ) {

			$supplier_post = get_post( $supplier_id );

			if ( $supplier_post ) {
				$supplier = $supplier_post->post_title;
			}

		}

		return apply_filters( 'atum/data_export/html_report/column_supplier', $supplier, $item, $this->product );
	}

	/**
	 * Column for product type
	 *
	 * @since 1.2.5
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_calc_type( $item ) {

		$type          = $this->product->get_type();
		$product_types = wc_get_product_types();

		if ( isset( $product_types[ $type ] ) || $this->is_child ) {

			/**
			 * WooCommerce icons
			 *
			 * @see https://rawgit.com/woothemes/woocommerce-icons/master/demo.html
			 */
			$icon_char = 'e006';

			switch ( $type ) {
				case 'simple':
					if ( $this->is_child ) {
						$type      = 'grouped-item';
						$icon_char = 'e039';

					}
					elseif ( $this->product->is_downloadable() ) {
						$type      = 'downloadable';
						$icon_char = 'e001';
					}
					elseif ( $this->product->is_virtual() ) {
						$type      = 'virtual';
						$icon_char = 'e000';
					}

					break;

				case 'variable':
				case 'grouped':
				case 'variable-subscription': // WC Subscriptions compatibility.
					if ( $this->is_child ) {
						$type      = 'grouped-item';
						$icon_char = 'e039';
					}
					elseif ( $this->product->has_child() ) {
						$icon_char = 'grouped' === $type ? 'e002' : 'e003';
						$type     .= ' has-child';
					}

					break;

				// WC Bookings compatibility.
				case 'booking':
					$icon_char = 'e00e';

					break;

			}

			return apply_filters( 'atum/data_export/html_report/column_type', '<span class="product-type ' . $type . '" style="font-family: woocommerce; font-size: 20px">&#x' . $icon_char . '</span>', $item, $this->product );

		}

		return '';
	}

	/**
	 * Column for stock indicators
	 *
	 * @since 1.2.5
	 *
	 * @param \WP_Post $item    The WooCommerce product post to use in calculations.
	 * @param string   $classes
	 * @param string   $data
	 * @param string   $primary
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {
		
		$product_id = $this->product->get_id();
		$content    = '';
		
		$dashicons_style = ' style="font-family: dashicons; font-size: 20px;"';

		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales.
		if ( ! $this->allow_calcs ) {
			$content = self::EMPTY_COL;
		}
		// Stock not managed by WC.
		elseif ( ! $this->product->managing_stock() || 'parent' === $this->product->managing_stock() ) {
			
			$wc_stock_status = $this->product->get_stock_status();
			$content         = '<span class="dashicons"' . $dashicons_style . '>&#xf530;</span>';
			
			switch ( $wc_stock_status ) {
				case 'instock':
					$classes .= ' cell-green';
					break;
				
				case 'outofstock':
					$classes .= ' cell-red';
					break;
				
				case 'onbackorder':
					$classes .= ' cell-blue';
					break;
			}
			
		}
		// Out of stock.
		elseif ( in_array( $product_id, $this->id_views['out_stock'] ) ) {
			
			if ( $this->product->backorders_allowed() ) {
				$content = '<span class="dashicons"' . $dashicons_style . '>&#xf177;</span>';
			}
			else {
				$classes .= ' cell-red';
				$content  = '<span class="dashicons"' . $dashicons_style . '>&#xf153;</span>';
			}
			
		}
		// Low Stock.
		elseif ( in_array( $product_id, $this->id_views['low_stock'] ) ) {
			$classes .= ' cell-yellow';
			$content  = '<span class="dashicons"' . $dashicons_style . '>&#xf534;</span>';
		}
		// In Stock.
		elseif ( in_array( $product_id, $this->id_views['in_stock'] ) ) {
			$classes .= ' cell-green';
			$content  = '<span class="dashicons"' . $dashicons_style . '>&#xf147;</span>';
		}
		
		$classes = $classes ? ' class="' . $classes . '"' : '';

		echo '<td ' . esc_attr( $data ) . esc_attr( $classes ) . '>' . apply_filters( 'atum/data_export/html_report/column_stock_indicator', $content, $item, $this->product ) . '</td>'; // WPCS: XSS ok.
		
	}

	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 1.2.5
	 *
	 * @return array
	 */
	protected function get_views() {
		// Views not needed in reports.
		return apply_filters( 'atum/data_export/html_report/views', array() );
	}

	/**
	 * Adds the data needed for ajax filtering, sorting and pagination and displays the table
	 *
	 * @since 1.2.5
	 */
	public function display() {

		// Add the report template.
		ob_start();
		parent::display();

		// The title column cannot be disabled, so we must add 1 to the count.
		$columns     = count( self::$table_columns ) + 1;
		$max_columns = count( $this->_args['table_columns'] );
		$count_views = $this->count_views;

		if ( ! empty( $_REQUEST['product_type'] ) ) { // WPCS: CSRF ok.

			$type = esc_attr( $_REQUEST['product_type'] ); // WPCS: CSRF ok.
			switch ( $type ) {
				case 'grouped':
					$product_type = __( 'Grouped', ATUM_TEXT_DOMAIN );
					break;

				case 'variable':
					$product_type = __( 'Variable', ATUM_TEXT_DOMAIN );
					break;

				case 'variable-subscription':
					$product_type = __( 'Variable Subscription', ATUM_TEXT_DOMAIN );
					break;

				case 'simple':
					$product_type = __( 'Simple', ATUM_TEXT_DOMAIN );
					break;

				case 'downloadable':
					$product_type = __( 'Downloadable', ATUM_TEXT_DOMAIN );
					break;

				case 'virtual':
					$product_type = __( 'Virtual', ATUM_TEXT_DOMAIN );
					break;

				// Assuming that we'll have other types in future.
				default:
					$product_type = ucfirst( $type );
					break;
			}

		}

		if ( ! empty( $_REQUEST['product_cat'] ) ) { // WPCS: CSRF ok.
			$category = ucfirst( esc_attr( $_REQUEST['product_cat'] ) ); // WPCS: CSRF ok.
		}

		$report = ob_get_clean();

		Helpers::load_view( 'exports/stock-central-html', compact( 'report', 'columns', 'max_columns', 'product_type', 'category', 'count_views' ) );

	}
	
}
