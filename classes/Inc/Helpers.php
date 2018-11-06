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
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\PurchaseOrders;
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
	 * Output a dropdown to choose the ATUM Order status
	 *
	 * @since 1.2.9
	 *
	 * @param string $id        The select ID.
	 * @param string $value     The selected option.
	 */
	public static function atum_order_status_dropdown( $id, $value ) {

		?>
		<select id="<?php echo esc_attr( $id ) ?>" name="<?php echo esc_attr( $id ) ?>" class="wc-enhanced-select">
			<?php
			$statuses = AtumOrderPostType::get_statuses();
			foreach ( $statuses as $status => $status_name ) : ?>
				<option value="<?php echo esc_attr( $status ) ?>"<?php selected( $status, $value ) ?>><?php echo esc_html( $status_name ) ?></option>
			<?php endforeach; ?>
		</select>
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
	 *
	 * @return array
	 */
	public static function get_all_products( $args = array() ) {

		$defaults = array(
			'post_type'      => 'product',
			'post_status'    => current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ],
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		);

		$args = (array) apply_filters( 'atum/get_all_products/args', array_merge( $defaults, $args ) );

		$product_ids_transient = self::get_transient_identifier( $args, 'all_products' );
		$products              = self::get_transient( $product_ids_transient );

		if ( ! $products ) {
			$products = get_posts( $args );
			self::set_transient( $product_ids_transient, $products, HOUR_IN_SECONDS );
		}

		return $products;

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
		
		$result = array();
		$query  = new \WP_Query( $args );
		
		if ( $query->post_count > 0 ) {
			
			if ( $fields ) {
				$result = $query->posts;
			}
			else {
				foreach ( $query->posts as $post ) {
					// We need the WooCommerce order, not the post.
					$result[] = new \WC_Order( $post->ID );
				}
			}
			
		}
		
		return $result;
		
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
				    INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `ITEMS` 
				        ON (`ORDERS`.`ID` = `ITEMS`.`order_id`)
				    INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_ID`
				        ON (`ITEMS`.`order_item_id` = `META_PROD_ID`.`order_item_id`)
				    INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_QTY`
				        ON (`META_PROD_ID`.`order_item_id` = `META_PROD_QTY`.`order_item_id`)
			        INNER JOIN `$wpdb->order_itemmeta` AS `META_PROD_TOTAL`
				        ON (`META_PROD_ID`.`order_item_id` = `META_PROD_TOTAL`.`order_item_id`)
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
	 * @param int $product_id   The product ID to calculate the lost sales.
	 * @param int $days         Optional. By default the calculation is made for 7 days average.
	 *
	 * @return bool|float       Returns the lost sales or FALSE if never had lost sales
	 */
	public static function get_product_lost_sales( $product_id, $days = 7 ) {

		$lost_sales        = FALSE;
		$out_of_stock_date = get_post_meta( $product_id, Globals::OUT_OF_STOCK_DATE_KEY, TRUE );

		if ( $out_of_stock_date && $days > 0 ) {

			$days_out_of_stock = self::get_product_out_of_stock_days( $product_id );

			if ( is_numeric( $days_out_of_stock ) ) {

				// Get the average sales for the past days when in stock.
				$days           = absint( $days );
				$sold_last_days = self::get_sold_last_days( [ $product_id ], $out_of_stock_date . " -{$days} days", $out_of_stock_date );
				$lost_sales     = 0;

				if ( ! empty( $sold_last_days ) ) {

					$sold_last_days = reset( $sold_last_days );

					if ( ! empty( $sold_last_days['QTY'] ) && $sold_last_days['QTY'] > 0 ) {

						$average_sales = $sold_last_days['QTY'] / $days;
						$product       = wc_get_product( $product_id );
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
	 * @param int $product_id   The product ID.
	 *
	 * @return bool|int         Returns the number of days or FALSE if is not "Out of Stock".
	 */
	public static function get_product_out_of_stock_days( $product_id ) {

		$out_of_stock_days = FALSE;

		// Check if the current product has the "Out of stock" date recorded.
		$out_of_stock_date = get_post_meta( $product_id, Globals::OUT_OF_STOCK_DATE_KEY, TRUE );

		if ( $out_of_stock_date ) {
			
			try {
				$out_date_time = new \DateTime( $out_of_stock_date );
				$now_date_time = new \DateTime( 'now' );
				$interval      = date_diff( $out_date_time, $now_date_time );

				$out_of_stock_days = $interval->days;

			} catch ( \Exception $e ) {
				error_log( __METHOD__ . ' || Product: ' . $product_id . ' || ' . $e->getMessage() );
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
		global $global_options;

		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites.
		$global_options = empty( $global_options ) ? get_option( Settings::OPTION_NAME ) : $global_options;
		$option         = isset( $global_options[ $name ] ) ? $global_options[ $name ] : $default;

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
		global $global_options;

		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites.
		$global_options = empty( $global_options ) ? get_option( Settings::OPTION_NAME ) : $global_options;

		if ( ! $global_options ) {
			$global_options = array();
		}

		return apply_filters( 'atum/get_options', $global_options );

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

				if ( '_' !== substr( $prefix, -1, 1 ) ) {
					$prefix .= '_';
				}

				$option_name = $prefix . $option_name;

			}

			$meta_value  = ! $allow_global ? self::get_option( $option_name, $default ) : 'global';

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

		if ( isset( $_REQUEST['sold_last_days'] ) ) { // WPCS: CSRF ok.

			// Sanitize.
			$value = absint( esc_attr( $_REQUEST['sold_last_days'] ) ); // WPCS: CSRF ok.

			if ( $value > 0 && $value < 31 ) {
				return $value;
			}

		}

		return absint( self::get_option( 'sales_last_ndays', Settings::DEFAULT_SALE_DAYS ) );

	}

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
		
		global $wpdb;
		
		$unmng_join = apply_filters( 'atum/get_unmanaged_products/join_query', "
			LEFT JOIN $wpdb->postmeta AS mt1 ON (posts.ID = mt1.post_id AND mt1.meta_key = '_manage_stock')
		" );
		
		$post_statuses = current_user_can( 'edit_private_products' ) ? [ 'private', 'publish' ] : [ 'publish' ];
		
		// TODO: Change the query to remove the subquery and get the values with joins.
		$stock_sql = $get_stock_status ? ", (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = posts.ID AND meta_key = '_stock_status' )" : '';
		
		// Exclude the inheritable products from query (as are just containers in ATUM List Tables).
		$excluded_types      = Globals::get_inheritable_product_types();
		$excluded_type_terms = array();
		
		foreach ( $excluded_types as $excluded_type ) {
			$excluded_type_terms[] = get_term_by( 'slug', $excluded_type, 'product_type' );
		}
		
		$excluded_type_terms = wp_list_pluck( array_filter( $excluded_type_terms ), 'term_taxonomy_id' );

		$unmng_where = "
			WHERE posts.post_type IN ('" . implode( "','", $post_types ) . "')
            AND posts.post_status IN ('" . implode( "','", $post_statuses ) . "')
		    AND (mt1.post_id IS NULL OR (mt1.meta_key = '_manage_stock' AND mt1.meta_value = 'no'))
			AND posts.ID NOT IN ( 
				SELECT DISTINCT object_id FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id IN  (" . implode( ',', $excluded_type_terms ) . ") )
				AND (posts.post_parent IN (
					SELECT ID FROM $wpdb->posts 
					WHERE post_type = 'product' AND post_status IN ('private','publish') 
				)
				OR posts.post_parent = 0
			)
		";
		
		$unmng_where = apply_filters( 'atum/get_unmanaged_products/where_query', $unmng_where );
		
		$sql = "SELECT DISTINCT posts.ID{$stock_sql} FROM $wpdb->posts posts $unmng_join $unmng_where";
		
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
	 * Set a transient adding ATUM stuff to unequivocal identify it
	 *
	 * @since 0.0.2
	 *
	 * @param string $transient_id  Transient identifier.
	 * @param mixed  $value         Value to store.
	 * @param int    $expiration    Optional. Time until expiration in seconds. By default is set to 0 (does not expire).
	 * @param bool   $force         Optional. If set to TRUE, will set the transient in debug mode too.
	 *
	 * @return bool  FALSE if value was not set and TRUE if value was set
	 */
	public static function set_transient( $transient_id, $value, $expiration = 0, $force = FALSE ) {
		
		return ( $force || TRUE !== ATUM_DEBUG ) ? set_transient( $transient_id, $value, $expiration ) : FALSE;
	}

	/**
	 * Get a transient adding ATUM stuff to unequivocal identify it
	 *
	 * @since 0.0.2
	 *
	 * @param string $transient_id  Transient identifier.
	 * @param bool   $force         Optional. If set to TRUE, will get the transient in debug mode too.
	 *
	 * @return mixed|bool  The ATUM transient value or FALSE if the transient does not exist or debug mode is on
	 */
	public static function get_transient( $transient_id, $force = FALSE ) {

		return ( $force || TRUE !== ATUM_DEBUG ) ? get_transient( $transient_id ) : FALSE;
	}

	/**
	 * Get md5 hash of a array of args to create unique transient identifier
	 *
	 * @since 0.0.3
	 *
	 * @param array  $args      Optional. The args to hash.
	 * @param string $prefix    Optional. The transient name prefix.
	 *
	 * @return string
	 */
	public static function get_transient_identifier( $args = array(), $prefix = '' ) {

		$transient_id = ( empty( $prefix ) || 0 !== strpos( $prefix, ATUM_PREFIX ) ) ? ATUM_PREFIX . $prefix : $prefix;

		if ( ! empty( $args ) ) {

			if ( '_' !== substr( $transient_id, -1, 1 ) ) {
				$transient_id .= '_';
			}

			$transient_id .= md5( maybe_serialize( $args ) );

		}

		return $transient_id;
	}

	/**
	 * Delete all the ATUM transients
	 *
	 * @since 0.1.5
	 *
	 * @param string $type  Optional. If specified will remove specific type of ATUM transients.
	 *
	 * @return int|bool The number of transients deleted on success or false on error
	 */
	public static function delete_transients( $type = '' ) {

		global $wpdb;

		$type         = esc_attr( $type );
		$transient_id = $type ?: ATUM_PREFIX;

		return $wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_{$transient_id}%'" ); // WPCS: unprepared SQL ok.
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
			@include $file_path; // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
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
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public static function is_atum_controlling_stock( $product_id ) {
		return 'yes' === self::get_atum_control_status( $product_id );
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
	 * @param int $product_id
	 *
	 * @return string|bool  yes if On of FALSE if Off
	 */
	public static function get_atum_control_status( $product_id ) {
		return get_post_meta( $product_id, Globals::ATUM_CONTROL_STOCK_KEY, TRUE );
	}

	/**
	 * Enables the ATUM control switch for the specified product
	 *
	 * @since 1.4.1
	 *
	 * @param int $product_id
	 */
	public static function enable_atum_control( $product_id ) {
		update_post_meta( $product_id, Globals::ATUM_CONTROL_STOCK_KEY, 'yes' );
	}

	/**
	 * Enables the WC's manage stock for the specified product
	 *
	 * @since 1.4.5
	 *
	 * @param int $product_id
	 */
	public static function enable_wc_manage_stock( $product_id ) {
		update_post_meta( $product_id, '_manage_stock', 'yes' );
	}

	/**
	 * Disables the ATUM control switch for the specified product
	 *
	 * @since 1.4.1
	 *
	 * @param int $product_id
	 */
	public static function disable_atum_control( $product_id ) {
		delete_post_meta( $product_id, Globals::ATUM_CONTROL_STOCK_KEY );
	}

	/**
	 * Disables the WC's manage stock for the specified product
	 *
	 * @since 1.4.5
	 *
	 * @param int $product_id
	 */
	public static function disable_wc_manage_stock( $product_id ) {
		update_post_meta( $product_id, '_manage_stock', 'no' );
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

		$output  = '<select name="product_type" class="' . $class . '" autocomplete="off">';
		$output .= '<option value=""' . selected( $selected, '', FALSE ) . '>' . __( 'Show all product types', ATUM_TEXT_DOMAIN ) . '</option>';

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

			<select name="supplier" class="<?php echo esc_attr( $class ) ?>" autocomplete="off" style="width: 165px">
				<option value=""<?php selected( $selected, '' ) ?>><?php esc_attr_e( 'Show all suppliers', ATUM_TEXT_DOMAIN ) ?></option>

				<?php foreach ( $suppliers as $supplier ) : ?>
					<option value="<?php echo esc_attr( $supplier->ID ) ?>"<?php selected( $supplier->ID, $selected ) ?>><?php echo esc_attr( $supplier->post_title ) ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>

			<select class="wc-product-search <?php echo esc_attr( $class ) ?>" id="supplier" name="supplier" data-allow_clear="true"
					data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier&hellip;', ATUM_TEXT_DOMAIN ); ?>"
					data-multiple="false" data-selected="" data-minimum_input_length="1" style="width: 165px">
				<?php if ( $selected ) :
					$supplier = get_post( $selected ); ?>
					<option value="<?php echo esc_attr( $selected ) ?>" selected="selected"><?php echo esc_attr( $supplier->post_title ) ?></option>
				<?php endif; ?>
			</select>

			<?php
			wp_enqueue_script( 'wc-enhanced-select' );

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

		return get_posts( apply_filters( 'atum/get_logs_args', $args ) );

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
	 * Update product meta from Stock Central List
	 *
	 * @since 1.3.0
	 *
	 * @param int   $product_id
	 * @param array $product_meta
	 * @param bool  $skip_action
	 */
	public static function update_product_meta( $product_id, $product_meta, $skip_action = FALSE ) {
		
		$product = wc_get_product( $product_id );
		
		if ( ! $product || ! is_a( $product, '\WC_Product' ) ) {
			return;
		}
		
		$product_meta = apply_filters( 'atum/product_meta', $product_meta, $product_id );
		
		foreach ( $product_meta as $meta_key => &$meta_value ) {
			
			$meta_key = esc_attr( $meta_key );
			
			switch ( $meta_key ) {
				
				case 'stock':
					unset( $product_meta['stock_custom'], $product_meta['stock_currency'] );
					$product->set_stock_quantity( $meta_value );
					
					// Needed to clear transients and other stuff.
					do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product ); // WPCS: prefix ok.
					
					break;
				
				case 'regular_price':
					$product->set_regular_price( $meta_value );

					if ( 'regular_price' === $meta_key && ! $product->is_on_sale( 'edit' ) ) {
						$product->set_price( $meta_value );
					}
						
					unset( $product_meta['regular_price_custom'], $product_meta['regular_price_currency'] );
					
					break;
				
				case 'sale_price':
					$sale_price    = wc_format_decimal( $meta_value );
					$regular_price = $product->get_regular_price();

					// The sale price cannot be higher than the regular price.
					if ( $regular_price >= $sale_price ) {
						$product->set_sale_price( $sale_price );
					}

					// Check for sale dates.
					if ( isset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] ) ) {

						$date_from = wc_clean( $product_meta['_sale_price_dates_from'] );
						$date_to   = wc_clean( $product_meta['_sale_price_dates_to'] );

						$date_from = $date_from ? strtotime( $date_from ) : '';
						$date_to   = $date_to ? strtotime( $date_to ) : '';

						if ( $date_to && ! $date_from ) {
							$date_from = current_time( 'timestamp', TRUE );
						}

						// Update price if on sale.
						if ( $product->is_on_sale( 'edit' ) ) {
							$product->set_price( $sale_price );
						}
						else {

							$product->set_price( $regular_price );

							if ( $date_to && $date_to < current_time( 'timestamp', TRUE ) ) {
								$date_from = $date_to = '';
							}

						}

						$product->set_date_on_sale_from( $date_from );
						$product->set_date_on_sale_to( $date_to );

					}
						
					unset( $product_meta['sale_price_custom'], $product_meta['sale_price_currency'] );
					
					break;
				
				case substr( Globals::PURCHASE_PRICE_KEY, 1 ):
					update_post_meta( $product_id, Globals::PURCHASE_PRICE_KEY, wc_format_decimal( $meta_value ) );
					
					unset( $product_meta['purchase_price_custom'], $product_meta['purchase_price_currency'] );
					break;
				
				// Any other text meta.
				default:
					update_post_meta( $product_id, '_' . $meta_key, esc_attr( $meta_value ) );
					unset( $product_meta[ '_' . $meta_key . '_custom' ], $product_meta[ '_' . $meta_key . 'currency' ] );
					break;
			}
			
		}
		
		$product->save();
		
		if ( ! $skip_action ) {
			do_action( 'atum/product_meta_updated', $product_id, $product_meta );
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
	 * (@see clean OUT_STOCK_THRESHOLD_KEY tool).
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

			$product->set_stock_quantity( $product->get_stock_quantity() + 1 );
			$product->set_stock_quantity( $product->get_stock_quantity() - 1 );
			$product->save();

			if ( $clean_meta ) {
				delete_post_meta( $product->get_id(), Globals::OUT_STOCK_THRESHOLD_KEY );
			}

			return;

		}

		if ( $all ) {

			$ids_to_rebuild_stock_status = $wpdb->get_col( "
                SELECT DISTINCT p.ID FROM $wpdb->posts p
                INNER JOIN $wpdb->postmeta pm ON ( pm.meta_key = '" . Globals::OUT_STOCK_THRESHOLD_KEY . "' AND pm.post_id = p.ID )
                WHERE p.post_type IN ('product', 'product_variation') AND p.post_status IN ('publish', 'future', 'private');
            " ); // WPCS: unprepared SQL ok.

			foreach ( $ids_to_rebuild_stock_status as $id_to_rebuild ) {

				// Delete _out_stock_threshold (avoid partial works to be done again).
				if ( $clean_meta ) {
					delete_post_meta( $id_to_rebuild, Globals::OUT_STOCK_THRESHOLD_KEY );
				}

				$product = wc_get_product( $id_to_rebuild );

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

		$rowcount = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta where meta_key = '" . Globals::OUT_STOCK_THRESHOLD_KEY . "';" ); // WPCS: unprepared SQL ok.

		return $rowcount > 0;
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

		$files = @scandir( $path, 1 ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

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
		
		return 10 / pow( 10, Globals::get_stock_decimals() + 1 );
	}

}
