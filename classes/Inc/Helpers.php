<?php
/**
 * Helper functions
 *
 * @package        Atum
 * @subpackage     Inc
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2022 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumColors;
use Atum\Components\AtumMarketingPopup;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use Atum\Legacy\HelpersLegacyTrait;
use Atum\Models\Products\AtumProductTrait;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Queries\ProductDataQuery;
use Atum\Settings\Settings;
use Atum\Suppliers\Suppliers;
use AtumLevels\Levels\Products\BOMProductSimpleTrait;
use AtumLevels\Levels\Products\BOMProductTrait;
use AtumLevels\Levels\Products\BOMProductVariationTrait;
use Westsworld\TimeAgo;
use Westsworld\TimeAgo\Translations\En;


final class Helpers {

	/**
	 * Get the term ids of a given array of slug terms
	 *
	 * @since 1.4.8
	 *
	 * @param array  $slug_terms
	 * @param string $taxonomy
	 *
	 * @return array term_ids
	 *
	 * @deprecated Will be removed once ATUM only supports the new tables.
	 */
	public static function get_term_ids_by_slug( array $slug_terms, $taxonomy = 'product_type' ) {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL
		$query = $wpdb->prepare( "
			SELECT $wpdb->terms.term_id FROM $wpdb->terms 
            INNER JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
            WHERE $wpdb->term_taxonomy.taxonomy = %s
            AND $wpdb->terms.slug IN ('" . implode( "','", array_map( 'esc_attr', $slug_terms ) ) . "')
        ", $taxonomy );
		// phpcs:enable

		$search_terms_ids = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result           = array();

		// Flat array.
		array_walk_recursive( $search_terms_ids, function ( $v, $k ) use ( &$result ) {
			$result[] = absint( $v );
		} );

		return $result;
	}

	/**
	 * Set the help tab for admin pages
	 *
	 * @since 1.3.0
	 *
	 * @param array  $help_tabs
	 * @param Object $obj
	 */
	public static function add_help_tab( $help_tabs, $obj ) {

		$screen = get_current_screen();

		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab( array_merge( array(
				'id'       => ATUM_PREFIX . get_class( $obj ) . '_help_tabs_' . $help_tab['name'],
				'callback' => array( $obj, 'help_tabs_content' ),
			), $help_tab ) );
		}

		$screen->set_help_sidebar( self::load_view_to_string( 'help-tabs/help-sidebar' ) );

	}

	/**
	 * Converts an associative array to HTML data attributes
	 *
	 * @since 1.4.0
	 *
	 * @param array  $array   The array to convert.
	 * @param string $prefix  Optional. Prefix for the data key names.
	 *
	 * @return string
	 */
	public static function array_to_data( $array, $prefix = '' ) {

		$data_array = array_map( function( $key, $value ) use ( $prefix ) {
			if ( is_array( $value ) ) {
				return "data-{$prefix}{$key}='" . wp_json_encode( $value ) . "'";
			}
			else {
				return "data-{$prefix}{$key}='$value'";
			}
		}, array_keys( $array ), $array );

		return implode( ' ', $data_array );
	}

	/**
	 * Outputs the add-on to append/prepend to ATUM fields
	 *
	 * @since 1.4.1
	 *
	 * @param string $side
	 */
	public static function atum_field_input_addon( $side = 'prepend' ) {

		?>
		<span class="input-group-<?php echo esc_attr( $side ) ?>" title="<?php esc_attr_e( 'ATUM field', ATUM_TEXT_DOMAIN ) ?>">
			<span class="input-group-text">
				<img src="<?php echo esc_url( ATUM_URL ) ?>assets/images/atum-icon.svg" alt="">
			</span>
		</span>
		<?php

	}

	/**
	 * Outputs a label-style span to be able to identify ATUM fields (where the append/prepend logo above isn't applicable)
	 *
	 * @since 1.9.18
	 */
	public static function atum_field_label() {
		?>
		<span style="background: #00B8DB;color: white;padding: 0 5px;border-radius: 3px;display: inline-block;margin-right: 3px;font-size: 11px;vertical-align: middle">ATUM</span>
		<?php
	}

	/**
	 * Decode a JSON object stringified
	 *
	 * @since 0.0.3
	 *
	 * @param string $string   The string to decode.
	 * @param bool   $as_array If return an associative array or an object.
	 *
	 * @return array|object|bool
	 */
	public static function decode_json_string( $string, $as_array = TRUE ) {

		return json_decode( str_replace( "'", '"', stripslashes( $string ) ), $as_array );
	}
	
	/**
	 * Prepare HTML data attributes
	 *
	 * @since  0.0.1
	 *
	 * @param mixed  $att          The data attribute name (for as single data att) or an associative array for multiple atts.
	 * @param string $value        The data attribute value. Optional for multiple atts (will be get from the $att array).
	 * @param string $quote_symbol Sometimes the quote symbol must be a single quote to allow json encoded values.
	 *
	 * @return string
	 */
	public static function get_data_att( $att, $value = '', $quote_symbol = '"' ) {
		
		$data_att = '';

		if ( is_array( $att ) ) {
			foreach ( $att as $name => $value ) {
				// Recursive calls.
				$data_att .= self::get_data_att( $name, $value, $quote_symbol );
			}
		}
		else {
			$data_att = ' data-' . $att . '=' . $quote_symbol . $value . $quote_symbol;
		}
		
		return $data_att;
		
	}
	
	/**
	 * Get a formatted HTML attribute string or an empty string if has an empty value
	 *
	 * @since 0.0.2
	 *
	 * @param string     $att   The attribute name.
	 * @param string|int $value The attribute value.
	 * @param bool       $force Force the attribute output without checking if it's empty.
	 *
	 * @return string
	 */
	protected function get_att( $att, $value, $force = FALSE ) {
		
		if ( ! empty( $value ) || $force ) {
			return ' ' . $att . '="' . $value . '"';
		}
		
		return '';
	}

	/**
	 * Get a list of all the products used for calculating stats
	 *
	 * @since 1.4.1
	 *
	 * @param array $args
	 * @param bool  $remove_variables
	 *
	 * @return array
	 */
	public static function get_all_products( $args = array(), $remove_variables = FALSE ) {

		$defaults = array(
			'post_type'      => 'product',
			'post_status'    => Globals::get_queryable_product_statuses(),
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		);

		$transient_name = $remove_variables ? 'all_products_no_variables' : 'all_products';
		$args           = (array) apply_filters( 'atum/get_all_products/args', array_merge( $defaults, $args ) );

		$product_ids_transient = AtumCache::get_transient_key( $transient_name, $args );
		$products              = AtumCache::get_transient( $product_ids_transient );

		if ( ! $products ) {

			$products = get_posts( $args );
			
			if ( $remove_variables ) {

				$args = (array) array_merge( $args, array(
					'post_type' => 'product_variation',
					'post__in'  => $products,
					'fields'    => 'id=>parent',
				) );
				
				$variables = array_unique( get_posts( $args ) );
				$products  = array_diff( $products, $variables );
			
			}

			AtumCache::set_transient( $product_ids_transient, $products, HOUR_IN_SECONDS );

		}

		return $products;

	}

	/**
	 * Get the right ATUM Product class when instantiating a WC product
	 *
	 * @since 1.5.0
	 *
	 * @param string $product_type
	 *
	 * @return string
	 */
	public static function get_atum_product_class( $product_type ) {

		$namespace    = '\Atum\Models\Products';
		$product_type = self::sanitize_psr4_class_name( $product_type );
		$class_name   = "$namespace\AtumProduct{$product_type}";

		if ( class_exists( $class_name ) ) {
			return $class_name;
		}

		// As fallback, return the simple product class.
		return "$namespace\AtumProductSimple";

	}

	/**
	 * Formats a class name following PSR-4 naming conventions
	 *
	 * @since 1.5.1
	 *
	 * @param string $class_name
	 *
	 * @return string
	 */
	public static function sanitize_psr4_class_name( $class_name ) {
		return str_replace( array( '_', '-' ), '', ucwords( $class_name, ' _-' ) );
	}
	
	/**
	 * Returns an array with the orders filtered by the atts array
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $atts {
	 *      Optional. Filters for the orders' query.
	 *
	 *      @type array|string  $type              Order post type(s).
	 *      @type array|string  $status            Order status(es).
	 *      @type array         $orders_in         Array of order's IDs we want to get.
	 *      @type int           $number            Max number of orders (-1 gets all).
	 *      @type string        $meta_key          Key of the meta field to filter/order (depending of orderby value).
	 *      @type mixed         $meta_value        Value of the meta field to filter/order(depending of orderby value).
	 *      @type string        $meta_type         Meta key type. Default value is 'CHAR'.
	 *      @type string        $meta_compare      Operator to test the meta value when filtering (See possible values: https://codex.wordpress.org/Class_Reference/WP_Meta_Query ).
	 *      @type string        $order             ASC/DESC, default to DESC.
	 *      @type string        $orderby           Field used to sort results (see WP_QUERY). Default to date (post_date).
	 *      @type int           $date_start        If has value, filters the orders between this and the $order_date_end (must be a string format convertible with strtotime).
	 *      @type int           $date_end          Requires $date_start. If has value, filters the orders completed/processed before this date (must be a string format convertible with strtotime). Default: Now.
	 *      @type string        $fields            If empty will return all the order posts. For returning only IDs the value must be 'ids'.
	 * }
	 *
	 * @return \WC_Order|array
	 */
	public static function get_orders( $atts = array() ) {

		$atts = (array) apply_filters( 'atum/get_orders/params', wp_parse_args( $atts, array(
			'type'         => 'shop_order',
			'status'       => '',
			'orders_in'    => '',
			'number'       => - 1,
			'meta_key'     => '',
			'meta_value'   => '',
			'meta_type'    => '',
			'meta_compare' => '',
			'order'        => '',
			'orderby'      => '',
			'date_start'   => '',
			'date_end'     => '',
			'fields'       => '',
		) ) );

		$cache_key = AtumCache::get_cache_key( 'orders', $atts );
		$orders    = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $orders;
		}

		/**
		 * Extract params
		 *
		 * @var array|string  $type
		 * @var array|string  $status
		 * @var array|string  $orders_in
		 * @var int           $number
		 * @var string        $meta_key
		 * @var mixed         $meta_value
		 * @var string        $meta_type
		 * @var string        $meta_compare
		 * @var string        $order
		 * @var string        $orderby
		 * @var string        $date_start
		 * @var string        $date_end
		 * @var string        $fields
		 */
		extract( $atts );
		
		// WP_Query arguments.
		$args = array(
			'offset' => 0,
		);
		
		// Post Type.
		$wc_order_types    = wc_get_order_types();
		$order_types       = (array) $type;
		$valid_order_types = array();
		
		// Validate order types.
		foreach ( $order_types as $ot ) {
			if ( in_array( $ot, $wc_order_types ) ) {
				$valid_order_types[] = $ot;
			}
		}
		
		$args['post_type'] = $valid_order_types;
		
		// Order Status.
		$valid_order_statuses = array();
		$wc_order_statuses    = array_keys( wc_get_order_statuses() );
		$order_statuses       = (array) $status;
		
		// Validate post statuses.
		foreach ( $order_statuses as $os ) {
			if ( in_array( $os, $wc_order_statuses ) ) {
				$valid_order_statuses[] = $os;
			}
		}
		
		$args['post_status'] = ! empty( $valid_order_statuses ) ? $valid_order_statuses : $wc_order_statuses;
		
		// Selected posts.
		if ( $orders_in ) {
			
			if ( ! is_array( $orders_in ) ) {
				$orders_in = explode( ',', $orders_in );
			}
			
			$args['post__in'] = array_map( 'absint', $orders_in );
		}
		
		$args['posts_per_page'] = intval( $number );
		
		// Filter/Order by meta key.
		if ( $meta_key ) {
			
			$meta_query = array(
				'key' => esc_attr( $meta_key ),
			);
			
			$meta_type = strtoupper( esc_attr( $meta_type ) );

			if ( in_array( $meta_type, [ 'NUMERIC', 'DECIMAL' ] ) ) {
				$meta_query['value'] = floatval( $meta_value );
				$meta_query['type']  = $meta_type;
			}
			else {
				$meta_query['value'] = esc_attr( $meta_value );
				
				if ( $meta_type ) {
					$meta_query['type'] = $meta_type;
				}
			}
			
			if ( ! empty( $meta_compare ) ) {
				$args['compare'] = esc_attr( $meta_compare );
			}
			
			$args['meta_query'][] = $meta_query;
			
		}
		
		if ( ! empty( $order ) ) {
			$args['order'] = in_array( $order, [ 'ASC', 'DESC' ] ) ? $order : 'DESC';
		}
		
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = esc_attr( $orderby );
		}

		// Filter by date.
		if ( $date_start ) {

			$args['date_query'][] = array(
				'after'     => $date_start,
				'before'    => $date_end ?: 'now',
				'inclusive' => TRUE,
			);
			
		}
		
		// Return only ID's.
		if ( $fields ) {
			$args['fields'] = $fields;
		}
		
		$orders = array();
		$query  = new \WP_Query( $args );
		
		if ( $query->post_count > 0 ) {
			
			if ( $fields ) {
				$orders = $query->posts;
			}
			else {
				foreach ( $query->posts as $post ) {
					// We need the WooCommerce order, not the post.
					$orders[] = new \WC_Order( $post->ID );
				}
			}
			
		}

		AtumCache::set_cache( $cache_key, $orders );
		
		return $orders;
		
	}

	/**
	 * Get the items' sales since $date_start or between $date_start and $date_end
	 *
	 * @since 1.2.3
	 *
	 * @param int       $date_start       The GMT date from when to start the items' sales calculations (must be a string format convertible with strtotime).
	 * @param int       $date_end         Optional. The max GMT date to calculate the items' sales (must be a string format convertible with strtotime).
	 * @param array|int $items            Optional. Array of Product IDs (or single ID) we want to calculate sales from.
	 * @param array     $colums           Optional. Which columns to return from DB. Possible values: "qty", "total" and "prod_id".
	 * @param bool      $use_lookup_table Optional. Whether to use the WC order product lookup tables (if available).
	 *
	 * @return array|int|float
	 */
	public static function get_sold_last_days( $date_start, $date_end = NULL, $items = NULL, $colums = [ 'qty' ], $use_lookup_table = TRUE ) {

		$items_sold = array();

		// Avoid duplicated queries in List Tables by using cache.
		// NOTE: As the dates may change between calls to this function (mainly the seconds or minutes), to ensure we use the cache and not calculate again, we must reformat them.
		$date_start_cache = self::validate_mysql_date( $date_start ) ? self::date_format( $date_start, FALSE, TRUE, 'Y-m-d H' ) : $date_start;
		$date_end_cache   = self::validate_mysql_date( $date_end ) ? self::date_format( $date_end, FALSE, TRUE, 'Y-m-d H' ) : $date_end;
		$cache_key        = AtumCache::get_cache_key( 'get_sold_last_days', [ $date_start_cache, $date_end_cache, $items, $colums ] );
		$sold_last_days   = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $sold_last_days;
		}

		if ( ! empty( $colums ) ) {

			global $wpdb;

			// Prepare the SQL query to get the orders in the specified time window.
			$date_start = self::date_format( strtotime( $date_start ), TRUE, TRUE );
			$date_where = $wpdb->prepare( 'WHERE post_date_gmt >= %s', $date_start );

			if ( $date_end ) {
				$date_end    = self::date_format( strtotime( $date_end ), TRUE, TRUE );
				$date_where .= $wpdb->prepare( ' AND post_date_gmt <= %s', $date_end );
			}

			$order_status = (array) apply_filters( 'atum/get_sold_last_days/orders_status', [
				'wc-processing',
				'wc-completed',
			] );

			$format = implode( ', ', array_fill( 0, count( $order_status ), '%s' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$orders_query = $wpdb->prepare( "
    			SELECT ID FROM $wpdb->posts  
				$date_where
				AND post_type = 'shop_order' AND post_status IN ($format)
			", $order_status );
			// phpcs:enable

			// The lookup tables must be enabled and also available in the actual system.
			// NOTE: There are some scenarios when the lookup tables are still not updated and we must use regular tables.
			// Like when a new order is placed (because WC delays the lookup table update a little).
			$use_lookup_table           = $use_lookup_table && self::maybe_use_wc_order_product_lookup_table();
			$query_columns              = $query_joins = [];
			$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';

			// Filter by product IDs.
			$products_where      = '';
			$products_type_where = "AND `mt_id`.`meta_key` = '_product_id'";
			if ( ! empty( $items ) ) {

				$items = apply_filters( 'atum/get_sold_last_days/product_ids', $items );

				if ( is_array( $items ) ) {

					if ( $use_lookup_table ) {
						$products_where = 'AND (`opl`.`product_id` IN (' . implode( ',', $items ) . ') OR `opl`.`variation_id` IN (' . implode( ',', $items ) . '))';
					}
					else {
						$products_where = 'AND `mt_id`.`meta_value` IN (' . implode( ',', $items ) . ')';
					}

				}
				else {

					if ( $use_lookup_table ) {
						$products_where = "AND (`opl`.`product_id` = $items OR `opl`.`variation_id` = $items)";
					}
					else {
						$products_where = "AND `mt_id`.`meta_value` = $items";
					}

				}
				$products_type_where = "AND `mt_id`.`meta_key` IN ('_product_id', '_variation_id')";
			}
			// Get the product ID column too.
			elseif ( in_array( 'prod_id', $colums ) && ! $use_lookup_table ) {
				$products_where = ' AND `mt_id`.`meta_value` > 0';
			}

			if ( in_array( 'qty', $colums ) ) {

				if ( $use_lookup_table ) {
					$query_columns[] = 'SUM(`opl`.`product_qty`) AS `QTY`';
				}
				else {
					$query_columns[] = 'SUM(`mt_qty`.`meta_value`) AS `QTY`';
					$query_joins[]   = "LEFT JOIN `$wpdb->order_itemmeta` AS `mt_qty` ON (`items`.`order_item_id` = `mt_qty`.`order_item_id` AND `mt_qty`.`meta_key` = '_qty')";
				}

			}

			if ( in_array( 'total', $colums ) ) {

				if ( $use_lookup_table ) {
					$query_columns[] = 'SUM(`opl`.`product_net_revenue`) AS `TOTAL`';
				}
				else {
					$query_columns[] = 'SUM(`mt_total`.`meta_value`) AS `TOTAL`';
					$query_joins[]   = "LEFT JOIN `$wpdb->order_itemmeta` AS `mt_total` ON (`items`.`order_item_id` = `mt_total`.`order_item_id` AND `mt_total`.`meta_key` = '_line_total')";
				}

			}

			if ( in_array( 'prod_id', $colums ) ) {

				if ( $use_lookup_table ) {
					$query_columns[] = '( IF( MAX(`opl`.`variation_id`) > 0, MAX(`opl`.`variation_id`), MAX(`opl`.`product_id`) ) ) AS `PROD_ID`';
				}
				else {
					$query_columns[] = 'MAX( CAST(`mt_id`.`meta_value` AS SIGNED) ) AS `PROD_ID`';
				}

			}

			$query_columns_str = implode( ', ', $query_columns );
			$query_joins_str   = implode( "\n", $query_joins );

			if ( $use_lookup_table ) {

				$query = "
					SELECT $query_columns_str
					FROM `$wpdb->posts` AS `orders`
				    LEFT JOIN `{$wpdb->prefix}woocommerce_order_items` AS `items` ON (`orders`.`ID` = `items`.`order_id`)		
				    LEFT JOIN `$order_product_lookup_table` opl ON `items`.`order_item_id` = `opl`.`order_item_id`	    
			        $query_joins_str
					WHERE `orders`.`ID` IN ($orders_query)
			        $products_where
					GROUP BY `opl`.`variation_id`, `opl`.`product_id`
					HAVING (`QTY` IS NOT NULL);
				";

			}
			else {

				$query = "
					SELECT $query_columns_str
					FROM `$wpdb->posts` AS `orders`
				    LEFT JOIN `{$wpdb->prefix}woocommerce_order_items` AS `items` ON (`orders`.`ID` = `items`.`order_id`)
				    LEFT JOIN `$wpdb->order_itemmeta` AS `mt_id` ON (`items`.`order_item_id` = `mt_id`.`order_item_id`)
			        $query_joins_str
					WHERE `orders`.`ID` IN ($orders_query) $products_type_where
			        $products_where
					GROUP BY `mt_id`.`meta_value`
					HAVING (`QTY` IS NOT NULL);
				";

			}

			// For single products.
			if ( ( ! empty( $items ) && ! is_array( $items ) ) || ( is_array( $items ) && count( $items ) === 1 ) ) {

				// When only 1 single result is requested.
				if ( count( $colums ) === 1 ) {
					$items_sold = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
				// Multiple results requested.
				else {
					$items_sold = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

			}
			// For multiple products.
			else {

				// When only 1 single result for each product is requested.
				if ( count( $colums ) === 1 ) {
					$items_sold = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
				// Multiple results requested for each product.
				else {
					$items_sold = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

			}

		}

		AtumCache::set_cache( $cache_key, $items_sold );

		return $items_sold;

	}

	/**
	 * Get the lost sales of a specified product during the last days
	 *
	 * @since 1.2.3
	 *
	 * @param int|\WC_Product $product          The product ID or product object to calculate the lost sales.
	 * @param int             $days             Optional. By default the calculation is made for 7 days average.
	 * @param bool            $use_lookup_table Optional. Whether to use the WC order lookup table on the get_sold_last_days method.
	 *
	 * @return bool|float       Returns the lost sales or FALSE if never had lost sales
	 */
	public static function get_product_lost_sales( $product, $days = 7, $use_lookup_table = TRUE ) {

		$lost_sales = FALSE;

		if ( ! $product instanceof \WC_Product ) {
			$product = self::get_atum_product( $product );
		}

		// Prevent error if no product is set.
		if ( ! $product ) {
			return FALSE;
		}

		$out_of_stock_date = $product->get_out_stock_date();

		if ( $out_of_stock_date && $days > 0 ) {

			$days_out_of_stock = self::get_product_out_stock_days( $product );

			if ( is_numeric( $days_out_of_stock ) ) {

				// Get the average sales for the past days when in stock.
				$days           = absint( $days );
				$sold_last_days = self::get_sold_last_days( "$out_of_stock_date -$days days", $out_of_stock_date, $product->get_id(), [ 'qty' ], $use_lookup_table );
				$lost_sales     = 0;

				if ( $sold_last_days > 0 ) {

					$average_sales = $sold_last_days / $days;
					$price         = floatval( $product->get_regular_price() );

					$lost_sales = $days_out_of_stock * $average_sales * $price;

				}
			}

		}

		return $lost_sales;

	}

	/**
	 * Get the number of days that a product was "Out of Stock"
	 *
	 * @since 1.2.3
	 *
	 * @param int|\WC_Product $product The product ID or product object.
	 *
	 * @return bool|null  Returns the number of days or NULL if is not "Out of Stock".
	 */
	public static function get_product_out_stock_days( $product ) {

		$out_stock_days = NULL;

		if ( ! $product instanceof \WC_Product ) {
			$product = self::get_atum_product( $product );
		}

		// Prevent error if no product is set.
		if ( ! $product ) {
			return NULL;
		}

		// Check if the current product has the "Out of stock" date recorded.
		$out_stock_date = $product->get_out_stock_date();

		if ( $out_stock_date ) {
			
			try {
				$out_date_time = new \DateTime( $out_stock_date );
				$now_date_time = new \DateTime( 'now' );
				$interval      = date_diff( $out_date_time, $now_date_time );

				$out_stock_days = $interval->days;

			} catch ( \Exception $e ) {
				error_log( __METHOD__ . ' || Product: ' . $product->get_id() . ' || ' . $e->getMessage() );
				return $out_stock_days;
			}
			
		}

		return $out_stock_days;

	}

	/**
	 * Get the Inventory Log item quantity for a specific type of log
	 *
	 * @since 1.2.4
	 *
	 * @param string      $log_type     Type of log.
	 * @param \WC_Product $product      Product to check.
	 * @param string      $log_status   Optional. Log status (completed or pending).
	 * @param bool        $force        Optional. Force to retrieve the data from db.
	 *
	 * @return int|float
	 */
	public static function get_log_item_qty( $log_type, &$product, $log_status = 'pending', $force = FALSE ) {

		$log_types   = Log::get_log_type_columns();
		$column_name = isset( $log_types[ $log_type ] ) ? $log_types[ $log_type ] : '';

		if ( ! $force && $column_name && is_callable( array( $product, "get_$column_name" ) ) ) {
			$qty = call_user_func( array( $product, "get_$column_name" ) );
		}

		if ( ! isset( $qty ) || is_null( $qty ) ) {

			$cache_key = AtumCache::get_cache_key( 'log_item_qty', [ $product->get_id(), $log_type, $log_status ] );
			$qty       = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

			if ( ! $has_cache || $force ) {

				$log_ids = self::get_logs( $log_type, $log_status );

				if ( ! empty( $log_ids ) ) {

					global $wpdb;

					// Get the sum of quantities for the specified product in the logs of that type.
					// phpcs:disable WordPress.DB.PreparedSQL
					$query = $wpdb->prepare( "
						SELECT SUM(meta_value) 				  
					 	FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " om
		                LEFT JOIN $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' oi ON om.order_item_id = oi.order_item_id
					    WHERE order_id IN (' . implode( ',', $log_ids ) . ") AND order_item_type = 'line_item' 
					    AND meta_key = '_qty' AND om.order_item_id IN (
					        SELECT order_item_id FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEM_META_TABLE . " 
						    WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value = %d
						)",
						$product->get_id()
					);
					// phpcs:enable

					$qty = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				}
				else {
					$qty = 0;
				}

				// Save it for future quicker access.
				if ( $column_name && is_callable( array( $product, "set_$column_name" ) ) ) {
					call_user_func( array( $product, "set_$column_name" ), $qty );

					// If it's a variation, sum up all the variations' log types and save the result to the variable (so it can be used in SC sortings).
					AtumCalculatedProps::maybe_update_variable_calc_prop( $product, $column_name, $qty );
				}

			}

			AtumCache::set_cache( $cache_key, $qty );

		}

		return floatval( $qty );

	}

	/**
	 * Helper function to return a plugin option value.
	 * If no value has been saved, it returns $default.
	 * Needed because options are saved as serialized strings.
	 *
	 * @since   0.0.2
	 *
	 * @param string $name    The option key to retrieve.
	 * @param mixed  $default Optional. The default value returned if the option was not found.
	 * @param bool   $echo    Optional. If the option has to be returned or printed.
	 * @param bool   $force   Optional. Whether to get the option from db instead of using the cached value.
	 *
	 * @return mixed
	 */
	public static function get_option( $name, $default = FALSE, $echo = FALSE, $force = FALSE ) {

		// Save it as a global variable to not get the value each time.
		global $atum_global_options;

		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites.
		$atum_global_options = empty( $atum_global_options ) || $force ? get_option( Settings::OPTION_NAME ) : $atum_global_options;
		$option              = isset( $atum_global_options[ $name ] ) ? $atum_global_options[ $name ] : $default;

		if ( $echo ) {
			echo apply_filters( "atum/print_option/$name", $option ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			return FALSE;
		}

		return apply_filters( "atum/get_option/$name", $option );

	}

	/**
	 * Helper function to return the entire plugin option value.
	 * If no option has been saved, it returns empty array.
	 *
	 * @since   0.0.2
	 *
	 * @return array
	 */
	public static function get_options() {

		// Save it as a global variable to not get the value each time.
		global $atum_global_options;

		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites.
		$atum_global_options = empty( $atum_global_options ) ? get_option( Settings::OPTION_NAME ) : $atum_global_options;

		if ( ! $atum_global_options ) {
			$atum_global_options = array();
		}

		return apply_filters( 'atum/get_options', $atum_global_options );

	}

	/**
	 * Update an individual option from ATUM Settings
	 *
	 * @since 1.8.2
	 *
	 * @param string $option
	 * @param mixed  $value
	 */
	public static function update_atum_setting( $option, $value ) {

		global $atum_global_options;

		$atum_settings            = self::get_options();
		$atum_settings[ $option ] = $value;
		$atum_global_options      = $atum_settings;

		update_option( Settings::OPTION_NAME, $atum_settings );

	}

	/**
	 * Get a setting for the specified product
	 * First checks if has the product has a specific value for that setting, if the value isn't NULL, returns that value,
	 * but if it's set to NULL, returns the ATUM's global setting for it.
	 *
	 * NOTE: The global setting MUST HAVE the same name as the individual setting but starting with the keyword "default".
	 *
	 * @since   1.4.18
	 * @version 1.1
	 *
	 * @param \WC_Product|AtumProductTrait $product      The ATUM product object.
	 * @param string                       $prop_name    The prop name.
	 * @param mixed                        $default      The default value for the global option.
	 * @param string                       $prefix       Optional. The ATUM add-ons should use a prefix for their settings.
	 * @param bool                         $allow_global Optional. If FALSE, only can return meta value or default. If TRUE, it could return 'global'.
	 *
	 * @return mixed
	 */
	public static function get_product_prop( $product, $prop_name, $default, $prefix = '', $allow_global = FALSE ) {

		$prop_value = NULL;

		if ( is_callable( array( $product, "get_$prop_name" ) ) ) {
			$prop_value = call_user_func( array( $product, "get_$prop_name" ) );
		}

		// If has no value saved, get the default.
		if ( ! $prop_value ) {
			
			$option_name = "default_$prop_name";
			
			if ( ! empty( $prefix ) ) {
				
				if ( '_' !== substr( $prefix, -1, 1 ) ) {
					$prefix .= '_';
				}
				
				$option_name = $prefix . $option_name;
				
			}

			$prop_value = ! $allow_global ? self::get_option( $option_name, $default ) : 'global';
			
		}
		
		return $prop_value;

	}

	/**
	 * Get sold_last_days address var if set and valid, or the sales_last_ndays options/ Settings::DEFAULT_SALE_DAYS if set
	 *
	 * @since 1.4.11
	 *
	 * @return int days between 1 and 31
	 */
	public static function get_sold_last_days_option() {

		return absint( self::get_option( 'sales_last_ndays', Settings::DEFAULT_SALE_DAYS ) );

	}

	/**
	 * If the site is not using the new tables, use the legacy methods
	 *
	 * @since 1.5.0
	 * @deprecated Only for backwards compatibility and will be removed in a future version.
	 */
	use HelpersLegacyTrait;

	/**
	 * Get an array of products that are not managed by WC
	 *
	 * @since 1.4.1
	 *
	 * @param array $post_types
	 * @param bool  $get_stock_status   Whether to get also the WC stock_status of the unmanaged products.
	 *
	 * @return array
	 */
	public static function get_unmanaged_products( $post_types, $get_stock_status = FALSE ) {

		/**
		 * If the site is not using the new tables, use the legacy method
		 *
		 * @since 1.5.0
		 * @deprecated Only for backwards compatibility and will be removed in a future version.
		 */
		if ( ! self::is_using_new_wc_tables() ) {
			return self::get_unmanaged_products_legacy( $post_types, $get_stock_status );
		}
		
		global $wpdb;

		$unmng_fields = array( 'posts.ID' );
		
		$unmng_join = array(
			"LEFT JOIN $wpdb->postmeta AS mt1 ON (posts.ID = mt1.post_id AND mt1.meta_key = '_manage_stock')",
			"LEFT JOIN {$wpdb->prefix}wc_products wpd ON (posts.ID = wpd.product_id)",
		);
		
		$post_statuses = Globals::get_queryable_product_statuses();
		
		if ( $get_stock_status ) {
			$unmng_fields[] = 'wpd.stock_status';
		}

		$unmng_join = (array) apply_filters( 'atum/get_unmanaged_products/join_query', $unmng_join );
		
		// Exclude the inheritable products from query (as are just containers in ATUM List Tables).
		$excluded_types = Globals::get_inheritable_product_types();

		$unmng_where = array(
			"WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')",
			"AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')",
			"AND (mt1.post_id IS NULL OR mt1.meta_value = 'no')",
			"AND wpd.type NOT IN ('" . implode( "','", $excluded_types ) . "')",
		);
		
		$unmng_where = (array) apply_filters( 'atum/get_unmanaged_products/where_query', $unmng_where );
		
		$sql = 'SELECT DISTINCT ' . implode( ',', $unmng_fields ) . "\n FROM $wpdb->posts posts \n" . implode( "\n", $unmng_join ) . "\n" . implode( "\n", $unmng_where );
		
		return $wpdb->get_results( $sql, ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		
	}
	
	
	/**
	 * Get the price formatted with no HTML tags
	 *
	 * @since 1.2.3
	 *
	 * @param float $price  The price number to format.
	 * @param array $args   The format configuration array.
	 *
	 * @return string
	 */
	public static function format_price( $price, $args = array() ) {

		// Do not add zeros as decimals.
		if ( ! empty( $args['trim_zeros'] ) && TRUE === $args['trim_zeros'] ) {
			add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
		}

		$price = apply_filters( 'atum/format_price', wp_strip_all_tags( wc_price( round( $price, 2 ), $args ) ) );

		remove_filter( 'woocommerce_price_trim_zeros', '__return_true' );

		return $price;

	}
	
	/**
	 * Display the template for the given view
	 *
	 * @since 0.0.2
	 *
	 * @param string $view                  View file that should be loaded.
	 * @param array  $args                  Optional. Variables that will be passed to the view.
	 * @param bool   $allow_theme_override  Optional. Allow overriding views from the theme.
	 *
	 * @return void
	 */
	public static function load_view( $view, $args = [], $allow_theme_override = TRUE ) {
		
		$file = apply_filters( "atum/load_view/$view", $view, $args );
		$args = apply_filters( "atum/load_view_args/$view", $args );
		
		// Whether or not .php was added.
		if ( '.php' !== substr( $file, - 4 ) ) {
			$file .= '.php';
		}

		if ( $allow_theme_override ) {
			$file = self::locate_template( [ $file ], $file );
		}

		// Allow using full paths as view name.
		if ( is_file( $file ) ) {
			$file_path = $file;
		}
		else {

			$file_path = ATUM_PATH . "views/$file";

			if ( ! is_file( $file_path ) ) {
				return;
			}

		}
		
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		if ( ATUM_DEBUG ) {
			/* @noinspection PhpIncludeInspection */
			include $file_path;
		}
		else {
			/* @noinspection PhpIncludeInspection */
			@include $file_path; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		
	}
	
	/**
	 * Get the template for the given view and return it as string
	 *
	 * @since 0.0.1
	 *
	 * @param string $view                  View file that should be loaded.
	 * @param array  $args                  Optional. Variables that will be passed to the view.
	 * @param bool   $allow_theme_override  Optional. Allow overriding views from the theme.
	 *
	 * @return string View template
	 */
	public static function load_view_to_string( $view, $args = [], $allow_theme_override = TRUE ) {
		
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @since 1.3.3
	 *
	 * @param array  $possibilities
	 * @param string $default
	 *
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {

		$possibilities = apply_filters( 'atum/locate_template/possibilities', $possibilities );

		// Check if the theme has an override for the template.
		$theme_overrides = array();

		foreach ( $possibilities as $p ) {

			// Get rid of the plugin's path from any template view.
			$p = str_replace( trailingslashit( WP_PLUGIN_DIR ), '', $p );

			if ( substr( $p, 0, 1 ) !== DIRECTORY_SEPARATOR ) {
				$p = DIRECTORY_SEPARATOR . $p;
			}

			$theme_overrides[] = Globals::TEMPLATE_DIR . $p;

		}

		$found = locate_template( $theme_overrides, FALSE );

		if ( $found ) {
			return $found;
		}

		// Check for it within the views directory.
		foreach ( $possibilities as $p ) {

			if ( substr( $p, 0, 1 ) !== DIRECTORY_SEPARATOR ) {
				$p = DIRECTORY_SEPARATOR . $p;
			}

			$p = ATUM_PATH . "views{$p}";

			if ( file_exists( $p ) ) {
				return $p;
			}

		}

		// No template found.
		return $default;

	}

	/**
	 * Checks if ATUM is managing the WC stock for a specific product
	 *
	 * @since 1.4.1
	 *
	 * @param int|\WC_Product $product
	 *
	 * @return bool
	 */
	public static function is_atum_controlling_stock( $product ) {
		return 'yes' === self::get_atum_control_status( $product );
	}

	/**
	 * Checks whether the product type passed is an inheritable type
	 *
	 * @since 1.4.1
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function is_inheritable_type( $type ) {
		return in_array( $type, Globals::get_inheritable_product_types() );
	}

	/**
	 * Checks whether the product type passed is a child type
	 *
	 * @since 1.4.1
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function is_child_type( $type ) {
		return in_array( $type, Globals::get_child_product_types() );
	}

	/**
	 * Gets the ATUM control switch status for the specified product
	 *
	 * @since 1.4.1
	 *
	 * @param int|\WC_Product $product
	 *
	 * @return string|bool  yes if On of FALSE if Off
	 */
	public static function get_atum_control_status( $product ) {

		if ( ! $product instanceof \WC_Product ) {
			$product = self::get_atum_product( $product );
		}

		return $product->get_atum_controlled();

	}

	/**
	 * Updates the ATUM control switch for the specified product
	 *
	 * @since 1.4.1
	 *
	 * @param int|\WC_Product $product  The product ID or product object.
	 * @param string          $status   Optional. Can be 'enable' or 'disable'.
	 */
	public static function update_atum_control( $product, $status = 'enable' ) {

		if ( ! $product instanceof \WC_Product ) {
			$product = self::get_atum_product( $product );
		}

		$is_inheritable_product = ! $product->get_parent_id() && self::is_inheritable_type( $product->get_type() );

		// The ATUM's stock control must be always 'yes' for inheritable products.
		if ( $is_inheritable_product ) {
			$status = 'enable';
		}

		$product->set_atum_controlled( ( 'enable' === $status ? 'yes' : 'no' ) );
		$product->save_atum_data();
	}

	/**
	 * Updates the WC's manage stock for the specified product
	 *
	 * @since 1.4.5
	 *
	 * @param int|\WC_Product $product  The product ID or product object.
	 * @param string          $status   Optional. Can be 'enable' or 'disable'.
	 */
	public static function update_wc_manage_stock( $product, $status = 'enable' ) {

		if ( ! $product instanceof \WC_Product ) {
			$product = wc_get_product( $product ); // We don't need to use the ATUM models here.
		}

		$product->set_manage_stock( ( 'enable' === $status ? 'yes' : 'no' ) );
		$product->save();

	}

	/**
	 * Change the value of a meta key for all products at once
	 *
	 * @since 1.4.5
	 *
	 * @param string $meta_key       The meta key name.
	 * @param string $status         'yes' (enable) or 'no' (disable).
	 * @param bool   $return_message Whether to return a message or just terminate the process.
	 *
	 * @return void|string Only will return a string with the message if the 3rd param is true.
	 */
	public static function change_status_meta( $meta_key, $status, $return_message = FALSE ) {

		global $wpdb;
		$wpdb->hide_errors();

		$insert_success = $update_success = $stock_success = NULL;

		if ( Globals::ATUM_CONTROL_STOCK_KEY === $meta_key ) {

			$meta_value      = 'yes' === $status ? 1 : 0;
			$atum_data_table = $wpdb->prefix . Globals::ATUM_PRODUCT_DATA_TABLE;

			// phpcs:disable WordPress.DB.PreparedSQL
			$update_success = $wpdb->query( $wpdb->prepare( "
				UPDATE $atum_data_table SET atum_controlled = %d		        		
            	WHERE atum_controlled != %d",
				$meta_value,
				$meta_value
			) );
			// phpcs:enable

			// Get product still not inserted.
			// phpcs:disable WordPress.DB.PreparedSQL
			$update_success_2 = $wpdb->query( "
				INSERT INTO $atum_data_table (product_id, atum_controlled) SELECT p.ID, $meta_value
				FROM $wpdb->posts p
				LEFT JOIN (SELECT * FROM $atum_data_table) ada ON p.ID = ada.product_id
				WHERE p.post_type IN('product', 'product_variation') AND ada.product_id IS NULL
			" );
			// phpcs:enable

			$update_success = FALSE !== $update_success && FALSE !== $update_success_2;

		}
		else {

			// If there are products without the manage_stock meta key, insert it for them.
			$insert_success = $wpdb->query( $wpdb->prepare( "
				INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
				SELECT DISTINCT posts.ID, %s, %s FROM $wpdb->posts AS posts
	            LEFT JOIN $wpdb->postmeta AS pm ON posts.ID = pm.post_id
	            WHERE posts.post_type IN ('product', 'product_variation')
	            AND posts.ID NOT IN (
	                SELECT DISTINCT post_id FROM $wpdb->postmeta
	                WHERE meta_key = %s
	            )",
				$meta_key,
				$status,
				$meta_key
			) );

			// For the rest, just update those that don't have the right status.
			$update_success = $wpdb->query( $wpdb->prepare( "
				UPDATE $wpdb->postmeta SET meta_value = %s		        		
	            WHERE meta_key = %s AND meta_value != %s",
				$status,
				$meta_key,
				$status
			) );

			// Ensure there is no _stock set to 0 for managed products.
			if ( '_manage_stock' === $meta_key && 'yes' === $status ) {

				if ( self::is_using_new_wc_tables() ) {

					$stock_success = $wpdb->query( "
						UPDATE {$wpdb->prefix}wc_products SET stock_quantity = 0
		                WHERE stock_quantity IS NULL
		                AND product_id IN (
		                    SELECT DISTINCT post_id FROM (SELECT post_id FROM $wpdb->postmeta) AS pm
		                    WHERE meta_key = '_manage_stock' AND meta_value = 'yes'
		                )
		            " ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}
				else {

					$stock_success = $wpdb->query( "
						UPDATE $wpdb->postmeta SET meta_value = '0'
		                WHERE meta_key = '_stock'
		                AND post_id IN (
		                    SELECT DISTINCT post_id FROM (SELECT ms.post_id FROM $wpdb->postmeta ms
		                    	LEFT JOIN $wpdb->postmeta sq ON ms.post_id = sq.post_id AND sq.meta_key = '_stock' 
		                    WHERE ms.meta_key = '_manage_stock' AND ms.meta_value = 'yes' AND sq.meta_value IS NULL ) pm
		                )
		            " ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				}

				if ( $stock_success ) {
					wc_update_product_lookup_tables_column( 'stock_quantity' );
				}

			}

		}

		do_action( 'atum/after_change_status_meta', $meta_key, $status );

		// If all goes fine, die with the message, if not, just do nothing (the next method will display the error).
		if ( FALSE !== $insert_success && FALSE !== $update_success && FALSE !== $stock_success ) {

			$mesage = __( 'All your products were updated successfully', ATUM_TEXT_DOMAIN );

			if ( ! $return_message ) {
				wp_send_json_success( $mesage );
			}
			else {
				return $mesage;
			}

		}
		elseif ( $return_message ) {
			return __( 'Something failed while updating your products', ATUM_TEXT_DOMAIN );
		}

	}

	/**
	 * Check whether a specific plugin is installed
	 *
	 * @since 1.2.0
	 *
	 * @param string $plugin        The plugin name/slug.
	 * @param string $folder        The plugin folder.
	 * @param string $by            Optional. It can be checked by 'slug' or by 'name'.
	 * @param bool   $return_bool   Optional. May return a boolean (true/false) or an associative array with the plugin data.
	 *
	 * @return bool|array
	 */
	public static function is_plugin_installed( $plugin, $folder = '', $by = 'slug', $return_bool = TRUE ) {

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {

			// Get the plugin slug from its path.
			$installed_plugin_key = 'slug' === $by ? explode( DIRECTORY_SEPARATOR, $plugin_file )[0] : $plugin_data['Title'];

			if ( in_array( strtolower( $installed_plugin_key ), array_map( 'strtolower', [ $plugin, $folder ] ) ) ) {
				return $return_bool ? TRUE : array( $plugin_file => $plugin_data );
			}
		}

		return FALSE;

	}
	
	/**
	 * Check whether or not register the ES6 promise polyfill
	 * This is only required for SweetAlert2 on IE<12
	 *
	 * @since 1.2.0
	 *
	 * @deprecated IE11 support was removed in WP 5.8 (https://make.wordpress.org/core/handbook/best-practices/browser-support/)
	 */
	public static function maybe_es6_promise() {
		
		global $is_IE;
		// ES6 Polyfill (only for IE<12). Required by SweetAlert2.
		if ( $is_IE ) {
			wp_register_script( 'es6-promise', 'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.js', [], ATUM_VERSION, TRUE );
		}
	}

	/**
	 * Trim inputs and arrays
	 *
	 * @since 1.2.4
	 *
	 * @param string|array $value value(s) to trim.
	 *
	 * @return mixed
	 */
	public static function trim_input( $value ) {

		if ( is_object( $value ) ) {
			return $value;
		}

		if ( is_array( $value ) ) {

			$return = array();

			foreach ( $value as $k => $v ) {

				if ( is_object( $v ) ) {
					$return[ $k ] = $v;
					continue;
				}

				$return[ $k ] = is_array( $v ) ? self::trim_input( $v ) : trim( $v );

			}

			return $return;

		}

		return trim( $value );

	}

	/**
	 * Builds a product type dowpdown for List Table filtering
	 *
	 * @since 1.2.5
	 *
	 * @param string $selected  The pre-selected option.
	 * @param string $class     The dropdown class name.
	 *
	 * @return string
	 */
	public static function product_types_dropdown( $selected = '', $class = 'dropdown_product_type' ) {

		$terms = get_terms( array(
			'taxonomy'   => 'product_type',
			'hide_empty' => FALSE,
		) );

		$allowed_types = apply_filters( 'atum/product_types_dropdown/allowed_types', Globals::get_product_types() );

		$output  = '<select name="product_type" class="' . $class . ' atum-tooltip" autocomplete="off">';
		$output .= '<option value=""' . selected( $selected, '', FALSE ) . '>' . __( 'All product types...', ATUM_TEXT_DOMAIN ) . '</option>';

		foreach ( $terms as $term ) {

			if ( ! in_array( $term->slug, $allowed_types ) ) {
				continue;
			}

			$output .= '<option value="' . sanitize_title( $term->name ) . '"' . selected( $term->slug, $selected, FALSE ) . '>';

			switch ( $term->name ) {
				case 'grouped':
					$output .= __( 'Grouped product', ATUM_TEXT_DOMAIN );
					break;

				case 'variable':
					$output .= __( 'Variable product', ATUM_TEXT_DOMAIN );
					break;

				case 'simple':
					$output .= __( 'Simple product', ATUM_TEXT_DOMAIN );
					break;

				// Assuming that we'll have other types in future.
				default:
					$output .= ucfirst( $term->name );
					break;
			}

			$output .= '</option>';

			if ( 'simple' === $term->name ) {
				$output .= '<option value="downloadable"' . selected( 'downloadable', $selected, FALSE ) . '> &rarr; ' . __( 'Downloadable', ATUM_TEXT_DOMAIN ) . '</option>';
				$output .= '<option value="virtual"' . selected( 'virtual', $selected, FALSE ) . '> &rarr; ' . __( 'Virtual', ATUM_TEXT_DOMAIN ) . '</option>';
			}
		}

		$output .= '</select>';

		return $output;

	}

	/**
	 * Builds a suppliers dowpdown for List Table filtering
	 *
	 * @since 1.3.1
	 *
	 * @param array $args {
	 *  Array of arguments.
	 *
	 *  @type string $selected    Optional. The pre-selected option.
	 *  @type bool   $enhanced    Optional. Whether to show an enhanced select.
	 *  @type string $class       Optional. The dropdown class name.
	 *  @type string $name        Optional. The input's name.
	 *  @type string $placeholder Optional: The select's placeholder.
	 * }
	 *
	 * @return string
	 */
	public static function suppliers_dropdown( $args ) {

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return '';
		}

		$default_args = array(
			'selected'    => '',
			'enhanced'    => FALSE,
			'class'       => 'dropdown_supplier',
			'name'        => 'supplier',
			'placeholder' => __( 'All suppliers...', ATUM_TEXT_DOMAIN ),
		);

		/**
		 * Variables definition
		 *
		 * @var string $selected
		 * @var bool   $enhanced
		 * @var string $class
		 * @var string $name
		 * @var string $placeholder
		 */
		extract( wp_parse_args( $args, $default_args ) );

		ob_start();

		if ( ! $enhanced ) :

			$args = array(
				'post_type'      => Suppliers::POST_TYPE,
				'posts_per_page' => - 1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);

			$suppliers = get_posts( $args );

			if ( empty( $suppliers ) ) :
				ob_end_flush();
				return '';
			endif;
			?>

			<select name="<?php echo esc_attr( $name ) ?>" class="wc-enhanced-select atum-enhanced-select atum-tooltip auto-filter <?php echo esc_attr( $class ) ?>" id="supplier" autocomplete="off" style="width: 165px">
				<option value=""<?php selected( $selected, '' ) ?>><?php esc_attr_e( 'Show all suppliers', ATUM_TEXT_DOMAIN ) ?></option>

				<?php foreach ( $suppliers as $supplier ) : ?>
					<option value="<?php echo esc_attr( $supplier->ID ) ?>"<?php selected( $supplier->ID, $selected ) ?>><?php echo esc_attr( $supplier->post_title ?: __( '(no title)', ATUM_TEXT_DOMAIN ) ) ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>

			<select class="wc-product-search atum-enhanced-select atum-tooltip auto-filter <?php echo esc_attr( $class ) ?>" id="supplier" name="supplier" data-allow_clear="true"
				data-action="atum_json_search_suppliers" data-placeholder="<?php echo esc_attr( $placeholder ) ?>"
				data-multiple="false" data-selected="" data-minimum_input_length="1" style="width: 165px">
				<?php if ( $selected ) :
					$supplier = get_post( $selected ); ?>
					<option value="<?php echo esc_attr( $selected ) ?>" selected="selected"><?php echo esc_attr( $supplier->post_title ?: __( '(no title)', ATUM_TEXT_DOMAIN ) ) ?></option>
				<?php endif; ?>
			</select>

		<?php endif;

		return ob_get_clean();

	}

	/**
	 * Get the inventory log's IDs
	 *
	 * @since 1.2.8
	 *
	 * @param string $type      The log type to query. Values: 'reserved-stock', 'customer-returns', 'warehouse-damage', 'lost-in-post', 'other'.
	 * @param string $status    Optional. The log status. Values: 'pending', 'completed'.
	 *
	 * @return array|bool
	 */
	public static function get_logs( $type, $status = '' ) {

		$cache_key = AtumCache::get_cache_key( 'get_logs', [ $type, $status ] );
		$logs      = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// Filter by log type meta key.
			$log_types = Log::get_log_types();

			if ( ! in_array( $type, array_keys( $log_types ) ) ) {
				return FALSE;
			}

			$args = array(
				'post_type'      => InventoryLogs::POST_TYPE,
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => '_type',
						'value' => $type,
					),
				),
			);

			// Filter by log status.
			if ( $status ) {

				if ( FALSE === strpos( $status, ATUM_PREFIX ) ) {
					$status = ATUM_PREFIX . $status;
				}

				$args['post_status'] = $status;

			}
			else {
				$args['post_status'] = 'any';
			}

			$logs = get_posts( apply_filters( 'atum/get_logs_args', $args ) );
			AtumCache::set_cache( $cache_key, $logs );

		}

		return $logs;

	}

	/**
	 * Get all the order items within a specified ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param int $order_id   The ATUM Order ID.
	 *
	 * @return object|null
	 */
	public static function get_order_items( $order_id ) {

		global $wpdb;
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' WHERE order_id = %d ORDER BY order_item_id', $order_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Get the appropriate ATUM Order model
	 *
	 * @since 1.2.9
	 *
	 * @param int    $atum_order_id The order item ID.
	 * @param bool   $read_items    NOTE: it's important to not read items when not necessary to improve performance.
	 * @param string $post_type     Optional. The ATUM order post type. If not passed will get the post type from the passed order ID.
	 *
	 * @return AtumOrderModel|\WP_Error
	 */
	public static function get_atum_order_model( $atum_order_id, $read_items, $post_type = '' ) {

		if ( ! $post_type ) {
			$post_type = get_post_type( $atum_order_id );
		}

		$model_class = NULL;

		switch ( $post_type ) {
			case InventoryLogs::POST_TYPE:
				$model_class = '\Atum\InventoryLogs\Models\Log';
				break;

			case PurchaseOrders::POST_TYPE:
				$model_class = '\Atum\PurchaseOrders\Models\PurchaseOrder';
				break;
		}

		$model_class = apply_filters( 'atum/order_model_class', $model_class, $post_type );

		if ( ! $model_class || ! class_exists( $model_class ) ) {
			return new \WP_Error( 'invalid_post_type', __( 'No valid ID provided', ATUM_TEXT_DOMAIN ) );
		}

		return new $model_class( $atum_order_id, $read_items );

	}

	/**
	 * Get the appropriate ATUM Order model object from the ATUM order item id
	 *
	 * @since 1.6.6
	 *
	 * @param int $atum_order_item_id
	 *
	 * @return AtumOrderModel|\WP_Error
	 */
	public static function get_atum_order_model_from_item_id( $atum_order_item_id ) {

		global $wpdb;
		$atum_order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' WHERE order_item_id = %d', $atum_order_item_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return self::get_atum_order_model( $atum_order_id, TRUE );
	}

	/**
	 * Get the inbound stock amount for the specified product
	 *
	 * @since 1.5.4
	 *
	 * @param \WC_Product|AtumProductTrait $product  The product to check.
	 * @param bool                         $force    Optional. Whether to force the recalculation from db.
	 *
	 * @return int|float
	 */
	public static function get_product_inbound_stock( &$product, $force = FALSE ) {

		$product_id    = $product->get_id();
		$cache_key     = AtumCache::get_cache_key( 'product_inbound_stock', $product_id );
		$inbound_stock = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache || $force ) {

			// Check if the inbound stock is already saved on the ATUM product table.
			$inbound_stock = $product->get_inbound_stock();

			if ( is_null( $inbound_stock ) || $force ) {

				// Get all the valid statuses.
				$due_statuses = PurchaseOrders::get_due_statuses();

				// Calculate the inbound stock from pending purchase orders.
				global $wpdb;

				$joins = array(
					"LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim ON oi.`order_item_id` = oim.`order_item_id`",
					"LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim2 ON oi.`order_item_id` = oim2.`order_item_id`",
					"LEFT JOIN `$wpdb->posts` AS p ON oi.`order_id` = p.`ID`",
				);

				$where = array(
					"oim.`meta_key` IN ('_product_id', '_variation_id')",
					"oi.`order_item_type` = 'line_item'",
					$wpdb->prepare( 'p.`post_type` = %s', PurchaseOrders::POST_TYPE ),
					$wpdb->prepare( 'oim.`meta_value` = %d', $product_id ),
					"`post_status` IN ('" . implode( "','", $due_statuses ) . "')",
					"oim2.`meta_key` = '_qty'",
				);

				$select_str = apply_filters( 'atum/product_inbound_stock/sql_select', 'SUM(oim2.`meta_value`) AS quantity', $product );
				$joins_str  = implode( "\n", apply_filters( 'atum/product_inbound_stock/sql_joins', $joins, $product ) );
				$where_str  = implode( ' AND ', apply_filters( 'atum/product_inbound_stock/sql_where', $where, $product ) );

				$sql = "
					SELECT  $select_str			
					FROM `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
					$joins_str								
					WHERE $where_str			
					GROUP BY oi.`order_id`;
				";

				$result        = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$inbound_stock = $result ? array_sum( $result ) : 0;

				// Save it for future quicker access.
				$product->set_inbound_stock( $inbound_stock );
				$product->set_sales_update_date();

				// If it's a variation, sum up all the variations' inbound stocks and save the result as the variable inbound (so it can be used in SC sortings).
				AtumCalculatedProps::maybe_update_variable_calc_prop( $product, 'inbound_stock', $inbound_stock );

			}

			AtumCache::set_cache( $cache_key, $inbound_stock );

		}

		return $inbound_stock;

	}

	/**
	 * Get the stock on hold amount (orders that have been paid but are not marked as completed yet) for the specified product
	 *
	 * @since 1.5.8
	 *
	 * @param \WC_Product|AtumProductTrait $product
	 * @param bool                         $force
	 *
	 * @return int|float
	 */
	public static function get_product_stock_on_hold( &$product, $force = FALSE ) {

		$cache_key     = AtumCache::get_cache_key( 'product_stock_on_hold', $product->get_id() );
		$stock_on_hold = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache || $force ) {

			// Check if the inbound stock is already saved on the ATUM product table.
			$stock_on_hold = $product->get_stock_on_hold();

			if ( is_null( $stock_on_hold ) || $force ) {

				global $wpdb;

				if ( self::is_child_type( $product->get_type() ) ) {

					$product_id_key = '_variation_id';
					$post_type      = 'product_variation';
				}
				else {

					$product_id_key = '_product_id';
					$post_type      = 'product';

				}

				$product_ids = apply_filters( 'atum/product_calc_stock_on_hold/product_ids', $product->get_id(), $post_type );
				$product_sql = is_array( $product_ids ) ? 'IN (' . implode( ',', $product_ids ) . ')' : "= $product_ids";

				// phpcs:disable
				$sql = $wpdb->prepare( "
					SELECT SUM(omq.meta_value) AS qty 
					FROM {$wpdb->prefix}woocommerce_order_items oi			
					LEFT JOIN $wpdb->order_itemmeta omq ON omq.`order_item_id` = oi.`order_item_id`
					LEFT JOIN $wpdb->order_itemmeta omp ON omp.`order_item_id` = oi.`order_item_id`			  
					WHERE order_id IN (
						SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_status IN ('wc-processing', 'wc-on-hold')
					)
					AND omq.meta_key = '_qty' AND order_item_type = 'line_item' AND omp.meta_key = %s AND omp.meta_value $product_sql ;",
					$product_id_key
				);
				// phpcs:enable

				$stock_on_hold = wc_stock_amount( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				// Save it for future quicker access.
				$product->set_stock_on_hold( $stock_on_hold );

				// If it's a variation, sum up all the variations' stocks on hold and save the result to the variable (so it can be used in SC sortings).
				AtumCalculatedProps::maybe_update_variable_calc_prop( $product, 'stock_on_hold', $stock_on_hold );

			}

			AtumCache::set_cache( $cache_key, $stock_on_hold );

		}

		return $stock_on_hold;

	}

	/**
	 * Check whether WooCommerce is using the new tables
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public static function is_using_new_wc_tables() {
		return class_exists( '\WC_Product_Data_Store_Custom_Table' );
	}
	
	/**
	 * Get the appropriate ATUM Order statuses depending on the post_type
	 *
	 * @since 1.5.0
	 *
	 * @param string $post_type
	 * @param bool   $remove_finished Whether to remove or not the finished status.
	 *
	 * @return array
	 */
	public static function get_atum_order_post_type_statuses( $post_type, $remove_finished = FALSE ) {
		
		// TODO: Modify to allow options to add simple "get_atum_order_post_type_statuses_simple" functionality and prefix.
		$statuses = [];
		
		switch ( $post_type ) {
			case InventoryLogs::POST_TYPE:
				$post_type_class = '\Atum\InventoryLogs\InventoryLogs';
				break;
			
			case PurchaseOrders::POST_TYPE:
				$post_type_class = '\Atum\PurchaseOrders\PurchaseOrders';
				break;
		}
		
		if ( ! empty( $post_type_class ) && class_exists( $post_type_class ) ) {
			$statuses = call_user_func( array( $post_type_class, 'get_statuses' ) );
			
			if ( $remove_finished ) {
				unset( $statuses[ constant( $post_type_class . '::FINISHED' ) ] );
			}
			
		}
		
		return apply_filters( 'atum/order_post_type/statuses', $statuses, $post_type );
		
	}
	
	
	/**
	 * Get a WooCommerce product using the ATUM's product data models
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $the_product Post object or post ID of the product.
	 * @param bool  $use_cache   Whether to use the ATUM cache or not.
	 *
	 * @return \WC_Product|AtumProductTrait|BOMProductTrait|BOMProductVariationTrait|BOMProductSimpleTrait|null|false
	 */
	public static function get_atum_product( $the_product = FALSE, $use_cache = FALSE ) {

		// No need to obtain the product again if what is coming is already an ATUM product.
		if ( self::is_atum_product( $the_product ) ) {
			return $the_product;
		}

		$use_cache = apply_filters( 'atum/get_atum_product/use_cache', $use_cache, $the_product );
		$has_cache = FALSE;
		$product   = FALSE;

		if ( $use_cache ) {
			$product_id = $the_product instanceof \WC_Product ? $the_product->get_id() : $the_product;
			$cache_key  = AtumCache::get_cache_key( 'atum_product', $product_id );
			$product    = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );
		}

		if ( ! $has_cache ) {

			Globals::enable_atum_product_data_models();
			$product = wc_get_product( $the_product );
			Globals::disable_atum_product_data_models();

			if ( $product instanceof \WC_Product && $use_cache ) {
				AtumCache::set_cache( $cache_key, $product );
			}

		}

		return $product;

	}

	/**
	 * Check whether the passed product is an ATUM product object
	 *
	 * @since 1.7.2
	 *
	 * @param \WC_Product|AtumProductTrait|BOMProductTrait $product
	 *
	 * @return bool
	 */
	public static function is_atum_product( $product ) {
		return $product instanceof \WC_Product && is_callable( array( $product, 'get_atum_controlled' ) );
	}
	
	/**
	 * Update product meta from ATUM List tables
	 *
	 * @since 1.3.0
	 *
	 * @param int   $product_id
	 * @param array $product_data
	 * @param bool  $skip_action
	 */
	public static function update_product_data( $product_id, $product_data, $skip_action = FALSE ) {
		
		$product = self::get_atum_product( $product_id );
		
		if ( ! $product || ! $product instanceof \WC_Product ) {
			return;
		}
		
		$product_data = apply_filters( 'atum/product_data', $product_data, $product_id );
		
		foreach ( $product_data as $meta_key => &$meta_value ) {
			
			$meta_key = esc_attr( $meta_key );
			
			switch ( $meta_key ) {
				
				case 'stock':
					$product->set_stock_quantity( $meta_value );
					
					// Needed to clear transients and other stuff.
					do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
					
					break;
				
				case 'regular_price':
					$product->set_regular_price( $meta_value );

					if ( 'regular_price' === $meta_key && ! $product->is_on_sale( 'edit' ) ) {
						$product->set_price( $meta_value );

					}

					if ( class_exists( '\WC_Subscription' ) && in_array( $product->get_type(), [ 'subscription', 'variable-subscription' ] ) ) {
						update_post_meta( $product_id, '_subscription_price', $meta_value );
					}
					
					break;
				
				case 'sale_price':
					$sale_price    = wc_format_decimal( $meta_value );
					$regular_price = $product->get_regular_price();

					// The sale price cannot be higher than the regular price.
					if ( $regular_price >= $sale_price ) {
						$product->set_sale_price( $sale_price );
					}

					// Check for sale dates.
					if ( isset( $product_data['_sale_price_dates_from'], $product_data['_sale_price_dates_to'] ) ) {
						
						$date_from = wc_clean( $product_data['_sale_price_dates_from'] );
						$date_to   = wc_clean( $product_data['_sale_price_dates_to'] );
						$now       = self::get_wc_time( self::get_current_timestamp() );

						$date_from     = $date_from ? self::get_wc_time( $date_from ) : '';
						$date_to       = $date_to ? self::get_wc_time( $date_to ) : '';
						$date_from_str = $date_to_str = '';

						if ( $date_to && ! $date_from ) {
							$date_from = $now;
						}

						// Update price if on sale.
						if ( $product->is_on_sale( 'edit' ) ) {

							$product->set_price( $sale_price );

							if ( $date_to ) {
								$date_from_str = ! empty( $date_from ) ? $date_from->getTimestamp() : '';
								$date_to_str   = ! empty( $date_to ) ? $date_to->getTimestamp() : '';
							}

						}
						else {
							
							$product->set_price( $regular_price );

							if ( ! $date_to || ( ! empty( $date_to ) && 0 < $date_to->diff( $now ) ) ) {
								$date_from_str = ! empty( $date_from ) ? $date_from->getTimestamp() : '';
								$date_to_str   = ! empty( $date_to ) ? $date_to->getTimestamp() : '';
							}
							
						}

						$product->set_date_on_sale_from( $date_from_str );
						$product->set_date_on_sale_to( $date_to_str );

					}
					
					break;
				
				case substr( Globals::PURCHASE_PRICE_KEY, 1 ):
					$product->set_purchase_price( $meta_value );
					break;

				case 'low_stock_threshold':
					$product->set_low_stock_amount( $meta_value );
					break;

				// Any other text meta.
				default:
					// These fields are only needed for WPML compatibility.
					if ( strpos( $meta_key, '_custom' ) !== FALSE || strpos( $meta_key, '_currency' ) !== FALSE ) {
						break;
					}

					if ( is_callable( array( $product, "set_{$meta_key}" ) ) ) {
						call_user_func( array( $product, "set_{$meta_key}" ), $meta_value );
					}
					else {
						update_post_meta( $product_id, '_' . $meta_key, esc_attr( $meta_value ) );
					}

					break;
			}
			
		}
		
		$product->save();

		// Trigger the "after_save_purchase_price" hook is needed if the PL's sync purchase price option is enabled.
		if ( array_key_exists( substr( Globals::PURCHASE_PRICE_KEY, 1 ), $product_data ) ) {
			do_action( 'atum/product_data/after_save_purchase_price', $product_id, $product_data[ substr( Globals::PURCHASE_PRICE_KEY, 1 ) ], NULL );
		}
		
		if ( ! $skip_action ) {
			do_action( 'atum/product_data_updated', $product_id, $product_data );
		}

		// Run all the hooks that are triggered after a product is saved.
		do_action( 'atum/product_data/after_save_data', $product_data, $product );

	}
	
	/**
	 * Return header support buttons info
	 *
	 * @since 1.4.3.3
	 *
	 * @return array
	 */
	public static function get_support_buttons() {
		
		if ( Addons::has_valid_key() ) {
			$support['support_link']        = 'https://stockmanagementlabs.ticksy.com/';
			$support['support_button_text'] = __( 'Get Premium Support', ATUM_TEXT_DOMAIN );
		}
		else {
			$support['support_link']        = 'https://forum.stockmanagementlabs.com/all';
			$support['support_button_text'] = __( 'Get Support', ATUM_TEXT_DOMAIN );
		}
		
		return $support;
		
	}

	/**
	 * Force save with changes to validate_props and rebuild stock_status if required.
	 * We can use it with 1 product/variation or set all to true to apply to all products OUT_STOCK_THRESHOLD_KEY
	 * set and clean or not the OUT_STOCK_THRESHOLD_KEY meta keys
	 *
	 * @since 1.4.10
	 *
	 * @param \WC_Product|AtumProductTrait $product    Optional. The product to rebuild the threshold for.
	 * @param bool                         $clean_meta Optional. Whether to clean the threshold value.
	 * @param bool                         $all        Optional. Whether to apply to all the products that reached the individual threshold.
	 */
	public static function force_rebuild_stock_status( $product = NULL, $clean_meta = FALSE, $all = FALSE ) {

		if ( $product instanceof \WC_Product ) {

			if ( ! apply_filters( 'atum/force_rebuild_stock_status_allowed', TRUE, $product ) ) {
				return;
			}

			if ( $clean_meta ) {
				$product->set_out_stock_threshold( NULL );
			}

			if ( $product->managing_stock() ) {

				// Force a stock quantity change to ensure the stock status is correctly updated.
				$current_stock = $product->get_stock_quantity();
				$product->set_stock_quantity( $current_stock + 1 );
				$product->set_stock_quantity( $current_stock ); // Restore the value.

				// Trigger the "Out of Stock threshold" hooks.
				if ( $product->is_type( 'variation' ) ) {
					do_action( 'woocommerce_variation_set_stock', $product );
				}
				else {
					do_action( 'woocommerce_product_set_stock', $product );
				}

				$product->set_atum_stock_status( $product->get_stock_status() );

			}

			$product->save();

			return;

		}

		// When disabling the "Out of stock threshold", we must restore the stock status to its correct value.
		if ( $all ) {

			global $wpdb;

			$statuses = Globals::get_queryable_product_statuses( FALSE );

			// phpcs:disable WordPress.DB.PreparedSQL
			$ids_to_rebuild_stock_status = $wpdb->get_col( "
                SELECT DISTINCT ID FROM $wpdb->posts p
                LEFT JOIN $wpdb->prefix" . Globals::ATUM_PRODUCT_DATA_TABLE . " ap ON p.ID = ap.product_id
                LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_stock')
                WHERE p.post_status IN ('" . implode( "','", $statuses ) . "')
                AND ap.out_stock_threshold IS NOT NULL
                AND p.post_type IN ('product', 'product_variation');
            " );
			// phpcs:enable

			foreach ( $ids_to_rebuild_stock_status as $id_to_rebuild ) {

				$product = self::get_atum_product( $id_to_rebuild );

				if ( $product instanceof \WC_Product ) {

					// Delete _out_stock_threshold (avoid partial works to be done again).
					if ( $clean_meta ) {
						$product->set_out_stock_threshold( NULL );
					}

					if ( $product->managing_stock() ) {

						// Trigger the "Out of Stock threshold" hooks (when enabled).
						if ( $product->is_type( 'variation' ) ) {
							do_action( 'woocommerce_variation_set_stock', $product );
						}
						else {
							do_action( 'woocommerce_product_set_stock', $product );
						}

					}

					$product->save();

				}

			}

			do_action( 'atum/out_stock_threshold/after_rebuild', $clean_meta );
			
		}

	}

	/**
	 * Checks whether the Out of Stock Threshold at product level is set for any product
	 *
	 * @since 1.4.10
	 *
	 * @return bool
	 */
	public static function is_any_out_stock_threshold_set() {

		global $wpdb;

		$statuses = Globals::get_queryable_product_statuses( FALSE );

		// phpcs:disable WordPress.DB.PreparedSQL
		$row_count = $wpdb->get_var( "
			SELECT COUNT(*) FROM $wpdb->posts p
            LEFT JOIN $wpdb->prefix" . Globals::ATUM_PRODUCT_DATA_TABLE . " ap ON p.ID = ap.product_id    
            WHERE p.post_status IN('" . implode( "','", $statuses ) . "') AND p.post_type IN ('product', 'product_variation')
            AND ap.out_stock_threshold IS NOT NULL;
		" );
		// phpcs:enable

		return $row_count > 0;
	}

	/**
	 * Customize the WP_Query to handle ATUM product data and product data from the new WC tables
	 *
	 * @since 1.5.0
	 *
	 * @param array  $query_data  The query data args.
	 * @param array  $pieces      The pieces array that must be returned to the post_clauses filter.
	 * @param string $table_name  Optional. If passed will use this table name instead of the ATUM product data table.
	 *
	 * @return array
	 */
	public static function product_data_query_clauses( $query_data, $pieces, $table_name = '' ) {
		
		if ( empty( $query_data ) ) {
			return $pieces;
		}
		
		if ( ! empty( $query_data['where'] ) ) {
			$atum_product_data_query = new ProductDataQuery( $query_data );
			$sql                     = $atum_product_data_query->get_sql( $table_name );
			
			foreach ( [ 'join', 'where' ] as $key ) {
				
				if ( ! empty( $sql[ $key ] ) ) {
					$pieces[ $key ] .= ' ' . $sql[ $key ];
				}
				
			}
		}
		
		if ( ! empty( $query_data['order'] ) ) {
			
			global $wpdb;

			$table_name = $table_name ? $table_name : Globals::ATUM_PRODUCT_DATA_TABLE;
			$column     = "{$wpdb->prefix}$table_name.{$query_data['order']['field']}";

			// If it's a numeric column, the NULL values should display at the end.
			if ( 'NUMERIC' === $query_data['order']['type'] ) {
				$compare_value = 'DESC' === strtoupper( $query_data['order']['order'] ) ? ( - 1 * PHP_INT_MAX ) : PHP_INT_MAX;
				$column        = "IFNULL($column, $compare_value)";
			}
			
			$pieces['orderby'] = "$column {$query_data['order']['order']}";

		}
		
		return $pieces;
		
	}

	/**
	 * Return true if value exists in a multiarray
	 * http://codepad.org/GU0qG5su.
	 *
	 * @since 1.4.8
	 *
	 * @param string $key
	 * @param array  $arr
	 *
	 * @return bool
	 */
	public static function in_multi_array( $key, array $arr ) {

		// Is in base array?
		if ( in_array( $key, $arr ) ) {
			return TRUE;
		}

		// Check arrays contained in this array.
		foreach ( $arr as $element ) {
			if ( is_array( $element ) && self::in_multi_array( $key, $element ) ) {
				return TRUE;
			}

		}

		return FALSE;
	}

	/**
	 * Like array_key_exists, but with multiple keys
	 *
	 * @since 1.4.10
	 *
	 * @param array $required array with the required keys.
	 * @param array $data     array to check.
	 *
	 * @return bool
	 */
	public static function array_keys_exist( array $required, array $data ) {

		if ( count( array_intersect_key( array_flip( $required ), $data ) ) === count( $required ) ) {
			// All required keys exist!
			return TRUE;
		}
		else {
			return FALSE;
		}

	}

	/**
	 * Groups an array into arrays by a given key, or set of keys, shared between all array members
	 *
	 * Based on mcaskill's {@link https://gist.github.com/mcaskill/baaee44487653e1afc0d array_group_by()} function.
	 *
	 * @param array $array   The array to have grouping performed on.
	 * @param mixed $key     The key to group or split by. Can be a _string_, an _integer_, a _float_, or a _callable_.
	 *                       If the key is a callback, it must return a valid key from the array.
	 *                       If the key is _NULL_, the iterated element is skipped.
	 *
	 * @return array|null Returns a multidimensional array or `null` if `$key` is invalid.
	 */
	public static function array_group_by( array $array, $key ) {

		if ( ! is_string( $key ) && ! is_int( $key ) && ! is_float( $key ) && ! is_callable( $key ) ) {
			// The key should be a string, an integer, or a callback.
			return NULL;
		}

		$func = ( ! is_string( $key ) && is_callable( $key ) ? $key : NULL );
		$_key = $key;

		// Load the new array, splitting by the target key.
		$grouped = [];
		foreach ( $array as $value ) {
			$key = NULL;

			if ( is_callable( $func ) ) {
				$key = call_user_func( $func, $value );
			}
			elseif ( is_object( $value ) && isset( $value->{$_key} ) ) {
				$key = $value->{$_key};
			}
			elseif ( isset( $value[ $_key ] ) ) {
				$key = $value[ $_key ];
			}

			if ( NULL === $key ) {
				continue;
			}

			$grouped[ $key ][] = $value;
		}

		return $grouped;

	}

	/**
	 * Load all PSR4-compliant classes within the specified path
	 *
	 * @since 1.4.12.3
	 *
	 * @param string $path          The path where are located the classes to load.
	 * @param string $namespace     The Namespace for the classes.
	 * @param bool   $is_singleton  Optional. Whether the classes follows the Singleton pattern.
	 */
	public static function load_psr4_classes( $path, $namespace, $is_singleton = TRUE ) {

		$files = @scandir( $path, 1 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( ! empty( $files ) ) {

			foreach ( $files as $file ) {

				if ( is_file( $path . $file ) ) {

					$class = $namespace . str_replace( '.php', '', $file );

					if ( class_exists( $class ) ) {

						if ( $is_singleton ) {
							/* @noinspection PhpUndefinedMethodInspection */
							$class::get_instance();
						}
						else {
							new $class();
						}

					}
				}

			}

		}

	}
	
	/**
	 * Validate a given string as CSS color. This validation supports alpha channel.
	 *
	 * @since 1.4.13
	 *
	 * @param string $color The color can be transparent, in hexadecimal, RGB or RGBA notation.
	 *
	 * @return bool
	 */
	public static function validate_color( $color ) {
		
		$color = trim( $color );
		
		// Regex match.
		if ( strpos( $color, '#' ) !== FALSE ) {
			return (bool) preg_match( '/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $color );
		}
		elseif ( strpos( $color, 'rgba' ) !== FALSE ) {
			return (bool) preg_match( '/rgba\(\s*(\d+\%?),\s*(\d+\%?),\s*(\d+\%?),\s*(\d*\.?\d*)\s*\)/', $color );
		}
		elseif ( strpos( $color, 'rgb' ) !== FALSE ) {
			return (bool) preg_match( '/rgb\(\s*(\d+\%?),\s*(\d+\%?),\s*(\d+\%?)\s*\)/', $color );
		}
		elseif ( ! $color || 'transparent' === $color ) {
			return TRUE;
		}
		
		return FALSE;
		
	}

	/**
	 * Like "array_unique" function but for multi-dimensional arrays where the specified key must be unique
	 *
	 * @since 1.4.14
	 *
	 * @param array  $array
	 * @param string $key
	 *
	 * @return array
	 */
	public static function unique_multidim_array( $array, $key ) {

		$temp_array = array();
		$i          = 0;
		$key_array  = array();

		foreach ( $array as $val ) {

			if ( ! in_array( $val[ $key ], $key_array ) ) {
				$key_array[ $i ]  = $val[ $key ];
				$temp_array[ $i ] = $val;
			}

			$i++;

		}

		return $temp_array;

	}
	
	/**
	 * Return the step to input stock quantities attending ATUM custom decimals set.
	 *
	 * @since 1.4.18
	 *
	 * @return float|int
	 */
	public static function get_input_step() {

		$stock_decimals = Globals::get_stock_decimals();

		if ( ! $stock_decimals ) {
			return 1;
		}
		
		$step = self::get_option( 'stock_quantity_step' );

		if ( ! is_numeric( $step ) || ! $step ) {
			return 'any';
		}

		$step = $step ?: ( 10 / pow( 10, $stock_decimals + 1 ) );

		// Avoid returning 1 when we should allow stock decimals to avoid HTML5 validation errors.
		return floor( $step ) == $step ? 'any' : $step; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

	}

	/**
	 * Read the type of the parent product (variable) of a child product (variation) from db, caching the result to improve performance
	 *
	 * @since 1.5.0
	 *
	 * @param int $child_id
	 *
	 * @return string   The product type slug
	 */
	public static function read_parent_product_type( $child_id ) {

		$cache_key           = AtumCache::get_cache_key( 'parent_product_type', $child_id );
		$parent_product_type = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			global $wpdb;

			if ( self::is_using_new_wc_tables() ) {

				$parent_product_type = $wpdb->get_var( $wpdb->prepare( "
					SELECT `type` FROM {$wpdb->prefix}wc_products			  
					WHERE product_id IN (
				        SELECT DISTINCT post_parent FROM $wpdb->posts WHERE ID = %d
					)
				", $child_id ) );

			}
			else {

				$parent_product_type = $wpdb->get_var( $wpdb->prepare( "
					SELECT terms.slug FROM $wpdb->posts posts
					LEFT JOIN $wpdb->term_relationships as termrelations ON (posts.ID = termrelations.object_id)
					LEFT JOIN $wpdb->term_taxonomy as taxonomies ON (taxonomies.term_taxonomy_id = termrelations.term_taxonomy_id)
				    LEFT JOIN $wpdb->terms as terms ON (terms.term_id = taxonomies.term_id) 
					WHERE taxonomies.taxonomy = 'product_type' AND posts.ID IN (
				        SELECT DISTINCT post_parent FROM $wpdb->posts WHERE ID = %d
					)
				", $child_id ) );

			}

			if ( $parent_product_type ) {
				AtumCache::set_cache( $cache_key, $parent_product_type );
			}

		}

		return $parent_product_type;

	}

	/**
	 * Get the ATUM meta for the specified user
	 *
	 * @since 1.5.0
	 *
	 * @param string $key       Optional. If passed will return that specific key within the meta array.
	 * @param int    $user_id   Optional. If passed will get the meta for that user, if not will get it from the current user.
	 *
	 * @return mixed
	 */
	public static function get_atum_user_meta( $key = '', $user_id = 0 ) {

		$user_id        = $user_id ?: get_current_user_id();
		$cache_key      = AtumCache::get_cache_key( 'get_atum_user_meta', [ $key, $user_id ] );
		$atum_user_meta = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			$atum_user_meta = get_user_meta( $user_id, Globals::ATUM_USER_META_KEY, TRUE );

			if ( $key && is_array( $atum_user_meta ) && in_array( $key, array_keys( $atum_user_meta ), TRUE ) ) {
				$atum_user_meta = $atum_user_meta[ $key ];
			}

			AtumCache::set_cache( $cache_key, $atum_user_meta );

		}

		return $atum_user_meta;

	}

	/**
	 * Set the ATUM meta for the specified user
	 *
	 * @since 1.5.0
	 *
	 * @param string $key       Set that specific key only and will preserve the others within the ATUM meta array.
	 * @param mixed  $value     The value to set. Should be previously sanitized.
	 * @param int    $user_id   Optional. If passed will set the meta for that user, if not will set it to the current user.
	 */
	public static function set_atum_user_meta( $key, $value, $user_id = 0 ) {

		$user_id        = $user_id ?: get_current_user_id();
		$atum_user_meta = self::get_atum_user_meta();

		if ( ! is_array( $atum_user_meta ) ) {
			$atum_user_meta = array();
		}

		$atum_user_meta[ $key ] = $value;
		update_user_meta( $user_id, Globals::ATUM_USER_META_KEY, $atum_user_meta );

		// Delete any saved user meta after updating its value.
		$cache_key = AtumCache::get_cache_key( 'get_atum_user_meta', [ $key, $user_id ] );
		AtumCache::delete_cache( $cache_key );

	}

	/**
	 * Use our own custom image placeholder for products without image
	 *
	 * @since 1.5.1
	 *
	 * @param string $image
	 * @param string $size
	 * @param array  $dimensions
	 *
	 * @return string
	 */
	public static function image_placeholder( $image = '', $size = '', $dimensions = [] ) {
		return '<span class="thumb-placeholder"><i class="atum-icon atmi-picture"></i></span>';
	}

	/**
	 * Check if it shows the marketing popup.
	 *
	 * @since 1.5.3
	 *
	 * @return bool
	 */
	public static function show_marketing_popup() {

		return self::show_marketing();

	}

	/**
	 * Check if it shows the marketing dashboard.
	 *
	 * @since 1.7.6
	 *
	 * @return bool
	 */
	public static function show_marketing_dashboard() {

		return self::show_marketing( 'dash' );

	}

	/**
	 * Check if it shows the marketing widget at popup or dashboard.
	 *
	 * @since 1.7.6
	 *
	 * @param string $wich
	 *
	 * @return bool
	 */
	private static function show_marketing( $wich = 'popup' ) {

		if ( FALSE === in_array( $wich, array( 'popup', 'dash' ) ) ) {
			return FALSE;
		}

		$marketing_popup = AtumMarketingPopup::get_instance();
		$transient_key   = AtumCache::get_transient( 'atum-marketing-' . $wich, TRUE );

		if ( ! $transient_key || $marketing_popup->get_transient_key() !== $transient_key ) {

			if ( ! $marketing_popup->is_loaded() ) {
				return FALSE;
			}

			$transient_key = $marketing_popup->get_transient_key();
			AtumCache::set_transient( 'atum-marketing-' . $wich, $transient_key, WEEK_IN_SECONDS, TRUE );

		}

		// Get marketing popup user meta.
		$marketing_popup_user_meta = get_user_meta( get_current_user_id(), 'atum-marketing-' . $wich, TRUE );

		if ( $marketing_popup_user_meta && $marketing_popup_user_meta === $transient_key ) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Get the right ATUM icon classes for the specified product's type
	 *
	 * @since 1.5.4
	 *
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public static function get_atum_icon_type( $product ) {

		$product_type = $product->get_type();
		$atum_icon    = 'atum-icon ';

		switch ( $product_type ) {
			case 'product-part':
			case 'raw-material':
				$atum_icon .= "atmi-$product_type";
				break;

			case 'product-part-variation':
				$atum_icon .= 'atmi-variable-product-part';
				break;

			case 'raw-material-variation':
				$atum_icon .= 'atmi-variable-raw-material';
				break;

			case 'variation':
			case 'variable-subscription':
				$atum_icon .= 'atmi-wc-variable';
				break;

			case 'grouped':
			case 'variable':
				$atum_icon .= "atmi-wc-$product_type";
				break;

			case 'booking':
				$atum_icon .= 'atmi-calendar-full';
				break;

			case 'bundle':
				$atum_icon .= 'atmi-bundle';
				break;

			default:
				if ( 'simple' === $product_type ) {

					if ( $product->is_downloadable() ) {
						$atum_icon .= 'atmi-wc-downloadable';
					}
					elseif ( $product->is_virtual() ) {
						$atum_icon .= 'atmi-wc-virtual';
					}
					else {
						$atum_icon .= 'atmi-wc-simple';
					}

				}
				else {
					$atum_icon .= 'atmi-wc-simple';
				}

				break;
		}

		return $atum_icon;

	}

	/**
	 * Get the items for a bundle product
	 *
	 * @since 1.5.6
	 *
	 * @param array $args
	 *
	 * @return array|bool
	 */
	public static function get_bundle_items( $args ) {

		$cache_key = AtumCache::get_cache_key( 'query_bundled_items', $args );
		$children  = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {
			$children = \WC_PB_DB::query_bundled_items( $args );
			AtumCache::set_cache( $cache_key, $children );
		}

		$bundle_items = [];

		// Check if bundle item exist.
		if ( ! empty( $children ) ) {

			foreach ( $children as $item ) {
				$product = self::get_atum_product( $item );
				if ( $product ) {
					array_push( $bundle_items, $item );
				}
			}

		}

		return $bundle_items;

	}

	/**
	 * Checks if the sales props of the passed product were not updated recently and require a new update
	 *
	 * @since 1.5.8
	 *
	 * @param AtumProductTrait $product    The product to check. It must be an ATUM product.
	 * @param string           $time_frame Optional. A time string compatible with strtotime. By default is 1 day in the past.
	 *
	 * @return bool
	 */
	public static function is_product_data_outdated( $product, $time_frame = '-1 day' ) {

		return is_null( $product->get_sales_update_date() ) || strtotime( $product->get_sales_update_date() ) <= strtotime( $time_frame );
	}

	/**
	 * Update the expiring data for the specified product.
	 *
	 * @since 1.5.8
	 *
	 * @return bool
	 */
	public static function is_atum_ajax() {

		return wp_doing_ajax() && ! empty( $_REQUEST['action'] ) && 'atum_' === substr( $_REQUEST['action'], 0, 5 ); // phpcs:ignore WordPress.Security.NonceVerification
	}
	
	/**
	 * Get selected visual mode style
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public static function get_visual_mode_style() {

		$theme       = AtumColors::get_user_theme();
		$atum_colors = AtumColors::get_instance();

		switch ( $theme ) {
			case 'dark_mode':
				return $atum_colors->get_dark_mode_colors();

			case 'hc_mode':
				return $atum_colors->get_high_contrast_mode_colors();

			default:
				return $atum_colors->get_branded_mode_colors();
		}

	}

	/**
	 * Get selected color value
	 *
	 * @since 1.5.9
	 *
	 * @param string $color_name
	 *
	 * @return string
	 */
	public static function get_color_value( $color_name ) {

		return AtumColors::get_user_color( $color_name, 0 );

	}

	/**
	 * Add the inline style for the ATUM colors
	 *
	 * @sine 1.5.9
	 *
	 * @param string $handle  The enqueued stylesheet handle needed to add the extra CSS styles to.
	 */
	public static function enqueue_atum_colors( $handle ) {
		wp_add_inline_style( $handle, self::get_visual_mode_style() );
	}

	/**
	 * Return the classes (product types ) to hide option groups in WC data panels
	 *
	 * @since 1.5.8.3
	 *
	 * @param bool $is_array Whether to return an array or a string.
	 *
	 * @return array|string
	 */
	public static function get_option_group_hidden_classes( $is_array = TRUE ) {

		$classes = [];

		foreach ( Globals::get_incompatible_products() as $product_type ) {

			$classes[] = "hide_if_$product_type";
		}

		return $is_array ? $classes : implode( ' ', $classes );

	}

	/**
	 * Checks if the current request is a WP REST API request.
	 * As we don't have an official coditional tag yet, we have to use our own
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings
	 * Case #3: It can happen that WP_Rewrite is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in subfolders
	 *
	 * @since   1.6.0.2
	 * @returns boolean
	 * @author  matzeeable
	 * @link    https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
	 */
	public static function is_rest_request() {

		$prefix = rest_get_url_prefix();
		if (
			defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
			|| isset( $_GET['rest_route'] ) // (#2)
			&& 0 === strpos( trim( $_GET['rest_route'], '\\/' ), $prefix, 0 )
		) {
			return TRUE;
		}

		// (#3)
		global $wp_rewrite;
		if ( NULL === $wp_rewrite ) {
			$wp_rewrite = new \WP_Rewrite();
		}

		// (#4)
		$rest_url    = wp_parse_url( trailingslashit( rest_url() ) );
		$current_url = wp_parse_url( add_query_arg( [] ) );

		return is_array( $current_url ) && ! empty( $current_url['path'] ) && strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;

	}


	/**
	 * Get if a product is on restock status: There's insufficient stock to fulfill the next "days to reorder" days expected sales.
	 * TODO: Perhaps change the static 7 days sales average by a setting.
	 *
	 * @since 1.6.6
	 *
	 * @param \WC_Product|AtumProductTrait $product
	 * @param bool                         $use_lookup_table
	 *
	 * @return bool
	 */
	public static function is_product_restock_status( $product, $use_lookup_table = TRUE ) {

		// sale_day option means actually Days to reorder.
		$days_to_reorder = absint( self::get_option( 'sale_days', Settings::DEFAULT_SALE_DAYS ) );
		$current_time    = self::date_format( '', TRUE, TRUE );
		$restock_needed  = FALSE;

		if ( $product->managing_stock() && 'instock' === $product->get_stock_status() ) {
			$expected_sales = self::get_sold_last_days( "$current_time -7 days", $current_time, $product->get_id(), [ 'qty' ], $use_lookup_table ) / 7 * $days_to_reorder;
			$restock_needed = $expected_sales > $product->get_stock_quantity();
		}

		return apply_filters( 'atum/is_product_restock_status', $restock_needed, $product );

	}

	/**
	 * Whether to use the wc_order_product_lookup table to improve queries performance
	 *
	 * @since 1.7.1
	 *
	 * @return bool
	 */
	public static function maybe_use_wc_order_product_lookup_table() {

		if ( 'no' === self::get_option( 'use_order_product_lookup_table', 'yes' ) ) {
			return FALSE;
		}

		$transient_key = AtumCache::get_transient_key( 'use_wc_order_product_lookup_table' );
		$use_lookup    = AtumCache::get_transient( $transient_key, TRUE );

		if ( FALSE !== $use_lookup ) {
			return wc_string_to_bool( $use_lookup );
		}

		global $wpdb;

		$order_product_lookup_table = $wpdb->prefix . 'wc_order_product_lookup';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$use_lookup = $wpdb->get_var( "SHOW TABLES LIKE '$order_product_lookup_table';" ) && $wpdb->get_var( "SELECT COUNT(*) FROM $order_product_lookup_table" ) > 0;
		AtumCache::set_transient( $transient_key, wc_bool_to_string( $use_lookup ), WEEK_IN_SECONDS, TRUE );

		return $use_lookup;

	}

	/**
	 * Search for ids in the an order note's data
	 *
	 * @since 1.8.0
	 *
	 * @param array $note_data
	 * @param array $searched_texts
	 *
	 * @return array
	 */
	public static function get_order_note_ids( $note_data, $searched_texts ) {

		global $wpdb;

		$found      = FALSE;
		$return_ids = [];

		if ( empty( $note_data['comment_content'] ) ) {
			return $return_ids;
		}

		foreach ( $searched_texts as $searched_text ) {

			if ( strpos( $note_data['comment_content'], $searched_text ) !== FALSE ) {
				$found = TRUE;
				break;
			}
		}

		if ( $found ) {

			// Try to determine whether the product being processed has calculated stock.
			preg_match_all( '/\((\#*[^()]+)\)/is', $note_data['comment_content'], $ids );

			if ( ! empty( $ids ) ) {

				$ids = $ids[1];

				foreach ( $ids as $id ) {

					if ( 0 === strpos( $id, '#' ) ) {

						// It's the id because begins with #.
						$return_ids[] = intval( substr( $id, 1 ) );
					}
					else {

						// It's as SKU, so get the product with that SKU.
						$id_from_sku = $wpdb->get_var( $wpdb->prepare( "
							SELECT pm.post_id FROM {$wpdb->posts} p
							INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
							WHERE p.post_status <> 'trash' AND p.post_type IN ('product','product_variation')
							AND pm.meta_key = '_sku' AND pm.meta_value = %s
						", $id));

						if ( $id_from_sku ) {
							$return_ids[] = intval( $id_from_sku );
						}

					}

				}

			}

		}

		return $return_ids;
	}

	/**
	 * Get the ATUM logo image placeholder
	 *
	 * @since 1.8.1
	 *
	 * @return string
	 */
	public static function get_atum_image_placeholder() {
		return '<span class="atum-img-placeholder">
			<img src="' . esc_url( ATUM_URL ) . 'assets/images/atum-icon.svg" alt="">
		</span>';
	}

	/**
	 * Get a relative date
	 *
	 * @since 1.8.2
	 *
	 * @param string $date A Valid string date compatible with DateTime.
	 *
	 * @return string
	 */
	public static function get_relative_date( $date ) {

		$lang_code       = ucfirst( get_locale() );
		$short_lang_code = substr( $lang_code, 0, 2 );
		$lang_class      = "\\Westsworld\\TimeAgo\\Translations\\$lang_code";
		$alt_lang_class  = "\\Westsworld\\TimeAgo\\Translations\\$short_lang_code";

		if ( class_exists( $lang_class ) ) {
			$language = new $lang_class();
		}
		elseif ( class_exists( $alt_lang_class ) ) {
			$language = new $alt_lang_class();
		}
		else {
			$language = new En();
		}

		$time_zone = new \DateTimeZone( wp_timezone_string() );
		$time_ago  = new TimeAgo( $language );

		return $time_ago->inWords( new \DateTime( $date, $time_zone ), new \DateTime( 'now', $time_zone ) );

	}

	/**
	 * Get the current UNIX timestamp (as GMT)
	 *
	 * NOTE: When the wp_date function is available and used, the timezone returned with wp_date( 'U' ) is always GMT. So a real UNIX timestamp.
	 * This differs from the old current_time function that returns the timestamp with a timezone applied (if the GMT param not specified).
	 *
	 * @since 1.8.2
	 *
	 * @return false|int|string
	 */
	public static function get_current_timestamp() {

		if ( ! function_exists( 'wp_date' ) ) {
			return current_time( 'timestamp', TRUE ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		}

		return wp_date( 'U' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

	}

	/**
	 * Format a date to match the db format
	 *
	 * @since 0.1.3
	 *
	 * @param string|int $date         Optional. The date to format. Can be an English date or a timestamp (with second param as true).
	 * @param bool       $is_timestamp Optional. Whether the first param is a Unix timesptamp.
	 * @param bool       $gmt_date     Optional. Whether to return a GMT formatted date.
	 * @param string     $format       Optional. A valid PHP date format. By default is 'Y-m-d H:i:s'.
	 *
	 * @return string                   The formatted date
	 */
	public static function date_format( $date = '', $is_timestamp = TRUE, $gmt_date = FALSE, $format = 'Y-m-d H:i:s' ) {

		// If no date is passed, get the current UNIX timestamp.
		if ( ! $date ) {
			$date = self::get_current_timestamp();
		}
		elseif ( ! $is_timestamp ) {
			$date = strtotime( $date );
		}

		return ! $gmt_date ? date_i18n( $format, $date ) : gmdate( $format, $date );

	}

	/**
	 * Validates a mySQL date (Y-m-d H:i:s)
	 *
	 * @since 1.9.20
	 *
	 * @param string $date
	 *
	 * @return bool
	 */
	public static function validate_mysql_date( $date ) {

		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $date, $matches ) ) {
			if ( checkdate( $matches[2], $matches[3], $matches[1] ) ) {
				return TRUE;
			}
		}

		return FALSE;

	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.5.0.3
	 *
	 * @param string|integer|\WC_DateTime $value
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_wc_time( $value ) {

		$date_time = NULL;

		try {

			if ( $value instanceof \WC_DateTime ) {
				$date_time = $value;
			}
			elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$date_time = new \WC_DateTime( "@{$value}", new \DateTimeZone( 'UTC' ) );
			}
			else {

				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				}
				else {
					$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
				}

				$date_time = new \WC_DateTime( "@{$timestamp}", new \DateTimeZone( 'UTC' ) );

			}

			// Set local timezone or offset.
			if ( get_option( 'timezone_string' ) ) {
				$date_time->setTimezone( new \DateTimeZone( wc_timezone_string() ) );
			}
			else {
				$date_time->set_utc_offset( wc_timezone_offset() );
			}

		} catch ( \Exception $e ) {
			error_log( __METHOD__ . '::' . $e->getMessage() );
		}

		return $date_time;

	}

	/**
	 * Return the text used in some places to ask users for rating ATUM
	 *
	 * @since 1.8.3
	 *
	 * @return string
	 */
	public static function get_rating_text() {

		if ( ! get_user_meta( get_current_user_id(), 'atum_admin_footer_text_rated' ) ) {

			$rating_text = '<span>' . esc_html__( 'HELP US TO IMPROVE!', ATUM_TEXT_DOMAIN ) . ' 🙏</span>';

			/* translators: the first one is the WordPress plugins directory link and the second is the link closing tag */
			$rating_text .= sprintf( __( 'If you like <strong>ATUM</strong> please leave us a %1$s&#9733;&#9733;&#9733;&#9733;&#9733;%2$s rating. Huge thanks in advance!', ATUM_TEXT_DOMAIN ), '<a href="https://wordpress.org/support/plugin/atum-stock-manager-for-woocommerce/reviews/?filter=5#new-post" target="_blank" class="wc-rating-link" data-rated="' . esc_attr__( 'Thanks :)', ATUM_TEXT_DOMAIN ) . '">', '</a>' );
			wc_enqueue_js( "
				jQuery( 'a.wc-rating-link' ).click( function() {
					jQuery.post( '" . WC()->ajax_url() . "', { action: 'atum_rated' } );
					jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
				});
			" );

		}
		else {
			$rating_text = __( 'Thank you for trusting <strong>ATUM</strong> to manage your inventory 🙌', ATUM_TEXT_DOMAIN );
		}

		return $rating_text;

	}

	/**
	 * Adds the 'wc-' prefix to an order status if not set.
	 *
	 * @since 1.8.7
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public static function get_raw_wc_order_status( $status ) {

		return 'wc-' . str_replace( 'wc-', '', $status );
	}

	/**
	 * Save order notes additional meta data.
	 *
	 * @since 1.8.8
	 *
	 * @param integer $note_id
	 * @param array   $params
	 */
	public static function save_order_note_meta( $note_id, $params ) {

		$params = apply_filters( 'atum/order_note_meta_params', $params, $note_id );

		if ( ! empty( $params ) && isset( $params['action'] ) ) {

			switch ( $params['action'] ) {
				case 'order_status_set':
					$action = 'setStatus';
					break;
				case 'order_status_change':
					$action = 'changeStatus';
					break;
				case 'ajax_note':
					$action = 'ajaxNote';
					break;
				case 'api_note':
					$action = 'apiNote';
					break;
				case 'increase_stock':
					$action = 'increaseStock';
					break;
				case 'decrease_stock':
					$action = 'decreaseStock';
					break;
				case 'increase_item_stock':
					$action = 'increaseItemStock';
					break;
				case 'added_line_items':
					$action = 'addedLineItems';
					break;
				case 'deleted_adjusted_line':
					$action = 'deletedAdjustedLineItems';
					break;
				case 'deleted_line':
					$action = 'deletedLineItems';
					break;
				case 'stock_levels_increased':
					$action = 'stockLevelsIncreased';
					break;
				case 'product_stock_levels_increased':
					$action = 'stockLevelsProductIncreased';
					break;
				case 'stock_levels_reduced':
					$action = 'stockLevelsReduced';
					break;
				case 'stock_levels_changed':
					$action = 'stockLevelsChanged';
					break;
				case 'unable_restore_inventory':
					$action = 'unableRestoreInventory';
					break;
				case 'unable_restore':
					$action = 'unableRestore';
					break;
				case 'unable_reduce_inventory':
					$action = 'unableReduceInventory';
					break;
				case 'unable_reduce':
					$action = 'unableReduce';
					break;
				case 'unable_increase_inventory_stock':
					$action = 'unableIncreaseInventoryStock';
					break;
				case 'unable_decrease_inventory_stock':
					$action = 'unableDecreaseInventoryStock';
					break;
				case 'unable_increase_stock':
					$action = 'unableIncreaseStock';
					break;
				case 'unable_decrease_stock':
					$action = 'unableDecreaseStock';
					break;
			}

			unset( $params['action'] );

			if ( ! empty( $action ) ) {
				update_comment_meta( $note_id, 'note_type', $action );
			}

			update_comment_meta( $note_id, 'note_params', $params );

		}

	}

	/**
	 * Get the timezone string from the WP settings
	 *
	 * @since 1.9.7
	 *
	 * @return string
	 */
	public static function get_wp_timezone_string() {

		$timezone_string = get_option( 'timezone_string' );

		if ( ! empty( $timezone_string ) ) {
			return $timezone_string;
		}

		$offset  = get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - floor( $offset ) ) * 60;
		$offset  = '-0.5' === $offset ? str_replace( '+', '-', sprintf( '%+03d:%02d', $hours, $minutes ) ) : sprintf( '%+03d:%02d', $hours, $minutes );

		return $offset;
	}

	/**
	 * Returns the UTC time from a given current server timezone time.
	 *
	 * @since 1.9.7
	 *
	 * @param string $time In hh:mm format.
	 *
	 * @return string
	 */
	public static function get_utc_time( $time ) {

		$timezone_string = self::get_wp_timezone_string();
		try {
			$date = new \DateTime( $time, new \DateTimeZone( $timezone_string ) );
			$date->setTimezone( new \DateTimeZone( 'UTC' ) );
		}
		catch ( \Exception $e ) {
			return $time;
		}

		return $date->format( 'H:i' );

	}

	/**
	 * Search product data for a term and return ids.
	 *
	 * @since 1.9.14
	 *
	 * @param string     $term               Search term.
	 * @param string     $type               Type of product.
	 * @param bool       $include_variations Include variations in search or not.
	 * @param bool       $all_statuses       Should we search all statuses or limit to published.
	 * @param null|int   $limit              Limit returned results.
	 * @param null|array $include            Keep specific results.
	 * @param null|array $exclude            Discard specific results.
	 *
	 * @based \WC_Product_Data_Store_CPT::search_products()
	 *
	 * @return array of ids
	 */
	public static function search_products( $term, $type = '', $include_variations = FALSE, $all_statuses = FALSE, $limit = NULL, $include = NULL, $exclude = NULL ) {

		global $wpdb;

		$atum_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;
		$post_types      = $include_variations ? [ 'product', 'product_variation' ] : [ 'product' ];
		$join_query      = '';
		$type_where      = '';
		$status_where    = '';
		$limit_query     = '';
		$joins           = [];

		// When searching variations we should include the parent's meta table for use in searches.
		if ( $include_variations ) {
			$joins[] = "LEFT JOIN $wpdb->wc_product_meta_lookup parent_wc_product_meta_lookup
			 ON (posts.post_type = 'product_variation' AND parent_wc_product_meta_lookup.product_id = posts.post_parent)";
			$joins[] = "LEFT JOIN $atum_data_table parent_apd
			 ON (posts.post_type = 'product_variation' AND parent_apd.product_id = posts.post_parent)";
		}

		$post_statuses = apply_filters( 'atum/search_products/post_statuses', Globals::get_queryable_product_statuses() );

		// See if search term contains OR keywords.
		$term_groups    = stristr( $term, ' or ' ) ? preg_split( '/\s+or\s+/i', $term ) : [ $term ];
		$search_where   = '';
		$search_queries = [];

		foreach ( $term_groups as $term_group ) {

			// Parse search terms.
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $term_group, $matches ) ) {

				$search_terms = self::get_valid_search_terms( $matches[0] );
				$count        = count( $search_terms );

				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence.
				if ( 9 < $count || 0 === $count ) {
					$search_terms = array( $term_group );
				}

			}
			else {
				$search_terms = array( $term_group );
			}

			$term_group_query = '';
			$searchand        = '';
			$variation_query  = '';

			foreach ( $search_terms as $search_term ) {

				$like = '%' . $wpdb->esc_like( $search_term ) . '%';

				// Variations should also search the parent's meta table for fallback fields.
				if ( $include_variations ) {
					$variation_query  = $wpdb->prepare( " OR ( wc_product_meta_lookup.sku = '' AND parent_wc_product_meta_lookup.sku LIKE %s ) ", $like );
					$variation_query .= $wpdb->prepare( " OR ( apd.supplier_sku = '' AND parent_apd.supplier_sku LIKE %s ) ", $like );
				}

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$term_group_query .= $wpdb->prepare( " 
					$searchand ( 
						( posts.post_title LIKE %s) OR ( posts.post_excerpt LIKE %s) OR 
						( posts.post_content LIKE %s ) OR ( wc_product_meta_lookup.sku LIKE %s ) OR 
						( apd.supplier_sku LIKE %s ) $variation_query 
					)
				", $like, $like, $like, $like, $like );
				// phpcs:enable

				$searchand = ' AND ';

			}

			if ( $term_group_query ) {
				$search_queries[] = $term_group_query;
			}

		}

		if ( ! empty( $joins ) ) {
			$join_query = implode( "\n", $joins );
		}

		if ( ! empty( $search_queries ) ) {
			$search_where = ' AND (' . implode( ') OR (', $search_queries ) . ') ';
		}

		if ( ! empty( $include ) && is_array( $include ) ) {
			$search_where .= ' AND posts.ID IN(' . implode( ',', array_map( 'absint', $include ) ) . ') ';
		}

		if ( ! empty( $exclude ) && is_array( $exclude ) ) {
			$search_where .= ' AND posts.ID NOT IN(' . implode( ',', array_map( 'absint', $exclude ) ) . ') ';
		}

		if ( 'virtual' === $type ) {
			$type_where = ' AND ( wc_product_meta_lookup.virtual = 1 ) ';
		}
		elseif ( 'downloadable' === $type ) {
			$type_where = ' AND ( wc_product_meta_lookup.downloadable = 1 ) ';
		}

		if ( ! $all_statuses ) {
			$status_where = " AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "') ";
		}

		if ( $limit ) {
			$limit_query = $wpdb->prepare( ' LIMIT %d ', $limit );
		}

		// phpcs:disable WordPress.DB.PreparedSQL
		$search_results = $wpdb->get_results( "
			SELECT DISTINCT posts.ID as product_id, posts.post_parent as parent_id FROM $wpdb->posts posts
			LEFT JOIN $wpdb->wc_product_meta_lookup wc_product_meta_lookup ON posts.ID = wc_product_meta_lookup.product_id
			LEFT JOIN $atum_data_table apd ON posts.ID = apd.product_id
			$join_query
			WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')
			$search_where
			$status_where
			$type_where
			ORDER BY posts.post_parent ASC, posts.post_title ASC
			$limit_query
		" );
		// phpcs:enable

		$product_ids = wp_parse_id_list( array_merge( wp_list_pluck( $search_results, 'product_id' ), wp_list_pluck( $search_results, 'parent_id' ) ) );

		if ( is_numeric( $term ) ) {

			$post_id   = absint( $term );
			$post_type = get_post_type( $post_id );

			if ( 'product_variation' === $post_type && $include_variations ) {
				$product_ids[] = $post_id;
			}
			elseif ( 'product' === $post_type ) {
				$product_ids[] = $post_id;
			}

			$product_ids[] = wp_get_post_parent_id( $post_id );

		}

		return wp_parse_id_list( $product_ids );

	}

	/**
	 * Check if the terms are suitable for searching.
	 *
	 * Uses an array of stopwords (terms) that are excluded from the separate
	 * term matching when searching for posts. The list of English stopwords is
	 * the approximate search engines list, and is translatable.
	 *
	 * @since 1.9.14
	 *
	 * @param array $terms Terms to check.
	 *
	 * @return array Terms that are not stopwords.
	 */
	private static function get_valid_search_terms( $terms ) {

		$valid_terms = [];

		// Translators: This is a comma-separated list of very common words that should be excluded from a search, like a, an, and the. These are usually called "stopwords". You should not simply translate these individual words into your language. Instead, look for and provide commonly accepted stopwords in your language.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$stopwords = apply_filters( 'wp_search_stopwords', array_map( 'wc_strtolower', array_map(
			'trim',
			explode(
				',',
				_x(
					'about,an,are,as,at,be,by,com,for,from,how,in,is,it,of,on,or,that,the,this,to,was,what,when,where,who,will,with,www',
					'Comma-separated list of search stopwords in your language',
					ATUM_TEXT_DOMAIN
				)
			)
		) ) ); // Using the default WP hook here (for compatibility).

		foreach ( $terms as $term ) {

			// keep before/after spaces when term is for exact match, otherwise trim quotes and spaces.
			if ( preg_match( '/^".+"$/', $term ) ) {
				$term = trim( $term, "\"'" );
			}
			else {
				$term = trim( $term, "\"' " );
			}

			// Avoid single A-Z and single dashes.
			if ( empty( $term ) || ( 1 === strlen( $term ) && preg_match( '/^[a-z\-]$/i', $term ) ) ) {
				continue;
			}

			if ( in_array( wc_strtolower( $term ), $stopwords, true ) ) {
				continue;
			}

			$valid_terms[] = $term;

		}

		return $valid_terms;

	}

	/**
	 * Convert a PHP date format to a compatible moment.js date format
	 *
	 * @since 1.9.21
	 *
	 * @param string $php_date_format
	 *
	 * @return string
	 */
	public static function convert_php_date_format_to_moment( $php_date_format ) {

		$replacements = [
			'A' => 'A',      // for the sake of escaping below.
			'a' => 'a',      // for the sake of escaping below.
			'B' => '',       // Swatch internet time (.beats), no equivalent.
			'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601.
			'D' => 'ddd',
			'd' => 'DD',
			'e' => 'zz',     // deprecated since version 1.6.0 of moment.js.
			'F' => 'MMMM',
			'G' => 'H',
			'g' => 'h',
			'H' => 'HH',
			'h' => 'hh',
			'I' => '',       // Daylight Saving Time? => moment().isDST().
			'i' => 'mm',
			'j' => 'D',
			'L' => '',       // Leap year? => moment().isLeapYear().
			'l' => 'dddd',
			'M' => 'MMM',
			'm' => 'MM',
			'N' => 'E',
			'n' => 'M',
			'O' => 'ZZ',
			'o' => 'YYYY',
			'P' => 'Z',
			'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822.
			'S' => 'o',
			's' => 'ss',
			'T' => 'z',      // deprecated since version 1.6.0 of moment.js.
			't' => '',       // days in the month => moment().daysInMonth().
			'U' => 'X',
			'u' => 'SSSSSS', // microseconds.
			'v' => 'SSS',    // milliseconds (from PHP 7.0.0).
			'W' => 'W',      // for the sake of escaping below.
			'w' => 'e',
			'Y' => 'YYYY',
			'y' => 'YY',
			'Z' => '',       // time zone offset in minutes => moment().zone();.
			'z' => 'DDD',
		];

		// Converts escaped characters.
		foreach ( $replacements as $from => $to ) {
			$replacements[ '\\' . $from ] = '[' . $from . ']';
		}

		return strtr( $php_date_format, $replacements );

	}

}
