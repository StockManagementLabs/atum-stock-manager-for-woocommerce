<?php
/**
 * Extends WP_List_Table to display the stock management table
 *
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Be Rebel - https://berebel.io
 * @copyright       ©2021 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumMarketingPopup;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Legacy\ListTableLegacyTrait;
use Atum\Models\Products\AtumProductTrait;
use Atum\Settings\Settings;
use Atum\Suppliers\Suppliers;
use AtumLevels\Levels\Products\BOMProductTrait;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

abstract class AtumListTable extends \WP_List_Table {

	/**
	 * The post type used to build the table (WooCommerce product)
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Current product used
	 *
	 * @var \WC_Product|\WC_Product_Variation|AtumProductTrait|BOMProductTrait
	 */
	protected $product;

	/**
	 * The table columns
	 *
	 * @var array
	 */
	protected static $table_columns;

	/**
	 * The columns that are hidden by default
	 *
	 * @var array
	 */
	protected static $default_hidden_columns = array();

	/**
	 * Which columns are numeric and searchable? and strings? append to this two keys
	 *
	 * @var array
	 */
	protected $searchable_columns = array();

	/**
	 * Set up the ATUM columns and types for correct sorting
	 *
	 * @var array
	 */
	protected $atum_sortable_columns = array();

	/**
	 * The previously selected items
	 *
	 * @var array
	 */
	protected $selected = array();

	/**
	 * Array of product IDs that are excluded from the list
	 *
	 * @var array
	 */
	protected $excluded = array();

	/**
	 * Group title columns
	 *
	 * @var array
	 */
	protected $group_columns = array();

	/**
	 * Group members
	 *
	 * @var array
	 */
	protected $group_members = array();

	/**
	 * The array of container products
	 *
	 * @var array
	 */
	protected $container_products = array(
		'variable'                  => [],
		'all_variable'              => [],
		'grouped'                   => [],
		'all_grouped'               => [],
		'variable_subscription'     => [],
		'all_variable_subscription' => [],
		'bundle'                    => [],
		'all_bundle'                => [],
	);

	/**
	 * Store parent type when in an inheritable sub-loop
	 *
	 * @var string
	 */
	protected $parent_type = '';

	/**
	 * The array of IDs of children products
	 *
	 * @var array
	 */
	protected $children_products = array();

	/**
	 * Elements per page (in order to obviate option default)
	 *
	 * @var int
	 */
	protected $per_page;

	/**
	 * Array with the id's of the products in current page
	 *
	 * @var array
	 */
	protected $current_products = array();

	/**
	 * Used to include product variations in the Supplier filterings
	 *
	 * @var array
	 */
	protected $supplier_variation_products = array();

	/**
	 * Taxonomies to filter by
	 *
	 * @var array
	 */
	protected $taxonomies = array();

	/**
	 * Extra meta args for the list query
	 *
	 * @var array
	 */
	protected $extra_meta = array();

	/**
	 * The ATUM product data used in WP_Query
	 *
	 * @var array
	 */
	protected $atum_query_data = array();

	/**
	 * The WC product data used in WP_Query (when using the new tables)
	 *
	 * @var array
	 */
	protected $wc_query_data = array();

	/**
	 * IDs for views
	 *
	 * @var array
	 */
	protected $id_views = array(
		'in_stock'   => [],
		'out_stock'  => [],
		'back_order' => [],
		'low_stock'  => [],
		'unmanaged'  => [],
	);

	/**
	 * Counters for views
	 *
	 * @var array
	 */
	protected $count_views = array(
		'count_in_stock'   => 0,
		'count_out_stock'  => 0,
		'count_back_order' => 0,
		'count_low_stock'  => 0,
		'count_unmanaged'  => 0,
		'count_all'        => 0,
	);

	/**
	 * Days to re-order from settings
	 *
	 * @var int
	 */
	protected $days_to_reorder;

	/**
	 * Time of query
	 *
	 * @var string
	 */
	protected $day;

	/**
	 * Number of days for Sold Last Days calculations
	 *
	 * @var int
	 */
	protected static $sale_days;

	/**
	 * Whether the currently displayed product is an expandable child product
	 *
	 * @var bool
	 */
	protected $is_child = FALSE;

	/**
	 * Whether or not the current product should do the calculations for the columns
	 *
	 * @var bool
	 */
	protected $allow_calcs = TRUE;

	/**
	 * Default currency symbol
	 *
	 * @var string
	 */
	protected static $default_currency;

	/**
	 * The user meta key used for first edit popup
	 *
	 * @var string
	 */
	protected $first_edit_key;

	/**
	 * Show the checkboxes in table rows
	 *
	 * @var bool
	 */
	protected $show_cb = FALSE;

	/**
	 * Whether to show products controlled by ATUM or not
	 *
	 * @var bool
	 */
	protected $show_controlled = TRUE;

	/**
	 * Columns that allow totalizers with their totals
	 *
	 * @var array
	 */
	protected $totalizers = array();

	/**
	 * Whether to show the totals row
	 *
	 * @var bool
	 */
	protected $show_totals = TRUE;

	/**
	 * Whether the current list query has a filter applied
	 *
	 * @var bool
	 */
	protected $is_filtering = FALSE;

	/**
	 * Filters being applied to the current query
	 *
	 * @var array
	 */
	protected $query_filters = array();

	/**
	 * Counter for the table rows
	 *
	 * @var int
	 */
	protected $row_count = 0;

	/**
	 * Whether to show or not the unmanaged counters
	 *
	 * @var bool
	 */
	protected $show_unmanaged_counters;

	/**
	 * The WC option where is stored whether to notify the customer when the
	 * out of stock thresholsd is reached
	 *
	 * @var string
	 */
	protected $woocommerce_notify_no_stock_amount;

	/**
	 * The columns that will be sticky
	 *
	 * @var array
	 */
	protected $sticky_columns = array();

	/**
	 * Report table flag
	 *
	 * @var bool
	 */
	protected static $is_report = FALSE;

	/**
	 * A list of available actions for the "Actions" column (if any).
	 *
	 * @var array
	 */
	protected static $row_actions = [];

	/**
	 * Value for empty columns
	 */
	const EMPTY_COL = '&#45;';


	/**
	 * AtumListTable Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *      NOTE: These args are being passed here (instead of ussing the class props) because we need to be able to alter them dynamically.
	 *
	 *      @type bool   $show_cb           Optional. Whether to show the row selector checkbox as first table column.
	 *      @type bool   $show_controlled   Optional. Whether to show items controlled by ATUM or not.
	 *      @type int    $per_page          Optional. The number of posts to show per page (-1 for no pagination).
	 *      @type array  $selected          Optional. The posts selected on the list table.
	 *      @type array  $excluded          Optional. The posts excluded from the list table.
	 * }
	 */
	public function __construct( $args = array() ) {

		$this->is_filtering  = ! empty( $_REQUEST['s'] ) || ! empty( $_REQUEST['search_column'] ) || ! empty( $_REQUEST['product_cat'] ) || ! empty( $_REQUEST['product_type'] ) || ! empty( $_REQUEST['supplier'] );
		$this->query_filters = $this->get_filters_query_string();
		$timestamp           = Helpers::get_current_timestamp( TRUE );
		$this->day           = Helpers::date_format( $timestamp, TRUE, TRUE );
		self::$sale_days     = Helpers::get_sold_last_days_option();

		// Filter the table data results to show specific product types only.
		$this->set_product_types_query_data();

		$args = wp_parse_args( $args, array(
			'show_cb'         => FALSE,
			'show_controlled' => TRUE,
			'per_page'        => Settings::DEFAULT_POSTS_PER_PAGE,
		) );

		$this->show_cb         = $args['show_cb'];
		$this->show_controlled = $args['show_controlled'];

		if ( TRUE === $this->show_totals && 'no' === Helpers::get_option( 'show_totals', 'yes' ) ) {
			$this->show_totals = FALSE;
		}

		if ( ! empty( $args['selected'] ) ) {
			$this->selected = is_array( $args['selected'] ) ? $args['selected'] : explode( ',', $args['selected'] );
		}

		if ( ! empty( $args['excluded'] ) ) {
			$this->excluded = is_array( $args['excluded'] ) ? $args['excluded'] : explode( ',', $args['excluded'] );
		}

		if ( isset( $this->group_members['product-details'] ) && TRUE === $this->show_cb ) {
			array_unshift( $this->group_members['product-details']['members'], 'cb' );
		}

		// Remove _out_stock_threshold columns if not set, or add filters to get availability etc.
		$is_out_stock_threshold_managed = 'no' === Helpers::get_option( 'out_stock_threshold', 'no' ) ? FALSE : TRUE;

		if ( ! $is_out_stock_threshold_managed ) {

			unset( self::$table_columns[ Globals::OUT_STOCK_THRESHOLD_KEY ] );

			if ( isset( $this->group_members['stock-counters']['members'] ) ) {
				$this->group_members['stock-counters']['members'] = array_diff( $this->group_members['stock-counters']['members'], array( Globals::OUT_STOCK_THRESHOLD_KEY ) );
			}

		}

		// Add the checkbox column to the table if enabled.
		self::$table_columns = TRUE === $this->show_cb ? array_merge( [ 'cb' => 'cb' ], self::$table_columns ) : self::$table_columns;

		// Add the row actions column if needed.
		if ( ! empty( self::$row_actions ) ) {

			self::$table_columns = array_merge( self::$table_columns, [ 'calc_actions' => '<span class="atum-icon atmi-magic-wand-solid tips" data-bs-placement="bottom" data-tip="' . esc_attr__( 'Actions', ATUM_TEXT_DOMAIN ) . '">' . esc_attr__( 'Actions', ATUM_TEXT_DOMAIN ) . '</span>' ] );

			if ( ! empty( $this->group_members ) ) {
				$this->group_members['actions'] = array(
					'title'   => '',
					'members' => array(
						'calc_actions',
					),
				);
			}

		}

		$this->per_page = isset( $args['per_page'] ) ? $args['per_page'] : Helpers::get_option( 'posts_per_page', Settings::DEFAULT_POSTS_PER_PAGE );
		$post_type_obj  = get_post_type_object( $this->post_type );

		if ( ! $post_type_obj ) {
			return;
		}

		// Set \WP_List_Table defaults.
		$args = array_merge( array(
			'singular'      => strtolower( $post_type_obj->labels->singular_name ),
			'plural'        => strtolower( $post_type_obj->labels->name ),
			'ajax'          => TRUE,
			'table_columns' => self::$table_columns,
			'group_members' => $this->group_members,
		), $args );

		parent::__construct( $args );

		add_filter( 'posts_search', array( $this, 'product_search' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Hook the default_hidden_columns filter used within get_hidden_columns() function.
		if ( ! empty( static::$default_hidden_columns ) ) {
			add_filter( 'default_hidden_columns', array( $this, 'hidden_columns' ), 10, 2 );
		}

		// Allow adding searchable columns externally.
		if ( ! empty( $this->searchable_columns ) ) {
			$this->searchable_columns = (array) apply_filters( 'atum/list_table/default_serchable_columns', $this->searchable_columns );
		}

		// Custom image placeholder.
		add_filter( 'woocommerce_placeholder_img', array( '\Atum\Inc\Helpers', 'image_placeholder' ), 10, 3 );

		self::$default_currency = get_woocommerce_currency();

	}

	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since  1.3.0
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function extra_tablenav( $which ) {

		if ( 'top' === $which ) : ?>

			<?php Helpers::load_view( 'list-tables/show-filters-button' ); ?>

			<div class="alignleft actions">
				<div class="actions-wrapper">

					<?php $this->table_nav_filters() ?>

				</div>
			</div>

		<?php endif;

	}

	/**
	 * Add the filters to the table nav
	 *
	 * @since 1.3.0
	 */
	protected function table_nav_filters() {

		// Category filtering.
		wc_product_dropdown_categories( array(
			'show_count' => 0,
			'selected'   => ! empty( $_REQUEST['product_cat'] ) ? esc_attr( $_REQUEST['product_cat'] ) : '',
			'class'      => 'wc-enhanced-select atum-enhanced-select dropdown_product_cat atum-tooltip auto-filter',
		) );

		// Product type filtering.
		echo Helpers::product_types_dropdown( isset( $_REQUEST['product_type'] ) ? esc_attr( $_REQUEST['product_type'] ) : '', 'wc-enhanced-select atum-enhanced-select dropdown_product_type auto-filter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Supplier filtering.
		echo Helpers::suppliers_dropdown( isset( $_REQUEST['supplier'] ) ? esc_attr( $_REQUEST['supplier'] ) : '', 'yes' === Helpers::get_option( 'enhanced_suppliers_filter', 'no' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'atum/list_table/after_nav_filters', $this );

	}

	/**
	 * Loads the current product
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 */
	public function single_row( $item ) {

		$this->product = Helpers::get_atum_product( $item );

		if ( ! $this->product instanceof \WC_Product ) {
			return;
		}

		$type              = $this->product->get_type();
		$this->allow_calcs = TRUE;
		$row_classes       = array( ( ++$this->row_count % 2 ? 'even' : 'odd' ) );

		// Inheritable products do not allow calcs.
		if ( Helpers::is_inheritable_type( $type ) ) {

			$this->parent_type = $type;
			$this->allow_calcs = FALSE;

			// WC product bundles compatibility.
			if ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $type ) {
				$this->allow_calcs = TRUE;
			}

			if ( 'grouped' === $type ) {
				$class_type = 'group';
			}
			elseif ( 'bundle' === $type ) {
				$class_type = 'bundle';
			}
			else {
				$class_type = 'variable';
			}

			$row_classes[] = $class_type;

			if ( 'yes' === Helpers::get_option( 'expandable_rows', 'no' ) ) {
				$row_classes[] = 'expanded';
			}

		}
		else {
			$this->parent_type = '';
		}

		$row_classes = apply_filters( 'atum/list_table/single_row_classes', $row_classes, $item, $this );
		$row_class   = ' class="main-row ' . implode( ' ', $row_classes ) . '"';
		$row_data    = apply_filters( 'atum/list_table/single_row_data', ' data-id="' . $this->get_current_product_id() . '"', $item, $this );

		do_action( 'atum/list_table/before_single_row', $item, $this );

		// Output the row.
		echo '<tr' . $row_data . $row_class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		do_action( 'atum/list_table/after_single_row', $item, $this );

		// If the current product has been modified within any of the columns, save it.
		if ( ! empty( $this->product->get_changes() ) ) {
			$this->product->save_atum_data();
		}

		// Add the children products of each inheritable product type.
		if ( ! $this->allow_calcs || 'bundle' === $type ) {

			if ( 'grouped' === $type ) {
				$product_type = 'product';
			}
			elseif ( 'bundle' === $type ) {
				$product_type = 'product_bundle';
			}
			else {
				$product_type = 'product_variation';
			}

			$child_products = $this->get_children( $type, [ $this->product->get_id() ], $product_type );

			if ( ! empty( $child_products ) ) {

				// If the post__in filter is applied, bypass the children that are not in the query var.
				$post_in           = get_query_var( 'post__in' );
				$this->allow_calcs = TRUE;

				foreach ( $child_products as $child_id ) {

					if ( ! empty( $post_in ) && ! in_array( $child_id, $post_in ) ) {
						continue;
					}

					// Exclude some children if there is a "Views Filter" active.
					if ( ! empty( $_REQUEST['view'] ) ) {

						$view = esc_attr( $_REQUEST['view'] );
						if ( ! in_array( $child_id, $this->id_views[ $view ] ) ) {
							continue;
						}

					}

					$this->is_child = TRUE;

					// Save the child product to the product prop.
					$this->product = Helpers::get_atum_product( $child_id );

					if ( $this->product instanceof \WC_Product ) {

						if ( 'grouped' === $type ) {
							$child_type = 'grouped';
						}
						elseif ( 'bundle' === $type ) {
							$child_type = 'bundle-item';
						}
						else {
							$child_type = 'variation';
						}

						$this->single_expandable_row( $this->product, $child_type );

						// If the current product has been modified within any of the columns, save it.
						if ( ! empty( $this->product->get_changes() ) ) {
							$this->product->save_atum_data();
						}

					}

				}
			}

		}

		// Reset the child value.
		$this->is_child = FALSE;

	}

	/**
	 * Generates content for a expandable row on the table
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Product $item The WooCommerce product.
	 * @param string      $type The type of product.
	 */
	public function single_expandable_row( $item, $type ) {

		$row_style = 'yes' !== Helpers::get_option( 'expandable_rows', 'no' ) ? ' style="display: none"' : '';

		do_action( 'atum/list_table/before_single_expandable_row', $item, $this );

		echo '<tr data-id="' . absint( $this->get_current_product_id() ) . '" class="expandable has-compounded ' . esc_attr( $type ) . '"' . $row_style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$this->single_row_columns( $item );
		echo '</tr>';

		do_action( 'atum/list_table/after_single_expandable_row', $item, $this );

	}

	/**
	 * The default column (when no specific column method found)
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item          The WooCommerce product post.
	 * @param string   $column_name   The current column name.
	 *
	 * @return string|bool
	 */
	protected function column_default( $item, $column_name ) {

		$id          = $this->get_current_product_id();
		$column_item = '';

		// Check if it's a hidden meta key (will start with underscore).
		if ( '_' === substr( $column_name, 0, 1 ) ) {

			// If the current product has a method to get the prop, use it.
			if ( is_callable( array( $this->product, "get{$column_name}" ) ) ) {
				$column_item = call_user_func( array( $this->product, "get{$column_name}" ) );
			}
			else {
				$column_item = get_post_meta( $id, $column_name, TRUE );
			}

		}

		if ( '' === $column_item || FALSE === $column_item ) {
			$column_item = self::EMPTY_COL;
		}

		return apply_filters( "atum/list_table/column_default_$column_name", $column_item, $item, $this->product, $this, $column_name );

	}

	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 1.4.15
	 *
	 * @param object $item The current item.
	 */
	public function single_row_columns( $item ) {

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$group_members = wp_list_pluck( $this->group_members, 'members' );

		foreach ( $columns as $column_name => $column_display_name ) {

			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden ) ) {
				$classes .= ' hidden';
			}

			// Add the group key as class.
			foreach ( $group_members as $group_key => $members ) {
				if ( in_array( $column_name, $members ) ) {
					$classes .= " $group_key";
					break;
				}
			}

			// Check if it's a numeric cell.
			if (
				! empty( $this->searchable_columns['numeric'] ) && is_array( $this->searchable_columns['numeric'] ) &&
				in_array( $column_name, $this->searchable_columns['numeric'], TRUE )
			) {
				$classes .= ' numeric';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

			$attributes = "class='$classes' $data";

			if ( 'cb' === $column_name ) {

				echo '<th scope="row" class="check-column column-cb">';
				echo $this->column_cb( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</th>';

			}
			elseif ( method_exists( apply_filters( "atum/list_table/column_source_object/_column_$column_name", $this, $item ), "_column_$column_name" ) ) {

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo call_user_func(
					array( apply_filters( "atum/list_table/column_source_object/_column_$column_name", $this, $item ), "_column_$column_name" ),
					$item,
					$classes,
					$data,
					$primary
				);

			}
			elseif ( method_exists( apply_filters( "atum/list_table/column_source_object/column_$column_name", $this, $item ), "column_$column_name" ) ) {

				echo "<td $attributes>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo call_user_func( array( apply_filters( "atum/list_table/column_source_object/column_$column_name", $this, $item ), "column_$column_name" ), $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</td>';

			}
			else {

				echo "<td $attributes>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				/* @noinspection PhpParamsInspection */
				echo $this->column_default( $item, $column_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</td>';

			}

		}

	}

	/**
	 * Column selector checkbox
	 *
	 * @since  0.0.1
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {

		$product_id = $this->get_current_product_id();

		return apply_filters( 'atum/list_table/column_cb', sprintf(
			'<input type="checkbox"%s name="%s[]" value="%s">',
			checked( in_array( $product_id, $this->selected ), TRUE, FALSE ),
			$this->_args['singular'],
			$product_id
		), $item, $this->product, $this );

	}

	/**
	 * Column for thumbnail
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_thumb( $item ) {

		$product_id = $this->get_current_product_id();
		$img_src    = wp_get_attachment_image_src( $this->product->get_image_id(), 'full' );
		$url        = $img_src ? $img_src[0] : get_edit_post_link( $product_id );
		$thumb      = '<a href="' . $url . '" target="_blank">' . $this->product->get_image( [ 40, 40 ] ) . '</a>';

		return apply_filters( 'atum/list_table/column_thumb', $thumb, $item, $this->product, $this );

	}

	/**
	 * Post ID column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return int
	 */
	protected function column_id( $item ) {
		return apply_filters( 'atum/list_table/column_id', $this->get_current_product_id(), $item, $this->product, $this );
	}

	/**
	 * Post title column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_title( $item ) {

		$title       = '';
		$product_id  = $this->get_current_product_id();
		$child_arrow = $this->is_child ? '<i class="atum-icon atmi-arrow-child"></i>' : '';

		if ( Helpers::is_child_type( $this->product->get_type() ) ) {

			$attributes = $this->product->get_attributes();

			if ( ! empty( $attributes ) ) {
				$title = ucfirst( implode( ' ', $attributes ) );
			}

			// Get the variable product ID to get the right link.
			$product_id = $this->product->get_parent_id();

		}
		else {
			$title = $this->product->get_title();
		}

		$title_length = absint( apply_filters( 'atum/list_table/column_title_length', 20 ) );

		if ( mb_strlen( $title ) > $title_length ) {
			$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr( $title ) . '"' : '';
			$title    = '<span class="tips"' . $data_tip . '>' . trim( mb_substr( $title, 0, $title_length ) ) . '...</span><span class="atum-title-small">' . $title . '</span>';
		}

		$title = '<a href="' . get_edit_post_link( $product_id ) . '" target="_blank">' . $child_arrow . $title . '</a>';

		return apply_filters( 'atum/list_table/column_title', $title, $item, $this->product, $this );

	}

	/**
	 * Product SKU column
	 *
	 * @since  1.1.2
	 *
	 * @param \WP_Post $item     The WooCommerce product post.
	 * @param bool     $editable Whether the SKU will be editable.
	 *
	 * @return string
	 */
	protected function column__sku( $item, $editable = TRUE ) {

		$sku = $this->product->get_sku();
		$sku = $sku ?: self::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'sku',
				'value'      => $sku,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the SKU', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'SKU', ATUM_TEXT_DOMAIN ),
			);

			$sku = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_sku', $sku, $item, $this->product, $this );

	}

	/**
	 * Supplier column
	 *
	 * @since  1.3.1
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

		$supplier_id = $this->product->get_supplier_id();

		if ( $supplier_id ) {

			$supplier_post = get_post( $supplier_id );

			if ( $supplier_post && Suppliers::POST_TYPE === $supplier_post->post_type ) {

				$supplier        = $supplier_post->post_title;
				$supplier_length = absint( apply_filters( 'atum/list_table/column_supplier_length', 20 ) );
				$supplier_abb    = mb_strlen( $supplier ) > $supplier_length ? trim( mb_substr( $supplier, 0, $supplier_length ) ) . '...' : $supplier;
				/* translators: first one is the supplier name and second is the supplier's ID */
				$supplier_tooltip = sprintf( esc_attr__( '%1$s (ID: %2$d)', ATUM_TEXT_DOMAIN ), $supplier, $supplier_id );

				$data_tip = ! self::$is_report ? ' data-tip="' . $supplier_tooltip . '"' : '';
				$supplier = '<span class="tips"' . $data_tip . '>' . $supplier_abb . '</span><span class="atum-title-small">' . $supplier_tooltip . '</span>';

			}

		}

		return apply_filters( 'atum/list_table/column_supplier', $supplier, $item, $this->product, $this );

	}

	/**
	 * Column for supplier sku
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item      The WooCommerce product post to use in calculations.
	 * @param bool     $editable  Optional. Whether the current column is editable.
	 *
	 * @return float
	 */
	protected function column__supplier_sku( $item, $editable = TRUE ) {

		$supplier_sku = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return $supplier_sku;
		}

		if ( $editable ) {

			$supplier_sku = $this->product->get_supplier_sku();

			if ( 0 === strlen( $supplier_sku ) ) {
				$supplier_sku = self::EMPTY_COL;
			}

			$args = apply_filters( 'atum/list_table/args_supplier_sku', array(
				'meta_key'   => 'supplier_sku',
				'value'      => $supplier_sku,
				'input_type' => 'text',
				'tooltip'    => esc_attr__( 'Click to edit the supplier SKU', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Supplier SKU', ATUM_TEXT_DOMAIN ),
			) );

			$supplier_sku = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_supplier_sku', $supplier_sku, $item, $this->product, $this );

	}

	/**
	 * Column for product type
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_calc_type( $item ) {

		$type = $this->product->get_type();

		$product_tip   = '';
		$product_types = wc_get_product_types();

		if ( isset( $product_types[ $type ] ) || $this->is_child ) {

			if ( ! $this->is_child ) {
				$product_tip = $product_types[ $type ];
			}

			switch ( $type ) {
				case 'simple':
					if ( $this->is_child ) {
						$type        = 'grouped-item';
						$product_tip = esc_attr__( 'Grouped item', ATUM_TEXT_DOMAIN );
					}
					elseif ( $this->product->is_downloadable() ) {
						$type        = 'downloadable';
						$product_tip = esc_attr__( 'Downloadable product', ATUM_TEXT_DOMAIN );
					}
					elseif ( $this->product->is_virtual() ) {
						$type        = 'virtual';
						$product_tip = esc_attr__( 'Virtual product', ATUM_TEXT_DOMAIN );
					}

					break;

				case 'variable':
				case 'grouped':
				case 'variable-subscription': // WC Subscriptions compatibility.
					if ( $this->is_child ) {
						$type        = 'grouped-item';
						$product_tip = esc_attr__( 'Grouped item', ATUM_TEXT_DOMAIN );
					}
					elseif ( $this->product->has_child() ) {

						$product_tip .= '<br>' . sprintf(
							/* translators: product type names */
							esc_attr__( '(click to show/hide %s)', ATUM_TEXT_DOMAIN ), 'grouped' === $type ? esc_attr__( 'grouped items', ATUM_TEXT_DOMAIN ) : esc_attr__( 'variations', ATUM_TEXT_DOMAIN )
						);
						$type .= ' has-child';

					}

					break;

				case 'variation':
					$product_tip = esc_attr__( 'Variation', ATUM_TEXT_DOMAIN );
					break;

				// WC Subscriptions compatibility.
				case 'subscription_variation':
					$type        = 'variation';
					$product_tip = esc_attr__( 'Subscription Variation', ATUM_TEXT_DOMAIN );
					break;

				// WC Bundle Products compatibility.
				case 'bundle':
					if ( $this->is_child ) {
						$type        = 'bundle-item';
						$product_tip = esc_attr__( 'Bundle item', ATUM_TEXT_DOMAIN );
					}

					$children = Helpers::get_bundle_items( array(
						'return'    => 'id=>product_id',
						'bundle_id' => $this->product->get_id(),
					) );

					if ( $children ) {
						$product_tip .= '<br>' . sprintf(
							/* translators: product type names */
							esc_attr__( '(click to show/hide %s)', ATUM_TEXT_DOMAIN ), esc_attr__( 'bundle items', ATUM_TEXT_DOMAIN )
						);
						$type .= ' has-child';
					}

					break;
			}

			$data_tip = ! self::$is_report ? ' data-tip="' . $product_tip . '"' : '';

			return apply_filters( 'atum/list_table/column_type', '<span class="product-type tips ' . $type . '"' . $data_tip . '></span>', $item, $this->product, $this );

		}

		return '';

	}

	/**
	 * Column for product location
	 *
	 * @since 1.4.2
	 *
	 * @param \WP_Post $item The WooCommerce product post.
	 *
	 * @return string
	 */
	protected function column_calc_location( $item ) {

		$has_location = $this->product->get_has_location();

		if ( is_null( $has_location ) ) {
			$location_terms = wp_get_post_terms( $this->get_current_product_id(), Globals::PRODUCT_LOCATION_TAXONOMY );
			$has_location   = ! empty( $location_terms );
			$this->product->set_has_location( $has_location );
		}

		$location_terms_class = $has_location && 'no' !== $has_location ? ' not-empty' : '';

		$data_tip  = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Show Locations', ATUM_TEXT_DOMAIN ) . '"' : '';
		$locations = '<a href="#" class="show-locations atum-icon atmi-map-marker tips' . $location_terms_class . '"' . $data_tip . ' data-locations=""></a>';

		return apply_filters( 'atum/list_table/column_locations', $locations, $item, $this->product, $this );

	}

	/**
	 * Column for regular price
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column__regular_price( $item ) {

		$regular_price = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			$regular_price_value = $this->product->get_regular_price();
			$regular_price_value = is_numeric( $regular_price_value ) ? Helpers::format_price( $regular_price_value, [
				'currency' => self::$default_currency,
			] ) : $regular_price;

			$args = apply_filters( 'atum/list_table/args_regular_price', array(
				'meta_key'  => 'regular_price',
				'value'     => $regular_price_value,
				'symbol'    => get_woocommerce_currency_symbol(),
				'currency'  => self::$default_currency,
				'tooltip'   => esc_attr__( 'Click to edit the regular price', ATUM_TEXT_DOMAIN ),
				'cell_name' => esc_attr__( 'Regular Price', ATUM_TEXT_DOMAIN ),
			), $this->product );

			$regular_price = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_regular_price', $regular_price, $item, $this->product );

	}

	/**
	 * Column for sale price
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column__sale_price( $item ) {

		$sale_price = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			$sale_price_value = $this->product->get_sale_price();
			$sale_price_value = is_numeric( $sale_price_value ) ? Helpers::format_price( $sale_price_value, [
				'currency' => self::$default_currency,
			] ) : $sale_price;

			$date_on_sale_from = $this->product->get_date_on_sale_from( 'edit' ) ? date_i18n( 'Y-m-d', $this->product->get_date_on_sale_from( 'edit' )->getOffsetTimestamp() ) : '';
			$date_on_sale_to   = $this->product->get_date_on_sale_to( 'edit' ) ? date_i18n( 'Y-m-d', $this->product->get_date_on_sale_to( 'edit' )->getOffsetTimestamp() ) : '';

			$args = apply_filters( 'atum/list_table/args_sale_price', array(
				'meta_key'   => 'sale_price',
				'value'      => $sale_price_value,
				'symbol'     => get_woocommerce_currency_symbol(),
				'currency'   => self::$default_currency,
				'tooltip'    => esc_attr__( 'Click to edit the sale price', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Sale Price', ATUM_TEXT_DOMAIN ),
				'extra_meta' => array(
					array(
						'name'        => '_sale_price_dates_from',
						'type'        => 'text',
						'placeholder' => esc_attr_x( 'Sale date from...(YYYY-MM-DD)', 'placeholder', ATUM_TEXT_DOMAIN ),
						'value'       => $date_on_sale_from,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'atum-datepicker from',
					),
					array(
						'name'        => '_sale_price_dates_to',
						'type'        => 'text',
						'placeholder' => esc_attr_x( 'Sale date to...(YYYY-MM-DD)', 'placeholder', ATUM_TEXT_DOMAIN ),
						'value'       => $date_on_sale_to,
						'maxlength'   => 10,
						'pattern'     => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
						'class'       => 'atum-datepicker to',
					),
				),
			), $this->product );

			$sale_price = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_sale_price', $sale_price, $item, $this->product );

	}

	/**
	 * Column for purchase price
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return float
	 */
	protected function column__purchase_price( $item ) {

		$purchase_price = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			return $purchase_price;
		}

		if ( $this->allow_calcs ) {

			$purchase_price_value = $this->product->get_purchase_price();
			$purchase_price_value = is_numeric( $purchase_price_value ) ? Helpers::format_price( $purchase_price_value, [
				'currency' => self::$default_currency,
			] ) : $purchase_price;

			$args = apply_filters( 'atum/list_table/args_purchase_price', array(
				'meta_key'  => 'purchase_price',
				'value'     => $purchase_price_value,
				'symbol'    => get_woocommerce_currency_symbol(),
				'currency'  => self::$default_currency,
				'tooltip'   => esc_attr__( 'Click to edit the purchase price', ATUM_TEXT_DOMAIN ),
				'cell_name' => esc_attr__( 'Purchase Price', ATUM_TEXT_DOMAIN ),
			) );

			$purchase_price = self::get_editable_column( $args );
		}

		return apply_filters( 'atum/list_table/column_purchase_price', $purchase_price, $item, $this->product, $this );

	}

	/**
	 * Column for gross profit
	 *
	 * @since 1.8.5
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	protected function column_calc_gross_profit( $item ) {

		$gross_profit = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can( 'view_purchase_price' ) ) {
			return $gross_profit;
		}

		if ( $this->allow_calcs ) {

			$purchase_price = (float) $this->product->get_purchase_price();
			$regular_price  = (float) $this->product->get_regular_price();

			// Exclude rates if prices includes them.
			if ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
				$base_tax_rates = \WC_Tax::get_base_tax_rates( $this->product->get_tax_class() );
				$base_pur_taxes = \WC_Tax::calc_tax( $purchase_price, $base_tax_rates, true );
				$base_reg_taxes = \WC_Tax::calc_tax( $regular_price, $base_tax_rates, true );
				$purchase_price = round( $purchase_price - array_sum( $base_pur_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
				$regular_price  = round( $regular_price - array_sum( $base_reg_taxes ), absint( get_option( 'woocommerce_price_num_decimals' ) ) );
			}

			if ( $purchase_price > 0 && $regular_price > 0 ) {

				$gross_profit_value      = wp_strip_all_tags( wc_price( $regular_price - $purchase_price ) );
				$gross_profit_percentage = wc_round_discount( ( 100 - ( ( $purchase_price * 100 ) / $regular_price ) ), 2 );
				$profit_margin           = (float) Helpers::get_option( 'profit_margin', 50 );
				$profit_margin_class     = $gross_profit_percentage < $profit_margin ? 'cell-red' : 'cell-green';

				if ( 'percentage' === Helpers::get_option( 'gross_profit', 'percentage' ) ) {
					$gross_profit = '<span class="tips ' . $profit_margin_class . '" data-tip="' . $gross_profit_value . '">' . $gross_profit_percentage . '%</span>';
				}
				else {
					$gross_profit = '<span class="tips ' . $profit_margin_class . '" data-tip="' . $gross_profit_percentage . '%">' . $gross_profit_value . '</span>';
				}
			}

		}

		return apply_filters( 'atum/list_table/column_gross_profit', $gross_profit, $item, $this->product, $this );

	}

	/**
	 * Column out_stock_threshold column
	 *
	 * @since 1.4.6
	 *
	 * @param \WP_Post $item      The WooCommerce product post.
	 * @param bool     $editable  Optional. Whether the current column is editable.
	 *
	 * @return double
	 */
	protected function column__out_stock_threshold( $item, $editable = TRUE ) {

		$out_stock_threshold = $this->product->get_out_stock_threshold();
		$out_stock_threshold = $out_stock_threshold ?: self::EMPTY_COL;

		// Check type and managed stock at product level (override $out_stock_threshold value if set and not allowed).
		$product_type = $this->product->get_type();
		if ( ! in_array( $product_type, Globals::get_product_types_with_stock() ) ) {
			$editable            = FALSE;
			$out_stock_threshold = self::EMPTY_COL;
		}

		$manage_stock = $this->product->get_manage_stock();

		if ( 'no' === $manage_stock ) {
			$editable            = FALSE;
			$out_stock_threshold = self::EMPTY_COL;
		}

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'out_stock_threshold',
				'value'      => $out_stock_threshold,
				'input_type' => 'number',
				'tooltip'    => esc_attr__( 'Click to edit the out of stock threshold', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Out of Stock Threshold', ATUM_TEXT_DOMAIN ),
			);

			$out_stock_threshold = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_out_stock_threshold', $out_stock_threshold, $item, $this->product, $this );

	}

	/**
	 * Column Weight column
	 *
	 * @since 1.4.6
	 *
	 * @param \WP_Post $item      The WooCommerce product post.
	 * @param bool     $editable  Optional. Whether the current column is editable.
	 *
	 * @return double
	 */
	protected function column__weight( $item, $editable = TRUE ) {

		$weight = $this->product->get_weight();
		$weight = $weight ?: self::EMPTY_COL;

		if ( $editable ) {

			$args = array(
				'meta_key'   => 'weight',
				'value'      => $weight,
				'input_type' => 'number',
				'tooltip'    => esc_attr__( 'Click to edit the weight', ATUM_TEXT_DOMAIN ),
				'cell_name'  => esc_attr__( 'Weight', ATUM_TEXT_DOMAIN ),
			);

			$weight = self::get_editable_column( $args );

		}

		return apply_filters( 'atum/list_table/column_weight', $weight, $item, $this->product, $this );

	}

	/**
	 * Column for stock amount
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 * @param bool     $editable Whether the stock will be editable.
	 *
	 * @return string|int
	 */
	protected function column__stock( $item, $editable = TRUE ) {

		$stock = self::EMPTY_COL;

		$classes_title             = '';
		$tooltip_warning           = '';
		$wc_notify_no_stock_amount = wc_stock_amount( get_option( 'woocommerce_notify_no_stock_amount' ) );
		$is_grouped                = 'grouped' === $this->product->get_type();
		$is_inheritable            = Helpers::is_inheritable_type( $this->product->get_type() );
		$editable                  = apply_filters( 'atum/list_table/editable_column_stock', $editable, $this->product );

		// Do not show the stock if the product is not managed by WC.
		if ( ! $is_inheritable && ( ! $this->product->managing_stock() || 'parent' === $this->product->managing_stock() ) ) {
			return apply_filters( 'atum/list_table/column_stock', $stock, $item, $this->product, $this );
		}

		if ( ! $is_grouped ) {
			$stock = wc_stock_amount( $this->product->get_stock_quantity() );
		}

		if ( 0 !== $stock ) {
			$this->increase_total( '_stock', $stock );
		}

		// Check the Out of Stock Threshold.
		if ( $this->product->managing_stock() ) {

			// Setings value is enabled?
			$is_out_stock_threshold_managed = 'no' === Helpers::get_option( 'out_stock_threshold', 'no' ) ? FALSE : TRUE;

			if ( $is_out_stock_threshold_managed && ! $is_grouped ) {

				$out_stock_threshold = $this->product->get_out_stock_threshold();

				if ( strlen( $out_stock_threshold ) > 0 ) {

					if ( wc_stock_amount( $out_stock_threshold ) >= $stock ) {

						if ( ! $editable ) {
							$classes_title = ' class="cell-yellow" title="' . esc_attr__( 'Stock is below the Out of Stock Threshold', ATUM_TEXT_DOMAIN ) . '"';
						}
						else {
							$classes_title   = ' class="cell-yellow"';
							$tooltip_warning = esc_attr__( "Click to edit the stock quantity (it's below the Out of Stock Threshold)", ATUM_TEXT_DOMAIN );
						}

					}

				}
				elseif ( $wc_notify_no_stock_amount >= $stock ) {

					if ( ! $editable ) {
						$classes_title = ' class="cell-yellow" title="' . esc_attr__( 'Stock is below the Out of Stock Threshold', ATUM_TEXT_DOMAIN ) . '"';
					}
					else {
						$classes_title   = ' class="cell-yellow"';
						$tooltip_warning = esc_attr__( "Click to edit the stock quantity (it's below the Out of Stock Threshold)", ATUM_TEXT_DOMAIN );
					}

				}

			}
			elseif ( $wc_notify_no_stock_amount >= $stock ) {

				if ( ! $editable ) {
					$classes_title = ' class="cell-yellow" title="' . esc_attr__( 'Stock is below the Out of Stock Threshold', ATUM_TEXT_DOMAIN ) . '"';
				}
				else {
					$classes_title   = ' class="cell-yellow"';
					$tooltip_warning = esc_attr__( "Click to edit the stock quantity (it's below the Out of Stock Threshold)", ATUM_TEXT_DOMAIN );
				}

			}

		}

		if ( $editable && ! $is_grouped ) {

			$args = array(
				'meta_key'  => 'stock',
				'value'     => $stock,
				'tooltip'   => $tooltip_warning ?: esc_attr__( 'Click to edit the stock quantity', ATUM_TEXT_DOMAIN ),
				'cell_name' => esc_attr__( 'Stock Quantity', ATUM_TEXT_DOMAIN ),
			);

			$stock = self::get_editable_column( $args );

		}

		$stock_html = "<span{$classes_title}>{$stock}</span>";

		if ( $is_inheritable ) {
			$tooltip     = esc_attr__( 'Compounded stock quantity', ATUM_TEXT_DOMAIN );
			$stock_html .= " | <span class='compounded tips' data-tip='$tooltip'>" . self::EMPTY_COL . '</span>';
		}

		return apply_filters( 'atum/list_table/column_stock', $stock_html, $item, $this->product, $this );

	}

	/**
	 * Column for back orders amount: show amount if items pending to serve and without existences
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int|string
	 */
	protected function column_calc_back_orders( $item ) {

		$back_orders = self::EMPTY_COL;

		if ( $this->allow_calcs ) {

			$back_orders = '--';
			if ( $this->product->backorders_allowed() && 'onbackorder' === $this->product->get_atum_stock_status() ) {
				$back_orders = $this->product->get_stock_quantity();
			}

			$this->increase_total( 'calc_back_orders', $back_orders );

		}

		return apply_filters( 'atum/list_table/column_back_orders', $back_orders, $item, $this->product, $this );

	}

	/**
	 * Column for inbound stock: shows sum of inbound stock within Purchase Orders
	 *
	 * @since 1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return int
	 */
	protected function column__inbound_stock( $item ) {

		$inbound_stock = self::EMPTY_COL;

		if ( $this->allow_calcs ) {
			$inbound_stock = Helpers::get_product_inbound_stock( $this->product );
			$this->increase_total( '_inbound_stock', $inbound_stock );
		}

		return apply_filters( 'atum/list_table/column_inbound_stock', $inbound_stock, $item, $this->product, $this );
	}

	/**
	 * Column for stock indicators
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 * @param string   $classes
	 * @param string   $data
	 * @param string   $primary
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {

		$product_id = $this->get_current_product_id();
		$content    = self::EMPTY_COL;

		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales.
		if ( $this->allow_calcs ) {

			// Stock not managed by WC.
			if ( ! $this->product->managing_stock() || 'parent' === $this->product->managing_stock() ) {

				$wc_stock_status = $this->product->get_stock_status();

				switch ( $wc_stock_status ) {
					case 'instock':
						$classes .= ' cell-green';
						$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'In Stock (not managed by WC)', ATUM_TEXT_DOMAIN ) . '"' : '';
						$content  = '<span class="atum-icon atmi-question-circle tips"' . $data_tip . '></span>';
						break;

					case 'outofstock':
						$classes .= ' cell-red';
						$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Out of Stock (not managed by WC)', ATUM_TEXT_DOMAIN ) . '"' : '';
						$content  = '<span class="atum-icon atmi-question-circle tips"' . $data_tip . '></span>';
						break;

					case 'onbackorder':
						$classes .= ' cell-yellow';
						$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'On Backorder (not managed by WC)', ATUM_TEXT_DOMAIN ) . '"' : '';
						$content  = '<span class="atum-icon atmi-question-circle tips"' . $data_tip . '></span>';
						break;
				}

			}
			// Out of stock.
			elseif ( in_array( $product_id, $this->id_views['out_stock'] ) ) {
				$classes .= ' cell-red';
				$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Out of Stock', ATUM_TEXT_DOMAIN ) . '"' : '';
				$content  = '<span class="atum-icon atmi-cross-circle tips"' . $data_tip . '></span>';
			}
			// Back Orders.
			elseif ( in_array( $product_id, $this->id_views['back_order'] ) ) {
				$classes .= ' cell-yellow';
				$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Out of Stock (back orders allowed)', ATUM_TEXT_DOMAIN ) . '"' : '';
				$content  = '<span class="atum-icon atmi-circle-minus tips"' . $data_tip . '></span>';
			}
			// Low Stock.
			elseif ( in_array( $product_id, $this->id_views['low_stock'] ) ) {
				$classes .= ' cell-blue';
				$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Low Stock', ATUM_TEXT_DOMAIN ) . '"' : '';
				$content  = '<span class="atum-icon atmi-arrow-down-circle tips"' . $data_tip . '></span>';
			}
			// In Stock.
			elseif ( in_array( $product_id, $this->id_views['in_stock'] ) ) {
				$classes .= ' cell-green';
				$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr__( 'In Stock', ATUM_TEXT_DOMAIN ) . '"' : '';
				$content  = '<span class="atum-icon atmi-checkmark-circle tips"' . $data_tip . '></span>';
			}

			$content = apply_filters( 'atum/list_table/column_stock_indicator', $content, $item, $this->product, $this );

		}

		$classes = apply_filters( 'atum/list_table/column_stock_indicator_classes', $classes, $this->product );
		$classes = $classes ? ' class="' . $classes . '"' : '';

		echo '<td ' . $data . $classes . '>' . $content . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Column for row actions
	 *
	 * @since 1.8.4
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations.
	 *
	 * @return string
	 */
	public function column_calc_actions( $item ) {

		if ( apply_filters( 'atum/list_table/allow_row_actions', empty( self::$row_actions ), $item, $this->product, $this ) ) {
			return '';
		}

		$actions_button = '<i class="show-actions atum-icon atmi-options" data-bs-placement="left"></i>';

		return apply_filters( 'atum/list_table/column_actions', $actions_button, $item, $this->product, $this );

	}

	/**
	 * REQUIRED! This method dictates the table's columns and titles
	 * This should return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see WP_List_Table::single_row_columns()
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'.
	 */
	public function get_columns() {

		$result = array();

		foreach ( self::$table_columns as $column_name => $column_label ) {
			$group                  = $this->search_group_columns( $column_name );
			$result[ $column_name ] = $group ? "<span class='col-$group'>$column_label</span>" : $column_label;
		}

		return apply_filters( 'atum/list_table/columns', $result );
	}

	/**
	 * Returns primary column name
	 *
	 * @since 0.0.8
	 *
	 * @return string   Name of the default primary column.
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Create an editable meta cell
	 *
	 * @since 1.2.0
	 *
	 * @param array $args {
	 *      Array of arguments.
	 *
	 *      @type int    $post_id           The current post ID.
	 *      @type string $meta_key          The meta key name (without initial underscore) to be saved.
	 *      @type mixed  $value             The new value for the meta key cell.
	 *      @type string $symbol            Whether to add any symbol to value.
	 *      @type string $tooltip           The informational tooltip text.
	 *      @type string $input_type        The input type field to use to edit the column value.
	 *      @type array  $extra_meta        Any extra fields will be appended to the popover (as JSON array).
	 *      @type string $tooltip_position  Where to place the tooltip.
	 *      @type string $cell_name         The display name for the cell.
	 *      @type array  $extra_data        Any other array of data that should be added to the element.
	 * }
	 *
	 * @return string
	 */
	public static function get_editable_column( $args ) {

		/**
		 * Variable definitions
		 *
		 * @var string $meta_key
		 * @var mixed  $value
		 * @var string $symbol
		 * @var string $tooltip
		 * @var string $input_type
		 * @var array  $extra_meta
		 * @var string $tooltip_position
		 * @var string $cell_name
		 * @var array  $extra_data
		 */
		extract( wp_parse_args( $args, array(
			'meta_key'         => '',
			'value'            => '',
			'symbol'           => '',
			'tooltip'          => '',
			'input_type'       => 'number',
			'extra_meta'       => array(),
			'tooltip_position' => 'top',
			'cell_name'        => '',
			'extra_data'       => array(),
		) ) );

		$extra_meta_data = ! empty( $extra_meta ) ? ' data-extra-meta="' . htmlspecialchars( wp_json_encode( $extra_meta ), ENT_QUOTES, 'UTF-8' ) . '"' : '';
		$symbol_data     = ! empty( $symbol ) ? ' data-symbol="' . esc_attr( $symbol ) . '"' : '';
		$extra_data      = ! empty( $extra_data ) ? Helpers::array_to_data( $extra_data ) : '';

		// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
		ob_start(); ?>
		<span class="atum-tooltip" title="<?php echo esc_attr( $tooltip ) ?>" data-bs-placement="<?php echo esc_attr( $tooltip_position ) ?>">
			<span class="set-meta" data-meta="<?php echo esc_attr( $meta_key ) ?>"
				<?php echo $symbol_data . $extra_meta_data . $extra_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				data-input-type="<?php echo esc_attr( $input_type ) ?>" data-cell-name="<?php echo esc_attr( $cell_name ) ?>"
			>
				<?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		</span>
		<?php

		return apply_filters( 'atum/list_table/editable_column', ob_get_clean(), $args );

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
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array('data_values', bool).
	 */
	protected function get_sortable_columns() {

		$not_sortable     = array( 'thumb', 'cb' );
		$sortable_columns = array();

		foreach ( self::$table_columns as $key => $column ) {
			if ( ! in_array( $key, $not_sortable ) && 0 !== strpos( $key, 'calc_' ) ) {
				$sortable_columns[ $key ] = array( $key, FALSE );
			}
		}

		return apply_filters( 'atum/list_table/sortable_columns', $sortable_columns );
	}

	/**
	 * Get an associative array ( id => link ) with the list of available views on this table.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	protected function get_views() {

		$views = array();
		$view  = ! empty( $_REQUEST['view'] ) ? esc_attr( $_REQUEST['view'] ) : 'all_stock';

		$views_name = array(
			'all_stock'  => __( 'All', ATUM_TEXT_DOMAIN ),
			'in_stock'   => __( 'In Stock', ATUM_TEXT_DOMAIN ),
			'out_stock'  => __( 'Out of Stock', ATUM_TEXT_DOMAIN ),
			'back_order' => __( 'Backorder', ATUM_TEXT_DOMAIN ),
			'low_stock'  => __( 'Low Stock', ATUM_TEXT_DOMAIN ),
			'unmanaged'  => __( 'Unmanaged by WC', ATUM_TEXT_DOMAIN ),
		);

		if ( $this->show_unmanaged_counters ) {

			unset( $views_name['unmanaged'] );

			$views = array(
				'all_stock'  => array(
					'all'       => 'all_stock',
					'managed'   => 'managed',
					'unmanaged' => 'unmanaged',
				),
				'in_stock'   => array(
					'all'       => 'all_in_stock',
					'managed'   => 'in_stock',
					'unmanaged' => 'unm_in_stock',
				),
				'out_stock'  => array(
					'all'       => 'all_out_stock',
					'managed'   => 'out_stock',
					'unmanaged' => 'unm_out_stock',
				),
				'back_order' => array(
					'all'       => 'all_back_order',
					'managed'   => 'back_order',
					'unmanaged' => 'unm_back_order',
				),
			);

		}

		global $plugin_page;

		if ( ! $plugin_page && ! empty( $this->_args['screen'] ) ) {
			$plugin_page = str_replace( Globals::ATUM_UI_HOOK . '_page_', '', $this->_args['screen'] );
		}

		$url = esc_url( add_query_arg( 'page', $plugin_page, admin_url() ) );

		foreach ( $views_name as $key => $text ) {

			$class   = $id = $active = $empty = '';
			$classes = array();

			$current_all = ! empty( $views[ $key ]['all'] ) ? $views[ $key ]['all'] : $key;

			if ( 'all_stock' === $current_all ) {
				$count    = $this->count_views['count_all'];
				$view_url = $url;
			}
			else {

				if ( ! empty( $views[ $key ] ) ) {
					$count = $this->count_views[ 'count_' . $views[ $key ]['all'] ];
				}
				else {
					$count = $this->count_views[ 'count_' . $key ];
				}

				$view_url = esc_url( add_query_arg( array( 'view' => $current_all ), $url ) );
				$id       = ' id="' . $current_all . '"';
			}

			$query_filters = $this->query_filters;

			if ( $current_all === $view || ( ! $view && 'all_stock' === $current_all ) ) {
				$classes[] = 'current';
				$active    = ' class="active"';
			}
			else {
				$query_filters['paged'] = 1;
			}

			if ( ! $count ) {
				$classes[] = 'empty';
				$empty     = 'empty';
			}

			if ( $classes ) {
				$class = ' class="' . implode( ' ', $classes ) . '"';
			}

			$hash_params = http_build_query( array_merge( $query_filters, array( 'view' => $current_all ) ) );

			if ( ! empty( $views[ $key ] ) && $this->show_controlled ) {

				$extra_links = '';

				if ( ! empty( $views[ $key ]['managed'] ) ) {

					$man_class = array( 'tips' );
					$man_url   = esc_url( add_query_arg( array( 'view' => $views[ $key ]['managed'] ), $url ) );
					$man_id    = ' id="' . $views[ $key ]['managed'] . '"';
					$man_count = $this->count_views[ 'count_' . $views[ $key ]['managed'] ];

					$query_filters = $this->query_filters;

					if ( ( $views[ $key ]['managed'] === $view ) ) {
						$man_class[] = 'current';
						$active      = ' class="active"';
					}
					else {
						$query_filters['paged'] = 1;
					}

					if ( ! $man_count ) {
						$man_class[] = 'empty';
						$empty       = 'empty';
					}

					if ( $man_class ) {
						$man_class = ' class="' . implode( ' ', $man_class ) . '"';
					}
					else {
						$man_class = '';
					}

					$man_hash_params = http_build_query( array_merge( $query_filters, array( 'view' => $views[ $key ]['managed'] ) ) );
					$data_tip        = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Managed by WC', ATUM_TEXT_DOMAIN ) . '"' : '';
					$extra_links    .= '<a' . $man_id . $man_class . ' href="' . $man_url . '" rel="address:/?' . $man_hash_params . '"' . $data_tip . '>' . $man_count . '</a>';

				}

				if ( ! empty( $views[ $key ]['unmanaged'] ) ) {

					$unm_class = array( 'tips' );
					$unm_url   = esc_url( add_query_arg( array( 'view' => $views[ $key ]['unmanaged'] ), $url ) );
					$unm_id    = ' id="' . $views[ $key ]['unmanaged'] . '"';
					$unm_count = $this->count_views[ 'count_' . $views[ $key ]['unmanaged'] ];

					$query_filters = $this->query_filters;

					if ( ( $views[ $key ]['unmanaged'] === $view ) ) {
						$unm_class[] = 'current';
						$active      = ' class="active"';
					}
					else {
						$query_filters['paged'] = 1;
					}

					if ( ! $unm_count ) {
						$unm_class[] = 'empty';
					}

					if ( $unm_class ) {
						$unm_class = ' class="' . implode( ' ', $unm_class ) . '"';
					}
					else {
						$unm_class = '';
					}

					$unm_hash_params = http_build_query( array_merge( $query_filters, array( 'view' => $views[ $key ]['unmanaged'] ) ) );
					$data_tip        = ! self::$is_report ? ' data-tip="' . esc_attr__( 'Unmanaged by WC', ATUM_TEXT_DOMAIN ) . '"' : '';
					$extra_links    .= ',<a' . $unm_id . $unm_class . ' href="' . $unm_url . '" rel="address:/?' . $unm_hash_params . '"' . $data_tip . '>' . $unm_count . '</a>';

				}

				$views[ $key ] = '<span' . $active . '><a' . $id . $class . ' href="' . $view_url . '" rel="address:/?' . $hash_params . '"><span' . $active . '>' . $text . ' <span class="count ' . $empty . '">' . $count . '</span></span></a> <span class="extra-links-container ' . $empty . '">(' . $extra_links . ')</span></span>';

			}
			else {
				$views[ $key ] = '<span' . $active . '><a' . $id . $class . ' href="' . $view_url . '" rel="address:/?' . $hash_params . '"><span' . $active . '>' . $text . ' <span class="count extra-links-container ' . $empty . '">(' . $count . ')</span></span></a></span>';
			}

		}

		return apply_filters( 'atum/list_table/view_filters', $views );

	}

	/**
	 * Display the list of views available on this table
	 *
	 * @since 1.4.3
	 */
	public function views() {

		$views = $this->get_views();
		$views = apply_filters( "views_{$this->screen->id}", $views ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( empty( $views ) ) {
			return;
		}

		$this->screen->render_screen_reader_content( 'heading_views' );

		?>
		<ul class="subsubsub extend-list-table">
			<?php
			foreach ( $views as $class => $view ) :
				$views[ $class ] = "\t<li class='$class'>$view";
			endforeach;

			echo implode( "</li>\n", $views ) . "</li>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</ul>
		<?php

	}

	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'.
	 */
	protected function get_bulk_actions() {

		$bulk_actions = array(
			'manage_stock'   => __( "Enable WC's Manage Stock", ATUM_TEXT_DOMAIN ),
			'unmanage_stock' => __( "Disable WC's Manage Stock", ATUM_TEXT_DOMAIN ),
		);

		if (
			( isset( $_GET['uncontrolled'] ) && 1 === absint( $_GET['uncontrolled'] ) ) ||
			( isset( $_REQUEST['show_controlled'] ) && 0 === absint( $_REQUEST['show_controlled'] ) )
		) {
			$bulk_actions['control_stock'] = __( "Enable ATUM's Stock Control", ATUM_TEXT_DOMAIN );
		}
		else {
			$bulk_actions['uncontrol_stock'] = __( "Disable ATUM's Stock Control", ATUM_TEXT_DOMAIN );
		}

		return apply_filters( 'atum/list_table/bulk_actions', $bulk_actions, $this );

	}

	/**
	 * Display the bulk actions dropdown
	 *
	 * @since 1.4.1
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {

		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			$this->_actions = apply_filters( "atum/list_table/bulk_actions-{$this->screen->id}", $this->_actions );
			$two            = '';
		}
		else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		?>
		<label for="bulk-action-selector-<?php echo esc_attr( $which ) ?>" class="screen-reader-text"><?php esc_html_e( 'Select bulk action', ATUM_TEXT_DOMAIN ) ?></label>
		<select name="action<?php echo esc_attr( $two ) ?>" class="wc-enhanced-select atum-enhanced-select atum-tooltip" id="bulk-action-selector-<?php echo esc_attr( $which ) ?>" autocomplete="off">
			<option value="-1"><?php esc_html_e( 'Bulk Actions', ATUM_TEXT_DOMAIN ) ?></option>

			<?php foreach ( $this->_actions as $name => $title ) : ?>
				<option value="<?php echo esc_attr( $name ) ?>"<?php if ( 'edit' === $name ) echo ' class="hide-if-no-js"' ?>><?php echo esc_html( $title ) ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->add_apply_bulk_action_button();
	}

	/**
	 * Adds the Bulk Actions' apply button to the List Table view
	 *
	 * @since 1.4.1
	 */
	public function add_apply_bulk_action_button() {
		?>
		<button type="button" class="apply-bulk-action btn btn-warning" style="display: none">
			<?php esc_html_e( 'Apply', ATUM_TEXT_DOMAIN ) ?>
		</button>
		<?php
	}

	/**
	 * If the site is not using the new tables, use the legacy method
	 *
	 * @since 1.5.0
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use ListTableLegacyTrait;

	/**
	 * Prepare the table data
	 *
	 * @since 0.0.1
	 */
	public function prepare_items() {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			$this->prepare_items_legacy();
			return;
		}

		/**
		 * Define our column headers
		 */
		$columns             = $this->get_columns();
		$posts               = array();
		$sortable            = $this->get_sortable_columns();
		$hidden              = get_hidden_columns( $this->screen );
		$this->group_columns = $this->calc_groups( $hidden );

		/**
		 * REQUIRED. Build an array to be used by the class for column headers
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => $this->per_page,
			'paged'          => $this->get_pagenum(),
		);

		/**
		 * Get Controlled or Uncontrolled items
		 */
		$this->set_controlled_query_data();

		/**
		 * Tax filter
		 */

		// Add product category to the tax query.
		if ( ! empty( $_REQUEST['product_cat'] ) ) {

			$this->taxonomies[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => esc_attr( $_REQUEST['product_cat'] ),
			);

		}

		// Change the product type tax query (initialized in constructor) to the current queried type.
		if ( ! empty( $_REQUEST['product_type'] ) && ! empty( $this->wc_query_data['where'] ) ) {

			$type = esc_attr( $_REQUEST['product_type'] );

			foreach ( $this->wc_query_data['where'] as $index => $query_arg ) {

				if ( isset( $query_arg['key'] ) && 'type' === $query_arg['key'] ) {

					if ( in_array( $type, [ 'downloadable', 'virtual' ] ) ) {

						$this->wc_query_data['where'][ $index ]['value'] = 'simple';

						$this->wc_query_data['where'][] = array(
							'key'   => $type,
							'value' => 1,
							'type'  => 'NUMERIC',
						);

					}
					else {
						$this->wc_query_data['where'][ $index ]['value'] = $type;
					}

					break;
				}

			}

		}

		if ( $this->taxonomies ) {
			$args['tax_query'] = (array) apply_filters( 'atum/list_table/taxonomies', $this->taxonomies );
		}

		/**
		 * Extra meta args
		 */
		if ( ! empty( $this->extra_meta ) ) {
			$args['meta_query'][] = $this->extra_meta;
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {

			// Add the orderby args.
			$args = $this->parse_orderby_args( $args );

		}

		/**
		 * Searching
		 */
		if ( ! empty( $_REQUEST['search_column'] ) ) {
			$args['search_column'] = esc_attr( $_REQUEST['search_column'] );
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( urldecode( stripslashes( $_REQUEST['s'] ) ) );
		}

		/**
		 * Supplier filter
		 * NOTE: it's important to run this filter after processing all the rest because we need to pass the $args through.
		 */
		if ( ! empty( $_REQUEST['supplier'] ) && AtumCapabilities::current_user_can( 'read_supplier' ) ) {

			$supplier = absint( $_REQUEST['supplier'] );

			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = array(
				'key'   => 'supplier_id',
				'value' => $supplier,
				'type'  => 'NUMERIC',
			);

			// This query does not get product variations and as each variation may have a distinct supplier,
			// we have to get them separately and to add their variables to the results.
			$this->supplier_variation_products = Suppliers::get_supplier_products( $supplier, [ 'product_variation' ], TRUE, $args );

			if ( ! empty( $this->supplier_variation_products ) ) {
				add_filter( 'atum/list_table/views_data_products', array( $this, 'add_supplier_variables_to_query' ), 10, 2 );
				add_filter( 'atum/list_table/items', array( $this, 'add_supplier_variables_to_query' ), 10, 2 );
				add_filter( 'atum/list_table/views_data_variations', array( $this, 'add_supplier_variations_to_query' ), 10, 2 );
			}

		}

		// Let others play.
		$args = apply_filters( 'atum/list_table/prepare_items/args', $args );

		// Build "Views Filters" and calculate totals.
		$this->set_views_data( $args );

		$allow_query = TRUE;

		/**
		 * REQUIRED. Register our pagination options & calculations
		 */
		$found_posts = isset( $this->count_views['count_all'] ) ? $this->count_views['count_all'] : 0;

		if ( ! empty( $_REQUEST['view'] ) ) {

			$view        = esc_attr( $_REQUEST['view'] );
			$allow_query = FALSE;

			foreach ( $this->id_views as $key => $post_ids ) {

				if ( $view === $key ) {

					$this->supplier_variation_products = array_intersect( $this->supplier_variation_products, $post_ids );

					if ( ! empty( $post_ids ) ) {

						$get_parents = FALSE;
						$parents     = array();

						foreach ( Globals::get_inheritable_product_types() as $inheritable_product_type ) {

							if ( ! empty( $this->container_products[ $inheritable_product_type ] ) ) {
								$get_parents = TRUE;
								break;
							}

						}

						if ( $get_parents ) {

							$parents = $this->get_variation_parents( $post_ids );

							// Exclude the parents with no children.
							// For example: the current list may have the "Out of stock" filter applied and a variable product
							// may have all of its variations in stock, but its own stock could be 0. It shouldn't appear empty.
							$empty_variables = array_diff( $this->container_products['variable'], $parents );

							foreach ( $empty_variables as $empty_variable ) {
								if ( in_array( $empty_variable, $post_ids ) ) {
									unset( $post_ids[ array_search( $empty_variable, $post_ids ) ] );
								}
							}

							// Get the Grouped parents.
							if ( ! empty( $this->container_products['grouped'] ) ) {

								$grouped_parents = $this->get_grouped_parents( $post_ids );

								$empty_grouped_parents = array_diff( $this->container_products['grouped'], $parents );

								foreach ( $empty_grouped_parents as $empty_grouped ) {
									if ( in_array( $empty_grouped, $post_ids ) ) {
										unset( $post_ids[ array_search( $empty_grouped, $post_ids ) ] );
									}
								}

								$parents = array_merge( $parents, $grouped_parents );

							}

						}

						// Add the parent products again to the query.
						$args['post__in'] = array_merge( $parents, $post_ids );
						$allow_query      = TRUE;
						$found_posts      = $this->count_views[ "count_$key" ];
					}

				}

			}
		}

		if ( $allow_query ) {

			if ( ! empty( $this->excluded ) ) {

				if ( isset( $args['post__not_in'] ) ) {
					$args['post__not_in'] = array_merge( $args['post__not_in'], $this->excluded );
				}
				else {
					$args['post__not_in'] = $this->excluded;
				}

			}

			// Setup the WP query.
			global $wp_query;

			// Pass through the ATUM query data and WC query data filters.
			do_action( 'atum/list_table/before_query_data' );
			add_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$wp_query = new \WP_Query( $args );
			remove_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			do_action( 'atum/list_table/after_query_data' );

			$posts = $wp_query->posts;

			if ( $found_posts > 0 && empty( $posts ) ) {
				$args['paged']     = 1;
				$_REQUEST['paged'] = $args['paged'];
				// Pass through the ATUM query data filter.
				do_action( 'atum/list_table/before_query_data' );
				add_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
				add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				$wp_query = new \WP_Query( $args );
				remove_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
				remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
				do_action( 'atum/list_table/after_query_data' );

				$posts = $wp_query->posts;
			}

			$product_ids = wp_list_pluck( $posts, 'ID' );

			$this->current_products = $product_ids;
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$total_pages = ( - 1 == $this->per_page || ! $wp_query->have_posts() ) ? 0 : ceil( $wp_query->found_posts / $this->per_page );

		}
		else {
			$found_posts = $total_pages = 0;
		}

		/**
		 * REQUIRED!!!
		 * Save the sorted data to the items property, where can be used by the rest of the class.
		 */
		$this->items = apply_filters( 'atum/list_table/items', $posts, 'posts' );

		$this->set_pagination_args( array(
			'total_items' => $found_posts,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
			'orderby'     => ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ! empty( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'desc',
		) );

	}

	/**
	 * Set the query data for filtering the Controlled/Uncontrolled products.
	 *
	 * @since 1.5.0
	 */
	protected function set_controlled_query_data() {

		// Do not need to alter the query data if the 'atum_controlled' key is already there.
		if ( ! empty( $this->atum_query_data['where'] ) && ! empty( wp_list_filter( $this->atum_query_data['where'], [ 'key' => 'atum_controlled' ] ) ) ) {
			return;
		}

		if ( $this->show_controlled ) {

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_controlled',
				'value' => 1,
				'type'  => 'NUMERIC',
			);

		}
		else {

			$this->atum_query_data['where'][] = array(
				'relation' => 'OR',
				array(
					'key'   => 'atum_controlled',
					'value' => 0,
					'type'  => 'NUMERIC',
				),
				array(
					'key'   => 'inheritable',
					'value' => 1,
					'type'  => 'NUMERIC',
				),
			);

		}

	}

	/**
	 * Filter the list table data to show compatible product types only
	 *
	 * @since 1.5.0
	 */
	protected function set_product_types_query_data() {

		/**
		 * If the site is not using the new tables, use the legacy way
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {

			$this->taxonomies[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => Globals::get_product_types(),
			);

		}
		else {

			$this->wc_query_data['where'][] = array(
				'key'     => 'type',
				'value'   => Globals::get_product_types(),
				'compare' => 'IN',
			);

		}

	}

	/**
	 * Customize the WP_Query to handle ATUM product data
	 *
	 * @since 1.5.0
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public function atum_product_data_query_clauses( $pieces ) {
		return Helpers::product_data_query_clauses( $this->atum_query_data, $pieces );
	}

	/**
	 * Customize the WP_Query to handle WC product data from the new tables
	 *
	 * @since 1.5.0
	 *
	 * @param array $pieces
	 *
	 * @return array
	 */
	public function wc_product_data_query_clauses( $pieces ) {
		return Helpers::product_data_query_clauses( $this->wc_query_data, $pieces, 'wc_products' );
	}

	/**
	 * Add the supplier's variable products to the filtered query
	 *
	 * @since 1.4.1.1
	 *
	 * @param array  $products
	 * @param string $return_type Optional. The return type: 'ids' or 'posts'.
	 *
	 * @return array
	 */
	public function add_supplier_variables_to_query( $products, $return_type = 'ids' ) {

		foreach ( $this->supplier_variation_products as $index => $variation_id ) {

			$variation_product = Helpers::get_atum_product( $variation_id );

			if ( ! $variation_product instanceof \WC_Product_Variation ) {
				unset( $this->supplier_variation_products[ $index ] );
				continue;
			}

			$is_controlled = Helpers::is_atum_controlling_stock( $variation_product );

			if ( ( $this->show_controlled && ! $is_controlled ) || ( ! $this->show_controlled && $is_controlled ) ) {
				unset( $this->supplier_variation_products[ $index ] );
				continue;
			}

			$variable_id = $variation_product->get_parent_id();
			$product_ids = 'ids' === $return_type ? $products : wp_list_pluck( $products, 'ID' );

			if ( ! is_array( $products ) || ! in_array( $variable_id, $product_ids ) ) {
				$this->container_products['all_variable'][] = $this->container_products['variable'][] = $variable_id;

				$products[] = 'posts' === $return_type ? get_post( $variable_id ) : $variable_id;
			}

		}

		return $products;

	}

	/**
	 * Add the supplier's variation products to the filtered query
	 *
	 * @since 1.4.1.1
	 *
	 * @param array $variations
	 * @param array $products
	 *
	 * @return array
	 */
	public function add_supplier_variations_to_query( $variations, $products ) {

		return array_merge( $variations, $this->supplier_variation_products );
	}

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.0.2
	 *
	 * @param array $args WP_Query arguments.
	 */
	protected function set_views_data( $args ) {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			$this->set_views_data_legacy( $args );
			return;
		}

		global $wpdb;

		if ( $this->show_unmanaged_counters ) {

			$this->id_views = array_merge( $this->id_views, array(
				'managed'        => [],
				'unm_in_stock'   => [],
				'unm_out_stock'  => [],
				'unm_back_order' => [],
				'all_in_stock'   => [],
				'all_out_stock'  => [],
				'all_back_order' => [],
			) );

			$this->count_views = array_merge( $this->count_views, array(
				'count_managed'        => 0,
				'count_unm_in_stock'   => 0,
				'count_unm_out_stock'  => 0,
				'count_unm_back_order' => 0,
				'count_all_in_stock'   => 0,
				'count_all_out_stock'  => 0,
				'count_all_back_order' => 0,
			) );

		}

		// Get all the IDs in the two queries with no pagination.
		$args['fields']         = 'ids';
		$args['posts_per_page'] = - 1;
		unset( $args['paged'] );

		// TODO: PERHAPS THE TRANSIENT CAN BE USED MORE GENERICALLY TO AVOID REPETITIVE WORK.
		$all_transient = AtumCache::get_transient_key( 'list_table_all', array_merge( $args, $this->wc_query_data, $this->atum_query_data ) );
		$products      = AtumCache::get_transient( $all_transient );

		if ( ! $products ) {

			global $wp_query;

			// Pass through the ATUM query data filter.
			do_action( 'atum/list_table/set_views_data/before_query_data' );
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$wp_query = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/all_products_args', $args ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			do_action( 'atum/list_table/set_views_data/after_query_data' );

			$products = $wp_query->posts;

			// Save it as a transient to improve the performance.
			AtumCache::set_transient( $all_transient, $products );

		}

		// Let others play here.
		$products = (array) apply_filters( 'atum/list_table/views_data_products', $products );

		$this->count_views['count_all'] = count( $products );

		if ( $this->is_filtering && empty( $products ) ) {
			return;
		}

		// If it's a search or a product filtering, include only the filtered items to search for children.
		$post_in = $this->is_filtering ? $products : array();

		// Loop all the registered product types.
		if ( ! empty( $this->wc_query_data['where'] ) ) {

			foreach ( $this->wc_query_data['where'] as $wc_query_arg ) {

				if ( isset( $wc_query_arg['key'] ) && 'type' === $wc_query_arg['key'] ) {

					$types = (array) $wc_query_arg['value'];

					if ( in_array( 'variable', $types, TRUE ) ) {

						$variations = apply_filters( 'atum/list_table/views_data_variations', $this->get_children( 'variable', $post_in, 'product_variation' ), $post_in );

						// Remove the variable containers from the array and add the variations.
						$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable'] ), $variations ) );

					}

					if ( in_array( 'grouped', $types, TRUE ) ) {

						$group_items = apply_filters( 'atum/list_table/views_data_grouped', $this->get_children( 'grouped', $post_in ), $post_in );

						// Remove the grouped containers from the array and add the group items.
						$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_grouped'] ), $group_items ) );

					}

					// WC Subscriptions compatibility.
					if ( class_exists( '\WC_Subscriptions' ) && in_array( 'variable-subscription', $types, TRUE ) ) {

						$sc_variations = apply_filters( 'atum/list_table/views_data_sc_variations', $this->get_children( 'variable-subscription', $post_in, 'product_variation' ), $post_in );

						// Remove the variable subscription containers from the array and add the subscription variations.
						$products = array_unique( array_merge( array_diff( $products, $this->container_products['all_variable_subscription'] ), $sc_variations ) );

					}

					// Re-count the resulting products.
					$this->count_views['count_all'] = count( $products );

					// The grouped items must count once per group they belongs to and once individually.
					if ( ! empty( $group_items ) ) {
						$this->count_views['count_all'] += count( $group_items );
					}

					do_action( 'atum/list_table/after_children_count', $types, $this );

					break;

				}

			}

		}

		// For the Uncontrolled items, we don't need to calculate stock totals.
		if ( ! $this->show_controlled ) {
			return;
		}

		if ( $products ) {

			$post_types = ( ! empty( $variations ) || ! empty( $sc_variations ) ) ? [ $this->post_type, 'product_variation' ] : [ $this->post_type ];

			/*
			 * Unmanaged products
			 */
			if ( $this->show_unmanaged_counters ) {

				$products_unmanaged        = array();
				$products_unmanaged_status = Helpers::get_unmanaged_products( $post_types, TRUE );

				if ( ! empty( $products_unmanaged_status ) ) {

					// Filter the unmanaged (also removes uncontrolled).
					$products_unmanaged_status = array_filter( $products_unmanaged_status, function ( $row ) use ( $products ) {
						return in_array( $row[0], $products );
					} );

					$this->id_views['unm_in_stock'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'instock' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_in_stock'] = count( $this->id_views['unm_in_stock'] );

					$this->id_views['unm_out_stock'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'outofstock' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_out_stock'] = count( $this->id_views['unm_out_stock'] );

					$this->id_views['unm_back_order'] = array_column( array_filter( $products_unmanaged_status, function ( $row ) {
						return 'onbackorder' === $row[1];
					} ), 0 );

					$this->count_views['count_unm_back_order'] = count( $this->id_views['unm_back_order'] );

					$products_unmanaged = array_column( $products_unmanaged_status, 0 );

					$this->id_views['managed'] = array_diff( $products, $products_unmanaged );
					// Need to substract count unmanaged because group items are not included twice in managed id_views.
					$this->count_views['count_managed'] = $this->count_views['count_all'] - count( $products_unmanaged );

				}

			}
			else {
				$products_unmanaged = array_column( Helpers::get_unmanaged_products( $post_types ), 0 );
			}

			// Remove the unmanaged from the products list.
			if ( ! empty( $products_unmanaged ) ) {

				// Filter the unmanaged (also removes uncontrolled).
				$products_unmanaged = array_intersect( $products, $products_unmanaged );

				$this->id_views['unmanaged']          = $products_unmanaged;
				$this->count_views['count_unmanaged'] = count( $products_unmanaged );

				if ( ! empty( $products_unmanaged ) ) {
					$products = ! empty( $this->count_views['count_managed'] ) ? $this->id_views['managed'] : array_diff( $products, $products_unmanaged );
				}

			}

			$products = (array) $products;

			/*
			 * Products args.
			 */
			$products_args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'post__in'       => $products,
			);

			$temp_atum_query_data = $this->atum_query_data;

			/*
			 * Products in stock.
			 */
			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_stock_status',
				'value' => 'instock',
				'type'  => 'CHAR',
			);

			$in_stock_transient = AtumCache::get_transient_key( 'list_table_in_stock', array_merge( $products_args, $this->wc_query_data, $this->atum_query_data ) );
			$products_in_stock  = AtumCache::get_transient( $in_stock_transient );

			if ( empty( $products_in_stock ) ) {

				// Pass through the WC query data filter (new tables).
				add_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
				$products_in_stock = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/in_stock_products_args', $products_args ) );
				remove_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );

				AtumCache::set_transient( $in_stock_transient, $products_in_stock );

			}

			$products_in_stock     = (array) $products_in_stock->posts;
			$this->atum_query_data = $temp_atum_query_data; // Restore the original value.

			$this->id_views['in_stock']          = $products_in_stock;
			$this->count_views['count_in_stock'] = count( $products_in_stock );

			$products_not_stock = array_diff( $products, $products_in_stock, $products_unmanaged );

			/**
			 * Products on Back Order.
			 */
			$products_args['post__in'] = $products_not_stock;

			if ( ! empty( $this->atum_query_data['where'] ) ) {
				$this->atum_query_data['where']['relation'] = 'AND';
			}

			$this->atum_query_data['where'][] = array(
				'key'   => 'atum_stock_status',
				'value' => 'onbackorder',
				'type'  => 'CHAR',
			);

			$back_order_transient = AtumCache::get_transient_key( 'list_table_back_order', array_merge( $products_args, $this->wc_query_data, $this->atum_query_data ) );
			$products_back_order  = AtumCache::get_transient( $back_order_transient );

			if ( empty( $products_back_order ) && ! empty( $products_not_stock ) ) {

				// Pass through the WC query data filter (new tables).
				add_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );
				$products_back_order = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/back_order_products_args', $products_args ) );
				remove_filter( 'posts_clauses', array( $this, 'wc_product_data_query_clauses' ) );

				AtumCache::set_transient( $back_order_transient, $products_back_order );

			}

			$products_back_order   = (array) $products_back_order->posts;
			$this->atum_query_data = $temp_atum_query_data;

			$this->id_views['back_order']          = $products_back_order;
			$this->count_views['count_back_order'] = count( $products_back_order );

			// As the Group items might be displayed multiple times, we should count them multiple times too.
			if ( ! empty( $group_items ) && ( empty( $_REQUEST['product_type'] ) || 'grouped' !== $_REQUEST['product_type'] ) ) {
				$this->count_views['count_in_stock']   += count( array_intersect( $group_items, $products_in_stock ) );
				$this->count_views['count_back_order'] += count( array_intersect( $group_items, $products_back_order ) );

			}

			/**
			 * Products with low stock
			 */
			if ( ! empty( $products_in_stock ) ) {

				$low_stock_transient = AtumCache::get_transient_key( 'list_table_low_stock', array_merge( $args, $this->wc_query_data, $this->atum_query_data ) );
				$products_low_stock  = AtumCache::get_transient( $low_stock_transient );

				if ( empty( $products_low_stock ) ) {

					$atum_product_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;
					$str_sql                 = apply_filters( 'atum/list_table/set_views_data/low_stock_products', "
						SELECT product_id FROM $atum_product_data_table WHERE low_stock = 1
					" );

					$products_low_stock = $wpdb->get_col( $str_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					AtumCache::set_transient( $low_stock_transient, $products_low_stock );

				}

				$this->id_views['low_stock']          = (array) $products_low_stock;
				$this->count_views['count_low_stock'] = count( $products_low_stock );

			}

			/**
			 * Products out of stock
			 */
			$products_out_stock = array_diff( $products_not_stock, $products_back_order );

			$this->id_views['out_stock']          = $products_out_stock;
			$this->count_views['count_out_stock'] = max( 0, $this->count_views['count_all'] - $this->count_views['count_in_stock'] - $this->count_views['count_back_order'] - $this->count_views['count_unmanaged'] );

			if ( $this->show_unmanaged_counters ) {
				/**
				 * Calculate totals
				 */
				$this->id_views['all_in_stock']          = array_merge( $this->id_views['in_stock'], $this->id_views['unm_in_stock'] );
				$this->count_views['count_all_in_stock'] = $this->count_views['count_in_stock'] + $this->count_views['count_unm_in_stock'];

				$this->id_views['all_out_stock']          = array_merge( $this->id_views['out_stock'], $this->id_views['unm_out_stock'] );
				$this->count_views['count_all_out_stock'] = $this->count_views['count_out_stock'] + $this->count_views['count_unm_out_stock'];

				$this->id_views['all_back_order']          = array_merge( $this->id_views['back_order'], $this->id_views['unm_back_order'] );
				$this->count_views['count_all_back_order'] = $this->count_views['count_back_order'] + $this->count_views['count_unm_back_order'];

			}

		}

	}

	/**
	 * Print column headers, accounting for hidden and sortable columns
	 *
	 * @since 1.4.5
	 *
	 * @param bool $with_id Whether to set the id attribute or not.
	 */
	public function print_column_headers( $with_id = TRUE ) {

		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$group_members = wp_list_pluck( $this->group_members, 'members' );

		$current_url     = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url     = remove_query_arg( 'paged', $current_url );
		$current_orderby = isset( $_GET['orderby'] ) ? esc_attr( $_GET['orderby'] ) : '';
		$current_order   = ( ! isset( $_GET['order'] ) || 'desc' === $_GET['order'] ) ? 'desc' : 'asc';

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;

			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . esc_html__( 'Select All', ATUM_TEXT_DOMAIN ) . '</label><input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {

			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			// Check if it's a numeric column.
			if (
				! empty( $this->searchable_columns['numeric'] ) && is_array( $this->searchable_columns['numeric'] ) &&
				in_array( $column_key, $this->searchable_columns['numeric'], TRUE )
			) {
				$class[] = 'numeric';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			}
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			// Add the group key as class.
			foreach ( $group_members as $group_key => $members ) {
				if ( in_array( $column_key, $members ) ) {
					$class[] = $group_key;
					break;
				}
			}

			if ( isset( $sortable[ $column_key ] ) ) {

				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				if ( $current_orderby === $orderby ) {
					$order   = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				}
				else {
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$sorting_params = compact( 'orderby', 'order' );
				$sorting_url    = esc_url( add_query_arg( $sorting_params, $current_url ) );
				$hash_params    = http_build_query( array_merge( $this->query_filters, $sorting_params ) );

				$column_display_name = '<a href="' . $sorting_url . '" rel="address:/?' . $hash_params . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';

			}

			$tag   = 'cb' === $column_key ? 'td' : 'th';
			$scope = 'th' === $tag ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<$tag $scope $id $class>$column_display_name</$tag>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

	}

	/**
	 * Prints the columns that groups the distinct header columns
	 *
	 * @since 0.0.1
	 */
	public function print_group_columns() {

		if ( ! empty( $this->group_columns ) ) {

			echo '<tr class="column-groups">';

			foreach ( $this->group_columns as $group_column ) {

				$data = $group_column['collapsed'] ? ' data-collapsed="1"' : '';

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th class="' . esc_attr( $group_column['name'] ) . '" colspan="' . esc_attr( $group_column['colspan'] ) . '"' . $data . '><span>' . $group_column['title'] . '</span>';

				if ( $group_column['toggler'] ) {
					/* translators: the column group title */
					$data_tip = ! self::$is_report ? ' data-tip="' . esc_attr( sprintf( __( "Show/Hide the '%s' columns", ATUM_TEXT_DOMAIN ), $group_column['title'] ) ) . '"' : '';

					echo '<span class="group-toggler tips"' . $data_tip . '></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				echo '</th>';

			}

			echo '</tr>';

		}
	}

	/**
	 * Prints the totals columns on totals row at table footer
	 *
	 * @since 1.4.2
	 */
	public function print_totals_columns() {

		// Does not show the totals row if there are no results.
		if ( empty( $this->items ) ) {
			return;
		}

		/* @noinspection PhpUnusedLocalVariableInspection */
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		$group_members = wp_list_pluck( $this->group_members, 'members' );
		$column_keys   = array_keys( $columns );
		$first_column  = current( $column_keys );
		$second_column = next( $column_keys );

		// Let to adjust the totals externally if needed.
		$this->totalizers = apply_filters( 'atum/list_table/totalizers', $this->totalizers );

		foreach ( $columns as $column_key => $column_display ) {

			$class   = array( 'manage-column', "column-$column_key" );
			$colspan = '';

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( $first_column === $column_key ) {

				$class[] = 'totals-heading';

				// Set a colspan of 2 if the checkbox column is present and the second column isn't hidden.
				if ( 'cb' === $first_column && ! in_array( $second_column, $hidden ) ) {
					$colspan = 'colspan="2"';
				}

				$column_display = '<span>' . __( 'Totals', ATUM_TEXT_DOMAIN ) . '</span>';

			}
			elseif ( 'cb' === $first_column && $second_column === $column_key ) {
				continue; // Get rid of the second column as the first one will have a colspan.
			}
			elseif ( in_array( $column_key, array_keys( $this->totalizers ) ) ) {
				$total          = $this->totalizers[ $column_key ];
				$total_class    = $total < 0 ? ' class="danger"' : '';
				$column_display = "<span{$total_class}>" . $total . '</span>';
			}
			else {
				$column_display = self::EMPTY_COL;
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			// Add the group key as class.
			foreach ( $group_members as $group_key => $members ) {
				if ( in_array( $column_key, $members ) ) {
					$class[] = $group_key;
					break;
				}
			}

			$tag   = 'cb' === $column_key ? 'td' : 'th';
			$scope = 'th' === $tag ? 'scope="col"' : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<$tag $scope $class $colspan>$column_display</th>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

	}

	/**
	 * Adds the data needed for ajax filtering, sorting and pagination and displays the table
	 *
	 * @since 0.0.1
	 */
	public function display() {

		do_action( 'atum/list_table/before_display', $this );

		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );

		?>
		<div class="atum-table-wrapper">
			<table class="wp-list-table atum-list-table <?php echo implode( ' ', $this->get_table_classes() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
				data-currency-pos="<?php echo esc_attr( get_option( 'woocommerce_currency_pos', 'left' ) ) ?>">

				<thead>
				<?php $this->print_group_columns(); ?>

				<tr class="item-heads">
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"<?php if ( $singular ) echo esc_attr( " data-wp-lists='list:$singular'" ); ?>>
				<?php $this->display_rows_or_placeholder(); ?>
				</tbody>

				<tfoot>

				<?php if ( $this->show_totals ) : ?>
					<tr class="totals">
						<?php $this->print_totals_columns(); ?>
					</tr>
				<?php endif ?>

				<tr class="item-heads">
					<?php $this->print_column_headers( FALSE ); ?>
				</tr>

				</tfoot>

			</table>

			<input type="hidden" name="atum-column-edits" id="atum-column-edits" value="">
		</div>
		<?php

		$this->display_tablenav( 'bottom' );
		global $plugin_page;

		// Prepare JS vars.
		$vars = array(
			'ajaxFilter'                     => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
			'apply'                          => __( 'Apply', ATUM_TEXT_DOMAIN ),
			'applyAction'                    => __( 'Apply Action', ATUM_TEXT_DOMAIN ),
			'applyBulkAction'                => __( 'Apply Bulk Action', ATUM_TEXT_DOMAIN ),
			'currencyFormat'                 => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
			'currencyFormatDecimalSeparator' => wc_get_price_decimal_separator(),
			'currencyFormatNumDecimals'      => wc_get_price_decimals(),
			'dateSelectorFilters'            => [ 'best_seller', 'worst_seller' ],
			'done'                           => __( 'Done!', ATUM_TEXT_DOMAIN ),
			'emptyCol'                       => self::EMPTY_COL,
			'editLocations'                  => __( 'Edit Locations', ATUM_TEXT_DOMAIN ),
			'editLocationsInfo'              => __( 'Click on the location icons to switch the states. Locations marked with blue icons will be set and with gray icons will be unset.', ATUM_TEXT_DOMAIN ),
			'editProductLocations'           => __( 'Edit Product Locations', ATUM_TEXT_DOMAIN ),
			'from'                           => __( 'From', ATUM_TEXT_DOMAIN ),
			'hideFilters'                    => __( 'Hide', ATUM_TEXT_DOMAIN ),
			'listUrl'                        => esc_url( add_query_arg( 'page', $plugin_page, admin_url() ) ),
			'locationsSaved'                 => __( 'Locations saved successfully', ATUM_TEXT_DOMAIN ),
			'noItemsSelected'                => __( 'No Items Selected', ATUM_TEXT_DOMAIN ),
			'noActions'                      => __( 'No actions', ATUM_TEXT_DOMAIN ),
			'nonce'                          => wp_create_nonce( 'atum-list-table-nonce' ),
			'ok'                             => __( 'OK', ATUM_TEXT_DOMAIN ),
			'order'                          => isset( $this->_pagination_args['order'] ) ? $this->_pagination_args['order'] : '',
			'orderby'                        => isset( $this->_pagination_args['orderby'] ) ? $this->_pagination_args['orderby'] : '',
			'perPage'                        => $this->per_page,
			'productLocations'               => __( 'Product Locations', ATUM_TEXT_DOMAIN ),
			'rowActions'                     => self::$row_actions,
			'saveButton'                     => __( 'Save', ATUM_TEXT_DOMAIN ),
			'searchableColumns'              => $this->searchable_columns,
			'selectDateRange'                => __( 'Select the date range to filter the products.', ATUM_TEXT_DOMAIN ),
			'selectItems'                    => __( 'Please, check the boxes for all the items to which you want to apply this bulk action', ATUM_TEXT_DOMAIN ),
			'setButton'                      => __( 'Set', ATUM_TEXT_DOMAIN ),
			'setTimeWindow'                  => __( 'Set Time Window', ATUM_TEXT_DOMAIN ),
			'setValue'                       => __( 'Set the %% value', ATUM_TEXT_DOMAIN ),
			'showCb'                         => $this->show_cb,
			'showFilters'                    => __( 'Show', ATUM_TEXT_DOMAIN ),
			'stickyColumns'                  => $this->sticky_columns,
			'stickyColumnsNonce'             => wp_create_nonce( 'atum-sticky-columns-button-nonce' ),
			'to'                             => __( 'To', ATUM_TEXT_DOMAIN ),
		);

		$vars = array_merge( $vars, Globals::get_date_time_picker_js_vars() );

		if ( $this->first_edit_key ) {
			$vars['firstEditKey']      = $this->first_edit_key;
			$vars['important']         = __( 'Important!', ATUM_TEXT_DOMAIN );
			$vars['preventLossNotice'] = __( "To prevent any loss of data, please, hit the blue 'Save Data' button at the top left after completing edits.", ATUM_TEXT_DOMAIN );
		}

		$vars = apply_filters( 'atum/list_table/js_vars', $vars );
		wp_localize_script( 'atum-list', 'atumListVars', $vars );

		do_action( 'atum/list_table/after_display', $this );

	}

	/**
	 * Generate the table navigation above or below the table
	 * Just the parent function but removing the nonce fields that are not required here
	 *
	 * @since 0.0.1
	 *
	 * @param string $which 'top' or 'bottom' table nav.
	 */
	protected function display_tablenav( $which ) {

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?> extend-list-table">

			<?php if ( ! empty( $this->get_bulk_actions() ) ) : ?>
				<div id="scroll-filters_container" class="filters-container-box <?php echo 'top' === $which && ( empty( $this->_pagination_args['total_pages'] ) || $this->_pagination_args['total_pages'] <= 1 ) ? 'no-pagination' : ''; ?><?php echo 'no' !== Helpers::get_option( 'enable_ajax_filter', 'yes' ) ? ' no-submit' : ''; ?>">
					<div id="filters_container" class="<?php echo 'top' === $which ? 'nav-with-scroll-effect dragscroll' : ''; ?>">

						<div class="alignleft actions bulkactions">
							<?php $this->bulk_actions( $which ); ?>
						</div>

						<?php $this->extra_tablenav( $which ); ?>

						<?php if ( 'top' === $which ) : ?>
							<div class="overflow-opacity-effect-right"></div>
							<div class="overflow-opacity-effect-left"></div>
						<?php endif; ?>

					</div>
				</div>
			<?php endif;

			// Firefox fix to not preserve the pagination input value when reloading the page.
			ob_start(); ?>

			<div class="tablenav-pages-container<?php echo empty( $this->_pagination_args['total_pages'] ) || $this->_pagination_args['total_pages'] <= 1 ? ' one-page' : ''; ?><?php echo 'no' !== Helpers::get_option( 'enable_ajax_filter', 'yes' ) ? ' no-submit' : ''; ?>">

				<?php if ( 'no' === Helpers::get_option( 'enable_ajax_filter', 'yes' ) ) : ?>
					<input type="submit" name="filter_action" class="btn btn-warning search-category hidden-sm" value="<?php esc_attr_e( 'Filter', ATUM_TEXT_DOMAIN ) ?>">
				<?php endif; ?>

				<?php
				$this->pagination( $which );
				echo str_replace( '<input ', '<input autocomplete="off" ', ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

			</div>
		</div>
		<?php

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 0.0.1
	 */
	public function no_items() {

		$post_type_obj = get_post_type_object( $this->post_type );
		echo $post_type_obj->labels->not_found; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $_REQUEST['s'] ) ) {
			/* translators: the search query */
			printf( __( " with query '%s'", ATUM_TEXT_DOMAIN ), stripslashes( esc_attr( $_REQUEST['s'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

	}

	/**
	 * Display the pagination.
	 *
	 * @since 1.4.3
	 *
	 * @param string $which
	 */
	protected function pagination( $which ) {

		if ( empty( $this->_pagination_args ) ) {
			$output = '<span class="displaying-num">' . esc_html__( '0 items', ATUM_TEXT_DOMAIN ) . '</span>';
			echo "<div class='tablenav-pages extend-list-table'>$output</div>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		/* translators: the number of items */
		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items, ATUM_TEXT_DOMAIN ), number_format_i18n( $total_items ) ) . '</span>';

		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url        = remove_query_arg( $removable_query_args, $current_url );
		$page_links         = array();
		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = FALSE;

		if ( 1 === $current ) {
			$disable_first = TRUE;
			$disable_prev  = TRUE;
		}
		elseif ( 2 === $current ) {
			$disable_first = TRUE;
		}

		if ( $current === $total_pages ) {
			$disable_last = TRUE;
			$disable_next = TRUE;
		}
		elseif ( $current === $total_pages - 1 ) {
			$disable_last = TRUE;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
		}
		else {

			$page_links[] = sprintf(
				"<a class='first-page' href='%1\$s' rel='address:/?%2\$s'><span class='screen-reader-text'>%3\$s</span><span aria-hidden='true'>%4\$s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				http_build_query( array_merge( $this->query_filters, [ 'paged' => 1 ] ) ),
				__( 'First page', ATUM_TEXT_DOMAIN ),
				'&laquo;'
			);

		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
		}
		else {

			$prev_page    = max( 1, $current - 1 );
			$page_links[] = sprintf(
				"<a class='prev-page' href='%1\$s' rel='address:/?%2\$s'><span class='screen-reader-text'>%3\$s</span><span aria-hidden='true'>%4\$s</span></a>",
				esc_url( add_query_arg( 'paged', $prev_page, $current_url ) ),
				http_build_query( array_merge( $this->query_filters, [ 'paged' => $prev_page ] ) ),
				__( 'Previous page', ATUM_TEXT_DOMAIN ),
				'&lsaquo;'
			);

		}

		if ( 'bottom' === $which ) {
			$current_page_style = 'tablenav-current-page';
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page', ATUM_TEXT_DOMAIN ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		}
		else {
			$current_page_style = '';
			/* @noinspection PhpFormatFunctionParametersMismatchInspection */
			$html_current_page = sprintf( "%1\$s<input class='current-page' data-current='%2\$s' id='current-page-selector' type='text' name='paged' value='%2\$s' size='%3\$d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page', ATUM_TEXT_DOMAIN ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		/* translators: first one is the current page number and sesond is the total number of pages */
		$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging', ATUM_TEXT_DOMAIN ), '<span class="' . esc_attr( $current_page_style ) . '">' . $html_current_page . '</span>', $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
		}
		else {

			$next_page    = min( $total_pages, $current + 1 );
			$page_links[] = sprintf(
				"<a class='next-page' href='%1\$s' rel='address:/?%2\$s'><span class='screen-reader-text'>%3\$s</span><span aria-hidden='true'>%4\$s</span></a>",
				esc_url( add_query_arg( 'paged', $next_page, $current_url ) ),
				http_build_query( array_merge( $this->query_filters, [ 'paged' => $next_page ] ) ),
				__( 'Next page', ATUM_TEXT_DOMAIN ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
		}
		else {

			$page_links[] = sprintf(
				"<a class='last-page' href='%1\$s' rel='address:/?%2\$s'><span class='screen-reader-text'>%3\$s</span><span aria-hidden='true'>%4\$s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				http_build_query( array_merge( $this->query_filters, [ 'paged' => $total_pages ] ) ),
				__( 'Last page', ATUM_TEXT_DOMAIN ),
				'&raquo;'
			);

		}

		$pagination_links_class = 'pagination-links';

		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		}
		else {
			$page_class = ' no-pages';
		}

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since  0.0.2
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {

		return array( 'widefat', $this->_args['plural'] );
	}

	/**
	 * A wrapper to get the right product ID (or variation ID)
	 *
	 * @since 1.2.1
	 *
	 * @return int
	 */
	protected function get_current_product_id() {

		if ( 'variation' === $this->product->get_type() ) {
			/**
			 * Deprecated notice
			 *
			 * @deprecated
			 * The get_variation_id() method was deprecated in WC 3.0.0
			 * In newer versions the get_id() method always be the variation_id if it's a variation
			 */
			/* @noinspection PhpDeprecationInspection */
			return version_compare( WC()->version, '3.0.0', '<' ) ? $this->product->get_variation_id() : $this->product->get_id();
		}

		return $this->product->get_id();

	}

	/**
	 * Gets the array needed to print html group columns in the table
	 *
	 * @since 0.0.1
	 *
	 * @param array $hidden Hidden columns.
	 *
	 * @return array
	 */
	public function calc_groups( $hidden ) {

		$response = array();

		foreach ( $this->group_members as $name => $group ) {

			$counter = 0;

			foreach ( $group['members'] as $member ) {

				if ( ! in_array( $member, $hidden ) ) {
					$counter ++;
				}

			}

			// Add the group only if there are columns within.
			if ( $counter ) {

				$response[] = array(
					'name'      => $name,
					'title'     => $group['title'],
					'colspan'   => $counter,
					'toggler'   => ! empty( $group['toggler'] ) && $group['toggler'],
					'collapsed' => ! empty( $group['collapsed'] ) && $group['collapsed'],
				);

			}
		}

		return $response;

	}

	/**
	 * Return the group of columns that a specific column belongs to or false
	 *
	 * @sinece 0.0.5
	 *
	 * @param string $column  The column to search to.
	 *
	 * @return bool|string
	 */
	public function search_group_columns( $column ) {

		foreach ( $this->group_members as $name => $group_member ) {

			if ( in_array( $column, $group_member['members'] ) ) {
				return $name;
			}

		}

		return FALSE;
	}

	/**
	 * Search products by: A (post_title, post_excerpt, post_content ), B (posts.ID), C (posts.title), D (other meta fields wich can be numeric or not)
	 *
	 * @since 1.4.8
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function product_search( $where ) {

		global $pagenow, $wpdb;

		// Changed the WooCommerce's "product_search" filter to allow Ajax requests.
		/* @see \WC_Admin_Post_Types::product_search */

		if (
			! is_admin() ||
			! in_array( $pagenow, array( 'edit.php', 'admin-ajax.php' ) ) ||
			! isset( $_REQUEST['s'], $_REQUEST['action'] ) || FALSE === strpos( $_REQUEST['action'], ATUM_PREFIX )
		) {
			return $where;
		}

		// Prevent keyUp problems (scenario: do a search with s and search_column, clean s, change search_column... and you will get nothing (s still set on url)).
		if ( 0 === strlen( $_REQUEST['s'] ) ) {
			return 'AND ( 1 = 1 )';
		}

		// If we don't get any result looking for a field, we must force an empty result before
		// WP tries to query {$wpdb->posts}.ID IN ( 'empty value' ), which raises an error.
		$where_without_results = "AND ( {$wpdb->posts}.ID = -1 )";

		$search_column = esc_attr( stripslashes( $_REQUEST['search_column'] ) );
		$search_term   = sanitize_text_field( urldecode( stripslashes( $_REQUEST['s'] ) ) );

		$cache_key = AtumCache::get_cache_key( 'product_search', [ $search_column, $search_term ] );
		$where     = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $where;
		}

		$search_terms = $this->parse_search( $search_term );

		if ( empty( $search_terms ) ) {
			AtumCache::set_cache( $cache_key, $where_without_results );
			return $where_without_results;
		}

		//
		// Regular search in post_title, post_excerpt and post_content (with no column selected).
		// --------------------------------------------------------------------------------------!
		if ( empty( $search_column ) ) {

			$search_query = $this->build_search_query( $search_terms );

			$query = "
				SELECT ID, post_type, post_parent FROM $wpdb->posts
		        WHERE post_type IN ('product', 'product_variation') 
		        AND $search_query
	         ";

			$search_terms_ids = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $search_terms_ids ) ) {
				AtumCache::set_cache( $cache_key, $where_without_results );
				return apply_filters( 'atum/list_table/product_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
			}

			// Remove duplicate values from a multi-dimensional array.
			$search_terms_ids = array_map( 'unserialize', array_unique( array_map( 'serialize', $search_terms_ids ) ) );

			$search_terms_ids_arr = array();

			foreach ( $search_terms_ids as $product ) {

				if ( 'product' === $product['post_type'] ) {
					array_push( $search_terms_ids_arr, $product['ID'] );
				}
				// Add parent and current.
				else {
					array_push( $search_terms_ids_arr, $product['ID'] );
					array_push( $search_terms_ids_arr, $product['post_parent'] );
				}
			}

			$search_terms_ids_arr = array_unique( $search_terms_ids_arr );
			$search_terms_ids_str = implode( ',', $search_terms_ids_arr );

			$where = "AND ( {$wpdb->posts}.ID IN ($search_terms_ids_str) )";

		}
		else {

			if ( Helpers::in_multi_array( $search_column, $this->searchable_columns ) ) {

				$column_name = ltrim( $search_column, '_' );

				//
				// Search by ID.
				// -------------!
				if ( 'ID' === $search_column ) {

					$search_query = $this->build_search_query( $search_terms, $search_column, 'int' );

					// Get all (parent and variations, and build where).
					$query = "
						SELECT ID, post_type, post_parent FROM $wpdb->posts
					    WHERE $search_query
					    AND post_type IN ('product', 'product_variation')
				    ";

					$search_term_id = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( empty( $search_term_id ) ) {
						AtumCache::set_cache( $cache_key, $where_without_results );
						return apply_filters( 'atum/list_table/product_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
					}

					$search_terms_ids_str = '';

					if ( 'product' === $search_term_id->post_type ) {

						$search_terms_ids_str .= $search_term_id->ID . ',';

						// If has children, add them.
						$product = wc_get_product( $search_term_id->ID );

						// Get an array of the children IDs (if any).
						$children = $product->get_children();

						if ( ! empty( $children ) ) {
							foreach ( $children as $child ) {
								$search_terms_ids_str .= $child . ',';
							}
						}

					}
					// Add parent and current.
					else {
						$search_terms_ids_str .= $search_term_id->post_parent . ',';
						$search_terms_ids_str .= $search_term_id->ID . ',';
					}

					$search_terms_ids_str = rtrim( $search_terms_ids_str, ',' );
					$where                = "AND ( $wpdb->posts.ID IN ($search_terms_ids_str) )";

				}
				//
				// Search by Supplier name.
				// ------------------------!
				elseif ( Suppliers::SUPPLIER_META_KEY === $search_column ) {

					$search_query = $this->build_search_query( $search_terms, 'post_title' );

					// Get suppliers.
					// phpcs:disable WordPress.DB.PreparedSQL
					$query = $wpdb->prepare( "
						SELECT ID FROM $wpdb->posts
					    WHERE post_type = %s AND $search_query",
						Suppliers::POST_TYPE
					);
					// phpcs:enable

					$search_supplier_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( empty( $search_supplier_ids ) ) {
						AtumCache::set_cache( $cache_key, $where_without_results );
						return apply_filters( 'atum/list_table/product_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
					}

					$supplier_products = array();

					// Avoid endless loops.
					remove_filter( 'posts_search', array( $this, 'product_search' ) );

					foreach ( $search_supplier_ids as $supplier_id ) {
						$supplier_products = array_merge( $supplier_products, Suppliers::get_supplier_products( $supplier_id ) );
					}

					add_filter( 'posts_search', array( $this, 'product_search' ), 10, 2 );

					$supplier_products = array_unique( $supplier_products );

					if ( empty( $supplier_products ) ) {
						AtumCache::set_cache( $cache_key, $where_without_results );
						return apply_filters( 'atum/list_table/product_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
					}

					$where = "AND $wpdb->posts.ID IN (" . implode( ',', $supplier_products ) . ')';

				}
				//
				// Search by title and other calc or meta fields.
				// ----------------------------------------------!
				else {

					$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

					// Title field is not in meta.
					if ( 'title' === $search_column ) {

						$search_query = $this->build_search_query( $search_terms, 'post_title' );

						$query = "
							SELECT ID, post_type, post_parent FROM $wpdb->posts
					        WHERE $search_query 
					        AND post_type IN ('product', 'product_variation')	
				         ";

					}
					/**
					 *  Numeric fields.
					 */
					elseif ( in_array( $search_column, $this->searchable_columns['numeric'] ) ) {

						// Search by ATUM product data columns.
						if ( in_array( $search_column, $this->get_searchable_atum_columns() ) ) {

							$search_query = $this->build_search_query( $search_terms, $column_name, 'float', 'apd' );
							$meta_where   = apply_filters( 'atum/list_table/product_search/numeric_meta_where', $search_query, $search_column, $search_terms );

							$query = "
								SELECT DISTINCT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN $atum_data_table apd ON (p.ID = apd.product_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
						    ";

						}
						// Search using the new WC tables.
						elseif ( Helpers::is_using_new_wc_tables() ) {

							// The _stock meta key was renamed to stock_quantity in the new products table.
							if ( 'stock' === $column_name ) {
								$column_name = 'stock_quantity';
							}

							$search_query = $this->build_search_query( $search_terms, $column_name, 'float', 'wcd' );
							$meta_where   = apply_filters( 'atum/list_table/product_search/numeric_meta_where', $search_query, $search_column, $search_terms );

							$query = "
								SELECT DISTINCT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN {$wpdb->prefix}wc_products wcd ON (p.ID = wcd.product_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
						    ";

						}
						// Search using the old way (meta keys).
						/* @deprecated */
						else {

							$search_query = $this->build_search_query( $search_terms, $search_column, 'string', 'pm', TRUE );
							$meta_where   = apply_filters( 'atum/list_table/product_search/numeric_meta_where', $search_query, $search_column, $search_terms );

							$query = "
								SELECT DISTINCT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
						    ";

						}

					}
					/**
					 * String fields.
					 */
					else {

						// Search by supplier SKU (or any other possible ATUM string col).
						if ( in_array( $search_column, $this->get_searchable_atum_columns() ) ) {

							$search_query = $this->build_search_query( $search_terms, $column_name, 'string', 'apd' );
							$meta_where   = apply_filters( 'atum/list_table/product_search/string_meta_where', $search_query, $search_column, $search_terms );

							$query = "
								SELECT DISTINCT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN $atum_data_table apd ON (p.ID = apd.product_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
						    ";

						}
						// Search using the new WC tables.
						elseif ( Helpers::is_using_new_wc_tables() ) {

							// The _stock meta key was renamed to stock_quantity in the new products table.
							if ( 'stock' === $column_name ) {
								$column_name = 'stock_quantity';
							}

							$search_query = $this->build_search_query( $search_terms, $column_name, 'string', 'wcd' );
							$meta_where   = apply_filters( 'atum/list_table/product_search/numeric_meta_where', $search_query, $search_column, $search_terms );

							$query = "
								SELECT DISTINCT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN {$wpdb->prefix}wc_products wcd ON (p.ID = wcd.product_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
						    ";

						}
						// Search using the old way.
						/* @deprecated */
						else {

							$search_query = $this->build_search_query( $search_terms, $search_column, 'string', 'pm', TRUE );
							$meta_where   = apply_filters( 'atum/list_table/product_search/string_meta_where', $search_query, $search_column, $search_terms );

							$query = "SELECT p.ID, p.post_type, p.post_parent FROM $wpdb->posts p
							    LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id)
							    WHERE p.post_type IN ('product', 'product_variation')
							    AND $meta_where
					         ";

						}

					}

					$search_terms_ids = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

					if ( empty( $search_terms_ids ) ) {
						AtumCache::set_cache( $cache_key, $where_without_results );
						return apply_filters( 'atum/list_table/product_search/where', $where_without_results, $search_column, $search_term, $search_terms, $cache_key );
					}

					$search_terms_ids_str = '';

					foreach ( $search_terms_ids as $term_id ) {

						if ( 'product' === $term_id->post_type ) {

							$search_terms_ids_str .= "$term_id->ID,";
							$product               = wc_get_product( $term_id->ID );
							$children              = $product->get_children();

							if ( ! empty( $children ) ) {
								foreach ( $children as $child ) {
									$search_terms_ids_str .= $child . ',';
								}
							}

						}
						// Add parent and current.
						else {
							$search_terms_ids_str .= "'$term_id->ID',";
							$search_terms_ids_str .= "'$term_id->post_parent',";
						}

					}

					// Removes last comma.
					$search_terms_ids_str = rtrim( $search_terms_ids_str, ',' );

					$where = "AND ( $wpdb->posts.ID IN ($search_terms_ids_str) )";
				}

			}

		}

		// We've to overwrite the cache generated by ATUM to ensure that the right where clause is set.
		AtumCache::set_cache( $cache_key, $where );

		return apply_filters( 'atum/list_table/product_search/where', $where, $search_column, $search_term, $search_terms, $cache_key );

	}

	/**
	 * Parse the search term submitted and return an array of search terms
	 *
	 * @since 1.5.2
	 *
	 * @param string $search_term
	 *
	 * @return array
	 */
	protected function parse_search( $search_term ) {

		$search_terms = array();

		// There are no line breaks in input fields.
		$search_term = str_replace( array( "\r", "\n" ), '', $search_term );

		if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $search_term, $matches ) ) {

			//
			// Get stopwords.
			// --------------!
			/* translators: This doesn't need to be translated in ATUM, as it's getting the translation from WordPress core. */
			$words = explode( ',', _x( 'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www', 'Comma-separated list of search stopwords in your language' ) ); // phpcs:ignore WordPress.WP.I18n.MissingArgDomain

			$stopwords = array();
			foreach ( $words as $word ) {

				$word = trim( $word, "\r\n\t " );

				if ( $word ) {
					$stopwords[] = $word;
				}

			}

			/**
			 * Filters stopwords used when parsing search terms.
			 * Note that it uses the same filter name as in WP_Query class for compatibility.
			 *
			 * @param array $stopwords Stopwords.
			 */
			$stopwords = apply_filters( 'wp_search_stopwords', $stopwords ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			//
			// Parse search terms.
			// -------------------!
			$strtolower = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';

			foreach ( $matches[0] as $term ) {

				// Keep before/after spaces when term is for exact match.
				$term = preg_match( '/^".+"$/', $term ) ? trim( $term, "\"'" ) : trim( $term, "\"' " );

				// Avoid single A-Z and single dashes.
				if ( ! $term || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
					continue;
				}

				if ( in_array( call_user_func( $strtolower, $term ), $stopwords, TRUE ) ) {
					continue;
				}

				$search_terms[] = $term;

			}

			// If the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
			if ( empty( $search_terms ) || count( $search_terms ) > 9 ) {
				$search_terms = array( $search_term );
			}

		}
		else {
			$search_terms = array( $search_term );
		}

		return $search_terms;

	}

	/**
	 * Build the search SQL query for the given search terms
	 *
	 * @since 1.5.2
	 *
	 * @param array  $search_terms      An array of search terms.
	 * @param string $column            Optional. If passed will search in the specified table column.
	 * @param string $format            Optional. The format that has that column.
	 * @param string $table_prefix      Optional. If passed, this prefix will be added as table alias to the column names.
	 * @param bool   $is_meta_search    Optional. Whether the search is being performed for meta keys.
	 *
	 * @return string
	 */
	protected function build_search_query( $search_terms, $column = '', $format = 'string', $table_prefix = '', $is_meta_search = FALSE ) {

		global $wpdb;

		$search_query = $search_and = '';

		/**
		 * Filters the prefix that indicates that a search term should be excluded from results.
		 * Note that uses the WP_Query's filter name for compatibility.
		 *
		 * @param string $exclusion_prefix The prefix. Default '-'. Returning an empty value disables exclusions.
		 */
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		foreach ( $search_terms as $term ) {

			// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
			$exclude = $exclusion_prefix && ( substr( $term, 0, 1 ) === $exclusion_prefix );

			if ( $exclude ) {
				$operator = 'string' === $format ? 'NOT LIKE' : '!=';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			}
			else {
				$operator = 'string' === $format ? 'LIKE' : '=';
				$andor_op = 'OR';
			}

			switch ( $format ) {
				case 'int':
					$term = intval( $term );
					break;

				case 'float':
					$term = floatval( $term );
					break;

				default:
					$term = "'%%" . esc_sql( $wpdb->esc_like( $term ) ) . "%%'"; // Use double %, so it doesn't conflict with wpdb::prepare.
					break;
			}

			// Post meta search.
			if ( $is_meta_search ) {
				$meta_key_column   = $table_prefix ? "$table_prefix.meta_key" : 'meta_key';
				$meta_value_column = $table_prefix ? "$table_prefix.meta_value" : 'meta_value';
				$search_query     .= "{$search_and}(($meta_key_column = '$column' AND $meta_value_column $operator $term))";
			}
			// Regular search.
			elseif ( empty( $column ) ) {
				$search_query .= "{$search_and}(({$wpdb->posts}.post_title $operator $term) $andor_op ({$wpdb->posts}.post_excerpt $operator $term) $andor_op ({$wpdb->posts}.post_content $operator $term))";
			}
			// Search in column.
			else {
				$column        = $table_prefix ? "$table_prefix.$column" : $column;
				$search_query .= "{$search_and}(($column $operator $term))";
			}

			$search_and = ' AND ';

		}

		return $search_query;

	}

	/**
	 * Handle an incoming ajax request
	 * Called by the \Ajax class
	 *
	 * @since 0.0.1
	 */
	public function ajax_response() {

		$this->prepare_items();
		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();

		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		}
		else {
			$this->display_rows_or_placeholder();
		}

		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->display_tablenav( 'top' );
		$extra_tablenav_top = ob_get_clean();

		ob_start();
		$this->display_tablenav( 'bottom' );
		$extra_tablenav_bottom = ob_get_clean();

		ob_start();
		$this->views();
		$views = ob_get_clean();

		$response = array(
			'rows'           => $rows,
			'extra_t_n'      => array(
				'top'    => $extra_tablenav_top,
				'bottom' => $extra_tablenav_bottom,
			),
			'column_headers' => $headers,
			'views'          => $views,
		);

		if ( isset( $_REQUEST['paged'] ) && ! empty( $_REQUEST['paged'] ) ) {
			$response['paged'] = $_REQUEST['paged'];
		}

		if ( $this->show_totals ) {
			ob_start();
			$this->print_totals_columns();
			$response['totals'] = ob_get_clean();
		}

		if ( isset( $total_items ) ) {
			/* translators: the number of items */
			$response['total_items_i18n'] = sprintf( _n( '%s item', '%s items', $total_items, ATUM_TEXT_DOMAIN ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		wp_send_json( $response );

	}

	/**
	 * Increase the total of the specified column by the specified amount
	 *
	 * @since 1.4.2
	 *
	 * @param string    $column_name
	 * @param int|float $amount
	 */
	protected function increase_total( $column_name, $amount ) {

		if ( $this->show_totals && isset( $this->totalizers[ $column_name ] ) && is_numeric( $amount ) && ! in_array( $this->parent_type, [ 'grouped', 'bundle' ], TRUE ) ) {
			$this->totalizers[ $column_name ] += floatval( $amount );
		}
	}

	/**
	 * Enqueue the required scripts
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		// Sweet Alert 2.
		wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', array(), ATUM_VERSION );
		wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', array(), ATUM_VERSION, TRUE );

		// ATUM marketing popup.
		AtumMarketingPopup::maybe_enqueue_scripts();

		Helpers::maybe_es6_promise();

		if ( wp_script_is( 'es6-promise', 'registered' ) ) {
			wp_enqueue_script( 'es6-promise' );
		}

		// List Table styles.
		wp_register_style( 'atum-list', ATUM_URL . 'assets/css/atum-list.css', array( 'woocommerce_admin_styles', 'sweetalert2' ), ATUM_VERSION );

		wp_enqueue_style( 'atum-list' );

		if ( is_rtl() ) {
			wp_register_style( 'atum-list-rtl', ATUM_URL . 'assets/css/atum-list-rtl.css', array( 'atum-list' ), ATUM_VERSION );
			wp_enqueue_style( 'atum-list-rtl' );
		}

		// Load the ATUM colors.
		Helpers::enqueue_atum_colors( 'atum-list' );

		// If it's the first time the user edits the List Table, load the sweetalert to show the popup.
		// TODO: WHAT IS THIS????
		$first_edit_key = ATUM_PREFIX . "first_edit_$hook";
		if ( ! get_user_meta( get_current_user_id(), $first_edit_key, TRUE ) ) {
			$this->first_edit_key = $first_edit_key;
		}

		// List Table script.
		wp_register_script( 'atum-list', ATUM_URL . 'assets/js/build/atum-list-tables.js', [ 'jquery', 'jquery-blockui', 'sweetalert2', 'wc-enhanced-select', 'wp-hooks' ], ATUM_VERSION, TRUE );
		wp_enqueue_script( 'atum-list' );

		do_action( 'atum/list_table/after_enqueue_scripts', $this );

	}

	/**
	 * Getter for the table_columns property
	 *
	 * @since 1.2.5
	 *
	 * @return array
	 */
	public static function get_table_columns() {
		return self::$table_columns;
	}

	/**
	 * Setter for the table_columns property
	 *
	 * @since 1.2.5
	 *
	 * @param array $table_columns
	 */
	public static function set_table_columns( $table_columns ) {
		self::$table_columns = $table_columns;
	}

	/**
	 * Getter for the group_members property
	 *
	 * @since 1.2.5
	 *
	 * @return array
	 */
	public function get_group_members() {
		return $this->group_members;
	}

	/**
	 * Setter for the group_members property
	 *
	 * @since 1.2.5
	 *
	 * @param array $group_members
	 */
	public function set_group_members( $group_members ) {
		$this->group_members = $group_members;
	}

	/**
	 * Getter for the ATUM's searchable columns (only those stored on the ATUM product data table)
	 *
	 * @since 1.7.2
	 *
	 * @return array
	 */
	public function get_searchable_atum_columns() {

		// Just extract the column names from the atum_sortable_columnms.
		return array_keys( $this->atum_sortable_columns );

	}

	/**
	 * Getter for the current product prop
	 *
	 * @since 1.4.15
	 *
	 * @return \WC_Product
	 */
	public function get_current_product() {
		return $this->product;
	}

	/**
	 * Getter for the default_currency prop
	 *
	 * @since 1.4.16
	 *
	 * @return string
	 */
	public static function get_default_currency() {
		return self::$default_currency;
	}

	/**
	 * Get all the available children products in the system
	 *
	 * @since 1.1.1
	 *
	 * @param string $parent_type   The parent product type.
	 * @param array  $post_in       Optional. If is a search query, get only the children from the filtered products.
	 * @param string $post_type     Optional. The children post type.
	 *
	 * @return array
	 */
	protected function get_children( $parent_type, $post_in = array(), $post_type = 'product' ) {

		$cache_key    = AtumCache::get_cache_key( 'get_children', [ $parent_type, $post_in, $post_type ] );
		$children_ids = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $children_ids;
		}

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! Helpers::is_using_new_wc_tables() ) {
			$children_ids = $this->get_children_legacy( $parent_type, $post_in, $post_type );
			AtumCache::set_cache( $cache_key, $children_ids );
			return $children_ids;
		}

		global $wpdb;

		// Get all the published Variables first.
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		$where         = " p.post_type = 'product' AND p.post_status IN('" . implode( "','", $post_statuses ) . "')";

		if ( ! empty( $post_in ) ) {
			$where .= ' AND p.ID IN (' . implode( ',', $post_in ) . ')';
		}

		// phpcs:disable WordPress.DB.PreparedSQL
		$parents = $wpdb->get_col( $wpdb->prepare( "
			SELECT p.ID FROM $wpdb->posts p  
			LEFT JOIN {$wpdb->prefix}wc_products pr ON p.ID = pr.product_id  
			WHERE $where AND pr.type = %s
			GROUP BY p.ID
		", $parent_type ) );
		// phpcs:enable

		$parents_with_child = $grouped_products = $bundle_children = array();

		if ( ! empty( $parents ) ) {

			switch ( $parent_type ) {
				case 'variable':
					$this->container_products['all_variable'] = array_unique( array_merge( $this->container_products['all_variable'], $parents ) );
					break;

				case 'grouped':
					$this->container_products['all_grouped'] = array_unique( array_merge( $this->container_products['all_grouped'], $parents ) );

					// Get all the children from their corresponding meta key.
					foreach ( $parents as $parent_id ) {
						$children = get_post_meta( $parent_id, '_children', TRUE );

						if ( ! empty( $children ) && is_array( $children ) ) {
							$grouped_products     = array_merge( $grouped_products, $children );
							$parents_with_child[] = $parent_id;
						}
					}

					break;

				// WC Subscriptions compatibility.
				case 'variable-subscription':
					$this->container_products['all_variable_subscription'] = array_unique( array_merge( $this->container_products['all_variable_subscription'], $parents ) );
					break;

				// WC Products Bundle compatibility.
				case 'bundle':
					$this->container_products['all_bundle'] = array_unique( array_merge( $this->container_products['all_bundle'], $parents ) );

					$bundle_children = Helpers::get_bundle_items( array(
						'return'    => 'id=>product_id',
						'bundle_id' => $parents,
					) );

					foreach ( $parents as $parent_id ) {

						if ( ! empty( $bundle_children ) && is_array( $bundle_children ) ) {
							$parents_with_child[] = $parent_id;
						}

					}

					break;

			}

			// Store the main query data to not lose when returning back.
			$temp_query_data = $this->atum_query_data;

			$children_args = array(
				'post_type'      => $post_type,
				'post_status'    => $post_statuses,
				'posts_per_page' => - 1,
				'fields'         => 'id=>parent',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			);

			if ( 'grouped' === $parent_type ) {
				$children_args['post__in'] = $grouped_products;
			}
			else {
				$children_args['post_parent__in'] = $parents;
			}

			// Apply the same order and orderby args than their parent.
			$children_args = $this->parse_orderby_args( $children_args );

			// Sometimes with the general cache for this function is not enough to avoid duplicated queries.
			$query_cache_key = AtumCache::get_cache_key( 'get_children_query', $children_args );
			$children_ids    = AtumCache::get_cache( $query_cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

			if ( $has_cache ) {
				return $children_ids;
			}

			/*
			 * NOTE: we should apply here all the query filters related to individual child products
			 * like the ATUM control switch or the supplier
			 */
			$this->set_controlled_query_data();

			if ( ! empty( $this->supplier_variation_products ) ) {

				$this->atum_query_data['where'][] = array(
					'key'   => 'supplier_id',
					'value' => absint( $_REQUEST['supplier'] ),
					'type'  => 'NUMERIC',
				);

				$this->atum_query_data['where']['relation'] = 'AND';

			}

			// Pass through the ATUM query data filter.
			add_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );
			$children = new \WP_Query( apply_filters( 'atum/list_table/get_children/children_args', $children_args ) );
			remove_filter( 'posts_clauses', array( $this, 'atum_product_data_query_clauses' ) );

			// Restore the original query_data.
			$this->atum_query_data = $temp_query_data;

			if ( $children->found_posts ) {

				if ( 'grouped' !== $parent_type ) {
					$parents_with_child = wp_list_pluck( $children->posts, 'post_parent' );
				}

				switch ( $parent_type ) {
					case 'variable':
						$this->container_products['variable'] = array_unique( array_merge( $this->container_products['variable'], $parents_with_child ) );

						// Exclude all those variations with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_variable'], $this->container_products['variable'] ) ) );
						break;

					case 'grouped':
						$this->container_products['grouped'] = array_unique( array_merge( $this->container_products['grouped'], $parents_with_child ) );

						// Exclude all those grouped with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_grouped'], $this->container_products['grouped'] ) ) );
						break;

					case 'variable-subscription':
						$this->container_products['variable_subscription'] = array_unique( array_merge( $this->container_products['variable_subscription'], $parents_with_child ) );

						// Exclude all those subscription variations with no children from the list.
						$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_variable_subscription'], $this->container_products['variable_subscription'] ) ) );
						break;
				}

				$children_ids            = wp_list_pluck( $children->posts, 'ID' );
				$this->children_products = array_unique( array_merge( $this->children_products, $children_ids ) );

				AtumCache::set_cache( $cache_key, $children_ids );
				AtumCache::set_cache( $query_cache_key, $children_ids );

				return $children_ids;

			}
			elseif ( class_exists( '\WC_Product_Bundle' ) && 'bundle' === $parent_type ) {

				foreach ( $bundle_children as $key => $bundle_child ) {

					$product_children = Helpers::get_atum_product( $bundle_child );

					if ( $product_children ) {
						if ( 'yes' === Helpers::get_atum_control_status( $product_children ) ) {

							if ( ! $this->show_controlled ) {
								unset( $bundle_children[ $key ] );
							}

						}
						elseif ( $this->show_controlled ) {
							unset( $bundle_children[ $key ] );
						}
					}

				}

				if ( empty( $bundle_children ) ) {
					$parents_with_child = [];
				}
				else {

					$bundle_parents = [];
					foreach ( $bundle_children as $bundle_child ) {
						$bundle_parents = array_merge( $bundle_parents, wc_pb_get_bundled_product_map( $bundle_child ) );
					}

					$parents_with_child = $bundle_parents;

				}

				$this->container_products['bundle'] = array_unique( array_merge( $this->container_products['bundle'], $parents_with_child ) );

				// Exclude all those subscription variations with no children from the list.
				$this->excluded = array_unique( array_merge( $this->excluded, array_diff( $this->container_products['all_bundle'], $this->container_products['bundle'] ) ) );

				$this->children_products = array_unique( array_merge( $this->children_products, array_map( 'intval', $bundle_children ) ) );
				AtumCache::set_cache( $cache_key, $children_ids );
				AtumCache::set_cache( $query_cache_key, $bundle_children );

				return $bundle_children;

			}
			else {
				$this->excluded = array_unique( array_merge( $this->excluded, $parents ) );
			}

		}

		return array();

	}

	/**
	 * Get the parent products from a list of product IDs
	 *
	 * @since 1.1.1
	 *
	 * @param array $product_ids  The array of children product IDs.
	 *
	 * @return array
	 */
	protected function get_variation_parents( $product_ids ) {

		// TODO: WHAT IF WE HAVE TO ADD BUNDLE PRODUCT?
		global $wpdb;

		$parents = "
			SELECT DISTINCT post_parent FROM $wpdb->posts 		
			WHERE ID IN (" . implode( ',', $product_ids ) . ")
			AND post_parent > 0 AND post_type = 'product_variation'	
		";

		return $wpdb->get_col( $parents ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Get the parent grouped products from a list of product IDs
	 *
	 * @since 1.6.6
	 *
	 * @param array $product_ids The array of children product IDs.
	 *
	 * @return array
	 */
	protected function get_grouped_parents( $product_ids ) {

		global $wpdb;

		$like_clauses = [];

		foreach ( $product_ids as $product_id ) {
			$like_clauses[] = "meta_value LIKE '%i:" . $product_id . ";%'";
		}

		$grouped_sql = "
			SELECT DISTINCT post_id FROM $wpdb->postmeta 		
			WHERE post_id IN (" . implode( ',', $this->container_products['grouped'] ) . ")
			AND meta_key = '_children' AND ( " . implode( ' OR ', $like_clauses ) . ')	
		';

		return $like_clauses ? $wpdb->get_col( $grouped_sql ) : []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Builds a query string with the active filters
	 *
	 * @since 1.4.3
	 *
	 * @param string $format  Optional. The return format (array or string).
	 *
	 * @return string|array
	 */
	protected function get_filters_query_string( $format = 'array' ) {

		$default_filters = array(
			'paged'          => 1,
			'order'          => 'desc',
			'orderby'        => 'date',
			'view'           => 'all_stock',
			'product_cat'    => '',
			'product_type'   => '',
			'supplier'       => '',
			'extra_filter'   => '',
			's'              => '',
			'search_column'  => '',
			'sold_last_days' => '',
		);

		parse_str( $_SERVER['QUERY_STRING'], $query_string );
		$params = array_filter( array_intersect_key( $query_string, $default_filters ) );

		// The filters with default values should be excluded.
		foreach ( $params as $param => $value ) {
			if ( $value === $default_filters[ $param ] ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				unset( $params[ $param ] );
			}
		}

		return 'string' === $format ? http_build_query( $params ) : $params;

	}

	/**
	 * Get columns hidden by default
	 *
	 * @since 1.2.1
	 *
	 * @return array
	 */
	public static function hidden_columns() {
		return apply_filters( 'atum/list_table/default_hidden_columns', static::$default_hidden_columns );
	}

	/**
	 * Getter fot the is_report prop
	 *
	 * @since 1.4.16
	 *
	 * @return bool
	 */
	public static function is_report() {
		return self::$is_report;
	}

	/**
	 * Getter for the selected prop
	 *
	 * @since 1.9.1
	 *
	 * @return array|false|string[]
	 */
	public function get_selected() {
		return $this->selected;
	}

	/**
	 * Apply order and orderby args by $_REQUEST options.
	 *
	 * @since 1.8.6
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	protected function parse_orderby_args( $args ) {

		if ( ! isset( $_REQUEST['orderby'] ) || empty( $_REQUEST['orderby'] ) || 'date' === $_REQUEST['orderby'] ) {
			return $args;
		}

		$order                 = ( isset( $_REQUEST['order'] ) && 'asc' === $_REQUEST['order'] ) ? 'ASC' : 'DESC';
		$atum_sortable_columns = apply_filters( 'atum/list_table/atum_sortable_columns', $this->atum_sortable_columns );

		// Columns starting by underscore are based in meta keys, so can be sorted.
		if ( '_' === substr( $_REQUEST['orderby'], 0, 1 ) ) {

			if ( array_key_exists( $_REQUEST['orderby'], $atum_sortable_columns ) ) {

				$this->atum_query_data['order']          = $atum_sortable_columns[ $_REQUEST['orderby'] ];
				$this->atum_query_data['order']['order'] = $order;

			}
			// All the meta key based columns are numeric except the SKU.
			else {

				if ( '_sku' === $_REQUEST['orderby'] ) {
					$args['orderby'] = 'meta_value';
				}
				else {
					$args['orderby'] = 'meta_value_num';
				}

				$args['meta_query']['relation'] = 'OR';

				$args['meta_query'][] = array(
					'meta_key' => $_REQUEST['orderby'],
				);

				$args['meta_query'][] = array(
					'key'     => $_REQUEST['orderby'],
					'compare' => 'EXISTS',
				);

				$args['meta_query'][] = array(
					'key'     => $_REQUEST['orderby'],
					'compare' => 'NOT EXISTS',
				);

				/*$args['meta_key'] = $_REQUEST['orderby'];*/
				$args['order'] = $order;

			}

		}
		// Standard Fields.
		else {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order']   = $order;
		}

		return $args;
	}
}
