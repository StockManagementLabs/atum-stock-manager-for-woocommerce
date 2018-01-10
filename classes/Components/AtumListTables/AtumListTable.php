<?php
/**
 * @package         Atum\Components
 * @subpackage      AtumListTables
 * @author          Salva Machí and Jose Piera - https://sispixels.com
 * @copyright       ©2017 Stock Management Labs™
 *
 * @since           0.0.1
 *
 * Extends WP_List_Table to display the stock management table
 */

namespace Atum\Components\AtumListTables;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Inc\Globals;
use Atum\Inc\Helpers;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Settings\Settings;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

abstract class AtumListTable extends \WP_List_Table {

	/**
	 * The post type used to build the table (WooCommerce product)
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Current product used
	 * @var \WC_Product
	 */
	protected $product;
	
	/**
	 * The table columns
	 * @var array
	 */
	protected $table_columns;
	
	/**
	 * The previously selected items
	 * @var array
	 */
	protected $selected = array();
	
	/**
	 * Group title columns
	 * @var array
	 */
	protected $group_columns = array();
	
	/**
	 * Group members
	 * @var array
	 */
	protected $group_members = array();

	/**
	 * The array of published Variable products' IDs
	 * @var array
	 */
	protected $variable_products = array();

	/**
	 * The array of published Grouped products' IDs
	 * @var array
	 */
	protected $grouped_products = array();
	
	/**
	 * Elements per page (in order to obviate option default)
	 * @var int
	 */
	protected $per_page;
	
	/**
	 * Arrat with the id's of the products in current page
	 * @var array
	 */
	protected $current_products;
	
	/**
	 * Taxonomies to filter by
	 * @var array
	 */
	protected $taxonomies = array();

	/**
	 * Extra meta args for the list query
	 * @var array
	 */
	protected $extra_meta = array();
	
	/**
	 * Data for send to client side
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * IDs for views
	 * @var array
	 */
	protected $id_views = array();
	
	/**
	 * Counters for views
	 * @var array
	 */
	protected $count_views = array();

	/**
	 * Sale days from settings
	 * @var int
	 */
	protected $last_days;

	/**
	 * Whether the currently displayed product is an expandable child product
	 * @var bool
	 */
	protected $is_child = FALSE;

	/**
	 * Whether or not the current product should do the calculations for the columns
	 * @var bool
	 */
	protected $allow_calcs = TRUE;
	
	/**
	 * Whether the WPML multicurrency option is active
	 * @var bool
	 */
	protected $is_wpml_multicurrency = FALSE;
	
	/**
	 * Current currency symbol
	 *
	 * @var string
	 */
	protected $current_currency;
	
	/**
	 * Default currency symbol
	 * @var string
	 */
	protected $default_currency;
	
	/**
	 * WooCommerce WPML if exists
	 * @var \woocommerce_wpml;
	 */
	protected $wpml;
	
	/**
	 * Current product and currency custom prices (WPML Multi-currency custom product prices)
	 * @var array|bool
	 */
	protected $custom_prices = FALSE;
	
	/**
	 * Original language product's id
	 * @var int
	 */
	protected $original_product_id;

	/**
	 * The user meta key used for first edit popup
	 * @var string
	 */
	protected $first_edit_key;
	
	/**
	 * User meta key to control the current user dismissed notices
	 */
	const DISMISSED_NOTICES = 'atum_dismissed_notices';

	/**
	 * Value for empty columns
	 */
	const EMPTY_COL = '&mdash;';
	
