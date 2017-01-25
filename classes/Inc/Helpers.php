<?php
/**
 * @package           Atum
 * @subpackage        Inc
 * @author            Salva Machí and Jose Piera - https://sispixels.com
 * @copyright      (c)2017 Stock Management Labs
 *
 * @since             0.0.1
 *
 * Helper functions
 */

namespace Atum\Inc;

defined( 'ABSPATH' ) or die;

use Atum\Components\AtumListTable;
use Atum\Settings\Settings;


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
	 * Returns an array with the orders filtered by the atts array attributes
	 *
	 * @since 0.0.1
	 *
	 * @param array|string    $atts                 optional {
	 *
	 * @type string           $order_type           Comma-separated list of order's post types
	 * @type string           $order_status         Comma-separated list of order's post statuses
	 * @type mixed            $orders_in            Array, integer o comma-separated list of order's IDs we want to get
	 * @type int              $number_orders        Max number of orders (-1 gets all)
	 * @type string           $meta_key             Key of the meta field to filter/order (depending of orderby value)
	 * @type string|int|float $meta_value           Value of the meta field to filter/order(depending of orderby value)
	 * @type string           $meta_type            Meta key type. Default value is 'CHAR'
	 * @type string           $meta_compare         Operator to test the meta value when filtering (', '!=', '>', '>=', '<', '<=',
	 *                                                   'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'NOT EXISTS',
	 *                                                   'REGEXP', 'NOT REGEXP' or 'RLIKE'. Default value is '='.)
	 * @type string           $order                ASC/DESC, default to DESC
	 * @type string           $orderby              Field used to sort results (see WP_QUERY). Default to date (post_date)
	 * @type string           $completed_date_start If has value returns order with the meta _completed_date after the value
	 * @type string           $completed_date_end   Needs completed_date_start. If has value returns order with the
	 *                                                   meta _completed_date before the date (default next day)
	 * }
	 *
	 * @param boolean         $return_ids           If TRUE, returns an array of ID's (default FALSE)
	 *
	 * @return array
	 */
	public static function get_orders( $atts = array(), $return_ids = FALSE ) {
		
		extract( (array) apply_filters( 'atum/helpers/get_orders/params', wp_parse_args( $atts, array(
			'order_type'           => '',
			'post_status'          => '',
			'orders_in'            => '',
			'number_orders'        => - 1,
			'meta_key'             => '',
			'meta_value'           => '',
			'meta_type'            => '',
			'meta_compare'         => '',
			'order'                => '',
			'orderby'              => '',
			'completed_date_start' => '',
			'completed_date_end'   => '',
		) ) ) );
		
		// WP_Query arguments
		$args = array(
			'offset' => 0
		);
		
		// Post Type
		$order_types       = explode( ', ', $order_type );
		$wc_order_types    = wc_get_order_types();
		$valid_order_types = array();
		
		// Validate order types
		foreach ( $order_types as $ot ) {
			if ( in_array( $ot, $wc_order_types ) ) {
				$valid_order_types[] = $ot;
			}
		}
		
		$args['post_type'] = ( ! empty( $valid_order_types ) ) ? $valid_order_types : $wc_order_types;
		
		// Post Status
		$order_statuses       = explode( ', ', $order_status );
		$valid_order_statuses = array();
		$wc_order_statuses    = array_keys( wc_get_order_statuses() );
		
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
		
		$args['posts_per_page'] = ( $number_orders ) ? $number_orders : - 1;
		
		// Filter/Order by a meta key
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
		
		// WooCommerce adds the _completed_date meta key to the orders once are completed, so we can use it to filter by date
		$completed_date_start = strtotime( $completed_date_start );
		if ( $completed_date_start ) {
			
			$completed_date_end = strtotime( $completed_date_end );
			
			if ( $completed_date_end ) {
				
				$args['meta_query'][] = array(
					'key'     => '_completed_date',
					'compare' => 'BETWEEN',
					'value'   => array(
						self::date_format( $completed_date_start, TRUE ),
						self::date_format( $completed_date_end, TRUE )
					),
					'type'    => 'DATETIME'
				);
				
			}
			else {
				
				$args['meta_query'][] = array(
					'key'     => '_completed_date',
					'compare' => '>=',
					'value'   => self::date_format( $completed_date_start, TRUE ),
					'type'    => 'DATETIME'
				);
				
			}
			
		}
		
		//Return only ID's
		if ( $return_ids ) {
			$args['fields'] = 'ids';
		}
		
		$result = array();
		$query  = new \WP_Query( $args );
		
		if ( $query->post_count > 0 ) {
			
			if ( $return_ids ) {
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
	 * @param string $view View file that should be loaded
	 * @param array  $args Variables that will be passed to the view
	 *
	 * @return void|bool
	 */
	public static function load_view( $view, $args = [ ] ) {
		
		$file = apply_filters( "atum/helpers/load_view/$view", $view );
		$args = apply_filters( "atum/helpers/load_view_args/$view", $args );
		
		// whether or not .php was added
		if ( substr( $file, - 4 ) != '.php' ) {
			$file .= '.php';
		}
		
		$file_path = ATUM_PATH . "views/$file";
		
		if ( ! is_file( $file_path ) ) {
			return FALSE;
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
	 * @param string $view View file that should be loaded
	 * @param array  $args Variables that will be passed to the view
	 *
	 * @return View template string
	 */
	public static function load_view_to_string( $view, $args = [ ] ) {
		
		ob_start();
		self::load_view( $view, $args );
		
		return ob_get_clean();
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
			echo apply_filters( "atum/helpers/print_option/$name", $option );
			
			return;
		}
		
		return apply_filters( "atum/helpers/get_option/$name", $option );
		
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
		
		return apply_filters( 'atum/helpers/get_options', $global_options );
		
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
		
		$global_options[ $name ] = apply_filters( "atum/helpers/update_option/$name", $value, $old_value );
		
		update_option( Settings::OPTION_NAME, $global_options );
		
	}
	
	/**
	 * Activate ATUM Management Stock Option
	 *
	 * @since 0.1.0
	 */
	public static function activate_manage_stock_option() {
		
		$product_types = Globals::get_product_types();
		$post_types = ( in_array('variable', $product_types) ) ? array('product', 'product_variation') : 'product';
		
		// Save the options
		// Don't mind type product. They never have yes
		$args     = array(
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
	
}