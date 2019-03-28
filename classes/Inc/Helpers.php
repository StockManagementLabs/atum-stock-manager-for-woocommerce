<?php
/**
 * Helper functions
 *
 * @package        Atum
 * @subpackage     Inc
 * @author         Be Rebel - https://berebel.io
 * @copyright      ©2018 Stock Management Labs™
 *
 * @since          0.0.1
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumMarketingPopup;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use Atum\Legacy\HelpersLegacyTrait;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Queries\ProductDataQuery;
use Atum\Settings\Settings;
use Atum\Suppliers\Suppliers;


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

		$query = $wpdb->prepare( "
			SELECT $wpdb->terms.term_id FROM $wpdb->terms 
            INNER JOIN $wpdb->term_taxonomy ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
            WHERE $wpdb->term_taxonomy.taxonomy = %s
            AND $wpdb->terms.slug IN ('" . implode( "','", array_map( 'esc_attr', $slug_terms ) ) . "')
        ", $taxonomy ); // WPCS: unprepared SQL ok.

		$search_terms_ids = $wpdb->get_results( $query, ARRAY_A ); // WPCS: unprepared SQL ok.
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
			return "data-{$prefix}{$key}='$value'";
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
	 * Format a date to match the db format
	 *
	 * @since 0.1.3
	 *
	 * @param string|int $date         The date to format. Can be an English date or a timestamp (with second param as true).
	 * @param bool       $is_timestamp Whether the first param is a Unix timesptamp.
	 *
	 * @return string                   The formatted date
	 */
	public static function date_format( $date, $is_timestamp = FALSE ) {

		if ( ! $is_timestamp ) {
			$date = strtotime( $date );
		}

		return date( 'Y-m-d H:i:s', $date );
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
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
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
	 * @param array $items      Array of Product IDs we want to calculate sales from.
	 * @param int   $date_start The date from when to start the items' sales calculations (must be a string format convertible with strtotime).
	 * @param int   $date_end   Optional. The max date to calculate the items' sales (must be a string format convertible with strtotime).
	 *
	 * @return array
	 */
	public static function get_sold_last_days( $items, $date_start, $date_end = NULL ) {

		$items_sold = array();

		if ( ! empty( $items ) ) {

			global $wpdb;

			// Prepare the SQL query to get the orders in the specified time window.
			$date_start = date( 'Y-m-d H:i:s', strtotime( $date_start ) );
			$date_where = $wpdb->prepare( 'WHERE post_date >= %s', $date_start );

			if ( $date_end ) {
				$date_end    = date( 'Y-m-d H:i:s', strtotime( $date_end ) );
				$date_where .= $wpdb->prepare( ' AND post_date <= %s', $date_end );
			}

			$orders_query = "
				SELECT ID FROM $wpdb->posts  
				$date_where
				AND post_type = 'shop_order' AND post_status IN ('wc-processing', 'wc-completed')				  
			";

			$products = implode( ',', $items );

			$query = "
				SELECT SUM(`META_PROD_QTY`.`meta_value`) AS `QTY`, SUM(`META_PROD_TOTAL`.`meta_value`) AS `TOTAL`, 
				MAX(CAST(`META_PROD_ID`.`meta_value` AS SIGNED)) AS `PROD_ID`
				FROM `{$wpdb->posts}` AS `ORDERS`
			    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `ITEMS` ON (`ORDERS`.`ID` = `ITEMS`.`order_id`)
			    INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_ID` ON (`ITEMS`.`order_item_id` = `META_PROD_ID`.`order_item_id`)
			  	INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_QTY` ON (`META_PROD_ID`.`order_item_id` = `META_PROD_QTY`.`order_item_id`)
		        INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_TOTAL` ON (`META_PROD_ID`.`order_item_id` = `META_PROD_TOTAL`.`order_item_id`)
				WHERE (`ORDERS`.`ID` IN ($orders_query) AND `META_PROD_ID`.`meta_value` IN ($products)
			    AND `META_PROD_ID`.`meta_key` IN ('_product_id', '_variation_id')
			    AND `META_PROD_QTY`.`meta_key` = '_qty' AND `META_PROD_TOTAL`.`meta_key` = '_line_total')
				GROUP BY `META_PROD_ID`.`meta_value`
				HAVING (`QTY` IS NOT NULL);
			";

			$items_sold = $wpdb->get_results( $query, ARRAY_A ); // WPCS: unprepared SQL ok.

		}

		return $items_sold;

	}

	/**
	 * Get the lost sales of a specified product during the last days
	 *
	 * @since 1.2.3
	 *
	 * @param int|\WC_Product $product   The product ID or product object to calculate the lost sales.
	 * @param int             $days      Optional. By default the calculation is made for 7 days average.
	 *
	 * @return bool|float       Returns the lost sales or FALSE if never had lost sales
	 */
	public static function get_product_lost_sales( $product, $days = 7 ) {

		$lost_sales = FALSE;

		if ( ! is_a( $product, '\WC_Product' ) ) {
			$product = self::get_atum_product( $product );
		}

		/* @noinspection PhpUndefinedMethodInspection */
		$out_of_stock_date = $product->get_out_stock_date();

		if ( $out_of_stock_date && $days > 0 ) {

			$days_out_of_stock = self::get_product_out_of_stock_days( $product );

			if ( is_numeric( $days_out_of_stock ) ) {

				// Get the average sales for the past days when in stock.
				$days           = absint( $days );
				$sold_last_days = self::get_sold_last_days( [ $product->get_id() ], $out_of_stock_date . " -{$days} days", $out_of_stock_date );
				$lost_sales     = 0;

				if ( ! empty( $sold_last_days ) ) {

					$sold_last_days = current( $sold_last_days );

					if ( ! empty( $sold_last_days['QTY'] ) && $sold_last_days['QTY'] > 0 ) {

						$average_sales = $sold_last_days['QTY'] / $days;
						$price         = $product->get_regular_price();

						$lost_sales = $days_out_of_stock * $average_sales * $price;

					}

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
	 * @return bool|int  Returns the number of days or FALSE if is not "Out of Stock".
	 */
	public static function get_product_out_of_stock_days( $product ) {

		$out_of_stock_days = FALSE;

		if ( ! is_a( $product, '\WC_Product' ) ) {
			$product = self::get_atum_product( $product );
		}

		// Check if the current product has the "Out of stock" date recorded.
		/* @noinspection PhpUndefinedMethodInspection */
		$out_of_stock_date = $product->get_out_stock_date();

		if ( $out_of_stock_date ) {
			
			try {
				$out_date_time = new \DateTime( $out_of_stock_date );
				$now_date_time = new \DateTime( 'now' );
				$interval      = date_diff( $out_date_time, $now_date_time );

				$out_of_stock_days = $interval->days;

			} catch ( \Exception $e ) {
				error_log( __METHOD__ . ' || Product: ' . $product->get_id() . ' || ' . $e->getMessage() );
				return $out_of_stock_days;
			}
			
		}

		return $out_of_stock_days;

	}

	/**
	 * Helper function to return a plugin option value.
	 * If no value has been saved, it returns $default.
	 * Needed because options are saved as serialized strings.
	 *
	 * @since   0.0.2
	 *
	 * @param string  $name    The option key to retrieve.
	 * @param mixed   $default The default value returned if the option was not found.
	 * @param boolean $echo    If the option has to be returned or printed.
	 *
	 * @return mixed
	 */
	public static function get_option( $name, $default = FALSE, $echo = FALSE ) {

		// Save it as a global variable to not get the value each time.
		global $atum_global_options;

		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites.
		$atum_global_options = empty( $atum_global_options ) ? get_option( Settings::OPTION_NAME ) : $atum_global_options;
		$option              = isset( $atum_global_options[ $name ] ) ? $atum_global_options[ $name ] : $default;

		if ( $echo ) {
			echo apply_filters( "atum/print_option/$name", $option ); // WPCS: XSS ok.

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
	 * Get the a setting for the specified product
	 * First checks if has a meta key, if the meta value is distinct than global, returns that value,
	 * but if it's set to global, returns the global ATUM setting default for it.
	 *
	 * NOTE: The global setting must have the same name as the individual meta key but starting with the keyword "default".
	 *
	 * @since 1.4.18
	 *
	 * @param int    $product_id     The product ID.
	 * @param string $meta_key       The meta key name.
	 * @param mixed  $default        The default value for the global option.
	 * @param string $prefix         Optional. The ATUM add-ons should use a prefix for their settings.
	 * @param bool   $allow_global   Optional. If FALSE, only can return meta value or default. If TRUE, it could return 'global'.
	 *
	 * @return mixed
	 */
	public static function get_product_setting( $product_id, $meta_key, $default, $prefix = '', $allow_global = FALSE ) {
		
		$meta_value = get_post_meta( $product_id, $meta_key, TRUE );
		
		// If has no value saved, get the default.
		if ( ! $meta_value || 'global' === $meta_value ) {
			
			$option_name = "default{$meta_key}";
			
			if ( ! empty( $prefix ) ) {
				
				if ( '_' !== substr( $prefix, - 1, 1 ) ) {
					$prefix .= '_';
				}
				
				$option_name = $prefix . $option_name;
				
			}
			
			$meta_value = ! $allow_global ? self::get_option( $option_name, $default ) : 'global';
			
		}
		
		return $meta_value;

	}

	/**
	 * Get sold_last_days address var if set and valid, or the sales_last_ndays options/ Settings::DEFAULT_SALE_DAYS if set
	 *
	 * @since 1.4.11
	 *
	 * @return int days between 1 and 31
	 */
	public static function get_sold_last_days_option() {

		if ( isset( $_REQUEST['sold_last_days'] ) ) {

			// Sanitize.
			$value = absint( $_REQUEST['sold_last_days'] );

			if ( $value > 0 && $value < 31 ) {
				return $value;
			}

		}

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
		
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		
		if ( $get_stock_status ) {
			$unmng_fields[] = 'wpd.stock_status';
		}

		$unmng_join = (array) apply_filters( 'atum/get_unmanaged_products/join_query', $unmng_join );
		
		// Exclude the inheritable products from query (as are just containers in ATUM List Tables).
		$excluded_types = Globals::get_inheritable_product_types();

		$unmng_where = array(
			"WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')",
			"AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')",
			"AND (mt1.post_id IS NULL OR (mt1.meta_key = '_manage_stock' AND mt1.meta_value = 'no'))",
			"AND wpd.type NOT IN ('" . implode( "','", $excluded_types ) . "')",
		);
		
		$unmng_where = (array) apply_filters( 'atum/get_unmanaged_products/where_query', $unmng_where );
		
		$sql = 'SELECT DISTINCT ' . implode( ',', $unmng_fields ) . "\n FROM $wpdb->posts posts \n" . implode( "\n", $unmng_join ) . "\n" . implode( "\n", $unmng_where );
		
		return $wpdb->get_results( $sql, ARRAY_N ); // WPCS: unprepared SQL ok.
		
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

		return apply_filters( 'atum/format_price', wp_strip_all_tags( wc_price( round( $price, 2 ), $args ) ) );

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
		
		$file = apply_filters( "atum/load_view/$view", $view );
		$args = apply_filters( "atum/load_view_args/$view", $args );
		
		// Whether or not .php was added.
		if ( '.php' !== substr( $file, - 4 ) ) {
			$file .= '.php';
		}

		if ( $allow_theme_override ) {
			$file = self::locate_template( array( $view ), $file );
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
			$theme_overrides[] = Globals::TEMPLATE_DIR . "/$p";
		}

		$found = locate_template( $theme_overrides, FALSE );
		if ( $found ) {
			return $found;
		}

		// Check for it in the public directory.
		foreach ( $possibilities as $p ) {

			if ( file_exists( ATUM_PATH . "views/$p" ) ) {
				return ATUM_PATH . "views/$p";
			}

		}

		// Not template found.
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

		if ( ! is_a( $product, '\WC_product' ) ) {
			$product = self::get_atum_product( $product );
		}

		/* @noinspection PhpUndefinedMethodInspection */
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

		if ( ! is_a( $product, '\WC_product' ) ) {
			$product = self::get_atum_product( $product );
		}

		/* @noinspection PhpUndefinedMethodInspection */
		$product->set_atum_controlled( ( 'enable' === $status ? 'yes' : 'no' ) );
		/* @noinspection PhpUndefinedMethodInspection */
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

		if ( ! is_a( $product, '\WC_product' ) ) {
			$product = wc_get_product( $product ); // We don't need to use the ATUM models here.
		}

		/* @noinspection PhpUndefinedMethodInspection */
		$product->set_manage_stock( ( 'enable' === $status ? 'yes' : 'no' ) );
		$product->save();

	}

	/**
	 * Check whether a specific plugin is installed
	 *
	 * @since 1.2.0
	 *
	 * @param string $plugin        The plugin name/slug.
	 * @param string $by            Optional. It can be cheched by 'slug' or by 'name'.
	 * @param bool   $return_bool   Optional. May return a boolean (true/false) or an associative array with the plugin data.
	 *
	 * @return bool|array
	 */
	public static function is_plugin_installed( $plugin, $by = 'slug', $return_bool = TRUE ) {

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {

			// Get the plugin slug from its path.
			$installed_plugin_key = 'slug' === $by ? explode( DIRECTORY_SEPARATOR, $plugin_file )[0] : $plugin_data['Title'];

			if ( $installed_plugin_key === $plugin ) {
				return $return_bool ? TRUE : array( $plugin_file => $plugin_data );
			}
		}

		return FALSE;

	}

	/**
	 * Display a notice an ATUM's admin notice
	 *
	 * @since 1.2.0
	 *
	 * @param string $type              The notice type: error, success, warning or info.
	 * @param string $message           The message within the notice.
	 * @param bool   $is_dismissible    Optional. Whether to make the notice dismissible.
	 * @param string $key               Optional. Only needed for dismissible notices. Is the key used to save the dismissal on db.
	 */
	public static function display_notice( $type, $message, $is_dismissible = FALSE, $key = '' ) {

		$notice_classes = array( "notice-$type" );

		if ( $is_dismissible ) {

			// Check if the notice was already dismissed.
			if ( $key && self::is_notice_dismissed( $key ) ) {
				return;
			}

			$notice_classes[] = 'is-dismissible';
		}

		?>
		<div class="notice <?php echo esc_attr( implode( ' ', $notice_classes ) ) ?> atum-notice" data-key="<?php echo esc_attr( $key ) ?>">
			<p><?php echo $message; // WPCS: XSS ok. ?></p>

			<?php if ( $is_dismissible ) : ?>
			<script type="text/javascript">

				jQuery('.atum-notice').click('.notice-dismiss', function() {

					var $notice = jQuery(this).closest('.atum-notice');

					jQuery.ajax({
						url   : ajaxurl,
						method: 'POST',
						data  : {
							token : '<?php echo wp_create_nonce( 'dismiss-atum-notice' ); // WPCS: XSS ok. ?>',
							action: 'atum_dismiss_notice',
							key   : $notice.data('key')
						}
					});
				});

			</script>
			<?php endif; ?>
		</div>
		<?php

	}

	/**
	 * Add a notice to the list of dismissed notices for the current user
	 *
	 * @since 1.1.1
	 *
	 * @param string $notice    The notice key.
	 *
	 * @return int|bool
	 */
	public static function dismiss_notice( $notice ) {

		$current_user_id                   = get_current_user_id();
		$user_dismissed_notices            = self::get_dismissed_notices( $current_user_id );
		$user_dismissed_notices            = ! is_array( $user_dismissed_notices ) ? array() : $user_dismissed_notices;
		$user_dismissed_notices[ $notice ] = 'yes';

		return update_user_meta( $current_user_id, Globals::DISMISSED_NOTICES, $user_dismissed_notices );

	}

	/**
	 * Get the list of ATUM's dismissed notices for the current user
	 *
	 * @since 1.1.1
	 *
	 * @param int $user_id  The ID of the user to retrieve the dismissed notices from.
	 *
	 * @return array|bool
	 */
	public static function get_dismissed_notices( $user_id = NULL ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		return apply_filters( 'atum/dismissed_notices', get_user_meta( $user_id, Globals::DISMISSED_NOTICES, TRUE ) );
	}

	/**
	 * Check whether the specified notice was previously dismissed
	 *
	 * @since 1.4.4
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function is_notice_dismissed( $key ) {

		$current_user_id        = get_current_user_id();
		$user_dismissed_notices = self::get_dismissed_notices( $current_user_id );

		return isset( $user_dismissed_notices[ $key ] ) && 'yes' === $user_dismissed_notices[ $key ];
	}
	
	/**
	 * Check whether or not register the ES6 promise polyfill
	 * This is only required for SweetAlert2 on IE<12
	 *
	 * @since 1.2.0
	 */
	public static function maybe_es6_promise() {
		
		global $is_IE;
		// ES6 Polyfill (only for IE<12). Required by SweetAlert2.
		if ( $is_IE ) {
			wp_register_script( 'es6-promise', 'https://cdnjs.cloudflare.com/ajax/libs/core-js/2.4.1/core.js', [], ATUM_VERSION, TRUE );
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
		$output .= '<option value=""' . selected( $selected, '', FALSE ) . '>' . __( 'All product types', ATUM_TEXT_DOMAIN ) . '</option>';

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
	 * @param string $selected  Optional. The pre-selected option.
	 * @param bool   $enhanced  Optional. Whether to show an enhanced select.
	 * @param string $class     Optional. The dropdown class name.
	 *
	 * @return string
	 */
	public static function suppliers_dropdown( $selected = '', $enhanced = FALSE, $class = 'dropdown_supplier' ) {

		if ( ! ModuleManager::is_module_active( 'purchase_orders' ) || ! AtumCapabilities::current_user_can( 'read_supplier' ) ) {
			return '';
		}

		ob_start();

		if ( ! $enhanced ) :

			$args = array(
				'post_type'      => Suppliers::POST_TYPE,
				'posts_per_page' => - 1,

			);

			$suppliers = get_posts( $args );

			if ( empty( $suppliers ) ) :
				ob_end_flush();
				return '';
			endif;
			?>

			<select name="supplier" class="wc-enhanced-select atum-enhanced-select atum-tooltip <?php echo esc_attr( $class ) ?>" id="supplier" autocomplete="off" style="width: 165px">
				<option value=""<?php selected( $selected, '' ) ?>><?php esc_attr_e( 'Show all suppliers', ATUM_TEXT_DOMAIN ) ?></option>

				<?php foreach ( $suppliers as $supplier ) : ?>
					<option value="<?php echo esc_attr( $supplier->ID ) ?>"<?php selected( $supplier->ID, $selected ) ?>><?php echo esc_attr( $supplier->post_title ) ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>

			<select class="wc-product-search atum-enhanced-select atum-tooltip <?php echo esc_attr( $class ) ?>" id="supplier" name="supplier" data-allow_clear="true"
				data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier&hellip;', ATUM_TEXT_DOMAIN ); ?>"
				data-multiple="false" data-selected="" data-minimum_input_length="1" style="width: 165px">
				<?php if ( $selected ) :
					$supplier = get_post( $selected ); ?>
					<option value="<?php echo esc_attr( $selected ) ?>" selected="selected"><?php echo esc_attr( $supplier->post_title ) ?></option>
				<?php endif; ?>
			</select>

			<?php

		endif;

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
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . ' WHERE order_id = %d ORDER BY order_item_id', $order_id ); // WPCS: unprepared SQL ok.

		return $wpdb->get_results( $query ); // WPCS: unprepared SQL ok.

	}

	/**
	 * Get the appropriate ATUM Order model
	 *
	 * @since 1.2.9
	 *
	 * @param int $atum_order_id
	 *
	 * @return AtumOrderModel|\WP_Error
	 */
	public static function get_atum_order_model( $atum_order_id ) {

		$post_type = get_post_type( $atum_order_id );

		switch ( $post_type ) {
			case InventoryLogs::POST_TYPE:
				$model_class = '\Atum\InventoryLogs\Models\Log';
				break;

			case PurchaseOrders::POST_TYPE:
				$model_class = '\Atum\PurchaseOrders\Models\PurchaseOrder';
				break;
		}

		if ( ! isset( $model_class ) || ! class_exists( $model_class ) ) {
			return new \WP_Error( 'invalid_post_type', __( 'No valid ID provided', ATUM_TEXT_DOMAIN ) );
		}

		return new $model_class( $atum_order_id );

	}

	/**
	 * Get the inbound stock amount for the specified product
	 *
	 * @since 1.5.4
	 *
	 * @param int $product_id
	 *
	 * @return float
	 */
	public static function get_inbound_stock_for_product( $product_id ) {

		$cache_key     = AtumCache::get_cache_key( 'inbound_stock_for_product', $product_id );
		$inbound_stock = AtumCache::get_cache( $cache_key, ATUM_TEXT_DOMAIN, FALSE, $has_cache );

		if ( ! $has_cache ) {

			// Calculate the inbound stock from pending purchase orders.
			global $wpdb;

			$sql = $wpdb->prepare( "
				SELECT SUM(oim2.`meta_value`) AS quantity 			
				FROM `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS oi 
				LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim ON oi.`order_item_id` = oim.`order_item_id`
				LEFT JOIN `$wpdb->atum_order_itemmeta` AS oim2 ON oi.`order_item_id` = oim2.`order_item_id`
				LEFT JOIN `$wpdb->posts` AS p ON oi.`order_id` = p.`ID`
				WHERE oim.`meta_key` IN ('_product_id', '_variation_id') AND `order_item_type` = 'line_item' 
				AND p.`post_type` = %s AND oim.`meta_value` = %d AND `post_status` <> '" . ATUM_PREFIX . PurchaseOrders::FINISHED . "' 
				AND oim2.`meta_key` = '_qty'
				GROUP BY oim.`meta_value`;",
				PurchaseOrders::POST_TYPE,
				$product_id
			); // WPCS: unprepared SQL ok.

			$inbound_stock = $wpdb->get_var( $sql ); // WPCS: unprepared SQL ok.
			$inbound_stock = $inbound_stock ?: 0;

			AtumCache::set_cache( $cache_key, $inbound_stock );

		}

		return $inbound_stock;

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
		
		if ( isset( $post_type_class ) && class_exists( $post_type_class ) ) {
			$statuses = call_user_func( array( $post_type_class, 'get_statuses' ) );
			
			if ( $remove_finished ) {
				
				unset( $statuses[ constant( $post_type_class . '::FINISHED' ) ] );
				
			}
			
		}
		
		return $statuses;
		
	}
	
	/**
	 * Get the appropriate ATUM Order list of statuses depending on the post_type
	 *
	 * @since 1.5.0
	 *
	 * @param string $post_type
	 * @param bool   $remove_finished Whether to remove or not the finished status.
	 * @param bool   $add_prefix      Whether to add or not the ATUM prefix to each status.
	 *
	 * @return array
	 */
	public static function get_atum_order_post_type_statuses_simple( $post_type, $remove_finished = FALSE, $add_prefix = FALSE ) {
		
		$statuses = [];
		
		switch ( $post_type ) {
			case InventoryLogs::POST_TYPE:
				$post_type_class = '\Atum\InventoryLogs\InventoryLogs';
				break;
			
			case PurchaseOrders::POST_TYPE:
				$post_type_class = '\Atum\PurchaseOrders\PurchaseOrders';
				break;
		}
		
		if ( isset( $post_type_class ) && class_exists( $post_type_class ) ) {
			$statuses = call_user_func( array( $post_type_class, 'get_statuses_simple' ), $add_prefix );
			
			if ( $remove_finished ) {
				
				$constant_name  = $add_prefix ? ATUM_PREFIX : '';
				$constant_name .= constant( $post_type_class . '::FINISHED' );
				
				if ( ( $key = array_search( $constant_name, $statuses ) ) !== FALSE ) {
					unset( $statuses[ $key ] );
				}
				
			}
			
		}
		
		return $statuses;
		
	}
	
	
	/**
	 * Get a WooCommerce product using the ATUM's product data models
	 *
	 * @since 1.5.0
	 *
	 * @param mixed $the_product Post object or post ID of the product.
	 *
	 * @return \WC_Product|null|false
	 */
	public static function get_atum_product( $the_product = FALSE ) {

		Globals::enable_atum_product_data_models();
		$product = wc_get_product( $the_product );
		Globals::disable_atum_product_data_models();

		return $product;

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
		
		if ( ! $product || ! is_a( $product, '\WC_Product' ) ) {
			return;
		}
		
		$product_data = apply_filters( 'atum/product_data', $product_data, $product_id );
		
		foreach ( $product_data as $meta_key => &$meta_value ) {
			
			$meta_key = esc_attr( $meta_key );
			
			switch ( $meta_key ) {
				
				case 'stock':
					unset( $product_data['stock_custom'], $product_data['stock_currency'] );
					$product->set_stock_quantity( $meta_value );
					
					// Needed to clear transients and other stuff.
					do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product ); // WPCS: prefix ok.
					
					break;
				
				case 'regular_price':
					$product->set_regular_price( $meta_value );

					if ( 'regular_price' === $meta_key && ! $product->is_on_sale( 'edit' ) ) {
						$product->set_price( $meta_value );

					}

					if ( class_exists( '\WC_Subscription' ) && in_array( $product->get_type(), [ 'subscription', 'variable-subscription' ] ) ) {

						update_post_meta( $product_id, '_subscription_price', $meta_value );

					}
						
					unset( $product_data['regular_price_custom'], $product_data['regular_price_currency'] );
					
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
						$now       = self::get_wc_time( current_time( 'timestamp', TRUE ) );

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
						
					unset( $product_data['sale_price_custom'], $product_data['sale_price_currency'] );
					
					break;
				
				case substr( Globals::PURCHASE_PRICE_KEY, 1 ):
					/* @noinspection PhpUndefinedMethodInspection */
					$product->set_purchase_price( $meta_value );
					
					unset( $product_data['purchase_price_custom'], $product_data['purchase_price_currency'] );
					break;
				
				// Any other text meta.
				default:
					if ( is_callable( array( $product, "set_{$meta_key}" ) ) ) {
						call_user_func( array( $product, "set_{$meta_key}" ), $meta_value );
					}
					else {
						update_post_meta( $product_id, '_' . $meta_key, esc_attr( $meta_value ) );
					}

					unset( $product_data[ '_' . $meta_key . '_custom' ], $product_data[ '_' . $meta_key . 'currency' ] );
					break;
			}
			
		}
		
		$product->save();
		
		if ( ! $skip_action ) {
			do_action( 'atum/product_data_updated', $product_id, $product_data );
		}
		
	}
	
	/**
	 * Return header support buttons info
	 *
	 * @since 1.4.3.3
	 *
	 * @return array
	 */
	public static function get_support_button() {
		
		if ( Addons::has_valid_key() ) {
			$support['support_link']        = 'https://stockmanagementlabs.ticksy.com/';
			$support['support_button_text'] = __( 'Get Premium Support', ATUM_TEXT_DOMAIN );
		}
		else {
			$support['support_link']        = 'https://forum.stockmanagementlabs.com/t/atum-wp-plugin-issues-bugs-discussions';
			$support['support_button_text'] = __( 'Get Support', ATUM_TEXT_DOMAIN );
		}
		
		return $support;
		
	}

	/**
	 * Force save with changes to validate_props and rebuild stock_status if required.
	 * We can use it with 1 product/variation or set all to true to aply to all products OUT_STOCK_THRESHOLD_KEY
	 * set and clean or not the OUT_STOCK_THRESHOLD_KEY meta keys
	 *
	 * @since 1.4.10
	 *
	 * @param \WC_Product $product    Any subclass of WC_Abstract_Legacy_Product.
	 * @param bool        $clean_meta
	 * @param bool        $all
	 */
	public static function force_rebuild_stock_status( $product = NULL, $clean_meta = FALSE, $all = FALSE ) {

		global $wpdb;
		$wpdb->hide_errors();

		if ( is_subclass_of( $product, '\WC_Abstract_Legacy_Product' ) && ! is_null( $product ) ) {

			// TODO: THIS SHOULD BE DONE IN A MORE STANDARD WAY.
			$product->set_stock_quantity( $product->get_stock_quantity() + 1 );
			$product->set_stock_quantity( $product->get_stock_quantity() - 1 );

			if ( $clean_meta ) {
				/* @noinspection PhpUndefinedMethodInspection */
				$product->set_out_stock_threshold( NULL );
			}

			$product->save();

			return;

		}

		// TODO: IS THIS NEEDED?
		if ( $all ) {

			$ids_to_rebuild_stock_status = $wpdb->get_col( "
                SELECT DISTINCT ID FROM $wpdb->posts p
                INNER JOIN $wpdb->prefix" . Globals::ATUM_PRODUCT_DATA_TABLE . " ap ON p.ID = ap.product_id
                WHERE p.post_status IN ('publish', 'future', 'private')
                AND ap.out_stock_threshold IS NOT NULL;
            " ); // WPCS: unprepared SQL ok.

			foreach ( $ids_to_rebuild_stock_status as $id_to_rebuild ) {

				$product = self::get_atum_product( $id_to_rebuild );

				// Delete _out_stock_threshold (avoid partial works to be done again).
				if ( $clean_meta ) {
					/* @noinspection PhpUndefinedMethodInspection */
					$product->set_out_stock_threshold( NULL );
				}

				// Force change and save.
				$product->set_stock_quantity( $product->get_stock_quantity() + 1 );
				$product->set_stock_quantity( $product->get_stock_quantity() - 1 );
				$product->save();

			}
			
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

		$row_count = $wpdb->get_var( "
			SELECT COUNT(*) FROM $wpdb->prefix" . Globals::ATUM_PRODUCT_DATA_TABLE . " ap
			INNER JOIN $wpdb->posts p  ON p.ID = ap.product_id
			WHERE ap.out_stock_threshold IS NOT NULL
			AND  p.post_status IN ('publish', 'future', 'private');
		" ); // WPCS: unprepared SQL ok.

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

			// If is a numeric column, the NULL values should display at the end.
			if ( 'NUMERIC' === $query_data['order']['type'] ) {
				$column = "IFNULL($column, " . PHP_INT_MAX . ')';
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

		// Recursively build a nested grouping if more parameters are supplied.
		// Each grouped array value is grouped according to the next sequential key.
		if ( func_num_args() > 2 ) {
			$args = func_get_args();

			foreach ( $grouped as $key => $value ) {
				$params          = array_merge( [ $value ], array_slice( $args, 2, func_num_args() ) );
				$grouped[ $key ] = call_user_func_array( 'array_group_by', $params );
			}
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
		
		$step = self::get_option( 'stock_quantity_step' );
		return $step ? $step : 10 / pow( 10, Globals::get_stock_decimals() + 1 );
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
				    LEFT JOIN $wpdb->terms as terms ON (terms.term_id = termrelations.term_taxonomy_id)
					LEFT JOIN $wpdb->term_taxonomy as taxonomies ON (taxonomies.term_taxonomy_id = termrelations.term_taxonomy_id)  
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
		$atum_user_meta = get_user_meta( $user_id, ATUM_PREFIX . 'user_meta', TRUE );

		if ( $key && is_array( $atum_user_meta ) && in_array( $key, array_keys( $atum_user_meta ), TRUE ) ) {
			return $atum_user_meta[ $key ];
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
		$atum_user_meta = get_user_meta( $user_id, ATUM_PREFIX . 'user_meta', TRUE );

		if ( ! is_array( $atum_user_meta ) ) {
			$atum_user_meta = array();
		}

		$atum_user_meta[ $key ] = $value;
		update_user_meta( $user_id, ATUM_PREFIX . 'user_meta', $atum_user_meta );

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
			
			if ( is_a( $value, 'WC_DateTime' ) ) {
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
	public static function image_placeholder( $image, $size, $dimensions ) {
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

		$marketing_popup = AtumMarketingPopup::get_instance();
		$transient_key   = AtumCache::get_transient( 'atum-marketing-popup', TRUE );

		if ( ! $transient_key || $marketing_popup->get_transient_key() !== $transient_key ) {

			if ( ! $marketing_popup->is_loaded() ) {
				return FALSE;
			}

			$transient_key = $marketing_popup->get_transient_key();
			AtumCache::set_transient( 'atum-marketing-popup', $transient_key, WEEK_IN_SECONDS, TRUE );

		}

		// Get marketing popup user meta.
		$marketing_popup_user_meta = get_user_meta( get_current_user_id(), 'atum-marketing-popup', TRUE );

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

}
