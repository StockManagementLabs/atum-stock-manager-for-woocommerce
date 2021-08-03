<?php
/**
 * The Supplier model class
 *
 * @since       1.6.8
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2021 Stock Management Labs™
 *
 * @package     Atum\Suppliers
 */

namespace Atum\Suppliers;

defined( 'ABSPATH' ) || die;

/**
 * Class Supplier
 *
 * @property int    $id
 * @property string $name
 * @property string $code
 * @property string $tax_number
 * @property string $phone
 * @property string $fax
 * @property string $website
 * @property string $ordering_url
 * @property string $general_email
 * @property string $ordering_email
 * @property string $description
 * @property string $currency
 * @property string $address
 * @property string $city
 * @property string $country
 * @property string $state
 * @property string $zip_code
 * @property int    $assigned_to
 * @property string $location
 * @property int    $thumbnail_id
 * @property int    $discount
 * @property int    $tax_rate
 * @property int    $lead_time
 * @property string $delivery_terms
 * @property int    $days_to_cancel
 * @property string $cancelation_policy
 */
class Supplier {

	/**
	 * The supplier's ID
	 *
	 * @var int
	 */
	protected $id = NULL;

	/**
	 * The post associated to this supplier
	 *
	 * @var \WP_Post
	 */
	protected $post;

	/**
	 * Stores the supplier's data
	 *
	 * @var array
	 */
	protected $data = array(
		'name'               => '',
		'code'               => '',
		'tax_number'         => '',
		'phone'              => '',
		'fax'                => '',
		'website'            => '',
		'ordering_url'       => '',
		'general_email'      => '',
		'ordering_email'     => '',
		'description'        => '',
		'currency'           => '',
		'address'            => '',
		'city'               => '',
		'country'            => '',
		'state'              => '',
		'zip_code'           => '',
		'assigned_to'        => NULL,
		'location'           => '',
		'thumbnail_id'       => NULL,
		'discount'           => NULL,
		'tax_rate'           => NULL,
		'lead_time'          => NULL,
		'delivery_terms'     => '',
		'days_to_cancel'     => NULL,
		'cancelation_policy' => '',
	);

	/**
	 * Changes made to the supplier that should be updated
	 *
	 * @var array
	 */
	private $changes = array();


	/**
	 * Supplier constructor.
	 *
	 * @since 1.6.8
	 *
	 * @param int $id
	 */
	public function __construct( $id ) {

		$this->post = get_post( $id );
		$this->id   = $id;
		$this->read_data();

	}

	/**
	 * Read the supplier's data from db
	 *
	 * @since 1.6.8
	 */
	public function read_data() {

		$this->data = apply_filters( 'atum/supplier/data', $this->data );

		if ( $this->post ) {

			// Get the name from the inherent post.
			$this->data['name'] = $this->post->post_title;

			// Get the rest of the data from meta.
			$meta_data = get_metadata( 'post', $this->id, '', TRUE );

			if ( is_array( $meta_data ) ) {

				foreach ( $meta_data as $meta_key => $meta_value ) {

					$data_name = ltrim( $meta_key, '_' );
					if ( array_key_exists( $data_name, $this->data ) ) {
						$this->data[ $data_name ] = is_array( $meta_value ) ? current( $meta_value ) : $meta_value;
					}

				}

			}

		}

	}

	/**
	 * Register any change done to any data field
	 *
	 * @since 1.6.8
	 *
	 * @param string $data_field
	 */
	protected function register_change( $data_field ) {

		if ( ! in_array( $data_field, $this->changes ) ) {
			$this->changes[] = $data_field;
		}

	}

	/**
	 * Save the changes to the database
	 *
	 * @since 1.6.8
	 */
	public function save() {

		if ( ! empty( $this->changes ) ) {

			foreach ( $this->changes as $changed_prop ) {

				if ( empty( $this->data[ $changed_prop ] ) ) {
					delete_post_meta( $this->id, "_$changed_prop" );
				}
				else {
					update_post_meta( $this->id, "_$changed_prop", $this->data[ $changed_prop ] );
				}

			}
		}

	}

