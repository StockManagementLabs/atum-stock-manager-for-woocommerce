<?php
/**
 * The abstract model class to be inherited by ATUM custom post type models
 *
 * @since       1.9.34
 * @author      Be Rebel - https://berebel.studio
 * @copyright   ©2024 Stock Management Labs™
 *
 * @package     Atum\Models
 */

namespace Atum\Models;

defined( 'ABSPATH' ) || die;

/**
 * Class AtumCPTModel
 *
 * @property int    $id
 * @property string $name
 * @property string $description
 * @property int    $thumbnail_id
 */
abstract class AtumCPTModel {

	/**
	 * The post ID
	 *
	 * @var int
	 */
	protected $id = NULL;

	/**
	 * The post associated to this model entity
	 *
	 * @var \WP_Post
	 */
	protected $post;

	/**
	 * Stores the post model data
	 *
	 * @var array
	 */
	protected $data = array(
		'name'         => '',
		'description'  => '',
		'thumbnail_id' => NULL,
	);

	/**
	 * Changes made to the model that should be updated
	 *
	 * @var array
	 */
	protected $changes = [];


	/**
	 * Constructor.
	 *
	 * @since 1.9.34
	 *
	 * @param int $id
	 */
	public function __construct( $id = 0 ) {

		$this->id = $id;

		if ( $id ) {
			$this->post = get_post( $id );
			$this->read_data();
		}

	}

	/**
	 * Read the post's data from db
	 *
	 * @since 1.9.34
	 */
	public function read_data() {

		$this->data = apply_filters( 'atum/cpt_model/data', $this->data, $this );

		if ( $this->post ) {

			// Get the name and description from the inherent post.
			$this->data['name']        = $this->post->post_title;
			$this->data['description'] = $this->post->post_content;

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
	 * @since 1.9.34
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
	 * @since 1.9.34
	 *
	 * @return int|\WP_Error
	 */
	public function save() {

		if ( ! empty( $this->changes ) ) {

			/**
			 * Insert.
			 */
			if ( ! $this->id ) {

				// The post name is required.
				if ( in_array( 'name', $this->changes ) ) {

					$post = [
						'post_title'   => $this->data['name'],
						'post_content' => $this->data['description'],
						'post_status'  => 'publish',
						'post_type'    => $this->get_post_type(),
					];

					$meta_input = [];

					foreach ( $this->changes as $changed_prop ) {
						if ( 'name' !== $changed_prop && ! empty( $this->data[ $changed_prop ] ) ) {
							$meta_input[ "_$changed_prop" ] = $this->data[ $changed_prop ];
						}
					}

					$post['meta_input'] = $meta_input;

					return wp_insert_post( $post );

				}
				else {
					return new \WP_Error( 'atum_cpt_name_empty', __( 'The name is required', ATUM_TEXT_DOMAIN ) );
				}

			}
			/**
			 * Update
			 */
			else {

				$post_data = [];

				foreach ( $this->changes as $changed_prop ) {

					if ( 'name' === $changed_prop ) {
						$post_data['post_title'] = $this->data[ 'name' ];
					}
					elseif ( 'description' === $changed_prop ) {
						$post_data['post_content'] = $this->data[ 'description' ];
					}
					else {

						if ( empty( $this->data[ $changed_prop ] ) ) {
							delete_post_meta( $this->id, "_$changed_prop" );
						}
						else {
							update_post_meta( $this->id, "_$changed_prop", $this->data[ $changed_prop ] );
						}

					}

				}

				if ( ! empty( $post_data ) ) {
					$post_data['ID'] = $this->id;
					wp_update_post( $post_data );
				}

			}

		}

		return $this->id;

	}

	/**********
	 * SETTERS
	 **********/

	/**
	 * Set multiple properties at once
	 *
	 * @since 1.9.34
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
	 * Set the description
	 *
	 * @since 1.9.34
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
	 * Set the name
	 *
	 * @since 1.9.34
	 *
	 * @param string $name
	 */
	public function set_name( $name ) {

		$name = sanitize_text_field( $name );

		if ( $this->data['name'] !== $name ) {
			$this->data['name'] = $name;
			$this->register_change( 'name' );
		}
	}

	/**
	 * Set the thumbnail ID
	 *
	 * @since 1.9.34
	 *
	 * @param int $thumbnail_id
	 */
	public function set_thumbnail_id( $thumbnail_id ) {

		$thumbnail_id = absint( $thumbnail_id );

		if ( $this->data['thumbnail_id'] !== $thumbnail_id ) {
			$this->data['thumbnail_id'] = $thumbnail_id;
			$this->register_change( 'thumbnail_id' );
		}
	}


	/***********
	 * GETTERS
	 ***********/

	/**
	 * Getter for the associated post
	 *
	 * @since 1.9.34
	 *
	 * @return array|\WP_Post|null
	 */
	public function get_post() {
		return $this->post;
	}

	/**
	 * Getter for the data prop
	 *
	 * @since 1.9.34
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Getter for the post type associated. Required.
	 *
	 * @since 1.9.34
	 *
	 * @return string
	 */
	abstract public function get_post_type();

	/**
	 * Get the post's thumbnail URL
	 *
	 * @since 1.9.34
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function get_thumb( $size = 'thumbnail' ) {
		return $this->data['thumbnail_id'] ? wp_get_attachment_image( $this->data['thumbnail_id'], $size ) : '';
	}

	/**
	 * Whether the related post still exists.
	 *
	 * @since 1.9.35
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->post );
	}


	/***************
	 * MAGIC METHODS
	 ***************/

	/**
	 * Magic Getter
	 * To avoid illegal access errors, the property being accessed must be declared within data or meta prop arrays
	 *
	 * @since 1.9.34
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
	 * @since 1.9.34
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
