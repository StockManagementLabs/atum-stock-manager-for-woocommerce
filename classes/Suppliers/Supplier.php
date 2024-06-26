<?php
/**
 * The Supplier model class
 *
 * @since       1.6.8
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Suppliers
 */

namespace Atum\Suppliers;

defined( 'ABSPATH' ) || die;

use Atum\Models\AtumCPTModel;


/**
 * Class Supplier
 *
 * @property string    $address
 * @property string    $address_2
 * @property int       $assigned_to
 * @property string    $atum_barcode
 * @property string    $cancelation_policy
 * @property int       $days_to_cancel
 * @property string    $delivery_terms
 * @property string    $description
 * @property int|float $discount
 * @property string    $city
 * @property string    $code
 * @property string    $country
 * @property string    $currency
 * @property string    $fax
 * @property string    $general_email
 * @property int       $id
 * @property int       $lead_time
 * @property string    $location
 * @property string    $name
 * @property string    $ordering_email
 * @property string    $ordering_url
 * @property string    $phone
 * @property string    $state
 * @property string    $tax_number
 * @property int|float $tax_rate
 * @property int       $thumbnail_id
 * @property string    $use_default_description
 * @property string    $use_default_terms
 * @property string    $website
 * @property string    $wpml_lang
 * @property string    $zip_code
 */
class Supplier extends AtumCPTModel {

	/**
	 * Stores the supplier's data
	 *
	 * @var array
	 */
	protected $data = array(
		'address'                 => '',
		'address_2'               => '',
		'assigned_to'             => NULL,
		'atum_barcode'            => '', // NOTE: It must have this name to be compatible with Barcodes PRO.
		'cancelation_policy'      => '',
		'days_to_cancel'          => NULL,
		'delivery_terms'          => '',
		'description'             => '',
		'discount'                => NULL,
		'city'                    => '',
		'code'                    => '',
		'country'                 => '',
		'currency'                => '',
		'fax'                     => '',
		'general_email'           => '',
		'lead_time'               => NULL,
		'location'                => '',
		'name'                    => '',
		'ordering_email'          => '',
		'ordering_url'            => '',
		'phone'                   => '',
		'state'                   => '',
		'tax_number'              => '',
		'tax_rate'                => NULL,
		'thumbnail_id'            => NULL,
		'use_default_description' => 'yes',
		'use_default_terms'       => 'yes',
		'website'                 => '',
		'wpml_lang'               => '',
		'zip_code'                => '',
	);

	/**********
	 * SETTERS
	 **********/

	/**
	 * Set the supplier code
	 *
	 * @since 1.6.8
	 *
	 * @param string $code
	 */
	public function set_code( $code ) {

		$code = esc_attr( $code );

		if ( $this->data['code'] !== $code ) {
			$this->data['code'] = $code;
			$this->register_change( 'code' );
		}

	}

	/**
	 * Set the Tax/VAT number
	 *
	 * @since 1.6.8
	 *
	 * @param string $tax_number
	 */
	public function set_tax_number( $tax_number ) {

		$tax_number = esc_attr( $tax_number );

		if ( $this->data['tax_number'] !== $tax_number ) {
			$this->data['tax_number'] = $tax_number;
			$this->register_change( 'tax_number' );
		}
	}

	/**
	 * Set the phone number
	 *
	 * @since 1.6.8
	 *
	 * @param string $phone_number
	 */
	public function set_phone( $phone_number ) {

		$phone_number = esc_attr( $phone_number );

		if ( $this->data['phone'] !== $phone_number ) {
			$this->data['phone'] = $phone_number;
			$this->register_change( 'phone' );
		}
	}

	/**
	 * Set the fax number
	 *
	 * @since 1.6.8
	 *
	 * @param string $fax_number
	 */
	public function set_fax( $fax_number ) {

		$fax_number = esc_attr( $fax_number );

		if ( $this->data['fax'] !== $fax_number ) {
			$this->data['fax'] = $fax_number;
			$this->register_change( 'fax' );
		}
	}

	/**
	 * Set the website
	 *
	 * @since 1.6.8
	 *
	 * @param string $website
	 */
	public function set_website( $website ) {

		$website = esc_url( $website );

		if ( $this->data['website'] !== $website ) {
			$this->data['website'] = $website;
			$this->register_change( 'website' );
		}
	}

	/**
	 * Set the ordering URL
	 *
	 * @since 1.6.8
	 *
	 * @param string $ordering_url
	 */
	public function set_ordering_url( $ordering_url ) {

		$ordering_url = esc_url( $ordering_url );

		if ( $this->data['ordering_url'] !== $ordering_url ) {
			$this->data['ordering_url'] = $ordering_url;
			$this->register_change( 'ordering_url' );
		}
	}