	/**********
	 * SETTERS
	 **********/

	/**
	 * Set multiple properties at once
	 *
	 * @since 1.6.8
	 *
	 * @param array $data
	 */
	public function set_data( $data ) {

		if ( is_array( $data ) ) {

			foreach ( $data as $field => $value ) {
				if ( is_callable( array( $this, "set_$field" ) ) ) {
					call_user_func( array( $this, "set_$field" ), $value );
				}
			}

		}

	}

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
	 * Set the description
	 *
	 * @since 1.6.8
	 *
	 * @param string $description
	 */
	public function set_description( $description ) {

		$description = wp_kses_post( $description );

		if ( $this->data['description'] !== $description ) {
			$this->data['description'] = $description;
			$this->register_change( 'description' );
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
	 * @param string $user_id
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
	 * Set the thumbnail ID
	 *
	 * @since 1.6.9
	 *
	 * @param string $thumbnail_id
	 */
	public function set_thumbnail_id( $thumbnail_id ) {

		$thumbnail_id = absint( $thumbnail_id );

		if ( $this->data['thumbnail_id'] !== $thumbnail_id ) {
			$this->data['thumbnail_id'] = $thumbnail_id;
			$this->register_change( 'thumbnail_id' );
		}
	}

	/**
	 * Set the discount
	 *
	 * @since 1.6.9
	 *
	 * @param string $discount
	 */
	public function set_discount( $discount ) {

		$discount = absint( $discount );

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
	 * @param string $tax_rate
	 */
	public function set_tax_rate( $tax_rate ) {

		$tax_rate = absint( $tax_rate );

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
	 * @param string $lead_time
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
	 * Set number of days to cancel
	 *
	 * @since 1.9.2
	 *
	 * @param string $days_to_cancel
	 */
	public function set_days_to_cancel( $days_to_cancel ) {

		$days_to_cancel = absint( $days_to_cancel );

		if ( $this->data['days_to_cancel'] !== $days_to_cancel ) {
			$this->data['days_to_cancel'] = $days_to_cancel;
			$this->register_change( 'days_to_cancel' );
		}
	}

	/**
	 * Set the cancelation policy
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

	/***********
	 * GETTERS
	 ***********/

	/**
	 * Getter for the Supplier's inherent post
	 *
	 * @since 1.6.8
	 *
	 * @return array|\WP_Post|null
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Get the Supplier's thumbnail URL
	 *
	 * @since 1.6.9
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function get_thumb( $size = 'thumbnail' ) {
		return $this->data['thumbnail_id'] ? wp_get_attachment_image( $this->data['thumbnail_id'], $size ) : '';
	}


	/***************
	 * MAGIC METHODS
	 ***************/

	/**
	 * Magic Getter
	 * To avoid illegal access errors, the property being accessed must be declared within data or meta prop arrays
	 *
	 * @since 1.6.8
	 *
	 * @param string $name
	 *
	 * @return mixed|\WP_Error
	 */
	public function __get( $name ) {

		// Search in declared class props.
		if ( isset( $this->$name ) ) {
			return $this->$name;
		}

		// Search in props array.
		if ( array_key_exists( $name, $this->data ) ) {
			return $this->data[ $name ];
		}

		return new \WP_Error( __( 'Invalid property', ATUM_TEXT_DOMAIN ) );

	}

	/**
	 * Magic Unset
	 *
	 * @since 1.6.8
	 *
	 * @param string $name
	 */
	public function __unset( $name ) {

		if ( isset( $this->$name ) ) {
			unset( $this->$name );
		}
		elseif ( array_key_exists( $name, $this->data ) ) {
			unset( $this->data[ $name ] );
		}

	}

}
