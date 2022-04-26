<?php
/**
 * Extends the Stock Central's List Table and exports it as HTML report
 *
 * @package         Atum\DataExport
 * @subpackage      Reports
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2022 Stock Management Labs™
 *
 * @since           1.2.5
 */

namespace Atum\DataExport\Reports;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCapabilities;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\StockCentral\Lists\ListTable;


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
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {

		if ( isset( $args['title_max_length'] ) ) {
			$this->title_max_length = absint( $args['title_max_length'] );
		}

		parent::__construct( $args );

		// Add the font icons inline for thumb and product type columns.
		self::$table_columns['thumb']     = '<span class="atum-icon atmi-picture" style="font-family: atum-icon-font">&#xe827;</span>';
		self::$table_columns['calc_type'] = '<span class="atum-icon atmi-tag" style="font-family: atum-icon-font">&#xe82f;</span>';
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

		$this->list_item = Helpers::get_atum_product( $item );

		if ( ! $this->list_item instanceof \WC_Product ) {
			return;
		}

		$type              = $this->list_item->get_type();
		$this->allow_calcs = Helpers::is_inheritable_type( $type ) ? FALSE : TRUE;
		$row_style         = '';

		// mPDF has problems reading multiple classes so we have to add the row bg color inline.
		if ( ! $this->allow_calcs ) {
			$class_row = '';
			if ( 'grouped' === $type ) {
				$row_color = '#EFAF00';
			}
			elseif ( 'bundle' === $type ) {
				$row_color = '#96588a';
				$class_row = ' main-bundle';
			}
			else {
				$row_color = '#00B8DB';
			}

			$row_style .= ' style="background-color:' . $row_color . ';" class="expanded' . $class_row . '"';

		}

		do_action( 'atum/list_table/before_single_row', $item, $this );

		echo '<tr' . $row_style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		do_action( 'atum/list_table/after_single_row', $item, $this );

		// Add the children products of each Variable and Grouped product.
		if ( ! $this->allow_calcs ) {

			$product_class  = '\WC_Product_' . ucwords( str_replace( '-', '_', $type ), '_' );
			$parent_product = new $product_class( $this->list_item->get_id() );

			if ( 'bundle' === $type ) {

				$child_products = Helpers::get_bundle_items( array(
					'return'    => 'id=>product_id',
					'bundle_id' => $this->list_item->get_id(),
				) );

			}
			else {

				/**
				 * Variable definition
				 *
				 * @var \WC_Product $parent_product
				 */
				$child_products = $parent_product->get_children();

			}

			if ( ! empty( $child_products ) ) {

				$this->allow_calcs = TRUE;

				foreach ( $child_products as $child_id ) {

					// Exclude some children if there is a "Views Filter" active.
					if ( ! empty( $_REQUEST['view'] ) ) {

						$view = esc_attr( $_REQUEST['view'] );
						if ( ! in_array( $child_id, $this->id_views[ $view ] ) ) {
							continue;
						}

					}

					$this->is_child  = TRUE;
					$this->list_item = Helpers::get_atum_product( $child_id );

					if ( $this->list_item instanceof \WC_Product ) {

						if ( 'grouped' === $type ) {
							$return_type = 'grouped';
						}
						elseif ( 'bundle' === $type ) {
							$return_type = 'bundle-item';
						}
						else {
							$return_type = 'variation';
						}

						$this->single_expandable_row( $this->list_item, ( $return_type ) );

					}
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
		if ( 'variation' === $this->list_item->get_type() ) {
			
			$attributes = wc_get_product_variation_attributes( $this->get_current_list_item_id() );

			if ( ! empty( $attributes ) ) {
				$title = ucfirst( implode( ' ', $attributes ) );
			}
			
		}
		else {
			$title = $this->list_item->get_title();

			// Limit the title length to 20 characters.
			if ( $this->title_max_length && mb_strlen( $title ) > $this->title_max_length ) {
				$title = trim( mb_substr( $title, 0, $this->title_max_length ) ) . '...';
			}
		}
		
		return apply_filters( 'atum/data_export/html_report/column_title', $title, $item, $this->list_item );
	}

	/**
	 * Supplier column
	 *
	 * @since 1.3.3
	 *
	 * @param \WP_Post $item     The WooCommerce product post.
	 * @param bool     $editable Optional. Whether the current column is editable.
	 *
	 * @return string
	 */
	protected function column__supplier( $item, $editable = FALSE ) {

		$supplier = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return $supplier;
		}

		$supplier_id = $this->list_item->get_supplier_id();

		if ( $supplier_id ) {

			$supplier_post = get_post( $supplier_id );

			if ( $supplier_post ) {
				$supplier = $supplier_post->post_title;
			}

		}

		return apply_filters( 'atum/data_export/html_report/column_supplier', $supplier, $item, $this->list_item );
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

		$type          = $this->list_item->get_type();
		$product_types = wc_get_product_types();

		if ( isset( $product_types[ $type ] ) || $this->is_child ) {

			/**
			 * ATUM icons
			 */
			$icon_char = 'e9c3';

			switch ( $type ) {
				case 'simple':
					if ( $this->is_child ) {
						$type      = 'grouped-item';
						$icon_char = 'e9c2';

					}
					elseif ( $this->list_item->is_downloadable() ) {
						$type      = 'downloadable';
						$icon_char = 'e9c1';
					}
					elseif ( $this->list_item->is_virtual() ) {
						$type      = 'virtual';
						$icon_char = 'e9c5';
					}

					break;

				case 'variable':
				case 'grouped':
				case 'variable-subscription': // WC Subscriptions compatibility.
					if ( $this->is_child ) {
						$type      = 'grouped-item';
						$icon_char = 'e9c9';
					}
					elseif ( $this->list_item->has_child() ) {
						$icon_char = 'grouped' === $type ? 'e9c2' : 'e9c4';
						$type     .= ' has-child';
					}

					break;

				// WC Bookings compatibility.
				case 'booking':
					$icon_char = 'e926';

					break;
				// WC Bundle compatibility.
				case 'bundle':
					$icon_char = 'e9cf';

					break;

			}

			return apply_filters( 'atum/data_export/html_report/column_type', '<span class="product-type ' . $type . '" style="font-family: atum-icon-font; font-size: 20px">&#x' . $icon_char . ';</span>', $item, $this->list_item );

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
		
		$product_id = $this->list_item->get_id();
		$content    = '';
		
		$atum_icons_style = ' style="font-family: atum-icon-font; font-size: 20px;"';

		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales.
		if ( ! $this->allow_calcs ) {
			$content = self::EMPTY_COL;
		}
		// Stock not managed by WC.
		elseif ( ! $this->list_item->managing_stock() || 'parent' === $this->list_item->managing_stock() ) {
			
			$wc_stock_status = $this->list_item->get_stock_status();
			$content         = '<span class="atum-icon atmi-question-circle"' . $atum_icons_style . '>&#xe991;</span>';
			
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
			
			if ( $this->list_item->backorders_allowed() ) {
				$content = '<span class="atum-icon atmi-circle-minus"' . $atum_icons_style . '>&#xe935;</span>';
			}
			else {
				$classes .= ' cell-red';
				$content  = '<span class="atum-icon atmi-cross-circle"' . $atum_icons_style . '>&#xe941;</span>';
			}
			
		}
		// Restock Status.
		elseif ( in_array( $product_id, $this->id_views['restock_status'] ) ) {
			$classes .= ' cell-yellow';
			$content  = '<span class="atum-icon atmi-arrow-down-circle"' . $atum_icons_style . '>&#xe915;</span>';
		}
		// In Stock.
		elseif ( in_array( $product_id, $this->id_views['in_stock'] ) ) {
			$classes .= ' cell-green';
			$content  = '<span class="atum-icon atmi-checkmark-circle"' . $atum_icons_style . '>&#xe92c;</span>';
		}
		
		$classes = $classes ? ' class="' . $classes . '"' : '';

		echo '<td ' . esc_attr( $data ) . esc_attr( $classes ) . '>' . apply_filters( 'atum/data_export/html_report/column_stock_indicator', $content, $item, $this->list_item ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
	}

	/**
	 * Column for product location
	 *
	 * @since 1.8.4
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_calc_location( $item ) {

		$location_terms = wp_get_post_terms( $this->get_current_list_item_id(), Globals::PRODUCT_LOCATION_TAXONOMY, array( 'fields' => 'names' ) );
		$locations_list = ! empty( $location_terms ) ? implode( ', ', $location_terms ) : self::EMPTY_COL;

		return $locations_list;
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
		$columns      = count( self::$table_columns ) + 1;
		$max_columns  = count( $this->_args['table_columns'] );
		$count_views  = $this->count_views;
		$product_type = FALSE;
		$category     = FALSE;

		if ( ! empty( $_REQUEST['product_type'] ) ) {

			$type = esc_attr( $_REQUEST['product_type'] );
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

		if ( ! empty( $_REQUEST['product_cat'] ) ) {
			$category = ucfirst( esc_attr( $_REQUEST['product_cat'] ) );
		}

		$report = ob_get_clean();

		Helpers::load_view( 'reports/stock-central-html', compact( 'report', 'columns', 'max_columns', 'product_type', 'category', 'count_views' ) );

	}
	
}