	/**
	 * Set the general email
	 *
	 * @since 1.6.8
	 *
	 * @param string $general_email
	 */
	public function set_general_email( $general_email ) {

		$general_email = sanitize_email( $general_email );

		if ( $this->data['general_email'] !== $general_email ) {
			$this->data['general_email'] = $general_email;
			$this->register_change( 'general_email' );
		}
	}

	/**
	 * Set the ordering email
	 *
	 * @since 1.6.8
	 *
	 * @param string $ordering_email
	 */
	public function set_ordering_email( $ordering_email ) {

		$ordering_email = sanitize_email( $ordering_email );

		if ( $this->data['ordering_email'] !== $ordering_email ) {
			$this->data['ordering_email'] = $ordering_email;
			$this->register_change( 'ordering_email' );
		}
	}

	/**
	 * Set the use default description
	 *
	 * @since 1.9.19
	 *
	 * @param string|bool $use_default_description
	 */
	public function set_use_default_description( $use_default_description ) {

		$use_default_description = wc_bool_to_string( $use_default_description );

		if ( $this->data['use_default_description'] !== $use_default_description ) {
			$this->data['use_default_description'] = $use_default_description;
			$this->register_change( 'use_default_description' );
		}
	}

	/**
	 * Set the currency
	 *
	 * @since 1.6.8
	 *
	 * @param string $currency
	 */
	public function set_currency( $currency ) {

		$currency = array_key_exists( $currency, get_woocommerce_currencies() ) ? $currency : '';

		if ( $this->data['currency'] !== $currency ) {
			$this->data['currency'] = $currency;
			$this->register_change( 'currency' );
		}
	}

	/**
	 * Set the address
	 *
	 * @since 1.6.8
	 *
	 * @param string $address
	 */
	public function set_address( $address ) {

		$address = esc_attr( $address );

		if ( $this->data['address'] !== $address ) {
			$this->data['address'] = $address;
			$this->register_change( 'address' );
		}
	}

	/**
	 * Set the address 2
	 *
	 * @since 1.6.15
	 *
	 * @param string $address_2
	 */
	public function set_address_2( $address_2 ) {

		$address_2 = esc_attr( $address_2 );

		if ( $this->data['address_2'] !== $address_2 ) {
			$this->data['address_2'] = $address_2;
			$this->register_change( 'address_2' );
		}
	}

	/**
	 * Set the city
	 *
	 * @since 1.6.8
	 *
	 * @param string $city
	 */
	public function set_city( $city ) {

		$city = esc_attr( $city );

		if ( $this->data['city'] !== $city ) {
			$this->data['city'] = $city;
			$this->register_change( 'city' );
		}
	}

	/**
	 * Set the country
	 *
	 * @since 1.6.8
	 *
	 * @param string $country
	 */
	public function set_country( $country ) {

		$country_obj = new \WC_Countries();
		$country     = array_key_exists( $country, $country_obj->get_countries() ) ? $country : '';

		if ( $this->data['country'] !== $country ) {
			$this->data['country'] = $country;
			$this->register_change( 'country' );
		}
	}

	/**
	 * Set the state
	 *
	 * @since 1.6.8
	 *
	 * @param string $state
	 */
	public function set_state( $state ) {

		$state = esc_attr( $state );

		if ( $this->data['state'] !== $state ) {
			$this->data['state'] = $state;
			$this->register_change( 'state' );
		}
	}

	/**
	 * Set the ZIP code
	 *
	 * @since 1.6.8
	 *
	 * @param string $zip_code
	 */
	public function set_zip_code( $zip_code ) {

		$zip_code = esc_attr( $zip_code );

		if ( $this->data['zip_code'] !== $zip_code ) {
			$this->data['zip_code'] = $zip_code;
			$this->register_change( 'zip_code' );
		}
	}

	/**
	 * Set the user assigned to
	 *
	 * @since 1.6.8
	 *
	 * @param int $user_id
	 */
	public function set_assigned_to( $user_id ) {

		$user_id = absint( $user_id );

		if ( $this->data['assigned_to'] !== $user_id ) {
			$this->data['assigned_to'] = $user_id;
			$this->register_change( 'assigned_to' );
		}
	}

	/**
	 * Set the location
	 *
	 * @since 1.6.8
	 *
	 * @param string $location
	 */
	public function set_location( $location ) {

		$location = esc_attr( $location );

		if ( $this->data['location'] !== $location ) {
			$this->data['location'] = $location;
			$this->register_change( 'location' );
		}
	}