	/**
	 * Constructor
	 *
	 * The child class should call this constructor from its own constructor to override the default $args.
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $args          {
	 *      Array or string of arguments.
	 *
	 *      @type array  $table_columns The table columns for the list table
	 *      @type array  $group_members The column grouping members
	 *      @type bool   $show_cb       Optional. Whether to show the row selector checkbox as first table column
	 *      @type int    $per_page      Optional. The number of posts to show per page (-1 for no pagination)
	 *      @type array  $selected      Optional. The posts selected on the list table
	 * }
	 */
	public function __construct( $args = array() ) {

		$this->last_days = absint( Helpers::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );
		
		$args = wp_parse_args( $args, array(
			'show_cb'  => FALSE,
			'per_page' => Settings::DEFAULT_POSTS_PER_PAGE,
		) );
		
		if ( ! empty( $args['selected'] ) ) {
			$this->selected = ( is_array( $args['selected'] ) ) ? $args['selected'] : explode( ',', $args['selected'] );
		}

		if ( ! empty($args['group_members']) ) {
			$this->group_members = $args['group_members'];
		}
		
		// Add the checkbox column to the table if enabled
		$this->table_columns = ( $args['show_cb'] == TRUE ) ? array_merge( array( 'cb' => 'cb' ), $args['table_columns'] ) : $args['table_columns'];
		$this->per_page      = $args['per_page'];
		
		$post_type_obj = get_post_type_object( $this->post_type );
		
		if ( ! $post_type_obj ) {
			return FALSE;
		}
		
		// Set \WP_List_Table defaults
		$args = array_merge( array(
			'singular' => strtolower( $post_type_obj->labels->singular_name ),
			'plural'   => strtolower( $post_type_obj->labels->name ),
			'ajax'     => TRUE
		), $args );
		
		parent::__construct( $args );

		add_filter( 'posts_search', array( $this, 'product_search' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		$user_dismissed_notices = Helpers::get_dismissed_notices();
		
		if (
			! Helpers::is_atum_managing_stock() &&
			( !$user_dismissed_notices || ! isset($user_dismissed_notices['manage_stock']) || $user_dismissed_notices['manage_stock'] != 'yes' )
		) {
			
			add_action( 'admin_notices', array( $this, 'add_manage_stock_notice' ) );
		}
		
		$this->current_currency = $this->default_currency = get_woocommerce_currency();

		// Do WPML Stuff
		if ( class_exists('\woocommerce_wpml') ) {
			
			$this->wpml = \woocommerce_wpml::instance();
			
			if ( $this->wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
				
				$this->is_wpml_multicurrency = TRUE;
				
				global $sitepress;
				$current_lang = $sitepress->get_current_language();
				
				if ( ! empty( $this->wpml->settings['default_currencies'][ $current_lang ] ) ) {
					$this->current_currency = $this->wpml->settings['default_currencies'][ $current_lang ];
				}
			}
		}
		
		
	}

	/**
	 * Extra controls to be displayed in table nav sections
	 *
	 * @since  1.3.0
	 *
	 * @param string $which 'top' or 'bottom' table nav
	 */
	protected function extra_tablenav( $which ) {

		if ( $which == 'top' ): ?>

			<div class="alignleft actions">
				<div class="actions-wrapper">

					<?php $this->table_nav_filters() ?>

					<?php if ( Helpers::get_option( 'enable_ajax_filter', 'yes' ) == 'no' ): ?>
						<input type="submit" name="filter_action" class="button search-category" value="<?php _e('Filter', ATUM_TEXT_DOMAIN) ?>">
					<?php endif; ?>

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

		// Category filtering
		wc_product_dropdown_categories( array(
			'show_count' => 0,
			'selected'   => ( ! empty( $_REQUEST['product_cat'] ) ) ? esc_attr( $_REQUEST['product_cat'] ) : '',
		) );

		// Product type filtering
		echo Helpers::product_types_dropdown( ( isset( $_REQUEST['product_type'] ) ) ? esc_attr( $_REQUEST['product_type'] ) : '' );

		// Supplier filtering
		echo Helpers::suppliers_dropdown( ( isset( $_REQUEST['supplier'] ) ) ? esc_attr( $_REQUEST['supplier'] ) : '', Helpers::get_option( 'enhanced_suppliers_filter', 'no' ) == 'yes' );

	}

	/**
	 * Loads the current product
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 */
	public function single_row( $item ) {

		$this->product = wc_get_product( $item );
		$type = $this->product->get_type();

		$this->custom_prices = FALSE;

		// Do the WPM stuff
		if ($this->is_wpml_multicurrency) {

			global $sitepress;
			$this->original_product_id = $item->ID;

			$product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($item->ID, 'post_'.$this->post_type), 'post_'.$this->post_type);
			foreach($product_translations as $translation){
				if( $translation->original ){
					$this->original_product_id = $translation->element_id;
					break;
				}
			}

			if ( get_post_meta( $this->original_product_id, '_wcml_custom_prices_status', TRUE ) ) {
				$custom_price_ui = new \WCML_Custom_Prices_UI( $this->wpml, $this->original_product_id);

				if ( $custom_price_ui) {

					global $thepostid;
					$keep_id = ($thepostid)? $thepostid : 0;
					$thepostid = $this->original_product_id;

					$this->custom_prices = $custom_price_ui->get_currencies_info();

					$thepostid = $keep_id;
				}
			}

		}
		// If a product is set as hidden from the catalog and is part of a Grouped product, don't display it on the list
		/*if ( $type == 'simple' && $this->product->visibility == 'hidden' && ! empty($this->product->post->post_parent) ) {
			return;
		}*/

		$this->allow_calcs = ( in_array( $type, Globals::get_inheritable_product_types() ) ) ? FALSE : TRUE;

		// Output the row
		echo '<tr data-id="' . $this->get_current_product_id() . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

		// Add the children products of each inheritable product type
		if ( in_array( $type, Globals::get_inheritable_product_types() ) ) {

			$product_class = '\WC_Product_' . ucwords( str_replace('-', '_', $type), '_' );
			$parent_product = new $product_class( $this->product->get_id() );
			$child_products = $parent_product->get_children();

			if ( ! empty($child_products) ) {

				// If the post__in filter is applied, bypass the children that are not in the query var
				$post_in = get_query_var('post__in');

				$this->allow_calcs = TRUE;

				foreach ($child_products as $child_id) {

					if ( ! empty($post_in) && ! in_array($child_id, $post_in) ) {
						continue;
					}

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
	 * Generates content for a expandable row on the table
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 * @param string   $type The type of product
	 */
	public function single_expandable_row( $item, $type ) {
		
		$this->custom_prices = FALSE;

		// If WPML has Multi Currency enabled, the related info is saved in the original product
		if ($this->is_wpml_multicurrency) {
			
			global $sitepress;
			$this->original_product_id = $item->get_id();
			
			$product_translations = $sitepress->get_element_translations($sitepress->get_element_trid($item->get_id(), "post_{$type}"), "post_{$type}");

			foreach($product_translations as $translation){
				if( $translation->original ){
					$this->original_product_id = $translation->element_id;
					break;
				}
			}
			
			if ( get_post_meta( $this->original_product_id, '_wcml_custom_prices_status', TRUE ) ) {

				$custom_price_ui = new \WCML_Custom_Prices_UI( $this->wpml, $this->original_product_id);
				
				if ( $custom_price_ui) {
					
					global $thepostid;
					$keep_id = ($thepostid)? $thepostid : 0;
					$thepostid = $this->original_product_id;
					
					$this->custom_prices = $custom_price_ui->get_currencies_info();
					
					$thepostid = $keep_id;
				}

			}
			
		}
		
		echo '<tr class="' . $type . '" style="display: none" data-id="' . $this->get_current_product_id() . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * The default column (when no specific column method found)
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item          The WooCommerce product post
	 * @param string   $column_name   The current column name
	 *
	 * @return string|bool
	 */
	protected function column_default( $item, $column_name ) {

		$id = $this->get_current_product_id();
		$column_item = '';

		// Check if it's a hidden meta key (will start with underscore)
		if ( substr( $column_name, 0, 1 ) == '_' ) {
			$column_item = get_post_meta( $id, $column_name, TRUE );
		}

		if ($column_item === '' || $column_item === FALSE) {
			$column_item = self::EMPTY_COL;
		}

		return apply_filters( "atum/list_table/column_default_$column_name", $column_item, $item, $this->product );

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
		
		return sprintf(
			'<input type="checkbox"%s name="%s[]" value="%s">',
			checked( in_array( $item->ID, $this->selected ), TRUE, FALSE ),
			$this->_args['singular'],
			$item->ID
		);
	}

	/**
	 * Column for thumbnail
	 *
	 * @since 0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_thumb( $item ) {

		$product_id = $this->get_current_product_id();
		$thumb = '<a href="' . get_edit_post_link($product_id) .'" target="_blank">' . $this->product->get_image( [40, 40] ) . '</a>';
		return apply_filters( 'atum/list_table/column_thumb', $thumb, $item, $this->product );
	}

	/**
	 * Post title column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_title( $item ) {

		$title      = '';
		$product_id = $this->get_current_product_id();

		if ( in_array( $this->product->get_type(), Globals::get_child_product_types() ) ) {

			$attributes = wc_get_product_variation_attributes($product_id);
			if ( ! empty($attributes) ) {
				$title = ucfirst( implode(' ', $attributes) );
			}

			// Get the variable product ID to get the right link
			$product_id = $this->product->get_parent_id();

		}
		else {
			$title = $this->product->get_title();
		}

		$title_length = absint( apply_filters( 'atum/list_table/column_title_length', 20 ) );

		if ( strlen( $title ) > $title_length ) {
			$title = '<span class="tips" data-toggle="tooltip" title="' . $title . '">' . trim( substr( $title, 0, $title_length ) ) .
			         '...</span><span class="atum-title-small">' . $title . '</span>';
		}

		$title = '<a href="' . get_edit_post_link($product_id) . '" target="_blank">' . $title . '</a>';

		return apply_filters( 'atum/list_table/column_title', $title, $item, $this->product );
	}

	/**
	 * Supplier column
	 *
	 * @since  1.3.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
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

				$supplier         = $supplier_post->post_title;
				$supplier_length  = absint( apply_filters( 'atum/list_table/column_supplier_length', 20 ) );
				$supplier_abb     = ( strlen( $supplier ) > $supplier_length ) ? trim( substr( $supplier, 0, $supplier_length ) ) . '...' : $supplier;
				$supplier_tooltip = sprintf( __( '%s (ID: %d)', ATUM_TEXT_DOMAIN ), $supplier, $supplier_id );

				$supplier = '<span class="tips" data-toggle="tooltip" title="' . $supplier_tooltip . '">' . $supplier_abb . '</span>' .
				            '<span class="atum-title-small">' . $supplier_tooltip . '</span>';

			}

		}

		return apply_filters( 'atum/list_table/column_supplier', $supplier, $item, $this->product );
	}

	/**
	 * Product SKU column
	 *
	 * @since  1.1.2
	 *
	 * @param \WP_Post $item     The WooCommerce product post
	 * @param bool     $editable Whether the SKU will be editable
	 *
	 * @return string
	 */
	protected function column__sku( $item, $editable = TRUE ) {

		$id = $this->get_current_product_id();
		$sku = get_post_meta( $id, '_sku', TRUE );
		$sku = $sku ?: self::EMPTY_COL;

		if ($editable) {

			$args = array(
				'post_id'    => $id,
				'meta_key'   => 'sku',
				'value'      => $sku,
				'input_type' => 'text',
				'tooltip'    => __( 'Click to edit the SKU', ATUM_TEXT_DOMAIN )
			);

			$sku = $this->get_editable_column($args);

		}

		return apply_filters( 'atum/list_table/column_sku', $sku, $item, $this->product );

	}

	/**
	 * Post ID column
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return int
	 */
	protected function column_ID( $item ) {
		return apply_filters( 'atum/list_table/column_ID', $this->get_current_product_id(), $item, $this->product );
	}

	/**
	 * Column for product type
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $item The WooCommerce product post
	 *
	 * @return string
	 */
	protected function column_calc_type( $item ) {

		$type = $this->product->get_type();
		$product_types = wc_get_product_types();

		if ( isset($product_types[$type]) || $this->is_child ) {

			if ( ! $this->is_child ) {
				$product_tip = $product_types[ $type ];
			}

			switch ( $type ) {
				case 'simple':

					if ($this->is_child) {
						$type = 'grouped-item';
						$product_tip = __('Grouped item', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->is_downloadable() ) {
						$type = 'downloadable';
						$product_tip = __('Downloadable product', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->is_virtual() ) {
						$type = 'virtual';
						$product_tip = __('Virtual product', ATUM_TEXT_DOMAIN);
					}

					break;

				case 'variable':
				case 'grouped':
				case 'variable-subscription': // WC Subscriptions compatibility

					if ($this->is_child) {
						$type = 'grouped-item';
						$product_tip = __('Grouped item', ATUM_TEXT_DOMAIN);
					}
					elseif ( $this->product->has_child() ) {

						$product_tip .= '<br>' . sprintf(
							__('(click to show/hide the %s)', ATUM_TEXT_DOMAIN),
							( ($type == 'grouped') ? __('Grouped items', ATUM_TEXT_DOMAIN) : __('Variations', ATUM_TEXT_DOMAIN) )
						);
						$type .= ' has-child';

					}

					break;

				case 'variation':

					$product_tip = __('Variation', ATUM_TEXT_DOMAIN);
					break;

				// WC Subscriptions compatibility
				case 'subscription_variation':

					$product_tip = __('Subscription Variation', ATUM_TEXT_DOMAIN);
					break;
			}

			return apply_filters( 'atum/list_table/column_type', '<span class="product-type tips ' . $type . '" data-toggle="tooltip" title="' . $product_tip . '"></span>', $item, $this->product );

		}

		return '';
	}

	/**
	 * Column for purchase price
	 *
	 * @since  1.2.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return float
	 */
	protected function column__purchase_price( $item ) {

		$purchase_price = self::EMPTY_COL;

		if ( ! AtumCapabilities::current_user_can('view_purchase_price') ) {
			return $purchase_price;
		}

		$product_id = $this->get_current_product_id();

		if ($this->allow_calcs) {

			if ( ! empty( $this->custom_prices[ $this->current_currency ] ) ) {
				$currency             = $this->current_currency;
				$purchase_price_value = $this->custom_prices[ $currency ]['custom_price']['_purchase_price'];
				$symbol               = $this->custom_prices[ $currency ]['currency_symbol'];
				$is_custom            = 'yes';
			}
			else {

				// The meta is synced between translations. Doesn't matter whether the current is the original
				$purchase_price_value = get_post_meta( $product_id, '_purchase_price', TRUE );
				$symbol               = get_woocommerce_currency_symbol();
				$currency             = $this->default_currency;
				$is_custom            = 'no';
			}

			$purchase_price_value = ( is_numeric($purchase_price_value) ) ? Helpers::format_price($purchase_price_value, ['trim_zeros' => TRUE, 'currency' => $currency]) : $purchase_price;

			$args = array(
				'post_id'   => $product_id,
				'meta_key'  => 'purchase_price',
				'value'     => $purchase_price_value,
				'symbol'    => $symbol,
				'currency'  => $currency,
				'is_custom' => $is_custom,
				'tooltip'   => __( 'Click to edit the purchase price', ATUM_TEXT_DOMAIN )
			);

			$purchase_price = $this->get_editable_column($args);
		}

		return apply_filters( 'atum/stock_central_list/column_purchase_price', $purchase_price, $item, $this->product );

	}

	/**
	 * Column for stock amount
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 * @param bool     $editable Whether the stock will be editable
	 *
	 * @return string|int
	 */
	protected function column__stock( $item, $editable = TRUE ) {

		$stock = self::EMPTY_COL;
		$product_id = $this->get_current_product_id();

		if ( ! isset($this->allow_calcs) || ( isset($this->allow_calcs) && $this->allow_calcs) ) {

			$stock = wc_stock_amount( $this->product->get_stock_quantity() );

			if ($editable) {

				$args = array(
					'post_id'  => $product_id,
					'meta_key' => 'stock',
					'value'    => $stock,
					'tooltip'  => __( 'Click to edit the stock quantity', ATUM_TEXT_DOMAIN )
				);

				$stock = $this->get_editable_column( $args );

			}

		}

		return apply_filters( 'atum/stock_central_list/column_stock', $stock, $item, $this->product );

	}

	/**
	 * Column for inbound stock: shows sum of inbound stock within Purchase Orders.
	 *
	 * @since  1.3.0
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 *
	 * @return int
	 */
	protected function column_calc_inbound( $item ) {

		$inbound_stock = self::EMPTY_COL;

		if ($this->allow_calcs) {

			// Calculate the inbound stock from pending purchase orders
			global $wpdb;

			$sql = $wpdb->prepare("
				SELECT SUM(oim2.`meta_value`) AS quantity 			
				FROM `{$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
				LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim ON oi.`order_item_id` = oim.`order_item_id`
				LEFT JOIN `{$wpdb->atum_order_itemmeta}` AS oim2 ON oi.`order_item_id` = oim2.`order_item_id`
				LEFT JOIN `{$wpdb->posts}` AS p ON oi.`order_id` = p.`ID`
				WHERE oim.`meta_key` IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
				AND p.`post_type` = %s AND oim.`meta_value` = %d AND `post_status` = 'atum_pending' AND oim2.`meta_key` = '_qty'	
				GROUP BY oim.`meta_value`;",
				PurchaseOrders::POST_TYPE,
				$this->product->get_id()
			);

			$inbound_stock = $wpdb->get_col($sql);
			$inbound_stock = ( ! empty($inbound_stock) ) ? reset($inbound_stock) : 0;

		}

		return apply_filters( 'atum/stock_central_list/column_inbound_stock', $inbound_stock, $item, $this->product );
	}

	/**
	 * Column for stock indicators
	 *
	 * @since  0.0.1
	 *
	 * @param \WP_Post $item The WooCommerce product post to use in calculations
	 * @param string   $classes
	 * @param string   $data
	 * @param string   $primary
	 */
	protected function _column_calc_stock_indicator( $item, $classes, $data, $primary ) {

		$product_id = $this->product->get_id();
		$content = '';

		// Add css class to the <td> elements depending on the quantity in stock compared to the last days sales
		if ( isset($this->allow_calcs) && !$this->allow_calcs ) {
			$content = self::EMPTY_COL;
		}
		// Out of stock
		elseif ( in_array($product_id, $this->id_views['out_stock']) ) {
			$classes .= ' cell-red';
			$content = '<span class="dashicons dashicons-dismiss" data-toggle="tooltip" title="' . __('Out of Stock', ATUM_TEXT_DOMAIN) . '"></span>';
		}
		// Low Stock
		elseif ( in_array($product_id, $this->id_views['low_stock']) ) {
			$classes .= ' cell-yellow';
			$content = '<span class="dashicons dashicons-warning" data-toggle="tooltip" title="' . __('Low Stock', ATUM_TEXT_DOMAIN) . '"></span>';
		}
		// In Stock
		elseif ( in_array($product_id, $this->id_views['in_stock']) ) {
			$classes .= ' cell-green';
			$content = '<span class="dashicons dashicons-yes" data-toggle="tooltip" title="' . __('In Stock', ATUM_TEXT_DOMAIN) . '"></span>';
		}

		$classes = ( $classes ) ? ' class="' . $classes . '"' : '';

		echo '<td ' . $data . $classes . '>' .
		     apply_filters( 'atum/list_table/column_stock_indicator', $content, $item, $this->product ) .
		     $this->handle_row_actions( $item, 'calc_stock_indicator', $primary ) . '</td>';

	}
	
	/**
	 * REQUIRED! This method dictates the table's columns and titles.
	 * This should return an array where the key is the column slug (and class) and the value
	 * is the column's title text.
	 *
	 * @see   WP_List_Table::single_row_columns()
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		
		$result = array();
		
		foreach ( $this->table_columns as $table => $slug ) {
			$group = $this->search_group_columns( $table );
			$result[ $table ] = ( $group ) ? "<span class='col-$group'>$slug</span>" : $slug;
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
	 *      @type int    $post_id           The current post ID
	 *      @type string $meta_key          The meta key name (without initial underscore) to be saved
	 *      @type mixed  $value             The new value for the meta key cell
	 *      @type string $symbol            Whether to add any symbol to value
	 *      @type string $tooltip           The informational tooltip text
	 *      @type string $input_type        The input type field to use to edit the column value
	 *      @type array  $extra_meta        Any extra fields will be appended to the popover (as JSON array)
	 *      @type string $tooltip_position  Where to place the tooltip
	 *      @type string $is_custom         For prices, whether value is a WPML custom price value or not
	 *      @type string $currency          Product prices currency
	 * }
	 *
	 * @return string
	 */
	protected function get_editable_column ($args) {

		/**
		 * @var int    $post_id
		 * @var string $meta_key
		 * @var mixed  $value
		 * @var string $symbol
		 * @var string $tooltip
		 * @var string $input_type
		 * @var array  $extra_meta
		 * @var string $tooltip_position
		 * @var string $is_custom
		 * @var string $currency
		 */
		extract( wp_parse_args( $args, array(
			'post_id'          => NULL,
			'meta_key'         => '',
			'value'            => '',
			'symbol'           => '',
			'tooltip'          => '',
			'input_type'       => 'number',
			'extra_meta'       => array(),
			'tooltip_position' => 'top',
			'is_custom'        => 'no',
			'currency'         => $this->default_currency
		) ) );

		$extra_meta_data = ( ! empty($extra_meta) ) ? ' data-extra-meta="' . htmlspecialchars( json_encode($extra_meta), ENT_QUOTES, 'UTF-8') . '"' : '';
		$symbol_data = ( ! empty($symbol) ) ? ' data-symbol="' . esc_attr($symbol) . '"' : '';

		$editable_col = '<span class="set-meta tips" data-toggle="tooltip" title="' . $tooltip . '" data-placement="' . $tooltip_position .
		       '" data-item="' . $post_id . '" data-meta="' . $meta_key . '"' . $symbol_data . $extra_meta_data .
		       ' data-input-type="' . $input_type . '" data-custom="' . $is_custom . '" data-currency="' . $currency . '">' . $value . '</span>';


		return apply_filters('atum/list_table/editable_column', $editable_col, $args);

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
	 * @return array An associative array containing all the columns that should be sortable: 'slugs' => array('data_values', bool)
	 */
	protected function get_sortable_columns() {
		
		$not_sortable = array( 'thumb', 'cb' );
		$sortable_columns = array();
		
		foreach ( $this->table_columns as $key => $column ) {
			if ( ! in_array( $key, $not_sortable ) && ! ( strpos( $key, 'calc_' ) === 0 ) ) {
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

		$views    = array();
		$v_filter = ( ! empty( $_REQUEST['v_filter'] ) ) ? esc_attr( $_REQUEST['v_filter'] ) : 'all_stock';

		$views_name = array(
			'all_stock' => __('All', ATUM_TEXT_DOMAIN),
			'in_stock'  => __('In Stock', ATUM_TEXT_DOMAIN),
			'out_stock' => __('Out of Stock', ATUM_TEXT_DOMAIN),
			'low_stock' => __('Low Stock', ATUM_TEXT_DOMAIN)
		);

		global $plugin_page;
		$url = esc_url( add_query_arg( 'page', $plugin_page, admin_url()) );

		foreach ( $views_name as $key => $text ) {

			$id = '';
			if ( $key != 'all_stock' ) {
				$view_url = esc_url( add_query_arg( array( 'v_filter' => $key ), $url ) );
				$id = ' id="' . $key . '"';
			}
			else {
				$view_url = $url;
			}

			$class = ( $key == $v_filter || ( ! $v_filter && $key == 'all_stock' ) ) ? ' class="current"' : '';
			$count = $this->count_views[ 'count_' . ( ( $key == 'all_stock' ) ? 'all' : $key ) ];

			$views[ $key ] = '<a' . $id . $class . ' href="' . $view_url . '"><span>' . $text . ' (' . $count . ')</span></a>';
		}

		return apply_filters( 'atum/list_table/view_filters', $views );

	}
	
	/**
	 * Bulk actions are an associative array in the format 'slug' => 'Visible Title'
	 *
	 * @since 0.0.1
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	protected function get_bulk_actions() {
		return apply_filters( 'atum/list_table/bulk_actions', array() );
	}
	
	/**
	 * Prepare the table data
	 *
	 * @since  0.0.1
	 */
	public function prepare_items() {
		
		/**
		 * Define our column headers
		 */
		$columns             = $this->get_columns();
		$selected_posts      = $posts_meta_query = $posts = array();
		$sortable            = $this->get_sortable_columns();
		$hidden              = get_hidden_columns( $this->screen );
		$this->group_columns = $this->calc_groups( $this->group_members, $hidden );
		
		/**
		 * REQUIRED. Build an array to be used by the class for column headers.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$args = array(
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $this->per_page,
			'paged'          => $this->get_pagenum()
		);

		/*
		 * Tax filter
		 */

		// Add product category to the tax query
		if ( ! empty( $_REQUEST['product_cat'] ) ) {
			$this->taxonomies[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => esc_attr( $_REQUEST['product_cat'] )
			);
		}

		// Change the product type tax query (initialized in constructor) to the current queried type
		if ( ! empty( $_REQUEST['product_type'] ) ) {

			$type = esc_attr( $_REQUEST['product_type'] );

			foreach($this->taxonomies as $index => $taxonomy) {

				if ($taxonomy['taxonomy'] == 'product_type') {

					if ( in_array($type, ['downloadable', 'virtual']) ) {
						$this->taxonomies[$index]['terms'] = 'simple';

						$this->extra_meta = array(
							'key'   => "_$type",
							'value' => 'yes'
						);

					}
					else {
						$this->taxonomies[$index]['terms'] = $type;
					}

					break;
				}

			}

		}

		if ( $this->taxonomies ) {
			$args['tax_query'] = (array) apply_filters( 'atum/list_table/taxonomies', $this->taxonomies );
		}
		
		/*
		 * Check whether ATUM is managing the WC stock
		 */
		if ( ! Helpers::is_atum_managing_stock() ) {
			
			// Only products with the _manage_stock meta set to yes
			$args['meta_query'][] = array(
				'key'   => '_manage_stock',
				'value' => 'yes'
			);
		}

		/*
		 * Supplier filter
		 */
		if ( ! empty( $_REQUEST['supplier'] ) && AtumCapabilities::current_user_can('read_supplier') ) {

			if ( ! empty($args['meta_query']) ) {
				$args['meta_query']['relation'] = 'AND';
			}

			$args['meta_query'][] = array(
				'key'   => '_supplier',
				'value' => absint( $_REQUEST['supplier'] ),
				'type'  => 'numeric'
			);

		}

		/*
		 * Extra meta args
		 */
		if ( ! empty($this->extra_meta) ) {
			$args['meta_query'][] = $this->extra_meta;
		}
		
		/*
		 * Ordering
		 */
		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			
			$args['order'] = $_REQUEST['order'];
			
			// Columns starting by underscore are based in meta keys, so can be sorted
			if ( substr( $_REQUEST['orderby'], 0, 1 ) == '_' ) {

				// All the meta key based columns are numeric except the SKU
				if ( $_REQUEST['orderby'] == '_sku' ) {
					$args['orderby']  = 'meta_value';
				}
				else {
					$args['orderby']  = 'meta_value_num';
				}

				$args['meta_key'] = $_REQUEST['orderby'];

			}
			// Calculated column... Can be sorted?
			/*elseif ( strpos( $_REQUEST['orderby'], 'calc_' ) === 0 ) {

			}*/
			// Standard Fields
			else {
				$args['orderby'] = $_REQUEST['orderby'];
			}
		}
		
		/*
		 * Searching
		 */
		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = esc_attr( $_REQUEST['s'] );
		}
		elseif ( ! empty( $this->selected ) ) {
			
			// Get first the selected posts that will be upper in the table
			$filter_args = array(
				'post__in' => $this->selected,
				'orderby'  => 'post__in'
			);
			
			$selected_posts_query = new \WP_Query( array_merge( $filter_args, $args ) );
			$selected_posts       = $selected_posts_query->posts;
			$args['post__not_in'] = $this->selected; // Exclude the selected posts from next query
			
		}
		
		// Build "Views Filters" and calculate totals
		$this->set_views_data( $args );
		
		$this->data['v_filter'] = '';
		$allow_query = TRUE;
		
		/*
	     * REQUIRED. Register our pagination options & calculations.
		 */
		$found_posts  = isset( $this->count_views['count_all'] ) ? $this->count_views['count_all'] : 0;
		$num_children = isset( $this->count_views['count_children'] ) ? $this->count_views['count_children'] : 0;
		$num_parent   = isset( $this->count_views['count_parent'] ) ? $this->count_views['count_parent'] : 0;

		if ( ! empty( $_REQUEST['v_filter'] ) ) {
			
			$this->data['v_filter'] = esc_attr( $_REQUEST['v_filter'] );
			$allow_query = FALSE;
			
			foreach ( $this->id_views as $key => $post_ids ) {
				
				if ( $this->data['v_filter'] == $key && ! empty($post_ids) ) {

					// Add the parent products again to the query
					$args['post__in'] = ( ! empty($this->variable_products) || ! empty($this->grouped_products) ) ? $this->get_parents($post_ids) : $post_ids;
					$allow_query = TRUE;
					$found_posts = $this->count_views["count_$key"];

				}
				
			}
		}
		
		if ( $allow_query ) {

			// Setup the WP query
			global $wp_query;
			$wp_query = new \WP_Query( $args );
			
			$posts = array_merge( $selected_posts, $wp_query->posts );
			$this->current_products = wp_list_pluck($posts, 'ID');
			
			$total_pages = ( $this->per_page == - 1 ) ? 0 : ceil( ($found_posts - $num_children + $num_parent) / $this->per_page );
			
		}
		else {
			$found_posts = $total_pages = 0;
		}
		
		/**
		 * REQUIRED!!!
		 * Save the sorted data to the items property, where can be used by the rest of the class
		 */
		$this->items = apply_filters( 'atum/list_table/items', $posts );
		
		$this->set_pagination_args( array(
			'total_items' => $found_posts,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
			'orderby'     => ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'date',
			'order'       => ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'desc'
		) );
		
	}

	/**
	 * Set views for table filtering and calculate total value counters for pagination
	 *
	 * @since 0.0.2
	 *
	 * @param array $args WP_Query arguments
	 */
	protected function set_views_data( $args ) {

		global $wpdb;

		$this->id_views = array(
			'in_stock'  => [ ],
			'out_stock' => [ ],
			'low_stock' => [ ]
		);

		$this->count_views = array(
			'count_in_stock'  => 0,
			'count_out_stock' => 0,
			'count_low_stock' => 0,
			'count_children'  => 0,
			'count_parent'    => 0
		);

		// Get all the IDs in the two queries with no pagination
		$args['fields']         = 'ids';
		$args['posts_per_page'] = - 1;
		unset( $args['paged'] );

		$all_transient = 'atum_list_table_all_' . Helpers::get_transient_identifier( $args );
		$posts = Helpers::get_transient( $all_transient );

		if ( ! $posts ) {

			global $wp_query;
			$wp_query = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/all_args', $args ) );
			$posts = $wp_query->posts;

			// Save it as a transient to improve the performance
			Helpers::set_transient( $all_transient, $posts );

		}

		$this->count_views['count_all'] = count( $posts );

		$variations = $group_items = '';

		// If it's a search or a product filtering, include only the filtered items to search for children
		$post_in = ( ! empty($args['s']) || ! empty($_REQUEST['product_cat']) || ! empty($_REQUEST['product_type']) || ! empty($_REQUEST['supplier']) ) ? $posts : array();

		foreach($this->taxonomies as $index => $taxonomy) {

			if ( $taxonomy['taxonomy'] == 'product_type' ) {

				if ( in_array('variable', (array) $taxonomy['terms']) ) {

					$variations = $this->get_children( 'variable', $post_in, 'product_variation' );

					// Add the Variations to the posts list
					if ( $variations ) {

						// The Variable products are just containers and don't count for the list views
						$this->count_views['count_children'] += count( $variations );
						$this->count_views['count_parent']   += count( $this->variable_products );
						$this->count_views['count_all']      += ( count( $variations ) - count( $this->variable_products ) );

						$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );

					}

				}

				if ( in_array('grouped', (array) $taxonomy['terms']) ) {

					$group_items = $this->get_children( 'grouped', $post_in );

					// Add the Group Items to the posts list
					if ( $group_items ) {

						// The Grouped products are just containers and don't count for the list views
						$this->count_views['count_children'] += count( $group_items );
						$this->count_views['count_parent']   += count( $this->grouped_products );
						$this->count_views['count_all']      += ( count( $group_items ) - count( $this->grouped_products ) );

						$posts = array_unique( array_merge( array_diff( $posts, $this->grouped_products ), $group_items ) );

					}

				}

				// WC Subscriptions compatibility
				if ( class_exists('\WC_Subscriptions') && in_array('variable_subscription', (array) $taxonomy['terms']) ) {

					$variations = $this->get_children( 'variable_subscription', $post_in, 'product_variation' );

					// Add the Variations to the posts list
					if ( $variations ) {

						// The Variable products are just containers and don't count for the list views
						$this->count_views['count_children'] += count( $variations );
						$this->count_views['count_parent']   += count( $this->variable_products );
						$this->count_views['count_all']      += ( count( $variations ) - count( $this->variable_products ) );

						$posts = array_unique( array_merge( array_diff( $posts, $this->variable_products ), $variations ) );

					}

				}

				do_action('atum/list_table/after_children_count', $taxonomy['terms'], $this);

				break;
			}

		}

		if ( $posts ) {

			$post_types = ($variations) ? array($this->post_type, 'product_variation') : $this->post_type;

			// Products in stock
			$args = array(
				'post_type'      => $post_types,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_stock',
						'value'   => 0,
						'type'    => 'numeric',
						'compare' => '>',
					),
				),
				'post__in'       => $posts
			);

			$in_stock_transient = 'atum_list_table_in_stock_' . Helpers::get_transient_identifier( $args );
			$posts_in_stock = Helpers::get_transient( $in_stock_transient );

			if ( ! $posts_in_stock ) {
				$posts_in_stock = new \WP_Query( apply_filters( 'atum/list_table/set_views_data/in_stock_args', $args ) );
				Helpers::set_transient( $in_stock_transient, $posts_in_stock );
			}

			$this->id_views['in_stock'] = $posts_in_stock->posts;
			$this->count_views['count_in_stock'] = count( $posts_in_stock->posts );

			// As the Group items might be displayed multiple times, we should count them multiple times too
			if ($group_items && ( empty($_REQUEST['product_type']) || $_REQUEST['product_type'] != 'grouped' )) {
				$this->count_views['count_in_stock'] += count( array_intersect($group_items, $posts_in_stock->posts) );
			}

			$this->id_views['out_stock']          = array_diff( $posts, $posts_in_stock->posts );
			$this->count_views['count_out_stock'] = $this->count_views['count_all'] - $this->count_views['count_in_stock'];

			if ( $this->count_views['count_in_stock'] ) {

				$low_stock_transient = 'atum_list_table_low_stock_' . Helpers::get_transient_identifier( $args );
				$result = Helpers::get_transient( $low_stock_transient );

				if ( ! $result ) {

					// Products in LOW stock (compare last seven days average sales per day * re-order days with current stock )
					$str_sales = "(SELECT			   
					    (SELECT MAX(CAST( meta_value AS SIGNED )) AS q FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN('_product_id', '_variation_id') AND order_item_id = `item`.`order_item_id`) AS IDs,
					    CEIL(SUM((SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_qty' AND order_item_id = `item`.`order_item_id`))/7*$this->last_days) AS qty
						FROM `{$wpdb->posts}` AS `order`
						    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `item` ON (`order`.`ID` = `item`.`order_id`)
							INNER JOIN `{$wpdb->postmeta}` AS `order_meta` ON (`order`.ID = `order_meta`.`post_id`)
						WHERE (`order`.`post_type` = 'shop_order'
						    AND `order`.`post_status` IN ('wc-completed', 'wc-processing') AND `item`.`order_item_type` ='line_item'
						    AND `order_meta`.`meta_key` = '_paid_date'
						    AND `order_meta`.`meta_value` >= '" . Helpers::date_format( '-7 days' ) . "')
						GROUP BY IDs) AS sales";

					$low_stock_post_types = ($variations) ? "('product', 'product_variation')" : "('product')";

					$str_states = "(SELECT `{$wpdb->posts}`.`ID`,
						IF( CAST( IFNULL(`sales`.`qty`, 0) AS DECIMAL(10,2) ) <= 
							CAST( IF( LENGTH(`{$wpdb->postmeta}`.`meta_value`) = 0 , 0, `{$wpdb->postmeta}`.`meta_value`) AS DECIMAL(10,2) ), TRUE, FALSE) AS state
						FROM `{$wpdb->posts}`
						    LEFT JOIN `{$wpdb->postmeta}` ON (`{$wpdb->posts}`.`ID` = `{$wpdb->postmeta}`.`post_id`)
						    LEFT JOIN " . $str_sales . " ON (`{$wpdb->posts}`.`ID` = `sales`.`IDs`)
						WHERE (`{$wpdb->postmeta}`.`meta_key` = '_stock'
				            AND `{$wpdb->posts}`.`post_type` IN " . $low_stock_post_types . "
				            AND (`{$wpdb->posts}`.`ID` IN (" . implode( ', ', $posts_in_stock->posts ) . ")) )) AS states";

					$str_sql = apply_filters( 'atum/list_table/set_views_data/low_stock', "SELECT `ID` FROM $str_states WHERE state IS FALSE;" );

					$result = $wpdb->get_results( $str_sql );
					$result = wp_list_pluck( $result, 'ID' );
					Helpers::set_transient( $low_stock_transient, $result );

				}

				$this->id_views['low_stock']          = $result;
				$this->count_views['count_low_stock'] = count( $result );

			}

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
			<table class="wp-list-table atum-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>"
				data-currency-pos="<?php echo get_option( 'woocommerce_currency_pos', 'left' ) ?>">
				
				<thead>
					<?php $this->print_group_columns(); ?>

					<tr class="item-heads">
						<?php $this->print_column_headers(); ?>
					</tr>
				</thead>
				
				<tbody id="the-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
				
				<tfoot>
					<tr>
						<?php $this->print_column_headers( FALSE ); ?>
					</tr>
				</tfoot>
			
			</table>
			
			<input type="hidden" name="atum-column-edits" id="atum-column-edits" value="">
		</div>
		<?php
		
		$this->display_tablenav( 'bottom' );

		// Prepare JS vars
		$vars = array(
			'page'         => isset( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1,
			'perpage'      => $this->per_page,
			'order'        => isset( $this->_pagination_args['order'] ) ? $this->_pagination_args['order'] : '',
			'orderby'      => isset( $this->_pagination_args['orderby'] ) ? $this->_pagination_args['orderby'] : '',
			'nonce'        => wp_create_nonce( 'atum-list-table-nonce' ),
			'ajaxfilter'   => Helpers::get_option( 'enable_ajax_filter', 'yes' ),
			'setValue'     => __( 'Set the %% value', ATUM_TEXT_DOMAIN ),
			'setButton'    => __( 'Set', ATUM_TEXT_DOMAIN ),
			'saveButton'   => __( 'Save Data', ATUM_TEXT_DOMAIN )
		);

		if ($this->first_edit_key) {
			$vars['firstEditKey'] = $this->first_edit_key;
			$vars['important'] = __('Important!', ATUM_TEXT_DOMAIN);
			$vars['preventLossNotice'] = __("To prevent any loss of data, please, hit the blue 'Save Data' button at the top left after completing edits.", ATUM_TEXT_DOMAIN);
			$vars['ok'] = __('OK', ATUM_TEXT_DOMAIN);
		}

		$vars = apply_filters( 'atum/list_table/js_vars',  array_merge($vars, $this->data) );
		wp_localize_script( 'atum-list', 'atumListTable', $vars );
		
		do_action( 'atum/list_table/after_display', $this );
		
	}
	
	/**
	 * Prints the columns that groups the distinct header columns
	 *
	 * @since 0.0.1
	 */
	public function print_group_columns() {
		
		if ( ! empty( $this->group_columns ) ) {
			
			echo '<tr class="group">';
			
			foreach ( $this->group_columns as $group_column ) {
				echo '<th class="' . $group_column['name'] . '" colspan="' . $group_column['colspan'] . '"><span>' . $group_column['title'] . '</span></th>';
			}
			
			echo '</tr>';
			
		}
	}
	
	/**
	 * Generate the table navigation above or below the table
	 * Just the parent function but removing the nonce fields that are not required here
	 *
	 * @since 0.0.1
	 *
	 * @param string $which 'top' or 'bottom' table nav
	 */
	protected function display_tablenav( $which ) {
		
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			
			<?php if ( ! empty( $this->get_bulk_actions() ) ): ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			endif;
			
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			
			<br class="clear"/>
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
		echo $post_type_obj->labels->not_found;
		
		if ( ! empty( $_REQUEST['s'] ) ) {
			printf( __( " with query '%s'", ATUM_TEXT_DOMAIN ), esc_attr( $_REQUEST['s'] ) );
		}
		
	}
	
	/**
	 * Get a list of CSS classes for the WP_List_Table table tag. Deleted 'fixed' from standard function
	 *
	 * @since  0.0.2
	 *
	 * @return array List of CSS classes for the table tag
	 */
	protected function get_table_classes() {
		
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * A wrapper to get the right product ID (or variation ID)
	 *
	 * @since 1.2.1
	 *
	 * @return int
	 */
	protected function get_current_product_id() {

		if ( $this->product->get_type() == 'variation' ) {
			/**
			 * @deprecated
			 * The get_variation_id() method was deprecated in WC 3.0.0
			 * In newer versions the get_id() method always be the variation_id if it's a variation
			 */
			return ( version_compare( WC()->version, '3.0.0', '<' ) == -1 ) ? $this->product->get_variation_id() : $this->product->get_id();
		}

		return $this->product->get_id();

	}
	
	/**
	 * Gets the array needed to print html group columns in the table
	 *
	 * @since 0.0.1
	 *
	 * @param   array $group_members Parameter from __contruct method
	 * @param   array $hidden        hidden columns
	 *
	 * @return  array
	 */
	public function calc_groups( $group_members, $hidden ) {
		
		$response = array();
		
		foreach ( $group_members as $name => $group ) {
			
			$counter = 0;
			
			foreach ( $group['members'] as $member ) {
				if ( ! in_array( $member, $hidden ) ) {
					$counter ++;
				}
			}
			
			// Add the group only if there are columns within
			if ($counter) {
				$response[] = array(
					'name'    => $name,
					'title'   => $group['title'],
					'colspan' => $counter
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
	 * @param $column  string  The column to search to
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
	 * Search products by SKU, Supplier's SKU or ID
	 *
	 * @since 1.2.5
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function product_search( $where ) {

		global $pagenow, $wpdb;

		/**
		 * Changed the WooCommerce's "product_search" filter to allow Ajax requests
		 * @see \\WC_Admin_Post_Types\product_search
		 */
		if (
			! in_array( $pagenow, array('edit.php', 'admin-ajax.php') ) ||
		    ! isset( $_GET['s'], $_GET['action'] ) || strpos( $_GET['action'], ATUM_PREFIX ) === FALSE
		) {
			return $where;
		}

		$search_ids = array();
		$terms      = explode( ',', $_GET['s'] );

		foreach ( $terms as $term ) {

			if ( is_numeric( $term ) ) {
				$search_ids[] = $term;
			}

			// Attempt to get an SKU or Supplier's SKU
			foreach (['sku', 'supplier_sku'] as $meta_key) {

				$sku_to_id = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_parent FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE meta_key='_{$meta_key}' AND meta_value LIKE %s;", '%' . $wpdb->esc_like( wc_clean( $term ) ) . '%' ) );
				$sku_to_id = array_merge( wp_list_pluck( $sku_to_id, 'ID' ), wp_list_pluck( $sku_to_id, 'post_parent' ) );

				if ( sizeof( $sku_to_id ) > 0 ) {
					$search_ids = array_merge( $search_ids, $sku_to_id );
				}

			}

		}

		$search_ids = array_filter( array_unique( array_map( 'absint', $search_ids ) ) );

		if ( sizeof( $search_ids ) > 0 ) {
			$where = str_replace( 'AND (((', "AND ( ({$wpdb->posts}.ID IN (" . implode( ',', $search_ids ) . ")) OR ((", $where );
		}

		return $where;

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
		$this->extra_tablenav( 'top' );
		$extra_tablenav_top = ob_get_clean();
		
		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();
		
		ob_start();
		$this->extra_tablenav( 'bottom' );
		$extra_tablenav_bottom = ob_get_clean();
		
		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();
		
		$response                         = array( 'rows' => $rows );
		$response['pagination']['top']    = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['extra_t_n']['top']     = $extra_tablenav_top;
		$response['extra_t_n']['bottom']  = $extra_tablenav_bottom;
		$response['column_headers']       = $headers;
		
		ob_start();
		$this->views();
		$response['views'] = ob_get_clean();
		
		
		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}
		
		if ( isset( $total_pages ) ) {
			$response['total_pages']      = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}
		
		wp_send_json( $response );
		
	}
	
	/**
	 * Enqueue the required scripts
	 *
	 * @since 0.0.1
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {
			
		wp_register_script( 'mousewheel', ATUM_URL . 'assets/js/vendor/jquery.mousewheel.js', array( 'jquery' ), ATUM_VERSION );
		wp_register_script( 'jscrollpane', ATUM_URL . 'assets/js/vendor/jquery.jscrollpane.min.js', array( 'jquery', 'mousewheel' ), ATUM_VERSION );

		wp_register_style( 'atum-list', ATUM_URL . 'assets/css/atum-list.css', FALSE, ATUM_VERSION );

		if ( isset($this->load_datepicker) && $this->load_datepicker === TRUE ) {
			global $wp_scripts;
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.11.4';
			wp_deregister_style('jquery-ui-style');
			wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/excite-bike/jquery-ui.min.css', array(), $jquery_version );

			wp_enqueue_style('jquery-ui-style');
			wp_enqueue_script('jquery-ui-datepicker');
		}

		$dependencies = array( 'jquery', 'jscrollpane' );

		// If it's the first time the user edits the List Table, load the sweetalert to show the popup
		$first_edit_key = ATUM_PREFIX . "first_edit_$hook";
		if ( ! get_user_meta( get_current_user_id(), $first_edit_key, TRUE ) ) {

			$this->first_edit_key = $first_edit_key;
			wp_register_style( 'sweetalert2', ATUM_URL . 'assets/css/vendor/sweetalert2.min.css', FALSE, ATUM_VERSION );
			wp_register_script( 'sweetalert2', ATUM_URL . 'assets/js/vendor/sweetalert2.min.js', FALSE, ATUM_VERSION );
			Helpers::maybe_es6_promise();

			wp_enqueue_style( 'sweetalert2' );
			wp_enqueue_style( 'sweetalert2' );
			$dependencies[] = 'sweetalert2';

			if ( wp_script_is('es6-promise', 'registered') ) {
				wp_enqueue_script( 'es6-promise' );
			}

		}

		$min = (! ATUM_DEBUG) ? '.min' : '';
		wp_register_script( 'atum-list', ATUM_URL . "assets/js/atum.list$min.js", $dependencies, ATUM_VERSION, TRUE );

		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'atum-list' );
		wp_enqueue_script( 'jscrollpane' );
		wp_enqueue_script( 'atum-list' );
		
	}
	
	/**
	 * Add notice warning if Atum manage stock option isn't enabled
	 *
	 * @since 0.1.0
	 */
	public function add_manage_stock_notice() {
		
		?>
		<div class="notice notice-warning atum-notice notice-management-stock is-dismissible" data-nonce="<?php echo wp_create_nonce( ATUM_PREFIX . 'manage-stock-notice' ) ?>">
			<p class="manage-message">
				<?php printf( __( '%1$s plugin can bulk-enable all your items for stock management at the product level. %1$s will save your original settings if you decide to reverse them later. To do so, go to %1$s > Settings > General, deactivate the &quot;Manage Stock&quot; switch and confirm your action by pressing the &quot;Yes, restore them&quot; button.', ATUM_TEXT_DOMAIN ), strtoupper( ATUM_TEXT_DOMAIN ) ) ?>
				<button type="button" class="add-manage-option button button-primary button-small"><?php _e( "Enable ATUM's Manage Stock option", ATUM_TEXT_DOMAIN ) ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Getter for the table_columns property
	 *
	 * @since 1.2.5
	 *
	 * @return array
	 */
	public function get_table_columns() {
		return $this->table_columns;
	}

	/**
	 * Setter for the table_columns property
	 *
	 * @since 1.2.5
	 *
	 * @param array $table_columns
	 */
	public function set_table_columns( $table_columns ) {
		$this->table_columns = $table_columns;
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
	 * Get all the available children products of the published parent products (Variable and Grouped)
	 *
	 * @since 1.1.1
	 *
	 * @param string $parent_type   The parent product type
	 * @param array  $post_in       Optional. If is a search query, get only the children from the filtered products
	 * @param string $post_type     Optional. The children post type
	 *
	 * @return array|bool
	 */
	protected function get_children( $parent_type, $post_in = array(), $post_type = 'product' ) {

		// Get the published Variables first
		$parent_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $parent_type
				)
			)
		);

		if (! empty($post_in) ) {
			$parent_args['post__in'] = $post_in;
		}

		$parents = new \WP_Query($parent_args);

		if ($parents->found_posts) {

			// Save them to be used when preparing the list query
			if ($parent_type == 'variable') {
				$this->variable_products = array_merge($this->variable_products, $parents->posts);
			}
			else {
				$this->grouped_products = array_merge($this->grouped_products, $parents->posts);
			}

			$children_args = array(
				'post_type'       => $post_type,
				'post_status'     => 'publish',
				'posts_per_page'  => - 1,
				'fields'          => 'ids',
				'post_parent__in' => $parents->posts
			);

			$children = new \WP_Query( apply_filters( 'atum/list_table/get_children_args', $children_args ) );

			if ($children->found_posts) {
				return $children->posts;
			}

		}

		return FALSE;

	}

	/**
	 * Get the parent products from a list of product IDs
	 *
	 * @since 1.1.1
	 *
	 * @param array $product_ids  The array of children product IDs
	 *
	 * @return array
	 */
	protected function get_parents ($product_ids) {

		// Filter the parents of the current values
		$parents = array();
		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);

			// For Variations
			if ( is_a($product, 'WC_Product_Variation') ) {
				$parents[] = $product->get_parent_id();
			}
			// For Group Items (these have the grouped ID as post_parent property)
			else {
				$product_post = get_post( $product_id );

				if ($product_post->post_parent) {
					$parents[] = $product_post->post_parent;
				}
			}
		}

		return array_merge( $product_ids, array_unique($parents) );

	}

}