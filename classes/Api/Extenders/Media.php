<?php
/**
 * Extender for the WP's media endpoint
 *
 * @since       1.9.19
 * @author      Be Rebel - https://berebel.io
 * @copyright   ©2022 Stock Management Labs™
 *
 * @package     Atum\Api
 * @subpackage  Extenders
 */

namespace Atum\Api\Extenders;

defined( 'ABSPATH' ) || die;

class Media {

	/**
	 * The singleton instance holder
	 *
	 * @var Media
	 */
	private static $instance;

	/**
	 * The linked post type coming as param in the current request
	 *
	 * @var string
	 */
	private $linked_post_type = '';

	/**
	 * Media constructor
	 *
	 * @since 1.9.19
	 */
	private function __construct() {

		add_filter( 'rest_attachment_collection_params', array( $this, 'add_params' ), 10, 2 );

		add_filter( 'rest_attachment_query', array( $this, 'maybe_filter_linked_post_type' ), 10, 2 );

	}

	/**
	 * Add extra params to the media endpoint.
	 *
	 * @since 1.9.19
	 *
	 * @param array  $query_params
	 * @param string $post_type
	 *
	 * @return array
	 */
	public function add_params( $query_params, $post_type ) {

		$query_params['linked_post_type'] = array(
			'default'     => NULL,
			'description' => __( 'Limit result set to attachments linked to a particular post type.', ATUM_TEXT_DOMAIN ),
			'type'        => 'string',
		);

		return $query_params;

	}

	/**
	 * Check if the endpoint is being filtered by linked_post_type
	 *
	 * @since 1.9.19
	 *
	 * @param array            $args
	 * @param \WP_REST_Request $request
	 */
	public function maybe_filter_linked_post_type( $args, $request ) {

		if ( ! empty( $request['linked_post_type'] ) ) {
			$this->linked_post_type = esc_attr( $request['linked_post_type'] );
			add_filter( 'posts_clauses', array( $this, 'filter_linked_post_type' ), 10, 2 );
		}

		return $args;

	}

	/**
	 * Add extra filtering to the media endpoint for the linked_post_type param
	 *
	 * @since 1.9.19
	 *
	 * @param string[] $clauses {
	 *     Associative array of the clauses for the query.
	 *
	 *     @type string $where    The WHERE clause of the query.
	 *     @type string $groupby  The GROUP BY clause of the query.
	 *     @type string $join     The JOIN clause of the query.
	 *     @type string $orderby  The ORDER BY clause of the query.
	 *     @type string $distinct The DISTINCT clause of the query.
	 *     @type string $fields   The SELECT clause of the query.
	 *     @type string $limits   The LIMIT clause of the query.
	 * }
	 * @param WP_Query $query   The WP_Query instance (passed by reference).
	 */
	public function filter_linked_post_type( $clauses, $query ) {

		remove_filter( 'posts_clauses', array( $this, 'filter_linked_post_type' ) );

		global $wpdb;

		if ( $this->linked_post_type ) {

			if ( strpos( $this->linked_post_type, ',' ) !== FALSE ) {
				$linked_post_types = array_map( 'trim', explode( ',', $this->linked_post_type ) );
			}
			else {
				$linked_post_types = [ $this->linked_post_type ];
			}

			$where_clause = "
				SELECT DISTINCT pm.meta_value FROM $wpdb->posts p
				LEFT JOIN $wpdb->postmeta pm ON (pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id')
				WHERE p.post_type IN ('" . implode( "','", $linked_post_types ) . "')
			";

			$clauses['where'] .= " AND $wpdb->posts.ID IN ($where_clause)";

		}

		return $clauses;

	}


	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Media instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