	/**
	 * Set the discount percentage
	 *
	 * @since 1.6.9
	 *
	 * @param int|float $discount
	 */
	public function set_discount( $discount ) {

		$discount = floatval( $discount );

		// If it has decimals, allow max 2.
		if ( floor( $discount ) !== $discount ) {
			$discount = wc_format_decimal( $discount, 2, TRUE );
		}

		if ( $this->data['discount'] !== $discount ) {
			$this->data['discount'] = $discount;
			$this->register_change( 'discount' );
		}
	}

	/**
	 * Set the tax rate
	 *
	 * @since 1.6.9
	 *
	 * @param int|float $tax_rate
	 */
	public function set_tax_rate( $tax_rate ) {

		$tax_rate = floatval( $tax_rate );

		// If it has decimals, allow max 2.
		if ( floor( $tax_rate ) !== $tax_rate ) {
			$tax_rate = wc_format_decimal( $tax_rate, 2, TRUE );
		}

		if ( $this->data['tax_rate'] !== $tax_rate ) {
			$this->data['tax_rate'] = $tax_rate;
			$this->register_change( 'tax_rate' );
		}
	}

	/**
	 * Set the lead time
	 *
	 * @since 1.6.9
	 *
	 * @param int $lead_time
	 */
	public function set_lead_time( $lead_time ) {

		$lead_time = absint( $lead_time );

		if ( $this->data['lead_time'] !== $lead_time ) {
			$this->data['lead_time'] = $lead_time;
			$this->register_change( 'lead_time' );
		}
	}

	/**
	 * Set the payments and delivery terms
	 *
	 * @since 1.9.2
	 *
	 * @param string $delivery_terms
	 */
	public function set_delivery_terms( $delivery_terms ) {

		$delivery_terms = wp_kses_post( $delivery_terms );

		if ( $this->data['delivery_terms'] !== $delivery_terms ) {
			$this->data['delivery_terms'] = $delivery_terms;
			$this->register_change( 'delivery_terms' );
		}
	}

	/**
	 * Set the use default terms
	 *
	 * @since 1.9.19
	 *
	 * @param string|bool $use_default_terms
	 */
	public function set_use_default_terms( $use_default_terms ) {

		$use_default_terms = wc_bool_to_string( $use_default_terms );

		if ( $this->data['use_default_terms'] !== $use_default_terms ) {
			$this->data['use_default_terms'] = $use_default_terms;
			$this->register_change( 'use_default_terms' );
		}
	}

	/**
	 * Set number of days to cancel
	 *
	 * @since 1.9.2
	 *
	 * @param int $days_to_cancel
	 */
	public function set_days_to_cancel( $days_to_cancel ) {

		$days_to_cancel = absint( $days_to_cancel );

		if ( $this->data['days_to_cancel'] !== $days_to_cancel ) {
			$this->data['days_to_cancel'] = $days_to_cancel;
			$this->register_change( 'days_to_cancel' );
		}
	}

	/**
	 * Set the cancellation policy
	 *
	 * @since 1.9.2
	 *
	 * @param string $cancelation_policy
	 */
	public function set_cancelation_policy( $cancelation_policy ) {

		$cancelation_policy = wp_kses_post( $cancelation_policy );

		if ( $this->data['cancelation_policy'] !== $cancelation_policy ) {
			$this->data['cancelation_policy'] = $cancelation_policy;
			$this->register_change( 'cancelation_policy' );
		}
	}

	/**
	 * Set WPML lang. Only when WPML is active
	 *
	 * @since 1.9.30
	 *
	 * @param string $wpml_lang
	 */
	public function set_wpml_lang( $wpml_lang ) {

		$wpml_lang = esc_attr( $wpml_lang );

		if ( $this->data['wpml_lang'] !== $wpml_lang ) {
			$this->data['wpml_lang'] = $wpml_lang;
			$this->register_change( 'wpml_lang' );
		}
	}

	/**
	 * Set the supplier barcode
	 *
	 * @since 1.9.32
	 *
	 * @param string $atum_barcode
	 */
	public function set_atum_barcode( $atum_barcode ) {

		$atum_barcode = esc_attr( $atum_barcode );

		if ( $this->data['atum_barcode'] !== $atum_barcode ) {
			$this->data['atum_barcode'] = $atum_barcode;
			$this->register_change( 'atum_barcode' );
		}

	}

	/***********
	 * GETTERS
	 ***********/

	/**
	 * Get the suppliers post type
	 *
	 * @since 1.9.34
	 *
	 * @return string
	 */
	public function get_post_type() {
		return Suppliers::POST_TYPE;
	}

	/**
	 * Get an array of supplier data keys
	 *
	 * @since 1.9.39
	 *
	 * @return string[]
	 */
	public function get_data_keys() {
		return array_keys( $this->data );
	}

}
