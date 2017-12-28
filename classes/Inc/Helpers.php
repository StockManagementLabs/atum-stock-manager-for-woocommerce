<?php
/**
 * @package        Atum
 * @subpackage     Inc
 * @author         Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      ©2017 Stock Management Labs™
 *
 * @since          0.0.1
 *
 * Helper functions
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumCapabilities;
use Atum\Components\AtumListTables\AtumListTable;
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
	 * Prepare HTML data attributes
	 *
	 * @since  0.0.1
	 *
	 * @param mixed  $att          The data attribute name (for as single data att) or an associative array for multiple atts
	 * @param string $value        The data attribute value. Optional for multiple atts (will be get from the $att array)
	 * @param string $quote_symbol Sometimes the quote symbol must be a single quote to allow json encoded values
	 *
	 * @return string
	 */
	public static function get_data_att( $att, $value = '', $quote_symbol = '"' ) {
		
		$data_att = '';
		if ( is_array( $att ) ) {
			foreach ( $att as $name => $value ) {
				// Recursive calls
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
	 * @param string     $att   The attribute name
	 * @param string|int $value The attribute value
	 * @param bool       $force Force the attribute output without checking if it's empty
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
	 * Returns an array with the orders filtered by the atts array
	 *
	 * @since 0.0.1
	 *
	 * @param array|string $atts {
	 *      Optional. Filters for the orders' query.
	 *
	 *      @type array|string  $type              Order post type(s)
	 *      @type array|string  $status            Order status(es)
	 *      @type array         $orders_in         Array of order's IDs we want to get
	 *      @type int           $number            Max number of orders (-1 gets all)
	 *      @type string        $meta_key          Key of the meta field to filter/order (depending of orderby value)
	 *      @type mixed         $meta_value        Value of the meta field to filter/order(depending of orderby value)
	 *      @type string        $meta_type         Meta key type. Default value is 'CHAR'
	 *      @type string        $meta_compare      Operator to test the meta value when filtering (See possible values: https://codex.wordpress.org/Class_Reference/WP_Meta_Query )
	 *      @type string        $order             ASC/DESC, default to DESC
	 *      @type string        $orderby           Field used to sort results (see WP_QUERY). Default to date (post_date)
	 *      @type int           $date_start        If has value, filters the orders between this and the $order_date_end (must be a string format convertible with strtotime)
	 *      @type int           $date_end          Requires $date_start. If has value, filters the orders completed/processed before this date (must be a string format convertible with strtotime). Default: Now
	 *      @type string        $fields            If empty will return all the order posts. For returning only IDs the value must be 'ids'
	 * }
	 *
	 * @return array
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
			'fields'       => ''
		) ) );

		/**
		 * Extract params
		 *
		 * @var array|string  $type
		 * @var array|string  $status
		 * @var array         $orders_in
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
		extract($atts);
		
		// WP_Query arguments
		$args = array(
			'offset' => 0
		);
		
		// Post Type
		$wc_order_types    = wc_get_order_types();
		$order_types       = (array) $type;
		$valid_order_types = array();
		
		// Validate order types
		foreach ( $order_types as $ot ) {
			if ( in_array( $ot, $wc_order_types ) ) {
				$valid_order_types[] = $ot;
			}
		}
		
		$args['post_type'] = $valid_order_types;
		
		// Order Status
		$valid_order_statuses = array();
		$wc_order_statuses    = array_keys( wc_get_order_statuses() );
		$order_statuses       = (array) $status;
		
		// Validate post statuses
		foreach ( $order_statuses as $os ) {
			if ( in_array( $os, $wc_order_statuses ) ) {
				$valid_order_statuses[] = $os;
			}
		}
		
		$args['post_status'] = ( ! empty( $valid_order_statuses ) ) ? $valid_order_statuses : $wc_order_statuses;
		
		// Selected posts
		if ( $orders_in ) {
			
			if ( ! is_array( $orders_in ) ) {
				$orders_in = explode( ',', $orders_in );
			}
			
			$args['post__in'] = array_map( 'absint', $orders_in );
		}
		
		$args['posts_per_page'] = intval($number);
		
		// Filter/Order by meta key
		if ( $meta_key ) {
			
			$meta_query = array(
				'key' => esc_attr( $meta_key )
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
			$args['order'] = ( in_array( $order, [ 'ASC', 'DESC' ] ) ) ? $order : 'DESC';
		}
		
		if ( ! empty( $orderby ) ) {
			$args['orderby'] = esc_attr( $orderby );
		}

		// Filter by date
		if ( $date_start ) {

			$args['date_query'][] = array(
				'after'     => $date_start,
				'before'    => $date_end ?: 'now',
				'inclusive' => TRUE
			);
			
		}
		
		// Return only ID's
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
					// We need the WooCommerce order, not the post
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
	 * @param array  $items      Array of Product IDs we want to calculate sales from
	 * @param int    $date_start The date from when to start the items' sales calculations (must be a string format convertible with strtotime)
	 * @param int    $date_end   Optional. The max date to calculate the items' sales (must be a string format convertible with strtotime)
	 *
	 * @return array
	 */
	public static function get_sold_last_days( $items, $date_start, $date_end = NULL ) {

		$items_sold = array();

		if ( ! empty($items) ) {

			global $wpdb;

			// Prepare the SQL query to get the orders in the specified time window
			$date_start = date_i18n( 'Y-m-d H:i:s', strtotime($date_start) );
			$date_where = $wpdb->prepare("WHERE post_date >= %s", $date_start);

			if ($date_end) {
				$date_end   = date_i18n( 'Y-m-d H:i:s', strtotime( $date_end ) );
				$date_where .= $wpdb->prepare( " AND post_date <= %s", $date_end );
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

			$items_sold = $wpdb->get_results( $query, ARRAY_A );

		}

		return $items_sold;

	}

	/**
	 * Get the lost sales of a specified product during the last days
	 *
	 * @since 1.2.3
	 *
	 * @param int $product_id   The product ID to calculate the lost sales
	 * @param int $days         Optional. By default the calculation is made for 7 days average
	 *
	 * @return bool|float       Returns the lost sales or FALSE if never had lost sales
	 */
	public static function get_product_lost_sales ($product_id, $days = 7) {

		$lost_sales = FALSE;
		$out_of_stock_date = get_post_meta( $product_id, Globals::get_out_of_stock_date_key(), TRUE );

		if ($out_of_stock_date && $days > 0) {

			$days_out_of_stock = self::get_product_out_of_stock_days($product_id);

			if ( is_numeric( $days_out_of_stock ) ) {

				// Get the average sales for the past days when in stock
				$days = absint($days);
				$sold_last_days = self::get_sold_last_days( [ $product_id ], $out_of_stock_date . " -{$days} days", $out_of_stock_date );
				$lost_sales = 0;

				if ( ! empty($sold_last_days) ) {
					$sold_last_days = reset($sold_last_days);

					if ( ! empty($sold_last_days['QTY']) && $sold_last_days['QTY'] > 0 ) {

						$average_sales = $sold_last_days['QTY'] / $days;
						$product = wc_get_product($product_id);
						$price = $product->get_regular_price();

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
	 * @param int $product_id   The product ID
	 *
	 * @return bool|int         Returns the number of days or FALSE if is not "Out of Stock"
	 */
	public static function get_product_out_of_stock_days ($product_id) {

		$out_of_stock_days = FALSE;

		// Check if the current product has the "Out of stock" date recorded
		$out_of_stock_date = get_post_meta($product_id, Globals::get_out_of_stock_date_key(), TRUE );

		if ( $out_of_stock_date ) {
			$out_date_time = new \DateTime( $out_of_stock_date );
			$now_date_time = new \DateTime( 'now' );
			$interval      = date_diff( $out_date_time, $now_date_time );

			$out_of_stock_days = $interval->days;
		}

		return $out_of_stock_days;

	}

	/**
	 * Get the price formatted with no HTML tags
	 *
	 * @since 1.2.3
	 *
	 * @param float $price  The price number to format
	 * @param array $args   The format configuration array
	 *
	 * @return string
	 */
	public static function format_price ($price, $args = array()) {

		// Do not add zeros as decimals
		if ( ! empty( $args['trim_zeros'] ) && $args['trim_zeros'] == TRUE ) {
			add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
		}

		return apply_filters('atum/format_price', strip_tags( wc_price( round($price, 2), $args ) ) );

	}
	
	/**
	 * Set a transient adding ATUM stuff to unequivocal identify it
	 *
	 * @since 0.0.2
	 *
	 * @param   string $transient  Transient simple name
	 * @param   mixed  $value      Value to store
	 * @param   int    $expiration Time until expiration in seconds
	 *
	 * @return  bool  False if value was not set and true if value was set.
	 *
	 */
	public static function set_transient( $transient, $value, $expiration = 0 ) {
		
		return (ATUM_DEBUG !== TRUE) ? set_transient( ATUM_PREFIX . $transient, $value, $expiration ) : FALSE;
	}
	
	/**
	 * Get a transient adding ATUM stuff to unequivocal identify it
	 *
	 * @since 0.0.2
	 *
	 * @param   string $transient Transient simple name
	 *
	 * @return  mixed|bool  The atum transient value or false if the transient does not exist or debug mode is on
	 */
	public static function get_transient( $transient ) {
		
		return (ATUM_DEBUG !== TRUE) ? get_transient( ATUM_PREFIX . $transient ) : FALSE;
	}
	
	/**
	 * Get md5 hash of a array of args to create unique transient identifier
	 *
	 * @since 0.0.3
	 *
	 * @param   array $args The args to hash
	 *
	 * @return  string
	 */
	public static function get_transient_identifier( $args = array() ) {
		
		return md5( serialize( $args ) );
	}
	
	/**
	 * Delete all the ATUM transients
	 *
	 * @since 0.1.5
	 *
	 * @return int|bool The number of transients deleted on success or false on error
	 */
	public static function delete_transients() {
		
		global $wpdb;
		return $wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_" . ATUM_PREFIX . "%'" );
	}
	
	/**
	 * Display the template for the given view
	 *
	 * @since 0.0.2
	 *
	 * @param string $view                  View file that should be loaded
	 * @param array  $args                  Optional. Variables that will be passed to the view
	 * @param bool   $allow_theme_override  Optional. Allow overriding views from the theme
	 *
	 * @return void|bool
	 */
	public static function load_view( $view, $args = [ ], $allow_theme_override = TRUE ) {
		
		$file = apply_filters( "atum/load_view/$view", $view );
		$args = apply_filters( "atum/load_view_args/$view", $args );
		
		// whether or not .php was added
		if ( substr( $file, - 4 ) != '.php' ) {
			$file .= '.php';
		}

		if ( $allow_theme_override ) {
			$file = self::locate_template( array( $view ), $file );
		}

		// Allow using full paths as view name
		if ( is_file($file) ) {
			$file_path = $file;
		}
		else {

			$file_path = ATUM_PATH . "views/$file";

			if ( ! is_file( $file_path ) ) {
				return FALSE;
			}

		}
		
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}
		
		if ( ATUM_DEBUG ) {
			include $file_path;
		}
		else {
			@include $file_path;
		}
		
	}
	
	/**
	 * Get the template for the given view and return it as string
	 *
	 * @since 0.0.1
	 *
	 * @param string $view                  View file that should be loaded
	 * @param array  $args                  Optional. Variables that will be passed to the view
	 * @param bool   $allow_theme_override  Optional. Allow overriding views from the theme
	 *
	 * @return View template string
	 */
	public static function load_view_to_string( $view, $args = [ ], $allow_theme_override = TRUE ) {
		
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @since 1.3.3
	 *
	 * @param array   $possibilities
	 * @param string  $default
	 *
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {

		$possibilities = apply_filters( 'atum/locate_template/possibilities', $possibilities );

		// Check if the theme has an override for the template
		$theme_overrides = array();

		foreach ( $possibilities as $p ) {
			$theme_overrides[] = Globals::TEMPLATE_DIR . "/$p";
		}

		if ( $found = locate_template( $theme_overrides, FALSE ) ) {
			return $found;
		}

		// Check for it in the public directory
		foreach ( $possibilities as $p ) {

			if ( file_exists( ATUM_PATH . "views/$p" ) ) {
				return ATUM_PATH . "views/$p";
			}

		}

		// Not template found
		return $default;
	}
	
	/**
	 * Helper function to return a plugin option value.
	 * If no value has been saved, it returns $default.
	 * Needed because options are saved as serialized strings.
	 *
	 * @since   0.0.2
	 *
	 * @param string  $name    The option key to retrieve
	 * @param mixed   $default The default value returned if the option was not found
	 * @param boolean $echo    If the option has to be returned or printed
	 *
	 * @return mixed
	 */
	public static function get_option( $name, $default = FALSE, $echo = FALSE ) {
		
		// Save it as a global variable to not get the value each time
		global $global_options;
		
		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites
		$global_options = ( empty( $global_options ) ) ? get_option( Settings::OPTION_NAME ) : $global_options;
		$option         = ( isset( $global_options[ $name ] ) ) ? $global_options[ $name ] : $default;
		
		if ( $echo ) {
			echo apply_filters( "atum/print_option/$name", $option );
			
			return;
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
		
		// Save it as a global variable to not get the value each time
		global $global_options;
		
		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites
		$global_options = ( empty( $global_options ) ) ? get_option( Settings::OPTION_NAME ) : $global_options;
		
		if ( ! $global_options ) {
			$global_options = array();
		}
		
		return apply_filters( 'atum/get_options', $global_options );
		
	}

	/**
	 * Checks if ATUM is currently managing the WC stock
	 *
	 * @since 1.2.6
	 *
	 * @return bool
	 */
	public static function is_atum_managing_stock() {
		return self::get_option( 'manage_stock', 'no' ) == 'yes';
	}
	
	/**
	 * Decode a JSON object stringified
	 *
	 * @since 0.0.3
	 *
	 * @param string $string   The string to decode
	 * @param bool   $as_array If return an associative array or an object
	 *
	 * @return array|object|bool
	 */
	public static function decode_json_string( $string, $as_array = TRUE ) {
		
		return json_decode( str_replace( "'", '"', stripslashes( $string ) ), $as_array );
	}
	
	/**
	 * Helper function to update a theme plugin value.
	 *
	 * @since   0.0.2
	 *
	 * @param string $name  The option key tu update
	 * @param mixed  $value The option value
	 *
	 */
	public static function update_option( $name, $value ) {
		
		// Save it as a global variable to not get the value each time
		global $global_options;
		
		// The option key it's built using ADP_PREFIX and theme slug to avoid overwrites
		$global_options = ( empty( $global_options ) ) ? get_option( Settings::OPTION_NAME ) : $global_options;
		
		$old_value = ( isset( $global_options[ $name ] ) ) ? $global_options[ $name ] : FALSE;
		
		$global_options[ $name ] = apply_filters( "atum/update_option/$name", $value, $old_value );
		
		update_option( Settings::OPTION_NAME, $global_options );
		
	}
	
	/**
	 * Activate ATUM Management Stock Option
	 *
	 * @since 0.1.0
	 */
	public static function activate_manage_stock_option() {
		
		$product_types = Globals::get_product_types();
		$post_types = ( in_array('variable', $product_types) || in_array('variable-subscription', $product_types) ) ? array('product', 'product_variation') : 'product';
		
		// Save the options
		// Don't take care of the type product. They never have "yes"
		$args = array(
			'post_type'      => $post_types,
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_manage_stock',
					'compare' => 'NOT EXISTS',
					'value'   => ''
				),
				array(
					'key'   => '_manage_stock',
					'value' => 'no'
				)
			),
			// The external products are not stockable
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'external',
					'operator' => 'NOT IN'
				)
			)
		);
		
		$products = new \WP_Query( $args );
		
		update_option( ATUM_PREFIX . 'restore_option_stock', $products->posts );
		
		// Set all products to yes
		foreach ( $products->posts as $product ) {
			update_post_meta( $product, '_manage_stock', 'yes' );
		}
	}
	
	/**
	 * Format a date to match the db format
	 *
	 * @since 0.1.3
	 *
	 * @param string|int $date         The date to format. Can be an English date or a timestamp (with second param as true)
	 * @param bool       $is_timestamp Whether the first param is a Unix timesptamp
	 *
	 * @return string                   The formatted date
	 */
	public static function date_format( $date, $is_timestamp = FALSE ) {
		
		if ( ! $is_timestamp ) {
			$date = strtotime( $date );
		}
		
		return date_i18n( 'Y-m-d H:i:s', $date );
	}

	/**
	 * Check whether a specific plugin is installed
	 *
	 * @since 1.2.0
	 *
	 * @param string $plugin        The plugin name/slug
	 * @param string $by            Optional. It can be cheched by 'slug' or by 'name'
	 * @param bool   $return_bool   Optional. May return a boolean (true/false) or an associative array with the plugin data
	 *
	 * @return bool|array
	 */
	public static function is_plugin_installed ($plugin, $by = 'slug', $return_bool = TRUE) {

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {

			// Get the plugin slug from its path
			if ($by == 'slug') {
				$installed_plugin_key = explode( DIRECTORY_SEPARATOR, $plugin_file )[0];
			}
			else {
				$installed_plugin_key = $plugin_data['Title'];
			}

			if ($installed_plugin_key == $plugin) {
				return ($return_bool) ? TRUE : array($plugin_file => $plugin_data);
			}
		}

		return FALSE;

	}
	
	/**
	 * Add a notice to the list of dismissed notices for the current user
	 *
	 * @since 1.1.1
	 *
	 * @param string $notice    The notice key
	 *
	 * @return int|bool
	 */
	public static function dismiss_notice ($notice) {
		
		$current_user_id = get_current_user_id();
		$user_dismissed_notices = self::get_dismissed_notices($current_user_id);
		$user_dismissed_notices[$notice] = 'yes';
		
		return update_user_meta($current_user_id, AtumListTable::DISMISSED_NOTICES, $user_dismissed_notices);
	}
	
	/**
	 * Get the list of ATUM's dismissed notices for the current user
	 *
	 * @since 1.1.1
	 *
	 * @param int $user_id  The ID of the user to retrieve the dismissed notices from
	 *
	 * @return array|bool
	 */
	public static function get_dismissed_notices ($user_id = NULL) {
		$user_id = ($user_id) ? absint($user_id) : get_current_user_id();
		return apply_filters( 'atum/dismissed_notices', get_user_meta($user_id, AtumListTable::DISMISSED_NOTICES, TRUE) );
	}

	/**
	 * Check whether or not register the ES6 promise script
	 * This is only required for SweetAlert2 on IE<12
	 *
	 * @since 1.2.0
	 */
	public static function maybe_es6_promise () {

		global $is_IE;
		// ES6 Polyfill (only for IE<12). Required by SweetAlert2
		if ($is_IE){
			$version = array();
			preg_match("/MSIE ([0-9]{1,}[\.0-9]{0,})/", $_SERVER['HTTP_USER_AGENT'], $version);
			if ( ! empty($version) && intval($version[1]) < 12 ) {
				wp_register_script( 'es6-promise', ATUM_URL . 'assets/js/vendor/es6-promise.auto.min.js', [], ATUM_VERSION, TRUE );
			}
		}

	}

	/**
	 * Trim inputs and arrays
	 *
	 * @since 1.2.4
	 *
	 * @param  string/array $value value/s to trim
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
					$return[$k] = $v;
					continue;
				}

				$return[$k] = is_array( $v ) ? self::trim_input( $v ) : trim( $v );

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
	 * @param string $selected  The pre-selected option
	 * @param string $class     The dropdown class name
	 *
	 * @return string
	 */
	public static function product_types_dropdown($selected = '', $class = 'dropdown_product_type') {

		$terms = get_terms( 'product_type' );
		$allowed_types = apply_filters( 'atum/product_types_dropdown/allowed_types', Globals::get_product_types() );

		$output  = '<select name="product_type" class="' . $class . '">';
		$output .= '<option value=""' . selected($selected, '', FALSE) . '>' . __( 'Show all product types', ATUM_TEXT_DOMAIN ) . '</option>';

		foreach ( $terms as $term ) {

			if ( ! in_array($term->slug, $allowed_types) ) {
				continue;
			}

			$output .= '<option value="' . sanitize_title( $term->name ) . '"' . selected( $term->slug, $selected, FALSE ) . '>';

			switch ( $term->name ) {
				case 'grouped' :
					$output .= __( 'Grouped product', ATUM_TEXT_DOMAIN );
					break;

				case 'variable' :
					$output .= __( 'Variable product', ATUM_TEXT_DOMAIN );
					break;

				case 'simple' :
					$output .= __( 'Simple product', ATUM_TEXT_DOMAIN );
					break;

				// Assuming that we'll have other types in future
				default :
					$output .= ucfirst( $term->name );
					break;
			}

			$output .= '</option>';

			if ( 'simple' == $term->name ) {

				$output .= '<option value="downloadable"' . selected( 'downloadable', $selected, FALSE ) . '> &rarr; '
				           . __( 'Downloadable', ATUM_TEXT_DOMAIN ) . '</option>';

				$output .= '<option value="virtual"' . selected( 'virtual', $selected, FALSE ) . '> &rarr; '
				           . __( 'Virtual', ATUM_TEXT_DOMAIN ) . '</option>';
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
	 * @param string $selected  Optional. The pre-selected option
	 * @param bool   $enhanced  Optional. Whether to show an enhanced select
	 * @param string $class     Optional. The dropdown class name
	 *
	 * @return string
	 */
	public static function suppliers_dropdown($selected = '', $enhanced = FALSE, $class = 'dropdown_supplier') {

		if ( ! ModuleManager::is_module_active('purchase_orders') || ! AtumCapabilities::current_user_can('read_supplier') ) {
			return '';
		}

		ob_start();

		if (!$enhanced):

			$args = array(
				'post_type' => Suppliers::POST_TYPE,
				'posts_per_page' => -1

			);

			$suppliers = get_posts( $args );

			if ( empty($suppliers) ) {
				ob_end_flush();
				return '';
			}
			?>

			<select name="supplier" class="<?php echo $class ?>">
				<option value=""<?php selected( $selected, '' ) ?>><?php _e( 'Show all suppliers', ATUM_TEXT_DOMAIN ) ?></option>

				<?php foreach ( $suppliers as $supplier ): ?>
					<option value="<?php echo $supplier->ID ?>"<?php selected( $supplier->ID, $selected ) ?>><?php echo $supplier->post_title ?></option>
				<?php endforeach; ?>
			</select>

		<?php else : ?>

			<select class="wc-product-search <?php echo $class ?>" id="supplier" name="supplier" data-allow_clear="true"
					data-action="atum_json_search_suppliers" data-placeholder="<?php esc_attr_e( 'Search Supplier&hellip;', ATUM_TEXT_DOMAIN ); ?>"
					data-multiple="false" data-selected="" data-minimum_input_length="1" style="width: 180px">
				<?php if ( $selected ): $supplier = get_post($selected)?>
					<option value="<?php echo $selected ?>" selected="selected"><?php echo $supplier->post_title ?></option>
				<?php endif; ?>
			</select>

			<?php
			wp_enqueue_script('wc-enhanced-select');

		endif;

		return ob_get_clean();

	}

	/**
	 * Output a dropdown to choose the ATUM Order status
	 *
	 * @since 1.2.9
	 *
	 * @param string $id        The select ID
	 * @param string $value     The selected option
	 */
	public static function atum_order_status_dropdown($id, $value) {

		?>
		<select id="<?php echo $id ?>" name="<?php echo $id ?>" class="wc-enhanced-select">
			<?php
			$statuses = AtumOrderPostType::get_statuses();
			foreach ( $statuses as $status => $status_name ): ?>
				<option value="<?php echo esc_attr( $status ) ?>"<?php selected( $status, $value ) ?>><?php echo esc_html( $status_name ) ?></option>
			<?php endforeach; ?>
		</select>
		<?php

	}

	/**
	 * Get the inventory log's IDs
	 *
	 * @since 1.2.8
	 *
	 * @param string $type      The log type to query. Values: 'reserved-stock', 'customer-returns', 'warehouse-damage', 'lost-in-post', 'other'
	 * @param string $status    Optional. The log status. Values: 'pending', 'completed'
	 *
	 * @return array|bool
	 */
	public static function get_logs($type, $status = '') {

		// Filter by log type meta key
		$log_types = Log::get_types();

		if ( ! in_array( $type, array_keys($log_types) ) ) {
			return FALSE;
		}

		$args = array(
			'post_type'      => InventoryLogs::POST_TYPE,
			'posts_per_page' => - 1,
			'fields'         => 'ids',
			'meta_query' =>  array(
				array(
					'key'   => '_type',
					'value' => $type
				)
			)
		);

		// Filter by log status
		if ($status) {
			if ( strpos( $status, ATUM_PREFIX ) === FALSE ) {
				$status = ATUM_PREFIX . $status;
			}

			$args['post_status'] = $status;
		}
		else {
			$args['post_status'] = 'any';
		}

		return get_posts( apply_filters('atum/get_logs_args', $args) );

	}

	/**
	 * Get all the order items within a specified ATUM Order
	 *
	 * @since 1.2.9
	 *
	 * @param int $order_id   The ATUM Order ID
	 *
	 * @return object|null
	 */
	public static function get_order_items($order_id) {

		global $wpdb;
		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}" . AtumOrderPostType::ORDER_ITEMS_TABLE . " WHERE order_id = %d ORDER BY order_item_id", $order_id );

		return $wpdb->get_results( $query );

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
	public static function get_atum_order_model($atum_order_id) {

		$post_type = get_post_type($atum_order_id);

		switch ( $post_type ) {
			case InventoryLogs::POST_TYPE:

				$model_class = '\Atum\InventoryLogs\Models\Log';
				break;

			case PurchaseOrders::POST_TYPE:

				$model_class = '\Atum\PurchaseOrders\Models\PurchaseOrder';
				break;
		}

		if ( ! isset($model_class) || ! class_exists($model_class) ) {
			return new \WP_Error( 'invalid_post_type', __( 'No valid ID provided', ATUM_TEXT_DOMAIN ) );
		}

		return new $model_class( $atum_order_id );

	}

	/**
	 * Set the help tab for admin pages
	 *
	 * @since 1.3.0
	 *
	 * @param array  $help_tabs
	 * @param Object $obj
	 */
	public static function add_help_tab($help_tabs, $obj) {

		$screen = get_current_screen();

		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab( array_merge( array(
				'id'       => ATUM_PREFIX . get_class($obj) . '_help_tabs_' . $help_tab['name'],
				'callback' => array( $obj, 'help_tabs_content' ),
			), $help_tab ) );
		}

		$screen->set_help_sidebar( self::load_view_to_string( 'help-tabs/help-sidebar' ) );

	}
	
	/**
	 * Update product meta translations if WPML is active
	 *
	 * @since 1.3.0
	 *
	 * @param int $product_id
	 * @param array $product_meta
	 */
	public static function maybe_synchronize_translations_wpml( $product_id, &$product_meta) {
	
		if ( ! class_exists('\woocommerce_wpml')) {
			return;
		}
		
		global $sitepress;
		
		
		$trid = $sitepress->get_element_trid( $product_id, 'post_product' );
		$translations = $sitepress->get_element_translations( $trid, 'post_product', false, true );
		
		foreach( $translations as $translation ) {
			if ( $translation->element_id != $product_id ) {
				self::update_product_meta( $translation->element_id, $product_meta) ;
			}
		}
	}
	
	/**
	 * Update product meta from Stock Central List
	 *
	 * @since 1.3.0
	 *
	 * @param int   $product_id
	 * @param array $product_meta
	 */
	public static function update_product_meta( $product_id, $product_meta ) {
		
		$product               = wc_get_product( $product_id );
		$is_wpml_multicurrency = FALSE;
		$original_product_id   = $product_id;
		
		if ( ! $product || ! is_a( $product, '\WC_Product' ) ) {
			return;
		}

		// Add WPML compatibility
		if ( class_exists( '\woocommerce_wpml' ) ) {
			
			$wpml = \woocommerce_wpml::instance();
			global $sitepress;
			
			if ( $wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

				$is_wpml_multicurrency = TRUE;
				$post_type             = get_post_type( $product_id );
				$product_translations  = $sitepress->get_element_translations( $sitepress->get_element_trid( $product_id, 'post_' . $post_type ), 'post_' . $post_type );

				foreach ( $product_translations as $translation ) {

					if ( $translation->original ) {
						$original_product_id = $translation->element_id;
						break;
					}

				}
				
			}

		}
		
		foreach ( $product_meta as $meta_key => &$meta_value ) {
			
			$meta_key = esc_attr( $meta_key );
			
			switch ( $meta_key ) {
				
				case 'stock':
					
					unset( $product_meta['stock_custom'], $product_meta['stock_currency'] );
					$product->set_stock_quantity( $meta_value );
					
					// Needed to clear transients and other stuff
					do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product );
					
					break;
				
				case 'regular_price':
					
					if ( isset($wpml) && $is_wpml_multicurrency && isset( $product_meta['regular_price_custom'] ) && $product_meta['regular_price_custom'] == 'yes' ) {
							
						$custom_prices                   = $wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_meta['regular_price_currency'] );
						$custom_prices['_regular_price'] = $meta_value;

						$wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_meta['regular_price_currency'] );

					}
					else {

						$product->set_regular_price( $meta_value );
						
						if ( $meta_key == 'regular_price' && ! $product->is_on_sale( 'edit' ) ) {
							$product->set_price( $meta_value );
						}

					}
					
					unset( $product_meta['regular_price_custom'], $product_meta['regular_price_currency'] );
					
					break;
				
				case 'sale_price':
					
					if ( isset($wpml) && $is_wpml_multicurrency && isset( $product_meta['sale_price_custom'] ) && $product_meta['sale_price_custom'] == 'yes' ) {
						
						$custom_prices                = $wpml->multi_currency->custom_prices->get_product_custom_prices( $product_id, $product_meta['sale_price_currency'] );
						$custom_prices['_sale_price'] = $meta_value;
						
						if ( isset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] ) ) {
							
							$date_from = wc_clean( $product_meta['_sale_price_dates_from'] );
							$date_to   = wc_clean( $product_meta['_sale_price_dates_to'] );
							
							$custom_prices['_sale_price_dates_from'] = ( $date_from ? strtotime( $date_from ) : '' );
							$custom_prices['_sale_price_dates_to']   = ( $date_to ? strtotime( $date_to ) : '' );
							
							// Ensure these meta keys are not handled on next iterations
							unset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] );
						}
						
						$wpml->multi_currency->custom_prices->update_custom_prices( $original_product_id, $custom_prices, $product_meta['sale_price_currency'] );
						
					}
					else {

						$sale_price    = wc_format_decimal( $meta_value );
						$regular_price = $product->get_regular_price();
						
						// The sale price cannot be higher than the regular price
						if ( $regular_price >= $sale_price ) {
							$product->set_sale_price( $sale_price );
						}
						
						// Check for sale dates
						if ( isset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] ) ) {
							
							$date_from = wc_clean( $product_meta['_sale_price_dates_from'] );
							$date_to   = wc_clean( $product_meta['_sale_price_dates_to'] );
							
							$product->set_date_on_sale_from( $date_from ? strtotime( $date_from ) : '' );
							$product->set_date_on_sale_to( $date_to ? strtotime( $date_to ) : '' );
							
							// Ensure these meta keys are not handled on next iterations
							unset( $product_meta['_sale_price_dates_from'], $product_meta['_sale_price_dates_to'] );
							
							if ( $date_to && ! $date_from ) {
								$date_from = date( 'Y-m-d' );
								$product->set_date_on_sale_from( strtotime( $date_from ) );
							}
							
							// Update price if on sale
							if ( $product->is_on_sale( 'edit' ) ) {
								$product->set_price( $sale_price );
							} else {
								$product->set_price( $regular_price );
								
								if ( $date_to && strtotime( $date_to ) < current_time( 'timestamp' ) ) {
									$product->set_date_on_sale_from( '' );
									$product->set_date_on_sale_to( '' );
								}
							}
							
						}

					}
					
					unset( $product_meta['sale_price_custom'], $product_meta['sale_price_currency'] );
					
					break;
				
				case 'purchase_price':
					
					if (  $is_wpml_multicurrency && isset( $product_meta['purchase_price_custom'] ) && $product_meta['purchase_price_custom'] == 'yes' ) {
						update_post_meta( $original_product_id, '_' . $meta_key . '_' . $product_meta['purchase_price_currency'], wc_format_decimal( $meta_value ) );
					}
					else {
						update_post_meta( $product_id, '_' . $meta_key, wc_format_decimal( $meta_value ) );
					}
					
					unset( $product_meta['purchase_price_custom'], $product_meta['purchase_price_currency'] );
					break;
				
				// Any other text meta
				default:
					
					update_post_meta( $product_id, '_' . $meta_key, esc_attr( $meta_value ) );
					unset( $product_meta[ '_' . $meta_key . '_custom' ], $product_meta[ '_' . $meta_key . 'currency' ] );
					break;
			}
			
		}
		
		// Hack to prevent overwriting the purchase_price on variations
		remove_action( 'woocommerce_update_product_variation', array(Main::get_instance(), 'save_purchase_price') );
		
		$product->save();
		
	}
	
}