<?php
/**
 * The Supplier model class
 *
 * @since       1.6.8
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     Atum\Suppliers
 */

namespace Atum\Suppliers;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumBarcodes;
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
        $this->set_prop( 'code', esc_attr( $code ) );
	}

	/**
	 * Set the Tax/VAT number
	 *
	 * @since 1.6.8
	 *
	 * @param string $tax_number
	 */
	public function set_tax_number( $tax_number ) {
        $this->set_prop( 'tax_number', esc_attr( $tax_number ) );
	}

	/**
	 * Set the phone number
	 *
	 * @since 1.6.8
	 *
	 * @param string $phone_number
	 */
	public function set_phone( $phone_number ) {
        $this->set_prop( 'phone', esc_attr( $phone_number ) );
	}

	/**
	 * Set the fax number
	 *
	 * @since 1.6.8
	 *
	 * @param string $fax_number
	 */
	public function set_fax( $fax_number ) {
        $this->set_prop( 'fax', esc_attr( $fax_number ) );
	}

	/**
	 * Set the website
	 *
	 * @since 1.6.8
	 *
	 * @param string $website
	 */
	public function set_website( $website ) {
        $this->set_prop( 'website', esc_url( $website ) );
	}

	/**
	 * Set the ordering URL
	 *
	 * @since 1.6.8
	 *
	 * @param string $ordering_url
	 */
	public function set_ordering_url( $ordering_url ) {
        $this->set_prop( 'ordering_url', esc_url( $ordering_url ) );
	}

	/**
	 * Set the general email
	 *
	 * @since 1.6.8
	 *
	 * @param string $general_email
	 */
	public function set_general_email( $general_email ) {
        $this->set_prop( 'general_email', sanitize_email( $general_email ) );
	}

	/**
	 * Set the ordering email
	 *
	 * @since 1.6.8
	 *
	 * @param string $ordering_email
	 */
	public function set_ordering_email( $ordering_email ) {
        $this->set_prop( 'ordering_email', sanitize_email( $ordering_email ) );
	}

	/**
	 * Set the use default description
	 *
	 * @since 1.9.19
	 *
	 * @param string|bool $use_default_description
	 */
	public function set_use_default_description( $use_default_description ) {
        $this->set_prop( 'use_default_description', wc_bool_to_string( $use_default_description ) );
	}

	/**
	 * Set the currency
	 *
	 * @since 1.6.8
	 *
	 * @param string $currency
	 */
	public function set_currency( $currency ) {
        $this->set_prop( 'currency', array_key_exists( $currency, get_woocommerce_currencies() ) ? $currency : '' );
	}

	/**
	 * Set the address
	 *
	 * @since 1.6.8
	 *
	 * @param string $address
	 */
	public function set_address( $address ) {
        $this->set_prop( 'address', esc_attr( $address ) );
	}

	/**
	 * Set the address 2
	 *
	 * @since 1.6.15
	 *
	 * @param string $address_2
	 */
	public function set_address_2( $address_2 ) {
        $this->set_prop( 'address_2', esc_attr( $address_2 ) );
	}

	/**
	 * Set the city
	 *
	 * @since 1.6.8
	 *
	 * @param string $city
	 */
	public function set_city( $city ) {
        $this->set_prop( 'city', esc_attr( $city ) );
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
        $this->set_prop( 'country', array_key_exists( $country, $country_obj->get_countries() ) ? $country : '' );
	}

	/**
	 * Set the state
	 *
	 * @since 1.6.8
	 *
	 * @param string $state
	 */
	public function set_state( $state ) {
        $this->set_prop( 'state', esc_attr( $state ) );
	}

	/**
	 * Set the ZIP code
	 *
	 * @since 1.6.8
	 *
	 * @param string $zip_code
	 */
	public function set_zip_code( $zip_code ) {
        $this->set_prop( 'zip_code', esc_attr( $zip_code ) );
	}

	/**
	 * Set the user assigned to
	 *
	 * @since 1.6.8
	 *
	 * @param int $user_id
	 */
	public function set_assigned_to( $user_id ) {
        $this->set_prop( 'assigned_to', absint( $user_id ) );
	}

	/**
	 * Set the location
	 *
	 * @since 1.6.8
	 *
	 * @param string $location
	 */
	public function set_location( $location ) {
        $this->set_prop( 'location', esc_attr( $location ) );
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

        $this->set_prop( 'discount', $discount );

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

        $this->set_prop( 'tax_rate', $tax_rate );

	}

	/**
	 * Set the lead time
	 *
	 * @since 1.6.9
	 *
	 * @param int $lead_time
	 */
	public function set_lead_time( $lead_time ) {
        $this->set_prop( 'lead_time', absint( $lead_time ) );
	}

	/**
	 * Set the payments and delivery terms
	 *
	 * @since 1.9.2
	 *
	 * @param string $delivery_terms
	 */
	public function set_delivery_terms( $delivery_terms ) {
        $this->set_prop( 'delivery_terms', wp_kses_post( $delivery_terms ) );
	}

	/**
	 * Set the use default terms
	 *
	 * @since 1.9.19
	 *
	 * @param string|bool $use_default_terms
	 */
	public function set_use_default_terms( $use_default_terms ) {
        $this->set_prop( 'use_default_terms', wc_bool_to_string( $use_default_terms ) );
	}

	/**
	 * Set number of days to cancel
	 *
	 * @since 1.9.2
	 *
	 * @param int $days_to_cancel
	 */
	public function set_days_to_cancel( $days_to_cancel ) {
        $this->set_prop( 'days_to_cancel', absint( $days_to_cancel ) );
	}

	/**
	 * Set the cancellation policy
	 *
	 * @since 1.9.2
	 *
	 * @param string $cancelation_policy
	 */
	public function set_cancelation_policy( $cancelation_policy ) {
        $this->set_prop( 'cancelation_policy', wp_kses_post( $cancelation_policy ) );
	}

	/**
	 * Set WPML lang. Only when WPML is active
	 *
	 * @since 1.9.30
	 *
	 * @param string $wpml_lang
	 */
	public function set_wpml_lang( $wpml_lang ) {
        $this->set_prop( 'wpml_lang', esc_attr( $wpml_lang ) );
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

            $found_supplier_barcode = AtumBarcodes::get_supplier_id_by_barcode( $this->id, $atum_barcode );

            if ( $found_supplier_barcode ) {
                $this->errors[ 'invalid_barcode' ] = new \WP_Error( 'invalid_barcode', __( 'Invalid or duplicated barcode.', ATUM_TEXT_DOMAIN ) );
            }
            else {
                $this->set_prop( 'atum_barcode', $atum_barcode );
            }

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
